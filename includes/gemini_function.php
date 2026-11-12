<?php
// includes/gemini_function.php
// Fungsi untuk memanggil AI Multi-Model (Gemini, Groq, DeepSeek, OpenAI)
// Mendukung Text + Gambar + Text-to-SQL + Auto-Failover Cadangan API Key

require_once __DIR__ . '/gemini_config.php';
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
    global $conn;
    $settings = get_ai_settings($conn);

    $user_role = $db_context['role'] ?? 'petani';
    $user_id   = $db_context['user_id'] ?? 0;
    $user_nama = $db_context['nama'] ?? 'Pengguna';
    $hari_ini  = date('d F Y');
    $tone      = $settings['ai_tone'] ?? 'seimbang';

    // 1. Tentukan Persona Berdasarkan Role
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

    // 2. Panduan Gaya Jawaban (Tone)
    $tone_instruction = "";
    if ($tone === 'ringkas') {
        $tone_instruction = "GAYA JAWABAN: Sangat ringkas, padat, to-the-point, tanpa basa-basi berlebih.";
    } elseif ($tone === 'mendalam') {
        $tone_instruction = "GAYA JAWABAN: Edukatif, mendalam, ilmiah praktis, dan memberikan wawasan komprehensif ala pakar pertanian.";
    } else {
        $tone_instruction = "GAYA JAWABAN: Seimbang, ramah, bersahabat, jelas, dan informatif.";
    }

    // 3. Susun System Prompt (Text-to-SQL Satpam)
    $system_prompt = "Kamu adalah $persona_name, $persona_desc.\n" .
                     "Hari ini: $hari_ini.\n" .
                     "Pengguna Saat Ini: $user_nama (Role: $user_role, ID: $user_id).\n" .
                     "$tone_instruction\n\n" .

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

    // 4. Eksekusi Panggilan AI Tahap 1
    $response = callUnifiedAI($system_prompt, $gambar_path, $settings);

    if (isset($response['error'])) {
        return $response['error'];
    }

    $text_output = $response['text'] ?? '';

    // 5. Parsing Output JSON
    $clean_json = preg_replace('/```json/i', '', $text_output);
    $clean_json = preg_replace('/```/i', '', $clean_json);
    $clean_json = trim($clean_json);

    $ai_data = json_decode($clean_json, true);

    if ((json_last_error() !== JSON_ERROR_NONE || !is_array($ai_data)) && preg_match('/\{[\s\S]*\}/', $text_output, $json_matches)) {
        $candidate_data = json_decode($json_matches[0], true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($candidate_data)) {
            $ai_data = $candidate_data;
        }
    }

    $action_normalized = '';
    if (is_array($ai_data) && !empty($ai_data['action'])) {
        $action_normalized = strtolower(str_replace(['_', '-', ' '], '', $ai_data['action']));
    }

    // 6. Tangani Aksi Query DB atau Jawaban Langsung
    if ($action_normalized === 'querydb' && !empty($ai_data['sql'])) {
        $raw_sql = $ai_data['sql'];

        // Satpam SQL
        $safe_sql = sanitizeAiQuery($raw_sql);
        if ($safe_sql === false) {
            return "⚠️ Maaf, sistem mendeteksi permintaan data yang tidak aman. Akses query dibatasi.";
        }

        $result = @mysqli_query($conn, $safe_sql);
        if (!$result) {
            return "⚠️ Maaf, saya gagal mengambil data dari database. (Error: " . mysqli_error($conn) . ")";
        }

        $rows = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }

        // KONDISI B: Kirim balik hasil query ke AI untuk dirangkum
        $data_json = !empty($rows) ? json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : "[] (Hasil query mengembalikan 0 baris data / tidak ada catatan)";

        $final_prompt = "Berikut adalah hasil query database untuk menjawab pertanyaan pengguna:\n" .
                        "Pengguna: $user_nama (Role: $user_role)\n" .
                        "Pertanyaan: $pertanyaan\n" .
                        "Data Hasil Database: $data_json\n\n" .
                        "Tugasmu: Rangkum data database di atas menjadi jawaban yang ramah, sopan, natural, dan akurat untuk $target_audience (dalam bahasa Indonesia).\n" .
                        "ATURAN SANGAT PENTING:\n" .
                        "1. Sebutkan angka / jumlah secara PASTI dan 100% AKURAT persis sesuai data di atas. Jika data kosong (0 baris), sampaikan dengan jelas bahwa saat ini belum ada data tercatat (jumlahnya 0).\n" .
                        "2. JANGAN menambah, mengurangi, atau berhalusinasi dengan angka lain di luar data di atas.\n" .
                        "3. Jika data memiliki rincian nama atau status, sebutkan secara jelas dan informatif.\n" .
                        "4. Jangan sebutkan istilah teknis query SQL, nama tabel database, atau format JSON.\n" .
                        "5. WAJIB TEKS BIASA TANPA SIMBOL MARKDOWN (tanpa *, #, _, atau `).\n";

        $final_response = callUnifiedAI($final_prompt, null, $settings);
        if (isset($final_response['error'])) {
            return $final_response['error'];
        }

        $final_text = $final_response['text'] ?? 'Data ditemukan, namun gagal merangkum.';
        return extractCleanAnswer($final_text);
    }

    // KONDISI C: AI jawab langsung (baik action jawab_langsung, jawablangsung, atau ada field text/jawaban)
    if (is_array($ai_data)) {
        if (!empty($ai_data['text'])) {
            return cleanOutputText($ai_data['text']);
        }
        if (!empty($ai_data['jawaban'])) {
            return cleanOutputText($ai_data['jawaban']);
        }
    }

    // Jika parsing JSON di atas lolos / format tidak standar, gunakan fungsi ekstraksi bersih serba-bisa:
    return extractCleanAnswer($text_output);
}

