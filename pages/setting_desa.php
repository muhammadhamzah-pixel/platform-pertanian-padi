<?php
include '../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit();
}

$message = '';
$error = '';

$query = "SELECT * FROM desa_settings LIMIT 1";
$result = mysqli_query($conn, $query);
$setting = mysqli_fetch_assoc($result);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error = t('err_csrf');
    } else {
        $nama_desa = sanitize_input($_POST['nama_desa']);
        $kecamatan = sanitize_input($_POST['kecamatan']);
        $kabupaten = sanitize_input($_POST['kabupaten']);
        $kontak = sanitize_input($_POST['kontak']);
        $default_hasil_per_m2 = sanitize_input($_POST['default_hasil_per_m2']);
        
        $validasi = cek_validasi([
            validasi_nama($nama_desa),
            validasi_nama($kecamatan),
            validasi_nama($kabupaten),
            validasi_no_hp($kontak),
            validasi_angka_positif($default_hasil_per_m2, 'Default Hasil')
        ]);
        
        if ($validasi !== true) {
            $error = "❌ " . $validasi;
        } else {
            $nama_desa = mysqli_real_escape_string($conn, $nama_desa);
            $kecamatan = mysqli_real_escape_string($conn, $kecamatan);
            $kabupaten = mysqli_real_escape_string($conn, $kabupaten);
            $kontak = mysqli_real_escape_string($conn, $kontak);
            $default_hasil_per_m2 = mysqli_real_escape_string($conn, $default_hasil_per_m2);
            
            if ($setting) {
                $query = "UPDATE desa_settings SET 
                            nama_desa = '$nama_desa',
                            kecamatan = '$kecamatan',
                            kabupaten = '$kabupaten',
                            kontak = '$kontak',
                            default_hasil_per_m2 = '$default_hasil_per_m2'
                          WHERE id = " . $setting['id'];
            } else {
                $query = "INSERT INTO desa_settings (nama_desa, kecamatan, kabupaten, kontak, default_hasil_per_m2) 
                          VALUES ('$nama_desa', '$kecamatan', '$kabupaten', '$kontak', '$default_hasil_per_m2')";
            }
            
            if (mysqli_query($conn, $query)) {
                $message = '✅ ' . t('setting_success');
                catat_log($conn, $_SESSION['user_id'], $_SESSION['email'], $_SESSION['nama'], 'Setting Desa', "Update data desa: $nama_desa");
                regenerate_csrf_token();
                $result = mysqli_query($conn, "SELECT * FROM desa_settings LIMIT 1");
                $setting = mysqli_fetch_assoc($result);
            } else {
                $error = '❌ ' . t('failed') . ': ' . mysqli_error($conn);
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
            <h1><i class="fas fa-map-marked-alt"></i> <?= t('setting_title') ?></h1>
            <p><?= t('setting_subtitle') ?></p>
        </div>
        <div class="dash-hero-actions">
            <a href="dashboard_admin.php" class="btn-outline-white">
                <i class="fas fa-arrow-left"></i> Dashboard
            </a>
        </div>
    </div>
</div>

<!-- MAIN PAGE CONTAINER -->
<div class="main-page-container">

    <div class="row justify-content-center">
        <div class="col-lg-8">

            <?php if ($setting && !empty($setting['nama_desa'])): ?>
                <div class="alert-modern alert-modern-success mb-4">
                    <i class="fas fa-map-pin fs-5 text-success"></i>
                    <div>
                        <strong>Wilayah Terkonfigurasi:</strong> Desa <strong><?= htmlspecialchars($setting['nama_desa']) ?></strong>, 
                        Kec. <?= htmlspecialchars($setting['kecamatan']) ?>, Kab. <?= htmlspecialchars($setting['kabupaten']) ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert-modern alert-modern-warning mb-4">
                    <i class="fas fa-exclamation-triangle fs-5 text-warning"></i>
                    <div><?= t('setting_not_set') ?></div>
                </div>
            <?php endif; ?>

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

            <!-- FORM CARD -->
            <div class="card-modern">
                <div class="card-modern-header">
                    <h2 class="card-modern-title">
                        <i class="fas fa-cogs"></i> Profil Wilayah & Parameter Panen
                    </h2>
                    <span class="badge bg-light text-muted border">Konfigurasi Desa</span>
                </div>
                <div class="card-modern-body">
                    <form method="POST" action="">
                        <?= csrf_field() ?>
                        
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label-modern"><?= t('setting_village_name') ?> *</label>
                                <input type="text" name="nama_desa" class="form-control-modern" value="<?= htmlspecialchars($setting['nama_desa'] ?? '') ?>" placeholder="Contoh: Sukamaju" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label-modern"><?= t('setting_district') ?> *</label>
                                <input type="text" name="kecamatan" class="form-control-modern" value="<?= htmlspecialchars($setting['kecamatan'] ?? '') ?>" placeholder="Contoh: Cibadak" required>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label-modern"><?= t('setting_regency') ?> *</label>
                                <input type="text" name="kabupaten" class="form-control-modern" value="<?= htmlspecialchars($setting['kabupaten'] ?? '') ?>" placeholder="Contoh: Sukabumi" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label-modern"><?= t('setting_contact') ?></label>
                                <input type="text" name="kontak" class="form-control-modern" value="<?= htmlspecialchars($setting['kontak'] ?? '') ?>" placeholder="08xxxxxxxxxx">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label-modern"><?= t('setting_default_yield') ?> (kg / m²) *</label>
                            <input type="number" step="0.1" name="default_hasil_per_m2" class="form-control-modern" value="<?= htmlspecialchars($setting['default_hasil_per_m2'] ?? '5.0') ?>" required>
                            <div class="text-muted small mt-1">
                                <i class="fas fa-info-circle me-1"></i> <?= t('setting_default_yield_desc') ?>
                            </div>
                        </div>

                        <div class="d-flex align-items-center justify-content-between pt-2">
                            <a href="dashboard_admin.php" class="btn-secondary-modern">
                                <i class="fas fa-arrow-left"></i> <?= t('btn_back') ?>
                            </a>
                            <button type="submit" class="btn-primary-green">
                                <i class="fas fa-save"></i> <?= t('setting_save') ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>

</div>

<?php include '../includes/footer.php'; ?>