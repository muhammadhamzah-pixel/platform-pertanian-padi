<?php
// pages/konfigurasi_ai.php
// Konfigurasi AI & Multi-Model Provider (Khusus Admin)

include '../includes/config.php';
include '../includes/gemini_config.php';

// Proteksi Keamanan: KHUSUS ROLE ADMIN
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
        $active_model        = sanitize_input($_POST['active_model'] ?? 'gemini-3.5-flash-lite');
        $gemini_key_primary  = trim($_POST['gemini_key_primary'] ?? '');
        $gemini_key_backup_1 = '';
        $gemini_key_backup_2 = '';
        $groq_api_key        = trim($_POST['groq_api_key'] ?? '');
        $deepseek_api_key    = trim($_POST['deepseek_api_key'] ?? '');
        $openai_api_key      = trim($_POST['openai_api_key'] ?? '');
        $auto_failover       = 0;
        $ai_tone             = sanitize_input($_POST['ai_tone'] ?? 'seimbang');
        $temperature         = floatval($_POST['temperature'] ?? 0.3);
        $max_tokens          = intval($_POST['max_tokens'] ?? 2000);

        $active_model_esc        = mysqli_real_escape_string($conn, $active_model);
        $gemini_key_primary_esc  = mysqli_real_escape_string($conn, $gemini_key_primary);
        $gemini_key_backup_1_esc = mysqli_real_escape_string($conn, $gemini_key_backup_1);
        $gemini_key_backup_2_esc = mysqli_real_escape_string($conn, $gemini_key_backup_2);
        $groq_api_key_esc        = mysqli_real_escape_string($conn, $groq_api_key);
        $deepseek_api_key_esc    = mysqli_real_escape_string($conn, $deepseek_api_key);
        $openai_api_key_esc      = mysqli_real_escape_string($conn, $openai_api_key);
        $ai_tone_esc             = mysqli_real_escape_string($conn, $ai_tone);

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
            $_SESSION['flash_message'] = '✅ Pengaturan AI berhasil disimpan!';
            catat_log($conn, $_SESSION['user_id'], $_SESSION['email'], $_SESSION['nama'], 'Update Konfigurasi AI', "Model: $active_model");
            header('Location: ' . $base_url . 'konfigurasi_ai');
            exit();
        } else {
            $error = '❌ Gagal menyimpan konfigurasi: ' . mysqli_error($conn);
        }
    }
}

// Flash message
if (isset($_SESSION['flash_message'])) {
    $message = $_SESSION['flash_message'];
    unset($_SESSION['flash_message']);
}

// Ambil data terbaru
$settings = get_ai_settings($conn);
$csrf_token = generate_csrf_token();

include '../includes/navbar.php';
?>

<style>
.test-result-box {
    margin-top: 8px;
    font-size: 13px;
    display: none;
    padding: 8px 12px;
    border-radius: 8px;
}
</style>

