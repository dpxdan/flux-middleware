<?php
defined('BASEPATH') or exit('No direct script access allowed');

// ##############################################################################
// Flux Telecom - Unindo pessoas e negócios
//
// Copyright (C) 2025 Flux Telecom
// Daniel Paixao <daniel@flux.net.br>
// FluxSBC Version 4.2 and above
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

require APPPATH . '/libraries/API_Controller.php';

class ApiProxy extends API_Controller {
    public static $global_config;
    public $CurrentDate = "";
    public $updateData = false;
    public $is_process = false;
    
    public $api_url = "";
    public $api_endpoint = "";
    public $api_provider = "";
    public $api_token = "";
    public $api_account = "";
    protected $postdata = "";

    function __construct() {
        parent::__construct();
        $this->load->model('common_model');
        $this->load->library('common');
        $this->load->model('db_model');
        $this->load->model('api_model');
        $this->load->library('flux/common');
        $this->load->library('flux_log');
        $this->load->library('Form_validation');       
        $this->CurrentDate = gmdate("Y-m-d H:i:s");
        $this->api_db_user = config_item('api_db_user');
        $this->api_db_pass = config_item('api_db_pass');
        $this->api_db_name = config_item('api_db_name');
        $this->api_db_host = config_item('api_db_host');
		    $rawinfo = $this->post ();
		    $this->postdata = array();
				foreach ( $rawinfo as $key => $value ) {
						$this->postdata [$key] = $this->_xss_clean ( $value, TRUE );
					} 
    }

    public function index() {
    		$function = isset ( $this->postdata ['action'] ) ? $this->postdata ['action'] : '';
    		$headers = $this->input->request_headers();
//            $headers = $this->_getRequestHeaders();
    		$this->authorization_header = $this->input->get_request_header('Authorization');
    		$this->flux_log->write_log('authorization_header', json_encode($this->authorization_header));
    		if ($function != '') {
    			$function = '_' . $function;
    			if (( int ) method_exists ( $this, $function ) > 0) {
    				$this->$function ();
    			} else {
    				$this->response ( array (
    					'status' => false,
    					'error' => $this->lang->line ( 'unknown_method' )
    				), 400 );
    			}
    		} 
    		else {
    			$this->response ( array (
    				'status'=> false,
    				'error' => $this->lang->line ( 'unknown_method' )
    			), 400 );
    		}
    	}

