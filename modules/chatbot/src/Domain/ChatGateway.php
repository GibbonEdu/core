<?php
namespace Gibbon\Module\ChatBot\Domain;

use PDO;
use Exception;

class ChatGateway
{
    private $pdo;
    private $tableName = 'chatbot_saved_chats';

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Get all chats for a user
     *
     * @param string $gibbonPersonID
     * @return array
     */
    public function selectChatsByPerson($gibbonPersonID)
    {
        $sql = "SELECT id, title, created_at FROM {$this->tableName} WHERE gibbonPersonID = :gibbonPersonID ORDER BY created_at DESC";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['gibbonPersonID' => $gibbonPersonID]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            throw new \Exception('Failed to fetch chats: ' . $e->getMessage());
        }
    }

    /**
     * Get a specific chat by ID and user
     *
     * @param string $chatID
     * @param string $gibbonPersonID
     * @return array|false
     */
    public function getChat($chatID, $gibbonPersonID)
    {
        $sql = "SELECT * FROM {$this->tableName} WHERE id = :chatID AND gibbonPersonID = :gibbonPersonID";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'chatID' => $chatID,
                'gibbonPersonID' => $gibbonPersonID
            ]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            throw new \Exception('Failed to fetch chat: ' . $e->getMessage());
        }
    }

    /**
     * Insert a new chat
     *
     * @param array $data
     * @return string|false
     */
    public function insert(array $data)
    {
        // Validate required fields
        if (!isset($data['gibbonPersonID']) || !isset($data['title']) || !isset($data['messages'])) {
            throw new \Exception('Missing required fields');
        }

        // Prepare SQL
        $sql = "INSERT INTO {$this->tableName} (gibbonPersonID, title, messages) VALUES (:gibbonPersonID, :title, :messages)";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'gibbonPersonID' => $data['gibbonPersonID'],
                'title' => $data['title'],
                'messages' => is_string($data['messages']) ? $data['messages'] : json_encode($data['messages'])
            ]);
            
            return $this->pdo->lastInsertId();
        } catch (\PDOException $e) {
            throw new \Exception('Failed to save chat: ' . $e->getMessage());
        }
    }

    /**
     * Delete a chat
     *
     * @param string $chatID
     * @param string $gibbonPersonID
     * @return bool
     */
    public function delete($chatID, $gibbonPersonID)
    {
        $sql = "DELETE FROM {$this->tableName} WHERE id = :chatID AND gibbonPersonID = :gibbonPersonID";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'chatID' => $chatID,
                'gibbonPersonID' => $gibbonPersonID
            ]);
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            throw new \Exception('Failed to delete chat: ' . $e->getMessage());
        }
    }

    public function saveInteraction(array $data) {
        // Validate required fields
        if (!isset($data['gibbonPersonID']) || !isset($data['message']) || !isset($data['response'])) {
            throw new \Exception('Missing required fields');
        }

        // Create messages array
        $messages = [
            ['type' => 'user', 'content' => $data['message']],
            ['type' => 'bot', 'content' => $data['response']]
        ];

        // Create title from first message
        $title = substr($data['message'], 0, 50) . '...';

        // Insert into database
        $sql = "INSERT INTO {$this->tableName} (gibbonPersonID, title, messages) VALUES (:gibbonPersonID, :title, :messages)";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'gibbonPersonID' => $data['gibbonPersonID'],
                'title' => $title,
                'messages' => json_encode($messages)
            ]);
            
            return $this->pdo->lastInsertId();
        } catch (\PDOException $e) {
            throw new \Exception('Failed to save chat: ' . $e->getMessage());
        }
    }

    public function saveTrainingData($data) {
        // Validate required fields
        if (!isset($data['gibbonPersonID']) || !isset($data['question']) || !isset($data['answer'])) {
            throw new Exception('Missing required fields for training data');
        }

        // Insert into training data table
        $sql = "INSERT INTO chatbot_training_data 
                (gibbonPersonID, question, answer, created_at) 
                VALUES 
                (:gibbonPersonID, :question, :answer, :created_at)";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'gibbonPersonID' => $data['gibbonPersonID'],
                'question' => $data['question'],
                'answer' => $data['answer'],
                'created_at' => $data['created_at']
            ]);
            
            return $this->pdo->lastInsertId();
        } catch (\PDOException $e) {
            throw new \Exception('Failed to save training data: ' . $e->getMessage());
        }
    }
} 