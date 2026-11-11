<?php
// pages/proses_lupa_password.php
header('Content-Type: application/json; charset=utf-8');
include '../includes/config.php';
// lang.php sudah di-include di dalam config.php

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid method']);
    exit();
}

if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
    echo json_encode(['success' => false, 'error' => t('err_csrf')]);
    exit();
}

$email = trim($_POST['email']);

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'error' => t('err_email_format')]);
    exit();
}

$email_esc = mysqli_real_escape_string($conn, $email);

// Cek apakah email terdaftar di users
$query = "SELECT id, nama FROM users WHERE email = '$email_esc'";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) === 0) {
    echo json_encode(['success' => false, 'error' => t('email_not_found')]);
    exit();
}

$user = mysqli_fetch_assoc($result);

// Generate token acak (menggunakan random_bytes agar secure)
$token = bin2hex(random_bytes(32));

// Hapus token lama untuk email ini (biar gak numpuk)
mysqli_query($conn, "DELETE FROM password_resets WHERE email = '$email_esc'");

// Insert token baru
$sql_insert = "INSERT INTO password_resets (email, token, created_at) VALUES ('$email_esc', '$token', NOW())";
if (mysqli_query($conn, $sql_insert)) {
    
    // Bikin link reset
    // Biasanya ini dikirim via email sungguhan pakai PHPMailer.
    // Karena ini environment lokal (Laragon), kita lempar link-nya langsung ke respon JSON untuk simulasi.
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    // Ambil base folder path, misal: /PLATFORM PERTANIAN PADI/pages/
    $uri_parts = explode('pages/', $_SERVER['REQUEST_URI']);
    $base_uri = rtrim($uri_parts[0], '/');
    
    $reset_link = $protocol . '://' . $host . $base_uri . '/pages/reset_password.php?token=' . $token;
    
    echo json_encode([
        'success' => true, 
        'message' => t('reset_link_sent'),
        'reset_link' => $reset_link // Tampilkan link di UI untuk kemudahan testing tanpa email server
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Gagal membuat token: ' . mysqli_error($conn)]);
}
?>
