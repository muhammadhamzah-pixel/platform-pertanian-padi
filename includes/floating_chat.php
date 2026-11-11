<?php
// includes/floating_chat.php
// Floating Chatbot Widget (Pojok Kanan Bawah) - Sahabat Tani AI

if (!isset($_SESSION['user_id'])) {
    return;
}

global $conn;
if (!$conn) {
    require_once __DIR__ . '/config.php';
}
if (!function_exists('generate_csrf_token')) {
    require_once __DIR__ . '/functions.php';
}

$fc_user_id = $_SESSION['user_id'];
$fc_nama = $_SESSION['nama'] ?? 'Petani';
$fc_role = $_SESSION['role'] ?? 'petani';
$fc_foto = $_SESSION['foto'] ?? 'defaults.png';

// Tentukan Persona & Batas Kuota Berdasarkan Role
if ($fc_role === 'admin') {
    $fc_limit_harian = 50;
    $fc_bot_icon = '⚡';
    $fc_bot_title = 'Admin Assistant AI';
    $fc_bot_subtitle = '';
    $fc_tooltip = '⚡ Mode Admin: Akses Database Penuh Aktif';
    $fc_welcome_desc = 'Halo, Administrator ' . htmlspecialchars($fc_nama) . '! Mode Akses Penuh aktif. Anda dapat menanyakan data seluruh tabel database (pengguna, seluruh lahan desa, log aktivitas, analisis keamanan).';
    $fc_chips = [
        ['icon' => '👥', 'text' => 'Rekap seluruh user: berapa admin, desa, dan petani aktif?'],
        ['icon' => '🌾', 'text' => 'Ringkasan total lahan dan produktivitas seluruh desa'],
        ['icon' => '🔒', 'text' => 'Bagaimana ringkasan log aktivitas & audit sistem terakhir?'],
        ['icon' => '📈', 'text' => 'Berikan analisis komprehensif perkembangan pertanian desa']
    ];
} elseif ($fc_role === 'perangkat_desa') {
    $fc_limit_harian = 25;
    $fc_bot_icon = '🏛️';
    $fc_bot_title = 'Asisten Desa AI';
    $fc_bot_subtitle = 'Rekapitulasi & Statistik Pertanian Desa';
    $fc_tooltip = '🏛️ Asisten Data Pertanian & Rekap Desa';
    $fc_welcome_desc = 'Halo, Bapak/Ibu Perangkat Desa ' . htmlspecialchars($fc_nama) . '! Saya siap membantu rekapitulasi data petani warga, statistik luas sawah desa, dan monitoring panen wilayah.';
    $fc_chips = [
        ['icon' => '📊', 'text' => 'Berapa total petani & total luas lahan di desa kita?'],
        ['icon' => '🌾', 'text' => 'Berapa banyak lahan yang siap panen di desa saat ini?'],
        ['icon' => '📋', 'text' => 'Ada berapa akun petani yang masih pending verifikasi?'],
        ['icon' => '🏆', 'text' => 'Apa varietas padi yang paling banyak ditanam warga?']
    ];
} else {
    // Petani
    $fc_limit_harian = 10;
    $fc_bot_icon = '🌾';
    $fc_bot_title = 'Sahabat Tani AI';
    $fc_bot_subtitle = 'Asisten Budidaya Padi Anda';
    $fc_tooltip = '🌾 Ada pertanyaan seputar sawah? Tanya AI yuk!';
    $fc_welcome_desc = 'Halo, ' . htmlspecialchars($fc_nama) . '! 👋 Saya adalah asisten AI resmi budidaya padi. Saya terhubung dengan data lahan Anda untuk memantau jadwal panen, varietas bibit, rekomendasi pemupukan, dan diagnosis penyakit.';
    $fc_chips = [
        ['icon' => '🌾', 'text' => 'Kapan perkiraan jadwal panen lahan saya?'],
        ['icon' => '📊', 'text' => 'Berapa total luas lahan & jenis padi saya?'],
        ['icon' => '🍂', 'text' => 'Kenapa daun padi menguning & cara mengatasinya?'],
        ['icon' => '💧', 'text' => 'Kapan waktu pemupukan susulan terbaik?']
    ];
}

