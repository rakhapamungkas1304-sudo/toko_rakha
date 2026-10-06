-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Oct 06, 2026 at 06:29 AM
-- Server version: 8.4.3
-- PHP Version: 8.3.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_toko`
--

-- --------------------------------------------------------

--
-- Table structure for table `tb_detail`
--

CREATE TABLE `tb_detail` (
  `id_detail` int NOT NULL,
  `id_transaksi` int NOT NULL,
  `id_produk` int NOT NULL,
  `jumlah` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `tb_detail`
--

INSERT INTO `tb_detail` (`id_detail`, `id_transaksi`, `id_produk`, `jumlah`) VALUES
(8, 7, 73, 2),
(9, 8, 73, 1),
(10, 8, 69, 1),
(11, 8, 74, 1),
(12, 8, 72, 1),
(13, 9, 72, 1),
(14, 10, 73, 1);

-- --------------------------------------------------------

--
-- Table structure for table `tb_kategori`
--

CREATE TABLE `tb_kategori` (
  `id_kategori` int NOT NULL,
  `nama_kategori` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `tb_kategori`
--

INSERT INTO `tb_kategori` (`id_kategori`, `nama_kategori`) VALUES
(7, 'Baju'),
(8, 'Celana');

-- --------------------------------------------------------

--
-- Table structure for table `tb_produk`
--

CREATE TABLE `tb_produk` (
  `id` int NOT NULL,
  `nama` varchar(255) NOT NULL,
  `harga` int NOT NULL,
  `stok` int NOT NULL,
  `foto` text NOT NULL,
  `id_kategori` int DEFAULT NULL,
  `deskripsi` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `tb_produk`
--

INSERT INTO `tb_produk` (`id`, `nama`, `harga`, `stok`, `foto`, `id_kategori`, `deskripsi`) VALUES
(55, 'Kemeja Flanel Pria', 150000, 35, 'pngtree-flannel-shirt-man-fashion-cloth-isolated-3d-render-illustration-png-image_15176980.png', 7, 'Kemeja flanel motif kotak-kotak, bahan katun tebal, nyaman dipakai untuk aktivitas santai maupun semi formal.'),
(56, 'Kaos Polos Cotton Combed', 75000, 60, 'gloaming-0140-7021183-1.webp', 7, 'Kaos polos bahan cotton combed 30s, adem dan menyerap keringat, cocok untuk sehari-hari.'),
(57, 'Kemeja Batik Modern', 185000, 25, '173900-1_depan_762x1100_ece46b8f-ec17-4fd3-bdad-3b67f5b74eac.webp', 7, 'Kemeja batik dengan motif modern, cocok untuk acara formal maupun kerja kantoran.'),
(58, 'Hoodie Oversize', 165000, 40, 'H469f5637dc6541d8a1fd16a3fac7810eI.avif', 7, 'Hoodie oversize bahan fleece tebal, hangat dan trendy, cocok untuk gaya kasual streetwear.'),
(59, 'Kaos Oblong Lengan Panjang', 90000, 45, 'sg-11134201-22120-1828xzm6wplv09.jpg', 7, 'Kaos lengan panjang bahan katun lembut, pas untuk cuaca dingin atau gaya berlapis.'),
(60, 'Kemeja Formal Kerja', 195000, 20, 'id-11134207-822wt-mpgbzlloznyl15.jpg', 7, 'Kemeja formal lengan panjang, bahan katun anti kusut, cocok untuk kerja kantoran.'),
(61, 'Jaket Bomber', 220000, 30, 'no_brand_brand_fashion_-_jaket_bomber_pria_terbaru-jaket_motor-jaket_kantoran-jaket_casual_pria_full04_fdr5xpiv.webp', 7, 'Jaket bomber bahan parasut water resistant, desain simpel dan modern.'),
(62, 'Kaos Polos V-Neck', 80000, 45, 'aerostreet_aerostreet_t_shirt_reguler_v_neck_polos_reguler_gelap_kaos_kavaa_full01_ime1s4sx.webp', 7, 'Kaos kerah V bahan katun combed berkualitas, nyaman dan pas untuk gaya kasual harian.'),
(63, 'Sweater Rajut Polos', 135000, 30, 'brd-69012_baju-sweater-rajut-distro-polos-pria-lengan-panjang_full01.webp', 7, 'Sweater rajut halus dan lembut, nyaman dipakai saat cuaca dingin.'),
(64, 'Blouse Kerja Wanita', 140000, 35, 'images.jpg', 7, 'Blouse wanita elegan untuk bekerja, bahan jatuh dan tidak mudah kusut.'),
(65, 'Celana Jeans Slim Fit', 175000, 35, 'images (1).jpg', 8, 'Celana jeans slim fit bahan denim stretch, nyaman dan mengikuti bentuk tubuh.'),
(66, 'Celana Chino Pria', 145000, 40, 'bc3b57fb-1aab-4bd2-a1e2-d8bacce59fd6.jpg~tplv-aphluv4xwc-resize-jpeg_700_0.jpg', 8, 'Celana chino bahan twill halus, cocok untuk gaya semi formal maupun kasual.'),
(67, 'Celana Jogger Sport', 120000, 50, 'sport-high-9847-7826562-1.webp', 8, 'Celana jogger bahan fleece dengan karet di pergelangan kaki, nyaman untuk olahraga atau santai.'),
(68, 'Celana Kargo Outdoor', 160000, 30, 'id-11134207-81ztl-meik193khv5vd3.jpg', 8, 'Celana kargo dengan banyak kantong, bahan ripstop kuat, cocok untuk aktivitas outdoor.'),
(69, 'Celana Kulot Wanita', 110000, 34, 'images (2).jpg', 8, 'Celana kulot bahan katun rayon, longgar dan adem, cocok untuk gaya kasual wanita.'),
(70, 'Celana Training Olahraga', 95000, 55, 'S5e8d312fc69c4b68ab9241a1304147c5W.jpg_720x720q80.jpg', 8, 'Celana training bahan parasut ringan, menyerap keringat, ideal untuk olahraga.'),
(71, 'Celana Formal Bahan', 180000, 25, 'ginee_20241001171715039_6507544542.jpeg', 8, 'Celana bahan formal untuk kerja kantoran, potongan rapi dan elegan.'),
(72, 'Celana Pendek Denim', 90000, 43, 'id-11134207-7r98t-lvex431ll1hf72.jpg', 8, 'Celana pendek denim casual, cocok dipakai saat cuaca panas atau jalan-jalan santai.'),
(73, 'Celana Palazzo Wanita', 130000, 21, 'images (3).jpg', 8, 'Celana palazzo panjang berukuran longgar dengan bahan yang lembut dan anggun.'),
(74, 'Celana Panjang Bahan Katun Santai', 105000, 39, 'sg-11134201-822zd-mhxkk42ef0ua5d.jpg', 8, 'Celana santai harian bahan katun ringan dengan karet pinggang elastis.');

-- --------------------------------------------------------

--
-- Table structure for table `tb_transaksi`
--

CREATE TABLE `tb_transaksi` (
  `id_transaksi` int NOT NULL,
  `id_pelanggan` int NOT NULL,
  `tanggal` date NOT NULL,
  `total_harga` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `tb_transaksi`
--

INSERT INTO `tb_transaksi` (`id_transaksi`, `id_pelanggan`, `tanggal`, `total_harga`) VALUES
(7, 11, '2026-10-06', 249000),
(8, 11, '2026-10-06', 406500),
(9, 11, '2026-10-06', 105000),
(10, 11, '2026-10-06', 138500);

-- --------------------------------------------------------

--
-- Table structure for table `tb_user`
--

CREATE TABLE `tb_user` (
  `id` int NOT NULL,
  `nama` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `hp` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `alamat` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `role` enum('admin','pelanggan') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `tb_user`
--

INSERT INTO `tb_user` (`id`, `nama`, `email`, `username`, `password`, `hp`, `alamat`, `role`) VALUES
(2, 'bagas', 'bagas@gmail.com', 'bagaas', 'apaya', '0854564765764', 'hahahahah', 'admin'),
(3, 'hahahaha', 'hahahaha', 'rakha', 'rakha', '08192971291', 'ahahha', 'admin'),
(11, 'daffa fallih ammar', 'daffa@gmail.com', 'daffa', 'daffa', '081234566677', '', 'pelanggan'),
(12, 'daffa', 'daps@gmail.com', 'daps', '1234', '', '', 'pelanggan'),
(13, 'rakha pamungkas ahyani', 'rakhapamungkas1304@gmail.com', 'rakha160810', '1234', '080987567098', 'jl suratno', 'pelanggan'),
(14, 'pamungkas', 'pamungkas1608@gmail.com', 'ahyani', 'ahyani', '085767890', 'suratno', 'pelanggan');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `tb_detail`
--
ALTER TABLE `tb_detail`
  ADD PRIMARY KEY (`id_detail`),
  ADD KEY `id_transaksi` (`id_transaksi`,`id_produk`),
  ADD KEY `id_produk` (`id_produk`);

--
-- Indexes for table `tb_kategori`
--
ALTER TABLE `tb_kategori`
  ADD PRIMARY KEY (`id_kategori`);

--
-- Indexes for table `tb_produk`
--
ALTER TABLE `tb_produk`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_kategori` (`id_kategori`);

