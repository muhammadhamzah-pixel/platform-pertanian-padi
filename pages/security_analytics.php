<?php
include '../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ' . $base_url . 'login');
    exit();
}

// ===== STATISTIK UTAMA =====
$total_log = mysqli_fetch_assoc(mysqli_query($conn, 
    "SELECT COUNT(*) AS total FROM log_aktivitas WHERE DATE(created_at) = CURDATE()"))['total'];

$login_hari_ini = mysqli_fetch_assoc(mysqli_query($conn, 
    "SELECT COUNT(*) AS total FROM log_aktivitas 
     WHERE aktivitas = 'Login Berhasil' AND DATE(created_at) = CURDATE()"))['total'];

$gagal_hari_ini = mysqli_fetch_assoc(mysqli_query($conn, 
    "SELECT COUNT(*) AS total FROM log_aktivitas 
     WHERE aktivitas = 'Login Gagal' AND DATE(created_at) = CURDATE()"))['total'];

$bot_count = mysqli_fetch_assoc(mysqli_query($conn, 
    "SELECT COUNT(*) AS total FROM log_aktivitas WHERE is_bot = 1"))['total'];

$manusia_count = mysqli_fetch_assoc(mysqli_query($conn, 
    "SELECT COUNT(*) AS total FROM log_aktivitas WHERE is_bot = 0"))['total'];

$negara_unik = mysqli_fetch_assoc(mysqli_query($conn, 
    "SELECT COUNT(DISTINCT country) AS total FROM log_aktivitas 
     WHERE country IS NOT NULL AND country NOT IN ('Localhost', 'Unknown')"))['total'];

$user_unik = mysqli_fetch_assoc(mysqli_query($conn, 
    "SELECT COUNT(DISTINCT user_id) AS total FROM log_aktivitas 
     WHERE user_id IS NOT NULL"))['total'];

$ip_unik = mysqli_fetch_assoc(mysqli_query($conn, 
    "SELECT COUNT(DISTINCT ip_address) AS total FROM log_aktivitas"))['total'];

// ===== DATA GRAFIK 7 HARI =====
$data_7hari = [];
for ($i = 6; $i >= 0; $i--) {
    $tanggal = date('Y-m-d', strtotime("-$i days"));
    $label = date('d M', strtotime("-$i days"));
    
    $login = mysqli_fetch_assoc(mysqli_query($conn, 
        "SELECT COUNT(*) AS total FROM log_aktivitas 
         WHERE aktivitas = 'Login Berhasil' AND DATE(created_at) = '$tanggal'"))['total'];
    
    $gagal = mysqli_fetch_assoc(mysqli_query($conn, 
        "SELECT COUNT(*) AS total FROM log_aktivitas 
         WHERE aktivitas = 'Login Gagal' AND DATE(created_at) = '$tanggal'"))['total'];
    
    $data_7hari[] = ['label' => $label, 'login' => $login, 'gagal' => $gagal];
}

// ===== USER LOGIN HARI INI =====
$user_login_query = "SELECT DISTINCT u.id, u.nama, u.email, u.role, 
                            MAX(l.created_at) AS login_terakhir, 
                            l.ip_address, l.country, l.country_code
                     FROM log_aktivitas l
                     JOIN users u ON l.user_id = u.id
                     WHERE l.aktivitas = 'Login Berhasil' 
                       AND DATE(l.created_at) = CURDATE()
                     GROUP BY u.id, u.nama, u.email, u.role, l.ip_address, l.country, l.country_code
                     ORDER BY login_terakhir DESC";
$user_login_result = mysqli_query($conn, $user_login_query);

// ===== USER PALING AKTIF =====
$user_aktif_query = "SELECT nama, COUNT(*) AS total 
                     FROM log_aktivitas 
                     WHERE nama IS NOT NULL AND aktivitas = 'Login Berhasil'
                     GROUP BY nama 
                     ORDER BY total DESC 
                     LIMIT 5";
$user_aktif_result = mysqli_query($conn, $user_aktif_query);

// ===== IP SERING AKSES =====
$ip_akses_query = "SELECT ip_address, country, country_code, COUNT(*) AS total 
                   FROM log_aktivitas 
                   GROUP BY ip_address, country, country_code
                   ORDER BY total DESC 
                   LIMIT 5";
$ip_akses_result = mysqli_query($conn, $ip_akses_query);

// ===== DATA PETA =====
$peta_query = "SELECT country, country_code, COUNT(*) AS total 
               FROM log_aktivitas 
               WHERE country IS NOT NULL 
               AND country NOT IN ('Localhost', 'Unknown')
               GROUP BY country, country_code
               ORDER BY total DESC LIMIT 20";
$peta_result = mysqli_query($conn, $peta_query);
$peta_data = [];
while ($row = mysqli_fetch_assoc($peta_result)) {
    $peta_data[] = $row;
}

// ===== TOP 10 IP ATTACKER =====
$ip_query = "SELECT ip_address, country, country_code, COUNT(*) AS total 
             FROM log_aktivitas 
             WHERE aktivitas LIKE '%Gagal%' OR is_bot = 1
             GROUP BY ip_address, country, country_code
             ORDER BY total DESC LIMIT 10";
$ip_result = mysqli_query($conn, $ip_query);

// ===== TOP 10 BOT =====
$bot_query = "SELECT user_agent, COUNT(*) AS total 
              FROM log_aktivitas 
              WHERE is_bot = 1
              GROUP BY user_agent
              ORDER BY total DESC LIMIT 10";
$bot_result = mysqli_query($conn, $bot_query);

include '../includes/navbar.php';
?>

<!-- Leaflet.js -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- SIGNATURE DASH HERO HEADER -->
<div class="dash-hero-header">
    <div class="dash-hero-inner">
        <div class="dash-hero-title">
            <h1><i class="fas fa-shield-virus"></i> <?= t('security_title') ?></h1>
            <p><?= t('security_subtitle') ?></p>
        </div>
        <div class="dash-hero-actions">
            <a href="user_terblokir.php" class="btn-primary-green">
                <i class="fas fa-user-lock"></i> User Terblokir
            </a>
            <a href="log_aktivitas.php" class="btn-outline-white">
                <i class="fas fa-history"></i> Log Aktivitas
            </a>
            <a href="dashboard_admin.php" class="btn-outline-white">
                <i class="fas fa-arrow-left"></i> Dashboard
            </a>
        </div>
    </div>
</div>

<!-- MAIN PAGE CONTAINER -->
<div class="main-page-container">

    <!-- STATISTIK UTAMA GRID -->
    <div class="stats-grid mb-4">
        <div class="stat-card">
            <div class="stat-card-header">
                <span class="stat-card-title"><?= t('security_log_today') ?></span>
                <div class="stat-card-icon stat-icon-emerald"><i class="fas fa-calendar-day"></i></div>
            </div>
            <div class="stat-card-value"><?= number_format($total_log) ?></div>
            <div class="stat-card-footer text-muted">Aktivitas Hari Ini</div>
        </div>

        <div class="stat-card">
            <div class="stat-card-header">
                <span class="stat-card-title"><?= t('security_login_success') ?></span>
                <div class="stat-card-icon stat-icon-emerald"><i class="fas fa-check-circle"></i></div>
            </div>
            <div class="stat-card-value text-success"><?= number_format($login_hari_ini) ?></div>
            <div class="stat-card-footer text-muted">Akses Sah Terverifikasi</div>
        </div>

        <div class="stat-card">
            <div class="stat-card-header">
                <span class="stat-card-title"><?= t('security_login_failed') ?></span>
                <div class="stat-card-icon stat-icon-rose"><i class="fas fa-times-circle"></i></div>
            </div>
            <div class="stat-card-value text-danger"><?= number_format($gagal_hari_ini) ?></div>
            <div class="stat-card-footer text-muted">Percobaan Gagal / Salah Sandi</div>
        </div>

        <div class="stat-card">
            <div class="stat-card-header">
                <span class="stat-card-title"><?= t('security_bot') ?></span>
                <div class="stat-card-icon stat-icon-rose"><i class="fas fa-robot"></i></div>
            </div>
            <div class="stat-card-value text-danger"><?= number_format($bot_count) ?></div>
            <div class="stat-card-footer text-muted">Aktivitas Bot Terdeteksi</div>
        </div>

        <div class="stat-card">
            <div class="stat-card-header">
                <span class="stat-card-title"><?= t('security_unique_user') ?></span>
                <div class="stat-card-icon stat-icon-blue"><i class="fas fa-user-check"></i></div>
            </div>
            <div class="stat-card-value"><?= number_format($user_unik) ?></div>
            <div class="stat-card-footer text-muted">Pengguna Aktif Terdata</div>
        </div>

        <div class="stat-card">
            <div class="stat-card-header">
                <span class="stat-card-title"><?= t('security_unique_ip') ?></span>
                <div class="stat-card-icon stat-icon-amber"><i class="fas fa-network-wired"></i></div>
            </div>
            <div class="stat-card-value"><?= number_format($ip_unik) ?></div>
            <div class="stat-card-footer text-muted">Alamat IP Berbeda</div>
        </div>
    </div>

    <!-- GRAFIK 7 HARI -->
    <div class="card-modern mb-4">
        <div class="card-modern-header">
            <h2 class="card-modern-title"><i class="fas fa-chart-line text-success"></i> <?= t('security_chart_7days') ?></h2>
            <span class="badge bg-light text-muted border">Tren 1 Minggu Terakhir</span>
        </div>
        <div class="card-modern-body">
            <div style="position: relative; width: 100%; height: 260px;">
                <canvas id="chartAktivitas"></canvas>
            </div>
        </div>
    </div>

    <!-- USER LOGIN HARI INI -->
    <div class="card-modern mb-4">
        <div class="card-modern-header">
            <h2 class="card-modern-title">
                <i class="fas fa-users text-primary"></i> <?= t('security_user_login_today') ?>
            </h2>
            <span class="badge bg-light text-muted border">Hari Ini</span>
        </div>
        <?php if (mysqli_num_rows($user_login_result) > 0): ?>
            <div class="table-responsive">
                <table class="table-modern">
                    <thead>
                        <tr>
                            <th style="width: 45px;">#</th>
                            <th><?= t('log_col_name') ?></th>
                            <th><?= t('log_col_email') ?></th>
                            <th><?= t('manage_user_col_role') ?></th>
                            <th><?= t('security_last_login') ?></th>
                            <th><?= t('log_col_ip') ?></th>
                            <th><?= t('security_country') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1; while ($row = mysqli_fetch_assoc($user_login_result)): ?>
                            <?php 
                            $role_badge = ($row['role'] == 'admin') ? 'badge-role-admin' : (($row['role'] == 'petani') ? 'badge-role-petani' : 'badge-role-desa');
                            ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><strong class="text-dark"><?= htmlspecialchars($row['nama']) ?></strong></td>
                                <td class="text-secondary small"><?= htmlspecialchars($row['email']) ?></td>
                                <td>
                                    <span class="badge-pill-modern <?= $role_badge ?>">
                                        <?= t('role_' . $row['role']) ?>
                                    </span>
                                </td>
                                <td class="text-secondary small" style="white-space: nowrap;">
                                    <i class="far fa-clock me-1 text-muted"></i> <?= date('H:i:s', strtotime($row['login_terakhir'])) ?>
                                </td>
                                <td><code><?= htmlspecialchars($row['ip_address']) ?></code></td>
                                <td>
                                    <span class="d-inline-flex align-items-center gap-1 small">
                                        <?= get_country_flag($row['country_code'] ?? 'XX') ?> 
                                        <?= htmlspecialchars($row['country'] ?? '-') ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="p-4 text-center text-muted small">
                <i class="fas fa-info-circle mb-2 fs-5 d-block text-secondary"></i>
                <?= t('security_no_user_login') ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- PETA DUNIA -->
    <div class="card-modern mb-4">
        <div class="card-modern-header">
            <h2 class="card-modern-title">
                <i class="fas fa-globe-americas text-emerald"></i> <?= t('security_map_title') ?>
            </h2>
            <span class="badge bg-light text-muted border">Geolokasi IP Akses</span>
        </div>
        <div class="card-modern-body p-0">
            <div id="map" style="height: 380px; width: 100%; border-radius: 0 0 16px 16px; z-index: 1;"></div>
            <?php if (empty($peta_data)): ?>
                <div class="p-3 text-center text-muted small bg-light">
                    ⚠️ <?= t('security_no_map_data') ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- 3 KOLOM: TOP USER + TOP IP + DONUT -->
    <div class="row g-4 mb-4">
        <div class="col-lg-4 col-md-6">
            <div class="card-modern h-100">
                <div class="card-modern-header">
                    <h2 class="card-modern-title">
                        <i class="fas fa-trophy text-warning"></i> <?= t('security_top_user') ?>
                    </h2>
                    <span class="badge bg-light text-muted border">Top 5</span>
                </div>
                <div class="table-responsive">
                    <table class="table-modern">
                        <thead>
                            <tr>
                                <th style="width: 40px;">#</th>
                                <th><?= t('log_col_name') ?></th>
                                <th class="text-end"><?= t('export_total') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; while ($row = mysqli_fetch_assoc($user_aktif_result)): ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><strong class="text-dark"><?= htmlspecialchars($row['nama']) ?></strong></td>
                                    <td class="text-end"><span class="badge bg-success-subtle text-success border border-success-subtle fw-semibold"><?= $row['total'] ?>x</span></td>
                                </tr>
                            <?php endwhile; ?>
                            <?php if ($no == 1): ?>
                                <tr><td colspan="3" class="text-center text-muted py-3 small"><?= t('security_no_data') ?></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-md-6">
            <div class="card-modern h-100">
                <div class="card-modern-header">
                    <h2 class="card-modern-title">
                        <i class="fas fa-network-wired text-info"></i> <?= t('security_top_ip') ?>
                    </h2>
                    <span class="badge bg-light text-muted border">Top 5</span>
                </div>
                <div class="table-responsive">
                    <table class="table-modern">
                        <thead>
                            <tr>
                                <th style="width: 40px;">#</th>
                                <th><?= t('log_col_ip') ?></th>
                                <th><?= t('security_country') ?></th>
                                <th class="text-end"><?= t('export_total') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; while ($row = mysqli_fetch_assoc($ip_akses_result)): ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><code><?= htmlspecialchars($row['ip_address']) ?></code></td>
                                    <td>
                                        <span class="d-inline-flex align-items-center gap-1 small">
                                            <?= get_country_flag($row['country_code'] ?? 'XX') ?> 
                                            <?= htmlspecialchars($row['country'] ?? '-') ?>
                                        </span>
                                    </td>
                                    <td class="text-end"><span class="badge bg-primary-subtle text-primary border border-primary-subtle fw-semibold"><?= $row['total'] ?>x</span></td>
                                </tr>
                            <?php endwhile; ?>
                            <?php if ($no == 1): ?>
                                <tr><td colspan="4" class="text-center text-muted py-3 small"><?= t('security_no_data') ?></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-md-12">
            <div class="card-modern h-100">
                <div class="card-modern-header">
                    <h2 class="card-modern-title">
                        <i class="fas fa-chart-pie text-danger"></i> <?= t('security_login_vs_failed') ?>
                    </h2>
                    <span class="badge bg-light text-muted border">Rasio</span>
                </div>
                <div class="card-modern-body d-flex flex-column align-items-center justify-content-center">
                    <div style="max-width: 190px; width: 100%; margin: 0 auto;">
                        <canvas id="chartPie"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- TOP 10 IP ATTACKER & BOT -->
    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="card-modern h-100">
                <div class="card-modern-header">
                    <h2 class="card-modern-title">
                        <i class="fas fa-fire text-danger"></i> <?= t('security_top_ip_attacker') ?>
                    </h2>
                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Insiden Gagal</span>
                </div>
                <?php if (mysqli_num_rows($ip_result) > 0): ?>
                    <div class="table-responsive">
                        <table class="table-modern">
                            <thead>
                                <tr>
                                    <th style="width: 40px;">#</th>
                                    <th><?= t('log_col_ip') ?></th>
                                    <th><?= t('security_country') ?></th>
                                    <th class="text-end"><?= t('export_total') ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = 1; while ($row = mysqli_fetch_assoc($ip_result)): ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td><code><?= htmlspecialchars($row['ip_address']) ?></code></td>
                                        <td>
                                            <span class="d-inline-flex align-items-center gap-1 small">
                                                <?= get_country_flag($row['country_code'] ?? 'XX') ?> 
                                                <?= htmlspecialchars($row['country'] ?? '-') ?>
                                            </span>
                                        </td>
                                        <td class="text-end"><span class="badge bg-danger text-white fw-bold"><?= $row['total'] ?>x</span></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="p-4 text-center text-muted small">
                        <i class="fas fa-shield-alt text-success fs-5 mb-2 d-block"></i>
                        <?= t('security_no_data') ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card-modern h-100">
                <div class="card-modern-header">
                    <h2 class="card-modern-title">
                        <i class="fas fa-robot text-secondary"></i> <?= t('security_top_bot') ?>
                    </h2>
                    <span class="badge bg-light text-muted border">Scanner & Bot</span>
                </div>
                <?php if (mysqli_num_rows($bot_result) > 0): ?>
                    <div class="table-responsive">
                        <table class="table-modern">
                            <thead>
                                <tr>
                                    <th style="width: 40px;">#</th>
                                    <th>User Agent</th>
                                    <th class="text-end"><?= t('export_total') ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = 1; while ($row = mysqli_fetch_assoc($bot_result)): ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td><small class="text-muted font-monospace"><?= htmlspecialchars(substr($row['user_agent'], 0, 45)) ?>...</small></td>
                                        <td class="text-end"><span class="badge bg-secondary text-white fw-semibold"><?= $row['total'] ?>x</span></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="p-4 text-center text-muted small">
                        <i class="fas fa-check-circle text-success fs-5 mb-2 d-block"></i>
                        <?= t('security_no_bot') ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="mb-4">
        <a href="dashboard_admin.php" class="btn btn-outline-secondary rounded-pill px-4 fw-semibold" style="font-size: 13px;">
            <i class="fas fa-arrow-left me-1"></i> <?= t('btn_back') ?>
        </a>
    </div>

</div> <!-- Close main-page-container -->

<script>
    // ===== GRAFIK 7 HARI =====
    const ctxAktivitas = document.getElementById('chartAktivitas').getContext('2d');
    new Chart(ctxAktivitas, {
        type: 'line',
        data: {
            labels: <?= json_encode(array_column($data_7hari, 'label')) ?>,
            datasets: [
                {
                    label: '<?= t('security_login_success') ?>',
                    data: <?= json_encode(array_column($data_7hari, 'login')) ?>,
                    borderColor: '#2e7d32',
                    backgroundColor: 'rgba(46, 125, 50, 0.1)',
                    tension: 0.3, fill: true
                },
                {
                    label: '<?= t('security_login_failed') ?>',
                    data: <?= json_encode(array_column($data_7hari, 'gagal')) ?>,
                    borderColor: '#dc3545',
                    backgroundColor: 'rgba(220, 53, 69, 0.1)',
                    tension: 0.3, fill: true
                }
            ]
        },
        options: { 
            responsive: true, 
            maintainAspectRatio: false,
            plugins: { legend: { position: 'top' } } 
        }
    });

    // ===== PIE CHART =====
    const ctxPie = document.getElementById('chartPie').getContext('2d');
    new Chart(ctxPie, {
        type: 'doughnut',
        data: {
            labels: ['<?= t('security_login_success') ?>', '<?= t('security_login_failed') ?>'],
            datasets: [{
                data: [<?= $login_hari_ini ?>, <?= $gagal_hari_ini ?>],
                backgroundColor: ['#2e7d32', '#dc3545']
            }]
        },
        options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
    });

    // ===== PETA DUNIA =====
    const map = L.map('map').setView([20, 0], 2);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap', maxZoom: 18
    }).addTo(map);

    const zoomControl = document.querySelector('.leaflet-control-zoom');
    if (zoomControl) {
        const zoomInfo = document.createElement('div');
        zoomInfo.id = 'zoomInfo';
        zoomControl.parentNode.appendChild(zoomInfo);
        
        function updateZoom() {
            const zoom = map.getZoom();
            const persen = Math.round((zoom / 18) * 100);
            document.getElementById('zoomInfo').textContent = persen + '%';
        }
        
        map.on('zoomend', updateZoom);
        updateZoom();
    }

    const petaData = <?= json_encode($peta_data) ?>;
    const negaraCoords = {
        'Indonesia': [-0.789, 113.921], 'United States': [37.0902, -95.7129],
        'China': [35.8617, 104.1954], 'Russia': [61.5240, 105.3188],
        'India': [20.5937, 78.9629], 'Brazil': [-14.235, -51.9253],
        'Germany': [51.1657, 10.4515], 'Japan': [36.2048, 138.2529],
        'Singapore': [1.3521, 103.8198], 'Malaysia': [4.2105, 101.9758],
        'Australia': [-25.2744, 133.7751], 'United Kingdom': [55.3781, -3.4360],
        'South Korea': [35.9078, 127.7669], 'Vietnam': [14.0583, 108.2772],
        'Thailand': [15.8700, 100.9925]
    };
    
    petaData.forEach(item => {
        const coords = negaraCoords[item.country];
        if (coords) {
            const radius = Math.max(5, Math.min(25, item.total * 2));
            L.circleMarker(coords, {
                radius: radius, fillColor: '#dc3545',
                color: '#fff', weight: 2, opacity: 1, fillOpacity: 0.7
            }).addTo(map).bindPopup(`<strong>${item.country}</strong><br><?= t('export_total') ?>: ${item.total} log`);
        }
    });
</script>

<?php include '../includes/footer.php'; ?>