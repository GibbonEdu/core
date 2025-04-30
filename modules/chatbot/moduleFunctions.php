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

use Gibbon\Domain\System\SettingGateway;
use Gibbon\Forms\Form;
use Gibbon\Services\Format as GibbonFormat;
use Gibbon\Domain\System\ModuleGateway;
use Gibbon\Domain\DataSet;
use Gibbon\Tables\DataTable;

// Include Gibbon core functions
require_once __DIR__ . '/../../functions.php';

/**
 * Get a setting value by scope
 * @param PDO $connection2 The database connection
 * @param string $scope The setting scope
 * @param string $name The setting name
 * @return string The setting value
 */
function getSettingByScope($connection2, $scope, $name) {
    try {
        $sql = "SELECT value FROM gibbonSetting WHERE scope=:scope AND name=:name";
        $result = $connection2->prepare($sql);
        $result->execute(['scope' => $scope, 'name' => $name]);
        if ($result->rowCount() == 1) {
            $row = $result->fetch();
            return $row['value'];
        }
    } catch (PDOException $e) {
        return '';
    }
    return '';
}

/**
 * Check if a file is a valid PDF
 * @param string $file The file path
 * @return bool Whether the file is a valid PDF
 */
function isValidPDF($file) {
    if (empty($file)) return false;
    
    // Check file extension
    $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    if ($extension !== 'pdf') return false;
    
    // Check file mime type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file);
    finfo_close($finfo);
    
    return $mimeType === 'application/pdf';
}

/**
 * Extract text from PDF file
 * @param string $pdfFile The PDF file path
 * @return string The extracted text
 */
function extractTextFromPDF($pdfFile) {
    if (!isValidPDF($pdfFile)) {
        throw new Exception('Invalid PDF file');
    }
    
    // Create temporary files
    $tempPdf = tempnam(sys_get_temp_dir(), 'pdf_');
    $tempTxt = tempnam(sys_get_temp_dir(), 'txt_');
    
    try {
        // Copy PDF to temp location
        if (!copy($pdfFile, $tempPdf)) {
            throw new Exception('Failed to copy PDF file');
        }
        
        // Convert PDF to text
        exec("pdftotext '{$tempPdf}' '{$tempTxt}' 2>&1", $output, $returnVar);
        
        if ($returnVar !== 0) {
            throw new Exception('Failed to convert PDF: ' . implode("\n", $output));
        }
        
        // Read converted text
        $text = file_get_contents($tempTxt);
        
        return $text;
        
    } finally {
        // Clean up temp files
        if (file_exists($tempPdf)) unlink($tempPdf);
        if (file_exists($tempTxt)) unlink($tempTxt);
    }
}

/**
 * Store the API key securely
 * @param PDO $connection2 The database connection
 * @param string $apiKey The API key to store
 * @return bool Whether the operation was successful
 */
function storeAPIKey($connection2, $apiKey) {
    try {
        $sql = "INSERT INTO gibbonSetting (scope, name, nameDisplay, description, value) 
                VALUES ('ChatBot', 'deepseek_api_key', 'DeepSeek API Key', 'API key for DeepSeek integration', :apiKey)
                ON DUPLICATE KEY UPDATE value = :apiKey";
        
        $result = $connection2->prepare($sql);
        return $result->execute(['apiKey' => $apiKey]);
    } catch (PDOException $e) {
        error_log('Error storing API key: ' . $e->getMessage());
        return false;
    }
}

/**
 * Get the API key securely
 * @param PDO $connection2 The database connection
 * @return string The API key
 */
function getSecureAPIKey($connection2) {
    try {
        $sql = "SELECT value FROM gibbonSetting WHERE scope='ChatBot' AND name='deepseek_api_key'";
        $result = $connection2->prepare($sql);
        $result->execute();
        if ($result->rowCount() == 1) {
            $row = $result->fetch();
            return $row['value'];
        }
    } catch (PDOException $e) {
        return '';
    }
    return '';
}

/**
 * Get student assessment data
 * @param PDO $connection2 The database connection
 * @param int $studentID The student ID
 * @return array The assessment data
 */
function getStudentAssessmentData($connection2, $studentID) {
    try {
        $sql = "SELECT 
                ia.name as assessment_name,
                iac.attainmentValue,
                iac.effortValue,
                iac.comment as teacher_feedback,
                c.name as course_name
                FROM gibbonInternalAssessmentEntry iac
                JOIN gibbonInternalAssessmentColumn ia ON (ia.gibbonInternalAssessmentColumnID=iac.gibbonInternalAssessmentColumnID)
                JOIN gibbonCourse c ON (c.gibbonCourseID=ia.gibbonCourseID)
                WHERE iac.gibbonPersonID=:studentID
                ORDER BY ia.timestampCreated DESC";
        
        $result = $connection2->prepare($sql);
        $result->execute(['studentID' => $studentID]);
        return $result->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log('Error getting student assessment data: ' . $e->getMessage());
        return [];
    }
}

