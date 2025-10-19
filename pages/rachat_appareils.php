<?php
// Vérifier si la session est déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Aucune restriction d'accès - tous les utilisateurs peuvent accéder à cette page
// Si vous souhaitez rétablir la restriction plus tard, décommentez le code ci-dessous
/*
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /pages/403.php');
    exit();
}
*/

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../config/subdomain_config.php';
require_once __DIR__ . '/../config/database.php';

$shop_pdo = getShopDBConnection();

// Afficher les données de session pour le débogage
if (isset($_GET['debug']) && $_GET['debug'] == 1) {
    echo '<pre>Session: ' . print_r($_SESSION, true) . '</pre>';
}

// Liste des clients (fallback si AJAX échoue)
$clients = [];
try {
    // Vérifier que la connexion à la base de données est établie
    if (isset($shop_pdo) && $shop_pdo !== null) {
        $stmt = $shop_pdo->prepare("SELECT id, nom, prenom FROM clients ORDER BY nom, prenom");
        $stmt->execute();
        $clients = $stmt->fetchAll();
    } else {
        error_log("Erreur: La connexion à la base de données n'est pas disponible");
    }
} catch (Exception $e) {
    error_log("Erreur lors de la récupération des clients: " . $e->getMessage());
}

?>

<!-- Style personnalisé pour décaler le contenu vers le bas -->
<style>
.main-content {
    padding-top: 60px !important; /* Ajouter 60px de padding en haut */
}

/* ===== TABLEAU ADAPTATIF CLAIR/NUIT SANS BOOTSTRAP ===== */

.clean-table-wrapper {
    margin: 20px 0;
    border-radius: 12px;
    overflow: hidden;
    /* Couleurs par défaut (mode clair) */
    background: #ffffff;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
    border: 1px solid #e5e7eb;
}

/* Mode nuit */
body.dark-mode .clean-table-wrapper {
    background: #1f2937;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
    border: 1px solid #374151;
}

.clean-data-table {
    width: 100%;
    border-collapse: collapse;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', sans-serif;
    font-size: 14px;
}

/* ===== EN-TÊTE ADAPTATIF ===== */
.clean-table-head {
    /* Mode clair par défaut */
    background: #f8fafc;
    border-bottom: 2px solid #e5e7eb;
}

body.dark-mode .clean-table-head {
    background: #111827;
    border-bottom: 2px solid #4b5563;
}

.clean-th {
    padding: 16px 20px;
    text-align: left;
    font-weight: 600;
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border: none;
    /* Mode clair par défaut */
    color: #374151;
}

body.dark-mode .clean-th {
    color: #f9fafb;
}

.clean-th-right { text-align: right; }
.clean-th-center { text-align: center; }

.clean-th i {
    margin-right: 8px;
    font-size: 12px;
    /* Mode clair par défaut */
    color: #6b7280;
}

body.dark-mode .clean-th i {
    color: #9ca3af;
}

/* ===== CORPS DU TABLEAU ADAPTATIF ===== */
.clean-table-body tr {
    /* Mode clair par défaut */
    border-bottom: 1px solid #f3f4f6;
    background: #ffffff;
}

body.dark-mode .clean-table-body tr {
    border-bottom: 1px solid #374151;
    background: #1f2937;
}

.clean-table-body tr:nth-child(even) {
    /* Mode clair par défaut */
    background: #f9fafb;
}

body.dark-mode .clean-table-body tr:nth-child(even) {
    background: #263040;
}

.clean-table-body tr:hover {
    /* Mode clair par défaut */
    background: #f0f9ff;
}

body.dark-mode .clean-table-body tr:hover {
    background: #1e3a8a;
}

.clean-table-body td {
    padding: 16px 20px;
    vertical-align: middle;
    border: none;
    /* Mode clair par défaut */
    color: #111827;
}

body.dark-mode .clean-table-body td {
    color: #f3f4f6;
}

/* ===== ÉLÉMENTS ADAPTATIFS ===== */
.clean-client-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.clean-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: #3b82f6;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
    font-size: 13px;
    /* Mode clair par défaut */
    border: 2px solid #e5e7eb;
}

body.dark-mode .clean-avatar {
    border: 2px solid #4b5563;
}

.clean-client-name {
    font-weight: 500;
    /* Mode clair par défaut */
    color: #111827;
}

body.dark-mode .clean-client-name {
    color: #f9fafb;
}

.clean-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.clean-badge-success {
    /* Mode clair par défaut */
    background: #dcfce7;
    color: #166534;
    border: 1px solid #22c55e;
}

body.dark-mode .clean-badge-success {
    background: #065f46;
    color: #6ee7b7;
    border: 1px solid #10b981;
}

.clean-badge-danger {
    /* Mode clair par défaut */
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #ef4444;
}

body.dark-mode .clean-badge-danger {
    background: #7f1d1d;
    color: #fca5a5;
    border: 1px solid #ef4444;
}

.clean-price {
    font-weight: 600;
    text-align: right;
    /* Mode clair par défaut */
    color: #059669;
}

body.dark-mode .clean-price {
    color: #6ee7b7;
}

/* ===== BOUTONS ADAPTATIFS ===== */
.clean-btn-group {
    display: flex;
    gap: 6px;
    align-items: center;
}

.clean-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 6px;
    text-decoration: none;
    cursor: pointer;
    /* Mode clair par défaut */
    border: 1px solid #d1d5db;
    background: #ffffff;
    color: #6b7280;
}

body.dark-mode .clean-btn {
    border: 1px solid #4b5563;
    background: #374151;
    color: #d1d5db;
}

.clean-btn:hover {
    /* Mode clair par défaut */
    background: #f3f4f6;
    border-color: #9ca3af;
    color: #374151;
}

body.dark-mode .clean-btn:hover {
    background: #4b5563;
    border-color: #6b7280;
    color: #f3f4f6;
}

.clean-btn-primary {
    /* Mode clair par défaut */
    background: #3b82f6;
    border-color: #3b82f6;
    color: white;
}

body.dark-mode .clean-btn-primary {
    background: #1d4ed8;
    border-color: #1d4ed8;
}

.clean-btn-primary:hover {
    /* Mode clair par défaut */
    background: #2563eb;
    border-color: #2563eb;
}

body.dark-mode .clean-btn-primary:hover {
    background: #1e40af;
    border-color: #1e40af;
}

.clean-btn-success {
    /* Mode clair par défaut */
    background: #10b981;
    border-color: #10b981;
    color: white;
}

body.dark-mode .clean-btn-success {
    background: #047857;
    border-color: #047857;
}

.clean-btn-success:hover {
    /* Mode clair par défaut */
    background: #059669;
    border-color: #059669;
}

body.dark-mode .clean-btn-success:hover {
    background: #065f46;
    border-color: #065f46;
}

.clean-btn i {
    font-size: 12px;
}

/* ===== RESPONSIVE ===== */
@media (max-width: 768px) {
    .clean-table-wrapper {
        display: none;
    }
}

@media (min-width: 769px) {
    .d-md-none {
        display: none !important;
    }
}

/* ===== MESSAGES ADAPTATIFS ===== */
.clean-no-data {
    padding: 40px;
    text-align: center;
    /* Mode clair par défaut */
    color: #6b7280;
}

body.dark-mode .clean-no-data {
    color: #9ca3af;
}

.clean-no-data i {
    font-size: 32px;
    margin-bottom: 12px;
    /* Mode clair par défaut */
    color: #d1d5db;
}

body.dark-mode .clean-no-data i {
    color: #6b7280;
}

.clean-no-data h4 {
    margin: 0 0 8px 0;
    font-weight: 500;
    /* Mode clair par défaut */
    color: #374151;
}

body.dark-mode .clean-no-data h4 {
    color: #d1d5db;
}

.clean-no-data p {
    margin: 0;
    /* Mode clair par défaut */
    color: #6b7280;
}

body.dark-mode .clean-no-data p {
    color: #9ca3af;
}

.clean-text-muted {
    font-style: italic;
    /* Mode clair par défaut */
    color: #9ca3af;
}

body.dark-mode .clean-text-muted {
    color: #6b7280;
}

/* ===== CORRECTION MODALS PHOTOS MODE NUIT ===== */
body.dark-mode .modal-content {
    background-color: #1f2937 !important;
    border: 1px solid #374151 !important;
}

body.dark-mode .modal-header {
    background-color: #111827 !important;
    border-bottom: 1px solid #4b5563 !important;
}

body.dark-mode .modal-title {
    color: #f9fafb !important;
}

body.dark-mode .modal-body {
    background-color: #1f2937 !important;
    color: #f3f4f6 !important;
}

/* ===== CORRECTION IMAGES MODALS MODE NUIT ===== */

/* Images génériques dans les modals */
body.dark-mode .modal-body img,
body.dark-mode .modal img {
    background-color: transparent !important;
    background: none !important;
    border: 1px solid #4b5563 !important;
    border-radius: 8px !important;
    filter: none !important;
    opacity: 1 !important;
    mix-blend-mode: normal !important;
}

/* Images spécifiques par ID */
body.dark-mode #modalIdentite,
body.dark-mode #modalAppareil,
body.dark-mode #modalPhotoClient,
body.dark-mode #modalSignature {
    background-color: transparent !important;
    background: none !important;
    border: 1px solid #4b5563 !important;
    border-radius: 8px !important;
    filter: none !important;
    opacity: 1 !important;
    mix-blend-mode: normal !important;
    display: block !important;
    max-width: 100% !important;
    height: auto !important;
}

/* Conteneurs des images */
body.dark-mode .modal-body .text-center,
body.dark-mode .modal-body div {
    background-color: transparent !important;
    background: none !important;
}

/* Images dans les modals détails */
body.dark-mode .modal-body .img-fluid,
body.dark-mode .modal-body .img-thumbnail {
    background-color: transparent !important;
    background: none !important;
    border: 1px solid #4b5563 !important;
    filter: none !important;
    opacity: 1 !important;
}

/* Forcer l'affichage normal des images base64 */
body.dark-mode img[src^="data:image"] {
    background-color: transparent !important;
    background: none !important;
    filter: none !important;
    opacity: 1 !important;
    mix-blend-mode: normal !important;
}

/* ===== TABLEAU RECHERCHE CLIENT MODERNE ===== */

.search-results-wrapper {
    margin: 20px 0;
    border-radius: 12px;
    overflow: hidden;
    background: #ffffff;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
    border: 1px solid #e5e7eb;
}

body.dark-mode .search-results-wrapper {
    background: #1f2937;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
    border: 1px solid #374151;
}

.search-table {
    width: 100%;
    border-collapse: collapse;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', sans-serif;
}

.search-table-head {
    background: #f8fafc;
    border-bottom: 2px solid #e5e7eb;
}

body.dark-mode .search-table-head {
    background: #111827;
    border-bottom: 2px solid #4b5563;
}

.search-th {
    padding: 16px 20px;
    text-align: left;
    font-weight: 600;
    font-size: 14px;
    letter-spacing: 0.025em;
    border: none;
    color: #374151;
}

body.dark-mode .search-th {
    color: #f9fafb;
}

.search-table-body tr {
    border-bottom: 1px solid #f3f4f6;
    background: #ffffff;
    transition: background-color 0.2s ease;
}

body.dark-mode .search-table-body tr {
    border-bottom: 1px solid #374151;
    background: #1f2937;
}

.search-table-body tr:nth-child(even) {
    background: #f9fafb;
}

body.dark-mode .search-table-body tr:nth-child(even) {
    background: #263040;
}

.search-table-body tr:hover {
    background: #f0f9ff;
}

body.dark-mode .search-table-body tr:hover {
    background: #1e3a8a;
}

.search-td {
    padding: 16px 20px;
    vertical-align: middle;
    border: none;
    color: #111827;
}

body.dark-mode .search-td {
    color: #f3f4f6;
}

.search-client-name {
    font-weight: 500;
    color: #111827;
}

body.dark-mode .search-client-name {
    color: #f9fafb;
}

.search-client-phone {
    color: #6b7280;
    font-size: 14px;
}

body.dark-mode .search-client-phone {
    color: #9ca3af;
}

.search-btn {
    padding: 8px 16px;
    border-radius: 8px;
    border: 1px solid #d1d5db;
    background: #ffffff;
    color: #374151;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.search-btn:hover {
    background: #f3f4f6;
    border-color: #9ca3af;
    transform: translateY(-1px);
}

.search-btn-primary {
    background: #3b82f6;
    border-color: #3b82f6;
    color: #ffffff;
}

.search-btn-primary:hover {
    background: #2563eb;
    border-color: #2563eb;
    color: #ffffff;
}

body.dark-mode .search-btn {
    border: 1px solid #4b5563;
    background: #374151;
    color: #f3f4f6;
}

body.dark-mode .search-btn:hover {
    background: #4b5563;
    border-color: #6b7280;
}

body.dark-mode .search-btn-primary {
    background: #1d4ed8;
    border-color: #1d4ed8;
    color: #ffffff;
}

body.dark-mode .search-btn-primary:hover {
    background: #1e40af;
    border-color: #1e40af;
}

/* ===== CORRECTION Z-INDEX CAMERA ===== */

.camera-preview {
    position: relative !important;
    z-index: 1070 !important; /* Plus haut que le modal (1055) */
}

#cameraVideo {
    position: relative !important;
    z-index: 1071 !important;
    background-color: #000 !important;
}

#cameraCanvas {
    position: relative !important;
    z-index: 1072 !important;
}

.photo-preview {
    position: relative !important;
    z-index: 1069 !important;
}

/* Assurer que les boutons de photo soient visibles */
#takePhotoIdentite,
#takePhotoAppareil {
    position: relative !important;
    z-index: 1068 !important;
}

/* Correction pour le modal newRachatModal */
#newRachatModal .camera-preview {
    z-index: 1070 !important;
    display: block !important;
    visibility: visible !important;
}

#newRachatModal #cameraVideo {
    z-index: 1071 !important;
    display: block !important;
    visibility: visible !important;
}

/* Assurer que la vidéo est au premier plan */
.camera-preview.d-none {
    display: block !important;
}

.camera-preview video {
    z-index: 1075 !important;
    position: relative !important;
}

/* ===== MODAL CAMERA PLEIN ÉCRAN ===== */

#cameraModal {
    z-index: 2000 !important;
}

#cameraModal .modal-dialog {
    margin: 0 !important;
    max-width: none !important;
    height: 100vh !important;
    width: 100vw !important;
}

#cameraModal .modal-content {
    height: 100vh !important;
    border: none !important;
    border-radius: 0 !important;
}

