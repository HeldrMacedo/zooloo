<?php

use Adianti\Core\AdiantiApplicationConfig;
use Adianti\Database\TTransaction;
use Adianti\Database\TCriteria;
use Adianti\Database\TFilter;
use Adianti\Database\TRepository;
use Adianti\Service\AdiantiRestService;
use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

/**
 * Serviço REST de autenticação móvel (Etapa 2).
 *
 * Modelo de tokens:
 *   - access  : JWT curto (15 min). Usado em Authorization: Bearer. Contém `jti`.
 *   - refresh : JWT longo (30 dias). Persistido em `mob_auth_token` (revogável).
 *               Usado apenas no método `refreshToken` para emitir novo par.
 *
 * Compatibilidade: o método `login` continua devolvendo `token`/`expires_at`
 * (= access-token) como antes; campos novos: `refresh_token`,
 * `refresh_expires_at`, `vendedor`, `permissoes`.
 */
class ApplicationAuthenticationRestService implements AdiantiRestService
{
    const ACCESS_TTL_SECONDS  = 86400;      // 24 horas
    const REFRESH_TTL_SECONDS = 2592000;    // 30 dias
    const GENERIC_INVALID_MSG = 'Não foi possível autenticar. Verifique suas credenciais.';

    /* ----------------------------------------------------------------- helpers */

    private static function jwtKey()
    {
        $ini = AdiantiApplicationConfig::get();
        if (empty($ini['general']['seed'])) {
            throw new Exception('Application seed not defined');
        }
        return APPLICATION_NAME . $ini['general']['seed'];
    }

    private static function clientIp()
    {
        return $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    private static function clientUserAgent()
    {
        return substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
    }

    private static function newJti()
    {
        if (function_exists('random_bytes')) {
            return bin2hex(random_bytes(16));
        }
        return bin2hex(openssl_random_pseudo_bytes(16));
    }

    private static function encodeJwt(array $payload)
    {
        return JWT::encode($payload, self::jwtKey(), 'HS256');
    }

    private static function decodeJwt($token)
    {
        return (array) JWT::decode($token, new Key(self::jwtKey(), 'HS256'));
    }

    private static function buildAccessPayload($user, $jti)
    {
        $now = time();
        return [
            'jti'       => $jti,
            'iat'       => $now,
            'exp'       => $now + self::ACCESS_TTL_SECONDS,
            'expires'   => $now + self::ACCESS_TTL_SECONDS, // compat retro
            'type'      => 'access',
            'user'      => $user->login,
            'userid'    => $user->id,
            'username'  => $user->name,
            'usermail'  => $user->email,
        ];
    }

    private static function buildRefreshPayload($user, $jti)
    {
        $now = time();
        return [
            'jti'    => $jti,
            'iat'    => $now,
            'exp'    => $now + self::REFRESH_TTL_SECONDS,
            'type'   => 'refresh',
            'userid' => $user->id,
            'user'   => $user->login,
        ];
    }

    /**
     * Persiste registro do token na base. Caller deve estar dentro de transação 'permission'
     * OU passar $open_tx=true.
     */
    private static function persistToken($jti, $user_id, $type, $expires_unix, $parent_jti = null, $open_tx = true)
    {
        if ($open_tx) TTransaction::open('permission');
        try {
            $rec = new MobAuthToken;
            $rec->jti            = $jti;
            $rec->user_id        = $user_id;
            $rec->token_type     = $type;
            $rec->parent_jti     = $parent_jti;
            $rec->issued_at      = date('Y-m-d H:i:s');
            $rec->expires_at     = date('Y-m-d H:i:s', $expires_unix);
            $rec->revoked        = 'f';
            $rec->user_agent     = self::clientUserAgent();
            $rec->ip_address     = self::clientIp();
            $rec->store();
        } finally {
            if ($open_tx) TTransaction::close();
        }
    }

    /**
     * Resolve vendedor + permissões a partir do system_user.id.
     * Retorna null se não houver cad_vendedor vinculado.
     * Caller deve estar dentro de transação 'permission'.
     */
    private static function resolveVendedor($user_id)
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('usuario_id', '=', $user_id));
        $repo = new TRepository('Vendedor');
        $rows = $repo->load($criteria);
        if (empty($rows)) return null;
        $v = $rows[0];

