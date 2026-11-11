<?php
// includes/responsive-table.php
// CSS Responsif: Tabel (Data Lahan, Kelola User, Log Aktivitas, dll)
?>
<style>
    /* ============================================
       RESPONSIVE TABLE
       Tabel Bisa Di-scroll Horizontal di HP
    ============================================ */

    /* ===== TABLET (max-width: 1024px) ===== */
    @media (max-width: 1024px) {
        .neumorphism-table-container {
            padding: 15px;
            border-radius: 20px;
        }
        .neumorphism-table-container table {
            font-size: 12px;
        }
        .neumorphism-table-container thead th {
            padding: 10px 12px;
            font-size: 12px;
        }
        .neumorphism-table-container tbody td {
            padding: 8px 12px;
            font-size: 12px;
        }
    }

    /* ===== HP (max-width: 768px) ===== */
    @media (max-width: 768px) {
        /* Container tabel bisa di-scroll horizontal */
        .neumorphism-table-container {
            padding: 10px !important;
            border-radius: 16px !important;
            overflow-x: auto !important;
            -webkit-overflow-scrolling: touch;
            max-width: 100% !important;
            width: 100% !important;
        }

        /* Tabel punya lebar minimum biar ga kekecilan */
        .neumorphism-table-container table {
            min-width: 600px !important;
            font-size: 11px !important;
        }

        .neumorphism-table-container thead th {
            padding: 8px 10px !important;
            font-size: 11px !important;
            white-space: nowrap !important;
        }

        .neumorphism-table-container tbody td {
            padding: 7px 10px !important;
            font-size: 11px !important;
            white-space: nowrap !important;
        }

        /* Badge status lebih kecil */
        .badge-status,
        .badge-aktivitas {
            font-size: 10px !important;
            padding: 2px 8px !important;
        }

        /* Tombol aksi lebih kecil */
        .btn-neumorphism-sm,
        .btn-export,
        .btn-unblock {
            padding: 6px 14px !important;
            font-size: 11px !important;
        }

        /* Header row (judul + tombol export) */
        .header-row {
            flex-direction: column !important;
            align-items: flex-start !important;
            gap: 10px !important;
        }
        .header-row h1 {
            font-size: 1.2rem !important;
        }
    }

    /* ===== HP KECIL (max-width: 480px) ===== */
    @media (max-width: 480px) {
        .neumorphism-table-container {
            padding: 8px !important;
            border-radius: 14px !important;
        }

        .neumorphism-table-container table {
            min-width: 550px !important;
            font-size: 10px !important;
        }

        .neumorphism-table-container thead th {
            padding: 7px 8px !important;
            font-size: 10px !important;
        }

        .neumorphism-table-container tbody td {
            padding: 6px 8px !important;
            font-size: 10px !important;
        }

        .badge-status,
        .badge-aktivitas {
            font-size: 9px !important;
            padding: 2px 6px !important;
        }
    }
</style>