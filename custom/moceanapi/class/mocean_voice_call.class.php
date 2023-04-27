<?php
require_once DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php";

class MoceanVoiceCall {
	private static $instance = null;
	private $data;
	private $nowTimestamp;

	public function __construct() {
		global $conf, $db;
		$this->data = !empty(dolibarr_get_const($db, "MOCEAN_LAST_VOICE_CALL_DATA")) ? json_decode(dolibarr_get_const($db, "MOCEAN_LAST_VOICE_CALL_DATA"), true) : array();
		$this->nowTimestamp = strtotime("now");

	}

	public function refreshData()
	{
		global $db;
		$this->data = !empty(dolibarr_get_const($db, "MOCEAN_LAST_VOICE_CALL_DATA")) ? json_decode(dolibarr_get_const($db, "MOCEAN_LAST_VOICE_CALL_DATA"), true) : array();
	}

	public function getFullData()
	{
		$this->refreshData();
		return $this->data;
	}
	public function getLastCallTimestamp($contact_id)
	{
		$this->refreshData();
		$id = "contact_{$contact_id}";
		if(array_key_exists($id, $this->data)) {
			return $this->data[$id];
		}
		return '';
	}

	public function setLastVoiceCallSent($contact_id, int $timestamp)
	{
		global $db;
		$id = "contact_{$contact_id}";
		$this->data[$id] = $timestamp;
		dolibarr_set_const($db, "MOCEAN_LAST_VOICE_CALL_DATA", json_encode($this->data));
	}

	public function canInitiateCall($contact_id, int $timestamp)
	{
		# if more than 2 minus, we allow them to initiate the call again.
		return ($this->getTsDifferenceInSeconds($contact_id) >= 120) ? true : false;
	}

	public function getTsDifferenceInSeconds($contact_id)
	{
		$lastCallTimestamp = $this->getLastCallTimestamp($contact_id);
		if(empty($lastCallTimestamp)) { return true; }
		return ($this->nowTimestamp - $lastCallTimestamp);
	}

}
