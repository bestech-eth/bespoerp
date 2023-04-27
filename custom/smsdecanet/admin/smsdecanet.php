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
 *  \file       htdocs/smsdecanet/admin/smsdecanet.php
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

$langs->load("companies");
$langs->load("products");
$langs->load("admin");
$langs->load("sms");
$langs->load("other");
$langs->load("errors");

if ((!$user->rights->smsdecanet->sendmulti && $action=='smsmulti' || !$user->rights->smsdecanet->send && $action=='singlesms') && !$user->admin)
accessforbidden();

$substitutionarrayfortest=array(
'__ID__' => 'TESTIdRecord',
'__PHONEFROM__' => 'TESTPhoneFrom',
'__PHONETO__' => 'TESTPhoneTo',
'__LASTNAME__' => 'TESTLastname',
'__FIRSTNAME__' => 'TESTFirstname'
);

$action=GETPOST('action');



/*
 * Actions
 */

if ($action == 'update' && empty($_POST["cancel"]))
{
	dolibarr_set_const($db, "MAIN_DISABLE_ALL_SMS",   $_POST["MAIN_DISABLE_ALL_SMS"],'chaine',0,'',$conf->entity);

	dolibarr_set_const($db, "MAIN_SMS_SENDMODE",      $_POST["MAIN_SMS_SENDMODE"],'chaine',0,'',$conf->entity);

	dolibarr_set_const($db, "MAIN_MAIL_SMS_FROM",     $_POST["MAIN_MAIL_SMS_FROM"],'chaine',0,'',$conf->entity);
	//dolibarr_set_const($db, "MAIN_MAIL_AUTOCOPY_TO",    $_POST["MAIN_MAIL_AUTOCOPY_TO"],'chaine',0,'',$conf->entity);

	header("Location: ".$_SERVER["PHP_SELF"]."?mainmenu=home&leftmenu=setup");
	exit;
}


/*
 * Send sms
 */
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
        $prevaction  = GETPOST("prevaction") == "smsmulti" ? "smsmulti" : "singlesms";
        
	// Create form object
	include_once(dirname(__FILE__) ."/../core/class/html.formsmsdecanet.class.php");
	$formsms = new FormSms($db);
        
        if($prevaction == "smsmulti"){
            $sendto = $formsms->getContactPhoneListByCategory(GETPOST('sendto'));
        }

	if (! empty($formsms->error))
	{
            setEventMessage($formsms->error,'errors');
	    $action=$prevaction;
	    $error++;
	}
        if (empty($body))
        {
            setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentities("Message")),'errors');
            $action=$prevaction;
            $error++;
        }
	if (empty($smsfrom) || ! str_replace('+','',$smsfrom))
	{
		setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentities("SmsFrom")),'errors');
        $action=$prevaction;
		$error++;
	}
	if (empty($sendto) || ! str_replace('+','',$sendto))
	{
		setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentities("SmsTo")),'errors');
        $action=$prevaction;
		$error++;
	}
	if (! $error)
	{
		// Make substitutions into message
        complete_substitutions_array($substitutionarrayfortest, $langs);
	    $body=make_substitutions($body,$substitutionarrayfortest);

		require_once DOL_DOCUMENT_ROOT.'/core/class/CSMSFile.class.php';

                        //var_dump($sendto);exit;
                if(is_array($sendto)){
                    if(!empty($sendto) or count($sendto) > 0){
                        foreach($sendto as $sto){
                            $smsfile = new CSMSFile($sto, $smsfrom, $body, $deliveryreceipt, $deferred, $priority, $class);  // This define OvhSms->login, pass, session and account
                            $result=$smsfile->sendfile(); // This send SMS

                            if ($result)  {
                                    setEventMessage($langs->trans("SmsSuccessfulySent",$smsfrom,$sto));
                            }  else {
                                    setEventMessage($smsfile->error,'errors');
                            }
                        }
                        $action='';
                    }else{
                        setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentities("SmsTo")),'errors');
                        $action=$prevaction;
                    }
		}else{
                    $smsfile = new CSMSFile($sendto, $smsfrom, $body, $deliveryreceipt, $deferred, $priority, $class);  // This define OvhSms->login, pass, session and account
                    $result=$smsfile->sendfile(); // This send SMS

                    if ($result)  {
                            setEventMessage($langs->trans("SmsSuccessfulySent",$smsfrom,$sendto));
                    }  else {
                            setEventMessage($smsfile->error,'errors');
                    }
                    $action='';
                }
	}
        
}



/*
 * View
 */

$linuxlike=1;
if (preg_match('/^win/i',PHP_OS)) $linuxlike=0;
if (preg_match('/^mac/i',PHP_OS)) $linuxlike=0;

