<?php
class Url_model extends CI_Model {

    public function __construct() {
        parent::__construct();
    }

    // Função para buscar o valor da URL
    public function get_url($id) {
        $this->db->select('url');
        $this->db->from('urls');
        $this->db->where('id', $id);
        $query = $this->db->get();

        // Verificar se a consulta retornou algum resultado
        if ($query->num_rows() > 0) {
            return $query->row()->url; // Retorna o valor da coluna 'url'
        } else {
            return null; // Caso não encontre a URL
        }
    }
}
