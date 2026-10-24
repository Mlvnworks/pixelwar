<?php

if ($adminRequestMethod === 'POST' && $adminRequestedPage === 'student-verification' && isset($_POST['action'])) {
    try {
        if (!hash_equals((string) ($_SESSION['_csrf_token'] ?? ''), (string) ($_POST['_csrf_token'] ?? ''))) {
            $_SESSION['alert'] = [
                'error' => true,
                'content' => 'Security check failed. Refresh the page and try again.',
            ];
            adminPanelRedirect('student-verification');
        }

        $action = trim((string) ($_POST['action'] ?? ''));
        $studentId = (int) ($_POST['student_id'] ?? 0);
        $users = adminPanelRequireUserRepository($userRepository);
        $logs = adminPanelRequireActivityLogRepository($activityLogRepository);
        $toolsService = adminPanelRequireTools($tools ?? null);

        if ($studentId <= 0) {
            throw new RuntimeException('Student record is missing.');
        }

        $adminUser = $users->findAuthUserById((int) ($_SESSION['user_id'] ?? 0));

        if ($adminUser === null || (int) ($adminUser['role_id'] ?? 0) !== 1) {
            throw new RuntimeException('Admin session could not be confirmed.');
        }

        $student = $users->findSessionUser($studentId);

        if ($student === null || (int) ($student['role_id'] ?? 0) !== 3) {
            throw new RuntimeException('Student account was not found.');
        }

        $adminName = trim((string) ($_SESSION['firstname'] ?? $_SESSION['username'] ?? 'Admin')) ?: 'Admin';
        $studentLabel = trim((string) ($student['firstname'] ?? '')) !== '' || trim((string) ($student['lastname'] ?? '')) !== ''
            ? trim((string) $student['firstname'] . ' ' . (string) $student['lastname'])
            : ((string) ($student['username'] ?? ('Student #' . $studentId)));

        if ($action === 'approve') {
            $users->updateActiveState($studentId, 1);
            $logs->create((int) ($_SESSION['user_id'] ?? 0), 'student', 'Approved student verification for ' . $studentLabel . ' via admin review queue.');
            adminPanelSendStudentReviewEmail($toolsService, (string) ($student['email'] ?? ''), $studentLabel, 'approved');
            $_SESSION['alert'] = [
                'error' => false,
                'content' => 'Student access approved successfully.',
            ];
        } elseif ($action === 'reject') {
            $users->updateActiveState($studentId, -1);
            $logs->create((int) ($_SESSION['user_id'] ?? 0), 'student', 'Rejected student verification for ' . $studentLabel . ' via admin review queue.');
            try {
                if (!$userAccountService instanceof UserAccountService) {
                    throw new RuntimeException('Profile cleanup service is unavailable.');
                }
                $storage = new SupabaseStorage(SUPABASE_URL, SUPABASE_SERVICE_ROLE_KEY, SUPABASE_STORAGE_BUCKET, '');
                $userAccountService->deleteBlockedStudentProfile($studentId, $storage);
            } catch (Throwable $cleanupError) {
                error_log('Pixelwar blocked student cleanup failed: ' . $cleanupError->getMessage());
                throw new RuntimeException('Student access is blocked, but profile cleanup did not finish. Retry Reject and Block.');
            }
            adminPanelSendStudentReviewEmail($toolsService, (string) ($student['email'] ?? ''), $studentLabel, 'rejected');
            $_SESSION['alert'] = [
                'error' => false,
                'content' => 'Student access blocked. Submitted profile information and uploaded profile files have been removed.',
            ];
        } elseif ($action === 'resubmission') {
            $users->updateActiveState($studentId, -2);
            $logs->create((int) ($_SESSION['user_id'] ?? 0), 'student', 'Requested profile resubmission for ' . $studentLabel . ' via admin review queue.');
            adminPanelSendStudentReviewEmail($toolsService, (string) ($student['email'] ?? ''), $studentLabel, 'resubmission');
            $_SESSION['alert'] = [
                'error' => false,
                'content' => 'Student can now correct and resubmit their saved profile for review.',
            ];
        } else {
            throw new RuntimeException('Verification action is not supported.');
        }
    } catch (Throwable $err) {
        error_log('Pixelwar admin student verification error: ' . $err->getMessage());
        $_SESSION['alert'] = [
            'error' => true,
            'content' => isset($action) && $action === 'reject'
                ? 'The rejection could not be fully completed. Check the student status and retry Reject and Block if cleanup is pending.'
                : (APP_DEBUG ? $err->getMessage() : 'Unable to update the student verification right now.'),
        ];
    }

    adminPanelRedirect('student-verification');
}
