<?php
include '../../gibbon.php';

$URL = $_SESSION[$guid]['absoluteURL'] . '/index.php?q=/modules/' . $_SESSION[$guid]['module'] . '/learning_management.php';

if (!isActionAccessible($guid, $connection2, '/modules/ChatBot/learning_management.php')) {
    // Access denied
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $title = $_POST['title'] ?? '';
    $type = $_POST['type'] ?? '';
    $description = $_POST['description'] ?? '';
    $gibbonSchoolYearID = $_POST['gibbonSchoolYearID'] ?? '';
    $url = $_POST['url'] ?? '';

    // Validate required fields
    if (empty($title) || empty($type) || empty($gibbonSchoolYearID)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    try {
        $data = [
            'title' => $title,
            'type' => $type,
            'description' => $description,
            'gibbonSchoolYearID' => $gibbonSchoolYearID,
            'dateAdded' => date('Y-m-d'),
            'gibbonPersonIDCreator' => $_SESSION[$guid]['gibbonPersonID']
        ];

        // Handle file upload if present
        if (!empty($_FILES['file']['tmp_name'])) {
            $file = $_FILES['file'];
            
            // Validate file type
            $fileType = mime_content_type($file['tmp_name']);
            $allowedTypes = [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'text/plain',
                'image/jpeg',
                'image/png',
                'video/mp4'
            ];

            if (!in_array($fileType, $allowedTypes)) {
                $URL .= '&return=error2';
                header("Location: {$URL}");
                exit;
            }

            // Move file to uploads directory
            $uploadDir = $_SESSION[$guid]['absolutePath'] . '/uploads/ChatBot/resources/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $fileName = time() . '_' . $file['name'];
            $filePath = $uploadDir . $fileName;

            if (move_uploaded_file($file['tmp_name'], $filePath)) {
                $data['filePath'] = 'uploads/ChatBot/resources/' . $fileName;
            } else {
                $URL .= '&return=error3';
                header("Location: {$URL}");
                exit;
            }
        } else if (!empty($url)) {
            // Store URL if no file was uploaded
            $data['filePath'] = $url;
        }

        // Insert into database
        $sql = "INSERT INTO gibbonChatBotCourseMaterials 
                (title, type, description, filePath, gibbonSchoolYearID, dateAdded, gibbonPersonIDCreator) 
                VALUES 
                (:title, :type, :description, :filePath, :gibbonSchoolYearID, :dateAdded, :gibbonPersonIDCreator)";
        
        $result = $connection2->prepare($sql);
        $result->execute($data);

        // Success
        $URL .= "&return=success0";
        header("Location: {$URL}");
        exit;
    } catch (Exception $e) {
        // Failed
        $URL .= "&return=error4";
        header("Location: {$URL}");
        exit;
    }
} 