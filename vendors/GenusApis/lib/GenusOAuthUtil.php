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

class GenusOAuthUtil {
	/**
	 * Takes an array where each entry has keys "key" and "value", and returns a normalized string per oAuth spec 9.1.1
	 * All strings are expected to be latin1
	 *
	 * @aparam array $aParam, array of arrays with keys "key" and "value", e.g. array(array("key"=>"a", "value"=>"1")) 
	 */
	public static function normalizeParameters($aParam) {
		$sortfunc = create_function('$a, $b', '
				$cmp = strcmp($a["key"], $b["key"]);
				if ($cmp == 0) {
					return strcmp($a["value"], $b["value"]);
				}
				return $cmp;
			');
		usort($aParam, $sortfunc);
		$aEncodedVars = array();
		foreach($aParam as $param) {
			$aEncodedVars[] = self::urlencodeRFC3986_UTF8($param["key"])."=".self::urlencodeRFC3986_UTF8($param["value"]);
		}
		return implode("&", $aEncodedVars);
	}
	
	public static function normalizeKeyValueParameters($sParam) {
		$aParam = array();
		foreach($sParam as $key=>$value) {
			$aParam[] = array("key"=>$key, "value"=>$value);
		}
		return self::normalizeParameters($aParam);
	}
	/**
	 * Encodes strings in an RFC3986 compatible encoding
	 *
	 * @param String $string
	 */
	public static function urlencodeRFC3986($string) {
	    return str_replace('%7E', '~', rawurlencode($string));
	}
	public static function urlencodeRFC3986_UTF8($string) {
		return self::urlencodeRFC3986(iconv("ISO-8859-1", "UTF-8", $string));
	}
	
	public static function urldecodeRFC3986($string) {
	    return rawurldecode($string); // no exta stuff needed for ~, goes correctly automatically
	}
	public static function urldecodeRFC3986_UTF8($string) {
		return iconv("UTF-8", "ISO-8859-1", self::urldecodeRFC3986($string));	
	}
	
	/**
	 * Creates the basestring needed for signing per oAuth Section 9.1.2
	 * All strings are latin1
	 *
	 * @param String $http_method: one of the http methods GET, POST, etc.
	 * @param String $uri: the uri; the url without querystring
	 * @param String $normalized parameters as returned from OAuthUtil::normalizeParameters
	 */
	public static function generateBaseString($http_method, $uri, $params) {
		$aBasestring = array(
			self::urlencodeRFC3986_UTF8($http_method),
			self::urlencodeRFC3986_UTF8($uri),
			self::urlencodeRFC3986_UTF8($params),
		);
		return implode("&", $aBasestring);
	}
	
	/**
	 * Calculates the HMAC-SHA1 secret
	 *
	 * @param String $basestring, gotten from generateBaseString
	 * @param String $consumersecret
	 * @param String $tokensecret, leave empty if no token present
	 */
	public static function calculateHMACSHA1Signature($basestring, $consumersecret, $tokensecret) {
		$aKey = array(
			self::urlencodeRFC3986_UTF8($consumersecret),
			self::urlencodeRFC3986_UTF8($tokensecret),
		);
		$key = implode("&", $aKey);
		if (function_exists("hash_hmac")) {
			$signature = base64_encode(hash_hmac("sha1", $basestring, $key, true));
		} else {
			$signature = base64_encode(self::hmacsha1($key, $basestring));
		}
		return $signature;
	}
	
	/**
	 * HMAC-SHA1 not dependent on php compile flags 
	 **/
	public static function hmacsha1($key,$data) {
	    $blocksize=64;
	    $hashfunc='sha1';
	    if (strlen($key)>$blocksize)
	        $key=pack('H*', $hashfunc($key));
	    $key=str_pad($key,$blocksize,chr(0x00));
	    $ipad=str_repeat(chr(0x36),$blocksize);
	    $opad=str_repeat(chr(0x5c),$blocksize);
	    $hmac = pack(
	                'H*',$hashfunc(
	                    ($key^$opad).pack(
	                        'H*',$hashfunc(
	                            ($key^$ipad).$data
	                        )
	                    )
	                )
	            );
	    return $hmac;
	}
	
}