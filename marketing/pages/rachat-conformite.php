<?php
/**
 * Landing Page - Rachat Conformité
 * Unique : Photo ID + Appareil pour protection légale
 */
?>

<!-- Hero Section Rachat -->
<section class="bg-gradient-hero text-white position-relative overflow-hidden">
    <div class="container py-5">
        <div class="row align-items-center min-vh-75 py-5">
            <div class="col-lg-6 order-2 order-lg-1">
                <div class="pe-lg-5">
                    <div class="badge bg-success text-white mb-4 px-3 py-2 fade-in-left">
                        <i class="fa-solid fa-shield-check me-2"></i>
                        Protection légale
                    </div>
                    
                    <h1 class="display-4 fw-black mb-4 fade-in-left">
                        Ra chetez en toute<br>sécurité
                    </h1>
                    
                    <p class="fs-5 mb-4 opacity-90 fade-in-left">
                        Photo de la pièce d'identité + de l'appareil + signature électronique. 
                        Vous êtes 100% protégé légalement en cas de recel.
                    </p>
                    
                    <div class="d-flex flex-column flex-sm-row gap-3 mb-4 fade-in-left">
                        <a href="/inscription" class="btn btn-success btn-lg">
                            <i class="fa-solid fa-rocket me-2"></i>
                            Essai gratuit 30 jours
                        </a>
                        <a href="#demo-rachat" class="btn btn-outline-light btn-lg">
                            <i class="fa-solid fa-camera me-2"></i>
                            Voir la démo
                        </a>
                    </div>
                    
                    <div class="d-flex flex-wrap gap-4 text-white-50">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fa-solid fa-id-card text-success"></i>
                            <small>Photo ID obligatoire</small>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <i class="fa-solid fa-signature"></i>
                            <small>Signature électronique</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6 order-1 order-lg-2 mb-4 mb-lg-0">
                <div class="position-relative">
                    <div class="card-modern p-4 bg-white bg-opacity-95 text-dark fade-in-right" style="max-width: 350px; margin: 0 auto;">
                        <h6 class="fw-bold mb-3">Rachat sécurisé</h6>
                        
                        <div class="d-flex gap-2 mb-3">
                            <div class="flex-grow-1 bg-light rounded p-2 text-center">
                                <i class="fa-solid fa-id-card text-success fs-3 mb-2"></i>
                                <p class="small mb-0">Pièce ID</p>
                            </div>
                            <div class="flex-grow-1 bg-light rounded p-2 text-center">
                                <i class="fa-solid fa-mobile-screen text-success fs-3 mb-2"></i>
                                <p class="small mb-0">Appareil</p>
                            </div>
                        </div>
                        
                        <div class="bg-success bg-opacity-10 rounded p-3 mb-3">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <i class="fa-solid fa-check-circle text-success"></i>
                                <small><strong>Identité vérifiée</strong></small>
                            </div>
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <i class="fa-solid fa-check-circle text-success"></i>
                                <small><strong>Photos horodatées</strong></small>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <i class="fa-solid fa-check-circle text-success"></i>
                                <small><strong>Signature capturée</strong></small>
                            </div>
                        </div>
                        
                        <button class="btn btn-success w-100">
                            <i class="fa-solid fa-file-pdf me-2"></i>
                            Générer le PDF
                        </button>
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
                <div class="h2 fw-black text-primary mb-1">100%</div>
                <div class="text-muted">Conforme loi</div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="h2 fw-black text-success mb-1">3</div>
                <div class="text-muted">Photos obligatoires</div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="h2 fw-black text-warning mb-1">PDF</div>
                <div class="text-muted">Preuve générée</div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="h2 fw-black text-info mb-1">0</div>
                <div class="text-muted">Risque légal</div>
            </div>
        </div>
    </div>
</section>

