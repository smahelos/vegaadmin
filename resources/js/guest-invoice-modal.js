// Modal for displaying guest invoice information
// This module handles the display of a modal for guest invoices.
// It includes functionality for submitting the invoice form, displaying the invoice number,
// and providing a download link for the invoice PDF.
// It also handles the CSRF token for secure form submission.
export default class GuestInvoiceModal {
    constructor(options = {}) {
        this.modal = null;
        this.closeButton = null;
        this.downloadButton = null;
        this.invoiceNumberElement = null;
        this.form = null;
        this.submitButton = null;
        
        this.messages = Object.assign({
            processing: 'Processing...',
            createError: 'An error occurred while creating the invoice.'
        }, options.messages || {});
        
        this.routes = Object.assign({
            storeGuest: '/invoice/store/guest'
        }, options.routes || {});
        
        this.csrfToken = options.csrfToken || '';
    }
    
    init() {
        this.form = document.querySelector('form');
        this.modal = document.getElementById('guest-invoice-modal');
        
        if (!this.form || !this.modal) return;
        
        this.closeButton = this.modal.querySelector('.close-modal');
        this.downloadButton = document.getElementById('download-invoice-btn');
        this.invoiceNumberElement = document.getElementById('invoice-number');
        this.submitButton = this.form.querySelector('button[type="submit"]');
        
        this.form.addEventListener('submit', this.handleFormSubmit.bind(this));
        
        if (this.closeButton) {
            this.closeButton.addEventListener('click', () => {
                this.modal.classList.add('hidden');
                // Reload the page to reset the form
                window.location.reload();
            });
        }
    }
    
    handleFormSubmit(e) {
        e.preventDefault();
        
        // Send AJAX request
        const formData = new FormData(this.form);
        
        // Show loading indicator
        if (this.submitButton) {
            this.submitButton.disabled = true;
            this.submitButton.innerHTML = `<i class="fas fa-spinner fa-spin mr-2"></i> ${this.messages.processing}`;
        }
        
        fetch(this.routes.storeGuest, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': this.csrfToken
            }
        })
        .then(response => response.json())
        .then(data => {
            // Debug server data
            console.log('Response data:', data);
            if (data.success) {
                // Set the invoice number
                if (this.invoiceNumberElement) {
                    this.invoiceNumberElement.textContent = data.invoice_number;
                }
                
                // Set the download link
                if (this.downloadButton) {
                    this.downloadButton.href = data.download_url;
                }
                
                // Show modal window
                this.modal.classList.remove('hidden');
            } else {
                alert(data.message || this.messages.createError);
                
                // Enable submit button
                if (this.submitButton) {
                    this.submitButton.disabled = false;
                    this.submitButton.innerHTML = '<i class="fas fa-save mr-2"></i> ' + data.buttonText;
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert(this.messages.createError);
            
            // Enable submit button
            if (this.submitButton) {
                this.submitButton.disabled = false;
                this.submitButton.innerHTML = '<i class="fas fa-save mr-2"></i> ' + this.form.querySelector('button[type="submit"]').textContent;
            }
        });
    }
}
