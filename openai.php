<?php
declare(strict_types=1);

function getApiKey(bool $required = true): string {
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    // get key from k8s secrete file first
    $path = getenv('OPENAI_API_KEY_FILE');
    if (!$path || !is_readable($path)) {        
        $path = '/run/secrets/openai/OPENAI_API_KEY';
    }
    if (is_readable($path)) {
        $k = trim((string) @file_get_contents($path));
        if ($k !== '') {
            return $cache = $k;
        }
    }
    // environment variables (may be unset by design)
    foreach (['OPENAI_API_KEY', 'GIBBON_OPENAI_API_KEY'] as $name) {
        $v = getenv($name);
        if ($v !== false && $v !== '') {
            return $cache = trim($v);
        }
    }
    if ($required) {
        throw new \RuntimeException('Missing OpenAI API key (file/env not found).');
    }
    return $cache = '';
    
}