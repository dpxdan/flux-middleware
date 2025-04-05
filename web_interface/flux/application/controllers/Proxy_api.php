<?php
class Proxy_api extends CI_Controller {

    public function __construct() {
        parent::__construct();
        // Carregar a biblioteca CURL para fazer requisições HTTP
        $this->load->library('curl');
        // Carregar o helper de URL
        $this->load->helper('url');
        $this->load->model('Url_model');
    }

    // Função para encaminhar a requisição GET
    public function get($endpoint) {
        // Validação de entrada (verifica se o endpoint está vazio ou não)
        if (empty($endpoint)) {
            $this->_send_response(400, ['error' => 'Endpoint não especificado']);
            return;
        }

        $url = 'https://api.exemplo.com/' . $endpoint;

        // Autenticação: Verifica se o token de API está presente nos headers
        $auth_token = $this->_get_auth_token();
        if (!$auth_token) {
            $this->_send_response(401, ['error' => 'Autenticação necessária']);
            return;
        }

        // Configurações para a requisição GET
        $headers = [
            "Authorization: Bearer $auth_token"
        ];

        // Fazendo a requisição GET usando CURL
        $response = $this->curl->simple_get($url, [], $headers);

        if ($response === false) {
            $this->_send_response(500, ['error' => 'Erro ao acessar o serviço remoto']);
            return;
        }

        // Retorna o resultado da requisição para o cliente
        $this->_send_response(200, json_decode($response, true));
    }

    // Função para encaminhar a requisição POST
    public function post($endpoint) {
        // Validação de entrada
        if (empty($endpoint) || !$this->_validate_json_input()) {
            $this->_send_response(400, ['error' => 'Dados inválidos ou endpoint não especificado']);
            return;
        }

        $url = 'https://api.exemplo.com/' . $endpoint;

        // Autenticação
        $auth_token = $this->_get_auth_token();
        if (!$auth_token) {
            $this->_send_response(401, ['error' => 'Autenticação necessária']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);

        // Configurações para a requisição POST
        $headers = [
            "Authorization: Bearer $auth_token",
            "Content-Type: application/json"
        ];

        // Fazendo a requisição POST usando CURL
        $response = $this->curl->simple_post($url, json_encode($data), $headers);

        if ($response === false) {
            $this->_send_response(500, ['error' => 'Erro ao acessar o serviço remoto']);
            return;
        }

        $this->_send_response(200, json_decode($response, true));
    }

    // Função para encaminhar a requisição PUT
    public function put($endpoint) {
        // Validação de entrada
        if (empty($endpoint) || !$this->_validate_json_input()) {
            $this->_send_response(400, ['error' => 'Dados inválidos ou endpoint não especificado']);
            return;
        }

        $url = 'https://api.exemplo.com/' . $endpoint;

        // Autenticação
        $auth_token = $this->_get_auth_token();
        if (!$auth_token) {
            $this->_send_response(401, ['error' => 'Autenticação necessária']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);

        // Configurações para a requisição PUT
        $headers = [
            "Authorization: Bearer $auth_token",
            "Content-Type: application/json"
        ];

        // Fazendo a requisição PUT usando CURL
        $response = $this->curl->simple_put($url, json_encode($data), $headers);

        if ($response === false) {
            $this->_send_response(500, ['error' => 'Erro ao acessar o serviço remoto']);
            return;
        }

        $this->_send_response(200, json_decode($response, true));
    }

    // Função para encaminhar a requisição DELETE
    public function delete($endpoint) {
        // Validação de entrada
        if (empty($endpoint)) {
            $this->_send_response(400, ['error' => 'Endpoint não especificado']);
            return;
        }

        $url = 'https://api.exemplo.com/' . $endpoint;

        // Autenticação
        $auth_token = $this->_get_auth_token();
        if (!$auth_token) {
            $this->_send_response(401, ['error' => 'Autenticação necessária']);
            return;
        }

        // Configurações para a requisição DELETE
        $headers = [
            "Authorization: Bearer $auth_token"
        ];

        // Fazendo a requisição DELETE usando CURL
        $response = $this->curl->simple_delete($url, $headers);

        if ($response === false) {
            $this->_send_response(500, ['error' => 'Erro ao acessar o serviço remoto']);
            return;
        }

        $this->_send_response(200, json_decode($response, true));
    }

    // Função auxiliar para enviar a resposta com status e dados
    private function _send_response($status, $data) {
        $this->output->set_status_header($status);
        $this->output->set_content_type('application/json');
        echo json_encode($data);
    }

    // Função para verificar a autenticação (token Bearer)
    private function _get_auth_token() {
        $headers = $this->input->request_headers();
        if (isset($headers['Authorization'])) {
            $auth_header = $headers['Authorization'];
            // Verificar se o header contém o token Bearer
            if (preg_match('/Bearer\s(\S+)/', $auth_header, $matches)) {
                return $matches[1]; // Retorna o token
            }
        }
        return false;
    }

    // Função para validar se os dados do corpo da requisição são JSON
    private function _validate_json_input() {
        $data = json_decode(file_get_contents('php://input'), true);
        return (json_last_error() === JSON_ERROR_NONE); // Retorna true se o JSON for válido
    }
    
    public function get_url_from_db($id) {
            // Buscar o valor da URL com base no id
            $url = $this->Url_model->get_url($id);
    
            if ($url) {
                echo "URL encontrada: " . $url;
            } else {
                echo "URL não encontrada para o ID fornecido.";
            }
        }
    
    public function get_proxy_data($id) {
        // Buscar o URL do banco de dados
        $url = $this->Url_model->get_url($id);
    
        if ($url) {
            // Agora você pode usar o valor de $url para realizar a requisição
            $response = $this->curl->simple_get($url);
    
            if ($response === false) {
                $this->_send_response(500, ['error' => 'Erro ao acessar o serviço remoto']);
            } else {
                $this->_send_response(200, json_decode($response, true));
            }
        } else {
            $this->_send_response(404, ['error' => 'URL não encontrada']);
        }
    }
}
