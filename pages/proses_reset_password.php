<?php
// pages/proses_reset_password.php
header('Content-Type: application/json; charset=utf-8');
include '../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid method']);
    exit();
}

if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
    echo json_encode(['success' => false, 'error' => t('err_csrf')]);
    exit();
}

$token = trim($_POST['token'] ?? '');
$password_new = $_POST['password_new'] ?? '';
$password_confirm = $_POST['password_confirm'] ?? '';

if (empty($token)) {
    echo json_encode(['success' => false, 'error' => t('invalid_token')]);
    exit();
}

if (strlen($password_new) < 6) {
    echo json_encode(['success' => false, 'error' => t('err_password_min')]);
    exit();
}

if ($password_new !== $password_confirm) {
    echo json_encode(['success' => false, 'error' => t('password_mismatch')]);
    exit();
}

$token_esc = mysqli_real_escape_string($conn, $token);

// Cek token validitas
$query = "SELECT * FROM password_resets WHERE token = '$token_esc' AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) === 0) {
    echo json_encode(['success' => false, 'error' => t('invalid_token')]);
    exit();
}

$reset_data = mysqli_fetch_assoc($result);
$email = $reset_data['email'];
$email_esc = mysqli_real_escape_string($conn, $email);

// Hash password baru
$password_hash = password_hash($password_new, PASSWORD_BCRYPT);

// Update password user
$update_sql = "UPDATE users SET password_hash = '$password_hash' WHERE email = '$email_esc'";
if (mysqli_query($conn, $update_sql)) {
    // Hapus token yang sudah dipakai
    mysqli_query($conn, "DELETE FROM password_resets WHERE email = '$email_esc'");
    
    // Catat log aktivitas (Opsional, asumsikan fungsi catat_log bisa tanpa ID jika cuma email)
    // catat_log($conn, null, $email, null, 'Reset Password', 'Berhasil mereset password melalui link email');
    
    echo json_encode(['success' => true, 'message' => t('password_reset_success')]);
} else {
    echo json_encode(['success' => false, 'error' => 'Gagal mengupdate password: ' . mysqli_error($conn)]);
}
?>
