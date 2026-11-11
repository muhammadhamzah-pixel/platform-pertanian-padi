<?php
include '../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'perangkat_desa') {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$foto_query = "SELECT foto FROM users WHERE id = '$user_id'";
$foto_result = mysqli_query($conn, $foto_query);
$foto_data = mysqli_fetch_assoc($foto_result);
$foto = $foto_data['foto'] ?? 'defaults.png';

// Query statistik riil untuk perangkat desa
$total_petani = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM users WHERE role='petani'"))['total'] ?? 0;
$total_pending = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM users WHERE role='petani' AND status='pending'"))['total'] ?? 0;

$lahan_stats = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total_lahan, COALESCE(SUM(luas), 0) AS total_luas FROM lahan"));
$total_lahan = $lahan_stats['total_lahan'] ?? 0;
$total_luas = $lahan_stats['total_luas'] ?? 0;
$total_panen = round($total_luas * 0.005, 1); // Estimasi panen (Ton)

$desa_setting = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM desa_settings LIMIT 1"));
$nama_desa = !empty($desa_setting['nama_desa']) ? $desa_setting['nama_desa'] : 'Wilayah Desa';

// Petani pending verifikasi terbaru (3 teratas)
$pending_list = mysqli_query($conn, "SELECT id, nama, email, created_at FROM users WHERE role='petani' AND status='pending' ORDER BY created_at DESC LIMIT 3");

include '../includes/navbar.php';
?>

<!-- SIGNATURE DASH HERO HEADER -->
<div class="dash-hero-header">
    <div class="dash-hero-inner">
        <div class="dash-hero-title">
            <h1><i class="fas fa-landmark"></i> <?= t('dashboard_desa') ?> <?= htmlspecialchars($nama_desa) ?></h1>
            <p><?= t('welcome_back') ?>, <strong><?= htmlspecialchars($_SESSION['nama']) ?></strong>! Kelola verifikasi petani warga, rekapitulasi data sawah, dan estimasi produksi pangan desa.</p>
        </div>
        <div class="dash-hero-actions">
            <a href="verifikasi_akun.php" class="btn-primary-green">
                <i class="fas fa-user-check"></i> Verifikasi Petani
                <?php if ($total_pending > 0): ?>
                    <span class="badge bg-warning text-dark ms-1"><?= $total_pending ?></span>
                <?php endif; ?>
            </a>
            <a href="rekap_desa.php" class="btn-outline-white">
                <i class="fas fa-file-invoice"></i> Rekap Pertanian
            </a>
        </div>
    </div>
</div>

