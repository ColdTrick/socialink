<?php

	if(!class_exists("OAuthConsumer")){
		require_once(dirname(__FILE__) . "/OAuth.php");
	}
	
	class OpenBibId {
		
		const _URL_REQUEST 	= "http://bibnet.lodgon.com/openbibid/rest/requestToken";
		const _URL_ACCESS 	= "http://bibnet.lodgon.com/openbibid/rest/accessToken";
		const _URL_AUTH 	= "http://bibnet.lodgon.com/openbibid/rest/auth/authorize?oauth_token=";
		
		/* Contains the last HTTP status code returned. */
		public $http_code;
		/* Contains the last API call. */
		public $url;
		/* Set up the API root URL. */
		public $host = "http://bibnet.lodgon.com/openbibid/rest/";
		/* Set timeout default. */
		public $timeout = 30;
		/* Set connect timeout. */
		public $connecttimeout = 30; 
		/* Verify SSL Cert. */
		public $ssl_verifypeer = FALSE;
		/* Respons format. */
		public $format = "json";
		/* Decode returned json data. */
		public $decode_json = TRUE;
		/* Contains the last HTTP headers returned. */
		public $http_info;
		/* Set the useragnet. */
		public $useragent = "OpenBibId 0.1";
		
		function __construct($consumer_key, $consumer_secret, $oauth_token = NULL, $oauth_token_secret = NULL){
			$this->sha1_method = new OAuthSignatureMethod_HMAC_SHA1();
			$this->consumer = new OAuthConsumer($consumer_key, $consumer_secret);
			
			if (!empty($oauth_token) && !empty($oauth_token_secret)) {
				$this->token = new OAuthConsumer($oauth_token, $oauth_token_secret);
			} else {
				$this->token = NULL;
			}
		}
		
		/**
		 * 
		 * Get an oAuth request token
		 * 
		 * @param string $oauth_callback optional callback URL
		 * @return string oAuth request token
		 */
		public function getRequestToken($oauth_callback = null){
			$params = array();
			
			if(!empty($oauth_callback)){
				$params["oauth_callback"] = $oauth_callback;
			}
			
			$request = $this->oAuthRequest(self::_URL_REQUEST, "POST", $params);
			$token = OAuthUtil::parse_parameters($request);
			
			$this->token = new OAuthConsumer($token['oauth_token'], $token['oauth_token_secret']);
    		
			return $token;
		}
		
		/**
		 * Get an URL to autorize the user
		 * 
		 * @param array $token
		 * @return string the URL to forward the user to
		 */
		function getAuthorizeURL($token) {
			if (is_array($token)) {
				$token = $token['oauth_token'];
			}
			
			return self::_URL_AUTH . $token;
		}
		
		/**
		 * Get a access_token from a request token
		 * 
		 * @param string $oauth_verifier the oauth_verifier from the response 
		 * @return array("oauth_token" => "the-access-token",
		 * 				"oauth_token_secret" => "the-access-secret",
		 * 				"user_id" => "9436992")
		 */
		function getAccessToken($oauth_verifier = FALSE) {
			$parameters = array();
			
			if (!empty($oauth_verifier)) {
				$parameters['oauth_verifier'] = $oauth_verifier;
			}
			
			$request = $this->oAuthRequest(self::_URL_ACCESS, "POST", $parameters);
			$token = OAuthUtil::parse_parameters($request);
			
			$this->token = new OAuthConsumer($token["oauth_token"], $token["oauth_token_secret"]);
			
			return $token;
		}
		
		/**
		 * Wrapper function for oAuth GET request
		 * 
		 * @param string $url the API URL
		 * @param array $parameters optional parameters
		 * @return mixed
		 */
		function get($url, $parameters = array()) {
			$response = $this->oAuthRequest($url, "GET", $parameters);
			if ($this->format === "json" && $this->decode_json) {
				return json_decode($response);
			}
			return $response;
		}
		
		/**
		 * Wrapper function for oAuth POST request
		 * 
		 * @param string $url the API URL
		 * @param array $parameters optional parameters
		 * @return mixed
		 */
		function post($url, $parameters = array()) {
			$response = $this->oAuthRequest($url, "POST", $parameters);
			if ($this->format === "json" && $this->decode_json) {
				return json_decode($response);
			}
			return $response;
		}
		
		/**
		 * Do a signed oAuth request
		 * 
		 * @param string $url the API URL
		 * @param string $method request method (GET, POST)
		 * @param array $params optional parameters
		 * @return mixed
		 */
		function oAuthRequest($url, $method, $params = array()){
			if (strrpos($url, "https://") !== 0 && strrpos($url, "http://") !== 0) {
				$url = $this->host . $url;
			}
		
			$request = OAuthRequest::from_consumer_and_token($this->consumer, $this->token, $method, $url, $params);
			$request->sign_request($this->sha1_method, $this->consumer, $this->token);
			
			$headers = array($request->to_header());
			$request->unset_parameter("oauth_version");
			$request->unset_parameter("oauth_nonce");
			$request->unset_parameter("oauth_timestamp");
			$request->unset_parameter("oauth_consumer_key");
			$request->unset_parameter("oauth_token");
			$request->unset_parameter("oauth_signature");
			$request->unset_parameter("oauth_signature_method");
			
			switch ($method) {
				case "GET":
					return $this->http($request->to_url(), "GET", null, $headers);
					break;
				default:
					return $this->http($request->get_normalized_http_url(), $method, $request->to_postdata(), $headers);
				break;
			}
		}
		
		/**
		 * Make a HTTP request
		 * 
		 * @param string $url the URL to send the request to
		 * @param string $method request method (GET, POST)
		 * @param $postfields optional post fields
		 * @return API result
		 */
		protected function http($url, $method, $postfields = NULL, $headers = array()) {
			$this->http_info = array();
			
			$default_headers = array('Expect:');
			$headers = array_merge($default_headers, $headers);
			
			$ci = curl_init();
			/* Curl settings */
			curl_setopt($ci, CURLOPT_USERAGENT, $this->useragent);
			curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, $this->connecttimeout);
			curl_setopt($ci, CURLOPT_TIMEOUT, $this->timeout);
			curl_setopt($ci, CURLOPT_RETURNTRANSFER, TRUE);
			curl_setopt($ci, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, $this->ssl_verifypeer);
			curl_setopt($ci, CURLOPT_HEADERFUNCTION, array($this, 'getHeader'));
			curl_setopt($ci, CURLOPT_HEADER, FALSE);
			
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
					break;
			}
			
			curl_setopt($ci, CURLOPT_URL, $url);
			
			$response = curl_exec($ci);
			
			$this->http_code = curl_getinfo($ci, CURLINFO_HTTP_CODE);
			$this->http_info = array_merge($this->http_info, curl_getinfo($ci));
			
			$this->url = $url;
			
			curl_close ($ci);
			
			return $response;
		}
		
		/**
		 * Get the header info to store.
		 * 
		 * @param cUrl handler $ch
		 * @param string $header
		 */
		protected function getHeader($ch, $header) {
			$i = strpos($header, ':');
			
			if (!empty($i)) {
				$key = str_replace('-', '_', strtolower(substr($header, 0, $i)));
				$value = trim(substr($header, $i + 2));
				
				$this->http_header[$key] = $value;
			}
			
			return strlen($header);
		}
	}