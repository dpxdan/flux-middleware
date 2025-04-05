<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class ProviderController extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->library('common');
        $this->load->model('db_model');
        $this->load->library('flux_log');
    }

    public function get_provider_data($api_provider) {
        
                $providerdata = $this->db_model->getSelect("*", "api_partners", [
                    "partner_name" => $api_provider,
                    "status" => "0",
                ]);
                $this->flux_log->write_log('get_provider_data', json_encode($providerdata));
                return $providerdata->num_rows() > 0 ? $providerdata->result_array()[0] : null;
            
    }

    public function update_partner() {
       
                $update_login_date = "UPDATE api_partners SET last_login_date = '{$this->CurrentDate}', partner_token = '{$api_token}' WHERE id = {$partner_id}";
                $this->db->query($update_login_date);
            
    }
}
