<?php

/**
 * Page de gestion des clients - Version COMPLÈTEMENT REFAITE
 * Interface moderne sans Bootstrap et sans modals problématiques
 */

// Configuration de la pagination
$items_per_page = 20;
$current_page = max(1, intval($_GET['p'] ?? 1));
$offset = ($current_page - 1) * $items_per_page;

// Paramètres de recherche et tri
$search = trim($_GET['search'] ?? '');
$sort_by = $_GET['sort'] ?? 'nom';
$sort_order = ($_GET['order'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';

// Validation des paramètres de tri
$allowed_sort_fields = ['nom', 'prenom', 'telephone', 'email', 'date_creation', 'nombre_reparations'];
if (!in_array($sort_by, $allowed_sort_fields)) {
    $sort_by = 'nom';
}

try {
    $shop_pdo = getShopDBConnection();
    
    // Construction de la requête avec recherche étendue (clients + réparations)
    $where_conditions = [];
    $params = [];
    
    if (!empty($search)) {
        // Recherche dans clients ET reparations
        $where_conditions[] = "(
            c.nom LIKE :search1 OR 
            c.prenom LIKE :search2 OR 
            c.telephone LIKE :search3 OR 
            c.email LIKE :search4 OR
            r.modele LIKE :search5 OR
            r.description_probleme LIKE :search6 OR
            r.notes_techniques LIKE :search7
        )";
        $searchTerm = "%$search%";
        $params['search1'] = $searchTerm;
        $params['search2'] = $searchTerm;
        $params['search3'] = $searchTerm;
        $params['search4'] = $searchTerm;
        $params['search5'] = $searchTerm;
        $params['search6'] = $searchTerm;
        $params['search7'] = $searchTerm;
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    // Requête pour compter le total (avec jointure pour chercher dans reparations)
    $count_sql = "SELECT COUNT(DISTINCT c.id) as total 
                  FROM clients c 
                  LEFT JOIN reparations r ON c.id = r.client_id 
                  $where_clause";
    $count_stmt = $shop_pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total_items = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_pages = ceil($total_items / $items_per_page);
    
    // Requête principale avec jointure pour compter les réparations ET chercher dans reparations
    $sql = "SELECT c.*, 
            COUNT(r.id) as nombre_reparations
        FROM clients c 
        LEFT JOIN reparations r ON c.id = r.client_id 
            $where_clause
        GROUP BY c.id 
            ORDER BY $sort_by $sort_order
            LIMIT :limit OFFSET :offset";
    
    $stmt = $shop_pdo->prepare($sql);
    
    // Ajouter les paramètres de pagination
    $params['limit'] = $items_per_page;
    $params['offset'] = $offset;
    
    // Bind des paramètres
    foreach ($params as $key => $value) {
        if ($key === 'limit' || $key === 'offset') {
            $stmt->bindValue(":$key", $value, PDO::PARAM_INT);
        } else {
            $stmt->bindValue(":$key", $value, PDO::PARAM_STR);
        }
    }
    
    $stmt->execute();
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Erreur lors de la récupération des clients: " . $e->getMessage());
    $clients = [];
    $total_pages = 0;
    $total_items = 0;
}

// Fonction pour générer les URLs de tri
function getSortUrl($field) {
    global $sort_by, $sort_order, $search, $current_page;
    
    $new_order = ($sort_by === $field && $sort_order === 'ASC') ? 'DESC' : 'ASC';
    $params = ['page' => 'clients', 'sort' => $field, 'order' => $new_order];
    
    if (!empty($search)) {
        $params['search'] = $search;
    }
    
    return 'index.php?' . http_build_query($params);
}

// Fonction pour générer l'icône de tri
function getSortIcon($field) {
    global $sort_by, $sort_order;
    
    if ($sort_by !== $field) {
        return '↕️';
    }
    
    return $sort_order === 'ASC' ? '⬆️' : '⬇️';
}
?>

<style>
/* CSS personnalisé pour la page clients - Responsive */
.clients-container {
    width: 100%;
    max-width: none; /* Suppression de la limite de largeur */
    margin: 0;
    padding: 15px 20px 30px 20px;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
    background: #f8fafc;
    min-height: 100vh;
    box-sizing: border-box;
}

/* Optimisation pour écrans moyens */
@media (min-width: 768px) {
    .clients-container {
        padding: 15px 30px 30px 30px;
    }
}

/* Optimisation pour grands écrans */
@media (min-width: 1200px) {
    .clients-container {
        padding: 15px 40px 40px 40px;
    }
}

@media (min-width: 1600px) {
    .clients-container {
        padding: 20px 60px 50px 60px;
    }
}

.page-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 30px;
    border-radius: 16px;
    margin-bottom: 30px;
    box-shadow: 0 8px 32px rgba(102, 126, 234, 0.2);
}

.page-title {
    font-size: 2.5rem;
    font-weight: 700;
    margin: 0 0 10px 0;
    text-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.page-subtitle {
    font-size: 1.1rem;
    opacity: 0.9;
    margin: 0;
}

.stats-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 25px;
    margin-bottom: 35px;
}

/* Optimisation des stats pour PC */
@media (min-width: 1200px) {
    .stats-row {
        grid-template-columns: repeat(4, 1fr); /* 4 colonnes sur écran large */
        gap: 30px;
    }
}

@media (min-width: 900px) and (max-width: 1199px) {
    .stats-row {
        grid-template-columns: repeat(3, 1fr); /* 3 colonnes sur écran moyen */
        gap: 25px;
    }
}

.stat-card {
    background: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 4px 16px rgba(0,0,0,0.1);
    border-left: 4px solid #667eea;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.15);
}

.stat-number {
    font-size: 2rem;
    font-weight: 700;
    color: #667eea;
    margin: 0;
}

.stat-label {
    color: #64748b;
    font-size: 0.9rem;
    margin: 5px 0 0 0;
}

.controls-section {
    background: white;
    padding: 30px;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    margin-bottom: 35px;
    border: 1px solid #f1f5f9;
}

.controls-grid {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 25px;
    align-items: center;
}

.search-container {
    position: relative;
    max-width: 500px; /* Plus large sur PC */
}

/* Optimisation contrôles pour PC */
@media (min-width: 1024px) {
    .controls-grid {
        grid-template-columns: 1fr auto auto; /* Plus d'espace pour actions */
        gap: 30px;
    }
    
    .search-container {
        max-width: 600px;
    }
}

@media (min-width: 1400px) {
    .controls-section {
        padding: 35px 40px;
    }
    
    .search-container {
        max-width: 700px;
    }
}

.search-input {
    width: 100%;
    padding: 12px 45px 12px 16px;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    font-size: 1rem;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
    background: white;
}

.search-input:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.search-icon {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 1.2rem;
}

.btn {
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 16px rgba(102, 126, 234, 0.4);
}

.table-container {
    background: white;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    margin-bottom: 30px;
    border: 1px solid #f1f5f9;
    width: 100%;
    box-sizing: border-box;
}

/* Tableau moderne optimisé PC */
.modern-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    font-size: 0.95rem;
    background: white;
    table-layout: auto; /* Changé à auto pour meilleure compatibilité */
    border-radius: 0;
    overflow: hidden;
}

/* Styles adaptatifs pour écrans plus larges */
@media (min-width: 1024px) {
    .modern-table {
        font-size: 1rem;
    }
    
    .modern-table th,
    .modern-table td {
        padding: 16px 14px;
    }
}

@media (min-width: 1400px) {
    .modern-table {
        font-size: 1.05rem;
    }
    
    .modern-table th,
    .modern-table td {
        padding: 18px 16px;
    }
}

.modern-table th {
    background: #f8fafc;
    padding: 16px 12px;
    text-align: left;
    font-weight: 600;
    color: #475569;
    border-bottom: 2px solid #e2e8f0;
    font-size: 0.9rem;
    white-space: nowrap;
}

.modern-table td {
    padding: 14px 12px;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
    background: white;
}

.modern-table tbody tr:hover {
    background: #f8fafc;
}

/* Styles simples pour le tableau */
.modern-table tbody tr {
    transition: background-color 0.2s ease;
}

