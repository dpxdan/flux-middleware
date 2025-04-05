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

class ApiProxyController extends API_Controller {
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
	
    public function __construct() {
        parent::__construct();
		$this->load->model('common_model');
		$this->load->library('common');
		$this->load->model('db_model');
		$this->load->library('flux/common');
		$this->load->library('flux_log');
		$this->load->library('Form_validation');       
		$this->CurrentDate = gmdate("Y-m-d H:i:s");
		$this->api_db_user = config_item('api_db_user');
		$this->api_db_pass = config_item('api_db_pass');
		$this->api_db_name = config_item('api_db_name');
		$this->api_db_host = config_item('api_db_host');		
    }

    public function forward_post() {
                require_once(APPPATH.'controllers/ProviderController.php');
                $ApiDataLog = array(
                	"api_db_user" => $this->api_db_user,
                	"api_db_pass" => $this->api_db_pass,
                	"api_db_name" => $this->api_db_name,
                	"api_db_host" => $this->api_db_host,
                );
                $this->flux_log->write_log('api_db', json_encode($ApiDataLog));
                $this->headers = $this->input->request_headers();
        
                $api_url = $_SERVER['HTTP_REDIRECT_URL'];
                $api_endpoint = $_SERVER['HTTP_API_ENDPOINT'];
                $api_provider = $_SERVER['HTTP_API_PROVIDER'];
                if(!empty($_SERVER['HTTP_API_ACCOUNT'])){
                $api_account = $_SERVER['HTTP_API_ACCOUNT'];
                }
                $api_token = $_SERVER['HTTP_AUTHORIZATION'];
                $post_data = file_get_contents("php://input");
                $providerdata = new ProviderController();
                $this->flux_log->write_log('ProviderController', json_encode($providerdata));
                $providerdata = $providerdata->get_provider_data($api_provider);
        
                if ($providerdata) {
                    $api_url = $providerdata['partner_url'] . $api_endpoint;
                    $partner_get = true;
                } 
                else {
                    $api_url = "https://".$_SERVER['HTTP_REDIRECT_URL'];
                    $partner_get = false;
                }
                
                if(!empty($api_account)){
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
                if ($api_provider == 'rbtelecom' || $api_provider == 'flux') {
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
                
                }
                }
            
    }

    protected function _forward_api() {
                
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
                if ($api_provider == 'rbtelecom' || $api_provider == 'flux') {
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
                else if ($api_endpoint == 'devices') {
    					$newArray = [];
    					if (!empty($data)) {
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

    public function make_api_request() {
        
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

    public function make_api_account_request() {
   
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
}
