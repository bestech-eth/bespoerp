<?php

dol_include_once("/moceanapi/core/helpers/helpers.php");
dol_include_once("/moceanapi/core/class/moceanapi_logger.class.php");
dol_include_once("/moceanapi/core/controllers/settings/mocean.controller.setting.php");

require_once DOL_DOCUMENT_ROOT."/core/class/html.form.class.php";
require_once DOL_DOCUMENT_ROOT."/ticket/class/ticket.class.php";
require_once DOL_DOCUMENT_ROOT."/user/class/user.class.php";

class SMS_Ticket_Setting extends MoceanBaseSettingController
{

	var $form;
	var $errors;
	var $log;
	var $page_name;
	var $db;
	var $context;
	public $trigger_events = [
		'TICKET_MODIFY',
		'TICKET_ASSIGNED',
		'TICKET_CREATE',
		'TICKET_CLOSE',
		'TICKET_DELETE',
	];

	public $db_key = "MOCEANAPI_TICKET_SETTING";

	function __construct($db)
	{
		$this->db = $db;
		$this->form = new Form($db);
		$this->log = new MoceanAPI_Logger();
		$this->errors = array();
		$this->context = 'ticket';
		$this->page_name = 'ticket_page_title';
	}

	private function get_default_settings()
	{
		$settings = array(
			"enable" 	=> "off",
			"send_from" => "",
			"send_on" 	=> array(
				"created" 		=> "on",
				"closed" 		=> "off",
				"read" 			=> "off",
				"abandoned" 	=> "off",
				"assigned" 		=> "off",
			),
			"sms_templates" => array(
				"created" 		=> "Hi [company_name], your ticket: ([ticket_ref]) has been created. Our agents will attend to you shortly",
				"closed" 		=> "Hi [company_name], your ticket: ([ticket_ref]) has been closed",
				"read" 			=> "Hi [company_name], your ticket: ([ticket_ref]) has been read by [assigned_user_fullname]",
				"abandoned" 	=> "Hi [company_name], your ticket: ([ticket_ref]) has been canceled",
				"assigned" 		=> "Hi [company_name], your ticket has been assigned to [assigned_user_fullname]",
			),
		);
		return json_encode($settings);
	}

	public function update_settings()
	{
		global $db, $user;

		if(!empty($_POST)) {
			if ( !$user->rights->moceanapi->permission->write ) {
				accessforbidden();
			}
			// Handle Update
			// must json encode before updating
			$action = GETPOST("action");
			if ($action == "update_{$this->context}") {
				$settings = array(
					"enable" 	=> GETPOST("enable"),
					"send_from" => GETPOST("send_from"),
					"send_on" 	=> array(
						"created" 	=> GETPOST("send_on_created"),
						"closed" 	=> GETPOST("send_on_closed"),
						"read" 		=> GETPOST("send_on_read"),
						"abandoned" => GETPOST("send_on_abandoned"),
						"assigned" 	=> GETPOST("send_on_assigned"),
					),
					"sms_templates" => array(
						"created" 		=> GETPOST("sms_templates_created"),
						"closed" 		=> GETPOST("sms_templates_closed"),
						"read" 			=> GETPOST("sms_templates_read"),
						"abandoned" 	=> GETPOST("sms_templates_abandoned"),
						"assigned" 		=> GETPOST("sms_templates_assigned"),
					),
				);

				$settings = json_encode($settings);

				$error = dolibarr_set_const($db, $this->db_key, $settings);
				if ($error < 1) {
					$this->log->add("MoceanAPI", "failed to update the ticket settings: " . print_r($settings, 1));
					$this->errors[] = "There was an error saving ticket settings.";
				}
				if(count($this->errors) > 0) {
					$this->add_notification_message("Failed to save ticket settings", "error");
				}
				else {
					$this->add_notification_message("Ticket settings saved");
				}
			}
		}
	}

	public function get_settings()
	{
		global $conf;
		// no exists will give null
		$settings = $conf->global->{$this->db_key};
		if (empty($settings)) {
			// use default
			$settings = $this->get_default_settings();
		}
		return json_decode($settings);
	}

