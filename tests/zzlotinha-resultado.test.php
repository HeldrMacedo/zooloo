<?php

declare(strict_types=1);

namespace {
    // Pré-requisitos: stubs do framework e classe IntJogo carregados pelos arquivos anteriores
    if (!class_exists('QuininhaResultadoForm', false)) {
        require_once __DIR__ . '/zquininha-resultado.test.php';
    }
    if (!class_exists('SeninhaResultadoForm', false)) {
        require_once __DIR__ . '/zseninha-resultado.test.php';
    }

    require_once __DIR__ . '/../app/control/lotinha/LotinhaResultadoForm.php';
    require_once __DIR__ . '/../app/control/lotinha/LotinhaResultadoList.php';

    $dezenas15 = '01,02,03,04,05,06,07,08,09,10,11,12,13,14,15';

    test('LotinhaResultadoForm.validarNumerosSorteados aceita 15 dezenas válidas', function () use ($dezenas15) {
        $result = LotinhaResultadoForm::validarNumerosSorteados($dezenas15);
        assertSameValue($dezenas15, $result);
    });

    test('LotinhaResultadoForm.validarNumerosSorteados normaliza espaços', function () {
        $input = ' 01 , 02 , 03 , 04 , 05 , 06 , 07 , 08 , 09 , 10 , 11 , 12 , 13 , 14 , 15 ';
        $result = LotinhaResultadoForm::validarNumerosSorteados($input);
        assertSameValue('01,02,03,04,05,06,07,08,09,10,11,12,13,14,15', $result);
    });

    test('LotinhaResultadoForm.validarNumerosSorteados falha com 14 dezenas', function () {
        assertThrows(
            fn() => LotinhaResultadoForm::validarNumerosSorteados('01,02,03,04,05,06,07,08,09,10,11,12,13,14'),
            'exatamente 15 dezenas'
        );
    });

    test('LotinhaResultadoForm.validarNumerosSorteados falha com 16 dezenas', function () {
        assertThrows(
            fn() => LotinhaResultadoForm::validarNumerosSorteados('01,02,03,04,05,06,07,08,09,10,11,12,13,14,15,16'),
            'exatamente 15 dezenas'
        );
    });

    test('LotinhaResultadoForm.validarNumerosSorteados falha com dezena com 1 dígito', function () {
        assertThrows(
            fn() => LotinhaResultadoForm::validarNumerosSorteados('1,02,03,04,05,06,07,08,09,10,11,12,13,14,15'),
            '2 dígitos'
        );
    });

    test('LotinhaResultadoForm.validarNumerosSorteados falha com não-numérico', function () {
        assertThrows(
            fn() => LotinhaResultadoForm::validarNumerosSorteados('AA,02,03,04,05,06,07,08,09,10,11,12,13,14,15'),
            '2 dígitos'
        );
    });

    test('LotinhaResultadoForm.validarNumerosSorteados falha com string vazia', function () {
        assertThrows(
            fn() => LotinhaResultadoForm::validarNumerosSorteados(''),
            'exatamente 15 dezenas'
        );
    });

    test('LotinhaResultadoForm.podeLancar permite quando hora atual >= hora sorteio', function () {
        assertTrue(LotinhaResultadoForm::podeLancar('14:00:00', '15:30:00'));
        assertTrue(LotinhaResultadoForm::podeLancar('14:00:00', '14:00:00'));
    });

    test('LotinhaResultadoForm.podeLancar bloqueia quando hora atual < hora sorteio', function () {
        assertTrue(!LotinhaResultadoForm::podeLancar('14:00:00', '13:59:59'));
    });

    test('LotinhaResultadoForm.podeLancar permite quando hora_sorteio é vazia', function () {
        assertTrue(LotinhaResultadoForm::podeLancar('', '00:00:00'));
    });

    test('LotinhaResultadoForm.assertExtracaoLotinha aceita filtro_banca = 4', function () {
        $GLOBALS['__test_extracao_filtro_banca'] = 4;
        LotinhaResultadoForm::assertExtracaoLotinha(99);
        assertTrue(true);
        $GLOBALS['__test_extracao_filtro_banca'] = 2;
    });

    test('LotinhaResultadoForm.assertExtracaoLotinha rejeita outras bancas', function () {
        $GLOBALS['__test_extracao_filtro_banca'] = 2;
        assertThrows(
            fn() => LotinhaResultadoForm::assertExtracaoLotinha(99),
            'não pertence à Lotinha'
        );
    });

    test('LotinhaResultadoList.buildCriteria aplica filtro_banca = 4 sempre', function () {
        $criteria = LotinhaResultadoList::buildCriteria(new stdClass());
        $hasFiltroBanca = false;
        foreach ($criteria->filters as $f) {
            if ($f->field === 'filtro_banca' && $f->op === '=' && $f->value === 4) $hasFiltroBanca = true;
        }
        assertTrue($hasFiltroBanca, 'filtro_banca=4 não foi aplicado');
    });

    test('LotinhaResultadoList.buildCriteria adiciona filtro de data quando informada', function () {
        $data = new stdClass();
        $data->data_sorteio = '2025-05-10';
        $criteria = LotinhaResultadoList::buildCriteria($data);
        $hasData = false;
        foreach ($criteria->filters as $f) {
            if ($f->field === 'data_sorteio' && $f->value === '2025-05-10') $hasData = true;
        }
        assertTrue($hasData, 'filtro data_sorteio não aplicado');
    });

    test('LotinhaResultadoList.buildCriteria sem filtros opcionais aplica somente filtro_banca', function () {
        $criteria = LotinhaResultadoList::buildCriteria(new stdClass());
        assertSameValue(1, count($criteria->filters));
    });

    test('LotinhaResultadoForm.DEZENAS é 15 (diferencia das outras bancas)', function () {
        assertSameValue(15, LotinhaResultadoForm::DEZENAS);
        assertSameValue(5,  QuininhaResultadoForm::DEZENAS);
        assertSameValue(6,  SeninhaResultadoForm::DEZENAS);
    });

    test('LotinhaResultadoForm.MASK comporta 15 dezenas', function () {
        assertSameValue('00,00,00,00,00,00,00,00,00,00,00,00,00,00,00', LotinhaResultadoForm::MASK);
    });
}
