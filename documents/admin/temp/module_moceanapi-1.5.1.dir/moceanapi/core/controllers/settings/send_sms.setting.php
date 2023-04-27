<?php
dol_include_once("/moceanapi/core/helpers/helpers.php");
dol_include_once("/moceanapi/core/class/moceanapi_logger.class.php");
dol_include_once("/moceanapi/core/controllers/contact.class.php");
dol_include_once("/moceanapi/core/controllers/settings/mocean.controller.setting.php");

class SMS_SendSMS_Setting extends MoceanBaseSettingController
{

	private $form;
	private $errors;
	private $log;
	private $page_name;
	private $db;
	private $context;
	private $thirdparty;

	function __construct($db)
	{
		$this->form = new Form($db);
		$this->log = new MoceanAPI_Logger();
		$this->errors = array();
		$this->context = 'send_sms';
		$this->page_name = 'send_sms_page_title';
		$this->thirdparty = new Societe($db);
	}

	public function validate_sms_form($data)
	{
		$error = false;
		if( empty($data['sms_from'])) {
			$this->add_error("From field is required");
			$error = true;
		}
		if( empty($data['sms_message']) )
		{
			$this->add_error("Message is required");
			$error = true;
		}

		return $error;
	}

	public function handle_send_sms_form()
	{
		global $db, $user;

		if(!empty($_POST) && !empty($_POST['action'])) {

			if ( !$user->rights->moceanapi->permission->write ) {
				accessforbidden();
			}

			$action=GETPOST('action');
			if($action == 'send_sms') {

				$sms_from       				= GETPOST("sms_from");
				$sms_contact_ids 				= GETPOST("sms_contact_ids");
				$sms_thirdparty_id 				= GETPOST("sms_thirdparty_id");
				$send_sms_to_thirdparty_flag	= GETPOST("send_sms_to_thirdparty_flag") == "on" ? true : false;
				$sms_message    				= GETPOST('sms_message');

				$post_data = array();
				$post_data['sms_contact_ids']				= $sms_contact_ids;
				$post_data['sms_thirdparty_id']				= $sms_thirdparty_id;
				$post_data['send_sms_to_thirdparty_flag']	= $send_sms_to_thirdparty_flag;
				$post_data['sms_from']						= $sms_from;
				$post_data['sms_message']					= $sms_message;



				$error = $this->validate_sms_form($post_data);

				if($error) {
					return;
				}

				$total_sms_responses = array();

				if(!empty($sms_thirdparty_id)) {
					if(empty($sms_contact_ids)) {
						$tp_obj = new ThirdPartyController($sms_thirdparty_id);
						$tp_phone_no = $tp_obj->get_thirdparty_mobile_number();
						$total_sms_responses[] = moceanapi_send_sms($sms_from, $tp_phone_no, $sms_message, "Send SMS");
					}
					else if(!empty($sms_contact_ids) && $send_sms_to_thirdparty_flag) {
						$tp_obj = new ThirdPartyController($sms_thirdparty_id);
						$tp_phone_no = $tp_obj->get_thirdparty_mobile_number();
						$total_sms_responses[] = moceanapi_send_sms($sms_from, $tp_phone_no, $sms_message, "Send SMS");
					}
				}

				if(isset($sms_contact_ids) && !empty($sms_contact_ids)) {
					foreach($sms_contact_ids as $sms_contact_id) {
						$contact = new ContactController($sms_contact_id);
						$sms_to = $contact->get_contact_mobile_number($sms_contact_id);
						$total_sms_responses[] = moceanapi_send_sms($sms_from, $sms_to, $sms_message, "Send SMS");
					}
				}
				$success_sms = 0;
				$total_sms = count($total_sms_responses);
				foreach($total_sms_responses as $sms_response) {
					if($sms_response['messages'][0]['status'] == 0) {
						$success_sms++;
					}
				}

				$response = array();
				$response['success'] = $success_sms;
				$response['failed'] = $total_sms - $success_sms;

				try {
					if(is_array($response)) {
						$this->add_notification_message("SMS sent successfully: {$response['success']}, Failed: {$response['failed']}");
					}
					else {
						$this->add_notification_message("Failed to send SMS", 'error');
					}

				} catch (Exception $e) {
					$this->add_notification_message("Critical error...", 'error');
					$this->log->add("MoceanAPI", "Error: " . $e->getMessage());
				}

			}

		}
	}

