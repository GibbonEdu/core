<?php
/**
 *
 * Security Class
 *
 * Provides Security Methods for Gibbon
 *
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
 * @author	Craig Rayner
 * @package	Gibbon
 * @subpackage	Core
 */
/**
 */
namespace Gibbon\core;

use Gibbon\Record\passwordReset ;
use Symfony\Component\Yaml\Yaml ;
use Gibbon\core\logger ;
use Gibbon\Record\role ;
use Gibbon\Record\person ;

/**
 * Security Manager
 *
 * Provides Security Methods for Gibbon
 * @version	19th September 2016
 * @since	21st April 2016
 */
class security 
{
	/**
	 * Gibbon\sqlConnection
	 */
	private $pdo;
	
	/**
	 * @var string
	 */
	private $salt = '';
	
	/**
	 * @var string
	 */
	private $hash;
	
	/**
	 * @var string
	 */
	private $password;
	
	/**
	 * @var	Gibbon\Record\person
	 */
	private  $person;
	
	/**
	 * @var	Gibbon\Record\passwordReset
	 */
	private  $passwordReset;
	
	/**
	 * @var	boolean
	 */
	private $validUser = null ;
	
	/**
	 * @var	array
	 */
	private  $security;

	/**
	 * Gibbon\config
	 */
	private  $view;

	/**
	 * Gibbon\config
	 */
	private  $config;
	
	/**
	 * get pdo
	 *
	 * @version	22nd June 2016
	 * @since	24th April 2016
	 * @return	Gibbon\sqlConnection
	 */
	public function getPDO()
	{
		if ($this->pdo instanceof sqlConnection)
			return $this->pdo;
		if (isset($this->view->pdo) && $this->view->pdo instanceof sqlConnection)
			$this->pdo = $this->view->pdo ;
		else
			throw new \Exception('No sql Connection class defined.  You may need to inject the view.', intval(23000 + __LINE__));
		return $this->pdo;
	}

	/**
	 * Gibbon\session
	 */
	private  $session;
	
	/**
	 * get Session
	 *
	 * @version	24th April 2016
	 * @since	24th April 2016
	 * @return	Gibbon\sqlConnection
	 */
	public function getSession()
	{
		if ($this->session instanceof session)
			return $this->session;
		if (isset($this->view->session) && $this->view->session instanceof session)
			$this->session = $this->view->session ;
		else
			$this->session = new session();
		return $this->session;
	}
	
	/**
	 * get Session
	 *
	 * @version	24th April 2016
	 * @since	24th April 2016
	 * @return	Gibbon\config
	 */
	public function getConfig()
	{
		if ($this->config instanceof config)
			return $this->config;
		if (isset($this->view->config) && $this->view->config instanceof config)
			$this->config = $this->view->config;
		else
			$this->config = new config();
		return $this->config ;
	}
	
	/**
	 * get View
	 *
	 * @version	24th April 2016
	 * @since	24th April 2016
	 * @return	Gibbon\config
	 */
	public function getView()
	{
		if ($this->view instanceof view)
			return $this->view;
		$this->view = new view('default.blank');
		return $this->view ;
	}
	
	/**
	 * get Person
	 *
	 * @version	9th September 2016
	 * @since	10th May 2016
	 * @param	integer		$id		Person ID
	 * @return	Gibbon\Record\person
	 */
	public function getPerson($id = null)
	{
		if (! $this->person instanceof person)
			$this->person = $this->getView()->getRecord('person');
		if (! is_null($id)) $this->person->find($id);
		return $this->person;
	}

