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

class GenusOAuthAccessToken extends GenusOAuthBase {
	private $userid;
	private $methods;
	private $expiredate;
	
	public function __construct($key, $secret, $userid, $methods, $expiredate) {
		parent::__construct($key, $secret);
		$this->userid = $userid;
		$this->methods = $methods;
		$this->expiredate = $expiredate;
	}
	
	public function getUserid() {
		return $this->userid;
	}

	public function getMethods() {
		return $this->methods;
	}

	public function getExpiredate() {
		return $this->expiredate;
	}
}
