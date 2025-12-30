// Fonction pour ouvrir le modal de détails rapides de réparation
window.openQuickRepairModal = function (event, repairId) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }

    // Vérifier si Bootstrap est disponible
    if (typeof bootstrap === 'undefined') {
        alert('Erreur: Bootstrap n\'est pas chargé.');
        return;
    }

    const modalElement = document.getElementById('quickRepairDetailsModal');
    if (!modalElement) {
        console.error('Modal quickRepairDetailsModal not found!');
        return;
    }

    // Réinitialiser le contenu
    document.getElementById('quickRepairLoading').style.display = 'block';
    document.getElementById('quickRepairContent').style.display = 'none';
    document.getElementById('quickRepairError').style.display = 'none';

    // Ouvrir le modal
    const modal = new bootstrap.Modal(modalElement);
    modal.show();

    // Charger les données
    fetch(`/ajax/get_repair_details.php?id=${repairId}`)
        .then(response => response.json())
        .then(data => {
            if (!data.success) throw new Error(data.message);

            const repair = data.data;

            // Remplir les champs
            document.getElementById('qrClientName').textContent = `${repair.client_prenom} ${repair.client_nom}`;
            document.getElementById('qrRepairId').textContent = `#${repair.id}`;
            document.getElementById('qrModel').textContent = repair.modele;
            document.getElementById('qrProblem').textContent = repair.description_probleme;
            document.getElementById('qrStatus').textContent = repair.statut_label;

            // Note interne
            const noteElement = document.getElementById('qrInternalNote');
            if (repair.notes_internes) {
                noteElement.textContent = repair.notes_internes;
                noteElement.parentElement.style.display = 'block';
            } else {
                noteElement.parentElement.style.display = 'none';
            }

            // Photo
            const photoContainer = document.getElementById('qrPhotoContainer');
            const photoElement = document.getElementById('qrPhoto');
            if (repair.photo_url) {
                photoElement.src = repair.photo_url;
                photoContainer.style.display = 'block';
            } else {
                photoContainer.style.display = 'none';
            }

            // Afficher le contenu
            document.getElementById('quickRepairLoading').style.display = 'none';
            document.getElementById('quickRepairContent').style.display = 'block';

            // Bouton Voir détails complets
            document.getElementById('qrFullDetailsBtn').onclick = function () {
                window.location.href = `?page=details_reparation&id=${repair.id}`;
            };
        })
        .catch(error => {
            console.error('Erreur:', error);
            document.getElementById('quickRepairLoading').style.display = 'none';
            document.getElementById('quickRepairError').style.display = 'block';
            document.getElementById('quickRepairErrorMessage').textContent = error.message;
        });
};

console.log('✅ quick-repair-modal.js loaded successfully');
