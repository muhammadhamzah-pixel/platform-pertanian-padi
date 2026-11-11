<?php
include '../includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error = t('err_csrf');
    } else {
        $password_lama = $_POST['password_lama'];
        $password_baru = $_POST['password_baru'];
        $password_konfirmasi = $_POST['password_konfirmasi'];
        
        $query = "SELECT password_hash FROM users WHERE id = '$user_id'";
        $result = mysqli_query($conn, $query);
        $user = mysqli_fetch_assoc($result);
        
        if (!password_verify($password_lama, $user['password_hash'])) {
            $error = '❌ ' . t('err_password_old_wrong');
        } elseif (strlen($password_baru) < 6) {
            $error = '❌ ' . t('err_password_min_6');
        } elseif ($password_baru != $password_konfirmasi) {
            $error = '❌ ' . t('err_password_mismatch');
        } else {
            $password_hash = password_hash($password_baru, PASSWORD_DEFAULT);
            $password_hash_esc = mysqli_real_escape_string($conn, $password_hash);
            
            $query = "UPDATE users SET password_hash = '$password_hash_esc' WHERE id = '$user_id'";
            if (mysqli_query($conn, $query)) {
                $message = '✅ ' . t('password_change_success');
                catat_log($conn, $user_id, $_SESSION['email'], $_SESSION['nama'], 'Ganti Password', 'Password berhasil diubah');
                regenerate_csrf_token();
            } else {
                $error = '❌ ' . t('failed') . ': ' . mysqli_error($conn);
            }
        }
    }
}

$role = $_SESSION['role'];
include '../includes/navbar.php';
?>

<!-- SIGNATURE DASH HERO HEADER -->
<div class="dash-hero-header">
    <div class="dash-hero-inner">
        <div class="dash-hero-title">
            <h1><i class="fas fa-key"></i> <?= t('profile_change_password') ?></h1>
            <p>Perbarui kata sandi akun Anda secara berkala untuk menjaga keamanan data.</p>
        </div>
        <div class="dash-hero-actions">
            <a href="dashboard_<?= $role ?>.php" class="btn-outline-white">
                <i class="fas fa-arrow-left"></i> Dashboard
            </a>
        </div>
    </div>
</div>

<!-- MAIN PAGE CONTAINER -->
<div class="main-page-container">
    <div class="row justify-content-center">
        <div class="col-lg-6">

            <?php if ($message): ?>
                <div class="alert-modern alert-modern-success">
                    <i class="fas fa-check-circle fs-5"></i>
                    <div><?= $message ?></div>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert-modern alert-modern-danger">
                    <i class="fas fa-exclamation-circle fs-5"></i>
                    <div><?= $error ?></div>
                </div>
            <?php endif; ?>

            <!-- PASSWORD CARD -->
            <div class="card-modern">
                <div class="card-modern-header">
                    <h2 class="card-modern-title">
                        <i class="fas fa-lock text-success"></i> Keamanan Kata Sandi
                    </h2>
                    <span class="badge bg-light text-muted border">Minimal 6 Karakter</span>
                </div>
                <div class="card-modern-body">
                    <form method="POST" action="">
                        <?= csrf_field() ?>

                        <div class="mb-3">
                            <label class="form-label-modern"><?= t('profile_password_old') ?> *</label>
                            <div class="position-relative">
                                <input type="password" name="password_lama" id="password_lama" class="form-control-modern pe-5" required>
                                <span class="position-absolute top-50 end-0 translate-middle-y me-3 text-muted cursor-pointer" onclick="togglePassword('password_lama')">
                                    <i class="far fa-eye" id="eye_password_lama"></i>
                                </span>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label-modern"><?= t('profile_password_new') ?> *</label>
                            <div class="position-relative">
                                <input type="password" name="password_baru" id="password_baru" class="form-control-modern pe-5" required minlength="6">
                                <span class="position-absolute top-50 end-0 translate-middle-y me-3 text-muted cursor-pointer" onclick="togglePassword('password_baru')">
                                    <i class="far fa-eye" id="eye_password_baru"></i>
                                </span>
                            </div>
                            <small class="text-muted"><i class="fas fa-info-circle me-1"></i> <?= t('password_min_hint') ?></small>
                        </div>

                        <div class="mb-4">
                            <label class="form-label-modern"><?= t('profile_password_confirm') ?> *</label>
                            <div class="position-relative">
                                <input type="password" name="password_konfirmasi" id="password_konfirmasi" class="form-control-modern pe-5" required>
                                <span class="position-absolute top-50 end-0 translate-middle-y me-3 text-muted cursor-pointer" onclick="togglePassword('password_konfirmasi')">
                                    <i class="far fa-eye" id="eye_password_konfirmasi"></i>
                                </span>
                            </div>
                        </div>

                        <div class="d-flex align-items-center justify-content-between pt-2 border-top">
                            <a href="dashboard_<?= $role ?>.php" class="btn-secondary-modern">
                                <i class="fas fa-arrow-left"></i> Batal
                            </a>
                            <button type="submit" class="btn-primary-green">
                                <i class="fas fa-save"></i> <?= t('profile_password_save') ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const eye = document.getElementById('eye_' + inputId);
    
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