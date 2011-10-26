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

class GenusApisUtil {
	public static function doCurlHttpCall($url, $vars, $doPost) {
		$ch = curl_init();
		if ($doPost) {
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $vars);
		} else {
			curl_setopt($ch, CURLOPT_URL, $url . "?" . $vars);
			curl_setopt($ch, CURLOPT_POST, 0);
		}
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$output = curl_exec($ch);
		curl_close($ch);
		return $output;
	}
	
	public static function boolToString($bool) {
		return $bool ? "true" : "false";
	}
	
	public static function stringToBool($string) {
		return strtolower($string) == "true" ? true : false;
	}	
	
	public static function calculateOAuthSignature($http_method, $uri, $sVar, $consumersecret, $oauth_token_secret) {
		$params = GenusOAuthUtil::normalizeKeyValueParameters($sVar);
		$basestring = GenusOAuthUtil::generateBaseString($http_method, $uri, $params);
		return GenusOAuthUtil::calculateHMACSHA1Signature($basestring, $consumersecret, $oauth_token_secret);
	}	
}
