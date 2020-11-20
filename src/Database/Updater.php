<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

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

namespace Gibbon\Database;

use PDOException;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Contracts\Database\Connection;

/**
 * Database Updater
 */
class Updater 
{
    public $versionDB;
    public $versionCode;
    
    public $cuttingEdgeCode;
    public $cuttingEdgeCodeLine;
    public $cuttingEdgeVersion;
    public $cuttingEdgeMaxLine;

    protected $db;
    protected $settingGateway;
    protected $absolutePath;

    protected $sql = [];
    protected $errors = [];

    public function __construct(Connection $db, SettingGateway $settingGateway)
    {
        $this->db = $db;
        $this->settingGateway = $settingGateway;
        $this->absolutePath = $this->settingGateway->getSettingByScope('System', 'absolutePath');

        include $this->absolutePath.'/version.php';

        $this->versionCode = $version;
        $this->versionDB = $this->settingGateway->getSettingByScope('System', 'version');
        $this->cuttingEdgeCode = $this->settingGateway->getSettingByScope('System', 'cuttingEdgeCode');
        $this->cuttingEdgeCodeLine = $this->settingGateway->getSettingByScope('System', 'cuttingEdgeCodeLine');
        $this->cuttingEdgeCodeLine = !empty($this->cuttingEdgeCodeLine) ? $this->cuttingEdgeCodeLine : 0;
    }

    public function isCuttingEdge()
    {
        return $this->cuttingEdgeCode == 'Y';
    }

    public function isVersionValid()
    {
        $this->sql = $this->loadChangeDB();

        if (empty($this->sql) || empty($this->versionCode) || empty($this->versionDB)) {
            return false;
        }

        if (version_compare($this->versionCode, $this->versionDB) === -1) {
            return false;
        }

        return true;
    }

    public function isUpdateRequired()
    {
        if (!$this->isVersionValid()) {
            return false;
        }

        if (!$this->isCuttingEdge()) {
            return version_compare($this->versionCode, $this->versionDB);
        }

        if (version_compare($this->cuttingEdgeVersion, $this->versionDB, '>')) {
            return true;
        } else if (version_compare($this->cuttingEdgeMaxLine, $this->cuttingEdgeCodeLine, '>')) {
            return true;
        }

        return false;
    }

    public function update() : array
    {
        if (!$this->isUpdateRequired()) {
            return [];
        }

        if (!$this->isCuttingEdge()) {
            // Regular release: run all lines for all versions
            $this->fullVersionUpdate(false);
        }

        if (version_compare($this->cuttingEdgeVersion, $this->versionDB, '>')) {
            // Cutting edge: at least one full version needs to be done first
            $this->partialVersionUpdate();
        } else {
            // Cutting edge: less than one whole version, get up to speed in max version
            $this->fullVersionUpdate(true);
        }

        if (!$this->errors) {
            // Update DB version
            $this->settingGateway->updateSettingByScope('System', 'version', $this->versionCode);
            $this->settingGateway->updateSettingByScope('System', 'cuttingEdgeCodeLine', $this->isCuttingEdge()? $this->cuttingEdgeMaxLine : 0);
        }

        return $this->errors;
    }

    protected function fullVersionUpdate($cuttingEdge = false)
    {
        foreach ($this->sql as $version) {
            $tokenCount = 0;

            if (version_compare($version[0], $this->versionDB, $cuttingEdge ? '>=' : '>') && version_compare($version[0], $this->versionCode, '<=')) {
                $sqlTokens = explode(';end', $version[1]);
                foreach ($sqlTokens as $sqlToken) {
                    // Only run lines that haven't already been run for cutting edge
                    if ($cuttingEdge && version_compare($tokenCount, $this->cuttingEdgeCodeLine, '>=')) {
                        $this->executeSQL($sqlToken);
                    }
                    
                    $tokenCount++;
                }
            }
        }
    }

    protected function partialVersionUpdate()
    {
        foreach ($this->sql as $version) {
            $tokenCount = 0;
            if (version_compare($version[0], $this->versionDB, '>=') and version_compare($version[0], $this->versionCode, '<=')) {
                $sqlTokens = explode(';end', $version[1]);
                if ($version[0] == $this->versionDB) { 
                    // Finish current version
                    foreach ($sqlTokens as $sqlToken) {
                        if (version_compare($tokenCount, $this->cuttingEdgeCodeLine, '>=')) {
                            $this->executeSQL($sqlToken);
                        }
                        
                        ++$tokenCount;
                    }
                } else { 
                    // Update intermediate versions and max version
                    foreach ($sqlTokens as $sqlToken) {
                        $this->executeSQL($sqlToken);
                    }
                }
            }
        }
    }

    protected function executeSQL($sqlToken)
    {
        if (trim($sqlToken) == '') return;

        try {
            $this->db->getConnection()->query($sqlToken);
        } catch (PDOException $e) {
            $this->errors[] = htmlPrep($sqlToken).'<br/><b>'.$e->getMessage().'</b><br/>';
        }
    }

    protected function loadChangeDB()
    {
        if (!empty($this->sql)) {
            return $this->sql;
        }

        if (!file_exists($this->absolutePath.'/CHANGEDB.php')) {
            return [];
        }

        include $this->absolutePath.'/CHANGEDB.php';

        $this->loadCuttingEdgeDetails($sql);

        return $sql;
    }

    protected function loadCuttingEdgeDetails(&$sql)
    {
        if (!$this->isCuttingEdge()) return;

        $latestVersion = end($sql);
        $sqlTokens = explode(';end', $latestVersion[1]);
        $this->cuttingEdgeVersion = $latestVersion[0];
        $this->cuttingEdgeMaxLine = count($sqlTokens) - 1;
    }
}
