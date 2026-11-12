<?php
include '../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'petani') {
    header('Location: ' . $base_url . 'login');
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['mark_all'])) {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $message = "❌ " . t('err_csrf');
    } else {
        $query = "UPDATE notifikasi SET is_read = 1 WHERE user_id = '$user_id'";
        mysqli_query($conn, $query);
        regenerate_csrf_token();
        header('Location: notifikasi.php');
        exit();
    }
}

$query = "SELECT * FROM notifikasi WHERE user_id = '$user_id' ORDER BY created_at DESC";
$result = mysqli_query($conn, $query);

$count_query = "SELECT COUNT(*) AS total FROM notifikasi WHERE user_id = '$user_id' AND is_read = 0";
$count_result = mysqli_query($conn, $count_query);
$unread_count = mysqli_fetch_assoc($count_result)['total'] ?? 0;

include '../includes/navbar.php';
?>

<!-- SIGNATURE DASH HERO HEADER -->
<div class="dash-hero-header">
    <div class="dash-hero-inner">
        <div class="dash-hero-title">
            <h1><i class="fas fa-bell"></i> <?= t('notif_title') ?></h1>
            <p>Pusat pemberitahuan jadwal tanam, masa panen, rekomendasi cuaca, dan info desa.</p>
        </div>
        <div class="dash-hero-actions">
            <?php if ($unread_count > 0): ?>
                <form method="POST" action="" style="margin: 0; display: inline;">
                    <?= csrf_field() ?>
                    <button type="submit" name="mark_all" value="1" class="btn-primary-green">
                        <i class="fas fa-check-double"></i> <?= t('notif_mark_read') ?>
                    </button>
                </form>
            <?php endif; ?>
            <a href="dashboard_petani.php" class="btn-outline-white">
                <i class="fas fa-arrow-left"></i> Dashboard
            </a>
        </div>
    </div>
</div>

<!-- MAIN PAGE CONTAINER -->
<div class="main-page-container">
    <div class="row justify-content-center">
        <div class="col-lg-8">

            <?php if ($message): ?>
                <div class="alert-modern alert-modern-danger mb-4">
                    <i class="fas fa-exclamation-circle fs-5"></i>
                    <div><?= $message ?></div>
                </div>
            <?php endif; ?>

            <div class="card-modern">
                <div class="card-modern-header">
                    <h2 class="card-modern-title">
                        <i class="fas fa-inbox text-success"></i> Kotak Masuk Pemberitahuan
                    </h2>
                    <span class="badge bg-light text-muted border">
                        <?= $unread_count ?> Belum Dibaca
                    </span>
                </div>
                <div class="card-modern-body p-0">
                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <div class="list-group list-group-flush">
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                <a href="mark_read.php?id=<?= $row['id'] ?>&csrf_token=<?= generate_csrf_token() ?>" class="list-group-item list-group-item-action p-4 border-bottom text-decoration-none <?= $row['is_read'] == 0 ? 'bg-light' : '' ?>">
                                    <div class="d-flex align-items-start gap-3">
                                        <div class="stat-card-icon <?= $row['is_read'] == 0 ? 'stat-icon-amber' : 'stat-icon-emerald' ?>" style="width: 44px; height: 44px; font-size: 1.1rem; flex-shrink: 0;">
                                            <i class="fas <?= $row['is_read'] == 0 ? 'fa-envelope' : 'fa-envelope-open' ?>"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="d-flex align-items-center justify-content-between mb-1">
                                                <h6 class="fw-bold text-dark mb-0"><?= htmlspecialchars($row['judul']) ?></h6>
                                                <small class="text-muted" style="font-size: 11px;">
                                                    <i class="far fa-clock me-1"></i><?= date('d M Y, H:i', strtotime($row['created_at'])) ?>
                                                </small>
                                            </div>
                                            <p class="text-secondary small mb-1"><?= htmlspecialchars($row['pesan']) ?></p>
                                            <?php if ($row['is_read'] == 0): ?>
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill" style="font-size: 10px;">
                                                    <?= t('notif_new') ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </a>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="p-5 text-center text-muted">
                            <i class="fas fa-bell-slash fa-3x mb-3 text-secondary opacity-50"></i>
                            <h5 class="text-dark fw-bold"><?= t('notif_empty') ?></h5>
                            <p class="small text-muted mb-0"><?= t('notif_empty_desc') ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>