<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class ExamType {
    const EXAM_TYPE_PRACTICE = 1; //章节练习题
    const EXAM_TYPE_EXCS = 2; //模拟真题
    const EXAM_TYPE_GUSSE = 3; //绝密押题
}

class PracticeType {
    const PRCT_TYPE_TRAN = 1; //练习模式
    const PRCT_TYPE_EXCS = 2; //模拟考试
    const PRCT_TYPE_LST_ERR = 3; //历史错题
    const PRCT_TYPE_HAS_ERR = 4; //错误消灭练习
}

class Exam extends TK_Controller {
    public function __construct()
    {
        parent::__construct();
        $this->load->model('auth_model', 'auth_model');
        $this->load->model('subjects_model','subjects_model');
        $this->load->model('records_model','records_model');
        $this->load->model('statis_model','statis_model');
        $this->load->model('exam_model','exam_model');
    }

    /**
    {
        subject_id : "123456", //科目ID
        exam_type : 2, //试题类型： 1-章节练习；2-模拟真题；3-绝密押题
        practice_type: 2, //练习模式：1-训练模式；2-模拟考试；3-历史错题练习；4-消灭错题练习
        view_id : "34drfsdgku", //课程ID （练习模式选填）
        chapter_id : "dd87jld", //章id（练习模式选填）
        sub_chpt_id : "sdfd23fd", //节id（练习模式选填）
        exam_id : "asdasdf",
        practice_id : "asdfsdf", //某次练习的题目（例如错题，就只是其中一部分，以落地的为准）
    }
     */
    public function getExamList(){
        $uid = $this->getUid();
        $subject_id = $this->input->get_post("subject_id", true);
        $exam_type = $this->input->get_post("exam_type", true);
        $practice_type = $this->input->get_post("practice_type", true);
        $exam_id = $this->input->get_post("exam_id", true);
        if($subject_id == "" || $subject_id == 0){
            $this->ret_json(100001, '请选择科目');
            return ;
        }
        if($practice_type == "" || $practice_type == 0){
            $this->ret_json(100001, '请选择练习方式');
            return ;
        }
        if($exam_type == "" || $exam_type == 0){
            $this->ret_json(100001, '请选择练习方式');
            return ;
        }

        //step1: 判断用户的权限类型
        $auths = $this->auth_model->getAuthRecordList($uid, $subject_id);
        if(!isset($auths['retcode']) || $auths['retcode'] !=0){
            $this->ret_json(100001, '查询已购买课程失败');
            return ;
        }

        $auth_type = AuthType::GUEST_AUTH;
        $auth_id = 0;
        $now = date('Y-m-d h:i:s', time());
        foreach($auths['records'] as $record){
            if($record['state'] == AuthState::ACTIVED){
                if($now < $record['validity_end_time']){
                    $auth_type = $record['type'];
                    $auth_id = $record['auth_id'];
                    break;
                }
            }
        }

        //step2: 获取试卷列表
        $list = $this->exam_model->getExamBaseInfoList($subject_id, $exam_type, $exam_id);
        if(!isset($list['retcode']) || $list['retcode'] !=0){
            $this->ret_json(100001, '查询已购买课程失败');
            return ;
        }
        /**
        {
            exam_id : "dsefse32f2", //试卷ID
            exam_name : "胜多负少的试题", //试卷名称
            exam_num : 13, //题目数
            can_examine : 1,
            has_auth : 1, //是否有权限练习
            practice_id:
            prograss : { //考试进度
                excs_num : 3, //练习考题数
                exam_times : 4, //模拟考试次数
                first_excs_time : 1345809389, //首次练习时间
                last_excs_time : 1389509873, //上次练习时间
            }，
            statis : { //考试结果统计
                lst_wrng_num : 34, //剩余错题数
                acc_wrng_num : 54, //累计错题数
                estim_record : 455 //预估得分
            }
        },
         */
        $exam_list = array();
        foreach ($list['exams'] as $exam){
            $exam_id =  $exam['exam_id'];
            $exam_list[$exam_id] = array(
                'exam_id' => $exam['exam_id'],
                'exam_name' => $exam['exam_name'],
                'exam_num' => $exam['question_num'],
                'can_examine' => ($exam['exam_type'] != ExamType::EXAM_TYPE_PRACTICE),
                'has_auth' => $this->auth_model->hasAuth($auth_type, $exam['requir_auth'])
            );
        }

        //step3: 获取练习进度
        $statis = $this->statis_model->getStatisInfo($uid, $subject_id, $auth_id);
        if(!isset($statis['retcode']) || $statis['retcode'] !=0){
            $this->ret_json(100003, '查询课程练习进度失败');
            return ;
        }
        foreach($statis['statises'] as $item){
            $exam_id = $item['exam_id'];
            if(isset($exam_list[$exam_id])){
                /**
                'do_num' => $row->do_num, //授权状态,
                'has_err_num' => $row->has_err_num, //授权科目ID,
                'question_num' => $row->question_num, //中介,
                'do_num' => $row->do_num, //中介id,
                'has_err_num' => $row->has_err_num, //生成授权码时间,
                'last_err_num' => $row->last_err_num, //激活时间,
                'create_time' => $row->create_time, //有效期截止时间, unixtimestamp,
                'update_time' => $row->update_time, //激活用户,
                 */
                $exam_list[$exam_id]['prograss'] = array('excs_num' => $item['do_num'],
                    'first_excs_time' => $item['create_time'],
                    'last_excs_time' => $item['update_time']
                );
                $exam_list[$exam_id]['statis'] = array('lst_wrng_num' => $item['has_err_num'],
                    'acc_wrng_num' => $item['acc_wrng_num']);

            }
        }
        $filter_exams = $exam_list;
        if($practice_type == PracticeType::PRCT_TYPE_HAS_ERR
            || $practice_type == PracticeType::PRCT_TYPE_LST_ERR){
            $filter_exams = array();
            foreach ($exam_list as $exam){
                if($practice_type == PracticeType::PRCT_TYPE_HAS_ERR
                    && $exam['statis']['lst_wrng_num'] > 0){
                    $filter_exams[] = $exam;
                }
                if($practice_type == PracticeType::PRCT_TYPE_LST_ERR
                    && $exam['statis']['acc_wrng_num'] > 0){
                    $filter_exams[] = $exam;
                }
            }
        }

        $ret = array('subject_id' => $subject_id,
            'view_list' => array(
                'view_id' => 100,
                'chapter_list' => array(
                    'charpter_id' => 1000,
                    'sub_chpt_list' => array(
                        'sub_chpt_id' => 10000,
                        'exam_list' => $filter_exams
                    )
                )
            )
        );

        $this->ret_json(0, 'success', $ret);
        return ;
    }

