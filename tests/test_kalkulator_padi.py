import unittest
import sys
import os

# Tambahkan direktori tests ke sys.path agar modul kalkulator_padi dapat diimpor
sys.path.insert(0, os.path.dirname(__file__))

from kalkulator_padi import hitung_estimasi_panen, hitung_fase_padi

class TestKalkulatorPadi(unittest.TestCase):
    """
    Unit Testing Modul Perhitungan Pertanian Padi
    Menerapkan metode Black-Box Testing:
    - Equivalence Partitioning (EP)
    - Boundary Value Analysis (BVA)
    """

    # ========================================================
    # 1. PENGUJIAN ESTIMASI HASIL PANEN (EP & BVA)
    # ========================================================
    def test_estimasi_panen_varietas_valid_ep(self):
        """Uji Equivalence Partitioning: Menghitung hasil panen normal untuk tiap varietas"""
        # Luas 1.000 m2
        self.assertAlmostEqual(hitung_estimasi_panen(1000, 'Ciherang'), 5.5, places=2)
        self.assertAlmostEqual(hitung_estimasi_panen(1000, 'Inpari'), 6.0, places=2)
        self.assertAlmostEqual(hitung_estimasi_panen(1000, 'Mekongga'), 5.0, places=2)
        self.assertAlmostEqual(hitung_estimasi_panen(1000, 'Sintanur'), 4.5, places=2)

    def test_estimasi_panen_case_insensitive(self):
        """Uji ketahanan input nama varietas huruf kecil / kapital"""
        self.assertAlmostEqual(hitung_estimasi_panen(1000, 'ciherang'), 5.5, places=2)
        self.assertAlmostEqual(hitung_estimasi_panen(1000, 'INPARI'), 6.0, places=2)

    def test_estimasi_panen_bva_luas_batas(self):
        """Uji Boundary Value Analysis pada batas nilai luas terkecil (> 0)"""
        # Luas minimal 1 m2
        self.assertAlmostEqual(hitung_estimasi_panen(1, 'Inpari'), 0.006, places=4)

    def test_estimasi_panen_luas_invalid_bva(self):
        """Uji Boundary Value Analysis: Luas <= 0 wajib melempar ValueError"""
        with self.assertRaises(ValueError):
            hitung_estimasi_panen(0, 'Inpari')  # Batas nol
        with self.assertRaises(ValueError):
            hitung_estimasi_panen(-10, 'Ciherang')  # Nilai negatif

    def test_estimasi_panen_varietas_tidak_terdaftar(self):
        """Uji Equivalence Partitioning: Varietas tidak valid wajib melempar ValueError"""
        with self.assertRaises(ValueError):
            hitung_estimasi_panen(1000, 'KetanHitam')

    # ========================================================
    # 2. PENGUJIAN SIKLUS FASE PADI (BVA & EP)
    # ========================================================
    def test_fase_padi_siklus_normal_ep(self):
        """Uji Equivalence Partitioning: Memeriksa sampel representatif tiap fase pertumbuhan"""
        self.assertEqual(hitung_fase_padi(1), "Tanam Awal")
        self.assertEqual(hitung_fase_padi(5), "Cek Air Macak-macak")
        self.assertEqual(hitung_fase_padi(10), "Sulam & Gulma")
        self.assertEqual(hitung_fase_padi(30), "Pemupukan 1 (Vegetatif)")
        self.assertEqual(hitung_fase_padi(80), "Pemupukan 2 (Generatif)")
        self.assertEqual(hitung_fase_padi(115), "H-7 Menuju Panen")
        self.assertEqual(hitung_fase_padi(130), "Hari Panen Tiba")

    def test_fase_padi_batas_kritis_bva(self):
        """Uji Boundary Value Analysis pada titik batas pergantian fase (0, 3, 7, 14, 45, 113, 120)"""
        self.assertEqual(hitung_fase_padi(0), "Tanam Awal")
        self.assertEqual(hitung_fase_padi(2), "Tanam Awal")
        self.assertEqual(hitung_fase_padi(3), "Cek Air Macak-macak")
        self.assertEqual(hitung_fase_padi(6), "Cek Air Macak-macak")
        self.assertEqual(hitung_fase_padi(7), "Sulam & Gulma")
        self.assertEqual(hitung_fase_padi(13), "Sulam & Gulma")
        self.assertEqual(hitung_fase_padi(14), "Pemupukan 1 (Vegetatif)")
        self.assertEqual(hitung_fase_padi(44), "Pemupukan 1 (Vegetatif)")
        self.assertEqual(hitung_fase_padi(45), "Pemupukan 2 (Generatif)")
        self.assertEqual(hitung_fase_padi(112), "Pemupukan 2 (Generatif)")
        self.assertEqual(hitung_fase_padi(113), "H-7 Menuju Panen")
        self.assertEqual(hitung_fase_padi(119), "H-7 Menuju Panen")
        self.assertEqual(hitung_fase_padi(120), "Hari Panen Tiba")

    def test_fase_padi_umur_negatif_bva(self):
        """Uji Boundary Value Analysis: Umur hari < 0 wajib melempar ValueError"""
        with self.assertRaises(ValueError):
            hitung_fase_padi(-1)

if __name__ == '__main__':
    unittest.main()

