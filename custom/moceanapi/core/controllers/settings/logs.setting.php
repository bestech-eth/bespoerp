<?php

dol_include_once("/moceanapi/core/helpers/helpers.php");
dol_include_once("/moceanapi/core/class/moceanapi_logger.class.php");
dol_include_once("/moceanapi/core/controllers/settings/mocean.controller.setting.php");


class SMS_Log_Setting extends MoceanBaseSettingController
{

	var $log;
	var $page_name;
	var $db;

	public $db_key = "MOCEANAPI_CONTACT_SETTING";

	function __construct($db)
	{
		$this->db = $db;
		$this->form = new Form($db);
		$this->log = new MoceanAPI_Logger();
		$this->errors = array();
		$this->page_name = 'logs_page_title';
	}

	public function post_request_handler()
	{
		global $db, $user;

		if(!empty($_POST)) {
			$action = GETPOST('action');
			if($action == 'clear_log_file') {
				if ( !$user->rights->moceanapi->permission->delete ) {
					accessforbidden();
				}
				$cleared = $this->clear_log_file();
				if($cleared) {
					$this->add_notification_message("MoceanAPI log file cleared");
				}
				else {
					$this->add_notification_message("Failed to clear MoceanAPI log file", 'error');
				}
			}
		}
	}

	public function clear_log_file()
	{
		$handler = "MoceanAPI";
		return $this->log->delete_log_file($handler);
	}

	public function render()
	{
		global $conf, $user, $langs;
		$customer_logs = $this->log->get_log_file("MoceanAPI");

?>
		<!-- Begin form SMS -->

		<?php
		llxHeader('', $langs->trans($this->page_name));
		print load_fiche_titre($langs->trans($this->page_name), '', 'title_setup');
		$head = moceanapiAdminPrepareHead();
		print dol_get_fiche_head($head, 'logs', $langs->trans($this->page_name), -1);

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
			<div class="bootstrap-wrapper">
				<div id="setting-error-settings_updated" class="border border-primary" style="padding:4px;width:1200px;height:600px;overflow:auto">
					<pre><strong><?php echo htmlspecialchars($customer_logs, ENT_QUOTES); ?></strong></pre>
				</div>
			</div>

			<div style="margin-bottom: 20px"></div>

			<form method="POST" action="<?php echo $_SERVER["PHP_SELF"] ?>" style="max-width: 500px">
			<input type="hidden" name="token" value="<?php echo newToken(); ?>">
			<input type="hidden" name="action" value="clear_log_file">
			<button>Clear log file</button>
		<?php

		// Page end
		print dol_get_fiche_end();

		llxFooter();
		?>
<?php
	}
}
