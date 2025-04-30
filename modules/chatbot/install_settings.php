<?php
require_once __DIR__ . '/../../gibbon.php';

try {
    // Insert ChatBot settings
    $data = array(
        'scope' => 'ChatBot',
        'name' => 'openai_api_key',
        'nameDisplay' => 'OpenAI API Key',
        'description' => 'API key for OpenAI integration',
        'value' => ''
    );

    $sql = "INSERT INTO gibbonSetting 
            (scope, name, nameDisplay, description, value) 
            VALUES 
            (:scope, :name, :nameDisplay, :description, :value)
            ON DUPLICATE KEY UPDATE 
            nameDisplay = VALUES(nameDisplay),
            description = VALUES(description)";

    $result = $connection2->prepare($sql);
    $result->execute($data);

    echo "Settings installed successfully!";
} catch (Exception $e) {
    echo "Error installing settings: " . $e->getMessage();
} 