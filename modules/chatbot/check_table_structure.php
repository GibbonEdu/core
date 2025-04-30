<?php
// Connection parameters
$host = 'localhost';
$port = '8889'; // Default MAMP port
$dbname = 'chhs';
$username = 'admin';
$password = '123JUBani';

// Define possible socket paths to try
$possibleSockets = [
    '/Applications/MAMP/tmp/mysql/mysql.sock', // MAMP default
    '/tmp/mysql.sock',                        // macOS default
    '/var/mysql/mysql.sock',                  // Another common macOS location
    '/var/run/mysqld/mysqld.sock'             // Linux default
];

// Try to connect
$connected = false;
$pdo = null;
$connectionError = '';

// Try each socket path
foreach ($possibleSockets as $socket) {
    try {
        // Create a direct PDO connection
        $dsn = "mysql:host=$host;port=$port;dbname=$dbname;unix_socket=$socket";
        echo "Trying to connect with socket: $socket<br>";
        
        $pdo = new PDO($dsn, $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Connection successful
        echo "Successfully connected using socket: $socket<br>";
        $connected = true;
        break;
        
    } catch (PDOException $e) {
        echo "Failed to connect with socket $socket: " . $e->getMessage() . "<br>";
        // Continue to next socket
    }
}

// If all sockets failed, try TCP/IP connection (no socket)
if (!$connected) {
    try {
        echo "Trying TCP/IP connection (no socket)<br>";
        $dsn = "mysql:host=$host;port=$port;dbname=$dbname";
        $pdo = new PDO($dsn, $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        echo "TCP/IP connection successful<br>";
        $connected = true;
        
    } catch (PDOException $e) {
        echo "TCP/IP connection failed: " . $e->getMessage() . "<br>";
        exit;
    }
}

// If all connection attempts failed
if (!$connected) {
    die('All database connection attempts failed');
}

// Check table structure
try {
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h2>Tables in database '$dbname':</h2>";
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>$table</li>";
    }
    echo "</ul>";
    
    // Check if gibbonChatBotFeedback exists
    if (in_array('gibbonChatBotFeedback', $tables)) {
        echo "<h2>Structure of gibbonChatBotFeedback table:</h2>";
        
        // Get table structure
        $columns = $pdo->query("DESCRIBE gibbonChatBotFeedback")->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>{$column['Field']}</td>";
            echo "<td>{$column['Type']}</td>";
            echo "<td>{$column['Null']}</td>";
            echo "<td>{$column['Key']}</td>";
            echo "<td>{$column['Default']}</td>";
            echo "<td>{$column['Extra']}</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        
        // Check sample data
        $sampleData = $pdo->query("SELECT * FROM gibbonChatBotFeedback LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($sampleData)) {
            echo "<h2>Sample data from gibbonChatBotFeedback table:</h2>";
            
            echo "<table border='1' cellpadding='5' cellspacing='0'>";
            
            // Print headers
            echo "<tr>";
            foreach (array_keys($sampleData[0]) as $key) {
                echo "<th>$key</th>";
            }
            echo "</tr>";
            
            // Print data
            foreach ($sampleData as $row) {
                echo "<tr>";
                foreach ($row as $value) {
                    echo "<td>" . htmlspecialchars($value) . "</td>";
                }
                echo "</tr>";
            }
            
            echo "</table>";
        } else {
            echo "<p>No data in the gibbonChatBotFeedback table yet.</p>";
        }
    } else {
        echo "<p style='color: red;'><strong>ERROR:</strong> Table gibbonChatBotFeedback does not exist!</p>";
    }
    
} catch (PDOException $e) {
    echo "Error checking table structure: " . $e->getMessage();
}
?> 