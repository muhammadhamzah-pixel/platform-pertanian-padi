<?php
include '../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ' . $base_url . 'login');
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
        $current_user_id = $_SESSION['user_id'];
        $is_super_admin = isSuperAdmin();
        
        if ($user_id == $current_user_id) {
            $error = '❌ ' . t('manage_user_cant_edit_self');
        } else {
            $cek_user = mysqli_query($conn, "SELECT role, status, email, nama FROM users WHERE id = '$user_id'");
            $target_user = mysqli_fetch_assoc($cek_user);
            
            if (!$target_user) {
                $error = '❌ ' . t('err_user_not_found');
            } else {
                $target_role = $target_user['role'];
                $target_email = $target_user['email'];
                $target_nama = $target_user['nama'];
                $update_success = false;
                $log_msg = '';
                
                $is_role_action = in_array($action, ['make_admin', 'make_petani', 'make_desa']);
                $is_admin_involved = ($target_role == 'admin' || $action == 'make_admin');
                
                if ($is_role_action && $is_admin_involved && !$is_super_admin) {
                    $error = '🔒 ' . t('manage_user_super_only');
                } else {
                    if ($action == 'activate') {
                        $query = "UPDATE users SET status = 'active' WHERE id = '$user_id'";
                        if (mysqli_query($conn, $query)) {
                            $message = '✅ ' . t('manage_user_activated') . ' <strong>' . htmlspecialchars($target_nama) . '</strong>';
                            $log_msg = "Aktifkan user: $target_email";
                            $update_success = true;
                        } else {
                            $error = '❌ ' . t('failed') . ': ' . mysqli_error($conn);
                        }
                    } elseif ($action == 'block') {
                        $query = "UPDATE users SET status = 'blocked' WHERE id = '$user_id'";
                        if (mysqli_query($conn, $query)) {
                            $message = '✅ ' . t('manage_user_blocked') . ' <strong>' . htmlspecialchars($target_nama) . '</strong>';
                            $log_msg = "Blokir user: $target_email";
                            $update_success = true;
                        } else {
                            $error = '❌ ' . t('failed') . ': ' . mysqli_error($conn);
                        }
                    } elseif ($action == 'pending') {
                        $query = "UPDATE users SET status = 'pending' WHERE id = '$user_id'";
                        if (mysqli_query($conn, $query)) {
                            $message = '✅ ' . t('manage_user_pending') . ' <strong>' . htmlspecialchars($target_nama) . '</strong>';
                            $log_msg = "Set pending user: $target_email";
                            $update_success = true;
                        } else {
                            $error = '❌ ' . t('failed') . ': ' . mysqli_error($conn);
                        }
                    } elseif ($action == 'make_admin') {
                        $query = "UPDATE users SET role = 'admin' WHERE id = '$user_id'";
                        if (mysqli_query($conn, $query)) {
                            $message = '✅ ' . t('manage_user_made_admin') . ' <strong>' . htmlspecialchars($target_nama) . '</strong>';
                            $log_msg = "Jadikan admin: $target_email";
                            $update_success = true;
                        } else {
                            $error = '❌ ' . t('failed') . ': ' . mysqli_error($conn);
                        }
                    } elseif ($action == 'make_petani') {
                        if ($target_role == 'admin') {
                            $cek_admin = mysqli_query($conn, "SELECT COUNT(*) AS total FROM users WHERE role = 'admin'");
                            $total_admin = mysqli_fetch_assoc($cek_admin)['total'];
                            
                            if ($total_admin <= 1) {
                                $error = '❌ ' . t('err_last_admin');
                            } else {
                                $query = "UPDATE users SET role = 'petani' WHERE id = '$user_id'";
                                if (mysqli_query($conn, $query)) {
                                    $message = '✅ ' . t('manage_user_made_petani') . ' <strong>' . htmlspecialchars($target_nama) . '</strong>';
                                    $log_msg = "Jadikan petani: $target_email";
                                    $update_success = true;
                                } else {
                                    $error = '❌ ' . t('failed') . ': ' . mysqli_error($conn);
                                }
                            }
                        } else {
                            $query = "UPDATE users SET role = 'petani' WHERE id = '$user_id'";
                            if (mysqli_query($conn, $query)) {
                                $message = '✅ ' . t('manage_user_made_petani') . ' <strong>' . htmlspecialchars($target_nama) . '</strong>';
                                $log_msg = "Jadikan petani: $target_email";
                                $update_success = true;
                            } else {
                                $error = '❌ ' . t('failed') . ': ' . mysqli_error($conn);
                            }
                        }
                    } elseif ($action == 'make_desa') {
                        $query = "UPDATE users SET role = 'perangkat_desa' WHERE id = '$user_id'";
                        if (mysqli_query($conn, $query)) {
                            $message = '✅ ' . t('manage_user_made_desa') . ' <strong>' . htmlspecialchars($target_nama) . '</strong>';
                            $log_msg = "Jadikan perangkat desa: $target_email";
                            $update_success = true;
                        } else {
                            $error = '❌ ' . t('failed') . ': ' . mysqli_error($conn);
                        }
                    }
                }
                
                if ($update_success) {
                    catat_log($conn, $current_user_id, $_SESSION['email'], $_SESSION['nama'], 'Kelola User', $log_msg);
                    regenerate_csrf_token();
                }
            }
        }
    }
}

