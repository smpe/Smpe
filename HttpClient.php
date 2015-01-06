<?php
// Copyright 2015 The Smpe Authors. All rights reserved.
// Use of this source code is governed by a BSD-style
// license that can be found in the LICENSE file.

class HttpClient
{
    /**
     * @var resource cURL multi handle resource.
     */
    private $mh;

    /**
     * @var resource cURL handle list.
     */
    private $chs = array();

    /**
     * @var array
     */
    private $options = array();

    /**
     * @var array
     */
    private $responses = array();

    /**
     * @var bool
     */
    private $isRunning = false;

    /**
     * @param array $options
     */
    public function __construct($options = array()) {
        // create the multiple cURL handle
        $this->mh = curl_multi_init();

        $this->options = $options;
    }

    /**
     * Insert a handle into the end of the list.
     * @param string $type post or get
     * @param $url
     * @param array $headers
     * @param string $body
     * @return int
     * @throws Exception
     */
    public function newHandle($type, $url, $headers = array(), $body = ''){
        if(empty($url)) {
            throw new Exception('URL address cannot be empty.');
        }

        $part = parse_url($url);
        if($part === false){
            throw new Exception('Invalid URL: '.$url);
        }
        if(empty($part['scheme']) || !in_array($part['scheme'], array('http', 'https'))){
            throw new Exception('Supports only http and https: '.$url);
        }

        return $this->addHandle($type, $url, $headers, $body);
    }

    /**
     * Deleting handle
     * @param $key
     * @return bool
     */
    public function removeHandle($key) {
        if($this->isRunning === true) {
            return false;
        }

        if(isset($this->chs[$key])) {
            curl_close($this->chs[$key]);
            unset($this->chs[$key]);
        }

        if(isset($this->responses[$key])) {
            unset($this->responses[$key]);
        }

        return true;
    }

    /**
     * Processes each of the handles in the stack.
     * This method can be called whether or not a handle needs to read or write data.
     * @throws Exception
     */
    public function exec() {
        $this->isRunning = true;
        $this->responses = array();

        $active = null;
        // execute the handles
        do {
            $mrc = curl_multi_exec($this->mh, $active);
            /*$info = curl_multi_info_read($this->mh);
            if (false !== $info) {
                var_dump($info);
            }*/
        } while ($mrc == CURLM_CALL_MULTI_PERFORM || $active);

        // Blocks until there is activity on any of the curl_multi connections.
        while ($active && $mrc == CURLM_OK) {
            $a = curl_multi_select($this->mh);
            if ($a != -1) {
                do {
                    $mrc = curl_multi_exec($this->mh, $active);
                } while ($mrc == CURLM_CALL_MULTI_PERFORM);
            }
        }

        // close the handles
        foreach($this->chs as $key => &$ch) {
            $this->getContent($key, $ch);
        }

        curl_multi_close($this->mh);

        $this->isRunning = false;
    }

    /**
     * If CURLOPT_RETURNTRANSFER is an option that is set for a specific handle, then this function
     * will return the content of that cURL handle in the form of a string.
     * @return array
     */
    public function responses() {
        return $this->responses;
    }

    /**
     * Adds the ch handle to the multi handle $this->mh.
     * @param string $type
     * @param string $url
     * @param array $headers
     * @param array $body
     * @return int
     */
    private function addHandle($type, $url, $headers = array(), $body = array()) {
        // create both cURL resources
        $this->chs[] = curl_init();
        $key = count($this->chs[]) - 1;

        // set URL and other appropriate options
        curl_setopt($this->chs[$key], CURLOPT_AUTOREFERER, true);
        curl_setopt($this->chs[$key], CURLOPT_URL, $url);
        curl_setopt($this->chs[$key], CURLOPT_FAILONERROR, false);
        curl_setopt($this->chs[$key], CURLOPT_RETURNTRANSFER, true);

        // https
        if(strtolower(substr($url, 0, 5)) == "https") {
            curl_setopt($this->chs[$key], CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($this->chs[$key], CURLOPT_SSL_VERIFYHOST, false);
        }

        // headers
        if (!empty($headers)) {
            // An array of HTTP header fields to set, in the format array('Content-type: text/plain', 'Content-length: 100')
            curl_setopt($this->chs[$key], CURLOPT_HTTPHEADER, $headers);
        }

        // post
        if (strtolower($type) == 'post') {
            curl_setopt($this->chs[$key], CURLOPT_POST, true);
            // If value is an array, the Content-Type header will be set to multipart/form-data.
            curl_setopt($this->chs[$key], CURLOPT_POSTFIELDS, $body);
        }

        // custom options
        foreach($this->options as $k => $v) {
            curl_setopt($this->chs[$key], $k, $v);
        }

        //add the two handles
        curl_multi_add_handle($this->mh, $this->chs[$key]);

        return $key;
    }

    /**
     * Return the content of a cURL handle if CURLOPT_RETURNTRANSFER is set.
     * @param $key
     * @param $ch
     * @throws Exception
     */
    private function getContent($key, &$ch) {
        $err = curl_errno($ch);
        if ($err != '') {
            $this->responses[$key] = $err;
        }

        $this->responses[$key]['body'] = curl_multi_getcontent($ch);
        $this->responses[$key]['http_code'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_multi_remove_handle($this->mh, $ch);
    }
}