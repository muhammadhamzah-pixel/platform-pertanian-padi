<?php
// includes/responsive-landing.php
// CSS Responsif: Landing Page (Hero, Card Fitur, Modal, Footer)
?>
<style>
    /* ============================================
       RESPONSIVE LANDING PAGE
       Hero, Card Fitur, Modal, Footer
    ============================================ */

    /* ===== TABLET (max-width: 1024px) ===== */
    @media (max-width: 1024px) {
        .container-custom {
            padding: 15px;
        }
        .hero-section {
            padding: 40px 25px;
            margin-bottom: 30px;
        }
        .hero-section h1 {
            font-size: 2.2rem;
        }
        .feature-row .feature-item {
            width: 200px;
        }
    }

    /* ===== HP (max-width: 768px) ===== */
    @media (max-width: 768px) {
        .container-custom {
            padding: 10px !important;
            max-width: 100% !important;
            width: 100% !important;
        }

        /* Hero */
        .hero-section {
            padding: 25px 15px !important;
            border-radius: 16px !important;
            margin-bottom: 15px !important;
        }
        .hero-section h1 {
            font-size: 1.4rem !important;
        }
        .hero-section p {
            font-size: 0.85rem !important;
        }

        /* Tombol Hero — Tetep Sejajar */
        .hero-section .btn-primary-custom,
        .hero-section .btn-secondary-custom {
            display: inline-block !important;
            width: auto !important;
            padding: 8px 16px !important;
            font-size: 0.8rem !important;
            margin: 5px 3px !important;
        }

        /* Card Fitur — Tetep 2 Kolom Horizontal */
        .feature-row {
            display: flex !important;
            flex-wrap: wrap !important;
            gap: 8px !important;
            justify-content: center !important;
        }
        .feature-row .feature-item {
            width: calc(50% - 4px) !important;
            max-width: calc(50% - 4px) !important;
        }
        .card-light {
            padding: 12px 8px !important;
            border-radius: 14px !important;
        }
        .card-light .icon {
            font-size: 1.8rem !important;
            margin-bottom: 6px !important;
        }
        .card-light h3 {
            font-size: 0.85rem !important;
        }
        .card-light p {
            font-size: 0.7rem !important;
            line-height: 1.3 !important;
        }

        /* Modal */
        .modal-content-light {
            max-width: 92% !important;
            padding: 20px 15px !important;
            border-radius: 16px !important;
        }
        .modal-content-light h2 {
            font-size: 1.1rem !important;
        }
        .modal-content-light p {
            font-size: 0.85rem !important;
        }
        .modal-content-light ul li {
            font-size: 0.85rem !important;
            padding: 5px 0 !important;
        }
        .btn-close-light {
            padding: 7px 20px !important;
            font-size: 0.85rem !important;
        }

        /* Footer */
        .footer-light {
            padding: 12px 10px !important;
            border-radius: 16px 16px 0 0 !important;
            margin-top: 15px !important;
        }
        .footer-light p {
            font-size: 0.75rem !important;
        }
        .footer-light .anggota {
            font-size: 0.65rem !important;
        }
    }

    /* ===== HP KECIL (max-width: 480px) ===== */
    @media (max-width: 480px) {
        .hero-section {
            padding: 20px 12px !important;
        }
        .hero-section h1 {
            font-size: 1.2rem !important;
        }
        .hero-section p {
            font-size: 0.75rem !important;
        }
        .hero-section .btn-primary-custom,
        .hero-section .btn-secondary-custom {
            padding: 7px 14px !important;
            font-size: 0.75rem !important;
        }
        .feature-row .feature-item {
            width: calc(50% - 3px) !important;
            max-width: calc(50% - 3px) !important;
        }
        .card-light {
            padding: 10px 6px !important;
        }
        .card-light .icon {
            font-size: 1.6rem !important;
        }
        .card-light h3 {
            font-size: 0.75rem !important;
        }
        .card-light p {
            font-size: 0.65rem !important;
        }
    }
</style>