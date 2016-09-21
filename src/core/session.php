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
use Gibbon\Record\person ;
use Gibbon\Record\house ;

/**
 * Session Manager
 *
 * @version	18th September 2016
 * @since	15th April 2016
 * @author	Craig Rayner
 * @package	Gibbon
 * @subpackage	Core
 */
class session
{
	/**
	 * string
	 */
	private	$guid  ;

	/**
	 * string
	 */
	private	$base ;

	/**
	 * Construct
	 *
	 * @version	29th June 2016
	 * @since	15th April 2016
	 * @return	void
	 */
	public function __construct()
	{
		if (defined('GIBBON_UID'))
			$this->guid = GIBBON_UID;
		else
		{
			$config = new config();
			$this->guid = $config->get('guid');
		}
		if (PHP_SESSION_ACTIVE !== session_status())
		{
//			session_name("Gibbon-".$this->guid);
//			session_set_cookie_params(0);
			$path = '/';
//			session_name('Gibbon-v13-Backwards');
			session_start(array('cookie_path' => $path, 'cache_limiter' => 'private_no_expire'));
			$this->clear('scripts');
		}
		date_default_timezone_set($this->get('timezone', 'UTC'));
		if ($this->notEmpty('username') && $this->get('security.lastPageTime') < strtotime('now') - $this->get('security.sessionDuration', 1200))
		{
			header('Location: '. GIBBON_URL . 'index.php?q=/modules/Security/logout.php&timeout=true&divert=true');
		}
		$this->set('security.lastPageTime', strtotime('now'));
	}

	/**
	 * get Value
	 *
	 * @version	5th May 2016
	 * @since	15th April 2016
	 * @param	string	$name	Session Value Name
	 * @param	mixed	$default	if variable does not exist, then return this value.
	 * When the name refers to an array then levels are separated by a period '.'
	 * So $this->session->get('x']['y']['z'] would have a name = x.y.z
	 * @return	mixed
	 */
	public function get($name, $default = NULL)
	{
		if (strpos(',', $name))
			throw new Exception(trans::__('Session Name cannot contain a comma. '.$name));
		if (strpos($name, $this->guid) === false) $name = $this->guid.'.'.$name;
		$steps = explode('.', $name);
		foreach($steps as $q=>$w)
			$steps[$q] = trim($w);
		if (count($steps) === 1)
		{
			if (isset($_SESSION[$name]))
				return $_SESSION[$name] ;
		}
		else {
			if (isset($_SESSION[$steps[0]]))
				return $this->getSub($steps, $_SESSION[$steps[0]], $default);
			else
				return $default;
		}
		return $default ;
	}

	/**
	 * set Value
	 *
	 * @version	1st May 2016
	 * @since	15th April 2016
	 * @param	string	Session Value Name
	 * @param	mixed	Session Value
	 * @return	object	Gibbon\session
	 */
	public function set($name, $value)
	{
		$this->base = NULL;
		if (strpos(',', $name))
			throw new Exception(trans::__('Session Name cannot contain a comma. '.$name));
		if (strpos($name, $this->guid) === false) $name = $this->guid.'.'.$name;
		$steps = explode('.', $name);
		foreach($steps as $q=>$w)
			$steps[$q] = trim($w);
			
		if (count($steps) > 1)
		{
			$aValue = $this->setSub($steps, $this->get($steps[0]), $value);
			return $this->set($this->base, $aValue);
		}
		else {
			$_SESSION[$name] = $value ;
		}
		return $this ;
	}

	/**
	 * get Sub Value
	 *
	 * @version	11th September 2016
	 * @since	15th April 2016
	 * @param	array	Step Names
	 * @param	array	Parent Value
	 * @return	mixed
	 */
	private function getSub($steps, $parent, $default = null)
	{
		array_shift($steps);
		if (count($steps) === 1)
		{
			if (isset($parent[$steps[0]]))
				return $parent[$steps[0]] ;
		}
		else
		{
			if (empty($parent[$steps[0]]))
				$parent[$steps[0]] = array();
			return $this->getSub($steps, $parent[$steps[0]], $default);
		}
		return $default ;
	}

