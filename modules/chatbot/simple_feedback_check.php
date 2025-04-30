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
    ->add(__('Feedback Database Check'));

// HTML header
echo '<!DOCTYPE html>
<html>
<head>
    <title>ChatBot Feedback Check</title>
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
        }
        h1 {
            color: #2a7fff;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }
        h2 {
            color: #333;
            margin-top: 30px;
        }
        .message {
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 4px;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
        }
        .info {
            background-color: #cce5ff;
            color: #004085;
        }
        .stats {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            margin-bottom: 30px;
        }
        .stat-card {
            flex: 1;
            background-color: #f5f5f5;
            border-left: 4px solid #2a7fff;
            padding: 15px;
            border-radius: 4px;
            min-width: 150px;
        }
        .stat-title {
            font-size: 14px;
            color: #666;
            margin-bottom: 5px;
        }
        .stat-value {
            font-size: 24px;
            font-weight: bold;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        tr:hover {
            background-color: #f9f9f9;
        }
        .like {
            color: #4CAF50;
        }
        .dislike {
            color: #F44336;
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #2a7fff;
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
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
    </style>
</head>
<body>
    <div class="container">
        <h1>ChatBot Feedback Database Check</h1>
        
        <div class="message info">
            <strong>Connection Info:</strong> Using Gibbon database connection
        </div>';
        
// Check if table exists
try {
    $tableCheck = $connection2->query("SHOW TABLES LIKE 'gibbonChatBotFeedback'");
    $tableExists = $tableCheck->rowCount() > 0;
    
    if (!$tableExists) {
        echo '<div class="message error">
            <strong>Error:</strong> The feedback table does not exist in the database!
        </div>';
        echo '<p>Please install the feedback table first:</p>';
        echo '<a href="install_feedback_table.php" class="button">Install Feedback Table</a>';
        echo '</div></body></html>';
        exit;
    }
    
    echo '<div class="message success">
        <strong>Success:</strong> The feedback table exists in the database.
    </div>';
    
    // Get table structure
    $columns = $connection2->query("DESCRIBE gibbonChatBotFeedback")->fetchAll(PDO::FETCH_ASSOC);
    
    echo '<h2>Table Structure</h2>';
    echo '<table>
        <tr>
            <th>Field</th>
            <th>Type</th>
            <th>Null</th>
            <th>Key</th>
            <th>Default</th>
            <th>Extra</th>
        </tr>';
    
    foreach ($columns as $column) {
        echo '<tr>';
        echo '<td>' . $column['Field'] . '</td>';
        echo '<td>' . $column['Type'] . '</td>';
        echo '<td>' . $column['Null'] . '</td>';
        echo '<td>' . $column['Key'] . '</td>';
        echo '<td>' . $column['Default'] . '</td>';
        echo '<td>' . $column['Extra'] . '</td>';
        echo '</tr>';
    }
    
    echo '</table>';
    
    // Get total counts
    $totalQuery = $connection2->query("SELECT COUNT(*) as total FROM gibbonChatBotFeedback");
    $totalFeedback = $totalQuery->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get feedback by type
    $byTypeQuery = $connection2->query("SELECT feedback, COUNT(*) as count FROM gibbonChatBotFeedback GROUP BY feedback");
    $feedbackByType = $byTypeQuery->fetchAll(PDO::FETCH_ASSOC);
    
    // Format data for display
    $likesCount = 0;
    $dislikesCount = 0;
    
    foreach ($feedbackByType as $item) {
        if ($item['feedback'] === 'like') {
            $likesCount = $item['count'];
        } else if ($item['feedback'] === 'dislike') {
            $dislikesCount = $item['count'];
        }
    }
    
    echo '<h2>Feedback Statistics</h2>';
    echo '<div class="stats">
        <div class="stat-card">
            <div class="stat-title">Total Feedback Entries</div>
            <div class="stat-value">' . $totalFeedback . '</div>
        </div>
        <div class="stat-card">
            <div class="stat-title">Total Likes</div>
            <div class="stat-value like">' . $likesCount . '</div>
        </div>
        <div class="stat-card">
            <div class="stat-title">Total Dislikes</div>
            <div class="stat-value dislike">' . $dislikesCount . '</div>
        </div>';
    
    if ($totalFeedback > 0) {
        $likesPercent = round(($likesCount / $totalFeedback) * 100);
        echo '<div class="stat-card">
            <div class="stat-title">Satisfaction Rate</div>
            <div class="stat-value">' . $likesPercent . '%</div>
        </div>';
    }
    
    echo '</div>';
    
    // Get the 10 most recent feedback entries
    $recentQuery = $connection2->query("SELECT 
        gibbonChatBotFeedbackID, 
        gibbonPersonID,
        messageID, 
        feedback, 
        timestamp
    FROM gibbonChatBotFeedback
    ORDER BY timestamp DESC
    LIMIT 10");
    $recentFeedback = $recentQuery->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($recentFeedback) > 0) {
        echo '<h2>Recent Feedback</h2>';
        echo '<table>
            <tr>
                <th>ID</th>
                <th>Person ID</th>
                <th>Message ID</th>
                <th>Type</th>
                <th>Date & Time</th>
            </tr>';
        
        foreach ($recentFeedback as $feedback) {
            $feedbackClass = $feedback['feedback'] === 'like' ? 'like' : 'dislike';
            
            echo '<tr>';
            echo '<td>' . $feedback['gibbonChatBotFeedbackID'] . '</td>';
            echo '<td>' . $feedback['gibbonPersonID'] . '</td>';
            echo '<td>' . htmlspecialchars(substr($feedback['messageID'], 0, 15)) . '...</td>';
            echo '<td class="' . $feedbackClass . '">' . ucfirst($feedback['feedback']) . '</td>';
            echo '<td>' . $feedback['timestamp'] . '</td>';
            echo '</tr>';
        }
        
        echo '</table>';
    } else {
        echo '<h2>Recent Feedback</h2>';
        echo '<div class="message info">No feedback entries found in the database yet.</div>';
    }
    
} catch (PDOException $e) {
    echo '<div class="message error">
        <strong>Database Error:</strong> ' . $e->getMessage() . '
    </div>';
}

// Provide navigation links
echo "<p>
    <a href='".$session->get('absoluteURL')."/index.php?q=/modules/ChatBot/chatbot.php' class='back-link'>← Back to ChatBot</a>
    <a href='ai_learning.php' class='button' style='margin-left: 10px;'>AI Learning System</a>
    <a href='install_feedback_table.php' class='button' style='float: right;'>Reinstall Feedback Table</a>
</p>";

echo "</div></body></html>";