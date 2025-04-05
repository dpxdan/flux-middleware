<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Cron_Job extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('common_model');
        $this->load->model('db_model');
        $this->load->library('common');
        $this->load->library('flux/common');
        $this->load->library('flux_log');
        
    }

    public function index() {
        log_message('info', 'Iniciando execução do Cron Job.');
        $this->flux_log->write_log('info', json_encode('Iniciando execução do Cron Job'));
        // Recupera os parceiros ativos
        $api_partners = $this->db->get_where('api_partners', ['status' => '0'])->result_array();

        if (empty($api_partners)) {
            log_message('error', 'Nenhum parceiro ativo encontrado.');
            return;
        }

        foreach ($api_partners as $partner) {
            $this->process_api_partner($partner);
        }

        log_message('info', 'Execução do Cron Job finalizada.');
    }

    private function process_api_partner($partner) {
        $api_url = $partner['partner_url'];
        $api_token = $partner['partner_token'];
        $api_endpoint = '/dados'; // Ajuste conforme necessário

        log_message('info', "Processando API do parceiro: {$partner['partner_name']}");

        // Chamada para a API
        $response = $this->make_api_request($api_url . $api_endpoint, $api_token);

        if ($response['http_code'] !== 200) {
            log_message('error', "Erro na API do parceiro: {$partner['partner_name']} | Código: {$response['http_code']}");
            return;
        }

        $arr = json_decode($response['response'], true);

        if (!isset($arr['registros']) || !isset($arr['total'])) {
            log_message('error', "Resposta inválida da API do parceiro: {$partner['partner_name']}");
            return;
        }

        $this->process_data($arr['registros'], $partner['partner_name']);

        log_message('info', "Dados processados para parceiro: {$partner['partner_name']}");
    }

    private function make_api_request($url, $api_token) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $api_token",
            "Content-Type: application/json"
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        return ['http_code' => $http_code, 'response' => $response];
    }

    private function process_data($data, $partner_name) {
        $apiTabela = "{$partner_name}_dados";
        $this->load->database();

        if (!empty($data)) {
            foreach ($data as $registro) {
                $this->db->replace($apiTabela, $registro);
            }
        }
    }
}
