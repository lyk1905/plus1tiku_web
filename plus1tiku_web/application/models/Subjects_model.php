<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Subjects_model extends TK_Model {
    //获取科目信息
    public function getNameBySubjectId($id){
        return '初级会计考试';
    }
}