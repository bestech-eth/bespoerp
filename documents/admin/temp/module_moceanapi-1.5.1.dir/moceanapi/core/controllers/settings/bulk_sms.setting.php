<?php
dol_include_once("/moceanapi/core/helpers/helpers.php");
dol_include_once("/moceanapi/core/class/moceanapi_logger.class.php");
dol_include_once("/moceanapi/core/controllers/settings/mocean.controller.setting.php");
dol_include_once("/moceanapi/core/controllers/settings/contact.setting.php");

require_once DOL_DOCUMENT_ROOT."/core/class/html.formcompany.class.php";

class SMS_BulkSMS_Setting extends MoceanBaseSettingController
{

	private $form;
	private $form_company;
	private $errors;
	private $log;
	private $page_name;
	private $db;
	private $context;

	function __construct($db)
	{
		$this->db = $db;
		$this->form = new Form($db);
		$this->form_company = new FormCompany($db);
		$this->log = new MoceanAPI_Logger();
		$this->errors = array();
		$this->context = 'bulk_sms';
		$this->page_name = 'bulk_sms_page_title';
	}

	public function validate_sms_form($post_data)
	{
		if($post_data['mocean_to'] == 'all_contacts_of_tp') {
			if(empty($post_data['socid'])) {
				$this->errors[] = "Third party is required";
			}
		}
		if($post_data['mocean_to'] == 'spec_phone_numbers') {
			$regex_pattern = '/^\d+(?:,\d+)*$/';
			$phone_numbers = $post_data['phone_numbers'];

			if ( !preg_match($regex_pattern, $phone_numbers) ) {
				$this->errors[] = "Mobile phone must be comma separated";
			}
		}
		$filter_by = filter_var($post_data['filter_by'], FILTER_SANITIZE_STRING);
		$country_id = filter_var($post_data['country_id'], FILTER_SANITIZE_STRING);
		$tp_type = filter_var($post_data['tp_type'], FILTER_SANITIZE_STRING);
		$prospectcustomer = filter_var($post_data['prospectcustomer'], FILTER_SANITIZE_STRING);

		if($post_data['mocean_to'] == 'all_contacts_of_spec_tp') {
			if($filter_by == 'countries') {
				if(!ctype_digit($country_id)) {
					$this->errors[] = 'country cannot be empty';
				}
			}
			if($filter_by == 'tp_type') {
				if(empty($tp_type)) {
					$this->errors[] = 'Third party type cannot be empty';
				}
			}
			if($filter_by == 'prospectcustomer') {
				if(!ctype_digit($prospectcustomer)) {
					$this->errors[] = 'Prospect / customer cannot be empty';
				}
			}
		}

		return (count($this->errors) == 0) ? true : false;
	}

