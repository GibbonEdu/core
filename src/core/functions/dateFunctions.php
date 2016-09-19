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
namespace Gibbon\core\functions ;

use DateTime ;
use Gibbon\core\session ;

/**
 * Date Functions
 *
 * @version	19th September 2016
 * @since	19th September 2016
 * @author	Craig Rayner
 * @package		Gibbon
 * @subpackage	Trait
 */
trait dateFunctions
{

	/**
	 * date Convert
	 *
	 * Converts date from language-specific format to YYYY-MM-DD
	 * @version	19th September 2016
	 * @since	21st April 2016
	 * @param	string		$date Date
	 * @return	mixed		Date or false
	 */
	public static function dateConvert($date) {

		$output = false ;
		$session = new session();
		if (! empty($date)) {
			if ($session->get("i18n.dateFormat") == "mm/dd/yyyy") {
				$firstSlashPosition = 2 ;
				$secondSlashPosition = 5 ;
				$output = substr($date,($secondSlashPosition+1)) . "-" . substr($date,0,$firstSlashPosition) . "-" . substr($date,($firstSlashPosition+1),2) ;
			}
			else {
				$output = date('Y-m-d', strtotime(str_replace('/', '-', $date)));
			}
		}
		return $output ;
	}
}
