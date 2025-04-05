<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class DataProcessingController extends CI_Controller {

    public function __construct() {
        parent::__construct();
    }

    public function process_data() {
        
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

    public function insertOrUpdateData() {
        
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

    public function orderKeysRecursively() {
       
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
}
