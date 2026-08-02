<?php

declare(strict_types=1);

namespace {
    test('BilheteRestService: delegacao de palpites para a trigger do banco', function () {
        // Assegura que o método registrar de BilheteRestService não faz inserções manuais redundantes em mov_jb_sort_palpite
        $reflector = new ReflectionMethod('BilheteRestService', 'registrar');
        $filename = $reflector->getFileName();
        $code = file_get_contents($filename);

        assertTrue(strpos($code, 'new MovJbSortPalpite') === false, 'BilheteRestService::registrar não deve instanciar MovJbSortPalpite manualmente, pois a trigger trg_mv_jb_sorteio realiza as inserções.');
    });
}
