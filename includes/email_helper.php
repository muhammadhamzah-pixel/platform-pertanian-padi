<?php
// includes/email_helper.php
// Fungsi buat kirim OTP via Email

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/PHPMailer/src/Exception.php';
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';

/**
 * Generate OTP 6 digit
 */
function generateOTP() {
    return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}

/**
 * Kirim OTP ke email
 */
function kirimOTP($email_tujuan, $otp, $nama) {
    $mail = new PHPMailer(true);
    
    try {
        // ===== SMTP CONFIG =====
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;
        $mail->CharSet    = 'UTF-8';
        
        // ===== PENGIRIM & PENERIMA =====
        $mail->setFrom(SMTP_USER, SMTP_FROM_NAME);
        $mail->addAddress($email_tujuan, $nama);
        $mail->addReplyTo(SMTP_USER, SMTP_FROM_NAME);
        
        // ===== ESCAPE OUTPUT (cegah XSS di email) =====
        $nama_aman = htmlspecialchars($nama, ENT_QUOTES, 'UTF-8');
        $otp_aman  = htmlspecialchars($otp, ENT_QUOTES, 'UTF-8');
        
        // ===== AMBIL BAHASA AKTIF =====
        // Cek bahasa dari cookie/session — karena email helper mungkin dipanggil
        // tanpa request user (misal: cron)
        $lang_code = 'id'; // default
        if (isset($_COOKIE['lang']) && in_array($_COOKIE['lang'], ['id', 'en', 'es', 'zh', 'fr', 'de', 'ar'])) {
            $lang_code = $_COOKIE['lang'];
        } elseif (isset($_SESSION['lang']) && in_array($_SESSION['lang'], ['id', 'en', 'es', 'zh', 'fr', 'de', 'ar'])) {
            $lang_code = $_SESSION['lang'];
        }
        
        // ===== TERJEMAHAN EMAIL (mini translate) =====
        $email_lang = [
            'id' => [
                'subject' => '🌾 Kode OTP - Platform Padi Desa',
                'title' => '🌾 Platform Padi Desa',
                'greeting' => 'Halo',
                'your_otp' => 'Kode OTP Anda:',
                'valid_for' => 'Kode ini berlaku selama <strong>5 menit</strong>.',
                'dont_share' => 'Jangan berikan kode ini ke siapapun.',
                'auto_email' => 'Email ini dikirim otomatis. Jangan balas.',
                'alt_valid' => 'Kode ini berlaku selama 5 menit.',
                'alt_share' => 'Jangan berikan kode ini ke siapapun.',
            ],
            'en' => [
                'subject' => '🌾 OTP Code - Village Rice Platform',
                'title' => '🌾 Village Rice Platform',
                'greeting' => 'Hello',
                'your_otp' => 'Your OTP Code:',
                'valid_for' => 'This code is valid for <strong>5 minutes</strong>.',
                'dont_share' => "Don't share this code with anyone.",
                'auto_email' => 'This email is sent automatically. Do not reply.',
                'alt_valid' => 'This code is valid for 5 minutes.',
                'alt_share' => "Don't share this code with anyone.",
            ],
            'es' => [
                'subject' => '🌾 Código OTP - Plataforma de Arroz',
                'title' => '🌾 Plataforma de Arroz del Pueblo',
                'greeting' => 'Hola',
                'your_otp' => 'Tu Código OTP:',
                'valid_for' => 'Este código es válido por <strong>5 minutos</strong>.',
                'dont_share' => 'No compartas este código con nadie.',
                'auto_email' => 'Este correo se envía automáticamente. No responder.',
                'alt_valid' => 'Este código es válido por 5 minutos.',
                'alt_share' => 'No compartas este código con nadie.',
            ],
            'zh' => [
                'subject' => '🌾 OTP 验证码 - 乡村水稻平台',
                'title' => '🌾 乡村水稻平台',
                'greeting' => '你好',
                'your_otp' => '您的 OTP 验证码：',
                'valid_for' => '此验证码有效期为 <strong>5 分钟</strong>。',
                'dont_share' => '请勿与任何人分享此验证码。',
                'auto_email' => '此邮件自动发送，请勿回复。',
                'alt_valid' => '此验证码有效期为 5 分钟。',
                'alt_share' => '请勿与任何人分享此验证码。',
            ],
            'fr' => [
                'subject' => '🌾 Code OTP - Plateforme de Riz',
                'title' => '🌾 Plateforme de Riz du Village',
                'greeting' => 'Bonjour',
                'your_otp' => 'Votre Code OTP :',
                'valid_for' => 'Ce code est valide pendant <strong>5 minutes</strong>.',
                'dont_share' => 'Ne partagez ce code avec personne.',
                'auto_email' => 'Cet email est envoyé automatiquement. Ne pas répondre.',
                'alt_valid' => 'Ce code est valide pendant 5 minutes.',
                'alt_share' => 'Ne partagez ce code avec personne.',
            ],
            'de' => [
                'subject' => '🌾 OTP-Code - Dorf-Reis-Plattform',
                'title' => '🌾 Dorf-Reis-Plattform',
                'greeting' => 'Hallo',
                'your_otp' => 'Ihr OTP-Code:',
                'valid_for' => 'Dieser Code ist <strong>5 Minuten</strong> gültig.',
                'dont_share' => 'Teilen Sie diesen Code mit niemandem.',
                'auto_email' => 'Diese E-Mail wird automatisch versendet. Nicht antworten.',
                'alt_valid' => 'Dieser Code ist 5 Minuten gültig.',
                'alt_share' => 'Teilen Sie diesen Code mit niemandem.',
            ],
            'ar' => [
                'subject' => '🌾 رمز OTP - منصة الأرز الريفية',
                'title' => '🌾 منصة الأرز الريفية',
                'greeting' => 'مرحبا',
                'your_otp' => 'رمز OTP الخاص بك:',
                'valid_for' => 'هذا الرمز صالح لمدة <strong>5 دقائق</strong>.',
                'dont_share' => 'لا تشارك هذا الرمز مع أي شخص.',
                'auto_email' => 'يتم إرسال هذا البريد تلقائيًا. لا ترد.',
                'alt_valid' => 'هذا الرمز صالح لمدة 5 دقائق.',
                'alt_share' => 'لا تشارك هذا الرمز مع أي شخص.',
            ],
        ];
        
        // Fallback ke Indonesia kalau bahasa nggak ada
        $T = $email_lang[$lang_code] ?? $email_lang['id'];
        
        // ===== KONTEN EMAIL =====
        $mail->isHTML(true);
        $mail->Subject = $T['subject'];
        $mail->Body    = "
        <!DOCTYPE html>
        <html lang='{$lang_code}'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>{$T['subject']}</title>
        </head>
        <body style='margin: 0; padding: 0; background-color: #e8eee8;'>
            <table role='presentation' border='0' cellpadding='0' cellspacing='0' width='100%'>
                <tr>
                    <td style='padding: 20px 0;'>
                        <div style='font-family: Arial, sans-serif; max-width: 500px; margin: 0 auto; padding: 20px; background: #ffffff; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); border-top: 5px solid #1b5e20;'>
                            <h2 style='color: #1b5e20; text-align: center; margin-top: 0;'>{$T['title']}</h2>
                            <p style='color: #333333;'>{$T['greeting']} <strong>$nama_aman</strong>,</p>
                            <p style='color: #333333;'>{$T['your_otp']}</p>
                            <div style='background: #1b5e20; color: #ffffff; padding: 15px; border-radius: 8px; text-align: center; font-size: 28px; letter-spacing: 5px; font-weight: bold; margin: 25px 0;'>
                                $otp_aman
                            </div>
                            <p style='color: #555555; font-size: 14px;'>{$T['valid_for']}</p>
                            <p style='color: #555555; font-size: 14px;'>{$T['dont_share']}</p>
                            <hr style='border: none; border-top: 1px solid #eeeeee; margin: 25px 0;'>
                            <p style='color: #999999; font-size: 12px; text-align: center; margin-bottom: 0;'>
                                {$T['auto_email']}<br>
                                &copy; " . date('Y') . " {$T['title']}
                            </p>
                        </div>
                    </td>
                </tr>
            </table>
        </body>
        </html>
        ";
        
        // ===== VERSI TEXT =====
        $mail->AltBody = "{$T['greeting']} $nama_aman,\n\n{$T['your_otp']} $otp_aman\n\n{$T['alt_valid']}\n{$T['alt_share']}\n\n{$T['title']}";
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        // ===== LOG ERROR =====
        error_log("Gagal kirim OTP ke $email_tujuan: " . $mail->ErrorInfo);
        return false;
    }
}
/**
 * Fungsi untuk mengirim notifikasi umum via Email (Gmail)
 */