	/**
	 * Is Action Accessible
	 *
	 * @version	19th September 2016
	 * @since	Copy from functions.php
	 * @param	string		$address Address to test
	 * @param	string		$sub Sub Action
	 * @param	string		$message Error Message
	 * <br/> If null the standard message is sent to stdout.
	 * <br/> If empty (='') the method is silent.
	 * @return	boolean
	 */
	public function isActionAccessible($address = null, $sub = null, $message = null)
	{
		$session = $this->getSession();
		$pdo = $this->getPDO();
		if (is_null($address))
			$address = $session->get('address');
		//Check user is logged in
		if (! is_null($session->get("username"))) {
			//Check user has a current role set
			if (! is_null($session->get("gibbonRoleIDCurrent"))) {
				//Check module ready
				$moduleID = $this->view->checkModuleReady($address, $this->getView());
				if ($moduleID) {
					//Check current role has access rights to the current action.
						$data = array("actionName"=>"%" . $this->view->getActionName($address) . "%", "gibbonRoleID" => $session->get("gibbonRoleIDCurrent"), 'moduleID' => $moduleID);
						$sqlWhere = "" ;
						if (! empty($sub)) {
							$data["sub"] = $sub ;
							$sqlWhere="AND `gibbonAction`.`name` = :sub" ;
						}
						$sql = "SELECT COUNT(`gibbonAction`.`name`) 
							FROM gibbonAction, gibbonPermission, gibbonRole 
							WHERE (gibbonAction.URLList LIKE :actionName) 
								AND (gibbonAction.gibbonActionID=gibbonPermission.gibbonActionID) 
								AND (gibbonPermission.gibbonRoleID=gibbonRole.gibbonRoleID) 
								AND (gibbonPermission.gibbonRoleID=:gibbonRoleID) 
								AND (gibbonAction.gibbonModuleID=:moduleID) 
								$sqlWhere" ;
						$result = $pdo->executeQuery($data, $sql);
						$result = $result->fetchColumn();
						if ($result > 0) return true;
						logger::__('The action role was not found.', 'Debug', 'Security', $data, $pdo);
				}
				else
				{
					logger::__('The Module has not been set correctly.', 'Debug', 'Security', array('username' => $session->get("username"), 'address' => $address, 'moduleID' => $moduleID), $pdo);
				}
			} else {
				logger::__('The has not set a Current Role', 'Debug', 'Security', array('username' => $session->get("username"), 'address'=>$address), $pdo);
			}
		} else {
			$x = debug_backtrace();
			$data = array();
			foreach($x as $q=>$w)
			{
				$data[$q]['file'] =$w['file'];
				$data[$q]['line'] =$w['line'];
			}
			$data = array('username' => $session->get("username"), 'address'=>$address, 'message'=>$message, 'data'=>$data);
			logger::__('The Username was not set correctly', 'Debug', 'Security', $data, $pdo);
			if (! is_null($message)) $this->getView()->insertMessage('You need to be authenticated to access the page requested.', 'warning', false, 'loginFlash') ;
		}
		// If successfully, then this section of code is not reached.
		if (! empty($message)) $this->getView()->displayMessage($message) ;
		return false ;
	}

	/**
	 * get Role Category
	 *
	 * Returns the category of the specified role
	 * @version	3rd August 2016
	 * @since	Copy from functions.php
	 * @param	integer		$roleID  Role ID
	 * @return	mixed		false or Category
	 */
	public function getRoleCategory($roleID) 
	{
		$rObj = new role($this->view, $roleID);

		if ($rObj->getSuccess() && ! $rObj->isEmpty('category')) 
			return $rObj->getField('category') ;
		return false ;
	}

	/**
	 * get Role List
	 *
	 * Get list of user roles from database, and convert to array
	 * @version	4th May 2016
	 * @since	Copy from functions.php
	 * @param	integer		$gibbonRoleIDAll  Role ID
	 * @return	mixed		false or Category
	 */
	public function getRoleList($gibbonRoleIDAll)
	{
		$pdo = $this->getPDO();	
	
		$output=array() ;
	
		//Tokenise list of roles
		$roles=explode(',', $gibbonRoleIDAll) ;
	
		//Check that roles exist
		$count=0 ;
		for ($i=0; $i<count($roles); $i++) {
			if ($row = $pdo->getRecordFromID(array("gibbonRoleID"=>$roles[$i]))) {
				$output[$count][0]=$row->gibbonRoleID ;
				$output[$count][1]=$row->name ;
				$count++ ;
			}
		}
	
		//Return list of roles
		return $output ;
	}

