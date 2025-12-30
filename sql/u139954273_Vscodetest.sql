-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Dec 11, 2025 at 11:51 PM
-- Server version: 11.8.3-MariaDB-log
-- PHP Version: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `u139954273_Vscodetest`
--

-- --------------------------------------------------------

--
-- Table structure for table `bug_reports`
--

CREATE TABLE `bug_reports` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `description` text NOT NULL,
  `page_url` varchar(255) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `priorite` enum('basse','moyenne','haute','critique') NOT NULL DEFAULT 'basse',
  `status` enum('nouveau','en_cours','resolu','ferme') DEFAULT 'nouveau',
  `date_creation` datetime DEFAULT current_timestamp(),
  `date_resolution` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `bug_reports`
--

INSERT INTO `bug_reports` (`id`, `user_id`, `description`, `page_url`, `user_agent`, `priorite`, `status`, `date_creation`, `date_resolution`) VALUES
(2, NULL, 'Quand je clique sur démarrer la réparation il faut que ça me redirige vers la page statut_rapide', NULL, NULL, 'basse', 'resolu', '2025-04-12 01:47:36', '2025-04-17 21:29:41'),
(3, NULL, 'Ça n’envoi pas de sms quand je glisse un dossier dans un nouveau statut', NULL, NULL, 'basse', 'resolu', '2025-04-12 01:50:04', '2025-04-17 21:29:36'),
(4, NULL, 'Il faudrait rajouter un module pour vérifier lorsque le prix est à 01 message de confirmation pour demander si on est sûre que le montant de la réparation est à zéro euro', NULL, NULL, 'basse', 'resolu', '2025-04-12 15:51:06', '2025-04-21 20:53:38'),
(5, NULL, 'J’ai un message me demandant de me connecter lorsque, j’appuie sur le bouton démarrer pour démarrer la tâche', NULL, NULL, 'basse', 'resolu', '2025-04-12 15:53:12', '2025-04-17 21:29:23'),
(6, NULL, 'Quand je clique sur Terminer, ca me redirige vers \nhttps://mdgeek.top/index.php?page=index.php%3Fpage%3Dreparations\net jai une erreur 404\n404\nPage non trouvée\n\nLa page que vous recherchez n&#039;existe pas ou a été déplacée.\n\n\n\n--\nje voudrais que ca me redirige vers \nhttps://mdgeek.top/index.php?page=reparations', NULL, NULL, 'basse', 'resolu', '2025-04-12 16:39:02', '2025-04-17 21:29:19'),
(8, 1, 'Au format mobile, quand j’ajoute une réparation, j’ai du mal à imprimer l’étiquette', 'https://mdgeek.top/index.php', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1', 'basse', 'resolu', '2025-04-15 11:46:37', '2025-04-17 21:29:04'),
(9, 1, 'Au format mobile, quand j’ajoute une réparation, j’ai du mal à imprimer l’étiquette', 'https://mdgeek.top/index.php', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1', 'basse', 'resolu', '2025-04-15 11:46:37', '2025-04-17 21:29:10'),
(14, NULL, 'afficher le nom de la personne qui a cree la reparation', 'https://mdgeek.top/index.php?page=reparations', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', 'basse', 'resolu', '2025-04-16 23:02:14', '2025-04-21 20:53:36'),
(15, NULL, 'Je voudrais que dans le champ éteins les réparations, tu affiche aussi les réparations en cours et les réparations en attente\r\n\r\nEt je voudrais que à côté du mot réparations récentes, tu affiche le numéro correspondant au nombre de réparations dans la catégorie nouvelles', 'https://mdgeek.top/index.php', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', 'basse', 'resolu', '2025-04-16 23:16:02', '2025-04-17 00:10:47'),
(16, NULL, 'Quand je veux ajouter un client depuis la page Rachat appareil, j’ai un message qui m’empêche d’enregistrer le client', 'https://mdgeek.top/index.php?page=rachat_appareils', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.3.1 Safari/605.1.15', 'basse', 'resolu', '2025-04-17 13:14:28', '2025-04-21 20:53:32'),
(17, NULL, 'Le SMS qui est envoyé lorsque la réparation est effectuée.\r\nIl faudrait remplacer le mot informatique par appareil', 'https://mdgeek.top/index.php?page=reparations', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1', 'basse', 'resolu', '2025-04-17 14:56:46', '2025-04-21 20:53:30'),
(18, NULL, 'Quand je glisse une reparation dans un statut, ca n;envoi pas de sms', 'https://mdgeek.top/index.php?page=reparations', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', 'basse', 'resolu', '2025-04-18 00:05:28', '2025-04-21 20:53:31'),
(19, NULL, 'Verifier pourquoi le module d\'accepation de devis ne fonctionne pas', 'https://mdgeek.top/index.php', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', 'basse', 'resolu', '2025-04-18 00:09:04', '2025-04-21 20:52:56'),
(20, NULL, 'Ca nenvoi pas de sms lors du glisser deposer', 'https://mdgeek.top/index.php?page=reparations', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', 'basse', 'nouveau', '2025-04-18 00:41:35', NULL),
(21, 1, 'ajouter des emoticon au sms', 'https://mdgeek.top/index.php', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', 'basse', 'resolu', '2025-04-18 01:21:33', '2025-04-21 20:53:12'),
(22, NULL, 'Si Rayan commence une réparation, je ne peux pas en commencer une autre.', 'https://mdgeek.top/index.php?page=reparations&view=cards', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1', 'basse', 'resolu', '2025-04-19 09:47:59', '2025-04-21 20:53:16'),
(24, NULL, 'Lorsque je veux démarrer une nouvelle réparation, si j’avais une réparation en cours avec marqué en attente d’un responsable, je ne peux pas en démarrer une nouvelle cela, crée un bug', 'https://mdgeek.top/index.php?page=taches', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1', 'basse', 'resolu', '2025-04-19 10:03:12', '2025-04-21 20:54:51'),
(26, NULL, 'Il faut décaler le contenu du modèle, ajouter une pièce de 60 PX au format Mobile', 'https://mdgeek.top/index.php?page=inventaire', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1', 'basse', 'resolu', '2025-04-21 02:02:04', '2025-04-21 20:56:09'),
(28, NULL, 'Ajouter une colonne fournisseur ainsi qu’un bouton Google', 'https://mdgeek.top/index.php?page=inventaire', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1', 'basse', 'nouveau', '2025-04-21 02:06:48', NULL),
(30, NULL, 'Le bouton de filtre dans le tableau ne fonctionne pas, je ne peux pas filtrer par ce statut', 'https://mdgeek.top/index.php?page=inventaire', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1', 'basse', 'nouveau', '2025-04-21 02:07:27', NULL),
(32, NULL, 'Le bouton exporter ne fonctionne pas', 'https://mdgeek.top/index.php?page=inventaire', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1', 'basse', 'nouveau', '2025-04-21 02:07:47', NULL),
(34, NULL, 'Vérifier pourquoi les notifications ne marche pas', 'https://mdgeek.top/index.php?page=ajouter_reparation', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1', 'basse', 'nouveau', '2025-04-21 17:52:06', NULL),
(36, 1, 'Ajouter un champs pour assigner à un employée ( technicien )', 'https://mdgeek.top/index.php?page=ajouter_reparation', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1', 'basse', 'nouveau', '2025-04-21 18:17:09', NULL),
(38, 1, 'Le bouton ajouter une photo ne fonctionne pas il me redirigé au lieu d’afficher un modal pour prendre une photo', 'https://mdgeek.top/index.php?page=statut_rapide&id=635', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1', 'basse', 'resolu', '2025-04-21 18:18:04', '2025-04-21 21:07:06'),
(39, 1, 'Je veut que tu decalle de 60px vers le bas au format mobile uniquement\r\nLe modal nouvelle commande de piece', 'https://mdgeek.top/index.php?page=statut_rapide&id=631', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1', 'basse', 'nouveau', '2025-04-21 18:18:52', NULL),
(41, 1, 'Dans le modal envoyer un devis ajoute l’option pour détailler le sms', 'https://mdgeek.top/index.php?page=statut_rapide&id=631', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1', 'basse', 'nouveau', '2025-04-21 18:19:31', NULL),
(43, 1, 'Le bouton statut n’affiche pas le modal pour changer le statut', 'https://mdgeek.top/index.php?page=statut_rapide&id=631', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1', 'basse', 'nouveau', '2025-04-21 18:20:02', NULL),
(45, 1, 'Quand j’appuie pour envoyer un devis, j’ai un message qui dit erreur de communication avec le serveur alors que le sms d’envoi correctement ( je reçoit bien le sms lors de mon test )', 'https://mdgeek.top/index.php?page=statut_rapide&id=631', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1', 'basse', 'nouveau', '2025-04-21 18:21:05', NULL),
(47, 1, 'Dans le sms du devis il ne prend pas en charge la valeur des variables', 'https://mdgeek.top/index.php?page=statut_rapide&id=631', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1', 'basse', 'nouveau', '2025-04-21 18:22:11', NULL),
(48, 1, 'Le sms du prix ne mentionne pas le prix', 'https://mdgeek.top/index.php?page=statut_rapide&id=631', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1', 'basse', 'nouveau', '2025-04-21 18:22:37', NULL),
(49, 1, 'Le bouton restituée fonctionne mais me redirigé vers une page qui me donne une erreuf', 'https://mdgeek.top/index.php?page=statut_rapide&id=631', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1', 'basse', 'nouveau', '2025-04-21 18:23:10', NULL),
(51, 1, 'Le bouton envoyer un sms ne fonctionne pas', 'https://mdgeek.top/index.php?page=statut_rapide&id=631', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1', 'basse', 'resolu', '2025-04-21 18:23:30', '2025-04-21 21:42:39'),
(52, 1, 'Problème de couleur modal confirmation du gardiennage en mode nuit', 'https://mdgeek.top/index.php?page=statut_rapide&id=631', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1', 'basse', 'nouveau', '2025-04-21 18:24:14', NULL),
(55, 1, 'Problème de couleur mode nuit modal devis', 'https://mdgeek.top/index.php?page=statut_rapide&id=631', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1', 'basse', 'nouveau', '2025-04-21 18:24:39', NULL),
(57, 1, 'Après avoir terminer la réparation Quand je sélectionne une réparation en statut \r\nEn attente d’un responsable.\r\nÇa ne desassigne pas la réparation au technicien', 'https://mdgeek.top/index.php?page=statut_rapide&id=632', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1', 'basse', 'resolu', '2025-04-21 18:26:14', '2025-04-22 00:45:26'),
(59, 1, 'Dans le modal prix quand j’appuie sur le clavier ça me compte les chiffre en double, si je veut écrit 321 ca va m’écrit 332211', 'https://mdgeek.top/index.php?page=statut_rapide&id=632', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1', 'basse', 'nouveau', '2025-04-21 18:26:59', NULL),
(60, 1, 'Je veut que tu retire le statut qui est tout en haut à droite ( à côté du bouton retour )', 'https://mdgeek.top/index.php?page=statut_rapide&id=632', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1', 'basse', 'nouveau', '2025-04-21 18:27:59', NULL),
(62, 1, 'Problème de couleur mode nuit - dans le tableau je veut que les prix soit affichée en noir', 'https://mdgeek.top/index.php?page=commandes_pieces', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1', 'basse', 'resolu', '2025-04-21 18:28:57', '2025-04-21 22:43:10'),
(65, 1, 'Problème de couleur mode nuit - je veut que la date soit affichée en noir en mode nuit', 'https://mdgeek.top/index.php?page=commandes_pieces', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1', 'basse', 'resolu', '2025-04-21 18:29:23', '2025-04-21 22:43:09'),
(72, 1, 'Je veut que le tableau au format mobile fasse la largeur de l’écran, et je veut pouvoir slider de gauche à droite pour visualiser le tableau sans qu’il ne dépasse de l’écran', 'https://mdgeek.top/index.php?page=commandes_pieces', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1', 'basse', 'resolu', '2025-04-21 18:30:32', '2025-04-21 22:16:39'),
(77, 1, 'Quand je veut éditer la commande j’ai - modale d’édition non trouvée - bouton editer dans la collone action', 'https://mdgeek.top/index.php?page=commandes_pieces', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1', 'basse', 'resolu', '2025-04-21 18:31:21', '2025-04-21 22:42:23'),
(78, 1, 'Quand j’appuie dans le tableau sur une tache ça ne m’affiche plus le modal avec les details', 'https://mdgeek.top/index.php', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1', 'basse', 'nouveau', '2025-04-21 18:32:07', NULL),
(80, 1, 'Je veut que tu inverse la position de la colline statut avec la collone fournisseur', 'https://mdgeek.top/index.php?page=commandes_pieces', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1', 'basse', 'resolu', '2025-04-21 18:33:11', '2025-04-21 22:05:26'),
(83, 1, 'Le bouton exporter ne fonctionne pas', 'https://mdgeek.top/index.php?page=commandes_pieces', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1', 'basse', 'resolu', '2025-04-21 18:33:31', '2025-04-21 22:42:19'),
(86, 1, 'Problème de couleur mode nuit modal ajustement de stock', 'https://mdgeek.top/index.php?page=inventaire', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1', 'basse', 'nouveau', '2025-04-21 18:34:08', NULL),
(89, 1, 'Ajouter une rubrique pour des produit temporaire\r\nCes produit seront destinée aux articles que nous avons commander pour les client mais que ils ne sont pas venu chercher. \r\nJe veut que quand le produit est scanner pour être enregistrer dans notre stock tu demande si c’est un produit succeptible d’être retournée.\r\nces produit seront enregistrée en produit temporaire.\r\nAu bout de 12 jours,il faudra que je retourne le produit, je veut que le produit apparaisse différemment dans le tableau ( pour qu’on le remarque couleur - badge À retourner)\r\n\r\nJe veut que tu adapte la table dsl pour cette fonction et que tu me fasse une page pour gérer les retour de produit\r\nCette page me permettra de voir quel produit je doit retourné et d’ajouter aussi de nouveau produit a retourné.\r\nJe veut pouvoir indiquée quand les produit ont été expédiée ( ajouter le numéro de suivi ) ( vérifier le statut de la livraison ) ( une fois le colis livrée je veut que le le retour passe en retour à vérifier, c’est à dire que à ce moment je vais devoir comparer le montant rembourser par mon fournisseur et le montant des produit que j’ai rembourser pour vérifier si tout c’est bien passer )\r\n\r\nJe veut que dans la pêche de retour je puisse sélectionner les produit a retourner, pour qu’ils soit enregistrée dans un colis\r\n\r\nJe veut une interface pour gérer les colis ( voir le contenu / suivi / etc )', 'https://mdgeek.top/index.php?page=inventaire', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1', 'basse', 'nouveau', '2025-04-21 18:42:00', NULL),
(90, 1, 'Dans la page réparation quand je suis sur le modal de détail d’une réparation et que je clique sur le bouton pour modifier le statut, le modal s’affiche mais derrière le modal de détails. Je suis obliger de fermer le modal de détails de réparation pour voir le modal de modification de statut', 'https://mdgeek.top/index.php?page=taches', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1', 'basse', 'nouveau', '2025-04-21 18:44:06', NULL),
(91, NULL, 'Trouver un moyen pour assigner une réparation à un employée pour que ça apparaisse dans ses tâches à faires.\r\nIl faudra qu’il demmare la réparation depuis les tâches, puis une fois la réparation terminée il faudra vérifier si la réparation est assignée en tâches à l’employée, si c’est le cas il va falloir marquée la tache comme terminée.', 'https://mdgeek.top/index.php?page=taches', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1', 'basse', 'nouveau', '2025-04-22 01:06:15', NULL),
(92, NULL, 'Trouver un moyen pour assigner une réparation à un employée pour que ça apparaisse dans ses tâches à faires.\r\nIl faudra qu’il demmare la réparation depuis les tâches, puis une fois la réparation terminée il faudra vérifier si la réparation est assignée en tâches à l’employée, si c’est le cas il va falloir marquée la tache comme terminée.', 'https://mdgeek.top/index.php?page=taches', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1', 'basse', 'nouveau', '2025-04-22 01:06:15', NULL),
(93, 1, 'asd', 'https://mdgeek.top/index.php', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', 'basse', 'resolu', '2025-04-22 01:06:52', '2025-04-30 22:36:55'),
(94, 1, 'Quand je soumet un but ça le soumet 2 fois.\r\nQuand je soumet une commande ça la soumet 2 fois\r\nAu format mobile', 'https://mdgeek.top/index.php', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1', 'basse', 'resolu', '2025-04-22 08:59:12', '2025-04-30 22:36:53'),
(95, NULL, 'Dans la page commande, je veut voir le technicien qui a bosser sur la reparation', 'https://mdgeek.top/index.php', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', 'basse', 'nouveau', '2025-04-22 09:46:20', NULL),
(96, NULL, 'Quand je scanne le billet, et que j’appuie sur terminer la réparation.\r\nSi je choisis le statut restitué, ça ne change pas le statut de la réparation', 'https://mdgeek.top/index.php?page=statut_rapide&id=637', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', 'basse', 'nouveau', '2025-04-22 10:18:01', NULL),
(97, NULL, 'Dans la page statut rapide\r\nQuand j’imprime l’étiquette\r\n\r\nJe veut que tu m’affiche le texte en plus gros de 2px', 'https://mdgeek.top/index.php', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1', 'basse', 'resolu', '2025-04-22 17:16:38', '2025-04-30 22:36:40'),
(98, NULL, 'Dans la page statut rapide\r\nQuand j’imprime l’étiquette\r\n\r\nJe veut que tu m’affiche le texte en plus gros de 2px', 'https://mdgeek.top/index.php', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1', 'basse', 'resolu', '2025-04-22 17:16:38', '2025-04-30 22:36:41'),
(99, 1, 'Désactiver le slide verre en haut pour actualiser la page', 'https://mdgeek.top/index.php?pwa=1&timestamp=1745512223331', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1', 'basse', 'resolu', '2025-04-24 16:30:47', '2025-04-30 22:36:36'),
(100, 1, 'Désactiver le slide verre en haut pour actualiser la page', 'https://mdgeek.top/index.php?pwa=1&timestamp=1745512223331', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1', 'basse', 'resolu', '2025-04-24 16:30:47', '2025-04-30 22:36:36'),
(101, 1, 'Je voudrais que le tableau réparation recent Africa que les nouvelles reparation', 'https://mdgeek.top/index.php', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1', 'basse', 'resolu', '2025-04-26 18:53:48', '2025-04-30 22:36:34'),
(102, 1, 'Je voudrais que le tableau réparation recent Africa que les nouvelles reparation', 'https://mdgeek.top/index.php', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1', 'basse', 'resolu', '2025-04-26 18:53:48', '2025-04-30 22:36:34'),
(103, 1, 'Je veux désactiver le slide vers le haut pour rafraîchir quand on est sur PWA', 'https://mdgeek.top/index.php', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1', 'basse', 'resolu', '2025-04-26 18:54:27', '2025-04-30 22:36:27'),
(104, 1, 'Je veux désactiver le slide vers le haut pour rafraîchir quand on est sur PWA', 'https://mdgeek.top/index.php', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1', 'basse', 'resolu', '2025-04-26 18:54:27', '2025-04-30 22:36:28'),
(105, 1, 'Je veut retirer le bouton urgence', 'https://mdgeek.top/index.php?pwa=1&timestamp=1745693801502', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1', 'basse', 'resolu', '2025-04-26 18:57:09', '2025-04-30 22:36:21'),
(106, 1, 'Je veut retirer le bouton urgence', 'https://mdgeek.top/index.php?pwa=1&timestamp=1745693801502', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1', 'basse', 'resolu', '2025-04-26 18:57:09', '2025-04-30 22:36:22'),
(107, 1, 'Quand je clique sur le statut d’une commande, il ne se passe rien.\r\nNormalement ça doit m’ouvrir un modèle', 'https://mdgeek.top/index.php?pwa=1&timestamp=1745693801502', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1', 'basse', 'resolu', '2025-04-26 18:57:40', '2025-04-30 22:36:18'),
(108, 1, 'Quand je clique sur le statut d’une commande, il ne se passe rien.\r\nNormalement ça doit m’ouvrir un modèle', 'https://mdgeek.top/index.php?pwa=1&timestamp=1745693801502', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1', 'basse', 'resolu', '2025-04-26 18:57:40', '2025-04-30 22:36:20'),
(109, 1, 'Quand je soumets un bug sur iOS, ça me soumettre deux fois', 'https://mdgeek.top/index.php?pwa=1&timestamp=1745693801502', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1', 'basse', 'resolu', '2025-04-26 18:57:56', '2025-04-30 22:36:11'),
(110, 1, 'Quand je soumets un bug sur iOS, ça me soumettre deux fois', 'https://mdgeek.top/index.php?pwa=1&timestamp=1745693801502', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1', 'basse', 'resolu', '2025-04-26 18:57:56', '2025-04-30 22:36:12'),
(111, 1, 'Quand je soumets une commande ça la soumettre deux fois', 'https://mdgeek.top/index.php?pwa=1&timestamp=1745693801502', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1', 'basse', 'resolu', '2025-04-26 18:58:44', '2025-04-30 22:36:10'),
(112, NULL, 'test', 'https://mdgeek.top/index.php', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', 'basse', 'resolu', '2025-04-30 22:35:58', '2025-04-30 22:36:09');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `clients`
--

CREATE TABLE `clients` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `telephone` varchar(20) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT current_timestamp(),
  `inscrit_parrainage` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Client inscrit au programme de parrainage ou non',
  `code_parrainage` varchar(10) DEFAULT NULL COMMENT 'Code unique pour le parrainage (peut être null si pas inscrit)',
  `date_inscription_parrainage` timestamp NULL DEFAULT NULL COMMENT 'Date d''inscription au programme de parrainage'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `clients`
--

INSERT INTO `clients` (`id`, `nom`, `prenom`, `telephone`, `email`, `date_creation`, `inscrit_parrainage`, `code_parrainage`, `date_inscription_parrainage`) VALUES
(505, 'database_general_sample_info', 'database_general_sample_info', '33782962906', '', '2025-05-03 22:22:31', 0, NULL, NULL),
(506, 'database_general_sample_info', 'database_general_sample_info', '0782962906', '', '2025-05-04 19:42:58', 0, NULL, NULL),
(507, 'database_general_sample_info', 'database_general_sample_info', '77829546545', '', '2025-05-04 19:55:07', 0, NULL, NULL),
(508, 'database_general_sample_info', 'database_general_sample_info', '782962854', '', '2025-05-04 19:56:59', 0, NULL, NULL),
(509, 'database_general_sample_info', 'database_general_sample_info', '78296295455', '', '2025-05-04 20:00:24', 0, NULL, NULL),
(510, 'database_general_sample_info', 'database_general_sample_info', '789456', '', '2025-05-04 20:08:25', 0, NULL, NULL),
(511, 'database_general_sample_info', 'database_general_sample_info', '77854545', '', '2025-05-04 21:19:32', 0, NULL, NULL),
(512, 'database_general_sample_info', 'database_general_sample_info', '337829626906', '', '2025-05-04 21:26:56', 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `colis_retour`
--

CREATE TABLE `colis_retour` (
  `id` int(11) NOT NULL,
  `numero_suivi` varchar(100) NOT NULL,
  `date_creation` datetime DEFAULT current_timestamp(),
  `date_expedition` datetime DEFAULT NULL,
  `statut` enum('en_preparation','en_expedition','livre') DEFAULT 'en_preparation',
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `commandes_fournisseurs`
--

CREATE TABLE `commandes_fournisseurs` (
  `id` int(11) NOT NULL,
  `fournisseur_id` int(11) NOT NULL,
  `date_commande` timestamp NULL DEFAULT current_timestamp(),
  `statut` enum('en_attente','validee','recue','annulee') DEFAULT 'en_attente',
  `montant_total` decimal(10,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `commandes_pieces`
--

CREATE TABLE `commandes_pieces` (
  `id` int(11) NOT NULL,
  `reference` varchar(50) NOT NULL,
  `reparation_id` int(11) DEFAULT NULL,
  `client_id` int(11) DEFAULT NULL,
  `fournisseur_id` int(11) NOT NULL,
  `nom_piece` varchar(255) NOT NULL,
  `code_barre` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `quantite` int(11) NOT NULL DEFAULT 1,
  `prix_estime` decimal(10,2) DEFAULT NULL,
  `commentaire_interne` text DEFAULT NULL,
  `note_interne` text DEFAULT NULL,
  `urgence` enum('normal','urgent','tres_urgent') DEFAULT 'normal',
  `statut` enum('en_attente','commande','recue','annulee','urgent','termine','utilise','a_retourner') NOT NULL DEFAULT 'en_attente',
  `date_commande` datetime DEFAULT NULL,
  `date_reception` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `date_creation` datetime DEFAULT current_timestamp(),
  `date_modification` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `commandes_pieces`
--

INSERT INTO `commandes_pieces` (`id`, `reference`, `reparation_id`, `client_id`, `fournisseur_id`, `nom_piece`, `code_barre`, `description`, `quantite`, `prix_estime`, `commentaire_interne`, `note_interne`, `urgence`, `statut`, `date_commande`, `date_reception`, `notes`, `date_creation`, `date_modification`) VALUES
(182, 'database_general_sample_info', NULL, 505, 9, 'asds', 'sda', NULL, 1, 2.00, NULL, NULL, 'normal', 'commande', NULL, NULL, NULL, '2025-05-07 22:33:53', '2025-06-15 13:45:26'),
(183, 'CMD-20250518-682a652ab7cad', NULL, 505, 9, 'asd', 'sad', NULL, 1, 2.00, NULL, NULL, 'normal', 'commande', NULL, NULL, NULL, '2025-05-18 22:54:34', '2025-06-15 13:45:14');

-- --------------------------------------------------------

--
-- Table structure for table `commentaires_tache`
--

CREATE TABLE `commentaires_tache` (
  `id` int(11) NOT NULL,
  `tache_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `commentaire` text NOT NULL,
  `date_creation` timestamp NULL DEFAULT current_timestamp(),
  `is_system` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `confirmations_lecture`
--

CREATE TABLE `confirmations_lecture` (
  `id` int(11) NOT NULL,
  `message_id` int(11) NOT NULL,
  `employe_id` int(11) NOT NULL,
  `date_confirmation` datetime DEFAULT NULL COMMENT 'NULL = non confirmé, datetime = confirmé à cette date',
  `rappel_envoye` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Indique si un rappel a été envoyé',
  `date_rappel` datetime DEFAULT NULL COMMENT 'Date et heure d''envoi du rappel'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `conges_demandes`
--

CREATE TABLE `conges_demandes` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `date_debut` date NOT NULL,
  `date_fin` date NOT NULL,
  `nb_jours` decimal(5,2) NOT NULL,
  `statut` enum('en_attente','approuve','refuse') DEFAULT 'en_attente',
  `type` enum('normal','impose') DEFAULT 'normal',
  `commentaire` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `conges_jours_disponibles`
--

CREATE TABLE `conges_jours_disponibles` (
  `id` int(11) NOT NULL,
  `date` date NOT NULL,
  `disponible` tinyint(1) DEFAULT 1,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `conges_solde`
--

CREATE TABLE `conges_solde` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `solde_actuel` decimal(5,2) NOT NULL DEFAULT 0.00,
  `date_derniere_maj` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `conversations`
--

CREATE TABLE `conversations` (
  `id` int(11) NOT NULL,
  `titre` varchar(255) NOT NULL,
  `type` enum('direct','groupe','annonce') NOT NULL DEFAULT 'direct',
  `created_by` int(11) DEFAULT NULL,
  `date_creation` datetime DEFAULT current_timestamp(),
  `derniere_activite` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `conversation_participants`
--

CREATE TABLE `conversation_participants` (
  `conversation_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role` enum('admin','membre','lecteur') NOT NULL DEFAULT 'membre',
  `date_ajout` datetime DEFAULT current_timestamp(),
  `date_derniere_lecture` datetime DEFAULT NULL,
  `est_favoris` tinyint(1) DEFAULT 0,
  `est_archive` tinyint(1) DEFAULT 0,
  `notification_mute` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employes`
--

CREATE TABLE `employes` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `date_embauche` date DEFAULT NULL,
  `statut` enum('actif','inactif') DEFAULT 'actif',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fournisseurs`
--

CREATE TABLE `fournisseurs` (
  `id` int(11) NOT NULL,
  `nom` varchar(255) NOT NULL,
  `contact_nom` varchar(100) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `url` varchar(20) DEFAULT NULL,
  `adresse` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `fournisseurs`
--

INSERT INTO `fournisseurs` (`id`, `nom`, `contact_nom`, `email`, `url`, `adresse`, `created_at`) VALUES
(2, 'database_general_sample_info', NULL, NULL, 'https://mdgeek.top/i', NULL, '2025-03-28 18:58:21'),
(4, 'database_general_sample_info', NULL, NULL, 'https://www.wattiz.f', NULL, '2025-03-29 00:40:20'),
(9, 'database_general_sample_info', NULL, NULL, 'http://Aliexpress.fr', NULL, '2025-03-29 00:41:01'),
(10, 'database_general_sample_info', NULL, NULL, 'http://amazon.fr', NULL, '2025-03-29 00:41:15'),
(11, 'database_general_sample_info', NULL, NULL, 'http://mobilax.fr', NULL, '2025-03-29 00:41:28'),
(12, 'database_general_sample_info', NULL, NULL, 'http://volt-corp.com', NULL, '2025-03-29 00:41:45'),
(14, 'database_general_sample_info', NULL, NULL, 'https://autre.com', NULL, '2025-04-02 16:27:46'),
(15, 'database_general_sample_info', NULL, NULL, 'http://E-Wheel.fr', NULL, '2025-04-02 16:29:49');

-- --------------------------------------------------------

--
-- Table structure for table `gardiennage`
--

CREATE TABLE `gardiennage` (
  `id` int(11) NOT NULL,
  `reparation_id` int(11) NOT NULL,
  `date_debut` date NOT NULL,
  `date_derniere_facturation` date NOT NULL,
  `tarif_journalier` decimal(10,2) NOT NULL DEFAULT 5.00,
  `jours_factures` int(11) NOT NULL DEFAULT 0,
  `montant_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `est_actif` tinyint(1) NOT NULL DEFAULT 1,
  `date_fin` date DEFAULT NULL,
  `derniere_notification` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `gardiennage`
--

INSERT INTO `gardiennage` (`id`, `reparation_id`, `date_debut`, `date_derniere_facturation`, `tarif_journalier`, `jours_factures`, `montant_total`, `est_actif`, `date_fin`, `derniere_notification`, `notes`, `created_at`, `updated_at`) VALUES
(15, 593, '2025-04-23', '2025-04-23', 5.00, 3, 15.00, 0, '2025-04-26', NULL, '', '2025-04-23 12:33:46', '2025-04-26 18:56:10'),
(16, 593, '2025-04-23', '2025-04-23', 5.00, 3, 15.00, 0, '2025-04-26', NULL, '', '2025-04-23 12:33:46', '2025-04-26 18:55:54'),
(17, 618, '2025-04-23', '2025-04-23', 5.00, 3, 15.00, 0, '2025-04-26', NULL, '', '2025-04-23 12:35:27', '2025-04-26 18:56:03'),
(18, 594, '2025-04-26', '2025-04-26', 5.00, 0, 0.00, 0, '2025-04-26', NULL, '', '2025-04-26 10:15:04', '2025-04-26 18:55:39'),
(19, 594, '2025-04-26', '2025-04-26', 5.00, 0, 0.00, 0, '2025-04-26', NULL, '', '2025-04-26 10:15:05', '2025-04-26 18:55:45'),
(20, 675, '2025-04-28', '2025-04-28', 5.00, 0, 0.00, 1, NULL, NULL, '', '2025-04-28 22:40:13', '2025-04-28 22:40:13');

-- --------------------------------------------------------

--
-- Table structure for table `gardiennage_notifications`
--

CREATE TABLE `gardiennage_notifications` (
  `id` int(11) NOT NULL,
  `gardiennage_id` int(11) NOT NULL,
  `date_notification` timestamp NULL DEFAULT current_timestamp(),
  `type_notification` enum('sms','email','appel') NOT NULL,
  `statut` enum('envoyé','échec','annulé') NOT NULL,
  `message` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `global_theme_settings`
--

CREATE TABLE `global_theme_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `global_theme_settings`
--

INSERT INTO `global_theme_settings` (`id`, `setting_key`, `setting_value`, `description`, `created_at`, `updated_at`) VALUES
(1, 'allow_user_themes', '1', 'Permettre aux utilisateurs de changer de thème', '2025-06-16 23:49:17', '2025-06-16 23:49:17'),
(2, 'default_theme_id', '4', 'ID du thème par défaut (Classique)', '2025-06-16 23:49:17', '2025-06-16 23:49:17'),
(3, 'enable_auto_dark_mode', '1', 'Activer le mode sombre automatique selon l\'heure', '2025-06-16 23:49:17', '2025-06-16 23:49:17');

-- --------------------------------------------------------

--
-- Table structure for table `help_requests`
--

CREATE TABLE `help_requests` (
  `id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `status` enum('en_attente','resolu','en_cours') DEFAULT 'en_attente',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `resolved_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `historique_soldes`
--

CREATE TABLE `historique_soldes` (
  `id` int(11) NOT NULL,
  `partenaire_id` int(11) NOT NULL,
  `ancien_solde` decimal(10,2) DEFAULT NULL,
  `nouveau_solde` decimal(10,2) DEFAULT NULL,
  `transaction_id` int(11) DEFAULT NULL,
  `date_modification` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `journal_actions`
--

CREATE TABLE `journal_actions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action_type` varchar(50) NOT NULL,
  `target_id` int(11) NOT NULL,
  `details` text DEFAULT NULL,
  `date_action` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `kb_articles`
--

CREATE TABLE `kb_articles` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` longtext NOT NULL,
  `category_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `views` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kb_articles`
--

INSERT INTO `kb_articles` (`id`, `title`, `content`, `category_id`, `created_at`, `updated_at`, `views`) VALUES
(1, 'Code Erreur Xiaomi M365', 'Liste rapide de tous les codes erreurs Xiaomi M365 (Liste détaillée disponible juste en dessous)\r\nSi tu souhaites directement consulter la correspondance de ton code erreur, tu peux regarder immédiatement dans la liste ci-dessous, ils sont tous répertoriés.\r\n\r\nCode erreur 10 : Défaut entre carte Bluetooth et carte mère.\r\nCode erreur 11, 12, 13, 28, 29 : Défaut MosFET carte mère\r\nCode erreur 14 : Défaut du levier de frein ou accélérateur\r\nCode erreur 15 : Défaut poignée accélérateur ou levier de frein\r\nCode erreur 18 : Défaut capteur hall moteur\r\nCode erreur 21 : Défaut communication batterie\r\nCode erreur 22, 23 : Défaut numéro de série BMS\r\nCode erreur 24 : Défaut tension batterie déséquilibré\r\nCode erreur 27, 39 : Défaut numéro série carte mère\r\nCode erreur 35, 36 : Défaut capteur ou surchauffe batterie\r\nCode erreur 40 : Défaut surchauffe carte mère\r\nCode erreur 41 : Défaut version BLE\r\nCode erreur 42 : Défaut carte mère numéro de série', 4, '2025-04-07 21:03:03', '2025-04-07 21:04:50', 17);

-- --------------------------------------------------------

--
-- Table structure for table `kb_article_ratings`
--

CREATE TABLE `kb_article_ratings` (
  `id` int(11) NOT NULL,
  `article_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `is_helpful` tinyint(1) NOT NULL,
  `rated_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kb_article_ratings`
--

INSERT INTO `kb_article_ratings` (`id`, `article_id`, `user_id`, `is_helpful`, `rated_at`) VALUES
(1, 1, 1, 0, '2025-04-07 21:03:09');

-- --------------------------------------------------------

--
-- Table structure for table `kb_article_tags`
--

CREATE TABLE `kb_article_tags` (
  `article_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kb_article_tags`
--

INSERT INTO `kb_article_tags` (`article_id`, `tag_id`) VALUES
(1, 10);

-- --------------------------------------------------------

--
-- Table structure for table `kb_categories`
--

CREATE TABLE `kb_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `icon` varchar(50) DEFAULT 'fas fa-folder',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kb_categories`
--

INSERT INTO `kb_categories` (`id`, `name`, `icon`, `created_at`) VALUES
(1, 'database_general_sample_info', 'fas fa-tools', '2025-03-30 03:07:40'),
(2, 'Inventaire', 'fas fa-boxes', '2025-03-30 03:07:40'),
(3, 'Facturation', 'fas fa-file-invoice-dollar', '2025-03-30 03:07:40'),
(4, 'Clients', 'fas fa-users', '2025-03-30 03:07:40'),
(5, 'Fournisseurs', 'fas fa-truck', '2025-03-30 03:07:40'),
(6, 'Système', 'fas fa-cogs', '2025-03-30 03:07:40'),
(7, 'Procédures', 'fas fa-clipboard-list', '2025-04-07 17:34:00'),
(8, 'Tutoriels', 'fas fa-book', '2025-04-07 17:34:00'),
(9, 'FAQ', 'fas fa-question-circle', '2025-04-07 17:34:00'),
(10, 'Documentation Technique', 'fas fa-file-alt', '2025-04-07 17:34:00');

-- --------------------------------------------------------

--
-- Table structure for table `kb_tags`
--

CREATE TABLE `kb_tags` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kb_tags`
--

INSERT INTO `kb_tags` (`id`, `name`, `created_at`) VALUES
(1, 'Guide', '2025-03-30 03:07:40'),
(2, 'Tutoriel', '2025-03-30 03:07:40'),
(3, 'Débutant', '2025-03-30 03:07:40'),
(4, 'Avancé', '2025-03-30 03:07:40'),
(5, 'Configuration', '2025-03-30 03:07:40'),
(6, 'Dépannage', '2025-03-30 03:07:40'),
(7, 'Astuces', '2025-03-30 03:07:40'),
(8, 'FAQ', '2025-03-30 03:07:40'),
(9, 'iPhone', '2025-04-07 17:34:00'),
(10, 'Android', '2025-04-07 17:34:00'),
(11, 'Samsung', '2025-04-07 17:34:00'),
(12, 'Réparation', '2025-04-07 17:34:00'),
(13, 'Formation', '2025-04-07 17:34:00'),
(14, 'Procédure', '2025-04-07 17:34:00'),
(15, 'Logiciel', '2025-04-07 17:34:00'),
(16, 'Matériel', '2025-04-07 17:34:00');

-- --------------------------------------------------------

--
-- Table structure for table `lecture_annonces`
--

CREATE TABLE `lecture_annonces` (
  `message_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `date_lecture` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lignes_commande_fournisseur`
--

CREATE TABLE `lignes_commande_fournisseur` (
  `id` int(11) NOT NULL,
  `commande_id` int(11) NOT NULL,
  `produit_id` int(11) NOT NULL,
  `quantite` int(11) NOT NULL,
  `prix_unitaire` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `Log_tasks`
--

CREATE TABLE `Log_tasks` (
  `id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action_type` enum('demarrer','terminer','pause','reprendre','modifier','creer','supprimer') NOT NULL,
  `old_status` enum('a_faire','en_cours','termine','pause','annule') DEFAULT NULL,
  `new_status` enum('a_faire','en_cours','termine','pause','annule') DEFAULT NULL,
  `action_timestamp` timestamp NULL DEFAULT current_timestamp(),
  `user_name` varchar(255) DEFAULT NULL,
  `task_title` varchar(500) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `Log_tasks`
--

INSERT INTO `Log_tasks` (`id`, `task_id`, `user_id`, `action_type`, `old_status`, `new_status`, `action_timestamp`, `user_name`, `task_title`, `details`, `ip_address`, `user_agent`) VALUES
(1, 27, 1, 'demarrer', 'a_faire', 'en_cours', '2025-06-15 22:50:07', 'Saber', 'Tâche de test pour le logging', 'Test d\'enregistrement d\'action de démarrage', NULL, NULL),
(2, 27, 1, 'terminer', 'en_cours', 'termine', '2025-06-15 22:50:07', 'Saber', 'Tâche de test pour le logging', 'Test d\'enregistrement d\'action de fin', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `marges_estimees`
--

CREATE TABLE `marges_estimees` (
  `id` int(11) NOT NULL,
  `categorie` enum('telephone','pc','tablette') NOT NULL,
  `description` varchar(255) NOT NULL,
  `prix_estime` decimal(10,2) NOT NULL,
  `marge_recommandee` decimal(10,2) NOT NULL,
  `date_creation` timestamp NULL DEFAULT current_timestamp(),
  `date_modification` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `marges_reference`
--

CREATE TABLE `marges_reference` (
  `id` int(11) NOT NULL,
  `type_reparation` varchar(255) NOT NULL,
  `categorie` enum('smartphone','tablet','computer') NOT NULL,
  `prix_achat` decimal(10,2) NOT NULL,
  `marge_pourcentage` int(11) NOT NULL,
  `date_creation` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `marges_reference`
--

INSERT INTO `marges_reference` (`id`, `type_reparation`, `categorie`, `prix_achat`, `marge_pourcentage`, `date_creation`) VALUES
(1, 'Remplacement écran iPhone', 'smartphone', 50.00, 100, '2025-04-01 13:28:19'),
(2, 'Remplacement écran Samsung', 'smartphone', 50.00, 100, '2025-04-01 13:28:19'),
(3, 'Remplacement batterie iPhone', 'smartphone', 15.00, 200, '2025-04-01 13:28:19'),
(4, 'Remplacement batterie Samsung', 'smartphone', 18.00, 200, '2025-04-01 13:28:19'),
(5, 'Remplacement connecteur de charge iPhone', 'smartphone', 10.00, 300, '2025-04-01 13:28:19'),
(6, 'Remplacement connecteur de charge Samsung', 'smartphone', 12.00, 250, '2025-04-01 13:28:19'),
(7, 'Remplacement vitre tactile iPhone', 'smartphone', 35.00, 100, '2025-04-01 13:28:19'),
(8, 'Remplacement caméra arrière iPhone', 'smartphone', 25.00, 150, '2025-04-01 13:28:19'),
(9, 'Remplacement haut-parleur iPhone', 'smartphone', 8.00, 250, '2025-04-01 13:28:27'),
(10, 'Réparation carte mère iPhone', 'smartphone', 60.00, 150, '2025-04-01 13:28:27'),
(11, 'Remplacement écran iPad', 'tablet', 65.00, 100, '2025-04-01 13:28:27'),
(12, 'Remplacement batterie iPad', 'tablet', 25.00, 150, '2025-04-01 13:28:27'),
(13, 'Remplacement connecteur de charge iPad', 'tablet', 15.00, 200, '2025-04-01 13:28:27'),
(14, 'Remplacement vitre tactile iPad', 'tablet', 40.00, 100, '2025-04-01 13:28:27'),
(15, 'Remplacement caméra iPad', 'tablet', 30.00, 120, '2025-04-01 13:28:27'),
(16, 'Réparation carte mère iPad', 'tablet', 70.00, 130, '2025-04-01 13:28:27'),
(17, 'Remplacement écran PC portable', 'computer', 80.00, 80, '2025-04-01 13:28:34'),
(18, 'Remplacement clavier PC portable', 'computer', 40.00, 100, '2025-04-01 13:28:34'),
(19, 'Remplacement disque dur par SSD', 'computer', 45.00, 90, '2025-04-01 13:28:34'),
(20, 'Remplacement batterie PC portable', 'computer', 30.00, 120, '2025-04-01 13:28:34'),
(21, 'Augmentation RAM PC portable', 'computer', 35.00, 80, '2025-04-01 13:28:34'),
(22, 'Nettoyage ventilation PC portable', 'computer', 15.00, 250, '2025-04-01 13:28:34'),
(23, 'Réparation carte mère PC portable', 'computer', 90.00, 120, '2025-04-01 13:28:34'),
(24, 'Réinstallation système d\'exploitation', 'computer', 0.00, 150, '2025-04-01 13:28:34');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `conversation_id` int(11) NOT NULL,
  `sender_id` int(11) DEFAULT NULL,
  `contenu` text DEFAULT NULL,
  `type` enum('text','file','image','system','info') NOT NULL DEFAULT 'text',
  `date_envoi` datetime DEFAULT current_timestamp(),
  `est_supprime` tinyint(1) DEFAULT 0,
  `est_modifie` tinyint(1) DEFAULT 0,
  `date_modification` datetime DEFAULT NULL,
  `est_important` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `message_attachments`
--

CREATE TABLE `message_attachments` (
  `id` int(11) NOT NULL,
  `message_id` int(11) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_type` varchar(100) NOT NULL,
  `file_size` int(11) NOT NULL,
  `thumbnail_path` varchar(255) DEFAULT NULL,
  `est_image` tinyint(1) DEFAULT 0,
  `date_upload` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `message_reactions`
--

CREATE TABLE `message_reactions` (
  `id` int(11) NOT NULL,
  `message_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `reaction` varchar(20) NOT NULL,
  `date_reaction` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `message_reads`
--

CREATE TABLE `message_reads` (
  `message_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `date_lecture` datetime DEFAULT current_timestamp(),
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `message_replies`
--

CREATE TABLE `message_replies` (
  `id` int(11) NOT NULL,
  `message_id` int(11) NOT NULL,
  `reply_to_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mouvements_stock`
--

CREATE TABLE `mouvements_stock` (
  `id` int(11) NOT NULL,
  `produit_id` int(11) NOT NULL,
  `fournisseur_id` int(11) DEFAULT NULL,
  `type_mouvement` enum('entree','sortie') NOT NULL,
  `quantite` int(11) NOT NULL,
  `date_mouvement` timestamp NULL DEFAULT current_timestamp(),
  `motif` varchar(255) DEFAULT NULL,
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `mouvements_stock`
--

INSERT INTO `mouvements_stock` (`id`, `produit_id`, `fournisseur_id`, `type_mouvement`, `quantite`, `date_mouvement`, `motif`, `user_id`) VALUES
(1, 6, NULL, 'sortie', 1, '2025-04-20 23:48:24', '2sad', 1),
(2, 6, NULL, 'entree', 3, '2025-04-20 23:49:16', 'ld', 1),
(3, 6, NULL, 'entree', 1, '2025-04-21 00:29:06', 'Retour de prêt', 1),
(4, 6, NULL, 'sortie', 1, '2025-04-21 00:43:37', 'Prêté à un partenaire', 1),
(5, 6, NULL, 'sortie', 1, '2025-04-21 00:44:04', 'Utilisé pour une réparation', 1),
(6, 6, NULL, 'entree', 1, '2025-04-21 00:45:31', 'Retour de prêt', 1),
(10, 6, NULL, 'entree', 1, '2025-04-21 01:18:29', 'Retour de prêt', 1);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `notification_type` varchar(50) NOT NULL DEFAULT 'general',
  `message` text NOT NULL,
  `related_id` int(11) DEFAULT NULL,
  `related_type` varchar(50) DEFAULT NULL,
  `action_url` varchar(255) DEFAULT NULL,
  `is_important` tinyint(1) NOT NULL DEFAULT 0,
  `is_broadcast` tinyint(1) NOT NULL DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `status` enum('new','pending','read') DEFAULT 'new',
  `created_at` datetime DEFAULT current_timestamp(),
  `read_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notification_preferences`
--

CREATE TABLE `notification_preferences` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type_notification` varchar(50) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `email_notification` tinyint(1) NOT NULL DEFAULT 0,
  `push_notification` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notification_types`
--

CREATE TABLE `notification_types` (
  `id` int(11) NOT NULL,
  `type_code` varchar(50) NOT NULL,
  `description` varchar(255) NOT NULL,
  `icon` varchar(50) NOT NULL,
  `color` varchar(20) NOT NULL,
  `importance` enum('basse','normale','haute','critique') NOT NULL DEFAULT 'normale'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notification_types`
--

INSERT INTO `notification_types` (`id`, `type_code`, `description`, `icon`, `color`, `importance`) VALUES
(1, 'reparation_start', 'Démarrage de réparation', 'fas fa-play-circle', '#4361ee', 'normale'),
(2, 'reparation_stop', 'Arrêt de réparation', 'fas fa-stop-circle', '#e11d48', 'normale'),
(3, 'reparation_update', 'Mise à jour dossier', 'fas fa-edit', '#6366f1', 'normale'),
(4, 'reparation_finish', 'Réparation terminée', 'fas fa-check-circle', '#10b981', 'haute'),
(5, 'new_device', 'Prise en charge appareil', 'fas fa-mobile-alt', '#0ea5e9', 'normale'),
(6, 'new_order', 'Nouvelle commande', 'fas fa-shopping-cart', '#f59e0b', 'haute'),
(7, 'stock_low', 'Stock bas', 'fas fa-exclamation-triangle', '#dc2626', 'critique'),
(8, 'message_received', 'Nouveau message', 'fas fa-envelope', '#8b5cf6', 'normale'),
(9, 'task_assigned', 'Tâche assignée', 'fas fa-tasks', '#ec4899', 'normale'),
(10, 'task_completed', 'Tâche terminée', 'fas fa-clipboard-check', '#0d9488', 'normale'),
(11, 'system_alert', 'Alerte système', 'fas fa-bell', '#f43f5e', 'critique'),
(12, 'appointment', 'Rendez-vous', 'fas fa-calendar-check', '#0891b2', 'haute'),
(13, 'sms_notification', 'Notification SMS', 'message', '#28a745', 'normale');

-- --------------------------------------------------------

--
-- Table structure for table `parametres`
--

CREATE TABLE `parametres` (
  `id` int(11) NOT NULL,
  `cle` varchar(50) NOT NULL,
  `valeur` text DEFAULT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `parametres`
--

INSERT INTO `parametres` (`id`, `cle`, `valeur`, `description`) VALUES
(1, 'attribution_reparation_active', '1', 'Activer/désactiver la fonctionnalité d\'attribution des réparations aux employés');

-- --------------------------------------------------------

--
-- Table structure for table `parametres_gardiennage`
--

CREATE TABLE `parametres_gardiennage` (
  `id` int(11) NOT NULL,
  `tarif_premiere_semaine` decimal(10,2) NOT NULL DEFAULT 5.00 COMMENT 'Tarif journalier pour les 7 premiers jours',
  `tarif_intermediaire` decimal(10,2) NOT NULL DEFAULT 3.00 COMMENT 'Tarif journalier de 8 à 30 jours',
  `tarif_longue_duree` decimal(10,2) NOT NULL DEFAULT 1.00 COMMENT 'Tarif journalier au-delà de 30 jours',
  `date_modification` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `parametres_gardiennage`
--

INSERT INTO `parametres_gardiennage` (`id`, `tarif_premiere_semaine`, `tarif_intermediaire`, `tarif_longue_duree`, `date_modification`) VALUES
(1, 5.00, 3.00, 1.00, '2025-04-10 18:13:37');

-- --------------------------------------------------------

--
-- Table structure for table `parrainage_config`
--

CREATE TABLE `parrainage_config` (
  `id` int(11) NOT NULL,
  `nombre_filleuls_requis` int(11) NOT NULL DEFAULT 1 COMMENT 'Nombre de filleuls requis pour activer les récompenses',
  `seuil_reduction_pourcentage` decimal(10,2) NOT NULL DEFAULT 100.00 COMMENT 'Seuil de dépense en euros pour déclencher la réduction maximale',
  `reduction_min_pourcentage` int(11) NOT NULL DEFAULT 10 COMMENT 'Pourcentage de réduction minimum (pour dépenses < seuil)',
  `reduction_max_pourcentage` int(11) NOT NULL DEFAULT 30 COMMENT 'Pourcentage de réduction maximum (pour dépenses >= seuil)',
  `actif` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Programme actif ou non',
  `date_creation` timestamp NULL DEFAULT current_timestamp(),
  `date_modification` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `parrainage_config`
--

INSERT INTO `parrainage_config` (`id`, `nombre_filleuls_requis`, `seuil_reduction_pourcentage`, `reduction_min_pourcentage`, `reduction_max_pourcentage`, `actif`, `date_creation`, `date_modification`) VALUES
(1, 1, 100.00, 10, 30, 1, '2025-04-11 02:14:22', '2025-04-11 02:14:22');

-- --------------------------------------------------------

--
-- Table structure for table `parrainage_reductions`
--

CREATE TABLE `parrainage_reductions` (
  `id` int(11) NOT NULL,
  `parrain_id` int(11) NOT NULL COMMENT 'ID du client parrain',
  `montant_depense_filleul` decimal(10,2) NOT NULL COMMENT 'Montant dépensé par le filleul qui a généré la réduction',
  `pourcentage_reduction` int(11) NOT NULL COMMENT 'Pourcentage de réduction accordé',
  `montant_reduction_max` decimal(10,2) NOT NULL COMMENT 'Montant maximum de la réduction',
  `utilise` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Si la réduction a été utilisée',
  `reparation_utilisee_id` int(11) DEFAULT NULL COMMENT 'ID de la réparation où la réduction a été utilisée',
  `date_creation` timestamp NULL DEFAULT current_timestamp(),
  `date_utilisation` timestamp NULL DEFAULT NULL COMMENT 'Date d''utilisation de la réduction'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `parrainage_relations`
--

CREATE TABLE `parrainage_relations` (
  `id` int(11) NOT NULL,
  `parrain_id` int(11) NOT NULL COMMENT 'ID du client parrain',
  `filleul_id` int(11) NOT NULL COMMENT 'ID du client filleul',
  `date_parrainage` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `partenaires`
--

CREATE TABLE `partenaires` (
  `id` int(11) NOT NULL,
  `nom` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `adresse` text DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT current_timestamp(),
  `actif` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `partenaires`
--

INSERT INTO `partenaires` (`id`, `nom`, `email`, `telephone`, `adresse`, `date_creation`, `actif`) VALUES
(1, 'Cannes Phones', '', '', '', '2025-04-05 15:09:28', 1),
(2, 'Phone Etoile', '', '', '', '2025-04-05 15:12:46', 1),
(3, 'Rayan', '', '', '', '2025-04-05 15:12:55', 1),
(5, 'Dylane', '', '', '', '2025-04-05 15:13:47', 1),
(6, 'Phone System', '', '', '', '2025-04-05 15:15:21', 1),
(7, 'Benjamin', '', '', '', '2025-04-05 15:15:44', 1);

-- --------------------------------------------------------

--
-- Table structure for table `photos_reparation`
--

CREATE TABLE `photos_reparation` (
  `id` int(11) NOT NULL,
  `reparation_id` int(11) NOT NULL,
  `url` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `date_upload` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pieces_avancees`
--

CREATE TABLE `pieces_avancees` (
  `id` int(11) NOT NULL,
  `partenaire_id` int(11) NOT NULL,
  `piece_id` int(11) NOT NULL,
  `quantite` int(11) NOT NULL,
  `prix_unitaire` decimal(10,2) NOT NULL,
  `date_avance` datetime DEFAULT current_timestamp(),
  `statut` enum('EN_ATTENTE','VALIDÉ','REMBOURSÉ','ANNULÉ') DEFAULT 'EN_ATTENTE'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `produits`
--

CREATE TABLE `produits` (
  `id` int(11) NOT NULL,
  `reference` varchar(50) NOT NULL,
  `nom` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `categorie_id` int(11) DEFAULT NULL,
  `fournisseur_id` int(11) DEFAULT NULL,
  `prix_achat` decimal(10,2) DEFAULT NULL,
  `prix_vente` decimal(10,2) DEFAULT NULL,
  `quantite` int(11) DEFAULT 0,
  `seuil_alerte` int(11) DEFAULT 5,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` enum('normal','temporaire','a_retourner') DEFAULT 'normal',
  `date_limite_retour` date DEFAULT NULL,
  `motif_retour` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `produits`
--

INSERT INTO `produits` (`id`, `reference`, `nom`, `description`, `categorie_id`, `fournisseur_id`, `prix_achat`, `prix_vente`, `quantite`, `seuil_alerte`, `created_at`, `updated_at`, `status`, `date_limite_retour`, `motif_retour`) VALUES
(6, '87248548', 'database_general_sample_info', '', NULL, NULL, 10.00, 89.00, 8, 2, '2025-04-20 23:19:34', '2025-06-09 00:05:43', 'normal', NULL, NULL),
(7, '6971402785014', 'database_general_sample_info', 'Hh', NULL, NULL, 20.00, 40.00, 9, 5, '2025-04-21 10:57:09', '2025-06-09 00:05:43', 'normal', NULL, NULL),
(8, '935764', 'database_general_sample_info', '', NULL, 4, 15.60, 99.00, 0, 2, '2025-04-21 21:16:05', '2025-06-09 00:05:43', 'a_retourner', NULL, NULL),
(9, 'iphone 8', 'database_general_sample_info', 'test', NULL, NULL, 12.00, 222.00, 0, 5, '2025-04-21 23:26:29', '2025-06-09 00:05:43', 'temporaire', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `push_subscriptions`
--

CREATE TABLE `push_subscriptions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `endpoint` varchar(512) NOT NULL,
  `auth_key` varchar(255) NOT NULL,
  `p256dh_key` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rachat_appareils`
--

CREATE TABLE `rachat_appareils` (
  `id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `type_appareil` varchar(255) NOT NULL,
  `photo_identite` varchar(255) NOT NULL,
  `photo_appareil` varchar(255) NOT NULL,
  `signature` text NOT NULL,
  `client_photo` varchar(255) DEFAULT NULL,
  `date_rachat` datetime DEFAULT current_timestamp(),
  `sin` varchar(100) DEFAULT NULL,
  `fonctionnel` tinyint(1) DEFAULT 0,
  `prix` decimal(10,2) DEFAULT NULL,
  `modele` varchar(255) DEFAULT NULL,
  `numero_serie` varchar(100) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rachat_appareils`
--

INSERT INTO `rachat_appareils` (`id`, `client_id`, `type_appareil`, `photo_identite`, `photo_appareil`, `signature`, `client_photo`, `date_rachat`, `sin`, `fonctionnel`, `prix`, `modele`, `numero_serie`, `created_by`) VALUES
(40, 414, 'Hbhj', 'identite_1744750425_67fec75998af5.jpg', 'appareil_1744750425_67fec7599b837.jpg', 'signature_1744750425_67fec759983ee.png', 'client_1744750425_67fec7599e0b9.jpg', '2025-04-15 20:53:45', 'Jhnh', 0, 80.00, 'Hbhj', NULL, NULL),
(41, 445, 'iPhone 12', 'identite_1744896129_680100816749c.jpg', 'appareil_1744896129_6801008168ee0.jpg', 'signature_1744896129_6801008166fd0.png', 'client_1744896129_680100816a537.jpg', '2025-04-17 13:22:09', 'G6TDJ84J0F0N', 1, 50.00, 'iPhone 12', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `reparations`
--

CREATE TABLE `reparations` (
  `id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `type_appareil` varchar(50) NOT NULL,
  `marque` varchar(50) NOT NULL,
  `modele` varchar(100) NOT NULL,
  `description_probleme` text NOT NULL,
  `date_reception` timestamp NULL DEFAULT current_timestamp(),
  `date_modification` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `date_fin_prevue` date DEFAULT NULL,
  `statut` varchar(50) NOT NULL DEFAULT 'nouvelle_intervention',
  `statut_id` int(11) DEFAULT NULL,
  `statut_categorie` int(11) DEFAULT NULL,
  `signature` varchar(255) DEFAULT NULL,
  `prix` decimal(10,2) DEFAULT NULL,
  `notes_techniques` text DEFAULT NULL,
  `notes_finales` text DEFAULT NULL,
  `photo_appareil` varchar(255) DEFAULT NULL,
  `mot_de_passe` varchar(100) DEFAULT NULL,
  `etat_esthetique` varchar(50) DEFAULT NULL,
  `prix_reparation` decimal(10,2) DEFAULT 0.00,
  `photos` text DEFAULT NULL,
  `urgent` tinyint(1) DEFAULT 0,
  `commande_requise` tinyint(1) DEFAULT 0,
  `archive` enum('OUI','NON') DEFAULT 'NON',
  `employe_id` int(11) DEFAULT NULL,
  `date_gardiennage` date DEFAULT NULL COMMENT 'Date de début du gardiennage',
  `gardiennage_facture` decimal(10,2) DEFAULT NULL COMMENT 'Montant facturé pour le gardiennage',
  `parrain_id` int(11) DEFAULT NULL COMMENT 'ID du client parrain si le client est un filleul',
  `reduction_parrainage` decimal(10,2) DEFAULT NULL COMMENT 'Montant de la réduction appliquée via parrainage',
  `reduction_parrainage_pourcentage` int(11) DEFAULT NULL COMMENT 'Pourcentage de la réduction parrainage appliquée',
  `signature_client` varchar(255) DEFAULT NULL,
  `photo_signature` varchar(255) DEFAULT NULL,
  `photo_client` varchar(255) DEFAULT NULL,
  `accept_conditions` tinyint(1) DEFAULT 0,
  `proprietaire` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reparation_attributions`
--

CREATE TABLE `reparation_attributions` (
  `id` int(11) NOT NULL,
  `reparation_id` int(11) NOT NULL,
  `employe_id` int(11) NOT NULL,
  `date_debut` timestamp NULL DEFAULT current_timestamp(),
  `date_fin` timestamp NULL DEFAULT NULL,
  `statut_avant` varchar(50) DEFAULT NULL,
  `statut_apres` varchar(50) DEFAULT NULL,
  `est_principal` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `reparation_attributions`
--

INSERT INTO `reparation_attributions` (`id`, `reparation_id`, `employe_id`, `date_debut`, `date_fin`, `statut_avant`, `statut_apres`, `est_principal`) VALUES
(77, 603, 1, '2025-04-17 14:46:39', '2025-04-17 16:02:37', 'nouveau_diagnostique', NULL, 1),
(89, 589, 1, '2025-04-19 10:16:53', '2025-04-19 15:30:13', 'en_attente_livraison', NULL, 1),
(91, 589, 1, '2025-04-19 15:32:13', '2025-04-19 15:32:44', 'en_attente_responsable', NULL, 1),
(92, 589, 1, '2025-04-19 15:33:15', '2025-04-20 16:34:32', 'reparation_effectue', NULL, 1),
(93, 632, 1, '2025-04-20 16:34:51', '2025-04-21 10:55:39', 'nouvelle_intervention', NULL, 1),
(94, 632, 1, '2025-04-21 10:55:48', '2025-04-21 18:25:32', 'reparation_effectue', NULL, 1),
(97, 638, 1, '2025-04-22 10:20:10', '2025-04-22 10:20:58', 'nouvelle_intervention', NULL, 1),
(98, 638, 1, '2025-04-22 10:21:02', '2025-04-22 10:21:16', 'reparation_effectue', NULL, 1),
(99, 638, 1, '2025-04-22 10:21:23', '2025-04-22 10:21:31', 'en_attente_responsable', NULL, 1),
(105, 640, 1, '2025-04-22 16:17:13', '2025-04-22 16:17:20', 'reparation_effectue', NULL, 1),
(106, 640, 1, '2025-04-22 16:17:42', '2025-04-22 16:17:54', 'reparation_effectue', NULL, 1),
(107, 640, 1, '2025-04-22 16:18:00', '2025-04-22 16:18:29', 'reparation_effectue', NULL, 1),
(108, 640, 1, '2025-04-22 16:18:35', '2025-04-22 16:18:42', 'reparation_annule', NULL, 1),
(109, 640, 1, '2025-04-22 16:18:52', '2025-04-22 16:18:55', 'en_attente_responsable', NULL, 1),
(110, 640, 1, '2025-04-22 16:24:19', '2025-04-22 16:24:22', 'reparation_effectue', NULL, 1),
(111, 644, 1, '2025-04-22 16:33:25', '2025-04-22 16:33:29', 'nouvelle_intervention', NULL, 1),
(112, 651, 1, '2025-04-23 01:19:26', '2025-04-23 08:52:05', 'restitue', NULL, 1),
(113, 652, 1, '2025-04-23 08:52:42', '2025-04-23 08:52:46', 'reparation_effectue', NULL, 1),
(114, 653, 1, '2025-04-23 09:30:49', '2025-04-23 09:30:57', 'nouvelle_intervention', NULL, 1),
(115, 625, 1, '2025-04-23 09:49:45', '2025-04-23 09:49:50', 'en_attente_livraison', NULL, 1),
(116, 655, 1, '2025-04-23 15:34:51', '2025-04-23 15:34:57', 'nouvelle_intervention', NULL, 1),
(117, 646, 1, '2025-04-25 08:23:29', '2025-04-25 08:23:38', 'nouveau_diagnostique', NULL, 1),
(118, 657, 1, '2025-04-25 13:04:32', '2025-04-25 13:04:45', 'nouvelle_intervention', NULL, 1),
(119, 663, 1, '2025-04-26 09:24:17', '2025-04-26 09:24:33', 'nouvelle_intervention', NULL, 1),
(120, 668, 1, '2025-04-26 10:01:10', '2025-04-26 10:04:27', 'nouvelle_intervention', NULL, 1),
(121, 670, 1, '2025-04-26 13:38:20', '2025-04-26 13:38:27', 'nouvelle_intervention', NULL, 1),
(122, 591, 1, '2025-04-26 14:18:07', '2025-04-26 14:18:12', 'en_attente_livraison', NULL, 1),
(123, 671, 1, '2025-04-26 15:14:58', '2025-04-26 15:15:01', 'nouvelle_intervention', NULL, 1),
(124, 675, 1, '2025-04-27 16:42:39', '2025-04-27 16:42:48', 'nouvelle_intervention', NULL, 1),
(125, 675, 1, '2025-04-28 22:35:52', '2025-04-29 10:32:39', 'restitue', NULL, 1),
(126, 688, 1, '2025-04-29 10:32:50', '2025-04-29 10:33:10', 'nouvelle_intervention', NULL, 1),
(131, 665, 1, '2025-04-30 08:47:30', '2025-04-30 08:47:34', 'en_attente_livraison', NULL, 1),
(134, 699, 1, '2025-04-30 15:16:18', '2025-04-30 15:16:26', 'en_cours_intervention', NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `reparation_logs`
--

CREATE TABLE `reparation_logs` (
  `id` int(11) NOT NULL,
  `reparation_id` int(11) NOT NULL,
  `employe_id` int(11) NOT NULL,
  `action_type` enum('demarrage','terminer','changement_statut','ajout_note','modification','autre') NOT NULL,
  `date_action` timestamp NULL DEFAULT current_timestamp(),
  `statut_avant` varchar(50) DEFAULT NULL,
  `statut_apres` varchar(50) DEFAULT NULL,
  `details` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reparation_sms`
--

CREATE TABLE `reparation_sms` (
  `id` int(11) NOT NULL,
  `reparation_id` int(11) NOT NULL,
  `template_id` int(11) NOT NULL,
  `telephone` varchar(20) NOT NULL,
  `message` text NOT NULL,
  `date_envoi` timestamp NULL DEFAULT current_timestamp(),
  `statut_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `retours`
--

CREATE TABLE `retours` (
  `id` int(11) NOT NULL,
  `produit_id` int(11) NOT NULL,
  `date_creation` datetime DEFAULT current_timestamp(),
  `date_limite` date NOT NULL,
  `statut` enum('en_attente','en_preparation','expedie','livre','a_verifier','termine') DEFAULT 'en_attente',
  `numero_suivi` varchar(100) DEFAULT NULL,
  `montant_rembourse` decimal(10,2) DEFAULT NULL,
  `montant_rembourse_client` decimal(10,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `colis_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `scheduled_notifications`
--

CREATE TABLE `scheduled_notifications` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `scheduled_datetime` datetime NOT NULL,
  `sent_datetime` datetime DEFAULT NULL,
  `target_user_id` int(11) DEFAULT NULL,
  `is_broadcast` tinyint(1) NOT NULL DEFAULT 0,
  `notification_type` varchar(50) NOT NULL DEFAULT 'general',
  `action_url` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `status` enum('pending','sent','failed','cancelled') NOT NULL DEFAULT 'pending',
  `options` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `services_partenaires`
--

CREATE TABLE `services_partenaires` (
  `id` int(11) NOT NULL,
  `partenaire_id` int(11) NOT NULL,
  `description` text NOT NULL,
  `montant` decimal(10,2) NOT NULL,
  `date_service` datetime DEFAULT current_timestamp(),
  `statut` enum('EN_ATTENTE','VALIDÉ','ANNULÉ') DEFAULT 'EN_ATTENTE'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `shops`
--

CREATE TABLE `shops` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `country` varchar(100) DEFAULT 'France',
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `website` varchar(200) DEFAULT NULL,
  `subdomain` varchar(50) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `db_host` varchar(255) NOT NULL,
  `db_port` varchar(10) DEFAULT '3306',
  `db_name` varchar(100) NOT NULL,
  `db_user` varchar(100) NOT NULL,
  `db_pass` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `shops`
--

INSERT INTO `shops` (`id`, `name`, `description`, `address`, `city`, `postal_code`, `country`, `phone`, `email`, `website`, `subdomain`, `logo`, `active`, `created_at`, `updated_at`, `db_host`, `db_port`, `db_name`, `db_user`, `db_pass`) VALUES
(1, 'DatabaseGeneral', 'Database general', NULL, NULL, NULL, 'France', NULL, NULL, NULL, NULL, NULL, 1, '2025-05-02 20:26:26', '2025-06-11 22:26:11', 'srv931.hstgr.io', '3306', 'u139954273_Vscodetest', 'u139954273_Vscodetest', 'Maman01#'),
(2, 'PScannes', 'PSCANNES', '', '', '', 'France', '', '', '', NULL, 'shop_2_1746221300.png', 1, '2025-05-02 21:28:20', '2025-06-11 22:25:52', 'srv931.hstgr.io', '3306', 'u139954273_pscannes', 'u139954273_pscannes', 'Merguez01#'),
(4, 'cannesphones', 'cannesphones', '', '', '', 'France', '', '', '', NULL, NULL, 1, '2025-05-02 22:41:55', '2025-06-07 22:50:20', 'srv931.hstgr.io', '3306', 'u139954273_cannesphones', 'u139954273_cannesphones', 'Merguez01#');

-- --------------------------------------------------------

--
-- Table structure for table `shop_admins`
--

CREATE TABLE `shop_admins` (
  `id` int(11) NOT NULL,
  `shop_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sms_campaigns`
--

CREATE TABLE `sms_campaigns` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `date_envoi` timestamp NULL DEFAULT current_timestamp(),
  `nb_destinataires` int(11) NOT NULL DEFAULT 0,
  `nb_envoyes` int(11) NOT NULL DEFAULT 0,
  `nb_echecs` int(11) NOT NULL DEFAULT 0,
  `user_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sms_campaign_details`
--

CREATE TABLE `sms_campaign_details` (
  `id` int(11) NOT NULL,
  `campaign_id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `telephone` varchar(20) NOT NULL,
  `message` text NOT NULL,
  `statut` enum('envoyé','échec') NOT NULL DEFAULT 'envoyé',
  `date_envoi` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sms_logs`
--

CREATE TABLE `sms_logs` (
  `id` int(11) NOT NULL,
  `recipient` varchar(20) NOT NULL,
  `message` text NOT NULL,
  `status` int(11) DEFAULT NULL,
  `reparation_id` int(11) DEFAULT NULL,
  `response` text DEFAULT NULL,
  `date_envoi` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sms_template`
--

CREATE TABLE `sms_template` (
  `id` int(11) NOT NULL,
  `statut_id` int(11) DEFAULT NULL,
  `message` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sms_templates`
--

CREATE TABLE `sms_templates` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `contenu` text NOT NULL,
  `statut_id` int(11) DEFAULT NULL,
  `est_actif` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sms_templates`
--

INSERT INTO `sms_templates` (`id`, `nom`, `contenu`, `statut_id`, `est_actif`, `created_at`, `updated_at`) VALUES
(1, 'Réparation en cours', 'Votre [APPAREIL_MODELE] est entre de bonnes mains ! Nos tournevis chauffent, les pixels tremblent. 🧑‍🔧\r\n🔍 Suivi : http://Mdgeek.top/suivi.php?id=[REPARATION_ID]\r\nMaison du Geek – 08 95 79 59 33', NULL, 1, '2025-04-09 23:53:13', '2025-04-17 23:46:21'),
(2, 'Réparation terminée', '[CLIENT_PRENOM], votre [APPAREIL_MODELE] est prêt ! Il vous attend pour retrouver une vie normale. 🥳\r\n🧾 Suivi : http://Mdgeek.top/suivi.php?id=[REPARATION_ID]\r\nMaison du Geek – 08 95 79 59 33', NULL, 1, '2025-04-09 23:53:13', '2025-04-17 23:46:07'),
(3, 'En attente de pièces', '📦 En attente de pièces\r\nVotre [APPAREIL_MODELE] attend sa livraison. Le livreur est en route à dos de modem. ⏳\r\n🚚 Suivi : http://Mdgeek.top/suivi.php?id=[REPARATION_ID]\r\nMaison du Geek – 08 95 79 59 33', NULL, 1, '2025-04-09 23:53:13', '2025-04-17 23:35:57'),
(4, 'En attente de validation', 'Bonjour, [CLIENT_PRENOM], \r\nle devis de votre [APPAREIL_MODELE] est disponible. \r\nMontant : [PRIX]\r\nConsultez-le ici :\r\n📄 http://Mdgeek.top/suivi.php?id=[REPARATION_ID]\r\nUne question ? Appelez-nous au 04 93 46 71 63\r\nMAISON DU GEEK', NULL, 1, '2025-04-09 23:53:13', '2025-04-19 21:46:39'),
(5, 'Nouvelle reparation', '👋 Bonjour [CLIENT_PRENOM],\r\n🛠️ Nous avons bien reçu votre [APPAREIL_MODELE] et nos experts geeks sont déjà à l\'œuvre pour le remettre en état.\r\n🔎 Suivez l\'avancement de la réparation ici :\r\n👉 http://Mdgeek.top/suivi.php?id=[REPARATION_ID]\r\n📞 Une question ? Contactez nous au 08 95 79 59 33\r\n🏠 Maison du GEEK 🛠️', NULL, 1, '2025-04-09 23:53:27', '2025-04-18 01:29:26'),
(6, 'Nouvelle Intervention', '👋 Bonjour [CLIENT_PRENOM],\r\n🛠️ Nous avons bien reçu votre [APPAREIL_MODELE] et nos experts geeks sont déjà à l\'œuvre pour le remettre en état.\r\n🔎 Suivez l\'avancement de la réparation ici :\r\n👉 http://Mdgeek.top/suivi.php?id=[REPARATION_ID]\r\n📞 Une question ? Contactez nous au 08 95 79 59 33\r\n🏠 Maison du GEEK 🛠️', NULL, 1, '2025-04-10 02:15:50', '2025-04-18 01:29:19'),
(7, 'Nouvelle Commande', 'MD Geek: Commande enregistrée pour votre [APPAREIL_MARQUE] . Ref:[REPARATION_ID]. Suivez l\'état de votre commande: mdgeek.top/suivi.php?id=[REPARATION_ID]', NULL, 1, '2025-04-10 02:15:55', '2025-04-18 01:20:57'),
(8, 'En cours de diagnostique', '👋 Hello [CLIENT_PRENOM],\r\nVotre devis pour le 📱 [APPAREIL_MODELE] est prêt !\r\n🧐 Consultez-le ici :\r\n🔗 http://Mdgeek.top/suivi.php?id=[REPARATION_ID]\r\n📞 Une question ? Appelez-nous au ☎️ 04 93 46 71 63\r\n🏠 Maison du Geek – 📱 08 95 79 59 33', NULL, 1, '2025-04-10 02:16:00', '2025-04-18 01:17:54'),
(9, 'En attente d\'un responsable', 'Bonjour [CLIENT_PRENOM], votre dossier [REPARATION_ID] au sujet de votre [APPAREIL_MODELE] est en attente de validation par un responsable technique. Nous vous tenons informé très bientôt.\r\n📲 Suivi : http://Mdgeek.top/suivi.php?id=[REPARATION_ID]\r\nMaison du Geek – 08 95 79 59 33', NULL, 1, '2025-04-10 02:16:06', '2025-04-17 23:36:28'),
(10, 'Réparation Annulée', 'Nous avons tout essayé pour sauver votre [APPAREIL_MODELE] ([APPAREIL_MARQUE]), mais pour des raisons techniques, nous avons dû annuler la réparation.\r\n📄 Détails : http://Mdgeek.top/suivi.php?id=[REPARATION_ID]\r\nMaison du Geek – 08 95 79 59 33', NULL, 1, '2025-04-10 02:16:12', '2025-04-17 23:46:00'),
(11, 'Restitué', '🎉 [CLIENT_PRENOM],\r\nTon [APPAREIL_MODELE] est de retour à la maison ! On espère qu’il est content 🤓\r\n💬 Laisse-nous un petit avis !\r\n⭐ https://g.page/r/Ce-HHwKZjezIEB0/review\r\n🏠 Maison du Geek\r\n📞 08 95 79 59 33\r\n', NULL, 1, '2025-04-10 02:16:18', '2025-04-18 01:23:35'),
(12, 'Gardiennage', '📣 [CLIENT_PRENOM],\r\nTon [APPAREIL_MODELE] est prêt mais t’attend toujours ! Des frais de gardiennage s’appliquent 🪙 et après 90j il sera recyclé gratuitement ♻️\r\n📍 Suivi : http://Mdgeek.top/suivi.php?id=[REPARATION_ID]\r\n📞 08 95 79 59 33\r\n🏠 Maison du Geek', NULL, 1, '2025-04-10 02:16:24', '2025-04-18 01:23:12'),
(13, 'Annulé', '😔 [CLIENT_PRENOM],\r\nOn a tout tenté pour réparer ton [APPAREIL_MODELE], mais pour raisons techniques, on a dû annuler la réparation.\r\n🔍 Détails : http://Mdgeek.top/suivi.php?id=[REPARATION_ID]\r\n📞 Une Question ?  08 95 79 59 33\r\n🏠 Maison du Geek', NULL, 1, '2025-04-10 02:16:29', '2025-04-18 01:22:43'),
(15, 'Terminé', '[CLIENT_PRENOM], on espère que ton [APPAREIL_MODELE] se porte comme un charme ! 😊 Aide nos Geeks avec un petit avis :\r\n⭐ https://g.page/r/Ce-HHwKZjezIEB0/review\r\n📲 Suivi : http://Mdgeek.top/suivi.php?id=[REPARATION_ID]\r\nMaison du Geek – 08 95 79 59 33', NULL, 1, '2025-04-10 02:16:51', '2025-04-17 23:46:25'),
(17, 'Relance client', 'Bonjour [CLIENT_PRENOM],\nVotre [APPAREIL_TYPE] [APPAREIL_MARQUE] [APPAREIL_MODELE] est réparé et attend votre visite à la boutique.\nMaison du Geek - 08 95 79 59 33', NULL, 1, '2025-04-23 00:14:29', '2025-04-23 00:14:29');

-- --------------------------------------------------------

--
-- Table structure for table `sms_template_variables`
--

CREATE TABLE `sms_template_variables` (
  `id` int(11) NOT NULL,
  `nom` varchar(50) NOT NULL,
  `description` varchar(255) NOT NULL,
  `exemple` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sms_template_variables`
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
(10, 'PRIX', 'Prix de la réparation', '59.90€');

-- --------------------------------------------------------

--
-- Table structure for table `soldes_partenaires`
--

CREATE TABLE `soldes_partenaires` (
  `partenaire_id` int(11) NOT NULL,
  `solde_actuel` decimal(10,2) DEFAULT 0.00,
  `derniere_mise_a_jour` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `soldes_partenaires`
--

INSERT INTO `soldes_partenaires` (`partenaire_id`, `solde_actuel`, `derniere_mise_a_jour`) VALUES
(2, -94.00, '2025-04-21 01:18:29');

-- --------------------------------------------------------

--
-- Table structure for table `statuts`
--

CREATE TABLE `statuts` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `code` varchar(50) NOT NULL,
  `couleur` varchar(7) DEFAULT '#6c757d',
  `categorie_id` int(11) NOT NULL,
  `est_actif` tinyint(1) NOT NULL DEFAULT 1,
  `ordre` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `statuts`
--

INSERT INTO `statuts` (`id`, `nom`, `code`, `couleur`, `categorie_id`, `est_actif`, `ordre`) VALUES
(18, 'Nouveau Diagnostique', 'nouveau_diagnostic', '#00d4ff', 1, 1, 1),
(19, 'Nouvelle Intervention', 'nouvelle_intervention', '#17a2b8', 1, 1, 2),
(20, 'Nouvelle Commande', 'nouvelle_commande', '#6f42c1', 1, 1, 3),
(21, 'En attente de l\'accord client', 'attente_accord', '#ffc107', 1, 1, 4),
(22, 'En attente de livraison', 'attente_livraison', '#fd7e14', 1, 1, 5),
(23, 'En attente d\'un responsable', 'attente_responsable', '#e83e8c', 1, 1, 6),
(24, 'Réparation Effectuée', 'reparation_effectuee', '#28a745', 1, 1, 7),
(25, 'Réparation Annulée', 'reparation_annulee', '#dc3545', 1, 1, 8),
(26, 'Restitué', 'restitue', '#20c997', 1, 1, 9),
(27, 'Gardiennage', 'gardiennage', '#6c757d', 1, 1, 10),
(28, 'Annulé', 'annule', '#343a40', 1, 1, 11),
(29, 'Archivé', 'archive', '#495057', 1, 1, 12),
(30, 'En cours de diagnostique', 'en_cours_diagnostic', '#0d6efd', 1, 1, 13),
(31, 'En cours d\'intervention', 'en_cours_intervention', '#7952b3', 1, 1, 14);

-- --------------------------------------------------------

--
-- Table structure for table `statuts_reparation`
--

CREATE TABLE `statuts_reparation` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `code` varchar(50) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `categorie` enum('nouvelle','en_cours','en_attente','termine','annule') NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `statuts_reparation`
--

INSERT INTO `statuts_reparation` (`id`, `user_id`, `code`, `nom`, `categorie`, `created_at`) VALUES
(1, NULL, 'nouveau_diagnostique', 'Nouveau Diagnostique', 'nouvelle', '2025-03-23 19:09:51'),
(2, NULL, 'nouvelle_intervention', 'Nouvelle Intervention', 'nouvelle', '2025-03-23 19:09:51'),
(3, NULL, 'nouvelle_commande', 'Nouvelle Commande', 'nouvelle', '2025-03-23 19:09:51'),
(4, NULL, 'en_cours_diagnostique', 'En cours de diagnostique', 'en_cours', '2025-03-23 19:09:51'),
(5, NULL, 'en_cours_intervention', 'En cours d\'intervention', 'en_cours', '2025-03-23 19:09:51'),
(6, NULL, 'en_attente_accord_client', 'En attente de l\'accord client', 'en_attente', '2025-03-23 19:09:51'),
(7, NULL, 'en_attente_livraison', 'En attente de livraison', 'en_attente', '2025-03-23 19:09:51'),
(8, NULL, 'en_attente_responsable', 'En attente d\'un responsable', 'en_attente', '2025-03-23 19:09:51'),
(9, NULL, 'reparation_effectue', 'Réparation Effectuée', 'termine', '2025-03-23 19:09:51'),
(10, NULL, 'reparation_annule', 'Réparation Annulée', 'termine', '2025-03-23 19:09:51'),
(11, NULL, 'restitue', 'Restitué', 'annule', '2025-03-23 19:09:51'),
(12, NULL, 'gardiennage', 'Gardiennage', 'annule', '2025-03-23 19:09:51'),
(13, NULL, 'annule', 'Annulé', 'annule', '2025-03-23 19:09:51');

-- --------------------------------------------------------

--
-- Table structure for table `statut_categories`
--

CREATE TABLE `statut_categories` (
  `id` int(11) NOT NULL,
  `nom` varchar(50) NOT NULL,
  `code` varchar(50) NOT NULL,
  `couleur` varchar(20) NOT NULL,
  `ordre` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `statut_categories`
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
-- Table structure for table `stock`
--

CREATE TABLE `stock` (
  `id` int(11) NOT NULL,
  `barcode` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `category` varchar(50) DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `price` decimal(10,2) DEFAULT 0.00,
  `description` text DEFAULT NULL,
  `date_created` datetime NOT NULL,
  `date_updated` datetime DEFAULT NULL,
  `status` enum('normal','temporaire','a_retourner') DEFAULT 'normal',
  `date_limite_retour` date DEFAULT NULL,
  `motif_retour` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stock_history`
--

CREATE TABLE `stock_history` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `action` varchar(20) NOT NULL,
  `quantity` int(11) NOT NULL,
  `note` text DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `date_created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `superadmins`
--

CREATE TABLE `superadmins` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `superadmins`
--

INSERT INTO `superadmins` (`id`, `username`, `password`, `full_name`, `email`, `active`, `created_at`) VALUES
(1, 'superadmin', '$2y$10$Qd6qnRp4F54lQapw.keRyOE6BVCzurkc6IbjliVNAaFwsIwDZnWD6', 'Super Administrateur', 'admin@geekboard.com', 1, '2025-05-02 20:26:26');

-- --------------------------------------------------------

--
-- Table structure for table `taches`
--

CREATE TABLE `taches` (
  `id` int(11) NOT NULL,
  `titre` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `priorite` enum('basse','moyenne','haute','urgente') DEFAULT 'moyenne',
  `statut` enum('a_faire','en_cours','termine','annule') DEFAULT 'a_faire',
  `date_creation` timestamp NULL DEFAULT current_timestamp(),
  `date_limite` date DEFAULT NULL,
  `date_fin` timestamp NULL DEFAULT NULL,
  `employe_id` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `taches`
--

INSERT INTO `taches` (`id`, `titre`, `description`, `priorite`, `statut`, `date_creation`, `date_limite`, `date_fin`, `employe_id`, `created_by`) VALUES
(27, 'Tâche de test pour le logging', 'Cette tâche a été créée automatiquement pour tester le système de logging des actions.', 'moyenne', 'a_faire', '2025-06-15 22:49:59', NULL, NULL, NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `tasks`
--

CREATE TABLE `tasks` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('en_attente','en_cours','termine','aide_necessaire') DEFAULT 'en_attente',
  `priority` enum('basse','moyenne','haute','urgente') DEFAULT 'moyenne',
  `assigned_to` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `due_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `theme_management`
--

CREATE TABLE `theme_management` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `display_name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `css_file` varchar(255) DEFAULT NULL,
  `js_file` varchar(255) DEFAULT NULL,
  `preview_color` varchar(7) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 0,
  `has_dark_mode` tinyint(1) DEFAULT 0,
  `author` varchar(100) DEFAULT NULL,
  `version` varchar(20) DEFAULT '1.0.0',
  `category` enum('classic','modern','futuristic','custom') DEFAULT 'custom',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `theme_management`
--

INSERT INTO `theme_management` (`id`, `name`, `display_name`, `description`, `css_file`, `js_file`, `preview_color`, `is_active`, `has_dark_mode`, `author`, `version`, `category`, `created_at`, `updated_at`) VALUES
(1, 'ios26_liquid_glass', 'iOS 26 Liquid Glass', 'Thème futuriste avec effets de verre liquide et animations avancées', 'assets/css/ios26-liquid-glass.css', 'assets/js/ios26-theme-manager.js', '#667eea', 0, 1, 'GeekBoard Team', '2.6.0', 'futuristic', '2025-06-16 23:49:16', '2025-06-16 23:49:16'),
(2, 'modern_theme', 'Thème Moderne', 'Interface moderne et épurée avec support du mode sombre', 'assets/css/modern-theme.css', NULL, '#2196F3', 0, 1, 'GeekBoard Team', '1.2.0', 'modern', '2025-06-16 23:49:16', '2025-06-16 23:49:16'),
(3, 'dark_theme', 'Thème Sombre', 'Thème sombre pour une utilisation en environnement faiblement éclairé', 'assets/css/dark-theme.css', NULL, '#212121', 0, 0, 'GeekBoard Team', '1.0.0', 'classic', '2025-06-16 23:49:17', '2025-06-16 23:49:17'),
(4, 'classic_theme', 'Thème Classique', 'Le thème par défaut de GeekBoard, simple et efficace', NULL, NULL, '#0078e8', 1, 0, 'GeekBoard Team', '1.0.0', 'classic', '2025-06-16 23:49:17', '2025-06-16 23:49:17');

-- --------------------------------------------------------

--
-- Table structure for table `transactions_partenaires`
--

CREATE TABLE `transactions_partenaires` (
  `id` int(11) NOT NULL,
  `partenaire_id` int(11) NOT NULL,
  `type` enum('AVANCE','REMBOURSEMENT','SERVICE') NOT NULL,
  `montant` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `date_transaction` datetime DEFAULT current_timestamp(),
  `reference_document` varchar(255) DEFAULT NULL,
  `statut` enum('EN_ATTENTE','VALIDÉ','ANNULÉ') DEFAULT 'EN_ATTENTE'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `transactions_partenaires`
--

INSERT INTO `transactions_partenaires` (`id`, `partenaire_id`, `type`, `montant`, `description`, `date_transaction`, `reference_document`, `statut`) VALUES
(19, 2, 'REMBOURSEMENT', 89.00, 'Retour de prêt d\'un LCD iphone 11', '2025-04-21 00:45:31', NULL, 'EN_ATTENTE'),
(22, 2, 'REMBOURSEMENT', 10.00, 'Retour de prêt d\'un LCD iphone 11', '2025-04-21 01:18:29', NULL, 'EN_ATTENTE');

-- --------------------------------------------------------

--
-- Table structure for table `typing_status`
--

CREATE TABLE `typing_status` (
  `user_id` int(11) NOT NULL,
  `conversation_id` int(11) NOT NULL,
  `timestamp` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('admin','technicien') NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `techbusy` int(11) DEFAULT 0,
  `active_repair_id` int(11) DEFAULT NULL,
  `shop_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `role`, `created_at`, `techbusy`, `active_repair_id`, `shop_id`) VALUES
(1, 'database_general_sample_info', '$2y$10$3JTMop/lAGF.gXQHR2wfBeDF.o84HA3h3W29riqL/lK3d9klKLQq.', 'Saber', 'admin', '2025-03-20 10:15:29', 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_sessions`
--

CREATE TABLE `user_sessions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expiry` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_sessions`
--

INSERT INTO `user_sessions` (`id`, `user_id`, `token`, `expiry`, `created_at`, `ip_address`, `user_agent`) VALUES
(1, 1, '11dee6a1ece1222631b78a9bdd5ebf863959bafc431453e7aad5760421139894', '2025-05-05 20:28:57', '2025-04-11 15:25:02', NULL, NULL),
(353, 2, '9271c14ff9cf1babc3babe23f2b240e9091198a0762368cb883380f6431f4d75', '2025-04-25 22:29:26', '2025-04-22 08:10:09', NULL, NULL),
(359, 3, 'ef8e240f3484df1142ea80017a057dcc807a11d4f72b7cbe75e91b15995824b5', '2025-05-03 08:49:38', '2025-04-22 12:33:39', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_theme_preferences`
--

CREATE TABLE `user_theme_preferences` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `theme_id` int(11) NOT NULL,
  `dark_mode_enabled` tinyint(1) DEFAULT 0,
  `auto_dark_mode` tinyint(1) DEFAULT 1,
  `dark_mode_start_time` time DEFAULT '19:00:00',
  `dark_mode_end_time` time DEFAULT '07:00:00',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bug_reports`
--
ALTER TABLE `bug_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `colis_retour`
--
ALTER TABLE `colis_retour`
  ADD PRIMARY KEY (`id`),
  ADD KEY `statut` (`statut`);

--
-- Indexes for table `commandes_fournisseurs`
--
ALTER TABLE `commandes_fournisseurs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fournisseur_id` (`fournisseur_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `commandes_pieces`
--
ALTER TABLE `commandes_pieces`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `reference` (`reference`),
  ADD KEY `reparation_id` (`reparation_id`),
  ADD KEY `fournisseur_id` (`fournisseur_id`),
  ADD KEY `fk_commandes_pieces_client` (`client_id`);

--
-- Indexes for table `commentaires_tache`
--
ALTER TABLE `commentaires_tache`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tache_id` (`tache_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `confirmations_lecture`
--
ALTER TABLE `confirmations_lecture`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_confirmation` (`message_id`,`employe_id`),
  ADD KEY `employe_id` (`employe_id`);

--
-- Indexes for table `conges_demandes`
--
ALTER TABLE `conges_demandes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `conges_jours_disponibles`
--
ALTER TABLE `conges_jours_disponibles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_date` (`date`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_date` (`date`);

--
-- Indexes for table `conges_solde`
--
ALTER TABLE `conges_solde`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `conversations`
--
ALTER TABLE `conversations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `conversation_participants`
--
ALTER TABLE `conversation_participants`
  ADD PRIMARY KEY (`conversation_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `employes`
--
ALTER TABLE `employes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `fournisseurs`
--
ALTER TABLE `fournisseurs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `gardiennage`
--
ALTER TABLE `gardiennage`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reparation_id` (`reparation_id`);

--
-- Indexes for table `gardiennage_notifications`
--
ALTER TABLE `gardiennage_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `gardiennage_id` (`gardiennage_id`);

--
-- Indexes for table `global_theme_settings`
--
ALTER TABLE `global_theme_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`),
  ADD KEY `idx_key` (`setting_key`);

--
-- Indexes for table `help_requests`
--
ALTER TABLE `help_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `task_id` (`task_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `historique_soldes`
--
ALTER TABLE `historique_soldes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `partenaire_id` (`partenaire_id`),
  ADD KEY `transaction_id` (`transaction_id`);

--
-- Indexes for table `journal_actions`
--
ALTER TABLE `journal_actions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `action_type` (`action_type`),
  ADD KEY `target_id` (`target_id`);

--
-- Indexes for table `kb_articles`
--
ALTER TABLE `kb_articles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `kb_article_ratings`
--
ALTER TABLE `kb_article_ratings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `article_user` (`article_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `kb_article_tags`
--
ALTER TABLE `kb_article_tags`
  ADD PRIMARY KEY (`article_id`,`tag_id`),
  ADD KEY `tag_id` (`tag_id`);

--
-- Indexes for table `kb_categories`
--
ALTER TABLE `kb_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `kb_tags`
--
ALTER TABLE `kb_tags`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `lecture_annonces`
--
ALTER TABLE `lecture_annonces`
  ADD PRIMARY KEY (`message_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `lignes_commande_fournisseur`
--
ALTER TABLE `lignes_commande_fournisseur`
  ADD PRIMARY KEY (`id`),
  ADD KEY `commande_id` (`commande_id`),
  ADD KEY `produit_id` (`produit_id`);

--
-- Indexes for table `Log_tasks`
--
ALTER TABLE `Log_tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_task_id` (`task_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_action_type` (`action_type`),
  ADD KEY `idx_timestamp` (`action_timestamp`);

--
-- Indexes for table `marges_estimees`
--
ALTER TABLE `marges_estimees`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `marges_reference`
--
ALTER TABLE `marges_reference`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `conversation_id` (`conversation_id`),
  ADD KEY `sender_id` (`sender_id`);

--
-- Indexes for table `message_attachments`
--
ALTER TABLE `message_attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `message_id` (`message_id`);

--
-- Indexes for table `message_reactions`
--
ALTER TABLE `message_reactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_reaction` (`message_id`,`user_id`,`reaction`),
  ADD KEY `idx_message_reactions_message_id` (`message_id`),
  ADD KEY `idx_message_reactions_user_id` (`user_id`);

--
-- Indexes for table `message_reads`
--
ALTER TABLE `message_reads`
  ADD PRIMARY KEY (`message_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `message_replies`
--
ALTER TABLE `message_replies`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_reply` (`message_id`,`reply_to_id`),
  ADD KEY `reply_to_id` (`reply_to_id`);

--
-- Indexes for table `mouvements_stock`
--
ALTER TABLE `mouvements_stock`
  ADD PRIMARY KEY (`id`),
  ADD KEY `produit_id` (`produit_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `mouvements_stock_fournisseur_fk` (`fournisseur_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_user_status` (`user_id`,`status`),
  ADD KEY `notification_type` (`notification_type`),
  ADD KEY `status` (`status`),
  ADD KEY `created_at` (`created_at`);

--
-- Indexes for table `notification_preferences`
--
ALTER TABLE `notification_preferences`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_type_unique` (`user_id`,`type_notification`);

--
-- Indexes for table `notification_types`
--
ALTER TABLE `notification_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `type_code` (`type_code`);

--
-- Indexes for table `parametres`
--
ALTER TABLE `parametres`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cle` (`cle`);

--
-- Indexes for table `parametres_gardiennage`
--
ALTER TABLE `parametres_gardiennage`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `parrainage_config`
--
ALTER TABLE `parrainage_config`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `parrainage_reductions`
--
ALTER TABLE `parrainage_reductions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parrain_id` (`parrain_id`),
  ADD KEY `reparation_utilisee_id` (`reparation_utilisee_id`);

--
-- Indexes for table `parrainage_relations`
--
ALTER TABLE `parrainage_relations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_parrainage` (`filleul_id`) COMMENT 'Un filleul ne peut avoir qu''un seul parrain',
  ADD KEY `parrain_id` (`parrain_id`);

--
-- Indexes for table `partenaires`
--
ALTER TABLE `partenaires`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `photos_reparation`
--
ALTER TABLE `photos_reparation`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reparation_id` (`reparation_id`);

--
-- Indexes for table `pieces_avancees`
--
ALTER TABLE `pieces_avancees`
  ADD PRIMARY KEY (`id`),
  ADD KEY `partenaire_id` (`partenaire_id`),
  ADD KEY `piece_id` (`piece_id`);

--
-- Indexes for table `produits`
--
ALTER TABLE `produits`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `reference` (`reference`),
  ADD KEY `categorie_id` (`categorie_id`),
  ADD KEY `produits_fournisseur_fk` (`fournisseur_id`);

--
-- Indexes for table `push_subscriptions`
--
ALTER TABLE `push_subscriptions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_endpoint` (`user_id`,`endpoint`(255)),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `rachat_appareils`
--
ALTER TABLE `rachat_appareils`
  ADD PRIMARY KEY (`id`),
  ADD KEY `client_id` (`client_id`);

--
-- Indexes for table `reparations`
--
ALTER TABLE `reparations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `fk_reparation_employe` (`employe_id`),
  ADD KEY `parrain_id` (`parrain_id`);

--
-- Indexes for table `reparation_attributions`
--
ALTER TABLE `reparation_attributions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_reparation` (`reparation_id`),
  ADD KEY `idx_employe` (`employe_id`);

--
-- Indexes for table `reparation_logs`
--
ALTER TABLE `reparation_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_reparation` (`reparation_id`),
  ADD KEY `idx_action_type` (`action_type`),
  ADD KEY `idx_date_action` (`date_action`),
  ADD KEY `idx_employe` (`employe_id`);

--
-- Indexes for table `reparation_sms`
--
ALTER TABLE `reparation_sms`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reparation_id` (`reparation_id`),
  ADD KEY `template_id` (`template_id`),
  ADD KEY `statut_id` (`statut_id`);

--
-- Indexes for table `retours`
--
ALTER TABLE `retours`
  ADD PRIMARY KEY (`id`),
  ADD KEY `produit_id` (`produit_id`),
  ADD KEY `statut` (`statut`),
  ADD KEY `date_limite` (`date_limite`),
  ADD KEY `colis_id` (`colis_id`);

--
-- Indexes for table `scheduled_notifications`
--
ALTER TABLE `scheduled_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `scheduled_datetime` (`scheduled_datetime`),
  ADD KEY `status` (`status`),
  ADD KEY `target_user_id` (`target_user_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `services_partenaires`
--
ALTER TABLE `services_partenaires`
  ADD PRIMARY KEY (`id`),
  ADD KEY `partenaire_id` (`partenaire_id`);

--
-- Indexes for table `shops`
--
ALTER TABLE `shops`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `subdomain` (`subdomain`);

--
-- Indexes for table `shop_admins`
--
ALTER TABLE `shop_admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `shop_id` (`shop_id`);

--
-- Indexes for table `sms_campaigns`
--
ALTER TABLE `sms_campaigns`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `sms_campaign_details`
--
ALTER TABLE `sms_campaign_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `campaign_id` (`campaign_id`),
  ADD KEY `client_id` (`client_id`);

--
-- Indexes for table `sms_logs`
--
ALTER TABLE `sms_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_date` (`date_envoi`);

--
-- Indexes for table `sms_template`
--
ALTER TABLE `sms_template`
  ADD PRIMARY KEY (`id`),
  ADD KEY `statut_id` (`statut_id`);

--
-- Indexes for table `sms_templates`
--
ALTER TABLE `sms_templates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_statut` (`statut_id`);

--
-- Indexes for table `sms_template_variables`
--
ALTER TABLE `sms_template_variables`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nom` (`nom`);

--
-- Indexes for table `soldes_partenaires`
--
ALTER TABLE `soldes_partenaires`
  ADD PRIMARY KEY (`partenaire_id`);

--
-- Indexes for table `statuts`
--
ALTER TABLE `statuts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `categorie_id` (`categorie_id`);

--
-- Indexes for table `statuts_reparation`
--
ALTER TABLE `statuts_reparation`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `statut_categories`
--
ALTER TABLE `statut_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `stock`
--
ALTER TABLE `stock`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `barcode` (`barcode`);

--
-- Indexes for table `stock_history`
--
ALTER TABLE `stock_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `superadmins`
--
ALTER TABLE `superadmins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `taches`
--
ALTER TABLE `taches`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `taches_ibfk_1` (`employe_id`);

--
-- Indexes for table `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `assigned_to` (`assigned_to`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `theme_management`
--
ALTER TABLE `theme_management`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD KEY `idx_active` (`is_active`),
  ADD KEY `idx_category` (`category`);

--
-- Indexes for table `transactions_partenaires`
--
ALTER TABLE `transactions_partenaires`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_transactions_partenaires` (`partenaire_id`);

--
-- Indexes for table `typing_status`
--
ALTER TABLE `typing_status`
  ADD PRIMARY KEY (`user_id`,`conversation_id`),
  ADD KEY `conversation_id` (`conversation_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `idx_shop_id` (`shop_id`);

--
-- Indexes for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `expiry` (`expiry`);

--
-- Indexes for table `user_theme_preferences`
--
ALTER TABLE `user_theme_preferences`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_theme` (`user_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_theme` (`theme_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bug_reports`
--
ALTER TABLE `bug_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=113;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `clients`
--
ALTER TABLE `clients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=513;

--
-- AUTO_INCREMENT for table `colis_retour`
--
ALTER TABLE `colis_retour`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `commandes_fournisseurs`
--
ALTER TABLE `commandes_fournisseurs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `commandes_pieces`
--
ALTER TABLE `commandes_pieces`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=184;

--
-- AUTO_INCREMENT for table `commentaires_tache`
--
ALTER TABLE `commentaires_tache`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `confirmations_lecture`
--
ALTER TABLE `confirmations_lecture`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `conges_demandes`
--
ALTER TABLE `conges_demandes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `conges_jours_disponibles`
--
ALTER TABLE `conges_jours_disponibles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `conges_solde`
--
ALTER TABLE `conges_solde`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `conversations`
--
ALTER TABLE `conversations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `employes`
--
ALTER TABLE `employes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `fournisseurs`
--
ALTER TABLE `fournisseurs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `gardiennage`
--
ALTER TABLE `gardiennage`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `gardiennage_notifications`
--
ALTER TABLE `gardiennage_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `global_theme_settings`
--
ALTER TABLE `global_theme_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `help_requests`
--
ALTER TABLE `help_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `historique_soldes`
--
ALTER TABLE `historique_soldes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `journal_actions`
--
ALTER TABLE `journal_actions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `kb_articles`
--
ALTER TABLE `kb_articles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `kb_article_ratings`
--
ALTER TABLE `kb_article_ratings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `kb_categories`
--
ALTER TABLE `kb_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `kb_tags`
--
ALTER TABLE `kb_tags`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `lignes_commande_fournisseur`
--
ALTER TABLE `lignes_commande_fournisseur`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `Log_tasks`
--
ALTER TABLE `Log_tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `marges_estimees`
--
ALTER TABLE `marges_estimees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `marges_reference`
--
ALTER TABLE `marges_reference`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `message_attachments`
--
ALTER TABLE `message_attachments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `message_reactions`
--
ALTER TABLE `message_reactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `message_replies`
--
ALTER TABLE `message_replies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mouvements_stock`
--
ALTER TABLE `mouvements_stock`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notification_preferences`
--
ALTER TABLE `notification_preferences`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notification_types`
--
ALTER TABLE `notification_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `parametres`
--
ALTER TABLE `parametres`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `parametres_gardiennage`
--
ALTER TABLE `parametres_gardiennage`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `parrainage_config`
--
ALTER TABLE `parrainage_config`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `parrainage_reductions`
--
ALTER TABLE `parrainage_reductions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `parrainage_relations`
--
ALTER TABLE `parrainage_relations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `partenaires`
--
ALTER TABLE `partenaires`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `photos_reparation`
--
ALTER TABLE `photos_reparation`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `pieces_avancees`
--
ALTER TABLE `pieces_avancees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `produits`
--
ALTER TABLE `produits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `push_subscriptions`
--
ALTER TABLE `push_subscriptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rachat_appareils`
--
ALTER TABLE `rachat_appareils`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `reparations`
--
ALTER TABLE `reparations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=702;

--
-- AUTO_INCREMENT for table `reparation_attributions`
--
ALTER TABLE `reparation_attributions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=135;

--
-- AUTO_INCREMENT for table `reparation_logs`
--
ALTER TABLE `reparation_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1124;

--
-- AUTO_INCREMENT for table `reparation_sms`
--
ALTER TABLE `reparation_sms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=441;

--
-- AUTO_INCREMENT for table `retours`
--
ALTER TABLE `retours`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `scheduled_notifications`
--
ALTER TABLE `scheduled_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `services_partenaires`
--
ALTER TABLE `services_partenaires`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `shops`
--
ALTER TABLE `shops`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `shop_admins`
--
ALTER TABLE `shop_admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sms_campaigns`
--
ALTER TABLE `sms_campaigns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sms_campaign_details`
--
ALTER TABLE `sms_campaign_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sms_logs`
--
ALTER TABLE `sms_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=449;

--
-- AUTO_INCREMENT for table `sms_template`
--
ALTER TABLE `sms_template`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sms_templates`
--
ALTER TABLE `sms_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `sms_template_variables`
--
ALTER TABLE `sms_template_variables`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `statuts`
--
ALTER TABLE `statuts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `statuts_reparation`
--
ALTER TABLE `statuts_reparation`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `statut_categories`
--
ALTER TABLE `statut_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `stock`
--
ALTER TABLE `stock`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stock_history`
--
ALTER TABLE `stock_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `superadmins`
--
ALTER TABLE `superadmins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `taches`
--
ALTER TABLE `taches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `theme_management`
--
ALTER TABLE `theme_management`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `transactions_partenaires`
--
ALTER TABLE `transactions_partenaires`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `user_sessions`
--
ALTER TABLE `user_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=488;

--
-- AUTO_INCREMENT for table `user_theme_preferences`
--
ALTER TABLE `user_theme_preferences`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bug_reports`
--
ALTER TABLE `bug_reports`
  ADD CONSTRAINT `bug_reports_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `commandes_fournisseurs`
--
ALTER TABLE `commandes_fournisseurs`
  ADD CONSTRAINT `commandes_fournisseurs_ibfk_1` FOREIGN KEY (`fournisseur_id`) REFERENCES `fournisseurs` (`id`),
  ADD CONSTRAINT `commandes_fournisseurs_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `commandes_pieces`
--
ALTER TABLE `commandes_pieces`
  ADD CONSTRAINT `commandes_pieces_ibfk_1` FOREIGN KEY (`reparation_id`) REFERENCES `reparations` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `commandes_pieces_ibfk_2` FOREIGN KEY (`fournisseur_id`) REFERENCES `fournisseurs` (`id`),
  ADD CONSTRAINT `fk_commandes_pieces_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`);

--
-- Constraints for table `commentaires_tache`
--
ALTER TABLE `commentaires_tache`
  ADD CONSTRAINT `commentaires_tache_ibfk_1` FOREIGN KEY (`tache_id`) REFERENCES `taches` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `commentaires_tache_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `confirmations_lecture`
--
ALTER TABLE `confirmations_lecture`
  ADD CONSTRAINT `confirmations_lecture_ibfk_1` FOREIGN KEY (`message_id`) REFERENCES `messages` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `confirmations_lecture_ibfk_2` FOREIGN KEY (`employe_id`) REFERENCES `employes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `conges_demandes`
--
ALTER TABLE `conges_demandes`
  ADD CONSTRAINT `conges_demandes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `conges_demandes_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `conges_jours_disponibles`
--
ALTER TABLE `conges_jours_disponibles`
  ADD CONSTRAINT `conges_jours_disponibles_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `conges_solde`
--
ALTER TABLE `conges_solde`
  ADD CONSTRAINT `conges_solde_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `conversations`
--
ALTER TABLE `conversations`
  ADD CONSTRAINT `conversations_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `conversation_participants`
--
ALTER TABLE `conversation_participants`
  ADD CONSTRAINT `conversation_participants_ibfk_1` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `conversation_participants_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `gardiennage`
--
ALTER TABLE `gardiennage`
  ADD CONSTRAINT `gardiennage_ibfk_1` FOREIGN KEY (`reparation_id`) REFERENCES `reparations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `gardiennage_notifications`
--
ALTER TABLE `gardiennage_notifications`
  ADD CONSTRAINT `gardiennage_notifications_ibfk_1` FOREIGN KEY (`gardiennage_id`) REFERENCES `gardiennage` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `help_requests`
--
ALTER TABLE `help_requests`
  ADD CONSTRAINT `help_requests_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `help_requests_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `historique_soldes`
--
ALTER TABLE `historique_soldes`
  ADD CONSTRAINT `historique_soldes_ibfk_1` FOREIGN KEY (`partenaire_id`) REFERENCES `fournisseurs` (`id`),
  ADD CONSTRAINT `historique_soldes_ibfk_2` FOREIGN KEY (`transaction_id`) REFERENCES `transactions_partenaires` (`id`);

--
-- Constraints for table `kb_articles`
--
ALTER TABLE `kb_articles`
  ADD CONSTRAINT `kb_articles_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `kb_categories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `kb_article_ratings`
--
ALTER TABLE `kb_article_ratings`
  ADD CONSTRAINT `kb_article_ratings_ibfk_1` FOREIGN KEY (`article_id`) REFERENCES `kb_articles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `kb_article_ratings_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `kb_article_tags`
--
ALTER TABLE `kb_article_tags`
  ADD CONSTRAINT `kb_article_tags_ibfk_1` FOREIGN KEY (`article_id`) REFERENCES `kb_articles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `kb_article_tags_ibfk_2` FOREIGN KEY (`tag_id`) REFERENCES `kb_tags` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `lecture_annonces`
--
ALTER TABLE `lecture_annonces`
  ADD CONSTRAINT `lecture_annonces_ibfk_1` FOREIGN KEY (`message_id`) REFERENCES `messages` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `lecture_annonces_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `lignes_commande_fournisseur`
--
ALTER TABLE `lignes_commande_fournisseur`
  ADD CONSTRAINT `lignes_commande_fournisseur_ibfk_1` FOREIGN KEY (`commande_id`) REFERENCES `commandes_fournisseurs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `lignes_commande_fournisseur_ibfk_2` FOREIGN KEY (`produit_id`) REFERENCES `produits` (`id`);

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `message_attachments`
--
ALTER TABLE `message_attachments`
  ADD CONSTRAINT `message_attachments_ibfk_1` FOREIGN KEY (`message_id`) REFERENCES `messages` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `message_reactions`
--
ALTER TABLE `message_reactions`
  ADD CONSTRAINT `message_reactions_ibfk_1` FOREIGN KEY (`message_id`) REFERENCES `messages` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `message_reactions_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `message_reads`
--
ALTER TABLE `message_reads`
  ADD CONSTRAINT `message_reads_ibfk_1` FOREIGN KEY (`message_id`) REFERENCES `messages` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `message_reads_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `message_replies`
--
ALTER TABLE `message_replies`
  ADD CONSTRAINT `message_replies_ibfk_1` FOREIGN KEY (`message_id`) REFERENCES `messages` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `message_replies_ibfk_2` FOREIGN KEY (`reply_to_id`) REFERENCES `messages` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `mouvements_stock`
--
ALTER TABLE `mouvements_stock`
  ADD CONSTRAINT `mouvements_stock_fournisseur_fk` FOREIGN KEY (`fournisseur_id`) REFERENCES `fournisseurs` (`id`),
  ADD CONSTRAINT `mouvements_stock_ibfk_1` FOREIGN KEY (`produit_id`) REFERENCES `produits` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `mouvements_stock_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `parrainage_reductions`
--
ALTER TABLE `parrainage_reductions`
  ADD CONSTRAINT `parrainage_reductions_ibfk_1` FOREIGN KEY (`parrain_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `parrainage_reductions_ibfk_2` FOREIGN KEY (`reparation_utilisee_id`) REFERENCES `reparations` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `parrainage_relations`
--
ALTER TABLE `parrainage_relations`
  ADD CONSTRAINT `parrainage_relations_ibfk_1` FOREIGN KEY (`parrain_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `parrainage_relations_ibfk_2` FOREIGN KEY (`filleul_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `photos_reparation`
--
ALTER TABLE `photos_reparation`
  ADD CONSTRAINT `photos_reparation_ibfk_1` FOREIGN KEY (`reparation_id`) REFERENCES `reparations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `pieces_avancees`
--
ALTER TABLE `pieces_avancees`
  ADD CONSTRAINT `pieces_avancees_ibfk_1` FOREIGN KEY (`partenaire_id`) REFERENCES `fournisseurs` (`id`),
  ADD CONSTRAINT `pieces_avancees_ibfk_2` FOREIGN KEY (`piece_id`) REFERENCES `produits` (`id`);

--
-- Constraints for table `produits`
--
ALTER TABLE `produits`
  ADD CONSTRAINT `produits_fournisseur_fk` FOREIGN KEY (`fournisseur_id`) REFERENCES `fournisseurs` (`id`),
  ADD CONSTRAINT `produits_ibfk_1` FOREIGN KEY (`categorie_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `push_subscriptions`
--
ALTER TABLE `push_subscriptions`
  ADD CONSTRAINT `push_subscriptions_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `rachat_appareils`
--
ALTER TABLE `rachat_appareils`
  ADD CONSTRAINT `rachat_appareils_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`);

--
-- Constraints for table `reparations`
--
ALTER TABLE `reparations`
  ADD CONSTRAINT `fk_reparation_employe` FOREIGN KEY (`employe_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `reparations_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`),
  ADD CONSTRAINT `reparations_ibfk_2` FOREIGN KEY (`parrain_id`) REFERENCES `clients` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `reparation_attributions`
--
ALTER TABLE `reparation_attributions`
  ADD CONSTRAINT `fk_attribution_reparation` FOREIGN KEY (`reparation_id`) REFERENCES `reparations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_attribution_user` FOREIGN KEY (`employe_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reparation_logs`
--
ALTER TABLE `reparation_logs`
  ADD CONSTRAINT `fk_log_reparation` FOREIGN KEY (`reparation_id`) REFERENCES `reparations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_log_user` FOREIGN KEY (`employe_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reparation_sms`
--
ALTER TABLE `reparation_sms`
  ADD CONSTRAINT `reparation_sms_ibfk_1` FOREIGN KEY (`reparation_id`) REFERENCES `reparations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reparation_sms_ibfk_2` FOREIGN KEY (`template_id`) REFERENCES `sms_templates` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reparation_sms_ibfk_3` FOREIGN KEY (`statut_id`) REFERENCES `statuts` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `retours`
--
ALTER TABLE `retours`
  ADD CONSTRAINT `retours_ibfk_1` FOREIGN KEY (`produit_id`) REFERENCES `stock` (`id`),
  ADD CONSTRAINT `retours_ibfk_2` FOREIGN KEY (`colis_id`) REFERENCES `colis_retour` (`id`);

--
-- Constraints for table `scheduled_notifications`
--
ALTER TABLE `scheduled_notifications`
  ADD CONSTRAINT `scheduled_notifications_created_by_fk` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `scheduled_notifications_target_user_fk` FOREIGN KEY (`target_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `services_partenaires`
--
ALTER TABLE `services_partenaires`
  ADD CONSTRAINT `services_partenaires_ibfk_1` FOREIGN KEY (`partenaire_id`) REFERENCES `fournisseurs` (`id`);

--
-- Constraints for table `shop_admins`
--
ALTER TABLE `shop_admins`
  ADD CONSTRAINT `shop_admins_ibfk_1` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sms_campaigns`
--
ALTER TABLE `sms_campaigns`
  ADD CONSTRAINT `sms_campaigns_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `sms_campaign_details`
--
ALTER TABLE `sms_campaign_details`
  ADD CONSTRAINT `sms_campaign_details_ibfk_1` FOREIGN KEY (`campaign_id`) REFERENCES `sms_campaigns` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sms_campaign_details_ibfk_2` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sms_template`
--
ALTER TABLE `sms_template`
  ADD CONSTRAINT `sms_template_ibfk_1` FOREIGN KEY (`statut_id`) REFERENCES `statuts` (`id`);

--
-- Constraints for table `sms_templates`
--
ALTER TABLE `sms_templates`
  ADD CONSTRAINT `sms_templates_ibfk_1` FOREIGN KEY (`statut_id`) REFERENCES `statuts` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `soldes_partenaires`
--
ALTER TABLE `soldes_partenaires`
  ADD CONSTRAINT `fk_soldes_partenaires` FOREIGN KEY (`partenaire_id`) REFERENCES `partenaires` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `soldes_partenaires_ibfk_1` FOREIGN KEY (`partenaire_id`) REFERENCES `fournisseurs` (`id`);

--
-- Constraints for table `statuts`
--
ALTER TABLE `statuts`
  ADD CONSTRAINT `statuts_ibfk_1` FOREIGN KEY (`categorie_id`) REFERENCES `statut_categories` (`id`);

--
-- Constraints for table `statuts_reparation`
--
ALTER TABLE `statuts_reparation`
  ADD CONSTRAINT `statuts_reparation_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `stock_history`
--
ALTER TABLE `stock_history`
  ADD CONSTRAINT `stock_history_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `stock` (`id`);

--
-- Constraints for table `taches`
--
ALTER TABLE `taches`
  ADD CONSTRAINT `taches_ibfk_1` FOREIGN KEY (`employe_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `taches_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `tasks`
--
ALTER TABLE `tasks`
  ADD CONSTRAINT `tasks_ibfk_1` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `tasks_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `transactions_partenaires`
--
ALTER TABLE `transactions_partenaires`
  ADD CONSTRAINT `fk_transactions_partenaires` FOREIGN KEY (`partenaire_id`) REFERENCES `partenaires` (`id`);

--
-- Constraints for table `typing_status`
--
ALTER TABLE `typing_status`
  ADD CONSTRAINT `typing_status_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `typing_status_ibfk_2` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_theme_preferences`
--
ALTER TABLE `user_theme_preferences`
  ADD CONSTRAINT `user_theme_preferences_ibfk_1` FOREIGN KEY (`theme_id`) REFERENCES `theme_management` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
