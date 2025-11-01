/**
 * MODAL SYSTEM - Modern Popups & Notifications
 * Replace all alert(), confirm() with beautiful modals
 */

class ModalSystem {
    constructor() {
        this.createContainer();
        this.createToastContainer();
    }

    // Create modal container
    createContainer() {
        if (!document.getElementById('modal-overlay')) {
            const overlay = document.createElement('div');
            overlay.id = 'modal-overlay';
            overlay.className = 'modal-overlay';
            overlay.onclick = (e) => {
                if (e.target === overlay) {
                    this.close();
                }
            };
            document.body.appendChild(overlay);
        }
    }

    // Create toast container
    createToastContainer() {
        if (!document.getElementById('toast-container')) {
            const container = document.createElement('div');
            container.id = 'toast-container';
            container.className = 'toast-container';
            document.body.appendChild(container);
        }
    }

    // Show alert modal
    alert(title, message, type = 'info') {
        const icons = {
            success: '<i class="fas fa-check-circle"></i>',
            error: '<i class="fas fa-exclamation-circle"></i>',
            warning: '<i class="fas fa-exclamation-triangle"></i>',
            info: '<i class="fas fa-info-circle"></i>'
        };

        const modal = `
            <div class="modal">
                <div class="modal-header ${type}">
                    <div class="modal-icon">${icons[type]}</div>
                    <h3 class="modal-title">${title}</h3>
                    <button class="modal-close" onclick="Modal.close()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <p>${message}</p>
                </div>
                <div class="modal-footer">
                    <button class="modal-btn modal-btn-primary" onclick="Modal.close()">
                        <i class="fas fa-check"></i> OK
                    </button>
                </div>
            </div>
        `;

        this.show(modal);
    }

    // Show confirm modal
    confirm(title, message, onConfirm, onCancel = null) {
        const modal = `
            <div class="modal">
                <div class="modal-header warning">
                    <div class="modal-icon">
                        <i class="fas fa-question-circle"></i>
                    </div>
                    <h3 class="modal-title">${title}</h3>
                    <button class="modal-close" onclick="Modal.close()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <p>${message}</p>
                </div>
                <div class="modal-footer">
                    <button class="modal-btn modal-btn-secondary" onclick="Modal.handleCancel()">
                        <i class="fas fa-times"></i> Annuler
                    </button>
                    <button class="modal-btn modal-btn-danger" onclick="Modal.handleConfirm()">
                        <i class="fas fa-check"></i> Confirmer
                    </button>
                </div>
            </div>
        `;

        this.confirmCallback = onConfirm;
        this.cancelCallback = onCancel;
        this.show(modal);
    }

    // Show success modal
    success(title, message) {
        this.alert(title, message, 'success');
    }

    // Show error modal
    error(title, message) {
        this.alert(title, message, 'error');
    }

    // Show warning modal
    warning(title, message) {
        this.alert(title, message, 'warning');
    }

    // Show custom modal
    show(content) {
        const overlay = document.getElementById('modal-overlay');
        overlay.innerHTML = content;
        overlay.classList.add('active');
        
        // Prevent body scroll
        document.body.style.overflow = 'hidden';
        
        // ESC key to close
        this.escHandler = (e) => {
            if (e.key === 'Escape') {
                this.close();
            }
        };
        document.addEventListener('keydown', this.escHandler);
    }

    // Close modal
    close() {
        const overlay = document.getElementById('modal-overlay');
        overlay.classList.remove('active');
        overlay.innerHTML = '';
        document.body.style.overflow = '';
        
        if (this.escHandler) {
            document.removeEventListener('keydown', this.escHandler);
        }
    }

    // Handle confirm
    handleConfirm() {
        if (this.confirmCallback) {
            this.confirmCallback();
        }
        this.close();
    }

    // Handle cancel
    handleCancel() {
        if (this.cancelCallback) {
            this.cancelCallback();
        }
        this.close();
    }

    // Show toast notification
    toast(message, type = 'info', duration = 3000) {
        const icons = {
            success: '<i class="fas fa-check-circle"></i>',
            error: '<i class="fas fa-times-circle"></i>',
            warning: '<i class="fas fa-exclamation-triangle"></i>',
            info: '<i class="fas fa-info-circle"></i>'
        };

        const titles = {
            success: 'Succès',
            error: 'Erreur',
            warning: 'Attention',
            info: 'Information'
        };

        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.innerHTML = `
            <div class="toast-icon">${icons[type]}</div>
            <div class="toast-content">
                <div class="toast-title">${titles[type]}</div>
                <div class="toast-message">${message}</div>
            </div>
            <button class="toast-close" onclick="this.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
        `;

        const container = document.getElementById('toast-container');
        container.appendChild(toast);

        // Auto remove
        setTimeout(() => {
            toast.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, duration);
    }

    // Loading modal
    loading(message = 'Chargement en cours...') {
        const modal = `
            <div class="modal">
                <div class="modal-header info">
                    <div class="modal-icon">
                        <div class="modal-spinner"></div>
                    </div>
                    <h3 class="modal-title">Veuillez patienter</h3>
                </div>
                <div class="modal-body">
                    <p>${message}</p>
                </div>
            </div>
        `;
        this.show(modal);
    }

    // Prompt modal
    prompt(title, message, defaultValue = '', onConfirm) {
        const modal = `
            <div class="modal">
                <div class="modal-header info">
                    <div class="modal-icon">
                        <i class="fas fa-edit"></i>
                    </div>
                    <h3 class="modal-title">${title}</h3>
                    <button class="modal-close" onclick="Modal.close()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <p>${message}</p>
                    <input type="text" id="modal-prompt-input" value="${defaultValue}" 
                           style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 6px; margin-top: 1rem; font-size: 1rem;">
                </div>
                <div class="modal-footer">
                    <button class="modal-btn modal-btn-secondary" onclick="Modal.close()">
                        <i class="fas fa-times"></i> Annuler
                    </button>
                    <button class="modal-btn modal-btn-primary" onclick="Modal.handlePrompt()">
                        <i class="fas fa-check"></i> Valider
                    </button>
                </div>
            </div>
        `;

        this.promptCallback = onConfirm;
        this.show(modal);
        
        // Focus input
        setTimeout(() => {
            document.getElementById('modal-prompt-input').focus();
        }, 100);
    }

    handlePrompt() {
        const value = document.getElementById('modal-prompt-input').value;
        if (this.promptCallback) {
            this.promptCallback(value);
        }
        this.close();
    }
}

// Initialize modal system
const Modal = new ModalSystem();

// Override window.alert
window.alert = function(message) {
    Modal.alert('Information', message, 'info');
};

// Override window.confirm
window.confirm = function(message) {
    return new Promise((resolve) => {
        Modal.confirm('Confirmation', message, 
            () => resolve(true),
            () => resolve(false)
        );
    });
};

// Add shorthand methods to window
window.Modal = Modal;
window.showToast = (message, type, duration) => Modal.toast(message, type, duration);
window.showSuccess = (message) => Modal.toast(message, 'success');
window.showError = (message) => Modal.toast(message, 'error');
window.showWarning = (message) => Modal.toast(message, 'warning');
window.showInfo = (message) => Modal.toast(message, 'info');
