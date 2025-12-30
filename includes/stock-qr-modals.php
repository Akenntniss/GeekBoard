<?php
/**
 * Modals pour le Workflow QR Scanner + Stock Adjustment
 * À inclure dans les pages pour le workflow global
 */
?>

<!-- Modal Raison Sortie (Choix entre Partenaire ou Autre) -->
<div class="modern-modal" id="gbReasonModal" style="z-index: 1060;">
    <div class="modern-modal-dialog">
        <div class="modern-modal-header" style="background: linear-gradient(135deg, #f59e0b, #d97706); color: white;">
            <h3 class="modern-modal-title">📉 Motif de Sortie</h3>
        </div>
        <div class="modern-modal-body text-center">
            <p class="mb-4">Vous réduisez le stock. Quelle est la raison ?</p>
            
            <div class="d-grid gap-3">
                <button class="btn btn-lg btn-outline-primary" onclick="gbOpenPartnerSelect()" style="border-radius: 15px; padding: 15px;">
                    <i class="fas fa-handshake fa-2x mb-2 d-block"></i>
                    Prêt / Transaction Partenaire
                </button>
                
                <button class="btn btn-lg btn-outline-secondary" onclick="gbOpenOtherReason()" style="border-radius: 15px; padding: 15px;">
                    <i class="fas fa-pen fa-2x mb-2 d-block"></i>
                    Autre (Casse, Perte...)
                </button>
            </div>
        </div>
        <div class="modern-modal-footer">
            <button class="modern-btn" onclick="gbClose('gbReasonModal')">Annuler</button>
        </div>
    </div>
</div>

<!-- Modal Sélection Partenaire -->
<div class="modern-modal" id="gbPartnerSelectModal" style="z-index: 1070;">
    <div class="modern-modal-dialog">
        <div class="modern-modal-header" style="background: linear-gradient(135deg, #10b981, #059669); color: white;">
            <h3 class="modern-modal-title">🤝 Sélection Partenaire</h3>
        </div>
        <div class="modern-modal-body">
            <div class="mb-3">
                <label class="form-label">Partenaire</label>
                <select id="gb_partner_select" class="form-select form-select-lg">
                    <option value="">Chargement...</option>
                </select>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Type de Transaction</label>
                <div class="btn-group w-100" role="group">
                    <input type="radio" class="btn-check" name="gb_trans_type" id="gb_type_avance" value="AVANCE" checked>
                    <label class="btn btn-outline-success" for="gb_type_avance">Prêt de pièce (Avance)</label>

                    <input type="radio" class="btn-check" name="gb_trans_type" id="gb_type_remboursement" value="REMBOURSEMENT">
                    <label class="btn btn-outline-danger" for="gb_type_remboursement">Retour de pièce (Remboursement)</label>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Valeur de la pièce (€)</label>
                <div class="input-group input-group-lg">
                    <span class="input-group-text">€</span>
                    <input type="number" id="gb_partner_amount" class="form-control" placeholder="0.00" step="0.01">
                </div>
            </div>
        </div>
        <div class="modern-modal-footer">
            <button class="modern-btn" onclick="gbClose('gbPartnerSelectModal')">Retour</button>
            <button class="modern-btn modern-btn--success" onclick="gbConfirmPartnerTransaction()">
                Valider Transaction
            </button>
        </div>
    </div>
</div>

<!-- Modal Raison Autre -->
<div class="modern-modal" id="gbOtherReasonModal" style="z-index: 1070;">
    <div class="modern-modal-dialog">
        <div class="modern-modal-header" style="background: linear-gradient(135deg, #6b7280, #4b5563); color: white;">
            <h3 class="modern-modal-title">📝 Préciser le motif</h3>
        </div>
        <div class="modern-modal-body">
            <div class="mb-3">
                <label class="form-label">Raison de la sortie</label>
                <textarea id="gb_other_reason_text" class="form-control" rows="3" placeholder="Ex: Casse lors du montage, Perdu..."></textarea>
            </div>
        </div>
        <div class="modern-modal-footer">
            <button class="modern-btn" onclick="gbClose('gbOtherReasonModal')">Retour</button>
            <button class="modern-btn modern-btn--primary" onclick="gbConfirmOtherReason()">
                Valider
            </button>
        </div>
    </div>
