<?php
// ##############################################################################
// Flux SBC - Unindo pessoas e negócios
//
// Copyright (C) 2022 Flux Telecom
// Daniel Paixao <daniel@flux.net.br>
// Flux SBC Version 4.0 and above
// License https://www.gnu.org/licenses/agpl-3.0.html
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU Affero General Public License as
// published by the Free Software Foundation, either version 3 of the
// License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU Affero General Public License for more details.
//
// You should have received a copy of the GNU Affero General Public License
// along with this program. If not, see <http://www.gnu.org/licenses/>.
// ##############################################################################
class api_model extends CI_Model {
	function api_model() {
		parent::__construct ();
		$this->load->library ('common');
		$this->load->model ('db_model');
		$this->load->library ('flux/order');
		$this->load->database(); // Carrega o banco de dados
	}


    /**
     * Busca a URL base da API pelo nome da API
     * @param string $api_nome - Nome da API a ser buscada
     * @return string|null - Retorna a URL da API ou null se não encontrar
     */
    public function getApiBaseUrl($api_nome) {
            $this->db->select('base_url');
            $this->db->from('endpoints');
            $this->db->where('nome', $api_nome);
            $query = $this->db->get();
    
            if ($query->num_rows() > 0) {
                return $query->row()->base_url;
            }
            return null;
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
        $this->db->from('accounts');
        $this->db->where('id_external', $cliente->id);
        $existing_account = $this->db->get()->row();
	    if (empty($cliente->senha)) {
	    $password = $this->common->generate_password();
	    } else {
	    $password = $cliente->senha;	    
	    }
	    $encoded_password = $this->common->encode($password);
	    
	    $pin_generate = common_model::$global_config['system_config']['generate_pin'];
		if ($pin_generate == 0 ) {
			$pin = (common_model::$global_config['system_config']['pinlength'] < 6) ? 6 : common_model::$global_config['system_config']['pinlength'];
			$pin_number = $this->common->find_uniq_rendno_customer($pin, 'number', 'accounts');
		}
		
		$uname = $this->common->find_uniq_rendno_customer(10, 'number ', 'accounts');
	    
        // Dados formatados
        $data = array(
            'id_external' => $cliente->id,
            'number' => preg_replace('/[^0-9]/', '', $cliente->cnpj_cpf),  // Remove '.', '-', '/'
            'reseller_id' => '0',
            'pricelist_id' => '1',
            'country_id' => '28',
            'currency_id' => '16',
            'timezone_id' => '78',
            'credit_limit' => '',
            'sweep_id' => '2',
            'posttoexternal' => '1',
            'type' => '0',
            'invoice_day' => '1',
            'first_name' => $cliente->razao,
            'last_name' => $cliente->razao,
            'company_name' => (!empty($cliente->fantasia)) ? $cliente->fantasia : $cliente->razao,
            'password' => $encoded_password,
            'pin' => $pin_number,
            'telephone_1' => (!empty($cliente->telefone_celular)) ? $cliente->telefone_celular : '5155555555',
            'telephone_2' => (!empty($cliente->telefone_comercial)) ? $cliente->telefone_comercial : '5155555555',
            'email' => $cliente->email,
            'notification_email' => $cliente->email,
            'address_1' => $cliente->endereco . ', ' . $cliente->numero . ' - ' . $cliente->bairro,
            'postal_code' => $cliente->cep,
            'city' => $this->get_city_name($cliente->cidade),
            'creation' => $cliente->data_cadastro,
            'status' => ($cliente->ativo == 'S') ? 0 : 1
        );

        // Se o cliente já existir na tabela accounts, faz um UPDATE
        if ($existing_account) {
            $this->db->where('id_external', $cliente->id);
            return $this->db->update('accounts', $data);
        } 
        else {
            $this->db->insert('accounts', $data);
            
            $this->load->library('flux/signup_lib');
	        $last_id = $this->signup_lib->create_account($data);
            $accountinfo = $this->db_model->getSelect('*', 'accounts', array('id' => $last_id))->row_array();                        
            return $accountinfo;
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
