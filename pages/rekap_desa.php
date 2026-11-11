<?php
include '../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'perangkat_desa') {
    header('Location: login.php');
    exit();
}

$query_petani = "SELECT COUNT(*) AS total FROM users WHERE role = 'petani'";
$result_petani = mysqli_query($conn, $query_petani);
$total_petani = mysqli_fetch_assoc($result_petani)['total'] ?? 0;

$query_lahan = "SELECT COUNT(*) AS total, COALESCE(SUM(luas), 0) AS total_luas FROM lahan";
$result_lahan = mysqli_query($conn, $query_lahan);
$data_lahan = mysqli_fetch_assoc($result_lahan);
$total_lahan = $data_lahan['total'] ?? 0;
$total_luas = $data_lahan['total_luas'] ?? 0;
$total_panen = round($total_luas * 0.005, 1); // Estimasi panen (Ton)

$query_status = "SELECT status, COUNT(*) AS jumlah FROM lahan GROUP BY status";
$result_status = mysqli_query($conn, $query_status);

$status_data = [];
while ($row = mysqli_fetch_assoc($result_status)) {
    $status_data[$row['status']] = $row['jumlah'];
}

// Data varietas padi
$query_varietas = "SELECT jenis_padi AS varietas, COUNT(*) AS total, SUM(luas) AS total_luas FROM lahan GROUP BY jenis_padi ORDER BY total DESC LIMIT 5";
$result_varietas = mysqli_query($conn, $query_varietas);

include '../includes/navbar.php';
?>

<!-- SIGNATURE DASH HERO HEADER -->
<div class="dash-hero-header">
    <div class="dash-hero-inner">
        <div class="dash-hero-title">
            <h1><i class="fas fa-chart-pie"></i> <?= t('recap_title') ?></h1>
            <p><?= t('recap_subtitle') ?></p>
        </div>
        <div class="dash-hero-actions">
            <a href="export_rekap_desa.php" class="btn-primary-green">
                <i class="fas fa-file-excel"></i> <?= t('btn_export') ?>
            </a>
            <a href="dashboard_desa.php" class="btn-outline-white">
                <i class="fas fa-arrow-left"></i> Dashboard
            </a>
        </div>
    </div>
</div>

