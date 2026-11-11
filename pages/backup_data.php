<?php
include '../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['backup'])) {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        header('Location: backup_data.php?error=csrf');
        exit();
    }
    
    $filename = 'backup_padi_' . date('Y-m-d_H-i-s') . '.sql';
    
    $tables = [];
    $result = mysqli_query($conn, "SHOW TABLES");
    while ($row = mysqli_fetch_row($result)) {
        $tables[] = $row[0];
    }
    
    $sql = "-- =========================================\n";
    $sql .= "-- Backup Database: db_padi\n";
    $sql .= "-- Tanggal: " . date('Y-m-d H:i:s') . "\n";
    $sql .= "-- =========================================\n\n";
    $sql .= "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n";
    $sql .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";
    
    foreach ($tables as $table) {
        $result = mysqli_query($conn, "SHOW CREATE TABLE `$table`");
        $row = mysqli_fetch_row($result);
        $sql .= "-- -----------------------------------------\n";
        $sql .= "-- Table: $table\n";
        $sql .= "-- -----------------------------------------\n";
        $sql .= "DROP TABLE IF EXISTS `$table`;\n";
        $sql .= $row[1] . ";\n\n";
        
        $result_data = mysqli_query($conn, "SELECT * FROM `$table`");
        if (mysqli_num_rows($result_data) > 0) {
            while ($data = mysqli_fetch_assoc($result_data)) {
                $columns = array_keys($data);
                $values = array_map(function($v) use ($conn) {
                    if ($v === null) return 'NULL';
                    return "'" . mysqli_real_escape_string($conn, $v) . "'";
                }, array_values($data));
                $sql .= "INSERT INTO `$table` (`" . implode('`, `', $columns) . "`) VALUES (" . implode(', ', $values) . ");\n";
            }
        }
        $sql .= "\n";
    }
    
    $sql .= "SET FOREIGN_KEY_CHECKS = 1;\n";
    $sql .= "\n-- =========================================\n";
    $sql .= "-- " . t('backup_done') . "\n";
    $sql .= "-- =========================================\n";
    
    if (ob_get_length()) {
        ob_end_clean();
    }
    
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($sql));
    header('Expires: 0');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Pragma: no-cache');
    echo $sql;
    exit;
}

// Ambil info tabel-tabel di database
$table_list = [];
$res_t = mysqli_query($conn, "SHOW TABLE STATUS");
$total_size_bytes = 0;
if ($res_t) {
    while ($t_row = mysqli_fetch_assoc($res_t)) {
        $table_list[] = $t_row;
        $total_size_bytes += ($t_row['Data_length'] + $t_row['Index_length']);
    }
}
$total_size_kb = round($total_size_bytes / 1024, 2);

include '../includes/navbar.php';
?>

<!-- SIGNATURE DASH HERO HEADER -->
<div class="dash-hero-header">
    <div class="dash-hero-inner">
        <div class="dash-hero-title">
            <h1><i class="fas fa-database"></i> <?= t('backup_title') ?></h1>
            <p><?= t('backup_subtitle') ?></p>
        </div>
        <div class="dash-hero-actions">
            <a href="dashboard_admin.php" class="btn-outline-white">
                <i class="fas fa-arrow-left"></i> Dashboard
            </a>
        </div>
    </div>
</div>

<!-- MAIN PAGE CONTAINER -->
<div class="main-page-container">
    <?php if (isset($_GET['error']) && $_GET['error'] == 'csrf'): ?>
        <div class="alert-modern alert-modern-danger">
            <i class="fas fa-exclamation-circle fs-5"></i>
            <div><?= t('err_csrf') ?></div>
        </div>
    <?php endif; ?>

    <div class="row g-4 justify-content-center">
        <!-- DOWNLOAD CARD -->
        <div class="col-lg-5">
            <div class="card-modern text-center p-4">
                <div class="stat-card-icon stat-icon-emerald mx-auto mb-3" style="width: 70px; height: 70px; font-size: 2rem;">
                    <i class="fas fa-cloud-download-alt"></i>
                </div>
                <h4 class="fw-bold text-dark mb-2"><?= t('backup_info') ?></h4>
                <p class="text-secondary small mb-4"><?= t('backup_info_desc') ?></p>
                
                <div class="p-3 bg-light rounded-3 text-start mb-4 small border">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Database:</span>
                        <strong class="text-dark">db_padi (MySQL)</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Total Tabel:</span>
                        <strong class="text-dark"><?= count($table_list) ?> Tabel</strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Estimasi Ukuran:</span>
                        <strong class="text-success"><?= $total_size_kb ?> KB</strong>
                    </div>
                </div>

                <form method="POST" action="" style="margin: 0;">
                    <?= csrf_field() ?>
                    <button type="submit" name="backup" value="1" class="btn-primary-green w-100 justify-content-center py-3 fs-6">
                        <i class="fas fa-download"></i> Unduh File Backup (.SQL)
                    </button>
                </form>
            </div>
        </div>

        <!-- TABLE DETAILS -->
        <div class="col-lg-7">
            <div class="card-modern">
                <div class="card-modern-header">
                    <h2 class="card-modern-title">
                        <i class="fas fa-table"></i> Rincian Tabel Database
                    </h2>
                    <span class="badge bg-light text-muted border">Skema Aktif</span>
                </div>
                <div class="table-responsive">
                    <table class="table-modern">
                        <thead>
                            <tr>
                                <th>Nama Tabel</th>
                                <th>Total Baris</th>
                                <th>Ukuran Data</th>
                                <th>Engine</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($table_list as $t): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="fas fa-database text-muted" style="font-size: 12px;"></i>
                                            <strong class="text-dark"><?= htmlspecialchars($t['Name']) ?></strong>
                                        </div>
                                    </td>
                                    <td><span class="badge bg-light text-dark border"><?= number_format($t['Rows']) ?> rows</span></td>
                                    <td class="text-secondary small"><?= round(($t['Data_length'] + $t['Index_length']) / 1024, 1) ?> KB</td>
                                    <td class="text-muted small"><?= htmlspecialchars($t['Engine']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>