<?php
include '../includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Kalo user klik "Ya, Logout"
if (isset($_GET['confirm']) && $_GET['confirm'] == 'yes') {
    // Verifikasi CSRF token
    if (!isset($_GET['csrf_token']) || !verify_csrf_token($_GET['csrf_token'])) {
        header('Location: logout.php');
        exit();
    }
    
    // Catat log logout SEBELUM session dihapus
    catat_log(
        $conn,
        $_SESSION['user_id'],
        $_SESSION['email'] ?? null,
        $_SESSION['nama'] ?? null,
        'Logout',
        'User logout dari sistem'
    );
    
    // Hapus session (lengkap)
    $_SESSION = [];
    
    // Hapus cookie session
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }
    
    // Hapus session di server
    session_destroy();
    
    // Redirect ke login
    header('Location: login.php');
    exit();
}

// Tentukan URL dashboard sesuai role
$dashboard_url = 'dashboard_petani.php'; // default
if ($_SESSION['role'] == 'admin') {
    $dashboard_url = 'dashboard_admin.php';
} elseif ($_SESSION['role'] == 'perangkat_desa') {
    $dashboard_url = 'dashboard_desa.php';
}
?>
<!DOCTYPE html>
<html lang="<?= get_lang() ?>" dir="<?= get_lang() === 'ar' ? 'rtl' : 'ltr' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= t('logout_confirm_title') ?> - <?= t('app_name') ?></title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../public/css/style.css">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: #f8fafc;
            font-family: 'Plus Jakarta Sans', sans-serif;
            padding: 20px;
        }
        .logout-card {
            background: #ffffff;
            padding: 40px 32px;
            border-radius: 20px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.03);
            border: 1px solid #e2e8f0;
            text-align: center;
            max-width: 420px;
            width: 100%;
        }
        .logout-card .icon-wrapper {
            width: 70px;
            height: 70px;
            background: #fee2e2;
            color: #ef4444;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            margin: 0 auto 20px;
        }
    </style>
</head>
<body>
    <div class="logout-card">
        <div class="icon-wrapper">
            <i class="fas fa-sign-out-alt"></i>
        </div>
        <h4 class="fw-bold text-dark mb-2"><?= t('logout_confirm_title') ?></h4>
        <p class="text-muted small mb-4"><?= t('logout_confirm_text') ?></p>
        
        <div class="d-flex align-items-center justify-content-center gap-2 p-2 bg-light rounded-pill border mb-4">
            <i class="fas fa-user-circle text-success"></i>
            <span class="small fw-bold text-dark"><?= htmlspecialchars($_SESSION['nama']) ?></span>
        </div>

        <div class="d-flex gap-3 justify-content-center">
            <a href="<?= $dashboard_url ?>" class="btn btn-light px-4 py-2 rounded-pill fw-semibold border w-50">
                <i class="fas fa-arrow-left me-1"></i> <?= t('logout_no') ?>
            </a>
            <a href="?confirm=yes&csrf_token=<?= generate_csrf_token() ?>" class="btn btn-danger px-4 py-2 rounded-pill fw-semibold w-50" style="background: #ef4444; border-color: #ef4444;">
                <i class="fas fa-check me-1"></i> <?= t('logout') ?>
            </a>
        </div>
    </div>
</body>
</html>