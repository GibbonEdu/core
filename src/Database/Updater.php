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

use Gibbon\Domain\System\SettingGateway;

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

    protected $settingGateway;
    protected $absolutePath;

    protected $sql = [];
    protected $errors = [];

    public function __construct(SettingGateway $settingGateway)
    {
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

        return true;
    }

    public function isUpdateRequired()
    {
        if (!$this->isVersionValid()) {
            return false;
        }

        if (!$this->isCuttingEdge()) {
            return version_compare($this->versionDB, $this->versionCode);
        }

        $latestVersion = end($this->sql);
        $sqlTokens = explode(';end', $latestVersion[1]);
        $this->cuttingEdgeVersion = $latestVersion[0];
        $this->cuttingEdgeMaxLine = count($sqlTokens) - 1;

        if (version_compare($this->cuttingEdgeVersion, $this->versionDB, '>')) {
            return true;
        } else if (version_compare($this->cuttingEdgeMaxLine, $this->cuttingEdgeCodeLine, '>')) {
            return true;
        }

        return false;
    }

    public function update()
    {
        if (!$this->isUpdateRequired()) {
            return false;
        }

        return true;
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

        return $sql;
    }
}
