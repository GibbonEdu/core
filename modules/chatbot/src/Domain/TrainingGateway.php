<?php
namespace Gibbon\Module\ChatBot\Domain;

use Gibbon\Module\ChatBot\Domain\Traits\TableAwareInterface;
use Gibbon\Module\ChatBot\Domain\Traits\TableAwareTrait;
use PDO;

class TrainingGateway implements TableAwareInterface
{
    use TableAwareTrait;

    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
        $this->tableName = 'gibbonChatBotTraining';
    }

    public function getTrainingData($id)
    {
        $sql = "SELECT * FROM {$this->getTableName()} 
                WHERE gibbonChatBotTrainingID = :id";
        
        $result = $this->db->prepare($sql);
        $result->execute(['id' => $id]);
        return $result->fetch(\PDO::FETCH_ASSOC);
    }

    public function getAllTrainingData()
    {
        $sql = "SELECT * FROM {$this->getTableName()} 
                ORDER BY timestampCreated DESC";
        
        $result = $this->db->prepare($sql);
        $result->execute();
        return $result->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function insertTrainingData($data)
    {
        $sql = "INSERT INTO {$this->getTableName()} 
                (data, filename, timestampCreated) 
                VALUES (:data, :filename, NOW())";
        
        $result = $this->db->prepare($sql);
        return $result->execute([
            'data' => json_encode($data['data']),
            'filename' => $data['filename']
        ]);
    }

    public function updateTrainingData($id, $data)
    {
        $sql = "UPDATE {$this->getTableName()} 
                SET data = :data, 
                    filename = :filename,
                    timestampModified = NOW()
                WHERE gibbonChatBotTrainingID = :id";
        
        $result = $this->db->prepare($sql);
        return $result->execute([
            'id' => $id,
            'data' => json_encode($data['data']),
            'filename' => $data['filename']
        ]);
    }

    public function deleteTrainingData($id)
    {
        $sql = "DELETE FROM {$this->getTableName()} 
                WHERE gibbonChatBotTrainingID = :id";
        
        $result = $this->db->prepare($sql);
        return $result->execute(['id' => $id]);
    }
} 