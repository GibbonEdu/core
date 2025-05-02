<?php

declare(strict_types=1);

namespace CHHS\Modules\ChatBot\Domain\Traits;

trait TableAwareTrait
{
    protected $tables = [];

    public function getTable(string $name)
    {
        if (!isset($this->tables[$name])) {
            throw new \RuntimeException("Table {$name} is not available");
        }
        return $this->tables[$name];
    }

    public function setTable(string $name, $table)
    {
        $this->tables[$name] = $table;
        return $this;
    }
}
