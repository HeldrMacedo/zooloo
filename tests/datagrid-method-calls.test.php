<?php

declare(strict_types=1);

/**
 * Guarda de regressão: nenhum controller pode chamar em $this->datagrid um método
 * que não exista em TDataGrid/TQuickGrid (nem no decorator BootstrapDatagridWrapper, que apenas
 * repassa a chamada via __call). Chamadas inexistentes só estouram em runtime, ao
 * clicar em "Buscar" — como aconteceu com $this->datagrid->updatePage().
 */

if (!function_exists('zooloo_datagrid_known_methods')) {
    function zooloo_datagrid_known_methods(): array
    {
        $root = dirname(__DIR__);
        $files = [
            $root . '/lib/adianti/widget/base/TElement.php',
            $root . '/lib/adianti/widget/container/TTable.php',
            $root . '/lib/adianti/widget/datagrid/TDataGrid.php',
            $root . '/lib/adianti/widget/wrapper/TQuickGrid.php',
            $root . '/lib/adianti/wrapper/BootstrapDatagridWrapper.php',
        ];

        $methods = [];
        foreach ($files as $file) {
            if (!is_file($file)) {
                throw new RuntimeException("Arquivo do framework não encontrado: {$file}");
            }
            if (preg_match_all('/function\s+([A-Za-z_][A-Za-z0-9_]*)\s*\(/', (string) file_get_contents($file), $m)) {
                foreach ($m[1] as $name) {
                    $methods[strtolower($name)] = true;
                }
            }
        }

        return $methods;
    }
}

if (!function_exists('zooloo_datagrid_calls')) {
    function zooloo_datagrid_calls(): array
    {
        $root = dirname(__DIR__) . '/app/control';
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
        $calls = [];

        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }
            $lines = file($file->getPathname(), FILE_IGNORE_NEW_LINES);
            foreach ($lines as $i => $line) {
                if (preg_match_all('/\$this->datagrid->([A-Za-z_][A-Za-z0-9_]*)\s*\(/', $line, $m)) {
                    foreach ($m[1] as $method) {
                        $calls[] = [
                            'method' => $method,
                            'file'   => str_replace(dirname(__DIR__) . '/', '', $file->getPathname()),
                            'line'   => $i + 1,
                        ];
                    }
                }
            }
        }

        return $calls;
    }
}

test('nenhum controller chama método inexistente em $this->datagrid', function () {
    $known = zooloo_datagrid_known_methods();
    $calls = zooloo_datagrid_calls();

    assertTrue(count($calls) > 0, 'Esperava encontrar chamadas a $this->datagrid em app/control');

    $invalid = [];
    foreach ($calls as $call) {
        if (!isset($known[strtolower($call['method'])])) {
            $invalid[] = "{$call['file']}:{$call['line']} -> {$call['method']}()";
        }
    }

    assertSameValue(
        [],
        $invalid,
        'Chamadas a métodos inexistentes em TDataGrid: ' . implode(', ', $invalid)
    );
});
