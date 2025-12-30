-- phpMyAdmin SQL Dump
-- version 5.2.1deb3
-- https://www.phpmyadmin.net/
--
-- Hôte : localhost:3306
-- Généré le : lun. 10 nov. 2025 à 14:06
-- Version du serveur : 8.0.43-0ubuntu0.24.04.1
-- Version de PHP : 8.3.6

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `geekboard_mkmkmk`
--

DELIMITER $$
--
-- Procédures
--
$$

$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `bug_reports`
--

CREATE TABLE `bug_reports` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `page_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `priorite` enum('basse','moyenne','haute','critique') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'basse',
  `status` enum('nouveau','en_cours','resolu','ferme') COLLATE utf8mb4_unicode_ci DEFAULT 'nouveau',
  `date_creation` datetime DEFAULT CURRENT_TIMESTAMP,
  `date_resolution` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `bug_reports`
--

INSERT INTO `bug_reports` (`id`, `user_id`, `description`, `page_url`, `user_agent`, `priorite`, `status`, `date_creation`, `date_resolution`) VALUES
(1, 6, 'je veut que dans le modal\r\nContenu du SMS\r\nla couleur du fonds du texte du SMS soit affiche en noir en mode nuit', 'https://mkmkmk.mdgeek.top/index.php?page=sms_historique', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'basse', 'nouveau', '2025-09-27 23:21:18', NULL),
(2, 6, 'Dans l\'etape 2/4 je veut Remplacer le bouton supprimer client par un bouton modifier qui affichera un popup pour modifier le client', 'https://mkmkmk.servo.tools/index.php?page=ajouter_reparation', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'basse', 'nouveau', '2025-10-01 03:58:44', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `cagnotte_historique`
--

CREATE TABLE `cagnotte_historique` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `montant` decimal(10,2) NOT NULL,
  `type` enum('credit','debit') NOT NULL,
  `description` text,
  `mission_id` int DEFAULT NULL,
  `admin_id` int DEFAULT NULL,
  `date_creation` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `calculator_settings`
--

CREATE TABLE `calculator_settings` (
  `id` int NOT NULL,
  `margin_min` decimal(5,2) NOT NULL DEFAULT '30.00' COMMENT 'Marge minimum en pourcentage',
  `margin_max` decimal(5,2) NOT NULL DEFAULT '60.00' COMMENT 'Marge maximum en pourcentage',
  `difficulty_multiplier` decimal(3,1) NOT NULL DEFAULT '1.5' COMMENT 'Multiplicateur pour la difficulté moyenne',
  `time_rate` decimal(5,2) NOT NULL DEFAULT '25.00' COMMENT 'Tarif horaire en euros',
  `google_api_key` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Clé API Google Custom Search',
  `google_search_engine_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'ID du moteur de recherche Google personnalisé',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `difficulty_easy` decimal(3,1) NOT NULL DEFAULT '1.0' COMMENT 'Multiplicateur pour difficulté facile',
  `difficulty_medium` decimal(3,1) NOT NULL DEFAULT '1.5' COMMENT 'Multiplicateur pour difficulté moyenne',
  `difficulty_hard` decimal(3,1) NOT NULL DEFAULT '2.0' COMMENT 'Multiplicateur pour difficulté difficile'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Paramètres du calculateur de prix de réparation';

--
-- Déchargement des données de la table `calculator_settings`
--

INSERT INTO `calculator_settings` (`id`, `margin_min`, `margin_max`, `difficulty_multiplier`, `time_rate`, `google_api_key`, `google_search_engine_id`, `created_at`, `updated_at`, `difficulty_easy`, `difficulty_medium`, `difficulty_hard`) VALUES
(1, 30.00, 60.00, 1.5, 25.00, 'AIzaSyBsqqE2tjgp6OY722lgUeFqJgjvNlnhyfk', '424b4fb42c6ad47d5', '2025-09-29 20:09:18', '2025-09-29 22:00:03', 1.0, 1.5, 2.0);

-- --------------------------------------------------------

--
-- Structure de la table `categories`
--

CREATE TABLE `categories` (
  `id` int NOT NULL,
  `nom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `clients`
--

CREATE TABLE `clients` (
  `id` int NOT NULL,
  `nom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `prenom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telephone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `inscrit_parrainage` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Client inscrit au programme de parrainage ou non',
  `code_parrainage` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Code unique pour le parrainage (peut être null si pas inscrit)',
  `date_inscription_parrainage` timestamp NULL DEFAULT NULL COMMENT 'Date d''inscription au programme de parrainage'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `clients`
--

INSERT INTO `clients` (`id`, `nom`, `prenom`, `telephone`, `email`, `date_creation`, `inscrit_parrainage`, `code_parrainage`, `date_inscription_parrainage`) VALUES
(1, 'saber', 'guezguez', '33782962906', NULL, '2025-09-23 08:20:00', 0, NULL, NULL),
(2, 'test', 'test', '33788998899', NULL, '2025-10-09 21:40:29', 0, NULL, NULL),
(3, 'test', 'test', '12345678908', NULL, '2025-10-09 21:50:37', 0, NULL, NULL),
(4, 'test', 'test3', '12345678909', NULL, '2025-10-09 22:03:03', 0, NULL, NULL),
(5, 'asd', 'ads', '12345678909', '', '2025-10-09 22:12:20', 0, NULL, NULL),
(6, 'ads', 'ads', '12345678909', '', '2025-10-09 22:32:44', 0, NULL, NULL),
(7, 'asd', 'dsa', '33123456789', '', '2025-10-09 23:17:14', 0, NULL, NULL),
(8, 'dupont', 'jean', '33782962906', '', '2025-10-20 19:17:01', 0, NULL, NULL),
(9, 'sad', 'das', '12345678902', NULL, '2025-11-02 21:30:47', 0, NULL, NULL),
(10, 'asddas', 'asddsa', '12345678912', NULL, '2025-11-02 21:43:38', 0, NULL, NULL),
(11, 'dashdash', 'iaduyg', '66123456789', NULL, '2025-11-02 21:44:11', 0, NULL, NULL),
(12, 'dashdash', 'iaduyg', '66123456787', NULL, '2025-11-02 21:47:19', 0, NULL, NULL),
(13, 'dashdash', 'iaduyg', '66123456780', NULL, '2025-11-02 21:51:11', 0, NULL, NULL),
(14, 'asd', 'ads', '22123456765', NULL, '2025-11-02 21:51:26', 0, NULL, NULL),
(1000, 'Ouerghemi', 'Sofien', '3354115219', NULL, '2025-04-08 09:44:35', 0, NULL, NULL),
(1001, 'Touba', 'Abbes', '3360577563', NULL, '2025-04-08 09:44:35', 0, NULL, NULL),
(1002, 'Smail', 'Cheyma', '3349292638', NULL, '2025-04-08 09:44:35', 0, NULL, NULL),
(1003, 'Diallo', 'Binta', '3345937372', NULL, '2025-04-08 09:44:35', 0, NULL, NULL),
(1004, 'Ziani', 'Sarah', '3345203297', NULL, '2025-04-08 09:44:42', 0, NULL, NULL),
(1005, 'Toumi', 'Hamid', '3376356317', NULL, '2025-04-08 09:44:42', 0, NULL, NULL),
(1006, 'Mezidi', 'Jean-Paul', '3356499574', NULL, '2025-04-08 09:44:42', 0, NULL, NULL),
(1007, 'Leboeuf', 'Gilbert', '3376518709', NULL, '2025-04-08 09:44:42', 0, NULL, NULL),
(1008, 'Laval', 'Sarah', '3378379694', NULL, '2025-04-08 09:46:00', 0, NULL, NULL),
(1009, 'Hani', 'Myriam', '3375033709', NULL, '2025-04-08 09:46:00', 0, NULL, NULL),
(1010, '', 'William', '+33123456789', '', '2025-04-08 09:50:11', 0, NULL, NULL),
(1011, 'blanchard', 'Franck', '33664995745', '', '2025-04-08 09:53:45', 0, NULL, NULL),
(1012, 'Chevalier', '', '33650759615', '', '2025-04-08 10:01:28', 0, NULL, NULL),
(1013, 'Edward', '', '33752549818', '', '2025-04-08 10:02:47', 0, NULL, NULL),
(1014, 'Tavares', '.           .', '33771784100', '', '2025-04-08 10:06:58', 0, NULL, NULL),
(1015, '. . . ...', 'Mhedhbi', '33780815206', '', '2025-04-08 10:10:32', 0, NULL, NULL),
(1016, 'Benji', '........', '33658837078', '', '2025-04-08 10:11:41', 0, NULL, NULL),
(1017, 'kiefer', '......', '33622375932', '', '2025-04-08 10:15:15', 0, NULL, NULL),
(1018, 'Kalil', '......', '33663630861', '', '2025-04-08 10:18:50', 0, NULL, NULL),
(1019, '.....', 'Alvaro', '33626370377', '', '2025-04-08 10:21:10', 0, NULL, NULL),
(1020, 'geay', '.....', '33766198488', '', '2025-04-08 10:22:39', 0, NULL, NULL),
(1021, 'Sonia', '.....', '33626900388', '', '2025-04-08 10:24:01', 0, NULL, NULL),
(1022, 'Mike', '.....', '33641991850', '', '2025-04-08 10:25:58', 0, NULL, NULL),
(1023, 'khalifa', '......', '33652652094', '', '2025-04-08 10:28:19', 0, NULL, NULL),
(1024, 'Gomez', '.....', '33745563392', '', '2025-04-08 10:29:03', 0, NULL, NULL),
(1025, 'cabes', '.....', '33605775636', '', '2025-04-08 10:29:57', 0, NULL, NULL),
(1026, 'poline', '.....', '33641126159', '', '2025-04-08 10:30:52', 0, NULL, NULL),
(1027, 'bruno', 'baradas', '+33780814592', '', '2025-04-08 10:45:11', 0, NULL, NULL),
(1028, 'sab', 'sab', '1234567890', NULL, '2025-04-08 22:23:51', 0, NULL, NULL),
(1029, 'guezguez', 'saber', '33782962906', '', '2025-04-09 23:12:07', 0, NULL, NULL),
(1030, 'eric', 'zemour', '0785256655', '', '2025-04-10 22:08:38', 0, NULL, NULL),
(1031, 'Dupont', 'Jean', '33612345678', 'jean.dupont@example.com', '2025-04-11 08:05:00', 0, NULL, NULL),
(1032, 'geeroms', '...', '33770378061', NULL, '2025-04-11 08:05:45', 0, NULL, NULL),
(1033, 'oubda', '...', '33784868274', NULL, '2025-04-11 08:05:45', 0, NULL, NULL),
(1034, 'gherab', '...', '33667352006', NULL, '2025-04-11 08:05:45', 0, NULL, NULL),
(1035, 'Besse', '...', '33682366934', NULL, '2025-04-11 08:05:45', 0, NULL, NULL),
(1036, 'vizy', '...', '33688341732', NULL, '2025-04-11 08:05:45', 0, NULL, NULL),
(1037, 'isabelle', '...', '33745187088', NULL, '2025-04-11 08:05:45', 0, NULL, NULL),
(1038, 'anis', '...', '33753607252', NULL, '2025-04-11 08:05:45', 0, NULL, NULL),
(1039, 'isabella', '...', '33745187098', NULL, '2025-04-11 08:05:45', 0, NULL, NULL),
(1040, 'jawher', '...', '33753096941', NULL, '2025-04-11 08:05:45', 0, NULL, NULL),
(1041, 'Chokri', 'Phone etoile', '0605652295', '', '2025-04-11 09:58:01', 0, NULL, NULL),
(1042, 'asd', 'ads', '08454', '', '2025-04-11 20:32:44', 0, NULL, NULL),
(1043, 'test', 'test', '0782965345', '', '2025-04-11 20:36:34', 0, NULL, NULL),
(1044, 'sdfgh', 'asdfg', '078296352454', '', '2025-04-11 20:37:09', 0, NULL, NULL),
(1045, 'iuhgf', 'gffbh', '0782962906', '', '2025-04-11 20:39:30', 0, NULL, NULL),
(1046, 'Elakkioui', 'Rayan', '0769835725', '', '2025-04-12 13:47:21', 0, NULL, NULL),
(1047, 'Shdj', 'Didj', '0782945467', '', '2025-04-15 09:44:59', 0, NULL, NULL),
(1048, 'Cheikh', '.', '33628926041', '', '2025-04-16 20:01:31', 0, NULL, NULL),
(1049, 'Reguillon', 'Sebastien', '33616371516', '', '2025-04-16 20:47:46', 0, NULL, NULL),
(1050, 'Naffati', 'Lyes', '33667710336', '', '2025-04-16 21:05:09', 0, NULL, NULL),
(1051, 'Gerard', '.', '33769471380', '', '2025-04-16 21:08:47', 0, NULL, NULL),
(1052, 'Leroy', '.', '33492180861', '', '2025-04-16 21:10:51', 0, NULL, NULL),
(1053, 'butez', '.', '33620130967', '', '2025-04-16 21:16:46', 0, NULL, NULL),
(1054, 'Mehdi', '.', '33622009900', '', '2025-04-16 21:18:13', 0, NULL, NULL),
(1055, 'benjamin', 'deguitre', '336644706968', '', '2025-04-16 22:09:25', 0, NULL, NULL),
(1056, 'Abdelkatous', 'Salim', '33788083299', '', '2025-04-17 08:56:50', 0, NULL, NULL),
(1057, 'Nassera', 'Touhami', '33650722275', '', '2025-04-17 09:56:52', 0, NULL, NULL),
(1058, 'amar', 'ruben', '+33765811362', '', '2025-04-17 10:23:46', 0, NULL, NULL),
(1059, 'konate', 'gilo', '+33780642062', '', '2025-04-17 10:32:02', 0, NULL, NULL),
(1060, 'Baptiste', 'paulin', '33667963163', '', '2025-04-17 11:17:29', 0, NULL, NULL),
(1061, 'Bsbs', 'Hsbd', '72927373', '', '2025-04-17 11:27:15', 0, NULL, NULL),
(1062, 'pina brito', 'gaoo', '+33755152290', '', '2025-04-17 11:37:02', 0, NULL, NULL),
(1063, 'Borges', 'Luis', '+33751116090', '', '2025-04-17 12:34:55', 0, NULL, NULL),
(1064, 'vaz da veiga', 'Alexandra', '+33745345479', '', '2025-04-18 10:14:46', 0, NULL, NULL),
(1065, 'Vassallo', 'Ludovic', '+39 340 076 0220', '', '2025-04-18 10:19:33', 0, NULL, NULL),
(1066, 'toptchii', 'Denys', '+33698557044', '', '2025-04-18 12:08:39', 0, NULL, NULL),
(1067, 'Client', 'Sav', '33614341278', '', '2025-04-18 12:26:24', 0, NULL, NULL),
(1068, 'lagzouli', 'saif', '+33771300956', '', '2025-04-18 13:41:02', 0, NULL, NULL),
(1069, 'julien', 'sebastien', '+33614064572', '', '2025-04-19 06:23:13', 0, NULL, NULL),
(1070, 'commarieu', 'Jean robert', '+33689685139', '', '2025-04-19 07:18:23', 0, NULL, NULL),
(1071, 'deli', 'mohamed', '+33758793436', '', '2025-04-19 10:43:54', 0, NULL, NULL),
(1072, 'Deli', 'Mohamed', '+33767980783', '', '2025-04-19 10:44:48', 0, NULL, NULL),
(1073, 'le jeune', 'thierry', '+33627532376', '', '2025-04-19 13:33:38', 0, NULL, NULL),
(1074, 'Guellim', 'Wajdi', '33665495221', '', '2025-04-20 14:31:36', 0, NULL, NULL),
(1075, 'Fadil', 'El harrak', '33643532356', '', '2025-04-21 09:00:33', 0, NULL, NULL),
(1076, 'hadjedji', 'herve', '+33641041186', '', '2025-04-22 07:42:59', 0, NULL, NULL),
(1077, 'semelagne', 'karl', '+33673973704', '', '2025-04-22 11:25:59', 0, NULL, NULL),
(1078, 'mfoulou', 'ronaldo', '+33749057297', '', '2025-04-22 12:02:06', 0, NULL, NULL),
(1079, 'Mendes', 'cinthya', '+33745583304', '', '2025-04-22 12:06:11', 0, NULL, NULL),
(1080, 'cardoso', 'junior', '+33760106281', '', '2025-04-22 12:23:36', 0, NULL, NULL),
(1081, 'limem', 'saif', '+33753132907', '', '2025-04-22 13:53:53', 0, NULL, NULL),
(1082, 'serrout', 'anouar', '+33618830077', '', '2025-04-22 14:18:05', 0, NULL, NULL),
(1083, 'boh', 'leny', '+33767233917', '', '2025-04-22 14:54:51', 0, NULL, NULL),
(1084, 'Laskar', 'Bruno', '33613795938', '', '2025-04-23 06:06:35', 0, NULL, NULL),
(1085, 'bitti', 'Adam', '33626861669', '', '2025-04-23 06:22:46', 0, NULL, NULL),
(1086, 'De soussa', 'Noaj', '33667034099', '', '2025-04-23 10:03:53', 0, NULL, NULL),
(1087, 'Valand', 'Adrien', '33613917144', '', '2025-04-23 10:08:05', 0, NULL, NULL),
(1088, 'nouira', 'rayan', '33659145811', '', '2025-04-23 12:02:40', 0, NULL, NULL),
(1089, 'Ali', 'Ben klil', '33619425652', '', '2025-04-23 13:42:41', 0, NULL, NULL),
(1090, 'mainte', 'lamine', '33745934270', '', '2025-04-23 13:53:23', 0, NULL, NULL),
(1091, 'fabien', 'alexis', '33622692293', '', '2025-04-24 10:57:55', 0, NULL, NULL),
(1092, 'Tavares', 'adelina', '33753014628', '', '2025-04-24 11:30:41', 0, NULL, NULL),
(1093, 'maunier', 'marie-france', '33777224461', '', '2025-04-24 12:07:18', 0, NULL, NULL),
(1094, 'LAme', 'Elias', '33781715702', '', '2025-04-25 06:49:46', 0, NULL, NULL),
(1095, 'Pienkowski', 'Julie', '33659406676', '', '2025-04-25 08:00:19', 0, NULL, NULL),
(1096, 'Jabri', 'Chad', '33663625393', '', '2025-04-25 08:46:46', 0, NULL, NULL),
(1097, 'Empire', 'Leo', '33744236061', '', '2025-04-25 09:04:09', 0, NULL, NULL),
(1098, 'Boufenchouche', 'Housseyn', '33651249879', '', '2025-04-26 07:33:18', 0, NULL, NULL),
(1099, 'paco', 'paco', '33766434929', '', '2025-04-26 10:10:40', 0, NULL, NULL),
(1100, 'Demaria', 'PAtrique', '33606752227', '', '2025-04-26 11:10:07', 0, NULL, NULL),
(1101, 'Samet', 'fares', '33626519674', '', '2025-04-26 11:59:13', 0, NULL, NULL),
(1102, 'chrigui', 'mohamed', '33781766665', '', '2025-04-26 13:59:09', 0, NULL, NULL),
(1103, 'briki', 'sami', '33624255314', '', '2025-04-26 14:13:10', 0, NULL, NULL),
(1104, 'Vincent', 'Arthure', '33769026694', '', '2025-04-26 15:07:48', 0, NULL, NULL),
(1105, 'ben frej', 'sami', '+33665529910', '', '2025-04-29 06:04:24', 0, NULL, NULL),
(1106, 'mechitoua', 'ali', '+33623080875', '', '2025-04-29 06:31:34', 0, NULL, NULL),
(1107, 'mange', 'dominique', '+33662269042', '', '2025-04-29 07:16:55', 0, NULL, NULL),
(1108, 'Dumenil', 'Julien', '33664990030', '', '2025-04-29 07:31:01', 0, NULL, NULL),
(1109, 'gomes', 'silvio', '+33652156733', '', '2025-04-29 07:44:31', 0, NULL, NULL),
(1110, 'klinger', 'reynald', '+33766784343', '', '2025-04-29 07:59:43', 0, NULL, NULL),
(1111, 'bouzidi', 'chtui', '+33758498584', '', '2025-04-29 08:03:12', 0, NULL, NULL),
(1112, 'gomes', 'isaias', '+33663021392', '', '2025-04-29 12:49:07', 0, NULL, NULL),
(1113, 'garden', 'harmony', '+33651667542', '', '2025-04-29 13:08:53', 0, NULL, NULL),
(1114, 'menier', 'menier', '33641498049', '', '2025-04-29 14:10:36', 0, NULL, NULL),
(1115, 'ghersi', 'clement', '+33670693168', '', '2025-04-30 06:04:15', 0, NULL, NULL),
(1116, 'lazouli', 'saif', '+33771300995', '', '2025-04-30 07:06:46', 0, NULL, NULL),
(1117, 'Bahmane', 'Aroun', '33652723796', '', '2025-04-30 09:56:46', 0, NULL, NULL),
(1118, 'anton', 'morin', '+33626603606', '', '2025-04-30 12:21:28', 0, NULL, NULL),
(1119, 'laurent', 'danielle', '+33663174467', '', '2025-04-30 12:23:57', 0, NULL, NULL),
(1120, 'bouzidi', 'saida', '+33622590880', '', '2025-05-02 07:59:44', 0, NULL, NULL),
(1121, 'Chavanon', 'didier', '33634252381', '', '2025-05-02 08:31:36', 0, NULL, NULL),
(1122, 'Dagachi', 'virginie', '33758457090', '', '2025-05-02 08:34:03', 0, NULL, NULL),
(1123, 'defleur', 'Jean marc', '+33634322971', '', '2025-05-02 11:18:05', 0, NULL, NULL),
(1124, 'bel abdallah', 'mabrouka', '+33766130266', '', '2025-05-02 14:14:28', 0, NULL, NULL),
(1125, 'cottin', 'Vincent', '+33652418052', '', '2025-05-03 06:40:01', 0, NULL, NULL),
(1126, 'delahey', 'benoit', '+33638662202', '', '2025-05-03 06:43:04', 0, NULL, NULL),
(1127, 'dehria', 'yassin', '+33767636170', '', '2025-05-03 07:13:40', 0, NULL, NULL),
(1128, 'barhoun', 'morad', '+33762027682', '', '2025-05-03 09:58:38', 0, NULL, NULL),
(1129, 'dubarry', 'bernard', '33781158831', '', '2025-05-03 10:12:43', 0, NULL, NULL),
(1130, '>>>>', 'mefrech', '33612363990', '', '2025-05-03 11:08:54', 0, NULL, NULL),
(1131, 'garcia', 'tristan', '33609629744', '', '2025-05-03 11:15:16', 0, NULL, NULL),
(1132, 'gotai', 'eden', '33650067226', '', '2025-05-03 13:18:19', 0, NULL, NULL),
(1133, 'Delani', 'Matola', '33780171999', '', '2025-05-05 14:44:01', 0, NULL, NULL),
(1134, 'de sousa', 'noah', '33667034099', '', '2025-05-06 06:26:45', 0, NULL, NULL),
(1135, 'gulille', 'georgio', '33622134961', '', '2025-05-06 06:54:21', 0, NULL, NULL),
(1136, 'mainge', 'martin', '33603515054', '', '2025-05-06 10:16:39', 0, NULL, NULL),
(1137, 'lannez', 'remi', '33658579886', '', '2025-05-06 10:22:44', 0, NULL, NULL),
(1138, 'christian', 'christian', '33751588192', '', '2025-05-06 11:27:13', 0, NULL, NULL),
(1139, 'ouvrard', 'amandine', '33660802378', '', '2025-05-06 13:58:19', 0, NULL, NULL),
(1140, 'Chaine', 'Amine', '33758152638', '', '2025-05-06 17:38:55', 0, NULL, NULL),
(1141, 'Afif', 'Afif', '33661662816', '', '2025-05-07 06:11:02', 0, NULL, NULL),
(1142, 'akrich', 'pierre', '33617427348', '', '2025-05-07 06:20:33', 0, NULL, NULL),
(1143, 'baelens', 'muriel', '33610057986', '', '2025-05-07 06:42:19', 0, NULL, NULL),
(1144, 'diez', 'marco', '33769358235', '', '2025-05-07 07:28:47', 0, NULL, NULL),
(1145, 'delautre', 'dorian', '33755858270', '', '2025-05-07 08:42:32', 0, NULL, NULL),
(1146, 'raida', 'raida', '33610108593', '', '2025-05-07 11:19:49', 0, NULL, NULL),
(1147, 'jliti', 'samira', '33663217198', '', '2025-05-07 11:33:49', 0, NULL, NULL),
(1148, 'mazure', 'mireille', '33683125073', '', '2025-05-07 12:03:00', 0, NULL, NULL),
(1149, 'Said', 'heikel', '33745744149', '', '2025-05-07 12:19:27', 0, NULL, NULL),
(1150, 'oubda', 'lamoussa', '33784868274', '', '2025-05-07 12:24:55', 0, NULL, NULL),
(1151, 'puric', 'miki', '33648365353', '', '2025-05-07 12:48:26', 0, NULL, NULL),
(1152, 'pedat', 'olivier', '33629803202', '', '2025-05-07 13:23:42', 0, NULL, NULL),
(1153, 'aladin', 'zhaira', '33780712779', '', '2025-05-07 14:46:53', 0, NULL, NULL),
(1154, 'dury', 'michael', '+33620458284', '', '2025-05-08 10:01:11', 0, NULL, NULL),
(1155, 'carrosserie parisienne', 'Naasim', '+33695314026', '', '2025-05-08 10:25:40', 0, NULL, NULL),
(1156, 'lejeune dutot', 'pierre', '+33685359397', '', '2025-05-08 11:36:24', 0, NULL, NULL),
(1157, 'ben chaib', 'nadia', '33783649456', '', '2025-05-08 13:21:09', 0, NULL, NULL),
(1158, 'jose', 'jose', '+33651531710', '', '2025-05-08 14:22:46', 0, NULL, NULL),
(1159, 'jacqueline', 'Jacqueline', '+33769194236', '', '2025-05-09 06:55:06', 0, NULL, NULL),
(1160, 'laine', 'reine jeanne', '+33677295624', '', '2025-05-09 07:24:03', 0, NULL, NULL),
(1161, 'coppola', 'stephanie', '33635818118', '', '2025-05-09 10:10:09', 0, NULL, NULL),
(1162, 'guillemot', 'audrey', '33618447560', '', '2025-05-09 10:22:10', 0, NULL, NULL),
(1163, 'da silva', 'fabio', '33750014313', '', '2025-05-09 11:25:31', 0, NULL, NULL),
(1164, 'jouen', 'Yannick', '33648160506', '', '2025-05-09 12:16:50', 0, NULL, NULL),
(1165, 'el abed', 'mohammed', '33614974233', '', '2025-05-09 13:30:19', 0, NULL, NULL),
(1166, 'belghaji', 'dorsaf', '33783842607', '', '2025-05-10 08:37:35', 0, NULL, NULL),
(1167, 'saif', 'bejaoui', '33619663118', '', '2025-05-10 10:01:03', 0, NULL, NULL),
(1168, 'ferrari', 'jean phillipe', '33671104603', '', '2025-05-10 10:06:30', 0, NULL, NULL),
(1169, 'spenlehauert', 'rudolph', '33750390767', '', '2025-05-10 10:08:57', 0, NULL, NULL),
(1170, 'koirat', 'walid', '33667516616', '', '2025-05-10 11:29:58', 0, NULL, NULL),
(1171, 'maslovaric', 'labud', '33624173027', '', '2025-05-10 13:27:13', 0, NULL, NULL),
(1172, 'el yatime', 'rayan', '33627441112', '', '2025-05-10 13:36:32', 0, NULL, NULL),
(1173, 'Saiz', 'Jose', '33617890017', '', '2025-05-13 06:12:36', 0, NULL, NULL),
(1174, 'alami', 'anas', '33618301575', '', '2025-05-13 06:47:44', 0, NULL, NULL),
(1175, 'jean sebastien', 'coste', '33601050501', '', '2025-05-13 07:50:00', 0, NULL, NULL),
(1176, 'rigot', 'johanna', '33620398976', '', '2025-05-13 11:39:32', 0, NULL, NULL),
(1177, 'arthaud', 'charles', '33619861986', '', '2025-05-14 06:47:09', 0, NULL, NULL),
(1178, 'penet', 'valerie', '33628985904', '', '2025-05-14 07:12:18', 0, NULL, NULL),
(1179, 'Ghaouti', 'Abdel', '33769967083', '', '2025-05-14 07:55:23', 0, NULL, NULL),
(1180, 'Davaille', 'Magalie', '33760967408', '', '2025-05-14 10:43:02', 0, NULL, NULL),
(1181, 'sebih', 'alain', '33698865980', '', '2025-05-14 11:13:11', 0, NULL, NULL),
(1182, 'andal', 'aston', '33665073395', '', '2025-05-14 12:44:17', 0, NULL, NULL),
(1183, 'roger', 'thomas', '33768270848', '', '2025-05-15 07:37:59', 0, NULL, NULL),
(1184, 'Djenan', 'Issam', '34632272526', '', '2025-05-15 09:05:41', 0, NULL, NULL),
(1185, 'Alessio', 'Jules', '33618427336', '', '2025-05-15 09:26:32', 0, NULL, NULL),
(1186, 'Sapia', 'Cathy', '33661269590', '', '2025-05-15 11:11:23', 0, NULL, NULL),
(1187, 'Pettine', 'morgane', '33628787608', '', '2025-05-16 06:39:44', 0, NULL, NULL),
(1188, 'ARABITO', 'THEO', '33660053900', '', '2025-05-16 10:06:47', 0, NULL, NULL),
(1189, 'ZELLATI', 'LINA', '33766178512', '', '2025-05-16 11:13:45', 0, NULL, NULL),
(1190, 'elbaroudi', 'sophiane', '33699710997', '', '2025-05-17 11:06:08', 0, NULL, NULL),
(1191, 'baiyili', 'Kevin', '33646662541', '', '2025-05-17 11:13:25', 0, NULL, NULL),
(1192, 'abouch', 'rayan', '33762710865', '', '2025-05-17 11:34:06', 0, NULL, NULL),
(1193, 'boussenec', 'Killian', '33668998605', '', '2025-05-17 12:16:32', 0, NULL, NULL),
(1194, 'plaza', 'jazek', '33672298008', '', '2025-05-17 13:32:09', 0, NULL, NULL),
(1195, 'isaias', 'gomes morera', '33663140292', '', '2025-05-17 14:09:56', 0, NULL, NULL),
(1196, 'Mickeal', 'Jebe', '33751561472', '', '2025-05-20 06:15:47', 0, NULL, NULL),
(1197, 'Jarrar', 'Maissa', '33784733278', '', '2025-05-20 07:53:59', 0, NULL, NULL),
(1198, 'Miled', 'Bejaoui', '33621925761', '', '2025-05-20 07:55:48', 0, NULL, NULL),
(1199, 'Razin', 'elodie', '33771208512', '', '2025-05-20 07:56:47', 0, NULL, NULL),
(1200, 'ambesi', 'lorenzo', '33780707069', '', '2025-05-20 12:26:35', 0, NULL, NULL),
(1201, 'aparicio', 'kevin', '33668636568', '', '2025-05-21 07:16:52', 0, NULL, NULL),
(1202, 'riabi', 'ilies', '33695140579', '', '2025-05-21 07:25:19', 0, NULL, NULL),
(1203, 'aymerick', 'Dantzer', '33757556485', '', '2025-05-21 10:41:23', 0, NULL, NULL),
(1204, 'momo', 'elge', '33627442896', '', '2025-05-21 13:02:06', 0, NULL, NULL),
(1205, 'sally', 'sophie', '33664918281', '', '2025-05-22 06:22:02', 0, NULL, NULL),
(1206, 'boisard', 'janine', '33616953409', '', '2025-05-22 07:15:57', 0, NULL, NULL),
(1207, 'maulaz', 'dominique', '33601943310', '', '2025-05-22 07:23:46', 0, NULL, NULL),
(1208, 'cravero', 'vincent', '33686066290', '', '2025-05-22 07:30:34', 0, NULL, NULL),
(1209, 'nassim', 'nassim', '33758884022', '', '2025-05-23 08:25:34', 0, NULL, NULL),
(1210, 'Ablali', 'Tarik', '33658816271', '', '2025-05-23 11:01:57', 0, NULL, NULL),
(1211, 'marbi', 'michael', '33781083617', '', '2025-05-23 11:17:57', 0, NULL, NULL),
(1212, 'khemaies', 'jeridi', '33745437379', '', '2025-05-23 13:07:01', 0, NULL, NULL),
(1213, 'zazoua', 'abdelhadi', '33771777724', '', '2025-05-24 10:55:26', 0, NULL, NULL),
(1214, 'bufalini', 'olivier', '33615915221', '', '2025-05-24 11:05:08', 0, NULL, NULL),
(1215, 'saidi', 'abdel', '33642653182', '', '2025-05-24 11:36:56', 0, NULL, NULL),
(1216, 'soussa', 'anis', '33781058557', '', '2025-05-24 14:18:08', 0, NULL, NULL),
(1217, 'loft', 'consulting', '33615324192', '', '2025-05-27 06:08:22', 0, NULL, NULL),
(1218, 'Garbi', 'malek', '3323566095', '', '2025-05-27 06:47:22', 0, NULL, NULL),
(1219, 'abed', 'foued', '33780424211', '', '2025-05-27 07:12:10', 0, NULL, NULL),
(1220, 'arou-defrancq', 'lokman', '33699136117', '', '2025-05-27 08:17:45', 0, NULL, NULL),
(1221, 'paolini', 'louis', '33620328193', '', '2025-05-27 14:13:31', 0, NULL, NULL),
(1222, 'aouni', 'walid', '33782414421', '', '2025-05-28 06:33:42', 0, NULL, NULL),
(1223, 'denis', 'agnes', '33668807873', '', '2025-05-28 07:31:13', 0, NULL, NULL),
(1224, 'benhattab', 'ibrahim', '33615179589', '', '2025-05-28 11:39:18', 0, NULL, NULL),
(1225, 'pisciotta', 'christophe', '33612710305', '', '2025-05-28 12:26:58', 0, NULL, NULL),
(1226, 'kechroud', 'leni', '33651388615', '', '2025-05-28 13:01:18', 0, NULL, NULL),
(1227, 'benkrea', 'yassine', '33658272540', '', '2025-05-28 13:09:52', 0, NULL, NULL),
(1228, 'zeroual', 'massinissa', '33602367817', '', '2025-05-29 10:04:45', 0, NULL, NULL),
(1229, 'lourdin', 'emmeric', '33602121960', '', '2025-05-29 10:10:49', 0, NULL, NULL),
(1230, 'vanoosthuyse', 'mederic', '33652448437', '', '2025-05-29 11:41:02', 0, NULL, NULL),
(1231, 'devita', 'miriam', '33664500769', '', '2025-05-29 11:43:11', 0, NULL, NULL),
(1232, 'tavares', 'david', '33775724701', '', '2025-05-30 14:35:06', 0, NULL, NULL),
(1233, 'brahim', 'hanel', '33675835758', '', '2025-05-31 06:39:00', 0, NULL, NULL),
(1234, 'lopes', 'sara', '33613929119', '', '2025-06-03 06:14:40', 0, NULL, NULL),
(1235, 'melndes', 'marlene', '0623948767', '', '2025-06-03 06:38:46', 0, NULL, NULL),
(1236, 'mendez tavares', 'maeva', '0745421459', '', '2025-06-03 07:26:08', 0, NULL, NULL),
(1237, 'DENDANI', 'FEDI', '0745582463', '', '2025-06-03 07:35:48', 0, NULL, NULL),
(1238, 'CYRILLE', 'THOMAS', '0685020238', '', '2025-06-03 07:45:21', 0, NULL, NULL),
(1239, 'LOIC', 'STREET MOTORS', '0769967083', '', '2025-06-03 10:49:11', 0, NULL, NULL),
(1240, '.', 'NADIR', '0635364673', '', '2025-06-04 06:12:23', 0, NULL, NULL),
(1241, '.', 'NARJES', '0773124235', '', '2025-06-04 06:30:50', 0, NULL, NULL),
(1242, 'thiault', 'paul', '33648188180', '', '2025-06-04 10:12:16', 0, NULL, NULL),
(1243, 'iberraken', 'adam', '33658606895', '', '2025-06-04 10:48:55', 0, NULL, NULL),
(1244, 'afassi', 'line', '33645241688', '', '2025-06-05 06:14:52', 0, NULL, NULL),
(1245, 'PORCHEDDA', 'MAGHALIE', '33601362577', '', '2025-06-05 07:47:13', 0, NULL, NULL),
(1246, 'balino', 'enzo', '33614150122', '', '2025-06-05 10:27:37', 0, NULL, NULL),
(1247, 'dahmami', 'mounam', '33664169964', '', '2025-06-05 12:16:12', 0, NULL, NULL),
(1248, 'bejaoui', 'saif', '33745474447', '', '2025-06-05 12:23:01', 0, NULL, NULL),
(1249, 'ramos', 'manuel', '33749936352', '', '2025-06-06 06:04:56', 0, NULL, NULL),
(1250, 'mahjoub', 'amnna', '33618577442', '', '2025-06-06 08:05:16', 0, NULL, NULL),
(1251, 'letova', 'anna', '33780551081', '', '2025-06-06 11:25:39', 0, NULL, NULL),
(1252, 'VR', 'Clement', '+33634271298', '', '2025-06-07 10:56:53', 0, NULL, NULL),
(1253, 'marchand', 'yohan', '33787956569', '', '2025-06-07 11:30:42', 0, NULL, NULL),
(1254, 'rimbert', 'djiani', '33781527626', '', '2025-06-07 11:37:18', 0, NULL, NULL),
(1255, 'picard', 'caroline', '33783904248', '', '2025-06-07 13:04:59', 0, NULL, NULL),
(1256, 'heitzler', 'Dylan', '33617402140', '', '2025-06-10 07:00:30', 0, NULL, NULL),
(1257, 'Guyoton', 'maxence', '33669024250', '', '2025-06-10 10:39:02', 0, NULL, NULL),
(1258, 'dachez', 'justine', '33783781606', '', '2025-06-10 12:42:48', 0, NULL, NULL),
(1259, 'platarot', 'felix', '33786479765', '', '2025-06-10 13:06:49', 0, NULL, NULL),
(1260, 'hamdi', 'guezguez', '337676866029', '', '2025-06-10 13:24:56', 0, NULL, NULL),
(1261, 'martin', 'elodie', '33603898043', '', '2025-06-10 14:10:34', 0, NULL, NULL),
(1262, 'jandoubi', 'Ali', '33626152887', '', '2025-06-11 06:09:20', 0, NULL, NULL),
(1263, 'ettouri', 'karim', '336710897790', '', '2025-06-11 06:14:16', 0, NULL, NULL),
(1264, 'vitali', 's', '33753875571', '', '2025-06-11 12:46:44', 0, NULL, NULL),
(1265, 'landry', 'adan', '33664370594', '', '2025-06-11 14:23:56', 0, NULL, NULL),
(1266, 'Melin', 'Marie', '33683363133', '', '2025-06-11 14:33:27', 0, NULL, NULL),
(1267, 'jafar', 'marine', '33668598082', '', '2025-06-12 06:42:53', 0, NULL, NULL),
(1268, 'ben', 'erera', '33749234582', '', '2025-06-12 08:05:01', 0, NULL, NULL),
(1269, 'fortado', 'thomas', '33684068225', '', '2025-06-12 10:32:38', 0, NULL, NULL),
(1270, 'Ruggiero', 'elichio', '33766157034', '', '2025-06-12 10:59:18', 0, NULL, NULL),
(1271, 'browaeys', 'frederique', '33664730819', '', '2025-06-12 11:05:09', 0, NULL, NULL),
(1272, 'Devita', 'michael', '33628870049', '', '2025-06-12 11:56:42', 0, NULL, NULL),
(1273, 'mejri', 'marouane', '33745415338', '', '2025-06-12 14:26:28', 0, NULL, NULL),
(1274, 'harrod', 'angela', '33630801135', '', '2025-06-13 06:11:43', 0, NULL, NULL),
(1275, 'ghougassian', 'eric', '33628346585', '', '2025-06-13 06:21:46', 0, NULL, NULL),
(1276, 'boudiraoui', 'sophiane', '33660719915', '', '2025-06-13 11:46:38', 0, NULL, NULL),
(1277, 'bruant', 'mathieu', '33652182472', '', '2025-06-13 11:49:11', 0, NULL, NULL),
(1278, 'el abed', 'rachid', '336145661736', '', '2025-06-13 12:07:59', 0, NULL, NULL),
(1279, '.', 'brissaud', '33650883462', '', '2025-06-13 13:11:31', 0, NULL, NULL),
(1280, 'le guennec', 'francoise', '33634862860', '', '2025-06-13 13:15:48', 0, NULL, NULL),
(1281, 'lopez', 'sara', '33625991823', '', '2025-06-13 14:14:49', 0, NULL, NULL),
(1282, 'gardavoir', 'dominique', '33609071860', '', '2025-06-14 06:19:25', 0, NULL, NULL),
(1283, 'Moroni', 'Raoul', '33652542388', '', '2025-06-14 06:47:20', 0, NULL, NULL),
(1284, '...', 'salim', '33618204676', '', '2025-06-14 10:27:27', 0, NULL, NULL),
(1285, 'sadok', '.....', '33782649053', '', '2025-06-14 11:50:30', 0, NULL, NULL),
(1286, 'adel', 'perreron', '33640876921', '', '2025-06-14 12:34:34', 0, NULL, NULL),
(1287, 'lefrada', 'radj', '33767928268', '', '2025-06-17 06:04:01', 0, NULL, NULL),
(1288, 'CHARTOM', 'ALEXIS', '33605548911', '', '2025-06-17 06:23:24', 0, NULL, NULL),
(1289, 'BAIJUE', 'ANTHONW', '330631661140', '', '2025-06-17 07:24:58', 0, NULL, NULL),
(1290, 'REGAIEG', 'IBRAIME', '330749073887', '', '2025-06-17 11:09:40', 0, NULL, NULL),
(1291, 'HUE', 'CHAD', '33769980728', '', '2025-06-17 12:47:52', 0, NULL, NULL),
(1292, 'darryl', 'mohamed', '33070641507538', '', '2025-06-17 13:33:50', 0, NULL, NULL),
(1293, 'gayet', 'juel', '330766510794', '', '2025-06-17 13:51:04', 0, NULL, NULL),
(1294, 'perenon', 'adell', '330640876921', '', '2025-06-17 14:32:11', 0, NULL, NULL),
(1295, 'dannecy', 'mehdi', '33769263571', '', '2025-06-18 07:49:39', 0, NULL, NULL),
(1296, 'gueddou', 'fael', '33695881419', '', '2025-06-18 08:45:32', 0, NULL, NULL),
(1297, 'jouhad', 'amel', '33698831036', '', '2025-06-18 10:12:22', 0, NULL, NULL),
(1298, 'mahjoubi', 'hayet', '33771645864', '', '2025-06-18 10:15:43', 0, NULL, NULL),
(1299, 'Walton', 'Ross', '33658360872', '', '2025-06-18 11:05:14', 0, NULL, NULL),
(1300, 'koula', 'sydney', '33652328728', '', '2025-06-18 12:06:42', 0, NULL, NULL),
(1301, 'mendes', 'derley', '33749208128', '', '2025-06-18 12:53:50', 0, NULL, NULL),
(1302, 'zitouni', 'moustapha', '33618399280', '', '2025-06-18 14:00:16', 0, NULL, NULL),
(1303, 'Dieude', 'Gabriel', '33637765351', '', '2025-06-19 06:05:20', 0, NULL, NULL),
(1304, 'boubaker', 'hamed', '330615907355', '', '2025-06-19 10:50:25', 0, NULL, NULL),
(1305, 'ali', 'ahamda', '330758719666', '', '2025-06-19 10:54:56', 0, NULL, NULL),
(1306, 'bennati', 'patricia', '330609987584', '', '2025-06-19 13:43:21', 0, NULL, NULL),
(1307, 'marwen', 'megigri', '330745415338', '', '2025-06-19 13:48:54', 0, NULL, NULL),
(1308, 'Hamilton', 'Maned aux', '330759890145', '', '2025-06-20 06:10:46', 0, NULL, NULL),
(1309, 'timberman', 'kristian', '33614749787', '', '2025-06-20 06:33:42', 0, NULL, NULL),
(1310, 'venzal', 'tyrone', '33076859070', '', '2025-06-20 11:18:16', 0, NULL, NULL),
(1311, 'el abed', 'hichem', '33668046105', '', '2025-06-20 14:32:07', 0, NULL, NULL),
(1312, 'OUAFI', 'ADEM', '33602419194', '', '2025-06-21 07:03:06', 0, NULL, NULL),
(1313, 'Majri', 'Abir', '33612028492', '', '2025-06-21 08:03:51', 0, NULL, NULL),
(1314, 'Boubaker', 'bader', '33669232625', '', '2025-06-21 10:36:57', 0, NULL, NULL),
(1315, 'evan', 'afchard', '33627614860', '', '2025-06-21 14:01:17', 0, NULL, NULL),
(1316, 'tomasz', 'malodobry', '33632913011', '', '2025-06-24 06:54:23', 0, NULL, NULL),
(1317, 'rahma', 'ben arfa', '330612480958', '', '2025-06-24 08:43:33', 0, NULL, NULL),
(1318, 'Powels', 'James', '33614355266', '', '2025-06-24 11:21:39', 0, NULL, NULL),
(1319, 'bahi', 'rayan', '33636050857', '', '2025-06-24 12:58:53', 0, NULL, NULL),
(1320, 'ardisson', 'anni', '33493467741', '', '2025-06-25 06:15:37', 0, NULL, NULL),
(1321, 'anouil', 'marie france', '33622480608', '', '2025-06-25 07:15:05', 0, NULL, NULL),
(1322, 'laszlo', 'attila', '33695572755', '', '2025-06-25 11:19:57', 0, NULL, NULL),
(1323, 'renard', 'killian', '33766647106', '', '2025-06-25 11:28:15', 0, NULL, NULL),
(1324, 'hua', 'diame', '33660772725', '', '2025-06-25 12:10:24', 0, NULL, NULL),
(1325, 'moignard', 'franck', '33684899264', '', '2025-06-25 13:25:20', 0, NULL, NULL),
(1326, 'chibani', 'aymen', '33651353666', '', '2025-06-25 14:32:05', 0, NULL, NULL),
(1327, 'municka', 'verena', '33783679100', '', '2025-06-26 11:28:40', 0, NULL, NULL),
(1328, 'b', 'laget', '33661571332', '', '2025-06-28 07:02:34', 0, NULL, NULL),
(1329, 'l ethan', 'jaison', '33602452184', '', '2025-06-28 07:07:36', 0, NULL, NULL),
(1330, 'coppola', 'stefani', '33612352265', '', '2025-06-28 07:38:15', 0, NULL, NULL),
(1331, 'May', 'Merlin', '33745026556', '', '2025-06-28 07:52:19', 0, NULL, NULL),
(1332, 'firas', 'farah', '33744540866', '', '2025-06-28 11:30:43', 0, NULL, NULL),
(1333, 'Neffati', 'Firas', '33651471886', '', '2025-06-28 14:06:27', 0, NULL, NULL),
(1334, 'Balgagi', 'Hamza', '33629184295', '', '2025-07-01 06:22:17', 0, NULL, NULL),
(1335, 'didry', 'philippe', '33629256509', '', '2025-07-01 10:52:47', 0, NULL, NULL),
(1336, 'logozzo', 'carmlo', '33650585696', '', '2025-07-01 11:08:36', 0, NULL, NULL),
(1337, 'Chaboua', 'Noran', '33652969734', '', '2025-07-01 12:00:00', 0, NULL, NULL),
(1338, 'panassidi', 'florence', '33668510337', '', '2025-07-01 12:38:38', 0, NULL, NULL),
(1339, 'halafi', 'sabur', '33629615166', '', '2025-07-01 12:45:58', 0, NULL, NULL),
(1340, 'arigo', 'jean michel', '33668367574', '', '2025-07-01 12:56:50', 0, NULL, NULL),
(1341, 'hanini', 'samima', '33620909107', '', '2025-07-01 14:40:17', 0, NULL, NULL),
(1342, 'Brindisille', 'Sebastien', '33623258572', '', '2025-07-02 07:15:22', 0, NULL, NULL),
(1343, 'agkalr', 'paula', '33622255416', '', '2025-07-02 08:31:23', 0, NULL, NULL),
(1344, 'horta davega', 'evanilson', '33626124853', '', '2025-07-02 11:09:49', 0, NULL, NULL),
(1345, 'renaud', 'justine', '33670040945', '', '2025-07-02 11:58:31', 0, NULL, NULL),
(1346, 'batnini', 'achwek', '33753981666', '', '2025-07-02 13:35:09', 0, NULL, NULL),
(1347, 'barera', 'nicolas', '33695539537', '', '2025-07-03 06:48:42', 0, NULL, NULL),
(1348, 'fawez', 'benacer', '33780833926', '', '2025-07-03 06:53:35', 0, NULL, NULL),
(1349, 'miscadjan', 'armelle', '33660263703', '', '2025-07-03 10:32:13', 0, NULL, NULL),
(1350, 'Vignacourt', 'Mathis', '33642485086', '', '2025-07-03 13:00:06', 0, NULL, NULL),
(1351, 'magaud', 'sean', '33778104548', '', '2025-07-03 13:34:15', 0, NULL, NULL),
(1352, 'amadasun', 'unity', '33758762028', '', '2025-07-03 14:35:43', 0, NULL, NULL),
(1353, 'perriot', 'cecille', '33642843492', '', '2025-07-03 14:42:49', 0, NULL, NULL),
(1354, 'Orfila', 'Charles', '33626636282', '', '2025-07-04 06:05:04', 0, NULL, NULL),
(1355, 'godard', 'mallo', '33751629683', '', '2025-07-04 06:11:39', 0, NULL, NULL),
(1356, 'Viada', 'Olivier', '33665618282', '', '2025-07-04 06:15:12', 0, NULL, NULL),
(1357, 'narvaez', 'loren', '33652879416', '', '2025-07-04 06:18:22', 0, NULL, NULL),
(1358, 'sultan', 'noham', '33752534809', '', '2025-07-04 11:16:40', 0, NULL, NULL),
(1359, 'louis', 'stephanie', '33614091760', '', '2025-07-04 11:22:18', 0, NULL, NULL),
(1360, 'schwender', 'stephan', '33760919988', '', '2025-07-04 11:30:07', 0, NULL, NULL),
(1361, 'djafari', 'faouzi', '33651473503', '', '2025-07-04 11:44:52', 0, NULL, NULL),
(1362, 'marton', 'mathias', '33612264406', '', '2025-07-04 11:50:47', 0, NULL, NULL),
(1363, 'avril', 'jeremy', '33665921748', '', '2025-07-04 12:16:50', 0, NULL, NULL),
(1364, 'legros', 'frederick', '33615689474', '', '2025-07-04 12:31:47', 0, NULL, NULL),
(1365, 'mercier', 'lionel', '33612531499', '', '2025-07-05 07:21:48', 0, NULL, NULL),
(1366, 'prat', 'gwenaelle', '33668260530', '', '2025-07-05 07:23:05', 0, NULL, NULL),
(1367, 'gerasi', 'olivier', '33785852247', '', '2025-07-05 14:48:42', 0, NULL, NULL),
(1368, 'Guezguez', 'Samah', '33612647674', '', '2025-07-06 12:44:20', 0, NULL, NULL),
(1369, 'lagreca', 'julien', '33755641160', '', '2025-07-08 08:27:35', 0, NULL, NULL),
(1370, 'Marro', 'Christine', '0612111167', 'nathalie.marro06110@gmail.com', '2025-07-08 10:04:14', 0, NULL, NULL),
(1371, 'Houari', 'Rayan', '0648442918', 'h.hib@laposte.net', '2025-07-08 10:12:32', 0, NULL, NULL),
(1372, 'Avaliani', 'David', '0612241254', 'avaliani', '2025-07-08 11:16:52', 0, NULL, NULL),
(1373, 'Carosserie', 'Parisiene', '0695314026', '', '2025-07-08 11:52:13', 0, NULL, NULL),
(1374, 'Askri', 'Ridha', '33624923574', '', '2025-07-08 12:14:57', 0, NULL, NULL),
(1375, 'Dubreucq', 'Quentin', '0641563424', '', '2025-07-08 12:33:26', 0, NULL, NULL),
(1376, 'Khalid', 'Said', '33620171291', '', '2025-07-08 12:56:52', 0, NULL, NULL),
(1377, 'kaboubi', 'mohammed', '33611385017', '', '2025-07-08 13:11:34', 0, NULL, NULL),
(1378, 'Kaouech', 'Mohamed', '33781804187', '', '2025-07-08 13:26:18', 0, NULL, NULL),
(1379, 'garcia', 'francisco', '33660184613', '', '2025-07-09 06:57:36', 0, NULL, NULL),
(1380, 'kakhoidze', 'alexandre', '33621744929', '', '2025-07-09 07:28:17', 0, NULL, NULL),
(1381, 'hassan', 'kouddan', '33', '', '2025-07-09 10:35:01', 0, NULL, NULL),
(1382, 'Diakite', 'Sala', '33758526050', '', '2025-07-09 11:49:13', 0, NULL, NULL),
(1383, 'costanzo', 'marie', '33782731548', '', '2025-07-09 12:22:14', 0, NULL, NULL),
(1384, 'jamal', 'rifkoun', '33749252527', '', '2025-07-09 12:48:26', 0, NULL, NULL),
(1385, 'fournier', 'Anthony', '33749296481', '', '2025-07-09 12:55:32', 0, NULL, NULL),
(1386, 'chaine', 'rachid', '33753247559', '', '2025-07-09 12:58:54', 0, NULL, NULL),
(1387, 'Legrand', 'Gaelle', '33667314360', '', '2025-07-09 14:25:15', 0, NULL, NULL),
(1388, 'martini', 'antoine', '33669658480', '', '2025-07-10 06:17:44', 0, NULL, NULL),
(1389, 'channoifi', 'hama', '33613316330', '', '2025-07-10 11:33:44', 0, NULL, NULL),
(1390, 'guyon', 'eric', '33663891299', '', '2025-07-10 13:11:12', 0, NULL, NULL),
(1391, 'gaulier', 'cheyms', '33669262674', '', '2025-07-11 06:31:49', 0, NULL, NULL),
(1392, 'Gilles', 'Bredusse', '33662276124', '', '2025-07-11 12:46:33', 0, NULL, NULL),
(1393, 'christophe', 'richard', '33680938848', '', '2025-07-12 06:06:52', 0, NULL, NULL),
(1394, 'benfredj', 'senda', '33620420659', '', '2025-07-12 07:42:36', 0, NULL, NULL),
(1395, 'bejaouu', 'hanen', '33619303176', '', '2025-07-12 07:53:47', 0, NULL, NULL),
(1396, 'Beji', 'Ali', '33785272651', '', '2025-07-12 08:33:27', 0, NULL, NULL),
(1397, 'kart', 'helmi', '0616078971', '', '2025-07-12 09:56:39', 0, NULL, NULL),
(1398, 'rayan', 'didier', '33615950026', '', '2025-07-12 10:28:35', 0, NULL, NULL),
(1399, 'Lafont', 'Francoise', '33609205132', '', '2025-07-12 11:04:45', 0, NULL, NULL),
(1400, 'lounis', 'nordine', '0615021764', '', '2025-07-12 13:01:30', 0, NULL, NULL),
(1401, 'Aboualla', 'Nael', '33661765087', '', '2025-07-15 06:24:56', 0, NULL, NULL),
(1402, 'Ahnouche', 'Edems', '33755950176', '', '2025-07-15 10:21:51', 0, NULL, NULL),
(1403, 'tavares ribero', 'helton', '33644014159', '', '2025-07-15 10:44:37', 0, NULL, NULL),
(1404, 'carai', 'nicolas', '33612200335', '', '2025-07-15 10:52:28', 0, NULL, NULL),
(1405, 'beunaiche', 'rafael', '33612191843', '', '2025-07-15 11:01:58', 0, NULL, NULL),
(1406, 'bedechian', 'amir', '0634192381', '', '2025-07-15 11:29:20', 0, NULL, NULL),
(1407, 'dada', 'mahaemed', '33636066610', '', '2025-07-15 12:30:11', 0, NULL, NULL),
(1408, 'Balti', 'Tarek', '33641182141', '', '2025-07-16 06:13:42', 0, NULL, NULL),
(1409, 'sousou', 'sousou', '0772103449', '', '2025-07-16 11:09:27', 0, NULL, NULL),
(1410, 'boulares', 'donia', '33629734409', '', '2025-07-16 12:08:15', 0, NULL, NULL),
(1411, 'mini', 'giusedpe', '0614653278', '', '2025-07-16 12:55:56', 0, NULL, NULL),
(1412, 'Kahwaji', 'Ramzi', '33626755202', '', '2025-07-16 14:11:16', 0, NULL, NULL),
(1413, 'Ndiaye', 'Sely', '33634365737', '', '2025-07-17 06:52:29', 0, NULL, NULL),
(1414, 'ben hassine', 'mohammed', '33780115755', '', '2025-07-17 08:09:18', 0, NULL, NULL),
(1415, 'Touis', 'Julien', '33665680135', '', '2025-07-17 08:29:05', 0, NULL, NULL),
(1416, 'rayan', 'duchez', '0618124087', '', '2025-07-17 11:01:17', 0, NULL, NULL),
(1417, 'Symba', 'Marcio', '33766088442', '', '2025-07-17 13:03:15', 0, NULL, NULL),
(1418, 'Kitsan', 'Rado', '33753812006', '', '2025-07-17 14:47:03', 0, NULL, NULL),
(1419, 'Yacoub', 'hatem', '33648039224', '', '2025-07-17 17:23:56', 0, NULL, NULL),
(1420, 'Kevin', 'Kevin', '33601013452', '', '2025-07-18 06:03:39', 0, NULL, NULL),
(1421, 'babi', 'nael', '0+641214187', '', '2025-07-18 07:23:56', 0, NULL, NULL),
(1422, 'Giraldo', 'Javier', '0749611647', '', '2025-07-18 07:24:30', 0, NULL, NULL),
(1423, 'benvenuti', 'lvira', '0644062165', '', '2025-07-18 07:50:48', 0, NULL, NULL),
(1424, 'sok', 'chea', '0695599987', '', '2025-07-18 10:26:21', 0, NULL, NULL),
(1425, 'mitzas', 'stephane', '33770414692', '', '2025-07-18 14:05:58', 0, NULL, NULL),
(1426, 'esteve', 'deborah', '33662176352', '', '2025-07-19 07:12:56', 0, NULL, NULL),
(1427, 'jillali', 'rafael', '33618034771', '', '2025-07-19 07:48:29', 0, NULL, NULL),
(1428, 'lejeune vutto', 'pierre', '0698445151', '', '2025-07-19 10:52:54', 0, NULL, NULL),
(1429, 'arrou', 'lokman', '0769957757', '', '2025-07-19 11:02:13', 0, NULL, NULL),
(1430, 'gourdon', 'maxime', '33783111968', '', '2025-07-19 13:10:11', 0, NULL, NULL),
(1431, 'montanelli', 'ludovic', '0617145874', '', '2025-07-19 14:03:17', 0, NULL, NULL),
(1432, 'Rahmoun', 'Weissi', '33680670844', '', '2025-07-22 06:04:26', 0, NULL, NULL),
(1433, 'schoch', 'raoul', '33626618676', '', '2025-07-22 06:26:37', 0, NULL, NULL),
(1434, 'dauti', 'ermand', '33768157454', '', '2025-07-22 06:49:05', 0, NULL, NULL),
(1435, 'toumi', 'hamid', '33758800324', '', '2025-07-22 10:39:31', 0, NULL, NULL),
(1436, 'sow', 'amadou', '33758880390', '', '2025-07-22 10:55:13', 0, NULL, NULL),
(1437, 'gueye', 'Amy', '33695954613', '', '2025-07-22 12:41:23', 0, NULL, NULL),
(1438, 'Elyatine', 'Farid', '33644059498', '', '2025-07-22 12:42:59', 0, NULL, NULL),
(1439, 'Monteiro', 'Dino', '33771787191', '', '2025-07-22 12:51:18', 0, NULL, NULL),
(1440, 'alain', 'hascoet', '33618641283', '', '2025-07-22 13:58:22', 0, NULL, NULL),
(1441, '2bprint', '2bprint', '0778068624', '', '2025-07-23 06:35:20', 0, NULL, NULL),
(1442, 'schneiderlin', 'anaelle', '33745315017', '', '2025-07-23 13:47:36', 0, NULL, NULL),
(1443, 'mercurio', 'cedric', '33658055625', '', '2025-07-24 11:59:42', 0, NULL, NULL),
(1444, 'bogojevic', 'david', '33604072854', '', '2025-07-24 12:09:25', 0, NULL, NULL),
(1445, 'le guludec', 'jemery', '33613626898', '', '2025-07-24 12:16:47', 0, NULL, NULL),
(1446, 'yahiaoui', 'Nadjet', '33760181693', '', '2025-07-24 13:21:03', 0, NULL, NULL),
(1447, 'chanoufi', 'hamza', '33613216330', '', '2025-07-24 14:02:23', 0, NULL, NULL),
(1448, 'Dumas', 'Fabrice', '33750993910', '', '2025-07-24 14:33:27', 0, NULL, NULL),
(1449, 'Fartadé', 'Alex', '33749877083', '', '2025-07-25 06:28:34', 0, NULL, NULL),
(1450, 'delporte', 'djany', '33782051127', '', '2025-07-25 14:32:35', 0, NULL, NULL),
(1451, 'tliba', 'abdelkader', '33620246595', '', '2025-07-26 06:12:28', 0, NULL, NULL),
(1452, 'Matiatos', 'Nathan', '33768128339', '', '2025-07-26 06:56:56', 0, NULL, NULL),
(1453, 'fournier', 'enzo', '33638109469', '', '2025-07-26 07:16:04', 0, NULL, NULL),
(1454, 'wassim', 'habibi', '33779546390', '', '2025-07-26 07:39:33', 0, NULL, NULL),
(1455, 'bourdier', 'maeva', '33643109053', '', '2025-07-26 07:52:11', 0, NULL, NULL),
(1456, 'Liberali', 'Florian', '33763137633', '', '2025-07-26 08:17:32', 0, NULL, NULL),
(1457, 'hamza', 'jaouadi', '33603868796', '', '2025-07-26 10:58:51', 0, NULL, NULL),
(1458, 'duque', 'diana', '33605569235', '', '2025-07-26 13:24:32', 0, NULL, NULL),
(1459, 'Gomes Tavares', 'Manuel', '33658585048', '', '2025-07-29 06:03:42', 0, NULL, NULL),
(1460, 'iovino', 'enzo', '33695897888', '', '2025-07-29 07:23:14', 0, NULL, NULL),
(1461, 'fahmi', 'kahwagi', '33753128483', '', '2025-07-29 10:08:46', 0, NULL, NULL),
(1462, 'maratea', 'eric', '33661136995', '', '2025-07-29 10:58:15', 0, NULL, NULL),
(1463, 'maillot', 'michelle', '33603113653', '', '2025-07-29 11:39:53', 0, NULL, NULL),
(1464, 'heitzler', 'jason', '33668398656', '', '2025-07-29 12:06:48', 0, NULL, NULL),
(1465, 'butti', 'amir', '33782635047', '', '2025-07-29 12:51:33', 0, NULL, NULL),
(1466, 'Montepagano', 'Daniel', '33662577253', '', '2025-07-29 12:56:53', 0, NULL, NULL),
(1467, 'Bigot', 'Cyril', '33745301669', '', '2025-07-29 12:59:41', 0, NULL, NULL),
(1468, 'loudhaief', 'nadir', '33783166430', '', '2025-07-29 13:54:08', 0, NULL, NULL),
(1469, 'gras', 'alexandre', '33658839251', '', '2025-07-30 06:24:34', 0, NULL, NULL),
(1470, 'SOUARE', 'jimmy', '33614632733', '', '2025-07-30 07:18:48', 0, NULL, NULL),
(1471, 'hammerschmidt', 'elodie', '33780238821', '', '2025-07-30 07:38:27', 0, NULL, NULL),
(1472, 'belhadj', 'kamel', '33613055645', '', '2025-07-30 10:36:17', 0, NULL, NULL),
(1473, 'moreira', 'edi', '33662380209', '', '2025-07-30 10:44:21', 0, NULL, NULL),
(1474, 'minoni', 'sadok', '33782649063', '', '2025-07-30 11:26:25', 0, NULL, NULL),
(1475, 'hamdi', 'mohamed', '33751166077', '', '2025-07-30 13:45:00', 0, NULL, NULL),
(1476, 'hamed', 'naffouti', '33752556285', '', '2025-07-31 06:34:56', 0, NULL, NULL),
(1477, 'establier', 'emanuelle', '33612289292', '', '2025-07-31 06:39:00', 0, NULL, NULL),
(1478, 'feuyit', 'ghislain', '33751571374', '', '2025-07-31 07:00:08', 0, NULL, NULL),
(1479, 'fabrfabrizj', 'christine', '33660908425', '', '2025-07-31 07:28:17', 0, NULL, NULL),
(1480, 'bertoni', 'louis', '33659472818', '', '2025-07-31 11:07:54', 0, NULL, NULL),
(1481, 'clayette', 'denis', '33664125872', '', '2025-07-31 11:11:04', 0, NULL, NULL),
(1482, 'badet', 'philipe', '33660551055', '', '2025-07-31 11:18:58', 0, NULL, NULL),
(1483, 'ugo', 'simon', '33659029859', '', '2025-07-31 11:27:20', 0, NULL, NULL),
(1484, 'moreira', 'edi', '33762380209', '', '2025-07-31 11:32:41', 0, NULL, NULL),
(1485, 'mahrez', 'MEhdi', '33684877004', '', '2025-07-31 12:35:20', 0, NULL, NULL),
(1486, 'plataroti', 'felix', '33617589886', '', '2025-07-31 13:21:15', 0, NULL, NULL),
(1487, 'Squarcioni', 'Laurence', '33609845683', '', '2025-07-31 15:03:29', 0, NULL, NULL),
(1488, 'Kaci', 'Reza', '33750931652', '', '2025-08-01 06:02:51', 0, NULL, NULL),
(1489, 'bosques', 'olivier', '33666467756', '', '2025-08-01 12:03:30', 0, NULL, NULL),
(1490, 'nainee', 'xiavier', '33668150238', '', '2025-08-01 12:51:43', 0, NULL, NULL),
(1491, 'Viatoslav', 'Sebano', '33759330874', '', '2025-08-01 15:04:27', 0, NULL, NULL),
(1492, 'abed', 'hichem', '336621012143', '', '2025-08-02 06:44:40', 0, NULL, NULL),
(1493, 'abed', 'Hichem', '33662101213', '', '2025-08-02 06:53:21', 0, NULL, NULL),
(1494, 'lahmar', 'allan', '33782481775', '', '2025-08-02 07:34:02', 0, NULL, NULL),
(1495, 'willey', 'robert', '33675020285', '', '2025-08-02 10:01:48', 0, NULL, NULL),
(1496, 'Sensey', 'Tim', '33695171462', '', '2025-08-02 10:38:08', 0, NULL, NULL),
(1497, 'karmous', 'bassem', '337514270287', '', '2025-08-02 12:54:58', 0, NULL, NULL),
(1498, 'lalabbe', 'isabelle', '33761925172', '', '2025-08-02 13:47:22', 0, NULL, NULL),
(1499, 'escude', 'monica', '33770441593', '', '2025-08-05 06:19:45', 0, NULL, NULL),
(1500, 'mouillot', 'mauro', '33618945472', '', '2025-08-05 06:26:05', 0, NULL, NULL),
(1501, 'carlton', 'philipe', '33626135760', '', '2025-08-05 07:43:47', 0, NULL, NULL),
(1502, 'errabahy', 'fadwa', '33667800581', '', '2025-08-05 10:03:48', 0, NULL, NULL),
(1503, 'dahmani', 'hatab', '33658588278', '', '2025-08-05 10:31:49', 0, NULL, NULL),
(1504, 'segura', 'adrien', '33620780023', '', '2025-08-05 10:59:16', 0, NULL, NULL),
(1505, 'taricco', 'romain', '33783668121', '', '2025-08-05 11:31:49', 0, NULL, NULL),
(1506, 'lefevvre', 'lucalucas', '33661235552', '', '2025-08-05 12:58:04', 0, NULL, NULL),
(1507, 'hassine', 'abde', '33744748873', '', '2025-08-06 06:18:53', 0, NULL, NULL),
(1508, '...', 'rahad', '33705513026', '', '2025-08-06 07:41:11', 0, NULL, NULL),
(1509, 'malek', 'benyousef', '33751518630', '', '2025-08-06 08:02:52', 0, NULL, NULL),
(1510, 'perez', 'cedic', '33641748642', '', '2025-08-06 11:04:26', 0, NULL, NULL),
(1511, 'tavares', 'davidsone', '33652461312', '', '2025-08-06 11:15:24', 0, NULL, NULL),
(1512, 'chevalier', 'alexandre', '33667755581', '', '2025-08-06 12:10:16', 0, NULL, NULL),
(1513, 'bejaoui', 'alaedin', '33668866386', '', '2025-08-06 12:19:30', 0, NULL, NULL),
(1514, 'mondain', 'nicolas', '33663469881', '', '2025-08-06 13:16:52', 0, NULL, NULL),
(1515, 'boufenchouche', 'nesrine', '33699800457', '', '2025-08-06 13:45:54', 0, NULL, NULL),
(1516, 'gile', 'nathalie', '33665231703', '', '2025-08-06 14:01:14', 0, NULL, NULL),
(1517, 'jridi', 'mohamed', '33782343986', '', '2025-08-06 14:49:23', 0, NULL, NULL),
(1518, 'dieude', 'gabriel', '33677227544', '', '2025-08-07 10:56:27', 0, NULL, NULL),
(1519, 'jimy', 'esteo', '060606060606', '', '2025-08-07 11:32:13', 0, NULL, NULL),
(1520, 'elsasi', 'sammy', '33763748910', '', '2025-08-07 12:11:34', 0, NULL, NULL),
(1521, 'ismail', 'ismIL', '33755670111', '', '2025-08-07 12:17:01', 0, NULL, NULL),
(1522, 'velasco', 'eda', '33678014501', '', '2025-08-07 14:04:21', 0, NULL, NULL),
(1523, 'belaidi', 'hassan', '33652943794', '', '2025-08-08 06:45:05', 0, NULL, NULL),
(1524, 'calzi', 'manuelle', '33631182905', '', '2025-08-08 07:44:51', 0, NULL, NULL),
(1525, 'kechichian', 'sevag', '33749204642', '', '2025-08-08 10:08:08', 0, NULL, NULL),
(1526, 'bilen', 'chaouali', '337450946', '', '2025-08-08 10:26:02', 0, NULL, NULL),
(1527, 'bs', 'automobile', '33621203945', '', '2025-08-08 13:37:35', 0, NULL, NULL),
(1528, 'demon', 'lea', '33667901107', '', '2025-08-08 14:02:29', 0, NULL, NULL),
(1529, 'dgiby', 'sy', '337587811225', '', '2025-08-09 06:44:56', 0, NULL, NULL),
(1530, 'hamri', 'ichem', '33649895150', '', '2025-08-09 07:06:19', 0, NULL, NULL),
(1531, 'rebih', 'anita', '33663151453', '', '2025-08-09 07:43:59', 0, NULL, NULL),
(1532, 'carmeiro', 'joaquim', '33616883407', '', '2025-08-09 10:58:53', 0, NULL, NULL),
(1533, 'ksouri', 'brahim', '33760200558', '', '2025-08-09 12:52:25', 0, NULL, NULL),
(1534, 'pinto', 'jorge', '33627021149', '', '2025-08-09 14:02:31', 0, NULL, NULL),
(1535, 'ramiro', 'nicole', '33661115540', '', '2025-08-12 06:09:32', 0, NULL, NULL),
(1536, 'sasi', 'oussan', '33754248549', '', '2025-08-12 06:13:20', 0, NULL, NULL),
(1537, 'duvauchelle', 'francoise', '33614984626', '', '2025-08-12 06:45:36', 0, NULL, NULL),
(1538, 'mendes', 'hirondina', '33755713595', '', '2025-08-12 07:02:55', 0, NULL, NULL),
(1539, 'ortega', 'michelle', '33674006498', '', '2025-08-12 10:28:05', 0, NULL, NULL),
(1540, 'naffti', 'lyes', '33665687447', '', '2025-08-12 13:27:29', 0, NULL, NULL),
(1541, 'guezguez', 'hamdi', '33767866029', '', '2025-08-12 13:39:19', 0, NULL, NULL),
(1542, 'nguyen', 'clara', '33631091450', '', '2025-08-13 06:50:35', 0, NULL, NULL),
(1543, 'defoor', 'jean marc', '33628560933', '', '2025-08-13 07:01:29', 0, NULL, NULL),
(1544, 'aymen', 'tlich', '33634891482', '', '2025-08-13 08:33:37', 0, NULL, NULL),
(1545, 'grosso', 'emanuelle', '33620297080', '', '2025-08-13 10:51:28', 0, NULL, NULL),
(1546, 'facher', 'dorian', '33761977551', '', '2025-08-13 11:28:32', 0, NULL, NULL),
(1547, 'tagomel', 'maxime', '33625749351', '', '2025-08-13 13:16:16', 0, NULL, NULL),
(1548, 'da ponte', 'victor', '33609665823', '', '2025-08-13 13:49:02', 0, NULL, NULL),
(1549, 'amdouni', 'moiunira', '33664270912', '', '2025-08-14 08:29:55', 0, NULL, NULL),
(1550, 'michau', 'nathalie', '33683970512', '', '2025-08-14 10:54:29', 0, NULL, NULL),
(1551, 'Thomas', 'Lucas', '33608089201', '', '2025-08-14 14:29:45', 0, NULL, NULL),
(1552, 'marcos das silva', 'william', '33758926540', '', '2025-08-15 06:04:58', 0, NULL, NULL),
(1553, 'hamza', 'elgares', '33615388146', '', '2025-08-15 12:24:06', 0, NULL, NULL),
(1554, 'luciano', 'bernard', '33760502842', '', '2025-08-16 06:59:07', 0, NULL, NULL),
(1555, 'khalile', 'chebbi', '33780158521', '', '2025-08-16 10:04:53', 0, NULL, NULL),
(1556, 'trovato picardi', 'karine', '33661449179', '', '2025-08-16 10:13:49', 0, NULL, NULL),
(1557, 'irles', 'anne marie', '000000000000', '', '2025-08-16 11:28:11', 0, NULL, NULL),
(1558, 'gros', 'francis', '33603510126', '', '2025-08-19 06:33:45', 0, NULL, NULL),
(1559, 'harley', 'david', '33650820275', '', '2025-08-19 06:53:42', 0, NULL, NULL),
(1560, 'saccone', 'sciro', '33751124034', '', '2025-08-19 07:13:05', 0, NULL, NULL),
(1561, 'schallwig', 'laurent', '33686449389', '', '2025-08-19 07:19:21', 0, NULL, NULL),
(1562, 'sahbi', 'karim', '33609859425', '', '2025-08-19 10:42:48', 0, NULL, NULL),
(1563, 'maniscalco', 'fabrice', '33760412677', '', '2025-08-19 11:36:51', 0, NULL, NULL),
(1564, 'viel', 'jonathan', '33767268472', '', '2025-08-19 12:31:55', 0, NULL, NULL),
(1565, 'benesine', 'fatma', '33620674430', '', '2025-08-19 12:39:32', 0, NULL, NULL),
(1566, 'denisoul tanov', 'Ilan', '33749919701', '', '2025-08-20 06:13:27', 0, NULL, NULL),
(1567, 'ndiaye', 'zackaria', '33662802773', '', '2025-08-20 07:51:12', 0, NULL, NULL),
(1568, 'kaone', 'aboubaka', '336958149369', '', '2025-08-20 08:09:17', 0, NULL, NULL),
(1569, 'semedo', 'sabrina', '33656791147', '', '2025-08-20 10:07:24', 0, NULL, NULL),
(1570, 'giroaud', 'thomas', '33786390168', '', '2025-08-20 12:01:52', 0, NULL, NULL),
(1571, 'pitchen', 'cedric', '00000000000000', '', '2025-08-20 12:42:09', 0, NULL, NULL),
(1572, 'lahimme', 'saad', '33661351522', '', '2025-08-21 08:13:54', 0, NULL, NULL),
(1573, 'zaini', 'mohammed', '33641250295', '', '2025-08-21 08:16:19', 0, NULL, NULL),
(1574, 'ambergny', 'eddy', '33695274244', '', '2025-08-21 11:34:39', 0, NULL, NULL),
(1575, 'rothman', 'max', '33610392965', '', '2025-08-21 12:20:59', 0, NULL, NULL),
(1576, 'gader', 'bechir', '33780700243', '', '2025-08-22 10:20:23', 0, NULL, NULL),
(1577, 'lopez', 'nestor', '33618357334', '', '2025-08-22 10:39:26', 0, NULL, NULL),
(1578, 'bouzidi', 'shudi', '33758498584', '', '2025-08-22 11:14:22', 0, NULL, NULL),
(1579, 'dagomel', 'mateo', '33781505063', '', '2025-08-22 14:15:56', 0, NULL, NULL),
(1580, 'gomes', 'sylviane', '33781218524', '', '2025-08-23 07:51:49', 0, NULL, NULL);
INSERT INTO `clients` (`id`, `nom`, `prenom`, `telephone`, `email`, `date_creation`, `inscrit_parrainage`, `code_parrainage`, `date_inscription_parrainage`) VALUES
(1581, 'ken', 'victor', '33607414848', '', '2025-08-26 07:44:33', 0, NULL, NULL),
(1582, 'loudhaief', 'rayane', '33745600830', '', '2025-08-26 07:47:23', 0, NULL, NULL),
(1583, 'benyoub', 'lea', '33624061181', '', '2025-08-26 08:34:18', 0, NULL, NULL),
(1584, 'jayet', 'julie', '33766510794', '', '2025-08-26 10:31:13', 0, NULL, NULL),
(1585, 'schallwig', 'laurent', '33762762832', '', '2025-08-26 10:48:06', 0, NULL, NULL),
(1586, 'said', 'hussain', '33627277777', '', '2025-08-26 11:36:32', 0, NULL, NULL),
(1587, 'ssaad', 'imed', '33776905801', '', '2025-08-26 11:49:19', 0, NULL, NULL),
(1588, 'ventura', 'salvator', '33757181252', '', '2025-08-26 11:55:24', 0, NULL, NULL),
(1589, 'romain', 'janne', '33666969407', '', '2025-08-26 12:56:40', 0, NULL, NULL),
(1590, 'blanc', 'perinne', '33659297545', '', '2025-08-26 13:17:00', 0, NULL, NULL),
(1591, 'soares', 'josez', '33619316711', '', '2025-08-27 10:15:15', 0, NULL, NULL),
(1592, 'ventura', 'salvator', '33757181256', '', '2025-08-27 11:13:49', 0, NULL, NULL),
(1593, 'bennaceur', 'kais', '33650785373', '', '2025-08-27 11:32:23', 0, NULL, NULL),
(1594, 'ciret', 'jordane', '33625747070', '', '2025-08-27 12:21:11', 0, NULL, NULL),
(1595, 'lagardere', 'hartur', '33637707388', '', '2025-08-27 13:03:08', 0, NULL, NULL),
(1596, 'mendes', 'leonor', '33786322438', '', '2025-08-27 13:11:40', 0, NULL, NULL),
(1597, 'cabral', 'lucardo', '33763539041', '', '2025-08-28 07:48:07', 0, NULL, NULL),
(1598, 'fernanbes', 'lenoel', '33753940569', '', '2025-08-28 11:16:11', 0, NULL, NULL),
(1599, 'klinger', 'reynald', '33766784343', '', '2025-08-28 11:31:32', 0, NULL, NULL),
(1600, 'ihebguissem', 'ihebguissem', '33780708155', '', '2025-08-28 11:35:22', 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `clients_backup_20251103_230305`
--

CREATE TABLE `clients_backup_20251103_230305` (
  `id` int NOT NULL DEFAULT '0',
  `nom` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `prenom` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `telephone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `inscrit_parrainage` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Client inscrit au programme de parrainage ou non',
  `code_parrainage` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Code unique pour le parrainage (peut être null si pas inscrit)',
  `date_inscription_parrainage` timestamp NULL DEFAULT NULL COMMENT 'Date d''inscription au programme de parrainage'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `clients_backup_20251103_230305`
--

INSERT INTO `clients_backup_20251103_230305` (`id`, `nom`, `prenom`, `telephone`, `email`, `date_creation`, `inscrit_parrainage`, `code_parrainage`, `date_inscription_parrainage`) VALUES
(1, 'saber', 'guezguez', '33782962906', NULL, '2025-09-23 08:20:00', 0, NULL, NULL),
(2, 'test', 'test', '33788998899', NULL, '2025-10-09 21:40:29', 0, NULL, NULL),
(3, 'test', 'test', '12345678908', NULL, '2025-10-09 21:50:37', 0, NULL, NULL),
(4, 'test', 'test3', '12345678909', NULL, '2025-10-09 22:03:03', 0, NULL, NULL),
(5, 'asd', 'ads', '12345678909', '', '2025-10-09 22:12:20', 0, NULL, NULL),
(6, 'ads', 'ads', '12345678909', '', '2025-10-09 22:32:44', 0, NULL, NULL),
(7, 'asd', 'dsa', '33123456789', '', '2025-10-09 23:17:14', 0, NULL, NULL),
(8, 'dupont', 'jean', '33782962906', '', '2025-10-20 19:17:01', 0, NULL, NULL),
(9, 'sad', 'das', '12345678902', NULL, '2025-11-02 21:30:47', 0, NULL, NULL),
(10, 'asddas', 'asddsa', '12345678912', NULL, '2025-11-02 21:43:38', 0, NULL, NULL),
(11, 'dashdash', 'iaduyg', '66123456789', NULL, '2025-11-02 21:44:11', 0, NULL, NULL),
(12, 'dashdash', 'iaduyg', '66123456787', NULL, '2025-11-02 21:47:19', 0, NULL, NULL),
(13, 'dashdash', 'iaduyg', '66123456780', NULL, '2025-11-02 21:51:11', 0, NULL, NULL),
(14, 'asd', 'ads', '22123456765', NULL, '2025-11-02 21:51:26', 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `colis_retour`
--

CREATE TABLE `colis_retour` (
  `id` int NOT NULL,
  `numero_suivi` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_creation` datetime DEFAULT CURRENT_TIMESTAMP,
  `date_expedition` datetime DEFAULT NULL,
  `statut` enum('en_preparation','en_expedition','livre') COLLATE utf8mb4_unicode_ci DEFAULT 'en_preparation',
  `notes` text COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `commandes_fournisseurs`
--

CREATE TABLE `commandes_fournisseurs` (
  `id` int NOT NULL,
  `fournisseur_id` int NOT NULL,
  `date_commande` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `statut` enum('en_attente','validee','recue','annulee') COLLATE utf8mb4_unicode_ci DEFAULT 'en_attente',
  `montant_total` decimal(10,2) DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `user_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `commandes_pieces`
--

CREATE TABLE `commandes_pieces` (
  `id` int NOT NULL,
  `reference` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `reparation_id` int DEFAULT NULL,
  `client_id` int DEFAULT NULL,
  `fournisseur_id` int NOT NULL,
  `nom_piece` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `code_barre` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `quantite` int NOT NULL DEFAULT '1',
  `prix_estime` decimal(10,2) DEFAULT NULL,
  `commentaire_interne` text COLLATE utf8mb4_general_ci,
  `note_interne` text COLLATE utf8mb4_general_ci,
  `urgence` enum('normal','urgent','tres_urgent') COLLATE utf8mb4_general_ci DEFAULT 'normal',
  `statut` enum('en_attente','commande','recue','annulee','urgent','termine','utilise','a_retourner') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'en_attente',
  `date_commande` datetime DEFAULT NULL,
  `date_reception` datetime DEFAULT NULL,
  `notes` text COLLATE utf8mb4_general_ci,
  `date_creation` datetime DEFAULT CURRENT_TIMESTAMP,
  `date_modification` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `commandes_pieces`
--

INSERT INTO `commandes_pieces` (`id`, `reference`, `reparation_id`, `client_id`, `fournisseur_id`, `nom_piece`, `code_barre`, `description`, `quantite`, `prix_estime`, `commentaire_interne`, `note_interne`, `urgence`, `statut`, `date_commande`, `date_reception`, `notes`, `date_creation`, `date_modification`) VALUES
(1, 'CMD-20250930-9698', NULL, 1, 2, 'asd', '2', NULL, 1, 2.00, NULL, NULL, 'normal', 'recue', NULL, '2025-10-24 19:59:16', NULL, '2025-09-30 01:38:24', '2025-10-24 19:59:16'),
(2, 'CMD-20250930-1944', NULL, 1, 2, 'dsadsa', '22', NULL, 1, 22.00, NULL, NULL, 'normal', 'recue', NULL, '2025-10-24 19:59:19', NULL, '2025-09-30 01:38:24', '2025-10-24 19:59:19'),
(3, 'CMD-20251001-68dc89832bc11', 5, 1, 11, 'ADW', NULL, 'ADS', 1, 2.00, NULL, NULL, 'normal', 'recue', NULL, '2025-10-24 19:59:13', NULL, '2025-10-01 03:53:07', '2025-10-24 19:59:13'),
(4, 'CMD-20251008-2495', NULL, 1, 11, 'das', 'asd', NULL, 1, 2.00, NULL, NULL, 'normal', 'recue', NULL, '2025-10-12 21:10:10', NULL, '2025-10-08 01:41:12', '2025-10-12 21:10:10'),
(5, 'CMD-20251009-4157', NULL, 1, 11, 'asd', 'ads', NULL, 1, 21.00, NULL, NULL, 'normal', 'commande', '2025-10-12 21:07:27', NULL, NULL, '2025-10-09 23:50:08', '2025-10-12 21:07:27'),
(7, 'CMD-20251010-2410', NULL, 4, 2, 'das', 'ads', NULL, 1, 21.00, NULL, NULL, 'normal', 'commande', '2025-10-12 21:07:11', NULL, NULL, '2025-10-10 00:03:12', '2025-10-12 21:07:11'),
(8, 'CMD-20251020-8308', NULL, 5, 11, 'asd', '12', NULL, 1, 12.00, NULL, NULL, 'normal', 'a_retourner', NULL, NULL, NULL, '2025-10-20 18:19:25', '2025-10-24 19:59:10'),
(9, 'CMD-20251020-7823', NULL, 5, 2, 'asd', 'dsa', NULL, 1, 21.00, NULL, NULL, 'normal', 'recue', NULL, '2025-10-24 19:59:21', NULL, '2025-10-20 18:19:42', '2025-10-24 19:59:21'),
(10, 'CMD-20251020-2197', NULL, 1, 11, 'ads', 'das', NULL, 1, 22.00, NULL, NULL, 'normal', 'utilise', NULL, NULL, NULL, '2025-10-20 18:20:11', '2025-10-24 19:59:06'),
(11, 'CMD-20251102-3112', NULL, 14, 11, 'asd', 'das', NULL, 1, 22.00, NULL, NULL, 'normal', 'recue', NULL, '2025-11-04 00:04:11', NULL, '2025-11-02 22:51:34', '2025-11-04 00:04:11'),
(12, 'CMD-20251102-6559', NULL, 10, 11, 'ads', 'das', NULL, 1, 2.00, NULL, NULL, 'normal', 'recue', NULL, '2025-11-04 00:04:11', NULL, '2025-11-02 23:31:47', '2025-11-04 00:04:11'),
(13, 'CMD-20251109-690fe62cd2863', 2140, 1279, 2, 'asd', '213', NULL, 1, 21.00, NULL, NULL, 'normal', 'a_retourner', NULL, NULL, NULL, '2025-11-09 01:54:04', '2025-11-10 13:12:45');

-- --------------------------------------------------------

--
-- Structure de la table `commentaires_tache`
--

CREATE TABLE `commentaires_tache` (
  `id` int NOT NULL,
  `tache_id` int NOT NULL,
  `user_id` int NOT NULL,
  `commentaire` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_creation` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `is_system` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `company_settings`
--

CREATE TABLE `company_settings` (
  `id` int NOT NULL,
  `shop_id` int NOT NULL,
  `company_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `company_phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `company_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `company_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `company_address` text COLLATE utf8mb4_unicode_ci,
  `company_hours` text COLLATE utf8mb4_unicode_ci,
  `company_logo` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `company_settings`
--

INSERT INTO `company_settings` (`id`, `shop_id`, `company_name`, `company_phone`, `company_number`, `company_email`, `company_address`, `company_hours`, `company_logo`, `created_at`, `updated_at`) VALUES
(1, 63, 'MAISON DU GEEK', '66666', '12345', 'contact@mkmkmk.servo.tools', '123 Rue Example, 06000 Nice', '7/7', '', '2025-11-06 23:35:31', '2025-11-09 01:58:00');

-- --------------------------------------------------------

--
-- Structure de la table `confirmations_lecture`
--

CREATE TABLE `confirmations_lecture` (
  `id` int NOT NULL,
  `message_id` int NOT NULL,
  `employe_id` int NOT NULL,
  `date_confirmation` datetime DEFAULT NULL COMMENT 'NULL = non confirmé, datetime = confirmé à cette date',
  `rappel_envoye` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Indique si un rappel a été envoyé',
  `date_rappel` datetime DEFAULT NULL COMMENT 'Date et heure d''envoi du rappel'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `conges_demandes`
--

CREATE TABLE `conges_demandes` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `date_debut` date NOT NULL,
  `date_fin` date NOT NULL,
  `nb_jours` decimal(5,2) NOT NULL,
  `statut` enum('en_attente','approuve','refuse') COLLATE utf8mb4_unicode_ci DEFAULT 'en_attente',
  `type` enum('normal','impose') COLLATE utf8mb4_unicode_ci DEFAULT 'normal',
  `commentaire` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_by` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `conges_jours_disponibles`
--

CREATE TABLE `conges_jours_disponibles` (
  `id` int NOT NULL,
  `date` date NOT NULL,
  `disponible` tinyint(1) DEFAULT '1',
  `created_by` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `conges_solde`
--

CREATE TABLE `conges_solde` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `solde_actuel` decimal(5,2) NOT NULL DEFAULT '0.00',
  `date_derniere_maj` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `conversations`
--

CREATE TABLE `conversations` (
  `id` int NOT NULL,
  `titre` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('direct','groupe','annonce') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'direct',
  `created_by` int DEFAULT NULL,
  `date_creation` datetime DEFAULT CURRENT_TIMESTAMP,
  `derniere_activite` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `conversation_participants`
--

CREATE TABLE `conversation_participants` (
  `conversation_id` int NOT NULL,
  `user_id` int NOT NULL,
  `role` enum('admin','membre','lecteur') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'membre',
  `date_ajout` datetime DEFAULT CURRENT_TIMESTAMP,
  `date_derniere_lecture` datetime DEFAULT NULL,
  `est_favoris` tinyint(1) DEFAULT '0',
  `est_archive` tinyint(1) DEFAULT '0',
  `notification_mute` tinyint(1) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `demandes_retrait`
--

CREATE TABLE `demandes_retrait` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `montant` decimal(10,2) NOT NULL,
  `methode_paiement` enum('virement','paypal','especes') NOT NULL,
  `details_paiement` text NOT NULL,
  `statut` enum('en_attente','approuvee','refusee','payee') DEFAULT 'en_attente',
  `commentaire_admin` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `processed_at` timestamp NULL DEFAULT NULL,
  `processed_by` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `devis`
--

CREATE TABLE `devis` (
  `id` int NOT NULL,
  `reparation_id` int NOT NULL,
  `client_id` int NOT NULL,
  `employe_id` int NOT NULL,
  `numero_devis` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `titre` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description_generale` text COLLATE utf8mb4_unicode_ci,
  `statut` enum('brouillon','envoye','accepte','refuse','expire') COLLATE utf8mb4_unicode_ci DEFAULT 'brouillon',
  `date_creation` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `date_envoi` timestamp NULL DEFAULT NULL,
  `date_reponse` timestamp NULL DEFAULT NULL,
  `date_expiration` timestamp NULL DEFAULT NULL,
  `lien_securise` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `total_ht` decimal(10,2) DEFAULT '0.00',
  `taux_tva` decimal(5,2) DEFAULT '20.00',
  `total_ttc` decimal(10,2) DEFAULT '0.00',
  `solution_choisie_id` int DEFAULT NULL,
  `notes_acceptation` text COLLATE utf8mb4_unicode_ci,
  `ip_client` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `devis`
--

INSERT INTO `devis` (`id`, `reparation_id`, `client_id`, `employe_id`, `numero_devis`, `titre`, `description_generale`, `statut`, `date_creation`, `date_envoi`, `date_reponse`, `date_expiration`, `lien_securise`, `total_ht`, `taux_tva`, `total_ttc`, `solution_choisie_id`, `notes_acceptation`, `ip_client`, `user_agent`) VALUES
(1, 1, 1, 63, 'DV-2025-0001', 'asd', '231', 'accepte', '2025-09-23 23:44:47', '2025-09-24 00:13:23', '2025-09-24 00:17:53', '2025-10-07 22:00:00', '5745323e5c856d8f206d5cf676ad29cb', 2.00, 20.00, 2.40, 1, NULL, NULL, NULL),
(2, 2, 1, 63, 'DV-2025-0002', 'Réparation iPhone 12', '', 'accepte', '2025-09-24 15:17:35', '2025-09-24 15:17:35', '2025-09-24 22:43:22', '2025-10-07 22:00:00', 'e1b2b34ec76c64e29640f8deccb71db2', 198.00, 20.00, 237.60, 2, NULL, NULL, NULL),
(3, 2, 1, 63, 'DV-2025-0003', 'Reparation iPhone 12', 'Description appareil etape 1', 'accepte', '2025-09-24 23:26:22', '2025-09-24 23:26:22', '2025-09-24 23:32:05', '2025-10-08 22:00:00', 'eb739f36bba0b122372e543211260acc', 24.00, 20.00, 28.80, 5, NULL, NULL, NULL),
(4, 2, 1, 63, 'DV-2025-0004', 'Reparation iphone 12', '', 'refuse', '2025-09-24 23:34:31', '2025-09-24 23:34:31', '2025-09-24 23:35:43', '2025-10-08 22:00:00', '7a89c5ec5f84b49062508fc42590e22a', 89.00, 20.00, 106.80, NULL, '', NULL, NULL),
(5, 2, 1, 63, 'DV-2025-0005', 'ikjuh', 'gvhj', 'envoye', '2025-09-24 23:36:23', '2025-11-09 20:56:28', '2025-09-24 23:36:36', '2025-10-28 01:20:48', '11820d115068a3f65137bf89ce5c484d', 24.00, 20.00, 28.80, NULL, '', NULL, NULL),
(6, 4, 1, 63, 'DV-2025-0006', 'reparation iphone 12', 'SN / f5sfd4fds5sfd4', 'accepte', '2025-09-25 10:29:50', '2025-09-25 10:29:50', '2025-09-25 10:31:08', '2025-10-08 22:00:00', 'a003621ffbccc941e0a964bd9e101393', 247.00, 20.00, 296.40, 11, NULL, NULL, NULL),
(7, 3, 1, 63, 'DV-2025-0007', 'reparation iPhone 12', 'SN / edewf3312', 'expire', '2025-09-27 17:19:34', '2025-10-21 00:21:22', '2025-09-27 17:20:39', '2025-10-28 01:21:09', '1f661d66811c582a2e566c80e2ba0823', 218.00, 20.00, 261.60, 12, NULL, NULL, NULL),
(8, 12, 1, 63, 'DV-2025-0008', 'Reparation iphone 12', 'SN : dfs146dfs1dsf51dfgs56', 'accepte', '2025-11-05 17:19:41', '2025-11-05 17:19:41', '2025-11-05 17:21:57', '2025-11-18 23:00:00', '9510f7795abf9d12595a2ad102e2568b', 430.00, 20.00, 516.00, 16, NULL, NULL, NULL),
(9, 2140, 1279, 63, 'DV-2025-0009', 'Reparation iphone 13', 'SN / 22138', 'envoye', '2025-11-08 23:16:12', '2025-11-09 20:56:28', NULL, '2025-11-22 23:00:00', '7709a45308176ecfb64d81db2d39a94a', 3.00, 20.00, 3.60, NULL, NULL, NULL, NULL),
(10, 13, 7, 63, 'DV-2025-0010', 'adfdas', 'sadsda', 'accepte', '2025-11-08 23:20:35', '2025-11-08 23:20:35', '2025-11-08 23:20:54', '2025-11-22 23:00:00', 'f62b5a9d5fae0f455be46b6e2df2e69f', 22.00, 20.00, 26.40, 17, NULL, NULL, NULL),
(11, 12, 1, 63, 'DV-2025-0011', 'oikjuh', 'qwsedfghj', 'envoye', '2025-11-08 23:24:35', '2025-11-09 20:56:28', NULL, '2025-11-22 23:00:00', '8f27a383f262c623ee44b959feb13c78', 33.00, 20.00, 39.60, NULL, NULL, NULL, NULL);

--
-- Déclencheurs `devis`
--
DELIMITER $$
CREATE TRIGGER `generate_devis_number` BEFORE INSERT ON `devis` FOR EACH ROW BEGIN
    DECLARE next_number BIGINT DEFAULT 1$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `log_devis_status_change` AFTER UPDATE ON `devis` FOR EACH ROW BEGIN
    IF OLD.statut != NEW.statut THEN
        INSERT INTO devis_logs (devis_id, action, description, utilisateur_type, date_action)
        VALUES (NEW.id, CONCAT('STATUT_CHANGE_', NEW.statut), CONCAT('Statut changé de "', OLD.statut, '" vers "', NEW.statut, '"'), 'systeme', NOW())$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `devis_acceptations`
--

CREATE TABLE `devis_acceptations` (
  `id` int NOT NULL,
  `devis_id` int NOT NULL,
  `solution_choisie_id` int NOT NULL,
  `signature_client` longtext COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Signature électronique en base64',
  `nom_complet` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telephone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `adresse` text COLLATE utf8mb4_unicode_ci,
  `date_acceptation` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `ip_client` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `conditions_acceptees` tinyint(1) DEFAULT '1',
  `newsletter_acceptee` tinyint(1) DEFAULT '0',
  `hash_verification` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Hash pour vérifier l intégrité'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `devis_acceptations`
--

INSERT INTO `devis_acceptations` (`id`, `devis_id`, `solution_choisie_id`, `signature_client`, `nom_complet`, `email`, `telephone`, `adresse`, `date_acceptation`, `ip_client`, `user_agent`, `conditions_acceptees`, `newsletter_acceptee`, `hash_verification`) VALUES
(1, 1, 1, 'data:image/png;base64,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', 'saber guezguez', '', '33782962906', NULL, '2025-09-24 00:17:53', '84.98.112.56', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 1, 0, 'badf56cd0912742d384e044ed8eb852b3012a0d670a9319b1dfb2163bbce0a9c'),
(2, 2, 2, 'data:image/png;base64,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', 'saber guezguez', '', '33782962906', NULL, '2025-09-24 22:43:22', '84.98.112.56', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.0 Mobile/15E148 Safari/604.1', 1, 0, 'c29977dc6f5e5be996b32258d50d3c07619a49dd84bdc118593880f93005ee33'),
(3, 3, 5, 'data:image/png;base64,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', 'saber guezguez', '', '33782962906', NULL, '2025-09-24 23:32:05', '84.98.112.56', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Mobile Safari/537.36', 1, 0, '98b2968f7d50bca3194dc95e1d8ca9e884e4cf36add99fb1353ec563a6b138eb'),
(4, 6, 11, 'data:image/png;base64,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', 'saber guezguez', '', '33782962906', NULL, '2025-09-25 10:31:08', '109.210.27.45', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 1, 0, '24d9180a6bd75970efb593a8461bd783a3542fc82280a8fa115b65c31178fd4d'),
(5, 7, 12, 'data:image/png;base64,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', 'saber guezguez', '', '33782962906', NULL, '2025-09-27 17:20:39', '109.210.27.45', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.0 Mobile/15E148 Safari/604.1', 1, 0, '6554355c5d68597327fc2795f83f3a9060538f3473e7604d20e7d9a171a67afb');
INSERT INTO `devis_acceptations` (`id`, `devis_id`, `solution_choisie_id`, `signature_client`, `nom_complet`, `email`, `telephone`, `adresse`, `date_acceptation`, `ip_client`, `user_agent`, `conditions_acceptees`, `newsletter_acceptee`, `hash_verification`) VALUES
(6, 8, 16, 'data:image/png;base64,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', 'saber guezguez', NULL, '33782962906', NULL, '2025-11-05 17:21:57', '109.210.27.45', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.0.1 Mobile/15E148 Safari/604.1', 1, 0, 'a0642377327ef7056edefe6d2c63e176c7b2572a817b309bbc1796075baf2dd6'),
(7, 10, 17, 'data:image/png;base64,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', 'asd dsa', '', '33123456789', NULL, '2025-11-08 23:20:54', '84.98.112.56', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 1, 0, 'e479f2720a9def39da00232c75649df3a53f5f8d66f15dbb00e1877829874e94');

-- --------------------------------------------------------

--
-- Structure de la table `devis_logs`
--

CREATE TABLE `devis_logs` (
  `id` int NOT NULL,
  `devis_id` int NOT NULL,
  `action` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `utilisateur_type` enum('employe','client','systeme') COLLATE utf8mb4_unicode_ci NOT NULL,
  `utilisateur_id` int DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `donnees_supplementaires` json DEFAULT NULL,
  `date_action` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `devis_logs`
--

INSERT INTO `devis_logs` (`id`, `devis_id`, `action`, `description`, `utilisateur_type`, `utilisateur_id`, `ip_address`, `user_agent`, `donnees_supplementaires`, `date_action`) VALUES
(1, 1, 'sms_renvoye', 'SMS renvoyé à 33782962906', 'employe', NULL, NULL, NULL, '{\"message\": \"⏰ Rappel guezguez !\\n\\nVotre devis expire dans 13 jours.\\n\\n📄 Consultez votre devis :\\n👉 https://mkmkmk.servo.tools/pages/devis_client.php?lien=5745323e5c856d8f206d5cf676ad29cb\\n📲 Suivi réparation :\\n👉 https://mkmkmk.servo.tools/suivi.php?id=1\\n\\n📞 Questions ? [COMPANY_PHONE]\\n🏠 [COMPANY_NAME]\", \"telephone\": \"33782962906\", \"type_devis\": \"en_attente\", \"template_utilise\": \"Devis en attente - Rappel\"}', '2025-09-23 23:58:52'),
(2, 1, 'sms_renvoye', 'SMS renvoyé à 33782962906', 'employe', NULL, NULL, NULL, '{\"message\": \"⏰ Rappel guezguez !\\n\\nVotre devis expire dans 13 jours.\\n\\n📄 Consultez votre devis :\\n👉 https://mkmkmk.servo.tools/pages/devis_client.php?lien=5745323e5c856d8f206d5cf676ad29cb\\n📲 Suivi réparation :\\n👉 https://mkmkmk.servo.tools/suivi.php?id=1\\n\\n📞 Questions ? [COMPANY_PHONE]\\n🏠 [COMPANY_NAME]\", \"telephone\": \"33782962906\", \"type_devis\": \"en_attente\", \"template_utilise\": \"Devis en attente - Rappel\"}', '2025-09-24 00:07:48'),
(3, 1, 'sms_renvoye', 'SMS renvoyé à 33782962906', 'employe', NULL, NULL, NULL, '{\"message\": \"⏰ Rappel guezguez !\\n\\nVotre devis expire dans 13 jours.\\n\\n📄 Consultez votre devis :\\n👉 https://mkmkmk.servo.tools/pages/devis_client.php?lien=5745323e5c856d8f206d5cf676ad29cb\\n📲 Suivi réparation :\\n👉 https://mkmkmk.servo.tools/suivi.php?id=1\\n\\n📞 Questions ? 05 55 44 33 22\\n🏠 MD Geek Shop\", \"telephone\": \"33782962906\", \"type_devis\": \"en_attente\", \"template_utilise\": \"Devis en attente - Rappel\"}', '2025-09-24 00:13:23'),
(4, 1, 'STATUT_CHANGE_accepte', 'Statut changé de \"envoye\" vers \"accepte\"', 'systeme', NULL, NULL, NULL, NULL, '2025-09-24 00:17:53'),
(5, 1, 'ACCEPTATION_CLIENT', 'Devis accepté par le client. Solution choisie: 213', 'client', NULL, '84.98.112.56', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', NULL, '2025-09-24 00:17:53'),
(6, 2, 'STATUT_CHANGE_accepte', 'Statut changé de \"envoye\" vers \"accepte\"', 'systeme', NULL, NULL, NULL, NULL, '2025-09-24 22:43:22'),
(7, 2, 'ACCEPTATION_CLIENT', 'Devis accepté par le client. Solution choisie: Écran generique', 'client', NULL, '84.98.112.56', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.0 Mobile/15E148 Safari/604.1', NULL, '2025-09-24 22:43:23'),
(8, 3, 'STATUT_CHANGE_accepte', 'Statut changé de \"envoye\" vers \"accepte\"', 'systeme', NULL, NULL, NULL, NULL, '2025-09-24 23:32:05'),
(9, 3, 'ACCEPTATION_CLIENT', 'Devis accepté par le client. Solution choisie: detail etape 3', 'client', NULL, '84.98.112.56', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Mobile Safari/537.36', NULL, '2025-09-24 23:32:05'),
(10, 4, 'STATUT_CHANGE_refuse', 'Statut changé de \"envoye\" vers \"refuse\"', 'systeme', NULL, NULL, NULL, NULL, '2025-09-24 23:35:43'),
(11, 4, 'REFUS_CLIENT', 'Devis refusé par le client', 'client', NULL, '84.98.112.56', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Mobile Safari/537.36', NULL, '2025-09-24 23:35:43'),
(12, 5, 'STATUT_CHANGE_refuse', 'Statut changé de \"envoye\" vers \"refuse\"', 'systeme', NULL, NULL, NULL, NULL, '2025-09-24 23:36:36'),
(13, 5, 'REFUS_CLIENT', 'Devis refusé par le client', 'client', NULL, '84.98.112.56', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Mobile Safari/537.36', NULL, '2025-09-24 23:36:36'),
(14, 6, 'STATUT_CHANGE_accepte', 'Statut changé de \"envoye\" vers \"accepte\"', 'systeme', NULL, NULL, NULL, NULL, '2025-09-25 10:31:08'),
(15, 6, 'ACCEPTATION_CLIENT', 'Devis accepté par le client. Solution choisie: Remise a neuf Ecran + Batterie', 'client', NULL, '109.210.27.45', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', NULL, '2025-09-25 10:31:08'),
(16, 7, 'STATUT_CHANGE_accepte', 'Statut changé de \"envoye\" vers \"accepte\"', 'systeme', NULL, NULL, NULL, NULL, '2025-09-27 17:20:39'),
(17, 7, 'ACCEPTATION_CLIENT', 'Devis accepté par le client. Solution choisie: Remplacement ecran original', 'client', NULL, '109.210.27.45', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.0 Mobile/15E148 Safari/604.1', NULL, '2025-09-27 17:20:39'),
(18, 5, 'STATUT_CHANGE_envoye', 'Statut changé de \"refuse\" vers \"envoye\"', 'systeme', NULL, NULL, NULL, NULL, '2025-10-21 00:20:48'),
(19, 7, 'STATUT_CHANGE_envoye', 'Statut changé de \"accepte\" vers \"envoye\"', 'systeme', NULL, NULL, NULL, NULL, '2025-10-21 00:21:09'),
(20, 7, 'sms_renvoye', 'SMS renvoyé à 33782962906', 'employe', NULL, NULL, NULL, '{\"message\": \"⏰ Rappel guezguez !\\n\\nVotre devis expire dans 6 jours.\\n\\n📄 Consultez votre devis :\\n👉 https://mkmkmk.mdgeek.top/pages/devis_client.php?lien=1f661d66811c582a2e566c80e2ba0823\\n📲 Suivi réparation :\\n👉 https://mkmkmk.mdgeek.top/suivi.php?id=3\\n\\n📞 Questions ? 05 55 44 33 22\\n🏠 MAISON DU GEEK\", \"telephone\": \"33782962906\", \"type_devis\": \"en_attente\", \"template_utilise\": \"Devis en attente - Rappel\"}', '2025-10-21 00:21:22'),
(21, 7, 'STATUT_CHANGE_expire', 'Statut changé de \"envoye\" vers \"expire\"', 'systeme', NULL, NULL, NULL, NULL, '2025-11-05 17:21:13'),
(22, 8, 'STATUT_CHANGE_accepte', 'Statut changé de \"envoye\" vers \"accepte\"', 'systeme', NULL, NULL, NULL, NULL, '2025-11-05 17:21:57'),
(23, 8, 'ACCEPTATION_CLIENT', 'Devis accepté par le client. Solution choisie: C', 'client', NULL, '109.210.27.45', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.0.1 Mobile/15E148 Safari/604.1', NULL, '2025-11-05 17:21:57'),
(24, 10, 'STATUT_CHANGE_accepte', 'Statut changé de \"envoye\" vers \"accepte\"', 'systeme', NULL, NULL, NULL, NULL, '2025-11-08 23:20:54'),
(25, 10, 'ACCEPTATION_CLIENT', 'Devis accepté par le client. Solution choisie: 2132', 'client', NULL, '84.98.112.56', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', NULL, '2025-11-08 23:20:54'),
(26, 5, 'sms_renvoye', 'SMS renvoyé à 33782962906', 'employe', NULL, NULL, NULL, '{\"message\": \"⚠️ GARDIENNAGE guezguez !\\n\\nVotre devis a expiré.\\nGardiennage : 5,00€/jour\\n\\n📄 Consultez votre devis :\\n👉 https://mkmkmk.mdgeek.top/pages/devis_client.php?lien=11820d115068a3f65137bf89ce5c484d\\n📲 Suivi réparation :\\n👉 https://mkmkmk.mdgeek.top/suivi.php?id=2\\n\\n📞 Questions ? 05 55 44 33 22\\n🏠 MAISON DU GEEK\", \"telephone\": \"33782962906\", \"type_devis\": \"expire_recent\", \"template_utilise\": \"Devis expiré - Gardiennage\"}', '2025-11-09 20:56:28'),
(27, 9, 'sms_renvoye', 'SMS renvoyé à 33650883462', 'employe', NULL, NULL, NULL, '{\"message\": \"⏰ Rappel brissaud !\\n\\nVotre devis expire dans 13 jours.\\n\\n📄 Consultez votre devis :\\n👉 https://mkmkmk.mdgeek.top/pages/devis_client.php?lien=7709a45308176ecfb64d81db2d39a94a\\n📲 Suivi réparation :\\n👉 https://mkmkmk.mdgeek.top/suivi.php?id=2140\\n\\n📞 Questions ? 05 55 44 33 22\\n🏠 MAISON DU GEEK\", \"telephone\": \"33650883462\", \"type_devis\": \"en_attente\", \"template_utilise\": \"Devis en attente - Rappel\"}', '2025-11-09 20:56:28'),
(28, 11, 'sms_renvoye', 'SMS renvoyé à 33782962906', 'employe', NULL, NULL, NULL, '{\"message\": \"⏰ Rappel guezguez !\\n\\nVotre devis expire dans 13 jours.\\n\\n📄 Consultez votre devis :\\n👉 https://mkmkmk.mdgeek.top/pages/devis_client.php?lien=8f27a383f262c623ee44b959feb13c78\\n📲 Suivi réparation :\\n👉 https://mkmkmk.mdgeek.top/suivi.php?id=12\\n\\n📞 Questions ? 05 55 44 33 22\\n🏠 MAISON DU GEEK\", \"telephone\": \"33782962906\", \"type_devis\": \"en_attente\", \"template_utilise\": \"Devis en attente - Rappel\"}', '2025-11-09 20:56:28');

-- --------------------------------------------------------

--
-- Structure de la table `devis_notifications`
--

CREATE TABLE `devis_notifications` (
  `id` int NOT NULL,
  `devis_id` int NOT NULL,
  `type` enum('envoi_devis','rappel','acceptation','refus') COLLATE utf8mb4_unicode_ci NOT NULL,
  `telephone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `statut_envoi` enum('en_attente','envoye','echec') COLLATE utf8mb4_unicode_ci DEFAULT 'en_attente',
  `date_programmee` timestamp NULL DEFAULT NULL,
  `date_envoi` timestamp NULL DEFAULT NULL,
  `erreur` text COLLATE utf8mb4_unicode_ci,
  `tentatives` int DEFAULT '0',
  `date_creation` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `devis_notifications`
--

INSERT INTO `devis_notifications` (`id`, `devis_id`, `type`, `telephone`, `message`, `statut_envoi`, `date_programmee`, `date_envoi`, `erreur`, `tentatives`, `date_creation`) VALUES
(1, 1, 'envoi_devis', '33782962906', 'Bonjour, guezguez, \nLe devis de votre test est disponible. \nMontant : 2,40 €\n📄 Consultez votre devis ici :\n👉 https://mkmkmk.servo.tools/pages/devis_client.php?lien=5745323e5c856d8f206d5cf676ad29cb\n📲 Suivi réparation :\n👉 https://mkmkmk.servo.tools/suivi.php?id=1\nUne question ? Appelez-nous au 04 93 46 71 63\nMAISON DU GEEK', 'envoye', '2025-09-23 23:44:47', '2025-09-23 23:44:48', NULL, 0, '2025-09-23 23:44:47'),
(2, 2, 'envoi_devis', '33782962906', 'Bonjour, guezguez, \nLe devis de votre IPHONE 8 est disponible. \nMontant : 237,60 €\n📄 Consultez votre devis ici :\n👉 https://mkmkmk.mdgeek.top/pages/devis_client.php?lien=e1b2b34ec76c64e29640f8deccb71db2\n📲 Suivi réparation :\n👉 https://mkmkmk.mdgeek.top/suivi.php?id=2\nUne question ? Appelez-nous au 05 55 44 33 22\nMAISON DU GEEK', 'envoye', '2025-09-24 15:17:35', '2025-09-24 15:17:35', NULL, 0, '2025-09-24 15:17:35'),
(3, 3, 'envoi_devis', '33782962906', 'Bonjour, guezguez, \nLe devis de votre IPHONE 8 est disponible. \nMontant : 28,80 €\n📄 Consultez votre devis ici :\n👉 https://mkmkmk.mdgeek.top/pages/devis_client.php?lien=eb739f36bba0b122372e543211260acc\n📲 Suivi réparation :\n👉 https://mkmkmk.mdgeek.top/suivi.php?id=2\nUne question ? Appelez-nous au 05 55 44 33 22\nMAISON DU GEEK', 'envoye', '2025-09-24 23:26:22', '2025-09-24 23:26:22', NULL, 0, '2025-09-24 23:26:22'),
(4, 4, 'envoi_devis', '33782962906', 'Bonjour, guezguez, \nLe devis de votre IPHONE 8 est disponible. \nMontant : 106,80 €\n📄 Consultez votre devis ici :\n👉 https://mkmkmk.mdgeek.top/pages/devis_client.php?lien=7a89c5ec5f84b49062508fc42590e22a\n📲 Suivi réparation :\n👉 https://mkmkmk.mdgeek.top/suivi.php?id=2\nUne question ? Appelez-nous au 05 55 44 33 22\nMAISON DU GEEK', 'envoye', '2025-09-24 23:34:31', '2025-09-24 23:34:31', NULL, 0, '2025-09-24 23:34:31'),
(5, 5, 'envoi_devis', '33782962906', 'Bonjour, guezguez, \nLe devis de votre IPHONE 8 est disponible. \nMontant : 28,80 €\n📄 Consultez votre devis ici :\n👉 https://mkmkmk.mdgeek.top/pages/devis_client.php?lien=11820d115068a3f65137bf89ce5c484d\n📲 Suivi réparation :\n👉 https://mkmkmk.mdgeek.top/suivi.php?id=2\nUne question ? Appelez-nous au 05 55 44 33 22\nMAISON DU GEEK', 'envoye', '2025-09-24 23:36:23', '2025-09-24 23:36:23', NULL, 0, '2025-09-24 23:36:23'),
(6, 6, 'envoi_devis', '33782962906', 'Bonjour, guezguez, \nLe devis de votre saber est disponible. \nMontant : 296,40 €\n📄 Consultez votre devis ici :\n👉 https://mkmkmk.servo.tools/pages/devis_client.php?lien=a003621ffbccc941e0a964bd9e101393\n📲 Suivi réparation :\n👉 https://mkmkmk.servo.tools/suivi.php?id=4\nUne question ? Appelez-nous au 05 55 44 33 22\nMAISON DU GEEK', 'envoye', '2025-09-25 10:29:50', '2025-09-25 10:29:50', NULL, 0, '2025-09-25 10:29:50'),
(7, 7, 'envoi_devis', '33782962906', 'Bonjour, guezguez, \nLe devis de votre test est disponible. \nMontant : 261,60 €\n📄 Consultez votre devis ici :\n👉 https://mkmkmk.mdgeek.top/pages/devis_client.php?lien=1f661d66811c582a2e566c80e2ba0823\n📲 Suivi réparation :\n👉 https://mkmkmk.mdgeek.top/suivi.php?id=3\nUne question ? Appelez-nous au 05 55 44 33 22\nMAISON DU GEEK', 'envoye', '2025-09-27 17:19:34', '2025-09-27 17:19:35', NULL, 0, '2025-09-27 17:19:34'),
(8, 8, 'envoi_devis', '33782962906', 'Bonjour, guezguez, \nLe devis de votre Macbook Pro est disponible. \nMontant : 516,00 €\n📄 Consultez votre devis ici :\n👉 https://mkmkmk.mdgeek.top/pages/devis_client.php?lien=9510f7795abf9d12595a2ad102e2568b\n📲 Suivi réparation :\n👉 https://mkmkmk.mdgeek.top/suivi.php?id=12\nUne question ? Appelez-nous au 05 55 44 33 22\nMAISON DU GEEK', 'envoye', '2025-11-05 17:19:41', '2025-11-05 17:19:41', NULL, 0, '2025-11-05 17:19:41'),
(9, 9, 'envoi_devis', '33650883462', 'Bonjour, brissaud, \nLe devis de votre asd est disponible. \nMontant : 3,60 €\n📄 Consultez votre devis ici :\n👉 https://mkmkmk.servo.tools/pages/devis_client.php?lien=7709a45308176ecfb64d81db2d39a94a\n📲 Suivi réparation :\n👉 https://mkmkmk.servo.tools/suivi.php?id=2140\nUne question ? Appelez-nous au 05 55 44 33 22\nMAISON DU GEEK', 'envoye', '2025-11-08 23:16:12', '2025-11-08 23:16:12', NULL, 0, '2025-11-08 23:16:12'),
(10, 10, 'envoi_devis', '33123456789', 'Bonjour, dsa, \nLe devis de votre sa est disponible. \nMontant : 26,40 €\n📄 Consultez votre devis ici :\n👉 https://mkmkmk.servo.tools/pages/devis_client.php?lien=f62b5a9d5fae0f455be46b6e2df2e69f\n📲 Suivi réparation :\n👉 https://mkmkmk.servo.tools/suivi.php?id=13\nUne question ? Appelez-nous au 05 55 44 33 22\nMAISON DU GEEK', 'envoye', '2025-11-08 23:20:35', '2025-11-08 23:20:35', NULL, 0, '2025-11-08 23:20:35'),
(11, 11, 'envoi_devis', '33782962906', 'Bonjour, guezguez, \nLe devis de votre Macbook Pro est disponible. \nMontant : 39,60 €\n📄 Consultez votre devis ici :\n👉 https://mkmkmk.servo.tools/pages/devis_client.php?lien=8f27a383f262c623ee44b959feb13c78\n📲 Suivi réparation :\n👉 https://mkmkmk.servo.tools/suivi.php?id=12\nUne question ? Appelez-nous au 05 55 44 33 22\nMAISON DU GEEK', 'envoye', '2025-11-08 23:24:35', '2025-11-08 23:24:35', NULL, 0, '2025-11-08 23:24:35');

-- --------------------------------------------------------

--
-- Structure de la table `devis_pannes`
--

CREATE TABLE `devis_pannes` (
  `id` int NOT NULL,
  `devis_id` int NOT NULL,
  `titre` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `gravite` enum('faible','moyenne','elevee','critique') COLLATE utf8mb4_unicode_ci DEFAULT 'moyenne',
  `ordre` int DEFAULT '1',
  `date_creation` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `devis_pannes`
--

INSERT INTO `devis_pannes` (`id`, `devis_id`, `titre`, `description`, `gravite`, `ordre`, `date_creation`) VALUES
(1, 1, '231', '231', 'moyenne', 1, '2025-09-23 23:44:47'),
(2, 2, 'Écran casse', 'Jd', 'critique', 1, '2025-09-24 15:17:35'),
(3, 3, 'Description etape 2', 'Details etape 2', 'moyenne', 1, '2025-09-24 23:26:22'),
(4, 4, 'Ecran casser', '', 'moyenne', 1, '2025-09-24 23:34:31'),
(5, 5, 'asdas', '', 'moyenne', 1, '2025-09-24 23:36:23'),
(6, 6, 'Ecran casser', '', 'moyenne', 1, '2025-09-25 10:29:50'),
(7, 7, 'Ecran fissure', '', 'moyenne', 1, '2025-09-27 17:19:34'),
(8, 8, 'Ecran casse', '', 'faible', 1, '2025-11-05 17:19:41'),
(9, 10, '213', '213', 'moyenne', 1, '2025-11-08 23:20:35'),
(10, 11, 'kijuhyg', 'kijuhyg', 'moyenne', 1, '2025-11-08 23:24:35'),
(11, 11, 'panne2', 'panne2', 'moyenne', 2, '2025-11-08 23:24:35');

-- --------------------------------------------------------

--
-- Structure de la table `devis_solutions`
--

CREATE TABLE `devis_solutions` (
  `id` int NOT NULL,
  `devis_id` int NOT NULL,
  `nom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `prix_total` decimal(10,2) NOT NULL,
  `duree_reparation` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `garantie` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `recommandee` tinyint(1) DEFAULT '0',
  `ordre` int DEFAULT '1',
  `date_creation` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `devis_solutions`
--

INSERT INTO `devis_solutions` (`id`, `devis_id`, `nom`, `description`, `prix_total`, `duree_reparation`, `garantie`, `recommandee`, `ordre`, `date_creation`) VALUES
(1, 1, '213', '', 2.00, '', '3 mois', 0, 1, '2025-09-23 23:44:47'),
(2, 2, 'Écran generique', '', 89.00, '', '3 mois', 0, 1, '2025-09-24 15:17:35'),
(3, 2, 'Écran orginal', '', 109.00, '', '3 mois', 0, 2, '2025-09-24 15:17:35'),
(4, 3, 'nom solution etape 3', 'detail etape 3', 2.00, '', '3 mois', 0, 1, '2025-09-24 23:26:22'),
(5, 3, 'detail etape 3', 'description solution 3', 22.00, '', '3 mois', 0, 2, '2025-09-24 23:26:22'),
(6, 4, 'Remplacement Ecran', '', 89.00, '', '3 mois', 0, 1, '2025-09-24 23:34:31'),
(7, 5, 'dsasa', '', 22.00, '', '3 mois', 0, 1, '2025-09-24 23:36:23'),
(8, 5, 'dsadas', '', 2.00, '', '3 mois', 0, 2, '2025-09-24 23:36:23'),
(9, 6, 'Ecran original', '', 89.00, '', '3 mois', 0, 1, '2025-09-25 10:29:50'),
(10, 6, 'Ecran generique', '', 49.00, '', '3 mois', 0, 2, '2025-09-25 10:29:50'),
(11, 6, 'Remise a neuf Ecran + Batterie', '', 109.00, '', '3 mois', 0, 3, '2025-09-25 10:29:50'),
(12, 7, 'Remplacement ecran original', '', 129.00, '', '3 mois', 0, 1, '2025-09-27 17:19:34'),
(13, 7, 'Remplacement ecran générique', '', 89.00, '', '3 mois', 0, 2, '2025-09-27 17:19:34'),
(14, 8, 'dashuksauhdi', '', 200.00, '', '3 mois', 0, 1, '2025-11-05 17:19:41'),
(15, 8, 'dfsdsf', '', 150.00, '', '3 mois', 0, 2, '2025-11-05 17:19:41'),
(16, 8, 'C', '', 80.00, '', '3 mois', 0, 3, '2025-11-05 17:19:41'),
(17, 10, '2132', '213321', 22.00, '', '3 mois', 0, 1, '2025-11-08 23:20:35'),
(18, 11, 'bkbnil', '', 22.00, '', '3 mois', 0, 1, '2025-11-08 23:24:35'),
(19, 11, 'lkjjbkbjkjk', '2', 11.00, '', '3 mois', 0, 2, '2025-11-08 23:24:35');

--
-- Déclencheurs `devis_solutions`
--
DELIMITER $$
CREATE TRIGGER `update_devis_totals` AFTER INSERT ON `devis_solutions` FOR EACH ROW BEGIN
    UPDATE devis 
    SET total_ht = (
        SELECT SUM(prix_total) 
        FROM devis_solutions 
        WHERE devis_id = NEW.devis_id
    ),
    total_ttc = (
        SELECT SUM(prix_total) * (1 + taux_tva/100)
        FROM devis_solutions 
        WHERE devis_id = NEW.devis_id
    )
    WHERE id = NEW.devis_id$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `devis_solutions_items`
--

CREATE TABLE `devis_solutions_items` (
  `id` int NOT NULL,
  `solution_id` int NOT NULL,
  `nom` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `quantite` int DEFAULT '1',
  `prix_unitaire` decimal(10,2) NOT NULL,
  `prix_total` decimal(10,2) NOT NULL,
  `type` enum('piece','main_oeuvre','autre') COLLATE utf8mb4_unicode_ci DEFAULT 'piece',
  `ordre` int DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `devis_templates`
--

CREATE TABLE `devis_templates` (
  `id` int NOT NULL,
  `nom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('sms','email') COLLATE utf8mb4_unicode_ci NOT NULL,
  `sujet` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Pour les emails',
  `contenu` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `variables_disponibles` json DEFAULT NULL COMMENT 'Liste des variables utilisables',
  `actif` tinyint(1) DEFAULT '1',
  `date_creation` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modification` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `employee_schedules`
--

CREATE TABLE `employee_schedules` (
  `id` int NOT NULL,
  `employee_id` int NOT NULL COMMENT 'Référence users.id',
  `day_of_week` tinyint(1) NOT NULL COMMENT '1=Lundi, 7=Dimanche',
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `is_working_day` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `employes`
--

CREATE TABLE `employes` (
  `id` int NOT NULL,
  `nom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `prenom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telephone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_embauche` date DEFAULT NULL,
  `statut` enum('actif','inactif') COLLATE utf8mb4_unicode_ci DEFAULT 'actif',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `employes`
--

INSERT INTO `employes` (`id`, `nom`, `prenom`, `email`, `telephone`, `date_embauche`, `statut`, `created_at`, `updated_at`) VALUES
(3, 'Durand', 'Jean', 'jean.durand@test.com', '0123456789', '2025-10-24', 'actif', '2025-10-23 23:21:59', '2025-10-23 23:21:59'),
(4, 'Martin', 'Sophie', 'sophie.martin@test.com', '0987654321', '2025-10-24', 'actif', '2025-10-23 23:21:59', '2025-10-23 23:21:59');

-- --------------------------------------------------------

--
-- Structure de la table `fournisseurs`
--

CREATE TABLE `fournisseurs` (
  `id` int NOT NULL,
  `nom` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact_nom` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `url` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `adresse` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `fournisseurs`
--

INSERT INTO `fournisseurs` (`id`, `nom`, `contact_nom`, `email`, `url`, `adresse`, `created_at`) VALUES
(2, 'Utopya', NULL, NULL, 'https://mdgeek.top/i', NULL, '2025-03-28 18:58:21'),
(11, 'Mobilax', NULL, NULL, 'http://mobilax.fr', NULL, '2025-03-29 00:41:28');

-- --------------------------------------------------------

--
-- Structure de la table `garanties`
--

CREATE TABLE `garanties` (
  `id` int NOT NULL,
  `reparation_id` int NOT NULL,
  `date_debut` timestamp NOT NULL COMMENT 'Date de début de garantie (quand réparation effectuée)',
  `date_fin` timestamp NOT NULL COMMENT 'Date de fin de garantie calculée',
  `duree_jours` int NOT NULL COMMENT 'Durée en jours (copie du paramètre au moment de la création)',
  `statut` enum('active','expiree','utilisee','annulee') DEFAULT 'active',
  `description_garantie` text COMMENT 'Description de ce qui est garanti',
  `notes` text COMMENT 'Notes administratives',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `garanties`
--

INSERT INTO `garanties` (`id`, `reparation_id`, `date_debut`, `date_fin`, `duree_jours`, `statut`, `description_garantie`, `notes`, `created_at`, `updated_at`) VALUES
(3, 1, '2025-09-27 21:22:22', '2025-12-26 22:22:22', 90, 'active', 'Garantie pièces et main d\'œuvre', NULL, '2025-09-27 21:22:22', '2025-09-27 21:22:22'),
(4, 4, '2025-10-23 19:34:26', '2026-01-21 20:34:26', 90, 'active', 'Garantie pièces et main d\'œuvre', NULL, '2025-10-23 19:34:26', '2025-10-23 19:34:26'),
(5, 2, '2025-11-03 22:37:50', '2026-02-01 22:37:50', 90, 'active', 'Garantie pièces et main d\'œuvre', NULL, '2025-11-03 22:37:50', '2025-11-03 22:37:50'),
(6, 2136, '2025-11-05 17:18:18', '2026-02-03 17:18:18', 90, 'active', 'Garantie pièces et main d\'œuvre', NULL, '2025-11-05 17:18:18', '2025-11-05 17:18:18'),
(7, 2135, '2025-11-06 21:45:46', '2026-02-04 21:45:46', 90, 'active', 'Garantie pièces et main d\'œuvre', NULL, '2025-11-06 21:45:46', '2025-11-06 21:45:46');

-- --------------------------------------------------------

--
-- Structure de la table `gardiennage`
--

CREATE TABLE `gardiennage` (
  `id` int NOT NULL,
  `reparation_id` int NOT NULL,
  `date_debut` date NOT NULL,
  `date_derniere_facturation` date NOT NULL,
  `tarif_journalier` decimal(10,2) NOT NULL DEFAULT '5.00',
  `jours_factures` int NOT NULL DEFAULT '0',
  `montant_total` decimal(10,2) NOT NULL DEFAULT '0.00',
  `est_actif` tinyint(1) NOT NULL DEFAULT '1',
  `date_fin` date DEFAULT NULL,
  `derniere_notification` date DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `gardiennage_notifications`
--

CREATE TABLE `gardiennage_notifications` (
  `id` int NOT NULL,
  `gardiennage_id` int NOT NULL,
  `date_notification` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `type_notification` enum('sms','email','appel') COLLATE utf8mb4_unicode_ci NOT NULL,
  `statut` enum('envoyé','échec','annulé') COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `help_requests`
--

CREATE TABLE `help_requests` (
  `id` int NOT NULL,
  `task_id` int NOT NULL,
  `user_id` int NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('en_attente','resolu','en_cours') COLLATE utf8mb4_unicode_ci DEFAULT 'en_attente',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `resolved_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `historique_gains`
--

CREATE TABLE `historique_gains` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `mission_id` int NOT NULL,
  `type` enum('euros','points') NOT NULL,
  `montant` decimal(10,2) NOT NULL,
  `description` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `historique_gains`
--

INSERT INTO `historique_gains` (`id`, `user_id`, `mission_id`, `type`, `montant`, `description`, `created_at`) VALUES
(1, 6, 1, 'euros', 22.00, 'Mission complétée', '2025-11-10 11:35:27'),
(2, 6, 1, 'points', 10.00, 'Mission complétée', '2025-11-10 11:35:27'),
(3, 6, 2, 'euros', 2.00, 'Mission complétée', '2025-11-10 11:37:31'),
(4, 6, 2, 'points', 2.00, 'Mission complétée', '2025-11-10 11:37:31');

-- --------------------------------------------------------

--
-- Structure de la table `historique_soldes`
--

CREATE TABLE `historique_soldes` (
  `id` int NOT NULL,
  `partenaire_id` int NOT NULL,
  `ancien_solde` decimal(10,2) DEFAULT NULL,
  `nouveau_solde` decimal(10,2) DEFAULT NULL,
  `transaction_id` int DEFAULT NULL,
  `date_modification` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `journal_actions`
--

CREATE TABLE `journal_actions` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `action_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `target_id` int NOT NULL,
  `details` text COLLATE utf8mb4_unicode_ci,
  `date_action` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `kb_articles`
--

CREATE TABLE `kb_articles` (
  `id` int NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `content` longtext COLLATE utf8mb4_general_ci NOT NULL,
  `category_id` int NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `views` int NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `kb_articles`
--

INSERT INTO `kb_articles` (`id`, `title`, `content`, `category_id`, `created_at`, `updated_at`, `views`) VALUES
(1, 'Comment activer Windows et Microsoft Office', '<div class=\"html-content-wrapper\"><!doctype html>\r\n<html lang=\"fr\">\r\n<head>\r\n<meta charset=\"utf-8\" />\r\n<meta name=\"viewport\" content=\"width=device-width, initial-scale=1\" />\r\n<title>Tuto – Préparation PC Windows (exact)</title>\r\n<style>\r\n  :root {\r\n    --bg: #f6f7fb;\r\n    --panel: #ffffff;\r\n    --text: #0b0d14;\r\n    --muted: #55617a;\r\n    --accent: #1a66ff;\r\n    --ok: #1f9d63;\r\n    --border: rgba(10,13,20,0.08);\r\n  }\r\n  @media (prefers-color-scheme: dark) {\r\n    :root {\r\n      --bg: #0b0c10;\r\n      --panel: #11131a;\r\n      --text: #f0f0f0;\r\n      --muted: #a9b0c5;\r\n      --accent: #6aa5ff;\r\n      --ok: #77e39b;\r\n      --border: rgba(255,255,255,0.1);\r\n    }\r\n  }\r\n  body {\r\n    font-family: \'Segoe UI\', Roboto, sans-serif;\r\n    background-color: var(--bg);\r\n    color: var(--text);\r\n    line-height: 1.6;\r\n    padding: 40px;\r\n    max-width: 800px;\r\n    margin: auto;\r\n    transition: background-color 0.3s, color 0.3s;\r\n  }\r\n  h2 {\r\n    color: var(--ok);\r\n  }\r\n  .step {\r\n    background-color: var(--panel);\r\n    border: 1px solid var(--border);\r\n    border-radius: 10px;\r\n    padding: 20px;\r\n    margin-bottom: 20px;\r\n    box-shadow: 0 8px 20px rgba(0,0,0,0.1);\r\n    transition: background-color 0.3s, color 0.3s;\r\n  }\r\n  .sep {\r\n    text-align: center;\r\n    color: var(--muted);\r\n    margin: 20px 0;\r\n  }\r\n  p {\r\n    margin: 6px 0;\r\n  }\r\n  strong {\r\n    color: var(--accent);\r\n  }\r\n  .btn-print {\r\n    display: inline-block;\r\n    margin-top: 30px;\r\n    padding: 10px 16px;\r\n    background: var(--accent);\r\n    color: white;\r\n    border: none;\r\n    border-radius: 8px;\r\n    font-weight: 600;\r\n    cursor: pointer;\r\n    box-shadow: 0 4px 10px rgba(0,0,0,0.2);\r\n    transition: opacity 0.3s;\r\n  }\r\n  .btn-print:hover {\r\n    opacity: 0.85;\r\n  }\r\n</style>\r\n</head>\r\n<body>\r\n\r\n<div class=\"step\">\r\n<h2>1. ✅ Vérifier la connexion Internet</h2>\r\n<p>• Vérifiez si l’ordinateur est connecté au WiFi.</p>\r\n<p>• Si ce n’est pas le cas : branchez un câble Ethernet à l’ordinateur pour obtenir une connexion Internet.</p>\r\n</div>\r\n<div class=\"sep\">⸻</div>\r\n\r\n<div class=\"step\">\r\n<h2>2. ✅ Sélectionner la version de Windows</h2>\r\n<p>• À l’apparition du popup GHOST MSG, sélectionnez une version de Windows.</p>\r\n<p>• Préférence : option 1.</p>\r\n</div>\r\n<div class=\"sep\">⸻</div>\r\n\r\n<div class=\"step\">\r\n<h2>3. ✅ Après le redémarrage</h2>\r\n<p>• Ouvrez Ghost Toolbox (icône sur le Bureau ou via menu démarrer).</p>\r\n</div>\r\n<div class=\"sep\">⸻</div>\r\n\r\n<div class=\"step\">\r\n<h2>4. ✅ Installer Google Chrome</h2>\r\n<p>• Dans Ghost Toolbox :</p>\r\n<p>• Option 51 → Internet Browser</p>\r\n<p>• Puis Option 4 → Google Chrome</p>\r\n<p>• Google Chrome va s’installer automatiquement.</p>\r\n</div>\r\n<div class=\"sep\">⸻</div>\r\n\r\n<div class=\"step\">\r\n<h2>5. ✅ Télécharger le pack d’activation</h2>\r\n<p>• Ouvrez Google Chrome.</p>\r\n<p>• Accédez à l’adresse suivante :</p>\r\n<p><strong>mdgeek.fr/pc.zip</strong></p>\r\n<p>• Téléchargez le fichier ZIP.</p>\r\n<p>• Extrayez le fichier ZIP sur le Bureau.</p>\r\n</div>\r\n<div class=\"sep\">⸻</div>\r\n\r\n<div class=\"step\">\r\n<h2>6. ✅ Activer Windows</h2>\r\n<p>• Dans le dossier extrait, ouvrez :</p>\r\n<p>• 1. Activation Windows</p>\r\n<p>• Lancez l’activation selon les instructions à l’écran.</p>\r\n</div>\r\n<div class=\"sep\">⸻</div>\r\n\r\n<div class=\"step\">\r\n<h2>7. ✅ Installer Office</h2>\r\n<p>• Toujours dans le dossier extrait :</p>\r\n<p>• Ouvrez le dossier 2. Oinstall.</p>\r\n<p>• Choisissez la langue française.</p>\r\n<p>• Cliquez sur “Installer” pour installer Microsoft Office.</p>\r\n</div>\r\n<div class=\"sep\">⸻</div>\r\n\r\n<div class=\"step\">\r\n<h2>8. ✅ Installer les logiciels de base avec Ninite</h2>\r\n<p>• Ouvrez l’application Ninite présente dans le pack téléchargé.</p>\r\n<p>• Patientez jusqu’à la fin complète des installations automatiques.</p>\r\n</div>\r\n<div class=\"sep\">⸻</div>\r\n\r\n<div class=\"step\">\r\n<h2>9. ✅ Configurer la langue et le clavier</h2>\r\n<p>• Ouvrez les Paramètres Windows.</p>\r\n<p>• Allez dans Heure et langue > Langue et région.</p>\r\n<p>• Ajoutez la langue française si elle n’est pas déjà installée.</p>\r\n<p>• Vérifiez que le clavier est bien en AZERTY.</p>\r\n</div>\r\n<div class=\"sep\">⸻</div>\r\n\r\n<div class=\"step\">\r\n<h2>✅ Votre PC est maintenant prêt avec Windows activé, Office installé, les logiciels essentiels et en français.</h2>\r\n</div>\r\n\r\n<button class=\"btn-print\" onclick=\"window.print()\">🖨️ Imprimer / Exporter en PDF</button>\r\n\r\n</body>\r\n</html></div>', 1, '2025-11-05 00:24:00', '2025-11-05 00:24:00', 21),
(2, 'Code Erreur Xiaomi M365', '<p>&lt;p&gt;Si tu souhaites directement consulter la correspondance de ton code erreur, tu peux regarder immédiatement dans la liste ci-dessous, ils sont tous répertoriés.&lt;br&gt;&lt;br&gt;Code erreur 10 : Défaut entre carte Bluetooth et carte mère.&lt;br&gt;Code erreur 11, 12, 13, 28, 29 : Défaut MosFET carte mère&lt;br&gt;Code erreur 14 : Défaut du levier de frein ou accélérateur&lt;br&gt;Code erreur 15 : Défaut poignée accélérateur ou levier de frein&lt;br&gt;Code erreur 18 : Défaut capteur hall moteur&lt;br&gt;Code erreur 21 : Défaut communication batterie&lt;br&gt;Code erreur 22, 23 : Défaut numéro de série BMS&lt;br&gt;Code erreur 24 : Défaut tension batterie déséquilibré&lt;br&gt;Code erreur 27, 39 : Défaut numéro série carte mère&lt;br&gt;Code erreur 35, 36 : Défaut capteur ou surchauffe batterie&lt;br&gt;Code erreur 40 : Défaut surchauffe carte mère&lt;br&gt;Code erreur 41 : Défaut version BLE&lt;br&gt;Code erreur 42 : Défaut carte mère numéro de série&lt;/p&gt;</p>', 2, '2025-11-05 01:33:01', '2025-11-05 01:33:01', 2),
(3, 'DIAG – Trottinette électrique HS -', '<p><br>⸻<br><br>📍 Étape 1 : Tester la tension de la batterie<br>• Utiliser un multimètre en mode tension continue (DC).<br>• Brancher les sondes rouge (+) et noire (−) directement sur les bornes de la batterie.<br><br>✅ Si la batterie affiche une tension normale (par exemple, &gt; 41V pour une 48V) → passer à l’étape 4<br><br>❌ Si la tension est trop basse ou nulle → passer à l’étape 2<br><br>⸻<br><br>📍 Étape 2 : Tester la tension aux câbles du LCD<br>• Identifier les câbles d’alimentation du LCD (souvent rouge/noir).<br>• Brancher le multimètre entre les fils d’alimentation du connecteur LCD.<br><br>✅ Si une tension est présente (ex. 41-54V) → passer à l’étape 4<br><br>❌ Si aucune tension n’est présente → passer à l’étape 3<br><br>⸻<br><br>📍 Étape 3 : Tester la continuité des câbles LCD<br>• Débrancher les deux extrémités du câble LCD (de la carte mère jusqu’au LCD).<br>• Utiliser le mode bip/continuité du multimètre.<br>• Tester chaque fil (bout à bout) pour vérifier qu’il n’y a pas de rupture.<br><br>✅ Si les câbles sont bons → passer à l’étape 4<br><br>❌ Si un ou plusieurs câbles sont coupés → remplacer le câble LCD et retester<br><br>⸻<br><br>📍 Étape 4 : Diagnostic final<br>• Si :<br>• la batterie est bonne<br>• il n’y a aucun affichage<br>• et le câblage est intact<br><br>👉 Le kit LCD + contrôleur est probablement HS.<br><br>✅ Solution recommandée :<br><br>Proposer un remplacement complet par un kit MiniMotors (LCD + contrôleur), compatible avec le modèle.</p>', 2, '2025-11-05 01:34:56', '2025-11-05 01:34:56', 2),
(4, 'Generer un code Iron TV / IPTV', '<div class=\"html-content-wrapper\"><!doctype html>\r\n<html lang=\"fr\">\r\n<head>\r\n<meta charset=\"utf-8\" />\r\n<meta name=\"viewport\" content=\"width=device-width, initial-scale=1\" />\r\n<title>Tuto – IRON TV (exact)</title>\r\n<style>\r\n  /* =========================\r\n     Design moderne (sans thème auto)\r\n     — Variables surchargables par votre site\r\n  ========================== */\r\n  :root {\r\n    --bg: #0e1116;          /* fond général si non géré par le site */\r\n    --surface: #111826;     /* carte */\r\n    --ink: #e8eef9;         /* texte principal */\r\n    --muted: #94a3b8;       /* texte secondaire */\r\n    --primary: #6aa5ff;     /* accents */\r\n    --ok: #22c55e;          /* titres */\r\n    --warn: #f59e0b;\r\n    --border: rgba(255,255,255,0.10);\r\n    --ring: rgba(106,165,255,.35);\r\n    --radius: 16px;\r\n    --radius-sm: 12px;\r\n    --shadow-lg: 0 20px 60px rgba(0,0,0,.35);\r\n    --shadow-md: 0 10px 30px rgba(0,0,0,.28);\r\n    --maxw: 920px;\r\n  }\r\n\r\n  body { font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Inter, Arial, sans-serif; color: var(--ink); line-height: 1.65; margin: 0; }\r\n  .kb-wrap { max-width: var(--maxw); margin: 40px auto; padding: 0 20px; }\r\n\r\n  /* Header */\r\n  .kb-head { display: flex; justify-content: space-between; align-items: flex-end; gap: 16px; margin-bottom: 18px; }\r\n  .kb-title { font-size: clamp(26px, 3.2vw, 40px); letter-spacing: .2px; margin: 0; }\r\n  .kb-meta { display:flex; gap:10px; flex-wrap:wrap; }\r\n  .tag { display:inline-flex; align-items:center; gap:.5ch; padding:8px 12px; border:1px solid var(--border); border-radius:999px; backdrop-filter: blur(6px); box-shadow: var(--shadow-md); background: linear-gradient(180deg, rgba(255,255,255,.03), rgba(255,255,255,.00)); font-weight:600; font-size:12px; color: var(--ink); }\r\n  .tag .dot{ width:6px; height:6px; border-radius:50%; background:var(--ok); box-shadow:0 0 0 6px rgba(34,197,94,.12) }\r\n\r\n  /* Card + steps */\r\n  .kb-card { position:relative; border:1px solid var(--border); border-radius: calc(var(--radius) + 2px); background: radial-gradient(120% 140% at 5% -10%, rgba(106,165,255,.10), transparent 55%), radial-gradient(120% 140% at 100% 10%, rgba(245,158,11,.10), transparent 60%), var(--surface); box-shadow: var(--shadow-lg); padding: 22px; }\r\n  .section { position: relative; padding: 18px 18px 18px 64px; border-radius: var(--radius); border: 1px solid var(--border); background: linear-gradient(180deg, rgba(255,255,255,.03), rgba(255,255,255,.00)); box-shadow: var(--shadow-md); margin: 16px 0; }\r\n  .num { position:absolute; left:14px; top:14px; width:38px; height:38px; border-radius: 12px; display:grid; place-items:center; font-weight:800; color:#001028; background: linear-gradient(180deg, color-mix(in oklab, var(--primary), white 12%), var(--primary)); border: 1px solid color-mix(in oklab, var(--primary), black 14%); box-shadow: 0 10px 24px color-mix(in oklab, var(--primary), transparent 70%); }\r\n  h2 { margin:0 0 8px; font-size: 20px; color: var(--ok); }\r\n  p { margin:8px 0; }\r\n  .bullet { display:flex; gap:10px; align-items:flex-start; padding:10px 12px; border:1px dashed var(--border); border-radius: var(--radius-sm); background: linear-gradient(180deg, rgba(255,255,255,.03), rgba(255,255,255,.00)); }\r\n  .bullet .dot { font-weight:700; color: var(--muted); }\r\n  .sep { margin: 18px 0; text-align:center; color: var(--muted); }\r\n\r\n  /* Callouts */\r\n  .callout { border:1px solid var(--border); border-left: 4px solid var(--warn); border-radius: var(--radius); background: linear-gradient(180deg, rgba(255,255,255,.03), rgba(255,255,255,0)); padding: 14px 16px; box-shadow: var(--shadow-md); }\r\n  .callout h3 { margin:0 0 6px; font-size: 16px; color: var(--warn); }\r\n\r\n  /* Utilities */\r\n  .btn-print { display:inline-flex; align-items:center; gap:.6ch; margin-top: 26px; padding: 10px 16px; background: var(--primary); color: white; border: none; border-radius: 12px; font-weight: 700; cursor: pointer; box-shadow: 0 8px 24px rgba(0,0,0,.25); }\r\n  .btn-print:hover { transform: translateY(-1px); }\r\n  .fine { color: var(--muted); font-size: 13px; margin-top: 8px; }\r\n\r\n  @media print { .btn-print, .kb-head { display:none; } .section { break-inside: avoid; } }\r\n</style>\r\n</head>\r\n<body>\r\n\r\n<div class=\"kb-wrap\">\r\n  <header class=\"kb-head\">\r\n    <h1 class=\"kb-title\">Tuto – IRON TV (texte exact)</h1>\r\n    <div class=\"kb-meta\">\r\n      <span class=\"tag\"><span class=\"dot\"></span> Procédure</span>\r\n      <span class=\"tag\">Dernière maj : <time>05/11/2025</time></span>\r\n    </div>\r\n  </header>\r\n\r\n  <div class=\"kb-card\">\r\n    <!-- Bloc Étapes -->\r\n    <section class=\"section\" id=\"etapes\">\r\n      <div class=\"num\">1</div>\r\n      <h2>Comment generer un code IRON TV</h2>\r\n      <p>📲 Étapes pour générer un code</p>\r\n      <p>1. Connectez-vous à https://myirontv.com avec les identifiants disponible a la fin du tutoriel.</p>\r\n      <p>2. Ouvrez Google Authenticator sur un des appareils autorisés pour entrer le code de validation.</p>\r\n      <p>3. Une fois connecté, dans le menu latéral gauche, cliquez sur :</p>\r\n      <p>• IPTV ActiveCode</p>\r\n      <p>4. Cliquez ensuite sur :</p>\r\n      <p>• New Active Code (ou Nouveau Code Actif)</p>\r\n      <p>5. Dans le menu déroulant, sélectionnez :</p>\r\n      <p>• 12 mois (6 crédits)</p>\r\n      <p>6. Le système génère automatiquement un code unique.</p>\r\n      <p>7. Copiez le code généré.</p>\r\n      <p>8. Imprimez le code et remettez-le au client.</p>\r\n    </section>\r\n    <div class=\"sep\">⸻</div>\r\n\r\n    <!-- Bloc A ne pas oublier -->\r\n    <section class=\"section\" id=\"memo\">\r\n      <div class=\"num\">2</div>\r\n      <div class=\"callout\">\r\n        <h3>⚠️ À ne pas oublier</h3>\r\n        <p>• Chaque code de 12 mois consomme 6 crédits.</p>\r\n        <p>• Lorsque le nombre de crédits disponibles descend en dessous de 72, il faut prévenir un responsable immédiatement.</p>\r\n      </div>\r\n    </section>\r\n\r\n    <div class=\"sep\">---</div>\r\n\r\n    <!-- Bloc Informations de connexion -->\r\n    <section class=\"section\" id=\"login\">\r\n      <div class=\"num\">3</div>\r\n      <h2>🔐 Informations de connexion</h2>\r\n      <p>• Panel : myirontv.com</p>\r\n      <p>• Identifiant : MDGEEK</p>\r\n      <p>• Mot de passe : Azerty@123456</p>\r\n      <p>• Google Authenticator :</p>\r\n      <p>Utiliser l’un des appareils suivants pour valider l’accès :</p>\r\n      <p>• iPad de la caisse</p>\r\n      <p>• Tablette Android de l’atelier trottinette</p>\r\n      <p>• Tablette Android de l’atelier informatique</p>\r\n    </section>\r\n\r\n    <button class=\"btn-print\" onclick=\"window.print()\">🖨️ Imprimer / Exporter en PDF</button>\r\n    <p class=\"fine\">Texte conservé à l’identique, mise en forme modernisée.</p>\r\n  </div>\r\n</div>\r\n\r\n</body>\r\n</html></div>', 3, '2025-11-05 01:36:47', '2025-11-05 01:36:47', 2),
(5, 'Diag : La trottinette s’allume mais n’accélère pas', '<h1>🛠️ Diagnostic – La trottinette s’allume mais n’accélère pas<br><br>⸻<br><br>📍 Étape 0 : Faire tourner la roue pour vérifier la détection moteur<br>1. Allumer la trottinette<br>2. Faire tourner manuellement la roue arrière<br>3. Regarder si les kilomètres défilent légèrement sur le LCD<br><br>✅ Si les kilomètres réagissent → capteurs Hall moteur OK → passer à l’étape 1<br><br>❌ Si rien ne bouge → vérifier les capteurs Hall (ci-dessous)<br><br>⸻<br><br>⚙️ Test des capteurs Hall moteur<br>1. Débrancher les fils Hall du contrôleur<br>2. Les connecter au boîtier de diagnostic Hall<br>3. Faire tourner la roue manuellement<br><br>✅ Si les 3 LED s’allument à tour de rôle → capteurs Hall fonctionnels<br><br>❌ Si une LED reste éteinte ou bloquée → capteur Hall ou câblage défectueux → envisager remplacement du moteur<br><br>⸻<br><br>📍 Étape 1 : Vérifier la présence d’un code erreur sur le LCD<br>• Si un code est affiché → consulter le site constructeur<br>• Appliquer la solution proposée<br><br>✅ Si résolu → fin du diagnostic<br><br>❌ Si pas de code ou code inconnu → passer à l’étape 2<br><br>⸻<br><br>📍 Étape 2 : Tester avec une autre gâchette d’accélérateur<br>• Remplacer uniquement la gâchette<br>• Tester l’accélération<br><br>✅ Si la trottinette accélère → gâchette HS → fin du diagnostic<br><br>❌ Si pas de changement → passer à l’étape 3<br><br>⸻<br><br>📍 Étape 3 : Tester avec un nouveau LCD<br>• Remplacer uniquement l’écran LCD<br>• Tester<br><br>✅ Si ça fonctionne → LCD d’origine HS → fin du diagnostic<br><br>❌ Si toujours rien → passer à l’étape 3.5<br><br>⸻<br><br>📍 Étape 3.5 : Vérifier les capteurs de frein<br><br>Il se peut que le frein reste activé en permanence, empêchant l’accélération.<br><br>Méthodes de vérification :<br>1. Vérifier si un symbole de frein s’affiche sur le LCD (⚠️ pas toujours présent)<br>2. Débrancher les capteurs de frein (⚠️ peut générer un code erreur sur certains modèles)<br>3. Appuyer sur le frein → vérifier si la lumière arrière s’allume (⚠️ feu parfois non fonctionnel)<br>4. Tester avec une autre poignée de frein<br><br>✅ Si ça accélère → capteur de frein HS → remplacer<br><br>❌ Si toujours rien → passer à l’étape 4<br><br>⸻<br><br>📍 Étape 4 : Tester avec un nouveau contrôleur + nouveau LCD + nouvelle gâchette<br>• Remplacer les 3 éléments clés<br>• Tester l’accélération<br><br>✅ Si la trottinette accélère → passer à l’étape 5 pour isoler la pièce défectueuse<br><br>❌ Si toujours rien → passer à l’étape 7 (solution ultime)<br><br>⸻<br><br>📍 Étape 5 : Tests croisés pour isoler la panne<br><br>⸻<br><br>Test A<br>• ✅ Nouveau contrôleur<br>• ✅ Nouveau LCD<br>• ❌ Gâchette d’origine<br><br>➡️ Si ça ne fonctionne plus → gâchette d’origine défectueuse<br><br>⸻<br><br>Test B<br>• ✅ Nouveau contrôleur<br>• ❌ LCD d’origine<br>• ✅ Nouvelle gâchette<br><br>➡️ Si ça ne fonctionne plus → LCD d’origine défectueux<br><br>⸻<br><br>Test C<br>• ❌ Contrôleur d’origine<br>• ✅ Nouveau LCD<br>• ✅ Nouvelle gâchette<br><br>➡️ Si ça ne fonctionne plus → contrôleur d’origine défectueux<br><br>⸻<br><br>📍 Étape 6 (facultative) : Rebrancher les capteurs de frein un par un<br>• Pour identifier un capteur de frein précis qui bloque l’accélération<br>• Rebrancher un seul frein à la fois<br>• Tester entre chaque<br><br>⸻<br><br>📍 Étape 7 : 💡 Solution ultime – Installer un kit MiniMotors<br><br>Si aucun test ne permet de réparer la trottinette :<br><br>➡️ Remplacer tout le système par un kit MiniMotors :<br>• Contrôleur<br>• Écran LCD<br>• Gâchette<br><br>✅ Avantages :<br>• Compatible avec la plupart des moteurs brushless<br>• Fiable, réactif, facile à diagnostiquer<br>• Kit complet prêt à câbler</h1>', 2, '2025-11-05 01:42:31', '2025-11-05 01:42:31', 4),
(6, 'Comment installer ironTv sur la cle Amazon Fire Stick', '<p>&nbsp;</p><h2><strong>🔧 Installation de l’application IRON sur Fire TV</strong></h2><p>&nbsp;</p><h3><strong>1️⃣ Télécharger l’application Downloader</strong></h3><p>&nbsp;</p><p>Depuis votre Fire TV, appuyez sur le <strong>micro</strong> de la télécommande.</p><p>Dites : <strong>« Télécharge application Downloader »</strong>.</p><p>Installez l’application <strong>Downloader</strong>.</p><p>&nbsp;</p><h3><strong>2️⃣ Télécharger l’application IRON</strong></h3><p>&nbsp;</p><p>Ouvrez <strong>Downloader</strong>.</p><p>Dans la barre de saisie, entrez le <strong>code : 347824</strong></p><p>Si le code ne fonctionne pas, utilisez l’une des adresses suivantes :</p><p>mdgeek.fr/tv.apk</p><p>mdgeek.fr/tv1.apk</p><p>&nbsp;</p><h3><strong>3️⃣ Activer le mode développeur</strong></h3><p>&nbsp;</p><p>Sur la Fire TV, allez dans <strong>Paramètres</strong> (roue crantée en haut à droite).</p><p>Sélectionnez <strong>Ma Fire TV</strong>.</p><p>Choisissez <strong>À propos de</strong>.</p><p>Placez-vous sur la <strong>première option</strong> de la liste.</p><p>Appuyez <strong>7 fois</strong> sur le <strong>bouton central</strong> de la télécommande.</p><p>Un message s’affiche : <i>« Vous êtes maintenant un développeur ».</i></p><p>Revenez en arrière : un nouveau menu <strong>Options pour les développeurs</strong> est apparu.</p><p>Activez l’option <strong>Installer les applications inconnues</strong> → <strong>Autoriser pour Downloader</strong>.</p><p>&nbsp;</p><h3><strong>4️⃣ Installer l’application IRON</strong></h3><p>&nbsp;</p><p>Revenez dans <strong>Downloader</strong>.</p><p>Sélectionnez le fichier APK téléchargé précédemment.</p><p>Cliquez sur <strong>Installer</strong>.</p><p>Une fois l’installation terminée, ouvrez l’application.</p><p>&nbsp;</p><h3><strong>5️⃣ Mettre l’application en avant</strong></h3><p>&nbsp;</p><p>Pour faciliter l’accès à IRON :</p><p>Appuyez sur le <strong>bouton menu (☰)</strong> de votre télécommande lorsque vous êtes sur l’écran des applications.</p><p>Choisissez <strong>Déplacer vers l’avant</strong> pour placer IRON sur la première ligne.</p><p>&nbsp;</p><h3><strong>✅ L’installation est terminée</strong><br>&nbsp;</h3><p><strong>il ne manque plus que le code et </strong>Votre application IRON est maintenant prête à l’emploi.</p><p>&nbsp;</p><p>&nbsp;</p>', 3, '2025-11-05 01:47:11', '2025-11-05 01:47:11', 5),
(7, '🚨 Si le client obtient « CODE ALREADY USED / CODE DÉJÀ UTILISÉ »', '<p>Si le message <strong>« CODE ALREADY USED »</strong> s’affiche :</p><p>Ouvrir le tutoriel suivant :</p><p>📄 <strong>Guide de résolution et génération ID / Mot de passe IPTV Smarters</strong></p><p>👉 <a href=\"http://mdgeek.fr/codeiron.pdf\">http://mdgeek.fr/codeiron.pdf</a></p><p>&nbsp;</p>', 3, '2025-11-05 01:57:57', '2025-11-05 01:57:57', 1),
(8, '🛠️ Diagnostic – Le moteur de la trottinette tourne à l’envers', '<p>❓ Symptôme<br>• Le moteur tourne à l’envers dès qu’on accélère.<br>• La trottinette recule au lieu d’avancer.<br><br>⸻<br><br>🎯 Objectif<br><br>Corriger le sens de rotation du moteur sans endommager le contrôleur ou les capteurs Hall.<br><br>⸻<br><br>📌 Étape 1 : Identifier les fils de phases moteur<br><br>Les 3 fils de phase sont généralement :<br>• Jaune<br>• Vert<br>• Bleu<br><br>Ces fils vont du moteur vers le contrôleur.<br><br>⸻<br><br>📌 Étape 2 : Inverser deux fils de phase<br>• Inverser le fil VERT et le BLEU<br>• Exemple : Vert ↔️ Bleu<br>• Laisser le JAUNE inchangé.<br><br>✅ Si le moteur tourne dans le bon sens → passer à l’étape 4<br><br>❌ Si le moteur vibre ou ne tourne pas → passer à l’étape 3<br><br>⸻<br><br>📌 Étape 3 : Inverser les capteurs Hall (si le moteur en est équipé)<br><br>Si le moteur utilise des capteurs Hall, il faut aussi inverser deux des fils de signal Hall (souvent très fins, 5 fils en général) :<br>• Inverser les fils de capteurs Hall VERT et BLEU<br>• Exemple : fil signal Hall vert ↔️ fil signal Hall bleu<br>• Laisser les autres fils (rouge, noir, jaune) inchangés.<br><br>✅ Après inversion, le moteur doit tourner normalement<br><br>⸻<br><br>📌 Étape 4 : Tester la trottinette<br>• Accélérez lentement pour vérifier que le moteur :<br>• Tourne dans le bon sens<br>• Ne vibre pas<br>• Ne force pas<br><br>⸻<br><br>⚠️ Attention<br>• Ne jamais inverser les 3 fils de phase en même temps<br>• Ne jamais inverser seulement les fils Hall sans toucher aux phases : cela peut endommager le contrôleur<br>• Toujours tester à basse vitesse après modification</p>', 2, '2025-11-05 01:58:28', '2025-11-05 01:58:28', 1),
(9, '🛠️ Diagnostic iPhone qui redémarre en boucle', '<p>&nbsp;</p><h1><strong>🧰 Procédure de diagnostic iPhone – Démarrage impossible / blocage logo</strong></h1><p>&nbsp;</p><h3><strong>⚙️ Objectif : </strong>Identifier la cause du problème de démarrage (logo bloqué, redémarrage en boucle, écran noir, etc.) <strong>en testant les nappes et composants internes étape par étape.</strong></h3><p>&nbsp;</p><h2><strong>Étape 1️⃣ : Débrancher toutes les nappes internes</strong></h2><p>&nbsp;</p><p>Débranchez <strong>toutes les nappes</strong> : batterie, écran, capteurs, caméras, bouton Power, etc.</p><p>Branchez uniquement la <strong>batterie</strong> et <strong>le connecteur de charge</strong>.</p><p>Essayez de démarrer l’iPhone.</p><p>✅ Si l’iPhone démarre normalement → <strong>Fin du diagnostic</strong></p><p>❌ Sinon → passer à <strong>l’étape 2</strong></p><p>&nbsp;</p><h2><strong>Étape 2️⃣ : Tester avec un nouveau connecteur de charge</strong></h2><p>&nbsp;</p><p>Remplacez la <strong>nappe connecteur de charge + micro</strong>.</p><p>Essayez de démarrer l’appareil.</p><p>✅ Si l’iPhone démarre → <strong>Fin du diagnostic</strong></p><p>❌ Sinon → passer à <strong>l’étape 3</strong></p><p>&nbsp;</p><h2><strong>Étape 3️⃣ : Tester avec un nouveau bouton Power / Volume</strong></h2><p>&nbsp;</p><p>Remplacez la <strong>nappe Power / Volume / Vibreur</strong>.</p><p>Testez le démarrage.</p><p>✅ Si l’iPhone démarre → <strong>Fin du diagnostic</strong></p><p>❌ Sinon → passer à <strong>l’étape 4</strong></p><p>&nbsp;</p><h2><strong>Étape 4️⃣ : Tester avec un nouveau capteur de proximité</strong></h2><p>&nbsp;</p><p>Remplacez la <strong>nappe capteur de proximité + micro supérieur + capteur lumière</strong>.</p><p>Testez le démarrage.</p><p>✅ Si l’iPhone démarre → <strong>Fin du diagnostic</strong></p><p>❌ Sinon → passer à <strong>l’étape 5</strong></p><p>&nbsp;</p><h2><strong>Étape 5️⃣ : Tester sans caméra arrière</strong></h2><p>&nbsp;</p><p><strong>Débranchez la caméra arrière.</strong></p><p>Démarrez l’iPhone.</p><p>✅ Si l’iPhone démarre → <strong>Fin du diagnostic</strong></p><p>❌ Sinon → passer à <strong>l’étape 6</strong></p><p>&nbsp;</p><h2><strong>Étape 6️⃣ : Tester sans caméra avant</strong></h2><p>&nbsp;</p><p><strong>Débranchez la caméra avant.</strong></p><p>Testez le démarrage.</p><p>✅ Si l’iPhone démarre → <strong>Fin du diagnostic</strong></p><p>❌ Sinon → passer à <strong>l’étape 7</strong></p><p>&nbsp;</p><h2><strong>Étape 7️⃣ : Tester avec une autre batterie</strong></h2><p>&nbsp;</p><p>Remplacez la <strong>batterie</strong> par une <strong>batterie fonctionnelle</strong>.</p><p>Testez le démarrage.</p><p>✅ Si l’iPhone démarre → <strong>Fin du diagnostic</strong></p><p>❌ Sinon → <strong>problème matériel complexe</strong> → <strong>rediriger le client vers Phone Étoile.</strong></p><p>&nbsp;</p><h3><strong>📋 Notes internes</strong></h3><p>&nbsp;</p><p>Toujours <strong>déconnecter la batterie avant chaque changement de nappe</strong>.</p><p>Ne pas mélanger les vis des plaques de blindage (risque de court-circuit).</p><p>Si aucune des étapes ne résout le problème → Conseillez la boutique <strong>Phone Étoile comme derniere solution au client. ( ils sont specialisee en microsoudure )</strong></p>', 4, '2025-11-05 02:02:59', '2025-11-05 02:02:59', 5);

-- --------------------------------------------------------

--
-- Structure de la table `kb_article_ratings`
--

CREATE TABLE `kb_article_ratings` (
  `id` int NOT NULL,
  `article_id` int NOT NULL,
  `user_id` int NOT NULL,
  `is_helpful` tinyint(1) NOT NULL,
  `rated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `kb_article_tags`
--

CREATE TABLE `kb_article_tags` (
  `article_id` int NOT NULL,
  `tag_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `kb_article_tags`
--

INSERT INTO `kb_article_tags` (`article_id`, `tag_id`) VALUES
(1, 1),
(2, 2),
(5, 2),
(3, 3),
(5, 3),
(8, 3),
(3, 4),
(5, 4),
(8, 4),
(9, 4),
(4, 5),
(5, 5),
(6, 5),
(7, 5),
(4, 6),
(5, 6),
(6, 6),
(7, 6),
(4, 7),
(5, 7),
(6, 7),
(7, 7),
(4, 8),
(5, 8),
(6, 8),
(7, 8),
(9, 9),
(9, 10),
(9, 11);

-- --------------------------------------------------------

--
-- Structure de la table `kb_categories`
--

CREATE TABLE `kb_categories` (
  `id` int NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `icon` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'fas fa-folder',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `kb_categories`
--

INSERT INTO `kb_categories` (`id`, `name`, `icon`, `created_at`) VALUES
(1, 'Windows', 'fas fa-folder', '2025-11-05 00:22:36'),
(2, 'Trotinette', 'fas fa-folder', '2025-11-05 01:32:55'),
(3, 'Television', 'fas fa-folder', '2025-11-05 01:35:48'),
(4, 'Telephone Apple', 'fas fa-folder', '2025-11-05 01:58:54');

-- --------------------------------------------------------

--
-- Structure de la table `kb_files`
--

CREATE TABLE `kb_files` (
  `id` int NOT NULL,
  `filename` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` bigint NOT NULL,
  `file_type` varchar(100) DEFAULT NULL,
  `uploaded_by` int NOT NULL,
  `uploaded_at` datetime NOT NULL,
  `downloads` int DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `kb_tags`
--

CREATE TABLE `kb_tags` (
  `id` int NOT NULL,
  `name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `kb_tags`
--

INSERT INTO `kb_tags` (`id`, `name`, `created_at`) VALUES
(1, 'Windows', '2025-11-05 00:24:00'),
(2, 'Xiaomi', '2025-11-05 01:33:01'),
(3, 'Tutoriel Diag', '2025-11-05 01:34:56'),
(4, 'Diag', '2025-11-05 01:34:56'),
(5, 'Iron TV', '2025-11-05 01:36:47'),
(6, 'IPTV', '2025-11-05 01:36:47'),
(7, 'Code Iron', '2025-11-05 01:36:47'),
(8, 'IP TV', '2025-11-05 01:36:47'),
(9, 'Diag iPhone', '2025-11-05 02:02:59'),
(10, 'iPhone', '2025-11-05 02:02:59'),
(11, 'Diagnostique', '2025-11-05 02:02:59');

-- --------------------------------------------------------

--
-- Structure de la table `label_layouts`
--

CREATE TABLE `label_layouts` (
  `id` int NOT NULL,
  `shop_id` int NOT NULL,
  `layout_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_default` tinyint(1) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `lecture_annonces`
--

CREATE TABLE `lecture_annonces` (
  `message_id` int NOT NULL,
  `user_id` int NOT NULL,
  `date_lecture` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `lignes_commande_fournisseur`
--

CREATE TABLE `lignes_commande_fournisseur` (
  `id` int NOT NULL,
  `commande_id` int NOT NULL,
  `produit_id` int NOT NULL,
  `quantite` int NOT NULL,
  `prix_unitaire` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `log_statistics_cache`
--

CREATE TABLE `log_statistics_cache` (
  `id` int NOT NULL,
  `shop_id` int NOT NULL DEFAULT '0',
  `cache_key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cache_data` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` timestamp NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `marges_estimees`
--

CREATE TABLE `marges_estimees` (
  `id` int NOT NULL,
  `categorie` enum('telephone','pc','tablette') COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `prix_estime` decimal(10,2) NOT NULL,
  `marge_recommandee` decimal(10,2) NOT NULL,
  `date_creation` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modification` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `marges_reference`
--

CREATE TABLE `marges_reference` (
  `id` int NOT NULL,
  `type_reparation` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `categorie` enum('smartphone','tablet','computer') COLLATE utf8mb4_unicode_ci NOT NULL,
  `prix_achat` decimal(10,2) NOT NULL,
  `marge_pourcentage` int NOT NULL,
  `date_creation` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `messages`
--

CREATE TABLE `messages` (
  `id` int NOT NULL,
  `conversation_id` int NOT NULL,
  `sender_id` int DEFAULT NULL,
  `contenu` text COLLATE utf8mb4_unicode_ci,
  `type` enum('text','file','image','system','info') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'text',
  `date_envoi` datetime DEFAULT CURRENT_TIMESTAMP,
  `est_supprime` tinyint(1) DEFAULT '0',
  `est_modifie` tinyint(1) DEFAULT '0',
  `date_modification` datetime DEFAULT NULL,
  `est_important` tinyint(1) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `message_attachments`
--

CREATE TABLE `message_attachments` (
  `id` int NOT NULL,
  `message_id` int NOT NULL,
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_size` int NOT NULL,
  `thumbnail_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `est_image` tinyint(1) DEFAULT '0',
  `date_upload` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `message_reactions`
--

CREATE TABLE `message_reactions` (
  `id` int NOT NULL,
  `message_id` int NOT NULL,
  `user_id` int NOT NULL,
  `reaction` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_reaction` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `message_reads`
--

CREATE TABLE `message_reads` (
  `message_id` int NOT NULL,
  `user_id` int NOT NULL,
  `date_lecture` datetime DEFAULT CURRENT_TIMESTAMP,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin
) ;

-- --------------------------------------------------------

--
-- Structure de la table `message_replies`
--

CREATE TABLE `message_replies` (
  `id` int NOT NULL,
  `message_id` int NOT NULL,
  `reply_to_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `missions`
--

CREATE TABLE `missions` (
  `id` int NOT NULL,
  `titre` varchar(200) NOT NULL,
  `description` text NOT NULL,
  `mission_type_id` int NOT NULL,
  `objectif_nombre` int NOT NULL DEFAULT '1',
  `recompense_euros` decimal(10,2) NOT NULL DEFAULT '0.00',
  `recompense_points` int NOT NULL DEFAULT '0',
  `statut` enum('active','inactive','terminee') DEFAULT 'active',
  `date_debut` date NOT NULL,
  `date_fin` date DEFAULT NULL,
  `max_participants` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `missions`
--

INSERT INTO `missions` (`id`, `titre`, `description`, `mission_type_id`, `objectif_nombre`, `recompense_euros`, `recompense_points`, `statut`, `date_debut`, `date_fin`, `max_participants`, `created_at`, `updated_at`) VALUES
(1, 'asd', 'ada', 2, 2, 22.00, 10, 'active', '2025-11-10', '2025-12-10', NULL, '2025-11-10 11:24:14', '2025-11-10 11:24:14'),
(2, 'asd12', 'asd', 3, 2, 2.00, 2, 'active', '2025-11-10', '2025-12-10', NULL, '2025-11-10 11:32:30', '2025-11-10 12:12:02');

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `mission_stats`
-- (Voir ci-dessous la vue réelle)
--
CREATE TABLE `mission_stats` (
);

-- --------------------------------------------------------

--
-- Structure de la table `mission_types`
--

CREATE TABLE `mission_types` (
  `id` int NOT NULL,
  `nom` varchar(100) NOT NULL,
  `icon` varchar(50) NOT NULL,
  `couleur` varchar(7) NOT NULL,
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `mission_types`
--

INSERT INTO `mission_types` (`id`, `nom`, `icon`, `couleur`, `description`, `created_at`) VALUES
(1, 'Réparations Smartphones', 'fas fa-mobile-alt', '#3498db', 'Réparations de téléphones et tablettes', '2025-11-10 00:42:06'),
(2, 'Réparations Trottinettes', 'fas fa-bicycle', '#e74c3c', 'Réparations de trottinettes électriques', '2025-11-10 00:42:06'),
(3, 'Ventes LeBonCoin', 'fas fa-shopping-cart', '#f39c12', 'Ventes d\'appareils sur LeBonCoin', '2025-11-10 00:42:06'),
(4, 'Ventes eBay', 'fab fa-ebay', '#8e44ad', 'Ventes d\'appareils sur eBay', '2025-11-10 00:42:06'),
(5, 'Service Client', 'fas fa-headset', '#1abc9c', 'Support et service clientèle', '2025-11-10 00:42:06'),
(6, 'Inventaire', 'fas fa-boxes', '#27ae60', 'Gestion des stocks et inventaire', '2025-11-10 00:42:06');

-- --------------------------------------------------------

--
-- Structure de la table `mission_validations`
--

CREATE TABLE `mission_validations` (
  `id` int NOT NULL,
  `user_mission_id` int NOT NULL,
  `tache_numero` int NOT NULL,
  `description` text NOT NULL,
  `preuve_fichier` varchar(255) DEFAULT NULL,
  `preuve_text` text,
  `statut` enum('en_attente','approuvee','rejetee') DEFAULT 'en_attente',
  `commentaire_admin` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `validated_at` timestamp NULL DEFAULT NULL,
  `validated_by` int DEFAULT NULL,
  `date_traitement` timestamp NULL DEFAULT NULL,
  `traite_par` int DEFAULT NULL,
  `type_validation` enum('completion','progress') DEFAULT 'completion',
  `preuve_url` varchar(500) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `mission_validations`
--

INSERT INTO `mission_validations` (`id`, `user_mission_id`, `tache_numero`, `description`, `preuve_fichier`, `preuve_text`, `statut`, `commentaire_admin`, `created_at`, `validated_at`, `validated_by`, `date_traitement`, `traite_par`, `type_validation`, `preuve_url`, `updated_at`) VALUES
(1, 1, 1, 'asd', NULL, 'asd', 'approuvee', 'd', '2025-11-10 11:24:27', NULL, NULL, '2025-11-10 11:25:05', 1, 'completion', NULL, '2025-11-10 11:25:05'),
(2, 1, 1, 'asd', NULL, 'ads', 'approuvee', 'e', '2025-11-10 11:24:31', NULL, NULL, '2025-11-10 11:24:47', 1, 'completion', NULL, '2025-11-10 11:24:47'),
(3, 2, 1, 'ads', NULL, 'das', 'approuvee', '', '2025-11-10 11:32:44', NULL, NULL, '2025-11-10 11:37:31', 1, 'completion', NULL, '2025-11-10 11:37:31'),
(4, 2, 1, 'ads', NULL, 'ads', 'approuvee', '', '2025-11-10 11:32:48', NULL, NULL, '2025-11-10 11:37:27', 1, 'completion', NULL, '2025-11-10 11:37:27');

-- --------------------------------------------------------

--
-- Structure de la table `mouvements_stock`
--

CREATE TABLE `mouvements_stock` (
  `id` int NOT NULL,
  `produit_id` int NOT NULL,
  `fournisseur_id` int DEFAULT NULL,
  `type_mouvement` enum('entree','sortie') COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantite` int NOT NULL,
  `date_mouvement` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `motif` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `mouvements_stock`
--

INSERT INTO `mouvements_stock` (`id`, `produit_id`, `fournisseur_id`, `type_mouvement`, `quantite`, `date_mouvement`, `motif`, `user_id`) VALUES
(16, 1, NULL, 'sortie', 1, '2025-09-27 22:54:37', 'Utilisation dans réparation #4 (QR scan)', 6),
(17, 1, NULL, 'sortie', 1, '2025-09-27 22:54:59', 'Utilisation dans réparation #4 (QR scan)', 6),
(18, 1, NULL, 'sortie', 1, '2025-09-27 23:13:15', 'Utilisation dans réparation #4 (QR scan)', 6),
(19, 1, NULL, 'sortie', 1, '2025-09-27 23:55:00', 'Utilisation partenaire: tets - Transaction #2', 6),
(20, 1, NULL, 'sortie', 1, '2025-09-28 00:46:58', 'Utilisation partenaire: tets - Transaction #3', 6);

-- --------------------------------------------------------

--
-- Structure de la table `notifications`
--

CREATE TABLE `notifications` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `notification_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'general',
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `related_id` int DEFAULT NULL,
  `related_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `action_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_important` tinyint(1) NOT NULL DEFAULT '0',
  `is_broadcast` tinyint(1) NOT NULL DEFAULT '0',
  `created_by` int DEFAULT NULL,
  `status` enum('new','pending','read') COLLATE utf8mb4_unicode_ci DEFAULT 'new',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `read_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `notification_preferences`
--

CREATE TABLE `notification_preferences` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `type_notification` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `email_notification` tinyint(1) NOT NULL DEFAULT '0',
  `push_notification` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `notification_types`
--

CREATE TABLE `notification_types` (
  `id` int NOT NULL,
  `type_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `icon` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `color` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `importance` enum('basse','normale','haute','critique') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'normale'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `oauth_tokens`
--

CREATE TABLE `oauth_tokens` (
  `id` int NOT NULL,
  `shop_id` int NOT NULL,
  `access_token` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `refresh_token` text COLLATE utf8mb4_unicode_ci,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `paid_leave_balance`
--

CREATE TABLE `paid_leave_balance` (
  `id` int NOT NULL,
  `employee_id` int NOT NULL COMMENT 'Référence users.id',
  `year` int NOT NULL,
  `total_days` decimal(5,2) DEFAULT '25.00',
  `used_days` decimal(5,2) DEFAULT '0.00',
  `remaining_days` decimal(5,2) GENERATED ALWAYS AS ((`total_days` - `used_days`)) STORED,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `paiements_sumup`
--

CREATE TABLE `paiements_sumup` (
  `id` int NOT NULL,
  `reparation_id` int NOT NULL,
  `checkout_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `checkout_reference` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `montant` decimal(10,2) NOT NULL,
  `currency` varchar(3) COLLATE utf8mb4_unicode_ci DEFAULT 'EUR',
  `statut_paiement` enum('pending','paid','failed','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `transaction_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_paiement` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modification` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `client_info` text COLLATE utf8mb4_unicode_ci COMMENT 'Infos client JSON',
  `description` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `erreur_message` text COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `parametres`
--

CREATE TABLE `parametres` (
  `id` int NOT NULL,
  `cle` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `valeur` text COLLATE utf8mb4_unicode_ci,
  `description` text COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `parametres`
--

INSERT INTO `parametres` (`id`, `cle`, `valeur`, `description`) VALUES
(1, 'attribution_reparation_active', '1', 'Activer/désactiver la fonctionnalité d\'attribution des réparations aux employés'),
(2, 'company_name', 'MAISON DU GEEK', 'Nom de l\'entreprise'),
(3, 'company_phone', '05 55 44 33 22', 'Numéro de téléphone de l\'entreprise'),
(4, 'company_email', 'contact@maisondugeek.fr', 'Adresse email de l\'entreprise'),
(5, 'company_address', '48 bd paul doumer', 'Adresse de l\'entreprise'),
(6, 'company_logo', 'assets/uploads/logos/logo_shop_63_1758673718.png', 'Chemin vers le logo de l\'entreprise (optionnel)'),
(7, 'garantie_active', '1', 'Activer/désactiver le système de garantie (1=actif, 0=inactif)'),
(8, 'garantie_duree_defaut', '90', 'Durée par défaut de la garantie en jours'),
(9, 'garantie_description_defaut', 'Garantie pièces et main d\'œuvre', 'Description par défaut de la garantie'),
(10, 'garantie_auto_creation', '1', 'Création automatique de la garantie quand réparation effectuée (1=auto, 0=manuel)'),
(11, 'garantie_notification_expiration', '0', 'Nombre de jours avant expiration pour notifier (0=pas de notification)'),
(12, 'label_layout_default', '4x6_moderne', 'Layout d\'étiquette par défaut');

-- --------------------------------------------------------

--
-- Structure de la table `parametres_gardiennage`
--

CREATE TABLE `parametres_gardiennage` (
  `id` int NOT NULL,
  `tarif_premiere_semaine` decimal(10,2) NOT NULL DEFAULT '5.00' COMMENT 'Tarif journalier pour les 7 premiers jours',
  `tarif_intermediaire` decimal(10,2) NOT NULL DEFAULT '3.00' COMMENT 'Tarif journalier de 8 à 30 jours',
  `tarif_longue_duree` decimal(10,2) NOT NULL DEFAULT '1.00' COMMENT 'Tarif journalier au-delà de 30 jours',
  `date_modification` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `parametres_gardiennage`
--

INSERT INTO `parametres_gardiennage` (`id`, `tarif_premiere_semaine`, `tarif_intermediaire`, `tarif_longue_duree`, `date_modification`) VALUES
(1, 5.00, 3.00, 1.00, '2025-04-10 18:13:37');

-- --------------------------------------------------------

--
-- Structure de la table `parrainage_config`
--

CREATE TABLE `parrainage_config` (
  `id` int NOT NULL,
  `nombre_filleuls_requis` int NOT NULL DEFAULT '1' COMMENT 'Nombre de filleuls requis pour activer les récompenses',
  `seuil_reduction_pourcentage` decimal(10,2) NOT NULL DEFAULT '100.00' COMMENT 'Seuil de dépense en euros pour déclencher la réduction maximale',
  `reduction_min_pourcentage` int NOT NULL DEFAULT '10' COMMENT 'Pourcentage de réduction minimum (pour dépenses < seuil)',
  `reduction_max_pourcentage` int NOT NULL DEFAULT '30' COMMENT 'Pourcentage de réduction maximum (pour dépenses >= seuil)',
  `actif` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Programme actif ou non',
  `date_creation` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modification` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `parrainage_config`
--

INSERT INTO `parrainage_config` (`id`, `nombre_filleuls_requis`, `seuil_reduction_pourcentage`, `reduction_min_pourcentage`, `reduction_max_pourcentage`, `actif`, `date_creation`, `date_modification`) VALUES
(1, 1, 100.00, 10, 30, 1, '2025-04-11 02:14:22', '2025-04-11 02:14:22');

-- --------------------------------------------------------

--
-- Structure de la table `parrainage_reductions`
--

CREATE TABLE `parrainage_reductions` (
  `id` int NOT NULL,
  `parrain_id` int NOT NULL COMMENT 'ID du client parrain',
  `montant_depense_filleul` decimal(10,2) NOT NULL COMMENT 'Montant dépensé par le filleul qui a généré la réduction',
  `pourcentage_reduction` int NOT NULL COMMENT 'Pourcentage de réduction accordé',
  `montant_reduction_max` decimal(10,2) NOT NULL COMMENT 'Montant maximum de la réduction',
  `utilise` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Si la réduction a été utilisée',
  `reparation_utilisee_id` int DEFAULT NULL COMMENT 'ID de la réparation où la réduction a été utilisée',
  `date_creation` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `date_utilisation` timestamp NULL DEFAULT NULL COMMENT 'Date d''utilisation de la réduction'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `parrainage_relations`
--

CREATE TABLE `parrainage_relations` (
  `id` int NOT NULL,
  `parrain_id` int NOT NULL COMMENT 'ID du client parrain',
  `filleul_id` int NOT NULL COMMENT 'ID du client filleul',
  `date_parrainage` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `partenaires`
--

CREATE TABLE `partenaires` (
  `id` int NOT NULL,
  `nom` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telephone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `adresse` text COLLATE utf8mb4_unicode_ci,
  `date_creation` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `actif` tinyint(1) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `partenaires`
--

INSERT INTO `partenaires` (`id`, `nom`, `email`, `telephone`, `adresse`, `date_creation`, `actif`) VALUES
(1, 'tets', '', '', '', '2025-09-27 23:23:38', 1);

-- --------------------------------------------------------

--
-- Structure de la table `partner_transactions_pending`
--

CREATE TABLE `partner_transactions_pending` (
  `id` int NOT NULL,
  `partenaire_id` int NOT NULL,
  `type` enum('AVANCE','REMBOURSEMENT') NOT NULL,
  `montant` decimal(10,2) NOT NULL,
  `description` text NOT NULL,
  `reject_reason` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `validated_at` timestamp NULL DEFAULT NULL,
  `validated_by` int DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `rejection_reason` text,
  `shop_id` int NOT NULL DEFAULT '63'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `photos_reparation`
--

CREATE TABLE `photos_reparation` (
  `id` int NOT NULL,
  `reparation_id` int NOT NULL,
  `url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `date_upload` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `photos_reparation`
--

INSERT INTO `photos_reparation` (`id`, `reparation_id`, `url`, `description`, `date_upload`) VALUES
(1, 11, 'assets/images/reparations/11/photo_1762207393_690926a160264.jpeg', '', '2025-11-03 23:03:13'),
(2, 2141, 'assets/images/reparations/2141/photo_1762714395_6910e31b10e8b.jpeg', '', '2025-11-09 19:53:15'),
(3, 2141, 'assets/images/reparations/2141/photo_1762714638_6910e40e51456.jpeg', 'dasasd', '2025-11-09 19:57:18'),
(4, 2141, 'assets/images/reparations/2141/photo_1762714730_6910e46a60ddd.jpeg', 'ads', '2025-11-09 19:58:50');

-- --------------------------------------------------------

--
-- Structure de la table `pieces_avancees`
--

CREATE TABLE `pieces_avancees` (
  `id` int NOT NULL,
  `partenaire_id` int NOT NULL,
  `piece_id` int NOT NULL,
  `quantite` int NOT NULL,
  `prix_unitaire` decimal(10,2) NOT NULL,
  `date_avance` datetime DEFAULT CURRENT_TIMESTAMP,
  `statut` enum('EN_ATTENTE','VALIDÉ','REMBOURSÉ','ANNULÉ') COLLATE utf8mb4_unicode_ci DEFAULT 'EN_ATTENTE'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `pieces_utilisees_reparations`
--

CREATE TABLE `pieces_utilisees_reparations` (
  `id` int NOT NULL,
  `reparation_id` int NOT NULL,
  `produit_id` int NOT NULL,
  `quantite_utilisee` int NOT NULL DEFAULT '1',
  `date_utilisation` datetime DEFAULT CURRENT_TIMESTAMP,
  `user_id` int DEFAULT NULL,
  `notes` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `pieces_utilisees_reparations`
--

INSERT INTO `pieces_utilisees_reparations` (`id`, `reparation_id`, `produit_id`, `quantite_utilisee`, `date_utilisation`, `user_id`, `notes`) VALUES
(1, 4, 1, 1, '2025-09-28 00:54:37', 6, 'Pièce utilisée via scanner QR - Stock ajusté de 3 à 2'),
(2, 4, 1, 1, '2025-09-28 00:54:59', 6, 'Pièce utilisée via scanner QR - Stock ajusté de 2 à 1'),
(3, 4, 1, 1, '2025-09-28 01:13:15', 6, 'Pièce utilisée via scanner QR - Stock ajusté de 1 à 0');

-- --------------------------------------------------------

--
-- Structure de la table `preferences`
--

CREATE TABLE `preferences` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `theme` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'light',
  `notifications` tinyint(1) DEFAULT '1',
  `elements_per_page` int DEFAULT '20',
  `timezone_offset` int DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `presence_comments`
--

CREATE TABLE `presence_comments` (
  `id` int NOT NULL,
  `event_id` int NOT NULL,
  `user_id` int NOT NULL,
  `comment` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `presence_events`
--

CREATE TABLE `presence_events` (
  `id` int NOT NULL,
  `employee_id` int NOT NULL COMMENT 'Référence users.id',
  `type_id` int NOT NULL,
  `date_start` datetime NOT NULL,
  `date_end` datetime DEFAULT NULL,
  `duration_minutes` int DEFAULT NULL,
  `status` enum('pending','approved','rejected','cancelled') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `comment` text COLLATE utf8mb4_unicode_ci,
  `document_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Chemin vers le document justificatif',
  `created_by` int NOT NULL,
  `approved_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `presence_events`
--

INSERT INTO `presence_events` (`id`, `employee_id`, `type_id`, `date_start`, `date_end`, `duration_minutes`, `status`, `comment`, `document_path`, `created_by`, `approved_by`, `created_at`, `updated_at`) VALUES
(1, 6, 1, '2025-11-07 00:00:00', NULL, 30, 'rejected', 'Test de retard depuis le script', NULL, 6, 6, '2025-11-07 00:39:50', '2025-11-08 20:01:10'),
(2, 6, 1, '2025-11-07 00:00:00', NULL, 30, 'approved', 'sad', NULL, 6, 6, '2025-11-07 00:40:16', '2025-11-08 19:59:21'),
(3, 6, 1, '2025-11-08 00:00:00', NULL, 13, 'rejected', 'bus', NULL, 6, 6, '2025-11-08 20:01:56', '2025-11-08 20:02:54'),
(4, 6, 1, '2025-11-08 00:00:00', NULL, 13, 'approved', 'bus', NULL, 6, 6, '2025-11-08 20:02:17', '2025-11-08 20:58:41'),
(5, 13, 1, '2025-11-08 00:00:00', NULL, 21, 'rejected', 'das', NULL, 13, 6, '2025-11-08 20:59:25', '2025-11-08 21:07:33'),
(6, 13, 1, '2025-11-08 00:00:00', NULL, 21, 'rejected', 'das', NULL, 13, 6, '2025-11-08 21:03:12', '2025-11-08 21:07:33'),
(7, 13, 1, '2025-11-08 00:00:00', NULL, 21, 'approved', 'das', NULL, 13, 6, '2025-11-08 21:05:24', '2025-11-08 21:07:32');

-- --------------------------------------------------------

--
-- Structure de la table `presence_history`
--

CREATE TABLE `presence_history` (
  `id` int NOT NULL,
  `event_id` int NOT NULL,
  `field_changed` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `old_value` text COLLATE utf8mb4_unicode_ci,
  `new_value` text COLLATE utf8mb4_unicode_ci,
  `changed_by` int NOT NULL,
  `changed_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `presence_types`
--

CREATE TABLE `presence_types` (
  `id` int NOT NULL,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `display_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `description` text COLLATE utf8mb4_unicode_ci,
  `color_code` varchar(7) COLLATE utf8mb4_unicode_ci DEFAULT '#007bff',
  `is_paid` tinyint(1) DEFAULT '0',
  `affects_salary` tinyint(1) DEFAULT '1',
  `is_absence` tinyint(1) DEFAULT '1',
  `is_late` tinyint(1) DEFAULT '0',
  `requires_justification` tinyint(1) DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `presence_types`
--

INSERT INTO `presence_types` (`id`, `name`, `display_name`, `description`, `color_code`, `is_paid`, `affects_salary`, `is_absence`, `is_late`, `requires_justification`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'retard', 'Retard', 'Retard au travail', '#f59e0b', 1, 1, 0, 1, 0, 1, '2025-11-07 00:39:02', '2025-11-07 00:39:02'),
(2, 'absence', 'Absence', 'Absence non justifiée', '#ef4444', 0, 1, 1, 0, 0, 1, '2025-11-07 00:39:02', '2025-11-07 00:39:02'),
(3, 'conge_paye', 'Congé Payé', 'Congé avec rémunération', '#10b981', 1, 1, 1, 0, 0, 1, '2025-11-07 00:39:02', '2025-11-07 00:39:02'),
(4, 'conge_sans_solde', 'Congé Sans Solde', 'Congé personnel non rémunéré', '#06b6d4', 0, 1, 1, 0, 0, 1, '2025-11-07 00:39:02', '2025-11-07 00:39:02');

-- --------------------------------------------------------

--
-- Structure de la table `produits`
--

CREATE TABLE `produits` (
  `id` int NOT NULL,
  `reference` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nom` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `categorie_id` int DEFAULT NULL,
  `fournisseur_id` int DEFAULT NULL,
  `prix_achat` decimal(10,2) DEFAULT NULL,
  `prix_vente` decimal(10,2) DEFAULT NULL,
  `quantite` int DEFAULT '0',
  `seuil_alerte` int DEFAULT '5',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `status` enum('normal','temporaire','a_retourner') COLLATE utf8mb4_unicode_ci DEFAULT 'normal',
  `date_limite_retour` date DEFAULT NULL,
  `motif_retour` text COLLATE utf8mb4_unicode_ci,
  `suivre_stock` tinyint(1) DEFAULT '0' COMMENT 'Indique si le produit doit être suivi dans le système de vérification de stock'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `produits`
--

INSERT INTO `produits` (`id`, `reference`, `nom`, `description`, `categorie_id`, `fournisseur_id`, `prix_achat`, `prix_vente`, `quantite`, `seuil_alerte`, `created_at`, `updated_at`, `status`, `date_limite_retour`, `motif_retour`, `suivre_stock`) VALUES
(1, '30058569', 'saber', 'test', NULL, 2, 222.00, 2.00, 7, 5, '2025-09-27 21:49:03', '2025-10-14 22:45:12', 'normal', NULL, NULL, 1),
(2, '3667075067146', 'verre trempe iphone 16 pro', 'verre trempe iphone 16 pro', NULL, 2, 0.70, 10.00, 1, 5, '2025-09-28 21:22:04', '2025-09-28 21:22:09', 'normal', NULL, NULL, 1),
(3, '3701569368019', 'Ecran A53', '', NULL, 2, 10.00, 50.00, 0, 5, '2025-09-30 11:00:29', '2025-09-30 11:00:29', 'normal', NULL, NULL, 0),
(4, '0458210015541', 'Hshd', 'Djdjd', NULL, 11, 8.00, 8.00, 0, 5, '2025-11-09 23:48:41', '2025-11-09 23:48:41', 'normal', NULL, NULL, 0),
(5, '0458210014421', 'Dhe', 'Ehe', NULL, 2, 6.00, 6.00, 0, 5, '2025-11-09 23:49:24', '2025-11-09 23:49:24', 'normal', NULL, NULL, 0);

-- --------------------------------------------------------

--
-- Structure de la table `push_subscriptions`
--

CREATE TABLE `push_subscriptions` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `endpoint` varchar(512) COLLATE utf8mb4_unicode_ci NOT NULL,
  `auth_key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `p256dh_key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `rachat_appareils`
--

CREATE TABLE `rachat_appareils` (
  `id` int NOT NULL,
  `client_id` int NOT NULL,
  `type_appareil` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `photo_identite` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `photo_appareil` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `signature` text COLLATE utf8mb4_general_ci NOT NULL,
  `client_photo` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `date_rachat` datetime DEFAULT CURRENT_TIMESTAMP,
  `sin` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `fonctionnel` tinyint(1) DEFAULT '0',
  `prix` decimal(10,2) DEFAULT NULL,
  `modele` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `numero_serie` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_by` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `rachat_appareils`
--

INSERT INTO `rachat_appareils` (`id`, `client_id`, `type_appareil`, `photo_identite`, `photo_appareil`, `signature`, `client_photo`, `date_rachat`, `sin`, `fonctionnel`, `prix`, `modele`, `numero_serie`, `created_by`) VALUES
(1, 1, 'iPhone', 'test_identite.jpg', 'test_appareil.jpg', 'test_signature.png', 'test_client.jpg', '2025-11-04 01:06:26', 'TEST123456789', 0, 450.00, 'iPhone 14 Pro', NULL, NULL),
(3, 1029, 'smartphone', 'identite_1762395176_690c0428d03fd.jpg', 'appareil_1762395176_690c0428d0422.jpg', 'signature_1762395176_690c0428d0384.png', 'client_1762395176_690c0428d043a.jpg', '2025-11-06 03:12:56', 'sdsd', 1, 0.00, 'sdsd', NULL, NULL),
(4, 1029, 'smartphone', 'identite_1762395247_690c046f08b93.jpg', 'appareil_1762395247_690c046f08bba.jpg', 'signature_1762395247_690c046f089a3.png', 'client_1762395247_690c046f08bd2.jpg', '2025-11-06 03:14:07', '21', 1, 0.00, '21', NULL, NULL),
(5, 1339, 'smartphone', 'identite_1762395284_690c0494cec58.jpg', 'appareil_1762395284_690c0494cec84.jpg', 'signature_1762395284_690c0494cebda.png', 'client_1762395284_690c0494ceca6.jpg', '2025-11-06 03:14:44', '21', 1, 0.00, '21', NULL, NULL),
(6, 1279, 'tablette', 'identite_1762395411_690c051327080.jpg', 'appareil_1762395411_690c0513270ae.jpg', 'signature_1762395411_690c051326ff2.png', 'client_1762395411_690c0513270c9.jpg', '2025-11-06 03:16:51', 'asd', 1, 2.00, 'sad', NULL, NULL),
(7, 1279, 'tablette', 'identite_1762395464_690c0548e649a.jpg', 'appareil_1762395464_690c0548e64ce.jpg', 'signature_1762395464_690c0548e638a.png', 'client_1762395464_690c0548e650c.jpg', '2025-11-06 03:17:44', 'das', 1, 21.00, 'sad', NULL, NULL),
(8, 1029, 'tablette', 'identite_1762395792_690c0690eed63.jpg', 'appareil_1762395792_690c0690eed86.jpg', 'signature_1762395792_690c0690eed05.png', 'client_1762395792_690c0690eedc2.jpg', '2025-11-06 03:23:12', 'dsfds', 1, 21.00, 'dfds', NULL, NULL),
(9, 1029, 'tablette', 'identite_1762395838_690c06be3e3ce.jpg', 'appareil_1762395838_690c06be3e3fd.jpg', 'signature_1762395838_690c06be3e351.png', 'client_1762395838_690c06be3e41f.jpg', '2025-11-06 03:23:58', 'ds', 1, 21.00, 'sad', NULL, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `reclamations_garantie`
--

CREATE TABLE `reclamations_garantie` (
  `id` int NOT NULL,
  `garantie_id` int NOT NULL,
  `reparation_id` int NOT NULL,
  `client_id` int NOT NULL,
  `date_reclamation` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `description_probleme` text NOT NULL,
  `statut` enum('en_attente','acceptee','refusee','traitee') DEFAULT 'en_attente',
  `decision_admin` text COMMENT 'Décision et justification de l''admin',
  `nouvelle_reparation_id` int DEFAULT NULL COMMENT 'ID de la nouvelle réparation si acceptée',
  `employe_traite_id` int DEFAULT NULL COMMENT 'Employé qui a traité la réclamation',
  `date_traitement` timestamp NULL DEFAULT NULL,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `relance_automatique_config`
--

CREATE TABLE `relance_automatique_config` (
  `id` int NOT NULL,
  `shop_id` int NOT NULL,
  `est_active` tinyint(1) DEFAULT '0',
  `relances_horaires` json DEFAULT NULL,
  `derniere_execution` datetime DEFAULT NULL,
  `date_creation` datetime DEFAULT CURRENT_TIMESTAMP,
  `date_modification` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `relance_automatique_config`
--

INSERT INTO `relance_automatique_config` (`id`, `shop_id`, `est_active`, `relances_horaires`, `derniere_execution`, `date_creation`, `date_modification`) VALUES
(1, 1, 0, '[\"09:00\", \"14:00\", \"17:00\"]', NULL, '2025-09-15 16:13:39', '2025-09-15 16:13:39'),
(2, 63, 1, '[\"09:00\", \"15:00\", \"18:18\"]', NULL, '2025-09-15 18:14:16', '2025-09-18 10:43:08');

-- --------------------------------------------------------

--
-- Structure de la table `relance_automatique_logs`
--

CREATE TABLE `relance_automatique_logs` (
  `id` int NOT NULL,
  `shop_id` int NOT NULL,
  `devis_id` int NOT NULL,
  `heure_programmee` time NOT NULL,
  `date_execution` datetime NOT NULL,
  `statut` enum('succes','echec') COLLATE utf8mb4_unicode_ci DEFAULT 'succes',
  `message` text COLLATE utf8mb4_unicode_ci,
  `date_creation` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `reparations`
--

CREATE TABLE `reparations` (
  `id` int NOT NULL,
  `client_id` int NOT NULL,
  `type_appareil` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `marque` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `modele` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description_probleme` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_reception` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modification` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `date_fin_prevue` date DEFAULT NULL,
  `statut` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'nouvelle_intervention',
  `statut_id` int DEFAULT NULL,
  `statut_categorie` int DEFAULT NULL,
  `signature` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `prix` decimal(10,2) DEFAULT NULL,
  `notes_techniques` text COLLATE utf8mb4_unicode_ci,
  `notes_finales` text COLLATE utf8mb4_unicode_ci,
  `photo_appareil` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mot_de_passe` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `etat_esthetique` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `prix_reparation` decimal(10,2) DEFAULT '0.00',
  `devis_envoye` enum('OUI','NON') COLLATE utf8mb4_unicode_ci DEFAULT 'NON',
  `devis_accepte` enum('en_attente','oui','non') COLLATE utf8mb4_unicode_ci DEFAULT 'en_attente',
  `date_envoi_devis` timestamp NULL DEFAULT NULL,
  `date_reponse_devis` timestamp NULL DEFAULT NULL,
  `photos` text COLLATE utf8mb4_unicode_ci,
  `urgent` tinyint(1) DEFAULT '0',
  `commande_requise` tinyint(1) DEFAULT '0',
  `archive` enum('OUI','NON') COLLATE utf8mb4_unicode_ci DEFAULT 'NON',
  `employe_id` int DEFAULT NULL,
  `date_gardiennage` date DEFAULT NULL COMMENT 'Date de début du gardiennage',
  `gardiennage_facture` decimal(10,2) DEFAULT NULL COMMENT 'Montant facturé pour le gardiennage',
  `parrain_id` int DEFAULT NULL COMMENT 'ID du client parrain si le client est un filleul',
  `reduction_parrainage` decimal(10,2) DEFAULT NULL COMMENT 'Montant de la réduction appliquée via parrainage',
  `reduction_parrainage_pourcentage` int DEFAULT NULL COMMENT 'Pourcentage de la réduction parrainage appliquée',
  `signature_client` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `photo_signature` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `photo_client` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `accept_conditions` tinyint(1) DEFAULT '0',
  `proprietaire` tinyint(1) DEFAULT '0',
  `signature_devis` longtext COLLATE utf8mb4_unicode_ci COMMENT 'Signature électronique du client pour acceptation devis (base64)',
  `date_signature_devis` datetime DEFAULT NULL COMMENT 'Date et heure de la signature du devis',
  `garantie_id` int DEFAULT NULL,
  `date_garantie_debut` timestamp NULL DEFAULT NULL,
  `date_garantie_fin` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `reparations`
--

INSERT INTO `reparations` (`id`, `client_id`, `type_appareil`, `marque`, `modele`, `description_probleme`, `date_reception`, `date_modification`, `date_fin_prevue`, `statut`, `statut_id`, `statut_categorie`, `signature`, `prix`, `notes_techniques`, `notes_finales`, `photo_appareil`, `mot_de_passe`, `etat_esthetique`, `prix_reparation`, `devis_envoye`, `devis_accepte`, `date_envoi_devis`, `date_reponse_devis`, `photos`, `urgent`, `commande_requise`, `archive`, `employe_id`, `date_gardiennage`, `gardiennage_facture`, `parrain_id`, `reduction_parrainage`, `reduction_parrainage_pourcentage`, `signature_client`, `photo_signature`, `photo_client`, `accept_conditions`, `proprietaire`, `signature_devis`, `date_signature_devis`, `garantie_id`, `date_garantie_debut`, `date_garantie_fin`) VALUES
(1, 1, 'Informatique', '', 'test', 'ECRAN : REMPLACEMENT_DE_LA_VITRE OU REMPLACEMENT_ECRAN_COMPLET', '2025-09-23 17:10:18', '2025-10-13 01:25:27', NULL, 'termine', 3, 1, NULL, NULL, '', NULL, 'assets/images/reparations/repair_68d2d47a02737.jpg', '', NULL, 2.00, 'OUI', 'oui', '2025-09-23 23:44:47', '2025-09-24 00:17:53', NULL, 0, 0, 'NON', 6, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2, 1, 'Informatique', '', 'IPHONE 8', 'ECRAN : DA', '2025-09-24 00:14:44', '2025-11-03 22:37:50', NULL, 'reparation_effectue', 9, 1, NULL, NULL, '', NULL, 'assets/images/reparations/repair_68d337f41238c.jpg', '', NULL, 22.00, 'OUI', 'non', '2025-09-24 23:36:23', '2025-09-24 23:36:36', NULL, 0, 0, 'NON', 6, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(3, 1, 'Informatique', '', 'test', 'ECRAN : REMPLACEMENT_DE_LA_VITRE OU REMPLACEMENT_ECRAN_COMPLET', '2025-09-24 18:20:06', '2025-11-02 23:23:31', NULL, 'reparation_effectue', 1, 1, NULL, NULL, '', NULL, 'assets/images/reparations/repair_68d43656a79ae.jpg', '', NULL, 129.00, 'OUI', 'oui', '2025-09-27 17:19:34', '2025-09-27 17:20:39', NULL, 0, 0, 'NON', 6, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(4, 1, 'Trottinette', '', 'saber', 'dasksadjkj', '2025-09-24 18:21:07', '2025-10-23 19:34:26', NULL, 'reparation_effectue', 9, 1, NULL, NULL, '', NULL, 'assets/images/reparations/repair_68d43693f1056.jpg', '', NULL, 109.00, 'OUI', 'oui', '2025-09-25 10:29:50', '2025-09-25 10:31:08', NULL, 0, 0, 'NON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(5, 1, 'Trottinette', '', 'SABER', 'RAS', '2025-10-01 01:53:07', '2025-10-13 01:24:58', NULL, 'termine', NULL, 1, NULL, NULL, '', NULL, 'assets/images/reparations/repair_68dc898324f7a.jpg', '', NULL, 21.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 1, 'NON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(6, 5, 'Trottinette', '', 'ads', 'Cycle : PRECISEZ_AVEC_OU_SANS_CHAMBRE_ET_PRECISEZ_LE_TYPE_ET_LA_TAILLE_DU_PNEU', '2025-10-12 11:47:13', '2025-10-12 13:47:13', NULL, 'En attente', NULL, NULL, NULL, NULL, '', NULL, NULL, '', NULL, 21.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(7, 5, 'Trottinette', '', 'asd', 'Electronique : MERCI_D_INDIQUER_DE_FACON_CLAIRE_ET_PRECISE_LE_PROBLEME_DE_L_APPAREIL', '2025-10-12 11:48:44', '2025-11-03 22:07:39', NULL, 'reparation_effectue', NULL, NULL, NULL, NULL, '', NULL, 'assets/images/reparations/repair_68ebb1bc54076.jpg', '', NULL, 12.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', 6, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(8, 1, 'Trottinette', '', 'asd', 'Electronique : MERCI_D_INDIQUER_DE_FACON_CLAIRE_ET_PRECISE_LE_PROBLEME_DE_L_APPAREIL', '2025-10-12 11:49:45', '2025-10-24 18:36:05', NULL, 'En attente', NULL, NULL, NULL, NULL, '', NULL, 'assets/images/reparations/repair_68ebb1f92e832.jpg', '', NULL, 12.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', 12, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(9, 7, 'Trottinette', '', 'asd', 'Electronique : MERCI_D_INDIQUER_DE_FACON_CLAIRE_ET_PRECISE_LE_PROBLEME_DE_L_APPAREIL', '2025-10-12 11:51:24', '2025-10-13 01:24:58', NULL, 'termine', NULL, NULL, NULL, NULL, '', NULL, 'assets/images/reparations/repair_68ebb25c57b9e.jpg', '', NULL, 2.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(10, 1, 'Trottinette', '', 'sav', 'Autre : MERCI_D_INDIQUER_DE_FACON_CLAIRE_ET_PRECISE_LE_PROBLEME_DE_L_APPAREIL', '2025-10-12 11:52:12', '2025-11-03 22:14:53', NULL, 'reparation_effectue', 11, NULL, NULL, NULL, 'sewewewq', NULL, 'assets/images/reparations/repair_68ebb28cd8fa2.jpg', '', NULL, 222.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', 6, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(11, 7, 'Trottinette', '', 'dsa', 'Electronique : MERCI_D_INDIQUER_DE_FACON_CLAIRE_ET_PRECISE_LE_PROBLEME_DE_L_APPAREIL', '2025-10-19 20:26:56', '2025-11-03 22:17:32', NULL, 'reparation_effectue', 2, NULL, NULL, NULL, '', NULL, NULL, '', NULL, 21.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', 6, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(12, 1, 'Informatique', '', 'Macbook Pro', 'ECRAN : Remplacement ecran complet', '2025-10-19 22:55:33', '2025-11-09 01:43:17', NULL, 'reparation_effectue', 6, NULL, NULL, NULL, '', NULL, NULL, '', NULL, 80.00, 'OUI', 'oui', '2025-11-08 23:24:35', '2025-11-05 17:21:57', NULL, 0, 0, 'NON', 6, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(13, 7, 'Trottinette', '', 'sa', 'Electronique : MERCI_D_INDIQUER_DE_FACON_CLAIRE_ET_PRECISE_LE_PROBLEME_DE_L_APPAREIL', '2025-10-20 17:53:39', '2025-11-09 01:47:30', NULL, 'reparation_effectue', 6, NULL, NULL, NULL, '', NULL, NULL, '', NULL, 22.00, 'OUI', 'oui', '2025-11-08 23:20:35', '2025-11-08 23:20:54', NULL, 0, 0, 'NON', 6, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2000, 1019, 'Trottinette', '', 'Ninebot E45', 'Remplacement du garde boue et de la béquille', '2025-04-16 19:40:04', '2025-07-15 17:29:28', NULL, 'gardiennage', 9, 3, NULL, NULL, 'Le garde boue de ce modele est introuvable. Nous avons fait au mieux pour trouver un garde boue qui va sur ce véhicule. Celui ci est un garde boue Sport.', NULL, 'assets/images/reparations/repair_680023b466504.jpg', '', NULL, 70.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 1, 'NON', NULL, '2025-07-15', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2001, 1040, 'Trottinette', '', 'Xiaomi M365', 'Remplacement Garde boue / Béquille / Cache LCD', '2025-04-16 19:44:53', '2025-04-20 09:03:52', NULL, 'restitue', 11, 4, NULL, NULL, '', '', 'assets/images/reparations/repair_680024d5542cd.jpg', '', NULL, 40.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 1, 'NON', 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2002, 1011, 'Trottinette', '', 'Ninebot P65', 'Potence a remplacer / Poignee de frein a remplacer', '2025-04-16 19:51:07', '2025-05-30 08:14:03', NULL, 'annule', 13, 3, NULL, NULL, '', NULL, 'assets/images/reparations/repair_6800264b8d2cb.jpg', '', NULL, 150.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2003, 1011, 'Informatique', '', 'iPad A2297', 'SAV - Vitre Tactile a remplacer', '2025-04-16 19:56:54', '2025-04-22 21:59:57', NULL, 'gardiennage', 5, 4, NULL, NULL, '', 'TEST comment', 'assets/images/reparations/repair_680027a6acb5c.jpg', '', NULL, 0.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', 1, '2025-04-22', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2004, 1049, 'Trottinette', '', 'Ecross AR5', 'Remplacement KIT Controlleur / LCD', '2025-04-16 20:07:55', '2025-04-26 14:56:20', NULL, 'restitue', 5, 5, NULL, NULL, '', NULL, 'assets/images/reparations/repair_68002a3ba977b.jpg', '', NULL, 200.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 1, 'NON', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2005, 1036, 'Informatique', '', 'TOUR PC', 'Diag', '2025-04-16 20:39:47', '2025-05-02 08:42:18', NULL, 'restitue', 5, 4, NULL, NULL, '', NULL, 'assets/images/reparations/repair_680031b3a76fc.jpg', '', NULL, 0.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', 3, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2006, 1050, 'Trottinette', '', 'Dualtron Mini Special', 'reviser les freins / feu arriere gauche manquant / gonfler les pneus /  béquilles / sécurité pliage casse', '2025-04-16 20:48:54', '2025-05-03 06:52:01', NULL, 'archive', 14, 4, NULL, NULL, '', NULL, 'assets/images/reparations/repair_680033d6b3ce9.jpg', '', NULL, 120.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2007, 1052, 'Informatique', '', 'iPad Air A1474', 'Batterie a remplacer', '2025-04-16 21:10:23', '2025-04-22 14:53:10', NULL, 'restitue', 5, 5, NULL, NULL, '', '', 'assets/images/reparations/repair_680038df8c617.jpg', '', NULL, 0.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 1, 'NON', 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2008, 1143, 'Informatique', '', 'iPhone 7', 'remplacer batterie + changer écran blanc en noir', '2025-05-10 10:33:35', '2025-05-13 14:30:49', NULL, 'restitue', 9, 1, NULL, NULL, '', NULL, 'assets/images/reparations/repair_681f479fb2d16.jpg', '0000', NULL, 0.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2009, 1171, 'Trottinette', '', 'G2', 'remplacement chambre arriere', '2025-05-10 11:32:20', '2025-05-15 10:32:10', NULL, 'restitue', 9, 1, NULL, NULL, '', NULL, 'assets/images/reparations/repair_681f5564653e0.jpg', '', NULL, 50.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2010, 1172, 'Trottinette', '', 'wespeed', 'reglage frein, resserrage ou remplacement plaquette', '2025-05-10 13:28:20', '2025-05-15 10:32:10', NULL, 'restitue', 9, 1, NULL, NULL, '', NULL, 'assets/images/reparations/repair_681f70948c9ad.jpg', '', NULL, 19.99, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2011, 1173, 'Trottinette', '', 'Ninebot g2', 'diag : no power', '2025-05-10 13:37:46', '2025-06-07 08:39:38', NULL, 'restitue', 9, 4, NULL, NULL, '', NULL, 'assets/images/reparations/repair_681f72cae346f.jpg', '2222', NULL, 0.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2012, 1174, 'Informatique', '', 'Redmi 10c', 'AUTRE : Camera', '2025-05-13 06:13:38', '2025-05-15 10:33:01', NULL, 'restitue', 11, 1, NULL, NULL, '', NULL, 'assets/images/reparations/repair_6822ff329b9ee.jpg', '', NULL, 39.90, 'NON', 'en_attente', NULL, NULL, NULL, 0, 1, 'NON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2013, 1175, 'Informatique', '', 'Samsung A12', 'ECRAN : REMPLACEMENT_DE_LA_VITRE OU REMPLACEMENT_ECRAN_COMPLET', '2025-05-13 06:48:36', '2025-07-03 09:02:47', NULL, 'restitue', 11, 1, NULL, NULL, '', NULL, 'assets/images/reparations/repair_6823076414d34.jpg', '', NULL, 59.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 1, 'NON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2014, 1057, 'Informatique', '', 'm2101k6g', 'ECRAN : Ecran complet', '2025-05-13 07:48:28', '2025-05-15 10:33:12', NULL, 'restitue', 11, 1, NULL, NULL, '', NULL, 'assets/images/reparations/repair_6823156c0a4d8.jpg', '', NULL, 50.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2015, 1176, 'Informatique', '', 'thinkpad', 'AUTRE : Fan Error', '2025-05-13 07:50:37', '2025-06-25 11:05:36', NULL, 'restitue', 11, 3, NULL, NULL, '=== DEVIS DÉTAILLÉ - 25/06/2025 10:11 ===\\n\\n🔧 PANNES IDENTIFIÉES :\\nFan Error\\n\\n💡 SOLUTIONS PROPOSÉES :\\n• Extraction disque dur : 30,00€\\n• Boitier externe m2 : 20,00€\\n==========================================\\n', NULL, 'assets/images/reparations/repair_682315ed9a755.jpg', '', NULL, 50.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2016, 1177, 'Trottinette', '', 'Dualtron Mini', 'Cycle : Pneu arriere', '2025-05-13 11:39:54', '2025-05-13 14:17:30', NULL, 'restitue', 11, 1, NULL, NULL, '', NULL, 'assets/images/reparations/repair_68234baaa755d.jpg', '', NULL, 49.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2017, 1178, 'Trottinette', '', 'Wespeed', 'Diag Electronique', '2025-05-14 06:47:34', '2025-05-23 11:15:52', NULL, 'restitue', 11, 1, NULL, NULL, '', NULL, 'assets/images/reparations/repair_682458a679b6f.jpg', '', NULL, 0.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2018, 1178, 'Trottinette', '', 'Pure', 'Diagnotique Alimentation', '2025-05-14 06:48:23', '2025-06-11 08:54:42', NULL, 'gardiennage', 12, 1, NULL, NULL, '', NULL, 'assets/images/reparations/repair_682458d73a907.jpg', '', NULL, 129.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2019, 1179, 'Trottinette', '', 'M365', 'Alimentation : Batterie', '2025-05-14 07:12:42', '2025-05-27 12:33:31', NULL, 'archive', 14, 4, NULL, NULL, '', NULL, 'assets/images/reparations/repair_68245e8ac313b.jpg', '', NULL, 0.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 1, 'NON', 3, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2020, 1180, 'Trottinette', '', 'Wespeed', 'Autre : Cable Frein', '2025-05-14 07:55:44', '2025-05-20 08:13:29', NULL, 'restitue', 11, 1, NULL, NULL, '', NULL, 'assets/images/reparations/repair_682468a04e302.jpg', '', NULL, 20.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2021, 1181, 'Trottinette', '', 'Orni', 'Electronique : No power', '2025-05-14 10:43:24', '2025-06-07 08:39:38', NULL, 'restitue', 9, 1, NULL, NULL, '', NULL, 'assets/images/reparations/repair_68248fecdb6ef.jpg', '', NULL, 0.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2022, 1182, 'Trottinette', '', 'Urban Glide 140', 'Cycle : Draisienne', '2025-05-14 11:13:38', '2025-05-27 12:30:17', NULL, 'archive', 14, 1, NULL, NULL, '', NULL, 'assets/images/reparations/repair_682497024414a.jpg', '', NULL, 50.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2023, 1183, 'Trottinette', '', 'Urban Glide 14 pouce', 'Cycle : Pneu + Chambre', '2025-05-14 12:45:33', '2025-05-27 12:30:28', NULL, 'archive', 14, 1, NULL, NULL, '', NULL, 'assets/images/reparations/repair_6824ac8d9d985.jpg', '', NULL, 60.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2024, 1185, 'Trottinette', '', 'Pure', 'Cycle : Chambre', '2025-05-15 09:06:18', '2025-05-15 14:00:21', NULL, 'restitue', 11, 1, NULL, NULL, '', NULL, 'assets/images/reparations/repair_6825caaabc21d.jpg', '', NULL, 30.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2025, 1186, 'Trottinette', '', 'Jeep', 'Alimentation : MERCI_D_INDIQUER_DE_FACON_CLAIRE_ET_PRECISE_LE_PROBLEME_DE_L_APPAREIL', '2025-05-15 09:27:21', '2025-05-23 11:15:37', NULL, 'restitue', 11, 1, NULL, NULL, '', NULL, 'assets/images/reparations/repair_6825cf996e7eb.jpg', '', NULL, 0.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2026, 1188, 'Informatique', '', 'Macbook', 'AUTRE : Windows Install', '2025-05-16 06:40:17', '2025-05-20 08:12:40', NULL, 'restitue', 11, 1, NULL, NULL, 'CAVALO', NULL, 'assets/images/reparations/repair_6826f9f1b83e0.jpg', '', NULL, 0.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2027, 1189, 'Informatique', '', 'IPHONE 13 PRO MAX', 'AUTRE : CAMERA', '2025-05-16 10:07:16', '2025-05-17 07:48:08', NULL, 'restitue', 11, 1, NULL, NULL, '', NULL, 'assets/images/reparations/repair_68272a7435b2d.jpg', '0804', NULL, 0.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2028, 1190, 'Informatique', '', 'iPhone 14', 'AUTRE : Dommage luquide', '2025-05-16 11:14:12', '2025-05-17 07:48:05', NULL, 'annule', 13, 1, NULL, NULL, '', NULL, 'assets/images/reparations/repair_68273a2492583.jpg', '', NULL, 0.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2029, 1191, 'Trottinette', '', 'Mini special', 'Remplacement chambre a air arriere', '2025-05-17 11:06:45', '2025-06-07 08:39:38', NULL, 'restitue', 5, 4, NULL, NULL, '', NULL, 'assets/images/reparations/repair_682889e5a7338.jpg', '', NULL, 0.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', 3, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2030, 1192, 'Trottinette', '', 'Ninebot', 'Cycle : Frein + Guidon + Gonflage', '2025-05-17 11:14:28', '2025-05-21 06:22:18', NULL, 'restitue', 11, 3, NULL, NULL, '', NULL, 'assets/images/reparations/repair_68288bb47e341.jpg', '', NULL, 89.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 1, 'NON', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2031, 1193, 'Trottinette', '', 'Wespeed', 'Autre : Garde boue', '2025-05-17 11:34:43', '2025-05-30 07:43:33', NULL, 'restitue', 11, 1, NULL, NULL, '', NULL, 'assets/images/reparations/repair_682890737f30b.jpg', '', NULL, 40.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 1, 'NON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2032, 1195, 'Informatique', '', 'samsung a40', 'devis remplacement connecteur de charge', '2025-05-17 13:33:26', '2025-06-07 08:39:38', NULL, 'restitue', 5, 4, NULL, NULL, '', NULL, 'assets/images/reparations/repair_6828ac4667ed1.jpg', '', NULL, 0.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', 3, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2033, 1197, 'Trottinette', '', 'Pure', 'Pneu + frein', '2025-05-20 06:20:09', '2025-06-07 08:39:38', NULL, 'restitue', 9, 1, NULL, NULL, '', NULL, 'assets/images/reparations/repair_682c3b3930934.jpg', '', NULL, 0.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2034, 1175, 'Informatique', '', 'A33', 'Le client a bloqué son téléphone, il veut réinitialiser', '2025-05-20 06:23:35', '2025-05-22 06:21:25', NULL, 'restitue', 11, 4, NULL, NULL, '', NULL, 'assets/images/reparations/repair_682c3c07139fe.jpg', '', NULL, 49.99, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2035, 1198, 'Informatique', '', 'iPhone 11', 'Diag : le téléphone est tombé dans l’eau il y’a 5 mois', '2025-05-20 07:54:53', '2025-06-07 08:39:38', NULL, 'restitue', 10, 1, NULL, NULL, '', NULL, 'assets/images/reparations/repair_682c516d86aa4.jpg', '080208', NULL, 0.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2036, 1199, 'Informatique', '', 'iPhone 11', 'Diag : ne s’allume plus', '2025-05-20 07:57:23', '2025-05-30 08:13:41', NULL, 'annule', 13, 3, NULL, NULL, '', NULL, 'assets/images/reparations/repair_682c520371019.jpg', 'Le client ne connaît pas le mot de passe', NULL, 0.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2037, 1200, 'Informatique', '', 'iPhone 13 Pro Max', 'AUTRE : lentilles, caméra', '2025-05-20 07:57:59', '2025-05-30 07:43:38', NULL, 'restitue', 11, 1, NULL, NULL, '', NULL, 'assets/images/reparations/repair_682c522752b46.jpg', '', NULL, 20.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 1, 'NON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2038, 1259, 'Informatique', '', 'iPhone 13', 'AUTRE : étuis camera arrière', '2025-06-13 12:34:42', '2025-06-17 10:28:03', NULL, 'annule', 13, 1, NULL, NULL, '', NULL, 'assets/images/reparations/repair_684c37020f42d.jpg', '109109', NULL, 10.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2039, 1280, 'Informatique', '', 'iPhone  S', 'ECRAN : REMPLACEMENT_ECRAN_COMPLET', '2025-06-13 13:12:12', '2025-06-20 07:32:38', NULL, 'restitue', 10, 1, NULL, NULL, '', NULL, 'assets/images/reparations/repair_684c3fcc8bddf.jpg', '600900', NULL, 50.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2040, 1281, 'Informatique', '', 'samsung tablette sound by aku', 'ECRAN : REMPLACEMENT_ECRAN_COMPLET', '2025-06-13 13:16:30', '2025-07-09 10:22:23', NULL, 'restitue', 9, 1, NULL, NULL, '', NULL, 'assets/images/reparations/repair_684c40cedc1dd.jpg', '0000', NULL, 149.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2041, 1282, 'Informatique', '', 'iPhone 11 noir', 'diag : redémarre en boucle', '2025-06-13 14:15:36', '2025-06-17 06:45:11', NULL, 'restitue', 11, 1, NULL, NULL, '', NULL, 'assets/images/reparations/repair_684c4ea896082.jpg', '', NULL, 0.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2042, 1283, 'Trottinette', '', 'ocean drive', 'Cycle : Frein', '2025-06-14 06:19:50', '2025-06-20 07:32:38', NULL, 'restitue', 9, 1, NULL, NULL, '', NULL, 'assets/images/reparations/repair_684d30a643724.jpg', '', NULL, 20.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2043, 1284, 'Informatique', '', 'iPhone 11', 'Dommagen liquide', '2025-06-14 06:47:51', '2025-06-20 07:32:38', NULL, 'restitue', 9, 3, NULL, NULL, '', NULL, 'assets/images/reparations/repair_684d3737d2ec7.jpg', '', NULL, 97.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2044, 1023, 'Trottinette', '', 'Wespeed', 'Cycle : Pneu avant', '2025-06-14 10:11:54', '2025-06-20 07:32:38', NULL, 'restitue', 9, 1, NULL, NULL, '', NULL, 'assets/images/reparations/repair_684d670a003d1.jpg', '', NULL, 49.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2045, 1286, 'Trottinette', '', 'MoovWay', 'Cycle : Chambre', '2025-06-14 11:50:58', '2025-10-09 08:41:23', NULL, 'restitue', 11, 1, NULL, NULL, '', NULL, 'assets/images/reparations/repair_684d7e42d10f2.jpg', '', NULL, 150.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2046, 1289, 'Trottinette', '', 'XIOMI M365', 'Cycle : LE CLIENT VEUT UN PNEU PLEIN', '2025-06-17 06:24:42', '2025-06-20 07:32:38', NULL, 'restitue', 5, 4, NULL, NULL, '', NULL, 'assets/images/reparations/repair_6851264a3be2d.jpg', '', NULL, 49.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2047, 1290, 'Trottinette', '', 'GOYOR', 'Electronique :FRAMAI', '2025-06-17 07:28:24', '2025-06-17 13:46:32', NULL, 'restitue', 11, 4, NULL, NULL, '', NULL, 'assets/images/reparations/repair_6851353862b7d.jpg', '', NULL, 0.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2048, 1291, 'Trottinette', '', 'XOMI', 'Electronique :TEGANESTIQUE', '2025-06-17 11:11:51', '2025-07-09 10:22:23', NULL, 'restitue', 9, 1, NULL, NULL, '', NULL, 'assets/images/reparations/repair_68516997a6b2d.jpg', '', NULL, 49.99, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2049, 1292, 'Trottinette', '', 'DUALTRON MINI', 'DIAG : SALLUME PLUS', '2025-06-17 12:48:27', '2025-06-19 14:42:58', NULL, 'restitue', 11, 1, NULL, NULL, '', NULL, 'assets/images/reparations/repair_6851803bd01a4.jpg', '', NULL, 159.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2050, 1293, 'Trottinette', '', 'dualtron', 'Electronique :cramai', '2025-06-17 13:35:06', '2025-07-09 10:22:23', NULL, 'restitue', 9, 1, NULL, NULL, '', NULL, 'assets/images/reparations/repair_68518b2a53a7a.jpg', '', NULL, 0.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2051, 1294, 'Trottinette', '', 'Xiaomi', 'Electronique :pneu agraire', '2025-06-17 13:52:07', '2025-06-18 13:57:20', NULL, 'archive', 14, 1, NULL, NULL, '', NULL, 'assets/images/reparations/repair_68518f27d9929.jpg', '', NULL, 0.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2052, 1295, 'Trottinette', '', 'Dualton', 'pune place', '2025-06-17 14:34:32', '2025-07-09 10:22:23', NULL, 'restitue', 5, 4, NULL, NULL, '', NULL, 'assets/images/reparations/repair_68519918cff1c.jpg', '', NULL, 80.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2053, 1296, 'Informatique', '', 'Redmi 2312draa8g', 'ECRAN : REMPLACEMENT_DE_LA_VITRE OU REMPLACEMENT_ECRAN_COMPLET', '2025-06-18 07:51:17', '2025-07-09 10:22:23', NULL, 'restitue', 9, 1, NULL, NULL, '', NULL, 'assets/images/reparations/repair_68528c15126de.jpg', '', NULL, 0.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 1, 'NON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2054, 1297, 'Trottinette', '', 'Xiaomi M365', 'Electronique : Erreur 14', '2025-06-18 08:46:06', '2025-07-09 10:22:23', NULL, 'restitue', 9, 1, NULL, NULL, '', NULL, 'assets/images/reparations/repair_685298ee129fc.jpg', '', NULL, 29.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2055, 1298, 'Informatique', '', 'mini pc acer', 'diag : problème boot sur disque affiche le bios', '2025-06-18 10:13:11', '2025-06-20 11:42:08', NULL, 'archive', 14, 3, NULL, NULL, '', NULL, 'assets/images/reparations/repair_6852ad5761d0e.jpg', '', NULL, 128.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2056, 1299, 'Informatique', '', 'xiaomi 5g 2109119DG', 'diag : bouton éteint enfonce', '2025-06-18 10:16:50', '2025-06-21 09:36:14', NULL, 'restitue', 11, 1, NULL, NULL, '', NULL, 'assets/images/reparations/repair_6852ae321c714.jpg', '', NULL, 0.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2057, 1300, 'Trottinette', '', 'Kukirin G2 MAster', 'Pneu avant arriere', '2025-06-18 11:05:47', '2025-07-09 10:22:23', NULL, 'restitue', 9, 4, NULL, NULL, '', NULL, 'assets/images/reparations/repair_6852b9ab70e03.jpg', '', NULL, 120.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2058, 1301, 'Trottinette', '', 'mobygum', 'diag : diagnotique batterie reste allumer mais naccelere pas', '2025-06-18 12:07:40', '2025-07-09 12:09:18', NULL, 'gardiennage', 12, 1, NULL, NULL, '', NULL, 'assets/images/reparations/repair_6852c82c3a49b.jpg', '', NULL, 50.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2059, 1303, 'Trottinette', '', 'scooter', 'Cycle :  chambre a air arriere', '2025-06-18 14:04:28', '2025-07-09 10:22:23', NULL, 'restitue', 9, 1, NULL, NULL, '', NULL, 'assets/images/reparations/repair_6852e38c479b0.jpg', '', NULL, 49.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2060, 1272, 'Trottinette', '', 'Xiaomi M365', 'Pb de charge', '2025-06-18 14:34:03', '2025-07-09 10:22:23', NULL, 'restitue', 9, 3, NULL, NULL, '=== DEVIS DÉTAILLÉ - 21/06/2025 09:46 ===\\n\\n🔧 PANNES IDENTIFIÉES :\\nBMS en securite\\n\\n💡 SOLUTIONS PROPOSÉES :\\n• Remise en etat bms : 25,00€\\n==========================================\\n', NULL, 'assets/images/reparations/repair_6852ea7b0abcd.jpg', '', NULL, 25.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2061, 1304, 'Trottinette', '', 'Ecross pro 2', 'Cycle : Pneu arrière / accelerateur', '2025-06-19 06:06:02', '2025-06-20 08:53:44', NULL, 'restitue', 11, 1, NULL, NULL, '', NULL, 'assets/images/reparations/repair_6853c4ea97c11.jpg', '', NULL, 0.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2062, 1306, 'Trottinette', '', 'e craoss pro2', 'Electronique pneu aire', '2025-06-19 10:57:07', '2025-06-19 14:43:23', NULL, 'archive', 14, 1, NULL, NULL, '', NULL, 'assets/images/reparations/repair_685409239e324.jpg', '', NULL, 0.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2063, 1307, 'Trottinette', '', 'cross pro 2', 'l axe arriere', '2025-06-19 13:44:43', '2025-07-09 10:22:23', NULL, 'restitue', 9, 1, NULL, NULL, '', NULL, 'assets/images/reparations/repair_6854306bad1f8.jpg', '', NULL, 0.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2064, 1273, 'Informatique', '', 'iPad F9FYGLG4JF8J', 'AUTRE : remettre a zéro réinitialiser', '2025-06-19 14:34:12', '2025-06-20 10:41:34', NULL, 'archive', 14, 1, NULL, NULL, '', NULL, 'assets/images/reparations/repair_68543c04961ef.jpg', '', NULL, 0.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2065, 1309, 'Trottinette', '', 'g 30', 'Electronique : chambre arrière Techno', '2025-06-20 06:12:15', '2025-06-21 10:10:00', NULL, 'restitue', 11, 1, NULL, NULL, '', NULL, 'assets/images/reparations/repair_685517df7fb57.jpg', '', NULL, 0.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2066, 1311, 'Trottinette', '', 'xiaomi 2pro', 'jante arriere', '2025-06-20 11:19:28', '2025-07-09 10:22:23', NULL, 'restitue', 9, 3, NULL, NULL, '=== DEVIS DÉTAILLÉ - 21/06/2025 11:35 ===\\n\\n🔧 PANNES IDENTIFIÉES :\\nSupport Axe roue arrière endommage\\r\\nDisque de frein tordu\\n\\n💡 SOLUTIONS PROPOSÉES :\\n• Remplacement Support : 15,00€\\n• Remplacement Disque : 15,00€\\n==========================================\\n', NULL, 'assets/images/reparations/repair_68555fe0b6ef7.jpg', '', NULL, 30.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2067, 1312, 'Informatique', '', 'Redmi note 13 pro', 'sallume plus', '2025-06-20 14:33:51', '2025-06-21 10:09:14', NULL, 'restitue', 11, 3, NULL, NULL, '=== DEVIS DÉTAILLÉ - 20/06/2025 23:58 ===\\n\\n🔧 PANNES IDENTIFIÉES :\\nPanne du controleur\\n\\n💡 SOLUTIONS PROPOSÉES :\\nRemplacement du controleur - Tarif 89 euro\\n\\n💰 MONTANT DU DEVIS : 80,00€\\n==========================================\\n', NULL, 'assets/images/reparations/repair_68558d6f104c4.jpg', '', NULL, 80.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2068, 1248, 'Trottinette', '', 'XIAOMI M365', 'Cycle : VIS GARDE BOUE', '2025-06-21 07:00:43', '2025-07-09 10:22:23', NULL, 'restitue', 5, 4, NULL, NULL, '', NULL, 'assets/images/reparations/repair_685674bbaf660.jpg', '', NULL, 9.99, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2069, 1313, 'Trottinette', '', 'XIAOMI PRO 4', 'Cycle : ROUE AVANT', '2025-06-21 07:03:43', '2025-07-09 10:22:23', NULL, 'restitue', 9, 1, NULL, NULL, '', NULL, 'assets/images/reparations/repair_6856756f6c34a.jpg', '', NULL, 50.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2070, 1314, 'Informatique', '', 'PC FIXE', 'AUTRE : Password Reset', '2025-06-21 08:04:40', '2025-06-21 13:26:45', NULL, 'restitue', 11, 3, NULL, NULL, '=== DEVIS DÉTAILLÉ - 21/06/2025 15:26 ===\\n\\n🔧 PANNES IDENTIFIÉES :\\nasd\\n\\n💡 SOLUTIONS PROPOSÉES :\\n• asd : 2,00€\\n==========================================\\n', NULL, 'assets/images/reparations/repair_685683b8cbd7e.jpg', '', NULL, 50.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2071, 1315, 'Trottinette', '', 'Ducati Pro Evo 1', 'Garde boue avant et arriere', '2025-06-21 10:37:28', '2025-06-21 10:59:57', NULL, 'restitue', NULL, 5, NULL, NULL, 'Solution proposee\\r\\nGarde boue avant avec montage : 19.99\\r\\nGarde boue arriere avec montage 39.99', NULL, 'assets/images/reparations/repair_6856a788af226.jpg', '', NULL, 59.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2072, 1316, 'Informatique', '', 'ipad a1458', 'ne recharge pas', '2025-06-21 14:02:46', '2025-07-09 10:22:23', NULL, 'restitue', 10, 1, NULL, NULL, '', NULL, 'assets/images/reparations/repair_6856d7a6f0d65.jpg', '', NULL, 0.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2073, 1317, 'Trottinette', '', 'whispeed', 'pneu arriere', '2025-06-24 06:56:43', '2025-07-09 10:22:23', NULL, 'restitue', 9, 1, NULL, NULL, '', NULL, 'assets/images/reparations/repair_685a684b7b846.jpg', '', NULL, 59.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2074, 1318, 'Trottinette', '', 'nibot', 'Electronique :chenge le painu et fren', '2025-06-24 08:45:05', '2025-07-09 10:22:23', NULL, 'restitue', 9, 1, NULL, NULL, '', NULL, 'assets/images/reparations/repair_685a81b1aae3f.jpg', '', NULL, 59.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2075, 1320, 'Trottinette', '', 'cotrax', 'chambre a air', '2025-06-24 13:00:11', '2025-06-25 06:46:38', NULL, 'annule', 13, 1, NULL, NULL, '', NULL, 'assets/images/reparations/repair_685abd7b13910.jpg', '', NULL, 0.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2076, 1321, 'Informatique', '', 'samsung', 'batterie', '2025-06-25 06:16:17', '2025-07-01 07:01:23', NULL, 'annule', 13, 1, NULL, NULL, '', NULL, 'assets/images/reparations/repair_685bb051b628b.jpg', '', NULL, 40.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2077, 1322, 'Trottinette', '', 'Draisienne avec selle', 'Alimentation : Batterie ne se charge pas', '2025-06-25 07:15:38', '2025-07-09 10:22:23', NULL, 'restitue', 9, 3, NULL, NULL, '=== DEVIS DÉTAILLÉ - 25/06/2025 14:40 ===\\n\\n🔧 PANNES IDENTIFIÉES :\\nne charge plus\\n\\n💡 SOLUTIONS PROPOSÉES :\\n• Réparation chargeur / connecteur de charge : 60,00€\\n==========================================\\n', NULL, 'assets/images/reparations/repair_685bbe3a7c11f.jpg', '', NULL, 60.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2078, 1137, 'Trottinette', '', 'chargeur trottinette', 'chargeur trottinette casse', '2025-06-25 08:02:14', '2025-07-09 10:22:23', NULL, 'restitue', 9, 1, NULL, NULL, '', NULL, 'assets/images/reparations/repair_685bc926bd5e6.jpg', '', NULL, 10.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2079, 1323, 'Informatique', '', 'Redmi note 13', 'telephone bloque', '2025-06-25 11:20:37', '2025-07-09 10:22:23', NULL, 'restitue', 9, 1, NULL, NULL, '', NULL, 'assets/images/reparations/repair_685bf7a518ce1.jpg', '', NULL, 40.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2080, 1324, 'Trottinette', '', 'urban glide', 'pneu arriere', '2025-06-25 11:28:51', '2025-06-25 12:50:09', NULL, 'restitue', 11, 1, NULL, NULL, '', NULL, 'assets/images/reparations/repair_685bf99300421.jpg', '', NULL, 0.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2081, 1118, 'Trottinette', '', 'dualtron', 'pneu arriere', '2025-06-25 12:09:13', '2025-07-09 10:22:23', NULL, 'restitue', 9, 1, NULL, NULL, '', NULL, 'assets/images/reparations/repair_685c03096a6aa.jpg', '', NULL, 30.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2082, 1325, 'Informatique', '', 'pc hp', 'batterie', '2025-06-25 12:11:34', '2025-06-28 10:31:17', NULL, 'restitue', 11, 1, NULL, NULL, '', NULL, 'assets/images/reparations/repair_685c039620891.jpg', '', NULL, 0.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2083, 1326, 'Informatique', '', 'samsung g4', 'batterie', '2025-06-25 13:26:20', '2025-07-09 10:22:54', NULL, 'restitue', 11, 1, NULL, NULL, '', NULL, 'assets/images/reparations/repair_685c151c6c38a.jpg', '', NULL, 0.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2084, 1327, 'Trottinette', '', 'whispeed', 'pneu arriere', '2025-06-25 14:32:42', '2025-07-09 10:22:23', NULL, 'restitue', 9, 1, NULL, NULL, '', NULL, 'assets/images/reparations/repair_685c24aa2b170.jpg', '', NULL, 30.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2085, 1309, 'Trottinette', '', 'kugo', 'chambre a air', '2025-06-26 12:39:01', '2025-06-28 10:31:11', NULL, 'annule', 13, 1, NULL, NULL, '', NULL, 'assets/images/reparations/repair_685d5b852c2f3.jpg', '', NULL, 0.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2086, 1307, 'Trottinette', '', 'urban glide pro2', 'pneu', '2025-06-27 10:26:32', '2025-06-28 06:28:50', NULL, 'restitue', 11, 1, NULL, NULL, '', NULL, 'assets/images/reparations/repair_685e8df8bd64d.jpg', '', NULL, 0.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2087, 1329, 'Informatique', '', 'iphone 11', 'pas de son', '2025-06-28 07:03:13', '2025-07-09 10:22:23', NULL, 'restitue', 9, 1, NULL, NULL, '', NULL, 'assets/images/reparations/repair_685fafd1a2c12.jpg', '068597', NULL, 0.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2088, 1330, 'Trottinette', '', 'btwin', 'erreur e1', '2025-06-28 07:08:37', '2025-07-09 10:22:23', NULL, 'restitue', 5, 4, NULL, NULL, '', NULL, 'assets/images/reparations/repair_685fb1155fc42.jpg', '', NULL, 0.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2089, 1331, 'Informatique', '', 'hawaïenne', 'battrai', '2025-06-28 07:39:18', '2025-07-09 10:22:23', NULL, 'restitue', 9, 1, NULL, NULL, '', NULL, 'assets/images/reparations/repair_685fb84699b49.jpg', '', NULL, 0.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2090, 1332, 'Informatique', '', 'iPhone 8', 'ECRAN : REMPLACEMENT_DE_LA_VITRE OU REMPLACEMENT_ECRAN_COMPLET', '2025-06-28 07:52:51', '2025-07-09 10:22:23', NULL, 'restitue', 5, 4, NULL, NULL, '', NULL, 'assets/images/reparations/repair_685fbb73e053c.jpg', '', NULL, 80.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2091, 1333, 'Trottinette', '', 'joyor', 'roue arriere', '2025-06-28 11:31:51', '2025-07-25 13:09:18', NULL, 'gardiennage', 9, 4, NULL, NULL, '', NULL, 'assets/images/reparations/repair_685feec703031.jpg', '', NULL, 200.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', 3, '2025-07-25', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2092, 1155, 'Trottinette', '', 'Thunder 3', 'système de pliage à remplacer / Disque avant à remplacer / diag problème de led / béquille Casse', '2025-07-01 07:41:30', '2025-08-07 07:35:16', NULL, 'restitue', 9, 3, NULL, NULL, '=== DEVIS DÉTAILLÉ - 07/07/2025 13:33 ===\\n\\n🔧 PANNES IDENTIFIÉES :\\n- Pas de LED fonctionnel\\r\\n- System de Pliage endommage\\r\\n- Bequille Endommage\\r\\n- Disque avant a remplacer\\n\\n💡 SOLUTIONS PROPOSÉES :\\n• Piece : BÉQUILLE : 52,80€\\n• Installation OFFERTE : 0,00€\\n• PIECE : CONTROLEUR DE LED : 43,20€\\n• Installation : 39,90€\\n• PIECE : SYSTÈME DE PLIAGE : 124,90€\\n• PIECE : BAS DE POTENCE : 52,80€\\n• Installation system de pliage : 80,00€\\n==========================================\\n', NULL, 'assets/images/reparations/repair_6863ad4a19388.jpg', '', NULL, 393.60, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2093, 1314, 'Trottinette', '', 'draisienne', 'accelerateur', '2025-07-01 10:36:28', '2025-09-11 06:22:22', NULL, 'restitue', 5, 4, NULL, NULL, '', NULL, 'assets/images/reparations/repair_6863d64c6f46f.jpg', '', NULL, 40.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2094, 1314, 'Informatique', '', 'iPad 4', 'ecran', '2025-07-01 10:37:45', '2025-07-25 13:08:51', NULL, 'restitue', 9, 1, NULL, NULL, '', NULL, 'assets/images/reparations/repair_6863d6993709b.jpg', '', NULL, 49.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2095, 1336, 'Trottinette', '', 'e cross pro2', 'pneu', '2025-07-01 10:53:25', '2025-07-01 15:55:57', NULL, 'restitue', 11, 1, NULL, NULL, '', NULL, 'assets/images/reparations/repair_6863da45100b8.jpg', '', NULL, 0.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2096, 1337, 'Informatique', '', 'Redmi', 'ecran et faire', '2025-07-01 11:09:39', '2025-07-11 08:54:15', NULL, 'restitue', 11, 1, NULL, NULL, '', NULL, 'assets/images/reparations/repair_6863de136cb81.jpg', '', NULL, 0.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2097, 1338, 'Trottinette', '', 'Xiaomi 4', 'Pneu arriere', '2025-07-01 12:02:11', '2025-07-09 10:22:23', NULL, 'restitue', 9, 1, NULL, NULL, '', NULL, 'assets/images/reparations/repair_6863ea6354a2d.jpg', '', NULL, 59.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2098, 1266, 'Trottinette', '', 'joyor', 'chambre a air plaquettes de frein avant et arriere', '2025-07-01 12:13:55', '2025-07-01 15:50:40', NULL, 'restitue', 11, 1, NULL, NULL, '', NULL, 'assets/images/reparations/repair_6863ed231d085.jpg', '', NULL, 65.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2099, 1339, 'Trottinette', '', 'wispeed', 'batterie', '2025-07-01 12:39:21', '2025-07-30 06:48:45', NULL, 'restitue', 9, 5, NULL, NULL, '', NULL, 'assets/images/reparations/repair_6863f319e610b.jpg', '', NULL, 139.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 1, 'NON', 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2110, 1350, 'Informatique', '', 'iphone11', 'ecran', '2025-07-03 10:32:40', '2025-07-09 10:22:23', NULL, 'restitue', 9, 1, NULL, NULL, '', NULL, 'assets/images/reparations/repair_68667868482fd.jpg', '', NULL, 0.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2111, 1352, 'Trottinette', '', 'honey whale', 'pneu arriere', '2025-07-03 13:34:49', '2025-07-09 10:22:23', NULL, 'restitue', 9, 1, NULL, NULL, '', NULL, 'assets/images/reparations/repair_6866a319bd3de.jpg', '', NULL, 0.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2112, 1353, 'Trottinette', '', 'Ninebot', 'volant frein et garde boue', '2025-07-03 14:37:37', '2025-07-09 10:22:23', NULL, 'restitue', 9, 1, NULL, NULL, '', NULL, 'assets/images/reparations/repair_6866b1d1e5d3b.jpg', '', NULL, 39.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2113, 1354, 'Informatique', '', 'samsung a53', 'sallume plus', '2025-07-03 14:43:58', '2025-07-12 14:52:31', NULL, 'restitue', 9, 1, NULL, NULL, '', NULL, 'assets/images/reparations/repair_6866b34e7f8ab.jpg', '', NULL, 0.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2114, 1355, 'Trottinette', '', 'Kaboul Mantis', 'Electronique : installation eye4 - pneu avant - vérifier blocage, guidon', '2025-07-04 06:06:49', '2025-10-30 11:54:20', NULL, 'restitue', 11, 3, NULL, NULL, '=== DEVIS DÉTAILLÉ - 15/07/2025 12:45 ===\\n\\n🔧 PANNES IDENTIFIÉES :\\nAmortisseur avant a remplacer\\n\\n💡 SOLUTIONS PROPOSÉES :\\n• Amortisseur avant : 79,99€\\n• Installation : 60,00€\\n==========================================\\n', NULL, 'assets/images/reparations/repair_68678b992b0b3.jpg', '', NULL, 139.99, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2115, 1356, 'Informatique', '', 'p30 lite', 'Recuperation de donnee Ecran HS / Le Client a fournis 3 Cle usb rouge de 8Gb', '2025-07-04 06:13:05', '2025-07-25 13:08:51', NULL, 'restitue', 13, 1, NULL, NULL, 'Verifier si on a un ecran en stock dans les tel a refurb', NULL, 'assets/images/reparations/repair_68678d118de38.jpg', '', NULL, 0.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 1, 'NON', 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2116, 1357, 'Trottinette', '', 'Pneu Togo', 'Electronique : Pneu', '2025-07-04 06:15:47', '2025-07-09 10:22:23', NULL, 'restitue', 9, 1, NULL, NULL, '', NULL, 'assets/images/reparations/repair_68678db3f3e05.jpg', '', NULL, 39.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2117, 1358, 'Trottinette', '', 'Kukirin g2', 'Pneu avant', '2025-07-04 06:19:09', '2025-07-09 10:22:23', NULL, 'restitue', 5, 4, NULL, NULL, '', NULL, 'assets/images/reparations/repair_68678e7d730cd.jpg', '', NULL, 49.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2118, 1359, 'Trottinette', '', 'draisienne', 'plaquettes', '2025-07-04 11:17:31', '2025-07-09 10:22:23', NULL, 'restitue', 9, 1, NULL, NULL, '', NULL, 'assets/images/reparations/repair_6867d46bc6b73.jpg', '', NULL, 0.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `reparations` (`id`, `client_id`, `type_appareil`, `marque`, `modele`, `description_probleme`, `date_reception`, `date_modification`, `date_fin_prevue`, `statut`, `statut_id`, `statut_categorie`, `signature`, `prix`, `notes_techniques`, `notes_finales`, `photo_appareil`, `mot_de_passe`, `etat_esthetique`, `prix_reparation`, `devis_envoye`, `devis_accepte`, `date_envoi_devis`, `date_reponse_devis`, `photos`, `urgent`, `commande_requise`, `archive`, `employe_id`, `date_gardiennage`, `gardiennage_facture`, `parrain_id`, `reduction_parrainage`, `reduction_parrainage_pourcentage`, `signature_client`, `photo_signature`, `photo_client`, `accept_conditions`, `proprietaire`, `signature_devis`, `date_signature_devis`, `garantie_id`, `date_garantie_debut`, `date_garantie_fin`) VALUES
(2119, 1360, 'Informatique', '', 'iPhone xr', 'restaurer le telephone', '2025-07-04 11:25:03', '2025-07-09 10:22:23', NULL, 'restitue', 9, 1, NULL, NULL, '', NULL, 'assets/images/reparations/repair_6867d62fc370e.jpg', '', NULL, 0.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2120, 1361, 'Trottinette', '', 'dualtron', 'pneu avant', '2025-07-04 11:30:36', '2025-07-15 14:35:11', NULL, 'restitue', 11, 1, NULL, NULL, '', NULL, 'assets/images/reparations/repair_6867d77c31a82.jpg', '', NULL, 0.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2121, 1362, 'Trottinette', '', 'obarter X1', 'Electronique : ne sallume pas', '2025-07-04 11:45:55', '2025-07-05 07:36:57', NULL, 'annule', 13, 1, NULL, NULL, '', NULL, 'assets/images/reparations/repair_6867db13f069c.jpg', '', NULL, 0.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2122, 1363, 'Trottinette', '', 'mobygum', 'ecran et contrôleur', '2025-07-04 11:51:46', '2025-07-11 08:47:19', NULL, 'gardiennage', 12, 3, NULL, NULL, '=== DEVIS DÉTAILLÉ - 09/07/2025 14:40 ===\\n\\n🔧 PANNES IDENTIFIÉES :\\nKIT CONTROLLEUR/LCD HS\\n\\n💡 SOLUTIONS PROPOSÉES :\\n• Remplacement KIT CONTROLLEUR/LCD : 229,00€\\n==========================================\\n', NULL, 'assets/images/reparations/repair_6867dc724661f.jpg', '', NULL, 229.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2123, 1364, 'Trottinette', '', 'Ninebot es25e', 'Electronique : Phare arriere', '2025-07-04 12:17:33', '2025-07-15 17:46:04', NULL, 'gardiennage', 12, 1, NULL, NULL, '', NULL, 'assets/images/reparations/repair_6867e27d7c0fe.jpg', '', NULL, 30.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 1, 'NON', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2124, 1365, 'Trottinette', '', 'xiaomi', 'pneu avant', '2025-07-04 12:32:32', '2025-07-09 11:00:04', NULL, 'restitue', 11, 1, NULL, NULL, '', NULL, 'assets/images/reparations/repair_6867e6006bc1d.jpg', '', NULL, 0.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2125, 1366, 'Trottinette', '', 'xiaomi', 'volant', '2025-07-05 07:22:22', '2025-07-09 12:14:43', NULL, 'termine', 15, 1, NULL, NULL, '', NULL, 'assets/images/reparations/repair_6868eece7c500.jpg', '', NULL, 0.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2126, 1367, 'Trottinette', '', 'xiaomi', 'pneu arriere et avant et garde boue', '2025-07-05 07:23:57', '2025-07-09 12:16:23', NULL, 'restitue', 11, 1, NULL, NULL, '', NULL, 'assets/images/reparations/repair_6868ef2d429ae.jpg', '', NULL, 0.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2127, 1368, 'Informatique', '', 'iptv', 'le code marche pas', '2025-07-05 14:49:46', '2025-07-09 12:21:56', NULL, 'restitue', 11, 4, NULL, NULL, '', NULL, 'assets/images/reparations/repair_686957aaadc01.jpg', '', NULL, 0.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2128, 1371, 'Informatique', '', 'Transfer donné', 'Transfer donnė', '2025-07-08 10:07:42', '2025-07-11 07:50:30', NULL, 'restitue', 9, 1, NULL, NULL, 'Code de la SIM: 4542', NULL, 'assets/images/reparations/repair_686d0a0e9231a.jpg', '700000', NULL, 19.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2129, 1372, 'Trottinette', '', 'Ninebot M56K5227U10100', 'Pneu crevé', '2025-07-08 10:17:59', '2025-07-12 14:32:35', NULL, 'restitue', 9, 1, NULL, NULL, '', NULL, 'assets/images/reparations/repair_686d0c77b76b6.jpg', '', NULL, 0.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2130, 1373, 'Informatique', '', 'Samsung a12f', 'ECRAN : REMPLACEMENT_DE_LA_VITRE OU REMPLACEMENT_ECRAN_COMPLET', '2025-07-08 11:18:23', '2025-07-11 08:47:33', NULL, 'restitue', 11, 3, NULL, NULL, '', NULL, 'assets/images/reparations/repair_686d1a9f84c5e.jpg', '15946', NULL, 69.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2131, 1374, 'Trottinette', '', 'Spider 2', 'Plaquette arrière', '2025-07-08 11:54:19', '2025-07-11 07:50:30', NULL, 'restitue', 9, 1, NULL, NULL, '', NULL, 'assets/images/reparations/repair_686d230b8465d.jpg', '', NULL, 0.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2132, 1396, 'Informatique', '', 'iPhone 7 plus', 'Factory reset', '2025-07-12 07:55:22', '2025-07-12 14:32:35', NULL, 'restitue', 9, 1, NULL, NULL, '', NULL, 'assets/images/reparations/repair_6872310abf024.jpg', '', NULL, 10.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', 3, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2133, 1397, 'Trottinette', '', 'Pure', 'Remplacement câble de frein /da / das', '2025-07-12 08:33:58', '2025-11-09 18:38:49', NULL, 'nouveau_diagnostique', 9, 1, NULL, NULL, '', NULL, 'assets/images/reparations/repair_68723a16e46ec.jpg', '', NULL, 20.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2134, 1398, 'Trottinette', '', 'dualtron mini', 'cable moteur', '2025-07-12 09:57:57', '2025-11-06 01:55:53', NULL, 'reparation_effectue', 9, 1, NULL, NULL, '', NULL, 'assets/images/reparations/repair_68724dc525c03.jpg', '', NULL, 79.99, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', 6, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2135, 1399, 'Trottinette', '', 'Batterie YADEA', 'Diag batterie', '2025-07-12 10:28:56', '2025-11-06 21:45:46', NULL, 'reparation_effectue', 9, 1, NULL, NULL, '', NULL, 'assets/images/reparations/repair_6872550871fb3.jpg', '', NULL, 0.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2136, 1400, 'Trottinette', '', 'Mini 4 Pro', 'Remplacement LCD', '2025-07-12 11:05:19', '2025-11-06 01:54:41', NULL, 'reparation_effectue', 9, 4, NULL, NULL, '', NULL, 'assets/images/reparations/repair_68725d8f76294.jpg', '', NULL, 14.99, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', 6, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2137, 1354, 'Informatique', '', 'Honor', 'Ecran', '2025-07-12 13:00:01', '2025-11-09 01:34:35', NULL, 'reparation_effectue', 11, 1, NULL, NULL, '', NULL, 'assets/images/reparations/repair_687278715cd95.jpg', '', NULL, 0.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 1, 'NON', 3, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2138, 1401, 'Informatique', '', 'ordi hp 14', 'remplacement batterie', '2025-07-12 13:03:57', '2025-07-17 14:30:56', NULL, 'archive', 14, 1, NULL, NULL, '', NULL, 'assets/images/reparations/repair_6872795d2a8e2.jpg', 'Adam06', NULL, 0.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2139, 1130, 'Informatique', '', 'iPhone 12', 'Alimentation', '2025-07-15 06:08:19', '2025-11-03 22:38:23', NULL, 'reparation_effectue', NULL, 1, NULL, NULL, 'Batterie', NULL, 'assets/images/reparations/repair_68760c73e4a55.jpg', '220862', NULL, 0.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', 6, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2140, 1279, 'Trottinette', '', 'asd', 'Electronique : MERCI_D_INDIQUER_DE_FACON_CLAIRE_ET_PRECISE_LE_PROBLEME_DE_L_APPAREIL', '2025-11-06 21:47:00', '2025-11-09 01:57:18', NULL, 'reparation_effectue', 6, NULL, NULL, NULL, '', NULL, 'assets/images/reparations/repair_690d256404f07.jpg', '', NULL, 555.00, 'OUI', 'en_attente', '2025-11-08 23:16:12', NULL, NULL, 0, 1, 'NON', 6, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2141, 1029, 'Trottinette', '', 'asddas', 'note probleme', '2025-11-09 17:40:50', '2025-11-09 19:37:24', NULL, 'nouvelle_intervention', NULL, NULL, NULL, NULL, NULL, 'Test note interne corrigée\n\nasd', 'assets/images/reparations/repair_6910e032a284f.jpg', '', NULL, 5.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2142, 1029, 'Trottinette', '', 'asd', 'Electronique : MERCI_D_INDIQUER_DE_FACON_CLAIRE_ET_PRECISE_LE_PROBLEME_DE_L_APPAREIL', '2025-11-09 18:35:27', '2025-11-09 19:35:27', NULL, 'nouvelle_intervention', NULL, NULL, NULL, NULL, '', NULL, 'assets/images/reparations/repair_6910ecff198c1.jpg', '', NULL, 222.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 1, 'NON', 13, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL);

--
-- Déclencheurs `reparations`
--
DELIMITER $$
CREATE TRIGGER `trigger_creation_garantie` AFTER UPDATE ON `reparations` FOR EACH ROW BEGIN
    DECLARE garantie_active INT DEFAULT 0$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `reparations_backup_20251103_230309`
--

CREATE TABLE `reparations_backup_20251103_230309` (
  `id` int NOT NULL DEFAULT '0',
  `client_id` int NOT NULL,
  `type_appareil` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `marque` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `modele` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description_probleme` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_reception` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modification` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `date_fin_prevue` date DEFAULT NULL,
  `statut` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'nouvelle_intervention',
  `statut_id` int DEFAULT NULL,
  `statut_categorie` int DEFAULT NULL,
  `signature` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `prix` decimal(10,2) DEFAULT NULL,
  `notes_techniques` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `notes_finales` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `photo_appareil` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mot_de_passe` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `etat_esthetique` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `prix_reparation` decimal(10,2) DEFAULT '0.00',
  `devis_envoye` enum('OUI','NON') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'NON',
  `devis_accepte` enum('en_attente','oui','non') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'en_attente',
  `date_envoi_devis` timestamp NULL DEFAULT NULL,
  `date_reponse_devis` timestamp NULL DEFAULT NULL,
  `photos` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `urgent` tinyint(1) DEFAULT '0',
  `commande_requise` tinyint(1) DEFAULT '0',
  `archive` enum('OUI','NON') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'NON',
  `employe_id` int DEFAULT NULL,
  `date_gardiennage` date DEFAULT NULL COMMENT 'Date de début du gardiennage',
  `gardiennage_facture` decimal(10,2) DEFAULT NULL COMMENT 'Montant facturé pour le gardiennage',
  `parrain_id` int DEFAULT NULL COMMENT 'ID du client parrain si le client est un filleul',
  `reduction_parrainage` decimal(10,2) DEFAULT NULL COMMENT 'Montant de la réduction appliquée via parrainage',
  `reduction_parrainage_pourcentage` int DEFAULT NULL COMMENT 'Pourcentage de la réduction parrainage appliquée',
  `signature_client` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `photo_signature` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `photo_client` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `accept_conditions` tinyint(1) DEFAULT '0',
  `proprietaire` tinyint(1) DEFAULT '0',
  `signature_devis` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Signature électronique du client pour acceptation devis (base64)',
  `date_signature_devis` datetime DEFAULT NULL COMMENT 'Date et heure de la signature du devis',
  `garantie_id` int DEFAULT NULL,
  `date_garantie_debut` timestamp NULL DEFAULT NULL,
  `date_garantie_fin` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `reparations_backup_20251103_230309`
--

INSERT INTO `reparations_backup_20251103_230309` (`id`, `client_id`, `type_appareil`, `marque`, `modele`, `description_probleme`, `date_reception`, `date_modification`, `date_fin_prevue`, `statut`, `statut_id`, `statut_categorie`, `signature`, `prix`, `notes_techniques`, `notes_finales`, `photo_appareil`, `mot_de_passe`, `etat_esthetique`, `prix_reparation`, `devis_envoye`, `devis_accepte`, `date_envoi_devis`, `date_reponse_devis`, `photos`, `urgent`, `commande_requise`, `archive`, `employe_id`, `date_gardiennage`, `gardiennage_facture`, `parrain_id`, `reduction_parrainage`, `reduction_parrainage_pourcentage`, `signature_client`, `photo_signature`, `photo_client`, `accept_conditions`, `proprietaire`, `signature_devis`, `date_signature_devis`, `garantie_id`, `date_garantie_debut`, `date_garantie_fin`) VALUES
(1, 1, 'Informatique', '', 'test', 'ECRAN : REMPLACEMENT_DE_LA_VITRE OU REMPLACEMENT_ECRAN_COMPLET', '2025-09-23 17:10:18', '2025-10-13 01:25:27', NULL, 'termine', 3, 1, NULL, NULL, '', NULL, 'assets/images/reparations/repair_68d2d47a02737.jpg', '', NULL, 2.00, 'OUI', 'oui', '2025-09-23 23:44:47', '2025-09-24 00:17:53', NULL, 0, 0, 'NON', 6, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(2, 1, 'Informatique', '', 'IPHONE 8', 'ECRAN : DA', '2025-09-24 00:14:44', '2025-09-25 10:33:36', NULL, 'restitue', 11, 1, NULL, NULL, '', NULL, 'assets/images/reparations/repair_68d337f41238c.jpg', '', NULL, 22.00, 'OUI', 'non', '2025-09-24 23:36:23', '2025-09-24 23:36:36', NULL, 0, 0, 'NON', 6, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(3, 1, 'Informatique', '', 'test', 'ECRAN : REMPLACEMENT_DE_LA_VITRE OU REMPLACEMENT_ECRAN_COMPLET', '2025-09-24 18:20:06', '2025-11-02 23:23:31', NULL, 'reparation_effectue', 1, 1, NULL, NULL, '', NULL, 'assets/images/reparations/repair_68d43656a79ae.jpg', '', NULL, 129.00, 'OUI', 'oui', '2025-09-27 17:19:34', '2025-09-27 17:20:39', NULL, 0, 0, 'NON', 6, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(4, 1, 'Trottinette', '', 'saber', 'dasksadjkj', '2025-09-24 18:21:07', '2025-10-23 19:34:26', NULL, 'reparation_effectue', 9, 1, NULL, NULL, '', NULL, 'assets/images/reparations/repair_68d43693f1056.jpg', '', NULL, 109.00, 'OUI', 'oui', '2025-09-25 10:29:50', '2025-09-25 10:31:08', NULL, 0, 0, 'NON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(5, 1, 'Trottinette', '', 'SABER', 'RAS', '2025-10-01 01:53:07', '2025-10-13 01:24:58', NULL, 'termine', NULL, 1, NULL, NULL, '', NULL, 'assets/images/reparations/repair_68dc898324f7a.jpg', '', NULL, 21.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 1, 'NON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(6, 5, 'Trottinette', '', 'ads', 'Cycle : PRECISEZ_AVEC_OU_SANS_CHAMBRE_ET_PRECISEZ_LE_TYPE_ET_LA_TAILLE_DU_PNEU', '2025-10-12 11:47:13', '2025-10-12 13:47:13', NULL, 'En attente', NULL, NULL, NULL, NULL, '', NULL, NULL, '', NULL, 21.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(7, 5, 'Trottinette', '', 'asd', 'Electronique : MERCI_D_INDIQUER_DE_FACON_CLAIRE_ET_PRECISE_LE_PROBLEME_DE_L_APPAREIL', '2025-10-12 11:48:44', '2025-11-02 23:23:31', NULL, 'en_cours_intervention', NULL, NULL, NULL, NULL, '', NULL, 'assets/images/reparations/repair_68ebb1bc54076.jpg', '', NULL, 12.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', 6, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(8, 1, 'Trottinette', '', 'asd', 'Electronique : MERCI_D_INDIQUER_DE_FACON_CLAIRE_ET_PRECISE_LE_PROBLEME_DE_L_APPAREIL', '2025-10-12 11:49:45', '2025-10-24 18:36:05', NULL, 'En attente', NULL, NULL, NULL, NULL, '', NULL, 'assets/images/reparations/repair_68ebb1f92e832.jpg', '', NULL, 12.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', 12, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(9, 7, 'Trottinette', '', 'asd', 'Electronique : MERCI_D_INDIQUER_DE_FACON_CLAIRE_ET_PRECISE_LE_PROBLEME_DE_L_APPAREIL', '2025-10-12 11:51:24', '2025-10-13 01:24:58', NULL, 'termine', NULL, NULL, NULL, NULL, '', NULL, 'assets/images/reparations/repair_68ebb25c57b9e.jpg', '', NULL, 2.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(10, 1, 'Trottinette', '', 'sav', 'Autre : MERCI_D_INDIQUER_DE_FACON_CLAIRE_ET_PRECISE_LE_PROBLEME_DE_L_APPAREIL', '2025-10-12 11:52:12', '2025-11-02 23:46:18', NULL, 'en_cours_diagnostique', 11, NULL, NULL, NULL, 'sewewewq', NULL, 'assets/images/reparations/repair_68ebb28cd8fa2.jpg', '', NULL, 222.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', 12, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(11, 7, 'Trottinette', '', 'dsa', 'Electronique : MERCI_D_INDIQUER_DE_FACON_CLAIRE_ET_PRECISE_LE_PROBLEME_DE_L_APPAREIL', '2025-10-19 20:26:56', '2025-11-02 23:46:51', NULL, 'nouveau_diagnostique', 1, NULL, NULL, NULL, '', NULL, NULL, '', NULL, 21.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', 12, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(12, 1, 'Informatique', '', 'Macbook Pro', 'ECRAN : Remplacement ecran complet', '2025-10-19 22:55:33', '2025-10-23 20:07:05', NULL, 'reparation_effectue', NULL, NULL, NULL, NULL, '', NULL, NULL, '', NULL, 9.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', 6, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(13, 7, 'Trottinette', '', 'sa', 'Electronique : MERCI_D_INDIQUER_DE_FACON_CLAIRE_ET_PRECISE_LE_PROBLEME_DE_L_APPAREIL', '2025-10-20 17:53:39', '2025-10-20 23:33:15', NULL, 'reparation_effectue', NULL, NULL, NULL, NULL, '', NULL, NULL, '', NULL, 21.00, 'NON', 'en_attente', NULL, NULL, NULL, 0, 0, 'NON', 6, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `reparation_attributions`
--

CREATE TABLE `reparation_attributions` (
  `id` int NOT NULL,
  `reparation_id` int NOT NULL,
  `employe_id` int NOT NULL,
  `date_debut` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `date_fin` timestamp NULL DEFAULT NULL,
  `statut_avant` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `statut_apres` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `est_principal` tinyint(1) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `reparation_attributions`
--

INSERT INTO `reparation_attributions` (`id`, `reparation_id`, `employe_id`, `date_debut`, `date_fin`, `statut_avant`, `statut_apres`, `est_principal`) VALUES
(195, 11, 6, '2025-10-23 23:34:25', NULL, 'nouvelle_intervention', 'nouvelle_intervention', 1),
(196, 8, 12, '2025-10-24 18:36:05', NULL, 'En attente', 'En attente', 1),
(197, 11, 12, '2025-11-02 20:56:57', NULL, 'nouvelle_intervention', 'nouvelle_intervention', 1),
(198, 10, 12, '2025-11-02 20:56:57', NULL, 'nouvelle_intervention', 'nouvelle_intervention', 1);

-- --------------------------------------------------------

--
-- Structure de la table `reparation_logs`
--

CREATE TABLE `reparation_logs` (
  `id` int NOT NULL,
  `reparation_id` int NOT NULL,
  `employe_id` int NOT NULL,
  `action_type` enum('demarrage','terminer','changement_statut','ajout_note','modification','autre') COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_action` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `statut_avant` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `statut_apres` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `details` text COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `reparation_logs`
--

INSERT INTO `reparation_logs` (`id`, `reparation_id`, `employe_id`, `action_type`, `date_action`, `statut_avant`, `statut_apres`, `details`) VALUES
(2, 1, 6, 'demarrage', '2025-09-23 23:48:31', NULL, NULL, 'Réparation assignée à l\'employé'),
(3, 1, 6, 'changement_statut', '2025-09-23 23:48:40', 'en_cours_intervention', 'reparation_effectue', 'Réparation marquée comme reparation_effectue'),
(6, 2, 6, 'demarrage', '2025-09-24 07:58:07', NULL, NULL, 'Réparation assignée à l\'employé'),
(7, 2, 6, 'changement_statut', '2025-09-24 07:58:25', 'en_cours_intervention', 'reparation_effectue', 'Réparation marquée comme reparation_effectue'),
(13, 1, 6, 'demarrage', '2025-09-27 22:42:22', NULL, NULL, 'Réparation assignée à l\'employé'),
(14, 1, 6, 'changement_statut', '2025-09-27 22:42:39', 'en_cours_intervention', 'reparation_effectue', 'Réparation marquée comme reparation_effectue'),
(19, 5, 6, 'autre', '2025-10-01 01:53:07', 'nouvelle_intervention', 'nouvelle_intervention', 'Commande de pièces créée: ADW (Réf: CMD-20251001-68dc89832bc11)'),
(20, 10, 6, 'demarrage', '2025-10-12 19:19:54', NULL, NULL, 'Réparation assignée à l\'employé'),
(21, 10, 6, 'changement_statut', '2025-10-12 19:20:04', 'en_cours_intervention', 'reparation_effectue', 'Réparation marquée comme reparation_effectue'),
(24, 7, 6, 'demarrage', '2025-10-14 23:39:21', NULL, NULL, 'Réparation assignée à l\'employé'),
(25, 7, 6, 'changement_statut', '2025-10-15 00:38:05', 'en_cours_intervention', 'reparation_effectue', 'Réparation marquée comme reparation_effectue'),
(26, 7, 6, 'demarrage', '2025-10-15 00:38:15', NULL, NULL, 'Réparation assignée à l\'employé'),
(27, 7, 6, 'changement_statut', '2025-10-15 00:56:19', 'en_cours_intervention', 'reparation_effectue', 'Réparation marquée comme reparation_effectue'),
(28, 7, 6, 'demarrage', '2025-10-15 00:59:11', NULL, NULL, 'Réparation assignée à l\'employé'),
(29, 7, 6, 'changement_statut', '2025-10-20 01:02:45', 'en_cours_intervention', 'en_attente_responsable', 'Réparation marquée comme en_attente_responsable'),
(30, 13, 6, 'demarrage', '2025-10-20 20:19:58', NULL, NULL, 'Réparation assignée à l\'employé'),
(31, 13, 6, 'changement_statut', '2025-10-20 23:33:15', 'en_cours_intervention', 'reparation_effectue', 'Réparation marquée comme reparation_effectue'),
(34, 12, 6, 'demarrage', '2025-10-23 20:06:52', NULL, NULL, 'Réparation assignée à l\'employé'),
(35, 12, 6, 'changement_statut', '2025-10-23 20:07:05', 'en_cours_intervention', 'reparation_effectue', 'Réparation marquée comme reparation_effectue'),
(36, 3, 6, 'demarrage', '2025-10-24 18:07:10', NULL, NULL, 'Réparation assignée à l\'employé'),
(38, 3, 6, 'changement_statut', '2025-11-02 23:23:31', 'nouveau_diagnostique', 'reparation_effectue', 'Réparation marquée comme reparation_effectue'),
(39, 7, 6, 'demarrage', '2025-11-02 23:23:31', NULL, NULL, 'Réparation assignée à l\'employé'),
(42, 7, 6, 'changement_statut', '2025-11-03 22:07:39', 'en_cours_intervention', 'reparation_effectue', 'Réparation marquée comme reparation_effectue'),
(43, 10, 6, 'demarrage', '2025-11-03 22:07:40', NULL, NULL, 'Réparation assignée à l\'employé'),
(44, 10, 6, 'changement_statut', '2025-11-03 22:08:07', 'en_cours_intervention', 'reparation_effectue', 'Réparation marquée comme reparation_effectue'),
(45, 10, 6, 'demarrage', '2025-11-03 22:08:55', NULL, NULL, 'Réparation assignée à l\'employé'),
(46, 10, 6, 'changement_statut', '2025-11-03 22:14:53', 'en_cours_intervention', 'reparation_effectue', 'Réparation marquée comme reparation_effectue'),
(47, 13, 6, 'demarrage', '2025-11-03 22:14:53', NULL, NULL, 'Réparation assignée à l\'employé'),
(48, 13, 6, 'changement_statut', '2025-11-03 22:15:04', 'en_cours_intervention', 'reparation_effectue', 'Réparation marquée comme reparation_effectue'),
(49, 13, 6, 'demarrage', '2025-11-03 22:15:15', NULL, NULL, 'Réparation assignée à l\'employé'),
(50, 13, 6, 'changement_statut', '2025-11-03 22:15:20', 'en_cours_intervention', 'reparation_effectue', 'Réparation marquée comme reparation_effectue'),
(51, 12, 6, 'demarrage', '2025-11-03 22:15:20', NULL, NULL, 'Réparation assignée à l\'employé'),
(52, 12, 6, 'changement_statut', '2025-11-03 22:15:40', 'en_cours_intervention', 'reparation_effectue', 'Réparation marquée comme reparation_effectue'),
(53, 11, 6, 'demarrage', '2025-11-03 22:15:40', NULL, NULL, 'Réparation assignée à l\'employé'),
(54, 11, 6, 'changement_statut', '2025-11-03 22:15:47', 'en_cours_intervention', 'reparation_effectue', 'Réparation marquée comme reparation_effectue'),
(55, 13, 6, 'demarrage', '2025-11-03 22:15:47', NULL, NULL, 'Réparation assignée à l\'employé'),
(56, 13, 6, 'changement_statut', '2025-11-03 22:16:05', 'en_cours_intervention', 'reparation_effectue', 'Réparation marquée comme reparation_effectue'),
(57, 11, 6, 'demarrage', '2025-11-03 22:16:05', NULL, NULL, 'Réparation assignée à l\'employé'),
(58, 11, 6, 'changement_statut', '2025-11-03 22:16:17', 'en_cours_intervention', 'en_attente_responsable', 'Réparation marquée comme en_attente_responsable'),
(59, 11, 6, 'demarrage', '2025-11-03 22:16:26', NULL, NULL, 'Réparation assignée à l\'employé'),
(60, 11, 6, 'changement_statut', '2025-11-03 22:17:32', 'en_cours_intervention', 'reparation_effectue', 'Réparation marquée comme reparation_effectue'),
(61, 13, 6, 'demarrage', '2025-11-03 22:17:32', NULL, NULL, 'Réparation assignée à l\'employé'),
(62, 13, 6, 'changement_statut', '2025-11-03 22:20:58', 'en_cours_intervention', 'reparation_effectue', 'Réparation marquée comme reparation_effectue'),
(63, 13, 6, 'demarrage', '2025-11-03 22:21:20', NULL, NULL, 'Réparation assignée à l\'employé'),
(64, 13, 6, 'changement_statut', '2025-11-03 22:26:56', 'en_cours_intervention', 'reparation_effectue', 'Réparation marquée comme reparation_effectue'),
(66, 2139, 6, 'demarrage', '2025-11-03 22:38:12', NULL, NULL, 'Réparation assignée à l\'employé'),
(67, 2139, 6, 'changement_statut', '2025-11-03 22:38:23', 'en_cours_intervention', 'reparation_effectue', 'Réparation marquée comme reparation_effectue'),
(68, 2136, 6, 'demarrage', '2025-11-03 22:38:24', NULL, NULL, 'Réparation assignée à l\'employé'),
(70, 2136, 6, 'changement_statut', '2025-11-06 01:54:41', 'reparation_effectue', 'reparation_effectue', 'Réparation marquée comme reparation_effectue'),
(71, 2134, 6, 'demarrage', '2025-11-06 01:55:12', NULL, NULL, 'Réparation assignée à l\'employé'),
(72, 2134, 6, 'changement_statut', '2025-11-06 01:55:53', 'en_cours_intervention', 'reparation_effectue', 'Réparation marquée comme reparation_effectue'),
(73, 12, 6, 'demarrage', '2025-11-06 02:06:13', NULL, NULL, 'Réparation assignée à l\'employé'),
(74, 12, 6, 'changement_statut', '2025-11-06 02:07:31', 'en_cours_intervention', 'reparation_effectue', 'Réparation marquée comme reparation_effectue'),
(77, 2140, 6, 'demarrage', '2025-11-08 21:38:53', NULL, NULL, 'Réparation assignée à l\'employé');

-- --------------------------------------------------------

--
-- Structure de la table `reparation_sms`
--

CREATE TABLE `reparation_sms` (
  `id` int NOT NULL,
  `reparation_id` int NOT NULL,
  `template_id` int NOT NULL,
  `telephone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_envoi` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `statut_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `reparation_sms`
--

INSERT INTO `reparation_sms` (`id`, `reparation_id`, `template_id`, `telephone`, `message`, `date_envoi`, `statut_id`) VALUES
(1, 1, 1, '+33782962906', 'Bonjour guezguez, votre réparation #1 a été enregistrée. Appareil: Informatique test. Prix estimé: 21,00€. Nous vous tiendrons informé de l\'avancement.', '2025-09-23 17:10:18', NULL),
(2, 1, 1, '33782962906', 'Bonjour, guezguez, \nLe devis de votre test est disponible. \nMontant : 2,40 €\n📄 Consultez votre devis ici :\n👉 https://mkmkmk.servo.tools/pages/devis_client.php?lien=5745323e5c856d8f206d5cf676ad29cb\n📲 Suivi réparation :\n👉 https://mkmkmk.servo.tools/suivi.php?id=1\nUne question ? Appelez-nous au 04 93 46 71 63\nMAISON DU GEEK', '2025-09-23 23:44:48', 1),
(3, 1, 1, '33782962906', '⏰ Rappel guezguez !\n\nVotre devis expire dans 13 jours.\n\n📄 Consultez votre devis :\n👉 https://mkmkmk.servo.tools/pages/devis_client.php?lien=5745323e5c856d8f206d5cf676ad29cb\n📲 Suivi réparation :\n👉 https://mkmkmk.servo.tools/suivi.php?id=1\n\n📞 Questions ? [COMPANY_PHONE]\n🏠 [COMPANY_NAME]', '2025-09-23 23:58:52', 1),
(4, 1, 1, '33782962906', '⏰ Rappel guezguez !\n\nVotre devis expire dans 13 jours.\n\n📄 Consultez votre devis :\n👉 https://mkmkmk.servo.tools/pages/devis_client.php?lien=5745323e5c856d8f206d5cf676ad29cb\n📲 Suivi réparation :\n👉 https://mkmkmk.servo.tools/suivi.php?id=1\n\n📞 Questions ? [COMPANY_PHONE]\n🏠 [COMPANY_NAME]', '2025-09-24 00:07:48', 1),
(5, 1, 1, '33782962906', '⏰ Rappel guezguez !\n\nVotre devis expire dans 13 jours.\n\n📄 Consultez votre devis :\n👉 https://mkmkmk.servo.tools/pages/devis_client.php?lien=5745323e5c856d8f206d5cf676ad29cb\n📲 Suivi réparation :\n👉 https://mkmkmk.servo.tools/suivi.php?id=1\n\n📞 Questions ? 05 55 44 33 22\n🏠 MD Geek Shop', '2025-09-24 00:13:23', 1),
(6, 2, 6, '+33782962906', '👋 Bonjour guezguez,\n🛠️ Nous avons bien reçu votre IPHONE 8 et nos experts geeks sont déjà à l\'œuvre pour le remettre en état.\n🔎 Suivez l\'avancement de la réparation ici :\n👉 https://mkmkmk.servo.tools/suivi.php?id=2\n💶 21,00€\n📞 Une question ? Contactez nous au 05 55 44 33 22\n🏠 MAISON DU GEEK 🛠️', '2025-09-24 00:14:44', NULL),
(7, 2, 1, '33782962906', 'Bonjour, guezguez, \nLe devis de votre IPHONE 8 est disponible. \nMontant : 237,60 €\n📄 Consultez votre devis ici :\n👉 https://mkmkmk.mdgeek.top/pages/devis_client.php?lien=e1b2b34ec76c64e29640f8deccb71db2\n📲 Suivi réparation :\n👉 https://mkmkmk.mdgeek.top/suivi.php?id=2\nUne question ? Appelez-nous au 05 55 44 33 22\nMAISON DU GEEK', '2025-09-24 15:17:35', 1),
(8, 3, 6, '+33782962906', '👋 Bonjour guezguez,\n🛠️ Nous avons bien reçu votre test et nos experts geeks sont déjà à l\'œuvre pour le remettre en état.\n🔎 Suivez l\'avancement de la réparation ici :\n👉 https://mkmkmk.mdgeek.top/suivi.php?id=3\n💶 22,00€\n📞 Une question ? Contactez nous au 05 55 44 33 22\n🏠 MAISON DU GEEK 🛠️', '2025-09-24 18:20:06', NULL),
(9, 4, 6, '+33782962906', '👋 Bonjour guezguez,\n🛠️ Nous avons bien reçu votre saber et nos experts geeks sont déjà à l\'œuvre pour le remettre en état.\n🔎 Suivez l\'avancement de la réparation ici :\n👉 https://mkmkmk.servo.tools/suivi.php?id=4\n💶 22,00€\n📞 Une question ? Contactez nous au 05 55 44 33 22\n🏠 MAISON DU GEEK 🛠️', '2025-09-24 18:21:08', NULL),
(10, 2, 1, '33782962906', 'Bonjour, guezguez, \nLe devis de votre IPHONE 8 est disponible. \nMontant : 28,80 €\n📄 Consultez votre devis ici :\n👉 https://mkmkmk.mdgeek.top/pages/devis_client.php?lien=eb739f36bba0b122372e543211260acc\n📲 Suivi réparation :\n👉 https://mkmkmk.mdgeek.top/suivi.php?id=2\nUne question ? Appelez-nous au 05 55 44 33 22\nMAISON DU GEEK', '2025-09-24 23:26:22', 1),
(11, 2, 1, '33782962906', 'Bonjour, guezguez, \nLe devis de votre IPHONE 8 est disponible. \nMontant : 106,80 €\n📄 Consultez votre devis ici :\n👉 https://mkmkmk.mdgeek.top/pages/devis_client.php?lien=7a89c5ec5f84b49062508fc42590e22a\n📲 Suivi réparation :\n👉 https://mkmkmk.mdgeek.top/suivi.php?id=2\nUne question ? Appelez-nous au 05 55 44 33 22\nMAISON DU GEEK', '2025-09-24 23:34:31', 1),
(12, 2, 1, '33782962906', 'Bonjour, guezguez, \nLe devis de votre IPHONE 8 est disponible. \nMontant : 28,80 €\n📄 Consultez votre devis ici :\n👉 https://mkmkmk.mdgeek.top/pages/devis_client.php?lien=11820d115068a3f65137bf89ce5c484d\n📲 Suivi réparation :\n👉 https://mkmkmk.mdgeek.top/suivi.php?id=2\nUne question ? Appelez-nous au 05 55 44 33 22\nMAISON DU GEEK', '2025-09-24 23:36:23', 1),
(13, 4, 1, '33782962906', 'Bonjour, guezguez, \nLe devis de votre saber est disponible. \nMontant : 296,40 €\n📄 Consultez votre devis ici :\n👉 https://mkmkmk.servo.tools/pages/devis_client.php?lien=a003621ffbccc941e0a964bd9e101393\n📲 Suivi réparation :\n👉 https://mkmkmk.servo.tools/suivi.php?id=4\nUne question ? Appelez-nous au 05 55 44 33 22\nMAISON DU GEEK', '2025-09-25 10:29:50', 1),
(14, 3, 1, '33782962906', 'Bonjour, guezguez, \nLe devis de votre test est disponible. \nMontant : 261,60 €\n📄 Consultez votre devis ici :\n👉 https://mkmkmk.mdgeek.top/pages/devis_client.php?lien=1f661d66811c582a2e566c80e2ba0823\n📲 Suivi réparation :\n👉 https://mkmkmk.mdgeek.top/suivi.php?id=3\nUne question ? Appelez-nous au 05 55 44 33 22\nMAISON DU GEEK', '2025-09-27 17:19:35', 1),
(15, 5, 6, '+33782962906', '👋 Bonjour guezguez,\n🛠️ Nous avons bien reçu votre SABER et nos experts geeks sont déjà à l\'œuvre pour le remettre en état.\n🔎 Suivez l\'avancement de la réparation ici :\n👉 https://mkmkmk.servo.tools/suivi.php?id=5\n💶 21,00€\n📞 Une question ? Contactez nous au [COMPANY_PHONE]\n🏠 [COMPANY_NAME] 🛠️', '2025-10-01 01:53:07', NULL),
(16, 2, 1, '33782962906', '⏰ Rappel guezguez !\n\nVotre devis expire dans 6 jours.\n\n📄 Consultez votre devis :\n👉 https://mkmkmk.mdgeek.top/pages/devis_client.php?lien=11820d115068a3f65137bf89ce5c484d\n📲 Suivi réparation :\n👉 https://mkmkmk.mdgeek.top/suivi.php?id=2\n\n📞 Questions ? 05 55 44 33 22\n🏠 MAISON DU GEEK', '2025-10-21 00:20:57', 1),
(17, 3, 1, '33782962906', '⏰ Rappel guezguez !\n\nVotre devis expire dans 6 jours.\n\n📄 Consultez votre devis :\n👉 https://mkmkmk.mdgeek.top/pages/devis_client.php?lien=1f661d66811c582a2e566c80e2ba0823\n📲 Suivi réparation :\n👉 https://mkmkmk.mdgeek.top/suivi.php?id=3\n\n📞 Questions ? 05 55 44 33 22\n🏠 MAISON DU GEEK', '2025-10-21 00:21:22', 1),
(18, 4, 1, '33782962906', 'guezguez, votre saber est prêt ! Il vous attend pour retrouver une vie normale. 🥳\n🧾 Suivi : http://mdgeek.fr/suivi.php?id=4\n[COMPANY_NAME] – [COMPANY_PHONE]', '2025-10-23 19:34:27', 1),
(19, 3, 1, '33782962906', '👋 Bonjour guezguez,\n🛠️ Nous avons bien reçu votre test et nos experts geeks sont déjà à l\'œuvre pour le remettre en état.\n🔎 Suivez l\'avancement de la réparation ici :\n👉 http://mdgeek.fr/suivi.php?id=3\n📞 Une question ? Contactez nous au [COMPANY_PHONE]\n🏠 [COMPANY_NAME] 🛠️', '2025-11-02 23:20:02', 1),
(20, 7, 1, '33123456789', 'ads', '2025-11-02 23:41:03', 1),
(24, 7, 1, '33123456789', 'asdsasdasda', '2025-11-03 22:04:26', 1),
(27, 2136, 1, '0615021764', 'nordine, votre Mini 4 Pro est prêt ! Il vous attend pour retrouver une vie normale. 🥳\n🧾 Suivi : [URL_SUIVI]\n[COMPANY_NAME] – [COMPANY_PHONE]', '2025-11-05 17:18:19', 1),
(28, 12, 1, '33782962906', 'Bonjour, guezguez, \nLe devis de votre Macbook Pro est disponible. \nMontant : 516,00 €\n📄 Consultez votre devis ici :\n👉 https://mkmkmk.mdgeek.top/pages/devis_client.php?lien=9510f7795abf9d12595a2ad102e2568b\n📲 Suivi réparation :\n👉 https://mkmkmk.mdgeek.top/suivi.php?id=12\nUne question ? Appelez-nous au 05 55 44 33 22\nMAISON DU GEEK', '2025-11-05 17:19:41', 1),
(29, 2140, 1, '33650883462', 'Bonjour brissaud, votre dossier 2140 au sujet de votre asd est en attente de validation par un responsable technique. Nous vous tenons informé très bientôt.\n📲 Suivi : [URL_SUIVI]\n[COMPANY_NAME] – [COMPANY_PHONE]', '2025-11-08 21:38:43', 1),
(30, 2140, 1, '33650883462', 'Bonjour, brissaud, \nLe devis de votre asd est disponible. \nMontant : 3,60 €\n📄 Consultez votre devis ici :\n👉 https://mkmkmk.servo.tools/pages/devis_client.php?lien=7709a45308176ecfb64d81db2d39a94a\n📲 Suivi réparation :\n👉 https://mkmkmk.servo.tools/suivi.php?id=2140\nUne question ? Appelez-nous au 05 55 44 33 22\nMAISON DU GEEK', '2025-11-08 23:16:12', 1),
(31, 13, 1, '33123456789', 'Bonjour, dsa, \nLe devis de votre sa est disponible. \nMontant : 26,40 €\n📄 Consultez votre devis ici :\n👉 https://mkmkmk.servo.tools/pages/devis_client.php?lien=f62b5a9d5fae0f455be46b6e2df2e69f\n📲 Suivi réparation :\n👉 https://mkmkmk.servo.tools/suivi.php?id=13\nUne question ? Appelez-nous au 05 55 44 33 22\nMAISON DU GEEK', '2025-11-08 23:20:35', 1),
(32, 12, 1, '33782962906', 'Bonjour, guezguez, \nLe devis de votre Macbook Pro est disponible. \nMontant : 39,60 €\n📄 Consultez votre devis ici :\n👉 https://mkmkmk.servo.tools/pages/devis_client.php?lien=8f27a383f262c623ee44b959feb13c78\n📲 Suivi réparation :\n👉 https://mkmkmk.servo.tools/suivi.php?id=12\nUne question ? Appelez-nous au 05 55 44 33 22\nMAISON DU GEEK', '2025-11-08 23:24:35', 1),
(33, 2, 1, '33782962906', '⚠️ GARDIENNAGE guezguez !\n\nVotre devis a expiré.\nGardiennage : 5,00€/jour\n\n📄 Consultez votre devis :\n👉 https://mkmkmk.mdgeek.top/pages/devis_client.php?lien=11820d115068a3f65137bf89ce5c484d\n📲 Suivi réparation :\n👉 https://mkmkmk.mdgeek.top/suivi.php?id=2\n\n📞 Questions ? 05 55 44 33 22\n🏠 MAISON DU GEEK', '2025-11-09 20:56:28', 1),
(34, 2140, 1, '33650883462', '⏰ Rappel brissaud !\n\nVotre devis expire dans 13 jours.\n\n📄 Consultez votre devis :\n👉 https://mkmkmk.mdgeek.top/pages/devis_client.php?lien=7709a45308176ecfb64d81db2d39a94a\n📲 Suivi réparation :\n👉 https://mkmkmk.mdgeek.top/suivi.php?id=2140\n\n📞 Questions ? 05 55 44 33 22\n🏠 MAISON DU GEEK', '2025-11-09 20:56:28', 1),
(35, 12, 1, '33782962906', '⏰ Rappel guezguez !\n\nVotre devis expire dans 13 jours.\n\n📄 Consultez votre devis :\n👉 https://mkmkmk.mdgeek.top/pages/devis_client.php?lien=8f27a383f262c623ee44b959feb13c78\n📲 Suivi réparation :\n👉 https://mkmkmk.mdgeek.top/suivi.php?id=12\n\n📞 Questions ? 05 55 44 33 22\n🏠 MAISON DU GEEK', '2025-11-09 20:56:28', 1),
(47, 13, 1, '33123456789', '⏰ Rappel dsa !\n\nVotre devis expire dans 13 jours.\n\n📄 Consultez votre devis :\n👉 https://mkmkmk.mdgeek.top/pages/devis_client.php?lien=f62b5a9d5fae0f455be46b6e2df2e69f\n📲 Suivi réparation :\n👉 https://mkmkmk.mdgeek.top/suivi.php?id=13\n\n📞 Questions ? 05 55 44 33 22\n🏠 MAISON DU GEEK', '2025-11-09 22:16:15', 1);

-- --------------------------------------------------------

--
-- Structure de la table `retours`
--

CREATE TABLE `retours` (
  `id` int NOT NULL,
  `produit_id` int NOT NULL,
  `date_creation` datetime DEFAULT CURRENT_TIMESTAMP,
  `date_limite` date NOT NULL,
  `statut` enum('en_attente','en_preparation','expedie','livre','a_verifier','termine') COLLATE utf8mb4_unicode_ci DEFAULT 'en_attente',
  `numero_suivi` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `montant_rembourse` decimal(10,2) DEFAULT NULL,
  `montant_rembourse_client` decimal(10,2) DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `colis_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `scheduled_notifications`
--

CREATE TABLE `scheduled_notifications` (
  `id` int NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `scheduled_datetime` datetime NOT NULL,
  `sent_datetime` datetime DEFAULT NULL,
  `target_user_id` int DEFAULT NULL,
  `is_broadcast` tinyint(1) NOT NULL DEFAULT '0',
  `notification_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'general',
  `action_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `status` enum('pending','sent','failed','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `options` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `services_partenaires`
--

CREATE TABLE `services_partenaires` (
  `id` int NOT NULL,
  `partenaire_id` int NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `montant` decimal(10,2) NOT NULL,
  `date_service` datetime DEFAULT CURRENT_TIMESTAMP,
  `statut` enum('EN_ATTENTE','VALIDÉ','ANNULÉ') COLLATE utf8mb4_unicode_ci DEFAULT 'EN_ATTENTE'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `sms_campaigns`
--

CREATE TABLE `sms_campaigns` (
  `id` int NOT NULL,
  `nom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_envoi` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `nb_destinataires` int NOT NULL DEFAULT '0',
  `nb_envoyes` int NOT NULL DEFAULT '0',
  `nb_echecs` int NOT NULL DEFAULT '0',
  `user_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `sms_campaign_details`
--

CREATE TABLE `sms_campaign_details` (
  `id` int NOT NULL,
  `campaign_id` int NOT NULL,
  `client_id` int NOT NULL,
  `telephone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `statut` enum('envoyé','échec') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'envoyé',
  `date_envoi` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `sms_deduplication`
--

CREATE TABLE `sms_deduplication` (
  `id` int NOT NULL,
  `phone_hash` varchar(64) NOT NULL,
  `message_hash` varchar(64) NOT NULL,
  `status_id` int DEFAULT NULL,
  `repair_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `sms_deduplication`
--

INSERT INTO `sms_deduplication` (`id`, `phone_hash`, `message_hash`, `status_id`, `repair_id`, `created_at`) VALUES
(253, '656c7a0c4f96f92f75bf9b5ea8cd4e4f697a9e738fe925d6cf21ceef2ef34f84', '9c22bc6e0d7f28d2505bf81a0fa0821b13bd7b78bb3908a07e37843bb0f2573c', NULL, NULL, '2025-11-08 23:16:12'),
(254, 'c473fbda80d42b39ba327f6a8b30fc64390255128a1b9d411179454bb58e5ec7', '74c755e71e3757bb79a70cf553ccf9e32f0fad477ca79dd7d73b94db93948dfd', NULL, NULL, '2025-11-08 23:20:35'),
(255, '1a616ec1c06322792b6f752c1a384822992d1a283a4ba5ac97d7dc124c0f7e44', 'ad597f7cb438da143f30532b4cabe61f2a18de250f8a3d6c63a016a9707a8bba', NULL, NULL, '2025-11-08 23:24:35'),
(256, '1a616ec1c06322792b6f752c1a384822992d1a283a4ba5ac97d7dc124c0f7e44', '4037189041dece1cefcfe75855d131d4c382a79508a85386b00d213913704c1c', NULL, NULL, '2025-11-09 01:43:17'),
(257, 'c473fbda80d42b39ba327f6a8b30fc64390255128a1b9d411179454bb58e5ec7', '8fb231af9405b5eebc6991dedb726b88e255380825b9f9c975be2648480a780e', NULL, NULL, '2025-11-09 01:47:30'),
(258, '656c7a0c4f96f92f75bf9b5ea8cd4e4f697a9e738fe925d6cf21ceef2ef34f84', '16a7eb4b2be7cca937de3bb6e2d7057454262b6d7801e1465c1ff870e56f025a', NULL, NULL, '2025-11-09 01:57:18'),
(259, '1a616ec1c06322792b6f752c1a384822992d1a283a4ba5ac97d7dc124c0f7e44', 'fe52c7e5fecfefc018dfdad5a065ecdc46e97ab42176e3f2dda8fe5b97684f09', NULL, NULL, '2025-11-09 20:56:28'),
(260, '656c7a0c4f96f92f75bf9b5ea8cd4e4f697a9e738fe925d6cf21ceef2ef34f84', 'ac0ffc93e948ec5519d44f822b81b72a3b9818528e9839700ae7146aa84f3e0d', NULL, NULL, '2025-11-09 20:56:28'),
(261, '1a616ec1c06322792b6f752c1a384822992d1a283a4ba5ac97d7dc124c0f7e44', '1fb80bd4ed80d92f922dacc9803e2169307ee0f3ad1d4173562f732578d533ab', NULL, NULL, '2025-11-09 20:56:28'),
(262, '1a616ec1c06322792b6f752c1a384822992d1a283a4ba5ac97d7dc124c0f7e44', 'fe52c7e5fecfefc018dfdad5a065ecdc46e97ab42176e3f2dda8fe5b97684f09', NULL, NULL, '2025-11-09 21:20:02'),
(263, '656c7a0c4f96f92f75bf9b5ea8cd4e4f697a9e738fe925d6cf21ceef2ef34f84', 'ac0ffc93e948ec5519d44f822b81b72a3b9818528e9839700ae7146aa84f3e0d', NULL, NULL, '2025-11-09 21:20:03'),
(264, '1a616ec1c06322792b6f752c1a384822992d1a283a4ba5ac97d7dc124c0f7e44', '1fb80bd4ed80d92f922dacc9803e2169307ee0f3ad1d4173562f732578d533ab', NULL, NULL, '2025-11-09 21:20:04'),
(265, '1a616ec1c06322792b6f752c1a384822992d1a283a4ba5ac97d7dc124c0f7e44', '1fb80bd4ed80d92f922dacc9803e2169307ee0f3ad1d4173562f732578d533ab', NULL, NULL, '2025-11-09 21:21:19'),
(266, '1a616ec1c06322792b6f752c1a384822992d1a283a4ba5ac97d7dc124c0f7e44', 'fe52c7e5fecfefc018dfdad5a065ecdc46e97ab42176e3f2dda8fe5b97684f09', NULL, NULL, '2025-11-09 21:54:05'),
(267, '656c7a0c4f96f92f75bf9b5ea8cd4e4f697a9e738fe925d6cf21ceef2ef34f84', 'ac0ffc93e948ec5519d44f822b81b72a3b9818528e9839700ae7146aa84f3e0d', NULL, NULL, '2025-11-09 21:54:06'),
(268, '1a616ec1c06322792b6f752c1a384822992d1a283a4ba5ac97d7dc124c0f7e44', '1fb80bd4ed80d92f922dacc9803e2169307ee0f3ad1d4173562f732578d533ab', NULL, NULL, '2025-11-09 21:54:07'),
(269, '1a616ec1c06322792b6f752c1a384822992d1a283a4ba5ac97d7dc124c0f7e44', 'fe52c7e5fecfefc018dfdad5a065ecdc46e97ab42176e3f2dda8fe5b97684f09', NULL, NULL, '2025-11-09 22:01:02'),
(270, '656c7a0c4f96f92f75bf9b5ea8cd4e4f697a9e738fe925d6cf21ceef2ef34f84', 'ac0ffc93e948ec5519d44f822b81b72a3b9818528e9839700ae7146aa84f3e0d', NULL, NULL, '2025-11-09 22:01:03'),
(271, '1a616ec1c06322792b6f752c1a384822992d1a283a4ba5ac97d7dc124c0f7e44', '1fb80bd4ed80d92f922dacc9803e2169307ee0f3ad1d4173562f732578d533ab', NULL, NULL, '2025-11-09 22:01:04'),
(272, '1a616ec1c06322792b6f752c1a384822992d1a283a4ba5ac97d7dc124c0f7e44', '1fb80bd4ed80d92f922dacc9803e2169307ee0f3ad1d4173562f732578d533ab', NULL, NULL, '2025-11-09 22:06:39'),
(273, 'c473fbda80d42b39ba327f6a8b30fc64390255128a1b9d411179454bb58e5ec7', 'e2003c42b251f863d8bf8ae12ad8dbcceb08171f4932d8604a39ccdc70adf397', NULL, NULL, '2025-11-09 22:16:15'),
(274, '1a616ec1c06322792b6f752c1a384822992d1a283a4ba5ac97d7dc124c0f7e44', 'fe52c7e5fecfefc018dfdad5a065ecdc46e97ab42176e3f2dda8fe5b97684f09', NULL, NULL, '2025-11-09 22:16:21'),
(275, '656c7a0c4f96f92f75bf9b5ea8cd4e4f697a9e738fe925d6cf21ceef2ef34f84', 'ac0ffc93e948ec5519d44f822b81b72a3b9818528e9839700ae7146aa84f3e0d', NULL, NULL, '2025-11-09 22:16:22'),
(276, '1a616ec1c06322792b6f752c1a384822992d1a283a4ba5ac97d7dc124c0f7e44', '1fb80bd4ed80d92f922dacc9803e2169307ee0f3ad1d4173562f732578d533ab', NULL, NULL, '2025-11-09 22:16:23');

-- --------------------------------------------------------

--
-- Structure de la table `sms_logs`
--

CREATE TABLE `sms_logs` (
  `id` int NOT NULL,
  `recipient` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` int DEFAULT NULL,
  `reparation_id` int DEFAULT NULL,
  `response` text COLLATE utf8mb4_unicode_ci,
  `date_envoi` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `sms_logs`
--

INSERT INTO `sms_logs` (`id`, `recipient`, `message`, `status`, `reparation_id`, `response`, `date_envoi`) VALUES
(1, '33782962906', 'Bonjour, guezguez, \nLe devis de votre test est disponible. \nMontant : 2,40 €\n📄 Consultez votre devis ici :\n👉 https://mkmkmk.servo.tools/pages/devis_client.php?lien=5745323e5c856d8f206d5cf676ad29cb\n📲 Suivi réparation :\n👉 https://mkmkmk.servo.tools/suivi.php?id=1\nUne question ? Appelez-nous au 04 93 46 71 63\nMAISON DU GEEK', 1, 1, '{\"success\":true,\"message\":\"SMS ajout\\u00e9 \\u00e0 la queue d\'envoi\",\"data\":{\"message_id\":1694,\"phone_id\":\"6ddc177d-8380-47db-bc7b-42354a2a0232\",\"sim_id\":68,\"status\":\"pending\",\"sim_info\":{\"carrier_name\":\"Free\",\"phone_number\":\"+33744770932\",\"is_default\":true,\"messages_sent\":86,\"monthly_limit\":30000}},\"attempt\":1,\"http_code\":201,\"response_time\":0.023948}', '2025-09-23 23:44:48'),
(2, '33782962906', '⏰ Rappel guezguez !\n\nVotre devis expire dans 13 jours.\n\n📄 Consultez votre devis :\n👉 https://mkmkmk.servo.tools/pages/devis_client.php?lien=5745323e5c856d8f206d5cf676ad29cb\n📲 Suivi réparation :\n👉 https://mkmkmk.servo.tools/suivi.php?id=1\n\n📞 Questions ? [COMPANY_PHONE]\n🏠 [COMPANY_NAME]', 1, 1, '{\"success\":true,\"message\":\"SMS ajout\\u00e9 \\u00e0 la queue d\'envoi\",\"data\":{\"message_id\":1696,\"phone_id\":\"6ddc177d-8380-47db-bc7b-42354a2a0232\",\"sim_id\":68,\"status\":\"pending\",\"sim_info\":{\"carrier_name\":\"Free\",\"phone_number\":\"+33744770932\",\"is_default\":true,\"messages_sent\":88,\"monthly_limit\":30000}},\"attempt\":1,\"http_code\":201,\"response_time\":0.009611}', '2025-09-23 23:58:52'),
(3, '33782962906', '⏰ Rappel guezguez !\n\nVotre devis expire dans 13 jours.\n\n📄 Consultez votre devis :\n👉 https://mkmkmk.servo.tools/pages/devis_client.php?lien=5745323e5c856d8f206d5cf676ad29cb\n📲 Suivi réparation :\n👉 https://mkmkmk.servo.tools/suivi.php?id=1\n\n📞 Questions ? [COMPANY_PHONE]\n🏠 [COMPANY_NAME]', 1, 1, '{\"success\":true,\"message\":\"SMS ajout\\u00e9 \\u00e0 la queue d\'envoi\",\"data\":{\"message_id\":1697,\"phone_id\":\"6ddc177d-8380-47db-bc7b-42354a2a0232\",\"sim_id\":68,\"status\":\"pending\",\"sim_info\":{\"carrier_name\":\"Free\",\"phone_number\":\"+33744770932\",\"is_default\":true,\"messages_sent\":89,\"monthly_limit\":30000}},\"attempt\":1,\"http_code\":201,\"response_time\":0.015463}', '2025-09-24 00:07:48'),
(4, '33782962906', '⏰ Rappel guezguez !\n\nVotre devis expire dans 13 jours.\n\n📄 Consultez votre devis :\n👉 https://mkmkmk.servo.tools/pages/devis_client.php?lien=5745323e5c856d8f206d5cf676ad29cb\n📲 Suivi réparation :\n👉 https://mkmkmk.servo.tools/suivi.php?id=1\n\n📞 Questions ? 05 55 44 33 22\n🏠 MD Geek Shop', 1, 1, '{\"success\":true,\"message\":\"SMS ajout\\u00e9 \\u00e0 la queue d\'envoi\",\"data\":{\"message_id\":1698,\"phone_id\":\"6ddc177d-8380-47db-bc7b-42354a2a0232\",\"sim_id\":68,\"status\":\"pending\",\"sim_info\":{\"carrier_name\":\"Free\",\"phone_number\":\"+33744770932\",\"is_default\":true,\"messages_sent\":90,\"monthly_limit\":30000}},\"attempt\":1,\"http_code\":201,\"response_time\":0.013275}', '2025-09-24 00:13:23'),
(5, '33782962906', 'Bonjour, guezguez, \nLe devis de votre IPHONE 8 est disponible. \nMontant : 237,60 €\n📄 Consultez votre devis ici :\n👉 https://mkmkmk.mdgeek.top/pages/devis_client.php?lien=e1b2b34ec76c64e29640f8deccb71db2\n📲 Suivi réparation :\n👉 https://mkmkmk.mdgeek.top/suivi.php?id=2\nUne question ? Appelez-nous au 05 55 44 33 22\nMAISON DU GEEK', 1, 2, '{\"success\":true,\"message\":\"SMS ajout\\u00e9 \\u00e0 la queue d\'envoi\",\"data\":{\"message_id\":1716,\"phone_id\":\"6ddc177d-8380-47db-bc7b-42354a2a0232\",\"sim_id\":68,\"status\":\"pending\",\"sim_info\":{\"carrier_name\":\"Free\",\"phone_number\":\"+33744770932\",\"is_default\":true,\"messages_sent\":107,\"monthly_limit\":30000}},\"attempt\":1,\"http_code\":201,\"response_time\":0.0116}', '2025-09-24 15:17:35'),
(6, '33782962906', 'Bonjour, guezguez, \nLe devis de votre IPHONE 8 est disponible. \nMontant : 28,80 €\n📄 Consultez votre devis ici :\n👉 https://mkmkmk.mdgeek.top/pages/devis_client.php?lien=eb739f36bba0b122372e543211260acc\n📲 Suivi réparation :\n👉 https://mkmkmk.mdgeek.top/suivi.php?id=2\nUne question ? Appelez-nous au 05 55 44 33 22\nMAISON DU GEEK', 1, 3, '{\"success\":true,\"message\":\"SMS ajout\\u00e9 \\u00e0 la queue d\'envoi\",\"data\":{\"message_id\":1721,\"phone_id\":\"6ddc177d-8380-47db-bc7b-42354a2a0232\",\"sim_id\":68,\"status\":\"pending\",\"sim_info\":{\"carrier_name\":\"Free\",\"phone_number\":\"+33744770932\",\"is_default\":true,\"messages_sent\":112,\"monthly_limit\":30000}},\"attempt\":1,\"http_code\":201,\"response_time\":0.016954}', '2025-09-24 23:26:22'),
(7, '33782962906', 'Bonjour, guezguez, \nLe devis de votre IPHONE 8 est disponible. \nMontant : 106,80 €\n📄 Consultez votre devis ici :\n👉 https://mkmkmk.mdgeek.top/pages/devis_client.php?lien=7a89c5ec5f84b49062508fc42590e22a\n📲 Suivi réparation :\n👉 https://mkmkmk.mdgeek.top/suivi.php?id=2\nUne question ? Appelez-nous au 05 55 44 33 22\nMAISON DU GEEK', 1, 4, '{\"success\":true,\"message\":\"SMS ajout\\u00e9 \\u00e0 la queue d\'envoi\",\"data\":{\"message_id\":1722,\"phone_id\":\"6ddc177d-8380-47db-bc7b-42354a2a0232\",\"sim_id\":68,\"status\":\"pending\",\"sim_info\":{\"carrier_name\":\"Free\",\"phone_number\":\"+33744770932\",\"is_default\":true,\"messages_sent\":113,\"monthly_limit\":30000}},\"attempt\":1,\"http_code\":201,\"response_time\":0.009129}', '2025-09-24 23:34:31'),
(8, '33782962906', 'Bonjour, guezguez, \nLe devis de votre IPHONE 8 est disponible. \nMontant : 28,80 €\n📄 Consultez votre devis ici :\n👉 https://mkmkmk.mdgeek.top/pages/devis_client.php?lien=11820d115068a3f65137bf89ce5c484d\n📲 Suivi réparation :\n👉 https://mkmkmk.mdgeek.top/suivi.php?id=2\nUne question ? Appelez-nous au 05 55 44 33 22\nMAISON DU GEEK', 1, 5, '{\"success\":true,\"message\":\"SMS ajout\\u00e9 \\u00e0 la queue d\'envoi\",\"data\":{\"message_id\":1723,\"phone_id\":\"6ddc177d-8380-47db-bc7b-42354a2a0232\",\"sim_id\":68,\"status\":\"pending\",\"sim_info\":{\"carrier_name\":\"Free\",\"phone_number\":\"+33744770932\",\"is_default\":true,\"messages_sent\":114,\"monthly_limit\":30000}},\"attempt\":1,\"http_code\":201,\"response_time\":0.012508}', '2025-09-24 23:36:23'),
(9, '33782962906', 'Bonjour, guezguez, \nLe devis de votre saber est disponible. \nMontant : 296,40 €\n📄 Consultez votre devis ici :\n👉 https://mkmkmk.servo.tools/pages/devis_client.php?lien=a003621ffbccc941e0a964bd9e101393\n📲 Suivi réparation :\n👉 https://mkmkmk.servo.tools/suivi.php?id=4\nUne question ? Appelez-nous au 05 55 44 33 22\nMAISON DU GEEK', 1, 6, '{\"success\":true,\"message\":\"SMS ajout\\u00e9 \\u00e0 la queue d\'envoi\",\"data\":{\"message_id\":1727,\"phone_id\":\"6ddc177d-8380-47db-bc7b-42354a2a0232\",\"sim_id\":68,\"status\":\"pending\",\"sim_info\":{\"carrier_name\":\"Free\",\"phone_number\":\"+33744770932\",\"is_default\":true,\"messages_sent\":118,\"monthly_limit\":30000}},\"attempt\":1,\"http_code\":201,\"response_time\":0.012939}', '2025-09-25 10:29:50'),
(10, '33782962906', 'Bonjour, guezguez, \nLe devis de votre test est disponible. \nMontant : 261,60 €\n📄 Consultez votre devis ici :\n👉 https://mkmkmk.mdgeek.top/pages/devis_client.php?lien=1f661d66811c582a2e566c80e2ba0823\n📲 Suivi réparation :\n👉 https://mkmkmk.mdgeek.top/suivi.php?id=3\nUne question ? Appelez-nous au 05 55 44 33 22\nMAISON DU GEEK', 1, 7, '{\"success\":true,\"message\":\"SMS ajout\\u00e9 \\u00e0 la queue d\'envoi\",\"data\":{\"message_id\":1794,\"phone_id\":\"14172d2f-a69d-4c7e-b6d3-f5aea8aa5fc2\",\"sim_id\":84,\"status\":\"pending\",\"sim_info\":{\"carrier_name\":\"Free\",\"phone_number\":\"+33759639560\",\"is_default\":true,\"messages_sent\":61,\"monthly_limit\":30000}},\"attempt\":1,\"http_code\":201,\"response_time\":0.014916}', '2025-09-27 17:19:35'),
(11, '33782962906', '🎉 guezguez,\nTon saber est de retour à la maison ! On espère qu\'il est content 🤓\n💬 Laisse-nous un petit avis !\n⭐ https://g.page/r/Ce-HHwKZjezIEB0/review\n🏠 [COMPANY_NAME]\n📞 [COMPANY_PHONE]\n', 1, 4, '{\"success\":true,\"message\":\"SMS ajout\\u00e9 \\u00e0 la queue d\'envoi\",\"data\":{\"message_id\":1795,\"phone_id\":\"14172d2f-a69d-4c7e-b6d3-f5aea8aa5fc2\",\"sim_id\":84,\"status\":\"pending\",\"sim_info\":{\"carrier_name\":\"Free\",\"phone_number\":\"+33759639560\",\"is_default\":true,\"messages_sent\":62,\"monthly_limit\":30000}},\"attempt\":1,\"http_code\":201,\"response_time\":0.014819}', '2025-09-27 17:22:08'),
(12, '33782962906', '🎉 guezguez,\nTon test est de retour à la maison ! On espère qu\'il est content 🤓\n💬 Laisse-nous un petit avis !\n⭐ https://g.page/r/Ce-HHwKZjezIEB0/review\n🏠 [COMPANY_NAME]\n📞 [COMPANY_PHONE]\n', 1, 3, '{\"success\":true,\"message\":\"SMS ajout\\u00e9 \\u00e0 la queue d\'envoi\",\"data\":{\"message_id\":1796,\"phone_id\":\"14172d2f-a69d-4c7e-b6d3-f5aea8aa5fc2\",\"sim_id\":84,\"status\":\"pending\",\"sim_info\":{\"carrier_name\":\"Free\",\"phone_number\":\"+33759639560\",\"is_default\":true,\"messages_sent\":63,\"monthly_limit\":30000}},\"attempt\":1,\"http_code\":201,\"response_time\":0.010698}', '2025-09-27 17:22:08'),
(13, '33782962906', '👋 Bonjour guezguez,\n🛠️ Nous avons bien reçu votre test et nos experts geeks sont déjà à l\'œuvre pour le remettre en état.\n🔎 Suivez l\'avancement de la réparation ici :\n👉 http://mdgeek.fr/suivi.php?id=1\n📞 Une question ? Contactez nous au [COMPANY_PHONE]\n🏠 [COMPANY_NAME] 🛠️', 1, 1, '{\"success\":true,\"message\":\"SMS ajout\\u00e9 \\u00e0 la queue d\'envoi\",\"data\":{\"message_id\":1797,\"phone_id\":\"14172d2f-a69d-4c7e-b6d3-f5aea8aa5fc2\",\"sim_id\":84,\"status\":\"pending\",\"sim_info\":{\"carrier_name\":\"Free\",\"phone_number\":\"+33759639560\",\"is_default\":true,\"messages_sent\":64,\"monthly_limit\":30000}},\"attempt\":1,\"http_code\":201,\"response_time\":0.023393}', '2025-09-27 20:28:12'),
(14, '0782962906', 'Accès partenaire: https://mkmkmk.mdgeek.top/partner_transaction.php?pid=1', 1, 1, '{\"success\":true,\"message\":\"SMS ajout\\u00e9 \\u00e0 la queue d\'envoi\",\"data\":{\"message_id\":1799,\"phone_id\":\"14172d2f-a69d-4c7e-b6d3-f5aea8aa5fc2\",\"sim_id\":84,\"status\":\"pending\",\"sim_info\":{\"carrier_name\":\"Free\",\"phone_number\":\"+33759639560\",\"is_default\":true,\"messages_sent\":66,\"monthly_limit\":30000}},\"attempt\":1,\"http_code\":201,\"response_time\":0.011035}', '2025-09-27 23:24:13'),
(15, '33123456789', 'dsa, on espère que ton asd se porte comme un charme ! 😊 Aide nos Geeks avec un petit avis :\n⭐ https://g.page/r/Ce-HHwKZjezIEB0/review\n📲 Suivi : http://mdgeek.fr/suivi.php?id=9\n[COMPANY_NAME] – [COMPANY_PHONE]', 1, 9, '{\"success\":true,\"message\":\"SMS ajout\\u00e9 \\u00e0 la queue d\'envoi\",\"data\":{\"message_id\":1994,\"phone_id\":\"14172d2f-a69d-4c7e-b6d3-f5aea8aa5fc2\",\"sim_id\":89,\"status\":\"pending\",\"sim_info\":{\"carrier_name\":\"Free\",\"phone_number\":\"+33783716897\",\"is_default\":true,\"messages_sent\":14,\"monthly_limit\":600}},\"attempt\":1,\"http_code\":201,\"response_time\":0.085717}', '2025-10-13 01:24:58'),
(16, '33782962906', 'guezguez, on espère que ton SABER se porte comme un charme ! 😊 Aide nos Geeks avec un petit avis :\n⭐ https://g.page/r/Ce-HHwKZjezIEB0/review\n📲 Suivi : http://mdgeek.fr/suivi.php?id=5\n[COMPANY_NAME] – [COMPANY_PHONE]', 1, 5, '{\"success\":true,\"message\":\"SMS ajout\\u00e9 \\u00e0 la queue d\'envoi\",\"data\":{\"message_id\":1995,\"phone_id\":\"14172d2f-a69d-4c7e-b6d3-f5aea8aa5fc2\",\"sim_id\":89,\"status\":\"pending\",\"sim_info\":{\"carrier_name\":\"Free\",\"phone_number\":\"+33783716897\",\"is_default\":true,\"messages_sent\":15,\"monthly_limit\":600}},\"attempt\":1,\"http_code\":201,\"response_time\":0.010136}', '2025-10-13 01:24:58'),
(17, '33782962906', 'guezguez, on espère que ton test se porte comme un charme ! 😊 Aide nos Geeks avec un petit avis :\n⭐ https://g.page/r/Ce-HHwKZjezIEB0/review\n📲 Suivi : http://mdgeek.fr/suivi.php?id=1\n[COMPANY_NAME] – [COMPANY_PHONE]', 1, 1, '{\"success\":true,\"message\":\"SMS ajout\\u00e9 \\u00e0 la queue d\'envoi\",\"data\":{\"message_id\":1996,\"phone_id\":\"14172d2f-a69d-4c7e-b6d3-f5aea8aa5fc2\",\"sim_id\":89,\"status\":\"pending\",\"sim_info\":{\"carrier_name\":\"Free\",\"phone_number\":\"+33783716897\",\"is_default\":true,\"messages_sent\":16,\"monthly_limit\":600}},\"attempt\":1,\"http_code\":201,\"response_time\":0.013224}', '2025-10-13 01:25:27'),
(18, '33782962906', '⏰ Rappel guezguez !\n\nVotre devis expire dans 6 jours.\n\n📄 Consultez votre devis :\n👉 https://mkmkmk.mdgeek.top/pages/devis_client.php?lien=11820d115068a3f65137bf89ce5c484d\n📲 Suivi réparation :\n👉 https://mkmkmk.mdgeek.top/suivi.php?id=2\n\n📞 Questions ? 05 55 44 33 22\n🏠 MAISON DU GEEK', 1, 5, '{\"success\":true,\"message\":\"SMS ajout\\u00e9 \\u00e0 la queue d\'envoi\",\"data\":{\"message_id\":2070,\"phone_id\":\"14172d2f-a69d-4c7e-b6d3-f5aea8aa5fc2\",\"sim_id\":89,\"status\":\"pending\",\"sim_info\":{\"carrier_name\":\"Free\",\"phone_number\":\"+33783716897\",\"is_default\":true,\"messages_sent\":90,\"monthly_limit\":600}},\"attempt\":1,\"http_code\":201,\"response_time\":0.012771}', '2025-10-21 00:20:57'),
(19, '33782962906', '⏰ Rappel guezguez !\n\nVotre devis expire dans 6 jours.\n\n📄 Consultez votre devis :\n👉 https://mkmkmk.mdgeek.top/pages/devis_client.php?lien=1f661d66811c582a2e566c80e2ba0823\n📲 Suivi réparation :\n👉 https://mkmkmk.mdgeek.top/suivi.php?id=3\n\n📞 Questions ? 05 55 44 33 22\n🏠 MAISON DU GEEK', 1, 7, '{\"success\":true,\"message\":\"SMS ajout\\u00e9 \\u00e0 la queue d\'envoi\",\"data\":{\"message_id\":2071,\"phone_id\":\"14172d2f-a69d-4c7e-b6d3-f5aea8aa5fc2\",\"sim_id\":89,\"status\":\"pending\",\"sim_info\":{\"carrier_name\":\"Free\",\"phone_number\":\"+33783716897\",\"is_default\":true,\"messages_sent\":91,\"monthly_limit\":600}},\"attempt\":1,\"http_code\":201,\"response_time\":0.015943}', '2025-10-21 00:21:22'),
(20, '33782962906', 'guezguez, votre saber est prêt ! Il vous attend pour retrouver une vie normale. 🥳\n🧾 Suivi : http://mdgeek.fr/suivi.php?id=4\n[COMPANY_NAME] – [COMPANY_PHONE]', 1, 4, '{\"success\":true,\"message\":\"SMS ajout\\u00e9 \\u00e0 la queue d\'envoi\",\"data\":{\"message_id\":2107,\"phone_id\":\"14172d2f-a69d-4c7e-b6d3-f5aea8aa5fc2\",\"sim_id\":93,\"status\":\"pending\",\"sim_info\":{\"carrier_name\":\"Free\",\"phone_number\":\"+33744770932\",\"is_default\":true,\"messages_sent\":1,\"monthly_limit\":30000}},\"attempt\":1,\"http_code\":201,\"response_time\":0.033867}', '2025-10-23 19:34:27'),
(21, '33782962906', '👋 Bonjour guezguez,\n🛠️ Nous avons bien reçu votre test et nos experts geeks sont déjà à l\'œuvre pour le remettre en état.\n🔎 Suivez l\'avancement de la réparation ici :\n👉 http://mdgeek.fr/suivi.php?id=3\n📞 Une question ? Contactez nous au [COMPANY_PHONE]\n🏠 [COMPANY_NAME] 🛠️', 1, 3, '{\"success\":true,\"message\":\"SMS ajout\\u00e9 \\u00e0 la queue d\'envoi\",\"data\":{\"message_id\":63,\"phone_id\":\"SM-A805F\",\"sim_id\":323,\"status\":\"pending\",\"sim_info\":{\"carrier_name\":\"Free\",\"phone_number\":\"+33745520054\",\"is_default\":true,\"messages_sent\":1,\"monthly_limit\":30000}},\"attempt\":1,\"http_code\":201,\"response_time\":0.032919}', '2025-11-02 23:20:02'),
(22, '33123456789', 'ads', 1, 7, '{\"success\":true,\"message\":\"SMS ajout\\u00e9 \\u00e0 la queue d\'envoi\",\"data\":{\"message_id\":65,\"phone_id\":\"SM-A805F\",\"sim_id\":323,\"status\":\"pending\",\"sim_info\":{\"carrier_name\":\"Free\",\"phone_number\":\"+33745520054\",\"is_default\":true,\"messages_sent\":3,\"monthly_limit\":30000}},\"attempt\":1,\"http_code\":201,\"response_time\":0.013072}', '2025-11-02 23:41:03'),
(23, '33782962906', 'asdad', 0, 1, '{\"success\":false,\"message\":\"Aucune SIM active disponible pour envoyer le SMS.\",\"response\":{\"success\":false,\"message\":\"Aucune SIM active disponible pour envoyer le SMS.\"},\"attempt\":2,\"http_code\":503}', '2025-11-02 23:41:17'),
(24, '33782962906', 'asdaddds', 0, 1, '{\"success\":false,\"message\":\"Aucune SIM active disponible pour envoyer le SMS.\",\"response\":{\"success\":false,\"message\":\"Aucune SIM active disponible pour envoyer le SMS.\"},\"attempt\":2,\"http_code\":503}', '2025-11-02 23:41:36'),
(25, '33123456789', 'nbiuubui', 0, 7, '{\"success\":false,\"message\":\"Aucune SIM active disponible pour envoyer le SMS.\",\"response\":{\"success\":false,\"message\":\"Aucune SIM active disponible pour envoyer le SMS.\"},\"attempt\":2,\"http_code\":503}', '2025-11-02 23:45:09'),
(26, '33123456789', '👋 Bonjour dsa,\n🛠️ Nous avons bien reçu votre dsa et nos experts geeks sont déjà à l\'œuvre pour le remettre en état.\n🔎 Suivez l\'avancement de la réparation ici :\n👉 http://mdgeek.fr/suivi.php?id=11\n📞 Une question ? Contactez nous au [COMPANY_PHONE]\n🏠 [COMPANY_NAME] 🛠️', 0, 11, '{\"success\":false,\"message\":\"Aucune SIM active disponible pour envoyer le SMS.\",\"response\":{\"success\":false,\"message\":\"Aucune SIM active disponible pour envoyer le SMS.\"},\"attempt\":2,\"http_code\":503}', '2025-11-02 23:46:03'),
(27, '33782962906', '👋 Bonjour guezguez,\n🛠️ Nous avons bien reçu votre sav et nos experts geeks sont déjà à l\'œuvre pour le remettre en état.\n🔎 Suivez l\'avancement de la réparation ici :\n👉 http://mdgeek.fr/suivi.php?id=10\n📞 Une question ? Contactez nous au [COMPANY_PHONE]\n🏠 [COMPANY_NAME] 🛠️', 0, 10, '{\"success\":false,\"message\":\"Aucune SIM active disponible pour envoyer le SMS.\",\"response\":{\"success\":false,\"message\":\"Aucune SIM active disponible pour envoyer le SMS.\"},\"attempt\":2,\"http_code\":503}', '2025-11-02 23:46:04'),
(28, '33123456789', 'Bonjour dsa asd, votre devis de réparation pour Trottinette d\'un montant de [PRIX] est prêt. Vous pouvez l\'accepter ou le refuser via ce lien: [LIEN_SUIVI]', 0, 11, '{\"success\":false,\"message\":\"Aucune SIM active disponible pour envoyer le SMS.\",\"response\":{\"success\":false,\"message\":\"Aucune SIM active disponible pour envoyer le SMS.\"},\"attempt\":2,\"http_code\":503}', '2025-11-02 23:46:18'),
(29, '33782962906', 'Bonjour guezguez saber, votre devis de réparation pour Trottinette d\'un montant de [PRIX] est prêt. Vous pouvez l\'accepter ou le refuser via ce lien: [LIEN_SUIVI]', 0, 10, '{\"success\":false,\"message\":\"Aucune SIM active disponible pour envoyer le SMS.\",\"response\":{\"success\":false,\"message\":\"Aucune SIM active disponible pour envoyer le SMS.\"},\"attempt\":2,\"http_code\":503}', '2025-11-02 23:46:19'),
(30, '33123456789', 'asdsasdasda', 1, 7, '{\"success\":true,\"message\":\"SMS ajout\\u00e9 \\u00e0 la queue d\'envoi\",\"data\":{\"message_id\":73,\"phone_id\":\"SM-A805F\",\"sim_id\":329,\"status\":\"pending\",\"sim_info\":{\"carrier_name\":\"Free\",\"phone_number\":\"+33744770932\",\"is_default\":true,\"messages_sent\":1,\"monthly_limit\":30000}},\"attempt\":1,\"http_code\":201,\"response_time\":0.020215}', '2025-11-03 22:04:26'),
(31, '33123456789', '👋 Bonjour dsa,\n🛠️ Nous avons bien reçu votre dsa et nos experts geeks sont déjà à l\'œuvre pour le remettre en état.\n🔎 Suivez l\'avancement de la réparation ici :\n👉 [URL_SUIVI]\n💶 21,00 €\n📞 Une question ? Contactez nous au [COMPANY_PHONE]\n🏠 [COMPANY_NAME] 🛠️', 0, 11, '{\"success\":false,\"message\":\"Aucune SIM active disponible pour envoyer le SMS.\",\"response\":{\"success\":false,\"message\":\"Aucune SIM active disponible pour envoyer le SMS.\"},\"attempt\":2,\"http_code\":503}', '2025-11-03 22:05:24'),
(32, '33782962906', 'guezguez, votre IPHONE 8 est prêt ! Il vous attend pour retrouver une vie normale. 🥳\n🧾 Suivi : [URL_SUIVI]\n[COMPANY_NAME] – [COMPANY_PHONE]', 0, 2, '{\"success\":false,\"message\":\"Aucune SIM active disponible pour envoyer le SMS.\",\"response\":{\"success\":false,\"message\":\"Aucune SIM active disponible pour envoyer le SMS.\"},\"attempt\":2,\"http_code\":503}', '2025-11-03 22:37:51'),
(33, '0615021764', 'nordine, votre Mini 4 Pro est prêt ! Il vous attend pour retrouver une vie normale. 🥳\n🧾 Suivi : [URL_SUIVI]\n[COMPANY_NAME] – [COMPANY_PHONE]', 1, 2136, '{\"success\":true,\"message\":\"SMS ajout\\u00e9 \\u00e0 la queue d\'envoi\",\"data\":{\"message_id\":120,\"phone_id\":\"SM-A805F\",\"sim_id\":333,\"status\":\"pending\",\"sim_info\":{\"carrier_name\":\"Free\",\"phone_number\":\"+33744770932\",\"is_default\":true,\"messages_sent\":12,\"monthly_limit\":30000}},\"attempt\":1,\"http_code\":201,\"response_time\":0.02542}', '2025-11-05 17:18:19'),
(34, '33782962906', 'Bonjour, guezguez, \nLe devis de votre Macbook Pro est disponible. \nMontant : 516,00 €\n📄 Consultez votre devis ici :\n👉 https://mkmkmk.mdgeek.top/pages/devis_client.php?lien=9510f7795abf9d12595a2ad102e2568b\n📲 Suivi réparation :\n👉 https://mkmkmk.mdgeek.top/suivi.php?id=12\nUne question ? Appelez-nous au 05 55 44 33 22\nMAISON DU GEEK', 1, 8, '{\"success\":true,\"message\":\"SMS ajout\\u00e9 \\u00e0 la queue d\'envoi\",\"data\":{\"message_id\":121,\"phone_id\":\"SM-A805F\",\"sim_id\":333,\"status\":\"pending\",\"sim_info\":{\"carrier_name\":\"Free\",\"phone_number\":\"+33744770932\",\"is_default\":true,\"messages_sent\":13,\"monthly_limit\":30000}},\"attempt\":1,\"http_code\":201,\"response_time\":0.008523}', '2025-11-05 17:19:41'),
(35, '33650883462', 'Bonjour brissaud, votre dossier 2140 au sujet de votre asd est en attente de validation par un responsable technique. Nous vous tenons informé très bientôt.\n📲 Suivi : [URL_SUIVI]\n[COMPANY_NAME] – [COMPANY_PHONE]', 1, 2140, '{\"success\":true,\"message\":\"SMS ajout\\u00e9 \\u00e0 la queue d\'envoi\",\"data\":{\"message_id\":264,\"phone_id\":\"SM-A035G\",\"sim_id\":352,\"status\":\"pending\",\"sim_info\":{\"carrier_name\":\"Free\",\"phone_number\":\"+33744770932\",\"is_default\":true,\"messages_sent\":13,\"monthly_limit\":30000}},\"attempt\":1,\"http_code\":201,\"response_time\":0.039604}', '2025-11-08 21:38:42'),
(36, '33650883462', 'Bonjour, brissaud, \nLe devis de votre asd est disponible. \nMontant : 3,60 €\n📄 Consultez votre devis ici :\n👉 https://mkmkmk.servo.tools/pages/devis_client.php?lien=7709a45308176ecfb64d81db2d39a94a\n📲 Suivi réparation :\n👉 https://mkmkmk.servo.tools/suivi.php?id=2140\nUne question ? Appelez-nous au 05 55 44 33 22\nMAISON DU GEEK', 1, 9, '{\"success\":true,\"message\":\"SMS ajout\\u00e9 \\u00e0 la queue d\'envoi\",\"data\":{\"message_id\":273,\"phone_id\":\"SM-A035G\",\"sim_id\":352,\"status\":\"pending\",\"sim_info\":{\"carrier_name\":\"Free\",\"phone_number\":\"+33744770932\",\"is_default\":true,\"messages_sent\":14,\"monthly_limit\":30000}},\"attempt\":1,\"http_code\":201,\"response_time\":0.014013}', '2025-11-08 23:16:12'),
(37, '33123456789', 'Bonjour, dsa, \nLe devis de votre sa est disponible. \nMontant : 26,40 €\n📄 Consultez votre devis ici :\n👉 https://mkmkmk.servo.tools/pages/devis_client.php?lien=f62b5a9d5fae0f455be46b6e2df2e69f\n📲 Suivi réparation :\n👉 https://mkmkmk.servo.tools/suivi.php?id=13\nUne question ? Appelez-nous au 05 55 44 33 22\nMAISON DU GEEK', 1, 10, '{\"success\":true,\"message\":\"SMS ajout\\u00e9 \\u00e0 la queue d\'envoi\",\"data\":{\"message_id\":277,\"phone_id\":\"SM-A035G\",\"sim_id\":352,\"status\":\"pending\",\"sim_info\":{\"carrier_name\":\"Free\",\"phone_number\":\"+33744770932\",\"is_default\":true,\"messages_sent\":15,\"monthly_limit\":30000}},\"attempt\":1,\"http_code\":201,\"response_time\":0.017003}', '2025-11-08 23:20:35'),
(38, '33782962906', 'Bonjour, guezguez, \nLe devis de votre Macbook Pro est disponible. \nMontant : 39,60 €\n📄 Consultez votre devis ici :\n👉 https://mkmkmk.servo.tools/pages/devis_client.php?lien=8f27a383f262c623ee44b959feb13c78\n📲 Suivi réparation :\n👉 https://mkmkmk.servo.tools/suivi.php?id=12\nUne question ? Appelez-nous au 05 55 44 33 22\nMAISON DU GEEK', 1, 11, '{\"success\":true,\"message\":\"SMS ajout\\u00e9 \\u00e0 la queue d\'envoi\",\"data\":{\"message_id\":287,\"phone_id\":\"SM-A035G\",\"sim_id\":353,\"status\":\"pending\",\"sim_info\":{\"carrier_name\":\"Free\",\"phone_number\":\"+33783716897\",\"is_default\":true,\"messages_sent\":3,\"monthly_limit\":30000}},\"attempt\":1,\"http_code\":201,\"response_time\":0.009791}', '2025-11-08 23:24:35'),
(39, '33782962906', 'guezguez, votre Macbook Pro est prêt ! Il vous attend pour retrouver une vie normale. 🥳\n🧾 Suivi : https://mkmkmk.servo.tools/suivi.php?id=12\n[COMPANY_NAME] – [COMPANY_PHONE]', 1, 12, '{\"success\":true,\"message\":\"SMS ajout\\u00e9 \\u00e0 la queue d\'envoi\",\"data\":{\"message_id\":290,\"phone_id\":\"SM-A805F\",\"sim_id\":377,\"status\":\"pending\",\"sim_info\":{\"carrier_name\":\"Free\",\"phone_number\":\"+33745520054\",\"is_default\":true,\"messages_sent\":3,\"monthly_limit\":30000}},\"attempt\":1,\"http_code\":201,\"response_time\":0.029526}', '2025-11-09 01:43:17'),
(40, '33123456789', 'dsa, votre sa est prêt ! Il vous attend pour retrouver une vie normale. 🥳\n🧾 Suivi : https://mkmkmk.servo.tools/suivi.php?id=13\nmkmkmk – 04 93 68 66 30', 1, 13, '{\"success\":true,\"message\":\"SMS ajout\\u00e9 \\u00e0 la queue d\'envoi\",\"data\":{\"message_id\":294,\"phone_id\":\"SM-A035G\",\"sim_id\":352,\"status\":\"pending\",\"sim_info\":{\"carrier_name\":\"Free\",\"phone_number\":\"+33744770932\",\"is_default\":true,\"messages_sent\":16,\"monthly_limit\":30000}},\"attempt\":1,\"http_code\":201,\"response_time\":0.012062}', '2025-11-09 01:47:30'),
(41, '33650883462', 'brissaud, votre asd est prêt ! Il vous attend pour retrouver une vie normale. 🥳\n🧾 Suivi : https://mkmkmk.servo.tools/suivi.php?id=2140\nMKMKMK – 66666', 1, 2140, '{\"success\":true,\"message\":\"SMS ajout\\u00e9 \\u00e0 la queue d\'envoi\",\"data\":{\"message_id\":306,\"phone_id\":\"SM-A805F\",\"sim_id\":377,\"status\":\"pending\",\"sim_info\":{\"carrier_name\":\"Free\",\"phone_number\":\"+33745520054\",\"is_default\":true,\"messages_sent\":4,\"monthly_limit\":30000}},\"attempt\":1,\"http_code\":201,\"response_time\":0.009555}', '2025-11-09 01:57:18'),
(42, '33782962906', '⚠️ GARDIENNAGE guezguez !\n\nVotre devis a expiré.\nGardiennage : 5,00€/jour\n\n📄 Consultez votre devis :\n👉 https://mkmkmk.mdgeek.top/pages/devis_client.php?lien=11820d115068a3f65137bf89ce5c484d\n📲 Suivi réparation :\n👉 https://mkmkmk.mdgeek.top/suivi.php?id=2\n\n📞 Questions ? 05 55 44 33 22\n🏠 MAISON DU GEEK', 1, 5, '{\"success\":true,\"message\":\"SMS ajout\\u00e9 \\u00e0 la queue d\'envoi\",\"data\":{\"message_id\":315,\"phone_id\":\"SM-A035G\",\"sim_id\":352,\"status\":\"pending\",\"sim_info\":{\"carrier_name\":\"Free\",\"phone_number\":\"+33744770932\",\"is_default\":true,\"messages_sent\":17,\"monthly_limit\":30000}},\"attempt\":1,\"http_code\":201,\"response_time\":0.04667}', '2025-11-09 20:56:28'),
(43, '33650883462', '⏰ Rappel brissaud !\n\nVotre devis expire dans 13 jours.\n\n📄 Consultez votre devis :\n👉 https://mkmkmk.mdgeek.top/pages/devis_client.php?lien=7709a45308176ecfb64d81db2d39a94a\n📲 Suivi réparation :\n👉 https://mkmkmk.mdgeek.top/suivi.php?id=2140\n\n📞 Questions ? 05 55 44 33 22\n🏠 MAISON DU GEEK', 1, 9, '{\"success\":true,\"message\":\"SMS ajout\\u00e9 \\u00e0 la queue d\'envoi\",\"data\":{\"message_id\":316,\"phone_id\":\"SM-A035G\",\"sim_id\":352,\"status\":\"pending\",\"sim_info\":{\"carrier_name\":\"Free\",\"phone_number\":\"+33744770932\",\"is_default\":true,\"messages_sent\":18,\"monthly_limit\":30000}},\"attempt\":1,\"http_code\":201,\"response_time\":0.010119}', '2025-11-09 20:56:28'),
(44, '33782962906', '⏰ Rappel guezguez !\n\nVotre devis expire dans 13 jours.\n\n📄 Consultez votre devis :\n👉 https://mkmkmk.mdgeek.top/pages/devis_client.php?lien=8f27a383f262c623ee44b959feb13c78\n📲 Suivi réparation :\n👉 https://mkmkmk.mdgeek.top/suivi.php?id=12\n\n📞 Questions ? 05 55 44 33 22\n🏠 MAISON DU GEEK', 1, 11, '{\"success\":true,\"message\":\"SMS ajout\\u00e9 \\u00e0 la queue d\'envoi\",\"data\":{\"message_id\":317,\"phone_id\":\"SM-A035G\",\"sim_id\":352,\"status\":\"pending\",\"sim_info\":{\"carrier_name\":\"Free\",\"phone_number\":\"+33744770932\",\"is_default\":true,\"messages_sent\":19,\"monthly_limit\":30000}},\"attempt\":1,\"http_code\":201,\"response_time\":0.009585}', '2025-11-09 20:56:28'),
(45, '33782962906', '⚠️ GARDIENNAGE guezguez !\n\nVotre devis a expiré.\nGardiennage : 5,00€/jour\n\n📄 Consultez votre devis :\n👉 https://mkmkmk.mdgeek.top/pages/devis_client.php?lien=11820d115068a3f65137bf89ce5c484d\n📲 Suivi réparation :\n👉 https://mkmkmk.mdgeek.top/suivi.php?id=2\n\n📞 Questions ? 05 55 44 33 22\n🏠 MAISON DU GEEK', 0, 5, '{\"success\":false,\"message\":\"Erreur serveur\",\"response\":{\"success\":false,\"message\":\"Erreur serveur\"},\"attempt\":2,\"http_code\":500}', '2025-11-09 21:20:03'),
(46, '33650883462', '⏰ Rappel brissaud !\n\nVotre devis expire dans 13 jours.\n\n📄 Consultez votre devis :\n👉 https://mkmkmk.mdgeek.top/pages/devis_client.php?lien=7709a45308176ecfb64d81db2d39a94a\n📲 Suivi réparation :\n👉 https://mkmkmk.mdgeek.top/suivi.php?id=2140\n\n📞 Questions ? 05 55 44 33 22\n🏠 MAISON DU GEEK', 0, 9, '{\"success\":false,\"message\":\"Erreur serveur\",\"response\":{\"success\":false,\"message\":\"Erreur serveur\"},\"attempt\":2,\"http_code\":500}', '2025-11-09 21:20:04'),
(47, '33782962906', '⏰ Rappel guezguez !\n\nVotre devis expire dans 13 jours.\n\n📄 Consultez votre devis :\n👉 https://mkmkmk.mdgeek.top/pages/devis_client.php?lien=8f27a383f262c623ee44b959feb13c78\n📲 Suivi réparation :\n👉 https://mkmkmk.mdgeek.top/suivi.php?id=12\n\n📞 Questions ? 05 55 44 33 22\n🏠 MAISON DU GEEK', 0, 11, '{\"success\":false,\"message\":\"Erreur serveur\",\"response\":{\"success\":false,\"message\":\"Erreur serveur\"},\"attempt\":2,\"http_code\":500}', '2025-11-09 21:20:05'),
(48, '33782962906', '⏰ Rappel guezguez !\n\nVotre devis expire dans 13 jours.\n\n📄 Consultez votre devis :\n👉 https://mkmkmk.mdgeek.top/pages/devis_client.php?lien=8f27a383f262c623ee44b959feb13c78\n📲 Suivi réparation :\n👉 https://mkmkmk.mdgeek.top/suivi.php?id=12\n\n📞 Questions ? 05 55 44 33 22\n🏠 MAISON DU GEEK', 0, 11, '{\"success\":false,\"message\":\"Erreur serveur\",\"response\":{\"success\":false,\"message\":\"Erreur serveur\"},\"attempt\":2,\"http_code\":500}', '2025-11-09 21:21:20'),
(49, '33782962906', '⚠️ GARDIENNAGE guezguez !\n\nVotre devis a expiré.\nGardiennage : 5,00€/jour\n\n📄 Consultez votre devis :\n👉 https://mkmkmk.mdgeek.top/pages/devis_client.php?lien=11820d115068a3f65137bf89ce5c484d\n📲 Suivi réparation :\n👉 https://mkmkmk.mdgeek.top/suivi.php?id=2\n\n📞 Questions ? 05 55 44 33 22\n🏠 MAISON DU GEEK', 0, 5, '{\"success\":false,\"message\":\"Erreur serveur\",\"response\":{\"success\":false,\"message\":\"Erreur serveur\"},\"attempt\":2,\"http_code\":500}', '2025-11-09 21:54:06'),
(50, '33650883462', '⏰ Rappel brissaud !\n\nVotre devis expire dans 13 jours.\n\n📄 Consultez votre devis :\n👉 https://mkmkmk.mdgeek.top/pages/devis_client.php?lien=7709a45308176ecfb64d81db2d39a94a\n📲 Suivi réparation :\n👉 https://mkmkmk.mdgeek.top/suivi.php?id=2140\n\n📞 Questions ? 05 55 44 33 22\n🏠 MAISON DU GEEK', 0, 9, '{\"success\":false,\"message\":\"Erreur serveur\",\"response\":{\"success\":false,\"message\":\"Erreur serveur\"},\"attempt\":2,\"http_code\":500}', '2025-11-09 21:54:07'),
(51, '33782962906', '⏰ Rappel guezguez !\n\nVotre devis expire dans 13 jours.\n\n📄 Consultez votre devis :\n👉 https://mkmkmk.mdgeek.top/pages/devis_client.php?lien=8f27a383f262c623ee44b959feb13c78\n📲 Suivi réparation :\n👉 https://mkmkmk.mdgeek.top/suivi.php?id=12\n\n📞 Questions ? 05 55 44 33 22\n🏠 MAISON DU GEEK', 0, 11, '{\"success\":false,\"message\":\"Erreur serveur\",\"response\":{\"success\":false,\"message\":\"Erreur serveur\"},\"attempt\":2,\"http_code\":500}', '2025-11-09 21:54:08'),
(52, '33782962906', '⚠️ GARDIENNAGE guezguez !\n\nVotre devis a expiré.\nGardiennage : 5,00€/jour\n\n📄 Consultez votre devis :\n👉 https://mkmkmk.mdgeek.top/pages/devis_client.php?lien=11820d115068a3f65137bf89ce5c484d\n📲 Suivi réparation :\n👉 https://mkmkmk.mdgeek.top/suivi.php?id=2\n\n📞 Questions ? 05 55 44 33 22\n🏠 MAISON DU GEEK', 0, 5, '{\"success\":false,\"message\":\"Erreur serveur\",\"response\":{\"success\":false,\"message\":\"Erreur serveur\"},\"attempt\":2,\"http_code\":500}', '2025-11-09 22:01:03'),
(53, '33650883462', '⏰ Rappel brissaud !\n\nVotre devis expire dans 13 jours.\n\n📄 Consultez votre devis :\n👉 https://mkmkmk.mdgeek.top/pages/devis_client.php?lien=7709a45308176ecfb64d81db2d39a94a\n📲 Suivi réparation :\n👉 https://mkmkmk.mdgeek.top/suivi.php?id=2140\n\n📞 Questions ? 05 55 44 33 22\n🏠 MAISON DU GEEK', 0, 9, '{\"success\":false,\"message\":\"Erreur serveur\",\"response\":{\"success\":false,\"message\":\"Erreur serveur\"},\"attempt\":2,\"http_code\":500}', '2025-11-09 22:01:04'),
(54, '33782962906', '⏰ Rappel guezguez !\n\nVotre devis expire dans 13 jours.\n\n📄 Consultez votre devis :\n👉 https://mkmkmk.mdgeek.top/pages/devis_client.php?lien=8f27a383f262c623ee44b959feb13c78\n📲 Suivi réparation :\n👉 https://mkmkmk.mdgeek.top/suivi.php?id=12\n\n📞 Questions ? 05 55 44 33 22\n🏠 MAISON DU GEEK', 0, 11, '{\"success\":false,\"message\":\"Erreur serveur\",\"response\":{\"success\":false,\"message\":\"Erreur serveur\"},\"attempt\":2,\"http_code\":500}', '2025-11-09 22:01:05'),
(55, '33782962906', '⏰ Rappel guezguez !\n\nVotre devis expire dans 13 jours.\n\n📄 Consultez votre devis :\n👉 https://mkmkmk.mdgeek.top/pages/devis_client.php?lien=8f27a383f262c623ee44b959feb13c78\n📲 Suivi réparation :\n👉 https://mkmkmk.mdgeek.top/suivi.php?id=12\n\n📞 Questions ? 05 55 44 33 22\n🏠 MAISON DU GEEK', 0, 11, '{\"success\":false,\"message\":\"Erreur serveur\",\"response\":{\"success\":false,\"message\":\"Erreur serveur\"},\"attempt\":2,\"http_code\":500}', '2025-11-09 22:06:40'),
(56, '33123456789', '⏰ Rappel dsa !\n\nVotre devis expire dans 13 jours.\n\n📄 Consultez votre devis :\n👉 https://mkmkmk.mdgeek.top/pages/devis_client.php?lien=f62b5a9d5fae0f455be46b6e2df2e69f\n📲 Suivi réparation :\n👉 https://mkmkmk.mdgeek.top/suivi.php?id=13\n\n📞 Questions ? 05 55 44 33 22\n🏠 MAISON DU GEEK', 1, 10, '{\"success\":true,\"message\":\"SMS ajout\\u00e9 \\u00e0 la queue d\'envoi\",\"data\":{\"message_id\":352,\"phone_id\":\"SM-A805F\",\"sim_id\":377,\"status\":\"pending\",\"sim_info\":{\"carrier_name\":\"Free\",\"phone_number\":\"+33745520054\",\"is_default\":true,\"messages_sent\":5,\"monthly_limit\":30000}},\"attempt\":1,\"http_code\":201,\"response_time\":0.01092}', '2025-11-09 22:16:15'),
(57, '33782962906', '⚠️ GARDIENNAGE guezguez !\n\nVotre devis a expiré.\nGardiennage : 5,00€/jour\n\n📄 Consultez votre devis :\n👉 https://mkmkmk.mdgeek.top/pages/devis_client.php?lien=11820d115068a3f65137bf89ce5c484d\n📲 Suivi réparation :\n👉 https://mkmkmk.mdgeek.top/suivi.php?id=2\n\n📞 Questions ? 05 55 44 33 22\n🏠 MAISON DU GEEK', 0, 5, '{\"success\":false,\"message\":\"Erreur serveur\",\"response\":{\"success\":false,\"message\":\"Erreur serveur\"},\"attempt\":2,\"http_code\":500}', '2025-11-09 22:16:22'),
(58, '33650883462', '⏰ Rappel brissaud !\n\nVotre devis expire dans 13 jours.\n\n📄 Consultez votre devis :\n👉 https://mkmkmk.mdgeek.top/pages/devis_client.php?lien=7709a45308176ecfb64d81db2d39a94a\n📲 Suivi réparation :\n👉 https://mkmkmk.mdgeek.top/suivi.php?id=2140\n\n📞 Questions ? 05 55 44 33 22\n🏠 MAISON DU GEEK', 0, 9, '{\"success\":false,\"message\":\"Erreur serveur\",\"response\":{\"success\":false,\"message\":\"Erreur serveur\"},\"attempt\":2,\"http_code\":500}', '2025-11-09 22:16:23'),
(59, '33782962906', '⏰ Rappel guezguez !\n\nVotre devis expire dans 13 jours.\n\n📄 Consultez votre devis :\n👉 https://mkmkmk.mdgeek.top/pages/devis_client.php?lien=8f27a383f262c623ee44b959feb13c78\n📲 Suivi réparation :\n👉 https://mkmkmk.mdgeek.top/suivi.php?id=12\n\n📞 Questions ? 05 55 44 33 22\n🏠 MAISON DU GEEK', 0, 11, '{\"success\":false,\"message\":\"Erreur serveur\",\"response\":{\"success\":false,\"message\":\"Erreur serveur\"},\"attempt\":2,\"http_code\":500}', '2025-11-09 22:16:24');

-- --------------------------------------------------------

--
-- Structure de la table `sms_template`
--

CREATE TABLE `sms_template` (
  `id` int NOT NULL,
  `statut_id` int DEFAULT NULL,
  `message` text COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `sms_templates`
--

CREATE TABLE `sms_templates` (
  `id` int NOT NULL,
  `nom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contenu` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `statut_id` int DEFAULT NULL,
  `est_actif` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `variables` text COLLATE utf8mb4_unicode_ci,
  `type` enum('devis','relance','notification','autre') COLLATE utf8mb4_unicode_ci DEFAULT 'autre'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `sms_templates`
--

INSERT INTO `sms_templates` (`id`, `nom`, `contenu`, `statut_id`, `est_actif`, `created_at`, `updated_at`, `code`, `variables`, `type`) VALUES
(1, 'Réparation en cours', 'Votre [APPAREIL_MODELE] est entre de bonnes mains ! Nos tournevis chauffent, les pixels tremblent. 🧑‍🔧\n🔍 Suivi : [URL_SUIVI]\n[COMPANY_NAME] – [COMPANY_PHONE]', 5, 1, '2025-04-09 23:53:13', '2025-11-03 22:30:25', NULL, NULL, 'autre'),
(2, 'Réparation terminée', '[CLIENT_PRENOM], votre [APPAREIL_MODELE] est prêt ! Il vous attend pour retrouver une vie normale. 🥳\n🧾 Suivi : [URL_SUIVI]\n[COMPANY_NAME] – [COMPANY_PHONE]', 9, 1, '2025-04-09 23:53:13', '2025-11-03 22:30:25', NULL, NULL, 'autre'),
(3, 'En attente de pièces', '📦 En attente de pièces\nVotre [APPAREIL_MODELE] attend sa livraison. Le livreur est en route à dos de modem. ⏳\n🚚 Suivi : [URL_SUIVI]\n[COMPANY_NAME] – [COMPANY_PHONE]', 7, 1, '2025-04-09 23:53:13', '2025-09-23 23:46:42', NULL, NULL, 'autre'),
(4, 'En attente de validation', 'Bonjour, [CLIENT_PRENOM], \nLe devis de votre [APPAREIL_MODELE] est disponible. \nMontant : [PRIX]\n📄 Consultez votre devis ici :\n👉 [URL_DEVIS]\n📲 Suivi réparation :\n👉 [URL_SUIVI]\nUne question ? Appelez-nous au [COMPANY_PHONE]\n[COMPANY_NAME]', 6, 1, '2025-04-09 23:53:13', '2025-09-23 23:46:42', NULL, NULL, 'autre'),
(5, 'Nouvelle reparation', '👋 Bonjour [CLIENT_PRENOM],\n🛠️ Nous avons bien reçu votre [APPAREIL_MODELE] et nos experts geeks sont déjà à l\'œuvre pour le remettre en état.\n🔎 Suivez l\'avancement de la réparation ici :\n👉 [URL_SUIVI]\n📞 Une question ? Contactez nous au [COMPANY_PHONE]\n🏠 [COMPANY_NAME] 🛠️', 1, 1, '2025-04-09 23:53:27', '2025-11-03 22:30:25', NULL, NULL, 'autre'),
(6, 'Nouvelle Intervention', '👋 Bonjour [CLIENT_PRENOM],\n🛠️ Nous avons bien reçu votre [APPAREIL_MODELE] et nos experts geeks sont déjà à l\'œuvre pour le remettre en état.\n🔎 Suivez l\'avancement de la réparation ici :\n👉 [URL_SUIVI]\n💶 [PRIX]\n📞 Une question ? Contactez nous au [COMPANY_PHONE]\n🏠 [COMPANY_NAME] 🛠️', 2, 1, '2025-04-10 02:15:50', '2025-09-23 23:46:42', NULL, NULL, 'autre'),
(7, 'Nouvelle Commande', '  ', 3, 1, '2025-04-10 02:15:55', '2025-06-20 23:18:48', NULL, NULL, 'autre'),
(8, 'En cours de diagnostique', 'Bonjour [CLIENT], votre devis de réparation pour [APPAREIL] d\'un montant de [PRIX] est prêt. Vous pouvez l\'accepter ou le refuser via ce lien: [LIEN_SUIVI]', 4, 1, '2025-04-10 02:16:00', '2025-06-21 22:54:03', NULL, NULL, 'autre'),
(9, 'En attente d\'un responsable', 'Bonjour [CLIENT_PRENOM], votre dossier [REPARATION_ID] au sujet de votre [APPAREIL_MODELE] est en attente de validation par un responsable technique. Nous vous tenons informé très bientôt.\n📲 Suivi : [URL_SUIVI]\n[COMPANY_NAME] – [COMPANY_PHONE]', 8, 1, '2025-04-10 02:16:06', '2025-09-23 23:46:42', NULL, NULL, 'autre'),
(10, 'Réparation Annulée', 'Nous avons tout essayé pour sauver votre [APPAREIL_MODELE] ([APPAREIL_MARQUE]), mais pour des raisons techniques, nous avons dû annuler la réparation.\n📄 Détails : [URL_SUIVI]\n[COMPANY_NAME] – [COMPANY_PHONE]', 10, 1, '2025-04-10 02:16:12', '2025-11-03 22:30:25', NULL, NULL, 'autre'),
(11, 'Restitué', '🎉 [CLIENT_PRENOM],\nTon [APPAREIL_MODELE] est de retour à la maison ! On espère qu\'il est content 🤓\n💬 Laisse-nous un petit avis !\n⭐ https://g.page/r/Ce-HHwKZjezIEB0/review\n🏠 [COMPANY_NAME]\n📞 [COMPANY_PHONE]\n', 11, 1, '2025-04-10 02:16:18', '2025-09-23 23:46:42', NULL, NULL, 'autre'),
(12, 'Gardiennage', 'Bonjour [CLIENT_PRENOM] ! 👋\n\nVotre [APPAREIL_MODELE] vous attend depuis plusieurs jours! ⏰ \n\n⚠️ Important à savoir :\n• Frais de gardiennage : 5€/jour dès aujourd\'hui\n• Recyclage GRATUIT après 90 jours si non récupéré ♻️\n📍 [COMPANY_NAME] - Le Cannet\n📞 [COMPANY_PHONE]\n📄 [URL_SUIVI]', 12, 1, '2025-04-10 02:16:24', '2025-11-03 22:30:25', NULL, NULL, 'autre'),
(13, 'Annulé', '😔 [CLIENT_PRENOM],\nOn a tout tenté pour réparer ton [APPAREIL_MODELE], mais pour raisons techniques, on a dû annuler la réparation.\n🔍 Détails : [URL_SUIVI]\n📞 Une Question ?  [COMPANY_PHONE]\n🏠 [COMPANY_NAME]', 13, 1, '2025-04-10 02:16:29', '2025-11-03 22:30:25', NULL, NULL, 'autre'),
(15, 'Terminé', '[CLIENT_PRENOM], on espère que ton [APPAREIL_MODELE] se porte comme un charme ! 😊 Aide nos Geeks avec un petit avis :\n⭐ https://g.page/r/Ce-HHwKZjezIEB0/review\n📲 Suivi : [URL_SUIVI]\n[COMPANY_NAME] – [COMPANY_PHONE]', 15, 1, '2025-04-10 02:16:51', '2025-11-03 22:30:25', NULL, NULL, 'autre'),
(17, 'Relance client', 'Bonjour [CLIENT_PRENOM],\nVotre [APPAREIL_TYPE] [APPAREIL_MARQUE] [APPAREIL_MODELE] est réparé et attend votre visite à la boutique.\n[COMPANY_NAME] - [COMPANY_PHONE]', NULL, 1, '2025-04-23 00:14:29', '2025-09-23 23:46:42', NULL, NULL, 'autre'),
(18, 'Relance Devis', '⏰ Rappel [CLIENT_PRENOM] !\n\nVotre [APPAREIL_MODELE] attend votre décision.\n📄 Consultez votre devis :\n👉 [URL_DEVIS]\n📲 Suivi réparation :\n👉 [URL_SUIVI]\n\n⚠️ IMPORTANT : Sans réponse sous 7 jours = gardiennage.\n\n📞 Questions ? [COMPANY_PHONE]\n🏠 [COMPANY_NAME]', NULL, 1, '2025-06-25 23:56:35', '2025-09-23 23:46:42', NULL, NULL, 'autre'),
(19, 'Devis en attente - Rappel', '⏰ Rappel [CLIENT_PRENOM] !\n\nVotre devis expire dans [JOURS_RESTANTS] jours.\n\n📄 Consultez votre devis :\n👉 [URL_DEVIS]\n📲 Suivi réparation :\n👉 [URL_SUIVI]\n\n📞 Questions ? [COMPANY_PHONE]\n🏠 [COMPANY_NAME]', NULL, 1, '2025-07-28 21:17:56', '2025-09-23 23:46:42', 'devis_rappel', 'client_nom,devis_numero,jours_restants,lien_devis', 'devis'),
(20, 'Devis expiré - Gardiennage', '⚠️ GARDIENNAGE [CLIENT_PRENOM] !\n\nVotre devis a expiré.\nGardiennage : [PRIX_GARDIENNAGE]€/jour\n\n📄 Consultez votre devis :\n👉 [URL_DEVIS]\n📲 Suivi réparation :\n👉 [URL_SUIVI]\n\n📞 Questions ? [COMPANY_PHONE]\n🏠 [COMPANY_NAME]', NULL, 1, '2025-07-28 21:17:56', '2025-09-23 23:46:42', 'devis_expire_gardiennage', 'client_nom,devis_numero,prix_gardiennage,lien_devis', 'devis'),
(21, 'Devis - Relance automatique', '⏰ Relance [CLIENT_PRENOM] !\n\nVotre devis expire dans [JOURS_RESTANTS] jour(s).\nMontant : [MONTANT]\n\n📄 Consultez votre devis :\n👉 [URL_DEVIS]\n📲 Suivi réparation :\n👉 [URL_SUIVI]\n\n📞 Questions ? [COMPANY_PHONE]\n🏠 [COMPANY_NAME]', NULL, 1, '2025-07-28 21:17:56', '2025-09-23 23:46:42', 'devis_relance_auto', 'client_nom,devis_numero,montant,lien_devis', 'devis'),
(22, 'Retard Livraison', '[COMPANY_NAME]\nEn raison d\'un problème de livraison, votre réparation #[REPARATION_ID] aura un léger retard (≈24h).\nVous pouvez suivre l\'avancée de votre réparation via le lien ci-dessous : [LIEN]\nNous vous enverrons un SMS dès que votre [APPAREIL_TYPE] sera prêt.\nVeuillez nous excuser pour la gêne occasionnée.\n\nCordialement,\n[COMPANY_NAME]', 21, 1, '2025-09-15 11:08:42', '2025-09-23 23:46:42', 'retard_livraison', '[CLIENT_NOM],[CLIENT_PRENOM],[REPARATION_ID],[APPAREIL_TYPE],[APPAREIL_MARQUE],[APPAREIL_MODELE],[LIEN],[DATE_RECEPTION],[DATE_FIN_PREVUE]', 'notification'),
(23, 'Relance Devis Expiré', '⏰ Devis expiré [CLIENT_PRENOM] !\n\nVotre devis a expiré il y a [JOURS_EXPIRES] jour(s) mais reste valable.\nMontant : [MONTANT]\n\n📄 Vous pouvez encore l\'accepter :\n👉 [URL_DEVIS]\n📲 Suivi réparation :\n👉 [URL_SUIVI]\n\n📞 Questions ? [COMPANY_PHONE]\n🏠 [COMPANY_NAME]', NULL, 1, '2025-09-15 16:21:00', '2025-09-23 23:46:42', 'devis_relance_expire', NULL, 'devis');

-- --------------------------------------------------------

--
-- Structure de la table `sms_template_variables`
--

CREATE TABLE `sms_template_variables` (
  `id` int NOT NULL,
  `nom` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `exemple` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `sms_template_variables`
--

INSERT INTO `sms_template_variables` (`id`, `nom`, `description`, `exemple`) VALUES
(1, 'CLIENT_NOM', 'Nom du client', 'Dupont'),
(2, 'CLIENT_PRENOM', 'Prénom du client', 'Jean'),
(3, 'CLIENT_TELEPHONE', 'Numéro de téléphone du client', '+33612345678'),
(4, 'REPARATION_ID', 'Numéro de la réparation', '1234'),
(5, 'APPAREIL_TYPE', 'Type d\'appareil', 'Téléphone'),
(6, 'APPAREIL_MARQUE', 'Marque de l\'appareil', 'Samsung'),
(7, 'APPAREIL_MODELE', 'Modèle de l\'appareil', 'Galaxy S21'),
(8, 'DATE_RECEPTION', 'Date de réception de l\'appareil', '01/01/2023'),
(9, 'DATE_FIN_PREVUE', 'Date de fin prévue', '15/01/2023'),
(10, 'PRIX', 'Prix de la réparation', '59.90€'),
(11, 'NOTES_TECHNIQUES', 'Notes techniques de la réparation', 'Remplacement écran tactile - Test effectué avec succès'),
(12, 'COMPANY_NAME', 'Nom du magasin', 'MKMKMK'),
(13, 'COMPANY_PHONE', 'Téléphone du magasin', '04 93 68 66 30'),
(14, 'URL_SUIVI', 'Lien de suivi de réparation', 'https://mkmkmk.servo.tools/suivi.php?id=1234'),
(15, 'URL_DEVIS', 'Lien vers le devis', 'https://mkmkmk.servo.tools/devis.php?id=1234'),
(16, 'COMPANY_NUMBER', 'Numéro SIRET/SIREN de l\'entreprise', '12345678901234'),
(17, 'COMPANY_HOURS', 'Horaires d\'ouverture', 'Lun-Ven: 9h-18h');

-- --------------------------------------------------------

--
-- Structure de la table `soldes_partenaires`
--

CREATE TABLE `soldes_partenaires` (
  `partenaire_id` int NOT NULL,
  `solde_actuel` decimal(10,2) DEFAULT '0.00',
  `derniere_mise_a_jour` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `soldes_partenaires`
--

INSERT INTO `soldes_partenaires` (`partenaire_id`, `solde_actuel`, `derniere_mise_a_jour`) VALUES
(1, 6.80, '2025-09-28 02:46:58');

-- --------------------------------------------------------

--
-- Structure de la table `statuts`
--

CREATE TABLE `statuts` (
  `id` int NOT NULL,
  `nom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `categorie_id` int NOT NULL,
  `est_actif` tinyint(1) NOT NULL DEFAULT '1',
  `ordre` int NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `statuts`
--

INSERT INTO `statuts` (`id`, `nom`, `code`, `categorie_id`, `est_actif`, `ordre`) VALUES
(1, 'Nouveau Diagnostique', 'nouveau_diagnostique', 1, 1, 1),
(2, 'Nouvelle Intervention', 'nouvelle_intervention', 1, 1, 2),
(3, 'Nouvelle Commande', 'nouvelle_commande', 1, 1, 3),
(4, 'En cours de diagnostique', 'en_cours_diagnostique', 2, 1, 1),
(5, 'En cours d\'intervention', 'en_cours_intervention', 2, 1, 2),
(6, 'En attente de l\'accord client', 'en_attente_accord_client', 3, 1, 1),
(7, 'En attente de livraison', 'en_attente_livraison', 3, 1, 2),
(8, 'En attente d\'un responsable', 'en_attente_responsable', 3, 1, 3),
(9, 'Réparation Effectuée', 'reparation_effectue', 4, 1, 1),
(10, 'Réparation Annulée', 'reparation_annule', 4, 1, 2),
(11, 'Restitué', 'restitue', 5, 1, 1),
(12, 'Gardiennage', 'gardiennage', 5, 1, 2),
(13, 'Annulé', 'annule', 5, 1, 3),
(14, 'Archivé', 'archive', 6, 1, 1),
(15, 'Terminé', 'termine', 3, 1, 0),
(19, 'Devis accepté', 'devis_accepte', 1, 1, 4),
(20, 'Devis refusé', 'devis_refuse', 1, 1, 4),
(21, 'Retard de livraison', 'retard_livraison', 3, 1, 4);

-- --------------------------------------------------------

--
-- Structure de la table `statuts_reparation`
--

CREATE TABLE `statuts_reparation` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `categorie` enum('nouvelle','en_cours','en_attente','termine','annule') COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `statut_categories`
--

CREATE TABLE `statut_categories` (
  `id` int NOT NULL,
  `nom` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `couleur` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ordre` int NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `statut_categories`
--

INSERT INTO `statut_categories` (`id`, `nom`, `code`, `couleur`, `ordre`) VALUES
(1, 'Nouvelle', 'nouvelle', 'info', 1),
(2, 'En cours', 'en_cours', 'primary', 2),
(3, 'En attente', 'en_attente', 'warning', 3),
(4, 'Terminé', 'termine', 'success', 4),
(5, 'Annulé', 'annule', 'danger', 5),
(6, 'Archivé', 'archive', 'secondary', 6);

-- --------------------------------------------------------

--
-- Structure de la table `stock`
--

CREATE TABLE `stock` (
  `id` int NOT NULL,
  `barcode` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `category` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `quantity` int NOT NULL DEFAULT '0',
  `price` decimal(10,2) DEFAULT '0.00',
  `description` text COLLATE utf8mb4_general_ci,
  `date_created` datetime NOT NULL,
  `date_updated` datetime DEFAULT NULL,
  `status` enum('normal','temporaire','a_retourner') COLLATE utf8mb4_general_ci DEFAULT 'normal',
  `date_limite_retour` date DEFAULT NULL,
  `motif_retour` text COLLATE utf8mb4_general_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `stock_history`
--

CREATE TABLE `stock_history` (
  `id` int NOT NULL,
  `product_id` int NOT NULL,
  `action` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `quantity` int NOT NULL,
  `note` text COLLATE utf8mb4_general_ci,
  `user_id` int DEFAULT NULL,
  `date_created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `taches`
--

CREATE TABLE `taches` (
  `id` int NOT NULL,
  `titre` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `priorite` enum('basse','moyenne','haute','urgente') COLLATE utf8mb4_unicode_ci DEFAULT 'moyenne',
  `statut` enum('a_faire','en_cours','termine','annule') COLLATE utf8mb4_unicode_ci DEFAULT 'a_faire',
  `date_creation` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `date_limite` date DEFAULT NULL,
  `date_fin` timestamp NULL DEFAULT NULL,
  `employe_id` int DEFAULT NULL,
  `created_by` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `taches`
--

INSERT INTO `taches` (`id`, `titre`, `description`, `priorite`, `statut`, `date_creation`, `date_limite`, `date_fin`, `employe_id`, `created_by`) VALUES
(1, 'test', 'test', 'moyenne', 'termine', '2025-09-23 17:08:27', '2022-02-02', NULL, NULL, 6),
(2, 'Renvoyer colis', 'Renvoyer les colis qui sont dans le tiroir', 'moyenne', 'termine', '2025-09-28 22:52:32', '2025-10-10', NULL, 6, 6),
(3, 'adsads', 'das', 'moyenne', 'termine', '2025-10-09 22:22:52', NULL, NULL, NULL, 6),
(4, 'sad', 'sad', 'moyenne', 'termine', '2025-10-19 23:10:31', NULL, NULL, NULL, NULL),
(6, 'asd', 'ads', 'moyenne', 'termine', '2025-11-05 22:32:10', NULL, NULL, NULL, 6),
(7, 'TEST', 'JKADSH', 'moyenne', 'termine', '2025-11-05 22:33:23', NULL, NULL, 6, 6),
(12, 'test', 'test', 'moyenne', 'a_faire', '2025-11-08 21:36:51', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `tache_attachments`
--

CREATE TABLE `tache_attachments` (
  `id` int NOT NULL,
  `tache_id` int NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_type` varchar(100) NOT NULL,
  `file_size` int NOT NULL,
  `thumbnail_path` varchar(255) DEFAULT NULL,
  `est_image` tinyint(1) DEFAULT '0',
  `date_upload` datetime DEFAULT CURRENT_TIMESTAMP,
  `uploaded_by` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `tache_attachments`
--

INSERT INTO `tache_attachments` (`id`, `tache_id`, `file_path`, `file_name`, `file_type`, `file_size`, `thumbnail_path`, `est_image`, `date_upload`, `uploaded_by`) VALUES
(1, 2, 'uploads/taches/2/68d9bc30ac221_20250904_attestation_de_compte.pdf', '20250904_attestation_de_compte.pdf', 'pdf', 18573, NULL, 0, '2025-09-29 00:52:32', 6);

-- --------------------------------------------------------

--
-- Structure de la table `tasks`
--

CREATE TABLE `tasks` (
  `id` int NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `status` enum('en_attente','en_cours','termine','aide_necessaire') COLLATE utf8mb4_unicode_ci DEFAULT 'en_attente',
  `priority` enum('basse','moyenne','haute','urgente') COLLATE utf8mb4_unicode_ci DEFAULT 'moyenne',
  `assigned_to` int DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `due_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `task_logs`
--

CREATE TABLE `task_logs` (
  `id` int NOT NULL,
  `tache_id` int NOT NULL,
  `employe_id` int NOT NULL,
  `action_type` enum('demarrage','terminer','changement_statut','ajout_note','modification','creation','autre') COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_action` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `statut_avant` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `statut_apres` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `details` text COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `task_logs`
--

INSERT INTO `task_logs` (`id`, `tache_id`, `employe_id`, `action_type`, `date_action`, `statut_avant`, `statut_apres`, `details`) VALUES
(3, 1, 6, 'demarrage', '2025-11-05 23:36:50', 'a_faire', 'en_cours', 'Test de démarrage avec IDs existants');

-- --------------------------------------------------------

--
-- Structure de la table `time_slots`
--

CREATE TABLE `time_slots` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `slot_type` enum('morning','afternoon') NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `time_slots`
--

INSERT INTO `time_slots` (`id`, `user_id`, `slot_type`, `start_time`, `end_time`, `is_active`, `created_at`, `updated_at`) VALUES
(5802, 12, 'morning', '01:01:00', '22:22:00', 1, '2025-11-06 02:50:07', '2025-11-06 02:50:07'),
(5803, 12, 'afternoon', '03:33:00', '03:44:00', 1, '2025-11-06 02:50:07', '2025-11-06 02:50:07'),
(5806, NULL, 'morning', '08:00:00', '12:30:00', 1, '2025-11-06 02:52:06', '2025-11-06 02:52:06'),
(5807, NULL, 'afternoon', '14:00:00', '19:01:00', 1, '2025-11-06 02:52:06', '2025-11-06 02:52:06'),
(5808, NULL, 'morning', '08:00:00', '12:30:00', 1, '2025-11-09 00:22:10', '2025-11-09 00:22:10'),
(5809, NULL, 'afternoon', '14:00:00', '19:00:00', 1, '2025-11-09 00:22:10', '2025-11-09 00:22:10'),
(5810, NULL, 'morning', '08:00:00', '12:30:00', 1, '2025-11-09 00:22:10', '2025-11-09 00:22:10'),
(5811, NULL, 'afternoon', '14:00:00', '19:00:00', 1, '2025-11-09 00:22:10', '2025-11-09 00:22:10'),
(5812, NULL, 'morning', '08:00:00', '12:30:00', 1, '2025-11-09 00:22:10', '2025-11-09 00:22:10'),
(5813, NULL, 'afternoon', '14:00:00', '19:00:00', 1, '2025-11-09 00:22:10', '2025-11-09 00:22:10'),
(5814, NULL, 'morning', '08:00:00', '12:30:00', 1, '2025-11-09 00:33:27', '2025-11-09 00:33:27'),
(5815, NULL, 'afternoon', '14:00:00', '19:00:00', 1, '2025-11-09 00:33:27', '2025-11-09 00:33:27'),
(5816, NULL, 'morning', '08:00:00', '12:30:00', 1, '2025-11-09 00:33:27', '2025-11-09 00:33:27'),
(5817, NULL, 'afternoon', '14:00:00', '19:00:00', 1, '2025-11-09 00:33:27', '2025-11-09 00:33:27'),
(5818, NULL, 'morning', '08:00:00', '12:30:00', 1, '2025-11-09 00:33:27', '2025-11-09 00:33:27'),
(5819, NULL, 'afternoon', '14:00:00', '19:00:00', 1, '2025-11-09 00:33:27', '2025-11-09 00:33:27'),
(5820, NULL, 'morning', '08:00:00', '12:30:00', 1, '2025-11-09 18:39:41', '2025-11-09 18:39:41'),
(5821, NULL, 'afternoon', '14:00:00', '19:00:00', 1, '2025-11-09 18:39:41', '2025-11-09 18:39:41'),
(5822, NULL, 'morning', '08:00:00', '12:30:00', 1, '2025-11-09 18:39:41', '2025-11-09 18:39:41'),
(5823, NULL, 'afternoon', '14:00:00', '19:00:00', 1, '2025-11-09 18:39:41', '2025-11-09 18:39:41'),
(5824, NULL, 'morning', '08:00:00', '12:30:00', 1, '2025-11-09 18:39:41', '2025-11-09 18:39:41'),
(5825, NULL, 'afternoon', '14:00:00', '19:00:00', 1, '2025-11-09 18:39:41', '2025-11-09 18:39:41'),
(5826, NULL, 'morning', '08:00:00', '12:30:00', 1, '2025-11-09 23:41:05', '2025-11-09 23:41:05'),
(5827, NULL, 'afternoon', '14:00:00', '19:00:00', 1, '2025-11-09 23:41:05', '2025-11-09 23:41:05'),
(5828, NULL, 'morning', '08:00:00', '12:30:00', 1, '2025-11-09 23:41:05', '2025-11-09 23:41:05'),
(5829, NULL, 'afternoon', '14:00:00', '19:00:00', 1, '2025-11-09 23:41:05', '2025-11-09 23:41:05'),
(5830, NULL, 'morning', '08:00:00', '12:30:00', 1, '2025-11-09 23:41:05', '2025-11-09 23:41:05'),
(5831, NULL, 'afternoon', '14:00:00', '19:00:00', 1, '2025-11-09 23:41:05', '2025-11-09 23:41:05'),
(5832, NULL, 'morning', '08:00:00', '12:30:00', 1, '2025-11-09 23:41:09', '2025-11-09 23:41:09'),
(5833, NULL, 'afternoon', '14:00:00', '19:00:00', 1, '2025-11-09 23:41:09', '2025-11-09 23:41:09'),
(5834, NULL, 'morning', '08:00:00', '12:30:00', 1, '2025-11-09 23:41:09', '2025-11-09 23:41:09'),
(5835, NULL, 'afternoon', '14:00:00', '19:00:00', 1, '2025-11-09 23:41:09', '2025-11-09 23:41:09'),
(5836, NULL, 'morning', '08:00:00', '12:30:00', 1, '2025-11-09 23:41:09', '2025-11-09 23:41:09'),
(5837, NULL, 'afternoon', '14:00:00', '19:00:00', 1, '2025-11-09 23:41:09', '2025-11-09 23:41:09'),
(5838, NULL, 'morning', '08:00:00', '12:30:00', 1, '2025-11-09 23:42:32', '2025-11-09 23:42:32'),
(5839, NULL, 'afternoon', '14:00:00', '19:00:00', 1, '2025-11-09 23:42:32', '2025-11-09 23:42:32'),
(5840, NULL, 'morning', '08:00:00', '12:30:00', 1, '2025-11-09 23:42:32', '2025-11-09 23:42:32'),
(5841, NULL, 'afternoon', '14:00:00', '19:00:00', 1, '2025-11-09 23:42:32', '2025-11-09 23:42:32'),
(5842, NULL, 'morning', '08:00:00', '12:30:00', 1, '2025-11-09 23:42:32', '2025-11-09 23:42:32'),
(5843, NULL, 'afternoon', '14:00:00', '19:00:00', 1, '2025-11-09 23:42:32', '2025-11-09 23:42:32'),
(5844, NULL, 'morning', '08:00:00', '12:30:00', 1, '2025-11-09 23:42:32', '2025-11-09 23:42:32'),
(5845, NULL, 'afternoon', '14:00:00', '19:00:00', 1, '2025-11-09 23:42:32', '2025-11-09 23:42:32'),
(5846, NULL, 'morning', '08:00:00', '12:30:00', 1, '2025-11-09 23:46:58', '2025-11-09 23:46:58'),
(5847, NULL, 'afternoon', '14:00:00', '19:00:00', 1, '2025-11-09 23:46:58', '2025-11-09 23:46:58'),
(5848, NULL, 'morning', '08:00:00', '12:30:00', 1, '2025-11-09 23:57:06', '2025-11-09 23:57:06'),
(5849, NULL, 'afternoon', '14:00:00', '19:00:00', 1, '2025-11-09 23:57:06', '2025-11-09 23:57:06'),
(5850, NULL, 'morning', '08:00:00', '12:30:00', 1, '2025-11-09 23:57:06', '2025-11-09 23:57:06'),
(5851, NULL, 'afternoon', '14:00:00', '19:00:00', 1, '2025-11-09 23:57:06', '2025-11-09 23:57:06'),
(5852, NULL, 'morning', '08:00:00', '12:30:00', 1, '2025-11-09 23:57:06', '2025-11-09 23:57:06'),
(5853, NULL, 'afternoon', '14:00:00', '19:00:00', 1, '2025-11-09 23:57:06', '2025-11-09 23:57:06');

-- --------------------------------------------------------

--
-- Structure de la table `time_tracking`
--

CREATE TABLE `time_tracking` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `clock_in` datetime NOT NULL,
  `clock_out` datetime DEFAULT NULL,
  `break_start` datetime DEFAULT NULL,
  `break_end` datetime DEFAULT NULL,
  `total_hours` decimal(5,2) DEFAULT NULL,
  `break_duration` decimal(5,2) DEFAULT '0.00',
  `work_duration` decimal(5,2) DEFAULT NULL,
  `status` enum('active','completed','break') DEFAULT 'active',
  `location` varchar(255) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `notes` text,
  `admin_approved` tinyint(1) DEFAULT '0',
  `auto_approved` tinyint(1) DEFAULT '0',
  `approval_reason` varchar(255) DEFAULT NULL,
  `admin_notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `location_in` text,
  `location_out` text,
  `latitude_in` decimal(10,8) DEFAULT NULL COMMENT 'Latitude GPS arrivée',
  `longitude_in` decimal(11,8) DEFAULT NULL COMMENT 'Longitude GPS arrivée',
  `latitude_out` decimal(10,8) DEFAULT NULL COMMENT 'Latitude GPS départ',
  `longitude_out` decimal(11,8) DEFAULT NULL COMMENT 'Longitude GPS départ',
  `gps_accuracy_in` float DEFAULT NULL COMMENT 'Précision GPS arrivée (mètres)',
  `gps_accuracy_out` float DEFAULT NULL COMMENT 'Précision GPS départ (mètres)',
  `altitude_in` float DEFAULT NULL COMMENT 'Altitude GPS arrivée',
  `altitude_out` float DEFAULT NULL COMMENT 'Altitude GPS départ',
  `device_fingerprint` text COMMENT 'Empreinte digitale de l''appareil',
  `screen_resolution` varchar(20) DEFAULT NULL COMMENT 'Résolution écran',
  `browser_language` varchar(10) DEFAULT NULL COMMENT 'Langue du navigateur',
  `timezone_offset` int DEFAULT NULL COMMENT 'Décalage horaire en minutes',
  `platform` varchar(50) DEFAULT NULL COMMENT 'Plateforme système',
  `cpu_cores` int DEFAULT NULL COMMENT 'Nombre de cœurs CPU',
  `memory_gb` float DEFAULT NULL COMMENT 'Mémoire RAM en GB',
  `connection_type` varchar(20) DEFAULT NULL COMMENT 'Type de connexion',
  `connection_speed` varchar(20) DEFAULT NULL COMMENT 'Vitesse de connexion',
  `ip_v6` varchar(45) DEFAULT NULL COMMENT 'Adresse IPv6',
  `battery_level` float DEFAULT NULL COMMENT 'Niveau de batterie',
  `is_charging` tinyint(1) DEFAULT NULL COMMENT 'Appareil en charge',
  `device_orientation` varchar(20) DEFAULT NULL COMMENT 'Orientation de l''appareil',
  `canvas_fingerprint` varchar(255) DEFAULT NULL COMMENT 'Empreinte Canvas',
  `webgl_fingerprint` varchar(255) DEFAULT NULL COMMENT 'Empreinte WebGL',
  `audio_fingerprint` varchar(255) DEFAULT NULL COMMENT 'Empreinte Audio',
  `client_timestamp` timestamp NULL DEFAULT NULL COMMENT 'Horodatage côté client',
  `server_timestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Horodatage côté serveur',
  `processing_time_ms` int DEFAULT NULL COMMENT 'Temps de traitement en millisecondes',
  `is_vpn_proxy` tinyint(1) DEFAULT NULL COMMENT 'Détection VPN/Proxy',
  `isp_name` varchar(100) DEFAULT NULL COMMENT 'Nom du fournisseur internet',
  `country_code` varchar(3) DEFAULT NULL COMMENT 'Code pays',
  `city_name` varchar(100) DEFAULT NULL COMMENT 'Nom de la ville',
  `user_agent` text,
  `wifi_ssid` varchar(255) DEFAULT NULL,
  `qr_code_used` tinyint(1) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `time_tracking`
--

INSERT INTO `time_tracking` (`id`, `user_id`, `clock_in`, `clock_out`, `break_start`, `break_end`, `total_hours`, `break_duration`, `work_duration`, `status`, `location`, `ip_address`, `notes`, `admin_approved`, `auto_approved`, `approval_reason`, `admin_notes`, `created_at`, `updated_at`, `location_in`, `location_out`, `latitude_in`, `longitude_in`, `latitude_out`, `longitude_out`, `gps_accuracy_in`, `gps_accuracy_out`, `altitude_in`, `altitude_out`, `device_fingerprint`, `screen_resolution`, `browser_language`, `timezone_offset`, `platform`, `cpu_cores`, `memory_gb`, `connection_type`, `connection_speed`, `ip_v6`, `battery_level`, `is_charging`, `device_orientation`, `canvas_fingerprint`, `webgl_fingerprint`, `audio_fingerprint`, `client_timestamp`, `server_timestamp`, `processing_time_ms`, `is_vpn_proxy`, `isp_name`, `country_code`, `city_name`, `user_agent`, `wifi_ssid`, `qr_code_used`) VALUES
(15, 6, '2025-09-09 14:57:24', '2025-09-09 14:57:30', NULL, NULL, NULL, 0.00, 0.00, 'completed', NULL, '161.142.153.142', NULL, 1, 1, NULL, NULL, '2025-09-09 12:57:24', '2025-09-09 12:57:30', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-09 12:57:24', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(19, 6, '2025-09-09 17:00:48', '2025-09-09 17:00:57', NULL, NULL, 0.00, 0.00, 0.00, 'completed', NULL, '161.142.153.142', NULL, 1, 1, 'Pointage dans créneau global (14:00:00-19:00:00)', '\nApproved by admin at 2025-09-09 17:01:41', '2025-09-09 15:00:48', '2025-09-09 15:01:41', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-09 15:00:48', NULL, NULL, NULL, NULL, NULL, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', NULL, 1),
(32, 9, '2025-09-10 10:52:10', '2025-09-10 10:57:52', NULL, NULL, NULL, 0.00, 0.10, 'completed', NULL, '161.142.153.142', NULL, 1, 0, 'Pointage hors créneau autorisé (11:00:00-12:00:00)', '\nApproved by admin at 2025-09-10 11:25:13', '2025-09-10 08:52:10', '2025-09-10 09:25:13', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-10 08:52:10', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(34, 9, '2025-09-10 11:52:22', '2025-09-10 11:52:43', NULL, NULL, NULL, 0.00, 0.01, 'completed', NULL, '82.29.168.205', NULL, 1, 0, 'Aucun créneau horaire défini pour cet utilisateur', '\nApproved by admin at 2025-09-10 14:44:38', '2025-09-10 09:52:22', '2025-09-10 12:44:38', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-10 09:52:22', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(40, 9, '2025-09-11 21:22:33', '2025-09-12 10:09:32', NULL, NULL, NULL, 0.00, 12.78, 'completed', NULL, '161.142.153.142', NULL, 1, 0, 'Aucun créneau horaire défini pour cet utilisateur', '\nApproved by admin at 2025-09-17 12:44:31', '2025-09-11 19:22:33', '2025-09-17 10:44:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-11 19:22:33', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(41, 6, '2025-09-17 12:19:22', '2025-09-17 13:51:16', NULL, NULL, NULL, 0.00, 1.53, 'completed', NULL, '161.142.153.152', NULL, 1, 0, 'Aucun créneau horaire défini pour cet utilisateur', '\nApproved by admin at 2025-09-27 23:57:05', '2025-09-17 10:19:22', '2025-09-27 21:57:05', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-17 10:19:22', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(42, 6, '2025-09-17 13:51:24', '2025-09-25 01:33:17', NULL, NULL, 11.68, 0.00, 11.68, 'completed', NULL, '161.142.153.152', NULL, 1, 0, 'Aucun créneau horaire défini pour cet utilisateur', '\nApproved by admin at 2025-09-27 23:57:03', '2025-09-17 11:51:24', '2025-09-27 21:57:03', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-17 11:51:24', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(43, 9, '2025-09-17 18:18:55', '2025-09-19 13:15:53', NULL, NULL, NULL, 0.00, 42.95, 'completed', NULL, '109.210.27.45', NULL, 0, 0, 'Aucun créneau horaire défini pour cet utilisateur', NULL, '2025-09-17 16:18:55', '2025-09-19 11:15:53', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-17 16:18:55', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(44, 6, '2025-09-25 01:33:20', '2025-09-25 12:26:23', NULL, NULL, 10.88, 0.00, 10.88, 'completed', NULL, '84.98.112.56', NULL, 1, 0, NULL, '\nApproved by admin at 2025-09-27 23:56:59', '2025-09-24 23:33:20', '2025-09-27 21:56:59', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-24 23:33:20', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(45, 6, '2025-09-27 19:18:44', '2025-09-28 03:28:39', NULL, NULL, 8.15, 0.00, 8.15, 'completed', NULL, '104.28.42.19', NULL, 1, 0, NULL, '\nApproved by admin at 2025-11-06 02:21:01', '2025-09-27 17:18:44', '2025-11-06 01:21:01', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-27 17:18:44', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(46, 6, '2025-09-28 03:31:40', '2025-09-28 03:50:27', NULL, NULL, NULL, 0.00, 0.31, 'completed', NULL, '84.98.112.56', NULL, 1, 0, 'Pointage hors créneau global (08:00:00-12:30:00)', '\nApproved by admin at 2025-11-06 02:20:58', '2025-09-28 01:31:40', '2025-11-06 01:20:58', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-28 01:31:40', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(53, 6, '2025-09-28 03:50:33', '2025-09-28 03:50:40', NULL, NULL, NULL, 0.00, 0.00, 'completed', NULL, '84.98.112.56', NULL, 1, 0, 'Pointage hors créneau global (08:00:00-12:30:00) | Pointage hors créneau global (08:00:00-12:30:00)', '\nApproved by admin at 2025-11-06 02:21:00', '2025-09-28 01:50:33', '2025-11-06 01:21:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-28 01:50:33', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(54, 6, '2025-09-28 03:50:53', '2025-09-28 20:53:58', NULL, NULL, NULL, 0.00, 17.05, 'completed', NULL, '82.29.168.205', NULL, 1, 0, 'Pointage hors créneau global (08:00:00-12:30:00) | Pointage hors créneau global (14:00:00-19:00:00)', '\nApproved by admin at 2025-11-06 02:20:56', '2025-09-28 01:50:53', '2025-11-06 01:20:56', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-28 01:50:53', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(55, 6, '2025-09-29 19:15:44', '2025-09-30 02:11:01', NULL, NULL, NULL, 0.00, 6.92, 'completed', NULL, '84.98.112.56', NULL, 1, 0, 'Pointage hors créneau global (14:00:00-19:00:00) | Pointage hors créneau global (08:00:00-12:30:00)', '\nApproved by admin at 2025-11-06 02:20:51', '2025-09-29 17:15:44', '2025-11-06 01:20:51', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-29 17:15:44', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(56, 12, '2025-10-20 16:30:37', '2025-10-20 16:51:56', NULL, NULL, 0.36, 0.00, 0.36, 'completed', NULL, '84.98.112.56', NULL, 1, 1, 'Pointage dans créneau global (14:00:00-19:00:00)', '\nApproved by admin at 2025-11-06 02:20:49', '2025-10-20 14:30:37', '2025-11-06 01:20:49', 'GPS: ,', 'GPS: ,', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"user_agent\":\"Mozilla\\/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit\\/605.1.15 (KHTML, like Gecko) Version\\/18.5 Mobile\\/15E148 Safari\\/604.1\",\"accept_language\":\"fr-FR,fr;q=0.9,en-US;q=0.8,en;q=0.7\",\"accept_encoding\":\"gzip, deflate, br, zstd\",\"accept\":\"*\\/*\",\"connection\":\"\",\"host\":\"mkmkmk.mdgeek.top\",\"referer\":\"https:\\/\\/mkmkmk.mdgeek.top\\/index.php?page=accueil-modern\",\"forwarded_for\":\"\",\"real_ip\":\"\",\"request_time\":1760970637,\"remote_port\":\"49641\",\"server_addr\":\"82.29.168.205\",\"request_uri\":\"\\/time_tracking_api.php\"}', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-20 14:30:37', 9, 0, NULL, NULL, NULL, 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', NULL, 0),
(57, 12, '2025-11-03 22:59:55', '2025-11-03 23:00:24', NULL, NULL, 0.01, 0.00, 0.01, 'completed', NULL, '84.98.112.56', NULL, 1, 0, 'Pointage hors créneau global (14:00:00-19:00:00)', '\nApproved by admin at 2025-11-06 02:20:46', '2025-11-03 21:59:55', '2025-11-06 01:20:46', 'GPS: ,', 'GPS: ,', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"user_agent\":\"Mozilla\\/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/141.0.0.0 Safari\\/537.36\",\"accept_language\":\"fr-FR,fr;q=0.9,en-US;q=0.8,en;q=0.7\",\"accept_encoding\":\"gzip, deflate, br, zstd\",\"accept\":\"*\\/*\",\"connection\":\"\",\"host\":\"mkmkmk.servo.tools\",\"referer\":\"https:\\/\\/mkmkmk.servo.tools\\/\",\"forwarded_for\":\"\",\"real_ip\":\"\",\"request_time\":1762207195,\"remote_port\":\"57715\",\"server_addr\":\"82.29.168.205\",\"request_uri\":\"\\/time_tracking_api.php\"}', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-03 21:59:55', 4, 0, NULL, NULL, NULL, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', NULL, 0),
(58, 12, '2025-11-03 23:59:43', '2025-11-06 02:24:18', NULL, NULL, 50.41, 0.00, 50.41, 'completed', NULL, '84.98.112.56', NULL, 1, 0, 'Pointage hors créneau global (14:00:00-19:00:00)', NULL, '2025-11-03 22:59:43', '2025-11-06 02:30:50', 'GPS: ,', 'GPS: ,', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"user_agent\":\"Mozilla\\/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/141.0.0.0 Safari\\/537.36\",\"accept_language\":\"fr-FR,fr;q=0.9,en-US;q=0.8,en;q=0.7\",\"accept_encoding\":\"gzip, deflate, br, zstd\",\"accept\":\"*\\/*\",\"connection\":\"\",\"host\":\"mkmkmk.servo.tools\",\"referer\":\"https:\\/\\/mkmkmk.servo.tools\\/index.php?page=commande_moderne\",\"forwarded_for\":\"\",\"real_ip\":\"\",\"request_time\":1762210783,\"remote_port\":\"56469\",\"server_addr\":\"82.29.168.205\",\"request_uri\":\"\\/time_tracking_api.php\"}', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-03 22:59:43', 4, 0, NULL, NULL, NULL, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', NULL, 0),
(59, 12, '2025-11-06 02:24:24', '2025-11-06 02:24:28', NULL, NULL, 0.00, 0.00, 0.00, 'completed', NULL, '84.98.112.56', NULL, 1, 0, 'Pointage hors créneau global (08:00:00-12:30:00)', NULL, '2025-11-06 01:24:25', '2025-11-06 02:28:04', 'GPS: ,', 'GPS: ,', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"user_agent\":\"Mozilla\\/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/142.0.0.0 Safari\\/537.36\",\"accept_language\":\"fr-FR,fr;q=0.9,en-US;q=0.8,en;q=0.7\",\"accept_encoding\":\"gzip, deflate, br, zstd\",\"accept\":\"*\\/*\",\"connection\":\"\",\"host\":\"mkmkmk.mdgeek.top\",\"referer\":\"https:\\/\\/mkmkmk.mdgeek.top\\/index.php?page=commandes_pieces\",\"forwarded_for\":\"\",\"real_ip\":\"\",\"request_time\":1762392264,\"remote_port\":\"54089\",\"server_addr\":\"82.29.168.205\",\"request_uri\":\"\\/time_tracking_api.php\"}', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-06 01:24:25', 4, 0, NULL, NULL, NULL, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', NULL, 0),
(60, 12, '2025-11-06 02:24:31', '2025-11-06 02:24:34', NULL, NULL, 0.00, 0.00, 0.00, 'completed', NULL, '84.98.112.56', NULL, 1, 0, 'Pointage hors créneau global (08:00:00-12:30:00)', NULL, '2025-11-06 01:24:31', '2025-11-06 02:26:18', 'GPS: ,', 'GPS: ,', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"user_agent\":\"Mozilla\\/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/142.0.0.0 Safari\\/537.36\",\"accept_language\":\"fr-FR,fr;q=0.9,en-US;q=0.8,en;q=0.7\",\"accept_encoding\":\"gzip, deflate, br, zstd\",\"accept\":\"*\\/*\",\"connection\":\"\",\"host\":\"mkmkmk.mdgeek.top\",\"referer\":\"https:\\/\\/mkmkmk.mdgeek.top\\/index.php?page=commandes_pieces\",\"forwarded_for\":\"\",\"real_ip\":\"\",\"request_time\":1762392271,\"remote_port\":\"54111\",\"server_addr\":\"82.29.168.205\",\"request_uri\":\"\\/time_tracking_api.php\"}', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-06 01:24:31', 4, 0, NULL, NULL, NULL, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', NULL, 0),
(61, 12, '2025-11-10 00:46:58', NULL, NULL, NULL, NULL, 0.00, NULL, 'active', NULL, '84.98.112.56', NULL, 0, 0, 'Pointage hors créneau spécifique (01:01:00-22:22:00)', NULL, '2025-11-09 23:46:58', '2025-11-09 23:46:58', 'GPS: ,', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"user_agent\":\"Mozilla\\/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit\\/605.1.15 (KHTML, like Gecko) Version\\/26.0.1 Mobile\\/15E148 Safari\\/604.1\",\"accept_language\":\"fr-FR,fr;q=0.9\",\"accept_encoding\":\"gzip, deflate, br\",\"accept\":\"*\\/*\",\"connection\":\"\",\"host\":\"mkmkmk.servo.tools\",\"referer\":\"https:\\/\\/mkmkmk.servo.tools\\/index.php?page=accueil\",\"forwarded_for\":\"\",\"real_ip\":\"\",\"request_time\":1762732018,\"remote_port\":\"49374\",\"server_addr\":\"82.29.168.205\",\"request_uri\":\"\\/time_tracking_api.php\"}', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-09 23:46:58', 2, 0, NULL, NULL, NULL, 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.0.1 Mobile/15E148 Safari/604.1', NULL, 0);

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `time_tracking_report`
-- (Voir ci-dessous la vue réelle)
--
CREATE TABLE `time_tracking_report` (
`admin_approved` tinyint(1)
,`admin_notes` text
,`break_duration` decimal(5,2)
,`break_end` datetime
,`break_start` datetime
,`clock_in` datetime
,`clock_out` datetime
,`display_status` varchar(14)
,`full_name` varchar(100)
,`id` int
,`location` varchar(255)
,`notes` text
,`overtime_hours` decimal(6,2)
,`role` enum('admin','technicien')
,`status` enum('active','completed','break')
,`total_hours` decimal(5,2)
,`user_id` int
,`username` varchar(50)
,`work_date` date
,`work_duration` decimal(5,2)
);

-- --------------------------------------------------------

--
-- Structure de la table `time_tracking_settings`
--

CREATE TABLE `time_tracking_settings` (
  `id` int NOT NULL,
  `setting_name` varchar(100) NOT NULL,
  `setting_value` text NOT NULL,
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `time_tracking_settings`
--

INSERT INTO `time_tracking_settings` (`id`, `setting_name`, `setting_value`, `description`, `created_at`, `updated_at`) VALUES
(1, 'auto_break_time', '120', 'Durée automatique de pause en minutes (0 = désactivé)', '2025-09-08 16:50:30', '2025-09-08 16:50:30'),
(2, 'max_work_hours', '12', 'Nombre maximum d\'heures de travail par jour', '2025-09-08 16:50:30', '2025-09-08 16:50:30'),
(3, 'require_location', 'false', 'Exiger la géolocalisation pour pointer', '2025-09-08 16:50:30', '2025-09-08 16:50:30'),
(4, 'admin_approval_required', 'false', 'Approbation admin requise pour les pointages', '2025-09-08 16:50:30', '2025-09-08 16:50:30'),
(5, 'allow_manual_edit', 'true', 'Permettre la modification manuelle des pointages', '2025-09-08 16:50:30', '2025-09-08 16:50:30'),
(6, 'break_threshold', '6', 'Heures de travail avant pause obligatoire', '2025-09-08 16:50:30', '2025-09-08 16:50:30'),
(7, 'overtime_threshold', '8', 'Heures de travail avant heures supplémentaires', '2025-09-08 16:50:30', '2025-09-08 16:50:30');

-- --------------------------------------------------------

--
-- Structure de la table `transactions_partenaires`
--

CREATE TABLE `transactions_partenaires` (
  `id` int NOT NULL,
  `partenaire_id` int NOT NULL,
  `type` enum('AVANCE','REMBOURSEMENT','SERVICE') COLLATE utf8mb4_unicode_ci NOT NULL,
  `montant` decimal(10,2) NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `date_transaction` datetime DEFAULT CURRENT_TIMESTAMP,
  `reference_document` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `statut` enum('EN_ATTENTE','VALIDÉ','ANNULÉ') COLLATE utf8mb4_unicode_ci DEFAULT 'EN_ATTENTE'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `transactions_partenaires`
--

INSERT INTO `transactions_partenaires` (`id`, `partenaire_id`, `type`, `montant`, `description`, `date_transaction`, `reference_document`, `statut`) VALUES
(1, 1, 'AVANCE', 2.00, 'sad', '2025-09-28 01:37:38', NULL, 'VALIDÉ'),
(2, 1, 'SERVICE', 2.40, 'Utilisation pièce: saber (Réf: 30058569) - Quantité: 1', '2025-09-28 01:55:00', NULL, 'VALIDÉ'),
(3, 1, 'SERVICE', 2.40, 'Utilisation pièce: saber (Réf: 30058569) - Quantité: 1', '2025-09-28 02:46:58', NULL, 'VALIDÉ');

-- --------------------------------------------------------

--
-- Structure de la table `typing_status`
--

CREATE TABLE `typing_status` (
  `user_id` int NOT NULL,
  `conversation_id` int NOT NULL,
  `timestamp` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `full_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('admin','technicien') COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `techbusy` int DEFAULT '0',
  `active_repair_id` int DEFAULT NULL,
  `cagnotte` decimal(10,2) DEFAULT '0.00',
  `points_experience` int DEFAULT '0',
  `score_total` int DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `role`, `created_at`, `techbusy`, `active_repair_id`, `cagnotte`, `points_experience`, `score_total`) VALUES
(6, 'admin', '$2y$10$T7HHfQ83FM9D1HfRFYUR5O4oiehfbDnreFXNxjaPbvDlSFSEpYVH2', 'Administrateur Mkmkmk', 'admin', '2025-07-03 21:52:58', 1, 2140, 24.00, 12, 0),
(12, 'testuser', 'password', 'Utilisateur Test', 'technicien', '2025-10-12 18:27:07', 0, NULL, 0.00, 0, 0),
(13, 'test1', '$2y$10$Adm8c0FpWIyUanzD.11Kq.N5NwWuMq2VmUBvBV4lAzVylW5wHDHAu', 'Utilisateur Standard Test', 'technicien', '2025-11-07 00:52:27', 0, NULL, 0.00, 0, 0);

-- --------------------------------------------------------

--
-- Structure de la table `user_cagnotte`
--

CREATE TABLE `user_cagnotte` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `solde_euros` decimal(10,2) NOT NULL DEFAULT '0.00',
  `solde_points` int NOT NULL DEFAULT '0',
  `total_gagne_euros` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_gagne_points` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `user_cagnotte`
--

INSERT INTO `user_cagnotte` (`id`, `user_id`, `solde_euros`, `solde_points`, `total_gagne_euros`, `total_gagne_points`, `created_at`, `updated_at`) VALUES
(1, 6, 46.00, 22, 46.00, 22, '2025-11-10 11:35:07', '2025-11-10 11:37:31');

-- --------------------------------------------------------

--
-- Structure de la table `user_missions`
--

CREATE TABLE `user_missions` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `mission_id` int NOT NULL,
  `progression` int NOT NULL DEFAULT '0',
  `statut` enum('en_cours','terminee','validee','payee') DEFAULT 'en_cours',
  `date_inscription` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `date_completion` timestamp NULL DEFAULT NULL,
  `date_validation` timestamp NULL DEFAULT NULL,
  `date_paiement` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `user_missions`
--

INSERT INTO `user_missions` (`id`, `user_id`, `mission_id`, `progression`, `statut`, `date_inscription`, `date_completion`, `date_validation`, `date_paiement`) VALUES
(1, 6, 1, 2, 'terminee', '2025-11-10 11:24:20', '2025-11-10 11:25:05', NULL, NULL),
(2, 6, 2, 2, 'terminee', '2025-11-10 11:32:40', '2025-11-10 11:37:31', NULL, NULL);

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `user_mission_dashboard`
-- (Voir ci-dessous la vue réelle)
--
CREATE TABLE `user_mission_dashboard` (
);

-- --------------------------------------------------------

--
-- Structure de la table `user_preferences`
--

CREATE TABLE `user_preferences` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `preference_key` varchar(100) NOT NULL,
  `preference_value` text,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `user_sessions`
--

CREATE TABLE `user_sessions` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiry` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `vue_garanties_actives`
-- (Voir ci-dessous la vue réelle)
--
CREATE TABLE `vue_garanties_actives` (
`alerte_expiration` varchar(14)
,`client_id` int
,`date_debut` timestamp
,`date_fin` timestamp
,`description_garantie` text
,`description_probleme` text
,`duree_jours` int
,`email` varchar(100)
,`garantie_id` int
,`jours_restants` int
,`modele` varchar(100)
,`nom` varchar(100)
,`prenom` varchar(100)
,`prix_reparation` decimal(10,2)
,`reparation_id` int
,`statut_garantie` enum('active','expiree','utilisee','annulee')
,`telephone` varchar(20)
,`type_appareil` varchar(50)
);

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `v_combined_logs`
-- (Voir ci-dessous la vue réelle)
--
CREATE TABLE `v_combined_logs` (
`action_type` varchar(17)
,`date_action` timestamp
,`details` mediumtext
,`employe_id` int
,`employe_nom` varchar(100)
,`id` int
,`log_source` varchar(10)
,`reference_id` int
,`reference_title` varchar(255)
,`statut_apres` varchar(50)
,`statut_avant` varchar(50)
,`unique_id` varchar(12)
);

-- --------------------------------------------------------

--
-- Structure de la table `wallet_transactions`
--

CREATE TABLE `wallet_transactions` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `amount_eur` decimal(10,2) DEFAULT '0.00',
  `points` int DEFAULT '0',
  `type` enum('mission_gain','withdrawal_request','withdrawal_paid','adjustment') NOT NULL,
  `status` enum('pending','confirmed','rejected') DEFAULT 'pending',
  `meta` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `wifi_authorized_ssids`
--

CREATE TABLE `wifi_authorized_ssids` (
  `id` int NOT NULL,
  `ssid` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `withdrawal_requests`
--

CREATE TABLE `withdrawal_requests` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `amount_eur` decimal(10,2) NOT NULL,
  `status` enum('pending','approved','paid','rejected') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `processed_at` timestamp NULL DEFAULT NULL,
  `processed_by` int DEFAULT NULL,
  `comment` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la vue `mission_stats`
--
DROP TABLE IF EXISTS `mission_stats`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `mission_stats`  AS SELECT `m`.`id` AS `id`, `m`.`titre` AS `titre`, `m`.`type_id` AS `type_id`, `mt`.`nom` AS `type_nom`, count(`um`.`id`) AS `participants`, count((case when (`um`.`statut` = 'terminee') then 1 end)) AS `completes`, round(((count((case when (`um`.`statut` = 'terminee') then 1 end)) * 100.0) / nullif(count(`um`.`id`),0)),2) AS `taux_completion`, sum((case when (`um`.`statut` = 'terminee') then `m`.`recompense_euros` else 0 end)) AS `cout_total_euros`, sum((case when (`um`.`statut` = 'terminee') then `m`.`recompense_points` else 0 end)) AS `points_total` FROM ((`missions` `m` left join `mission_types` `mt` on((`m`.`type_id` = `mt`.`id`))) left join `user_missions` `um` on((`m`.`id` = `um`.`mission_id`))) WHERE (`m`.`statut` = 'active') GROUP BY `m`.`id`, `m`.`titre`, `m`.`type_id`, `mt`.`nom` ;

-- --------------------------------------------------------

--
-- Structure de la vue `time_tracking_report`
--
DROP TABLE IF EXISTS `time_tracking_report`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `time_tracking_report`  AS SELECT `tt`.`id` AS `id`, `tt`.`user_id` AS `user_id`, `u`.`username` AS `username`, `u`.`full_name` AS `full_name`, `u`.`role` AS `role`, cast(`tt`.`clock_in` as date) AS `work_date`, `tt`.`clock_in` AS `clock_in`, `tt`.`clock_out` AS `clock_out`, `tt`.`break_start` AS `break_start`, `tt`.`break_end` AS `break_end`, `tt`.`total_hours` AS `total_hours`, `tt`.`break_duration` AS `break_duration`, `tt`.`work_duration` AS `work_duration`, `tt`.`status` AS `status`, `tt`.`location` AS `location`, `tt`.`notes` AS `notes`, `tt`.`admin_approved` AS `admin_approved`, `tt`.`admin_notes` AS `admin_notes`, (case when (`tt`.`total_hours` > 8) then (`tt`.`total_hours` - 8) else 0 end) AS `overtime_hours`, (case when ((`tt`.`status` = 'active') and (`tt`.`clock_in` < (now() - interval 12 hour))) then 'session_longue' when (`tt`.`status` = 'active') then 'en_cours' when (`tt`.`status` = 'break') then 'en_pause' else 'termine' end) AS `display_status` FROM (`time_tracking` `tt` join `users` `u` on((`tt`.`user_id` = `u`.`id`))) ORDER BY `tt`.`clock_in` DESC ;

-- --------------------------------------------------------

--
-- Structure de la vue `user_mission_dashboard`
--
DROP TABLE IF EXISTS `user_mission_dashboard`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `user_mission_dashboard`  AS SELECT `u`.`id` AS `user_id`, `u`.`username` AS `username`, count(`um`.`id`) AS `missions_rejointes`, count((case when (`um`.`statut` = 'terminee') then 1 end)) AS `missions_completees`, count((case when (`um`.`statut` = 'en_cours') then 1 end)) AS `missions_en_cours`, coalesce(sum(`mr`.`montant_euros`),0) AS `total_euros_gagne`, coalesce(sum(`mr`.`points_gagnes`),0) AS `total_points_gagne` FROM ((`users` `u` left join `user_missions` `um` on((`u`.`id` = `um`.`user_id`))) left join `mission_recompenses` `mr` on((`u`.`id` = `mr`.`user_id`))) GROUP BY `u`.`id`, `u`.`username` ;

-- --------------------------------------------------------

--
-- Structure de la vue `vue_garanties_actives`
--
DROP TABLE IF EXISTS `vue_garanties_actives`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vue_garanties_actives`  AS SELECT `g`.`id` AS `garantie_id`, `g`.`date_debut` AS `date_debut`, `g`.`date_fin` AS `date_fin`, `g`.`duree_jours` AS `duree_jours`, `g`.`statut` AS `statut_garantie`, `g`.`description_garantie` AS `description_garantie`, `r`.`id` AS `reparation_id`, `r`.`type_appareil` AS `type_appareil`, `r`.`modele` AS `modele`, `r`.`description_probleme` AS `description_probleme`, `r`.`prix_reparation` AS `prix_reparation`, `c`.`id` AS `client_id`, `c`.`nom` AS `nom`, `c`.`prenom` AS `prenom`, `c`.`telephone` AS `telephone`, `c`.`email` AS `email`, (to_days(`g`.`date_fin`) - to_days(now())) AS `jours_restants`, (case when (`g`.`date_fin` < now()) then 'Expirée' when ((to_days(`g`.`date_fin`) - to_days(now())) <= 7) then 'Expire bientôt' else 'Active' end) AS `alerte_expiration` FROM ((`garanties` `g` join `reparations` `r` on((`g`.`reparation_id` = `r`.`id`))) join `clients` `c` on((`r`.`client_id` = `c`.`id`))) WHERE (`g`.`statut` = 'active') ORDER BY `g`.`date_fin` ASC ;

-- --------------------------------------------------------

--
-- Structure de la vue `v_combined_logs`
--
DROP TABLE IF EXISTS `v_combined_logs`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_combined_logs`  AS SELECT concat('R',`rl`.`id`) AS `unique_id`, `rl`.`id` AS `id`, `rl`.`date_action` AS `date_action`, `rl`.`action_type` AS `action_type`, `rl`.`statut_avant` AS `statut_avant`, `rl`.`statut_apres` AS `statut_apres`, `rl`.`details` AS `details`, `rl`.`employe_id` AS `employe_id`, coalesce(`u`.`full_name`,'Employé inconnu') AS `employe_nom`, 'reparation' AS `log_source`, `rl`.`reparation_id` AS `reference_id`, concat('Réparation #',`rl`.`reparation_id`) AS `reference_title` FROM (`reparation_logs` `rl` left join `users` `u` on((`rl`.`employe_id` = `u`.`id`)))union all select concat('T',`tl`.`id`) AS `unique_id`,`tl`.`id` AS `id`,`tl`.`date_action` AS `date_action`,`tl`.`action_type` AS `action_type`,`tl`.`statut_avant` AS `statut_avant`,`tl`.`statut_apres` AS `statut_apres`,`tl`.`details` AS `details`,`tl`.`employe_id` AS `employe_id`,coalesce(`u`.`full_name`,'Employé inconnu') AS `employe_nom`,'tache' AS `log_source`,`tl`.`tache_id` AS `reference_id`,coalesce(`t`.`titre`,concat('Tâche #',`tl`.`tache_id`)) AS `reference_title` from ((`task_logs` `tl` left join `users` `u` on((`tl`.`employe_id` = `u`.`id`))) left join `taches` `t` on((`tl`.`tache_id` = `t`.`id`)))  ;

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `bug_reports`
--
ALTER TABLE `bug_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `cagnotte_historique`
--
ALTER TABLE `cagnotte_historique`
  ADD PRIMARY KEY (`id`),
  ADD KEY `mission_id` (`mission_id`),
  ADD KEY `admin_id` (`admin_id`),
  ADD KEY `idx_cagnotte_user` (`user_id`),
  ADD KEY `idx_cagnotte_date` (`date_creation`);

--
-- Index pour la table `calculator_settings`
--
ALTER TABLE `calculator_settings`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `colis_retour`
--
ALTER TABLE `colis_retour`
  ADD PRIMARY KEY (`id`),
  ADD KEY `statut` (`statut`);

--
-- Index pour la table `commandes_fournisseurs`
--
ALTER TABLE `commandes_fournisseurs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fournisseur_id` (`fournisseur_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `commandes_pieces`
--
ALTER TABLE `commandes_pieces`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `reference` (`reference`),
  ADD KEY `reparation_id` (`reparation_id`),
  ADD KEY `fournisseur_id` (`fournisseur_id`),
  ADD KEY `fk_commandes_pieces_client` (`client_id`);

--
-- Index pour la table `commentaires_tache`
--
ALTER TABLE `commentaires_tache`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tache_id` (`tache_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `company_settings`
--
ALTER TABLE `company_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_shop` (`shop_id`),
  ADD KEY `idx_shop_id` (`shop_id`);

--
-- Index pour la table `confirmations_lecture`
--
ALTER TABLE `confirmations_lecture`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_confirmation` (`message_id`,`employe_id`),
  ADD KEY `employe_id` (`employe_id`);

--
-- Index pour la table `conges_demandes`
--
ALTER TABLE `conges_demandes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Index pour la table `conges_jours_disponibles`
--
ALTER TABLE `conges_jours_disponibles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_date` (`date`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_date` (`date`);

--
-- Index pour la table `conges_solde`
--
ALTER TABLE `conges_solde`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `conversations`
--
ALTER TABLE `conversations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Index pour la table `conversation_participants`
--
ALTER TABLE `conversation_participants`
  ADD PRIMARY KEY (`conversation_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `demandes_retrait`
--
ALTER TABLE `demandes_retrait`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `devis`
--
ALTER TABLE `devis`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `numero_devis` (`numero_devis`),
  ADD UNIQUE KEY `lien_securise` (`lien_securise`),
  ADD KEY `idx_reparation` (`reparation_id`),
  ADD KEY `idx_client` (`client_id`),
  ADD KEY `idx_statut` (`statut`),
  ADD KEY `idx_lien_securise` (`lien_securise`);

--
-- Index pour la table `devis_acceptations`
--
ALTER TABLE `devis_acceptations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_devis` (`devis_id`),
  ADD KEY `solution_choisie_id` (`solution_choisie_id`);

--
-- Index pour la table `devis_logs`
--
ALTER TABLE `devis_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_devis` (`devis_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_date` (`date_action`);

--
-- Index pour la table `devis_notifications`
--
ALTER TABLE `devis_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_devis` (`devis_id`),
  ADD KEY `idx_statut` (`statut_envoi`);

--
-- Index pour la table `devis_pannes`
--
ALTER TABLE `devis_pannes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_devis` (`devis_id`);

--
-- Index pour la table `devis_solutions`
--
ALTER TABLE `devis_solutions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_devis` (`devis_id`);

--
-- Index pour la table `devis_solutions_items`
--
ALTER TABLE `devis_solutions_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_solution` (`solution_id`);

--
-- Index pour la table `devis_templates`
--
ALTER TABLE `devis_templates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_actif` (`actif`);

--
-- Index pour la table `employee_schedules`
--
ALTER TABLE `employee_schedules`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `employee_day` (`employee_id`,`day_of_week`);

--
-- Index pour la table `employes`
--
ALTER TABLE `employes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Index pour la table `fournisseurs`
--
ALTER TABLE `fournisseurs`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `garanties`
--
ALTER TABLE `garanties`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_reparation_id` (`reparation_id`),
  ADD KEY `idx_date_fin` (`date_fin`),
  ADD KEY `idx_statut` (`statut`),
  ADD KEY `idx_garanties_dates` (`date_debut`,`date_fin`);

--
-- Index pour la table `gardiennage`
--
ALTER TABLE `gardiennage`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reparation_id` (`reparation_id`);

--
-- Index pour la table `gardiennage_notifications`
--
ALTER TABLE `gardiennage_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `gardiennage_id` (`gardiennage_id`);

--
-- Index pour la table `help_requests`
--
ALTER TABLE `help_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `task_id` (`task_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `historique_gains`
--
ALTER TABLE `historique_gains`
  ADD PRIMARY KEY (`id`),
  ADD KEY `mission_id` (`mission_id`);

--
-- Index pour la table `historique_soldes`
--
ALTER TABLE `historique_soldes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `partenaire_id` (`partenaire_id`),
  ADD KEY `transaction_id` (`transaction_id`);

--
-- Index pour la table `journal_actions`
--
ALTER TABLE `journal_actions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `action_type` (`action_type`),
  ADD KEY `target_id` (`target_id`);

--
-- Index pour la table `kb_articles`
--
ALTER TABLE `kb_articles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- Index pour la table `kb_article_ratings`
--
ALTER TABLE `kb_article_ratings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `article_user` (`article_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `kb_article_tags`
--
ALTER TABLE `kb_article_tags`
  ADD PRIMARY KEY (`article_id`,`tag_id`),
  ADD KEY `tag_id` (`tag_id`);

--
-- Index pour la table `kb_categories`
--
ALTER TABLE `kb_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Index pour la table `kb_files`
--
ALTER TABLE `kb_files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `uploaded_by` (`uploaded_by`),
  ADD KEY `uploaded_at` (`uploaded_at`);

--
-- Index pour la table `kb_tags`
--
ALTER TABLE `kb_tags`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Index pour la table `label_layouts`
--
ALTER TABLE `label_layouts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_shop_layout` (`shop_id`),
  ADD KEY `idx_shop_id_label` (`shop_id`);

--
-- Index pour la table `lecture_annonces`
--
ALTER TABLE `lecture_annonces`
  ADD PRIMARY KEY (`message_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `lignes_commande_fournisseur`
--
ALTER TABLE `lignes_commande_fournisseur`
  ADD PRIMARY KEY (`id`),
  ADD KEY `commande_id` (`commande_id`),
  ADD KEY `produit_id` (`produit_id`);

--
-- Index pour la table `log_statistics_cache`
--
ALTER TABLE `log_statistics_cache`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cache_lookup` (`shop_id`,`cache_key`,`expires_at`);

--
-- Index pour la table `marges_estimees`
--
ALTER TABLE `marges_estimees`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `marges_reference`
--
ALTER TABLE `marges_reference`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `conversation_id` (`conversation_id`),
  ADD KEY `sender_id` (`sender_id`);

--
-- Index pour la table `message_attachments`
--
ALTER TABLE `message_attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `message_id` (`message_id`);

--
-- Index pour la table `message_reactions`
--
ALTER TABLE `message_reactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_reaction` (`message_id`,`user_id`,`reaction`),
  ADD KEY `idx_message_reactions_message_id` (`message_id`),
  ADD KEY `idx_message_reactions_user_id` (`user_id`);

--
-- Index pour la table `message_reads`
--
ALTER TABLE `message_reads`
  ADD PRIMARY KEY (`message_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `message_replies`
--
ALTER TABLE `message_replies`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_reply` (`message_id`,`reply_to_id`),
  ADD KEY `reply_to_id` (`reply_to_id`);

--
-- Index pour la table `missions`
--
ALTER TABLE `missions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `mission_type_id` (`mission_type_id`);

--
-- Index pour la table `mission_types`
--
ALTER TABLE `mission_types`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `mission_validations`
--
ALTER TABLE `mission_validations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_mission_id` (`user_mission_id`);

--
-- Index pour la table `mouvements_stock`
--
ALTER TABLE `mouvements_stock`
  ADD PRIMARY KEY (`id`),
  ADD KEY `produit_id` (`produit_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `mouvements_stock_fournisseur_fk` (`fournisseur_id`);

--
-- Index pour la table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_user_status` (`user_id`,`status`),
  ADD KEY `notification_type` (`notification_type`),
  ADD KEY `status` (`status`),
  ADD KEY `created_at` (`created_at`);

--
-- Index pour la table `notification_preferences`
--
ALTER TABLE `notification_preferences`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_type_unique` (`user_id`,`type_notification`);

--
-- Index pour la table `notification_types`
--
ALTER TABLE `notification_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `type_code` (`type_code`);

--
-- Index pour la table `oauth_tokens`
--
ALTER TABLE `oauth_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `shop_id` (`shop_id`),
  ADD KEY `expires_at` (`expires_at`);

--
-- Index pour la table `paid_leave_balance`
--
ALTER TABLE `paid_leave_balance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `employee_year` (`employee_id`,`year`);

--
-- Index pour la table `paiements_sumup`
--
ALTER TABLE `paiements_sumup`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `checkout_id` (`checkout_id`),
  ADD KEY `idx_reparation` (`reparation_id`),
  ADD KEY `idx_statut` (`statut_paiement`),
  ADD KEY `idx_date` (`date_paiement`);

--
-- Index pour la table `parametres`
--
ALTER TABLE `parametres`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cle` (`cle`);

--
-- Index pour la table `parametres_gardiennage`
--
ALTER TABLE `parametres_gardiennage`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `parrainage_config`
--
ALTER TABLE `parrainage_config`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `parrainage_reductions`
--
ALTER TABLE `parrainage_reductions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parrain_id` (`parrain_id`),
  ADD KEY `reparation_utilisee_id` (`reparation_utilisee_id`);

--
-- Index pour la table `parrainage_relations`
--
ALTER TABLE `parrainage_relations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_parrainage` (`filleul_id`) COMMENT 'Un filleul ne peut avoir qu''un seul parrain',
  ADD KEY `parrain_id` (`parrain_id`);

--
-- Index pour la table `partenaires`
--
ALTER TABLE `partenaires`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `partner_transactions_pending`
--
ALTER TABLE `partner_transactions_pending`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_partenaire` (`partenaire_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_shop` (`shop_id`);

--
-- Index pour la table `photos_reparation`
--
ALTER TABLE `photos_reparation`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reparation_id` (`reparation_id`);

--
-- Index pour la table `pieces_avancees`
--
ALTER TABLE `pieces_avancees`
  ADD PRIMARY KEY (`id`),
  ADD KEY `partenaire_id` (`partenaire_id`),
  ADD KEY `piece_id` (`piece_id`);

--
-- Index pour la table `pieces_utilisees_reparations`
--
ALTER TABLE `pieces_utilisees_reparations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_reparation` (`reparation_id`),
  ADD KEY `idx_produit` (`produit_id`);

--
-- Index pour la table `preferences`
--
ALTER TABLE `preferences`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_prefs` (`user_id`),
  ADD KEY `idx_user_id_prefs` (`user_id`);

--
-- Index pour la table `presence_comments`
--
ALTER TABLE `presence_comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_event` (`event_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `presence_events`
--
ALTER TABLE `presence_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_employee_date` (`employee_id`,`date_start`),
  ADD KEY `idx_type` (`type_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_date_range` (`date_start`,`date_end`),
  ADD KEY `idx_document` (`document_path`);

--
-- Index pour la table `presence_history`
--
ALTER TABLE `presence_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_event_history` (`event_id`),
  ADD KEY `changed_by` (`changed_by`);

--
-- Index pour la table `presence_types`
--
ALTER TABLE `presence_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Index pour la table `produits`
--
ALTER TABLE `produits`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `reference` (`reference`),
  ADD KEY `categorie_id` (`categorie_id`),
  ADD KEY `produits_fournisseur_fk` (`fournisseur_id`),
  ADD KEY `idx_produits_suivre_stock` (`suivre_stock`);

--
-- Index pour la table `push_subscriptions`
--
ALTER TABLE `push_subscriptions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_endpoint` (`user_id`,`endpoint`(255)),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `rachat_appareils`
--
ALTER TABLE `rachat_appareils`
  ADD PRIMARY KEY (`id`),
  ADD KEY `client_id` (`client_id`);

--
-- Index pour la table `reclamations_garantie`
--
ALTER TABLE `reclamations_garantie`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reparation_id` (`reparation_id`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `nouvelle_reparation_id` (`nouvelle_reparation_id`),
  ADD KEY `employe_traite_id` (`employe_traite_id`),
  ADD KEY `idx_garantie_id` (`garantie_id`),
  ADD KEY `idx_statut` (`statut`),
  ADD KEY `idx_date_reclamation` (`date_reclamation`);

--
-- Index pour la table `relance_automatique_config`
--
ALTER TABLE `relance_automatique_config`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_shop` (`shop_id`);

--
-- Index pour la table `relance_automatique_logs`
--
ALTER TABLE `relance_automatique_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_shop_date` (`shop_id`,`date_execution`),
  ADD KEY `idx_devis` (`devis_id`);

--
-- Index pour la table `reparations`
--
ALTER TABLE `reparations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `fk_reparation_employe` (`employe_id`),
  ADD KEY `parrain_id` (`parrain_id`),
  ADD KEY `garantie_id` (`garantie_id`),
  ADD KEY `idx_reparations_statut_id` (`statut_id`),
  ADD KEY `idx_reparations_garantie_dates` (`date_garantie_debut`,`date_garantie_fin`);

--
-- Index pour la table `reparation_attributions`
--
ALTER TABLE `reparation_attributions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_reparation` (`reparation_id`),
  ADD KEY `idx_employe` (`employe_id`);

--
-- Index pour la table `reparation_logs`
--
ALTER TABLE `reparation_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_reparation` (`reparation_id`),
  ADD KEY `idx_action_type` (`action_type`),
  ADD KEY `idx_date_action` (`date_action`),
  ADD KEY `idx_employe` (`employe_id`),
  ADD KEY `idx_date_employe` (`date_action` DESC,`employe_id`),
  ADD KEY `idx_employe_date` (`employe_id`,`date_action` DESC),
  ADD KEY `idx_action_date` (`action_type`,`date_action` DESC),
  ADD KEY `idx_statut_apres_date` (`statut_apres`,`date_action` DESC),
  ADD KEY `idx_reparation_id` (`reparation_id`),
  ADD KEY `idx_ongoing_activities` (`employe_id`,`statut_apres`,`date_action` DESC);

--
-- Index pour la table `reparation_sms`
--
ALTER TABLE `reparation_sms`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reparation_id` (`reparation_id`),
  ADD KEY `template_id` (`template_id`),
  ADD KEY `statut_id` (`statut_id`);

--
-- Index pour la table `retours`
--
ALTER TABLE `retours`
  ADD PRIMARY KEY (`id`),
  ADD KEY `produit_id` (`produit_id`),
  ADD KEY `statut` (`statut`),
  ADD KEY `date_limite` (`date_limite`),
  ADD KEY `colis_id` (`colis_id`);

--
-- Index pour la table `scheduled_notifications`
--
ALTER TABLE `scheduled_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `scheduled_datetime` (`scheduled_datetime`),
  ADD KEY `status` (`status`),
  ADD KEY `target_user_id` (`target_user_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Index pour la table `services_partenaires`
--
ALTER TABLE `services_partenaires`
  ADD PRIMARY KEY (`id`),
  ADD KEY `partenaire_id` (`partenaire_id`);

--
-- Index pour la table `sms_campaigns`
--
ALTER TABLE `sms_campaigns`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `sms_campaign_details`
--
ALTER TABLE `sms_campaign_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `campaign_id` (`campaign_id`),
  ADD KEY `client_id` (`client_id`);

--
-- Index pour la table `sms_deduplication`
--
ALTER TABLE `sms_deduplication`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_phone_message` (`phone_hash`,`message_hash`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Index pour la table `sms_logs`
--
ALTER TABLE `sms_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_date` (`date_envoi`);

--
-- Index pour la table `sms_template`
--
ALTER TABLE `sms_template`
  ADD PRIMARY KEY (`id`),
  ADD KEY `statut_id` (`statut_id`);

--
-- Index pour la table `sms_templates`
--
ALTER TABLE `sms_templates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_statut` (`statut_id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Index pour la table `sms_template_variables`
--
ALTER TABLE `sms_template_variables`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nom` (`nom`);

--
-- Index pour la table `soldes_partenaires`
--
ALTER TABLE `soldes_partenaires`
  ADD PRIMARY KEY (`partenaire_id`);

--
-- Index pour la table `statuts`
--
ALTER TABLE `statuts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `categorie_id` (`categorie_id`);

--
-- Index pour la table `statuts_reparation`
--
ALTER TABLE `statuts_reparation`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `statut_categories`
--
ALTER TABLE `statut_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Index pour la table `stock`
--
ALTER TABLE `stock`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `barcode` (`barcode`);

--
-- Index pour la table `stock_history`
--
ALTER TABLE `stock_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Index pour la table `taches`
--
ALTER TABLE `taches`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `taches_ibfk_1` (`employe_id`);

--
-- Index pour la table `tache_attachments`
--
ALTER TABLE `tache_attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `uploaded_by` (`uploaded_by`),
  ADD KEY `idx_tache_id` (`tache_id`),
  ADD KEY `idx_date_upload` (`date_upload`);

--
-- Index pour la table `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `assigned_to` (`assigned_to`),
  ADD KEY `created_by` (`created_by`);

--
-- Index pour la table `task_logs`
--
ALTER TABLE `task_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tache` (`tache_id`),
  ADD KEY `idx_action_type` (`action_type`),
  ADD KEY `idx_date_action` (`date_action`),
  ADD KEY `idx_employe` (`employe_id`),
  ADD KEY `idx_date_employe` (`date_action` DESC,`employe_id`),
  ADD KEY `idx_employe_date` (`employe_id`,`date_action` DESC),
  ADD KEY `idx_action_date` (`action_type`,`date_action` DESC),
  ADD KEY `idx_statut_apres_date` (`statut_apres`,`date_action` DESC),
  ADD KEY `idx_tache_id` (`tache_id`),
  ADD KEY `idx_ongoing_activities` (`employe_id`,`statut_apres`,`date_action` DESC);

--
-- Index pour la table `time_slots`
--
ALTER TABLE `time_slots`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_slot` (`user_id`,`slot_type`),
  ADD KEY `idx_time_slots_user_type` (`user_id`,`slot_type`,`is_active`);

--
-- Index pour la table `time_tracking`
--
ALTER TABLE `time_tracking`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_date` (`user_id`,`clock_in`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_clock_in` (`clock_in`),
  ADD KEY `idx_active_sessions` (`user_id`,`status`),
  ADD KEY `idx_time_tracking_approval` (`admin_approved`,`auto_approved`,`status`),
  ADD KEY `idx_time_tracking_gps_in` (`latitude_in`,`longitude_in`),
  ADD KEY `idx_time_tracking_gps_out` (`latitude_out`,`longitude_out`),
  ADD KEY `idx_time_tracking_device` (`device_fingerprint`(50)),
  ADD KEY `idx_time_tracking_security` (`is_vpn_proxy`,`country_code`),
  ADD KEY `idx_time_tracking_timestamps` (`client_timestamp`,`server_timestamp`);

--
-- Index pour la table `time_tracking_settings`
--
ALTER TABLE `time_tracking_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_name` (`setting_name`);

--
-- Index pour la table `transactions_partenaires`
--
ALTER TABLE `transactions_partenaires`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_transactions_partenaires` (`partenaire_id`);

--
-- Index pour la table `typing_status`
--
ALTER TABLE `typing_status`
  ADD PRIMARY KEY (`user_id`,`conversation_id`),
  ADD KEY `conversation_id` (`conversation_id`);

--
-- Index pour la table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Index pour la table `user_cagnotte`
--
ALTER TABLE `user_cagnotte`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Index pour la table `user_missions`
--
ALTER TABLE `user_missions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_mission` (`user_id`,`mission_id`),
  ADD KEY `mission_id` (`mission_id`);

--
-- Index pour la table `user_preferences`
--
ALTER TABLE `user_preferences`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_user_pref` (`user_id`,`preference_key`);

--
-- Index pour la table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `expiry` (`expiry`);

--
-- Index pour la table `wallet_transactions`
--
ALTER TABLE `wallet_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `type` (`type`),
  ADD KEY `status` (`status`);

--
-- Index pour la table `wifi_authorized_ssids`
--
ALTER TABLE `wifi_authorized_ssids`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_ssid` (`ssid`);

--
-- Index pour la table `withdrawal_requests`
--
ALTER TABLE `withdrawal_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `status` (`status`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `bug_reports`
--
ALTER TABLE `bug_reports`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `cagnotte_historique`
--
ALTER TABLE `cagnotte_historique`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `calculator_settings`
--
ALTER TABLE `calculator_settings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `clients`
--
ALTER TABLE `clients`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1602;

--
-- AUTO_INCREMENT pour la table `colis_retour`
--
ALTER TABLE `colis_retour`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `commandes_fournisseurs`
--
ALTER TABLE `commandes_fournisseurs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `commandes_pieces`
--
ALTER TABLE `commandes_pieces`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT pour la table `commentaires_tache`
--
ALTER TABLE `commentaires_tache`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT pour la table `company_settings`
--
ALTER TABLE `company_settings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `confirmations_lecture`
--
ALTER TABLE `confirmations_lecture`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `conges_demandes`
--
ALTER TABLE `conges_demandes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `conges_jours_disponibles`
--
ALTER TABLE `conges_jours_disponibles`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT pour la table `conges_solde`
--
ALTER TABLE `conges_solde`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `conversations`
--
ALTER TABLE `conversations`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `demandes_retrait`
--
ALTER TABLE `demandes_retrait`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `devis`
--
ALTER TABLE `devis`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT pour la table `devis_acceptations`
--
ALTER TABLE `devis_acceptations`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT pour la table `devis_logs`
--
ALTER TABLE `devis_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT pour la table `devis_notifications`
--
ALTER TABLE `devis_notifications`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT pour la table `devis_pannes`
--
ALTER TABLE `devis_pannes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT pour la table `devis_solutions`
--
ALTER TABLE `devis_solutions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT pour la table `devis_solutions_items`
--
ALTER TABLE `devis_solutions_items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `devis_templates`
--
ALTER TABLE `devis_templates`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `employee_schedules`
--
ALTER TABLE `employee_schedules`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `employes`
--
ALTER TABLE `employes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `fournisseurs`
--
ALTER TABLE `fournisseurs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT pour la table `garanties`
--
ALTER TABLE `garanties`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT pour la table `gardiennage`
--
ALTER TABLE `gardiennage`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT pour la table `gardiennage_notifications`
--
ALTER TABLE `gardiennage_notifications`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `help_requests`
--
ALTER TABLE `help_requests`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `historique_gains`
--
ALTER TABLE `historique_gains`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `historique_soldes`
--
ALTER TABLE `historique_soldes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `journal_actions`
--
ALTER TABLE `journal_actions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT pour la table `kb_articles`
--
ALTER TABLE `kb_articles`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT pour la table `kb_article_ratings`
--
ALTER TABLE `kb_article_ratings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `kb_categories`
--
ALTER TABLE `kb_categories`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `kb_files`
--
ALTER TABLE `kb_files`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `kb_tags`
--
ALTER TABLE `kb_tags`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT pour la table `label_layouts`
--
ALTER TABLE `label_layouts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `lignes_commande_fournisseur`
--
ALTER TABLE `lignes_commande_fournisseur`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `log_statistics_cache`
--
ALTER TABLE `log_statistics_cache`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=75;

--
-- AUTO_INCREMENT pour la table `marges_estimees`
--
ALTER TABLE `marges_estimees`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT pour la table `marges_reference`
--
ALTER TABLE `marges_reference`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT pour la table `message_attachments`
--
ALTER TABLE `message_attachments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `message_reactions`
--
ALTER TABLE `message_reactions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `message_replies`
--
ALTER TABLE `message_replies`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `missions`
--
ALTER TABLE `missions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `mission_types`
--
ALTER TABLE `mission_types`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT pour la table `mission_validations`
--
ALTER TABLE `mission_validations`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `mouvements_stock`
--
ALTER TABLE `mouvements_stock`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT pour la table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `notification_preferences`
--
ALTER TABLE `notification_preferences`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `notification_types`
--
ALTER TABLE `notification_types`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `oauth_tokens`
--
ALTER TABLE `oauth_tokens`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `paid_leave_balance`
--
ALTER TABLE `paid_leave_balance`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `paiements_sumup`
--
ALTER TABLE `paiements_sumup`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `parametres`
--
ALTER TABLE `parametres`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT pour la table `parametres_gardiennage`
--
ALTER TABLE `parametres_gardiennage`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `parrainage_config`
--
ALTER TABLE `parrainage_config`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `parrainage_reductions`
--
ALTER TABLE `parrainage_reductions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `parrainage_relations`
--
ALTER TABLE `parrainage_relations`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `partenaires`
--
ALTER TABLE `partenaires`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `partner_transactions_pending`
--
ALTER TABLE `partner_transactions_pending`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `photos_reparation`
--
ALTER TABLE `photos_reparation`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `pieces_avancees`
--
ALTER TABLE `pieces_avancees`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `pieces_utilisees_reparations`
--
ALTER TABLE `pieces_utilisees_reparations`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `preferences`
--
ALTER TABLE `preferences`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `presence_comments`
--
ALTER TABLE `presence_comments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `presence_events`
--
ALTER TABLE `presence_events`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT pour la table `presence_history`
--
ALTER TABLE `presence_history`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `presence_types`
--
ALTER TABLE `presence_types`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `produits`
--
ALTER TABLE `produits`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT pour la table `push_subscriptions`
--
ALTER TABLE `push_subscriptions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `rachat_appareils`
--
ALTER TABLE `rachat_appareils`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT pour la table `reclamations_garantie`
--
ALTER TABLE `reclamations_garantie`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `relance_automatique_config`
--
ALTER TABLE `relance_automatique_config`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `relance_automatique_logs`
--
ALTER TABLE `relance_automatique_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `reparations`
--
ALTER TABLE `reparations`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2143;

--
-- AUTO_INCREMENT pour la table `reparation_attributions`
--
ALTER TABLE `reparation_attributions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=199;

--
-- AUTO_INCREMENT pour la table `reparation_logs`
--
ALTER TABLE `reparation_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=78;

--
-- AUTO_INCREMENT pour la table `reparation_sms`
--
ALTER TABLE `reparation_sms`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT pour la table `retours`
--
ALTER TABLE `retours`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `scheduled_notifications`
--
ALTER TABLE `scheduled_notifications`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `services_partenaires`
--
ALTER TABLE `services_partenaires`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `sms_campaigns`
--
ALTER TABLE `sms_campaigns`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `sms_campaign_details`
--
ALTER TABLE `sms_campaign_details`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `sms_deduplication`
--
ALTER TABLE `sms_deduplication`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=277;

--
-- AUTO_INCREMENT pour la table `sms_logs`
--
ALTER TABLE `sms_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=60;

--
-- AUTO_INCREMENT pour la table `sms_template`
--
ALTER TABLE `sms_template`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `sms_templates`
--
ALTER TABLE `sms_templates`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT pour la table `sms_template_variables`
--
ALTER TABLE `sms_template_variables`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT pour la table `statuts`
--
ALTER TABLE `statuts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT pour la table `statuts_reparation`
--
ALTER TABLE `statuts_reparation`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT pour la table `statut_categories`
--
ALTER TABLE `statut_categories`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT pour la table `stock`
--
ALTER TABLE `stock`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `stock_history`
--
ALTER TABLE `stock_history`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `taches`
--
ALTER TABLE `taches`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT pour la table `tache_attachments`
--
ALTER TABLE `tache_attachments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `task_logs`
--
ALTER TABLE `task_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `time_slots`
--
ALTER TABLE `time_slots`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5854;

--
-- AUTO_INCREMENT pour la table `time_tracking`
--
ALTER TABLE `time_tracking`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=62;

--
-- AUTO_INCREMENT pour la table `time_tracking_settings`
--
ALTER TABLE `time_tracking_settings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT pour la table `transactions_partenaires`
--
ALTER TABLE `transactions_partenaires`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT pour la table `user_cagnotte`
--
ALTER TABLE `user_cagnotte`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `user_missions`
--
ALTER TABLE `user_missions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `user_preferences`
--
ALTER TABLE `user_preferences`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `user_sessions`
--
ALTER TABLE `user_sessions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=600;

--
-- AUTO_INCREMENT pour la table `wallet_transactions`
--
ALTER TABLE `wallet_transactions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `wifi_authorized_ssids`
--
ALTER TABLE `wifi_authorized_ssids`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `withdrawal_requests`
--
ALTER TABLE `withdrawal_requests`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `bug_reports`
--
ALTER TABLE `bug_reports`
  ADD CONSTRAINT `bug_reports_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `cagnotte_historique`
--
ALTER TABLE `cagnotte_historique`
  ADD CONSTRAINT `cagnotte_historique_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `cagnotte_historique_ibfk_2` FOREIGN KEY (`mission_id`) REFERENCES `missions` (`id`),
  ADD CONSTRAINT `cagnotte_historique_ibfk_3` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`);

--
-- Contraintes pour la table `commandes_fournisseurs`
--
ALTER TABLE `commandes_fournisseurs`
  ADD CONSTRAINT `commandes_fournisseurs_ibfk_1` FOREIGN KEY (`fournisseur_id`) REFERENCES `fournisseurs` (`id`),
  ADD CONSTRAINT `commandes_fournisseurs_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Contraintes pour la table `commandes_pieces`
--
ALTER TABLE `commandes_pieces`
  ADD CONSTRAINT `commandes_pieces_ibfk_1` FOREIGN KEY (`reparation_id`) REFERENCES `reparations` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `commandes_pieces_ibfk_2` FOREIGN KEY (`fournisseur_id`) REFERENCES `fournisseurs` (`id`),
  ADD CONSTRAINT `fk_commandes_pieces_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`);

--
-- Contraintes pour la table `commentaires_tache`
--
ALTER TABLE `commentaires_tache`
  ADD CONSTRAINT `commentaires_tache_ibfk_1` FOREIGN KEY (`tache_id`) REFERENCES `taches` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `commentaires_tache_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Contraintes pour la table `confirmations_lecture`
--
ALTER TABLE `confirmations_lecture`
  ADD CONSTRAINT `confirmations_lecture_ibfk_1` FOREIGN KEY (`message_id`) REFERENCES `messages` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `confirmations_lecture_ibfk_2` FOREIGN KEY (`employe_id`) REFERENCES `employes` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `conges_demandes`
--
ALTER TABLE `conges_demandes`
  ADD CONSTRAINT `conges_demandes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `conges_demandes_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Contraintes pour la table `conges_jours_disponibles`
--
ALTER TABLE `conges_jours_disponibles`
  ADD CONSTRAINT `conges_jours_disponibles_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Contraintes pour la table `conges_solde`
--
ALTER TABLE `conges_solde`
  ADD CONSTRAINT `conges_solde_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Contraintes pour la table `conversations`
--
ALTER TABLE `conversations`
  ADD CONSTRAINT `conversations_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `conversation_participants`
--
ALTER TABLE `conversation_participants`
  ADD CONSTRAINT `conversation_participants_ibfk_1` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `conversation_participants_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `devis_acceptations`
--
ALTER TABLE `devis_acceptations`
  ADD CONSTRAINT `devis_acceptations_ibfk_1` FOREIGN KEY (`devis_id`) REFERENCES `devis` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `devis_acceptations_ibfk_2` FOREIGN KEY (`solution_choisie_id`) REFERENCES `devis_solutions` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `devis_logs`
--
ALTER TABLE `devis_logs`
  ADD CONSTRAINT `devis_logs_ibfk_1` FOREIGN KEY (`devis_id`) REFERENCES `devis` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `devis_notifications`
--
ALTER TABLE `devis_notifications`
  ADD CONSTRAINT `devis_notifications_ibfk_1` FOREIGN KEY (`devis_id`) REFERENCES `devis` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `devis_pannes`
--
ALTER TABLE `devis_pannes`
  ADD CONSTRAINT `devis_pannes_ibfk_1` FOREIGN KEY (`devis_id`) REFERENCES `devis` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `devis_solutions`
--
ALTER TABLE `devis_solutions`
  ADD CONSTRAINT `devis_solutions_ibfk_1` FOREIGN KEY (`devis_id`) REFERENCES `devis` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `devis_solutions_items`
--
ALTER TABLE `devis_solutions_items`
  ADD CONSTRAINT `devis_solutions_items_ibfk_1` FOREIGN KEY (`solution_id`) REFERENCES `devis_solutions` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `employee_schedules`
--
ALTER TABLE `employee_schedules`
  ADD CONSTRAINT `employee_schedules_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `garanties`
--
ALTER TABLE `garanties`
  ADD CONSTRAINT `garanties_ibfk_1` FOREIGN KEY (`reparation_id`) REFERENCES `reparations` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `gardiennage`
--
ALTER TABLE `gardiennage`
  ADD CONSTRAINT `gardiennage_ibfk_1` FOREIGN KEY (`reparation_id`) REFERENCES `reparations` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `gardiennage_notifications`
--
ALTER TABLE `gardiennage_notifications`
  ADD CONSTRAINT `gardiennage_notifications_ibfk_1` FOREIGN KEY (`gardiennage_id`) REFERENCES `gardiennage` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `help_requests`
--
ALTER TABLE `help_requests`
  ADD CONSTRAINT `help_requests_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `help_requests_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Contraintes pour la table `historique_gains`
--
ALTER TABLE `historique_gains`
  ADD CONSTRAINT `historique_gains_ibfk_1` FOREIGN KEY (`mission_id`) REFERENCES `missions` (`id`);

--
-- Contraintes pour la table `historique_soldes`
--
ALTER TABLE `historique_soldes`
  ADD CONSTRAINT `historique_soldes_ibfk_1` FOREIGN KEY (`partenaire_id`) REFERENCES `fournisseurs` (`id`),
  ADD CONSTRAINT `historique_soldes_ibfk_2` FOREIGN KEY (`transaction_id`) REFERENCES `transactions_partenaires` (`id`);

--
-- Contraintes pour la table `kb_articles`
--
ALTER TABLE `kb_articles`
  ADD CONSTRAINT `kb_articles_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `kb_categories` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `kb_article_ratings`
--
ALTER TABLE `kb_article_ratings`
  ADD CONSTRAINT `kb_article_ratings_ibfk_1` FOREIGN KEY (`article_id`) REFERENCES `kb_articles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `kb_article_ratings_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `kb_article_tags`
--
ALTER TABLE `kb_article_tags`
  ADD CONSTRAINT `kb_article_tags_ibfk_1` FOREIGN KEY (`article_id`) REFERENCES `kb_articles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `kb_article_tags_ibfk_2` FOREIGN KEY (`tag_id`) REFERENCES `kb_tags` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `lecture_annonces`
--
ALTER TABLE `lecture_annonces`
  ADD CONSTRAINT `lecture_annonces_ibfk_1` FOREIGN KEY (`message_id`) REFERENCES `messages` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `lecture_annonces_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `lignes_commande_fournisseur`
--
ALTER TABLE `lignes_commande_fournisseur`
  ADD CONSTRAINT `lignes_commande_fournisseur_ibfk_1` FOREIGN KEY (`commande_id`) REFERENCES `commandes_fournisseurs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `lignes_commande_fournisseur_ibfk_2` FOREIGN KEY (`produit_id`) REFERENCES `produits` (`id`);

--
-- Contraintes pour la table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `message_attachments`
--
ALTER TABLE `message_attachments`
  ADD CONSTRAINT `message_attachments_ibfk_1` FOREIGN KEY (`message_id`) REFERENCES `messages` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `message_reactions`
--
ALTER TABLE `message_reactions`
  ADD CONSTRAINT `message_reactions_ibfk_1` FOREIGN KEY (`message_id`) REFERENCES `messages` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `message_reactions_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `message_reads`
--
ALTER TABLE `message_reads`
  ADD CONSTRAINT `message_reads_ibfk_1` FOREIGN KEY (`message_id`) REFERENCES `messages` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `message_reads_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `message_replies`
--
ALTER TABLE `message_replies`
  ADD CONSTRAINT `message_replies_ibfk_1` FOREIGN KEY (`message_id`) REFERENCES `messages` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `message_replies_ibfk_2` FOREIGN KEY (`reply_to_id`) REFERENCES `messages` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `missions`
--
ALTER TABLE `missions`
  ADD CONSTRAINT `missions_ibfk_1` FOREIGN KEY (`mission_type_id`) REFERENCES `mission_types` (`id`);

--
-- Contraintes pour la table `mission_validations`
--
ALTER TABLE `mission_validations`
  ADD CONSTRAINT `mission_validations_ibfk_1` FOREIGN KEY (`user_mission_id`) REFERENCES `user_missions` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `mouvements_stock`
--
ALTER TABLE `mouvements_stock`
  ADD CONSTRAINT `mouvements_stock_fournisseur_fk` FOREIGN KEY (`fournisseur_id`) REFERENCES `fournisseurs` (`id`),
  ADD CONSTRAINT `mouvements_stock_ibfk_1` FOREIGN KEY (`produit_id`) REFERENCES `produits` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `mouvements_stock_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Contraintes pour la table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Contraintes pour la table `paid_leave_balance`
--
ALTER TABLE `paid_leave_balance`
  ADD CONSTRAINT `paid_leave_balance_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `parrainage_reductions`
--
ALTER TABLE `parrainage_reductions`
  ADD CONSTRAINT `parrainage_reductions_ibfk_1` FOREIGN KEY (`parrain_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `parrainage_reductions_ibfk_2` FOREIGN KEY (`reparation_utilisee_id`) REFERENCES `reparations` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `parrainage_relations`
--
ALTER TABLE `parrainage_relations`
  ADD CONSTRAINT `parrainage_relations_ibfk_1` FOREIGN KEY (`parrain_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `parrainage_relations_ibfk_2` FOREIGN KEY (`filleul_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `partner_transactions_pending`
--
ALTER TABLE `partner_transactions_pending`
  ADD CONSTRAINT `partner_transactions_pending_ibfk_1` FOREIGN KEY (`partenaire_id`) REFERENCES `partenaires` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `photos_reparation`
--
ALTER TABLE `photos_reparation`
  ADD CONSTRAINT `photos_reparation_ibfk_1` FOREIGN KEY (`reparation_id`) REFERENCES `reparations` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `pieces_avancees`
--
ALTER TABLE `pieces_avancees`
  ADD CONSTRAINT `pieces_avancees_ibfk_1` FOREIGN KEY (`partenaire_id`) REFERENCES `fournisseurs` (`id`),
  ADD CONSTRAINT `pieces_avancees_ibfk_2` FOREIGN KEY (`piece_id`) REFERENCES `produits` (`id`);

--
-- Contraintes pour la table `pieces_utilisees_reparations`
--
ALTER TABLE `pieces_utilisees_reparations`
  ADD CONSTRAINT `pieces_utilisees_reparations_ibfk_1` FOREIGN KEY (`reparation_id`) REFERENCES `reparations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pieces_utilisees_reparations_ibfk_2` FOREIGN KEY (`produit_id`) REFERENCES `produits` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `presence_comments`
--
ALTER TABLE `presence_comments`
  ADD CONSTRAINT `presence_comments_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `presence_events` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `presence_comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `presence_events`
--
ALTER TABLE `presence_events`
  ADD CONSTRAINT `presence_events_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `presence_events_ibfk_2` FOREIGN KEY (`type_id`) REFERENCES `presence_types` (`id`) ON DELETE RESTRICT;

--
-- Contraintes pour la table `presence_history`
--
ALTER TABLE `presence_history`
  ADD CONSTRAINT `presence_history_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `presence_events` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `presence_history_ibfk_2` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `produits`
--
ALTER TABLE `produits`
  ADD CONSTRAINT `produits_fournisseur_fk` FOREIGN KEY (`fournisseur_id`) REFERENCES `fournisseurs` (`id`),
  ADD CONSTRAINT `produits_ibfk_1` FOREIGN KEY (`categorie_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `push_subscriptions`
--
ALTER TABLE `push_subscriptions`
  ADD CONSTRAINT `push_subscriptions_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `rachat_appareils`
--
ALTER TABLE `rachat_appareils`
  ADD CONSTRAINT `rachat_appareils_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`);

--
-- Contraintes pour la table `reclamations_garantie`
--
ALTER TABLE `reclamations_garantie`
  ADD CONSTRAINT `reclamations_garantie_ibfk_1` FOREIGN KEY (`garantie_id`) REFERENCES `garanties` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reclamations_garantie_ibfk_2` FOREIGN KEY (`reparation_id`) REFERENCES `reparations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reclamations_garantie_ibfk_3` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reclamations_garantie_ibfk_4` FOREIGN KEY (`nouvelle_reparation_id`) REFERENCES `reparations` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `reclamations_garantie_ibfk_5` FOREIGN KEY (`employe_traite_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `reparations`
--
ALTER TABLE `reparations`
  ADD CONSTRAINT `fk_reparation_employe` FOREIGN KEY (`employe_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `reparations_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`),
  ADD CONSTRAINT `reparations_ibfk_2` FOREIGN KEY (`parrain_id`) REFERENCES `clients` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `reparations_ibfk_3` FOREIGN KEY (`garantie_id`) REFERENCES `garanties` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `reparation_attributions`
--
ALTER TABLE `reparation_attributions`
  ADD CONSTRAINT `fk_attribution_reparation` FOREIGN KEY (`reparation_id`) REFERENCES `reparations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_attribution_user` FOREIGN KEY (`employe_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `reparation_logs`
--
ALTER TABLE `reparation_logs`
  ADD CONSTRAINT `fk_log_reparation` FOREIGN KEY (`reparation_id`) REFERENCES `reparations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_log_user` FOREIGN KEY (`employe_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `reparation_sms`
--
ALTER TABLE `reparation_sms`
  ADD CONSTRAINT `reparation_sms_ibfk_1` FOREIGN KEY (`reparation_id`) REFERENCES `reparations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reparation_sms_ibfk_2` FOREIGN KEY (`template_id`) REFERENCES `sms_templates` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reparation_sms_ibfk_3` FOREIGN KEY (`statut_id`) REFERENCES `statuts` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `retours`
--
ALTER TABLE `retours`
  ADD CONSTRAINT `retours_ibfk_1` FOREIGN KEY (`produit_id`) REFERENCES `stock` (`id`),
  ADD CONSTRAINT `retours_ibfk_2` FOREIGN KEY (`colis_id`) REFERENCES `colis_retour` (`id`);

--
-- Contraintes pour la table `scheduled_notifications`
--
ALTER TABLE `scheduled_notifications`
  ADD CONSTRAINT `scheduled_notifications_created_by_fk` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `scheduled_notifications_target_user_fk` FOREIGN KEY (`target_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `services_partenaires`
--
ALTER TABLE `services_partenaires`
  ADD CONSTRAINT `services_partenaires_ibfk_1` FOREIGN KEY (`partenaire_id`) REFERENCES `fournisseurs` (`id`);

--
-- Contraintes pour la table `sms_campaigns`
--
ALTER TABLE `sms_campaigns`
  ADD CONSTRAINT `sms_campaigns_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `sms_campaign_details`
--
ALTER TABLE `sms_campaign_details`
  ADD CONSTRAINT `sms_campaign_details_ibfk_1` FOREIGN KEY (`campaign_id`) REFERENCES `sms_campaigns` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sms_campaign_details_ibfk_2` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `sms_template`
--
ALTER TABLE `sms_template`
  ADD CONSTRAINT `sms_template_ibfk_1` FOREIGN KEY (`statut_id`) REFERENCES `statuts` (`id`);

--
-- Contraintes pour la table `sms_templates`
--
ALTER TABLE `sms_templates`
  ADD CONSTRAINT `sms_templates_ibfk_1` FOREIGN KEY (`statut_id`) REFERENCES `statuts` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `soldes_partenaires`
--
ALTER TABLE `soldes_partenaires`
  ADD CONSTRAINT `fk_soldes_partenaires` FOREIGN KEY (`partenaire_id`) REFERENCES `partenaires` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `statuts`
--
ALTER TABLE `statuts`
  ADD CONSTRAINT `statuts_ibfk_1` FOREIGN KEY (`categorie_id`) REFERENCES `statut_categories` (`id`);

--
-- Contraintes pour la table `statuts_reparation`
--
ALTER TABLE `statuts_reparation`
  ADD CONSTRAINT `statuts_reparation_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Contraintes pour la table `stock_history`
--
ALTER TABLE `stock_history`
  ADD CONSTRAINT `stock_history_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `stock` (`id`);

--
-- Contraintes pour la table `taches`
--
ALTER TABLE `taches`
  ADD CONSTRAINT `taches_ibfk_1` FOREIGN KEY (`employe_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `taches_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Contraintes pour la table `tache_attachments`
--
ALTER TABLE `tache_attachments`
  ADD CONSTRAINT `tache_attachments_ibfk_1` FOREIGN KEY (`tache_id`) REFERENCES `taches` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tache_attachments_ibfk_2` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `tasks`
--
ALTER TABLE `tasks`
  ADD CONSTRAINT `tasks_ibfk_1` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `tasks_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Contraintes pour la table `task_logs`
--
ALTER TABLE `task_logs`
  ADD CONSTRAINT `fk_task_log_tache` FOREIGN KEY (`tache_id`) REFERENCES `taches` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_task_log_user` FOREIGN KEY (`employe_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `time_slots`
--
ALTER TABLE `time_slots`
  ADD CONSTRAINT `time_slots_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `time_tracking`
--
ALTER TABLE `time_tracking`
  ADD CONSTRAINT `time_tracking_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `transactions_partenaires`
--
ALTER TABLE `transactions_partenaires`
  ADD CONSTRAINT `fk_transactions_partenaires` FOREIGN KEY (`partenaire_id`) REFERENCES `partenaires` (`id`);

--
-- Contraintes pour la table `typing_status`
--
ALTER TABLE `typing_status`
  ADD CONSTRAINT `typing_status_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `typing_status_ibfk_2` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `user_missions`
--
ALTER TABLE `user_missions`
  ADD CONSTRAINT `user_missions_ibfk_1` FOREIGN KEY (`mission_id`) REFERENCES `missions` (`id`) ON DELETE CASCADE;

DELIMITER $$
--
-- Évènements
--
CREATE DEFINER=`root`@`localhost` EVENT `clean_cache_hourly` ON SCHEDULE EVERY 1 HOUR STARTS '2025-11-06 02:10:03' ON COMPLETION NOT PRESERVE ENABLE DO CALL clean_expired_cache()$$

DELIMITER ;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