<!-- DÉMO INTERACTIVE -->
<section class="section" id="demo-rachat" style="background: linear-gradient(180deg, var(--bg-secondary) 0%, var(--bg-primary) 100%);">
    <div class="container">
        <div class="text-center mb-5">
            <div class="badge bg-success mb-3 px-3 py-2">
                <i class="fa-solid fa-camera me-2"></i>
                Démo interactive
            </div>
            <h2 class="fw-black mb-3">Simulez un rachat protégé</h2>
            <p class="text-muted fs-5">Voyez comment capturer les preuves légales</p>
        </div>
        
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card-modern p-4">
                    <div class="row g-4">
                        <div class="col-md-4">
                            <div class="card h-100" id="step-id" style="cursor: pointer;" onclick="capturePhoto('id')">
                                <div class="card-body text-center p-4">
                                    <div class="bg-light rounded mb-3 d-flex align-items-center justify-content-center" style="height: 200px;" id="preview-id">
                                        <div>
                                            <i class="fa-solid fa-id-card text-muted fs-1 mb-2"></i>
                                            <p class="text-muted">Cliquez pour simuler</p>
                                        </div>
                                    </div>
                                    <h6 class="fw-bold">1. Pièce d'identité</h6>
                                    <small class="text-muted">CNI, Passport, Permis</small>
                                    <div class="mt-2" id="status-id"></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card h-100" id="step-device" style="cursor: pointer; opacity: 0.5;" onclick="capturePhoto('device')">
                                <div class="card-body text-center p-4">
                                    <div class="bg-light rounded mb-3 d-flex align-items-center justify-content-center" style="height: 200px;" id="preview-device">
                                        <div>
                                            <i class="fa-solid fa-mobile-screen text-muted fs-1 mb-2"></i>
                                            <p class="text-muted">Étape 2</p>
                                        </div>
                                    </div>
                                    <h6 class="fw-bold">2. Appareil</h6>
                                    <small class="text-muted">Photo de l'appareil racheté</small>
                                    <div class="mt-2" id="status-device"></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card h-100" id="step-signature" style="cursor: pointer; opacity: 0.5;" onclick="capturePhoto('signature')">
                                <div class="card-body text-center p-4">
                                    <div class="bg-light rounded mb-3 d-flex align-items-center justify-content-center" style="height: 200px;" id="preview-signature">
                                        <div>
                                            <i class="fa-solid fa-signature text-muted fs-1 mb-2"></i>
                                            <p class="text-muted">Étape 3</p>
                                        </div>
                                    </div>
                                    <h6 class="fw-bold">3. Signature</h6>
                                    <small class="text-muted">Signature électronique vendeur</small>
                                    <div class="mt-2" id="status-signature"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4 text-center" id="complete-section" style="display: none;">
                        <div class="alert alert-success">
                            <i class="fa-solid fa-check-circle me-2"></i>
                            Toutes les preuves capturées ! PDF de rachat prêt.
                        </div>
                        <button class="btn btn-success btn-lg" onclick="downloadPDF()">
                            <i class="fa-solid fa-file-pdf me-2"></i>
                            Télécharger le PDF de rachat
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Protection légale -->
<section class="section bg-white">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-black mb-3">Vous êtes protégé légalement</h2>
        </div>
        
        <div class="row g-4">
            <div class="col-lg-3 col-md-6">
                <div class="card-feature p-4 text-center h-100">
                    <i class="fa-solid fa-id-card text-primary fs-1 mb-3"></i>
                    <h6 class="fw-bold">Identité vérifiée</h6>
                    <p class="text-muted small">Photo recto/verso pièce d'identité obligatoire</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="card-feature p-4 text-center h-100">
                    <i class="fa-solid fa-mobile-screen text-success fs-1 mb-3"></i>
                    <h6 class="fw-bold">Appareil photographié</h6>
                    <p class="text-muted small">Photo de l'appareil avec IMEI visible</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="card-feature p-4 text-center h-100">
                    <i class="fa-solid fa-signature text-warning fs-1 mb-3"></i>
                    <h6 class="fw-bold">Signature vendeur</h6>
                    <p class="text-muted small">Signature électronique horodatée juridiquement valable</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="card-feature p-4 text-center h-100">
                    <i class="fa-solid fa-file-pdf text-danger fs-1 mb-3"></i>
                    <h6 class="fw-bold">PDF certifié</h6>
                    <p class="text-muted small">Document officiel avec toutes les preuves</p>
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
                <h2 class="fw-black mb-4">Rachetez en toute sérénité</h2>
                <p class="fs-5 mb-4 opacity-90">
                    Protection maximale contre le recel. Toutes les preuves en un PDF.
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
let captured = {id: false, device: false, signature: false};

function capturePhoto(type) {
    const prev = type === 'id' ? null : (type === 'device' ? 'id' : 'device');
    
    if (prev && !captured[prev]) {
        alert('Complétez d\'abord l\'étape précédente');
        return;
    }
    
    // Simulate capture
    document.getElementById(`preview-${type}`).innerHTML = `
        <div class="spinner-border text-primary"></div>
        <p class="text-muted mt-2">Capture...</p>
    `;
    
    setTimeout(() => {
        captured[type] = true;
        document.getElementById(`preview-${type}`).innerHTML = `
            <i class="fa-solid fa-check-circle text-success" style="font-size: 4rem;"></i>
            <p class="text-success mt-2">Capturé !</p>
        `;
        document.getElementById(`status-${type}`).innerHTML = '<span class="badge bg-success">✓ Validé</span>';
        
        // Enable next step
        if (type === 'id') {
            document.getElementById('step-device').style.opacity = '1';
        } else if (type === 'device') {
            document.getElementById('step-signature').style.opacity = '1';
        } else if (type === 'signature') {
            document.getElementById('complete-section').style.display = 'block';
        }
    }, 1500);
}

function downloadPDF() {
    alert('En production, un PDF sécurisé serait généré avec toutes les preuves horodatées.');
}
</script>
