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
class core {

	public $config;
	public $session;
	public $trans;

	protected $absolutePath;
	protected $absoluteURL;

	protected $basePath;
	protected $baseURL;

	protected $guid;
	protected $caching;
	protected $version;

	private $initialized;

	/**
	 * Construct
	 *
	 * @version	v13
	 * @since	v13
	 */
	public function __construct()
	{
		// Set the root path
		$this->locateSystemDirectory();

		// Set the current version
		$this->loadVersionFromFile( $this->basePath.'/version.php' );

		// Load the configuration, if installed
		if ( $this->isInstalled() ) {
			$this->loadConfigFromFile( $this->basePath.'/config.php' );
		}

		// Create the core objects
		$this->session = new session($this->guid());
		$this->locale = new Locale($this->session);

		// Set the absolute Gibbon Path and URL from the session if available, otherwise default to basePath and URL
		$this->absolutePath = $this->session->get('absolutePath', $this->basePath );
		$this->absoluteURL = $this->session->get('absoluteURL', $this->baseURL );
	}

	/**
	 * Setup Gibbon core: Runs once (enforced), if Gibbon is installed & database connection exists
	 * 
	 * @version v13
	 * @since   v13
	 * @param   Gibbon\sqlConnection  $pdo
	 */
	public function initializeCore( sqlConnection $pdo) {

		if ($this->initialized == true) return;

		// Setup the textdomain based on the current locale, if any
		$this->locale->setTextDomain($pdo);

		// Load the string replacements from db
		$this->locale->setStringReplacementList($pdo);

		$this->initialized = true;
	}

	public function isInstalled()
	{
		return file_exists($this->basePath.'/config.php');
	}

	public function guid() {
		return $this->guid;
	}

	public function getVersion() {
		return $this->version;
	}

	public function getCaching() {
		return $this->caching;
	}

	public function getAbsolutePath() {
		return $this->absolutePath;
	}

	public function getAbsoluteURL() {
		return $this->absoluteURL;
	}

	public function loadYamlFromFile( $name ) {
		$yaml = new Yaml();
		return $yaml::parse( file_get_contents($this->basePath . '/config/'. $name .'.yml') );
	}

	protected function loadVersionFromFile( $versionFilePath ) {

		if (file_exists($versionFilePath) == false) {
			throw new Exception('Gibbon version.php file missing: '. $versionFilePath );
		}

		include $versionFilePath;
		$this->version = $version;
	}

	protected function loadConfigFromFile( $configFilePath ) {

		include $configFilePath;

		//Sets globally unique id, to allow multiple installs on the server.
		$this->guid = $guid ;

		//Sets system-wide caching factor, used to balance performance and freshness. Value represents number of page loads between cache refresh. Must be posititve integer. 1 means no caching.
		$this->caching = $caching ;
	}

	protected function locateSystemDirectory() {
		// Determine the base Gibbon Path
		$this->basePath = str_replace('src/Gibbon', '', dirname(__FILE__) );
		$this->basePath = rtrim(str_replace('\\', '/', $this->basePath), '/');

		// Determine the base Gibbon URL
		$http = (isset($_SERVER['HTTPS']))? 'https://' : 'http://';
		$port = ($_SERVER['SERVER_PORT'] != '80')? ':'.$_SERVER['SERVER_PORT'] : '';
		$path = dirname(str_replace('src/Gibbon', '', $_SERVER['PHP_SELF']));

		$this->baseURL = $http . $_SERVER['SERVER_NAME'] . $port . $path;
		$this->baseURL = rtrim($this->baseURL, '/ ');
	}

}