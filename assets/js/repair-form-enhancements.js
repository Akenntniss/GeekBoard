/**
 * AMÉLIORATIONS POUR LE FORMULAIRE DE RÉPARATION
 * Effets sonores, animations et interactions avancées
 */

document.addEventListener('DOMContentLoaded', function() {
    // Détection du mode sombre
    const isDarkMode = () => {
        return document.body.classList.contains('dark-mode') || 
               window.matchMedia('(prefers-color-scheme: dark)').matches;
    };
    
    // Sons futuristes (uniquement en mode nuit)
    const playSound = (type) => {
        if (!isDarkMode()) return;
        
        const audioContext = window.AudioContext || window.webkitAudioContext;
        if (!audioContext) return;
        
        const ctx = new audioContext();
        const oscillator = ctx.createOscillator();
        const gainNode = ctx.createGain();
        
        oscillator.connect(gainNode);
        gainNode.connect(ctx.destination);
        
        // Configuration des sons selon le type
        switch(type) {
            case 'hover':
                oscillator.frequency.setValueAtTime(800, ctx.currentTime);
                oscillator.frequency.exponentialRampToValueAtTime(1000, ctx.currentTime + 0.1);
                gainNode.gain.setValueAtTime(0.1, ctx.currentTime);
                gainNode.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.1);
                oscillator.type = 'sine';
                break;
            case 'select':
                oscillator.frequency.setValueAtTime(600, ctx.currentTime);
                oscillator.frequency.exponentialRampToValueAtTime(800, ctx.currentTime + 0.2);
                gainNode.gain.setValueAtTime(0.2, ctx.currentTime);
                gainNode.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.2);
                oscillator.type = 'triangle';
                break;
            case 'success':
                oscillator.frequency.setValueAtTime(523, ctx.currentTime); // Do
                oscillator.frequency.setValueAtTime(659, ctx.currentTime + 0.1); // Mi
                oscillator.frequency.setValueAtTime(784, ctx.currentTime + 0.2); // Sol
                gainNode.gain.setValueAtTime(0.15, ctx.currentTime);
                gainNode.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.3);
                oscillator.type = 'sine';
                break;
        }
        
        oscillator.start(ctx.currentTime);
        oscillator.stop(ctx.currentTime + 0.3);
    };
    
    // Amélioration des interactions pour les options d'appareils
    const enhanceDeviceOptions = () => {
        const deviceOptions = document.querySelectorAll('.device-type-option');
        console.log('🎨 Amélioration des interactions pour', deviceOptions.length, 'options');
        
        deviceOptions.forEach(option => {
            // Effet de survol (n'interfère pas avec la logique existante)
            option.addEventListener('mouseenter', function() {
                playSound('hover');
                
                if (isDarkMode()) {
                    // Effet de particules au survol en mode nuit
                    createParticleEffect(this);
                }
            });
            
            // Effet visuel de clic (ajouté APRÈS le clic principal pour ne pas interférer)
            option.addEventListener('click', function(event) {
                playSound('select');
                
                // Animation de sélection
                this.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    this.style.transform = '';
                }, 150);
                
                // Effet d'ondulation
                createRippleEffect(this, event);
            }, { capture: false }); // S'assurer que cet événement se déclenche APRÈS les autres
            
            // Amélioration du focus pour l'accessibilité
            option.addEventListener('focus', function() {
                playSound('hover');
            });
        });
    };
    
    // Créer un effet de particules (mode nuit uniquement)
    const createParticleEffect = (element) => {
        if (!isDarkMode()) return;
        
        const rect = element.getBoundingClientRect();
        const particleCount = 5;
        
        for (let i = 0; i < particleCount; i++) {
            const particle = document.createElement('div');
            particle.style.cssText = `
                position: fixed;
                width: 4px;
                height: 4px;
                background: #00ffff;
                border-radius: 50%;
                pointer-events: none;
                z-index: 9999;
                box-shadow: 0 0 10px #00ffff;
                left: ${rect.left + Math.random() * rect.width}px;
                top: ${rect.top + Math.random() * rect.height}px;
                opacity: 1;
                transition: all 1s ease-out;
            `;
            
            document.body.appendChild(particle);
            
            // Animation des particules
            setTimeout(() => {
                particle.style.transform = `translate(${(Math.random() - 0.5) * 100}px, ${(Math.random() - 0.5) * 100}px)`;
                particle.style.opacity = '0';
            }, 10);
            
            // Nettoyage
            setTimeout(() => {
                if (particle.parentNode) {
                    particle.parentNode.removeChild(particle);
                }
            }, 1000);
        }
    };
    
    // Créer un effet d'ondulation au clic
    const createRippleEffect = (element, event) => {
        const rect = element.getBoundingClientRect();
        const ripple = document.createElement('div');
        const size = Math.max(rect.width, rect.height);
        const x = event.clientX - rect.left - size / 2;
        const y = event.clientY - rect.top - size / 2;
        
        ripple.style.cssText = `
            position: absolute;
            width: ${size}px;
            height: ${size}px;
            border-radius: 50%;
            background: ${isDarkMode() ? 'rgba(0, 255, 255, 0.3)' : 'rgba(37, 99, 235, 0.3)'};
            transform: scale(0);
            left: ${x}px;
            top: ${y}px;
            pointer-events: none;
            animation: ripple 0.6s ease-out;
        `;
        
        // S'assurer que l'élément parent a une position relative
        if (getComputedStyle(element).position === 'static') {
            element.style.position = 'relative';
        }
        
        element.appendChild(ripple);
        
        // Nettoyage après l'animation
        setTimeout(() => {
            if (ripple.parentNode) {
                ripple.parentNode.removeChild(ripple);
            }
        }, 600);
    };
    
    // Amélioration de la barre de progression
    const enhanceProgressBar = () => {
        const progressFill = document.querySelector('.repair-progress-fill');
        if (!progressFill) return;
        
        // Animation de la barre de progression
        const animateProgress = (targetWidth) => {
            const currentWidth = parseInt(progressFill.style.width) || 0;
            const increment = (targetWidth - currentWidth) / 20;
            let currentStep = 0;
            
            const animate = () => {
                if (currentStep < 20) {
                    const newWidth = currentWidth + (increment * currentStep);
                    progressFill.style.width = `${Math.min(newWidth, targetWidth)}%`;
                    currentStep++;
                    requestAnimationFrame(animate);
                } else {
                    progressFill.style.width = `${targetWidth}%`;
                    if (targetWidth === 100) {
                        playSound('success');
                    }
                }
            };
            
            animate();
        };
        
        // Observer les changements d'étapes
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.type === 'attributes' && mutation.attributeName === 'data-step') {
                    const step = parseInt(mutation.target.getAttribute('data-step'));
                    const width = step * 25; // 4 étapes = 100%
                    animateProgress(width);
                }
            });
        });
        
        observer.observe(progressFill, { attributes: true });
    };
    
    // Amélioration des boutons
    const enhanceButtons = () => {
        const buttons = document.querySelectorAll('.btn');
        
        buttons.forEach(button => {
            button.addEventListener('click', function(event) {
                // Effet d'ondulation pour les boutons
                createRippleEffect(this, event);
                
                // Son de clic
                playSound('select');
            });
            
            // Effet de survol
            button.addEventListener('mouseenter', function() {
                playSound('hover');
            });
        });
    };
    
    // Animation d'entrée pour les éléments
    const animateElements = () => {
        const elementsToAnimate = document.querySelectorAll('.repair-header, .repair-form-container, .device-type-option');
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, { threshold: 0.1 });
        
        elementsToAnimate.forEach(element => {
            // État initial
            element.style.opacity = '0';
            element.style.transform = 'translateY(30px)';
            element.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            
            observer.observe(element);
        });
    };
    
    // Gestion du mode sombre/clair
    const handleThemeChange = () => {
        const themeObserver = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.type === 'attributes' && mutation.attributeName === 'data-theme') {
                    // Réinitialiser les effets selon le nouveau thème
                    console.log('Thème changé:', isDarkMode() ? 'Sombre' : 'Clair');
                }
            });
        });
        
        themeObserver.observe(document.documentElement, { attributes: true });
    };
    
    // Initialisation de tous les améliorations
    const init = () => {
        // Ajouter les styles CSS pour les animations
        const style = document.createElement('style');
        style.textContent = `
            @keyframes ripple {
                to {
                    transform: scale(2);
                    opacity: 0;
                }
            }
            
            .device-type-option {
                overflow: hidden;
            }
            
            .repair-progress-fill {
                transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            }
        `;
        document.head.appendChild(style);
        
        // Initialiser toutes les améliorations
        enhanceDeviceOptions();
        enhanceProgressBar();
        enhanceButtons();
        animateElements();
        handleThemeChange();
        
        console.log('🚀 Améliorations du formulaire de réparation initialisées');
    };
    
    // Démarrer l'initialisation
    init();
});
