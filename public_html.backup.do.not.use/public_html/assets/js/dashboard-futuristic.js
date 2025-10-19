/**
 * ==================== JAVASCRIPT ULTRA-FUTURISTE SANS ROTATION CONTINUE ====================
 * Effets visuels spectaculaires SANS rotation continue des icônes
 * Système d'animations avancé optimisé
 */

class UltraFuturisticDashboardNoContinuousRotation {
    constructor() {
        this.isReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        this.isLowEndDevice = this.detectLowEndDevice();
        this.particles = [];
        this.animationFrameId = null;
        this.effectsEnabled = !this.isReducedMotion && !this.isLowEndDevice;
        this.init();
    }

    init() {
        this.setupAnimations();
        this.setupInteractions();
        this.setupParticleSystem();
        this.setupDataRain();
        this.setupHolographicEffects();
        this.setupSoundEffects();
        console.log('🌟 Interface ultra-futuriste SANS rotation continue initialisée');
    }

    // ==================== DÉTECTION APPAREIL ====================
    detectLowEndDevice() {
        const connection = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
        const slowConnection = connection && (connection.effectiveType === 'slow-2g' || connection.effectiveType === '2g');
        const limitedMemory = navigator.deviceMemory && navigator.deviceMemory < 4;
        const limitedCores = navigator.hardwareConcurrency && navigator.hardwareConcurrency < 4;
        return slowConnection || limitedMemory || limitedCores;
    }

    // ==================== SYSTÈME D'ANIMATIONS ==================== 
    setupAnimations() {
        this.setupCascadeAnimations();
        this.setupScrollAnimations();
        this.setupTypingAnimations();
        this.setupCounterAnimations();
    }

    setupCascadeAnimations() {
        const elements = document.querySelectorAll('.futuristic-action-btn, .futuristic-stat-card, .table-section');
        
        elements.forEach((element, index) => {
            element.style.opacity = '0';
            element.style.transform = 'translateY(50px)';
            
            setTimeout(() => {
                element.style.transition = 'all 0.8s cubic-bezier(0.4, 0, 0.2, 1)';
                element.style.opacity = '1';
                element.style.transform = 'translateY(0)';
            }, index * 100);
        });
    }

