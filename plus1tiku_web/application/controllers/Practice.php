<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Practice extends TK_Model {

    //default router
    public function index(){

    }

    public function login(){
       // $this->load->view('templates/header', $data);
    }

    public function regist(){

    }

    //4. 科目切换： https://plus1tiku.cn/index.php/practice/subjects	courseclass.html
    public function subjects(){

    }

    //5. 首页内容： https://plus1tiku.cn/index.php/practice/home     home.html
    public function home(){

    }

    //6. 激活：https://plus1tiku.cn/index.php/practice/active  	active.html
    public function active(){

    }

    //6. 练习目录： https://plus1tiku.cn/index.php/practice/examlist 	chapterbox.html / chapterExamBox.html
    public function examlist(){

    }

    //7. 目录展开页面 ： https://plus1tiku.cn/index.php/practice/examlistpage 	chapterlistPage.html
    public function examlistpage(){

    }

    //7. 练习页面：https://plus1tiku.cn/index.php/practice/practice   practice.html
    //	subject_id + exam_id + is_redo + practice_type
    public function practice(){

    }


    //8. 模拟考试页面： https://plus1tiku.cn/index.php/practice/exam    examComfirm.html exam.html
    //	type = 1 ; 模拟考试 subject_id + exam_id + practice_type
    //	type = 2 ; 答案解析 subject_id + exam_id + practice_id + practice_type

    //	1. 登录模拟考试页面： https://plus1tiku.cn/index.php/practice/examlogin   examLogin.html
    public function examlogin(){

    }

    //	2. 确认进入考试 ： https://plus1tiku.cn/index.php/practice/examconfirm examComfirm.html
    public function examconfirm(){

    }

    //	3. 模拟考试：https://plus1tiku.cn/index.php/practice/exam exam.html
    public function exam(){

    }

    //9. 查看解析： https://plus1tiku.cn/index.php/practice/analysis
    public function analysis(){

    }
}