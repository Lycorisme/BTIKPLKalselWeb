/**
 * BTIKP Notification System
 * Centralized notifications, alerts, and confirmations
 */

class BTIKPNotification {
    constructor() {
        this.container = null;
        this.init();
    }

    init() {
        // Create notification container
        if (!document.getElementById('btikp-notification-container')) {
            const container = document.createElement('div');
            container.id = 'btikp-notification-container';
            container.className = 'btikp-notification-container';
            document.body.appendChild(container);
            this.container = container;
        }
    }

    /**
     * Show toast notification
     * @param {string} type - success, error, warning, info
     * @param {string} message - Message content
     * @param {number} duration - Duration in ms (default: 3000)
     */
    toast(type, message, duration = 3000) {
        const toast = document.createElement('div');
        toast.className = `btikp-toast btikp-toast-${type}`;
        
        const icon = this.getIcon(type);
        const title = this.getTitle(type);
        
        toast.innerHTML = `
            <div class="btikp-toast-icon">
                <i class="${icon}"></i>
            </div>
            <div class="btikp-toast-content">
                <div class="btikp-toast-title">${title}</div>
                <div class="btikp-toast-message">${message}</div>
            </div>
            <button class="btikp-toast-close" onclick="this.parentElement.remove()">
                <i class="bi bi-x-lg"></i>
            </button>
        `;
        
        this.container.appendChild(toast);
        
        // Animate in
        setTimeout(() => toast.classList.add('show'), 10);
        
        // Auto remove
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, duration);
    }

    /**
     * Show alert modal
     * @param {object} options - Alert options
     */
    alert(options = {}) {
        const defaults = {
            type: 'info',
            title: 'Informasi',
            message: '',
            confirmText: 'OK',
            onConfirm: null
        };
        
        const config = { ...defaults, ...options };
        
        const modal = document.createElement('div');
        modal.className = 'btikp-alert-overlay';
        modal.innerHTML = `
            <div class="btikp-alert btikp-alert-${config.type}">
                <div class="btikp-alert-icon">
                    <i class="${this.getIcon(config.type)}"></i>
                </div>
                <div class="btikp-alert-title">${config.title}</div>
                <div class="btikp-alert-message">${config.message}</div>
                <div class="btikp-alert-actions">
                    <button class="btikp-btn btikp-btn-${config.type}" id="btikp-alert-confirm">
                        ${config.confirmText}
                    </button>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        setTimeout(() => modal.classList.add('show'), 10);
        
        const confirmBtn = modal.querySelector('#btikp-alert-confirm');
        confirmBtn.onclick = () => {
            modal.classList.remove('show');
            setTimeout(() => modal.remove(), 300);
            if (config.onConfirm) config.onConfirm();
        };
    }

    /**
     * Show confirmation dialog
     * @param {object} options - Confirm options
     */
    confirm(options = {}) {
        const defaults = {
            type: 'warning',
            title: 'Konfirmasi',
            message: 'Apakah Anda yakin?',
            confirmText: 'Ya',
            cancelText: 'Batal',
            onConfirm: null,
            onCancel: null
        };
        
        const config = { ...defaults, ...options };
        
        const modal = document.createElement('div');
        modal.className = 'btikp-alert-overlay';
        modal.innerHTML = `
            <div class="btikp-alert btikp-alert-${config.type}">
                <div class="btikp-alert-icon">
                    <i class="${this.getIcon(config.type)}"></i>
                </div>
                <div class="btikp-alert-title">${config.title}</div>
                <div class="btikp-alert-message">${config.message}</div>
                <div class="btikp-alert-actions">
                    <button class="btikp-btn btikp-btn-secondary" id="btikp-alert-cancel">
                        ${config.cancelText}
                    </button>
                    <button class="btikp-btn btikp-btn-${config.type}" id="btikp-alert-confirm">
                        ${config.confirmText}
                    </button>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        setTimeout(() => modal.classList.add('show'), 10);
        
        const confirmBtn = modal.querySelector('#btikp-alert-confirm');
        const cancelBtn = modal.querySelector('#btikp-alert-cancel');
        
        const closeModal = () => {
            modal.classList.remove('show');
            setTimeout(() => modal.remove(), 300);
        };
        
        confirmBtn.onclick = () => {
            closeModal();
            if (config.onConfirm) config.onConfirm();
        };
        
        cancelBtn.onclick = () => {
            closeModal();
            if (config.onCancel) config.onCancel();
        };
        
        // Close on overlay click
        modal.onclick = (e) => {
            if (e.target === modal) {
                closeModal();
                if (config.onCancel) config.onCancel();
            }
        };
    }

    /**
     * Show loading overlay
     * @param {string} message - Loading message
     */
    loading(message = 'Memproses...') {
        const loading = document.createElement('div');
        loading.id = 'btikp-loading-overlay';
        loading.className = 'btikp-loading-overlay';
        loading.innerHTML = `
            <div class="btikp-loading">
                <div class="btikp-loading-spinner"></div>
                <div class="btikp-loading-message">${message}</div>
            </div>
        `;
        
        document.body.appendChild(loading);
        setTimeout(() => loading.classList.add('show'), 10);
    }

    /**
     * Hide loading overlay
     */
    hideLoading() {
        const loading = document.getElementById('btikp-loading-overlay');
        if (loading) {
            loading.classList.remove('show');
            setTimeout(() => loading.remove(), 300);
        }
    }

    getIcon(type) {
        const icons = {
            success: 'bi bi-check-circle-fill',
            error: 'bi bi-x-circle-fill',
            warning: 'bi bi-exclamation-triangle-fill',
            info: 'bi bi-info-circle-fill',
            danger: 'bi bi-x-circle-fill'
        };
        return icons[type] || icons.info;
    }

    getTitle(type) {
        const titles = {
            success: 'Berhasil!',
            error: 'Error!',
            warning: 'Peringatan!',
            info: 'Informasi',
            danger: 'Error!'
        };
        return titles[type] || titles.info;
    }

    /**
     * AUTO-BIND DELETE BUTTONS
     * Automatically bind all delete buttons with data-confirm attribute
     */
    autoBindDeleteButtons() {
        // Bind all links/buttons with data-confirm-delete attribute
        document.querySelectorAll('[data-confirm-delete]').forEach((btn) => {
            // Remove existing onclick to avoid conflicts
            btn.removeAttribute('onclick');
            
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                
                const url = btn.getAttribute('href') || btn.getAttribute('data-url');
                const title = btn.getAttribute('data-title') || 'item ini';
                const message = btn.getAttribute('data-message') || `"${title}" akan dihapus permanen. Lanjutkan?`;
                const confirmText = btn.getAttribute('data-confirm-text') || 'Ya, Hapus';
                const cancelText = btn.getAttribute('data-cancel-text') || 'Batal';
                const loadingText = btn.getAttribute('data-loading-text') || 'Menghapus...';
                
                this.confirm({
                    type: 'danger',
                    title: 'Hapus Data?',
                    message: message,
                    confirmText: confirmText,
                    cancelText: cancelText,
                    onConfirm: () => {
                        this.loading(loadingText);
                        window.location.href = url;
                    }
                });
            });
        });
    }

    /**
     * AUTO-BIND LOGOUT BUTTON
     */
    autoBindLogoutButton() {
        const logoutBtn = document.querySelector('[data-confirm-logout]');
        if (logoutBtn) {
            logoutBtn.removeAttribute('onclick');
            
            logoutBtn.addEventListener('click', (e) => {
                e.preventDefault();
                
                const url = logoutBtn.getAttribute('href') || logoutBtn.getAttribute('data-url');
                
                this.confirm({
                    type: 'warning',
                    title: 'Logout',
                    message: 'Yakin ingin keluar dari admin panel?',
                    confirmText: 'Ya, Logout',
                    cancelText: 'Batal',
                    onConfirm: () => {
                        this.loading('Logging out...');
                        window.location.href = url;
                    }
                });
            });
        }
    }

    /**
     * Initialize all auto-bindings
     */
    initAutoBindings() {
        this.autoBindDeleteButtons();
        this.autoBindLogoutButton();
    }
}

// Initialize global instance
window.BTIKPNotify = new BTIKPNotification();

// Shorthand functions
window.notify = {
    success: (msg, duration) => window.BTIKPNotify.toast('success', msg, duration),
    error: (msg, duration) => window.BTIKPNotify.toast('error', msg, duration),
    warning: (msg, duration) => window.BTIKPNotify.toast('warning', msg, duration),
    info: (msg, duration) => window.BTIKPNotify.toast('info', msg, duration),
    alert: (options) => window.BTIKPNotify.alert(options),
    confirm: (options) => window.BTIKPNotify.confirm(options),
    loading: (msg) => window.BTIKPNotify.loading(msg),
    hideLoading: () => window.BTIKPNotify.hideLoading()
};

// Auto-initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    window.BTIKPNotify.initAutoBindings();
});