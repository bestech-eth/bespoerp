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
<title>Our new web site has been launched</title>
<meta charset="utf-8">
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<meta name="robots" content="index, follow" />
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="keywords" content="" />
<meta name="title" content="Our new web site has been launched" />
<meta name="description" content="Our new website, based on Dolibarr CMS, has been launched. Modern and directly integrated with the internal management tools of the company, many new online services for our customers will be able to see the day..." />
<meta name="generator" content="Dolibarr 17.0.0-alpha (https://www.bespo.et)" />
<meta name="dolibarr:pageid" content="181" />
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
<body id="bodywebsite" class="bodywebsite bodywebpage-blog-our-new-web-site-has-been-launched">
<?php includeContainer('header'); ?>

      <section id="sectionimage" contenteditable="true">
        <div class="">
          <div class="swiper-wrapper text-center" style="transform: translate3d(0px, 0px, 0px); transition-duration: 0ms;">
            <div class="swiper-slide swiper-slide-active" style="height: 200px; background-image: url('medias/image/template-corporate/background_sunset.webp'); background-size: cover;">
              <div class="swiper-slide-caption">
                <div class="container">
                  <div class="row justify-content-sm-center">
                    <div class="col-md-11 col-lg-10">
                      <div class="text-white text-uppercase border-modern fadeInUp animated" data-caption-animate="fadeInUp" data-caption-delay="0s" style="font-size: 2em"><?php echo $websitepage->title; ?></span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
        </div>
      </section>

        <div class="container">
            <br><br><br><br><br><br>


            Our new website, based on Dolibarr CMS, has been launched. <br>
            Now it is modern and directly integrated with the internal management tools of the company. Many new online services will be available for our customers...

            
                        <br><br><br>
            
            <figure>
            <img src="/viewimage.php?modulepart=medias&file=image/template-corporate/background_rough-horn.webp">
            <br><br>
            <figcaption style="opacity: 0.5; text-align: center;">Theme of our new web site</figcaption>
            </figure>
            

            <br><br><br><br><br><br>
        </div>


<?php includeContainer('footer'); ?>




</body>
</html>
<?php // BEGIN PHP
$tmp = ob_get_contents(); ob_end_clean(); dolWebsiteOutput($tmp, "html", 181);
// END PHP ?>
