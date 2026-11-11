<?php
// includes/auth.php
// ========================================================================
// FILE INI SANGAT PENTING UNTUK KEAMANAN (SECURITY MIDDLEWARE)
// Kegunaan: Menjaga agar halaman tidak bisa diakses oleh orang yang belum login.
// ========================================================================

// 1. Pastikan session sudah berjalan
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 2. Pengecekan Login Utama
// Jika tidak ada user_id di session, berarti belum login
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    // Tendang (redirect) balik ke halaman login
    header("Location: ../pages/login.php?msg=harap_login_dahulu");
    exit();
}

// 3. Pengecekan Akun Terblokir (Security Ekstra)
// Mengecek apakah user yang sedang login statusnya diblokir oleh admin
global $conn;
if (isset($conn)) {
    $auth_user_id = mysqli_real_escape_string($conn, $_SESSION['user_id']);
    $query_cek_status = mysqli_query($conn, "SELECT status FROM users WHERE id = '$auth_user_id'");
    
    if ($query_cek_status && mysqli_num_rows($query_cek_status) > 0) {
        $data_user_auth = mysqli_fetch_assoc($query_cek_status);
        
        // Sesuaikan dengan nama field/value di database (misal: 'terblokir' atau 'blocked')
        if (strtolower($data_user_auth['status']) === 'terblokir') {
            // Hancurkan session dan paksa keluar
            session_unset();
            session_destroy();
            header("Location: ../pages/login.php?error=akun_terblokir");
            exit();
        }
    }
}

// Note: Pengecekan Role (Admin/Petani/Desa) biasanya ditaruh di masing-masing
// file halaman (misal: dashboard_petani.php ngecek role == 'petani').
?>
