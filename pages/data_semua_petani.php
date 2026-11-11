<?php
include '../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'perangkat_desa') {
    header('Location: login.php');
    exit();
}

$query = "SELECT * FROM users WHERE role = 'petani' ORDER BY created_at DESC";
$result = mysqli_query($conn, $query);
$total_petani = mysqli_num_rows($result);

include '../includes/navbar.php';
?>

<!-- SIGNATURE DASH HERO HEADER -->
<div class="dash-hero-header">
    <div class="dash-hero-inner">
        <div class="dash-hero-title">
            <h1><i class="fas fa-users"></i> <?= t('farmers_title') ?></h1>
            <p><?= t('farmers_subtitle') ?></p>
        </div>
        <div class="dash-hero-actions">
            <a href="export_semua_petani.php" class="btn-primary-green">
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
    <div class="table-container-modern">
        <div class="table-toolbar">
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <div class="position-relative">
                    <i class="fas fa-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                    <input type="text" id="farmerTableSearch" class="table-search-input ps-5" placeholder="Cari nama petani, email, no HP...">
                </div>
            </div>
            <div class="text-muted small">
                Total Terdaftar: <strong class="text-dark"><?= $total_petani ?></strong> Petani Warga
            </div>
        </div>

        <?php if ($total_petani > 0): ?>
            <div class="table-responsive">
                <table class="table-modern" id="farmerTable">
                    <thead>
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th>Petani</th>
                            <th>Email</th>
                            <th>Kontak WhatsApp</th>
                            <th>Alamat Domisili</th>
                            <th>Status Akun</th>
                            <th>Tanggal Registrasi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1; while ($row = mysqli_fetch_assoc($result)): ?>
                            <?php $status = $row['status'] ?? 'pending'; ?>
                            <tr>
                                <td class="text-muted"><?= $no++ ?></td>
                                <td>
                                    <div class="user-cell">
                                        <img src="../uploads/foto/<?= htmlspecialchars($row['foto'] ?? 'defaults.png') ?>" alt="Avatar" class="user-avatar-sm" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($row['nama']) ?>&background=059669&color=fff'">
                                        <div>
                                            <strong class="d-block text-dark"><?= htmlspecialchars($row['nama'] ?? '-') ?></strong>
                                            <small class="text-muted"><i class="fas fa-map-marker-alt"></i> Petani Wilayah</small>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-secondary"><?= htmlspecialchars($row['email'] ?? '-') ?></td>
                                <td>
                                    <?php if (!empty($row['no_hp'])): ?>
                                        <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $row['no_hp']) ?>" target="_blank" class="text-success small fw-semibold">
                                            <i class="fab fa-whatsapp me-1"></i><?= htmlspecialchars($row['no_hp']) ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted small">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-secondary small"><?= htmlspecialchars($row['alamat'] ?? '-') ?></td>
                                <td>
                                    <span class="badge-pill-modern badge-status-<?= $status ?>">
                                        <i class="fas fa-<?= $status == 'active' ? 'check-circle' : ($status == 'pending' ? 'clock' : 'ban') ?>"></i>
                                        <?= t('status_' . $status) ?>
                                    </span>
                                </td>
                                <td class="text-muted small"><?= date('d M Y', strtotime($row['created_at'])) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="p-5 text-center text-muted">
                <i class="fas fa-users-slash fa-3x mb-3 text-secondary opacity-50"></i>
                <h5><?= t('farmers_empty') ?></h5>
                <p class="small">Belum ada data petani warga yang tersimpan di sistem.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('farmerTableSearch');
    const tableRows = document.querySelectorAll('#farmerTable tbody tr');

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