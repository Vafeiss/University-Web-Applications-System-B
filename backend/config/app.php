<?php
/**
 * Shared application URL/path helpers.
 * Detects the app base path from the current request so the project can run
 * under different folders like /University-Web-Applications-System-B or /student.
 */

if (defined('UNISUPPORT_APP_CONFIG_LOADED')) {
    return;
}

define('UNISUPPORT_APP_CONFIG_LOADED', true);

function app_detect_base_path(): string
{
    $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));

    if ($scriptName === '') {
        return '';
    }

    foreach (['/frontend/', '/backend/', '/database/'] as $marker) {
        $position = strpos($scriptName, $marker);
        if ($position !== false) {
            $basePath = substr($scriptName, 0, $position);
            return ($basePath === '/' || $basePath === '') ? '' : rtrim($basePath, '/');
        }
    }

    $directory = str_replace('\\', '/', dirname($scriptName));
    if ($directory === '/' || $directory === '.' || $directory === '\\') {
        return '';
    }

    return rtrim($directory, '/');
}

function app_base_path(): string
{
    static $basePath = null;

    if ($basePath === null) {
        $basePath = app_detect_base_path();
    }

    return $basePath;
}

function app_url(string $path = ''): string
{
    $basePath = app_base_path();
    $cleanPath = ltrim($path, '/');

    if ($cleanPath === '') {
        return $basePath !== '' ? $basePath : '/';
    }

    return ($basePath !== '' ? $basePath : '') . '/' . $cleanPath;
}

function app_frontend_url(string $path = ''): string
{
    $cleanPath = ltrim($path, '/');
    return app_url('frontend' . ($cleanPath !== '' ? '/' . $cleanPath : ''));
}

function app_backend_url(string $path = ''): string
{
    $cleanPath = ltrim($path, '/');
    return app_url('backend' . ($cleanPath !== '' ? '/' . $cleanPath : ''));
}

function app_absolute_url(string $path = ''): string
{
    $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
    $forwardedProto = strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
    $https = !empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off';
    $isSecure = $https || $forwardedProto === 'https' || (string) ($_SERVER['SERVER_PORT'] ?? '') === '443';
    $scheme = $isSecure ? 'https' : 'http';

    return $scheme . '://' . $host . app_url($path);
}
