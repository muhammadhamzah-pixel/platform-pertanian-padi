<?php
// includes/navbar.php
require_once __DIR__ . '/lang.php';

if (!isset($_SESSION['user_id'])) {
    return;
}

$role = $_SESSION['role'];
$nama = $_SESSION['nama'];
$user_id = $_SESSION['user_id'];
$current_page = basename($_SERVER['PHP_SELF']);

// Ambil foto dari database atau session
$foto = $_SESSION['foto'] ?? 'defaults.png';
if (isset($conn)) {
    $foto_res = mysqli_query($conn, "SELECT foto FROM users WHERE id = '$user_id'");
    if ($foto_res && $row = mysqli_fetch_assoc($foto_res)) {
        if (!empty($row['foto'])) {
            $foto = $row['foto'];
            $_SESSION['foto'] = $foto;
        }
    }
}

// Deteksi tanggal hari ini (Bahasa Indonesia/Lokal)
$hari_ini = date('d M Y');

// Sinkronisasi dan hitung notifikasi unread untuk lonceng topbar (khusus petani)
$navbar_unread_notif = 0;
if (isset($conn) && isset($user_id) && $role === 'petani') {
    if (function_exists('sync_notifikasi_petani')) {
        sync_notifikasi_petani($conn, $user_id);
    }
    $q_notif_bar = mysqli_query($conn, "SELECT COUNT(*) AS total FROM notifikasi WHERE user_id = '$user_id' AND is_read = 0");
    if ($q_notif_bar && $row_nb = mysqli_fetch_assoc($q_notif_bar)) {
        $navbar_unread_notif = (int)$row_nb['total'];
    }
}

// Pastikan Header HTML, CSS, Font & Meta dimuat ke browser
require_once __DIR__ . '/header.php';
?>

<!-- OVERLAY FOR MOBILE SIDEBAR -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<!-- SIDEBAR (DASH UI SLEEK DARK STYLE) -->
<aside class="sidebar" id="sidebar">
    <!-- Brand -->
    <a href="dashboard_<?= $role ?>.php" class="brand">
        <div class="brand-icon">
            <i class="fas fa-seedling"></i>
        </div>
        <div class="brand-text">
            <span class="title"><?= t('app_name') ?></span>
            <span>AgriPlatform v2.0</span>
        </div>
    </a>

    <!-- User Profile Widget -->
    <div class="user-info">
        <div class="user-avatar-wrap">
            <img src="../uploads/foto/<?= htmlspecialchars($foto) ?>" alt="Foto Profil" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($nama) ?>&background=059669&color=fff'">
            <div class="online-dot" title="Online"></div>
        </div>
        <div class="user-details">
            <strong><?= htmlspecialchars($nama) ?></strong>
            <small><i class="fas fa-shield-alt"></i> <?= t('role_' . $role) ?></small>
        </div>
    </div>

    <!-- Navigation Menu Items -->
    <nav class="sidebar-nav">
        <!-- Section: Menu Utama -->
        <div class="menu-section-title">Menu Utama</div>
        <ul>
            <?php if ($role == 'petani'): ?>
                <li>
                    <a href="dashboard_petani.php" class="<?= $current_page == 'dashboard_petani.php' ? 'active' : '' ?>">
                        <i class="fas fa-chart-pie nav-icon"></i>
                        <span><?= t('menu_dashboard') ?></span>
                    </a>
                </li>
                <li>
                    <a href="data_lahan.php" class="<?= $current_page == 'data_lahan.php' ? 'active' : '' ?>">
                        <i class="fas fa-layer-group nav-icon"></i>
                        <span><?= t('menu_land_data') ?></span>
                    </a>
                </li>
                <li>
                    <a href="tambah_lahan.php" class="<?= $current_page == 'tambah_lahan.php' ? 'active' : '' ?>">
                        <i class="fas fa-plus-circle nav-icon"></i>
                        <span><?= t('menu_add_land') ?></span>
                    </a>
                </li>
                <li>
                    <a href="ai_chat.php" class="<?= $current_page == 'ai_chat.php' ? 'active' : '' ?>">
                        <i class="fas fa-robot nav-icon" style="color: #10b981;"></i>
                        <span>Sahabat Tani AI</span>
                    </a>
                </li>
                <li>
                    <a href="notifikasi.php" class="<?= $current_page == 'notifikasi.php' ? 'active' : '' ?>">
                        <i class="fas fa-bell nav-icon"></i>
                        <span><?= t('menu_notification') ?></span>
                    </a>
                </li>

            <?php elseif ($role == 'perangkat_desa'): ?>
                <li>
                    <a href="dashboard_desa.php" class="<?= $current_page == 'dashboard_desa.php' ? 'active' : '' ?>">
                        <i class="fas fa-chart-pie nav-icon"></i>
                        <span><?= t('menu_dashboard') ?></span>
                    </a>
                </li>
                <li>
                    <a href="verifikasi_akun.php" class="<?= $current_page == 'verifikasi_akun.php' ? 'active' : '' ?>">
                        <i class="fas fa-user-check nav-icon"></i>
                        <span><?= t('menu_verify_account') ?></span>
                    </a>
                </li>
                <li>
                    <a href="data_semua_petani.php" class="<?= $current_page == 'data_semua_petani.php' ? 'active' : '' ?>">
                        <i class="fas fa-users nav-icon"></i>
                        <span><?= t('menu_farmer_data') ?></span>
                    </a>
                </li>
                <li>
                    <a href="rekap_desa.php" class="<?= $current_page == 'rekap_desa.php' ? 'active' : '' ?>">
                        <i class="fas fa-file-invoice nav-icon"></i>
                        <span><?= t('menu_village_recap') ?></span>
                    </a>
                </li>

            <?php elseif ($role == 'admin'): ?>
                <li>
                    <a href="dashboard_admin.php" class="<?= $current_page == 'dashboard_admin.php' ? 'active' : '' ?>">
                        <i class="fas fa-chart-pie nav-icon"></i>
                        <span><?= t('menu_dashboard') ?></span>
                    </a>
                </li>
                <li>
                    <a href="security_analytics.php" class="<?= $current_page == 'security_analytics.php' ? 'active' : '' ?>">
                        <i class="fas fa-shield-virus nav-icon"></i>
                        <span><?= t('menu_security') ?></span>
                    </a>
                </li>
                <li>
                    <a href="kelola_user.php" class="<?= $current_page == 'kelola_user.php' ? 'active' : '' ?>">
                        <i class="fas fa-user-cog nav-icon"></i>
                        <span><?= t('menu_manage_user') ?></span>
                    </a>
                </li>
                <li>
                    <a href="user_terblokir.php" class="<?= $current_page == 'user_terblokir.php' ? 'active' : '' ?>">
                        <i class="fas fa-user-slash nav-icon"></i>
                        <span><?= t('menu_blocked_user') ?></span>
                    </a>
                </li>
                <li>
                    <a href="setting_desa.php" class="<?= $current_page == 'setting_desa.php' ? 'active' : '' ?>">
                        <i class="fas fa-map-marked-alt nav-icon"></i>
                        <span><?= t('menu_village_setting') ?></span>
                    </a>
                </li>
                <li>
                    <a href="backup_data.php" class="<?= $current_page == 'backup_data.php' ? 'active' : '' ?>">
                        <i class="fas fa-database nav-icon"></i>
                        <span><?= t('menu_backup_data') ?></span>
                    </a>
                </li>
                <li>
                    <a href="log_aktivitas.php" class="<?= $current_page == 'log_aktivitas.php' ? 'active' : '' ?>">
                        <i class="fas fa-history nav-icon"></i>
                        <span><?= t('menu_activity_log') ?></span>
                    </a>
                </li>
            <?php endif; ?>
        </ul>

        <!-- Section: Pengaturan Akun -->
        <div class="menu-section-title">Akun & Sistem</div>
        <ul>
            <?php if ($role == 'petani'): ?>
                <li>
                    <a href="setting_notifikasi.php" class="<?= $current_page == 'setting_notifikasi.php' ? 'active' : '' ?>">
                        <i class="fas fa-sliders-h nav-icon"></i>
                        <span>Setting Notifikasi</span>
                    </a>
                </li>
            <?php endif; ?>
            <li>
                <a href="ganti_password.php" class="<?= $current_page == 'ganti_password.php' ? 'active' : '' ?>">
                    <i class="fas fa-key nav-icon"></i>
                    <span><?= t('menu_change_password') ?></span>
                </a>
            </li>
            <li>
                <a href="edit_foto.php" class="<?= $current_page == 'edit_foto.php' ? 'active' : '' ?>">
                    <i class="fas fa-image nav-icon"></i>
                    <span><?= t('menu_change_photo') ?></span>
                </a>
            </li>
            <li>
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt nav-icon"></i>
                    <span><?= t('menu_logout') ?></span>
                </a>
            </li>
        </ul>
    </nav>
