<?php
// Copyright 2015 The Smpe Authors. All rights reserved.
// Use of this source code is governed by a BSD-style
// license that can be found in the LICENSE file.

mb_internal_encoding('utf-8');
header("Content-type: text/html; charset=UTF-8");

class Smpe_Application
{
    /**
     * @var string
     */
    public static $time = '';

    /**
     * @var int
     */
    public static $timestamp = 0;

    /**
     * @var string
     */
    public static $workingDir = '';

    /**
     * @var string
     */
    public static $lang = array('code'=>'en_US', 'name'=>'en-US');

    /**
     * @var array
     */
    public static $request = array(
        'module' => '',
        'controller' => 'Index',
        'action' => 'Index',
        'args' => array(),
        'protocol' => '',
        'host' => '',
        'domain' => '',
    );

    /**
     * Run application
     * @param $workingDir
     * @param string $configPath
     */
    public static function init($workingDir, $configPath = '') {
        try {
            self::initWorkingDir($workingDir);
            self::initDomain();
            self::initAutoload();
            self::initConfig($configPath);
            self::initLanguage();
            self::initLog();
            self::initRequest();
            self::initActionName();
            self::initController();
            $r = self::initAction();
        } catch (Exception $e) {
            $r = array('data'=>-1, 'msg'=>$e->getMessage());
        }

        self::result($r);

        if(Config::$environment < 2) {
            $t = number_format(microtime(true)-$_SERVER['REQUEST_TIME_FLOAT'], 4, '.', '');
            self::log(sprintf("%s: Consuming time %ss (%s)\n", self::$time, $t, $_SERVER['REQUEST_URI']));
        }
    }

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
     * @param $mdoule
     * @param $str
     * @param string $origin
     * @return mixed
     */
    public static function i18in($mdoule, $str, $origin = '') {
        if(self::$lang['code'] == 'en_US') {
            return $str.$origin;
        } else {
            $cls = $mdoule.'_'.self::$lang['code'];
            if(class_exists($cls)) {
                return $cls::parse($str, $origin);
            } else {
                return $str.$origin;
            }
        }
    }

    /**
     * @var object
     */
    private static $action = null;

    /**
     * Working directory
     * @param $p
     */
    private static function initWorkingDir($p) {
        self::$workingDir = $p;
        self::$time = date('Y-m-d H:i:s');
        self::$timestamp = time();
    }

    /**
     * Domain
     */
    private static function initDomain() {
        self::$request['protocol'] = $_SERVER['SERVER_PORT'] == '443' ? 'https' : 'http';
        self::$request['host'] = $_SERVER['HTTP_HOST'];
        self::$request['domain'] = strstr($_SERVER['HTTP_HOST'], '.');
        if(empty(self::$request['domain'])) {
            self::$request['domain'] = $_SERVER['HTTP_HOST'];
        }
    }

    /**
     * Autoload class
     */
    private static function initAutoload() {
        spl_autoload_register('Smpe_Application::autoload');
    }

    /**
     * @param string $path
     * @throws Exception
     */
    private static function initConfig($path = '') {
        if(empty($path)){
            $path = sprintf("%s/Config.php", self::$workingDir);
        }

        if(is_file($path)) {
            require $path;
        } else {
            throw new Exception(Smpe_I18in::smpe('Cannot load configuration file: ', $path));
        }
    }

    /**
     * language
     */
    private static function initLanguage() {
        $s = Smpe_InputFilter::string('HTTP_ACCEPT_LANGUAGE');
        if(is_null($s) || $s === false) {
            return;
        }

        self::$lang['name'] = locale_accept_from_http($s);
        self::$lang['code'] = str_replace('-', '_', self::$lang['name']);
    }

    /**
     * Log
     */
    private static function initLog() {
        if(Config::$environment < 2) { // < staging
            ini_set('error_reporting', E_ALL | E_STRICT);
            ini_set('log_errors', 0);
            ini_set('display_errors', 1);
            ini_set('display_startup_errors', 1);
        }
    }

    /**
     * Request
     */
    private static function initRequest() {
        self::initArgs();

        //module
        if(empty(self::$request['args'][0]) || self::$request['args'][0] == 'index.php'){
            self::$request['module'] = Config::$defaultModule;
        } else {
            self::$request['module'] = array_shift(self::$request['args']);
        }

        //controller
        if(!empty(self::$request['args'][0])) {
            self::$request['controller'] = array_shift(self::$request['args']);
        }

        //action
        if(!empty(self::$request['args'][0])) {
            self::$request['action'] = array_shift(self::$request['args']);
        }
    }

    /**
     * ActionName
     * @throws Exception
     */
    private static function initActionName() {
        $a = ord(self::$request['action']);
        if($a < 65 || $a > 90) {
            throw new Exception(Smpe_I18in::smpe('The first letter of the method name must be capitalized: ', self::$request['action']));
        }
    }

    /**
     * Arguments
     */
    private static function initArgs() {
        //vDir
        if(Config::$isRewrite){
            $path = parse_url(Smpe_InputFilter::string('REQUEST_URI', INPUT_SERVER), PHP_URL_PATH);
            $path = substr($path, strlen(config::$vDir));
        } else {
            config::$vDir = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            $path = Smpe_InputFilter::string('p', INPUT_GET);
        }
        
        //args
        self::$request['args'] = explode('/', $path);
        
        //Remove "/" and "" at begin.
        if(empty(self::$request['args'][0]) || self::$request['args'][0] == '/') {
            array_shift(self::$request['args']);
        }
    }

    /**
     * Controller
     * @throws Exception
     */
    private static function initController() {
        $s = "%s/controller/%s/%sController.php";
        $path = sprintf($s, self::$workingDir, self::$request['module'], self::$request['controller']);
        if(!is_file($path)){
            throw new Exception(Smpe_I18in::smpe('Cannot load controller file: ', $path));
        }

        require $path;
    }

    /**
     * Action
     * @throws Exception
     */
    private static function initAction() {
        $className = sprintf("%s_%sController", self::$request['module'], self::$request['controller']);
        if(!class_exists($className)){
            throw new Exception(Smpe_I18in::smpe('Class not exists: ', $className));
        }

        self::$action  = new $className();

        if(!method_exists(self::$action, self::$request['action'])){
            throw new Exception(Smpe_I18in::smpe('Method not exists: ', $className.'->'.self::$request['action']));
        }

        // User can write custorm code in this method.
        self::$action->init();

        // If init() is ok, start the action.
        return call_user_func_array(array(self::$action, self::$request['action']), self::$request['args']);
    }

    /**
     * Autoload
     * @param $className
     */
    private static function autoload($className) {
        $path = str_replace(array('_', '\\'), array('/', '/'), $className);
        $path = sprintf('%s/library/%s.php', self::$workingDir, $path);
        
        if(is_file($path)) {
            require $path;
        } else {
            self::log(Smpe_I18in::smpe('File not exists: ', $path."\n"));
        }
    }

    /**
     * Result
     * @param $r
     */
    private static function result($r) {
        if(is_null(self::$action)){
            var_dump($r);
            return;
        }

        if(is_null($r)) {
            return;
        }

        if(!is_array($r)){
            $r = array('data'=>$r, 'msg'=>'');
        }

        if($_SERVER["REQUEST_METHOD"] == 'POST') {
            self::$action->json($r);
        } else { //GET
            self::$action->error($r['msg'], $r['data']);
        }
    }
}
