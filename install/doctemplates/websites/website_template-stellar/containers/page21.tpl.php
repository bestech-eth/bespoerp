<?php // BEGIN PHP
$websitekey=basename(__DIR__); if (empty($websitepagefile)) $websitepagefile=__FILE__;
if (! defined('USEDOLIBARRSERVER') && ! defined('USEDOLIBARREDITOR')) {
	$pathdepth = count(explode('/', $_SERVER['SCRIPT_NAME'])) - 2;
	require_once $pathdepth ? str_repeat('../', $pathdepth) : './'.'master.inc.php';
} // Not already loaded
require_once DOL_DOCUMENT_ROOT.'/core/lib/website.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/website.inc.php';
ob_start();
// END PHP ?>
<html lang="fr">
<head>
<title>Footer</title>
<meta charset="utf-8">
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<meta name="robots" content="index, follow" />
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="keywords" content="" />
<meta name="title" content="Footer" />
<meta name="description" content="" />
<meta name="generator" content="Dolibarr 14.0.0-alpha (https://www.bespo.et)" />
<meta name="dolibarr:pageid" content="21" />
<?php if ($website->use_manifest) { print '<link rel="manifest" href="/manifest.json.php" />'."\n"; } ?>
<!-- Include link to CSS file -->
<link rel="stylesheet" href="/styles.css.php?website=<?php echo $websitekey; ?>" type="text/css" />
<!-- Include link to JS file -->
<script src="/javascript.js.php"></script>
<!-- Include HTML header from common file -->
<?php if (file_exists(DOL_DATA_ROOT."/website/".$websitekey."/htmlheader.html")) include DOL_DATA_ROOT."/website/".$websitekey."/htmlheader.html"; ?>
<!-- Include HTML header from page header block -->

</head>
<!-- File generated by Dolibarr website module editor -->
<body id="bodywebsite" class="bodywebsite bodywebpage-footer">
				<!-- Footer -->
					<footer id="footer">
						<section contenteditable="true">
							<h2>Aliquam sed mauris</h2>
							<p>Sed lorem ipsum dolor sit amet et nullam consequat feugiat consequat magna adipiscing tempus etiam dolore veroeros. eget dapibus mauris. Cras aliquet, nisl ut viverra sollicitudin, ligula erat egestas velit, vitae tincidunt odio.</p>
							<ul class="actions">
								<li><a href="generic.php" class="buttonwebsite">Learn More</a></li>
							</ul>
						</section>
						<section contenteditable="true">
							<h2>Etiam feugiat</h2>
							<dl class="alt">
								<dt>Address</dt>
								<dd><?php echo $mysoc->getFullAddress(1, '<br>'); ?></dd>
								<dt>Phone</dt>
								<dd><?php echo $mysoc->phone; ?></dd>
								<dt>Email</dt>
								<dd><a href="mailto:<?php echo $mysoc->email; ?>"><?php echo $mysoc->email; ?></a></dd>
							</dl>
							<ul class="icons">
							  <ul class="list-inline">
							  <?php foreach ($mysoc->socialnetworks as $key => $value) {
									print '<li><a target="_blank" class="icon alt fab fa-'.$key.'" href="'.(preg_match('/^http/', $value) ? $value : 'https://www.'.$key.'.com/'.$value).'"><span class="label">'.ucfirst($key).'</span></a></li>';
							  } ?>
							</ul>
						</section>
						<div class="copyright">&copy; Untitled. Design: <a href="https://html5up.net">HTML5 UP</a> adapted for <a href="https://www.bespo.et">Dolibarr</a> by <a href="https://www.nltechno.com">NLTechno</a>.</div>
					</footer>


<script>
// When the user scrolls down 20px from the top of the document, show the button
window.onscroll = function() {scrollFunction()};

function scrollFunction() {
  console.log("Execute code for scroll Top button");
  if (document.body.scrollTop > 20 || document.documentElement.scrollTop > 20) {
	document.getElementById("myBtnToTop").style.display = "block";
  } else {
	document.getElementById("myBtnToTop").style.display = "none";
  }
}

// When the user clicks on the button, scroll to the top of the document
function topFunction() {
  document.body.scrollTop = 0; // For Safari
  document.documentElement.scrollTop = 0; // For Chrome, Firefox, IE and Opera
}
</script>

<button onclick="topFunction()" id="myBtnToTop" title="Go to top">Top</button>

</body>
</html>
<?php // BEGIN PHP
$tmp = ob_get_contents(); ob_end_clean(); dolWebsiteOutput($tmp, "html", 21);
// END PHP ?>
