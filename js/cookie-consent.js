//cookie-consent.js

(function() {
    // Utility: Set consent cookie
    function setConsentCookie(status) {
        document.cookie = "cookie_consent=" + status + "; path=/; max-age=" + (60*60*24*365) + "; SameSite=Strict";
    }

    // Utility: Get consent cookie
    function getConsentCookie() {
        var match = document.cookie.match(/(^|;) ?cookie_consent=([^;]*)(;|$)/);
        return match ? match[2] : null;
    }

    // Utility: Hide cookie banner
    function hideCookieBanner() {
        var banner = document.getElementById('cookies-model');
        if (banner) banner.style.display = 'none';
    }

    // Send consent status to server
    function sendConsentToServer(status) {
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

    // Button event handlers
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
                // Optionally: initialize Google Analytics here
            });
        }

        if (rejectBtn) {
            rejectBtn.addEventListener('click', function(e) {
                e.preventDefault();
                setConsentCookie('rejected');
                sendConsentToServer('rejected');
                hideCookieBanner();
                // Optionally: block Google Analytics here
            });
        }

        if (optionsBtn) {
            optionsBtn.addEventListener('click', function(e) {
                e.preventDefault();
                // Show your custom options modal here
                alert('Custom cookie options coming soon!');
            });
        }
    }

    // Initialize on DOMContentLoaded
    document.addEventListener('DOMContentLoaded', setupConsentButtons);

    // Expose getConsentCookie globally if needed
    window.getConsentCookie = getConsentCookie;
})();
