<?php
// includes/gemini_function.php
// Fungsi untuk manggil Gemini AI (Support Text + Gambar + Text-to-SQL / RAG)

// Pastikan file ai_tools.php diload
require_once __DIR__ . '/ai_tools.php';

/**
 * FUNGSI UTAMA: Tanya AI
 * 
 * @param string $pertanyaan Pertanyaan dari user
 * @param string|null $gambar_path Path gambar (jika ada)
 * @param array $db_context Konteks user (nama, role, user_id, dll)
 * @return string Jawaban AI
 */
function tanyaGemini($pertanyaan, $gambar_path = null, $db_context = []) {
    // 1. Tentukan API Key berdasarkan Role
    $user_role = $db_context['role'] ?? 'petani';
    $user_id   = $db_context['user_id'] ?? 0;
    $user_nama = $db_context['nama'] ?? 'Pengguna';
    $api_key   = getGeminiApiKey($user_role);
    $hari_ini  = date('d F Y');

    // 2. Tentukan Persona Berdasarkan Role
    if ($user_role === 'admin') {
        $persona_name = 'Admin Assistant AI';
        $persona_desc = 'asisten virtual resmi Administrator Platform Pertanian Padi Desa';
        $target_audience = 'Administrator';
    } elseif ($user_role === 'perangkat_desa') {
        $persona_name = 'Asisten Desa AI';
        $persona_desc = 'asisten virtual resmi Perangkat Desa untuk rekapitulasi data dan statistik pertanian desa';
        $target_audience = 'Perangkat Desa';
    } else {
        $persona_name = 'Sahabat Tani AI';
        $persona_desc = 'asisten virtual resmi Petani untuk budidaya padi dan data pertanian';
        $target_audience = 'Petani';
    }

    // 3. SUSUN SYSTEM PROMPT (Si AI jadi "Programmer SQL" yang Presisi)
    $system_prompt = "Kamu adalah $persona_name, $persona_desc.\n" .
                     "Hari ini: $hari_ini.\n" .
                     "Pengguna Saat Ini: $user_nama (Role: $user_role, ID: $user_id).\n\n" .

                     "=== TUGAS UTAMA ===\n" .
                     "Kamu memiliki akses langsung ke database sistem. Jika pengguna bertanya tentang DATA, JUMLAH, DAFTAR, REKAPITULASI, atau STATISTIK sistem (contoh: 'ada berapa perangkat desa?', 'siapa saja perangkat desa?', 'berapa petani terdaftar?', 'kapan panen lahan saya?', 'rekap lahan desa', 'siapa admin?'), kamu WAJIB membuat query SQL (SELECT) yang tepat untuk mengambil data riil dari database.\n" .
                     "DILARANG KERAS MENGARANG atau mengira-ngira data dan angka jika pertanyaan berkaitan dengan informasi sistem atau pengguna.\n\n" .

                     "=== STRUKTUR DATABASE YANG BOLEH DIAKSES (SESUAI ROLE: $user_role) ===\n" .
                     getDatabaseSchemaForAI($user_role, $user_id) . "\n\n" .

                     "=== ATURAN KETAT ===\n" .
                     "1. DILARANG KERAS menggunakan query manipulasi data (UPDATE, DELETE, INSERT, DROP, ALTER, dll). Cuma boleh query pembacaan SELECT.\n" .
                     "2. Jika pertanyaan murni umum tentang budidaya padi/konsultasi pertanian (misal: 'apa penyebab daun padi menguning?', 'bagaimana cara pemupukan berimbang?'), JANGAN buat query SQL, langsung jawab pakai pengetahuanmu.\n" .
                     "3. Jangan pernah membocorkan password_hash, token, atau data sensitif.\n\n" .

                     "=== FORMAT OUTPUT (WAJIB DIIKUTI TANPA TEKS LAIN!) ===\n" .
                     "Jika butuh data dari DB, jawab HANYA dengan format JSON:\n" .
                     "{\"action\": \"query_db\", \"sql\": \"SELECT ...\", \"reason\": \"Alasan singkat\"}\n\n" .
                     "Jika TIDAK butuh data dari DB, jawab HANYA dengan format JSON:\n" .
                     "{\"action\": \"jawab_langsung\", \"text\": \"Jawaban kamu di sini...\"}\n\n" .

                     "=== PERTANYAAN PENGGUNA ===\n" .
                     $pertanyaan;

    // 4. SUSUN PAYLOAD KE GEMINI
    $parts = [];
    $parts[] = ['text' => $system_prompt];

    // Kalau ada gambar, tambahkan
    if ($gambar_path && file_exists($gambar_path)) {
        $image_data = file_get_contents($gambar_path);
        $image_base64 = base64_encode($image_data);
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $gambar_path);
        finfo_close($finfo);
        
        if (!$mime_type || strpos($mime_type, 'image/') !== 0) {
            $mime_type = 'image/jpeg';
        }
        
        $parts[] = [
            'inline_data' => [
                'mime_type' => $mime_type,
                'data' => $image_base64
            ]
        ];
        
        // Tambah instruksi khusus gambar
        $parts[0]['text'] .= "\n\n[CATATAN GAMBAR]: Pengguna mengunggah gambar. Analisis gambar ini (hama/penyakit) dan berikan solusi. Abaikan instruksi JSON jika ini pertanyaan visual.";
    }

    // 5. KIRIM KE API
    $response_json = callGeminiAPI($api_key, $parts);
    
    // Kalau error koneksi
    if (isset($response_json['error'])) {
        return $response_json['error'];
    }

    $text_output = $response_json['text'] ?? '';
    
    // 6. PARSING OUTPUT AI
    // Bersihkan format markdown (```json ... ```) kalau AI menyertakannya
    $clean_json = preg_replace('/```json/i', '', $text_output);
    $clean_json = preg_replace('/```/i', '', $clean_json);
    $clean_json = trim($clean_json);
    
    $ai_data = json_decode($clean_json, true);

    // Fallback regex jika ada teks pengantar di luar blok JSON
    if ((json_last_error() !== JSON_ERROR_NONE || !isset($ai_data['action'])) && preg_match('/\{[\s\S]*\}/', $text_output, $json_matches)) {
        $candidate_data = json_decode($json_matches[0], true);
        if (json_last_error() === JSON_ERROR_NONE && isset($candidate_data['action'])) {
            $ai_data = $candidate_data;
        }
    }

    // Jika format JSON valid
    if (is_array($ai_data) && isset($ai_data['action'])) {
        
        // KONDISI A: AI mau query Database
        if ($ai_data['action'] === 'query_db' && !empty($ai_data['sql'])) {
            $raw_sql = $ai_data['sql'];
            
            // Satpam 1: Validasi SQL (Cuma SELECT yang aman)
            $safe_sql = sanitizeAiQuery($raw_sql);
            
            if ($safe_sql === false) {
                return "⚠️ Maaf, sistem mendeteksi permintaan data yang tidak valid atau tidak aman. Akses ditolak.";
            }

            // Satpam 2: Eksekusi Query
            global $conn;
            $result = mysqli_query($conn, $safe_sql);
            
            if (!$result) {
                // Kalau query error (misal salah nama kolom)
                return "⚠️ Maaf, saya gagal mengambil data dari sistem. (Error: " . mysqli_error($conn) . ")";
            }
            
            // Kumpulkan hasil query
            $rows = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $rows[] = $row;
            }

            // KONDISI B: Kirim hasil query balik ke AI untuk dirangkum
            $data_json = !empty($rows) ? json_encode($rows, JSON_PRETTY_PRINT) : "[] (Hasil query mengembalikan 0 baris data / tidak ada catatan yang sesuai)";
            
            $final_prompt = "Berikut adalah hasil query database untuk menjawab pertanyaan pengguna:\n" .
                            "Pengguna: $user_nama (Role: $user_role)\n" .
                            "Pertanyaan: $pertanyaan\n" .
                            "Data Hasil Database: $data_json\n\n" .
                            "Tugasmu: Rangkum data database di atas menjadi jawaban yang ramah, sopan, natural, dan akurat untuk $target_audience (dalam bahasa Indonesia).\n" .
                            "ATURAN SANGAT PENTING:\n" .
                            "1. Sebutkan angka / jumlah secara PASTI dan 100% AKURAT persis sesuai data di atas. Jika data kosong (0 baris), sampaikan dengan jelas bahwa saat ini belum ada data tercatat (jumlahnya 0).\n" .
                            "2. JANGAN menambah, mengurangi, atau berhalusinasi dengan angka lain di luar data di atas.\n" .
                            "3. Jika data memiliki rincian nama atau status, sebutkan secara jelas dan informatif.\n" .
                            "4. Jangan sebutkan istilah teknis query SQL, nama tabel database, atau format JSON. Jawab seolah kamu langsung mengetahui data tersebut.\n" .
                            "5. WAJIB TEKS BIASA TANPA SIMBOL MARKDOWN (tanpa *, #, _, atau `).\n";
                            
            $final_parts = [['text' => $final_prompt]];
            $final_response = callGeminiAPI($api_key, $final_parts);
            
            if (isset($final_response['error'])) {
                return $final_response['error'];
            }
            
            $final_text = $final_response['text'] ?? 'Data ditemukan, tapi gagal dirangkum.';
            return cleanOutputText($final_text);
        }
        
        // KONDISI C: AI jawab langsung tanpa DB
        if ($ai_data['action'] === 'jawab_langsung') {
            $text = $ai_data['text'] ?? '';
            return cleanOutputText($text);
        }
    }

    // Fallback: Kalau AI gagal nulis JSON, bersihkan teks jawaban
    return cleanOutputText($text_output);
}


