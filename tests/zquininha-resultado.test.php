<?php

declare(strict_types=1);

namespace Adianti\Control {
    if (!class_exists(__NAMESPACE__ . '\\TPage', false)) {
        class TPage { public function __construct() {} public function add($x) {} public function show() {} }
    }
    if (!class_exists(__NAMESPACE__ . '\\TAction', false)) {
        class TAction { public function __construct($a, $b = null) {} public function setParameter($k, $v) {} }
    }
}

namespace Adianti\Database {
    if (!class_exists(__NAMESPACE__ . '\\TCriteria', false)) {
        class TCriteria {
            public array $filters = [];
            public array $properties = [];
            public function add($filter) { $this->filters[] = $filter; }
            public function setProperty($k, $v) { $this->properties[$k] = $v; }
            public function setProperties($p) { if (is_array($p)) { $this->properties = array_merge($this->properties, $p); } }
            public function resetProperties() { $this->properties = []; }
        }
    }
    if (!class_exists(__NAMESPACE__ . '\\TFilter', false)) {
        class TFilter { public function __construct(public string $field, public string $op, public $value) {} }
    }
    if (!class_exists(__NAMESPACE__ . '\\TRepository', false)) {
        class TRepository { public function __construct($entity) {} }
    }
    if (!class_exists(__NAMESPACE__ . '\\TTransaction', false)) {
        class TTransaction {
            public static function open($db) {}
            public static function close() {}
            public static function rollback() {}
            public static function get() { return null; }
        }
    }
    if (!class_exists(__NAMESPACE__ . '\\TRecord', false)) {
        class TRecord {
            protected array $data = [];
            public function __construct($id = null) {}
            public function addAttribute($n) {}
            public function __set($k, $v) { $this->data[$k] = $v; }
            public function __get($k) { return $this->data[$k] ?? null; }
            public function store() {}
        }
    }
}

namespace Adianti\Registry {
    if (!class_exists(__NAMESPACE__ . '\\TSession', false)) {
        class TSession {
            private static array $data = [];
            public static function setValue($k, $v) { self::$data[$k] = $v; }
            public static function getValue($k) { return self::$data[$k] ?? null; }
        }
    }
}

namespace Adianti\Widget\Container {
    if (!class_exists(__NAMESPACE__ . '\\TPanelGroup', false)) {
        class TPanelGroup { public $style; public function add($x) { return $this; } public function addFooter($x) {} }
    }
    if (!class_exists(__NAMESPACE__ . '\\TVBox', false)) {
        class TVBox { public $style; public function add($x) {} }
    }
    if (!class_exists(__NAMESPACE__ . '\\THBox', false)) {
        class THBox { public function add($x) {} }
    }
}

namespace Adianti\Widget\Datagrid {
    if (!class_exists(__NAMESPACE__ . '\\TDataGrid', false)) {
        class TDataGrid { public $style; public function setHeight($h) {} public function getWidth() { return 0; } public function addColumn($c) {} public function addAction($a) {} public function createModel() {} public function clear() {} public function addItem($i) {} }
    }
    if (!class_exists(__NAMESPACE__ . '\\TDataGridColumn', false)) {
        class TDataGridColumn { public function __construct(...$a) {} public function setTransformer($t) {} }
    }
    if (!class_exists(__NAMESPACE__ . '\\TDataGridAction', false)) {
        class TDataGridAction { public function __construct(...$a) {} public function setUseButton($b) {} public function setButtonClass($c) {} public function setLabel($l) {} public function setImage($i) {} }
    }
    if (!class_exists(__NAMESPACE__ . '\\TPageNavigation', false)) {
        class TPageNavigation { public function enableCounters() {} public function setAction($a) {} public function setWidth($w) {} public function setCount($c) {} public function setProperties($p) {} public function setLimit($l) {} }
    }
}

namespace Adianti\Widget\Form {
    if (!class_exists(__NAMESPACE__ . '\\TDate', false)) {
        class TDate { public $style; public function __construct($n) {} public function setMask($m) {} public function setDatabaseMask($m) {} public function setValue($v) {} public static function date2br($v) { return $v; } public function setEditable($b) {} }
    }
    if (!class_exists(__NAMESPACE__ . '\\TCombo', false)) {
        class TCombo { public function __construct($n) {} public function enableSearch() {} public function addItems($i) {} }
    }
    if (!class_exists(__NAMESPACE__ . '\\TEntry', false)) {
        class TEntry { public $style; public $placeholder; public function __construct($n) {} public function setEditable($b) {} public function setMask($m) {} public function setSize($s) {} }
    }
    if (!class_exists(__NAMESPACE__ . '\\TLabel', false)) {
        class TLabel { public function __construct($t) {} }
    }
    if (!class_exists(__NAMESPACE__ . '\\TForm', false)) {
        class TForm {}
    }
    if (!class_exists(__NAMESPACE__ . '\\TButton', false)) {
        class TButton { public function __construct($n) {} }
    }
}

