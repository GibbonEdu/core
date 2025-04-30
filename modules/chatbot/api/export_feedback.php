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

// Get session
$session = $container->get('session');

// Check if user is logged in
if (!$session->has('gibbonPersonID')) {
    header('Location: ' . $session->get('absoluteURL') . '/index.php?q=modules/ChatBot/feedback.php');
    exit;
}

// Check if user has admin access
if (!isActionAccessible($guid, $connection2, '/modules/ChatBot/feedback.php')) {
    header('Location: ' . $session->get('absoluteURL') . '/index.php?q=modules/ChatBot/feedback.php');
    exit;
}

// Set filename with date
$filename = 'chatbot_feedback_export_' . date('Y-m-d') . '.csv';

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

// Create output stream
$output = fopen('php://output', 'w');

// Write CSV header
fputcsv($output, [
    'Feedback ID',
    'Message ID',
    'Type',
    'User ID',
    'User Name',
    'Comment',
    'Date & Time'
]);

try {
    // Get all feedback data with user information
    $query = $connection2->prepare("SELECT 
            f.gibbonChatBotFeedbackID, 
            f.messageID, 
            f.feedback, 
            f.comment,
            f.timestamp,
            f.gibbonPersonID,
            p.username,
            p.surname,
            p.firstName,
            p.title
        FROM gibbonChatBotFeedback f
        LEFT JOIN gibbonPerson p ON f.gibbonPersonID = p.gibbonPersonID
        ORDER BY f.timestamp DESC");
    $query->execute();
    
    // Write data rows
    while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
        $userName = trim($row['title'] . ' ' . $row['firstName'] . ' ' . $row['surname']);
        
        fputcsv($output, [
            $row['gibbonChatBotFeedbackID'],
            $row['messageID'],
            $row['feedback'],
            $row['gibbonPersonID'],
            $userName,
            $row['comment'],
            $row['timestamp']
        ]);
    }
    
} catch (PDOException $e) {
    error_log('Export error: ' . $e->getMessage());
    die('Error exporting data: ' . $e->getMessage());
}

// Close output stream
fclose($output);
exit;