/**
 * FUNGSI HELPER: Panggil API Gemini (Dengan Fallback Model & Retry)
 */
function callGeminiAPI($api_key, $parts) {
    $data = [
        'contents' => [['parts' => $parts]],
        'generationConfig' => [
            'temperature' => 0.3, // Rendah biar AI fokus & nggak halusinasi
            'maxOutputTokens' => 2000,
        ]
    ];

    $models_to_try = [
        defined('GEMINI_MODEL') ? GEMINI_MODEL : 'gemini-3.5-flash-lite',
        'gemini-3.5-flash-lite',
        'gemini-3.8-flash',
        'gemini-3.5-flash',
        'gemini-3.1-flash-lite'
    ];
    $models_to_try = array_values(array_unique($models_to_try));
    
    $last_http_code = 0;
    $last_curl_error = '';

    foreach ($models_to_try as $model) {
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . $model . ':generateContent?key=' . $api_key;
        
        for ($attempt = 1; $attempt <= 2; $attempt++) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'x-goog-api-key: ' . $api_key
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Matikan verify buat localhost
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curl_error = curl_error($ch);
            curl_close($ch);
            
            $last_http_code = $http_code;
            $last_curl_error = $curl_error;

            // Kalau sukses
            if ($http_code == 200) {
                $result = json_decode($response, true);
                $parts_out = $result['candidates'][0]['content']['parts'] ?? [];
                $text_collected = '';
                
                foreach ($parts_out as $p) {
                    if (isset($p['thought']) && $p['thought'] === true) continue; // Skip mode thinking
                    if (!empty($p['text'])) $text_collected .= $p['text'] . "\n";
                }
                
                return ['text' => trim($text_collected)];
            }
            
            // Retry kalau 503
            if ($http_code == 503 && $attempt == 1) {
                sleep(1);
                continue;
            }
            break; // Keluar dari retry loop jika bukan 503
        }
    }

    // Handle Error Codes
    if ($last_curl_error) return ['error' => "⚠️ Koneksi ke server AI terputus."];
    if ($last_http_code == 429) return ['error' => "⚠️ Kuota AI penuh. Coba lagi 1 menit lagi."];
    if ($last_http_code == 403) return ['error' => "⚠️ Akses AI ditolak (API Key salah/limit)."];
    
    return ['error' => "⚠️ AI sibuk (Error: $last_http_code). Coba lagi."];
}

/**
 * FUNGSI HELPER: Bersihkan Teks Output
 */
function cleanOutputText($text) {
    // Hapus markdown
    $text = str_replace(['*', '#', '_', '`'], '', $text);
    $text = preg_replace('/^[ \t]*-[ \t]+/m', '• ', $text);
    return trim($text);
}
?>