// $wikihelp='EN:Setup Sms|FR:Paramétrage Sms|ES:Configuración Sms';
llxHeader('',$langs->trans("Setup"),$wikihelp);

// print_fiche_titre($langs->trans("SmsSetup"),'','setup');

// print $langs->trans("SmsDesc")."<br>\n";
print "<h1>".$langs->trans("SMSDecanetName")."</h1>";
print "<p>".$langs->trans("SMSDecanetDesc")."</p>";
print "<br>\n";

// List of sending methods
$listofmethods=(is_array($conf->modules_parts['sms'])?$conf->modules_parts['sms']:array());
asort($listofmethods);

	$var=true;

	if (! count($listofmethods)) print '<div class="warning">'.$langs->trans("NoSmsEngine",'<a target="_blank" href="http://www.dolistore.com/search.php?orderby=position&orderway=desc&search_query=smsmanager">DoliStore</a>').'</div>';

	if (count($listofmethods) && ! empty($conf->global->MAIN_SMS_SENDMODE))
	{
	   print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=singlesms&amp;mode=init">'.$langs->trans("SMSDecanetSingleSend").'</a>';
	   print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=smsmulti&amp;mode=init">'.$langs->trans("SMSDecanetMultiSend").'</a>';
	}
	else
	{
       print '<a class="butActionRefused" href="#">'.$langs->trans("SMSDecanetSingleSend").'</a>';
	}
	print '</div>';



	// Affichage formulaire de sms simple
	if ($action == 'singlesms')
	{
		print '<br><div style="padding:15px;">';
		print_titre($langs->trans("SMSDecanetSingleSend"));

		// Cree l'objet formulaire mail
		include_once(dirname(__FILE__) ."/../core/class/html.formsmsdecanet.class.php");
		$formsms = new FormSms($db);
                $formsms->fromtype='user';
                $formsms->fromid=$user->id;
                $formsms->fromsms = (isset($_POST['fromsms'])?$_POST['fromsms']:($conf->global->MAIN_MAIL_SMS_FROM?$conf->global->MAIN_MAIL_SMS_FROM:$user->user_mobile));
		$formsms->withfromreadonly=0;
		$formsms->withsubstit=0;
		$formsms->withfrom=1;
		$formsms->witherrorsto=1;
		$formsms->withto=(isset($_POST['sendto'])?$_POST['sendto']:$user->user_mobile?$user->user_mobile:1);
		$formsms->withfile=2;
		$formsms->withbody=(isset($_POST['message'])?(empty($_POST['message'])?1:$_POST['message']):$langs->trans("ThisIsATestMessage"));
		$formsms->withbodyreadonly=0;
		$formsms->withcancel=1;
		$formsms->withfckeditor=0;
		// Tableau des substitutions
		$formsms->substit=$substitutionarrayfortest;
		// Tableau des parametres complementaires du post
		$formsms->param["action"]="send";
		$formsms->param["models"]="body";
		$formsms->param["smsid"]=0;
		$formsms->param["returnurl"]=$_SERVER["PHP_SELF"];

		$formsms->show_form();

		print '</div><br>';
	}

	// Affichage formulaire d'envoi de masse
	if ($action == 'smsmulti')
	{
		print '<br><div style="padding:15px;">';
		print_titre($langs->trans("SMSDecanetMultiSend"));

		// Cree l'objet formulaire mail
		include_once(dirname(__FILE__) ."/../core/class/html.formsmsdecanet.class.php");

		$formsms = new FormSms($db);
                $formsms->fromtype='user';
                $formsms->fromid=$user->id;
                $formsms->fromsms = (isset($_POST['fromsms'])?$_POST['fromsms']:($conf->global->MAIN_MAIL_SMS_FROM?$conf->global->MAIN_MAIL_SMS_FROM:$user->user_mobile));
		$formsms->withfromreadonly=0;
		$formsms->withsubstit=0;
		$formsms->withfrom=1;
		$formsms->witherrorsto=1;
		$formsms->withto=(isset($_POST['sendto'])?$_POST['sendto']:$user->user_mobile?$user->user_mobile:1);
		$formsms->withfile=2;
		$formsms->withbody=(isset($_POST['message'])?(empty($_POST['message'])?1:$_POST['message']):$langs->trans("ThisIsATestMessage"));
		$formsms->withbodyreadonly=0;
		$formsms->withcancel=1;
		$formsms->sendmulti=1;
		$formsms->withfckeditor=0;
                
		// Tableau des substitutions
		$formsms->substit=$substitutionarrayfortest;
		// Tableau des parametres complementaires du post
		$formsms->param["action"]="send";
		$formsms->param["models"]="body";
		$formsms->param["smsid"]=0;
		$formsms->param["returnurl"]=$_SERVER["PHP_SELF"];

		$formsms->show_form();

		print '</div><br>';
	}


llxFooter();

$db->close();
