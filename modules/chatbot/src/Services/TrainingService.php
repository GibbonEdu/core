<?php
namespace Gibbon\Module\ChatBot\Services;

use Gibbon\Domain\System\SettingGateway;

class TrainingService
{
    private $container;
    private $deepSeekService;
    private $db;

    public function __construct($container)
    {
        $this->container = $container;
        $this->db = $container->get('db');
        $this->deepSeekService = new DeepSeekService($container);
    }

    public function processTrainingData($data, $filename)
    {
        try {
            // Process data through DeepSeek
            $response = $this->deepSeekService->trainFromData($data);
            
            // Add institutional metadata
            $enrichedData = array_map(function($item) {
                return array_merge($item, [
                    'institution_context' => [
                        'curriculum_alignment' => true,
                        'grade_level' => $item['grade_level'] ?? 'all',
                        'subject_area' => $item['category'] ?? 'general',
                        'learning_outcomes' => $item['learning_outcomes'] ?? [],
                        'assessment_criteria' => $item['assessment_criteria'] ?? []
                    ],
                    'metadata' => [
                        'source' => 'institutional_training',
                        'verified' => true,
                        'last_updated' => date('Y-m-d H:i:s')
                    ]
                ]);
            }, $data);
            
            // Store in database
            $sql = "INSERT INTO gibbonChatBotTraining 
                    (data, filename, metadata, timestampCreated) 
                    VALUES 
                    (:data, :filename, :metadata, NOW())";
            
            $result = $this->db->prepare($sql);
            $success = $result->execute([
                'data' => json_encode($enrichedData),
                'filename' => $filename,
                'metadata' => json_encode([
                    'institution_specific' => true,
                    'training_version' => '2.0',
                    'content_type' => 'educational'
                ])
            ]);
            
            if (!$success) {
                throw new \Exception('Failed to store training data');
            }
            
            return true;
            
        } catch (\Exception $e) {
            error_log('Error processing training data: ' . $e->getMessage());
            return false;
        }
    }

    public function getTrainingHistory()
    {
        try {
            $sql = "SELECT * FROM gibbonChatBotTraining 
                    ORDER BY timestampCreated DESC";
            
            $result = $this->db->prepare($sql);
            $result->execute();
            return $result->fetchAll(\PDO::FETCH_ASSOC);
            
        } catch (\Exception $e) {
            error_log('Error getting training history: ' . $e->getMessage());
            return [];
        }
    }

    public function deleteTrainingData($id)
    {
        try {
            $sql = "DELETE FROM gibbonChatBotTraining 
                    WHERE gibbonChatBotTrainingID = :id";
            
            $result = $this->db->prepare($sql);
            return $result->execute(['id' => $id]);
            
        } catch (\Exception $e) {
            error_log('Error deleting training data: ' . $e->getMessage());
            return false;
        }
    }
} 