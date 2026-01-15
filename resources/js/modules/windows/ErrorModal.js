/**
 * ErrorModal - Reusable error modal dialog
 * Can be used anywhere in the application to display errors
 */
export class ErrorModal {
    constructor() {
        this.backdrop = null;
    }

    /**
     * Show error modal
     * @param {string} title - Modal title
     * @param {string} message - Error message
     * @param {string|null} details - Optional details/suggestions
     * @param {Object} options - Optional configuration
     * @param {boolean} options.showRefresh - Show refresh button (default: true)
     * @param {boolean} options.showClose - Show close button (default: true)
     * @param {Function} options.onRefresh - Custom refresh handler
     * @param {Function} options.onClose - Custom close handler
     */
    show(title, message, details = null, options = {}) {
        // Default options
        const config = {
            showRefresh: true,
            showClose: true,
            onRefresh: () => window.location.reload(),
            onClose: () => this.close(),
            ...options
        };

        // Remove existing modal if any
        this.close();

        // Create modal backdrop
        this.backdrop = document.createElement('div');
        this.backdrop.className = 'error-modal-backdrop';
        this.backdrop.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 10000;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.2s ease-out;
        `;

        // Create modal dialog
        const modal = document.createElement('div');
        modal.className = 'error-modal';
        modal.style.cssText = `
            background: #2b2b2b;
            border: 2px solid #ff4444;
            border-radius: 8px;
            padding: 30px;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
            color: #ffffff;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            animation: slideIn 0.3s ease-out;
        `;

        // Build modal content
        let contentHtml = `
            <div style="display: flex; align-items: center; margin-bottom: 20px;">
                <div style="font-size: 40px; color: #ff4444; margin-right: 15px;">⚠️</div>
                <h2 style="margin: 0; font-size: 24px; color: #ff4444;">${this.escapeHtml(title)}</h2>
            </div>
            <div style="margin-bottom: 20px; line-height: 1.6;">
                <strong>Error:</strong><br>
                <span style="color: #ffcccc;">${this.escapeHtml(message)}</span>
            </div>
        `;

        if (details) {
            contentHtml += `
                <div style="margin-bottom: 20px; line-height: 1.6; color: #cccccc;">
                    ${this.escapeHtml(details)}
                </div>
            `;
        }

        // Add buttons
        if (config.showRefresh || config.showClose) {
            contentHtml += '<div style="text-align: right;">';

            if (config.showRefresh) {
                contentHtml += `
                    <button class="error-modal-refresh" style="
                        background: #ff4444;
                        color: white;
                        border: none;
                        padding: 10px 20px;
                        font-size: 16px;
                        border-radius: 4px;
                        cursor: pointer;
                        margin-right: 10px;
                        transition: background 0.2s;
                    " onmouseover="this.style.background='#ff6666'" onmouseout="this.style.background='#ff4444'">
                        Refresh Page
                    </button>
                `;
            }

            if (config.showClose) {
                contentHtml += `
                    <button class="error-modal-close" style="
                        background: #555;
                        color: white;
                        border: none;
                        padding: 10px 20px;
                        font-size: 16px;
                        border-radius: 4px;
                        cursor: pointer;
                        transition: background 0.2s;
                    " onmouseover="this.style.background='#777'" onmouseout="this.style.background='#555'">
                        Close
                    </button>
                `;
            }

            contentHtml += '</div>';
        }

        modal.innerHTML = contentHtml;
        this.backdrop.appendChild(modal);
        document.body.appendChild(this.backdrop);

        // Add animations CSS if not already present
        this.addAnimations();

        // Attach event listeners
        if (config.showRefresh) {
            modal.querySelector('.error-modal-refresh').addEventListener('click', config.onRefresh);
        }

        if (config.showClose) {
            modal.querySelector('.error-modal-close').addEventListener('click', config.onClose);
        }

        // Close on backdrop click
        this.backdrop.addEventListener('click', (e) => {
            if (e.target === this.backdrop) {
                config.onClose();
            }
        });

        // Close on ESC key
        this.escKeyHandler = (e) => {
            if (e.key === 'Escape') {
                config.onClose();
            }
        };
        document.addEventListener('keydown', this.escKeyHandler);
    }

    /**
     * Close and remove modal
     */
    close() {
        if (this.backdrop) {
            this.backdrop.remove();
            this.backdrop = null;
        }

        if (this.escKeyHandler) {
            document.removeEventListener('keydown', this.escKeyHandler);
            this.escKeyHandler = null;
        }
    }

    /**
     * Escape HTML to prevent XSS
     * @param {string} text
     * @returns {string}
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Add CSS animations if not already present
     */
    addAnimations() {
        if (document.getElementById('error-modal-animations')) {
            return;
        }

        const style = document.createElement('style');
        style.id = 'error-modal-animations';
        style.textContent = `
            @keyframes fadeIn {
                from { opacity: 0; }
                to { opacity: 1; }
            }

            @keyframes slideIn {
                from {
                    transform: translateY(-50px);
                    opacity: 0;
                }
                to {
                    transform: translateY(0);
                    opacity: 1;
                }
            }
        `;
        document.head.appendChild(style);
    }

    /**
     * Static helper to show modal quickly without creating instance
     * @param {string} title
     * @param {string} message
     * @param {string|null} details
     * @param {Object} options
     */
    static show(title, message, details = null, options = {}) {
        const modal = new ErrorModal();
        modal.show(title, message, details, options);
        return modal;
    }
}
