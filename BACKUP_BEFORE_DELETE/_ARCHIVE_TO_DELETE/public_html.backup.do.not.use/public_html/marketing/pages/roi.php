<!-- ROI Hero -->
<section class="section bg-gradient-primary text-white">
    <div class="container">
        <div class="text-center mb-5">
            <div class="badge bg-dark text-white mb-3 px-3 py-2">
                <i class="fa-solid fa-calculator me-2"></i>
                Calculateur ROI
            </div>
            <h1 class="display-4 fw-black mb-4">Calculez vos économies avec GeekBoard</h1>
            <p class="fs-5 opacity-90 mb-0">
                Estimez précisément le temps et l'argent que vous allez économiser. 
                Basé sur les retours de nos 250+ clients.
            </p>
        </div>
    </div>
</section>

<!-- Calculator -->
<section class="section-sm bg-white">
    <div class="container">
        <div class="row g-5 align-items-start">
            
            <!-- Calculator Form -->
            <div class="col-lg-5">
                <div class="card-modern p-4 sticky-top">
                    <h4 class="fw-bold mb-4">Vos informations</h4>
                    
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Nombre d'employés</label>
                        <input id="employees" type="range" class="form-range" min="1" max="20" value="5" oninput="updateValue('employees', this.value); calculate();">
                        <div class="d-flex justify-content-between text-muted small">
                            <span>1</span>
                            <span id="employees-value" class="fw-bold text-primary">5</span>
                            <span>20+</span>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Coût horaire moyen (€)</label>
                        <input id="hourly-cost" type="range" class="form-range" min="10" max="50" value="22" oninput="updateValue('hourly-cost', this.value); calculate();">
                        <div class="d-flex justify-content-between text-muted small">
                            <span>10€</span>
                            <span id="hourly-cost-value" class="fw-bold text-primary">22€</span>
                            <span>50€</span>
                        </div>
                    </div>
                    
                    <!-- Results Card -->
                    <div class="bg-gradient-primary text-white rounded-3 p-4 mt-4">
                        <h5 class="fw-bold mb-3">💰 Vos économies mensuelles</h5>
                        
                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <div class="text-center">
                                    <div id="hours-saved" class="h4 fw-black">52h</div>
                                    <small class="opacity-90">Heures gagnées</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="text-center">
                                    <div id="money-saved" class="h4 fw-black">1 144€</div>
                                    <small class="opacity-90">Économies</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-center">
                            <div class="h6 opacity-90">ROI en <span id="roi-months" class="fw-bold">2 mois</span></div>
                        </div>
                        
                        <hr class="my-3 opacity-25">
                        
                        <div class="text-center">
                            <a href="/contact" class="btn btn-light btn-sm">
                                <i class="fa-solid fa-calendar-days me-2"></i>
                                Voir ma démo personnalisée
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Benefits Breakdown -->
            <div class="col-lg-7">
                <h4 class="fw-bold mb-4">D'où viennent ces gains de temps ?</h4>
                
                <div class="row g-4">
                    
                    <div class="col-12">
                        <div class="card-feature p-4">
                            <div class="d-flex align-items-start gap-3">
                                <div class="bg-danger bg-opacity-10 text-danger rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 60px; height: 60px;">
                                    <i class="fa-solid fa-phone-slash fs-4"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold mb-2">Plus besoin d'appeler les clients</h6>
                                    <p class="text-muted mb-2">
                                        Les SMS automatiques informent quand une réparation est prête, annulée ou en attente. 
                                        <strong>Économie : 8 min/jour/employé</strong>
                                    </p>
                                    <div class="progress" style="height: 6px;">
                                        <div class="progress-bar bg-danger" style="width: 32%"></div>
                                    </div>
                                    <small class="text-muted">32% du gain total</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-12">
                        <div class="card-feature p-4">
                            <div class="d-flex align-items-start gap-3">
                                <div class="bg-warning bg-opacity-10 text-warning rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 60px; height: 60px;">
                                    <i class="fa-solid fa-list-check fs-4"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold mb-2">File d'attente intelligente</h6>
                                    <p class="text-muted mb-2">
                                        Plus de recherche "quelle réparation faire ensuite ?". L'écran atelier affiche la priorité. 
                                        <strong>Économie : 7 min/jour/employé</strong>
                                    </p>
                                    <div class="progress" style="height: 6px;">
                                        <div class="progress-bar bg-warning" style="width: 28%"></div>
                                    </div>
                                    <small class="text-muted">28% du gain total</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-12">
                        <div class="card-feature p-4">
                            <div class="d-flex align-items-start gap-3">
                                <div class="bg-success bg-opacity-10 text-success rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 60px; height: 60px;">
                                    <i class="fa-solid fa-bolt fs-4"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold mb-2">Prise en charge express (3 minutes)</h6>
                                    <p class="text-muted mb-2">
                                        Création ultra-rapide du ticket avec modèles et champs intelligents. 
                                        <strong>Économie : 4 min/réparation</strong>
                                    </p>
                                    <div class="progress" style="height: 6px;">
                                        <div class="progress-bar bg-success" style="width: 20%"></div>
                                    </div>
                                    <small class="text-muted">20% du gain total</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-12">
                        <div class="card-feature p-4">
                            <div class="d-flex align-items-start gap-3">
                                <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 60px; height: 60px;">
                                    <i class="fa-solid fa-file-signature fs-4"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold mb-2">Devis : acceptation autonome client</h6>
                                    <p class="text-muted mb-2">
                                        Envoi par SMS avec validation en 1 clic. Plus de relances, démarrage immédiat. 
                                        <strong>Économie : 3 min/devis</strong>
                                    </p>
                                    <div class="progress" style="height: 6px;">
                                        <div class="progress-bar bg-primary" style="width: 12%"></div>
                                    </div>
                                    <small class="text-muted">12% du gain total</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-12">
                        <div class="card-feature p-4">
                            <div class="d-flex align-items-start gap-3">
                                <div class="bg-info bg-opacity-10 text-info rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 60px; height: 60px;">
                                    <i class="fa-solid fa-magnifying-glass fs-4"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold mb-2">Inventaire : recherche directe fournisseur</h6>
                                    <p class="text-muted mb-2">
                                        Le bouton magique ouvre la page partenaire de la pièce. Réassort instantané. 
                                        <strong>Économie : 3 min/recherche</strong>
                                    </p>
                                    <div class="progress" style="height: 6px;">
                                        <div class="progress-bar bg-info" style="width: 8%"></div>
                                    </div>
                                    <small class="text-muted">8% du gain total</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Comparison Table -->
