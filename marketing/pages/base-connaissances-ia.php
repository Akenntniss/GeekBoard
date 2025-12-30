<?php
/**
 * Landing Page - Base de Connaissances IA
 * Unique : Recherche intelligente avec Groq AI
 */
?>

<!-- Hero Section KBASE -->
<section class="bg-gradient-hero text-white position-relative overflow-hidden">
    <div class="container py-5">
        <div class="row align-items-center min-vh-75 py-5">
            <div class="col-lg-6 order-2 order-lg-1">
                <div class="pe-lg-5">
                    <div class="badge bg-primary text-white mb-4 px-3 py-2 fade-in-left">
                        <i class="fa-solid fa-brain me-2"></i>
                        IA intégrée
                    </div>
                    
                    <h1 class="display-4 fw-black mb-4 fade-in-left">
                        Base de connaissances<br>intelligente
                    </h1>
                    
                    <p class="fs-5 mb-4 opacity-90 fade-in-left">
                        Posez des questions en langage naturel. L'IA comprend "iPhone ne charge plus" et trouve la solution instantanément.
                    </p>
                    
                    <div class="d-flex flex-column flex-sm-row gap-3 mb-4 fade-in-left">
                        <a href="/inscription" class="btn btn-light btn-lg">
                            <i class="fa-solid fa-rocket me-2"></i>
                            Essai gratuit 30 jours
                        </a>
                        <a href="#demo-kbase" class="btn btn-outline-light btn-lg">
                            <i class="fa-solid fa-wand-magic-sparkles me-2"></i>
                            Tester l'IA
                        </a>
                    </div>
                    
                    <div class="d-flex flex-wrap gap-4 text-white-50">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fa-solid fa-bolt text-primary"></i>
                            <small>Groq AI ultra-rapide</small>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <i class="fa-solid fa-language"></i>
                            <small>Langage naturel</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6 order-1 order-lg-2 mb-4 mb-lg-0">
                <div class="position-relative">
                    <div class="card-modern p-4 bg-white bg-opacity-95 text-dark fade-in-right" style="max-width: 400px; margin: 0 auto;">
                        <h6 class="fw-bold mb-3">
                            <i class="fa-solid fa-brain text-primary me-2"></i>
                            Recherche intelligente
                        </h6>
                        
                        <div class="bg-light rounded p-3 mb-3">
                            <div class="d-flex gap-2 mb-2">
                                <i class="fa-solid fa-user text-primary"></i>
                                <div class="flex-grow-1">
                                    <p class="mb-0 small"><em>"Mon iPhone ne charge plus, que faire ?"</em></p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-primary bg-opacity-10 rounded p-3">
                            <div class="d-flex gap-2">
                                <i class="fa-solid fa-robot text-primary"></i>
                                <div class="flex-grow-1">
                                    <p class="mb-2 small fw-bold">Résultat trouvé :</p>
                                    <p class="mb-2 small"><strong>iPhone ne charge plus</strong></p>
                                    <p class="mb-0 small text-muted">
                                        1. Tester avec un autre câble<br>
                                        2. Nettoyer le port Lightning<br>
                                        3. Vérifier le connecteur...
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Stats -->
<section class="section-sm bg-white">
    <div class="container">
        <div class="row g-4 text-center">
            <div class="col-lg-3 col-md-6">
                <div class="h2 fw-black text-primary mb-1">0,5s</div>
                <div class="text-muted">Temps de réponse</div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="h2 fw-black text-success mb-1">95%</div>
                <div class="text-muted">Pertinence IA</div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="h2 fw-black text-warning mb-1">1000+</div>
                <div class="text-muted">Articles intégrés</div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="h2 fw-black text-info mb-1">24/7</div>
                <div class="text-muted">Disponibilité</div>
            </div>
        </div>
    </div>
</section>

<!-- DÉMO INTERACTIVE -->
<section class="section" id="demo-kbase" style="background: linear-gradient(180deg, var(--bg-secondary) 0%, var(--bg-primary) 100%);">
    <div class="container">
        <div class="text-center mb-5">
            <div class="badge bg-primary mb-3 px-3 py-2">
                <i class="fa-solid fa-brain me-2"></i>
                Démo IA en direct
            </div>
            <h2 class="fw-black mb-3">Posez une question à l'IA</h2>
            <p class="text-muted fs-5">Recherche en langage naturel</p>
        </div>
        
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card-modern p-4">
                    <div class="mb-4">
                        <div class="input-group input-group-lg">
                            <span class="input-group-text"><i class="fa-solid fa-search"></i></span>
                            <input type="text" id="ai-search" class="form-control" placeholder="Ex: iPhone ne s'allume plus, écran noir Samsung..."
                                   style="border-radius: 0 var(--border-radius) var(--border-radius) 0;">
                            <button class="btn btn-primary" onclick="searchAI()">
                                <i class="fa-solid fa-wand-magic-sparkles me-2"></i>
                                Rechercher
                            </button>
                        </div>
                    </div>
                    
                    <div class="row g-2 mb-4">
                        <div class="col-auto">
                            <button class="btn btn-outline-secondary btn-sm" onclick="setQuery('iPhone ne charge plus')">iPhone ne charge plus</button>
                        </div>
                        <div class="col-auto">
                            <button class="btn btn-outline-secondary btn-sm" onclick="setQuery('Ecran noir au démarrage')">Ecran noir</button>
                        </div>
                        <div class="col-auto">
                            <button class="btn btn-outline-secondary btn-sm" onclick="setQuery('Problème tactile')">Tactile défaillant</button>
                        </div>
                    </div>
                    
                    <div id="ai-results" class="d-none">
                        <div class="bg-primary bg-opacity-5 rounded p-4">
                            <div class="d-flex gap-3 mb-3">
                                <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center text-white flex-shrink-0" style="width: 40px; height: 40px;">
                                    <i class="fa-solid fa-robot"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="fw-bold mb-2">Résultat IA</h6>
                                    <div id="ai-response"></div>
                                </div>
                            </div>
                            
                            <div class="border-top pt-3">
                                <h6 class="fw-bold mb-3">Articles correspondants</h6>
                                <div id="ai-articles"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Fonctionnalités -->
