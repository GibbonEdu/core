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
    
    // Get total number of training items
    $totalItems = $gateway->getTotalTrainingItems();
    
    // Get last upload date
    $lastUpload = $gateway->getLastTrainingUpload();
    
    echo json_encode([
        'totalItems' => $totalItems,
        'lastUploadDate' => $lastUpload ? date('Y-m-d H:i:s', strtotime($lastUpload)) : null
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to retrieve training stats']);
} 