/**
 * Page Loader SERVO - Overlay au chargement (autonome)
 * - Crée un overlay plein écran avec animation "SERVO"
 * - Se retire automatiquement après l'événement window.load
 * - Fallback: disparaît passé un délai si load tarde
 */
(function () {
  const OVERLAY_ID = 'pageLoader';
  const FADE_OUT_MS = 400; // durée de la disparition
  const MAX_WAIT_MS = 2500; // fallback max si load tarde

  function createStyleElement() {
    const style = document.createElement('style');
    style.setAttribute('data-loader-style', 'servo');
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

    #${OVERLAY_ID} .servo-loader { position: relative; width: 120px; height: 120px; display: flex; align-items: center; justify-content: center; }
    #${OVERLAY_ID} .loader-circle {
      position: absolute; inset: 0; border-radius: 50%;
      border: 3px solid rgba(59, 130, 246, 0.25);
      border-top-color: #3b82f6;
      animation: servoSpin 1s linear infinite;
      box-shadow: 0 0 20px rgba(59, 130, 246, 0.25);
    }
    body.night-mode #${OVERLAY_ID} .loader-circle,
    .night-mode #${OVERLAY_ID} .loader-circle {
      border: 3px solid rgba(0, 212, 255, 0.2); border-top-color: #00d4ff;
      box-shadow: 0 0 24px rgba(0, 212, 255, 0.25);
    }
    #${OVERLAY_ID} .loader-text { position: relative; display: flex; gap: 6px; font-family: 'Orbitron', system-ui, sans-serif; font-weight: 700; letter-spacing: 0.08em; }
    #${OVERLAY_ID} .loader-letter { font-size: 18px; color: #1f2937; opacity: 0.4; animation: servoPulse 1.2s ease-in-out infinite; }
    #${OVERLAY_ID} .loader-letter:nth-child(1) { animation-delay: 0s; }
    #${OVERLAY_ID} .loader-letter:nth-child(2) { animation-delay: 0.1s; }
    #${OVERLAY_ID} .loader-letter:nth-child(3) { animation-delay: 0.2s; }
    #${OVERLAY_ID} .loader-letter:nth-child(4) { animation-delay: 0.3s; }
    #${OVERLAY_ID} .loader-letter:nth-child(5) { animation-delay: 0.4s; }
    body.night-mode #${OVERLAY_ID} .loader-letter,
    .night-mode #${OVERLAY_ID} .loader-letter { color: #e2e8f0; text-shadow: 0 0 8px rgba(0, 212, 255, 0.45); }

    @keyframes servoSpin { to { transform: rotate(360deg); } }
    @keyframes servoPulse {
      0%, 100% { opacity: 0.4; transform: translateY(0); }
      20% { opacity: 1; text-shadow: 0 0 6px currentColor; }
      40% { opacity: 0.75; transform: translateY(0); }
    }
    `;
    document.head.appendChild(style);
  }

  function buildOverlay() {
    if (document.getElementById(OVERLAY_ID)) return null;
    const overlay = document.createElement('div');
    overlay.id = OVERLAY_ID;
    overlay.setAttribute('aria-hidden', 'true');
    overlay.innerHTML = `
      <div class="servo-loader">
        <div class="loader-circle"></div>
        <div class="loader-text">
          <span class="loader-letter">S</span>
          <span class="loader-letter">E</span>
          <span class="loader-letter">R</span>
          <span class="loader-letter">V</span>
          <span class="loader-letter">O</span>
        </div>
      </div>
    `;
    return overlay;
  }

  function showOverlay() {
    createStyleElement();
    const overlay = buildOverlay();
    if (!overlay) return;
    document.body.appendChild(overlay);
  }

  function hideOverlay() {
    const overlay = document.getElementById(OVERLAY_ID);
    if (!overlay) return;
    overlay.classList.add('is-hidden');
    setTimeout(() => { overlay.remove(); }, FADE_OUT_MS + 20);
  }

  // Ne pas re-créer si markup inline déjà présent
  if (!document.getElementById(OVERLAY_ID)) {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', showOverlay);
    } else {
      showOverlay();
    }
  }

  // Retirer après le chargement complet
  window.addEventListener('load', () => {
    setTimeout(hideOverlay, 250); // laisser un petit temps pour l’effet
  });

  // Fallback dureté si l'événement load ne survient pas
  setTimeout(hideOverlay, MAX_WAIT_MS);
})();


