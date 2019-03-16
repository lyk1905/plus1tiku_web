<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class TK_Model extends CI_Model {
    public function __construct(){
        parent::__construct();
    }
    //application/config/config.php 中的threshold 需要设置，设置为0相当于禁止日志
    //log的目录必须设置为可写的
    protected function log_err($msg){
        log_message('error', $msg);
    }
    protected function log_info($msg){
        log_message('info', $msg);
    }
    protected function log_debug($msg){
        log_message('debug', $msg);
    }
}