	public function delete_settings()
	{
		global $db, $conf;

		$deleted = dolibarr_del_const($db, $this->db_key);
		if($deleted < 0) {
			$this->log->add("MoceanAPI", "there was an issue deleting {$this->db_key} settings");
		}
		$this->log->add("MoceanAPI", "Successfully deleted {$this->db_key} settings");

	}

	public function trigger_sms(Ticket $object, $action='')
	{
		global $db;
		// check settings is it enabled.
		$settings = $this->get_settings();
		if($settings->enable != 'on') { return; }

		if(empty($action)) {
			$ticket = new Ticket($db);
			$ticket->fetch($object->id);
			switch ($ticket->status) {
				case 0:
					$action = 'not_read';
					break;
				case 1:
					$action = 'read';
					break;
				case 2:
					$action = 'assigned';
					break;
				case 3:
					$action = 'in_progress';
					break;
				case 5:
					$action = 'need_more_info';
					break;
				case 7:
					$action = 'waiting';
					break;
				default:
					$action = 'not_available';
					break;
			}
		}

		if($settings->send_on->{$action} != 'on') { return; }
		$this->log->add("MoceanAPI", "current ticket action triggered: {$action}");

		$company = new Societe($db);
		$company->fetch($object->fk_soc);

		$from = $settings->send_from;
		$to = validated_mobile_number($company->phone, $company->country_code);
		if(empty($to)) {
			$this->log->add("MoceanAPI", "SMS to is empty. Aborting SMS");
			return;
		}
		$message = $settings->sms_templates->{$action};
		$message = $this->replace_keywords_with_value($object, $message);

		moceanapi_send_sms($from, $to, $message, "Automation");
	}

	protected function replace_keywords_with_value(Ticket $object, $message)
	{
		global $db, $langs;
		$company = new Societe($db);
		$company->fetch($object->fk_soc);

		$assigned_user = new User($db);
		$assigned_user->fetch($object->fk_user_assign);
		$keywords = array(
			'[ticket_id]'           => !empty($object->id)         		? $object->id : 'N/A',
			'[ticket_track_id]'    	=> !empty($object->track_id) 		? $object->track_id : 'N/A',
			'[ticket_priority]'    	=> !empty($object->severity_label) 	? $object->severity_label : 'N/A',
			'[ticket_ref]'    		=> !empty($object->ref) 			? $object->ref : 'N/A',
			'[ticket_status]'    	=> !empty($object->getLibStatut(1)) ? $object->getLibStatut(1) : 'N/A',
			'[ticket_subject]'    	=> !empty($object->subject) ? $object->subject : 'N/A',
			'[ticket_message]'    	=> !empty($object->message) ? $object->message : 'N/A',
			'[assigned_user_town]'    			=> !empty($assigned_user->town) 			? $assigned_user->town : 'N/A',
			'[assigned_user_country]'     		=> !empty($assigned_user->country) 		? $assigned_user->country : 'N/A',
			'[assigned_user_country_code]'    	=> !empty($assigned_user->country_code)    ? $assigned_user->country_code : 'N/A',
			'[assigned_user_email]'    			=> !empty($assigned_user->email) 			? $assigned_user->email : 'N/A',
			'[assigned_user_note_public]'    	=> !empty($assigned_user->note_public) 	? $assigned_user->note_public : 'N/A',
			'[assigned_user_note_private]'    	=> !empty($assigned_user->note_private) 	? $assigned_user->note_private : 'N/A',
			'[assigned_user_firstname]'    		=> !empty($assigned_user->firstname) 		? $assigned_user->firstname : 'N/A',
			'[assigned_user_lastname]'    		=> !empty($assigned_user->lastname) 		? $assigned_user->lastname : 'N/A',
			'[assigned_user_fullname]'    		=> !empty($assigned_user->getFullName($langs)) 		? $assigned_user->getFullName($langs) : 'N/A',
			'[company_id]'   		=> !empty($company->id) 			? $company->id : 'N/A',
			'[company_name]'   		=> !empty($company->name) 			? $company->name : 'N/A',
			'[company_alias_name]'  => !empty($company->name_alias) 	? $company->name_alias : 'N/A',
			'[company_address]'    	=> !empty($company->address) 		? $company->address : 'N/A',
			'[company_zip]'    		=> !empty($company->zip) 			? $company->zip : 'N/A',
			'[company_town]'    	=> !empty($company->town) 			? $company->town : 'N/A',
			'[company_phone]'    	=> !empty($company->phone) 			? $company->phone : 'N/A',
			'[company_fax]'    		=> !empty($company->fax) 			? $company->fax : 'N/A',
			'[company_email]'    	=> !empty($company->email) 			? $company->email : 'N/A',
			'[company_url]'    		=> !empty($company->url) 			? $company->url : 'N/A',
			'[company_capital]'     => !empty($company->capital) 		? $company->capital : 'N/A',
		);

		$replaced_msg = str_replace(array_keys($keywords), array_values($keywords), $message);
		$this->log->add("MoceanAPI", "replaced_msg: " . $replaced_msg);
		return $replaced_msg;
	}

