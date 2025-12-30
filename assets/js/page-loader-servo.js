/**
 * Page Loader SERVO - Overlay au chargement (autonome & robuste)
 * - Gère les doublons (si header.php a déjà créé un loader)
 * - Nettoie TOUS les loaders présents
 */
(function () {
    const OVERLAY_ID = 'pageLoader';
    const FADE_OUT_MS = 400;
    const MAX_WAIT_MS = 2500;

    function hideOverlay() {
        // Cibler TOUS les loaders potentiels (ID ou classe)
        const loaders = document.querySelectorAll(`#${OVERLAY_ID}, .servo-loader-overlay`);

        loaders.forEach(overlay => {
            if (!overlay) return;
            overlay.classList.add('is-hidden');
            setTimeout(() => {
                if (overlay.parentNode) overlay.remove();
            }, FADE_OUT_MS + 50);
        });
    }

    // Fonction pour injecter le style si nécessaire
    function ensureStyle() {
        if (document.getElementById('servo-loader-style')) return;
        const style = document.createElement('style');
        style.id = 'servo-loader-style';
        style.textContent = `
    #${OVERLAY_ID} {
      position: fixed !important;
      inset: 0 !important;
      display: flex !important;
      align-items: center !important;
      justify-content: center !important;
      z-index: 999999 !important;
      background: rgba(255, 255, 255, 0.85) !important;
      backdrop-filter: blur(8px) !important;
      -webkit-backdrop-filter: blur(8px) !important;
      transition: opacity ${FADE_OUT_MS}ms ease !important;
      opacity: 1 !important;
    }
    body.night-mode #${OVERLAY_ID}, .night-mode #${OVERLAY_ID} {
      background: rgba(5, 12, 24, 0.88) !important;
    }
    #${OVERLAY_ID}.is-hidden { opacity: 0 !important; pointer-events: none !important; }
    `;
        document.head.appendChild(style);
    }

    // Initialisation
    function init() {
        // Si aucun loader n'existe, on en crée un (mais header.php devrait déjà l'avoir fait)
        // On évite de créer un doublon si un existe déjà
        if (!document.getElementById(OVERLAY_ID)) {
            // On ne fait rien, on suppose que si le loader n'est pas là, c'est qu'on n'en veut pas
            // ou qu'il a déjà été supprimé.
        }

        // On s'assure que le nettoyage se lance
        if (document.readyState === 'complete') {
            hideOverlay();
        } else {
            window.addEventListener('load', hideOverlay);
            setTimeout(hideOverlay, MAX_WAIT_MS); // Fallback
        }
    }

    init();

    // Sécurité supplémentaire : vérifier périodiquement s'il reste des loaders bloqués
    setTimeout(hideOverlay, 1000);
    setTimeout(hideOverlay, 3000);
    setTimeout(hideOverlay, 5000);

})();
