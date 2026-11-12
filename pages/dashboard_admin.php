<?php
include '../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ' . $base_url . 'login');
    exit();
}

// Ambil foto profil
$foto_query = "SELECT foto FROM users WHERE id = '" . $_SESSION['user_id'] . "'";
$foto_result = mysqli_query($conn, $foto_query);
$foto_data = mysqli_fetch_assoc($foto_result);
$foto = $foto_data['foto'] ?? 'defaults.png';

// Ambil data statistik
$total_user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM users"))['total'] ?? 0;
$total_petani = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM users WHERE role='petani'"))['total'] ?? 0;
$total_desa = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM users WHERE role='perangkat_desa'"))['total'] ?? 0;
$total_lahan = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM lahan"))['total'] ?? 0;
$total_blocked = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM users WHERE status='blocked'"))['total'] ?? 0;

// Ambil 5 aktivitas terbaru
$recent_logs = mysqli_query($conn, "SELECT * FROM log_aktivitas ORDER BY created_at DESC LIMIT 5");

include '../includes/navbar.php';
?>

<!-- SIGNATURE DASH HERO HEADER -->
<div class="dash-hero-header">
    <div class="dash-hero-inner">
        <div class="dash-hero-title">
            <h1><i class="fas fa-solar-panel"></i> <?= t('dashboard_admin') ?></h1>
            <p><?= t('welcome_back') ?>, <strong><?= htmlspecialchars($_SESSION['nama']) ?></strong>! Pantau performa platform, audit sistem, dan manajemen pengguna desa secara real-time.</p>
        </div>
        <div class="dash-hero-actions">
            <a href="<?= $base_url ?>konfigurasi_ai" class="btn-primary-green">
                <i class="fas fa-microchip"></i> Konfigurasi AI
            </a>
            <a href="<?= $base_url ?>kelola_user" class="btn-outline-white">
                <i class="fas fa-user-plus"></i> Kelola User
            </a>
            <a href="<?= $base_url ?>security_analytics" class="btn-outline-white">
                <i class="fas fa-shield-alt"></i> Security Analytics
            </a>
            <a href="<?= $base_url ?>backup_data" class="btn-outline-white">
                <i class="fas fa-database"></i> Backup Data
            </a>
        </div>
    </div>
</div>

