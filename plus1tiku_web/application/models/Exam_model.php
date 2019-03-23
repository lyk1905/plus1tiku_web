<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Exam_model extends TK_Model {
    const STATE_AVAILABLE = 1; //有效的
    const STATE_ABANDONED = 2; //已废弃的

    const TAB_NAME = 't_exam';
    /**
        exam_id BIGINT UNSIGNED NOT NULL COMMENT '试卷id',
        exam_name VARCHAR(512) NOT NULL DEFAULT '' COMMENT '试卷名',
        exam_desc TEXT COMMENT '试卷描述',
        subject_id BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '科目id',
        exam_type INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'practice or examination',
        requir_auth INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '要求的权限'，
        view_id BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '试卷组织视图id，例如章节方式展示',
        exam_state INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '试卷状态',
        create_time DATETIME NOT NULL COMMENT '创建时间',
        update_time DATETIME NOT NULL COMMENT '更新时间',
        questions MEDIUMTEXT COMMENT '题目内容，json',
        `question_num` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT '题目数',
        data_ver INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '数据版本号',
        data_mac VARCHAR(256) NOT NULL DEFAULT '' COMMENT '数据摘要',
     */
    public function getExamInfo($exam_id){
        $conn = $this->load->database('tiku', TRUE);
        if(!$conn->conn_id){
            $ret = array('retcode'=>200100, 'retmsg'=>'connect db failed');
            $this->log_err(array('ret'=>$ret, 'exam_id'=>$exam_id));
            return $ret;
        }
        $qry = $conn->get_where(self::TAB_NAME, array('exam_id' => $exam_id));
        if(!$qry){
            $ret = array('retcode'=>200101, 'retmsg'=>'qry failed');
            $this->log_err(array('ret'=>$ret,
                'ret_num'=>$qry->num_rows(),
                'exam_id'=>$exam_id));
            return $ret;
        }else if($qry->num_rows() > 1){
            $ret = array('retcode'=>200002, 'retmsg'=>'qry result num err');
            $this->log_err(array('ret'=>$ret, 'ret_num'=>$qry->num_rows(), 'exam_id'=>$exam_id));
            return $ret;
        }else if($qry->num_rows() == 0){
            $ret = array('retcode'=>200003, 'retmsg'=>'qry result empty');
            $this->log_err(array('ret'=>$ret, 'ret_num'=>$qry->num_rows(), 'exam_id'=>$exam_id));
            return $ret;
        }
        $exam = $qry->row(0);
        $ret = array('retcode' => 0,
            'exam' => array(
                'exam_id' => $exam->exam_id,
                'exam_name' => $exam->exam_name,
                'exam_desc' => $exam->exam_desc, //试卷描述',
                'subject_id' => $exam->subject_id, //科目id,
                'exam_type' => $exam->exam_type,
                'requir_auth' => $exam->requir_auth, //要求的权限
                'view_id' => $exam->view_id, //试卷组织视图id，例如章节方式展示,
                'exam_state' => $exam->exam_state, //试卷状态,
                'create_time' => $exam->create_time, //生成时间,
                'update_time' => $exam->active_time, //更新时间,
                'questions' => json_decode(base64_decode($exam->questions)), //试题,
                'question_num' => $exam->question_num,
                'data_ver' => $exam->data_ver, //数据版本号,
                'data_mac' => $exam->data_mac //数据摘要
            ));
        return $ret;
    }

    //拉取试卷的基本信息
    public function getExamBaseInfoList($subject_id, $exam_type = 0, $exam_id = 0){
        $conn = $this->load->database('tiku', TRUE);
        if(!$conn->conn_id){
            $ret = array('retcode'=>200100, 'retmsg'=>'connect db failed');
            $this->log_err(array('ret'=>$ret, 'subject_id'=>$subject_id, 'exam_type'=>$exam_type));
            return $ret;
        }
        $condition = array('subject_id'=>$subject_id);
        if($exam_type > 0){
            $condition['exam_type'] = $exam_type;
        }
        if($exam_id > 0){
            $condition['exam_id'] = $exam_id;
        }
        $qry = $conn->get_where(self::TAB_NAME, $condition);
        if(!$qry){
            $ret = array('retcode'=>200101, 'retmsg'=>'qry failed');
            $this->log_err(array('ret'=>$ret,
                                'ret_num'=>$qry->num_rows(),
                                'subject_id'=>$subject_id,
                                'exam_type'=>$exam_type));
            return $ret;
        }

        $ret = array('retcode'=>0, 'retmsg'=>'msg', 'exams' => array());
        foreach($qry->result() as $row){
            $exam = array(
                'exam_id' => $row->exam_id,
                'exam_name' => $row->exam_name,
                'exam_desc' => $row->exam_desc, //试卷描述',
                'subject_id' => $row->subject_id, //科目id,
                'exam_type' => $row->exam_type,
                'requir_auth' => $row->requir_auth, //要求的权限
                'view_id' => $row->view_id, //试卷组织视图id，例如章节方式展示,
                'exam_state' => $row->exam_state, //试卷状态,
                'question_num' => $row->question_num,
                'create_time' => $row->create_time, //生成时间,
                'update_time' => $row->active_time, //更新时间,
                'questions' => $row->questions, //试题,
                'data_ver' => $row->data_ver, //数据版本号,
                'data_mac' => $row->data_mac //数据摘要
            );
            $ret['exams'][] = $exam;
        }

        return $ret;
    }

}