-- phpMyAdmin SQL Dump
-- version 5.2.1deb3
-- https://www.phpmyadmin.net/
--
-- Hôte : localhost:3306
-- Généré le : mar. 16 déc. 2025 à 01:27
-- Version du serveur : 8.0.44-0ubuntu0.24.04.2
-- Version de PHP : 8.3.6

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `geekboard_mdg`
--
CREATE DATABASE IF NOT EXISTS `geekboard_mdg` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
USE `geekboard_mdg`;

-- --------------------------------------------------------

--
-- Structure de la table `ai_expert_profiles`
--

CREATE TABLE `ai_expert_profiles` (
  `id` int NOT NULL,
  `profile_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `expertise` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `bug_reports`
--

CREATE TABLE `bug_reports` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `page_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `priorite` enum('basse','moyenne','haute','critique') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'basse',
  `status` enum('nouveau','en_cours','resolu','ferme') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'nouveau',
  `date_creation` datetime DEFAULT CURRENT_TIMESTAMP,
  `date_resolution` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  `google_api_key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Clé API Google Custom Search',
  `google_search_engine_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'ID du moteur de recherche Google personnalisé',
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
  `nom` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `clients`
--

CREATE TABLE `clients` (
  `id` int NOT NULL,
  `nom` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `prenom` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `telephone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `inscrit_parrainage` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Client inscrit au programme de parrainage ou non',
  `code_parrainage` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Code unique pour le parrainage (peut être null si pas inscrit)',
  `date_inscription_parrainage` timestamp NULL DEFAULT NULL COMMENT 'Date d''inscription au programme de parrainage'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `colis_retour`
--

CREATE TABLE `colis_retour` (
  `id` int NOT NULL,
  `numero_suivi` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_creation` datetime DEFAULT CURRENT_TIMESTAMP,
  `date_expedition` datetime DEFAULT NULL,
  `statut` enum('en_preparation','en_expedition','livre') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'en_preparation',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `commandes_fournisseurs`
--

CREATE TABLE `commandes_fournisseurs` (
  `id` int NOT NULL,
  `fournisseur_id` int NOT NULL,
  `date_commande` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `statut` enum('en_attente','validee','recue','annulee') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'en_attente',
  `montant_total` decimal(10,2) DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `user_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `commandes_pieces`
--

CREATE TABLE `commandes_pieces` (
  `id` int NOT NULL,
  `reference` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `reparation_id` int DEFAULT NULL,
  `client_id` int DEFAULT NULL,
  `fournisseur_id` int NOT NULL,
  `nom_piece` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `code_barre` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `quantite` int NOT NULL DEFAULT '1',
  `prix_estime` decimal(10,2) DEFAULT NULL,
  `commentaire_interne` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `note_interne` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `urgence` enum('normal','urgent','tres_urgent') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'normal',
  `statut` enum('en_attente','commande','recue','annulee','urgent','termine','utilise','a_retourner') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'en_attente',
  `date_commande` datetime DEFAULT NULL,
  `date_reception` datetime DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `date_creation` datetime DEFAULT CURRENT_TIMESTAMP,
  `date_modification` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `commentaires_tache`
--

CREATE TABLE `commentaires_tache` (
  `id` int NOT NULL,
  `tache_id` int NOT NULL,
  `user_id` int NOT NULL,
  `commentaire` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
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
  `company_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `company_phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `company_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `company_email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `company_address` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `company_hours` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `company_logo` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
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
  `statut` enum('en_attente','approuve','refuse') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'en_attente',
  `type` enum('normal','impose') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'normal',
  `commentaire` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
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
  `titre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('direct','groupe','annonce') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'direct',
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
  `role` enum('admin','membre','lecteur') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'membre',
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
  `numero_devis` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `titre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description_generale` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `statut` enum('brouillon','envoye','accepte','refuse','expire') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'brouillon',
  `date_creation` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `date_envoi` timestamp NULL DEFAULT NULL,
  `date_reponse` timestamp NULL DEFAULT NULL,
  `date_expiration` timestamp NULL DEFAULT NULL,
  `lien_securise` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `total_ht` decimal(10,2) DEFAULT '0.00',
  `taux_tva` decimal(5,2) DEFAULT '20.00',
  `total_ttc` decimal(10,2) DEFAULT '0.00',
  `solution_choisie_id` int DEFAULT NULL,
  `notes_acceptation` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `ip_client` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `devis`
--

INSERT INTO `devis` (`id`, `reparation_id`, `client_id`, `employe_id`, `numero_devis`, `titre`, `description_generale`, `statut`, `date_creation`, `date_envoi`, `date_reponse`, `date_expiration`, `lien_securise`, `total_ht`, `taux_tva`, `total_ttc`, `solution_choisie_id`, `notes_acceptation`, `ip_client`, `user_agent`) VALUES
(1, 1621, 1228, 162, 'DV-2025-0001', 'Garde boue', 'Remplacement garde boue + lumière arriere', 'envoye', '2025-12-12 10:06:01', '2025-12-12 10:06:01', NULL, '2025-12-25 23:00:00', '8d9583d11a3750e3cf40bc831c991215', 59.99, 20.00, 71.99, NULL, NULL, NULL, NULL),
(2, 1721, 1298, 162, 'DV-2025-0002', 'reparation iphone 12', 'SN : 1234567890', 'accepte', '2025-12-12 22:44:12', '2025-12-12 22:44:12', '2025-12-12 22:47:32', '2025-12-25 23:00:00', 'd1f91a482f44d7b6e097bf9bd341f855', 218.00, 20.00, 261.60, 3, NULL, NULL, NULL);

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
  `signature_client` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Signature électronique en base64',
  `nom_complet` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telephone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `adresse` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `date_acceptation` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `ip_client` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `conditions_acceptees` tinyint(1) DEFAULT '1',
  `newsletter_acceptee` tinyint(1) DEFAULT '0',
  `hash_verification` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Hash pour vérifier l intégrité'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `devis_acceptations`
--

INSERT INTO `devis_acceptations` (`id`, `devis_id`, `solution_choisie_id`, `signature_client`, `nom_complet`, `email`, `telephone`, `adresse`, `date_acceptation`, `ip_client`, `user_agent`, `conditions_acceptees`, `newsletter_acceptee`, `hash_verification`) VALUES
(1, 2, 3, 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAASwAAADICAYAAABS39xVAAAQAElEQVR4AeydP48dRbqH7UXL3gvL+Gq9Wmkl23wBm4AQW1gEOOML2EJCpDgisXECEdgJGTkB4wQiEiQTIBBDSIK/ABhdpNXlrswurGD/zPYDKm+fmuo+fU7/OVXdD3LRVdXVb7311PRv3upTp+dXh4eH/2uSgT8D/gyU8DPwq2P+JwEJSKAQAgpWIROlmxKQwLFjCtaCfgocqgRKJ6BglT6D+i+BBRFQsBY02Q5VAqUTULBKn0H9l0CKwEzrFKyZTqzDksAcCShYc5xVxySBmRJQsGY6sQ5LAnMkoGClZtU6CUggSwIKVpbTolMSkECKgIKVomKdBCSQJQEFK8tp0anpCNhTSQQUrJJmS18lsHACCtbCfwAcvgRKIqBglTRb+iqBhRPoKVgLp+fwJSCBSQkoWJPitjMJSKAPAQWrDz2vlYAEJiWgYE2Ku+jOdF4COyegYO18CnRAAhLoSkDB6krKdhKQwM4JKFg7nwIdkEB+BHL1SMHKdWb0SwISOEJAwTqCxAoJSCBXAgpWrjOjXxKQwBECCtYRJP0rtCABCYxDQMEah6tWJSCBEQgoWCNA1aQEJDAOAQVrHK5aXQoBxzkpAQVrUtx2JgEJ9CGgYPWh57USkMCkBBSsSXHbmQQk0IfAbgWrj+deKwEJLI6AgrW4KXfAEiiXgIJV7tzpuQQWR0DBWtyU72rA9iuB/gQUrP4MtSABCUxEQMGaCLTdSEAC/QkoWP0ZakECElglMFpJwRoNrYYlIIGhCShYQxPVngQkMBoBBWs0tBqWgASGJqBgDU20vz0tSEACDQQUrAYwVktAAvkRULDymxM9koAEGggoWA1gcq7+8MMPHz5//vzJ48eP/5FE/saNG7+9devWozn7rW9HCVizGQEFazNeO2+NMF26dOnkZ5999nBwhvwbb7zx2PXr1/cQsCtXrpwI5zxKYE4EFKyCZpNICmFa5/Lt27cfQdjWtfO8BEojoGAVMmNETkRSXd1F2BStrrRsVwqBogWrFMh9/bxw4cLvtrGhaG1DzWtyJqBg5Tw7lW9ESQcHB7+pslv9Q7R4SL/VxV4kgcwIKFiZTUjsDoIT14XynTt3vj08PPzm8uXLP4S61JGH9IpWiox1pRFQsDKeMaKrJvcQq2efffYnzu/v79+nTL4pFS9aTQOzflEEFKyMp7spunrllVf+EsQquE+Z+lBOHRWtFBXrSiKgYGU6W03RFaL0+uuv/zXlNvWcT50Lda+99tpjIe9RAqURULAynbFUdPXUU0/9hCi1ucz5NtFia8SNGzd+22bDcxLYLYHm3hWsZjbZnbl48eKPXZxaJ1qIoQ/hu5C0TW4EFKzcZqTyp0lMEKLqdKd/tG2LtFwadsJoo8wIKFiZTQjufPTRRw++J0iZxHKQ4yapTbRYGjYJ4yZ92FYCUxJQsKak3aOvrsvBY8dWO2kTLaOsVVaW8iegYGU4R++8884jsVtffvnlQ3Fd1zKilYrQiLK62rCdBHIgoGDlMAuRDydOnPhXVHWsj2Bhq4qm/sIxTi4LYyKWcyagYGU4O3t7e4exW6+++mpy71XcrqnctLG0EjL3ZTVBy79+cR4qWIVMOYLT19XU0tBlYV+qXj8lAQVrStpb9pV6/rSlqWNVRHVkaeirlbel6XVTE1Cwpia+4/6I1E6dOvXPuhtvvfWW74KvAzGfLYElC1a2kzK2Y1evXv2+3se9e/ce8uF7nYj5XAkoWBnODAJSd2vo50zXrl37Pl5mVktFH77XoZvPkoCCleG0nDlz5h+xW0NHQJVArTzLQhSH7iMeg2UJ9CWgYPUlOML1h4eHx0cwu2KSZ1lLirJWBm+hWAIKVoZTR7QTu5X6fmHcZtPywcHBt/Vr6NdXz9SJmM+NgIKV24w0+PPxxx9v/YcoGkz+XB2/0YFXz/x8wv9JIEMCClZmkzL1cyQ2k8YIjLJiIpZzIdBJsHJxdgl+NC39WK6NNf5UlDW1cI41Nu3Oi4CCNa/53Go0RFk+gN8KnRdNTEDBmhh4n+7GjHrc5tBnZrx2KgIK1lSkO/Yz1sP1dd0/2OZQa1iJmJtJazzM7p6AgrX7OcjGg0qgjmwm9QF8NtOjIxUBBauC4L9fCBBlpR7A/3LW/0tg9wQUrN3PwYoHbZ8GNn2CuGKgZyH1AL4pyuKZGun8+fMnSbRLpZ4uefloBMozrGCVN2ejexwvDdlMijDVO0agLl26dJKEyJJol0pnzpz5Q/1a8xLYloCCtS25GV/H0rBpmwPCdfz48T8iUF0R8PaJK1eunOja3nYSaCKgYDWRWXh9HGUhUOfOnfs9EdU2aG7fvv0IYrfNtV4jgUBAwQokNj7O+4JUlHX37t1f9xk1Yqdo9SHotQpWZj8Dp0+fXnl9cd29vn/qq26rSz6Osrpcs66NorWOkOfbCChYbXR2cO7+/fujvwur67C6fCrJs647d+58G6ezZ8/+vamfSgjdkNoEx/pWAgpWK57pT6b+JmHw4oUXXvhbyI99ZOnGJ35t/bBni3dqsXyM05tvvvld07U8D8N+0/kM63UpEwIKViYTEdw4c+ZM45IQUQjtxj6ui4KIqNiz1eQHvtKGCKypjfUS2JSAgrUpsZHbX7x48ceRu1hrnj1WREFrG65pgGhVwrfydZ9wSVXvsjDA8NiZgILVGdVuG04VqXQVqxdffPF/uhBBtKbyvYs/timbwBSCVTahBXnfVaxA8vXXX/f6W4ZDRHD4YVoWAQUrs/l+5plnfkq5NPZScZ1Y8YA9/uTPZV1qpqwbk4CCNSbdLWx32UqwhdnWS7qIFQ/Yv/jii/+rGyJK6vJpX5PY3rp169G6PfMSWEdAwVpHaOLzn3zyycOpLsfaNMrbFRCeVJ/UEVkhVuRJlDmGFEdZob5+PHHixGG9HPI57TkLPnnMm0CRgsVvdW40EnkiBBJlUt7It/NuDMGCVdteK8SpLlZ4HpcRO+xwrildu3bt+9S5Xb1dNeWLdWUQKE6w+NY/X+/gRiOR56YhUSatu4Fynprnnnsuua2hqb7PWGDVdH1KrEJbzoU8R+zwi4N8U4qvoR1zxtEkga4EihMsvvW/bnDcQLwCpUThIhqposUV0aJM/bpxb3K+jQ0bPuNIqm6bc/FWhXXbHJo+TKjbNV8YgR24W5RgEV1twgjhunDhwu82uSaHtp9++un/37x58zuEiiPlIf1CrGCTsolYsXcqda5eFz9IZ5tD2/w02VwXmdX7NC+BogTr8ccfb/zaStNUHhwc/IZoq+l8rvVEVAgVx6l87CpW+EOUxbGe1kW/e3t7/6q3J//555/3emUNNkzLIVCUYDVFBV2mC9EisujSds5tYJDiyDOmpiioiQcRYHwO+3FdKFeCdeTTQj8pDHQ8diFQjGA13QiXL1/+4fDw8Buig3UD5kZtsrPu2sHPZ2QQsUpFTOtcJAKM28C4aZmX+mK3nxTGBC23EShGsLgRUgPZ39+/Tz3RAcKFgFFuStipIoOTTefnXs/462PcVqyCDa4P+XDkk9uUaKU+FUzVBTseJRATKEKwUj/8DCR1syBg60SLm2SJkVbqofg2kRXsQ2q6/uWXX94LbTxKYCgCRQhW09dVmm4WRCslZnVoRBqpG7jeZk55BDp+KL6OUdfxnzp16siHIbz/vekXTWy3a7v4unLKejoUgSIEKzXYeB9Q3AYxY4nYdlNyAy/hZkGsEOg6I6JQGNXrts1fvXo1uZO9y9d2tu3T65ZJoAjB6vNglpuyTbR43sINPefpj8UKsScKHWrMbL3AZmxvqUvvmIPl4QgUIVh9h7tOtLih5ypaqQ8Yqsgn+RbQPpybbMJ2CVFsH3Ze251AAYLVfTBtLREtlkFNbbixTp8+/Yem8yXWI8JEOXXfiTb5RLVeN0Qem9hO2eJrO22ixbWp66yTQEygCMGKvwYSD6JrmWVQ002FDb5eMpcNpogVIsy4QmLsCHcoD31ssg3Xd99997+G7k97yyNQhGANOS3cVNy4bTa50bnh29rkfA7fGUPdR8bM2Ot1Y+SbNvB+8MEHCtYYwBdmswjBSn3TP17qbDJv3LhNN1awww2fev4Tzud6TIkVD8QZ8xQ+s7zbS3xnkCirQ/82kUArgSIEa4wvyHJjse2hEqWVV7nUaSGKJS0RU2LFeA4ODr7lOFV67733/ty1r9Qerq7X2m55BIoQrCeffPLv8dS0CU3ctq3M9+HW2SLayl24msRqXSTZxmbbc/wyIKrrcv3zzz//Q5d2tpEABIoQLByN09NPP5386zJxuy5lRItnPOvaBuHKbYd8k1gRQSIe68Y1xvmmbQ5j9KXNMgls43URgpW66VLPtbYBEK7hGQ/RSJfIgB3yIeLi4/qQEI4qWjtJIk/iXOhjjCN9IKSxbcYS101ZZs66sBx6Hqcco31NT6AIwQJLlx9+2vVJ3GQ87+kSbdEPQsFO+ZAo89yLRJ7EuSBuRGakoUQMW/SBL/WEWDGWet0u8rBsmzc45+DnLtjY53YEihGse/fuPVQf4ttvv/3f9fKQeaItllPcUEPZRViIzEiIGGLTxzaRFbbqNhCHXMQq+IVoNW3YhXNo51ECXQgUI1iVgKyMZ4w/e7XSQVXghhpStCqTD/4hNiHyelDZMYNYIYBxc54b5RixsGG3WiavfBqLsMb+W5bAOgLFCFb8adKQD93bICFalVh+0xQltF3b5RzCU93MJ7suE5vECgHIUawCAz7YwEcSPHP2NfjsMT8CxQhW/HC2zxsctpkGogRutDGEi2deLBMRozbfOI/A1dvkuAys+1fPI1Kkep15CWxCoBjB2mRQY7YNwsVSkRT3hYCQ4vouZcQIUUq1pZ7z8blcl4Gxn5Z7EPDSBwSKEaz4NzNRSddl1IPRDphhqUgi6iKFpQ4PmUnUkRA1EiLWZVc3ohSPiyUj9XX3sYf9mEu9jXkJzI1AMYIFeG5SjiE1vTo5nJ/y2CQciBoJEas+6fwT4rXOrypqeow2CBdihThTDgkO2AtljxJYCoGiBKu6kVdePBdHHSVMGuJFZNQmXAgU2x54rkW+Pi7Fqk7D/NIIFCVYRDHcsPVJ4tlOvXwkn2kFwtUmWmx7iF2nvZFVTMXykggUJVhMzByiLMZBWidatAkJsaJ9KHuUwBIJFCdYc4qy+IFDhM6ePXvkbRScC0mxCiQ8Lp1AcYLFhKWiLB5Qc67E9MQTTzQK1s2bN79D1Eoc1/Q+2+PcCRQpWERZp0+f/md9cioR+/mTtXpdCflz5879PvW8Kvj+/vvv+2rhAMPj4gkUKVjM2ksvvbTyxzv5NK2kB/D4yncJ7969+2vGY5KABNYTKFawUn+8k20OCMH6Ye+2BXur8LWLFwhxl3a2kcASCNQEq7zhVsvAlX1ZjAAhyPV5Fn4RVaVEqBKxH7vshGeMJgkslUDRgsWzLD5BiyevErKsnmchVJUgnWQjaOwr+8r4Wg9vM/Avy8R0LEtglUDR2V6MLgAABI9JREFUgsVQ+ASNm558SEQwCEQo7+pYFyp8iv1AbNkIivByLv4ggTqTBCTwHwLFCxZDqSKqI0tDBGJXz7Nu3br1KIJJRIUf+FhPCCxRFWJbr28SLISv3m6AvCYkUCSBWQgWEQrRSjwDPM+aUrQQFrYpXL9+fS8lVPiHn/WoirqQLl68uPJWzlDvUQIS+IXALASLoRCtELmQr6epRAthJKJq2qaAb6moqu5r/JLCcC6nt1IEnzxKYBcEZiNYwEstDalHtFiikR86EVVhmz5StoNQNUVVqWusk8CQBOZka1aCxdKQKCY1QSzREBYEJnV+0zrsYI+oCtvx9TyP4ms1mwgV/sd2KE/9Omj6NEkgRwKzEiwAc9PznIh8nBAWBIblW3yua3mdUGGH/r/66qs/sbmVskkCEhiGwOwECyw8z0I0yKcSy7dNRCuIVFtERT9h+Uf/lLdJ2NjmOq+RwBIIzFKwmDhEo2l5yHlEi7d6km9KCFVdpIjQUm0RGfraZPmXskNd6pPCVB1tTRJYGoHZChYTyfKQ1xEjKJTjxFsS+KpMPdpCpBAy6lk+NokUtrA7lFBhj4TQ1qND8tRxziSBpROYtWCFySXy4cYP5fhItEUkRUKkELK4TSgjUtgaWqiCfY4IFEJLIk+dSQISOHZsEYLFRHPjIzTkU4lIipQ6Rx1CFUQKW0Rv1JtmRMChZE9gMYLFTCA0iA75LimIFJEOUZoi1YWabSQwHoFFCRYYER0E6PLlyz+kIq4gUpvuocK2SQISGJfA4gQr4Nzf379PxIV4EXWRyIdIyj1UgZRHCeRDYDjBymdMG3tC1EXa+EIvkIAEJiWgYE2K284kIIE+BBSsPvS8VgISmJSAgjUp7rl05jgksBsCCtZuuNurBCSwBQEFawtoXiIBCeyGgIK1G+72KoFSCGTlp4KV1XTojAQk0EZAwWqj4zkJSCArAgpWVtOhMxKQQBsBBauNTv9zWpCABAYkoGANCFNTEpDAuAQUrHH5al0CEhiQgII1IExNLZuAox+fgII1PmN7kIAEBiKgYA0EUjMSkMD4BBSs8RnbgwQkMBCBbARroPFoRgISmDEBBWvGk+vQJDA3AgrW3GbU8UhgxgQUrBlPbrZD0zEJbElAwdoSnJdJQALTE1CwpmdujxKQwJYEFKwtwXmZBCTQhcCwbRSsYXlqTQISGJGAgjUiXE1LQALDElCwhuWpNQlIYEQCCtaIcPub1oIEJFAnoGDVaZiXgASyJqBgZT09OicBCdQJKFh1GuYlsDsC9tyBgILVAZJNJCCBPAgoWHnMg15IQAIdCChYHSDZRAISyIPAXAQrD5p6IQEJjEpAwRoVr8YlIIEhCShYQ9LUlgQkMCoBBWtUvBofg4A2l0tAwVru3DtyCRRHQMEqbsp0WALLJaBgLXfuHbkE8icQeahgRUAsSkAC+RJQsPKdGz2TgAQiAgpWBMSiBCSQLwEFK9+56e+ZFiQwMwIK1swm1OFIYM4EFKw5z65jk8DMCChYM5tQh7NUAssYt4K1jHl2lBKYBQEFaxbT6CAksAwC/wYAAP///vPuDwAAAAZJREFUAwCzkSxJc0XKRwAAAABJRU5ErkJggg==', 'hammami naima', '', '33656880413', NULL, '2025-12-12 22:47:32', '84.98.112.56', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 1, 0, '44fe45336be043e9debc9275159ea5ef15e6985a78e016af418ac1126ee34c31');

-- --------------------------------------------------------

--
-- Structure de la table `devis_logs`
--

CREATE TABLE `devis_logs` (
  `id` int NOT NULL,
  `devis_id` int NOT NULL,
  `action` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `utilisateur_type` enum('employe','client','systeme') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `utilisateur_id` int DEFAULT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `donnees_supplementaires` json DEFAULT NULL,
  `date_action` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `devis_notifications`
--

CREATE TABLE `devis_notifications` (
  `id` int NOT NULL,
  `devis_id` int NOT NULL,
  `type` enum('envoi_devis','rappel','acceptation','refus') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `telephone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `statut_envoi` enum('en_attente','envoye','echec') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'en_attente',
  `date_programmee` timestamp NULL DEFAULT NULL,
  `date_envoi` timestamp NULL DEFAULT NULL,
  `erreur` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `tentatives` int DEFAULT '0',
  `date_creation` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `devis_notifications`
--

INSERT INTO `devis_notifications` (`id`, `devis_id`, `type`, `telephone`, `message`, `statut_envoi`, `date_programmee`, `date_envoi`, `erreur`, `tentatives`, `date_creation`) VALUES
(1, 1, 'envoi_devis', '330749936352', 'Bonjour, manuel, \nLe devis de votre joyor F3 est disponible. \nMontant : 71,99 €\n📄 Consultez votre devis ici :\n👉 https://mdg.servo.tools/pages/devis_client.php?lien=8d9583d11a3750e3cf40bc831c991215\n📲 Suivi réparation :\n👉 https://mdg.servo.tools/suivi.php?id=1621\nUne question ? Appelez-nous au 05 55 44 33 22\nMAISON DU GEEK', 'en_attente', '2025-12-12 10:06:02', NULL, NULL, 0, '2025-12-12 10:06:02'),
(2, 2, 'envoi_devis', '33656880413', 'Bonjour, naima, \nLe devis de votre iPhone 13 est disponible. \nMontant : 261,60 €\n📄 Consultez votre devis ici :\n👉 https://mdg.servo.tools/pages/devis_client.php?lien=d1f91a482f44d7b6e097bf9bd341f855\n📲 Suivi réparation :\n👉 https://mdg.servo.tools/suivi.php?id=1721\nUne question ? Appelez-nous au 05 55 44 33 22\nMAISON DU GEEK', 'en_attente', '2025-12-12 22:44:12', NULL, NULL, 0, '2025-12-12 22:44:12');

-- --------------------------------------------------------

--
-- Structure de la table `devis_pannes`
--

CREATE TABLE `devis_pannes` (
  `id` int NOT NULL,
  `devis_id` int NOT NULL,
  `titre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `gravite` enum('faible','moyenne','elevee','critique') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'moyenne',
  `ordre` int DEFAULT '1',
  `date_creation` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `devis_pannes`
--

INSERT INTO `devis_pannes` (`id`, `devis_id`, `titre`, `description`, `gravite`, `ordre`, `date_creation`) VALUES
(1, 1, 'Remplacement garde boue + lumière arriere', 'Remplacement garde boue + lumière arriere', 'moyenne', 1, '2025-12-12 10:06:01'),
(2, 2, 'Ecran casse', 'Ecran casse', 'moyenne', 1, '2025-12-12 22:44:12');

-- --------------------------------------------------------

--
-- Structure de la table `devis_solutions`
--

CREATE TABLE `devis_solutions` (
  `id` int NOT NULL,
  `devis_id` int NOT NULL,
  `nom` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `prix_total` decimal(10,2) NOT NULL,
  `duree_reparation` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `garantie` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `recommandee` tinyint(1) DEFAULT '0',
  `ordre` int DEFAULT '1',
  `date_creation` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `devis_solutions`
--

INSERT INTO `devis_solutions` (`id`, `devis_id`, `nom`, `description`, `prix_total`, `duree_reparation`, `garantie`, `recommandee`, `ordre`, `date_creation`) VALUES
(1, 1, 'Remplacement garde boue + lumière arriere', 'Garde boue\nPhare arriere', 59.99, '', '3 mois', 0, 1, '2025-12-12 10:06:02'),
(2, 2, 'Remplacement de l\'eran par un original', '', 129.00, '', '3 mois', 0, 1, '2025-12-12 22:44:12'),
(3, 2, 'Remplacement de l\'eran par un ecran generique', '', 89.00, '', '3 mois', 0, 2, '2025-12-12 22:44:12');

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
  `nom` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `quantite` int DEFAULT '1',
  `prix_unitaire` decimal(10,2) NOT NULL,
  `prix_total` decimal(10,2) NOT NULL,
  `type` enum('piece','main_oeuvre','autre') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'piece',
  `ordre` int DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `devis_templates`
--

CREATE TABLE `devis_templates` (
  `id` int NOT NULL,
  `nom` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('sms','email') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `sujet` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Pour les emails',
  `contenu` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `variables_disponibles` json DEFAULT NULL COMMENT 'Liste des variables utilisables',
  `actif` tinyint(1) DEFAULT '1',
  `date_creation` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modification` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `employee_notes`
--

CREATE TABLE `employee_notes` (
  `id` int NOT NULL,
  `employee_id` int NOT NULL COMMENT 'ID de l''employé concerné',
  `note_type` enum('avertissement','incident','appreciation','remarque','sanction','autre') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Type de note',
  `title` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Titre court de la note',
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Description détaillée',
  `date_incident` date DEFAULT NULL COMMENT 'Date de l''incident ou de la remarque',
  `severity` enum('info','low','medium','high','critical') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'info' COMMENT 'Niveau de gravité',
  `is_resolved` tinyint(1) DEFAULT '0' COMMENT '1 si le problème est résolu',
  `is_private` tinyint(1) DEFAULT '1' COMMENT '1 si visible uniquement par les admins',
  `include_in_ai_analysis` tinyint(1) DEFAULT '1' COMMENT '1 si inclus dans l''analyse IA',
  `created_by` int NOT NULL COMMENT 'ID de l''utilisateur qui a créé la note',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Notes contextuelles sur les employés pour analyses IA';

--
-- Déchargement des données de la table `employee_notes`
--

INSERT INTO `employee_notes` (`id`, `employee_id`, `note_type`, `title`, `description`, `date_incident`, `severity`, `is_resolved`, `is_private`, `include_in_ai_analysis`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 6, 'incident', 'Absent toute la journee', 'se permet de ne pas venir au travail pendant 1 journee sans prevenir ', '2025-12-01', 'medium', 0, 0, 0, 6, '2025-12-01 16:16:22', '2025-12-01 22:17:06');

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
  `nom` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `prenom` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `telephone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_embauche` date DEFAULT NULL,
  `statut` enum('actif','inactif') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'actif',
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
  `nom` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact_nom` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `url` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `adresse` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `fournisseurs`
--

INSERT INTO `fournisseurs` (`id`, `nom`, `contact_nom`, `email`, `url`, `adresse`, `created_at`) VALUES
(2, 'Utopya', NULL, NULL, 'https://mdgeek.top/i', NULL, '2025-03-28 18:58:21'),
(11, 'Mobilax', NULL, NULL, 'http://mobilax.fr', NULL, '2025-03-29 00:41:28'),
(16, 'AUTRE', NULL, 'aucun@fournisseur.com', NULL, NULL, '2025-12-15 04:39:06');

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
(7, 2135, '2025-11-06 21:45:46', '2026-02-04 21:45:46', 90, 'active', 'Garantie pièces et main d\'œuvre', NULL, '2025-11-06 21:45:46', '2025-11-06 21:45:46'),
(8, 1730, '2025-12-13 00:35:58', '2026-03-13 00:35:58', 90, 'active', 'Garantie pièces et main d\'œuvre', NULL, '2025-12-13 00:35:58', '2025-12-13 00:35:58');

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
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
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
  `type_notification` enum('sms','email','appel') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `statut` enum('envoyé','échec','annulé') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
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
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('en_attente','resolu','en_cours') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'en_attente',
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
  `action_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `target_id` int NOT NULL,
  `details` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `date_action` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `kb_articles`
--

CREATE TABLE `kb_articles` (
  `id` int NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `category_id` int NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `views` int NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `kb_articles`
--

INSERT INTO `kb_articles` (`id`, `title`, `content`, `category_id`, `created_at`, `updated_at`, `views`) VALUES
(1, 'Comment activer Windows et Microsoft Office', '<div class=\"html-content-wrapper\"><!doctype html>\\r\\n<html lang=\"fr\">\\r\\n<head>\\r\\n<meta charset=\"utf-8\" />\\r\\n<meta name=\"viewport\" content=\"width=device-width, initial-scale=1\" />\\r\\n<title>Tuto – Préparation PC Windows (exact)</title>\\r\\n<style>\\r\\n  :root {\\r\\n    --bg: #f6f7fb;\\r\\n    --panel: #ffffff;\\r\\n    --text: #0b0d14;\\r\\n    --muted: #55617a;\\r\\n    --accent: #1a66ff;\\r\\n    --ok: #1f9d63;\\r\\n    --border: rgba(10,13,20,0.08);\\r\\n  }\\r\\n  @media (prefers-color-scheme: dark) {\\r\\n    :root {\\r\\n      --bg: #0b0c10;\\r\\n      --panel: #11131a;\\r\\n      --text: #f0f0f0;\\r\\n      --muted: #a9b0c5;\\r\\n      --accent: #6aa5ff;\\r\\n      --ok: #77e39b;\\r\\n      --border: rgba(255,255,255,0.1);\\r\\n    }\\r\\n  }\\r\\n  body {\\r\\n    font-family: \\\'Segoe UI\\\', Roboto, sans-serif;\\r\\n    background-color: var(--bg);\\r\\n    color: var(--text);\\r\\n    line-height: 1.6;\\r\\n    padding: 40px;\\r\\n    max-width: 800px;\\r\\n    margin: auto;\\r\\n    transition: background-color 0.3s, color 0.3s;\\r\\n  }\\r\\n  h2 {\\r\\n    color: var(--ok);\\r\\n  }\\r\\n  .step {\\r\\n    background-color: var(--panel);\\r\\n    border: 1px solid var(--border);\\r\\n    border-radius: 10px;\\r\\n    padding: 20px;\\r\\n    margin-bottom: 20px;\\r\\n    box-shadow: 0 8px 20px rgba(0,0,0,0.1);\\r\\n    transition: background-color 0.3s, color 0.3s;\\r\\n  }\\r\\n  .sep {\\r\\n    text-align: center;\\r\\n    color: var(--muted);\\r\\n    margin: 20px 0;\\r\\n  }\\r\\n  p {\\r\\n    margin: 6px 0;\\r\\n  }\\r\\n  strong {\\r\\n    color: var(--accent);\\r\\n  }\\r\\n  .btn-print {\\r\\n    display: inline-block;\\r\\n    margin-top: 30px;\\r\\n    padding: 10px 16px;\\r\\n    background: var(--accent);\\r\\n    color: white;\\r\\n    border: none;\\r\\n    border-radius: 8px;\\r\\n    font-weight: 600;\\r\\n    cursor: pointer;\\r\\n    box-shadow: 0 4px 10px rgba(0,0,0,0.2);\\r\\n    transition: opacity 0.3s;\\r\\n  }\\r\\n  .btn-print:hover {\\r\\n    opacity: 0.85;\\r\\n  }\\r\\n</style>\\r\\n<style>\n:root {\n  --bg: #f8fafc !important;\n  --panel: #ffffff !important;\n  --text: #334155 !important;\n  --accent: #2563eb !important;\n  --ok: #16a34a !important;\n  --border: #e2e8f0 !important;\n}\nbody { \n  background-color: var(--bg) !important; \n  color: var(--text) !important;\n  font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, sans-serif !important;\n}\n.step {\n  border: 0 !important;\n  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06) !important;\n  border-radius: 12px !important;\n  padding: 1.5rem !important;\n  margin-bottom: 1.5rem !important;\n}\nh2 {\n  font-size: 1.25rem !important;\n  font-weight: 600 !important;\n  margin-bottom: 0.5rem !important;\n  display: flex; align-items: center; gap: 8px;\n}\n.btn-print {\n  background: var(--accent) !important;\n  border-radius: 8px !important;\n  padding: 12px 24px !important;\n  box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.2) !important;\n}\n</style></head>\\r\\n<body>\\r\\n\\r\\n<div class=\"step\">\\r\\n<h2>1. ✅ Vérifier la connexion Internet</h2>\\r\\n<p>• Vérifiez si l’ordinateur est connecté au WiFi.</p>\\r\\n<p>• Si ce n’est pas le cas : branchez un câble Ethernet à l’ordinateur pour obtenir une connexion Internet.</p>\\r\\n</div>\\r\\n<div class=\"sep\">⸻</div>\\r\\n\\r\\n<div class=\"step\">\\r\\n<h2>2. ✅ Sélectionner la version de Windows</h2>\\r\\n<p>• À l’apparition du popup GHOST MSG, sélectionnez une version de Windows.</p>\\r\\n<p>• Préférence : option 1.</p>\\r\\n</div>\\r\\n<div class=\"sep\">⸻</div>\\r\\n\\r\\n<div class=\"step\">\\r\\n<h2>3. ✅ Après le redémarrage</h2>\\r\\n<p>• Ouvrez Ghost Toolbox (icône sur le Bureau ou via menu démarrer).</p>\\r\\n</div>\\r\\n<div class=\"sep\">⸻</div>\\r\\n\\r\\n<div class=\"step\">\\r\\n<h2>4. ✅ Installer Google Chrome</h2>\\r\\n<p>• Dans Ghost Toolbox :</p>\\r\\n<p>• Option 51 → Internet Browser</p>\\r\\n<p>• Puis Option 4 → Google Chrome</p>\\r\\n<p>• Google Chrome va s’installer automatiquement.</p>\\r\\n</div>\\r\\n<div class=\"sep\">⸻</div>\\r\\n\\r\\n<div class=\"step\">\\r\\n<h2>5. ✅ Télécharger le pack d’activation</h2>\\r\\n<p>• Ouvrez Google Chrome.</p>\\r\\n<p>• Accédez à l’adresse suivante :</p>\\r\\n<p><strong>mdgeek.fr/pc.zip</strong></p>\\r\\n<p>• Téléchargez le fichier ZIP.</p>\\r\\n<p>• Extrayez le fichier ZIP sur le Bureau.</p>\\r\\n</div>\\r\\n<div class=\"sep\">⸻</div>\\r\\n\\r\\n<div class=\"step\">\\r\\n<h2>6. ✅ Activer Windows</h2>\\r\\n<p>• Dans le dossier extrait, ouvrez :</p>\\r\\n<p>• 1. Activation Windows</p>\\r\\n<p>• Lancez l’activation selon les instructions à l’écran.</p>\\r\\n</div>\\r\\n<div class=\"sep\">⸻</div>\\r\\n\\r\\n<div class=\"step\">\\r\\n<h2>7. ✅ Installer Office</h2>\\r\\n<p>• Toujours dans le dossier extrait :</p>\\r\\n<p>• Ouvrez le dossier 2. Oinstall.</p>\\r\\n<p>• Choisissez la langue française.</p>\\r\\n<p>• Cliquez sur “Installer” pour installer Microsoft Office.</p>\\r\\n</div>\\r\\n<div class=\"sep\">⸻</div>\\r\\n\\r\\n<div class=\"step\">\\r\\n<h2>8. ✅ Installer les logiciels de base avec Ninite</h2>\\r\\n<p>• Ouvrez l’application Ninite présente dans le pack téléchargé.</p>\\r\\n<p>• Patientez jusqu’à la fin complète des installations automatiques.</p>\\r\\n</div>\\r\\n<div class=\"sep\">⸻</div>\\r\\n\\r\\n<div class=\"step\">\\r\\n<h2>9. ✅ Configurer la langue et le clavier</h2>\\r\\n<p>• Ouvrez les Paramètres Windows.</p>\\r\\n<p>• Allez dans Heure et langue > Langue et région.</p>\\r\\n<p>• Ajoutez la langue française si elle n’est pas déjà installée.</p>\\r\\n<p>• Vérifiez que le clavier est bien en AZERTY.</p>\\r\\n</div>\\r\\n<div class=\"sep\">⸻</div>\\r\\n\\r\\n<div class=\"step\">\\r\\n<h2>✅ Votre PC est maintenant prêt avec Windows activé, Office installé, les logiciels essentiels et en français.</h2>\\r\\n</div>\\r\\n\\r\\n<button class=\"btn-print\" onclick=\"window.print()\">🖨️ Imprimer / Exporter en PDF</button>\\r\\n\\r\\n</body>\\r\\n</html></div>', 1, '2025-11-05 00:24:00', '2025-11-05 00:24:00', 28),
(2, 'Code Erreur Xiaomi M365', '<div class=\"kb-modern-wrapper\">\n<style>\n.kb-modern-wrapper { \n  font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, sans-serif; \n  color: #334155; \n  line-height: 1.6; \n  max-width: 100%; \n  margin: 0 auto; \n  background: #fff; \n  padding: 2rem; \n  border-radius: 12px; \n  box-shadow: 0 1px 3px rgba(0,0,0,0.1); \n  border: 1px solid #e2e8f0;\n}\n.kb-modern-wrapper h1 { \n  font-size: 1.8rem; \n  font-weight: 700;\n  color: #0f172a; \n  margin-top: 0;\n  margin-bottom: 1.5rem; \n  padding-bottom: 1rem; \n  border-bottom: 2px solid #f1f5f9; \n}\n.kb-modern-wrapper h2 { \n  font-size: 1.4rem; \n  font-weight: 600;\n  color: #1e293b; \n  margin-top: 2rem; \n  margin-bottom: 1rem; \n  display: flex; align-items: center; gap: 0.5rem;\n  background: #f8fafc;\n  padding: 0.75rem;\n  border-radius: 8px;\n  border-left: 4px solid #3b82f6;\n}\n.kb-modern-wrapper h3 { \n  font-size: 1.1rem; \n  font-weight: 600;\n  color: #475569; \n  margin-top: 1.5rem; \n  margin-bottom: 0.5rem;\n}\n.kb-modern-wrapper p { margin-bottom: 1rem; }\n.kb-modern-wrapper ul, .kb-modern-wrapper ol { padding-left: 1.5rem; margin-bottom: 1rem; }\n.kb-modern-wrapper li { margin-bottom: 0.5rem; }\n.kb-modern-wrapper code { \n  background: #eff6ff; \n  color: #1d4ed8; \n  padding: 0.2rem 0.4rem; \n  border-radius: 4px; \n  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; \n  font-size: 0.9em; \n  border: 1px solid #dbeafe;\n}\n.kb-modern-wrapper table { \n  width: 100%; \n  border-collapse: separate; \n  border-spacing: 0;\n  margin: 1.5rem 0; \n  font-size: 0.95rem; \n  border: 1px solid #e2e8f0;\n  border-radius: 8px;\n  overflow: hidden;\n}\n.kb-modern-wrapper th { \n  background: #f1f5f9; \n  color: #475569; \n  font-weight: 600; \n  text-align: left; \n  padding: 1rem; \n  border-bottom: 1px solid #e2e8f0; \n}\n.kb-modern-wrapper td { \n  padding: 1rem; \n  border-bottom: 1px solid #f1f5f9; \n  vertical-align: middle; \n}\n.kb-modern-wrapper tr:last-child td { border-bottom: none; }\n.kb-modern-wrapper tr:hover td { background: #f8fafc; }\n</style>\n<div class=\"kb-content-original\"><p>&lt;p&gt;Si tu souhaites directement consulter la correspondance de ton code erreur, tu peux regarder immédiatement dans la liste ci-dessous, ils sont tous répertoriés.&lt;br&gt;&lt;br&gt;Code erreur 10 : Défaut entre carte Bluetooth et carte mère.&lt;br&gt;Code erreur 11, 12, 13, 28, 29 : Défaut MosFET carte mère&lt;br&gt;Code erreur 14 : Défaut du levier de frein ou accélérateur&lt;br&gt;Code erreur 15 : Défaut poignée accélérateur ou levier de frein&lt;br&gt;Code erreur 18 : Défaut capteur hall moteur&lt;br&gt;Code erreur 21 : Défaut communication batterie&lt;br&gt;Code erreur 22, 23 : Défaut numéro de série BMS&lt;br&gt;Code erreur 24 : Défaut tension batterie déséquilibré&lt;br&gt;Code erreur 27, 39 : Défaut numéro série carte mère&lt;br&gt;Code erreur 35, 36 : Défaut capteur ou surchauffe batterie&lt;br&gt;Code erreur 40 : Défaut surchauffe carte mère&lt;br&gt;Code erreur 41 : Défaut version BLE&lt;br&gt;Code erreur 42 : Défaut carte mère numéro de série&lt;/p&gt;</p></div></div>', 2, '2025-11-05 01:33:01', '2025-11-05 01:33:01', 13),
(3, 'DIAG – Trottinette électrique HS -', '<div class=\"kb-modern-wrapper\">\n<style>\n.kb-modern-wrapper { \n  font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, sans-serif; \n  color: #334155; \n  line-height: 1.6; \n  max-width: 100%; \n  margin: 0 auto; \n  background: #fff; \n  padding: 2rem; \n  border-radius: 12px; \n  box-shadow: 0 1px 3px rgba(0,0,0,0.1); \n  border: 1px solid #e2e8f0;\n}\n.kb-modern-wrapper h1 { \n  font-size: 1.8rem; \n  font-weight: 700;\n  color: #0f172a; \n  margin-top: 0;\n  margin-bottom: 1.5rem; \n  padding-bottom: 1rem; \n  border-bottom: 2px solid #f1f5f9; \n}\n.kb-modern-wrapper h2 { \n  font-size: 1.4rem; \n  font-weight: 600;\n  color: #1e293b; \n  margin-top: 2rem; \n  margin-bottom: 1rem; \n  display: flex; align-items: center; gap: 0.5rem;\n  background: #f8fafc;\n  padding: 0.75rem;\n  border-radius: 8px;\n  border-left: 4px solid #3b82f6;\n}\n.kb-modern-wrapper h3 { \n  font-size: 1.1rem; \n  font-weight: 600;\n  color: #475569; \n  margin-top: 1.5rem; \n  margin-bottom: 0.5rem;\n}\n.kb-modern-wrapper p { margin-bottom: 1rem; }\n.kb-modern-wrapper ul, .kb-modern-wrapper ol { padding-left: 1.5rem; margin-bottom: 1rem; }\n.kb-modern-wrapper li { margin-bottom: 0.5rem; }\n.kb-modern-wrapper code { \n  background: #eff6ff; \n  color: #1d4ed8; \n  padding: 0.2rem 0.4rem; \n  border-radius: 4px; \n  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; \n  font-size: 0.9em; \n  border: 1px solid #dbeafe;\n}\n.kb-modern-wrapper table { \n  width: 100%; \n  border-collapse: separate; \n  border-spacing: 0;\n  margin: 1.5rem 0; \n  font-size: 0.95rem; \n  border: 1px solid #e2e8f0;\n  border-radius: 8px;\n  overflow: hidden;\n}\n.kb-modern-wrapper th { \n  background: #f1f5f9; \n  color: #475569; \n  font-weight: 600; \n  text-align: left; \n  padding: 1rem; \n  border-bottom: 1px solid #e2e8f0; \n}\n.kb-modern-wrapper td { \n  padding: 1rem; \n  border-bottom: 1px solid #f1f5f9; \n  vertical-align: middle; \n}\n.kb-modern-wrapper tr:last-child td { border-bottom: none; }\n.kb-modern-wrapper tr:hover td { background: #f8fafc; }\n</style>\n<div class=\"kb-content-original\"><p><br>⸻<br><br>📍 Étape 1 : Tester la tension de la batterie<br>• Utiliser un multimètre en mode tension continue (DC).<br>• Brancher les sondes rouge (+) et noire (−) directement sur les bornes de la batterie.<br><br>✅ Si la batterie affiche une tension normale (par exemple, &gt; 41V pour une 48V) → passer à l’étape 4<br><br>❌ Si la tension est trop basse ou nulle → passer à l’étape 2<br><br>⸻<br><br>📍 Étape 2 : Tester la tension aux câbles du LCD<br>• Identifier les câbles d’alimentation du LCD (souvent rouge/noir).<br>• Brancher le multimètre entre les fils d’alimentation du connecteur LCD.<br><br>✅ Si une tension est présente (ex. 41-54V) → passer à l’étape 4<br><br>❌ Si aucune tension n’est présente → passer à l’étape 3<br><br>⸻<br><br>📍 Étape 3 : Tester la continuité des câbles LCD<br>• Débrancher les deux extrémités du câble LCD (de la carte mère jusqu’au LCD).<br>• Utiliser le mode bip/continuité du multimètre.<br>• Tester chaque fil (bout à bout) pour vérifier qu’il n’y a pas de rupture.<br><br>✅ Si les câbles sont bons → passer à l’étape 4<br><br>❌ Si un ou plusieurs câbles sont coupés → remplacer le câble LCD et retester<br><br>⸻<br><br>📍 Étape 4 : Diagnostic final<br>• Si :<br>• la batterie est bonne<br>• il n’y a aucun affichage<br>• et le câblage est intact<br><br>👉 Le kit LCD + contrôleur est probablement HS.<br><br>✅ Solution recommandée :<br><br>Proposer un remplacement complet par un kit MiniMotors (LCD + contrôleur), compatible avec le modèle.</p></div></div>', 2, '2025-11-05 01:34:56', '2025-11-05 01:34:56', 3),
(4, 'Generer un code Iron TV / IPTV', '<div class=\"kb-modern-wrapper\">\n<style>\n.kb-modern-wrapper { \n  font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, sans-serif; \n  color: #334155; \n  line-height: 1.6; \n  max-width: 100%; \n  margin: 0 auto; \n  background: #fff; \n  padding: 2rem; \n  border-radius: 12px; \n  box-shadow: 0 1px 3px rgba(0,0,0,0.1); \n  border: 1px solid #e2e8f0;\n}\n.kb-modern-wrapper h1 { \n  font-size: 1.8rem; \n  font-weight: 700;\n  color: #0f172a; \n  margin-top: 0;\n  margin-bottom: 1.5rem; \n  padding-bottom: 1rem; \n  border-bottom: 2px solid #f1f5f9; \n}\n.kb-modern-wrapper h2 { \n  font-size: 1.4rem; \n  font-weight: 600;\n  color: #1e293b; \n  margin-top: 2rem; \n  margin-bottom: 1rem; \n  display: flex; align-items: center; gap: 0.5rem;\n  background: #f8fafc;\n  padding: 0.75rem;\n  border-radius: 8px;\n  border-left: 4px solid #3b82f6;\n}\n.kb-modern-wrapper h3 { \n  font-size: 1.1rem; \n  font-weight: 600;\n  color: #475569; \n  margin-top: 1.5rem; \n  margin-bottom: 0.5rem;\n}\n.kb-modern-wrapper p { margin-bottom: 1rem; }\n.kb-modern-wrapper ul, .kb-modern-wrapper ol { padding-left: 1.5rem; margin-bottom: 1rem; }\n.kb-modern-wrapper li { margin-bottom: 0.5rem; }\n.kb-modern-wrapper code { \n  background: #eff6ff; \n  color: #1d4ed8; \n  padding: 0.2rem 0.4rem; \n  border-radius: 4px; \n  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; \n  font-size: 0.9em; \n  border: 1px solid #dbeafe;\n}\n.kb-modern-wrapper table { \n  width: 100%; \n  border-collapse: separate; \n  border-spacing: 0;\n  margin: 1.5rem 0; \n  font-size: 0.95rem; \n  border: 1px solid #e2e8f0;\n  border-radius: 8px;\n  overflow: hidden;\n}\n.kb-modern-wrapper th { \n  background: #f1f5f9; \n  color: #475569; \n  font-weight: 600; \n  text-align: left; \n  padding: 1rem; \n  border-bottom: 1px solid #e2e8f0; \n}\n.kb-modern-wrapper td { \n  padding: 1rem; \n  border-bottom: 1px solid #f1f5f9; \n  vertical-align: middle; \n}\n.kb-modern-wrapper tr:last-child td { border-bottom: none; }\n.kb-modern-wrapper tr:hover td { background: #f8fafc; }\n</style>\n<div class=\"kb-content-original\"><div class=\"html-content-wrapper\"><!doctype html>\\r\\n<html lang=\"fr\">\\r\\n<head>\\r\\n<meta charset=\"utf-8\" />\\r\\n<meta name=\"viewport\" content=\"width=device-width, initial-scale=1\" />\\r\\n<title>Tuto – IRON TV (exact)</title>\\r\\n<style>\\r\\n  /* =========================\\r\\n     Design moderne (sans thème auto)\\r\\n     — Variables surchargables par votre site\\r\\n  ========================== */\\r\\n  :root {\\r\\n    --bg: #0e1116;          /* fond général si non géré par le site */\\r\\n    --surface: #111826;     /* carte */\\r\\n    --ink: #e8eef9;         /* texte principal */\\r\\n    --muted: #94a3b8;       /* texte secondaire */\\r\\n    --primary: #6aa5ff;     /* accents */\\r\\n    --ok: #22c55e;          /* titres */\\r\\n    --warn: #f59e0b;\\r\\n    --border: rgba(255,255,255,0.10);\\r\\n    --ring: rgba(106,165,255,.35);\\r\\n    --radius: 16px;\\r\\n    --radius-sm: 12px;\\r\\n    --shadow-lg: 0 20px 60px rgba(0,0,0,.35);\\r\\n    --shadow-md: 0 10px 30px rgba(0,0,0,.28);\\r\\n    --maxw: 920px;\\r\\n  }\\r\\n\\r\\n  body { font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Inter, Arial, sans-serif; color: var(--ink); line-height: 1.65; margin: 0; }\\r\\n  .kb-wrap { max-width: var(--maxw); margin: 40px auto; padding: 0 20px; }\\r\\n\\r\\n  /* Header */\\r\\n  .kb-head { display: flex; justify-content: space-between; align-items: flex-end; gap: 16px; margin-bottom: 18px; }\\r\\n  .kb-title { font-size: clamp(26px, 3.2vw, 40px); letter-spacing: .2px; margin: 0; }\\r\\n  .kb-meta { display:flex; gap:10px; flex-wrap:wrap; }\\r\\n  .tag { display:inline-flex; align-items:center; gap:.5ch; padding:8px 12px; border:1px solid var(--border); border-radius:999px; backdrop-filter: blur(6px); box-shadow: var(--shadow-md); background: linear-gradient(180deg, rgba(255,255,255,.03), rgba(255,255,255,.00)); font-weight:600; font-size:12px; color: var(--ink); }\\r\\n  .tag .dot{ width:6px; height:6px; border-radius:50%; background:var(--ok); box-shadow:0 0 0 6px rgba(34,197,94,.12) }\\r\\n\\r\\n  /* Card + steps */\\r\\n  .kb-card { position:relative; border:1px solid var(--border); border-radius: calc(var(--radius) + 2px); background: radial-gradient(120% 140% at 5% -10%, rgba(106,165,255,.10), transparent 55%), radial-gradient(120% 140% at 100% 10%, rgba(245,158,11,.10), transparent 60%), var(--surface); box-shadow: var(--shadow-lg); padding: 22px; }\\r\\n  .section { position: relative; padding: 18px 18px 18px 64px; border-radius: var(--radius); border: 1px solid var(--border); background: linear-gradient(180deg, rgba(255,255,255,.03), rgba(255,255,255,.00)); box-shadow: var(--shadow-md); margin: 16px 0; }\\r\\n  .num { position:absolute; left:14px; top:14px; width:38px; height:38px; border-radius: 12px; display:grid; place-items:center; font-weight:800; color:#001028; background: linear-gradient(180deg, color-mix(in oklab, var(--primary), white 12%), var(--primary)); border: 1px solid color-mix(in oklab, var(--primary), black 14%); box-shadow: 0 10px 24px color-mix(in oklab, var(--primary), transparent 70%); }\\r\\n  h2 { margin:0 0 8px; font-size: 20px; color: var(--ok); }\\r\\n  p { margin:8px 0; }\\r\\n  .bullet { display:flex; gap:10px; align-items:flex-start; padding:10px 12px; border:1px dashed var(--border); border-radius: var(--radius-sm); background: linear-gradient(180deg, rgba(255,255,255,.03), rgba(255,255,255,.00)); }\\r\\n  .bullet .dot { font-weight:700; color: var(--muted); }\\r\\n  .sep { margin: 18px 0; text-align:center; color: var(--muted); }\\r\\n\\r\\n  /* Callouts */\\r\\n  .callout { border:1px solid var(--border); border-left: 4px solid var(--warn); border-radius: var(--radius); background: linear-gradient(180deg, rgba(255,255,255,.03), rgba(255,255,255,0)); padding: 14px 16px; box-shadow: var(--shadow-md); }\\r\\n  .callout h3 { margin:0 0 6px; font-size: 16px; color: var(--warn); }\\r\\n\\r\\n  /* Utilities */\\r\\n  .btn-print { display:inline-flex; align-items:center; gap:.6ch; margin-top: 26px; padding: 10px 16px; background: var(--primary); color: white; border: none; border-radius: 12px; font-weight: 700; cursor: pointer; box-shadow: 0 8px 24px rgba(0,0,0,.25); }\\r\\n  .btn-print:hover { transform: translateY(-1px); }\\r\\n  .fine { color: var(--muted); font-size: 13px; margin-top: 8px; }\\r\\n\\r\\n  @media print { .btn-print, .kb-head { display:none; } .section { break-inside: avoid; } }\\r\\n</style>\\r\\n</head>\\r\\n<body>\\r\\n\\r\\n<div class=\"kb-wrap\">\\r\\n  <header class=\"kb-head\">\\r\\n    <h1 class=\"kb-title\">Tuto – IRON TV (texte exact)</h1>\\r\\n    <div class=\"kb-meta\">\\r\\n      <span class=\"tag\"><span class=\"dot\"></span> Procédure</span>\\r\\n      <span class=\"tag\">Dernière maj : <time>05/11/2025</time></span>\\r\\n    </div>\\r\\n  </header>\\r\\n\\r\\n  <div class=\"kb-card\">\\r\\n    <!-- Bloc Étapes -->\\r\\n    <section class=\"section\" id=\"etapes\">\\r\\n      <div class=\"num\">1</div>\\r\\n      <h2>Comment generer un code IRON TV</h2>\\r\\n      <p>📲 Étapes pour générer un code</p>\\r\\n      <p>1. Connectez-vous à https://myirontv.com avec les identifiants disponible a la fin du tutoriel.</p>\\r\\n      <p>2. Ouvrez Google Authenticator sur un des appareils autorisés pour entrer le code de validation.</p>\\r\\n      <p>3. Une fois connecté, dans le menu latéral gauche, cliquez sur :</p>\\r\\n      <p>• IPTV ActiveCode</p>\\r\\n      <p>4. Cliquez ensuite sur :</p>\\r\\n      <p>• New Active Code (ou Nouveau Code Actif)</p>\\r\\n      <p>5. Dans le menu déroulant, sélectionnez :</p>\\r\\n      <p>• 12 mois (6 crédits)</p>\\r\\n      <p>6. Le système génère automatiquement un code unique.</p>\\r\\n      <p>7. Copiez le code généré.</p>\\r\\n      <p>8. Imprimez le code et remettez-le au client.</p>\\r\\n    </section>\\r\\n    <div class=\"sep\">⸻</div>\\r\\n\\r\\n    <!-- Bloc A ne pas oublier -->\\r\\n    <section class=\"section\" id=\"memo\">\\r\\n      <div class=\"num\">2</div>\\r\\n      <div class=\"callout\">\\r\\n        <h3>⚠️ À ne pas oublier</h3>\\r\\n        <p>• Chaque code de 12 mois consomme 6 crédits.</p>\\r\\n        <p>• Lorsque le nombre de crédits disponibles descend en dessous de 72, il faut prévenir un responsable immédiatement.</p>\\r\\n      </div>\\r\\n    </section>\\r\\n\\r\\n    <div class=\"sep\">---</div>\\r\\n\\r\\n    <!-- Bloc Informations de connexion -->\\r\\n    <section class=\"section\" id=\"login\">\\r\\n      <div class=\"num\">3</div>\\r\\n      <h2>🔐 Informations de connexion</h2>\\r\\n      <p>• Panel : myirontv.com</p>\\r\\n      <p>• Identifiant : MDGEEK</p>\\r\\n      <p>• Mot de passe : Azerty@123456</p>\\r\\n      <p>• Google Authenticator :</p>\\r\\n      <p>Utiliser l’un des appareils suivants pour valider l’accès :</p>\\r\\n      <p>• iPad de la caisse</p>\\r\\n      <p>• Tablette Android de l’atelier trottinette</p>\\r\\n      <p>• Tablette Android de l’atelier informatique</p>\\r\\n    </section>\\r\\n\\r\\n    <button class=\"btn-print\" onclick=\"window.print()\">🖨️ Imprimer / Exporter en PDF</button>\\r\\n    <p class=\"fine\">Texte conservé à l’identique, mise en forme modernisée.</p>\\r\\n  </div>\\r\\n</div>\\r\\n\\r\\n</body>\\r\\n</html></div></div></div>', 3, '2025-11-05 01:36:47', '2025-11-05 01:36:47', 2),
(5, 'Diag : La trottinette s’allume mais n’accélère pas', '<div class=\"kb-modern-wrapper\">\n<style>\n.kb-modern-wrapper { \n  font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, sans-serif; \n  color: #334155; \n  line-height: 1.6; \n  max-width: 100%; \n  margin: 0 auto; \n  background: #fff; \n  padding: 2rem; \n  border-radius: 12px; \n  box-shadow: 0 1px 3px rgba(0,0,0,0.1); \n  border: 1px solid #e2e8f0;\n}\n.kb-modern-wrapper h1 { \n  font-size: 1.8rem; \n  font-weight: 700;\n  color: #0f172a; \n  margin-top: 0;\n  margin-bottom: 1.5rem; \n  padding-bottom: 1rem; \n  border-bottom: 2px solid #f1f5f9; \n}\n.kb-modern-wrapper h2 { \n  font-size: 1.4rem; \n  font-weight: 600;\n  color: #1e293b; \n  margin-top: 2rem; \n  margin-bottom: 1rem; \n  display: flex; align-items: center; gap: 0.5rem;\n  background: #f8fafc;\n  padding: 0.75rem;\n  border-radius: 8px;\n  border-left: 4px solid #3b82f6;\n}\n.kb-modern-wrapper h3 { \n  font-size: 1.1rem; \n  font-weight: 600;\n  color: #475569; \n  margin-top: 1.5rem; \n  margin-bottom: 0.5rem;\n}\n.kb-modern-wrapper p { margin-bottom: 1rem; }\n.kb-modern-wrapper ul, .kb-modern-wrapper ol { padding-left: 1.5rem; margin-bottom: 1rem; }\n.kb-modern-wrapper li { margin-bottom: 0.5rem; }\n.kb-modern-wrapper code { \n  background: #eff6ff; \n  color: #1d4ed8; \n  padding: 0.2rem 0.4rem; \n  border-radius: 4px; \n  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; \n  font-size: 0.9em; \n  border: 1px solid #dbeafe;\n}\n.kb-modern-wrapper table { \n  width: 100%; \n  border-collapse: separate; \n  border-spacing: 0;\n  margin: 1.5rem 0; \n  font-size: 0.95rem; \n  border: 1px solid #e2e8f0;\n  border-radius: 8px;\n  overflow: hidden;\n}\n.kb-modern-wrapper th { \n  background: #f1f5f9; \n  color: #475569; \n  font-weight: 600; \n  text-align: left; \n  padding: 1rem; \n  border-bottom: 1px solid #e2e8f0; \n}\n.kb-modern-wrapper td { \n  padding: 1rem; \n  border-bottom: 1px solid #f1f5f9; \n  vertical-align: middle; \n}\n.kb-modern-wrapper tr:last-child td { border-bottom: none; }\n.kb-modern-wrapper tr:hover td { background: #f8fafc; }\n</style>\n<div class=\"kb-content-original\"><h1>🛠️ Diagnostic – La trottinette s’allume mais n’accélère pas<br><br>⸻<br><br>📍 Étape 0 : Faire tourner la roue pour vérifier la détection moteur<br>1. Allumer la trottinette<br>2. Faire tourner manuellement la roue arrière<br>3. Regarder si les kilomètres défilent légèrement sur le LCD<br><br>✅ Si les kilomètres réagissent → capteurs Hall moteur OK → passer à l’étape 1<br><br>❌ Si rien ne bouge → vérifier les capteurs Hall (ci-dessous)<br><br>⸻<br><br>⚙️ Test des capteurs Hall moteur<br>1. Débrancher les fils Hall du contrôleur<br>2. Les connecter au boîtier de diagnostic Hall<br>3. Faire tourner la roue manuellement<br><br>✅ Si les 3 LED s’allument à tour de rôle → capteurs Hall fonctionnels<br><br>❌ Si une LED reste éteinte ou bloquée → capteur Hall ou câblage défectueux → envisager remplacement du moteur<br><br>⸻<br><br>📍 Étape 1 : Vérifier la présence d’un code erreur sur le LCD<br>• Si un code est affiché → consulter le site constructeur<br>• Appliquer la solution proposée<br><br>✅ Si résolu → fin du diagnostic<br><br>❌ Si pas de code ou code inconnu → passer à l’étape 2<br><br>⸻<br><br>📍 Étape 2 : Tester avec une autre gâchette d’accélérateur<br>• Remplacer uniquement la gâchette<br>• Tester l’accélération<br><br>✅ Si la trottinette accélère → gâchette HS → fin du diagnostic<br><br>❌ Si pas de changement → passer à l’étape 3<br><br>⸻<br><br>📍 Étape 3 : Tester avec un nouveau LCD<br>• Remplacer uniquement l’écran LCD<br>• Tester<br><br>✅ Si ça fonctionne → LCD d’origine HS → fin du diagnostic<br><br>❌ Si toujours rien → passer à l’étape 3.5<br><br>⸻<br><br>📍 Étape 3.5 : Vérifier les capteurs de frein<br><br>Il se peut que le frein reste activé en permanence, empêchant l’accélération.<br><br>Méthodes de vérification :<br>1. Vérifier si un symbole de frein s’affiche sur le LCD (⚠️ pas toujours présent)<br>2. Débrancher les capteurs de frein (⚠️ peut générer un code erreur sur certains modèles)<br>3. Appuyer sur le frein → vérifier si la lumière arrière s’allume (⚠️ feu parfois non fonctionnel)<br>4. Tester avec une autre poignée de frein<br><br>✅ Si ça accélère → capteur de frein HS → remplacer<br><br>❌ Si toujours rien → passer à l’étape 4<br><br>⸻<br><br>📍 Étape 4 : Tester avec un nouveau contrôleur + nouveau LCD + nouvelle gâchette<br>• Remplacer les 3 éléments clés<br>• Tester l’accélération<br><br>✅ Si la trottinette accélère → passer à l’étape 5 pour isoler la pièce défectueuse<br><br>❌ Si toujours rien → passer à l’étape 7 (solution ultime)<br><br>⸻<br><br>📍 Étape 5 : Tests croisés pour isoler la panne<br><br>⸻<br><br>Test A<br>• ✅ Nouveau contrôleur<br>• ✅ Nouveau LCD<br>• ❌ Gâchette d’origine<br><br>➡️ Si ça ne fonctionne plus → gâchette d’origine défectueuse<br><br>⸻<br><br>Test B<br>• ✅ Nouveau contrôleur<br>• ❌ LCD d’origine<br>• ✅ Nouvelle gâchette<br><br>➡️ Si ça ne fonctionne plus → LCD d’origine défectueux<br><br>⸻<br><br>Test C<br>• ❌ Contrôleur d’origine<br>• ✅ Nouveau LCD<br>• ✅ Nouvelle gâchette<br><br>➡️ Si ça ne fonctionne plus → contrôleur d’origine défectueux<br><br>⸻<br><br>📍 Étape 6 (facultative) : Rebrancher les capteurs de frein un par un<br>• Pour identifier un capteur de frein précis qui bloque l’accélération<br>• Rebrancher un seul frein à la fois<br>• Tester entre chaque<br><br>⸻<br><br>📍 Étape 7 : 💡 Solution ultime – Installer un kit MiniMotors<br><br>Si aucun test ne permet de réparer la trottinette :<br><br>➡️ Remplacer tout le système par un kit MiniMotors :<br>• Contrôleur<br>• Écran LCD<br>• Gâchette<br><br>✅ Avantages :<br>• Compatible avec la plupart des moteurs brushless<br>• Fiable, réactif, facile à diagnostiquer<br>• Kit complet prêt à câbler</h1></div></div>', 2, '2025-11-05 01:42:31', '2025-11-05 01:42:31', 14),
(6, 'Comment installer ironTv sur la cle Amazon Fire Stick', '<div class=\"kb-modern-wrapper\">\n<style>\n.kb-modern-wrapper { \n  font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, sans-serif; \n  color: #334155; \n  line-height: 1.6; \n  max-width: 100%; \n  margin: 0 auto; \n  background: #fff; \n  padding: 2rem; \n  border-radius: 12px; \n  box-shadow: 0 1px 3px rgba(0,0,0,0.1); \n  border: 1px solid #e2e8f0;\n}\n.kb-modern-wrapper h1 { \n  font-size: 1.8rem; \n  font-weight: 700;\n  color: #0f172a; \n  margin-top: 0;\n  margin-bottom: 1.5rem; \n  padding-bottom: 1rem; \n  border-bottom: 2px solid #f1f5f9; \n}\n.kb-modern-wrapper h2 { \n  font-size: 1.4rem; \n  font-weight: 600;\n  color: #1e293b; \n  margin-top: 2rem; \n  margin-bottom: 1rem; \n  display: flex; align-items: center; gap: 0.5rem;\n  background: #f8fafc;\n  padding: 0.75rem;\n  border-radius: 8px;\n  border-left: 4px solid #3b82f6;\n}\n.kb-modern-wrapper h3 { \n  font-size: 1.1rem; \n  font-weight: 600;\n  color: #475569; \n  margin-top: 1.5rem; \n  margin-bottom: 0.5rem;\n}\n.kb-modern-wrapper p { margin-bottom: 1rem; }\n.kb-modern-wrapper ul, .kb-modern-wrapper ol { padding-left: 1.5rem; margin-bottom: 1rem; }\n.kb-modern-wrapper li { margin-bottom: 0.5rem; }\n.kb-modern-wrapper code { \n  background: #eff6ff; \n  color: #1d4ed8; \n  padding: 0.2rem 0.4rem; \n  border-radius: 4px; \n  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; \n  font-size: 0.9em; \n  border: 1px solid #dbeafe;\n}\n.kb-modern-wrapper table { \n  width: 100%; \n  border-collapse: separate; \n  border-spacing: 0;\n  margin: 1.5rem 0; \n  font-size: 0.95rem; \n  border: 1px solid #e2e8f0;\n  border-radius: 8px;\n  overflow: hidden;\n}\n.kb-modern-wrapper th { \n  background: #f1f5f9; \n  color: #475569; \n  font-weight: 600; \n  text-align: left; \n  padding: 1rem; \n  border-bottom: 1px solid #e2e8f0; \n}\n.kb-modern-wrapper td { \n  padding: 1rem; \n  border-bottom: 1px solid #f1f5f9; \n  vertical-align: middle; \n}\n.kb-modern-wrapper tr:last-child td { border-bottom: none; }\n.kb-modern-wrapper tr:hover td { background: #f8fafc; }\n</style>\n<div class=\"kb-content-original\"><p>&nbsp;</p><h2><strong>🔧 Installation de l’application IRON sur Fire TV</strong></h2><p>&nbsp;</p><h3><strong>1️⃣ Télécharger l’application Downloader</strong></h3><p>&nbsp;</p><p>Depuis votre Fire TV, appuyez sur le <strong>micro</strong> de la télécommande.</p><p>Dites : <strong>« Télécharge application Downloader »</strong>.</p><p>Installez l’application <strong>Downloader</strong>.</p><p>&nbsp;</p><h3><strong>2️⃣ Télécharger l’application IRON</strong></h3><p>&nbsp;</p><p>Ouvrez <strong>Downloader</strong>.</p><p>Dans la barre de saisie, entrez le <strong>code : 347824</strong></p><p>Si le code ne fonctionne pas, utilisez l’une des adresses suivantes :</p><p>mdgeek.fr/tv.apk</p><p>mdgeek.fr/tv1.apk</p><p>&nbsp;</p><h3><strong>3️⃣ Activer le mode développeur</strong></h3><p>&nbsp;</p><p>Sur la Fire TV, allez dans <strong>Paramètres</strong> (roue crantée en haut à droite).</p><p>Sélectionnez <strong>Ma Fire TV</strong>.</p><p>Choisissez <strong>À propos de</strong>.</p><p>Placez-vous sur la <strong>première option</strong> de la liste.</p><p>Appuyez <strong>7 fois</strong> sur le <strong>bouton central</strong> de la télécommande.</p><p>Un message s’affiche : <i>« Vous êtes maintenant un développeur ».</i></p><p>Revenez en arrière : un nouveau menu <strong>Options pour les développeurs</strong> est apparu.</p><p>Activez l’option <strong>Installer les applications inconnues</strong> → <strong>Autoriser pour Downloader</strong>.</p><p>&nbsp;</p><h3><strong>4️⃣ Installer l’application IRON</strong></h3><p>&nbsp;</p><p>Revenez dans <strong>Downloader</strong>.</p><p>Sélectionnez le fichier APK téléchargé précédemment.</p><p>Cliquez sur <strong>Installer</strong>.</p><p>Une fois l’installation terminée, ouvrez l’application.</p><p>&nbsp;</p><h3><strong>5️⃣ Mettre l’application en avant</strong></h3><p>&nbsp;</p><p>Pour faciliter l’accès à IRON :</p><p>Appuyez sur le <strong>bouton menu (☰)</strong> de votre télécommande lorsque vous êtes sur l’écran des applications.</p><p>Choisissez <strong>Déplacer vers l’avant</strong> pour placer IRON sur la première ligne.</p><p>&nbsp;</p><h3><strong>✅ L’installation est terminée</strong><br>&nbsp;</h3><p><strong>il ne manque plus que le code et </strong>Votre application IRON est maintenant prête à l’emploi.</p><p>&nbsp;</p><p>&nbsp;</p></div></div>', 3, '2025-11-05 01:47:11', '2025-11-05 01:47:11', 7),
(7, '🚨 Si le client obtient « CODE ALREADY USED / CODE DÉJÀ UTILISÉ »', '<div class=\"kb-modern-wrapper\">\n<style>\n.kb-modern-wrapper { \n  font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, sans-serif; \n  color: #334155; \n  line-height: 1.6; \n  max-width: 100%; \n  margin: 0 auto; \n  background: #fff; \n  padding: 2rem; \n  border-radius: 12px; \n  box-shadow: 0 1px 3px rgba(0,0,0,0.1); \n  border: 1px solid #e2e8f0;\n}\n.kb-modern-wrapper h1 { \n  font-size: 1.8rem; \n  font-weight: 700;\n  color: #0f172a; \n  margin-top: 0;\n  margin-bottom: 1.5rem; \n  padding-bottom: 1rem; \n  border-bottom: 2px solid #f1f5f9; \n}\n.kb-modern-wrapper h2 { \n  font-size: 1.4rem; \n  font-weight: 600;\n  color: #1e293b; \n  margin-top: 2rem; \n  margin-bottom: 1rem; \n  display: flex; align-items: center; gap: 0.5rem;\n  background: #f8fafc;\n  padding: 0.75rem;\n  border-radius: 8px;\n  border-left: 4px solid #3b82f6;\n}\n.kb-modern-wrapper h3 { \n  font-size: 1.1rem; \n  font-weight: 600;\n  color: #475569; \n  margin-top: 1.5rem; \n  margin-bottom: 0.5rem;\n}\n.kb-modern-wrapper p { margin-bottom: 1rem; }\n.kb-modern-wrapper ul, .kb-modern-wrapper ol { padding-left: 1.5rem; margin-bottom: 1rem; }\n.kb-modern-wrapper li { margin-bottom: 0.5rem; }\n.kb-modern-wrapper code { \n  background: #eff6ff; \n  color: #1d4ed8; \n  padding: 0.2rem 0.4rem; \n  border-radius: 4px; \n  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; \n  font-size: 0.9em; \n  border: 1px solid #dbeafe;\n}\n.kb-modern-wrapper table { \n  width: 100%; \n  border-collapse: separate; \n  border-spacing: 0;\n  margin: 1.5rem 0; \n  font-size: 0.95rem; \n  border: 1px solid #e2e8f0;\n  border-radius: 8px;\n  overflow: hidden;\n}\n.kb-modern-wrapper th { \n  background: #f1f5f9; \n  color: #475569; \n  font-weight: 600; \n  text-align: left; \n  padding: 1rem; \n  border-bottom: 1px solid #e2e8f0; \n}\n.kb-modern-wrapper td { \n  padding: 1rem; \n  border-bottom: 1px solid #f1f5f9; \n  vertical-align: middle; \n}\n.kb-modern-wrapper tr:last-child td { border-bottom: none; }\n.kb-modern-wrapper tr:hover td { background: #f8fafc; }\n</style>\n<div class=\"kb-content-original\"><p>Si le message <strong>« CODE ALREADY USED »</strong> s’affiche :</p><p>Ouvrir le tutoriel suivant :</p><p>📄 <strong>Guide de résolution et génération ID / Mot de passe IPTV Smarters</strong></p><p>👉 <a href=\"http://mdgeek.fr/codeiron.pdf\">http://mdgeek.fr/codeiron.pdf</a></p><p>&nbsp;</p></div></div>', 3, '2025-11-05 01:57:57', '2025-11-05 01:57:57', 5),
(8, '🛠️ Diagnostic – Le moteur de la trottinette tourne à l’envers', '<div class=\"kb-modern-wrapper\">\n<style>\n.kb-modern-wrapper { \n  font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, sans-serif; \n  color: #334155; \n  line-height: 1.6; \n  max-width: 100%; \n  margin: 0 auto; \n  background: #fff; \n  padding: 2rem; \n  border-radius: 12px; \n  box-shadow: 0 1px 3px rgba(0,0,0,0.1); \n  border: 1px solid #e2e8f0;\n}\n.kb-modern-wrapper h1 { \n  font-size: 1.8rem; \n  font-weight: 700;\n  color: #0f172a; \n  margin-top: 0;\n  margin-bottom: 1.5rem; \n  padding-bottom: 1rem; \n  border-bottom: 2px solid #f1f5f9; \n}\n.kb-modern-wrapper h2 { \n  font-size: 1.4rem; \n  font-weight: 600;\n  color: #1e293b; \n  margin-top: 2rem; \n  margin-bottom: 1rem; \n  display: flex; align-items: center; gap: 0.5rem;\n  background: #f8fafc;\n  padding: 0.75rem;\n  border-radius: 8px;\n  border-left: 4px solid #3b82f6;\n}\n.kb-modern-wrapper h3 { \n  font-size: 1.1rem; \n  font-weight: 600;\n  color: #475569; \n  margin-top: 1.5rem; \n  margin-bottom: 0.5rem;\n}\n.kb-modern-wrapper p { margin-bottom: 1rem; }\n.kb-modern-wrapper ul, .kb-modern-wrapper ol { padding-left: 1.5rem; margin-bottom: 1rem; }\n.kb-modern-wrapper li { margin-bottom: 0.5rem; }\n.kb-modern-wrapper code { \n  background: #eff6ff; \n  color: #1d4ed8; \n  padding: 0.2rem 0.4rem; \n  border-radius: 4px; \n  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; \n  font-size: 0.9em; \n  border: 1px solid #dbeafe;\n}\n.kb-modern-wrapper table { \n  width: 100%; \n  border-collapse: separate; \n  border-spacing: 0;\n  margin: 1.5rem 0; \n  font-size: 0.95rem; \n  border: 1px solid #e2e8f0;\n  border-radius: 8px;\n  overflow: hidden;\n}\n.kb-modern-wrapper th { \n  background: #f1f5f9; \n  color: #475569; \n  font-weight: 600; \n  text-align: left; \n  padding: 1rem; \n  border-bottom: 1px solid #e2e8f0; \n}\n.kb-modern-wrapper td { \n  padding: 1rem; \n  border-bottom: 1px solid #f1f5f9; \n  vertical-align: middle; \n}\n.kb-modern-wrapper tr:last-child td { border-bottom: none; }\n.kb-modern-wrapper tr:hover td { background: #f8fafc; }\n</style>\n<div class=\"kb-content-original\"><p>❓ Symptôme<br>• Le moteur tourne à l’envers dès qu’on accélère.<br>• La trottinette recule au lieu d’avancer.<br><br>⸻<br><br>🎯 Objectif<br><br>Corriger le sens de rotation du moteur sans endommager le contrôleur ou les capteurs Hall.<br><br>⸻<br><br>📌 Étape 1 : Identifier les fils de phases moteur<br><br>Les 3 fils de phase sont généralement :<br>• Jaune<br>• Vert<br>• Bleu<br><br>Ces fils vont du moteur vers le contrôleur.<br><br>⸻<br><br>📌 Étape 2 : Inverser deux fils de phase<br>• Inverser le fil VERT et le BLEU<br>• Exemple : Vert ↔️ Bleu<br>• Laisser le JAUNE inchangé.<br><br>✅ Si le moteur tourne dans le bon sens → passer à l’étape 4<br><br>❌ Si le moteur vibre ou ne tourne pas → passer à l’étape 3<br><br>⸻<br><br>📌 Étape 3 : Inverser les capteurs Hall (si le moteur en est équipé)<br><br>Si le moteur utilise des capteurs Hall, il faut aussi inverser deux des fils de signal Hall (souvent très fins, 5 fils en général) :<br>• Inverser les fils de capteurs Hall VERT et BLEU<br>• Exemple : fil signal Hall vert ↔️ fil signal Hall bleu<br>• Laisser les autres fils (rouge, noir, jaune) inchangés.<br><br>✅ Après inversion, le moteur doit tourner normalement<br><br>⸻<br><br>📌 Étape 4 : Tester la trottinette<br>• Accélérez lentement pour vérifier que le moteur :<br>• Tourne dans le bon sens<br>• Ne vibre pas<br>• Ne force pas<br><br>⸻<br><br>⚠️ Attention<br>• Ne jamais inverser les 3 fils de phase en même temps<br>• Ne jamais inverser seulement les fils Hall sans toucher aux phases : cela peut endommager le contrôleur<br>• Toujours tester à basse vitesse après modification</p></div></div>', 2, '2025-11-05 01:58:28', '2025-11-05 01:58:28', 2),
(9, '🛠️ Diagnostic iPhone qui redémarre en boucle', '<div class=\"kb-modern-wrapper\">\n<style>\n.kb-modern-wrapper { \n  font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, sans-serif; \n  color: #334155; \n  line-height: 1.6; \n  max-width: 100%; \n  margin: 0 auto; \n  background: #fff; \n  padding: 2rem; \n  border-radius: 12px; \n  box-shadow: 0 1px 3px rgba(0,0,0,0.1); \n  border: 1px solid #e2e8f0;\n}\n.kb-modern-wrapper h1 { \n  font-size: 1.8rem; \n  font-weight: 700;\n  color: #0f172a; \n  margin-top: 0;\n  margin-bottom: 1.5rem; \n  padding-bottom: 1rem; \n  border-bottom: 2px solid #f1f5f9; \n}\n.kb-modern-wrapper h2 { \n  font-size: 1.4rem; \n  font-weight: 600;\n  color: #1e293b; \n  margin-top: 2rem; \n  margin-bottom: 1rem; \n  display: flex; align-items: center; gap: 0.5rem;\n  background: #f8fafc;\n  padding: 0.75rem;\n  border-radius: 8px;\n  border-left: 4px solid #3b82f6;\n}\n.kb-modern-wrapper h3 { \n  font-size: 1.1rem; \n  font-weight: 600;\n  color: #475569; \n  margin-top: 1.5rem; \n  margin-bottom: 0.5rem;\n}\n.kb-modern-wrapper p { margin-bottom: 1rem; }\n.kb-modern-wrapper ul, .kb-modern-wrapper ol { padding-left: 1.5rem; margin-bottom: 1rem; }\n.kb-modern-wrapper li { margin-bottom: 0.5rem; }\n.kb-modern-wrapper code { \n  background: #eff6ff; \n  color: #1d4ed8; \n  padding: 0.2rem 0.4rem; \n  border-radius: 4px; \n  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; \n  font-size: 0.9em; \n  border: 1px solid #dbeafe;\n}\n.kb-modern-wrapper table { \n  width: 100%; \n  border-collapse: separate; \n  border-spacing: 0;\n  margin: 1.5rem 0; \n  font-size: 0.95rem; \n  border: 1px solid #e2e8f0;\n  border-radius: 8px;\n  overflow: hidden;\n}\n.kb-modern-wrapper th { \n  background: #f1f5f9; \n  color: #475569; \n  font-weight: 600; \n  text-align: left; \n  padding: 1rem; \n  border-bottom: 1px solid #e2e8f0; \n}\n.kb-modern-wrapper td { \n  padding: 1rem; \n  border-bottom: 1px solid #f1f5f9; \n  vertical-align: middle; \n}\n.kb-modern-wrapper tr:last-child td { border-bottom: none; }\n.kb-modern-wrapper tr:hover td { background: #f8fafc; }\n</style>\n<div class=\"kb-content-original\"><p>&nbsp;</p><h1><strong>🧰 Procédure de diagnostic iPhone – Démarrage impossible / blocage logo</strong></h1><p>&nbsp;</p><h3><strong>⚙️ Objectif : </strong>Identifier la cause du problème de démarrage (logo bloqué, redémarrage en boucle, écran noir, etc.) <strong>en testant les nappes et composants internes étape par étape.</strong></h3><p>&nbsp;</p><h2><strong>Étape 1️⃣ : Débrancher toutes les nappes internes</strong></h2><p>&nbsp;</p><p>Débranchez <strong>toutes les nappes</strong> : batterie, écran, capteurs, caméras, bouton Power, etc.</p><p>Branchez uniquement la <strong>batterie</strong> et <strong>le connecteur de charge</strong>.</p><p>Essayez de démarrer l’iPhone.</p><p>✅ Si l’iPhone démarre normalement → <strong>Fin du diagnostic</strong></p><p>❌ Sinon → passer à <strong>l’étape 2</strong></p><p>&nbsp;</p><h2><strong>Étape 2️⃣ : Tester avec un nouveau connecteur de charge</strong></h2><p>&nbsp;</p><p>Remplacez la <strong>nappe connecteur de charge + micro</strong>.</p><p>Essayez de démarrer l’appareil.</p><p>✅ Si l’iPhone démarre → <strong>Fin du diagnostic</strong></p><p>❌ Sinon → passer à <strong>l’étape 3</strong></p><p>&nbsp;</p><h2><strong>Étape 3️⃣ : Tester avec un nouveau bouton Power / Volume</strong></h2><p>&nbsp;</p><p>Remplacez la <strong>nappe Power / Volume / Vibreur</strong>.</p><p>Testez le démarrage.</p><p>✅ Si l’iPhone démarre → <strong>Fin du diagnostic</strong></p><p>❌ Sinon → passer à <strong>l’étape 4</strong></p><p>&nbsp;</p><h2><strong>Étape 4️⃣ : Tester avec un nouveau capteur de proximité</strong></h2><p>&nbsp;</p><p>Remplacez la <strong>nappe capteur de proximité + micro supérieur + capteur lumière</strong>.</p><p>Testez le démarrage.</p><p>✅ Si l’iPhone démarre → <strong>Fin du diagnostic</strong></p><p>❌ Sinon → passer à <strong>l’étape 5</strong></p><p>&nbsp;</p><h2><strong>Étape 5️⃣ : Tester sans caméra arrière</strong></h2><p>&nbsp;</p><p><strong>Débranchez la caméra arrière.</strong></p><p>Démarrez l’iPhone.</p><p>✅ Si l’iPhone démarre → <strong>Fin du diagnostic</strong></p><p>❌ Sinon → passer à <strong>l’étape 6</strong></p><p>&nbsp;</p><h2><strong>Étape 6️⃣ : Tester sans caméra avant</strong></h2><p>&nbsp;</p><p><strong>Débranchez la caméra avant.</strong></p><p>Testez le démarrage.</p><p>✅ Si l’iPhone démarre → <strong>Fin du diagnostic</strong></p><p>❌ Sinon → passer à <strong>l’étape 7</strong></p><p>&nbsp;</p><h2><strong>Étape 7️⃣ : Tester avec une autre batterie</strong></h2><p>&nbsp;</p><p>Remplacez la <strong>batterie</strong> par une <strong>batterie fonctionnelle</strong>.</p><p>Testez le démarrage.</p><p>✅ Si l’iPhone démarre → <strong>Fin du diagnostic</strong></p><p>❌ Sinon → <strong>problème matériel complexe</strong> → <strong>rediriger le client vers Phone Étoile.</strong></p><p>&nbsp;</p><h3><strong>📋 Notes internes</strong></h3><p>&nbsp;</p><p>Toujours <strong>déconnecter la batterie avant chaque changement de nappe</strong>.</p><p>Ne pas mélanger les vis des plaques de blindage (risque de court-circuit).</p><p>Si aucune des étapes ne résout le problème → Conseillez la boutique <strong>Phone Étoile comme derniere solution au client. ( ils sont specialisee en microsoudure )</strong></p></div></div>', 4, '2025-11-05 02:02:59', '2025-11-05 02:02:59', 9),
(10, 'test', '<p>test</p>', 3, '2025-11-29 02:03:42', '2025-11-29 02:03:42', 2),
(11, 'sda', '<p>dsa</p>', 3, '2025-11-29 20:30:46', '2025-11-29 20:30:46', 1);
INSERT INTO `kb_articles` (`id`, `title`, `content`, `category_id`, `created_at`, `updated_at`, `views`) VALUES
(13, 'Codes Panic Log iPhone - Guide Technique Complet par Modèle', '<div class=\"kb-modern-wrapper\">\n<style>\n.kb-modern-wrapper { \n  font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, sans-serif; \n  color: #334155; \n  line-height: 1.6; \n  max-width: 100%; \n  margin: 0 auto; \n  background: #fff; \n  padding: 2rem; \n  border-radius: 12px; \n  box-shadow: 0 1px 3px rgba(0,0,0,0.1); \n  border: 1px solid #e2e8f0;\n}\n.kb-modern-wrapper h1 { \n  font-size: 1.8rem; \n  font-weight: 700;\n  color: #0f172a; \n  margin-top: 0;\n  margin-bottom: 1.5rem; \n  padding-bottom: 1rem; \n  border-bottom: 2px solid #f1f5f9; \n}\n.kb-modern-wrapper h2 { \n  font-size: 1.4rem; \n  font-weight: 600;\n  color: #1e293b; \n  margin-top: 2rem; \n  margin-bottom: 1rem; \n  display: flex; align-items: center; gap: 0.5rem;\n  background: #f8fafc;\n  padding: 0.75rem;\n  border-radius: 8px;\n  border-left: 4px solid #3b82f6;\n}\n.kb-modern-wrapper h3 { \n  font-size: 1.1rem; \n  font-weight: 600;\n  color: #475569; \n  margin-top: 1.5rem; \n  margin-bottom: 0.5rem;\n}\n.kb-modern-wrapper p { margin-bottom: 1rem; }\n.kb-modern-wrapper ul, .kb-modern-wrapper ol { padding-left: 1.5rem; margin-bottom: 1rem; }\n.kb-modern-wrapper li { margin-bottom: 0.5rem; }\n.kb-modern-wrapper code { \n  background: #eff6ff; \n  color: #1d4ed8; \n  padding: 0.2rem 0.4rem; \n  border-radius: 4px; \n  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; \n  font-size: 0.9em; \n  border: 1px solid #dbeafe;\n}\n.kb-modern-wrapper table { \n  width: 100%; \n  border-collapse: separate; \n  border-spacing: 0;\n  margin: 1.5rem 0; \n  font-size: 0.95rem; \n  border: 1px solid #e2e8f0;\n  border-radius: 8px;\n  overflow: hidden;\n}\n.kb-modern-wrapper th { \n  background: #f1f5f9; \n  color: #475569; \n  font-weight: 600; \n  text-align: left; \n  padding: 1rem; \n  border-bottom: 1px solid #e2e8f0; \n}\n.kb-modern-wrapper td { \n  padding: 1rem; \n  border-bottom: 1px solid #f1f5f9; \n  vertical-align: middle; \n}\n.kb-modern-wrapper tr:last-child td { border-bottom: none; }\n.kb-modern-wrapper tr:hover td { background: #f8fafc; }\n</style>\n<div class=\"kb-content-original\"><h2>📱 Diagnostic des Panic Logs iPhone</h2>\\n\\n<div style=\"background: #e3f2fd; padding: 15px; border-left: 4px solid #2196f3; margin: 15px 0;\">\\n<strong>📍 Accès aux Panic Logs :</strong><br>\\nRéglages → Confidentialité et sécurité → Analyses et améliorations → Données d\\\'analyses<br>\\nRecherchez les fichiers <code>panic-full-AAAA-MM-JJ-HHMMSS.ips</code>\\n</div>\\n\\n<h3>🔧 iPhone 8 / iPhone X - Codes SMC Assertion</h3>\\n\\n<table border=\"1\" cellpadding=\"10\" style=\"border-collapse: collapse; width: 100%; font-size: 13px;\">\\n<thead style=\"background-color: #37474f; color: white;\">\\n<tr>\\n<th style=\"width: 20%;\">Code</th>\\n<th style=\"width: 35%;\">Composant</th>\\n<th style=\"width: 45%;\">Solution de réparation</th>\\n</tr>\\n</thead>\\n<tbody>\\n<tr>\\n<td><code>0x8</code> / <code>0x00000008</code></td>\\n<td>Batterie / Données batterie</td>\\n<td>Tester batterie, connecteur, BMS EEPROM</td>\\n</tr>\\n<tr style=\"background-color: #f5f5f5;\">\\n<td><code>0x10</code></td>\\n<td>Capteur température batterie</td>\\n<td>Vérifier ligne capteur température</td>\\n</tr>\\n<tr>\\n<td><code>0x20</code></td>\\n<td>Circuit de charge</td>\\n<td>Tester Tristar (U2 IC), Tigris, Hydra, USB IC</td>\\n</tr>\\n<tr style=\"background-color: #f5f5f5;\">\\n<td><code>0x80</code></td>\\n<td>Défaut PMIC</td>\\n<td>Analyser PMU, rails de tension</td>\\n</tr>\\n<tr>\\n<td><code>0x100</code></td>\\n<td>Capteur thermique carte mère</td>\\n<td>Identifier composant en surchauffe</td>\\n</tr>\\n<tr style=\"background-color: #f5f5f5;\">\\n<td><code>0x200</code></td>\\n<td>Surconsommation électrique</td>\\n<td>Chercher court-circuit sur carte</td>\\n</tr>\\n<tr>\\n<td><code>0x400</code></td>\\n<td>VDD_MAIN instable<br>Séparation sandwich (iPhone X+)</td>\\n<td>Vérifier ligne principale, filtre. Reballing si séparation</td>\\n</tr>\\n<tr style=\"background-color: #f5f5f5;\">\\n<td><code>0x800</code></td>\\n<td>Nappe de charge<br>Capteur pression (prs0)</td>\\n<td>Remplacer nappe charge, vérifier connecteur</td>\\n</tr>\\n<tr>\\n<td><code>0x1000</code></td>\\n<td>Nappe proximité</td>\\n<td>Remplacer nappe proximité</td>\\n</tr>\\n<tr style=\"background-color: #f5f5f5;\">\\n<td><code>0x1800</code></td>\\n<td>Nappe charge + Nappe proximité</td>\\n<td>Remplacer les deux nappes</td>\\n</tr>\\n<tr>\\n<td><code>0x10000</code></td>\\n<td>Nappe bouton power</td>\\n<td>Remplacer nappe bouton power</td>\\n</tr>\\n<tr style=\"background-color: #f5f5f5;\">\\n<td><code>0x20000</code></td>\\n<td>Sandwich board</td>\\n<td>Problème carte mère - Reballing requis</td>\\n</tr>\\n<tr>\\n<td><code>0x40000</code></td>\\n<td>Nappe de charge</td>\\n<td>Remplacer nappe charge</td>\\n</tr>\\n<tr style=\"background-color: #f5f5f5;\">\\n<td><code>0x80000</code></td>\\n<td>Nappe proximité</td>\\n<td>Remplacer nappe proximité</td>\\n</tr>\\n<tr>\\n<td><code>mic1</code></td>\\n<td>Nappe de charge ou bouton power</td>\\n<td>Tester et remplacer nappe concernée</td>\\n</tr>\\n<tr style=\"background-color: #f5f5f5;\">\\n<td><code>mic2</code></td>\\n<td>Nappe bouton power</td>\\n<td>Remplacer nappe bouton power</td>\\n</tr>\\n<tr>\\n<td><code>prs0</code></td>\\n<td>Capteur pression (nappe charge)</td>\\n<td>Remplacer nappe charge</td>\\n</tr>\\n<tr style=\"background-color: #f5f5f5;\">\\n<td><code>tg0b</code> / <code>TPG0V</code></td>\\n<td>Batterie / Ligne données batterie</td>\\n<td>Tester batterie et connecteur</td>\\n</tr>\\n</tbody>\\n</table>\\n\\n<h3>🔧 iPhone 11 / iPhone 12 - Codes Diagnostic</h3>\\n\\n<table border=\"1\" cellpadding=\"10\" style=\"border-collapse: collapse; width: 100%; font-size: 13px;\">\\n<thead style=\"background-color: #1976d2; color: white;\">\\n<tr>\\n<th style=\"width: 20%;\">Code / Message</th>\\n<th style=\"width: 35%;\">Composant</th>\\n<th style=\"width: 45%;\">Solution de réparation</th>\\n</tr>\\n</thead>\\n<tbody>\\n<tr>\\n<td><code>mic1</code></td>\\n<td>Nappe de charge</td>\\n<td>Remplacer nappe charge</td>\\n</tr>\\n<tr style=\"background-color: #f5f5f5;\">\\n<td><code>mic2</code></td>\\n<td>Nappe bouton power</td>\\n<td>Remplacer nappe bouton power (sur iPhone 11, les deux nappes doivent être connectées)</td>\\n</tr>\\n<tr>\\n<td><code>prs0</code></td>\\n<td>Capteur pression sur nappe charge</td>\\n<td>Remplacer nappe charge</td>\\n</tr>\\n<tr style=\"background-color: #f5f5f5;\">\\n<td><code>tg0b</code></td>\\n<td>Batterie / Connecteur / Ligne données</td>\\n<td>Tester batterie et connecteur</td>\\n</tr>\\n<tr>\\n<td><code>ans2</code></td>\\n<td>NAND (stockage)</td>\\n<td>Problème stockage - Remplacement NAND requis (micro-soudure)</td>\\n</tr>\\n<tr style=\"background-color: #f5f5f5;\">\\n<td><code>Thermalmonitord</code></td>\\n<td>Surchauffe / Nappe charge ou power</td>\\n<td>Vérifier température, tester nappes</td>\\n</tr>\\n<tr>\\n<td><code>0x100000</code></td>\\n<td>Capteur proximité (iPhone 11)</td>\\n<td>Remplacer nappe proximité</td>\\n</tr>\\n<tr style=\"background-color: #f5f5f5;\">\\n<td><code>0x400000</code></td>\\n<td>Connecteur batterie (iPhone 11)</td>\\n<td>Nettoyer ou remplacer connecteur</td>\\n</tr>\\n<tr>\\n<td><code>Watchdog Timeout</code></td>\\n<td>Problème logiciel (Springboard, wifid)</td>\\n<td>Restauration iOS ou mise à jour</td>\\n</tr>\\n<tr style=\"background-color: #f5f5f5;\">\\n<td><code>AOP NMI POWER</code></td>\\n<td>Nappe bouton power / Caméra avant</td>\\n<td>Remplacer composant concerné</td>\\n</tr>\\n<tr>\\n<td><code>i2c Error</code></td>\\n<td>Erreur canal i2c</td>\\n<td>Analyse schéma requis - Composant sur canal i2c</td>\\n</tr>\\n</tbody>\\n</table>\\n\\n<h3>🔧 iPhone 13 (tous modèles) - Codes SMC Panic</h3>\\n\\n<table border=\"1\" cellpadding=\"10\" style=\"border-collapse: collapse; width: 100%; font-size: 13px;\">\\n<thead style=\"background-color: #0288d1; color: white;\">\\n<tr>\\n<th style=\"width: 20%;\">Code</th>\\n<th style=\"width: 35%;\">Composant</th>\\n<th style=\"width: 45%;\">Solution de réparation</th>\\n</tr>\\n</thead>\\n<tbody>\\n<tr>\\n<td><code>0x800</code></td>\\n<td>Nappe de charge</td>\\n<td>Remplacer nappe charge</td>\\n</tr>\\n<tr style=\"background-color: #f5f5f5;\">\\n<td><code>0x1000</code></td>\\n<td>Nappe proximité</td>\\n<td>Remplacer nappe proximité</td>\\n</tr>\\n<tr>\\n<td><code>0x1800</code></td>\\n<td>Nappe charge + Nappe proximité</td>\\n<td>Remplacer les deux nappes (combinaison 0x800 + 0x1000)</td>\\n</tr>\\n<tr style=\"background-color: #f5f5f5;\">\\n<td><code>0x4000</code></td>\\n<td>Problème kernel batterie</td>\\n<td>Tester batterie, vérifier firmware batterie</td>\\n</tr>\\n<tr>\\n<td><code>0xC00</code></td>\\n<td>Bottom board / Nappe charge (iPhone 13 Mini uniquement)</td>\\n<td>Vérifier carte inférieure ou remplacer nappe</td>\\n</tr>\\n</tbody>\\n</table>\\n\\n<h3>🔧 iPhone 14 / iPhone 14 Plus - Codes SMC Panic</h3>\\n\\n<table border=\"1\" cellpadding=\"10\" style=\"border-collapse: collapse; width: 100%; font-size: 13px;\">\\n<thead style=\"background-color: #0277bd; color: white;\">\\n<tr>\\n<th style=\"width: 20%;\">Code</th>\\n<th style=\"width: 35%;\">Composant</th>\\n<th style=\"width: 45%;\">Solution de réparation</th>\\n</tr>\\n</thead>\\n<tbody>\\n<tr>\\n<td><code>0x100000</code></td>\\n<td>Nappe de charge</td>\\n<td>Remplacer nappe charge</td>\\n</tr>\\n<tr style=\"background-color: #f5f5f5;\">\\n<td><code>0x200000</code></td>\\n<td>Nappe proximité</td>\\n<td>Remplacer nappe proximité</td>\\n</tr>\\n<tr>\\n<td><code>0x400000</code></td>\\n<td>Nappe charge sans fil (vitre arrière)</td>\\n<td>Remplacer vitre arrière avec nappe MagSafe</td>\\n</tr>\\n<tr style=\"background-color: #f5f5f5;\">\\n<td><code>0x500000</code></td>\\n<td>Communication batterie / Taptic engine / Nappe charge</td>\\n<td>Tester batterie, taptic engine, nappe charge</td>\\n</tr>\\n<tr>\\n<td><code>0x600000</code></td>\\n<td>Nappe charge + Nappe proximité</td>\\n<td>Remplacer les deux nappes</td>\\n</tr>\\n<tr style=\"background-color: #f5f5f5;\">\\n<td><code>0x20000</code></td>\\n<td>Interposer / Sandwich board</td>\\n<td>Problème carte mère - Reballing</td>\\n</tr>\\n</tbody>\\n</table>\\n\\n<h3>�� iPhone 14 Pro / iPhone 14 Pro Max - Codes SMC Panic</h3>\\n\\n<table border=\"1\" cellpadding=\"10\" style=\"border-collapse: collapse; width: 100%; font-size: 13px;\">\\n<thead style=\"background-color: #01579b; color: white;\">\\n<tr>\\n<th style=\"width: 20%;\">Code</th>\\n<th style=\"width: 35%;\">Composant</th>\\n<th style=\"width: 45%;\">Solution de réparation</th>\\n</tr>\\n</thead>\\n<tbody>\\n<tr>\\n<td><code>0x10000</code></td>\\n<td>Nappe bouton power</td>\\n<td>Remplacer nappe bouton power</td>\\n</tr>\\n<tr style=\"background-color: #f5f5f5;\">\\n<td><code>0x40000</code></td>\\n<td>Nappe de charge</td>\\n<td>Remplacer nappe charge</td>\\n</tr>\\n<tr>\\n<td><code>0x80000</code></td>\\n<td>Nappe proximité</td>\\n<td>Remplacer nappe proximité</td>\\n</tr>\\n<tr style=\"background-color: #f5f5f5;\">\\n<td><code>0x20000</code></td>\\n<td>Séparation sandwich / Carte mère</td>\\n<td>Problème carte mère - Reballing requis</td>\\n</tr>\\n<tr>\\n<td><code>SMC PANIC: AppleSMC/AOP mailbox failure</code></td>\\n<td>PMIC / AOP / Carte mère (souvent dégât des eaux)</td>\\n<td>Inspection liquide, nettoyage, remplacement PMIC si nécessaire</td>\\n</tr>\\n</tbody>\\n</table>\\n\\n<h3>🔧 iPhone 15 / iPhone 15 Plus - Codes SMC Panic</h3>\\n\\n<table border=\"1\" cellpadding=\"10\" style=\"border-collapse: collapse; width: 100%; font-size: 13px;\">\\n<thead style=\"background-color: #00838f; color: white;\">\\n<tr>\\n<th style=\"width: 20%;\">Code</th>\\n<th style=\"width: 35%;\">Composant</th>\\n<th style=\"width: 45%;\">Solution de réparation</th>\\n</tr>\\n</thead>\\n<tbody>\\n<tr>\\n<td><code>0x200000</code></td>\\n<td>Nappe charge sans fil (vitre arrière)</td>\\n<td>Remplacer vitre arrière avec nappe MagSafe</td>\\n</tr>\\n<tr style=\"background-color: #f5f5f5;\">\\n<td><code>0x80000</code></td>\\n<td>Nappe de charge</td>\\n<td>Remplacer nappe charge</td>\\n</tr>\\n<tr>\\n<td><code>0x100000</code></td>\\n<td>Nappe proximité</td>\\n<td>Remplacer nappe proximité</td>\\n</tr>\\n</tbody>\\n</table>\\n\\n<h3>🔧 iPhone 15 Pro / iPhone 15 Pro Max - Codes SMC Panic</h3>\\n\\n<table border=\"1\" cellpadding=\"10\" style=\"border-collapse: collapse; width: 100%; font-size: 13px;\">\\n<thead style=\"background-color: #00695c; color: white;\">\\n<tr>\\n<th style=\"width: 20%;\">Code</th>\\n<th style=\"width: 35%;\">Composant</th>\\n<th style=\"width: 45%;\">Solution de réparation</th>\\n</tr>\\n</thead>\\n<tbody>\\n<tr>\\n<td><code>0xa1</code></td>\\n<td>Défaut batterie</td>\\n<td>Remplacer batterie</td>\\n</tr>\\n<tr style=\"background-color: #f5f5f5;\">\\n<td><code>0x300000</code></td>\\n<td>Nappe de charge</td>\\n<td>Remplacer nappe charge</td>\\n</tr>\\n<tr>\\n<td><code>0x400000</code></td>\\n<td>Nappe charge sans fil</td>\\n<td>Remplacer vitre arrière avec nappe MagSafe</td>\\n</tr>\\n<tr style=\"background-color: #f5f5f5;\">\\n<td><code>0x700000</code></td>\\n<td>Nappe charge + Nappe charge sans fil</td>\\n<td>Remplacer nappe charge ET vitre arrière</td>\\n</tr>\\n</tbody>\\n</table>\\n\\n<h3>🔧 iPhone 16 Pro / iPhone 16 Pro Max - Codes SMC Panic</h3>\\n\\n<table border=\"1\" cellpadding=\"10\" style=\"border-collapse: collapse; width: 100%; font-size: 13px;\">\\n<thead style=\"background-color: #004d40; color: white;\">\\n<tr>\\n<th style=\"width: 20%;\">Code</th>\\n<th style=\"width: 35%;\">Composant</th>\\n<th style=\"width: 45%;\">Solution de réparation</th>\\n</tr>\\n</thead>\\n<tbody>\\n<tr>\\n<td><code>3145728</code><br>(0x300000 en hexa)</td>\\n<td>Nappe de charge</td>\\n<td>Remplacer nappe charge</td>\\n</tr>\\n<tr style=\"background-color: #f5f5f5;\">\\n<td><code>2097152</code><br>(0x200000 en hexa)</td>\\n<td>Nappe de charge</td>\\n<td>Remplacer nappe charge</td>\\n</tr>\\n<tr>\\n<td>Module capteur pression air</td>\\n<td>Capteur barométrique</td>\\n<td>Vérifier/remplacer module capteur, nettoyer si dégât des eaux</td>\\n</tr>\\n<tr style=\"background-color: #f5f5f5;\">\\n<td>Problème batterie</td>\\n<td>Batterie / Connecteur</td>\\n<td>Tester et remplacer batterie</td>\\n</tr>\\n</tbody>\\n</table>\\n\\n<h3>🔍 Méthodologie de Diagnostic</h3>\\n\\n<ol style=\"line-height: 1.8;\">\\n<li><strong>Récupérer le panic log</strong> via Réglages → Confidentialité</li>\\n<li><strong>Identifier le code hexadécimal</strong> ou le message d\\\'erreur</li>\\n<li><strong>Consulter le tableau correspondant</strong> au modèle d\\\'iPhone</li>\\n<li><strong>Inspecter visuellement</strong> le connecteur FPC du composant suspect</li>\\n<li><strong>Tester en mode diode</strong> les lignes du connecteur au multimètre</li>\\n<li><strong>Remplacer le composant</strong> identifié par une pièce testée</li>\\n<li><strong>Vérifier la résolution</strong> en consultant à nouveau les panic logs</li>\\n</ol>\\n\\n<h3>💡 Points Clés pour la Réparation</h3>\\n\\n<ul style=\"line-height: 1.8;\">\\n<li>Les codes peuvent se <strong>combiner</strong> (ex: 0x800 + 0x1000 = 0x1800)</li>\\n<li>Les modèles iPhone 13+ utilisent majoritairement le format <strong>SMC Panic Assertion</strong></li>\\n<li>Un redémarrage <strong>toutes les 3 minutes</strong> indique généralement un capteur manquant</li>\\n<li>Vérifier TOUJOURS les <strong>connecteurs FPC</strong> pour dommages physiques ou liquides</li>\\n<li>Les codes peuvent varier légèrement entre <strong>versions iOS</strong></li>\\n<li>Utiliser des <strong>pièces OEM ou testées</strong> pour éviter de faux diagnostics</li>\\n<li>En cas d\\\'échec après remplacement nappe, suspecter un <strong>problème carte mère</strong></li>\\n</ul>\\n\\n<div style=\"background-color: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 20px 0;\">\\n<strong>⚠️ ATTENTION :</strong> Les codes liés à la carte mère (sandwich, PMIC, AOP) nécessitent des compétences en <strong>micro-soudure</strong> et équipement professionnel. Ne pas tenter sans formation adéquate.\\n</div>\\n\\n<h3>🛠️ Outils Recommandés</h3>\\n\\n<ul>\\n<li><strong>iDevice Panic Log Analyzer</strong> - Logiciel d\\\'analyse automatique</li>\\n<li><strong>PanicFull.com</strong> - Service en ligne d\\\'analyse de panic logs</li>\\n<li><strong>Repair.wiki</strong> - Documentation communautaire</li>\\n<li><strong>Multimètre en mode diode</strong> - Test des lignes FPC</li>\\n<li><strong>Schémas électriques</strong> - Pour diagnostic avancé carte mère</li>\\n</ul></div></div>', 4, '2025-12-15 00:45:58', '2025-12-15 00:45:58', 10),
(14, '🔍 Diagnostic Avancé : Les Panic Logs iPhone (Guide 2024-2025)', '<div class=\"kb-article-modern\"><style>.kb-article-modern{font-family:-apple-system,BlinkMacSystemFont,\"Segoe UI\",Roboto,Helvetica,Arial,sans-serif;color:#334155;line-height:1.4;font-size:14px}.kb-hero{background:linear-gradient(135deg,#0f172a 0%,#1e293b 100%);color:white;padding:1.5rem;border-radius:12px;margin-bottom:1rem;position:relative;overflow:hidden;box-shadow:0 4px 6px -1px rgba(0,0,0,0.1)}.kb-hero::after{content:\"📱\";position:absolute;right:-10px;bottom:-30px;font-size:6rem;opacity:0.1;transform:rotate(-15deg)}.kb-hero h2{margin:0 0 0.5rem 0;font-size:1.4rem;font-weight:700;color:#fff}.kb-hero p{color:#94a3b8;font-size:0.95rem;margin:0}.kb-section{background:white;border:1px solid #e2e8f0;border-radius:8px;padding:1rem;margin-bottom:1rem;box-shadow:0 1px 2px rgba(0,0,0,0.05)}.kb-title{display:flex;align-items:center;gap:8px;font-size:1.1rem;font-weight:600;color:#0f172a;margin-bottom:0.75rem;padding-bottom:0.5rem;border-bottom:1px solid #f1f5f9}.kb-icon-box{display:flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:6px}.step-box{background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;padding:0.5rem 0.75rem;display:flex;align-items:center;gap:0.75rem;margin-bottom:0.5rem}.step-number{background:#3b82f6;color:white;width:20px;height:20px;font-size:11px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:bold;flex-shrink:0}.table-container{overflow-x:auto;border-radius:6px;border:1px solid #e2e8f0}.kb-table{width:100%;border-collapse:collapse;font-size:13px}.kb-table th{background:#f1f5f9;color:#475569;font-weight:600;text-align:left;padding:0.5rem;border-bottom:1px solid #e2e8f0}.kb-table td{padding:0.4rem 0.5rem;border-bottom:1px solid #f1f5f9;color:#334155;vertical-align:middle}.kb-table tr:hover{background-color:#f8fafc}.code-badge{font-family:monospace;background:#e0f2fe;color:#0369a1;padding:2px 6px;border-radius:4px;font-weight:600;font-size:12px;border:1px solid #bae6fd;display:inline-block}.tag-charge{color:#d97706;background:#fef3c7;padding:1px 6px;border-radius:4px;font-size:11px;font-weight:600;border:1px solid rgba(217,119,6,0.1)}.tag-sensor{color:#4f46e5;background:#e0e7ff;padding:1px 6px;border-radius:4px;font-size:11px;font-weight:600;border:1px solid rgba(79,70,229,0.1)}.tag-board{color:#dc2626;background:#fee2e2;padding:1px 6px;border-radius:4px;font-size:11px;font-weight:600;border:1px solid rgba(220,38,38,0.1)}.tag-batt{color:#16a34a;background:#dcfce7;padding:1px 6px;border-radius:4px;font-size:11px;font-weight:600;border:1px solid rgba(22,163,74,0.1)}.kb-alert{padding:0.75rem;border-radius:6px;margin-top:0.75rem;display:flex;align-items:center;gap:10px;font-size:13px}.alert-warning{background:#fffbeb;border:1px solid #fcd34d;color:#92400e}.alert-info{background:#eff6ff;border:1px solid #bfdbfe;color:#1e40af}</style><div class=\"kb-hero\"><h2>📊 Diagnostic Panic Logs</h2><p>Guide d\'interprétation des redémarrages (Restart 3min) et codes d\'erreurs Kernel.</p></div><div class=\"kb-section\"><div class=\"kb-title\"><div class=\"kb-icon-box\" style=\"background:#e0f2fe;color:#0284c7\">🔍</div>Accès aux logs</div><div style=\"display:grid;grid-template-columns:1fr 1fr;gap:10px\"><div class=\"step-box\"><div class=\"step-number\">1</div><div><strong>Réglages</strong> > Confidentialité & sécurité</div></div><div class=\"step-box\"><div class=\"step-number\">2</div><div><strong>Analyses</strong> > Données d\'analyses</div></div></div><div class=\"step-box\" style=\"margin-top:5px;margin-bottom:0\"><div class=\"step-number\">3</div><div>Ouvrir <code>panic-full-AAAA-MM-JJ.ips</code> récent</div></div></div><div class=\"kb-section\"><div class=\"kb-title\"><div class=\"kb-icon-box\" style=\"background:#d1fae5;color:#059669\">🚀</div>iPhone 15 & 16 Series</div><div class=\"table-container\"><table class=\"kb-table\"><thead><tr><th width=\"20%\">Code Hexa</th><th width=\"30%\">Composant</th><th>Action</th></tr></thead><tbody><tr><td><span class=\"code-badge\">0x300000</span></td><td><span class=\"tag-charge\">Nappe Charge</span></td><td>Remplacer connecteur charge</td></tr><tr><td><span class=\"code-badge\">0x200000</span></td><td><span class=\"tag-charge\">Vitre Wireless</span></td><td>Vérifier nappe MagSafe/Dos</td></tr><tr><td><span class=\"code-badge\">0x700000</span></td><td><span class=\"tag-charge\">Charge + Wire</span></td><td>Vérifier Charge ET Dos</td></tr><tr><td><span class=\"code-badge\">0xa1</span></td><td><span class=\"tag-batt\">Batterie</span></td><td>Défaut BMS ou batterie HS</td></tr><tr><td><span class=\"code-badge\">Pression</span></td><td><span class=\"tag-sensor\">Baromètre</span></td><td>Dégâts liquides micro bas</td></tr></tbody></table></div></div><div class=\"kb-section\"><div class=\"kb-title\"><div class=\"kb-icon-box\" style=\"background:#fae8ff;color:#86198f\">⚡</div>iPhone 13 & 14 Series <span style=\"font-size:0.8em;font-weight:normal;color:#64748b;margin-left:8px\">(SMC Panic Assertion)</span></div><div class=\"table-container\"><table class=\"kb-table\"><thead><tr><th width=\"20%\">Code Hexa</th><th width=\"30%\">Composant</th><th>Action</th></tr></thead><tbody><tr><td><span class=\"code-badge\">0x800</span></td><td><span class=\"tag-charge\">Nappe Charge</span></td><td>Remplacer port charge</td></tr><tr><td><span class=\"code-badge\">0x1000</span></td><td><span class=\"tag-sensor\">Proximité</span></td><td>Capteur lum./Flash flood HS</td></tr><tr><td><span class=\"code-badge\">0x1800</span></td><td><span class=\"tag-charge\">Charge + Prox</span></td><td>Les deux nappes en défaut</td></tr><tr><td><span class=\"code-badge\">0x10000</span></td><td><span class=\"tag-sensor\">Power/Flash</span></td><td>Nappe Flash/Power (Pro)</td></tr><tr><td><span class=\"code-badge\">0x4000</span></td><td><span class=\"tag-batt\">Batterie</span></td><td>FW corrompu ou non-org</td></tr><tr><td><span class=\"code-badge\">0x20000</span></td><td><span class=\"tag-board\">Sandwich</span></td><td>Séparation inter-cartes</td></tr></tbody></table></div></div><div class=\"kb-section\"><div class=\"kb-title\"><div class=\"kb-icon-box\" style=\"background:#e0e7ff;color:#3730a3\">🔵</div>iPhone 11 & 12 Series</div><div class=\"table-container\"><table class=\"kb-table\"><thead><tr><th width=\"20%\">Code</th><th width=\"30%\">Composant</th><th>Action</th></tr></thead><tbody><tr><td><span class=\"code-badge\">mic1</span></td><td><span class=\"tag-charge\">Port Charge</span></td><td>Micro bas HS -> Remplacer</td></tr><tr><td><span class=\"code-badge\">mic2</span></td><td><span class=\"tag-sensor\">Power/Flash</span></td><td>Micro dos HS -> Remplacer</td></tr><tr><td><span class=\"code-badge\">prs0</span></td><td><span class=\"tag-charge\">Baromètre</span></td><td>Pression HS -> Remplacer</td></tr><tr><td><span class=\"code-badge\">tg0b</span></td><td><span class=\"tag-batt\">Batterie</span></td><td>Ligne data (SWI) coupée</td></tr><tr><td><span class=\"code-badge\">thermal</span></td><td><span class=\"tag-sensor\">Thermique</span></td><td>Surchauffe/Capteur absent</td></tr><tr><td><span class=\"code-badge\">ans2</span></td><td><span class=\"tag-board\">NAND</span></td><td>Puce mémoire HS</td></tr></tbody></table></div></div><div class=\"kb-section\"><div class=\"kb-title\"><div class=\"kb-icon-box\" style=\"background:#f3f4f6;color:#1f2937\">💾</div>iPhone 8 / X / XS / XR</div><div class=\"table-container\"><table class=\"kb-table\"><thead><tr><th width=\"20%\">Code</th><th width=\"30%\">Composant</th><th>Action</th></tr></thead><tbody><tr><td><span class=\"code-badge\">0x800</span></td><td><span class=\"tag-charge\">Port Charge</span></td><td>Connecteur Lightning HS</td></tr><tr><td><span class=\"code-badge\">0x20</span></td><td><span class=\"tag-board\">Circuit</span></td><td>Hydra/Tigris/Tristar HS</td></tr><tr><td><span class=\"code-badge\">0x400</span></td><td><span class=\"tag-board\">VDD_MAIN</span></td><td>Court-circuit ligne princ.</td></tr><tr><td><span class=\"code-badge\">0x100</span></td><td><span class=\"tag-sensor\">Caméra Av</span></td><td>FaceID/Flood en court-circuit</td></tr></tbody></table></div></div><div class=\"kb-alert alert-warning\" style=\"margin-bottom:0\"><div><strong>⚠️ Note :</strong> Code panic = Indice. Toujours inspecter les connecteurs avant remplacement.</div></div></div>', 4, '2025-12-15 00:49:45', '2025-12-15 00:49:45', 6);

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
(14, 9),
(9, 10),
(14, 10),
(9, 11),
(14, 11);

-- --------------------------------------------------------

--
-- Structure de la table `kb_categories`
--

CREATE TABLE `kb_categories` (
  `id` int NOT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `icon` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'fas fa-folder',
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
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
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
-- Structure de la table `kpi_ai_profiles`
--

CREATE TABLE `kpi_ai_profiles` (
  `id` int NOT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Nom du profil expert',
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Description du rôle de l''expert',
  `icon` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'fas fa-user' COMMENT 'Icône Font Awesome',
  `system_prompt` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Instructions système pour l''IA',
  `is_default` tinyint(1) DEFAULT '0' COMMENT '1 si profil par défaut (non supprimable)',
  `active` tinyint(1) DEFAULT '1' COMMENT '1 si profil actif',
  `created_by` int DEFAULT NULL COMMENT 'ID de l''utilisateur créateur',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `kpi_ai_profiles`
--

INSERT INTO `kpi_ai_profiles` (`id`, `name`, `description`, `icon`, `system_prompt`, `is_default`, `active`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'Expert Gestion Entreprise', 'Vision stratégique globale avec analyse des tendances et opportunités', 'fas fa-chart-line', 'Tu es un expert analytique en gestion d\'entreprise avec 20 ans d\'expérience. Analyse les données KPI fournies avec une vision stratégique globale. Identifie les tendances, opportunités de croissance, et recommandations stratégiques. Ton analyse doit être professionnelle, factuelle et orientée vers l\'action. Structure ton rapport en : 1) Vue d\'ensemble, 2) Points clés, 3) Tendances identifiées, 4) Recommandations stratégiques.', 1, 1, NULL, '2025-12-01 13:46:28', '2025-12-01 13:46:28'),
(2, 'Expert Ventes', 'Analyse de la performance commerciale et optimisation du chiffre d\'affaires', 'fas fa-dollar-sign', 'Tu es un expert en analyse des ventes et performance commerciale. Analyse les données de chiffre d\'affaires, panier moyen, et conversions. Identifie les opportunités d\'optimisation commerciale et les leviers de croissance du CA. Propose des stratégies concrètes pour améliorer les performances de vente. Structure ton rapport en : 1) Performance actuelle, 2) Analyse du panier moyen, 3) Opportunités commerciales, 4) Plan d\'action.', 1, 1, NULL, '2025-12-01 13:46:28', '2025-12-01 13:46:28'),
(3, 'Expert Comptable', 'Analyse financière : santé financière, créances, trésorerie, rentabilité', 'fas fa-calculator', 'Tu es un expert-comptable spécialisé dans les PME. Analyse la santé financière de l\'entreprise à travers les KPI : chiffre d\'affaires encaissé vs à encaisser, créances, délais de paiement. Identifie les risques financiers et opportunités d\'optimisation de la trésorerie. Ton analyse doit être rigoureuse et chiffrée. Structure : 1) Santé financière, 2) Analyse des créances, 3) Risques identifiés, 4) Recommandations financières.', 1, 1, NULL, '2025-12-01 13:46:28', '2025-12-01 13:46:28'),
(4, 'Manager Constructif', 'Performance collective et optimisation des processus', 'fas fa-users', 'Tu es un manager d\'équipe expérimenté et constructif. Analyse les performances collectives et individuelles avec bienveillance mais exigence. Identifie les forces de l\'équipe, les axes d\'amélioration des processus, et propose des solutions concrètes pour optimiser l\'organisation. Reste factuel et propositionnel. Structure : 1) Performance d\'équipe, 2) Points forts, 3) Axes d\'amélioration, 4) Plan d\'action.', 1, 1, NULL, '2025-12-01 13:46:28', '2025-12-01 13:46:28'),
(5, 'Coach Motivant', 'Rôle :\r\nTu es une experte en management, coaching d’équipes commerciales et analyse de performance.\r\nTon objectif est d’analyser les KPI que je vais te fournir et de produire un discours que je pourrai tenir directement à mes collaborateurs, lors de nos entretiens individuels.\r\n	3.	De fournir une liste de questions utiles à poser pendant l’entretien afin d’encourager l’échange, la prise de recul et la responsabilisation.\r\n\r\n\r\nStyle attendu :\r\n	•	Ton bienveillant, motivant, humain et concret\r\n	•	Posture de manager-coach : reconnaissance + axes de progrès + vision + mobilisation\r\n	•	Pas de jugement, pas de dureté\r\n	•	Discours structuré, inspirant et adapté au profil de l’employé\r\n	•	Langage simple, clair et professionnel\r\n	•	Entre 10 et 20 lignes maximum\r\n	•	Intégrer les chiffres uniquement pour mettre en perspective, jamais pour “accabler”\r\n\r\nContenu attendu dans le discours :\r\n	1.	Bravo / points positifs observés (basé sur les KPI)\r\n	2.	Reconnaissance des difficultés du mois\r\n	3.	Lecture constructive des KPI (mettre en lumière les leviers, pas les défauts)\r\n	4.	Axes de progression réalistes\r\n	5.	Plan d’action simple à suivre\r\n	6.	Message final fortement motivant, orienté réussite et confiance\r\n\r\n	4.	Axes d’amélioration concrets, atteignables, non culpabilisants\r\n	5.	Plan d’action court et simple\r\n	6.	Message de motivation final visant à reconstruire confiance et engagement\r\n\r\n	2.	Liste de questions à poser pendant l’entretien hebdomadaire avec un technicien/vendeur par exemple :\r\n\r\nFormat de la réponse :\r\n	•	Un discours rédigé type MEMO que je regarderais p[endant que je parle à mon employé\r\n	•	Le discours doit s’adresser directement à lui, avec le tutoiement (sauf si je précise autrement)\r\n\r\nFormat de la réponse :\r\n	•	Un seul paragraphe structuré comme un discours que je lirai directement\r\n	•	S’adresser à l’employé avec le tutoiement ou vouvoiement selon contexte (à préciser selon ce que je dirai ensuite)\r\n\r\nDonnées à analyser :', 'fas fa-trophy', 'Rôle :\r\nTu es une experte en management, coaching d’équipes commerciales et analyse de performance.\r\nTon objectif est d’analyser les KPI que je vais te fournir et de produire un discours que je pourrai tenir directement à mes collaborateurs, lors de nos entretiens individuels.\r\n	3.	De fournir une liste de questions utiles à poser pendant l’entretien afin d’encourager l’échange, la prise de recul et la responsabilisation.\r\n\r\n\r\nStyle attendu :\r\n	•	Ton bienveillant, motivant, humain et concret\r\n	•	Posture de manager-coach : reconnaissance + axes de progrès + vision + mobilisation\r\n	•	Pas de jugement, pas de dureté\r\n	•	Discours structuré, inspirant et adapté au profil de l’employé\r\n	•	Langage simple, clair et professionnel\r\n	•	Entre 10 et 20 lignes maximum\r\n	•	Intégrer les chiffres uniquement pour mettre en perspective, jamais pour “accabler”\r\n\r\nContenu attendu dans le discours :\r\n	1.	Bravo / points positifs observés (basé sur les KPI)\r\n	2.	Reconnaissance des difficultés du mois\r\n	3.	Lecture constructive des KPI (mettre en lumière les leviers, pas les défauts)\r\n	4.	Axes de progression réalistes\r\n	5.	Plan d’action simple à suivre\r\n	6.	Message final fortement motivant, orienté réussite et confiance\r\n\r\n	4.	Axes d’amélioration concrets, atteignables, non culpabilisants\r\n	5.	Plan d’action court et simple\r\n	6.	Message de motivation final visant à reconstruire confiance et engagement\r\n\r\n	2.	Liste de questions à poser pendant l’entretien hebdomadaire avec un technicien/vendeur par exemple :\r\n\r\nFormat de la réponse :\r\n	•	Un discours rédigé type MEMO que je regarderais p[endant que je parle à mon employé\r\n	•	Le discours doit s’adresser directement à lui, avec le tutoiement (sauf si je précise autrement)\r\n\r\nFormat de la réponse :\r\n	•	Un seul paragraphe structuré comme un discours que je lirai directement\r\n	•	S’adresser à l’employé avec le tutoiement ou vouvoiement selon contexte (à préciser selon ce que je dirai ensuite)\r\n\r\nDonnées à analyser :', 1, 1, NULL, '2025-12-01 13:46:28', '2025-12-02 01:16:44'),
(6, 'Manager Critique', 'Identification des problèmes et points d\'amélioration nécessaires', 'fas fa-exclamation-triangle', 'Tu es un manager exigeant et direct. Analyse les performances en identifiant clairement les problèmes, manquements et axes d\'amélioration prioritaires. Ne sois pas complice : pointe les retards, erreurs, contre-performances. Reste professionnel mais ferme. Propose des solutions mais insiste sur les responsabilités. Structure : 1) Problèmes identifiés, 2) Manquements par employé, 3) Impact sur l\'activité, 4) Exigences d\'amélioration.', 1, 1, NULL, '2025-12-01 13:46:28', '2025-12-01 13:46:28'),
(7, 'Directeur', 'Vue d\'ensemble stratégique et décisions à prendre', 'fas fa-briefcase', 'Tu es le directeur de l\'entreprise avec une vision globale. Analyse l\'ensemble des KPI pour prendre des décisions stratégiques. Évalue la performance globale, identifie les priorités, et détermine les actions critiques à entreprendre. Ton analyse doit être synthétique, décisionnelle et orientée résultats. Structure : 1) Synthèse exécutive, 2) Performance globale, 3) Priorités stratégiques, 4) Décisions à prendre.', 1, 1, NULL, '2025-12-01 13:46:28', '2025-12-01 13:46:28'),
(8, 'Analyste Comportemental', 'Analyse psychologique de l\'équipe et dynamiques de groupe', 'fas fa-brain', 'Tu es un psychologue organisationnel spécialisé dans l\'analyse comportementale en entreprise. Analyse les patterns de comportement des employés (retards, autonomie, collaboration) pour identifier les dynamiques de l\'équipe, les sources de motivation ou démotivation, et les recommandations RH. Structure : 1) Dynamiques observées, 2) Analyse comportementale, 3) Facteurs d\'engagement, 4) Recommandations RH.', 1, 1, NULL, '2025-12-01 13:46:28', '2025-12-01 13:46:28'),
(17, 'sad', 'dsa', 'fas fa-user', 'ads', 0, 1, 6, '2025-12-01 15:55:17', '2025-12-01 15:55:17'),
(18, 'testeur', 'tu m\'aidera a preparer mes one to one', 'fas fa-user', 'ton tole est de maider a preparer mes one to one avec mes employe en fonction des metrics que tu aura', 0, 1, 6, '2025-12-01 22:01:47', '2025-12-01 22:01:47');

-- --------------------------------------------------------

--
-- Structure de la table `label_layouts`
--

CREATE TABLE `label_layouts` (
  `id` int NOT NULL,
  `shop_id` int NOT NULL,
  `layout_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
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
  `cache_key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `cache_data` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` timestamp NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `marges_estimees`
--

CREATE TABLE `marges_estimees` (
  `id` int NOT NULL,
  `categorie` enum('telephone','pc','tablette') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
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
  `type_reparation` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `categorie` enum('smartphone','tablet','computer') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
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
  `contenu` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `type` enum('text','file','image','system','info') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'text',
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
  `file_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_size` int NOT NULL,
  `thumbnail_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
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
  `reaction` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
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
(2, 'asd12', 'asd', 3, 2, 2.00, 2, 'active', '2025-11-10', '2025-12-10', NULL, '2025-11-10 11:32:30', '2025-11-10 12:12:02'),
(3, 'reparer 10 telephone', 'reparer 10 tel', 2, 10, 100.00, 1000, 'active', '2025-11-26', '2025-12-26', NULL, '2025-11-26 17:40:19', '2025-11-26 17:40:19');

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
(4, 2, 1, 'ads', NULL, 'ads', 'approuvee', '', '2025-11-10 11:32:48', NULL, NULL, '2025-11-10 11:37:27', 1, 'completion', NULL, '2025-11-10 11:37:27'),
(5, 3, 1, 'lcd', NULL, 'ok', 'approuvee', 'ok', '2025-11-26 17:41:39', NULL, NULL, '2025-11-26 17:42:16', 1, 'completion', NULL, '2025-11-26 17:42:16'),
(6, 4, 1, 'bnku', NULL, 'bu', 'en_attente', NULL, '2025-12-15 00:41:53', NULL, NULL, NULL, NULL, 'completion', NULL, '2025-12-15 00:41:53');

-- --------------------------------------------------------

--
-- Structure de la table `mouvements_stock`
--

CREATE TABLE `mouvements_stock` (
  `id` int NOT NULL,
  `produit_id` int NOT NULL,
  `fournisseur_id` int DEFAULT NULL,
  `type_mouvement` enum('entree','sortie') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantite` int NOT NULL,
  `date_mouvement` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `motif` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `notifications`
--

CREATE TABLE `notifications` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `notification_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'general',
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `related_id` int DEFAULT NULL,
  `related_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `action_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_important` tinyint(1) NOT NULL DEFAULT '0',
  `is_broadcast` tinyint(1) NOT NULL DEFAULT '0',
  `created_by` int DEFAULT NULL,
  `status` enum('new','pending','read') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'new',
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
  `type_notification` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
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
  `type_code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `icon` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `color` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `importance` enum('basse','normale','haute','critique') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'normale'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `oauth_tokens`
--

CREATE TABLE `oauth_tokens` (
  `id` int NOT NULL,
  `shop_id` int NOT NULL,
  `access_token` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `refresh_token` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
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
  `checkout_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `checkout_reference` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `montant` decimal(10,2) NOT NULL,
  `currency` varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'EUR',
  `statut_paiement` enum('pending','paid','failed','cancelled') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `transaction_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_paiement` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modification` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `client_info` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Infos client JSON',
  `description` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `erreur_message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `parametres`
--

CREATE TABLE `parametres` (
  `id` int NOT NULL,
  `cle` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `valeur` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
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
  `nom` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telephone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `adresse` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `date_creation` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `actif` tinyint(1) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  `url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `date_upload` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  `statut` enum('EN_ATTENTE','VALIDÉ','REMBOURSÉ','ANNULÉ') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'EN_ATTENTE'
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

-- --------------------------------------------------------

--
-- Structure de la table `preferences`
--

CREATE TABLE `preferences` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `theme` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'light',
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
  `comment` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
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
  `status` enum('pending','approved','rejected','cancelled') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `comment` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `document_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Chemin vers le document justificatif',
  `created_by` int NOT NULL,
  `approved_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `presence_events`
--

INSERT INTO `presence_events` (`id`, `employee_id`, `type_id`, `date_start`, `date_end`, `duration_minutes`, `status`, `comment`, `document_path`, `created_by`, `approved_by`, `created_at`, `updated_at`) VALUES
(2, 6, 1, '2025-11-07 00:00:00', NULL, 30, 'approved', 'sad', NULL, 6, 6, '2025-11-07 00:40:16', '2025-11-08 19:59:21'),
(5, 13, 1, '2025-11-08 00:00:00', NULL, 21, 'rejected', 'das', NULL, 13, 6, '2025-11-08 20:59:25', '2025-11-08 21:07:33'),
(6, 13, 1, '2025-11-08 00:00:00', NULL, 21, 'rejected', 'das', NULL, 13, 6, '2025-11-08 21:03:12', '2025-11-08 21:07:33'),
(7, 13, 1, '2025-11-08 00:00:00', NULL, 21, 'approved', 'das', NULL, 13, 6, '2025-11-08 21:05:24', '2025-11-08 21:07:32'),
(8, 7, 1, '2025-12-12 00:00:00', NULL, 30, 'approved', 'Bus', NULL, 7, 1, '2025-12-12 09:44:48', '2025-12-15 03:30:32'),
(9, 6, 2, '2025-12-05 00:00:00', '2025-12-05 00:00:00', NULL, 'approved', 'Absence', NULL, 1, 1, '2025-12-15 03:32:29', '2025-12-15 03:32:36'),
(10, 6, 2, '2025-12-11 00:00:00', '2025-12-12 00:00:00', NULL, 'approved', 'gastro', NULL, 1, 1, '2025-12-15 03:34:31', '2025-12-15 03:50:43'),
(11, 1, 1, '2025-12-04 00:00:00', NULL, 300, 'approved', '5 heure d\'absence ( 14h-19h )', NULL, 1, 1, '2025-12-15 03:35:28', '2025-12-15 03:35:34');

-- --------------------------------------------------------

--
-- Structure de la table `presence_history`
--

CREATE TABLE `presence_history` (
  `id` int NOT NULL,
  `event_id` int NOT NULL,
  `field_changed` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `old_value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `new_value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `changed_by` int NOT NULL,
  `changed_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `presence_types`
--

CREATE TABLE `presence_types` (
  `id` int NOT NULL,
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `display_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `color_code` varchar(7) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '#007bff',
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
  `reference` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `nom` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `categorie_id` int DEFAULT NULL,
  `fournisseur_id` int DEFAULT NULL,
  `prix_achat` decimal(10,2) DEFAULT NULL,
  `prix_vente` decimal(10,2) DEFAULT NULL,
  `quantite` int DEFAULT '0',
  `seuil_alerte` int DEFAULT '5',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `status` enum('normal','temporaire','a_retourner') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'normal',
  `date_limite_retour` date DEFAULT NULL,
  `motif_retour` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `suivre_stock` tinyint(1) DEFAULT '0' COMMENT 'Indique si le produit doit être suivi dans le système de vérification de stock'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `produits`
--

INSERT INTO `produits` (`id`, `reference`, `nom`, `description`, `categorie_id`, `fournisseur_id`, `prix_achat`, `prix_vente`, `quantite`, `seuil_alerte`, `created_at`, `updated_at`, `status`, `date_limite_retour`, `motif_retour`, `suivre_stock`) VALUES
(2, '3667075067146', 'verre trempe iphone 16 pro', 'verre trempe iphone 16 pro', NULL, 2, 0.70, 10.00, 0, 5, '2025-09-28 21:22:04', '2025-12-15 22:20:36', 'normal', NULL, NULL, 1),
(3, '3701569368019', 'Ecran A53', '', NULL, 2, 10.00, 50.00, 0, 5, '2025-09-30 11:00:29', '2025-09-30 11:00:29', 'normal', NULL, NULL, 0),
(4, '0458210015541', 'Hshd', 'Djdjd', NULL, 11, 8.00, 8.00, 0, 5, '2025-11-09 23:48:41', '2025-11-09 23:48:41', 'normal', NULL, NULL, 0),
(5, '0458210014421', 'Dhe', 'Ehe', NULL, 2, 6.00, 6.00, 2, 5, '2025-11-09 23:49:24', '2025-12-01 00:26:59', 'normal', NULL, NULL, 0),
(7, 'asd', 'asd', 'sad', NULL, 11, 2.00, 2.00, 20, 522, '2025-12-15 00:22:47', '2025-12-15 00:22:47', 'normal', NULL, NULL, 0),
(8, '0421094277411', 'kijuhyg', 'ikjuhg', NULL, 11, 1234.00, 12345.00, 234, 5, '2025-12-15 00:23:19', '2025-12-15 00:23:19', 'normal', NULL, NULL, 0),
(9, '3524710184227', 'kijuh', 'fgh', NULL, 11, 1234.00, 2345.00, 212, 5, '2025-12-15 00:24:57', '2025-12-15 03:24:52', 'normal', NULL, NULL, 0);

-- --------------------------------------------------------

--
-- Structure de la table `push_subscriptions`
--

CREATE TABLE `push_subscriptions` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `endpoint` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `auth_key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `p256dh_key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
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
  `type_appareil` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `photo_identite` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `photo_appareil` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `signature` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `client_photo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `date_rachat` datetime DEFAULT CURRENT_TIMESTAMP,
  `sin` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `fonctionnel` tinyint(1) DEFAULT '0',
  `prix` decimal(10,2) DEFAULT NULL,
  `modele` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `numero_serie` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
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
(9, 1029, 'tablette', 'identite_1762395838_690c06be3e3ce.jpg', 'appareil_1762395838_690c06be3e3fd.jpg', 'signature_1762395838_690c06be3e351.png', 'client_1762395838_690c06be3e41f.jpg', '2025-11-06 03:23:58', 'ds', 1, 21.00, 'sad', NULL, NULL),
(10, 1, 'tablette', 'identite_1764528308_692c90b434b10.jpg', 'appareil_1764528308_692c90b434b3c.jpg', 'signature_1764528308_692c90b434a0d.png', NULL, '2025-11-30 19:45:08', 'asd', 0, 0.00, 'sad', NULL, NULL),
(11, 1498, 'ordinateur_fixe', 'identite_1764528405_692c9115d7000.jpg', 'appareil_1764528405_692c9115d703a.jpg', 'signature_1764528405_692c9115d6f76.png', NULL, '2025-11-30 19:46:45', 'asd', 0, 0.00, 'sad', NULL, NULL),
(12, 1029, 'ordinateur_portable', 'identite_1764528593_692c91d169e43.jpg', 'appareil_1764528593_692c91d169e6b.jpg', 'signature_1764528593_692c91d169dc3.png', 'client_1764528593_692c91d169eae.jpg', '2025-11-30 19:49:53', '123', 0, 0.00, '213', NULL, NULL),
(13, 1248, 'ordinateur_portable', 'identite_1764528851_692c92d39f662.jpg', 'appareil_1764528851_692c92d39f694.jpg', 'signature_1764528851_692c92d39f5bf.png', 'client_1764528851_692c92d39f6e9.jpg', '2025-11-30 19:54:11', 'asd', 0, 0.00, 'asd', NULL, NULL),
(14, 1029, 'tablette', 'identite_1764528913_692c9311e6f83.jpg', 'appareil_1764528913_692c9311e6fc4.jpg', 'signature_1764528913_692c9311e6f0f.png', 'client_1764528913_692c9311e700d.jpg', '2025-11-30 19:55:13', '22', 1, 0.00, '11', NULL, NULL),
(15, 1028, 'tablette', 'identite_1764529145_692c93f94ffd2.jpg', 'appareil_1764529145_692c93f94fff8.jpg', 'signature_1764529145_692c93f94ff25.png', 'client_1764529145_692c93f950037.jpg', '2025-11-30 19:59:05', '22', 1, 9.00, '11', NULL, NULL);

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
  `statut` enum('succes','echec') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'succes',
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `date_creation` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `reparations`
--

CREATE TABLE `reparations` (
  `id` int NOT NULL,
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
  `cree_par` int DEFAULT NULL,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déclencheurs `reparations`
--
DELIMITER $$
CREATE TRIGGER `trigger_creation_garantie` AFTER UPDATE ON `reparations` FOR EACH ROW BEGIN
    DECLARE garantie_active INT DEFAULT 0$$
DELIMITER ;

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
  `statut_avant` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `statut_apres` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `est_principal` tinyint(1) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `reparation_logs`
--

CREATE TABLE `reparation_logs` (
  `id` int NOT NULL,
  `reparation_id` int NOT NULL,
  `employe_id` int NOT NULL,
  `action_type` enum('demarrage','terminer','changement_statut','ajout_note','modification','modification_prix','creation','autre') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_action` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `statut_avant` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `statut_apres` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `details` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `reparation_sms`
--

CREATE TABLE `reparation_sms` (
  `id` int NOT NULL,
  `reparation_id` int NOT NULL,
  `template_id` int NOT NULL,
  `telephone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_envoi` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `statut_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `retours`
--

CREATE TABLE `retours` (
  `id` int NOT NULL,
  `produit_id` int NOT NULL,
  `date_creation` datetime DEFAULT CURRENT_TIMESTAMP,
  `date_limite` date NOT NULL,
  `statut` enum('en_attente','en_preparation','expedie','livre','a_verifier','termine') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'en_attente',
  `numero_suivi` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `montant_rembourse` decimal(10,2) DEFAULT NULL,
  `montant_rembourse_client` decimal(10,2) DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `colis_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `scheduled_notifications`
--

CREATE TABLE `scheduled_notifications` (
  `id` int NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `scheduled_datetime` datetime NOT NULL,
  `sent_datetime` datetime DEFAULT NULL,
  `target_user_id` int DEFAULT NULL,
  `is_broadcast` tinyint(1) NOT NULL DEFAULT '0',
  `notification_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'general',
  `action_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `status` enum('pending','sent','failed','cancelled') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `options` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
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
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `montant` decimal(10,2) NOT NULL,
  `date_service` datetime DEFAULT CURRENT_TIMESTAMP,
  `statut` enum('EN_ATTENTE','VALIDÉ','ANNULÉ') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'EN_ATTENTE'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `shop_notes`
--

CREATE TABLE `shop_notes` (
  `id` int NOT NULL,
  `note_type` enum('fermeture','travaux','evenement','probleme_technique','stock','autre') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Type d''événement',
  `title` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Titre de l''événement',
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Description détaillée',
  `date_start` date NOT NULL COMMENT 'Date de début',
  `date_end` date DEFAULT NULL COMMENT 'Date de fin (NULL si événement ponctuel)',
  `impact_level` enum('info','low','medium','high','critical') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'info' COMMENT 'Niveau d''impact sur l''activité',
  `affects_kpi` tinyint(1) DEFAULT '1' COMMENT '1 si cet événement impacte les KPI',
  `include_in_ai_analysis` tinyint(1) DEFAULT '1' COMMENT '1 si inclus dans l''analyse IA',
  `created_by` int NOT NULL COMMENT 'ID de l''utilisateur créateur',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Notes contextuelles du magasin pour analyses IA';

--
-- Déchargement des données de la table `shop_notes`
--

INSERT INTO `shop_notes` (`id`, `note_type`, `title`, `description`, `date_start`, `date_end`, `impact_level`, `affects_kpi`, `include_in_ai_analysis`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'autre', 'qwee', 'asd', '2025-12-01', '2025-12-01', 'medium', 1, 1, 6, '2025-12-01 16:15:17', '2025-12-01 16:15:17');

-- --------------------------------------------------------

--
-- Structure de la table `sms_campaigns`
--

CREATE TABLE `sms_campaigns` (
  `id` int NOT NULL,
  `nom` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
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
  `telephone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `statut` enum('envoyé','échec') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'envoyé',
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

-- --------------------------------------------------------

--
-- Structure de la table `sms_logs`
--

CREATE TABLE `sms_logs` (
  `id` int NOT NULL,
  `recipient` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` int DEFAULT NULL,
  `reparation_id` int DEFAULT NULL,
  `response` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `date_envoi` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `sms_template`
--

CREATE TABLE `sms_template` (
  `id` int NOT NULL,
  `statut_id` int DEFAULT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `sms_templates`
--

CREATE TABLE `sms_templates` (
  `id` int NOT NULL,
  `nom` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `contenu` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `statut_id` int DEFAULT NULL,
  `est_actif` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `variables` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `type` enum('devis','relance','notification','autre') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'autre'
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
(7, 'Nouvelle Commande', 'saddsa', 3, 1, '2025-04-10 02:15:55', '2025-12-14 23:41:01', NULL, NULL, 'autre'),
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
  `nom` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `exemple` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
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

-- --------------------------------------------------------

--
-- Structure de la table `statuts`
--

CREATE TABLE `statuts` (
  `id` int NOT NULL,
  `nom` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
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
  `code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `nom` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `categorie` enum('nouvelle','en_cours','en_attente','termine','annule') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `statut_categories`
--

CREATE TABLE `statut_categories` (
  `id` int NOT NULL,
  `nom` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `couleur` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
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
  `barcode` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `category` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `quantity` int NOT NULL DEFAULT '0',
  `price` decimal(10,2) DEFAULT '0.00',
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `date_created` datetime NOT NULL,
  `date_updated` datetime DEFAULT NULL,
  `status` enum('normal','temporaire','a_retourner') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'normal',
  `date_limite_retour` date DEFAULT NULL,
  `motif_retour` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `stock_history`
--

CREATE TABLE `stock_history` (
  `id` int NOT NULL,
  `product_id` int NOT NULL,
  `action` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `quantity` int NOT NULL,
  `note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `user_id` int DEFAULT NULL,
  `date_created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `taches`
--

CREATE TABLE `taches` (
  `id` int NOT NULL,
  `titre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `priorite` enum('basse','moyenne','haute','urgente') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'moyenne',
  `statut` enum('a_faire','en_cours','termine','annule') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'a_faire',
  `date_creation` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `date_limite` date DEFAULT NULL,
  `date_fin` timestamp NULL DEFAULT NULL,
  `employe_id` int DEFAULT NULL,
  `created_by` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

-- --------------------------------------------------------

--
-- Structure de la table `tasks`
--

CREATE TABLE `tasks` (
  `id` int NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `status` enum('en_attente','en_cours','termine','aide_necessaire') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'en_attente',
  `priority` enum('basse','moyenne','haute','urgente') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'moyenne',
  `assigned_to` int DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `due_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `Task_logs`
--

CREATE TABLE `Task_logs` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `task_id` int NOT NULL,
  `action_type` varchar(50) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `task_logs`
--

CREATE TABLE `task_logs` (
  `id` int NOT NULL,
  `tache_id` int NOT NULL,
  `employe_id` int NOT NULL,
  `action_type` enum('demarrage','terminer','changement_statut','ajout_note','modification','creation','autre') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_action` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `statut_avant` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `statut_apres` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `details` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
(5853, NULL, 'afternoon', '14:00:00', '19:00:00', 1, '2025-11-09 23:57:06', '2025-11-09 23:57:06'),
(5854, NULL, 'morning', '08:00:00', '12:30:00', 1, '2025-11-10 23:20:17', '2025-11-10 23:20:17'),
(5855, NULL, 'afternoon', '14:00:00', '19:00:00', 1, '2025-11-10 23:20:17', '2025-11-10 23:20:17'),
(5856, NULL, 'morning', '08:00:00', '12:30:00', 1, '2025-11-10 23:20:17', '2025-11-10 23:20:17'),
(5857, NULL, 'afternoon', '14:00:00', '19:00:00', 1, '2025-11-10 23:20:17', '2025-11-10 23:20:17'),
(5858, NULL, 'morning', '08:00:00', '12:30:00', 1, '2025-11-10 23:20:17', '2025-11-10 23:20:17'),
(5859, NULL, 'afternoon', '14:00:00', '19:00:00', 1, '2025-11-10 23:20:17', '2025-11-10 23:20:17'),
(5860, NULL, 'morning', '08:00:00', '12:30:00', 1, '2025-11-10 23:20:19', '2025-11-10 23:20:19'),
(5861, NULL, 'afternoon', '14:00:00', '19:00:00', 1, '2025-11-10 23:20:19', '2025-11-10 23:20:19'),
(5862, NULL, 'morning', '08:00:00', '12:30:00', 1, '2025-11-10 23:20:19', '2025-11-10 23:20:19'),
(5863, NULL, 'afternoon', '14:00:00', '19:00:00', 1, '2025-11-10 23:20:19', '2025-11-10 23:20:19'),
(5864, NULL, 'morning', '08:00:00', '12:30:00', 1, '2025-11-10 23:20:19', '2025-11-10 23:20:19'),
(5865, NULL, 'afternoon', '14:00:00', '19:00:00', 1, '2025-11-10 23:20:19', '2025-11-10 23:20:19'),
(5866, NULL, 'morning', '08:00:00', '12:30:00', 1, '2025-11-10 23:20:22', '2025-11-10 23:20:22'),
(5867, NULL, 'afternoon', '14:00:00', '19:00:00', 1, '2025-11-10 23:20:22', '2025-11-10 23:20:22'),
(5868, NULL, 'morning', '08:00:00', '12:30:00', 1, '2025-11-10 23:20:51', '2025-11-10 23:20:51'),
(5869, NULL, 'afternoon', '14:00:00', '19:00:00', 1, '2025-11-10 23:20:51', '2025-11-10 23:20:51'),
(5870, NULL, 'morning', '08:00:00', '12:30:00', 1, '2025-11-10 23:20:51', '2025-11-10 23:20:51'),
(5871, NULL, 'afternoon', '14:00:00', '19:00:00', 1, '2025-11-10 23:20:51', '2025-11-10 23:20:51'),
(5872, NULL, 'morning', '08:00:00', '12:30:00', 1, '2025-11-10 23:20:51', '2025-11-10 23:20:51'),
(5873, NULL, 'afternoon', '14:00:00', '19:00:00', 1, '2025-11-10 23:20:51', '2025-11-10 23:20:51'),
(5874, NULL, 'morning', '08:00:00', '12:30:00', 1, '2025-11-10 23:20:52', '2025-11-10 23:20:52'),
(5875, NULL, 'afternoon', '14:00:00', '19:00:00', 1, '2025-11-10 23:20:52', '2025-11-10 23:20:52'),
(5876, NULL, 'morning', '08:00:00', '12:30:00', 1, '2025-11-10 23:20:52', '2025-11-10 23:20:52'),
(5877, NULL, 'afternoon', '14:00:00', '19:00:00', 1, '2025-11-10 23:20:52', '2025-11-10 23:20:52'),
(5878, NULL, 'morning', '08:00:00', '12:30:00', 1, '2025-11-10 23:20:52', '2025-11-10 23:20:52'),
(5879, NULL, 'afternoon', '14:00:00', '19:00:00', 1, '2025-11-10 23:20:52', '2025-11-10 23:20:52'),
(5880, NULL, 'morning', '08:00:00', '12:30:00', 1, '2025-11-10 23:20:57', '2025-11-10 23:20:57'),
(5881, NULL, 'afternoon', '14:00:00', '19:00:00', 1, '2025-11-10 23:20:57', '2025-11-10 23:20:57'),
(5882, NULL, 'morning', '08:00:00', '12:30:00', 1, '2025-11-10 23:20:57', '2025-11-10 23:20:57'),
(5883, NULL, 'afternoon', '14:00:00', '19:00:00', 1, '2025-11-10 23:20:57', '2025-11-10 23:20:57'),
(5884, NULL, 'morning', '08:00:00', '12:30:00', 1, '2025-11-10 23:20:57', '2025-11-10 23:20:57'),
(5885, NULL, 'afternoon', '14:00:00', '19:00:00', 1, '2025-11-10 23:20:57', '2025-11-10 23:20:57'),
(5886, NULL, 'morning', '08:00:00', '12:30:00', 1, '2025-11-10 23:21:24', '2025-11-10 23:21:24'),
(5887, NULL, 'afternoon', '14:00:00', '19:00:00', 1, '2025-11-10 23:21:24', '2025-11-10 23:21:24'),
(5888, NULL, 'morning', '08:00:00', '12:30:00', 1, '2025-11-10 23:21:24', '2025-11-10 23:21:24'),
(5889, NULL, 'afternoon', '14:00:00', '19:00:00', 1, '2025-11-10 23:21:24', '2025-11-10 23:21:24'),
(5890, NULL, 'morning', '08:00:00', '12:30:00', 1, '2025-11-10 23:21:24', '2025-11-10 23:21:24'),
(5891, NULL, 'afternoon', '14:00:00', '19:00:00', 1, '2025-11-10 23:21:24', '2025-11-10 23:21:24'),
(5892, NULL, 'morning', '08:00:00', '12:30:00', 1, '2025-11-10 23:21:25', '2025-11-10 23:21:25'),
(5893, NULL, 'afternoon', '14:00:00', '19:00:00', 1, '2025-11-10 23:21:25', '2025-11-10 23:21:25'),
(5894, NULL, 'morning', '08:00:00', '12:30:00', 1, '2025-11-10 23:22:32', '2025-11-10 23:22:32'),
(5895, NULL, 'afternoon', '14:00:00', '19:00:00', 1, '2025-11-10 23:22:32', '2025-11-10 23:22:32'),
(5896, NULL, 'morning', '08:00:00', '12:30:00', 1, '2025-11-10 23:22:32', '2025-11-10 23:22:32'),
(5897, NULL, 'afternoon', '14:00:00', '19:00:00', 1, '2025-11-10 23:22:32', '2025-11-10 23:22:32'),
(5898, NULL, 'morning', '08:00:00', '12:30:00', 1, '2025-11-10 23:22:32', '2025-11-10 23:22:32'),
(5899, NULL, 'afternoon', '14:00:00', '19:00:00', 1, '2025-11-10 23:22:32', '2025-11-10 23:22:32'),
(5900, NULL, 'morning', '08:00:00', '12:30:00', 1, '2025-11-10 23:22:33', '2025-11-10 23:22:33'),
(5901, NULL, 'afternoon', '14:00:00', '19:00:00', 1, '2025-11-10 23:22:33', '2025-11-10 23:22:33'),
(5902, NULL, 'morning', '08:00:00', '12:30:00', 1, '2025-11-10 23:23:11', '2025-11-10 23:23:11'),
(5903, NULL, 'afternoon', '14:00:00', '19:00:00', 1, '2025-11-10 23:23:11', '2025-11-10 23:23:11'),
(5904, NULL, 'morning', '08:00:00', '12:30:00', 1, '2025-11-10 23:23:11', '2025-11-10 23:23:11'),
(5905, NULL, 'afternoon', '14:00:00', '19:00:00', 1, '2025-11-10 23:23:11', '2025-11-10 23:23:11'),
(5906, NULL, 'morning', '08:00:00', '12:30:00', 1, '2025-11-10 23:23:11', '2025-11-10 23:23:11'),
(5907, NULL, 'afternoon', '14:00:00', '19:00:00', 1, '2025-11-10 23:23:11', '2025-11-10 23:23:11'),
(5908, NULL, 'morning', '08:00:00', '12:30:00', 1, '2025-11-10 23:23:12', '2025-11-10 23:23:12'),
(5909, NULL, 'afternoon', '14:00:00', '19:00:00', 1, '2025-11-10 23:23:12', '2025-11-10 23:23:12'),
(5910, NULL, 'morning', '08:00:00', '12:30:00', 1, '2025-11-10 23:55:21', '2025-11-10 23:55:21'),
(5911, NULL, 'afternoon', '14:00:00', '19:00:00', 1, '2025-11-10 23:55:21', '2025-11-10 23:55:21'),
(5912, NULL, 'morning', '08:00:00', '12:30:00', 1, '2025-11-10 23:56:10', '2025-11-10 23:56:10'),
(5913, NULL, 'afternoon', '14:00:00', '19:00:00', 1, '2025-11-10 23:56:10', '2025-11-10 23:56:10'),
(5914, NULL, 'morning', '08:00:00', '12:30:00', 1, '2025-11-10 23:56:20', '2025-11-10 23:56:20'),
(5915, NULL, 'afternoon', '14:00:00', '19:00:00', 1, '2025-11-10 23:56:20', '2025-11-10 23:56:20'),
(5916, NULL, 'morning', '08:00:00', '12:30:00', 1, '2025-11-10 23:56:22', '2025-11-10 23:56:22'),
(5917, NULL, 'afternoon', '14:00:00', '19:00:00', 1, '2025-11-10 23:56:22', '2025-11-10 23:56:22'),
(5918, NULL, 'morning', '08:00:00', '12:30:00', 1, '2025-11-10 23:57:24', '2025-11-10 23:57:24'),
(5919, NULL, 'afternoon', '14:00:00', '19:00:00', 1, '2025-11-10 23:57:24', '2025-11-10 23:57:24'),
(5920, NULL, 'morning', '08:00:00', '12:30:00', 1, '2025-11-10 23:57:24', '2025-11-10 23:57:24'),
(5921, NULL, 'afternoon', '14:00:00', '19:00:00', 1, '2025-11-10 23:57:24', '2025-11-10 23:57:24'),
(5922, NULL, 'morning', '08:00:00', '12:30:00', 1, '2025-11-10 23:57:24', '2025-11-10 23:57:24'),
(5923, NULL, 'afternoon', '14:00:00', '19:00:00', 1, '2025-11-10 23:57:24', '2025-11-10 23:57:24'),
(5924, NULL, 'morning', '08:00:00', '12:30:00', 1, '2025-11-10 23:57:26', '2025-11-10 23:57:26'),
(5925, NULL, 'afternoon', '14:00:00', '19:00:00', 1, '2025-11-10 23:57:26', '2025-11-10 23:57:26'),
(5926, NULL, 'morning', '08:00:00', '12:30:00', 1, '2025-11-10 23:57:28', '2025-11-10 23:57:28'),
(5927, NULL, 'afternoon', '14:00:00', '19:00:00', 1, '2025-11-10 23:57:28', '2025-11-10 23:57:28'),
(5928, NULL, 'morning', '08:00:00', '12:30:00', 1, '2025-11-10 23:57:28', '2025-11-10 23:57:28'),
(5929, NULL, 'afternoon', '14:00:00', '19:00:00', 1, '2025-11-10 23:57:28', '2025-11-10 23:57:28'),
(5930, NULL, 'morning', '08:00:00', '12:30:00', 1, '2025-11-10 23:57:28', '2025-11-10 23:57:28'),
(5931, NULL, 'afternoon', '14:00:00', '19:00:00', 1, '2025-11-10 23:57:28', '2025-11-10 23:57:28'),
(5932, NULL, 'morning', '08:00:00', '12:30:00', 1, '2025-11-10 23:57:30', '2025-11-10 23:57:30'),
(5933, NULL, 'afternoon', '14:00:00', '19:00:00', 1, '2025-11-10 23:57:30', '2025-11-10 23:57:30'),
(5934, NULL, 'morning', '08:00:00', '12:30:00', 1, '2025-11-11 00:00:15', '2025-11-11 00:00:15'),
(5935, NULL, 'afternoon', '14:00:00', '19:00:00', 1, '2025-11-11 00:00:15', '2025-11-11 00:00:15'),
(5936, NULL, 'morning', '08:00:00', '12:30:00', 1, '2025-11-11 00:00:15', '2025-11-11 00:00:15'),
(5937, NULL, 'afternoon', '14:00:00', '19:00:00', 1, '2025-11-11 00:00:15', '2025-11-11 00:00:15'),
(5938, NULL, 'morning', '08:00:00', '12:30:00', 1, '2025-11-11 00:00:15', '2025-11-11 00:00:15'),
(5939, NULL, 'afternoon', '14:00:00', '19:00:00', 1, '2025-11-11 00:00:15', '2025-11-11 00:00:15'),
(5940, NULL, 'morning', '08:00:00', '12:30:00', 1, '2025-11-11 00:00:16', '2025-11-11 00:00:16'),
(5941, NULL, 'afternoon', '14:00:00', '19:00:00', 1, '2025-11-11 00:00:16', '2025-11-11 00:00:16'),
(5942, NULL, 'morning', '08:00:00', '12:30:00', 1, '2025-11-11 00:00:52', '2025-11-11 00:00:52'),
(5943, NULL, 'afternoon', '14:00:00', '19:00:00', 1, '2025-11-11 00:00:52', '2025-11-11 00:00:52'),
(5944, NULL, 'morning', '08:00:00', '12:30:00', 1, '2025-11-11 00:00:52', '2025-11-11 00:00:52'),
(5945, NULL, 'afternoon', '14:00:00', '19:00:00', 1, '2025-11-11 00:00:52', '2025-11-11 00:00:52'),
(5946, NULL, 'morning', '08:00:00', '12:30:00', 1, '2025-11-11 00:00:52', '2025-11-11 00:00:52'),
(5947, NULL, 'afternoon', '14:00:00', '19:00:00', 1, '2025-11-11 00:00:52', '2025-11-11 00:00:52'),
(5948, NULL, 'morning', '08:00:00', '12:30:00', 1, '2025-11-11 00:00:53', '2025-11-11 00:00:53'),
(5949, NULL, 'afternoon', '14:00:00', '19:00:00', 1, '2025-11-11 00:00:53', '2025-11-11 00:00:53'),
(5950, NULL, 'morning', '08:00:00', '12:30:00', 1, '2025-11-11 00:00:55', '2025-11-11 00:00:55'),
(5951, NULL, 'afternoon', '14:00:00', '19:00:00', 1, '2025-11-11 00:00:55', '2025-11-11 00:00:55'),
(5952, NULL, 'morning', '08:00:00', '12:30:00', 1, '2025-11-11 00:00:55', '2025-11-11 00:00:55'),
(5953, NULL, 'afternoon', '14:00:00', '19:00:00', 1, '2025-11-11 00:00:55', '2025-11-11 00:00:55'),
(5954, NULL, 'morning', '08:00:00', '12:30:00', 1, '2025-11-11 00:00:55', '2025-11-11 00:00:55'),
(5955, NULL, 'afternoon', '14:00:00', '19:00:00', 1, '2025-11-11 00:00:55', '2025-11-11 00:00:55'),
(5956, NULL, 'morning', '08:00:00', '12:30:00', 1, '2025-11-11 00:00:57', '2025-11-11 00:00:57'),
(5957, NULL, 'afternoon', '14:00:00', '19:00:00', 1, '2025-11-11 00:00:57', '2025-11-11 00:00:57');

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
(62, 12, '2025-11-11 00:55:21', '2025-11-11 01:44:00', NULL, NULL, NULL, 0.00, NULL, 'completed', NULL, '84.98.112.56', NULL, 1, 0, 'Pointage hors créneau spécifique (01:01:00-22:22:00)', NULL, '2025-11-10 23:55:21', '2025-11-11 00:44:00', 'GPS: ,', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"user_agent\":\"Mozilla\\/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/142.0.0.0 Safari\\/537.36\",\"accept_language\":\"fr-FR,fr;q=0.9\",\"accept_encoding\":\"gzip, deflate, br, zstd\",\"accept\":\"*\\/*\",\"connection\":\"\",\"host\":\"mdg.servo.tools\",\"referer\":\"https:\\/\\/mdg.servo.tools\\/\",\"forwarded_for\":\"\",\"real_ip\":\"\",\"request_time\":1762818921,\"remote_port\":\"49345\",\"server_addr\":\"82.29.168.205\",\"request_uri\":\"\\/time_tracking_api.php?action=clock_in\"}', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-10 23:55:21', 2, 0, NULL, NULL, NULL, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', NULL, 0),
(63, 6, '2025-11-11 00:44:05', '2025-11-11 01:44:10', NULL, NULL, NULL, 0.00, NULL, 'completed', NULL, '84.98.112.56', NULL, 1, 0, 'Pointage hors créneau global (08:00:00-12:30:00)', NULL, '2025-11-11 00:44:05', '2025-11-11 00:44:10', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-11 00:44:05', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(64, 6, '2025-11-11 00:49:28', '2025-11-23 17:42:43', NULL, NULL, NULL, 0.00, 16.89, 'completed', NULL, '84.98.112.56', NULL, 1, 0, 'Pointage hors créneau global (08:00:00-12:30:00)', NULL, '2025-11-11 00:49:28', '2025-11-26 22:34:43', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-11 00:49:28', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(65, 6, '2025-11-23 17:42:46', '2025-11-26 23:34:53', NULL, NULL, NULL, 0.00, NULL, 'completed', NULL, '37.167.40.211', NULL, 1, 1, NULL, NULL, '2025-11-23 17:42:46', '2025-11-26 22:34:53', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-23 17:42:46', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(66, 12, '2025-11-26 20:34:31', '2025-11-26 23:34:55', NULL, NULL, NULL, 0.00, 0.00, 'completed', NULL, '37.167.143.214', NULL, 1, 0, 'Pointage hors créneau autorisé (03:33:00-03:44:00)', NULL, '2025-11-26 19:34:31', '2025-11-26 22:34:55', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-26 19:34:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(67, 12, '2025-11-26 20:35:05', '2025-11-26 23:34:55', NULL, NULL, NULL, 0.00, 2.95, 'completed', NULL, '37.167.143.214', NULL, 1, 0, 'Pointage hors créneau autorisé (03:33:00-03:44:00)', NULL, '2025-11-26 19:35:05', '2025-11-26 22:34:55', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-26 19:35:05', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(68, 12, '2025-11-26 23:32:14', '2025-11-26 23:34:55', NULL, NULL, NULL, 0.00, 0.04, 'completed', NULL, '37.167.15.238', NULL, 1, 0, 'Pointage hors créneau autorisé (03:33:00-03:44:00)', NULL, '2025-11-26 22:32:14', '2025-11-26 22:34:55', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-26 22:32:14', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(69, 12, '2025-11-26 23:34:30', '2025-11-26 23:34:55', NULL, NULL, NULL, 0.00, NULL, 'completed', NULL, '37.167.15.238', NULL, 1, 0, 'Pointage hors créneau autorisé (03:33:00-03:44:00)', NULL, '2025-11-26 22:34:30', '2025-11-26 22:34:55', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-26 22:34:30', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(70, 12, '2025-11-26 23:35:00', '2025-11-26 23:44:09', NULL, NULL, NULL, 0.00, 0.15, 'completed', NULL, '37.167.15.238', NULL, 1, 0, 'Pointage hors créneau autorisé (03:33:00-03:44:00)', NULL, '2025-11-26 22:35:00', '2025-11-26 22:44:09', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-26 22:35:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(71, 12, '2025-11-26 23:43:52', '2025-11-26 23:44:09', NULL, NULL, NULL, 0.00, NULL, 'completed', NULL, '37.167.15.238', NULL, 1, 0, 'Pointage hors créneau autorisé (03:33:00-03:44:00)', NULL, '2025-11-26 22:43:52', '2025-11-26 22:44:09', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-26 22:43:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(72, 12, '2025-11-26 23:44:14', '2025-11-29 03:01:16', NULL, NULL, NULL, 0.00, 0.10, 'completed', NULL, '37.167.15.238', NULL, 1, 0, 'Pointage hors créneau autorisé (03:33:00-03:44:00)', NULL, '2025-11-26 22:44:14', '2025-11-29 02:01:16', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-26 22:44:14', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(73, 12, '2025-11-26 23:50:11', '2025-11-29 03:01:16', NULL, NULL, NULL, 0.00, 0.01, 'completed', NULL, '172.226.208.8', NULL, 1, 0, 'Pointage hors créneau autorisé (03:33:00-03:44:00)', NULL, '2025-11-26 22:50:11', '2025-11-29 02:01:16', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-26 22:50:11', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(74, 12, '2025-11-26 23:50:53', '2025-11-29 03:01:16', NULL, NULL, NULL, 0.00, NULL, 'completed', NULL, '172.226.208.1', NULL, 1, 0, 'Pointage hors créneau autorisé (03:33:00-03:44:00)', NULL, '2025-11-26 22:50:53', '2025-11-29 02:01:16', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-26 22:50:53', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(75, 6, '2025-11-26 22:54:39', '2025-11-29 03:01:10', NULL, NULL, NULL, 0.00, 0.48, 'completed', NULL, '37.167.15.238', NULL, 1, 0, 'Aucun créneau horaire défini pour cet utilisateur', NULL, '2025-11-26 22:54:39', '2025-11-29 02:01:10', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-26 22:54:39', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(76, 6, '2025-11-26 23:23:29', '2025-11-29 03:01:10', NULL, NULL, NULL, 0.00, 24.81, 'completed', NULL, '37.167.15.238', NULL, 1, 0, 'Aucun créneau horaire défini pour cet utilisateur', NULL, '2025-11-26 23:23:29', '2025-11-29 02:01:10', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-26 23:23:29', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(77, 6, '2025-11-28 00:12:04', '2025-11-29 03:01:10', NULL, NULL, NULL, 0.00, 0.00, 'completed', NULL, '84.98.112.56', NULL, 1, 0, 'Aucun créneau horaire défini pour cet utilisateur', NULL, '2025-11-28 00:12:04', '2025-11-29 02:01:10', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-28 00:12:04', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(78, 6, '2025-11-29 00:03:55', '2025-11-29 03:01:10', NULL, NULL, NULL, 0.00, 1.93, 'completed', NULL, '84.98.112.56', NULL, 1, 0, 'Aucun créneau horaire défini pour cet utilisateur', NULL, '2025-11-29 00:03:55', '2025-11-29 02:01:10', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-29 00:03:55', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(79, 6, '2025-11-29 02:00:44', '2025-11-29 03:01:10', NULL, NULL, NULL, 0.00, NULL, 'completed', NULL, '84.98.112.56', NULL, 1, 0, 'Aucun créneau horaire défini pour cet utilisateur', NULL, '2025-11-29 02:00:44', '2025-11-29 02:01:10', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-29 02:00:44', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(80, 6, '2025-11-29 13:48:31', '2025-12-15 23:47:09', NULL, NULL, NULL, 0.00, 12.50, 'completed', NULL, '82.65.103.221', NULL, 1, 0, 'Aucun créneau horaire défini pour cet utilisateur', NULL, '2025-11-29 13:48:31', '2025-12-15 22:47:09', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-29 13:48:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(81, 6, '2025-11-30 02:19:09', '2025-12-15 23:47:09', NULL, NULL, NULL, 0.00, 22.08, 'completed', NULL, '84.98.112.56', NULL, 1, 0, 'Aucun créneau horaire défini pour cet utilisateur', NULL, '2025-11-30 02:19:09', '2025-12-15 22:47:09', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-30 02:19:09', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(82, 6, '2025-12-04 11:01:30', '2025-12-15 23:47:09', NULL, NULL, NULL, 0.00, 83.07, 'completed', NULL, '92.184.112.199', NULL, 1, 0, 'Aucun créneau horaire défini pour cet utilisateur', NULL, '2025-12-04 11:01:30', '2025-12-15 22:47:09', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-12-04 11:01:30', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(83, 6, '2025-12-10 19:19:18', '2025-12-15 23:47:09', NULL, NULL, NULL, 0.00, 0.03, 'completed', NULL, '37.169.40.193', NULL, 1, 0, 'Aucun créneau horaire défini pour cet utilisateur', NULL, '2025-12-10 19:19:18', '2025-12-15 22:47:09', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-12-10 19:19:18', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(84, 6, '2025-12-10 19:21:06', '2025-12-15 23:47:09', NULL, NULL, NULL, 0.00, 0.05, 'completed', NULL, '37.169.40.193', NULL, 1, 0, 'Aucun créneau horaire défini pour cet utilisateur', NULL, '2025-12-10 19:21:06', '2025-12-15 22:47:09', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-12-10 19:21:06', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(85, 6, '2025-12-10 19:23:59', '2025-12-15 23:47:09', NULL, NULL, NULL, 0.00, 0.26, 'completed', NULL, '37.169.40.193', NULL, 1, 0, 'Aucun créneau horaire défini pour cet utilisateur', NULL, '2025-12-10 19:23:59', '2025-12-15 22:47:09', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-12-10 19:23:59', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(86, 6, '2025-12-10 19:39:29', '2025-12-15 23:47:09', NULL, NULL, NULL, 0.00, NULL, 'completed', NULL, '37.169.40.193', NULL, 1, 0, 'Aucun créneau horaire défini pour cet utilisateur', NULL, '2025-12-10 19:39:29', '2025-12-15 22:47:09', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-12-10 19:39:29', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(91, 7, '2025-12-12 09:45:02', '2025-12-15 23:47:16', NULL, NULL, NULL, 0.00, NULL, 'completed', NULL, '109.210.27.45', NULL, 1, 0, 'Aucun créneau horaire défini pour cet utilisateur', NULL, '2025-12-12 09:45:02', '2025-12-15 22:47:16', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-12-12 09:45:02', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(92, 3, '2025-12-12 09:46:13', '2025-12-15 23:47:19', NULL, NULL, NULL, 0.00, NULL, 'completed', NULL, '172.226.208.16', NULL, 1, 0, 'Aucun créneau horaire défini pour cet utilisateur', NULL, '2025-12-12 09:46:13', '2025-12-15 22:47:19', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-12-12 09:46:13', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(93, 1, '2025-12-13 23:02:47', '2025-12-15 23:48:23', NULL, NULL, NULL, 0.00, NULL, 'completed', NULL, '84.98.112.56', NULL, 1, 0, 'Aucun créneau horaire défini pour cet utilisateur', NULL, '2025-12-13 23:02:47', '2025-12-15 22:48:23', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-12-13 23:02:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0);

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
  `type` enum('AVANCE','REMBOURSEMENT','SERVICE') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `montant` decimal(10,2) NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `date_transaction` datetime DEFAULT CURRENT_TIMESTAMP,
  `reference_document` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `statut` enum('EN_ATTENTE','VALIDÉ','ANNULÉ') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'EN_ATTENTE'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  `username` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `full_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('admin','technicien') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `techbusy` int DEFAULT '0',
  `active_repair_id` int DEFAULT NULL,
  `is_online` tinyint(1) DEFAULT '0' COMMENT 'Statut: 0=Hors Ligne, 1=En Ligne',
  `cagnotte` decimal(10,2) DEFAULT '0.00',
  `points_experience` int DEFAULT '0',
  `score_total` int DEFAULT '0',
  `isActiveTask` tinyint(1) DEFAULT '0',
  `activetaskid` int DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `role`, `created_at`, `techbusy`, `active_repair_id`, `is_online`, `cagnotte`, `points_experience`, `score_total`, `isActiveTask`, `activetaskid`, `email`) VALUES
(1, 'admin', '$2y$10$Kni0S0stzwmulbUdz02kRO1MCO/VXPIpgBHkmE1INOMWnqZx6D1/W', 'Administrateur', 'admin', '2025-03-20 09:15:29', 0, NULL, 1, 0.00, 0, 0, 0, NULL, 'admin@maisondugeek.fr'),
(3, 'benjamin', '$2y$10$Kni0S0stzwmulbUdz02kRO1MCO/VXPIpgBHkmE1INOMWnqZx6D1/W', 'Benjamin', 'admin', '2025-03-20 09:15:29', 1, 1695, 1, 0.00, 0, 0, 0, NULL, 'benjamin@maisondugeek.fr'),
(6, 'Yassir', '$2y$10$Kni0S0stzwmulbUdz02kRO1MCO/VXPIpgBHkmE1INOMWnqZx6D1/W', 'Yassir', 'technicien', '2025-09-24 10:52:08', 1, 1650, 0, 0.00, 0, 0, 0, NULL, 'yassir@maisondugeek.fr'),
(7, 'Adam', '$2y$10$Kni0S0stzwmulbUdz02kRO1MCO/VXPIpgBHkmE1INOMWnqZx6D1/W', 'Adam', 'technicien', '2025-10-17 12:48:25', 1, 1715, 1, 0.00, 0, 0, 0, NULL, 'adam@maisondugeek.fr');

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
(2, 6, 2, 2, 'terminee', '2025-11-10 11:32:40', '2025-11-10 11:37:31', NULL, NULL),
(3, 6, 3, 1, 'en_cours', '2025-11-26 17:40:57', NULL, NULL, NULL),
(4, 1, 3, 0, 'en_cours', '2025-12-12 22:49:26', NULL, NULL, NULL);

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

--
-- Déchargement des données de la table `user_preferences`
--

INSERT INTO `user_preferences` (`id`, `user_id`, `preference_key`, `preference_value`, `updated_at`) VALUES
(1, 7, 'camera_device', '{\"deviceId\":\"FAEC6FBB7D3F62F1D15F0E59726069CB4E73602C\",\"label\":\"Caméra arrière\",\"facingMode\":\"\"}', '2025-12-12 15:17:10');

-- --------------------------------------------------------

--
-- Structure de la table `user_sessions`
--

CREATE TABLE `user_sessions` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `token` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiry` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
-- Structure de la vue `time_tracking_report`
--
DROP TABLE IF EXISTS `time_tracking_report`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `time_tracking_report`  AS SELECT `tt`.`id` AS `id`, `tt`.`user_id` AS `user_id`, `u`.`username` AS `username`, `u`.`full_name` AS `full_name`, `u`.`role` AS `role`, cast(`tt`.`clock_in` as date) AS `work_date`, `tt`.`clock_in` AS `clock_in`, `tt`.`clock_out` AS `clock_out`, `tt`.`break_start` AS `break_start`, `tt`.`break_end` AS `break_end`, `tt`.`total_hours` AS `total_hours`, `tt`.`break_duration` AS `break_duration`, `tt`.`work_duration` AS `work_duration`, `tt`.`status` AS `status`, `tt`.`location` AS `location`, `tt`.`notes` AS `notes`, `tt`.`admin_approved` AS `admin_approved`, `tt`.`admin_notes` AS `admin_notes`, (case when (`tt`.`total_hours` > 8) then (`tt`.`total_hours` - 8) else 0 end) AS `overtime_hours`, (case when ((`tt`.`status` = 'active') and (`tt`.`clock_in` < (now() - interval 12 hour))) then 'session_longue' when (`tt`.`status` = 'active') then 'en_cours' when (`tt`.`status` = 'break') then 'en_pause' else 'termine' end) AS `display_status` FROM (`time_tracking` `tt` join `users` `u` on((`tt`.`user_id` = `u`.`id`))) ORDER BY `tt`.`clock_in` DESC ;

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `ai_expert_profiles`
--
ALTER TABLE `ai_expert_profiles`
  ADD PRIMARY KEY (`id`);

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
-- Index pour la table `employee_notes`
--
ALTER TABLE `employee_notes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_employee` (`employee_id`),
  ADD KEY `idx_ai_analysis` (`employee_id`,`include_in_ai_analysis`),
  ADD KEY `idx_date` (`date_incident`),
  ADD KEY `idx_type_severity` (`note_type`,`severity`);

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
-- Index pour la table `kpi_ai_profiles`
--
ALTER TABLE `kpi_ai_profiles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_active` (`active`),
  ADD KEY `idx_default` (`is_default`);

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
  ADD KEY `idx_reparations_garantie_dates` (`date_garantie_debut`,`date_garantie_fin`),
  ADD KEY `idx_cree_par` (`cree_par`);

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
-- Index pour la table `shop_notes`
--
ALTER TABLE `shop_notes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_dates` (`date_start`,`date_end`),
  ADD KEY `idx_ai_analysis` (`include_in_ai_analysis`,`affects_kpi`),
  ADD KEY `idx_type` (`note_type`),
  ADD KEY `idx_impact` (`impact_level`);

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
-- Index pour la table `Task_logs`
--
ALTER TABLE `Task_logs`
  ADD PRIMARY KEY (`id`);

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
-- AUTO_INCREMENT pour la table `ai_expert_profiles`
--
ALTER TABLE `ai_expert_profiles`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `bug_reports`
--
ALTER TABLE `bug_reports`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

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
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

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
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `commentaires_tache`
--
ALTER TABLE `commentaires_tache`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

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
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `devis_acceptations`
--
ALTER TABLE `devis_acceptations`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `devis_logs`
--
ALTER TABLE `devis_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `devis_notifications`
--
ALTER TABLE `devis_notifications`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `devis_pannes`
--
ALTER TABLE `devis_pannes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `devis_solutions`
--
ALTER TABLE `devis_solutions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

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
-- AUTO_INCREMENT pour la table `employee_notes`
--
ALTER TABLE `employee_notes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

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
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT pour la table `garanties`
--
ALTER TABLE `garanties`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT pour la table `gardiennage`
--
ALTER TABLE `gardiennage`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

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
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `historique_soldes`
--
ALTER TABLE `historique_soldes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `journal_actions`
--
ALTER TABLE `journal_actions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `kb_articles`
--
ALTER TABLE `kb_articles`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

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
-- AUTO_INCREMENT pour la table `kpi_ai_profiles`
--
ALTER TABLE `kpi_ai_profiles`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

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
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

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
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `mission_types`
--
ALTER TABLE `mission_types`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT pour la table `mission_validations`
--
ALTER TABLE `mission_validations`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT pour la table `mouvements_stock`
--
ALTER TABLE `mouvements_stock`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

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
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `partner_transactions_pending`
--
ALTER TABLE `partner_transactions_pending`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `photos_reparation`
--
ALTER TABLE `photos_reparation`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `pieces_avancees`
--
ALTER TABLE `pieces_avancees`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `pieces_utilisees_reparations`
--
ALTER TABLE `pieces_utilisees_reparations`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

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
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

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
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT pour la table `push_subscriptions`
--
ALTER TABLE `push_subscriptions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `rachat_appareils`
--
ALTER TABLE `rachat_appareils`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

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
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `reparation_attributions`
--
ALTER TABLE `reparation_attributions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `reparation_logs`
--
ALTER TABLE `reparation_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `reparation_sms`
--
ALTER TABLE `reparation_sms`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

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
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `shop_notes`
--
ALTER TABLE `shop_notes`
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
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `sms_logs`
--
ALTER TABLE `sms_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

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
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `tache_attachments`
--
ALTER TABLE `tache_attachments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `Task_logs`
--
ALTER TABLE `Task_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `task_logs`
--
ALTER TABLE `task_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `time_slots`
--
ALTER TABLE `time_slots`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5958;

--
-- AUTO_INCREMENT pour la table `time_tracking`
--
ALTER TABLE `time_tracking`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=94;

--
-- AUTO_INCREMENT pour la table `time_tracking_settings`
--
ALTER TABLE `time_tracking_settings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT pour la table `transactions_partenaires`
--
ALTER TABLE `transactions_partenaires`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT pour la table `user_cagnotte`
--
ALTER TABLE `user_cagnotte`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `user_missions`
--
ALTER TABLE `user_missions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `user_preferences`
--
ALTER TABLE `user_preferences`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

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
-- Contraintes pour la table `employee_notes`
--
ALTER TABLE `employee_notes`
  ADD CONSTRAINT `employee_notes_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `employee_notes_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT;

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
-- Contraintes pour la table `kpi_ai_profiles`
--
ALTER TABLE `kpi_ai_profiles`
  ADD CONSTRAINT `kpi_ai_profiles_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

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
-- Contraintes pour la table `shop_notes`
--
ALTER TABLE `shop_notes`
  ADD CONSTRAINT `shop_notes_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT;

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
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
