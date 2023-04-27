<?php
/* Copyright (C) 2005-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2010      Juanjo Menent        <jmenent@2byte.es>
*
* This program is free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; either version 3 of the License, or
* (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
/**
 *       \file       htdocs/core/class/html.formmail.class.php
 *       \ingroup    core
 *       \brief      Fichier de la classe permettant la generation du formulaire html d'envoi de mail unitaire
 */
require_once DOL_DOCUMENT_ROOT .'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
dol_include_once("/moceanapi/lib/MoceanSMS.class.php");
dol_include_once("/moceanapi/core/helpers/helpers.php");
dol_include_once("/moceanapi/core/controllers/mocean.controller.php");


/**
 *      Classe permettant la generation du formulaire d'envoi de Sms
 *      Usage: $formsms = new FormSms($db)
 *             $formsms->proprietes=1 ou chaine ou tableau de valeurs
 *             $formsms->show_form() affiche le formulaire
 */
class MoceanFormSms
{
    var $db;
    var $param=array();
	var $logger;
	var $sms_from;
	var $sms_contact_id;
	var $sms_message;
    var $errors;


    /**
     *	Constructor
     *
     *  @param		DoliDB		$db      Database handler
     */
    function __construct($db)
    {
        $this->db = $db;
		$this->errors = array();
		$this->logger = new MoceanAPI_Logger();
    }

	function add_errors($error_msg) {
		$this->errors[] = $error_msg;
	}

	function get_errors() {
		return $this->errors;
	}

	public function handle_post_request()
	{
		$action=GETPOST('action');
		if ($action == 'send_sms')
		{
			$error = false;
			$sms_contact_id = GETPOST("sms_contact_ids");
			$sms_thirdparty_id = GETPOST("thirdparty_id");
			$sms_from       = GETPOST("sms_from");
			$sms_message    = GETPOST('sms_message');
			$object_id = GETPOST("object_id");

			if( empty($sms_from)) {
				$this->add_errors("From field is required");
				$error = true;
			}
			if( empty($sms_message) )
			{
				$this->add_errors("Message is required");
				$error = true;
			}

			if (! $error)
			{
				try {
					$result = process_send_sms_data();
					if(is_array($result)) {
						dol_htmloutput_mesg("SMS sent successfully: {$result['success']}, Failed: {$result['failed']}");
					}
					else { dol_htmloutput_mesg("Failed to send SMS", [], 'error'); }

				} catch (Exception $e) {
					dol_htmloutput_mesg("Something went wrong...", [], 'error');
					echo "Error: " . $e->getMessage();
				}
			}

		}
	}

