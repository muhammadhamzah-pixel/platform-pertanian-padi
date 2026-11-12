<?php
// pages/konfigurasi_ai.php
// Konfigurasi AI & Multi-Model Provider (Eksklusif Administrator)

include '../includes/config.php';
include '../includes/gemini_config.php';

// Proteksi Keamanan: KHUSUS ROLE ADMIN!
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ' . $base_url . 'login');
    exit();
}

$message = '';
$error = '';

// ============================================
// AJAX HANDLER: TES KONEKSI API KEY REAL-TIME
// ============================================
if (isset($_GET['ajax']) && $_GET['ajax'] === 'test_key') {
    header('Content-Type: application/json');
    
    // Verifikasi CSRF
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf)) {
        echo json_encode(['success' => false, 'message' => 'Token CSRF tidak valid. Silakan refresh halaman.']);
        exit();
    }

    $provider = sanitize_input($_POST['provider'] ?? 'gemini');
    $api_key  = trim($_POST['api_key'] ?? '');
    $model    = sanitize_input($_POST['model'] ?? '');

    $result = test_ai_connection($provider, $api_key, $model);
    echo json_encode($result);
    exit();
}

// ============================================
// FORM SUBMIT: SIMPAN PENGATURAN AI
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_GET['ajax'])) {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error = t('err_csrf');
    } else {
        $active_model        = sanitize_input($_POST['active_model'] ?? 'gemini-1.5-flash');
        $gemini_key_primary  = trim($_POST['gemini_key_primary'] ?? '');
        $gemini_key_backup_1 = trim($_POST['gemini_key_backup_1'] ?? '');
        $gemini_key_backup_2 = trim($_POST['gemini_key_backup_2'] ?? '');
        $groq_api_key        = trim($_POST['groq_api_key'] ?? '');
        $deepseek_api_key    = trim($_POST['deepseek_api_key'] ?? '');
        $openai_api_key      = trim($_POST['openai_api_key'] ?? '');
        $auto_failover       = isset($_POST['auto_failover']) ? 1 : 0;
        $ai_tone             = sanitize_input($_POST['ai_tone'] ?? 'seimbang');
        $temperature         = floatval($_POST['temperature'] ?? 0.3);
        $max_tokens          = intval($_POST['max_tokens'] ?? 2000);

        // Sanitize ke database
        $active_model_esc        = mysqli_real_escape_string($conn, $active_model);
        $gemini_key_primary_esc  = mysqli_real_escape_string($conn, $gemini_key_primary);
        $gemini_key_backup_1_esc = mysqli_real_escape_string($conn, $gemini_key_backup_1);
        $gemini_key_backup_2_esc = mysqli_real_escape_string($conn, $gemini_key_backup_2);
        $groq_api_key_esc        = mysqli_real_escape_string($conn, $groq_api_key);
        $deepseek_api_key_esc    = mysqli_real_escape_string($conn, $deepseek_api_key);
        $openai_api_key_esc      = mysqli_real_escape_string($conn, $openai_api_key);
        $ai_tone_esc             = mysqli_real_escape_string($conn, $ai_tone);

        // Pastikan tabel ada & simpan ke ID 1
        $update_sql = "INSERT INTO ai_settings (
            id, active_model, gemini_key_primary, gemini_key_backup_1, gemini_key_backup_2,
            groq_api_key, deepseek_api_key, openai_api_key, auto_failover, ai_tone, temperature, max_tokens, updated_at
        ) VALUES (
            1, '$active_model_esc', '$gemini_key_primary_esc', '$gemini_key_backup_1_esc', '$gemini_key_backup_2_esc',
            '$groq_api_key_esc', '$deepseek_api_key_esc', '$openai_api_key_esc', $auto_failover, '$ai_tone_esc', $temperature, $max_tokens, NOW()
        ) ON DUPLICATE KEY UPDATE
            active_model = VALUES(active_model),
            gemini_key_primary = VALUES(gemini_key_primary),
            gemini_key_backup_1 = VALUES(gemini_key_backup_1),
            gemini_key_backup_2 = VALUES(gemini_key_backup_2),
            groq_api_key = VALUES(groq_api_key),
            deepseek_api_key = VALUES(deepseek_api_key),
            openai_api_key = VALUES(openai_api_key),
            auto_failover = VALUES(auto_failover),
            ai_tone = VALUES(ai_tone),
            temperature = VALUES(temperature),
            max_tokens = VALUES(max_tokens),
            updated_at = NOW()";

        if (mysqli_query($conn, $update_sql)) {
            $message = '✅ Konfigurasi AI & Cadangan Kunci berhasil diperbarui!';
            catat_log($conn, $_SESSION['user_id'], $_SESSION['email'], $_SESSION['nama'], 'Update Konfigurasi AI', "Model aktif: $active_model");
            regenerate_csrf_token();
        } else {
            $error = '❌ Gagal menyimpan konfigurasi: ' . mysqli_error($conn);
        }
    }
}

