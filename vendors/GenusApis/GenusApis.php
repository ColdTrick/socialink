<?php
/*
 * Copyright (c) 2008 Kilian Marjew <kilian@marjew.nl>
 *
 * Permission to use, copy, modify, and distribute this software for any
 * purpose with or without fee is hereby granted, provided that the above
 * copyright notice and this permission notice appear in all copies.
 *
 * THE SOFTWARE IS PROVIDED "AS IS" AND THE AUTHOR DISCLAIMS ALL WARRANTIES
 * WITH REGARD TO THIS SOFTWARE INCLUDING ALL IMPLIED WARRANTIES OF
 * MERCHANTABILITY AND FITNESS. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR
 * ANY SPECIAL, DIRECT, INDIRECT, OR CONSEQUENTIAL DAMAGES OR ANY DAMAGES
 * WHATSOEVER RESULTING FROM LOSS OF USE, DATA OR PROFITS, WHETHER IN AN
 * ACTION OF CONTRACT, NEGLIGENCE OR OTHER TORTIOUS ACTION, ARISING OUT OF
 * OR IN CONNECTION WITH THE USE OR PERFORMANCE OF THIS SOFTWARE.
 *
 *
 * @author Kilian Marjew (kilian@marjew.nl)
 * @url http://genusapis.marjew.nl/
 */

// All requires
/*
require_once('lib/GenusApisUtil.php');
require_once('lib/GeneralException.php');
require_once('lib/HyvesApiException.php');
require_once('lib/OAuthBase.php');
require_once('lib/OAuthConsumer.php');
require_once('lib/OAuthRequestToken.php');
require_once('lib/OAuthAccessToken.php');
require_once('lib/OAuthUtil.php');
*/

class GenusApis {
	const DEFAULT_HA_FORMAT = 'xml';
	const DEFAULT_HA_FANCYLAYOUT = 'false';
	const DEFAULT_OAUTH_SIGNATURE_METHOD = "HMAC-SHA1";
	const HTTP_TYPE_GET = 'GET';
	const HTTP_TYPE_POST = 'POST';	
	const API_URL = "http://data.hyves-api.nl/";
	const AUTHORIZE_URL = "http://www.hyves.nl/api/authorize/";
	
	private $oOAuthConsumer;
	private $timestampLastMethod;
	private $nonce;
	private $ha_version;
	
	public function __construct(GenusOAuthConsumer $oOAuthConsumer, $ha_version) {
		$this->oOAuthConsumer = $oOAuthConsumer;
		$this->ha_version = $ha_version;
	}
	
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
		
		if ($oOAuthToken !== null)
		{
			$sDefaultParams["oauth_token"] = $oOAuthToken->getKey();
			$oauth_token_secret = $oOAuthToken->getSecret();
		}
		
		$sParams = $sParams + $sDefaultParams;
		$sParams["oauth_signature"] = GenusApisUtil::calculateOAuthSignature($httpType, self::API_URL, $sParams, $oauth_consumer_secret, $oauth_token_secret);
		
		$params = GenusOAuthUtil::normalizeKeyValueParameters($sParams);
		$response = GenusApisUtil::doCurlHttpCall(self::API_URL, $params, ($httpType == self::HTTP_TYPE_POST));
		$oXml = simplexml_load_string($response);
		
		if ($oXml === false) {
			throw new GenusGeneralException("Failed to parse response to XML. \nResponse:\n".$response);
		}
		$this->checkForErrors($oXml);
		return $oXml;
	}
	
	public function retrieveRequesttoken($aMethods, $expirationtype = 'default') {
		$oXml = $this->doMethod("auth.requesttoken", array("methods" => implode(',', $aMethods), "expirationtype" => $expirationtype));
		return new GenusOAuthRequestToken((string)$oXml->oauth_token, (string)$oXml->oauth_token_secret);
	}
	
	public function getAuthorizeUrl(GenusOAuthRequestToken $oOAuthRequestToken, $callback = null) {
		$url = self::AUTHORIZE_URL . "?oauth_token=" . $oOAuthRequestToken->getKey();
		if ($callback !== null) {
			$url .= "&oauth_callback=" . GenusOAuthUtil::urlencodeRFC3986($callback);
		}
		return $url;
	}

	public function redirectToAuthorizeUrl(GenusOAuthRequestToken $oOAuthRequestToken, $callback = null) {
		$url = $this->getAuthorizeUrl($oOAuthRequestToken, $callback);
		header("Location: " . $url);
	}
	
	public function retrieveAccesstoken(GenusOAuthRequestToken $oOAuthRequestToken) {
		$oXml = $this->doMethod("auth.accesstoken", array(), $oOAuthRequestToken);
		return new GenusOAuthAccessToken((string)$oXml->oauth_token, (string)$oXml->oauth_token_secret, (string)$oXml->userid, (string)$oXml->methods, (integer)$oXml->expiredate);
	}
	
	private function checkForErrors($oXml) {
		if (isset($oXml->error_code)) {
			throw new GenusHyvesApiException($oXml->error_message, $oXml->error_code, $oXml);
		}
	}
	
	private function getOAuthTimestamp() {
		$timestamp = time();
		if ($this->timestampLastMethod == $timestamp) {
			$this->nonce++;
		} else {
			$this->timestampLastMethod = $timestamp;
			$this->nonce = 0;
		}
		return $this->timestampLastMethod;
	}
	
	private function getOAuthNonce() {
		$ipAddress = ip2long($_SERVER['REMOTE_ADDR']);
		$rand = rand(0, getrandmax());
		return $this->nonce . "_" . $ipAddress . "_" . $rand;
	}
	
}