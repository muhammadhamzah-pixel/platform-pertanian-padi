<?php
// includes/header.php
require_once __DIR__ . '/lang.php';

// Deteksi base & relative path CSS otomatis
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host_url = $_SERVER['HTTP_HOST'] ?? 'localhost';
$is_in_public = (strpos($_SERVER['PHP_SELF'] ?? '', '/public/') !== false);
$rel_css_base = $is_in_public ? 'css/' : '../public/css/';
?>
<!DOCTYPE html>
<html lang="<?= get_lang() ?>" dir="<?= get_lang() === 'ar' ? 'rtl' : 'ltr' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= t('app_name') ?></title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Google Font (Plus Jakarta Sans & Poppins) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <!-- CSS Custom -->
    <link rel="stylesheet" href="<?= $rel_css_base ?>style.css?v=2.0">
    <link rel="stylesheet" href="<?= $rel_css_base ?>responsive.css?v=2.0">
</head>
<body>