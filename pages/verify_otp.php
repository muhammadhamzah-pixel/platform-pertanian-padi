<?php
include '../includes/config.php';

if (!isset($_SESSION['verify_email'])) {
    header('Location: register.php');
    exit();
}

$email = $_SESSION['verify_email'];
$error = '';
$success = '';
$verified = false;
$reset_timer = false;

$query = "SELECT * FROM users WHERE email = '$email'";
$result = mysqli_query($conn, $query);
$user = mysqli_fetch_assoc($result);

if (!$user) {
    unset($_SESSION['verify_email']);
    header('Location: register.php');
    exit();
}

// ===== PROSES VERIFIKASI OTP =====
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['verify_otp'])) {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error = t('err_csrf');
    } else {
        $otp_input = trim($_POST['otp']);
        
        if ($user['otp_attempts'] >= 5) {
            $error = "🔒 Terlalu banyak percobaan OTP salah. Silakan kirim ulang OTP baru.";
        } elseif (empty($otp_input)) {
            $error = '❌ ' . t('err_otp_empty');
        } elseif (strlen($otp_input) != 6) {
            $error = '❌ ' . t('err_otp_6digit');
        } elseif (strtotime($user['otp_expired']) < time()) {
            $error = '❌ ' . t('err_otp_expired');
        } elseif ($otp_input != $user['otp']) {
            $attempts_baru = $user['otp_attempts'] + 1;
            
            if ($attempts_baru >= 5) {
                $update = "UPDATE users SET otp = NULL, otp_expired = NULL, otp_attempts = 0 WHERE email = '$email'";
                mysqli_query($conn, $update);
                $error = "🔒 Terlalu banyak percobaan OTP salah (5x). Silakan kirim ulang OTP baru.";
                catat_log($conn, $user['id'], $user['email'], $user['nama'], 'OTP Terkunci', 'Terlalu banyak percobaan OTP salah');
                $query = "SELECT * FROM users WHERE email = '$email'";
                $user = mysqli_fetch_assoc(mysqli_query($conn, $query));
            } else {
                $update = "UPDATE users SET otp_attempts = $attempts_baru WHERE email = '$email'";
                mysqli_query($conn, $update);
                $sisa = 5 - $attempts_baru;
                $error = '❌ ' . t('err_otp_wrong') . ' ' . t('err_otp_remaining') . ': <strong>' . $sisa . 'x</strong>';
                $query = "SELECT * FROM users WHERE email = '$email'";
                $user = mysqli_fetch_assoc(mysqli_query($conn, $query));
            }
        } else {
            $update = "UPDATE users SET email_verified = 1, status = 'active', otp = NULL, otp_expired = NULL, otp_attempts = 0 WHERE email = '$email'";
            
            if (mysqli_query($conn, $update)) {
                catat_log($conn, $user['id'], $user['email'], $user['nama'], 'Verifikasi Email', 'Email berhasil diverifikasi');
                unset($_SESSION['verify_email']);
                $verified = true;
                $success = '✅ ' . t('verify_success');
            } else {
                $error = '❌ ' . t('failed') . ': ' . mysqli_error($conn);
            }
        }
    }
}

