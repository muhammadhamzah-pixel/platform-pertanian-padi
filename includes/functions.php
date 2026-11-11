<?php
// includes/functions.php

/**
 * Fungsi untuk deteksi negara dari IP address (pakai API gratis)
 * Return: ['country' => 'Indonesia', 'country_code' => 'ID']
 */
function get_country_from_ip($ip) {
    // Skip localhost & IP lokal
    if ($ip === '::1' || $ip === '127.0.0.1' || strpos($ip, '192.168.') === 0 || strpos($ip, '10.') === 0) {
        return ['country' => 'Localhost', 'country_code' => 'LO'];
    }
    
    // Cek cache di DB dulu (hemat kuota API)
    global $conn;
    $ip_esc = mysqli_real_escape_string($conn, $ip);
    $cached = mysqli_query($conn, "SELECT country, country_code FROM log_aktivitas 
                                    WHERE ip_address = '$ip_esc' 
                                    AND country IS NOT NULL 
                                    AND country != 'Unknown'
                                    LIMIT 1");
    if ($cached && $row = mysqli_fetch_assoc($cached)) {
        return $row;
    }
    
    // Panggil API ipapi.co (gratis 1000 request/hari)
    $ctx = stream_context_create([
        'http' => ['timeout' => 3, 'method' => 'GET']
    ]);
    $response = @file_get_contents("https://ipapi.co/{$ip}/json/", false, $ctx);
    
    if ($response) {
        $data = json_decode($response, true);
        return [
            'country' => $data['country_name'] ?? 'Unknown',
            'country_code' => $data['country_code'] ?? 'XX'
        ];
    }
    
    return ['country' => 'Unknown', 'country_code' => 'XX'];
}

/**
 * Fungsi untuk dapetin emoji bendera dari country code
 * Contoh: 'US' → 🇺🇸, 'ID' → 🇮🇩
 */
function get_country_flag($country_code) {
    if (empty($country_code) || $country_code === 'XX' || $country_code === 'LO') {
        return '🌐';
    }
    
    $country_code = strtolower($country_code);
    
    if (!preg_match('/^[a-z]{2}$/', $country_code)) {
        return '🌐';
    }
    
    // Pakai gambar bendera dari flagcdn.com (gratis, muncul di semua OS)
    return '<img src="https://flagcdn.com/20x15/' . $country_code . '.png" 
                 alt="' . $country_code . '" 
                 style="width: 20px; height: 15px; margin-right: 5px; vertical-align: middle; border-radius: 2px;">';
}

/**
 * Fungsi untuk deteksi bot / scanner dari user-agent
 * Return: 1 kalau bot, 0 kalau manusia
 */
function is_bot_agent($user_agent) {
    $bot_signatures = [
        'bot', 'crawl', 'spider', 'scraper',
        'curl', 'wget', 'python', 'java',
        'scanner', 'nmap', 'sqlmap', 'nikto', 'masscan',
        'postman', 'insomnia', 'httpclient', 'go-http',
        'facebookexternalhit', 'whatsapp', 'telegram'
    ];
    
    $ua_lower = strtolower($user_agent);
    foreach ($bot_signatures as $sig) {
        if (strpos($ua_lower, $sig) !== false) {
            return 1;
        }
    }
    return 0;
}

/**
 * Fungsi untuk mencatat log aktivitas user (v2 — dengan negara & bot)
 */
function catat_log($conn, $user_id, $email, $nama, $aktivitas, $keterangan = '') {
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    
    // Deteksi negara & bot
    $country_data = get_country_from_ip($ip_address);
    $country = $country_data['country'];
    $country_code = $country_data['country_code'];
    $is_bot = is_bot_agent($user_agent);
    
    $user_id = $user_id ? intval($user_id) : 'NULL';
    $email = $email ? "'" . mysqli_real_escape_string($conn, $email) . "'" : 'NULL';
    $nama = $nama ? "'" . mysqli_real_escape_string($conn, $nama) . "'" : 'NULL';
    $aktivitas = mysqli_real_escape_string($conn, $aktivitas);
    $keterangan = mysqli_real_escape_string($conn, $keterangan);
    $ip_address = mysqli_real_escape_string($conn, $ip_address);
    $user_agent = mysqli_real_escape_string($conn, $user_agent);
    $country = mysqli_real_escape_string($conn, $country);
    $country_code = mysqli_real_escape_string($conn, $country_code);
    
    $query = "INSERT INTO log_aktivitas 
              (user_id, email, nama, aktivitas, keterangan, ip_address, country, country_code, is_bot, user_agent) 
              VALUES 
              ($user_id, $email, $nama, '$aktivitas', '$keterangan', '$ip_address', '$country', '$country_code', '$is_bot', '$user_agent')";
    
    return mysqli_query($conn, $query);
}

/**
 * Fungsi untuk mencatat percobaan login
 */
function catat_login_attempt($conn, $email, $status = 'gagal') {
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    
    $email = trim($email);
    if (empty($email)) {
        return false;
    }
    
    $email = mysqli_real_escape_string($conn, $email);
    $ip_address = mysqli_real_escape_string($conn, $ip_address);
    $user_agent = mysqli_real_escape_string($conn, $user_agent);
    $status = mysqli_real_escape_string($conn, $status);
    
    $query = "INSERT INTO login_attempts (email, ip_address, user_agent, status) 
              VALUES ('$email', '$ip_address', '$user_agent', '$status')";
    
    return mysqli_query($conn, $query);
}

/**
 * Fungsi untuk cek apakah email diblokir karena terlalu banyak gagal login
 */
function cek_blokir_login($conn, $email) {
    $email = mysqli_real_escape_string($conn, $email);
    $query = "SELECT COUNT(*) AS total FROM login_attempts 
              WHERE email = '$email' 
              AND status = 'gagal' 
              AND created_at >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)";
    $result = mysqli_query($conn, $query);
    $data = mysqli_fetch_assoc($result);
    return $data['total'] >= 5;
}

