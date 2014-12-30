<?php
// Copyright 2015 The Smpe Authors. All rights reserved.
// Use of this source code is governed by a BSD-style
// license that can be found in the LICENSE file.

class Smpe_Url
{
    /**
     * @return string
     */
    public static function http() {
        return self::fullUrl('http', func_get_args());
    }

    /**
     * @return string
     */
    public static function https() {
        return self::fullUrl('https', func_get_args());
    }

    /**
     * @param $path
     * @return string
     */
    public static function pub($path) {
        return sprintf("%s%s?time=%d", Config::$vDir, $path, Config::$version);
    }

    /**
     * @param $path
     * @return string
     */
    public static function theme($path) {
        return sprintf("%s/src/themes/default%s?time=%d", Config::$vDir, $path, Config::$version);
    }

    /**
     * @param $schema
     * @param $args
     * @return string
     */
    private static function fullUrl($schema, $args) {
        if(Config::$isRewrite) {
            return self::fullUrlRwrite($schema, $args);
        }

        if(empty($args)) {
            return Config::$vDir;
        }

        $module = array_shift($args);
        return sprintf('%s?p=/%s/%s', Config::$vDir, $module, implode('/', $args));
    }

    /**
     * @param $schema
     * @param $args
     * @return string
     */
    private static function fullUrlRwrite($schema, $args) {
        $module = empty($args) ? Config::$defaultModule : array_shift($args);
        $domain = Config::$modules[$module]['listen'];
        $url = sprintf("%s://%s%s", $schema, $domain, Config::$vDir);
        return sprintf('%s%s/%s', $url, $module, implode('/', $args));
    }
}
