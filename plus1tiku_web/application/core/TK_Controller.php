<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class TK_Controller extends CI_Controller {
    public function __construct(){
        parent::__construct();
    }

    protected function ret_json($ret, $msg, $data = null){
        $res = $data;
        $res->retcode = $ret;
        $res->retmsg = $msg;

        header ('Content-Type: application/json; charset=utf-8');
        $this->output->set_output(json_encode($data));
        return;
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
