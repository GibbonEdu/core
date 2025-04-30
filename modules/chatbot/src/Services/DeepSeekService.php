<?php
namespace Gibbon\Module\ChatBot\Services;

use Gibbon\Core\Container;
use Gibbon\Domain\System\SettingGateway;
use PDO;

class DeepSeekService
{
    private $container;
    private $apiKey;
    private $modelName;
    private $maxTokens;
    private $baseUrl = 'https://api.deepseek.com/v1';

    public function __construct(\Gibbon\Core\Container $container)
    {
        $this->container = $container;
        $settingGateway = $container->get(\Gibbon\Domain\System\SettingGateway::class);
        $this->apiKey = $settingGateway->getSettingByScope('ChatBot', 'deepseek_api_key');
        $this->modelName = $settingGateway->getSettingByScope('ChatBot', 'model_name') ?? 'deepseek-chat';
        $this->maxTokens = $settingGateway->getSettingByScope('ChatBot', 'max_tokens') ?? 4000;
    }

    public function chat($messages)
    {
        $url = $this->baseUrl . '/chat/completions';
        
        $data = [
            'model' => $this->modelName,
            'messages' => $messages,
            'max_tokens' => $this->maxTokens,
            'temperature' => 0.7,
            'top_p' => 0.95
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new \Exception('API request failed with status code: ' . $httpCode);
        }

        return json_decode($response, true);
    }

    public function analyzeGrades($studentData)
    {
        $prompt = "Analyze the following student data and provide guidance for improvement:\n\n";
        $prompt .= json_encode($studentData, JSON_PRETTY_PRINT);
        
        $messages = [
            [
                'role' => 'system',
                'content' => 'You are an educational AI assistant specialized in analyzing student performance and providing constructive feedback.'
            ],
            [
                'role' => 'user',
                'content' => $prompt
            ]
        ];

        return $this->chat($messages);
    }

    public function trainFromData($trainingData)
    {
        $formattedData = $this->formatTrainingData($trainingData);
        
        $prompt = "Learn from the following training data:\n\n";
        $prompt .= json_encode($formattedData, JSON_PRETTY_PRINT);
        
        $messages = [
            [
                'role' => 'system',
                'content' => 'You are an AI assistant learning from provided training data.'
            ],
            [
                'role' => 'user',
                'content' => $prompt
            ]
        ];

        return $this->chat($messages);
    }

    private function formatTrainingData($data)
    {
        if (is_array($data)) {
            return $data;
        }
        
        if (strpos($data, '.csv') !== false) {
            return $this->formatCSVData($data);
        } elseif (strpos($data, '.json') !== false) {
            return json_decode($data, true);
        }
        
        return $data;
    }

    private function formatCSVData($csvData)
    {
        $rows = str_getcsv($csvData, "\n");
        $headers = str_getcsv(array_shift($rows));
        $formatted = [];
        
        foreach ($rows as $row) {
            $values = str_getcsv($row);
            $formatted[] = array_combine($headers, $values);
        }
        
        return $formatted;
    }
} 