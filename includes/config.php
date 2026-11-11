<?php
// includes/config.php

// ============================================
// ERROR REPORTING (BUAT DEVELOPMENT)
// ============================================
// ⚠️ KALAU UDAH ONLINE, GANTI display_errors JADI 0!
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ============================================
// KONEKSI DATABASE
// ============================================
$host = 'localhost';
$user = 'root';
$password = ''; 
$database = 'db_padi';

$conn = mysqli_connect($host, $user, $password, $database);

if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8mb4");

// ============================================
// SESSION (dengan security settings)
// ============================================
if (session_status() == PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}

// ============================================
// BASE URL (OTOMATIS)
// ============================================
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host_url = $_SERVER['HTTP_HOST'] ?? 'localhost';
$folder_project = '/PLATFORM%20PERTANIAN%20PADI/';
$base_url = $protocol . '://' . $host_url . $folder_project;

// ============================================
// PATH UPLOAD
// ============================================
define('UPLOAD_PATH', $_SERVER['DOCUMENT_ROOT'] . '/PLATFORM%20PERTANIAN%20PADI/uploads/');
define('UPLOAD_URL', $base_url . 'uploads/');

// ============================================
// GMAIL SMTP CONFIG
// ============================================
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_USER', 'platformpadidesa@gmail.com');
define('SMTP_PASS', 'dvlurdyfvllkwkqu');
define('SMTP_PORT', 587);
define('SMTP_FROM_NAME', 'Platform Padi Desa');

// ============================================
// TELEGRAM BOT CONFIG
// ============================================
define('TELEGRAM_BOT_TOKEN', '8987168090:AAF7COKUg0asoYKoD-UQYraOM1GVW8AmHvk');
define('TELEGRAM_BOT_USERNAME', 'PlaformPadiDesa_bot');

// ============================================
// FONNTE WHATSAPP CONFIG
// ============================================
// Ganti string di bawah ini dengan Device Token dari akun Fonnte Anda
define('FONNTE_TOKEN', 'YY7o3nPEsgTifmVuiCHa');

// ============================================
// SETTING LAINNYA
// ============================================
$app_name = 'Platform Pertanian Padi';
$app_version = '1.0';

// ============================================
// SUPER ADMIN (KEAMANAN TINGKAT TINGGI)
// ============================================
// Cuma email ini yang bisa ubah role admin
define('SUPER_ADMIN_EMAIL', 'admin@padidesa.com');

// ============================================
// FUNGSI GLOBAL
// ============================================
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] == $role;
}

function redirect($url) {
    header('Location: ' . $url);
    exit();
}

function sanitize($input) {
    global $conn;
    return mysqli_real_escape_string($conn, htmlspecialchars(strip_tags($input)));
}

function formatDate($date) {
    return date('d/m/Y H:i', strtotime($date));
}

function getUserName($id) {
    global $conn;
    $id = intval($id);
    $query = "SELECT nama FROM users WHERE id = '$id'";
    $result = mysqli_query($conn, $query);
    if ($row = mysqli_fetch_assoc($result)) {
        return $row['nama'];
    }
    return 'Unknown';
}

function getUserRole($id) {
    global $conn;
    $id = intval($id);
    $query = "SELECT role FROM users WHERE id = '$id'";
    $result = mysqli_query($conn, $query);
    if ($row = mysqli_fetch_assoc($result)) {
        return $row['role'];
    }
    return 'unknown';
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] == 'admin';
}

function isPetani() {
    return isset($_SESSION['role']) && $_SESSION['role'] == 'petani';
}

function isPerangkatDesa() {
    return isset($_SESSION['role']) && $_SESSION['role'] == 'perangkat_desa';
}

/**
 * Cek apakah user yang login adalah Super Admin
 */
function isSuperAdmin() {
    return isset($_SESSION['email']) && $_SESSION['email'] === SUPER_ADMIN_EMAIL;
}

// Include fungsi global
include_once __DIR__ . '/functions.php';
include_once __DIR__ . '/validasi.php';
include_once __DIR__ . '/email_helper.php';
include_once __DIR__ . '/lang.php';