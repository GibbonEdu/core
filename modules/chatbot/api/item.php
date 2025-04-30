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

// Get item ID from URL
$id = isset($_GET['id']) ? intval($_GET['id']) : null;
if (!$id) {
    http_response_code(400);
    echo json_encode(['error' => 'Item ID is required']);
    exit;
}

try {
    $gateway = new ChatGateway($pdo);
    
    // Handle different HTTP methods
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            // Get single item
            $item = $gateway->getTrainingItem($id);
            
            if (!$item) {
                http_response_code(404);
                echo json_encode(['error' => 'Item not found']);
                exit;
            }
            
            echo json_encode($item);
            break;
            
        case 'PUT':
            // Update item
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['question']) || !isset($data['answer'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Question and answer are required']);
                exit;
            }
            
            $success = $gateway->updateTrainingItem($id, $data);
            
            if (!$success) {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to update item']);
                exit;
            }
            
            echo json_encode(['message' => 'Item updated successfully']);
            break;
            
        case 'DELETE':
            // Delete item
            $success = $gateway->deleteTrainingItem($id);
            
            if (!$success) {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to delete item']);
                exit;
            }
            
            echo json_encode(['message' => 'Item deleted successfully']);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
} 