	/**
	 * crypto Random Secure
	 *
	 * @version	18th June 2016
	 * @since	18th June 2016
	 * @param	integer		$min	
	 * @param	integer		$max	
	 * @return	integer	
	 */
	private function crypto_rand_secure($min, $max) 
	{
		$range = $max - $min;
		if ($range < 0) return $min; // not so random...
		$log = log($range, 2);
		$bytes = (int) ($log / 8) + 1; // length in bytes
		$bits = (int) $log + 1; // length in bits
		$filter = (int) (1 << $bits) - 1; // set all lower bits to 1
		do {
			$rnd = hexdec(bin2hex(openssl_random_pseudo_bytes($bytes)));
			$rnd = $rnd & $filter; // discard irrelevant bits
		} while ($rnd >= $range);
		return $min + $rnd;
	}

	/**
	 * generate Token
	 *
	 * @version	20th July 2016
	 * @since	18th June 2016
	 * @param	integer		$personID	
	 * @param	integer		$length	
	 * @return	string		
	 */
	public function generateToken($length = 32)
	{
		$token = "";
		$codeAlphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
		$codeAlphabet.= "abcdefghijklmnopqrstuvwxyz";
		$codeAlphabet.= "0123456789./";
		for($i=0;$i<$length;$i++)
			$token .= $codeAlphabet[$this->crypto_rand_secure(0,strlen($codeAlphabet))];

		return $token ;
	}

	/**
	 * get Salt
	 *
	 * @version	22nd June 2016
	 * @since	Copy from functions.php
	 * @param	boolean		$new	Generate new Salt.
	 * @return	string		Random String
	 */
	public function getSalt($new = false)
	{
		if (! is_null($this->salt) && ! $new)
			return $this->salt;
		if (version_compare(phpversion(), '5.5.0', '>=')) {
			$this->salt = '';
			return $this->salt;
		}
		$this->salt = $this->generateToken(22); 
		return $this->salt;
	}

	/**
	 * get Highest Group Action
	 *
	 * Looks at the grouped actions accessible to the user in the current module and returns the highest
	 * @version	11th August 2016
	 * @since	Copy from functions.php
	 * @param	string		$address	Address
	 * @return	string	
	 */
	public function getHighestGroupedAction($address = null)
	{
		$address = is_null($address) ? $_GET['q'] : $address ;
		$output = false ;
		$moduleID = $this->view->checkModuleReady($address, $this->view);
		
		$obj = new \Gibbon\Record\action($this->view);
		$data = array("actionName" => "%" . $this->view->getActionName($address) . "%", "roleID" => $this->view->session->get("gibbonRoleIDCurrent"), "moduleID" => $moduleID);
		$sql = "SELECT `gibbonAction`.`name` 
			FROM `gibbonAction`, `gibbonPermission`, `gibbonRole` 
			WHERE `gibbonAction`.`URLList` LIKE :actionName  
				AND `gibbonAction`.`gibbonActionID` = `gibbonPermission`.`gibbonActionID` 
				AND `gibbonPermission`.`gibbonRoleID` = `gibbonRole`.`gibbonRoleID` 
				AND `gibbonPermission`.`gibbonRoleID` = :roleID 
				AND `gibbonAction`.`gibbonModuleID` = :moduleID 
			ORDER BY `precedence` DESC" ;
		$result = $obj->findAll($sql, $data);
		if (count($result) > 0) {
			$row = reset($result);
			$output = $row->getField('name');
		}
		return $output ;
	}

	/**
	 * get Password Policy
	 *
	 * Looks at the grouped actions accessible to the user in the current module and returns the highest
	 * @version	20th July 2016
	 * @since	Copy from functions.php
	 * @return	string		Random String
	 */
	public function getPasswordPolicy()
	{
		$output = false ;
		$this->config->injectView($this->getView());
		
		$alpha = $this->config->getSettingByScope( "System", "passwordPolicyAlpha" ) ;
		$numeric = $this->config->getSettingByScope( "System", "passwordPolicyNumeric" ) ;
		$punctuation = $this->config->getSettingByScope( "System", "passwordPolicyNonAlphaNumeric" ) ;
		$minLength = $this->config->getSettingByScope( "System", "passwordPolicyMinLength" ) ;
	
		if ( ! $alpha || ! $numeric || ! $punctuation || ! $minLength ) {
			$output.=  $this->getView()->__( "An error occurred.") ;
		}
		else if ($alpha!="N" OR $numeric!="N" OR $punctuation!="N" OR $minLength>=0) {
			$output.=  $this->getView()->__( "The password policy stipulates that passwords must:") . "<br/>" ;
			$output.="<ul>" ;
				if ($alpha=="Y") {
					$output.="<li>" .  $this->getView()->__( 'Contain at least one lowercase letter, and one uppercase letter.') . "</li>" ;
				}
				if ($numeric=="Y") {
					$output.="<li>" .  $this->getView()->__( 'Contain at least one number.') . "</li>" ;
				}
				if ($punctuation=="Y") {
					$output.="<li>" .  $this->getView()->__( 'Contain at least one non-alphanumeric character (e.g. a punctuation mark or space).') . "</li>" ;
				}
				if ($minLength>=0) {
					$output.="<li>" . sprintf( $this->getView()->__( 'Must be at least %1$s characters in length.'), $minLength) . "</li>" ;
				}
			$output.="</ul>" ;
		}
	
		return $output ;
	}

