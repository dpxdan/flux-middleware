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

class ApiProxyOld1 extends CI_Controller {

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
        $this->CurrentDate = gmdate("Y-m-d H:i:s");
    }

    public function forward_post() {
        $headers = $this->input->request_headers();

        $api_url = $_SERVER['HTTP_REDIRECT_URL'];
        $api_endpoint = $_SERVER['HTTP_API_ENDPOINT'];
        $api_provider = $_SERVER['HTTP_API_PROVIDER'];
        $api_account = isset($_SERVER['HTTP_API_ACCOUNT']) && !empty($_SERVER['HTTP_API_ACCOUNT']) ? $_SERVER['HTTP_API_ACCOUNT'] : null;
        $api_token = $_SERVER['HTTP_AUTHORIZATION'];
        $post_data = file_get_contents("php://input");

        $providerdata = $this->get_provider_data($api_provider);

        if ($providerdata) {
            $api_url = $providerdata['partner_url'] . $api_endpoint;
            $partner_get = true;
        } else {
            $api_url = "https://".$_SERVER['HTTP_REDIRECT_URL'];
            $partner_get = false;
        }
        
        if ($api_account) {
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
        } else {
            $account_get = false;
        }

        $headers = [];
        if ($account_get) {
            $headers[] = 'Authorization: Basic ' . $credentials;
        }
        
        foreach ($this->input->request_headers() as $key => $value) {
            $headers[] = "$key: $value";
        }

        $response = $account_get ? $this->make_api_account_request($accountdata, $headers, $post_data) : $this->make_api_request($api_url, $headers, $post_data);

        if ($response['http_code'] !== 200) {
            $this->flux_log->write_log('forward_post', json_encode([
                'error' => 'API Response Code',
                'response_code' => $response['http_code'],
                'response' => $response['response'],
                'params' => $post_data
            ]));
        }

        $arr = json_decode($response['response'], true);
        $data = $arr['registros'];
        $dataTotal = $arr['total'];

        if ($api_endpoint == 'voip_sippeers' || $api_endpoint == 'view_voip_sippeers_cliente') {
            $api_endpoint = 'devices';
        }
        
        $apiTabela = $api_provider . "_" . $api_endpoint;
        
        if ($this->should_update_data($dataTotal, $apiTabela) && !$account_get) {
            $this->process_data($data, $api_provider, $api_endpoint);
        }

        if ($partner_get) {
            $this->update_partner($providerdata['id'], $api_token);
        }

        echo $response['response'];
    }

    private function get_provider_data($api_provider) {
        $providerdata = $this->db_model->getSelect("*", "api_partners", [
            "partner_name" => $api_provider,
            "status" => "0",
        ]);
        return $providerdata->num_rows() > 0 ? $providerdata->result_array()[0] : null;
    }

    private function get_account_data($api_account, $api_provider) {
        $accountdata = $this->db_model->getSelect("*", "view_api_partners", [
            "name" => $api_account,
            "partner_id" => $api_provider,
            "status" => "0",
        ]);
        return $accountdata->num_rows() > 0 ? $accountdata->result_array()[0] : null;
    }

    private function make_api_request($api_url, $headers, $post_data) {
        $ch = curl_init($api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
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
        $ch = curl_init($accountdata['url']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
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
        $pdo = new PDO('mysql:host=localhost;dbname=flux', 'root', 'SPsR*L4LgIThYU6Jd9Lq', [
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
        } else {
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

    public function forward_post_up() {
        $this->load->library("curl");
        

        $api_url = $_SERVER["HTTP_REDIRECT_URL"];
        $api_endpoint = $_SERVER["HTTP_API_ENDPOINT"];
        $api_provider = $_SERVER["HTTP_API_PROVIDER"];
        $api_token = $_SERVER["HTTP_AUTHORIZATION"];
        $api_method = $_SERVER["REQUEST_METHOD"];
        
        if ($api_endpoint == "produtos" && $api_provider == "ixc") {
            $partner_produtos = true;
        } else {
            $partner_produtos = false;
        }
        
        $this->flux_log->write_log("partner_produtos", json_encode($partner_produtos));

        $providerdata = $this->db_model->getSelect("*", "api_partners", [
            "partner_name" => $api_provider,
            "status" => "0",
        ]);

        if ($providerdata->num_rows() > 0) {
            $providerdata = $providerdata->result_array()[0];
            $api_url = $providerdata["partner_url"] . "" . $api_endpoint;
            $partner_get = true;
        } else {
            $api_url = "https://" . $_SERVER["HTTP_REDIRECT_URL"];
            $partner_get = false;
        }


        $post_data = file_get_contents("php://input");
        
        $headers = [];
        $headers = $this->input->request_headers();
        
        foreach ($this->input->request_headers() as $key => $value) {
            $headers[] = "$key: $value";
        }

        $ch = curl_init($api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
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
        
        
        if ($response === false) {
            echo "Erro ao fazer a requisição POST.";
        } else {
            $data = json_decode($response, true);
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
    }
}
