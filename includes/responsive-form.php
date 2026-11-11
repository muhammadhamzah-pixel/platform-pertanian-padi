<?php
// includes/responsive-form.php
// CSS Responsif: Form (Login, Register, Tambah Lahan, Setting Desa, dll)
?>
<style>
    /* ============================================
       RESPONSIVE FORM
       Form Nyaman Dipake di HP
    ============================================ */

    /* ===== TABLET (max-width: 1024px) ===== */
    @media (max-width: 1024px) {
        .neumorphism-form {
            max-width: 90%;
            padding: 30px 25px;
            margin: 40px auto;
        }
        .neumorphism-form .form-control {
            padding: 11px 16px;
            font-size: 0.95rem;
        }
        .btn-neumorphism {
            padding: 11px 18px;
            font-size: 1rem;
        }
    }

    /* ===== HP (max-width: 768px) ===== */
    @media (max-width: 768px) {
        .neumorphism-form {
            max-width: 100% !important;
            padding: 20px 15px !important;
            margin: 15px auto !important;
            border-radius: 20px !important;
        }

        .neumorphism-form h1 {
            font-size: 1.2rem !important;
        }
        .neumorphism-form h2 {
            font-size: 1.1rem !important;
        }
        .neumorphism-form .subtitle {
            font-size: 0.85rem !important;
            margin-bottom: 15px !important;
        }
        .neumorphism-form .form-label {
            font-size: 0.9rem !important;
        }
        .neumorphism-form .form-control {
            padding: 10px 14px !important;
            font-size: 0.9rem !important;
            border-radius: 12px !important;
        }
        .neumorphism-form textarea.form-control {
            min-height: 70px !important;
        }
        .btn-neumorphism {
            padding: 10px 16px !important;
            font-size: 0.95rem !important;
        }

        /* Form Grid (label & input) */
        .form-grid {
            grid-template-columns: 1fr !important;
            gap: 5px !important;
        }
        .form-grid .label {
            margin-top: 10px !important;
            font-size: 0.9rem !important;
        }
        .form-grid .form-text {
            grid-column: 1 !important;
            font-size: 0.8rem !important;
        }

        /* Icon mata (show/hide password) */
        .toggle-password {
            font-size: 1rem !important;
            right: 12px !important;
        }
        .password-wrapper .form-control {
            padding-right: 40px !important;
        }

        /* Error & success message */
        .error-message,
        .success-message {
            padding: 10px 14px !important;
            font-size: 0.85rem !important;
            border-radius: 12px !important;
        }
    }

    /* ===== HP KECIL (max-width: 480px) ===== */
    @media (max-width: 480px) {
        .neumorphism-form {
            max-width: 100% !important;
            padding: 15px 12px !important;
            margin: 10px auto !important;
            border-radius: 16px !important;
        }
        .neumorphism-form h1 {
            font-size: 1.1rem !important;
        }
        .neumorphism-form h2 {
            font-size: 1rem !important;
        }
        .neumorphism-form .form-control {
            padding: 9px 12px !important;
            font-size: 0.85rem !important;
        }
        .btn-neumorphism {
            padding: 10px 14px !important;
            font-size: 0.9rem !important;
        }
    }
</style>