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

use Gibbon\core\view ;
use Gibbon\core\logger ;

/**
 * Edit Record
 *
 * @version	17th September 2016
 * @since	21st May 2016
 * @author	Craig Rayner
 * @package	Gibbon
 */
class editRecord
{
	/**
	 * Constructor
	 *
	 * @version	21st May 2016
	 * @since	21st May 2016
	 * @param	string		$table	Table Class Name
	 * @param	integer		$id		Identifier
	 * @param	string		$security	Security Page 
	 * @param	array		$urlGET	$_GET options for URL
	 * @param	Gibbon\view		$view
	 * @return	void
	 */
	public function __construct($table, $id, $security, array $urlGET = array(), view $view)
	{
		$url = GIBBON_URL.'index.php?';
		foreach ($urlGET as $q=>$w)
			$url .= $q.'='.$w.'&';
		$url = rtrim($url, '&');
		$url = rtrim($url, '?');
		if (! $view->getSecurity()->isActionAccessible($security)) {
			$view->insertMessage("return.error.0") ;
			$view->redirect($url);
		}
		else
		{
			if (empty($id)) {
				$view->insertMessage("return.error.1") ;
				$view->redirect($url);
			}
			else {
				$table = '\\Gibbon\\Record\\'.$table;
				$obj = new $table($view);
				if ($id !== 'Add' && ! $obj->find(intval($id))) { 
					$view->insertMessage("return.error.2") ;
					logger::__('Find Record Failure', 'Debug', 'Edit Record '.$table);
					$view->redirect($url);
				}
				else {
					$obj->injectPost();
					if (! $obj->uniqueTest() ) { 
						$view->insertMessage("return.error.3") ;
						logger::__('Unique Test Failure', 'Debug', 'Edit Record '.$table);
						$view->redirect($url);
					}
					else
					{
						if (! $obj->writeRecord()) {
							$view->insertMessage("return.error.2") ;
							logger::__('Write Record Failure', 'Debug', 'Edit Record '.$table, (array)$obj->returnRecord());
							$view->redirect($url);
						}
						else	
						{
							if ($id === 'Add') 
							{
								$url = $view->session->get('absoluteURL').'/index.php?';
								$urlGET[$obj->getIdentifierName()] = $obj->getIdentifier();
								foreach ($urlGET as $q=>$w)
									$url .= $q.'='.$w.'&';
								$url = rtrim($url, '&');
								$url = rtrim($url, '?');
							}
							$view->insertMessage("return.success.0", 'success') ;
							$view->redirect($url);
						}
					}
				}
			}
		}
	}
}