<?php

final class TeacherAccountService
{
    public function __construct(
        private mysqli $connection,
        private UserRepository $users,
        private Tools $tools
    ) {
    }

    public function createPendingTeacher(string $username, string $email, string $password): int
    {
        return $this->transaction(function () use ($username, $email, $password): int {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $userId = $this->users->createTeacher($username, $email, $passwordHash);
            $mailSent = $this->sendTeacherWelcomeEmail($email, $username, $password);

            if (!$mailSent) {
                throw new RuntimeException('Teacher account email could not be sent.');
            }

            return $userId;
        });
    }

    public function completeTeacherSetup(
        int $userId,
        string $username,
        string $password,
        string $avatarUrl,
        string $firstname,
        string $lastname
    ): int {
        return $this->transaction(function () use ($userId, $username, $password, $avatarUrl, $firstname, $lastname): int {
            $imageId = $this->users->insertImage($avatarUrl);
            $this->users->upsertUserDetails($userId, $imageId, $firstname, $lastname);
            $this->users->updateTeacherSetupCredentials($userId, $username, password_hash($password, PASSWORD_DEFAULT), 1);

            return $imageId;
        });
    }

    private function sendTeacherWelcomeEmail(string $email, string $username, string $password): bool
    {
        $safeUsername = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
        $safePassword = htmlspecialchars($password, ENT_QUOTES, 'UTF-8');
        $subject = 'Your Pixelwar teacher account is ready';
        $content = '<!doctype html>'
            . '<html lang="en">'
            . '<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Pixelwar Teacher Account</title></head>'
            . '<body style="margin:0;padding:0;background:#f5f6f8;color:#111827;font-family:Arial,Helvetica,sans-serif;">'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="width:100%;background:#f5f6f8;padding:28px 14px;">'
            . '<tr><td align="center">'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:580px;background:#ffffff;border:1px solid #d9dee7;border-radius:20px;overflow:hidden;">'
            . '<tr><td style="padding:22px 24px;border-bottom:1px solid #e5e7eb;background:#ffffff;">'
            . '<p style="margin:0 0 8px;font-size:12px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:#6b7280;">Pixelwar</p>'
            . '<h1 style="margin:0;font-size:28px;line-height:1.2;color:#111827;">Teacher account created</h1>'
            . '</td></tr>'
            . '<tr><td style="padding:24px;">'
            . '<p style="margin:0 0 14px;font-size:15px;line-height:1.7;color:#374151;">A Pixelwar admin created your teacher account. Use the temporary credentials below to sign in and finish your teacher setup.</p>'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border:1px solid #d9dee7;border-radius:16px;background:#f8fafc;">'
            . '<tr><td style="padding:18px 18px 8px;">'
            . '<p style="margin:0 0 6px;font-size:12px;font-weight:700;text-transform:uppercase;color:#6b7280;">Temporary username</p>'
            . '<p style="margin:0;font-size:20px;font-weight:700;color:#111827;">' . $safeUsername . '</p>'
            . '</td></tr>'
            . '<tr><td style="padding:8px 18px 18px;">'
            . '<p style="margin:0 0 6px;font-size:12px;font-weight:700;text-transform:uppercase;color:#6b7280;">Temporary password</p>'
            . '<p style="margin:0;font-size:20px;font-weight:700;color:#111827;">' . $safePassword . '</p>'
            . '</td></tr>'
            . '</table>'
            . '<div style="margin:18px 0 0;padding:14px 16px;border-radius:14px;background:#fff7ed;border:1px solid #fed7aa;">'
            . '<p style="margin:0;font-size:14px;line-height:1.6;color:#9a3412;">On first login, you will be required to set your final username, final password, first name, last name, and profile avatar before entering the teacher panel.</p>'
            . '</div>'
            . '<p style="margin:18px 0 0;font-size:13px;line-height:1.6;color:#6b7280;">If you did not expect this account, contact your admin.</p>'
            . '</td></tr>'
            . '</table>'
            . '</td></tr>'
            . '</table>'
            . '</body></html>';

        $result = $this->tools->sendEmail($content, $email, $subject);

        if (empty($result['success'])) {
            $error = $result['err'] ?? null;
            error_log('Pixelwar teacher account email failed: ' . ($error instanceof Throwable ? $error->getMessage() : 'Unknown mail error'));

            return false;
        }

        return true;
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
