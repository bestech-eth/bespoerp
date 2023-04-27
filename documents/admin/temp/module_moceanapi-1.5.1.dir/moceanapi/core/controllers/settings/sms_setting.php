<?php

dol_include_once("/moceanapi/core/helpers/helpers.php");
dol_include_once("/moceanapi/core/class/moceanapi_logger.class.php");
dol_include_once("/moceanapi/core/controllers/settings/mocean.controller.setting.php");
dol_include_once("/moceanapi/class/jobs/send-sms-reminder.class.php");

class SMS_Setting extends MoceanBaseSettingController
{

	private $form;
	private $errors;
	private $log;
	var $context;
	private $page_name;

	private static $setting_vars = array(
		'MOCEAN_FROM',
		'MOCEAN_API_KEY',
		'MOCEAN_API_SECRET',
		'MOCEAN_COUNTRY_CODE',
		'MOCEAN_CALLBACK_NUMBER',
		'MOCEAN_ACTIVE_HOUR_START',
		'MOCEAN_ACTIVE_HOUR_END',
	);

	function __construct($db) {
		$this->form = new Form($db);
		$this->errors = array();
		$this->log = new MoceanAPI_Logger();
		$this->page_name = 'setting_page_title';
	}

	public function update_settings()
	{
		global $db, $user;

		if(!empty($_POST)) {
			if ( !$user->rights->moceanapi->permission->write ) {
				accessforbidden();
			}
			// Handle Update
			$action = GETPOST("action");
			if($action == "update_{$this->context}") {
				foreach(self::$setting_vars as $key) {
					$value = GETPOST($key, 'alphanohtml');
					if($key == 'MOCEAN_CALLBACK_NUMBER' && !empty($value) ) {
						if( !ctype_digit($value) ) {
							$this->errors[] = 'Mocean Callback Number must be in digits';
							continue;
						}
					}
					$error = dolibarr_set_const($db, $key, $value);
					if($error < 1) {
						$this->log->add("MoceanAPI", "failed to update the value of {$key}");
						$this->errors[] = "There was an error saving {$key}.";
					}
				}
			}
			if(count($this->errors) > 0) {
				$this->add_notification_message("Failed to save settings", "error");
			}
			else {
				$this->add_notification_message("Settings saved");
			}
		}
	}

	public static function get_settings() {
		global $conf, $db;
		$settings = array();

		// set default value if key not found or empty
		if(empty($conf->global->MOCEAN_ACTIVE_HOUR_START)) {
			dolibarr_set_const($db, "MOCEAN_ACTIVE_HOUR_START", "12:00am");
		}
		if(empty($conf->global->MOCEAN_ACTIVE_HOUR_END)) {
			dolibarr_set_const($db, "MOCEAN_ACTIVE_HOUR_END", "11.59pm");
		}

		foreach(self::$setting_vars as $key) {
			$settings[$key] = $conf->global->$key;
		}

		return $settings;
	}

	public static function delete_settings()
	{
		/*
		* @return int
		* OK: > 0; KO < 0;
		*
		*/
		global $db;
		$log = new MoceanAPI_Logger();
		$errors = 0;
		foreach(self::$setting_vars as $key) {
			$error = dolibarr_del_const($db, $key);
			if($error < 1) {
				$log->add("MoceanAPI", "failed to delete the value of {$key}");
				$errors--;
			}
		}
		return $errors;
	}

	public static function download_log_file($handler)
	{
		$log = new MoceanAPI_Logger();
		$filepath = $log->get_log_file_path($handler);
		if(!file_exists($filepath)) {
			http_response_code(404);
	        die();
		}
		header('Content-Description: File Transfer');
		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename="'.basename($filepath).'"');
		header('Expires: 0');
		header('Cache-Control: must-revalidate');
		header('Pragma: public');
		header('Content-Length: ' . filesize($filepath));
		flush();
		readfile($filepath);
		die();
	}

