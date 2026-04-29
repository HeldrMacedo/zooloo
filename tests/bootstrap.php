<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

$GLOBALS['__tests'] = [];
$GLOBALS['__test_results'] = ['passed' => 0, 'failed' => 0];

function test(string $name, callable $fn): void
{
    $GLOBALS['__tests'][] = ['name' => $name, 'fn' => $fn];
}

function assertTrue(bool $condition, string $message = 'Expected condition to be true'): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function assertSameValue($expected, $actual, string $message = ''): void
{
    if ($expected !== $actual) {
        $suffix = $message ? " ({$message})" : '';
        throw new RuntimeException(
            "Expected [" . var_export($expected, true) . "] but got [" . var_export($actual, true) . "]{$suffix}"
        );
    }
}

function assertContainsText(string $needle, string $haystack, string $message = ''): void
{
    if (strpos($haystack, $needle) === false) {
        $suffix = $message ? " ({$message})" : '';
        throw new RuntimeException("Could not find expected text: {$needle}{$suffix}");
    }
}

function assertThrows(callable $fn, string $expectedMessage): void
{
    try {
        $fn();
    } catch (Throwable $e) {
        if ($expectedMessage !== '' && strpos($e->getMessage(), $expectedMessage) === false) {
            throw new RuntimeException(
                "Exception message mismatch. Expected to contain '{$expectedMessage}', got '{$e->getMessage()}'"
            );
        }
        return;
    }

    throw new RuntimeException('Expected exception was not thrown');
}

function runTests(): int
{
    foreach ($GLOBALS['__tests'] as $test) {
        try {
            ($test['fn'])();
            $GLOBALS['__test_results']['passed']++;
            echo "[PASS] {$test['name']}" . PHP_EOL;
        } catch (Throwable $e) {
            $GLOBALS['__test_results']['failed']++;
            echo "[FAIL] {$test['name']}: {$e->getMessage()}" . PHP_EOL;
        }
    }

    $passed = $GLOBALS['__test_results']['passed'];
    $failed = $GLOBALS['__test_results']['failed'];
    $total = $passed + $failed;

    echo PHP_EOL;
    echo "Total: {$total} | Passed: {$passed} | Failed: {$failed}" . PHP_EOL;

    return $failed === 0 ? 0 : 1;
}