	public function get_keywords()
	{
		$keywords = array(
			'ticket' => array(
				'id',
				'track_id',
				'priority',
				'ref',
				'ticket_status',
				'subject',
				'message',
			),
			'Assigned User' => array(
				'assigned_user_town',
				'assigned_user_country',
				'assigned_user_country_code',
				'assigned_user_email',
				'assigned_user_note_public',
				'assigned_user_note_private',
				'assigned_user_firstname',
				'assigned_user_lastname',
			),
			'Third Party' => array(
				'company_id',
				'company_name',
				'company_alias_name',
				'company_address',
				'company_zip',
				'company_town',
				'company_phone',
				'company_fax',
				'company_email',
				'company_skype',
				'company_twitter',
				'company_facebook',
				'company_linkedin',
				'company_url',
				'company_capital',
			),
		);
		return $keywords;
	}

	public function render()
	{
		global $conf, $user, $langs, $db;
		$settings = $this->get_settings();
?>
		<!-- Begin form SMS -->

		<?php
		llxHeader('', $langs->trans($this->page_name));
		print load_fiche_titre($langs->trans($this->page_name), '', 'title_setup');
		$head = moceanapiAdminPrepareHead();
		print dol_get_fiche_head($head, 'ticket', $langs->trans($this->page_name), -1);

		if(!empty($this->notification_messages)) {
			foreach($this->notification_messages as $notification_message) {
				dol_htmloutput_mesg($notification_message['message'], [], $notification_message['style']);
			}
		}

		?>
		<?php if ($this->errors) { ?>
			<?php foreach ($this->errors as $error) { ?>
				<p style="color: red;"><?php echo $error ?></p>
			<?php } ?>
		<?php } ?>
		<form method="POST" action="<?php echo $_SERVER["PHP_SELF"] ?>">
			<input type="hidden" name="token" value="<?php echo newToken(); ?>">
			<input type="hidden" name="action" value="<?php echo "update_{$this->context}" ?>">

			<table class="border mocean-table">
				<!-- SMS Notification -->
				<tr>
					<td width="200px"><?php echo $langs->trans("sms_form_enable_notification") ?></td>
					<td>
						<label for="enable">
							<input type="hidden" name="enable" value="off"></input>
							<input id="enable" type="checkbox" name="enable" <?php echo ($settings->enable == 'on') ? "checked" : '' ?>></input>
							<?php echo $langs->trans("moceanapi_{$this->context}_enable") ?>
						</label>
					</td>
				</tr>
				<!-- SMS Sender -->
				<tr>
					<td width="200px"><?php echo $langs->trans("sms_form_send_from") ?></td>
					<td>
						<input type="text" name="send_from" value="<?php echo $settings->send_from ?>">
					</td>
				</tr>

				<!-- SMS Send On -->
				<div>

					<tr>
						<td width="200px"><?php echo $langs->trans("sms_form_send_on") ?></td>
						<td>
							<?php foreach ($settings->send_on as $key => $value) { ?>
								<label for="<?php echo "send_on_{$key}" ?>">
									<input type="hidden" name="<?php echo "send_on_{$key}" ?>" value="off"></input>
									<input id="<?php echo "send_on_{$key}" ?>" name="<?php echo "send_on_{$key}" ?>" <?php echo ($value == 'on') ? "checked" : '' ?> type="checkbox">
									</input>

									<?php echo $langs->trans("moceanapi_{$this->context}_send_on_{$key}") ?>
								</label>
							<?php } ?>
						</td>
					</tr>


				</div>
				<!-- SMS Templates  -->

				<?php foreach ($settings->sms_templates as $key => $value) { ?>
					<tr>
						<td width="200px"><?php echo $langs->trans("moceanapi_{$this->context}_sms_templates_{$key}") ?></td>
						<td>
							<label for="<?php echo "sms_templates_{$key}" ?>">
								<textarea id="<?php echo "sms_templates_{$key}" ?>" name="<?php echo "sms_templates_{$key}" ?>" cols="40" rows="4"><?php echo $value ?></textarea>
							</label>
							<p>Customize your SMS with keywords
								<button type="button" class="moceanapi_open_keyword" data-attr-target="<?php echo "sms_templates_{$key}" ?>">
									Keywords
								</button>
							</p>
						</td>
					</tr>
				<?php } ?>
			</table>
			<!-- Submit -->
			<p>SMS will be sent to the associated third party</p>

			<center>
				<input class="button" type="submit" name="submit" value="<?php echo $langs->trans("sms_form_save") ?>">
			</center>
		</form>

		<script>
			const entity_keywords = <?php echo json_encode($this->get_keywords()); ?>;
			jQuery(function($) {
				var $div = $('<div />').appendTo('body');
				$div.attr('id', `keyword-modal`);
				$div.attr('class', "modal");
				$div.attr('style', "display: none;");

				$('.moceanapi_open_keyword').click(function(e) {
					const target = $(e.target).attr('data-attr-target');

					caretPosition = document.getElementById(target).selectionStart;


					const buildTable = function(keywords) {
						const chunkedKeywords = keywords.array_chunk(3);

						let tableCode = '';
						chunkedKeywords.forEach(function(row, rowIndex) {
							if (rowIndex === 0) {
								tableCode += '<table class="widefat fixed striped"><tbody>';
							}

							tableCode += '<tr>';
							row.forEach(function(col) {
								tableCode += `<td class="column"><button class="button-link" onclick="moceansms_bind_text_to_field('${target}', '[${col}]')">[${col}]</button></td>`;
							});
							tableCode += '</tr>';

							if (rowIndex === chunkedKeywords.length - 1) {
								tableCode += '</tbody></table>';
							}
						});

						return tableCode;
					};

					$('#keyword-modal').off();
					$('#keyword-modal').on($.modal.AFTER_CLOSE, function() {
						document.getElementById(target).focus();
						document.getElementById(target).setSelectionRange(caretPosition, caretPosition);
					});

					let mainTable = '';
					for (let [key, value] of Object.entries(entity_keywords)) {
						mainTable += `<h3>${capitalize_first_letter(key.replaceAll('_', ' '))}</h3>`;
						mainTable += buildTable(value);
					}

					mainTable += '<div style="margin-top: 10px"><small>*Press on keyword to add to sms template</small></div>';

					$('#keyword-modal').html(mainTable);
					$('#keyword-modal').modal();
				});
			});

			function capitalize_first_letter(str) {
				return (str + '').replace(/^([a-z])|\s+([a-z])/g, function($1) {
					return $1.toUpperCase();
				});
			}

			function moceansms_bind_text_to_field(target, keyword) {
				const startStr = document.getElementById(target).value.substring(0, caretPosition);
				const endStr = document.getElementById(target).value.substring(caretPosition);
				document.getElementById(target).value = startStr + keyword + endStr;
				caretPosition += keyword.length;
			}

			Object.defineProperty(Array.prototype, 'array_chunk', {
				value: function(chunkSize) {
					const array = this;
					return [].concat.apply([],
						array.map(function(elem, i) {
							return i % chunkSize ? [] : [array.slice(i, i + chunkSize)];
						})
					);
				}
			});
		</script>
		<?php
		// Page end
		print dol_get_fiche_end();

		llxFooter();
		?>
<?php
	}
}
