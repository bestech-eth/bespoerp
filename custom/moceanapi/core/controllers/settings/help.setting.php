<?php

dol_include_once("/moceanapi/core/helpers/helpers.php");
dol_include_once("/moceanapi/core/class/moceanapi_logger.class.php");

class SMS_Help_Setting
{

	var $form;
	var $errors;
	var $log;
	var $page_name;
	var $db;
	var $context;

	function __construct($db)
	{
		$this->db = $db;
		$this->form = new Form($db);
		$this->log = new MoceanAPI_Logger();
		$this->errors = array();
		$this->context = 'help';
		$this->page_name = 'help_page_title';
	}

	public function render()
	{
		global $conf, $user, $langs;
?>
		<!-- Begin form SMS -->
		<?php
		llxHeader('', $langs->trans($this->page_name));
		print load_fiche_titre($langs->trans($this->page_name), '', 'title_setup');
		$head = moceanapiAdminPrepareHead();
		print dol_get_fiche_head($head, $this->context, $langs->trans($this->page_name), -1);
		?>
		<?php if ($this->errors) { ?>
			<?php foreach ($this->errors as $error) { ?>
				<p style="color: red;"><?php echo $error ?></p>
			<?php } ?>
		<?php } ?>
		<form method="POST" action="<?php echo $_SERVER["PHP_SELF"] ?>">
			<h2>What is MoceanAPI Send SMS ?</h2>
			<p>MoceanAPI Send SMS is a cloud-based reliable interface for sending short text messages to 200+ networks around the world.</p>

			<h2>How to create an API key?</h2>
			<p>To use MoceanAPI Send SMS in Dolibarr, you need to create an account in MoceanAPI Dashboard. You can do this
				<a href="https://dashboard.moceanapi.com/register?fr=dolibarr">
					<strong>here</strong>
				</a>
			. The account creation is free and trial credit will be provided subject to approval.
			</p>

			<h2>Questions and Support</h2>
			<p>If you have any questions or feedbacks, you can send a message to our support team and we will get back to you as soon as possible at our
				<a href="https://moceanapi.com/#contact">
					<strong>page</strong>
				</a>.
			</p>
		</form>

		<?php
		// Page end
		print dol_get_fiche_end();

		llxFooter();
		?>
<?php
	}
}
