<?php
session_start();

function check_csrf()
{
    $headers = apache_request_headers();
    $clientToken = $headers['X-CSRF-Token'] ?? '';

    // Fallback for non-Apache servers or missing headers
    if (!$clientToken && isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
        $clientToken = $_SERVER['HTTP_X_CSRF_TOKEN'];
    }

    if (empty($_SESSION['csrf_token']) || $clientToken !== $_SESSION['csrf_token']) {
        header('HTTP/1.1 403 Forbidden');
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Unauthorized: Invalid or missing CSRF token']);
        exit;
    }
}

// If this file is included directly, run the check
if (basename($_SERVER['PHP_SELF']) !== 'index.php') {
    check_csrf();
}
