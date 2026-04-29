<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$testFiles = glob(__DIR__ . '/*.test.php') ?: [];
sort($testFiles);

foreach ($testFiles as $file) {
    require_once $file;
}

exit(runTests());
