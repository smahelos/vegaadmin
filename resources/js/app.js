import './bootstrap';
import Alpine from 'alpinejs';
import focus from '@alpinejs/focus';
import { initCountrySelect } from './country-select';
import SlugGenerator from './slug-generator';

// For None Livewire pages, start Alpine.js when the DOM is ready
// This is to ensure that Alpine.js is only started when there is no Livewire component on the page
// This is important because Livewire and Alpine.js can conflict with each other
if (!window.Livewire) {
    Alpine.plugin(focus);
    window.Alpine = Alpine;
    document.addEventListener('DOMContentLoaded', () => {
        Alpine.start();
    });
}

// Replace jQuery AJAX with native Fetch API
// New helper function for AJAX requests
// This function will be used to set up default options for all AJAX requests
// and to make requests with those options.
// It will also handle CSRF tokens and other headers automatically.
// This is a simple wrapper around the Fetch API to make it easier to use
// and to ensure that all requests are made with the same default options.
// It will also handle errors and responses in a consistent way.
window.ajax = {
    setup(defaultOptions) {
        window.ajax.defaultOptions = defaultOptions;
    },
    request(url, options = {}) {
        const mergedOptions = { 
            ...window.ajax.defaultOptions,
            ...options,
            headers: {
                ...window.ajax.defaultOptions.headers,
                ...(options.headers || {})
            }
        };
        
        return fetch(url, mergedOptions);
    }
};

// Set default options for AJAX requests
// This will be used for all AJAX requests made with the ajax.request function
// It will include the CSRF token and other headers that are needed for the requests.
window.ajax.setup({
    headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    },
    credentials: 'same-origin'
});

// Initialize on DOMContentLoaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips and popovers (if used)
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    if (tooltipTriggerList.length > 0) {
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
    
    // Responsive menu toggle
    const mobileMenuButton = document.querySelector('[x-data]');
    if (mobileMenuButton) {
        mobileMenuButton.addEventListener('click', function() {
            document.dispatchEvent(new CustomEvent('toggle-mobile-menu'));
        });
    }

    // Initializing country select
    initCountrySelect();

    // Initialize currency manager for invoice forms
    if (document.getElementById('payment_currency') && document.getElementById('invoice-items-list')) {
        // Import the CurrencyManager dynamically to prevent errors if file doesn't exist
        import('./currency-manager.js')
            .then(module => {
                const CurrencyManager = module.default;
                window.currencyManager = new CurrencyManager();
                console.log('Currency manager initialized');
            })
            .catch(err => {
                console.error('Failed to load currency manager:', err);
            });
    }
});

// For fetch API, always use credentials: 'same-origin'
async function fetchWithSession(url, options = {}) {
    const defaultOptions = {
        credentials: 'same-origin',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    };
    
    const mergedOptions = { 
        ...defaultOptions,
        ...options,
        headers: {
            ...defaultOptions.headers,
            ...(options.headers || {})
        }
    };
    
    const response = await fetch(url, mergedOptions);
    
    // Check the response for authentication issues
    if (response.status === 401) {
        const data = await response.json();
        console.error('Authentication failed:', data);
        
        // Show dialog with login option
        if (confirm('Your session has expired. Would you like to log in again?')) {
            window.location.href = data.redirect || '/login';
        }
        throw new Error('Your session has expired.');
    }
    
    if (!response.ok) {
        throw new Error(`HTTP error! Status: ${response.status}`);
    }
    
    return response.json();
}

// Export for use in modules
window.fetchWithSession = fetchWithSession;

// Export SlugGenerator for use in other modules
window.SlugGenerator = SlugGenerator;
