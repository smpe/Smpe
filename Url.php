<?php
// Copyright 2015 The Smpe Authors. All rights reserved.
// Use of this source code is governed by a BSD-style
// license that can be found in the LICENSE file.

class Smpe_Url
{
    /**
     * @param $module
     * @param string $controller
     * @param string $action
     * @param array $args
     * @param array $query
     * @param string $anchor
     * @return string
     */
    public static function http($module = '', $controller = '', $action = '', $args = array(), $query = array(), $anchor = '') {
        $f = 'fullUrl'.Config::$fun;
        return self::$f('http', $module, $controller, $action, $args, $query, $anchor);
    }

    /**
     * @param $module
     * @param string $controller
     * @param string $action
     * @param array $args
     * @param array $query
     * @param string $anchor
     * @return string
     */
    public static function https($module = '', $controller = '', $action = '', $args = array(), $query = array(), $anchor = '') {
        $f = 'fullUrl'.Config::$fun;
        return self::$f('http', $module, $controller, $action, $args, $query, $anchor);
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
     * @param string $module
     * @param string $controller
     * @param string $action
     * @param array $args
     * @param array $query
     * @param string $anchor
     * @return string
     */
    private static function fullUrl($schema, $module = '', $controller = '', $action = '', $args = array(), $query = array(), $anchor = '') {
        if(empty($module)){
            return Config::$vDir;
        }

        $p = '/'.$module;

        if(!empty($controller)){
            $p .= '/'.$controller;
        }
        if(!empty($action)){
            $p .= '/'.$action;
        }
        if(!empty($args)){
            $p .= '/'.implode('/', $args);
        }
        if(!empty($query)){
            $p .= '&'.http_build_query($query);
        }
        if(!empty($anchor)){
            $p .= '#'.$anchor;
        }

        return sprintf('%s?p=%s', Config::$vDir, $p);
    }

    /**
     * @param $schema
     * @param string $module
     * @param string $controller
     * @param string $action
     * @param array $args
     * @param array $query
     * @param string $anchor
     * @return string
     */
    private static function fullUrlAdv($schema, $module = '', $controller = '', $action = '', $args = array(), $query = array(), $anchor = '') {
        if(empty($module)){
            return sprintf("%s://%s/%s", $schema, Config::$listen, Config::$vDir);
        }

        $p = '/'.$module;

        if(!empty($controller)){
            $p .= '/'.$controller;
        }
        if(!empty($action)){
            $p .= '/'.$action;
        }
        if(!empty($args)){
            $p .= '/'.implode('/', $args);
        }
        if(!empty($query)){
            $p .= '?'.http_build_query($query);
        }
        if(!empty($anchor)){
            $p .= '#'.$anchor;
        }

        return sprintf('%s://%s/%s%s', $schema, Config::$listen, Config::$vDir, $p);
    }
}
