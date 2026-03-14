-- Database: sistem_lelang_online
CREATE DATABASE IF NOT EXISTS sistem_lelang_online;
USE sistem_lelang_online;

-- Tabel tb_level (Level pengguna)
CREATE TABLE tb_level (
    id_level INT(11) PRIMARY KEY AUTO_INCREMENT,
    level VARCHAR(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO tb_level (level) VALUES 
('Administrator'),
('Petugas'),
('Masyarakat');

-- Tabel tb_user
CREATE TABLE tb_user (
    id_user INT(11) PRIMARY KEY AUTO_INCREMENT,
    nama_lengkap VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    telp VARCHAR(20),
    id_level INT(11) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_level) REFERENCES tb_level(id_level)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default users
INSERT INTO tb_user (nama_lengkap, username, password, telp, id_level) VALUES
('Administrator', 'admin', MD5('admin123'), '081234567890', 1),
('Petugas Lelang', 'petugas', MD5('petugas123'), '081234567891', 2),
('Budi Santoso', 'budi', MD5('budi123'), '081234567892', 3);

-- Tabel tb_barang
CREATE TABLE tb_barang (
    id_barang INT(11) PRIMARY KEY AUTO_INCREMENT,
    nama_barang VARCHAR(100) NOT NULL,
    tgl VARCHAR(20) NOT NULL,
    harga_awal DECIMAL(15,2) NOT NULL,
    deskripsi_barang TEXT,
    gambar VARCHAR(255),
    status_barang ENUM('pending', 'dibuka', 'ditutup') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert sample barang
INSERT INTO tb_barang (nama_barang, tgl, harga_awal, deskripsi_barang, gambar, status_barang) VALUES
('Laptop Gaming ASUS ROG', '2026-02-01', 7500000.00, 'Laptop gaming ASUS ROG dengan spesifikasi tinggi', 'laptop.jpg', 'dibuka'),
('iPhone 13 Pro 256GB', '2026-02-01', 13350000.00, 'iPhone 13 Pro warna Sierra Blue 256GB', 'iphone.jpg', 'dibuka'),
('Jaket Kulit Pria Premium', '2026-02-01', 1350000.00, 'Jaket kulit asli berkualitas premium', 'jaket.jpg', 'dibuka');

-- Tabel tb_lelang
CREATE TABLE tb_lelang (
    id_lelang INT(11) PRIMARY KEY AUTO_INCREMENT,
    id_barang INT(11) NOT NULL,
    tgl_lelang DATE NOT NULL,
    harga_akhir DECIMAL(15,2) DEFAULT 0,
    id_user INT(11),
    id_petugas INT(11),
    status ENUM('dibuka', 'ditutup') DEFAULT 'dibuka',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_barang) REFERENCES tb_barang(id_barang),
    FOREIGN KEY (id_user) REFERENCES tb_user(id_user),
    FOREIGN KEY (id_petugas) REFERENCES tb_user(id_user)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert sample lelang
INSERT INTO tb_lelang (id_barang, tgl_lelang, harga_akhir, id_petugas, status) VALUES
(1, '2026-02-01', 7500000.00, 2, 'dibuka'),
(2, '2026-02-01', 13350000.00, 2, 'dibuka'),
(3, '2026-02-01', 1350000.00, 2, 'dibuka');

-- Tabel history_lelang
CREATE TABLE history_lelang (
    id_history INT(11) PRIMARY KEY AUTO_INCREMENT,
    id_lelang INT(11) NOT NULL,
    id_barang INT(11) NOT NULL,
    id_user INT(11) NOT NULL,
    penawaran_harga DECIMAL(15,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_lelang) REFERENCES tb_lelang(id_lelang),
    FOREIGN KEY (id_barang) REFERENCES tb_barang(id_barang),
    FOREIGN KEY (id_user) REFERENCES tb_user(id_user)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel tb_pembayaran
CREATE TABLE tb_pembayaran (
    id_pembayaran INT(11) PRIMARY KEY AUTO_INCREMENT,
    id_lelang INT(11) NOT NULL,
    id_user INT(11) NOT NULL,
    jumlah DECIMAL(15,2) NOT NULL,
    metode_pembayaran VARCHAR(50),
    status_pembayaran ENUM('pending', 'dibayar', 'selesai') DEFAULT 'pending',
    bukti_pembayaran VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_lelang) REFERENCES tb_lelang(id_lelang),
    FOREIGN KEY (id_user) REFERENCES tb_user(id_user)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
