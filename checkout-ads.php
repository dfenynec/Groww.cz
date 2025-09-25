<html class="no-js" lang="cs">
    <head>
        <title>Groww. - Objednávka</title>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="author" content="David Fenynec">
        <meta name="viewport" content="width=device-width,initial-scale=1.0" />
        <meta name="description" content="Tvoříme rychlé a spolehlivé webové stránky pro klienty z celého světa. Spusťte moderní web, který přivádí zákazníky.">
        <!-- favicon icon -->
        <link rel="shortcut icon" href="images/favicon.png">
        <link rel="apple-touch-icon" href="images/apple-touch-icon-57x57.png">
        <link rel="apple-touch-icon" sizes="72x72" href="images/apple-touch-icon-72x72.png">
        <link rel="apple-touch-icon" sizes="114x114" href="images/apple-touch-icon-114x114.png">
        <!-- google fonts preconnect -->
        <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <!-- style sheets and font icons  -->
        <link rel="stylesheet" href="css/vendors.min.css"/>
        <link rel="stylesheet" href="css/icon.min.css"/>
        <link rel="stylesheet" href="css/style.css"/>
        <link rel="stylesheet" href="css/responsive.css"/>
        <link rel="stylesheet" href="demos/marketing/marketing.css" />
    </head>
    <body data-mobile-nav-style="classic" class="custom-cursor">
        <!-- start cursor -->
        <div class="cursor-page-inner">
            <div class="circle-cursor circle-cursor-inner"></div>
            <div class="circle-cursor circle-cursor-outer"></div>
        </div>
        <!-- end cursor -->
        <!-- start header -->
       <?php include 'assets/php/header.php'; ?>          
         <!-- end header -->  
  <!-- start section -->
   