    setupScrollAnimations() {
        if (!this.effectsEnabled) return;

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-in');
                    this.triggerElementAnimation(entry.target);
                }
            });
        }, {
            threshold: 0.1,
            rootMargin: '50px'
        });

        document.querySelectorAll('.futuristic-action-btn, .futuristic-stat-card, .table-section').forEach(el => {
            observer.observe(el);
        });
    }

    setupTypingAnimations() {
        const titles = document.querySelectorAll('.section-title, .holographic-text, .modal-title');
        
        titles.forEach(title => {
            const text = title.textContent;
            title.textContent = '';
            title.style.borderRight = '3px solid var(--neon-blue)';
            
            let i = 0;
            const typeInterval = setInterval(() => {
                title.textContent += text[i];
                i++;
                
                if (i >= text.length) {
                    clearInterval(typeInterval);
                    setTimeout(() => {
                        title.style.borderRight = 'none';
                    }, 1000);
                }
            }, 100);
        });
    }

    setupCounterAnimations() {
        const counters = document.querySelectorAll('.stat-value-futuristic, .stat-value');
        
        counters.forEach(counter => {
            const target = parseInt(counter.textContent);
            if (isNaN(target)) return;
            
            counter.textContent = '0';
            let current = 0;
            const increment = target / 50;
            
            const timer = setInterval(() => {
                current += increment;
                if (current >= target) {
                    counter.textContent = target;
                    clearInterval(timer);
                } else {
                    counter.textContent = Math.floor(current);
                }
            }, 50);
        });
    }

    // ==================== INTERACTIONS AVANCÉES ====================
    setupInteractions() {
        this.setupButtonEffects();
        this.setupCardEffects();
        this.setupTableEffects();
        this.setupModalEffects();
    }

    setupButtonEffects() {
        const buttons = document.querySelectorAll('.futuristic-action-btn, .action-card');
        
        buttons.forEach(button => {
            button.addEventListener('mouseenter', (e) => this.handleButtonHover(e, true));
            button.addEventListener('mouseleave', (e) => this.handleButtonHover(e, false));
            button.addEventListener('click', (e) => this.handleButtonClick(e));
            button.addEventListener('focus', (e) => this.handleButtonFocus(e, true));
            button.addEventListener('blur', (e) => this.handleButtonFocus(e, false));
        });
    }

    handleButtonHover(event, isEntering) {
        if (!this.effectsEnabled) return;
        
        const button = event.currentTarget;
        
        if (isEntering) {
            // Animation de survol SANS rotation continue
            button.style.transform = 'translateY(-8px) scale(1.02)';
            button.style.boxShadow = '0 15px 40px rgba(0, 0, 0, 0.3), 0 0 30px var(--neon-blue)';
            
            // Effet sur l'icône SANS rotation continue
            const icon = button.querySelector('.action-icon');
            if (icon) {
                icon.style.transform = 'scale(1.2)'; // SEULEMENT scale, PAS de rotation
                icon.style.textShadow = '0 0 20px currentColor';
            }
            
            // Créer des particules
            this.createHoverParticles(button);
            this.playHoverSound();
            
        } else {
            button.style.transform = '';
            button.style.boxShadow = '';
            
            const icon = button.querySelector('.action-icon');
            if (icon) {
                icon.style.transform = '';
                icon.style.textShadow = '';
            }
        }
    }

    handleButtonClick(event) {
        const button = event.currentTarget;
        
        // Feedback tactile
        if (navigator.vibrate && this.effectsEnabled) {
            navigator.vibrate([30, 10, 30]);
        }
        
        // Effet ripple avancé
        this.createAdvancedRipple(event, button);
        
        // Onde d'énergie
        this.createEnergyWave(button);
        
        // Son de clic
        this.playClickSound();
        
        // Animation de clic
        button.style.transform = 'translateY(-4px) scale(0.98)';
        setTimeout(() => {
            button.style.transform = '';
        }, 150);
    }

    handleButtonFocus(event, isFocusing) {
        const button = event.currentTarget;
        
        if (isFocusing) {
            button.style.boxShadow = '0 0 0 4px rgba(99, 102, 241, 0.4), 0 0 30px var(--neon-blue)';
            button.style.borderColor = 'var(--neon-blue)';
        } else {
            button.style.boxShadow = '';
            button.style.borderColor = '';
        }
    }

    // ==================== SYSTÈME DE PARTICULES ==================== 
    setupParticleSystem() {
        if (!this.effectsEnabled) return;
        
        this.createParticleCanvas();
        this.startParticleAnimation();
    }

    createParticleCanvas() {
        const canvas = document.createElement('canvas');
        canvas.id = 'particle-canvas';
        canvas.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: -1;
            opacity: 0.6;
        `;
        document.body.appendChild(canvas);
        
        this.canvas = canvas;
        this.ctx = canvas.getContext('2d');
        this.resizeCanvas();
        
        window.addEventListener('resize', () => this.resizeCanvas());
    }

    resizeCanvas() {
        this.canvas.width = window.innerWidth;
        this.canvas.height = window.innerHeight;
    }

    startParticleAnimation() {
        for (let i = 0; i < 20; i++) {
            this.particles.push(this.createParticle());
        }
        
        this.animateParticles();
    }

    createParticle() {
        return {
            x: Math.random() * this.canvas.width,
            y: Math.random() * this.canvas.height,
            vx: (Math.random() - 0.5) * 0.5,
            vy: (Math.random() - 0.5) * 0.5,
            size: Math.random() * 3 + 1,
            color: this.getRandomNeonColor(),
            opacity: Math.random() * 0.8 + 0.2,
            life: Math.random() * 200 + 100
        };
    }

    getRandomNeonColor() {
        const colors = ['#00d4ff', '#8b5cf6', '#06ffa5', '#ff006e'];
        return colors[Math.floor(Math.random() * colors.length)];
    }

    animateParticles() {
        if (!this.effectsEnabled) return;
        
        this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
        
        for (let i = this.particles.length - 1; i >= 0; i--) {
            const particle = this.particles[i];
            
            particle.x += particle.vx;
            particle.y += particle.vy;
            particle.life--;
            
            if (particle.x < 0 || particle.x > this.canvas.width) particle.vx *= -1;
            if (particle.y < 0 || particle.y > this.canvas.height) particle.vy *= -1;
            
            this.ctx.globalAlpha = particle.opacity;
            this.ctx.fillStyle = particle.color;
            this.ctx.shadowBlur = 10;
            this.ctx.shadowColor = particle.color;
            this.ctx.beginPath();
            this.ctx.arc(particle.x, particle.y, particle.size, 0, Math.PI * 2);
            this.ctx.fill();
            
            if (particle.life <= 0) {
                this.particles.splice(i, 1);
                this.particles.push(this.createParticle());
            }
        }
        
        this.animationFrameId = requestAnimationFrame(() => this.animateParticles());
    }

    createHoverParticles(element) {
        if (!this.effectsEnabled) return;
        
        const rect = element.getBoundingClientRect();
        
        for (let i = 0; i < 5; i++) {
            setTimeout(() => {
                this.particles.push({
                    x: rect.left + Math.random() * rect.width,
                    y: rect.top + Math.random() * rect.height,
                    vx: (Math.random() - 0.5) * 2,
                    vy: (Math.random() - 0.5) * 2,
                    size: Math.random() * 4 + 2,
                    color: '#00d4ff',
                    opacity: 1,
                    life: 60
                });
            }, i * 50);
        }
    }

    // ==================== EFFETS AVANCÉS ====================
    createAdvancedRipple(event, element) {
        if (!this.effectsEnabled) return;
        
        const rect = element.getBoundingClientRect();
        const ripple = document.createElement('div');
        const size = Math.max(rect.width, rect.height) * 1.5;
        const x = event.clientX - rect.left - size / 2;
        const y = event.clientY - rect.top - size / 2;

        ripple.style.cssText = `
            position: absolute;
            width: ${size}px;
            height: ${size}px;
            left: ${x}px;
            top: ${y}px;
            background: radial-gradient(circle, 
                rgba(0, 212, 255, 0.6) 0%, 
                rgba(139, 92, 246, 0.4) 30%, 
                transparent 70%);
            border-radius: 50%;
            transform: scale(0);
            animation: energyRipple 1s ease-out;
            pointer-events: none;
            z-index: 100;
        `;

        this.ensureAdvancedStyles();
        element.style.position = 'relative';
        element.appendChild(ripple);
        
        setTimeout(() => {
            if (ripple.parentNode) {
                ripple.parentNode.removeChild(ripple);
            }
        }, 1000);
    }

    createEnergyWave(element) {
        if (!this.effectsEnabled) return;
        
        const wave = document.createElement('div');
        wave.style.cssText = `
            position: absolute;
            top: 50%;
            left: 50%;
            width: 20px;
            height: 20px;
            border: 2px solid var(--neon-cyan);
            border-radius: 50%;
            transform: translate(-50%, -50%) scale(0);
            animation: energyWave 0.8s ease-out;
            pointer-events: none;
            z-index: 99;
        `;

        element.style.position = 'relative';
        element.appendChild(wave);
        
        setTimeout(() => {
            if (wave.parentNode) {
                wave.parentNode.removeChild(wave);
            }
        }, 800);
    }

    // ==================== PLUIE DE DONNÉES ==================== 
    setupDataRain() {
        if (!this.effectsEnabled) return;
        
        const dataRain = document.createElement('div');
        dataRain.className = 'data-rain';
        document.body.appendChild(dataRain);
        
        for (let i = 0; i < 10; i++) {
            this.createDataColumn(i);
        }
    }

    createDataColumn(index) {
        const column = document.createElement('div');
        const data = ['01001010', '11010011', '10110101', '01011010', '11100001', '00110110'];
        
        column.style.cssText = `
            position: fixed;
            left: ${index * 10}%;
            top: -100px;
            color: var(--neon-cyan);
            font-family: 'Courier New', monospace;
            font-size: 12px;
            opacity: 0.2;
            z-index: -2;
            animation: matrixRain ${8 + Math.random() * 4}s linear infinite;
            animation-delay: ${Math.random() * 5}s;
        `;
        
        column.textContent = data[Math.floor(Math.random() * data.length)];
        document.body.appendChild(column);
        
        setTimeout(() => {
            if (column.parentNode) {
                column.parentNode.removeChild(column);
                this.createDataColumn(index);
            }
        }, 12000);
    }

    // ==================== EFFETS HOLOGRAPHIQUES ==================== 
    setupHolographicEffects() {
        if (!this.effectsEnabled) return;
        
        this.createScanLines();
        this.setupGlitchEffect();
    }

    createScanLines() {
        for (let i = 0; i < 3; i++) {
            const scanLine = document.createElement('div');
            scanLine.style.cssText = `
                position: fixed;
                top: ${i * 33}%;
                left: 0;
                width: 100%;
                height: 1px;
                background: linear-gradient(90deg, 
                    transparent 0%, 
                    var(--neon-cyan) 50%, 
                    transparent 100%);
                opacity: 0.3;
                z-index: -1;
                animation: scanLine ${6 + i}s linear infinite;
                animation-delay: ${i * 2}s;
            `;
            document.body.appendChild(scanLine);
        }
    }

    setupGlitchEffect() {
        setInterval(() => {
            if (!this.effectsEnabled) return;
            
            const elements = document.querySelectorAll('.futuristic-action-btn, .section-title');
            const randomElement = elements[Math.floor(Math.random() * elements.length)];
            
            if (randomElement) {
                randomElement.style.textShadow = `
                    2px 0 var(--neon-pink),
                    -2px 0 var(--neon-cyan),
                    0 0 10px var(--neon-blue)
                `;
                
                setTimeout(() => {
                    randomElement.style.textShadow = '';
                }, 200);
            }
        }, 5000 + Math.random() * 10000);
    }

    // ==================== EFFETS SONORES ==================== 
    setupSoundEffects() {
        this.audioContext = new (window.AudioContext || window.webkitAudioContext)();
    }

    playHoverSound() {
        if (!this.effectsEnabled || !this.audioContext) return;
        
        const oscillator = this.audioContext.createOscillator();
        const gainNode = this.audioContext.createGain();
        
        oscillator.connect(gainNode);
        gainNode.connect(this.audioContext.destination);
        
        oscillator.frequency.setValueAtTime(800, this.audioContext.currentTime);
        oscillator.frequency.exponentialRampToValueAtTime(1200, this.audioContext.currentTime + 0.1);
        
        gainNode.gain.setValueAtTime(0, this.audioContext.currentTime);
        gainNode.gain.linearRampToValueAtTime(0.1, this.audioContext.currentTime + 0.01);
        gainNode.gain.exponentialRampToValueAtTime(0.001, this.audioContext.currentTime + 0.1);
        
        oscillator.start(this.audioContext.currentTime);
        oscillator.stop(this.audioContext.currentTime + 0.1);
    }

    playClickSound() {
        if (!this.effectsEnabled || !this.audioContext) return;
        
        const oscillator = this.audioContext.createOscillator();
        const gainNode = this.audioContext.createGain();
        
        oscillator.connect(gainNode);
        gainNode.connect(this.audioContext.destination);
        
        oscillator.frequency.setValueAtTime(400, this.audioContext.currentTime);
        oscillator.frequency.exponentialRampToValueAtTime(200, this.audioContext.currentTime + 0.1);
        
        gainNode.gain.setValueAtTime(0, this.audioContext.currentTime);
        gainNode.gain.linearRampToValueAtTime(0.2, this.audioContext.currentTime + 0.01);
        gainNode.gain.exponentialRampToValueAtTime(0.001, this.audioContext.currentTime + 0.1);
        
        oscillator.start(this.audioContext.currentTime);
        oscillator.stop(this.audioContext.currentTime + 0.1);
    }

    // ==================== STYLES AVANCÉS ====================
    ensureAdvancedStyles() {
        if (!document.getElementById('ultra-futuristic-styles-no-continuous-rotation')) {
            const style = document.createElement('style');
            style.id = 'ultra-futuristic-styles-no-continuous-rotation';
            style.textContent = `
                @keyframes energyRipple {
                    to {
                        transform: scale(2);
                        opacity: 0;
                    }
                }
                
                @keyframes energyWave {
                    to {
                        transform: translate(-50%, -50%) scale(10);
                        opacity: 0;
                    }
                }
                
                @keyframes matrixRain {
                    0% { transform: translateY(-100vh); opacity: 0; }
                    10% { opacity: 0.8; }
                    90% { opacity: 0.8; }
                    100% { transform: translateY(100vh); opacity: 0; }
                }
                
                @keyframes scanLine {
                    0% { transform: translateX(-100%); }
                    100% { transform: translateX(100vw); }
                }
            `;
            document.head.appendChild(style);
        }
    }

    // ==================== API PUBLIQUE ==================== 
    
    toggleEffects() {
        this.effectsEnabled = !this.effectsEnabled;
        
        if (this.effectsEnabled) {
            document.body.classList.remove('no-effects');
            this.startParticleAnimation();
            console.log('✨ Effets ultra-futuristes SANS rotation continue activés');
        } else {
            document.body.classList.add('no-effects');
            if (this.animationFrameId) {
                cancelAnimationFrame(this.animationFrameId);
            }
            console.log('❌ Effets ultra-futuristes désactivés');
        }
        
        return this.effectsEnabled;
    }

    enablePerformanceMode() {
        this.effectsEnabled = false;
        document.body.classList.add('no-effects');
        
        if (this.animationFrameId) {
            cancelAnimationFrame(this.animationFrameId);
        }
        
        const canvas = document.getElementById('particle-canvas');
        if (canvas) canvas.remove();
        
        console.log('🐌 Mode performance activé');
    }

    triggerElementAnimation(element) {
        if (!this.effectsEnabled) return;
        
        element.style.animation = 'none';
        element.offsetHeight;
        element.style.animation = 'scaleIn 0.6s ease-out';
        
        const rect = element.getBoundingClientRect();
        for (let i = 0; i < 8; i++) {
            this.particles.push({
                x: rect.left + rect.width / 2,
                y: rect.top + rect.height / 2,
                vx: Math.cos(i * Math.PI / 4) * 2,
                vy: Math.sin(i * Math.PI / 4) * 2,
                size: 3,
                color: this.getRandomNeonColor(),
                opacity: 0.8,
                life: 80
            });
        }
    }

    destroy() {
        if (this.animationFrameId) {
            cancelAnimationFrame(this.animationFrameId);
        }
        
        const canvas = document.getElementById('particle-canvas');
        if (canvas) canvas.remove();
        
        const dataRain = document.querySelector('.data-rain');
        if (dataRain) dataRain.remove();
    }

    static getInstance() {
        if (!window.ultraFuturisticDashboardNoContinuousRotation) {
            window.ultraFuturisticDashboardNoContinuousRotation = new UltraFuturisticDashboardNoContinuousRotation();
        }
        return window.ultraFuturisticDashboardNoContinuousRotation;
    }
}

// ==================== AUTO-INITIALISATION ====================
document.addEventListener('DOMContentLoaded', function() {
    if (document.querySelector('.modern-dashboard, .futuristic-dashboard-container') ||
        window.location.pathname.includes('accueil')) {
        
        const dashboard = UltraFuturisticDashboardNoContinuousRotation.getInstance();
        
        window.UltraFuturisticDashboardNoContinuousRotation = UltraFuturisticDashboardNoContinuousRotation;
        window.ultraFuturisticDashboard = dashboard;
        
        console.log('🚀 Interface ultra-futuriste SANS rotation continue activée !');
    }
});

// ==================== FONCTIONS UTILITAIRES GLOBALES ==================== 
function toggleUltraFuturisticEffects() {
    if (window.ultraFuturisticDashboard) {
        return window.ultraFuturisticDashboard.toggleEffects();
    }
    return false;
}

function enableUltraPerformanceMode() {
    if (window.ultraFuturisticDashboard) {
        window.ultraFuturisticDashboard.enablePerformanceMode();
    } else {
        document.body.classList.add('no-effects');
    }
}

if (typeof module !== 'undefined' && module.exports) {
    module.exports = UltraFuturisticDashboardNoContinuousRotation;
}