	/**
	 * get Password Hash
	 * 
	 * @version	31st July 2016
	 * @since	24th April 2016
	 * @param	string		Password
	 * @param	string		Salt
	 * @return	string		Hash
	 */
	public function getPasswordHash($pw, $salt = '')
	{
		$pw = trim($pw);
		$this->password = $pw;
		$this->salt = trim($salt) ;
		if (phpversion() < '5.5.0')
		{
			if (empty($this->salt))
				$this->salt = $this->getSalt(true);
			$this->hash = hash("sha256", $this->salt.$pw) ;
		}
		else
		{
			$this->salt = '';
			$options = array('cost' => $this->getSecurityCost());
			$this->hash = password_hash($pw, PASSWORD_DEFAULT, $options);
		}
		return $this->hash ;
	}

	/**
	 * verify Password
	 * 
	 * @version	12th October 2016
	 * @since	24th April 2016
	 * @param	string		Password
	 * @param	string		Hash
	 * @param	string		Salt
	 * @param	string		Old Password md5
	 * @return	boolean
	 */
	public function verifyPassword($pw, $hash, $salt, $md5 = '')
	{
		// Check if very Old Version
		$pw = trim($pw);
		if ($md5 === md5($pw))
		{
			$this->salt = $this->getSalt(true) ;
			$this->hash = $this->getPasswordHash($pw, $this->salt);
			$this->updatePassword($this->hash, $this->salt);
			return true ;
		}

		$this->password = trim($pw) ;
		$this->salt = trim($salt) ;
		$this->hash = trim($hash) ;
		// Now check sha256 on php versions < 5.5
		if (version_compare(PHP_VERSION, '5.5.00', '<'))
		{
			return (hash("sha256", $this->salt.$this->password) === $this->hash) ;
		}
		elseif (version_compare(PHP_VERSION, '5.5.00', '>='))
		{
			if (! empty($this->salt) && hash("sha256", $this->salt.$this->password) === $this->hash)
			{
				$this->hash = $this->getPasswordHash($this->password);
				$this->updatePassword($this->hash, '');
			}
			if (empty($this->salt) && password_verify($this->password, $this->hash) )
			{
				if (password_needs_rehash($this->hash, PASSWORD_DEFAULT, array('cost' => $this->getSecurityCost())))
				{
					$hash = $this->getPasswordHash($this->password);
					$this->updatePassword($hash, '');
				}
				return true ;
			}
		}
		return false ;

	}

	/**
	 * does Password Match Policy
	 * 
	 * @version	20th June 2016
	 * @since	24th April 2016
	 * @param	string		Password
	 * @return	boolean
	 */
	public function doesPasswordMatchPolicy($passwordNew)
	{
		$config = $this->getConfig();
		$alpha= $config->getSettingByScope( "System", "passwordPolicyAlpha" ) ;
		$numeric= $config->getSettingByScope( "System", "passwordPolicyNumeric" ) ;
		$punctuation= $config->getSettingByScope( "System", "passwordPolicyNonAlphaNumeric" ) ;
		$minLength= $config->getSettingByScope( "System", "passwordPolicyMinLength" ) ;
	
		if (! $alpha OR ! $numeric OR ! $punctuation OR !$minLength) {
			$output = false ;
		}
		else {
			if ($alpha == "Y") {
				if (! preg_match('`[A-Z]`',$passwordNew) || ! preg_match('`[a-z]`',$passwordNew)) {
					return false ;
				}
			}
			if ($numeric == "Y") {
				if (! preg_match('`[0-9]`',$passwordNew)) {
					return false ;
				}
			}
			if ($punctuation=="Y") {
				if (! preg_match('/[^a-zA-Z0-9]/',$passwordNew) AND false !== strpos($passwordNew, " ")) {
					return false ;
				}
			}
			if ($minLength>0) {
				if (mb_strlen($passwordNew) < $minLength) {
					return false ;
				}
			}
		}
	
		return true ;
	}

