<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Statis_model extends TK_Model
{
    const TAB_NAME = "t_exam_statis";

    /**
    CREATE TABLE IF NOT EXISTS t_exam_statis(
    statis_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '刷题id',
    uid BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户id',
    subject_id BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '科目id',
    `auth_id` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT '授权记录'
    exam_id BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '试卷id',
    `question_num` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT '总题数',
    do_num BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '已刷题数',
    has_err_num BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '剩余错题数',
    last_err_num BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '累计错题数',
    practice_detail MEDIUMTEXT COMMENT '详细记录，json',
    create_time DATETIME NOT NULL COMMENT '创建时间',
    update_time DATETIME NOT NULL COMMENT '更新时间',
    data_ver INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '数据版本号',
    data_mac VARCHAR(256) NOT NULL DEFAULT '' COMMENT '数据摘要',
    PRIMARY KEY (`statis_id`),
    KEY `i_user_subject_exam` (`uid`, `subject_id`, `exam_id`)
    )ENGINE=InnoDB AUTO_INCREMENT=1915048 DEFAULT CHARSET=utf8 COMMENT '刷题记录表'
     */
    public function getStatisInfo($uid, $subject_id, $auth_id = 0, $exam_id = 0){
        $conn = $this->load->database('tiku', TRUE);
        if(!$conn->conn_id){
            $ret = array('retcode'=>200100, 'retmsg'=>'connect db failed');
            $this->log_err(array('ret'=>$ret, 'uid'=>$uid, 'subject_id'=>$subject_id, 'auth_id'=>$auth_id, 'exam_id'=>$exam_id));
            return $ret;
        }
        $condition = array('uid'=>$uid, 'subject_id'=>$subject_id);
        if($auth_id > 0){
            $condition['auth_id'] = $auth_id;
        }
        if($exam_id > 0){
            $condition['exam_id'] = $exam_id;
        }
        $qry = $conn->get_where(self::TAB_NAME, $condition);
        if(!$qry){
            $ret = array('retcode'=>200101, 'retmsg'=>'qry failed');
            $this->log_err(array('ret'=>$ret, 'ret_num'=>$qry->num_rows(),'uid'=>$uid, 'subject_id'=>$subject_id, 'auth_id'=>$auth_id, 'exam_id'=>$exam_id));
            return $ret;
        }

        $ret = array('retcode'=>0, 'retmsg'=>'msg', 'statises' => array());
        foreach($qry->result() as $row){
            $statis = array(
                'statis_id' => $row->statis_id,
                'uid' => $row->uid,
                'subject_id' => $row->subject_id, //授权类型',
                'auth_id' => $row->auth_id, //授权码有效期天数,
                'exam_id' => $row->exam_id, //授权有效天数,
                'question_num' => $row->question_num, //授权状态,
                'do_num' => $row->do_num, //授权状态,
                'has_err_num' => $row->has_err_num, //授权科目ID,
                'last_err_num' => $row->last_err_num, //激活时间,
                'practice_detail' => json_decode(base64_decode($row->practice_detail)),
                'create_time' => $row->create_time, //有效期截止时间, unixtimestamp,
                'update_time' => $row->update_time, //激活用户,
                'data_ver' => $row->data_ver, //数据版本号,
                'data_mac' => $row->data_mac //数据摘要
            );
            $ret['statises'][] = $statis;
        }

        return $ret;
    }

    public function initStatis($uid, $subject_id, $auth_id, $exam_id, $question_num){
        $conn = $this->load->database('tiku', TRUE);
        if(!$conn->conn_id){
            $ret = array('retcode'=>200100, 'retmsg'=>'connect db failed');
            $this->log_err(array('ret'=>$ret,'uid'=>$uid, 'subject_id'=>$subject_id, 'exam_id'=>$exam_id, 'auth_id'=>$auth_id));
            return $ret;
        }
        $now = date('Y-m-d h:i:s', time());
        $insrt_data = array('uid' => $uid,
            'subject_id' => $subject_id,
            'auth_id'=>$auth_id,
            'exam_id'=>$exam_id,
            'question_num' => $question_num,
            'create_time'=>$now,
            'update_time' => '0000-00-00 00:00:00',
            'data_ver'=>1);
        if(!$conn->insert(self::TAB_NAME, $insrt_data)){
            if($conn->error()['code'] == 1062){
                $ret = array('retcode'=>200000, 'retmsg'=>$conn->error()['message']);
                $this->log_err(array('insert_data'=>$insrt_data, 'err'=>$conn->error()));
                return $ret;
            }else{
                $ret = array('retcode'=>200001, 'retmsg'=>$conn->error()['message']);
                $this->log_err(array('insert_data'=>$insrt_data, 'err'=>$conn->error()));
                return $ret;
            }
        }
        $qry = $conn->get_where(self::TAB_NAME, array('uid' => $uid,
                                                        'subject_id' => $subject_id,
                                                        'auth_id'=>$auth_id,
                                                        'exam_id'=>$exam_id,
                                                        'question_num' => $question_num));
        if(!$qry){
            $ret = array('retcode'=>200101, 'retmsg'=>'qry failed');
            $this->log_err(array('ret'=>$ret, 'ret_num'=>$qry->num_rows(),'uid'=>$uid, 'subject_id'=>$subject_id, 'auth_id'=>$auth_id, 'exam_id'=>$exam_id));
            return $ret;
        }
        $row = $qry->row(0);
        $statis = array(
            'statis_id' => $row->statis_id,
            'uid' => $row->uid,
            'subject_id' => $row->subject_id, //授权类型',
            'auth_id' => $row->auth_id, //授权码有效期天数,
            'exam_id' => $row->exam_id, //授权有效天数,
            'question_num' => $row->question_num, //授权状态,
            'do_num' => $row->do_num, //授权状态,
            'has_err_num' => $row->has_err_num, //授权科目ID,
            'last_err_num' => $row->last_err_num, //激活时间,
            'practice_detail' => json_decode(base64_decode($row->practice_detail)),
            'create_time' => $row->create_time, //有效期截止时间, unixtimestamp,
            'update_time' => $row->update_time, //激活用户,
            'data_ver' => $row->data_ver, //数据版本号,
            'data_mac' => $row->data_mac //数据摘要
        );
        return array('retcode'=>0, 'retmsg'=>'succ', 'statis' => $statis);
    }
    /**
    statis_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '刷题id',
    uid BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户id',
    subject_id BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '科目id',
    `auth_id` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT '授权记录'
    exam_id BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '试卷id',
    `question_num` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT '总题数',
    do_num BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '已刷题数',
    has_err_num BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '剩余错题数',
    last_err_num BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '累计错题数',
    practice_detail MEDIUMTEXT COMMENT '详细记录，json',
    create_time DATETIME NOT NULL COMMENT '创建时间',
    update_time DATETIME NOT NULL COMMENT '更新时间',
    data_ver INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '数据版本号',
    data_mac VARCHAR(256) NOT NULL DEFAULT '' COMMENT '数据摘要',
     */
    private function makeUpdateStatis($statis, &$new_statis){
        $now = date('Y-m-d h:i:s', time());
        $new_statis = array('update_time'=>$now, 'data_ver' => ($statis['data_ver'] + 1));
        if(isset($statis['do_num']) && $statis['do_num'] > 0){
            $new_statis['do_num'] = $statis['do_num'];
        }
        if(isset($statis['has_err_num']) && $statis['has_err_num'] > 0){
            $new_statis['has_err_num'] = $statis['has_err_num'];
        }
        if(isset($statis['last_err_num']) && $statis['last_err_num'] > 0){
            $new_statis['last_err_num'] = $statis['last_err_num'];
        }
        if(isset($statis['practice_detail'])){
            $new_statis['practice_detail'] = base64_encode(json_encode($statis['practice_detail']));
        }
    }

    public function updateStatis($statis){
        $conn = $this->load->database('tiku', TRUE);
        if(!$conn->conn_id){
            $ret = array('retcode'=>200100, 'retmsg'=>'connect db failed');
            $this->log_err(array('ret'=>$ret,'$statis'=>$statis));
            return $ret;
        }

        $new_statis = array();
        $this->makeUpdateStatis($statis, $new_statis);
        $conn->update(self::TAB_NAME_STATIS,
            $new_statis,
            array(
                'statis_id' => $statis['statis_id'],
                'data_ver' => $statis['data_ver']));
        if(!$conn->affacted_rows()){
            $ret = array('retcode'=>200104, 'retmsg'=>'update statis cas or empty');
            $this->log_err(array('insert_data'=>$new_statis, 'err'=>$conn->error()));
            return $ret;
        }

        return array('retcode'=>0, 'retmsg'=>'succ');
    }
}