	/**
	 * set Sub Value
	 *
	 * @version	11th September 2016
	 * @since	15th April 2016
	 * @param	array	Name Array
	 * @param	array	Current Value
	 * @param	mixed	Session Value
	 * @return	object	Gibbon\core\	session
	 */
	public function setSub($steps, $existing, $value)
	{
		if ($this->base === NULL)
			$base = $this->base = array_shift($steps);
		else
			$base = array_shift($steps);
		if (count($steps) === 1)
		{
			if (empty($existing) || ! is_array($existing))
				$existing = array();
			$existing[$steps[0]] = $value; 
		}
		else
		{
			if (empty($existing)) $existing = array();
			if (empty($existing[$steps[0]])) $existing[$steps[0]] = array();
			$existing[$steps[0]] = $this->setSub($steps, $existing[$steps[0]], $value);
		}
		return $existing;	
	}
	
	/**
	 * set System Settings
	 *
	 * Gets system settings from database and writes them to individual session variables.
	 * @version 22nd June 2016
	 * @since	Pulled from functions.php
	 * @param	Gibbon\sqlConnection
	 * @return	void
	 */
	public function getSystemSettings(sqlConnection $pdo) {

		//System settings from gibbonSetting
		$this->set("systemSettingsSet", true) ;

		$message = NULL;
		$data=array();
		$sql="SELECT * FROM gibbonSetting WHERE scope='System'" ;
		$result=$pdo->executeQuery($data, $sql, $message);
		if (! $pdo->getQuerySuccess()) 
			$this->set("systemSettingsSet", false) ;
		else
			while ($row=$result->fetch()) 
				$this->set($row["name"], $row["value"]) ;
				
		$this->set('security.sessionDuration', $this->get('sessionDuration', 1200));
	
		//Get names and emails for administrator, dba, admissions
		//System Administrator
		$pObj = new person(new view('default.blank', array(), $this, null, $pdo), $this->get("organisationAdministrator"));
		if ($pObj->getSuccess()) {
			$this->set("organisationAdministratorName", $pObj->formatName(false, true)) ;
			$this->set("organisationAdministratorEmail", $pObj->getField("email"));
		}
		//DBA
		$pObj->find($this->get("organisationDBA"));
		if ($pObj->getSuccess()) {
			$this->set("organisationDBAName",$pObj->formatName(false, true)) ;
			$this->set("organisationDBAEmail", $pObj->getField("email")) ;
		}
		//Admissions
		$pObj->find($this->get("organisationAdmissions"));
		if ($pObj->getSuccess()) {
			$this->set("organisationAdmissionsName", $pObj->formatName(false, true)) ;
			$this->set("organisationAdmissionsEmail", $pObj->getField("email")) ;
		}

		//HR Administrator
		$pObj->find($this->get('organisationHR') );
		if ($pObj->getSuccess()) {
			$this->set("organisationHRName", $pObj->formatName(false, true)) ;
			$this->set("organisationHREmail",$pObj->getField("email")) ;
		}
	
	
		//Language settings 
		
		$this->setLanguageSession($this->get('defaultLanguage')) ;
	
	}

	/**
	 * set Language Session
	 *
	 * @version	6th September 2016
	 * @since	pulled from functions.php
	 * @param	string	$code	Language Code
	 * @return	void
	 */
	public function setLanguageSession( $code ) 
	{
		if (empty($code))
			$code = 'en_GB';
		$this->clear('i18n');
		$x = Yaml::parse( file_get_contents(GIBBON_ROOT . "config/local/languages.yml") );
		$row = $x['languages'][$code];
		if (empty($row)) 
		{
			$code = 'en_GB';
			$row = $x['languages'][$code];
		}
		$this->set("i18n.code", $code) ;
		$this->set("i18n.name", $row["name"]) ;
		$this->set("i18n.dateFormat", $row["dateFormat"]['human']) ;
		$this->set("i18n.dateFormatRegEx", $row["dateFormat"]['RegEx']) ;
		$this->set("i18n.dateFormatPHP", $row["dateFormat"]['PHP']) ;
		$this->set("i18n.maintainerName", $row["maintainer"]['name']) ;
		$this->set("i18n.maintainerWebsite", $row["maintainer"]['website']) ;
		$this->set("i18n.rtl", $row["rtl"]) ;
	}

