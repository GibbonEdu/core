<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

// Module includes - Include Gibbon core first
require_once '../../gibbon.php';

use Gibbon\Forms\Form;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Http\Url;
use Gibbon\View\Page;

// Setup routes
$page = new Page($container, ['address' => $_GET['q'] ?? '']);

if (!$page instanceof Page) {
    die('Fatal Error: Failed to initialize Page object');
}

// Check access
if (!isActionAccessible($guid, $connection2, '/modules/ChatBot/simple_api_test.php')) {
    // Access denied
    $page->addWarning(__('You do not have access to this action.'));
    return;
}

// Basic initialization
$session = $container->get('session');
$page->breadcrumbs
    ->add(__('ChatBot'), 'chatbot.php')
    ->add(__('API Test'));

// Check CSRF token
if (!isset($_POST['gibbonCSRFToken']) || $_POST['gibbonCSRFToken'] !== $session->get('gibbonCSRFToken')) {
    echo json_encode([
        'success' => false,
        'error' => __('Invalid security token')
    ]);
    exit;
}

// Get test message
$testMessage = $_POST['testMessage'] ?? '';
if (empty($testMessage)) {
    echo json_encode([
        'success' => false,
        'error' => __('No test message provided')
    ]);
    exit;
}

try {
    // Get API key from settings
    $settingGateway = $container->get(SettingGateway::class);
    $apiKey = $settingGateway->getSettingByScope('ChatBot', 'deepseek_api_key');
    
    if (empty($apiKey)) {
        echo json_encode([
            'success' => false,
            'error' => __('No API key configured. Please configure the API key in ChatBot settings.')
        ]);
        exit;
    }

    // API endpoint
    $url = 'https://api.deepseek.com/v1/chat/completions';

    // Prepare request data
    $data = [
        'model' => 'deepseek-chat',
        'messages' => [
            ['role' => 'system', 'content' => 'You are a helpful teaching assistant.'],
            ['role' => 'user', 'content' => $testMessage]
        ],
        'max_tokens' => 100
    ];

    // Start timing
    $startTime = microtime(true);

    // Initialize curl
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ],
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => true,  // Enable SSL verification
        CURLOPT_SSL_VERIFYHOST => 2      // Verify the certificate's name against host
    ]);

    // Execute request
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    // End timing
    $endTime = microtime(true);
    $responseTime = round(($endTime - $startTime) * 1000); // Convert to milliseconds

    curl_close($ch);

    // Log the API test attempt (excluding sensitive data)
    $logData = [
        'timestamp' => date('Y-m-d H:i:s'),
        'user' => $session->get('username'),
        'http_code' => $httpCode,
        'response_time' => $responseTime,
        'success' => ($httpCode === 200)
    ];
    
    // Write to log file
    $logFile = __DIR__ . '/logs/api_tests.log';
    if (!file_exists(dirname($logFile))) {
        mkdir(dirname($logFile), 0755, true);
    }
    file_put_contents($logFile, json_encode($logData) . "\n", FILE_APPEND);

    // Check for curl errors
    if ($error) {
        echo json_encode([
            'success' => false,
            'error' => __('cURL Error') . ': ' . $error
        ]);
        exit;
    }

    // Check HTTP response code
    if ($httpCode !== 200) {
        $errorMessage = $response;
        try {
            $decoded = json_decode($response, true);
            if (isset($decoded['error']['message'])) {
                $errorMessage = $decoded['error']['message'];
            }
        } catch (Exception $e) {
            // Keep original error message if JSON parsing fails
        }
        
        echo json_encode([
            'success' => false,
            'error' => __('API returned HTTP code') . ' ' . $httpCode . ': ' . $errorMessage
        ]);
        exit;
    }

    // Decode response
    $decoded = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode([
            'success' => false,
            'error' => __('Failed to decode API response') . ': ' . json_last_error_msg()
        ]);
        exit;
    }

    // Extract AI response
    $aiResponse = $decoded['choices'][0]['message']['content'] ?? null;
    if (empty($aiResponse)) {
        echo json_encode([
            'success' => false,
            'error' => __('No response content in API reply')
        ]);
        exit;
    }

    // Return success response
    echo json_encode([
        'success' => true,
        'response' => $aiResponse,
        'responseTime' => $responseTime
    ]);

} catch (Exception $e) {
    // Log the error
    error_log('API Test Error: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    
    echo json_encode([
        'success' => false,
        'error' => __('Server error') . ': ' . $e->getMessage()
    ]);
}