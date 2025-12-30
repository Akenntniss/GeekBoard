<?php
/**
 * Exit Intent Popup
 * Apparaît quand l'utilisateur essaie de quitter la page
 */
?>
<!-- Exit Popup Modal -->
<div class="modal fade" id="exitPopupModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content glass-card border-0 text-white position-relative overflow-hidden">
            <!-- Glow Effect -->
            <div class="position-absolute top-50 start-50 translate-middle bg-primary rounded-circle" style="width: 200px; height: 200px; filter: blur(80px); opacity: 0.2; z-index: -1;"></div>
            
            <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3 z-3" data-bs-dismiss="modal" aria-label="Close"></button>
            
            <div class="modal-body p-5 text-center">
                <div class="mb-4">
                    <div class="d-inline-flex align-items-center justify-content-center bg-primary bg-opacity-10 rounded-circle p-3 mb-3" style="width: 80px; height: 80px; border: 1px solid rgba(6,182,212,0.3);">
                        <i class="fa-solid fa-gift text-primary fs-1 animate-pulse"></i>
                    </div>
                </div>
                
                <h3 class="fw-black mb-3 text-uppercase display-6">Attendez !</h3>
                <p class="text-secondary fs-5 mb-4">
                    Ne partez pas les mains vides.<br>
                    Profitez de <span class="text-neon fw-bold">30 jours offerts</span> + Formation Admin.
                </p>
                
                <div class="glass p-3 rounded-3 mb-4 border border-primary border-opacity-25">
                    <div class="text-muted small text-uppercase tracking-wider mb-1">Code Promo activé</div>
                    <div class="h3 fm-mono text-white mb-0 tracking-widest">FUTUR2025</div>
                </div>
                
                <div class="d-grid gap-3">
                    <a href="/inscription?promo=FUTUR2025" class="btn btn-glow btn-lg rounded-pill" onclick="trackConversion('exit_popup')">
                        JE RÉCUPÈRE MON OFFRE
                        <i class="fa-solid fa-arrow-right ms-2"></i>
                    </a>
                    <button type="button" class="btn btn-link text-secondary text-decoration-none btn-sm" data-bs-dismiss="modal">
                        Non merci, je refuse ce cadeau
                    </button>
                </div>
            </div>
            
            <!-- Progress Bar -->
            <div class="progress position-absolute bottom-0 start-0 w-100" style="height: 4px; background: transparent;">
                <div class="progress-bar bg-primary" id="popupTimer" role="progressbar" style="width: 100%"></div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let exitIntentTriggered = false;
    const COOKIE_NAME = 'servo_exit_popup_shown';
    const EXPIRY_DAYS = 7;

    // Check cookie
    function getCookie(name) {
        const value = `; ${document.cookie}`;
        const parts = value.split(`; ${name}=`);
        if (parts.length === 2) return parts.pop().split(';').shift();
    }

    function setCookie(name, value, days) {
        const date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        document.cookie = `${name}=${value};expires=${date.toUTCString()};path=/`;
    }

    // Logic
    if (!getCookie(COOKIE_NAME)) {
        document.addEventListener('mouseout', function(e) {
            // Si la souris sort par le haut de la fenêtre
            if (e.clientY < 0 && !exitIntentTriggered) {
                exitIntentTriggered = true;
                const modal = new bootstrap.Modal(document.getElementById('exitPopupModal'));
                modal.show();
                setCookie(COOKIE_NAME, 'true', EXPIRY_DAYS);
            }
        });
    }
});
</script>
