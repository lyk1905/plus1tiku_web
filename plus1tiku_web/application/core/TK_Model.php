<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class TK_Model extends CI_Model {
    public function __construct(){
        parent::__construct();
    }
    //application/config/config.php 中的threshold 需要设置，设置为0相当于禁止日志
    //log的目录必须设置为可写的
    protected function log($level, $msg){
        if(is_object($msg) || is_array($msg)){
            log_message($level, json_encode($msg));
        }else{
            log_message($level, $msg);
        }
    }
    protected function log_err($msg){
        $this->log('error', $msg);
    }
    protected function log_info($msg){
        $this->log('info', $msg);
    }
    protected function log_debug($msg){
        $this->log('debug', $msg);
    }
    protected function getUid(){
        $this -> load -> library('session');
        return $this -> session -> userdata('uid');
    }
    protected function getUserName(){
        $this -> load -> library('session');
        return $this -> session -> userdata('user_name');
    }
}