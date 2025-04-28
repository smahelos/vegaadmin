// Funkce pro kontrolu platnosti autentizace
function setupAuthChecker() {
    // Pravidelně kontrolujeme stav autentizace (každých 5 minut)
    const checkInterval = 5 * 60 * 1000; // 5 minut v ms
    
    // Funkce pro provedení kontroly
    async function checkAuthentication() {
        try {
            // Rychlé volání API pro ověření platnosti přihlášení
            const response = await fetch('/api/auth-check', {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });
            
            if (!response.ok) {
                // Pokud nejsme přihlášeni, necháme uživatele znovu přihlásit
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
    
    // Nastavíme interval pro kontrolu
    setInterval(checkAuthentication, checkInterval);
    
    // Nastavíme event listenery pro aktivitu uživatele - každá akce prodlouží session
    const userActivityEvents = ['mousedown', 'keydown', 'touchstart', 'scroll'];
    
    let activityTimeout;
    const activityDelay = 30 * 1000; // 30 sekund mezi kontrolami aktivity
    
    function handleUserActivity() {
        clearTimeout(activityTimeout);
        
        activityTimeout = setTimeout(() => {
            // Po 30 sekundách od poslední aktivity provedeme API volání pro prodloužení session
            fetch('/api/session-refresh', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            }).catch(error => console.error('Chyba při obnovení session:', error));
        }, activityDelay);
    }
    
    // Přidáme event listenery
    userActivityEvents.forEach(eventType => {
        document.addEventListener(eventType, handleUserActivity);
    });
    
    // Iniciální kontrola
    handleUserActivity();
}

// Spuštění po načtení stránky
document.addEventListener('DOMContentLoaded', setupAuthChecker);