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
 *  \file       htdocs/smsdecanet/send.php
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

require_once(dirname(__FILE__) ."/core/modules/modSmsDecanet.class.php");

$langs->load("admin");
$langs->load("sms");
$langs->load("smsdecanet@smsdecanet");

// Security check
if ($user->societe_id > 0)
{
	accessforbidden();
}

$action=GETPOST('action');


if ($action == 'send' && ! $_POST['cancel'])
{
	$error=0;

	$smsfrom='';
	if (! empty($_POST["fromsms"])) $smsfrom=GETPOST("fromsms");
	if (empty($smsfrom)) $smsfrom=GETPOST("fromname");
	$sendto     = GETPOST("sendto");
	$body       = GETPOST('message');
	$deliveryreceipt= GETPOST("deliveryreceipt");
    $deferred   = GETPOST('deferred');
    $priority   = GETPOST('priority');
    $class      = GETPOST('class');
    $errors_to  = GETPOST("errorstosms");

	// Create form object
	include_once(dirname(__FILE__) ."/core/class/html.formsmsdecanet.class.php");

	$formsms = new FormSms($db);

	if (! empty($formsms->error))
	{
	    $message='<div class="error">'.$formsms->error.'</div>';
	    $action='singlesms';
	    $error++;
	}
    if (empty($body))
    {
        $message='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentities("Message")).'</div>';
        $action='singlesms';
        $error++;
    }
	if (empty($smsfrom) || ! str_replace('+','',$smsfrom))
	{
		$message='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentities("SmsFrom")).'</div>';
        $action='singlesms';
		$error++;
	}
	if (empty($sendto) || ! str_replace('+','',$sendto))
	{
		$message='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentities("SmsTo")).'</div>';
        $action='singlesms';
		$error++;
	}
	if (! $error)
	{
		require_once(dirname(__FILE__) ."/core/class/CSMSFile.class.php");

		$smsfile = new CSMSFile($sendto, $smsfrom, $body, $deliveryreceipt, $deferred, $priority, $class);
		$result=$smsfile->sendfile();
		if ($result!='0')
		{
			$message='<div class="ok">'.$langs->trans("SmsSuccessfulySent",$smsfrom,$sendto).'</div>';
		}
		else
		{
			$message='<div class="error">'.$langs->trans("ResultKo").'<br>'.$smsfile->error.' '.$result.'</div>';
		}

		$action='';
	}
}

llxHeader('',$langs->trans("SendSMS"));
dol_htmloutput_mesg($message);
$to = '+33';
$socid=intval($_GET['id']);
if($socid>0) {
	$soc = new Societe($db);
	$soc->fetch($socid);
	$soc->info($socid);
	if(substr($soc->phone,0,1)!='+')
		$to = '+33'.substr($soc->phone,1);
	else
		$to = $soc->phone;
}


$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("SendSMS"),false,'setup');
	include_once(dirname(__FILE__) ."/core/class/html.formsmsdecanet.class.php");
$formsms = new FormSms($db);
$formsms->fromtype='user';
$formsms->fromid=$user->id;
$formsms->fromsms = (isset($_POST['fromsms'])?$_POST['fromsms']:($conf->global->MAIN_MAIL_SMS_FROM?$conf->global->MAIN_MAIL_SMS_FROM:$user->user_mobile));
$formsms->withfromreadonly=0;
$formsms->withsubstit=0;
$formsms->withfrom=1;
$formsms->witherrorsto=1;
$formsms->withto=$to;
$formsms->withfile=2;
$formsms->withbody=$langs->trans("yourMessage");
$formsms->withbodyreadonly=0;
$formsms->withcancel=0;
$formsms->withfckeditor=0;
// Tableau des parametres complementaires du post
$formsms->param["action"]="send";
$formsms->param["models"]="body";
$formsms->param["smsid"]=0;
$formsms->param["returnurl"]=$_SERVER['REQUEST_URI'];

$formsms->show_form();
$db->close();

llxFooter('$Date: 2010/03/10 15:00:00');