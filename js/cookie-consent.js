// /js/cookie-consent.js
(function() {
    function setConsentCookie(type) {
        document.cookie = "cookie_consent=" + type + "; path=/; max-age=" + (60*60*24*365) + "; SameSite=Strict";
    }

    function getConsentCookie() {
        var match = document.cookie.match(/(^|;) ?cookie_consent=([^;]*)(;|$)/);
        return match ? match[2] : null;
    }

    function hideCookieBanner() {
        var banner = document.getElementById('cookies-model');
        if (banner) banner.style.display = 'none';
    }

    // Send to PHP endpoint
    function sendConsentToPHP(type) {
        fetch('/assets/php/save-consent.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                consent: type,
                timestamp: new Date().toISOString(),
                userAgent: navigator.userAgent,
                pageUrl: window.location.href
            })
        });
    }

    // Send to SheetsDB endpoint
    function sendConsentToSheetsDB(type) {
        fetch('https://sheetdb.io/api/v1/abza59bhpfpzo', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                Timestamp: new Date().toISOString(),
                Consent: type,
                UserAgent: navigator.userAgent,
                PageURL: window.location.href
            })
        });
    }

    function sendConsentToServer(type) {
        sendConsentToPHP(type);
        sendConsentToSheetsDB(type);
    }

    function enableAllCookies() {
        // Initialize Google Analytics, marketing, etc.
        // Example: load GA4 here if not already loaded
    }

    function enableOnlyNecessaryCookies() {
        // Do not load analytics/marketing scripts
        // Only essential cookies/scripts should run
    }

    function setupConsentButtons() {
        var acceptAllBtn = document.querySelector('.accept_all_btn');
        var acceptNecessaryBtn = document.querySelector('.accept_necessary_btn');

        if (acceptAllBtn) {
            acceptAllBtn.addEventListener('click', function(e) {
                e.preventDefault();
                setConsentCookie('all');
                sendConsentToServer('all');
                enableAllCookies();
                hideCookieBanner();
            });
        }

        if (acceptNecessaryBtn) {
            acceptNecessaryBtn.addEventListener('click', function(e) {
                e.preventDefault();
                setConsentCookie('necessary');
                sendConsentToServer('necessary');
                enableOnlyNecessaryCookies();
                hideCookieBanner();
            });
        }
    }

    document.addEventListener('DOMContentLoaded', setupConsentButtons);

    window.getConsentCookie = getConsentCookie;
})();
