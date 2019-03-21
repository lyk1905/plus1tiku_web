<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class SubjectsState {
    const INIT = 1;
    const ACTIVED = 2;
    const EXPIRED = 3; //已过期
}

class Subjects extends TK_Controller {
    public function __construct()
    {
        parent::__construct();
        $this->load->model('auth_model', 'auth_model');
        $this->load->model('subjects_model','subjects_mode');
        $this->load->model('records_model','records_model');
        $this->load->model('statis_model','statis_model');
    }
    //获取所有已激活科目
    public function list1(){
        $uid = $this->getUid();
        $auths = $this->auth_model->getAuthRecordList($uid);
        if(!isset($auths['retcode']) || $auths['retcode'] !=0){
            $this->ret_json(100001, '查询已购买课程失败');
            return ;
        }

        $data = array('list' => array());
        $now = date('Y-m-d h:i:s', time());
        foreach($auths['records'] as $record){
            if($record->state == SubjectsState::ACTIVED
                && $now < $record->validity_end_time)
                $subject_id = $record->subject_id;
                $subject_name = $this->subject_model->getNameBySubjectId($subject_id);
                $item = array(
                    'subject_id' => $subject_id,
                    'subject_name' => $subject_name,
                    'validate_time' => $record->validity_end_time
                );

                array_push($data['list'], $item);
        }

        $this->ret_json(0, 'succ', $data);
        return;
    }

    //获取所有可购买科目
    public function all(){

    }

    //判断科目是否已激活
    public function has_actived(){
        $uid = $this->getUid();
        $subject_id = $this->input->get_post("subject_id", true);
        if($subject_id === "" || $subject_id == 0){
            $this->ret_json(100001, '请选择科目');
            return ;
        }
        $auths = $this->auth_model->getAuthRecordList($uid, $subject_id);
        if(!isset($auths['retcode']) || $auths['retcode'] !=0){
            $this->ret_json(100001, '查询已购买课程失败');
            return ;
        }

        $isActived = False;
        $isExpired  = False;
        $now = date('Y-m-d h:i:s', time());
        foreach($auths['records'] as $record){
            if($record->state == SubjectsState::ACTIVED){
                if($now < $record->validity_end_time){
                    $isActived = True;
                    break;
                }else{
                    $isExpired = True;
                }
            }
        }
        if($isActived){
            $this->ret_json(0, '已激活');
            return;
        }else if($isExpired){
            $this->ret_json(2, '已过期');
            return;
        }else{
            $this->ret_json(1, '未激活');
            return;
        }

        return;
    }

    //激活科目
    public function active(){
        $uid = $this->getUid();
        $subject_id = $this->input->get_post("subject_id", true);
        $code = $this->input->get_post("auth_code", true);
        if($subject_id === "" || $subject_id == 0){
            $this->ret_json(100001, '请选择科目');
            return ;
        }
        if($code === "" || $code == 0){
            $this->ret_json(100001, '请填写激活码');
            return ;
        }
        $auth = $this->auth_model->getAuthByCode($code);
        if(!isset($auth['retcode']) || $auth['retcode'] != 0){
            $this->ret_json(100002, '获取激活码信息失败');
            return ;
        }

        //重入判定
        $now = date('Y-m-d h:i:s', time());
        $record = $auth['record'];
        if($record->state == SubjectsState::ACTIVED
           && $record->subject_id == $subject_id
           && $record->active_user_id == $uid){
            if($record->validity_end_time > $now){
                $this->ret_json(0, 'success');
                return ;
            }else {
                $this->ret_json(2, '激活码已使用，科目已过期');
                return ;
            }
        }

        //是否允许用来激活本科目，以下case不允许
        if($record->subject_id != $subject_id){//case1: 科目不匹配
            $this->ret_json(100003, '其他科目的激活码不能用于激活本科目，请输入正确的激活码或更换科目');
            return ;
        }else if($record->active_user_id != 0
            || $record->state != SubjectsState::INIT){ //case2：已经被其他账号激活
            $this->ret_json(100004, '激活码已被使用');
            return ;
        }else if(time() - strtotime($record->create_time) > $record->code_validity_day * 86400){
            $this->ret_json(100005, '激活码已过期');
            return ;
        }

        //那就激活它
        $record->active_user_id = $uid;
        $record->state = SubjectsState::ACTIVED;
        $record->active_time = $now;
        $today = date('Y-m-d', time()).' 23:59:59';
        $record->validity_end_time = date('Y-m-d', strtotime($today) + $record->auth_validity_day);
        $res = $this->auth_model->updateAuthRecord($record);
        if(!isset($res['retcode'])){
            $this->ret_json(100006, '激活失败，请刷新重试');
            return ;
        }else if(isset($res['retcode']) && $res['retcode'] != 0){
            if(200104 == $res['retcode']){
                $this->ret_json(100007, '激活失败，请稍后刷新重试');
                return ;
            }else{
                $this->ret_json(100008, '激活失败');
                return ;
            }
        }

        $this->ret_json(0, 'success');
        return ;
    }

