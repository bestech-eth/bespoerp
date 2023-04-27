<?php
/* Copyright (C) 2022 SuperAdmin
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
 * \file    moceanapi/class/actions_moceanapi.class.php
 * \ingroup moceanapi
 * \brief   Example hook overload.
 *
 * Put detailed description here.
 */

dol_include_once("/moceanapi/vendor/autoload.php");
dol_include_once("/moceanapi/core/helpers/helpers.php");
dol_include_once("/moceanapi/class/mocean_voice_call.class.php");
dol_include_once("/moceanapi/core/class/moceanapi_logger.class.php");
dol_include_once("/moceanapi/core/class/moceanapi_voice_call.db.php");

use Mocean\Client;
use Mocean\Client\Credentials\Basic;
use Mocean\Voice\Mc;
use Mocean\Voice\McBuilder;

/**
 * Class ActionsMoceanAPI
 */
class ActionsMoceanAPI
{
	/**
	 * @var DoliDB Database handler.
	 */
	public $db;
	public $voice_call_resp;
	private $log;
	private $can_initiate_call;
	/**
	 * @var string Error code (or message)
	 */
	public $error = '';

	/**
	 * @var array Errors
	 */
	public $errors = array();


	/**
	 * @var array Hook results. Propagated to $hookmanager->resArray for later reuse
	 */
	public $results = array();

	/**
	 * @var string String displayed by executeHook() immediately after return
	 */
	public $resprints;


	/**
	 * Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
		$this->log = new MoceanAPI_Logger();
	}


	/**
	 * Execute action
	 *
	 * @param	array			$parameters		Array of parameters
	 * @param	CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param	string			$action      	'add', 'update', 'view'
	 * @return	int         					<0 if KO,
	 *                           				=0 if OK but we want to process standard actions too,
	 *                            				>0 if OK and we want to replace standard actions.
	 */
	public function getNomUrl($parameters, &$object, &$action)
	{
		global $db, $langs, $conf, $user;
		$this->resprints = '';
		return 0;
	}

	/**
	 * Overloading the doActions function : replacing the parent's function with the one below
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function doActions($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $user, $langs, $db;

		$mocean_api_key 	  	= $conf->global->MOCEAN_API_KEY;
		$mocean_api_secret    	= $conf->global->MOCEAN_API_SECRET;
		$mocean_callback_number = $conf->global->MOCEAN_CALLBACK_NUMBER;

		$error = 0; // Error counter

		$vc_class = new MoceanVoiceCall($db);
		$db_obj = new MoceanVoiceCallDatabase($db);

		/* print_r($parameters); print_r($object); echo "action: " . $action; */
		if (in_array($parameters['currentcontext'], array('contactcard'))) {	    // do something only for the context 'somecontext1' or 'somecontext2'
			// Do what you want here...
			// You can for example call global vars like $fieldstosearchall to overwrite them, or update database depending on $action and $_POST values.
			$this->can_initiate_call = $vc_class->canInitiateCall($object->id, strtotime("now"));
			if( $action == 'clicktodial' && $this->can_initiate_call ) {

				$to = GETPOST("to");
				$mocean = new Client(new Basic($mocean_api_key, $mocean_api_secret));
				$mcBuilder = McBuilder::create()
					->add(Mc::dial($mocean_callback_number)); // virtual number

				$result = $mocean->voice()->call([
					'mocean-to' => $to, // who he wants to call
					'mocean-command' => $mcBuilder,
					'mocean-from' => $mocean_callback_number // virtual number
				]);

				$this->log->add("MoceanAPI", "Voice call initiated. Response as below");
				$this->log->add("MoceanAPI", print_r($result, 1));
				$this->voice_call_resp = $result;
				$db_obj->insert($mocean_callback_number, $to, $object->id, "contact", $result['calls'][0]['status']);

				$vc_class->setLastVoiceCallSent($object->id, strtotime("now"));
				?>
				<script>
					let url = window.location.href.split('&')[0];
					window.history.pushState("", document.title, url)
				</script>
				<?php
			}
		}

