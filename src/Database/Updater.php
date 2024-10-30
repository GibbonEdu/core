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

namespace Gibbon\Database;

use Gibbon\Domain\System\SettingGateway;
use Gibbon\Contracts\Database\Connection;
use League\Container\ContainerAwareTrait;
use League\Container\ContainerAwareInterface;
use League\Container\Exception\NotFoundException;

/**
 * Database Updater
 */
class Updater implements ContainerAwareInterface
{
    use ContainerAwareTrait;

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

        require $this->absolutePath.'/version.php';

        $this->versionCode = $version ?? 'version-not-found';
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

    public function isComposerUpdateRequired()
    {
        $currentHash = $this->settingGateway->getSettingByScope('System Admin', 'composerLockHash');
        $autoloadFile = $this->absolutePath.'/vendor/autoload.php';

        return (!empty($currentHash) && $currentHash != $this->getComposerHash()) || !is_file($autoloadFile);
    }

    public function update() : array
    {
        if (!$this->isUpdateRequired()) {
            return [];
        }

        if (!$this->isCuttingEdge()) {
            // Regular release: run all lines for all versions
            error_log('Updater: Regular release - run all lines for all versions');
            $this->fullVersionUpdate();
        } elseif (version_compare($this->cuttingEdgeVersion, $this->versionDB, '>')) {
            // Cutting edge: at least one full version needs to be done first
            error_log('Updater: Cutting edge - at least one full version needs to be done first');
            $this->partialVersionUpdate();
        } else {
            // Cutting edge: less than one whole version, get up to speed in max version
            error_log('Updater: Cutting edge - less than one whole version, get up to speed in max version');
            $this->fullVersionUpdate();
        }

        $this->settingGateway->updateSettingByScope('System', 'version', $this->versionDB);
        $this->settingGateway->updateSettingByScope('System', 'cuttingEdgeCodeLine', $this->isCuttingEdge()? $this->cuttingEdgeMaxLine : 0);

        if (empty($this->errors)) {
            $this->runMigrations();
        }

        return $this->errors;
    }

    protected function fullVersionUpdate()
    {
        $cuttingEdge = $this->isCuttingEdge();

        foreach ($this->sql as $version) {
            $tokenCount = 0;

            if (!empty($this->errors)) {
                error_log('Updater: fullVersionUpdate - found previous error - break loop');
                break;
            }

            if (version_compare($version[0], $this->versionDB, $cuttingEdge ? '>=' : '>') && version_compare($version[0], $this->versionCode, '<=')) {
                error_log(sprintf('Updater: fullVersionUpdate - updating version %s', $version[0]));
                $sqlTokens = explode(';end', $version[1]);
                foreach ($sqlTokens as $sqlToken) {
                    // Only run lines that haven't already been run for cutting edge
                    if (!$cuttingEdge || ($cuttingEdge && version_compare($tokenCount, $this->cuttingEdgeCodeLine, '>='))) {
                        $this->executeSQL($sqlToken);
                    }

                    if ($cuttingEdge && !empty($this->errors)) {
                        error_log(sprintf('Updater: fullVersionUpdate - Line %d run into error. Break now. Run: %s', $tokenCount, $sqlToken));
                        $this->cuttingEdgeMaxLine = $tokenCount;
                        break;
                    }

                    $tokenCount++;
                }

                // Save where we left off, for interrupted updates
                $this->versionDB = $version[0];
            }
        }
    }

    protected function partialVersionUpdate()
    {
        foreach ($this->sql as $version) {
            $tokenCount = 0;

            if (!empty($this->errors)) break;

            if (version_compare($version[0], $this->versionDB, '>=') && version_compare($version[0], $this->versionCode, '<=')) {
                $sqlTokens = explode(';end', $version[1]);
                if ($version[0] == $this->versionDB) {

                    // Finish current version
                    foreach ($sqlTokens as $sqlToken) {
                        if (version_compare($tokenCount, $this->cuttingEdgeCodeLine, '>=')) {
                            $this->executeSQL($sqlToken);
                        }

                        if (!empty($this->errors)) {
                            $this->cuttingEdgeMaxLine = $tokenCount;
                            break;
                        }

                        ++$tokenCount;
                    }
                } else {
                    // Update intermediate versions and max version
                    foreach ($sqlTokens as $sqlToken) {
                        $this->executeSQL($sqlToken);
                    }
                }

                // Save where we left off, for interrupted updates
                $this->versionDB = $version[0];
            }
        }
    }

    protected function executeSQL($sqlToken)
    {
        if (trim($sqlToken) == '') return;

        try {
            $this->db->getConnection()->query($sqlToken);
        } catch (\PDOException $e) {
            $this->errors[] = htmlPrep($sqlToken).'<br/><b>'.$e->getMessage().'</b><br/>';
        }
    }

    public function runMigrations()
    {
        // Get the list of migrations from the db
        $migrationsDB = $this->db->select("SELECT name, version FROM gibbonMigration ORDER BY timestamp")->fetchKeyPair();

        // Get the list of migrations from the filesystem, in order of their date
        $migrations = glob($this->absolutePath.'/src/Database/Migrations/*-*-*-*.php');
        sort($migrations);

        foreach ($migrations as $migrationPath) {
            // Extract the class name from the file name
            $fileName = strchr(basename($migrationPath), '.', true);
            $className = implode('', array_slice(explode('-', $fileName), 3));

            // Skip migrations that have already been run
            if (isset($migrationsDB[$className])) continue;

            // Include the file directly, because the filename does not match the classname
            include $migrationPath;

            // Instantiate this object through the container
            try {
                $migration = $this->container->get($className);
            } catch (NotFoundException $e) {
                $this->errors[] = __('Migration Error').': <b>'.$fileName.':</b> '.$e->getMessage().'<br/>';
                continue;
            }

            if (!$migration->canMigrate()) continue;

            // Do the migration, catch and log any errors
            try {
                $migration->migrate();
            } catch (\Exception $e) {
                $this->errors[] = __('Migration Error').': <b>'.$fileName.':</b> '.$e->getMessage().'<br/>';
                continue;
            }

            // Add this migration to the database
            if (empty($this->errors)) {
                $data = ['name' => $className, 'version' => $this->versionCode];
                $sql = "INSERT INTO gibbonMigration SET name=:name, version=:version";
                $this->db->insert($sql, $data);
            }
        }
    }

    public function getComposerHash()
    {
        $composerLock = file_get_contents($this->absolutePath.'/composer.lock');
        $composerLock = json_decode($composerLock, true);

        return $composerLock['content-hash'] ?? '';
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
