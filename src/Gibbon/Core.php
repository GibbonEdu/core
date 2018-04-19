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

namespace Gibbon;

use Psr\Container\ContainerInterface;

/**
 * Gibbon Core
 *
 * @version	v13
 * @since	v13
 */
class Core
{
    /**
     * Gibbon system path and url, only available internally
     * @var  string
     */
    protected $basePath;

    /**
     * Core classes available to all Gibbon scripts 
     * TODO: These need removed & replaced with DI
     * @var  object
     */
    public $session;
    public $locale;
    
    /**
     * Configuration variables
     * @var  string
     */
    protected $guid;
    protected $caching;
    protected $version;
    protected $systemRequirements;

    /**
     * Has gibbon been initialized using a DB connection?
     * @var  bool
     */
    private $initialized;

    /**
     * Construct
     */
    public function __construct($directory)
    {
        $this->basePath = realpath($directory);
        
        // Set the current version
        $this->loadVersionFromFile($this->basePath . '/version.php');

        // Load the configuration, if installed
        if ($this->isInstalled()) {
            $this->loadConfigFromFile($this->basePath . '/config.php');
        }
    }

    /**
     * Setup the Gibbon core: Runs once (enforced), if Gibbon is installed & database connection exists
     *
     * @param   ContainerInterface  $container
     */
    public function initializeCore(ContainerInterface $container)
    {
        if ($this->initialized == true) return;

        $db = $container->get('db');

        $this->session->setDatabaseConnection($db);

        if (empty($this->session->get('systemSettingsSet'))) {
            $this->session->loadSystemSettings($db);
            $this->session->loadLanguageSettings($db);
        }

        $installType = $this->session->get('installType');
        if (empty($installType) || $installType == 'Production') {
            ini_set('display_errors', 0);
        }

        $this->locale->setLocale($this->session->get(array('i18n', 'code')));
        $this->locale->setTimezone($this->session->get('timezone', 'UTC'));
        $this->locale->setTextDomain($db);
        $this->locale->setStringReplacementList($db);

        $this->initialized = true;
    }

    /**
     * Is Gibbon Installed? Based on existance of config.php file
     *
     * @return   bool
     */
    public function isInstalled()
    {
        return (file_exists($this->basePath . '/config.php') && filesize($this->basePath . '/config.php') > 0);
    }

    /**
     * Gets the globally unique id, to allow multiple installs on the server
     *
     * @return   string|null
     */
    public function guid()
    {
        return $this->guid;
    }

    /**
     * Gets the current Gibbon version
     *
     * @return   string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Gets a System Requirement by array key
     *
     * @return   string
     */
    public function getSystemRequirement($key)
    {
        return (isset($this->systemRequirements[$key])) ? $this->systemRequirements[$key] : null;
    }

    /**
     * Gets system-wide caching factor, used to balance performance and freshness.
     *
     * @return   int|null
     */
    public function getCaching()
    {
        return $this->caching;
    }

    /**
     * Load the current Gibbon version number
     *
     * @param    string  $versionFilePath
     *
     * @throws   Exception If the version file is not found
     */
    protected function loadVersionFromFile($versionFilePath)
    {
        if (file_exists($versionFilePath) == false) {
            throw new Exception('Gibbon version.php file missing: ' . $versionFilePath);
        }

        include $versionFilePath;

        $this->version = $version;
        $this->systemRequirements = $systemRequirements;
    }

    /**
     * Load the Gibbon configuration file, contained in this scope to prevent unintended global access
     *
     * @param   string  $configFilePath
     */
    protected function loadConfigFromFile($configFilePath)
    {
        include $configFilePath;

        //Sets globally unique id, to allow multiple installs on the server.
        $this->guid = $guid;

        //Sets system-wide caching factor, used to balance performance and freshness.
        $this->caching = $caching;
    }
}
