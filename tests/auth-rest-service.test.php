<?php

declare(strict_types=1);

/*
 * Testes do ApplicationAuthenticationRestService (Etapa 2).
 * Mocka Adianti (config, transação, repositório) para isolar a lógica de auth.
 */

namespace Adianti\Core {
    if (!class_exists(__NAMESPACE__ . '\\AdiantiApplicationConfig', false)) {
        class AdiantiApplicationConfig
        {
            public static array $config = ['general' => ['seed' => 'seed-teste']];
            public static function get(): array { return self::$config; }
        }
    }
}

namespace Adianti\Service {
    if (!interface_exists(__NAMESPACE__ . '\\AdiantiRestService', false)) {
        interface AdiantiRestService {}
    }
}

namespace Adianti\Database {
    if (!class_exists(__NAMESPACE__ . '\\TTransaction', false)) {
        class TTransaction
        {
            // No-op compatível com as demais suítes (get() retorna null).
            public static function open($name) {}
            public static function close() {}
            public static function rollback() {}
            public static function get() { return null; }
        }
    }
    if (!class_exists(__NAMESPACE__ . '\\TCriteria', false)) {
        // Superconjunto compatível com as suítes de Resultado (setProperty/setProperties/resetProperties).
        class TCriteria
        {
            public array $filters = [];
            public array $properties = [];
            public function add($filter) { $this->filters[] = $filter; }
            public function setProperty($k, $v) { $this->properties[$k] = $v; }
            public function setProperties($p) { if (is_array($p)) { $this->properties = array_merge($this->properties, $p); } }
            public function resetProperties() { $this->properties = []; }
        }
    }
    if (!class_exists(__NAMESPACE__ . '\\TFilter', false)) {
        class TFilter
        {
            public $field; public $op; public $value;
            public function __construct($field, $op = null, $value = null) {
                $this->field = $field; $this->op = $op; $this->value = $value;
            }
        }
    }
    if (!class_exists(__NAMESPACE__ . '\\TRepository', false)) {
        class TRepository
        {
            public $class;
            public function __construct($class) { $this->class = $class; }
            public function load($criteria = null) {
                return class_exists('\\MockRepository') ? \MockRepository::load($this->class, $criteria) : [];
            }
            public function delete($criteria = null) {
                return class_exists('\\MockRepository') ? \MockRepository::delete($this->class, $criteria) : null;
            }
        }
    }
}

namespace {
    if (!defined('APPLICATION_NAME')) {
        define('APPLICATION_NAME', 'zooloo');
    }

    // ---- mocks de domínio ----

    class MockRepository
    {
        public static array $data = []; // class => [records]
        public static function reset(): void { self::$data = []; }
        public static function load($class, $criteria) {
            $rows = self::$data[$class] ?? [];
            if (!$criteria || empty($criteria->filters)) return $rows;
            return array_values(array_filter($rows, function ($row) use ($criteria) {
                foreach ($criteria->filters as $f) {
                    $val = $row->{$f->field} ?? null;
                    switch ($f->op) {
                        case '=': if ($val != $f->value) return false; break;
                        case '<': if (!($val < $f->value))  return false; break;
                    }
                }
                return true;
            }));
        }
        public static function delete($class, $criteria) {
            $kept = [];
            foreach (self::$data[$class] ?? [] as $row) {
                $match = true;
                foreach ($criteria->filters as $f) {
                    $val = $row->{$f->field} ?? null;
                    if ($f->op === '=' && $val != $f->value) { $match = false; break; }
                    if ($f->op === '<' && !($val < $f->value))  { $match = false; break; }
                }
                if (!$match) $kept[] = $row;
            }
            self::$data[$class] = $kept;
        }
    }

    class MobAuthToken
    {
        public $jti, $user_id, $token_type, $parent_jti, $issued_at, $expires_at;
        public $revoked = 'f', $revoked_at, $revoked_reason, $user_agent, $ip_address;

