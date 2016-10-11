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

namespace Gibbon\People;

use Gibbon\core\session ;

/**
 * Employee
 *
 * @version	11th October 2016
 * @since	1st October 2016
 * @author	Craig Rayner
 * @package	Gibbon
 * @subpackage	People
 */
trait user 
{

	/**
	 * get User Photo
	 *
	 * Gets a given user photo, or a blank if not available
	 * @version	11th October 2016
	 * @since	copied from functions.php
	 * @param	string		$path	Photo Path
	 * @param	string		$size	
	 * @return	string		HTML
	 */
	public function getUserPhoto($path, $size) {

		$output = "" ;

		$width = $size > '240' ? '240' : $size ;
		$size = $size != '75' ? '240' : '75' ;
		
		$path = empty($path) ? ltrim($this->getField('image_240'), '/') : $path ;
		$path = ! is_file(GIBBON_ROOT.$path) ? "src/themes/" . $this->getSession()->get("theme.Name") . "/img/anonymous_".$size.".jpg" : $path ;
		
		$sizeStyle = $size == '240' ? "style='width: 240px;'" : "style='width: 75px;'" ;
		if (! in_array($width, array('240', '75')))
			$sizeStyle = "style='width: ".$width."px;'" ;

		$output = "<img ".$sizeStyle." class='user' src='" . GIBBON_URL . $path . "'/>" ;

		return $output ;
	}

	/**
	 * get Session
	 *
	 * @version	2nd October 2016	
	 * @since	2nd October 2016
	 * @return	Gibbon\core\session	
	 */
	protected function getSession()
	{
		if (isset($this->view->session) && $this->view->session instanceof session)
			return $this->view->session;
		if (isset($this->session) && $this->session instanceof session)
			return $this->session;
		$this->session = new session();
		return $this->session();
	}

	/**
	 * days Until Next Birthday
	 *
	 * Accepts birthday in mysql date (YYYY-MM-DD) 
	 * @version	2nd October 2016
	 * @since	copied from functions.php
	 * @param	date		$birthday	Birth Date
	 * @return	integer		Days to next Birthday
	 */
	public function daysUntilNextBirthday($birthday)
	{
		if (empty($birthday)) return false ;
		$dtz = new \DateTimeZone('UTC');
		$today = new \DateTime(date('Y-m-d', strtotime('now')), $dtz);
		$birthday = new \DateTime($birthday, $dtz);
		$x = $today->diff($birthday);
		$years = $x->y;
		if ($x->m + $x->d > 0) //  Check if the birthday is today.
			$years++;
		$interval = new \DateInterval('P'.$years.'Y');
		$birthday->add($interval);
		$d = $today->diff($birthday);
		
		return intval($d->days);
	}
}