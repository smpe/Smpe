<?php
// Copyright 2015 The Smpe Authors. All rights reserved.
// Use of this source code is governed by a BSD-style
// license that can be found in the LICENSE file.

class Smpe_Config
{
	/**
	 * @var int version
	 */
	public static $version = 1;

	/**
	 * @var number 0:development 1:testing 2:staging 3:production
	 */
	public static $environment = 0;

	/**
	 * @var bool url rewrite.
	 */
	public static $isRewrite = false;

	/**
	 * @var string Virtual directory. $isRewrite only.
	 */
	public static $vDir = '';

	/**
	 * @var array modules
	 */
	public static $modules = array();

	/**
	 * @var array DSN
	 */
	public static $dsn = array();

	/**
	 * @var string Default module
	 */
	public static $defaultModule = '';

	/**
	 * @var bool Whether to enable multi-language
	 */
	public static $enableI18in = false;
}
