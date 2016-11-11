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
 * sql Connection
 *
 * @version	13th April 2016
 * @since	8th April 2016
 * @author	Craig Rayner
 */
class config
{
	private $databaseServer;
	private $databaseName;
	private $databaseUsername;
	private $databasePassword;
	private $guid;
	private $caching;
	private $version;
	private $absolutePath;
	private $absoluteURL;

	/**
	 * Construct
	 *
	 * This constructor is only for version 12 and backwards.
	 * @version	18th April 2016
	 * @since	18th April 2016
	 */
	public function __construct()
	{
		// Determine the base Gibbon Path
		$this->absolutePath = str_replace('src/Gibbon', '', dirname(__FILE__) );
		$this->absolutePath = rtrim(str_replace('\\', '/', $this->absolutePath), '/');

		// Determine the base Gibbon URL
		$http = (isset($_SERVER['HTTPS']))? 'https://' : 'http://';
		$port = ($_SERVER['SERVER_PORT'] != '80')? ':'.$_SERVER['SERVER_PORT'] : '';
		$path = dirname(str_replace('src/Gibbon', '', $_SERVER['PHP_SELF']));

		$this->absoluteURL = $http . $_SERVER['SERVER_NAME'] . $port . $path;
		$this->absoluteURL = rtrim($this->absoluteURL, '/ ');

		// Set the current version
		if ( file_exists( $this->absolutePath.'/version.php')) {
			$this->loadVersionFromFile( $this->absolutePath.'/version.php' );
		} else {
			throw new Exception('Gibbon version.php file missing in gibbon root directory: '. $this->absolutePath );
		}

		// Load the configuration, if installed
		if ( file_exists( $this->absolutePath.'/config.php')) {
			$this->loadConfigFromFile( $this->absolutePath.'/config.php' );
		}
	}

	public function isGibbonInstalled()
	{
		return !empty($this->guid);
	}

	public function loadVersionFromFile( $versionFilePath ) {
		include $versionFilePath;
		$this->version = $version ;
	}

	public function loadConfigFromFile( $configFilePath ) {
		// Include the config file
		include $configFilePath;

		$this->databaseServer = $databaseServer ;
		$this->databaseUsername = $databaseUsername ;
		$this->databasePassword = $databasePassword ;
		$this->databaseName = $databaseName ;

		//Sets globally unique id, to allow multiple installs on the server server.
		$this->guid = $guid ;

		//Sets system-wide caching factor, used to baalance performance and freshness. Value represents number of page loads between cache refresh. Must be posititve integer. 1 means no caching.
		$this->caching = $caching ;

		// Set the absolute Gibbon Path from the session if available
		if (isset($_SESSION[$guid]['absolutePath'])) {
			$this->absolutePath = $_SESSION[$guid]['absolutePath'];
		}

		// Set the absolute Gibbon URL from the session if available
		if (isset($_SESSION[$guid]['absoluteURL'])) {
			$this->absoluteURL = $_SESSION[$guid]['absoluteURL'];
		}
	}

	/**
	 * get
	 * 
	 * @version	8th April 2016
	 * @since	8th April 2016
	 * @param	string	Configuration Name
	 * @return	mixed	Configuration Setting
	 */
	public function get($name)
	{
		if ( isset( $this->$name ) )
			return $this->$name;
		return NULL;
	}

	/**
	 * get Currency List
	 * 
	 * @version	12th April 2016
	 * @since	12th April 2016
	 * @param	string	Configuration Name
	 * @return	mixed	Configuration Setting
	 */
	public function getCurrencyList($name, $value, $style="width: 302px; ")
	{
		$yaml = new Yaml();
		$currencies = $yaml::parse( file_get_contents($this->absolutePath . "/config/currency.yml") );

		$output = "<select name='".$name."' id='".$name."' style='".$style."'>\n";
		foreach ($currencies as $optGroup=>$list)
		{
			$output .= "<optgroup label='--" .__($this->get('guid'), $optGroup) ."--'/>\n";
			foreach ( $list as $key=>$currency) 
			{
				$output .= "<option";
				if ($key == $value) $output .=  " selected" ;
				$output .= " value='".$key."'>".$currency."</option>\n";
			}
			$output .= "</optgroup>\n";
		}
		$output .= "</select>";
		
		return $output ;			
	}
}
