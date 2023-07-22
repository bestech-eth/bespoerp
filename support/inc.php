<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2004      Sebastien DiCintio   <sdicintio@ressource-toi.org>
 * Copyright (C) 2007-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * 	\file       htdocs/support/inc.php
 * 	\ingroup	core
 *	\brief      File that define environment for support pages
 */

// Define DOL_DOCUMENT_ROOT
if (!defined('DOL_DOCUMENT_ROOT')) {
	define('DOL_DOCUMENT_ROOT', '..');
}

require_once DOL_DOCUMENT_ROOT.'/core/class/translate.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

// Avoid warnings with strict mode E_STRICT
$conf = new stdClass(); // instantiate $conf explicitely
$conf->global	= new stdClass();
$conf->file = new stdClass();
$conf->db = new stdClass();
$conf->syslog	= new stdClass();

// Force $_REQUEST["logtohtml"]
$_REQUEST["logtohtml"] = 1;

// Correction PHP_SELF (ex pour apache via caudium) car PHP_SELF doit valoir URL relative
// et non path absolu.
if (isset($_SERVER["DOCUMENT_URI"]) && $_SERVER["DOCUMENT_URI"]) {
	$_SERVER["PHP_SELF"] = $_SERVER["DOCUMENT_URI"];
}


$includeconferror = '';

// Define vars
$conffiletoshowshort = "conf.php";
// Define localization of conf file
$conffile = "../conf/conf.php";
$conffiletoshow = "htdocs/conf/conf.php";
// For debian/redhat like systems
if (!file_exists($conffile)) {
	$conffile = "/etc/bespoerp/conf.php";
	$conffiletoshow = "/etc/bespoerp/conf.php";
}


// Load conf file if it is already defined
if (!defined('DONOTLOADCONF') && file_exists($conffile) && filesize($conffile) > 8) { // Test on filesize is to ensure that conf file is more that an empty template with just <?php in first line
	$result = include_once $conffile; // Load conf file
	if ($result) {
		if (empty($bespoerp_main_db_type)) {
			$bespoerp_main_db_type = 'mysql'; // For backward compatibility
		}

		//Mysql driver support has been removed in favor of mysqli
		if ($bespoerp_main_db_type == 'mysql') {
			$bespoerp_main_db_type = 'mysqli';
		}

		if (empty($bespoerp_main_db_port) && ($bespoerp_main_db_type == 'mysqli')) {
			$bespoerp_main_db_port = '3306'; // For backward compatibility
		}

		// Clean parameters
		$bespoerp_main_data_root        = isset($bespoerp_main_data_root) ?trim($bespoerp_main_data_root) : '';
		$bespoerp_main_url_root         = isset($bespoerp_main_url_root) ?trim($bespoerp_main_url_root) : '';
		$bespoerp_main_url_root_alt     = isset($bespoerp_main_url_root_alt) ?trim($bespoerp_main_url_root_alt) : '';
		$bespoerp_main_document_root    = isset($bespoerp_main_document_root) ?trim($bespoerp_main_document_root) : '';
		$bespoerp_main_document_root_alt = isset($bespoerp_main_document_root_alt) ?trim($bespoerp_main_document_root_alt) : '';

		// Remove last / or \ on directories or url value
		if (!empty($bespoerp_main_document_root) && !preg_match('/^[\\/]+$/', $bespoerp_main_document_root)) {
			$bespoerp_main_document_root = preg_replace('/[\\/]+$/', '', $bespoerp_main_document_root);
		}
		if (!empty($bespoerp_main_url_root) && !preg_match('/^[\\/]+$/', $bespoerp_main_url_root)) {
			$bespoerp_main_url_root = preg_replace('/[\\/]+$/', '', $bespoerp_main_url_root);
		}
		if (!empty($bespoerp_main_data_root) && !preg_match('/^[\\/]+$/', $bespoerp_main_data_root)) {
			$bespoerp_main_data_root = preg_replace('/[\\/]+$/', '', $bespoerp_main_data_root);
		}
		if (!empty($bespoerp_main_document_root_alt) && !preg_match('/^[\\/]+$/', $bespoerp_main_document_root_alt)) {
			$bespoerp_main_document_root_alt = preg_replace('/[\\/]+$/', '', $bespoerp_main_document_root_alt);
		}
		if (!empty($bespoerp_main_url_root_alt) && !preg_match('/^[\\/]+$/', $bespoerp_main_url_root_alt)) {
			$bespoerp_main_url_root_alt = preg_replace('/[\\/]+$/', '', $bespoerp_main_url_root_alt);
		}

		// Create conf object
		if (!empty($bespoerp_main_document_root)) {
			$result = conf($bespoerp_main_document_root);
		}
		// Load database driver
		if ($result) {
			if (!empty($bespoerp_main_document_root) && !empty($bespoerp_main_db_type)) {
				$result = include_once $bespoerp_main_document_root."/core/db/".$bespoerp_main_db_type.'.class.php';
				if (!$result) {
					$includeconferror = 'ErrorBadValueForbespoerpMainDBType';
				}
			}
		} else {
			$includeconferror = 'ErrorBadValueForbespoerpMainDocumentRoot';
		}
	} else {
		$includeconferror = 'ErrorBadFormatForConfFile';
	}
}
$conf->global->MAIN_LOGTOHTML = 1;

// Define prefix
if (!isset($bespoerp_main_db_prefix) || !$bespoerp_main_db_prefix) {
	$bespoerp_main_db_prefix = 'llx_';
}
define('MAIN_DB_PREFIX', (isset($bespoerp_main_db_prefix) ? $bespoerp_main_db_prefix : ''));

