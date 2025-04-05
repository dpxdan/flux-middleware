<?php
defined('BASEPATH') or exit('No direct script access allowed');
// ##############################################################################
// Flux Telecom - Unindo pessoas e negocios
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


class ApiCron extends CI_Controller {

    public static $global_config;
    public $CurrentDate = "";
    public $updateData = false;
    public $is_process = false;
    
    public $api_url = "";
    public $api_endpoint = "";
    public $api_provider = "";
    public $api_token = "";
    protected $postdata = "";
    

    function __construct() {
        parent::__construct();
        $this->load->model('common_model');
        $this->load->library('common');
        $this->load->model('db_model');
        $this->load->library('flux/common');
        $this->load->library('flux_log');

/*        $rawinfo = $this->post();
        $this->postdata = array_map(function($value) {
            return $this->_xss_clean($value, true);
        }, $rawinfo);*/

        $this->CurrentDate = gmdate("Y-m-d H:i:s");
    }
    
    function GetApiData() {
    
    	$providersdata = $this->db_model->getSelect("*", "api_partners", array(
    		"status" => "0",
    	));
    	if ($providersdata->num_rows > 0) {
    		$providersdata = $providersdata->result_array();
    		$this->flux_log->write_log('GetApiData', json_encode($providersdata));
    		foreach ($providersdata as $providerkey => $providervalue) {
    			if ($providervalue['run_cron'] == '0') {
    				$this->generate_api_data($providervalue);
    			}
    
    		}
    	}
    	exit();
    }

    public function run() {
    
            $api_partners = $this->db->get_where('api_partners', ['status' => '0'])->result_array();
            $this->flux_log->write_log('api_partners', json_encode($api_partners));
            if (empty($api_partners)) {
                return;
            }
    
            foreach ($api_partners as $partner) {
                $sql_endpoints = "SELECT nome FROM (`endpoints`,`api_partners`) WHERE `partner_id`=api_partners.id AND `endpoints`.`status`='0' AND `partner_id` = ".$partner['id']."";
                $endpoint_partners = $this->db->query($sql_endpoints);                
                if ($endpoint_partners->num_rows() > 0) {
                $endpoint_partners = $endpoint_partners->result_array();
                foreach ($endpoint_partners as $endpointdata) {
                $partner['endpoint_nome'] = $endpointdata['nome'];
                $this->process_api_partner($partner);
                }
                
                }
            }
    
        }
        
    public function generate_api_data($providervalue) {
        
                $api_partners = $this->db->get_where('api_partners', ['id' => $providervalue['id'],'status' => '0'])->result_array();
                $this->flux_log->write_log('api_partners', json_encode($api_partners));
                if (empty($api_partners)) {
                    return;
                }
        
                foreach ($api_partners as $partner) {
                    $sql_endpoints = "SELECT nome FROM (`endpoints`,`api_partners`) WHERE `partner_id`=api_partners.id AND `endpoints`.`status`='0' AND `partner_id` = ".$partner['id']."";
                    $endpoint_partners = $this->db->query($sql_endpoints);                
                    if ($endpoint_partners->num_rows() > 0) {
                    $endpoint_partners = $endpoint_partners->result_array();
                    foreach ($endpoint_partners as $endpointdata) {
                    $partner['endpoint_nome'] = $endpointdata['nome'];
                    $this->process_api_partner($partner);
                    }
                    
                    }
                }
        
            }
            
