        </main>

        <!-- Footer moderne et discret -->
        <footer style="
            background: var(--sa-surface-muted); 
            padding: 2rem 3rem; 
            border-top: 1px solid var(--sa-border);
            text-align: center;
            color: var(--sa-text-muted);
        ">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-tools" style="color: var(--sa-primary);"></i>
                    <span style="font-weight: 600;">GeekBoard SuperAdmin</span>
                    <span style="font-size: 0.875rem; opacity: 0.7;">v2024.1</span>
                </div>
                
                <div style="font-size: 0.875rem;">
                    © <?php echo date('Y'); ?> GeekBoard. 
                    <span style="opacity: 0.7;">Développé avec ❤️ pour les professionnels</span>
                </div>
                
                <div style="display: flex; gap: 1rem; font-size: 0.875rem;">
                    <a href="#" style="color: var(--sa-text-muted); text-decoration: none; transition: color 0.3s ease;" 
                       onmouseover="this.style.color='var(--sa-primary)'" 
                       onmouseout="this.style.color='var(--sa-text-muted)'">
                        <i class="fas fa-question-circle me-1"></i>Support
                    </a>
                    <a href="#" style="color: var(--sa-text-muted); text-decoration: none; transition: color 0.3s ease;"
                       onmouseover="this.style.color='var(--sa-primary)'" 
                       onmouseout="this.style.color='var(--sa-text-muted)'">
                        <i class="fas fa-book me-1"></i>Documentation
                    </a>
                </div>
            </div>
            
            <!-- Indicateur de statut système (optionnel) -->
            <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--sa-border); font-size: 0.75rem; opacity: 0.6;">
                <span style="color: var(--sa-success);">
                    <i class="fas fa-circle" style="font-size: 0.5rem; margin-right: 0.5rem;"></i>
                    Système opérationnel
                </span>
                <span style="margin-left: 2rem;">
                    Dernière mise à jour: <?php echo date('d/m/Y à H:i'); ?>
                </span>
            </div>
        </footer>
    </div>

    <!-- Scripts Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" 
            integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" 
            crossorigin="anonymous"></script>
    
    <!-- Scripts spécifiques à la page (si définis) -->
    <?php if (!empty($extra_footer_scripts)): ?>
        <?php echo $extra_footer_scripts; ?>
    <?php endif; ?>

    <!-- Performance et Analytics (si nécessaire) -->
    <script>
        // Métriques de performance simples
        window.addEventListener('load', function() {
            if ('performance' in window) {
                const loadTime = Math.round(performance.now());
                console.log(`🚀 Page chargée en ${loadTime}ms`);
                
                // Optionnel: envoyer les métriques à un service d'analytics
                // analytics.track('page_load', { time: loadTime, page: location.pathname });
            }
        });

        // Gestion des erreurs JavaScript globales
        window.addEventListener('error', function(e) {
            console.error('Erreur JavaScript:', e.error);
            // Optionnel: rapporter l'erreur à un service de monitoring
        });
    </script>
</body>
</html>




