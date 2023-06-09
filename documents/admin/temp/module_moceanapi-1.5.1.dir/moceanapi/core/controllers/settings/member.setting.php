<?php

dol_include_once("/moceanapi/core/helpers/helpers.php");
dol_include_once("/moceanapi/core/class/moceanapi_logger.class.php");
dol_include_once("/moceanapi/core/controllers/settings/mocean.controller.setting.php");
require_once DOL_DOCUMENT_ROOT."/core/class/html.form.class.php";
require_once DOL_DOCUMENT_ROOT."/commande/class/commande.class.php";
class SMS_Member_Setting extends MoceanBaseSettingController
{

	var $form;
	var $errors;
	var $log;
	var $page_name;
	var $db;
	var $context;
	public $trigger_events = [
		"MEMBER_CREATE",
		"MEMBER_VALIDATE",
		"MEMBER_SUBSCRIPTION_CREATE",
		"MEMBER_RESILIATE",
	];

	public $db_key = "MOCEANAPI_MEMBER_SETTING";

	function __construct($db)
	{
		$this->db = $db;
		$this->form = new Form($db);
		$this->log = new MoceanAPI_Logger();
		$this->errors = array();
		$this->context = 'member';
		$this->page_name = 'member_page_title';
	}

	private function get_default_settings()
	{
		$settings = array(
			"enable" 	=> "off",
			"send_from" => "",
			"send_on" 	=> array(
				"created" 		 		=> "on",
				"validated" 	 		=> "on",
				"subscription_created" 	=> "on",
				"terminated" 			=> "on",
			),
			"sms_templates" => array(
				"created" 				=> "Hi [member_firstname], we're processing your membership application",
				"validated"     		=> "Hi [member_firstname], your membership has been verified!",
				"subscription_created"  => "Hi [member_firstname], thank you for your contribution of \$[member_contribution_amount].",
				"terminated"  			=> "Hi [member_firstname], your membership has expired. Please renew now to retain access.",
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
					"enable" => GETPOST("enable"),
					"send_from" => GETPOST("send_from"),
					"send_on" => array(
						"created" 				=> GETPOST("send_on_created"),
						"validated" 			=> GETPOST("send_on_validated"),
						"subscription_created" 	=> GETPOST("send_on_subscription_created"),
						"terminated" 			=> GETPOST("send_on_terminated"),
					),
					"sms_templates" => array(
						"created" 				=> GETPOST("sms_templates_created"),
						"validated" 			=> GETPOST("sms_templates_validated"),
						"subscription_created" 	=> GETPOST("sms_templates_subscription_created"),
						"terminated" 			=> GETPOST("sms_templates_terminated"),
					),
				);

				$settings = json_encode($settings);

				$error = dolibarr_set_const($db, $this->db_key, $settings);
				if ($error < 1) {
					$this->log->add("MoceanAPI", "failed to update the member settings: " . print_r($settings, 1));
					$this->errors[] = "There was an error saving member settings.";
				}
				if(count($this->errors) > 0) {
					$this->add_notification_message("Failed to save member settings", "error");
				}
				else {
					$this->add_notification_message("Member settings saved");
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

	public function trigger_send_sms($object, $status)
	{
		// $object can either be class Adherent (member) or Subscription
		global $db;
		// check settings is it enabled.
		$settings = $this->get_settings();
		if($settings->enable != 'on') { return; }
		if($settings->send_on->created != 'on') { return; }

		$from = $settings->send_from;

		$member = $object;

		if(get_class($object) == 'Subscription') {
			$subscription = $object;
			$member = $subscription->context['member'];
			if(empty($member)) {
				$this->log->add("MoceanAPI", "member object is empty. Aborting");
				return;
			}
		}

		$to = validated_mobile_number($member->phone_mobile, $member->country_code);
		if(empty($to)) { return; }
		$message = $settings->sms_templates->{$status};
		$message = $this->replace_keywords_with_value($object, $message);

		moceanapi_send_sms($from, $to, $message, "Automation");
	}

	protected function replace_keywords_with_value($object, $message)
	{
		global $db;

		$member = $object;

		if(get_class($object) == 'Subscription') {
			$subscription = $object;
			$member = $subscription->context['member'];
			if(empty($member)) {
				$this->log->add("MoceanAPI", "member object is empty. Aborting");
				return;
			}
		}

		$keywords = array(
			'[member_id]'   				=> !empty($member->id) 			 ? $member->id :  '',
			'[member_firstname]'   			=> !empty($member->firstname) 	 ? $member->firstname :  '',
			'[member_lastname]'   			=> !empty($member->lastname) 	 ? $member->lastname :  '',
			'[company_name]'   				=> !empty($member->company) 	 ? $member->company :  '',
			'[member_country]'    			=> !empty($member->country) 	 ? $member->country :  '',
			'[member_country_code]' 		=> !empty($member->country_code) ? $member->country_code :  '',
			'[member_address]'    			=> !empty($member->address) 	 ? $member->address :  '',
			'[member_town]'    				=> !empty($member->town) 		 ? $member->town :  '',
			'[member_zip]'    				=> !empty($member->zip) 		 ? $member->zip :  '',
			'[member_mobile_phone]' 		=> !empty($member->phone_mobile) ? $member->phone_mobile :  '',
			'[membership_type]'    			=> !empty($member->type) 		 ? $member->type :  '',
			'[member_fax]'    				=> !empty($member->fax) 		 ? $member->fax :  '',
			'[member_email]'    			=> !empty($member->email) 		 ? $member->email :  '',
			'[member_url]'    				=> !empty($member->url) 		 ? $member->url :  '',
			'[member_contribution_amount]' 	=> !empty($subscription->amount) ? number_format($subscription->amount, 2) :  '',
			'[member_contribution_note]' 	=> !empty($subscription->note)   ? $subscription->note :  '',
		);

		$replaced_msg = str_replace(array_keys($keywords), array_values($keywords), $message);
		$this->log->add("MoceanAPI", "replaced_msg: " . $replaced_msg);
		return $replaced_msg;
	}

	public function get_keywords()
	{
		$keywords = array(
			'member' => array(
				'member_id',
				'member_firstname',
				'member_lastname',
				'company_name',
				'member_country',
				'member_country_code',
				'member_address',
				'member_town',
				'member_zip',
				'member_mobile_phone',
				'membership_type',
				'member_fax',
				'member_email',
				'member_url',
				'member_contribution_amount',
				'member_contribution_note',
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
			<!-- Submit -->
			<p>SMS will be sent to the member</p>

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
