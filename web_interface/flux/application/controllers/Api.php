<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Api extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Cliente_model');
    }

    public function importar_dados($tabela = 'clientes') {
        // Definir tabela por parâmetro ou por lógica interna
        if (empty($tabela)) {
            echo json_encode(array('status' => 'erro', 'mensagem' => 'Tabela não especificada.'));
            return;
        }

        // 1. Dados de envio
        $payload = array(
            'parametro1' => 'valor1',
            'parametro2' => 'valor2'
        );

        // 2. Requisição POST via API
        $url = 'https://api.exemplo.com/endpoint';
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer SEU_TOKEN_AQUI'
        ));
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        // Validação da API
        if ($error) {
            echo json_encode(array('status' => 'erro', 'mensagem' => 'Erro cURL: ' . $error));
            return;
        }
        if ($http_code != 200) {
            echo json_encode(array('status' => 'erro', 'mensagem' => 'Erro API: ' . $response));
            return;
        }

        // 3. Tratamento do JSON
        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            echo json_encode(array('status' => 'erro', 'mensagem' => 'JSON inválido: ' . json_last_error_msg()));
            return;
        }

        // 4. Inserir ou atualizar na tabela dinâmica
        $inserted = 0;
        if (isset($data[0]) && is_array($data[0])) {
            foreach ($data as $registro) {
                if ($this->Cliente_model->insert_or_update($tabela, $registro)) {
                    $inserted++;
                }
            }
        } else {
            if ($this->Cliente_model->insert_or_update($tabela, $data)) {
                $inserted++;
            }
        }

        // 5. Retorno final
        echo json_encode(array('status' => 'sucesso', 'mensagem' => "$inserted registros inseridos/atualizados com sucesso.", 'tabela' => $tabela));
    }
}
