<?php

function loadEnvFile(string $path): void
{
    if (!is_readable($path)) {
        return;
    }

    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value, " \t\"'");

        if ($key !== '') {
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
}

function isLocalHost(): bool
{
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return str_contains($host, 'localhost') || str_contains($host, '127.0.0.1');
}

function envValue(string $key, string $default = ''): string
{
    if (array_key_exists($key, $_ENV)) {
        return (string) $_ENV[$key];
    }

    $value = getenv($key);
    return $value !== false ? (string) $value : $default;
}

function getDbCredentials(): array
{
    if (isLocalHost()) {
        return [
            'host' => envValue('DB_HOST', 'localhost'),
            'port' => (int) envValue('DB_PORT', '3306'),
            'user' => envValue('DB_USER', 'root'),
            'pass' => envValue('DB_PASSWORD'),
            'name' => envValue('DB_NAME', 'obifunds'),
        ];
    }

    return [
        'host' => 'localhost',
        'user' => 'u850523537_VPS_ObiFundsU',
        'pass' => '@Kt2026#Kakebe',
        'name' => 'u850523537_ObiFunds',
    ];
}