<!-- MAIN CONTAINER -->
<div class="dashboard-container">

    <!-- FLOATING STATS GRID -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-card-header">
                <span class="stat-card-title"><?= t('menu_farmer_data') ?></span>
                <div class="stat-card-icon stat-icon-emerald"><i class="fas fa-users"></i></div>
            </div>
            <div class="stat-card-value"><?= number_format($total_petani) ?></div>
            <div class="stat-card-footer">
                <span class="stat-trend-up"><i class="fas fa-user"></i> Petani Warga</span>
                <span>terdaftar</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-card-header">
                <span class="stat-card-title">Verifikasi Tertunda</span>
                <div class="stat-card-icon <?= $total_pending > 0 ? 'stat-icon-rose' : 'stat-icon-emerald' ?>">
                    <i class="fas fa-user-clock"></i>
                </div>
            </div>
            <div class="stat-card-value <?= $total_pending > 0 ? 'text-danger' : 'text-success' ?>">
                <?= number_format($total_pending) ?>
            </div>
            <div class="stat-card-footer">
                <span><?= $total_pending > 0 ? 'Menunggu persetujuan' : 'Semua akun tervalidasi' ?></span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-card-header">
                <span class="stat-card-title">Total Petak Lahan</span>
                <div class="stat-card-icon stat-icon-amber"><i class="fas fa-layer-group"></i></div>
            </div>
            <div class="stat-card-value"><?= number_format($total_lahan) ?></div>
            <div class="stat-card-footer">
                <span class="stat-trend-up"><i class="fas fa-vector-square"></i> Luas: <?= number_format($total_luas) ?> m²</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-card-header">
                <span class="stat-card-title">Estimasi Panen Desa</span>
                <div class="stat-card-icon stat-icon-blue"><i class="fas fa-seedling"></i></div>
            </div>
            <div class="stat-card-value"><?= number_format($total_panen) ?> <small style="font-size: 1rem; font-weight: 600;">kg</small></div>
            <div class="stat-card-footer">
                <span class="stat-trend-up"><i class="fas fa-chart-line"></i> Prediksi hasil panen</span>
            </div>
        </div>
    </div>

    <!-- MAIN TWO COLUMN ROW -->
    <!-- QUICK MENU CARDS (FULL WIDTH) -->
    <div class="card-modern mb-4">
        <div class="card-modern-header">
            <h2 class="card-modern-title">
                <i class="fas fa-th-large"></i> Fitur & Pelayanan Pertanian Desa
            </h2>
            <span class="badge bg-light text-muted border">Wilayah <?= htmlspecialchars($nama_desa) ?></span>
        </div>
        <div class="card-modern-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <a href="verifikasi_akun.php" class="text-decoration-none">
                        <div class="p-3 rounded-3 border bg-light h-100 transition-hover text-center position-relative">
                            <?php if ($total_pending > 0): ?>
                                <span class="position-absolute top-0 end-0 translate-middle badge rounded-pill bg-danger">
                                    <?= $total_pending ?>
                                </span>
                            <?php endif; ?>
                            <div class="stat-card-icon stat-icon-emerald mx-auto mb-2" style="width: 48px; height: 48px;">
                                <i class="fas fa-user-check"></i>
                            </div>
                            <h6 class="fw-bold text-dark mb-1"><?= t('menu_verify_account') ?></h6>
                            <small class="text-muted"><?= t('verify_account_subtitle') ?></small>
                        </div>
                    </a>
                </div>

                <div class="col-md-4">
                    <a href="data_semua_petani.php" class="text-decoration-none">
                        <div class="p-3 rounded-3 border bg-light h-100 transition-hover text-center">
                            <div class="stat-card-icon stat-icon-blue mx-auto mb-2" style="width: 48px; height: 48px;">
                                <i class="fas fa-users"></i>
                            </div>
                            <h6 class="fw-bold text-dark mb-1"><?= t('menu_farmer_data') ?></h6>
                            <small class="text-muted"><?= t('farmers_subtitle') ?></small>
                        </div>
                    </a>
                </div>

                <div class="col-md-4">
                    <a href="rekap_desa.php" class="text-decoration-none">
                        <div class="p-3 rounded-3 border bg-light h-100 transition-hover text-center">
                            <div class="stat-card-icon stat-icon-amber mx-auto mb-2" style="width: 48px; height: 48px;">
                                <i class="fas fa-chart-pie"></i>
                            </div>
                            <h6 class="fw-bold text-dark mb-1"><?= t('menu_village_recap') ?></h6>
                            <small class="text-muted"><?= t('recap_subtitle') ?></small>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- TABEL PETANI MENUNGGU VERIFIKASI (FULL WIDTH) -->
    <div class="card-modern">
        <div class="card-modern-header">
            <h2 class="card-modern-title">
                <i class="fas fa-user-clock text-warning"></i> Antrean Verifikasi Petani Terbaru
            </h2>
            <a href="verifikasi_akun.php" class="btn btn-sm btn-outline-secondary rounded-pill px-3" style="font-size: 12px; font-weight: 600;">
                Kelola Semua <i class="fas fa-arrow-right ms-1"></i>
            </a>
        </div>
        <div class="table-responsive">
            <table class="table-modern">
                <thead>
                    <tr>
                        <th>Nama Petani</th>
                        <th>Email</th>
                        <th>Tanggal Daftar</th>
                        <th style="text-align: center;">Aksi Cepat</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($pending_list && mysqli_num_rows($pending_list) > 0): ?>
                        <?php while ($p = mysqli_fetch_assoc($pending_list)): ?>
                            <tr>
                                <td><strong class="text-dark"><?= htmlspecialchars($p['nama']) ?></strong></td>
                                <td class="text-secondary"><?= htmlspecialchars($p['email']) ?></td>
                                <td class="text-muted small"><?= date('d M Y, H:i', strtotime($p['created_at'])) ?></td>
                                <td style="text-align: center;">
                                    <a href="verifikasi_akun.php" class="btn-action-icon btn-action-activate" title="Verifikasi Akun">
                                        <i class="fas fa-check"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">
                                <i class="fas fa-check-circle text-success me-1"></i> Tidak ada antrean verifikasi saat ini.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php include '../includes/footer.php'; ?>