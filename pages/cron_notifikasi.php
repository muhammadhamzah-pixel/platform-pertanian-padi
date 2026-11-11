<?php
// pages/cron_notifikasi.php
// Script auto-generate notifikasi berdasarkan umur padi

include '../includes/config.php';

// ===== GUARD: HANYA BISA DIAKSES CLI ATAU TOKEN =====
$token_rahasia = 'PADI_DESA_2026_SECRET'; // ⚠️ GANTI dengan token unik lu sendiri!
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
echo "🔄 AUTO-GENERATE NOTIFIKASI\n";
echo "========================================\n\n";

// ===== DEFINISI TAHAP NOTIFIKASI =====
$tahapan = [
    [
        'min' => 0,
        'tipe' => 'tanam',
        'judul' => '🌱 Lahan Baru Ditanam!',
        'pesan' => 'Lahan "%s" berhasil ditanam! Semoga panennya melimpah. Pantau terus perkembangannya ya! 🌾'
    ],
    [
        'min' => 3,
        'tipe' => 'hari_3',
        'judul' => '💧 Hari ke-3: Cek Air Sawah',
        'pesan' => 'Padi pada lahan "%s" memasuki hari ke-3 tanam. Pastikan kondisi air macak-macak (1-2 cm) agar bibit lekas berakar kokoh.'
    ],
    [
        'min' => 7,
        'tipe' => 'hari_7',
        'judul' => '🌿 Hari ke-7: Adaptasi Bibit & Sulam',
        'pesan' => 'Sudah 7 hari sejak tanam pada "%s". Segera periksa bibit yang mati untuk disulam dan bersihkan gulma awal.'
    ],
    [
        'min' => 14,
        'tipe' => 'pemupukan_1',
        'judul' => '💧 Waktunya Pupuk Pertama!',
        'pesan' => 'Sudah waktunya pupuk pertama untuk "%s". Jangan lupa cek kondisi air juga! 💦'
    ],
    [
        'min' => 45,
        'tipe' => 'pemupukan_2',
        'judul' => '🌿 Waktunya Pupuk Kedua!',
        'pesan' => 'Padi di "%s" butuh pupuk susulan kedua. Berikan nutrisi terbaik biar bulirnya lebat dan bernas! 🌾'
    ],
    [
        'min' => 113,
        'tipe' => 'h7',
        'judul' => '🌾 H-7 Menuju Panen!',
        'pesan' => 'Tinggal 7 hari lagi! Padi di "%s" siap dipanen. Yuk cek kondisi sawah sekarang! 😊'
    ],
    [
        'min' => 117,
        'tipe' => 'h3',
        'judul' => '⏰ H-3 Menuju Panen!',
        'pesan' => '3 hari lagi! Siapkan alat panen: sabit, karung, dan tenaga. "%s" siap dipanen! 💪'
    ],
    [
        'min' => 119,
        'tipe' => 'h1',
        'judul' => '🎉 Besok Panen!',
        'pesan' => 'Besok hari panen! Pastikan cuaca cerah & alat siap. "%s" siap menghasilkan! 🌾'
    ],
    [
        'min' => 120,
        'tipe' => 'panen',
        'judul' => '🎊 Hari Panen Tiba!',
        'pesan' => 'Selamat! Hari panen "%s" udah tiba! Semoga hasilnya melimpah & berkah! 🙏🌾'
    ],
];

$total_lahan = 0;
$total_notif = 0;
$total_skip = 0;

// ===== AMBIL SEMUA LAHAN YANG BELUM SELESAI =====
$query_lahan = "SELECT id, user_id, nama_lahan, tgl_tanam, status 
                FROM lahan 
                WHERE status != 'selesai' 
                ORDER BY id ASC";
$result_lahan = mysqli_query($conn, $query_lahan);

if (!$result_lahan) {
    die("❌ Error query lahan: " . mysqli_error($conn) . "\n");
}

