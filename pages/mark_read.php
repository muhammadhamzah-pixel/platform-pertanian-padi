<?php
include '../includes/config.php';

// ===== GUARD LOGIN =====
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . $base_url . 'login');
    exit();
}

$user_id = $_SESSION['user_id'];
$notif_id = intval($_GET['id'] ?? 0);

if ($notif_id <= 0) {
    header('Location: dashboard_petani.php');
    exit();
}

// ===== VERIFIKASI CSRF TOKEN =====
if (!isset($_GET['csrf_token']) || !verify_csrf_token($_GET['csrf_token'])) {
    header('Location: dashboard_petani.php?error=csrf');
    exit();
}

// ===== UPDATE STATUS NOTIFIKASI (hanya milik user yg login) =====
$query = "UPDATE notifikasi SET is_read = 1 WHERE id = '$notif_id' AND user_id = '$user_id'";
mysqli_query($conn, $query);

// ===== AMBIL LINK NOTIFIKASI (dengan filter user_id) =====
$link_query = "SELECT link FROM notifikasi WHERE id = '$notif_id' AND user_id = '$user_id'";
$link_result = mysqli_query($conn, $link_query);
$link_data = mysqli_fetch_assoc($link_result);
$link = $link_data['link'] ?? 'dashboard_petani.php';

// ===== VALIDASI LINK (whitelist: cuma boleh relative path .php) =====
if (!preg_match('/^[a-zA-Z0-9_\-]+\.php(\?[a-zA-Z0-9_\-=&]*)?$/', $link)) {
    $link = 'dashboard_petani.php';
}

// ===== REDIRECT =====
header('Location: ' . $link);
exit();
?>