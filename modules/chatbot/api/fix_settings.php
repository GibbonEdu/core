<?php
// Include Gibbon core with absolute path
require_once dirname(__FILE__) . '/../../../gibbon.php';

try {
    // First check if setting exists
    $sql = "SELECT * FROM gibbonSetting WHERE scope='ChatBot' AND name='deepseek_api_key'";
    $result = $connection2->query($sql);
    
    if ($result->rowCount() == 0) {
        // Setting doesn't exist, create it
        $sql = "INSERT INTO gibbonSetting (scope, name, nameDisplay, description, value) 
                VALUES ('ChatBot', 'deepseek_api_key', 'DeepSeek API Key', 'API key for DeepSeek AI service', '')";
        $connection2->query($sql);
        echo "Created DeepSeek API key setting<br/>";
    } else {
        echo "DeepSeek API key setting exists<br/>";
        $setting = $result->fetch();
        if (empty($setting['value'])) {
            echo "Warning: API key is not set<br/>";
        }
    }
    
    // Show all ChatBot settings
    $sql = "SELECT * FROM gibbonSetting WHERE scope='ChatBot'";
    $result = $connection2->query($sql);
    $settings = $result->fetchAll();
    
    echo "<pre>";
    echo "Current ChatBot Settings:\n";
    foreach ($settings as $setting) {
        echo "- {$setting['name']}: " . (empty($setting['value']) ? 'Not set' : 'Set') . "\n";
        echo "  Description: {$setting['description']}\n";
    }
    echo "</pre>";
    
    // Add link to settings page
    echo "<p>To update the API key, <a href='../../settings.php'>go to ChatBot Settings</a></p>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br/>";
    echo "SQL State: " . $e->getCode() . "<br/>";
    error_log("ChatBot settings error: " . $e->getMessage());
} 