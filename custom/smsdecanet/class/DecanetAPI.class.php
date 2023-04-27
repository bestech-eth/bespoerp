<?php


class DecanetApi {

    var $LOGIN = false;
    var $PASS = false;
	var $ROOT = 'https://api.decanet.fr';

    var $timeDrift = 0;

    function __construct($_login=false, $_pass=false, $_root=false) {
        if($_login)$this->LOGIN = $_login;
        if($_pass)$this->PASS = $_pass;
		if($_root)$this->ROOT = $_root;

        // Compute time drift
        $srvTime = json_decode(file_get_contents($this->ROOT . '/auth/time'));
        if($srvTime !== FALSE)
        {
            $this->timeDrift = time() - (int)$srvTime;
        }
    }

    function call($method, $url, $body = NULL)
    {
        $url = $this->ROOT . $url;
        if($body)
        {
			$bodystring = '';
			foreach($body as $key=>$value) { $bodystring .= $key.'='.urlencode($value).'&'; }
			$bodystring = rtrim($bodystring, '&');
        }
        
        // Compute signature
        $time = time() - $this->timeDrift;
        $toSign = $this->LOGIN.'+'.$this->PASS.'+'.$method.'+'.$time;
        $signature = '$1$' . sha1($toSign);

        // Call
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'X-Consumer:' . $this->LOGIN,
            'X-Signature:' . $signature,
            'X-Timestamp:' . $time,
        ));
	
        if($body)
        {
			curl_setopt($curl,CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $bodystring);
        }
        $result = curl_exec($curl);
        if($result === FALSE)
        {
            echo curl_error($curl);
            return NULL;
        }
        
        return json_decode($result);
    }

    function get($url)
    {
        return $this->call("GET", $url);
    }
    function put($url, $body)
    {
        return $this->call("PUT", $url, $body);
    }
    function post($url, $body)
    {
        return $this->call("POST", $url, $body);
    }
    function delete($url, $body = false)
    {
        return $this->call("DELETE", $url, $body);
    }
	
	function encode($string) {
		if (preg_match('!!u', $string))
		{
		   return $string;
		}
		else 
		{
		   return utf8_encode($string);
		}
	}
	
	function decode($string) {
		if (preg_match('!!u', $string))
		{
		   return utf8_decode($string);
		}
		else 
		{
		   return $string;
		}
	}
}

?>