.sort-header {
    cursor: pointer;
    user-select: none;
    transition: color 0.2s ease;
    display: flex;
    align-items: center;
    gap: 6px;
    text-decoration: none;
    color: inherit;
}

.sort-header:hover {
    color: #667eea;
    text-decoration: none;
}

/* Styles simples pour les données */
.client-id {
    font-weight: 600;
    color: #667eea;
    font-size: 0.9rem;
}

.client-name {
    font-weight: 600;
    color: #1e293b;
}

.contact-group {
    display: flex;
    align-items: center;
    gap: 10px;
}

.contact-link {
    color: #059669;
    text-decoration: none;
    font-weight: 500;
    transition: color 0.2s ease;
}

.contact-link:hover {
    color: #047857;
}

.sms-btn {
    background: #3b82f6;
    color: white;
    border: none;
    border-radius: 6px;
    padding: 6px 10px;
    cursor: pointer;
    font-size: 1rem;
    transition: background 0.2s ease;
}

.sms-btn:hover {
    background: #2563eb;
}

.email-link {
    color: #7c3aed;
    text-decoration: none;
    font-weight: 500;
    transition: color 0.2s ease;
}

.email-link:hover {
    color: #6d28d9;
}

.date-text {
    color: #64748b;
    font-size: 0.9rem;
}

.badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
}

.badge-primary {
    background: #dbeafe;
    color: #1d4ed8;
}

.badge-success {
    background: #dcfce7;
    color: #166534;
}

.badge-warning {
    background: #fef3c7;
    color: #92400e;
}

.action-buttons {
    display: flex;
    gap: 8px;
    align-items: center;
}

.btn-sm {
    padding: 6px 12px;
    font-size: 0.85rem;
    border-radius: 6px;
}

.btn-info {
    background: #0ea5e9;
    color: white;
}

.btn-info:hover {
    background: #0284c7;
}

.btn-danger {
    background: #ef4444;
    color: white;
}

.btn-danger:hover {
    background: #dc2626;
}

.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 10px;
    margin-top: 30px;
}

.pagination a, .pagination span {
    padding: 8px 16px;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    text-decoration: none;
    color: #475569;
    transition: all 0.2s ease;
}

.pagination a:hover {
    background: #667eea;
    color: white;
    border-color: #667eea;
}

.pagination .current {
    background: #667eea;
    color: white;
    border-color: #667eea;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #64748b;
}

.empty-icon {
    font-size: 4rem;
    margin-bottom: 20px;
    opacity: 0.5;
}

/* Modal SMS Simple */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    display: none !important; /* Forcer display none par défaut */
    justify-content: center;
    align-items: center;
    z-index: 1000;
    visibility: hidden !important; /* Double sécurité */
    opacity: 0 !important; /* Triple sécurité */
}

.modal-overlay.show {
    display: flex !important;
    visibility: visible !important;
    opacity: 1 !important;
}

.modal-content {
    background: white;
    border-radius: 12px;
    padding: 0;
    max-width: 500px;
    width: 90%;
    max-height: 90vh;
    overflow: hidden;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
}

.modal-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-title {
    margin: 0;
    font-size: 1.2rem;
    font-weight: 600;
}

.modal-close {
    background: none;
    border: none;
    color: white;
    font-size: 1.5rem;
    cursor: pointer;
    padding: 0;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.2s ease;
}

.modal-close:hover {
    background: rgba(255, 255, 255, 0.2);
}

.modal-body {
    padding: 25px;
}

.form-group {
    margin-bottom: 20px;
}

.form-label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #374151;
}

.form-control {
    width: 100%;
    padding: 12px;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    font-size: 1rem;
    transition: border-color 0.2s ease;
    box-sizing: border-box;
    resize: vertical;
}

.form-control:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.client-info {
    background: #f8fafc;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    border-left: 4px solid #667eea;
}

.modal-footer {
    padding: 20px 25px;
    background: #f8fafc;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

.btn-secondary {
    background: #6b7280;
    color: white;
}

.btn-secondary:hover {
    background: #4b5563;
}

.btn-success {
    background: #059669;
    color: white;
}

.btn-success:hover {
    background: #047857;
}

/* Responsive pour tablettes */
@media (max-width: 1199px) and (min-width: 768px) {
    .stats-row {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .modern-table {
        font-size: 0.95rem;
    }
    
    .controls-section {
        padding: 25px;
    }
}

/* Responsive pour mobile */
@media (max-width: 767px) {
    /* Masquer la navbar desktop sur mobile */
    #desktop-navbar,
    nav#desktop-navbar {
        display: none !important;
        visibility: hidden !important;
        opacity: 0 !important;
    }
    
    .clients-container {
        padding: 20px 15px 100px 15px; /* Plus de padding en bas pour le dock mobile */
    }
    
    .page-title {
        font-size: 2rem;
    }
    
    .stats-row {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .stat-card {
        padding: 20px;
    }
    
    .controls-grid {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .search-container {
        max-width: 100%;
    }
    
    .table-container {
        overflow-x: auto;
        border-radius: 12px;
        margin: 0 -15px; /* Étendre sur les bords sur mobile */
        border-radius: 0;
    }
    
    .modern-table {
        min-width: 600px;
        font-size: 0.9rem;
    }
    
    .modern-table th,
    .modern-table td {
        padding: 12px 10px;
    }
    
    .modern-table th {
        font-size: 0.85rem;
    }
    
    .action-buttons {
        flex-direction: column;
        gap: 5px;
    }
    
    .btn-sm {
        padding: 8px 12px;
        font-size: 0.8rem;
    }
    
    .client-id {
        font-size: 0.8rem;
        padding: 4px 8px;
    }
    
    .client-name {
        font-size: 1rem;
    }
    
    .modal-content {
        width: 95%;
        margin: 20px;
    }
}

/* === MODAL HISTORIQUE MODERNE === */
.modern-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(8px);
    display: none !important;
    justify-content: center;
    align-items: center;
    z-index: 9999;
    padding: 20px;
    box-sizing: border-box;
}

.modern-modal-overlay.show {
    display: flex !important;
    animation: fadeIn 0.3s ease-out;
}

.modern-modal-container {
    background: white;
    border-radius: 24px;
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
    max-width: 1000px;
    width: 100%;
    max-height: 90vh;
    overflow: hidden;
    animation: slideInUp 0.4s ease-out;
    display: flex;
    flex-direction: column;
}

.modern-modal-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: none;
}

.modal-header-content {
    display: flex;
    align-items: center;
    gap: 20px;
}

.modal-icon {
    font-size: 2.5rem;
    opacity: 0.9;
}

.modal-title-section h2 {
    margin: 0;
    font-size: 1.8rem;
    font-weight: 700;
    line-height: 1.2;
}

.modal-subtitle {
    margin: 5px 0 0 0;
    opacity: 0.9;
    font-size: 1rem;
    font-weight: 400;
}

.modern-modal-close {
    background: rgba(255, 255, 255, 0.2);
    border: none;
    border-radius: 12px;
    width: 44px;
    height: 44px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
    color: white;
}

.modern-modal-close:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: scale(1.1);
}

.modern-modal-body {
    flex: 1;
    overflow-y: auto;
    padding: 0;
    background: #f8fafc;
}

.loading-spinner {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 60px 30px;
    gap: 20px;
}

