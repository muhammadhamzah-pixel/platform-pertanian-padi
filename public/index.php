<?php
include '../includes/config.php';
include '../includes/header.php';

// Ambil statistik publik riil
$stat_petani = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM users WHERE role='petani'"))['total'] ?? 0;
$stat_lahan = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total, COALESCE(SUM(luas), 0) AS luas FROM lahan"));
$total_lahan_count = $stat_lahan['total'] ?? 0;
$total_luas_m2 = $stat_lahan['luas'] ?? 0;
$desa_setting = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM desa_settings LIMIT 1"));
$nama_desa = !empty($desa_setting['nama_desa']) ? $desa_setting['nama_desa'] : 'Desa Digital';
?>

<style>
    /* ===== MODERN LANDING PAGE SUITE ===== */
    body {
        background-color: #f8fafc;
        color: #0f172a;
        font-family: 'Plus Jakarta Sans', sans-serif;
    }

    /* Public Landing Header Navigation */
    .landing-nav {
        position: sticky;
        top: 0;
        z-index: 1000;
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border-bottom: 1px solid rgba(226, 232, 240, 0.8);
        padding: 16px 0;
    }
    .landing-nav .container {
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .landing-brand {
        display: flex;
        align-items: center;
        gap: 12px;
        text-decoration: none;
        color: #0f172a;
    }
    .landing-brand .brand-logo {
        width: 42px;
        height: 42px;
        border-radius: 12px;
        background: linear-gradient(135deg, #10b981, #059669);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.3rem;
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.35);
    }
    .landing-brand .brand-title {
        font-size: 1.15rem;
        font-weight: 800;
        letter-spacing: -0.02em;
        line-height: 1.1;
    }
    .landing-brand .brand-title span {
        display: block;
        font-size: 0.72rem;
        color: #059669;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    /* Landing Hero */
    .landing-hero {
        background: linear-gradient(145deg, #064e3b 0%, #065f46 50%, #059669 100%);
        color: #ffffff;
        padding: 90px 20px 110px 20px;
        position: relative;
        overflow: hidden;
        text-align: center;
    }
    .landing-hero::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -20%;
        width: 140%;
        height: 200%;
        background: radial-gradient(circle at center, rgba(16, 185, 129, 0.15) 0%, transparent 60%);
        pointer-events: none;
    }
    .hero-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: rgba(255, 255, 255, 0.15);
        backdrop-filter: blur(8px);
        border: 1px solid rgba(255, 255, 255, 0.25);
        color: #ffffff;
        padding: 6px 16px;
        border-radius: 9999px;
        font-size: 0.82rem;
        font-weight: 600;
        margin-bottom: 24px;
        letter-spacing: 0.03em;
    }
    .landing-hero h1 {
        font-size: 3rem;
        font-weight: 800;
        letter-spacing: -0.03em;
        line-height: 1.15;
        max-width: 850px;
        margin: 0 auto 20px auto;
    }
    .landing-hero p {
        font-size: 1.15rem;
        color: rgba(255, 255, 255, 0.88);
        max-width: 680px;
        margin: 0 auto 36px auto;
        line-height: 1.6;
    }
    .hero-btn-group {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 16px;
        flex-wrap: wrap;
    }
    .btn-hero-primary {
        background: #ffffff;
        color: #064e3b !important;
        font-weight: 700;
        padding: 13px 32px;
        border-radius: 9999px;
        font-size: 0.95rem;
        box-shadow: 0 10px 25px -4px rgba(0, 0, 0, 0.2);
        transition: all 0.25s ease;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    .btn-hero-primary:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 30px -4px rgba(0, 0, 0, 0.3);
        background: #ecfdf5;
        color: #064e3b !important;
    }
    .btn-hero-secondary {
        background: rgba(255, 255, 255, 0.12);
        color: #ffffff !important;
        border: 1px solid rgba(255, 255, 255, 0.4);
        backdrop-filter: blur(6px);
        font-weight: 600;
        padding: 13px 30px;
        border-radius: 9999px;
        font-size: 0.95rem;
        transition: all 0.25s ease;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    .btn-hero-secondary:hover {
        background: rgba(255, 255, 255, 0.25);
        transform: translateY(-3px);
    }

    /* Floating Stats Bar */
    .landing-stats-bar {
        margin-top: -50px;
        position: relative;
        z-index: 10;
        margin-bottom: 70px;
    }
    .stats-bar-card {
        background: #ffffff;
        border-radius: 20px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 20px 40px -10px rgba(15, 23, 42, 0.08);
        padding: 24px 30px;
    }

    /* Feature Cards */
    .landing-card-feature {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 20px;
        padding: 32px 26px;
        box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.04);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        height: 100%;
        cursor: pointer;
    }
    .landing-card-feature:hover {
        transform: translateY(-6px);
        box-shadow: 0 20px 35px -8px rgba(5, 150, 105, 0.12);
        border-color: #a7f3d0;
    }



    /* Team Section */
    .team-avatar-pill {
        display: flex;
        align-items: center;
        gap: 12px;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 9999px;
        padding: 10px 20px;
        box-shadow: 0 4px 12px -2px rgba(15, 23, 42, 0.04);
    }

    /* Footer */
    .landing-footer {
        background: #0f172a;
        color: #94a3b8;
        padding: 60px 0 30px 0;
        margin-top: 80px;
    }
    .landing-footer a {
        color: #cbd5e1;
    }
    .landing-footer a:hover {
        color: #10b981;
    }

    @media (max-width: 768px) {
        .landing-hero h1 { font-size: 2.1rem; }
        .landing-hero p { font-size: 1rem; }
        .hero-btn-group { flex-direction: column; }
        .btn-hero-primary, .btn-hero-secondary { width: 100%; justify-content: center; }
    }
</style>

<!-- PUBLIC NAVBAR -->
<nav class="landing-nav">
    <div class="container">
        <a href="index.php" class="landing-brand">
            <div class="brand-logo">
                <i class="fas fa-seedling"></i>
            </div>
            <div class="brand-title">
                <?= t('app_name') ?>
                <span>Sistem Padi Desa</span>
            </div>
        </a>

        <div class="d-flex align-items-center gap-3">
            <a href="../pages/login.php" class="btn btn-outline-success rounded-pill px-4 fw-bold" style="font-size: 14px;">
                <i class="fas fa-sign-in-alt me-1"></i> <?= t('btn_login') ?>
            </a>
            <a href="../pages/register.php" class="btn btn-success rounded-pill px-4 fw-bold d-none d-sm-inline-flex" style="background: #059669; border-color: #059669; font-size: 14px;">
                <?= t('btn_register') ?>
            </a>
        </div>
    </div>
</nav>

<!-- HERO SECTION -->
<section class="landing-hero">
    <div class="container position-relative z-2">
        <div class="hero-badge">
            <i class="fas fa-sparkles text-warning"></i> Solusi Digital Ketahanan Pangan Desa
        </div>
        <h1>Modernisasi Pertanian Padi dengan Presisi &amp; Kecerdasan Buatan</h1>
        <p>Satu platform cerdas terpadu untuk monitoring siklus sawah petani, rekapitulasi aparatur desa, dan rekomendasi budidaya bersama Asisten AI.</p>
        
        <div class="hero-btn-group">
            <a href="../pages/register.php" class="btn-hero-primary">
                <i class="fas fa-user-plus"></i> Daftar Akun Petani Sekarang
            </a>
            <a href="../pages/login.php" class="btn-hero-secondary">
                <i class="fas fa-sign-in-alt"></i> Masuk ke Dashboard
            </a>
        </div>
    </div>
</section>

<!-- FLOATING STATS BAR -->
<div class="container landing-stats-bar">
    <div class="stats-bar-card">
        <div class="row g-4 text-center">
            <div class="col-6 col-md-3 border-end">
                <h3 class="fw-bold text-dark mb-0"><?= number_format($stat_petani) ?>+</h3>
                <small class="text-muted">Petani Terdaftar</small>
            </div>
            <div class="col-6 col-md-3 border-end">
                <h3 class="fw-bold text-dark mb-0"><?= number_format($total_lahan_count) ?></h3>
                <small class="text-muted">Petak Sawah Aktif</small>
            </div>
            <div class="col-6 col-md-3 border-end">
                <h3 class="fw-bold text-dark mb-0"><?= number_format($total_luas_m2, 0) ?> m²</h3>
                <small class="text-muted">Total Luas Monitoring</small>
            </div>
            <div class="col-6 col-md-3">
                <h3 class="fw-bold text-success mb-0">24/7</h3>
                <small class="text-muted">Asisten AI Budidaya</small>
            </div>
        </div>
    </div>
</div>

<!-- FITUR UNGGULAN SECTION -->
<section class="container py-4">
    <div class="text-center mb-5">
        <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill fw-bold mb-2">
            FITUR UTAMA
        </span>
        <h2 class="fw-bold text-dark">Solusi Terlengkap Pertanian Presisi</h2>
        <p class="text-muted">Dirancang khusus untuk kebutuhan petani lokal dan transparansi aparatur desa.</p>
    </div>

    <div class="row g-4">
        <!-- Fitur 1 -->
        <div class="col-md-6 col-lg-3">
            <div class="landing-card-feature" onclick="openModal('<?= t('feature_data') ?>', 'fas fa-chart-pie', '<?= t('feature_data_desc') ?>')">
                <div class="stat-card-icon stat-icon-emerald mb-3" style="width: 54px; height: 54px; font-size: 1.4rem;">
                    <i class="fas fa-layer-group"></i>
                </div>
                <h5 class="fw-bold text-dark mb-2"><?= t('feature_data') ?></h5>
                <p class="text-secondary small mb-0"><?= t('feature_data_desc') ?></p>
            </div>
        </div>

        <!-- Fitur 2 -->
        <div class="col-md-6 col-lg-3">
            <div class="landing-card-feature" onclick="openModal('<?= t('feature_monitoring') ?>', 'fas fa-seedling', '<?= t('feature_monitoring_desc') ?>')">
                <div class="stat-card-icon stat-icon-amber mb-3" style="width: 54px; height: 54px; font-size: 1.4rem;">
                    <i class="fas fa-seedling"></i>
                </div>
                <h5 class="fw-bold text-dark mb-2"><?= t('feature_monitoring') ?></h5>
                <p class="text-secondary small mb-0"><?= t('feature_monitoring_desc') ?></p>
            </div>
        </div>

        <!-- Fitur 3 -->
        <div class="col-md-6 col-lg-3">
            <div class="landing-card-feature" onclick="openModal('<?= t('feature_ai') ?>', 'fas fa-robot', '<?= t('feature_ai_desc') ?>')">
                <div class="stat-card-icon stat-icon-blue mb-3" style="width: 54px; height: 54px; font-size: 1.4rem;">
                    <i class="fas fa-robot"></i>
                </div>
                <h5 class="fw-bold text-dark mb-2"><?= t('feature_ai') ?></h5>
                <p class="text-secondary small mb-0"><?= t('feature_ai_desc') ?></p>
            </div>
        </div>

        <!-- Fitur 4 -->
        <div class="col-md-6 col-lg-3">
            <div class="landing-card-feature" onclick="openModal('<?= t('feature_about') ?>', 'fas fa-users-cog', '<?= t('feature_about_desc') ?>')">
                <div class="stat-card-icon stat-icon-rose mb-3" style="width: 54px; height: 54px; font-size: 1.4rem;">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h5 class="fw-bold text-dark mb-2"><?= t('feature_about') ?></h5>
                <p class="text-secondary small mb-0"><?= t('feature_about_desc') ?></p>
            </div>
        </div>
    </div>
</section>



<!-- TIM PENGEMBANG (PRUS & PRAY) -->
<section class="container py-5 text-center">
    <div class="mb-4">
        <h4 class="fw-bold text-dark">Dikembangkan oleh Tim Prus &amp; Pray</h4>
        <p class="text-muted small">Inovasi karya anak bangsa untuk masa depan agrikultur Indonesia</p>
    </div>

    <div class="d-flex justify-content-center flex-wrap gap-3">
        <div class="team-avatar-pill">
            <img src="https://ui-avatars.com/api/?name=Muhammad+Hamzah&background=059669&color=fff" class="rounded-circle" width="28" height="28" alt="Hamzah">
            <strong class="text-dark small">Muhammad Hamzah</strong>
        </div>
        <div class="team-avatar-pill">
            <img src="https://ui-avatars.com/api/?name=Muhyi+Sabil&background=0284c7&color=fff" class="rounded-circle" width="28" height="28" alt="Muhyi">
            <strong class="text-dark small">Muhyi Sabil</strong>
        </div>
        <div class="team-avatar-pill">
            <img src="https://ui-avatars.com/api/?name=Mutlifatul+Husna&background=d97706&color=fff" class="rounded-circle" width="28" height="28" alt="Husna">
            <strong class="text-dark small">Mutlifatul Husna</strong>
        </div>
        <div class="team-avatar-pill">
            <img src="https://ui-avatars.com/api/?name=Risma&background=e11d48&color=fff" class="rounded-circle" width="28" height="28" alt="Risma">
            <strong class="text-dark small">Risma</strong>
        </div>
        <div class="team-avatar-pill">
            <img src="https://ui-avatars.com/api/?name=Nadia&background=7c3aed&color=fff" class="rounded-circle" width="28" height="28" alt="Nadia">
            <strong class="text-dark small">Nadia</strong>
        </div>
    </div>
</section>

<!-- FOOTER -->
<footer class="landing-footer">
    <div class="container text-center">
        <div class="d-flex align-items-center justify-content-center gap-2 mb-3">
            <div class="brand-logo" style="width: 32px; height: 32px; border-radius: 8px; background: #059669; display: flex; align-items: center; justify-content: center; color: white;">
                <i class="fas fa-seedling"></i>
            </div>
            <strong class="text-white"><?= t('app_name') ?></strong>
        </div>
        <p class="small text-muted mb-2">&copy; 2026 <strong>Prus &amp; Pray</strong>. Seluruh hak cipta dilindungi.</p>
        <p class="small text-muted mb-0">Platform Manajemen Pertanian Berkelanjutan &amp; Smart Village Ecosystem.</p>
    </div>
</footer>

<!-- MODAL INFO DINAMIS -->
<div class="modal fade" id="featureModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold text-dark" id="featureModalTitle">Detail Fitur</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-4">
                <div class="text-center mb-3">
                    <div id="featureModalIcon" class="stat-card-icon stat-icon-emerald mx-auto" style="width: 60px; height: 60px; font-size: 1.8rem;">
                        <i class="fas fa-info-circle"></i>
                    </div>
                </div>
                <p id="featureModalDesc" class="text-secondary text-center leading-relaxed"></p>
            </div>
            <div class="modal-footer border-top-0 pt-0 justify-content-center">
                <button type="button" class="btn btn-secondary-modern px-4" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
function openModal(title, iconClass, desc) {
    document.getElementById('featureModalTitle').innerText = title;
    document.getElementById('featureModalDesc').innerText = desc;
    document.getElementById('featureModalIcon').innerHTML = '<i class="' + iconClass + '"></i>';
    var myModal = new bootstrap.Modal(document.getElementById('featureModal'));
    myModal.show();
}
</script>

<?php include '../includes/footer.php'; ?>