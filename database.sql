-- ============================================================================
-- DATABASE SCHEMA: APLIKASI PRESENSI DIGITAL SMA NEGERI 1 LHOKSUKON
-- UNTUK CPANEL MYSQL / MARIADB DATABASE
-- ============================================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+07:00";

-- 1. TABEL USERS (AKUN ADMINISTRATOR & PENGGUNA)
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(100) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'guru', 'tendik', 'siswa') NOT NULL DEFAULT 'siswa',
  `nip` VARCHAR(50) DEFAULT '-',
  `nama` VARCHAR(150) NOT NULL,
  `no_hp` VARCHAR(30) DEFAULT '-',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed Data Users Initial
INSERT INTO `users` (`username`, `password`, `role`, `nip`, `nama`, `no_hp`) VALUES
('admin', 'admin123', 'admin', '198501012010011001', 'Administrator SMANSA', '081234567890'),
('guru', 'guru123', 'guru', '197805122005011003', 'Drs. Muhammad Hasan, M.Pd', '085260112233'),
('tendik', 'tendik123', 'tendik', '198203152009022001', 'Cut Rahmah, S.Sos', '085370445566')
ON DUPLICATE KEY UPDATE `nama` = VALUES(`nama`);

-- 2. TABEL SISWA
CREATE TABLE IF NOT EXISTS `siswa` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `nama` VARCHAR(150) NOT NULL,
  `nisn` VARCHAR(30) NOT NULL,
  `jenis_kelamin` ENUM('Laki-laki', 'Perempuan') NOT NULL DEFAULT 'Laki-laki',
  `tanggal_lahir` DATE DEFAULT '2008-01-01',
  `agama` VARCHAR(30) DEFAULT 'Islam',
  `nama_ayah` VARCHAR(150) DEFAULT '-',
  `nama_ibu` VARCHAR(150) DEFAULT '-',
  `no_hp` VARCHAR(30) DEFAULT '-',
  `kelas` VARCHAR(50) NOT NULL,
  `alamat` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nisn` (`nisn`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed Data Siswa Initial
INSERT INTO `siswa` (`nama`, `nisn`, `jenis_kelamin`, `tanggal_lahir`, `agama`, `nama_ayah`, `nama_ibu`, `no_hp`, `kelas`, `alamat`) VALUES
('Ahmad Rizki', '0061234567', 'Laki-laki', '2008-03-12', 'Islam', 'Abdullah', 'Amina', '081299887766', 'X MIPA 1', 'Jl. Medan-Banda Aceh, Lhoksukon'),
('Siti Nurhaliza', '0067654321', 'Perempuan', '2008-07-25', 'Islam', 'Zulkifli', 'Fatimah', '081377665544', 'X MIPA 1', 'Lhoksukon Tengah'),
('Muhammad Syahrul', '0069876543', 'Laki-laki', '2008-01-10', 'Islam', 'Ibrahim', 'Mariam', '085211223344', 'XI IPS 2', 'Matang Ulim, Lhoksukon')
ON DUPLICATE KEY UPDATE `nama` = VALUES(`nama`);

-- 3. TABEL GURU
CREATE TABLE IF NOT EXISTS `guru` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `nip` VARCHAR(50) NOT NULL,
  `nama` VARCHAR(150) NOT NULL,
  `jenis_kelamin` ENUM('Laki-laki', 'Perempuan') NOT NULL DEFAULT 'Laki-laki',
  `jabatan` VARCHAR(100) DEFAULT 'Guru Mata Pelajaran',
  `username` VARCHAR(100) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `no_hp` VARCHAR(30) DEFAULT '-',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nip` (`nip`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed Data Guru Initial
INSERT INTO `guru` (`nip`, `nama`, `jenis_kelamin`, `jabatan`, `username`, `password`, `no_hp`) VALUES
('197805122005011003', 'Drs. Muhammad Hasan, M.Pd', 'Laki-laki', 'Wali Kelas X MIPA 1', 'guru', 'guru123', '085260112233')
ON DUPLICATE KEY UPDATE `nama` = VALUES(`nama`);

-- 4. TABEL TENDIK (STAF KEPENDIDIKAN)
CREATE TABLE IF NOT EXISTS `tendik` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `nip` VARCHAR(50) NOT NULL,
  `nama` VARCHAR(150) NOT NULL,
  `jenis_kelamin` ENUM('Laki-laki', 'Perempuan') NOT NULL DEFAULT 'Perempuan',
  `jabatan` VARCHAR(100) DEFAULT 'Staf Tata Usaha',
  `username` VARCHAR(100) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `no_hp` VARCHAR(30) DEFAULT '-',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nip` (`nip`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed Data Tendik Initial
INSERT INTO `tendik` (`nip`, `nama`, `jenis_kelamin`, `jabatan`, `username`, `password`, `no_hp`) VALUES
('198203152009022001', 'Cut Rahmah, S.Sos', 'Perempuan', 'Kepala Tata Usaha', 'tendik', 'tendik123', '085370445566')
ON DUPLICATE KEY UPDATE `nama` = VALUES(`nama`);

-- 5. TABEL ABSENSI
CREATE TABLE IF NOT EXISTS `absensi` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `tanggal` DATE NOT NULL,
  `nisn_nip` VARCHAR(50) NOT NULL,
  `nama` VARCHAR(150) NOT NULL,
  `role` VARCHAR(30) DEFAULT 'siswa',
  `kelas` VARCHAR(50) DEFAULT '-',
  `jam_datang` VARCHAR(20) DEFAULT '-',
  `jam_pulang` VARCHAR(20) DEFAULT '-',
  `keterangan` VARCHAR(100) DEFAULT 'Tepat Waktu',
  `status` ENUM('Hadir', 'Sakit', 'Izin', 'Alpa', 'Belum Absen') NOT NULL DEFAULT 'Hadir',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tanggal_nisn` (`tanggal`, `nisn_nip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. TABEL HARI LIBUR
CREATE TABLE IF NOT EXISTS `hari_libur` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `tanggal` DATE NOT NULL,
  `keterangan` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tanggal` (`tanggal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed Hari Libur Initial
INSERT INTO `hari_libur` (`tanggal`, `keterangan`) VALUES
('2026-08-17', 'HUT Kemerdekaan RI'),
('2026-12-25', 'Hari Raya Natal')
ON DUPLICATE KEY UPDATE `keterangan` = VALUES(`keterangan`);

-- 7. TABEL KONFIGURASI
CREATE TABLE IF NOT EXISTS `konfigurasi` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `key_name` VARCHAR(100) NOT NULL,
  `value_name` VARCHAR(255) NOT NULL,
  `keterangan` TEXT,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `key_name` (`key_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed Konfigurasi Jam Presensi Initial
INSERT INTO `konfigurasi` (`key_name`, `value_name`, `keterangan`) VALUES
('jam_masuk_mulai', '06:30', 'Waktu absen datang dibuka'),
('jam_masuk_akhir', '07:15', 'Batas waktu terlambat'),
('jam_pulang_mulai', '15:00', 'Waktu absen pulang dibuka'),
('jam_pulang_akhir', '17:00', 'Batas akhir absen pulang')
ON DUPLICATE KEY UPDATE `value_name` = VALUES(`value_name`);

COMMIT;