    public function forward_post1() {

        $ApiDataLog = array(
        	"api_db_user" => $this->api_db_user,
        	"api_db_pass" => $this->api_db_pass,
        	"api_db_name" => $this->api_db_name,
        	"api_db_host" => $this->api_db_host,
        );
        $this->flux_log->write_log('api_db', json_encode($ApiDataLog));

        /*$function = isset ( $this->postdata ['action'] ) ? $this->postdata ['action'] : '';
        if ($function != '') {
            			$function = '_' . $function;
            			$this->flux_log->write_log('function', json_encode($function));
            			if (( int ) method_exists ( $this, $function ) > 0) {
            				$this->$function ();
            			} else {
            				$this->response ( array (
            					'status' => false,
            					'error' => $this->lang->line ( 'unknown_method' )
            				), 400 );
            			}
            		} 
        else {
            			$this->response ( array (
            				'status'=> false,
            				'error' => $this->lang->line ( 'unknown_method' )
            			), 400 );
            		}
        */
        $this->headers = $this->input->request_headers();

        $api_url = $_SERVER['HTTP_REDIRECT_URL'];
        $api_endpoint = $_SERVER['HTTP_API_ENDPOINT'];
        $api_provider = $_SERVER['HTTP_API_PROVIDER'];
        if(!empty($_SERVER['HTTP_API_ACCOUNT'])){
        $api_account = $_SERVER['HTTP_API_ACCOUNT'];
        }
        $api_token = $_SERVER['HTTP_AUTHORIZATION'];
        $post_data = file_get_contents("php://input");
        $providerdata = $this->get_provider_data($api_provider);

        if ($providerdata) {
//            $this->flux_log->write_log('forward_post_providerdata', json_encode($providerdata));
            $api_url = $providerdata['partner_url'] . $api_endpoint;
//            $this->flux_log->write_log('forward_post_api_url', json_encode($api_url));
            $partner_get = true;
        } 
        else {
            $api_url = "https://".$_SERVER['HTTP_REDIRECT_URL'];
            $partner_get = false;
        }
        
        if(!empty($api_account)){
//        if ($api_account) {
            $this->flux_log->write_log('forward_post_api_account', json_encode($api_account));
            $accountdata = $this->get_account_data($api_account, $providerdata['id']);
            if ($accountdata) {
                $accountdata['url'] = $accountdata['url'] . $api_endpoint;
                $account_username = $accountdata['endpoint_user'];
                $account_password = $accountdata['endpoint_password'];
                $credentials = base64_encode($accountdata['endpoint_user'] . ":" . $accountdata['endpoint_password']);
                $account_get = true;
            } else {
                $account_url = "https://".$_SERVER['HTTP_REDIRECT_URL'];
                $account_get = false;
            }
        } 
        else {
            $account_get = false;
        }


        if ($account_get == true) {
        $response = $this->make_api_account_request($accountdata, $this->headers, $post_data);
        
        } 
        else {
        $response = $this->make_api_request($api_url, $post_data);
        
        }

        if ($response['http_code'] !== 200) {
            $this->flux_log->write_log('forward_post', json_encode([
                'error' => 'API Response Code',
                'response_code' => $response['http_code'],
                'response' => $response['response'],
                'params' => $post_data
            ]));
        } 
        else {
        $arr = json_decode($response['response'], true);
        if(empty($arr['registros'])){
        			$this->response ( array (
        				'total'=>0,
        				'success' => $this->lang->line("no_records_found")
        			), $response['http_code'] );
        		}
        else {
        
        $data = $arr['registros'];
        $dataTotal = $arr['total'];
        $page = $arr['page'];

        if ($api_endpoint == 'voip_sippeers' || $api_endpoint == 'view_voip_sippeers_cliente') {
            $api_endpoint = 'devices';
        }
        if ($api_provider == 'flux') {
            $api_provider = 'ixc';
        }
        if ($api_endpoint == 'produtos') {
		    $partner_produtos = true;
		}
        $apiTabela = $api_provider . "_" . $api_endpoint;
        
        if ($this->should_update_data($dataTotal, $apiTabela)) {
            $this->process_data($data, $api_provider, $api_endpoint);
        }

        if ($partner_get) {
            $this->update_partner($providerdata['id'], $api_token);
        }
        if ($api_endpoint == 'produtos'){
                $newArray = [];
        		if (!empty($data["registros"])) {
        		   foreach ($data["registros"] as $item) {
        			   $newArray[] = [
        				   "id" => $item["id"],
        				   "descricao" => $item["descricao"],
        				   "valor" => $item["valor"],
        				   "preco_base" => $item["preco_base"],
        				   "ultima_atualizacao" => $item["ultima_atualizacao"],
        				   "valor_custo" => $item["valor_custo"],
        				   "id_sub_grupo" => $item["id_sub_grupo"],
        				   "ativo" => $item["ativo"],
        			   ];
        		   }
        		   echo json_encode($newArray);
        		}
                }
        header ( 'Content-Type: application/json');
        foreach ($arr['registros'] as &$registro) {
            $registro = $this->orderKeysRecursively($registro);  // Ordena as chaves de cada objeto
        }
        $sorted_json = json_encode($arr['registros'], JSON_PRETTY_PRINT);
        $this->response ( array (
        				'status' => true,
        				'total'=>$dataTotal,
        				'page'=>$page,
        				'data' => json_decode($sorted_json),
        				'success' => $this->lang->line( "api_proxy_list_information" )
        			), 200 );
        
       // echo $sorted_json;
        }
        }
    }

