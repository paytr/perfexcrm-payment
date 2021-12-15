<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Paytr_gateway Installment_module Class
 */
class Installment_module extends AdminController
{

    /**
     * Constructor
     */
    public function index(){
        $this->load->helper('form');
        $this->load->model('invoice_items_model');
        $this->load->model('paytr_gateway/installment_model');
        $data['installment_item'] = $this->installment_model->get();
        $data['groups'] = $this->invoice_items_model->get_groups();
        $data['installments'] = array(
            0 => _l('paytr_installment_all'),
            1 => _l('paytr_single_installment'),
            2 => '2 '._l('paytr_installment'),
            3 => '3 '._l('paytr_installment'),
            4 => '4 '._l('paytr_installment'),
            5 => '5 '._l('paytr_installment'),
            6 => '6 '._l('paytr_installment'),
            7 => '7 '._l('paytr_installment'),
            8 => '8 '._l('paytr_installment'),
            9 => '9 '._l('paytr_installment'),
            10 => '10 '._l('paytr_installment'),
            11 => '11 '._l('paytr_installment'),
            12 => '12 '._l('paytr_installment'),
        );
        $data['title']  = _l('paytr_installment_settings');
        $this->load->view('installment_settings', $data);
    }

    /**
     * Installment_module::save_installment_settings()
     */
    public function update(){
        foreach ($this->input->post('installment_group') as $key => $value) {
            $this->db->replace(db_prefix().'paytr_installments', array(
                'group_id'          => $key,
                'installment_count' => $value,
            ));
        }
        redirect(site_url('admin/paytr_gateway/installment_module?success=true'));
    }

}