<?php

class MoceanBaseController {

	public $id;
	public $notification_messages;

	function __construct($id) {
		$this->id = $id;
		$this->notification_messages = array();
	}

	public function add_notification_message($message, $style='ok')
	{
		$this->notification_messages[] = array(
			'message' => $message,
			'style'   => $style,
		);
	}

}
