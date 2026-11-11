<?php
// includes/ai_tools.php

/**
 * Mendapatkan "Kamus" (Skema) Database berdasarkan Role User.
 * AI HANYA akan tahu tabel & kolom yang ada di dalam string ini.
 * 
 * @param string $role Role user (admin, perangkat_desa, petani)
 * @param int $user_id ID user yang sedang login (penting buat filter privasi)
 * @return string Teks skema database untuk prompt AI
 */
function getDatabaseSchemaForAI($role, $user_id) {
    
    // ========================================================
    // 1. SKEMA UNTUK PETANI (Privasi Ketat & Hanya Data Milik Sendiri)
    // ========================================================
    if ($role === 'petani') {
        return "
        ATURAN PRIVASI PETANI:
        - Data Lahan & Panen: Kamu HANYA BOLEH mengambil data milik petani ini sendiri (user_id = '$user_id').
        - DILARANG KERAS membocorkan data pribadi atau rincian lahan milik petani lain.
        - Data Publik: Boleh melihat statistik umum desa (misal: total petani terdaftar, jumlah perangkat desa, profil desa, FAQ).

        TABEL YANG BOLEH DIAKSES:
        1. Tabel `lahan` (Data lahan milik user ini):
           Kolom: id, user_id, nama_lahan, lokasi, luas (m2), jenis_padi, tgl_tanam, tgl_panen_perkiraan, status, catatan
           Status ENUM: 'tanam', 'tumbuh', 'panen', 'selesai'
           Syarat query: WHERE user_id = '$user_id'
        
        2. Tabel `riwayat_panen` (Hasil panen nyata):
           Kolom: id, lahan_id, tgl_panen_real, hasil_kg, catatan, created_at
           Relasi: Hubungkan dengan tabel lahan: JOIN lahan ON riwayat_panen.lahan_id = lahan.id WHERE lahan.user_id = '$user_id'
        
        3. Tabel `notifikasi` (Pemberitahuan & pengingat jadwal panen/pupuk):
           Kolom: id, user_id, judul, pesan, link, tipe, is_read, created_at
           Syarat query: WHERE user_id = '$user_id'
        
        4. Tabel `desa_settings` (Informasi publik desa):
           Kolom: nama_desa, kecamatan, kabupaten, kontak, default_hasil_per_m2
        
        5. Tabel `users` (HANYA untuk statistik agregat publik):
           Kolom role (ENUM: 'petani', 'perangkat_desa', 'admin')
           Kolom status (ENUM: 'pending', 'active', 'blocked')
           - Menghitung petani terdaftar: SELECT COUNT(*) FROM users WHERE role = 'petani' AND status = 'active'
           - Menghitung perangkat desa: SELECT COUNT(*) FROM users WHERE role = 'perangkat_desa'
           DILARANG mengambil detail kolom nama/no_hp/email pengguna lain.

        6. Tabel `faq` (Pusat bantuan seputar padi & web):
           Kolom: pertanyaan, jawaban
        ";
    }

    // ========================================================
    // 2. SKEMA UNTUK PERANGKAT DESA
    // ========================================================
    if ($role === 'perangkat_desa') {
        return "
        ATURAN HAK AKSES PERANGKAT DESA:
        - Memiliki akses pemantauan tingkat desa untuk: desa_settings, lahan, log_aktivitas, riwayat_panen, dan data petani/perangkat desa.
        - DILARANG KERAS mengakses password_hash, token reset, atau OTP user.

        TABEL YANG BOLEH DIAKSES:
        1. Tabel `desa_settings` (Pengaturan dan profil desa):
           Kolom: id, nama_desa, kecamatan, kabupaten, kontak, default_hasil_per_m2, created_at
        
        2. Tabel `lahan` (Seluruh data lahan pertanian di desa):
           Kolom: id, user_id, nama_lahan, lokasi, luas (m2), jenis_padi, tgl_tanam, tgl_panen_perkiraan, status, catatan, created_at
           Status ENUM: 'tanam', 'tumbuh', 'panen', 'selesai'
        
        3. Tabel `riwayat_panen` (Catatan hasil panen seluruh petani):
           Kolom: id, lahan_id, tgl_panen_real, hasil_kg, catatan, created_at
           Bisa di-JOIN dengan tabel `lahan` dan `users` untuk melihat pemiliknya.
        
        4. Tabel `log_aktivitas` (Audit & jejak aktivitas sistem desa):
           Kolom: id, user_id, nama, email, aktivitas, keterangan, ip_address, country, is_bot, created_at

        5. Tabel `users` (Daftar pengguna & aparatur desa):
           Kolom: id, nama, email, no_hp, alamat, role, status, created_at
           - role (ENUM: 'petani', 'perangkat_desa', 'admin')
             * Perangkat Desa: WAJIB role = 'perangkat_desa' (dengan underscore). BUKAN 'desa', BUKAN 'perangkat'.
             * Petani: role = 'petani'
             * Admin: role = 'admin'
           - status (ENUM: 'pending', 'active', 'blocked')
             * 'active' = aktif
             * 'pending' = menunggu verifikasi
             * 'blocked' = diblokir
        ";
    }

    // ========================================================
    // 3. SKEMA UNTUK ADMIN (Akses Penuh Seluruh Database)
    // ========================================================
    if ($role === 'admin') {
        return "
        ATURAN HAK AKSES SUPER ADMIN:
        - Kamu adalah Asisten AI Administrator dengan izin akses penuh ke SELURUH tabel database.
        - Keamanan: Jangan pernah menampilkan password_hash atau rahasia API key secara mentah kepada siapapun.

        TABEL DATABASE & STRUKTUR ENUM LENGKAP:

        1. `users` (Pengguna Sistem):
           Kolom: id, nama, email, no_hp, alamat, role, status, email_verified, last_login_ip, last_login_at, created_at
           - role (ENUM: 'petani', 'perangkat_desa', 'admin')
             * PENTING SEKALI: Nilai role untuk perangkat desa adalah PERSIS 'perangkat_desa' (dengan underscore).
             * BUKAN 'desa', BUKAN 'perangkat', dan JANGAN menggabungkan role 'admin'.
             * 'admin' adalah Administrator sistem (BUKAN perangkat desa).
             * 'petani' adalah Petani binaan desa.
           - status (ENUM: 'pending', 'active', 'blocked')
             * 'active' = Akun aktif / terverifikasi
             * 'pending' = Akun baru yang belum diverifikasi admin
             * 'blocked' = Akun yang diblokir

        2. `lahan` (Lahan Sawah Pertanian):
           Kolom: id, user_id, nama_lahan, lokasi, luas (m2), jenis_padi, tgl_tanam, tgl_panen_perkiraan, status, catatan, created_at
           - status (ENUM: 'tanam', 'tumbuh', 'panen', 'selesai')
           - user_id berelasi dengan users.id (pemilik lahan)

        3. `riwayat_panen`:
           Kolom: id, lahan_id, tgl_panen_real, hasil_kg, catatan, created_at
           - lahan_id berelasi dengan lahan.id

        4. `desa_settings`:
           Kolom: id, nama_desa, kecamatan, kabupaten, kontak, default_hasil_per_m2, created_at

        5. `log_aktivitas`:
           Kolom: id, user_id, email, nama, aktivitas, keterangan, ip_address, country, is_bot, created_at

        6. `login_attempts`:
           Kolom: id, email, ip_address, user_agent, status (ENUM: 'gagal', 'berhasil'), created_at

        7. `notifikasi`:
           Kolom: id, user_id, judul, pesan, link, tipe, is_read, created_at

        8. `chat_ai`:
           Kolom: id, user_id, pertanyaan, jawaban, created_at

        9. `faq`:
           Kolom: id, pertanyaan, jawaban, created_at

        10. `chat_admin` & `chat_messages`:
           Kolom: id, pengirim_id, penerima_id, pesan, is_read, created_at

        PANDUAN PEMBUATAN QUERY SQL UNTUK ADMIN:
        - Jika pengguna bertanya tentang 'perangkat desa' (jumlah atau siapa saja), gunakan kondisi:
          `WHERE role = 'perangkat_desa'`
        - JANGAN PERNAH menyertakan 'admin' saat ditanya 'perangkat desa', kecuali pengguna secara eksplisit menanyakan 'admin dan perangkat desa'.
        - Saat menghitung atau mengambil data jumlah orang/perangkat/petani, disarankan mengambil kolom rincian seperti nama dan status (misal: `SELECT COUNT(*) AS total, GROUP_CONCAT(nama SEPARATOR ', ') AS daftar_nama FROM users WHERE role = 'perangkat_desa'` atau `SELECT id, nama, role, status FROM users WHERE role = 'perangkat_desa'`) agar kamu bisa menyebutkan total sekaligus nama-nama orangnya secara akurat.
        - Jika pengguna menanyakan role/status seseorang secara spesifik (contoh: 'Budi sekarang rolenya apa?', 'apakah Budi perangkat desa atau petani?', 'cek akun Budi'):
          Gunakan pencarian nama: `SELECT id, nama, email, role, status FROM users WHERE nama LIKE '%Budi%' OR email LIKE '%Budi%'`
        - Jika pengguna menanyakan siapa yang baru saja diubah rolenya / riwayat kelola user terakhir (contoh: 'siapa yang barusan diedit?', 'siapa yang baru saja diubah jadi perangkat desa/petani?', 'cek log kelola user'):
          Gunakan query ke tabel `log_aktivitas`: `SELECT nama, email, aktivitas, keterangan, created_at FROM log_aktivitas WHERE aktivitas = 'Kelola User' ORDER BY created_at DESC LIMIT 5`
        - Jika pengguna menanyakan akun yang belum diverifikasi / pending:
          `WHERE status = 'pending'`
        - Jika pengguna menanyakan akun petani aktif:
          `WHERE role = 'petani' AND status = 'active'`
        - Jika pengguna menanyakan lahan yang siap panen:
          `WHERE status = 'panen'`
        ";
    }

    return "Tidak ada akses database untuk role ini.";
}

