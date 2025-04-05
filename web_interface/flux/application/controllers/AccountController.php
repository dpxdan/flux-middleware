<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class AccountController extends CI_Controller {

    public function __construct() {
        parent::__construct();
    }

    public function get_account_data() {
        
                $this->flux_log->write_log('get_account_data', json_encode($api_account));
                $this->flux_log->write_log('get_account_data', json_encode($api_provider));
                $accountdata = $this->db_model->getSelect("*", "view_api_partners", [
                    "name" => $api_account,
                    "partner_id" => $api_provider,
                    "status" => "0",
                ]);
                $this->flux_log->write_log('accountdata', json_encode($accountdata->result_array()[0]));
                return $accountdata->num_rows() > 0 ? $accountdata->result_array()[0] : null;
            
    }

    public function get_device_data() {
        
                        $devicedata = $this->db_model->getSelect("*", "sip_devices", [
                            "id_sip_external" => $id_sip,
                            "status" => "0",
                        ]);
                //        $this->flux_log->write_log('get_provider_data', json_encode($providerdata));
                        return $devicedata->num_rows() > 0 ? $devicedata->result_array()[0] : null;
                    
    }

    public function should_update_data() {
        
                $get_total_item = $this->db->query("SELECT count(id) as total FROM " . $apiTabela);
                $total_item = $get_total_item->row_array()['total'];
                return $total_item != $dataTotal;
            
    }
}
