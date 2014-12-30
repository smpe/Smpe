<?php
// Copyright 2015 The Smpe Authors. All rights reserved.
// Use of this source code is governed by a BSD-style
// license that can be found in the LICENSE file.

class Smpe_I18in
{
	/**
	 * @param string $str
	 * @param string $origin
	 * @return string
	 */
	public static function parse($str, $origin = '') {
		if(isset(static::$nodes[$str])) {
			return static::$nodes[$str].$origin;
		} else {
			// Save log.
            Smpe_Application::log($str, 'i18in');
			return $str.$origin;
		}
	}

	/**
	 * @param string $str
	 * @param string $origin
	 * @return string
	 */
	public static function smpe($str, $origin = '') {
		return Smpe_Application::i18in('Smpe', $str, $origin);
	}
}
