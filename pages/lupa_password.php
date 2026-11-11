<?php
include '../includes/config.php';
include '../includes/header.php';

// Kalo udah login, redirect aja
if (isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5 col-xl-4">

            <!-- BRAND LOGO -->
            <div class="text-center mb-4">
                <a href="../public/index.php" class="d-inline-flex align-items-center gap-2 text-decoration-none">
                    <div class="stat-card-icon stat-icon-amber mx-auto shadow-sm" style="width: 50px; height: 50px; font-size: 1.4rem;">
                        <i class="fas fa-key"></i>
                    </div>
                </a>
                <h4 class="fw-bold text-dark mt-2 mb-1"><?= t('forgot_password_title') ?></h4>
                <p class="text-muted small"><?= t('forgot_password_desc') ?></p>
            </div>

            <!-- FORGOT PASSWORD CARD -->
            <div class="card-modern p-4">
                <div id="msgBox" style="display: none;" class="mb-3"></div>

                <form id="forgotForm">
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label class="form-label-modern"><?= t('email') ?></label>
                        <div class="position-relative">
                            <i class="fas fa-envelope position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                            <input type="email" id="email" name="email" class="form-control-modern ps-5" required placeholder="contoh@gmail.com">
                        </div>
                    </div>

                    <button type="submit" class="btn-primary-green w-100 justify-content-center py-2 fs-6 mt-3 mb-3" id="btnSubmit">
                        <i class="fas fa-paper-plane me-1"></i> <?= t('send_reset_link') ?>
                    </button>
                </form>

                <div class="text-center pt-2 border-top">
                    <a href="login.php" class="small text-success fw-bold text-decoration-none">
                        <i class="fas fa-arrow-left me-1"></i> <?= t('back_to_login') ?>
                    </a>
                </div>
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
document.getElementById('forgotForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const btnSubmit = document.getElementById('btnSubmit');
    const msgBox = document.getElementById('msgBox');
    const email = document.getElementById('email').value;
    const csrf = document.querySelector('input[name="csrf_token"]').value;
    
    btnSubmit.disabled = true;
    btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> <?= t('loading') ?>';
    msgBox.style.display = 'none';
    msgBox.className = 'mb-3';
    
    const formData = new FormData();
    formData.append('email', email);
    formData.append('csrf_token', csrf);
    
    try {
        const response = await fetch('proses_lupa_password.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        
        msgBox.style.display = 'block';
        if (data.success) {
            msgBox.className = 'alert-modern alert-modern-success mb-3';
            msgBox.innerHTML = `<i class="fas fa-check-circle"></i> <div>${data.message} <br><br> <a class="fw-bold text-success text-decoration-underline" href="${data.reset_link}">🔗 Klik di sini untuk Reset Password</a></div>`;
            document.getElementById('email').value = '';
        } else {
            msgBox.className = 'alert-modern alert-modern-danger mb-3';
            msgBox.innerHTML = `<i class="fas fa-exclamation-circle"></i> <div>${data.error}</div>`;
        }
    } catch (err) {
        msgBox.style.display = 'block';
        msgBox.className = 'alert-modern alert-modern-danger mb-3';
        msgBox.innerHTML = `<i class="fas fa-exclamation-triangle"></i> <div><?= t('error') ?></div>`;
    }
    
    btnSubmit.disabled = false;
    btnSubmit.innerHTML = '<i class="fas fa-paper-plane me-1"></i> <?= t('send_reset_link') ?>';
});
</script>

<?php include '../includes/footer.php'; ?>
