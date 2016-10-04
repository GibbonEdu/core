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

/**
 * String Functions
 *
 * @version	1st October 2016
 * @since	1st October 2016
 * @author	Craig Rayner
 * @package		Gibbon
 * @subpackage	Trait
 */
trait arrayFunctions
{
	/**
	 * multiDimension Array Sort
	 *
	 * @version	1st October 2016
	 * @since	copied from functions.php
	 * @param	array		$array Data to be sorted
	 * @param	string		$id id Name
	 * @param	boolean		$sort_ascending	Ascending
	 * @return	array		Sorted Array	
	 */
	public function msort($array, $id="id", $sort_ascending=true)
	{

		$temp_array=array();
		while(count($array)>0) {
			$lowest_id=0;
			$index=0;
			foreach ($array as $item) {
				if (isset($item[$id])) {
					if ($array[$lowest_id][$id]) {
						if (strtolower($item[$id]) < strtolower($array[$lowest_id][$id])) {
							$lowest_id=$index;
						}
					}
				}
				$index++;
			}
			$temp_array[]=$array[$lowest_id];
			$array=array_merge(array_slice($array, 0,$lowest_id), array_slice($array, $lowest_id+1));
		}
		if ($sort_ascending) {
			return $temp_array;
		} else {
			return array_reverse($temp_array);
		}
	}
}
