<?php
// pages/edit_profil.php
// Halaman Edit Profil Petani & Pengguna Terdaftar

include '../includes/config.php';

// Proteksi Keamanan: Wajib Login
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . $base_url . 'login');
    exit();
}

$user_id = intval($_SESSION['user_id']);
$message = '';
$error = '';

// Ambil flash message dari session jika ada (pola PRG)
if (isset($_SESSION['flash_message'])) {
    $message = $_SESSION['flash_message'];
    unset($_SESSION['flash_message']);
}
if (isset($_SESSION['flash_error'])) {
    $error = $_SESSION['flash_error'];
    unset($_SESSION['flash_error']);
}

// Ambil data user terbaru dari database
$query = "SELECT * FROM users WHERE id = '$user_id' LIMIT 1";
$result = mysqli_query($conn, $query);
$user = mysqli_fetch_assoc($result);

if (!$user) {
    header('Location: ' . $base_url . 'login');
    exit();
}

$role = $user['role'];

// ============================================
// PROSES SIMPAN PERUBAHAN PROFIL
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $_SESSION['flash_error'] = t('err_csrf');
        header('Location: ' . $base_url . 'edit_profil');
        exit();
    }

    $nama   = sanitize_input($_POST['nama'] ?? '');
    $email  = sanitize_input($_POST['email'] ?? '');
    $no_hp  = sanitize_input($_POST['no_hp'] ?? '');
    $alamat = sanitize_input($_POST['alamat'] ?? '');

    // Validasi input
    $validasi = cek_validasi([
        validasi_nama($nama),
        validasi_email($email, true),
        validasi_no_hp($no_hp),
        validasi_alamat($alamat, 'Alamat')
    ]);

    if ($validasi !== true) {
        $_SESSION['flash_error'] = "❌ " . $validasi;
        header('Location: ' . $base_url . 'edit_profil');
        exit();
    }

    // Cek keunikan email (tidak boleh bentrok dengan akun lain)
    $email_esc = mysqli_real_escape_string($conn, $email);
    $cek_email = mysqli_query($conn, "SELECT id FROM users WHERE email = '$email_esc' AND id != '$user_id' LIMIT 1");
    if (mysqli_num_rows($cek_email) > 0) {
        $_SESSION['flash_error'] = "❌ Email '$email' sudah digunakan oleh pengguna lain. Silakan gunakan email lain.";
        header('Location: ' . $base_url . 'edit_profil');
        exit();
    }

    // Proses unggah foto profil baru (jika ada file yang dipilih)
    $new_foto_name = $user['foto'];
    $foto_updated = false;

    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $target_dir = "../uploads/foto/";
        $file_tmp   = $_FILES['foto']['tmp_name'];
        $file_size  = $_FILES['foto']['size'];

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file_tmp);
        finfo_close($finfo);

        $allowed_mimes = ['image/jpeg', 'image/png', 'image/webp'];

        if (!in_array($mime_type, $allowed_mimes)) {
            $_SESSION['flash_error'] = '❌ Format file foto harus JPG, PNG, atau WebP!';
            header('Location: ' . $base_url . 'edit_profil');
            exit();
        } elseif ($file_size > 2 * 1024 * 1024) {
            $_SESSION['flash_error'] = '❌ Ukuran file foto maksimal 2 MB!';
            header('Location: ' . $base_url . 'edit_profil');
            exit();
        } elseif (getimagesize($file_tmp) === false) {
            $_SESSION['flash_error'] = '❌ File foto yang diunggah tidak valid atau rusak!';
            header('Location: ' . $base_url . 'edit_profil');
            exit();
        } else {
            $ext_map = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
            $ext = $ext_map[$mime_type];
            $generated_file_name = 'user_' . $user_id . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
            $target_file = $target_dir . $generated_file_name;

            if (move_uploaded_file($file_tmp, $target_file)) {
                // Hapus foto profil lama jika bukan foto default
                $old_foto = $user['foto'];
                if ($old_foto && !in_array($old_foto, ['defaults.png', 'default.png'])) {
                    if (file_exists($target_dir . $old_foto)) {
                        @unlink($target_dir . $old_foto);
                    }
                }
                $new_foto_name = $generated_file_name;
                $foto_updated = true;
            } else {
                $_SESSION['flash_error'] = '❌ Gagal mengunggah foto ke server. Periksa hak akses folder.';
                header('Location: ' . $base_url . 'edit_profil');
                exit();
            }
        }
    }

    // Simpan perubahan ke Database
    $nama_esc   = mysqli_real_escape_string($conn, $nama);
    $no_hp_esc  = mysqli_real_escape_string($conn, $no_hp);
    $alamat_esc = mysqli_real_escape_string($conn, $alamat);
    $foto_esc   = mysqli_real_escape_string($conn, $new_foto_name);

    $update_query = "UPDATE users SET 
                        nama = '$nama_esc',
                        email = '$email_esc',
                        no_hp = '$no_hp_esc',
                        alamat = '$alamat_esc',
                        foto = '$foto_esc'
                     WHERE id = '$user_id'";

    if (mysqli_query($conn, $update_query)) {
        // Sinkronisasi session login agar nama & foto langsung ter-update di layar
        $_SESSION['nama']  = $nama;
        $_SESSION['email'] = $email;
        if ($foto_updated) {
            $_SESSION['foto'] = $new_foto_name;
        }

        // Catat log aktivitas
        catat_log($conn, $user_id, $email, $nama, 'Update Profil', "Petani memperbarui profil akun (Nama: $nama)");

        $_SESSION['flash_message'] = '✅ Profil Anda berhasil diperbarui!';
        header('Location: ' . $base_url . 'edit_profil');
        exit();
    } else {
        $_SESSION['flash_error'] = '❌ Terjadi kesalahan saat menyimpan data: ' . mysqli_error($conn);
        header('Location: ' . $base_url . 'edit_profil');
        exit();
    }
}

