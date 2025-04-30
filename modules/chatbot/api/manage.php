<?php
// Include Gibbon's autoloader
require_once __DIR__ . '/../../../vendor/autoload.php';

// Include Gibbon core files
require_once __DIR__ . '/../../../gibbon.php';
require_once __DIR__ . '/../moduleFunctions.php';

use Gibbon\Contracts\Database\Connection;
use Gibbon\Domain\System\SettingGateway;

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!$session->has('gibbonPersonID')) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Please log in to use this feature.',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit;
}

// Get database connection
try {
    $connection = $container->get(Connection::class);
    $pdo = $connection->getConnection();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database connection error',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit;
}

// Handle different request methods
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

switch ($method) {
    case 'GET':
        if ($action === 'list') {
            // List all saved chats
            try {
                $stmt = $pdo->prepare('SELECT id, title, created_at FROM chatbot_saved_chats WHERE user_id = ? ORDER BY created_at DESC');
                $stmt->execute([$session->get('gibbonPersonID')]);
                $chats = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode([
                    'success' => true,
                    'chats' => $chats,
                    'timestamp' => date('Y-m-d H:i:s')
                ]);
            } catch (Exception $e) {
                // Log the detailed error
                error_log("ChatBot Manage API Error (List Chats): " . $e->getMessage());
                error_log("Stack Trace: " . $e->getTraceAsString());
                
                http_response_code(500);
                echo json_encode([ 
                    'success' => false,
                    // Provide a slightly more specific, but still safe, error message
                    'error' => 'Failed to retrieve chats due to a server error.',
                    'timestamp' => date('Y-m-d H:i:s')
                ]);
            }
        } elseif (isset($_GET['id'])) {
            // Get specific chat
            try {
                $stmt = $pdo->prepare('SELECT * FROM chatbot_saved_chats WHERE id = ? AND user_id = ?');
                $stmt->execute([$_GET['id'], $session->get('gibbonPersonID')]);
                $chat = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($chat) {
                    echo json_encode([
                        'success' => true,
                        'chat' => $chat,
                        'timestamp' => date('Y-m-d H:i:s')
                    ]);
                } else {
                    http_response_code(404);
                    echo json_encode([
                        'success' => false,
                        'error' => 'Chat not found',
                        'timestamp' => date('Y-m-d H:i:s')
                    ]);
                }
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'error' => 'Failed to retrieve chat',
                    'timestamp' => date('Y-m-d H:i:s')
                ]);
            }
        }
        break;

    case 'POST':
        // Save new chat
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['title']) || !isset($data['messages'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Missing required fields',
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            exit;
        }

        try {
            $stmt = $pdo->prepare('INSERT INTO chatbot_saved_chats (user_id, title, messages, created_at) VALUES (?, ?, ?, NOW())');
            $stmt->execute([
                $session->get('gibbonPersonID'),
                $data['title'],
                json_encode($data['messages'])
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Chat saved successfully',
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Failed to save chat',
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        }
        break;

    case 'PUT':
        // Rename chat
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['id']) || !isset($data['title'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Missing required fields',
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            exit;
        }

        try {
            $stmt = $pdo->prepare('UPDATE chatbot_saved_chats SET title = ? WHERE id = ? AND user_id = ?');
            $stmt->execute([
                $data['title'],
                $data['id'],
                $session->get('gibbonPersonID')
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Chat renamed successfully',
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Failed to rename chat',
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        }
        break;

    case 'DELETE':
        // Delete chat
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['id'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Missing chat ID',
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            exit;
        }

        try {
            $stmt = $pdo->prepare('DELETE FROM chatbot_saved_chats WHERE id = ? AND user_id = ?');
            $stmt->execute([
                $data['id'],
                $session->get('gibbonPersonID')
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Chat deleted successfully',
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Failed to delete chat',
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'error' => 'Method not allowed',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        break;
} 