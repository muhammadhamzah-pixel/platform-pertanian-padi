<?php
include '../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'petani') {
    header('Location: ' . $base_url . 'login');
    exit();
}

$user_id = $_SESSION['user_id'];

// Otomatis sinkronkan notifikasi siklus pertumbuhan padi (hari ke-3, 7, pupuk 1 & 2, H-7, H-3, panen)
if (function_exists('sync_notifikasi_petani')) {
    sync_notifikasi_petani($conn, $user_id);
}

// Ambil foto profil
$foto_query = "SELECT foto FROM users WHERE id = '$user_id'";
$foto_result = mysqli_query($conn, $foto_query);
$foto_data = mysqli_fetch_assoc($foto_result);
$foto = $foto_data['foto'] ?? 'defaults.png';

// Hitung notifikasi unread
$notif_query = "SELECT COUNT(*) AS total FROM notifikasi WHERE user_id = '$user_id' AND is_read = 0";
$notif_result = mysqli_query($conn, $notif_query);
$notif_count = mysqli_fetch_assoc($notif_result)['total'] ?? 0;

// Ambil 5 notifikasi terbaru
$list_query = "SELECT * FROM notifikasi WHERE user_id = '$user_id' ORDER BY created_at DESC LIMIT 5";
$list_result = mysqli_query($conn, $list_query);

// Data statistik lahan petani
$lahan_stat = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total_lahan, COALESCE(SUM(luas), 0) AS total_luas FROM lahan WHERE user_id = '$user_id'"));
$my_total_lahan = $lahan_stat['total_lahan'] ?? 0;
$my_total_luas = $lahan_stat['total_luas'] ?? 0;
$my_total_panen = round($my_total_luas * 0.005, 1); // Perkiraan panen (Ton) berdasarkan luas sawah

// Lahan siap panen
$siap_panen_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM lahan WHERE user_id = '$user_id' AND status = 'panen'"))['total'] ?? 0;

// Ambil daftar lahan milik petani ini
$lahan_list = mysqli_query($conn, "SELECT * FROM lahan WHERE user_id = '$user_id' ORDER BY id DESC LIMIT 5");

include '../includes/navbar.php';
?>

<!-- SIGNATURE DASH HERO HEADER -->
<div class="dash-hero-header">
    <div class="dash-hero-inner">
        <div class="dash-hero-title">
            <h1><i class="fas fa-seedling"></i> <?= t('dashboard_petani') ?></h1>
            <p><?= t('welcome_back') ?>, <strong><?= htmlspecialchars($_SESSION['nama']) ?></strong>! Pantau kondisi sawah Anda, perkiraan panen, dan konsultasi budidaya bersama AI.</p>
        </div>
        <div class="dash-hero-actions">
            <a href="tambah_lahan.php" class="btn-primary-green">
                <i class="fas fa-plus-circle"></i> <?= t('menu_add_land') ?>
            </a>
            <a href="data_lahan.php" class="btn-outline-white">
                <i class="fas fa-layer-group"></i> <?= t('menu_land_data') ?>
            </a>
            <a href="ai_chat.php" class="btn-outline-white">
                <i class="fas fa-robot"></i> Sahabat Tani AI
            </a>
        </div>
    </div>
</div>

