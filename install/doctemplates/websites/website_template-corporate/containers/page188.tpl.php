<?php // BEGIN PHP
$websitekey=basename(__DIR__); if (empty($websitepagefile)) $websitepagefile=__FILE__;
if (! defined('USEDOLIBARRSERVER') && ! defined('USEDOLIBARREDITOR')) {
	$pathdepth = count(explode('/', $_SERVER['SCRIPT_NAME'])) - 2;
	require_once ($pathdepth ? str_repeat('../', $pathdepth) : './').'master.inc.php';
} // Not already loaded
require_once DOL_DOCUMENT_ROOT.'/core/lib/website.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/website.inc.php';
ob_start();
// END PHP ?>
<html lang="en">
<head>
<title>Header and Top Menu</title>
<meta charset="utf-8">
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<meta name="robots" content="index, follow" />
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="keywords" content="" />
<meta name="title" content="Header and Top Menu" />
<meta name="description" content="Header with menu" />
<meta name="generator" content="Dolibarr 17.0.0-alpha (https://www.bespo.et)" />
<meta name="dolibarr:pageid" content="188" />
<?php if ($website->use_manifest) { print '<link rel="manifest" href="/manifest.json.php" />'."\n"; } ?>
<!-- Include link to CSS file -->
<link rel="stylesheet" href="/styles.css.php?website=<?php echo $websitekey; ?>" type="text/css" />
<!-- Include link to JS file -->
<script async src="/javascript.js.php"></script>
<!-- Include HTML header from common file -->
<?php if (file_exists(DOL_DATA_ROOT."/website/".$websitekey."/htmlheader.html")) include DOL_DATA_ROOT."/website/".$websitekey."/htmlheader.html"; ?>
<!-- Include HTML header from page header block -->

</head>
<!-- File generated by Dolibarr website module editor -->
<body id="bodywebsite" class="bodywebsite bodywebpage-header">
<!-- JQuery + Other js libs -->
<script src="/document.php?modulepart=medias&file=js/template-corporate/extlib.min.js"></script>


<div class="dolcontenteditable dolsticky" contenteditable="true"><div class="div-page-head">
      <header class="page-head">
        <div class="rd-navbar-wrap" style="height: 106px;">
          <nav class="rd-navbar rd-navbar-corporate-dark rd-navbar-original rd-navbar-static" 
                data-layout="rd-navbar-fixed" data-sm-layout="rd-navbar-fixed" data-md-device-layout="rd-navbar-fixed" 
                data-md-layout="rd-navbar-static" data-lg-layout="rd-navbar-static" data-xl-layout="rd-navbar-static" data-xxl-layout="rd-navbar-static"
                data-lg-device-layout="rd-navbar-static" data-xl-device-layout="rd-navbar-static" data-xxl-device-layout="rd-navbar-static"
                data-stick-up-clone="false" data-md-stick-up-offset="53px" data-lg-stick-up-offset="53px" data-md-stick-up="true" data-lg-stick-up="true">
            <div class="rd-navbar-inner">
              
              <div class="rd-navbar-group rd-navbar-search-wrap toggle-original-elements">
                <div class="rd-navbar-panel">
                  <button class="rd-navbar-toggle" data-custom-toggle=".rd-navbar-nav-wrap" data-custom-toggle-disable-on-blur="true"><span></span></button><a class="rd-navbar-brand brand" href="home.php">
                      <img src="/viewimage.php?modulepart=mycompany&file=logos/<?php echo getImageFileNameForSize($mysoc->logo_squarred ? $mysoc->logo_squarred : $mysoc->logo, '_small') ?>" alt="" height="34"></a>
                </div>
                <div class="rd-navbar-nav-wrap">
                  <div class="rd-navbar-nav-inner">
                    <div class="rd-navbar-search toggle-original-elements">
                      <form class="rd-search" action="search.php" method="POST" data-search-live="rd-search-results-live">
                        <input type="hidden" name="token" value="<?php echo newToken(); ?>" />
                        <div class="form-wrap">
                          <input class="form-input" id="rd-search-form-input" type="text" name="s" autocomplete="off" placeholder="Search...">
                          <div class="rd-search-results-live cleared" id="rd-search-results-live"></div>
                        </div>
                        <button class="rd-search-submit buttonwithnoborder" type="submit"><span class="icon icon-xs icon-dusty-gray fa fa-search"></span></button>
                      </form>
                      <button class="buttonwithnoborder" data-rd-navbar-toggle=".rd-navbar-search, .rd-navbar-search-wrap">
                          <span class="icon icon-xs icon-dusty-gray fa fa-search"></span>
                      </button>
                    </div>
                    
                    <!-- Tree with menu -->
                    <ul class="rd-navbar-nav">
                      <li class="active"><a href="/">Home</a></li>
                      <li class="rd-navbar--has-megamenu rd-navbar-submenu"><a href="#">Products | Services</a>
                        <ul class="rd-navbar-megamenu rd-navbar-open-right">
                          <li>
                            <h4 class="rd-megamenu-header">Products and Services</h4>
                            <ul class="rd-navbar-list">
                              <li><a href="product-p.php">Product P</a></li>
                              <li><a href="service-s.php">Service S</a></li>
                            </ul>
                          </li>
                          <li>
                            <h4 class="rd-megamenu-header">Pricing</h4>
                            <ul class="rd-navbar-list">
                              <li><a href="pricing.php">Pricing</a></li>
                            </ul>
                          </li>
                        </ul>
                      </li>
                      <li><a href="blog.php">Blog</a></li>
                      <li class="rd-navbar--has-megamenu rd-navbar-submenu"><a href="#">Our company</a>
                        <ul class="rd-navbar-megamenu rd-navbar-open-right">
                          <li>
                            <h4 class="rd-megamenu-header">Our business</h4>
                            <ul class="rd-navbar-list">
                              <li><a href="clients-testimonials.php">Clients testimonials</a></li>
                              <li><a href="partners.php">Partners</a></li>
                            </ul>
                          </li>
                          <li>
                            <h4 class="rd-megamenu-header">Human Resources</h4>
                            <ul class="rd-navbar-list">
                              <li><a href="our-team.php">Our team</a></li>
                              <li><a href="careers.php">Careers</a></li>
                            </ul>
                          </li>
                          <li>
                            <h4 class="rd-megamenu-header">Other</h4>
                            <ul class="rd-navbar-list">
                              <li><a href="faq.php">FAQ</a></li>
                              <li><a href="search.php">Search</a></li>
                              <li><a href="contact.php">Contact us</a></li>
                              <li><a href="privacy-policies.php">Privacy policies</a></li>
                            </ul>
                          </li>
                        </ul>
                      </li>
                      <li><a href="contact.php">Contacts</a></li>
                    </ul>
                    
                  </div>
                </div>
              </div>
            </div>
          </nav>
        </div>
      </header>
</div></div>

</body>
</html>
<?php // BEGIN PHP
$tmp = ob_get_contents(); ob_end_clean(); dolWebsiteOutput($tmp, "html", 188);
// END PHP ?>