<!-- SIGNATURE DASH HERO HEADER (SEDERHANA & BERSIH) -->
<div class="dash-hero-header">
    <div class="dash-hero-inner">
        <div class="dash-hero-title">
            <h1><i class="fas fa-microchip"></i> Konfigurasi AI</h1>
            <p>Kelola pilihan model dan API Key untuk sistem AI Admin.</p>
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

    <form method="POST" action="" id="formAiConfig">
        <input type="hidden" name="csrf_token" id="formCsrfToken" value="<?= $csrf_token ?>">

        <!-- BAGIAN 1: PEMILIHAN MODEL AI AKTIF (RINGKAS & BERSIH) -->
        <div class="card-modern mb-4">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h5 class="fw-bold text-dark mb-0">
                    <i class="fas fa-robot text-success me-2"></i> Model AI Aktif
                </h5>
                <span class="badge bg-success-subtle text-success px-3 py-2 rounded-pill fw-bold border border-success" id="badgeActiveModel">
                    <?= htmlspecialchars($settings['active_model']) ?>
                </span>
            </div>

            <div class="row align-items-center">
                <div class="col-md-8">
                    <label class="form-label-modern small text-muted mb-1">Pilih salah satu dari 10 model yang tersedia:</label>
                    <select name="active_model" class="form-select form-control-modern fs-6" id="modelSelector" onchange="updateModelBadge(this)">
                        <optgroup label="── Google Gemini ──">
                            <option value="gemini-3.5-flash-lite" <?= $settings['active_model'] == 'gemini-3.5-flash-lite' ? 'selected' : '' ?>>Gemini 3.5 Flash Lite - Cepat dan Hemat Kuota</option>
                            <option value="gemini-3.5-flash" <?= $settings['active_model'] == 'gemini-3.5-flash' ? 'selected' : '' ?>>Gemini 3.5 Flash - Super Cerdas dan Analisis Detail</option>
                            <option value="gemini-2.5-flash" <?= $settings['active_model'] == 'gemini-2.5-flash' ? 'selected' : '' ?>>Gemini 2.5 Flash - Versi Stabil</option>
                            <option value="gemini-2.5-pro" <?= $settings['active_model'] == 'gemini-2.5-pro' ? 'selected' : '' ?>>Gemini 2.5 Pro - Penalaran Kompleks</option>
                        </optgroup>
                        <optgroup label="── Groq Cloud Meta Llama dan Mixtral ──">
                            <option value="llama-3.3-70b-versatile" <?= $settings['active_model'] == 'llama-3.3-70b-versatile' ? 'selected' : '' ?>>Meta Llama 3.3 70B - Open Source Flagship</option>
                            <option value="llama-3.1-8b-instant" <?= $settings['active_model'] == 'llama-3.1-8b-instant' ? 'selected' : '' ?>>Meta Llama 3.1 8B Instant - Respon Kilat</option>
                            <option value="mixtral-8x7b-32768" <?= $settings['active_model'] == 'mixtral-8x7b-32768' ? 'selected' : '' ?>>Mixtral 8x7B - Pakar Data dan Logika</option>
                        </optgroup>
                        <optgroup label="── DeepSeek ──">
                            <option value="deepseek-chat" <?= $settings['active_model'] == 'deepseek-chat' ? 'selected' : '' ?>>DeepSeek V3 - Setara GPT 4o</option>
                            <option value="deepseek-reasoner" <?= $settings['active_model'] == 'deepseek-reasoner' ? 'selected' : '' ?>>DeepSeek R1 - Penalaran Mendalam</option>
                        </optgroup>
                        <optgroup label="── OpenAI ──">
                            <option value="gpt-4o-mini" <?= $settings['active_model'] == 'gpt-4o-mini' ? 'selected' : '' ?>>GPT 4o Mini - OpenAI</option>
                        </optgroup>
                    </select>
                </div>
                <div class="col-md-4 mt-2 mt-md-0">
                    <p class="text-muted small mb-0 pt-md-3">
                        Model yang dipilih akan digunakan untuk melayani percakapan dan analisis data sistem.
                    </p>
                </div>
            </div>
        </div>

        <!-- BAGIAN 2: API KEY GOOGLE GEMINI -->
        <div class="card-modern mb-4">
            <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                <h5 class="fw-bold text-dark mb-0">
                    <i class="fab fa-google text-success me-2"></i> API Key Google Gemini
                </h5>
                <a href="https://aistudio.google.com/app/apikey" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-success rounded-pill fw-bold">
                    <i class="fas fa-external-link-alt me-1"></i> 🔑 + Buat API Key di Google AI Studio
                </a>
            </div>

            <!-- API KEY UTAMA -->
            <div class="mb-2">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <label class="form-label-modern mb-0 fw-bold">API Key Utama</label>
                    <button type="button" class="btn btn-sm btn-light border rounded-pill px-3" onclick="testSingleKey('gemini', 'gemini_key_primary', 'res_gemini_1')">
                        <i class="fas fa-bolt text-warning me-1"></i> Tes Koneksi
                    </button>
                </div>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="fas fa-key text-muted"></i></span>
                    <input type="password" name="gemini_key_primary" id="gemini_key_primary" class="form-control form-control-modern border-start-0" 
                           placeholder="Masukkan API Key Gemini Utama" 
                           value="<?= htmlspecialchars($settings['gemini_key_primary']) ?>" required>
                    <button class="btn btn-outline-secondary border" type="button" onclick="toggleVisibility('gemini_key_primary', this)">
                        <i class="far fa-eye"></i>
                    </button>
                </div>
                <div id="res_gemini_1" class="test-result-box"></div>
            </div>
        </div>

        <!-- BAGIAN 3: PROVIDER LAIN (GROQ / DEEPSEEK / OPENAI) -->
        <div class="card-modern mb-4">
            <h5 class="fw-bold text-dark mb-3">
                <i class="fas fa-network-wired text-primary me-2"></i> API Key Provider Lain
            </h5>

            <!-- GROQ -->
            <div class="mb-3">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <label class="form-label-modern mb-0">Groq API Key</label>
                    <div class="d-flex gap-2 align-items-center">
                        <a href="https://console.groq.com/keys" target="_blank" class="small text-decoration-none text-muted">
                            <i class="fas fa-external-link-alt"></i> Buat Key
                        </a>
                        <button type="button" class="btn btn-sm btn-light border rounded-pill px-3" onclick="testSingleKey('groq', 'groq_api_key', 'res_groq')">
                            <i class="fas fa-bolt text-warning me-1"></i> Tes
                        </button>
                    </div>
                </div>
                <div class="input-group">
                    <input type="password" name="groq_api_key" id="groq_api_key" class="form-control form-control-modern" 
                           placeholder="gsk_..." value="<?= htmlspecialchars($settings['groq_api_key']) ?>">
                    <button class="btn btn-outline-secondary border" type="button" onclick="toggleVisibility('groq_api_key', this)"><i class="far fa-eye"></i></button>
                </div>
                <div id="res_groq" class="test-result-box"></div>
            </div>

            <!-- DEEPSEEK -->
            <div class="mb-3">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <label class="form-label-modern mb-0">DeepSeek API Key</label>
                    <div class="d-flex gap-2 align-items-center">
                        <a href="https://platform.deepseek.com/api_keys" target="_blank" class="small text-decoration-none text-muted">
                            <i class="fas fa-external-link-alt"></i> Buat Key
                        </a>
                        <button type="button" class="btn btn-sm btn-light border rounded-pill px-3" onclick="testSingleKey('deepseek', 'deepseek_api_key', 'res_deepseek')">
                            <i class="fas fa-bolt text-warning me-1"></i> Tes
                        </button>
                    </div>
                </div>
                <div class="input-group">
                    <input type="password" name="deepseek_api_key" id="deepseek_api_key" class="form-control form-control-modern" 
                           placeholder="sk-..." value="<?= htmlspecialchars($settings['deepseek_api_key']) ?>">
                    <button class="btn btn-outline-secondary border" type="button" onclick="toggleVisibility('deepseek_api_key', this)"><i class="far fa-eye"></i></button>
                </div>
                <div id="res_deepseek" class="test-result-box"></div>
            </div>

            <!-- OPENAI -->
            <div class="mb-0">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <label class="form-label-modern mb-0">OpenAI API Key</label>
                    <div class="d-flex gap-2 align-items-center">
                        <a href="https://platform.openai.com/api-keys" target="_blank" class="small text-decoration-none text-muted">
                            <i class="fas fa-external-link-alt"></i> Buat Key
                        </a>
                        <button type="button" class="btn btn-sm btn-light border rounded-pill px-3" onclick="testSingleKey('openai', 'openai_api_key', 'res_openai')">
                            <i class="fas fa-bolt text-warning me-1"></i> Tes
                        </button>
                    </div>
                </div>
                <div class="input-group">
                    <input type="password" name="openai_api_key" id="openai_api_key" class="form-control form-control-modern" 
                           placeholder="sk-proj-..." value="<?= htmlspecialchars($settings['openai_api_key']) ?>">
                    <button class="btn btn-outline-secondary border" type="button" onclick="toggleVisibility('openai_api_key', this)"><i class="far fa-eye"></i></button>
                </div>
                <div id="res_openai" class="test-result-box"></div>
            </div>
        </div>

        <!-- BAGIAN 4: GAYA & KARAKTERISTIK RESPON -->
        <div class="card-modern mb-4">
            <h5 class="fw-bold text-dark mb-3">
                <i class="fas fa-sliders-h text-warning me-2"></i> Karakteristik Respon AI
            </h5>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label-modern fw-bold">Gaya Bahasa:</label>
                    <select name="ai_tone" class="form-select form-control-modern">
                        <option value="seimbang" <?= ($settings['ai_tone'] === 'seimbang') ? 'selected' : '' ?>>
                            🟢 Ramah dan Informatif
                        </option>
                        <option value="ringkas" <?= ($settings['ai_tone'] === 'ringkas') ? 'selected' : '' ?>>
                            ⚡ Praktis dan Ringkas
                        </option>
                        <option value="mendalam" <?= ($settings['ai_tone'] === 'mendalam') ? 'selected' : '' ?>>
                            📚 Edukatif dan Mendalam
                        </option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label-modern fw-bold">Kreativitas:</label>
                    <input type="number" step="0.1" min="0.0" max="1.0" name="temperature" class="form-control form-control-modern" 
                           value="<?= htmlspecialchars($settings['temperature']) ?>">
                </div>

                <div class="col-md-3">
                    <label class="form-label-modern fw-bold">Maksimal Token:</label>
                    <input type="number" step="100" min="500" max="4000" name="max_tokens" class="form-control form-control-modern" 
                           value="<?= htmlspecialchars($settings['max_tokens']) ?>">
                </div>
            </div>
        </div>

        <!-- TOMBOL SIMPAN -->
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

// Update Active Model Badge on select change
function updateModelBadge(selectEl) {
    document.getElementById('badgeActiveModel').innerText = selectEl.value;
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
        const activeModel = document.getElementById('modelSelector') ? document.getElementById('modelSelector').value : 'gemini-3.5-flash-lite';
        const formData = new FormData();
        formData.append('csrf_token', csrfToken);
        formData.append('provider', provider);
        formData.append('api_key', keyVal);
        formData.append('model', activeModel);

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
