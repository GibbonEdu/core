<?php
require_once '../../gibbon.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $db = $container->get('db');
    
    // Read the SQL file
    $sql = file_get_contents(__DIR__ . '/sql/install.sql');
    
    // Split into individual queries
    $queries = array_filter(array_map('trim', explode(';', $sql)));
    
    // Execute each query
    foreach ($queries as $query) {
        if (!empty($query)) {
            $db->statement($query);
            echo "Executed query successfully.<br>";
        }
    }
    
    echo "All tables created successfully!";
    
} catch (Exception $e) {
    die("Installation Error: " . $e->getMessage());
} 