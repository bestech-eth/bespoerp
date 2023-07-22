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
<title>Home page</title>
<meta charset="utf-8">
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<meta name="robots" content="index, follow" />
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="keywords" content="" />
<meta name="title" content="Home page" />
<meta name="description" content="" />
<meta name="generator" content="Dolibarr 17.0.0-alpha (https://www.bespo.et)" />
<meta name="dolibarr:pageid" content="202" />
<?php if ($website->use_manifest) { print '<link rel="manifest" href="/manifest.json.php" />'."\n"; } ?>
<!-- Include link to CSS file -->
<link rel="stylesheet" href="/styles.css.php?website=<?php echo $websitekey; ?>" type="text/css" />
<!-- Include link to JS file -->
<script async src="/javascript.js.php"></script>
<!-- Include HTML header from common file -->
<?php if (file_exists(DOL_DATA_ROOT."/website/".$websitekey."/htmlheader.html")) include DOL_DATA_ROOT."/website/".$websitekey."/htmlheader.html"; ?>
<!-- Include HTML header from page header block -->
<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM"
    crossorigin="anonymous"
></script>
</head>
<!-- File generated by Dolibarr website module editor -->
<body id="bodywebsite" class="bodywebsite bodywebpage-index">
<!-- Enter here your HTML content. Add a section with an id tag and tag contenteditable="true" if you want to use the inline editor for the content  -->
<?php 
    if (GETPOST('action') == 'sendmail')    {
    include_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
    $from = GETPOST('email', 'alpha');
    $to = $mysoc->email;
    $message = GETPOST('message', 'alpha');
    $cmail = new CMailFile('Contact from website', $to, $from, $message);
    if ($cmail->sendfile()) {
        ?>
        <script>
            alert("Message sent successfully !");
        </script>
        <?php
	} else {
		echo $langs->trans("ErrorFailedToSendMail", $from, $to).'. '.$cmail->error;
	}
}
?>
<section id="mysection1" contenteditable="true">
        <nav class="navbar navbar-expand-lg navbar-dark position-fixed px-3">
                <a class="navbar-brand fw-bold fs-2" href="#landing"> Company </a>
                <button
                    class="navbar-toggler"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#navbarSupportedContent"
                    aria-controls="navbarSupportedContent"
                    aria-expanded="false"
                    aria-label="Toggle navigation"
                >
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div
                    class="collapse navbar-collapse"
                    id="navbarSupportedContent"
                >
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0 fw-bold ">
                        <li class="nav-item">
                            <a
                                class="nav-link active"
                                aria-current="page"
                                href="#landing"
                                >Description</a
                            >
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" aria-current="page" href="#team"
                                >Team</a
                            >
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#contact">Contact</a>
                        </li>
                        <li class="nav-item dropdown">
                            <a
                                class="nav-link dropdown-toggle"
                                href="#"
                                id="navbarDropdown"
                                role="button"
                                data-bs-toggle="dropdown"
                                aria-expanded="false"
                            >
                                Useful links
                            </a>
                            <ul
                                class="dropdown-menu"
                                aria-labelledby="navbarDropdown"
                            >
                                <li>
                                    <a class="dropdown-item" href="#" onclick="alert('define link')">Link One</a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="#" onclick="alert('define link')"
                                        >Link two</a
                                    >
                                </li>
                                <li><hr class="dropdown-divider" /></li>                                
                            </ul>
                        </li>                       
                    </ul>                    
                </div>
            </nav>
   
        <section id="landing">
            <main class="landing-content container text-center">
                <div class="row">
                    <div class="col-md-12">
                        <h1 id="title">Get Productive</h1>
                        <p>
                            Lorem ipsum dolor, sit amet consectetur adipisicing
                            elit. Ab fuga nobis omnis alias, aliquid iste cumque
                            tempora nam reprehenderit quia itaque debitis,
                            nostrum labore rerum reiciendis laboriosam unde,
                            tempore corporis.
                        </p>
                        <img
                            class="img-landing img-fluid"
                            src="/image/lll/bg.png"                            
                            alt="landing-img"
                        />                        
                    </div>
                    <a href="#desc" id="desc-btn" class="btn btn-perso w-auto mx-auto">   
                        Learn More <span class="bi-arrow-down"></span>
                    </a>
                </div>
            </main>
        </section>
        <section id="desc">
            <div class="container text-white">
                <div class="row flex text-center article">
                    <div class="col-md-6">
                        <h1 class="article-title fw-bold text-center">
                            LOREM IPSUM DOLOR SIT AMET EZAJB
                        </h1>
                        <img
                            src="/image/lll/article.png"
                            width="50%"
                            alt="article"
                        />
                    </div>
                    <div class="col-md-6">
                        <h1>Our Company</h1>
                        <p>
                            Lorem ipsum dolor, sit amet consectetur adipisicing
                            elit. Ab fuga nobis omnis alias, aliquid iste cumque
                            tempora nam reprehenderit quia itaque debitis,
                            nostrum labore rerum reiciendis laboriosam unde,
                            tempore corporis.
                        </p>                        
                    </div>
                </div>
            </div>
        </section>
        <section id="team">
            <div class="container">
                <div class="row founders-article">
                    <div class="col-md-10 mx-auto my-auto">
                        <h1 class="text-center">Founders</h1>
                        <ul id="authors" class="list-group-flush mt-5">                            
                            <li id="one" onmouseenter="addPointClass(this)" onmouseleave="removePointClass(this)" class="list-group-item">
                                <h3>Author One</h3>
                            </li>
                            <li id="two" onmouseenter="addPointClass(this)" onmouseleave="removePointClass(this)" class="list-group-item">
                                <h3>Author Two</h3>
                            </li>
                            <li id="three" onmouseenter="addPointClass(this)" onmouseleave="removePointClass(this)" class="list-group-item">
                                <h3>Author Three</h3>
                            </li>
                            <li id="four" onmouseenter="addPointClass(this)" onmouseleave="removePointClass(this)" class="list-group-item">
                                <h3>Author Four</h3>
                            </li>
                        </ul>
                    </div>                   
                    <div class="col-md-8 mx-auto">
                        <h4 class="text-center text-secondary">About</h4>
                        <p class="text-left " id="aboutAuthor">
                            Lorem ipsum dolor sit amet consectetur adipisicing elit. Veritatis accusantium earum sed odit velit laudantium ex libero quisquam consectetur, 
                            dolorem vero ipsam perferendis quibusdam itaque omnis a consequatur error repellat.
                        </p>
                    </div>                                  
            </div>
        </section>
        <section class="" id="contact">

            <div class="container">
                <h1 class="h1-responsive font-weight-bold text-center my-4">Contact us</h1>
                <!--Section description-->
                <p class="text-center w-responsive mx-auto mb-5">Do you have any questions? Please do not hesitate to contact us directly. Our team will come back to you within
                    a matter of hours to help you.</p>
            
                <div class="row">
            
                    <!--Grid column-->
                    <div class="col-md-9 mb-md-0 mb-5">
                        <form action="index.php" method="POST">
                        <input type="hidden" name="token" value="<?php echo newToken(); ?>" />
                        <input type="hidden" name="action" value="sendmail">
                        <div class="row gy-3">
                            <div class="col-12 col-md-6">
                                <div class="form-group">
                                    <label>Email</label>
                                    <input
                                        name="email"
                                        type="email"
                                        class="form-control"
                                        placeholder="Email"
                                    />
                                </div>
                                <div class="form-group">
                                    <label>Name</label>
                                    <input
                                        name="name"
                                        type="text"
                                        class="form-control"
                                        placeholder="Name"
                                    />
                                </div>
                                <div class="form-group">
                                    <label>Phone</label>
                                    <input
                                        name="phone"
                                        type="text"
                                        class="form-control"
                                        placeholder="Phone"
                                    />
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="form-group">
                                    <label>Message</label>
                                    <textarea
                                        name="message"
                                        class="form-control"
                                        rows="6"
                                        placeholder="Your message"
                                    ></textarea>
                                </div>
                            </div>
                            <div class="col-12 mt-3">
                                <div class="text-center text-md-left">
                            <input type="submit" class="btn btn-perso mt-3 fw-bold fs-5" value="Send message" />
                        </div>
                            </div>
                        </div>
                    </form>
            
                        
                    </div>
                     <div class="col-md-3 text-center">
                        <ul class="list-unstyled mb-0">
                            <li><i class="fas fa-map-marker-alt fa-2x"></i>
                                <p><?php echo $mysoc->getFullAddress() ?></p>
                            </li>
            
                            <li><i class="fas fa-phone mt-4 fa-2x"></i>
                                <p><?php echo $mysoc->phone ?></p>
                            </li>
            
                            <li><i class="fas fa-envelope mt-4 fa-2x"></i>
                                <p><?php echo $mysoc->email ?></p>
                            </li>
                        </ul>
                    </div>             

            </div>
            
                      
                </div>
        </section>
        <script>
          const about = document.getElementById("aboutAuthor");
            const commonText = "Lorem ipsum dolor sit amet consectetur adipisicing elit. Veritatis accusantium earum sed odit velit laudantium ex libero quisquam consectetur, dolorem vero ipsam perferendis quibusdam itaque omnis a consequatur error repellat";
            const authorsText = {
                one: "Author 1 : lorem ipsum dolor sit amet, consectetur adipis lorem ipsum dolor sit amet, consectetur adipis lorem ipsum dolor sit amet, consectetur adipis lorem ipsum dolor sit amet, consectetur adipis",
                two: "Author 2 : lorem ipsum dolor sit amet, consectetur adipis lorem ipsum dolor sit amet, consectetur adipislorem ipsum dolor sit amet, consectetur adipis lorem ipsum dolor sit amet, consectetur adipis",
                three: "Author 3 : lorem ipsum dolor sit amet, consectetur adipis lorem ipsum dolor sit amet, consectetur adipislorem ipsum dolor sit amet, consectetur adipis lorem ipsum dolor sit amet, consectetur adipis",
                four: "Author 4 : lorem ipsum dolor sit amet, consectetur adipis lorem ipsum dolor sit amet, consectetur adipis lorem ipsum dolor sit amet, consectetur adipis lorem ipsum dolor sit amet, consectetur adipis",
            }                                

            addPointClass = function(point) {
                point.classList.add("pointed");  
                about.innerText = authorsText[point.id];                                  
            };
            
            removePointClass = function(point) {
                point.classList.remove("pointed");        
                about.innerText = commonText;                        
            };
        </script>
</section>

</body>
</html>
<?php // BEGIN PHP
$tmp = ob_get_contents(); ob_end_clean(); dolWebsiteOutput($tmp, "html", 202);
// END PHP ?>
