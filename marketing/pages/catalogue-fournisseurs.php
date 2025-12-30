<?php
/**
 * Landing Page - Catalogue Multi-Fournisseurs
 * Unique : Recherche simultanée dans plusieurs catalogues
 */
?>

<!-- Hero Section Catalogue -->
<section class="bg-gradient-hero text-white position-relative overflow-hidden">
    <div class="container py-5">
        <div class="row align-items-center min-vh-75 py-5">
            <div class="col-lg-6 order-2 order-lg-1">
                <div class="pe-lg-5">
                    <div class="badge bg-warning text-dark mb-4 px-3 py-2 fade-in-left">
                        <i class="fa-solid fa-boxes-stacked me-2"></i>
                        Multi-fournisseurs
                    </div>
                    
                    <h1 class="display-4 fw-black mb-4 fade-in-left">
                        10 catalogues<br>en 1 recherche
                    </h1>
                    
                    <p class="fs-5 mb-4 opacity-90 fade-in-left">
                        Recherchez une pièce dans Mobilax, Utopya, Fixya, eMagic et 6 autres fournisseurs simultanément. 
                        Comparez prix et stocks en temps réel.
                    </p>
                    
                    <div class="d-flex flex-column flex-sm-row gap-3 mb-4 fade-in-left">
                        <a href="/inscription" class="btn btn-warning btn-lg text-dark">
                            <i class="fa-solid fa-rocket me-2"></i>
                            Essai gratuit 30 jours
                        </a>
                        <a href="#demo-catalogue" class="btn btn-outline-light btn-lg">
                            <i class="fa-solid fa-search me-2"></i>
                            Tester la recherche
                        </a>
                    </div>
                    
                    <div class="d-flex flex-wrap gap-4 text-white-50">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fa-solid fa-bolt text-warning"></i>
                            <small>Résultats en 2 secondes</small>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <i class="fa-solid fa-list"></i>
                            <small>10+ fournisseurs</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6 order-1 order-lg-2 mb-4 mb-lg-0">
                <div class="position-relative">
                    <div class="card-modern p-4 bg-white bg-opacity-95 text-dark fade-in-right" style="max-width: 400px; margin: 0 auto;">
                        <h6 class="fw-bold mb-3">Recherche multi-catalogues</h6>
                        
                        <div class="input-group mb-3" style="border-radius: var(--border-radius); overflow: hidden;">
                            <input type="text" class="form-control" placeholder="Ex: Ecran iPhone 14" disabled value="Ecran iPhone 14">
                            <button class="btn btn-warning"><i class="fa-solid fa-search"></i></button>
                        </div>
                        
                        <div class="small text-muted mb-2">Recherche dans 10 fournisseurs...</div>
                        
                        <div class="d-flex gap-2 flex-wrap mb-3">
                            <span class="badge bg-success">Mobilax ✓</span>
                            <span class="badge bg-success">Utopya ✓</span>
                            <span class="badge bg-success">Fixya ✓</span>
                            <span class="badge bg-secondary">eMagic...</span>
                        </div>
                        
                        <div class="bg-light rounded p-2 mb-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <small class="fw-bold">Ecran LCD Original</small><br>
                                    <small class="text-success">En stock • Mobilax</small>
                                </div>
                                <strong class="text-warning">189€</strong>
                            </div>
                        </div>
                        
                        <div class="bg-light rounded p-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <small class="fw-bold">Ecran OLED Premium</small><br>
                                    <small class="text-success">En stock • Utopya</small>
                                </div>
                                <strong class="text-warning">149€</strong>
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
                <div class="h2 fw-black text-primary mb-1">10+</div>
                <div class="text-muted">Fournisseurs</div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="h2 fw-black text-success mb-1">200K+</div>
                <div class="text-muted">Références</div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="h2 fw-black text-warning mb-1">-40%</div>
                <div class="text-muted">Prix moyen</div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="h2 fw-black text-info mb-1">2 sec</div>
                <div class="text-muted">Résultats</div>
            </div>
        </div>
    </div>
</section>