define('DOL_CLASS_PATH', 'class/'); // Filsystem path to class dir
define('DOL_DATA_ROOT', (isset($bespoerp_main_data_root) ? $bespoerp_main_data_root : ''));
define('DOL_MAIN_URL_ROOT', (isset($bespoerp_main_url_root) ? $bespoerp_main_url_root : '')); // URL relative root
$uri = preg_replace('/^http(s?):\/\//i', '', constant('DOL_MAIN_URL_ROOT')); // $uri contains url without http*
$suburi = strstr($uri, '/'); // $suburi contains url without domain
if ($suburi == '/') {
	$suburi = ''; // If $suburi is /, it is now ''
}
define('DOL_URL_ROOT', $suburi); // URL relative root ('', '/bespoerp', ...)

if (empty($character_set_client)) {
	$character_set_client = "UTF-8";
}
$conf->file->character_set_client = strtoupper($character_set_client);
if (empty($bespoerp_main_db_character_set)) {
	$bespoerp_main_db_character_set = ($conf->db->type == 'mysqli' ? 'utf8' : ''); // Old installation
}
$conf->db->character_set = $bespoerp_main_db_character_set;
if (empty($bespoerp_main_db_collation)) {
	$bespoerp_main_db_collation = ($conf->db->type == 'mysqli' ? 'utf8_unicode_ci' : ''); // Old installation
}
$conf->db->bespoerp_main_db_collation = $bespoerp_main_db_collation;
if (empty($bespoerp_main_db_encryption)) {
	$bespoerp_main_db_encryption = 0;
}
$conf->db->bespoerp_main_db_encryption = $bespoerp_main_db_encryption;
if (empty($bespoerp_main_db_cryptkey)) {
	$bespoerp_main_db_cryptkey = '';
}
$conf->db->bespoerp_main_db_cryptkey = $bespoerp_main_db_cryptkey;

if (empty($conf->db->user)) {
	$conf->db->user = '';
}


// Defini objet langs
$langs = new Translate('..', $conf);
if (GETPOST('lang', 'aZ09')) {
	$langs->setDefaultLang(GETPOST('lang', 'aZ09'));
} else {
	$langs->setDefaultLang('auto');
}

$bc[false] = ' class="bg1"';
$bc[true] = ' class="bg2"';


/**
 *	Load conf file (file must exists)
 *
 *	@param	string	$bespoerp_main_document_root		Root directory of bespoerp bin files
 *	@return	int											<0 if KO, >0 if OK
 */
function conf($bespoerp_main_document_root)
{
	global $conf;
	global $bespoerp_main_db_type;
	global $bespoerp_main_db_host;
	global $bespoerp_main_db_port;
	global $bespoerp_main_db_name;
	global $bespoerp_main_db_user;
	global $bespoerp_main_db_pass;
	global $character_set_client;

	$return = include_once $bespoerp_main_document_root.'/core/class/conf.class.php';
	if (!$return) {
		return -1;
	}

	$conf = new Conf();
	$conf->db->type = trim($bespoerp_main_db_type);
	$conf->db->host = trim($bespoerp_main_db_host);
	$conf->db->port = trim($bespoerp_main_db_port);
	$conf->db->name = trim($bespoerp_main_db_name);
	$conf->db->user = trim($bespoerp_main_db_user);
	$conf->db->pass = trim($bespoerp_main_db_pass);

	if (empty($conf->db->bespoerp_main_db_collation)) {
		$conf->db->bespoerp_main_db_collation = 'utf8_unicode_ci';
	}

	return 1;
}


/**
 * Show HTML header
 *
 * @param	string	$soutitre	Title
 * @param	string	$next		Next
 * @param	string	$action		Action code
 * @return	void
 */
function pHeader($soutitre, $next, $action = 'none')
{
	global $conf, $langs;

	$langs->loadLangs(array("main", "admin"));

	// On force contenu dans format sortie
	header("Content-type: text/html; charset=".$conf->file->character_set_client);

	// Security options
	header("X-Content-Type-Options: nosniff");
	header("X-Frame-Options: SAMEORIGIN"); // Frames allowed only if on same domain (stop some XSS attacks)

	print '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">'."\n";
	print '<head>'."\n";
	print '<meta http-equiv="content-type" content="text/html; charset='.$conf->file->character_set_client.'">'."\n";
	print '<meta name="robots" content="index,follow">'."\n";
	print '<meta name="viewport" content="width=device-width, initial-scale=1.0">'."\n";
	print '<meta name="keywords" content="help, center, bespoerp, doliwamp">'."\n";
	print '<meta name="description" content="bespoerp help center">'."\n";
	print '<link rel="stylesheet" type="text/css" href="../install/default.css">'."\n";
	print '<title>'.$langs->trans("bespoerpHelpCenter").'</title>'."\n";
	print '</head>'."\n";

	print '<body class="center">'."\n";

	print '<div class="noborder centpercent center valignmiddle inline-block">';
	print '<img src="helpcenter.png" alt="logohelpcenter" class="inline-block"><br><br>';
	print '<span class="titre inline-block">'.$soutitre.'</span>'."\n";
	print '</div><br>';
}

/**
 * Print HTML footer
 *
 * @param	integer	$nonext			No button "Next step"
 * @param   string	$setuplang		Language code
 * @return	void
 */
function pFooter($nonext = 0, $setuplang = '')
{
	global $langs;
	$langs->load("main");
	$langs->load("admin");

	print '</body>'."\n";
	print '</html>'."\n";
}