.camera-preview-fullscreen {
    max-width: 90vw;
    max-height: 70vh;
    margin: 0 auto;
    border-radius: 12px;
    overflow: hidden;
    background: #000;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.5);
}

.camera-video-fullscreen {
    width: 100%;
    height: auto;
    max-height: 70vh;
    object-fit: cover;
    display: block;
    background: #000;
}

#cameraCanvasFullscreen {
    width: 100%;
    height: auto;
    max-height: 70vh;
}

.camera-container {
    width: 100%;
    max-width: 1200px;
}

.camera-controls {
    padding: 20px 0;
}

.camera-controls .btn {
    font-size: 18px;
    padding: 12px 24px;
    border-radius: 8px;
    font-weight: 600;
}

#cameraInstructions {
    font-size: 16px;
    max-width: 600px;
    margin: 0 auto;
}

/* Animation d'ouverture du modal caméra */
#cameraModal.show {
    animation: cameraModalFadeIn 0.3s ease-out;
}

@keyframes cameraModalFadeIn {
    from {
        opacity: 0;
        transform: scale(0.9);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}
</style>

<!-- Ajouter la bibliothèque SignaturePad -->
<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.5/dist/signature_pad.umd.min.js"></script>

<!-- Loader Screen -->
<div id="pageLoader" class="loader">
    <div class="loader-wrapper dark-loader">
        <div class="loader-circle"></div>
        <div class="loader-text">
            <span class="loader-letter">S</span>
            <span class="loader-letter">E</span>
            <span class="loader-letter">R</span>
            <span class="loader-letter">V</span>
            <span class="loader-letter">O</span>
        </div>
    </div>
    <div class="loader-wrapper light-loader">
        <div class="loader-circle-light"></div>
        <div class="loader-text-light">
            <span class="loader-letter">S</span>
            <span class="loader-letter">E</span>
            <span class="loader-letter">R</span>
            <span class="loader-letter">V</span>
            <span class="loader-letter">O</span>
        </div>
    </div>
</div>

<div class="container-fluid py-4 main-content" id="mainContent" style="display: none;">
        <!-- Section liste des rachats -->
    <div class="w-100">
        <div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
                    <h3 class="mb-0">
                        <i class="fas fa-history me-2 text-primary"></i>
                        Historique des rachats
                    </h3>
            <div class="d-flex flex-wrap mt-2 mt-md-0">
                <div class="search-box me-3 position-relative">
                    <i class="fas fa-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                    <input type="text" class="form-control ps-5" id="searchRachat" placeholder="Rechercher par client (nom, prénom, tél, email) ou modèle...">
                </div>
                <div class="filter-box me-3">
                    <select class="form-select" id="filterRachat">
                        <option value="all">Tous les appareils</option>
                        <option value="functional">Fonctionnels</option>
                        <option value="non-functional">Non fonctionnels</option>
                    </select>
                </div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newRachatModal">
                    <i class="fas fa-plus me-2"></i>
                    Nouveau Rachat
                </button>
                <button class="btn btn-success ms-2" id="btnExportSelection" onclick="exportMultipleRachats()" disabled>
                    <i class="fas fa-file-pdf me-2"></i>
                    Exporter Sélection (<span id="selectedCount">0</span>)
                </button>
                    </div>
                </div>

        <!-- Affichage en mode carte pour mobile -->
        <div class="d-md-none mb-4" id="cardViewRachats">
            <!-- Les cartes seront générées en JS -->
        </div>

        <!-- Tableau propre sans Bootstrap -->
        <div class="clean-table-wrapper">
            <table class="clean-data-table">
                <thead class="clean-table-head">
                    <tr>
                        <th class="clean-th" style="width: 50px;">
                            <input type="checkbox" id="selectAll" class="form-check-input" title="Tout sélectionner">
                        </th>
                        <th class="clean-th">
                            <i class="fas fa-calendar-alt"></i>Date
                        </th>
                        <th class="clean-th">
                            <i class="fas fa-user"></i>Client
                        </th>
                        <th class="clean-th">
                            <i class="fas fa-mobile-alt"></i>Modèle
                        </th>
                        <th class="clean-th">
                            <i class="fas fa-barcode"></i>SIN
                        </th>
                        <th class="clean-th">
                            <i class="fas fa-check-circle"></i>État
                        </th>
                        <th class="clean-th">
                            <i class="fas fa-camera"></i>Photos
                        </th>
                        <th class="clean-th clean-th-right">
                            <i class="fas fa-euro-sign"></i>Prix
                        </th>
                        <th class="clean-th clean-th-center">
                            <i class="fas fa-cog"></i>Actions
                        </th>
                    </tr>
                </thead>
                <tbody id="rachatsList" class="clean-table-body">
                    <!-- Les résultats AJAX seront chargés ici -->
                </tbody>
            </table>
        </div>

                <nav aria-label="Pagination" class="mt-4">
                    <ul class="pagination justify-content-center" id="paginationRachats">
                    </ul>
                </nav>
                            </div>
    </div>

    <!-- Modal Détails -->
    <div class="modal fade" id="detailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-info-circle me-2 text-primary"></i>
                    Détails du rachat <span id="modalRachatId" class="badge bg-secondary ms-2"></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                <div class="d-flex justify-content-between mb-3">
                    <div class="client-info">
                        <h6 class="text-primary">
                            <i class="fas fa-user me-2"></i>
                            Client: <span id="modalClientName">-</span>
                        </h6>
                        <p class="text-muted small" id="modalRachatDate">Date: -</p>
                    </div>
                    <div class="price-info text-end">
                        <h5 class="text-success" id="modalRachatPrice">- €</h5>
                        <span class="badge" id="modalRachatState">-</span>
                    </div>
                                </div>

                    <div class="row g-4">
                                <div class="col-md-6">
                            <div class="card h-100">
                            <div class="card-header py-2 d-flex justify-content-between align-items-center">
                                <h6 class="card-title mb-0">
                                        <i class="fas fa-id-card me-2 text-primary"></i>
                                        Pièce d'identité
                                    </h6>
                                <button class="btn btn-sm btn-outline-secondary download-btn" data-img="modalIdentite">
                                    <i class="fas fa-download"></i>
                                </button>
                                </div>
                            <div class="card-body text-center">
                                    <img id="modalIdentite" class="img-fluid rounded img-preview" alt="Pièce d'identité" onerror="this.src='/assets/images/no-image.png'">
                                </div>
                            </div>
                        </div>
                                <div class="col-md-6">
                            <div class="card h-100">
                            <div class="card-header py-2 d-flex justify-content-between align-items-center">
                                <h6 class="card-title mb-0">
                                        <i class="fas fa-mobile-alt me-2 text-primary"></i>
                                        Photo de l'appareil
                                    </h6>
                                <button class="btn btn-sm btn-outline-secondary download-btn" data-img="modalAppareil">
                                    <i class="fas fa-download"></i>
                                </button>
                                </div>
                            <div class="card-body text-center">
                                    <img id="modalAppareil" class="img-fluid rounded img-preview" alt="Appareil" onerror="this.src='/assets/images/no-image.png'">
                            </div>
                        </div>
                    </div>
                                <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header py-2 d-flex justify-content-between align-items-center">
                                <h6 class="card-title mb-0">
                                    <i class="fas fa-user me-2 text-primary"></i>
                                    Photo du client
                                </h6>
                                <button class="btn btn-sm btn-outline-secondary download-btn" data-img="modalPhotoClient">
                                    <i class="fas fa-download"></i>
                                </button>
                                </div>
                            <div class="card-body text-center">
                                    <img id="modalPhotoClient" class="img-fluid rounded img-preview" alt="Photo du client" onerror="this.src='/assets/images/no-image.png'">
                                    </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header py-2 d-flex justify-content-between align-items-center">
                                <h6 class="card-title mb-0">
                                        <i class="fas fa-signature me-2 text-primary"></i>
                                        Signature du client
                                    </h6>
                                <button class="btn btn-sm btn-outline-secondary download-btn" data-img="modalSignature">
                                    <i class="fas fa-download"></i>
                                    </button>
                                </div>
                            <div class="card-body text-center">
                                    <img id="modalSignature" class="img-fluid rounded img-preview" alt="Signature" onerror="this.src='/assets/images/no-image.png'">
                            </div>
                            </div>
                        </div>
                    </div>
                </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                <button type="button" class="btn btn-primary" id="btnExportDetails">
                    <i class="fas fa-file-pdf me-2"></i>Exporter l'attestation
                                </button>
                            </div>
            </div>
                    </div>
                </div>
            </div>

<!-- Modal Caméra Plein Écran -->
<div class="modal fade" id="cameraModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content bg-dark">
            <div class="modal-header border-0 bg-dark text-white">
                <h5 class="modal-title">
                    <i class="fas fa-camera me-2"></i>
                    <span id="cameraModalTitle">Prendre une photo</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" onclick="closeCameraModal()"></button>
            </div>
            <div class="modal-body bg-dark d-flex align-items-center justify-content-center">
                <div class="camera-container text-center">
                    <div id="cameraPreviewFullscreen" class="camera-preview-fullscreen">
                        <video id="cameraVideoFullscreen" autoplay muted playsinline class="camera-video-fullscreen"></video>
                        <canvas id="cameraCanvasFullscreen" class="d-none"></canvas>
                    </div>
                    <div class="camera-controls mt-4">
                        <button type="button" class="btn btn-success btn-lg me-3" onclick="takePictureFullscreen()">
                            <i class="fas fa-camera me-2"></i>Prendre la photo
                        </button>
                        <button type="button" class="btn btn-secondary btn-lg" onclick="closeCameraModal()">
                            <i class="fas fa-times me-2"></i>Annuler
                        </button>
                    </div>
                    <div id="cameraInstructions" class="text-white-50 mt-3">
                        <i class="fas fa-info-circle me-1"></i>
                        Positionnez l'appareil ou le document dans le cadre et cliquez sur "Prendre la photo"
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Nouveau Rachat -->
<div class="modal fade" id="newRachatModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-hand-holding-usd me-2"></i>
                    Nouveau rachat d'appareil
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
                            </div>
            <div class="modal-body">
                <form id="rachatForm" class="needs-validation" novalidate enctype="multipart/form-data">
                    <div id="rachatFormError" class="alert alert-danger d-none" role="alert"></div>
                    <div id="rachatFormSuccess" class="alert alert-success d-none" role="alert"></div>
                    <input type="hidden" name="debug_mode" value="0">
                    
                    <!-- Indicateur d'étapes amélioré -->
                    <div class="stepper-wrapper mb-4">
                        <div class="stepper-item" data-step="1">
                            <div class="step-counter active">1</div>
                            <div class="step-name">Client</div>
                        </div>
                        <div class="stepper-item" data-step="2">
                            <div class="step-counter">2</div>
                            <div class="step-name">Appareil</div>
                            </div>
                        <div class="stepper-item" data-step="3">
                            <div class="step-counter">3</div>
                            <div class="step-name">Signature</div>
                        </div>
                        <div class="stepper-item" data-step="4">
                            <div class="step-counter">4</div>
                            <div class="step-name">Prix</div>
                    </div>
                </div>
                    <div class="progress mb-4 d-none">
                        <div class="progress-bar" role="progressbar" style="width: 20%;" id="rachatProgressBar">Étape 1/4</div>
        </div>

                    <!-- Étape 1: Informations client (ancienne étape 2) -->
                    <div class="rachat-step" id="step1">
                        <h4 class="mb-3">Informations sur le client</h4>
                        <div class="card mb-3 border-primary">
                            <div class="card-header bg-primary bg-opacity-10">
                                <i class="fas fa-search me-2"></i> Rechercher un client existant
                            </div>
            <div class="card-body">
                                <div class="mb-3">
                                    <div class="input-group">
                                        <span class="input-group-text bg-white border-end-0">
                                            <i class="fas fa-search text-primary"></i>
                                        </span>
                                        <input type="text" class="form-control border-start-0" id="recherche_client_rachat" placeholder="Rechercher par nom, prénom ou téléphone...">
                                        <button type="button" class="btn btn-primary rounded-end shadow-sm" id="btn_recherche_client">
                                            <i class="fas fa-search me-1"></i> Rechercher
                                        </button>
                                    </div>
                                    <div class="form-text">Saisissez au moins 2 caractères pour lancer la recherche</div>
                </div>

                                <div id="resultats_clients" class="mb-3 d-none">
                    <div class="search-results-wrapper">
                        <table class="search-table">
                            <thead class="search-table-head">
                                <tr>
                                    <th class="search-th">
                                        <i class="fas fa-user me-2"></i>Nom
                                    </th>
                                    <th class="search-th">
                                        <i class="fas fa-user-tag me-2"></i>Prénom
                                    </th>
                                    <th class="search-th">
                                        <i class="fas fa-phone me-2"></i>Téléphone
                                    </th>
                                    <th class="search-th">
                                        <i class="fas fa-cog me-2"></i>Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="search-table-body" id="liste_clients">
                                <!-- Résultats de recherche ici -->
                            </tbody>
                        </table>
                    </div>
                </div>

                                <div id="no_results" class="alert alert-info d-none">
                                    <p class="mb-2"><i class="fas fa-info-circle me-2"></i>Aucun client trouvé avec ces critères.</p>
                                    <button type="button" class="btn btn-primary" id="btn_nouveau_client">
                                        <i class="fas fa-user-plus me-2"></i>Ajouter un nouveau client
                                    </button>
            </div>
                                
                                <div id="client_selectionne" class="alert alert-success d-none">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <i class="fas fa-user-check me-2"></i>
                                            Client sélectionné: <strong id="nom_client_selectionne"></strong>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-danger" id="reset_client">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                                <input type="hidden" name="client_id" id="client_id" required>
        </div>
    </div>

                        <div class="col-md-12">
                            <label class="form-label">Pièce d'identité (recto) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                                <input type="file" class="form-control" name="photo_identite" id="photo_identite" accept="image/*" required>
                                <button type="button" class="btn btn-outline-primary" onclick="openCameraModal('identite')">
                                    <i class="fas fa-camera me-1"></i> Prendre une photo
                                </button>
                                <div class="invalid-feedback">Veuillez ajouter une photo de la pièce d'identité</div>
                            </div>
                            <div class="form-text">
                                <i class="fas fa-shield-alt me-1"></i> Cette photo est utilisée uniquement pour vérifier l'identité du client
                            </div>
                            <!-- Aperçu de la photo capturée -->
                            <div id="previewIdentite" class="mt-2 d-none">
                                <img id="previewIdentiteImg" class="img-fluid rounded border" style="max-height: 200px;">
                                <button type="button" class="btn btn-sm btn-danger mt-1" id="retakeIdentite">
                                    <i class="fas fa-redo me-1"></i> Reprendre
                                </button>
                            </div>
                            <!-- Canvas caché pour la capture -->
                            <canvas id="canvasIdentite" style="display: none;"></canvas>
                        </div>
                    </div>
                    
                    <!-- Étape 2: Informations sur l'appareil (ancienne étape 1) -->
                    <div class="rachat-step d-none" id="step2">
                        <h4 class="mb-3">Informations sur l'appareil</h4>
                        <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Modèle</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-tag"></i></span>
                                    <input type="text" class="form-control" name="modele" id="modele" placeholder="Ex: iPhone 12, Galaxy S21...">
                                </div>
                                <!-- Ajout du champ caché type_appareil -->
                                <input type="hidden" name="type_appareil" id="type_appareil">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Numéro de série (SIN)</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-barcode"></i></span>
                                    <input type="text" class="form-control" name="sin" id="sin" placeholder="Numéro de série">
                        </div>
                        </div>

                        <div class="col-md-6">
                                <label class="form-label">Photo de l'appareil <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-camera"></i></span>
                                    <input type="file" class="form-control" name="photo_appareil" id="photo_appareil" accept="image/*" required>
                                    <button type="button" class="btn btn-outline-primary" onclick="openCameraModal('appareil')">
                                        <i class="fas fa-camera me-1"></i> Prendre une photo
                                    </button>
                                    <div class="invalid-feedback">Veuillez ajouter une photo de l'appareil</div>
                                </div>
                                <div class="form-text"><i class="fas fa-info-circle"></i> Prenez une photo claire de l'appareil</div>
                                <!-- Aperçu de la photo capturée -->
                                <div id="previewAppareil" class="mt-2 d-none">
                                    <img id="previewAppareilImg" class="img-fluid rounded border" style="max-height: 200px;">
                                    <button type="button" class="btn btn-sm btn-danger mt-1" id="retakeAppareil">
                                        <i class="fas fa-redo me-1"></i> Reprendre
                                    </button>
                                </div>
                                <!-- Canvas caché pour la capture -->
                                <canvas id="canvasAppareil" style="display: none;"></canvas>
                        </div>

                        <div class="col-md-6">
                                <label class="form-label">État <span class="text-danger">*</span></label>
                                <div class="btn-group w-100" role="group" aria-label="État de l'appareil">
                                    <input type="radio" class="btn-check" name="fonctionnel" id="fonctionnel_1" value="1" checked required>
                                    <label class="btn btn-outline-success" for="fonctionnel_1">
                                        <i class="fas fa-check-circle me-2"></i>Fonctionnel
                                    </label>
                                    
                                    <input type="radio" class="btn-check" name="fonctionnel" id="fonctionnel_0" value="0" required>
                                    <label class="btn btn-outline-danger" for="fonctionnel_0">
                                        <i class="fas fa-times-circle me-2"></i>Non fonctionnel
                                    </label>
                                </div>
                                <div class="invalid-feedback">Veuillez sélectionner l'état de l'appareil</div>
                            </div>
                        </div>
                        </div>

                    <!-- Étape 3: Conditions générales -->
                    <div class="rachat-step d-none" id="step3">
                        <h4 class="mb-3">Conditions générales et Signature</h4>
                        <div class="row g-3">
                            <div class="col-md-12 mb-3">
                                <div class="card border-info">
                                    <div class="card-header bg-info bg-opacity-10">
                                        <i class="fas fa-file-contract me-2"></i>Conditions générales de rachat
                                    </div>
                                    <div class="card-body" style="max-height: 200px; overflow-y: auto;">
                                        <p>
                                            <strong>1. Propriété</strong> - Le client certifie être le propriétaire légitime de l'appareil.
                                        </p>
                                        <p>
                                            <strong>2. État</strong> - Le client s'engage à décrire fidèlement l'état de l'appareil.
                                        </p>
                                        <p>
                                            <strong>3. Données</strong> - Le client est responsable de la suppression de ses données personnelles.
                                        </p>
                                        <p>
                                            <strong>4. Prix</strong> - Le prix de rachat est ferme et définitif après acceptation.
                                        </p>
                                        <p>
                                            <strong>5. Transaction</strong> - Une fois le rachat effectué, la transaction est considérée comme définitive.
                                        </p>
                                    </div>
                                </div>
                        </div>

                            <div class="col-md-12">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label class="form-label fw-bold mb-0">Signature du client <span class="text-danger">*</span></label>
                                <div class="form-text camera-notice">
                                    <i class="fas fa-camera me-1"></i> Une photo du client sera prise pendant la signature
                                </div>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-8">
                                    <div class="signature-pad border rounded p-2">
                                        <canvas id="signatureCanvas"></canvas>
                                    </div>
                                        <div class="d-flex justify-content-between mt-2">
                                            <div class="form-text">
                                                <i class="fas fa-pen me-1"></i> Signez dans le cadre ci-dessus
                                            </div>
                                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="clearSignature()">
                                                <i class="fas fa-eraser me-1"></i>Effacer
                                    </button>
                                        </div>
                                    <input type="hidden" name="signature" id="signatureInput">
                                </div>
                                <div class="col-md-4">
                                    <div class="camera-preview mb-2 d-none">
                                        <video id="cameraVideo" autoplay muted playsinline class="w-100 rounded"></video>
                                        <canvas id="cameraCanvas" class="d-none"></canvas>
                                    </div>
                                    <div id="photoPreview" class="photo-preview border rounded p-2 text-center">
                                        <img id="capturedPhoto" class="img-fluid d-none" alt="Photo client">
                                        <div id="photoPlaceholder" class="text-muted">
                                            <i class="fas fa-user fa-3x mb-2"></i>
                                                <p>Photo automatique lors de la signature</p>
                                        </div>
                                    </div>
                                    <input type="hidden" name="client_photo" id="clientPhotoInput">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Étape 4: Prix -->
                    <div class="rachat-step d-none" id="step4">
                        <h4 class="mb-3">Prix de rachat</h4>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Prix (€) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-euro-sign"></i></span>
                                    <input type="number" step="0.01" class="form-control" name="prix" id="prix" required>
                                    <div class="invalid-feedback">Veuillez saisir un prix de rachat</div>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Le prix est déterminé en fonction du modèle et de l'état de l'appareil.
                                </div>
                            </div>
                            
                            <div class="col-md-12 mt-4">
                                <div class="card border-success">
                                    <div class="card-header bg-success bg-opacity-10">
                                        <i class="fas fa-check-circle me-2"></i>Récapitulatif
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <p><strong>Appareil:</strong> <span id="recap_appareil">-</span></p>
                                                <p><strong>Modèle:</strong> <span id="recap_modele">-</span></p>
                                                <p><strong>État:</strong> <span id="recap_etat">-</span></p>
                                            </div>
                                            <div class="col-md-6">
                                                <p><strong>Client:</strong> <span id="recap_client">-</span></p>
                                                <p><strong>Prix proposé:</strong> <span id="recap_prix">-</span></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-outline-secondary" id="prevStep" disabled>
                    <i class="fas fa-arrow-left me-2"></i>Précédent
                </button>
                <button type="button" class="btn btn-primary" id="nextStep">
                    <i class="fas fa-arrow-right me-2"></i>Suivant
                </button>
                <button type="button" class="btn btn-success d-none" id="submitRachat">
                    <i class="fas fa-save me-2"></i>Enregistrer le rachat
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Nouveau Client -->
<div class="modal fade" id="nouveauClientModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="z-index: 1100;">
            <div class="modal-header bg-light">
                    <h5 class="modal-title">
                    <i class="fas fa-user-plus me-2 text-primary"></i>
                    Ajouter un nouveau client
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                <form id="nouveauClientForm" class="needs-validation" novalidate>
                    <div class="mb-3">
                        <label for="nouveau_nom" class="form-label">Nom <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" class="form-control" id="nouveau_nom" required>
                            <div class="invalid-feedback">Ce champ est obligatoire</div>
                                </div>
                            </div>
                    <div class="mb-3">
                        <label for="nouveau_prenom" class="form-label">Prénom <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" class="form-control" id="nouveau_prenom" required>
                            <div class="invalid-feedback">Ce champ est obligatoire</div>
                        </div>
                                </div>
                    <div class="mb-3">
                        <label for="nouveau_telephone" class="form-label">Téléphone <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-phone"></i></span>
                            <input type="tel" class="form-control" id="nouveau_telephone" required>
                            <div class="invalid-feedback">Ce champ est obligatoire</div>
                            </div>
                        </div>
                    <div class="mb-3">
                        <label for="nouveau_email" class="form-label">Email</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                            <input type="email" class="form-control" id="nouveau_email">
                                </div>
                            </div>
                    <div class="mb-3">
                        <label for="nouveau_adresse" class="form-label">Adresse</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span>
                            <textarea class="form-control" id="nouveau_adresse" rows="2"></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" id="btn_sauvegarder_client">
                    <i class="fas fa-save me-2"></i>
                    Enregistrer
                </button>
                </div>
            </div>
        </div>
    </div>

<!-- Modal Plein Écran -->
<div class="modal fade" id="fullscreenModal" tabindex="-1">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="fullscreenModalTitle"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body d-flex align-items-center justify-content-center bg-dark">
                <img id="fullscreenImage" class="img-fluid" style="max-height: 90vh; object-fit: contain;">
            </div>
        </div>
    </div>
</div>

<style>
    html, body {
        height: 100%;
    }
    
    body {
        display: flex;
        flex-direction: column;
    }
    
    .main-content {
        flex: 1 0 auto;
        padding-bottom: 2rem;
    }
    
    footer {
        flex-shrink: 0;
        margin-top: auto;
    }
    
    .table-responsive {
        overflow-x: auto;
        width: 100%;
    }
    
    .table {
        min-width: 100%;
        table-layout: auto;
    }
    
    .camera-preview {
        display: none;
        background-color: #000;
        border-radius: 4px;
        overflow: hidden;
        margin-bottom: 10px;
        position: relative;
    }
    
    #cameraVideo {
        width: 100%;
        height: auto;
        display: block;
        background-color: #000;
    }
    
    #cameraCanvas {
        display: none;
        width: 100%;
        height: auto;
    }
    
    .camera-active {
        border: 2px solid #0d6efd;
    }
    
    .photo-preview {
        min-height: 200px;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 0.25rem;
        opacity: 0;
    }
    
    .camera-notice {
        color: #6c757d;
        font-size: 0.85rem;
    }
    
    #photoPlaceholder {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        color: #6c757d;
    }
    
    #capturedPhoto {
        max-height: 200px;
        object-fit: contain;
        border-radius: 0.25rem;
    }
    
    .signature-pad {
        position: relative;
        display: block;
        width: 100%;
        min-height: 200px;
        background-color: #fff;
    }
    
    #signatureCanvas {
        width: 100%;
        height: 100%;
        min-height: 200px;
        position: absolute;
        top: 0;
        left: 0;
        cursor: crosshair;
        touch-action: none;
    }
    
    .img-preview {
        max-height: 200px;
        width: auto;
        margin: 0 auto;
        display: block;
    }
    
    .clickable-image {
        cursor: pointer;
        transition: transform 0.2s;
    }
    .clickable-image:hover {
        transform: scale(1.05);
    }
    
    /* Styles pour les étapes du formulaire */
    .rachat-step {
        animation: fadeIn 0.4s;
        min-height: 300px;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .progress {
        height: 10px;
    }
    
    .progress-bar {
        background-color: #0d6efd;
        transition: width 0.4s ease;
    }
    
    /* Styles améliorés pour les titres d'étapes */
    .rachat-step h4 {
        border-bottom: 2px solid #e9ecef;
        padding-bottom: 10px;
        margin-bottom: 15px;
        color: #0d6efd;
    }
    
    /* Styles pour l'indicateur d'étapes amélioré */
    .stepper-wrapper {
        display: flex;
        justify-content: space-between;
        margin-bottom: 20px;
    }
    
    .stepper-item {
        position: relative;
        display: flex;
        flex-direction: column;
        align-items: center;
        flex: 1;
    }
    
    .stepper-item::before {
        position: absolute;
        content: "";
        border-bottom: 2px solid #ccc;
        width: 100%;
        top: 20px;
        left: -50%;
        z-index: 2;
    }
    
    .stepper-item::after {
        position: absolute;
        content: "";
        border-bottom: 2px solid #ccc;
        width: 100%;
        top: 20px;
        left: 50%;
        z-index: 2;
    }
    
    .stepper-item:first-child::before {
        content: none;
    }
    
    .stepper-item:last-child::after {
        content: none;
    }
    
    .stepper-item.completed .step-counter {
        background-color: #198754;
        color: white;
    }
    
    .stepper-item.completed::after {
        position: absolute;
        content: "";
        border-bottom: 2px solid #198754;
        width: 100%;
        top: 20px;
        left: 50%;
        z-index: 3;
    }
    
    .stepper-item.completed::before {
        position: absolute;
        content: "";
        border-bottom: 2px solid #198754;
        width: 100%;
        top: 20px;
        left: -50%;
        z-index: 3;
    }
    
    .stepper-item.active .step-counter {
        background-color: #0d6efd;
        color: white;
    }
    
    .step-counter {
        height: 40px;
        width: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: #ccc;
        border-radius: 50%;
        margin-bottom: 6px;
        font-weight: bold;
        z-index: 5;
    }
    
    .step-name {
        font-size: 0.85rem;
        color: #6c757d;
    }
    
    .stepper-item.active .step-name,
    .stepper-item.completed .step-name {
        color: #495057;
        font-weight: 500;
    }
    
    /* Nouvelles classes pour l'amélioration de l'interface */
    .avatar-sm {
        width: 32px;
        height: 32px;
        line-height: 32px;
        display: inline-block;
    }
    
    .search-box {
        width: 250px;
        max-width: 100%;
    }
    
    .table tbody tr {
        transition: all 0.2s;
    }
    
    .table tbody tr:hover {
        background-color: rgba(13, 110, 253, 0.05);
    }
    
    .card {
        transition: all 0.2s;
    }
    
    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
    }
    
    .modal-header, .modal-footer {
        background-color: #f8f9fa;
    }
    
    @media (max-width: 767.98px) {
        .search-box {
            width: 100%;
            margin-bottom: 0.5rem;
        }
        
        .filter-box {
            width: 100%;
            margin-bottom: 0.5rem;
        }
        
        .btn-group-sm .btn {
            padding: 0.25rem 0.5rem;
        }
    }