    public function detail(){
        $uid = $this->getUid();
        $subject_id = $this->input->get_post("subject_id", true);
        $practice_type = $this->input->get_post("practice_type", true);
        $exam_id = $this->input->get_post("exam_id", true);
        if($subject_id == "" || $subject_id == 0){
            $this->ret_json(100001, '请选择科目');
            return ;
        }
        if($practice_type == "" || $practice_type == 0){
            $this->ret_json(100001, '请选择练习方式');
            return ;
        }
        if($exam_id == "" || $exam_id == 0){
            $this->ret_json(100001, '请选择具体试卷');
            return ;
        }
        //step1: 判断用户的权限类型
        $auths = $this->auth_model->getAuthRecordList($uid, $subject_id);
        if(!isset($auths['retcode']) || $auths['retcode'] !=0){
            $this->ret_json(100001, '查询已购买课程失败');
            return ;
        }

        $auth_type = AuthType::GUEST_AUTH;
        $auth_id = 0;
        foreach($auths['records'] as $record){
            if($record['state'] == AuthState::ACTIVED
               && time() < $record['validity_end_time']){
                $auth_type = $record['type'];
                $auth_id = $record['auth_id'];
                break;
            }
        }
        //step2: 获取试卷列表
        $exam = $this->exam_model->getExamInfo($exam_id);
        if(!isset($exam['retcode']) || $exam['retcode'] !=0){
            $this->ret_json(100001, '获取试卷信息失败');
            return ;
        }
        if(!$this->auth_model->hasAuth($auth_type, $exam['exam']['requir_auth'])){
            $this->ret_json(100001, '请激活当前科目');
            return ;
        }

        //step3：如果是错题集，需要根据练习记录过滤题目，只返回错误的题
        $all_question = $exam['exam']['questions'];
        $ret_question = $all_question;
        if($practice_type == PracticeType::PRCT_TYPE_LST_ERR
            || $practice_type == PracticeType::PRCT_TYPE_HAS_ERR){
            $statis = $this->statis_model->getStatisInfo($uid, $subject_id, $auth_id);
            if(!isset($statis['retcode']) || $statis['retcode'] !=0){
                $this->ret_json(100003, '查询课程练习进度失败');
                return ;
            }
            $ret_question['items'] = array();
            $this->filterErrQuestion($practice_type, $all_question['items'], $statis['statises']['practice_detail'], $ret_question['items']);
        }

        $this->ret_json(0, 'success', $ret_question);
        return ;
    }