/**
 * FUNGSI INTI: Panggil AI Terpadu (Gemini / Groq / DeepSeek / OpenAI)
 * Mendukung Auto-Failover Cadangan API Key
 */
function callUnifiedAI($prompt, $gambar_path = null, $settings = null) {
    if (!$settings) {
        $settings = get_ai_settings();
    }

    $active_model = $settings['active_model'] ?? 'gemini-3.5-flash-lite';
    $models_list  = get_available_ai_models();
    $model_info   = $models_list[$active_model] ?? [
        'provider_key' => 'gemini',
        'provider'     => 'Google Gemini'
    ];
    $provider = $model_info['provider_key'] ?? 'gemini';

    // 1. JIKA PROVIDER ADALAH GEMINI (Google AI Studio)
    if ($provider === 'gemini') {
        // Susun urutan kunci (Utama -> Cadangan 1 -> Cadangan 2)
        $key_pool = [];
        if (!empty($settings['gemini_key_primary']))  $key_pool[] = $settings['gemini_key_primary'];
        if (!empty($settings['gemini_key_backup_1'])) $key_pool[] = $settings['gemini_key_backup_1'];
        if (!empty($settings['gemini_key_backup_2'])) $key_pool[] = $settings['gemini_key_backup_2'];
        
        // Fallback local define jika pool kosong
        if (empty($key_pool)) {
            if (defined('GEMINI_API_KEY_ADMIN')) $key_pool[] = GEMINI_API_KEY_ADMIN;
        }

        $key_pool = array_values(array_unique($key_pool));

        if (empty($key_pool)) {
            return ['error' => '⚠️ API Key Gemini belum disetel. Hubungi Administrator untuk mengisi API Key di menu Konfigurasi AI.'];
        }

        $last_error = '⚠️ Layanan AI sedang sibuk.';

        foreach ($key_pool as $index => $api_key) {
            $res = callSingleGemini($api_key, $active_model, $prompt, $gambar_path, $settings);
            
            if (isset($res['text'])) {
                return $res; // Berhasil!
            }

            // Kalau kena rate limit (429) atau invalid key dan failover aktif, lanjut ke key cadangan berikutnya
            $last_error = $res['error'] ?? $last_error;
            if (!empty($settings['auto_failover'])) {
                continue; // Coba key berikutnya di pool
            } else {
                break; // Stop kalau failover dimatikan
            }
        }

        return ['error' => $last_error];
    }

    // 2. JIKA PROVIDER ADALAH GROQ / DEEPSEEK / OPENAI
    $provider_key = '';
    $endpoint = '';

    if ($provider === 'groq') {
        $provider_key = $settings['groq_api_key'] ?? '';
        $endpoint = 'https://api.groq.com/openai/v1/chat/completions';
    } elseif ($provider === 'deepseek') {
        $provider_key = $settings['deepseek_api_key'] ?? '';
        $endpoint = 'https://api.deepseek.com/chat/completions';
    } elseif ($provider === 'openai') {
        $provider_key = $settings['openai_api_key'] ?? '';
        $endpoint = 'https://api.openai.com/v1/chat/completions';
    }

    // Jika key provider eksternal diisi, panggil provider tersebut
    if (!empty($provider_key)) {
        $res = callOpenAICompatibleAPI($endpoint, $provider_key, $active_model, $prompt, $settings);
        if (isset($res['text'])) {
            return $res;
        }
    }

    // Fallback otomatis ke Gemini jika provider eksternal gagal atau key belum diisi
    if (!empty($settings['auto_failover'])) {
        $gemini_key = !empty($settings['gemini_key_primary']) ? $settings['gemini_key_primary'] : (defined('GEMINI_API_KEY_ADMIN') ? GEMINI_API_KEY_ADMIN : '');
        if (!empty($gemini_key)) {
            return callSingleGemini($gemini_key, 'gemini-3.5-flash-lite', $prompt, $gambar_path, $settings);
        }
    }

    return ['error' => "⚠️ Gagal menghubungi provider $provider. Periksa API Key di menu Konfigurasi AI."];
}

