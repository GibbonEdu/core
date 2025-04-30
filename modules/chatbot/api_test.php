<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include Gibbon core files
require_once __DIR__ . '/../../gibbon.php';

// Get database connection from Gibbon
$connection2 = $container->get('db');

echo "<html><head><title>DeepSeek API Test</title>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    h1, h2 { color: #333; }
    pre { background-color: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .info { color: blue; }
    .section { margin-bottom: 30px; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
</style>";
echo "</head><body>";
echo "<h1>DeepSeek API Connection Test</h1>";

// PART 1: Database Connection and API Key Retrieval
echo "<div class='section'>";
echo "<h2>1. Database Connection</h2>";

try {
    // Get the API key from the database using Gibbon's connection
    $query = $connection2->prepare("SELECT value FROM gibbonSetting WHERE scope='ChatBot' AND name='deepseek_api_key'");
    $query->execute();
    $result = $query->fetch(PDO::FETCH_ASSOC);
    $apiKey = $result ? $result['value'] : '';
    
    if (empty($apiKey)) {
        echo "<p class='error'>❌ No API key found in the database.</p>";
        echo "<p>Please configure the DeepSeek API key in the ChatBot settings.</p>";
    } else {
        $maskedKey = substr($apiKey, 0, 4) . str_repeat('*', strlen($apiKey) - 8) . substr($apiKey, -4);
        echo "<p class='success'>✅ API key found in database: $maskedKey</p>";
        
        // Get model name
        $modelQuery = $connection2->prepare("SELECT value FROM gibbonSetting WHERE scope='ChatBot' AND name='model_name'");
        $modelQuery->execute();
        $modelResult = $modelQuery->fetch(PDO::FETCH_ASSOC);
        $model = $modelResult ? $modelResult['value'] : 'deepseek-chat';
        
        // Get max tokens
        $tokensQuery = $connection2->prepare("SELECT value FROM gibbonSetting WHERE scope='ChatBot' AND name='max_tokens'");
        $tokensQuery->execute();
        $tokensResult = $tokensQuery->fetch(PDO::FETCH_ASSOC);
        $maxTokens = $tokensResult ? $tokensResult['value'] : '2000';
        
        echo "<p><strong>Model:</strong> $model</p>";
        echo "<p><strong>Max Tokens:</strong> $maxTokens</p>";
    }
} catch (PDOException $e) {
    echo "<p class='error'>❌ Database Error: " . $e->getMessage() . "</p>";
    $apiKey = '';
}
echo "</div>";

// PART 2: API Connection Test
if (!empty($apiKey)) {
    echo "<div class='section'>";
    echo "<h2>2. DeepSeek API Connection Test</h2>";
    echo "<p>Testing connection to DeepSeek API...</p>";
    
    // Create a basic curl request to test the API key
    $ch = curl_init('https://api.deepseek.com/v1/chat/completions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey
    ]);
    
    $data = [
        'model' => $model,
        'messages' => [
            ['role' => 'system', 'content' => 'You are a helpful assistant.'],
            ['role' => 'user', 'content' => 'Say hello!']
        ],
        'max_tokens' => intval($maxTokens)
    ];
    
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $info = curl_getinfo($ch);
    $error = curl_error($ch);
    curl_close($ch);
    
    // Display cURL info
    echo "<h3>cURL Info:</h3>";
    echo "<pre>";
    echo "HTTP Status: " . $info['http_code'] . "\n";
    echo "Total time: " . $info['total_time'] . " seconds\n";
    echo "Connect time: " . $info['connect_time'] . " seconds\n";
    echo "Size download: " . $info['size_download'] . " bytes\n";
    echo "</pre>";
    
    if ($error) {
        echo "<p class='error'>❌ API Connection Error: " . htmlspecialchars($error) . "</p>";
    } elseif ($info['http_code'] >= 400) {
        echo "<p class='error'>❌ API Error: HTTP Status " . $info['http_code'] . "</p>";
        $responseData = json_decode($response, true);
        if ($responseData && isset($responseData['error'])) {
            echo "<p class='error'>Error Message: " . htmlspecialchars($responseData['error']['message']) . "</p>";
            if (isset($responseData['error']['type'])) {
                echo "<p class='error'>Error Type: " . htmlspecialchars($responseData['error']['type']) . "</p>";
            }
        }
        echo "<p>Raw Response:</p>";
        echo "<pre>" . htmlspecialchars($response) . "</pre>";
    } else {
        echo "<p class='success'>✅ API Connection Successful! HTTP Status " . $info['http_code'] . "</p>";
        $responseData = json_decode($response, true);
        if ($responseData) {
            echo "<p class='success'>Response received from DeepSeek API!</p>";
            if (isset($responseData['choices'][0]['message']['content'])) {
                $aiResponse = $responseData['choices'][0]['message']['content'];
                echo "<p><strong>AI Response:</strong></p>";
                echo "<pre>" . htmlspecialchars($aiResponse) . "</pre>";
            }
            if (isset($responseData['usage'])) {
                echo "<p><strong>Token Usage:</strong></p>";
                echo "<pre>";
                echo "Prompt tokens: " . $responseData['usage']['prompt_tokens'] . "\n";
                echo "Completion tokens: " . $responseData['usage']['completion_tokens'] . "\n";
                echo "Total tokens: " . $responseData['usage']['total_tokens'] . "\n";
                echo "</pre>";
            }
        }
    }
    echo "</div>";
    
    // PART 3: JavaScript Integration Test
    echo "<div class='section'>";
    echo "<h2>3. JavaScript Integration Test</h2>";
    echo "<p>This test will attempt to use the same API to send a message using JavaScript (similar to how the chatbot works).</p>";
    echo "<div id='jsTestArea'>";
    echo "<textarea id='testMessage' style='width: 100%; height: 100px;' placeholder='Enter a test message...'></textarea><br>";
    echo "<button id='testButton' style='padding: 10px; margin-top: 10px;'>Send Test Message</button>";
    echo "<div id='jsResult' style='margin-top: 15px;'></div>";
    echo "</div>";
    
    echo "<script>
    document.getElementById('testButton').addEventListener('click', async function() {
        const message = document.getElementById('testMessage').value;
        if (!message) {
            document.getElementById('jsResult').innerHTML = '<p class=\"error\">Please enter a message</p>';
            return;
        }
        
        document.getElementById('jsResult').innerHTML = '<p>Sending request to DeepSeek API...</p>';
        
        try {
            const response = await fetch('https://api.deepseek.com/v1/chat/completions', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': 'Bearer " . $apiKey . "'
                },
                body: JSON.stringify({
                    model: '" . $model . "',
                    messages: [
                        {role: 'system', content: 'You are a helpful assistant.'},
                        {role: 'user', content: message}
                    ],
                    max_tokens: " . $maxTokens . "
                })
            });
            
            const result = await response.json();
            let resultHtml = '<h3>API Response:</h3>';
            
            if (response.ok) {
                resultHtml += '<p class=\"success\">✅ Success! HTTP Status: ' + response.status + '</p>';
                if (result.choices && result.choices[0] && result.choices[0].message) {
                    resultHtml += '<p><strong>AI Response:</strong></p>';
                    resultHtml += '<pre>' + result.choices[0].message.content + '</pre>';
                }
                if (result.usage) {
                    resultHtml += '<p><strong>Token Usage:</strong></p>';
                    resultHtml += '<pre>';
                    resultHtml += 'Prompt tokens: ' + result.usage.prompt_tokens + '\\n';
                    resultHtml += 'Completion tokens: ' + result.usage.completion_tokens + '\\n';
                    resultHtml += 'Total tokens: ' + result.usage.total_tokens + '\\n';
                    resultHtml += '</pre>';
                }
            } else {
                resultHtml += '<p class=\"error\">❌ Error! HTTP Status: ' + response.status + '</p>';
                if (result.error) {
                    resultHtml += '<p class=\"error\">Error Message: ' + result.error.message + '</p>';
                    if (result.error.type) {
                        resultHtml += '<p class=\"error\">Error Type: ' + result.error.type + '</p>';
                    }
                }
                resultHtml += '<pre>' + JSON.stringify(result, null, 2) + '</pre>';
            }
            
            document.getElementById('jsResult').innerHTML = resultHtml;
        } catch (error) {
            document.getElementById('jsResult').innerHTML = '<p class=\"error\">❌ JavaScript Error: ' + error.message + '</p>';
        }
    });
    </script>";
    echo "</div>";
}

// PART 4: Troubleshooting and Recommendations
echo "<div class='section'>";
echo "<h2>4. Troubleshooting</h2>";

echo "<h3>Common Issues:</h3>";
echo "<ul>";
echo "<li><strong>Invalid API Key</strong>: Ensure the API key is copied correctly from DeepSeek.</li>";
echo "<li><strong>CORS Issues</strong>: API requests from JavaScript may be blocked by CORS policy.</li>";
echo "<li><strong>Network Connectivity</strong>: Check if your server can reach api.deepseek.com.</li>";
echo "<li><strong>Rate Limiting</strong>: DeepSeek may rate limit your requests.</li>";
echo "<li><strong>Model Availability</strong>: Ensure the selected model is available in your DeepSeek plan.</li>";
echo "</ul>";

echo "<h3>Next Steps:</h3>";
echo "<ul>";
echo "<li>Check the PHP error logs for any additional information.</li>";
echo "<li>Try restarting your web server.</li>";
echo "<li>Verify your DeepSeek account is active and has sufficient credits.</li>";
echo "<li>Check if you need to create a proxy endpoint on your server to avoid CORS issues.</li>";
echo "</ul>";
echo "</div>";

echo "</body></html>";
?> 