<html class="no-js" lang="cs">
    <head>
        <title>Groww. - Šablony</title>
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
         <!-- end header -->
        <!-- start page title -->
       <section class="page-title-big-typography ipad-top-space-margin xs-py-0 background-position-center-top position-relative lg-overflow-hidden" style="background-image: url('images/demo-marketing-dot.svg')" data-anime='{ "opacity": [0, 1], "easing": "easeOutQuad" }'>
            <div class="bg-gradient-black-green position-absolute left-0px top-0px h-100 w-100 z-index-minus-1"></div>
            <div class="container">
                <div class="row align-items-center extra-small-screen">
                    <div class="col-9 col-lg-4 col-sm-6 position-relative page-title-extra-small" data-anime='{ "el": "childs", "opacity": [0, 1], "translateX": [-50, 0], "duration": 800, "delay": 0, "staggervalue": 150, "easing": "easeOutQuad" }'>
                        <h1 class="mb-20px text-base-color fw-500 text-uppercase ls-minus-05px">Pojďme spojit síly</h1>
                        <h2 class=" fw-700 text-dark-gray mb-0 ls-minus-2px">Vyberte si svou šablonu</h2>
                    </div>
                    <div class="col-lg-5 offset-lg-1 col-md-3 align-self-end d-none d-md-inline-block">
                        <div class="position-absolute right-0px top-80px md-right-minus-250px lg-right-minus-150px w-45 xl-w-55 lg-w-65 md-w-75 overflow-hidden">
                            <img src="" class="w-100" alt="" data-anime='{ "opacity": [0, 1], "translateX": [100, 0], "duration": 1000, "delay": 200, "easing": "easeOutQuad" }'>
                        </div>

                    </div>
                </div>
            </div>
            
        </section>
        <!-- end page title --> 
        <!-- start section -->
        <section class="overlap-height">
            <div class="container overlap-gap-section" data-anime='{ "opacity": [0,1], "duration": 600, "delay": 0, "staggervalue": 150, "easing": "easeOutQuad" }'>
                <div class="row align-items-center mb-5">
                    <div class="col-12 text-center text-md-start">
                        <!-- filter navigation -->
                        <ul class="portfolio-filter nav nav-tabs justify-content-center border-0">
                            <li class="nav active"><a data-filter="*" href="#">Vše</a></li>
                            <li class="nav"><a data-filter=".doporucujeme" href="#">Doporučujeme</a></li>
                            <li class="nav"><a data-filter=".sluzby" href="#">Služby</a></li>
                            <li class="nav"><a data-filter=".design" href="#">Design</a></li>
                            <li class="nav"><a data-filter=".tech" href="#">Technologie</a></li>
                        </ul>
                        <!-- end filter navigation --> 
                    </div>
                </div> 
                <div class="row">
                    <div class="col-12 filter-content p-md-0">
                        <ul class="portfolio-simple portfolio-wrapper grid-loading grid grid-3col xxl-grid-3col xl-grid-3col lg-grid-3col md-grid-2col sm-grid-1col xs-grid-1col gutter-extra-large text-center">
                            <li class="grid-sizer"></li>
                            <!-- start portfolio item -->
                            <li class="grid-item doporucujeme design transition-inner-all">
                                <div class="portfolio-box">
                                    <div class="portfolio-image border-radius-6px">
                                        <a href="demo-architecture.html">
                                            <img src="images/architecture.jpg" alt="" />
                                        </a>
                                    </div>
                                    <div class="portfolio-caption pt-30px pb-30px">
                                        <a href="demo-architecture.html" class="text-dark-gray text-dark-gray-hover fw-600">Architektura</a>
                                        <span class="d-inline-block align-middle w-10px separator-line-1px bg-light-gray ms-10px me-10px"></span>
                                        <div class="d-inline-block">Design</div>
                                    </div>
                                </div>
                            </li>
                            <!-- end portfolio item -->
                            <!-- start portfolio item -->
                            <li class="grid-item doporucujeme sluzby transition-inner-all">
                                <div class="portfolio-box">
                                    <div class="portfolio-image border-radius-6px">
                                        <a href="demo-beauty-salon.html">
                                            <img src="images/beauty.jpg" alt="" />
                                        </a> 
                                    </div>
                                    <div class="portfolio-caption pt-30px pb-30px">
                                        <a href="demo-beauty-salon.html" class="text-dark-gray text-dark-gray-hover fw-600">Salon krásy</a>
                                        <span class="d-inline-block align-middle w-10px separator-line-1px bg-light-gray ms-10px me-10px"></span>
                                        <div class="d-inline-block">Služby</div>
                                    </div>
                                </div>
                            </li>
                            <!-- end portfolio item -->
                            <!-- start portfolio item -->
                            <li class="grid-item sluzby transition-inner-all">
                                <div class="portfolio-box">
                                    <div class="portfolio-image border-radius-6px">
                                        <a href="demo-barber.html">
                                            <img src="https://placehold.co/800x635" alt="" />
                                        </a>
                                    </div>
                                    <div class="portfolio-caption pt-30px pb-30px">
                                        <a href="demo-barber.html" class="text-dark-gray text-dark-gray-hover fw-600">Barber Shop</a>
                                        <span class="d-inline-block align-middle w-10px separator-line-1px bg-light-gray ms-10px me-10px"></span>
                                        <div class="d-inline-block">Služby</div>
                                    </div>
                                </div>
                            </li>
                            <!-- end portfolio item -->
                            <!-- start portfolio item -->
                            <li class="grid-item sluzby transition-inner-all">
                                <div class="portfolio-box">
                                    <div class="portfolio-image border-radius-6px">
                                        <a href="demo-consulting.html">
                                            <img src="https://placehold.co/800x635" alt="" />
                                        </a> 
                                    </div>
                                    <div class="portfolio-caption pt-30px pb-30px">
                                        <a href="demo-consulting.html" class="text-dark-gray text-dark-gray-hover fw-600">Konzultace</a>
                                        <span class="d-inline-block align-middle w-10px separator-line-1px bg-light-gray ms-10px me-10px"></span>
                                        <div class="d-inline-block">Služby</div>
                                    </div>
                                </div>
                            </li>
                            <!-- end portfolio item -->
                            <!-- start portfolio item -->
                            <li class="grid-item tech sluzby doporucujeme transition-inner-all">
                                <div class="portfolio-box">
                                    <div class="portfolio-image border-radius-6px">
                                        <a href="demo-logistics.html">
                                            <img src="https://placehold.co/800x635" alt="" />
                                        </a> 
                                    </div>
                                    <div class="portfolio-caption pt-30px pb-30px">
                                        <a href="demo-logistics.html" class="text-dark-gray text-dark-gray-hover fw-600">Logistika</a>
                                        <span class="d-inline-block align-middle w-10px separator-line-1px bg-light-gray ms-10px me-10px"></span>
                                        <div class="d-inline-block">Služby</div>
                                    </div>
                                </div>
                            </li>
                            <!-- end portfolio item -->
                            <!-- start portfolio item -->
                            <li class="grid-item tech doporucujeme design sluzby transition-inner-all">
                                <div class="portfolio-box">
                                    <div class="portfolio-image border-radius-6px">
                                        <a href="demo-marketing-single-project-simple.html">
                                            <img src="https://placehold.co/800x635" alt="" />
                                        </a>
                                    </div>
                                    <div class="portfolio-caption pt-30px pb-30px">
                                        <a href="demo-marketing-single-project-simple.html" class="text-dark-gray text-dark-gray-hover fw-600">Violator series</a>
                                        <span class="d-inline-block align-middle w-10px separator-line-1px bg-light-gray ms-10px me-10px"></span>
                                        <div class="d-inline-block">Digital</div>
                                    </div>
                                </div>
                            </li>
                            <!-- end portfolio item --> 
                            <!-- start portfolio item -->
                            <li class="grid-item doporucujeme design transition-inner-all">
                                <div class="portfolio-box">
                                    <div class="portfolio-image border-radius-6px">
                                        <a href="demo-marketing-single-project-simple.html">
                                            <img src="https://placehold.co/800x635" alt="" />
                                        </a>
                                    </div>
                                    <div class="portfolio-caption pt-30px pb-30px">
                                        <a href="demo-marketing-single-project-simple.html" class="text-dark-gray text-dark-gray-hover fw-600">Tailoring inteo</a>
                                        <span class="d-inline-block align-middle w-10px separator-line-1px bg-light-gray ms-10px me-10px"></span>
                                        <div class="d-inline-block">Branding</div>
                                    </div>
                                </div>
                            </li>
                            <!-- end portfolio item -->
                            <!-- start portfolio item -->
                            <li class="grid-item tech sluzby transition-inner-all">
                                <div class="portfolio-box">
                                    <div class="portfolio-image border-radius-6px">
                                        <a href="demo-marketing-single-project-simple.html">
                                            <img src="https://placehold.co/800x635" alt="" />
                                        </a> 
                                    </div>
                                    <div class="portfolio-caption pt-30px pb-30px">
                                        <a href="demo-marketing-single-project-simple.html" class="text-dark-gray text-dark-gray-hover fw-600">Design blast</a>
                                        <span class="d-inline-block align-middle w-10px separator-line-1px bg-light-gray ms-10px me-10px"></span>
                                        <div class="d-inline-block">Photography</div>
                                    </div>
                                </div>
                            </li>
                            <!-- end portfolio item -->
                            <!-- start portfolio item -->
                            <li class="grid-item tech doporucujeme design sluzby transition-inner-all">
                                <div class="portfolio-box">
                                    <div class="portfolio-image border-radius-6px">
                                        <a href="demo-marketing-single-project-simple.html">
                                            <img src="https://placehold.co/800x635" alt="" />
                                        </a>
                                    </div>
                                    <div class="portfolio-caption pt-30px pb-30px">
                                        <a href="demo-marketing-single-project-simple.html" class="text-dark-gray text-dark-gray-hover fw-600">Herbal beauty</a>
                                        <span class="d-inline-block align-middle w-10px separator-line-1px bg-light-gray ms-10px me-10px"></span>
                                        <div class="d-inline-block">Application</div>
                                    </div>
                                </div>
                            </li>
                            <!-- end portfolio item -->
                            <!-- start portfolio item -->
                            <li class="grid-item tech doporucujeme design transition-inner-all">
                                <div class="portfolio-box">
                                    <div class="portfolio-image border-radius-6px">
                                        <a href="demo-marketing-single-project-simple.html">
                                            <img src="https://placehold.co/800x635" alt="" />
                                        </a> 
                                    </div>
                                    <div class="portfolio-caption pt-30px pb-30px">
                                        <a href="demo-marketing-single-project-simple.html" class="text-dark-gray text-dark-gray-hover fw-600">Cropo identity</a>
                                        <span class="d-inline-block align-middle w-10px separator-line-1px bg-light-gray ms-10px me-10px"></span>
                                        <div class="d-inline-block">Packaging</div>
                                    </div>
                                </div>
                            </li>
                            <!-- end portfolio item -->
                            <!-- start portfolio item -->
                            <li class="grid-item tech sluzby doporucujeme transition-inner-all">
                                <div class="portfolio-box">
                                    <div class="portfolio-image border-radius-6px">
                                        <a href="demo-marketing-single-project-simple.html">
                                            <img src="https://placehold.co/800x635" alt="" />
                                        </a> 
                                    </div>
                                    <div class="portfolio-caption pt-30px pb-30px">
                                        <a href="demo-marketing-single-project-simple.html" class="text-dark-gray text-dark-gray-hover fw-600">Harddot stone</a>
                                        <span class="d-inline-block align-middle w-10px separator-line-1px bg-light-gray ms-10px me-10px"></span>
                                        <div class="d-inline-block">Graphics</div>
                                    </div>
                                </div>
                            </li>
                            <!-- end portfolio item -->
                            <!-- start portfolio item -->
                            <li class="grid-item tech doporucujeme design sluzby transition-inner-all">
                                <div class="portfolio-box">
                                    <div class="portfolio-image border-radius-6px">
                                        <a href="demo-marketing-single-project-simple.html">
                                            <img src="https://placehold.co/800x635" alt="" />
                                        </a>
                                    </div>
                                    <div class="portfolio-caption pt-30px pb-30px">
                                        <a href="demo-marketing-single-project-simple.html" class="text-dark-gray text-dark-gray-hover fw-600">Violator series</a>
                                        <span class="d-inline-block align-middle w-10px separator-line-1px bg-light-gray ms-10px me-10px"></span>
                                        <div class="d-inline-block">Digital</div>
                                    </div>
                                </div>
                            </li>
                            <!-- end portfolio item --> 
                        </ul>
                    </div>
                </div>
            </div>
        </section>
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
        function getConsentCookie() {
            const match = document.cookie.match(/(^|;) ?cookie_consent=([^;]*)(;|$)/);
            return match ? match[2] : null;
        }

        // Only load Google Analytics if user accepted all cookies
        if (getConsentCookie() === 'all') {
            // Insert your Google Analytics/gtag code here
        }
        </script>


       
        <!-- javascript libraries -->
          <script src="js/cookie-consent.js"></script>
        <script type="text/javascript" src="js/jquery.js"></script>
        <script type="text/javascript" src="js/vendors.min.js"></script>
        <script type="text/javascript" src="js/main.js"></script>
    </body>
</html>