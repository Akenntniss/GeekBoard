/**
 * LOGIQUE DU NOUVEAU MODAL TÂCHE
 * Handles interactions, file uploads, and submission
 */

document.addEventListener('DOMContentLoaded', function () {
    initNewTaskModal();
});

function initNewTaskModal() {
    const modal = document.getElementById('ajouterTacheModal');
    if (!modal) return;

    const dropZone = document.getElementById('taskDropZone');
    const fileInput = document.getElementById('taskAttachments');
    const saveBtn = document.getElementById('btnSaveTask');

    // 1. File Upload Handling
    if (dropZone && fileInput) {
        dropZone.addEventListener('click', () => fileInput.click());

        dropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropZone.style.borderColor = getComputedStyle(document.documentElement).getPropertyValue('--accent-color-day');
            dropZone.style.background = 'rgba(13, 110, 253, 0.1)';
        });

        dropZone.addEventListener('dragleave', (e) => {
            e.preventDefault();
            dropZone.style.borderColor = '';
            dropZone.style.background = '';
        });

        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.style.borderColor = '';
            dropZone.style.background = '';

            if (e.dataTransfer.files.length) {
                fileInput.files = e.dataTransfer.files;
                updateFileList(fileInput);
            }
        });

        fileInput.addEventListener('change', () => updateFileList(fileInput));
    }

    // 2. Save Button Handling
    if (saveBtn) {
        saveBtn.addEventListener('click', submitNewTask);
    }
}

// Priority Setter
window.setTaskPriority = function (priority) {
    document.getElementById('taskPriority').value = priority;

    // Update UI
    document.querySelectorAll('.priority-btn').forEach(btn => {
        btn.classList.remove('active');
        if (btn.dataset.value === priority) {
            btn.classList.add('active');
        }
    });
};

// Update File List UI
function updateFileList(input) {
    const list = document.getElementById('taskFileList');
    list.innerHTML = '';

    Array.from(input.files).forEach(file => {
        const item = document.createElement('div');
        item.className = 'd-flex align-items-center justify-content-between p-2 mb-2 border rounded';
        item.style.background = 'rgba(255,255,255,0.05)';
        item.innerHTML = `
            <span><i class="fas fa-paperclip me-2"></i>${file.name}</span>
            <small class="text-muted">${(file.size / 1024).toFixed(1)} KB</small>
        `;
        list.appendChild(item);
    });
}

// Submit Function
function submitNewTask() {
    const form = document.getElementById('taskForm');
    const formData = new FormData(form);
    const saveBtn = document.getElementById('btnSaveTask');

    // Validation
    if (!formData.get('titre') || !formData.get('description')) {
        alert('Veuillez remplir le titre et la description.');
        return;
    }

    // Loading State
    const originalBtnContent = saveBtn.innerHTML;
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Enregistrement...';
    saveBtn.disabled = true;

    // Add Shop ID - L'API détecte automatiquement le magasin via le sous-domaine
    const url = 'ajax_simple_no_auth.php';

    fetch(url, {
        method: 'POST',
        body: formData
    })
        .then(response => response.text()) // Get text first to debug
        .then(text => {
            console.log('Raw response:', text);
            try {
                return JSON.parse(text);
            } catch (e) {
                throw new Error('Invalid JSON response: ' + text);
            }
        })
        .then(data => {
            if (data.success) {
                // Success
                const modal = bootstrap.Modal.getInstance(document.getElementById('ajouterTacheModal'));
                modal.hide();
                form.reset();
                document.getElementById('taskFileList').innerHTML = '';
                setTaskPriority('moyenne'); // Reset priority

                // Show Toast
                showToast('Tâche créée avec succès !', 'success');

                // Refresh tasks if function exists
                if (typeof loadTasks === 'function') loadTasks();
                if (typeof updateDashboardStats === 'function') updateDashboardStats();
            } else {
                throw new Error(data.message || 'Erreur inconnue');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Erreur lors de la création de la tâche: ' + error.message);
        })
        .finally(() => {
            saveBtn.innerHTML = originalBtnContent;
            saveBtn.disabled = false;
        });
}

// Toast Helper (Reusing existing or creating simple one)
function showToast(message, type) {
    // Check if toastr AND jQuery exist (toastr requires jQuery)
    if (typeof toastr !== 'undefined' && typeof $ !== 'undefined' && $.fn && $.fn.jquery) {
        try {
            toastr[type](message);
            return;
        } catch (e) {
            console.warn('Toastr error, using fallback:', e);
        }
    }

    // Fallback - Custom toast without jQuery
    const toast = document.createElement('div');
    toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 25px;
        background: ${type === 'success' ? '#28a745' : type === 'warning' ? '#ffc107' : type === 'info' ? '#17a2b8' : '#dc3545'};
        color: ${type === 'warning' ? '#212529' : 'white'};
        border-radius: 8px;
        z-index: 99999;
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        font-size: 14px;
        animation: slideIn 0.3s ease-out;
    `;
    toast.textContent = message;

    // Add animation keyframes if not exists
    if (!document.getElementById('toast-animation-style')) {
        const style = document.createElement('style');
        style.id = 'toast-animation-style';
        style.textContent = `
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
        `;
        document.head.appendChild(style);
    }

    document.body.appendChild(toast);
    setTimeout(() => {
        toast.style.animation = 'slideIn 0.3s ease-out reverse';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}
