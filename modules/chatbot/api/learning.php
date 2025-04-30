<?php
include '../../../gibbon.php';

// Check user has access
if (!isActionAccessible($guid, $connection2, '/modules/ChatBot/learning_management.php')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

// Get action
$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'list':
            // Get pagination parameters
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            $offset = ($page - 1) * $limit;
            
            // Get filter parameters
            $search = isset($_GET['search']) ? $_GET['search'] : '';
            $category = isset($_GET['category']) ? $_GET['category'] : '';
            $status = isset($_GET['status']) ? $_GET['status'] : '';
            
            // Build query conditions
            $conditions = [];
            $params = [];
            
            if ($search) {
                $conditions[] = "(question LIKE ? OR answer LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            
            if ($category) {
                $conditions[] = "category = ?";
                $params[] = $category;
            }
            
            if ($status !== '') {
                $conditions[] = "approved = ?";
                $params[] = $status === 'approved' ? 1 : 0;
            }
            
            // Build the WHERE clause
            $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";
            
            // Get total count
            $countQuery = "SELECT COUNT(*) as total FROM gibbonChatBotTraining $whereClause";
            $stmt = $connection2->prepare($countQuery);
            $stmt->execute($params);
            $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Get paginated data
            $query = "SELECT * FROM gibbonChatBotTraining $whereClause ORDER BY created_at DESC LIMIT ? OFFSET ?";
            $stmt = $connection2->prepare($query);
            $params[] = $limit;
            $params[] = $offset;
            $stmt->execute($params);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'items' => $items,
                'total' => $total,
                'page' => $page,
                'limit' => $limit
            ]);
            break;

        case 'stats':
            $query = "SELECT 
                COUNT(*) as totalQuestions,
                SUM(approved = 1) as approvedItems,
                MAX(created_at) as lastUpdate
                FROM gibbonChatBotTraining";
            $stmt = $connection2->prepare($query);
            $stmt->execute();
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'totalQuestions' => (int)$stats['totalQuestions'],
                'approvedItems' => (int)$stats['approvedItems'],
                'lastUpdate' => $stats['lastUpdate']
            ]);
            break;

        case 'save':
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (isset($data['id'])) {
                // Update existing item
                $stmt = $connection2->prepare("UPDATE gibbonChatBotTraining SET 
                    question = ?, 
                    answer = ?, 
                    category = ?, 
                    approved = ?
                    WHERE id = ?");
                $stmt->execute([
                    $data['question'],
                    $data['answer'],
                    $data['category'],
                    $data['approved'] ? 1 : 0,
                    $data['id']
                ]);
            } else {
                // Insert new item
                $stmt = $connection2->prepare("INSERT INTO gibbonChatBotTraining 
                    (question, answer, category, approved) 
                    VALUES (?, ?, ?, ?)");
                $stmt->execute([
                    $data['question'],
                    $data['answer'],
                    $data['category'],
                    $data['approved'] ? 1 : 0
                ]);
            }
            
            echo json_encode(['success' => true]);
            break;

        case 'delete':
            $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
            
            if ($id > 0) {
                $stmt = $connection2->prepare("DELETE FROM gibbonChatBotTraining WHERE id = ?");
                $stmt->execute([$id]);
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid ID']);
            }
            break;

        case 'get':
            $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
            
            if ($id > 0) {
                $stmt = $connection2->prepare("SELECT * FROM gibbonChatBotTraining WHERE id = ?");
                $stmt->execute([$id]);
                $item = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($item) {
                    echo json_encode(['success' => true, 'item' => $item]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Item not found']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid ID']);
            }
            break;

        case 'upload':
            if (!isset($_FILES['file'])) {
                echo json_encode(['success' => false, 'message' => 'No file uploaded']);
                break;
            }

            $file = $_FILES['file'];
            if ($file['error'] !== UPLOAD_ERR_OK) {
                echo json_encode(['success' => false, 'message' => 'File upload failed']);
                break;
            }

            $handle = fopen($file['tmp_name'], 'r');
            if ($handle === false) {
                echo json_encode(['success' => false, 'message' => 'Could not read file']);
                break;
            }

            $connection2->beginTransaction();
            try {
                $stmt = $connection2->prepare("INSERT INTO gibbonChatBotTraining 
                    (question, answer, category, approved) 
                    VALUES (?, ?, ?, ?)");

                // Skip header row
                fgetcsv($handle);

                while (($data = fgetcsv($handle)) !== false) {
                    $stmt->execute([
                        $data[0], // question
                        $data[1], // answer
                        $data[2], // category
                        isset($data[3]) ? (int)$data[3] : 1 // approved
                    ]);
                }

                $connection2->commit();
                echo json_encode(['success' => true]);
            } catch (Exception $e) {
                $connection2->rollBack();
                echo json_encode(['success' => false, 'message' => 'Error importing data']);
            }

            fclose($handle);
            break;

        case 'export':
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="learning_data.csv"');

            $output = fopen('php://output', 'w');
            fputcsv($output, ['Question', 'Answer', 'Category', 'Approved']);

            $stmt = $connection2->query("SELECT question, answer, category, approved FROM gibbonChatBotTraining ORDER BY created_at DESC");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                fputcsv($output, $row);
            }

            fclose($output);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
?> 