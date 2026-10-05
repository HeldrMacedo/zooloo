<?php

declare(strict_types=1);

/**
 * Valida a base de conhecimento em markdown (AGENTS.md, ARCHITECTURE.md, docs/).
 *
 * - Links markdown relativos `[x](caminho.md)` precisam apontar para arquivo existente.
 *   Links para fora do repo (ex.: ../app-zooloo) só geram aviso — o repo irmão pode não
 *   estar presente (container, CI).
 * - AGENTS.md é um mapa, não uma enciclopédia: limite de linhas.
 *
 * Uso: php scripts/check-docs.php   (ou composer docs:check)
 */

const AGENTS_MAX_LINES = 150;
const SKIP_DIRS = ['vendor', 'lib', '.git', 'tmp', 'files', '.idea', '.kilo', '.claude', '.agents', 'app'];

$root = dirname(__DIR__);

function collectMarkdown(string $dir, array &$out): void
{
    foreach (scandir($dir) ?: [] as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }
        $full = $dir . '/' . $entry;
        if (is_dir($full)) {
            if (!in_array($entry, SKIP_DIRS, true)) {
                collectMarkdown($full, $out);
            }
        } elseif (str_ends_with($entry, '.md')) {
            $out[] = $full;
        }
    }
}

function stripCode(string $text): string
{
    $text = preg_replace('/```.*?```/s', '', $text) ?? $text;
    $text = preg_replace('/`[^`\n]*`/', '', $text) ?? $text;
    return preg_replace('/<!--.*?-->/s', '', $text) ?? $text;
}

function normalizePath(string $path): string
{
    $parts = [];
    foreach (explode('/', $path) as $part) {
        if ($part === '' || $part === '.') {
            continue;
        }
        if ($part === '..') {
            array_pop($parts);
            continue;
        }
        $parts[] = $part;
    }
    return '/' . implode('/', $parts);
}

$files = [];
collectMarkdown($root, $files);

$errors = [];
$warnings = [];

foreach ($files as $file) {
    $rel = substr($file, strlen($root) + 1);
    $text = stripCode((string) file_get_contents($file));

    preg_match_all('/\[[^\]]*\]\(([^)\s]+)(?:\s+"[^"]*")?\)/', $text, $matches);
    foreach ($matches[1] as $target) {
        if (preg_match('/^(https?:|mailto:|#)/', $target)) {
            continue;
        }
        $path = rawurldecode(explode('#', $target)[0]);
        $abs = normalizePath(dirname($file) . '/' . $path);
        if (file_exists($abs)) {
            continue;
        }
        $msg = "{$rel}: link quebrado -> {$target}";
        if (str_starts_with($abs, $root . '/')) {
            $errors[] = $msg;
        } else {
            $warnings[] = $msg;
        }
    }
}

$agents = $root . '/AGENTS.md';
if (!file_exists($agents)) {
    $errors[] = 'AGENTS.md ausente na raiz';
} else {
    $lines = count(explode("\n", (string) file_get_contents($agents)));
    if ($lines > AGENTS_MAX_LINES) {
        $errors[] = "AGENTS.md tem {$lines} linhas (máx " . AGENTS_MAX_LINES . '). Mova detalhes para docs/ e deixe só o ponteiro.';
    }
}

foreach ($warnings as $w) {
    fwrite(STDERR, "aviso: {$w}\n");
}
foreach ($errors as $e) {
    fwrite(STDERR, "erro: {$e}\n");
}
echo count($files) . ' arquivos verificados, ' . count($errors) . ' erro(s), ' . count($warnings) . " aviso(s).\n";
exit($errors ? 1 : 0);
