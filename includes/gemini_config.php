<?php
// includes/gemini_config.php
// Konfigurasi AI Multi-Model & Multi-Key Pool (Google Gemini, Groq, DeepSeek, OpenAI)

// Muat API key lokal jika ada (agar aman dari repository publik)
if (file_exists(__DIR__ . '/gemini_key_local.php')) {
    include_once __DIR__ . '/gemini_key_local.php';
}

// Fallback placeholder jika key lokal belum disetel
if (!defined('GEMINI_API_KEY_ADMIN')) {
    define('GEMINI_API_KEY_ADMIN', 'YOUR_GEMINI_API_KEY_ADMIN');
}
if (!defined('GEMINI_API_KEY_DESA')) {
    define('GEMINI_API_KEY_DESA', 'YOUR_GEMINI_API_KEY_DESA');
}
if (!defined('GEMINI_API_KEY_PETANI')) {
    define('GEMINI_API_KEY_PETANI', 'YOUR_GEMINI_API_KEY_PETANI');
}

/**
 * Mendapatkan daftar 10 Model AI pilihan lintas provider
 */
function get_available_ai_models() {
    return [
        'gemini-1.5-flash' => [
            'id' => 'gemini-1.5-flash',
            'name' => 'Gemini 1.5 Flash',
            'provider' => 'Google Gemini',
            'provider_key' => 'gemini',
            'badge' => 'Rekomendasi Utama',
            'badge_color' => 'success',
            'desc' => 'Super cepat, hemat kuota token, sangat responsif untuk konsultasi harian petani.'
        ],
        'gemini-1.5-pro' => [
            'id' => 'gemini-1.5-pro',
            'name' => 'Gemini 1.5 Pro',
            'provider' => 'Google Gemini',
            'provider_key' => 'gemini',
            'badge' => 'Super Cerdas',
            'badge_color' => 'primary',
            'desc' => 'Penalaran tingkat tinggi & multimodal, paling akurat mendiagnosis foto hama & penyakit padi.'
        ],
        'gemini-2.0-flash-exp' => [
            'id' => 'gemini-2.0-flash-exp',
            'name' => 'Gemini 2.0 Flash (Next-Gen)',
            'provider' => 'Google Gemini',
            'provider_key' => 'gemini',
            'badge' => 'Experimental',
            'badge_color' => 'warning',
            'desc' => 'Arsitektur generasi terbaru dari Google dengan kapabilitas penalaran real-time mutakhir.'
        ],
        'gemini-1.5-flash-8b' => [
            'id' => 'gemini-1.5-flash-8b',
            'name' => 'Gemini 1.5 Flash-8B',
            'provider' => 'Google Gemini',
            'provider_key' => 'gemini',
            'badge' => 'Ultra Hemat',
            'badge_color' => 'info',
            'desc' => 'Versi hemat sumber daya, dirancang untuk throughput tinggi dengan latensi super rendah.'
        ],
        'llama-3.3-70b-versatile' => [
            'id' => 'llama-3.3-70b-versatile',
            'name' => 'Meta Llama 3.3 70B',
            'provider' => 'Groq Cloud',
            'provider_key' => 'groq',
            'badge' => 'Open Source Flagship',
            'badge_color' => 'dark',
            'desc' => 'Model open-source terkuat di dunia via Groq LPUs dengan kecepatan eksekusi instan.'
        ],
        'llama-3.1-8b-instant' => [
            'id' => 'llama-3.1-8b-instant',
            'name' => 'Meta Llama 3.1 8B Instant',
            'provider' => 'Groq Cloud',
            'provider_key' => 'groq',
            'badge' => 'Zero-Latency ⚡',
            'badge_color' => 'secondary',
            'desc' => 'Kecepatan inferensi tercepat (ratusan token per detik), cocok untuk tanya jawab kilat.'
        ],
        'mixtral-8x7b-32768' => [
            'id' => 'mixtral-8x7b-32768',
            'name' => 'Mixtral 8x7B MoE',
            'provider' => 'Groq Cloud',
            'provider_key' => 'groq',
            'badge' => 'Expert Mixture',
            'badge_color' => 'info',
            'desc' => 'Model arsitektur pakar jamak dari Mistral AI, sangat piawai mengolah angka & rekap desa.'
        ],
        'deepseek-chat' => [
            'id' => 'deepseek-chat',
            'name' => 'DeepSeek-V3',
            'provider' => 'DeepSeek',
            'provider_key' => 'deepseek',
            'badge' => 'Trending 🌟',
            'badge_color' => 'primary',
            'desc' => 'Model terobosan dengan efisiensi tinggi dan kemampuan setara GPT-4o dalam penalaran.'
        ],
        'deepseek-reasoner' => [
            'id' => 'deepseek-reasoner',
            'name' => 'DeepSeek-R1 (Reasoner)',
            'provider' => 'DeepSeek',
            'provider_key' => 'deepseek',
            'badge' => 'Deep Thinking',
            'badge_color' => 'danger',
            'desc' => 'Model pemikir dengan Chain-of-Thought (CoT), sangat teliti dalam takaran pupuk & kalkulasi.'
        ],
        'gpt-4o-mini' => [
            'id' => 'gpt-4o-mini',
            'name' => 'OpenAI GPT-4o Mini',
            'provider' => 'OpenAI',
            'provider_key' => 'openai',
            'badge' => 'Industry Standard',
            'badge_color' => 'success',
            'desc' => 'Model cerdas dan hemat dari OpenAI dengan pemahaman bahasa Indonesia yang sangat luwes.'
        ]
    ];
}

