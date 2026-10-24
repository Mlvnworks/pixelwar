<?php

class UserAccountService
{
    public function __construct(
        private mysqli $connection,
        private UserRepository $users,
        private VerificationRepository $verifications
    ) {
    }

    public function createStudentWithVerification(string $username, string $email, string $passwordHash, string $tokenHash): int
    {
        return $this->transaction(function () use ($username, $email, $passwordHash, $tokenHash): int {
            $userId = $this->users->createStudent($username, $email, $passwordHash);
            $this->verifications->create($userId, 'account verification', $tokenHash, 0);

            return $userId;
        });
    }

    public function changeVerificationEmail(int $userId, string $email): void
    {
        $this->transaction(function () use ($userId, $email): void {
            $this->users->updateEmailVerificationState($userId, $email, 0);
            $this->verifications->expirePending($userId, 'account verification');
        });
    }

    public function verifyUserEmail(int $userId, int $verificationId): array
    {
        return $this->transaction(function () use ($userId, $verificationId): array {
            $this->verifications->updateStatus($verificationId, 1);
            $this->users->markEmailVerified($userId);

            return $this->users->findBasicUser($userId);
        });
    }

    public function createProfileDetails(
        int $userId,
        string $avatarUrl,
        string $firstname,
        string $lastname,
        ?string $corFileUrl = null,
        ?string $studentNumber = null,
        ?string $section = null
    ): int
    {
        return $this->transaction(function () use ($userId, $avatarUrl, $firstname, $lastname, $corFileUrl, $studentNumber, $section): int {
            $existingDetails = $this->users->findUserDetailsAvatar($userId);
            $existingAvatarUrl = (string) ($existingDetails['avatar_original_url'] ?? $existingDetails['avatar_url'] ?? '');
            $imageId = !empty($existingDetails['image_id']) && $avatarUrl === $existingAvatarUrl
                ? (int) $existingDetails['image_id']
                : $this->users->insertImage($avatarUrl);
            $corFileImageId = null;

            if ($corFileUrl !== null && $corFileUrl !== '') {
                $corFileImageId = !empty($existingDetails['cor_file']) && $corFileUrl === (string) ($existingDetails['cor_file_url'] ?? '')
                    ? (int) $existingDetails['cor_file']
                    : $this->users->insertImage($corFileUrl);
            }

            $this->users->upsertUserDetails($userId, $imageId, $firstname, $lastname, $corFileImageId, $studentNumber, $section);
            $this->users->updateActiveState($userId, 0);

            return $imageId;
        });
    }

