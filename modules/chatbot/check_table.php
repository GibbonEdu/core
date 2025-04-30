<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include Gibbon core
require_once '../../gibbon.php';

// Get database connection
$connection2 = $container->get('db');

try {
    // Check if table exists
    $sql = "SHOW TABLES LIKE 'gibbonChatBotFeedback'";
    $result = $connection2->query($sql);
    $tableExists = ($result->rowCount() > 0);
    
    echo "<h3>Table Check Results:</h3>";
    echo "Table exists: " . ($tableExists ? "Yes" : "No") . "<br><br>";
    
    if ($tableExists) {
        // Get table structure
        $sql = "DESCRIBE gibbonChatBotFeedback";
        $result = $connection2->query($sql);
        $columns = $result->fetchAll();
        
        echo "<h4>Table Structure:</h4>";
        echo "<pre>";
        print_r($columns);
        echo "</pre>";
        
        // Check for any records
        $sql = "SELECT COUNT(*) as count FROM gibbonChatBotFeedback";
        $result = $connection2->query($sql);
        $count = $result->fetch();
        
        echo "<h4>Record Count:</h4>";
        echo "Total records: " . $count['count'] . "<br><br>";
        
        // Show last 5 records if any exist
        if ($count['count'] > 0) {
            $sql = "SELECT * FROM gibbonChatBotFeedback ORDER BY timestamp DESC LIMIT 5";
            $result = $connection2->query($sql);
            $records = $result->fetchAll();
            
            echo "<h4>Last 5 Records:</h4>";
            echo "<pre>";
            print_r($records);
            echo "</pre>";
        }
    }
    
    // Check CSRF token in session
    echo "<h3>CSRF Token Check:</h3>";
    echo "CSRF Token exists in session: " . (isset($_SESSION[$guid]['gibbonCSRFToken']) ? "Yes" : "No") . "<br>";
    if (isset($_SESSION[$guid]['gibbonCSRFToken'])) {
        echo "Token value: " . $_SESSION[$guid]['gibbonCSRFToken'] . "<br>";
    }
    
} catch (Exception $e) {
    echo "<h3>Error:</h3>";
    echo "An error occurred: " . $e->getMessage();
} 