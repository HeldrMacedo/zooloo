<?php

declare(strict_types=1);

namespace Adianti\Database {
    if (!class_exists(__NAMESPACE__ . '\\TTransaction', false)) {
    class TTransaction
    {
        public static function open($database): void
        {
        }

        public static function close(): void
        {
        }

        public static function rollback(): void
        {
        }

        public static function get()
        {
            return null;
        }
    }
    }
}

namespace {
require_once __DIR__ . '/../app/service/rest/BilheteRestService.php';
require_once __DIR__ . '/../app/service/rest/CaixaRestService.php';
require_once __DIR__ . '/../app/service/rest/ModalidadeRestService.php';
require_once __DIR__ . '/../app/service/rest/ResultadoRestService.php';
require_once __DIR__ . '/../app/service/rest/SorteioRestService.php';
require_once __DIR__ . '/../app/service/rest/TerminalRestService.php';
require_once __DIR__ . '/../app/service/rest/VendedorRestService.php';

test('bilhete.registrar exige autenticação', function () {
    assertThrows(fn () => BilheteRestService::registrar([]), 'Usuário não autenticado');
});

test('bilhete.registrar exige terminal_id', function () {
    assertThrows(
        fn () => BilheteRestService::registrar(['_auth' => ['id' => 1], 'data' => []]),
        'terminal_id é obrigatório'
    );
});

test('bilhete.cancelar exige bilhete_id', function () {
    assertThrows(
        fn () => BilheteRestService::cancelar(['_auth' => ['id' => 1]]),
        'bilhete_id é obrigatório'
    );
});

test('bilhete.detalhe exige bilhete_id', function () {
    assertThrows(
        fn () => BilheteRestService::detalhe(['_auth' => ['id' => 1]]),
        'bilhete_id é obrigatório'
    );
});

test('caixa.resumo exige autenticação', function () {
    assertThrows(fn () => CaixaRestService::resumo([]), 'Usuário não autenticado');
});

test('modalidade.disponiveis exige sorteio_id', function () {
    assertThrows(
        fn () => ModalidadeRestService::disponiveis(['_auth' => ['id' => 1]]),
        'sorteio_id é obrigatório'
    );
});

test('resultado.recentes exige autenticação', function () {
    assertThrows(fn () => ResultadoRestService::recentes([]), 'Usuário não autenticado');
});

test('sorteio.abertos exige autenticação', function () {
    assertThrows(fn () => SorteioRestService::abertos([]), 'Usuário não autenticado');
});

test('terminal.registrar exige serial', function () {
    assertThrows(
        fn () => TerminalRestService::registrar(['_auth' => ['id' => 1], 'data' => []]),
        'Serial do dispositivo é obrigatório'
    );
});

test('vendedor.me exige autenticação', function () {
    assertThrows(fn () => VendedorRestService::me([]), 'Usuário não autenticado');
});
}
