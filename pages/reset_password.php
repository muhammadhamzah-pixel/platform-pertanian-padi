<?php
include '../includes/config.php';
include '../includes/header.php';

// Validasi token
if (!isset($_GET['token']) || empty(trim($_GET['token']))) {
    $error_msg = t('invalid_token');
} else {
    $token = mysqli_real_escape_string($conn, $_GET['token']);
    
    // Cek token di database, dan batas waktu 1 jam (60 menit)
    $query = "SELECT * FROM password_resets WHERE token = '$token' AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)";
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) === 0) {
        $error_msg = t('invalid_token');
    } else {
        $reset_data = mysqli_fetch_assoc($result);
        $email_to_reset = $reset_data['email'];
    }
}
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5 col-xl-4">

            <!-- BRAND LOGO -->
            <div class="text-center mb-4">
                <a href="../public/index.php" class="d-inline-flex align-items-center gap-2 text-decoration-none">
                    <div class="stat-card-icon stat-icon-emerald mx-auto shadow-sm" style="width: 50px; height: 50px; font-size: 1.4rem;">
                        <i class="fas fa-lock-open"></i>
                    </div>
                </a>
                <h4 class="fw-bold text-dark mt-2 mb-1"><?= t('forgot_password_title') ?></h4>
                <p class="text-muted small">Buat kata sandi baru untuk akun Anda</p>
            </div>

            <!-- RESET PASSWORD CARD -->
            <div class="card-modern p-4">
                <?php if (isset($error_msg)): ?>
                    <div class="alert-modern alert-modern-danger mb-3">
                        <i class="fas fa-exclamation-circle"></i>
                        <div><?= $error_msg ?></div>
                    </div>
                    <div class="text-center mt-3">
                        <a href="login.php" class="btn-primary-green d-inline-flex align-items-center gap-2 text-decoration-none px-4 py-2">
                            <i class="fas fa-arrow-left"></i> <?= t('back_to_login') ?>
                        </a>
                    </div>
                <?php else: ?>
                    <div id="msgBox" style="display: none;" class="mb-3"></div>

                    <form id="resetForm">
                        <?= csrf_field() ?>
                        <input type="hidden" name="token" value="<?= htmlspecialchars($_GET['token']) ?>">

                        <div class="mb-3">
                            <label class="form-label-modern"><?= t('new_password') ?></label>
                            <div class="position-relative">
                                <i class="fas fa-lock position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                                <input type="password" id="password_new" name="password_new" class="form-control-modern ps-5 pe-5" required minlength="6" placeholder="Minimal 6 karakter">
                                <span class="position-absolute top-50 end-0 translate-middle-y me-3 text-muted cursor-pointer" onclick="togglePass('password_new', 'eyeNew')" style="cursor: pointer;">
                                    <i class="far fa-eye" id="eyeNew"></i>
                                </span>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label-modern"><?= t('confirm_new_password') ?></label>
                            <div class="position-relative">
                                <i class="fas fa-shield-alt position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                                <input type="password" id="password_confirm" name="password_confirm" class="form-control-modern ps-5 pe-5" required minlength="6" placeholder="Ulangi kata sandi">
                                <span class="position-absolute top-50 end-0 translate-middle-y me-3 text-muted cursor-pointer" onclick="togglePass('password_confirm', 'eyeConfirm')" style="cursor: pointer;">
                                    <i class="far fa-eye" id="eyeConfirm"></i>
                                </span>
                            </div>
                        </div>

                        <button type="submit" class="btn-primary-green w-100 justify-content-center py-2 fs-6 mt-3 mb-3" id="btnSubmit">
                            <i class="fas fa-check-circle me-1"></i> <?= t('reset_password_btn') ?>
                        </button>
                    </form>

                    <div class="text-center pt-2 border-top">
                        <a href="login.php" class="small text-muted text-decoration-none">
                            <i class="fas fa-arrow-left me-1"></i> Batal dan kembali ke Login
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- BACK TO HOME -->
            <div class="text-center mt-3">
                <a href="../public/index.php" class="small text-muted text-decoration-none">
                    <i class="fas fa-home me-1"></i> Kembali ke Beranda
                </a>
            </div>

        </div>
    </div>
</div>

<script>
function togglePass(id, eyeId) {
    const el = document.getElementById(id);
    const eye = document.getElementById(eyeId);
    if (el.type === 'password') {
        el.type = 'text';
        eye.classList.remove('fa-eye');
        eye.classList.add('fa-eye-slash');
    } else {
        el.type = 'password';
        eye.classList.remove('fa-eye-slash');
        eye.classList.add('fa-eye');
    }
}

const form = document.getElementById('resetForm');
if (form) {
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const btnSubmit = document.getElementById('btnSubmit');
        const msgBox = document.getElementById('msgBox');
        
        btnSubmit.disabled = true;
        btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> <?= t('loading') ?>';
        msgBox.style.display = 'none';
        msgBox.className = 'mb-3';
        
        const formData = new FormData(this);
        
        try {
            const response = await fetch('proses_reset_password.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            
            msgBox.style.display = 'block';
            if (data.success) {
                msgBox.className = 'alert-modern alert-modern-success mb-3';
                msgBox.innerHTML = `<i class="fas fa-check-circle"></i> <div>${data.message} <br><br> <a href="login.php" class="btn-primary-green d-inline-flex align-items-center gap-1 text-decoration-none px-3 py-1 mt-1 fs-6">Masuk Sekarang</a></div>`;
                form.style.display = 'none'; // Sembunyikan form
            } else {
                msgBox.className = 'alert-modern alert-modern-danger mb-3';
                msgBox.innerHTML = `<i class="fas fa-exclamation-circle"></i> <div>${data.error}</div>`;
                btnSubmit.disabled = false;
                btnSubmit.innerHTML = '<i class="fas fa-check-circle me-1"></i> <?= t('reset_password_btn') ?>';
            }
        } catch (err) {
            msgBox.style.display = 'block';
            msgBox.className = 'alert-modern alert-modern-danger mb-3';
            msgBox.innerHTML = `<i class="fas fa-exclamation-triangle"></i> <div><?= t('error') ?></div>`;
            btnSubmit.disabled = false;
            btnSubmit.innerHTML = '<i class="fas fa-check-circle me-1"></i> <?= t('reset_password_btn') ?>';
        }
    });
}
</script>

<?php include '../includes/footer.php'; ?>
