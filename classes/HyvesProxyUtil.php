<?php

/**
* This class is a wrapper around the default HyvesUtil (GenusApiUtil) class to support cURL proxy
*
* @package Socialink
*/
class HyvesProxyUtil extends GenusApisUtil {
	
	/**
	 * Make a api call
	 *
	 * @param string $url    the URL to call
	 * @param array  $vars   supplied params
	 * @param bool   $doPost POST or GET (default: GET)
	 * @param bool   $proxy  use proxy (default: false)
	 *
	 * @return string
	 */
	public static function doCurlHttpCall($url, $vars, $doPost, $proxy = false) {
		$ch = curl_init();
		if ($doPost) {
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $vars);
		} else {
			curl_setopt($ch, CURLOPT_URL, $url . "?" . $vars);
			curl_setopt($ch, CURLOPT_POST, 0);
		}
		
		if (!empty($proxy)) {
			if (isset($proxy["host"])) {
				curl_setopt($ch, CURLOPT_PROXY, $proxy["host"]);
			}
				
			if (isset($proxy["port"])) {
				curl_setopt($ch, CURLOPT_PROXYPORT, $proxy["port"]);
			}
		}
		
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		
		$output = curl_exec($ch);
		
		curl_close($ch);
		
		return $output;
	}
}