/**
 * Make a secure call to the OpenAI API
 * 
 * @param string $apiKey
 * @param array $data
 * @return array|null
 */
function callOpenAIAPI($apiKey, $data) {
    try {
        // Initialize cURL session
        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        
        // Set cURL options
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey
            ],
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_SSL_VERIFYPEER => true,  // Verify SSL certificate
            CURLOPT_SSL_VERIFYHOST => 2,     // Verify hostname
            CURLOPT_CONNECTTIMEOUT => 10,    // Connection timeout
            CURLOPT_TIMEOUT => 30,           // Response timeout
            CURLOPT_PROTOCOLS => CURLPROTO_HTTPS, // Only allow HTTPS
        ]);
        
        // Execute request
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        // Check for errors
        if ($httpCode !== 200) {
            error_log('OpenAI API error: HTTP ' . $httpCode);
            error_log('Response: ' . $response);
            return null;
        }
        
        // Close cURL session
        curl_close($ch);
        
        // Parse and return response
        return json_decode($response, true);
        
    } catch (Exception $e) {
        error_log('Error calling OpenAI API: ' . $e->getMessage());
        return null;
    }
}

// Only declare if not already defined
if (!function_exists('isActionAccessible')) {
    /**
     * Check if a user has access to a specific action
     * @param mixed $guid The global unique identifier
     * @param PDO $connection2 The database connection
     * @param string $address The action address to check
     * @return bool Whether the user has access
     */
    function isActionAccessible($guid, $connection2, $address) {
        try {
            $data = array('gibbonRoleID' => $_SESSION[$guid]['gibbonRoleIDCurrent'], 'address' => $address);
            $sql = "SELECT DISTINCT gibbonAction.gibbonActionID 
                    FROM gibbonAction 
                    JOIN gibbonPermission ON (gibbonAction.gibbonActionID=gibbonPermission.gibbonActionID) 
                    WHERE (gibbonPermission.gibbonRoleID=:gibbonRoleID AND gibbonAction.URLList LIKE CONCAT('%', :address, '%'))";
            $result = $connection2->prepare($sql);
            $result->execute($data);
            return ($result->rowCount() > 0);
        } catch (PDOException $e) {
            return false;
        }
    }
}

/**
 * Helper functions for the ChatBot module
 */

/**
 * Check if a user has access to the ChatBot module
 *
 * @param string $guid
 * @param PDO $connection2
 * @return bool
 */
function chatbotCheckAccess($guid, $connection2) {
    return isActionAccessible($guid, $connection2, '/modules/ChatBot/chatbot.php');
}

/**
 * Get the DeepSeek API key from settings
 *
 * @param SettingGateway $settingGateway
 * @return string|null
 */
function chatbotGetApiKey($settingGateway) {
    return $settingGateway->getSettingByScope('ChatBot', 'deepseek_api_key');
}

/**
 * Format chat messages for display
 *
 * @param array $messages
 * @return string
 */
function chatbotFormatMessages($messages) {
    $output = '';
    foreach ($messages as $message) {
        $type = $message['type'];
        $content = $message['content'];
        $output .= "<div class='message {$type}-message'>";
        $output .= "<div class='message-content'>{$content}</div>";
        $output .= "</div>";
    }
    return $output;
}

/**
 * Validate chat message
 *
 * @param string $message
 * @return bool
 */
function chatbotValidateMessage($message) {
    return !empty(trim($message));
}

/**
 * Sanitize chat message
 *
 * @param string $message
 * @return string
 */
function chatbotSanitizeMessage($message) {
    return strip_tags(trim($message));
}

/**
 * Store AI recommendations for a student
 * @param PDO $connection2 The database connection
 * @param int $gibbonPersonID The student ID
 * @param int $gibbonCourseClassID The course class ID
 * @param int $assessmentID The assessment ID
 * @param string $recommendation The AI recommendation
 * @return bool Whether the operation was successful
 */
function storeRecommendation($connection2, $gibbonPersonID, $gibbonCourseClassID, $assessmentID, $recommendation) {
    try {
        $sql = "INSERT INTO gibbonChatBotRecommendations 
                (gibbonPersonID, gibbonCourseClassID, assessmentID, recommendation) 
                VALUES 
                (:gibbonPersonID, :gibbonCourseClassID, :assessmentID, :recommendation)";
        
        $result = $connection2->prepare($sql);
        return $result->execute([
            'gibbonPersonID' => $gibbonPersonID,
            'gibbonCourseClassID' => $gibbonCourseClassID,
            'assessmentID' => $assessmentID,
            'recommendation' => $recommendation
        ]);
    } catch (PDOException $e) {
        error_log('Error storing recommendation: ' . $e->getMessage());
        return false;
    }
}

