<?php
/* Copyright (C) 2022 SuperAdmin
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    moceanapi/lib/moceanapi.lib.php
 * \ingroup moceanapi
 * \brief   Library files with common functions for MoceanAPI
 */

/**
 * Prepare admin pages header
 *
 * @return array
 */
function moceanapiAdminPrepareHead()
{
	global $langs, $conf;

	$langs->load("moceanapi@moceanapi");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/moceanapi/admin/setting.php", 1);
	$head[$h][1] = $langs->trans("setting_page_menu_title");
	$head[$h][2] = 'settings';
	$h++;

	/*
	$head[$h][0] = dol_buildpath("/moceanapi/admin/myobject_extrafields.php", 1);
	$head[$h][1] = $langs->trans("ExtraFields");
	$head[$h][2] = 'myobject_extrafields';
	$h++;
	*/

	$head[$h][0] = dol_buildpath("/moceanapi/admin/send_sms.php", 1);
	$head[$h][1] = $langs->trans("SendSMSSettingPageTitle");
	$head[$h][2] = 'send_sms';
	$h++;

	$head[$h][0] = dol_buildpath("/moceanapi/admin/bulk_sms.php", 1);
	$head[$h][1] = $langs->trans("BulkSMSSettingPageTitle");
	$head[$h][2] = 'bulk_sms';
	$h++;

	$head[$h][0] = dol_buildpath("/moceanapi/admin/third_party.php", 1);
	$head[$h][1] = $langs->trans("ThirdPartySettingPageTitle");
	$head[$h][2] = 'third_party';
	$h++;

	$head[$h][0] = dol_buildpath("/moceanapi/admin/contact.php", 1);
	$head[$h][1] = $langs->trans("ContactSettingPageTitle");
	$head[$h][2] = 'contact';
	$h++;

	$head[$h][0] = dol_buildpath("/moceanapi/admin/invoice.php", 1);
	$head[$h][1] = $langs->trans("InvoiceSettingPageTitle");
	$head[$h][2] = 'invoice';
	$h++;

	$head[$h][0] = dol_buildpath("/moceanapi/admin/project.php", 1);
	$head[$h][1] = $langs->trans("ProjectSettingPageTitle");
	$head[$h][2] = 'project';
	$h++;

	$head[$h][0] = dol_buildpath("/moceanapi/admin/supplier_order.php", 1);
	$head[$h][1] = $langs->trans("SupplierOrderSettingPageTitle");
	$head[$h][2] = 'supplier_order';
	$h++;

	$head[$h][0] = dol_buildpath("/moceanapi/admin/ticket.php", 1);
	$head[$h][1] = $langs->trans("TicketSettingPageTitle");
	$head[$h][2] = 'ticket';
	$h++;

	$head[$h][0] = dol_buildpath("/moceanapi/admin/shipment.php", 1);
	$head[$h][1] = $langs->trans("ShipmentSettingPageTitle");
	$head[$h][2] = 'shipment';
	$h++;

	$head[$h][0] = dol_buildpath("/moceanapi/admin/member.php", 1);
	$head[$h][1] = $langs->trans("MemberSettingPageTitle");
	$head[$h][2] = 'member';
	$h++;

	$head[$h][0] = dol_buildpath("/moceanapi/admin/sms_outbox.php", 1);
	$head[$h][1] = $langs->trans("SMSOutboxSettingPageTitle");
	$head[$h][2] = 'sms_outbox';
	$h++;

	$head[$h][0] = dol_buildpath("/moceanapi/admin/voice_call_logs.php", 1);
	$head[$h][1] = $langs->trans("VoiceCallSettingPageTitle");
	$head[$h][2] = 'voice_call_logs';
	$h++;

	$head[$h][0] = dol_buildpath("/moceanapi/admin/logs.php", 1);
	$head[$h][1] = $langs->trans("LogsSettingPageTitle");
	$head[$h][2] = 'logs';
	$h++;

	$head[$h][0] = dol_buildpath("/moceanapi/admin/help.php", 1);
	$head[$h][1] = $langs->trans("HelpSettingPageTitle");
	$head[$h][2] = 'help';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	//$this->tabs = array(
	//	'entity:+tabname:Title:@moceanapi:/moceanapi/mypage.php?id=__ID__'
	//); // to add new tab
	//$this->tabs = array(
	//	'entity:-tabname:Title:@moceanapi:/moceanapi/mypage.php?id=__ID__'
	//); // to remove a tab
	complete_head_from_modules($conf, $langs, null, $head, $h, 'moceanapi@moceanapi');


	return $head;
}