    /**
     statis detail:
       {
            question_id : 'asdfasd',
            last_answer : 'A',
            err_times : 2,
            right_times : 2
       }
     */
    private function filterErrQuestion($practice_type, $questions, $statis, &$err_questions){
        $show_list = array();
        foreach($statis as $item){
            if($practice_type == PracticeType::PRCT_TYPE_HAS_ERR
                && (!isset($item['right_times']) || $item['right_times'] == 0)){
                $show_list[] = $item['question_id'];
            }
            if($practice_type == PracticeType::PRCT_TYPE_LST_ERR
                && (isset($item['err_times']) && $item['err_times'] > 0)){
                $show_list[] = $item['question_id'];
            }
        }
        foreach ($questions as $q){
            if(array_key_exists($q['question_id'], $show_list)){
                $err_questions[] = $q;
            }
        }
    }

    /**
        {
            courseid: subject_id, //课程id
            chapterid: exam_id, //试卷id
            practice_type:practice_type, //练习方式
            score: zong_right_score, //总得分
            usetime: examusetime, //使用时间
            rightnum: zong_right_numbers, //答对题目数
            doanswers: jsonstr(do_answers)
        }

        do_answers : [
            {
                question_id : "asdfa",
                answer : "asdfasd"
            },
        ]
     */
    public function submit()
    {
        $uid = $this->getUid();
        $subject_id = $this->input->get_post("courseid", true);
        $practice_type = $this->input->get_post("practice_type", true);
        $exam_id = $this->input->get_post("chapterid", true);
        $score = $this->input->get_post("score", true);
        $usetime = $this->input->get_post("usetime", true);
        $doanswers = $this->input->get_post("doanswers", true);
        if ($subject_id == "" || $subject_id == 0) {
            $this->ret_json(100001, '请选择科目');
            return;
        }
        if ($practice_type == "" || $practice_type == 0) {
            $this->ret_json(100001, '请选择练习方式');
            return;
        }
        if ($exam_id == "" || $exam_id == 0) {
            $this->ret_json(100001, '请选择具体试卷');
            return;
        }
        if ($doanswers == "") {
            $this->ret_json(100001, '提交答案失败，请重新提交重试');
            return;
        }
        $doanswers = json_decode($doanswers);
        if ($doanswers == "") {
            $this->ret_json(100001, '提交答案格式错误');
            return;
        }

        return $this->submitAnswer($uid, $subject_id, $practice_type, $exam_id, $score, $usetime, $doanswers);
    }

