<?php

if ($teacherRequestMethod === 'POST' && $teacherRequestedPage === 'students' && (string) ($_POST['students_action'] ?? '') === 'snapshot') {
    if (!teacherPanelValidateCsrf()) {
        teacherPanelJsonResponse(['success' => false], 403);
    }

    $search = trim((string) ($_POST['q'] ?? ''));
    $status = strtolower(trim((string) ($_POST['status'] ?? 'all')));
    $statusMap = [
        'all' => null,
        'verified' => 1,
        'pending' => 0,
        'rejected' => -1,
    ];
    $activeFilter = $statusMap[$status] ?? null;
    $perPage = 25;
    $requestedPage = max(1, (int) ($_POST['page'] ?? 1));

    if (!$userRepository instanceof UserRepository) {
        teacherPanelJsonResponse(['success' => false, 'message' => 'Student records are unavailable.'], 422);
    }

    $totalCount = $userRepository->countUsersByRoleFiltered(3, $search, $activeFilter);
    $totalPages = max(1, (int) ceil($totalCount / $perPage));
    $page = min($requestedPage, $totalPages);
    $offset = ($page - 1) * $perPage;
    $students = $userRepository->listUsersByRoleFiltered(3, $search, $activeFilter, $perPage, $offset);

    teacherPanelJsonResponse([
        'success' => true,
        'counts' => [
            'total' => $totalCount,
            'verified' => $userRepository->countUsersByRoleFiltered(3, '', 1),
            'pending' => $userRepository->countUsersByRoleFiltered(3, '', 0),
            'rejected' => $userRepository->countUsersByRoleFiltered(3, '', -1),
        ],
        'pagination' => [
            'page' => $page,
            'total_pages' => $totalPages,
            'total_count' => $totalCount,
        ],
        'students' => $students,
    ]);
}