    public function _forward_api() {            
            $headers = $this->input->request_headers();
            $api_url = $_SERVER['HTTP_REDIRECT_URL'];
            $api_endpoint = $_SERVER['HTTP_API_ENDPOINT'];
            $api_provider = $_SERVER['HTTP_API_PROVIDER'];
            if(!empty($_SERVER['HTTP_API_ACCOUNT'])){
            $api_account = $_SERVER['HTTP_API_ACCOUNT'];
            }
            $api_token = $_SERVER['HTTP_AUTHORIZATION'];
            $post_data = file_get_contents("php://input");
            $providerdata = $this->get_provider_data($api_provider);

            if ($providerdata) {
                $api_url = $providerdata['partner_url'] . $api_endpoint;
                $partner_get = true;
            } 
            else {
                $api_url = "https://".$_SERVER['HTTP_REDIRECT_URL'];
                $partner_get = false;
            }
            
            if(!empty($api_account)){
    //        if ($api_account) {
                $this->flux_log->write_log('forward_post_api_account', json_encode($api_account));
                $accountdata = $this->get_account_data($api_account, $providerdata['id']);
                if ($accountdata) {
                    $accountdata['url'] = $accountdata['url'] . $api_endpoint;
                    $account_username = $accountdata['endpoint_user'];
                    $account_password = $accountdata['endpoint_password'];
                    $credentials = base64_encode($accountdata['endpoint_user'] . ":" . $accountdata['endpoint_password']);
                    $credentials = 'Basic: '.$credentials.'';
                    $account_get = true;
                    
                    $AccountDataLog = array(
                    	"url" => $accountdata['url'],
                    	"account_username" => $account_username,
                    	"account_password" => $account_password,
                    	"credentials" => $credentials,
                    	"account_get" => $account_get,
                    );
                    $this->flux_log->write_log('account_data', json_encode($AccountDataLog));
                    
                    
                } else {
                    $account_url = "https://".$_SERVER['HTTP_REDIRECT_URL'];
                    $account_get = false;
                }
            } 
            else {
                $account_get = false;
            }
    
    
            if ($account_get == true) {
            $response = $this->make_api_account_request($accountdata,$headers,$post_data);
            
            } 
            else {
            $response = $this->make_api_request($api_url, $post_data);
            
            }
    
            if ($response['http_code'] !== 200) {
                $this->flux_log->write_log('forward_post', json_encode([
                    'error' => 'API Response Code',
                    'response_code' => $response['http_code'],
                    'response' => $response['response'],
                    'params' => $post_data
                ]));
            } 
            else {
            $arr = json_decode($response['response'], true);
            if(empty($arr['registros'])){
            			$this->response ( array (
            				'total'=>0,            				
            				'success' => $this->lang->line("no_records_found")
            			), $response['http_code'] );
            		}
            else {
            
            $data = $arr['registros'];
            $dataTotal = $arr['total'];
            $page = $arr['page'];
    
            if ($api_endpoint == 'voip_sippeers' || $api_endpoint == 'view_voip_sippeers_cliente') {
                $api_endpoint = 'devices';
            }
            if ($api_endpoint == 'cliente') {
                $cidade_id = $data[0]['cidade'];                
                $cidadedata = $this->get_cidade_data($cidade_id);
                if ($cidadedata) {
                $cidade_nome = $cidadedata['nome'];
                } 
                else {
                $cidade_nome = $cidade_id;
                }
            }
            if ($api_provider == 'flux') {
                $api_provider = 'ixc';
            }
            if ($api_endpoint == 'produtos') {
    		    $partner_produtos = true;
    		    }
            $apiTabela = $api_provider . "_" . $api_endpoint;
            
            if ($this->should_update_data($dataTotal, $apiTabela)) {
                $this->process_data($data, $api_provider, $api_endpoint);
            }    
            if ($partner_get) {
                $this->update_partner($providerdata['id'], $api_token);
            }

            
            if ($api_endpoint == 'produtos'){
                    $newArray = [];
            		if (!empty($data)) {
            		   foreach ($data as $item) {
            			   $newArray[] = [
            				   "id" => $item["id"],
            				   "descricao" => $item["descricao"],
            				   "valor" => $item["valor"],
            				   "preco_base" => $item["preco_base"],
            				   "ultima_atualizacao" => $item["ultima_atualizacao"],
            				   "valor_custo" => $item["valor_custo"],
            				   "id_sub_grupo" => $item["id_sub_grupo"],
            				   "ativo" => $item["ativo"],
            			   ];
            		   }
					   header ( 'Content-Type: application/json');
					   foreach ($newArray as &$registro) {
						   $registro = $this->orderKeysRecursively($registro);
					   }
					   $sorted_json = json_encode($newArray, JSON_PRETTY_PRINT);
					   $this->response ( array (
									'status' => true,
									'total'=>$dataTotal,
									'page'=>$page,
									'data' => json_decode($sorted_json),
									'success' => $this->lang->line( "api_proxy_list_information" )
								), 200 );
            		}
                    }
            else if ($api_endpoint == 'cliente'){
								$newArray = [];
								if (!empty($data)) {
									 foreach ($data as $item) {
										 $newArray[] = [
											 "id" => $item["id"],
											 "first_name" => $item["razao"],
											 "last_name" => $item["razao"],
											 "company_name" => $item["razao"],
											 "password" => $item["senha"],
											 "email" => $item["email"],
											 "address_1" => $item["endereco"].', '.$item["numero"].' - '.$item["bairro"],
											 "postal_code" => $item["cep"],
											 "city" => $cidade_nome,
											 "telephone_1" => $item["telefone_celular"],
											 "telephone_2" => $item["telefone_comercial"],
											 "creation" => $item["data_cadastro"].' 00:00:00',
											 "status" => $item["ativo"],
										 ];
										 $updateClient = $this->api_model->insert_or_update_cliente($item["id"]);
//										 $updateClient = $this->processar_cliente($item["id"]);
									 }
						 header ( 'Content-Type: application/json');
						 foreach ($newArray as &$registro) {
							 $registro = $this->orderKeysRecursively($registro);
						 }
						 $sorted_json = json_encode($newArray, JSON_PRETTY_PRINT);
						 $this->response ( array (
									'status' => true,
									'total'=>$dataTotal,
									'page'=>$page,
									'data' => json_decode($sorted_json),
									'success' => $this->lang->line( "api_proxy_list_information" )
								), 200 );
								}
            }
            else if ($api_endpoint == 'devices') {
					$newArray = [];
					if (!empty($data)) {
					   $this->flux_log->write_log('data', json_encode($data));
					   foreach ($data as $item) {
						   if ($item["ativo"] == 'N') {
						   $ativo = "Inactive";
						   
						   } else {
						   $ativo = "Active";
						   
						   }						   						   
						   $newArray[] = [
							   "id_sip_external" => $item["id"],
							   "accountid" => $item["id_contrato"],
							   "cliente_id" => $item["cliente_id"],
							   "contrato_id" => $item["id_contrato"],
							   "status" => $ativo,
							   "sipdevice_id" => $item["id_sip"],
							   "caller_name" => $item["name"],
							   "caller_number" => $item["callerid"],
							   "username" => $item["name"],
							   "reseller_id" => $item["id_contrato"],
							   "password" => $item["secret"],
						   ];
					   }
					   header ( 'Content-Type: application/json');
					   $sipgetData = $this->get_device_data($item["id"]);
					   $this->flux_log->write_log('sipgetData', json_encode($sipgetData));
					   foreach ($newArray as &$registro) {
						   $registro = $this->orderKeysRecursively($registro);
					   }
					   $sorted_json = json_encode($newArray, JSON_PRETTY_PRINT);
					   $this->response ( array (
									'status' => true,
									'total'=>$dataTotal,
									'page'=>$page,
									'data' => json_decode($sorted_json),
									'success' => $this->lang->line( "api_proxy_list_information" )
								), 200 );
					}
					}
            else {
            header ( 'Content-Type: application/json');
            foreach ($arr['registros'] as &$registro) {
                $registro = $this->orderKeysRecursively($registro);  // Ordena as chaves de cada objeto
            }
            $sorted_json = json_encode($arr['registros'], JSON_PRETTY_PRINT);
            $this->response ( array (
            				'status' => true,
            				'total'=>$dataTotal,
            				'page'=>$page,
            				'data' => json_decode($sorted_json),
            				'success' => $this->lang->line( "api_proxy_list_information" )
            			), 200 );
            }
            }
            }
        }