<section class="pt-5">
  <div class="container">
    <!-- end section 
    <div class="row justify-content-center mb-8 lg-mb-10 align-items-center">
      <div class="col-auto icon-with-text-style-08 lg-mb-10px">
        <div class="feature-box feature-box-left-icon">
          <div class="feature-box-icon me-5px">
            <i class="feather icon-feather-user top-9px position-relative text-dark-gray icon-small"></i>
          </div> 
          <div class="feature-box-content">
            <span class="d-inline-block text-dark-gray align-middle alt-font fw-500">Jste již naším klientem? <a href="#" class="text-decoration-line-bottom fw-600 text-dark-gray">Přihlaste se</a></span>
          </div>
        </div>
      </div>
      <div class="col-auto d-none d-lg-inline-block">
        <span class="w-1px h-20px bg-extra-medium-gray d-block"></span>
      </div>
      <div class="col-auto icon-with-text-style-08">
        <div class="feature-box feature-box-left-icon">
          <div class="feature-box-icon me-5px">
            <i class="feather icon-feather-scissors top-9px position-relative text-dark-gray icon-small"></i>
          </div>
          <div class="feature-box-content">
            <span class="d-inline-block text-dark-gray align-middle alt-font fw-500">Máte slevový kód? <a href="#" class="text-decoration-line-bottom fw-600 text-dark-gray">Zadejte ho zde</a></span>
          </div>
        </div>
      </div>
    </div>-->
    <div class="row align-items-start">
      <div class="col-lg-7 pe-50px md-pe-15px md-mb-50px xs-mb-35px">
        <span class="fs-26 alt-font fw-600 text-dark-gray mb-20px d-block">Fakturační údaje</span>
        <form id="objednavka-form" autocomplete="off" novalidate class="row">
          <div class="row">
            <div class="col-md-6 mb-20px">
              <label class="mb-10px ">Vybraná služba</label>
              <input class="border-radius-4px text-dark-gray input-small fw-600 bg-yellow" type="text" name="sablona" id="sablona" value="Reklama" readonly>
            </div>
            <div class="col-md-6 mb-20px">
              <label class="mb-10px">Inzertní systémy <span class="text-red">*</span></label>
              <select class="form-select select-small required" name="hosting" id="hosting" >
                <option value="">Používáte Google Ads nebo Sklik?</option>
                <option value="Ano">Ano</option>
                <option value="Registrovat">Zatím ne</option>
              </select>
            </div>
            <div class="col-md-6 mb-20px">
              <label class="mb-10px">Jméno <span class="text-red">*</span></label>
              <input class="border-radius-4px required input-small form-control" type="text" name="jmeno" id="jmeno" >
            </div>
            <div class="col-md-6 mb-20px">
              <label class="mb-10px">Příjmení <span class="text-red">*</span></label>
              <input class="border-radius-4px required input-small form-control" type="text" name="prijmeni" id="prijmeni" >
            </div>
            <div class="col-12 mb-20px">
              <label class="mb-10px">Email <span class="text-red">*</span></label>
              <input class="border-radius-4px required input-small form-control" type="email" name="email" id="email" >
            </div>
            <div class="col-12 mb-20px">
              <label class="mb-10px">Web pro reklamu <span class="text-red">*</span></label>
              <input class="border-radius-4px required input-small form-control" type="text" name="domena" id="domena" placeholder="např. mojedomena.cz" >
            </div>
            
            
          </div>
          
        </form>
        
      </div>
      <div class="col-lg-5">
        <div class="bg-very-light-gray border-radius-6px p-50px lg-p-25px your-order-box">
          <span class="fs-26 alt-font fw-600 text-dark-gray mb-5px d-block">Vaše objednávka</span>
          <table class="w-100 total-price-table your-order-table">
            <tbody>
              <tr>
                <th class="w-60 lg-w-55 xs-w-50 fw-600 text-dark-gray alt-font">Služba</th>
                <td class="fw-600 text-dark-gray alt-font">Inzertní systémy</td>
              </tr>
              <tr class="product">
                <td class="product-thumbnail">
                  <span class="product-price" id="order-sablona"></span>
                </td>
                <td class="product-price" id="order-hosting"></td>
              </tr>
              <tr class="total-amount">
                    <th class="fw-600 text-dark-gray alt-font">Celkem</th>
                    <td data-title="Total">
                        <h6 class="d-block fw-700 mb-0 text-dark-gray alt-font" data-title="Price" id="order-cena">0 Kč</h6>
                    </td>
                     
                </tr>
               
            </tbody>
            
          </table>
          <!-- ZACHOVÁNO: původní platební metody, tabulky, texty -->
            <span class="fs-14">1. měsíc zdarma - potom 2 990 Kč/měsíc</span>
          <div class="p-40px lg-p-25px bg-white border-radius-6px box-shadow-large mt-10px mb-30px sm-mb-25px checkout-accordion">
            <div class="w-100" id="accordion-style-05">
             <div class="heading active-accordion">
                <label class="mb-5px">
                  <input class="d-inline w-auto me-5px mb-0 p-0" type="radio" name="payment_option" value="stripe" form="objednavka-form">
                  <span class="d-inline-block text-dark-gray fw-500">Online kartou <img src="images/payment.png" class="w-120px ms-10px" alt=""/></span>
                  <a class="accordion-toggle" data-bs-toggle="collapse" data-bs-parent="#accordion-style-05" href="#style-5-collapse-3"></a>
                </label>
              </div>
              <div id="style-5-collapse-3" class="collapse" data-bs-parent="#accordion-style-05">
                <div class="p-25px bg-very-light-gray mt-20px mb-20px fs-14 lh-24">Plaťte pohodlně online, bezpečně přes platební bránu Stripe. Platba odchází z karty automaticky každý měsíc. Přístup k platbě vám zašleme emailem.</div>
              </div>
            </div>
          </div>
          <p class="fs-14 mb-5 lh-24"> <b>Upozornění:</b> První měsíc správy reklam je zdarma. Po objednání budete přesměrováni na platební bránu a budeme vás kontaktovat ohledně nastavení přístupu k reklamním systémům. Od druhého měsíce je služba zpoplatněna dle platného ceníku, můžete ji kdykoliv zrušit.</p>
          <div class="position-relative terms-condition-box text-start d-flex align-items-center">
            <label>
              <input type="checkbox" form="objednavka-form" name="terms_condition" value="1" id="terms_condition" class="check-box terms-condition-box required align-middle" required>
              <span class="box required fs-14 lh-28">Souhlasím s <a href="#" class="text-decoration-line-bottom text-dark-gray fw-500">obchodními podmínkami</a> a <a href="#" class="text-decoration-line-bottom text-dark-gray fw-500">zásadami zpracování osobních údajů</a>.<span class="text-red">*</span></span>
            </label>
          </div>
          <p class="fs-14 lh-24"><span class="text-red">*</span> Povinné.</p>
         <button type="submit" form="objednavka-form" class="btn btn-dark-gray btn-large btn-switch-text btn-round-edge btn-box-shadow w-100 mt-30px">
<span>
<span class="btn-double-text" data-text="s měsícem zdarma">Objednat reklamu</span>
</span>
</button>
<div id="potvrzeni" class="form-results mt-20px d-none" ></div>

        </div>
      </div>
    </div>
  </div>