</div>

<!-- Modal Ajustement Stock -->
<div class="modern-modal" id="gbAdjustModal">
    <div class="modern-modal-dialog adjust-modal">
        <div class="modern-modal-header" style="background: linear-gradient(135deg, #667eea, #764ba2); color: white; border-radius: 20px 20px 0 0;">
            <h3 class="modern-modal-title" style="color: white; margin-bottom: 0;" id="gb_adjust_name">Produit</h3>
        </div>
        <div class="modern-modal-body">
            <div class="adjust-product-info">
                <div class="adjust-product-ref" id="gb_adjust_ref">REF-000</div>
                <div style="color: #94a3b8; font-size: 0.9rem;">Stock actuel</div>
            </div>
            
            <div class="adjust-controls">
                <button class="adjust-btn adjust-btn--minus" onclick="gbDecreaseQuantity()" type="button">
                    <i class="fas fa-minus"></i>
                </button>
                
                <div class="adjust-display">
                    <div class="adjust-value" id="gb_adjust_current">0</div>
                    <div class="adjust-unit">unités</div>
                </div>
                
                <button class="adjust-btn adjust-btn--plus" onclick="gbIncreaseQuantity()" type="button">
                    <i class="fas fa-plus"></i>
                </button>
            </div>
            
            <!-- Champs cachés -->
            <input type="hidden" id="gb_adjust_id" />
            <input type="hidden" id="gb_adjust_original" />
            <input type="hidden" id="gb_adjust_new" />
            
            <div class="modern-form-actions">
                <button class="modern-btn" style="background: #6b7280; color: white;" onclick="gbClose('gbAdjustModal')" type="button">
                    <i class="fas fa-times"></i>
                    Annuler
                </button>
                <button class="modern-btn modern-btn--success" onclick="gbUpdateStock()" type="button">
                    <i class="fas fa-check"></i>
                    Confirmer
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Scanner QR pour Réparations -->
<div class="modern-modal" id="gbQRRepairModal">
    <div class="modern-modal-dialog">
        <div class="modern-modal-header" style="background: linear-gradient(135deg, #8b5cf6, #6366f1); color: white;">
            <h3 class="modern-modal-title" style="color: white;">
                <i class="fas fa-qrcode"></i>
                Scanner QR Code Réparation
            </h3>
            <button class="modern-modal-close" onclick="gbCloseQRScanner()" type="button">×</button>
        </div>
        <div class="modern-modal-body">
            <!-- Zone scanner QR -->
            <div id="gb_qr_scan_area" style="height:320px; background:#000; border-radius:15px; overflow:hidden; position:relative; margin-bottom: 1rem;">
                <video id="gb_qr_video" style="width:100%; height:100%; object-fit:cover;"></video>
                <canvas id="gb_qr_canvas" style="display:none;"></canvas>
                <div style="position:absolute; left:50%; top:50%; width:200px; height:200px; transform:translate(-50%,-50%); border:3px solid #10b981; box-shadow:0 0 0 4px rgba(16,185,129,0.2); border-radius:15px;"></div>
            </div>
            
            <div id="gb_qr_status" style="text-align:center; padding:1rem; background:rgba(139,92,246,0.1); border-radius:10px; margin-bottom:1rem; color:#8b5cf6; font-weight:600;">
                📱 Scannez le QR code de la réparation...
            </div>
            
            <!-- Bouton manuel -->
            <div class="modern-form-actions">
                <button class="modern-btn modern-btn--secondary" onclick="gbSkipQRScan()" type="button">
                    <i class="fas fa-edit"></i>
                    AJUSTER LE STOCK (Sans Réparation)
                </button>
            </div>
        </div>
    </div>
</div>
