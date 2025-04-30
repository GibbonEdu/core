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
if (!isActionAccessible($guid, $connection2, '/modules/ChatBot/db_check_feedback.php')) {
    // Access denied
    $page->addWarning(__('You do not have access to this action.'));
    return;
}

// Get session
$session = $container->get('session');

// Set page breadcrumb
$page->breadcrumbs
    ->add(__('ChatBot'), 'chatbot.php')
    ->add(__('Debug Feedback Storage'));

// Add CSS
echo "<style>
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
    code {
        background: #f0f0f0;
        padding: 2px 4px;
        border-radius: 3px;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        margin: 20px 0;
    }
    table, th, td {
        border: 1px solid #ddd;
    }
    th, td {
        padding: 10px;
        text-align: left;
    }
    th {
        background-color: #f2f2f2;
    }
    tr:nth-child(even) {
        background-color: #f9f9f9;
    }
</style>";

// HTML header
echo '<!DOCTYPE html>
<html>
<head>
    <title>Debug Feedback Storage</title>
</head>
<body>
    <div class="container">
        <h1>Debug Feedback Storage</h1>';

// Check if the feedback table exists
$tableCheck = $connection2->query("SHOW TABLES LIKE 'gibbonChatBotFeedback'");
$tableExists = $tableCheck->rowCount() > 0;

if (!$tableExists) {
    echo "<div class='error-message'>
        <strong>Table not found:</strong> The gibbonChatBotFeedback table does not exist.
        <br><br>
        <a href='install_feedback_table.php' class='back-button'>Install Feedback Table</a>
    </div>";
    echo "</div></body></html>";
    exit;
}

// Check table structure
$describeTable = $connection2->query("DESCRIBE gibbonChatBotFeedback");
$columns = $describeTable->fetchAll(PDO::FETCH_COLUMN);

echo "<div class='info-message'>
    <strong>Table Structure:</strong><br>
    " . implode(", ", $columns) . "
</div>";

// Direct test: Insert a test record
$testSuccess = false;
try {
    // Test insert
    $messageID = 'test_' . time();
    $feedback = 'like';
    $personID = $session->get('gibbonPersonID'); // Use the current user's ID
    
    // Try an insert operation
    $insertSql = "INSERT INTO gibbonChatBotFeedback 
                 (gibbonPersonID, messageID, feedback, comment) 
                 VALUES (:personID, :messageID, :feedback, :comment)";
    $insertStmt = $connection2->prepare($insertSql);
    $insertStmt->execute([
        ':personID' => $personID,
        ':messageID' => $messageID,
        ':feedback' => $feedback,
        ':comment' => 'Debug test insert'
    ]);
    
    // Get the newly inserted ID
    $newId = $connection2->lastInsertId();
    
    // Show success message
    echo "<div class='success-message'>
        <strong>Test Insert Successful!</strong><br>
        Inserted new record with ID: $newId<br>
        Message ID: $messageID<br>
        Feedback: $feedback<br>
        Person ID: $personID
    </div>";
    
    $testSuccess = true;
} catch (PDOException $e) {
    echo "<div class='error-message'>
        <strong>Test Insert Failed:</strong><br>
        " . $e->getMessage() . "
    </div>";
}

// Show recent records
if ($testSuccess) {
    try {
        $selectSql = "SELECT * FROM gibbonChatBotFeedback ORDER BY timestamp DESC LIMIT 10";
        $result = $connection2->query($selectSql);
        $records = $result->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($records) > 0) {
            echo "<h2>Recent Feedback Records</h2>";
            echo "<table>";
            echo "<tr>";
            foreach (array_keys($records[0]) as $column) {
                echo "<th>$column</th>";
            }
            echo "</tr>";
            
            foreach ($records as $record) {
                echo "<tr>";
                foreach ($record as $value) {
                    echo "<td>" . htmlspecialchars($value) . "</td>";
                }
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<div class='info-message'>No records found in the table.</div>";
        }
    } catch (PDOException $e) {
        echo "<div class='error-message'>
            <strong>Error retrieving records:</strong><br>
            " . $e->getMessage() . "
        </div>";
    }
}

// Provide navigation links
echo "<p>
    <a href='".$session->get('absoluteURL')."/index.php?q=/modules/ChatBot/chatbot.php' class='back-button'>Back to ChatBot</a>
    <a href='db_check_feedback.php' class='back-button'>Check Feedback Database</a>
    <a href='test_feedback_api.php' class='back-button'>Test Feedback API</a>
</p>";

echo "</div></body></html>"; 