    /**
     *	Show the form to input an sms.
     *
     *	@param	string	$width	Width of form
     *	@return	void
     */
    function show_form()
    {
        global $conf, $langs, $user, $form;

        if (! is_object($form)) $form=new Form($this->db);

        $langs->load("other");
        $langs->load("mails");
        $langs->load("sms");

		?>
		<!-- Begin form SMS -->
		<form method="POST" name="send_sms_form" enctype="multipart/form-data" action="<?php echo $this->param["returnUrl"] ?>" style="max-width: 500px;">
			<?php if(! empty($this->get_errors()) ) { ?>
				<?php foreach($this->get_errors() as $error) { ?>
					<div class="error"><?php echo $error ?></div>
				<?php } ?>
			<?php } ?>
			<input type="hidden" name="token" value="<?php echo newToken(); ?>">
			<?php foreach ($this->param as $key=>$value) { ?>
				<input type="hidden" name="<?php echo $key ?>" value="<?php echo $value ?>">
			<?php } ?>
			<!-- Balance -->

			<table class="border" width="100%">
				<tr>
					<td width="200px">Balance</td>
					<td>
						<input type="text" name="sms_from" size="30" value="<?php echo get_mocean_balance() ?>" disabled>
					</td>
				</tr>

				<!-- From -->

				<tr>
					<td width="200px"><?php echo $form->textwithpicto($langs->trans("MOCEAN_FROM"), "Your business name"); ?>*</td>
					<td>
						<input type="text" name="sms_from" size="30" value="<?php echo $conf->global->MOCEAN_FROM; ?>">
					</td>
				</tr>

				<!-- To -->
				<?php
					// sms_contact_id must come from here
					$entity = $_GET['entity'];

					if(intval($_GET['invoice_id']) > 0 && !empty($entity) && $entity == 'invoice') {
						$id = intval($_GET['invoice_id']);
						dol_include_once("/moceanapi/core/controllers/invoice.class.php");

						$controller = new InvoiceController($id);
						$controller->render();
					}
					else if (intval($_GET['thirdparty_id']) > 0 && !empty($entity) && $entity == 'thirdparty') {
						$id = intval($_GET['thirdparty_id']);
						dol_include_once("/moceanapi/core/controllers/thirdparty.class.php");

						$controller = new ThirdPartyController($id);
						$controller->render();
					}

					else if (intval($_GET['supplier_invoice_id']) > 0 && !empty($entity) && $entity == 'supplier_invoice') {
						$id = intval($_GET['supplier_invoice_id']);
						dol_include_once("/moceanapi/core/controllers/supplier_invoice.class.php");

						$controller = new SupplierInvoiceController($id);
						$controller->render();
					}

					else if (intval($_GET['supplier_order_id']) > 0 && !empty($entity) && $entity == 'supplier_order') {
						$id = intval($_GET['supplier_order_id']);
						dol_include_once("/moceanapi/core/controllers/supplier_order.class.php");

						$controller = new SupplierOrderController($id);
						$controller->render();
					}

					else if (intval($_GET['contact_id']) > 0 && !empty($entity) && $entity == 'contact') {
						$id = intval($_GET['contact_id']);
						dol_include_once("/moceanapi/core/controllers/contact.class.php");

						$controller = new ContactController($id);
						$controller->render();
					}

					else if (intval($_GET['project_id']) > 0 && !empty($entity) && $entity == 'project') {
						$id = intval($_GET['project_id']);
						dol_include_once("/moceanapi/core/controllers/project.class.php");

						$controller = new ProjectController($id);
						$controller->render();
					}

				?>

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
					var thirdpartyTree = $("#thirdparty_id");
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
				// $("#thirdparty_id").on("change", function () {
				// 	let chosen_tp_id = $(this).val();
				// 	urlParams = new URLSearchParams(window.location.search);
				// 	let entity = urlParams.get("entity"); // thirdparty
				// 	urlParams.set(entity+"_id", chosen_tp_id);
				// 	let baseURL = window.location.href.split('?')[0];
				// 	let url = baseURL + "?" + urlParams.toString();
				// 	window.location = url;
				// });
			})
		</script>
		<?php
    }

    function getContactPhoneListByCategory($type = "") {
        $db = $this->db;
		if($type == 'a') {
			$sql = "SELECT phone_mobile as phone";
			$sql.= " FROM ".MAIN_DB_PREFIX."adherent";
		} else {
			$sql = "SELECT p.phone ";
			$sql.= " FROM ".MAIN_DB_PREFIX."socpeople as p";
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = p.fk_soc";
			$sql.= ' WHERE  ';
			$sql.= " p.statut = 1 ";

			if ($type == "o")        // filtre sur type
			{
				$sql .= " AND p.fk_soc IS NULL";
			}
			else if ($type == "f")        // filtre sur type
			{
				$sql .= " AND s.fournisseur = 1";
			}
			else if ($type == "c")        // filtre sur type
			{
				$sql .= " AND s.client IN (1, 3)";
			}
			else if ($type == "p")        // filtre sur type
			{
				$sql .= " AND s.client IN (2, 3)";
			}
		}
        $result = $db->query($sql);
        // Count total nb of records
        $num = (int)$db->num_rows($result);


        $contact_list = array();
        if ($result){
            $i = 0;

            while ($i < $num) {
                $contact = $db->fetch_array($result);
                $contact_list[] = $contact["phone"];
                $i++;
            }

            $db->free($result);
        }
        //$db->close();

        //var_dump($contact_list);die;
        return $contact_list;
    }

}