.spinner {
    width: 50px;
    height: 50px;
    border: 4px solid #e2e8f0;
    border-top: 4px solid #667eea;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

.loading-spinner p {
    color: #64748b;
    font-size: 1.1rem;
    margin: 0;
}

.historique-content {
    padding: 30px;
    display: none;
}

.historique-content.loaded {
    display: block;
}

.modern-modal-footer {
    background: white;
    padding: 25px 30px;
    border-top: 1px solid #e2e8f0;
    display: flex;
    justify-content: flex-end;
    gap: 15px;
}

.modern-btn {
    padding: 12px 24px;
    border-radius: 12px;
    border: none;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.modern-btn-secondary {
    background: #6b7280;
    color: white;
}

.modern-btn-secondary:hover {
    background: #4b5563;
    transform: translateY(-1px);
}

/* Mode sombre pour le modal */
body.dark-mode .modern-modal-container {
    background: #1e293b;
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.7);
    border: 1px solid #334155;
}

body.dark-mode .modern-modal-body {
    background: #0f172a;
}

body.dark-mode .historique-content {
    color: #e2e8f0;
}

body.dark-mode .modern-modal-footer {
    background: #1e293b;
    border-top-color: #334155;
}

body.dark-mode .loading-spinner p {
    color: #94a3b8;
}

body.dark-mode .spinner {
    border-color: #334155;
    border-top-color: #667eea;
}

/* Styles spécifiques pour le contenu SMS en mode sombre */
body.dark-mode .historique-content table {
    background: #1e293b !important;
    border: 1px solid #334155;
}

body.dark-mode .historique-content thead tr {
    background: #334155 !important;
}

body.dark-mode .historique-content th {
    color: #e2e8f0 !important;
    border-bottom-color: #475569 !important;
    background: #334155 !important;
}

body.dark-mode .historique-content tbody tr {
    background: #1e293b !important;
    border-bottom-color: #334155 !important;
}

body.dark-mode .historique-content tbody tr:nth-child(even) {
    background: #0f172a !important;
}

body.dark-mode .historique-content tbody tr:hover {
    background: #334155 !important;
}

body.dark-mode .historique-content td {
    color: #e2e8f0 !important;
    border-bottom-color: #334155 !important;
}

body.dark-mode .historique-content .date-formatted {
    color: #94a3b8 !important;
}

body.dark-mode .historique-content .source-table {
    color: #64748b !important;
}

/* Résumé en mode sombre */
body.dark-mode .historique-content > div > div:first-child {
    background: #334155 !important;
    border-left-color: #667eea !important;
}

body.dark-mode .historique-content > div > div:first-child h4 {
    color: #e2e8f0 !important;
}

body.dark-mode .historique-content > div > div:first-child p {
    color: #94a3b8 !important;
}

/* Liens en mode sombre */
body.dark-mode .historique-content a {
    color: #60a5fa !important;
}

body.dark-mode .historique-content a:hover {
    color: #93c5fd !important;
}

/* Statuts colorés optimisés pour le mode sombre */
body.dark-mode .historique-content span[style*="background: #059669"] {
    background: #065f46 !important;
    color: #d1fae5 !important;
}

body.dark-mode .historique-content span[style*="background: #dc2626"] {
    background: #991b1b !important;
    color: #fecaca !important;
}

body.dark-mode .historique-content span[style*="background: #d97706"] {
    background: #92400e !important;
    color: #fed7aa !important;
}

body.dark-mode .historique-content span[style*="background: #0284c7"] {
    background: #0369a1 !important;
    color: #bae6fd !important;
}

/* Animations */
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideInUp {
    from {
        opacity: 0;
        transform: translateY(30px) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

/* Responsive */
@media (max-width: 768px) {
    .modern-modal-container {
        margin: 10px;
        max-height: 95vh;
        border-radius: 16px;
    }
    
    .modern-modal-header {
        padding: 20px;
    }
    
    .modal-header-content {
        gap: 15px;
    }
    
    .modal-icon {
        font-size: 2rem;
    }
    
    .modal-title-section h2 {
        font-size: 1.5rem;
    }
    
    .historique-content,
    .modern-modal-footer {
        padding: 20px;
    }
}


/* ========================================
   FIX NAVBAR & ANIMATION SERVO
   ======================================== */
@media (min-width: 992px) {
    /* Masquer le dock mobile sur desktop */
    #mobile-dock, #dock-recall-zone {
        display: none !important;
        visibility: hidden !important;
        opacity: 0 !important;
        pointer-events: none !important;
        z-index: -1 !important;
    }
    
    /* S'assurer que la navbar desktop est visible */
    #desktop-navbar, nav#desktop-navbar {
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        right: 0 !important;
        z-index: 1030 !important;
        width: 100% !important;
        height: 60px !important;
    }
    
    /* Container fluid de la navbar */
    #desktop-navbar .container-fluid {
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        height: 100% !important;
        padding: 0.5rem 1rem !important;
        min-height: 60px !important;
    }
    
    /* Logo SERVO - CENTRÉ horizontalement ET verticalement */
    /* Logo SERVO - CENTRÉ horizontalement ET verticalement */
    .servo-logo-container {
        position: absolute !important;
        left: 50% !important;
        top: 0 !important;
        transform: translateX(-50%) !important;
        z-index: 99999 !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        width: auto !important;
        height: 100% !important;
        min-width: 200px !important;
        padding-bottom: 5px !important; /* Ajustement fin pour le centrage visuel */
        pointer-events: auto !important;
    }
    
    /* S'assurer que le loader SERVO est visible */
    .servo-logo-container .loader {
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        visibility: visible !important;
        opacity: 1 !important;
    }
    
    /* Animations SVG pour toutes les lettres SERVO */
    .servo-logo-container .dash {
        animation: dashArray 2s ease-in-out infinite, dashOffset 2s linear infinite !important;
    }
    
    .servo-logo-container .spin {
        animation: spinDashArray 2s ease-in-out infinite, spin 8s ease-in-out infinite, dashOffset 2s linear infinite !important;
        transform-origin: center;
    }
    
    /* Keyframes pour l'animation .dash (S, E, R, V) */
    @keyframes dashArray {
        0% { stroke-dasharray: 0 1 359 0; }
        50% { stroke-dasharray: 0 359 1 0; }
        100% { stroke-dasharray: 359 1 0 0; }
    }
    
    /* Keyframes pour l'animation .spin (O) */
    @keyframes spinDashArray {
        0% { stroke-dasharray: 270 90; }
        50% { stroke-dasharray: 0 360; }
        100% { stroke-dasharray: 250 90; }
    }
    
    /* Animation du trait qui se dessine */
    @keyframes dashOffset {
        0% { stroke-dashoffset: 385; }
        100% { stroke-dashoffset: 5; }
    }
    
    /* Animation de rotation pour le O */
    @keyframes spin {
        0% { rotate: 0deg; }
        12.5%, 25% { rotate: 270deg; }
        37.5%, 50% { rotate: 540deg; }
        62.5%, 75% { rotate: 810deg; }
        87.5%, 100% { rotate: 1080deg; }
    }
    
    /* S'assurer que tous les SVG sont visibles */
    .servo-logo-container svg,
    .servo-logo-container path {
        opacity: 1 !important;
        visibility: visible !important;
    }
    
    /* Padding pour le body */
    body {
        padding-top: 80px !important;
    }
}

/* ====================================================================
   ANIMATED BACKGROUND SYSTEM (harmonisé avec taches_moderne.php)
==================================================================== */
/* Mode Jour - Fond animé bleu/violet */
html body {
    background: linear-gradient(-45deg, #e0f2fe, #f0f9ff, #ede9fe, #fdf4ff) !important;
    background-size: 300% 300% !important;
    animation: gradientFlowDay 20s ease infinite !important;
}

@keyframes gradientFlowDay {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}

/* Mode Nuit - Transparent pour voir #animated-bg */
html body.night-mode,
html body.dark-mode {
    background: transparent !important;
    animation: none !important;
}

/* Conteneurs transparents */
.clients-container {
    background: transparent !important;
}

/* Cartes avec fond blanc semi-opaque en mode jour */
html body .stat-card,
html body .controls-section,
html body .table-container,
html body .modal-content,
html body .modern-modal-container {
    background: rgba(255, 255, 255, 0.95) !important;
    backdrop-filter: blur(10px) !important;
}

/* Page header garde son gradient violet */
html body .page-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
    color: white !important;
}

/* Boutons avec fond solide et haute visibilité en mode jour */
html body .btn-primary,
html body .btn {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
    color: white !important;
    border: none !important;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4) !important;
}

html body .btn-info {
    background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%) !important;
    color: white !important;
    box-shadow: 0 4px 12px rgba(14, 165, 233, 0.4) !important;
}

html body .btn-danger {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important;
    color: white !important;
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4) !important;
}

html body .btn-success {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
    color: white !important;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4) !important;
}

html body .btn-secondary {
    background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%) !important;
    color: white !important;
    box-shadow: 0 4px 12px rgba(107, 114, 128, 0.4) !important;
}

