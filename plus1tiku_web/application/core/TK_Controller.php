<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$white = array('/user/login', '/user/regist');

class TK_Controller extends CI_Controller {

    public function __construct(){
        parent::__construct();

        //非登录页面，先校验登录态
        global $white;
        $uri = $_SERVER['REQUEST_URI'];
        $idx = strpos($_SERVER['REQUEST_URI'], '?');
        if($idx > 0){
            $uri = substr($_SERVER['REQUEST_URI'], 0, $idx);
        }
        if (!in_array($uri, $white)) {
            $this -> load -> library('session');
            if (!$this -> session -> userdata('uid')){
                $redirect = $this->uri->uri_string();
                if ( $_SERVER['QUERY_STRING'])
                {
                    $redirect .= '?' . $_SERVER['QUERY_STRING'];
                }
                /*跳转到用户登陆页面，指定Login后跳转的URL*/
                $this->load->helper('url');
                redirect('user/login?redirect='.$redirect);
            }
        }
    }

    protected function ret_json($ret, $msg, $data = null){
        $res = $data;
        $res['retcode'] = $ret;
        $res['retmsg'] = $msg;

        header ('Content-Type: application/json; charset=utf-8');
        $this->output->set_output(json_encode($res));
        return;
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