	/**
	 * append Value
	 *
	 * @version	19th April 2016
	 * @since	19th April 2016
	 * @param	string	Session Value Name
	 * @param	mixed	Session Value
	 * @return	object	Gibbon\session
	 */
	public function append($name, $value)
	{
		$value = $this->get($name) . $value;
		return $this->set($name, $value);
	}

	/**
	 * destroy
	 *
	 * @version	20th June 2016
	 * @since	20th April 2016
	 * @return	void
	 */
	public function destroy()
	{
		$this->clear('security.lastPageTime');
		if (PHP_SESSION_ACTIVE === session_status())
			session_unset();
	}

	/**
	 * is Valid Session
	 *
	 * @version	21st April 2016
	 * @since	21st April 2016
	 * @return	boolean		Valid Session
	 */
	public function isValid()
	{
		if (PHP_SESSION_ACTIVE === session_status())
			return true ;
		return false ;
			
	}

	/**
	 * is Empty
	 *
	 * @version	24th April 2016
	 * @since	21st April 2016
	 * @param	string		Name
	 * @return	boolean		is the value empty.
	 */
	public function isEmpty($name)
	{
		$value = $this->get($name);
		if ($value === NULL)
			return true;
		if ($value === "")
			return true;
		if ($value === array())
			return true;
		return false;
	}

	/**
	 * clear Value
	 *
	 * @version	22nd April 2016
	 * @since	22nd April 2016
	 * @param	string	Session Value Name
	 * @return	object	Gibbon\session
	 */
	public function clear($name)
	{
		return $this->set($name, NULL);
	}

	/**
	 * Plus 
	 *
	 * @version	18th September 2016
	 * @since	23rd April 2016
	 * @param	string	$name 	Session Value Name
	 * @param	integer	$interval	Add the value
	 * @return	object	Gibbon\session
	 */
	public function plus($name, $interval = 1)
	{
		return $this->set($name, intval($this->get($name)) + $interval);
	}

	/**
	 * is Set
	 *
	 * @version	24th April 2016
	 * @since	24th April 2016
	 * @param	string		Name
	 * @return	boolean		is the value empty.
	 */
	public function notEmpty($name)
	{
		return (! $this->isEmpty($name));
	}

	/**
	 * Load Logo
	 *
	 * @version	27th May 2016
	 * @since	27th May 2016
	 * @return	void
	 */
	public function loadLogo()
	{
		//Get house logo and set session variable, only on first load after login (for performance)
		if (intval($this->get("pageLoads")) === 0 AND $this->notEmpty("username") AND $this->notEmpty("gibbonHouseID"))
		{
			$hObj = new house(new view(), $this->get("gibbonHouseID"));
	
			if ($hObj->getSuccess()) {
				$this->set("gibbonHouseIDLogo", $hObj->getField("logo")) ;
				$this->set("gibbonHouseIDName", $hObj->getField("name")) ;
			}
		}
	}

	/**
	 * start
	 *
	 * Alias for Construct
	 * @version	2nd June 2016
	 * @since	2nd June 2016
	 * @return	void
	 */
	public function start()
	{
		$this->__construct();
	}

	/**
	 * push
	 *
	 * @version	30th June 2016
	 * @since	30th June 2016
	 * @param	string	$name		Session Value Name
	 * @param	mixed	$value		Value to push to the array
	 * @return	object	Gibbon\session
	 */
	public function push($name, $value, $v)
	{
		$x = $this->get($name);
		$x = ! empty($x) && is_array($x) ? $x : array();
		$x[] = $value;
		return $this->set($name, $x);
	}
}