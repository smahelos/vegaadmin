/**
 * Product image preview functionality
 * Shows preview of selected image when editing products
 */
class ProductImagePreview {
    constructor() {
        this.imageInput = document.querySelector('.product-image-input');
        this.imagePreview = document.getElementById('current-image-preview');
        this.noImageMessage = document.getElementById('no-image-message');
        
        this.init();
    }
    
    init() {
        if (this.imageInput && this.imagePreview) {
            this.imageInput.addEventListener('change', this.handleImageChange.bind(this));
        }
    }
    
    handleImageChange(event) {
        if (event.target.files && event.target.files[0]) {
            const reader = new FileReader();
            
            reader.onload = (e) => {
                this.imagePreview.src = e.target.result;
                this.imagePreview.classList.remove('hidden');
                
                // Hide no image message if it exists
                if (this.noImageMessage) {
                    this.noImageMessage.classList.add('hidden');
                }
            };
            
            reader.readAsDataURL(event.target.files[0]);
        }
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new ProductImagePreview();
});
