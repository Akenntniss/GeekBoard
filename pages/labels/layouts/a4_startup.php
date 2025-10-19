<?php
/**
 * Layout Startup - Format A4
 * Design dynamique et créatif - AVEC COULEURS
 */
$date_reception = date('d/m/Y', strtotime($reparation['date_reception']));
?>
<style>
@page { size: A4; margin: 0; }
body { width: 210mm; height: 297mm; margin: 0; padding: 0; font-family: 'Arial', sans-serif; background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); }
.label-a4-startup { background: white; margin: 12mm; padding: 0; border-radius: 25px; overflow: hidden; box-shadow: 0 15px 50px rgba(0,0,0,0.2); position: relative; }
.corner-accent { position: absolute; width: 80px; height: 80px; }
.corner-tl { top: 0; left: 0; background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); clip-path: polygon(0 0, 100% 0, 0 100%); }
.corner-br { bottom: 0; right: 0; background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); clip-path: polygon(100% 0, 100% 100%, 0 100%); }
.header-startup { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); padding: 40px; text-align: center; position: relative; }
.brand-startup { font-size: 48px; font-weight: 900; color: white; margin: 0; text-shadow: 2px 2px 4px rgba(0,0,0,0.3); letter-spacing: 3px; }
.id-circle { display: inline-block; background: white; color: #fa709a; width: 120px; height: 120px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-direction: column; font-weight: bold; margin-top: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
.id-number { font-size: 32px; }
.id-label { font-size: 12px; text-transform: uppercase; }
.content-startup { padding: 35px; }
.card-startup { background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); padding: 20px; border-radius: 20px; margin-bottom: 20px; border-left: 6px solid #fa709a; transform: skewY(-1deg); }
.card-startup-inner { transform: skewY(1deg); }
.card-title { font-size: 14px; font-weight: bold; color: #fa709a; text-transform: uppercase; margin-bottom: 8px; }
.card-value { font-size: 18px; font-weight: 600; color: #333; }
.grid-startup { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
.problem-card { background: linear-gradient(135deg, #ffeaa7 0%, #fdcb6e 100%); padding: 25px; border-radius: 20px; margin: 20px 0; min-height: 120px; transform: skewY(1deg); }
.qr-section-startup { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); padding: 35px; text-align: center; color: white; }
</style>

<div class="label-a4-startup">
    <div class="corner-accent corner-tl"></div>
    <div class="corner-accent corner-br"></div>
    
    <div class="header-startup">
        <div class="brand-startup">MAISON DU GEEK</div>
        <div class="id-circle">
            <div class="id-number">#<?php echo $reparation['id']; ?></div>
            <div class="id-label"><?php echo htmlspecialchars($reparation['statut']); ?></div>
        </div>
    </div>
    
    <div class="content-startup">
        <div class="card-startup">
            <div class="card-startup-inner">
                <div class="card-title">👤 Client</div>
                <div class="card-value"><?php echo htmlspecialchars($reparation['client_nom'] . ' ' . $reparation['client_prenom']); ?></div>
                <div style="font-size: 16px; color: #666; margin-top: 5px;">📞 <?php echo htmlspecialchars($reparation['client_telephone']); ?></div>
            </div>
        </div>
        
        <div class="grid-startup">
            <div class="card-startup">
                <div class="card-startup-inner">
                    <div class="card-title">📱 Appareil</div>
                    <div class="card-value" style="font-size: 16px;"><?php echo htmlspecialchars($reparation['type_appareil']); ?></div>
                    <div style="font-size: 14px; color: #666; margin-top: 3px;"><?php echo htmlspecialchars($reparation['modele']); ?></div>
                </div>
            </div>
            
            <div class="card-startup">
                <div class="card-startup-inner">
                    <div class="card-title">📅 Date</div>
                    <div class="card-value"><?php echo $date_reception; ?></div>
                </div>
            </div>
            
            <div class="card-startup">
                <div class="card-startup-inner">
                    <div class="card-title">🔐 Code</div>
                    <div class="card-value" style="color: #e74c3c;"><?php echo !empty($reparation['mot_de_passe']) ? htmlspecialchars($reparation['mot_de_passe']) : 'N/A'; ?></div>
                </div>
            </div>
            
            <div class="card-startup">
                <div class="card-startup-inner">
                    <div class="card-title">💰 Prix</div>
                    <div class="card-value" style="color: #27ae60;"><?php echo !empty($reparation['prix_reparation']) ? number_format($reparation['prix_reparation'], 2, ',', ' ') . ' €' : 'À définir'; ?></div>
                </div>
            </div>
        </div>
        
        <div class="problem-card">
            <div style="transform: skewY(-1deg);">
                <div class="card-title" style="color: #d63031;">⚠️ Problème Signalé</div>
                <div style="font-size: 16px; line-height: 1.6; margin-top: 10px; color: #333;">
                    <?php echo nl2br(htmlspecialchars($reparation['description_probleme'])); ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="qr-section-startup">
        <div style="background: white; display: inline-block; padding: 20px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.2);">
            <div id="qrcode_a4_startup"></div>
        </div>
        <div style="font-size: 22px; font-weight: bold; margin-top: 20px; text-shadow: 2px 2px 4px rgba(0,0,0,0.2);">
            SCAN ME! 🚀
        </div>
        <div style="font-size: 14px; margin-top: 8px; opacity: 0.9;">
            Suivez votre réparation en temps réel
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    new QRCode(document.getElementById("qrcode_a4_startup"), {
        text: window.location.origin + '/index.php?page=statut_rapide&id=<?php echo $reparation['id']; ?>',
        width: 145, height: 145, colorDark: "#000000", colorLight: "#ffffff", correctLevel: QRCode.CorrectLevel.H
    });
});
</script>