namespace Adianti\Widget\Wrapper {
    if (!class_exists(__NAMESPACE__ . '\\TDBCombo', false)) { class TDBCombo {} }
    if (!class_exists(__NAMESPACE__ . '\\TDBUniqueSearch', false)) { class TDBUniqueSearch {} }
}

namespace Adianti\Wrapper {
    if (!class_exists(__NAMESPACE__ . '\\BootstrapFormBuilder', false)) {
        class BootstrapFormBuilder {
            public function __construct($n) {}
            public function setFormTitle($t) {}
            public function addFields(...$f) {}
            public function addAction(...$a) {}
            public function addActionLink(...$a) {}
            public function addHeaderActionLink(...$a) {}
            public function addContent($c) {}
            public function setData($d) {}
            public function getData() { return new \stdClass(); }
            public function clear() {}
            public function getField($n) { return null; }
        }
    }
    if (!class_exists(__NAMESPACE__ . '\\BootstrapDatagridWrapper', false)) {
        class BootstrapDatagridWrapper { public $style; public function __construct($g) {} public function setHeight($h) {} public function getWidth() { return 0; } public function addColumn($c) {} public function addAction($a) {} public function createModel() {} public function clear() {} public function addItem($i) {} }
    }
}

namespace Adianti\Widget\Util {
    if (!class_exists(__NAMESPACE__ . '\\TXMLBreadCrumb', false)) {
        class TXMLBreadCrumb { public function __construct(...$a) {} }
    }
}

namespace Adianti\Widget\Dialog {
    if (!class_exists(__NAMESPACE__ . '\\TMessage', false)) {
        class TMessage { public function __construct(...$a) { $GLOBALS['__last_msg'] = $a; } }
    }
    if (!class_exists(__NAMESPACE__ . '\\TQuestion', false)) {
        class TQuestion { public function __construct(...$a) { $GLOBALS['__last_question'] = $a; } }
    }
}

namespace Adianti\Widget\Base {
    if (!class_exists(__NAMESPACE__ . '\\TScript', false)) {
        class TScript { public static function create($s) {} }
    }
}

namespace Adianti\Core {
    if (!class_exists(__NAMESPACE__ . '\\AdiantiCoreApplication', false)) {
        class AdiantiCoreApplication {}
    }
}

namespace {
    if (!function_exists('_t')) {
        function _t($s) { return $s; }
    }

    if (!class_exists('Extracao', false)) {
        class Extracao {
            public $filtro_banca;
            public function __construct($id = null) {
                $this->filtro_banca = $GLOBALS['__test_extracao_filtro_banca'] ?? 2;
            }
        }
    }

    if (!class_exists('MovSorteio', false)) {
        class MovSorteio {
            public $sorteio_id;
            public $extracao_id;
            public $sorteio_numero;
            public $data_sorteio;
            public $hora_sorteio;
            public $situacao;
            public $numeros_sorteados;
            public function __construct($id = null) {
                if ($id !== null) {
                    $stub = $GLOBALS['__test_sorteio_stub'] ?? [];
                    foreach ($stub as $k => $v) { $this->$k = $v; }
                    $this->sorteio_id = $id;
                }
            }
            public function store() { $GLOBALS['__test_sorteio_stored'] = clone $this; }
        }
    }

    require_once __DIR__ . '/../app/control/quininha/QuininhaResultadoForm.php';
    require_once __DIR__ . '/../app/control/quininha/QuininhaResultadoList.php';

    test('QuininhaResultadoForm.validarNumerosSorteados aceita 5 dezenas válidas', function () {
        $result = QuininhaResultadoForm::validarNumerosSorteados('05,12,33,44,77');
        assertSameValue('05,12,33,44,77', $result);
    });

    test('QuininhaResultadoForm.validarNumerosSorteados normaliza espaços', function () {
        $result = QuininhaResultadoForm::validarNumerosSorteados(' 01 , 02 , 03 , 04 , 05 ');
        assertSameValue('01,02,03,04,05', $result);
    });

