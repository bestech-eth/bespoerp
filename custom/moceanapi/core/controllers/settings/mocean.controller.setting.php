<?php

class MoceanBaseSettingController {
	public $notification_messages;

	public function add_notification_message($message, $style='ok')
	{
		$this->notification_messages[] = array(
			'message' => $message,
			'style'   => $style,
		);
	}
}
