<?php
// includes/navbar.php
require_once __DIR__ . '/lang.php';

if (!isset($_SESSION['user_id'])) {
    return;
}

// Pastikan $base_url selalu tersedia
if (!isset($base_url)) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host_url = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $folder_project = '/PLATFORM%20PERTANIAN%20PADI/';
    $base_url = $protocol . '://' . $host_url . $folder_project;
}

$role = $_SESSION['role'];
$nama = $_SESSION['nama'];
$user_id = $_SESSION['user_id'];
$current_page = basename($_SERVER['PHP_SELF']);
$current_clean = str_replace('.php', '', $current_page);

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
    <a href="<?= $base_url ?>dashboard_<?= $role ?>" class="brand">
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
            <img src="<?= $base_url ?>uploads/foto/<?= htmlspecialchars($foto) ?>" alt="Foto Profil" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($nama) ?>&background=059669&color=fff'">
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
                    <a href="<?= $base_url ?>dashboard_petani" class="<?= $current_clean == 'dashboard_petani' ? 'active' : '' ?>">
                        <i class="fas fa-chart-pie nav-icon"></i>
                        <span><?= t('menu_dashboard') ?></span>
                    </a>
                </li>
                <li>
                    <a href="<?= $base_url ?>data_lahan" class="<?= $current_clean == 'data_lahan' ? 'active' : '' ?>">
                        <i class="fas fa-layer-group nav-icon"></i>
                        <span><?= t('menu_land_data') ?></span>
                    </a>
                </li>
                <li>
                    <a href="<?= $base_url ?>tambah_lahan" class="<?= $current_clean == 'tambah_lahan' ? 'active' : '' ?>">
                        <i class="fas fa-plus-circle nav-icon"></i>
                        <span><?= t('menu_add_land') ?></span>
                    </a>
                </li>
                <li>
                    <a href="<?= $base_url ?>ai_chat" class="<?= $current_clean == 'ai_chat' ? 'active' : '' ?>">
                        <i class="fas fa-robot nav-icon" style="color: #10b981;"></i>
                        <span>Sahabat Tani AI</span>
                    </a>
                </li>
                <li>
                    <a href="<?= $base_url ?>notifikasi" class="<?= $current_clean == 'notifikasi' ? 'active' : '' ?>">
                        <i class="fas fa-bell nav-icon"></i>
                        <span><?= t('menu_notification') ?></span>
                    </a>
                </li>

            <?php elseif ($role == 'perangkat_desa'): ?>
                <li>
                    <a href="<?= $base_url ?>dashboard_desa" class="<?= $current_clean == 'dashboard_desa' ? 'active' : '' ?>">
                        <i class="fas fa-chart-pie nav-icon"></i>
                        <span><?= t('menu_dashboard') ?></span>
                    </a>
                </li>
                <li>
                    <a href="<?= $base_url ?>verifikasi_akun" class="<?= $current_clean == 'verifikasi_akun' ? 'active' : '' ?>">
                        <i class="fas fa-user-check nav-icon"></i>
                        <span><?= t('menu_verify_account') ?></span>
                    </a>
                </li>
                <li>
                    <a href="<?= $base_url ?>data_semua_petani" class="<?= $current_clean == 'data_semua_petani' ? 'active' : '' ?>">
                        <i class="fas fa-users nav-icon"></i>
                        <span><?= t('menu_farmer_data') ?></span>
                    </a>
                </li>
                <li>
                    <a href="<?= $base_url ?>rekap_desa" class="<?= $current_clean == 'rekap_desa' ? 'active' : '' ?>">
                        <i class="fas fa-file-invoice nav-icon"></i>
                        <span><?= t('menu_village_recap') ?></span>
                    </a>
                </li>

            <?php elseif ($role == 'admin'): ?>
                <li>
                    <a href="<?= $base_url ?>dashboard_admin" class="<?= $current_clean == 'dashboard_admin' ? 'active' : '' ?>">
                        <i class="fas fa-chart-pie nav-icon"></i>
                        <span><?= t('menu_dashboard') ?></span>
                    </a>
                </li>
                <li>
                    <a href="<?= $base_url ?>konfigurasi_ai" class="<?= $current_clean == 'konfigurasi_ai' ? 'active' : '' ?>">
                        <i class="fas fa-microchip nav-icon" style="color: #10b981;"></i>
                        <span>Konfigurasi AI</span>
                    </a>
                </li>
                <li>
                    <a href="<?= $base_url ?>security_analytics" class="<?= $current_clean == 'security_analytics' ? 'active' : '' ?>">
                        <i class="fas fa-shield-virus nav-icon"></i>
                        <span><?= t('menu_security') ?></span>
                    </a>
                </li>
                <li>
                    <a href="<?= $base_url ?>kelola_user" class="<?= $current_clean == 'kelola_user' ? 'active' : '' ?>">
                        <i class="fas fa-user-cog nav-icon"></i>
                        <span><?= t('menu_manage_user') ?></span>
                    </a>
                </li>
                <li>
                    <a href="<?= $base_url ?>user_terblokir" class="<?= $current_clean == 'user_terblokir' ? 'active' : '' ?>">
                        <i class="fas fa-user-slash nav-icon"></i>
                        <span><?= t('menu_blocked_user') ?></span>
                    </a>
                </li>
                <li>
                    <a href="<?= $base_url ?>setting_desa" class="<?= $current_clean == 'setting_desa' ? 'active' : '' ?>">
                        <i class="fas fa-map-marked-alt nav-icon"></i>
                        <span><?= t('menu_village_setting') ?></span>
                    </a>
                </li>
                <li>
                    <a href="<?= $base_url ?>backup_data" class="<?= $current_clean == 'backup_data' ? 'active' : '' ?>">
                        <i class="fas fa-database nav-icon"></i>
                        <span><?= t('menu_backup_data') ?></span>
                    </a>
                </li>
                <li>
                    <a href="<?= $base_url ?>log_aktivitas" class="<?= $current_clean == 'log_aktivitas' ? 'active' : '' ?>">
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
                    <a href="<?= $base_url ?>setting_notifikasi" class="<?= $current_clean == 'setting_notifikasi' ? 'active' : '' ?>">
                        <i class="fas fa-sliders-h nav-icon"></i>
                        <span>Setting Notifikasi</span>
                    </a>
                </li>
            <?php endif; ?>
            <li>
                <a href="<?= $base_url ?>ganti_password" class="<?= $current_clean == 'ganti_password' ? 'active' : '' ?>">
                    <i class="fas fa-key nav-icon"></i>
                    <span><?= t('menu_change_password') ?></span>
                </a>
            </li>
            <li>
                <a href="<?= $base_url ?>edit_foto" class="<?= $current_clean == 'edit_foto' ? 'active' : '' ?>">
                    <i class="fas fa-image nav-icon"></i>
                    <span><?= t('menu_change_photo') ?></span>
                </a>
            </li>
            <li>
                <a href="<?= $base_url ?>logout" class="logout-btn">
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
                <a href="<?= $base_url ?>notifikasi" class="topbar-notif-btn" title="Notifikasi <?= $navbar_unread_notif > 0 ? "($navbar_unread_notif pesan baru)" : '' ?>">
                    <i class="far fa-bell"></i>
                    <?php if ($navbar_unread_notif > 0): ?>
                        <span class="topbar-notif-dot" title="<?= $navbar_unread_notif ?> belum dibaca"></span>
                    <?php endif; ?>
                </a>
            <?php endif; ?>
            
            <a href="<?= $base_url ?>edit_foto" class="d-flex align-items-center gap-2 text-decoration-none">
                <img src="<?= $base_url ?>uploads/foto/<?= htmlspecialchars($foto) ?>" alt="Avatar" class="user-avatar-sm" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($nama) ?>&background=059669&color=fff'">
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
