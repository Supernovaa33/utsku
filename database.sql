CREATE DATABASE IF NOT EXISTS `uts_biodata`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `uts_biodata`;

CREATE TABLE IF NOT EXISTS `biodata` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nama` VARCHAR(150) NOT NULL,
  `nim` VARCHAR(50) NOT NULL,
  `deskripsi` TEXT NOT NULL,
  `foto` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `biodata` (`nama`, `nim`, `deskripsi`, `foto`) VALUES
(
  'rizki eka prasodjo',
  '12.11.5866',
  '12.11.5866 merupakan master dari uts ini dengan tugas membuat front-end dan mengirimkannya ke grup',
  'seed-rizki.png'
),
(
  'andi agus salim',
  '12.11.6018',
  'dengan NIM 12.11.6018 merupakan pegawai dan mempunyai tugas mengerjakan back-end pada UTS ini',
  'seed-andi.png'
);
