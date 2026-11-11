<?php
// includes/responsive-global.php
// CSS Responsif Global: Sidebar, Konten, Font, Umum
?>
<style>
    /* ============================================
       RESPONSIVE GLOBAL
       Sidebar, Konten, Font, Umum
    ============================================ */

    /* ===== TABLET (max-width: 1024px) ===== */
    @media (max-width: 1024px) {
        .sidebar {
            width: 220px;
        }
        .content {
            margin-left: 220px;
            padding: 18px;
        }
        .sidebar ul li a {
            padding: 11px 18px;
            font-size: 13.5px;
        }
        .sidebar .user-info img {
            width: 70px;
            height: 70px;
        }
    }

    /* ===== HP (max-width: 768px) ===== */
    @media (max-width: 768px) {
        /* Font global */
        body {
            font-size: 14px;
        }
        h1 {
            font-size: 1.3rem !important;
        }
        h2 {
            font-size: 1.1rem !important;
        }
        h3 {
            font-size: 1rem !important;
        }

        /* Sidebar (kalo ga pake hamburger) */
        .sidebar {
            width: 200px;
        }
        .content {
            margin-left: 200px;
            padding: 15px;
        }

        /* Tombol global */
        .content a[href*="dashboard"],
        .content a[href*="export"] {
            padding: 7px 16px !important;
            font-size: 12px !important;
            margin-top: 10px;
        }

        /* Profile Card */
        .profile-card {
            padding: 12px 15px !important;
            gap: 12px !important;
            max-width: 100% !important;
        }
        .profile-card img {
            width: 55px !important;
            height: 55px !important;
        }
        .profile-card h2 {
            font-size: 1rem !important;
        }
        .profile-card p {
            font-size: 0.8rem !important;
        }
    }

    /* ===== HP KECIL (max-width: 480px) ===== */
    @media (max-width: 480px) {
        body {
            font-size: 13px;
        }
        h1 {
            font-size: 1.1rem !important;
        }
        h2 {
            font-size: 1rem !important;
        }
        h3 {
            font-size: 0.9rem !important;
        }
        .profile-card img {
            width: 45px !important;
            height: 45px !important;
        }
        .profile-card h2 {
            font-size: 0.9rem !important;
        }
    }
</style>