    public function deleteBlockedStudentProfile(int $userId, SupabaseStorage $storage): void
    {
        $this->transaction(function () use ($userId, $storage): void {
            $statement = $this->connection->prepare(
                'SELECT user_id FROM users WHERE user_id = ? AND role_id = 3
                 AND is_active = -1 AND date_deleted IS NULL FOR UPDATE'
            );
            $statement->bind_param('i', $userId);
            $statement->execute();
            $student = $statement->get_result()->fetch_assoc();
            $statement->close();
            if (!$student) {
                throw new RuntimeException('Only a blocked student profile can be removed.');
            }

            $statement = $this->connection->prepare(
                'SELECT image_id, cor_file FROM user_details WHERE user_id = ? FOR UPDATE'
            );
            $statement->bind_param('i', $userId);
            $statement->execute();
            $details = $statement->get_result()->fetch_assoc();
            $statement->close();
            if (!$details) {
                return;
            }

            $imageIds = array_values(array_unique(array_filter([
                (int) ($details['image_id'] ?? 0), (int) ($details['cor_file'] ?? 0),
            ])));
            $removableImageIds = [];
            foreach ($imageIds as $imageId) {
                $statement = $this->connection->prepare('SELECT source FROM images WHERE img_id = ? FOR UPDATE');
                $statement->bind_param('i', $imageId);
                $statement->execute();
                $image = $statement->get_result()->fetch_assoc();
                $statement->close();
                if (!$image) {
                    continue;
                }
                $source = trim((string) $image['source']);
                // Shared uploads must remain available to other accounts.
                $statement = $this->connection->prepare(
                    'SELECT ud.user_id FROM user_details ud
                     JOIN images i ON (i.img_id = ud.image_id OR i.img_id = ud.cor_file)
                     WHERE ud.user_id <> ? AND (i.img_id = ? OR i.source = ?) LIMIT 1'
                );
                $statement->bind_param('iis', $userId, $imageId, $source);
                $statement->execute();
                $shared = $statement->get_result()->fetch_assoc() !== null;
                $statement->close();
                if ($shared) {
                    continue;
                }

                $sourcePath = (string) (parse_url($source, PHP_URL_PATH) ?: '');
                if (str_starts_with($sourcePath, '/storage/v1/')) {
                    if (!$storage->deletePublicObject($source)) {
                        throw new RuntimeException('Account blocked, but an uploaded profile file could not be removed. Retry Reject and Block.');
                    }
                }
                $removableImageIds[] = $imageId;
            }

            // Keep database references until storage cleanup succeeds so it can be retried.
            $statement = $this->connection->prepare('DELETE FROM user_details WHERE user_id = ?');
            $statement->bind_param('i', $userId);
            $statement->execute();
            $statement->close();
            foreach ($removableImageIds as $imageId) {
                $statement = $this->connection->prepare('DELETE FROM images WHERE img_id = ?');
                $statement->bind_param('i', $imageId);
                $statement->execute();
                $statement->close();
            }
        });
    }

    public function saveSettingsProfile(
        int $userId,
        string $firstname,
        string $lastname,
        string $email,
        int $isVerified,
        int $imageId,
        ?string $newAvatarUrl,
        bool $emailChanged,
        ?string $username = null
    ): int {
        return $this->transaction(function () use ($userId, $firstname, $lastname, $email, $isVerified, $imageId, $newAvatarUrl, $emailChanged, $username): int {
            $existingDetails = $this->users->findUserDetailsAvatar($userId);
            $existingCorFile = null;
            $existingStudentNumber = null;

            if ($existingDetails !== null) {
                $existingCorFile = isset($existingDetails['cor_file']) ? (int) $existingDetails['cor_file'] : null;
                $studentNumberValue = trim((string) ($existingDetails['student_number'] ?? ''));
                $existingStudentNumber = $studentNumberValue !== '' ? $studentNumberValue : null;
            }

            if ($newAvatarUrl !== null) {
                $imageId = $this->users->insertImage($newAvatarUrl);
            }

            if ($username !== null) {
                $this->users->updateUsername($userId, $username);
            }

            $this->users->updateEmailVerificationState($userId, $email, $isVerified);

            if ($emailChanged) {
                $this->verifications->expirePending($userId, 'account verification');
            }

            $this->users->upsertUserDetails(
                $userId,
                $imageId,
                $firstname,
                $lastname,
                $existingCorFile,
                $existingStudentNumber
            );

            return $imageId;
        });
    }

    public function completeAdminSetup(
        int $userId,
        string $username,
        string $email,
        string $password,
        string $avatarUrl,
        string $firstname,
        string $lastname
    ): int {
        return $this->transaction(function () use ($userId, $username, $email, $password, $avatarUrl, $firstname, $lastname): int {
            $imageId = $this->users->insertImage($avatarUrl);
            $this->users->upsertUserDetails($userId, $imageId, $firstname, $lastname);
            $this->users->updateAdminSetupCredentials($userId, $username, $email, password_hash($password, PASSWORD_DEFAULT), 0);
            $this->verifications->expirePending($userId, 'account verification');

            return $imageId;
        });
    }

    private function transaction(callable $operation): mixed
    {
        $this->connection->begin_transaction();

        try {
            $result = $operation();
            $this->connection->commit();

            return $result;
        } catch (Throwable $err) {
            $this->connection->rollback();
            throw $err;
        }
    }
}
