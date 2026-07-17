<?php

use Adianti\Core\AdiantiApplicationConfig;
use Adianti\Core\AdiantiCoreApplication;

// initialization script
require_once 'init.php';

/* ----------------------------------------------------------------- CORS */
$ini_cors = AdiantiApplicationConfig::get();
$allowed_origins = $ini_cors['security']['cors_allowed_origins'] ?? [];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

header('Content-Type: application/json; charset=utf-8');
header('Vary: Origin');

if (!empty($allowed_origins) && in_array('*', $allowed_origins, true)) {
    // explicitamente liberado (dev) — eco do Origin, NÃO usa "*" com credentials
    if ($origin !== '') header('Access-Control-Allow-Origin: ' . $origin);
    else header('Access-Control-Allow-Origin: *');
} elseif ($origin !== '' && in_array($origin, $allowed_origins, true)) {
    header('Access-Control-Allow-Origin: ' . $origin);
} elseif (empty($allowed_origins)) {
    // sem configuração explícita — modo legado em dev, restrito em produção
    if (!empty($ini_cors['general']['debug']) && $ini_cors['general']['debug'] === '1') {
        if ($origin !== '') header('Access-Control-Allow-Origin: ' . $origin);
    }
    // em produção sem config: nenhum header CORS = navegador bloqueia (apps nativos não são afetados)
}

header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Max-Age: 600');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit();
}

class AdiantiRestServer
{
    public static function run($request)
    {
        $ini      = AdiantiApplicationConfig::get();
        $input    = json_decode(file_get_contents("php://input"), true);
        $request  = array_merge($request, (array) $input);
        $class    = isset($request['class'])  ? $request['class']  : '';
        $method   = isset($request['method']) ? $request['method'] : '';
        $headers  = AdiantiCoreApplication::getHeaders();
        $response = NULL;

        $headers['Authorization'] = $headers['Authorization'] ?? ($headers['authorization'] ?? null);

        try
        {
            $is_login    = ($class === 'ApplicationAuthenticationRestService' && $method === 'login');
            $is_refresh  = ($class === 'ApplicationAuthenticationRestService' && $method === 'refreshToken');

            if ($is_login || $is_refresh) {
                // public — autenticação acontece dentro do próprio service
            }
            else if (empty($headers['Authorization']))
            {
                http_response_code(401);
                return json_encode(['status' => 'error', 'data' => 'Authorization required']);
            }
            else
            {
                $auth = $headers['Authorization'];
                if (substr($auth, 0, 5) === 'Basic')
                {
                    if (empty($ini['general']['rest_key'])) {
                        http_response_code(500);
                        return json_encode(['status' => 'error', 'data' => 'REST key not defined']);
                    }
                    if ($ini['general']['rest_key'] !== substr($auth, 6)) {
                        http_response_code(401);
                        return json_encode(['status' => 'error', 'data' => 'Authorization error']);
                    }
                }
                else if (substr($auth, 0, 6) === 'Bearer')
                {
                    $token = substr($auth, 7);
                    $validation = ApplicationAuthenticationRestService::validateToken(['token' => $token]);
                    if (empty($validation['success'])) {
                        http_response_code(401);
                        return json_encode(['status' => 'error', 'data' => $validation['message'] ?? 'Token inválido']);
                    }
                    $request['_auth'] = $validation['user'];
                }
                else
                {
                    http_response_code(401);
                    return json_encode(['status' => 'error', 'data' => 'Authorization scheme not supported']);
                }
            }

            if (class_exists($class) && method_exists($class, $method))
            {
                $response = call_user_func([$class, $method], $request);
            }
            else
            {
                http_response_code(404);
                return json_encode(['status' => 'error', 'data' => 'Endpoint not found']);
            }

            if (is_array($response)) {
                array_walk_recursive($response, ['AdiantiStringConversion', 'assureUnicode']);
            }
            return json_encode(['status' => 'success', 'data' => $response]);
        }
        catch (Exception $e)
        {
            if (200 === http_response_code()) http_response_code(500);
            $debug = !empty($ini['general']['debug']) && $ini['general']['debug'] === '1';
            return json_encode(['status' => 'error', 'data' => $debug ? $e->getMessage() : 'Erro interno']);
        }
        catch (Error $e)
        {
            if (200 === http_response_code()) http_response_code(500);
            $debug = !empty($ini['general']['debug']) && $ini['general']['debug'] === '1';
            return json_encode(['status' => 'error', 'data' => $debug ? $e->getMessage() : 'Erro interno']);
        }
    }
}

print AdiantiRestServer::run($_REQUEST);
