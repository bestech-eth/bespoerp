<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2015      Jean-François Ferry	<jfefe@aternatik.fr>
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
 *	\file       moceanapi/moceanapiindex.php
 *	\ingroup    moceanapi
 *	\brief      Home page of moceanapi top menu
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--; $j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) {
	$res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
}
// Try main.inc.php using relative path
if (!$res && file_exists("../main.inc.php")) {
	$res = @include "../main.inc.php";
}
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}

global $langs, $user, $conf;

// Libraries
require_once DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php";
// include DOL_DOCUMENT_ROOT.'/core/actions_setmoduleoptions.inc.php';

// Load translation files required by the page
$langs->loadLangs(array("moceanapi@moceanapi"));

$action = GETPOST('action', 'aZ09');

// Security check
if (! $user->rights->moceanapi->myobject->read) {
	accessforbidden();
}

llxHeader("", $langs->trans("MoceanAPIArea"));

print load_fiche_titre($langs->trans("MoceanAPIArea"), '', 'moceanapi.png@moceanapi');

print '<div class="fichecenter"><div class="fichethirdleft">';

// file to save data into database
// @Method POST params: action = 'update', token = newToken()
// @Params action = 'update', token = newToken()

$action = GETPOST('action');
$form = new Form($db);
$arrayofparameters = array(
	"MOCEAN_AUTO_THIRDPARTY_INVOICE_CREATED" => array(),
);

include DOL_DOCUMENT_ROOT.'/core/actions_setmoduleoptions.inc.php';

?>
<!-- Begin form SMS -->
<form method="POST" action="<?php echo $_SERVER["PHP_SELF"] ?>">
	<input type="hidden" name="token" value="<?php echo newToken(); ?>">
	<input type="hidden" name="action" value="update">

	<table class="border" width="100%">
		<!-- Balance -->
		<tr>
			<td width="200px">Balance</td>
			<td>
				<input type="text" name="sms_from" size="30" value="123" disabled>
			</td>
		</tr>

		<!-- Enable  -->

		<tr>
			<td width="200px"><?php echo $form->textwithpicto($langs->trans("MOCEAN_FROM"), "Your business name"); ?>*</td>
			<td>
				<input type="text" name="sms_from" size="30" value="<?php echo $conf->global->MOCEAN_FROM; ?>">
			</td>
		</tr>

		<!-- To -->
		<tr>
		<td width="200px" valign="top">SMS Message</td>
			<td>
				<textarea cols="40" name="MOCEAN_AUTO_THIRDPARTY_INVOICE_CREATED" id="message" rows="4"><?php echo $conf->global->MOCEAN_AUTO_THIRDPARTY_INVOICE_CREATED ?></textarea>
				<div>
					<a href="https://dashboard.moceanapi.com/sms-calculator" target="_blank">
						<?php echo $langs->trans("SmsCounterLinkText") ?>
					</a>
				</div>
			</td>
		</td>
		</tr>

		<!-- Message -->
		<tr>
		<td width="200px" valign="top">SMS Message</td>
			<td>
				<textarea cols="40" name="sms_message" id="message" rows="4"><?php echo $conf->global->MOCEAN_AUTO_THIRDPARTY_INVOICE_CREATED ?></textarea>
				<div>
					<a href="https://dashboard.moceanapi.com/sms-calculator" target="_blank">
						<?php echo $langs->trans("SmsCounterLinkText") ?>
					</a>
				</div>
			</td>
		</td>
		</tr>

	</table>
	<!-- Submit -->
	<center>
		<input class="button" type="submit" name="submit" value="<?php echo $langs->trans("SendSMSButtonTitle") ?>">
	</center>

</form>
<?php
print '</div><div class="fichetwothirdright">';


$NBMAX = $conf->global->MAIN_SIZE_SHORTLIST_LIMIT;
$max = $conf->global->MAIN_SIZE_SHORTLIST_LIMIT;

print '</div></div>';

// End of page
llxFooter();
$db->close();
