<?php
session_start();

// Cấu hình mật khẩu Admin
define('ADMIN_PASS', 'admin123'); // Bạn nên đổi pass này

// Hàm kiểm tra đăng nhập
function checkLogin()
{
    if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
        header('Location: login.php');
        exit;
    }
}

// Hàm xóa thư mục đệ quy
function deleteDir($dirPath)
{
    if (!is_dir($dirPath)) {
        return;
    }
    if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
        $dirPath .= '/';
    }
    $files = glob($dirPath . '*', GLOB_MARK);
    foreach ($files as $file) {
        if (is_dir($file)) {
            deleteDir($file);
        } else {
            unlink($file);
        }
    }
    rmdir($dirPath);
}
