<?php
// pages/process_ai.php
// Handle AJAX request dari floating chat / ai_chat

header('Content-Type: application/json; charset=utf-8');

include '../includes/config.php';
include '../includes/gemini_config.php';
include '../includes/gemini_function.php';

// ===== 1. GUARD LOGIN =====
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Akses ditolak. Silakan login terlebih dahulu.']);
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'] ?? 'petani';
$user_nama = $_SESSION['nama'] ?? 'Pengguna';

// ===== 2. TENTUKAN LIMIT BERDASARKAN ROLE (Sesuai UI Floating Chat) =====
if ($user_role === 'admin') {
    $limit_harian = 50;
} elseif ($user_role === 'perangkat_desa') {
    $limit_harian = 25;
} else {
    $limit_harian = 10; // Petani
}

// ===== 3. VERIFIKASI CSRF =====
if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
    echo json_encode(['success' => false, 'error' => '❌ Sesi keamanan kedaluwarsa! Silakan refresh halaman.']);
    exit();
}

// ===== 4. VALIDASI INPUT =====
if (empty($_POST['pertanyaan'])) {
    echo json_encode(['success' => false, 'error' => '❌ Pertanyaan tidak boleh kosong!']);
    exit();
}

$pertanyaan = sanitize_input($_POST['pertanyaan']);

if (strlen($pertanyaan) > 1000) {
    echo json_encode(['success' => false, 'error' => '❌ Pertanyaan terlalu panjang (maks 1000 karakter).']);
    exit();
}

// ===== 5. CEK LIMIT HARIAN =====
$query_hari_ini = "SELECT COUNT(*) AS total FROM chat_ai 
                   WHERE user_id = '$user_id' 
                   AND DATE(created_at) = CURDATE()";
$result_hari_ini = mysqli_query($conn, $query_hari_ini);
$chat_hari_ini = intval(mysqli_fetch_assoc($result_hari_ini)['total'] ?? 0);

if ($chat_hari_ini >= $limit_harian) {
    echo json_encode([
        'success' => false, 
        'error' => "🤖 Kuota tanya AI Anda hari ini sudah mencapai batas ($chat_hari_ini/$limit_harian). Coba lagi besok ya!"
    ]);
    exit();
}

// ===== 6. PRE-FILTER SEDERHANA (Mencegah Spam Pertanyaan Jahil) =====
$pertanyaan_lower = strtolower($pertanyaan);
$kata_terlarang = ['password', 'api key', 'secret key', 'token otp'];
foreach ($kata_terlarang as $kata) {
    if (strpos($pertanyaan_lower, $kata) !== false && strpos($pertanyaan_lower, 'cara ganti') === false) {
        $current_csrf = $_SESSION['csrf_token'] ?? generate_csrf_token();
        echo json_encode([
            'success' => true,
            'jawaban' => "Mohon maaf, demi keamanan sistem, saya dilarang memberikan informasi mengenai kata sandi atau kredensial.",
            'sisa_limit' => max(0, $limit_harian - $chat_hari_ini),
            'new_csrf_token' => $current_csrf
        ]);
        exit();
    }
}

// ===== 7. PROSES UPLOAD GAMBAR (Jika Ada) =====
$gambar_chat = null;
$gambar_path = null;

if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == 0) {
    $target_dir = '../uploads/chat_ai/';
    
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0755, true);
    }
    
    // Validasi MIME asli
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $_FILES['gambar']['tmp_name']);
    finfo_close($finfo);
    
    $allowed_mimes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp'
    ];
    
    if (!array_key_exists($mime_type, $allowed_mimes)) {
        echo json_encode(['success' => false, 'error' => '❌ Format gambar tidak valid! Cuma JPG, PNG, WEBP.']);
        exit();
    }
    
    if ($_FILES['gambar']['size'] > 5 * 1024 * 1024) {
        echo json_encode(['success' => false, 'error' => '❌ Ukuran gambar terlalu besar (maks 5MB).']);
        exit();
    }
    
    if (getimagesize($_FILES['gambar']['tmp_name']) === false) {
        echo json_encode(['success' => false, 'error' => '❌ File gambar rusak.']);
        exit();
    }
    
    // Generate nama file aman
    $ext = $allowed_mimes[$mime_type];
    $file_name = 'chat_' . $user_id . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
    $target_file = $target_dir . $file_name;
    
    if (move_uploaded_file($_FILES['gambar']['tmp_name'], $target_file)) {
        $gambar_chat = $file_name;
        $gambar_path = $target_file;
    } else {
        echo json_encode(['success' => false, 'error' => '❌ Gagal upload gambar.']);
        exit();
    }
}

// ===== 8. SUSUN KONTEKS (Hanya Info User, Data Lahan Dihandle AI Sendiri) =====
// Kita nggak perlu narik data lahan manual lagi. Cukup kasih identitas user.
$db_context = [
    'user_id' => $user_id,
    'nama' => $user_nama,
    'role' => $user_role
];

// ===== 9. TANYA KE GEMINI =====
// AI bakal mikir: "Ini butuh query DB atau nggak?"
$response = tanyaGemini($pertanyaan, $gambar_path, $db_context);

if (empty($response)) {
    // Hapus gambar kalau gagal
    if ($gambar_path && file_exists($gambar_path)) {
        unlink($gambar_path);
    }
    echo json_encode(['success' => false, 'error' => '❌ AI tidak memberikan jawaban. Coba lagi.']);
    exit();
}

// ===== 10. SIMPAN KE DB =====
$pertanyaan_esc = mysqli_real_escape_string($conn, $pertanyaan);
$response_esc = mysqli_real_escape_string($conn, $response);
$gambar_esc = $gambar_chat ? "'" . mysqli_real_escape_string($conn, $gambar_chat) . "'" : 'NULL';

$query = "INSERT INTO chat_ai (user_id, pertanyaan, jawaban, gambar_chat) 
          VALUES ('$user_id', '$pertanyaan_esc', '$response_esc', $gambar_esc)";

if (!mysqli_query($conn, $query)) {
    if ($gambar_path && file_exists($gambar_path)) {
        unlink($gambar_path);
    }
    echo json_encode(['success' => false, 'error' => '❌ Gagal menyimpan ke database.']);
    exit();
}

// ===== 11. HITUNG SISA LIMIT & CSRF =====
$chat_hari_ini++;
$sisa_limit = max(0, $limit_harian - $chat_hari_ini);
$current_csrf = $_SESSION['csrf_token'] ?? generate_csrf_token();

// ===== 12. KEMBALIKAN RESPON =====
echo json_encode([
    'success' => true,
    'jawaban' => $response,
    'sisa_limit' => $sisa_limit,
    'limit_harian' => $limit_harian,
    'new_csrf_token' => $current_csrf
]);
?>