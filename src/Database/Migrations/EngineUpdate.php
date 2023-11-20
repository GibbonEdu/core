<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright © 2010, Gibbon Foundation
Gibbon™, Gibbon Education Ltd. (Hong Kong)

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

namespace Gibbon\Database\Migrations;

use Gibbon\Contracts\Database\Connection;

/**
 * Migrate MyISAM tables to use the InnoDB engine.
 */
class EngineUpdate extends Migration
{
    public $tablesTotal = 0;
    public $tablesInnoDB = 0;

    protected $db;

    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    public function checkEngine()
    {
        $engines = $this->db->select("SHOW ENGINES")->fetchAll();

        if (empty($engines)) {
            return 'Unknown';
        }

        return array_reduce($engines, function ($currentEngine, $engine) {
            if ($engine['Support'] == 'DEFAULT') {
                $currentEngine = $engine['Engine'];
            }
            return $currentEngine;
        }, 'Unknown');
    }
    
    public function canMigrate() : bool
    {
        $tables = $this->db->select("SHOW TABLE STATUS")->fetchAll();
        $this->tablesTotal = count($tables);

        if (empty($this->tablesTotal)) {
            return false;
        }

        $this->tablesInnoDB = count(array_filter($tables, function ($table) {
            return $table['Engine'] == 'InnoDB' || $table['Engine'] === null;
        }));

        if ($this->tablesTotal - $this->tablesInnoDB > 0) {
            return true;
        }
        
        return false;
    }

    public function migrate()
    {
        $partialFail = false;
        $tables = $this->db->select("SHOW TABLE STATUS")->fetchAll();

        foreach ($tables as $table) {
            if ($table['Engine'] == 'InnoDB') continue;

            $partialFail &= !$this->db->statement("ALTER TABLE `".$table['Name']."` ENGINE=InnoDB;");
        }

        return !$partialFail;
    }
}
