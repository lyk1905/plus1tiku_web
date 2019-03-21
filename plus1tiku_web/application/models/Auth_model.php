<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class AuthType
{
    const NO_AUTH = 1; //无权限
    const GUEST_AUTH = 2; //游客权限
    const EXPIRENCE_AUTH = 3; //体验权限
    const EXAM_AUTH = 4; //考试权限
    const PRACTICE_AUTH = 5; //练习权限
    const ALL_AUTH = 6; //所有权限

    public function hasAuth($user_auth, $require_auth){
        $has_auth = False;
        switch($user_auth){
            case self::NO_AUTH :
            case self::GUEST_AUTH:
            case self::EXPIRENCE_AUTH:
                if($require_auth > $user_auth){
                    $has_auth = False;
                }else{
                    $has_auth = True;
                }
                break;
            case self::EXAM_AUTH :
                if($require_auth == self::PRACTICE_AUTH
                    || $require_auth == self::ALL_AUTH){
                    $has_auth = False;
                }else{
                    $has_auth = True;
                }
                break;
            case self::PRACTICE_AUTH :
                if($require_auth == self::EXAM_AUTH
                    || $require_auth == self::ALL_AUTH){
                    $has_auth = False;
                }else{
                    $has_auth = True;
                }
                break;
            case self::ALL_AUTH :
                $has_auth = True;
                break;
            default :
                $has_auth = False;
                break;
        }
        return $has_auth;
    }
}

class Auth_model extends TK_Model
{
    const TAB_NAME = 't_auth_record';
    /**
     * CREATE TABLE IF NOT EXISTS t_auth_record(
         * auth_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '授权记录id',
         * auth_code VARCHAR(32) NOT NULL DEFAULT '' COMMENT '授权码',
         * type INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '授权类型',
         * code_validity_day BIGINT COMMENT '授权码有效期天数',
         * auth_validity_day BIGINT COMMENT '授权有效天数',
         * state INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '授权状态',
         * subject_id BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '授权科目ID',
         * agent VARCHAR(256) NOT NULL DEFAULT '' COMMENT '中介',
         * agent_id VARCHAR(32) NOT NULL DEFAULT '' COMMENT '中介id',
         * create_time DATETIME NOT NULL COMMENT '生成授权码时间',
         * active_time DATETIME NOT NULL COMMENT '激活时间',
         * validity_end_time INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '有效期截止时间, unixtimestamp',
         * active_user_id BIGINT NOT NULL DEFAULT 0 COMMENT '激活用户',
         * data_ver INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '数据版本号',
         * data_mac VARCHAR(256) NOT NULL DEFAULT '' COMMENT '数据摘要',
         * PRIMARY KEY (`auth_id`),
         * UNIQUE KEY `i_u_authcode` (`auth_code`),
         * KEY `i_user_state_endtime` (`active_user_id`, `state`, `validity_end_time`),
         * KEY `i_user_subject` (`active_user_id`, `subject_id`)
     * )ENGINE=InnoDB AUTO_INCREMENT=1915048 DEFAULT CHARSET=utf8 COMMENT '授权激活记录表'
     */

    //是否有权限
    public function hasAuth($user_auth, $require_auth){
        $auth = new AuthType();
        return $auth->hasAuth($user_auth, $require_auth);
    }

    //激活前判断科目和授权码是否一致
    public function getAuthByCode($code){
        $conn = $this->load->database('tiku', TRUE);
        if(!$conn){
            $ret = array('retcode'=>200100, 'retmsg'=>'connect db failed');
            $this->log_err(array('ret'=>$ret,'code'=>$code));
            return $ret;
        }
        $qry = $conn->get_where(self::TAB_NAME, array('auth_code'=>$code));
        if(!$qry){
            $ret = array('retcode'=>200101, 'retmsg'=>'qry failed');
            $this->log_err(array('ret'=>$ret, 'ret_num'=>$qry->num_rows(), 'code'=>$code));
            return $ret;
        }else if($qry->num_rows() > 1){
            $ret = array('retcode'=>200102, 'retmsg'=>'qry result num err');
            $this->log_err(array('ret'=>$ret, 'ret_num'=>$qry->num_rows(), 'code'=>$code));
            return $ret;
        }else if($qry->num_rows() == 0){
            $ret = array('retcode'=>200103, 'retmsg'=>'qry result empty');
            $this->log_err(array('ret'=>$ret, 'ret_num'=>$qry->num_rows(), 'code'=>$code));
            return $ret;
        }
        $record = $qry->row(0);
        $ret = array('retcode' => 0,
            'record' => array(
                'auth_id' => $record->auth_id,
                'auth_code' => $record->auth_code,
                'type' => $record->type, //授权类型',
                'code_validity_day' => $record->code_validity_day, //授权码有效期天数,
                'auth_validity_day' => $record->auth_validity_day, //授权有效天数,
                'state' => $record->state, //授权状态,
                'subject_id' => $record->subject_id, //授权科目ID,
                'agent' => $record->agent, //中介,
                'agent_id' => $record->agent_id, //中介id,
                'create_time' => $record->create_time, //生成授权码时间,
                'active_time' => $record->active_time, //激活时间,
                'validity_end_time' => $record->validity_end_time, //有效期截止时间, unixtimestamp,
                'active_user_id' => $record->active_user_id, //激活用户,
                'data_ver' => $record->data_ver, //数据版本号,
                'data_mac' => $record->data_mac //数据摘要
            ));
        return $ret;
    }

