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
	protected $systemRequirements;

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

		$this->session->setDatabaseConnection($pdo);

		if (empty($this->session->get('systemSettingsSet'))) {
			$this->session->loadSystemSettings($pdo);
			$this->session->loadLanguageSettings($pdo);
        }
		
		$installType = $this->session->get('installType');
        if ($installType == 'Development' || $installType == 'Testing') {
			set_error_handler(array($this, 'handleError'));
            set_exception_handler(array($this, 'handleException'));
        } else {
            ini_set('display_errors', 0);
            set_exception_handler(array($this, 'handleExceptionInProduction'));
        }

		$this->locale->setLocale($this->session->get(array('i18n', 'code')));
		$this->locale->setTimezone($this->session->get('timezone', 'UTC'));
		$this->locale->setTextDomain($pdo);
		$this->locale->setStringReplacementList($pdo);

		$this->initialized = true;
    }

	/**
	 * Is Gibbon Installed? Based on existance of config.php file
	 *
	 * @return   bool
	 */
	public function isInstalled()
	{
		return (file_exists($this->basePath.'/config.php') && filesize($this->basePath.'/config.php') > 0);
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
	 * Gets a System Requirement by array key
	 *
	 * @return   string
	 */
	public function getSystemRequirement($key) {
		return (isset($this->systemRequirements[$key]))? $this->systemRequirements[$key] : null;
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
		$this->systemRequirements = $systemRequirements;
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
		$port = (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] != '80')? ':'.$_SERVER['SERVER_PORT'] : '';
		$host = (isset($_SERVER['SERVER_NAME']))? $_SERVER['SERVER_NAME'] : '';

		$this->baseURL = $http . $host. $port . dirname($domain);
		$this->baseURL = rtrim($this->baseURL, '/ ');
	}

    /**
     * Callback for handling PHP errors and those generated with trigger_error(). Callback signature from set_error_handler().
     * @param int $errno
     * @param string $errstr
     * @param string $errfile
     * @param int $errline
     */
    public function handleError($errno, $errstr, $errfile = null, $errline = null) 
    {
        if (!(error_reporting() & $errno)) return false;

        $errorType = 'Unknown Error';
        if ($errno & (E_PARSE | E_ERROR | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR)) $errorType = 'Fatal Error';
        if ($errno & (E_WARNING | E_USER_WARNING | E_COMPILE_WARNING | E_RECOVERABLE_ERROR)) $errorType = 'Warning';
        if ($errno & (E_DEPRECATED | E_USER_DEPRECATED)) $errorType = 'Deprecated';
        if ($errno & (E_NOTICE | E_USER_NOTICE)) $errorType = 'Notice';
        if ($errno & (E_STRICT)) $errorType = 'Strict';

        $origin = ($errno & (E_USER_ERROR | E_USER_WARNING | E_USER_DEPRECATED | E_USER_NOTICE))? 'Gibbon' : 'PHP';
        $stackTrace = debug_backtrace();

        $this->displayFormattedError($errno, $origin.' '.$errorType, $errstr, next($stackTrace), $errfile, $errline);
    }

    /**
     * Callback for handling uncaught exceptions. Also closes the main content tag (prevents missing sidebar). Callback signature from set_exception_handler().
     * @param Exception $e
     */
    public function handleException($e) 
    {
        $this->displayFormattedError($e->getCode(), 'Fatal Error', 'Uncaught '.get_class($e).' - '.$e->getMessage(), $e->getTrace(), $e->getFile(), $e->getLine());
        echo '</div><br style="clear: both">';
    }

    /**
     * Fallback more gracefully from Fatal Errors in production by displaying the generic error message and closing the main content tag (prevents missing sidebar).
     * @param Exception $e
     */
    public function handleExceptionInProduction($e) 
    {
        if (headers_sent()) {
            include($this->absolutePath.'/error.php');
            echo '</div><br style="clear: both">';
        } else {
            header("Location: ".$this->absoluteURL."/index.php?q=error.php");
        }
    }

    /**
     * Output HTML formatted errors with a stack trace. CSS moved inline to apply to errors before the HTML head is rendered.
     * @param int $errorCode
     * @param string $errorName
     * @param string $errorMessage
     * @param array $stackTrace
     * @param string $file
     * @param int $line
     */
    protected function displayFormattedError($errorCode, $errorName, $errorMessage, $stackTrace = array(), $file = null, $line = null) 
    {
        echo '<div style="display: flow-root; border-left: 6px solid #444; color: #444; background-color: #f9f9f9; font-family: Helvetica, Arial, sans-serif; font-size: 12px; padding: 10px; margin: 10px 0px 15px 0px; box-shadow: 2px 2px 2px rgba(50,50,50,0.15);">';
        echo sprintf('<strong title="Error Code: %1$s">%2$s</strong>: %3$s', $errorCode, $errorName, $errorMessage);
        
        echo '<ul>';
        echo sprintf('<li>Line %1$s in <span title="%2$s">%3$s</span></li>', $line, $file, str_replace($this->basePath, '', $file));

        foreach ($stackTrace as $index => $caller) {
            if (empty($caller['file']) || empty($caller['line'])) continue;
            echo sprintf('<li>Line %1$s in <span title="%2$s">%3$s</span></li>', $caller['line'], $caller['file'], str_replace($this->basePath, '', $caller['file']));
        }
        
        echo '</ul>';
        echo '</div>';
    }
}
