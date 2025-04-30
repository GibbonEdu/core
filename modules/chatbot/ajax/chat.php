<?php
require_once __DIR__ . '/../../../gibbon.php';
require_once __DIR__ . '/../moduleFunctions.php';

// Check for POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Method not allowed');
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$message = $data['message'] ?? '';
$gibbonPersonID = $data['gibbonPersonID'] ?? '';

if (empty($message) || empty($gibbonPersonID)) {
    http_response_code(400);
    die('Missing required parameters');
}

try {
    // Log the interaction
    $sql = "INSERT INTO chatbot_interactions (userID, message, timestamp) VALUES (:userID, :message, NOW())";
    $stmt = $connection2->prepare($sql);
    $stmt->execute([
        'userID' => $gibbonPersonID,
        'message' => $message
    ]);
    
    // Get response from training data
    $sql = "SELECT answer FROM gibbonChatBotTraining WHERE question LIKE :query AND approved = 1 ORDER BY RAND() LIMIT 1";
    $stmt = $connection2->prepare($sql);
    $stmt->execute(['query' => '%' . $message . '%']);
    
    $response = $stmt->fetch();
    $aiResponse = $response ? $response['answer'] : "I'm not sure how to respond to that. Could you please rephrase your question?";
    
    // Log the AI response
    $sql = "UPDATE chatbot_interactions SET response = :response WHERE userID = :userID ORDER BY timestamp DESC LIMIT 1";
    $stmt = $connection2->prepare($sql);
    $stmt->execute([
        'response' => $aiResponse,
        'userID' => $gibbonPersonID
    ]);
    
    // Return response
    header('Content-Type: application/json');
    echo json_encode(['response' => $aiResponse]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} 