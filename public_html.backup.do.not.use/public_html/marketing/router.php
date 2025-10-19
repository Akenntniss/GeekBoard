<?php
// Routeur marketing pour mdgeek.top
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
    case '/testimonials':
    case '/temoignages':
        $page = 'testimonials';
        break;
    case '/contact':
    case '/demo':
        $page = 'contact';
        break;
    case '/roi':
    case '/calculator':
    case '/calculateur':
        $page = 'roi';
        break;
    case '/integrations':
        $page = 'integrations';
        break;
    case '/multistore':
        $page = 'multistore';
        break;
    case '/security':
        $page = 'security';
        break;
    case '/customer-portal':
        $page = 'customer-portal';
        break;
    case '/vs-repairdesk':
        $page = 'vs-repairdesk';
        break;
    case '/cgv':
    case '/conditions-generales-vente':
        $page = 'cgv';
        break;
    case '/cgu':
    case '/conditions-generales-utilisation':
    case '/terms':
        $page = 'cgu';
        break;
    case '/privacy':
    case '/confidentialite':
    case '/politique-confidentialite':
        $page = 'privacy';
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