// Generate token CSRF untuk form
$csrf_token = generate_csrf_token();

// Load navbar
include '../includes/navbar.php';
?>

<style>
.profile-card-left {
    background: #ffffff;
    border-radius: 16px;
    border: 1px solid #e2e8f0;
    padding: 28px 24px;
    text-align: center;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.04);
}
.profile-avatar-large-wrap {
    position: relative;
    width: 130px;
    height: 130px;
    margin: 0 auto 16px auto;
}
.profile-avatar-large {
    width: 130px;
    height: 130px;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid #10b981;
    box-shadow: 0 4px 14px rgba(16, 185, 129, 0.25);
}
.profile-upload-pill {
    position: absolute;
    bottom: 2px;
    right: 2px;
    background: #10b981;
    color: #ffffff;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    border: 2px solid #ffffff;
    transition: all 0.2s ease;
    box-shadow: 0 2px 6px rgba(0,0,0,0.2);
}
.profile-upload-pill:hover {
    background: #059669;
    transform: scale(1.1);
}
.badge-role-petani {
    background: #ecfdf5;
    color: #059669;
    border: 1px solid #a7f3d0;
    padding: 6px 16px;
    border-radius: 50px;
    font-size: 0.82rem;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
.badge-status-aktif {
    background: #eff6ff;
    color: #2563eb;
    border: 1px solid #bfdbfe;
    padding: 6px 14px;
    border-radius: 50px;
    font-size: 0.82rem;
    font-weight: 600;
}
.profile-info-list {
    text-align: left;
    margin-top: 24px;
    padding-top: 20px;
    border-top: 1px dashed #e2e8f0;
}
.profile-info-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    font-size: 0.88rem;
}
.profile-info-item .label {
    color: #64748b;
}
.profile-info-item .val {
    color: #0f172a;
    font-weight: 600;
}
</style>

<!-- DASH HERO HEADER -->
<div class="dash-hero-header">
    <div class="dash-hero-inner">
        <div class="dash-hero-title">
            <h1><i class="fas fa-user-edit"></i> Edit Profil Petani</h1>
            <p>Kelola data identitas, kontak WhatsApp, alamat, dan foto profil Anda.</p>
        </div>
        <div class="dash-hero-actions">
            <a href="<?= $base_url ?>dashboard_<?= $role ?>" class="btn-outline-white">
                <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
            </a>
        </div>
    </div>
