<?php
require_once __DIR__ . '/../../gibbon.php';
require_once __DIR__ . '/moduleFunctions.php';

// Check access
if (!isActionAccessible($guid, $connection2, '/modules/ChatBot/training.php', 'Manage Training')) {
    $URL = $session->get('absoluteURL').'/index.php?q=/modules/ChatBot/training.php&return=error0';
    header("Location: {$URL}");
    exit;
}

// Handle file upload
if (empty($_FILES['trainingFile']['tmp_name'])) {
    $URL = $session->get('absoluteURL').'/index.php?q=/modules/ChatBot/training.php&return=error1';
    header("Location: {$URL}");
    exit;
}

try {
    $file = $_FILES['trainingFile'];
    $fileName = $file['name'];
    $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    
    // Validate file type
    if (!in_array($fileType, ['txt', 'csv', 'json', 'pdf'])) {
        throw new Exception('Invalid file type. Allowed types: txt, csv, json, pdf');
    }
    
    // Process file based on type
    switch($fileType) {
        case 'csv':
            if (($handle = fopen($file['tmp_name'], "r")) !== FALSE) {
                while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                    if (count($data) >= 2) {
                        $sql = "INSERT INTO gibbonChatBotTraining (question, answer, approved, created_at) VALUES (:question, :answer, :approved, NOW())";
                        $stmt = $connection2->prepare($sql);
                        $stmt->execute([
                            'question' => $data[0],
                            'answer' => $data[1],
                            'approved' => 1
                        ]);
                    }
                }
                fclose($handle);
            }
            break;
            
        case 'json':
            $jsonData = json_decode(file_get_contents($file['tmp_name']), true);
            if (json_last_error() === JSON_ERROR_NONE) {
                foreach ($jsonData as $item) {
                    if (isset($item['question']) && isset($item['answer'])) {
                        $sql = "INSERT INTO gibbonChatBotTraining (question, answer, approved, created_at) VALUES (:question, :answer, :approved, NOW())";
                        $stmt = $connection2->prepare($sql);
                        $stmt->execute([
                            'question' => $item['question'],
                            'answer' => $item['answer'],
                            'approved' => 1
                        ]);
                    }
                }
            }
            break;
            
        case 'txt':
            $content = file_get_contents($file['tmp_name']);
            $lines = explode("\n", $content);
            for ($i = 0; $i < count($lines) - 1; $i += 2) {
                $question = trim($lines[$i]);
                $answer = trim($lines[$i + 1]);
                if (!empty($question) && !empty($answer)) {
                    $sql = "INSERT INTO gibbonChatBotTraining (question, answer, approved, created_at) VALUES (:question, :answer, :approved, NOW())";
                    $stmt = $connection2->prepare($sql);
                    $stmt->execute([
                        'question' => $question,
                        'answer' => $answer,
                        'approved' => 1
                    ]);
                }
            }
            break;
            
        default:
            throw new Exception('File type processing not implemented yet');
    }
    
    $URL = $session->get('absoluteURL').'/index.php?q=/modules/ChatBot/training.php&return=success0';
    header("Location: {$URL}");
    exit;
    
} catch (Exception $e) {
    $URL = $session->get('absoluteURL').'/index.php?q=/modules/ChatBot/training.php&return=error2';
    header("Location: {$URL}");
    exit;
} 