</aside>

<!-- MAIN CONTENT WRAPPER -->
<main class="content" id="mainContent">
    <!-- STICKY TOPBAR HEADER -->
    <header class="main-topbar">
        <div class="left-group">
            <button type="button" class="btn-sidebar-toggle" onclick="toggleSidebar()" aria-label="Toggle Navigation">
                <i class="fas fa-bars"></i>
            </button>
            <div class="topbar-search">
                <i class="fas fa-search search-icon"></i>
                <input type="text" id="globalSearchInput" placeholder="Cari data atau fitur..." aria-label="Search">
            </div>
        </div>
        <div class="right-group">
            <div class="topbar-date-chip">
                <i class="far fa-calendar-alt" style="color: var(--primary);"></i>
                <span><?= $hari_ini ?></span>
            </div>

            <?php if ($role === 'petani'): ?>
                <!-- Ikon Lonceng Notifikasi Khusus Petani -->
                <a href="notifikasi.php" class="topbar-notif-btn" title="Notifikasi <?= $navbar_unread_notif > 0 ? "($navbar_unread_notif pesan baru)" : '' ?>">
                    <i class="far fa-bell"></i>
                    <?php if ($navbar_unread_notif > 0): ?>
                        <span class="topbar-notif-dot" title="<?= $navbar_unread_notif ?> belum dibaca"></span>
                    <?php endif; ?>
                </a>
            <?php endif; ?>
            
            <a href="edit_foto.php" class="d-flex align-items-center gap-2 text-decoration-none">
                <img src="../uploads/foto/<?= htmlspecialchars($foto) ?>" alt="Avatar" class="user-avatar-sm" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($nama) ?>&background=059669&color=fff'">
            </a>
        </div>
    </header>

<?php
// Load Floating Chatbot Widget (Pojok Kanan Bawah)
require_once __DIR__ . '/floating_chat.php';
?>

<!-- SCRIPT SIDEBAR TOGGLE & TOOLTIPS -->
<script>
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        const content = document.getElementById('mainContent');
        
        if (window.innerWidth <= 1024) {
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        } else {
            sidebar.classList.toggle('collapsed');
            content.classList.toggle('expanded');
        }
    }

    function closeSidebar() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        
        sidebar.classList.remove('active');
        overlay.classList.remove('active');
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Close sidebar on menu click in mobile
        const sidebarLinks = document.querySelectorAll('.sidebar-nav a');
        sidebarLinks.forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 1024) {
                    closeSidebar();
                }
            });
        });
    });
</script>