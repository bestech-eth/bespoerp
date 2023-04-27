<?php

dol_include_once("/moceanapi/lib/MoceanSMS.class.php");
dol_include_once("/moceanapi/core/class/moceanapi_logger.class.php");
dol_include_once("/moceanapi/core/class/moceanapi.db.php");
dol_include_once("/moceanapi/core/controllers/invoice.class.php");
dol_include_once("/moceanapi/core/controllers/thirdparty.class.php");
dol_include_once("/moceanapi/core/controllers/supplier_invoice.class.php");
dol_include_once("/moceanapi/core/controllers/supplier_order.class.php");
dol_include_once("/moceanapi/core/controllers/contact.class.php");
dol_include_once("/moceanapi/core/controllers/project.class.php");

function process_send_sms_data() {
	$log = new MoceanAPI_Logger();
	$object_id = GETPOST('object_id');
	$send_context   = GETPOST('send_context');
	$sms_from       = GETPOST("sms_from");
	$sms_contact_ids = GETPOST("sms_contact_ids");
	$sms_thirdparty_id = GETPOST("thirdparty_id");
	$send_sms_to_thirdparty_flag = GETPOST("send_sms_to_thirdparty_flag") == "on" ? true : false;
	$sms_message    = GETPOST('sms_message');

	$total_sms_responses = array();

	if(!empty($sms_thirdparty_id)) {
		if(empty($sms_contact_ids)) {
			$tp_obj = new ThirdPartyController($sms_thirdparty_id);
			$tp_phone_no = $tp_obj->get_thirdparty_mobile_number();
			$total_sms_responses[] = moceanapi_send_sms($sms_from, $tp_phone_no, $sms_message, "Tab");
		}
		else if(!empty($sms_contact_ids) && $send_sms_to_thirdparty_flag) {
			$tp_obj = new ThirdPartyController($sms_thirdparty_id);
			$tp_phone_no = $tp_obj->get_thirdparty_mobile_number();
			$total_sms_responses[] = moceanapi_send_sms($sms_from, $tp_phone_no, $sms_message, "Tab");
		}
	}

	if(isset($sms_contact_ids) && !empty($sms_contact_ids)) {
		foreach($sms_contact_ids as $sms_contact_id) {
			switch($send_context) {
				case 'invoice':
					$invoice = new InvoiceController($object_id);
					$sms_to = $invoice->get_contact_mobile_number($sms_contact_id);
					$total_sms_responses[] = moceanapi_send_sms($sms_from, $sms_to, $sms_message, "Tab");
					break;
				case 'thirdparty':
					$tp = new ThirdPartyController($object_id);
					$sms_to = $tp->get_contact_mobile_number($sms_contact_id);
					$total_sms_responses[] = moceanapi_send_sms($sms_from, $sms_to, $sms_message, "Tab");
					break;
				case 'supplier_invoice':
					$si = new SupplierInvoiceController($object_id);
					$sms_to = $si->get_contact_mobile_number($sms_contact_id);
					$total_sms_responses[] = moceanapi_send_sms($sms_from, $sms_to, $sms_message, "Tab");
					break;
				case 'supplier_order':
					$so = new SupplierOrderController($object_id);
					$sms_to = $so->get_contact_mobile_number($sms_contact_id);
					$total_sms_responses[] = moceanapi_send_sms($sms_from, $sms_to, $sms_message, "Tab");
					break;
				case 'contact':
					$contact = new ContactController($object_id);
					$sms_to = $contact->get_contact_mobile_number($sms_contact_id);
					$total_sms_responses[] = moceanapi_send_sms($sms_from, $sms_to, $sms_message, "Tab");
					break;
				case 'project':
					$project = new ProjectController($object_id);
					$sms_to = $project->get_contact_mobile_number($sms_contact_id);
					$total_sms_responses[] = moceanapi_send_sms($sms_from, $sms_to, $sms_message, "Tab");
					break;
				default:
					return [
						'success' => 0,
						'failed' => 0,
					];
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
	return $response;

}

function moceanapi_send_sms($from, $to, $message, $source, $medium = 'dolibarr') {
	global $conf, $db;
	$db_obj = new MoceanDatabase($db);
	$log = new MoceanAPI_Logger();

	try {
		$mocean_api_key = $conf->global->MOCEAN_API_KEY;
		$mocean_api_secret = $conf->global->MOCEAN_API_SECRET;

		$from = !empty($from) ? $from : $conf->global->MOCEAN_FROM;

		if(empty($to)) {

			$log->add("MoceanAPI", "Mobile number is empty, exiting...");
			throw new Exception("Mobile number cannot be empty");
		}

		$mocean = new MoceanSMS($mocean_api_key, $mocean_api_secret);

		$resp = $mocean->sendSMS($from, $to, $message, $medium);
		$resp = json_decode($resp, 1);
		$log->add("MoceanAPI", "SMS Resp");
		$log->add("MoceanAPI", print_r($resp, 1));
		$msg_status = $resp->messages[0]->status;
		$db_obj->insert($from, $to, $message, $msg_status, $source);
		return $resp;

	} catch(Exception $e) {
		$db_obj->insert($from, $to, $message, 1, $source);
		$log->add("MoceanAPI", print_r($e->getMessage(), 1));
		$failed_data = array(
			'messages' => array(
				array(
					'status' => 1,
				),
			),
		);
		return $failed_data;
	}
}

function get_mocean_balance()
{
	global $conf;
	$log = new MoceanAPI_Logger();
	try {
		$mocean_api_key = $conf->global->MOCEAN_API_KEY;
		$mocean_api_secret = $conf->global->MOCEAN_API_SECRET;

		$mocean = new MoceanSMS($mocean_api_key, $mocean_api_secret);

		$rest_response = $mocean->accountBalance();

		$rest_response = json_decode($rest_response);

		if($rest_response->{'status'} == 0){
			$account_pricing = json_decode($mocean->accountPricing());
			$account_currency = $account_pricing->destinations[0]->currency ?? "Currency not available";
			$balance_value = $rest_response->{'value'};
			$balance_display = "{$balance_value} {$account_currency}";
			return $balance_display;
		} else {
			return 'Invalid API Credentials';
		}
	} catch (Exception $e) {
		$log->add("MoceanAPI", print_r($e->getMessage(), 1));
		return 'Failed to retrieve account balance';
	}
}

function get_country_code_from_ip($ip_address)
{
	$log = new MoceanAPI_Logger();
	$api_url = "https://www.iplocate.io/api/lookup/{$ip_address}";
	try {
		$c = curl_init();
		curl_setopt( $c , CURLOPT_URL , $api_url);
		curl_setopt( $c , CURLOPT_USERAGENT, "Mozilla/5.0 (Linux Centos 7;) Chrome/74.0.3729.169 Safari/537.36");
		curl_setopt( $c , CURLOPT_RETURNTRANSFER, true);
		curl_setopt( $c , CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt( $c , CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt( $c , CURLOPT_TIMEOUT, 10000); // 10 sec
		$response = json_decode(curl_exec($c), 1);
		curl_close($c);


		if(!empty($response['error'])) {
			$log->add("MoceanAPI", "Unable to get country code for IP address: {$ip_address}");
			$log->add("MoceanAPI", "Error from API request: {$response['error']}");
			return ''; // ''
		}

		$country_code = $response['country_code'];

		$log->add("MoceanAPI", "Resolved {$ip_address} to country code: {$country_code}");
		return $country_code;

	} catch (Exception $e) {
		$log->add("MoceanAPI", "Error occured. Failed to get country code from ip address: {$ip_address}");
		$log->add("MoceanAPI", print_r($e->getMessage(), 1));
		return '';
	}
}

function validated_mobile_number($phone, $country_code) {
	global $conf, $db;
	$logger = new MoceanAPI_Logger();
	$db_obj = new MoceanDatabase($db);
	if(empty($country_code)) {
		$country_code = $conf->global->MOCEAN_COUNTRY_CODE;
		$logger->add("MoceanAPI", "Given country code is empty, using the default one.");
	}
	if(empty($phone)) {
		$logger->add("MoceanAPI", "Mobile number is empty. Exiting");
		return false;
	}

	$api_url = "https://dashboard.moceanapi.com/public/mobileChecking?mobile_number={$phone}&country_code={$country_code}";

	$logger->add("MoceanAPI", "Url used: {$api_url}");
	try {
		$c = curl_init();
		curl_setopt( $c , CURLOPT_URL , $api_url);
		curl_setopt( $c , CURLOPT_USERAGENT, "Mozilla/5.0 (Linux Centos 7;) Chrome/74.0.3729.169 Safari/537.36");
		curl_setopt( $c , CURLOPT_RETURNTRANSFER, true);
		curl_setopt( $c , CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt( $c , CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt( $c , CURLOPT_TIMEOUT, 10000); // 10 sec
		$response = curl_exec($c);
		curl_close($c);

		if(empty($response)) {
			$logger->add("MoceanAPI", "Invalid phone number: {$phone} for country: {$country_code}");
			$db_obj->insert("Mocean", $phone, "Invalid phone number: {$phone} for country: {$country_code}", 1, '');
			// return $phone; // 0123456789
			return ''; // ''
		}

		$logger->add("MoceanAPI", "{$phone} is converted to country ({$country_code}): {$response}");
		$phone = $response;
		return $phone; // 60123456789

	} catch (Exception $e) {
		$logger->add("MoceanAPI", "Error occured. Failed to validate mobile number");
		$logger->add("MoceanAPI", print_r($e->getMessage(), 1));
		return $phone;
	}
}

function generateUniqueKey() {
	/*
		Generates a 256 bits character long string. (hex size: 64)
	*/
	$now = time();
	$salt = bin2hex(random_bytes(32));
	$str_to_hash = $now.$salt;
	return hash("sha256", $str_to_hash);
}
