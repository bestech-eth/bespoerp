<?php

dol_include_once("/moceanapi/core/helpers/helpers.php");
dol_include_once("/moceanapi/core/class/moceanapi_logger.class.php");
dol_include_once("/moceanapi/core/controllers/settings/mocean.controller.setting.php");

class SMS_Project_Setting extends MoceanBaseSettingController
{

	var $form;
	var $errors;
	var $log;
	var $page_name;
	var $db;
	var $context;
	public $trigger_events = [
		"PROJECT_MODIFY",
	];

	public $db_key = "MOCEANAPI_PROJECT_SETTING";

	function __construct($db)
	{
		$this->db = $db;
		$this->form = new Form($db);
		$this->log = new MoceanAPI_Logger();
		$this->errors = array();
		$this->context = 'project';
		$this->page_name = 'project_page_title';
	}

	private function get_default_settings()
	{
		$settings = array(
			"enable" 	=> "off",
			"send_from" => "",
			"send_on" 	=> array(
				"prospection" 	=> "on",
				"qualification" => "off",
				"proposal" 		=> "off",
				"negotiation" 	=> "off",
			),
			"sms_templates" => array(
				"prospection" 	=> "Hello, you might be interested in our service",
				"qualification" => "We could potentially help you 10x your sales this year. Do you have 10 minutes ?",
				"proposal" 		=> "Thank you for your interest in our service, when would be a good time for us to meet for proposition ?",
				"negotiation" 	=> "We understand your concern, we are committed to help you through this problem you're facing.",
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
						"prospection" 	=> GETPOST("send_on_prospection"),
						"qualification" => GETPOST("send_on_qualification"),
						"proposal" 		=> GETPOST("send_on_proposal"),
						"negotiation" 	=> GETPOST("send_on_negotiation"),
					),
					"sms_templates" => array(
						"prospection" 	=> GETPOST("sms_templates_prospection"),
						"qualification" => GETPOST("sms_templates_qualification"),
						"proposal" 		=> GETPOST("sms_templates_proposal"),
						"negotiation" 	=> GETPOST("sms_templates_negotiation"),
					),
				);

				$settings = json_encode($settings);

				$error = dolibarr_set_const($db, $this->db_key, $settings);
				if ($error < 1) {
					$this->log->add("MoceanAPI", "failed to update the contact settings: " . print_r($settings, 1));
					$this->errors[] = "There was an error saving contact settings.";
				}
				if(count($this->errors) > 0) {
					$this->add_notification_message("Failed to save project settings", "error");
				}
				else {
					$this->add_notification_message("Project settings saved");
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

	public function trigger_send_sms(Project $object)
	{
		global $db;
		$settings = $this->get_settings();
		if($settings->enable != 'on') { return; }

		$lead_status = $this->get_lead_status($object->opp_status);
		$this->log->add("MoceanAPI", "Lead status: {$lead_status}");

		$this->log->add("MoceanAPI", "current action triggered: {$lead_status}");
		if($settings->send_on->{$lead_status} != 'on') { return; }

		$from = $settings->send_from;
		$thirdparty = new Societe($db);
		$thirdparty->fetch($object->socid);
		$to = validated_mobile_number($thirdparty->phone, $thirdparty->country_code);
		if(empty($to)) { return; }
		$message = $settings->sms_templates->{$lead_status};
		$message = $this->replace_keywords_with_value($object, $message);

		moceanapi_send_sms($from, $to, $message, "Automation");
	}

	private function get_lead_status($opp_status)
	{
		switch($opp_status) {
			case 1:
				return "prospection";
			case 2:
				return "qualification";
			case 3:
				return "proposal";
			case 4:
				return "negotiation";
			default:
				return "";
		}
	}

	protected function replace_keywords_with_value(Project $object, $message)
	{
		global $db;
		$company = new Societe($db);
		$company->fetch($object->socid);
		$keywords = array(
			'[id]'            		=> !empty($object->id)         		? $object->id : '',
			'[lead_status]'    		=> !empty($object->opp_status) 		? $this->get_lead_status($object->opp_status) : '',
			'[date_start]'    		=> !empty($object->date_start) 		? date( "m/d/Y H:i:s", $object->date_start) : '',
			'[date_end]'    		=> !empty($object->date_end) 		? date( "m/d/Y H:i:s", $object->date_end) : '',
			'[company_id]'   		=> !empty($company->id) 			? $company->id : '',
			'[company_name]'   		=> !empty($company->name) 			? $company->name : '',
			'[company_alias_name]'  => !empty($company->name_alias) 	? $company->name_alias : '',
			'[company_address]'    	=> !empty($company->address) 		? $company->address : '',
			'[company_zip]'    		=> !empty($company->zip) 			? $company->zip : '',
			'[company_town]'    	=> !empty($company->town) 			? $company->town : '',
			'[company_phone]'    	=> !empty($company->phone) 			? $company->phone : '',
			'[company_fax]'    		=> !empty($company->fax) 			? $company->fax : '',
			'[company_email]'    	=> !empty($company->email) 			? $company->email : '',
			'[company_url]'    		=> !empty($company->url) 			? $company->url : '',
			'[company_capital]'     => !empty($company->capital) 		? $company->capital : '',
		);

		$replaced_msg = str_replace(array_keys($keywords), array_values($keywords), $message);
		$this->log->add("MoceanAPI", "replaced_msg: " . $replaced_msg);
		return $replaced_msg;
	}

	public function get_keywords()
	{
		$keywords = array(
			'project' => array(
				'id',
				'lead_status',
				'date_start',
				'date_end',
			),
			'company' => array(
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
		global $conf, $user, $langs;
		$settings = $this->get_settings();
?>
		<!-- Begin form SMS -->

		<?php
		llxHeader('', $langs->trans($this->page_name));
		print load_fiche_titre($langs->trans($this->page_name), '', 'title_setup');
		$head = moceanapiAdminPrepareHead();
		print dol_get_fiche_head($head, $this->context, $langs->trans($this->page_name), -1);

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
			<p>SMS will be sent to the associated third party</p>
			<!-- Submit -->
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
