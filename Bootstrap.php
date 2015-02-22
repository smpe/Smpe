<?php
// Copyright 2015 The Smpe Authors. All rights reserved.
// Use of this source code is governed by a BSD-style
// license that can be found in the LICENSE file.

mb_internal_encoding('utf-8');
class Smpe_Bootstrap
{
    /**
     * @var string
     */
    public static $workingDir = '';

    /**
     * @param $message
     * @param string $scope
     * @param array $options
     * @return int
     */
    public static function log($message, $scope = 'smpe', $options = array()) {
        return file_put_contents(self::$workingDir.'/data/log/'.$scope.'.log', $message, FILE_APPEND|LOCK_EX);
    }

    /**
     * Working directory
     * @param $p
     */
    protected static function initWorkingDir($p) {
        if(empty(self::$workingDir)){
            self::$workingDir = $p;
        }
    }

    /**
     * Autoload class
     */
    protected static function initAutoload() {
        spl_autoload_register('Smpe_Bootstrap::autoload');
    }

    /**
     * Autoload
     * @param $className
     */
    protected static function autoload($className) {
        $path = str_replace(array('_', '\\'), array('/', '/'), $className);
        $path = sprintf('%s/library/%s.php', self::$workingDir, $path);

        if(is_file($path)) {
            require $path;
        } else {
            self::log(Smpe_I18in::smpe('File not exists: ', $path."\n"));
        }
    }
}