$fc_query = "SELECT COUNT(*) AS total FROM chat_ai WHERE user_id = '$fc_user_id' AND DATE(created_at) = CURDATE()";
$fc_result = mysqli_query($conn, $fc_query);
$fc_total_hari_ini = intval(mysqli_fetch_assoc($fc_result)['total'] ?? 0);
$fc_sisa_limit = max(0, $fc_limit_harian - $fc_total_hari_ini);
$fc_csrf = generate_csrf_token();
?>

<!-- STYLE FLOATING CHATBOT WIDGET -->
<style>
    /* ===== TOMBOL FLOATING (POJOK KANAN BAWAH) ===== */
    .floating-chat-launcher {
        position: fixed;
        bottom: 25px;
        right: 25px;
        z-index: 99999;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .floating-chat-btn {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: linear-gradient(135deg, #064e3b, #059669);
        color: white;
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 26px;
        box-shadow: 0 8px 24px rgba(5, 150, 105, 0.4), 0 2px 6px rgba(0,0,0,0.1);
        transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        position: relative;
        outline: none;
    }

    .floating-chat-btn:hover {
        transform: scale(1.08) translateY(-2px);
        box-shadow: 0 12px 28px rgba(5, 150, 105, 0.5);
    }

    .floating-chat-btn:active {
        transform: scale(0.95);
    }

    .floating-chat-btn .badge-online {
        position: absolute;
        bottom: 2px;
        right: 2px;
        width: 14px;
        height: 14px;
        background: #10b981;
        border: 2px solid #ffffff;
        border-radius: 50%;
        box-shadow: 0 0 8px rgba(16, 185, 129, 0.8);
    }

    /* Tooltip Banner Melayang */
    .floating-chat-tooltip {
        background: #ffffff;
        color: #064e3b;
        padding: 8px 14px;
        border-radius: 20px;
        font-size: 13px;
        font-weight: 700;
        box-shadow: 0 4px 15px rgba(0,0,0,0.12);
        white-space: nowrap;
        pointer-events: none;
        opacity: 0.95;
        display: flex;
        align-items: center;
        gap: 6px;
        border: 1px solid rgba(5, 150, 105, 0.2);
        animation: tooltipFloat 3s ease-in-out infinite alternate;
    }

    @keyframes tooltipFloat {
        0% { transform: translateY(0); }
        100% { transform: translateY(-4px); }
    }

    /* ===== JENDELA CHAT POPUP (KANAN BAWAH) ===== */
    .floating-chat-window {
        position: fixed;
        bottom: 98px;
        right: 25px;
        width: 390px;
        max-width: calc(100vw - 30px);
        height: 560px;
        max-height: calc(100vh - 120px);
        background: #f8fafc;
        border-radius: 24px;
        box-shadow: 0 20px 50px rgba(15, 23, 42, 0.2), 0 5px 15px rgba(5, 150, 105, 0.1);
        display: flex;
        flex-direction: column;
        overflow: hidden;
        z-index: 99999;
        border: 1px solid #e2e8f0;
        transition: all 0.35s cubic-bezier(0.16, 1, 0.3, 1);
        transform-origin: bottom right;
        opacity: 0;
        transform: scale(0.85) translateY(20px);
        pointer-events: none;
        visibility: hidden;
    }

    .floating-chat-window.active {
        opacity: 1;
        transform: scale(1) translateY(0);
        pointer-events: auto;
        visibility: visible;
    }

    /* Header Window */
    .fc-header {
        background: linear-gradient(135deg, #064e3b 0%, #059669 100%);
        color: white;
        padding: 14px 18px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        flex-shrink: 0;
    }

    .fc-header-left {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .fc-avatar {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        background: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
        border: 2px solid rgba(255,255,255,0.8);
        box-shadow: 0 2px 8px rgba(0,0,0,0.2);
    }

    .fc-header-info h4 {
        margin: 0;
        font-size: 15px;
        font-weight: 700;
        letter-spacing: 0.2px;
    }

    .fc-header-info p {
        margin: 2px 0 0;
        font-size: 11px;
        opacity: 0.9;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .fc-status-dot {
        width: 8px;
        height: 8px;
        background: #00e676;
        border-radius: 50%;
        display: inline-block;
        box-shadow: 0 0 6px #00e676;
    }

    .fc-header-actions {
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .fc-limit-badge {
        background: rgba(255,255,255,0.2);
        padding: 4px 10px;
        border-radius: 14px;
        font-size: 11px;
        font-weight: 600;
        white-space: nowrap;
    }

    .fc-limit-badge.danger {
        background: #dc3545;
        color: white;
    }

    .fc-btn-icon {
        background: transparent;
        border: none;
        color: white;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        font-size: 16px;
        transition: 0.2s;
        opacity: 0.85;
    }

    .fc-btn-icon:hover {
        background: rgba(255,255,255,0.2);
        opacity: 1;
    }

    /* Body Window */
    .fc-body {
        flex: 1;
        overflow-y: auto;
        padding: 16px;
        background: #eef3ee;
        display: flex;
        flex-direction: column;
        gap: 12px;
        scroll-behavior: smooth;
    }

    /* Welcome Card */
    .fc-welcome {
        background: #ffffff;
        border-radius: 18px;
        padding: 14px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        border-left: 4px solid #2e7d32;
    }

    .fc-welcome h5 {
        margin: 0 0 6px;
        color: #1b5e20;
        font-size: 13.5px;
        font-weight: 700;
    }

    .fc-welcome p {
        margin: 0 0 10px;
        font-size: 12px;
        color: #555;
        line-height: 1.45;
    }

    .fc-chips-title {
        font-size: 11px;
        font-weight: 700;
        color: #2e7d32;
        margin-bottom: 6px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .fc-chips-container {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .fc-chip {
        background: #f0f7f0;
        border: 1px solid #c8e6c9;
        color: #1b5e20;
        padding: 6px 12px;
        border-radius: 12px;
        font-size: 11.5px;
        text-align: left;
        cursor: pointer;
        transition: all 0.2s;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .fc-chip:hover {
        background: #2e7d32;
        color: #ffffff;
        border-color: #2e7d32;
        transform: translateX(4px);
    }

    /* Chat Bubbles */
    .fc-msg-user {
        display: flex;
        justify-content: flex-end;
        margin-bottom: 4px;
    }

    .fc-msg-user .fc-bubble {
        background: linear-gradient(135deg, #1b5e20, #2e7d32);
        color: white;
        padding: 10px 14px;
        border-radius: 16px 16px 2px 16px;
        max-width: 82%;
        font-size: 12.5px;
        line-height: 1.45;
        word-wrap: break-word;
        box-shadow: 0 3px 8px rgba(27, 94, 32, 0.2);
    }

    .fc-msg-ai {
        display: flex;
        justify-content: flex-start;
        align-items: flex-start;
        gap: 8px;
        margin-bottom: 4px;
    }

    .fc-msg-ai .fc-ai-avatar {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        background: #2e7d32;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        flex-shrink: 0;
        margin-top: 2px;
    }

    .fc-msg-ai .fc-bubble {
        background: #ffffff;
        color: #2b2b2b;
        padding: 10px 14px;
        border-radius: 16px 16px 16px 2px;
        max-width: 84%;
        font-size: 12.5px;
        line-height: 1.5;
        word-wrap: break-word;
        box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        border: 1px solid rgba(0,0,0,0.04);
    }

    .fc-bubble-img {
        width: 100%;
        max-width: 180px;
        border-radius: 10px;
        margin-bottom: 6px;
        display: block;
        cursor: pointer;
        transition: 0.2s;
    }

    .fc-bubble-img:hover {
        opacity: 0.95;
    }

    .fc-msg-time {
        font-size: 10px;
        opacity: 0.65;
        margin-top: 4px;
        text-align: right;
    }

    .fc-msg-ai .fc-msg-time {
        text-align: left;
    }

    /* Typing Indicator */
    .fc-typing {
        display: none;
        align-items: center;
        gap: 8px;
        margin-bottom: 4px;
    }

    .fc-typing.active {
        display: flex;
    }

    .fc-typing-bubble {
        background: #ffffff;
        padding: 10px 14px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        gap: 5px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    }

    .fc-dot {
        width: 7px;
        height: 7px;
        background: #2e7d32;
        border-radius: 50%;
        animation: fcBounce 1.4s infinite ease-in-out;
    }

    .fc-dot:nth-child(2) { animation-delay: 0.2s; }
    .fc-dot:nth-child(3) { animation-delay: 0.4s; }

    @keyframes fcBounce {
        0%, 80%, 100% { transform: scale(0.6); opacity: 0.4; }
        40% { transform: scale(1.1); opacity: 1; }
    }

    /* Footer / Input Area */
    .fc-footer {
        background: #ffffff;
        padding: 10px 14px;
        border-top: 1px solid #e0e8e0;
        display: flex;
        flex-direction: column;
        gap: 8px;
        flex-shrink: 0;
    }

    .fc-preview-area {
        display: none;
        align-items: center;
        gap: 10px;
        padding: 6px 10px;
        background: #f0f7f0;
        border-radius: 10px;
        border: 1px dashed #2e7d32;
    }

    .fc-preview-area.active {
        display: flex;
    }

    .fc-preview-thumb {
        width: 38px;
        height: 38px;
        border-radius: 6px;
        object-fit: cover;
    }

    .fc-preview-name {
        flex: 1;
        font-size: 11px;
        color: #333;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .fc-preview-remove {
        background: #ff5252;
        color: white;
        border: none;
        width: 22px;
        height: 22px;
        border-radius: 50%;
        font-size: 11px;
        cursor: pointer;
        font-weight: bold;
    }

    .fc-input-row {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .fc-btn-attach {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        background: #f0f4f0;
        border: 1px solid #d0ded0;
        color: #1b5e20;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        transition: 0.2s;
        flex-shrink: 0;
    }

    .fc-btn-attach:hover {
        background: #e0eee0;
        transform: scale(1.05);
    }

    .fc-input-text {
        flex: 1;
        padding: 10px 14px;
        background: #f4f8f4;
        border: 1px solid #d4e2d4;
        border-radius: 20px;
        font-size: 12.5px;
        outline: none;
        transition: border-color 0.2s;
    }

    .fc-input-text:focus {
        border-color: #2e7d32;
        background: #ffffff;
    }

    .fc-btn-send {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        background: #1b5e20;
        color: white;
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 15px;
        transition: all 0.2s;
        flex-shrink: 0;
    }

    .fc-btn-send:hover {
        background: #2e7d32;
        transform: scale(1.05);
    }

    .fc-btn-send:disabled, .fc-btn-attach:disabled {
        opacity: 0.4;
        cursor: not-allowed;
        transform: none;
    }

    /* RESPONSIVE LAYOUT */
    @media (max-width: 600px) {
        .floating-chat-launcher {
            bottom: 18px;
            right: 18px;
        }

        .floating-chat-btn {
            width: 56px;
            height: 56px;
            font-size: 24px;
        }

        .floating-chat-tooltip {
            display: none; /* Sembunyikan tooltip di layar kecil agar tidak menutupi tombol */
        }

        .floating-chat-window {
            bottom: 84px;
            right: 12px;
            left: 12px;
            width: auto;
            max-width: none;
            height: calc(100vh - 100px);
            border-radius: 20px;
        }
    }
</style>

<!-- ELEMEN DOM FLOATING CHATBOT WIDGET -->
<div id="floatingChatWrapper">
    <!-- LAUNCHER BUTTON -->
    <div class="floating-chat-launcher">
        <div class="floating-chat-tooltip" id="fcLauncherTooltip">
            <?= htmlspecialchars($fc_tooltip) ?>
        </div>
        <button class="floating-chat-btn" id="fcLauncherBtn" onclick="toggleFloatingChat()" title="<?= htmlspecialchars($fc_bot_title) ?>">
            <?= $fc_bot_icon ?>
            <span class="badge-online"></span>
        </button>
    </div>

    <!-- WINDOW POPUP -->
    <div class="floating-chat-window" id="fcWindow">
        <!-- HEADER -->
        <div class="fc-header">
            <div class="fc-header-left">
                <div class="fc-avatar"><?= $fc_bot_icon ?></div>
                <div class="fc-header-info">
                    <h4><?= htmlspecialchars($fc_bot_title) ?></h4>
                    <p><span class="fc-status-dot"></span> Online • <?= htmlspecialchars($fc_bot_subtitle) ?></p>
                </div>
            </div>
            <div class="fc-header-actions">
                <span class="fc-limit-badge <?= $fc_sisa_limit <= 2 ? 'danger' : '' ?>" id="fcLimitBadge">
                    Sisa: <?= $fc_sisa_limit ?>/<?= $fc_limit_harian ?>
                </span>
                <button class="fc-btn-icon" onclick="resetFloatingChat()" title="Hapus Tampilan Chat">🔄</button>
                <button class="fc-btn-icon" onclick="closeFloatingChat()" title="Tutup Chat">✕</button>
            </div>
        </div>

        <!-- BODY MESSAGES -->
        <div class="fc-body" id="fcMessages">
            <!-- WELCOME CARD -->
            <div class="fc-welcome" id="fcWelcomeCard">
                <h5>Halo, <?= htmlspecialchars($fc_nama) ?>! 👋</h5>
                <p><?= $fc_welcome_desc ?></p>
                <div class="fc-chips-title">💡 Pertanyaan Rekomendasi (Klik untuk Tanya Langsung):</div>
                <div class="fc-chips-container">
                    <?php foreach ($fc_chips as $chip): ?>
                        <button type="button" class="fc-chip" onclick="fcAskPrompt('<?= addslashes($chip['text']) ?>')">
                            <?= $chip['icon'] ?> <?= htmlspecialchars($chip['text']) ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- CHAT ITEMS INSERTED DYNAMICALLY HERE -->

            <!-- TYPING INDICATOR -->
            <div class="fc-typing" id="fcTypingIndicator">
                <div class="fc-msg-ai">
                    <div class="fc-ai-avatar">🤖</div>
                    <div class="fc-typing-bubble">
                        <div class="fc-dot"></div>
                        <div class="fc-dot"></div>
                        <div class="fc-dot"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- FOOTER FORM -->
        <form class="fc-footer" id="fcForm" onsubmit="fcHandleSubmit(event)">
            <input type="hidden" name="csrf_token" id="fcCsrfToken" value="<?= $fc_csrf ?>">

            <!-- PREVIEW GAMBAR -->
            <div class="fc-preview-area" id="fcPreviewArea">
                <img id="fcPreviewImg" class="fc-preview-thumb" src="" alt="Thumbnail">
                <span id="fcPreviewName" class="fc-preview-name">gambar.jpg</span>
                <button type="button" class="fc-preview-remove" onclick="fcCancelImage()">✕</button>
            </div>

            <div class="fc-input-row">
                <button type="button" class="fc-btn-attach" id="fcBtnAttach" onclick="document.getElementById('fcInputFile').click()" title="Unggah Foto Tanaman/Hama">
                    📎
                </button>
                <input type="file" id="fcInputFile" accept="image/jpeg,image/png,image/webp" style="display: none;" onchange="fcHandleImageSelect(this)">
                
                <input type="text" class="fc-input-text" id="fcInputText" placeholder="Tanya seputar sawah / tanaman padi..." autocomplete="off" <?= $fc_sisa_limit <= 0 ? 'disabled' : '' ?>>
                
                <button type="submit" class="fc-btn-send" id="fcBtnSend" title="Kirim Pesan" <?= $fc_sisa_limit <= 0 ? 'disabled' : '' ?>>
                    ➤
                </button>
            </div>
        </form>
    </div>
</div>

<!-- SCRIPT LOGIKA FLOATING CHATBOT WIDGET -->
<script>
(function() {
    let sisaLimit = <?= $fc_sisa_limit ?>;
    const limitHarian = <?= $fc_limit_harian ?>;
    const STORAGE_KEY = 'fc_chat_history_<?= $fc_user_id ?>';

    // Toggle Chat Window
    window.toggleFloatingChat = function() {
        const win = document.getElementById('fcWindow');
        const tooltip = document.getElementById('fcLauncherTooltip');
        if (win.classList.contains('active')) {
            win.classList.remove('active');
        } else {
            win.classList.add('active');
            if (tooltip) tooltip.style.display = 'none';
            document.getElementById('fcInputText').focus();
            fcScrollToBottom();
        }
    };

    window.openFloatingChat = function() {
        const win = document.getElementById('fcWindow');
        const tooltip = document.getElementById('fcLauncherTooltip');
        win.classList.add('active');
        if (tooltip) tooltip.style.display = 'none';
        document.getElementById('fcInputText').focus();
        fcScrollToBottom();
    };

    window.closeFloatingChat = function() {
        document.getElementById('fcWindow').classList.remove('active');
    };

    window.resetFloatingChat = function() {
        if (confirm('Bersihkan percakapan di layar saat ini?')) {
            sessionStorage.removeItem(STORAGE_KEY);
            const messagesContainer = document.getElementById('fcMessages');
            const welcomeCard = document.getElementById('fcWelcomeCard');
            const typing = document.getElementById('fcTypingIndicator');
            
            messagesContainer.innerHTML = '';
            if (welcomeCard) messagesContainer.appendChild(welcomeCard);
            messagesContainer.appendChild(typing);
        }
    };

    window.fcAskPrompt = function(promptText) {
        const input = document.getElementById('fcInputText');
        input.value = promptText;
        document.getElementById('fcForm').dispatchEvent(new Event('submit'));
    };

    window.fcHandleImageSelect = function(fileInput) {
        const file = fileInput.files[0];
        if (!file) return;

        if (file.size > 5 * 1024 * 1024) {
            alert('Ukuran gambar terlalu besar! Maksimal 5MB.');
            fileInput.value = '';
            return;
        }

        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('fcPreviewImg').src = e.target.result;
            document.getElementById('fcPreviewName').textContent = file.name;
            document.getElementById('fcPreviewArea').classList.add('active');
        };
        reader.readAsDataURL(file);
    };

    window.fcCancelImage = function() {
        document.getElementById('fcInputFile').value = '';
        document.getElementById('fcPreviewArea').classList.remove('active');
        document.getElementById('fcPreviewImg').src = '';
    };

    function fcScrollToBottom() {
        const box = document.getElementById('fcMessages');
        box.scrollTop = box.scrollHeight;
    }

    function fcEscapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function fcGetTimeStr() {
        const now = new Date();
        const h = String(now.getHours()).padStart(2, '0');
        const m = String(now.getMinutes()).padStart(2, '0');
        return h + ':' + m;
    }

    function fcSaveHistory() {
        const container = document.getElementById('fcMessages');
        const items = container.querySelectorAll('.fc-msg-item');
        const historyData = [];
        items.forEach(el => {
            historyData.push(el.outerHTML);
        });
        sessionStorage.setItem(STORAGE_KEY, JSON.stringify(historyData));
    }

    function fcRestoreHistory() {
        const raw = sessionStorage.getItem(STORAGE_KEY);
        if (!raw) return;
        try {
            const list = JSON.parse(raw);
            const typing = document.getElementById('fcTypingIndicator');
            list.forEach(html => {
                const temp = document.createElement('div');
                temp.innerHTML = html;
                typing.parentNode.insertBefore(temp.firstElementChild, typing);
            });
        } catch (e) {
            console.error('Failed restoring chat history:', e);
        }
    }

    // Submit handler
    window.fcHandleSubmit = async function(event) {
        event.preventDefault();

        const inputText = document.getElementById('fcInputText');
        const inputFile = document.getElementById('fcInputFile');
        const btnSend = document.getElementById('fcBtnSend');
        const btnAttach = document.getElementById('fcBtnAttach');
        const csrfInput = document.getElementById('fcCsrfToken');
        const typing = document.getElementById('fcTypingIndicator');
        const messages = document.getElementById('fcMessages');

        const pertanyaan = inputText.value.trim();
        if (!pertanyaan) return;

        if (sisaLimit <= 0) {
            alert('Kuota tanya AI hari ini sudah habis. Silakan kembali besok!');
            return;
        }

        // Disable input
        btnSend.disabled = true;
        btnAttach.disabled = true;
        inputText.disabled = true;

        // Render Bubble User
        let imgHtml = '';
        if (inputFile.files[0]) {
            const previewSrc = document.getElementById('fcPreviewImg').src;
            imgHtml = `<img src="${previewSrc}" alt="Foto Lahan" class="fc-bubble-img" onclick="window.open(this.src, '_blank')">`;
        }

        const userMsgDiv = document.createElement('div');
        userMsgDiv.className = 'fc-msg-user fc-msg-item';
        userMsgDiv.innerHTML = `
            <div class="fc-bubble">
                ${imgHtml}
                <div>${fcEscapeHtml(pertanyaan)}</div>
                <div class="fc-msg-time">${fcGetTimeStr()}</div>
            </div>
        `;
        typing.parentNode.insertBefore(userMsgDiv, typing);

        // Reset Form Input
        const formData = new FormData();
        formData.append('pertanyaan', pertanyaan);
        formData.append('csrf_token', csrfInput.value);
        if (inputFile.files[0]) {
            formData.append('gambar', inputFile.files[0]);
        }

        inputText.value = '';
        fcCancelImage();
        typing.classList.add('active');
        fcScrollToBottom();
        fcSaveHistory();

        try {
            const response = await fetch('../pages/process_ai.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();
            typing.classList.remove('active');

            if (result.success) {
                // Update CSRF & Limit
                if (result.new_csrf_token) {
                    csrfInput.value = result.new_csrf_token;
                }
                if (typeof result.sisa_limit !== 'undefined') {
                    sisaLimit = result.sisa_limit;
                    const badge = document.getElementById('fcLimitBadge');
                    badge.textContent = `Sisa: ${sisaLimit}/${limitHarian}`;
                    if (sisaLimit <= 2) {
                        badge.classList.add('danger');
                    }
                }

                // Render Bubble AI
                const formattedJawaban = fcEscapeHtml(result.jawaban).replace(/\n/g, '<br>');
                const aiMsgDiv = document.createElement('div');
                aiMsgDiv.className = 'fc-msg-ai fc-msg-item';
                aiMsgDiv.innerHTML = `
                    <div class="fc-ai-avatar">🤖</div>
                    <div class="fc-bubble">
                        <div>${formattedJawaban}</div>
                        <div class="fc-msg-time">${fcGetTimeStr()}</div>
                    </div>
                `;
                typing.parentNode.insertBefore(aiMsgDiv, typing);
            } else {
                // Error response
                const errMsgDiv = document.createElement('div');
                errMsgDiv.className = 'fc-msg-ai fc-msg-item';
                errMsgDiv.innerHTML = `
                    <div class="fc-ai-avatar" style="background:#dc3545;">⚠️</div>
                    <div class="fc-bubble" style="background:#fff3f3; color:#c62828; border-color:#ffcdd2;">
                        <div>${fcEscapeHtml(result.error || 'Terjadi kesalahan sistem.')}</div>
                        <div class="fc-msg-time">${fcGetTimeStr()}</div>
                    </div>
                `;
                typing.parentNode.insertBefore(errMsgDiv, typing);
            }
        } catch (error) {
            console.error('Error sending chat:', error);
            typing.classList.remove('active');

            const errMsgDiv = document.createElement('div');
            errMsgDiv.className = 'fc-msg-ai fc-msg-item';
            errMsgDiv.innerHTML = `
                <div class="fc-ai-avatar" style="background:#dc3545;">⚠️</div>
                <div class="fc-bubble" style="background:#fff3f3; color:#c62828;">
                    <div>Koneksi terputus. Pastikan server web Anda aktif lalu coba lagi.</div>
                    <div class="fc-msg-time">${fcGetTimeStr()}</div>
                </div>
            `;
            typing.parentNode.insertBefore(errMsgDiv, typing);
        } finally {
            if (sisaLimit > 0) {
                btnSend.disabled = false;
                btnAttach.disabled = false;
                inputText.disabled = false;
                inputText.focus();
            } else {
                inputText.placeholder = 'Kuota tanya AI hari ini telah habis';
            }
            fcScrollToBottom();
            fcSaveHistory();
        }
    };

    // Auto-buka chat jika ada parameter ?open_chat=1 di URL
    document.addEventListener('DOMContentLoaded', function() {
        fcRestoreHistory();
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('open_chat') === '1' || window.location.hash === '#open_chat') {
            setTimeout(openFloatingChat, 300);
        }
    });
})();
</script>
