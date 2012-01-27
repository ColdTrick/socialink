<?php

	/**
	 * This class is a Wrapper around the default Facebook class, to support cURL proxy
	 * 
	 */

	if(!class_exists("Facebook")){
		require_once(dirname(__FILE__) . "/src/facebook.php");
	}

	class FacebookProxy extends Facebook {
		
		private $proxy = false;
		
		function setProxySettings($settings){
			$result = false;
			
			if(!empty($settings) && is_array($settings)){
				$this->proxy = $settings;
				$result = true;
			}
			
			return $result;
		}
		
		/**
		* Makes an HTTP request. This method can be overridden by subclasses if
		* developers want to do fancier things or use something other than curl to
		* make the request.
		*
		* @param string $url The URL to make the request to
		* @param array $params The parameters to use for the POST body
		* @param CurlHandler $ch Initialized curl handle
		*
		* @return string The response text
		*/
		protected function makeRequest($url, $params, $ch = null) {
			if (!$ch) {
				$ch = curl_init();
			}
			
			$opts = self::$CURL_OPTS;
			if ($this->useFileUploadSupport()) {
				$opts[CURLOPT_POSTFIELDS] = $params;
			} else {
				$opts[CURLOPT_POSTFIELDS] = http_build_query($params, null, "&");
			}
			$opts[CURLOPT_URL] = $url;

			// disable the "Expect: 100-continue" behaviour. This causes CURL to wait
			// for 2 seconds if the server does not support this header.
			if (isset($opts[CURLOPT_HTTPHEADER])) {
				$existing_headers = $opts[CURLOPT_HTTPHEADER];
				$existing_headers[] = "Expect:";
				
				$opts[CURLOPT_HTTPHEADER] = $existing_headers;
			} else {
				$opts[CURLOPT_HTTPHEADER] = array("Expect:");
			}
			
			// check for proxy settings
			if($this->proxy){
				if(isset($this->proxy["host"])){
					$opts[CURLOPT_PROXY] = $this->proxy["host"];
				}
			
				if(isset($this->proxy["port"])){
					$opts[CURLOPT_PROXYPORT] = $this->proxy["port"];
				}
			}

			curl_setopt_array($ch, $opts);
			$result = curl_exec($ch);

			if (curl_errno($ch) == 60) {
				// CURLE_SSL_CACERT
				self::errorLog("Invalid or no certificate authority found, using bundled information");
				curl_setopt($ch, CURLOPT_CAINFO, dirname(__FILE__) . "/src/fb_ca_chain_bundle.crt");
				
				$result = curl_exec($ch);
			}

			if ($result === false) {
				$e = new FacebookApiException(array(
			        "error_code" => curl_errno($ch),
			        "error" => array(
				        "message" => curl_error($ch),
				        "type" => "CurlException",
					),
				));
				curl_close($ch);
				throw $e;
			}
			
			curl_close($ch);
			
			return $result;
		}
	}