/* Mode Nuit - Cartes avec fond sombre */
html body.night-mode .stat-card,
html body.night-mode .controls-section,
html body.night-mode .table-container,
html body.night-mode .modal-content,
html body.night-mode .modern-modal-container,
html body.dark-mode .stat-card,
html body.dark-mode .controls-section,
html body.dark-mode .table-container,
html body.dark-mode .modal-content,
html body.dark-mode .modern-modal-container {
    background: rgba(30, 41, 59, 0.95) !important;
}

/* #animated-bg pour le mode nuit */
#animated-bg {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    z-index: -1;
    pointer-events: none;
    opacity: 0;
    transition: opacity 0.5s ease;
    background-color: #0f172a;
}

body.night-mode #animated-bg,
body.dark-mode #animated-bg {
    opacity: 1;
}

#animated-bg::before,
#animated-bg::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
}

#animated-bg::before {
    background: radial-gradient(circle at 20% 30%, rgba(76, 29, 149, 0.4), transparent 50%),
                radial-gradient(circle at 80% 70%, rgba(59, 130, 246, 0.3), transparent 50%);
    animation: moveBackground1 25s ease-in-out infinite alternate;
}

#animated-bg::after {
    background: radial-gradient(circle at 80% 20%, rgba(139, 92, 246, 0.3), transparent 45%),
                radial-gradient(circle at 10% 80%, rgba(236, 72, 153, 0.25), transparent 45%);
    animation: moveBackground2 30s ease-in-out infinite alternate-reverse;
}

@keyframes moveBackground1 {
    0% { transform: scale(1) translate(0, 0); }
    50% { transform: scale(1.1) translate(30px, -20px); }
    100% { transform: scale(1) translate(-20px, 20px); }
}

@keyframes moveBackground2 {
    0% { transform: scale(1) translate(0, 0); }
    50% { transform: scale(1.15) translate(-30px, 25px); }
    100% { transform: scale(1) translate(20px, -20px); }
}
</style>

<!-- Animated Background for Night Mode -->
<div id="animated-bg"></div>

<!-- Loader Screen -->
<div id="pageLoader" class="loader">
    <!-- Loader Mode Sombre (par défaut) -->
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
    
    <!-- Loader Mode Clair -->
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

<div class="clients-container" id="mainContent" style="display: none;">
    <!-- En-tête de la page -->
    <div class="page-header">
        <h1 class="page-title">👥 Gestion des Clients</h1>
        <p class="page-subtitle">Gérez votre base client et consultez les informations détaillées</p>
            </div>

    <!-- Statistiques -->
    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-number"><?php echo number_format($total_items); ?></div>

            <div class="stat-label">Total clients</div>
            </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo count(array_filter($clients, function($c) { return $c['nombre_reparations'] > 0; })); ?></div>

            <div class="stat-label">Clients actifs</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo array_sum(array_column($clients, 'nombre_reparations')); ?></div>

            <div class="stat-label">Total réparations</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo count(array_filter($clients, function($c) { return $c['nombre_reparations'] == 0; })); ?></div>

            <div class="stat-label">Nouveaux clients</div>
        </div>
    </div>

    <!-- Contrôles -->
    <div class="controls-section">
        <div class="controls-grid">
            <div class="search-container">
                <form method="GET" action="index.php">
                    <input type="hidden" name="page" value="clients">
                    <input type="text" 
                           class="search-input" 
                           name="search" 
                            value="<?php echo htmlspecialchars($search); ?>" 

                           placeholder="Rechercher un client...">
                    <span class="search-icon">🔍</span>
                </form>
                    </div>
            <a href="index.php?page=ajouter_client" class="btn btn-primary">
                ➕ Nouveau Client
            </a>
                    </div>
                </div>
                
    <?php if (empty($clients)): ?>

        <div class="table-container">
            <div class="empty-state">
                <div class="empty-icon">👥</div>
                <h3>Aucun client trouvé</h3>
                <p>
                    <?php if (!empty($search)): ?>

                        Aucun client ne correspond à votre recherche "<?php echo htmlspecialchars($search); ?>".

                    <?php else: ?>

                        Vous n'avez pas encore de clients enregistrés.
                    <?php endif; ?>

                </p>
                <?php if (!empty($search)): ?>

                    <a href="index.php?page=clients" class="btn btn-primary">Voir tous les clients</a>
                <?php else: ?>

                    <a href="index.php?page=ajouter_client" class="btn btn-primary">Ajouter le premier client</a>
                    <?php endif; ?>

                </div>
            </div>
    <?php else: ?>

<!-- Tableau des clients -->
        <div class="table-container">
            <table class="modern-table">
                    <thead>
                    <tr>
                        <th>
                            <a href="<?php echo getSortUrl('id'); ?>" class="sort-header">

                                ID <?php echo getSortIcon('id'); ?>

                            </a>
                            </th>
                        <th>
                            <a href="<?php echo getSortUrl('nom'); ?>" class="sort-header">

                                Nom <?php echo getSortIcon('nom'); ?>

                            </a>
                            </th>
                        <th>
                            <a href="<?php echo getSortUrl('prenom'); ?>" class="sort-header">

                                Prénom <?php echo getSortIcon('prenom'); ?>

                            </a>
                            </th>
                        <th>
                            <a href="<?php echo getSortUrl('telephone'); ?>" class="sort-header">

                                Téléphone <?php echo getSortIcon('telephone'); ?>

                            </a>
                            </th>
                        <th>
                            <a href="<?php echo getSortUrl('date_creation'); ?>" class="sort-header">

                                Créé le <?php echo getSortIcon('date_creation'); ?>

                            </a>
                            </th>
                        <th>
                            <a href="<?php echo getSortUrl('nombre_reparations'); ?>" class="sort-header">

                                Réparations <?php echo getSortIcon('nombre_reparations'); ?>

                            </a>
                            </th>
                        <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clients as $client): ?>

                    <tr>
                        <td>
                            <span class="client-id">#<?php echo $client['id']; ?></span>

                                </td>
                        <td>
                            <span class="client-name"><?php echo htmlspecialchars($client['nom']); ?></span>

                                </td>
                        <td>
                            <?php echo htmlspecialchars($client['prenom']); ?>

                                </td>
                        <td>
                                        <?php if (!empty($client['telephone'])): ?>

                                <div class="contact-group">
                                    <a href="tel:<?php echo htmlspecialchars($client['telephone']); ?>" class="contact-link">

                                        📞 <?php echo htmlspecialchars($client['telephone']); ?>

                                            </a>
                                            <button type="button" 
                                            class="sms-btn"
                                            onclick="openSmsModal('<?php echo $client['id']; ?>', '<?php echo htmlspecialchars($client['nom'] . ' ' . $client['prenom']); ?>', '<?php echo htmlspecialchars($client['telephone']); ?>')"

                                                    title="Envoyer un SMS">
                                        💬
                                            </button>
                                    </div>
                                    <?php else: ?>

                                <span style="color: #9ca3af; font-style: italic;">Non renseigné</span>
                                    <?php endif; ?>

                                </td>
                        <td>
                            <span class="date-text">
                                <?php echo date('d/m/Y', strtotime($client['date_creation'])); ?>

                            </span>
                        </td>
                        <td>
                                    <?php if ($client['nombre_reparations'] > 0): ?>

                                <span class="badge badge-primary">
                                            <?php echo $client['nombre_reparations']; ?>

                                        </span>
                                    <?php else: ?>

                                <span class="badge badge-warning">0</span>
                                    <?php endif; ?>

                                </td>
                        <td>
                            <div class="action-buttons">
                                <button type="button" class="btn btn-info btn-sm" onclick="showClientHistory('<?php echo $client['id']; ?>', '<?php echo htmlspecialchars($client['nom'] . ' ' . $client['prenom']); ?>')">

                                    📋 Historique
                                        </button>
                                <button type="button" class="btn btn-success btn-sm" onclick="showClientSms('<?php echo $client['id']; ?>', '<?php echo htmlspecialchars($client['nom'] . ' ' . $client['prenom']); ?>', '<?php echo htmlspecialchars($client['telephone']); ?>')">

                                    💬 Voir les SMS
                                            </button>
                                <button type="button" class="btn btn-danger btn-sm" onclick="confirmDelete('<?php echo $client['id']; ?>', '<?php echo htmlspecialchars($client['nom'] . ' ' . $client['prenom']); ?>')">

                                    🗑️ Supprimer
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
        <?php if ($total_pages > 1): ?>

            <div class="pagination">
                <?php if ($current_page > 1): ?>

                    <a href="index.php?page=clients<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>&p=<?php echo ($current_page - 1); ?>">

                        ⬅️ Précédent
                    </a>
                <?php endif; ?>


                <?php

                $start_page = max(1, $current_page - 2);
                $end_page = min($total_pages, $current_page + 2);
                
                for ($i = $start_page; $i <= $end_page; $i++):
                ?>
                    <?php if ($i == $current_page): ?>

                        <span class="current"><?php echo $i; ?></span>

        <?php else: ?>

                        <a href="index.php?page=clients<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>&p=<?php echo $i; ?>">

                            <?php echo $i; ?>

                        </a>
                    <?php endif; ?>

                <?php endfor; ?>


                <?php if ($current_page < $total_pages): ?>

                    <a href="index.php?page=clients<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>&p=<?php echo ($current_page + 1); ?>">

                        Suivant ➡️
                    </a>
                <?php endif; ?>

            </div>
        <?php endif; ?>

        <?php endif; ?>

