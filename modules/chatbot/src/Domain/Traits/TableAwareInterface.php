<?php

declare(strict_types=1);

namespace CHHS\Modules\ChatBot\Domain\Traits;

interface TableAwareInterface
{
    public function getTable(string $name);
    public function setTable(string $name, $table);
}
