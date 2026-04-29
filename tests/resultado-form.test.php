<?php

declare(strict_types=1);

namespace Adianti\Control {
    class TPage
    {
        public function __construct()
        {
        }
    }
}

namespace {
    require_once __DIR__ . '/../app/control/resultado/ResultadoForm.php';

    test('resultadoForm.calcularGrupoDescricao retorna AVESTRUZ para dezena 1', function () {
        $ref = new ReflectionClass(ResultadoForm::class);
        $obj = $ref->newInstanceWithoutConstructor();
        $method = $ref->getMethod('calcularGrupoDescricao');
        $method->setAccessible(true);

        $result = $method->invoke($obj, 1);
        assertSameValue('01', $result['grupo']);
        assertSameValue('AVESTRUZ', $result['descricao']);
    });

    test('resultadoForm.calcularGrupoDescricao retorna VACA para dezena 100', function () {
        $ref = new ReflectionClass(ResultadoForm::class);
        $obj = $ref->newInstanceWithoutConstructor();
        $method = $ref->getMethod('calcularGrupoDescricao');
        $method->setAccessible(true);

        $result = $method->invoke($obj, 100);
        assertSameValue('25', $result['grupo']);
        assertSameValue('VACA', $result['descricao']);
    });
}