</div>

<!-- Modal SMS Simple -->
<div id="smsModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
            <h5 class="modal-title">💬 Envoyer un SMS</h5>
            <button type="button" class="modal-close" onclick="closeSmsModal()">×</button>
            </div>
            <div class="modal-body">
            <div class="client-info">
                <strong>Client :</strong> <span id="smsClientName"></span><br>
                <strong>Téléphone :</strong> <span id="smsClientPhone"></span>
                        </div>
            <div class="form-group">
                <label for="smsMessage" class="form-label">Message SMS</label>
                <textarea id="smsMessage" class="form-control" rows="4" placeholder="Tapez votre message ici..." maxlength="160"></textarea>
                <small style="color: #6b7280; font-size: 0.85rem; margin-top: 5px; display: block;">
                    <span id="charCount">0</span>/160 caractères
                                    </small>
                                </div>
            </div>
            <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeSmsModal()">
                ❌ Annuler
                </button>
            <button type="button" class="btn btn-success" onclick="sendSms()">
                📤 Envoyer SMS
                </button>
        </div>
    </div>
</div>

<!-- Modal Historique Client -->
<div id="historiqueModal" class="modern-modal-overlay">
    <div class="modern-modal-container">
        <div class="modern-modal-header">
            <div class="modal-header-content">
                <div class="modal-icon">📋</div>
                <div class="modal-title-section">
                    <h2 class="modal-title">Historique Client</h2>
                    <p class="modal-subtitle" id="historiqueClientName">Chargement...</p>
                </div>
            </div>
            <button type="button" class="modern-modal-close" onclick="closeHistoriqueModal()">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>
        <div class="modern-modal-body">
            <div class="loading-spinner" id="historiqueLoading">
                <div class="spinner"></div>
                <p>Chargement de l'historique...</p>
            </div>
            <div class="historique-content" id="historiqueContent">
                <!-- Le contenu sera chargé via AJAX -->
            </div>
        </div>
        <div class="modern-modal-footer">
            <button type="button" class="modern-btn modern-btn-secondary" onclick="closeHistoriqueModal()">
                Fermer
            </button>
        </div>
    </div>
</div>

<!-- Modal Historique SMS -->
<div id="smsHistoryModal" class="modern-modal-overlay">
    <div class="modern-modal-container">
        <div class="modern-modal-header">
            <div class="modal-header-content">
                <div class="modal-icon">💬</div>
                <div class="modal-title-section">
                    <h2 class="modal-title">Historique des SMS</h2>
                    <p class="modal-subtitle" id="smsHistoryClientName">Chargement...</p>
                </div>
            </div>
            <button type="button" class="modern-modal-close" onclick="closeSmsHistoryModal()">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>
        <div class="modern-modal-body">
            <div class="loading-spinner" id="smsHistoryLoading">
                <div class="spinner"></div>
                <p>Chargement des SMS...</p>
            </div>
            <div class="historique-content" id="smsHistoryContent">
                <!-- Le contenu sera chargé via AJAX -->
            </div>
        </div>
        <div class="modern-modal-footer">
            <button type="button" class="modern-btn modern-btn-secondary" onclick="closeSmsHistoryModal()">
                Fermer
            </button>
        </div>
    </div>
</div>

<script>
// Variables globales
let currentSmsData = {};

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    initializeSearch();
    initializeCharacterCounter();
    debugModalBehavior(); // Ajouter debug pour comprendre le problème
});

// === FONCTIONS DE RECHERCHE ===
function initializeSearch() {
    const searchInput = document.querySelector('.search-input');
    if (!searchInput) return;
    
    let searchTimeout;
    
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            const form = searchInput.closest('form');
            if (form) {
                form.submit();
            }
        }, 800);
        });
        
        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                clearTimeout(searchTimeout);
            const form = this.closest('form');
            if (form) {
                form.submit();
            }
        }
    });
}

// === FONCTIONS SMS ===
function openSmsModal(clientId, clientName, clientPhone) {
    console.log("📱 Ouverture du modal SMS pour:", {
        clientId: clientId,
        clientName: clientName,
        clientPhone: clientPhone
    });
    
    // Vérifications de sécurité
    if (!clientId || !clientName || !clientPhone) {
        console.log("❌ Données client manquantes, annulation de l'ouverture du modal");
        return false;
    }
    
    // Stocker les données du client
    currentSmsData = {
        id: clientId,
        name: clientName,
        phone: clientPhone
    };
    
    // Remplir les informations du client
    const nameElement = document.getElementById('smsClientName');
    const phoneElement = document.getElementById('smsClientPhone');
    const messageElement = document.getElementById('smsMessage');
    
    if (!nameElement || !phoneElement || !messageElement) {
        console.log("❌ Éléments du modal manquants");
        return false;
    }
    
    nameElement.textContent = clientName;
    phoneElement.textContent = clientPhone;
    messageElement.value = '';
    updateCharacterCount();
    
    // Ouvrir le modal
    const modal = document.getElementById('smsModal');
    if (!modal) {
        console.log("❌ Modal SMS introuvable");
        return false;
    }
    
    // S'assurer que le modal était fermé avant
    modal.style.display = '';  // Retirer le display none forcé
    modal.classList.add('show');
    document.body.style.overflow = 'hidden';
    
    console.log("✅ Modal SMS ouvert");
    
    // Focus sur le textarea
    setTimeout(() => {
        if (document.getElementById('smsMessage')) {
            document.getElementById('smsMessage').focus();
        }
    }, 100);
    
    return true;
}

function closeSmsModal() {
    console.log("🔒 Fermeture du modal SMS");
    
    const modal = document.getElementById('smsModal');
    if (!modal) {
        console.log("❌ Modal SMS introuvable lors de la fermeture");
        return;
    }
    
    // S'assurer que toutes les classes d'affichage sont retirées
    modal.classList.remove('show');
    modal.style.display = 'none'; // Forcer le display none en plus
    
    // Restaurer le scroll du body
    document.body.style.overflow = '';
    
    // Nettoyer les données
    currentSmsData = {};
    
    // Vider le formulaire
    const messageField = document.getElementById('smsMessage');
    if (messageField) {
        messageField.value = '';
    }
    
    console.log("✅ Modal SMS fermé");
}

function initializeCharacterCounter() {
    const textarea = document.getElementById('smsMessage');
    if (!textarea) return;
    
    textarea.addEventListener('input', updateCharacterCount);
}

