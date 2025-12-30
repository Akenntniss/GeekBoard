/**
 * Loader SERVO - Durée minimale 1.5 secondes
 */

(function () {
    'use strict';

    const startTime = Date.now();
    const MIN_DURATION = 1500; // 1.5 secondes minimum

    // CSS EXACT - AUCUNE MODIFICATION
    const loaderCSS = `
        .loader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 999999;
            background: linear-gradient(0deg, #1a3379, #0f172a, #000);
            transition: opacity 0.5s ease;
        }
        
        .loader.fade-out {
            opacity: 0;
            pointer-events: none;
        }

        .loader-wrapper {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 180px;
            height: 180px;
            font-family: "Inter", sans-serif;
            font-size: 1.1em;
            font-weight: 300;
            color: white;
            border-radius: 50%;
            background-color: transparent;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
        }

        .loader-circle {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            aspect-ratio: 1 / 1;
            border-radius: 50%;
            background-color: transparent;
            animation: loader-combined 2.3s linear infinite;
            z-index: 0;
        }
        
        @keyframes loader-combined {
            0% {
                transform: rotate(90deg);
                box-shadow:
                    0 6px 12px 0 #38bdf8 inset,
                    0 12px 18px 0 #005dff inset,
                    0 36px 36px 0 #1e40af inset,
                    0 0 3px 1.2px rgba(56, 189, 248, 0.3),
                    0 0 6px 1.8px rgba(0, 93, 255, 0.2);
            }
            25% {
                transform: rotate(180deg);
                box-shadow:
                    0 6px 12px 0 #0099ff inset,
                    0 12px 18px 0 #38bdf8 inset,
                    0 36px 36px 0 #005dff inset,
                    0 0 6px 2.4px rgba(56, 189, 248, 0.3),
                    0 0 12px 3.6px rgba(0, 93, 255, 0.2),
                    0 0 18px 6px rgba(30, 64, 175, 0.15);
            }
            50% {
                transform: rotate(270deg);
                box-shadow:
                    0 6px 12px 0 #60a5fa inset,
                    0 12px 6px 0 #0284c7 inset,
                    0 24px 36px 0 #005dff inset,
                    0 0 3px 1.2px rgba(56, 189, 248, 0.3),
                    0 0 6px 1.8px rgba(0, 93, 255, 0.2);
            }
            75% {
                transform: rotate(360deg);
                box-shadow:
                    0 6px 12px 0 #3b82f6 inset,
                    0 12px 18px 0 #0ea5e9 inset,
                    0 36px 36px 0 #2563eb inset,
                    0 0 6px 2.4px rgba(56, 189, 248, 0.3),
                    0 0 12px 3.6px rgba(0, 93, 255, 0.2),
                    0 0 18px 6px rgba(30, 64, 175, 0.15);
            }
            100% {
                transform: rotate(450deg);
                box-shadow:
                    0 6px 12px 0 #4dc8fd inset,
                    0 12px 18px 0 #005dff inset,
                    0 36px 36px 0 #1e40af inset,
                    0 0 3px 1.2px rgba(56, 189, 248, 0.3),
                    0 0 6px 1.8px rgba(0, 93, 255, 0.2);
            }
        }

        .loader-letter {
            display: inline-block;
            opacity: 0.4;
            transform: translateY(0);
            animation: loader-letter-anim 2.4s infinite;
            z-index: 1;
            border-radius: 50ch;
            border: none;
        }

        .loader-letter:nth-child(1) {
            animation-delay: 0s;
        }
        .loader-letter:nth-child(2) {
            animation-delay: 0.1s;
        }
        .loader-letter:nth-child(3) {
            animation-delay: 0.2s;
        }
        .loader-letter:nth-child(4) {
            animation-delay: 0.3s;
        }
        .loader-letter:nth-child(5) {
            animation-delay: 0.4s;
        }
        .loader-letter:nth-child(6) {
            animation-delay: 0.5s;
        }
        .loader-letter:nth-child(7) {
            animation-delay: 0.6s;
        }
        .loader-letter:nth-child(8) {
            animation-delay: 0.7s;
        }
        .loader-letter:nth-child(9) {
            animation-delay: 0.8s;
        }
        .loader-letter:nth-child(10) {
            animation-delay: 0.9s;
        }
        .loader-letter:nth-child(11) {
            animation-delay: 1s;
        }
        .loader-letter:nth-child(12) {
            animation-delay: 1.1s;
        }
        .loader-letter:nth-child(13) {
            animation-delay: 1.2s;
        }

        @keyframes loader-letter-anim {
            0%,
            100% {
                opacity: 0.4;
                transform: translateY(0);
            }
            20% {
                opacity: 1;
                text-shadow: #f8fcff 0 0 5px;
            }
            40% {
                opacity: 0.7;
                transform: translateY(0);
            }
        }
    `;

    // Injecter le CSS
    const styleEl = document.createElement('style');
    styleEl.textContent = loaderCSS;
    document.head.appendChild(styleEl);

    // HTML EXACT - AUCUNE MODIFICATION
    const loaderHTML = `
        <div class="loader" id="loader">
            <div class="loader-wrapper">
                <span class="loader-letter">S</span>
                <span class="loader-letter">E</span>
                <span class="loader-letter">R</span>
                <span class="loader-letter">V</span>
                <span class="loader-letter">O</span>
                <div class="loader-circle"></div>
            </div>
        </div>
    `;

    // Injecter le loader
    if (document.body) {
        document.body.insertAdjacentHTML('afterbegin', loaderHTML);
    } else {
        document.addEventListener('DOMContentLoaded', function () {
            document.body.insertAdjacentHTML('afterbegin', loaderHTML);
        });
    }

    // Fonction pour cacher le loader (avec durée minimale garantie)
    function hideLoader() {
        const elapsed = Date.now() - startTime;
        const remaining = MIN_DURATION - elapsed;

        if (remaining > 0) {
            // Attendre le temps restant pour atteindre 1.5 secondes minimum
            setTimeout(function () {
                const loader = document.getElementById('loader');
                if (loader) {
                    loader.classList.add('fade-out');
                    setTimeout(function () {
                        if (loader.parentNode) {
                            loader.parentNode.removeChild(loader);
                        }
                    }, 600);
                }
            }, remaining);
        } else {
            // Déjà passé 1.5 secondes, cacher immédiatement
            const loader = document.getElementById('loader');
            if (loader) {
                loader.classList.add('fade-out');
                setTimeout(function () {
                    if (loader.parentNode) {
                        loader.parentNode.removeChild(loader);
                    }
                }, 600);
            }
        }
    }

    // Cacher le loader quand la page est chargée
    if (document.readyState === 'complete') {
        hideLoader();
    } else {
        window.addEventListener('load', hideLoader);
    }

})();
