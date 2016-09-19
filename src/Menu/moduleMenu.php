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
 * @author	Ross Parker
 * @package	Gibbon
*/
/**
 * Namespace
 */
namespace Gibbon\Menu;

use Gibbon\core\trans ;
use Gibbon\core\security ;
use Gibbon\core\module as helper ;
use Gibbon\Record\module ;
use Gibbon\core\listElement ;

/**
 * Main Menu Class
 *
 * @version	10th August 2016
 * @since	6th July 2016
 * @author	Craig Rayner
 * @package	Gibbon
 * @subpackage	Menu
 */
class moduleMenu extends menu
{
	/**
	 * Construct module Menu
	 *
	 * @version 10th August 2016
	 * @since	Moved from /functions.php
	 * @return	HTML	Menu
	 */
	public function setMenu()
	{
		$this->menu = '';
		//Show Module Menu
		//Check address to see if we are in the module area
		if (substr($this->session->get("address"),0,8) == "/modules") {
			//Get and check the module name
			$moduleID = helper::checkModuleReady($this->session->get("address"), $this->view);
			if ( $moduleID ) {
				$moduleObj = new Module($this->view, $moduleID);
				$RoleIDCurrent = null ;
				if ($this->session->notEmpty("gibbonRoleIDCurrent")) {
					$RoleIDCurrent = $this->session->get("gibbonRoleIDCurrent") ;
				}
				$moduleObj->getActionByRole($RoleIDCurrent);
				$output = '';
	
				if (count($moduleObj->actions) > 0 ) {
	
					$currentCategory="" ;
					$lastCategory="" ;
					$currentName="" ;
					$lastName="" ;
					$count=0;
					$links=0 ;
					$moduleName = $moduleObj->getField("name") ;
					$entryList = array();
					foreach ($moduleObj->actions as $row) {

						//Set active link class
						$moduleEntry = $row->getField("entryURL") ;

						if (! in_array($moduleEntry, $entryList))
						{
							$entryList[] = $moduleEntry ;
							
							$style = strpos($row->getField("URLList"), helper::getActionName($this->session->get("address"))) === 0 ? 'class="active"' : '' ;
		
							$currentCategory = $row->getField("category");
							
							$currentName = strpos($row->getField("name"),"_") > 0 ? trans::__(substr($row->getField("name"), 0, strpos($row->getField("name"),"_"))) : trans::__($row->getField("name")) ;
							
							if ($currentCategory != $lastCategory)
							{
								if (! empty($lastCategory)) $output .= $list->renderList($this->view, true);
								$list = $this->view->startList('ul', 'moduleMenu')
									->addHeader($this->view->h4($currentCategory, array(), true));
							}
							
							if ($currentName != $lastName)
							{
								$list->addListElement('%1$s'.$currentName.'%2$s', array("<a ".$style." href='" . GIBBON_URL . "index.php?q=/modules/" . $moduleName . "/" . $moduleEntry . "'>", "</a>"));
								$links++ ;
							}
							$lastCategory = $currentCategory ;
							$lastName = $currentName ;
						}
					}
					if ($list instanceof listElement) $output .= $list->renderList($this->view, true);
	
					if ($links > 1 || ! $this->view->getSecurity()->isActionAccessible("/modules/".$moduleName."/".$moduleEntry)) $this->menu = $output ;
				}
			}
		}
		
		return $this->menu ;
	}


} 
