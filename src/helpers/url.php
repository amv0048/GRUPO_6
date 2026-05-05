<?php

function app_base_path(): string
{
    static $basePath = null;

    if ($basePath !== null) {
        return $basePath;
    }

    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $scriptFile = realpath($_SERVER['SCRIPT_FILENAME'] ?? '');
    $projectRoot = realpath(__DIR__ . '/../../');

    if ($scriptName !== '' && $scriptFile !== false && $projectRoot !== false) {
        $scriptFile = str_replace('\\', '/', $scriptFile);
        $projectRoot = str_replace('\\', '/', $projectRoot);

        if ($scriptFile === $projectRoot . '/index.php') {
            $basePath = rtrim(substr($scriptName, 0, -strlen('index.php')), '/');
            return $basePath;
        }

        if (str_starts_with($scriptFile, $projectRoot . '/')) {
            $relativeScript = substr($scriptFile, strlen($projectRoot) + 1);
            if ($relativeScript !== '' && str_ends_with($scriptName, $relativeScript)) {
                $basePath = rtrim(substr($scriptName, 0, -strlen($relativeScript)), '/');
                return $basePath;
            }
        }
    }

    $basePath = '';
    return $basePath;
}

function app_url(string $path = ''): string
{
    $path = trim($path);
    if ($path === '') {
        $base = app_base_path();
        return $base === '' ? '/' : $base . '/';
    }

    $path = str_replace('\\', '/', $path);

    if (preg_match('#^(https?:)?//#i', $path) || str_starts_with($path, 'data:')) {
        return $path;
    }

    $base = app_base_path();
    if ($base !== '' && ($path === $base || str_starts_with($path, $base . '/'))) {
        return $path;
    }

    return ($base === '' ? '' : $base) . '/' . ltrim($path, '/');
}

function redirect_to(string $path): void
{
    header('Location: ' . app_url($path));
    exit();
}

function app_rewrite_output(string $buffer): string
{
    $base = app_base_path();
    if ($base === '') {
        return $buffer;
    }

    $quotedBase = preg_quote(ltrim($base, '/'), '#');

    $replacements = [
        '#((?:href|src|action)\s*=\s*["\'])/(?!/)(?!' . $quotedBase . '/)#i' => '$1' . $base . '/',
        '#(fetch\(\s*["\'])/(?!/)(?!' . $quotedBase . '/)#i' => '$1' . $base . '/',
        '#((?:window\.)?location(?:\.href)?\s*=\s*["\'])/(?!/)(?!' . $quotedBase . '/)#i' => '$1' . $base . '/',
    ];

    return preg_replace(array_keys($replacements), array_values($replacements), $buffer);
}

function app_rewrite_location_headers(): void
{
    $base = app_base_path();
    if ($base === '' || headers_sent()) {
        return;
    }

    foreach (headers_list() as $header) {
        if (!preg_match('#^Location:\s+/(?!/)(.*)$#i', $header, $matches)) {
            continue;
        }

        $target = $matches[1];
        if (str_starts_with('/' . $target, $base . '/')) {
            continue;
        }

        header_remove('Location');
        header('Location: ' . app_url($target));
        break;
    }
}

function app_start_output_buffer(): void
{
    static $started = false;

    if ($started) {
        return;
    }

    $started = true;

    if (function_exists('header_register_callback')) {
        header_register_callback('app_rewrite_location_headers');
    }

    ob_start('app_rewrite_output');
}
