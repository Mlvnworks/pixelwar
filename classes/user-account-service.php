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
        ?string $idPictureUrl = null,
        ?string $studentNumber = null
    ): int
    {
        return $this->transaction(function () use ($userId, $avatarUrl, $firstname, $lastname, $idPictureUrl, $studentNumber): int {
            $imageId = $this->users->insertImage($avatarUrl);
            $idPictureImageId = null;

            if ($idPictureUrl !== null && $idPictureUrl !== '') {
                $idPictureImageId = $this->users->insertImage($idPictureUrl);
            }

            $this->users->upsertUserDetails($userId, $imageId, $firstname, $lastname, $idPictureImageId, $studentNumber);
            $this->users->updateActiveState($userId, 0);

            return $imageId;
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
        bool $emailChanged
    ): int {
        return $this->transaction(function () use ($userId, $firstname, $lastname, $email, $isVerified, $imageId, $newAvatarUrl, $emailChanged): int {
            if ($newAvatarUrl !== null) {
                $imageId = $this->users->insertImage($newAvatarUrl);
            }

            $this->users->updateEmailVerificationState($userId, $email, $isVerified);

            if ($emailChanged) {
                $this->verifications->expirePending($userId, 'account verification');
            }

            $this->users->upsertUserDetails($userId, $imageId, $firstname, $lastname);

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
