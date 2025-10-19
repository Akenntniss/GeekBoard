/* ====================================================================
   DIAGNOSTIC ULTRA-COMPLET DU MODAL CIRCULAIRE
   Script pour identifier exactement ce qui ne fonctionne pas
==================================================================== */

(function() {
    'use strict';
    
    console.log('🔍🚨 [MODAL-DIAGNOSTIC-ULTRA] Script de diagnostic ultra-complet chargé');
    
    // Diagnostic complet du modal
    window.ultraDiagnosticModal = function() {
        console.log('🔍🚨 [MODAL-DIAGNOSTIC-ULTRA] === DIAGNOSTIC COMPLET ===');
        
        // 1. Vérifier l'environnement
        console.log('📱 Largeur écran:', window.innerWidth);
        console.log('🌙 Mode sombre:', window.matchMedia('(prefers-color-scheme: dark)').matches);
        console.log('📱 Mode mobile:', window.innerWidth <= 768);
        console.log('🌙📱 Mode mobile nuit:', window.innerWidth <= 768 && window.matchMedia('(prefers-color-scheme: dark)').matches);
        
        // 2. Vérifier l'existence du modal
        const modal = document.getElementById('nouvelles_actions_modal');
        console.log('🎭 Modal trouvé:', !!modal);
        
        if (!modal) {
            console.error('❌ PROBLÈME: Modal nouvelles_actions_modal introuvable !');
            console.log('📋 Modals disponibles:', Array.from(document.querySelectorAll('.modal')).map(m => m.id));
            return;
        }
        
        // 3. Analyser la structure du modal
        console.log('🎭 Classes du modal:', modal.className);
        console.log('🎭 Style display:', getComputedStyle(modal).display);
        console.log('🎭 Style visibility:', getComputedStyle(modal).visibility);
        console.log('🎭 Style opacity:', getComputedStyle(modal).opacity);
        
        // 4. Vérifier la structure circulaire
        const circularContainer = modal.querySelector('.circular-container');
        const links = modal.querySelector('.links');
        const linksList = modal.querySelector('.links__list');
        const linksItems = modal.querySelectorAll('.links__item');
        const linksLinks = modal.querySelectorAll('.links__link');
        
        console.log('🔗 Circular container:', !!circularContainer);
        console.log('🔗 Links container:', !!links);
        console.log('🔗 Links list:', !!linksList);
        console.log('🔗 Links items:', linksItems.length);
        console.log('🔗 Links links:', linksLinks.length);
        
        if (linksLinks.length === 0) {
            console.error('❌ PROBLÈME CRITIQUE: Aucun .links__link trouvé !');
            console.log('🔍 Structure HTML du modal:');
            console.log(modal.innerHTML.substring(0, 1000) + '...');
            return;
        }
        
        // 5. Analyser chaque lien
        linksLinks.forEach((link, index) => {
            const styles = getComputedStyle(link);
            console.log(`🔗 Lien ${index + 1}:`, {
                display: styles.display,
                visibility: styles.visibility,
                opacity: styles.opacity,
                transform: styles.transform,
                animation: styles.animation,
                animationName: styles.animationName,
                animationDuration: styles.animationDuration,
                animationDelay: styles.animationDelay,
                position: styles.position,
                zIndex: styles.zIndex
            });
        });
        
        // 6. Vérifier les CSS chargés
        const cssFiles = Array.from(document.styleSheets).map(sheet => {
            try {
                return sheet.href || 'inline';
            } catch (e) {
                return 'inaccessible';
            }
        });
        
        const relevantCSS = cssFiles.filter(css => 
            css.includes('modal-circular') || 
            css.includes('mobile-night') || 
            css.includes('force-animations')
        );
        
        console.log('📄 CSS pertinents chargés:', relevantCSS);
        
        // 7. Tester l'animation manuellement
        console.log('🧪 Test d\'animation manuelle...');
        
        linksLinks.forEach((link, index) => {
            // Réinitialiser
            link.style.cssText = '';
            link.offsetHeight; // Force reflow
            
            // Appliquer l'animation
            link.style.setProperty('opacity', '0', 'important');
            link.style.setProperty('transform', 'scale(0.1)', 'important');
            link.style.setProperty('background-color', 'red', 'important'); // Test visuel
            link.style.setProperty('border', '5px solid yellow', 'important'); // Test visuel
            
            setTimeout(() => {
                link.style.setProperty('opacity', '1', 'important');
                link.style.setProperty('transform', 'scale(1)', 'important');
                link.style.setProperty('transition', 'all 0.5s ease', 'important');
                console.log(`✅ Animation appliquée au lien ${index + 1}`);
            }, 100 + (index * 200));
        });
        
        console.log('🔍🚨 [MODAL-DIAGNOSTIC-ULTRA] === FIN DIAGNOSTIC ===');
    };
    
    // Test de visibilité pure
    window.testModalVisibility = function() {
        console.log('👁️ [TEST-VISIBILITÉ] Test de visibilité du modal...');
        
        const modal = document.getElementById('nouvelles_actions_modal');
        if (!modal) {
            console.error('❌ Modal introuvable');
            return;
        }
        
        // Forcer l'ouverture du modal
        modal.classList.add('show');
        modal.style.display = 'block';
        modal.style.visibility = 'visible';
        modal.style.opacity = '1';
        modal.style.zIndex = '9999';
        modal.style.backgroundColor = 'rgba(255, 0, 0, 0.8)'; // Rouge pour test
        
        console.log('✅ Modal forcé visible avec fond rouge');
        
        // Tester les liens
        const links = modal.querySelectorAll('.links__link');
        links.forEach((link, index) => {
            link.style.backgroundColor = `hsl(${index * 60}, 100%, 50%)`;
            link.style.border = '3px solid black';
            link.style.width = '100px';
            link.style.height = '100px';
            link.style.display = 'block';
            link.style.position = 'relative';
            link.style.margin = '10px';
            link.style.zIndex = '10000';
            
            console.log(`✅ Lien ${index + 1} stylé en couleur`);
        });
    };
    
    // Forcer la création du modal s'il n'existe pas
    window.forceCreateModal = function() {
        console.log('🏗️ [FORCE-CREATE] Création forcée du modal...');
        
        let modal = document.getElementById('nouvelles_actions_modal');
        if (modal) {
            console.log('ℹ️ Modal existe déjà');
            return;
        }
        
        // Créer le modal de base
        const modalHTML = `
        <div class="modal fade" id="nouvelles_actions_modal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Test Modal</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="circular-container">
                            <div class="links">
                                <ul class="links__list" style="--item-total: 3;">
                                    <li class="links__item" style="--item-count: 0;">
                                        <a href="#" class="links__link">
                                            <span>Test 1</span>
                                        </a>
                                    </li>
                                    <li class="links__item" style="--item-count: 1;">
                                        <a href="#" class="links__link">
                                            <span>Test 2</span>
                                        </a>
                                    </li>
                                    <li class="links__item" style="--item-count: 2;">
                                        <a href="#" class="links__link">
                                            <span>Test 3</span>
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>`;
        
        document.body.insertAdjacentHTML('beforeend', modalHTML);
        console.log('✅ Modal créé avec succès');
        
        // Tester immédiatement
        setTimeout(() => {
            window.testModalVisibility();
        }, 100);
    };
    
    // Auto-diagnostic au chargement
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(() => {
            console.log('🔍🚨 [MODAL-DIAGNOSTIC-ULTRA] Auto-diagnostic...');
            window.ultraDiagnosticModal();
            
            console.log('🔍🚨 [MODAL-DIAGNOSTIC-ULTRA] ✅ Fonctions disponibles:');
            console.log('- window.ultraDiagnosticModal() : Diagnostic complet');
            console.log('- window.testModalVisibility() : Test de visibilité');
            console.log('- window.forceCreateModal() : Créer le modal si absent');
            
        }, 1000);
    });
    
})();


