/**
 * Fungsi untuk hitung sisa waktu blokir (dalam menit)
 */
function sisa_waktu_blokir($conn, $email) {
    $email = mysqli_real_escape_string($conn, $email);
    $query = "SELECT created_at FROM login_attempts 
              WHERE email = '$email' 
              AND status = 'gagal' 
              AND created_at >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)
              ORDER BY created_at ASC 
              LIMIT 1";
    $result = mysqli_query($conn, $query);
    $data = mysqli_fetch_assoc($result);
    
    if ($data) {
        $waktu_gagal_pertama = strtotime($data['created_at']);
        $waktu_blokir_selesai = $waktu_gagal_pertama + (15 * 60);
        $sisa_detik = $waktu_blokir_selesai - time();
        $sisa_menit = ceil($sisa_detik / 60);
        return max(0, $sisa_menit);
    }
    return 0;
}

/**
 * Fungsi untuk reset percobaan login
 */
function reset_login_attempts($conn, $email) {
    $email = mysqli_real_escape_string($conn, $email);
    $query = "DELETE FROM login_attempts WHERE email = '$email'";
    return mysqli_query($conn, $query);
}

/**
 * Fungsi untuk sanitasi input
 */
function sanitize_input($input) {
    $input = trim($input);
    $input = strip_tags($input);
    return $input;
}

/**
 * Fungsi untuk escape output
 */
function escape_output($output) {
    return htmlspecialchars($output, ENT_QUOTES, 'UTF-8');
}

/**
 * Fungsi untuk generate CSRF Token
 */
function generate_csrf_token() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Fungsi untuk verifikasi CSRF Token
 */
function verify_csrf_token($token) {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Fungsi untuk regenerate CSRF Token
 */
function regenerate_csrf_token() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}

/**
 * Fungsi untuk bikin hidden input CSRF
 */
