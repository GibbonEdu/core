<?php
namespace Gibbon\Module\ChatBot;

use Gibbon\Domain\System\SettingGateway;
use Gibbon\Contracts\Database\Connection;

class DeepSeekAPI
{
    private $apiKey;
    private $apiEndpoint = 'https://api.deepseek.com/v1/chat/completions';
    private $modelName = 'deepseek-chat';
    private $maxTokens = 1000;
    private $maxRetries = 3;
    private $timeout = 60;
    private $rateLimitDelay = 1; // Base delay in seconds

    public function __construct($apiKey = null, $connection = null, $container = null)
    {
        if ($apiKey === null) {
            // Try to get API key from Gibbon settings
            try {
                // Try to use container if available (preferred method)
                if (isset($container) && $container->has(SettingGateway::class)) {
                    $settingGateway = $container->get(SettingGateway::class);
                    $apiKey = $settingGateway->getSettingByScope('ChatBot', 'deepseek_api_key');
                    
                    if (empty($apiKey)) {
                        throw new \Exception('DeepSeek API key is empty in settings');
                    }
                }
                // Fallback to using provided database connection
                else if ($connection instanceof Connection) {
                    $sql = "SELECT value FROM gibbonSetting WHERE scope='ChatBot' AND name='deepseek_api_key'";
                    $result = $connection->select($sql);
                    
                    if (empty($result)) {
                        throw new \Exception('DeepSeek API key not found in settings');
                    }
                    
                    $apiKey = $result[0]['value'];
                    if (empty($apiKey)) {
                        throw new \Exception('DeepSeek API key is empty');
                    }
                }
                // Last resort: try to get database connection from globals
                else if (isset($GLOBALS['container']) && $GLOBALS['container']->has(SettingGateway::class)) {
                    $settingGateway = $GLOBALS['container']->get(SettingGateway::class);
                    $apiKey = $settingGateway->getSettingByScope('ChatBot', 'deepseek_api_key');
                    
                    if (empty($apiKey)) {
                        throw new \Exception('DeepSeek API key is empty in settings');
                    }
                } 
                else {
                    throw new \Exception('No valid connection or container available');
                }
            } catch (\Exception $e) {
                error_log('DeepSeekAPI Database Error: ' . $e->getMessage());
                throw new \Exception('Failed to retrieve API key from database: ' . $e->getMessage());
            }
        }
        
        $this->apiKey = $apiKey;
        
        // Try to get model settings if available
        if (isset($container) && $container->has(SettingGateway::class)) {
            $settingGateway = $container->get(SettingGateway::class);
            $modelName = $settingGateway->getSettingByScope('ChatBot', 'model_name');
            $maxTokens = $settingGateway->getSettingByScope('ChatBot', 'max_tokens');
            
            if (!empty($modelName)) $this->modelName = $modelName;
            if (!empty($maxTokens)) $this->maxTokens = intval($maxTokens);
        }
    }

