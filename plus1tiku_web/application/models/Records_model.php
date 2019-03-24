<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Records_model extends TK_Model {
    const TAB_NAME = 't_exam_do_record';
    const TAB_NAME_STATIS = 't_exam_statis';
    /**
    CREATE TABLE IF NOT EXISTS t_exam_do_record(
        practice_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '刷题id',
        practice_type INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '刷题类型',
        uid BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户id',
        exam_id BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '试卷id',
        exam_name VARCHAR(512) NOT NULL DEFAULT '' COMMENT '试卷名',
        subject_id BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '科目id',
        subject_name VARCHAR(128) NOT NULL DEFAULT '' COMMENT '科目名称',
        question_num BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '题目数',
        do_num BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '题目数',
       `use_time` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT '练习所用时间',
        has_err_num BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '题目数',
        last_err_num BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '题目数',
        scroll BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '题目数',
        create_time DATETIME NOT NULL COMMENT '创建时间',
        update_time DATETIME NOT NULL COMMENT '更新时间',
        last_practice_time DATETIME NOT NULL COMMENT '最近刷题时间',
        records MEDIUMTEXT COMMENT '记录，json',
        data_ver INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '数据版本号',
        data_mac VARCHAR(256) NOT NULL DEFAULT '' COMMENT '数据摘要',
        PRIMARY KEY (`practice_id`),
        KEY `i_user_subject_type` (`uid`, `subject_id`, `practice_type`)
    )ENGINE=InnoDB AUTO_INCREMENT=1915048 DEFAULT CHARSET=utf8 COMMENT '刷题记录表'
     */
    public function getRecordOverLook($uid, $subject_id, $practice_type = 0, $practice_id = 0){
        $conn = $this->load->database('tiku', TRUE);
        if(!$conn->conn_id){
            $ret = array('retcode'=>200100, 'retmsg'=>'connect db failed');
            $this->log_err(array('ret'=>$ret, 'uid'=>$uid, 'subject_id'=>$subject_id, 'practice_type'=>$practice_type));
            return $ret;
        }
        $condition = array('uid'=>$uid, 'subject_id'=>$subject_id);
        if($practice_type > 0){
            $condition['practice_type'] = $practice_type;
        }
        if($practice_id > 0){
            $condition['practice_id'] = $practice_id;
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
                'practice_id' => $row->practice_id,
                'practice_type' => $row->practice_type,
                'uid' => $row->uid, //授权类型',
                'exam_id' => $row->exam_id, //授权码有效期天数,
                'exam_name' => $row->exam_name, //授权有效天数,
                'subject_id' => $row->subject_id, //授权状态,
                'subject_name' => $row->subject_name, //授权科目ID,
                'question_num' => $row->question_num, //中介,
                'do_num' => $row->do_num, //中介id,
                'has_err_num' => $row->has_err_num, //生成授权码时间,
                'last_err_num' => $row->last_err_num, //激活时间,
                'scroll' => $row->scroll, //有效期截止时间, unixtimestamp,
                'use_time' => $row->use_time,
                'records' => json_decode(base64_decode($row->records)),
                'create_time' => $row->create_time, //激活用户,
                'update_time' => $row->update_time, //激活用户,
                'last_practice_time' => $row->last_practice_time, //激活用户,
                'data_ver' => $row->data_ver, //数据版本号,
                'data_mac' => $row->data_mac //数据摘要
            );
            $ret['records'][] = $record;
        }

        return $ret;
    }

    public function getRecord($uid, $subject_id, $record_id){
        $conn = $this->load->database('tiku', TRUE);
        if(!$conn->conn_id){
            $ret = array('retcode'=>200100, 'retmsg'=>'connect db failed');
            $this->log_err(array('ret'=>$ret, 'uid'=>$uid, 'subject_id'=>$subject_id, 'record_id'=>$record_id));
            return $ret;
        }
        $qry = $conn->get_where(self::TAB_NAME,
                                array('record_id' => $record_id,
                                      'uid' => $uid,
                                      'subject_id' => $subject_id));
        if(!$qry){
            $ret = array('retcode'=>200101, 'retmsg'=>'qry failed');
            $this->log_err(array('ret'=>$ret, 'ret_num'=>$qry->num_rows(),'uid'=>$uid, 'subject_id'=>$subject_id, 'record_id'=>$record_id));
            return $ret;
        }else if($qry->num_rows() > 1){
            $ret = array('retcode'=>200102, 'retmsg'=>'qry result num err');
            $this->log_err(array('ret'=>$ret, 'ret_num'=>$qry->num_rows(),'uid'=>$uid, 'subject_id'=>$subject_id, 'record_id'=>$record_id));
            return $ret;
        }else if($qry->num_rows() == 0){
            $ret = array('retcode'=>200103, 'retmsg'=>'qry result empty');
            $this->log_err(array('ret'=>$ret, 'ret_num'=>$qry->num_rows(),'uid'=>$uid, 'subject_id'=>$subject_id, 'record_id'=>$record_id));
            return $ret;
        }

        $row = $qry->row(0);
        $ret = array('retcode'=>0,
                     'retmsg'=>'success',
                     'records' => array(
                         'practice_id' => $row->practice_id,
                         'practice_type' => $row->practice_type,
                         'uid' => $row->uid, //授权类型',
                         'exam_id' => $row->exam_id, //授权码有效期天数,
                         'exam_name' => $row->exam_name, //授权有效天数,
                         'subject_id' => $row->subject_id, //授权状态,
                         'subject_name' => $row->subject_name, //授权科目ID,
                         'question_num' => $row->question_num, //中介,
                         'do_num' => $row->do_num, //中介id,
                         'has_err_num' => $row->has_err_num, //生成授权码时间,
                         'last_err_num' => $row->last_err_num, //激活时间,
                         'scroll' => $row->scroll, //有效期截止时间, unixtimestamp,
                         'use_time' => $row->use_time,
                         'records' => json_decode(base64_decode($row->records)),
                         'create_time' => $row->create_time, //激活用户,
                         'update_time' => $row->update_time, //激活用户,
                         'last_practice_time' => $row->last_practice_time, //激活用户,
                         'data_ver' => $row->data_ver, //数据版本号,
                         'data_mac' => $row->data_mac //数据摘要
            ));

        return $ret;
    }

    private  function makeInsertRecord($record, &$insert_data){
        $now = date('Y-m-d h:i:s', time());
        $insert_data = array('uid' => $record['uid'],
            'practice_type' => $record['practice_type'],
            'exam_id' => $record['exam_id'],
            'exam_name' => $record['exam_name'],
            'subject_id' => $record['subject_id'],
            'subject_name' => $record['subject_name'],
            'question_num' => $record['question_num'],
            'do_num' => $record['do_num'],
            'has_err_num' => $record['has_err_num'],
            'last_err_num' => $record['last_err_num'],
            'scroll' => $record['scroll'],
            'use_time' => $record['use_time'],
            'create_time' => $now,
            'update_time' => '0000-00-00 00:00:00',
            'last_practice_time' => $now,
            'records' => base64_encode(json_encode($record['records'])),
            'data_ver' => 1);
    }

    private function makeUpdateRecord($record, &$new_record){
        $now = date('Y-m-d h:i:s', time());

        $new_record = array('update_time'=>$now, 'data_ver' => ($record['data_ver'] + 1));
        if(isset($record['do_num']) && $record['do_num'] > 0){
            $new_record['do_num'] = $record['do_num'];
        }
        if(isset($record['has_err_num']) && $record['has_err_num'] > 0){
            $new_record['has_err_num'] = $record['has_err_num'];
        }
        if(isset($record['last_err_num']) && $record['last_err_num'] > 0){
            $new_record['last_err_num'] = $record['last_err_num'];
        }
        if(isset($record['scroll']) && $record['scroll'] > 0){
            $new_record['scroll'] = $record['scroll'];
        }
        if(isset($record['last_practice_time']) && $record['last_practice_time'] > 0){
            $new_record['last_practice_time'] = $record['last_practice_time'];
        }
        if(isset($record['use_time']) && $record['use_time'] > 0){
            $new_record['use_time'] = $record['use_time'];
        }
        if(isset($record['records'])){
            $new_record['records'] = base64_encode(json_encode($record['records']));
        }
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

    public function insertRecord($record, $statis){
        $conn = $this->load->database('tiku', TRUE);
        if(!$conn->conn_id){
            $ret = array('retcode'=>200100, 'retmsg'=>'connect db failed');
            $this->log_err(array('ret'=>$ret,'record'=>$record));
            return $ret;
        }

        $insrt_data = array();
        $new_statis = array();
        $this->makeInsertRecord($record, $insrt_data);
        $this->makeUpdateStatis($statis, $new_statis);

        $conn->trans_begin();
        $conn->insert(self::TAB_NAME, $insrt_data);
        $conn->update(self::TAB_NAME_STATIS,
                        $new_statis,
                        array(
                            'statis_id' => $statis['statis_id'],
                            'data_ver' => $statis['data_ver']));
        if(!$conn->affected_rows()){
            $conn->trans_rollback();
            $ret = array('retcode'=>200104, 'retmsg'=>'update statis cas or empty');
            $this->log_err(array('insert_data'=>$new_statis, 'err'=>$conn->error()));
            return $ret;
        }
        $qry = $conn->get_where(self::TAB_NAME,
                                    array('uid' => $record['uid'],
                                          'subject_id' => $record['subject_id'],
                                          'practice_type' => $record['practice_type']));
        if(!$qry){
            $conn->trans_rollback();
            $ret = array('retcode'=>200104, 'retmsg'=>'query new record failed');
            $this->log_err(array('insert_data'=>$new_statis, 'err'=>$conn->error()));
            return $ret;
        }
        $practice_id = $qry->row(0)['practice_id'];
        if ($conn->trans_status() === FALSE){
            $conn->trans_rollback();
            $ret = array('retcode'=>200105, 'retmsg'=>'insert failed');
            $this->log_err(array('insert_data'=>$insrt_data, 'err'=>$conn->error()));
            return $ret;
        }else{
            $conn->trans_commit();
        }

        return array('retcode'=>0, 'retmsg'=>'succ', 'practice_id' => $practice_id);
    }

    //每次更新记录都要更新统计结果
    /**
     @brief do_record允许更新字段
    practice_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '刷题id',

    do_num BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '题目数',
    has_err_num BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '题目数',
    last_err_num BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '题目数',
    scroll BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '题目数',
    update_time DATETIME NOT NULL COMMENT '更新时间',
    last_practice_time DATETIME NOT NULL COMMENT '最近刷题时间',
    records MEDIUMTEXT COMMENT '记录，json',
    data_ver INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '数据版本号',
     */
    /**
    @brief do_record允许更新字段

    statis_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '刷题id',

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
    public function updateRecord($record, $statis){
        $conn = $this->load->database('tiku', TRUE);
        if(!$conn->conn_id){
            $ret = array('retcode'=>200100, 'retmsg'=>'connect db failed');
            $this->log_err(array('ret'=>$ret,'record'=>$record));
            return $ret;
        }

        $new_records = array();
        $new_statis = array();
        $this->makeUpdateRecord($record, $new_records);
        $this->makeUpdateStatis($statis, $new_statis);

        $conn->trans_begin();
        $conn->update(self::TAB_NAME, $new_records,
                array(
                    'practice_id' => $record['practice_id'],
                    'data_ver' => $record['data_ver']));
        $conn->update(self::TAB_NAME_STATIS,
            $new_statis,
            array(
                'statis_id' => $statis['statis_id'],
                'data_ver' => $statis['data_ver']));
        if(!$conn->affected_rows()){
            $conn->trans_rollback();
            $ret = array('retcode'=>200104, 'retmsg'=>'update statis cas or empty');
            $this->log_err(array('insert_data'=>$new_statis, 'err'=>$conn->error()));
            return $ret;
        }
        $conn->trans_complete();
        if ($conn->trans_status() === FALSE){
            $conn->trans_rollback();
            $ret = array('retcode'=>200105, 'retmsg'=>'insert failed');
            $this->log_err(array('insert_data'=>$new_records, 'err'=>$conn->error()));
            return $ret;
        }else{
            $conn->trans_commit();
        }

        return array('retcode'=>0, 'retmsg'=>'succ');
    }
}