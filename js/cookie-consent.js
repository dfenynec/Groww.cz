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
    // Google Analytics 4
    var gaScript = document.createElement('script');
    gaScript.async = true;
    gaScript.src = "https://www.googletagmanager.com/gtag/js?id=G-G31QGDFGTW";
    document.head.appendChild(gaScript);

    gaScript.onload = function() {
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', 'G-G31QGDFGTW');
    };

  // Meta Pixel
    !function(f,b,e,v,n,t,s)
    {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
    n.callMethod.apply(n,arguments):n.queue.push(arguments)};
    if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
    n.queue=[];t=b.createElement(e);t.async=!0;
    t.src=v;s=b.getElementsByTagName(e)[0];
    s.parentNode.insertBefore(t,s)}(window, document,'script',
    'https://connect.facebook.net/en_US/fbevents.js');
    fbq('init', 'TVŮJ_PIXEL_ID');
    fbq('track', 'PageView');

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