    /**
     * Get a response from the DeepSeek API with rate limiting and exponential backoff
     *
     * @param string $message The user's message
     * @param bool $isTrainingMode Whether training mode is enabled
     * @return array The AI's response
     * @throws \Exception
     */
    public function getResponse($message, $isTrainingMode = false)
    {
        error_log('DeepSeekAPI: Preparing request for message: ' . substr($message, 0, 100) . '...');
        
        // Validate input
        if (empty($message)) {
            error_log('DeepSeekAPI Error: Empty message provided');
            throw new \Exception('Message cannot be empty');
        }

        // Define system prompt based on mode
        $systemPrompt = $this->getSystemPrompt($isTrainingMode);

        // Prepare request data
        $data = [
            'model' => $this->modelName,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $message]
            ],
            'max_tokens' => $this->maxTokens,
            'temperature' => $isTrainingMode ? 0.7 : 0.5,
            'response_format' => ['type' => 'json_object'],
            'stream' => false
        ];

        error_log('DeepSeekAPI: Request data prepared: ' . json_encode($data));

        // Try up to maxRetries times with exponential backoff
        $lastError = null;
        for ($attempt = 1; $attempt <= $this->maxRetries; $attempt++) {
            try {
                error_log("DeepSeekAPI: Attempt $attempt of {$this->maxRetries}");
                
                // Apply rate limiting delay if not first attempt
                if ($attempt > 1) {
                    $delay = $this->rateLimitDelay * pow(2, $attempt - 1); // Exponential backoff
                    error_log("DeepSeekAPI: Waiting {$delay} seconds before retry");
                    sleep($delay);
                }
                
                // Initialize cURL
                $ch = curl_init($this->apiEndpoint);
                if ($ch === false) {
                    throw new \Exception('Failed to initialize cURL');
                }

                // Set cURL options
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => json_encode($data),
                    CURLOPT_HTTPHEADER => [
                        'Content-Type: application/json',
                        'Authorization: Bearer ' . $this->apiKey
                    ],
                    CURLOPT_TIMEOUT => $this->timeout,
                    CURLOPT_SSL_VERIFYPEER => true
                ]);

                error_log('DeepSeekAPI: Sending request to ' . $this->apiEndpoint);

                // Execute request
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curlError = curl_error($ch);
                
                error_log('DeepSeekAPI: Response received - HTTP Code: ' . $httpCode);
                error_log('DeepSeekAPI: Raw response: ' . substr($response, 0, 1000));

                curl_close($ch);

                // Handle cURL errors
                if ($response === false) {
                    throw new \Exception('Failed to connect to AI service: ' . $curlError);
                }

                // Handle rate limiting
                if ($httpCode === 429) {
                    $retryAfter = $this->getRetryAfterHeader($response) ?? $this->rateLimitDelay * pow(2, $attempt);
                    error_log("DeepSeekAPI: Rate limited. Waiting {$retryAfter} seconds.");
                    sleep($retryAfter);
                    continue;
                }

                // Handle other HTTP errors
                if ($httpCode !== 200) {
                    $errorData = json_decode($response, true);
                    $errorMessage = isset($errorData['error']['message']) ? $errorData['error']['message'] : 'Unknown error';
                    throw new \Exception('AI service error: ' . $errorMessage);
                }

                // Parse response
                $responseData = json_decode($response, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \Exception('Invalid response from AI service: ' . json_last_error_msg());
                }

                // Extract message content
                if (!isset($responseData['choices'][0]['message']['content'])) {
                    throw new \Exception('Unexpected response format from AI service');
                }

                // Parse the JSON content from the response
                $content = $responseData['choices'][0]['message']['content'];
                $parsedContent = json_decode($content, true);
                
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \Exception('Invalid JSON in AI response: ' . json_last_error_msg());
                }

                error_log('DeepSeekAPI: Successfully parsed response content');
                
                return [
                    'success' => true,
                    'answer' => $parsedContent['answer'] ?? $content,
                    'timestamp' => date('Y-m-d H:i:s'),
                    'usage' => $responseData['usage'] ?? null
                ];

            } catch (\Exception $e) {
                $lastError = $e;
                error_log('DeepSeekAPI Error on attempt ' . $attempt . ': ' . $e->getMessage());
                
                // Don't retry on certain errors
                if ($this->isNonRetryableError($e)) {
                    throw $e;
                }
                
                if ($attempt < $this->maxRetries) {
                    continue;
                }
            }
        }

        // If we get here, all retries failed
        throw $lastError ?? new \Exception('Failed to get response from AI service after ' . $this->maxRetries . ' attempts');
    }

    /**
     * Get the system prompt based on mode
     *
     * @param bool $trainingMode
     * @return string
     */
    private function getSystemPrompt($trainingMode)
    {
        if ($trainingMode) {
            return "You are an intelligent AI assistant trained to provide detailed, comprehensive guidance on any topic. " .
                   "Format your responses in JSON with markdown formatting. Use the following structure:\n\n" .
                   "{\n" .
                   "    \"answer\": {\n" .
                   "        \"title\": \"# Main Topic Title\",\n" .
                   "        \"overview\": \"Brief overview with **bold** and *italic* text where appropriate\",\n" .
                   "        \"main_points\": [\n" .
                   "            {\n" .
                   "                \"title\": \"## Point Title\",\n" .
                   "                \"description\": \"Detailed explanation with `code` and ***emphasis*** where needed\",\n" .
                   "                \"examples\": [\n" .
                   "                    \"```language\\ncode example\\n```\",\n" .
                   "                    \"1. First example with **formatting**\",\n" .
                   "                    \"2. Second example with *formatting*\"\n" .
                   "                ]\n" .
                   "            }\n" .
                   "        ],\n" .
                   "        \"additional_info\": {\n" .
                   "            \"references\": [\"* [Reference Title](link)\"],\n" .
                   "            \"related_topics\": [\"* Related topic with **emphasis**\"],\n" .
                   "            \"practical_applications\": [\"1. Application with `code` example\"]\n" .
                   "        },\n" .
                   "        \"summary\": \"### Summary\\nConcise summary with key points and *formatting*\"\n" .
                   "    }\n" .
                   "}\n\n" .
                   "Use markdown formatting consistently:\n" .
                   "- Headers: #, ##, ###\n" .
                   "- Emphasis: **bold**, *italic*, ***bold-italic***\n" .
                   "- Lists: 1. 2. 3. for ordered, * or - for unordered\n" .
                   "- Code: `inline code`, ```language\\ncode block\\n```\n" .
                   "- Links: [text](url)\n" .
                   "- Blockquotes: > quote\n" .
                   "- Tables: | Header | Header |\\n|---|---|\n";
        } else {
            return "You are a helpful AI assistant. " .
                   "Format your responses in JSON with markdown formatting:\n\n" .
                   "{\n" .
                   "    \"answer\": {\n" .
                   "        \"content\": \"Response with **bold**, *italic*, `code`, and other markdown formatting\"\n" .
                   "    }\n" .
                   "}\n";
        }
    }

    /**
     * Test the API connection
     *
     * @return bool
     * @throws \Exception
     */
    public function testConnection()
    {
        try {
            // Check DNS resolution first
            $host = parse_url($this->apiEndpoint, PHP_URL_HOST);
            if (!$host) {
                throw new \Exception('Invalid API endpoint URL');
            }

            $dnsCheck = dns_get_record($host, DNS_A);
            if (empty($dnsCheck)) {
                throw new \Exception('Could not resolve API host');
            }

            error_log('DeepSeek API Test: DNS resolution successful for ' . $host);
            error_log('DeepSeek API Test: IP addresses - ' . implode(', ', array_column($dnsCheck, 'ip')));

            $response = $this->getResponse('Test connection');
            return true;
        } catch (\Exception $e) {
            error_log('DeepSeek API Test Connection Failed: ' . $e->getMessage());
            throw new \Exception('API connection test failed: ' . $e->getMessage());
        }
    }

    /**
     * Get retry-after value from response headers
     *
     * @param string $response The raw response
     * @return int|null The number of seconds to wait
     */
    private function getRetryAfterHeader($response)
    {
        $headers = [];
        $rawHeaders = substr($response, 0, strpos($response, "\r\n\r\n"));
        foreach (explode("\r\n", $rawHeaders) as $line) {
            if (strpos($line, ':') !== false) {
                list($key, $value) = explode(':', $line, 2);
                $headers[strtolower(trim($key))] = trim($value);
            }
        }
        
        return isset($headers['retry-after']) ? (int)$headers['retry-after'] : null;
    }

    /**
     * Check if an error should not be retried
     *
     * @param \Exception $error The error to check
     * @return bool
     */
    private function isNonRetryableError(\Exception $error)
    {
        $nonRetryableMessages = [
            'API key not found',
            'Invalid API key',
            'Permission denied',
            'Invalid request format'
        ];
        
        foreach ($nonRetryableMessages as $message) {
            if (stripos($error->getMessage(), $message) !== false) {
                return true;
            }
        }
        
        return false;
    }
} 