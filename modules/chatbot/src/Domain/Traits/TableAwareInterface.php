<?php
namespace Gibbon\Module\ChatBot\Domain\Traits;

interface TableAwareInterface
{
    /**
     * Get the table name for the gateway
     *
     * @return string
     */
    public function getTableName();
} 