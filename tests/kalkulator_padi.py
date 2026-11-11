"""
Modul Perhitungan Pertanian Padi
Platform Padi Desa (AgriPlatform v2.0)
Implementasi TDD & Clean Architecture
"""

# Tabel koefisien produktivitas varietas padi (Ton per m2)
FAKTOR_PRODUKTIVITAS = {
    'ciherang': 0.0055,  # 5.5 kg / m2
    'inpari': 0.0060,    # 6.0 kg / m2
    'mekongga': 0.0050,  # 5.0 kg / m2
    'sintanur': 0.0045   # 4.5 kg / m2
}

def hitung_estimasi_panen(luas_m2: float, varietas: str) -> float:
    """
    Menghitung estimasi hasil panen padi dalam satuan Ton.
    
    Parameter:
    - luas_m2 (float/int): Luas lahan sawah dalam meter persegi (wajib > 0).
    - varietas (str): Nama varietas padi terdaftar (Ciherang, Inpari, Mekongga, Sintanur).
    
    Return:
    - float: Estimasi total panen dalam Ton.
    
    Exception:
    - ValueError: Jika luas <= 0 atau varietas tidak terdaftar.
    """
    if luas_m2 <= 0:
        raise ValueError("Luas lahan harus lebih besar dari 0")
    
    nama_varietas = varietas.strip().lower()
    if nama_varietas not in FAKTOR_PRODUKTIVITAS:
        raise ValueError(f"Varietas padi '{varietas}' tidak valid")
    
    koefisien = FAKTOR_PRODUKTIVITAS[nama_varietas]
    total_ton = luas_m2 * koefisien
    return round(total_ton, 4)

def hitung_fase_padi(umur_hari: int) -> str:
    """
    Menentukan fase siklus pertumbuhan padi berdasarkan umur tanaman (hari).
    
    Parameter:
    - umur_hari (int): Jumlah hari sejak tanggal tanam (wajib >= 0).
    
    Return:
    - str: Label nama fase pertumbuhan padi.
    
    Exception:
    - ValueError: Jika umur hari < 0.
    """
    if umur_hari < 0:
        raise ValueError("Umur padi tidak boleh negatif")
    
    if umur_hari <= 2:
        return "Tanam Awal"
    elif umur_hari <= 6:
        return "Cek Air Macak-macak"
    elif umur_hari <= 13:
        return "Sulam & Gulma"
    elif umur_hari <= 44:
        return "Pemupukan 1 (Vegetatif)"
    elif umur_hari <= 112:
        return "Pemupukan 2 (Generatif)"
    elif umur_hari <= 119:
        return "H-7 Menuju Panen"
    else:
        return "Hari Panen Tiba"