<!-- DÉMO INTERACTIVE -->
<section class="section" id="demo-catalogue" style="background: linear-gradient(180deg, var(--bg-secondary) 0%, var(--bg-primary) 100%);">
    <div class="container">
        <div class="text-center mb-5">
            <div class="badge bg-warning text-dark mb-3 px-3 py-2">
                <i class="fa-solid fa-flask me-2"></i>
                Démo interactive
            </div>
            <h2 class="fw-black mb-3">Recherchez dans 10 catalogues simultanément</h2>
            <p class="text-muted fs-5">Comparez prix et stocks en temps réel</p>
        </div>
        
        <div class="row g-4">
            <div class="col-lg-12">
                <div class="card-modern p-4">
                    <div class="row g-4">
                        <div class="col-lg-4">
                            <h6 class="fw-bold mb-3">Recherche</h6>
                            <input type="text" id="search-input" class="form-control mb-3" placeholder="Ex: Ecran iPhone 14, Batterie Samsung..."
                                   style="border-radius: var(--border-radius); border: 2px solid var(--border-color);">
                            
                            <button class="btn btn-warning w-100 mb-4" onclick="searchCatalogue()">
                                <i class="fa-solid fa-search me-2"></i>
                                Rechercher
                            </button>
                            
                            <h6 class="fw-bold mb-3">Fournisseurs actifs</h6>
                            <div id="supplier-status" class="d-flex flex-column gap-2">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="spinner-border spinner-border-sm text-secondary d-none" data-supplier="mobilax"></div>
                                        <i class="fa-solid fa-circle-check text-muted" id="icon-mobilax"></i>
                                        <span>Mobilax</span>
                                    </div>
                                    <span class="badge bg-secondary" id="count-mobilax">0</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="spinner-border spinner-border-sm text-secondary d-none" data-supplier="utopya"></div>
                                        <i class="fa-solid fa-circle-check text-muted" id="icon-utopya"></i>
                                        <span>Utopya</span>
                                    </div>
                                    <span class="badge bg-secondary" id="count-utopya">0</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="spinner-border spinner-border-sm text-secondary d-none" data-supplier="fixya"></div>
                                        <i class="fa-solid fa-circle-check text-muted" id="icon-fixya"></i>
                                        <span>Fixya</span>
                                    </div>
                                    <span class="badge bg-secondary" id="count-fixya">0</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="spinner-border spinner-border-sm text-secondary d-none" data-supplier="emagic"></div>
                                        <i class="fa-solid fa-circle-check text-muted" id="icon-emagic"></i>
                                        <span>eMagic</span>
                                    </div>
                                    <span class="badge bg-secondary" id="count-emagic">0</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-8">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="fw-bold mb-0">Résultats (<span id="total-results">0</span>)</h6>
                                <select class="form-select form-select-sm" style="width: auto;" onchange="sortResults(this.value)">
                                    <option value="price-asc">Prix croissant</option>
                                    <option value="price-desc">Prix décroissant</option>
                                    <option value="stock">En stock d'abord</option>
                                </select>
                            </div>
                            
                            <div id="results-container" style="max-height: 500px; overflow-y: auto;">
                                <div class="text-center text-muted py-5">
                                    <i class="fa-solid fa-search fs-1 mb-3 opacity-25"></i>
                                    <p>Lancez une recherche pour voir les résultats</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Fournisseurs intégrés -->
<section class="section bg-white">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-black mb-3">Tous vos fournisseurs en un seul endroit</h2>
            <p class="text-muted fs-5">Intégrations directes avec les plus grands catalogues</p>
        </div>
        
        <div class="row g-4 text-center">
            <div class="col-6 col-md-4 col-lg-2">
                <div class="card-feature p-3">
                    <div class="fw-bold">Mobilax</div>
                    <small class="text-success">50K+ pièces</small>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="card-feature p-3">
                    <div class="fw-bold">Utopya</div>
                    <small class="text-success">40K+ pièces</small>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="card-feature p-3">
                    <div class="fw-bold">Fixya</div>
                    <small class="text-success">35K+ pièces</small>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="card-feature p-3">
                    <div class="fw-bold">eMagic</div>
                    <small class="text-success">30K+ pièces</small>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="card-feature p-3">
                    <div class="fw-bold">AliExpress</div>
                    <small class="text-success">1M+ pièces</small>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="card-feature p-3">
                    <div class="fw-bold">+ 5 autres</div>
                    <small class="text-muted">En cours</small>
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
                <h2 class="fw-black mb-4">Gagnez des heures de recherche</h2>
                <p class="fs-5 mb-4 opacity-90">
                    Plus besoin de 10 onglets ouverts. Une recherche, tous les fournisseurs, le meilleur prix.
                </p>
                
                <a href="/inscription" class="btn btn-warning btn-lg text-dark">
                    <i class="fa-solid fa-rocket me-2"></i>
                    Démarrer l'essai gratuit
                </a>
            </div>
        </div>
    </div>
