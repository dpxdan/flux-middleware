<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once APPPATH . 'third_party/JWT/JWT.php';

use \Firebase\JWT\JWT;

class JwtHelper {
    private static $secret_key = 'Flux223304442Dev2516'; // Altere para um segredo forte
    private static $algoritmo = 'HS256';

    /**
     * Gera um token JWT
     */
    public static function gerarToken($dados, $expiracao = 3600) {
        $payload = [
            'iat' => time(),
            'exp' => time() + $expiracao,
            'dados' => $dados
        ];
        return JWT::encode($payload, self::$secret_key, self::$algoritmo);
    }

    /**
     * Valida um token JWT
     */
    public static function validarToken($token) {
        try {
            return JWT::decode($token, self::$secret_key, [self::$algoritmo]);
        } catch (Exception $e) {
            return null;
        }
    }
}
