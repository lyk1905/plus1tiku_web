<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Records_model extends TK_Model {

    public function getRecordOverLook($uid, $subject_id, $practice_type){

    }

    public function getRecord($uid, $subject_id, $record_id){

    }

    public function getStatisInfo($uid, $subject_id, $state){

    }

    public function updateRecord($record){
        /*
        try {
            $this->db->trans_begin();
            $res = $this->db->query('AN SQL QUERY...');
            if(!$res) throw new Exception($this->db->_error_message(), $this->db->_error_number());
            $res = $this->db->query('ANOTHER QUERY...');
            if(!$res) throw new Exception($this->db->_error_message(), $this->db->_error_number());
            $this->db->trans_commit();
        }
        catch (Exception $e) {
            $this->db->trans_rollback();
            log_message('error', sprintf('%s : %s : DB transaction failed. Error no: %s, Error msg:%s, Last query: %s', __CLASS__, __FUNCTION__, $e->getCode(), $e->getMessage(), print_r($this->main_db->last_query(), TRUE)));
        }
        */
    }

    public function updateStatis($statis){

    }

    public function updateRecrdAndStatis($record, $statis){

    }
}