/**
 * Mengambil settingan AI aktif dari database table ai_settings
 */
function get_ai_settings($conn = null) {
    static $cached_settings = null;
    if ($cached_settings !== null) {
        return $cached_settings;
    }

    if (!$conn) {
        global $conn;
    }

    $default = [
        'id' => 1,
        'active_model' => 'gemini-1.5-flash',
        'gemini_key_primary' => defined('GEMINI_API_KEY_ADMIN') ? GEMINI_API_KEY_ADMIN : '',
        'gemini_key_backup_1' => defined('GEMINI_API_KEY_DESA') ? GEMINI_API_KEY_DESA : '',
        'gemini_key_backup_2' => defined('GEMINI_API_KEY_PETANI') ? GEMINI_API_KEY_PETANI : '',
        'groq_api_key' => '',
        'deepseek_api_key' => '',
        'openai_api_key' => '',
        'auto_failover' => 1,
        'ai_tone' => 'seimbang',
        'temperature' => 0.3,
        'max_tokens' => 2000,
        'last_tested_at' => null
    ];

    if ($conn) {
        $q = @mysqli_query($conn, "SELECT * FROM ai_settings WHERE id = 1 LIMIT 1");
        if ($q && $row = mysqli_fetch_assoc($q)) {
            $cached_settings = array_merge($default, $row);
            return $cached_settings;
        }
    }

    $cached_settings = $default;
    return $cached_settings;
}

/**
 * Tes koneksi ke API Key dan Model tertentu
 */