    private function submitAnswer($uid, $subject_id, $practice_type, $exam_id, $score, $usetime, $doanswers){
        //step1: 判断用户的权限类型
        $auths = $this->auth_model->getAuthRecordList($uid, $subject_id);
        if(!isset($auths['retcode']) || $auths['retcode'] !=0){
            $this->ret_json(100001, '查询已购买课程失败');
            return ;
        }

        $auth_type = AuthType::GUEST_AUTH;
        $auth_id = 0;
        $now = date('Y-m-d h:i:s', time());
        foreach($auths['records'] as $record){
            if($record['state'] == AuthState::ACTIVED){
                if($now < $record['validity_end_time']){
                    $auth_type = $record['type'];
                    $auth_id = $record['auth_id'];
                    break;
                }
            }
        }
        //step2: 获取试卷列表
        $exam = $this->exam_model->getExamInfo($exam_id);
        if(!isset($exam['retcode']) || $exam['retcode'] !=0){
            $this->ret_json(100001, '获取试卷信息失败');
            return ;
        }
        if(!$this->auth_model->hasAuth($auth_type, $exam['exam']['requir_auth'])){
            $this->ret_json(100001, '请先激活当前科目');
            return ;
        }

        //step3: 获取统计记录
        $statis = $this->statis_model->getStatisInfo($uid, $subject_id, $auth_id, $exam_id);
        if(!isset($statis['retcode']) || $statis['retcode'] !=0){
            $this->ret_json(100003, '查询课程练习进度失败');
            return ;
        }
        //如果还没有生成统计信息，先生成统计信息--第一次做题时需要先生成统计信息
        if(!count($statis['statises'])){
            $init = $this->statis_model->initStatis($uid, $subject_id, $auth_id, $exam_id, $exam['exam']['question_num']);
            if(!isset($init['retcode']) || $init['retcode'] !=0){
                $this->ret_json(100004, '初始化练习记录失败');
                return ;
            }

            //构造新的统计信息
            $statis = array('statises' => array());
            $statis['statises'][] = $init['statis'];
        }

        //step4: 如果是练习模式，在原来记录上追加练习记录
        $new_record = array();
        $new_statis = array();
        $ret_data = array();
        if($practice_type == PracticeType::PRCT_TYPE_TRAN
            || $practice_type == PracticeType::PRCT_TYPE_LST_ERR
            || $practice_type == PracticeType::PRCT_TYPE_HAS_ERR){
            $records = $this->records_model->getRecordOverLook($uid, $subject_id, $practice_type);
            if(!isset($records['retcode']) || $records['retcode'] !=0){
                $this->ret_json(100001, '获取练习记录失败');
                return ;
            }
            if(!count($records['records'])){ //new records
                $this->makeNewRecordsAndStatis($practice_type, $exam['exam'], $doanswers, $statis['statises'][0], $new_record, $new_statis);
                if($score > 0){
                    $new_record['scroll'] = $score;
                }
                if($usetime > 0){
                    $new_record['use_time'] = $usetime;
                }
                $res = $this->records_model->insertRecord($new_record, $new_statis);
                if(!isset($res['retcode']) || $res['retcode'] !=0){
                    $this->ret_json(100001, '更新练习记录失败');
                    return ;
                }
                $ret_data['practice_id'] = $res['practice_id'];
            }else{ //update record
                $old_record = $records['records'][0];
                $this->makeUpdateRecordsAndStatis($practice_type, $exam['exam'], $doanswers, $old_record, $statis['statises'][0], $new_record, $new_statis);
                if($score > 0){
                    $new_record['scroll'] = $score;
                }
                if($usetime > 0){
                    $new_record['use_time'] = $usetime;
                }
                $res = $this->records_model->updateRecord($new_record, $new_statis);
                if(!isset($res['retcode']) || $res['retcode'] !=0){
                    $this->ret_json(100001, '更新练习记录失败');
                    return ;
                }
                $ret_data['practice_id'] = $old_record['practice_id'];
            }
            /*
        }else if($practice_type == PracticeType::PRCT_TYPE_LST_ERR
                    || $practice_type == PracticeType::PRCT_TYPE_HAS_ERR){ //错误练习，要把正确的补上
            $this->makeNewRecordsAndStatis($practice_type, $exam['exam'], $doanswers, $statis['statises'][0], $new_record, $new_statis);
            if($score > 0){
                $new_record['scroll'] = $score;
            }
            if($usetime > 0){
                $new_record['use_time'] = $usetime;
            }
            $res = $this->records_model->insertRecord($new_record, $new_statis);
            if(!isset($res['retcode']) || $res['retcode'] !=0){
                $this->ret_json(100001, '更新练习记录失败');
                return ;
            }
            $ret_data['practice_id'] = $res['practice_id'];
            */
        }else{ //模拟考试，既要修改统计信息，也要增加一条模拟记录
            $this->makeNewRecordsAndStatis($practice_type, $exam['exam'], $doanswers, $statis['statises'][0], $new_record, $new_statis);
            if($score > 0){
                $new_record['scroll'] = $score;
            }
            if($usetime > 0){
                $new_record['use_time'] = $usetime;
            }
            $res = $this->records_model->insertRecord($new_record, $new_statis);
            if(!isset($res['retcode']) || $res['retcode'] !=0){
                $this->ret_json(100001, '更新练习记录失败');
                return ;
            }
            $ret_data['practice_id'] = $res['practice_id'];
        }

        $this->ret_json(0, 'success', $ret_data);
        return ;
    }

