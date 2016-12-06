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
 * Responsibilities:
 * 		- User session
 * 		- Persistance ($_SESSION)
 * 		- Caching
 *
 * @version	v13
 * @since	v12
 */
class Session
{
	/**
	 * string
	 */
	private	$guid ;

	/**
	 * Construct
	 */
	public function __construct( core $gibbon = null )
	{
		//Prevent breakage of back button on POST pages
		ini_set('session.cache_limiter', 'private');
		session_cache_limiter(false);

		// Start the session (this should be the first time called)
		if (PHP_SESSION_ACTIVE !== session_status())
			session_start();

		$this->guid = $gibbon->guid();
	}

	/**
	 * Return the guid string
	 *
	 * @return	string
	 */
	public function guid() {
		return $this->guid;
	}

	/**
	 * Get Session Value
	 *
	 * @param	string	Session Value Name
	 * @param	mixed	default Define a value to return if the variable is empty
	 * 
	 * @return	mixed
	 */
	public function get($name, $default = null)
	{
		return (isset($_SESSION[$this->guid][$name]))? $_SESSION[$this->guid][$name] : $default;
	}

	/**
	 * Set Session Value
	 *
	 * @param	string	Session Value Name
	 * @param	mixed	Session Value
	 * 
	 * @return	object	Gibbon\session
	 */
	public function set($name, $value)
	{
		$_SESSION[$this->guid][$name] = $value ;

		return $this;
	}

	/**
	 * Set Multiple Session Values
	 *
	 * @param	array	Array of name => value pairs
	 * 
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