<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

// Include Gibbon core
require_once __DIR__ . '/../../../gibbon.php';
require_once __DIR__ . '/../moduleFunctions.php';

use Gibbon\Domain\System\SettingGateway;

// Check access
if (!isActionAccessible($guid, $connection2, '/modules/ChatBot/training.php')) {
    die(json_encode(['success' => false, 'error' => 'Access denied']));
}

// Handle file upload
try {
    if (!isset($_FILES['trainingFile']) || $_FILES['trainingFile']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('No file uploaded or upload error occurred');
    }

    $file = $_FILES['trainingFile'];
    $fileName = $file['name'];
    $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    // Validate file type
    $allowedTypes = ['csv', 'txt', 'json'];
    if (!in_array($fileType, $allowedTypes)) {
        throw new Exception('Invalid file type. Allowed types: ' . implode(', ', $allowedTypes));
    }

    // Read file content
    $content = file_get_contents($file['tmp_name']);
    
    if ($fileType === 'csv') {
        // Parse CSV
        $handle = fopen($file['tmp_name'], 'r');
        $header = fgetcsv($handle);
        $data = [];
        
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) >= 3) {
                $data[] = [
                    'question' => $row[0],
                    'answer' => $row[1],
                    'category' => $row[2]
                ];
            }
        }
        fclose($handle);
    } elseif ($fileType === 'json') {
        // Parse JSON
        $data = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON format');
        }
    } else {
        // Parse TXT (assuming tab or comma separated)
        $lines = explode("\n", $content);
        $data = [];
        foreach ($lines as $line) {
            $parts = str_getcsv($line);
            if (count($parts) >= 3) {
                $data[] = [
                    'question' => $parts[0],
                    'answer' => $parts[1],
                    'category' => $parts[2]
                ];
            }
        }
    }

    // Insert data into database
    $pdo = $connection2;
    $pdo->beginTransaction();

    try {
        $sql = "INSERT INTO gibbonChatBotTraining (question, answer, category) VALUES (:question, :answer, :category)";
        $stmt = $pdo->prepare($sql);

        foreach ($data as $row) {
            if (!empty($row['question']) && !empty($row['answer'])) {
                $stmt->execute([
                    'question' => $row['question'],
                    'answer' => $row['answer'],
                    'category' => $row['category'] ?? null
                ]);
            }
        }

        $pdo->commit();
        
        // Redirect back with success message
        $URL = $session->get('absoluteURL') . '/index.php?q=/modules/ChatBot/training.php&return=success0';
        header("Location: {$URL}");
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        throw new Exception('Failed to insert training data: ' . $e->getMessage());
    }

} catch (Exception $e) {
    // Redirect back with error message
    $URL = $session->get('absoluteURL') . '/index.php?q=/modules/ChatBot/training.php&return=error0';
    header("Location: {$URL}");
    exit;
} 