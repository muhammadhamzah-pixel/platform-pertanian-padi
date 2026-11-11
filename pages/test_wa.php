<?php
include '../includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'] ?? 'petani';

// Ambil nomor HP user saat ini
$q_user = mysqli_query($conn, "SELECT no_hp, nama FROM users WHERE id = '$user_id'");
$d_user = mysqli_fetch_assoc($q_user);
$user_phone = $d_user['no_hp'] ?? '';
$user_nama = $d_user['nama'] ?? 'Petani';

$status_result = null;
$raw_response = null;
$is_success = false;

// Cek apakah token sudah diisi
$is_token_configured = (defined('FONNTE_TOKEN') && FONNTE_TOKEN !== 'TEMPEL_TOKEN_FONNTE_DISINI' && !empty(trim(FONNTE_TOKEN)));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $status_result = '❌ Token keamanan (CSRF) tidak valid.';
    } elseif (!$is_token_configured) {
        $status_result = '❌ Token Fonnte belum dipasang! Silakan tempel token Anda di file includes/config.php baris ke-70.';
    } else {
        $target_phone = sanitize_input($_POST['no_hp'] ?? '');
        $pesan = trim($_POST['pesan'] ?? '');

        if (empty($target_phone)) {
            $status_result = '❌ Nomor WhatsApp tujuan tidak boleh kosong!';
        } elseif (empty($pesan)) {
            $status_result = '❌ Pesan WhatsApp tidak boleh kosong!';
        } else {
            // Eksekusi pengiriman WA via helper function
            $res = kirimWaNotifikasi($target_phone, $pesan);
            $raw_response = $res;

            if ($res !== false) {
                $decoded = json_decode($res, true);
                if (isset($decoded['status']) && $decoded['status'] === true) {
                    $is_success = true;
                    $status_result = '🎉 PESAN BERHASIL TERKIRIM! Silakan cek WhatsApp di nomor ' . htmlspecialchars($target_phone);
                } else {
                    $detail = $decoded['reason'] ?? $decoded['detail'] ?? ($decoded['message'] ?? 'Periksa apakah Device WA di Fonnte dalam status Connected.');
                    $status_result = '⚠️ Fonnte merespons, namun pesan belum terkirim: ' . htmlspecialchars($detail);
                }
            } else {
                $status_result = '❌ Gagal menghubungi server Fonnte (cURL error / koneksi gagal).';
            }
        }
    }
}

include '../includes/navbar.php';
?>

<!-- SIGNATURE DASH HERO HEADER -->
<div class="dash-hero-header">
    <div class="dash-hero-inner">
        <div class="dash-hero-title">
            <h1><i class="fab fa-whatsapp text-success"></i> Uji Coba Notifikasi WhatsApp</h1>
            <p>Kirim pesan uji coba ke WhatsApp Anda untuk memastikan integrasi Fonnte API berjalan normal.</p>
        </div>
        <div class="dash-hero-actions">
            <a href="setting_notifikasi.php" class="btn-outline-white">
                <i class="fas fa-arrow-left"></i> Pengaturan Notifikasi
            </a>
        </div>
    </div>
</div>