    //获取授权码列表，判断科目是否已激活
    //获取所有已经激活的科目
    //@TODO 分页查询，估计这个业务很长时间都不会用到
    public function getAuthRecordList($uid, $subject_id = 0){
        $conn = $this->load->database('tiku', TRUE);
        if(!$conn){
            $ret = array('retcode'=>200100, 'retmsg'=>'connect db failed');
            $this->log_err(array('ret'=>$ret, 'uid'=>$uid, 'subject_id'=>$subject_id));
            return $ret;
        }
        $condition = array('active_user_id'=>$uid);
        if($subject_id > 0){
            $condition['subject_id'] = $subject_id;
        }
        $qry = $conn->get_where(self::TAB_NAME, $condition);
        if(!$qry){
            $ret = array('retcode'=>200101, 'retmsg'=>'qry failed');
            $this->log_err(array('ret'=>$ret, 'ret_num'=>$qry->num_rows(),'uid'=>$uid, 'subject_id'=>$subject_id));
            return $ret;
        }

        $ret = array('retcode'=>0, 'retmsg'=>'msg', 'records' => array());
        foreach($qry->result() as $row){
            $record = array(
                'auth_id' => $row->auth_id,
                'auth_code' => $row->auth_code,
                'type' => $row->type, //授权类型',
                'code_validity_day' => $row->code_validity_day, //授权码有效期天数,
                'auth_validity_day' => $row->auth_validity_day, //授权有效天数,
                'state' => $row->state, //授权状态,
                'subject_id' => $row->subject_id, //授权科目ID,
                'agent' => $row->agent, //中介,
                'agent_id' => $row->agent_id, //中介id,
                'create_time' => $row->create_time, //生成授权码时间,
                'active_time' => $row->active_time, //激活时间,
                'validity_end_time' => $row->validity_end_time, //有效期截止时间, unixtimestamp,
                'active_user_id' => $row->active_user_id, //激活用户,
                'data_ver' => $row->data_ver, //数据版本号,
                'data_mac' => $row->data_mac //数据摘要
            );
            $ret['records'][] = $record;
        }

        return $ret;
    }

    //更新授权记录
    public function updateAuthRecord($record){
        $conn = $this->load->database('tiku', TRUE);
        if(!$conn){
            $ret = array('retcode'=>200100, 'retmsg'=>'connect db failed');
            $this->log_err(array('ret'=>$ret, 'record'=>$record));
            return $ret;
        }

        $new = array('data_ver' => ($record['data_ver'] + 1));
        if(isset($record['state']) && $record['state'] > 0){
            $new['state'] = $record['state'];
        }
        if(isset($record['active_time']) && $record['active_time'] > 0){
            $new['active_time'] = $record['active_time'];
        }
        if(isset($record['validity_end_time']) && $record['validity_end_time'] > 0){
            $new['validity_end_time'] = $record['validity_end_time'];
        }
        if(isset($record['active_user_id']) && $record['active_user_id'] > 0){
            $new['active_user_id'] = $record['active_user_id'];
        }

        $conn->where('auth_id', $record['auth_id']);
        $conn->where('data_ver', $record['data_ver']);
        if(!$conn->update(self::TAB_NAME, $new)){
            if($conn->affected_rows() === 0){
                $ret = array('retcode'=>200104, 'retmsg'=> 'user not exist or cas err');
                $this->log_err(array('ret'=>$ret, 'record'=>$record, 'err'=>$conn->error()));
                return $ret;
            }else{
                $ret = array('retcode'=>200105, 'retmsg'=> 'update err');
                $this->log_err(array('ret'=>$ret, 'record'=>$record, 'err'=>$conn->error()));
                return $ret;
            }
        }

        return array('retcode'=>0, 'retmsg'=>'succ');
    }

}