	/**
	 * update Password
	 * 
	 * @version	8th September 2016
	 * @since	25th April 2016
	 * @param	string		Hash
	 * @param	string		Salt
	 * @return	boolean
	 */
	public function updatePassword($hash, $salt)
	{
		$username = isset($_POST['username']) ? $_POST['username'] : '' ;
		$username = empty($username) ? $this->getSession()->get('username') : $username ;
		if (empty($username))
			return false ;
		$person = $this->getPerson();
		$x = $person->findOneBy(array('username' => $username));
		if (! $person->getSuccess() || $person->rowCount() !== 1)
			return false;
		$person->setField("passwordStrong", $hash);
		$person->setField("passwordStrongSalt", $salt);
		$person->setField("password", '');
		$x = $person->writeRecord(array("passwordStrong", "passwordStrongSalt", "password"));
		return $x;
	}

	/**
	 * randomPassword
	 * 
	 * @version	26th April 2016
	 * @since	copied from functions.php
	 * @param	integer		Password Length
	 * @return	string		Password
	 */
	public function randomPassword($length)
	{
	  if (!(is_int($length))) {
		$length=8 ;
	  }
	  else if ($length>255) {
		$length=255;
	  }
	
	  $charList="abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789![]{}()%&*$#^<>~@|";
	  $len = strlen($charList) ;
	  $password='' ;
	
		//Generate the password
		for ($i=0;$i<$length;$i++)
			$password=$password . substr($charList, rand(1,$len),1);
	
		return $password;
	}
	
	/**
	 * get Security
	 *
	 * @version	10th May 2016
	 * @since	10th May 2016
	 * @return	array
	 */
	public function getSecurity()
	{
		if (is_array($this->security) && ! empty($this->security))
			return $this->security ;
		$this->security = null;
		if (file_exists($this->getSession()->get('absolutePath').'/config/security.yml'))
			$this->security = Yaml::parse(file_get_contents($this->getSession()->get('absolutePath').'/config/security.yml'));
		return $this->security ;
	}

	/**
	 * get Security Cost
	 *
	 * @version	10th May 2016
	 * @since	10th May 2016
	 * @return	integer
	 */
	public function getSecurityCost()
	{
		$this->getSecurity();
		if (isset($this->security['cost']) && intval($this->security['cost']) >= 12)
			return intval($this->security['cost']);
		return 12 ;
	}

	/**
	 * clear Tokens
	 *
	 * @version	20th June 2016
	 * @since	20th June 2016
	 * @param	integer		$personID	
	 * @return	boolean
	 */
	public function clearTokens($personID)
	{
		$pr = $this->getPasswordReset();
		$time = strtotime('-1 day');
		$data = array('gibbonPersonID' => intval($personID), 'requestTime' => $time);
		$sql = "DELETE FROM `gibbonPasswordReset` WHERE `gibbonPersonID` = :gibbonPersonID OR `requestTime` < :requestTime";
		$pr->executeQuery($data, $sql);
		return $pr->getSuccess();
	}
	
	/**
	 * get Password Reset
	 *
	 * @version	10th May 2016
	 * @since	10th May 2016
	 * @param	integer		$id		Person ID
	 * @return	Gibbon\Record\person
	 */
	public function getPasswordReset($id = null)
	{
		if ($this->passwordReset instanceof passwordReset)
			return $this->person;
		$this->passwordReset = new passwordReset(new view('default.blank'), $id);
		return $this->passwordReset;
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
		$this->config = $view->config;
	}

	/**
	 * Constructor
	 *
	 * @version	22nd June 2016
	 * @since	22nd June 2016
	 * @param	Gibbon\view		$view
	 * @return	void
	 */
	public function __construct(view $view)
	{
		$this->injectView($view);
	}
}
