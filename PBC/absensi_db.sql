-- Buat database jika belum ada
CREATE DATABASE IF NOT EXISTS absensi_db;
USE absensi_db;

-- Hapus tabel jika sudah ada (biar tidak bentrok)
DROP TABLE IF EXISTS class_info;
DROP TABLE IF EXISTS students;

-- Tabel informasi kelas
CREATE TABLE class_info (
    id INT NOT NULL PRIMARY KEY,
    class_name VARCHAR(100) NOT NULL
);

-- Data default kelas
INSERT INTO class_info (id, class_name) VALUES (1, 'Pendidikan Pancasila');

-- Tabel siswa
CREATE TABLE students (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    status VARCHAR(10) NOT NULL DEFAULT 'hadir',
    note TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
