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
 */
/**
 */
namespace Gibbon\core;

use Symfony\Component\Yaml\Yaml ;

require GIBBON_ROOT. 'vendor/phpmailer/phpmailer/PHPMailerAutoload.php';

/**
 * Security Manager
 *
 * Provides Security Methods for Gibbon
 * @version	19th June 2016
 * @since	19th June 2016
 * @author	Craig Rayner
 * @package	Gibbon
 */
class mailer extends \phpmailer
{
	/**
	 * Gibbon\sqlConnection
	 */
	private $pdo;

	/**
	 * get pdo
	 *
	 * @version	19th June 2016
	 * @since	19th June 2016
	 * @return	Gibbon\sqlConnection
	 */
	public function getPdo()
	{
		if (self::$pdo instanceof sqlConnection)
			return self::$pdo;
		self::$pdo = new sqlConnection();
		return self::$pdo;
	}

	/**
	 * Gibbon\session
	 */
	private $session;
	
	/**
	 * get Session
	 *
	 * @version	19th June 2016
	 * @since	19th June 2016
	 * @return	Gibbon\sqlConnection
	 */
	public function getSession()
	{
		if (self::$session instanceof session)
			return self::$session;
		self::$session = new session();
		return self::$session;
	}

	/**
	 * Gibbon\config
	 */
	private $config;
	
	/**
	 * get Session
	 *
	 * @version	19th June 2016
	 * @since	19th June 2016
	 * @return	Gibbon\config
	 */
	public function getConfig()
	{
		if (self::$config instanceof config)
			return self::$config;
		self::$config = new config();
		return self::$config ;
	}
	
	
	/**
	 * Constructor
	 *
	 * @version	19th June 2016
	 * @since	19th June 2016
	 * @return	void
	 */
	public function __construct()
	{
		parent::__construct();
		
		$this->CharSet = 'UTF-8';
		$this->Encoding = 'base64';
		$this->IsHTML(true);
		$this->WordWrap = 65 ;
		
		if (file_exists(GIBBON_ROOT . 'config/local/mailer.yml'))
		{
			$settings = Yaml::parse(file_get_contents(GIBBON_ROOT . 'config/local/mailer.yml'));
			foreach($settings as $setting=>$value)
			{
				switch ($setting) {
					case 'IsSMTP':
						$this->IsSMTP() ;
						break ;
					case 'IsHTML':
						$this->IsHTML() ;
						break ;
					default:
						$this->$setting = $value;
				}
			}
		}
		//$this->IsSMTP();                                      // set mailer to use SMTP

		//$this->Host = "smtp1.example.com;smtp2.example.com";  // specify main and backup server
		
		//$this->SMTPAuth = true;     // turn on SMTP authentication
		
		//$this->Username = "jswan";  // SMTP username
		
		//$this->Password = "secret"; // SMTP password

	}
	
}
