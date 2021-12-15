<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Paytr_gateway Installment Model Class
 */
class Installment_model extends App_Model
{

    /**
     * @var string
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @param string $id
     * @param false $exclude_notified
     * @return mixed
     */
    public function get($id = '', $exclude_notified = false)
    {
        if (is_numeric($id)) {
            $this->db->where('group_id', $id);
            return $this->db->get(db_prefix() . 'paytr_installments')->row();
        }
        return $this->db->get(db_prefix() . 'paytr_installments')->result_array();
    }

}