    test('QuininhaResultadoForm.validarNumerosSorteados falha com menos de 5 dezenas', function () {
        assertThrows(fn() => QuininhaResultadoForm::validarNumerosSorteados('01,02,03,04'), 'exatamente 5 dezenas');
    });

    test('QuininhaResultadoForm.validarNumerosSorteados falha com mais de 5 dezenas', function () {
        assertThrows(fn() => QuininhaResultadoForm::validarNumerosSorteados('01,02,03,04,05,06'), 'exatamente 5 dezenas');
    });

    test('QuininhaResultadoForm.validarNumerosSorteados falha com dezena com 1 dígito', function () {
        assertThrows(fn() => QuininhaResultadoForm::validarNumerosSorteados('1,02,03,04,05'), '2 dígitos');
    });

    test('QuininhaResultadoForm.validarNumerosSorteados falha com não-numérico', function () {
        assertThrows(fn() => QuininhaResultadoForm::validarNumerosSorteados('AA,02,03,04,05'), '2 dígitos');
    });

    test('QuininhaResultadoForm.validarNumerosSorteados falha com string vazia', function () {
        assertThrows(fn() => QuininhaResultadoForm::validarNumerosSorteados(''), 'exatamente 5 dezenas');
    });

    test('QuininhaResultadoForm.podeLancar permite quando hora atual >= hora sorteio', function () {
        assertTrue(QuininhaResultadoForm::podeLancar('14:00:00', '15:30:00'));
        assertTrue(QuininhaResultadoForm::podeLancar('14:00:00', '14:00:00'));
    });

    test('QuininhaResultadoForm.podeLancar bloqueia quando hora atual < hora sorteio', function () {
        assertTrue(!QuininhaResultadoForm::podeLancar('14:00:00', '13:59:59'));
    });

    test('QuininhaResultadoForm.podeLancar permite quando hora_sorteio é vazia', function () {
        assertTrue(QuininhaResultadoForm::podeLancar('', '00:00:00'));
    });

    test('QuininhaResultadoForm.assertExtracaoQuininha aceita filtro_banca = 2', function () {
        $GLOBALS['__test_extracao_filtro_banca'] = 2;
        QuininhaResultadoForm::assertExtracaoQuininha(99);
        assertTrue(true);
    });

    test('QuininhaResultadoForm.assertExtracaoQuininha rejeita outras bancas', function () {
        $GLOBALS['__test_extracao_filtro_banca'] = 1;
        assertThrows(fn() => QuininhaResultadoForm::assertExtracaoQuininha(99), 'não pertence à Quininha');
        $GLOBALS['__test_extracao_filtro_banca'] = 2;
    });

    test('QuininhaResultadoList.buildCriteria aplica filtro_banca = 2 sempre', function () {
        $criteria = QuininhaResultadoList::buildCriteria(new stdClass());
        $hasFiltroBanca = false;
        foreach ($criteria->filters as $f) {
            if ($f->field === 'filtro_banca' && $f->op === '=' && $f->value === 2) $hasFiltroBanca = true;
        }
        assertTrue($hasFiltroBanca, 'filtro_banca=2 não foi aplicado');
    });

    test('QuininhaResultadoList.buildCriteria adiciona filtro de data quando informada', function () {
        $data = new stdClass();
        $data->data_sorteio = '2025-05-10';
        $criteria = QuininhaResultadoList::buildCriteria($data);
        $hasData = false;
        foreach ($criteria->filters as $f) {
            if ($f->field === 'data_sorteio' && $f->value === '2025-05-10') $hasData = true;
        }
        assertTrue($hasData, 'filtro data_sorteio não aplicado');
    });

    test('QuininhaResultadoList.buildCriteria adiciona filtro de extracao_id quando informada', function () {
        $data = new stdClass();
        $data->extracao_id = 42;
        $criteria = QuininhaResultadoList::buildCriteria($data);
        $hasExtr = false;
        foreach ($criteria->filters as $f) {
            if ($f->field === 'extracao_id' && $f->value === 42) $hasExtr = true;
        }
        assertTrue($hasExtr, 'filtro extracao_id não aplicado');
    });

    test('QuininhaResultadoList.buildCriteria sem filtros opcionais aplica somente filtro_banca', function () {
        $criteria = QuininhaResultadoList::buildCriteria(new stdClass());
        assertSameValue(1, count($criteria->filters));
    });
}
