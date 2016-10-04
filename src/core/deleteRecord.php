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

/**
 * Delete Record
 *
 * @version	20th May 2016
 * @since	20th May 2016
 * @author	Craig Rayner
 * @package	Gibbon
 * @subpackage	Core
 */
class deleteRecord
{
	/**
	 * Constructor
	 *
	 * @version	30th June 2016
	 * @since	20th May 2016
	 * @param	string		$table	Table Name
	 * @param	integer		$id		Identifier
	 * @param	string		$security	Security Page 
	 * @param	string		$failURL	Failure URL
	 * @param	string		$successURL	Success URL
	 * @param	Gibbon\view		$view
	 * @return	void
	 */
	public function __construct($table, $id, $security, $failURL, $successURL, view $view)
	{
		if (is_null($security) || $view->getSecurity()->isActionAccessible($security)) {
			//Proceed!
			if (empty($id)) {
				$view->insertMessage("return.error.1") ;
				$view->redirect($failURL);
			}
			else {
				$table = '\\Gibbon\\Record\\'.$table;
				if (! $Obj = new $table($view, $id)) { 
					$view->insertMessage("return.error.2") ;
					$view->redirect($failURL);
				}
				else {
					//Write to database
					if (! $Obj->deleteRecord($id) ) { 
						$view->insertMessage("return.error.2") ;
						$view->redirect($failURL);
					}
					else
					{
						$view->insertMessage("return.success.0", 'success') ;
						$view->redirect($successURL);
					}
				}
			}
		} else
			$view->insertMessage("You do not have access to this action.") ;
			$view->redirect($failURL);
	}
}