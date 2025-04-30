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

use Gibbon\Services\Format;

// Module includes
require_once '../../config.php';
require_once '../../functions.php';
require_once '../../gibbon.php';

// Setup global variables
global $container, $gibbon;

$session = $container->get('session');

// Check access
if (!isActionAccessible($guid, $connection2, '/modules/ChatBot/db_check_feedback.php')) {
    http_response_code(403);
    die(json_encode(['error' => 'Access denied']));
}

// Get feedback ID
$id = $_GET['id'] ?? null;
if (!$id) {
    http_response_code(400);
    die(json_encode(['error' => 'No feedback ID provided']));
}

// Initialize database connection
$connection2 = $gibbon->db;

try {
    // Get feedback details
    $data = $connection2->selectOne("
        SELECT timestamp, feedback_type, user_message, ai_response, feedback_text 
        FROM gibbonChatBotFeedback 
        WHERE id = :id
    ", ['id' => $id]);

    if (!$data) {
        http_response_code(404);
        die(json_encode(['error' => 'Feedback not found']));
    }

    // Format timestamp
    $data['timestamp'] = Format::dateTime($data['timestamp']);

    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($data);
} catch (PDOException $e) {
    http_response_code(500);
    die(json_encode(['error' => 'Database error']));
} 