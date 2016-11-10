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

/**
 * Session Class
 *
 * @version	10th November 2016
 * @since	15th April 2016
 */
class session
{
	/**
	 * string
	 */
	private	$guid ;

	/**
	 * Construct
	 *
	 * @version	10th November 2016
	 * @since	15th April 2016
	 * @return	void
	 */
	public function __construct( config $config = null )
	{
		//Prevent breakage of back button on POST pages
		ini_set('session.cache_limiter', 'private');
		session_cache_limiter(false);

		if (PHP_SESSION_ACTIVE !== session_status())
			session_start();

		// Test for config, Backwards compatability
		if (empty($config)) $config = new config();

		$this->guid = $config->get('guid');
	}

	/**
	 * guid 	Return the guid string
	 *
	 * @version	10th November 2016
	 * @since	10th November 2016
	 * @return	string
	 */
	public function guid() {
		return $this->guid;
	}

	/**
	 * get Value
	 *
	 * @version	10th November 2016
	 * @since	15th April 2016
	 * @param	string	Session Value Name
	 * @param	mixed	default Define a value to return if the variable is empty
	 * @return	mixed
	 */
	public function get($name, $default = null)
	{
		return (isset($_SESSION[$this->guid][$name]))? $_SESSION[$this->guid][$name] : $default;
	}

	/**
	 * set Value
	 *
	 * @version	10th November 2016
	 * @since	15th April 2016
	 * @param	string	Session Value Name
	 * @param	mixed	Session Value
	 * @return	object	Gibbon\session
	 */
	public function set($name, $value)
	{
		$_SESSION[$this->guid][$name] = $value ;

		return $this;
	}

	/**
	 * setAll Values
	 *
	 * @version	10th November 2016
	 * @since	10th November 2016
	 * @param	array	Array of name => value pairs
	 * @return	object	Gibbon\session
	 */
	public function setAll( array $values )
	{
		foreach ($values as $name => $value) {
			$this->set($name, $value);
		}

		return $this;
	}

}