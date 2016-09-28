<?php
/**
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
/**
 */
namespace Gibbon\core;

use Symfony\Component\Yaml\Yaml ;
use Gibbon\core\view ;
use Gibbon\core\helper ;
use Gibbon\Record\setting ;
use stdClass ;

/**
 * Configuration Manager
 *
 * @version	24th September 2016
 * @since	8th April 2016
 * @author	Craig Rayner
 * @package	Gibbon
 * @subpackage	Core
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
	private $system	;
	private $scope ;
	private $languages = array();
	private $languageList = array();
	private $currencies = array();
	private $install = false ;
	private $setting = array() ;
	private $pdo ;
	private $view ;
	private $session ;
	private $relationships = array();
	private $titles = array();
	private $staff = array();
	private $family = array();
	private $phoneTypes = array();
	private $countries = array();
	private $medConditions = array();
	private $districts = array();

	/**
	 * Construct
	 *
	 * @version	10th September 2016
	 * @since	8th April 2016
	 * @param	Gibbon\sqlConnection	$pdo
	 */
	public function __construct($pdo = null)
	{
		if ($pdo instanceof sqlConnection)
			$this->pdo = $pdo ;
		$this->upgradeConfig();
		$this->system = new \stdClass();
		if (! file_exists(GIBBON_ROOT . "config/.htaccess"))
			file_put_contents(GIBBON_ROOT . "config/.htaccess", 'deny from all');
		if ('5cc8a02be988615b049f5abecba2f3a0' !== md5(file_get_contents(GIBBON_ROOT . "config/.htaccess")))
			file_put_contents(GIBBON_ROOT . "config/.htaccess", 'deny from all');
		if (file_exists(GIBBON_ROOT . 'config/local/config.php')) {
			$content = file_get_contents(GIBBON_ROOT . 'config/local/config.php') ;
			$config = Yaml::parse(mb_substr($content, 13));
			foreach($config as $name=>$value)
				$this->$name = $value ;
		} else
			$this->install = true ;
		if (! empty($this->guid) && ! defined('GIBBON_UID'))
			define('GIBBON_UID', $this->guid);
		$this->baseDir	= rtrim(GIBBON_ROOT, '/');
		$this->baseURL	= rtrim(GIBBON_URL);
		$this->getVersion();
		$this->getLanguages();
		$this->getDatabase();
	}

	/**
	 * get
	 * 
	 * @version	6th July 2016
	 * @since	8th April 2016
	 * @param	string	$name	Configuration Name
	 * @return	mixed	Configuration Setting
	 */
	public function get($name)
	{
		if ( isset( $this->$name ) )
			return $this->$name;
		if ($name === 'guid' && isset($_GET['guid']))
			return $this->$name = $_GET['guid'];
		if ($name === 'guid' && file_exists(GIBBON_ROOT . 'config.php'))
		{
			include GIBBON_ROOT . 'config.php';
			if (isset($guid))
				return $this->$name = $guid;
		}
		return NULL;
	}

	/**
	 * get Currency List
	 * 
	 * @version	16th May 2016
	 * @since	12th April 2016
	 * @return	array	Currencies
	 */
	public function getCurrencyList()
	{
		if ( empty ($this->currencies) AND file_exists( GIBBON_ROOT . 'config/local' . "/currency.yml" ) )	
		{
			$x = Yaml::parse( file_get_contents( GIBBON_ROOT . 'config/local' . "/currency.yml" ) ) ;
			$this->currencies = $x['currencies'] ;
		}
		return $this->currencies ;
	}

	/**
	 * upgrade Config
	 *
	 * Create yml format from existing php config
	 * 
	 * @version	6th September 2016
	 * @since	14th April 2016
	 * @return	void
	 */
	private function upgradeConfig()
	{
		$guid = $this->get('guid') ;
		if (empty($guid)) return ;
		if (is_dir(GIBBON_ROOT . 'config/local') && ! is_file(GIBBON_ROOT . 'config/local/config.php'))
		{
			try
			{
				$dir = GIBBON_ROOT . 'config' ;
				if (is_dir($dir)) {
					foreach (glob($dir.'/*.yml') as $file)   
					{  
						$fileName = basename($file);
						if (! file_exists(GIBBON_ROOT . 'config/local' . '/' . $fileName))
							copy($file, GIBBON_ROOT . 'config/local' . '/' . $fileName) ; 
					}
				}
			}
			catch (Exception $e)
			{
				throw new Exception('Not able to generate Configuration files.', 28000 + intval(__LINE__));
			}
		}

		if (! file_exists(GIBBON_ROOT . "config.php"))
			return ;

		include GIBBON_ROOT . 'config.php';

		if (is_dir(GIBBON_ROOT . 'config/local') && ! file_exists(GIBBON_ROOT . 'config/local/config.php')) 
		{
			$config = array();
			$config['database']['dbHost'] = $databaseServer ;
			$config['database']['dbUser'] = $databaseUsername ;
			$config['database']['dbPWord'] = $databasePassword ;
			$config['database']['dbName'] = $databaseName ;
			$config['guid'] = $guid; 
			$config['caching'] = $caching ; 

			if (! file_put_contents(GIBBON_ROOT . 'config/local/config.php', "<?php\ndie();\n" . Yaml::dump($config)))
				throw new \Exception('Not able to generate Configuration files.', 28000 + intval(__LINE__));
			else
			{
				$this->pdo = new sqlConnection(true, null, $this);
				$this->pdo->executeQuery(array(), "UPDATE `gibbonTheme` SET `active` = 'N'");
				$this->pdo->executeQuery(array(), "INSERT INTO `gibbonTheme` (`gibbonThemeID`, `name`, `description`, `active`, `version`, `author`, `url`) VALUES
(0001, 'Bootstrap', 'Gibbon\'s 2016 look and feel.', 'Y', '1.0.00', 'Craig Rayner', 'http://www.craigrayner.com')");			

			}
		}
		elseif (! is_dir(GIBBON_ROOT . 'config/local'))
			throw new \Exception('Not able to generate Configuration files.', 28000 + intval(__LINE__));
	}

	/**
	 * get Setting Value by Scope
	 *
	 * Gets the desired setting, specified by name and scope.
	 * @version	24th September 2016
	 * @since	20th April 2016
	 * @param	string	$scope		Scope
	 * @param	string	$name		Name
	 * @param	mixed	$default	Default Value
	 * @return	mixed	Value
	 */
	public function getSettingByScope( $scope, $name, $default = null )
	{
		if (isset($this->setting[$scope][$name]))
			return $this->setting[$scope][$name];
		if (empty($this->system->$scope))
			$this->system->$scope = new \stdClass();
		if ( isset($this->system->$scope->$name) )
			return $this->system->$scope->$name ;
	
		$this->system->$scope->$name = false ;
		$pdo = $this->getPDO();
		$data = array("scope"=>$scope, "name"=>$name);
		$sql = "SELECT `value`, `type`
			FROM `gibbonSetting` 
			WHERE `scope`=:scope 
				AND `name`=:name" ;
		$result = $pdo->executeQuery($data, $sql);
		if ($pdo->getQuerySuccess() && $result->rowCount() === 1) 
			return $this->system->$scope->$name = $this->databaseToValue($result->fetchObject()) ;
		else
		{
			unset($this->system->$scope->$name);
			return $default;
		}
		return $this->system->$scope->$name ;
	}

	/**
	 * set Setting by Scope
	 *
	 * @version	24th September 2016
	 * @since	21st April 2016
	 * @param	string		$name	Name
	 * @param	mixed		$value	Value
	 * @param	string		$scope	Scope
	 * @return	boolean		Success
	 */
	public function setSettingByScope($name, $value, $scope = NULL )
	{
		if ($scope === NULL)
			$scope = $this->scope;
		else
			$this->scope = $scope;
		$value = filter_var($value);
		if (empty($this->system->$scope))
			$this->system->$scope = new stdClass();
		$pdo = new setting($this->getView());
		$record = $pdo->findOneBy(array("scope" => $scope, "name" => $name));
		$value = $this->valueToDatabase($record, $value);
		$el = new stdClass();
		$el->type = $record->type;
		$el->value = $value;
		$this->system->$scope->$name = $this->databaseToValue($el);
		$ok = true ;
		if ($pdo->getField('value') != $value)
		{
			$pdo->setField('value', $value);
			$ok = $pdo->writeRecord(array('value'));
		}
		if (isset($this->setting[$scope][$name]))
			$this->updateConfigYaml($name, $this->system->$scope->$name);
		return $ok;
	}

	/**
	 * set Scope
	 *
	 * @version	21st April 2016
	 * @since	21st April 2016
	 * @param	string		$scope	Scope
	 * @return	void
	 */
	public function setScope( $scope )
	{
		$this->scope = $scope ;
	}

	/**
	 * isInstall
	 *
	 * @version	24th April 2016
	 * @since	23rd April 2016
	 * @return	boolean		is Install
	 */
	public function isInstall( )
	{
		return (! (false === strpos($_SERVER['PHP_SELF'], 'installer/install.php')) OR $this->install);
	}

	/**
	 * get Languages
	 *
	 * @version	19th September 2016
	 * @since	23rd April 2016
	 * @return	array	Languages
	 */
	public function getLanguages( )
	{
		if ( empty ($this->languages) && file_exists(GIBBON_ROOT . 'config/local' . "/languages.yml"))	
		{
			$this->languages = array();
			$x = Yaml::parse( file_get_contents(GIBBON_ROOT . 'config/local' . "/languages.yml") );
			foreach($x['languages'] as $code=>$details)
			{
				if ($details['active'] == 'Y')
					$this->languages[$code] = $details['name'];
			}
		} elseif (empty ($this->languages) && file_exists(GIBBON_ROOT . "config/languages.yml"))
		{
			$this->languages = array();
			$x = Yaml::parse( file_get_contents(GIBBON_ROOT . "config/languages.yml") );
			foreach($x['languages'] as $code=>$details)
			{
				if ($details['active'] == 'Y')
					$this->languages[$code] = $details['name'];
			}
		}
		return $this->languages ;
	}


	/**
	 * set
	 *
	 * @version	23rd April 2016
	 * @since	23rd April 2016
	 * @param	string		$name	name
	 * @param	mixed		$value	value
	 * @return	Gibbon\config
	 */
	public function set($name, $value)
	{
		$this->$name = $value ;
		return $this ;
	}

	/**
	 * get Setting
	 *
	 * Gets the desired setting, specified by name and scope.
	 * @version	24th September 2016
	 * @since	25th April 2016
	 * @param	string		$name	Name
	 * @param	string		$scope	Scope
	 * @param	string		$object	Object Name
	 * @return	mixed		Defined Object, stdClass or NULL
	 */
	public function getSetting( $name, $scope, $object = '\stdClass' )
	{
		$settingObj = new setting($this->view);
		$row = $settingObj->findBy(array('scope' => $scope, 'name' => $name));
		if ($settingObj->rowCount() === 1)  {
			if (isset($this->setting[$scope][$name]))
				$row->value = $this->setting[$scope][$name];
			else
			{
				$this->setting[$scope][$name] = $this->databaseToValue($row);
				$row->value = $this->setting[$scope][$name];
			}
			return $row ;
		}
		else
			return NULL;
	}
	
	/**
	 * Update Config Yaml
	 *
	 * Settings that are required before Database connection should be placed update in yaml
	 * @version	1st May 2016
	 * @since	1st May 2016
	 * @param	string		$name	Name
	 * @param	mixed		$value	Value
	 * @return	void
	 */
	private function updateConfigYaml($name, $value)
	{
		$config = Yaml::parse( file_get_contents(GIBBON_ROOT . 'config/local' . "/config.yml") );
		$this->setting[$this->scope][$name] = $value;
		$config['setting'][$this->scope][$name] = $value;
		file_put_contents( GIBBON_ROOT . 'config/local' . "/config.yml", Yaml::dump($config));
		return ;
	}

	/**
	 * get Database
	 *
	 * @version	14th June 2016
	 * @since	14th June 2016
	 * @return	void
	 */
	public function getDatabase()
	{
		if ( ! empty ($this->database))	
		{
			foreach($this->database as $name=>$value)
				$this->$name = $value ;
		}
		return  ;
	}

	/**
	 * update SQL
	 * 
	 * @version	21st June 2016
	 * @since	21st June 2016
	 * @param	Gibbon\view		$view
	 * @return	void
	 */
	public function upgradeSQL(view $view)
	{
		if (intval($view->session->get('gibbonRoleIDCurrent')) === 1 ) 
		{
			$versionDB = $this->getSettingByScope("System", "version" ) ;
			$versionCode = $this->get('version') ;
			if (version_compare($versionDB, $versionCode, '<') && $view->session->isEmpty('address'))
			{
				$view->paragraph('It seems that you have updated your Gibbon code to a new version, and are ready to update your database from v%1$s to v%2$s. %3$sUpdate Now%4$s.', array($versionDB, $versionCode, "<a href='". GIBBON_URL . "index.php?q=/modules/System Admin/update.php'>", '</a>')) ;
			}
		}
	}

	/**
	 * get PDO
	 * 
	 * @version	22nd June 2016
	 * @since	22nd June 2016
	 * @param	Gibbon\sqlConnection
	 * @return	void
	 */
	public function getPDO(sqlConnection $pdo = null)
	{
		if ($pdo instanceof sqlConnection)
			$this->pdo = $pdo;
		if ($this->pdo instanceof sqlConnection)
			return $this->pdo;
		if (isset($this->view->pdo) && $this->view->pdo instanceof sqlConnection)
			$this->pdo = $this->view->pdo ;
		else
			throw new \Exception('No sql Connection class defined.  You may need to inject the view.', intval(22000 + __LINE__));
		return $this->pdo;
	}
	
	/**
	 * inject View
	 *
	 * @version	22nd June 2016
	 * @since	22nd June 2016
	 * @param	Gibbon\view		$view
	 * @return	void
	 */
	public function injectView(view $view)
	{
		$this->view = $view;
		$this->pdo = $view->pdo;
		$this->session = $view->session ;
	}

	/**
	 * get Relationships
	 *
	 * @version	2nd July 2016
	 * @since	2nd July 2016
	 * @return	array	Relationships
	 */
	public function getRelationships()
	{
		if ( empty ($this->relationships) && file_exists(GIBBON_ROOT . 'config/local' . "/schoolData.yml"))	
		{
			$this->relationships = array();
			$x = Yaml::parse( file_get_contents(GIBBON_ROOT . 'config/local' . "/schoolData.yml") );
			if (! empty($x['relationships'])) $this->relationships = $x['relationships'];
		}
		return $this->relationships ;
	}

	/**
	 * get Titles
	 *
	 * @version	7th July 2016
	 * @since	7th July 2016
	 * @return	array	Titles
	 */
	public function getTitles()
	{
		if ( empty ($this->titles) && file_exists(GIBBON_ROOT . 'config/local' . "/schoolData.yml"))	
		{
			$this->titles = array();
			$x = Yaml::parse( file_get_contents(GIBBON_ROOT . 'config/local' . "/schoolData.yml") );
			if (! empty($x['titles'])) $this->titles = $x['titles'];
		}
		return $this->titles ;
	}

	/**
	 * get Staff (Details)
	 *
	 * @version	13th July 2016
	 * @since	13th July 2016
	 * @return	array	Staff
	 */
	public function getStaff()
	{
		if ( empty ($this->staff) && file_exists(GIBBON_ROOT . 'config/local' . "/schoolData.yml"))	
		{
			$this->staff = array();
			$x = Yaml::parse( file_get_contents(GIBBON_ROOT . 'config/local' . "/schoolData.yml") );
			if (! empty($x['staff'])) $this->staff = $x['staff'];
		}
		return $this->staff ;
	}

	/**
	 * get Family
	 *
	 * @version	23th July 2016
	 * @since	23th July 2016
	 * @return	array	Family
	 */
	public function getFamily()
	{
		if ( empty ($this->family) && file_exists(GIBBON_ROOT . 'config/local' . "/schoolData.yml"))	
		{
			$this->family = array();
			$x = Yaml::parse( file_get_contents(GIBBON_ROOT . 'config/local' . "/schoolData.yml") );
			if (! empty($x['family'])) $this->family = $x['family'];
		}
		return $this->family ;
	}

	/**
	 * get Phone Types
	 *
	 * @version	5th August 2016
	 * @since	3rd August 2016
	 * @return	array	Phone Types
	 */
	public function getPhoneTypes()
	{
		if ( empty ($this->phoneTypes) && file_exists(GIBBON_ROOT . 'config/local' . "/schoolData.yml"))	
		{
			$this->phoneTypes = array();
			$x = Yaml::parse( file_get_contents(GIBBON_ROOT . 'config/local' . "/schoolData.yml") );
			if (! empty($x['Phone Types'])) $this->phoneTypes = $x['Phone Types'];
		}
		return $this->phoneTypes ;
	}

	/**
	 * get Countries
	 *
	 * @version	3rd August 2016
	 * @since	3rd August 2016
	 * @return	array	Countries
	 */
	public function getCountries()
	{
		if ( empty ($this->countries) && file_exists(GIBBON_ROOT . 'config/local' . "/country.yml"))	
		{
			$this->countries = Yaml::parse( file_get_contents(GIBBON_ROOT . 'config/local' . "/country.yml") );
		}
		return $this->countries ;
	}

	/**
	 * get Mail Settings
	 *
	 * @version	4th August 2016
	 * @since	4th August 2016
	 * @return	array	Mail Settings
	 */
	public function getMailSettings()
	{
		if ( empty ($this->mailSettings) && file_exists(GIBBON_ROOT . 'config/local' . "/mailer.yml"))	
		{
			$this->mailSettings = Yaml::parse( file_get_contents(GIBBON_ROOT . 'config/local' . "/mailer.yml") );
		}
		return $this->mailSettings ;
	}

	/**
	 * get Medical Conditions
	 *
	 * @version	5th August 2016
	 * @since	5th August 2016
	 * @return	array	Phone Types
	 */
	public function getMedicalConditions()
	{
		if ( empty ($this->medConditions) && file_exists(GIBBON_ROOT . 'config/local' . "/schoolData.yml"))	
		{
			$this->medConditions = array();
			$x = Yaml::parse( file_get_contents(GIBBON_ROOT . 'config/local' . "/schoolData.yml") );
			if (! empty($x['Medical Conditions'])) $this->medConditions = $x['Medical Conditions'];
		}
		return $this->medConditions ;
	}

	/**
	 * get Districts
	 *
	 * @version	10th August 2016
	 * @since	10th August 2016
	 * @return	array	Districts
	 */
	public function getDistricts()
	{
		if ( empty ($this->districts))	
		{
			$this->districts = array();
			$obj = new \Gibbon\Record\district($this->view);
			$x = $obj->findAll('SELECT DISTINCT `name` FROM `gibbonDistrict` ORDER BY `name`');
			foreach ($x as $district)
				$this->districts[] = $district->getField('name');
		}
		return $this->districts ;
	}

	/**
	 * get Districts
	 *
	 * @version	10th August 2016
	 * @since	10th August 2016
	 * @return	array	Districts
	 */
	public function getLanguageList()
	{
		if ( empty ($this->languageList))	
		{
			$this->languageList = array();
			$obj = new \Gibbon\Record\language($this->view);
			$x = $obj->findAll('SELECT DISTINCT `name` FROM `gibbonLanguage` ORDER BY `name`');
			foreach ($x as $language)
				$this->languageList[] = $language->getField('name');
		}
		return $this->languageList ;
	}

	/**
	 * get Version
	 *
	 * @version	24th August 2016
	 * @since	24th August 2016
	 * @return	string
	 */
	public function getVersion()
	{
		if ( empty ($this->version) && file_exists(GIBBON_ROOT . 'config' . "/version.yml"))	
		{
			$version = Yaml::parse( file_get_contents(GIBBON_ROOT . 'config' . "/version.yml") );
			$this->version = $version['version'] ;
		}
		return $this->version ;
	}

	/**
	 * is Empty
	 * 
	 * @version	6th September 2016
	 * @since	6th September 2016
	 * @param	string	$name	Configuration Name
	 * @return	boolean
	 */
	public function isEmpty($name)
	{
		$x = $this->get($name);
		if (empty($x)) return true;
		return false;
	}

	/**
	 * get View
	 * 
	 * @version	9th September 2016
	 * @since	9th September 2016
	 * @return	boolean
	 */
	public function getView()
	{
		if (! $this->view instanceof view)
			$this->view = new view();
		return $this->view ;
	}

	/**
	 * database To Value
	 *
	 * @version	29th September 2016
	 * @since	24th September 2016
	 * @todo	Date management not implemented.
	 * @param	stdClass	$data	
	 * @return	mixed	Value
	 */
	private function databaseToValue($data)
	{
		switch($data->type)
		{
			case 'array':
				$value = json_decode($data->value);
				if (is_null($value) && is_string($data->value) && mb_strlen($data->value) > 0)
				{
					$value = explode(',', $data->value);
				}
				return $value ;
				break;
			case 'number':
				$value = 0;
				if (is_numeric($data->value))
				{
					if (is_integer($data->value))
						$value = intval($data->value);
					else
						$value = floatval($data->value);
				}
				return $value ;
				break;
			case 'date':
			die('Need to work on dates');
				break;
			case 'text':
				return $data->value ;
				break;
			default:
				return $data->value ;
		}
	}

	/**
	 * value to Database
	 *
	 * @version	24th September 2016
	 * @since	24th September 2016
	 * @param	stdClass	$record	
	 * @param	mixed		$value
	 * @return	mixed	Value
	 */
	private function valueToDatabase($record, $value)
	{
		switch ($record->type)
		{
			case 'array':
				$x = explode(',', filter_var($value));
				foreach($x as $q=>$w)
					if (empty($w)) unset($x[$q]);
				return json_encode($x); 
				break ;
			default:
				return filter_var($value);
		}
		$this->view->dump(array($record, $value), true);
	}
}