// Ambil data terbaru
$settings = get_ai_settings($conn);
$models = get_available_ai_models();
$csrf_token = generate_csrf_token();

include '../includes/navbar.php';
?>

<style>
/* Styling Elegan Khusus Konfigurasi AI */
.model-picker-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}
.model-card-option {
    border: 2px solid #e2e8f0;
    border-radius: 16px;
    padding: 16px;
    background: #ffffff;
    cursor: pointer;
    transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    position: relative;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}
.model-card-option:hover {
    border-color: #10b981;
    transform: translateY(-2px);
    box-shadow: 0 10px 20px -5px rgba(16, 185, 129, 0.15);
}
.model-card-option.active {
    border-color: #059669;
    background: linear-gradient(180deg, #f0fdf4 0%, #ffffff 100%);
    box-shadow: 0 10px 25px -5px rgba(5, 150, 105, 0.25);
}
.model-card-radio {
    position: absolute;
    top: 16px;
    right: 16px;
    accent-color: #059669;
    width: 20px;
    height: 20px;
}
.provider-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    border-radius: 9999px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 8px;
}
.key-slot-card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 18px;
    margin-bottom: 16px;
    transition: all 0.2s ease;
}
.key-slot-card:focus-within {
    border-color: #10b981;
    background: #ffffff;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.08);
}
.test-result-box {
    margin-top: 10px;
    font-size: 13px;
    display: none;
    padding: 8px 12px;
    border-radius: 8px;
}
</style>

<!-- SIGNATURE DASH HERO HEADER -->
<div class="dash-hero-header">
    <div class="dash-hero-inner">
        <div class="dash-hero-title">
            <h1><i class="fas fa-microchip"></i> Konfigurasi AI & Multi-Model Pool</h1>
            <p>Pusat kendali AI tingkat Administrator: kelola 10 model pilihan, rotasi cadangan API Key, dan auto-failover anti-limit.</p>
        </div>
        <div class="dash-hero-actions">
            <a href="<?= $base_url ?>dashboard_admin" class="btn-outline-white">
                <i class="fas fa-arrow-left"></i> Dashboard Admin
            </a>
        </div>
    </div>
</div>

