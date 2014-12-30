<?php
// Copyright 2015 The Smpe Authors. All rights reserved.
// Use of this source code is governed by a BSD-style
// license that can be found in the LICENSE file.

class Smpe_InputFilter
{
    /**
     * @param $v
     * @param $t
     * @return mixed
     * @throws Exception
     */
    private static function ex($v, $t) {
        if(is_null($v) || $v === false) {
            throw new Exception($t.' is invalid.');
        } else {
            return $v;
        }
    }

    /**
     * Gets a specific external variable by name and optionally filters it (Unsafe)
     * @param string $queryName
     * @param int $type INPUT_GET, INPUT_POST, INPUT_COOKIE, INPUT_SERVER, INPUT_ENV
     * @return array
     */
    public static function arr($queryName, $type = INPUT_POST)
    {
        return filter_input($type, $queryName, FILTER_DEFAULT, array('flags' => FILTER_REQUIRE_ARRAY));
    }

    /**
     * Gets a specific external variable by name and optionally filters it (Unsafe)
     * @param string $queryName
     * @param int $type INPUT_GET, INPUT_POST, INPUT_COOKIE, INPUT_SERVER, INPUT_ENV
     * @return array
     */
    public static function arrEx($queryName, $type = INPUT_POST)
    {
        return self::ex(self::arr($queryName, $type), $queryName);
    }

