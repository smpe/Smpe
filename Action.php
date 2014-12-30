<?php
// Copyright 2015 The Smpe Authors. All rights reserved.
// Use of this source code is governed by a BSD-style
// license that can be found in the LICENSE file.

class Smpe_Action
{
    /**
     * constructor
     */
    public function __construct(){

    }

    /**
     * Init
     */
    public function init(){

    }

    /**
     * @param $str
     * @param string $origin
     * @return mixed
     */
    public static function i18in($str, $origin = '') {
        return Smpe_Application::i18in(Smpe_Application::$request['module'], $str, $origin);
    }

    /**
     * Error page.
     * @param string $msg
     * @param array $data
     */
    public function error($msg = '', $data = array()) {
        $this->error['msg'] = $msg;
        $this->error['data'] = $data;
        $this->layout('Error');
    }

    /**
     * Json
     * @param array $data
     */
    public function json($data = array()) {
        //ob_start();
        echo json_encode($data);
        //header('Content-Length: '.ob_get_length());
        //ob_end_flush();
    }

    /**
     * @var array Response
     */
    protected $response = array(
        'Title'       =>'',
        'Description' =>'',
    );

    /**
     * @var array
     */
    protected $data = array();

    /**
     * @var array
     */
    protected $pagination = array();

    /**
     * @var array
     */
    protected $error = array();

    /**
     * Get data from $this->data.
     * @param string $node
     * @param string $key
     * @return string
     */
    protected function data($node = '', $key = ''){
        if(isset($this->data[$node][$key])) {
            return $this->data[$node][$key];
        }
        return '';
    }

    /**
    * @param string $layout
    */
    protected function layout($layout = 'Normal') {
        //ob_start();
        $htmlPath = sprintf('%s/layout/%s.php',Smpe_Application::$workingDir, $layout);
        $this->view($htmlPath);
        //header('Content-Length: '.ob_get_length());
        //ob_end_flush();
    }

    /**
     * Load block file.
     * @param $module
     * @param $file
     */
    protected function block($module, $file) {
        $htmlPath = sprintf('%s/block/%s/%s.php',Smpe_Application::$workingDir, $module, $file);
        $this->view($htmlPath);
    }

    /**
     * Load view file.
     * @param string $htmlPath
     * @throws Exception
     */
    protected function view($htmlPath = ''){
        if(empty($htmlPath)) {
            $htmlPath = sprintf(
                '%s/view/%s/%s%s.php',
                Smpe_Application::$workingDir,
                Smpe_Application::$request['module'],
                Smpe_Application::$request['controller'],
                Smpe_Application::$request['action']
            );
        }

        if(is_file($htmlPath)){
            include $htmlPath;
        } else {
            echo Smpe_I18in::smpe('Cannot load view file: ').$htmlPath;
            //throw new Exception('Cannot load view file: '.$htmlPath);
        }
    }

    /**
     * @param string $message
     * @param mixed $data
     * @return array
     */
    protected function failed($message = 'Failed', $data = -1){
        return array('data' => $data, 'msg' => $message);
    }

    /**
     * @param mixed $data
     * @param string $message
     * @return array
     */
    protected function succeed($data = '', $message = 'Succeed'){
        return array('data' => $data, 'msg' => $message);
    }

    /**
     * Initiates a transaction
     * @param string $moduleName
     * @throws Exception
     */
    protected function beginTransaction($moduleName = ''){
        $obj = $this->transactionObj($moduleName);
        if($obj->beginTransaction() === false){
            throw new Exception(Smpe_I18in::smpe('Begin transaction error.'));
        }
    }

    /**
     * Commits a transaction
     * @param string $moduleName
     * @throws Exception
     */
    protected function commit($moduleName = ''){
        $obj = $this->transactionObj($moduleName);
        if($obj->commit() === false){
            throw new Exception(Smpe_I18in::smpe('Commit transaction error.'));
        }
    }

    /**
     * Roll back a transaction
     * @param string $moduleName
     * @throws Exception
     */
    protected function rollBack($moduleName = ''){
        $obj = $this->transactionObj($moduleName);
        $obj->rollBack();
    }

    /**
     * @param string $moduleName
     * @return mixed
     * @throws Exception
     */
    private function transactionObj($moduleName = '') {
        if(empty($moduleName)) {
            $moduleName = Smpe_Application::$request['module'];
        }

        if(!isset(Config::$modules[$moduleName]['dsn']) || !isset(Config::$dsn[Config::$modules[$moduleName]['dsn']])){
            throw new Exception(Smpe_I18in::smpe('Module DB error.'));
        }

        if(!isset(Config::$dsn[Config::$modules[$moduleName]['dsn']]['type'])){
            throw new Exception(Smpe_I18in::smpe('Module DB type error.'));
        }

        $obj = array('Smpe_Db'.Config::$dsn[Config::$modules[$moduleName]['dsn']]['type'], 'db');
        return call_user_func_array($obj, array($moduleName));
    }
}
