<?php
// Script to add forgot password translations and create table
$lang_dir = __DIR__ . '/includes/lang/';
$langs = ['id', 'en', 'es', 'zh', 'fr', 'de', 'ar'];

$translations = [
    'id' => [
        'forgot_password' => 'Lupa Password?',
        'forgot_password_title' => 'Reset Password',
        'forgot_password_desc' => 'Masukkan email Anda untuk menerima link reset password.',
        'send_reset_link' => 'Kirim Link Reset',
        'email_not_found' => 'Email tidak ditemukan!',
        'reset_link_sent' => 'Link reset berhasil dikirim!',
        'new_password' => 'Password Baru',
        'confirm_new_password' => 'Konfirmasi Password Baru',
        'reset_password_btn' => 'Reset Password',
        'password_reset_success' => 'Password berhasil direset! Silakan login.',
        'invalid_token' => 'Token tidak valid atau sudah kadaluarsa!',
        'password_mismatch' => 'Konfirmasi password tidak cocok!',
        'back_to_login' => 'Kembali ke Login'
    ],
    'en' => [
        'forgot_password' => 'Forgot Password?',
        'forgot_password_title' => 'Reset Password',
        'forgot_password_desc' => 'Enter your email to receive a password reset link.',
        'send_reset_link' => 'Send Reset Link',
        'email_not_found' => 'Email not found!',
        'reset_link_sent' => 'Reset link sent successfully!',
        'new_password' => 'New Password',
        'confirm_new_password' => 'Confirm New Password',
        'reset_password_btn' => 'Reset Password',
        'password_reset_success' => 'Password reset successfully! Please login.',
        'invalid_token' => 'Invalid or expired token!',
        'password_mismatch' => 'Passwords do not match!',
        'back_to_login' => 'Back to Login'
    ],
    'es' => [
        'forgot_password' => '¿Olvidaste tu contraseña?',
        'forgot_password_title' => 'Restablecer Contraseña',
        'forgot_password_desc' => 'Introduce tu correo para recibir un enlace.',
        'send_reset_link' => 'Enviar Enlace',
        'email_not_found' => '¡Correo no encontrado!',
        'reset_link_sent' => '¡Enlace enviado!',
        'new_password' => 'Nueva Contraseña',
        'confirm_new_password' => 'Confirmar Nueva Contraseña',
        'reset_password_btn' => 'Restablecer',
        'password_reset_success' => '¡Contraseña restablecida! Por favor inicia sesión.',
        'invalid_token' => '¡Token inválido o caducado!',
        'password_mismatch' => '¡Las contraseñas no coinciden!',
        'back_to_login' => 'Volver'
    ],
    'zh' => [
        'forgot_password' => '忘记密码？',
        'forgot_password_title' => '重置密码',
        'forgot_password_desc' => '输入您的电子邮件以接收密码重置链接。',
        'send_reset_link' => '发送重置链接',
        'email_not_found' => '未找到电子邮件！',
        'reset_link_sent' => '重置链接已发送！',
        'new_password' => '新密码',
        'confirm_new_password' => '确认新密码',
        'reset_password_btn' => '重置密码',
        'password_reset_success' => '密码重置成功！请登录。',
        'invalid_token' => '无效或已过期的令牌！',
        'password_mismatch' => '密码不匹配！',
        'back_to_login' => '返回登录'
    ],
    'fr' => [
        'forgot_password' => 'Mot de passe oublié ?',
        'forgot_password_title' => 'Réinitialiser le mot de passe',
        'forgot_password_desc' => 'Entrez votre e-mail pour recevoir un lien.',
        'send_reset_link' => 'Envoyer le lien',
        'email_not_found' => 'E-mail introuvable !',
        'reset_link_sent' => 'Lien de réinitialisation envoyé !',
        'new_password' => 'Nouveau mot de passe',
        'confirm_new_password' => 'Confirmer le mot de passe',
        'reset_password_btn' => 'Réinitialiser',
        'password_reset_success' => 'Mot de passe réinitialisé !',
        'invalid_token' => 'Jeton invalide ou expiré !',
        'password_mismatch' => 'Les mots de passe ne correspondent pas !',
        'back_to_login' => 'Retour à la connexion'
    ],
    'de' => [
        'forgot_password' => 'Passwort vergessen?',
        'forgot_password_title' => 'Passwort zurücksetzen',
        'forgot_password_desc' => 'Geben Sie Ihre E-Mail ein, um einen Link zu erhalten.',
        'send_reset_link' => 'Link senden',
        'email_not_found' => 'E-Mail nicht gefunden!',
        'reset_link_sent' => 'Link erfolgreich gesendet!',
        'new_password' => 'Neues Passwort',
        'confirm_new_password' => 'Neues Passwort bestätigen',
        'reset_password_btn' => 'Zurücksetzen',
        'password_reset_success' => 'Passwort zurückgesetzt! Bitte einloggen.',
        'invalid_token' => 'Ungültiges oder abgelaufenes Token!',
        'password_mismatch' => 'Passwörter stimmen nicht überein!',
        'back_to_login' => 'Zurück zum Login'
    ],
    'ar' => [
        'forgot_password' => 'هل نسيت كلمة المرور؟',
        'forgot_password_title' => 'إعادة تعيين كلمة المرور',
        'forgot_password_desc' => 'أدخل بريدك الإلكتروني لتلقي رابط إعادة التعيين.',
        'send_reset_link' => 'إرسال رابط',
        'email_not_found' => 'البريد الإلكتروني غير موجود!',
        'reset_link_sent' => 'تم إرسال رابط إعادة التعيين بنجاح!',
        'new_password' => 'كلمة مرور جديدة',
        'confirm_new_password' => 'تأكيد كلمة المرور الجديدة',
        'reset_password_btn' => 'إعادة تعيين كلمة المرور',
        'password_reset_success' => 'تمت إعادة تعيين كلمة المرور بنجاح! الرجاء تسجيل الدخول.',
        'invalid_token' => 'رمز غير صالح أو منتهي الصلاحية!',
        'password_mismatch' => 'كلمات المرور غير متطابقة!',
        'back_to_login' => 'العودة لتسجيل الدخول'
    ]
];

foreach ($langs as $lang) {
    $file = $lang_dir . $lang . '.php';
    if (file_exists($file)) {
        $content = file_get_contents($file);
        
        // Cek kalau sudah ditambahkan
        if (strpos($content, "'forgot_password'") === false) {
            // Hapus awalan dan akhiran tag php dari konten array kita
            $new_keys = "";
            foreach ($translations[$lang] as $k => $v) {
                // escape single quotes
                $v = str_replace("'", "\'", $v);
                $new_keys .= "    '$k' => '$v',\n";
            }
            
            // Sisipkan sebelum return [
            // Tapi file terstruktur sebagai return [ ... ];
            // Jadi kita ganti akhirannya
            $content = preg_replace("/\];?\s*$/", ",\n" . $new_keys . "];\n", $content);
            file_put_contents($file, $content);
            echo "Updated $lang.php\n";
        } else {
            echo "Skipped $lang.php (already has translations)\n";
        }
    }
}

// Database setup for password_resets table
include __DIR__ . '/includes/config.php';

$sql = "CREATE TABLE IF NOT EXISTS password_resets (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL,
    token VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)";

if (mysqli_query($conn, $sql)) {
    echo "Table password_resets created/exists.\n";
} else {
    echo "Error creating table: " . mysqli_error($conn) . "\n";
}

?>
