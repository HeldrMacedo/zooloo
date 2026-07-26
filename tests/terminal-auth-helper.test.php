<?php

declare(strict_types=1);

/*
 * Testes unitários de TerminalAuthHelper (validação de cad_terminal).
 */

namespace Adianti\Database {
    if (!class_exists(__NAMESPACE__ . '\\TCriteria', false)) {
        class TCriteria
        {
            public array $filters = [];
            public function add($filter) { $this->filters[] = $filter; }
        }
    }
    if (!class_exists(__NAMESPACE__ . '\\TFilter', false)) {
        class TFilter
        {
            public $field; public $op; public $value;
            public function __construct($field, $op = null, $value = null) {
                $this->field = $field; $this->op = $op; $this->value = $value;
            }
        }
    }
    if (!class_exists(__NAMESPACE__ . '\\TRepository', false)) {
        class TRepository
        {
            public $class;
            public function __construct($class) { $this->class = $class; }
            public function load($criteria = null) {
                return class_exists('\\MockRepository') ? \MockRepository::load($this->class, $criteria) : [];
            }
        }
    }
}

namespace {
    if (!class_exists('MockRepository', false)) {
        class MockRepository
        {
            public static array $data = [];
            public static function reset(): void { self::$data = []; }
            public static function load($class, $criteria) {
                $rows = self::$data[$class] ?? [];
                if (!$criteria || empty($criteria->filters)) return $rows;
                return array_values(array_filter($rows, function ($row) use ($criteria) {
                    foreach ($criteria->filters as $f) {
                        $val = $row->{$f->field} ?? null;
                        switch ($f->op) {
                            case '=': if ($val != $f->value) return false; break;
                            case '!=': if ($val == $f->value) return false; break;
                        }
                    }
                    return true;
                }));
            }
        }
    }

    require_once __DIR__ . '/../app/service/auth/TerminalAuthHelper.php';

    function resetTerminalHelperState(): void {
        MockRepository::reset();
    }

    test('TerminalAuthHelper: serial vazio lança obrigatório', function () {
        resetTerminalHelperState();
        $threw = false;
        try {
            TerminalAuthHelper::validateForLogin('');
        } catch (Exception $e) {
            $threw = true;
            assertContainsText('obrigatório', $e->getMessage());
        }
        assertTrue($threw, 'deveria lançar');
    });

    test('TerminalAuthHelper: serial inexistente lança não cadastrado', function () {
        resetTerminalHelperState();
        $threw = false;
        try {
            TerminalAuthHelper::validateForLogin('XYZ');
        } catch (Exception $e) {
            $threw = true;
            assertContainsText('não cadastrado', $e->getMessage());
        }
        assertTrue($threw);
    });

    test('TerminalAuthHelper: terminal inativo lança bloqueado', function () {
        resetTerminalHelperState();
        MockRepository::$data['Terminal'] = [(object) [
            'terminal_id' => 1, 'vendedor_id' => 1, 'serial' => 'S1',
            'tipo' => 'APP', 'multi_usuario' => 'S', 'ativo' => 'N',
        ]];
        $threw = false;
        try {
            TerminalAuthHelper::validateForLogin('S1', 1);
        } catch (Exception $e) {
            $threw = true;
            assertContainsText('bloqueado', $e->getMessage());
        }
        assertTrue($threw);
    });

    test('TerminalAuthHelper: multi_usuario=N exige vendedor dono', function () {
        resetTerminalHelperState();
        MockRepository::$data['Terminal'] = [(object) [
            'terminal_id' => 1, 'vendedor_id' => 5, 'serial' => 'S1',
            'tipo' => 'APP', 'multi_usuario' => 'N', 'ativo' => 'S',
        ]];
        $threw = false;
        try {
            TerminalAuthHelper::validateForLogin('S1', 9);
        } catch (Exception $e) {
            $threw = true;
            assertContainsText('vinculado', $e->getMessage());
        }
        assertTrue($threw);

        $ok = TerminalAuthHelper::validateForLogin('S1', 5);
        assertSameValue(1, $ok['terminal_id']);
        assertSameValue('S1', $ok['serial']);
    });

    test('TerminalAuthHelper: multi_usuario=S ignora vendedor', function () {
        resetTerminalHelperState();
        MockRepository::$data['Terminal'] = [(object) [
            'terminal_id' => 3, 'vendedor_id' => 5, 'serial' => 'MULTI',
            'tipo' => 'POS', 'multi_usuario' => 'S', 'ativo' => 'S',
        ]];
        $ok = TerminalAuthHelper::validateForLogin('MULTI', 99);
        assertSameValue(3, $ok['terminal_id']);
        assertSameValue('POS', $ok['tipo']);
        assertSameValue('S', $ok['multi_usuario']);
    });

    test('TerminalAuthHelper: validateForBilhete por id', function () {
        resetTerminalHelperState();
        MockRepository::$data['Terminal'] = [(object) [
            'terminal_id' => 10, 'vendedor_id' => 2, 'serial' => 'B1',
            'tipo' => 'APP', 'multi_usuario' => 'N', 'ativo' => 'S',
        ]];
        TerminalAuthHelper::validateForBilhete(10, 2);

        $threw = false;
        try {
            TerminalAuthHelper::validateForBilhete(10, 3);
        } catch (Exception $e) {
            $threw = true;
        }
        assertTrue($threw);
    });
}
