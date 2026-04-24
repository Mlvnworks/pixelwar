<?php

if ($teacherRequestMethod === 'POST' && $teacherRequestedPage === 'challenge-view') {
    try {
        if (!teacherPanelValidateCsrf()) {
            $_SESSION['alert'] = [
                'error' => true,
                'content' => 'Security check failed. Refresh the page and try again.',
            ];
            teacherPanelRedirect('dashboard');
        }

        $teacherId = (int) ($_SESSION['user_id'] ?? 0);
        $challengeId = (int) ($_POST['challenge_id'] ?? 0);
        $action = trim((string) ($_POST['challenge_action'] ?? ''));

        if ($teacherId <= 0) {
            teacherPanelRootRedirect('login');
        }

        if ($challengeId <= 0) {
            throw new RuntimeException('Challenge record is missing.');
        }

        $users = teacherPanelRequireUserRepository($userRepository ?? null);
        $challenges = teacherPanelRequireChallengeRepository($challengeRepository ?? null);
        $logs = teacherPanelRequireActivityLogRepository($activityLogRepository ?? null);
        $teacher = $users->findAuthUserById($teacherId);
        $challenge = $challenges->findCreatedChallengeForOwner($challengeId, $teacherId);

        if ($teacher === null || (int) ($teacher['role_id'] ?? 0) !== 2) {
            throw new RuntimeException('Teacher session could not be confirmed.');
        }

        if ($challenge === null) {
            throw new RuntimeException('You can only manage your own challenges.');
        }

        $challengeName = trim((string) ($challenge['name'] ?? ('Challenge #' . $challengeId)));

        if ($action === 'set_visibility') {
            $status = (int) ($_POST['status'] ?? 0);

            if (!in_array($status, [0, 1], true)) {
                throw new RuntimeException('Challenge visibility is invalid.');
            }

            $challenges->updateChallengeVisibilityForOwner($challengeId, $teacherId, $status);
            $logs->create(
                $teacherId,
                'challenge',
                ($status === 1 ? 'Published challenge "' : 'Set challenge "' ) . $challengeName . ($status === 1 ? '" to public.' : '" to only me.')
            );
            $_SESSION['alert'] = [
                'error' => false,
                'content' => $status === 1 ? 'Challenge is now public.' : 'Challenge is now visible only to you.',
            ];

            teacherPanelRedirect('challenge-view&id=' . $challengeId);
        }

        if ($action === 'delete') {
            $password = (string) ($_POST['teacher_password'] ?? '');

            if ($password === '') {
                throw new RuntimeException('Enter your password to delete this challenge.');
            }

            if (!password_verify($password, (string) ($teacher['password'] ?? ''))) {
                throw new RuntimeException('Teacher password is incorrect.');
            }

            $challenges->softDeleteChallengeForOwner($challengeId, $teacherId);
            $logs->create($teacherId, 'challenge', 'Deleted challenge "' . $challengeName . '".');
            $_SESSION['alert'] = [
                'error' => false,
                'content' => 'Challenge deleted successfully.',
            ];

            teacherPanelRedirect('challenges');
        }

        throw new RuntimeException('Challenge action is not supported.');
    } catch (Throwable $err) {
        error_log('Pixelwar teacher challenge management error: ' . $err->getMessage());
        $_SESSION['alert'] = [
            'error' => true,
            'content' => APP_DEBUG ? $err->getMessage() : 'Unable to update the challenge right now.',
        ];

        $redirectChallengeId = (int) ($_POST['challenge_id'] ?? 0);
        if ($redirectChallengeId > 0) {
            teacherPanelRedirect('challenge-view&id=' . $redirectChallengeId);
        }

        teacherPanelRedirect('challenges');
    }
}