<section class="section bg-gradient-primary text-white">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold mb-3">Avant / Après GeekBoard</h2>
            <p class="opacity-90">L'impact concret sur votre quotidien</p>
        </div>
        
        <div class="row g-4 justify-content-center">
            <div class="col-lg-10">
                <div class="table-responsive bg-white text-dark rounded-3 p-3">
                    <table class="table table-borderless mb-0">
                        <thead>
                            <tr class="border-bottom">
                                <th class="fw-bold" style="width: 40%;">Situation</th>
                                <th class="fw-bold text-danger text-center" style="width: 30%;">😰 Avant</th>
                                <th class="fw-bold text-success text-center" style="width: 30%;">😎 Après</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="py-3"><strong>Client demande l'état de sa réparation</strong></td>
                                <td class="py-3 text-center text-danger">5 min de recherche + appel</td>
                                <td class="py-3 text-center text-success">SMS automatique envoyé</td>
                            </tr>
                            <tr class="bg-light">
                                <td class="py-3"><strong>Technicien cherche la prochaine tâche</strong></td>
                                <td class="py-3 text-center text-danger">5 min de navigation</td>
                                <td class="py-3 text-center text-success">Affiché sur son écran</td>
                            </tr>
                            <tr>
                                <td class="py-3"><strong>Client veut valider un devis</strong></td>
                                <td class="py-3 text-center text-danger">Appel + RDV + paperasse</td>
                                <td class="py-3 text-center text-success">1 clic sur le lien SMS</td>
                            </tr>
                            <tr class="bg-light">
                                <td class="py-3"><strong>Rupture de stock détectée</strong></td>
                                <td class="py-3 text-center text-danger">Recherche manuelle</td>
                                <td class="py-3 text-center text-success">Bouton → page fournisseur</td>
                            </tr>
                            <tr>
                                <td class="py-3"><strong>Prise en charge nouveau client</strong></td>
                                <td class="py-3 text-center text-danger">8 min de saisie</td>
                                <td class="py-3 text-center text-success">3 min avec modèles</td>
                            </tr>
                        </tbody>
                    </table>
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
                <h2 class="fw-black mb-3">Le cerveau qui organise votre entreprise</h2>
                <div class="badge bg-dark text-white mb-3 px-3 py-2">GRATUIT PENDANT 1 MOIS</div>
                <p class="fs-5 mb-4 opacity-90">Après cela vous ne pourrez plus vous en séparer.</p>

                <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center mb-4">
                    <a href="/inscription" class="btn btn-light btn-lg">
                        <i class="fa-solid fa-rocket me-2"></i>
                        J'en profite
                    </a>
                </div>

                <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center small">
                    <span class="opacity-90">Essai gratuit 30 jours</span>
                    <span class="opacity-50 d-none d-sm-inline">•</span>
                    <span class="opacity-90">Sans engagement</span>
                    <span class="opacity-50 d-none d-sm-inline">•</span>
                    <span class="opacity-90">Support inclus</span>
                </div>
            </div>
        </div>
    </div>
    </section>

<script>
function updateValue(id, value) {
    document.getElementById(id + '-value').textContent = value + (id === 'hourly-cost' ? '€' : '');
}

function calculate() {
    const employees = parseInt(document.getElementById('employees').value);
    const hourlyCost = parseInt(document.getElementById('hourly-cost').value);
    
    // Time savings calculation based on our breakdown
    const dailyTimeSavingPerEmployee = 25; // minutes per day per employee
    const monthlyMinutesSaved = employees * dailyTimeSavingPerEmployee * 22; // 22 working days
    const monthlyHoursSaved = Math.round(monthlyMinutesSaved / 60);
    const monthlySavings = Math.round(monthlyHoursSaved * hourlyCost);
    
    // ROI calculation (assuming GeekBoard costs ~99€/month for Professional)
    const monthlyCost = 99;
    const roiMonths = Math.max(1, Math.round(monthlyCost / (monthlySavings - monthlyCost)));
    
    // Update display
    document.getElementById('hours-saved').textContent = monthlyHoursSaved + 'h';
    document.getElementById('money-saved').textContent = monthlySavings.toLocaleString() + '€';
    document.getElementById('roi-months').textContent = roiMonths + ' mois';
}

// Initialize calculation
calculate();
</script>