function updateCharacterCount() {
    const textarea = document.getElementById('smsMessage');
    const counter = document.getElementById('charCount');
    if (!textarea || !counter) return;
    
    const count = textarea.value.length;
    counter.textContent = count;
    
    // Changer la couleur si on approche de la limite
    if (count > 140) {
        counter.style.color = '#ef4444';
    } else if (count > 120) {
        counter.style.color = '#f59e0b';
        } else {
        counter.style.color = '#6b7280';
    }
}
    
    function sendSms() {
    const message = document.getElementById('smsMessage').value.trim();
    
    if (!message) {
        alert('Veuillez saisir un message SMS');
                return;
            }
    
    if (!currentSmsData.phone) {
        alert('Numéro de téléphone manquant');
                return;
    }
    
    const sendButton = document.querySelector('.btn-success');
    const originalText = sendButton.textContent;
    sendButton.textContent = '⏳ Envoi...';
    sendButton.disabled = true;
    
    const formData = new FormData();
    formData.append('telephone', currentSmsData.phone);
    formData.append('message', message);
    formData.append('client_id', currentSmsData.id);
    
    fetch('ajax/send_sms.php', {
            method: 'POST',
        body: formData
        })
        .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ SMS envoyé avec succès !');
            closeSmsModal();
            } else {
            alert('❌ Erreur lors de l\'envoi : ' + (data.message || 'Erreur inconnue'));
            }
        })
        .catch(error => {
        console.error('Erreur envoi SMS:', error);
        alert('❌ Erreur lors de l\'envoi du SMS');
        })
        .finally(() => {
        sendButton.textContent = originalText;
        sendButton.disabled = false;
    });
}

// === AUTRES FONCTIONS ===
function showClientHistory(clientId, clientName) {
    console.log('📋 Ouverture de l\'historique pour:', clientId, clientName);
    
    // Ouvrir le modal
    openHistoriqueModal(clientId, clientName);
}

function confirmDelete(clientId, clientName) {
    if (confirm(`Êtes-vous sûr de vouloir supprimer le client "${clientName}" ?\n\nCette action est irréversible.`)) {
        deleteClient(clientId);
    }
}

function deleteClient(clientId) {
    const formData = new FormData();
    formData.append('client_id', clientId);
    
    fetch('ajax/delete_client.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ Client supprimé avec succès');
            location.reload();
        } else {
            alert('❌ Erreur lors de la suppression : ' + (data.message || 'Erreur inconnue'));
        }
    })
    .catch(error => {
        console.error('Erreur suppression client:', error);
        alert('❌ Erreur lors de la suppression du client');
    });
}

// Fermer le modal en cliquant en dehors
document.getElementById('smsModal').addEventListener('click', function(e) {
            if (e.target === this) {
        closeSmsModal();
            }
    });

// Fermer le modal avec Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
        closeSmsModal();
        }
});

// === FONCTION DE DEBUG ===
function debugModalBehavior() {
    console.log("🐛 DEBUG MODAL SMS - Initialisation");
    
    const smsModal = document.getElementById('smsModal');
    if (!smsModal) {
        console.log("❌ Modal SMS introuvable");
        return;
    }
    
    // Vérifier l'état initial du modal
    const hasShowClass = smsModal.classList.contains('show');
    const computedDisplay = window.getComputedStyle(smsModal).display;
    const isVisible = computedDisplay !== 'none';
    
    console.log("📊 État initial du modal:");
    console.log("- Classe 'show':", hasShowClass);
    console.log("- Display CSS:", computedDisplay);
    console.log("- Visible:", isVisible);
    console.log("- Classes actuelles:", smsModal.className);
    
    // S'assurer que le modal est fermé au chargement
    if (hasShowClass || isVisible) {
        console.log("⚠️ PROBLÈME DÉTECTÉ - Modal ouvert automatiquement!");
        console.log("🔧 Fermeture forcée du modal...");
        
        // Fermeture forcée avec tous les styles
        smsModal.classList.remove('show');
        smsModal.style.display = 'none';
        smsModal.style.visibility = 'hidden';
        smsModal.style.opacity = '0';
        document.body.style.overflow = '';
        
        console.log("✅ Modal fermé de force");
    }
    
    // Forcer la fermeture immédiatement aussi
    console.log("🔧 Application forcée des styles de fermeture...");
    smsModal.style.display = 'none';
    smsModal.style.visibility = 'hidden';
    smsModal.style.opacity = '0';
    smsModal.classList.remove('show');
    
    // Surveiller les changements de classe sur le modal
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                const hasShow = smsModal.classList.contains('show');
                console.log("🔄 Changement de classe détecté sur modal SMS:");
                console.log("- Nouvelles classes:", smsModal.className);
                console.log("- Modal affiché:", hasShow);
                
                // Log de la pile d'appel pour identifier qui ouvre le modal
                if (hasShow) {
                    console.log("📞 Pile d'appel lors de l'ouverture:");
                    console.trace();
                }
            }
        });
    });
    
    observer.observe(smsModal, {
        attributes: true,
        attributeFilter: ['class']
    });
    
    // Vérifier les paramètres URL qui pourraient déclencher l'ouverture
    const urlParams = new URLSearchParams(window.location.search);
    console.log("🔗 Paramètres URL:", Array.from(urlParams.entries()));
    
    // Vérifier s'il y a des scripts externes qui pourraient interférer
    setTimeout(() => {
        const finalState = smsModal.classList.contains('show');
        console.log("⏰ État du modal après 1 seconde:", finalState);
        
        if (finalState) {
            console.log("🚨 ALERTE: Le modal s'est ouvert malgré nos vérifications!");
            console.log("🔧 Fermeture forcée...");
            closeSmsModal();
        }
    }, 1000);
    
    console.log("✅ Debug modal SMS initialisé");
}

// Protection contre l'ouverture automatique du modal - à installer après définition des fonctions
function installSmsModalProtection() {
    console.log("🛡️ Installation de la protection contre l'ouverture automatique...");
    
    // Vérifier si la fonction existe
    if (typeof openSmsModal !== 'function') {
        console.log("⚠️ Fonction openSmsModal non trouvée, réessai dans 100ms...");
        setTimeout(installSmsModalProtection, 100);
        return;
    }
    
    // Stocker la fonction originale
    const originalOpenSmsModal = openSmsModal;
    
    // Remplacer par une version protégée
    window.openSmsModal = function(clientId, clientName, clientPhone) {
        console.log("🛡️ Tentative d'ouverture du modal SMS interceptée");
        
        // Vérifier si l'ouverture est déclenchée par une interaction utilisateur réelle
        const isUserInteraction = event && (event.isTrusted === true || event.type === 'click');
        
        if (!isUserInteraction) {
            console.log("🚫 Ouverture automatique bloquée - Aucune interaction utilisateur détectée");
            return false;
        }
        
        console.log("✅ Ouverture autorisée - Interaction utilisateur détectée");
        return originalOpenSmsModal.call(this, clientId, clientName, clientPhone);
    };
    
    console.log("🛡️ Protection contre l'ouverture automatique installée");
}

// Installer la protection après un délai
setTimeout(installSmsModalProtection, 500);

// === FONCTIONS MODAL HISTORIQUE ===
function openHistoriqueModal(clientId, clientName) {
    console.log('📋 Ouverture du modal historique pour:', clientId, clientName);
    
    const modal = document.getElementById('historiqueModal');
    const clientNameElement = document.getElementById('historiqueClientName');
    const loadingElement = document.getElementById('historiqueLoading');
    const contentElement = document.getElementById('historiqueContent');
    
    if (!modal) {
        console.error('Modal historique introuvable');
        return;
    }
    
    // Réinitialiser le modal
    clientNameElement.textContent = clientName;
    loadingElement.style.display = 'flex';
    contentElement.style.display = 'none';
    contentElement.classList.remove('loaded');
    
    // Ouvrir le modal
    modal.classList.add('show');
    document.body.style.overflow = 'hidden';
    
    // Charger l'historique via AJAX
    loadClientHistory(clientId);
}

function closeHistoriqueModal() {
    console.log('📋 Fermeture du modal historique');
    
    const modal = document.getElementById('historiqueModal');
    if (!modal) return;
    
    modal.classList.remove('show');
    document.body.style.overflow = '';
    
    // Nettoyer le contenu après fermeture
    setTimeout(() => {
        const contentElement = document.getElementById('historiqueContent');
        if (contentElement) {
            contentElement.innerHTML = '';
            contentElement.classList.remove('loaded');
        }
    }, 300);
}

