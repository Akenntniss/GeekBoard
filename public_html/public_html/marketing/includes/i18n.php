<?php
/**
 * Système d'internationalisation pour GeekBoard Marketing
 * Gestion des langues : FR, EN, ES, DE, IT
 */

class MarketingI18n {
    private static $instance = null;
    private $currentLanguage = 'fr';
    private $translations = [];
    private $supportedLanguages = [
        'fr' => [
            'name' => 'Français',
            'flag' => '🇫🇷',
            'code' => 'fr-FR',
            'prefix' => ''
        ],
        'en' => [
            'name' => 'English',
            'flag' => '🇬🇧',
            'code' => 'en-US',
            'prefix' => '/en'
        ],
        'es' => [
            'name' => 'Español',
            'flag' => '🇪🇸',
            'code' => 'es-ES',
            'prefix' => '/es'
        ],
        'de' => [
            'name' => 'Deutsch',
            'flag' => '🇩🇪',
            'code' => 'de-DE',
            'prefix' => '/de'
        ],
        'it' => [
            'name' => 'Italiano',
            'flag' => '🇮🇹',
            'code' => 'it-IT',
            'prefix' => '/it'
        ]
    ];

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->detectLanguage();
        $this->loadTranslations();
    }

    /**
     * Détecte la langue à partir de l'URL ou paramètre
     */
    private function detectLanguage() {
        // 1. Vérifier le paramètre GET lang
        if (isset($_GET['lang']) && array_key_exists($_GET['lang'], $this->supportedLanguages)) {
            $this->currentLanguage = $_GET['lang'];
            // Sauvegarder en session
            session_start();
            $_SESSION['marketing_lang'] = $this->currentLanguage;
            return;
        }

        // 2. Vérifier la session
        session_start();
        if (isset($_SESSION['marketing_lang']) && array_key_exists($_SESSION['marketing_lang'], $this->supportedLanguages)) {
            $this->currentLanguage = $_SESSION['marketing_lang'];
            return;
        }

        // 3. Détecter depuis l'URL (prefixe de langue)
        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($requestUri, PHP_URL_PATH) ?? '/';
        
        // Vérifier si l'URL commence par un préfixe de langue
        if (preg_match('/^\/([a-z]{2})(?:\/.*)?$/', $path, $matches)) {
            $langCode = $matches[1];
            if (array_key_exists($langCode, $this->supportedLanguages)) {
                $this->currentLanguage = $langCode;
            }
        } else {
            // 4. Détecter depuis l'en-tête Accept-Language du navigateur
            $browserLang = $this->detectBrowserLanguage();
            if ($browserLang && array_key_exists($browserLang, $this->supportedLanguages)) {
                $this->currentLanguage = $browserLang;
            }
        }

        // Sauvegarder en session
        $_SESSION['marketing_lang'] = $this->currentLanguage;
    }

    /**
     * Détecte la langue préférée du navigateur
     */
    private function detectBrowserLanguage() {
        if (!isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            return null;
        }

        $acceptLanguage = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
        $languages = explode(',', $acceptLanguage);
        
        foreach ($languages as $lang) {
            $lang = trim(explode(';', $lang)[0]);
            $lang = strtolower(substr($lang, 0, 2));
            
            if (array_key_exists($lang, $this->supportedLanguages)) {
                return $lang;
            }
        }

        return null;
    }

    /**
     * Charge les traductions pour la langue actuelle
     */
    private function loadTranslations() {
        $langDir = __DIR__ . '/../languages/' . $this->currentLanguage;
        
        // Charger les traductions communes
        $commonFile = $langDir . '/common.php';
        if (file_exists($commonFile)) {
            $this->translations = array_merge($this->translations, require $commonFile);
        }

        // Possibilité d'ajouter d'autres fichiers (pages spécifiques, etc.)
    }

    /**
     * Charge les traductions pour une page spécifique
     */
    public function loadPageTranslations($page) {
        $langDir = __DIR__ . '/../languages/' . $this->currentLanguage;
        $pageFile = $langDir . '/' . $page . '.php';
        
        if (file_exists($pageFile)) {
            $pageTranslations = require $pageFile;
            $this->translations = array_merge($this->translations, $pageTranslations);
        }
    }

    /**
     * Récupère une traduction
     */
    public function t($key, $default = null) {
        return $this->translations[$key] ?? $default ?? $key;
    }

    /**
     * Récupère la langue actuelle
     */
    public function getCurrentLanguage() {
        return $this->currentLanguage;
    }

    /**
     * Récupère les langues supportées
     */
    public function getSupportedLanguages() {
        return $this->supportedLanguages;
    }

    /**
     * Récupère les informations de la langue actuelle
     */
    public function getCurrentLanguageInfo() {
        return $this->supportedLanguages[$this->currentLanguage];
    }

    /**
     * Génère une URL pour une langue donnée
     */
    public function getLanguageUrl($lang, $currentPath = '') {
        if (!array_key_exists($lang, $this->supportedLanguages)) {
            return '#';
        }

        $langInfo = $this->supportedLanguages[$lang];
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'] ?? 'servo.tools';
        
        // Nettoyer le currentPath des préfixes de langue existants
        $cleanPath = $currentPath;
        if (preg_match('/^\/[a-z]{2}(\/.*)?$/', $currentPath, $matches)) {
            $cleanPath = $matches[1] ?? '/';
        }
        
        // Pour le français (défaut), pas de préfixe
        if ($lang === 'fr') {
            return $protocol . $host . $cleanPath;
        }
        
        // Pour les autres langues, ajouter le préfixe
        return $protocol . $host . $langInfo['prefix'] . $cleanPath;
    }

    /**
     * Génère le sélecteur de langue HTML
     */
    public function renderLanguageSelector($currentPath = '') {
        $currentPath = $_SERVER['REQUEST_URI'] ?? '/';
        $currentLang = $this->currentLanguage;
        
        $html = '<div class="language-selector dropdown">';
        $html .= '<button class="btn btn-outline-light dropdown-toggle" type="button" data-bs-toggle="dropdown">';
        $html .= $this->supportedLanguages[$currentLang]['flag'] . ' ' . strtoupper($currentLang);
        $html .= '</button>';
        $html .= '<ul class="dropdown-menu">';
        
        foreach ($this->supportedLanguages as $code => $info) {
            $isActive = $code === $currentLang;
            $activeClass = $isActive ? ' active' : '';
            $url = $this->getLanguageUrl($code, $currentPath);
            
            // Forcer le rechargement de la page en ajoutant un timestamp ou en utilisant window.location
            $html .= '<li><a class="dropdown-item' . $activeClass . '" href="' . htmlspecialchars($url) . '" data-lang="' . $code . '">';
            $html .= $info['flag'] . ' ' . strtoupper($code);
            $html .= '</a></li>';
        }
        
        $html .= '</ul>';
        $html .= '</div>';
        
        return $html;
    }
}

// Fonction helper globale
function t($key, $default = null) {
    return MarketingI18n::getInstance()->t($key, $default);
}

function getCurrentLanguage() {
    return MarketingI18n::getInstance()->getCurrentLanguage();
}

function getSupportedLanguages() {
    return MarketingI18n::getInstance()->getSupportedLanguages();
}

function renderLanguageSelector($currentPath = '') {
    return MarketingI18n::getInstance()->renderLanguageSelector($currentPath);
}

function loadPageTranslations($page) {
    return MarketingI18n::getInstance()->loadPageTranslations($page);
}

// Initialiser le système
$i18n = MarketingI18n::getInstance();
?>