</div>

<!-- MAIN PAGE CONTAINER -->
<div class="main-page-container">

    <!-- FEEDBACK ALERT -->
    <?php if ($message): ?>
        <div class="alert-modern alert-modern-success mb-4">
            <i class="fas fa-check-circle fs-5"></i>
            <div><?= $message ?></div>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert-modern alert-modern-danger mb-4">
            <i class="fas fa-exclamation-circle fs-5"></i>
            <div><?= $error ?></div>
        </div>
    <?php endif; ?>

    <form method="POST" action="" enctype="multipart/form-data" id="formEditProfil">
        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

        <div class="row g-4">
            <!-- KOLOM KIRI: KARTU FOTO & RINGKASAN AKUN -->
            <div class="col-lg-4">
                <div class="profile-card-left">
                    <div class="profile-avatar-large-wrap">
                        <img src="<?= $base_url ?>uploads/foto/<?= htmlspecialchars($user['foto'] ?? 'defaults.png') ?>" 
                             alt="Avatar" id="avatarPreview" class="profile-avatar-large"
                             onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($user['nama']) ?>&background=059669&color=fff'">
                        <label for="fotoInput" class="profile-upload-pill" title="Klik untuk pilih foto baru">
                            <i class="fas fa-camera"></i>
                        </label>
                    </div>

                    <h4 class="fw-bold text-dark mb-1"><?= htmlspecialchars($user['nama']) ?></h4>
                    <p class="text-muted small mb-3"><?= htmlspecialchars($user['email']) ?></p>

                    <div class="d-flex justify-content-center gap-2 mb-3">
                        <span class="badge-role-petani">
                            <i class="fas fa-shield-alt"></i> <?= t('role_' . $role) ?>
                        </span>
                        <span class="badge-status-aktif">
                            <i class="fas fa-check-circle me-1"></i> <?= ucfirst($user['status'] ?? 'Aktif') ?>
                        </span>
                    </div>

                    <div class="mt-2">
                        <label for="fotoInput" class="btn btn-sm btn-outline-success rounded-pill px-3 fw-semibold">
                            <i class="fas fa-upload me-1"></i> Unggah Foto Baru
                        </label>
                        <input type="file" name="foto" id="fotoInput" class="d-none" accept="image/jpeg,image/png,image/webp" onchange="previewAvatar(this)">
                        <div class="text-muted" style="font-size: 11px; margin-top: 8px;">
                            Format JPG, PNG, atau WebP (Maksimal 2 MB)
                        </div>
                    </div>

                    <div class="profile-info-list">
                        <div class="profile-info-item">
                            <span class="label"><i class="far fa-calendar-alt me-1 text-muted"></i> Terdaftar</span>
                            <span class="val"><?= date('d M Y', strtotime($user['created_at'])) ?></span>
                        </div>
                        <div class="profile-info-item">
                            <span class="label"><i class="fas fa-phone-alt me-1 text-muted"></i> No. HP</span>
                            <span class="val"><?= !empty($user['no_hp']) ? htmlspecialchars($user['no_hp']) : '<span class="text-muted fw-normal">Belum diisi</span>' ?></span>
                        </div>
                        <div class="profile-info-item">
                            <span class="label"><i class="fas fa-shield-virus me-1 text-muted"></i> Keamanan</span>
                            <span class="val text-success">Terlindungi</span>
                        </div>
                    </div>

                    <div class="mt-3 pt-3 border-top">
                        <a href="<?= $base_url ?>ganti_password" class="btn btn-sm btn-light border rounded-pill w-100 fw-semibold text-secondary">
                            <i class="fas fa-key me-1 text-warning"></i> Ganti Kata Sandi
                        </a>
                    </div>
                </div>
            </div>

            <!-- KOLOM KANAN: FORMULIR DATA PETANI -->
            <div class="col-lg-8">
                <div class="card-modern">
                    <div class="card-modern-header border-bottom pb-3 mb-4">
                        <h5 class="fw-bold text-dark mb-0">
                            <i class="fas fa-id-card text-success me-2"></i> Informasi Data Petani
                        </h5>
                        <small class="text-muted">Data ini tersimpan aman di database sistem desa</small>
                    </div>

                    <!-- NAMA LENGKAP -->
                    <div class="mb-3">
                        <label class="form-label-modern fw-bold">
                            Nama Lengkap <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0">
                                <i class="fas fa-user text-muted"></i>
                            </span>
                            <input type="text" name="nama" id="namaInput" class="form-control form-control-modern border-start-0" 
                                   placeholder="Masukkan nama lengkap Anda" 
                                   value="<?= htmlspecialchars($user['nama']) ?>" required>
                        </div>
                        <div class="form-text small text-muted">Gunakan nama resmi Anda agar mudah dikenali oleh perangkat desa.</div>
                    </div>

                    <!-- NOMOR HP / WHATSAPP -->
                    <div class="mb-3">
                        <label class="form-label-modern fw-bold">
                            Nomor HP / WhatsApp <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0">
                                <i class="fab fa-whatsapp text-success fs-6"></i>
                            </span>
                            <input type="text" name="no_hp" id="noHpInput" class="form-control form-control-modern border-start-0" 
                                   placeholder="Contoh: 081234567890" 
                                   value="<?= htmlspecialchars($user['no_hp'] ?? '') ?>" required>
                        </div>
                        <div class="form-text small text-muted">Nomor aktif untuk menerima notifikasi pengingat panen dan pemupukan.</div>
                    </div>

                    <!-- ALAMAT EMAIL -->
                    <div class="mb-3">
                        <label class="form-label-modern fw-bold">
                            Alamat Email <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0">
                                <i class="fas fa-envelope text-muted"></i>
                            </span>
                            <input type="email" name="email" id="emailInput" class="form-control form-control-modern border-start-0" 
                                   placeholder="nama@email.com" 
                                   value="<?= htmlspecialchars($user['email']) ?>" required>
                        </div>
                        <div class="form-text small text-muted">Digunakan sebagai email login dan reset kata sandi akun Anda.</div>
                    </div>

                    <!-- ALAMAT LENGKAP -->
                    <div class="mb-4">
                        <label class="form-label-modern fw-bold">
                            Alamat Lengkap / Dusun
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0 align-items-start pt-2">
                                <i class="fas fa-map-marker-alt text-danger"></i>
                            </span>
                            <textarea name="alamat" id="alamatInput" rows="3" class="form-control form-control-modern border-start-0" 
                                      placeholder="Nama jalan, RT/RW, Dusun, atau Desa..."><?= htmlspecialchars($user['alamat'] ?? '') ?></textarea>
                        </div>
                        <div class="form-text small text-muted">Alamat domisili atau tempat tinggal petani di wilayah desa.</div>
                    </div>

                    <!-- TOMBOL AKSI -->
                    <div class="d-flex align-items-center justify-content-end gap-3 pt-3 border-top">
                        <a href="<?= $base_url ?>dashboard_<?= $role ?>" class="btn btn-light px-4 py-2 rounded-pill fw-semibold border">
                            Batal
                        </a>
                        <button type="submit" class="btn-primary-green px-5 py-2 fs-6" id="btnSubmit">
                            <i class="fas fa-save me-2"></i> Simpan Perubahan
                        </button>
                    </div>

                </div>
            </div>
        </div>
    </form>

</div>

<script>
// Preview Foto Profil secara real-time saat user memilih file gambar
function previewAvatar(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];

        // Validasi ukuran sisi klien (maks 2MB)
        if (file.size > 2 * 1024 * 1024) {
            alert('Ukuran file foto maksimal adalah 2 MB!');
            input.value = '';
            return;
        }

        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('avatarPreview').src = e.target.result;
        };
        reader.readAsDataURL(file);
    }
}

// Efek loading saat form disubmit
document.getElementById('formEditProfil').addEventListener('submit', function() {
    const btn = document.getElementById('btnSubmit');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Menyimpan Perubahan...';
});
</script>

<?php include '../includes/footer.php'; ?>
