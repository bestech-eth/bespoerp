<?php
dol_include_once("./moceanapi_logger.class.php");

class MoceanVoiceCallDatabase {

	private $db;
	private $log;

	private $table_name = MAIN_DB_PREFIX . 'moceanapi_voice_call_logs';

	public function __construct($db) {
		$this->db = $db;
		$this->log = new MoceanAPI_Logger();
	}

	public function insert($sender, $recipient, $model_id, $dolibarr_class, $status)
	{
		$sql = "INSERT INTO `{$this->table_name}` (`sender`, `recipient`, `model_id`, `dolibarr_class`, `status`, `date`) ";
		$sql .= sprintf('VALUES ("%s", "%s", %d, "%s", %d, UTC_TIMESTAMP())',
			$this->db->escape($sender),
			$this->db->escape($recipient),
			$this->db->escape($model_id),
			$this->db->escape($dolibarr_class),
			$this->db->escape($status)
		);

		$result = $this->db->query($sql);
		if($result) {
			$this->log->add("MoceanAPI", "Successfully added to Voice Call Log");
			return true;
		} else {
			$this->log->add("MoceanAPI", "Failed to add into Voice Call Log");
			return false;
		}
	}

	public function delete_all()
	{
		$sql = "DELETE FROM `{$this->table_name}`;";
		$result = $this->db->query($sql);
		if($result) {
			$this->log->add("MoceanAPI", "Successfully cleared Voice Call Log");
			return true;
		} else {
			$this->log->add("MoceanAPI", "Failed to clear Voice Call Log");
			return false;
		}
	}

	public function get()
	{
		$sql = "SELECT `id`, `sender`, `recipient`, `model_id`, `dolibarr_class`, `status`, `date` FROM `{$this->table_name}` ORDER BY `id` DESC";
		$result = $this->db->query($sql);
		return $result ? $result : [];
	}

}
