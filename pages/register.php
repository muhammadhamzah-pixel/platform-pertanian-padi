<?php
include '../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error = t('err_csrf');
    } else {
        $email = trim($_POST['email']);
        $nama = sanitize_input($_POST['nama']);
        $no_hp = sanitize_input($_POST['no_hp']);
        $password = $_POST['password'];
        $alamat = sanitize_input($_POST['alamat']);
        
        $validasi = cek_validasi([
            validasi_email($email),
            validasi_nama($nama),
            validasi_no_hp($no_hp),
            validasi_password($password)
        ]);
        
        if ($validasi !== true) {
            $error = "❌ " . $validasi;
        } else {
            $email = mysqli_real_escape_string($conn, $email);
            $nama = mysqli_real_escape_string($conn, $nama);
            $no_hp = mysqli_real_escape_string($conn, $no_hp);
            $alamat = mysqli_real_escape_string($conn, $alamat);
            
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            $check = mysqli_query($conn, "SELECT * FROM users WHERE email='$email'");
            if (mysqli_num_rows($check) > 0) {
                $error = "❌ Email sudah terdaftar!";
            } else {
                $otp = generateOTP();
                $otp_expired = date('Y-m-d H:i:s', strtotime('+5 minutes'));
                
                $query = "INSERT INTO users (email, nama, no_hp, alamat, password_hash, otp, otp_expired, email_verified, role, status, created_by) 
                          VALUES ('$email', '$nama', '$no_hp', '$alamat', '$password_hash', '$otp', '$otp_expired', 0, 'petani', 'pending', NULL)";
                
                if (mysqli_query($conn, $query)) {
                    if (kirimOTP($email, $otp, $nama)) {
                        $_SESSION['verify_email'] = $email;
                        header('Location: ' . $base_url . 'verify_otp');
                        exit();
                    } else {
                        mysqli_query($conn, "DELETE FROM users WHERE email='$email' AND email_verified=0");
                        $error = "❌ " . t('failed') . " Kirim OTP.";
                    }
                } else {
                    $error = "❌ " . t('failed') . ": " . mysqli_error($conn);
                }
            }
        }
    }
}

include '../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-6 col-xl-5">

            <!-- BRAND LOGO -->
            <div class="text-center mb-4">
                <a href="<?= $base_url ?>" class="d-inline-flex align-items-center gap-2 text-decoration-none">
                    <div class="stat-card-icon stat-icon-emerald mx-auto shadow-sm" style="width: 50px; height: 50px; font-size: 1.4rem;">
                        <i class="fas fa-seedling"></i>
                    </div>
                </a>
                <h4 class="fw-bold text-dark mt-2 mb-1"><?= t('register_title') ?></h4>
                <p class="text-muted small">Daftarkan akun petani Anda untuk bergabung dalam ekosistem digital</p>
            </div>

            <!-- REGISTER CARD -->
            <div class="card-modern p-4">
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
                            <input type="email" name="email" class="form-control-modern ps-5" required placeholder="<?= t('register_placeholder_email') ?>" autocomplete="off" value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label-modern"><?= t('name') ?></label>
                        <div class="position-relative">
                            <i class="fas fa-user position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                            <input type="text" name="nama" class="form-control-modern ps-5" required placeholder="<?= t('register_placeholder_name') ?>" value="<?= isset($_POST['nama']) ? htmlspecialchars($_POST['nama']) : '' ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label-modern"><?= t('phone') ?></label>
                        <div class="position-relative">
                            <i class="fas fa-phone position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                            <input type="text" name="no_hp" class="form-control-modern ps-5" placeholder="<?= t('register_placeholder_phone') ?>" value="<?= isset($_POST['no_hp']) ? htmlspecialchars($_POST['no_hp']) : '' ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label-modern"><?= t('address') ?></label>
                        <div class="position-relative">
                            <i class="fas fa-map-marker-alt position-absolute top-0 start-0 mt-3 ms-3 text-muted"></i>
                            <textarea name="alamat" class="form-control-modern ps-5" rows="2" placeholder="<?= t('register_placeholder_address') ?>"><?= isset($_POST['alamat']) ? htmlspecialchars($_POST['alamat']) : '' ?></textarea>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label-modern"><?= t('password') ?></label>
                        <div class="position-relative">
                            <i class="fas fa-lock position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                            <input type="password" name="password" id="password" class="form-control-modern ps-5 pe-5" required placeholder="<?= t('register_placeholder_password') ?>">
                            <span class="position-absolute top-50 end-0 translate-middle-y me-3 text-muted cursor-pointer" onclick="togglePassword()" style="cursor: pointer;">
                                <i class="far fa-eye" id="eyeIcon"></i>
                            </span>
                        </div>
                    </div>

                    <button type="submit" class="btn-primary-green w-100 justify-content-center py-2 fs-6 mt-3 mb-3">
                        <i class="fas fa-user-plus"></i> <?= t('register_button') ?>
                    </button>
                </form>

                <div class="text-center pt-2 border-top">
                    <p class="small text-muted mb-0">
                        <?= t('register_have_account') ?> 
                        <a href="<?= $base_url ?>login" class="text-success fw-bold text-decoration-none">
                            <?= t('register_login_here') ?>
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