    /**
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
    'records' => base64_encode(json_encode($record['records'])),
     */
    /**
    statis detail:
    {
    question_id : 'asdfasd',
    last_answer : 'A',
    err_times : 2,
    right_times : 2
    }
     */
    private function makeNewRecordsAndStatis($practice_type, $exam, $doanswers, $statis, &$new_record, &$new_statis){
        //part1: make practice records
        $new_record['practice_type'] = $practice_type;
        $new_record['exam_id'] = $exam['exam_id'];
        $new_record['exam_name'] = $exam['exam_name'];
        $new_record['subject_name'] = $this->subjects_model->getNameBySubjectId($exam['subject_id']);
        $new_record['subject_id'] = $exam['subject_id'];
        $new_record['question_num'] = $exam['question_num'];

        $err_num = 0;
        $question_idx = array();
        foreach ($exam['questions']['items'] as $q){
            $question_idx[$q['question_id']] = $q;
        }
        /**
            {
                "subjectid":6799311,
                "courseid":101, //exam_id
                "subjectType":0, //题目类型
                "index":1,	//序号 试卷内递增，从1开始
                "score":1.0, //float 题目分数
                "title":"企业为增值税一般纳税人，2017 年应交各种税金为：增值税 350 万元，消费税 150万元，城市维护建设税35 万元，车辆购置税 10 万元，耕地占用税 5 万元，所得税 150万元。该企业当期“应交税费”科目余额为（ ）万元。",
                "question":"A.535\r\nB.545\r\nC.550\r\nD.685",
                "user_answer":"D",
                "answerNum":4,
                "analysis":"车辆购置税与耕地占用税都计入相关资产的成本中，不在“应交税费”中核算，其他的税费都\r\n在“应交税费”中核算，因此“应交税费”科目余额=350+150+35+150=685（万元）。",
                "typeId":0,
                "showType":"单项选择题"
            }
         */
        $records = array();
        $answer_idx = array();
        foreach($doanswers as $a){
            $question_id = $a['question_id'];
            $answer_idx[$question_id] = $a;
            if(isset($question_idx[$question_id])){
                $q = $question_idx[$question_id];
                $err_times = $a['answer'] != $q['rightAnswer'] ? 1 : 0;
                $right_time = 1 - $err_times;
                $err_num += $err_times;
                $records[] = array('question_id' => $question_id,
                            'exam_id' => $exam['exam_id'],
                            'question_type' => $q['question_type'],
                            'index' => $q['index'],
                            'score' => $q['score'],
                            'title' => $q['title'],
                            'question' => $q['question'],
                            'user_answer' => $a['answer'],
                            'right_answer' => $q['rightAnswer'],
                            'answerNum' => $q['answerNum'],
                            'analysis' => $q['analysis'],
                            'typeId' => $q['typeId'],
                            'showType' => $q['showType'],
                            'err_times' => $err_times,
                            'right_time' => $right_time);
            }
        }
        $new_record['do_num'] = count($records);
        $new_record['has_err_num'] = $err_num;
        $new_record['last_err_num'] = $err_num;
        $new_record['records'] = $records;

        //part2: update statis ret: do_num, has_err_num, last_err_num, practice_detail
        $statis_idx = array();
        if(isset($statis['practice_detail'])){
            foreach($statis['practice_detail'] as $s){
                $statis_idx[$s['question_id']] = $s;
            }
        }

        $do_num = $statis['do_num'];
        $has_err_num = $statis['has_err_num'];
        $last_err_num = $statis['last_err_num'];
        $new_statis = $statis;
        $new_statis['practice_detail'] = array();
        foreach($records as $r){
            $question_id = $r['$question_id'];
            if(array_key_exists($question_id, $statis_idx)){
                $err = ($r['user_answer'] != $r['right_answer']) ? 1 : 0;
                $right = 1 - $err;
                $t = $statis_idx[$question_id];
                if(!isset($t['right_times']) || $t['right_times'] == 0){
                    $has_err_num -= $right;
                    $t['right_times'] = 0;
                }
                if(!isset($t['err_times']) || $t['err_times'] == 0){
                    $last_err_num += $err;
                    $t['err_times'] = 0;
                }
                $new_statis['practice_detail'][] = array('question_id' => $question_id,
                    'last_answer' => $r['user_answer'],
                    'err_times' => $t['err_times'] + $err,
                    'right_times' => $t['right_times'] + $right);
            }else{
                $do_num += 1;
                $err = ($r['user_answer'] != $r['right_answer']) ? 1 : 0;
                $has_err_num += 1 - $err;
                $last_err_num += $err;
                $new_statis['practice_detail'][] = array('question_id' => $question_id,
                            'last_answer' => $r['user_answer'],
                            'err_times' => $err,
                            'right_times' => (1 - $err));
            }
        }
        foreach($statis_idx as $s){
            $question_id = $s['question_id'];
            if(!array_key_exists($question_id, $answer_idx)){
                $new_statis['practice_detail'][] = $s;
            }
        }
        $new_statis['do_num'] = $do_num;
        $new_statis['has_err_num'] = $has_err_num;
        $new_statis['last_err_num'] = $last_err_num;
    }

