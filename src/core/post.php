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

use Gibbon\core\logger ;
use Gibbon\core\view ;

/**
 * Post Manager
 *
 * @version	26th August 2016
 * @since	21st April 2016
 * @author	Craig Rayner
 * @package	Gibbon
 * @subpackage	Core
 */
class post extends view
{
	/**
	 * string
	 */
	private $action;

	/**
	 * string
	 */
	private $token;

	/**
	 * array
	 */
	protected $post;

	/**
	 * string
	 */
	private $address;

	/**
	 * Constructor
	 *
	 * @version	26th August 2016
	 * @since	21st April 2016
	 * @param	string	CSV Data
	 * @return	string	CSV Data
	 */
	public function __construct( sqlConnection $pdo = NULL, session $session, config $config)
	{
		if (empty($_POST))
			return ;
		$this->pdo = $pdo;
		$this->session = $session;
		$this->config = $config;
		$this->post = $_POST;
		$this->address = isset($_POST['address']) ? $_POST['address'] : '';
		$this->action = empty($_POST['action']) ?  null : $_POST['action'] ;
		$this->token = empty($_POST['_token']) ? null : $_POST['_token'];
		if (! (isset($_POST['absoluteAction']) && $_POST['absoluteAction']))
			$this->action = str_replace('//', '/', GIBBON_ROOT . 'src/' . str_replace(array('//', '\\', 'src/', GIBBON_ROOT), array('/', '/', '', ''), $this->action));
		if ($this->action ==  GIBBON_ROOT . 'src/')
			$_POST['absoluteAction'] = GIBBON_ROOT . 'index.php';
		$this->testToken();
	}

	/**
	 * test Token
	 *
	 * @version	16th July 2016
	 * @since	22nd April 2016
	 * @return	void
	 */
	public function testToken()
	{
		$installType = $this->config->get('installType');

		if  (empty($this->token))
		{
			if ($installType !== 'Production') {
				$this->dump($_POST);
				$this->dump($_SERVER, true, true);
			}
			throw new Exception(  $this->__('The submitted form is not valid!'), 28000 + __LINE__);
		}
		if  (empty($this->action))
		{
			if ($installType !== 'Production') {
				$this->dump($_POST);
				$this->dump($_SERVER, true, true);
			}
			throw new Exception(  $this->__('The submitted form is not valid!'), 28000 + __LINE__);
		}
		if (md5($this->config->get('guid') . $this->action) !== $this->token && $this->token != 'This is an old script!')
		{
			if ($installType !== 'Production') {
				$this->dump('Token validation failed');
				$this->dump(array(md5($this->config->get('guid') . $this->action), $this->config->get('guid')));
				$this->dump($_POST);
				$this->dump($_SERVER, true, true);
			}
			throw new Exception(  $this->__('The submitted form is not valid!'), 28000 + __LINE__);
		}
		if (! file_exists($this->action))
		{
			parent::__construct('default.error', array(), $this->session, $this->config, $this->pdo);
			die();
		}
		else
		{
			if ($_POST['_token'] == 'This is an old script!')
				logger::__('This is an old script!', 'Warning', 'Deprecated', $_POST);
			$set = new \stdClass();
			$set->action = $this->action;
			$set->post = $this->post;
			unset($_POST['divert'], $_POST['address'], $_POST['_token'], $_POST['action']);
			if (isset($_POST['keepAddress']) && $_POST['keepAddress'])
			{
				$_POST['address'] = $this->address;
				unset($_POST['keepAddress']);
			}
			$this->checkBoolean();
			if (! isset($this->post['divert']))
				parent::__construct('home.html', $set, $this->session, $this->config, $this->pdo);
			else
				parent::__construct('post.inject', $set, $this->session, $this->config, $this->pdo);
			die();
		}
		if ($installType !== 'Production') {
			$this->dump($_POST);
			$this->dump($_SERVER, true, true);
		}
		throw new Exception( $this->__('Post to this system must be correctly formatted.'), 28000 + __LINE__);
	}

	/**
	 * Check Boolean
	 *
	 * @version	16th July 2016
	 * @since	16th July 2016
	 * @return	void
	 */
	protected function checkBoolean()
	{
		if (! isset($_POST['boolean']))
			return ;
		if (! is_array($_POST['boolean']))
			return ;
		foreach($_POST['boolean'] as $name=>$value)
		{
			if (strpos($name, '['))
			{
				$this->manageBooleanPost($name, $value);
			}
			else
				if (empty($_POST[$name]))
					$_POST[$name] = $value;
		}
		unset($_POST['boolean']);
	}

	/**
	 * Manage Boolean Post
	 *
	 * @version	16th July 2016
	 * @since	16th July 2016
	 * @return	void
	 */
	protected function manageBooleanPost($name, $value)
	{
		$first = substr($name, 0, strpos($name, '['));
		$second = str_replace($first.'[', '', $name);
		if (! is_array($value))
		{
			if (empty($_POST[$first][$second]))
				$_POST[$first][$second] = $value;
			return ;
		}
		$third = key($value);
		$value = reset($value);
		if (! is_array($value))
		{
			if (empty($_POST[$first][$second][$third]))
				$_POST[$first][$second][$third] = $value;
			return ;
		}
		foreach($value as $forth=>$v4)
		{
			if (! is_array($v4))
			{
				if (empty($_POST[$first][$second][$third][$forth]))
					$_POST[$first][$second][$third][$forth] = $v4;
			}
			else
			{
				foreach($v4 as $fifth=>$v5)
				{
					if (empty($_POST[$first][$second][$third][$forth][$fifth]))
						$_POST[$first][$second][$third][$forth][$fifth] = $v5;
				}
			}
		}
	}
}