function test_ai_connection($provider, $api_key, $model = null) {
    $api_key = trim($api_key);
    if (empty($api_key)) {
        return [
            'success' => false,
            'status' => 'empty',
            'code' => 0,
            'message' => 'API Key masih kosong. Silakan isi terlebih dahulu.'
        ];
    }

    $start_time = microtime(true);

    if ($provider === 'gemini') {
        $test_model = $model ?: 'gemini-1.5-flash';
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . $test_model . ':generateContent?key=' . urlencode($api_key);
        $payload = [
            'contents' => [
                ['parts' => [['text' => 'ping']]]
            ],
            'generationConfig' => [
                'maxOutputTokens' => 5,
                'temperature' => 0.1
            ]
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_TIMEOUT, 12);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_err = curl_error($ch);
        curl_close($ch);

        $latency = round((microtime(true) - $start_time) * 1000);

        if ($curl_err) {
            return [
                'success' => false,
                'status' => 'network_error',
                'code' => 0,
                'latency' => $latency,
                'message' => 'Gagal terhubung ke Google AI: ' . $curl_err
            ];
        }

        if ($http_code === 200) {
            return [
                'success' => true,
                'status' => 'active',
                'code' => 200,
                'latency' => $latency,
                'message' => "🟢 Koneksi Sukses! Model $test_model merespon dalam {$latency}ms."
            ];
        } elseif ($http_code === 429) {
            return [
                'success' => false,
                'status' => 'rate_limited',
                'code' => 429,
                'latency' => $latency,
                'message' => "🟡 Kuota Habis / Rate Limit (Error 429). Sistem akan otomatis memakai key cadangan."
            ];
        } elseif ($http_code === 400 || $http_code === 403) {
            return [
                'success' => false,
                'status' => 'invalid_key',
                'code' => $http_code,
                'latency' => $latency,
                'message' => "🔴 API Key Ditolak / Tidak Valid (Error $http_code). Periksa kembali key Anda."
            ];
        } else {
            return [
                'success' => false,
                'status' => 'error',
                'code' => $http_code,
                'latency' => $latency,
                'message' => "⚠️ Respon Google: HTTP Code $http_code."
            ];
        }
    } else {
        // OpenAI-Compatible (Groq, DeepSeek, OpenAI)
        $endpoint = 'https://api.groq.com/openai/v1/chat/completions';
        $test_model = $model ?: 'llama-3.1-8b-instant';

        if ($provider === 'deepseek') {
            $endpoint = 'https://api.deepseek.com/chat/completions';
            $test_model = $model ?: 'deepseek-chat';
        } elseif ($provider === 'openai') {
            $endpoint = 'https://api.openai.com/v1/chat/completions';
            $test_model = $model ?: 'gpt-4o-mini';
        }

        $payload = [
            'model' => $test_model,
            'messages' => [['role' => 'user', 'content' => 'ping']],
            'max_tokens' => 5
        ];

        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $api_key
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_TIMEOUT, 12);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_err = curl_error($ch);
        curl_close($ch);

        $latency = round((microtime(true) - $start_time) * 1000);

        if ($curl_err) {
            return [
                'success' => false,
                'status' => 'network_error',
                'code' => 0,
                'latency' => $latency,
                'message' => 'Gagal terhubung ke ' . ucfirst($provider) . ': ' . $curl_err
            ];
        }

        if ($http_code === 200) {
            return [
                'success' => true,
                'status' => 'active',
                'code' => 200,
                'latency' => $latency,
                'message' => "🟢 Koneksi Sukses! Provider " . ucfirst($provider) . " ($test_model) merespon dalam {$latency}ms."
            ];
        } elseif ($http_code === 429) {
            return [
                'success' => false,
                'status' => 'rate_limited',
                'code' => 429,
                'latency' => $latency,
                'message' => "🟡 Kuota Habis (Rate Limit 429) di provider " . ucfirst($provider) . "."
            ];
        } else {
            return [
                'success' => false,
                'status' => 'error',
                'code' => $http_code,
                'latency' => $latency,
                'message' => "🔴 Respon " . ucfirst($provider) . ": HTTP Code $http_code. Periksa API key Anda."
            ];
        }
    }
}

// ============================================
// KONSTANTA & FUNGSI UNTUK KOMPATIBILITAS KODE LAMA
// ============================================
$current_ai_settings = get_ai_settings();
if (!defined('GEMINI_MODEL')) {
    define('GEMINI_MODEL', $current_ai_settings['active_model'] ?? 'gemini-1.5-flash');
}
if (!defined('GEMINI_ENDPOINT')) {
    define('GEMINI_ENDPOINT', 'https://generativelanguage.googleapis.com/v1beta/models/' . GEMINI_MODEL . ':generateContent');
}

/**
 * Kompatibilitas fungsi pemilih API key lama
 */
function getGeminiApiKey($role = 'petani') {
    $settings = get_ai_settings();
    // Gunakan Key Utama jika tersedia
    if (!empty($settings['gemini_key_primary'])) {
        return $settings['gemini_key_primary'];
    }
    // Fallback role
    if ($role === 'admin' && defined('GEMINI_API_KEY_ADMIN')) return GEMINI_API_KEY_ADMIN;
    if ($role === 'perangkat_desa' && defined('GEMINI_API_KEY_DESA')) return GEMINI_API_KEY_DESA;
    if (defined('GEMINI_API_KEY_PETANI')) return GEMINI_API_KEY_PETANI;
    return '';
}
?>