    private function makeUpdateRecordsAndStatis($practice_type, $exam, $doanswers, $old_record, $statis, &$new_record, &$new_statis){
        //part1: make practice records
        $new_record['practice_type'] = $practice_type;
        $new_record['exam_id'] = $exam['exam_id'];
        $new_record['exam_name'] = $exam['exam_name'];
        $new_record['subject_name'] = $this->subjects_model->getNameBySubjectId($exam['subject_id']);
        $new_record['subject_id'] = $exam['subject_id'];
        $new_record['question_num'] = $exam['question_num'];

        $err_num = 0;
        $question_idx = array();
        foreach ($exam['questions']['items'] as $q){
            $question_idx[$q['question_id']] = $q;
        }
        $old_record_idx = array();
        foreach($old_record[records] as $r){
            $old_record_idx[$r['question_id']] = $r;
        }

        /**
        {
        "subjectid":6799311,
        "courseid":101, //exam_id
        "subjectType":0, //题目类型
        "index":1,	//序号 试卷内递增，从1开始
        "score":1.0, //float 题目分数
        "title":"企业为增值税一般纳税人，2017 年应交各种税金为：增值税 350 万元，消费税 150万元，城市维护建设税35 万元，车辆购置税 10 万元，耕地占用税 5 万元，所得税 150万元。该企业当期“应交税费”科目余额为（ ）万元。",
        "question":"A.535\r\nB.545\r\nC.550\r\nD.685",
        "user_answer":"D",
        "answerNum":4,
        "analysis":"车辆购置税与耕地占用税都计入相关资产的成本中，不在“应交税费”中核算，其他的税费都\r\n在“应交税费”中核算，因此“应交税费”科目余额=350+150+35+150=685（万元）。",
        "typeId":0,
        "showType":"单项选择题"
        }
         */
        $records = array();
        $answer_idx = array();
        $do_num = $old_record['do_num'];
        $has_err_num = $old_record['has_err_num'];
        $last_err_num = $old_record['last_err_num'];
        foreach($doanswers as $a){
            $question_id = $a['question_id'];
            $answer_idx[$question_id] = $a;
            if(array_key_exists($question_id, $old_record_idx)){
                $t = $old_record_idx[$question_id];
                $err = ($a['answer'] != $t['right_answer']) ? 1 : 0;
                $right = 1- $err;
                if(!isset($t['right_times']) || $t['right_times'] == 0){
                    $has_err_num -= $right;
                    $t['right_times'] = 0;
                }
                if(!isset($t['err_times']) || $t['err_times'] == 0){
                    $last_err_num += $err;
                    $t['err_times'] = 0;
                }
                $t['right_times'] += $right;
                $t['err_times'] += $err;
                $t['user_answer'] = $a['answer'];
                $records[] = $t;
            }else if(isset($question_idx[$question_id])){
                $do_num ++;
                $q = $question_idx[$question_id];
                $err_times = $a['answer'] != $q['rightAnswer'] ? 1 : 0;
                $right_time = 1 - $err_times;
                $err_num += $err_times;
                $records[] = array('question_id' => $question_id,
                    'exam_id' => $exam['exam_id'],
                    'question_type' => $q['question_type'],
                    'index' => $q['index'],
                    'score' => $q['score'],
                    'title' => $q['title'],
                    'question' => $q['question'],
                    'user_answer' => $a['answer'],
                    'right_answer' => $q['rightAnswer'],
                    'answerNum' => $q['answerNum'],
                    'analysis' => $q['analysis'],
                    'typeId' => $q['typeId'],
                    'showType' => $q['showType'],
                    'err_times' => $err_times,
                    'right_time' => $right_time);
            }
        }
        foreach ($old_record_idx as $o){
            $question_id = $o['question_id'];
            if(!array_key_exists($question_id, $answer_idx)){
                $new_statis['practice_detail'][] = $o;
            }
        }
        $new_record['do_num'] = $do_num;
        $new_record['has_err_num'] = $has_err_num;
        $new_record['last_err_num'] = $last_err_num;

        //part2: update statis ret: do_num, has_err_num, last_err_num, practice_detail
        $statis_idx = array();
        if(isset($statis['practice_detail'])){
            foreach($statis['practice_detail'] as $s){
                $statis_idx[$s['question_id']] = $s;
            }
        }

        $do_num = $statis['do_num'];
        $has_err_num = $statis['has_err_num'];
        $last_err_num = $statis['last_err_num'];
        $new_statis = $statis;
        $new_statis['practice_detail'] = array();
        foreach($records as $r){
            $question_id = $r['$question_id'];
            if(array_key_exists($question_id, $statis_idx)){
                $err = ($r['user_answer'] != $r['right_answer']) ? 1 : 0;
                $right = 1 - $err;
                $t = $statis_idx[$question_id];
                if(!isset($t['right_times']) || $t['right_times'] == 0){
                    $has_err_num -= $right;
                    $t['right_times'] = 0;
                }
                if(!isset($t['err_times']) || $t['err_times'] == 0){
                    $last_err_num += $err;
                    $t['err_times'] = 0;
                }
                $new_statis['practice_detail'][] = array('question_id' => $question_id,
                    'last_answer' => $r['user_answer'],
                    'err_times' => $t['err_times'] + $err,
                    'right_times' => $t['right_times'] + $right);
            }else{
                $do_num += 1;
                $err = ($r['user_answer'] != $r['right_answer']) ? 1 : 0;
                $has_err_num += 1 - $err;
                $last_err_num += $err;
                $new_statis['practice_detail'][] = array('question_id' => $question_id,
                    'last_answer' => $r['user_answer'],
                    'err_times' => $err,
                    'right_times' => (1 - $err));
            }
        }
        foreach($statis_idx as $s){
            $question_id = $s['question_id'];
            if(!array_key_exists($question_id, $answer_idx)){
                $new_statis['practice_detail'][] = $s;
            }
        }
        $new_statis['do_num'] = $do_num;
        $new_statis['has_err_num'] = $has_err_num;
        $new_statis['last_err_num'] = $last_err_num;
    }

