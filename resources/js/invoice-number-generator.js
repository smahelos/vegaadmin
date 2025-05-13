// Invoice number generator
export default class InvoiceNumberGenerator {
    constructor() {
        this.generateButton = null;
        this.invoiceNumberInput = null;
    }
    
    init() {
        this.generateButton = document.getElementById('generate-invoice-number');
        this.invoiceNumberInput = document.getElementById('invoice_vs');
        
        if (this.generateButton && this.invoiceNumberInput) {
            this.generateButton.addEventListener('click', this.generateNumber.bind(this));
        }
    }
    
    generateNumber() {
        // Get the current year
        const currentYear = new Date().getFullYear();
        
        // Get the current timestamp (used for uniqueness)
        const timestamp = new Date().getTime().toString().slice(-5);
        
        // Create a new invoice number in the format YYYY + 4 digits
        const newInvoiceNumber = currentYear.toString() + timestamp.padStart(4, '0').slice(-4);
        
        // Set the value to the input field
        this.invoiceNumberInput.value = newInvoiceNumber;
    }
}
