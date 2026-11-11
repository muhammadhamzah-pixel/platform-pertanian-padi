<?php
include '../includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

$query = "SELECT telegram_chat_id, no_hp FROM users WHERE id = '$user_id'";
$result = mysqli_query($conn, $query);
$user = mysqli_fetch_assoc($result);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error = t('err_csrf');
    } else {
        $telegram_chat_id = sanitize_input($_POST['telegram_chat_id'] ?? '');
        $no_hp = sanitize_input($_POST['no_hp'] ?? '');

        // Validasi format nomor HP jika diisi
        $val_hp = validasi_no_hp($no_hp);
        if ($val_hp !== true) {
            $error = $val_hp;
        } else {
            $telegram_chat_id_esc = mysqli_real_escape_string($conn, $telegram_chat_id);
            $no_hp_esc = mysqli_real_escape_string($conn, $no_hp);

            $query_update = "UPDATE users SET telegram_chat_id = '$telegram_chat_id_esc', no_hp = '$no_hp_esc' WHERE id = '$user_id'";
            
            if (mysqli_query($conn, $query_update)) {
                $message = '✅ Pengaturan Notifikasi WhatsApp & Telegram berhasil disimpan!';
                $_SESSION['telegram_chat_id'] = $telegram_chat_id;
                $_SESSION['no_hp'] = $no_hp;
                catat_log($conn, $user_id, $_SESSION['email'], $_SESSION['nama'], 'Setting Notifikasi', 'Update Kontak Notifikasi');
                regenerate_csrf_token();
                $user['telegram_chat_id'] = $telegram_chat_id;
                $user['no_hp'] = $no_hp;
            } else {
                $error = '❌ Gagal menyimpan: ' . mysqli_error($conn);
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
            <h1><i class="fas fa-sliders-h"></i> Pengaturan Notifikasi</h1>
            <p>Integrasikan akun Anda dengan WhatsApp (Fonnte) & Bot Telegram untuk pemberitahuan panen dan jadwal pemupukan instan.</p>
        </div>
        <div class="dash-hero-actions">
            <a href="test_wa.php" class="btn-outline-white me-2">
                <i class="fab fa-whatsapp"></i> Uji Coba Kirim WA
            </a>
            <a href="dashboard_<?= $role ?>.php" class="btn-outline-white">
                <i class="fas fa-arrow-left"></i> Dashboard
            </a>
        </div>
    </div>
</div>

<!-- MAIN PAGE CONTAINER -->
<div class="main-page-container">
    <div class="row justify-content-center">
        <div class="col-lg-8">

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

            <form method="POST" action="">
                <?= csrf_field() ?>

                <!-- WHATSAPP CARD -->
                <div class="card-modern mb-4">
                    <div class="card-modern-header">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fab fa-whatsapp text-success fs-3"></i>
                            <h2 class="card-modern-title mb-0">Notifikasi WhatsApp (Fonnte API)</h2>
                        </div>
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                            <i class="fas fa-check-circle me-1"></i> Langsung ke HP Petani
                        </span>
                    </div>
                    <div class="card-modern-body">
                        <div class="p-3 bg-light rounded-3 border mb-4">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                                <h6 class="fw-bold text-dark mb-0">
                                    <i class="fab fa-whatsapp text-success me-1"></i> Cara Kerja Notifikasi WhatsApp:
                                </h6>
                                <a href="test_wa.php" class="btn btn-sm btn-success rounded-pill px-3" style="font-size: 12px; font-weight: 600;">
                                    <i class="fas fa-paper-plane me-1"></i> Tes Kirim Pesan WA
                                </a>
                            </div>
                            <p class="small text-secondary mb-0" style="line-height: 1.6;">
                                Masukkan nomor WhatsApp aktif Anda dengan awalan <strong>08</strong> (contoh: <code>081234567890</code>). Setiap kali lahan memasuki jadwal penting (pemupukan 1 & 2, cek air, H-7 panen, hingga hari panen), sistem akan otomatis mengirimkan pesan WhatsApp ke nomor ini.
                            </p>
                        </div>

                        <div class="mb-3">
                            <label class="form-label-modern">Nomor WhatsApp Aktif</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0">
                                    <i class="fab fa-whatsapp text-success"></i>
                                </span>
                                <input type="text" name="no_hp" class="form-control-modern" style="border-top-left-radius: 0; border-bottom-left-radius: 0;" value="<?= htmlspecialchars($user['no_hp'] ?? '') ?>" placeholder="Contoh: 081234567890">
                            </div>
                            <small class="text-muted mt-1 d-block">
                                <i class="fas fa-info-circle text-primary me-1"></i> Format: 10-15 digit angka, diawali <code>08</code>. Pastikan nomor sudah terdaftar di WhatsApp.
                            </small>
                        </div>
                    </div>
                </div>

                <!-- TELEGRAM CARD -->
                <div class="card-modern mb-4">
                    <div class="card-modern-header">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fab fa-telegram text-primary fs-3"></i>
                            <h2 class="card-modern-title mb-0">Integrasi Bot Telegram</h2>
                        </div>
                        <span class="badge bg-light text-muted border">Gratis & Real-Time</span>
                    </div>
                    <div class="card-modern-body">
                        <div class="p-3 bg-light rounded-3 border mb-4">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3 pb-2 border-bottom">
                                <h6 class="fw-bold text-dark mb-0">
                                    <i class="fas fa-question-circle text-primary me-1"></i> Cara Menghubungkan Bot Telegram:
                                </h6>
                                <a href="https://t.me/PlaformPadiDesa_bot" target="_blank" class="btn btn-sm btn-primary rounded-pill px-3" style="font-size: 12px; font-weight: 600;">
                                    <i class="fab fa-telegram-plane me-1"></i> Buka @PlaformPadiDesa_bot
                                </a>
                            </div>
                            <ol class="small text-secondary ps-3 mb-0" style="line-height: 1.6;">
                                <li class="mb-2">
                                    <strong>Mulai Bot Resmi Web:</strong> Buka Telegram lalu cari bot <strong>@PlaformPadiDesa_bot</strong> (atau klik tombol biru di atas), kemudian ketik atau klik <code>/start</code>. <em class="text-danger d-block">*Wajib dilakukan terlebih dahulu agar bot memiliki izin mengirim notifikasi ke akun Anda.</em>
                                </li>
                                <li class="mb-2">
                                    <strong>Dapatkan Nomor Chat ID Anda:</strong> Cari bot pembantu <strong>@userinfobot</strong> atau <strong>@getmyid_bot</strong> di aplikasi Telegram.
                                </li>
                                <li class="mb-2">
                                    Ketik <code>/start</code> pada bot tersebut, lalu salin angka <strong>Id / Chat ID</strong> Anda (contoh: <code>123456789</code>).
                                </li>
                                <li>
                                    Tempelkan angka Chat ID tersebut pada formulir di bawah ini.
                                </li>
                            </ol>
                        </div>

                        <div class="mb-3">
                            <label class="form-label-modern">Telegram Chat ID Anda</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0">
                                    <i class="fab fa-telegram text-primary"></i>
                                </span>
                                <input type="text" name="telegram_chat_id" class="form-control-modern" style="border-top-left-radius: 0; border-bottom-left-radius: 0;" value="<?= htmlspecialchars($user['telegram_chat_id'] ?? '') ?>" placeholder="Contoh: 123456789">
                            </div>
                            <small class="text-muted mt-1 d-block">
                                <i class="fas fa-shield-alt text-success me-1"></i> Notifikasi otomatis akan dikirimkan oleh <strong>@PlaformPadiDesa_bot</strong> langsung ke akun Telegram Anda.
                            </small>
                        </div>
                    </div>
                </div>

                <!-- SUBMIT ACTIONS -->
                <div class="d-flex align-items-center justify-content-between pt-3 pb-5">
                    <a href="dashboard_<?= $role ?>.php" class="btn-secondary-modern">
                        <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
                    </a>
                    <button type="submit" class="btn-primary-green px-4 py-2">
                        <i class="fas fa-save me-1"></i> Simpan Semua Pengaturan
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
