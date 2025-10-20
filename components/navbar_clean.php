<?php
// Navbar minimaliste unifiée (sans dock, sans scripts annexes)
?>
<link rel="stylesheet" href="<?php echo (strpos($_SERVER['SCRIPT_NAME'], '/pages/') !== false) ? '../assets/' : 'assets/'; ?>css/navbar-clean-buttons.css">
<nav id="desktop-navbar" class="navbar navbar-light bg-white border-bottom shadow-sm py-2" style="position:fixed;top:0;left:0;right:0;z-index:1030;">
    <div class="container-fluid px-3" style="position: relative;">
        <!-- Logo à gauche -->
        <a class="navbar-brand d-flex align-items-center" href="/index.php" style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%);">
            <img src="<?php echo (strpos($_SERVER['SCRIPT_NAME'], '/pages/') !== false) ? '../assets/' : 'assets/'; ?>images/logo/logoservo.png" alt="MDGeek" height="60">
        </a>
        
        <!-- Logo SERVO animé au centre -->
        <a href="/index.php" class="servo-logo-container" style="text-decoration: none; cursor: pointer;">
            <div class="loader">
                <svg height="0" width="0" viewBox="0 0 100 100" class="absolute">
                    <defs class="s-xJBuHA073rTt" xmlns="http://www.w3.org/2000/svg">
                        <linearGradient class="s-xJBuHA073rTt" gradientUnits="userSpaceOnUse" y2="2" x2="0" y1="62" x1="0" id="b">
                            <stop class="s-xJBuHA073rTt" stop-color="#0369a1"></stop>
                            <stop class="s-xJBuHA073rTt" stop-color="#67e8f9" offset="1.5"></stop>
                        </linearGradient>
                        <linearGradient class="s-xJBuHA073rTt" gradientUnits="userSpaceOnUse" y2="0" x2="0" y1="64" x1="0" id="c">
                            <stop class="s-xJBuHA073rTt" stop-color="#0369a1"></stop>
                            <stop class="s-xJBuHA073rTt" stop-color="#22d3ee" offset="1"></stop>
                            <animateTransform repeatCount="indefinite" keySplines=".42,0,.58,1;.42,0,.58,1;.42,0,.58,1;.42,0,.58,1;.42,0,.58,1;.42,0,.58,1;.42,0,.58,1;.42,0,.58,1" keyTimes="0; 0.125; 0.25; 0.375; 0.5; 0.625; 0.75; 0.875; 1" dur="8s" values="0 32 32;-270 32 32;-270 32 32;-540 32 32;-540 32 32;-810 32 32;-810 32 32;-1080 32 32;-1080 32 32" type="rotate" attributeName="gradientTransform"></animateTransform>
                        </linearGradient>
                        <linearGradient class="s-xJBuHA073rTt" gradientUnits="userSpaceOnUse" y2="2" x2="0" y1="62" x1="0" id="d">
                            <stop class="s-xJBuHA073rTt" stop-color="#38bdf8"></stop>
                            <stop class="s-xJBuHA073rTt" stop-color="#075985" offset="1.5"></stop>
                        </linearGradient>
                    </defs>
                </svg>
                <!-- S -->
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 100 100" width="40" height="40" class="inline-block" style="transform: translateY(5px);">
                    <path stroke-linejoin="round" stroke-linecap="round" stroke-width="11" stroke="url(#b)" d="M 75,25 Q 75,15 65,15 L 35,15 Q 25,15 25,25 Q 25,35 35,37 L 65,43 Q 75,45 75,55 Q 75,65 65,65 L 35,65 Q 25,65 25,75" class="dash" id="S" pathLength="360"></path>
                </svg>
                <!-- E -->
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 100 100" width="32" height="32" class="inline-block">
                    <path stroke-linejoin="round" stroke-linecap="round" stroke-width="8" stroke="url(#b)" d="M 20,20 L 80,20 L 80,27 L 27,27 L 27,50 L 70,50 L 70,57 L 25,57 L 25,80 L 80,80 L 80,87 L 20,87 Z" class="dash" id="E" pathLength="360"></path>
                </svg>
                <!-- R -->
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 100 100" width="32" height="32" class="inline-block">
                    <path stroke-linejoin="round" stroke-linecap="round" stroke-width="8" stroke="url(#d)" d="M 20,20 L 20,87 M 20,20 L 70,20 L 80,30 L 80,43 L 70,53 L 20,53 M 70,53 L 80,87" class="dash" id="R" pathLength="360"></path>
                </svg>
                <!-- V -->
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 100 100" width="32" height="32" class="inline-block">
                    <path stroke-linejoin="round" stroke-linecap="round" stroke-width="12" stroke="url(#d)" d="M 20,20 L 50,80 L 80,20" class="dash" id="V" pathLength="360"></path>
                </svg>
                <!-- O -->
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 100 100" width="32" height="32" class="inline-block">
                    <path stroke-linejoin="round" stroke-linecap="round" stroke-width="11" stroke="url(#c)" d="M 50,15 A 35,35 0 0 1 85,50 A 35,35 0 0 1 50,85 A 35,35 0 0 1 15,50 A 35,35 0 0 1 50,15 Z" class="spin" id="O" pathLength="360"></path>
                </svg>
            </div>
        </a>
        
        <!-- Boutons à droite -->
        <div class="d-flex align-items-center gap-2" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%) translateY(8px);">
            <button class="btn btn-primary btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#nouvelles_actions_modal" title="Nouvelle action">
                <i class="fas fa-plus"></i>
            </button>
            <button class="btn btn-outline-secondary btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#futuristicMenuModal" title="Menu">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </div>
</nav>
<div style="height:56px"></div>


