<?php
// Include Gibbon core
require_once __DIR__ . '/../../gibbon.php';

// Get database connection
$connection2 = $container->get('db');

// HTML header
echo '<!DOCTYPE html>
<html>
<head>
    <title>Fix ChatBot Feedback Table</title>
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
            color: #4CAF50;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }
        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .info-message {
            background-color: #cce5ff;
            color: #004085;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .button {
            display: inline-block;
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 20px;
        }
        .button:hover {
            background-color: #45a049;
        }
        .back-button {
            background-color: #6c757d;
        }
        .back-button:hover {
            background-color: #5a6268;
        }
        pre {
            background-color: #f5f5f5;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
            font-family: monospace;
        }
        .card {
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.12);
            margin-bottom: 20px;
            padding: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Fix ChatBot Feedback Table</h1>
        <p>This tool will check and fix any issues with the ChatBot feedback table.</p>';

// Check if the feedback table exists
try {
    $tableCheck = $connection2->selectOne("SHOW TABLES LIKE 'gibbonChatBotFeedback'");
    if (empty($tableCheck)) {
        echo "<div class='error-message'>
            <strong>Error:</strong> The feedback table does not exist.
            </div>
            <p>Please install the feedback table first.</p>
            <a href='install_feedback_table.php' class='button'>Install Feedback Table</a>
            <a href='chatbot.php' class='button back-button'>Back to ChatBot</a>
            </div></body></html>";
        exit;
    }
    
    // Check table structure
    $columns = $connection2->select("DESCRIBE gibbonChatBotFeedback");
    $columns = array_column($columns, 'Field');
    
    // Define expected columns
    $expectedColumns = [
        'gibbonChatBotFeedbackID',
        'messageID',
        'message',
        'response',
        'feedback',
        'gibbonPersonID',
        'timestamp'
    ];
    
    // Check for missing columns
    $missingColumns = array_diff($expectedColumns, $columns);
    
    if (!empty($missingColumns)) {
        echo "<div class='error-message'>
            <strong>Error:</strong> The feedback table is missing the following columns: " . implode(', ', $missingColumns) . "
            </div>";
        
        // Create missing columns
        echo "<div class='card'>
            <h2>Fixing Table Structure</h2>
            <p>The following SQL will be executed to add the missing columns:</p>";
        
        foreach ($missingColumns as $column) {
            $sql = "";
            
            switch ($column) {
                case 'gibbonChatBotFeedbackID':
                    $sql = "ALTER TABLE gibbonChatBotFeedback ADD COLUMN gibbonChatBotFeedbackID INT AUTO_INCREMENT PRIMARY KEY FIRST";
                    break;
                case 'messageID':
                    $sql = "ALTER TABLE gibbonChatBotFeedback ADD COLUMN messageID VARCHAR(255) NOT NULL AFTER gibbonChatBotFeedbackID";
                    break;
                case 'message':
                    $sql = "ALTER TABLE gibbonChatBotFeedback ADD COLUMN message TEXT AFTER messageID";
                    break;
                case 'response':
                    $sql = "ALTER TABLE gibbonChatBotFeedback ADD COLUMN response TEXT AFTER message";
                    break;
                case 'feedback':
                    $sql = "ALTER TABLE gibbonChatBotFeedback ADD COLUMN feedback ENUM('like', 'dislike') NOT NULL AFTER response";
                    break;
                case 'gibbonPersonID':
                    $sql = "ALTER TABLE gibbonChatBotFeedback ADD COLUMN gibbonPersonID INT AFTER feedback";
                    break;
                case 'timestamp':
                    $sql = "ALTER TABLE gibbonChatBotFeedback ADD COLUMN timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER gibbonPersonID";
                    break;
            }
            
            echo "<pre>$sql</pre>";
            
            try {
                $connection2->statement($sql);
                echo "<div class='success-message'>Column '$column' added successfully.</div>";
            } catch (PDOException $e) {
                echo "<div class='error-message'>Error adding column '$column': " . $e->getMessage() . "</div>";
            }
        }
    } else {
        echo "<div class='success-message'>
            <strong>Success:</strong> The feedback table structure is correct.
            </div>";
    }
    
    // Check for indexes
    $indexes = $connection2->select("SHOW INDEX FROM gibbonChatBotFeedback");
    $indexNames = array_column($indexes, 'Key_name');
    
    // Check for required indexes
    if (!in_array('messageID', $indexNames)) {
        echo "<div class='info-message'>
            <strong>Info:</strong> Adding index on messageID column.
            </div>";
        
        try {
            $connection2->statement("ALTER TABLE gibbonChatBotFeedback ADD INDEX messageID (messageID)");
            echo "<div class='success-message'>Index on messageID added successfully.</div>";
        } catch (PDOException $e) {
            echo "<div class='error-message'>Error adding index: " . $e->getMessage() . "</div>";
        }
    }
    
    if (!in_array('gibbonPersonID', $indexNames)) {
        echo "<div class='info-message'>
            <strong>Info:</strong> Adding index on gibbonPersonID column.
            </div>";
        
        try {
            $connection2->statement("ALTER TABLE gibbonChatBotFeedback ADD INDEX gibbonPersonID (gibbonPersonID)");
            echo "<div class='success-message'>Index on gibbonPersonID added successfully.</div>";
        } catch (PDOException $e) {
            echo "<div class='error-message'>Error adding index: " . $e->getMessage() . "</div>";
        }
    }
    
    // Check for foreign key
    $foreignKeys = $connection2->select("
        SELECT CONSTRAINT_NAME 
        FROM information_schema.TABLE_CONSTRAINTS 
        WHERE CONSTRAINT_TYPE = 'FOREIGN KEY' 
        AND TABLE_NAME = 'gibbonChatBotFeedback'
    ");
    
    if (empty($foreignKeys)) {
        echo "<div class='info-message'>
            <strong>Info:</strong> Adding foreign key constraint for gibbonPersonID.
            </div>";
        
        try {
            $connection2->statement("
                ALTER TABLE gibbonChatBotFeedback 
                ADD CONSTRAINT fk_gibbonPersonID 
                FOREIGN KEY (gibbonPersonID) 
                REFERENCES gibbonPerson(gibbonPersonID) 
                ON DELETE SET NULL
            ");
            echo "<div class='success-message'>Foreign key constraint added successfully.</div>";
        } catch (PDOException $e) {
            echo "<div class='error-message'>Error adding foreign key: " . $e->getMessage() . "</div>";
        }
    }
    
    // Final check
    $finalColumns = $connection2->select("DESCRIBE gibbonChatBotFeedback");
    $finalColumns = array_column($finalColumns, 'Field');
    
    if (count(array_diff($expectedColumns, $finalColumns)) == 0) {
        echo "<div class='success-message'>
            <strong>Success:</strong> The feedback table has been fixed and is now ready to use.
            </div>";
    } else {
        echo "<div class='error-message'>
            <strong>Warning:</strong> Some issues could not be fixed automatically. Please contact your system administrator.
            </div>";
    }
    
} catch (PDOException $e) {
    echo "<div class='error-message'>
        <strong>Database Error:</strong> " . $e->getMessage() . "
    </div>";
}
?>

<div style="margin-top: 30px;">
    <a href="db_check_feedback.php" class="button">Check Feedback Table</a>
    <a href="chatbot.php" class="button back-button">Back to ChatBot</a>
</div>

</div>
</body>
</html> 