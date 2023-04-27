<?php
/* Copyright (C) 2020 Benjamin ARGOUD - SARL Decanet - https://www.decanet.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *  \file       htdocs/smsdecanet/admin/smsdecanet_conf.php
 *  \ingroup    technic
 *  \brief      Page d'administration/configuration du module SMS Decanet
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) { $i--; $j--; }
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) $res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) $res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
// Try main.inc.php using relative path
if (!$res && file_exists("../main.inc.php")) $res = @include "../main.inc.php";
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res) die("Include of main fails");

dol_include_once('/core/lib/admin.lib.php');
require_once(dirname(__FILE__) ."/../core/modules/modSmsDecanet.class.php");

$langs->load("admin");
$langs->load("smsdecanet@smsdecanet");

// Security check
if (!$user->admin)
accessforbidden();

if(isset($_GET['del'])) {
	$from = unserialize($conf->global->DECANETSMS_FROM);
	foreach($from as $k=>$n) {
		if($n->number==$_GET['del'])
			unset($from[$k]);
	}
	dolibarr_set_const($db, "DECANETSMS_FROM", serialize($from),'chaine',0,'',$conf->entity);
}
if ($_POST["action"] == 'majAccess')
{
	dolibarr_set_const($db, "DECANETSMS_EMAIL", $_POST["emailSMS"],'chaine',0,'',$conf->entity);
	if($_POST['passSMS']!='')dolibarr_set_const($db, "DECANETSMS_PASS", $_POST["passSMS"],'chaine',0,'',$conf->entity);
	dolibarr_set_const($db, "DECANETSMS_SSL", $_POST["sslSMS"],'entier',0,'',$conf->entity);
	dolibarr_set_const($db, "DECANETSMS_FROM", $_POST['fromSMS'],'chaine',0,'',$conf->entity);
}
if(isset($_GET['transactional'])) {
	dolibarr_set_const($db, "DECANETSMS_TRANSACTIONAL", intval($_GET['transactional']),'entier',0,'',$conf->entity);
}

/*
 * Affiche page
 */

llxHeader('',$langs->trans("DecanetSMSSetup"));

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("DecanetSMSSetup"),$linkback,'setup');


$var=true;

echo '<table class="noborder" width="100%">';
echo '<tr class="liste_titre">';
echo "  <td>".$langs->trans("DiagnosticSMS")."</td>";
echo '</tr>';
echo "<tr ".$bc[$var].">";
echo '<td>';
dol_include_once('/smsdecanet/class/DecanetAPI.class.php');
$url = (intval($conf->global->DECANETSMS_SSL)==1)?'https':'http';
$url.='://api.decanet.fr';	
$api = new DecanetApi($conf->global->DECANETSMS_EMAIL,$conf->global->DECANETSMS_PASS, $url);
$result = $api->get('/sms/solde');
if(isset($result->details)) {
	echo $result->details.' - (<a href="https://www.decanet.fr/prix-sms-premium/france,FR" target="_blank"><strong>'.$langs->trans('CreateSMSAcount').'</strong></a>)';
} else {
	echo '<strong>'.$langs->trans('CREDITSMS').'</strong>'.$result->credit.' '.$langs->trans('SMS').' - (<a href="https://www.decanet.fr/prix-sms-premium/france,FR" target="_blank"><strong>'.$langs->trans('RechargeSms').'</strong></a>)';
}
echo '</td>';
echo '</tr>';
if(isset($conf->global->DECANETSMS_TRANSACTIONAL) && intval($conf->global->DECANETSMS_TRANSACTIONAL)==1) {
	echo "<tr ".$bc[$var].">";
	echo '<td>'.$langs->trans("SmsTransactional").' (<a href="?transactional=0">'.$langs->trans("disable").'</a>)</td>';
	echo '</tr>';
}
echo '</table><br><br>';



echo '<table class="noborder" width="100%">';
echo '<tr class="liste_titre">';
echo "  <td>".$langs->trans("ParametersAccount")."</td>\n";
echo "  <td align=\"left\" ></td>";
echo "  <td >&nbsp;</td></tr>";

$var=!$var;

echo "<form method=\"post\" action=\"smsdecanet_conf.php\">";
echo '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
echo "<input type=\"hidden\" name=\"action\" value=\"majAccess\">";
echo "<tr ".$bc[$var].">";
echo '<td>'.$langs->trans("fromSMS").'</td>';
echo '<td align="left"><input type="text" name="fromSMS" size="50" class="flat" value="'.$conf->global->DECANETSMS_FROM.'"></td>';
echo '<td align="right"></td>';
echo '</tr>';
$var=!$var;
echo "<tr ".$bc[$var].">";
echo '<td>'.$langs->trans("emailSMS").'</td>';
echo '<td align="left"><input type="text" name="emailSMS" size="50" class="flat" value="'.$conf->global->DECANETSMS_EMAIL.'"></td>';
echo '<td align="right"></td>';
echo '</tr>';
$var=!$var;
echo "<tr ".$bc[$var].">";
echo '<td>'.$langs->trans("passSMS").'</td>';
echo '<td align="left"><input type="password" name="passSMS" size="50" class="flat"></td>';
echo '<td align="right"></td>';
echo '</tr>';
$var=!$var;

echo "<tr ".$bc[$var].">";
echo '<td>'.$langs->trans("Cryptage SSL").'</td>';
echo '<td align="left"><input type="checkbox" name="sslSMS" class="flat" value="1"';
if($conf->global->DECANETSMS_SSL==1) echo ' checked';
echo '></td>';
echo '<td align="right"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td>';
echo '</tr>';
echo '</form>';
echo '</table><br><br>';

$db->close();

echo '<table class="noborder" width="100%">';
echo '<tr class="liste_titre">';
echo "  <td>".$langs->trans("HistorySMSAccount")."</td>\n";
echo "  <td align=\"left\" ></td>";
echo "  <td >&nbsp;</td></tr>";
$var=!$var;

$result = $api->get('/sms?limit=50');
if(count($result)>0) {
	foreach($result as $k=>$s) {
		echo "<tr ".$bc[$var].">";
		echo '<td>'.$s->date->date.'</td>';
		echo '<td align="left">'.$s->details.' ('.$s->Country.')</td>';
		echo '<td align="right">'.$s->StatusText.'</td>';
		echo '</tr>';
		$var=!$var;
		if($k==50)break;
	}
}

echo '</table><br><br>';

llxFooter('$Date: 2010/03/10 15:00:00');

?>