</section>
        <!-- end section --> 
          <!-- end section -->
         <!-- start footer -->
     <?php include 'assets/php/footer.php'; ?>    
        <!-- start subscription popup -->
        <!-- (beze změny, skryto) -->
        <!-- end subscription popup -->
         <!-- start scroll progress -->
        <div class="scroll-progress d-none d-xxl-block">
          <a href="#" class="scroll-top" aria-label="scroll">
            <span class="scroll-text">Scroll</span><span class="scroll-line"><span class="scroll-point"></span></span>
          </a>
        </div>
        <!-- end scroll progress -->
         <!-- start cookies model -->
        <div id="cookies-model" class="cookie-message bg-dark-gray border-radius-8px" style="display: block;">
            <div class="cookie-description fs-14 text-white mb-20px lh-22">
                Cookies tu máme, aby všechno šlapalo jak má. A věřte - na vašem novém webu je určitě taky oceníte!
            </div>
            <div class="cookie-btn">
                <a href="#" class="btn btn-transparent-white border-1 border-color-transparent-white-light btn-very-small btn-switch-text btn-rounded w-100 mb-15px accept_necessary_btn " aria-label="btn">
                    <span>
                        <span class="btn-double-text" data-text="jenom pár">Pouze nezbytné</span>
                    </span>
                </a>
                <a href="#" class="btn btn-white btn-very-small btn-switch-text btn-box-shadow btn-rounded accept_all_btn w-100 mb-15px" data-accept-btn="" aria-label="text">
                    <span>
                        <span class="btn-double-text" data-text="všechny sušenky">Přijmout vše</span>
                    </span>
                </a>
            </div>
            <div class="fs-14 text-center align-items-center ">
                <a class="text-decoration-line-bottom fs-12 cookie_policy_link" href="#">Více o Cookies</a>
            </div>
            
        </div>


        <!-- end cookies model -->
        
      
        <!-- javascript libraries -->
     
        <script>
// Najdi potřebné prvky
const sablonaInput = document.getElementById('sablona');
const orderSablona = document.getElementById('order-sablona');
const orderHosting = document.getElementById('order-hosting');
const potvrzeni = document.getElementById('potvrzeni');
const form = document.getElementById('objednavka-form');

// Dynamicky zobraz vybraný hosting v objednávce
const hostingSelect = document.getElementById('hosting');
if (hostingSelect) {
  hostingSelect.addEventListener('change', function() {
    if (orderHosting) orderHosting.textContent = this.options[this.selectedIndex].text;
  });
}

// Submit handler s validací a přesměrováním na Stripe
form.addEventListener('submit', function(e) {
  e.preventDefault();

  // 1) Spusť nativní validaci. Pokud něco chybí, ukonči a nic neodesílej.
  if (!form.reportValidity()) {
    return;
  }

  // 2) Odešli data na PHP endpoint
  const formData = new FormData(form);

  // Volitelně: disable tlačítko během odesílání
  const submitBtn = document.querySelector('button[form="objednavka-form"], button#order-submit, button[type="submit"]');
  if (submitBtn) submitBtn.disabled = true;

  fetch('assets/php/objednavka-reklama.php', {
    method: 'POST',
    body: formData
  })
  .then(r => r.json()) // Očekáváme JSON s platebním linkem
  .then(response => {
    // Zobrazit potvrzení
    if (potvrzeni) {
      potvrzeni.classList.remove('d-none');
      potvrzeni.innerHTML = response.message || "Objednávka byla úspěšně odeslána.";
    }
    // Reset formuláře
    form.reset();
    if (sablonaInput) sablonaInput.value = '';
    if (orderSablona) orderSablona.textContent = '';
    if (orderHosting) orderHosting.textContent = '';

    // 3) Přesměrování na Stripe platební link
    if (response.stripe_url) {
      window.location.href = response.stripe_url;
    } else {
      potvrzeni.innerHTML += "<br><b>Platební odkaz nebyl vygenerován.</b>";
    }
  })
  .catch(() => {
    if (potvrzeni) {
      potvrzeni.classList.remove('d-none');
      potvrzeni.innerHTML = "<b>Chyba při odesílání. Zkuste to prosím znovu.</b>";
    }
  })
  .finally(() => {
    if (submitBtn) submitBtn.disabled = false;
  });
});

// Při načtení předvyplň hosting v rekapitulaci, pokud je zvolen
document.addEventListener('DOMContentLoaded', function() {
  if (hostingSelect && hostingSelect.value && orderHosting) {
    orderHosting.textContent = hostingSelect.options[hostingSelect.selectedIndex].text;
  }
});
        </script>
       
        <!-- javascript libraries -->
          <script src="js/cookie-consent.js"></script>
        <script type="text/javascript" src="js/jquery.js"></script>
        <script type="text/javascript" src="js/vendors.min.js"></script>
        <script type="text/javascript" src="js/main.js"></script>
    </body>
</html>