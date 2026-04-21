<?php

if ($teacherRequestMethod === 'POST' && $teacherRequestedPage === 'create-challenge') {
    try {
        if (!teacherPanelValidateCsrf()) {
            $_SESSION['alert'] = [
                'error' => true,
                'content' => 'Session expired. Please try creating the challenge again.',
            ];
            teacherPanelRedirect('create-challenge');
        }

        $teacherId = (int) ($_SESSION['user_id'] ?? 0);
        $challengeId = (int) ($_POST['challenge_id'] ?? 0);

        if ($teacherId <= 0) {
            teacherPanelRootRedirect('login');
        }

        $service = teacherPanelRequireChallengeCreationService($challengeCreationService ?? null);
        $payload = [
            'name' => (string) ($_POST['challenge_name'] ?? ''),
            'instruction' => (string) ($_POST['challenge_instruction'] ?? ''),
            'difficulty' => (string) ($_POST['challenge_difficulty'] ?? ''),
            'html' => (string) ($_POST['html_source_code'] ?? ''),
            'css' => (string) ($_POST['css_source_code'] ?? ''),
        ];
        $result = $challengeId > 0
            ? $service->update($teacherId, $challengeId, $payload)
            : $service->create($teacherId, $payload);

        unset($_SESSION['create_challenge_old']);
        $_SESSION['alert'] = [
            'error' => false,
            'content' => $challengeId > 0 ? 'Challenge updated successfully.' : 'Challenge created successfully.',
        ];

        header('Location: ./?c=challenge-view&id=' . (int) $result['challenge_id']);
        exit;
    } catch (InvalidArgumentException $err) {
        $_SESSION['create_challenge_old'] = [
            'challenge_id' => (int) ($_POST['challenge_id'] ?? 0),
            'name' => (string) ($_POST['challenge_name'] ?? ''),
            'instruction' => (string) ($_POST['challenge_instruction'] ?? ''),
            'difficulty' => (string) ($_POST['challenge_difficulty'] ?? ''),
            'html' => (string) ($_POST['html_source_code'] ?? ''),
            'css' => (string) ($_POST['css_source_code'] ?? ''),
        ];
        $_SESSION['alert'] = [
            'error' => true,
            'content' => $err->getMessage(),
        ];
        $redirect = (int) ($_POST['challenge_id'] ?? 0) > 0
            ? 'create-challenge&edit=' . (int) ($_POST['challenge_id'] ?? 0)
            : 'create-challenge';
        teacherPanelRedirect($redirect);
    } catch (Throwable $err) {
        error_log('Pixelwar challenge creation error: ' . $err->getMessage());
        $_SESSION['create_challenge_old'] = [
            'challenge_id' => (int) ($_POST['challenge_id'] ?? 0),
            'name' => (string) ($_POST['challenge_name'] ?? ''),
            'instruction' => (string) ($_POST['challenge_instruction'] ?? ''),
            'difficulty' => (string) ($_POST['challenge_difficulty'] ?? ''),
            'html' => (string) ($_POST['html_source_code'] ?? ''),
            'css' => (string) ($_POST['css_source_code'] ?? ''),
        ];
        $_SESSION['alert'] = [
            'error' => true,
            'content' => APP_DEBUG ? $err->getMessage() : 'Challenge save failed. Please check Supabase and try again.',
        ];
        $redirect = (int) ($_POST['challenge_id'] ?? 0) > 0
            ? 'create-challenge&edit=' . (int) ($_POST['challenge_id'] ?? 0)
            : 'create-challenge';
        teacherPanelRedirect($redirect);
    }
}