function loadClientHistory(clientId) {
    console.log('📋 Chargement de l\'historique pour le client:', clientId);
    
    const loadingElement = document.getElementById('historiqueLoading');
    const contentElement = document.getElementById('historiqueContent');
    
    // Simuler un chargement pour l'instant
    fetch(`ajax/get_client_history.php?client_id=${clientId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Erreur de réseau');
            }
            return response.text();
        })
        .then(data => {
            console.log('✅ Historique chargé avec succès');
            
            // Masquer le spinner et afficher le contenu
            loadingElement.style.display = 'none';
            contentElement.innerHTML = data;
            contentElement.style.display = 'block';
            contentElement.classList.add('loaded');
        })
        .catch(error => {
            console.error('❌ Erreur lors du chargement de l\'historique:', error);
            
            // Afficher un message d'erreur
            loadingElement.style.display = 'none';
            contentElement.innerHTML = `
                <div style="text-align: center; padding: 40px; color: #ef4444;">
                    <div style="font-size: 3rem; margin-bottom: 20px;">⚠️</div>
                    <h3>Erreur de chargement</h3>
                    <p>Impossible de charger l'historique du client.</p>
                    <button onclick="loadClientHistory(${clientId})" style="background: #667eea; color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; margin-top: 15px;">
                        Réessayer
                    </button>
                </div>
            `;
            contentElement.style.display = 'block';
            contentElement.classList.add('loaded');
        });
}

// Fermer le modal en cliquant en dehors
document.addEventListener('click', function(e) {
    const historiqueModal = document.getElementById('historiqueModal');
    const smsHistoryModal = document.getElementById('smsHistoryModal');
    
    if (e.target === historiqueModal) {
        closeHistoriqueModal();
    }
    
    if (e.target === smsHistoryModal) {
        closeSmsHistoryModal();
    }
});

// Fermer le modal avec Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const historiqueModal = document.getElementById('historiqueModal');
        const smsHistoryModal = document.getElementById('smsHistoryModal');
        
        if (historiqueModal && historiqueModal.classList.contains('show')) {
            closeHistoriqueModal();
        }
        
        if (smsHistoryModal && smsHistoryModal.classList.contains('show')) {
            closeSmsHistoryModal();
        }
    }
});

// === FONCTIONS MODAL HISTORIQUE SMS ===
function showClientSms(clientId, clientName, clientPhone) {
    console.log('💬 Ouverture du modal SMS pour:', clientId, clientName, clientPhone);
    
    const modal = document.getElementById('smsHistoryModal');
    const clientNameElement = document.getElementById('smsHistoryClientName');
    const loadingElement = document.getElementById('smsHistoryLoading');
    const contentElement = document.getElementById('smsHistoryContent');
    
    if (!modal) {
        console.error('Modal historique SMS introuvable');
        return;
    }
    
    // Réinitialiser le modal
    clientNameElement.textContent = `${clientName} - ${clientPhone}`;
    loadingElement.style.display = 'flex';
    contentElement.style.display = 'none';
    contentElement.classList.remove('loaded');
    
    // Ouvrir le modal
    modal.classList.add('show');
    document.body.style.overflow = 'hidden';
    
    // Charger l'historique SMS via AJAX
    loadClientSmsHistory(clientId);
}

function closeSmsHistoryModal() {
    console.log('💬 Fermeture du modal historique SMS');
    
    const modal = document.getElementById('smsHistoryModal');
    if (!modal) return;
    
    modal.classList.remove('show');
    document.body.style.overflow = '';
    
    // Nettoyer le contenu après fermeture
    setTimeout(() => {
        const contentElement = document.getElementById('smsHistoryContent');
        if (contentElement) {
            contentElement.innerHTML = '';
            contentElement.classList.remove('loaded');
        }
    }, 300);
}

function loadClientSmsHistory(clientId) {
    console.log('💬 Chargement de l\'historique SMS pour le client:', clientId);
    
    const loadingElement = document.getElementById('smsHistoryLoading');
    const contentElement = document.getElementById('smsHistoryContent');
    
    fetch(`ajax/get_client_sms.php?client_id=${clientId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Erreur de réseau');
            }
            return response.json();
        })
        .then(data => {
            console.log('✅ Historique SMS chargé avec succès:', data);
            
            if (!data.success) {
                throw new Error(data.message || 'Erreur inconnue');
            }
            
            // Masquer le spinner et afficher le contenu
            loadingElement.style.display = 'none';
            contentElement.innerHTML = generateSmsHistoryHTML(data);
            contentElement.style.display = 'block';
            contentElement.classList.add('loaded');
        })
        .catch(error => {
            console.error('❌ Erreur lors du chargement de l\'historique SMS:', error);
            
            // Afficher un message d'erreur
            loadingElement.style.display = 'none';
            
            // Détecter le mode sombre pour l'erreur
            const isDarkMode = document.body.classList.contains('dark-mode');
            const errorColor = isDarkMode ? '#f87171' : '#ef4444';
            const errorSecondaryColor = isDarkMode ? '#94a3b8' : '#6b7280';
            const buttonBg = isDarkMode ? '#4f46e5' : '#667eea';
            const buttonHoverBg = isDarkMode ? '#4338ca' : '#5a67d8';
            
            contentElement.innerHTML = `
                <div style="text-align: center; padding: 40px; color: ${errorColor};">
                    <div style="font-size: 3rem; margin-bottom: 20px;">⚠️</div>
                    <h3 style="color: ${errorColor};">Erreur de chargement</h3>
                    <p style="color: ${errorColor};">Impossible de charger l'historique des SMS.</p>
                    <p style="color: ${errorSecondaryColor}; font-size: 0.9rem;">${error.message}</p>
                    <button onclick="loadClientSmsHistory(${clientId})" 
                            style="background: ${buttonBg}; color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer; margin-top: 15px; font-weight: 500; transition: all 0.2s ease;"
                            onmouseover="this.style.background='${buttonHoverBg}'; this.style.transform='translateY(-1px)'"
                            onmouseout="this.style.background='${buttonBg}'; this.style.transform='translateY(0)'">
                        🔄 Réessayer
                    </button>
                </div>
            `;
            contentElement.style.display = 'block';
            contentElement.classList.add('loaded');
        });
}

