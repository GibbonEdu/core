<?php
namespace Gibbon\Module\ChatBot\Domain\Traits;

trait TableAwareTrait
{
    private $tableName;

    public function getTableName()
    {
        return $this->tableName;
    }
} 