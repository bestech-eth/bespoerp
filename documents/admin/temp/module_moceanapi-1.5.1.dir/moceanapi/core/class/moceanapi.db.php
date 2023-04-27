<?php
dol_include_once("./moceanapi_logger.class.php");

class MoceanDatabase {

	private $db;
	private $log;

	private $table_name = MAIN_DB_PREFIX . 'moceanapi_sms_outbox';

	public function __construct($db) {
		$this->db = $db;
		$this->log = new MoceanAPI_Logger();
	}

	public function insert($sender, $recipient, $message, $status, $source)
	{
		$sql = "INSERT INTO `{$this->table_name}` (`sender`, `recipient`, `message`, `status`, `source`, `date`) ";
		$sql .= sprintf('VALUES ("%s", "%s", "%s", %d, "%s", UTC_TIMESTAMP())',
			$this->db->escape($sender),
			$this->db->escape($recipient),
			$this->db->escape($message),
			$this->db->escape($status),
			$this->db->escape($source)
		);

		$result = $this->db->query($sql);
		if($result) {
			$this->log->add("MoceanAPI", "Successfully added to SMS Outbox");
			return true;
		} else {
			$this->log->add("MoceanAPI", "Failed to add into SMS Outbox");
			return false;
		}
	}

	public function delete_all()
	{
		$sql = "DELETE FROM `{$this->table_name}`;";
		$result = $this->db->query($sql);
		if($result) {
			$this->log->add("MoceanAPI", "Successfully cleared SMS Outbox");
			return true;
		} else {
			$this->log->add("MoceanAPI", "Failed to clear SMS Outbox");
			return false;
		}
	}

	public function get()
	{
		$sql = "SELECT `id`, `sender`, `recipient`, `message`, `status`, `source`, `date` FROM `{$this->table_name}` ORDER BY `id` DESC";
		$result = $this->db->query($sql);
		return $result ? $result : [];
	}

}
