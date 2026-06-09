<?php
if ($requestMethod === 'GET' && $requestedPage === 'google-auth') {
    $googleClientId = trim((string) (defined('GOOGLE_CLIENT_ID') ? GOOGLE_CLIENT_ID : ''));
    $googleClientSecret = trim((string) (defined('GOOGLE_CLIENT_SECRET') ? GOOGLE_CLIENT_SECRET : ''));
    $googleRedirectUri = trim((string) (defined('GOOGLE_REDIRECT_URI') ? GOOGLE_REDIRECT_URI : ''));

    $googleFail = static function (string $message): void {
        $_SESSION['login_errors'] = [$message];
        pixelwarRedirect('login');
    };

    $googleHttpRequest = static function (string $url, string $method = 'GET', array $payload = [], array $headers = []): array {
        $method = strtoupper($method);
        $body = $payload === [] ? '' : http_build_query($payload);
        $headers[] = 'Accept: application/json';

        if ($method === 'POST') {
            $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        }

        if (function_exists('curl_init')) {
            $curl = curl_init($url);
            curl_setopt_array($curl, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_TIMEOUT => 15,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_CUSTOMREQUEST => $method,
            ]);

            if ($method === 'POST') {
                curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
            }

            $responseBody = curl_exec($curl);
            $statusCode = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
            $error = curl_error($curl);
            curl_close($curl);

            if ($responseBody === false) {
                throw new RuntimeException('Google request failed: ' . ($error ?: 'Unknown network error'));
            }
        } else {
            $context = stream_context_create([
                'http' => [
                    'method' => $method,
                    'header' => implode("\r\n", $headers),
                    'content' => $method === 'POST' ? $body : '',
                    'timeout' => 15,
                    'ignore_errors' => true,
                ],
            ]);
            $responseBody = file_get_contents($url, false, $context);
            $statusCode = 0;

            if (isset($http_response_header) && is_array($http_response_header)) {
                foreach ($http_response_header as $headerLine) {
                    if (preg_match('/^HTTP\/\S+\s+(\d+)/', $headerLine, $matches)) {
                        $statusCode = (int) $matches[1];
                        break;
                    }
                }
            }

            if ($responseBody === false) {
                throw new RuntimeException('Google request failed.');
            }
        }

        $decoded = json_decode((string) $responseBody, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('Google returned an invalid response.');
        }

        if ($statusCode >= 400) {
            $googleError = (string) ($decoded['error_description'] ?? $decoded['error'] ?? 'Google request failed.');
            throw new RuntimeException($googleError);
        }

        return $decoded;
    };

    $buildGoogleUsername = static function (UserRepository $users, string $email, string $name): string {
        $base = strtolower((string) preg_replace('/[^a-z0-9_]+/i', '_', strstr($email, '@', true) ?: $name));
        $base = trim($base, '_');
        if (strlen($base) < 3) {
            $base = 'player_' . substr(bin2hex(random_bytes(3)), 0, 6);
        }
        $base = substr($base, 0, 24);
        $username = $base;
        $attempt = 0;

        while ($users->usernameExists($username)) {
            $attempt++;
            $suffix = '_' . substr(bin2hex(random_bytes(3)), 0, 6);
            $username = substr($base, 0, 32 - strlen($suffix)) . $suffix;
            if ($attempt > 12) {
                $username = 'player_' . substr(bin2hex(random_bytes(8)), 0, 16);
                break;
            }
        }

        return $username;
    };

    try {
        if ($googleClientId === '' || $googleClientSecret === '' || $googleRedirectUri === '') {
            $googleFail('Google sign-in is not configured yet.');
        }

        if (isset($_GET['error'])) {
            $googleError = trim((string) ($_GET['error_description'] ?? $_GET['error'] ?? ''));
            $googleFail($googleError !== ''
                ? 'Google sign-in failed: ' . $googleError
                : 'Google sign-in was cancelled or denied.');
        }

        $code = trim((string) ($_GET['code'] ?? ''));
        if ($code === '') {
            $state = bin2hex(random_bytes(24));
            $_SESSION['google_oauth_state'] = $state;
            $authUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
                'client_id' => $googleClientId,
                'redirect_uri' => $googleRedirectUri,
                'response_type' => 'code',
                'scope' => 'openid email profile',
                'state' => $state,
                'access_type' => 'online',
                'prompt' => 'select_account',
            ]);
            header('Location: ' . $authUrl);
            exit;
        }

        $state = (string) ($_GET['state'] ?? '');
        $expectedState = (string) ($_SESSION['google_oauth_state'] ?? '');
        unset($_SESSION['google_oauth_state']);

        if ($state === '' || $expectedState === '' || !hash_equals($expectedState, $state)) {
            $googleFail('Google sign-in security check failed. Try again.');
        }

        $tokenPayload = $googleHttpRequest('https://oauth2.googleapis.com/token', 'POST', [
            'code' => $code,
            'client_id' => $googleClientId,
            'client_secret' => $googleClientSecret,
            'redirect_uri' => $googleRedirectUri,
            'grant_type' => 'authorization_code',
        ]);

        $accessToken = trim((string) ($tokenPayload['access_token'] ?? ''));
        if ($accessToken === '') {
            throw new RuntimeException('Google did not return an access token.');
        }

        $googleUser = $googleHttpRequest('https://www.googleapis.com/oauth2/v3/userinfo', 'GET', [], [
            'Authorization: Bearer ' . $accessToken,
        ]);

        $email = strtolower(trim((string) ($googleUser['email'] ?? '')));
        $emailVerified = filter_var($googleUser['email_verified'] ?? false, FILTER_VALIDATE_BOOL);
        $name = trim((string) ($googleUser['name'] ?? ''));
        $firstname = trim((string) ($googleUser['given_name'] ?? ''));
        $lastname = trim((string) ($googleUser['family_name'] ?? ''));
        $picture = trim((string) ($googleUser['picture'] ?? ''));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !$emailVerified) {
            $googleFail('Google did not confirm a verified email for this account.');
        }

        $users = pixelwarRequireUserRepository($userRepository);
        $deletedUser = $users->findDeletedLoginUser($email);
        if ($deletedUser !== null) {
            $googleFail('You no longer have access to this account. If you think this is a mistake, please contact your admin or instructor.');
        }

        $user = $users->findUserByEmail($email);
        if ($user !== null) {
            if ((int) ($user['role_id'] ?? 0) !== pixelwarStudentRoleId()) {
                $googleFail('Google sign-in is only available for student accounts.');
            }

            if (strtolower((string) ($user['acc_type'] ?? 'manual')) !== 'google') {
                $googleFail('This email is registered with manual login. Use your password to continue.');
            }

            if ((int) ($user['is_verified'] ?? 0) !== 1) {
                $users->markEmailVerified((int) $user['user_id']);
                $user = $users->findUserByEmail($email);
            }
        } else {
            $username = $buildGoogleUsername($users, $email, $name !== '' ? $name : $email);
            $userId = $users->createGoogleStudent($username, $email);

            if ($firstname === '' && $name !== '') {
                $parts = preg_split('/\s+/', $name) ?: [];
                $firstname = trim((string) ($parts[0] ?? ''));
                $lastname = trim(implode(' ', array_slice($parts, 1)));
            }

            if ($picture !== '') {
                $imageId = $users->insertImage($picture);
                $users->upsertUserDetails($userId, $imageId, $firstname !== '' ? $firstname : $username, $lastname, null, null);
            }

            pixelwarLogActivity($activityLogRepository ?? null, $userId, 'account', 'Created student account using Google.');
            $user = $users->findUserByEmail($email);
        }

        if ($user === null) {
            throw new RuntimeException('Google account was not available after sign-in.');
        }

        session_regenerate_id(true);
        pixelwarLogActivity($activityLogRepository ?? null, (int) $user['user_id'], 'auth', 'Logged in using Google.');
        $sessionUser = $users->findSessionUser((int) $user['user_id']) ?: $user;
        pixelwarRedirectAfterAuthState($users, $sessionUser);
    } catch (Throwable $err) {
        error_log('Pixelwar Google auth error: ' . $err->getMessage());
        $_SESSION['login_errors'] = ['Google sign-in failed. Please try again.'];
        pixelwarRedirect('login');
    }
}
