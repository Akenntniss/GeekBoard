<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/session_cleanup.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta charset="UTF-8">
    <meta name="description" content="Application de gestion des réparations pour appareils électroniques">
    <meta name="theme-color" content="#0078e8">
    
    <?php
    // Déterminer le bon chemin selon l'emplacement du fichier
    $assets_path = (strpos($_SERVER['SCRIPT_NAME'], '/pages/') !== false) ? '../assets/' : 'assets/';
    $favicon_path = (strpos($_SERVER['SCRIPT_NAME'], '/pages/') !== false) ? '../' : '';
    ?>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?php echo $assets_path; ?>images/logo/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="16x16" href="<?php echo $assets_path; ?>images/logo/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo $assets_path; ?>images/logo/apple-touch-icon.png">
    
    <!-- Police Orbitron pour le mode nuit futuriste -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- iOS PWA Meta Tags -->
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="GeekBoard">
    <link rel="apple-touch-icon" href="<?php echo $assets_path; ?>images/logo/apple-touch-icon.png">
    <link rel="apple-touch-icon" sizes="152x152" href="<?php echo $assets_path; ?>images/logo/apple-touch-icon.png">
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo $assets_path; ?>images/logo/apple-touch-icon.png">
    <link rel="apple-touch-icon" sizes="167x167" href="<?php echo $assets_path; ?>images/logo/apple-touch-icon.png">
    
    <!-- iOS Splash Screens -->
    <!-- iPhone X (1125px x 2436px) -->
    <link rel="apple-touch-startup-image" href="<?php echo $assets_path; ?>images/pwa-icons/splash-1125x2436.png" media="(device-width: 375px) and (device-height: 812px) and (-webkit-device-pixel-ratio: 3)">
    <!-- iPhone 8, 7, 6s, 6 (750px x 1334px) -->
    <link rel="apple-touch-startup-image" href="<?php echo $assets_path; ?>images/pwa-icons/splash-750x1334.png" media="(device-width: 375px) and (device-height: 667px) and (-webkit-device-pixel-ratio: 2)">
    <!-- iPhone 8 Plus, 7 Plus, 6s Plus, 6 Plus (1242px x 2208px) -->
    <link rel="apple-touch-startup-image" href="<?php echo $assets_path; ?>images/pwa-icons/splash-1242x2208.png" media="(device-width: 414px) and (device-height: 736px) and (-webkit-device-pixel-ratio: 3)">
    <!-- iPhone 12 Pro Max (1284px x 2778px) -->
    <link rel="apple-touch-startup-image" href="<?php echo $assets_path; ?>images/pwa-icons/splash-1284x2778.png" media="(device-width: 428px) and (device-height: 926px) and (-webkit-device-pixel-ratio: 3)">
    <!-- iPhone 14 Pro, iPhone 13 Pro (1170px x 2532px) -->
    <link rel="apple-touch-startup-image" href="<?php echo $assets_path; ?>images/pwa-icons/splash-1170x2532.png" media="(device-width: 390px) and (device-height: 844px) and (-webkit-device-pixel-ratio: 3)">
    
    <title>MD Geek - Gestion des Réparations</title>
    
    <!-- Loader SERVO en tout premier: style inline + markup inline -->
    <style id="pageLoaderInlineStyle">
    #pageLoader{position:fixed;inset:0;display:flex;align-items:center;justify-content:center;z-index:999999;background:rgba(255,255,255,.85);backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px);transition:opacity .4s ease;opacity:1}
    body.night-mode #pageLoader,.night-mode #pageLoader{background:rgba(5,12,24,.88)}
    #pageLoader.is-hidden{opacity:0;pointer-events:none}
    #pageLoader .servo-loader{position:relative;width:120px;height:120px;display:flex;align-items:center;justify-content:center}
    #pageLoader .loader-circle{position:absolute;inset:0;border-radius:50%;border:3px solid rgba(59,130,246,.25);border-top-color:#3b82f6;animation:plSpin 1s linear infinite;box-shadow:0 0 20px rgba(59,130,246,.25)}
    body.night-mode #pageLoader .loader-circle,.night-mode #pageLoader .loader-circle{border:3px solid rgba(0,212,255,.2);border-top-color:#00d4ff;box-shadow:0 0 24px rgba(0,212,255,.25)}
    #pageLoader .loader-text{position:relative;display:flex;gap:6px;font-family:'Orbitron',system-ui,sans-serif;font-weight:700;letter-spacing:.08em}
    #pageLoader .loader-letter{font-size:18px;color:#1f2937;opacity:.4;animation:plPulse 1.2s ease-in-out infinite}
    #pageLoader .loader-letter:nth-child(2){animation-delay:.1s}#pageLoader .loader-letter:nth-child(3){animation-delay:.2s}#pageLoader .loader-letter:nth-child(4){animation-delay:.3s}#pageLoader .loader-letter:nth-child(5){animation-delay:.4s}
    body.night-mode #pageLoader .loader-letter,.night-mode #pageLoader .loader-letter{color:#e2e8f0;text-shadow:0 0 8px rgba(0,212,255,.45)}
    @keyframes plSpin{to{transform:rotate(360deg)}}
    @keyframes plPulse{0%,100%{opacity:.4;transform:translateY(0)}20%{opacity:1;text-shadow:0 0 6px currentColor}40%{opacity:.75;transform:translateY(0)}}
    </style>
    <script>document.write('<div id="pageLoader" aria-hidden="true"><div class="servo-loader"><div class="loader-circle"></div><div class="loader-text"><span class="loader-letter">S<\/span><span class="loader-letter">E<\/span><span class="loader-letter">R<\/span><span class="loader-letter">V<\/span><span class="loader-letter">O<\/span><\/div><\/div><\/div>');</script>
    <script>
      (function(){
        var OVERLAY_ID='pageLoader';
        function hide(){var el=document.getElementById(OVERLAY_ID); if(!el) return; el.classList.add('is-hidden'); setTimeout(function(){ if(el && el.parentNode){ el.parentNode.removeChild(el);} }, 420);} 
        // Disparition au load (immédiate)
        window.addEventListener('load', function(){ hide(); });
        // Fallback sécurité (1.5s max)
        setTimeout(hide, 1500);
      })();
    </script>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome pour les icônes -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Google Fonts - Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- jQuery d'abord, puis toastr -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Scanner Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/quagga@0.12.1/dist/quagga.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.js"></script>
    <script src="https://unpkg.com/@zxing/library@latest/umd/index.min.js"></script>
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    
    <!-- Scanner Libraries - Lazy Load jsQR and Quagga for universal scanner -->
    <script>
    window.loadScannerLibraries = function(callback) {
        console.log('📚 Chargement des bibliothèques scanner...');
        if (typeof jsQR !== 'undefined' && typeof Quagga !== 'undefined') {
            console.log('✅ Bibliothèques déjà chargées');
            if (callback) callback();
            return;
        }
        let jsQRLoaded = typeof jsQR !== 'undefined';
        let quaggaLoaded = typeof Quagga !== 'undefined';
        const checkComplete = () => {
            if (jsQRLoaded && quaggaLoaded) {
                console.log('✅ Toutes les bibliothèques scanner sont chargées');
                if (callback) callback();
            }
        };
        if (!jsQRLoaded) {
            const jsQRScript = document.createElement('script');
            jsQRScript.src = 'https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.js';
            jsQRScript.async = true;
            jsQRScript.onload = () => { console.log('✅ jsQR chargé'); jsQRLoaded = true; checkComplete(); };
            jsQRScript.onerror = () => { console.error('❌ Erreur jsQR'); jsQRLoaded = true; checkComplete(); };
            document.head.appendChild(jsQRScript);
        }
        if (!quaggaLoaded) {
            const quaggaScript = document.createElement('script');
            quaggaScript.src = 'https://cdn.jsdelivr.net/npm/quagga@0.12.1/dist/quagga.min.js';
            quaggaScript.async = true;
            quaggaScript.onload = () => { console.log('✅ Quagga chargé'); quaggaLoaded = true; checkComplete(); };
            quaggaScript.onerror = () => { console.error('❌ Erreur Quagga'); quaggaLoaded = true; checkComplete(); };
            document.head.appendChild(quaggaScript);
        }
        if (jsQRLoaded || quaggaLoaded) checkComplete();
    };
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => setTimeout(() => window.loadScannerLibraries(), 2000));
    } else {
        setTimeout(() => window.loadScannerLibraries(), 2000);
    }
    </script>
    
    <!-- Pull to refresh (mobile & iPad) -->
    <script src="<?php echo $assets_path; ?>js/pull-to-refresh.js?v=<?php echo time(); ?>" defer></script>
    
    <!-- Configuration Bootstrap pour éviter les erreurs de modal -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Configuration améliorée pour tous les modals Bootstrap
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            console.log('🚀 Initialisation des modals Bootstrap...');
            
            // Configuration sécurisée par défaut
            const modalDefaults = {
                backdrop: true,
                keyboard: true,
                focus: true
            };
            
            // Attendre que tous les éléments soient chargés
            setTimeout(function() {
                // Initialiser tous les modals avec gestion d'erreur améliorée
                document.querySelectorAll('.modal').forEach(function(modalEl) {
                    if (!modalEl.hasAttribute('data-bs-initialized')) {
                        try {
                            // Vérifier que l'élément modal est valide
                            if (modalEl && modalEl.id) {
                                const modalInstance = new bootstrap.Modal(modalEl, modalDefaults);
                                modalEl.setAttribute('data-bs-initialized', 'true');
                                console.log(`✅ Modal initialisé: ${modalEl.id}`);
                            }
                        } catch (e) {
                            console.warn(`⚠️ Erreur initialisation modal ${modalEl.id}:`, e);
                            
                            // Tentative de réinitialisation avec paramètres minimaux
                            try {
                                new bootstrap.Modal(modalEl, { backdrop: 'static' });
                                modalEl.setAttribute('data-bs-initialized', 'true');
                                console.log(`✅ Modal réinitialisé (mode statique): ${modalEl.id}`);
                            } catch (e2) {
                                console.error(`❌ Impossible d'initialiser le modal ${modalEl.id}:`, e2);
                            }
                        }
                    }
                });
                
                console.log('✅ Initialisation des modals terminée');
            }, 100); // Délai court pour s'assurer que le DOM est complètement chargé
        } else {
            console.error('❌ Bootstrap Modal non disponible');
        }
    });
    </script>
    <!-- Toastr CSS et JS pour les notifications -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    
    <!-- Configuration de Toastr -->
    <script>
        // La configuration sera initialisée après le chargement complet de la page
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof toastr !== 'undefined') {
                toastr.options = {
                    "closeButton": true,
                    "debug": false,
                    "newestOnTop": true,
                    "progressBar": true,
                    "positionClass": "toast-top-right",
                    "preventDuplicates": false,
                    "onclick": null,
                    "showDuration": "300",
                    "hideDuration": "1000",
                    "timeOut": "5000",
                    "extendedTimeOut": "1000",
                    "showEasing": "swing",
                    "hideEasing": "linear",
                    "showMethod": "fadeIn",
                    "hideMethod": "fadeOut"
                };
            }
        });
    </script>
    
    <!-- SYSTÈME DE TEST CSS - Désactiver via URL: ?disable_css=nom1,nom2,nom3 -->
    <?php 
    $disabled_css = isset($_GET['disable_css']) ? explode(',', $_GET['disable_css']) : [];
    $cache_bust = time() . '_' . mt_rand(1000, 9999);
    $css_files = [
        'professional-desktop' => 'css/professional-desktop.css',
        'modern-effects' => 'css/modern-effects.css', 
        'tablet-friendly' => 'css/tablet-friendly.css',
        'responsive' => 'css/responsive.css',
        'navbar' => 'css/navbar.css',
        'navbar-clean' => 'css/navbar-clean.css',
        'header-day-mode' => 'css/header-day-mode.css',
        'launchpad-enhanced' => 'css/launchpad-enhanced.css',
        'status-colors' => 'css/status-colors.css',
        'status-colors-fix' => 'css/status-colors-fix.css',
        'rachat-styles' => 'css/rachat-styles.css',
        'pwa-enhancements' => 'css/pwa-enhancements.css',
        'ipad-header-fix' => 'css/ipad-header-fix.css',
        'ipad-pwa-fix' => 'css/ipad-pwa-fix.css',
        'ipad-statusbar-fix' => 'css/ipad-statusbar-fix.css',
        
        'nouvelles-actions-modal' => 'css/nouvelles-actions-modal-simple.css',
        'modal-ajoutercommande-fix' => 'css/modal-ajoutercommande-fix.css',
        'modal-stacking-fix' => 'css/modal-stacking-fix.css',
        'modal-sms-fix' => 'css/modal-sms-fix.css',
        'navigation-modal' => 'css/navigation-modal.css',
        'plus-button-improvements' => 'css/plus-button-improvements.css',
        'navbar-buttons-light-mode-fix' => 'css/navbar-buttons-light-mode-fix.css',
        'navbar-buttons-ultimate-fix' => 'css/navbar-buttons-ultimate-fix.css',
        'hamburger-button-fix' => 'css/hamburger-button-fix.css',
        'modal-recherche-day-mode-fix' => 'css/modal-recherche-day-mode-fix.css',
        'geek-navbar-buttons' => 'css/geek-navbar-buttons.css',
        'ipad-navbar-orientation-fix' => 'css/ipad-navbar-orientation-fix.css',
        'ipad-navbar-blur-fix' => 'css/ipad-navbar-blur-fix.css',
        'accueil-navbar-blur-fix' => 'css/accueil-navbar-blur-fix.css',
        'accueil-ipad-buttons-position-fix' => 'css/accueil-ipad-buttons-position-fix.css',
        'navbar-simplified-buttons' => 'css/navbar-simplified-buttons.css',
        'servo-logo-animated' => 'css/servo-logo-animated.css',
        'navbar-day-mode-fix' => 'css/navbar-day-mode-fix.css',
        'servo-logo-force-visibility' => 'css/servo-logo-force-visibility.css',
        'navbar-servo-fix' => 'css/navbar-servo-fix.css',
        'navbar-night-mode' => 'css/navbar-night-mode.css',
        // 'simple-modal' => 'css/simple-modal.css', // désactivé: on conserve Bootstrap futuristicMenuModal
        'modal-navigation-night-mode' => 'css/modal-navigation-night-mode.css',
        'scanner-improvements' => 'css/scanner-improvements.css'
    ];
    
    echo "<!-- CSS ACTIFS: -->\n";
    foreach ($css_files as $name => $path) {
        if (!in_array($name, $disabled_css)) {
            echo "    <link href=\"{$assets_path}{$path}?v={$cache_bust}\" rel=\"stylesheet\">\n";
        } else {
            echo "    <!-- DÉSACTIVÉ: {$name} -->\n";
        }
    }
    ?>
    
    <!-- 🔥 TABLEAUX MASTER - TOUJOURS ACTIF -->
    <link href="<?php echo $assets_path; ?>css/tableaux-master.css?v=<?php echo time() . '_' . mt_rand(1000, 9999); ?>" rel="stylesheet">
    
    
    
    
    <script src="<?php echo $assets_path; ?>js/app.js" defer></script>
    <script src="<?php echo $assets_path; ?>js/modern-interactions.js" defer></script>
    <script src="<?php echo $assets_path; ?>js/offline-sync.js" defer></script>
    <script src="<?php echo $assets_path; ?>js/statusbar-theme.js" defer></script>
    
    <script src="<?php echo $assets_path; ?>js/pwa-notifications.js" defer></script>
    
    <!-- Script pour détecter le mode d'affichage (standalone vs navigateur) -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Récupérer les paramètres de l'URL
            const urlParams = new URLSearchParams(window.location.search);
            const testPwa = urlParams.get('test_pwa') === 'true';
            const testIos = urlParams.get('test_ios') === 'true';
            const testDynamicIsland = urlParams.get('test_dynamic_island') === 'true';
            const isPwa = urlParams.get('pwa') === '1'; // Détection via URL
            
            // Détecter si l'application est en mode standalone (ajoutée à l'écran d'accueil)
            const isInStandaloneMode = () => 
                (window.matchMedia('(display-mode: standalone)').matches) || 
                (window.navigator.standalone) || 
                document.referrer.includes('android-app://') ||
                isPwa;
            
            // Ajouter une classe au body selon le mode d'affichage
            if (isInStandaloneMode() || testPwa) {
                document.body.classList.add('pwa-mode');
                localStorage.setItem('isPwaMode', 'true');
                // Envoyer une requête au serveur pour définir une variable de session
                fetch('set_pwa_mode.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'pwa_mode=true'
                }).catch(error => console.error('Erreur lors de la définition du mode PWA:', error));
                
                // Optimisations spécifiques pour iOS avec Dynamic Island
                if (/iPhone/.test(navigator.userAgent) || testIos) {
                    document.body.classList.add('ios-pwa');
                    
                    // Optimisation spécifique pour les iPhones avec Dynamic Island
                    const hasNotch = (window.screen.height >= 812 && window.screen.width >= 375) || testDynamicIsland;
                    if (hasNotch || testDynamicIsland) {
                        document.body.classList.add('ios-dynamic-island');
                    }
                }
            } else {
                document.body.classList.add('browser-mode');
                localStorage.setItem('isPwaMode', 'false');
                // Envoyer une requête au serveur pour réinitialiser la variable de session
                fetch('set_pwa_mode.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'pwa_mode=false'
                }).catch(error => console.error('Erreur lors de la réinitialisation du mode PWA:', error));
            }
            
            // Gestion de l'installation PWA
            let deferredPrompt;
            const installButton = document.createElement('button');
            installButton.classList.add('btn', 'btn-primary', 'pwa-install-btn');
            installButton.textContent = 'Installer l\'application';
            installButton.style.display = 'none';
            
            // Ajouter le bouton d'installation au DOM
            document.addEventListener('DOMContentLoaded', () => {
                const navbarRight = document.querySelector('.navbar-nav.ml-auto');
                if (navbarRight) {
                    const liElement = document.createElement('li');
                    liElement.classList.add('nav-item');
                    liElement.appendChild(installButton);
                    navbarRight.appendChild(liElement);
                }
            });
            
            // Détecter si l'app peut être installée
            window.addEventListener('beforeinstallprompt', (e) => {
                // Empêcher Chrome de montrer automatiquement l'invite d'installation
                e.preventDefault();
                // Stocker l'événement pour l'utiliser plus tard
                deferredPrompt = e;
                // Afficher le bouton d'installation
                installButton.style.display = 'block';
            });
            
            // Gérer le clic sur le bouton d'installation
            installButton.addEventListener('click', async () => {
                if (deferredPrompt) {
                    // Montrer l'invite d'installation
                    deferredPrompt.prompt();
                    // Attendre que l'utilisateur réponde à l'invite
                    const { outcome } = await deferredPrompt.userChoice;
                    console.log(`Choix d'installation: ${outcome}`);
                    // Réinitialiser la variable
                    deferredPrompt = null;
                    // Cacher le bouton d'installation
                    installButton.style.display = 'none';
                }
            });
            
            // Détecter quand l'application est installée
            window.addEventListener('appinstalled', (evt) => {
                console.log('Application installée !');
                // Cacher le bouton d'installation
                installButton.style.display = 'none';
                // Afficher un message à l'utilisateur
                if (typeof toastr !== 'undefined') {
                    toastr.success('Application installée avec succès !');
                }
            });
            
            // Enregistrement du service worker pour PWA
            if ('serviceWorker' in navigator) {
                // Chercher si un paramètre URL demande de désactiver le service worker
                const disableSW = urlParams.get('disableSW');
                
                if (disableSW === 'true') {
                    // Désactiver le service worker si demandé
                    navigator.serviceWorker.getRegistrations().then(function(registrations) {
                        for(let registration of registrations) {
                            registration.unregister();
                            console.log('Service worker unregistered');
                        }
                    });
                } else {
                    // Enregistrer le service worker avec une mise à jour forcée
                    navigator.serviceWorker.register('/service-worker.js?v=4_modal_fix')
                        .then(registration => {
                            console.log('Service Worker enregistré avec succès:', registration.scope);
                            
                            // FORCER LA MISE À JOUR IMMÉDIATE pour le nouveau modal
                            if (registration.waiting) {
                                console.log('Service Worker en attente détecté, activation forcée...');
                                registration.waiting.postMessage({type: 'SKIP_WAITING'});
                            }
                            
                            // Vérifier les mises à jour du service worker
                            registration.addEventListener('updatefound', () => {
                                const newWorker = registration.installing;
                                newWorker.addEventListener('statechange', () => {
                                    if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                                        // Appliquer automatiquement la mise à jour sans popup
                                        console.log('Service Worker mis à jour, rafraîchissement silencieux disponible');
                                    }
                                });
                            });
                            
                            // Forcer la mise à jour immédiate
                            registration.update();
                        })
                        .catch(error => {
                            console.log('Échec de l\'enregistrement du Service Worker:', error);
                        });
                }
            }
        });
    </script>
    
    <style>
    /* Styles globaux */
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Inter', sans-serif;
        background-color: #f0f2f5;
        min-height: 100vh;
        margin: 0;
        position: relative;
    }
    
    /* Styles pour les applications ajoutées à l'écran d'accueil iOS */
    @supports (-webkit-touch-callout: none) {
        @media (display-mode: standalone) {
            body {
                /* Ajustement pour Dynamic Island */
                padding-left: env(safe-area-inset-left) !important;
                padding-right: env(safe-area-inset-right) !important;
            }
        }
    }
    
    /* Styles spécifiques pour le mode PWA */
    body.pwa-mode {
        overscroll-behavior: none; /* Empêche le rebond sur les appareils iOS */
    }
    
    /* Styles pour iOS en mode PWA */
    body.ios-pwa {
        /* Optimisations spécifiques pour iOS */
        -webkit-user-select: none; /* Désactive la sélection de texte */
        -webkit-touch-callout: none; /* Désactive le menu contextuel sur appui long */
    }
    
    /* Ajustements pour forcer le plein écran en mode PWA iOS */
    @media all and (display-mode: standalone) {
        html {
            height: 100vh;
        }
        
        body.ios-pwa {
            min-height: 100vh;
            min-height: -webkit-fill-available;
        }
    }
    
    /* Correction pour le contenu principal */
    main {
        margin-left: 0 !important;
        width: 100% !important;
        padding-top: 0 !important;
        margin-top: 0 !important;
    }

    /* Main Content - Style amélioré */
    .main-container {
        max-width: 100% !important;
        margin: 0 auto !important;
        padding: 1rem;
        width: 100%;
    }

    /* Contenu centré */
    .content {
        margin: 0 !important;
        padding: 0 !important;
        width: 100%;
    }

    /* Réinitialisation des marges pour le conteneur fluid */
    .container-fluid {
        padding: 0 !important;
        margin: 0 !important;
        width: 100%;
    }

    /* Réinitialisation des marges pour les lignes */
    .row {
        margin: 0 !important;
    }

    /* Force les tableaux du dashboard à s'afficher côte à côte */
    .dashboard-tables-container {
        display: grid !important;
        grid-template-columns: repeat(3, 1fr) !important;
        gap: 1.5rem !important;
        width: 100% !important;
    }

    @media (max-width: 1400px) {
        .dashboard-tables-container {
            grid-template-columns: repeat(2, 1fr) !important;
        }
    }

    @media (max-width: 992px) {
        .dashboard-tables-container {
            grid-template-columns: 1fr !important;
        }
    }

    /* Améliorations pour le format mobile */
    @media (max-width: 767.98px) {
        /* Ajustements du corps pour le mobile */
        body {
            padding-bottom: 0 !important;
            background-color: #f8f9fc;
        }
        
        /* Style pour les cartes sur mobile */
        .card {
            border-radius: 12px;
            border: none;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            margin-bottom: 16px;
            overflow: hidden;
        }

        /* Styles pour les tableaux sur mobile */
        .table-responsive {
            border-radius: 8px;
            overflow: hidden;
        }
        
        .table th {
            white-space: nowrap;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #6c757d;
        }
        
        .table td {
            font-size: 13px;
            padding: 12px 15px;
        }
        
        /* Améliorations pour les formulaires */
        .form-control, .form-select {
            font-size: 16px; /* Taille optimale pour éviter le zoom sur iOS */
            height: 48px;
            border-radius: 8px;
        }
        
        .form-label {
            font-weight: 500;
            margin-bottom: 8px;
            color: #495057;
        }
        
        .btn {
            border-radius: 8px;
            font-weight: 500;
            padding: 10px 16px;
        }
        
        /* Améliorations des styles de notification pour mobile */
        .toast-container {
            bottom: 20px !important;
            right: 16px;
            left: 16px;
            z-index: 1055;
        }
        
        .toast {
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            border: none;
        }
    }
    
    /* Styles d'optimisation pour iPad */
    .ipad-device {
        padding-bottom: 0 !important;
    }
    
    /* Styles pour l'adaptation du dashboard */
    .dashboard-card {
        border-radius: 12px;
        border: none;
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.05);
        overflow: hidden;
        transition: all 0.3s ease;
        height: 100%;
    }
    
    .dashboard-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }
    
    .dashboard-card .card-body {
        padding: 1.5rem;
    }
    
    .dashboard-card .card-title {
        font-weight: 600;
        font-size: 1.1rem;
        margin-bottom: 1rem;
        color: #333;
    }
    
    .dashboard-stat {
        font-size: 2rem;
        font-weight: 700;
        color: #4361ee;
        margin-bottom: 0.5rem;
    }
    
    .dashboard-change {
        font-size: 0.85rem;
        font-weight: 500;
    }
    
    .dashboard-change.positive {
        color: #10b981;
    }
    
    .dashboard-change.negative {
        color: #ef4444;
    }
    
    .dashboard-icon {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* Style pour le conteneur principal de la page */
    .page-container {
        display: flex;
        flex-direction: column;
        min-height: 100%;
        padding-top: 85px;
        max-width: 1400px;
        margin: 0 auto;
        padding-left: 00px;
        padding-right: 00px;
    }
    
    /* Styles pour les boutons de filtres */
    .filter-buttons {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
        justify-content: center;
        margin-bottom: 1rem;
        width: 100%;
        max-width: 1200px;
        margin-left: auto;
        margin-right: auto;
    }
    
    /* Badge pour indiquer les nouveaux éléments */
    .nav-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #ff6b6b 0%, #ff8e8e 100%);
        color: white;
        font-size: 0.7rem;
        font-weight: 600;
        border-radius: 50px;
        padding: 0.15rem 0.4rem;
        margin-left: 0.5rem;
        box-shadow: 0 2px 5px rgba(255, 107, 107, 0.3);
        animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
        0% {
            transform: scale(1);
        }
        50% {
            transform: scale(1.1);
        }
        100% {
            transform: scale(1);
        }
    }
    
    /* Badge pour la page de suivi des réparations */
    .suivi-badge {
        background: linear-gradient(135deg, #0078e8 0%, #37a1ff 100%);
        box-shadow: 0 2px 5px rgba(0, 120, 232, 0.3);
    }
    </style>
    <!-- 🌙 NOUVEAU THÈME SOMBRE FUTURISTE V2 -->
    <link rel="stylesheet" href="<?php echo $assets_path; ?>css/homepage-dark-theme-v2.css">
    <script src="<?php echo $assets_path; ?>js/dark-theme-v2.js" defer></script>
    
    <!-- 💉 INJECTION MODAL COMMANDE FUTURISTE -->
    <script src="<?php echo $assets_path; ?>js/modal-commande-inject.js?v=<?php echo time(); ?>" defer></script>
    
    <!-- 🔧 DÉBOGAGE MODAL -->
    <script src="<?php echo $assets_path; ?>js/modal-debug.js?v=<?php echo time(); ?>" defer></script>
    
    <!-- 🚀 MODAL SIMPLE (FALLBACK) désactivé: on conserve Bootstrap futuristicMenuModal -->
    <!-- <script src="<?php echo $assets_path; ?>js/simple-modal.js?v=<?php echo time(); ?>" defer></script> -->
    
    <!-- 🔧 CORRECTION SCANNER CODES-BARRES -->
    <script src="<?php echo $assets_path; ?>js/barcode-scanner-fix.js?v=<?php echo time(); ?>" defer></script>
    
    <!-- 🎯 DÉTECTEUR SIMPLE CODES-BARRES -->
    <script src="<?php echo $assets_path; ?>js/simple-barcode-detector.js?v=<?php echo time(); ?>" defer></script>
    
    <!-- 🔍 DEBUG VISUEL CODES-BARRES -->
    <script src="<?php echo $assets_path; ?>js/barcode-debug-visual.js?v=<?php echo time(); ?>" defer></script>
    
    <!-- 🧪 TEST FORCÉ CODES-BARRES -->
    <script src="<?php echo $assets_path; ?>js/barcode-force-test.js?v=<?php echo time(); ?>" defer></script>
    
    <!-- 🔍 DÉCODEUR RÉEL CODES-BARRES -->
    <script src="<?php echo $assets_path; ?>js/real-barcode-decoder.js?v=<?php echo time(); ?>" defer></script>
</head>
<body data-page="<?php echo htmlspecialchars($page ?? 'accueil'); ?>">
<?php if (!(isset($_GET['modal']) && $_GET['modal'] === '1')): ?>
<?php include_once __DIR__ . '/../components/navbar_new.php'; ?>
<?php include_once __DIR__ . '/../components/mobile_dock_bar.php'; ?>
<?php include_once __DIR__ . '/../components/voip_incoming_banner.php'; ?>
<?php endif; ?>
</body>
</html>