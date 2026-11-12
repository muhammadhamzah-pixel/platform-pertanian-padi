<?php
include '../includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . $base_url . 'login');
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['foto'])) {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error = t('err_csrf');
    } else {
        $target_dir = "../uploads/foto/";
        
        $file_tmp = $_FILES['foto']['tmp_name'];
        $file_size = $_FILES['foto']['size'];
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file_tmp);
        finfo_close($finfo);
        
        $allowed_mimes = ['image/jpeg', 'image/png', 'image/webp'];
        
        if (!in_array($mime_type, $allowed_mimes)) {
            $error = '❌ ' . t('err_file_not_image');
        } elseif ($file_size > 2 * 1024 * 1024) {
            $error = '❌ ' . t('err_file_too_big_2mb');
        } elseif (getimagesize($file_tmp) === false) {
            $error = '❌ ' . t('err_file_corrupt');
        } else {
            $ext_map = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
            $ext = $ext_map[$mime_type];
            $file_name = 'user_' . $user_id . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
            $target_file = $target_dir . $file_name;
            
            if (move_uploaded_file($file_tmp, $target_file)) {
                $query = "SELECT foto FROM users WHERE id = '$user_id'";
                $result = mysqli_query($conn, $query);
                $old_foto = mysqli_fetch_assoc($result)['foto'];
                
                if ($old_foto && $old_foto != 'defaults.png' && $old_foto != 'default.png') {
                    if (file_exists($target_dir . $old_foto)) {
                        unlink($target_dir . $old_foto);
                    }
                }
                
                $file_name_esc = mysqli_real_escape_string($conn, $file_name);
                $query = "UPDATE users SET foto = '$file_name_esc' WHERE id = '$user_id'";
                
                if (mysqli_query($conn, $query)) {
                    $message = '✅ ' . t('photo_update_success');
                    $_SESSION['foto'] = $file_name;
                    catat_log($conn, $user_id, $_SESSION['email'], $_SESSION['nama'], 'Ganti Foto', 'Foto profil berhasil diupdate');
                    regenerate_csrf_token();
                } else {
                    unlink($target_file);
                    $error = '❌ ' . t('photo_update_failed');
                }
            } else {
                $error = '❌ ' . t('photo_upload_failed');
            }
        }
    }
}

$query = "SELECT * FROM users WHERE id = '$user_id'";
$result = mysqli_query($conn, $query);
$user = mysqli_fetch_assoc($result);

$role = $user['role'];
include '../includes/navbar.php';
?>

<!-- SIGNATURE DASH HERO HEADER -->
<div class="dash-hero-header">
    <div class="dash-hero-inner">
        <div class="dash-hero-title">
            <h1><i class="fas fa-camera"></i> <?= t('photo_title') ?></h1>
            <p><?= t('photo_subtitle') ?></p>
        </div>
        <div class="dash-hero-actions">
            <a href="dashboard_<?= $role ?>.php" class="btn-outline-white">
                <i class="fas fa-arrow-left"></i> Dashboard
            </a>
        </div>
    </div>
</div>

<!-- MAIN PAGE CONTAINER -->
<div class="main-page-container">
    <div class="row justify-content-center">
        <div class="col-lg-7">

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

            <!-- UPLOAD CARD -->
            <div class="card-modern">
                <div class="card-modern-header">
                    <h2 class="card-modern-title">
                        <i class="fas fa-image text-success"></i> Ganti Foto Profil Akun
                    </h2>
                    <span class="badge bg-light text-muted border">Maks. 2 MB (JPG/PNG/WEBP)</span>
                </div>
                <div class="card-modern-body">
                    <form method="POST" action="" enctype="multipart/form-data">
                        <?= csrf_field() ?>

                        <div class="d-flex align-items-center gap-4 flex-wrap mb-4">
                            <div class="text-center">
                                <img src="../uploads/foto/<?= htmlspecialchars($user['foto'] ?? 'defaults.png') ?>" id="previewAvatar" alt="Preview Foto" class="rounded-circle border shadow-sm" style="width: 110px; height: 110px; object-fit: cover; border: 3px solid #10b981 !important;">
                                <small class="text-muted d-block mt-2 font-monospace">Foto Saat Ini</small>
                            </div>
                            <div class="flex-grow-1">
                                <label class="form-label-modern"><?= t('photo_select') ?></label>
                                <input type="file" name="foto" id="fotoInput" class="form-control-modern" accept="image/jpeg,image/png,image/webp" required onchange="previewImage(this)">
                                <div class="text-muted small mt-2">
                                    <i class="fas fa-info-circle me-1"></i> Gunakan foto resmi yang jelas untuk memudahkan identifikasi petani dan petugas desa.
                                </div>
                            </div>
                        </div>

                        <div class="d-flex align-items-center justify-content-between pt-2 border-top">
                            <a href="dashboard_<?= $role ?>.php" class="btn-secondary-modern">
                                <i class="fas fa-arrow-left"></i> Kembali
                            </a>
                            <button type="submit" class="btn-primary-green">
                                <i class="fas fa-cloud-upload-alt"></i> <?= t('photo_save') ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
function previewImage(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('previewAvatar').src = e.target.result;
        }
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php include '../includes/footer.php'; ?>