<?php include 'marketing/shared/header.php'; ?>

<!-- Hero Section -->
<section class="hero-section position-relative overflow-hidden pt-5 pb-5">
    <div class="position-absolute top-0 start-0 w-100 h-100" style="z-index: -1;">
        <div class="position-absolute top-0 start-0 bg-primary opacity-20 rounded-circle blur-3xl" style="width: 500px; height: 500px; filter: blur(100px); transform: translate(-30%, -30%);"></div>
    </div>

    <div class="container position-relative pt-5 mt-5">
        <div class="row justify-content-center text-center">
            <div class="col-lg-8">
                <div class="badge bg-primary bg-opacity-20 text-primary border border-primary border-opacity-20 rounded-pill px-3 py-2 mb-4 fade-in-up">
                    <i class="fa-regular fa-newspaper me-2"></i>Le Blog SERVO
                </div>
                <h1 class="display-3 fw-bold mb-4 fade-in-up delay-1">Actualités & Conseils</h1>
                <p class="fs-5 text-muted mb-0 fade-in-up delay-2">
                    Tutoriels techniques, astuces de gestion et analyses du marché de la réparation. Tout pour faire décoller votre atelier.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Blog Grid -->
<section class="section pt-0">
    <div class="container">
        
        <!-- Categories -->
        <div class="d-flex justify-content-center gap-3 mb-5 overflow-auto pb-2">
            <a href="#" class="btn btn-primary rounded-pill px-4">Tout</a>
            <a href="#" class="btn btn-outline-light rounded-pill px-4">Tutoriels</a>
            <a href="#" class="btn btn-outline-light rounded-pill px-4">Business</a>
            <a href="#" class="btn btn-outline-light rounded-pill px-4">Mises à jour</a>
            <a href="#" class="btn btn-outline-light rounded-pill px-4">Études de cas</a>
        </div>

        <div class="row g-4">
            
            <!-- Featured Article (Big) -->
            <div class="col-12 mb-4 fade-in-up">
                <div class="card-glass position-relative overflow-hidden h-100 group-hover">
                    <div class="row g-0 h-100">
                        <div class="col-lg-6 position-relative" style="min-height: 300px;">
                            <img src="https://images.unsplash.com/photo-1581092921461-eab62e97a780?q=80&w=2670&auto=format&fit=crop" class="img-cover w-100 h-100 position-absolute top-0 start-0" alt="Repair">
                            <div class="position-absolute top-0 start-0 m-3">
                                <span class="badge bg-primary">Business</span>
                            </div>
                        </div>
                        <div class="col-lg-6 p-4 p-md-5 d-flex flex-column justify-content-center">
                            <div class="d-flex align-items-center mb-3 text-muted small">
                                <i class="fa-regular fa-calendar me-2"></i>27 Décembre 2025
                                <span class="mx-2">•</span>
                                <i class="fa-regular fa-clock me-2"></i>5 min de lecture
                            </div>
                            <h2 class="fw-bold mb-3 group-hover-text-primary transition-all">Comment augmenter votre panier moyen de 30% grâce aux accessoires ?</h2>
                            <p class="text-muted mb-4">
                                Vendre une réparation c'est bien, vendre une protection en plus c'est mieux. Découvrez nos techniques de vente croisée (cross-selling) à appliquer au comptoir.
                            </p>
                            <div>
                                <a href="#" class="btn btn-outline-primary rounded-pill px-4">Lire l'article</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Article 1 -->
            <div class="col-md-6 col-lg-4 fade-in-up delay-1">
                <div class="card-glass h-100 d-flex flex-column group-hover overflow-hidden">
                    <div class="position-relative" style="height: 200px;">
                        <img src="https://images.unsplash.com/photo-1597733336794-12d05021d510?q=80&w=2574&auto=format&fit=crop" class="img-cover w-100 h-100" alt="Tech">
                        <div class="position-absolute top-0 start-0 m-3">
                            <span class="badge bg-secondary">Technique</span>
                        </div>
                    </div>
                    <div class="p-4 flex-grow-1 d-flex flex-column">
                        <div class="text-muted small mb-2">15 Décembre 2025</div>
                        <h4 class="fw-bold mb-3 group-hover-text-primary transition-all">Microsoudure : Quel équipement pour débuter ?</h4>
                        <p class="text-muted small mb-4 flex-grow-1">
                            Comparatif des stations de soudure (JBC vs Quick) et liste du matériel indispensable pour démarrer la réparation carte mère.
                        </p>
                        <a href="#" class="fw-bold text-decoration-none text-white stretched-link">Lire la suite <i class="fa-solid fa-arrow-right ms-2 opacity-0 group-hover-show transition-all"></i></a>
                    </div>
                </div>
            </div>

            <!-- Article 2 -->
            <div class="col-md-6 col-lg-4 fade-in-up delay-2">
                <div class="card-glass h-100 d-flex flex-column group-hover overflow-hidden">
                    <div class="position-relative" style="height: 200px;">
                        <img src="https://images.unsplash.com/photo-1556761175-5973dc0f32e7?q=80&w=2664&auto=format&fit=crop" class="img-cover w-100 h-100" alt="Customer">
                        <div class="position-absolute top-0 start-0 m-3">
                            <span class="badge bg-success">Tutoriel</span>
                        </div>
                    </div>
                    <div class="p-4 flex-grow-1 d-flex flex-column">
                        <div class="text-muted small mb-2">10 Décembre 2025</div>
                        <h4 class="fw-bold mb-3 group-hover-text-primary transition-all">Configurer vos modèles de SMS automatiques</h4>
                        <p class="text-muted small mb-4 flex-grow-1">
                            Guide pas à pas pour personnaliser vos notifications clients dans SERVO et réduire les appels entrants.
                        </p>
                        <a href="#" class="fw-bold text-decoration-none text-white stretched-link">Lire la suite <i class="fa-solid fa-arrow-right ms-2 opacity-0 group-hover-show transition-all"></i></a>
                    </div>
                </div>
            </div>

            <!-- Article 3 -->
            <div class="col-md-6 col-lg-4 fade-in-up delay-3">
                <div class="card-glass h-100 d-flex flex-column group-hover overflow-hidden">
                    <div class="position-relative" style="height: 200px;">
                        <img src="https://images.unsplash.com/photo-1551288049-bebda4e38f71?q=80&w=2670&auto=format&fit=crop" class="img-cover w-100 h-100" alt="Data">
                        <div class="position-absolute top-0 start-0 m-3">
                            <span class="badge bg-info">Analyse</span>
                        </div>
                    </div>
                    <div class="p-4 flex-grow-1 d-flex flex-column">
                        <div class="text-muted small mb-2">05 Décembre 2025</div>
                        <h4 class="fw-bold mb-3 group-hover-text-primary transition-all">L'état du marché de la réparation en 2026</h4>
                        <p class="text-muted small mb-4 flex-grow-1">
                            Tendances, indice de réparabilité et impact de l'IA. Ce qui va changer pour les réparateurs indépendants cette année.
                        </p>
                        <a href="#" class="fw-bold text-decoration-none text-white stretched-link">Lire la suite <i class="fa-solid fa-arrow-right ms-2 opacity-0 group-hover-show transition-all"></i></a>
                    </div>
                </div>
            </div>

        </div>

        <!-- Pagination -->
        <div class="d-flex justify-content-center mt-5">
            <nav>
                <ul class="pagination pagination-modern">
                    <li class="page-item disabled"><a class="page-link" href="#"><i class="fa-solid fa-chevron-left"></i></a></li>
                    <li class="page-item active"><a class="page-link" href="#">1</a></li>
                    <li class="page-item"><a class="page-link" href="#">2</a></li>
                    <li class="page-item"><a class="page-link" href="#">3</a></li>
                    <li class="page-item"><a class="page-link" href="#"><i class="fa-solid fa-chevron-right"></i></a></li>
                </ul>
            </nav>
        </div>

    </div>
