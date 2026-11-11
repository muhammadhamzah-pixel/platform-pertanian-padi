<?php
// pages/update_status_lahan.php
// Script auto-update status lahan berdasarkan umur padi (KHUSUS CRON)

include '../includes/config.php';

// ===== GUARD: HANYA BISA DIAKSES CLI ATAU TOKEN =====
$token_rahasia = 'PADI_DESA_2026_SECRET'; // ⚠️ GANTI dengan token unik lu!
$is_cli = (php_sapi_name() === 'cli');
$is_web_with_token = (isset($_GET['token']) && $_GET['token'] === $token_rahasia);

if (!$is_cli && !$is_web_with_token) {
    http_response_code(403);
    die('⛔ Forbidden - Akses ditolak');
}

// ===== SETUP OUTPUT =====
header('Content-Type: text/plain; charset=utf-8');
echo "📅 Tanggal: " . date('Y-m-d') . "\n";
echo "========================================\n";
echo "🔄 AUTO-UPDATE STATUS LAHAN\n";
echo "========================================\n\n";

// ===== AMBIL SEMUA LAHAN YANG BELUM 'SELESAI' & BELUM DI-SET MANUAL =====
// Asumsi: kolom `status_manual` (TINYINT) — 1 = manual, 0 = otomatis
$query = "SELECT id, user_id, nama_lahan, tgl_tanam, status, status_manual 
          FROM lahan 
          WHERE status != 'selesai' 
          ORDER BY id ASC";
$result = mysqli_query($conn, $query);

if (!$result) {
    die("❌ Error query: " . mysqli_error($conn) . "\n");
}

$total_lahan = mysqli_num_rows($result);
echo "🌾 Total lahan aktif: $total_lahan\n\n";

$updated_count = 0;
$skipped_manual = 0;

while ($row = mysqli_fetch_assoc($result)) {
    $lahan_id = $row['id'];
    $user_id = $row['user_id'];
    $nama_lahan = $row['nama_lahan'];
    $tgl_tanam = $row['tgl_tanam'];
    $status_lama = $row['status'];
    $status_manual = $row['status_manual'] ?? 0;
    
    // ===== SKIP KALAU STATUS DI-SET MANUAL OLEH PETANI =====
    if ($status_manual == 1) {
        $skipped_manual++;
        echo "⏩ Skip: $nama_lahan (status di-set manual)\n\n";
        continue;
    }
    
    // Hitung umur padi (dalam hari)
    $umur_hari = floor((strtotime(date('Y-m-d')) - strtotime($tgl_tanam)) / 86400);
    
    // ===== LOGIKA UMUR PADI (LEBIH REALISTIS) =====
    // 0-14   : tanam (persemaian & awal tanam)
    // 15-95  : tumbuh (fase vegetatif & generatif)
    // 96-110 : panen (fase pematangan bulir)
    // > 110  : selesai
    if ($umur_hari <= 14) {
        $status_baru = 'tanam';
    } elseif ($umur_hari <= 95) {
        $status_baru = 'tumbuh';
    } elseif ($umur_hari <= 110) {
        $status_baru = 'panen';
    } else {
        $status_baru = 'selesai';
    }
    
    // Cek apakah status berubah
    if ($status_lama != $status_baru) {
        $status_baru_esc = mysqli_real_escape_string($conn, $status_baru);
        $update = "UPDATE lahan SET status = '$status_baru_esc' WHERE id = '$lahan_id'";
        
        if (mysqli_query($conn, $update)) {
            echo "✅ Lahan: $nama_lahan\n";
            echo "   Umur: $umur_hari hari\n";
            echo "   Status: $status_lama → $status_baru\n\n";
            
            // ===== CATAT LOG =====
            catat_log(
                $conn, 
                $user_id, 
                null, // email user pemilik lahan (null karena sistem)
                'Sistem', 
                'Auto Update Status', 
                "Lahan: $nama_lahan, $status_lama → $status_baru (umur $umur_hari hari)"
            );
            
            $updated_count++;
        } else {
            echo "❌ Gagal update lahan: $nama_lahan - " . mysqli_error($conn) . "\n\n";
        }
    } else {
        echo "⏩ Lahan: $nama_lahan (umur: $umur_hari hari) - Status masih '$status_lama', skip.\n\n";
    }
}

echo "========================================\n";
echo "📊 RINGKASAN:\n";
echo "   Total lahan diproses: $total_lahan\n";
echo "   Status di-update: $updated_count\n";
echo "   Skip (manual): $skipped_manual\n";
echo "========================================\n";
echo "✅ Selesai!\n";
?>