<!-- MAIN PAGE CONTAINER -->
<div class="main-page-container">

    <!-- FEEDBACK ALERT -->
    <?php if ($message): ?>
        <div class="alert-modern alert-modern-success mb-4">
            <i class="fas fa-check-circle fs-5"></i>
            <div><?= $message ?></div>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert-modern alert-modern-danger mb-4">
            <i class="fas fa-exclamation-circle fs-5"></i>
            <div><?= $error ?></div>
        </div>
    <?php endif; ?>

    <!-- BANNER PERINGATAN EKSKLUSIF ADMIN -->
    <div class="alert-modern alert-modern-warning mb-4" style="background: linear-gradient(135deg, #fffbeb, #fef3c7); border-color: #fde68a;">
        <i class="fas fa-shield-alt fs-4 text-warning"></i>
        <div>
            <strong class="text-dark">Fitur Eksklusif Administrator:</strong> 
            Pengaturan di halaman ini mengendalikan mesin kecerdasan buatan untuk seluruh sistem desa.
            Semua pengguna (Petani, Perangkat Desa, dan Admin) akan otomatis menikmati model aktif dan cadangan API Key yang Anda tentukan di sini tanpa mengubah hak akses database mereka.
        </div>
    </div>

    <form method="POST" action="" id="formAiConfig">
        <input type="hidden" name="csrf_token" id="formCsrfToken" value="<?= $csrf_token ?>">

        <!-- BAGIAN 1: PEMILIHAN MODEL AI AKTIF (10 MODEL) -->
        <div class="card-modern mb-4">
            <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                <div>
                    <h5 class="fw-bold text-dark mb-1">
                        <i class="fas fa-layer-group text-success me-2"></i> 1. Pilih Model AI Utama (10 Pilihan Lintas Provider)
                    </h5>
                    <p class="text-muted small mb-0">Klik pada salah satu kartu model untuk menjadikannya sebagai mesin AI aktif sistem.</p>
                </div>
                <span class="badge bg-success-subtle text-success px-3 py-2 rounded-pill fw-bold border border-success">
                    <i class="fas fa-check-circle me-1"></i> Model Aktif: <strong id="lblActiveModel"><?= htmlspecialchars($settings['active_model']) ?></strong>
                </span>
            </div>

            <div class="model-picker-grid">
                <?php foreach ($models as $mId => $m): ?>
                    <?php $isActive = ($settings['active_model'] === $mId); ?>
                    <label class="model-card-option <?= $isActive ? 'active' : '' ?>" onclick="selectModel('<?= $mId ?>', this)">
                        <input type="radio" name="active_model" value="<?= $mId ?>" class="model-card-radio" <?= $isActive ? 'checked' : '' ?>>
                        
                        <div>
                            <span class="provider-badge bg-<?= $m['badge_color'] ?>-subtle text-<?= $m['badge_color'] ?> border border-<?= $m['badge_color'] ?>">
                                <?= htmlspecialchars($m['provider']) ?> • <?= htmlspecialchars($m['badge']) ?>
                            </span>
                            <h6 class="fw-bold text-dark mb-1 mt-1"><?= htmlspecialchars($m['name']) ?></h6>
                            <p class="text-muted small mb-0" style="font-size: 12px; line-height: 1.4;">
                                <?= htmlspecialchars($m['desc']) ?>
                            </p>
                        </div>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- BAGIAN 2: POOL API KEY GOOGLE GEMINI (UTAMA & CADANGAN) -->
        <div class="card-modern mb-4">
            <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                <div>
                    <h5 class="fw-bold text-dark mb-1">
                        <i class="fab fa-google text-success me-2"></i> 2. Pool API Key Google Gemini (3 Tingkat Cadangan)
                    </h5>
                    <p class="text-muted small mb-0">Isi kunci API dari Google AI Studio. Sistem otomatis berganti ke cadangan bila kuota utama habis.</p>
                </div>
                <!-- TOMBOL PINTAS BUAT KEY BARU DI AI STUDIO -->
                <a href="https://aistudio.google.com/app/apikey" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-success rounded-pill fw-bold px-3 py-2">
                    <i class="fas fa-external-link-alt me-1"></i> 🔑 + Buat API Key di Google AI Studio
                </a>
            </div>

            <!-- SLOT 1: UTAMA -->
            <div class="key-slot-card">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <label class="form-label-modern mb-0 fw-bold text-dark">
                        <span class="badge bg-success me-1">UTAMA</span> API Key Gemini Primer (Wajib)
                    </label>
                    <button type="button" class="btn btn-sm btn-light border rounded-pill px-3" onclick="testSingleKey('gemini', 'gemini_key_primary', 'res_gemini_1')">
                        <i class="fas fa-bolt text-warning me-1"></i> Tes Koneksi
                    </button>
                </div>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="fas fa-key text-muted"></i></span>
                    <input type="password" name="gemini_key_primary" id="gemini_key_primary" class="form-control form-control-modern border-start-0" 
                           placeholder="AQ.Ab8RN... (Tempel API Key Gemini Utama Anda di sini)" 
                           value="<?= htmlspecialchars($settings['gemini_key_primary']) ?>" required>
                    <button class="btn btn-outline-secondary border" type="button" onclick="toggleVisibility('gemini_key_primary', this)">
                        <i class="far fa-eye"></i>
                    </button>
                </div>
                <div id="res_gemini_1" class="test-result-box"></div>
            </div>

            <!-- SLOT 2: CADANGAN 1 -->
            <div class="key-slot-card">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <label class="form-label-modern mb-0 fw-bold text-dark">
                        <span class="badge bg-secondary me-1">CADANGAN 1</span> API Key Gemini Cadangan #1
                    </label>
                    <button type="button" class="btn btn-sm btn-light border rounded-pill px-3" onclick="testSingleKey('gemini', 'gemini_key_backup_1', 'res_gemini_2')">
                        <i class="fas fa-bolt text-warning me-1"></i> Tes Koneksi
                    </button>
                </div>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="fas fa-shield-alt text-muted"></i></span>
                    <input type="password" name="gemini_key_backup_1" id="gemini_key_backup_1" class="form-control form-control-modern border-start-0" 
                           placeholder="AQ.Ab8RN... (Kunci cadangan otomatis bila kunci utama limit)" 
                           value="<?= htmlspecialchars($settings['gemini_key_backup_1']) ?>">
                    <button class="btn btn-outline-secondary border" type="button" onclick="toggleVisibility('gemini_key_backup_1', this)">
                        <i class="far fa-eye"></i>
                    </button>
                </div>
                <div id="res_gemini_2" class="test-result-box"></div>
            </div>

            <!-- SLOT 3: CADANGAN 2 -->
            <div class="key-slot-card">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <label class="form-label-modern mb-0 fw-bold text-dark">
                        <span class="badge bg-secondary me-1">CADANGAN 2</span> API Key Gemini Cadangan #2
                    </label>
                    <button type="button" class="btn btn-sm btn-light border rounded-pill px-3" onclick="testSingleKey('gemini', 'gemini_key_backup_2', 'res_gemini_3')">
                        <i class="fas fa-bolt text-warning me-1"></i> Tes Koneksi
                    </button>
                </div>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="fas fa-life-ring text-muted"></i></span>
                    <input type="password" name="gemini_key_backup_2" id="gemini_key_backup_2" class="form-control form-control-modern border-start-0" 
                           placeholder="AQ.Ab8RN... (Kunci lapis ketiga)" 
                           value="<?= htmlspecialchars($settings['gemini_key_backup_2']) ?>">
                    <button class="btn btn-outline-secondary border" type="button" onclick="toggleVisibility('gemini_key_backup_2', this)">
                        <i class="far fa-eye"></i>
                    </button>
                </div>
                <div id="res_gemini_3" class="test-result-box"></div>
            </div>

            <!-- NOTIFIKASI AUTO FAILOVER -->
            <div class="d-flex align-items-center justify-content-between p-3 bg-light rounded-3 border">
                <div class="d-flex align-items-center gap-3">
                    <i class="fas fa-sync-alt fa-2x text-success"></i>
                    <div>
                        <strong class="d-block text-dark">Fitur Auto-Failover Pintar (Anti-Limit)</strong>
                        <small class="text-muted">Jika diaktifkan, sistem secara otomatis merotasi ke kunci cadangan ketika terjadi batas kuota Google (Error 429).</small>
                    </div>
                </div>
                <div class="form-check form-switch fs-4 mb-0">
                    <input class="form-check-input" type="checkbox" name="auto_failover" id="auto_failover" <?= !empty($settings['auto_failover']) ? 'checked' : '' ?>>
                </div>
            </div>
        </div>

        <!-- BAGIAN 3: PROVIDER EKSTERNAL (GROQ, DEEPSEEK, OPENAI) -->
        <div class="card-modern mb-4">
            <div class="mb-3">
                <h5 class="fw-bold text-dark mb-1">
                    <i class="fas fa-network-wired text-primary me-2"></i> 3. Kunci API Provider Lain (Groq / DeepSeek / OpenAI)
                </h5>
                <p class="text-muted small mb-0">Opsional: Diperlukan hanya jika Anda memilih model Llama, Mixtral, DeepSeek, atau ChatGPT pada pilihan model di atas.</p>
            </div>

            <!-- GROQ -->
            <div class="key-slot-card mb-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <label class="form-label-modern mb-0 fw-bold text-dark">
                        <span class="badge bg-dark me-1">GROQ CLOUD</span> API Key Groq (Meta Llama 3.3 & Mixtral)
                    </label>
                    <div class="d-flex gap-2">
                        <a href="https://console.groq.com/keys" target="_blank" class="small text-decoration-none text-muted">
                            <i class="fas fa-external-link-alt"></i> Buat Key Groq
                        </a>
                        <button type="button" class="btn btn-sm btn-light border rounded-pill px-3" onclick="testSingleKey('groq', 'groq_api_key', 'res_groq')">
                            <i class="fas fa-bolt text-warning me-1"></i> Tes
                        </button>
                    </div>
                </div>
                <div class="input-group">
                    <input type="password" name="groq_api_key" id="groq_api_key" class="form-control form-control-modern" 
                           placeholder="gsk_... (Dapatkan gratis di console.groq.com)" 
                           value="<?= htmlspecialchars($settings['groq_api_key']) ?>">
                    <button class="btn btn-outline-secondary border" type="button" onclick="toggleVisibility('groq_api_key', this)"><i class="far fa-eye"></i></button>
                </div>
                <div id="res_groq" class="test-result-box"></div>
            </div>

            <!-- DEEPSEEK -->
            <div class="key-slot-card mb-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <label class="form-label-modern mb-0 fw-bold text-dark">
                        <span class="badge bg-primary me-1">DEEPSEEK</span> API Key DeepSeek (V3 & R1 Reasoner)
                    </label>
                    <div class="d-flex gap-2">
                        <a href="https://platform.deepseek.com/api_keys" target="_blank" class="small text-decoration-none text-muted">
                            <i class="fas fa-external-link-alt"></i> Buat Key DeepSeek
                        </a>
                        <button type="button" class="btn btn-sm btn-light border rounded-pill px-3" onclick="testSingleKey('deepseek', 'deepseek_api_key', 'res_deepseek')">
                            <i class="fas fa-bolt text-warning me-1"></i> Tes
                        </button>
                    </div>
                </div>
                <div class="input-group">
                    <input type="password" name="deepseek_api_key" id="deepseek_api_key" class="form-control form-control-modern" 
                           placeholder="sk-... (Dapatkan di platform.deepseek.com)" 
                           value="<?= htmlspecialchars($settings['deepseek_api_key']) ?>">
                    <button class="btn btn-outline-secondary border" type="button" onclick="toggleVisibility('deepseek_api_key', this)"><i class="far fa-eye"></i></button>
                </div>
                <div id="res_deepseek" class="test-result-box"></div>
            </div>

            <!-- OPENAI -->
            <div class="key-slot-card mb-0">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <label class="form-label-modern mb-0 fw-bold text-dark">
                        <span class="badge bg-success me-1">OPENAI</span> API Key OpenAI (GPT-4o Mini)
                    </label>
                    <div class="d-flex gap-2">
                        <a href="https://platform.openai.com/api-keys" target="_blank" class="small text-decoration-none text-muted">
                            <i class="fas fa-external-link-alt"></i> Buat Key OpenAI
                        </a>
                        <button type="button" class="btn btn-sm btn-light border rounded-pill px-3" onclick="testSingleKey('openai', 'openai_api_key', 'res_openai')">
                            <i class="fas fa-bolt text-warning me-1"></i> Tes
                        </button>
                    </div>
                </div>
                <div class="input-group">
                    <input type="password" name="openai_api_key" id="openai_api_key" class="form-control form-control-modern" 
                           placeholder="sk-proj-... (Dapatkan di platform.openai.com)" 
                           value="<?= htmlspecialchars($settings['openai_api_key']) ?>">
                    <button class="btn btn-outline-secondary border" type="button" onclick="toggleVisibility('openai_api_key', this)"><i class="far fa-eye"></i></button>
                </div>
                <div id="res_openai" class="test-result-box"></div>
            </div>
        </div>

        <!-- BAGIAN 4: PARAMETER PERILAKU & GAYA JAWABAN (TONE) -->
        <div class="card-modern mb-4">
            <h5 class="fw-bold text-dark mb-3">
                <i class="fas fa-sliders-h text-warning me-2"></i> 4. Karakteristik & Gaya Respon AI
            </h5>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label-modern fw-bold">Gaya Tutur Percakapan (Tone):</label>
                    <select name="ai_tone" class="form-select form-control-modern">
                        <option value="seimbang" <?= ($settings['ai_tone'] === 'seimbang') ? 'selected' : '' ?>>
                            🟢 Ramah, Seimbang & Informatif (Standar)
                        </option>
                        <option value="ringkas" <?= ($settings['ai_tone'] === 'ringkas') ? 'selected' : '' ?>>
                            ⚡ Praktis & Ringkas (To-the-point)
                        </option>
                        <option value="mendalam" <?= ($settings['ai_tone'] === 'mendalam') ? 'selected' : '' ?>>
                            📚 Edukatif & Mendalam (Pakar Agronomi)
                        </option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label-modern fw-bold">Kreativitas (Temperature):</label>
                    <input type="number" step="0.1" min="0.0" max="1.0" name="temperature" class="form-control form-control-modern" 
                           value="<?= htmlspecialchars($settings['temperature']) ?>" title="0.1-0.3 untuk fokus data SQL, 0.7 untuk kreatif">
                    <small class="text-muted" style="font-size: 11px;">Rekomendasi: 0.3 (Akurat data)</small>
                </div>

                <div class="col-md-3">
                    <label class="form-label-modern fw-bold">Maksimal Token:</label>
                    <input type="number" step="100" min="500" max="4000" name="max_tokens" class="form-control form-control-modern" 
                           value="<?= htmlspecialchars($settings['max_tokens']) ?>">
                    <small class="text-muted" style="font-size: 11px;">Batas panjang respon</small>
                </div>
            </div>
        </div>

        <!-- ACTION BUTTONS -->
        <div class="d-flex align-items-center justify-content-end gap-3 pb-5">
            <a href="<?= $base_url ?>dashboard_admin" class="btn btn-light px-4 py-2 rounded-pill fw-semibold border">
                Batal
            </a>
            <button type="submit" class="btn-primary-green px-5 py-2 fs-6">
                <i class="fas fa-save me-1"></i> Simpan Konfigurasi AI
            </button>
        </div>

    </form>

