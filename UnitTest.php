<?php
// Copyright 2015 The Smpe Authors. All rights reserved.
// Use of this source code is governed by a BSD-style
// license that can be found in the LICENSE file.

class Smpe_UnitTest extends Smpe_Bootstrap
{
    /**
     * @var array
     */
    private static $args = array();

    /**
     * @param $workingDir
     * @param $args
     * @param string $configPath
     */
    public static function init($workingDir, $args, $configPath = '') {
        self::initWorkingDir($workingDir);
        self::initAutoload();
        array_shift($args);
        self::$args = $args;

        var_dump(self::$args);

        if(count(self::$args) == 2){ // suite
            self::runSuite();
        } else if(count(self::$args) == 3) { // singe
            self::runSinge();
        } else { //error

        }
    }

    /**
     * Working directory
     * @param $p
     */
    protected static function initWorkingDir($p) {
        parent::initWorkingDir($p);
    }

    private static function runSuite($dir = '') {
        $path = self::$workingDir . '/test' . $dir;

        $handle = opendir($path);

        if($handle === false) {
            return;
        }

        while(($file = readdir($handle)) !== false){
            if(in_array($file, array('.', '..', '.svn', 'readme.txt'))){
                continue;
            }

            $filePath  = $path.'/'.$file;

            //子目录
            if(filetype($filePath) == 'dir'){
                //排除data目录, 此目录存放测试用例需要的数据
                if($file != 'data') self::runSuite($dir.'/'.$file);
                continue;
            }

            //运行测试
            require $filePath;

            $object = ltrim($dir, '/').'/'.basename($file, '.php');

            $class = 'test_'.str_replace('/', '_', $object);

            $f = new ReflectionClass($class);
            $methods = array();
            foreach ($f->getMethods() as $m) {
                if ($m->class == $class //排除父类方法
                    && $m->isPublic() //仅公共方法
                    && $m->getNumberOfParameters() == 0 //无参数
                    && substr($m->name, 0, 4) == 'test') { //以test开头的方法
                    $methods[] = $m->name;
                }
            }

            foreach ( $methods as $action ) {
                $result = self::runCore($filePath, $class, $action);

                if($result['data'] === false){
                    echo $class.'::'.$action.'()';
                    echo "\r\n";

                    //保存测试结果到log/tests目录
                    $logDir = self::$workingDir.'/log/test'.$dir.'/'.basename($file, '.php');
                    if(!is_dir($logDir)){
                        mkdir($logDir, 0755, true);
                    }

                    $logPath = $logDir.'/'.$action.'.log';
                    file_put_contents($logPath, $result['msg']);
                }
            }
        }
    }

    private static function runSinge() {
        //mtest 0 monle/htmlTest testDemo
        $object = isset($argv[1]) ? $argv[1] : 'none'; // PHPUnit/Framework/AssertTest
        $action = isset($argv[2]) ? $argv[2] : 'none'; // testEqual

        //$result = monle_test_run($object, $action);
        $filePath = self::$workingDir . "/test/{$object}.php";

        if(!is_file($filePath)){
            return self::result(false, "File not exists: $filePath\r\n");
        }

        require $filePath;

        $class = 'test_'.str_replace('/', '_', $object);

        $result = self::runCore($filePath, $class, $action, $args);

        if($result['data'] === true){
            echo 'SUCCESS';
        }
        else{
            echo 'FAILURE';
        }
        echo "\r\n";
        echo $result['msg'];

    }

    /**
     * 运行测试用例
     * @param $filePath
     * @param $class
     * @param $action
     * @param array $args
     * @return array
     */
    private static function runCore($filePath, $class, $action, $args = array())
    {
        $msg = '';

        try{
            $app = new $class();
            call_user_func_array(array($app, $action), $args);

            //SUCCESS
            return self::result(true, "");
        }
        catch(PHPUnit_Framework_ExpectationFailedException $e){
            //FAILURE
            $msg  = $e->getMessage();
            $msg .= "\r\n";
            $msg .= self::error($e->getTrace(), $filePath);
            $msg .= "\r\n";
            $msg .= $class.'::'.$action.'()';
        }
        catch(PHPUnit_Framework_Exception $e){
            //FAILURE
            $msg  = 'PHPUnit_Framework_Exception:';
            $msg .= "\r\n";
            $msg  = $e->getMessage();
        }
        catch(Exception $e){
            //FAILURE
            $msg  = 'Exception:';
            $msg .= "\r\n";
            $msg  = $e->getMessage();
        }

        return self::result(false, $msg);
    }

    /**
     * @param $traces
     * @param $classPath
     * @return array|string
     */
    private static function error($traces, $classPath)
    {
        $result = array();

        $path = $classPath;
        if(DIRECTORY_SEPARATOR == '\\'){
            $path = str_replace('/', DIRECTORY_SEPARATOR, $classPath);
        }

        foreach ($traces as $trace) {
            if($trace['file'] == $path){
                $result = $trace['file'].':'.$trace['line'];
                break;
            }
        }

        return $result;
    }

    /**
     * @param mixed $data status or data
     * @param string $message
     * @return array
     */
    private static function result($data, $message = '')
    {
        return array('data'=>$data, 'msg'=>$message);
    }
}