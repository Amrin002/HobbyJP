<?php
// auth.php - Include file ini di halaman yang memerlukan login

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    // Simpan URL yang diminta untuk redirect setelah login
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];

    // Redirect ke halaman login
    header('Location: login.php');
    exit;
}

// Optional: Cek timeout session (30 menit)
$timeout_duration = 1800; // 30 menit dalam detik

if (isset($_SESSION['login_time'])) {
    $elapsed_time = time() - $_SESSION['login_time'];

    if ($elapsed_time > $timeout_duration) {
        // Session timeout
        session_unset();
        session_destroy();
        header('Location: login.php?timeout=1');
        exit;
    }
}

// Update login time untuk reset timeout
$_SESSION['login_time'] = time();
