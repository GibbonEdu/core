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

// Define as API endpoint
header('Content-Type: application/json');

// Basic initialization
$scriptPath = dirname(__DIR__);
$gibbonRoot = realpath($scriptPath . '/../../');

// Include Gibbon core files
require_once $gibbonRoot . '/gibbon.php';

// Check access
if (!isActionAccessible($guid, $connection2, '/modules/ChatBot/feedback.php')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'You do not have access to this action.']);
    exit;
}

// Check for CSRF token
if (!isset($_SESSION[$guid]['gibbonCSRFToken']) || !isset($_POST['gibbonCSRFToken']) || ($_POST['gibbonCSRFToken'] != $_SESSION[$guid]['gibbonCSRFToken'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid security token']);
    exit;
}

// Check if user is logged in
if (!isset($_SESSION[$guid]['gibbonPersonID'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

// Get user ID
$gibbonPersonID = $_SESSION[$guid]['gibbonPersonID'];

// Get database connection
$connection2 = $container->get('db');

// Check if table exists
try {
    $tableCheck = $connection2->selectOne("SHOW TABLES LIKE 'gibbonChatBotFeedback'");
    if (!$tableCheck) {
        http_response_code(500);
        die(json_encode([
            'success' => false,
            'error' => 'Feedback table does not exist. Please install it first.',
            'install_url' => $_SESSION[$guid]['absoluteURL'] . '/modules/ChatBot/install_feedback_table.php'
        ]));
    }
} catch (Exception $e) {
    http_response_code(500);
    die(json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]));
}

// Validate input
if (!isset($_POST['messageID']) || !isset($_POST['feedback'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

$messageID = $_POST['messageID'];
$feedback = $_POST['feedback'];
$comment = $_POST['comment'] ?? null;

// Validate feedback value
if (!in_array($feedback, ['like', 'dislike'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid feedback value']);
    exit;
}

try {
    // Begin transaction
    $connection2->getConnection()->beginTransaction();
    
    // Check if feedback already exists for this message from this user
    $existingFeedback = $connection2->selectOne("
        SELECT gibbonChatBotFeedbackID, feedback 
        FROM gibbonChatBotFeedback 
        WHERE gibbonPersonID = :personID 
        AND messageID = :messageID", 
        [
            'personID' => $gibbonPersonID,
            'messageID' => $messageID
        ]
    );
    
    if ($existingFeedback) {
        // If the same feedback type, remove it (toggle off)
        if ($existingFeedback['feedback'] === $feedback) {
            $connection2->delete('gibbonChatBotFeedback', ['gibbonChatBotFeedbackID' => $existingFeedback['gibbonChatBotFeedbackID']]);
            
            $result = [
                'success' => true,
                'message' => 'Feedback removed',
                'action' => 'removed',
                'feedback' => $feedback,
                'timestamp' => date('Y-m-d H:i:s')
            ];
        } else {
            // Update the existing feedback to the new type
            $data = [
                'feedback' => $feedback,
                'comment' => $comment,
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
            $connection2->update('gibbonChatBotFeedback', $data, ['gibbonChatBotFeedbackID' => $existingFeedback['gibbonChatBotFeedbackID']]);
            
            $result = [
                'success' => true,
                'message' => 'Feedback updated',
                'action' => 'updated',
                'feedback' => $feedback,
                'timestamp' => date('Y-m-d H:i:s')
            ];
        }
    } else {
        // Insert new feedback
        $data = [
            'gibbonPersonID' => $gibbonPersonID,
            'messageID' => $messageID,
            'feedback' => $feedback,
            'comment' => $comment
        ];
        
        $connection2->insert('gibbonChatBotFeedback', $data);
        
        $result = [
            'success' => true,
            'message' => 'Feedback saved',
            'action' => 'added',
            'feedback' => $feedback,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
    
    // Commit transaction
    $connection2->getConnection()->commit();
    
    // Return success
    echo json_encode($result);
    
} catch (Exception $e) {
    // Rollback transaction on error
    if ($connection2->getConnection()->inTransaction()) {
        $connection2->getConnection()->rollBack();
    }
    
    error_log('API Feedback Error: ' . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error saving feedback: ' . $e->getMessage()
    ]);
} 