</section>

<script>
const mockData = {
    'ecran': [
        {name: 'Ecran LCD Original iPhone 14', price: 189, stock: true, supplier: 'mobilax'},
        {name: 'Ecran OLED Premium iPhone 14', price: 149, stock: true, supplier: 'utopya'},
        {name: 'Ecran LCD Compatible iPhone 14', price: 89, stock: true, supplier: 'fixya'},
        {name: 'Ecran Refurb iPhone 14', price: 129, stock: false, supplier: 'emagic'},
    ],
    'batterie': [
        {name: 'Batterie OEM Samsung S21', price: 45, stock: true, supplier: 'mobilax'},
        {name: 'Batterie Premium Samsung S21', price: 32, stock: true, supplier: 'utopya'},
        {name: 'Batterie Compatible S21', price: 25, stock: true, supplier: 'fixya'},
    ]
};

let currentResults = [];

function searchCatalog() {
    const query = document.getElementById('search-input').value.toLowerCase();
    if (!query) return;
    
    // Show spinners
    document.querySelectorAll('[data-supplier]').forEach(el => el.classList.remove('d-none'));
    document.querySelectorAll('[id^="icon-"]').forEach(el => el.className = 'fa-solid fa-spinner fa-spin text-secondary');
    
    // Clear results
    document.getElementById('results-container').innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary"></div><p class="text-muted mt-2">Recherche en cours...</p></div>';
    
    // Simulate search
    setTimeout(() => {
        const results = query.includes('ecran') || query.includes('iphone') ? mockData.ecran : 
                       query.includes('batterie') || query.includes('samsung') ? mockData.batterie : [];
        
        currentResults = results;
        displayResults(results);
        
        // Update supplier counts
        const counts = {mobilax: 0, utopya: 0, fixya: 0, emagic: 0};
        results.forEach(r => counts[r.supplier]++);
        
        Object.keys(counts).forEach(supplier => {
            document.querySelector(`[data-supplier="${supplier}"]`).classList.add('d-none');
            document.getElementById(`icon-${supplier}`).className = counts[supplier] > 0 ? 'fa-solid fa-circle-check text-success' : 'fa-solid fa-circle-xmark text-danger';
            document.getElementById(`count-${supplier}`).textContent = counts[supplier];
            document.getElementById(`count-${supplier}`).className = counts[supplier] > 0 ? 'badge bg-success' : 'badge bg-secondary';
        });
    }, 1500);
}

function displayResults(results) {
    const container = document.getElementById('results-container');
    document.getElementById('total-results').textContent = results.length;
    
    if (results.length === 0) {
        container.innerHTML = '<div class="text-center text-muted py-5"><i class="fa-solid fa-inbox fs-1 mb-3 opacity-25"></i><p>Aucun résultat trouvé</p></div>';
        return;
    }
    
    container.innerHTML = results.map(r => `
        <div class="card mb-3" style="border-radius: var(--border-radius);">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h6 class="fw-bold mb-1">${r.name}</h6>
                        <div class="d-flex gap-2">
                            <span class="badge bg-secondary">${r.supplier}</span>
                            <span class="badge ${r.stock ? 'bg-success' : 'bg-danger'}">${r.stock ? 'En stock' : 'Rupture'}</span>
                        </div>
                    </div>
                    <div class="col-md-3 text-md-end">
                        <div class="h5 fw-black text-warning mb-0">${r.price}€</div>
                    </div>
                    <div class="col-md-3 text-md-end">
                        <button class="btn btn-primary btn-sm">
                            <i class="fa-solid fa-cart-plus me-1"></i> Ajouter
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `).join('');
}

function sortResults(sortBy) {
    let sorted = [...currentResults];
    if (sortBy === 'price-asc') sorted.sort((a, b) => a.price - b.price);
    if (sortBy === 'price-desc') sorted.sort((a, b) => b.price - a.price);
    if (sortBy === 'stock') sorted.sort((a, b) => (b.stock ? 1 : 0) - (a.stock ? 1 : 0));
    displayResults(sorted);
}
</script>
