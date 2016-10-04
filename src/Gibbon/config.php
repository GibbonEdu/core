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
 * @package	Gibbon
 * @subpackage	Old
 * @deprecated
 */
class config
{
	private $dbHost;
	private $dbName;
	private $dbUser;
	private $dbPWord;
	private $guid;
	private $caching;
	private $baseDir;
	private $baseURL;
	private $version;

	/**
	 * Construct
	 *
	 * This constructor is only for version 12 and backwards.
	 * @version	18th April 2016
	 * @since	18th April 2016
	 */
	public function __construct()
	{
		if ( file_exists(GIBBON_ROOT . "config.php"))
			include GIBBON_ROOT.'config.php';
		$this->dbHost = $databaseServer ;
		$this->dbUser = $databaseUsername ;
		$this->dbPWord = $databasePassword ;
		$this->dbName = $databaseName ;

		//Sets globally unique id, to allow multiple installs on the server server.
		$this->guid = $guid ;

		//Sets system-wide caching factor, used to baalance performance and freshness. Value represents number of page loads between cache refresh. Must be posititve integer. 1 means no caching.
		$this->caching = $caching ;

		$this->baseDir = rtrim(GIBBON_ROOT, '/');
		$this->baseURL = GIBBON_URL;
		
		include GIBBON_ROOT.'version.php';
		$this->version = $version ;
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
		$currencies = $yaml::parse( file_get_contents(GIBBON_ROOT . "config/currency.yml") );

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

	/**
	 * upgrade Cofig
	 *
	 * Create yml format from existing php config
	 * 
	 * @version	14th April 2016
	 * @since	14th April 2016
	 * @return	void
	 */
	private function upgradeConfig()
	{
		include GIBBON_ROOT . 'config.php';

		$config = array();
		$config['dbHost'] = $databaseServer ;
		$config['dbUser'] = $databaseUsername ;
		$config['dbPWord'] = $databasePassword ;
		$config['dbName'] = $databaseName ;
		$config['guid'] = $guid; 
		$config['caching'] = 10 ; 
		
		file_put_contents(GIBBON_ROOT . 'config/config.yml', Yaml::dump($config));

		foreach($config as $name=>$value)
			$this->$name = $value ;
		$this->baseDir	= rtrim(GIBBON_ROOT, '/');
		$this->baseURL	= rtrim(GIBBON_URL);
		unlink(GIBBON_ROOT . 'config.php');
		unlink(GIBBON_ROOT . 'version.php');
	}
}


include GIBBON_ROOT.'config.php';
