<?php
// Auth
if (!isset($_SESSION['user_id'])) {
	redirect('index');
}

// Data
try {
	$shop_pdo = getShopDBConnection();
	$colCheck = $shop_pdo->query("SHOW COLUMNS FROM produits LIKE 'suivre_stock'");
	$gb_has_suivre_stock = $colCheck && $colCheck->rowCount() > 0;
	$sql = "SELECT p.*" . ($gb_has_suivre_stock ? ", p.suivre_stock" : "") . ", f.nom as fournisseur_nom FROM produits p LEFT JOIN fournisseurs f ON p.fournisseur_id = f.id ORDER BY p.nom ASC";
	$stmt = $shop_pdo->prepare($sql);
	$stmt->execute();
	$gb_products = $stmt->fetchAll();
} catch (Throwable $e) {
	set_message("Erreur chargement inventaire: " . $e->getMessage(), 'danger');
	$gb_has_suivre_stock = false;
	$gb_products = [];
}

$gb_total = count($gb_products);
$gb_alert = array_filter($gb_products, function($p){ return (int)$p['quantite'] > 0 && (int)$p['quantite'] <= (int)$p['seuil_alerte'];});
$gb_out = array_filter($gb_products, function($p){ return (int)$p['quantite'] === 0; });
$gb_stock = $gb_total - count($gb_out);
$gb_tracked = $gb_has_suivre_stock ? array_filter($gb_products, function($p){ return isset($p['suivre_stock']) && (int)$p['suivre_stock'] === 1; }) : [];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<title>Inventaire — Élégant</title>
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
	<style>
		:root {
			--gb-primary: #4f46e5;
			--gb-primary-700: #3730a3;
			--gb-success: #10b981;
			--gb-warning: #f59e0b;
			--gb-danger: #ef4444;
			--gb-info: #06b6d4;
			--gb-bg: #0f172a;
			--gb-surface: #0b1222;
			--gb-card: #0f172a;
			--gb-muted: #94a3b8;
			--gb-border: #22314f;
			--gb-white: #ffffff;
			--gb-shadow: 0 10px 30px rgba(0,0,0,0.35);
			--gb-radius: 14px;
			--gb-radius-sm: 10px;
		}
		* { box-sizing: border-box; }
		body {
			margin: 0; font-family: Inter, system-ui, -apple-system, Segoe UI, Roboto, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
			background: radial-gradient(1200px 800px at 20% -10%, rgba(79,70,229,0.15), transparent 55%),
				radial-gradient(900px 700px at 100% 0%, rgba(6,182,212,0.18), transparent 55%),
				linear-gradient(180deg, #0b1222, #0a0f1a 60%, #080d17);
			color: var(--gb-white);
		}
		.gb-container { max-width: 1400px; margin: 0 auto; padding: 28px; }
		.gb-header { display:flex; gap:18px; align-items:center; justify-content:space-between; margin: 8px 0 22px; }
		.gb-title { display:flex; gap:12px; align-items:center; font-weight:800; letter-spacing:.3px; font-size: 28px; }
		.gb-title i { color: var(--gb-primary); }
		.gb-actions { display:flex; gap:10px; flex-wrap:wrap; }
		.gb-btn { display:inline-flex; align-items:center; gap:8px; padding:10px 14px; border:1px solid var(--gb-border); color:var(--gb-white); background:linear-gradient(180deg, #121a2c, #0d1628); border-radius:12px; cursor:pointer; transition:.2s ease; box-shadow: inset 0 1px rgba(255,255,255,0.05); }
		.gb-btn:hover { transform: translateY(-1px); border-color:#35508a; }
		.gb-btn--primary { background: linear-gradient(180deg, #4f46e5, #4338ca); border-color: #4338ca; }
		.gb-btn--primary:hover { background: linear-gradient(180deg, #5b52ff, #4b3fd9); }
		.gb-btn--success { background: linear-gradient(180deg, #10b981, #059669); border-color:#059669; }
		.gb-btn--info { background: linear-gradient(180deg, #06b6d4, #0891b2); border-color:#0891b2; }

		.gb-grid-stats { display:grid; grid-template-columns: repeat(5, minmax(0,1fr)); gap:14px; margin: 18px 0 22px; }
		@media (max-width: 1100px){ .gb-grid-stats{ grid-template-columns: repeat(3, 1fr);} }
		@media (max-width: 700px){ .gb-grid-stats{ grid-template-columns: repeat(1, 1fr);} }
		.gb-card { background: linear-gradient(180deg, #0f172a, #0b1222); border:1px solid var(--gb-border); border-radius: var(--gb-radius); padding:18px; box-shadow: var(--gb-shadow); }
		/* Info rows inside adjust modal */
		.gb-info-line { color:#9eb0d3; font-size:13px; }
		.gb-info-line code { color: inherit; }
		.gb-info-current { color:#c9d5ef; }
		.gb-stat { display:flex; align-items:center; gap:14px; }
		.gb-stat-icon { width:46px; height:46px; display:flex; align-items:center; justify-content:center; border-radius: 12px; background: #101b33; border:1px solid #1b2947; color:#8fb4ff; }
		.gb-stat-label { color: var(--gb-muted); font-size:13px; letter-spacing:.2px; }
		.gb-stat-value { font-size:26px; font-weight:800; margin-top:2px; letter-spacing:.4px; }

		.gb-controls { display:flex; gap:10px; align-items:center; flex-wrap:wrap; margin: 8px 0 18px; }
		.gb-search { position:relative; min-width:300px; flex:1; }
		.gb-search input { width:100%; background: #0c1426; border:1px solid var(--gb-border); color: var(--gb-white); padding:12px 14px 12px 40px; border-radius: 12px; outline:none; transition:.15s ease; }
		.gb-search i { position:absolute; left:12px; top:50%; transform: translateY(-50%); color: #6b7da4; }
		.gb-search input:focus { border-color:#365aa5; box-shadow: 0 0 0 3px rgba(54,90,165,.2); }
		.gb-select { background:#0c1426; border:1px solid var(--gb-border); color:#d9e2f1; padding:12px; border-radius: 12px; }

		.gb-table-wrap { overflow: auto; border-radius: 14px; border:1px solid var(--gb-border); box-shadow: var(--gb-shadow); }
		.gb-table { width:100%; border-collapse: collapse; background: #0c1426; }
		.gb-table th { text-align:left; font-weight:700; color:#b8c5e3; padding:14px; background:#0e1730; border-bottom:1px solid #1a2b4f; position:sticky; top:0; }
		.gb-table td { padding:14px; border-bottom:1px solid #152443; color:#e5ecfb; }
		.gb-table tr:hover td { background:#0d1831; }
		.gb-badge { display:inline-flex; align-items:center; gap:6px; padding:4px 10px; border-radius: 9999px; font-size:12px; border:1px solid transparent; }
		.gb-badge--ok { background: rgba(16,185,129,.15); color:#22c55e; border-color: rgba(16,185,129,.35); }
		.gb-badge--warn { background: rgba(245,158,11,.15); color:#f59e0b; border-color: rgba(245,158,11,.35); }
		.gb-badge--danger { background: rgba(239,68,68,.15); color:#ef4444; border-color: rgba(239,68,68,.35); }
		.gb-actions-cell { display:flex; gap:8px; }

		/* Modals isolés */
		.gb-modal { display:none; position:fixed; inset:0; z-index: 99999; align-items:center; justify-content:center; }
		.gb-modal.gb-show { display:flex; }
		.gb-modal__backdrop { position:absolute; inset:0; background: rgba(3,7,18,0.65); backdrop-filter: blur(5px); }
		.gb-modal__dialog { position:relative; width:min(96vw, 1000px); max-height:92vh; overflow:auto; border-radius: var(--gb-radius); background: linear-gradient(180deg, #0f172a, #0b1222); border:1px solid var(--gb-border); box-shadow: var(--gb-shadow); animation: gbPop .2s ease; }
		.gb-modal__header { display:flex; align-items:center; justify-content:space-between; padding:16px 18px; border-bottom:1px solid var(--gb-border); }
		.gb-modal__title { display:flex; align-items:center; gap:10px; font-weight:800; letter-spacing:.3px; }
		
		/* Adjust Modal Styles */
		.gb-adjust-modal { width:min(90vw, 400px); max-height:none; }
		.gb-adjust-header { display:flex; align-items:center; justify-content:space-between; padding:20px 24px; background: linear-gradient(135deg, #4f46e5, #7c3aed); color:white; }
		.gb-adjust-header h3 { margin:0; font-size:18px; font-weight:700; }
		.gb-adjust-close { background:none; border:none; color:white; font-size:18px; cursor:pointer; padding:4px; border-radius:4px; transition: background 0.2s; }
		.gb-adjust-close:hover { background:rgba(255,255,255,0.1); }
		.gb-adjust-body { padding:32px 24px; text-align:center; }
		.gb-adjust-quantity { margin-bottom:24px; }
		.gb-quantity-label { font-size:14px; color:#94a3b8; margin-bottom:8px; font-weight:500; }
		.gb-quantity-value { font-size:48px; font-weight:800; color:#e2e8f0; cursor:pointer; user-select:none; transition: color 0.2s; }
		.gb-quantity-value:hover { color:#60a5fa; }
		.gb-quantity-input { 
			width:100%; 
			font-size:32px; 
			font-weight:700; 
			text-align:center; 
			padding:16px; 
			border:2px solid #334155; 
			border-radius:12px; 
			background:#1e293b; 
			color:#e2e8f0; 
			margin-bottom:24px;
			transition: all 0.2s;
		}
		.gb-quantity-input:focus { 
			outline:none; 
			border-color:#4f46e5; 
			box-shadow:0 0 0 4px rgba(79, 70, 229, 0.2); 
		}
		.gb-update-btn { 
			width:100%; 
			padding:16px; 
			font-size:16px; 
			font-weight:600; 
			background:linear-gradient(135deg, #10b981, #059669); 
			color:white; 
			border:none; 
			border-radius:12px; 
			cursor:pointer; 
			display:flex; 
			align-items:center; 
			justify-content:center; 
			gap:8px;
			transition: all 0.2s;
		}
		.gb-update-btn:hover { 
			transform:translateY(-1px); 
			box-shadow:0 8px 25px rgba(16, 185, 129, 0.3); 
		}
		.gb-modal__close { background:transparent; border:1px solid var(--gb-border); color:#c9d5ef; width:36px; height:36px; border-radius:10px; cursor:pointer; }
		.gb-modal__body { padding:18px; }
		@keyframes gbPop { from { transform: scale(.98); opacity:.7 } to { transform: scale(1); opacity:1 } }
		@keyframes gbToastSlide { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
		
		/* Form styles */
		.gb-label { display: block; font-weight: 600; margin-bottom: 6px; color: var(--gb-text); }
		.gb-input { width: 100%; padding: 10px 12px; border: 1px solid var(--gb-border); border-radius: 8px; background: var(--gb-light); color: var(--gb-text); font-size: 14px; transition: border-color 0.2s; }
		.gb-input:focus { outline: none; border-color: #4f46e5; box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1); }
		.gb-checkbox-container { display: flex; align-items: center; gap: 10px; cursor: pointer; }
		.gb-checkbox-container input[type="checkbox"] { display: none; }
		.gb-checkbox-mark { width: 20px; height: 20px; border: 2px solid var(--gb-border); border-radius: 4px; background: var(--gb-light); position: relative; transition: all 0.2s; }
		.gb-checkbox-container input[type="checkbox"]:checked + .gb-checkbox-mark { background: #4f46e5; border-color: #4f46e5; }
		.gb-checkbox-container input[type="checkbox"]:checked + .gb-checkbox-mark::after { content: '✓'; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); color: white; font-weight: bold; font-size: 12px; }

		/* Stock cards */
		.gb-stock-grid { display:grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap:14px; }
		.gb-stock-card { border:1px solid var(--gb-border); border-radius: 14px; background:#0c1426; padding:14px; cursor:pointer; transition:.15s ease; }
		.gb-stock-card:hover { transform: translateY(-2px); border-color:#35508a; }
		.gb-stock-title { font-weight:700; }
		.gb-stock-ref { font-size:12px; color:#aab7d6; background:#0e1730; border:1px solid #1c2b4f; padding:3px 6px; border-radius:8px; }
		.gb-empty { text-align:center; color:#a3b1cf; padding:26px; }

		/* Light mode improvements */
		@media (prefers-color-scheme: light) {
			body { 
				background: linear-gradient(180deg, #ffffff, #f9fafb 60%, #f3f4f6);
				color: #111827;
			}

			.gb-title { color: #0f172a; }
			.gb-actions .gb-btn { background: linear-gradient(180deg, #ffffff, #f8fafc); border-color: #e5e7eb; color: #1f2937; box-shadow: 0 1px 2px rgba(0,0,0,.04); }
			.gb-actions .gb-btn:hover { border-color: #cbd5e1; }

			.gb-grid-stats .gb-card { background: #ffffff; border-color: #e5e7eb; box-shadow: 0 4px 12px rgba(17,24,39,.06); }
			/* Make generic cards light too (including the info card in modal) */
			.gb-card { background:#ffffff; border-color:#e5e7eb; color:#0f172a; }
			.gb-stat-icon { background: #f1f5f9; border-color: #e2e8f0; color: #475569; }
			.gb-stat-label { color: #64748b; }
			.gb-stat-value { color: #0f172a; }

			.gb-controls .gb-select { background: #ffffff; border-color: #e5e7eb; color: #111827; }
			.gb-search input { background: #ffffff; border-color: #e5e7eb; color: #111827; }
			.gb-search i { color: #94a3b8; }
			.gb-search input:focus { border-color: #60a5fa; box-shadow: 0 0 0 3px rgba(96,165,250,.25); }

			.gb-table-wrap { border-color: #e5e7eb; box-shadow: 0 6px 16px rgba(17,24,39,.05); }
			.gb-table { background: #ffffff; }
			.gb-table th { background: #f9fafb; color: #0f172a; border-bottom-color: #e5e7eb; }
			.gb-table td { color: #111827; border-bottom-color: #e5e7eb; }
			.gb-table tr:hover td { background: #f8fafc; }

			.gb-badge--ok { background: rgba(16,185,129,.12); color:#15803d; border-color: rgba(16,185,129,.28); }
			.gb-badge--warn { background: rgba(245,158,11,.12); color:#b45309; border-color: rgba(245,158,11,.28); }
			.gb-badge--danger { background: rgba(239,68,68,.12); color:#b91c1c; border-color: rgba(239,68,68,.28); }

			/* Modals */
			.gb-modal__dialog { background: #ffffff; border-color: #e5e7eb; box-shadow: 0 12px 32px rgba(17,24,39,.12); }
			.gb-modal__header { border-bottom-color: #e5e7eb; }
			.gb-modal__title { color: #0f172a; }
			.gb-modal__close { border-color: #e5e7eb; color: #334155; }
			.gb-modal__body { color: #111827; }
			.gb-info-line { color:#475569; }
			.gb-info-current { color:#0f172a; }
			
			/* Adjust Modal Light Mode */
			.gb-adjust-modal { }
			.gb-adjust-header { background: linear-gradient(135deg, #4f46e5, #7c3aed); }
			.gb-adjust-body { background: #ffffff; }
			.gb-quantity-label { color: #6b7280; }
			.gb-quantity-value { color: #111827; }
			.gb-quantity-input { 
				background: #ffffff; 
				border-color: #d1d5db; 
				color: #111827; 
			}
			.gb-quantity-input:focus { 
				border-color: #4f46e5; 
				box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1); 
			}
			
			/* Form styles light mode */
			.gb-label { color: #374151; }
			.gb-input { background: #ffffff; border-color: #d1d5db; color: #111827; }
			.gb-input:focus { border-color: #4f46e5; box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1); }
			.gb-checkbox-mark { background: #ffffff; border-color: #d1d5db; }
			.gb-checkbox-container input[type="checkbox"]:checked + .gb-checkbox-mark { background: #4f46e5; border-color: #4f46e5; }

			/* Inputs inside modals */
			.gb-input { width: 100%; background: #ffffff; border: 1px solid #e5e7eb; color: #111827; padding: 10px 12px; border-radius: 10px; }
			.gb-input:focus { outline: none; border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,.2); }

			/* Stock cards */
			.gb-stock-card { background: #ffffff; border-color: #e5e7eb; }
			.gb-stock-card:hover { border-color: #94a3b8; }
			.gb-stock-ref { color: #475569; background: #f9fafb; border-color: #e5e7eb; }
			.gb-empty { color: #64748b; }
		}
		
		/* Styles pour les statuts du scanner */
		.gb-status {
			padding: 12px 16px;
			border-radius: 10px;
			font-weight: 500;
			text-align: center;
			margin: 10px 0;
			transition: all 0.3s ease;
			border: 1px solid var(--gb-border);
			background: var(--gb-surface);
			color: var(--gb-white);
		}
		
		.gb-status.gb-success {
			background: linear-gradient(135deg, var(--gb-success), #059669);
			border-color: var(--gb-success);
			color: white;
			box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
		}
		
		.gb-status.gb-warning {
			background: linear-gradient(135deg, var(--gb-warning), #d97706);
			border-color: var(--gb-warning);
			color: white;
			box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
		}
		
		.gb-status.gb-danger {
			background: linear-gradient(135deg, var(--gb-danger), #dc2626);
			border-color: var(--gb-danger);
			color: white;
			box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
		}
		
		/* Animation pour les changements de statut */
		.gb-status {
			animation: statusFade 0.3s ease-in-out;
		}
		
		@keyframes statusFade {
			0% { opacity: 0; transform: translateY(-10px); }
			100% { opacity: 1; transform: translateY(0); }
		}
		
		/* Interface tactile pour l'ajustement de stock */
		.gb-product-info {
			text-align: center;
			margin-bottom: 24px;
		}
		
		.gb-product-ref {
			background: var(--gb-surface);
			border: 1px solid var(--gb-border);
			border-radius: 8px;
			padding: 8px 12px;
			font-family: 'Courier New', monospace;
			font-size: 14px;
			color: var(--gb-muted);
			margin-bottom: 8px;
			display: inline-block;
		}
		
		.gb-quantity-control {
			display: flex;
			align-items: center;
			justify-content: center;
			gap: 0;
			margin: 32px 0;
			background: var(--gb-surface);
			border: 2px solid var(--gb-border);
			border-radius: 16px;
			padding: 8px;
			box-shadow: inset 0 2px 8px rgba(0,0,0,0.1);
		}
		
		.gb-quantity-btn {
			width: 60px;
			height: 60px;
			border: none;
			border-radius: 12px;
			font-size: 24px;
			font-weight: bold;
			cursor: pointer;
			transition: all 0.2s ease;
			display: flex;
			align-items: center;
			justify-content: center;
			user-select: none;
			-webkit-tap-highlight-color: transparent;
		}
		
		.gb-btn-minus {
			background: linear-gradient(135deg, #ef4444, #dc2626);
			color: white;
			border-top-right-radius: 4px;
			border-bottom-right-radius: 4px;
		}
		
		.gb-btn-minus:hover {
			background: linear-gradient(135deg, #f87171, #ef4444);
			transform: scale(1.05);
		}
		
		.gb-btn-minus:active {
			transform: scale(0.95);
		}
		
		.gb-btn-plus {
			background: linear-gradient(135deg, #10b981, #059669);
			color: white;
			border-top-left-radius: 4px;
			border-bottom-left-radius: 4px;
		}
		
		.gb-btn-plus:hover {
			background: linear-gradient(135deg, #34d399, #10b981);
			transform: scale(1.05);
		}
		
		.gb-btn-plus:active {
			transform: scale(0.95);
		}
		
		.gb-quantity-display {
			flex: 1;
			text-align: center;
			padding: 0 20px;
			background: var(--gb-bg);
			border-radius: 8px;
			margin: 0 4px;
			min-height: 60px;
			display: flex;
			flex-direction: column;
			align-items: center;
			justify-content: center;
		}
		
		.gb-quantity-value {
			font-size: 32px;
			font-weight: 800;
			color: var(--gb-white);
			line-height: 1;
			margin-bottom: 2px;
		}
		
		.gb-quantity-unit {
			font-size: 12px;
			color: var(--gb-muted);
			text-transform: uppercase;
			letter-spacing: 0.5px;
		}
		
		.gb-action-buttons {
			display: flex;
			gap: 12px;
			margin-top: 32px;
		}
		
		.gb-btn-cancel,
		.gb-btn-save {
			flex: 1;
			padding: 16px 24px;
			border: none;
			border-radius: 12px;
			font-size: 16px;
			font-weight: 600;
			cursor: pointer;
			transition: all 0.2s ease;
			display: flex;
			align-items: center;
			justify-content: center;
			gap: 8px;
			-webkit-tap-highlight-color: transparent;
		}
		
		.gb-btn-cancel {
			background: var(--gb-surface);
			color: var(--gb-muted);
			border: 1px solid var(--gb-border);
		}
		
		.gb-btn-cancel:hover {
			background: var(--gb-border);
			color: var(--gb-white);
		}
		
		.gb-btn-save {
			background: linear-gradient(135deg, var(--gb-primary), #3730a3);
			color: white;
		}
		
		.gb-btn-save:hover {
			background: linear-gradient(135deg, #5b52ff, var(--gb-primary));
			transform: translateY(-1px);
		}
		
		.gb-btn-save:active {
			transform: translateY(0);
		}
		
		/* Responsive pour mobile */
		@media (max-width: 480px) {
			.gb-adjust-modal {
				width: 95vw;
			}
			
			.gb-quantity-btn {
				width: 50px;
				height: 50px;
				font-size: 20px;
			}
			
			.gb-quantity-value {
				font-size: 28px;
			}
			
			.gb-action-buttons {
				flex-direction: column;
			}
		}
		
		/* Styles pour le modal Scanner QR */
		.gb-qr-modal {
			width: min(90vw, 500px);
			max-height: none;
		}
		
		.gb-qr-header {
			display: flex;
			align-items: center;
			justify-content: space-between;
			padding: 20px 24px;
			background: linear-gradient(135deg, #6366f1, #8b5cf6);
			color: white;
		}
		
		.gb-qr-header h3 {
			margin: 0;
			font-size: 18px;
			font-weight: 700;
		}
		
		.gb-qr-body {
			padding: 24px;
			text-align: center;
		}
		
		.gb-qr-info {
			margin-bottom: 20px;
		}
		
		.gb-qr-product-name {
			font-size: 18px;
			font-weight: 600;
			color: var(--gb-white);
			margin-bottom: 8px;
		}
		
		.gb-qr-quantity-info {
			color: var(--gb-muted);
			font-size: 14px;
		}
		
		.gb-qr-quantity {
			font-weight: 600;
			color: #ef4444;
			margin-left: 4px;
		}
		
		.gb-qr-scanner {
			position: relative;
			width: 300px;
			height: 300px;
			margin: 0 auto 20px;
			border-radius: 12px;
			overflow: hidden;
			background: #000;
		}
		
		#gb_qr_video {
			width: 100%;
			height: 100%;
			object-fit: cover;
		}
		
		.gb-qr-overlay {
			position: absolute;
			top: 0;
			left: 0;
			right: 0;
			bottom: 0;
			display: flex;
			align-items: center;
			justify-content: center;
		}
		
		.gb-qr-frame {
			width: 200px;
			height: 200px;
			border: 3px solid #10b981;
			border-radius: 12px;
			position: relative;
			background: rgba(16, 185, 129, 0.1);
		}
		
		.gb-qr-frame::before,
		.gb-qr-frame::after {
			content: '';
			position: absolute;
			width: 30px;
			height: 30px;
			border: 4px solid #10b981;
		}
		
		.gb-qr-frame::before {
			top: -4px;
			left: -4px;
			border-right: none;
			border-bottom: none;
		}
		
		.gb-qr-frame::after {
			bottom: -4px;
			right: -4px;
			border-left: none;
			border-top: none;
		}
		
		.gb-qr-status {
			padding: 12px 16px;
			border-radius: 8px;
			font-size: 14px;
			margin-bottom: 20px;
			background: var(--gb-surface);
			border: 1px solid var(--gb-border);
			color: var(--gb-muted);
		}
		
		.gb-qr-status.success {
			background: rgba(16, 185, 129, 0.1);
			border-color: #10b981;
			color: #10b981;
		}
		
		.gb-qr-status.error {
			background: rgba(239, 68, 68, 0.1);
			border-color: #ef4444;
			color: #ef4444;
		}
		
		.gb-qr-actions {
			display: flex;
			gap: 12px;
		}
		
		.gb-btn-manual {
			flex: 1;
			padding: 12px 20px;
			border: none;
			border-radius: 8px;
			font-size: 14px;
			font-weight: 600;
			cursor: pointer;
			transition: all 0.2s ease;
			display: flex;
			align-items: center;
			justify-content: center;
			gap: 8px;
			background: var(--gb-info);
			color: white;
		}
		
		.gb-btn-manual:hover {
			background: #0891b2;
			transform: translateY(-1px);
		}
		
		.gb-btn-partner {
			flex: 1;
			padding: 12px 20px;
			border: none;
			border-radius: 8px;
			font-size: 14px;
			font-weight: 600;
			cursor: pointer;
			transition: all 0.2s ease;
			display: flex;
			align-items: center;
			justify-content: center;
			gap: 8px;
			background: linear-gradient(135deg, #f59e0b, #d97706);
			color: white;
		}
		
		.gb-btn-partner:hover {
			background: linear-gradient(135deg, #d97706, #b45309);
			transform: translateY(-1px);
		}
		
		/* Responsive pour le modal QR */
		@media (max-width: 480px) {
			.gb-qr-modal {
				width: 95vw;
			}
			
			.gb-qr-scanner {
				width: 250px;
				height: 250px;
			}
			
			.gb-qr-frame {
				width: 160px;
				height: 160px;
			}
			
			.gb-qr-actions {
				flex-direction: column;
			}
		}
		
		/* Styles pour le modal Partenaire */
		.gb-partner-modal {
			width: 90vw;
			max-width: 500px;
			background: var(--gb-white);
			border-radius: 16px;
			box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
			overflow: hidden;
		}
		
		.gb-partner-header {
			background: linear-gradient(135deg, #f59e0b, #d97706);
			color: white;
			padding: 20px;
			display: flex;
			justify-content: space-between;
			align-items: center;
		}
		
		.gb-partner-header h3 {
			margin: 0;
			font-size: 18px;
			font-weight: 600;
			display: flex;
			align-items: center;
			gap: 8px;
		}
		
		.gb-partner-body {
			padding: 24px;
		}
		
		.gb-partner-info {
			background: var(--gb-surface);
			border-radius: 12px;
			padding: 16px;
			margin-bottom: 20px;
		}
		
		.gb-partner-product-name {
			font-size: 16px;
			font-weight: 600;
			color: var(--gb-text);
			margin-bottom: 12px;
		}
		
		.gb-partner-quantity-info,
		.gb-partner-price-info,
		.gb-partner-total-info {
			display: flex;
			justify-content: space-between;
			align-items: center;
			margin-bottom: 8px;
		}
		
		.gb-partner-label {
			color: var(--gb-muted);
			font-size: 14px;
		}
		
		.gb-partner-quantity,
		.gb-partner-price {
			font-weight: 600;
			color: var(--gb-text);
		}
		
		.gb-partner-total {
			font-weight: 700;
			color: #f59e0b;
			font-size: 16px;
		}
		
		.gb-partner-list {
			max-height: 300px;
			overflow-y: auto;
			border: 1px solid var(--gb-border);
			border-radius: 8px;
			margin-bottom: 20px;
		}
		
		.gb-partner-loading {
			padding: 40px;
			text-align: center;
			color: var(--gb-muted);
		}
		
		.gb-partner-item {
			padding: 16px;
			border-bottom: 1px solid var(--gb-border);
			cursor: pointer;
			transition: all 0.2s ease;
			display: flex;
			justify-content: space-between;
			align-items: center;
		}
		
		.gb-partner-item:last-child {
			border-bottom: none;
		}
		
		.gb-partner-item:hover {
			background: var(--gb-surface);
		}
		
		.gb-partner-item.selected {
			background: rgba(245, 158, 11, 0.1);
			border-color: #f59e0b;
		}
		
		.gb-partner-name {
			font-weight: 600;
			color: var(--gb-text);
		}
		
		.gb-partner-contact {
			font-size: 12px;
			color: var(--gb-muted);
			margin-top: 4px;
		}
		
		.gb-partner-select-btn {
			padding: 8px 16px;
			background: #f59e0b;
			color: white;
			border: none;
			border-radius: 6px;
			font-size: 12px;
			font-weight: 600;
			cursor: pointer;
			transition: all 0.2s ease;
		}
		
		.gb-partner-select-btn:hover {
			background: #d97706;
		}
		
		.gb-partner-actions {
			display: flex;
			gap: 12px;
			justify-content: flex-end;
		}
		
		/* Responsive pour le modal Partenaire */
		@media (max-width: 480px) {
			.gb-partner-modal {
				width: 95vw;
			}
			
			.gb-partner-actions {
				flex-direction: column;
			}
		}
	</style>
	<script>
		// Données d'alerte côté serveur (forcer un tableau indexé)
		const GB_ALERTS = <?php echo json_encode(array_values(array_map(function($p){
			return [
				'id'=>(int)$p['id'],
				'nom'=>$p['nom'],
				'reference'=>$p['reference'],
				'fournisseur_nom'=>isset($p['fournisseur_nom']) ? $p['fournisseur_nom'] : null,
				'quantite'=>(int)$p['quantite'],
				'seuil_alerte'=>(int)$p['seuil_alerte']
			];
		}, $gb_alert))); ?>;
	</script>
</head>
<body>
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

	<div class="gb-container" id="mainContent" style="display: none;">
		<div class="gb-header">
			<div class="gb-title"><i class="fas fa-boxes"></i> Inventaire</div>
			<div class="gb-actions">
				<button class="gb-btn gb-btn--success" onclick="gbOpenScanner()"><i class="fas fa-barcode"></i> Scanner</button>
				<button class="gb-btn gb-btn--info" onclick="gbOpenStockCheck()"><i class="fas fa-eye"></i> Vérifier Stock</button>
			<button class="gb-btn gb-btn--primary" onclick="gbOpen('gbAddModal')"><i class="fas fa-plus"></i> Nouveau produit</button>
			<button class="gb-btn" onclick="gbOpenAlerts()"><i class="fas fa-triangle-exclamation"></i> Alertes stock</button>
			</div>
		</div>

		<div class="gb-grid-stats">
			<div class="gb-card">
				<div class="gb-stat"><div class="gb-stat-icon"><i class="fas fa-box"></i></div><div><div class="gb-stat-label">Total</div><div class="gb-stat-value"><?php echo $gb_total; ?></div></div></div>
			</div>
			<div class="gb-card">
				<div class="gb-stat"><div class="gb-stat-icon" style="color:#22c55e;"><i class="fas fa-circle-check"></i></div><div><div class="gb-stat-label">En stock</div><div class="gb-stat-value"><?php echo $gb_stock; ?></div></div></div>
			</div>
			<div class="gb-card" style="cursor:pointer;" onclick="gbOpenAlerts()">
				<div class="gb-stat"><div class="gb-stat-icon" style="color:#f59e0b;"><i class="fas fa-triangle-exclamation"></i></div><div><div class="gb-stat-label">Alerte</div><div class="gb-stat-value"><?php echo count($gb_alert); ?></div></div></div>
			</div>
			<div class="gb-card">
				<div class="gb-stat"><div class="gb-stat-icon" style="color:#ef4444;"><i class="fas fa-xmark"></i></div><div><div class="gb-stat-label">Épuisés</div><div class="gb-stat-value"><?php echo count($gb_out); ?></div></div></div>
			</div>
			<div class="gb-card">
				<div class="gb-stat"><div class="gb-stat-icon" style="color:#06b6d4;"><i class="fas fa-eye"></i></div><div><div class="gb-stat-label">Suivis</div><div class="gb-stat-value"><?php echo $gb_has_suivre_stock ? count($gb_tracked) : 0; ?></div></div></div>
			</div>
		</div>

		<div class="gb-controls">
			<div class="gb-search">
				<i class="fas fa-search"></i>
				<input id="gbSearch" placeholder="Rechercher par nom ou référence..." />
			</div>
			<select id="gbFilter" class="gb-select">
				<option value="all">Tous</option>
				<option value="stock">En stock</option>
				<option value="alert">Alerte</option>
				<option value="out">Épuisés</option>
				<?php if ($gb_has_suivre_stock): ?><option value="tracked">Suivis</option><?php endif; ?>
			</select>
			<button class="gb-btn" onclick="gbExport()"><i class="fas fa-download"></i> Exporter</button>
		</div>

		<div class="gb-table-wrap">
			<table class="gb-table" id="gbTable">
				<thead><tr>
					<th>Référence</th>
					<th>Nom</th>
					<th>Fournisseur</th>
					<th>Prix Achat</th>
					<th>Prix Vente</th>
					<th>Stock</th>
					<th>Statut</th>
					<th>Actions</th>
				</tr></thead>
				<tbody>
				<?php foreach ($gb_products as $p): ?>
					<tr data-ref="<?php echo strtolower($p['reference']); ?>" data-name="<?php echo strtolower($p['nom']); ?>" data-qty="<?php echo (int)$p['quantite']; ?>" data-th="<?php echo (int)$p['seuil_alerte']; ?>" data-tracked="<?php echo ($gb_has_suivre_stock && isset($p['suivre_stock']) && (int)$p['suivre_stock']===1) ? '1' : '0'; ?>" data-id="<?php echo (int)$p['id']; ?>">
						<td><code><?php echo htmlspecialchars($p['reference']); ?></code></td>
						<td><b><?php echo htmlspecialchars($p['nom']); ?></b><?php if (!empty($p['description'])): ?><br /><span style="color:#93a6cd; font-size:12px;"><?php echo htmlspecialchars(substr($p['description'],0,60)); ?></span><?php endif; ?></td>
						<td><?php echo $p['fournisseur_nom'] ? htmlspecialchars($p['fournisseur_nom']) : '<em style="color:#9ca3af;">Non défini</em>'; ?></td>
						<td><?php echo number_format((float)$p['prix_achat'],2); ?>€</td>
						<td><?php echo number_format((float)$p['prix_vente'],2); ?>€</td>
						<td><b><?php echo (int)$p['quantite']; ?></b><?php if ($gb_has_suivre_stock && !empty($p['suivre_stock'])): ?> <i class="fas fa-eye" title="Suivi" style="color:#06b6d4;"></i><?php endif; ?></td>
						<td>
							<?php if ((int)$p['quantite'] === 0): ?><span class="gb-badge gb-badge--danger">Épuisé</span>
							<?php elseif ((int)$p['quantite'] <= (int)$p['seuil_alerte']): ?><span class="gb-badge gb-badge--warn">Alerte</span>
							<?php else: ?><span class="gb-badge gb-badge--ok">En stock</span><?php endif; ?>
						</td>
						<td class="gb-actions-cell">
							<button class="gb-btn" title="Ajuster" onclick="gbOpenAdjust(<?php echo (int)$p['id']; ?>)"><i class="fas fa-boxes"></i></button>
							<button class="gb-btn" title="Modifier" onclick="gbEdit(<?php echo (int)$p['id']; ?>)"><i class="fas fa-pen"></i></button>
							<button class="gb-btn" title="Supprimer" onclick="gbDelete(<?php echo (int)$p['id']; ?>)"><i class="fas fa-trash"></i></button>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<?php if (empty($gb_products)): ?>
				<div class="gb-empty">Aucun produit. Ajoutez votre premier produit.</div>
			<?php endif; ?>
		</div>
	</div>

	<!-- Add Product Modal -->
	<div class="gb-modal" id="gbAddModal" aria-hidden="true">
		<div class="gb-modal__backdrop" onclick="gbClose('gbAddModal')"></div>
		<div class="gb-modal__dialog" style="max-width:720px;">
			<div class="gb-modal__header">
				<div class="gb-modal__title"><i class="fas fa-plus"></i> Nouveau produit</div>
				<button class="gb-modal__close" onclick="gbClose('gbAddModal')"><i class="fas fa-times"></i></button>
			</div>
			<div class="gb-modal__body">
				<form id="gbAddForm" method="POST" action="?page=inventaire_actions">
					<input type="hidden" name="action" value="ajouter_produit" />
					<div style="display:grid; gap:12px; grid-template-columns: 1fr 1fr;">
						<div>
							<label>Référence *</label>
							<input class="gb-input" name="reference" required />
						</div>
						<div>
							<label>Nom *</label>
							<input class="gb-input" name="nom" required />
						</div>
					</div>
					<div style="margin-top:12px;">
						<label>Description</label>
						<textarea class="gb-input" name="description" rows="3"></textarea>
					</div>
					<div style="margin-top:12px;">
						<label>Fournisseur</label>
						<select class="gb-input" name="fournisseur_id">
							<option value="">-- Sélectionner un fournisseur --</option>
							<?php
							// Récupérer les fournisseurs
							try {
								$stmt_fournisseurs = $shop_pdo->prepare("SELECT id, nom FROM fournisseurs ORDER BY nom");
								$stmt_fournisseurs->execute();
								while ($fournisseur = $stmt_fournisseurs->fetch()) {
									echo '<option value="' . (int)$fournisseur['id'] . '">' . htmlspecialchars($fournisseur['nom']) . '</option>';
								}
							} catch (Exception $e) {
								// En cas d'erreur, on continue sans fournisseurs
							}
							?>
						</select>
					</div>
					<div style="display:grid; gap:12px; grid-template-columns: 1fr 1fr; margin-top:12px;">
						<div>
							<label>Prix d'achat *</label>
							<input class="gb-input" type="number" step="0.01" name="prix_achat" required />
						</div>
						<div>
							<label>Prix de vente *</label>
							<input class="gb-input" type="number" step="0.01" name="prix_vente" required />
						</div>
					</div>
					<div style="display:grid; gap:12px; grid-template-columns: 1fr 1fr; margin-top:12px;">
						<div>
							<label>Quantité *</label>
							<input class="gb-input" type="number" name="quantite" value="0" required />
						</div>
						<div>
							<label>Seuil d'alerte *</label>
							<input class="gb-input" type="number" name="seuil_alerte" value="5" required />
						</div>
					</div>
					<?php if ($gb_has_suivre_stock): ?>
					<div style="margin-top:10px; display:flex; gap:8px; align-items:center;">
						<input id="gb_suivre_stock" type="checkbox" name="suivre_stock" value="1" />
						<label for="gb_suivre_stock">Suivre ce produit</label>
					</div>
					<?php endif; ?>
					<div style="display:flex; gap:10px; justify-content:flex-end; margin-top:16px;">
						<button type="button" class="gb-btn" onclick="gbClose('gbAddModal')">Annuler</button>
						<button type="submit" class="gb-btn gb-btn--primary"><i class="fas fa-plus"></i> Ajouter</button>
					</div>
				</form>
			</div>
		</div>
	</div>

	<!-- Stock Check Modal -->
	<div class="gb-modal" id="gbStockModal" aria-hidden="true">
		<div class="gb-modal__backdrop" onclick="gbClose('gbStockModal')"></div>
		<div class="gb-modal__dialog">
			<div class="gb-modal__header">
				<div class="gb-modal__title"><i class="fas fa-eye"></i> Vérifier le stock</div>
				<button class="gb-modal__close" onclick="gbClose('gbStockModal')"><i class="fas fa-times"></i></button>
			</div>
			<div class="gb-modal__body">
				<div class="gb-search" style="margin-bottom:10px;">
					<i class="fas fa-search"></i>
					<input id="gbStockSearch" placeholder="Rechercher..." />
				</div>
				<div id="gbStockCards" class="gb-stock-grid">
					<div class="gb-empty">Chargement...</div>
				</div>
			</div>
		</div>
	</div>

	<!-- Alerts Modal -->
	<div class="gb-modal" id="gbAlertModal" aria-hidden="true">
		<div class="gb-modal__backdrop" onclick="gbClose('gbAlertModal')"></div>
		<div class="gb-modal__dialog" style="max-width:980px;">
			<div class="gb-modal__header">
				<div class="gb-modal__title"><i class="fas fa-triangle-exclamation" style="color:#f59e0b;"></i> Produits en alerte stock</div>
				<button class="gb-modal__close" onclick="gbClose('gbAlertModal')"><i class="fas fa-times"></i></button>
			</div>
			<div class="gb-modal__body">
				<div style="display:flex; gap:12px; align-items:center; margin-bottom:12px;">
					<label for="gbAlertSupplier" class="gb-label" style="margin:0;">Fournisseur</label>
					<select id="gbAlertSupplier" class="gb-input" style="max-width:320px;"></select>
				</div>
				<div class="gb-table-wrap">
					<table class="gb-table">
						<thead><tr>
							<th>Produit</th>
							<th>Référence</th>
							<th>Fournisseur</th>
							<th>Quantité en stock</th>
							<th>Quantité requise</th>
							<th style="width:120px;">Google</th>
						</tr></thead>
						<tbody id="gbAlertBody">
							<!-- Rempli en JS -->
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>

	<!-- Adjust Stock Modal -->
	<div class="gb-modal" id="gbAdjustModal" aria-hidden="true">
		<div class="gb-modal__backdrop" onclick="gbClose('gbAdjustModal')"></div>
		<div class="gb-modal__dialog gb-adjust-modal">
			<div class="gb-adjust-header">
				<h3 id="gb_adjust_name">Produit</h3>
				<button class="gb-adjust-close" onclick="gbClose('gbAdjustModal')">
					<i class="fas fa-times"></i>
				</button>
			</div>
			<div class="gb-adjust-body">
				<div class="gb-product-info">
					<div class="gb-product-ref" id="gb_adjust_ref">REF-000</div>
					<div class="gb-quantity-label">Stock actuel</div>
				</div>
				
				<!-- Interface tactile avec boutons +/- -->
				<div class="gb-quantity-control">
					<button class="gb-quantity-btn gb-btn-minus" onclick="gbDecreaseQuantity()" type="button">
						<i class="fas fa-minus"></i>
					</button>
					
					<div class="gb-quantity-display">
						<div class="gb-quantity-value" id="gb_adjust_current">0</div>
						<div class="gb-quantity-unit">unités</div>
				</div>
					
					<button class="gb-quantity-btn gb-btn-plus" onclick="gbIncreaseQuantity()" type="button">
						<i class="fas fa-plus"></i>
					</button>
				</div>
				
				<!-- Champs cachés -->
				<input type="hidden" id="gb_adjust_id" />
				<input type="hidden" id="gb_adjust_original" />
				<input type="hidden" id="gb_adjust_new" />
				
				<!-- Boutons d'action -->
				<div class="gb-action-buttons">
					<button class="gb-btn-cancel" onclick="gbClose('gbAdjustModal')" type="button">
						<i class="fas fa-times"></i>
						Annuler
					</button>
					<button class="gb-btn-save" onclick="gbUpdateStock()" type="button">
					<i class="fas fa-check"></i>
						Confirmer
				</button>
				</div>
			</div>
		</div>
	</div>

	<!-- Modal Scanner QR Réparation -->
	<div class="gb-modal" id="gbQrScanModal" aria-hidden="true">
		<div class="gb-modal__backdrop" onclick="gbClose('gbQrScanModal')"></div>
		<div class="gb-modal__dialog gb-qr-modal">
			<div class="gb-qr-header">
				<h3>Scanner QR Code Réparation</h3>
				<button class="gb-adjust-close" onclick="gbClose('gbQrScanModal')">
					<i class="fas fa-times"></i>
				</button>
			</div>
			<div class="gb-qr-body">
				<div class="gb-qr-info">
					<div class="gb-qr-product-name" id="gb_qr_product_name">Produit</div>
					<div class="gb-qr-quantity-info">
						<span class="gb-qr-label">Quantité utilisée:</span>
						<span class="gb-qr-quantity" id="gb_qr_quantity">1</span>
					</div>
				</div>
				
				<!-- Scanner QR -->
				<div class="gb-qr-scanner">
					<video id="gb_qr_video" autoplay muted playsinline></video>
					<div class="gb-qr-overlay">
						<div class="gb-qr-frame"></div>
					</div>
				</div>
				
				<div class="gb-qr-status" id="gb_qr_status">
					Positionnez le QR code dans le cadre
				</div>
				
				<!-- Boutons d'action -->
				<div class="gb-qr-actions">
					<button class="gb-btn-cancel" onclick="gbCancelQrScan()" type="button">
						<i class="fas fa-times"></i>
						Annuler
					</button>
					<button class="gb-btn-partner" onclick="gbOpenPartnerModal()" type="button">
						<i class="fas fa-handshake"></i>
						Partenaire
					</button>
					<button class="gb-btn-manual" onclick="gbManualReparationId()" type="button">
						<i class="fas fa-keyboard"></i>
						Saisie manuelle
					</button>
				</div>
				
				<!-- Bouton de test (temporaire) -->
				<div style="margin-top: 12px;">
					<button class="gb-btn-manual" onclick="gbTestQrUrl()" type="button" style="background: #f59e0b;">
						<i class="fas fa-flask"></i>
						Test QR (ID=1)
					</button>
				</div>
				
				<!-- Champs cachés -->
				<input type="hidden" id="gb_qr_product_id" />
				<input type="hidden" id="gb_qr_quantity_used" />
				<input type="hidden" id="gb_qr_original_quantity" />
			</div>
		</div>
	</div>

	<!-- Modal Sélection Partenaire -->
	<div class="gb-modal" id="gbPartnerModal" aria-hidden="true">
		<div class="gb-modal__backdrop" onclick="gbClose('gbPartnerModal')"></div>
		<div class="gb-modal__dialog gb-partner-modal">
			<div class="gb-partner-header">
				<h3><i class="fas fa-handshake"></i> Sélectionner un Partenaire</h3>
				<button class="gb-adjust-close" onclick="gbClose('gbPartnerModal')">
					<i class="fas fa-times"></i>
				</button>
			</div>
			<div class="gb-partner-body">
				<div class="gb-partner-info">
					<div class="gb-partner-product-name" id="gb_partner_product_name">Produit</div>
					<div class="gb-partner-quantity-info">
						<span class="gb-partner-label">Quantité utilisée:</span>
						<span class="gb-partner-quantity" id="gb_partner_quantity">1</span>
					</div>
					<div class="gb-partner-price-info">
						<span class="gb-partner-label">Prix unitaire:</span>
						<span class="gb-partner-price" id="gb_partner_price">0.00€</span>
					</div>
					<div class="gb-partner-total-info">
						<span class="gb-partner-label">Total avec coefficient (x1.2):</span>
						<span class="gb-partner-total" id="gb_partner_total">0.00€</span>
					</div>
				</div>
				
				<div class="gb-partner-list" id="gb_partner_list">
					<div class="gb-partner-loading">
						<i class="fas fa-spinner fa-spin"></i>
						Chargement des partenaires...
					</div>
				</div>
				
				<!-- Boutons d'action -->
				<div class="gb-partner-actions">
					<button class="gb-btn-cancel" onclick="gbCancelPartnerSelection()" type="button">
						<i class="fas fa-times"></i>
						Annuler
					</button>
				</div>
				
				<!-- Champs cachés -->
				<input type="hidden" id="gb_partner_product_id" />
				<input type="hidden" id="gb_partner_quantity_used" />
				<input type="hidden" id="gb_partner_original_quantity" />
				<input type="hidden" id="gb_partner_unit_price" />
			</div>
		</div>
	</div>

	<!-- Edit Product Modal -->
	<div class="gb-modal" id="gbEditModal" aria-hidden="true">
		<div class="gb-modal__backdrop" onclick="gbClose('gbEditModal')"></div>
		<div class="gb-modal__dialog" style="max-width:600px;">
			<div class="gb-modal__header">
				<div class="gb-modal__title"><i class="fas fa-pen"></i> Modifier le produit</div>
				<button class="gb-modal__close" onclick="gbClose('gbEditModal')"><i class="fas fa-times"></i></button>
			</div>
			<div class="gb-modal__body">
				<form id="gbEditForm" onsubmit="return gbSubmitEdit(event)">
					<input type="hidden" id="gb_edit_id" />
					
					<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
						<div>
							<label class="gb-label">Référence</label>
							<input class="gb-input" type="text" id="gb_edit_reference" required />
						</div>
						<div>
							<label class="gb-label">Prix de vente (€)</label>
							<input class="gb-input" type="number" step="0.01" id="gb_edit_prix_vente" required />
						</div>
					</div>
					
					<div style="margin-bottom: 1rem;">
						<label class="gb-label">Nom du produit</label>
						<input class="gb-input" type="text" id="gb_edit_nom" required />
					</div>
					
					<div style="margin-bottom: 1rem;">
						<label class="gb-label">Description</label>
						<textarea class="gb-input" id="gb_edit_description" rows="3"></textarea>
					</div>
					
					<div style="margin-bottom: 1rem;">
						<label class="gb-label">Fournisseur</label>
						<select class="gb-input" id="gb_edit_fournisseur">
							<option value="">-- Sélectionner un fournisseur --</option>
							<?php
							// Récupérer les fournisseurs pour le modal d'édition
							try {
								$stmt_fournisseurs = $shop_pdo->prepare("SELECT id, nom FROM fournisseurs ORDER BY nom");
								$stmt_fournisseurs->execute();
								while ($fournisseur = $stmt_fournisseurs->fetch()) {
									echo '<option value="' . (int)$fournisseur['id'] . '">' . htmlspecialchars($fournisseur['nom']) . '</option>';
								}
							} catch (Exception $e) {
								// En cas d'erreur, on continue sans fournisseurs
							}
							?>
						</select>
					</div>
					
					<div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
						<div>
							<label class="gb-label">Prix d'achat (€)</label>
							<input class="gb-input" type="number" step="0.01" id="gb_edit_prix_achat" />
						</div>
						<div>
							<label class="gb-label">Quantité</label>
							<input class="gb-input" type="number" id="gb_edit_quantite" min="0" required />
						</div>
						<div>
							<label class="gb-label">Seuil d'alerte</label>
							<input class="gb-input" type="number" id="gb_edit_seuil" min="0" value="5" />
						</div>
					</div>
					
					<div style="margin-bottom: 2rem;">
						<label class="gb-checkbox-container">
							<input type="checkbox" id="gb_edit_suivre_stock" />
							<span class="gb-checkbox-mark"></span>
							Suivre ce produit dans le stock
						</label>
					</div>
					
					<div style="display: flex; gap: 1rem; justify-content: flex-end;">
						<button type="button" class="gb-btn" onclick="gbClose('gbEditModal')" style="background: var(--gb-text-muted); color: white;">
							Annuler
						</button>
						<button type="submit" class="gb-btn gb-btn--primary">
							<i class="fas fa-save"></i>
							Enregistrer
						</button>
					</div>
				</form>
			</div>
		</div>
	</div>

	<!-- Scanner Modal -->
	<div class="gb-modal" id="gbScanModal" aria-hidden="true">
		<div class="gb-modal__backdrop" onclick="gbClose('gbScanModal')"></div>
		<div class="gb-modal__dialog" style="max-width:640px;">
			<div class="gb-modal__header">
				<div class="gb-modal__title"><i class="fas fa-barcode"></i> Scanner</div>
				<button class="gb-modal__close" onclick="gbClose('gbScanModal')"><i class="fas fa-times"></i></button>
			</div>
			<div class="gb-modal__body">
				<div id="gb_scan_area" style="height:380px; background:#000; border-radius:12px; overflow:hidden; position:relative;">
					<video id="gb_scan_video" style="width:100%; height:100%; object-fit:cover;"></video>
					<div style="position:absolute; left:50%; top:50%; width:220px; height:2px; transform:translate(-50%,-50%); background:#ff2d2d; box-shadow:0 0 10px #ff2d2d;"></div>
				</div>
				<div id="gb_scan_status" class="gb-card" style="margin-top:12px; text-align:center;">Initialisation...</div>
			</div>
		</div>
	</div>

	<script src="https://cdn.jsdelivr.net/npm/quagga@0.12.1/dist/quagga.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.js"></script>
	<script>
		// Utils Modals
		function gbOpen(id){ document.getElementById(id).classList.add('gb-show'); }
		function gbClose(id){ document.getElementById(id).classList.remove('gb-show'); }

		// Search/Filter
		document.getElementById('gbSearch').addEventListener('input', function(){
			const q = this.value.toLowerCase();
			document.querySelectorAll('#gbTable tbody tr').forEach(tr=>{
				const ok = (tr.dataset.name||'').includes(q) || (tr.dataset.ref||'').includes(q);
				tr.style.display = ok ? '' : 'none';
			});
		});
		document.getElementById('gbFilter').addEventListener('change', function(){
			const f = this.value;
			document.querySelectorAll('#gbTable tbody tr').forEach(tr=>{
				const qty = parseInt(tr.dataset.qty||'0',10);
				const th  = parseInt(tr.dataset.th||'0',10);
				const tk  = tr.dataset.tracked === '1';
				let vis = true;
				switch(f){
					case 'stock': vis = qty>0; break;
					case 'alert': vis = qty>0 && qty<=th; break;
					case 'out': vis = qty===0; break;
					case 'tracked': vis = tk; break;
					default: vis = true;
				}
				tr.style.display = vis ? '' : 'none';
			});
		});

		// Export
		function gbExport(){ const f = document.getElementById('gbFilter').value; window.open(`ajax/export_print.php?filter=${encodeURIComponent(f)}`, '_blank'); }

		// Variables globales pour l'ajustement
		let gbCurrentQuantity = 0;
		let gbOriginalQuantity = 0;

		// Adjust stock
		function gbOpenAdjust(id){
			const tr = document.querySelector(`#gbTable tbody tr[data-id="${id}"]`);
			if(!tr) return;
			
			// Récupérer les informations du produit
			const productName = tr.querySelector('td:nth-child(2) b').textContent;
			const productRef = tr.querySelector('td:nth-child(2) small')?.textContent || `REF-${id}`;
			const currentQty = parseInt(tr.dataset.qty) || 0;
			
			// Initialiser les variables
			gbCurrentQuantity = currentQty;
			gbOriginalQuantity = currentQty;
			
			// Remplir le modal
			document.getElementById('gb_adjust_id').value = id;
			document.getElementById('gb_adjust_name').textContent = productName;
			document.getElementById('gb_adjust_ref').textContent = productRef;
			document.getElementById('gb_adjust_current').textContent = currentQty;
			document.getElementById('gb_adjust_original').value = currentQty;
			document.getElementById('gb_adjust_new').value = currentQty;
			
			gbOpen('gbAdjustModal');
		}
		
		// Diminuer la quantité
		function gbDecreaseQuantity() {
			if (gbCurrentQuantity > 0) {
				gbCurrentQuantity--;
				gbUpdateQuantityDisplay();
			}
		}
		
		// Augmenter la quantité
		function gbIncreaseQuantity() {
			gbCurrentQuantity++;
			gbUpdateQuantityDisplay();
		}
		
		// Mettre à jour l'affichage de la quantité
		function gbUpdateQuantityDisplay() {
			document.getElementById('gb_adjust_current').textContent = gbCurrentQuantity;
			document.getElementById('gb_adjust_new').value = gbCurrentQuantity;
			
			// Changer la couleur selon la variation
			const display = document.querySelector('.gb-quantity-value');
			if (gbCurrentQuantity > gbOriginalQuantity) {
				display.style.color = '#10b981'; // Vert pour augmentation
			} else if (gbCurrentQuantity < gbOriginalQuantity) {
				display.style.color = '#ef4444'; // Rouge pour diminution
			} else {
				display.style.color = 'var(--gb-white)'; // Blanc pour inchangé
			}
		}
		
		function gbFocusInput() {
			const input = document.getElementById('gb_adjust_new');
			input.focus();
			input.select();
		}
		
		function gbUpdateStock() {
			const produitId = document.getElementById('gb_adjust_id').value;
			const nouvelleQuantite = gbCurrentQuantity;
			const originalQuantite = gbOriginalQuantity;
			
			if (isNaN(nouvelleQuantite) || nouvelleQuantite < 0) {
				gbShowToast('❌ Quantité invalide', 'error');
				return;
			}
			
			if (nouvelleQuantite === originalQuantite) {
				gbClose('gbAdjustModal');
				return;
			}
			
			// Si la quantité diminue, ouvrir le scanner QR
			if (nouvelleQuantite < originalQuantite) {
				gbOpenQrScanModal(produitId, originalQuantite - nouvelleQuantite);
				return;
			}
			
			// Sinon, procéder normalement pour les augmentations
			gbProceedWithStockUpdate();
		}
		
		function gbProceedWithStockUpdate() {
			const produitId = document.getElementById('gb_adjust_id').value;
			const nouvelleQuantite = gbCurrentQuantity;
			const originalQuantite = gbOriginalQuantity;
			
			// Désactiver le bouton pendant la requête
			const btn = document.querySelector('.gb-btn-save');
			const originalText = btn.innerHTML;
			btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mise à jour...';
			btn.disabled = true;
			
			// Requête AJAX
			const formData = new FormData();
			formData.append('produit_id', produitId);
			formData.append('nouvelle_quantite', nouvelleQuantite);
			
			fetch('ajax/ajuster_stock_minimal.php', {
				method: 'POST',
				body: formData
			})
			.then(response => response.json())
			.then(data => {
				if (data.success) {
					// Mettre à jour l'affichage dans le tableau
					const row = document.querySelector(`#gbTable tbody tr[data-id="${produitId}"]`);
					if (row) {
						const quantiteCell = row.querySelector('td:nth-child(5) strong');
						if (quantiteCell) {
							quantiteCell.textContent = data.nouvelle_quantite;
						}
						row.dataset.qty = data.nouvelle_quantite;
					}
					
					// Mettre à jour la carte dans le modal "Vérifier le stock" si elle existe
					gbUpdateStockCard(produitId, data.nouvelle_quantite, data.produit_nom);
					
					// Fermer le modal
					gbClose('gbAdjustModal');
					
					// Afficher un message de succès discret
					gbShowToast('✅ Stock mis à jour: ' + data.nouvelle_quantite, 'success');
				} else {
					alert('Erreur: ' + data.message);
				}
			})
			.catch(error => {
				console.error('Erreur:', error);
				alert('Erreur de connexion');
			})
			.finally(() => {
				// Réactiver le bouton
				btn.innerHTML = originalText;
				btn.disabled = false;
			});
		}
		function gbEdit(id) {
			// Récupérer les données du produit depuis le tableau
			const row = document.querySelector(`#gbTable tbody tr[data-id="${id}"]`);
			if (!row) {
				alert('Produit non trouvé');
				return;
			}
			
			const cells = row.querySelectorAll('td');
			
			// Remplir le formulaire avec les données existantes
			document.getElementById('gb_edit_id').value = id;
			document.getElementById('gb_edit_reference').value = cells[0].textContent.trim();
			document.getElementById('gb_edit_nom').value = cells[1].querySelector('b').textContent.trim();
			document.getElementById('gb_edit_prix_vente').value = cells[4].textContent.replace('€', '').trim();
			document.getElementById('gb_edit_quantite').value = cells[5].querySelector('b').textContent.trim();
			
			// Récupérer les détails complets du produit via AJAX
			fetch(`ajax/get_product_details.php?id=${id}`)
				.then(response => response.json())
				.then(data => {
					if (data.success) {
						const product = data.product;
						document.getElementById('gb_edit_description').value = product.description || '';
						document.getElementById('gb_edit_prix_achat').value = product.prix_achat || '';
						document.getElementById('gb_edit_seuil').value = product.seuil_alerte || '5';
						document.getElementById('gb_edit_suivre_stock').checked = product.suivre_stock == 1;
						document.getElementById('gb_edit_fournisseur').value = product.fournisseur_id || '';
					}
				})
				.catch(error => {
					console.error('Erreur lors du chargement des détails:', error);
				});
			
			gbOpen('gbEditModal');
		}
		
		function gbSubmitEdit(event) {
			event.preventDefault();
			
			const formData = new FormData();
			formData.append('id', document.getElementById('gb_edit_id').value);
			formData.append('reference', document.getElementById('gb_edit_reference').value);
			formData.append('nom', document.getElementById('gb_edit_nom').value);
			formData.append('description', document.getElementById('gb_edit_description').value);
			formData.append('fournisseur_id', document.getElementById('gb_edit_fournisseur').value);
			formData.append('prix_achat', document.getElementById('gb_edit_prix_achat').value);
			formData.append('prix_vente', document.getElementById('gb_edit_prix_vente').value);
			formData.append('quantite', document.getElementById('gb_edit_quantite').value);
			formData.append('seuil_alerte', document.getElementById('gb_edit_seuil').value);
			formData.append('suivre_stock', document.getElementById('gb_edit_suivre_stock').checked ? '1' : '0');
			
			// Désactiver le bouton pendant la requête
			const submitBtn = event.target.querySelector('button[type="submit"]');
			const originalText = submitBtn.innerHTML;
			submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enregistrement...';
			submitBtn.disabled = true;
			
			fetch('ajax/modifier_produit.php', {
				method: 'POST',
				body: formData
			})
			.then(response => response.json())
			.then(data => {
				if (data.success) {
					// Mettre à jour l'affichage dans le tableau
					gbUpdateTableRow(data.product);
					
					// Fermer le modal
					gbClose('gbEditModal');
					
					// Afficher un message de succès
					gbShowToast('✅ Produit modifié avec succès', 'success');
				} else {
					alert('Erreur: ' + data.message);
				}
			})
			.catch(error => {
				console.error('Erreur:', error);
				alert('Erreur de connexion');
			})
			.finally(() => {
				// Réactiver le bouton
				submitBtn.innerHTML = originalText;
				submitBtn.disabled = false;
			});
			
			return false;
		}
		
		function gbUpdateTableRow(product) {
			const row = document.querySelector(`#gbTable tbody tr[data-id="${product.id}"]`);
			if (row) {
				const cells = row.querySelectorAll('td');
				cells[0].textContent = product.reference;
				cells[1].innerHTML = `<b>${product.nom}</b>`;
				cells[2].innerHTML = product.fournisseur_nom || '<em style="color:#9ca3af;">Non défini</em>';
				cells[3].textContent = parseFloat(product.prix_achat).toFixed(2) + '€';
				cells[4].textContent = parseFloat(product.prix_vente).toFixed(2) + '€';
				cells[5].innerHTML = `<b>${product.quantite}</b>`;
				row.dataset.qty = product.quantite;
			}
		}
		function gbDelete(id){
			if(!confirm('Supprimer ce produit ?')) return;
			const btns = document.querySelectorAll(`.gb-actions-cell button[onclick*="gbDelete(${id})"]`);
			btns.forEach(b=>{ b.disabled=true; b.innerHTML='<i class="fas fa-spinner fa-spin"></i>'; });
			const fd = new FormData(); fd.append('id', id);
			fetch('ajax/supprimer_produit.php', { method:'POST', body: fd })
			.then(r=>r.json())
			.then(d=>{
				if(d.success){
					const tr = document.querySelector(`#gbTable tbody tr[data-id="${id}"]`);
					if(tr){ tr.remove(); }
					gbShowToast('✅ Produit supprimé', 'success');
				}else{
					alert('Erreur: '+(d.message||'Suppression impossible'));
				}
			})
			.catch(()=>alert('Erreur de connexion'))
			.finally(()=>{ btns.forEach(b=>{ b.disabled=false; b.innerHTML='<i class="fas fa-trash"></i>'; }); });
		}

		// Stock check
		function gbOpenStockCheck(){ gbOpen('gbStockModal'); gbLoadTracked(); }
		function gbLoadTracked(){
			const wrap = document.getElementById('gbStockCards');
			wrap.innerHTML = '<div class="gb-empty">Chargement...</div>';
			fetch('ajax/get_tracked_products.php').then(r=>r.json()).then(d=>{
				if(!d.success){ wrap.innerHTML = `<div class="gb-empty">${d.error||'Erreur'}</div>`; return; }
				if(!d.products || d.products.length===0){ wrap.innerHTML = '<div class="gb-empty">Aucun produit suivi. Cochez "Suivre ce produit" lors de l\'ajout.</div>'; return; }
				const grid = document.createElement('div'); grid.className='gb-stock-grid';
				d.products.forEach(p=>{
					const qcls = (p.quantite<=0)?'gb-badge--danger':(p.quantite<=p.seuil_alerte?'gb-badge--warn':'gb-badge--ok');
					const card = document.createElement('div'); 
					card.className='gb-stock-card'; 
					card.setAttribute('data-id', p.id);
					card.onclick=()=>gbOpenAdjust(p.id);
					card.innerHTML = `<div style="display:flex; align-items:start; justify-content:space-between; gap:10px;">
						<div><div class="gb-stock-title">${p.nom}</div><div class="gb-stock-ref">${p.reference}</div></div>
						<span class="gb-badge gb-stock-qty ${qcls}">${p.quantite}</span>
					</div>
					<div style="color:#9fb3da; font-size:13px; margin-top:6px;">Seuil: ${p.seuil_alerte} • Prix: ${Number(p.prix_vente||0).toFixed(2)}€</div>`;
					grid.appendChild(card);
				});
				wrap.innerHTML = ''; wrap.appendChild(grid);
			}).catch(()=>{ wrap.innerHTML = '<div class="gb-empty">Erreur de chargement.</div>'; });
		}

		// Alerts modal
		function gbOpenAlerts(){ gbOpen('gbAlertModal'); setTimeout(gbRenderAlerts,0); }
		function gbRenderAlerts(){
			const body = document.getElementById('gbAlertBody');
			if(!body) return;
			body.innerHTML = '';
			const supplierSelect = document.getElementById('gbAlertSupplier');
			if(supplierSelect && !supplierSelect.dataset.init){
				const set = new Set();
				(GB_ALERTS||[]).forEach(p=>{ if(p.fournisseur_nom){ set.add(p.fournisseur_nom); } });
				supplierSelect.innerHTML = '<option value="">Tous les fournisseurs</option>' + Array.from(set).map(v=>`<option value="${v}">${v}</option>`).join('');
				supplierSelect.dataset.init = '1';
				supplierSelect.onchange = ()=>gbRenderAlerts();
			}
			const activeSupplier = supplierSelect ? supplierSelect.value : '';
			(GB_ALERTS||[]).forEach(p=>{
				if(activeSupplier && p.fournisseur_nom!==activeSupplier) return;
				const required = Math.max(p.seuil_alerte - p.quantite, 0);
				const trEl = document.createElement('tr');
				trEl.innerHTML = `
					<td>${p.nom}</td>
					<td><code>${p.reference}</code></td>
					<td>${p.fournisseur_nom ? p.fournisseur_nom : '<em style="color:#9ca3af;">Non défini</em>'}</td>
					<td><b>${p.quantite}</b></td>
					<td><span class="gb-badge gb-badge--warn">${required}</span></td>
					<td><a class="gb-btn" href="https://www.google.com/search?q=${encodeURIComponent(p.nom+' '+p.reference+' '+(p.fournisseur_nom||''))}" target="_blank" rel="noopener noreferrer"><i class='fab fa-google'></i> Google</a></td>
				`;
				body.appendChild(trEl);
			});
			if(!body.children.length){
				const trEl = document.createElement('tr');
				const td = document.createElement('td'); td.colSpan = 6; td.innerHTML = '<div class="gb-empty">Aucun produit en alerte.</div>';
				trEl.appendChild(td); body.appendChild(trEl);
			}
		}
		function gbGoogle(q){
			try{ window.open('https://www.google.com/search?q='+q, '_blank'); }catch(e){}
		}
		document.addEventListener('input', function(e){ if(e.target && e.target.id==='gbStockSearch'){ const q=e.target.value.toLowerCase(); document.querySelectorAll('#gbStockCards .gb-stock-card').forEach(c=>{ const t=c.querySelector('.gb-stock-title').textContent.toLowerCase(); const r=c.querySelector('.gb-stock-ref').textContent.toLowerCase(); c.style.display = (t.includes(q)||r.includes(q))?'':'none'; }); }});

		// Scanner
		let gbStream=null;
		function gbOpenScanner(){ gbOpen('gbScanModal'); gbStartCam(); }
		function gbCloseScanner(){ gbStopCam(); gbClose('gbScanModal'); }
		// Variables pour la stabilisation du scanner
		let gbDetectedCodes = [];
		let gbIsProcessing = false;
		let gbLastProcessTime = 0;
		
		function gbStartCam(){
			const st = document.getElementById('gb_scan_status'); 
			st.textContent='Ouverture caméra...';
			
			// Configuration caméra simplifiée et compatible
			const constraints = {
				video: {
					facingMode: 'environment'
				}
			};
			
			navigator.mediaDevices.getUserMedia(constraints).then(stream=>{
				gbStream = stream; 
				const v = document.getElementById('gb_scan_video'); 
				v.srcObject = stream; 
				v.play(); 
				st.textContent='Caméra active — Scannez un code';
				
				// Configuration Quagga optimisée pour meilleure confiance
				const config = {
					inputStream: {
						type: 'LiveStream',
						target: v,
						constraints: {
							width: 640,
							height: 480,
							facingMode: 'environment'
						}
					},
					locator: {
						patchSize: "large",
						halfSample: false
					},
					numOfWorkers: 2,
					frequency: 10,
					decoder: {
						readers: [
							"code_128_reader",
							"ean_reader", 
							"ean_8_reader",
							"code_39_reader",
							"codabar_reader"
						]
					},
					locate: true,
					debug: false
				};
				
				Quagga.init(config, err => {
					if(err){ 
						st.textContent='Erreur scanner: ' + (err.message || err); 
						console.error('Erreur Quagga:', err);
						return;
					} 
					console.log('Scanner initialisé avec succès');
					Quagga.start(); 
				});
				
				// Gestionnaire de détection simplifié
				Quagga.onDetected(res => {
					if(!res || !res.codeResult || !res.codeResult.code) {
						console.log('Détection invalide:', res);
						return;
					}
					
					const code = res.codeResult.code.trim();
					const confidence = res.codeResult.confidence || 0;
					const currentTime = Date.now();
					
					console.log('Code détecté:', code, 'Confiance:', confidence);
					
					// Filtrage très permissif - accepter tous les codes valides
					if(code.length < 3) {
						console.log('Code rejeté - trop court:', code);
						return;
					}
					
					// Log de la confiance mais ne pas rejeter
					console.log('Code accepté - Confiance:', confidence, 'Code:', code);
					
					// Éviter le spam de détections
					if(gbIsProcessing || (currentTime - gbLastProcessTime) < 1500) {
						console.log('Détection ignorée - traitement en cours ou trop rapide');
						return;
					}
					
					// Traitement immédiat pour tester
					gbIsProcessing = true;
					gbLastProcessTime = currentTime;
					
					st.className = 'gb-status gb-success';
					st.textContent = `Code détecté: ${code} - Vérification...`;
					
					console.log('Traitement du code:', code);
					
					// Son de confirmation
					gbBeep();
					
					// Vérifier le produit
					gbCheckCode(code);
					
					// Réinitialiser après 3 secondes
					setTimeout(() => {
						gbIsProcessing = false;
						if(st.textContent.includes('Vérification')) {
							st.className = 'gb-status';
							st.textContent = 'Caméra active — Scannez un code';
						}
					}, 3000);
				});
				
			}).catch(err => { 
				console.error('Erreur caméra:', err);
				st.textContent='Impossible d\'accéder à la caméra: ' + err.message; 
			});
		}
		
		// Fonction pour le son de confirmation
		function gbBeep() {
			try {
				const audioContext = new (window.AudioContext || window.webkitAudioContext)();
				const oscillator = audioContext.createOscillator();
				const gainNode = audioContext.createGain();
				
				oscillator.connect(gainNode);
				gainNode.connect(audioContext.destination);
				
				oscillator.frequency.value = 800;
				oscillator.type = 'sine';
				gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
				gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.1);
				
				oscillator.start(audioContext.currentTime);
				oscillator.stop(audioContext.currentTime + 0.1);
			} catch(e) {
				// Son non disponible, ignorer
			}
		}
		function gbStopCam(){ 
			console.log('Arrêt du scanner...');
			
			// Arrêter la caméra
			if(gbStream){ 
				gbStream.getTracks().forEach(t=>t.stop()); 
				gbStream=null; 
				console.log('Caméra arrêtée');
			} 
			
			// Arrêter Quagga
			if(typeof Quagga!=='undefined'){ 
				try{ 
					Quagga.stop(); 
					console.log('Quagga arrêté');
				}catch(e){
					console.log('Erreur arrêt Quagga:', e);
				} 
			}
			
			// Nettoyer les variables
			gbDetectedCodes = [];
			gbIsProcessing = false;
			gbLastProcessTime = 0;
			
			// Réinitialiser le statut
			const st = document.getElementById('gb_scan_status');
			if(st) {
				st.className = 'gb-status';
				st.textContent = 'Scanner fermé';
			}
		}
		function gbCheckCode(code){ 
			console.log('Vérification du code:', code);
			fetch(`ajax/verifier_produit.php?code=${encodeURIComponent(code)}`)
			.then(r=>r.json())
			.then(d=>{
				console.log('Réponse reçue:', d);
				if(d.existe && d.id){ 
					console.log('Produit trouvé, ouverture du modal d\'ajustement');
					gbCloseScanner(); 
					gbOpenAdjust(d.id);
				} else { 
					console.log('Produit non trouvé');
					document.getElementById('gb_scan_status').textContent='Produit non trouvé: '+code; 
				} 
			})
			.catch((error)=>{ 
				console.error('Erreur:', error);
				document.getElementById('gb_scan_status').textContent='Erreur de vérification'; 
			}); 
		}

		// Mettre à jour une carte de stock dans le modal "Vérifier le stock"
		function gbUpdateStockCard(produitId, nouvelleQuantite, nomProduit) {
			// Chercher la carte correspondante dans le modal stock
			const stockCard = document.querySelector(`#gbStockCards .gb-stock-card[data-id="${produitId}"]`);
			if (stockCard) {
				// Mettre à jour la quantité dans la carte
				const quantityElement = stockCard.querySelector('.gb-stock-qty');
				if (quantityElement) {
					quantityElement.textContent = nouvelleQuantite;
					
					// Mettre à jour la classe de badge selon la quantité
					// On doit récupérer le seuil d'alerte pour déterminer la bonne classe
					const seuilText = stockCard.querySelector('div[style*="color:#9fb3da"]')?.textContent || '';
					const seuilMatch = seuilText.match(/Seuil: (\d+)/);
					const seuil = seuilMatch ? parseInt(seuilMatch[1]) : 5;
					
					// Supprimer les anciennes classes
					quantityElement.classList.remove('gb-badge--danger', 'gb-badge--warn', 'gb-badge--ok');
					
					// Ajouter la nouvelle classe selon la quantité
					if (nouvelleQuantite <= 0) {
						quantityElement.classList.add('gb-badge--danger');
					} else if (nouvelleQuantite <= seuil) {
						quantityElement.classList.add('gb-badge--warn');
					} else {
						quantityElement.classList.add('gb-badge--ok');
					}
				}
				
				// Ajouter un effet visuel pour indiquer la mise à jour
				stockCard.style.transform = 'scale(1.05)';
				stockCard.style.borderColor = '#10b981';
				stockCard.style.boxShadow = '0 0 15px rgba(16, 185, 129, 0.3)';
				
				// Remettre l'état normal après animation
				setTimeout(() => {
					stockCard.style.transform = '';
					stockCard.style.borderColor = '';
					stockCard.style.boxShadow = '';
				}, 1000);
			}
		}
		
		// Variables globales pour le scanner QR
		let gbQrStream = null;
		let gbQrScanner = null;
		
		// Ouvrir le modal scanner QR
		function gbOpenQrScanModal(produitId, quantiteUtilisee) {
			// Récupérer les infos du produit
			const tr = document.querySelector(`#gbTable tbody tr[data-id="${produitId}"]`);
			const productName = tr ? tr.querySelector('td:nth-child(2) b').textContent : 'Produit';
			
			// Remplir les informations
			document.getElementById('gb_qr_product_name').textContent = productName;
			document.getElementById('gb_qr_quantity').textContent = quantiteUtilisee;
			document.getElementById('gb_qr_product_id').value = produitId;
			document.getElementById('gb_qr_quantity_used').value = quantiteUtilisee;
			document.getElementById('gb_qr_original_quantity').value = gbOriginalQuantity;
			
			// Fermer le modal d'ajustement
			gbClose('gbAdjustModal');
			
			// Ouvrir le modal QR
			gbOpen('gbQrScanModal');
			
			// Démarrer le scanner
			gbStartQrScanner();
		}
		
		// Démarrer le scanner QR
		function gbStartQrScanner() {
			const video = document.getElementById('gb_qr_video');
			const status = document.getElementById('gb_qr_status');
			
			status.textContent = 'Démarrage de la caméra...';
			status.className = 'gb-qr-status';
			
			navigator.mediaDevices.getUserMedia({ 
				video: { 
					facingMode: 'environment',
					width: { ideal: 1280 },
					height: { ideal: 720 }
				} 
			})
			.then(stream => {
				gbQrStream = stream;
				video.srcObject = stream;
				
				// Attendre que la vidéo soit prête
				video.onloadedmetadata = () => {
					video.play();
					// Démarrer le scan QR avec jsQR
					gbStartQrScan();
					status.textContent = 'Positionnez le QR code dans le cadre';
				};
			})
			.catch(error => {
				console.error('Erreur caméra:', error);
				status.textContent = 'Erreur: Impossible d\'accéder à la caméra';
				status.className = 'gb-qr-status error';
			});
		}
		
		// Scanner QR avec jsQR (plus efficace pour QR codes)
		function gbStartQrScan() {
			const video = document.getElementById('gb_qr_video');
			const canvas = document.createElement('canvas');
			const context = canvas.getContext('2d');
			
			console.log('Démarrage du scan QR avec jsQR');
			
			function scanFrame() {
				if (video.readyState === video.HAVE_ENOUGH_DATA) {
					canvas.width = video.videoWidth;
					canvas.height = video.videoHeight;
					context.drawImage(video, 0, 0, canvas.width, canvas.height);
					
					const imageData = context.getImageData(0, 0, canvas.width, canvas.height);
					
					// Utiliser jsQR pour décoder le QR code
					if (typeof jsQR !== 'undefined') {
						try {
							const code = jsQR(imageData.data, imageData.width, imageData.height, {
								inversionAttempts: "attemptBoth",
							});
							
							if (code && code.data) {
								console.log('✅ QR Code détecté avec jsQR:', code.data);
								gbProcessQrUrl(code.data);
								return; // Arrêter le scan une fois détecté
							}
						} catch (error) {
							console.error('Erreur jsQR:', error);
						}
					} else {
						console.warn('jsQR non disponible, utilisation du fallback');
						// Fallback sans jsQR - détection basique
						gbBasicQrDetection(imageData);
					}
				}
				
				// Continuer le scan si pas de QR détecté
				if (gbQrScanner) {
					gbQrScanner = requestAnimationFrame(scanFrame);
				}
			}
			
			// Vérifier que jsQR est chargé
			if (typeof jsQR === 'undefined') {
				console.error('jsQR non chargé! Vérifiez la connexion CDN');
				document.getElementById('gb_qr_status').textContent = 'Erreur: Bibliothèque QR non chargée';
				document.getElementById('gb_qr_status').className = 'gb-qr-status error';
			}
			
			gbQrScanner = requestAnimationFrame(scanFrame);
		}
		
		// Détection basique sans jsQR (fallback)
		function gbBasicQrDetection(imageData) {
			// Cette fonction sera utilisée si jsQR n'est pas disponible
			// Pour l'instant, on peut permettre la saisie manuelle
			const status = document.getElementById('gb_qr_status');
			if (!status.textContent.includes('Utilisez la saisie manuelle')) {
				status.textContent = 'Positionnez le QR code dans le cadre ou utilisez la saisie manuelle';
			}
		}
		
		// Traiter l'URL QR scannée
		function gbProcessQrUrl(url) {
			const status = document.getElementById('gb_qr_status');
			
			console.log('URL QR détectée:', url);
			
			// Nettoyer l'URL
			url = url.trim();
			
			// Vérifier si c'est une URL de réparation valide
			const isValidRepairUrl = (
				url.includes('mdgeek.top') && 
				url.includes('statut_rapide') && 
				url.includes('id=')
			) || url.match(/[?&]id=\d+/); // Accepter aussi les URLs avec juste id=
			
			if (!isValidRepairUrl) {
				status.textContent = 'QR Code invalide - URL de réparation non reconnue';
				status.className = 'gb-qr-status error';
				console.log('URL invalide:', url);
				return;
			}
			
			// Extraire l'ID de réparation de l'URL
			const match = url.match(/[?&]id=(\d+)/);
			if (match) {
				const reparationId = match[1];
				console.log('ID réparation extrait:', reparationId);
				
				status.textContent = `✅ QR Code détecté! Réparation #${reparationId}`;
				status.className = 'gb-qr-status success';
				
				// Arrêter le scanner
				gbStopQrScanner();
				
				// Enregistrer l'utilisation de la pièce
				setTimeout(() => {
					gbRecordPieceUsage(reparationId);
				}, 1000);
			} else {
				status.textContent = 'QR Code invalide - ID de réparation non trouvé';
				status.className = 'gb-qr-status error';
				console.log('Impossible d\'extraire l\'ID de:', url);
			}
		}
		
		// Enregistrer l'utilisation de la pièce
		function gbRecordPieceUsage(reparationId) {
			const produitId = document.getElementById('gb_qr_product_id').value;
			const quantiteUtilisee = document.getElementById('gb_qr_quantity_used').value;
			
			const formData = new FormData();
			formData.append('reparation_id', reparationId);
			formData.append('produit_id', produitId);
			formData.append('quantite_utilisee', quantiteUtilisee);
			formData.append('nouvelle_quantite', gbCurrentQuantity);
			formData.append('ancienne_quantite', gbOriginalQuantity);
			
			fetch('ajax/enregistrer_piece_utilisee.php', {
				method: 'POST',
				body: formData
			})
			.then(response => response.json())
			.then(data => {
				if (data.success) {
					gbClose('gbQrScanModal');
					gbShowToast('✅ Pièce enregistrée pour la réparation #' + reparationId, 'success');
					
					// Recharger la page pour mettre à jour le stock
					setTimeout(() => {
						location.reload();
					}, 1500);
				} else {
					document.getElementById('gb_qr_status').textContent = 'Erreur: ' + data.message;
					document.getElementById('gb_qr_status').className = 'gb-qr-status error';
				}
			})
			.catch(error => {
				console.error('Erreur:', error);
				document.getElementById('gb_qr_status').textContent = 'Erreur de connexion';
				document.getElementById('gb_qr_status').className = 'gb-qr-status error';
			});
		}
		
		// Arrêter le scanner QR
		function gbStopQrScanner() {
			// Arrêter l'animation frame
			if (gbQrScanner) {
				cancelAnimationFrame(gbQrScanner);
				gbQrScanner = null;
			}
			
			// Arrêter le stream vidéo
			if (gbQrStream) {
				gbQrStream.getTracks().forEach(track => track.stop());
				gbQrStream = null;
			}
			
			// Arrêter Quagga si il était utilisé
			if (typeof Quagga !== 'undefined' && Quagga.stop) {
				try {
					Quagga.stop();
				} catch (e) {
					console.log('Quagga déjà arrêté');
				}
			}
		}
		
		// Annuler le scan QR
		function gbCancelQrScan() {
			gbStopQrScanner();
			gbClose('gbQrScanModal');
			gbOpen('gbAdjustModal'); // Revenir au modal d'ajustement
		}
		
		// Saisie manuelle de l'ID de réparation
		function gbManualReparationId() {
			const reparationId = prompt('Entrez l\'ID de la réparation:');
			if (reparationId && /^\d+$/.test(reparationId)) {
				gbStopQrScanner();
				gbRecordPieceUsage(reparationId);
			} else if (reparationId !== null) {
				alert('ID de réparation invalide');
			}
		}
		
		// Fonction de test QR (temporaire)
		function gbTestQrUrl() {
			console.log('Test QR déclenché');
			gbProcessQrUrl('https://mkmkmk.mdgeek.top/index.php?page=statut_rapide&id=1');
		}
		
		// Variables globales pour le modal partenaire
		let gbPartnerData = {
			productId: null,
			productName: '',
			quantityUsed: 0,
			originalQuantity: 0,
			unitPrice: 0
		};
		
		// Ouvrir le modal de sélection partenaire
		function gbOpenPartnerModal() {
			console.log('🤝 Ouverture modal partenaire');
			
			// Récupérer les données du produit depuis le modal QR
			const productId = document.getElementById('gb_qr_product_id').value;
			const productName = document.getElementById('gb_qr_product_name').textContent;
			const quantityUsed = parseInt(document.getElementById('gb_qr_quantity_used').value);
			const originalQuantity = parseInt(document.getElementById('gb_qr_original_quantity').value);
			
			// Stocker les données
			gbPartnerData.productId = productId;
			gbPartnerData.productName = productName;
			gbPartnerData.quantityUsed = quantityUsed;
			gbPartnerData.originalQuantity = originalQuantity;
			
			// Récupérer le prix du produit
			gbGetProductPrice(productId).then(price => {
				gbPartnerData.unitPrice = price;
				
				// Remplir le modal
				document.getElementById('gb_partner_product_name').textContent = productName;
				document.getElementById('gb_partner_quantity').textContent = quantityUsed;
				document.getElementById('gb_partner_price').textContent = price.toFixed(2) + '€';
				document.getElementById('gb_partner_total').textContent = (price * quantityUsed * 1.2).toFixed(2) + '€';
				
				// Remplir les champs cachés
				document.getElementById('gb_partner_product_id').value = productId;
				document.getElementById('gb_partner_quantity_used').value = quantityUsed;
				document.getElementById('gb_partner_original_quantity').value = originalQuantity;
				document.getElementById('gb_partner_unit_price').value = price;
				
				// Charger la liste des partenaires
				gbLoadPartners();
				
				// Fermer le modal QR et ouvrir le modal partenaire
				gbStopQrScanner();
				gbClose('gbQrScanModal');
				gbOpen('gbPartnerModal');
			}).catch(error => {
				console.error('Erreur récupération prix:', error);
				gbShowToast('❌ Erreur lors de la récupération du prix du produit', 'error');
			});
		}
		
		// Récupérer le prix d'un produit
		async function gbGetProductPrice(productId) {
			try {
				const response = await fetch('ajax/get_product_price.php', {
					method: 'POST',
					headers: {
						'Content-Type': 'application/x-www-form-urlencoded',
					},
					body: `product_id=${productId}`
				});
				
				const data = await response.json();
				if (data.success) {
					return parseFloat(data.price);
				} else {
					throw new Error(data.message || 'Erreur récupération prix');
				}
			} catch (error) {
				console.error('Erreur AJAX prix:', error);
				throw error;
			}
		}
		
		// Charger la liste des partenaires
		async function gbLoadPartners() {
			const partnerList = document.getElementById('gb_partner_list');
			
			try {
				const response = await fetch('ajax/get_partners.php', {
					method: 'GET',
					headers: {
						'Content-Type': 'application/json',
					}
				});
				
				const data = await response.json();
				if (data.success) {
					gbDisplayPartners(data.partners);
				} else {
					partnerList.innerHTML = `
						<div class="gb-partner-loading" style="color: #ef4444;">
							<i class="fas fa-exclamation-triangle"></i>
							Erreur: ${data.message}
						</div>
					`;
				}
			} catch (error) {
				console.error('Erreur chargement partenaires:', error);
				partnerList.innerHTML = `
					<div class="gb-partner-loading" style="color: #ef4444;">
						<i class="fas fa-exclamation-triangle"></i>
						Erreur de connexion
					</div>
				`;
			}
		}
		
		// Afficher la liste des partenaires
		function gbDisplayPartners(partners) {
			const partnerList = document.getElementById('gb_partner_list');
			
			if (partners.length === 0) {
				partnerList.innerHTML = `
					<div class="gb-partner-loading">
						<i class="fas fa-info-circle"></i>
						Aucun partenaire actif trouvé
					</div>
				`;
				return;
			}
			
			let html = '';
			partners.forEach(partner => {
				html += `
					<div class="gb-partner-item" onclick="gbSelectPartner(${partner.id}, '${partner.nom.replace(/'/g, "\\'")}')">
						<div>
							<div class="gb-partner-name">${partner.nom}</div>
							${partner.email ? `<div class="gb-partner-contact">${partner.email}</div>` : ''}
							${partner.telephone ? `<div class="gb-partner-contact">${partner.telephone}</div>` : ''}
						</div>
						<button class="gb-partner-select-btn" onclick="event.stopPropagation(); gbSelectPartner(${partner.id}, '${partner.nom.replace(/'/g, "\\'")}')">
							<i class="fas fa-check"></i>
							Sélectionner
						</button>
					</div>
				`;
			});
			
			partnerList.innerHTML = html;
		}
		
		// Sélectionner un partenaire
		async function gbSelectPartner(partnerId, partnerName) {
			console.log(`🤝 Partenaire sélectionné: ${partnerName} (ID: ${partnerId})`);
			
			// Confirmer la sélection
			const confirmed = confirm(`Confirmer la transaction avec ${partnerName} ?\n\nMontant: ${(gbPartnerData.unitPrice * gbPartnerData.quantityUsed * 1.2).toFixed(2)}€\nProduit: ${gbPartnerData.productName}\nQuantité: ${gbPartnerData.quantityUsed}`);
			
			if (!confirmed) {
				return;
			}
			
			// Créer la transaction
			try {
				const response = await fetch('ajax/create_partner_transaction.php', {
					method: 'POST',
					headers: {
						'Content-Type': 'application/x-www-form-urlencoded',
					},
					body: new URLSearchParams({
						partner_id: partnerId,
						product_id: gbPartnerData.productId,
						quantity_used: gbPartnerData.quantityUsed,
						unit_price: gbPartnerData.unitPrice,
						original_quantity: gbPartnerData.originalQuantity,
						new_quantity: gbPartnerData.originalQuantity - gbPartnerData.quantityUsed
					})
				});
				
				const data = await response.json();
				if (data.success) {
					gbClose('gbPartnerModal');
					gbShowToast(`✅ Transaction créée avec ${partnerName} - ${data.montant}€`, 'success');
					
					// Recharger la page pour mettre à jour le stock
					setTimeout(() => {
						location.reload();
					}, 1500);
				} else {
					gbShowToast('❌ Erreur: ' + data.message, 'error');
				}
			} catch (error) {
				console.error('Erreur création transaction:', error);
				gbShowToast('❌ Erreur lors de la création de la transaction', 'error');
			}
		}
		
		// Annuler la sélection partenaire
		function gbCancelPartnerSelection() {
			gbClose('gbPartnerModal');
			gbOpen('gbQrScanModal'); // Revenir au modal QR
			gbStartQrScanner(); // Redémarrer le scanner
		}

		// Toast notification system
		function gbShowToast(message, type = 'info') {
			// Supprimer les anciens toasts
			const existingToasts = document.querySelectorAll('.gb-toast');
			existingToasts.forEach(toast => toast.remove());
			
			// Créer le toast
			const toast = document.createElement('div');
			toast.className = `gb-toast gb-toast--${type}`;
			toast.textContent = message;
			
			// Styles inline pour éviter les conflits
			toast.style.cssText = `
				position: fixed;
				top: 20px;
				right: 20px;
				background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#3b82f6'};
				color: white;
				padding: 12px 20px;
				border-radius: 8px;
				box-shadow: 0 4px 12px rgba(0,0,0,0.15);
				z-index: 100000;
				font-weight: 500;
				font-size: 14px;
				animation: gbToastSlide 0.3s ease;
				max-width: 300px;
			`;
			
			document.body.appendChild(toast);
			
			// Supprimer après 3 secondes
			setTimeout(() => {
				toast.style.opacity = '0';
				toast.style.transform = 'translateX(100%)';
				setTimeout(() => toast.remove(), 300);
			}, 3000);
		}
	</script>

	</div> <!-- Fermeture de gb-container -->

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
	.gb-container.fade-in {
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
	</style>

	<script>
	document.addEventListener('DOMContentLoaded', function() {
		const loader = document.getElementById('pageLoader');
		const mainContent = document.getElementById('mainContent');
		
		// Attendre 0,5 seconde puis masquer le loader et afficher le contenu
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
		
		// Vérifier si on doit ouvrir le modal d'ajout avec un code pré-rempli
		const urlParams = new URLSearchParams(window.location.search);
		if (urlParams.get('add_product') === '1' && urlParams.get('reference')) {
			const reference = urlParams.get('reference');
			console.log('📦 [INVENTAIRE] Ouverture automatique du modal d\'ajout avec référence:', reference);
			
			// Attendre que le contenu soit chargé puis ouvrir le modal
			setTimeout(() => {
				gbOpen('gbAddModal');
				
				// Pré-remplir le champ référence
				setTimeout(() => {
					const referenceField = document.querySelector('input[name="reference"]');
					if (referenceField) {
						referenceField.value = reference;
						referenceField.focus();
						console.log('✅ [INVENTAIRE] Champ référence pré-rempli avec:', reference);
					}
				}, 300);
			}, 1000);
			
			// Nettoyer l'URL pour éviter de rouvrir le modal au refresh
			const cleanUrl = window.location.pathname + '?page=inventaire';
			window.history.replaceState({}, document.title, cleanUrl);
		}
	});
	</script>
</body>
</html>
