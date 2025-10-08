-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : mar. 17 juin 2025 à 12:24
-- Version du serveur : 8.3.0
-- Version de PHP : 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `academy_planning`
--

-- --------------------------------------------------------

--
-- Structure de la table `attributions`
--

DROP TABLE IF EXISTS `attributions`;
CREATE TABLE IF NOT EXISTS `attributions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `enseignant_id` int NOT NULL,
  `matiere_id` int NOT NULL,
  `statut` enum('en_attente','accepte','refuse') DEFAULT 'en_attente',
  `date_attribution` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `enseignant_id` (`enseignant_id`),
  KEY `matiere_id` (`matiere_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `cours`
--

DROP TABLE IF EXISTS `cours`;
CREATE TABLE IF NOT EXISTS `cours` (
  `id` int NOT NULL AUTO_INCREMENT,
  `matiere_id` int NOT NULL,
  `titre` varchar(255) NOT NULL,
  `description` text,
  `date` date NOT NULL,
  `heure_debut` time NOT NULL,
  `heure_fin` time NOT NULL,
  `salle_id` int NOT NULL,
  `enseignant_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `matiere_id` (`matiere_id`),
  KEY `salle_id` (`salle_id`),
  KEY `enseignant_id` (`enseignant_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `cours`
--

INSERT INTO `cours` (`id`, `matiere_id`, `titre`, `description`, `date`, `heure_debut`, `heure_fin`, `salle_id`, `enseignant_id`, `created_at`) VALUES
(1, 13, 'l\'informatique au cœur du marketing', 'chap0', '2025-06-20', '08:30:00', '12:30:00', 4, 19, '2025-06-12 01:34:45');

-- --------------------------------------------------------

--
-- Structure de la table `disponibilites`
--

DROP TABLE IF EXISTS `disponibilites`;
CREATE TABLE IF NOT EXISTS `disponibilites` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `date_dispo` date NOT NULL,
  `periode` enum('periode1','periode2','periode3') NOT NULL,
  `date_creation` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `statut` enum('en_attente','approuve','rejete') NOT NULL DEFAULT 'en_attente',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=99 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `disponibilites`
--

INSERT INTO `disponibilites` (`id`, `user_id`, `date_dispo`, `periode`, `date_creation`, `statut`) VALUES
(1, 3, '2025-06-06', 'periode1', '2025-06-05 23:51:57', 'en_attente'),
(2, 3, '2025-06-06', 'periode2', '2025-06-05 23:51:57', 'en_attente'),
(10, 3, '2025-06-07', 'periode1', '2025-06-06 00:46:49', 'rejete'),
(11, 3, '2025-06-07', 'periode2', '2025-06-06 00:46:49', 'approuve'),
(13, 3, '2025-06-09', 'periode1', '2025-06-06 15:48:24', 'approuve'),
(14, 3, '2025-06-09', 'periode3', '2025-06-06 15:48:24', 'approuve'),
(15, 11, '2025-06-09', 'periode1', '2025-06-06 15:49:48', 'approuve'),
(16, 11, '2025-06-09', 'periode2', '2025-06-06 15:49:48', 'approuve'),
(19, 11, '2025-06-11', 'periode2', '2025-06-06 17:14:54', 'rejete'),
(20, 11, '2025-06-11', 'periode3', '2025-06-06 17:14:54', 'approuve'),
(21, 11, '2025-06-08', 'periode1', '2025-06-06 17:29:31', 'approuve'),
(22, 11, '2025-06-08', 'periode2', '2025-06-06 17:29:31', 'approuve'),
(23, 11, '2025-06-10', 'periode2', '2025-06-06 18:01:03', 'approuve'),
(24, 11, '2025-06-12', 'periode1', '2025-06-06 18:43:16', 'approuve'),
(25, 11, '2025-06-12', 'periode3', '2025-06-06 18:43:16', 'approuve'),
(26, 19, '2025-06-10', 'periode1', '2025-06-07 00:13:46', 'approuve'),
(27, 19, '2025-06-10', 'periode2', '2025-06-07 00:13:46', 'approuve'),
(28, 19, '2025-06-11', 'periode2', '2025-06-07 00:13:53', 'approuve'),
(29, 19, '2025-06-11', 'periode3', '2025-06-07 00:13:53', 'approuve'),
(31, 19, '2025-06-09', 'periode1', '2025-06-07 00:14:08', 'approuve'),
(32, 19, '2025-06-09', 'periode2', '2025-06-07 00:14:08', 'approuve'),
(35, 20, '2025-06-09', 'periode1', '2025-06-07 01:32:12', 'en_attente'),
(36, 20, '2025-06-09', 'periode2', '2025-06-07 01:32:12', 'en_attente'),
(37, 20, '2025-06-10', 'periode1', '2025-06-07 01:32:21', 'approuve'),
(38, 20, '2025-06-10', 'periode2', '2025-06-07 01:32:21', 'approuve'),
(39, 20, '2025-06-11', 'periode1', '2025-06-07 01:32:30', 'approuve'),
(40, 20, '2025-06-11', 'periode2', '2025-06-07 01:32:30', 'approuve'),
(41, 20, '2025-06-12', 'periode2', '2025-06-07 01:32:38', 'rejete'),
(42, 20, '2025-06-13', 'periode1', '2025-06-07 01:32:47', 'approuve'),
(43, 20, '2025-06-13', 'periode2', '2025-06-07 01:32:47', 'approuve'),
(44, 17, '2025-06-09', 'periode1', '2025-06-07 09:04:25', 'rejete'),
(45, 17, '2025-06-09', 'periode2', '2025-06-07 09:04:25', 'approuve'),
(46, 17, '2025-06-10', 'periode1', '2025-06-07 09:04:34', 'approuve'),
(47, 17, '2025-06-10', 'periode2', '2025-06-07 09:04:34', 'approuve'),
(48, 17, '2025-06-11', 'periode1', '2025-06-07 09:04:59', 'approuve'),
(49, 17, '2025-06-11', 'periode2', '2025-06-07 09:04:59', 'approuve'),
(50, 17, '2025-06-12', 'periode2', '2025-06-07 09:05:04', 'rejete'),
(51, 17, '2025-06-13', 'periode1', '2025-06-07 09:05:11', 'approuve'),
(52, 17, '2025-06-13', 'periode2', '2025-06-07 09:05:11', 'approuve'),
(53, 21, '2025-06-09', 'periode1', '2025-06-07 09:20:40', 'approuve'),
(54, 21, '2025-06-09', 'periode2', '2025-06-07 09:20:40', 'approuve'),
(55, 21, '2025-06-10', 'periode1', '2025-06-07 09:20:47', 'rejete'),
(56, 21, '2025-06-10', 'periode2', '2025-06-07 09:20:47', 'approuve'),
(57, 21, '2025-06-11', 'periode2', '2025-06-07 09:20:53', 'rejete'),
(58, 21, '2025-06-12', 'periode1', '2025-06-07 09:21:01', 'approuve'),
(59, 21, '2025-06-12', 'periode2', '2025-06-07 09:21:01', 'approuve'),
(60, 21, '2025-06-12', 'periode3', '2025-06-07 09:21:01', 'approuve'),
(61, 21, '2025-06-13', 'periode1', '2025-06-07 09:21:07', 'approuve'),
(62, 21, '2025-06-13', 'periode2', '2025-06-07 09:21:07', 'approuve'),
(63, 10, '2025-06-09', 'periode1', '2025-06-07 09:35:33', 'rejete'),
(64, 10, '2025-06-10', 'periode1', '2025-06-07 09:35:45', 'approuve'),
(65, 10, '2025-06-10', 'periode2', '2025-06-07 09:35:45', 'rejete'),
(66, 10, '2025-06-11', 'periode1', '2025-06-07 09:36:35', 'approuve'),
(67, 10, '2025-06-11', 'periode2', '2025-06-07 09:36:35', 'approuve'),
(68, 23, '2025-06-09', 'periode1', '2025-06-07 09:55:29', 'approuve'),
(69, 23, '2025-06-09', 'periode2', '2025-06-07 09:55:29', 'approuve'),
(70, 23, '2025-06-10', 'periode1', '2025-06-07 09:55:36', 'approuve'),
(71, 23, '2025-06-10', 'periode2', '2025-06-07 09:55:36', 'approuve'),
(72, 23, '2025-06-11', 'periode2', '2025-06-07 09:55:46', 'approuve'),
(77, 19, '2025-06-13', 'periode2', '2025-06-07 23:12:43', 'rejete'),
(78, 19, '2025-06-12', 'periode2', '2025-06-07 23:13:55', 'approuve'),
(81, 28, '2025-06-11', 'periode1', '2025-06-10 08:55:07', 'rejete'),
(82, 28, '2025-06-11', 'periode2', '2025-06-10 08:55:07', 'approuve'),
(83, 28, '2025-06-12', 'periode2', '2025-06-10 08:55:16', 'approuve'),
(84, 28, '2025-06-13', 'periode1', '2025-06-10 08:55:22', 'approuve'),
(85, 28, '2025-06-13', 'periode2', '2025-06-10 08:55:22', 'approuve'),
(86, 19, '2025-06-16', 'periode1', '2025-06-12 01:29:35', 'approuve'),
(87, 19, '2025-06-17', 'periode1', '2025-06-12 19:45:30', 'approuve'),
(88, 19, '2025-06-17', 'periode2', '2025-06-12 19:45:30', 'en_attente'),
(89, 19, '2025-06-18', 'periode1', '2025-06-12 19:45:56', 'en_attente'),
(90, 19, '2025-06-18', 'periode2', '2025-06-12 19:45:56', 'approuve'),
(91, 19, '2025-06-19', 'periode1', '2025-06-12 19:46:02', 'approuve'),
(92, 19, '2025-06-19', 'periode2', '2025-06-12 19:46:02', 'rejete'),
(93, 19, '2025-06-20', 'periode1', '2025-06-12 21:54:10', 'approuve'),
(94, 19, '2025-06-20', 'periode2', '2025-06-12 21:54:10', 'rejete'),
(95, 19, '2025-06-23', 'periode2', '2025-06-12 21:54:25', 'approuve'),
(96, 19, '2025-06-24', 'periode1', '2025-06-13 19:19:49', 'approuve'),
(97, 19, '2025-06-24', 'periode2', '2025-06-13 19:19:49', 'rejete'),
(98, 19, '2025-06-25', 'periode2', '2025-06-13 19:20:10', 'approuve');

--
-- Déclencheurs `disponibilites`
--
DROP TRIGGER IF EXISTS `after_disponibilite_approbation`;
DELIMITER $$
CREATE TRIGGER `after_disponibilite_approbation` AFTER UPDATE ON `disponibilites` FOR EACH ROW BEGIN
    IF NEW.statut = 'approuve' AND OLD.statut != 'approuve' THEN
        INSERT INTO disponibilites_approuvees (
            disponibilite_id,
            user_id,
            date_dispo,
            periode,
            matiere_id,
            level_id,
            date_approbation,
            approbateur_id
        )
        SELECT 
            NEW.id,
            NEW.user_id,
            NEW.date_dispo,
            NEW.periode,
            ts.matiere_id,
            m.level_id,
            NOW(),
            (SELECT id FROM users WHERE role = 'admin' LIMIT 1)
        FROM teacher_subjects ts
        JOIN matieres m ON ts.matiere_id = m.id
        WHERE ts.user_id = NEW.user_id
        LIMIT 1;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `disponibilites_approuvees`
--

DROP TABLE IF EXISTS `disponibilites_approuvees`;
CREATE TABLE IF NOT EXISTS `disponibilites_approuvees` (
  `id` int NOT NULL AUTO_INCREMENT,
  `disponibilite_id` int NOT NULL,
  `user_id` int NOT NULL,
  `date_dispo` date NOT NULL,
  `periode` enum('periode1','periode2','periode3') COLLATE utf8mb4_unicode_ci NOT NULL,
  `matiere_id` int NOT NULL,
  `level_id` int NOT NULL,
  `date_approbation` datetime NOT NULL,
  `approbateur_id` int NOT NULL,
  `statut` enum('confirmee','annulee') COLLATE utf8mb4_unicode_ci DEFAULT 'confirmee',
  `commentaire` text COLLATE utf8mb4_unicode_ci,
  `date_creation` datetime DEFAULT CURRENT_TIMESTAMP,
  `date_modification` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `disponibilite_id` (`disponibilite_id`),
  KEY `matiere_id` (`matiere_id`),
  KEY `level_id` (`level_id`),
  KEY `approbateur_id` (`approbateur_id`),
  KEY `idx_date_dispo` (`date_dispo`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_statut` (`statut`)
) ENGINE=InnoDB AUTO_INCREMENT=63 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `disponibilites_approuvees`
--

INSERT INTO `disponibilites_approuvees` (`id`, `disponibilite_id`, `user_id`, `date_dispo`, `periode`, `matiere_id`, `level_id`, `date_approbation`, `approbateur_id`, `statut`, `commentaire`, `date_creation`, `date_modification`) VALUES
(8, 20, 11, '2025-06-11', 'periode3', 1, 1, '2025-06-06 19:42:01', 1, 'confirmee', NULL, '2025-06-06 19:42:01', '2025-06-06 19:42:01'),
(9, 15, 11, '2025-06-09', 'periode1', 1, 1, '2025-06-06 19:44:19', 1, 'confirmee', NULL, '2025-06-06 19:44:19', '2025-06-06 19:44:19'),
(10, 24, 11, '2025-06-12', 'periode1', 1, 1, '2025-06-07 01:11:45', 1, 'confirmee', NULL, '2025-06-07 01:11:45', '2025-06-07 01:11:45'),
(11, 25, 11, '2025-06-12', 'periode3', 1, 1, '2025-06-07 01:11:52', 1, 'confirmee', NULL, '2025-06-07 01:11:52', '2025-06-07 01:11:52'),
(12, 23, 11, '2025-06-10', 'periode2', 1, 1, '2025-06-07 01:11:55', 1, 'confirmee', NULL, '2025-06-07 01:11:55', '2025-06-07 01:11:55'),
(15, 28, 19, '2025-06-11', 'periode2', 11, 2, '2025-06-07 01:15:48', 1, 'annulee', NULL, '2025-06-07 01:15:48', '2025-06-07 01:51:58'),
(17, 29, 19, '2025-06-11', 'periode3', 11, 2, '2025-06-07 01:15:54', 1, 'annulee', NULL, '2025-06-07 01:15:54', '2025-06-07 01:51:58'),
(18, 27, 19, '2025-06-10', 'periode2', 11, 2, '2025-06-07 01:15:58', 1, 'annulee', NULL, '2025-06-07 01:15:58', '2025-06-07 01:51:58'),
(19, 32, 19, '2025-06-09', 'periode2', 11, 2, '2025-06-07 01:16:02', 1, 'annulee', NULL, '2025-06-07 01:16:02', '2025-06-07 01:51:58'),
(20, 26, 19, '2025-06-10', 'periode1', 11, 2, '2025-06-07 01:53:21', 1, 'annulee', NULL, '2025-06-07 01:53:21', '2025-06-07 11:42:29'),
(21, 31, 19, '2025-06-09', 'periode1', 11, 2, '2025-06-07 01:53:24', 1, 'annulee', NULL, '2025-06-07 01:53:24', '2025-06-07 11:42:29'),
(22, 16, 11, '2025-06-09', 'periode2', 1, 1, '2025-06-07 01:53:59', 1, 'confirmee', NULL, '2025-06-07 01:53:59', '2025-06-07 01:53:59'),
(23, 21, 11, '2025-06-08', 'periode1', 1, 1, '2025-06-07 01:54:03', 1, 'confirmee', NULL, '2025-06-07 01:54:03', '2025-06-07 01:54:03'),
(24, 42, 20, '2025-06-13', 'periode1', 2, 1, '2025-06-07 02:33:36', 1, 'confirmee', NULL, '2025-06-07 02:33:36', '2025-06-07 02:33:36'),
(25, 43, 20, '2025-06-13', 'periode2', 2, 1, '2025-06-07 02:33:39', 1, 'confirmee', NULL, '2025-06-07 02:33:39', '2025-06-07 02:33:39'),
(26, 39, 20, '2025-06-11', 'periode1', 2, 1, '2025-06-07 02:33:55', 1, 'confirmee', NULL, '2025-06-07 02:33:55', '2025-06-07 02:33:55'),
(27, 40, 20, '2025-06-11', 'periode2', 2, 1, '2025-06-07 02:33:59', 1, 'confirmee', NULL, '2025-06-07 02:33:59', '2025-06-07 02:33:59'),
(28, 37, 20, '2025-06-10', 'periode1', 2, 1, '2025-06-07 02:34:01', 1, 'confirmee', NULL, '2025-06-07 02:34:01', '2025-06-07 02:34:01'),
(29, 38, 20, '2025-06-10', 'periode2', 2, 1, '2025-06-07 02:34:03', 1, 'confirmee', NULL, '2025-06-07 02:34:03', '2025-06-07 02:34:03'),
(30, 51, 17, '2025-06-13', 'periode1', 5, 2, '2025-06-07 10:05:51', 1, 'annulee', NULL, '2025-06-07 10:05:51', '2025-06-07 11:42:29'),
(31, 52, 17, '2025-06-13', 'periode2', 5, 2, '2025-06-07 10:05:55', 1, 'annulee', NULL, '2025-06-07 10:05:55', '2025-06-07 11:42:29'),
(32, 48, 17, '2025-06-11', 'periode1', 5, 2, '2025-06-07 10:05:58', 1, 'annulee', NULL, '2025-06-07 10:05:58', '2025-06-07 11:42:29'),
(33, 49, 17, '2025-06-11', 'periode2', 5, 2, '2025-06-07 10:06:01', 1, 'annulee', NULL, '2025-06-07 10:06:01', '2025-06-07 11:42:29'),
(34, 46, 17, '2025-06-10', 'periode1', 5, 2, '2025-06-07 10:06:04', 1, 'annulee', NULL, '2025-06-07 10:06:04', '2025-06-07 11:42:29'),
(35, 47, 17, '2025-06-10', 'periode2', 5, 2, '2025-06-07 10:06:15', 1, 'annulee', NULL, '2025-06-07 10:06:15', '2025-06-07 11:42:29'),
(36, 45, 17, '2025-06-09', 'periode2', 5, 2, '2025-06-07 10:12:00', 1, 'annulee', NULL, '2025-06-07 10:12:00', '2025-06-07 11:42:29'),
(37, 61, 21, '2025-06-13', 'periode1', 9, 2, '2025-06-07 10:22:32', 1, 'annulee', NULL, '2025-06-07 10:22:32', '2025-06-07 11:42:29'),
(38, 62, 21, '2025-06-13', 'periode2', 9, 2, '2025-06-07 10:22:35', 1, 'annulee', NULL, '2025-06-07 10:22:35', '2025-06-07 11:42:29'),
(39, 58, 21, '2025-06-12', 'periode1', 9, 2, '2025-06-07 10:22:40', 1, 'annulee', NULL, '2025-06-07 10:22:40', '2025-06-07 11:42:29'),
(40, 59, 21, '2025-06-12', 'periode2', 9, 2, '2025-06-07 10:22:45', 1, 'annulee', NULL, '2025-06-07 10:22:45', '2025-06-07 11:42:29'),
(41, 60, 21, '2025-06-12', 'periode3', 9, 2, '2025-06-07 10:22:48', 1, 'annulee', NULL, '2025-06-07 10:22:48', '2025-06-07 11:42:29'),
(42, 56, 21, '2025-06-10', 'periode2', 9, 2, '2025-06-07 10:23:09', 1, 'annulee', NULL, '2025-06-07 10:23:09', '2025-06-07 11:42:29'),
(43, 53, 21, '2025-06-09', 'periode1', 9, 2, '2025-06-07 10:23:13', 1, 'annulee', NULL, '2025-06-07 10:23:13', '2025-06-07 11:42:29'),
(44, 54, 21, '2025-06-09', 'periode2', 9, 2, '2025-06-07 10:23:16', 1, 'annulee', NULL, '2025-06-07 10:23:16', '2025-06-07 11:42:29'),
(45, 66, 10, '2025-06-11', 'periode1', 6, 1, '2025-06-07 10:37:19', 1, 'confirmee', NULL, '2025-06-07 10:37:19', '2025-06-07 10:37:19'),
(46, 67, 10, '2025-06-11', 'periode2', 6, 1, '2025-06-07 10:37:21', 1, 'confirmee', NULL, '2025-06-07 10:37:21', '2025-06-07 10:37:21'),
(47, 64, 10, '2025-06-10', 'periode1', 6, 1, '2025-06-07 10:37:24', 1, 'confirmee', NULL, '2025-06-07 10:37:24', '2025-06-07 10:37:24'),
(48, 78, 19, '2025-06-12', 'periode2', 11, 2, '2025-06-08 00:14:50', 1, 'confirmee', NULL, '2025-06-08 00:14:50', '2025-06-08 00:14:50'),
(49, 22, 11, '2025-06-08', 'periode2', 1, 1, '2025-06-08 21:56:30', 1, 'confirmee', NULL, '2025-06-08 21:56:30', '2025-06-08 21:56:30'),
(51, 84, 28, '2025-06-13', 'periode1', 7, 2, '2025-06-10 09:56:19', 1, 'confirmee', NULL, '2025-06-10 09:56:19', '2025-06-10 09:56:19'),
(52, 85, 28, '2025-06-13', 'periode2', 7, 2, '2025-06-10 09:56:22', 1, 'confirmee', NULL, '2025-06-10 09:56:22', '2025-06-10 09:56:22'),
(53, 83, 28, '2025-06-12', 'periode2', 7, 2, '2025-06-10 09:56:25', 1, 'confirmee', NULL, '2025-06-10 09:56:25', '2025-06-10 09:56:25'),
(54, 82, 28, '2025-06-11', 'periode2', 7, 2, '2025-06-10 09:56:28', 1, 'confirmee', NULL, '2025-06-10 09:56:28', '2025-06-10 09:56:28'),
(55, 86, 19, '2025-06-16', 'periode1', 11, 2, '2025-06-12 02:30:24', 1, 'confirmee', NULL, '2025-06-12 02:30:24', '2025-06-12 02:30:24'),
(56, 91, 19, '2025-06-19', 'periode1', 11, 2, '2025-06-12 20:46:37', 1, 'confirmee', NULL, '2025-06-12 20:46:37', '2025-06-12 20:46:37'),
(57, 95, 19, '2025-06-23', 'periode2', 11, 2, '2025-06-12 22:55:21', 1, 'confirmee', NULL, '2025-06-12 22:55:21', '2025-06-12 22:55:21'),
(58, 93, 19, '2025-06-20', 'periode1', 11, 2, '2025-06-12 22:55:25', 1, 'confirmee', NULL, '2025-06-12 22:55:25', '2025-06-12 22:55:25'),
(59, 90, 19, '2025-06-18', 'periode2', 11, 2, '2025-06-12 22:55:36', 1, 'confirmee', NULL, '2025-06-12 22:55:36', '2025-06-12 22:55:36'),
(60, 87, 19, '2025-06-17', 'periode1', 11, 2, '2025-06-12 22:55:40', 1, 'confirmee', NULL, '2025-06-12 22:55:40', '2025-06-12 22:55:40'),
(61, 98, 19, '2025-06-25', 'periode2', 11, 2, '2025-06-13 20:21:09', 1, 'confirmee', NULL, '2025-06-13 20:21:09', '2025-06-13 20:21:09'),
(62, 96, 19, '2025-06-24', 'periode1', 11, 2, '2025-06-13 20:21:12', 1, 'confirmee', NULL, '2025-06-13 20:21:12', '2025-06-13 20:21:12');

-- --------------------------------------------------------

--
-- Structure de la table `emplois_du_temps`
--

DROP TABLE IF EXISTS `emplois_du_temps`;
CREATE TABLE IF NOT EXISTS `emplois_du_temps` (
  `id` int NOT NULL AUTO_INCREMENT,
  `level_id` int NOT NULL,
  `matiere_id` int DEFAULT NULL,
  `enseignant_id` int DEFAULT NULL,
  `salle_id` int DEFAULT NULL,
  `jour` enum('Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi') NOT NULL,
  `heure_debut` time NOT NULL,
  `heure_fin` time NOT NULL,
  `type_periode` enum('cours','pause','tpe') NOT NULL DEFAULT 'cours',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `filiere_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_level` (`level_id`),
  KEY `fk_matiere` (`matiere_id`),
  KEY `fk_enseignant` (`enseignant_id`),
  KEY `fk_salle` (`salle_id`),
  KEY `filiere_id` (`filiere_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2942 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `emplois_du_temps`
--

INSERT INTO `emplois_du_temps` (`id`, `level_id`, `matiere_id`, `enseignant_id`, `salle_id`, `jour`, `heure_debut`, `heure_fin`, `type_periode`, `created_at`, `filiere_id`) VALUES
(2780, 1, 1, 11, 5, 'Lundi', '08:30:00', '12:30:00', 'cours', '2025-06-12 19:39:54', NULL),
(2781, 1, NULL, NULL, NULL, 'Lundi', '12:30:00', '13:30:00', 'pause', '2025-06-12 19:39:54', NULL),
(2782, 1, 1, 11, 2, 'Lundi', '13:30:00', '17:30:00', 'cours', '2025-06-12 19:39:54', NULL),
(2783, 1, 2, 20, 5, 'Mardi', '08:30:00', '12:30:00', 'cours', '2025-06-12 19:39:54', NULL),
(2784, 1, NULL, NULL, NULL, 'Mardi', '12:30:00', '13:30:00', 'pause', '2025-06-12 19:39:54', NULL),
(2785, 1, 2, 20, 5, 'Mardi', '13:30:00', '17:30:00', 'cours', '2025-06-12 19:39:54', NULL),
(2786, 1, 6, 10, 6, 'Mercredi', '08:30:00', '12:30:00', 'cours', '2025-06-12 19:39:54', NULL),
(2787, 1, NULL, NULL, NULL, 'Mercredi', '12:30:00', '13:30:00', 'pause', '2025-06-12 19:39:54', NULL),
(2788, 1, 2, 20, 3, 'Mercredi', '13:30:00', '17:30:00', 'cours', '2025-06-12 19:39:54', NULL),
(2789, 1, 1, 11, 3, 'Jeudi', '08:30:00', '12:30:00', 'cours', '2025-06-12 19:39:54', NULL),
(2790, 1, NULL, NULL, NULL, 'Jeudi', '12:30:00', '13:30:00', 'pause', '2025-06-12 19:39:54', NULL),
(2791, 1, 57, NULL, 6, 'Jeudi', '13:30:00', '17:30:00', 'cours', '2025-06-12 19:39:54', NULL),
(2792, 1, 2, 20, 6, 'Vendredi', '08:30:00', '12:30:00', 'cours', '2025-06-12 19:39:54', NULL),
(2793, 1, NULL, NULL, NULL, 'Vendredi', '12:30:00', '13:30:00', 'pause', '2025-06-12 19:39:54', NULL),
(2794, 1, 2, 20, 5, 'Vendredi', '13:30:00', '17:30:00', 'cours', '2025-06-12 19:39:54', NULL),
(2795, 1, NULL, NULL, 2, 'Samedi', '08:30:00', '12:30:00', 'cours', '2025-06-12 19:39:54', NULL),
(2796, 1, NULL, NULL, NULL, 'Samedi', '12:30:00', '13:30:00', 'pause', '2025-06-12 19:39:54', NULL),
(2797, 1, NULL, NULL, 6, 'Samedi', '13:30:00', '17:30:00', 'cours', '2025-06-12 19:39:54', NULL),
(2924, 2, 11, 19, 6, 'Lundi', '08:30:00', '12:30:00', 'cours', '2025-06-16 10:15:41', NULL),
(2925, 2, NULL, NULL, NULL, 'Lundi', '12:30:00', '13:30:00', 'pause', '2025-06-16 10:15:41', NULL),
(2926, 2, 11, 19, 6, 'Lundi', '13:30:00', '17:30:00', 'cours', '2025-06-16 10:15:41', NULL),
(2927, 2, 11, 19, 1, 'Mardi', '08:30:00', '12:30:00', 'cours', '2025-06-16 10:15:41', NULL),
(2928, 2, NULL, NULL, NULL, 'Mardi', '12:30:00', '13:30:00', 'pause', '2025-06-16 10:15:41', NULL),
(2929, 2, 59, NULL, 3, 'Mardi', '13:30:00', '17:30:00', 'cours', '2025-06-16 10:15:41', NULL),
(2930, 2, 59, NULL, 5, 'Mercredi', '08:30:00', '12:30:00', 'cours', '2025-06-16 10:15:41', NULL),
(2931, 2, NULL, NULL, NULL, 'Mercredi', '12:30:00', '13:30:00', 'pause', '2025-06-16 10:15:41', NULL),
(2932, 2, 11, 19, 3, 'Mercredi', '13:30:00', '17:30:00', 'cours', '2025-06-16 10:15:41', NULL),
(2933, 2, 11, 19, 5, 'Jeudi', '08:30:00', '12:30:00', 'cours', '2025-06-16 10:15:41', NULL),
(2934, 2, NULL, NULL, NULL, 'Jeudi', '12:30:00', '13:30:00', 'pause', '2025-06-16 10:15:41', NULL),
(2935, 2, 11, 19, 6, 'Jeudi', '13:30:00', '17:30:00', 'cours', '2025-06-16 10:15:41', NULL),
(2936, 2, 7, 28, 5, 'Vendredi', '08:30:00', '12:30:00', 'cours', '2025-06-16 10:15:41', NULL),
(2937, 2, NULL, NULL, NULL, 'Vendredi', '12:30:00', '13:30:00', 'pause', '2025-06-16 10:15:41', NULL),
(2938, 2, 7, 28, 1, 'Vendredi', '13:30:00', '17:30:00', 'cours', '2025-06-16 10:15:41', NULL),
(2939, 2, NULL, NULL, 3, 'Samedi', '08:30:00', '12:30:00', 'cours', '2025-06-16 10:15:41', NULL),
(2940, 2, NULL, NULL, NULL, 'Samedi', '12:30:00', '13:30:00', 'pause', '2025-06-16 10:15:41', NULL),
(2941, 2, NULL, NULL, 2, 'Samedi', '13:30:00', '17:30:00', 'cours', '2025-06-16 10:15:41', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `enseignant_filiere_matiere`
--

DROP TABLE IF EXISTS `enseignant_filiere_matiere`;
CREATE TABLE IF NOT EXISTS `enseignant_filiere_matiere` (
  `id` int NOT NULL AUTO_INCREMENT,
  `enseignant_id` int NOT NULL,
  `filiere_id` int NOT NULL,
  `matiere_id` int NOT NULL,
  `type_formation` enum('jour','soir') NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_enseignant_filiere_matiere_type` (`enseignant_id`,`filiere_id`,`matiere_id`,`type_formation`),
  KEY `filiere_id` (`filiere_id`),
  KEY `matiere_id` (`matiere_id`),
  KEY `idx_enseignant_filiere` (`enseignant_id`,`filiere_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `filieres`
--

DROP TABLE IF EXISTS `filieres`;
CREATE TABLE IF NOT EXISTS `filieres` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `description` text,
  `type_formation` enum('jour','soir') NOT NULL,
  `niveau_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `niveau_id` (`niveau_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `filieres`
--

INSERT INTO `filieres` (`id`, `nom`, `description`, `type_formation`, `niveau_id`, `created_at`) VALUES
(1, 'MARKETING-COMMUNICATION', 'Formation en Marketing et Communication', 'jour', 3, '2025-06-14 10:33:19'),
(2, 'RESSOURCES HUMAINES', 'Formation en Gestion des Ressources Humaines', 'jour', 3, '2025-06-14 10:33:19'),
(3, 'COMMERCE INTERNATIONAL', 'Formation en Commerce International', 'jour', 3, '2025-06-14 10:33:19'),
(4, 'MARKETING-COMMUNICATION', 'Formation en Marketing et Communication (Soir)', 'soir', 3, '2025-06-14 10:33:19'),
(5, 'RESSOURCES HUMAINES', 'Formation en Gestion des Ressources Humaines (Soir)', 'soir', 3, '2025-06-14 10:33:19'),
(6, 'COMMERCE INTERNATIONAL', 'Formation en Commerce International (Soir)', 'soir', 3, '2025-06-14 10:33:19');

-- --------------------------------------------------------

--
-- Structure de la table `filiere_matieres`
--

DROP TABLE IF EXISTS `filiere_matieres`;
CREATE TABLE IF NOT EXISTS `filiere_matieres` (
  `id` int NOT NULL AUTO_INCREMENT,
  `filiere_id` int NOT NULL,
  `matiere_id` int NOT NULL,
  `nombre_heures` int NOT NULL,
  `periode` enum('periode1','periode2','periode3') NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_filiere_matiere` (`filiere_id`,`matiere_id`,`periode`),
  KEY `matiere_id` (`matiere_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `levels`
--

DROP TABLE IF EXISTS `levels`;
CREATE TABLE IF NOT EXISTS `levels` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `has_filieres` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `levels`
--

INSERT INTO `levels` (`id`, `name`, `has_filieres`) VALUES
(1, 'Niveau 1', 0),
(2, 'Niveau 2', 0),
(3, 'Licence', 1);

-- --------------------------------------------------------

--
-- Structure de la table `matieres`
--

DROP TABLE IF EXISTS `matieres`;
CREATE TABLE IF NOT EXISTS `matieres` (
  `id` int NOT NULL AUTO_INCREMENT,
  `code` varchar(20) NOT NULL,
  `libelle` varchar(100) NOT NULL,
  `description` text,
  `nombre_heures` int DEFAULT '0',
  `level_id` int DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code_level` (`code`,`level_id`),
  KEY `level_id` (`level_id`)
) ENGINE=InnoDB AUTO_INCREMENT=65 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `matieres`
--

INSERT INTO `matieres` (`id`, `code`, `libelle`, `description`, `nombre_heures`, `level_id`, `date_creation`) VALUES
(1, 'S1001', 'introduction au management', 'aucune et prompt', 30, 1, '2025-06-05 11:48:34'),
(2, 'S1002', 'Principe fondamentaux du maketing', '', 30, 1, '2025-06-05 11:50:06'),
(3, 'S1003', 'Comptabilite general', 'les étudiants doivent doivent faire une activité pratique', 30, 1, '2025-06-05 11:55:57'),
(4, 'S1004', 'Economie generale', 'gerer une tontine interne', 30, 1, '2025-06-05 11:57:37'),
(5, 'S2001', 'gestion des ressources humaines', 'exigence d\'établir une planification de ces économies ', 30, 2, '2025-06-05 12:03:29'),
(6, 'S1005', 'methodologie de travail universitaire', 'petit emploi de temps fait par chaque apprenant', 20, 1, '2025-06-05 12:22:21'),
(7, 'S2002', 'marketing digitale', 'aucune', 30, 2, '2025-06-05 16:04:31'),
(8, 'S1006', 'anglais professionnel ', 'les étudiants doivent communiquer en anglais pendant le cours ', 20, 1, '2025-06-06 21:08:22'),
(9, 'S2003', 'contrôle de gestion', 'aucune', 30, 2, '2025-06-06 21:13:45'),
(10, 'S2004', 'droit du travail', 'avoir un esprits de défendre ces droit ', 30, 2, '2025-06-06 21:18:52'),
(11, 'S2005', 'outil bureautique avancé', 'aucune', 20, 2, '2025-06-06 21:33:16'),
(12, 'S006', 'anglais des affaires', 'aucune', 20, 2, '2025-06-06 21:36:36'),
(13, 'S007', 'strategie d\'entreprise', 'obliger les étudiants de venir a l\' heure', 30, 2, '2025-06-06 21:42:52'),
(14, 'S008', 'finance d\'entreprise', 'aucune', 30, 2, '2025-06-06 21:46:58'),
(57, 'TPE1', 'Travaux Personnel de l\'Etudiant', 'aucune', 1, 1, '2025-06-08 22:26:57'),
(58, 'PRJ1', 'Projet', 'aucune', 1, 1, '2025-06-08 22:26:57'),
(59, 'TPE2', 'Travaux Personnel de l\'Etudiant', 'aucune', 1, 2, '2025-06-08 22:26:57'),
(60, 'PRJ2', 'Projet', 'aucune', 1, 2, '2025-06-08 22:26:57'),
(62, 'S001G', 'marketing stratégique et esthétique', 'le dynamisme dans la gestion \r\n', 40, 3, '2025-06-14 22:45:51'),
(63, 'S002G', 'Création de valeur et performance', 'aucune', 48, 3, '2025-06-14 22:48:03'),
(64, 'S003G', 'culture d\'entreprise et RSE', '', 40, 3, '2025-06-14 22:49:20');

-- --------------------------------------------------------

--
-- Structure de la table `matieres_filieres`
--

DROP TABLE IF EXISTS `matieres_filieres`;
CREATE TABLE IF NOT EXISTS `matieres_filieres` (
  `id` int NOT NULL AUTO_INCREMENT,
  `matiere_id` int NOT NULL,
  `filiere_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_matiere_filiere` (`matiere_id`,`filiere_id`),
  KEY `filiere_id` (`filiere_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `planning`
--

DROP TABLE IF EXISTS `planning`;
CREATE TABLE IF NOT EXISTS `planning` (
  `id` int NOT NULL AUTO_INCREMENT,
  `date_creation` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `planning_details`
--

DROP TABLE IF EXISTS `planning_details`;
CREATE TABLE IF NOT EXISTS `planning_details` (
  `id` int NOT NULL AUTO_INCREMENT,
  `planning_id` int NOT NULL,
  `jour` enum('Lundi','Mardi','Mercredi','Jeudi','Vendredi') NOT NULL,
  `heure` time NOT NULL,
  `matiere_id` int NOT NULL,
  `enseignant_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `planning_id` (`planning_id`),
  KEY `matiere_id` (`matiere_id`),
  KEY `enseignant_id` (`enseignant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `salles`
--

DROP TABLE IF EXISTS `salles`;
CREATE TABLE IF NOT EXISTS `salles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(50) NOT NULL,
  `capacite` int NOT NULL,
  `type` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `salles`
--

INSERT INTO `salles` (`id`, `nom`, `capacite`, `type`, `created_at`) VALUES
(1, 'patheson', 200, 'Salle de cours', '2025-06-07 00:38:34'),
(2, 'ndjiki', 50, 'Salle de cours', '2025-06-07 00:39:08'),
(3, 'coriale', 90, 'Salle de cours', '2025-06-07 00:39:45'),
(4, 'royaume', 500, 'Amphithéâtre', '2025-06-07 00:40:18'),
(5, 'spino', 120, 'Salle de cours', '2025-06-07 00:40:51'),
(6, 'salle c', 100, 'Salle de cours', '2025-06-07 09:41:08'),
(7, 'salle yellow', 100, 'Salle de réunion', '2025-06-07 09:42:08'),
(8, 'labo', 150, 'Laboratoire', '2025-06-12 09:03:09');

-- --------------------------------------------------------

--
-- Structure de la table `schedule`
--

DROP TABLE IF EXISTS `schedule`;
CREATE TABLE IF NOT EXISTS `schedule` (
  `id` int NOT NULL AUTO_INCREMENT,
  `level_id` int NOT NULL,
  `user_id` int NOT NULL,
  `matiere_id` int NOT NULL,
  `day_of_week` int NOT NULL COMMENT '0=Lundi, 1=Mardi, etc.',
  `time_slot` varchar(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `level_id` (`level_id`),
  KEY `user_id` (`user_id`),
  KEY `matiere_id` (`matiere_id`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `schedule`
--

INSERT INTO `schedule` (`id`, `level_id`, `user_id`, `matiere_id`, `day_of_week`, `time_slot`, `created_at`, `updated_at`) VALUES
(5, 2, 28, 12, 2, 'periode2', '2025-06-11 19:05:50', '2025-06-11 19:05:50'),
(14, 2, 19, 13, 0, 'periode1', '2025-06-11 19:26:08', '2025-06-11 19:26:08'),
(15, 2, 19, 13, 0, 'periode1', '2025-06-11 19:31:40', '2025-06-11 19:31:40'),
(16, 2, 28, 12, 2, 'periode2', '2025-06-11 19:31:50', '2025-06-11 19:31:50'),
(17, 2, 19, 13, 0, 'periode1', '2025-06-11 20:05:23', '2025-06-11 20:05:23'),
(18, 1, 20, 8, 4, 'periode2', '2025-06-12 08:59:35', '2025-06-12 08:59:35'),
(19, 1, 11, 4, 3, 'periode1', '2025-06-12 15:40:41', '2025-06-12 15:40:41'),
(20, 1, 11, 4, 0, 'periode2', '2025-06-12 15:41:09', '2025-06-12 15:41:09'),
(21, 1, 11, 4, 0, 'periode1', '2025-06-12 19:41:09', '2025-06-12 19:41:09'),
(22, 2, 20, 2, 5, 'periode1', '2025-06-14 14:05:56', '2025-06-14 14:05:56'),
(23, 2, 20, 2, 2, 'periode1', '2025-06-14 21:53:21', '2025-06-14 21:53:21');

-- --------------------------------------------------------

--
-- Structure de la table `specializations`
--

DROP TABLE IF EXISTS `specializations`;
CREATE TABLE IF NOT EXISTS `specializations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `level_id` int NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `level_id` (`level_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `teacher_profiles`
--

DROP TABLE IF EXISTS `teacher_profiles`;
CREATE TABLE IF NOT EXISTS `teacher_profiles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `ville` varchar(100) DEFAULT NULL,
  `quartier` varchar(100) DEFAULT NULL,
  `dernier_diplome` varchar(100) DEFAULT NULL,
  `statut_matrimonial` varchar(50) DEFAULT NULL,
  `nombre_enfants` int DEFAULT '0',
  `langues_parlees` text,
  `langues_ecrites` text,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `date_soumission` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `teacher_profiles`
--

INSERT INTO `teacher_profiles` (`id`, `user_id`, `ville`, `quartier`, `dernier_diplome`, `statut_matrimonial`, `nombre_enfants`, `langues_parlees`, `langues_ecrites`, `status`, `date_soumission`) VALUES
(1, 3, 'doualla', 'bonaberie', 'marters', 'marie', 4, 'francais,anglais', 'francais', 'approved', '2025-06-05 13:15:43'),
(2, 15, 'yaounde', 'oyack', 'ingenieurs', 'marie', 3, 'francais,anglais', 'francais', 'approved', '2025-06-05 14:46:49'),
(3, 17, 'nord', 'aouda', 'licence en marketing', 'celibataire', 0, 'francais,anglais', 'anglais', 'approved', '2025-06-05 18:39:52'),
(4, 19, 'doualla', 'bonaberie', 'marters en droit', 'celibataire', 0, 'francais,anglais', 'francais,anglais', 'approved', '2025-06-06 22:38:57'),
(5, 20, 'doualla', 'bonanoussadi', 'marters en finance', 'marie', 45, 'francais,anglais', 'francais,anglais', 'approved', '2025-06-07 01:27:29'),
(6, 21, 'douala', 'village(borne10)', 'marsters en bussiness', 'celibataire', 0, 'francais,anglais', 'francais,', 'approved', '2025-06-07 09:17:22'),
(7, 10, 'douala', 'new bell', 'doctorat en gestion', 'marie', 0, 'francais,anglais', 'anglais,francais', 'approved', '2025-06-07 09:34:02'),
(8, 23, 'doualla', 'bonaberie', 'marters en droit', 'celibataire', 0, 'francais,anglais', 'francais,anglais', 'approved', '2025-06-07 09:54:22'),
(10, 25, 'doualla', 'bonaberie', 'marters en droit', 'divorce', 0, 'francais,anglais', 'francais,anglais', 'rejected', '2025-06-08 02:27:15'),
(11, 26, 'doualla', 'bonaberie', 'marters en droit', '', 0, 'francais,anglais', 'francais,anglais', 'pending', '2025-06-08 08:52:21'),
(12, 27, 'doualla', 'bonaberie', 'marters en droit', 'celibataire', 0, 'francais,anglais', 'francais', 'approved', '2025-06-09 13:41:41'),
(13, 28, 'bertoua', 'oballa', 'marsters en economie', 'celibataire', 2, 'francais,anglais', 'francais,anglais', 'approved', '2025-06-10 08:52:39'),
(14, 30, 'bertoua', 'oballa', 'marsters en economie', 'celibataire', 0, 'francais,anglais', 'francais,anglais', 'rejected', '2025-06-11 08:38:43'),
(15, 31, 'douala', 'bonanoussadi', 'doctorat en physique', 'celibataire', 0, 'francais,anglais', 'francais,anglais', 'pending', '2025-06-12 14:20:31');

-- --------------------------------------------------------

--
-- Structure de la table `teacher_subjects`
--

DROP TABLE IF EXISTS `teacher_subjects`;
CREATE TABLE IF NOT EXISTS `teacher_subjects` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `matiere_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_teacher_subject` (`user_id`,`matiere_id`),
  KEY `matiere_id` (`matiere_id`)
) ENGINE=InnoDB AUTO_INCREMENT=87 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `teacher_subjects`
--

INSERT INTO `teacher_subjects` (`id`, `user_id`, `matiere_id`, `created_at`) VALUES
(24, 10, 6, '2025-06-06 15:54:40'),
(30, 19, 11, '2025-06-06 22:02:38'),
(31, 19, 13, '2025-06-06 22:02:38'),
(62, 17, 14, '2025-06-07 02:22:25'),
(63, 17, 5, '2025-06-07 02:22:25'),
(75, 21, 9, '2025-06-07 09:18:29'),
(76, 21, 10, '2025-06-07 09:18:29'),
(77, 11, 4, '2025-06-10 08:47:54'),
(78, 11, 1, '2025-06-10 08:47:54'),
(80, 25, 3, '2025-06-10 08:48:21'),
(83, 28, 12, '2025-06-10 09:16:49'),
(84, 28, 7, '2025-06-10 09:16:49'),
(85, 20, 2, '2025-06-12 09:05:02');

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(50) NOT NULL,
  `prenom` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','enseignant','etudiant') NOT NULL,
  `level_id` int DEFAULT NULL,
  `specialization_id` int DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `fk_users_level` (`level_id`),
  KEY `fk_users_specialization` (`specialization_id`)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`id`, `nom`, `prenom`, `email`, `password`, `role`, `level_id`, `specialization_id`, `date_creation`) VALUES
(1, 'Admin', 'System', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', NULL, NULL, '2025-06-04 13:23:45'),
(2, 'Dupont', 'Jean', 'enseignant@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'enseignant', NULL, NULL, '2025-06-04 13:57:06'),
(3, 'nanda', 'wilfried', 'admin@exemple.com', '$2y$10$qcHv9o7RktanN5ks5SGy3.g604lM2jhBEWjJyQ5V0uKWNE2L.70c6', 'enseignant', NULL, NULL, '2025-06-04 13:59:01'),
(5, 'Nouveau', 'Admin', 'nouvel.admin@example.com', 'LE_HASH_ICI', 'admin', NULL, NULL, '2025-06-05 09:35:16'),
(7, 'Nouvel', 'Admin', 'ma.admin@example.com', '$2y$10$Val..bVHkVZ.5ieyB9NhMuWhmpOhRCLJsbpeM2xp6Ot1OmmOhK/ni', 'admin', NULL, NULL, '2025-06-05 09:43:49'),
(8, 'Nouvel', 'Admin', 'mesagogeradine@gmail.com', '$2y$10$nmXly42lxIZ5Q2H.aL2IiOhkeHBCOOywNT4znuUQV2WlEbNh4cQDW', 'admin', NULL, NULL, '2025-06-05 09:49:25'),
(10, 'nassrine', 'hamadou', 'sadjohamadounassrine@gmail.com', '$2y$10$yHVq0xv.kLHSYGXz4Q.LK.NgZDmUhwAL777pTF4CnfZdyYuPo5jTO', 'enseignant', 1, NULL, '2025-06-05 12:12:24'),
(11, 'nando', 'wilfried', 'nandi@gmail.com', '$2y$10$Z00UV3EmAf5DjQHthvG8dOm3SWiJRFWv9yYwfUdg4z896g.LmHPAm', 'enseignant', 1, NULL, '2025-06-05 12:23:25'),
(13, 'tabet', 'pierre', 'tabetpierre@gmail.com', '$2y$10$T82pQ0IujogttGUxkwiO/.INBTVAaNNn9H.g4qoNzhp9xzsW/O.8O', 'etudiant', 1, NULL, '2025-06-05 12:30:21'),
(14, 'mbiadou', 'helowin', 'mbiadou@gmail.com', '$2y$10$Rdq6hyMJltefUUJBhc93oO75Thpt7okATx3xWVtO37/1s/fWKD4/G', 'etudiant', 2, NULL, '2025-06-05 12:31:28'),
(15, 'denis', 'andre', 'denis@gmail.com', '$2y$10$84Yqxn4iPwylpYCOWE9fXO3gq.Q86L.Mt/buDzgtWzYxATtzUnRJy', 'enseignant', 1, NULL, '2025-06-05 14:45:03'),
(17, 'yonga', 'fortune', 'yonga@gmail.com', '$2y$10$LQDhKaNtRAREfurPCEdFDuiW6Z6bCc.IjxmZsytNh17rzHFhnwpqG', 'enseignant', 2, NULL, '2025-06-05 18:36:50'),
(19, 'yoba', 'fortune', 'yoba@yahoo.fr', '$2y$10$VzNbUmI91EVX5mezxpK8FOXQA03fS9EApjNr14Dx1SJVmrnEqVC5q', 'enseignant', 2, NULL, '2025-06-06 21:52:15'),
(20, 'kamdem', 'rovil', 'kamdem@gmail.com', '$2y$10$GyMSYs8hhnTnMEa.jt7kP.DrnQS1xY.nNFYBQtftms8LeZ8y90t2q', 'enseignant', 1, NULL, '2025-06-07 01:12:02'),
(21, 'tagne ', 'nembot', 'tagnenembot@gmail.com', '$2y$10$l09HeGCIAZLAE/ZgklccguklQClRaVnwISVnAUnQ8YHZWQ4BGhj4y', 'enseignant', 2, NULL, '2025-06-07 09:14:56'),
(22, 'mbiadou', 'marck', 'marck@gmail.com', '$2y$10$Z9deo3PTH03l0Szo4DiZ.uj5moqLNqs7C1QTqYg5D6aDi3aTEOZAG', 'etudiant', 1, NULL, '2025-06-07 09:44:48'),
(23, 'pierre', 'below', 'pierre@gmail.com', '$2y$10$K56W2AEyvlPUEavd0j/.SulaU1mCHte2IShp4jNXzu6dE7vaqmati', 'enseignant', 1, NULL, '2025-06-07 09:53:04'),
(25, 'mario', 'onthioce', 'regone.@fr', '$2y$10$vxjZD98..1XTyVzkmYMJZOwEl0f47vuk3MKtA1kjwJ3gvzMBsj2zG', 'enseignant', 1, NULL, '2025-06-08 02:26:34'),
(26, 'kegne', 'valdes', 'kegne@gmail.com', '$2y$10$hgFHGs5Zcgo6cDa/igtyDO4/b6wH.drrXhYEN3Frub2NCgKUjClpa', 'enseignant', 2, NULL, '2025-06-08 08:51:33'),
(27, 'yum', 'emmanuel', 'yum@gmail.com', '$2y$10$uZuAwHv9veh7EbSiEh6n8eETXsnu5jNJ922iLNk3O8.lehmXCIGr6', 'enseignant', 1, NULL, '2025-06-09 13:40:42'),
(28, 'momo', 'herman', 'momo@gmail.com', '$2y$10$B6OawFabdiZZJ7WbJ3CD6.VE3nVTyB/XYlxeUY3w7uLCi26sfvIXO', 'enseignant', 2, NULL, '2025-06-10 08:50:42'),
(29, 'fotue', 'cabrel', 'fotue@gmail.com', '$2y$10$yvYPl/58isNmEKtXryUeiOdEqfODymLEpvfpePb2u0LF4Gd5.dBYi', 'etudiant', 1, NULL, '2025-06-11 05:32:47'),
(30, 'maurice', 'serge', 'maurice@gmail.com', '$2y$10$Hhee5Rnh80IHIMjOCf9j9OgOmqOc4giFhQOZNarVF7KDDLwMgDsb6', 'enseignant', 2, NULL, '2025-06-11 08:38:14'),
(31, 'geogio', 'ndom', 'geogio@gmail.com', '$2y$10$00XO.HRH35r5tR80FQCDQuM0mRW.KsT4HpS5RwvXOQ9/jdCk4fSkq', 'enseignant', 2, NULL, '2025-06-12 14:19:15'),
(32, 'foyep', 'claude', 'foyep@gmail.com', '$2y$10$az3h/jaxtxeBIQIuTgOqfuFCHjhiC5tn0lQqPA/KveuzcXNPRl7D.', 'enseignant', 3, NULL, '2025-06-14 22:46:56');

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `attributions`
--
ALTER TABLE `attributions`
  ADD CONSTRAINT `attributions_ibfk_1` FOREIGN KEY (`enseignant_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `attributions_ibfk_2` FOREIGN KEY (`matiere_id`) REFERENCES `matieres` (`id`);

--
-- Contraintes pour la table `cours`
--
ALTER TABLE `cours`
  ADD CONSTRAINT `cours_ibfk_1` FOREIGN KEY (`matiere_id`) REFERENCES `matieres` (`id`),
  ADD CONSTRAINT `cours_ibfk_2` FOREIGN KEY (`salle_id`) REFERENCES `salles` (`id`),
  ADD CONSTRAINT `cours_ibfk_3` FOREIGN KEY (`enseignant_id`) REFERENCES `users` (`id`);

--
-- Contraintes pour la table `disponibilites`
--
ALTER TABLE `disponibilites`
  ADD CONSTRAINT `disponibilites_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `disponibilites_approuvees`
--
ALTER TABLE `disponibilites_approuvees`
  ADD CONSTRAINT `disponibilites_approuvees_ibfk_1` FOREIGN KEY (`disponibilite_id`) REFERENCES `disponibilites` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `disponibilites_approuvees_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `disponibilites_approuvees_ibfk_3` FOREIGN KEY (`matiere_id`) REFERENCES `matieres` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `disponibilites_approuvees_ibfk_4` FOREIGN KEY (`level_id`) REFERENCES `levels` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `disponibilites_approuvees_ibfk_5` FOREIGN KEY (`approbateur_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `emplois_du_temps`
--
ALTER TABLE `emplois_du_temps`
  ADD CONSTRAINT `emplois_du_temps_ibfk_1` FOREIGN KEY (`filiere_id`) REFERENCES `filieres` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_enseignant` FOREIGN KEY (`enseignant_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_level` FOREIGN KEY (`level_id`) REFERENCES `levels` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_matiere` FOREIGN KEY (`matiere_id`) REFERENCES `matieres` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_salle` FOREIGN KEY (`salle_id`) REFERENCES `salles` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `enseignant_filiere_matiere`
--
ALTER TABLE `enseignant_filiere_matiere`
  ADD CONSTRAINT `enseignant_filiere_matiere_ibfk_1` FOREIGN KEY (`enseignant_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `enseignant_filiere_matiere_ibfk_2` FOREIGN KEY (`filiere_id`) REFERENCES `filieres` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `enseignant_filiere_matiere_ibfk_3` FOREIGN KEY (`matiere_id`) REFERENCES `matieres` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `filieres`
--
ALTER TABLE `filieres`
  ADD CONSTRAINT `filieres_ibfk_1` FOREIGN KEY (`niveau_id`) REFERENCES `levels` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `filiere_matieres`
--
ALTER TABLE `filiere_matieres`
  ADD CONSTRAINT `filiere_matieres_ibfk_1` FOREIGN KEY (`filiere_id`) REFERENCES `filieres` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `filiere_matieres_ibfk_2` FOREIGN KEY (`matiere_id`) REFERENCES `matieres` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `matieres`
--
ALTER TABLE `matieres`
  ADD CONSTRAINT `fk_matieres_level` FOREIGN KEY (`level_id`) REFERENCES `levels` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `matieres_ibfk_1` FOREIGN KEY (`level_id`) REFERENCES `levels` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `matieres_filieres`
--
ALTER TABLE `matieres_filieres`
  ADD CONSTRAINT `matieres_filieres_ibfk_1` FOREIGN KEY (`matiere_id`) REFERENCES `matieres` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `matieres_filieres_ibfk_2` FOREIGN KEY (`filiere_id`) REFERENCES `filieres` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `planning_details`
--
ALTER TABLE `planning_details`
  ADD CONSTRAINT `planning_details_ibfk_1` FOREIGN KEY (`planning_id`) REFERENCES `planning` (`id`),
  ADD CONSTRAINT `planning_details_ibfk_2` FOREIGN KEY (`matiere_id`) REFERENCES `matieres` (`id`),
  ADD CONSTRAINT `planning_details_ibfk_3` FOREIGN KEY (`enseignant_id`) REFERENCES `users` (`id`);

--
-- Contraintes pour la table `schedule`
--
ALTER TABLE `schedule`
  ADD CONSTRAINT `schedule_ibfk_1` FOREIGN KEY (`level_id`) REFERENCES `levels` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `schedule_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `schedule_ibfk_3` FOREIGN KEY (`matiere_id`) REFERENCES `matieres` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `specializations`
--
ALTER TABLE `specializations`
  ADD CONSTRAINT `specializations_ibfk_1` FOREIGN KEY (`level_id`) REFERENCES `levels` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `teacher_profiles`
--
ALTER TABLE `teacher_profiles`
  ADD CONSTRAINT `teacher_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `teacher_subjects`
--
ALTER TABLE `teacher_subjects`
  ADD CONSTRAINT `teacher_subjects_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `teacher_subjects_ibfk_2` FOREIGN KEY (`matiere_id`) REFERENCES `matieres` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_level` FOREIGN KEY (`level_id`) REFERENCES `levels` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_users_specialization` FOREIGN KEY (`specialization_id`) REFERENCES `specializations` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
