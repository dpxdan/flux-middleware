<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Auth_api extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Auth_model');
    }

    /**
     * Função para verificar as credenciais via Basic Auth
     */
    public function verificarAutenticacao() {
        // Obter o cabeçalho de autorização (Authorization)
        $authorization = $this->input->get_request_header('Authorization');

        if (!$authorization) {
            show_error('Credenciais não fornecidas', 401, 'Unauthorized');
        }

        // O cabeçalho deve ser no formato: Basic <base64_encode(usuario:senha)>
        if (strpos($authorization, 'Basic ') !== 0) {
            show_error('Formato de autenticação inválido', 401, 'Unauthorized');
        }

        // Extrair a string codificada em base64
        $encoded_credentials = substr($authorization, 6);
        $decoded_credentials = base64_decode($encoded_credentials);
        
        // Separar o usuário e a senha
        list($usuario, $senha) = explode(":", $decoded_credentials);

        // Verificar as credenciais no banco de dados
        $usuarioValido = $this->Auth_model->verify_login($usuario, $senha);

        if (!$usuarioValido) {
            show_error('Usuário ou senha inválidos', 401, 'Unauthorized');
        }

        // Caso as credenciais sejam válidas, retorna os dados do usuário
        return $usuarioValido;  // Ou você pode fazer um redirect ou continuar o processo.
    }
}
