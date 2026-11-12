<?php
include '../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'petani') {
    header('Location: ' . $base_url . 'login');
    exit();
}

$user_id = $_SESSION['user_id'];

$query = "SELECT * FROM lahan WHERE user_id = '$user_id' ORDER BY id DESC";
$result = mysqli_query($conn, $query);
$total_lahan = mysqli_num_rows($result);

include '../includes/navbar.php';
?>

<!-- SIGNATURE DASH HERO HEADER -->
<div class="dash-hero-header">
    <div class="dash-hero-inner">
        <div class="dash-hero-title">
            <h1><i class="fas fa-layer-group"></i> <?= t('land_title_data') ?></h1>
            <p><?= t('land_data_subtitle') ?></p>
        </div>
        <div class="dash-hero-actions">
            <a href="tambah_lahan.php" class="btn-primary-green">
                <i class="fas fa-plus-circle"></i> <?= t('land_add_now') ?>
            </a>
            <a href="export_lahan.php" class="btn-outline-white">
                <i class="fas fa-file-excel"></i> <?= t('btn_export') ?>
            </a>
            <a href="dashboard_petani.php" class="btn-outline-white">
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
                    <input type="text" id="lahanTableSearch" class="table-search-input ps-5" placeholder="Cari nama lahan, varietas, lokasi...">
                </div>
            </div>
            <div class="text-muted small">
                Total Sawah: <strong class="text-dark"><?= $total_lahan ?></strong> Petak Lahan
            </div>
        </div>

        <?php if ($total_lahan > 0): ?>
            <div class="table-responsive">
                <table class="table-modern" id="lahanTable">
                    <thead>
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th><?= t('land_name') ?></th>
                            <th><?= t('land_location') ?></th>
                            <th><?= t('land_area') ?> (m²)</th>
                            <th><?= t('land_rice_type') ?></th>
                            <th><?= t('land_plant_date') ?></th>
                            <th><?= t('land_harvest_estimate') ?></th>
                            <th><?= t('status') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1; while ($row = mysqli_fetch_assoc($result)): ?>
                            <?php
                            $status = $row['status'] ?? 'tanam';
                            if ($status == 'tanam') {
                                $badge_cls = 'badge-status-active';
                                $label = t('status_planting');
                            } elseif ($status == 'tumbuh') {
                                $badge_cls = 'badge-role-admin';
                                $label = t('status_growing');
                            } elseif ($status == 'panen') {
                                $badge_cls = 'badge-status-pending';
                                $label = t('status_harvest');
                            } else {
                                $badge_cls = 'badge-role-desa';
                                $label = t('status_finished');
                            }
                            ?>
                            <tr>
                                <td class="text-muted"><?= $no++ ?></td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="stat-card-icon stat-icon-emerald" style="width: 34px; height: 34px; font-size: 0.95rem;">
                                            <i class="fas fa-seedling"></i>
                                        </div>
                                        <div>
                                            <strong class="d-block text-dark"><?= htmlspecialchars($row['nama_lahan']) ?></strong>
                                            <?php if (!empty($row['catatan'])): ?>
                                                <small class="text-muted"><?= htmlspecialchars(substr($row['catatan'], 0, 35)) ?>...</small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-secondary small"><?= htmlspecialchars($row['lokasi']) ?></td>
                                <td><strong class="text-dark"><?= number_format($row['luas'], 0) ?></strong> <small class="text-muted">m²</small></td>
                                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($row['jenis_padi'] ?? $row['varietas'] ?? 'IR64') ?></span></td>
                                <td class="text-muted small"><?= date('d M Y', strtotime($row['tgl_tanam'])) ?></td>
                                <td class="text-muted small" style="white-space: nowrap;">
                                    <i class="far fa-calendar-alt text-warning me-1"></i>
                                    <strong><?= date('d M Y', strtotime($row['tgl_panen_perkiraan'])) ?></strong>
                                </td>
                                <td>
                                    <span class="badge-pill-modern <?= $badge_cls ?>">
                                        <?= $label ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="p-5 text-center text-muted">
                <i class="fas fa-seedling fa-3x mb-3 text-secondary opacity-50"></i>
                <h5><?= t('land_data_empty') ?></h5>
                <p class="small text-muted mb-3">Anda belum menambahkan petak lahan sawah.</p>
                <a href="tambah_lahan.php" class="btn-primary-green">
                    <i class="fas fa-plus-circle"></i> <?= t('land_add_now') ?>
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('lahanTableSearch');
    const tableRows = document.querySelectorAll('#lahanTable tbody tr');

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const query = this.value.toLowerCase().trim();
            tableRows.forEach(row => {
                const text = row.innerText.toLowerCase();
                row.style.display = text.includes(query) ? '' : 'none';
            });
        });
    }
});
</script>

<?php include '../includes/footer.php'; ?>