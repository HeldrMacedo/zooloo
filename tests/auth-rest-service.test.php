<?php

declare(strict_types=1);

namespace Adianti\Core {
    class AdiantiApplicationConfig
    {
        public static array $config = ['general' => ['seed' => 'seed-teste']];

        public static function get(): array
        {
            return self::$config;
        }
    }
}

namespace Adianti\Service {
    interface AdiantiRestService
    {
    }
}

namespace {
    if (!defined('APPLICATION_NAME')) {
        define('APPLICATION_NAME', 'zooloo');
    }

    class ApplicationAuthenticationService
    {
        public static $fakeUser = null;

        public static function authenticate($login, $password, $register_session = false)
        {
            return self::$fakeUser;
        }
    }

    class SystemAccessLogService
    {
        public static int $loginCount = 0;

        public static function registerLogin(): void
        {
            self::$loginCount++;
        }
    }

    require_once __DIR__ . '/../app/service/auth/ApplicationAuthenticationRestService.php';

    test('auth.login exige login e senha dentro de data', function () {
        $result = ApplicationAuthenticationRestService::login([]);
        assertSameValue(false, $result['success']);
        assertContainsText('obrigatórios', $result['message']);
    });

    test('auth.login retorna token quando usuário válido', function () {
        $user = (object) [
            'id' => 10,
            'login' => 'admin',
            'name' => 'Administrador',
            'email' => 'admin@teste.local',
            'active' => 'Y',
        ];
        ApplicationAuthenticationService::$fakeUser = $user;
        SystemAccessLogService::$loginCount = 0;

        $result = ApplicationAuthenticationRestService::login([
            'data' => ['login' => 'admin', 'password' => '123'],
        ]);

        assertSameValue(true, $result['success']);
        assertTrue(!empty($result['token']), 'Token JWT não foi retornado');
        assertSameValue(10, $result['user']['id']);
        assertSameValue(1, SystemAccessLogService::$loginCount);
    });

    test('auth.validateToken rejeita token vazio', function () {
        $result = ApplicationAuthenticationRestService::validateToken([]);
        assertSameValue(false, $result['success']);
        assertContainsText('Token é obrigatório', $result['message']);
    });

    test('auth.validateToken aceita token válido', function () {
        $login = ApplicationAuthenticationRestService::login([
            'data' => ['login' => 'admin', 'password' => '123'],
        ]);

        $valid = ApplicationAuthenticationRestService::validateToken(['token' => $login['token']]);
        assertSameValue(true, $valid['success']);
        assertSameValue(10, $valid['user']['id']);
    });

    test('auth.refreshToken gera novo token', function () {
        $login = ApplicationAuthenticationRestService::login([
            'data' => ['login' => 'admin', 'password' => '123'],
        ]);
        usleep(1100000);
        $refresh = ApplicationAuthenticationRestService::refreshToken(['token' => $login['token']]);

        assertSameValue(true, $refresh['success']);
        assertTrue(!empty($refresh['token']));
        assertTrue($refresh['token'] !== $login['token'], 'Refresh deve emitir um token novo');
    });
}