/**
 * Panggil API Google Gemini Tunggal dengan Payload Multi-Part (Teks & Gambar)
 */
function callSingleGemini($api_key, $model, $prompt, $gambar_path = null, $settings = null) {
    $temperature = floatval($settings['temperature'] ?? 0.3);
    $max_tokens  = intval($settings['max_tokens'] ?? 2000);

    $parts = [];
    $parts[] = ['text' => $prompt];

    if ($gambar_path && file_exists($gambar_path)) {
        $image_data = @file_get_contents($gambar_path);
        if ($image_data) {
            $image_base64 = base64_encode($image_data);
            $mime_type = 'image/jpeg';
            if (function_exists('finfo_open')) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $detected_mime = finfo_file($finfo, $gambar_path);
                finfo_close($finfo);
                if ($detected_mime && strpos($detected_mime, 'image/') === 0) {
                    $mime_type = $detected_mime;
                }
            }
            $parts[] = [
                'inline_data' => [
                    'mime_type' => $mime_type,
                    'data' => $image_base64
                ]
            ];
            $parts[0]['text'] .= "\n\n[CATATAN GAMBAR]: Analisis foto tanaman/daun padi ini dengan teliti dan berikan rekomendasi penanganan budidaya.";
        }
    }

    $payload = [
        'contents' => [['parts' => $parts]],
        'generationConfig' => [
            'temperature' => $temperature,
            'maxOutputTokens' => $max_tokens,
        ]
    ];

    $models_fallback = array_values(array_unique([
        $model,
        'gemini-3.5-flash-lite',
        'gemini-3.5-flash',
        'gemini-2.5-flash',
        'gemini-2.5-pro'
    ]));

    $last_http_code = 0;
    $last_curl_error = '';

    foreach ($models_fallback as $current_model) {
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . $current_model . ':generateContent?key=' . urlencode($api_key);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'x-goog-api-key: ' . $api_key
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        $last_http_code = $http_code;
        $last_curl_error = $curl_error;

        if ($http_code == 200) {
            $result = json_decode($response, true);
            $parts_out = $result['candidates'][0]['content']['parts'] ?? [];
            $text_collected = '';
            foreach ($parts_out as $p) {
                if (isset($p['thought']) && $p['thought'] === true) continue;
                if (!empty($p['text'])) $text_collected .= $p['text'] . "\n";
            }
            return ['text' => trim($text_collected)];
        }

        // Jika 429 atau 403, jangan buang waktu coba model lain dengan key yang sama, biar loop failover kunci berjalan
        if ($http_code == 429 || $http_code == 403 || $http_code == 400) {
            break;
        }
    }

    if ($last_http_code == 429) {
        return ['error' => '⚠️ Kuota harian API Key Gemini telah tercapai (Error 429).'];
    }
    if ($last_http_code == 403 || $last_http_code == 400) {
        return ['error' => '⚠️ Akses API Key Google ditolak (Periksa validitas key Anda).'];
    }
    if ($last_curl_error) {
        return ['error' => '⚠️ Koneksi jaringan ke Google AI terputus.'];
    }

    return ['error' => "⚠️ Server AI merespon dengan status HTTP $last_http_code."];
}

