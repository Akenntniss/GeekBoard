<!-- End Main Content -->
</main>

<footer class="pt-5 pb-0 position-relative overflow-hidden" style="background-color: #020617; border-top: 1px solid rgba(255,255,255,0.05);">
    
    <!-- Glow Effect Footer -->
    <div class="position-absolute start-50 translate-middle-x" style="top: 0; width: 60%; height: 1px; background: linear-gradient(90deg, transparent, var(--primary), transparent); opacity: 0.5;"></div>

    <div class="container pt-5">
        <div class="row g-5 mb-5 ps-lg-4">
            <!-- Brand Column -->
            <div class="col-lg-4">
                <a href="/" class="d-flex align-items-center text-decoration-none mb-4">
                    <img src="/assets/images/logo/logoservo.png" alt="Servo" height="40" class="me-2 filter-brightness">
                    <span class="fs-3 fw-black text-white tracking-tight">SERVO<span class="text-primary">.TOOLS</span></span>
                </a>
                <p class="text-secondary mb-4 opacity-75">
                    L'OS complet pour les ateliers de réparation modernes. 
                    Pilotez votre activité avec une interface futuriste et puissante.
                </p>
                <div class="d-flex gap-3">
                    <a href="#" class="btn btn-icon btn-outline-secondary rounded-circle border-opacity-25 text-white social-hover" aria-label="LinkedIn">
                        <i class="fa-brands fa-linkedin-in" aria-hidden="true"></i>
                    </a>
                    <a href="#" class="btn btn-icon btn-outline-secondary rounded-circle border-opacity-25 text-white social-hover" aria-label="Twitter">
                        <i class="fa-brands fa-twitter" aria-hidden="true"></i>
                    </a>
                    <a href="#" class="btn btn-icon btn-outline-secondary rounded-circle border-opacity-25 text-white social-hover" aria-label="Instagram">
                        <i class="fa-brands fa-instagram" aria-hidden="true"></i>
                    </a>
                </div>
            </div>

            <!-- Links Column 1 -->
            <div class="col-lg-2 col-6">
                <h6 class="text-white fw-bold mb-4 text-uppercase tracking-wider small opacity-50">Produit</h6>
                <ul class="list-unstyled d-flex flex-column gap-3">
                    <li><a href="/features" class="text-secondary text-decoration-none hover-white transition-all">Fonctionnalités</a></li>
                    <li><a href="/enterprise" class="text-secondary text-decoration-none hover-white transition-all">Grandes Entreprises</a></li>
                    <li><a href="/pricing" class="text-secondary text-decoration-none hover-white transition-all">Tarifs</a></li>
                    <li><a href="/customers" class="text-secondary text-decoration-none hover-white transition-all">Clients <span class="badge bg-success bg-opacity-10 text-success ms-1 text-xs">New</span></a></li>
                    <li><a href="/roadmap" class="text-secondary text-decoration-none hover-white transition-all">Roadmap</a></li>
                </ul>
            </div>

            <!-- Links Column 2 -->
            <div class="col-lg-2 col-6">
                <h6 class="text-white fw-bold mb-4 text-uppercase tracking-wider small opacity-50">Société</h6>
                <ul class="list-unstyled d-flex flex-column gap-3">
                    <li><a href="/about" class="text-secondary text-decoration-none hover-white transition-all">À Propos</a></li>
                    <li><a href="/security" class="text-secondary text-decoration-none hover-white transition-all">Sécurité</a></li>
                    <li><a href="/blog" class="text-secondary text-decoration-none hover-white transition-all">Blog</a></li>
                    <li><a href="/help" class="text-secondary text-decoration-none hover-white transition-all">Centre d'aide</a></li>
                    <li><a href="/status" class="text-secondary text-decoration-none hover-white transition-all">Status</a></li>
                </ul>
            </div>

            <!-- Newsletter Column -->
            <div class="col-lg-4">
                <h6 class="text-white fw-bold mb-4 text-uppercase tracking-wider small opacity-50">Restez connecté</h6>
                <p class="text-secondary small mb-3">Recevez nos dernières astuces et mises à jour.</p>
                <form class="mb-3 position-relative">
                    <label for="newsletter-email" class="visually-hidden">Adresse email pour la newsletter</label>
                    <input type="email" id="newsletter-email" class="form-control bg-dark border-secondary border-opacity-25 text-white py-3 ps-4 pe-5 rounded-pill" placeholder="votre@email.com" style="background: rgba(255,255,255,0.03) !important;">
                    <button type="button" class="btn btn-link position-absolute top-50 end-0 translate-middle-y text-primary pe-3" aria-label="S'inscrire à la newsletter">
                        <i class="fa-solid fa-paper-plane" aria-hidden="true"></i>
                    </button>
                </form>
                <div class="d-flex align-items-center gap-2 text-secondary small opacity-50">
                    <i class="fa-solid fa-circle-check text-green-500"></i> Pas de spam, désabonnement facile.
                </div>
            </div>
        </div>

        <!-- Bottom Bar -->
        <div class="border-top border-white border-opacity-5 py-4 d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
            <div class="text-secondary small opacity-50">
                &copy; <?php echo date('Y'); ?> SERVO TOOLS. Tous droits réservés.
            </div>
            <div class="d-flex gap-4 small">
                <a href="/legal" class="text-secondary text-decoration-none opacity-50 hover-white">Mentions Légales</a>
                <a href="/privacy" class="text-secondary text-decoration-none opacity-50 hover-white">Confidentialité</a>
                <a href="/terms" class="text-secondary text-decoration-none opacity-50 hover-white">CGV</a>
            </div>
        </div>
    </div>
</footer>

<!-- Exit Intent Popup -->
<?php include_once __DIR__ . '/exit-popup.php'; ?>

<!-- Chatbot Widget -->
<?php include_once __DIR__ . '/chatbot-widget.php'; ?>

<!-- Global Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Intersection Observer for fade-in animations
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
            }
        });
    }, {
        threshold: 0.1
    });

    // Add scroll listener for navbar
    window.addEventListener('scroll', function() {
        const navbar = document.querySelector('.navbar-modern');
        if (window.scrollY > 50) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
    });
</script>

<style>
    .hover-white:hover { color: white !important; opacity: 1 !important; }
    .social-hover:hover { border-color: var(--primary) !important; background: var(--primary); color: white !important; }
    .text-xs { font-size: 0.7rem; }
    
    /* Input Customization */
    .form-control::placeholder { color: rgba(255,255,255,0.3); }
    .form-control:focus { background: rgba(255,255,255,0.05) !important; border-color: var(--primary) !important; box-shadow: 0 0 0 4px rgba(6,182,212,0.1); color: white; }
</style>
</body>
</html>