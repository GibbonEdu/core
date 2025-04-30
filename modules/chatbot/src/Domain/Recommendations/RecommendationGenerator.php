<?php
namespace Gibbon\Module\ChatBot\Domain\Recommendations;

use Gibbon\Domain\Students\StudentGateway;
use Gibbon\Domain\Assessment\InternalAssessmentGateway;

/**
 * AI Recommendation Generator
 *
 * @version v23.0.00
 * @since   v23.0.00
 */
class RecommendationGenerator
{
    protected $studentGateway;
    protected $assessmentGateway;
    protected $connection;

    public function __construct($connection, StudentGateway $studentGateway, InternalAssessmentGateway $assessmentGateway)
    {
        $this->connection = $connection;
        $this->studentGateway = $studentGateway;
        $this->assessmentGateway = $assessmentGateway;
    }

    /**
     * Generate recommendations for a student based on their assessment data
     *
     * @param string $gibbonPersonID
     * @return array
     */
    public function generateRecommendations($gibbonPersonID)
    {
        // Get student's assessment data
        $criteria = $this->assessmentGateway->newQueryCriteria()
            ->sortBy(['date', 'DESC']);

        $assessments = $this->assessmentGateway->queryAssessmentsByStudent($criteria, $gibbonPersonID);
        
        $recommendations = [];
        foreach ($assessments as $assessment) {
            $recommendation = $this->generateSingleRecommendation($assessment);
            if ($recommendation) {
                $recommendations[] = [
                    'assessment' => $assessment,
                    'recommendation' => $recommendation
                ];
                
                // Store the recommendation
                $this->storeRecommendation($gibbonPersonID, $assessment, $recommendation);
            }
        }
        
        return $recommendations;
    }

    /**
     * Generate a recommendation for a single assessment
     *
     * @param array $assessment
     * @return string|null
     */
    protected function generateSingleRecommendation($assessment)
    {
        // Get the API key
        $apiKey = $this->getSecureAPIKey();
        if (empty($apiKey)) {
            return null;
        }
        
        // Prepare the prompt for the AI
        $prompt = $this->preparePrompt($assessment);
        
        try {
            // Call the DeepSeek API
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://api.deepseek.com/v1/chat/completions');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey
            ]);
            
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
            
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200) {
                $result = json_decode($response, true);
                return $result['choices'][0]['message']['content'];
            }
        } catch (\Exception $e) {
            return null;
        }
        
        return null;
    }

    /**
     * Prepare the AI prompt based on assessment data
     *
     * @param array $assessment
     * @return string
     */
    protected function preparePrompt($assessment)
    {
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
        
        return $prompt;
    }

    /**
     * Store a recommendation in the database
     *
     * @param string $gibbonPersonID
     * @param array $assessment
     * @param string $recommendation
     * @return bool
     */
    protected function storeRecommendation($gibbonPersonID, $assessment, $recommendation)
    {
        try {
            $data = [
                'gibbonPersonID' => $gibbonPersonID,
                'gibbonCourseClassID' => $assessment['gibbonCourseClassID'],
                'assessmentID' => $assessment['assessmentID'],
                'recommendation' => $recommendation
            ];
            
            $sql = "INSERT INTO gibbonChatBotRecommendations 
                    (gibbonPersonID, gibbonCourseClassID, assessmentID, recommendation) 
                    VALUES 
                    (:gibbonPersonID, :gibbonCourseClassID, :assessmentID, :recommendation)";
            
            $stmt = $this->connection->prepare($sql);
            return $stmt->execute($data);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get the secure API key
     *
     * @return string|null
     */
    protected function getSecureAPIKey()
    {
        return getSecureAPIKey($this->connection);
    }
} 