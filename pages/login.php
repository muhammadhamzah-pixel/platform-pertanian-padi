<?php
include '../includes/config.php';

// Kalo udah login, redirect ke dashboard masing-masing
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] == 'admin') {
        header('Location: ' . $base_url . 'dashboard_admin');
    } elseif ($_SESSION['role'] == 'perangkat_desa') {
        header('Location: ' . $base_url . 'dashboard_desa');
    } else {
        header('Location: ' . $base_url . 'dashboard_petani');
    }
    exit();
}

// Proses login
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error = t('err_csrf');
    } else {
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        
        if (empty($email) || empty($password)) {
            $error = '❌ ' . t('err_email_empty');
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = '❌ ' . t('err_login_failed');
        } else {
            $email = mysqli_real_escape_string($conn, $email);
            
            $cek_role = mysqli_query($conn, "SELECT role FROM users WHERE email = '$email'");
            $role_data = mysqli_fetch_assoc($cek_role);
            $role_user = $role_data['role'] ?? null;
            $is_privileged = in_array($role_user, ['admin', 'perangkat_desa']);
            
            if (!$is_privileged && cek_blokir_login($conn, $email)) {
                $sisa = sisa_waktu_blokir($conn, $email);
                $error = "🔒 " . t('err_login_blocked') . " <strong>$sisa menit</strong>.";
                catat_log($conn, null, $email, null, 'Login Diblokir', "Sisa: $sisa menit");
            } else {
                $query = "SELECT * FROM users WHERE email='$email'";
                $result = mysqli_query($conn, $query);
                
                if (mysqli_num_rows($result) == 1) {
                    $user = mysqli_fetch_assoc($result);
                    
                    if (password_verify($password, $user['password_hash'])) {
                        if ($user['status'] == 'blocked') {
                            $error = t('err_login_blocked');
                            catat_log($conn, $user['id'], $user['email'], $user['nama'], 'Login Gagal', 'Akun diblokir');
                            if (!$is_privileged) catat_login_attempt($conn, $email, 'gagal');
                        } elseif ($user['email_verified'] != 1) {
                            $error = '❌ ' . t('err_email_verified');
                            catat_log($conn, $user['id'], $user['email'], $user['nama'], 'Login Gagal', 'Gmail belum diverifikasi');
                            if (!$is_privileged) catat_login_attempt($conn, $email, 'gagal');
                        } elseif ($user['status'] == 'pending') {
                            $error = t('err_email_verified');
                            catat_log($conn, $user['id'], $user['email'], $user['nama'], 'Login Gagal', 'Akun belum diverifikasi');
                            if (!$is_privileged) catat_login_attempt($conn, $email, 'gagal');
                        } else {
                            session_regenerate_id(true);
                            
                            $_SESSION['user_id'] = $user['id'];
                            $_SESSION['email'] = $user['email'];
                            $_SESSION['nama'] = $user['nama'];
                            $_SESSION['role'] = $user['role'];
                            $_SESSION['foto'] = $user['foto'] ?? 'defaults.png';
                            $_SESSION['no_hp'] = $user['no_hp'] ?? '';
                            $_SESSION['telegram_chat_id'] = $user['telegram_chat_id'] ?? '';
                            
                            $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
                            $ip_esc = mysqli_real_escape_string($conn, $ip_address);
                            $update_audit = "UPDATE users SET last_login_ip = '$ip_esc', last_login_at = NOW() WHERE id = '{$user['id']}'";
                            mysqli_query($conn, $update_audit);
                            
                            catat_log($conn, $user['id'], $user['email'], $user['nama'], 'Login Berhasil', 'Role: ' . $user['role']);
                            if (!$is_privileged) {
                                catat_login_attempt($conn, $email, 'berhasil');
                                reset_login_attempts($conn, $email);
                            }
                            
                            regenerate_csrf_token();
                            
                            if ($user['role'] == 'admin') {
                                header('Location: ' . $base_url . 'dashboard_admin');
                            } elseif ($user['role'] == 'perangkat_desa') {
                                header('Location: ' . $base_url . 'dashboard_desa');
                            } else {
                                header('Location: ' . $base_url . 'dashboard_petani');
                            }
                            exit();
                        }
                    } else {
                        if (!$is_privileged) catat_login_attempt($conn, $email, 'gagal');
                        catat_log($conn, $user['id'], $user['email'], $user['nama'], 'Login Gagal', 'Password salah');
                        
                        if (!$is_privileged && cek_blokir_login($conn, $email)) {
                            $sisa = sisa_waktu_blokir($conn, $email);
                            $error = "🔒 " . t('err_login_blocked') . " <strong>$sisa menit</strong>.";
                        } else {
                            if ($is_privileged) {
                                $error = '❌ ' . t('err_login_failed');
                            } else {
                                $sisa_percobaan = 5 - mysqli_fetch_assoc(mysqli_query($conn, 
                                    "SELECT COUNT(*) AS total FROM login_attempts 
                                     WHERE email = '$email' AND status = 'gagal' 
                                     AND created_at >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)"))['total'];
                                $error = '❌ ' . t('err_login_failed') . ' Sisa: <strong>' . max(0, $sisa_percobaan) . 'x</strong>';
                            }
                        }
                    }
                } else {
                    if (!$is_privileged) catat_login_attempt($conn, $email, 'gagal');
                    catat_log($conn, null, $email, null, 'Login Gagal', 'Email tidak terdaftar');
                    $error = '❌ ' . t('err_login_failed');
                }
            }
        }
    }
}