        $vendedor = [
            'vendedor_id'   => (int) $v->vendedor_id,
            'area_id'       => isset($v->area_id) ? (int) $v->area_id : null,
            'coletor_id'    => isset($v->coletor_id) ? (int) $v->coletor_id : null,
            'nome'          => $v->nome,
            'comissao'      => isset($v->comissao) ? (float) $v->comissao : 0.0,
            'limite_venda'  => isset($v->limite_venda) ? (float) $v->limite_venda : 0.0,
            'tipo_limite'   => $v->tipo_limite ?? null,
            'treinamento'   => $v->treinamento ?? 'N',
            'ativo'         => $v->ativo ?? 'N',
        ];
        $permissoes = [
            'exibe_comissao'                   => $v->exibe_comissao ?? 'N',
            'exibe_premiacao'                  => $v->exibe_premiacao ?? 'N',
            'pode_cancelar'                    => $v->pode_cancelar ?? 'N',
            'pode_cancelar_qtde'               => isset($v->pode_cancelar_qtde) ? (int) $v->pode_cancelar_qtde : 0,
            'pode_cancelar_tempo'              => $v->pode_cancelar_tempo ?? null,
            'pode_reimprimir'                  => $v->pode_reimprimir ?? 'N',
            'pode_reimprimir_qtde'             => isset($v->pode_reimprimir_qtde) ? (int) $v->pode_reimprimir_qtde : 0,
            'pode_reimprimir_tempo'            => $v->pode_reimprimir_tempo ?? null,
            'pode_reimprimir_outro'            => $v->pode_reimprimir_outro ?? 'N',
            'pode_reimprimir_sort_pago'        => $v->pode_reimprimir_sort_pago ?? 'N',
            'pode_reimprimir_sort_naopg'       => $v->pode_reimprimir_sort_naopg ?? 'N',
            'pode_reimprimir_sort_pago_outro'  => $v->pode_reimprimir_sort_pago_outro ?? 'N',
            'pode_reimprimir_sort_naopg_outro' => $v->pode_reimprimir_sort_naopg_outro ?? 'N',
            'pode_pagar'                       => $v->pode_pagar ?? 'N',
            'pode_pagar_outro'                 => $v->pode_pagar_outro ?? 'N',
        ];
        return ['vendedor' => $vendedor, 'permissoes' => $permissoes];
    }

    /* ----------------------------------------------------------------- endpoints */

    /**
     * Login mobile.
     * Request: { data: { login, password } }
     */
    public static function login($param)
    {
        $login_raw = trim((string)($param['data']['login']    ?? ''));
        $password  = (string)($param['data']['password'] ?? '');
        $ip        = self::clientIp();

        if ($login_raw === '' || $password === '') {
            return ['success' => false, 'message' => self::GENERIC_INVALID_MSG];
        }

        // rate-limit (sempre antes de tocar no banco)
        if (class_exists('AuthRateLimiter')) {
            $rl = AuthRateLimiter::check($login_raw, $ip);
            if (!$rl['allowed']) {
                http_response_code(429);
                return [
                    'success'     => false,
                    'message'     => 'Muitas tentativas. Tente novamente em ' . max(1, (int) ceil($rl['retry_after'] / 60)) . ' minuto(s).',
                    'retry_after' => $rl['retry_after'],
                ];
            }
        }

        try {
            $user = ApplicationAuthenticationService::authenticate($login_raw, $password, false);
        } catch (Exception $e) {
            $user = null;
        }

        // Mensagem genérica para qualquer falha (mitiga user enumeration).
        if (!$user || $user->active !== 'Y') {
            if (class_exists('AuthRateLimiter')) AuthRateLimiter::registerFailure($login_raw, $ip);
            return ['success' => false, 'message' => self::GENERIC_INVALID_MSG];
        }

        try {
            $access_jti  = self::newJti();
            $refresh_jti = self::newJti();
            $now         = time();
            $access      = self::encodeJwt(self::buildAccessPayload($user,  $access_jti));
            $refresh     = self::encodeJwt(self::buildRefreshPayload($user, $refresh_jti));

            TTransaction::open('permission');
            self::persistToken($access_jti,  $user->id, 'access',  $now + self::ACCESS_TTL_SECONDS,  null, false);
            self::persistToken($refresh_jti, $user->id, 'refresh', $now + self::REFRESH_TTL_SECONDS, null, false);
            $vendedor_data = self::resolveVendedor($user->id);
            TTransaction::close();

            if (class_exists('AuthRateLimiter')) AuthRateLimiter::registerSuccess($login_raw, $ip);
            if (class_exists('SystemAccessLogService')) {
                try { SystemAccessLogService::registerLogin(); } catch (Throwable $e) { /* não-fatal */ }
            }

            $response = [
                'success'             => true,
                'message'             => 'Login realizado com sucesso',
                'user' => [
                    'id'     => $user->id,
                    'login'  => $user->login,
                    'name'   => $user->name,
                    'email'  => $user->email,
                    'active' => $user->active,
                ],
                'token'               => $access,
                'expires_at'          => date('Y-m-d H:i:s', $now + self::ACCESS_TTL_SECONDS),
                'refresh_token'       => $refresh,
                'refresh_expires_at'  => date('Y-m-d H:i:s', $now + self::REFRESH_TTL_SECONDS),
            ];
            if ($vendedor_data) {
                $response['vendedor']   = $vendedor_data['vendedor'];
                $response['permissoes'] = $vendedor_data['permissoes'];
            } else {
                $response['vendedor']   = null;
                $response['permissoes'] = null;
                $response['warning']    = 'Usuário sem cad_vendedor vinculado.';
            }
            return $response;
        } catch (Exception $e) {
            if (TTransaction::get()) TTransaction::rollback();
            return ['success' => false, 'message' => 'Falha interna na autenticação.'];
        }
    }

    /**
     * Valida access-token. Usado pelo rest.php em cada request Bearer.
     * Aceita token em $param['token'] (legado) ou já decodificado por rest.php.
     */
    public static function validateToken($param)
    {
        $token = $param['token'] ?? '';
        if (empty($token)) {
            return ['success' => false, 'message' => 'Token é obrigatório'];
        }
        try {
            $decoded = self::decodeJwt($token);
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Token inválido'];
        }

        $exp = $decoded['exp'] ?? $decoded['expires'] ?? 0;
        if ($exp < time()) {
            return ['success' => false, 'message' => 'Token expirado'];
        }
        if (($decoded['type'] ?? 'access') !== 'access') {
            return ['success' => false, 'message' => 'Tipo de token inválido para esta operação'];
        }

        $jti = $decoded['jti'] ?? null;
        if ($jti) {
            try {
                TTransaction::open('permission');
                $usable = MobAuthToken::isUsable($jti, 'access');
                TTransaction::close();
                if (!$usable) {
                    return ['success' => false, 'message' => 'Token revogado'];
                }
            } catch (Exception $e) {
                // base offline: cai para validação JWT-only (não-bloqueante)
            }
        }

        return [
            'success'    => true,
            'message'    => 'Token válido',
            'user' => [
                'id'    => $decoded['userid']   ?? null,
                'login' => $decoded['user']     ?? null,
                'name'  => $decoded['username'] ?? null,
                'email' => $decoded['usermail'] ?? null,
            ],
            'expires_at' => date('Y-m-d H:i:s', $exp),
        ];
    }

    /**
     * Rotaciona refresh-token. Aceita refresh em $param['refresh_token'].
     * Fallback de compatibilidade: aceita $param['token'] mas só renova se for refresh válido.
     */
    public static function refreshToken($param)
    {
        $refresh = $param['refresh_token'] ?? $param['token'] ?? '';
        if (empty($refresh)) {
            return ['success' => false, 'message' => 'Refresh token é obrigatório'];
        }
        try {
            $decoded = self::decodeJwt($refresh);
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Refresh token inválido'];
        }
        if (($decoded['type'] ?? null) !== 'refresh') {
            return ['success' => false, 'message' => 'Tipo de token inválido'];
        }
        $exp = $decoded['exp'] ?? 0;
        if ($exp < time()) {
            return ['success' => false, 'message' => 'Refresh token expirado. Faça login novamente.'];
        }
        $jti = $decoded['jti'] ?? null;
        $userid = $decoded['userid'] ?? null;
        if (!$jti || !$userid) {
            return ['success' => false, 'message' => 'Refresh token inválido'];
        }

        try {
            TTransaction::open('permission');

            if (!MobAuthToken::isUsable($jti, 'refresh')) {
                // possível replay: revoga toda a árvore desse usuário
                MobAuthToken::revokeAllForUser($userid, 'reuse_detected');
                TTransaction::close();
                return ['success' => false, 'message' => 'Refresh token revogado. Faça login novamente.'];
            }

            $user = SystemUser::find($userid);
            if (!$user || $user->active !== 'Y') {
                TTransaction::close();
                return ['success' => false, 'message' => 'Usuário inativo'];
            }

            // rotação: revoga refresh antigo, emite novo par
            $old = MobAuthToken::find($jti);
            if ($old) $old->revoke('rotated');

            $now             = time();
            $new_access_jti  = self::newJti();
            $new_refresh_jti = self::newJti();
            $access          = self::encodeJwt(self::buildAccessPayload($user,  $new_access_jti));
            $new_refresh     = self::encodeJwt(self::buildRefreshPayload($user, $new_refresh_jti));

            self::persistToken($new_access_jti,  $user->id, 'access',  $now + self::ACCESS_TTL_SECONDS,  $jti, false);
            self::persistToken($new_refresh_jti, $user->id, 'refresh', $now + self::REFRESH_TTL_SECONDS, $jti, false);

            TTransaction::close();

            return [
                'success'            => true,
                'message'            => 'Token renovado com sucesso',
                'token'              => $access,
                'expires_at'         => date('Y-m-d H:i:s', $now + self::ACCESS_TTL_SECONDS),
                'refresh_token'      => $new_refresh,
                'refresh_expires_at' => date('Y-m-d H:i:s', $now + self::REFRESH_TTL_SECONDS),
            ];
        } catch (Exception $e) {
            if (TTransaction::get()) TTransaction::rollback();
            return ['success' => false, 'message' => 'Erro ao renovar token'];
        }
    }

    /**
     * Logout: revoga access (se vier) e refresh (se vier).
     * Idempotente — sempre retorna success.
     */
    public static function logout($param)
    {
        $tokens_to_check = [];
        foreach (['token', 'refresh_token'] as $field) {
            if (!empty($param[$field])) $tokens_to_check[] = $param[$field];
        }

        if (empty($tokens_to_check)) {
            return ['success' => true, 'message' => 'Logout realizado'];
        }

        try {
            TTransaction::open('permission');
            foreach ($tokens_to_check as $tk) {
                try {
                    $decoded = self::decodeJwt($tk);
                    $jti = $decoded['jti'] ?? null;
                    if ($jti) {
                        $rec = MobAuthToken::find($jti);
                        if ($rec && ($rec->revoked === 'f' || $rec->revoked === false)) {
                            $rec->revoke('logout');
                        }
                    }
                } catch (Exception $e) {
                    // token inválido — ignora
                }
            }
            TTransaction::close();
        } catch (Exception $e) {
            if (TTransaction::get()) TTransaction::rollback();
        }

        if (class_exists('SystemAccessLogService')) {
            try { SystemAccessLogService::registerLogout(); } catch (Throwable $e) {}
        }

        return ['success' => true, 'message' => 'Logout realizado com sucesso'];
    }

    /**
     * Logout em todos os dispositivos.
     * Requer Bearer válido (rest.php já validou e injetou _auth).
     */
    public static function logoutAll($param)
    {
        $userid = $param['_auth']['id'] ?? null;
        if (!$userid) {
            return ['success' => false, 'message' => 'Não autenticado'];
        }
        try {
            TTransaction::open('permission');
            MobAuthToken::revokeAllForUser($userid, 'logout_all');
            TTransaction::close();
            return ['success' => true, 'message' => 'Sessões revogadas'];
        } catch (Exception $e) {
            if (TTransaction::get()) TTransaction::rollback();
            return ['success' => false, 'message' => 'Erro ao revogar sessões'];
        }
    }

    /**
     * Método legado mantido para retro-compat com /rest examples.
     */
    public static function getToken($param)
    {
        $result = self::login($param);
        if (!empty($result['success'])) return $result['token'];
        throw new Exception($result['message']);
    }
}