    private function get_provider_data($api_provider) {
        $providerdata = $this->db_model->getSelect("*", "api_partners", [
            "partner_name" => $api_provider,
            "status" => "0",
        ]);
//        $this->flux_log->write_log('get_provider_data', json_encode($providerdata));
        return $providerdata->num_rows() > 0 ? $providerdata->result_array()[0] : null;
    }
    
    private function get_cidade_data($id) {
            $cidadedata = $this->db_model->getSelect("nome", "ixc_cidade", [
                "id" => $id,                
            ]);
    //        $this->flux_log->write_log('get_provider_data', json_encode($providerdata));
            return $cidadedata->num_rows() > 0 ? $cidadedata->result_array()[0] : null;
        }

    private function get_account_data($api_account, $api_provider) {
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
    
    private function get_device_data($id_sip) {
                $devicedata = $this->db_model->getSelect("*", "sip_devices", [
                    "id_sip_external" => $id_sip,
                    "status" => "0",
                ]);
        //        $this->flux_log->write_log('get_provider_data', json_encode($providerdata));
                return $devicedata->num_rows() > 0 ? $devicedata->result_array()[0] : null;
            }

    private function make_api_request($api_url, $post_data) {
        $api_token = $_SERVER['HTTP_AUTHORIZATION'];
        $api_ixcsoft = $_SERVER['HTTP_IXCSOFT'];
        $ch = curl_init($api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
                         'ixcsoft: '.$api_ixcsoft.'',
                         'Authorization: '.$api_token.'',
                         'Accept-Encoding: gzip, zlib, deflate, zstd, br'
                     ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            $this->flux_log->write_log('make_api_request', json_encode([
                'error' => 'API Curl Request',
                'response_code' => curl_error($ch),
                'params' => $post_data
            ]));
        }

        curl_close($ch);
        return ['response' => $response, 'http_code' => $http_code];
    }

    private function make_api_account_request($accountdata, $headers, $post_data) {
            $api_token = $_SERVER['HTTP_AUTHORIZATION'];
            $api_ixcsoft = $_SERVER['HTTP_IXCSOFT'];
            $ch = curl_init($accountdata['url']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
		    curl_setopt($ch, CURLOPT_HTTPHEADER, [
		                    'ixcsoft: '.$api_ixcsoft.'',
		                    'Authorization: '.$api_token.'',
		                    'Accept-Encoding: gzip, zlib, deflate, zstd, br'
		                ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
            if (curl_errno($ch)) {
                $this->flux_log->write_log('make_api_request', json_encode([
                    'error' => 'API Curl Request',
                    'response_code' => curl_error($ch),
                    'params' => $post_data
                ]));
            }
    
            curl_close($ch);
            return ['response' => $response, 'http_code' => $http_code];
        }

    private function should_update_data($dataTotal, $apiTabela) {
        $get_total_item = $this->db->query("SELECT count(id) as total FROM " . $apiTabela);
        $total_item = $get_total_item->row_array()['total'];
        return $total_item != $dataTotal;
    }

    private function process_data($data, $api_provider, $api_endpoint) {
        if ($api_endpoint == 'voip_sippeers' || $api_endpoint == 'view_voip_sippeers_cliente' || $api_endpoint == 'devices') {
            $api_endpoint = 'devices';
            $newArray = [];

            if (!empty($data)) {
                foreach ($data as $item) {
                    if (!isset($item['cliente_id']) || !isset($item['cliente_razao'])) {
                        $item['cliente_id'] = 0;
                        $item['cliente_razao'] = "";
                    }
/*

  CURLOPT_POSTFIELDS =>'{
    "id":"1",
	"token":"cUFyN0ZHQ3VGelcwSld4dUgySTdwdz09",
    "action": "sip_devices_create",
    "reseller_id": 0,
    "username": "1002", name
    "caller_number": "1002", callerid
    "caller_name": "1002", name
    "accountid": 17, cliente_id
    "status": 0, ativo
    "sip_profile_id": "1", 1
    "id_sip_external": "2" id
}',
*/
                    $newArray[] = [
                        'id' => $item['id'],
                        'cliente_id' => ($item['cliente_id'] > 0) ? $item['cliente_id'] : 0,
                        'cliente_razao' => ($item['cliente_razao'] > 0) ? $item['cliente_razao'] : 0,
                        'numero' => $item['numero'],
                        'use_area_code' => $item['use_area_code'],
                        'id_plano' => $item['id_plano'],
                        'id_plano_sip' => $item['id_plano_sip'],
                        'callerid' => $item['callerid'],
                        'name' => $item['name'],
                        'context' => $item['context'],
                        'secret' => $item['secret'],
                        'id_sip' => $item['id_sip'],
                        'id_contrato' => $item['id_contrato'],
                        'accountcode' => $item['accountcode'],
                        'id_integracao' => $item['id_integracao'],
                        'ativo' => $item['ativo']
                    ];
                }
                $this->flux_log->write_log('newArray', json_encode($newArray));
            }
        }
       
        $apiTabela = $api_provider . "_" . $api_endpoint;
        $pdo = new PDO('mysql:host='.$this->api_db_host.';dbname='.$this->api_db_name.'', ''.$this->api_db_user.'', ''.$this->api_db_pass.'', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);

        if ($api_endpoint == 'devices') {
            if (is_array($newArray[0])) {
                foreach ($newArray as $registro) {
                    $this->insertOrUpdateData($pdo, $apiTabela, $registro);
                }
            } else {
                $this->insertOrUpdateData($pdo, $apiTabela, $newArray);
            }
        } 
        else {
            if (is_array($data[0])) {
                foreach ($data as $registro) {
                    $this->insertOrUpdateData($pdo, $apiTabela, $registro);
                }
            } else {
                $this->insertOrUpdateData($pdo, $apiTabela, $data);
            }
        }
    }

    private function update_partner($partner_id, $api_token) {
        $update_login_date = "UPDATE api_partners SET last_login_date = '{$this->CurrentDate}', partner_token = '{$api_token}' WHERE id = {$partner_id}";
        $this->db->query($update_login_date);
    }

    public function insertOrUpdateData(PDO $pdo, string $tabela, array $dados) {
        try {
            if (empty($dados)) {
                throw new Exception("Nenhum dado fornecido para inserção/atualização.");
            }

            if (!preg_match('/^[a-zA-Z0-9_]+$/', $tabela)) {
                throw new Exception("Nome da tabela inválido.");
            }

            $colunas = "`" . implode("`, `", array_keys($dados)) . "`";
            $placeholders = ":" . implode(", :", array_keys($dados));

            $updates = [];
            foreach ($dados as $coluna => $valor) {
                $updates[] = "`$coluna` = :update_$coluna";
            }
            $update_clause = implode(", ", $updates);

            $sql = "INSERT INTO `$tabela` ($colunas) VALUES ($placeholders) 
                    ON DUPLICATE KEY UPDATE $update_clause";

            $stmt = $pdo->prepare($sql);

            foreach ($dados as $coluna => &$valor) {
                $stmt->bindParam(":$coluna", $valor, is_int($valor) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }

            foreach ($dados as $coluna => &$valor) {
                $stmt->bindParam(":update_$coluna", $valor, is_int($valor) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }

            $stmt->execute();

            return true;
        } catch (PDOException $e) {
            $this->flux_log->write_log('insertOrUpdateDataNew1', json_encode([
                'error' => $e->getMessage(),
                'query' => $sql,
                'params' => $dados
            ]));
            return false;
        } catch (Exception $e) {
            $this->flux_log->write_log('insertOrUpdateDataNew2', json_encode([
                'error' => $e->getMessage()
            ]));
            return false;
        }
    }
    
    public function orderKeysRecursively($array) {
        if (!is_array($array)) {
            return $array;
        }
        ksort($array);  // Ordena as chaves do array
        foreach ($array as $key => &$value) {
            if (is_array($value)) {
                $value = orderKeysRecursively($value);  // Recursão para sub-arrays
            }
        }
        return $array;
    }
    
    // Método que será chamado para processar o cliente
    public function processar_cliente($cliente_id) {
           // Chama a função para inserir ou atualizar o cliente
           $result = $this->api_model->insert_or_update_cliente($cliente_id);
    
           if ($result) {
               // Aqui você pode retornar um JSON ou mensagem de sucesso
               $this->response ( array (
								'status' => true,
								'data' => $result,
								'success' => 'Cliente processado com sucesso!'
							), 200 );
               
             } else {
                $this->response ( array (
								'status' => false,
								'data' => json_decode($result),
								'error' => 'Erro ao processar o cliente.'
							), 500 );
              
               //echo json_encode(["message" => "Erro ao processar o cliente.", "status" => false]);
           }
       }    
}
