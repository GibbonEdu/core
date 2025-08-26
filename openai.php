<?php
declare(strict_types=1);

function getApiKey(bool $required = true): string {
    // will read from file first
    $file = $_ENV['OPENAI_API_KEY_FILE'] ?? getenv('OPENAI_API_KEY_FILE');
    if ($file && is_readable($file)) {
        $v = trim((string)file_get_contents($file));
        if ($v !== '') return $v;
    }
    // Read from env (K8s environment file)
    $v =$_ENV['OPENAI_API_KEY'] ?? getenv('OPENAI_API_KEY');
    if ($v !== false && $v !== '') {
        return trim((string)$v);
    }
    // Fallback to $_ENV (e.g., loaded by Dotenv) only if getenv() was empty
    if (!empty($_ENV['OPENAI_API_KEY'])) {
        return trim((string)$_ENV['OPENAI_API_KEY']);
    }
    if ($required) {
        throw new RuntimeException("OPENAI_API_KEY not set");
    }
    return "";
}