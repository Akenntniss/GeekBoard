(function() {
  'use strict';

  const isTouchDevice = ('ontouchstart' in window) || navigator.maxTouchPoints > 0;
  const isMobileOrTablet = () => window.innerWidth <= 1024; // iPhone + iPad

  if (!isTouchDevice) return;

  let startY = 0;
  let isPulling = false;
  let atTop = false;
  let distance = 0;
  let holdTimer = null;
  let holdArmed = false;
  let lastTriggerTs = 0;

  const THRESHOLD_PX = 90;           // distance à tirer avant d'armer
  const HOLD_REQUIRED_MS = 600;      // maintenir au-delà du seuil
  const MAX_PULL_PX = 160;           // limite visuelle
  const MIN_INTERVAL_MS = 15000;     // anti-spam: 15s min entre deux refresh

  // Éviter de perturber l'UX si un modal est ouvert
  function anyModalOpen() {
    return !!document.querySelector('.modal.show');
  }

  // Indicateur visuel discret
  let indicator;
  function ensureIndicator() {
    if (indicator) return indicator;
    indicator = document.createElement('div');
    indicator.id = 'ptr-indicator';
    indicator.setAttribute('aria-hidden', 'true');
    indicator.style.cssText = [
      'position: fixed',
      'top: 0',
      'left: 0',
      'right: 0',
      'height: 64px',
      'display: flex',
      'align-items: center',
      'justify-content: center',
      'transform: translateY(-100%)',
      'transition: transform 180ms ease',
      'z-index: 100000',
      'pointer-events: none',
      'backdrop-filter: blur(8px) saturate(160%)',
      '-webkit-backdrop-filter: blur(8px) saturate(160%)',
      'background: linear-gradient(180deg, rgba(255,255,255,0.85), rgba(255,255,255,0.65))',
      'color: #111827',
      'font-family: Inter, system-ui, -apple-system, sans-serif',
      'font-weight: 600'
    ].join(';');

    const inner = document.createElement('div');
    inner.style.cssText = [
      'display:flex',
      'gap:10px',
      'align-items:center'
    ].join(';');

    const spinner = document.createElement('div');
    spinner.style.cssText = [
      'width:16px','height:16px','border-radius:50%',
      'border:2px solid rgba(59,130,246,0.25)',
      'border-top-color:#3b82f6',
      'animation: ptrSpin 800ms linear infinite'
    ].join(';');

    const label = document.createElement('span');
    label.id = 'ptr-label';
    label.textContent = 'Tirez et maintenez pour actualiser';
    label.style.cssText = 'font-size:13px;letter-spacing:.2px;';

    inner.appendChild(spinner);
    inner.appendChild(label);
    indicator.appendChild(inner);

    // Variante nuit
    const bodyNight = document.body.classList.contains('night-mode');
    if (bodyNight) {
      indicator.style.background = 'linear-gradient(180deg, rgba(15,23,42,0.85), rgba(15,23,42,0.65))';
      indicator.style.color = '#e5e7eb';
    }

    // Keyframes spinner (shadow DOM-less)
    const style = document.createElement('style');
    style.textContent = '@keyframes ptrSpin {to {transform: rotate(360deg)}}';
    document.head.appendChild(style);

    document.body.appendChild(indicator);
    return indicator;
  }

  function updateIndicator(px) {
    ensureIndicator();
    const shown = Math.max(0, Math.min(px, MAX_PULL_PX));
    const translate = (-100 + (shown / MAX_PULL_PX) * 100); // -100% -> 0%
    indicator.style.transform = `translateY(${translate}%)`;
    const label = document.getElementById('ptr-label');
    if (!label) return;
    if (px >= THRESHOLD_PX) {
      label.textContent = holdArmed ? 'Maintenez...' : 'Relâchez pas — maintenez pour actualiser';
    } else {
      label.textContent = 'Tirez et maintenez pour actualiser';
    }
  }

  function hideIndicator() {
    if (!indicator) return;
    indicator.style.transform = 'translateY(-100%)';
  }

  function canTrigger() {
    return (Date.now() - lastTriggerTs) > MIN_INTERVAL_MS;
  }

  function triggerRefresh() {
    if (!canTrigger()) return;
    lastTriggerTs = Date.now();
    try {
      const label = document.getElementById('ptr-label');
      if (label) label.textContent = 'Actualisation...';
    } catch(_) {}
    // Rechargement léger
    window.location.reload();
  }

  function resetState() {
    isPulling = false;
    atTop = false;
    distance = 0;
    holdArmed = false;
    if (holdTimer) { clearTimeout(holdTimer); holdTimer = null; }
    hideIndicator();
  }

  function onTouchStart(e) {
    if (!isMobileOrTablet() || anyModalOpen()) return;
    if (window.scrollY > 0) { atTop = false; return; }
    atTop = true;
    startY = (e.touches ? e.touches[0].clientY : e.clientY) || 0;
    distance = 0;
  }

  function onTouchMove(e) {
    if (!atTop || anyModalOpen() || !isMobileOrTablet()) return;
    const currentY = (e.touches ? e.touches[0].clientY : e.clientY) || 0;
    distance = Math.max(0, currentY - startY);
    if (distance <= 0) return;

    // On empêche le scroll par défaut uniquement quand on tire vers le bas au top
    e.preventDefault();
    isPulling = true;
    updateIndicator(distance);

    if (distance >= THRESHOLD_PX) {
      if (!holdArmed && !holdTimer) {
        holdTimer = setTimeout(function() {
          holdArmed = true; // Le maintien a été suffisant
          triggerRefresh();
        }, HOLD_REQUIRED_MS);
      }
    } else {
      holdArmed = false;
      if (holdTimer) { clearTimeout(holdTimer); holdTimer = null; }
    }
  }

  function onTouchEnd() {
    if (!isPulling) { resetState(); return; }
    // Si pas suffisamment maintenu, ne rafraîchit pas
    if (!holdArmed) {
      resetState();
      return;
    }
    // Déjà déclenché par holdTimer
    resetState();
  }

  // Écouteurs (passive:false requis pour preventDefault sur move)
  window.addEventListener('touchstart', onTouchStart, { passive: true });
  window.addEventListener('touchmove', onTouchMove, { passive: false });
  window.addEventListener('touchend', onTouchEnd, { passive: true });
  window.addEventListener('touchcancel', resetState, { passive: true });

  // Ajuster l’indicateur si le thème change dynamiquement
  const observer = new MutationObserver(() => {
    if (!indicator) return;
    if (document.body.classList.contains('night-mode')) {
      indicator.style.background = 'linear-gradient(180deg, rgba(15,23,42,0.85), rgba(15,23,42,0.65))';
      indicator.style.color = '#e5e7eb';
    } else {
      indicator.style.background = 'linear-gradient(180deg, rgba(255,255,255,0.85), rgba(255,255,255,0.65))';
      indicator.style.color = '#111827';
    }
  });
  observer.observe(document.body, { attributes: true, attributeFilter: ['class'] });
})();
