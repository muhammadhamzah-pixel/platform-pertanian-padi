<?php
// includes/responsive-card.php
// CSS Responsif: Card Statistik & Menu Dashboard
?>
<style>
    /* ============================================
       RESPONSIVE CARD DASHBOARD
       Card Statistik & Menu Tetep Horizontal
    ============================================ */

    /* ===== TABLET (max-width: 1024px) ===== */
    @media (max-width: 1024px) {
        .menu-row {
            gap: 15px;
        }
        .menu-row .menu-item {
            width: 180px;
        }
        .neumorphism-card {
            padding: 16px 12px;
            max-width: 180px;
        }
        .neumorphism-card h2 {
            font-size: 1.6rem;
        }
        .neumorphism-card h3 {
            font-size: 0.9rem;
        }
        .neumorphism-card p {
            font-size: 0.8rem;
        }
        .neumorphism-card .icon {
            font-size: 2.2rem;
        }
    }

    /* ===== HP (max-width: 768px) ===== */
    @media (max-width: 768px) {
        /* Container full width di HP */
        .dashboard-container {
            max-width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        /* Card 3 kolom horizontal */
        .menu-row {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 8px;
            margin-top: 15px;
            margin-bottom: 20px !important;
        }
        .menu-row .menu-item {
            width: calc(33.33% - 6px) !important;
            max-width: calc(33.33% - 6px) !important;
        }
        .neumorphism-card {
            padding: 12px 6px;
            max-width: 100% !important;
            border-radius: 14px;
            margin: 0 auto;
        }
        .neumorphism-card h2 {
            font-size: 1.2rem;
        }
        .neumorphism-card h3 {
            font-size: 0.75rem;
            margin-top: 4px;
            margin-bottom: 3px;
        }
        .neumorphism-card p {
            font-size: 0.65rem;
            line-height: 1.2;
        }
        .neumorphism-card .icon {
            font-size: 1.6rem;
        }
    }

    /* ===== HP KECIL (max-width: 480px) ===== */
    @media (max-width: 480px) {
        .menu-row {
            gap: 6px;
        }
        .menu-row .menu-item {
            width: calc(33.33% - 4px) !important;
            max-width: calc(33.33% - 4px) !important;
        }
        .neumorphism-card {
            padding: 10px 5px;
            border-radius: 12px;
        }
        .neumorphism-card h2 {
            font-size: 1rem;
        }
        .neumorphism-card h3 {
            font-size: 0.65rem;
        }
        .neumorphism-card p {
            font-size: 0.55rem;
            line-height: 1.1;
        }
        .neumorphism-card .icon {
            font-size: 1.4rem;
        }
    }
</style>