        public function store(): void
        {
            $bucket = &MockRepository::$data[self::class];
            $bucket = $bucket ?? [];
            foreach ($bucket as $i => $r) {
                if ($r->jti === $this->jti) { $bucket[$i] = $this; return; }
            }
            $bucket[] = $this;
        }
        public static function find($jti)
        {
            foreach (MockRepository::$data[self::class] ?? [] as $r) {
                if ($r->jti === $jti) return $r;
            }
            return null;
        }
        public function revoke($reason = 'manual'): void
        {
            $this->revoked = 't';
            $this->revoked_at = date('Y-m-d H:i:s');
            $this->revoked_reason = $reason;
            $this->store();
        }
        public static function isUsable($jti, $type): bool
        {
            $t = self::find($jti);
            if (!$t) return false;
            if ($t->token_type !== $type) return false;
            if ($t->revoked === 't') return false;
            if (strtotime($t->expires_at) < time()) return false;
            return true;
        }
        public static function revokeAllForUser($user_id, $reason = 'logout_all'): void
        {
            foreach (MockRepository::$data[self::class] ?? [] as $r) {
                if ((int)$r->user_id === (int)$user_id && $r->revoked !== 't') {
                    $r->revoke($reason);
                }
            }
        }
        public static function purgeOlderThan($days = 30): void {}
    }

    class ApplicationAuthenticationService
    {
        public static $fakeUser = null;
        public static $shouldThrow = false;
        public static function authenticate($login, $password, $register = false)
        {
            if (self::$shouldThrow) throw new RuntimeException('bad creds');
            return self::$fakeUser;
        }
    }

    class SystemUser
    {
        public static $fakeUser = null;
        public static function find($id) { return self::$fakeUser; }
    }

    class SystemAccessLogService
    {
        public static int $loginCount = 0;
        public static int $logoutCount = 0;
        public static function registerLogin(): void { self::$loginCount++; }
        public static function registerLogout(): void { self::$logoutCount++; }
    }

    require_once __DIR__ . '/../app/service/auth/ApplicationAuthenticationRestService.php';
    require_once __DIR__ . '/../app/service/auth/AuthRateLimiter.php';

    // ---- helpers ----

    function makeUser(): object {
        return (object) [
            'id' => 10, 'login' => 'admin', 'name' => 'Admin',
            'email' => 'a@b.c', 'active' => 'Y',
        ];
    }

    function resetAuthState(): void {
        // Config determinística independente de qual arquivo declarou a classe.
        \Adianti\Core\AdiantiApplicationConfig::$config = [
            'general'    => ['seed' => 'seed-teste'],
            'rate_limit' => [
                'login_max'     => 3,
                'login_window'  => 60,
                'login_lockout' => 120,
                'ip_max'        => 10,
                'ip_window'     => 60,
            ],
        ];
        MockRepository::reset();
        ApplicationAuthenticationService::$fakeUser = null;
        ApplicationAuthenticationService::$shouldThrow = false;
        SystemUser::$fakeUser = null;
        SystemAccessLogService::$loginCount = 0;
        SystemAccessLogService::$logoutCount = 0;
        // limpa buckets de rate-limiter (tmp/ratelimit na raiz do projeto)
        $dir = __DIR__ . '/../tmp/ratelimit';
        if (is_dir($dir)) foreach (glob($dir . '/*.json') as $f) @unlink($f);
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    }

    // ---- testes ----

    test('login: credenciais vazias retornam mensagem genérica', function () {
        resetAuthState();
        $r = ApplicationAuthenticationRestService::login(['data' => ['login' => '', 'password' => '']]);
        assertSameValue(false, $r['success']);
        assertContainsText('autenticar', $r['message']);
    });

    test('login: credenciais inválidas retornam mensagem genérica (sem user enumeration)', function () {
        resetAuthState();
        ApplicationAuthenticationService::$fakeUser = null;
        $r = ApplicationAuthenticationRestService::login(['data' => ['login' => 'x', 'password' => 'y']]);
        assertSameValue(false, $r['success']);
        assertContainsText('autenticar', $r['message']);
    });

    test('login: usuário inativo recebe a mesma mensagem genérica', function () {
        resetAuthState();
        $u = makeUser(); $u->active = 'N';
        ApplicationAuthenticationService::$fakeUser = $u;
        $r = ApplicationAuthenticationRestService::login(['data' => ['login' => 'admin', 'password' => 'x']]);
        assertSameValue(false, $r['success']);
        assertContainsText('autenticar', $r['message']);
    });

    test('login: sucesso emite access + refresh, persiste ambos, devolve vendedor=null sem cad_vendedor', function () {
        resetAuthState();
        ApplicationAuthenticationService::$fakeUser = makeUser();
        $r = ApplicationAuthenticationRestService::login(['data' => ['login' => 'admin', 'password' => '1']]);
        assertSameValue(true, $r['success']);
        assertTrue(!empty($r['token']), 'access token ausente');
        assertTrue(!empty($r['refresh_token']), 'refresh token ausente');
        assertTrue($r['token'] !== $r['refresh_token'], 'access e refresh devem diferir');
        assertSameValue(null, $r['vendedor']);
        assertTrue(isset($r['warning']));
        $persisted = MockRepository::$data['MobAuthToken'] ?? [];
        assertSameValue(2, count($persisted));
    });

