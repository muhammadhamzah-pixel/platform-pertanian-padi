<?php
// includes/gemini_config.php

// ============================================
// 1. KONFIGURASI API KEY (3 TINGKAT)
// ============================================
// Muat API key lokal jika ada (agar aman dan tidak bocor ke publik)
if (file_exists(__DIR__ . '/gemini_key_local.php')) {
    include_once __DIR__ . '/gemini_key_local.php';
}

// Fallback placeholder jika key lokal belum disetel
if (!defined('GEMINI_API_KEY_ADMIN')) {
    define('GEMINI_API_KEY_ADMIN', 'YOUR_GEMINI_API_KEY_ADMIN');
}
if (!defined('GEMINI_API_KEY_DESA')) {
    define('GEMINI_API_KEY_DESA', 'YOUR_GEMINI_API_KEY_DESA');
}
if (!defined('GEMINI_API_KEY_PETANI')) {
    define('GEMINI_API_KEY_PETANI', 'YOUR_GEMINI_API_KEY_PETANI');
}

// ============================================
// 2. MODEL & ENDPOINT
// ============================================
// Menggunakan model Gemini 3.5 Flash Lite yang resmi, sangat cepat & hemat kuota
define('GEMINI_MODEL', 'gemini-3.5-flash-lite');

// Endpoint API Gemini
define('GEMINI_ENDPOINT', 'https://generativelanguage.googleapis.com/v1beta/models/' . GEMINI_MODEL . ':generateContent');

// ============================================
// 3. FUNGSI PEMILIH API KEY BERDASARKAN ROLE
// ============================================
/**
 * Mendapatkan API Key yang sesuai dengan role user.
 * Kalau role nggak dikenali, default ke Petani (paling aman).
 */
function getGeminiApiKey($role) {
    if ($role === 'admin') {
        return GEMINI_API_KEY_ADMIN;
    } elseif ($role === 'perangkat_desa') {
        return GEMINI_API_KEY_DESA;
    } else {
        return GEMINI_API_KEY_PETANI; // Default buat petani / selainnya
    }
}
?>
