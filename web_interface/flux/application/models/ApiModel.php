<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class ApiModel extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->load->database(); // Certifique-se de que o banco está carregando corretamente
    }

    public function getApiBaseUrl($api_nome) {
        $this->db->select('base_url');
        $this->db->from('api_endpoints');
        $this->db->where('nome', $api_nome);
        $query = $this->db->get();

        if ($query->num_rows() > 0) {
            return $query->row()->base_url;
        }
        return null;
    }
}
