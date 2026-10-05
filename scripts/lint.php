<?php

declare(strict_types=1);

/**
 * Roda `php -l` em todo PHP do projeto (app/, tests/, scripts/, raiz) e mostra só as falhas.
 *
 * Uso: php scripts/lint.php   (ou composer lint)
 */

$root = dirname(__DIR__);
$files = glob($root . '/*.php') ?: [];
foreach (['app', 'tests', 'scripts'] as $dir) {
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/' . $dir, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $file) {
        if ($file->getExtension() === 'php') {
            $files[] = $file->getPathname();
        }
    }
}

$failures = 0;
foreach ($files as $file) {
    exec(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($file) . ' 2>&1', $output, $code);
    if ($code !== 0) {
        $failures++;
        echo implode("\n", $output), "\n";
    }
    $output = [];
}

echo count($files) . " arquivos PHP, {$failures} com erro de sintaxe.\n";
exit($failures ? 1 : 0);
