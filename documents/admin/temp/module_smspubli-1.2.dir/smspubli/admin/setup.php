<?php
/* SMSPubli: send SMS to thirdparties by smspubli.com
/* Copyright (C) 2012 Maxime MANGIN             <maxime@tuxserv.fr>
 * Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2017-2021 Josep Lluís Amador   <joseplluis@lliuretic.cat>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    smspubli/admin/setup.php
 * \ingroup smspubli
 * \brief   SmsPubli setup page.
 *
 * Based on smsdecanet module.
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
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res) die("Include of main fails");

global $langs, $user;

// Libraries
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once DOL_DOCUMENT_ROOT . "/categories/class/categorie.class.php";
require_once "../core/modules/modSmsPubli.class.php";


// Translations
$langs->load("admin");
$langs->load("smspubli@smspubli");


// Access control
if (!$user->admin) accessforbidden();

// Parameters
$action = GETPOST('action', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');

$arrayofparameters = array(
	'SMSPUBLI_FAKESMS'=>array('enabled'=>1),
	'SMSPUBLI_SMSFROM'=>array('css'=>'minwidth200', 'enabled'=>1),
	'SMSPUBLI_APIKEY'=>array('css'=>'minwidth500', 'enabled'=>1)
);

$error = 0;
$setupnotempty = 0;


/*
 * Actions
 */

if ((float) DOL_VERSION >= 6)
{
	include DOL_DOCUMENT_ROOT.'/core/actions_setmoduleoptions.inc.php';
}
else
{
	if ($action == 'update' && is_array($arrayofparameters))
	{
		$db->begin();

		$ok=true;
		foreach($arrayofparameters as $key => $val)
		{
			$result=dolibarr_set_const($db, $key, GETPOST($key, 'alpha'), 'chaine', 0, '', $conf->entity);
			if ($result < 0)
			{
				$ok=false;
				break;
			}
		}

		if (! $error)
		{
			$db->commit();
			if (empty($nomessageinupdate)) setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
		}
		else
		{
			$db->rollback();
			if (empty($nomessageinupdate)) setEventMessages($langs->trans("SetupNotSaved"), null, 'errors');
		}
	}
}




/*
 * View
 */

$form = new Form($db);

$page_name = "SMSPubliSetup";
llxHeader('', $langs->trans($page_name));

