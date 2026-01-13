/**
 * Template selector functionality for invoice forms
 * HTML modal is now defined in Blade template, this class only handles interactions
 */

class TemplateSelector {
    constructor() {
        this.modalId = 'template-selector-modal';
        this.currentTemplate = 'default';
        this.hiddenInput = null;
        this.init();
    }

    init() {
        this.attachEventListeners();
        this.loadCurrentTemplate();
    }

    attachEventListeners() {
        // Settings button click
        document.addEventListener('click', (e) => {
            if (e.target.matches('.template-settings-btn') || e.target.closest('.template-settings-btn')) {
                e.preventDefault();
                this.showModal();
            }
        });

        // Modal close buttons
        document.addEventListener('click', (e) => {
            if (e.target.matches('.template-modal-close') || e.target.closest('.template-modal-close')) {
                this.hideModal();
            }
        });

        // Modal backdrop click
        document.addEventListener('click', (e) => {
            if (e.target.id === this.modalId) {
                this.hideModal();
            }
        });

        // Template option selection
        document.addEventListener('click', (e) => {
            const templateOption = e.target.closest('.template-option');
            if (templateOption) {
                this.selectTemplate(templateOption.dataset.template);
            }
        });

        // Confirm selection
        document.addEventListener('click', (e) => {
            if (e.target.id === 'confirm-template-selection') {
                this.confirmSelection();
            }
        });

        // ESC key to close modal
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                const modal = document.getElementById(this.modalId);
                if (modal && !modal.classList.contains('hidden')) {
                    this.hideModal();
                }
            }
        });
    }

    showModal() {
        const modal = document.getElementById(this.modalId);
        if (modal) {
            modal.classList.remove('hidden');
            modal.style.display = 'flex';
            modal.style.alignItems = 'center';
            modal.style.justifyContent = 'center';
            document.body.style.overflow = 'hidden';
            this.updateModalSelection();
        }
    }

    hideModal() {
        const modal = document.getElementById(this.modalId);
        if (modal) {
            modal.classList.add('hidden');
            modal.style.display = 'none';
            document.body.style.overflow = '';
        }
    }

    selectTemplate(template) {
        // Remove selection from all options
        document.querySelectorAll('.template-option').forEach(option => {
            option.classList.remove('border-blue-500', 'dark:border-blue-400', 'bg-blue-50', 'dark:bg-blue-900');
            const radioDiv = option.querySelector('.template-radio div');
            if (radioDiv) {
                radioDiv.classList.add('hidden');
            }
        });

        // Add selection to clicked option
        const selectedOption = document.querySelector(`[data-template="${template}"]`);
        if (selectedOption) {
            selectedOption.classList.add('border-blue-500', 'dark:border-blue-400', 'bg-blue-50', 'dark:bg-blue-900');
            const radioDiv = selectedOption.querySelector('.template-radio div');
            if (radioDiv) {
                radioDiv.classList.remove('hidden');
            }
        }

        this.currentTemplate = template;
        
        // Enable confirm button
        const confirmBtn = document.getElementById('confirm-template-selection');
        if (confirmBtn) {
            confirmBtn.disabled = false;
        }
    }

    confirmSelection() {
        // Update hidden input value
        if (!this.hiddenInput) {
            this.hiddenInput = document.querySelector('input[name="template"]');
        }

        if (this.hiddenInput) {
            this.hiddenInput.value = this.currentTemplate;
        }

        // Update settings button text
        this.updateSettingsButtonText();

        // Close modal
        this.hideModal();

        // Show success message
        this.showSuccessMessage();
    }

    updateModalSelection() {
        // Clear all selections first
        document.querySelectorAll('.template-option').forEach(option => {
            option.classList.remove('border-blue-500', 'dark:border-blue-400', 'bg-blue-50', 'dark:bg-blue-900');
            const radioDiv = option.querySelector('.template-radio div');
            if (radioDiv) {
                radioDiv.classList.add('hidden');
            }
        });

        // Select current template
        const currentOption = document.querySelector(`[data-template="${this.currentTemplate}"]`);
        if (currentOption) {
            currentOption.classList.add('border-blue-500', 'dark:border-blue-400', 'bg-blue-50', 'dark:bg-blue-900');
            const radioDiv = currentOption.querySelector('.template-radio div');
            if (radioDiv) {
                radioDiv.classList.remove('hidden');
            }
        }

        // Enable confirm button if template is selected
        const confirmBtn = document.getElementById('confirm-template-selection');
        if (confirmBtn) {
            confirmBtn.disabled = false;
        }
    }

    updateSettingsButtonText() {
        const templateName = window.trans?.invoices?.templates?.[this.currentTemplate] || this.currentTemplate;
        const settingsBtn = document.querySelector('.template-settings-btn .template-name');
        if (settingsBtn) {
            settingsBtn.textContent = templateName;
        }
    }

    loadCurrentTemplate() {
        // Get current template from hidden input
        this.hiddenInput = document.querySelector('input[name="template"]');
        if (this.hiddenInput && this.hiddenInput.value) {
            this.currentTemplate = this.hiddenInput.value;
        }

        // Update settings button text
        this.updateSettingsButtonText();
    }

    showSuccessMessage() {
        // Create and show temporary success message
        const templateName = this.getTemplateDisplayName(this.currentTemplate);
        const message = document.createElement('div');
        message.className = 'fixed top-4 right-4 bg-green-500 text-white px-4 py-2 rounded-lg shadow-lg z-50 transform translate-x-full transition-transform duration-300';
        message.textContent = `${templateName} template selected`;
        
        document.body.appendChild(message);
        
        // Animate in
        setTimeout(() => {
            message.classList.remove('translate-x-full');
        }, 100);
        
        // Remove after 3 seconds
        setTimeout(() => {
            message.classList.add('translate-x-full');
            setTimeout(() => {
                if (message.parentNode) {
                    message.parentNode.removeChild(message);
                }
            }, 300);
        }, 3000);
    }

    getTemplateDisplayName(templateKey) {
        // Get template name from existing DOM elements or fallback to key
        const templateOption = document.querySelector(`[data-template="${templateKey}"] h4`);
        if (templateOption) {
            return templateOption.textContent.trim();
        }
        return templateKey.charAt(0).toUpperCase() + templateKey.slice(1);
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.templateSelector = new TemplateSelector();
});

// Also initialize if script is loaded after DOM
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.templateSelector = new TemplateSelector();
    });
} else {
    window.templateSelector = new TemplateSelector();
}
