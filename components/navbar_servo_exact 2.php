<?php
/**
 * Navbar GeekBoard avec Logo SERVO Animé
 * Version exacte avec logo SERVO au centre
 */
?>

<!-- NAVBAR DESKTOP avec Logo SERVO -->
<nav class="navbar navbar-expand-lg fixed-top" id="desktop-navbar">
    <div class="container-fluid">
        
        <!-- Logo SERVO animé au centre -->
        <div class="servo-logo-container">
            <div class="loader">
                <svg height="0" width="0" viewBox="0 0 100 100" class="absolute">
                    <defs class="s-xJBuHA073rTt" xmlns="http://www.w3.org/2000/svg">
                        <linearGradient class="s-xJBuHA073rTt" gradientUnits="userSpaceOnUse" y2="2" x2="0" y1="62" x1="0" id="servo-gradient-b">
                            <stop class="s-xJBuHA073rTt" stop-color="#0369a1"></stop>
                            <stop class="s-xJBuHA073rTt" stop-color="#67e8f9" offset="1.5"></stop>
                        </linearGradient>
                        <linearGradient class="s-xJBuHA073rTt" gradientUnits="userSpaceOnUse" y2="0" x2="0" y1="64" x1="0" id="servo-gradient-c">
                            <stop class="s-xJBuHA073rTt" stop-color="#0369a1"></stop>
                            <stop class="s-xJBuHA073rTt" stop-color="#22d3ee" offset="1"></stop>
                            <animateTransform repeatCount="indefinite" keySplines=".42,0,.58,1;.42,0,.58,1;.42,0,.58,1;.42,0,.58,1;.42,0,.58,1;.42,0,.58,1;.42,0,.58,1;.42,0,.58,1" keyTimes="0; 0.125; 0.25; 0.375; 0.5; 0.625; 0.75; 0.875; 1" dur="8s" values="0 32 32;-270 32 32;-270 32 32;-540 32 32;-540 32 32;-810 32 32;-810 32 32;-1080 32 32;-1080 32 32" type="rotate" attributeName="gradientTransform"></animateTransform>
                        </linearGradient>
                        <linearGradient class="s-xJBuHA073rTt" gradientUnits="userSpaceOnUse" y2="2" x2="0" y1="62" x1="0" id="servo-gradient-d">
                            <stop class="s-xJBuHA073rTt" stop-color="#38bdf8"></stop>
                            <stop class="s-xJBuHA073rTt" stop-color="#075985" offset="1.5"></stop>
                        </linearGradient>
                    </defs>
                </svg>
                <!-- S -->
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 100 100" width="32" height="32" class="inline-block">
                    <path stroke-linejoin="round" stroke-linecap="round" stroke-width="8" stroke="url(#servo-gradient-b)" d="M 80,20 L 20,20 L 20,27 L 73,27 L 73,43 L 27,43 L 27,50 L 75,50 L 75,80 L 20,80 L 20,87 L 80,87 Z" class="dash" id="servo-S" pathLength="360"></path>
                </svg>
                <!-- E -->
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 100 100" width="32" height="32" class="inline-block">
                    <path stroke-linejoin="round" stroke-linecap="round" stroke-width="8" stroke="url(#servo-gradient-b)" d="M 20,20 L 80,20 L 80,27 L 27,27 L 27,50 L 70,50 L 70,57 L 25,57 L 25,80 L 80,80 L 80,87 L 20,87 Z" class="dash" id="servo-E" pathLength="360"></path>
                </svg>
                <!-- R -->
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 100 100" width="32" height="32" class="inline-block">
                    <path stroke-linejoin="round" stroke-linecap="round" stroke-width="8" stroke="url(#servo-gradient-d)" d="M 20,20 L 20,87 M 20,20 L 70,20 L 80,30 L 80,43 L 70,53 L 20,53 M 70,53 L 80,87" class="dash" id="servo-R" pathLength="360"></path>
                </svg>
                <!-- V -->
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 100 100" width="32" height="32" class="inline-block">
                    <path stroke-linejoin="round" stroke-linecap="round" stroke-width="12" stroke="url(#servo-gradient-d)" d="M 20,20 L 50,80 L 80,20" class="dash" id="servo-V" pathLength="360"></path>
                </svg>
                <!-- O -->
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 100 100" width="32" height="32" class="inline-block">
                    <path stroke-linejoin="round" stroke-linecap="round" stroke-width="11" stroke="url(#servo-gradient-c)" d="M 50,15 A 35,35 0 0 1 85,50 A 35,35 0 0 1 50,85 A 35,35 0 0 1 15,50 A 35,35 0 0 1 50,15 Z" class="spin" id="servo-O" pathLength="360"></path>
                </svg>
            </div>
        </div>
        
        <!-- Boutons de navigation à droite -->
        <div class="d-flex align-items-center ms-auto gap-2">
            <!-- Bouton + -->
            <button class="btn btn-primary btn-nouvelle-improved" type="button" id="btnNouvelle" data-bs-toggle="modal" data-bs-target="#nouvelles_actions_modal" title="Nouvelle action">
                <i class="fas fa-plus"></i>
            </button>
            
            <!-- Bouton hamburger -->
            <button class="navbar-toggler main-menu-btn" type="button" data-bs-toggle="modal" data-bs-target="#futuristicMenuModal" aria-controls="futuristicMenuModal">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </div>
</nav>

<script>
// S'assurer que le logo SERVO est visible
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 [SERVO-LOGO] Logo SERVO chargé dans la navbar');
    
    // Vérifier que les animations SVG fonctionnent
    const servoContainer = document.querySelector('.servo-logo-container');
    if (servoContainer) {
        console.log('✅ [SERVO-LOGO] Conteneur logo trouvé');
        servoContainer.style.opacity = '1';
        servoContainer.style.visibility = 'visible';
    } else {
        console.error('❌ [SERVO-LOGO] Conteneur logo non trouvé');
    }
});
</script>