    private function process_api_partner($partner) {
            $api_url = $partner['partner_url'];
            $api_token = $partner['partner_token'];
            $api_user = $partner['partner_user'];
            $api_provider = $partner['partner_name'];
            $api_password = $partner['partner_password'];
            $api_endpoint = $partner['endpoint_nome'];
            $json_array = [
              'sortname' => ''.$api_endpoint.'.id',
              'qtype' => ''.$api_endpoint.'.id',
              'oper' => '>',
              'query' => '0',
              'rp' => '10000',
              'sortorder' => 'asc',
              'page' => '1'
            ]; 
            $body = json_encode($json_array);
            
    
            log_message('info', "Processando API do parceiro: {$partner['partner_name']} | endpoint: {$partner['endpoint_nome']}");
    
            $response = $this->make_api_cron_request("$api_url$api_endpoint", $api_token, $body);
            if ($response['http_code'] !== 200) {
                log_message('error', "Erro na API do parceiro: {$partner['partner_name']} | Código: {$response['response']}");
                return;
            }
    
            $arr = json_decode($response['response'], true);
    
            if (!isset($arr['registros']) || !isset($arr['total'])) {
                log_message('error', "Resposta inválida da API do parceiro: {$partner['partner_name']}");
                return;
            }
            $dataTotal = $arr['total'];
            if ($api_endpoint == 'voip_sippeers' || $api_endpoint == 'view_voip_sippeers_cliente') {
                    $api_endpoint = 'devices';
                    }
            $apiTabela = "".$api_provider."_".$api_endpoint."";
            
            
            if ($this->should_update_data_cron($dataTotal,$apiTabela) == true) {
                  $this->process_data_cron($arr['registros'], $partner['partner_name'], $api_endpoint);
                  log_message('info', "Dados processados para parceiro: {$partner['partner_name']} | endpoint: {$partner['endpoint_nome']}");
            } else {
            
                 log_message('info', "Dados nao atualizados para parceiro: {$partner['partner_name']} | endpoint: {$partner['endpoint_nome']}");
            }
        }
        
    private function get_provider_data($api_provider) {
            $providerdata = $this->db_model->getSelect("*", "api_partners", [
                "partner_name" => $api_provider,
                "status" => "0",
            ]);
    
            return $providerdata->num_rows() > 0 ? $providerdata->result_array()[0] : null;
        }

    private function get_provider_endpoint($api_provider) {
            $endpointdata = $this->db_model->getSelect("nome", "endpoints,api_partners", [
                "partner_id" => $api_provider,
                "endpoints.status" => "0",
            ]);
    
            return $endpointdata->num_rows() > 0 ? $endpointdata->result_array()[0] : null;
        }
        
    private function make_api_cron_request($url, $api_token, $body) {
        $this->flux_log->write_log('make_api_cron_request', json_encode($url));
        
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: $api_token",
            "Content-Type: application/json",
            "ixcsoft: listar"
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
        curl_close($ch);
    
        return ['http_code' => $http_code, 'response' => $response];
    }
            
    private function should_update_data_cron($dataTotal,$apiTabela) {
        $get_total_item = $this->db->query("SELECT count(id) as total FROM ".$apiTabela."");
        $total_item = $get_total_item->row_array()['total'];
        
        if ($total_item != $dataTotal) {
        $this->flux_log->write_log('should_update_data', json_encode("true"));
        return true;        
        }
        else {
        $this->flux_log->write_log('should_update_data', json_encode("false"));
        return false;
        }
    }

    private function process_data_cron($data, $api_provider, $api_endpoint) {
        if ($api_endpoint == 'voip_sippeers' || $api_endpoint == 'view_voip_sippeers_cliente' || $api_endpoint == 'devices') {
                $api_endpoint = 'devices';
                // Decodificar a resposta JSON
        //		  $dados = json_decode($data, TRUE);  
        //		  $this->flux_log->write_log ( 'dados', json_encode($data) );                    
        		  // Cria uma nova array para armazenar os dados filtrados
        		  $newArray = array();
        		  if (!empty($data)) {
        			  foreach ($data as $item) {
        				 if (!isset($item['cliente_id']) || !isset($item['cliente_razao'])) {
        				     $item['cliente_id'] = 0;
        				     $item['cliente_razao'] = "";				     
        				 }
        				 
        				 
        				  // Adiciona um novo item na nova array, com apenas as colunas desejadas
        				  $newArray[] = array(
        					'id' => $item['id'],
        					'cliente_id' => ($item['cliente_id'] > 0)?$item['cliente_id']:0,
        					'cliente_razao' => ($item['cliente_razao'] > 0)?$item['cliente_razao']:0,
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
        				  );
        			  }
        			  $this->flux_log->write_log ( 'newArray', json_encode($newArray) );
        			  //echo json_encode($newArray);
        		  }         
                }
        $apiTabela = "".$api_provider."_".$api_endpoint."";
        $this->flux_log->write_log('process_data', json_encode($apiTabela));
        $pdo = new PDO('mysql:host=localhost;dbname=flux', 'root', 'SPsR*L4LgIThYU6Jd9Lq', [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]);
        if ($api_endpoint == 'devices') {
                if (is_array($newArray[0])) {
                    foreach ($newArray as $registro) {
                        $this->insertOrUpdateData($pdo, $apiTabela, $registro);
                            			}
                } 
                else {
                    $this->insertOrUpdateData($pdo, $apiTabela, $newArray);
                }
                } 
        else {
                if (is_array($data[0])) {
                            foreach ($data as $registro) {
                                $this->insertOrUpdateData($pdo, $apiTabela, $registro);
                                    			}
                        } 
                        else {
                            $this->insertOrUpdateData($pdo, $apiTabela, $data);
                        }
                
                }
    }

