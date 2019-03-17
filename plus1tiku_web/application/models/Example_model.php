<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Example_model extends TK_Model {

    public function __construct()
    {
        parent::__construct();
        // Your own constructor code
    }

    public function getDbConn(){
        $db_tiku = $this->load->database('tiku', TRUE);
        //return $tiku;
        $query = $db_tiku->query('SELECT * FROM t_acct');
        return $query->result();
        }
}