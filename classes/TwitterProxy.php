<?php

/**
* This class is a wrapper around the default Twitter class to support cURL proxy
*
* @package Socialink
*/
class TwitterProxy extends TwitterOAuth {
	private $proxy = false;
	
	/**
	 * Set proxy settings
	 *
	 * @param array $settings the proxy settings
	 *
	 * @return bool
	 */
	function setProxySettings($settings) {
		$result = false;
			
		if (!empty($settings) && is_array($settings)) {
			$this->proxy = $settings;
			$result = true;
		}
			
		return $result;
	}
	
	/**
	 * Do an API call
	 *
	 * @param string $url        the url to call
	 * @param string $method     the http method
	 * @param array  $postfields post vars
	 *
	 * @return string
	 */
	function http($url, $method, $postfields = NULL) {
		$this->http_info = array();
		
		$ci = curl_init();
		
		/* Curl settings */
		curl_setopt($ci, CURLOPT_USERAGENT, $this->useragent);
		curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, $this->connecttimeout);
		curl_setopt($ci, CURLOPT_TIMEOUT, $this->timeout);
		curl_setopt($ci, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ci, CURLOPT_HTTPHEADER, array('Expect:'));
		curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, $this->ssl_verifypeer);
		curl_setopt($ci, CURLOPT_HEADERFUNCTION, array($this, 'getHeader'));
		curl_setopt($ci, CURLOPT_HEADER, FALSE);
	
		// check if we need to set proxy settings
		if ($this->proxy) {
			if (isset($this->proxy["host"])) {
				curl_setopt($ci, CURLOPT_PROXY, $this->proxy["host"]);
			}
		
			if (isset($this->proxy["port"])) {
				curl_setopt($ci, CURLOPT_PROXYPORT, $this->proxy["port"]);
			}
		}
		
		switch ($method) {
			case 'POST':
				curl_setopt($ci, CURLOPT_POST, TRUE);
				if (!empty($postfields)) {
					curl_setopt($ci, CURLOPT_POSTFIELDS, $postfields);
				}
				break;
			case 'DELETE':
				curl_setopt($ci, CURLOPT_CUSTOMREQUEST, 'DELETE');
				if (!empty($postfields)) {
					$url = "{$url}?{$postfields}";
				}
		}
	
		curl_setopt($ci, CURLOPT_URL, $url);
		
		$response = curl_exec($ci);
		
		$this->http_code = curl_getinfo($ci, CURLINFO_HTTP_CODE);
		$this->http_info = array_merge($this->http_info, curl_getinfo($ci));
		$this->url = $url;
		
		curl_close($ci);
		
		return $response;
	}
}