/**
 * Get AI recommendations for a student
 * @param PDO $connection2 The database connection
 * @param int $gibbonPersonID The student ID
 * @return array The recommendations
 */
function getStudentRecommendations($connection2, $gibbonPersonID) {
    try {
        $sql = "SELECT r.*, 
                ia.name as assessment_name,
                c.name as course_name
                FROM gibbonChatBotRecommendations r
                JOIN gibbonInternalAssessment ia ON (ia.gibbonInternalAssessmentID = r.assessmentID)
                JOIN gibbonCourseClass cc ON (cc.gibbonCourseClassID = r.gibbonCourseClassID)
                JOIN gibbonCourse c ON (c.gibbonCourseID = cc.gibbonCourseID)
                WHERE r.gibbonPersonID = :gibbonPersonID
                ORDER BY r.timestamp DESC";
        
        $result = $connection2->prepare($sql);
        $result->execute(['gibbonPersonID' => $gibbonPersonID]);
        return $result->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Error getting student recommendations: ' . $e->getMessage());
        return [];
    }
}

/**
 * Add training data to the ChatBot
 * @param PDO $connection2 The database connection
 * @param string $question The question
 * @param string $answer The answer
 * @param int $createdBy The user ID who created the entry
 * @param string|null $category The category (optional)
 * @return bool Whether the operation was successful
 */
function addTrainingData($connection2, $question, $answer, $createdBy, $category = null) {
    try {
        $sql = "INSERT INTO gibbonChatBotTraining 
                (question, answer, category, created_by) 
                VALUES 
                (:question, :answer, :category, :created_by)";
        
        $result = $connection2->prepare($sql);
        return $result->execute([
            'question' => $question,
            'answer' => $answer,
            'category' => $category,
            'created_by' => $createdBy
        ]);
    } catch (PDOException $e) {
        error_log('Error adding training data: ' . $e->getMessage());
        return false;
    }
}

/**
 * Get training data for the ChatBot
 * @param PDO $connection2 The database connection
 * @param string $category Optional category filter
 * @return array The training data
 */
function getTrainingData($connection2, $category = null) {
    try {
        $sql = "SELECT * FROM gibbonChatBotTraining";
        $params = [];
        
        if ($category) {
            $sql .= " WHERE category = :category";
            $params['category'] = $category;
        }
        
        $sql .= " ORDER BY timestampCreated DESC";
        
        $result = $connection2->prepare($sql);
        $result->execute($params);
        return $result->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Error getting training data: ' . $e->getMessage());
        return [];
    }
}

/**
 * Delete training data
 * @param PDO $connection2 The database connection
 * @param int $id The training data ID
 * @return bool Whether the operation was successful
 */
function deleteTrainingData($connection2, $id) {
    try {
        $sql = "DELETE FROM gibbonChatBotTraining WHERE gibbonChatBotTrainingID = :id";
        $result = $connection2->prepare($sql);
        return $result->execute(['id' => $id]);
    } catch (PDOException $e) {
        error_log('Error deleting training data: ' . $e->getMessage());
        return false;
    }
}

/**
 * Generate AI recommendations based on assessment data
 * @param array $assessment The assessment data
 * @param string $apiKey The DeepSeek API key
 * @return string|null The AI recommendation
 */
function generateAssessmentRecommendation($assessment, $apiKey) {
    try {
        $prompt = "Based on the following assessment data:\n";
        $prompt .= "Subject: {$assessment['subject']}\n";
        $prompt .= "Grade: {$assessment['grade']}\n";
        $prompt .= "Teacher Feedback: {$assessment['feedback']}\n\n";
        $prompt .= "Please provide specific, actionable recommendations for improvement in this subject. Consider:\n";
        $prompt .= "1. Study strategies\n";
        $prompt .= "2. Practice exercises\n";
        $prompt .= "3. Additional resources\n";
        $prompt .= "4. Areas needing focus based on the assessment\n";
        $prompt .= "5. Time management suggestions";
        
        $data = [
            'model' => 'deepseek-chat',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are an educational AI assistant providing specific, actionable recommendations for academic improvement based on assessment data and teacher feedback.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'max_tokens' => 500
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.deepseek.com/v1/chat/completions');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ]);
        
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $result = json_decode($response, true);
            return $result['choices'][0]['message']['content'];
        }
    } catch (Exception $e) {
        error_log('Error generating assessment recommendation: ' . $e->getMessage());
    }
    
    return null;
}

