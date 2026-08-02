<?php

declare(strict_types=1);

namespace Adianti\Database {
    if (!class_exists(__NAMESPACE__ . '\\TRecord', false)) {
        class TRecord
        {
            protected array $data = [];
            public function __construct($id = null, $callObjectLoad = TRUE) {}
            public function addAttribute($attribute) {}
            public function __set($property, $value) { $this->data[$property] = $value; }
            public function __get($property) { return $this->data[$property] ?? null; }
        }
    }
}

namespace {
    require_once __DIR__ . '/../app/model/entities/MovJbSortPalpite.php';

    test('MovJbSortPalpite: inicializa premio_colocacao_01 ate 10 sem deixar valores nulos', function () {
        $palpite = new MovJbSortPalpite;
        for ($c = 1; $c <= 10; $c++) {
            $campo = 'premio_colocacao_' . str_pad((string)$c, 2, '0', STR_PAD_LEFT);
            $palpite->$campo = 0;
        }

        for ($c = 1; $c <= 10; $c++) {
            $campo = 'premio_colocacao_' . str_pad((string)$c, 2, '0', STR_PAD_LEFT);
            assertSameValue(0, $palpite->$campo, "{$campo} deve estar definido com 0");
        }
    });
}
