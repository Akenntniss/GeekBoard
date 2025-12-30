// ============================================
// GeekBoard SuperAdmin - Modern JavaScript
// ============================================

document.addEventListener('DOMContentLoaded', function () {

    // ==================== Sidebar Toggle ==================== //
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.querySelector('.sidebar');

    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function () {
            sidebar.classList.toggle('show');
        });

        // Close sidebar on outside click (mobile)
        document.addEventListener('click', function (e) {
            if (window.innerWidth <= 768) {
                if (!sidebar.contains(e.target) && !sidebarToggle.contains(e.target)) {
                    sidebar.classList.remove('show');
                }
            }
        });
    }

    // ==================== Dropdown Profile ==================== //
    const profileDropdown = document.getElementById('profileDropdown');
    const profileMenu = document.getElementById('profileMenu');

    if (profileDropdown && profileMenu) {
        profileDropdown.addEventListener('click', function (e) {
            e.stopPropagation();
            profileMenu.parentElement.classList.toggle('show');
        });

        // Close on outside click
        document.addEventListener('click', function () {
            profileMenu.parentElement.classList.remove('show');
        });
    }

    // ==================== Tooltips ==================== //
    // Initialize tooltips if needed
    const tooltips = document.querySelectorAll('[data-tooltip]');
    tooltips.forEach(el => {
        // Tooltip is handled by CSS, nothing to do in JS
    });

    // ==================== Smooth Animations ==================== //
    // Add slide-in animation to cards
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-slideInUp');
            }
        });
    }, {
        threshold: 0.1
    });

    // Observe all cards
    document.querySelectorAll('.card, .stat-card').forEach(card => {
        observer.observe(card);
    });

    // ==================== Table Sorting ==================== //
    const sortableHeaders = document.querySelectorAll('.table th.sortable');

    sortableHeaders.forEach(header => {
        header.addEventListener('click', function () {
            const table = this.closest('table');
            const tbody = table.querySelector('tbody');
            const rows = Array.from(tbody.querySelectorAll('tr'));
            const columnIndex = Array.from(this.parentElement.children).indexOf(this);
            const currentDirection = this.dataset.sortDirection || 'asc';
            const newDirection = currentDirection === 'asc' ? 'desc' : 'asc';

            // Remove sort indicators from all headers
            sortableHeaders.forEach(h => {
                h.dataset.sortDirection = '';
                const icon = h.querySelector('i');
                if (icon) {
                    icon.className = 'fas fa-sort text-muted ms-1';
                }
            });

            // Set sort indicator for this header
            this.dataset.sortDirection = newDirection;
            const icon = this.querySelector('i');
            if (icon) {
                icon.className = `fas fa-sort-${newDirection === 'asc' ? 'up' : 'down'} ms-1`;
            }

            // Sort rows
            rows.sort((a, b) => {
                const aValue = a.cells[columnIndex].textContent.trim();
                const bValue = b.cells[columnIndex].textContent.trim();

                // Try to parse as number
                const aNum = parseFloat(aValue);
                const bNum = parseFloat(bValue);

                if (!isNaN(aNum) && !isNaN(bNum)) {
                    return newDirection === 'asc' ? aNum - bNum : bNum - aNum;
                }

                // String comparison
                return newDirection === 'asc'
                    ? aValue.localeCompare(bValue)
                    : bValue.localeCompare(aValue);
            });

            // Reappend sorted rows
            rows.forEach(row => tbody.appendChild(row));
        });
    });

    // ==================== Search Filter ==================== //
    const searchInputs = document.querySelectorAll('[data-search-target]');

    searchInputs.forEach(input => {
        input.addEventListener('input', function () {
            const searchTerm = this.value.toLowerCase().trim();
            const targetSelector = this.dataset.searchTarget;
            const items = document.querySelectorAll(targetSelector);

            items.forEach(item => {
                const text = item.textContent.toLowerCase();
                item.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
    });

    // ==================== Modal Management ==================== //
    // Bootstrap modals are automatically handled
    // But we can add custom logic if needed

    // ==================== Auto-dismiss Alerts ==================== //
    const alerts = document.querySelectorAll('.alert[data-auto-dismiss]');

    alerts.forEach(alert => {
        const delay = parseInt(alert.dataset.autoDismiss) || 5000;

        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        }, delay);
    });

    // ==================== Copy to Clipboard ==================== //
    document.querySelectorAll('[data-copy]').forEach(btn => {
        btn.addEventListener('click', function () {
            const text = this.dataset.copy;
            navigator.clipboard.writeText(text).then(() => {
                // Show feedback
                const originalText = this.innerHTML;
                this.innerHTML = '<i class="fas fa-check"></i> Copié!';
                this.classList.add('btn-success');

                setTimeout(() => {
                    this.innerHTML = originalText;
                    this.classList.remove('btn-success');
                }, 2000);
            });
        });
    });

    // ==================== Loading State for Forms ==================== //
    document.querySelectorAll('form[data-loading]').forEach(form => {
        form.addEventListener('submit', function (e) {
            const submitBtn = this.querySelector('button[type="submit"]');

            if (submitBtn) {
                submitBtn.disabled = true;
                const originalHTML = submitBtn.innerHTML;
                submitBtn.innerHTML = '<span class="spinner"></span> Chargement...';

                // Fallback: re-enable after 10 seconds
                setTimeout(() => {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalHTML;
                }, 10000);
            }
        });
    });

    // ==================== Confirmation Dialogs ==================== //
    document.querySelectorAll('[data-confirm]').forEach(el => {
        el.addEventListener('click', function (e) {
            const message = this.dataset.confirm;
            if (!confirm(message)) {
                e.preventDefault();
                return false;
            }
        });
    });
});

// ==================== Helper Functions ==================== //

// Toast notification
function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `alert alert-${type} toast-notification`;
    toast.style.cssText = `
        position: fixed;
        top: 2rem;
        right: 2rem;
        z-index: 9999;
        min-width: 300px;
        animation: slideInLeft 0.3s ease;
    `;
    toast.textContent = message;

    document.body.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Loading overlay
function showLoading() {
    const overlay = document.createElement('div');
    overlay.id = 'loadingOverlay';
    overlay.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
    `;
    overlay.innerHTML = '<div class="spinner" style="width: 40px; height: 40px;"></div>';
    document.body.appendChild(overlay);
}

function hideLoading() {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) {
        overlay.remove();
    }
}

// Export functions
window.showToast = showToast;
window.showLoading = showLoading;
window.hideLoading = hideLoading;