/**
 * Get student performance metrics
 * @param int $gibbonPersonID
 * @return string HTML output of performance metrics
 */
function getStudentPerformanceMetrics($gibbonPersonID) {
    global $pdo;
    
    try {
        // Get assessment data
        $data = getStudentAssessmentData($gibbonPersonID);
        
        // Calculate metrics
        $metrics = [
            'average_attainment' => calculateAverageAttainment($data),
            'progress_indicators' => analyzeProgressTrends($data),
            'subject_breakdown' => getSubjectPerformance($data)
        ];
        
        // Generate HTML output
        $output = '<div class="metrics-grid">';
        foreach ($metrics as $key => $value) {
            $output .= generateMetricCard($key, $value);
        }
        $output .= '</div>';
        
        return $output;
    } catch (Exception $e) {
        return "Error generating performance metrics: " . $e->getMessage();
    }
}

/**
 * Generate AI recommendations based on student data
 * @param int $gibbonPersonID
 * @return string HTML output of recommendations
 */
function generateAIRecommendations($gibbonPersonID) {
    try {
        // Get comprehensive student data
        $assessmentData = getStudentAssessmentData($gibbonPersonID);
        $feedbackData = getTeacherFeedback($gibbonPersonID);
        $engagementData = getStudentEngagementData($gibbonPersonID);
        
        // Prepare data for AI analysis
        $analysisData = [
            'assessment_history' => $assessmentData,
            'teacher_feedback' => $feedbackData,
            'engagement_metrics' => $engagementData,
            'learning_patterns' => analyzeLearningPatterns($gibbonPersonID)
        ];
        
        // Get AI recommendations
        $recommendations = callDeepSeekAPI($analysisData);
        
        // Format recommendations
        return formatAIRecommendations($recommendations);
    } catch (Exception $e) {
        return "Error generating AI recommendations: " . $e->getMessage();
    }
}

/**
 * Process and store training data
 * @param array $data Training data array
 * @return bool Success status
 */
function processTrainingData($data) {
    try {
        // Validate training data
        validateTrainingData($data);
        
        // Enrich data with institutional context
        $enrichedData = enrichTrainingData($data);
        
        // Store in database
        storeTrainingData($enrichedData);
        
        // Update AI model
        updateAIModel($enrichedData);
        
        return true;
    } catch (Exception $e) {
        error_log("Training data processing error: " . $e->getMessage());
        return false;
    }
}

/**
 * Generate learning analytics
 * @param array $params Analytics parameters
 * @return array Analytics data
 */
function generateLearningAnalytics($params) {
    try {
        // Gather data
        $performanceData = getPerformanceData($params);
        $engagementData = getEngagementData($params);
        $patternData = getPatternData($params);
        
        // Process analytics
        $analytics = [
            'performance' => analyzePerformance($performanceData),
            'engagement' => analyzeEngagement($engagementData),
            'patterns' => analyzePatterns($patternData),
            'recommendations' => generateRecommendations([
                'performance' => $performanceData,
                'engagement' => $engagementData,
                'patterns' => $patternData
            ])
        ];
        
        return $analytics;
    } catch (Exception $e) {
        error_log("Analytics generation error: " . $e->getMessage());
        return null;
    }
}

/**
 * Format AI recommendations for display
 * @param array $recommendations
 * @return string Formatted HTML
 */
function formatAIRecommendations($recommendations) {
    $output = '<div class="recommendations-container">';
    
    // Format subject-specific recommendations
    if (isset($recommendations['subjects'])) {
        $output .= '<div class="subject-recommendations">';
        $output .= '<h4>Subject-Specific Recommendations</h4>';
        foreach ($recommendations['subjects'] as $subject => $data) {
            $output .= formatSubjectRecommendation($subject, $data);
        }
        $output .= '</div>';
    }
    
    // Format learning strategy recommendations
    if (isset($recommendations['strategies'])) {
        $output .= '<div class="strategy-recommendations">';
        $output .= '<h4>Learning Strategy Recommendations</h4>';
        foreach ($recommendations['strategies'] as $strategy) {
            $output .= formatStrategyRecommendation($strategy);
        }
        $output .= '</div>';
    }
    
    // Format improvement suggestions
    if (isset($recommendations['improvements'])) {
        $output .= '<div class="improvement-suggestions">';
        $output .= '<h4>Areas for Improvement</h4>';
        foreach ($recommendations['improvements'] as $area => $suggestions) {
            $output .= formatImprovementSuggestions($area, $suggestions);
        }
        $output .= '</div>';
    }
    
    $output .= '</div>';
    return $output;
}

