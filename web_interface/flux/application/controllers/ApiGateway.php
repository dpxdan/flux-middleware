<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class ApiGateway extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Api_model'); // Carrega o model para buscar URLs da API
        $this->load->library('flux_log');
    }

    /**
     * Encaminha requisições para um endpoint externo baseado no nome da API
     * @param string $api_nome - Nome da API no banco de dados
     * @param string $endpoint - Caminho do endpoint de destino
     */
    public function route($api_nome = '', $endpoint = '') {
        if (empty($api_nome)) {
            show_error("Nome da API não foi especificado.", 400);
            return;
        }

        // Buscar a URL base no banco de dados
        $base_url = $this->Api_model->getApiBaseUrl($api_nome);
        $this->flux_log->write_log ( 'ApiGateway', json_encode($base_url) );

        if (!$base_url) {
            show_error("API não encontrada no banco de dados.", 404);
            return;
        }

        // Construir a URL completa
        $targetUrl = rtrim($base_url, '/') . '/' . ltrim($endpoint, '/');

        // Obter método da requisição
        $method = $this->input->server('REQUEST_METHOD');

        // Obter headers originais
        $headers = $this->_getRequestHeaders();

        // Obter dados do corpo da requisição (para POST, PUT, DELETE)
        $body = file_get_contents('php://input');

        // Fazer a requisição para o destino
        $response = $this->_sendRequest($targetUrl, $method, $headers, $body);

        // Definir headers de resposta
        header("Content-Type: application/json");
        echo $response;
    }

    private function _getRequestHeaders() {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $header = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
                $headers[] = "$header: $value";
            }
        }
        return $headers;
    }

    private function _sendRequest($url, $method, $headers, $body) {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        if ($method !== 'GET') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }
    
    
    /**
     * Função para proteger a rota com Basic Auth
     */
    private function validarAutenticacao() {
        // Chama a função de autenticação Basic Auth da Controller Auth
        $this->load->controller('Auth_api', 'verificarAutenticacao'); 
    
        // Chama o método de verificação de autenticação (Basic Auth)
        $usuarioValido = $this->Auth_api->verificarAutenticacao();
    
        if (!$usuarioValido) {
            show_error('Autenticação falhou', 401, 'Unauthorized');
        }
    
        return $usuarioValido;
    }
    
    /**
     * Rota de exemplo protegida por Basic Auth
     */
    public function rotaProtegida() {
        // Valida a autenticação antes de acessar a API
        $this->validarAutenticacao();
    
        // Se a autenticação for bem-sucedida, o código continua aqui
        echo json_encode(['message' => 'Acesso autorizado']);
    }
}
