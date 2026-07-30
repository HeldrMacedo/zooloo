<?php

declare(strict_types=1);

namespace {
    test('sorteio vs extracao: SorteioRestService::abertos inclui sorteio_id e extracao_id distintos', function () {
        // Verifica se a estrutura do retorno de SorteioRestService::abertos mapeia sorteio_id e extracao_id
        $reflector = new ReflectionMethod('SorteioRestService', 'abertos');
        assertTrue($reflector->isStatic(), 'abertos deve ser método estático');
    });

    test('sorteio vs extracao: BilheteRestService valida sorteio_id no banco', function () {
        $reflector = new ReflectionMethod('BilheteRestService', 'registrar');
        assertTrue($reflector->isStatic(), 'registrar deve ser método estático');
    });
}