include '../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5 col-xl-4">

            <!-- BRAND LOGO -->
            <div class="text-center mb-4">
                <a href="../public/index.php" class="d-inline-flex align-items-center gap-2 text-decoration-none">
                    <div class="stat-card-icon stat-icon-emerald mx-auto shadow-sm" style="width: 50px; height: 50px; font-size: 1.4rem;">
                        <i class="fas fa-seedling"></i>
                    </div>
                </a>
                <h4 class="fw-bold text-dark mt-2 mb-1"><?= t('login_title') ?></h4>
                <p class="text-muted small">Masuk untuk mengakses portal pertanian Anda</p>
            </div>

            <!-- LOGIN CARD -->
            <div class="card-modern p-4">
                <?php if (isset($_GET['verified']) && $_GET['verified'] == '1'): ?>
                    <div class="alert-modern alert-modern-success mb-3">
                        <i class="fas fa-check-circle"></i>
                        <div><?= t('login_verified') ?></div>
                    </div>
                <?php endif; ?>

                <?php if (isset($error)): ?>
                    <div class="alert-modern alert-modern-danger mb-3">
                        <i class="fas fa-exclamation-circle"></i>
                        <div><?= $error ?></div>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label class="form-label-modern"><?= t('email') ?></label>
                        <div class="position-relative">
                            <i class="fas fa-envelope position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                            <input type="email" name="email" class="form-control-modern ps-5" required placeholder="<?= t('login_placeholder_email') ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <label class="form-label-modern mb-0"><?= t('password') ?></label>
                            <a href="<?= $base_url ?>lupa_password" class="small text-success text-decoration-none fw-semibold">
                                <?= t('forgot_password') ?>
                            </a>
                        </div>
                        <div class="position-relative">
                            <i class="fas fa-lock position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                            <input type="password" name="password" id="password" class="form-control-modern ps-5 pe-5" required placeholder="<?= t('login_placeholder_password') ?>">
                            <span class="position-absolute top-50 end-0 translate-middle-y me-3 text-muted cursor-pointer" onclick="togglePassword()">
                                <i class="far fa-eye" id="eyeIcon"></i>
                            </span>
                        </div>
                    </div>

                    <button type="submit" class="btn-primary-green w-100 justify-content-center py-2 fs-6 mt-3 mb-3">
                        <i class="fas fa-sign-in-alt"></i> <?= t('login_button') ?>
                    </button>
                </form>

                <div class="text-center pt-2 border-top">
                    <p class="small text-muted mb-0">
                        <?= t('login_no_account') ?> 
                        <a href="<?= $base_url ?>register" class="text-success fw-bold text-decoration-none">
                            <?= t('login_register_here') ?>
                        </a>
                    </p>
                </div>
            </div>

            <!-- BACK TO HOME -->
            <div class="text-center mt-3">
                <a href="<?= $base_url ?>" class="small text-muted text-decoration-none">
                    <i class="fas fa-arrow-left me-1"></i> Kembali ke Beranda
                </a>
            </div>

        </div>
    </div>
</div>

<script>
function togglePassword() {
    const input = document.getElementById('password');
    const eye = document.getElementById('eyeIcon');
    if (input.type === 'password') {
        input.type = 'text';
        eye.classList.remove('fa-eye');
        eye.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        eye.classList.remove('fa-eye-slash');
        eye.classList.add('fa-eye');
    }
}
</script>

<?php include '../includes/footer.php'; ?>