    test('login: devolve vendedor + permissoes quando cad_vendedor existe', function () {
        resetAuthState();
        ApplicationAuthenticationService::$fakeUser = makeUser();
        $v = (object) [
            'vendedor_id' => 7, 'area_id' => 1, 'coletor_id' => 2, 'nome' => 'Vendedor',
            'comissao' => 5.0, 'limite_venda' => 1000.0, 'tipo_limite' => 'D',
            'treinamento' => 'N', 'ativo' => 'S', 'usuario_id' => 10,
            'exibe_comissao' => 'S', 'exibe_premiacao' => 'S',
            'pode_cancelar' => 'S', 'pode_cancelar_qtde' => 3, 'pode_cancelar_tempo' => '10',
            'pode_reimprimir' => 'S', 'pode_reimprimir_qtde' => 2, 'pode_reimprimir_tempo' => '5',
            'pode_reimprimir_outro' => 'N', 'pode_reimprimir_sort_pago' => 'N',
            'pode_reimprimir_sort_naopg' => 'S', 'pode_reimprimir_sort_pago_outro' => 'N',
            'pode_reimprimir_sort_naopg_outro' => 'N', 'pode_pagar' => 'S', 'pode_pagar_outro' => 'N',
        ];
        MockRepository::$data['Vendedor'] = [$v];

        $r = ApplicationAuthenticationRestService::login(['data' => ['login' => 'admin', 'password' => '1']]);
        assertSameValue(7, $r['vendedor']['vendedor_id']);
        assertSameValue('S', $r['permissoes']['pode_cancelar']);
        assertSameValue(3, $r['permissoes']['pode_cancelar_qtde']);
    });

    test('rate-limit: 4ª tentativa com login_max=3 bloqueia', function () {
        resetAuthState();
        ApplicationAuthenticationService::$fakeUser = null;
        for ($i = 0; $i < 3; $i++) {
            ApplicationAuthenticationRestService::login(['data' => ['login' => 'admin', 'password' => 'x']]);
        }
        http_response_code(200);
        $r = ApplicationAuthenticationRestService::login(['data' => ['login' => 'admin', 'password' => 'x']]);
        assertSameValue(false, $r['success']);
        assertContainsText('Muitas tentativas', $r['message']);
        assertSameValue(429, http_response_code());
        http_response_code(200);
    });

    test('rate-limit: sucesso limpa contador do login', function () {
        resetAuthState();
        ApplicationAuthenticationService::$fakeUser = null;
        for ($i = 0; $i < 2; $i++) {
            ApplicationAuthenticationRestService::login(['data' => ['login' => 'admin', 'password' => 'x']]);
        }
        ApplicationAuthenticationService::$fakeUser = makeUser();
        ApplicationAuthenticationRestService::login(['data' => ['login' => 'admin', 'password' => 'x']]);
        // outras 3 falhas devem ser permitidas (contador zerou)
        ApplicationAuthenticationService::$fakeUser = null;
        $r = ApplicationAuthenticationRestService::login(['data' => ['login' => 'admin', 'password' => 'x']]);
        assertSameValue(false, $r['success']);
        assertContainsText('autenticar', $r['message']); // não é "muitas tentativas"
    });

    test('validateToken: rejeita vazio', function () {
        resetAuthState();
        $r = ApplicationAuthenticationRestService::validateToken([]);
        assertSameValue(false, $r['success']);
    });

    test('validateToken: aceita access válido recém-emitido', function () {
        resetAuthState();
        ApplicationAuthenticationService::$fakeUser = makeUser();
        $login = ApplicationAuthenticationRestService::login(['data' => ['login' => 'admin', 'password' => '1']]);
        $v = ApplicationAuthenticationRestService::validateToken(['token' => $login['token']]);
        assertSameValue(true, $v['success']);
        assertSameValue(10, $v['user']['id']);
    });

    test('validateToken: rejeita refresh-token quando vem na rota de access', function () {
        resetAuthState();
        ApplicationAuthenticationService::$fakeUser = makeUser();
        $login = ApplicationAuthenticationRestService::login(['data' => ['login' => 'admin', 'password' => '1']]);
        $v = ApplicationAuthenticationRestService::validateToken(['token' => $login['refresh_token']]);
        assertSameValue(false, $v['success']);
    });

