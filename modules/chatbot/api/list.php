<?php
require_once '../../gibbon.php';
require_once '../ChatGateway.php';

use Gibbon\Module\ChatBot\ChatGateway;

header('Content-Type: application/json');

// Check user is logged in
if (!isset($_SESSION[$guid]['gibbonPersonID'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    $gateway = new ChatGateway($pdo);
    
    // Get filter parameters
    $filters = [
        'category' => $_GET['category'] ?? null,
        'date' => $_GET['date'] ?? null
    ];
    
    // Get training data with filters
    $items = $gateway->getTrainingItems($filters);
    
    echo json_encode(['items' => $items]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to retrieve training data']);
} 