function kirimEmailNotifikasi($email_tujuan, $nama_penerima, $judul, $pesan) {
    $mail = new PHPMailer(true);
    
    try {
        // Konfigurasi SMTP (Sama persis kayak OTP)
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;
        $mail->CharSet    = 'UTF-8';

        // Pengirim & Penerima
        $mail->setFrom(SMTP_USER, SMTP_FROM_NAME);
        $mail->addAddress($email_tujuan, $nama_penerima);

        // Konten Email
        $mail->isHTML(true);
        $mail->Subject = $judul;
        
        // Bikin template HTML simpel
        $nama_aman = htmlspecialchars($nama_penerima);
        $pesan_aman = nl2br(htmlspecialchars($pesan));
        
        $mail->Body = "
        <div style='font-family: Arial, sans-serif; max-width: 500px; margin: 0 auto; background: #f9f9f9; padding: 20px; border-radius: 10px; border-top: 5px solid #1b5e20;'>
            <h2 style='color: #1b5e20;'>🌾 Platform Padi Desa</h2>
            <p>Halo <strong>$nama_aman</strong>,</p>
            <p>$pesan_aman</p>
            <br>
            <p style='font-size: 12px; color: #888;'>Email ini dikirim otomatis oleh sistem. Jangan dibalas.</p>
        </div>
        ";
        
        $mail->AltBody = strip_tags($pesan);

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Gagal kirim notif email ke $email_tujuan: " . $mail->ErrorInfo);
        return false;
    }
}
?>