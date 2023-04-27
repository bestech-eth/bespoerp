<?php
	class Smsdecanet extends CommonObject{
		var $expe='';
		var $dest='';
		var $message='';
		var $deferred='';
		var $priority='';
		var $class='';
		var $error;
        var $timeDrift = 0;
		
		function Smsdecanet($DB) {
		
		}
		
		function SmsSenderList() {
			global $conf;
			$frm = new stdClass();
			$frm->number = $conf->global->DECANETSMS_FROM;
			return array($frm);
		}
		
		function SmsSend() {
			
			global $langs, $conf;
			$langs->load("smsdecanet@smsdecanet");
			$to = str_replace('+','00',$this->dest);
			if(!preg_match('/^[0-9]+$/', $to)) {
				$this->error = $langs->trans('errorRecipient');
				return 0;
			}
			dol_include_once('/smsdecanet/class/DecanetAPI.class.php');
			$url = (intval($conf->global->DECANETSMS_SSL)==1)?'https':'http';
			$url.='://api.decanet.fr';	
			$api = new DecanetApi($conf->global->DECANETSMS_EMAIL,$conf->global->DECANETSMS_PASS, $url);
			$opts = array(
				'FROMNUM'=>$this->expe,
				'MSG'=>$api->decode($this->message),
				'TO'=>$to,
				'lang'=>$langs->defaultlang,
				'deferred'=>$this->deferred
			);
			if(isset($conf->global->DECANETSMS_TRANSACTIONAL) && intval($conf->global->DECANETSMS_TRANSACTIONAL)==1) {
				$opts['TRANSACTIONAL']=1;
			}
			$result = $api->post('/sms/send', $opts);
			
			if($result->code==1) {
				$this->error = $result->details;
				dol_syslog(get_class($this)."::SmsSend ".print_r($result->details, true), LOG_ERR);
				return 0;
			} else {
				return 1;
			}
		}
		
		
	}
?>