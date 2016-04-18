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
 * CSV Generator
 *
 * @version	15th April 2016
 * @since	15th April 2016
 * @author	Craig Rayner
 */
class session
{
	/**
	 * string
	 */
	private	$guid ;

	/**
	 * string
	 */
	private	$base ;

	/**
	 * Construct
	 *
	 * @version	15th April 2016
	 * @since	15th April 2016
	 * @return	void
	 */
	public function __construct()
	{
		@session_start();
		$config = new config();
		$this->guid = $config->get('guid');
	}

	/**
	 * get Value
	 *
	 * @version	15th April 2016
	 * @since	15th April 2016
	 * @param	string	Session Value Name
	 * @return	mixed
	 */
	public function get($name)
	{
		$steps = explode(',', $name);
		foreach($steps as $q=>$w)
			$steps[$q] = trim($w);
		if (count($steps) === 1)
		{
			if (isset($_SESSION[$this->guid][$name]))
				return $_SESSION[$this->guid][$name] ;
		}
		else
			return $this->getSub($steps, $_SESSION[$this->guid][$steps[0]]);
		return NULL ;
	}

	/**
	 * set Value
	 *
	 * @version	15th April 2016
	 * @since	15th April 2016
	 * @param	string	Session Value Name
	 * @param	mixed	Session Value
	 * @return	object	Gibbon\session
	 */
	public function set($name, $value)
	{
		$this->base = NULL;
		$steps = explode(',', $name);
		foreach($steps as $q=>$w)
			$steps[$q] = trim($w);
			
		if (count($steps) > 1)
		{
			$aValue = $this->setSub($steps, $this->get($steps[0]), $value);
			return $this->set($this->base, $aValue);
		}
		else
			$_SESSION[$this->guid][$name] = $value ;
		return $this ;
	}

	/**
	 * get Sub Value
	 *
	 * @version	15th April 2016
	 * @since	15th April 2016
	 * @param	array	Step Names
	 * @param	array	Parent Value
	 * @return	mixed
	 */
	private function getSub($steps, $parent)
	{
		array_shift($steps);
		if (count($steps) === 1)
		{
			if (isset($parent[$steps[0]]))
				return $parent[$steps[0]] ;
		}
		else
			return $this->getSub($steps, $parent[$steps[0]]);
		return NULL ;
	}

	/**
	 * set Sub Value
	 *
	 * @version	15th April 2016
	 * @since	15th April 2016
	 * @param	array	Name Array
	 * @param	array	Current Value
	 * @param	mixed	Session Value
	 * @return	object	Gibbon\session
	 */
	public function setSub($steps, $existing, $value)
	{
		if ($this->base === NULL)
			$base = $this->base = array_shift($steps);
		else
			$base = array_shift($steps);
		if (count($steps) === 1)
			$existing[$steps[0]] = $value; 
		else
			$existing[$steps[0]] = $this->setSub($steps, $existing[$steps[0]], $value);
		return $existing;	
	}
}