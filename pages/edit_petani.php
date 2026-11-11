<?php
include '../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit();
}

$message = '';
$error = '';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: kelola_user.php');
    exit();
}

$user_id = intval($_GET['id']);

$query = "SELECT * FROM users WHERE id = '$user_id'";
$result = mysqli_query($conn, $query);
$user = mysqli_fetch_assoc($result);

if (!$user) {
    header('Location: kelola_user.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error = t('err_csrf');
    } else {
        $email = sanitize_input($_POST['email']);
        $nama = sanitize_input($_POST['nama']);
        $no_hp = sanitize_input($_POST['no_hp']);
        $alamat = sanitize_input($_POST['alamat']);
        $role = sanitize_input($_POST['role']);
        $status = sanitize_input($_POST['status']);
        
        $validasi = cek_validasi([
            validasi_email($email),
            validasi_nama($nama),
            validasi_no_hp($no_hp)
        ]);
        
        $role_valid = in_array($role, ['petani', 'perangkat_desa', 'admin']);
        $status_valid = in_array($status, ['pending', 'active', 'blocked']);
        
        if ($validasi !== true) {
            $error = "❌ " . $validasi;
        } elseif (!$role_valid) {
            $error = '❌ ' . t('err_role_invalid');
        } elseif (!$status_valid) {
            $error = '❌ ' . t('err_status_invalid');
        } else {
            $email = mysqli_real_escape_string($conn, $email);
            $nama = mysqli_real_escape_string($conn, $nama);
            $no_hp = mysqli_real_escape_string($conn, $no_hp);
            $alamat = mysqli_real_escape_string($conn, $alamat);
            $role = mysqli_real_escape_string($conn, $role);
            $status = mysqli_real_escape_string($conn, $status);
            
            $query = "UPDATE users SET 
                        email = '$email',
                        nama = '$nama',
                        no_hp = '$no_hp',
                        alamat = '$alamat',
                        role = '$role',
                        status = '$status'
                      WHERE id = '$user_id'";
            
            if (mysqli_query($conn, $query)) {
                $message = '✅ ' . t('edit_user_success');
                catat_log($conn, $_SESSION['user_id'], $_SESSION['email'], $_SESSION['nama'], 'Edit User', "Update user ID: $user_id ($nama)");
                regenerate_csrf_token();
                $result = mysqli_query($conn, "SELECT * FROM users WHERE id = '$user_id'");
                $user = mysqli_fetch_assoc($result);
            } else {
                $error = '❌ ' . t('failed') . ': ' . mysqli_error($conn);
            }
        }
    }
}

include '../includes/navbar.php';
?>

<!-- SIGNATURE DASH HERO HEADER -->
<div class="dash-hero-header">
    <div class="dash-hero-inner">
        <div class="dash-hero-title">
            <h1><i class="fas fa-user-edit"></i> <?= t('edit_user_title') ?></h1>
            <p><?= t('edit_user_subtitle') ?></p>
        </div>
        <div class="dash-hero-actions">
            <a href="kelola_user.php" class="btn-outline-white">
                <i class="fas fa-users-cog"></i> Kelola User
            </a>
            <a href="dashboard_admin.php" class="btn-outline-white">
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

            <div class="card-modern">
                <div class="card-modern-header">
                    <div class="d-flex align-items-center gap-3">
                        <img src="../uploads/foto/<?= htmlspecialchars($user['foto'] ?? 'defaults.png') ?>" alt="Foto" class="user-avatar-sm" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($user['nama']) ?>&background=059669&color=fff'">
                        <div>
                            <h2 class="card-modern-title mb-0"><?= htmlspecialchars($user['nama'] ?? 'Pengguna') ?></h2>
                            <small class="text-muted"><?= htmlspecialchars($user['email'] ?? '-') ?></small>
                        </div>
                    </div>
                    <span class="badge-pill-modern badge-status-<?= $user['status'] ?>">
                        Status: <?= ucfirst($user['status']) ?>
                    </span>
                </div>
                <div class="card-modern-body">
                    <form method="POST" action="">
                        <?= csrf_field() ?>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label-modern"><?= t('email') ?> *</label>
                                <input type="email" name="email" class="form-control-modern" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label-modern"><?= t('name') ?> *</label>
                                <input type="text" name="nama" class="form-control-modern" value="<?= htmlspecialchars($user['nama'] ?? '') ?>" required>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label-modern"><?= t('phone') ?></label>
                                <input type="text" name="no_hp" class="form-control-modern" value="<?= htmlspecialchars($user['no_hp'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label-modern"><?= t('manage_user_col_role') ?> *</label>
                                <select name="role" class="form-control-modern form-select">
                                    <option value="petani" <?= $user['role'] == 'petani' ? 'selected' : '' ?>><?= t('role_petani') ?></option>
                                    <option value="perangkat_desa" <?= $user['role'] == 'perangkat_desa' ? 'selected' : '' ?>><?= t('role_perangkat_desa') ?></option>
                                    <option value="admin" <?= $user['role'] == 'admin' ? 'selected' : '' ?>><?= t('role_admin') ?></option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label-modern"><?= t('manage_user_col_status') ?> *</label>
                            <select name="status" class="form-control-modern form-select">
                                <option value="pending" <?= $user['status'] == 'pending' ? 'selected' : '' ?>><?= t('status_pending') ?></option>
                                <option value="active" <?= $user['status'] == 'active' ? 'selected' : '' ?>><?= t('status_active') ?></option>
                                <option value="blocked" <?= $user['status'] == 'blocked' ? 'selected' : '' ?>><?= t('status_blocked') ?></option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="form-label-modern"><?= t('address') ?></label>
                            <textarea name="alamat" class="form-control-modern" rows="3"><?= htmlspecialchars($user['alamat'] ?? '') ?></textarea>
                        </div>

                        <div class="d-flex align-items-center justify-content-between pt-2">
                            <a href="kelola_user.php" class="btn-secondary-modern">
                                <i class="fas fa-arrow-left"></i> Batal
                            </a>
                            <button type="submit" class="btn-primary-green">
                                <i class="fas fa-save"></i> <?= t('edit_user_save') ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>