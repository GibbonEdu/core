<?php
declare(strict_types=1);

function getApiKey(bool $required = true): string {
    // Read from env (K8s environment file)
    $v = getenv('OPENAI_API_KEY');
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