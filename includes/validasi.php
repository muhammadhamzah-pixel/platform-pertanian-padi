<?php
// includes/validasi.php
// Kumpulan fungsi validasi spesifik

/**
 * Validasi No HP (10-15 digit angka, wajib diawali 08)
 */
function validasi_no_hp($no_hp) {
    if (empty($no_hp)) {
        return true; // No HP opsional
    }
    $no_hp = trim($no_hp);
    if (!preg_match('/^[0-9]{10,15}$/', $no_hp)) {
        return "No HP harus 10-15 digit angka!";
    }
    // Cek harus mulai dengan 08 (nomor Indonesia)
    if (substr($no_hp, 0, 2) !== '08') {
        return "No HP harus diawali dengan 08!";
    }
    return true;
}

/**
 * Validasi nama (min 3 karakter, max 100, cuma huruf & spasi)
 */
function validasi_nama($nama) {
    if (empty($nama)) {
        return "Nama tidak boleh kosong!";
    }
    $nama = trim($nama);
    if (strlen($nama) < 3) {
        return "Nama minimal 3 karakter!";
    }
    if (strlen($nama) > 100) {
        return "Nama maksimal 100 karakter!";
    }
    if (!preg_match('/^[a-zA-Z\s\.\,\']+$/', $nama)) {
        return "Nama cuma boleh huruf, spasi, titik, koma!";
    }
    return true;
}

/**
 * Validasi nama lahan (boleh ada angka, max 100)
 */
function validasi_nama_lahan($nama, $field_name = 'Nama Lahan') {
    if (empty($nama)) {
        return "$field_name tidak boleh kosong!";
    }
    $nama = trim($nama);
    if (strlen($nama) < 3) {
        return "$field_name minimal 3 karakter!";
    }
    if (strlen($nama) > 100) {
        return "$field_name maksimal 100 karakter!";
    }
    if (!preg_match('/^[a-zA-Z0-9\s\.\,\-\/\']+$/', $nama)) {
        return "$field_name mengandung karakter tidak valid!";
    }
    return true;
}

/**
 * Validasi alamat/lokasi (boleh ada angka, max 500)
 */
function validasi_alamat($alamat, $field_name = 'Alamat') {
    if (empty($alamat)) {
        return "$field_name tidak boleh kosong!";
    }
    $alamat = trim($alamat);
    if (strlen($alamat) < 3) {
        return "$field_name minimal 3 karakter!";
    }
    if (strlen($alamat) > 500) {
        return "$field_name maksimal 500 karakter!";
    }
    if (!preg_match('/^[a-zA-Z0-9\s\.\,\-\/\']+$/', $alamat)) {
        return "$field_name mengandung karakter tidak valid!";
    }
    return true;
}

/**
 * Validasi password (min 6, max 100)
 */
function validasi_password($password) {
    if (empty($password)) {
        return "Password tidak boleh kosong!";
    }
    if (strlen($password) < 6) {
        return "Password minimal 6 karakter!";
    }
    if (strlen($password) > 100) {
        return "Password maksimal 100 karakter!";
    }
    return true;
}

/**
 * Validasi angka positif (luas lahan, dll)
 * @param mixed $angka
 * @param string $field_name
 * @param float $max — batas maksimal (default 1.000.000)
 */
function validasi_angka_positif($angka, $field_name = 'Nilai', $max = 1000000) {
    if (empty($angka)) {
        return "$field_name tidak boleh kosong!";
    }
    if (!is_numeric($angka)) {
        return "$field_name harus berupa angka!";
    }
    if ($angka <= 0) {
        return "$field_name harus lebih dari 0!";
    }
    if ($angka > $max) {
        return "$field_name maksimal " . number_format($max, 0, ',', '.') . "!";
    }
    return true;
}

/**
 * Validasi luas lahan (khusus, max 10 hektar = 100.000 m²)
 */
function validasi_luas_lahan($luas) {
    return validasi_angka_positif($luas, 'Luas lahan', 100000);
}

/**
 * Validasi tanggal (format Y-m-d, maks hari ini)
 */
function validasi_tanggal($tanggal, $field_name = 'Tanggal') {
    if (empty($tanggal)) {
        return "$field_name tidak boleh kosong!";
    }
    $d = DateTime::createFromFormat('Y-m-d', $tanggal);
    if (!$d || $d->format('Y-m-d') !== $tanggal) {
        return "$field_name tidak valid!";
    }
    // Cek tanggal nggak boleh di masa depan
    if (strtotime($tanggal) > strtotime(date('Y-m-d'))) {
        return "$field_name tidak boleh di masa depan!";
    }
    return true;
}

/**
 * Validasi email
 */
function validasi_email($email, $wajib = true) {
    if (empty($email)) {
        if ($wajib) {
            return "Email tidak boleh kosong!";
        }
        return true;
    }
    $email = trim($email);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return "Format email tidak valid!";
    }
    if (strlen($email) > 254) {
        return "Email terlalu panjang!";
    }
    return true;
}

/**
 * Fungsi bantu: Cek semua validasi
 */
function cek_validasi($validasi_array) {
    foreach ($validasi_array as $hasil) {
        if ($hasil !== true) {
            return $hasil;
        }
    }
    return true;
}
?>