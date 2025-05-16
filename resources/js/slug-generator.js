/**
 * Slug Generator
 * 
 * This script automatically generates a URL-friendly slug from a source input field.
 * It converts the text to lowercase, removes special characters and replaces spaces with dashes.
 */
export default class SlugGenerator {
    /**
     * Initialize slug generator
     * 
     * @param {string} sourceSelector - CSS selector for the source input field
     * @param {string} targetSelector - CSS selector for the target slug field
     * @param {Object} options - Additional options
     * @param {boolean} options.overwriteExisting - Whether to overwrite existing slug value (default: false)
     * @param {boolean} options.enableEdit - Whether to allow manual editing of the slug field (default: true)
     */
    constructor(sourceSelector, targetSelector, options = {}) {
        this.sourceField = document.querySelector(sourceSelector);
        this.targetField = document.querySelector(targetSelector);
        this.options = {
            overwriteExisting: false,
            enableEdit: true,
            ...options
        };
        
        this.manuallyEdited = false;
        
        if (!this.sourceField || !this.targetField) {
            console.error('SlugGenerator: Source or target field not found');
            return;
        }
        
        this.init();
    }
    
    /**
     * Initialize event listeners
     */
    init() {
        // Generate slug on source field input using both input and keyup events for better compatibility
        this.sourceField.addEventListener('input', () => this.generateSlug());
        this.sourceField.addEventListener('keyup', () => this.generateSlug());
        this.sourceField.addEventListener('change', () => this.generateSlug());
        
        // Track when user manually edits the slug field
        if (this.options.enableEdit) {
            this.targetField.addEventListener('input', () => {
                this.manuallyEdited = true;
            });
            
            // Re-enable auto generation when source field is focused after manual edit
            this.sourceField.addEventListener('focus', () => {
                if (this.manuallyEdited && this.options.overwriteExisting) {
                    this.manuallyEdited = false;
                }
            });
        } else {
            // If editing is disabled, make the field readonly
            this.targetField.setAttribute('readonly', 'readonly');
        }
        
        // Initial generation if source has value and target is empty
        if (this.sourceField.value && (!this.targetField.value || this.options.overwriteExisting)) {
            this.generateSlug();
        }
        
        // Debug logging to check if the events are properly attached
        console.log('SlugGenerator initialized for source field:', this.sourceField, 'target field:', this.targetField);
    }
    
    /**
     * Generate a slug from the source field value
     */
    generateSlug() {
        // Don't overwrite if manually edited
        if (this.manuallyEdited && !this.options.overwriteExisting) {
            return;
        }
        
        const sourceText = this.sourceField.value;
        if (!sourceText) {
            return;
        }
        
        // Convert to slug format
        const slug = this.convertToSlug(sourceText);
        
        // Only update if the slug has changed
        if (this.targetField.value !== slug) {
            this.targetField.value = slug;
            
            // Trigger change event on the target field
            const event = new Event('change', { bubbles: true });
            this.targetField.dispatchEvent(event);
            
            // Debug logging
            console.log('Slug generated:', slug, 'from source:', sourceText);
        }
    }
    
    /**
     * Convert text to URL-friendly slug format
     * 
     * @param {string} text - Text to convert to slug
     * @return {string} - URL-friendly slug
     */
    convertToSlug(text) {
        // Convert to lowercase and remove accents/diacritics
        let slug = text.toLowerCase().trim();
        
        // Replace accents/diacritics
        slug = slug.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        
        // Replace spaces and special characters with hyphens
        slug = slug.replace(/[^a-z0-9]+/g, '-');
        
        // Remove leading and trailing hyphens
        slug = slug.replace(/^-+|-+$/g, '');
        
        return slug;
    }
}