	public function render() {
		global $conf, $user, $langs, $db;
        $settings = $this->get_settings();
		$mocean_api_key 	  		= $settings['MOCEAN_API_KEY'];
		$mocean_api_secret    		= $settings['MOCEAN_API_SECRET'];
		$mocean_from 		  		= $settings['MOCEAN_FROM'];
		$mocean_callback_number 	= $settings['MOCEAN_CALLBACK_NUMBER'];
		$mocean_country_code  		= $settings['MOCEAN_COUNTRY_CODE'];
		$mocean_active_hour_start  	= $settings['MOCEAN_ACTIVE_HOUR_START'];
		$mocean_active_hour_end 	= $settings['MOCEAN_ACTIVE_HOUR_END'];

		// @since v1.5.0
		// can remove this if we are later than version 1.5.0
		$reminder = new MoceanSMSReminderDatabase($db);
		$require_reactivation = !($reminder->healthcheck());

		?>
			<!-- Begin form SMS -->

			<?php
				llxHeader('', $langs->trans($this->page_name));
				print load_fiche_titre($langs->trans($this->page_name), '', 'title_setup');
				$head = moceanapiAdminPrepareHead();
				print dol_get_fiche_head($head, 'settings', $langs->trans($this->page_name), -1);

				if(!empty($this->notification_messages)) {
					foreach($this->notification_messages as $notification_message) {
						dol_htmloutput_mesg($notification_message['message'], [], $notification_message['style']);
					}
				}
			?>
			<?php if($this->errors) { ?>
				<?php foreach($this->errors as $error) { ?>
					<p style="color: red;"><?php echo $error ?></p>
				<?php } ?>
			<?php } ?>

			<?php if($require_reactivation) { ?>
				<h2 style="color: red">Please reactivate MoceanAPI module</h2>
			<?php } ?>

			<form method="POST" action="<?php echo $_SERVER["PHP_SELF"] ?>">
				<input type="hidden" name="token" value="<?php echo newToken(); ?>">
				<input type="hidden" name="action" value="<?php echo "update_{$this->context}" ?>">

				<table class="border mocean-table">
					<!-- Balance -->
					<tr>
						<td width="200px">Balance</td>
						<td>
							<input type="text" name="sms_balance" size="30" value="<?php echo get_mocean_balance() ?>" disabled>
						</td>
					</tr>
					<!-- API Key -->
					<tr>
						<td width="200px">API Key *</td>
						<td>
							<input type="text" name="MOCEAN_API_KEY" size="30" value="<?php echo $mocean_api_key ?>" required>
						</td>
					</tr>

					<!-- API Secret -->
					<tr>
						<td width="200px">API Secret *</td>
						<td>
							<input type="password" name="MOCEAN_API_SECRET" size="30" value="<?php echo $mocean_api_secret ?>" required>
						</td>
					</tr>

					<!-- Sender  -->

					<tr>
						<td width="200px"><?php echo $this->form->textwithpicto($langs->trans("MOCEAN_FROM"), "Your business name"); ?>*</td>
						<td>
							<input type="text" name="MOCEAN_FROM" size="30" value="<?php echo $mocean_from ?>" required>
						</td>
					</tr>

					<!-- Country code -->
					<tr>
						<td width="200px">Default country</td>

						<td>
							<?php echo $this->form->select_country($mocean_country_code, 'MOCEAN_COUNTRY_CODE', '', 0, 'minwidth250', 'code2', 0) ?>
						</td>
					</tr>

					<!-- Callback number -->
					<tr>
						<td width="200px"><?php echo $this->form->textwithpicto($langs->trans("MOCEAN_CALLBACK_NUMBER"), $langs->trans("MOCEAN_CALLBACK_NUMBER_TOOLTIP")); ?></td>

						<td>
							<input type="text" name="MOCEAN_CALLBACK_NUMBER" size="30" value="<?php echo $mocean_callback_number ?>">
							<p>
								If you bought a virtual number, please configure call forwarding <a href="https://dashboard.moceanapi.com/number/myNumber" rel="noopener noreferrer" target="_blank">here</a>
							</p>
						</td>
					</tr>
					<tr>
						<td width="200px"><?php echo $this->form->textwithpicto($langs->trans("MOCEAN_ACTIVE_HOUR"), $langs->trans("MOCEAN_ACTIVE_HOUR_TOOLTIP")); ?></td>
						<td>
							<input type="text" name="MOCEAN_ACTIVE_HOUR_START" value="<?php echo $mocean_active_hour_start ?>" style="width: 75px;">
							-
							<input type="text" name="MOCEAN_ACTIVE_HOUR_END" value="<?php echo $mocean_active_hour_end ?>" style="width: 75px;">
						</td>
					</tr>

					<tr>
						<td width="200px">Export log</td>

						<td>
							<button>
								<a href="<?php echo "{$_SERVER['PHP_SELF']}?action=download_log&handler=MoceanAPI"?>">Download log</a>
							</button>
						</td>
					</tr>
					<tr>
						<td width="200px"></td>

						<td>
							<button>
								<a href="https://bit.ly/3BIoY0n" target="_blank" rel="noreferrer noopener">Send us your feedback!</a>
							</button>
						</td>
					</tr>

				</table>
				<p>Create an account
					<a href="https://dashboard.moceanapi.com/register?fr=dolibarr">
						<strong>here</strong>
					</a>
					in less than 5 minutes
				</p>
				<!-- Submit -->
				<center>
					<input class="button" type="submit" name="submit" value="<?php echo $langs->trans("savesetting") ?>">
				</center>

			</form>
			<script>
				// https://github.com/jonthornton/jquery-timepicker#timepicker-plugin-for-jquery

				$(document).ready(function() {
					$('input[name="MOCEAN_ACTIVE_HOUR_START"]').timepicker();
					$('input[name="MOCEAN_ACTIVE_HOUR_END"]').timepicker();
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