echo "🌾 Memproses lahan...\n\n";

while ($lahan = mysqli_fetch_assoc($result_lahan)) {
    $total_lahan++;
    $lahan_id = $lahan['id'];
    $user_id = $lahan['user_id'];
    $nama_lahan = $lahan['nama_lahan'];
    $tgl_tanam = $lahan['tgl_tanam'];
    
    // Hitung umur padi (dalam hari)
    $umur_hari = floor((strtotime(date('Y-m-d')) - strtotime($tgl_tanam)) / 86400);
    
    // Cek setiap tahapan
    foreach ($tahapan as $tahap) {
        // Cek apakah umur sudah mencapai atau melewati tahap ini
        if ($umur_hari < $tahap['min']) {
            continue;
        }
        
        // Cek apakah notif tipe ini untuk lahan ini udah ada
        $tipe = $tahap['tipe'];
        $nama_lahan_esc = mysqli_real_escape_string($conn, $nama_lahan);
        $tipe_esc = mysqli_real_escape_string($conn, $tipe);
        
        $cek_query = "SELECT id FROM notifikasi 
                      WHERE user_id = '$user_id' 
                      AND tipe = '$tipe_esc' 
                      AND pesan LIKE '%$nama_lahan_esc%'
                      LIMIT 1";
        $cek_result = mysqli_query($conn, $cek_query);
        
        if (mysqli_num_rows($cek_result) > 0) {
            $total_skip++;
            echo "⏩ Skip: $nama_lahan (hari ke-$umur_hari) - Notif '$tipe' udah ada\n";
            continue;
        }
        
        // Buat notif
        $judul = mysqli_real_escape_string($conn, $tahap['judul']);
        $pesan = mysqli_real_escape_string($conn, sprintf($tahap['pesan'], $nama_lahan));
        $link = 'data_lahan.php';
        
        $insert = "INSERT INTO notifikasi (user_id, judul, pesan, link, tipe, is_read) 
                   VALUES ('$user_id', '$judul', '$pesan', '$link', '$tipe_esc', 0)";
        
        if (mysqli_query($conn, $insert)) {
            $total_notif++;
            echo "✅ Notif '$tipe' dibuat: $nama_lahan (user_id: $user_id, hari ke-$umur_hari)\n";

            // ===== AMBIL DATA USER UNTUK NOTIF EKSTERNAL =====
            $q_user = mysqli_query($conn, "SELECT email, nama, telegram_chat_id, no_hp FROM users WHERE id = '$user_id'");
            $d_user = mysqli_fetch_assoc($q_user);

            if ($d_user) {
                // 1. Kirim Email
                if (function_exists('kirimEmailNotifikasi')) {
                    kirimEmailNotifikasi($d_user['email'], $d_user['nama'], $judul, $pesan);
                }
                
                // 2. Kirim Telegram
                if (!empty($d_user['telegram_chat_id']) && function_exists('kirimTelegramNotifikasi')) {
                    $pesan_tg = "🌾 <b>" . $judul . "</b>\n\n" . $pesan;
                    kirimTelegramNotifikasi($d_user['telegram_chat_id'], $pesan_tg);
                }

                // 3. Kirim WhatsApp (Fonnte)
                if (!empty($d_user['no_hp']) && function_exists('kirimWaNotifikasi')) {
                    $pesan_wa = "🌾 *" . $judul . "*\n\n" . $pesan;
                    kirimWaNotifikasi($d_user['no_hp'], $pesan_wa);
                }
            }
            // ==================================================
            
        } else {
            echo "❌ Gagal insert: " . mysqli_error($conn) . "\n";
        }
    }
}

echo "\n========================================\n";
echo "📊 RINGKASAN:\n";
echo "   Total lahan diproses: $total_lahan\n";
echo "   Notif dibuat: $total_notif\n";
echo "   Notif di-skip (udah ada): $total_skip\n";
echo "========================================\n";
echo "✅ Selesai!\n";
?>