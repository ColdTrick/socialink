<?php

/**
* This class is a wrapper around the default Hyves (GenusApi) class to support cURL proxy
*
* @package Socialink
*/
class HyvesProxy extends GenusApis {
	private $proxy = false;
	
	protected $oOAuthConsumer;
	protected $timestampLastMethod;
	protected $nonce;
	protected $ha_version;
	
	/**
	 * Constructor
	 *
	 * @param GenusOAuthConsumer $oOAuthConsumer Consumer settings
	 * @param string             $ha_version     HA version
	 *
	 * @return void
	 */
	public function __construct(GenusOAuthConsumer $oOAuthConsumer, $ha_version) {
		$this->oOAuthConsumer = $oOAuthConsumer;
		$this->ha_version = $ha_version;
	}
	
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
	 * Call the API
	 *
	 * @param string         $ha_method   the API method
	 * @param array          $sParams     additional params
	 * @param GenusOAuthBase $oOAuthToken OAuth token
	 * @param string         $httpType    the http call type
	 *
	 * @return string
	 */
	public function doMethod($ha_method, $sParams, GenusOAuthBase $oOAuthToken = null, $httpType = self::HTTP_TYPE_POST) {
		$sDefaultParams = array(
			"oauth_consumer_key" => $this->oOAuthConsumer->getKey(),
			"oauth_timestamp" => $this->getOAuthTimestamp(),
			"oauth_nonce" => $this->getOAuthNonce(),
			"oauth_signature_method" => self::DEFAULT_OAUTH_SIGNATURE_METHOD,
			"ha_method" => $ha_method,
			"ha_version" => $this->ha_version,
			"ha_format" => self::DEFAULT_HA_FORMAT,
			"ha_fancylayout" => self::DEFAULT_HA_FANCYLAYOUT
		);
	
		$oauth_consumer_secret = $this->oOAuthConsumer->getSecret();
		$oauth_token_secret = "";
	
		if ($oOAuthToken !== null) {
			$sDefaultParams["oauth_token"] = $oOAuthToken->getKey();
			$oauth_token_secret = $oOAuthToken->getSecret();
		}
	
		$sParams = $sParams + $sDefaultParams;
		$sParams["oauth_signature"] = GenusApisUtil::calculateOAuthSignature($httpType, self::API_URL, $sParams, $oauth_consumer_secret, $oauth_token_secret);
	
		$params = GenusOAuthUtil::normalizeKeyValueParameters($sParams);
		$response = HyvesProxyUtil::doCurlHttpCall(self::API_URL, $params, ($httpType == self::HTTP_TYPE_POST), $this->proxy);
		$oXml = simplexml_load_string($response);
	
		if ($oXml === false) {
			throw new GenusGeneralException("Failed to parse response to XML. \nResponse:\n" . $response);
		}
		$this->checkForErrors($oXml);
		return $oXml;
	}
	
	/**
	 * Check the result for errors
	 *
	 * @param SimpleXMLElement $oXml the XML element
	 *
	 * @throws GenusHyvesApiException
	 *
	 * @return void
	 */
	protected function checkForErrors($oXml) {
		if (isset($oXml->error_code)) {
			throw new GenusHyvesApiException($oXml->error_message, $oXml->error_code, $oXml);
		}
	}
	
	/**
	 * Get a valid timestamp
	 *
	 * @return int
	 */
	protected function getOAuthTimestamp() {
		$timestamp = time();
		if ($this->timestampLastMethod == $timestamp) {
			$this->nonce++;
		} else {
			$this->timestampLastMethod = $timestamp;
			$this->nonce = 0;
		}
		return $this->timestampLastMethod;
	}
	
	/**
	 * Get a valid nonce
	 *
	 * @return string
	 */
	protected function getOAuthNonce() {
		$ipAddress = ip2long($_SERVER['REMOTE_ADDR']);
		$rand = rand(0, getrandmax());
		return $this->nonce . "_" . $ipAddress . "_" . $rand;
	}
}