<?php

use Adianti\Core\AdiantiApplicationConfig;
use Adianti\Service\AdiantiRestService;
use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

class ApplicationAuthenticationRestService implements AdiantiRestService
{
    /**
     * Método de login para aplicativos móveis
     */
    public static function login($param)
    {
        try
        {   
            //echo json_encode($param);
            // Validar parâmetros obrigatórios
            if (empty($param['data']['login']) || empty($param['data']['password']))
            {
                return array(
                    'success' => false,
                    'message' => 'Login e senha são obrigatórios'
                );
            }
            
            // Autenticar usuário
            $user = ApplicationAuthenticationService::authenticate($param['data']['login'], $param['data']['password'], false);
            
            if (!$user)
            {
                return array(
                    'success' => false,
                    'message' => 'Credenciais inválidas'
                );
            }
            
            // Verificar se usuário está ativo
            if ($user->active !== 'Y')
            {
                return array(
                    'success' => false,
                    'message' => 'Usuário inativo'
                );
            }
            
            $ini = AdiantiApplicationConfig::get();
            $key = APPLICATION_NAME . $ini['general']['seed'];
            
            if (empty($ini['general']['seed']))
            {
                return array(
                    'success' => false,
                    'message' => 'Application seed not defined'
                );
            }
            
            // Criar payload do token
            $token_payload = array(
                "user" => $param['data']['login'],
                "userid" => $user->id,
                "username" => $user->name,
                "usermail" => $user->email,
                "issued_at" => time(),
                "expires" => strtotime("+ 1 hour") // Token válido por 1 hora
            );
            
            // Gerar token JWT
            $token = JWT::encode($token_payload, $key, 'HS256');
            
            // Registrar login se o serviço existir
            if (class_exists('SystemAccessLogService'))
            {
                SystemAccessLogService::registerLogin();
            }
            
            // Retornar dados do usuário e token
            return array(
                'success' => true,
                'message' => 'Login realizado com sucesso',
                'user' => array(
                    'id' => $user->id,
                    'login' => $user->login,
                    'name' => $user->name,
                    'email' => $user->email,
                    'active' => $user->active
                ),
                'token' => $token,
                'expires_at' => date('Y-m-d H:i:s', $token_payload['expires'])
            );
        }
        catch (Exception $e)
        {
            return array(
                'success' => false,
                'message' => $e->getMessage()
            );
        }
    }
    
    /**
     * Método para validar token
     */
    public static function validateToken($param)
    {
        try
        {
            if (empty($param['token']))
            {
                return array(
                    'success' => false,
                    'message' => 'Token é obrigatório'
                );
            }
            
            $ini = AdiantiApplicationConfig::get();
            $key = APPLICATION_NAME . $ini['general']['seed'];
            
            if (empty($ini['general']['seed']))
            {
                return array(
                    'success' => false,
                    'message' => 'Application seed not defined'
                );
            }
            
            // Decodificar token
            $decoded = (array) JWT::decode($param['token'], new Key($key, 'HS256'));
            
            // Verificar se token não expirou
            if ($decoded['expires'] < time())
            {
                return array(
                    'success' => false,
                    'message' => 'Token expirado'
                );
            }
            
            return array(
                'success' => true,
                'message' => 'Token válido',
                'user' => array(
                    'id' => $decoded['userid'],
                    'login' => $decoded['user'],
                    'name' => $decoded['username'],
                    'email' => $decoded['usermail']
                ),
                'expires_at' => date('Y-m-d H:i:s', $decoded['expires'])
            );
        }
        catch (Exception $e)
        {
            return array(
                'success' => false,
                'message' => 'Token inválido: ' . $e->getMessage()
            );
        }
    }
    
    /**
     * Método para refresh do token
     */
    public static function refreshToken($param)
    {
        try
        {
            if (empty($param['token']))
            {
                return array(
                    'success' => false,
                    'message' => 'Token é obrigatório'
                );
            }
            
            $ini = AdiantiApplicationConfig::get();
            $key = APPLICATION_NAME . $ini['general']['seed'];
            
            if (empty($ini['general']['seed']))
            {
                return array(
                    'success' => false,
                    'message' => 'Application seed not defined'
                );
            }
            
            // Decodificar token atual
            $decoded = (array) JWT::decode($param['token'], new Key($key, 'HS256'));

            if ($decoded['expires'] < time())
            {
                return array(
                    'success' => false,
                    'message' => 'Token expirado. Faça login novamente.'
                );
            }

            // Criar novo token com nova expiração
            $new_token_payload = array(
                "user" => $decoded['user'],
                "userid" => $decoded['userid'],
                "username" => $decoded['username'],
                "usermail" => $decoded['usermail'],
                "issued_at" => time(),
                "expires" => strtotime("+ 1 hour")
            );
            
            $new_token = JWT::encode($new_token_payload, $key, 'HS256');
            
            return array(
                'success' => true,
                'message' => 'Token renovado com sucesso',
                'token' => $new_token,
                'expires_at' => date('Y-m-d H:i:s', $new_token_payload['expires'])
            );
        }
        catch (Exception $e)
        {
            return array(
                'success' => false,
                'message' => 'Erro ao renovar token: ' . $e->getMessage()
            );
        }
    }
    
    /**
     * Método para logout
     */
    public static function logout($param)
    {
        try
        {
            // Registrar logout se token for válido
            if (!empty($param['token']))
            {
                try
                {
                    $ini = AdiantiApplicationConfig::get();
                    $key = APPLICATION_NAME . $ini['general']['seed'];
                    $decoded = (array) JWT::decode($param['token'], new Key($key, 'HS256'));
                    
                    // Simular sessão para registrar logout
                    if (class_exists('TSession'))
                    {
                        TSession::setValue('login', $decoded['user']);
                    }
                    
                    if (class_exists('SystemAccessLogService'))
                    {
                        SystemAccessLogService::registerLogout();
                    }
                }
                catch (Exception $e)
                {
                    // Token inválido, mas ainda assim processar logout
                }
            }
            
            return array(
                'success' => true,
                'message' => 'Logout realizado com sucesso'
            );
        }
        catch (Exception $e)
        {
            return array(
                'success' => false,
                'message' => $e->getMessage()
            );
        }
    }
    
    /**
     * Método legado para compatibilidade (usado pelos exemplos em /rest)
     */
    public static function getToken($param)
    {
        $result = self::login($param);
        
        if ($result['success'])
        {
            return $result['token'];
        }
        else
        {
            throw new Exception($result['message']);
        }
    }
}
