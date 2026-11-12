<?php
include '../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'petani') {
    header('Location: ' . $base_url . 'login');
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error = t('err_csrf');
    } else {
        $nama_lahan = sanitize_input($_POST['nama_lahan']);
        $lokasi = sanitize_input($_POST['lokasi']);
        $luas = sanitize_input($_POST['luas']);
        $jenis_padi = sanitize_input($_POST['jenis_padi']);
        $tgl_tanam = sanitize_input($_POST['tgl_tanam']);
        $catatan = sanitize_input($_POST['catatan']);
        
        $validasi = cek_validasi([
            validasi_nama_lahan($nama_lahan, 'Nama Lahan'),
            validasi_alamat($lokasi, 'Lokasi'),
            validasi_angka_positif($luas, 'Luas'),
            validasi_tanggal($tgl_tanam, 'Tanggal Tanam')
        ]);
        
        if ($validasi !== true) {
            $error = "❌ " . $validasi;
        } else {
            $nama_lahan = mysqli_real_escape_string($conn, $nama_lahan);
            $lokasi = mysqli_real_escape_string($conn, $lokasi);
            $luas = mysqli_real_escape_string($conn, $luas);
            $jenis_padi = mysqli_real_escape_string($conn, $jenis_padi);
            $tgl_tanam = mysqli_real_escape_string($conn, $tgl_tanam);
            $catatan = mysqli_real_escape_string($conn, $catatan);
            
            $tgl_panen_perkiraan = date('Y-m-d', strtotime($tgl_tanam . ' + 120 days'));
            $query = "INSERT INTO lahan (user_id, nama_lahan, lokasi, luas, jenis_padi, tgl_tanam, tgl_panen_perkiraan, catatan) 
                      VALUES ('$user_id', '$nama_lahan', '$lokasi', '$luas', '$jenis_padi', '$tgl_tanam', '$tgl_panen_perkiraan', '$catatan')";
            
            if (mysqli_query($conn, $query)) {
                $lahan_id_baru = mysqli_insert_id($conn);
                $message = "✅ " . t('land_added_success');
                catat_log($conn, $user_id, $_SESSION['email'], $_SESSION['nama'], 'Tambah Lahan', "Nama: $nama_lahan, Luas: $luas m²");
                
                // Notifikasi Web
                $judul_notif = t('notif_new_land_title');
                $pesan_notif = t('notif_new_land_msg_prefix') . " \"$nama_lahan\" " . t('notif_new_land_msg_suffix');
                $link_notif = "data_lahan.php";
                $tipe_notif = "tanam";
                
                $insert_notif = "INSERT INTO notifikasi (user_id, judul, pesan, link, tipe, is_read) 
                                 VALUES ('$user_id', '$judul_notif', '$pesan_notif', '$link_notif', '$tipe_notif', 0)";
                mysqli_query($conn, $insert_notif);

                // Notifikasi Eksternal
                if (function_exists('kirimEmailNotifikasi')) {
                    kirimEmailNotifikasi($_SESSION['email'], $_SESSION['nama'], $judul_notif, $pesan_notif);
                }
                
                $tg_id = $_SESSION['telegram_chat_id'] ?? '';
                if (!empty($tg_id) && function_exists('kirimTelegramNotifikasi')) {
                    $pesan_tg = "🌾 <b>" . $judul_notif . "</b>\n\n" . $pesan_notif;
                    kirimTelegramNotifikasi($tg_id, $pesan_tg);
                }

                $user_no_hp = $_SESSION['no_hp'] ?? '';
                if (empty($user_no_hp)) {
                    $q_hp = mysqli_query($conn, "SELECT no_hp FROM users WHERE id = '$user_id'");
                    if ($q_hp && $row_hp = mysqli_fetch_assoc($q_hp)) {
                        $user_no_hp = $row_hp['no_hp'];
                        $_SESSION['no_hp'] = $user_no_hp;
                    }
                }
                if (!empty($user_no_hp) && function_exists('kirimWaNotifikasi')) {
                    $pesan_wa = "🌾 *" . $judul_notif . "*\n\n" . $pesan_notif;
                    kirimWaNotifikasi($user_no_hp, $pesan_wa);
                }
                
                regenerate_csrf_token();
            } else {
                $error = "❌ " . t('failed') . ": " . mysqli_error($conn);
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
            <h1><i class="fas fa-plus-circle"></i> <?= t('land_title_add') ?></h1>
            <p><?= t('land_subtitle') ?></p>
        </div>
        <div class="dash-hero-actions">
            <a href="data_lahan.php" class="btn-outline-white">
                <i class="fas fa-layer-group"></i> <?= t('menu_land_data') ?>
            </a>
            <a href="dashboard_petani.php" class="btn-outline-white">
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

            <!-- CARD FORM -->
            <div class="card-modern">
                <div class="card-modern-header">
                    <h2 class="card-modern-title">
                        <i class="fas fa-seedling text-success"></i> Formulir Pendaftaran Petak Sawah
                    </h2>
                    <span class="badge bg-light text-muted border">Petani: <?= htmlspecialchars($_SESSION['nama']) ?></span>
                </div>
                <div class="card-modern-body">
                    <form method="POST" action="">
                        <?= csrf_field() ?>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label-modern"><?= t('land_name') ?> *</label>
                                <input type="text" name="nama_lahan" class="form-control-modern" placeholder="<?= t('land_placeholder_name') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label-modern"><?= t('land_location') ?> *</label>
                                <input type="text" name="lokasi" class="form-control-modern" placeholder="<?= t('land_placeholder_location') ?>" required>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label-modern"><?= t('land_area') ?> (m²) *</label>
                                <input type="number" name="luas" class="form-control-modern" placeholder="<?= t('land_placeholder_area') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label-modern"><?= t('land_rice_type') ?> *</label>
                                <select name="jenis_padi" class="form-control-modern form-select" required>
                                    <option value=""><?= t('land_select_rice') ?></option>
                                    <option value="Ciherang">Ciherang</option>
                                    <option value="IR64">IR64</option>
                                    <option value="Inpari 32">Inpari 32</option>
                                    <option value="Inpari 42">Inpari 42</option>
                                    <option value="Mekongga">Mekongga</option>
                                    <option value="Sintanur">Sintanur</option>
                                    <option value="Cisadane">Cisadane</option>
                                    <option value="Lainnya"><?= t('others') ?></option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label-modern"><?= t('land_plant_date') ?> *</label>
                            <input type="date" name="tgl_tanam" class="form-control-modern" value="<?= date('Y-m-d') ?>" required>
                            <small class="text-muted"><i class="fas fa-info-circle me-1"></i> Perkiraan panen otomatis dihitung 120 hari dari tanggal tanam.</small>
                        </div>

                        <div class="mb-4">
                            <label class="form-label-modern"><?= t('land_notes') ?></label>
                            <textarea name="catatan" class="form-control-modern" rows="3" placeholder="<?= t('land_placeholder_notes') ?>"></textarea>
                        </div>

                        <div class="d-flex align-items-center justify-content-between pt-2">
                            <a href="data_lahan.php" class="btn-secondary-modern">
                                <i class="fas fa-arrow-left"></i> Batal
                            </a>
                            <button type="submit" class="btn-primary-green">
                                <i class="fas fa-save"></i> <?= t('land_save') ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>