    /**
     * Gets a specific external variable by name and optionally filters it
     * @param mixed $queryName
     * @param int $type INPUT_GET, INPUT_POST, INPUT_COOKIE, INPUT_SERVER, INPUT_ENV
     * @return boolean
     */
    public static function boolean($queryName, $type = INPUT_POST)
    {
        return filter_input($type, $queryName, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Gets a specific external variable by name and optionally filters it
     * @param mixed $queryName
     * @param int $type INPUT_GET, INPUT_POST, INPUT_COOKIE, INPUT_SERVER, INPUT_ENV
     * @return boolean
     */
    public static function booleanEx($queryName, $type = INPUT_POST)
    {
        return self::ex(self::boolean($queryName, $type), $queryName);
    }

    /**
     * Gets a specific external variable by name and optionally filters it
     * @param string $queryName
     * @param int $type INPUT_GET, INPUT_POST, INPUT_COOKIE, INPUT_SERVER, INPUT_ENV
     * @return string
     */
    public static function email($queryName, $type = INPUT_POST)
    {
        return filter_input($type, $queryName, FILTER_VALIDATE_EMAIL);
    }

    /**
     * Gets a specific external variable by name and optionally filters it
     * @param string $queryName
     * @param int $type INPUT_GET, INPUT_POST, INPUT_COOKIE, INPUT_SERVER, INPUT_ENV
     * @return string
     */
    public static function emailEx($queryName, $type = INPUT_POST)
    {
        return self::ex(self::email($queryName, $type), $queryName);
    }

    /**
     * Gets a specific external variable by name and optionally filters it
     * @param $queryName
     * @param int $type INPUT_GET, INPUT_POST, INPUT_COOKIE, INPUT_SERVER, INPUT_ENV
     * @return float
     */
    public static function float($queryName, $type = INPUT_POST)
    {
        return filter_input($type, $queryName, FILTER_VALIDATE_FLOAT);
    }

    /**
     * Gets a specific external variable by name and optionally filters it
     * @param $queryName
     * @param int $type INPUT_GET, INPUT_POST, INPUT_COOKIE, INPUT_SERVER, INPUT_ENV
     * @return float
     */
    public static function floatEx($queryName, $type = INPUT_POST)
    {
        return self::ex(self::float($queryName, $type), $queryName);
    }

    /**
     * Gets a specific external variable by name and optionally filters it
     * @param $queryName
     * @param int $type INPUT_GET, INPUT_POST, INPUT_COOKIE, INPUT_SERVER, INPUT_ENV
     * @return int
     */
    public static function int($queryName, $type = INPUT_POST)
    {
        return filter_input($type, $queryName, FILTER_VALIDATE_INT);
    }

    /**
     * Gets a specific external variable by name and optionally filters it
     * @param $queryName
     * @param int $type INPUT_GET, INPUT_POST, INPUT_COOKIE, INPUT_SERVER, INPUT_ENV
     * @return int
     */
    public static function intEx($queryName, $type = INPUT_POST)
    {
        return self::ex(self::int($queryName, $type), $queryName);
    }

    /**
     * Gets a specific external variable by name and optionally filters it
     * @param string $queryName
     * @param int $type INPUT_GET, INPUT_POST, INPUT_COOKIE, INPUT_SERVER, INPUT_ENV
     * @return string
     */
    public static function order($queryName = 'order', $type = INPUT_GET)
    {
        //a.UserID-asc:b.CreationTime-desc
        $orderStr = self::string($queryName, $type);
        if(empty($orderStr)){
            return array();
        }

        $result = array();
        $orders = explode(':', $orderStr);
        foreach ($orders as $key => $value) {
            $item = explode('-', $value);
            $result[$item[0]] = $item[1];
        }

        return $result;
    }

    /**
     * Gets a specific external variable by name and optionally filters it (Unsafe)
     * @param string $queryName
     * @param int $type INPUT_GET, INPUT_POST, INPUT_COOKIE, INPUT_SERVER, INPUT_ENV
     * @return string
     */
    public static function raw($queryName, $type = INPUT_POST)
    {
        return filter_input($type, $queryName, FILTER_UNSAFE_RAW);
    }

    /**
     * Gets a specific external variable by name and optionally filters it (Unsafe)
     * @param string $queryName
     * @param int $type INPUT_GET, INPUT_POST, INPUT_COOKIE, INPUT_SERVER, INPUT_ENV
     * @return string
     */
    public static function rawEx($queryName, $type = INPUT_POST)
    {
        return self::ex(self::raw($queryName, $type), $queryName);
    }

    /**
     * Gets a specific external variable by name and optionally filters it
     * The translations performed are:
     *  '&' (ampersand) becomes '&amp;'
     *  '"' (double quote) becomes '&quot;' when ENT_NOQUOTES is not set.
     *  ''' (single quote) becomes '&#039;' only when ENT_QUOTES is set.
     *  '<' (less than) becomes '&lt;'
     *  '>' (greater than) becomes '&gt;'
     *
     * @param string $queryName
     * @param int $type INPUT_GET, INPUT_POST, INPUT_COOKIE, INPUT_SERVER, INPUT_ENV
     * @return string
     */
    public static function string($queryName, $type = INPUT_POST)
    {
        return filter_input($type, $queryName, FILTER_SANITIZE_SPECIAL_CHARS);
    }

    /**
     * Gets a specific external variable by name and optionally filters it
     * The translations performed are:
     *  '&' (ampersand) becomes '&amp;'
     *  '"' (double quote) becomes '&quot;' when ENT_NOQUOTES is not set.
     *  ''' (single quote) becomes '&#039;' only when ENT_QUOTES is set.
     *  '<' (less than) becomes '&lt;'
     *  '>' (greater than) becomes '&gt;'
     *
     * @param string $queryName
     * @param int $type INPUT_GET, INPUT_POST, INPUT_COOKIE, INPUT_SERVER, INPUT_ENV
     * @return string
     */
    public static function stringEx($queryName, $type = INPUT_POST)
    {
        return self::ex(self::string($queryName, $type), $queryName);
    }

    /**
     * Gets a specific external variable by name and optionally filters it
     * @param string $queryName
     * @param int $type INPUT_GET, INPUT_POST, INPUT_COOKIE, INPUT_SERVER, INPUT_ENV
     * @return string
     */
    public static function url($queryName, $type = INPUT_POST)
    {
        return filter_input($type, $queryName, FILTER_VALIDATE_URL);
    }

    /**
     * Gets a specific external variable by name and optionally filters it
     * @param string $queryName
     * @param int $type INPUT_GET, INPUT_POST, INPUT_COOKIE, INPUT_SERVER, INPUT_ENV
     * @return string
     */
    public static function urlEx($queryName, $type = INPUT_POST)
    {
        return self::ex(self::url($queryName, $type), $queryName);
    }
}
