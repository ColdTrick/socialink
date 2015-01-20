<?php

/**
 * This class is a wrapper around the default LinkedIn class to support cURL proxy
 *
 * @package Socialink
 */
class LinkedInProxy extends LinkedIn {
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
	* General data send/request method.
	*
	* @param string $method     The data communication method.
	* @param string $url        The Linkedin API endpoint to connect with.
	* @param string $data       [OPTIONAL] The data to send to LinkedIn.
	* @param array  $parameters [OPTIONAL] Addition OAuth parameters to send to LinkedIn.
	*
	* @return array Array containing:
	*
	* array(
	*   'info'      =>	Connection information,
	*   'linkedin'  => LinkedIn response,
	*   'oauth'     => The OAuth request string that was sent to LinkedIn
	* )
	*/
	protected function fetch($method, $url, $data = NULL, $parameters = array()) {
		// check for cURL
		if (!extension_loaded('curl')) {
			// cURL not present
			throw new LinkedInException('LinkedIn->fetch(): PHP cURL extension does not appear to be loaded/present.');
		}
		
		try {
			// generate OAuth values
			$oauth_consumer  = new OAuthConsumer($this->getApplicationKey(), $this->getApplicationSecret(), $this->getCallbackUrl());
			$oauth_token     = $this->getTokenAccess();
			$oauth_token     = (!is_null($oauth_token)) ? new OAuthToken($oauth_token['oauth_token'], $oauth_token['oauth_token_secret']) : NULL;
			$defaults        = array(
				'oauth_version' => self::_API_OAUTH_VERSION
			);
			$parameters    = array_merge($defaults, $parameters);
			
			// generate OAuth request
			$oauth_req = OAuthRequest::from_consumer_and_token($oauth_consumer, $oauth_token, $method, $url, $parameters);
			$oauth_req->sign_request(new OAuthSignatureMethod_HMAC_SHA1(), $oauth_consumer, $oauth_token);
	
			// start cURL, checking for a successful initiation
			if (!$handle = curl_init()) {
				// cURL failed to start
				throw new LinkedInException('LinkedIn->fetch(): cURL did not initialize properly.');
			}
	
			// set cURL options, based on parameters passed
			curl_setopt($handle, CURLOPT_CUSTOMREQUEST, $method);
			curl_setopt($handle, CURLOPT_RETURNTRANSFER, TRUE);
			curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt($handle, CURLOPT_URL, $url);
			curl_setopt($handle, CURLOPT_VERBOSE, FALSE);
	
			// configure the header we are sending to LinkedIn - http://developer.linkedin.com/docs/DOC-1203
			$header = array($oauth_req->to_header(self::_API_OAUTH_REALM));
			if (is_null($data)) {
				// not sending data, identify the content type
				$header[] = 'Content-Type: text/plain; charset=UTF-8';
				switch ($this->getResponseFormat()) {
					case self::_RESPONSE_JSON:
						$header[] = 'x-li-format: json';
						break;
					case self::_RESPONSE_JSONP:
						$header[] = 'x-li-format: jsonp';
						break;
				}
			} else {
				$header[] = 'Content-Type: text/xml; charset=UTF-8';
				curl_setopt($handle, CURLOPT_POSTFIELDS, $data);
			}
			curl_setopt($handle, CURLOPT_HTTPHEADER, $header);
			
			// check if we need to set proxy settings
			if ($this->proxy) {
				if (isset($this->proxy["CURLOPT_PROXY"])) {
					curl_setopt($handle, CURLOPT_PROXY, $this->proxy["CURLOPT_PROXY"]);
				}
				
				if (isset($this->proxy["CURLOPT_PROXYPORT"])) {
					curl_setopt($handle, CURLOPT_PROXYPORT, $this->proxy["CURLOPT_PROXYPORT"]);
				}
			}
	
			// gather the response
			$return_data                    = array();
			$return_data['linkedin']        = curl_exec($handle);
			$return_data['info']            = curl_getinfo($handle);
			$return_data['oauth']['header'] = $oauth_req->to_header(self::_API_OAUTH_REALM);
			$return_data['oauth']['string'] = $oauth_req->base_string;
	
			// check for throttling
			if (self::isThrottled($return_data['linkedin'])) {
				throw new LinkedInException('LinkedIn->fetch(): throttling limit for this user/application has been reached.');
			}
	
			// close cURL connection
			curl_close($handle);
	
			// no exceptions thrown, return the data
			return $return_data;
		} catch(OAuthException $e) {
			// oauth exception raised
			throw new LinkedInException('OAuth exception caught: ' . $e->getMessage());
		}
	}
}