</section>

<!-- Newsletter Section -->
<section class="section pt-0">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                 <div class="card-glass p-5 text-center bg-primary bg-opacity-5">
                     <i class="fa-regular fa-paper-plane fs-1 mb-3 text-primary"></i>
                     <h3 class="fw-bold mb-2">Rejoignez 500+ réparateurs informés</h3>
                     <p class="text-muted mb-4">Recevez une fois par semaine nos meilleurs conseils pour gérer votre atelier.</p>
                     <form class="d-flex justify-content-center gap-2">
                         <input type="email" class="form-control w-50 bg-dark border-light" placeholder="Email professionnel">
                         <button class="btn btn-primary fw-bold">S'inscrire</button>
                     </form>
                 </div>
            </div>
        </div>
    </div>
</section>

<style>
.card-glass {
    background: rgba(255, 255, 255, 0.03);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.05);
    border-radius: 16px;
    transition: all 0.3s ease;
}
.group-hover:hover {
    transform: translateY(-5px);
    background: rgba(255, 255, 255, 0.08);
    border-color: rgba(255, 255, 255, 0.1);
}
.group-hover:hover .group-hover-text-primary {
    color: var(--primary) !important;
}
.group-hover:hover .group-hover-show {
    opacity: 1 !important;
    transform: translateX(5px);
}
.img-cover {
    object-fit: cover;
}
.pagination-modern .page-link {
    background: transparent;
    border: 1px solid rgba(255,255,255,0.1);
    color: white;
    margin: 0 5px;
    border-radius: 8px;
}
.pagination-modern .page-item.active .page-link {
    background: var(--primary);
    border-color: var(--primary);
}
.pagination-modern .page-link:hover {
    background: rgba(255,255,255,0.1);
}
</style>

<?php include 'marketing/shared/footer.php'; ?>