    private function update_partner($partner_id, $api_token) {
        $update_login_date = "UPDATE api_partners SET last_login_date = '{$this->CurrentDate}', partner_token = '{$api_token}' WHERE id = {$partner_id}";
        $this->db->query($update_login_date);
    }

    public function insertOrUpdateDataCron(PDO $pdo, string $tabela, array $dados) {
        // Colunas e placeholders
        $colunas = implode(", ", array_keys($dados));
        $placeholders = ":" . implode(", :", array_keys($dados));
    
        // Construindo o ON DUPLICATE KEY UPDATE dinamicamente
        $updates = [];
        foreach ($dados as $coluna => $valor) {
            $updates[] = "$coluna = VALUES($coluna)";
        }
        $update_clause = implode(", ", $updates);
    
        // SQL final
        $sql = "INSERT INTO $tabela ($colunas) VALUES ($placeholders) ON DUPLICATE KEY UPDATE $update_clause";
    
        // Executando a query preparada
        $stmt = $pdo->prepare($sql);
        $stmt->execute($dados);
    }
    
    public function insertOrUpdateData(PDO $pdo, string $tabela, array $dados) {
            try {
                // Certifique-se de que o array de dados não está vazio
                if (empty($dados)) {
                    throw new Exception("Nenhum dado fornecido para inserção/atualização.");
                }
        
                // Prevenção contra SQL Injection no nome da tabela
                if (!preg_match('/^[a-zA-Z0-9_]+$/', $tabela)) {
                    throw new Exception("Nome da tabela inválido.");
                }
        
                // Nomes das colunas
                $colunas = "`" . implode("`, `", array_keys($dados)) . "`";
                $placeholders = ":" . implode(", :", array_keys($dados));
        
                // Construindo a cláusula ON DUPLICATE KEY UPDATE com placeholders nomeados
                $updates = [];
                foreach ($dados as $coluna => $valor) {
                    $updates[] = "`$coluna` = :update_$coluna";
                }
                $update_clause = implode(", ", $updates);
        
                // SQL final
                $sql = "INSERT INTO `$tabela` ($colunas) VALUES ($placeholders) 
                        ON DUPLICATE KEY UPDATE $update_clause";
        
                // Preparar a query
                $stmt = $pdo->prepare($sql);
        
                // Bind dos valores para INSERT
                foreach ($dados as $coluna => &$valor) {
                    $stmt->bindParam(":$coluna", $valor, is_int($valor) ? PDO::PARAM_INT : PDO::PARAM_STR);
                }
        
                // Bind dos valores para UPDATE (evitando erro SQLSTATE[HY093])
                foreach ($dados as $coluna => &$valor) {
                    $stmt->bindParam(":update_$coluna", $valor, is_int($valor) ? PDO::PARAM_INT : PDO::PARAM_STR);
                }
        
                // Executar a query
                $stmt->execute();
        
                return true; // Sucesso
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

}
