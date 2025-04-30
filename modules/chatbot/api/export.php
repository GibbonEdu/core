<?php
require_once '../../gibbon.php';
require_once '../ChatGateway.php';

use Gibbon\Module\ChatBot\ChatGateway;

// Check user is logged in
if (!isset($_SESSION[$guid]['gibbonPersonID'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    $gateway = new ChatGateway($pdo);
    
    // Get all training data
    $items = $gateway->getTrainingItems();
    
    if (empty($items)) {
        throw new Exception('No training data available for export');
    }
    
    // Set headers for CSV download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="training_data_' . date('Y-m-d') . '.csv"');
    
    // Create output stream
    $output = fopen('php://output', 'w');
    
    // Add headers
    fputcsv($output, array_keys($items[0]));
    
    // Add data rows
    foreach ($items as $item) {
        fputcsv($output, $item);
    }
    
    fclose($output);
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
} 