function csrf_field() {
    $token = generate_csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

/**
 * Fungsi untuk mengirim pesan via Bot Telegram
 */
function kirimTelegramNotifikasi($chat_id_telegram, $pesan) {
    if (empty($chat_id_telegram) || !defined('TELEGRAM_BOT_TOKEN') || empty(TELEGRAM_BOT_TOKEN)) {
        return false;
    }
    
    $url = "https://api.telegram.org/bot" . TELEGRAM_BOT_TOKEN . "/sendMessage";
    
    $data = [
        'chat_id' => $chat_id_telegram,
        'text' => $pesan,
        'parse_mode' => 'HTML'
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Biar aman di Laragon lokal
    $response = curl_exec($ch);
    curl_close($ch);
    
    return $response;
}

/**
 * Fungsi untuk mengirim notifikasi WhatsApp via Fonnte API
 */
function kirimWaNotifikasi($no_hp, $pesan) {
    if (empty($no_hp) || !defined('FONNTE_TOKEN') || empty(FONNTE_TOKEN) || FONNTE_TOKEN === 'TEMPEL_TOKEN_FONNTE_DISINI') {
        return false;
    }
    
    // Bersihkan karakter non-digit
    $target = preg_replace('/[^0-9]/', '', $no_hp);
    if (empty($target)) {
        return false;
    }

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => 'https://api.fonnte.com/send',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => [
            'target' => $target,
            'message' => $pesan,
            'countryCode' => '62'
        ],
        CURLOPT_HTTPHEADER => [
            'Authorization: ' . FONNTE_TOKEN
        ],
        CURLOPT_SSL_VERIFYPEER => false, // Biar lancar di Laragon localhost
    ]);

    $res = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);

    if ($err) {
        error_log("Fonnte WA Error: " . $err);
        return false;
    }

    return $res;
}

/**
 * Fungsi Otomatis Sinkronisasi Notifikasi Siklus Padi Petani
 * Mengecek umur lahan (hari ke-3, hari ke-7, pemupukan 1 & 2, H-7, H-3, H-1, sampai Panen)
 * Otomatis membuat notifikasi baru tanpa harus menunggu cron server eksternal.
 */