// ===== PROSES KIRIM ULANG OTP =====
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['resend_otp']) && !$verified) {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error = t('err_csrf');
    } else {
        $last_resend = $_SESSION['last_resend'] ?? 0;
        $cooldown = 60;
        
        if (time() - $last_resend < $cooldown) {
            $sisa = $cooldown - (time() - $last_resend);
            $error = '⏳ ' . t('err_otp_wait') . ' <strong>' . $sisa . '</strong> ' . t('err_otp_seconds');
        } else {
            $otp_baru = generateOTP();
            $otp_expired_baru = date('Y-m-d H:i:s', strtotime('+5 minutes'));
            
            $update = "UPDATE users SET otp = '$otp_baru', otp_expired = '$otp_expired_baru', otp_attempts = 0 WHERE email = '$email'";
            
            if (mysqli_query($conn, $update)) {
                if (kirimOTP($email, $otp_baru, $user['nama'])) {
                    $_SESSION['last_resend'] = time();
                    $success = '✅ ' . t('verify_resend_success');
                    $reset_timer = true;
                    regenerate_csrf_token();
                } else {
                    $error = '❌ ' . t('failed');
                }
            }
            
            $query = "SELECT * FROM users WHERE email = '$email'";
            $result = mysqli_query($conn, $query);
            $user = mysqli_fetch_assoc($result);
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
                        <i class="fas fa-envelope-open-text"></i>
                    </div>
                </a>
                <h4 class="fw-bold text-dark mt-2 mb-1"><?= t('verify_title') ?></h4>
                <p class="text-muted small">Verifikasi kode keamanan yang dikirimkan ke email Anda</p>
            </div>

            <!-- VERIFY CARD -->
            <div class="card-modern p-4 text-center">
                <?php if ($verified): ?>
                    <div class="alert-modern alert-modern-success text-start mb-3">
                        <i class="fas fa-check-circle"></i>
                        <div>
                            <strong class="d-block mb-1"><?= t('verify_success') ?></strong>
                            <span class="small"><?= t('verify_login_desc') ?></span>
                        </div>
                    </div>

                    <a href="login.php" class="btn-primary-green w-100 justify-content-center py-2 fs-6 mt-3 text-decoration-none">
                        <i class="fas fa-sign-in-alt me-1"></i> <?= t('verify_login_now') ?>
                    </a>

                <?php else: ?>
                    <div class="d-inline-flex align-items-center gap-2 px-3 py-2 bg-light rounded-pill border text-muted small mb-3">
                        <i class="fas fa-at text-success"></i>
                        <span class="fw-bold text-dark"><?= htmlspecialchars($email) ?></span>
                    </div>

                    <div class="alert-modern alert-modern-warning text-start mb-3 p-2 small">
                        <i class="fas fa-info-circle"></i>
                        <div>
                            <strong>Belum terima OTP?</strong> Coba periksa folder <strong>Spam</strong> pada email Anda.
                        </div>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert-modern alert-modern-danger text-start mb-3">
                            <i class="fas fa-exclamation-circle"></i>
                            <div><?= $error ?></div>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert-modern alert-modern-success text-start mb-3">
                            <i class="fas fa-check-circle"></i>
                            <div><?= $success ?></div>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <?= csrf_field() ?>
                        <div class="mb-3">
                            <label class="form-label-modern small text-muted text-uppercase fw-bold letter-spacing-1">Masukkan 6 Digit OTP</label>
                            <input type="text" 
                                   name="otp" 
                                   class="form-control text-center fw-bold text-success font-monospace" 
                                   style="font-size: 2rem; letter-spacing: 12px; border-radius: 12px; border: 2px solid #e2e8f0; background: #f8fafc;"
                                   maxlength="6" 
                                   pattern="[0-9]{6}" 
                                   placeholder="000000"
                                   required 
                                   autofocus
                                   inputmode="numeric"
                                   autocomplete="one-time-code">
                        </div>

                        <button type="submit" name="verify_otp" value="1" class="btn-primary-green w-100 justify-content-center py-2 fs-6 mb-3">
                            <i class="fas fa-check-circle me-1"></i> <?= t('verify_button') ?>
                        </button>
                    </form>

                    <div class="d-flex align-items-center justify-content-between pt-2 border-top">
                        <div class="small text-muted fw-semibold" id="timerText">
                            <i class="far fa-clock me-1"></i> <span id="countdown">05:00</span>
                        </div>

                        <form method="POST" action="" style="margin: 0; display: inline;">
                            <?= csrf_field() ?>
                            <button type="submit" name="resend_otp" value="1" class="btn btn-link btn-sm text-success text-decoration-none fw-bold p-0" id="btnResend">
                                <i class="fas fa-redo-alt me-1"></i> <?= t('verify_resend') ?>
                            </button>
                        </form>
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

<?php if (!$verified): ?>
<script>
    const otpExpired = <?= strtotime($user['otp_expired']) ?> * 1000;
    const countdownEl = document.getElementById('countdown');
    const timerText = document.getElementById('timerText');
    const expiredMsg = '<?= t('verify_timer_expired') ?>';
    
    function updateTimer() {
        const now = Date.now();
        const diff = otpExpired - now;
        
        if (diff <= 0) {
            countdownEl.textContent = "00:00";
            timerText.className = "timer danger";
            timerText.innerHTML = "⏰ " + expiredMsg;
            return;
        }
        
        const minutes = Math.floor(diff / 60000);
        const seconds = Math.floor((diff % 60000) / 1000);
        
        countdownEl.textContent = 
            String(minutes).padStart(2, '0') + ':' + 
            String(seconds).padStart(2, '0');
        
        if (diff < 60000) {
            timerText.className = "timer danger";
        } else if (diff < 120000) {
            timerText.className = "timer warning";
        }
    }
    
    updateTimer();
    setInterval(updateTimer, 1000);
    
    const otpInput = document.querySelector('.otp-input');
    if (otpInput) {
        otpInput.focus();
        otpInput.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    }
</script>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>