/**
 * Fungsi untuk membersihkan query SQL yang dihasilkan AI.
 * Mencegah AI menulis query berbahaya (seperti DROP TABLE atau UPDATE),
 * tetapi tetap mengizinkan kolom aman seperti `created_at` atau `updated_at`.
 */
function sanitizeAiQuery($sql) {
    $sql = trim($sql);
    
    // Hapus format markdown kalau AI memberikan ```sql ... ```
    $sql = preg_replace('/```sql/i', '', $sql);
    $sql = preg_replace('/```/i', '', $sql);
    $sql = trim($sql);
    // Hapus titik koma di akhir query
    $sql = rtrim($sql, "; \t\n\r\0\x0B");
    
    // Whitelist: Harus diawali dengan SELECT (hanya membaca data)
    if (!preg_match('/^\s*SELECT\b/i', $sql)) {
        return false; 
    }

    // Cegah multi-query (stacked queries dengan titik koma di tengah)
    if (strpos($sql, ';') !== false) {
        return false;
    }

    // Blacklist: Cegah query manipulasi berbahaya dengan boundary kata (\b)
    // Menggunakan \b agar tidak salah memblokir kolom seperti `created_at` atau `updated_at`
    if (preg_match('/\b(DROP|DELETE|UPDATE|INSERT|ALTER|TRUNCATE|CREATE|GRANT|REVOKE|REPLACE|INTO\s+OUTFILE|LOAD_FILE)\b/i', $sql)) {
        return false;
    }

    return $sql;
}
?>