<?php
/**
 * Shared environment loader.
 * Loads variables from the project root .env file when Dotenv is available.
 */

if (defined('UNISUPPORT_ENV_LOADER_INCLUDED')) {
    return;
}

define('UNISUPPORT_ENV_LOADER_INCLUDED', true);

function app_load_env(): void
{
    static $loaded = false;

    if ($loaded) {
        return;
    }

    $autoloadPath = __DIR__ . '/../../vendor/autoload.php';
    if (file_exists($autoloadPath)) {
        require_once $autoloadPath;
    }

    if (!class_exists(\Dotenv\Dotenv::class)) {
        $loaded = true;
        return;
    }

    $envPath = __DIR__ . '/../../.env';
    if (file_exists($envPath)) {
        $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
        $dotenv->safeLoad();
    }

    $loaded = true;
}

app_load_env();
