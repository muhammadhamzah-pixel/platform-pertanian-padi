<?php
include '../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'perangkat_desa') {
    header('Location: login.php');
    exit();
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && isset($_POST['user_id'])) {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error = t('err_csrf');
    } else {
        $user_id = intval($_POST['user_id']);
        $action = mysqli_real_escape_string($conn, $_POST['action']);
        
        if ($action == 'activate') {
            $query = "UPDATE users SET status = 'active' WHERE id = '$user_id' AND status = 'pending'";
            if (mysqli_query($conn, $query)) {
                $message = '✅ ' . t('verify_account_activated');
                catat_log($conn, $_SESSION['user_id'], $_SESSION['email'], $_SESSION['nama'], 'Verifikasi Akun', "Aktifkan user ID: $user_id");
                regenerate_csrf_token();
            } else {
                $error = '❌ ' . t('failed') . ': ' . mysqli_error($conn);
            }
        } elseif ($action == 'reject') {
            $query = "UPDATE users SET status = 'blocked' WHERE id = '$user_id' AND status = 'pending'";
            if (mysqli_query($conn, $query)) {
                $message = '✅ ' . t('verify_account_rejected');
                catat_log($conn, $_SESSION['user_id'], $_SESSION['email'], $_SESSION['nama'], 'Verifikasi Akun', "Tolak user ID: $user_id");
                regenerate_csrf_token();
            } else {
                $error = '❌ ' . t('failed') . ': ' . mysqli_error($conn);
            }
        }
    }
}

$query = "SELECT * FROM users WHERE role = 'petani' AND status = 'pending' ORDER BY created_at DESC";
$result = mysqli_query($conn, $query);
$total_pending = mysqli_num_rows($result);

include '../includes/navbar.php';
?>

<!-- SIGNATURE DASH HERO HEADER -->
<div class="dash-hero-header">
    <div class="dash-hero-inner">
        <div class="dash-hero-title">
            <h1><i class="fas fa-user-check"></i> <?= t('verify_account_title') ?></h1>
            <p><?= t('verify_account_subtitle') ?></p>
        </div>
        <div class="dash-hero-actions">
            <a href="data_semua_petani.php" class="btn-outline-white">
                <i class="fas fa-users"></i> Data Semua Petani
            </a>
            <a href="dashboard_desa.php" class="btn-outline-white">
                <i class="fas fa-arrow-left"></i> Dashboard
            </a>
        </div>
    </div>
</div>

<!-- MAIN PAGE CONTAINER -->
<div class="main-page-container">

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

    <div class="table-container-modern">
        <div class="table-toolbar">
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <div class="position-relative">
                    <i class="fas fa-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                    <input type="text" id="verifyTableSearch" class="table-search-input ps-5" placeholder="Cari nama, email, no HP...">
                </div>
            </div>
            <div class="text-muted small">
                Menunggu Verifikasi: <strong class="text-warning fs-6"><?= $total_pending ?></strong> Petani
            </div>
        </div>

        <?php if ($total_pending > 0): ?>
            <div class="table-responsive">
                <table class="table-modern" id="verifyTable">
                    <thead>
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th>Petani</th>
                            <th>Email</th>
                            <th>No. Handphone</th>
                            <th>Alamat Domisili</th>
                            <th>Waktu Daftar</th>
                            <th style="text-align: center; width: 120px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1; while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td class="text-muted"><?= $no++ ?></td>
                                <td>
                                    <div class="user-cell">
                                        <img src="../uploads/foto/<?= htmlspecialchars($row['foto'] ?? 'defaults.png') ?>" alt="Avatar" class="user-avatar-sm" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($row['nama']) ?>&background=059669&color=fff'">
                                        <div>
                                            <strong class="d-block text-dark"><?= htmlspecialchars($row['nama'] ?? '-') ?></strong>
                                            <span class="badge-pill-modern badge-status-pending">Menunggu</span>
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
                                <td class="text-muted small"><?= date('d M Y, H:i', strtotime($row['created_at'])) ?></td>
                                <td style="text-align: center;">
                                    <div class="action-toolbar">
                                        <!-- Form Setujui (Icon Only) -->
                                        <form method="POST" action="" style="margin: 0; display: inline;">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="user_id" value="<?= $row['id'] ?>">
                                            <input type="hidden" name="action" value="activate">
                                            <button type="submit" class="btn-action-icon btn-action-activate" title="Setujui & Verifikasi Akun"
                                                    onclick="return confirm('Setujui akun petani <?= htmlspecialchars(addslashes($row['nama'])) ?>?')">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        </form>
                                        
                                        <!-- Form Tolak (Icon Only) -->
                                        <form method="POST" action="" style="margin: 0; display: inline;">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="user_id" value="<?= $row['id'] ?>">
                                            <input type="hidden" name="action" value="reject">
                                            <button type="submit" class="btn-action-icon btn-action-block" title="Tolak Pendaftaran"
                                                    onclick="return confirm('Tolak pendaftaran akun <?= htmlspecialchars(addslashes($row['nama'])) ?>?')">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="p-5 text-center text-muted">
                <div class="stat-card-icon stat-icon-emerald mx-auto mb-3" style="width: 64px; height: 64px; font-size: 1.8rem;">
                    <i class="fas fa-clipboard-check"></i>
                </div>
                <h5 class="text-dark fw-bold"><?= t('verify_account_empty') ?></h5>
                <p class="small text-muted mb-0">Semua pendaftaran akun petani warga telah selesai diverifikasi.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('verifyTableSearch');
    const tableRows = document.querySelectorAll('#verifyTable tbody tr');

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