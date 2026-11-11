<?php
$langs = ['en', 'es', 'zh', 'fr', 'de', 'ar'];
$dir = __DIR__ . '/includes/lang/';

foreach ($langs as $lang) {
    $file = $dir . $lang . '.php';
    if (file_exists($file)) {
        $content = file_get_contents($file);
        
        // Hapus koma yang sendirian di baris baru sebelum 'forgot_password'
        $content = preg_replace("/\n,\n\s*'forgot_password'/", "\n    'forgot_password'", $content);
        $content = preg_replace("/\r\n,\r\n\s*'forgot_password'/", "\r\n    'forgot_password'", $content);
        
        file_put_contents($file, $content);
        echo "Fixed $lang.php<br>";
    }
}
echo "<strong>Semua file bahasa sudah diperbaiki! Silakan kembali ke halaman Lupa Password.</strong>";
?>
