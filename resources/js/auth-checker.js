// Function to check authentication status and refresh session
function setupAuthChecker() {
    // Controlling the interval for checking authentication
    // This is set to 5 minutes (300000 ms) for demonstration purposes
    const checkInterval = 5 * 60 * 1000; // 5 minut v ms
    
    // Function to check authentication status
    async function checkAuthentication() {
        try {
            // Quick API call to verify login validity
            const response = await fetch('/api/auth-check', {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });
            
            if (!response.ok) {
                // If we get a 401 Unauthorized response, we can assume the session has expired
                // and we can prompt the user to log in again
                if (response.status === 401) {
                    if (confirm('Vaše přihlášení vypršelo. Chcete se přihlásit znovu?')) {
                        window.location.reload();
                    }
                }
            }
        } catch (error) {
            console.error('Chyba při kontrole autentizace:', error);
        }
    }
    
    // Set up the interval to check authentication every 5 minutes
    setInterval(checkAuthentication, checkInterval);
    
    // Set up event listeners for user activity - each action will extend the session
    const userActivityEvents = ['mousedown', 'keydown', 'touchstart', 'scroll'];
    
    let activityTimeout;
    const activityDelay = 30 * 1000; // 30 seconds between activity checks
    
    function handleUserActivity() {
        clearTimeout(activityTimeout);
        
        activityTimeout = setTimeout(() => {
            // After 30 seconds of inactivity, we will make an API call to refresh the session
            fetch('/api/session-refresh', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            }).catch(error => console.error('Error while refreshing session:', error));
        }, activityDelay);
    }
    
    // Set up event listeners for user activity - each action will extend the session
    userActivityEvents.forEach(eventType => {
        document.addEventListener(eventType, handleUserActivity);
    });
    
    // Initial check
    handleUserActivity();
}

// Start after the page has loaded
document.addEventListener('DOMContentLoaded', setupAuthChecker);