</style>

<script>
    // Gestion de la recherche
    const searchInput = document.getElementById('searchRachat');
    let currentPage = 1;
let signaturePad;
let stream;
let photoTaken = false;
let capturedPhotoData = null;

    // Chargement initial au démarrage
    window.addEventListener('DOMContentLoaded', () => {
        // Aucune restriction d'accès - tous les utilisateurs peuvent accéder à cette page
        // Si vous souhaitez rétablir la restriction plus tard, décommentez le code ci-dessous
        /*
        if (!<?php echo isset($_SESSION['user_id']) && $_SESSION['role'] === 'admin' ? 'true' : 'false'; ?>) {
            window.location.href = '/pages/login.php';
            return;
        }
        */
        loadRachats();
        initSignaturePad();
    
        // Préparer le canvas pour la photo
        const canvas = document.getElementById('cameraCanvas');
        if (canvas) {
            canvas.width = 640;
            canvas.height = 480;
            const ctx = canvas.getContext('2d');
            ctx.fillStyle = '#f8f9fa';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
        }
    
        // Ajouter un écouteur d'événement pour le modal
        document.getElementById('newRachatModal').addEventListener('shown.bs.modal', function () {
            console.log("Modal ouvert, démarrage de la caméra...");
            // Nettoyer les champs photo et signature
            clearSignature();
            // Attendre un peu avant de démarrer la caméra
            setTimeout(() => {
                startCamera();
            }, 500);
        });
    });

    function loadRachats(search = '', page = 1) {
        fetch('/ajax/recherche_rachat.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            credentials: 'same-origin',
            body: new URLSearchParams({ search })
        })
        .then(response => {
            if (response.status === 401) {
                window.location.href = '/pages/login.php';
                throw new Error('Session expirée ou accès non autorisé');
            }
            if (!response.ok) throw new Error('Erreur réseau');
            return response.json();
        })
        .then(data => {
            if (data.error) {
                document.getElementById('rachatsList').innerHTML = `
                    <tr>
                        <td colspan="8">
                            <div class="clean-no-data">
                                <i class="fas fa-exclamation-triangle"></i>
                                <h4>Erreur</h4>
                                <p>${data.error}</p>
                            </div>
                        </td>
                    </tr>
                `;
                return;
            }
            updateTable(data);
            setupPagination(data.length);
        })
        .catch(error => {
            console.error('Erreur:', error);
            document.getElementById('rachatsList').innerHTML = `
                <tr>
                    <td colspan="8">
                        <div class="clean-no-data">
                            <i class="fas fa-exclamation-triangle"></i>
                            <h4>Erreur de connexion</h4>
                            <p>${error.message}</p>
                        </div>
                    </td>
                </tr>
            `;
        });
    }

    function updateTable(rachats) {
        console.log('🔍 Données reçues dans updateTable:', rachats);
        
        const tbody = document.getElementById('rachatsList');
        const cardView = document.getElementById('cardViewRachats');
        
        // Vider les conteneurs
        tbody.innerHTML = '';
        cardView.innerHTML = '';
        
        // Filtrer les rachats si un filtre est actif
        const filterValue = document.getElementById('filterRachat').value;
        if (filterValue !== 'all') {
            rachats = rachats.filter(rachat => {
                if (filterValue === 'functional') return rachat.fonctionnel === 1;
                if (filterValue === 'non-functional') return rachat.fonctionnel === 0;
                return true;
            });
        }
        
        if (rachats.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="8">
                        <div class="clean-no-data">
                            <i class="fas fa-search"></i>
                            <h4>Aucun résultat</h4>
                            <p>Aucun rachat trouvé avec ces critères</p>
                        </div>
                    </td>
                </tr>
            `;
            cardView.innerHTML = `
                <div class="clean-no-data">
                    <i class="fas fa-search"></i>
                    <h4>Aucun résultat</h4>
                    <p>Aucun rachat trouvé avec ces critères</p>
                </div>
            `;
            return;
        }
        
        // Générer le HTML pour chaque rachat
        rachats.forEach(rachat => {
            console.log(`📸 Rachat ${rachat.id} - Photos:`, {
                photo_appareil: rachat.photo_appareil,
                photo_identite: rachat.photo_identite,
                client_photo: rachat.client_photo,
                signature: rachat.signature
            });
            
            // Formater la date
            const date = new Date(rachat.date_rachat);
            const formattedDate = date.toLocaleDateString('fr-FR', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric'
            });
            
            // Créer un badge propre pour l'état fonctionnel
            const stateBadge = rachat.fonctionnel ? 
                '<span class="clean-badge clean-badge-success"><i class="fas fa-check"></i> Fonctionnel</span>' : 
                '<span class="clean-badge clean-badge-danger"><i class="fas fa-times"></i> Non fonctionnel</span>';
            
            // Prix formaté
            const prix = rachat.prix ? 
                new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(rachat.prix) : 
                'N/A';
            
            // HTML pour le tableau (desktop)
            const row = document.createElement('tr');
            row.dataset.id = rachat.id;
            row.innerHTML = `
                <td>
                    <input type="checkbox" class="form-check-input rachat-checkbox" value="${rachat.id}" onchange="updateSelectionCount()">
                </td>
                <td>${formattedDate}</td>
                <td>
                    <div class="clean-client-info">
                        <div class="clean-avatar">
                            <span>${rachat.prenom?.charAt(0) || ''}${rachat.nom?.charAt(0) || ''}</span>
                        </div>
                        <div class="clean-client-name">${rachat.prenom} ${rachat.nom}</div>
                    </div>
                </td>
                <td>${rachat.modele || rachat.type_appareil}</td>
                <td>${rachat.sin || '<span class="clean-text-muted">N/A</span>'}</td>
                <td>${stateBadge}</td>
                <td>
                    <div class="clean-btn-group">
                        <button class="clean-btn btn-view-appareil" data-photo="${rachat.photo_appareil || ''}" title="Photo appareil">
                            <i class="fas fa-mobile-alt"></i>
                        </button>
                        <button class="clean-btn btn-view-identite" data-photo="${rachat.photo_identite || ''}" title="Pièce d'identité">
                            <i class="fas fa-id-card"></i>
                        </button>
                        <button class="clean-btn btn-view-client" data-photo="${rachat.client_photo || ''}" title="Photo client">
                            <i class="fas fa-user"></i>
                        </button>
                        <button class="clean-btn btn-view-signature" data-photo="${rachat.signature || ''}" title="Signature">
                            <i class="fas fa-signature"></i>
                        </button>
                    </div>
                </td>
                <td class="clean-price">${prix}</td>
                <td>
                    <div class="clean-btn-group">
                        <button class="clean-btn clean-btn-primary" onclick="showDetails(${rachat.id})" title="Voir détails">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="clean-btn clean-btn-success" onclick="exportAttestation(${rachat.id})" title="Attestation PDF">
                            <i class="fas fa-file-pdf"></i>
                        </button>
                    </div>
                </td>
            `;
            tbody.appendChild(row);
            
            // HTML pour la vue carte (mobile)
            const card = document.createElement('div');
            card.className = 'card mb-3 shadow-sm';
            card.dataset.id = rachat.id;
            card.innerHTML = `
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h5 class="card-title mb-0">${rachat.prenom} ${rachat.nom}</h5>
                        <span class="text-muted small">${formattedDate}</span>
                    </div>
                    <div class="row mb-2">
                        <div class="col-8">
                            <p class="card-text mb-1">
                                <strong>Appareil:</strong> ${rachat.modele || 'N/A'}
                            </p>
                            <p class="card-text mb-1">
                                <strong>SIN:</strong> ${rachat.sin || 'N/A'}
                            </p>
                            <p class="card-text mb-0">
                                <strong>Prix:</strong> ${prix}
                            </p>
                        </div>
                        <div class="col-4 text-end">
                            ${stateBadge}
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                        <div>
                            <button class="btn btn-sm btn-outline-secondary btn-view-appareil" data-photo="${rachat.photo_appareil || ''}">
                                <i class="fas fa-mobile-alt me-1"></i> Photo
                            </button>
                        </div>
                        <div>
                            <button class="btn btn-sm btn-outline-primary" onclick="showDetails(${rachat.id})">
                                <i class="fas fa-eye me-1"></i> Détails
                            </button>
                            <button class="btn btn-sm btn-outline-success" onclick="exportAttestation(${rachat.id})">
                                <i class="fas fa-file-pdf me-1"></i> PDF
                            </button>
                        </div>
                    </div>
                </div>
            `;
            cardView.appendChild(card);
        });
        
        // Ajouter les gestionnaires d'événements après avoir créé les éléments
        document.querySelectorAll('.btn-view-appareil').forEach(btn => {
            btn.addEventListener('click', function() {
                const photoName = this.getAttribute('data-photo');
                if (photoName && photoName !== 'undefined' && photoName !== 'null' && photoName.trim() !== '') {
                    showFullscreen("Photo de l'appareil", '/assets/images/rachat/' + photoName);
                } else {
                    console.log('⚠️ Photo appareil non disponible:', photoName);
                    alert('Photo de l\'appareil non disponible.');
                }
            });
        });
        
        document.querySelectorAll('.btn-view-identite').forEach(btn => {
            btn.addEventListener('click', function() {
                const photoName = this.getAttribute('data-photo');
                if (photoName && photoName !== 'undefined' && photoName !== 'null' && photoName.trim() !== '') {
                    showFullscreen("Pièce d'identité", '/assets/images/rachat/' + photoName);
                } else {
                    console.log('⚠️ Photo identité non disponible:', photoName);
                    alert('Photo de la pièce d\'identité non disponible.');
                }
            });
        });
        
        document.querySelectorAll('.btn-view-client').forEach(btn => {
            btn.addEventListener('click', function() {
                const photoName = this.getAttribute('data-photo');
                if (photoName && photoName !== 'undefined' && photoName !== 'null' && photoName.trim() !== '') {
                    showFullscreen("Photo du client", '/assets/images/rachat/' + photoName);
                } else {
                    console.log('⚠️ Photo client non disponible:', photoName);
                    alert('Photo du client non disponible.');
                }
            });
        });
        
        document.querySelectorAll('.btn-view-signature').forEach(btn => {
            btn.addEventListener('click', function() {
                const photoName = this.getAttribute('data-photo');
                if (photoName && photoName !== 'undefined' && photoName !== 'null' && photoName.trim() !== '') {
                    showFullscreen("Signature", '/assets/images/rachat/' + photoName);
                } else {
                    console.log('⚠️ Signature non disponible:', photoName);
                    alert('Signature non disponible.');
                }
            });
        });
    }

    function setupPagination(totalItems) {
        const pagination = document.getElementById('paginationRachats');
        const pageCount = Math.ceil(totalItems / 10);
        
        let html = '';
        for(let i = 1; i <= pageCount; i++) {
            html += `<li class="page-item ${i === currentPage ? 'active' : ''}">
                <button class="page-link" onclick="currentPage = ${i}; loadRachats(searchInput.value)">${i}</button>
            </li>`;
        }
        pagination.innerHTML = html;
    }

    // Fonction pour afficher une image en plein écran
    function showFullscreen(title, imageUrl) {
        const modal = new bootstrap.Modal(document.getElementById('fullscreenModal'));
        document.getElementById('fullscreenModalTitle').textContent = title;
        document.getElementById('fullscreenImage').src = imageUrl;
        modal.show();
    }

    async function showDetails(id) {
        try {
            const response = await fetch(`/ajax/details_rachat.php?id=${id}`);
            if (!response.ok) {
                throw new Error('Erreur lors de la récupération des détails');
            }
            const data = await response.json();
            
            console.log("Données reçues:", {
                photo_identite: !!data.photo_identite,
                photo_appareil: !!data.photo_appareil,
                client_photo: !!data.client_photo,
                signature: !!data.signature
            });
            
            // Mettre à jour les informations générales du rachat
            document.getElementById('modalRachatId').textContent = '#' + id;
            document.getElementById('modalClientName').textContent = data.prenom + ' ' + data.nom;
            
            // Formater la date
            const date = new Date(data.date_rachat);
            const formattedDate = date.toLocaleDateString('fr-FR', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric'
            });
            document.getElementById('modalRachatDate').textContent = 'Date: ' + formattedDate;
            
            // Mettre à jour le prix
            const prix = data.prix ? 
                new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(data.prix) : 
                'N/A';
            document.getElementById('modalRachatPrice').textContent = prix;
            
            // Mettre à jour l'état
            const stateElement = document.getElementById('modalRachatState');
            if (data.fonctionnel == 1) {
                stateElement.textContent = 'Fonctionnel';
                stateElement.className = 'badge bg-success';
            } else {
                stateElement.textContent = 'Non fonctionnel';
                stateElement.className = 'badge bg-danger';
            }
            
            // Mettre à jour les images avec gestion des erreurs
            const modalIdentite = document.getElementById('modalIdentite');
            const modalAppareil = document.getElementById('modalAppareil');
            const modalPhotoClient = document.getElementById('modalPhotoClient');
            const modalSignature = document.getElementById('modalSignature');

            // Pièce d'identité
            if (data.photo_identite) {
                modalIdentite.src = data.photo_identite;
                modalIdentite.classList.add('clickable-image');
                modalIdentite.onclick = () => showFullscreen("Pièce d'identité", data.photo_identite);
            } else {
                modalIdentite.src = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZjhmOWZhIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCwgc2Fucy1zZXJpZiIgZm9udC1zaXplPSIxNCIgZmlsbD0iIzZjNzU3ZCIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPkF1Y3VuZSBpbWFnZTwvdGV4dD48L3N2Zz4=';
                modalIdentite.classList.remove('clickable-image');
                modalIdentite.onclick = null;
            }

            // Photo de l'appareil
            if (data.photo_appareil) {
                modalAppareil.src = data.photo_appareil;
                modalAppareil.classList.add('clickable-image');
                modalAppareil.onclick = () => showFullscreen("Photo de l'appareil", data.photo_appareil);
            } else {
                modalAppareil.src = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZjhmOWZhIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCwgc2Fucy1zZXJpZiIgZm9udC1zaXplPSIxNCIgZmlsbD0iIzZjNzU3ZCIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPkF1Y3VuZSBpbWFnZTwvdGV4dD48L3N2Zz4=';
                modalAppareil.classList.remove('clickable-image');
                modalAppareil.onclick = null;
            }

            // Photo du client
            if (data.client_photo) {
                modalPhotoClient.src = data.client_photo;
                modalPhotoClient.classList.add('clickable-image');
                modalPhotoClient.onclick = () => showFullscreen("Photo du client", data.client_photo);
            } else {
                modalPhotoClient.src = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZjhmOWZhIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCwgc2Fucy1zZXJpZiIgZm9udC1zaXplPSIxNCIgZmlsbD0iIzZjNzU3ZCIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPkF1Y3VuZSBpbWFnZTwvdGV4dD48L3N2Zz4=';
                modalPhotoClient.classList.remove('clickable-image');
                modalPhotoClient.onclick = null;
            }

            // Signature
            if (data.signature) {
                modalSignature.src = data.signature;
                modalSignature.classList.add('clickable-image');
                modalSignature.onclick = () => showFullscreen("Signature", data.signature);
            } else {
                modalSignature.src = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZjhmOWZhIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCwgc2Fucy1zZXJpZiIgZm9udC1zaXplPSIxNCIgZmlsbD0iIzZjNzU3ZCIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPkF1Y3VuZSBpbWFnZTwvdGV4dD48L3N2Zz4=';
                modalSignature.classList.remove('clickable-image');
                modalSignature.onclick = null;
            }

            // Ajouter des gestionnaires d'erreur pour chaque image (avec protection contre les boucles infinies)
            modalIdentite.onerror = function() {
                if (this.src.indexOf('no-image') === -1 && this.src.indexOf('data:image/svg') === -1) {
                    console.error("Erreur de chargement de la pièce d'identité");
                    this.src = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZjhmOWZhIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCwgc2Fucy1zZXJpZiIgZm9udC1zaXplPSIxNCIgZmlsbD0iIzZjNzU3ZCIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPkF1Y3VuZSBpbWFnZTwvdGV4dD48L3N2Zz4=';
                    this.classList.remove('clickable-image');
                    this.onclick = null;
                }
            };
            modalAppareil.onerror = function() {
                if (this.src.indexOf('no-image') === -1 && this.src.indexOf('data:image/svg') === -1) {
                    console.error("Erreur de chargement de la photo de l'appareil");
                    this.src = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZjhmOWZhIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCwgc2Fucy1zZXJpZiIgZm9udC1zaXplPSIxNCIgZmlsbD0iIzZjNzU3ZCIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPkF1Y3VuZSBpbWFnZTwvdGV4dD48L3N2Zz4=';
                    this.classList.remove('clickable-image');
                    this.onclick = null;
                }
            };
            modalPhotoClient.onerror = function() {
                if (this.src.indexOf('no-image') === -1 && this.src.indexOf('data:image/svg') === -1) {
                    console.error("Erreur de chargement de la photo du client");
                    this.src = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZjhmOWZhIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCwgc2Fucy1zZXJpZiIgZm9udC1zaXplPSIxNCIgZmlsbD0iIzZjNzU3ZCIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPkF1Y3VuZSBpbWFnZTwvdGV4dD48L3N2Zz4=';
                    this.classList.remove('clickable-image');
                    this.onclick = null;
                }
            };
            modalSignature.onerror = function() {
                if (this.src.indexOf('no-image') === -1 && this.src.indexOf('data:image/svg') === -1) {
                    console.error("Erreur de chargement de la signature");
                    this.src = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZjhmOWZhIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCwgc2Fucy1zZXJpZiIgZm9udC1zaXplPSIxNCIgZmlsbD0iIzZjNzU3ZCIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPkF1Y3VuZSBpbWFnZTwvdGV4dD48L3N2Zz4=';
                    this.classList.remove('clickable-image');
                    this.onclick = null;
                }
            };
            
            // Configurer le bouton d'export
            document.getElementById('btnExportDetails').onclick = () => exportAttestation(id);
            
            // Afficher le modal
            new bootstrap.Modal(document.getElementById('detailsModal')).show();
        } catch (error) {
            console.error('Erreur:', error);
            alert('Erreur lors du chargement des détails');
        }
    }
    
    // Fonction pour exporter l'attestation de rachat en PDF
    async function exportAttestation(id) {
        try {
            // Afficher un indicateur de chargement
            const loadingModal = new bootstrap.Modal(document.createElement('div'));
            const loadingContent = document.createElement('div');
            loadingContent.innerHTML = `
                <div class="modal fade" id="loadingModal" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-body text-center p-4">
                                <div class="spinner-border text-primary mb-3" role="status"></div>
                                <h5>Génération de l'attestation en cours...</h5>
                                <p class="text-muted">Veuillez patienter pendant la création du PDF.</p>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(loadingContent);
            const loadingModalElement = document.getElementById('loadingModal');
            const loadingModalInstance = new bootstrap.Modal(loadingModalElement);
            loadingModalInstance.show();
            
            // Récupérer les données de l'attestation
            const response = await fetch(`/ajax/export_attestation.php?id=${id}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            });
            
            if (!response.ok) {
                throw new Error(`Erreur lors de la récupération des données: ${response.status}`);
            }
            
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                console.error('Réponse non-JSON reçue:', await response.text());
                throw new Error('Le serveur n\'a pas retourné du JSON valide');
            }
            
            const data = await response.json();
            
            if (data.error) {
                throw new Error(data.error);
            }
            
            // Créer un élément iframe invisible pour afficher le HTML
            const iframe = document.createElement('iframe');
            iframe.style.width = '0';
            iframe.style.height = '0';
            iframe.style.position = 'absolute';
            iframe.style.top = '-9999px';
            document.body.appendChild(iframe);
            
            // Écrire le HTML dans l'iframe
            const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
            iframeDoc.open();
            iframeDoc.write(data.html);
            iframeDoc.close();
            
            // Attendre que les images soient chargées
            setTimeout(async () => {
                try {
                    // Créer le PDF avec window.print()
                    iframe.contentWindow.print();
                    
                    // Nettoyer
                    setTimeout(() => {
                        document.body.removeChild(iframe);
                        loadingModalInstance.hide();
                        loadingContent.remove();
                    }, 1000);
                    
                } catch (printError) {
                    console.error('Erreur lors de l\'impression:', printError);
                    alert('Erreur lors de la génération du PDF. Veuillez réessayer.');
                    document.body.removeChild(iframe);
                    loadingModalInstance.hide();
                    loadingContent.remove();
                }
            }, 1500);
            
        } catch (error) {
            console.error('Erreur:', error);
            alert(`Erreur: ${error.message}`);
        }
    }

    // Fonction d'initialisation du pad de signature
    function initSignaturePad() {
        const canvas = document.getElementById('signatureCanvas');
        
        // Assurons-nous que le canvas a la bonne taille
        const container = canvas.parentElement;
        canvas.width = container.clientWidth - 20; // -20 pour le padding
        canvas.height = 200;
        
        signaturePad = new SignaturePad(canvas, {
            backgroundColor: 'rgba(255, 255, 255, 0)',
            penColor: 'black',
            minWidth: 1,
            maxWidth: 3
        });

        // Attacher des événements pour capturer la photo lorsque la signature commence
        signaturePad.addEventListener("beginStroke", () => {
            console.log("Début de signature détecté, capture de la photo");
            capturePhoto();
        });
        
        console.log("Signature pad initialized");
        
        // Ajouter des événements de débogage
        canvas.addEventListener('mousedown', (e) => {
            console.log('Canvas mousedown event triggered');
        });
        
        canvas.addEventListener('touchstart', (e) => {
            console.log('Canvas touchstart event triggered');
        });
        
        // Vérifier que la signature fonctionne en ajoutant une fonction test
        debugSignaturePad();
    }

    // Fonction pour déboguer le pad de signature
    function debugSignaturePad() {
        const canvas = document.getElementById('signatureCanvas');
        console.log(`Canvas initialisé: ${canvas.width}x${canvas.height}`);
    }

    // Fonction pour démarrer la caméra
    async function startCamera() {
        // Ne démarrer la caméra qu'une seule fois
        if (stream || photoTaken) return;
        
        console.log("Starting camera...");
        
        try {
            const video = document.getElementById('cameraVideo');
            const cameraPreview = document.querySelector('.camera-preview');
            
            // Stopper toute caméra précédente qui pourrait être en cours d'utilisation
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
            }
            
            // Demander l'accès à la caméra frontale avec des contraintes plus flexibles
            stream = await navigator.mediaDevices.getUserMedia({
                video: { 
                    facingMode: 'user',
                    width: { ideal: 640 },
                    height: { ideal: 480 }
                },
                audio: false
            });
            
            // Afficher le flux vidéo
            video.srcObject = stream;
            cameraPreview.classList.remove('d-none');
            
            // Forcer l'affichage et le z-index
            cameraPreview.style.display = 'block';
            cameraPreview.style.visibility = 'visible';
            cameraPreview.style.zIndex = '1070';
            video.style.zIndex = '1071';
            video.style.display = 'block';
            video.style.visibility = 'visible';
            
            console.log('🎥 Caméra affichée:', {
                previewDisplay: cameraPreview.style.display,
                previewVisible: cameraPreview.style.visibility,
                previewZIndex: cameraPreview.style.zIndex,
                videoDisplay: video.style.display,
                videoZIndex: video.style.zIndex
            });
            
            // Attendre que la vidéo soit prête avec un gestionnaire d'événements
            video.onloadedmetadata = () => {
                video.play()
                    .then(() => {
                        console.log("Vidéo démarrée avec succès");
                        // Attendre un court instant pour s'assurer que la caméra est bien initialisée
                        setTimeout(() => {
                            // Ajouter une classe pour montrer que la caméra est active
                            video.classList.add('camera-active');
                        }, 500);
                    })
                    .catch(e => console.error("Erreur lors du démarrage de la vidéo:", e));
            };
            
            console.log("Camera initialized successfully");
        } catch (err) {
            console.error("Erreur d'accès à la caméra:", err);
            // Informer l'utilisateur du problème de caméra
            alert("Impossible d'accéder à la caméra. Veuillez vérifier les permissions de votre navigateur.");
        }
    }

    // Fonction pour capturer la photo
    async function capturePhoto() {
        if (!stream) {
            console.log("No camera stream available");
            return;
        }
        
        try {
            const video = document.getElementById('cameraVideo');
            const canvas = document.getElementById('cameraCanvas');
            const context = canvas.getContext('2d');
            
            // S'assurer que la vidéo est en cours de lecture
            if (video.paused || video.ended) {
                await video.play();
                // Attendre un court instant pour que la vidéo démarre réellement
                await new Promise(resolve => setTimeout(resolve, 300));
            }
            
            // Définir les dimensions du canvas aux dimensions actuelles de la vidéo
            canvas.width = video.videoWidth || 640;
            canvas.height = video.videoHeight || 480;
            
            console.log(`Capture dimensions: ${canvas.width}x${canvas.height}`);
            
            // Vérifier si les dimensions sont correctes
            if (canvas.width === 0 || canvas.height === 0) {
                console.error("Dimensions de vidéo invalides");
                canvas.width = 640;
                canvas.height = 480;
            }
            
            // Dessiner la vidéo sur le canvas
            context.drawImage(video, 0, 0, canvas.width, canvas.height);
            
            // Convertir le canvas en image
            capturedPhotoData = canvas.toDataURL('image/jpeg', 0.9);
            
            // Vérifier si l'image est vide ou noire
            if (capturedPhotoData.length < 1000) {
                console.error("L'image capturée est potentiellement vide ou noire");
            }
            
            // Afficher l'image capturée
            const capturedPhoto = document.getElementById('capturedPhoto');
            capturedPhoto.src = capturedPhotoData;
            capturedPhoto.classList.remove('d-none');
            document.getElementById('photoPlaceholder').classList.add('d-none');
            
            // Arrêter la caméra après la capture
            stopCamera();
            
            // Marquer que la photo a été prise
            photoTaken = true;
            
            console.log("Photo captured successfully");
        } catch (err) {
            console.error("Erreur lors de la capture de la photo:", err);
            alert("Erreur lors de la prise de photo. Veuillez réessayer.");
        }
    }

    // Fonction pour arrêter la caméra
    function stopCamera() {
        if (stream) {
            stream.getTracks().forEach(track => track.stop());
            stream = null;
            document.getElementById('cameraVideo').srcObject = null;
            document.querySelector('.camera-preview').classList.add('d-none');
        }
    }

    function clearSignature() {
        if (signaturePad) {
            signaturePad.clear();
            document.getElementById('signatureInput').value = '';
        }
        
        // Réinitialiser la photo également
        const capturedPhoto = document.getElementById('capturedPhoto');
        const photoPlaceholder = document.getElementById('photoPlaceholder');
        const clientPhotoInput = document.getElementById('clientPhotoInput');
        
        capturedPhoto.classList.add('d-none');
        photoPlaceholder.classList.remove('d-none');
        clientPhotoInput.value = '';
        photoTaken = false;
    }

    // Soumission du formulaire
    document.getElementById('submitRachat').addEventListener('click', function() {
        const form = document.getElementById('rachatForm');
        const errorDiv = document.getElementById('rachatFormError');
        const successDiv = document.getElementById('rachatFormSuccess');
        
        // Réinitialiser les messages
        errorDiv.classList.add('d-none');
        successDiv.classList.add('d-none');
        
        // S'assurer que le champ type_appareil est rempli avec la valeur du modèle
        const modele = document.getElementById('modele').value;
        document.getElementById('type_appareil').value = modele;
        
        // Vérifier la validité du formulaire
        if (!form.checkValidity()) {
            form.classList.add('was-validated');
            errorDiv.textContent = 'Veuillez remplir tous les champs obligatoires.';
            errorDiv.classList.remove('d-none');
            return;
        }
        
        // Vérifier la signature
        if (signaturePad.isEmpty()) {
            errorDiv.textContent = 'La signature est obligatoire.';
            errorDiv.classList.remove('d-none');
            return;
        }
        
        // Vérifier si une photo a été prise
        if (!photoTaken) {
            errorDiv.textContent = 'La photo du client est obligatoire. Veuillez recommencer la signature.';
            errorDiv.classList.remove('d-none');
            return;
        }
        
        try {
            // Récupérer la signature en base64
            document.getElementById('signatureInput').value = signaturePad.toDataURL();
            
            // Ajouter la photo capturée au formulaire
            if (capturedPhotoData) {
                // Créer un champ caché pour la photo du client
                let photoInput = document.createElement('input');
                photoInput.type = 'hidden';
                photoInput.name = 'client_photo_data';
                photoInput.value = capturedPhotoData;
                form.appendChild(photoInput);
                
                // Stocker également dans le champ prévu pour ça
                document.getElementById('clientPhotoInput').value = capturedPhotoData;
            }
            
            // Créer un objet FormData pour l'envoi
            const formData = new FormData(form);
            
            // Afficher un message d'attente
            errorDiv.textContent = 'Envoi en cours...';
            errorDiv.classList.remove('d-none');
            errorDiv.classList.remove('alert-danger');
            errorDiv.classList.add('alert-info');
            
            // Désactiver le bouton pendant l'envoi
            this.disabled = true;
            
            // Envoyer les données avec withCredentials pour assurer l'envoi des cookies
            fetch('/ajax/save_rachat.php', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
                cache: 'no-cache'
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`Erreur HTTP: ${response.status} ${response.statusText}`);
                }
                return response.json();
            })
            .then(data => {
                // Réactiver le bouton
                this.disabled = false;
                errorDiv.classList.remove('alert-info');
                
                if (data.error) {
                    errorDiv.textContent = `Erreur: ${data.error}`;
                    errorDiv.classList.add('alert-danger');
                    errorDiv.classList.remove('d-none');
                    console.error('Erreur serveur:', data.error);
                } else {
                    successDiv.textContent = 'Rachat enregistré avec succès !';
                    successDiv.classList.remove('d-none');
                    
                    // Réinitialiser le formulaire
                    form.reset();
                    clearSignature();
                    form.classList.remove('was-validated');
                    
                    // Recharger la liste des rachats
                    loadRachats();
                    
                    // Fermer le modal après un délai
                    setTimeout(() => {
                        const modalElement = document.getElementById('newRachatModal');
                        const modal = bootstrap.Modal.getInstance(modalElement);
                        
                        // Fermer le modal
                        modal.hide();
                        
                        // Nettoyer le modal après sa fermeture
                        cleanupModal();
                    }, 2000);
                }
            })
            .catch(error => {
                // Réactiver le bouton
                this.disabled = false;
                
                console.error('Erreur:', error);
                errorDiv.textContent = `Une erreur est survenue lors de l'enregistrement: ${error.message}`;
                errorDiv.classList.add('alert-danger');
                errorDiv.classList.remove('alert-info');
                errorDiv.classList.remove('d-none');
            });
        } catch (e) {
            console.error('Erreur lors de la préparation du formulaire:', e);
            errorDiv.textContent = `Erreur de préparation: ${e.message}`;
            errorDiv.classList.add('alert-danger');
            errorDiv.classList.remove('d-none');
        }
    });

    // Fonction pour nettoyer correctement le modal
    function cleanupModal() {
        // S'assurer que le backdrop est supprimé
        document.querySelectorAll('.modal-backdrop').forEach(backdrop => {
            backdrop.remove();
        });
        
        // S'assurer que le corps de la page est débloqué
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';
    }

    // S'assurer que la caméra est arrêtée lorsque le modal est fermé
    document.getElementById('newRachatModal').addEventListener('hidden.bs.modal', function () {
        stopCamera();
        photoTaken = false;
        
        // Nettoyer le modal après sa fermeture
        setTimeout(cleanupModal, 150);
    });

    // Ajouter un gestionnaire d'événements aux boutons de fermeture du modal pour assurer le nettoyage
    document.querySelectorAll('[data-bs-dismiss="modal"]').forEach(button => {
        button.addEventListener('click', function() {
            // Le modal sera fermé par Bootstrap, mais nous ajoutons une sécurité supplémentaire
            setTimeout(cleanupModal, 350);
        });
    });

    // Gestion des étapes du formulaire de rachat
    let currentStep = 1;
    const totalSteps = 4;
    
    // Fonction pour mettre à jour la barre de progression
    function updateProgressBar() {
        const progress = (currentStep / totalSteps) * 100;
        const progressBar = document.getElementById('rachatProgressBar');
        progressBar.style.width = `${progress}%`;
        progressBar.textContent = `Étape ${currentStep}/${totalSteps}`;
        
        // Mise à jour de l'indicateur d'étapes amélioré
        const stepperItems = document.querySelectorAll('.stepper-item');
        stepperItems.forEach((item, index) => {
            const stepNum = index + 1;
            if (stepNum < currentStep) {
                item.classList.add('completed');
                item.classList.remove('active');
            } else if (stepNum === currentStep) {
                item.classList.add('active');
                item.classList.remove('completed');
            } else {
                item.classList.remove('active', 'completed');
            }
        });
    }
    
    // Fonction pour afficher une étape spécifique
    function showStep(step) {
        // Masquer toutes les étapes
        document.querySelectorAll('.rachat-step').forEach(s => s.classList.add('d-none'));
        
        // Afficher l'étape demandée
        document.getElementById(`step${step}`).classList.remove('d-none');
        
        // Mettre à jour les boutons
        const prevBtn = document.getElementById('prevStep');
        const nextBtn = document.getElementById('nextStep');
        const submitBtn = document.getElementById('submitRachat');
        
        prevBtn.disabled = (step === 1);
        
        if (step === totalSteps) {
            nextBtn.classList.add('d-none');
            submitBtn.classList.remove('d-none');
        } else {
            nextBtn.classList.remove('d-none');
            submitBtn.classList.add('d-none');
        }
        
        // Si on est à l'étape 3 (conditions générales et signature), initialiser la caméra
        if (step === 3) {
            // Laisser un petit délai pour que le DOM soit complètement chargé
            setTimeout(() => {
                startCamera();
            }, 500);
        } else {
            // Arrêter la caméra si on quitte l'étape 3
            stopCamera();
        }
        
        // Mettre à jour la barre de progression
        updateProgressBar();
    }
    
    // Bouton suivant
    document.getElementById('nextStep').addEventListener('click', function() {
        // Validation spécifique à chaque étape
        let canProceed = true;
        
        // Vérifier la validation selon l'étape actuelle
        switch(currentStep) {
            case 1: // Étape 1: Information sur le client
                const clientId = document.getElementById('client_id').value;
                const photoIdentiteInput = document.getElementById('photo_identite');
                const photoIdentite = photoIdentiteInput.files.length;
                
                console.log('🔍 Validation étape 1:', {
                    clientId: clientId,
                    photoIdentiteFiles: photoIdentite,
                    inputValid: photoIdentiteInput.classList.contains('is-valid'),
                    inputInvalid: photoIdentiteInput.classList.contains('is-invalid'),
                    currentPhotoType: currentPhotoType,
                    filesInInput: Array.from(photoIdentiteInput.files).map(f => f.name)
                });
                
                if (!clientId) {
                    alert('Veuillez sélectionner un client.');
                    canProceed = false;
                    break;
                }
                
                if (!photoIdentite) {
                    alert('Veuillez ajouter une photo d\'identité.');
                    canProceed = false;
                    break;
                }
                
                console.log('✅ Validation étape 1 réussie');
                break;
                
            case 2: // Étape 2: Information sur l'appareil
                const photoAppareil = document.getElementById('photo_appareil').files.length;
                
                // S'assurer que le champ type_appareil est rempli avec la valeur du modèle
                const modele = document.getElementById('modele').value;
                document.getElementById('type_appareil').value = modele;
                
                if (!photoAppareil) {
                    alert('Veuillez ajouter une photo de l\'appareil.');
                    canProceed = false;
                }
                break;
                
            case 3: // Étape 3: Conditions générales et Signature
                if (signaturePad && signaturePad.isEmpty()) {
                    alert('Veuillez signer le formulaire après avoir lu les conditions générales.');
                    canProceed = false;
                }
                
                if (!photoTaken) {
                    alert('La photo du client est nécessaire. Veuillez signer pour déclencher la photo.');
                    canProceed = false;
                }
                break;
                
            case 4: // Étape 4: Prix (dernière étape)
                const prix = document.getElementById('prix').value;
                if (!prix) {
                    alert('Veuillez spécifier un prix.');
                    canProceed = false;
                }
                break;
        }
        
        if (canProceed && currentStep < totalSteps) {
            currentStep++;
            showStep(currentStep);
            
            // Si on passe à l'étape 4, mettre à jour le récapitulatif
            if (currentStep === 4) {
                updateRecap();
            }
        }
    });
    
    // Bouton précédent
    document.getElementById('prevStep').addEventListener('click', function() {
        if (currentStep > 1) {
            currentStep--;
            showStep(currentStep);
        }
    });
    
    // Réinitialiser le formulaire à l'ouverture du modal
    document.getElementById('newRachatModal').addEventListener('shown.bs.modal', function () {
        console.log('🔄 Modal newRachat ouvert - Vérification besoin de reset...');
        
        // Vérifier si c'est la première ouverture ou si on revient du modal caméra
        const isReturningFromCamera = currentPhotoType !== null;
        
        if (!isReturningFromCamera) {
            console.log('📝 Première ouverture - Reset complet du formulaire');
            
            // Réinitialiser l'étape courante
            currentStep = 1;
            showStep(currentStep);
            
            // Réinitialiser le formulaire
            document.getElementById('rachatForm').reset();
            document.getElementById('client_selectionne').classList.add('d-none');
            
            if (signaturePad) {
                signaturePad.clear();
            }
            
            // Masquer les messages
            document.getElementById('rachatFormError').classList.add('d-none');
            document.getElementById('rachatFormSuccess').classList.add('d-none');
        } else {
            console.log('📸 Retour du modal caméra - Préservation des données');
            
            // Sauvegarder les fichiers existants avant reset partiel
            const photoIdentiteFiles = document.getElementById('photo_identite').files;
            const photoAppareilFiles = document.getElementById('photo_appareil').files;
            const clientId = document.getElementById('client_id').value;
            
            console.log('💾 Sauvegarde:', {
                photoIdentiteFiles: photoIdentiteFiles.length,
                photoAppareilFiles: photoAppareilFiles.length,
                clientId: clientId
            });
            
            // Reset uniquement certains champs spécifiques
            const fieldsToReset = ['marque', 'modele', 'imei', 'defaut', 'accessoires', 'prix_propose'];
            fieldsToReset.forEach(fieldId => {
                const field = document.getElementById(fieldId);
                if (field) field.value = '';
            });
            
            // Restaurer les fichiers si ils existaient
            if (photoIdentiteFiles.length > 0) {
                console.log('🔄 Restauration photo identité');
                const input = document.getElementById('photo_identite');
                input.classList.add('is-valid');
                input.classList.remove('is-invalid');
            }
            
            if (photoAppareilFiles.length > 0) {
                console.log('🔄 Restauration photo appareil');
                const input = document.getElementById('photo_appareil');
                input.classList.add('is-valid');
                input.classList.remove('is-invalid');
            }
            
            // Masquer uniquement les messages d'erreur
            document.getElementById('rachatFormError').classList.add('d-none');
        }
        
        // Le reset du flag se fait dans closeCameraModal après réouverture complète
    });

    // Fonction de recherche client
    function rechercherClients(terme) {
        if (terme.length < 2) {
            document.getElementById('resultats_clients').classList.add('d-none');
            document.getElementById('no_results').classList.add('d-none');
            return;
        }
        
        // Recherche AJAX
        fetch('ajax/recherche_clients.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'terme=' + encodeURIComponent(terme)
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Erreur réseau: ' + response.status);
            }
            return response.text();
        })
        .then(text => {
            let data;
            try {
                data = JSON.parse(text);
            } catch (e) {
                console.error('Texte reçu (non JSON):', text);
                throw new Error('Réponse invalide: ' + e.message);
            }
            
            const listeClients = document.getElementById('liste_clients');
            listeClients.innerHTML = '';
            
            if (data.success && data.clients && data.clients.length > 0) {
                data.clients.forEach(function(client) {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td class="search-td">
                            <div class="search-client-name">${client.nom || ''}</div>
                        </td>
                        <td class="search-td">
                            <div class="search-client-name">${client.prenom || ''}</div>
                        </td>
                        <td class="search-td">
                            <div class="search-client-phone">${client.telephone || ''}</div>
                        </td>
                        <td class="search-td">
                            <button type="button" class="search-btn search-btn-primary selectionner-client" 
                                data-id="${client.id}" 
                                data-nom="${client.nom || ''}" 
                                data-prenom="${client.prenom || ''}">
                                <i class="fas fa-check me-1"></i>Sélectionner
                            </button>
                        </td>
                    `;
                    listeClients.appendChild(row);
                });
                
                document.getElementById('resultats_clients').classList.remove('d-none');
                document.getElementById('no_results').classList.add('d-none');
            } else {
                document.getElementById('resultats_clients').classList.add('d-none');
                document.getElementById('no_results').classList.remove('d-none');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            document.getElementById('resultats_clients').classList.add('d-none');
            
            // Afficher l'erreur à l'utilisateur
            const noResults = document.getElementById('no_results');
            noResults.innerHTML = `
                <p class="text-danger mb-2"><i class="fas fa-exclamation-circle me-2"></i>Erreur: ${error.message}</p>
                <button type="button" class="btn btn-primary" id="btn_nouveau_client">
                    <i class="fas fa-user-plus me-2"></i>Ajouter un nouveau client
                </button>
            `;
            noResults.classList.remove('d-none');
        });
    }
    
    // Variable pour le délai de recherche (debounce)
    let timeoutRecherche;
    
    // Recherche automatique lorsque l'utilisateur tape
    document.getElementById('recherche_client_rachat').addEventListener('input', function() {
        const terme = this.value.trim();
        
        // Annuler le précédent timeout s'il existe
        clearTimeout(timeoutRecherche);
        
        // Définir un nouveau timeout pour éviter trop de requêtes
        timeoutRecherche = setTimeout(() => {
            rechercherClients(terme);
        }, 300); // Délai de 300ms avant de lancer la recherche
    });
    
    // Conserver le bouton de recherche pour compatibilité
    document.getElementById('btn_recherche_client').addEventListener('click', function() {
        const terme = document.getElementById('recherche_client_rachat').value.trim();
        rechercherClients(terme);
    });

    // Sélection d'un client
    document.addEventListener('click', function(e) {
        if (e.target && e.target.classList.contains('selectionner-client')) {
            const id = e.target.getAttribute('data-id');
            const nom = e.target.getAttribute('data-nom');
            const prenom = e.target.getAttribute('data-prenom');
            
            document.getElementById('client_id').value = id;
            document.getElementById('nom_client_selectionne').textContent = prenom + ' ' + nom;
            document.getElementById('client_selectionne').classList.remove('d-none');
            document.getElementById('resultats_clients').classList.add('d-none');
        }
    });
    
    // Réinitialiser la sélection du client
    document.getElementById('reset_client').addEventListener('click', function() {
        document.getElementById('client_id').value = '';
        document.getElementById('client_selectionne').classList.add('d-none');
    });
    
    // Ouvrir le modal d'ajout de client
    document.getElementById('btn_nouveau_client').addEventListener('click', function() {
        // Fermer d'abord le modal parent pour éviter les conflits
        const rachatModal = bootstrap.Modal.getInstance(document.getElementById('newRachatModal'));
        if (rachatModal) {
            rachatModal.hide();
            
            // Attendre que le modal parent soit fermé avant d'ouvrir le nouveau
            setTimeout(() => {
                const clientModal = new bootstrap.Modal(document.getElementById('nouveauClientModal'));
                clientModal.show();
                
                // Après avoir montré le modal client, configurer un gestionnaire pour sa fermeture
                document.getElementById('nouveauClientModal').addEventListener('hidden.bs.modal', function() {
                    // Réouvrir le modal de rachat
                    setTimeout(() => {
                        rachatModal.show();
                    }, 200);
                }, { once: true });  // L'événement ne sera déclenché qu'une seule fois
            }, 300);
        } else {
            // Si pour une raison quelconque le modal parent n'existe pas, ouvrir directement
            const clientModal = new bootstrap.Modal(document.getElementById('nouveauClientModal'));
            clientModal.show();
        }
    });
    
    // Sauvegarder un nouveau client
    document.getElementById('btn_sauvegarder_client').addEventListener('click', function() {
        const nom = document.getElementById('nouveau_nom').value;
        const prenom = document.getElementById('nouveau_prenom').value;
        const telephone = document.getElementById('nouveau_telephone').value;
        const email = document.getElementById('nouveau_email').value;
        const adresse = document.getElementById('nouveau_adresse').value;
        
        if (!nom || !prenom || !telephone) {
            alert('Veuillez remplir tous les champs obligatoires');
            return;
        }
        
        // Désactiver le bouton pendant l'enregistrement
        this.disabled = true;
        this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Enregistrement...';
        
        // Enregistrement AJAX - Utilisation du chemin direct vers le fichier PHP
        fetch('/ajax/ajouter_client.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            credentials: 'same-origin', // Pour garantir que les cookies de session sont envoyés
            body: 'nom=' + encodeURIComponent(nom) +
                  '&prenom=' + encodeURIComponent(prenom) + 
                  '&telephone=' + encodeURIComponent(telephone) +
                  '&email=' + encodeURIComponent(email) +
                  '&adresse=' + encodeURIComponent(adresse)
        })
        .then(response => {
            // Vérifier si la réponse est ok
            if (!response.ok) {
                throw new Error('Erreur réseau: ' + response.status);
            }
            // Vérifier le type de contenu
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                throw new Error('Format de réponse invalide');
            }
            return response.json();
        })
        .then(data => {
            // Réactiver le bouton
            this.disabled = false;
            this.innerHTML = '<i class="fas fa-save me-2"></i>Enregistrer';
            
            if (data.success) {
                // Stocker les informations du client pour une utilisation ultérieure
                const newClientInfo = {
                    id: data.client_id,
                    nom: nom,
                    prenom: prenom
                };
                
                // Fermer le modal d'ajout de client
                const clientModal = bootstrap.Modal.getInstance(document.getElementById('nouveauClientModal'));
                if (clientModal) {
                    clientModal.hide();
                }
                
                // Réinitialiser le formulaire
                document.getElementById('nouveauClientForm').reset();
                
                // Attendre la fermeture du modal avant de pré-sélectionner le client
                setTimeout(() => {
                    // Sélectionner le client créé dans le modal de rachat
                    document.getElementById('client_id').value = newClientInfo.id;
                    document.getElementById('nom_client_selectionne').textContent = newClientInfo.prenom + ' ' + newClientInfo.nom;
                    document.getElementById('client_selectionne').classList.remove('d-none');
                    document.getElementById('resultats_clients').classList.add('d-none');
                    document.getElementById('no_results').classList.add('d-none');
                    
                    // Notifier l'utilisateur de façon discrète
                    const notificationElement = document.createElement('div');
                    notificationElement.className = 'alert alert-success alert-dismissible fade show mt-2';
                    notificationElement.innerHTML = `
                        <i class="fas fa-check-circle me-2"></i>
                        Client ajouté avec succès
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    `;
                    
                    // Ajouter la notification en haut du formulaire du modal rachat
                    const firstStep = document.querySelector('.rachat-step');
                    if (firstStep) {
                        firstStep.insertBefore(notificationElement, firstStep.firstChild);
                        
                        // Supprimer la notification après 3 secondes
                        setTimeout(() => {
                            notificationElement.remove();
                        }, 3000);
                    }
                }, 300);
            } else {
                alert('Erreur: ' + (data.message || 'Une erreur est survenue'));
            }
        })
        .catch(error => {
            // Réactiver le bouton
            this.disabled = false;
            this.innerHTML = '<i class="fas fa-save me-2"></i>Enregistrer';
            
            console.error('Erreur:', error);
            alert('Erreur lors de l\'enregistrement du client: ' + error.message);
        });
    });

    // Filtrage des résultats
    document.getElementById('filterRachat').addEventListener('change', function() {
        loadRachats(document.getElementById('searchRachat').value);
    });
    
    // Recherche en temps réel avec debounce
    let searchTimeout;
    document.getElementById('searchRachat').addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            loadRachats(this.value);
        }, 300);
    });

    // Ajouter au début du code JavaScript du document
    document.addEventListener('DOMContentLoaded', function() {
        // Gestionnaires d'événements pour les boutons de téléchargement
        document.querySelectorAll('.download-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.stopPropagation(); // Empêcher la propagation qui pourrait déclencher d'autres événements
                const imgId = this.getAttribute('data-img');
                downloadImage(imgId);
            });
        });
        
        // Vérifier si des messages d'erreur sont présents dans l'URL
        const urlParams = new URLSearchParams(window.location.search);
        const error = urlParams.get('error');
        if (error) {
            const errorDiv = document.createElement('div');
            errorDiv.className = 'alert alert-danger alert-dismissible fade show';
            errorDiv.innerHTML = `
                <i class="fas fa-exclamation-circle me-2"></i>
                ${error}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.querySelector('.main-content').prepend(errorDiv);
        }
        
        // Mise à jour du champ type_appareil avec la valeur du modèle
        document.getElementById('modele').addEventListener('input', function() {
            document.getElementById('type_appareil').value = this.value;
        });
        
        // Initialiser le champ type_appareil au chargement de la page
        document.getElementById('type_appareil').value = document.getElementById('modele').value;
        
        // ... existing code ...
        
        // Fonction pour la validation du step 1
        function validateStep1() {
            let valid = true;
            if (!document.getElementById('photo_appareil').files.length) {
                document.getElementById('photo_appareil').classList.add('is-invalid');
                valid = false;
            } else {
                document.getElementById('photo_appareil').classList.remove('is-invalid');
            }
            
            // S'assurer que type_appareil a une valeur (modèle)
            if (document.getElementById('modele').value) {
                document.getElementById('type_appareil').value = document.getElementById('modele').value;
            }
            
            return valid;
        }
        
        // ... existing code ...
    });

    // Ajouter cette fonction de téléchargement d'image
    function downloadImage(imgElement) {
        const img = document.getElementById(imgElement);
        if (!img || img.src.includes('no-image.png')) {
            alert("Aucune image disponible à télécharger");
            return;
        }
        
        // Créer un lien temporaire pour le téléchargement
        const link = document.createElement('a');
        
        // Si c'est une image base64
        if (img.src.startsWith('data:image')) {
            link.href = img.src;
        } else {
            // Si c'est une URL
            link.href = img.src;
        }
        
        // Définir un nom de fichier
        const filename = imgElement.replace('modal', '').toLowerCase() + '_' + Date.now() + '.png';
        link.download = filename;
        
        // Simuler un clic pour déclencher le téléchargement
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    // Fonction pour mettre à jour le récapitulatif
    function updateRecap() {
        document.getElementById('recap_appareil').textContent = document.getElementById('modele').value || '-';
        document.getElementById('recap_modele').textContent = document.getElementById('modele').value || '-';
        
        // Récupérer l'état sélectionné (boutons radio au lieu de select)
        const etatValue = document.querySelector('input[name="fonctionnel"]:checked').value === "1" ? 
            "Fonctionnel" : "Non fonctionnel";
        document.getElementById('recap_etat').textContent = etatValue;
        
        document.getElementById('recap_client').textContent = document.getElementById('nom_client_selectionne').textContent;
        
        // Le prix est mis à jour dynamiquement
        document.getElementById('prix').addEventListener('input', function() {
            if (this.value) {
                document.getElementById('recap_prix').textContent = new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(this.value);
            } else {
                document.getElementById('recap_prix').textContent = '-';
            }
        });
    }
    
    // ===== FONCTIONS DE SÉLECTION MULTIPLE =====
    
    // Fonction pour sélectionner/désélectionner tout
    document.getElementById('selectAll').addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.rachat-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        updateSelectionCount();
    });
    
    // Fonction pour mettre à jour le compteur de sélection
    function updateSelectionCount() {
        const selectedCheckboxes = document.querySelectorAll('.rachat-checkbox:checked');
        const count = selectedCheckboxes.length;
        const selectedCount = document.getElementById('selectedCount');
        const btnExport = document.getElementById('btnExportSelection');
        
        selectedCount.textContent = count;
        btnExport.disabled = count === 0;
        
        // Mettre à jour la case "Tout sélectionner"
        const selectAll = document.getElementById('selectAll');
        const allCheckboxes = document.querySelectorAll('.rachat-checkbox');
        selectAll.checked = count === allCheckboxes.length && count > 0;
        selectAll.indeterminate = count > 0 && count < allCheckboxes.length;
    }
    
    // Fonction pour exporter plusieurs rachats
    async function exportMultipleRachats() {
        const selectedCheckboxes = document.querySelectorAll('.rachat-checkbox:checked');
        if (selectedCheckboxes.length === 0) {
            alert('Veuillez sélectionner au moins un rachat à exporter.');
            return;
        }
        
        const selectedIds = Array.from(selectedCheckboxes).map(cb => cb.value);
        
        try {
            // Afficher un indicateur de chargement
            const loadingModal = new bootstrap.Modal(document.createElement('div'));
            const loadingContent = document.createElement('div');
            loadingContent.innerHTML = `
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-body text-center py-4">
                            <div class="spinner-border text-primary mb-3" role="status"></div>
                            <h5>Génération du PDF multi-pages en cours...</h5>
                            <p class="text-muted">Veuillez patienter pendant la création du PDF pour ${selectedIds.length} rachat(s).</p>
                        </div>
                    </div>
                </div>
            `;
            loadingContent.classList.add('modal', 'fade');
            loadingContent.setAttribute('tabindex', '-1');
            document.body.appendChild(loadingContent);
            const loadingModalInstance = new bootstrap.Modal(loadingContent);
            loadingModalInstance.show();
            
            // Récupérer les données pour tous les rachats sélectionnés
            const responses = await Promise.all(
                selectedIds.map(async id => {
                    const response = await fetch(`/ajax/export_attestation.php?id=${id}`, {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    const data = await response.json();
                    if (data.success && data.html) {
                        return data.html;
                    } else {
                        throw new Error(`Erreur pour le rachat ${id}: ${data.error || 'Données manquantes'}`);
                    }
                })
            );
            
            // Créer un iframe pour imprimer toutes les pages
            const iframe = document.createElement('iframe');
            iframe.style.position = 'absolute';
            iframe.style.left = '-9999px';
            iframe.style.top = '-9999px';
            iframe.style.width = '210mm';
            iframe.style.height = '297mm';
            document.body.appendChild(iframe);
            
            // Combiner tous les contenus HTML avec des sauts de page
            console.log('Responses reçues:', responses.length);
            const combinedContent = responses.map((content, index) => {
                console.log(`Contenu ${index + 1}:`, content.substring(0, 200) + '...');
                const pageBreak = index < responses.length - 1 ? '<div style="page-break-after: always;"></div>' : '';
                return content + pageBreak;
            }).join('');
            
            console.log('Contenu final combiné:', combinedContent.substring(0, 500) + '...');
            
            // Vérifier que le contenu n'est pas vide
            if (!combinedContent || combinedContent.trim().length === 0) {
                throw new Error('Le contenu HTML est vide');
            }
            
            iframe.contentDocument.open();
            iframe.contentDocument.write(combinedContent);
            iframe.contentDocument.close();
            
            // Attendre que le contenu soit chargé et imprimer
            setTimeout(() => {
                try {
                    iframe.contentWindow.print();
                    
                    // Nettoyer après impression
                    setTimeout(() => {
                        document.body.removeChild(iframe);
                        loadingModalInstance.hide();
                        loadingContent.remove();
                    }, 1000);
                    
                } catch (printError) {
                    console.error('Erreur lors de l\'impression:', printError);
                    alert('Erreur lors de la génération du PDF. Veuillez réessayer.');
                    document.body.removeChild(iframe);
                    loadingModalInstance.hide();
                    loadingContent.remove();
                }
            }, 2000);
            
        } catch (error) {
            console.error('Erreur lors de l\'export multiple:', error);
            alert('Erreur lors de l\'export. Veuillez réessayer.');
        }
    }
    
    // Rendre les fonctions disponibles globalement
    window.updateSelectionCount = updateSelectionCount;
    window.exportMultipleRachats = exportMultipleRachats;
    
    // ===== SYSTÈME MODAL CAMÉRA PLEIN ÉCRAN =====
    
    let currentPhotoType = null;
    let rachatModalInstance = null;
    let cameraModalInstance = null;
    let fullscreenStream = null;
    
    // Fonction pour ouvrir le modal caméra
    window.openCameraModal = function(photoType) {
        console.log('📸 Ouverture modal caméra pour:', photoType);
        
        currentPhotoType = photoType;
        
        // Sauvegarder l'instance du modal rachat
        rachatModalInstance = bootstrap.Modal.getInstance(document.getElementById('newRachatModal'));
        
        // Fermer temporairement le modal rachat
        if (rachatModalInstance) {
            rachatModalInstance.hide();
        }
        
        // Mettre à jour le titre selon le type de photo
        const titles = {
            'identite': 'Pièce d\'identité',
            'appareil': 'Photo de l\'appareil'
        };
        document.getElementById('cameraModalTitle').textContent = titles[photoType] || 'Prendre une photo';
        
        // Ouvrir le modal caméra
        setTimeout(() => {
            cameraModalInstance = new bootstrap.Modal(document.getElementById('cameraModal'));
            cameraModalInstance.show();
            
            // Démarrer la caméra après l'ouverture du modal
            setTimeout(() => {
                startFullscreenCamera();
            }, 500);
        }, 300);
    };
    
    // Fonction pour démarrer la caméra plein écran
    async function startFullscreenCamera() {
        try {
            console.log('🎥 Démarrage caméra plein écran...');
            
            // Arrêter toute caméra existante
            if (fullscreenStream) {
                fullscreenStream.getTracks().forEach(track => track.stop());
            }
            
            const video = document.getElementById('cameraVideoFullscreen');
            const constraints = {
                video: {
                    width: { ideal: 1280 },
                    height: { ideal: 720 },
                    facingMode: 'environment' // Caméra arrière par défaut
                },
                audio: false
            };
            
            fullscreenStream = await navigator.mediaDevices.getUserMedia(constraints);
            video.srcObject = fullscreenStream;
            
            video.onloadedmetadata = () => {
                video.play().then(() => {
                    console.log('✅ Caméra plein écran démarrée');
                }).catch(e => console.error('Erreur lecture vidéo:', e));
            };
            
        } catch (error) {
            console.error('❌ Erreur caméra plein écran:', error);
            alert('Impossible d\'accéder à la caméra. Vérifiez les permissions.');
            closeCameraModal();
        }
    }
    
    // Fonction pour prendre une photo en plein écran
    window.takePictureFullscreen = function() {
        try {
            console.log('📸 Capture photo pour:', currentPhotoType);
            
            const video = document.getElementById('cameraVideoFullscreen');
            const canvas = document.getElementById('cameraCanvasFullscreen');
            const context = canvas.getContext('2d');
            
            // Ajuster la taille du canvas à la vidéo
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            
            // Capturer l'image
            context.drawImage(video, 0, 0, canvas.width, canvas.height);
            
            // Convertir en blob et créer un fichier
            canvas.toBlob((blob) => {
                const file = new File([blob], `${currentPhotoType}_${Date.now()}.jpg`, { type: 'image/jpeg' });
                
                // Assigner le fichier au bon input
                if (currentPhotoType === 'identite') {
                    setFileToInput('photo_identite', file);
                } else if (currentPhotoType === 'appareil') {
                    setFileToInput('photo_appareil', file);
                }
                
                console.log('✅ Photo capturée et assignée');
                closeCameraModal();
                
            }, 'image/jpeg', 0.9);
            
        } catch (error) {
            console.error('❌ Erreur capture:', error);
            alert('Erreur lors de la capture de la photo.');
        }
    };
    
    // Fonction pour assigner un fichier à un input
    function setFileToInput(inputId, file) {
        console.log('📁 Assignation fichier à:', inputId, 'Taille:', file.size);
        
        const input = document.getElementById(inputId);
        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(file);
        input.files = dataTransfer.files;
        
        console.log('✅ Fichier assigné. Nombre de fichiers:', input.files.length);
        
        // Déclencher l'événement change pour mettre à jour l'interface
        input.dispatchEvent(new Event('change', { bubbles: true }));
        
        // Créer un aperçu de l'image
        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(e) {
                // Afficher l'aperçu selon le type de photo
                if (inputId === 'photo_identite') {
                    showPreviewIdentite(e.target.result);
                } else if (inputId === 'photo_appareil') {
                    showPreviewAppareil(e.target.result);
                }
            };
            reader.readAsDataURL(file);
        }
        
        // Forcer la validation du formulaire
        setTimeout(() => {
            input.classList.remove('is-invalid');
            input.classList.add('is-valid');
            console.log('✅ Validation mise à jour pour:', inputId);
        }, 100);
    }
    
    // Fonction pour afficher l'aperçu de la photo d'identité
    function showPreviewIdentite(imageSrc) {
        const preview = document.getElementById('previewIdentite');
        const previewImg = document.getElementById('previewIdentiteImg');
        
        if (preview && previewImg) {
            previewImg.src = imageSrc;
            preview.classList.remove('d-none');
            console.log('🖼️ Aperçu identité affiché');
        }
    }
    
    // Fonction pour afficher l'aperçu de la photo d'appareil
    function showPreviewAppareil(imageSrc) {
        const preview = document.getElementById('previewAppareil');
        const previewImg = document.getElementById('previewAppareilImg');
        
        if (preview && previewImg) {
            previewImg.src = imageSrc;
            preview.classList.remove('d-none');
            console.log('🖼️ Aperçu appareil affiché');
        }
    }
    
    // Fonction pour fermer le modal caméra
    window.closeCameraModal = function() {
        console.log('🔄 Fermeture modal caméra...');
        
        // Arrêter la caméra
        if (fullscreenStream) {
            fullscreenStream.getTracks().forEach(track => track.stop());
            fullscreenStream = null;
        }
        
        // Fermer le modal caméra
        if (cameraModalInstance) {
            cameraModalInstance.hide();
        }
        
        // Réouvrir le modal rachat après un délai
        setTimeout(() => {
            if (rachatModalInstance) {
                rachatModalInstance.show();
            } else {
                // Si l'instance n'existe plus, en créer une nouvelle
                const newRachatModal = new bootstrap.Modal(document.getElementById('newRachatModal'));
                newRachatModal.show();
            }
            console.log('✅ Modal rachat réouvert');
            
            // Réinitialiser les variables APRÈS la réouverture
            setTimeout(() => {
                console.log('🏁 Reset du flag currentPhotoType après réouverture complète');
                currentPhotoType = null;
            }, 500);
        }, 300);
        
        // Réinitialiser seulement l'instance du modal caméra
        cameraModalInstance = null;
    };
    </script>


</div> <!-- Fermeture de mainContent -->

<style>
.loader {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 9999;
  background: linear-gradient(0deg, #0f1419, #0a0f1a, #000);
}

.loader-wrapper {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 180px;
  height: 180px;
  font-family: "Inter", sans-serif;
  font-size: 1.1em;
  font-weight: 300;
  color: white;
  border-radius: 50%;
  background-color: transparent;
  -webkit-user-select: none;
  -moz-user-select: none;
  -ms-user-select: none;
  user-select: none;
}

.loader-circle {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  aspect-ratio: 1 / 1;
  border-radius: 50%;
  background-color: transparent;
  animation: loader-combined 2.3s linear infinite;
  z-index: 0;
}
@keyframes loader-combined {
  0% {
    transform: rotate(90deg);
    box-shadow:
      0 6px 12px 0 #38bdf8 inset,
      0 12px 18px 0 #005dff inset,
      0 36px 36px 0 #1e40af inset,
      0 0 3px 1.2px rgba(56, 189, 248, 0.3),
      0 0 6px 1.8px rgba(0, 93, 255, 0.2);
  }
  25% {
    transform: rotate(180deg);
    box-shadow:
      0 6px 12px 0 #0099ff inset,
      0 12px 18px 0 #38bdf8 inset,
      0 36px 36px 0 #005dff inset,
      0 0 6px 2.4px rgba(56, 189, 248, 0.3),
      0 0 12px 3.6px rgba(0, 93, 255, 0.2),
      0 0 18px 6px rgba(30, 64, 175, 0.15);
  }
  50% {
    transform: rotate(270deg);
    box-shadow:
      0 6px 12px 0 #60a5fa inset,
      0 12px 6px 0 #0284c7 inset,
      0 24px 36px 0 #005dff inset,
      0 0 3px 1.2px rgba(56, 189, 248, 0.3),
      0 0 6px 1.8px rgba(0, 93, 255, 0.2);
  }
  75% {
    transform: rotate(360deg);
    box-shadow:
      0 6px 12px 0 #3b82f6 inset,
      0 12px 18px 0 #0ea5e9 inset,
      0 36px 36px 0 #2563eb inset,
      0 0 6px 2.4px rgba(56, 189, 248, 0.3),
      0 0 12px 3.6px rgba(0, 93, 255, 0.2),
      0 0 18px 6px rgba(30, 64, 175, 0.15);
  }
  100% {
    transform: rotate(450deg);
    box-shadow:
      0 6px 12px 0 #4dc8fd inset,
      0 12px 18px 0 #005dff inset,
      0 36px 36px 0 #1e40af inset,
      0 0 3px 1.2px rgba(56, 189, 248, 0.3),
      0 0 6px 1.8px rgba(0, 93, 255, 0.2);
  }
}

.loader-letter {
  display: inline-block;
  opacity: 0.4;
  transform: translateY(0);
  animation: loader-letter-anim 2.4s infinite;
  z-index: 1;
  border-radius: 50ch;
  border: none;
}

.loader-letter:nth-child(1) {
  animation-delay: 0s;
}
.loader-letter:nth-child(2) {
  animation-delay: 0.1s;
}
.loader-letter:nth-child(3) {
  animation-delay: 0.2s;
}
.loader-letter:nth-child(4) {
  animation-delay: 0.3s;
}
.loader-letter:nth-child(5) {
  animation-delay: 0.4s;
}

@keyframes loader-letter-anim {
  0%,
  100% {
    opacity: 0.4;
    transform: translateY(0);
  }
  20% {
    opacity: 1;
    text-shadow: #f8fcff 0 0 5px;
  }
  40% {
    opacity: 0.7;
    transform: translateY(0);
  }
}

.loader.fade-out {
  opacity: 0;
  transition: opacity 0.5s ease-out;
}

.loader.hidden {
  display: none;
}

#mainContent.fade-in {
  opacity: 1;
  transition: opacity 0.5s ease-in;
}

.dark-loader {
  display: flex;
}

.light-loader {
  display: none;
  background: #ffffff !important;
}

body:not(.dark-mode) #pageLoader {
  background: #ffffff !important;
}

body:not(.dark-mode) .dark-loader {
  display: none;
}

body:not(.dark-mode) .light-loader {
  display: flex;
}

.loader-circle-light {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  aspect-ratio: 1 / 1;
  border-radius: 50%;
  background-color: transparent;
  animation: loader-combined-light 2.3s linear infinite;
  z-index: 0;
}

@keyframes loader-combined-light {
  0% {
    transform: rotate(90deg);
    box-shadow:
      0 6px 12px 0 #1e40af inset,
      0 12px 18px 0 #3b82f6 inset,
      0 36px 36px 0 #60a5fa inset,
      0 0 3px 1.2px rgba(30, 64, 175, 0.4),
      0 0 6px 1.8px rgba(59, 130, 246, 0.3);
  }
  25% {
    transform: rotate(180deg);
    box-shadow:
      0 6px 12px 0 #2563eb inset,
      0 12px 18px 0 #1e40af inset,
      0 36px 36px 0 #3b82f6 inset,
      0 0 6px 2.4px rgba(30, 64, 175, 0.4),
      0 0 12px 3.6px rgba(59, 130, 246, 0.3),
      0 0 18px 6px rgba(96, 165, 250, 0.2);
  }
  50% {
    transform: rotate(270deg);
    box-shadow:
      0 6px 12px 0 #3b82f6 inset,
      0 12px 6px 0 #1d4ed8 inset,
      0 24px 36px 0 #2563eb inset,
      0 0 3px 1.2px rgba(30, 64, 175, 0.4),
      0 0 6px 1.8px rgba(59, 130, 246, 0.3);
  }
  75% {
    transform: rotate(360deg);
    box-shadow:
      0 6px 12px 0 #1e40af inset,
      0 12px 18px 0 #2563eb inset,
      0 36px 36px 0 #60a5fa inset,
      0 0 6px 2.4px rgba(30, 64, 175, 0.4),
      0 0 12px 3.6px rgba(59, 130, 246, 0.3),
      0 0 18px 6px rgba(96, 165, 250, 0.2);
  }
  100% {
    transform: rotate(450deg);
    box-shadow:
      0 6px 12px 0 #3b82f6 inset,
      0 12px 18px 0 #2563eb inset,
      0 36px 36px 0 #1e40af inset,
      0 0 3px 1.2px rgba(30, 64, 175, 0.4),
      0 0 6px 1.8px rgba(59, 130, 246, 0.3);
  }
}

.loader-text-light {
  display: flex;
  gap: 2px;
  z-index: 1;
}

.loader-text-light .loader-letter {
  display: inline-block;
  opacity: 0.4;
  transform: translateY(0);
  animation: loader-letter-anim-light 2.4s infinite;
  z-index: 1;
  font-family: "Inter", sans-serif;
  font-size: 1.1em;
  font-weight: 300;
  color: #1f2937;
  border-radius: 50ch;
  border: none;
}

.loader-text-light .loader-letter:nth-child(1) {
  animation-delay: 0s;
}
.loader-text-light .loader-letter:nth-child(2) {
  animation-delay: 0.1s;
}
.loader-text-light .loader-letter:nth-child(3) {
  animation-delay: 0.2s;
}
.loader-text-light .loader-letter:nth-child(4) {
  animation-delay: 0.3s;
}
.loader-text-light .loader-letter:nth-child(5) {
  animation-delay: 0.4s;
}

@keyframes loader-letter-anim-light {
  0%,
  100% {
    opacity: 0.4;
    transform: translateY(0);
  }
  20% {
    opacity: 1;
    text-shadow: #1e40af 0 0 5px;
  }
  40% {
    opacity: 0.7;
    transform: translateY(0);
  }
}

body,
body.dark-mode,
body.light-mode,
html {
  background: linear-gradient(0deg, #0f1419, #0a0f1a, #000) !important;
  background-attachment: fixed !important;
  min-height: 100vh !important;
}

.main-content,
.main-content * {
  background: transparent !important;
}

.card,
.modal-content,
.rachat-card {
  background: rgba(255, 255, 255, 0.95) !important;
  backdrop-filter: blur(10px) !important;
}

.dark-mode .card,
.dark-mode .modal-content,
.dark-mode .rachat-card {
  background: rgba(30, 41, 59, 0.95) !important;
  backdrop-filter: blur(10px) !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const loader = document.getElementById('pageLoader');
    const mainContent = document.getElementById('mainContent');
    
    setTimeout(function() {
        loader.classList.add('fade-out');
        setTimeout(function() {
            loader.classList.add('hidden');
            mainContent.style.display = 'block';
            mainContent.classList.add('fade-in');
        }, 500);
    }, 300);
});
</script>

<?php
// Afficher le footer
include_once __DIR__ . '/../includes/footer.php';
?>