	private function add_error($error)
	{
		$this->errors[] = $error;
	}

	private function get_errors(){
		return $this->errors;
	}

	public function render()
	{
		global $conf, $user, $langs;
		$this->thirdparty->id = !empty($this->thirdparty->id) ? $this->thirdparty->id : 0;

		if(isset($_GET) && !empty($_GET)) {
			if(!empty($_GET["thirdparty_id"])) {
				$tp_id = intval($_GET["thirdparty_id"]);
				$this->thirdparty->fetch($tp_id);
			}
		}

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
		<form method="POST" enctype="multipart/form-data" action="<?php echo $_SERVER["PHP_SELF"] ?>" style="max-width: 500px">
			<?php if(! empty($this->get_errors()) ) { ?>
				<?php foreach($this->get_errors() as $error) { ?>
					<div class="error"><?php echo $error ?></div>
				<?php } ?>
			<?php } ?>
			<input type="hidden" name="action" value="send_sms">
			<input type="hidden" name="token" value="<?php echo newToken(); ?>">
			<!-- Balance -->

			<table class="border" width="100%">
				<!-- From -->

				<tr>
					<td width="200px"><?php echo $this->form->textwithpicto($langs->trans("MOCEAN_FROM"), "Your business name"); ?>*</td>
					<td>
						<input type="text" name="sms_from" size="30" value="<?php echo $conf->global->MOCEAN_FROM; ?>">
					</td>
				</tr>

				<!-- To -->
				<tr>
					<td width="200px">
						<?php echo $this->form->textwithpicto($langs->trans("SmsTo"), "The contact mobile you want to send SMS to"); ?>*
					</td>
					<td>
						<?php echo $this->form->select_thirdparty_list($this->thirdparty->id, 'sms_thirdparty_id') ?>
					</td>
				</tr>

				<tr>
					<td width="200px">
					</td>
					<td>
						<?php echo $this->form->selectcontacts($this->thirdparty->id, '', 'sms_contact_ids', 1, '', '', 1, '', false, 0, 0, [], '', '', true) ?>
					</td>
				</tr>

				<tr>
					<td width="200px" valign="top"><?php echo $langs->trans("Sms_To_Thirdparty_Flag") ?></td>
						<td>
							<input id="send_sms_to_thirdparty_flag" type="checkbox" name="send_sms_to_thirdparty_flag"></input>
						</td>
					</td>
				</tr>

				<!-- Message -->
				<tr>
					<td width="200px" valign="top"><?php echo $langs->trans("SmsText") ?>*</td>
						<td>
							<textarea cols="40" name="sms_message" id="message" rows="4"></textarea>
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
			<input style="float:right;" class="button" type="submit" name="submit" value="<?php echo $langs->trans("SendSMSButtonTitle") ?>">
		</form>
		<!-- End form SMS -->

		<script>
			jQuery(document).ready(function () {
				$("#send_sms_to_thirdparty_flag").closest("tr").hide();
				$("#sms_contact_ids").on("change", function () {
					var sms_contact_id = $("#sms_contact_ids").val();
					var thirdpartyTree = $("#sms_thirdparty_id");
					// if the tree exists
					if(thirdpartyTree.length > 0) {
						if(sms_contact_id.length > 0) {
							$("#send_sms_to_thirdparty_flag").closest("tr").show();
						}
						else {
							$("#send_sms_to_thirdparty_flag").closest("tr").hide();
						}
					}
				});
				$("#sms_thirdparty_id").on("change", function () {
					let chosen_tp_id = $(this).val();
					urlParams = new URLSearchParams(window.location.search);

					urlParams.set("thirdparty_id", chosen_tp_id);

					let baseURL = window.location.href.split('?')[0];
					let url = baseURL + "?" + urlParams.toString();
					window.location = url;
				});
			})
		</script>

		<?php
		// Page end
		print dol_get_fiche_end();

		llxFooter();
		?>
<?php
	}
}
