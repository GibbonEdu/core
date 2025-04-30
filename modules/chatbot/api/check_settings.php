<?php
// Include Gibbon core
include '../../../gibbon.php';

// Get settings
$sql = "SELECT * FROM gibbonSetting WHERE scope='ChatBot'";
try {
    $result = $connection2->query($sql);
    $settings = $result->fetchAll();
    
    echo "<pre>";
    echo "ChatBot Settings:\n";
    foreach ($settings as $setting) {
        echo "- {$setting['name']}: " . (empty($setting['value']) ? 'Not set' : 'Set') . "\n";
    }
    echo "</pre>";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?> 