// Subheader
$linkback = '<a href="'.($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.$langs->trans("BackToModuleList").'</a>';

print load_fiche_titre($langs->trans($page_name), $linkback);

// Configuration header

$head = ''; //smspubliAdminPrepareHead();
dol_fiche_head($head, 'settings', $langs->trans("Module409000Name"), -1, "smspubli@smspubli");

// Setup page goes here
echo '<span class="opacitymedium">'.$langs->trans("SMSPubliSetupPage").'</span><br><br>';

echo '<br><br><div>';
echo '<strong>'.$langs->trans("SMSPubliConfigureSMS").'</strong>';
echo "&nbsp;&nbsp;<a href='".DOL_URL_ROOT."/admin/sms.php' target='_blank'><strong>".$langs->trans("Here")."</strong></a>"; 
echo "</div>";
$var=true;
echo '<table class="noborder" width="100%">';
echo '<tr class="liste_titre">';
echo "  <td>".$langs->trans("SMSPubliAccount")."</td>";
echo '</tr>';
echo "<tr ".$bc[$var].">";
echo '<td>';
include_once('../class/SMSpubliAPI.class.php');
$url = 'https://api.gateway360.com/api/3.0';	
$api = new SMSPubliApi($conf->global->SMSPUBLI_APIKEY, $url);
$result = $api->get('/account/get-balance');
if(!$conf->global->SMSPUBLI_APIKEY || ($result->status!="ok")) {
	if (!$conf->global->SMSPUBLI_APIKEY)
		echo $langs->trans('ApiKeyNotDefined').' - (<a href="http://panel.smspubli.com/signup/?ida=67340" target="_blank"><strong>'.$langs->trans('CreateSMSAcount').'</strong></a>)';
	else
		echo $result->error_msg.' - (<a href="http://panel.smspubli.com/signup/?ida=67340" target="_blank"><strong>'.$langs->trans('CreateSMSAcount').'</strong></a>)';
} else {
	echo '<strong>'.$langs->trans('CreditSMS').'</strong> '.$result->result->balance.' '.$result->result->currency.' - (<a href="http://panel.smspubli.com/signup/?ida=67340" target="_blank"><strong>'.$langs->trans('RechargeSms').'</strong></a>)';
} 
echo '</td>';
echo '</td>';
echo '</table><br><br>';

if ($action == 'edit')
{
	//Custom for OLD Dolibarr versions
	if ((float) DOL_VERSION >= 11)
		$newToken = newToken();
	else
		$newToken = $_SESSION['newtoken'];

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.$newToken.'">';
	print '<input type="hidden" name="action" value="update">';

	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre"><td class="titlefield">'.$langs->trans("ParametersAccount").'</td><td>'.$langs->trans("Value").'</td></tr>';

	foreach ($arrayofparameters as $key => $val)
	{
		if ($key=='SMSPUBLI_FAKESMS') continue;
		$setupnotempty++;

		print '<tr class="oddeven"><td>';
		$tooltiphelp = (($langs->trans($key.'Tooltip') != $key.'Tooltip') ? $langs->trans($key.'Tooltip') : '');
		print $form->textwithpicto($langs->trans($key), $tooltiphelp);
		print '</td><td><input name="'.$key.'"  class="flat '.(empty($val['css']) ? 'minwidth200' : $val['css']).'" value="'.$conf->global->$key.'"></td></tr>';
	}
	print '</table>';

	print '<br><div class="center">';
	print '<input class="button" type="submit" value="'.$langs->trans("Save").'">';
	print '</div>';

	print '</form>';
	print '<br>';
} else {
	if (!empty($arrayofparameters))
	{
		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre"><td class="titlefield">'.$langs->trans("ParametersAccount").'</td><td>'.$langs->trans("Value").'</td></tr>';

		foreach ($arrayofparameters as $key => $val)
		{
			$setupnotempty++;

			print '<tr class="oddeven"><td>';
			$tooltiphelp = (($langs->trans($key.'Tooltip') != $key.'Tooltip') ? $langs->trans($key.'Tooltip') : '');
			print $form->textwithpicto($langs->trans($key), $tooltiphelp);
			if ($key=='SMSPUBLI_FAKESMS') {
				print '</td><td>';
				if ($conf->use_javascript_ajax)
					print ajax_constantonoff('SMSPUBLI_FAKESMS');
				else {
					if (empty($conf->global->SMSPUBLI_FAKESMS))
						print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_SMSPUBLI_FAKESMS">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
					else
						print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_SMSPUBLI_FAKESMS">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
				}
				print '</td></tr>';
			}
			else {
				print '</td><td>'.$conf->global->$key.'</td></tr>';
			}
		}

		print '</table>';

		print '<div class="tabsAction">';
		print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=edit">'.$langs->trans("Modify").'</a>';
		print '</div>';

		echo '<br><br><table class="noborder" centpercent>';
		echo '<tr class="liste_titre">';
		echo "  <td>".$langs->trans("HistorySMSAccount")."</td>\n";
		echo "  <td align=\"left\" ></td>";
		echo "  <td >&nbsp;</td></tr>";
		$var = !$var;
		//from_date --> Obtain reports from this date onwards. Format is YYYY-MM-DD HH:MM:SS.
		$result = $api->post('/sms/get-reports', 
						array(
							  '"to_date"'=>'"'.dol_print_date(dol_now(),'standard').'"'
							 )
						);
		if(count($result->result) > 0) {
			foreach($result->result as $k => $s) {
				echo "<tr ".$bc[$var].">";
				echo '<td>'.$s->dlr_date.' ('.$s->status.')</td>';
				echo '<td align="left">'.$s->from.' ('.$s->custom.')</td>';
				echo '<td align="left">'.$s->to.'</td>';
				echo '</tr>';
				$var = !$var;
				if ($k == 50) break;
			}
		}

		print '</table>';
	}
}

if (empty($setupnotempty)) {
	print '<br>'.$langs->trans("NothingToSetup");
}

// Page end
dol_fiche_end();

llxFooter();
$db->close();