<!-- MAIN PAGE CONTAINER -->
<div class="dashboard-container">

    <!-- FLOATING STATS GRID -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-card-header">
                <span class="stat-card-title"><?= t('menu_land_data') ?></span>
                <div class="stat-card-icon stat-icon-emerald"><i class="fas fa-layer-group"></i></div>
            </div>
            <div class="stat-card-value"><?= number_format($my_total_lahan) ?></div>
            <div class="stat-card-footer">
                <span class="stat-trend-up"><i class="fas fa-map-marker-alt"></i> Petak Lahan</span>
                <span>aktif</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-card-header">
                <span class="stat-card-title">Luas Sawah</span>
                <div class="stat-card-icon stat-icon-blue"><i class="fas fa-vector-square"></i></div>
            </div>
            <div class="stat-card-value"><?= number_format($my_total_luas, 0) ?> <small style="font-size: 1rem; font-weight: 600;">m²</small></div>
            <div class="stat-card-footer">
                <span>Total area tanam</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-card-header">
                <span class="stat-card-title">Siap Panen</span>
                <div class="stat-card-icon stat-icon-amber"><i class="fas fa-wheat-awn"></i></div>
            </div>
            <div class="stat-card-value text-warning"><?= number_format($siap_panen_count) ?></div>
            <div class="stat-card-footer">
                <span>Petak siap panen</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-card-header">
                <span class="stat-card-title">Pemberitahuan</span>
                <div class="stat-card-icon stat-icon-rose"><i class="fas fa-bell"></i></div>
            </div>
            <div class="stat-card-value <?= $notif_count > 0 ? 'text-danger' : 'text-success' ?>">
                <?= number_format($notif_count) ?>
            </div>
            <div class="stat-card-footer">
                <span>Pesan belum dibaca</span>
            </div>
        </div>
    </div>

    <!-- MONITORING LAHAN SAWAH SAYA (FULL WIDTH) -->
    <div class="card-modern">
        <div class="card-modern-header">
            <h2 class="card-modern-title">
                <i class="fas fa-layer-group text-success"></i> Monitoring Lahan Sawah Saya
            </h2>
            <a href="data_lahan.php" class="btn btn-sm btn-outline-secondary rounded-pill px-3" style="font-size: 12px; font-weight: 600;">
                Lihat Semua <i class="fas fa-arrow-right ms-1"></i>
            </a>
        </div>

        <?php if ($my_total_lahan > 0): ?>
            <div class="table-responsive">
                <table class="table-modern">
                    <thead>
                        <tr>
                            <th>Nama Lahan</th>
                            <th>Lokasi</th>
                            <th>Varietas</th>
                            <th>Luas</th>
                            <th>Status Tanam</th>
                            <th>Perkiraan Panen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($l = mysqli_fetch_assoc($lahan_list)): ?>
                            <?php
                            $st = $l['status'] ?? 'tanam';
                            $badge_cls = ($st == 'panen') ? 'badge-status-pending' : (($st == 'selesai') ? 'badge-role-admin' : 'badge-status-active');
                            ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="fas fa-leaf text-success"></i>
                                        <strong class="text-dark"><?= htmlspecialchars($l['nama_lahan']) ?></strong>
                                    </div>
                                </td>
                                <td class="text-secondary small"><?= htmlspecialchars($l['lokasi']) ?></td>
                                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($l['jenis_padi'] ?? $l['varietas'] ?? 'IR64') ?></span></td>
                                <td class="text-secondary small"><?= number_format($l['luas'], 0) ?> m²</td>
                                <td>
                                    <span class="badge-pill-modern <?= $badge_cls ?>">
                                        <?= ucfirst($st) ?>
                                    </span>
                                </td>
                                <td class="text-muted small" style="white-space: nowrap;">
                                    <i class="far fa-calendar-alt me-1"></i> <?= date('d M Y', strtotime($l['tgl_panen_perkiraan'] ?? $l['tgl_tanam'])) ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="p-5 text-center text-muted">
                <i class="fas fa-seedling fa-3x mb-3 text-success opacity-50"></i>
                <h5 class="text-dark fw-bold">Belum Ada Data Lahan</h5>
                <p class="small text-muted mb-3">Daftarkan lahan sawah Anda sekarang untuk memantau jadwal panen dan rekomendasi pupuk otomatis.</p>
                <a href="tambah_lahan.php" class="btn-primary-green">
                    <i class="fas fa-plus-circle"></i> Tambah Lahan Sekarang
                </a>
            </div>
        <?php endif; ?>
    </div>

</div>

<?php include '../includes/footer.php'; ?>