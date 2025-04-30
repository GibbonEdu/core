<?php
namespace Gibbon\Module\ChatBot\Domain;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryableGateway;

class ChatGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonChatBotSavedChats';
    private static $primaryKey = 'id';
    private static $searchableColumns = ['title'];
    
    /**
     * Get all chats for a user
     *
     * @param string $gibbonPersonID
     * @return array
     */
    public function selectChatsByPerson($gibbonPersonID)
    {
        $query = $this
            ->newSelect()
            ->from($this->getTableName())
            ->cols(['id', 'title', 'created_at'])
            ->where('gibbonPersonID = :gibbonPersonID')
            ->bindValue('gibbonPersonID', $gibbonPersonID)
            ->orderBy(['created_at DESC']);
        
        return $this->runSelect($query);
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
        $query = $this
            ->newSelect()
            ->from($this->getTableName())
            ->cols(['*'])
            ->where('id = :chatID')
            ->bindValue('chatID', $chatID)
            ->where('gibbonPersonID = :gibbonPersonID')
            ->bindValue('gibbonPersonID', $gibbonPersonID);
        
        return $this->runSelect($query)->fetch();
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

        $messages = is_string($data['messages']) ? $data['messages'] : json_encode($data['messages']);
        
        $query = $this
            ->newInsert()
            ->into($this->getTableName())
            ->cols([
                'gibbonPersonID' => $data['gibbonPersonID'],
                'title' => $data['title'],
                'messages' => $messages
            ]);
        
        return $this->runInsert($query);
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
        $query = $this
            ->newDelete()
            ->from($this->getTableName())
            ->where('id = :chatID')
            ->bindValue('chatID', $chatID)
            ->where('gibbonPersonID = :gibbonPersonID')
            ->bindValue('gibbonPersonID', $gibbonPersonID);
        
        return $this->runDelete($query);
    }

    /**
     * Save a chat interaction
     *
     * @param array $data
     * @return string|false
     */
    public function saveInteraction(array $data) 
    {
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
        $query = $this
            ->newInsert()
            ->into($this->getTableName())
            ->cols([
                'gibbonPersonID' => $data['gibbonPersonID'],
                'title' => $title,
                'messages' => json_encode($messages)
            ]);
        
        return $this->runInsert($query);
    }

    /**
     * Save training data
     *
     * @param array $data
     * @return string|false
     */
    public function saveTrainingData($data) 
    {
        // Validate required fields
        if (!isset($data['gibbonPersonID']) || !isset($data['question']) || !isset($data['answer'])) {
            throw new \Exception('Missing required fields for training data');
        }

        // Insert into training data table
        $query = $this
            ->newInsert()
            ->into('gibbonChatBotTrainingData')
            ->cols([
                'gibbonPersonID' => $data['gibbonPersonID'],
                'question' => $data['question'],
                'answer' => $data['answer'],
                'created_at' => $data['created_at'] ?? date('Y-m-d H:i:s')
            ]);
        
        return $this->runInsert($query);
    }
} 