		if (!$error) {
			// $this->results = array('myreturn' => 999);
			// $this->resprints = 'A text to show';
			return 0; // or return 1 to replace standard code
		} else {
			$this->errors[] = 'Error message';
			return -1;
		}
	}

	public function addMoreActionsButtons($parameters, Contact &$object, &$action, $hookmanager)
	{
		global $langs, $conf, $db;
		$mocean_callback_number = $conf->global->MOCEAN_CALLBACK_NUMBER;
		// if virutal number is set $conf->MOCEAN_VIRTUAL_NUMBER
		$phone = $object->phone_mobile;
		$cc = $object->country_code;
		$vc_class = new MoceanVoiceCall($db);

		$validated_phone = validated_mobile_number($phone, $cc);
		$disabled_button = '<div id="mocean-disabled-btn" class="inline-block divButAction"><a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("moceanapi_voice_call_disabled_title")).'">'.$langs->trans('moceanapi_voice_call_button_label').'<span id="cd-timer"> (2:00)</span></a></div>';
		$enabled_button = '<div id="mocean-enabled-btn" class="inline-block divButAction"><a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=clicktodial&to='.$validated_phone.'">'.$langs->trans('moceanapi_voice_call_button_label').'</a></div>';
		if( !$this->can_initiate_call ) {
			print $disabled_button;
			?>
				<script>
					function startTimer(duration, display, $) {
						var timer = duration, minutes, seconds;
						setInterval(function () {
							minutes = parseInt(timer / 60, 10);
							seconds = parseInt(timer % 60, 10);

							minutes = minutes < 10 ? "0" + minutes : minutes;
							seconds = seconds < 10 ? "0" + seconds : seconds;

							display.text(" (" + minutes + ":" + seconds + ")");

							var btnLabel = "<?php echo $langs->trans('moceanapi_voice_call_button_label') ?>";
							var submitUrl = "<?php echo $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=clicktodial&to='.$validated_phone ?>";
							var anchorTag = `<a class="butAction" href="${submitUrl}">${btnLabel}</a>`;
							if (--timer < 0) {
								timer = duration;
								$("#mocean-disabled-btn").empty();
								$("#mocean-disabled-btn").append(anchorTag);
							}
						}, 1000);
					}

					jQuery(function ($) {

						var twoMinutes = 120 - <?php echo $vc_class->getTsDifferenceInSeconds($object->id); ?>,
							display = $('#cd-timer');
						startTimer(twoMinutes, display, $);
					});
				</script>
			<?php
			return 0;
		}

		if($mocean_callback_number) {
			if(!empty($validated_phone)) {
				print $enabled_button;
			} else {
				dol_htmloutput_mesg("User phone number is invalid", [], 'error');
				print $disabled_button;
			}
		} else {
			print $disabled_button;
		}
		return 0;
	}

	public function beforeBodyClose($parameters, &$object, &$action, $hookmanager)
	{
		global $db;
		$act = GETPOST("action");
		if( $act == 'clicktodial' && !$this->can_initiate_call ) {
			dol_htmloutput_mesg("Please wait 2 minutes before calling again.", [], 'error');
			return 0;
		}

		$vc_resp = $this->voice_call_resp;

		if(isset($vc_resp) && !empty($vc_resp)) {
			if( !empty($vc_resp['err_msg']) ) {
				dol_htmloutput_mesg($vc_resp['err_msg'], [], 'error');
				return 0;
			}
			else {
				$to = $vc_resp['calls'][0]['receiver'];
				dol_htmloutput_mesg("Call initiated to: {$to}");
				return 0;
			}
		}

	}

	/**
	 * Overloading the doMassActions function : replacing the parent's function with the one below
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function doMassActions($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $user, $langs;

		$error = 0; // Error counter

		/* print_r($parameters); print_r($object); echo "action: " . $action; */
		if (in_array($parameters['currentcontext'], array('somecontext1', 'somecontext2'))) {		// do something only for the context 'somecontext1' or 'somecontext2'
			foreach ($parameters['toselect'] as $objectid) {
				// Do action on each object id
			}
		}

		if (!$error) {
			$this->results = array('myreturn' => 999);
			$this->resprints = 'A text to show';
			return 0; // or return 1 to replace standard code
		} else {
			$this->errors[] = 'Error message';
			return -1;
		}
	}


	/**
	 * Overloading the addMoreMassActions function : replacing the parent's function with the one below
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function addMoreMassActions($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $user, $langs;

		$error = 0; // Error counter
		$disabled = 1;

		/* print_r($parameters); print_r($object); echo "action: " . $action; */
		if (in_array($parameters['currentcontext'], array('somecontext1', 'somecontext2'))) {		// do something only for the context 'somecontext1' or 'somecontext2'
			$this->resprints = '<option value="0"'.($disabled ? ' disabled="disabled"' : '').'>'.$langs->trans("MoceanAPIMassAction").'</option>';
		}

		if (!$error) {
			return 0; // or return 1 to replace standard code
		} else {
			$this->errors[] = 'Error message';
			return -1;
		}
	}



	/**
	 * Execute action
	 *
	 * @param	array	$parameters     Array of parameters
	 * @param   Object	$object		   	Object output on PDF
	 * @param   string	$action     	'add', 'update', 'view'
	 * @return  int 		        	<0 if KO,
	 *                          		=0 if OK but we want to process standard actions too,
	 *  	                            >0 if OK and we want to replace standard actions.
	 */
	public function beforePDFCreation($parameters, &$object, &$action)
	{
		global $conf, $user, $langs;
		global $hookmanager;

		$outputlangs = $langs;

		$ret = 0; $deltemp = array();
		dol_syslog(get_class($this).'::executeHooks action='.$action);

		/* print_r($parameters); print_r($object); echo "action: " . $action; */
		if (in_array($parameters['currentcontext'], array('somecontext1', 'somecontext2'))) {		// do something only for the context 'somecontext1' or 'somecontext2'
		}

		return $ret;
	}

	/**
	 * Execute action
	 *
	 * @param	array	$parameters     Array of parameters
	 * @param   Object	$pdfhandler     PDF builder handler
	 * @param   string	$action         'add', 'update', 'view'
	 * @return  int 		            <0 if KO,
	 *                                  =0 if OK but we want to process standard actions too,
	 *                                  >0 if OK and we want to replace standard actions.
	 */
	public function afterPDFCreation($parameters, &$pdfhandler, &$action)
	{
		global $conf, $user, $langs;
		global $hookmanager;

		$outputlangs = $langs;

		$ret = 0; $deltemp = array();
		dol_syslog(get_class($this).'::executeHooks action='.$action);

		/* print_r($parameters); print_r($object); echo "action: " . $action; */
		if (in_array($parameters['currentcontext'], array('somecontext1', 'somecontext2'))) {
			// do something only for the context 'somecontext1' or 'somecontext2'
		}

		return $ret;
	}



	/**
	 * Overloading the loadDataForCustomReports function : returns data to complete the customreport tool
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function loadDataForCustomReports($parameters, &$action, $hookmanager)
	{
		global $conf, $user, $langs;

		$langs->load("moceanapi@moceanapi");

		$this->results = array();

		$head = array();
		$h = 0;

		if ($parameters['tabfamily'] == 'moceanapi') {
			$head[$h][0] = dol_buildpath('/module/index.php', 1);
			$head[$h][1] = $langs->trans("Home");
			$head[$h][2] = 'home';
			$h++;

			$this->results['title'] = $langs->trans("MoceanAPI");
			$this->results['picto'] = 'moceanapi@moceanapi';
		}

		$head[$h][0] = 'customreports.php?objecttype='.$parameters['objecttype'].(empty($parameters['tabfamily']) ? '' : '&tabfamily='.$parameters['tabfamily']);
		$head[$h][1] = $langs->trans("CustomReports");
		$head[$h][2] = 'customreports';

		$this->results['head'] = $head;

		return 1;
	}



	/**
	 * Overloading the restrictedArea function : check permission on an object
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int 		      			  	<0 if KO,
	 *                          				=0 if OK but we want to process standard actions too,
	 *  	                            		>0 if OK and we want to replace standard actions.
	 */
	public function restrictedArea($parameters, &$action, $hookmanager)
	{
		global $user;

		if ($parameters['features'] == 'myobject') {
			if ($user->rights->moceanapi->myobject->read) {
				$this->results['result'] = 1;
				return 1;
			} else {
				$this->results['result'] = 0;
				return 1;
			}
		}

		return 0;
	}

	/**
	 * Execute action completeTabsHead
	 *
	 * @param   array           $parameters     Array of parameters
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         'add', 'update', 'view'
	 * @param   Hookmanager     $hookmanager    hookmanager
	 * @return  int                             <0 if KO,
	 *                                          =0 if OK but we want to process standard actions too,
	 *                                          >0 if OK and we want to replace standard actions.
	 */
	public function completeTabsHead(&$parameters, &$object, &$action, $hookmanager)
	{
		global $langs, $conf, $user;

		if (!isset($parameters['object']->element)) {
			return 0;
		}
		if ($parameters['mode'] == 'remove') {
			// utilisé si on veut faire disparaitre des onglets.
			return 0;
		} elseif ($parameters['mode'] == 'add') {
			$langs->load('moceanapi@moceanapi');
			// utilisé si on veut ajouter des onglets.
			$counter = count($parameters['head']);
			$element = $parameters['object']->element;
			$id = $parameters['object']->id;
			// verifier le type d'onglet comme member_stats où ça ne doit pas apparaitre
			// if (in_array($element, ['societe', 'member', 'contrat', 'fichinter', 'project', 'propal', 'commande', 'facture', 'order_supplier', 'invoice_supplier'])) {
			if (in_array($element, ['context1', 'context2'])) {
				$datacount = 0;

				$parameters['head'][$counter][0] = dol_buildpath('/moceanapi/moceanapi_tab.php', 1) . '?id=' . $id . '&amp;module='.$element;
				$parameters['head'][$counter][1] = $langs->trans('MoceanAPITab');
				if ($datacount > 0) {
					$parameters['head'][$counter][1] .= '<span class="badge marginleftonlyshort">' . $datacount . '</span>';
				}
				$parameters['head'][$counter][2] = 'moceanapiemails';
				$counter++;
			}
			if ($counter > 0 && (int) DOL_VERSION < 14) {
				$this->results = $parameters['head'];
				// return 1 to replace standard code
				return 1;
			} else {
				// en V14 et + $parameters['head'] est modifiable par référence
				return 0;
			}
		}
	}

	/* Add here any other hooked methods... */
}