$query = "SELECT * FROM users ORDER BY id DESC";
$result = mysqli_query($conn, $query);

// Hitung total ringkasan
$total_users_count = mysqli_num_rows($result);

include '../includes/navbar.php';
?>

<!-- SIGNATURE DASH HERO HEADER -->
<div class="dash-hero-header">
    <div class="dash-hero-inner">
        <div class="dash-hero-title">
            <h1><i class="fas fa-users-cog"></i> <?= t('manage_user_title') ?></h1>
            <p><?= t('manage_user_subtitle') ?></p>
        </div>
        <div class="dash-hero-actions">
            <a href="dashboard_admin.php" class="btn-outline-white">
                <i class="fas fa-arrow-left"></i> Dashboard
            </a>
            <a href="log_aktivitas.php" class="btn-outline-white">
                <i class="fas fa-history"></i> Log Aktivitas
            </a>
        </div>
    </div>
</div>

<!-- MAIN PAGE CONTAINER -->
<div class="main-page-container">

    <!-- ALERTS -->
    <?php if (!isSuperAdmin()): ?>
        <div class="alert-modern alert-modern-warning">
            <i class="fas fa-shield-alt fs-5"></i>
            <div>
                <strong><?= t('manage_user_normal_mode') ?>:</strong> <?= t('manage_user_normal_mode_desc') ?>
                <strong><?= htmlspecialchars(SUPER_ADMIN_EMAIL) ?></strong>
            </div>
        </div>
    <?php endif; ?>

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

    <!-- TABLE CARD CONTAINER -->
    <div class="table-container-modern">
        <!-- Table Toolbar (Like Reference Jagowebdev & Dash UI) -->
        <div class="table-toolbar">
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <div class="position-relative">
                    <i class="fas fa-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                    <input type="text" id="userTableSearch" class="table-search-input ps-5" placeholder="Cari nama, email, role...">
                </div>
                <div class="d-flex align-items-center gap-2">
                    <select id="filterRole" class="form-select form-select-sm" style="width: auto; border-radius: 9px; border-color: var(--border-color);">
                        <option value="">Semua Role</option>
                        <option value="admin">Admin</option>
                        <option value="perangkat_desa">Perangkat Desa</option>
                        <option value="petani">Petani</option>
                    </select>
                    <select id="filterStatus" class="form-select form-select-sm" style="width: auto; border-radius: 9px; border-color: var(--border-color);">
                        <option value="">Semua Status</option>
                        <option value="active">Active</option>
                        <option value="pending">Pending</option>
                        <option value="blocked">Blocked</option>
                    </select>
                </div>
            </div>
            <div class="text-muted small">
                Total: <strong id="userCountDisplay" class="text-dark"><?= $total_users_count ?></strong> Pengguna Terdaftar
            </div>
        </div>

        <!-- Table Data -->
        <div class="table-responsive">
            <table class="table-modern" id="userTable">
                <thead>
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>Pengguna</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th style="text-align: center; width: 220px;"><?= t('manage_user_col_action') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = 1; 
                    while ($row = mysqli_fetch_assoc($result)): 
                        $status = $row['status'] ?? 'pending';
                        $role = $row['role'] ?? 'petani';
                        $is_self = ($row['id'] == $_SESSION['user_id']);
                        $is_target_super = ($row['email'] === SUPER_ADMIN_EMAIL);
                        $can_edit_role = isSuperAdmin() || ($role != 'admin');
                        $user_foto = !empty($row['foto']) ? $row['foto'] : 'defaults.png';
                    ?>
                        <tr data-role="<?= htmlspecialchars($role) ?>" data-status="<?= htmlspecialchars($status) ?>">
                            <td class="text-muted"><?= $no++ ?></td>
                            <td>
                                <div class="user-cell">
                                    <img src="../uploads/foto/<?= htmlspecialchars($user_foto) ?>" alt="Avatar" class="user-avatar-sm" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($row['nama']) ?>&background=059669&color=fff'">
                                    <div>
                                        <strong class="d-block text-dark"><?= htmlspecialchars($row['nama'] ?? '-') ?></strong>
                                        <?php if ($is_self): ?>
                                            <span class="badge bg-primary text-white" style="font-size: 10px; font-weight: 600;">Akun Anda</span>
                                        <?php endif; ?>
                                        <?php if ($is_target_super): ?>
                                            <span class="badge bg-warning text-dark" style="font-size: 10px; font-weight: 700;">Super Admin</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="text-secondary"><?= htmlspecialchars($row['email'] ?? '-') ?></span>
                            </td>
                            <td>
                                <span class="badge-pill-modern badge-role-<?= $role ?>">
                                    <i class="fas fa-<?= $role == 'admin' ? 'user-shield' : ($role == 'perangkat_desa' ? 'landmark' : 'user') ?>"></i>
                                    <?= t('role_' . $role) ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge-pill-modern badge-status-<?= $status ?>">
                                    <i class="fas fa-<?= $status == 'active' ? 'check-circle' : ($status == 'pending' ? 'clock' : 'ban') ?>"></i>
                                    <?= t('status_' . $status) ?>
                                </span>
                            </td>
                            <td style="text-align: center;">
                                <div class="action-toolbar">
                                    <!-- Edit Profile Button (Icon Only) -->
                                    <a href="edit_petani.php?id=<?= $row['id'] ?>" class="btn-action-icon btn-action-edit" title="Edit Profil Pengguna">
                                        <i class="fas fa-pen"></i>
                                    </a>

                                    <?php if ($is_self): ?>
                                        <span class="badge bg-light text-muted" title="Tidak dapat mengubah status akun sendiri">
                                            <i class="fas fa-lock"></i>
                                        </span>
                                    <?php else: ?>
                                        <!-- Status Actions (Icons Only) -->
                                        <?php if ($status != 'active'): ?>
                                            <form method="POST" action="" style="margin: 0; display: inline;">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="user_id" value="<?= $row['id'] ?>">
                                                <input type="hidden" name="action" value="activate">
                                                <button type="submit" class="btn-action-icon btn-action-activate" title="Aktifkan Akun"
                                                        onclick="return confirm('<?= t('confirm_action') ?> Aktifkan <?= htmlspecialchars(addslashes($row['nama'])) ?>?')">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>

                                        <?php if ($status != 'blocked'): ?>
                                            <form method="POST" action="" style="margin: 0; display: inline;">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="user_id" value="<?= $row['id'] ?>">
                                                <input type="hidden" name="action" value="block">
                                                <button type="submit" class="btn-action-icon btn-action-block" title="Blokir Akun"
                                                        onclick="return confirm('<?= t('confirm_action') ?> Blokir <?= htmlspecialchars(addslashes($row['nama'])) ?>?')">
                                                    <i class="fas fa-ban"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>

                                        <?php if ($status != 'pending'): ?>
                                            <form method="POST" action="" style="margin: 0; display: inline;">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="user_id" value="<?= $row['id'] ?>">
                                                <input type="hidden" name="action" value="pending">
                                                <button type="submit" class="btn-action-icon btn-action-pending" title="Set Status Pending"
                                                        onclick="return confirm('<?= t('confirm_action') ?> Jadikan Pending <?= htmlspecialchars(addslashes($row['nama'])) ?>?')">
                                                    <i class="fas fa-clock"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>

                                        <!-- Role Actions (Icons Only) -->
                                        <?php if ($can_edit_role): ?>
                                            <?php if ($role != 'admin'): ?>
                                                <form method="POST" action="" style="margin: 0; display: inline;">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="user_id" value="<?= $row['id'] ?>">
                                                    <input type="hidden" name="action" value="make_admin">
                                                    <button type="submit" class="btn-action-icon btn-action-role-admin" title="Jadikan Administrator"
                                                            onclick="return confirm('<?= t('confirm_action') ?> Jadikan Admin <?= htmlspecialchars(addslashes($row['nama'])) ?>?')">
                                                        <i class="fas fa-user-shield"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>

                                            <?php if ($role != 'petani'): ?>
                                                <form method="POST" action="" style="margin: 0; display: inline;">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="user_id" value="<?= $row['id'] ?>">
                                                    <input type="hidden" name="action" value="make_petani">
                                                    <button type="submit" class="btn-action-icon btn-action-role-petani" title="Jadikan Petani"
                                                            onclick="return confirm('<?= t('confirm_action') ?> Jadikan Petani <?= htmlspecialchars(addslashes($row['nama'])) ?>?')">
                                                        <i class="fas fa-user"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>

                                            <?php if ($role != 'perangkat_desa'): ?>
                                                <form method="POST" action="" style="margin: 0; display: inline;">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="user_id" value="<?= $row['id'] ?>">
                                                    <input type="hidden" name="action" value="make_desa">
                                                    <button type="submit" class="btn-action-icon btn-action-role-desa" title="Jadikan Perangkat Desa"
                                                            onclick="return confirm('<?= t('confirm_action') ?> Jadikan Perangkat Desa <?= htmlspecialchars(addslashes($row['nama'])) ?>?')">
                                                        <i class="fas fa-landmark"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- CLIENT-SIDE FILTER & SEARCH SCRIPT -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('userTableSearch');
    const roleSelect = document.getElementById('filterRole');
    const statusSelect = document.getElementById('filterStatus');
    const tableRows = document.querySelectorAll('#userTable tbody tr');
    const countDisplay = document.getElementById('userCountDisplay');

    function filterTable() {
        const query = searchInput.value.toLowerCase().trim();
        const roleFilter = roleSelect.value;
        const statusFilter = statusSelect.value;
        let visibleCount = 0;

        tableRows.forEach(row => {
            const text = row.innerText.toLowerCase();
            const rowRole = row.getAttribute('data-role');
            const rowStatus = row.getAttribute('data-status');

            const matchQuery = text.includes(query);
            const matchRole = !roleFilter || rowRole === roleFilter;
            const matchStatus = !statusFilter || rowStatus === statusFilter;

            if (matchQuery && matchRole && matchStatus) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        if (countDisplay) {
            countDisplay.innerText = visibleCount;
        }
    }

    searchInput.addEventListener('input', filterTable);
    roleSelect.addEventListener('change', filterTable);
    statusSelect.addEventListener('change', filterTable);
});
</script>

<?php include '../includes/footer.php'; ?>