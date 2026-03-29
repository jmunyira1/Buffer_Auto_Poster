<?php

function loadEnv($path = '.env')
{ // ← default changed to .env
    if (!file_exists($path)) {
        return false;
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (strpos($line, '#') === 0 || strpos($line, '=') === false) {
            continue;
        }
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value, " \"'"); // strip spaces and quotes

        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
        putenv("$key=$value"); // ← this makes getenv() work too
    }
    return true;
}

function env($key, $default = null)
{
    return $_ENV[$key] ?? getenv($key) ?: $default;
}