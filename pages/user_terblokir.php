<?php
include '../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit();
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['unblock'])) {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error = t('err_csrf');
    } else {
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        
        $query = "DELETE FROM login_attempts WHERE email = '$email'";
        if (mysqli_query($conn, $query)) {
            $message = '✅ ' . t('blocked_unblock_success') . ' <strong>' . htmlspecialchars($email) . '</strong>';
            catat_log($conn, $_SESSION['user_id'], $_SESSION['email'], $_SESSION['nama'], 'Buka Blokir', "Admin buka blokir email: $email");
            regenerate_csrf_token();
        } else {
            $error = '❌ ' . t('failed') . ': ' . mysqli_error($conn);
        }
    }
}

$query = "SELECT la.email, 
          COUNT(*) AS total_gagal,
          MAX(la.created_at) AS terakhir_gagal,
          MIN(la.created_at) AS pertama_gagal
          FROM login_attempts la
          LEFT JOIN users u ON la.email = u.email
          WHERE la.status = 'gagal' 
          AND la.created_at >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)
          AND (u.role = 'petani' OR u.role IS NULL)
          GROUP BY la.email
          HAVING total_gagal >= 5
          ORDER BY total_gagal DESC";

$result = mysqli_query($conn, $query);
$total_blocked = mysqli_num_rows($result);

include '../includes/navbar.php';
?>

<!-- SIGNATURE DASH HERO HEADER -->
<div class="dash-hero-header">
    <div class="dash-hero-inner">
        <div class="dash-hero-title">
            <h1><i class="fas fa-user-lock"></i> <?= t('blocked_title') ?></h1>
            <p><?= t('blocked_subtitle') ?></p>
        </div>
        <div class="dash-hero-actions">
            <a href="security_analytics.php" class="btn-primary-green">
                <i class="fas fa-shield-alt"></i> Security Analytics
            </a>
            <a href="dashboard_admin.php" class="btn-outline-white">
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
            <div class="d-flex align-items-center gap-2">
                <i class="fas fa-shield-virus text-danger fs-5"></i>
                <span class="fw-bold text-dark">Daftar Akun Terblokir Otomatis (Rate Limit)</span>
            </div>
            <div class="text-muted small">
                Total: <strong class="text-danger"><?= $total_blocked ?></strong> Akun Terdeteksi
            </div>
        </div>

        <?php if ($total_blocked > 0): ?>
            <div class="table-responsive">
                <table class="table-modern">
                    <thead>
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th>Email Akun</th>
                            <th>Percobaan Gagal</th>
                            <th>Awal Gagal</th>
                            <th>Terakhir Gagal</th>
                            <th>Sisa Waktu Blokir</th>
                            <th style="text-align: center; width: 100px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1; while ($row = mysqli_fetch_assoc($result)): ?>
                            <?php
                            $waktu_gagal_pertama = strtotime($row['pertama_gagal']);
                            $waktu_blokir_selesai = $waktu_gagal_pertama + (15 * 60);
                            $sisa_detik = $waktu_blokir_selesai - time();
                            $sisa_menit = max(0, ceil($sisa_detik / 60));
                            ?>
                            <tr>
                                <td class="text-muted"><?= $no++ ?></td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="fas fa-envelope text-muted"></i>
                                        <strong class="text-dark"><?= htmlspecialchars($row['email']) ?></strong>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge-pill-modern badge-status-blocked">
                                        <i class="fas fa-times-circle"></i> <?= $row['total_gagal'] ?>x Gagal
                                    </span>
                                </td>
                                <td class="text-muted small"><?= date('d M Y, H:i:s', strtotime($row['pertama_gagal'])) ?></td>
                                <td class="text-muted small"><?= date('d M Y, H:i:s', strtotime($row['terakhir_gagal'])) ?></td>
                                <td>
                                    <span class="badge bg-warning-subtle text-dark border border-warning-subtle px-3 py-2 rounded-pill fw-bold">
                                        <i class="fas fa-hourglass-half me-1 text-warning"></i> <?= $sisa_menit ?> Menit
                                    </span>
                                </td>
                                <td style="text-align: center;">
                                    <form method="POST" action="" style="margin: 0; display: inline;">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="email" value="<?= htmlspecialchars($row['email']) ?>">
                                        <button type="submit" name="unblock" value="1" class="btn-action-icon btn-action-activate" title="Buka Blokir Akun Sekarang"
                                                onclick="return confirm('<?= t('confirm_action') ?> Buka blokir <?= htmlspecialchars(addslashes($row['email'])) ?>?')">
                                            <i class="fas fa-unlock"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="p-5 text-center text-muted">
                <i class="fas fa-shield-check fa-3x mb-3 text-success"></i>
                <h5 class="text-success fw-bold">Aman! Tidak Ada Akun Terblokir</h5>
                <p class="small text-muted mb-0">Tidak ada indikasi serangan login berulang saat ini.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>