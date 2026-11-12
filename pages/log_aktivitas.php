<?php
include '../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ' . $base_url . 'login');
    exit();
}

$query = "SELECT * FROM log_aktivitas ORDER BY created_at DESC LIMIT 150";
$result = mysqli_query($conn, $query);
$total_logs = mysqli_num_rows($result);

include '../includes/navbar.php';
?>

<!-- SIGNATURE DASH HERO HEADER -->
<div class="dash-hero-header">
    <div class="dash-hero-inner">
        <div class="dash-hero-title">
            <h1><i class="fas fa-history"></i> <?= t('log_title') ?></h1>
            <p><?= t('log_subtitle') ?></p>
        </div>
        <div class="dash-hero-actions">
            <a href="export_log.php" class="btn-primary-green">
                <i class="fas fa-file-excel"></i> <?= t('btn_export') ?>
            </a>
            <a href="dashboard_admin.php" class="btn-outline-white">
                <i class="fas fa-arrow-left"></i> Dashboard
            </a>
        </div>
    </div>
</div>

<!-- MAIN PAGE CONTAINER -->
<div class="main-page-container">
    <div class="table-container-modern">
        <!-- Table Toolbar -->
        <div class="table-toolbar">
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <div class="position-relative">
                    <i class="fas fa-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                    <input type="text" id="logTableSearch" class="table-search-input ps-5" placeholder="Cari nama, aktivitas, IP...">
                </div>
            </div>
            <div class="text-muted small">
                Menampilkan <strong id="logCountDisplay" class="text-dark"><?= $total_logs ?></strong> log audit terakhir
            </div>
        </div>

        <?php if ($total_logs > 0): ?>
            <div class="table-responsive">
                <table class="table-modern" id="logTable">
                    <thead>
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th>Waktu & Tanggal</th>
                            <th>Pengguna</th>
                            <th>Email</th>
                            <th>Aktivitas</th>
                            <th>Keterangan</th>
                            <th>Alamat IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1; while ($row = mysqli_fetch_assoc($result)): ?>
                            <?php
                            $aktivitas = $row['aktivitas'];
                            $is_success = strpos($aktivitas, 'Berhasil') !== false;
                            $is_fail = strpos($aktivitas, 'Gagal') !== false || strpos($aktivitas, 'Diblokir') !== false;
                            ?>
                            <tr>
                                <td class="text-muted"><?= $no++ ?></td>
                                <td style="white-space: nowrap;">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="far fa-clock text-muted"></i>
                                        <span><?= date('d M Y, H:i:s', strtotime($row['created_at'])) ?></span>
                                    </div>
                                </td>
                                <td><strong class="text-dark"><?= htmlspecialchars($row['nama'] ?? '-') ?></strong></td>
                                <td class="text-secondary"><?= htmlspecialchars($row['email'] ?? '-') ?></td>
                                <td>
                                    <?php if ($is_success): ?>
                                        <span class="badge-pill-modern badge-status-active">
                                            <i class="fas fa-check-circle"></i> <?= htmlspecialchars($aktivitas) ?>
                                        </span>
                                    <?php elseif ($is_fail): ?>
                                        <span class="badge-pill-modern badge-status-blocked">
                                            <i class="fas fa-times-circle"></i> <?= htmlspecialchars($aktivitas) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge-pill-modern badge-role-admin">
                                            <i class="fas fa-info-circle"></i> <?= htmlspecialchars($aktivitas) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-secondary small"><?= htmlspecialchars($row['keterangan'] ?? '-') ?></td>
                                <td>
                                    <code class="px-2 py-1 bg-light rounded text-dark border small">
                                        <?= htmlspecialchars($row['ip_address'] ?? '127.0.0.1') ?>
                                    </code>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="p-5 text-center text-muted">
                <i class="fas fa-folder-open fa-3x mb-3 text-secondary opacity-50"></i>
                <h5><?= t('log_empty') ?></h5>
                <p class="small">Belum ada riwayat aktivitas sistem yang dicatat.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('logTableSearch');
    const tableRows = document.querySelectorAll('#logTable tbody tr');
    const countDisplay = document.getElementById('logCountDisplay');

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const query = this.value.toLowerCase().trim();
            let visibleCount = 0;

            tableRows.forEach(row => {
                const text = row.innerText.toLowerCase();
                if (text.includes(query)) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            if (countDisplay) countDisplay.innerText = visibleCount;
        });
    }
});
</script>

<?php include '../includes/footer.php'; ?>