</div>

<script>
// Toggle Password Masking
function toggleVisibility(inputId, btn) {
    const input = document.getElementById(inputId);
    const icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// Select Model Card Interactive
function selectModel(modelId, cardEl) {
    document.querySelectorAll('.model-card-option').forEach(c => c.classList.remove('active'));
    cardEl.classList.add('active');
    const radio = cardEl.querySelector('input[type="radio"]');
    if (radio) radio.checked = true;
    document.getElementById('lblActiveModel').innerText = modelId;
}

// Test Single Key AJAX
async function testSingleKey(provider, inputId, resultBoxId) {
    const keyVal = document.getElementById(inputId).value.trim();
    const resultBox = document.getElementById(resultBoxId);
    const csrfToken = document.getElementById('formCsrfToken').value;

    resultBox.style.display = 'block';
    resultBox.className = 'test-result-box bg-light border text-muted';
    resultBox.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Menghubungi server ' + provider.toUpperCase() + '...';

    if (!keyVal) {
        resultBox.className = 'test-result-box bg-danger-subtle text-danger border border-danger';
        resultBox.innerHTML = '<i class="fas fa-exclamation-circle me-1"></i> Kunci API masih kosong. Silakan isi terlebih dahulu.';
        return;
    }

    try {
        const formData = new FormData();
        formData.append('csrf_token', csrfToken);
        formData.append('provider', provider);
        formData.append('api_key', keyVal);

        const response = await fetch('<?= $base_url ?>konfigurasi_ai?ajax=test_key', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            resultBox.className = 'test-result-box bg-success-subtle text-success border border-success';
            resultBox.innerHTML = '<i class="fas fa-check-circle me-1"></i> ' + data.message;
        } else {
            if (data.status === 'rate_limited') {
                resultBox.className = 'test-result-box bg-warning-subtle text-warning-emphasis border border-warning';
            } else {
                resultBox.className = 'test-result-box bg-danger-subtle text-danger border border-danger';
            }
            resultBox.innerHTML = '<i class="fas fa-info-circle me-1"></i> ' + data.message;
        }
    } catch (err) {
        resultBox.className = 'test-result-box bg-danger-subtle text-danger border border-danger';
        resultBox.innerHTML = '<i class="fas fa-exclamation-triangle me-1"></i> Terjadi kesalahan jaringan saat tes koneksi.';
    }
}
</script>

<?php include '../includes/footer.php'; ?>
