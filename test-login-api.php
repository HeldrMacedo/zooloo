<?php
/**
 * Arquivo de teste para a API de Login
 * Execute este arquivo no navegador para testar os endpoints
 */

require_once 'rest/request.php';

echo "<h1>Teste da API de Login - Zooloo</h1>";

$base_url = 'http://localhost/zooloo/rest.php';

// Teste 1: Login
echo "<h2>1. Teste de Login</h2>";
try {
    $login_params = array(
        'class' => 'ApplicationAuthenticationRestService',
        'method' => 'login',
        'login' => 'admin',
        'password' => 'admin'
    );
    
    $login_result = request($base_url, 'POST', $login_params);
    echo "<pre>";
    print_r($login_result);
    echo "</pre>";
    
    if (isset($login_result['token'])) {
        $token = $login_result['token'];
        echo "<p><strong>Token obtido:</strong> " . substr($token, 0, 50) . "...</p>";
        
        // Teste 2: Validar Token
        echo "<h2>2. Teste de Validação de Token</h2>";
        try {
            $validate_params = array(
                'class' => 'ApplicationAuthenticationRestService',
                'method' => 'validateToken',
                'token' => $token
            );
            
            $validate_result = request($base_url, 'POST', $validate_params, 'Bearer ' . $token);
            echo "<pre>";
            print_r($validate_result);
            echo "</pre>";
        } catch (Exception $e) {
            echo "<p style='color: red;'>Erro na validação: " . $e->getMessage() . "</p>";
        }
        
        // Teste 3: Refresh Token
        echo "<h2>3. Teste de Refresh Token</h2>";
        try {
            $refresh_params = array(
                'class' => 'ApplicationAuthenticationRestService',
                'method' => 'refreshToken',
                'token' => $token
            );
            
            $refresh_result = request($base_url, 'POST', $refresh_params, 'Bearer ' . $token);
            echo "<pre>";
            print_r($refresh_result);
            echo "</pre>";
            
            if (isset($refresh_result['token'])) {
                $new_token = $refresh_result['token'];
                echo "<p><strong>Novo token:</strong> " . substr($new_token, 0, 50) . "...</p>";
                
                // Teste 4: Logout
                echo "<h2>4. Teste de Logout</h2>";
                try {
                    $logout_params = array(
                        'class' => 'ApplicationAuthenticationRestService',
                        'method' => 'logout',
                        'token' => $new_token
                    );
                    
                    $logout_result = request($base_url, 'POST', $logout_params, 'Bearer ' . $new_token);
                    echo "<pre>";
                    print_r($logout_result);
                    echo "</pre>";
                } catch (Exception $e) {
                    echo "<p style='color: red;'>Erro no logout: " . $e->getMessage() . "</p>";
                }
            }
        } catch (Exception $e) {
            echo "<p style='color: red;'>Erro no refresh: " . $e->getMessage() . "</p>";
        }
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Erro no login: " . $e->getMessage() . "</p>";
}

// Teste 5: Login com credenciais inválidas
echo "<h2>5. Teste de Login com Credenciais Inválidas</h2>";
try {
    $invalid_login_params = array(
        'class' => 'ApplicationAuthenticationRestService',
        'method' => 'login',
        'login' => 'usuario_inexistente',
        'password' => 'senha_errada'
    );
    
    $invalid_result = request($base_url, 'POST', $invalid_login_params);
    echo "<pre>";
    print_r($invalid_result);
    echo "</pre>";
} catch (Exception $e) {
    echo "<p style='color: red;'>Erro esperado: " . $e->getMessage() . "</p>";
}

echo "<h2>Testes Concluídos</h2>";
echo "<p>Verifique os resultados acima para confirmar se a API está funcionando corretamente.</p>";
?>