<!-- MAIN PAGE CONTAINER -->
<div class="main-page-container">

    <!-- STATS GRID -->
    <div class="stats-grid mb-4">
        <div class="stat-card">
            <div class="stat-card-header">
                <span class="stat-card-title"><?= t('recap_total_farmer') ?></span>
                <div class="stat-card-icon stat-icon-emerald"><i class="fas fa-users"></i></div>
            </div>
            <div class="stat-card-value"><?= number_format($total_petani) ?></div>
            <div class="stat-card-footer text-muted">Petani Warga Terdaftar</div>
        </div>

        <div class="stat-card">
            <div class="stat-card-header">
                <span class="stat-card-title"><?= t('recap_total_land') ?></span>
                <div class="stat-card-icon stat-icon-amber"><i class="fas fa-layer-group"></i></div>
            </div>
            <div class="stat-card-value"><?= number_format($total_lahan) ?></div>
            <div class="stat-card-footer text-muted">Petak Sawah Terdata</div>
        </div>

        <div class="stat-card">
            <div class="stat-card-header">
                <span class="stat-card-title"><?= t('recap_total_area') ?></span>
                <div class="stat-card-icon stat-icon-blue"><i class="fas fa-vector-square"></i></div>
            </div>
            <div class="stat-card-value"><?= number_format($total_luas, 0) ?> <small style="font-size: 1rem; font-weight: 600;">m²</small></div>
            <div class="stat-card-footer text-muted">Total Luas Area Tanam</div>
        </div>

        <div class="stat-card">
            <div class="stat-card-header">
                <span class="stat-card-title">Estimasi Produksi Padi</span>
                <div class="stat-card-icon stat-icon-emerald"><i class="fas fa-seedling"></i></div>
            </div>
            <div class="stat-card-value"><?= number_format($total_panen, 0) ?> <small style="font-size: 1rem; font-weight: 600;">kg</small></div>
            <div class="stat-card-footer text-muted">Proyeksi Hasil Gabah</div>
        </div>
    </div>

    <!-- ROW DUA KOLOM: STATUS TAHAPAN & VARIETAS -->
    <div class="row g-4 mb-4">
        <!-- TAHAPAN LAHAN -->
        <div class="col-lg-6">
            <div class="card-modern h-100">
                <div class="card-modern-header">
                    <h2 class="card-modern-title">
                        <i class="fas fa-clock text-success"></i> <?= t('recap_status') ?>
                    </h2>
                    <span class="badge bg-light text-muted border">Siklus Tanam</span>
                </div>
                <div class="card-modern-body">
                    <?php
                    $tanam_c = $status_data['tanam'] ?? 0;
                    $tumbuh_c = $status_data['tumbuh'] ?? 0;
                    $panen_c = $status_data['panen'] ?? 0;
                    $selesai_c = $status_data['selesai'] ?? 0;
                    $total_aktif = max(1, $total_lahan);
                    ?>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="fw-semibold text-dark"><i class="fas fa-seedling text-success me-1"></i> <?= t('status_planting') ?> (Tahap Awal)</span>
                            <strong><?= $tanam_c ?> Petak (<?= round(($tanam_c / $total_aktif) * 100) ?>%)</strong>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-success" style="width: <?= ($tanam_c / $total_aktif) * 100 ?>%"></div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="fw-semibold text-dark"><i class="fas fa-leaf text-primary me-1"></i> <?= t('status_growing') ?> (Vegetatif & Generatif)</span>
                            <strong><?= $tumbuh_c ?> Petak (<?= round(($tumbuh_c / $total_aktif) * 100) ?>%)</strong>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-info" style="width: <?= ($tumbuh_c / $total_aktif) * 100 ?>%"></div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="fw-semibold text-dark"><i class="fas fa-wheat-awn text-warning me-1"></i> <?= t('status_harvest') ?> (Siap Panen)</span>
                            <strong><?= $panen_c ?> Petak (<?= round(($panen_c / $total_aktif) * 100) ?>%)</strong>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-warning" style="width: <?= ($panen_c / $total_aktif) * 100 ?>%"></div>
                        </div>
                    </div>

                    <div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="fw-semibold text-dark"><i class="fas fa-check-circle text-secondary me-1"></i> <?= t('status_finished') ?> (Selesai Panen)</span>
                            <strong><?= $selesai_c ?> Petak (<?= round(($selesai_c / $total_aktif) * 100) ?>%)</strong>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-secondary" style="width: <?= ($selesai_c / $total_aktif) * 100 ?>%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- VARIETAS PADI TERBANYAK -->
        <div class="col-lg-6">
            <div class="card-modern h-100">
                <div class="card-modern-header">
                    <h2 class="card-modern-title">
                        <i class="fas fa-award text-warning"></i> Varietas Padi Pilihan Warga
                    </h2>
                    <span class="badge bg-light text-muted border">Top Varietas</span>
                </div>
                <div class="table-responsive">
                    <table class="table-modern">
                        <thead>
                            <tr>
                                <th>Varietas Padi</th>
                                <th>Jumlah Petak</th>
                                <th>Total Luas</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result_varietas && mysqli_num_rows($result_varietas) > 0): ?>
                                <?php while ($var = mysqli_fetch_assoc($result_varietas)): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <i class="fas fa-seedling text-success"></i>
                                                <strong class="text-dark"><?= htmlspecialchars($var['varietas'] ?? 'Varietas Lokal') ?></strong>
                                            </div>
                                        </td>
                                        <td><span class="badge bg-light text-dark border"><?= $var['total'] ?> Lahan</span></td>
                                        <td class="text-secondary small"><?= number_format($var['total_luas'] ?? 0) ?> m²</td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-4">Belum ada data varietas tersimpan.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>

<?php include '../includes/footer.php'; ?>