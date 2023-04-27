<?php

dol_include_once("/moceanapi/core/interfaces/view_interface.class.php");
dol_include_once("/moceanapi/core/controllers/mocean.controller.php");
dol_include_once("/contact/class/contact.class.php"); // Contact
dol_include_once("/fourn/class/fournisseur.facture.class.php"); // Supplier Invoice
dol_include_once("/moceanapi/core/controllers/mocean.controller.php");

class SupplierInvoiceController extends MoceanBaseController implements ViewInterface {

	var $invoice;
	var $thirdparty;

	function __construct($id) {
		global $db;
		$this->invoice = new FactureFournisseur($db);
		$this->invoice->fetch($id);

		$this->thirdparty = new Societe($db);
		$this->thirdparty->fetch($this->invoice->socid);
		parent::__construct($id);

	}

	public function get_contact_mobile_number($contact_id)
	{
		global $db;
		$contact = new Contact($db);
		$contact->fetch($contact_id);
		$phone = $this->thirdparty->contact_get_property($contact_id, "mobile");
		$country_code = $contact->country_code;
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
		<input type="hidden" name="send_context" value="supplier_invoice">
		<tr>
			<td width="200px">
				<?php echo $form->textwithpicto($langs->trans("SmsTo"), "The contact mobile you want to send SMS to"); ?>
			</td>
			<td>
				<input type="hidden" name="thirdparty_id" value="<?php echo $this->thirdparty->id ?>">
				<?php echo $form->select_thirdparty_list($this->thirdparty->id, 'thirdparty_id', '', 0, 0, 0, [], '', 0, 0, '', 'disabled') ?>
			</td>
		</tr>

		<tr>
			<td width="200px">
			</td>
			<td>
				<?php echo $form->selectcontacts($this->thirdparty->id, '', 'sms_contact_ids', 1, '', '', 1, '', false, 0, 0, [], '', '', true) ?>
			</td>
		</tr>
	<?php
	}
}
