<?php
include '../includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Menu Chat Bot kini telah dipisah dan dipindahkan ke pojok kanan bawah (Floating Chat Widget)
$role = $_SESSION['role'] ?? 'petani';
if ($role == 'petani') {
    header('Location: dashboard_petani.php?open_chat=1');
} elseif ($role == 'perangkat_desa') {
    header('Location: dashboard_desa.php?open_chat=1');
} else {
    header('Location: dashboard_admin.php?open_chat=1');
}
exit();
?>

<style>
    .neumorphism-chat-container {
        max-width: 800px;
        margin: 0 auto;
        background: #f0f4f0;
        border-radius: 30px;
        overflow: hidden;
        box-shadow: 10px 10px 20px #d1d9d1, -10px -10px 20px #ffffff;
    }
    .chat-header {
        background: #1b5e20;
        color: white;
        padding: 15px 20px;
        display: flex;
        align-items: center;
        gap: 12px;
        justify-content: space-between;
    }
    .chat-header-left { display: flex; align-items: center; gap: 12px; }
    .chat-header img {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid white;
    }
    .chat-header strong { font-size: 1.1rem; }
    .chat-header p { margin: 0; font-size: 0.8rem; opacity: 0.8; }
    .limit-badge {
        background: rgba(255,255,255,0.15);
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    .limit-badge.warning { background: #ff9800; }
    .limit-badge.danger { background: #dc3545; }
    .chat-body {
        height: 400px;
        overflow-y: auto;
        padding: 20px;
        background: #e8eee8;
    }
    .chat-body .chat-item-user {
        display: flex;
        justify-content: flex-end;
        margin-bottom: 15px;
    }
    .chat-body .chat-item-user .bubble {
        background: #1b5e20;
        color: white;
        padding: 10px 15px;
        border-radius: 15px 15px 0 15px;
        max-width: 70%;
        word-wrap: break-word;
        box-shadow: 4px 4px 8px #c8d0c8, -2px -2px 6px #ffffff;
    }
    .chat-body .chat-item-user .bubble .time {
        font-size: 0.65rem;
        opacity: 0.7;
        text-align: right;
        margin-top: 5px;
    }
    .chat-body .chat-item-user .bubble .gambar-chat {
        width: 100%;
        max-width: 250px;
        border-radius: 10px;
        margin-bottom: 8px;
        display: block;
        cursor: pointer;
        transition: 0.3s;
    }
    .chat-body .chat-item-user .bubble .gambar-chat:hover { transform: scale(1.02); }
    .chat-body .chat-item-user img.avatar {
        width: 35px;
        height: 35px;
        border-radius: 50%;
        object-fit: cover;
        margin-left: 10px;
        border: 2px solid #1b5e20;
    }
    .chat-body .chat-item-ai {
        display: flex;
        justify-content: flex-start;
        margin-bottom: 15px;
    }
    .chat-body .chat-item-ai .bubble {
        background: white;
        color: #333;
        padding: 10px 15px;
        border-radius: 15px 15px 15px 0;
        max-width: 70%;
        word-wrap: break-word;
        box-shadow: 4px 4px 8px #c8d0c8, -2px -2px 6px #ffffff;
    }
    .chat-body .chat-item-ai .bubble .time {
        font-size: 0.65rem;
        opacity: 0.5;
        margin-top: 5px;
    }
    .chat-body .chat-item-ai img.avatar {
        width: 35px;
        height: 35px;
        border-radius: 50%;
        object-fit: cover;
        margin-right: 10px;
        border: 2px solid #ccc;
    }
    .chat-empty {
        text-align: center;
        padding: 50px 20px;
        color: #999;
    }
    .chat-empty p { font-size: 3rem; margin: 0; }
    .chat-empty h5 { margin: 10px 0 5px; font-size: 1.1rem; }
    .chat-empty small { font-size: 0.9rem; }
    .chat-footer {
        display: flex;
        flex-direction: column;
        padding: 10px 15px;
        background: #f0f4f0;
        border-top: 1px solid #dce4dc;
        gap: 10px;
    }
    .preview-area {
        display: none;
        align-items: center;
        gap: 10px;
        padding: 8px 12px;
        background: #e8eee8;
        border-radius: 12px;
        box-shadow: inset 4px 4px 8px #c8d0c8, inset -4px -4px 8px #ffffff;
    }
    .preview-area.active { display: flex; }
    .preview-area img {
        width: 50px;
        height: 50px;
        border-radius: 8px;
        object-fit: cover;
        border: 2px solid #1b5e20;
    }
    .preview-area span { flex: 1; font-size: 0.8rem; color: #555; }
    .preview-area button {
        background: #dc3545;
        color: white;
        border: none;
        border-radius: 50%;
        width: 25px;
        height: 25px;
        font-size: 14px;
        cursor: pointer;
        font-weight: 700;
    }
    .input-row { display: flex; gap: 10px; align-items: center; }
    .btn-upload {
        background: #e8eee8;
        border: none;
        border-radius: 50%;
        width: 45px;
        height: 45px;
        font-size: 20px;
        cursor: pointer;
        box-shadow: 6px 6px 12px #c8d0c8, -6px -6px 12px #ffffff;
        transition: all 0.3s ease;
        color: #1b5e20;
        flex-shrink: 0;
    }
    .btn-upload:hover {
        transform: scale(1.05);
        box-shadow: 8px 8px 16px #b8c0b8, -8px -8px 16px #ffffff;
    }
    .chat-footer input[type="text"] {
        flex: 1;
        padding: 10px 18px;
        border: none;
        border-radius: 50px;
        font-size: 14px;
        background: #e8eee8;
        box-shadow: inset 4px 4px 8px #c8d0c8, inset -4px -4px 8px #ffffff;
        outline: none;
    }
    .chat-footer input[type="text"]:focus {
        box-shadow: inset 6px 6px 12px #b8c0b8, inset -6px -6px 12px #ffffff;
    }
    .chat-footer input[type="text"]:disabled { opacity: 0.5; cursor: not-allowed; }
    .btn-send {
        background: #e8eee8;
        border: none;
        border-radius: 50%;
        width: 45px;
        height: 45px;
        font-size: 20px;
        cursor: pointer;
        box-shadow: 6px 6px 12px #c8d0c8, -6px -6px 12px #ffffff;
        transition: all 0.3s ease;
        color: #1b5e20;
        flex-shrink: 0;
    }
    .btn-send:hover {
        transform: scale(1.05);
        box-shadow: 8px 8px 16px #b8c0b8, -8px -8px 16px #ffffff;
    }
    .btn-send:disabled, .btn-upload:disabled {
        opacity: 0.5;
        cursor: not-allowed;
        transform: none;
    }
    .typing-animation { display: flex; align-items: center; gap: 4px; padding: 5px 0; }
    .typing-animation .dot {
        width: 8px;
        height: 8px;
        background: #333;
        border-radius: 50%;
        animation: typing 1.4s infinite;
    }
    .typing-animation .dot:nth-child(2) { animation-delay: 0.2s; }
    .typing-animation .dot:nth-child(3) { animation-delay: 0.4s; }
    @keyframes typing {
        0%, 60%, 100% { transform: translateY(0); opacity: 0.4; }
        30% { transform: translateY(-8px); opacity: 1; }
    }
    .error-bubble {
        background: #f8d7da;
        color: #721c24;
        padding: 15px 18px;
        border-radius: 12px;
        margin-bottom: 15px;
        text-align: center;
        line-height: 1.5;
    }
</style>

<div class="neumorphism-chat-container">
    <div class="chat-header">
        <div class="chat-header-left">
            <img src="../uploads/foto/defaults.png" alt="AI">
            <div>
                <strong>🤖 <?= t('ai_title') ?></strong>
                <p><?= t('ai_online') ?></p>
            </div>
        </div>
        <?php 
        $badge_class = 'limit-badge';
        if ($sisa_limit <= 1) $badge_class .= ' danger';
        elseif ($sisa_limit <= 2) $badge_class .= ' warning';
        ?>
        <div class="<?= $badge_class ?>" id="limitBadge">
            <?= $sisa_limit > 0 ? t('ai_remaining') . ": $sisa_limit/$limit_harian" : t('ai_limit_habis') ?>
        </div>
    </div>

    <div class="chat-body" id="chatBox">
        <?php if (count($rows) > 0): ?>
            <?php foreach ($rows as $row): ?>
                <div class="chat-item-user">
                    <div class="bubble">
                        <?php if (!empty($row['gambar_chat'])): ?>
                            <img src="../uploads/chat_ai/<?= htmlspecialchars($row['gambar_chat']) ?>" 
                                 alt="Gambar" 
                                 class="gambar-chat"
                                 onclick="window.open(this.src, '_blank')">
                        <?php endif; ?>
                        <?= htmlspecialchars($row['pertanyaan']) ?>
                        <div class="time"><?= date('H:i', strtotime($row['created_at'])) ?></div>
                    </div>
                    <img src="../uploads/foto/<?= htmlspecialchars($foto_user) ?>" alt="User" class="avatar">
                </div>

                <div class="chat-item-ai">
                    <img src="../uploads/foto/defaults.png" alt="AI" class="avatar">
                    <div class="bubble">
                        <?= nl2br(htmlspecialchars($row['jawaban'])) ?>
                        <div class="time"><?= date('H:i', strtotime($row['created_at'])) ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="chat-empty" id="chatEmpty">
                <p>🤖</p>
                <h5><?= t('ai_empty') ?></h5>
                <small><?= t('ai_empty_desc') ?></small>
            </div>
        <?php endif; ?>
    </div>

    <form id="chatForm" class="chat-footer" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
        
        <div class="preview-area" id="previewArea">
            <img id="previewImg" src="" alt="Preview">
            <span id="previewName">gambar.jpg</span>
            <button type="button" onclick="cancelGambar()">✕</button>
        </div>
        
        <div class="input-row">
            <button type="button" class="btn-upload" id="btnUpload" onclick="document.getElementById('inputGambar').click()" <?= $sisa_limit <= 0 ? 'disabled' : '' ?>>
                📎
            </button>
            <input type="file" name="gambar" id="inputGambar" accept="image/jpeg,image/png,image/webp" style="display: none;" onchange="previewGambar(this)">
            
            <input type="text" name="pertanyaan" id="inputPertanyaan" placeholder="<?= $sisa_limit > 0 ? t('ai_placeholder') : t('ai_limit_habis') ?>" required <?= $sisa_limit <= 0 ? 'disabled' : '' ?> autocomplete="off">
            
            <button type="submit" class="btn-send" id="btnSend" <?= $sisa_limit <= 0 ? 'disabled' : '' ?>>➤</button>
        </div>
    </form>
</div>

<script>
    const chatBox = document.getElementById('chatBox');
    const chatForm = document.getElementById('chatForm');
    const inputPertanyaan = document.getElementById('inputPertanyaan');
    const inputGambar = document.getElementById('inputGambar');
    const btnSend = document.getElementById('btnSend');
    const btnUpload = document.getElementById('btnUpload');
    const previewArea = document.getElementById('previewArea');
    const limitBadge = document.getElementById('limitBadge');
    const fotoUser = '<?= htmlspecialchars($foto_user) ?>';
    
    // ===== TRANSLATIONS =====
    const TXT = {
        remaining: '<?= t('ai_remaining') ?>',
        limit_habis: '<?= t('ai_limit_habis') ?>',
        error_connection: '<?= t('ai_error_connection') ?>',
        error_generic: '<?= t('ai_error_generic') ?>'
    };
    
    chatBox.scrollTop = chatBox.scrollHeight;
    
    chatForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const pertanyaan = inputPertanyaan.value.trim();
        if (!pertanyaan) return;
        
        btnSend.disabled = true;
        btnUpload.disabled = true;
        inputPertanyaan.disabled = true;
        
        const emptyState = document.getElementById('chatEmpty');
        if (emptyState) emptyState.style.display = 'none';
        
        let gambarPreviewHTML = '';
        if (inputGambar.files[0]) {
            const previewSrc = document.getElementById('previewImg').src;
            gambarPreviewHTML = `<img src="${previewSrc}" alt="Gambar" class="gambar-chat">`;
        }
        
        const userBubbleHTML = `
            <div class="chat-item-user">
                <div class="bubble">
                    ${gambarPreviewHTML}
                    ${escapeHtml(pertanyaan)}
                    <div class="time">${getCurrentTime()}</div>
                </div>
                <img src="../uploads/foto/${fotoUser}" alt="User" class="avatar">
            </div>
        `;
        chatBox.insertAdjacentHTML('beforeend', userBubbleHTML);
        
        const typingHTML = `
            <div class="chat-item-ai" id="typingBubble">
                <img src="../uploads/foto/defaults.png" alt="AI" class="avatar">
                <div class="bubble">
                    <div class="typing-animation">
                        <div class="dot"></div>
                        <div class="dot"></div>
                        <div class="dot"></div>
                    </div>
                </div>
            </div>
        `;
        chatBox.insertAdjacentHTML('beforeend', typingHTML);
        chatBox.scrollTop = chatBox.scrollHeight;
        
        const formData = new FormData();
        formData.append('pertanyaan', pertanyaan);
        formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);
        if (inputGambar.files[0]) {
            formData.append('gambar', inputGambar.files[0]);
        }
        
        const startTime = Date.now();
        const minAnimationTime = 3000;
        
        try {
            const response = await fetch('process_ai.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            const elapsed = Date.now() - startTime;
            const remaining = Math.max(0, minAnimationTime - elapsed);
            
            setTimeout(() => {
                const typingBubble = document.getElementById('typingBubble');
                if (typingBubble) typingBubble.remove();
                
                if (data.success) {
                    const aiBubbleHTML = `
                        <div class="chat-item-ai">
                            <img src="../uploads/foto/defaults.png" alt="AI" class="avatar">
                            <div class="bubble">
                                ${nl2br(escapeHtml(data.jawaban))}
                                <div class="time">${getCurrentTime()}</div>
                            </div>
                        </div>
                    `;
                    chatBox.insertAdjacentHTML('beforeend', aiBubbleHTML);
                    
                    document.querySelector('input[name="csrf_token"]').value = data.new_csrf_token;
                    
                    if (data.sisa_limit !== undefined) {
                        limitBadge.textContent = data.sisa_limit > 0 
                            ? `${TXT.remaining}: ${data.sisa_limit}/5` 
                            : TXT.limit_habis;
                        
                        if (data.sisa_limit <= 0) {
                            btnSend.disabled = true;
                            btnUpload.disabled = true;
                            inputPertanyaan.disabled = true;
                            inputPertanyaan.placeholder = TXT.limit_habis;
                        } else {
                            btnSend.disabled = false;
                            btnUpload.disabled = false;
                            inputPertanyaan.disabled = false;
                        }
                    } else {
                        btnSend.disabled = false;
                        btnUpload.disabled = false;
                        inputPertanyaan.disabled = false;
                    }
                } else {
                    const errorBubbleHTML = `
                        <div class="chat-item-ai">
                            <img src="../uploads/foto/defaults.png" alt="AI" class="avatar">
                            <div class="bubble" style="background: #f8d7da; color: #721c24;">
                                ${escapeHtml(data.error || TXT.error_generic)}
                            </div>
                        </div>
                    `;
                    chatBox.insertAdjacentHTML('beforeend', errorBubbleHTML);
                    
                    btnSend.disabled = false;
                    btnUpload.disabled = false;
                    inputPertanyaan.disabled = false;
                }
                
                inputPertanyaan.value = '';
                inputGambar.value = '';
                previewArea.classList.remove('active');
                inputPertanyaan.focus();
                chatBox.scrollTop = chatBox.scrollHeight;
            }, remaining);
            
        } catch (err) {
            const typingBubble = document.getElementById('typingBubble');
            if (typingBubble) typingBubble.remove();
            
            chatBox.insertAdjacentHTML('beforeend', `
                <div class="chat-item-ai">
                    <img src="../uploads/foto/defaults.png" alt="AI" class="avatar">
                    <div class="bubble" style="background: #f8d7da; color: #721c24;">
                        ❌ ${TXT.error_connection}
                    </div>
                </div>
            `);
            
            btnSend.disabled = false;
            btnUpload.disabled = false;
            inputPertanyaan.disabled = false;
            console.error(err);
        }
    });
    
    function previewGambar(input) {
        if (input.files && input.files[0]) {
            const file = input.files[0];
            
            if (file.size > 5000000) {
                alert('❌ ' + '<?= t("err_file_too_big") ?>');
                input.value = '';
                return;
            }
            
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('previewImg').src = e.target.result;
                document.getElementById('previewName').textContent = file.name;
                previewArea.classList.add('active');
            };
            reader.readAsDataURL(file);
        }
    }
    
    function cancelGambar() {
        inputGambar.value = '';
        previewArea.classList.remove('active');
    }
    
    function getCurrentTime() {
        const d = new Date();
        return String(d.getHours()).padStart(2, '0') + ':' + 
               String(d.getMinutes()).padStart(2, '0');
    }
    
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    function nl2br(text) {
        return text.replace(/\n/g, '<br>');
    }
</script>

<?php include '../includes/footer.php'; ?>