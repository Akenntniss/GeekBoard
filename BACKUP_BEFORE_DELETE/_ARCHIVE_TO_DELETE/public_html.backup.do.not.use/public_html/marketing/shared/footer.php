<!-- Footer -->
<footer class="border-top" style="background-color: var(--bg-primary); border-color: var(--border-color) !important; transition: background-color 0.3s ease, border-color 0.3s ease;">
    <div class="container py-5">
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="mb-4">
                    <a href="/" class="navbar-brand fs-4">
                        <img src="/assets/images/logo/logoservo.png" alt="SERVO" height="40">
                    </a>
                </div>
                <p class="mb-3" style="color: var(--text-muted);">
                    Révolutionnez votre atelier avec SERVO. La solution tout-en-un qui digitalise votre activité : SMS automatiques, gestion intelligente du stock, suivi clients en temps réel et pointage employés simplifié. 
                    Boostez votre productivité et votre chiffre d'affaires dès le premier jour.
                </p>
                <div class="d-flex gap-3">
                    <a href="/inscription" class="btn btn-primary">
                        <i class="fa-solid fa-rocket me-2"></i>Essai gratuit 30 jours
                    </a>
                </div>
            </div>
            
            <div class="col-lg-2 col-md-6">
                <h6 class="fw-bold mb-3" style="color: var(--text-primary);"><?php echo t('footer_product'); ?></h6>
                <ul class="list-unstyled">
                    <li class="mb-2"><a href="/features" class="text-decoration-none" style="color: var(--text-muted); transition: color 0.3s ease;" onmouseover="this.style.color='var(--primary)'" onmouseout="this.style.color='var(--text-muted)'"><?php echo t('nav_features'); ?></a></li>
                    <li class="mb-2"><a href="/pricing" class="text-decoration-none" style="color: var(--text-muted); transition: color 0.3s ease;" onmouseover="this.style.color='var(--primary)'" onmouseout="this.style.color='var(--text-muted)'"><?php echo t('nav_pricing'); ?></a></li>
                    <li class="mb-2"><a href="/integrations" class="text-decoration-none" style="color: var(--text-muted); transition: color 0.3s ease;" onmouseover="this.style.color='var(--primary)'" onmouseout="this.style.color='var(--text-muted)'"><?php echo t('nav_integrations'); ?></a></li>
                    <li class="mb-2"><a href="/security" class="text-decoration-none" style="color: var(--text-muted); transition: color 0.3s ease;" onmouseover="this.style.color='var(--primary)'" onmouseout="this.style.color='var(--text-muted)'"><?php echo t('nav_security'); ?></a></li>
                </ul>
            </div>
            
            <div class="col-lg-2 col-md-6">
                <h6 class="fw-bold mb-3" style="color: var(--text-primary);"><?php echo t('nav_resources'); ?></h6>
                <ul class="list-unstyled">
                    <li class="mb-2"><a href="/roi" class="text-decoration-none" style="color: var(--text-muted); transition: color 0.3s ease;" onmouseover="this.style.color='var(--primary)'" onmouseout="this.style.color='var(--text-muted)'"><?php echo t('nav_roi'); ?></a></li>
                    <li class="mb-2"><a href="/testimonials" class="text-decoration-none" style="color: var(--text-muted); transition: color 0.3s ease;" onmouseover="this.style.color='var(--primary)'" onmouseout="this.style.color='var(--text-muted)'"><?php echo t('nav_testimonials'); ?></a></li>
                    <li class="mb-2"><a href="/multistore" class="text-decoration-none" style="color: var(--text-muted); transition: color 0.3s ease;" onmouseover="this.style.color='var(--primary)'" onmouseout="this.style.color='var(--text-muted)'"><?php echo t('nav_multistore'); ?></a></li>
                    <li class="mb-2"><a href="/vs-repairdesk" class="text-decoration-none" style="color: var(--text-muted); transition: color 0.3s ease;" onmouseover="this.style.color='var(--primary)'" onmouseout="this.style.color='var(--text-muted)'"><?php echo t('nav_vs_repairdesk'); ?></a></li>
                </ul>
            </div>
            
            <div class="col-lg-4">
                <h6 class="fw-bold mb-3" style="color: var(--text-primary);">Contact</h6>
                <ul class="list-unstyled">
                    <li class="mb-2 d-flex align-items-center">
                        <i class="fa-solid fa-envelope text-primary me-3"></i>
                        <a href="mailto:servo@maisondugeek.fr" class="text-decoration-none" style="color: var(--text-muted); transition: color 0.3s ease;" onmouseover="this.style.color='var(--primary)'" onmouseout="this.style.color='var(--text-muted)'">servo@maisondugeek.fr</a>
                    </li>
                    <li class="mb-2 d-flex align-items-center">
                        <i class="fa-solid fa-phone text-primary me-3"></i>
                        <span style="color: var(--text-muted);">08 95 79 59 33</span>
                    </li>
                    <li class="mb-2 d-flex align-items-center">
                        <i class="fa-solid fa-map-marker-alt text-primary me-3"></i>
                        <span style="color: var(--text-muted);">78 bd maison du geek, 06110 le cannet</span>
                    </li>
                </ul>
                
                <div class="mt-4">
                    <h6 class="fw-bold mb-3" style="color: var(--text-primary);">Restez informé</h6>
                    <p class="small mb-3" style="color: var(--text-muted);">Recevez nos actualités produit et conseils d'optimisation.</p>
                    <div class="input-group">
                        <input type="email" class="form-control" placeholder="Votre email" style="border-radius: var(--border-radius) 0 0 var(--border-radius); background-color: var(--bg-primary); border-color: var(--border-color); color: var(--text-primary);">
                        <button class="btn btn-primary" type="button" style="border-radius: 0 var(--border-radius) var(--border-radius) 0;">
                            <i class="fa-solid fa-paper-plane"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <hr class="my-4" style="border-color: var(--border-color);">
        
        <div class="row align-items-center">
            <div class="col-md-6">
                <p class="small mb-0" style="color: var(--text-muted);">
                    &copy; <?php echo date('Y'); ?> SERVO. Tous droits réservés.
                </p>
            </div>
            <div class="col-md-6 text-md-end">
                <ul class="list-inline mb-0">
                    <li class="list-inline-item">
                        <a href="/privacy" class="text-decoration-none small" style="color: var(--text-muted); transition: color 0.3s ease;" onmouseover="this.style.color='var(--primary)'" onmouseout="this.style.color='var(--text-muted)'">Confidentialité</a>
                    </li>
                    <li class="list-inline-item">
                        <a href="/cgu" class="text-decoration-none small" style="color: var(--text-muted); transition: color 0.3s ease;" onmouseover="this.style.color='var(--primary)'" onmouseout="this.style.color='var(--text-muted)'">CGU</a>
                    </li>
                    <li class="list-inline-item">
                        <a href="/cookies" class="text-decoration-none small" style="color: var(--text-muted); transition: color 0.3s ease;" onmouseover="this.style.color='var(--primary)'" onmouseout="this.style.color='var(--text-muted)'">Cookies</a>
                    </li>
                    <li class="list-inline-item">
                        <a href="/mentions-legales" class="text-decoration-none small" style="color: var(--text-muted); transition: color 0.3s ease;" onmouseover="this.style.color='var(--primary)'" onmouseout="this.style.color='var(--text-muted)'">Mentions légales</a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</footer>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Animations on scroll -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Animate elements on scroll
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-fade-in-up');
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);

    // Observe all cards and sections
    document.querySelectorAll('.card-feature, .card-modern, .section > .container > *').forEach(el => {
        observer.observe(el);
    });
});
</script>

</body>
</html>