<?php

use Gibbon\Contracts\Database\Connection;

require_once __DIR__ . '/../../../gibbon.php';
require_once __DIR__ . '/../moduleFunctions.php';

use Gibbon\Module\ChatBot\Domain\ChatGateway;

// Set JSON response headers
header('Content-Type: application/json');

try {
    // Check if user is logged in
    if (!isset($_SESSION[$guid]['gibbonPersonID'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Not authenticated']);
        exit;
    }

    // Get request data
    $rawData = file_get_contents('php://input');
    error_log("Received raw data: " . $rawData);
    
    $data = json_decode($rawData, true);
    error_log("Decoded data: " . print_r($data, true));

    if (!isset($data['training_data']) || !is_array($data['training_data'])) {
        throw new Exception('Training data is required');
    }

    // Get database connection from Gibbon's container
    $connection = $container->get(Connection::class);
    $connection2 = $connection->getConnection();
    error_log("Using Gibbon database connection");

    // Verify database connection
    try {
        $connection2->query("SELECT 1");
        error_log("Database connection verified");
    } catch (PDOException $e) {
        error_log("Database connection verification failed: " . $e->getMessage());
        throw new Exception('Database connection verification failed: ' . $e->getMessage());
    }

    // Drop table if exists and recreate
    try {
        $connection2->exec("DROP TABLE IF EXISTS gibbonChatBotTraining");
        error_log("Dropped existing table if present");
        
        $createTable = "CREATE TABLE gibbonChatBotTraining (
            id INT AUTO_INCREMENT PRIMARY KEY,
            question TEXT NOT NULL,
            answer TEXT NOT NULL,
            approved TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $connection2->exec($createTable);
        error_log("Created fresh gibbonChatBotTraining table");
    } catch (PDOException $e) {
        error_log("Table creation failed: " . $e->getMessage());
        throw new Exception('Failed to create table: ' . $e->getMessage());
    }

    // Insert training data
    $sql = "INSERT INTO gibbonChatBotTraining (question, answer) VALUES (:question, :answer)";
    $stmt = $connection2->prepare($sql);
    
    $successCount = 0;
    $totalCount = count($data['training_data']);
    $errors = [];

    foreach ($data['training_data'] as $index => $row) {
        if (empty($row['question']) || empty($row['answer'])) {
            $errors[] = "Row $index: Missing question or answer";
            error_log("Row $index: Missing question or answer");
            continue;
        }

        try {
            error_log("Processing row $index - Question: " . substr($row['question'], 0, 50));
            
            $params = [
                'question' => $row['question'],
                'answer' => $row['answer']
            ];
            error_log("Parameters for row $index: " . print_r($params, true));
            
            $result = $stmt->execute($params);
            
            if ($result) {
                $successCount++;
                error_log("Successfully inserted row $index");
            } else {
                $error = $stmt->errorInfo();
                error_log("Failed to insert row $index: " . print_r($error, true));
                $errors[] = "Row $index: Database insert failed - " . implode(", ", $error);
            }
        } catch (PDOException $e) {
            error_log("PDO Error inserting row $index: " . $e->getMessage());
            $errors[] = "Row $index: " . $e->getMessage();
        }
    }

    // Return success response with stats
    $response = [
        'success' => true,
        'message' => "Successfully uploaded $successCount out of $totalCount training items.",
        'stats' => [
            'total' => $totalCount,
            'success' => $successCount,
            'failed' => $totalCount - $successCount
        ],
        'errors' => $errors
    ];
    
    error_log("Training upload complete. Response: " . print_r($response, true));
    echo json_encode($response);

} catch (Exception $e) {
    error_log("Training upload error: " . $e->getMessage());
    error_log("Error trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

// Handle different actions
switch ($action) {
    case 'list':
        try {
            // Get pagination parameters
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            $offset = ($page - 1) * $limit;
            
            // Get filter parameters
            $search = isset($_GET['search']) ? $_GET['search'] : '';
            $approved = isset($_GET['approved']) ? $_GET['approved'] : 'all';
            $dateRange = isset($_GET['dateRange']) ? $_GET['dateRange'] : 'all';
            
            // Build query conditions
            $conditions = [];
            $params = [];
            
            if ($search) {
                $conditions[] = "(question LIKE ? OR answer LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            
            if ($approved !== 'all') {
                $conditions[] = "approved = ?";
                $params[] = $approved === '1' ? 1 : 0;
            }
            
            if ($dateRange !== 'all') {
                switch ($dateRange) {
                    case 'today':
                        $conditions[] = "DATE(created_at) = CURDATE()";
                        break;
                    case 'week':
                        $conditions[] = "created_at >= DATE_SUB(CURDATE(), INTERVAL 1 WEEK)";
                        break;
                    case 'month':
                        $conditions[] = "created_at >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)";
                        break;
                }
            }
            
            // Build the WHERE clause
            $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";
            
            // Get total count
            $countQuery = "SELECT COUNT(*) as total FROM chatbot_training $whereClause";
            $stmt = $pdo->prepare($countQuery);
            $stmt->execute($params);
            $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Get paginated data
            $query = "SELECT * FROM chatbot_training $whereClause ORDER BY created_at DESC LIMIT ? OFFSET ?";
            $stmt = $pdo->prepare($query);
            $params[] = $limit;
            $params[] = $offset;
            $stmt->execute($params);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Return success response with data
            echo json_encode([
                'success' => true,
                'data' => $data,
                'total' => $total,
                'page' => $page,
                'limit' => $limit
            ]);
        } catch (Exception $e) {
            error_log("Error in list action: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Failed to retrieve training data'
            ]);
        }
        break;

    // ... existing cases ...
} 