--
-- Indexes for table `tb_transaksi`
--
ALTER TABLE `tb_transaksi`
  ADD PRIMARY KEY (`id_transaksi`),
  ADD KEY `id_pelanggan` (`id_pelanggan`);

--
-- Indexes for table `tb_user`
--
ALTER TABLE `tb_user`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `tb_detail`
--
ALTER TABLE `tb_detail`
  MODIFY `id_detail` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `tb_kategori`
--
ALTER TABLE `tb_kategori`
  MODIFY `id_kategori` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `tb_produk`
--
ALTER TABLE `tb_produk`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=75;

--
-- AUTO_INCREMENT for table `tb_transaksi`
--
ALTER TABLE `tb_transaksi`
  MODIFY `id_transaksi` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `tb_user`
--
ALTER TABLE `tb_user`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `tb_detail`
--
ALTER TABLE `tb_detail`
  ADD CONSTRAINT `tb_detail_ibfk_1` FOREIGN KEY (`id_transaksi`) REFERENCES `tb_transaksi` (`id_transaksi`),
  ADD CONSTRAINT `tb_detail_ibfk_2` FOREIGN KEY (`id_produk`) REFERENCES `tb_produk` (`id`);

--
-- Constraints for table `tb_transaksi`
--
ALTER TABLE `tb_transaksi`
  ADD CONSTRAINT `tb_transaksi_ibfk_1` FOREIGN KEY (`id_pelanggan`) REFERENCES `tb_user` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
