<?php
require_once '../../gibbon.php';
require_once '../moduleFunctions.php';

use Gibbon\Module\ChatBot\Domain\ChatGateway;

// Set JSON response headers
header('Content-Type: application/json');

// Check user is logged in
$isLoggedIn = isset($_SESSION[$guid]['gibbonPersonID']);
if (!$isLoggedIn) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

// Get user ID from session
$userID = $_SESSION[$guid]['gibbonPersonID'];

// Initialize ChatGateway
global $pdo;
if (!$pdo) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

$chatGateway = new ChatGateway($pdo);

// Handle different HTTP methods
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Get chat ID from query string if provided
        $chatID = $_GET['id'] ?? null;
        
        if ($chatID) {
            // Get specific chat
            $chat = $chatGateway->getChat($chatID, $userID);
            
            if ($chat) {
                echo json_encode(['success' => true, 'chat' => $chat]);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Chat not found']);
            }
        } else {
            // Get all chats for user
            try {
                $chats = $chatGateway->selectChatsByPerson($userID);
                echo json_encode(['success' => true, 'chats' => $chats]);
            } catch (\Exception $e) {
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => 'Failed to fetch chats']);
            }
        }
        break;

    case 'POST':
        // Get request body
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validate required fields
        if (!isset($data['title']) || !isset($data['messages'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Missing required fields']);
            exit;
        }
        
        // Insert new chat
        try {
            $chatID = $chatGateway->insert([
                'gibbonPersonID' => $userID,
                'title' => $data['title'],
                'messages' => $data['messages']
            ]);
            
            if ($chatID) {
                echo json_encode(['success' => true, 'id' => $chatID]);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => 'Failed to save chat']);
            }
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Failed to save chat: ' . $e->getMessage()]);
        }
        break;

    case 'DELETE':
        // Get request body
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validate chat ID
        if (!isset($data['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Chat ID is required']);
            exit;
        }
        
        // Delete chat
        try {
            $success = $chatGateway->delete($data['id'], $userID);
            
            if ($success) {
                echo json_encode(['success' => true]);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Chat not found or unauthorized']);
            }
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Failed to delete chat: ' . $e->getMessage()]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        break;
} 