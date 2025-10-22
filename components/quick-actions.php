<?php
/**
 * Composant des boutons d'action rapide
 * Utilise les styles définis dans dashboard-new.css
 */
?>

<div class="quick-actions-grid futuristic-action-grid">
    <!-- Rechercher -->
    <a href="#" class="action-card action-primary futuristic-action-btn action-search" onclick="ouvrirRechercheModerne()">
        <div class="action-icon">
            <i class="fas fa-search"></i>
        </div>
        <div class="action-text">Rechercher</div>
    </a>

    <!-- Nouvelle tâche -->
    <a href="index.php?page=ajouter_tache" class="action-card action-info futuristic-action-btn action-task" data-bs-toggle="modal" data-bs-target="#ajouterTacheModal" onclick="event.preventDefault();">
        <div class="action-icon">
            <i class="fas fa-tasks"></i>
        </div>
        <div class="action-text">Nouvelle tâche</div>
    </a>

    <!-- Nouvelle réparation -->
    <a href="index.php?page=ajouter_reparation" class="action-card action-success futuristic-action-btn action-repair">
        <div class="action-icon">
            <i class="fas fa-tools"></i>
        </div>
        <div class="action-text">Nouvelle réparation</div>
    </a>

    <!-- Nouvelle commande -->
    <a href="#" class="action-card action-warning futuristic-action-btn action-order" data-bs-toggle="modal" data-bs-target="#ajouterCommandeModal">
        <div class="action-icon">
            <i class="fas fa-shopping-cart"></i>
        </div>
        <div class="action-text">Nouvelle commande</div>
    </a>
</div>


<!-- Modal: Ajouter Tâche (charge la page dans un iframe pour apparence identique) -->
<div class="modal fade" id="ajouterTacheModal" tabindex="-1" aria-labelledby="ajouterTacheModalLabel" aria-hidden="true" data-bs-backdrop="false">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="ajouterTacheModalLabel">
                    <i class="fas fa-tasks me-2"></i> Nouvelle tâche
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0" style="height: 85vh;">
                <iframe id="ajouterTacheIframe" src="about:blank" data-src="/index.php?page=ajouter_tache&modal=1" style="border:0;width:100%;height:100%;"></iframe>
            </div>
        </div>
    </div>
    </div>

<script>
(function(){
    var modalEl = document.getElementById('ajouterTacheModal');
    var trigger = document.querySelector('[data-bs-target="#ajouterTacheModal"]');
    var iframe = document.getElementById('ajouterTacheIframe');

    function ensureIframeLoaded(){
        if (!iframe) return;
        var targetSrc = iframe.getAttribute('data-src') || (window.location.origin + '/index.php?page=ajouter_tache&modal=1');
        // Force reload each open to avoid caching issues; server will redirect to login_auto if nécessaire
        var finalSrc = targetSrc + (targetSrc.indexOf('?') === -1 ? '?' : '&') + '_=' + Date.now();
        iframe.removeAttribute('srcdoc');
        iframe.src = finalSrc;
    }

    if (trigger) {
        trigger.addEventListener('click', function(e){
            e.preventDefault();
            ensureIframeLoaded();
        });
    }

    if (modalEl) {
        modalEl.addEventListener('show.bs.modal', ensureIframeLoaded);
        modalEl.addEventListener('shown.bs.modal', ensureIframeLoaded);
    }
})();
</script>




