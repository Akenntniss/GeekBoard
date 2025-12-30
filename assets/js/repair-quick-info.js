// Fonction pour ouvrir le modal d'infos rapides d'une réparation
window.openRepairQuickInfo = function (repairId) {
    if (!repairId) {
        console.error('Repair ID is required');
        return;
    }

    // Ouvrir le modal
    const modalElement = document.getElementById('repairQuickInfoModal');
    if (!modalElement) {
        console.error('Modal repairQuickInfoModal not found');
        return;
    }

    const modal = new bootstrap.Modal(modalElement);
    modal.show();

    // Afficher le loading
    document.getElementById('repairQuickInfoLoading').style.display = 'block';
    document.getElementById('repairQuickInfoContent').style.display = 'none';
    document.getElementById('repairQuickInfoError').style.display = 'none';

    // Charger les données
    fetch(`/ajax/get_repair_quick_info.php?id=${repairId}`)
        .then(response => response.json())
        .then(data => {
            if (!data.success) throw new Error(data.message);

            // Cacher le loading
            document.getElementById('repairQuickInfoLoading').style.display = 'none';
            document.getElementById('repairQuickInfoContent').style.display = 'block';

            // Remplir les données
            document.getElementById('repairClientName').textContent = data.data.client_name || '-';
            document.getElementById('repairModel').textContent = data.data.model || '-';
            document.getElementById('repairProblem').textContent = data.data.problem || '-';

            // Statut
            const statusBadge = document.getElementById('repairStatus');
            statusBadge.textContent = data.data.status_label;
            statusBadge.className = `badge bg-${data.data.status_color}`;

            // Note interne
            document.getElementById('repairNote').textContent = data.data.note || 'Aucune note interne';

            // Photo
            if (data.data.photo) {
                document.getElementById('repairPhoto').src = data.data.photo;
                document.getElementById('repairPhoto').style.display = 'block';
                document.getElementById('repairNoPhoto').style.display = 'none';
            } else {
                document.getElementById('repairPhoto').style.display = 'none';
                document.getElementById('repairNoPhoto').style.display = 'block';
            }

            // Lien détails complets
            document.getElementById('repairDetailsLink').href = `?page=details_reparation&id=${data.data.id}`;
        })
        .catch(error => {
            console.error('Error loading repair info:', error);
            document.getElementById('repairQuickInfoLoading').style.display = 'none';
            document.getElementById('repairQuickInfoError').style.display = 'block';
            document.getElementById('repairQuickInfoErrorMessage').textContent = error.message;
        });
};

console.log('✅ repair-quick-info.js loaded successfully');
