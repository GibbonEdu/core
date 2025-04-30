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

// Module includes
require_once '../../gibbon.php';

// Import required classes
use Gibbon\View\Page;
use Gibbon\Domain\System\SettingGateway;

// Check if core Gibbon functions are available
if (!function_exists('__') || !function_exists('isActionAccessible')) {
    die('Fatal Error: Gibbon core functions not loaded');
}

// Basic initialization
if (!isset($container)) {
    die('Fatal Error: Gibbon container not initialized');
}

if (!isset($guid) || !isset($connection2)) {
    die('Fatal Error: Gibbon core variables not initialized');
}

// Setup routes
$page = new Page($container, ['address' => $_GET['q'] ?? '']);

if (!$page instanceof Page) {
    die('Fatal Error: Failed to initialize Page object');
}

// Check access
if (!isActionAccessible($guid, $connection2, '/modules/ChatBot/install_api_key_setting.php')) {
    // Access denied
    $page->addWarning(__('You do not have access to this action.'));
    return;
}

// Get session
$session = $container->get('session');

// Set page breadcrumb
$page->breadcrumbs
    ->add(__('ChatBot'), 'chatbot.php')
    ->add(__('Install API Key Setting'));

// HTML header
echo '<!DOCTYPE html>
<html>
<head>
    <title>Install API Key Setting</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 20px;
            padding: 0;
            color: #333;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2a7fff;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .info {
            background-color: #cce5ff;
            color: #004085;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .back-button {
            display: inline-block;
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 20px;
            margin-right: 10px;
        }
        .back-button:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Install API Key Setting</h1>';

try {
    // Check if setting already exists
    $sql = "SELECT COUNT(*) FROM gibbonSetting WHERE scope='ChatBot' AND name='deepseek_api_key'";
    $result = $connection2->query($sql);
    $exists = ($result->fetchColumn() > 0);

    if (!$exists) {
        // Add new setting
        $data = [
            'scope' => 'ChatBot',
            'name' => 'deepseek_api_key',
            'nameDisplay' => 'DeepSeek API Key',
            'description' => 'API key for DeepSeek AI integration',
            'value' => ''
        ];

        $sql = "INSERT INTO gibbonSetting (scope, name, nameDisplay, description, value) 
                VALUES (:scope, :name, :nameDisplay, :description, :value)";
        
        $stmt = $connection2->prepare($sql);
        $stmt->execute($data);
        
        echo "<div class='success'>Successfully added DeepSeek API key setting.</div>";
    } else {
        echo "<div class='info'>DeepSeek API key setting already exists.</div>";
    }
    
    // Try using SettingGateway
    try {
        $settingGateway = $container->get(SettingGateway::class);
        $apiKey = $settingGateway->getSettingByScope('ChatBot', 'deepseek_api_key');
        
        echo "<div class='info'>API Key retrieved via SettingGateway: <strong>" . htmlspecialchars($apiKey) . "</strong></div>";
    } catch (Exception $e) {
        echo "<div class='error'>Error using SettingGateway: " . $e->getMessage() . "</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>Error: " . $e->getMessage() . "</div>";
}

// Provide navigation links
echo "<p>
    <a href='".$session->get('absoluteURL')."/index.php?q=/modules/ChatBot/chatbot.php' class='back-button'>Back to ChatBot</a>
    <a href='db_test_api_key.php' class='back-button'>Test API Key</a>
    <a href='simple_api_test.php' class='back-button'>Test API</a>
</p>";

echo "</div></body></html>";