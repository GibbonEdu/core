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

// Set PHP configuration
ini_set('max_execution_time', 300);
ini_set('memory_limit', '256M');
set_time_limit(300);

use Gibbon\Contracts\Database\Connection;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\System\ModuleGateway;
use Gibbon\Module\ChatBot\Domain\ChatGateway;
use Gibbon\Module\ChatBot\DeepSeekAPI;

// Include Gibbon core files
require_once '../../../gibbon.php';
require_once __DIR__ . '/../moduleFunctions.php';
require_once __DIR__ . '/../src/Domain/ChatGateway.php';
require_once __DIR__ . '/../src/DeepSeekAPI.php';

// Set headers and enable error logging
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
//ini_set('error_log', '/Applications/MAMP/logs/php_error.log');

try {
    // Check if user is logged in
    if (!$session->has('username')) {
        error_log("ChatBot: User not authenticated");
        http_response_code(401);
        echo json_encode([
            'error' => 'Please log in to use the AI Teaching Assistant.',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        exit;
    }

    // Get the DeepSeek API key from settings
    $settingGateway = $container->get(SettingGateway::class);
    $apiKey = $settingGateway->getSettingByScope('ChatBot', 'deepseek_api_key');
    
    if (empty($apiKey)) {
        error_log("ChatBot: DeepSeek API key not configured");
        http_response_code(500);
        echo json_encode([
            'error' => 'The AI Teaching Assistant is not properly configured. Please contact your administrator.',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        exit;
    }

    // Validate the incoming request
    $input = json_decode(file_get_contents('php://input'), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("ChatBot: Invalid JSON input - " . json_last_error_msg());
        http_response_code(400);
        echo json_encode([
            'error' => 'Invalid request format. Please try again.',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        exit;
    }

    if (empty($input['message'])) {
        error_log("ChatBot: Empty message received");
        http_response_code(400);
        echo json_encode([
            'error' => 'Please enter a message.',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        exit;
    }

    try {
        // Initialize the DeepSeek API
        $api = new DeepSeekAPI($apiKey);
        error_log("ChatBot: Processing request for user " . $session->get('username'));

        // Get the response
        $response = $api->getResponse($input['message']);
        
        // Validate response format
        if (empty($response['answer'])) {
            error_log("ChatBot: Empty response from API");
            throw new Exception("Invalid response format from AI service");
        }

        // Log success and response content
        $answer = $response['answer'];
        $logContent = is_array($answer) ? json_encode($answer) : $answer;
        error_log("ChatBot: Successfully processed request. Response: " . substr($logContent, 0, 100) . "...");

        // Return the response
        $jsonResponse = json_encode($response);
        if ($jsonResponse === false) {
            error_log("ChatBot: Failed to encode API response to JSON. Error: " . json_last_error_msg());
            throw new Exception("Failed to encode response from AI service.");
        }
        echo $jsonResponse;

    } catch (Exception $e) {
        error_log("ChatBot: Error processing request - " . $e->getMessage());
        
        // Handle specific error cases
        if (strpos($e->getMessage(), 'Authentication Fails') !== false) {
            http_response_code(401);
            echo json_encode([
                'error' => 'The AI Teaching Assistant is not properly configured. Please contact your administrator.',
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        } else if (strpos($e->getMessage(), 'timeout') !== false) {
            http_response_code(504);
            echo json_encode([
                'error' => 'The AI Teaching Assistant is taking too long to respond. Please try again.',
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        } else {
            error_log("ChatBot Error Stack Trace: " . $e->getTraceAsString());
            http_response_code(500);
            echo json_encode([
                'error' => 'An unexpected error occurred: ' . htmlspecialchars($e->getMessage()),
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        }
    }

} catch (PDOException $e) {
    // Database errors
    error_log('ChatBot Database Error: ' . $e->getMessage());
    error_log('ChatBot Database Error Stack Trace: ' . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'error' => 'A database error occurred. Please try again later.',
        'details' => $e->getMessage()
    ]);
    
} catch (Exception $e) {
    // General errors
    error_log('ChatBot Error: ' . $e->getMessage());
    error_log('ChatBot Error Stack Trace: ' . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}

class InternalAssessmentTrainer {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function getTeacherFeedback() {
        $sql = "SELECT 
            ia.gibbonInternalAssessmentID,
            ia.name as assessment_name,
            iac.comment as teacher_feedback,
            iac.attainmentValue,
            iac.effortValue,
            c.name as course_name,
            c.nameShort as course_code,
            student.preferredName as student_name,
            student.gibbonPersonID
        FROM gibbonInternalAssessmentColumn ia 
        JOIN gibbonInternalAssessmentEntry iac ON iac.gibbonInternalAssessmentColumnID=ia.gibbonInternalAssessmentColumnID
        JOIN gibbonCourse c ON c.gibbonCourseID=ia.gibbonCourseID
        JOIN gibbonPerson student ON student.gibbonPersonID=iac.gibbonPersonID
        WHERE iac.comment IS NOT NULL AND iac.comment != ''
        ORDER BY ia.gibbonInternalAssessmentID DESC";
        
        try {
            $result = $this->pdo->query($sql);
            return $result->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Error fetching Internal Assessment feedback: ' . $e->getMessage());
            return [];
        }
    }
    
    public function formatTrainingData($feedbackData) {
        $trainingData = [];
        foreach ($feedbackData as $entry) {
            $trainingData[] = [
                'context' => [
                    'course' => $entry['course_name'],
                    'assessment' => $entry['assessment_name'],
                    'attainment' => $entry['attainmentValue'],
                    'effort' => $entry['effortValue']
                ],
                'feedback' => $entry['teacher_feedback'],
                'metadata' => [
                    'course_code' => $entry['course_code'],
                    'student_id' => $entry['gibbonPersonID']
                ]
            ];
        }
        return $trainingData;
    }
    
    public function trainChatbot($trainingData) {
        // Format data for DeepSeek API
        $systemPrompt = "You are an educational AI assistant trained on teacher feedback. Your role is to:
1. Analyze patterns in student performance
2. Identify common areas for improvement
3. Suggest specific strategies for enhancement
4. Provide constructive, encouraging guidance
5. Reference real examples from teacher feedback";
        
        $messages = [
            [
                'role' => 'system',
                'content' => $systemPrompt
            ],
            [
                'role' => 'user',
                'content' => 'Train using this feedback data: ' . json_encode($trainingData)
            ]
        ];
        
        return $messages;
    }
} 