<section class="section bg-white">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-black mb-3">Plus qu'une simple base de connaissances</h2>
        </div>
        
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card-feature p-4 text-center h-100">
                    <i class="fa-solid fa-brain text-primary fs-1 mb-3"></i>
                    <h5 class="fw-bold mb-3">IA Groq</h5>
                    <p class="text-muted">L'IA la plus rapide du marché (0,5s) pour comprendre vos questions</p>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card-feature p-4 text-center h-100">
                    <i class="fa-solid fa-tags text-success fs-1 mb-3"></i>
                    <h5 class="fw-bold mb-3">Catégories</h5>
                    <p class="text-muted">Organisez par marque, modèle, type de panne pour retrouver facilement</p>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card-feature p-4 text-center h-100">
                    <i class="fa-solid fa-file-pdf text-danger fs-1 mb-3"></i>
                    <h5 class="fw-bold mb-3">Fichiers joints</h5>
                    <p class="text-muted">PDF, images, vidéos pour guider vos techniciens pas à pas</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="section bg-gradient-primary text-white">
    <div class="container text-center">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <h2 class="fw-black mb-4">Formez vos équipes plus rapidement</h2>
                <p class="fs-5 mb-4 opacity-90">
                    Vos techniciens trouvent instantanément la solution à chaque problème.
                </p>
                <a href="/inscription" class="btn btn-light btn-lg">
                    <i class="fa-solid fa-rocket me-2"></i>
                    Démarrer l'essai gratuit
                </a>
            </div>
        </div>
    </div>
</section>

<script>
const mockKB = {
    'charge': {
        response: 'Problème de charge détecté. Voici les étapes de diagnostic :',
        articles: [
            {title: 'iPhone ne charge plus - Diagnostic complet', category: 'iPhone', views: 342},
            {title: 'Problème port Lightning', category: 'Connectique', views: 256},
            {title: 'Tester adaptateur secteur', category: 'Accessoires', views: 189}
        ]
    },
    'ecran': {
        response: 'Problème d\'affichage identifié. Solutions possibles :',
        articles: [
            {title: 'Ecran noir au démarrage - Solutions', category: 'Affichage', views: 428},
            {title: 'Problème tactile iPhone', category: 'Ecran', views: 312},
            {title: 'Remplacement écran LCD', category: 'Réparation', views: 245}
        ]
    },
    'tactile': {
        response: 'Dysfonctionnement tactile. Diagnostic recommandé :',
        articles: [
            {title: 'Calibration écran tactile', category: 'Ecran', views: 298},
            {title: 'Problème tactile après chute', category: 'Diagnostic', views: 267},
            {title: 'Remplacement digitizer', category: 'Réparation', views: 201}
        ]
    }
};

function setQuery(text) {
    document.getElementById('ai-search').value = text;
    searchAI();
}

function searchAI() {
    const query = document.getElementById('ai-search').value.toLowerCase();
    if (!query) return;
    
    const resultsDiv = document.getElementById('ai-results');
    resultsDiv.classList.remove('d-none');
    
    // Simulate AI thinking
    document.getElementById('ai-response').innerHTML = '<div class="spinner-border spinner-border-sm text-primary me-2"></div>Analyse en cours...';
    document.getElementById('ai-articles').innerHTML = '';
    
    setTimeout(() => {
        let data = mockKB.charge; // default
        if (query.includes('ecran') || query.includes('noir')) data = mockKB.ecran;
        if (query.includes('tactile') || query.includes('touch')) data = mockKB.tactile;
        
        document.getElementById('ai-response').innerHTML = `
            <p class="mb-3">${data.response}</p>
            <ol class="mb-0">
                <li>Vérifier les connexions</li>
                <li>Tester avec un autre câble</li>
                <li>Nettoyer les contacts</li>
                <li>Vérifier l'état du composant</li>
            </ol>
        `;
        
        document.getElementById('ai-articles').innerHTML = data.articles.map(a => `
            <div class="card mb-2">
                <div class="card-body p-3">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h6 class="mb-1">${a.title}</h6>
                            <small class="text-muted">
                                <span class="badge bg-secondary me-1">${a.category}</span>
                                ${a.views} vues
                            </small>
                        </div>
                        <div class="col-md-4 text-md-end">
                            <button class="btn btn-sm btn-primary">
                                <i class="fa-solid fa-arrow-right"></i> Voir
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `).join('');
    }, 1000);
}
</script>