/**
 * Panggil API OpenAI-Compatible (Groq, DeepSeek, OpenAI)
 */
function callOpenAICompatibleAPI($endpoint, $api_key, $model, $prompt, $settings = null) {
    $temperature = floatval($settings['temperature'] ?? 0.3);
    $max_tokens  = intval($settings['max_tokens'] ?? 2000);

    $payload = [
        'model' => $model,
        'messages' => [
            ['role' => 'user', 'content' => $prompt]
        ],
        'temperature' => $temperature,
        'max_tokens' => $max_tokens
    ];

    $ch = curl_init($endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $api_key
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($http_code == 200) {
        $result = json_decode($response, true);
        $content = $result['choices'][0]['message']['content'] ?? '';
        return ['text' => trim($content)];
    }

    return ['error' => "⚠️ Provider eksternal ($model) merespon kode $http_code."];
}

/**
 * Mengekstrak teks murni jawaban AI dari format JSON mentah (apapun bentuknya)
 */
function extractCleanAnswer($text_output) {
    if (empty($text_output) || !is_string($text_output)) return '';

    // 1. Coba decode JSON langsung
    $clean_json = preg_replace('/```json/i', '', $text_output);
    $clean_json = preg_replace('/```/i', '', $clean_json);
    $clean_json = trim($clean_json);

    $ai_data = json_decode($clean_json, true);

    // Fallback regex jika ada teks pengantar di luar blok JSON
    if ((json_last_error() !== JSON_ERROR_NONE || !is_array($ai_data)) && preg_match('/\{[\s\S]*\}/', $text_output, $json_matches)) {
        $candidate = json_decode($json_matches[0], true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($candidate)) {
            $ai_data = $candidate;
        }
    }

    if (is_array($ai_data)) {
        if (!empty($ai_data['text'])) {
            return cleanOutputText($ai_data['text']);
        }
        if (!empty($ai_data['jawaban'])) {
            return cleanOutputText($ai_data['jawaban']);
        }
    }

    // 2. Fallback regex jika JSON decode gagal karena kutip/karakter khusus
    if (preg_match('/"(?:text|jawaban)"\s*:\s*"((?:[^"\\\\]|\\\\.)*)"/s', $text_output, $m)) {
        $extracted = stripcslashes($m[1]);
        return cleanOutputText($extracted);
    }

    // 3. Fallback pembersihan tag JSON mentah di awal & akhir string
    $cleaned = preg_replace('/^\s*\{.*?"(?:text|jawaban)"\s*:\s*"?/is', '', $text_output);
    $cleaned = preg_replace('/"?\s*\}\s*$/s', '', $cleaned);
    return cleanOutputText($cleaned);
}

/**
 * Membersihkan output teks dari karakter markdown dan sisa-sisa simbol teknis JSON
 */
function cleanOutputText($text) {
    if (!is_string($text)) return '';

    // Hapus sisa-sisa pembungkus JSON jika masih ada
    $text = preg_replace('/^\s*\{\s*"action"\s*:\s*"[^"]+"\s*,\s*"(?:text|jawaban)"\s*:\s*"?/is', '', $text);
    $text = preg_replace('/"?\s*\}\s*$/s', '', $text);

    // Hapus kurung kurawal pembungkus JSON lainnya jika ada di awal & akhir
    $text = preg_replace('/^\s*\{.*?"(?:text|jawaban)"\s*:\s*"?/is', '', $text);
    $text = preg_replace('/"?\s*\}\s*$/s', '', $text);

    // Jika ada literal \n atau \" yang belum ter-decode
    if (strpos($text, '\n') !== false && strpos($text, "\n") === false) {
        $text = str_replace('\n', "\n", $text);
    }
    if (strpos($text, '\"') !== false) {
        $text = str_replace('\"', '"', $text);
    }

    // Bersihkan markdown (*, #, _, `)
    $text = str_replace(['*', '#', '_', '`'], '', $text);
    // Rapikan bullet point dash menjadi bulatan yang rapi
    $text = preg_replace('/^[ \t]*-[ \t]+/m', '• ', $text);

    return trim($text);
}
?>
