<?php
// Routeur marketing pour servo.tools (ex-mdgeek.top)
// Objectif: servir un site vitrine multi-pages sans charger l'application SaaS

// Initialiser le système d'internationalisation
require_once __DIR__ . '/includes/i18n.php';

// Déterminer la page demandée en tenant compte des préfixes de langue
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';

// Normaliser et extraire le chemin sans query string
$path = parse_url($requestUri, PHP_URL_PATH) ?? '/';
$path = rtrim($path, '/');

// Supprimer le préfixe de langue de l'URL si présent (/en, /es, /de, /it)
$cleanPath = $path;
if (preg_match('/^\/([a-z]{2})(?:\/(.*))?$/', $path, $matches)) {
    $langCode = $matches[1];
    $cleanPath = '/' . ($matches[2] ?? '');
    $cleanPath = rtrim($cleanPath, '/');
}

// Support des formats: /, /features, /pricing, /testimonials, /contact, /roi
// + nouvelles pages: /integrations, /multistore, /security, /customer-portal, /vs-repairdesk
switch ($cleanPath) {
    case '':
    case '/':
    case '/accueil':
        $page = 'home';
        break;
    case '/features':
    case '/fonctionnalites':
        $page = 'features';
        break;
    case '/pricing':
    case '/tarifs':
        $page = 'pricing';
        break;
        $meta['title'] = 'Tarifs SERVO – Simple, Transparent, Sans Engagement';
        $meta['description'] = '49€/mois tout inclus. Accès illimité à toutes les fonctionnalités : SMS, IA, Multi-boutiques. Testez gratuitement pendant 30 jours.';
        break;
        
    // --- FEATURES PHASE 1 ---
        
    case '/sms-automatiques':
    case '/sms':
    case '/notifications':
    case '/integrations':
        $page = 'sms-automatiques';
        $meta['title'] = 'SMS Automatiques SAV & Suivi Réparation | SERVO';
        $meta['description'] = 'Automatisez vos notifications clients. Envoyez des SMS automatiques à chaque étape de réparation. Réduisez les appels entrants de 60%.';
        break;
        
    case '/missions-gamification':
    case '/missions':
        $page = 'missions-gamification';
        $meta['title'] = 'Gamification Atelier – Motivez vos techniciens | SERVO';
        $meta['description'] = 'Transformez le travail en jeu. Assignez des missions, offrez des récompenses et boostez la productivité de votre équipe avec notre module de gamification.';
        break;
        
    case '/pointage-employes':
    case '/pointage':
        $page = 'pointage-employes';
        $meta['title'] = 'Pointage Horaire QR Code & Gestion RH | SERVO';
        $meta['description'] = 'Suivi des heures simplifié par QR Code ou WiFi. Gérez les plannings, retards et la productivité de vos employés en temps réel.';
        break;
        
    case '/catalogue-fournisseurs':
    case '/catalogue':
        $page = 'catalogue-fournisseurs';
        $meta['title'] = 'Catalogue Multi-Fournisseurs Intégré | SERVO';
        $meta['description'] = 'Accédez aux stocks de Mobilax, SOSav, et 10+ autres fournisseurs directement depuis votre interface. Comparez les prix et commandez en 1 clic.';
        break;
        
    case '/base-connaissances-ia':
    case '/base-connaissances':
    case '/kbase':
        $page = 'base-connaissances-ia';
        $meta['title'] = 'Base de Connaissances IA pour Réparateurs | SERVO';
        $meta['description'] = 'Un assistant technique IA qui répond à vos questions. Stockez vos procédures internes et accédez-y instantanément grâce à l\'intelligence artificielle.';
        break;
        
    case '/rachat-conformite':
    case '/rachat':
        $page = 'rachat-conformite';
        $meta['title'] = 'Logiciel Rachat Cash & Livre de Police | SERVO';
        $meta['description'] = 'Sécurisez vos rachats d\'occasion. Édition automatique des contrats de cession, photos d\'identité et conformité légale (Livre de Police) garantie.';
        break;
        
    case '/analytics-kpi':
    case '/analytics':
        $page = 'analytics-kpi';
        $meta['title'] = 'Tableau de Bord & KPI pour Atelier | SERVO';
        $meta['description'] = 'Suivez votre CA, marge, et performance technicien en temps réel. Des graphiques clairs pour prendre les bonnes décisions et développer votre activité.';
        break;
        
    case '/telephonie-voip':
    case '/telephonie':
    case '/voip':
        $page = 'telephonie-voip';
        $meta['title'] = 'Téléphonie VoIP Intégrée | SERVO';
        $meta['description'] = 'Appels en 1 clic depuis vos fiches clients. Enregistrement des appels, pop-up d\'appel entrant et historique illimité.';
        break;

    // --- NOUVELLES PAGES ÉCOSYSTÈME ---

    case '/changelog':
        $page = 'changelog';
        $meta['title'] = 'Historique des Mises à Jour & Nouveautés | SERVO';
        $meta['description'] = 'Suivez l\'évolution de SERVO. Découvrez les dernières fonctionnalités, corrections de bugs et améliorations déployées chaque semaine.';
        break;

    case '/roadmap':
        $page = 'roadmap';
        $meta['title'] = 'Roadmap Produit & Fonctionnalités à Venir | SERVO';
        $meta['description'] = 'Explorez le futur de SERVO. Votez pour les prochaines fonctionnalités et voyez ce que notre équipe prépare pour votre atelier.';
        break;

    case '/blog':
        $page = 'blog';
        $meta['title'] = 'Blog & Conseils pour Réparateurs | SERVO';
        $meta['description'] = 'Articles, guides et astuces pour développer votre activité de réparation, optimiser votre gestion et suivre l\'actualité du marché.';
        break;

    case '/status':
        $page = 'status';
        $meta['title'] = 'État des Services & Uptime | SERVO';
        $meta['description'] = 'Vérifiez en temps réel l\'état de disponibilité de nos services API, Dashboard, et serveurs de base de données.';
        break;

    case '/help':
    case '/support':
        $page = 'help';
        $meta['title'] = 'Centre d\'Aide & Support | SERVO';
        $meta['description'] = 'Trouvez des réponses à vos questions. Tutoriels, guides de démarrage et documentation complète pour maîtriser SERVO.';
        break;

    case '/api':
    case '/developers':
        $page = 'api';
        $meta['title'] = 'Documentation API & Développeurs | SERVO';
        $meta['description'] = 'Intégrez SERVO à vos outils. Documentation technique complète de notre API REST pour les développeurs et intégrateurs.';
        break;

    // --- SPRINT 5: AUTHORITY & TRUST ---

    case '/security':
    case '/trust':
    case '/conformite':
    case '/gdpr':
        $page = 'security';
        $meta['title'] = 'Centre de Confiance & Sécurité | SERVO';
        $meta['description'] = 'Sécurité de niveau bancaire, conformité RGPD et redondance des données. Découvrez comment nous protégeons votre activité.';
        break;

    case '/customers':
    case '/clients':
    case '/case-studies':
        $page = 'customers';
        $meta['title'] = 'Ils nous font confiance | SERVO';
        $meta['description'] = 'Découvrez comment des centaines de réparateurs ont boosté leur CA avec SERVO. Études de cas et témoignages clients.';
        break;

    case '/about':
    case '/equipe':
    case '/team':
    case '/careers':
    case '/jobs':
        $page = 'about';
        $meta['title'] = 'À Propos de Nous & Carrières | SERVO';
        $meta['description'] = 'Rencontrez l\'équipe derrière SERVO. Notre mission : révolutionner le marché de la réparation. On recrute !';
        break;
        
    case '/enterprise':
    case '/grands-comptes':
        $page = 'enterprise';
        $meta['title'] = 'Offre Enterprise & Grands Comptes | SERVO';
        $meta['description'] = 'Solutions sur mesure pour réseaux de franchises et centres de services. SSO, SLA, API dédiée et Account Manager.';
        break;
        
    // --- FEATURES PHASE 1.5 ---
        
    case '/campagnes-sms-marketing':
    case '/campagnes-sms':
    case '/sms-marketing':
        $page = 'campagnes-sms-marketing';
        $meta['title'] = 'Logiciel SMS Marketing pour Réparateurs | SERVO';
        $meta['description'] = 'Créez des campagnes SMS ciblées pour fidéliser vos clients. Promos, relances, anniversaires : boostez votre chiffre d\'affaires simplement.';
        break;
        
    case '/gestion-fournisseurs':
    case '/fournisseurs':
        $page = 'gestion-fournisseurs';
        $meta['title'] = 'Gestion Fournisseurs & Achats Centralisés | SERVO';
        $meta['description'] = 'Centralisez tous vos fournisseurs. Suivez vos commandes de pièces, gérez les avoirs et les retours (RMA) depuis une interface unique.';
        break;
        
    case '/gestion-taches':
    case '/taches':
        $page = 'gestion-taches';
        $meta['title'] = 'Gestion de Tâches Collaboratives Atelier | SERVO';
        $meta['description'] = 'Un Trello intégré à votre logiciel de caisse. Assignez des tâches, suivez l\'avancement et ne laissez rien passer. Organisation parfaite.';
        break;
        
    case '/messagerie-interne':
    case '/messagerie':
    case '/chat':
        $page = 'messagerie-interne';
        $meta['title'] = 'Chat Interne Sécurisé pour Équipes | SERVO';
        $meta['description'] = 'Communiquez avec vos techniciens instantanément. Finis les groupes WhatsApp perso. Une messagerie pro, sécurisée et intégrée à votre outil.';
        break;
        
    case '/commandes-pieces':
    case '/commandes':
        $page = 'commandes-pieces';
        $meta['title'] = 'Gestion Commandes Pièces Détachées | SERVO';
        $meta['description'] = 'Workflow complet de commande : de la demande technicien à la réception en stock. Évitez les erreurs et les ruptures de stock.';
        break;
        
    // --- COMPARISON ---
        
    case '/vs-repairdesk':
    case '/alternative-repairdesk':
        $page = 'vs-repairdesk';
        $meta['title'] = 'SERVO vs RepairDesk : La Meilleure Alternative 2025';
        $meta['description'] = 'Pourquoi payer 99€ ? SERVO offre une interface plus moderne, une IA intégrée et un support français pour 49€. Comparez et migrez gratuitement.';
        break;

    case '/multistore':
        $page = 'multistore';
        $meta['title'] = 'Logiciel Multi-Boutiques & Franchise | SERVO';
        $meta['description'] = 'Pilotez votre réseau de magasins. Stocks unifiés, transferts inter-boutiques, reporting consolidé. La solution scalable pour les franchises.';
        break;
        
    case '/blog':
        $page = 'blog';
        $meta['title'] = 'Blog : Conseils pour Réparateurs & Gérants | SERVO';
        break;
        
    case '/contact':
        $page = 'contact';
        $meta['title'] = 'Contactez l\'équipe SERVO | Support & Commercial';
        break;

    case '/testimonials':
    case '/temoignages':
        $page = 'testimonials';
        $meta['title'] = 'Témoignages Clients SERVO – Ils nous font confiance';
        $meta['description'] = 'Découvrez ce que nos clients disent de SERVO. Des réparateurs satisfaits qui ont transformé leur gestion d\'atelier grâce à notre logiciel.';
        break;

    case '/roi':
    case '/calculator':
    case '/calculateur':
        $page = 'roi';
        $meta['title'] = 'Calculateur de ROI – Évaluez vos gains avec SERVO';
        $meta['description'] = 'Estimez les économies et les gains de productivité que SERVO peut apporter à votre atelier. Calculez votre retour sur investissement.';
        break;

    case '/security':
        $page = 'security';
        $meta['title'] = 'Sécurité des Données – Vos informations sont protégées';
        $meta['description'] = 'Découvrez nos mesures de sécurité avancées pour protéger vos données et celles de vos clients. Conformité RGPD garantie.';
        break;

    case '/customer-portal':
        $page = 'customer-portal';
        $meta['title'] = 'Portail Client – Suivi de Réparation en Ligne';
        $meta['description'] = 'Offrez à vos clients un portail dédié pour suivre l\'état de leurs réparations en temps réel. Améliorez leur expérience et réduisez les appels.';
        break;

    case '/cgv':
    case '/conditions-generales-vente':
        $page = 'cgv';
        $meta['title'] = 'Conditions Générales de Vente (CGV) | SERVO';
        $meta['description'] = 'Consultez nos Conditions Générales de Vente pour l\'utilisation de la plateforme SERVO.';
        break;
    case '/cgu':
    case '/conditions-generales-utilisation':
    case '/terms':
        $page = 'cgu';
        $meta['title'] = 'Conditions Générales d\'Utilisation (CGU) | SERVO';
        $meta['description'] = 'Consultez nos Conditions Générales d\'Utilisation pour la plateforme SERVO.';
        break;
    case '/privacy':
    case '/confidentialite':
    case '/politique-confidentialite':
        $page = 'privacy';
        $meta['title'] = 'Politique de Confidentialité | SERVO';
        $meta['description'] = 'Découvrez comment SERVO protège vos données personnelles et respecte votre vie privée.';
        break;
    case '/cookies':
        $page = 'cookies';
        break;
    case '/mentions-legales':
    case '/legal':
        $page = 'mentions-legales';
        break;
    case '/inscription':
        // Redirection HTTP vers le fichier d'inscription principal
        header('Location: /inscription.php');
        exit;
        break;
    default:
        $page = 'home';
        break;
}

// Charger les traductions spécifiques à la page
loadPageTranslations($page);

// Inclure le layout
require __DIR__ . '/shared/header.php';

// Inclure la page
$pageFile = __DIR__ . '/pages/' . $page . '.php';
if (file_exists($pageFile)) {
    require $pageFile;
} else {
    require __DIR__ . '/pages/home.php';
}

require __DIR__ . '/shared/footer.php';
?>


