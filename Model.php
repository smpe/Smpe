<?php
// Copyright 2015 The Smpe Authors. All rights reserved.
// Use of this source code is governed by a BSD-style
// license that can be found in the LICENSE file.

class Smpe_Model
{
    /**
     * @return Smpe_DbInterface
     * @throws Exception
     */
    public static function data()
    {
        if(is_null(static::$data)){
            if(!isset(Config::$modules[static::$module]['dsn'])) {
                $err = Smpe_I18in::smpe('Config not exists: ', "Config::\$modules['%s']['dsn']");
                throw new Exception(sprintf($err, static::$module));
            }

            $dsnKey = Config::$modules[static::$module]['dsn'];

            if(!isset(Config::$dsn[$dsnKey]['type'])) {
                $err = Smpe_I18in::smpe('Config not exists: ', "Config::\$dsn['%s']['type']");
                throw new Exception(sprintf($err, $dsnKey));
            }

            // Data Access Objects
            $cls = 'Smpe_Db'.Config::$dsn[$dsnKey]['type'];
            static::$data = new $cls(static::$module, static::$table, static::$primary, static::$joins);
        }

        //Return static::$data anywhere.
        return static::$data;
    }

    /**
     * @param $str
     * @param string $origin
     * @return mixed
     */
    public static function i18in($str, $origin = '') {
        return Smpe_Application::i18in(static::$module, $str, $origin);
    }
}