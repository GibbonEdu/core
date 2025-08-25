<?php
declare(strict_types=1);

function getApiKey(bool \$required = true): string {
    // Read from env (K8s environment file)
    $v = $_ENV["OPENAI_API_KEY"] ?? getenv("OPENAI_API_KEY");
    if ($v !== false && $v !== null && $v !== "") {
        return trim((string)$v);
    }
    if ($required) {
        throw new RuntimeException("OPENAI_API_KEY not set");
    }
    return "";
}