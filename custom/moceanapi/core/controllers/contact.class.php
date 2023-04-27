<?php

dol_include_once("/moceanapi/core/interfaces/view_interface.class.php");
dol_include_once("/moceanapi/core/class/moceanapi_logger.class.php");
dol_include_once("/fourn/class/fournisseur.facture.class.php"); // Supplier Invoice
dol_include_once("/fourn/class/fournisseur.commande.class.php"); // Supplier Order
dol_include_once("/contact/class/contact.class.php"); // Contact
dol_include_once("/moceanapi/core/controllers/mocean.controller.php"); // mocean base controller

class ContactController extends MoceanBaseController implements ViewInterface {

	var $contact;

	function __construct($id) {
		global $db;
		$this->log = new MoceanAPI_Logger();
		$this->contact = new Contact($db);
		$this->contact->fetch($id);
		parent::__construct($id);
	}

	public function get_contact_mobile_number($contact_id)
	{
		$phone = $this->contact->phone_mobile;
		$country_code = $this->contact->country_code;
		return validated_mobile_number($phone, $country_code);
	}

	/**
     *	Show the Send SMS To section in HTML
     *
     *	@param	void
     *	@return	void
     */
	public function render() {
        global $conf, $langs, $user, $form;
	?>

		<input type="hidden" name="object_id" value="<?php echo $this->id ?>">
		<input type="hidden" name="send_context" value="contact">
		<tr>
			<td width="200px">
				<?php echo $form->textwithpicto($langs->trans("SmsTo"), "The contact mobile you want to send SMS to"); ?>
			</td>
			<td>
				<?php echo $form->selectcontacts(0, $this->contact->id, 'sms_contact_ids', 0, '', '', 1, '', false, 1, 0, [], '', '', true) ?>
			</td>
		</tr>
	<?php
	}
}