<!-- DASHBOARD CONTAINER -->
<div class="dashboard-container">

    <!-- FLOATING STATS GRID (DASH UI STYLE) -->
    <div class="stats-grid">
        <!-- Stat Card 1 -->
        <div class="stat-card">
            <div class="stat-card-header">
                <span class="stat-card-title"><?= t('menu_manage_user') ?></span>
                <div class="stat-card-icon stat-icon-emerald">
                    <i class="fas fa-users"></i>
                </div>
            </div>
            <div class="stat-card-value"><?= number_format($total_user) ?></div>
            <div class="stat-card-footer">
                <span class="stat-trend-up"><i class="fas fa-check-circle"></i> Terdaftar</span>
                <span>semua role</span>
            </div>
        </div>

        <!-- Stat Card 2 -->
        <div class="stat-card">
            <div class="stat-card-header">
                <span class="stat-card-title">Petani Aktif</span>
                <div class="stat-card-icon stat-icon-amber">
                    <i class="fas fa-seedling"></i>
                </div>
            </div>
            <div class="stat-card-value"><?= number_format($total_petani) ?></div>
            <div class="stat-card-footer">
                <span class="stat-trend-up"><i class="fas fa-user"></i> Petani</span>
                <span>terverifikasi</span>
            </div>
        </div>

        <!-- Stat Card 3 -->
        <div class="stat-card">
            <div class="stat-card-header">
                <span class="stat-card-title">Perangkat Desa</span>
                <div class="stat-card-icon stat-icon-blue">
                    <i class="fas fa-landmark"></i>
                </div>
            </div>
            <div class="stat-card-value"><?= number_format($total_desa) ?></div>
            <div class="stat-card-footer">
                <span class="stat-trend-up"><i class="fas fa-building"></i> Aparatur</span>
                <span>pengelola wilayah</span>
            </div>
        </div>

        <!-- Stat Card 4 -->
        <div class="stat-card">
            <div class="stat-card-header">
                <span class="stat-card-title"><?= t('recap_total_land') ?></span>
                <div class="stat-card-icon stat-icon-emerald">
                    <i class="fas fa-layer-group"></i>
                </div>
            </div>
            <div class="stat-card-value"><?= number_format($total_lahan) ?></div>
            <div class="stat-card-footer">
                <span class="stat-trend-up"><i class="fas fa-map"></i> Petak</span>
                <span>monitoring sawah</span>
            </div>
        </div>
    </div>

    <!-- QUICK MANAGEMENT SHORTCUTS (FULL WIDTH) -->
    <div class="card-modern mb-4">
        <div class="card-modern-header">
            <h2 class="card-modern-title">
                <i class="fas fa-th-large"></i> Menu Akses Cepat Administrator
            </h2>
            <span class="badge bg-light text-secondary border">Pusat Kendali</span>
        </div>
        <div class="card-modern-body">
            <div class="row g-3">
                <div class="col-6 col-sm-4 col-lg-2">
                    <a href="kelola_user.php" class="text-decoration-none">
                        <div class="p-3 rounded-3 border bg-light h-100 transition-hover text-center">
                            <div class="stat-card-icon stat-icon-blue mx-auto mb-2" style="width: 48px; height: 48px;">
                                <i class="fas fa-users-cog"></i>
                            </div>
                            <h6 class="fw-bold text-dark mb-1">Kelola Pengguna</h6>
                            <small class="text-muted">Aktivasi & edit role user</small>
                        </div>
                    </a>
                </div>
                <div class="col-6 col-sm-4 col-lg-2">
                    <a href="security_analytics.php" class="text-decoration-none">
                        <div class="p-3 rounded-3 border bg-light h-100 transition-hover text-center">
                            <div class="stat-card-icon stat-icon-rose mx-auto mb-2" style="width: 48px; height: 48px;">
                                <i class="fas fa-shield-virus"></i>
                            </div>
                            <h6 class="fw-bold text-dark mb-1">Keamanan Sistem</h6>
                            <small class="text-muted">Deteksi serangan & log IP</small>
                        </div>
                    </a>
                </div>
                <div class="col-6 col-sm-4 col-lg-2">
                    <a href="setting_desa.php" class="text-decoration-none">
                        <div class="p-3 rounded-3 border bg-light h-100 transition-hover text-center">
                            <div class="stat-card-icon stat-icon-emerald mx-auto mb-2" style="width: 48px; height: 48px;">
                                <i class="fas fa-map-marked-alt"></i>
                            </div>
                            <h6 class="fw-bold text-dark mb-1">Setting Desa</h6>
                            <small class="text-muted">Wilayah & batas target</small>
                        </div>
                    </a>
                </div>
                <div class="col-6 col-sm-4 col-lg-2">
                    <a href="backup_data.php" class="text-decoration-none">
                        <div class="p-3 rounded-3 border bg-light h-100 transition-hover text-center">
                            <div class="stat-card-icon stat-icon-amber mx-auto mb-2" style="width: 48px; height: 48px;">
                                <i class="fas fa-database"></i>
                            </div>
                            <h6 class="fw-bold text-dark mb-1">Backup Database</h6>
                            <small class="text-muted">Download arsip SQL data</small>
                        </div>
                    </a>
                </div>
                <div class="col-6 col-sm-4 col-lg-2">
                    <a href="log_aktivitas.php" class="text-decoration-none">
                        <div class="p-3 rounded-3 border bg-light h-100 transition-hover text-center">
                            <div class="stat-card-icon stat-icon-blue mx-auto mb-2" style="width: 48px; height: 48px;">
                                <i class="fas fa-history"></i>
                            </div>
                            <h6 class="fw-bold text-dark mb-1">Log Aktivitas</h6>
                            <small class="text-muted">Jejak audit pengguna</small>
                        </div>
                    </a>
                </div>
                <div class="col-6 col-sm-4 col-lg-2">
                    <a href="user_terblokir.php" class="text-decoration-none">
                        <div class="p-3 rounded-3 border bg-light h-100 transition-hover text-center">
                            <div class="stat-card-icon stat-icon-rose mx-auto mb-2" style="width: 48px; height: 48px;">
                                <i class="fas fa-user-lock"></i>
                            </div>
                            <h6 class="fw-bold text-dark mb-1">User Terblokir</h6>
                            <small class="text-muted"><?= $total_blocked ?> akun terblokir</small>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- RECENT ACTIVITY TABLE PREVIEW (FULL WIDTH) -->
    <div class="card-modern">
        <div class="card-modern-header">
            <h2 class="card-modern-title">
                <i class="fas fa-stream"></i> 5 Aktivitas Terakhir Sistem
            </h2>
            <a href="log_aktivitas.php" class="btn btn-sm btn-outline-secondary rounded-pill px-3" style="font-size: 12px; font-weight: 600;">
                Lihat Semua <i class="fas fa-chevron-right ms-1"></i>
            </a>
        </div>
        <div class="table-responsive">
            <table class="table-modern">
                <thead>
                    <tr>
                        <th>Pengguna</th>
                        <th>Aktivitas</th>
                        <th>Keterangan</th>
                        <th>Waktu</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($recent_logs && mysqli_num_rows($recent_logs) > 0): ?>
                        <?php while ($log = mysqli_fetch_assoc($recent_logs)): ?>
                            <tr>
                                <td>
                                    <strong class="d-block text-dark"><?= htmlspecialchars($log['nama'] ?? 'Sistem') ?></strong>
                                    <small class="text-muted"><?= htmlspecialchars($log['email'] ?? '-') ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border" style="font-size: 11px; font-weight: 600;">
                                        <?= htmlspecialchars($log['aktivitas'] ?? '-') ?>
                                    </span>
                                </td>
                                <td class="text-secondary small">
                                    <?= htmlspecialchars($log['keterangan'] ?? '-') ?>
                                </td>
                                <td class="text-muted small" style="white-space: nowrap;">
                                    <i class="far fa-clock me-1"></i> <?= htmlspecialchars($log['created_at'] ?? '-') ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">Belum ada aktivitas tercatat.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>