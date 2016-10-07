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
 * @version	6th October 2016
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

	/**
	 * date Convert Back
	 *
	 * Converts date from YYYY-MM-DD to language-specific format.
	 * @version	22nd April 2016
	 * @since	22nd April 2016
	 * @param	string		$date Date
	 * @return	string
	 */
	public function dateConvertBack($date)
	{
		$output = false; ;
		if (! empty($date)) {
			$session = new session();
			$timestamp = strtotime($date) ;
			if (! $session->isEmpty("i18n.dateFormatPHP") ) 
				$output = date($session->get("i18n.dateFormatPHP"), $timestamp) ;
			else 
				$output = date("d/m/Y", $timestamp) ;
		}
		return $output ;
	}

	/**
	 * date Convert to Timestamp
	 *
	 * Converts a specified date (YYYY-MM-DD) into a UNIX timestamp
	 * @version	1st October 2016
	 * @since	21st April 2016
	 * @param	string		$date Date
	 * @return	mixed		Timestamp or false
	 */
	public function dateConvertToTimestamp($date)
	{
		list($dateYear, $dateMonth, $dateDay) = explode('-', $date);
		$timestamp=mktime(0, 0, 0, $dateMonth, $dateDay, $dateYear);
		return $timestamp ;
	}

	/**
	 * get Week Number
	 *
	 * @version	6th October 2016
	 * @since	21st April 2016
	 * @param	string		$date
	 * @return	mixed		ModuleID or false
	 */
	public function getWeekNumber($date)
	{
		$week=0 ;
		$session = new session();
		
		$data=array("gibbonSchoolYearID"=>$session->get("gibbonSchoolYearID"));
		$sql="SELECT * FROM gibbonSchoolYearTerm WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY sequenceNumber" ;
		$weeks = $this->getRecord('schoolYearTerm')->findAll($sql, $data);
		foreach($weeks as $rowWeek) {
			$firstDayStamp = strtotime($rowWeek->getField('firstDay')) ;
			$lastDayStamp = strtotime($rowWeek->getField('lastDay')) ;
			while (date("D",$firstDayStamp) != "Mon") {
				$firstDayStamp = $firstDayStamp  -86400 ;
			}
			$head = $firstDayStamp ;
			while ($head <= ($date) && $head < ($lastDayStamp+86399)) {
				$head = $head + (86400 * 7) ;
				$week++ ;
			}
			if ($head < ($lastDayStamp + 86399)) {
				break ;
			}
		}
	
		if ($week <= 0) {
			return false ;
		}
		else {
			return $week ;
		}
	}
}
