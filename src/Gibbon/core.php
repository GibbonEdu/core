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

use Library\Yaml\Yaml ;

/**
 * Gibbon Core
 *
 * Responsibilities:
 * 		- Configuration (file & db)
 * 		- Initialization
 * 		- System settings
 * 		- Core classes
 * 		- System paths
 *
 * @version	v13
 * @since	v13
 */
class Core {

	/**
	 * Core classes available to all Gibbon scripts
	 * @var  object
	 */
	public $session;
	public $trans;
	public $security;

	/**
	 * Gibbon path and url, set from Database where available, falls back to system path
	 * @var  string
	 */
	protected $absolutePath;
	protected $absoluteURL;

	/**
	 * Gibbon system path and url, only available internally
	 * @var  string
	 */
	protected $basePath;
	protected $baseURL;

	/**
	 * Configuration variables
	 * @var  string
	 */
	protected $guid;
	protected $caching;
	protected $version;

	/**
	 * Has gibbon been initialized using a DB connection?
	 * @var  bool
	 */
	private $initialized;

	/**
	 * Construct
	 */
	public function __construct($directory, $domain)
	{
		// Set the root path
		$this->locateSystemDirectory($directory, $domain);

		// Set the current version
		$this->loadVersionFromFile( $this->basePath.'/version.php' );

		// Load the configuration, if installed
		if ( $this->isInstalled() ) {
			$this->loadConfigFromFile( $this->basePath.'/config.php' );
		}

		// Create the core objects
		$this->session = new session($this);
		$this->locale = new locale($this);
		$this->security = new security($this);

		// Set the absolute Gibbon Path and URL from the session if available, otherwise default to basePath and URL
		$this->absolutePath = $this->session->get('absolutePath', $this->basePath );
		$this->absoluteURL = $this->session->get('absoluteURL', $this->baseURL );
	}

	/**
	 * Setup the Gibbon core: Runs once (enforced), if Gibbon is installed & database connection exists
	 *
	 * @param   Gibbon\sqlConnection  $pdo
	 */
	public function initializeCore(sqlConnection $pdo) {

		if ($this->initialized == true) return;

		// Setup the textdomain based on the current locale  (if any)
		$this->locale->setTextDomain($pdo);

		// Load the string replacements from db
		$this->locale->setStringReplacementList($pdo);

		// Provide the session class with a db connection
		$this->session->setDatabaseConnection($pdo);

		$this->initialized = true;
	}

	/**
	 * Is Gibbon Installed? Based on existance of config.php file
	 *
	 * @return   bool
	 */
	public function isInstalled()
	{
		return file_exists($this->basePath.'/config.php');
	}

	/**
	 * Gets the globally unique id, to allow multiple installs on the server
	 *
	 * @return   string|null
	 */
	public function guid() {
		return $this->guid;
	}

	/**
	 * Gets the current Gibbon version
	 *
	 * @return   string
	 */
	public function getVersion() {
		return $this->version;
	}

	/**
	 * Gets system-wide caching factor, used to balance performance and freshness.
	 *
	 * @return   int|null
	 */
	public function getCaching() {
		return $this->caching;
	}

	/**
	 * Gets the absolute filesystem path, without a trailing /
	 *
	 * @return   string
	 */
	public function getAbsolutePath() {
		return $this->absolutePath;
	}

	/**
	 * Get the absolute url, without a trailing /
	 *
	 * @return   string
	 */
	public function getAbsoluteURL() {
		return $this->absoluteURL;
	}

	/**
	 * Load a YAML configuration file, filename should include the .yml file extension
	 *
	 * @param    array|mixed
	 */
	public function loadYamlConfigFromFile($filename) {
		$yaml = new Yaml();
		return $yaml::parse( file_get_contents($this->basePath . '/config/'. $filename ) );
	}

	/**
	 * Load the current Gibbon version number
	 *
	 * @param    string  $versionFilePath
	 *
	 * @throws   Exception If the version file is not found
	 */
	protected function loadVersionFromFile($versionFilePath) {

		if (file_exists($versionFilePath) == false) {
			throw new Exception('Gibbon version.php file missing: '. $versionFilePath );
		}

		include $versionFilePath;
		$this->version = $version;
	}

	/**
	 * Load the Gibbon configuration file, contained in this scope to prevent unintended global access
	 *
	 * @param   string  $configFilePath
	 */
	protected function loadConfigFromFile($configFilePath) {

		include $configFilePath;

		//Sets globally unique id, to allow multiple installs on the server.
		$this->guid = $guid ;

		//Sets system-wide caching factor, used to balance performance and freshness.
		$this->caching = $caching ;
	}

	/**
	 * Gets the base filesystem path and domain url
	 *
	 * @param    string  $directory
	 * @param    string  $domain
	 */
	protected function locateSystemDirectory($directory, $domain) {
		// Determine the base Gibbon Path
		$this->basePath = str_replace('\\', '/', $directory);
		$this->basePath = rtrim($this->basePath, '/ ');

		// Determine the base Gibbon URL
		$http = (isset($_SERVER['HTTPS']))? 'https://' : 'http://';
		$port = ($_SERVER['SERVER_PORT'] != '80')? ':'.$_SERVER['SERVER_PORT'] : '';

		$this->baseURL = $http . $_SERVER['SERVER_NAME'] . $port . dirname($domain);
		$this->baseURL = rtrim($this->baseURL, '/ ');
	}

}
