<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class cliente_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        // Carregar a biblioteca de banco de dados
        $this->load->database();
    }

    // Função para inserir ou atualizar os registros na tabela 'accounts_copy1'
    public function insert_or_update_cliente($cliente_id) {
        // Buscando os dados do cliente
        $this->db->select('*');
        $this->db->from('ixc_cliente');
        $this->db->where('id', $cliente_id);
        $cliente = $this->db->get()->row();

        if (!$cliente) {
            return false;  // Cliente não encontrado
        }

        // Verificando se o cliente já existe na tabela 'accounts_copy1'
        $this->db->select('id');
        $this->db->from('accounts_copy1');
        $this->db->where('id', $cliente->id);
        $existing_account = $this->db->get()->row();

        // Dados formatados
        $data = array(
            'id' => $cliente->id,
            'number' => preg_replace('/[^0-9]/', '', $cliente->cnpj_cpf),  // Remove '.', '-', '/'
            'reseller_id' => '0',
            'pricelist_id' => '1',
            'country_id' => '28',
            'currency_id' => '16',
            'timezone_id' => '78',
            'type' => '0',
            'invoice_day' => '1',
            'first_name' => $cliente->razao,
            'last_name' => $cliente->razao,
            'company_name' => (!empty($cliente->fantasia)) ? $cliente->fantasia : $cliente->razao,
            'password' => $cliente->senha,
            'telephone_1' => (!empty($cliente->telefone_celular)) ? $cliente->telefone_celular : '5155555555',
            'telephone_2' => (!empty($cliente->telefone_comercial)) ? $cliente->telefone_comercial : '5155555555',
            'email' => $cliente->email,
            'address_1' => $cliente->endereco . ', ' . $cliente->numero . ' - ' . $cliente->bairro,
            'postal_code' => $cliente->cep,
            'city' => $this->get_city_name($cliente->cidade),
            'creation' => $cliente->data_cadastro,
            'status' => ($cliente->ativo == 'S') ? 0 : 1
        );

        // Se o cliente já existir na tabela accounts_copy1, faz um UPDATE
        if ($existing_account) {
            $this->db->where('id', $cliente->id);
            return $this->db->update('accounts_copy1', $data);
        } else {
            // Caso contrário, insere o novo cliente
            return $this->db->insert('accounts_copy1', $data);
        }
    }

    // Função para obter o nome da cidade baseado no ID da cidade
    private function get_city_name($city_id) {
        $this->db->select('nome');
        $this->db->from('ixc_cidade');
        $this->db->where('id', $city_id);
        $city = $this->db->get()->row();

        return $city ? $city->nome : 'Cidade Desconhecida';
    }
}
