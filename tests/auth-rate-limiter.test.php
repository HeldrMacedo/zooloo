<?php

declare(strict_types=1);

/*
 * Testes diretos do AuthRateLimiter.
 * Não depende de Adianti — usa apenas filesystem em tmp/ratelimit.
 */

namespace Adianti\Core {
    if (!class_exists(__NAMESPACE__ . '\\AdiantiApplicationConfig')) {
        class AdiantiApplicationConfig
        {
            public static array $config = [
                'rate_limit' => [
                    'login_max'     => 3,
                    'login_window'  => 60,
                    'login_lockout' => 120,
                    'ip_max'        => 5,
                    'ip_window'     => 60,
                ],
            ];
            public static function get(): array { return self::$config; }
        }
    }
}

namespace {
    require_once __DIR__ . '/../app/service/auth/AuthRateLimiter.php';

    function rl_clear(): void {
        $dir = __DIR__ . '/../tmp/ratelimit';
        if (is_dir($dir)) foreach (glob($dir . '/*.json') as $f) @unlink($f);
    }

    test('rate-limiter: permite até login_max-1 falhas, bloqueia na login_max+1', function () {
        rl_clear();
        for ($i = 0; $i < 3; $i++) {
            $c = AuthRateLimiter::check('user', '1.1.1.1');
            assertSameValue(true, $c['allowed'], "Esperava allowed na tentativa $i");
            AuthRateLimiter::registerFailure('user', '1.1.1.1');
        }
        $c = AuthRateLimiter::check('user', '1.1.1.1');
        assertSameValue(false, $c['allowed']);
        assertTrue($c['retry_after'] > 0);
    });

    test('rate-limiter: registerSuccess remove lockout do login', function () {
        rl_clear();
        for ($i = 0; $i < 4; $i++) {
            AuthRateLimiter::registerFailure('alice', '2.2.2.2');
        }
        assertSameValue(false, AuthRateLimiter::check('alice', '2.2.2.2')['allowed']);
        AuthRateLimiter::registerSuccess('alice', '2.2.2.2');
        assertSameValue(true, AuthRateLimiter::check('alice', '2.2.2.2')['allowed']);
    });

    test('rate-limiter: limite por IP é independente do login', function () {
        rl_clear();
        for ($i = 0; $i < 5; $i++) {
            AuthRateLimiter::registerFailure("user$i", '3.3.3.3');
        }
        // 5 falhas no mesmo IP excede ip_max=5
        $c = AuthRateLimiter::check('user6', '3.3.3.3');
        assertSameValue(false, $c['allowed']);
        // outro IP segue liberado
        $c2 = AuthRateLimiter::check('user6', '4.4.4.4');
        assertSameValue(true, $c2['allowed']);
    });

    test('rate-limiter: chaves perigosas são sanitizadas (sem path traversal)', function () {
        rl_clear();
        AuthRateLimiter::registerFailure('../../../etc/passwd', '0.0.0.0');
        $dir = __DIR__ . '/../tmp/ratelimit';
        $files = glob($dir . '/*.json') ?: [];
        assertTrue(count($files) >= 1);
        // nenhum arquivo fora do diretório
        foreach ($files as $f) {
            $real = realpath($f);
            assertTrue(strpos($real, realpath($dir)) === 0, 'arquivo escapou do diretório');
        }
    });
}