function generateSmsHistoryHTML(data) {
    const { client, sms, total } = data;
    
    // Détecter le mode sombre
    const isDarkMode = document.body.classList.contains('dark-mode');
    
    if (!sms || sms.length === 0) {
        const emptyStateColor = isDarkMode ? '#94a3b8' : '#6b7280';
        const emptyStateSecondaryColor = isDarkMode ? '#64748b' : '#9ca3af';
        
        return `
            <div style="text-align: center; padding: 40px; color: ${emptyStateColor};">
                <div style="font-size: 3rem; margin-bottom: 20px;">📱</div>
                <h3 style="color: ${emptyStateColor};">Aucun SMS trouvé</h3>
                <p style="color: ${emptyStateColor};">Aucun SMS n'a été envoyé à ce client.</p>
                <p style="font-size: 0.9rem; color: ${emptyStateSecondaryColor};">
                    Numéro recherché : ${client.telephone || 'Non renseigné'}
                </p>
            </div>
        `;
    }
    
    // Couleurs adaptatives selon le mode
    const summaryBg = isDarkMode ? '#334155' : '#f8fafc';
    const summaryTitleColor = isDarkMode ? '#e2e8f0' : '#374151';
    const summaryTextColor = isDarkMode ? '#94a3b8' : '#6b7280';
    const tableBg = isDarkMode ? '#1e293b' : 'white';
    const tableHeaderBg = isDarkMode ? '#334155' : '#f8fafc';
    const tableHeaderColor = isDarkMode ? '#e2e8f0' : '#374151';
    const tableHeaderBorder = isDarkMode ? '#475569' : '#e5e7eb';
    const tableBorder = isDarkMode ? '#334155' : 'rgba(0, 0, 0, 0.1)';
    
    let html = `
        <div style="padding: 20px;">
            <div style="background: ${summaryBg}; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #667eea;">
                <h4 style="margin: 0 0 5px 0; color: ${summaryTitleColor};">📊 Résumé</h4>
                <p style="margin: 0; color: ${summaryTextColor};">
                    <strong>${total}</strong> SMS envoyé${total > 1 ? 's' : ''} au numéro <strong>${client.telephone}</strong>
                </p>
            </div>
            
            <div style="background: ${tableBg}; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px ${isDarkMode ? 'rgba(0, 0, 0, 0.3)' : 'rgba(0, 0, 0, 0.1)'}; border: ${isDarkMode ? '1px solid #334155' : 'none'};">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: ${tableHeaderBg};">
                            <th style="padding: 12px; text-align: left; font-weight: 600; color: ${tableHeaderColor}; border-bottom: 2px solid ${tableHeaderBorder};">Date</th>
                            <th style="padding: 12px; text-align: left; font-weight: 600; color: ${tableHeaderColor}; border-bottom: 2px solid ${tableHeaderBorder};">Message</th>
                            <th style="padding: 12px; text-align: left; font-weight: 600; color: ${tableHeaderColor}; border-bottom: 2px solid ${tableHeaderBorder};">Statut</th>
                            <th style="padding: 12px; text-align: left; font-weight: 600; color: ${tableHeaderColor}; border-bottom: 2px solid ${tableHeaderBorder};">Réparation</th>
                        </tr>
                    </thead>
                    <tbody>
    `;
    
    sms.forEach((message, index) => {
        // Couleurs adaptatives pour les lignes
        const rowBg = isDarkMode 
            ? (index % 2 === 0 ? '#1e293b' : '#0f172a')
            : (index % 2 === 0 ? '#fafafa' : 'white');
        const rowBorder = isDarkMode ? '#334155' : '#f3f4f6';
        const textColor = isDarkMode ? '#e2e8f0' : '#374151';
        const secondaryTextColor = isDarkMode ? '#94a3b8' : '#9ca3af';
        const linkColor = isDarkMode ? '#60a5fa' : '#667eea';
        
        // Couleurs des statuts optimisées pour le mode sombre
        let statusColor = {
            'success': isDarkMode ? '#065f46' : '#059669',
            'danger': isDarkMode ? '#991b1b' : '#dc2626',
            'warning': isDarkMode ? '#92400e' : '#d97706',
            'info': isDarkMode ? '#0369a1' : '#0284c7'
        }[message.status_class] || (isDarkMode ? '#475569' : '#6b7280');
        
        let statusTextColor = isDarkMode ? 'white' : 'white';
        if (isDarkMode) {
            statusTextColor = {
                'success': '#d1fae5',
                'danger': '#fecaca',
                'warning': '#fed7aa',
                'info': '#bae6fd'
            }[message.status_class] || '#e2e8f0';
        }
        
        const repairInfo = message.reparation_info 
            ? `<a href="#" onclick="openRepairPage(${message.reparation_id})" style="color: ${linkColor}; text-decoration: none; font-weight: 500;">#${message.reparation_id} - ${message.reparation_info.appareil}</a>`
            : `<span style="color: ${secondaryTextColor};">-</span>`;
        
        html += `
            <tr style="border-bottom: 1px solid ${rowBorder}; background: ${rowBg};">
                <td style="padding: 12px; vertical-align: top;">
                    <div style="font-weight: 500; color: ${textColor};" class="date-formatted">${message.date_formatted}</div>
                    <div style="font-size: 0.8rem; color: ${secondaryTextColor};" class="source-table">${message.source_table}</div>
                </td>
                <td style="padding: 12px; vertical-align: top; max-width: 300px;">
                    <div style="color: ${textColor}; line-height: 1.4; word-wrap: break-word;">
                        ${message.message.replace(/\n/g, '<br>')}
                    </div>
                </td>
                <td style="padding: 12px; vertical-align: top;">
                    <span style="background: ${statusColor}; color: ${statusTextColor}; padding: 4px 8px; border-radius: 12px; font-size: 0.8rem; font-weight: 500;">
                        ${message.status_text}
                    </span>
                </td>
                <td style="padding: 12px; vertical-align: top;">
                    ${repairInfo}
                </td>
            </tr>
        `;
    });
    
    html += `
                    </tbody>
                </table>
            </div>
        </div>
    `;
    
    return html;
}

// === FONCTION GLOBALE POUR OUVRIR LES MODALS DE RÉPARATION ===
window.openRepairModal = function(repairId) {
    console.log('🔧 Ouverture du modal pour la réparation:', repairId);
    
    // Fermer le modal historique d'abord
    if (typeof closeHistoriqueModal === 'function') {
        closeHistoriqueModal();
    }
    
    // Fermer le modal SMS historique aussi
    if (typeof closeSmsHistoryModal === 'function') {
        closeSmsHistoryModal();
    }
    
    // Rediriger vers la page de réparations avec le modal ouvert
    const url = `index.php?page=reparations&open_modal=${repairId}`;
    console.log('🔗 Redirection vers:', url);
    
    // Utiliser window.location pour la redirection
    window.location.href = url;
};

// === FONCTION POUR OUVRIR LA PAGE REPARATIONS AVEC MODAL ===
window.openRepairPage = function(repairId) {
    console.log('🔧 Ouverture de la page réparations pour la réparation:', repairId);
    
    // Fermer le modal historique d'abord
    if (typeof closeHistoriqueModal === 'function') {
        closeHistoriqueModal();
    }
    
    // Fermer le modal SMS historique aussi
    if (typeof closeSmsHistoryModal === 'function') {
        closeSmsHistoryModal();
    }
    
    // Rediriger vers index.php?page=reparations avec le modal ouvert
    const url = `index.php?page=reparations&open_modal=${repairId}`;
    console.log('🔗 Redirection vers:', url);
    
    // Utiliser window.location pour la redirection
    window.location.href = url;
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

/* Masquer le loader quand la page est chargée */
.loader.fade-out {
  opacity: 0;
  transition: opacity 0.5s ease-out;
}

.loader.hidden {
  display: none;
}

/* Afficher le contenu principal quand chargé */
#mainContent.fade-in {
  opacity: 1;
  transition: opacity 0.5s ease-in;
}

/* Gestion des deux types de loaders */
.dark-loader {
  display: flex;
}

.light-loader {
  display: none;
  background: #ffffff !important;
}

/* En mode clair, inverser l'affichage */
body:not(.dark-mode) #pageLoader {
  background: #ffffff !important;
}

body:not(.dark-mode) .dark-loader {
  display: none;
}

body:not(.dark-mode) .light-loader {
  display: flex;
}

/* Loader Mode Clair - Cercle avec couleurs sombres */
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

/* Texte du loader mode clair */
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

/* Appliquer le fond du loader à la page - MODE JOUR ET NUIT */
body,
body.dark-mode,
body.light-mode,
html {
  background: linear-gradient(0deg, #0f1419, #0a0f1a, #000) !important;
  background-attachment: fixed !important;
  min-height: 100vh !important;
}

.clients-container,
.clients-container * {
  background: transparent !important;
}

/* Forcer le fond pour tous les éléments principaux */
.main-content,
.container-fluid,
.content-wrapper {
  background: transparent !important;
}

/* S'assurer que les cartes et éléments restent visibles */
.table-container,
.modern-table,
.stat-card,
.modal-content,
.modern-modal-container {
  background: rgba(255, 255, 255, 0.95) !important;
  backdrop-filter: blur(10px) !important;
}

.dark-mode .table-container,
.dark-mode .modern-table,
.dark-mode .stat-card,
.dark-mode .modal-content,
.dark-mode .modern-modal-container {
  background: rgba(30, 41, 59, 0.95) !important;
  backdrop-filter: blur(10px) !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const loader = document.getElementById('pageLoader');
    const mainContent = document.getElementById('mainContent');
    
    // Attendre 0,3 seconde puis masquer le loader et afficher le contenu
    setTimeout(function() {
        // Commencer l'animation de disparition du loader
        loader.classList.add('fade-out');
        
        // Après l'animation de disparition, masquer complètement le loader et afficher le contenu
        setTimeout(function() {
            loader.classList.add('hidden');
            mainContent.style.display = 'block';
            mainContent.classList.add('fade-in');
        }, 500); // Durée de l'animation de disparition
        
    }, 300); // 0,3 seconde comme demandé
});
</script>