    //获取科目下所有试卷的练习进度
    public function practiceprocess(){
        $uid = $this->getUid();
        $subject_id = $this->input->get_post("subject_id", true);
        $practice_type = $this->input->get_post("type", true);
        if($subject_id === "" || $subject_id == 0){
            $this->ret_json(100001, '请选择科目');
            return ;
        }
        if($practice_type === "" || $practice_type == 0){
            $this->ret_json(100001, '请选择练习方式');
            return ;
        }
        $records = $this->records_model->getRecordOverLook($uid, $subject_id, $practice_type);
        if(!isset($records['retcode']) || $records['retcode'] != 0){
            $this->ret_json(100002, '获取练习记录失败');
            return ;
        }

        /**
            {
                practice_id : "asdfasdfa", //practice_id
                exam_id : "asdfasdfas", //exam_id
                exam_name : "基础打得", //exam_name
                question_num :143, //question_num
                do_num : 13, //do_num
                right_num :4, //has_err_num
                last_practice_time : '2019-02-17 23:31:11', //last_practice_time
                last_question : 3,
                score : 343 //得分 //scroll
            }
         */
        $exam_total_num = 0;
        $process = array('subject_id' => $subject_id,
            'subject_name' => $this->subjects_model->getNameBySubjectId($subject_id),
            'list' => array());
        foreach ($records['records'] as $recd){
            $item = array(
                'practice_id' => $recd['practice_id'],
                'exam_id' => $recd['exam_id'],
                'exam_name' => $recd['exam_name'],
                'question_num' => $recd['question_num'],
                'do_num' => $recd['do_num'],
                'right_num' => $recd['do_num'] > $recd['has_err_num'] ? $recd['do_num'] - $recd['has_err_num'] : 0,
                'last_practice_time' => $recd['last_practice_time'],
                'score' => $recd['score']
            );
            $exam_total_num += $item['question_num'];
            $process['list'][] = $item;
        }

        $process['exam_total_num'] = $exam_total_num;
        $this->ret_json(0, 'success', $process);
        return ;
    }

    //获取科目的统计信息
    public function subjectStatis(){
        $uid = $this->getUid();
        $subject_id = $this->input->get_post("subject_id", true);
        if($subject_id === "" || $subject_id == 0){
            $this->ret_json(100001, '请选择科目');
            return ;
        }
        //step1: 判断用户的权限类型
        $auths = $this->auth_model->getAuthRecordList($uid, $subject_id);
        if(!isset($auths['retcode']) || $auths['retcode'] !=0){
            $this->ret_json(100001, '查询已购买课程失败');
            return ;
        }

        $auth_type = AuthType::GUEST_AUTH;
        $now = date('Y-m-d h:i:s', time());
        foreach($auths['records'] as $record){
            if($record->state == SubjectsState::ACTIVED){
                if($now < $record['validity_end_time']){
                    $auth_type = $record['type'];
                    $auth_id = $record['auth_id'];
                    break;
                }
            }
        }
        $statis = $this->statis_model->getStatisInfo($uid, $subject_id, $auth_id);
        if(!isset($statis['retcode']) || $statis['retcode'] != 0){
            $this->ret_json(100002, '获取练习记录失败');
            return ;
        }
        $ret = array('qestion_num' => 0,
                     'answer_num' => 0,
                     'right_num' => 0,
                     'wrong_num' => 0);
        foreach ($statis['statises'] as $item){
            $ret['qestion_num'] += $item['question_num'];
            $ret['answer_num'] += $item['do_num'];
            $ret['wrong_num'] += $item['has_err_num'];
            $ret['right_num'] += $item['do_num'] > $item['has_err_num'] ? $item['do_num'] - $item['has_err_num'] : 0;
        }
        $ret['right_rate'] = ($ret['answer_num'] > 0) ? (100 * $ret['right_num']/$ret['answer_num']) : 0;
        $ret['answer_rate'] = ($ret['qestion_num'] > 0) ? (100 * $ret['answer_num']/$ret['qestion_num']) : 0;
        $this->ret_json(0, 'success', $ret);
        return ;
    }

}