function sync_notifikasi_petani($conn, $user_id) {
    if (!$conn || empty($user_id)) {
        return;
    }
    
    $user_id_esc = mysqli_real_escape_string($conn, $user_id);
    
    // Ambil semua lahan petani yang belum berstatus 'selesai'
    $q_lahan = mysqli_query($conn, "SELECT id, nama_lahan, tgl_tanam, tgl_panen_perkiraan, status 
                                     FROM lahan 
                                     WHERE user_id = '$user_id_esc' AND status != 'selesai'");
    if (!$q_lahan) {
        return;
    }

    // Ambil data user untuk notif telegram / email bila ada
    $user_data = null;
    $qu = mysqli_query($conn, "SELECT email, nama, telegram_chat_id, no_hp FROM users WHERE id = '$user_id_esc'");
    if ($qu && $row_u = mysqli_fetch_assoc($qu)) {
        $user_data = $row_u;
    }

    $today = strtotime(date('Y-m-d'));

    while ($lahan = mysqli_fetch_assoc($q_lahan)) {
        $lahan_id = $lahan['id'];
        $nama_lahan = $lahan['nama_lahan'];
        $nama_lahan_esc = mysqli_real_escape_string($conn, $nama_lahan);
        $tgl_tanam = strtotime($lahan['tgl_tanam']);
        if (!$tgl_tanam) continue;

        $umur_hari = floor(($today - $tgl_tanam) / 86400);

        // Perkiraan panen (default 120 hari jika belum diset)
        $tgl_panen = !empty($lahan['tgl_panen_perkiraan']) ? strtotime($lahan['tgl_panen_perkiraan']) : ($tgl_tanam + (120 * 86400));
        $sisa_hari_panen = floor(($tgl_panen - $today) / 86400);

        // Update status ke 'panen' jika sudah waktunya dan status masih 'tanam'
        if ($sisa_hari_panen <= 0 && $lahan['status'] === 'tanam') {
            mysqli_query($conn, "UPDATE lahan SET status = 'panen' WHERE id = '$lahan_id'");
        }

        // Definisi Tahapan Notifikasi Siklus Padi
        $milestones = [
            [
                'tipe' => 'hari_3',
                'kondisi' => ($umur_hari >= 3),
                'judul' => '💧 Hari ke-3: Cek Air Sawah',
                'pesan' => "Padi pada lahan \"$nama_lahan\" memasuki hari ke-3 tanam. Pastikan kondisi air macak-macak (1-2 cm) agar bibit lekas berakar kokoh."
            ],
            [
                'tipe' => 'hari_7',
                'kondisi' => ($umur_hari >= 7),
                'judul' => '🌿 Hari ke-7: Adaptasi Bibit & Sulam',
                'pesan' => "Sudah 7 hari sejak tanam pada \"$nama_lahan\". Segera periksa bibit yang mati untuk disulam dan bersihkan gulma awal."
            ],
            [
                'tipe' => 'pemupukan_1',
                'kondisi' => ($umur_hari >= 14),
                'judul' => '💧 Waktunya Pupuk Pertama!',
                'pesan' => "Waktunya pemupukan pertama (Urea / NPK) untuk \"$nama_lahan\". Nutrisi dasar ini penting untuk memacu anakan produktif!"
            ],
            [
                'tipe' => 'pemupukan_2',
                'kondisi' => ($umur_hari >= 45),
                'judul' => '🌿 Waktunya Pupuk Kedua!',
                'pesan' => "Padi di \"$nama_lahan\" memasuki fase bunting. Berikan pupuk susulan kedua agar pembentukan malai dan bulir padi lebat!"
            ],
            [
                'tipe' => 'h7',
                'kondisi' => ($sisa_hari_panen <= 7 || $umur_hari >= 113),
                'judul' => '🌾 H-7 Menuju Panen!',
                'pesan' => "Tinggal 7 hari lagi menuju panen lahan \"$nama_lahan\"! Mulai keringkan sawah dan siapkan tempat penjemuran gabah."
            ],
            [
                'tipe' => 'h3',
                'kondisi' => ($sisa_hari_panen <= 3 || $umur_hari >= 117),
                'judul' => '⏰ H-3 Menuju Panen!',
                'pesan' => "3 hari lagi panen tiba! Siapkan sabit, karung gabah, dan mesin perontok untuk lahan \"$nama_lahan\"."
            ],
            [
                'tipe' => 'h1',
                'kondisi' => ($sisa_hari_panen <= 1 || $umur_hari >= 119),
                'judul' => '🎉 Besok Hari Panen!',
                'pesan' => "Besok adalah hari panen padi \"$nama_lahan\"! Pastikan cuaca cerah dan tenaga panen siap di petak sawah."
            ],
            [
                'tipe' => 'panen',
                'kondisi' => ($sisa_hari_panen <= 0 || $umur_hari >= 120),
                'judul' => '🎊 Hari Panen Tiba!',
                'pesan' => "Selamat! Hari panen untuk lahan \"$nama_lahan\" telah tiba! Segera petik hasil panen dan catat tonase Anda."
            ]
        ];

        foreach ($milestones as $m) {
            if (!$m['kondisi']) {
                continue;
            }

            $tipe_esc = mysqli_real_escape_string($conn, $m['tipe']);
            
            // Cek apakah notifikasi tipe ini untuk lahan ini sudah pernah dibuat
            $cek = mysqli_query($conn, "SELECT id FROM notifikasi 
                                        WHERE user_id = '$user_id_esc' 
                                          AND tipe = '$tipe_esc' 
                                          AND (pesan LIKE '%$nama_lahan_esc%' OR judul LIKE '%$nama_lahan_esc%')
                                        LIMIT 1");
            if ($cek && mysqli_num_rows($cek) > 0) {
                continue; // Sudah pernah dibuat, lewati
            }

            // Insert notifikasi baru
            $judul_esc = mysqli_real_escape_string($conn, $m['judul']);
            $pesan_esc = mysqli_real_escape_string($conn, $m['pesan']);
            $link = 'data_lahan.php';

            $ins = mysqli_query($conn, "INSERT INTO notifikasi (user_id, judul, pesan, link, tipe, is_read, created_at) 
                                        VALUES ('$user_id_esc', '$judul_esc', '$pesan_esc', '$link', '$tipe_esc', 0, NOW())");
            
            if ($ins && $user_data) {
                // Notifikasi Telegram jika aktif
                if (!empty($user_data['telegram_chat_id']) && function_exists('kirimTelegramNotifikasi')) {
                    $pesan_tg = "🌾 <b>" . $m['judul'] . "</b>\n\n" . $m['pesan'];
                    kirimTelegramNotifikasi($user_data['telegram_chat_id'], $pesan_tg);
                }
                // Notifikasi Email jika aktif
                if (!empty($user_data['email']) && function_exists('kirimEmailNotifikasi')) {
                    kirimEmailNotifikasi($user_data['email'], $user_data['nama'], $m['judul'], $m['pesan']);
                }
                // Notifikasi WhatsApp jika aktif
                if (!empty($user_data['no_hp']) && function_exists('kirimWaNotifikasi')) {
                    $pesan_wa = "🌾 *" . $m['judul'] . "*\n\n" . $m['pesan'];
                    kirimWaNotifikasi($user_data['no_hp'], $pesan_wa);
                }
            }
        }
    }
}
?>