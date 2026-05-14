<?php

declare(strict_types=1);

namespace {
    // Stubs do framework já foram registrados por zquininha-resultado.test.php (carregado antes).
    // Caso este arquivo seja executado isoladamente, exigimos esse pré-requisito.
    if (!class_exists('QuininhaResultadoForm', false)) {
        require_once __DIR__ . '/zquininha-resultado.test.php';
    }

    if (!class_exists('IntJogo', false)) {
        class IntJogo
        {
            const FILTRO_BANCA_JB      = 1;
            const FILTRO_BANCA_QUINHA  = 2;
            const FILTRO_BANCA_SENINA  = 3;
            const FILTRO_BANCA_LOTINHA = 4;
        }
    }

    require_once __DIR__ . '/../app/control/seninha/SeninhaResultadoForm.php';
    require_once __DIR__ . '/../app/control/seninha/SeninhaResultadoList.php';

    test('SeninhaResultadoForm.validarNumerosSorteados aceita 6 dezenas válidas', function () {
        $result = SeninhaResultadoForm::validarNumerosSorteados('05,12,33,44,55,77');
        assertSameValue('05,12,33,44,55,77', $result);
    });

    test('SeninhaResultadoForm.validarNumerosSorteados normaliza espaços', function () {
        $result = SeninhaResultadoForm::validarNumerosSorteados(' 01 , 02 , 03 , 04 , 05 , 06 ');
        assertSameValue('01,02,03,04,05,06', $result);
    });

    test('SeninhaResultadoForm.validarNumerosSorteados falha com 5 dezenas', function () {
        assertThrows(
            fn() => SeninhaResultadoForm::validarNumerosSorteados('01,02,03,04,05'),
            'exatamente 6 dezenas'
        );
    });

    test('SeninhaResultadoForm.validarNumerosSorteados falha com 7 dezenas', function () {
        assertThrows(
            fn() => SeninhaResultadoForm::validarNumerosSorteados('01,02,03,04,05,06,07'),
            'exatamente 6 dezenas'
        );
    });

    test('SeninhaResultadoForm.validarNumerosSorteados falha com dezena com 1 dígito', function () {
        assertThrows(
            fn() => SeninhaResultadoForm::validarNumerosSorteados('1,02,03,04,05,06'),
            '2 dígitos'
        );
    });

    test('SeninhaResultadoForm.validarNumerosSorteados falha com não-numérico', function () {
        assertThrows(
            fn() => SeninhaResultadoForm::validarNumerosSorteados('AA,02,03,04,05,06'),
            '2 dígitos'
        );
    });

    test('SeninhaResultadoForm.validarNumerosSorteados falha com string vazia', function () {
        assertThrows(
            fn() => SeninhaResultadoForm::validarNumerosSorteados(''),
            'exatamente 6 dezenas'
        );
    });

    test('SeninhaResultadoForm.podeLancar permite quando hora atual >= hora sorteio', function () {
        assertTrue(SeninhaResultadoForm::podeLancar('14:00:00', '15:30:00'));
        assertTrue(SeninhaResultadoForm::podeLancar('14:00:00', '14:00:00'));
    });

    test('SeninhaResultadoForm.podeLancar bloqueia quando hora atual < hora sorteio', function () {
        assertTrue(!SeninhaResultadoForm::podeLancar('14:00:00', '13:59:59'));
    });

    test('SeninhaResultadoForm.podeLancar permite quando hora_sorteio é vazia', function () {
        assertTrue(SeninhaResultadoForm::podeLancar('', '00:00:00'));
    });

    test('SeninhaResultadoForm.assertExtracaoSeninha aceita filtro_banca = 3', function () {
        $GLOBALS['__test_extracao_filtro_banca'] = 3;
        SeninhaResultadoForm::assertExtracaoSeninha(99);
        assertTrue(true);
        $GLOBALS['__test_extracao_filtro_banca'] = 2;
    });

    test('SeninhaResultadoForm.assertExtracaoSeninha rejeita outras bancas', function () {
        $GLOBALS['__test_extracao_filtro_banca'] = 2;
        assertThrows(
            fn() => SeninhaResultadoForm::assertExtracaoSeninha(99),
            'não pertence à Seninha'
        );
    });

    test('SeninhaResultadoList.buildCriteria aplica filtro_banca = 3 sempre', function () {
        $criteria = SeninhaResultadoList::buildCriteria(new stdClass());
        $hasFiltroBanca = false;
        foreach ($criteria->filters as $f) {
            if ($f->field === 'filtro_banca' && $f->op === '=' && $f->value === 3) $hasFiltroBanca = true;
        }
        assertTrue($hasFiltroBanca, 'filtro_banca=3 não foi aplicado');
    });

    test('SeninhaResultadoList.buildCriteria adiciona filtro de data quando informada', function () {
        $data = new stdClass();
        $data->data_sorteio = '2025-05-10';
        $criteria = SeninhaResultadoList::buildCriteria($data);
        $hasData = false;
        foreach ($criteria->filters as $f) {
            if ($f->field === 'data_sorteio' && $f->value === '2025-05-10') $hasData = true;
        }
        assertTrue($hasData, 'filtro data_sorteio não aplicado');
    });

    test('SeninhaResultadoList.buildCriteria sem filtros opcionais aplica somente filtro_banca', function () {
        $criteria = SeninhaResultadoList::buildCriteria(new stdClass());
        assertSameValue(1, count($criteria->filters));
    });

    test('SeninhaResultadoForm.DEZENAS é 6 (diferencia de Quininha)', function () {
        assertSameValue(6, SeninhaResultadoForm::DEZENAS);
        assertSameValue(5, QuininhaResultadoForm::DEZENAS);
    });

    test('SeninhaResultadoForm.MASK comporta 6 dezenas', function () {
        assertSameValue('00,00,00,00,00,00', SeninhaResultadoForm::MASK);
    });
}