    test('validateToken: token com jti revogado é rejeitado', function () {
        resetAuthState();
        ApplicationAuthenticationService::$fakeUser = makeUser();
        $login = ApplicationAuthenticationRestService::login(['data' => ['login' => 'admin', 'password' => '1']]);
        // revoga manualmente o access persistido
        foreach (MockRepository::$data['MobAuthToken'] as $t) {
            if ($t->token_type === 'access') $t->revoke('test');
        }
        $v = ApplicationAuthenticationRestService::validateToken(['token' => $login['token']]);
        assertSameValue(false, $v['success']);
        assertContainsText('revogado', $v['message']);
    });

    test('refreshToken: rotação revoga refresh antigo e emite novo par', function () {
        resetAuthState();
        ApplicationAuthenticationService::$fakeUser = makeUser();
        SystemUser::$fakeUser = makeUser();
        $login = ApplicationAuthenticationRestService::login(['data' => ['login' => 'admin', 'password' => '1']]);
        $r = ApplicationAuthenticationRestService::refreshToken(['refresh_token' => $login['refresh_token']]);
        assertSameValue(true, $r['success']);
        assertTrue($r['token'] !== $login['token']);
        assertTrue($r['refresh_token'] !== $login['refresh_token']);
        // refresh antigo agora deve estar revogado
        $count_active_refresh = 0;
        foreach (MockRepository::$data['MobAuthToken'] as $t) {
            if ($t->token_type === 'refresh' && $t->revoked !== 't') $count_active_refresh++;
        }
        assertSameValue(1, $count_active_refresh);
    });

    test('refreshToken: replay (reuso de refresh já rotacionado) revoga toda a árvore', function () {
        resetAuthState();
        ApplicationAuthenticationService::$fakeUser = makeUser();
        SystemUser::$fakeUser = makeUser();
        $login = ApplicationAuthenticationRestService::login(['data' => ['login' => 'admin', 'password' => '1']]);
        ApplicationAuthenticationRestService::refreshToken(['refresh_token' => $login['refresh_token']]);
        // tentativa 2 com o MESMO refresh antigo: deve falhar e revogar tudo
        $r2 = ApplicationAuthenticationRestService::refreshToken(['refresh_token' => $login['refresh_token']]);
        assertSameValue(false, $r2['success']);
        $active = 0;
        foreach (MockRepository::$data['MobAuthToken'] as $t) {
            if ($t->revoked !== 't') $active++;
        }
        assertSameValue(0, $active);
    });

    test('refreshToken: access-token (não-refresh) é rejeitado', function () {
        resetAuthState();
        ApplicationAuthenticationService::$fakeUser = makeUser();
        $login = ApplicationAuthenticationRestService::login(['data' => ['login' => 'admin', 'password' => '1']]);
        $r = ApplicationAuthenticationRestService::refreshToken(['refresh_token' => $login['token']]);
        assertSameValue(false, $r['success']);
    });

    test('logout: revoga access e refresh persistidos', function () {
        resetAuthState();
        ApplicationAuthenticationService::$fakeUser = makeUser();
        $login = ApplicationAuthenticationRestService::login(['data' => ['login' => 'admin', 'password' => '1']]);
        $r = ApplicationAuthenticationRestService::logout([
            'token' => $login['token'],
            'refresh_token' => $login['refresh_token'],
        ]);
        assertSameValue(true, $r['success']);
        $active = 0;
        foreach (MockRepository::$data['MobAuthToken'] as $t) {
            if ($t->revoked !== 't') $active++;
        }
        assertSameValue(0, $active);
    });

    test('logout: sem tokens ainda retorna sucesso (idempotente)', function () {
        resetAuthState();
        $r = ApplicationAuthenticationRestService::logout([]);
        assertSameValue(true, $r['success']);
    });

    test('logoutAll: revoga todos os tokens ativos do usuário', function () {
        resetAuthState();
        ApplicationAuthenticationService::$fakeUser = makeUser();
        ApplicationAuthenticationRestService::login(['data' => ['login' => 'admin', 'password' => '1']]);
        ApplicationAuthenticationRestService::login(['data' => ['login' => 'admin', 'password' => '1']]);
        $r = ApplicationAuthenticationRestService::logoutAll(['_auth' => ['id' => 10]]);
        assertSameValue(true, $r['success']);
        foreach (MockRepository::$data['MobAuthToken'] as $t) {
            assertSameValue('t', $t->revoked);
        }
    });

    test('logoutAll: sem _auth retorna não-autenticado', function () {
        resetAuthState();
        $r = ApplicationAuthenticationRestService::logoutAll([]);
        assertSameValue(false, $r['success']);
    });
}