<!-- MAIN PAGE CONTAINER -->
<div class="main-page-container">
    <div class="row justify-content-center">
        <div class="col-lg-8">

            <!-- STATUS TOKEN BANNER -->
            <?php if (!$is_token_configured): ?>
                <div class="alert-modern alert-modern-danger mb-4">
                    <i class="fas fa-exclamation-triangle fs-4 text-danger"></i>
                    <div>
                        <strong class="d-block mb-1">Token Fonnte Belum Dipasang!</strong>
                        <span>Buka file <code>includes/config.php</code> pada <strong>baris ke-70</strong>, lalu ganti teks <code>TEMPEL_TOKEN_FONNTE_DISINI</code> dengan API Device Token yang Anda salin dari akun Fonnte.</span>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert-modern alert-modern-success mb-4">
                    <i class="fas fa-check-circle fs-4 text-success"></i>
                    <div>
                        <strong class="d-block mb-1">Token Fonnte Terpasang</strong>
                        <span>Sistem sudah siap mengirim pesan WhatsApp otomatis ke nomor petani.</span>
                    </div>
                </div>
            <?php endif; ?>

            <!-- RESULT ALERT -->
            <?php if ($status_result): ?>
                <div class="alert-modern <?= $is_success ? 'alert-modern-success' : 'alert-modern-danger' ?> mb-4">
                    <i class="fas <?= $is_success ? 'fa-check-circle' : 'fa-times-circle' ?> fs-4"></i>
                    <div>
                        <div class="fw-bold fs-6"><?= $status_result ?></div>
                        <?php if ($raw_response): ?>
                            <details class="mt-2 text-start">
                                <summary class="small cursor-pointer text-muted">Lihat respons teknis dari server Fonnte</summary>
                                <pre class="bg-dark text-light p-3 rounded mt-2 small" style="white-space: pre-wrap; word-break: break-all;"><?= htmlspecialchars($raw_response) ?></pre>
                            </details>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- FORM TESTING CARD -->
            <div class="card-modern">
                <div class="card-modern-header">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fas fa-paper-plane text-success fs-5"></i>
                        <h2 class="card-modern-title mb-0">Formulir Kirim Uji Coba</h2>
                    </div>
                    <span class="badge bg-light text-muted border">Live Test</span>
                </div>
                <div class="card-modern-body">
                    <form method="POST" action="">
                        <?= csrf_field() ?>

                        <div class="mb-3">
                            <label class="form-label-modern">Nomor WhatsApp Tujuan</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0">
                                    <i class="fab fa-whatsapp text-success"></i>
                                </span>
                                <input type="text" name="no_hp" class="form-control-modern" style="border-top-left-radius: 0; border-bottom-left-radius: 0;" value="<?= htmlspecialchars($_POST['no_hp'] ?? $user_phone) ?>" placeholder="Contoh: 081234567890" required>
                            </div>
                            <small class="text-muted mt-1 d-block">
                                Nomor WhatsApp yang akan menerima pesan tes ini (bisa nomor Anda sendiri).
                            </small>
                        </div>

                        <div class="mb-4">
                            <label class="form-label-modern">Isi Pesan WhatsApp</label>
                            <textarea name="pesan" class="form-control-modern" rows="5" required><?= htmlspecialchars($_POST['pesan'] ?? "🌾 *Tes Notifikasi Platform Pertanian Padi*\n\nHalo " . $user_nama . "! Ini adalah pesan uji coba integrasi WhatsApp via Fonnte API.\n\nNotifikasi jadwal pemupukan dan estimasi panen padi Anda akan dikirimkan otomatis melalui nomor ini. Semoga panen berkah & melimpah! 🌾✨") ?></textarea>
                            <small class="text-muted mt-1 d-block">
                                <i class="fas fa-info-circle text-primary me-1"></i> Format teks WhatsApp: gunakan <code>*teks*</code> untuk huruf tebal (bold), <code>_teks_</code> untuk huruf miring (italic).
                            </small>
                        </div>

                        <div class="d-flex align-items-center justify-content-between pt-3 border-top">
                            <a href="setting_notifikasi.php" class="btn-secondary-modern">
                                <i class="fas fa-arrow-left"></i> Kembali ke Pengaturan
                            </a>
                            <button type="submit" class="btn-primary-green px-4 py-2" <?= !$is_token_configured ? 'disabled' : '' ?>>
                                <i class="fas fa-paper-plane me-1"></i> Kirim Pesan Sekarang
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- PETUNJUK TAMBAHAN -->
            <div class="card-modern mt-4">
                <div class="card-modern-body p-4">
                    <h6 class="fw-bold text-dark mb-3">
                        <i class="fas fa-lightbulb text-warning me-2"></i>Catatan Penggunaan Fonnte:
                    </h6>
                    <ul class="text-secondary small ps-3 mb-0" style="line-height: 1.8;">
                        <li>Pastikan status WhatsApp di tab <strong>Device</strong> pada dashboard Fonnte tetap <strong>Connected</strong>.</li>
                        <li>Format nomor handphone bebas: bisa diawali <code>08...</code> atau <code>628...</code>, sistem akan otomatis menyesuaikannya.</li>
                        <li>Pesan otomatis untuk siklus padi (pemupukan 1 & 2, cek air, H-7, H-3, panen) akan otomatis terkirim saat petani membuka website atau melalui jadwal cron background.</li>
                    </ul>
                </div>
            </div>

        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

