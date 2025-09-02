// /js/cookie-consent.js

(function() {
    function setConsentCookie(status) {
        document.cookie = "cookie_consent=" + status + "; path=/; max-age=" + (60*60*24*365) + "; SameSite=Strict";
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
    function sendConsentToPHP(status) {
        fetch('/assets/php/save-consent.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                consent: status,
                timestamp: new Date().toISOString(),
                userAgent: navigator.userAgent
            })
        });
    }

    // Send to SheetsDB endpoint
    function sendConsentToSheetsDB(status) {
        fetch('https://sheetdb.io/api/v1/abza59bhpfpzo', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                   Timestamp: new Date().toISOString(),
                    Consent: status,
                    UserAgent: navigator.userAgent,
                    PageURL: window.location.href,
                    Language: navigator.language
                // IP will be logged by SheetsDB automatically
            })
        });
    }

    // Call both endpoints
    function sendConsentToServer(status) {
        sendConsentToPHP(status);
        sendConsentToSheetsDB(status);
    }

    function setupConsentButtons() {
        var acceptBtn = document.querySelector('.accept_cookies_btn');
        var rejectBtn = document.querySelector('.reject_cookies_btn');
        var optionsBtn = document.querySelector('.cookie_options_btn');

        if (acceptBtn) {
            acceptBtn.addEventListener('click', function(e) {
                e.preventDefault();
                setConsentCookie('accepted');
                sendConsentToServer('accepted');
                hideCookieBanner();
            });
        }

        if (rejectBtn) {
            rejectBtn.addEventListener('click', function(e) {
                e.preventDefault();
                setConsentCookie('rejected');
                sendConsentToServer('rejected');
                hideCookieBanner();
            });
        }

        if (optionsBtn) {
            optionsBtn.addEventListener('click', function(e) {
                e.preventDefault();
                alert('Custom cookie options coming soon!');
            });
        }
    }

    document.addEventListener('DOMContentLoaded', setupConsentButtons);

    window.getConsentCookie = getConsentCookie;
})();
