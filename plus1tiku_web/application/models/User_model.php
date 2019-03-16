<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class User_model extends TK_Model {
    const TAB_NAME = "t_acct";
    //新增用户，用于注册
    public function insertUser($phone, $email, $passwd){
        $conn = $this->load->database('tiku', TRUE);
        $now = date('Y-m-d h:i:s', time());
        $insrt_data = array('user_name' => $phone,
            'acct_type' => 1,
            'acct_state' => 1,
            'phone_no'=>$phone,
            'mail'=>$email,
            'regist_time' => $now,
            'update_time'=>'0000-00-00 00:00:00',
            'last_login_time' => '0000-00-00 00:00:00',
            'passwd' => $passwd,
            'data_ver'=>1);
		if(!$conn->insert(self::TAB_NAME, $insrt_data)){
            if($conn->error()->code == 1062){
                $ret = array('retcode'=>200000, 'retmsg'=>$conn->error()->message);
                $this->log_err(array('insert_data'=>$insrt_data, 'err'=>$conn->error()));
                return $ret;
            }else{
                $ret = array('retcode'=>200001, 'retmsg'=>$conn->error()->message);
                $this->log_err(array('insert_data'=>$insrt_data, 'err'=>$conn->error()));
                return $ret;
            }
        }
		return array('retcode'=>0, 'retmsg'=>'succ');
	}

    //查询用户信息，用于登录
    public function getUserInfo($username){
        $conn = $this->load->database('tiku', TRUE);
        $qry = $conn->get_where(self::TAB_NAME, array('user_name'=>$username));
        if($qry->num_rows() > 1){
            $ret = array('retcode'=>200002, 'retmsg'=>'qry result num err');
            $this->log_err(array('ret'=>$ret, 'ret_num'=>$qry->num_rows(), 'user_name'=>$username));
            return $ret;
        }else if($qry->num_rows() == 0){
            $ret = array('retcode'=>200003, 'retmsg'=>'qry result empty');
            $this->log_err(array('ret'=>$ret, 'ret_num'=>$qry->num_rows(), 'user_name'=>$username));
            return $ret;
        }

        $user = $qry->row(0);
        $ret = array('retcode' => 0,
            'user' => array(
                'user_name' => $user->user_name,
                'acct_type' => $user->acct_type,
                'acct_state' => $user->acct_state,
                'phone_no' => $user->phone_no,
                'last_login_time' => $user->last_login_time,
                'last_choose_subject' => $user->last_choose_subject,
                'passwd' => $user->passwd,
                'data_ver' => $user->data_ver
            ));
        return $ret;
    }

    //更新用户信息, uid必填
    // case1: 修改登录记录
    // case2: 更新科目选择信息 --当前只有一个地方用到了选择科目的信息，暂时在控制层控制decode和encode
    /**
    user = {
    uid : 12312,
    acct_type INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '账户类型',
    acct_state INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '账户状态',
    phone_no VARCHAR(64) NOT NULL DEFAULT '' COMMENT '注册手机',
    mail VARCHAR(64) NOT NULL DEFAULT '' COMMENT '注册邮箱',
    last_login_time DATETIME NOT NULL COMMENT '上次登录时间',
    last_choose_subject VARCHAR(1024) NOT NULL DEFAULT '' COMMENT '科目选择{subject_id, subject_name}',
    passwd VARCHAR(256) NOT NULL DEFAULT '' COMMENT '用户密码，临时存这里',
    data_ver BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '数据版本号'
    };
     */
    public function updateUserInfo($user){
        $conn = $this->load->database('tiku', TRUE);
        $now = date('Y-m-d h:i:s', time());

        $new = array('update_time'=>$now, 'data_ver' => ($user['data_ver'] + 1));
        if(isset($user['acct_type']) && $user['acct_type'] > 0){
            $new['acct_type'] = $user['acct_type'];
        }
        if(isset($user['acct_state']) && $user['acct_state'] > 0){
            $new['acct_state'] = $user['acct_state'];
        }
        if(isset($user['mail']) && $user['mail'] > 0){
            $new['mail'] = $user['mail'];
        }
        if(isset($user['last_login_time']) && $user['last_login_time'] > 0){
            $new['last_login_time'] = $user['last_login_time'];
        }
        if(isset($user['last_choose_subject']) && $user['last_choose_subject'] > 0){
            $new['last_choose_subject'] = $user['last_choose_subject'];
        }
        if(isset($user['passwd']) && $user['passwd'] > 0){
            $new['passwd'] = $user['passwd'];
        }

        $conn->where('uid', $user['uid']);
        $conn->where('data_ver', $user['data_ver']);
        if(!$conn->update(self::TAB_NAME, $new)){
            if($conn->affected_rows() === 0){
                $ret = array('retcode'=>200004, 'retmsg'=> 'user not exist or cas err');
                $this->log_err(array('ret'=>$ret, 'user'=>$user, 'err'=>$conn->error()));
                return $ret;
            }else{
                $ret = array('retcode'=>200005, 'retmsg'=> 'update err');
                $this->log_err(array('ret'=>$ret, 'user'=>$user, 'err'=>$conn->error()));
                return $ret;
            }
        }

        return array('retcode'=>0, 'retmsg'=>'succ');
    }

}