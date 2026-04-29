<?php

declare(strict_types=1);

test('rest.php mantém bypass de autorização apenas para login', function () {
    $content = file_get_contents(__DIR__ . '/../rest.php');
    assertTrue($content !== false, 'Não foi possível ler rest.php');
    assertContainsText("ApplicationAuthenticationRestService' && \$method === 'login", $content);
    assertContainsText("throw new Exception('Authorization required')", $content);
    assertContainsText("\$request['_auth'] = \$validation['user'];", $content);
});

test('test-login-api.php cobre fluxo completo de autenticação', function () {
    $content = file_get_contents(__DIR__ . '/../test-login-api.php');
    assertTrue($content !== false, 'Não foi possível ler test-login-api.php');
    assertContainsText("'method' => 'login'", $content);
    assertContainsText("'method' => 'validateToken'", $content);
    assertContainsText("'method' => 'refreshToken'", $content);
    assertContainsText("'method' => 'logout'", $content);
});
