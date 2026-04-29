<?php

use Adianti\Core\AdiantiApplicationConfig;
use Adianti\Core\AdiantiCoreApplication;

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// initialization script
require_once 'init.php';

class AdiantiRestServer
{
    public static function run($request)
    {
        $ini      = AdiantiApplicationConfig::get();
        $input    = json_decode(file_get_contents("php://input"), true);
        $request  = array_merge($request, (array) $input);
        $class    = isset($request['class']) ? $request['class']   : '';
        $method   = isset($request['method']) ? $request['method'] : '';
        $headers  = AdiantiCoreApplication::getHeaders();
        $response = NULL;
        
        $headers['Authorization'] = $headers['Authorization'] ?? ($headers['authorization'] ?? null);
        
        try
        {
            // Para métodos de login, não exigir autorização
            if ($class === 'ApplicationAuthenticationRestService' && $method === 'login')
            {
                // Login não precisa de autorização prévia
            }
            else if (empty($headers['Authorization']))
            {
                throw new Exception('Authorization required');
            }
            else
            {
                if (substr($headers['Authorization'], 0, 5) == 'Basic')
                {
                    if (empty($ini['general']['rest_key']))
                    {
                        throw new Exception('REST key not defined');
                    }
                    
                    if ($ini['general']['rest_key'] !== substr($headers['Authorization'], 6))
                    {
                        http_response_code(401);
                        return json_encode(array('status' => 'error', 'data' => 'Authorization error'));
                    }
                }
                else if (substr($headers['Authorization'], 0, 6) == 'Bearer')
                {
                    // Validar token JWT usando o serviço de autenticação
                    $token = substr($headers['Authorization'], 7);
                    $validation = ApplicationAuthenticationRestService::validateToken(['token' => $token]);

                    if (!$validation['success'])
                    {
                        http_response_code(401);
                        return json_encode(array('status' => 'error', 'data' => $validation['message']));
                    }

                    // Injeta dados do usuário autenticado no request para uso nos services
                    $request['_auth'] = $validation['user'];
                }
                else
                {
                    http_response_code(403);
                    throw new Exception('Authorization error');
                }
            }
            
            // Executar método da classe
            if (class_exists($class) && method_exists($class, $method))
            {
                $response = call_user_func(array($class, $method), $request);
            }
            else
            {
                throw new Exception("Class {$class} or method {$method} not found");
            }
            
            if (is_array($response))
            {
                array_walk_recursive($response, ['AdiantiStringConversion', 'assureUnicode']);
            }
            return json_encode(array('status' => 'success', 'data' => $response));
        }
        catch (Exception $e)
        {
            if (200 === http_response_code())
            {
                http_response_code(500);
            }
            return json_encode(array('status' => 'error', 'data' => $e->getMessage()));
        }
        catch (Error $e)
        {
            if (200 === http_response_code())
            {
                http_response_code(500);
            }
            return json_encode(array('status' => 'error', 'data' => $e->getMessage()));
        }
    }
}

print AdiantiRestServer::run($_REQUEST);