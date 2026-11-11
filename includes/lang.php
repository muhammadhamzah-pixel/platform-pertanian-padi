<?php
// includes/lang.php

// ===== DAFTAR BAHASA YANG DIDUKUNG =====
$available_langs = ['id', 'en', 'es', 'zh', 'fr', 'de', 'ar'];

// ===== TENTUKAN BAHASA AKTIF =====
if (isset($_GET['lang']) && in_array($_GET['lang'], $available_langs)) {
    $current_lang = $_GET['lang'];
    setcookie('lang', $current_lang, time() + (30 * 24 * 60 * 60), '/');
    $_SESSION['lang'] = $current_lang;
} elseif (isset($_COOKIE['lang']) && in_array($_COOKIE['lang'], $available_langs)) {
    $current_lang = $_COOKIE['lang'];
    $_SESSION['lang'] = $current_lang;
} elseif (isset($_SESSION['lang']) && in_array($_SESSION['lang'], $available_langs)) {
    $current_lang = $_SESSION['lang'];
} else {
    $browser_lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'id', 0, 2);
    $current_lang = in_array($browser_lang, $available_langs) ? $browser_lang : 'id';
    $_SESSION['lang'] = $current_lang;
}

// ===== LOAD FILE BAHASA =====
$lang_file = __DIR__ . '/lang/' . $current_lang . '.php';
if (file_exists($lang_file)) {
    $lang = include $lang_file;
} else {
    $lang = include __DIR__ . '/lang/id.php';
}

// ===== FUNGSI t() =====
function t($key) {
    global $lang;
    return $lang[$key] ?? $key;
}

// ===== FUNGSI get_lang() =====
function get_lang() {
    global $current_lang;
    return $current_lang;
}

// ===== FUNGSI list_langs() =====
function list_langs() {
    return [
        'id' => ['flag' => '🇮🇩', 'name' => 'Indonesia'],
        'en' => ['flag' => '🇬🇧', 'name' => 'English'],
        'es' => ['flag' => '🇪🇸', 'name' => 'Español'],
        'zh' => ['flag' => '🇨🇳', 'name' => '中文'],
        'fr' => ['flag' => '🇫🇷', 'name' => 'Français'],
        'de' => ['flag' => '🇩🇪', 'name' => 'Deutsch'],
        'ar' => ['flag' => '🇸🇦', 'name' => 'العربية'],
    ];
}
?>