	public function handle_send_sms_form($post_data)
	{
		global $user, $db;

		if(!empty($_POST) && !empty($_POST['mocean_to'])) {
			if ( !$user->rights->moceanapi->permission->write ) {
				accessforbidden();
			}

			if($post_data['action'] == 'send_bulk_sms' && $this->validate_sms_form($post_data)) {
				$mocean_from = filter_var($post_data['mocean_from'], FILTER_SANITIZE_STRING);
				$mocean_to = filter_var($post_data['mocean_to'], FILTER_SANITIZE_STRING);
				$message = filter_var($post_data['message'], FILTER_SANITIZE_STRING);
				$socid = filter_var($post_data['socid'], FILTER_SANITIZE_STRING);
				$phone_numbers = filter_var($post_data['phone_numbers'], FILTER_SANITIZE_STRING);
				$auto_add_country_code = filter_var($post_data['auto_add_country_code'], FILTER_SANITIZE_STRING) == 'on' ? true : false;
				$filter_by = filter_var($post_data['filter_by'], FILTER_SANITIZE_STRING);
				$country_id = filter_var($post_data['country_id'], FILTER_SANITIZE_STRING);
				$tp_type = filter_var($post_data['tp_type'], FILTER_SANITIZE_STRING);
				$prospectcustomer = filter_var($post_data['prospectcustomer'], FILTER_SANITIZE_STRING);

				$total_sms_responses = array();

				$contact_model = new SMS_Contact_Setting($db);

				if($mocean_to == 'all_contacts') {
					$sql = "SELECT rowid FROM ". MAIN_DB_PREFIX . "socpeople";
					$resql = $this->db->query($sql);
					if($resql) {
						while ($obj = $this->db->fetch_object($resql)) {
							$contact = new Contact($this->db);
							$contact->fetch($obj->rowid);
							$val_mobile = validated_mobile_number($contact->phone_mobile, $contact->country_code);
							if(empty($val_mobile)) {
								$failed_message_data = array(
									'messages' => array(
										array(
											'status' => 1,
											'err_msg' => "Invalid mobile number {$contact->phone_mobile} for country code:{$contact->country_code}",
										)
									)
								);
								$total_sms_responses[] = $failed_message_data;
								continue;
							}
							$mocean_text = $contact_model->replace_keywords_with_value($contact, $message);

							$total_sms_responses[] = moceanapi_send_sms($mocean_from, $val_mobile, $mocean_text, "Bulk SMS");
						}
					} else {
						// no contacts
						return -1;
					}

				}
				else if ($mocean_to == 'all_contacts_of_tp') {
					// TODO
					$sql = "SELECT rowid FROM ". MAIN_DB_PREFIX . "socpeople WHERE fk_soc = {$socid};";
					$resql = $this->db->query($sql);
					if($resql) {
						if($resql->num_rows > 0) {
							while ($obj = $this->db->fetch_object($resql)) {
								$contact = new Contact($this->db);
								$contact->fetch($obj->rowid);
								$val_mobile = validated_mobile_number($contact->phone_mobile, $contact->country_code);
								if(empty($val_mobile)) {
									$failed_message_data = array(
										'messages' => array(
											array(
												'status' => 1
											)
										)
									);
									$total_sms_responses[] = $failed_message_data;
									continue;
								}
								$mocean_text = $contact_model->replace_keywords_with_value($contact, $message);

								$total_sms_responses[] = moceanapi_send_sms($mocean_from, $val_mobile, $mocean_text, "Bulk SMS");
							}
						}
						else {
							$this->add_notification_message("No contacts found in third party", 'error');
						}
					} else {
						// sql failed
						return -1;
					}
				}

				else if($mocean_to == 'all_contacts_of_spec_tp') {
					if($filter_by == 'countries') {
						// TODO
						$sql = "SELECT sp.rowid FROM ".MAIN_DB_PREFIX . "societe as s, " . MAIN_DB_PREFIX . "socpeople as sp WHERE s.fk_pays = {$country_id} AND sp.fk_soc = s.rowid;";
						$resql = $this->db->query($sql);
						if($resql) {
							if($resql->num_rows > 0) {
								while ($obj = $this->db->fetch_object($resql)) {
									$contact = new Contact($this->db);
									$contact->fetch($obj->rowid);
									$val_mobile = validated_mobile_number($contact->phone_mobile, $contact->country_code);
									if(empty($val_mobile)) {
										$failed_message_data = array(
											'messages' => array(
												array(
													'status' => 1
												)
											)
										);
										$total_sms_responses[] = $failed_message_data;
										continue;
									}
									$mocean_text = $contact_model->replace_keywords_with_value($contact, $message);

									$total_sms_responses[] = moceanapi_send_sms($mocean_from, $val_mobile, $mocean_text, "Bulk SMS");
								}

							} else {
								$this->add_notification_message("No filtered contacts found in third party", 'error');
							}
						} else {
							// sql failed
							return -1;
						}
					}
					else if ($filter_by == 'tp_type') {
						$sql = "SELECT sp.rowid FROM ".MAIN_DB_PREFIX . "societe as s, " . MAIN_DB_PREFIX . "socpeople as sp WHERE s.fk_typent = {$tp_type} AND sp.fk_soc = s.rowid;";
						$resql = $this->db->query($sql);
						if($resql) {
							if($resql->num_rows > 0) {
								while ($obj = $this->db->fetch_object($resql)) {
									$contact = new Contact($this->db);
									$contact->fetch($obj->rowid);
									$val_mobile = validated_mobile_number($contact->phone_mobile, $contact->country_code);
									if(empty($val_mobile)) {
										$failed_message_data = array(
											'messages' => array(
												array(
													'status' => 1
												)
											)
										);
										$total_sms_responses[] = $failed_message_data;
										continue;
									}
									$mocean_text = $contact_model->replace_keywords_with_value($contact, $message);

									$total_sms_responses[] = moceanapi_send_sms($mocean_from, $val_mobile, $mocean_text, "Bulk SMS");
								}
							} else {
								$this->add_notification_message("No filtered contacts found in third party", 'error');
							}
						} else {
							// no contacts
							return -1;
						}
					}
					else if ($filter_by == 'prospectcustomer') {
						$sql = "SELECT sp.rowid FROM ".MAIN_DB_PREFIX . "societe as s, " . MAIN_DB_PREFIX . "socpeople as sp WHERE s.client = {$prospectcustomer} AND sp.fk_soc = s.rowid;";
						$resql = $this->db->query($sql);
						if($resql) {
							if($resql->num_rows > 0) {
								while ($obj = $this->db->fetch_object($resql)) {
									$contact = new Contact($this->db);
									$contact->fetch($obj->rowid);
									$val_mobile = validated_mobile_number($contact->phone_mobile, $contact->country_code);
									if(empty($val_mobile)) {
										$failed_message_data = array(
											'messages' => array(
												array(
													'status' => 1
												)
											)
										);
										$total_sms_responses[] = $failed_message_data;
										continue;
									}
									$mocean_text = $contact_model->replace_keywords_with_value($contact, $message);

									$total_sms_responses[] = moceanapi_send_sms($mocean_from, $val_mobile, $mocean_text, "Bulk SMS");
								}
							} else {
								$this->add_notification_message("No filtered contacts found in third party", 'error');
							}
						} else {
							// no contacts
							return -1;
						}
					}
				}

				else if ($mocean_to == 'spec_phone_numbers') {
					$comma_sep_numbers = explode(',', $phone_numbers);
					$client_ip_address = filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP);
					$country_code = get_country_code_from_ip($client_ip_address);
					foreach($comma_sep_numbers as $mob_num) {
						if(ctype_digit($mob_num)) {
							if($auto_add_country_code) {
								// add prefix country code
								$val_mobile = validated_mobile_number($mob_num, $country_code);
								if(empty($val_mobile)) {
									$failed_message_data = array(
										'messages' => array(
											array(
												'status' => 1
											)
										)
									);
									$total_sms_responses[] = $failed_message_data;
									continue;
								}
								$total_sms_responses[] = moceanapi_send_sms($mocean_from, $val_mobile, $message, "Bulk SMS");
							}
							else {
								$total_sms_responses[] = moceanapi_send_sms($mocean_from, $mob_num, $message, "Bulk SMS");
							}
						}
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

	public function add_notification_message($message, $style ='ok')
	{
		$this->notification_messages[] = array(
			'message' => $message,
			'style'   => $style,
		);
	}

	public function render()
	{
		global $conf, $user, $langs, $db;
		$client_ip_address = filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP);
		$auto_add_country_code = get_country_code_from_ip($client_ip_address);
		$contact_model = new SMS_Contact_Setting($db);

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
		<form method="POST" action="<?php echo $_SERVER["PHP_SELF"] ?>" style="max-width: 500px">
			<input type="hidden" name="token" value="<?php echo newToken(); ?>">
			<input type="hidden" name="action" value="send_bulk_sms">
			<table class="border mocean-table">
				<!-- SMS Notification -->
				<tr>
					<td width="200px"><?php echo $this->form->textwithpicto($langs->trans("MOCEAN_FROM"), "Your business name"); ?>*</td>
					<td>
						<input type="text" name="mocean_from" value="<?php echo $conf->global->MOCEAN_FROM; ?>" required></input>
					</td>
				</tr>
				<tr>
					<td width="200px"><?php echo $this->form->textwithpicto($langs->trans("SmsTo"), "The contact mobile you want to send SMS to"); ?>*</td>
					<td>
						<select name="mocean_to" id="mocean_to">
							<option value="all_contacts" selected>All contacts</option>
							<option value="all_contacts_of_tp">All contacts of a third party</option>
							<option value="all_contacts_of_spec_tp">All contacts of a filtered third party(s)</option>
							<option value="spec_phone_numbers">Specific mobile numbers</option>
						</select>
					</td>
				</tr>
				<tr>
					<td width="200px">Thirdparty list</td>
					<td>
						<?php echo $this->form->select_thirdparty_list('') ?>
					</td>
				</tr>
				<tr>
					<td width="200px">Filter by</td>
					<td>
						<select name="filter_by" id="filter_by">
							<option value="countries" selected>Countries</option>
							<option value="tp_type">Third party type</option>
							<option value="prospectcustomer">Prospect / Customer</option>
						</select>
					</td>
				</tr>
				<tr>
					<td width="200px">Countries</td>
					<td>
						<!-- The id of this element is selectcountry_code -->
						<?php echo $this->form->select_country('', 'country_id', '', 0, "minwidth300", '', 0) ?>
					</td>
				</tr>
				<tr>
					<td width="200px">Third party type</td>
					<td>
						<?php echo $this->form->selectarray('tp_type', $this->form_company->typent_array(0));  ?>
					</td>
				</tr>
				<tr>
					<td width="200px">Prospect / customer</td>
					<td>
						<?php echo $this->form_company->selectProspectCustomerType('', 'prospectcustomer', 'prospectcustomer')  ?>
					</td>
				</tr>
				<tr>
					<td width="200px">Mobile numbers</td>
					<td>
						<textarea name="phone_numbers" id="phone_numbers" cols="30" rows="5" placeholder="60123456789,12014567890"></textarea>
					</td>
				</tr>
				<tr>
					<td width="200px">Automatically add country code (<?php echo "+".$auto_add_country_code ?>)</td>
					<td>
						<input name="auto_add_country_code" id="auto_add_country_code" type="checkbox"></input>
					</td>
				</tr>
				<tr>
					<td width="200px"><?php echo $langs->trans("SmsText") ?>*</td>
					<td>
						<textarea id="message" name="message" cols="30" rows="5" placeholder="Your SMS message" required></textarea>
						<div>
							<p id="sms_keyword_paragraph">Customize your SMS with keywords
								<button type="button" class="moceanapi_open_keyword" data-attr-target="message">
									Keywords
								</button>
							</p>
						</div>
					</td>
				</tr>
			</table>
			<input style="float:right;" class="button" type="submit" name="submit" value="<?php echo $langs->trans("SendSMSButtonTitle") ?>">
		</form>

		<script>
			jQuery( function($) {
				const entity_keywords = <?php echo json_encode($contact_model->get_keywords()); ?>;

				var div = $('<div />').appendTo('body');
				div.attr('id', `keyword-modal`);
				div.attr('class', "modal");
				div.attr('style', "display: none;");

				$('#socid').closest("tr").hide();
				$('#phone_numbers').closest("tr").hide();
				$('#auto_add_country_code').closest("tr").hide();
				$('#filter_by').closest("tr").hide();
				$('#selectcountry_id').closest("tr").hide();
				$('#tp_type').closest("tr").hide();
				$('#prospectcustomer').closest("tr").hide();

				$("#mocean_to").on("change", function () {
					if($(this).val()=="all_contacts") {
						$('#sms_keyword_paragraph').show();
						$('#socid').closest("tr").hide();
						$('#phone_numbers').closest("tr").hide();
						$('#auto_add_country_code').closest("tr").hide();
						$('#filter_by').closest("tr").hide();
						$('#selectcountry_id').closest("tr").hide();
						$('#tp_type').closest("tr").hide();
						$('#prospectcustomer').closest("tr").hide();
					}
					else if ($(this).val()=="all_contacts_of_tp") {
						$('#sms_keyword_paragraph').show();
						$('#socid').closest("tr").show();
						$('#phone_numbers').closest("tr").hide();
						$('#auto_add_country_code').closest("tr").hide();
						$('#filter_by').closest("tr").hide();
						$('#selectcountry_id').closest("tr").hide();
						$('#tp_type').closest("tr").hide();
						$('#prospectcustomer').closest("tr").hide();
					}
					else if ($(this).val()=="all_contacts_of_spec_tp") {
						$('#sms_keyword_paragraph').show();
						$('#socid').closest("tr").hide();
						$('#phone_numbers').closest("tr").hide();
						$('#auto_add_country_code').closest("tr").hide();

						$('#filter_by').closest("tr").show();
						$('#selectcountry_id').closest("tr").show();

						$("#filter_by").on("change", function () {
							if($(this).val()=="countries") {
								$('#selectcountry_id').closest("tr").show();
								$('#tp_type').closest("tr").hide();
								$('#prospectcustomer').closest("tr").hide();
							}
							else if($(this).val()=="tp_type") {
								$('#selectcountry_id').closest("tr").hide();
								$('#tp_type').closest("tr").show();
								$('#prospectcustomer').closest("tr").hide();
							}
							else if($(this).val()=="prospectcustomer") {
								$('#selectcountry_id').closest("tr").hide();
								$('#tp_type').closest("tr").hide();
								$('#prospectcustomer').closest("tr").show();
							}
							else {
								$('#selectcountry_id').closest("tr").hide();
								$('#tp_type').closest("tr").hide();
								$('#prospectcustomer').closest("tr").hide();
							}
						})
					}
					else if ($(this).val()=="spec_phone_numbers") {
						$('#sms_keyword_paragraph').hide();
						$('#socid').closest("tr").hide();
						$('#phone_numbers').closest("tr").show();
						$('#auto_add_country_code').closest("tr").show();
						$('#filter_by').closest("tr").hide();
						$('#selectcountry_code').closest("tr").hide();
						$('#tp_type').closest("tr").hide();
						$('#prospectcustomer').closest("tr").hide();
					}
					else {
						$('#socid').closest("tr").hide();
						$('#phone_numbers').closest("tr").hide();
						$('#auto_add_country_code').closest("tr").hide();
						$('#filter_by').closest("tr").hide();
						$('#selectcountry_code').closest("tr").hide();
						$('#tp_type').closest("tr").hide();
						$('#prospectcustomer').closest("tr").hide();
					}
				});

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
