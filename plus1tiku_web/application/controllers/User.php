<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class User extends TK_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->model('user_model', 'user_model');
        $this->load->model('subjects_model','subjects_mode');
    }

    /** METHOD: POST
    {
    phone: "13823490934", //一个手机只能绑一个账号
    email: "xxxx@mail.com", //邮箱
    psword : "" //密码[a-z|A-Z|0-9|@|#|$|_|-|+|(|)]{6,18}
    }
    {
    retcode: 0,
    retmsg : "succ" //注册成功也需要返回
    }
     */
    public function regist(){
        $phone = $this->input->get_post("phone", true);
        $email = $this->input->get_post("email", true);
        $psword = $this->input->get_post("psword", true);

        if($phone === ""){
            $this->ret_json(100001, '电话号码未填写');
            return ;
        }
        if($email === ""){
            $this->ret_json(100001, '电子邮件未填写');
            return ;
        }
        if($psword === ""){
            $this->ret_json(100001, '密码未填写');
            return ;
        }

        $res = $this->user_model->insertUser($phone, $email, $psword);
        if(!isset($res['retcode'])){
            $this->ret_json(100002, '注册失败，请刷新重试');
            return ;
        }else if(isset($res['retcode']) && $res['retcode'] != 0){
            if(200000 == $res['retcode']){
                $this->ret_json(100003, '手机号已经被注册');
                return ;
            }else{
                $this->ret_json(100004, '注册失败，请稍后重试');
                return ;
            }
        }

        $this->ret_json(0, 'succ');
        return ;
    }

    /** METHOD: POST
    {
    username: "13823490934", //当前只支持手机号登录
    pswrod : "sd3fs3ff"
    }
    {
    retcode: 0, //账号不存在|密码不正确|系统错误
    retmsg : "succ",
    extras : {
    selectCourseId : "asdfsadf",  //科目id
    selectCourseName : "", //科目名称
    selectCourseClassId : "",
    currentUserName : "",
    examTime : "2019-04-02" //考试时间
    }
    }
     */
    public function login(){
        $username = $this->input->get_post("username", true);
        $pswrod = $this->input->get_post("pswrod", true);
        if($username == ""){
            $this->ret_json(100001, '用户名未填写');
            return ;
        }
        if($pswrod == ""){
            $this->ret_json(100001, '请输入密码');
            return ;
        }
        $res = $this->user_model->getUserInfo($username);
        if(!isset($res['retcode'])){
            $this->ret_json(100005, '查询用户信息失败，请刷新重试');
            return ;
        }
        if(isset($res['retcode']) && $res['retcode'] != 0){
            if(200003 == $res['retcode']){
                $this->ret_json(100006, '用户不存在或密码错误');
                return ;
            }else{
                $this->ret_json(100007, '查询用户信息失败');
                return ;
            }
        }
        $userInfo = $res['user'];
        if($pswrod != $userInfo['passwd']){
            $this->ret_json(100007, '用户不存在或密码错误');
            return ;
        }

        $now = date('Y-m-d h:i:s', time());
        $data = array('extras' => $userInfo['last_login_time']);
        $selectinfo = $userInfo['last_choose_subject'];
        if($selectinfo && $selectinfo != ''){
            $subject = json_decode($selectinfo);
            $data['extras']['selectCourseId'] = $subject->subject_id;
            $data['extras']['selectCourseName'] = $subject->subject_name;
        }

        $this -> load -> library('session');
        $arr = array('uid' => $userInfo['uid'], 'user_name' => $userInfo['user_name'], 'login_time' => $now);
        $this -> session -> set_userdata($arr);

        //把最近一次登录时间更新到用户记录中
        $userInfo['last_login_time'] = $now;
        $update = $this->user_model->updateUserInfo($userInfo);
        $this->log_info($update);

        $this->ret_json(0, '登录成功', $data);
    }


    //修改选择的科目
    public function changeDefault(){
        $username = $this->getUserName();
        $subject_id = $this->input->get_post("subject_id", true);
        if($subject_id === "" || $subject_id == 0){
            $this->ret_json(100001, '请选择科目');
            return ;
        }
        $subject_name = $this->subject_model->getNameBySubjectId($subject_id);
        $last_choose = json_encode(array('subject_id' => $subject_id, 'subject_name' => $subject_name));

        $res = $this->user_model->getUserInfo($username);
        if(!isset($res['retcode'])){
            $this->ret_json(100005, '查询用户信息失败，请刷新重试');
            return ;
        }else if(isset($res['retcode']) && $res['retcode'] != 0){
            if(200003 == $res['retcode']){
                $this->ret_json(100006, '用户不存在');
                return ;
            }else{
                $this->ret_json(100007, '查询用户信息失败');
                return ;
            }
        }
        $userInfo = $res['user'];
        $userInfo['last_choose_subject'] = $last_choose;
        $update = $this->user_model->updateUserInfo($userInfo);
        if(!isset($res['retcode']) || $res['retcode'] != 0){
            $this->ret_json(100009, '记录选择科目，请刷新重试');
            return ;
        }

        $this->ret_json(0, '更新成功');
    }
    public function chkLogin(){

    }
}