    //注，第一次练习的时候，是没有记录的要先建记录
    public function submitQuestion(){
        $practice_type = PracticeType::PRCT_TYPE_TRAN;
        $uid = $this->getUid();
        $subject_id = $this->input->get_post("courseid", true);
        $exam_id = $this->input->get_post("chapterid", true);
        $question_id = $this->input->get_post("question_id", true);
        $answer = $this->input->get_post("answer", true);
        $correct = $this->input->get_post("correct", true);
        if($subject_id == "" || $subject_id == 0){
            $this->ret_json(100001, '请选择科目');
            return ;
        }
        if($question_id == "" || $question_id == 0){
            $this->ret_json(100001, '提交内容缺少题目信息');
            return ;
        }
        if($exam_id == "" || $exam_id == 0){
            $this->ret_json(100001, '请选择具体试卷');
            return ;
        }
        if($answer == ""){
            $this->ret_json(100001, '提交内容缺少选择的答案');
            return ;
        }
        $answer_list = array();
        $answer_list[] = array('question_id' => $question_id, 'answer' => $answer);
        return $this->submitAnswer($uid, $subject_id, $practice_type, $exam_id, 0, 0, $answer_list);
    }

    /**
        {
            subject_id : "asdfsad",
            exam_id : "asdsss",
            practice_id : "asdfa"
        }
        {
            "resultcode" : 0,
            "resultmsg":"success",
            "usetime" : 343,
            "score" : 233,
            "answers":[
                {
                "subjectid":6799311,
                "courseid":101, //exam_id
                "subjectType":0, //题目类型
                "index":1,	//序号 试卷内递增，从1开始
                "score":1.0, //float 题目分数
                "title":"企业为增值税一般纳税人，2017 年应交各种税金为：增值税 350 万元，消费税 150万元，城市维护建设税35 万元，车辆购置税 10 万元，耕地占用税 5 万元，所得税 150万元。该企业当期“应交税费”科目余额为（ ）万元。",
                "question":"A.535\r\nB.545\r\nC.550\r\nD.685",
                "user_answer":"D",
                "answerNum":4,
                "analysis":"车辆购置税与耕地占用税都计入相关资产的成本中，不在“应交税费”中核算，其他的税费都\r\n在“应交税费”中核算，因此“应交税费”科目余额=350+150+35+150=685（万元）。",
                "typeId":0,
                "showType":"单项选择题"
                },
     *      ]
     *  }
     */
    public function getUserAnswer(){
        $uid = $this->getUid();
        $subject_id = $this->input->get_post("courseid", true);
        $practice_id = $this->input->get_post("practice_id", true);
        if($subject_id == "" || $subject_id == 0){
            $this->ret_json(100001, '请选择科目');
            return ;
        }
        if($practice_id == "" || $practice_id == 0){
            $this->ret_json(100001, '未填写练习记录id');
            return ;
        }
        $records = $this->records_model->getRecord($uid, $subject_id, $practice_id);
        if(!isset($records['retcode']) || $records['retcode'] !=0){
            $this->ret_json(100001, '获取练习记录失败');
            return ;
        }
        $r = $records['records'];
        $ret_data = array('usetime' => $r['use_time'],
                        'score' => $r['score'],
                        'answers' => $r['records']);
        $this->ret_json(0, 'success', $ret_data);
        return ;
    }
}