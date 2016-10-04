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
use stdClass ;
use Gibbon\Record\module ;

/**
 * Main Menu Class
 *
 * @version	4th October 2016
 * @since	22nd April 2016
 * @author	Ross Parker
 * @package	Gibbon
 * @subpackage	Menu
 */
class main extends menu
{
	/**
	 * Construct and store main menu in session
	 *
	 * @version 4th October 2016
	 * @since	Moved from /functions.php
	 * @return	HTML	Menu
	 */
	public function setMenu()
	{
		$el = $this->session->get('display.menu.main');
		if (empty($el['refresh']) || --$el['refresh'] < 1 || (isset($el['theme']) && $el['theme'] != $this->session->get('theme.Name')) || empty($el['content'])) {

			$this->session->clear('display.studentFastFinder');	
			$menu="" ;
	
			if ($this->session->isEmpty("gibbonRoleIDCurrent")) {
				$menu .= $this->view->renderReturn('menu.main.start');
			}
			else {
				$mObj = $this->view->getRecord('module') ;
				$data=array("gibbonRoleID"=>$this->session->get("gibbonRoleIDCurrent"));
				$sql="SELECT DISTINCT gibbonModule.name, gibbonModule.category, gibbonModule.entryURL 
					FROM `gibbonModule`, gibbonAction, gibbonPermission 
					WHERE (active='Y') 
						AND (gibbonModule.gibbonModuleID=gibbonAction.gibbonModuleID) 
						AND (gibbonAction.gibbonActionID=gibbonPermission.gibbonActionID) 
						AND (gibbonPermission.gibbonRoleID=:gibbonRoleID) 
					ORDER BY (gibbonModule.category='Other') ASC, category, name";
				$raw = $mObj->findAll($sql, $data);
				$order = $this->config->getSettingByScope('System', 'mainMenuCategories');
				if (empty($order))
				{
					$order = $mObj->getCategories();
					$this->config->setSettingByScope('mainMenuCategories', $order, 'System');
				}
				$result = array();
				foreach($order as $cat)
				{
					$found = false;
					foreach($raw as $w)
					{
						if ($cat == $w->getField('category'))
						{
							$found = true;
							$result[] = $w->returnRecord();
						}
						if ($found && $cat != $w->getField('category'))
							break;
					}
				}
		
				if (! $this->pdo->getQuerySuccess()) {
					$menu .= $this->view->insertMessage($this->pdo->getError());
					$menu .= $this->view->renderReturn('menu.main.start');
				}
				if (count($result) >= 1) {
					$el = new stdClass();
					$el->doNotClose = true;
					$menu .= $this->view->renderReturn('menu.main.start', $el);
	
					$el = new stdClass();
					$el->count = 0;
					$el->currentCategory="" ;
					$el->lastCategory="" ;
					foreach($result as $w) {
						$row = (array) $w;
						$el->currentCategory = $row["category"] ;
						$el->name = $row['name'];
						$el->entryURL = $row["entryURL"] ;

						if (! $this->view->getSecurity()->isActionAccessible("/modules/" . $row["name"] . "/" . $el->entryURL, NULL, '') && $el->entryURL != "index.php") {
							$dataEntry=array("gibbonRoleID"=>$this->session->get("gibbonRoleIDCurrent"),"name"=>$row["name"]);
							$sqlEntry="SELECT DISTINCT gibbonAction.entryURL 
								FROM gibbonModule, gibbonAction, gibbonPermission 
								WHERE (active='Y') 
									AND (gibbonModule.gibbonModuleID=gibbonAction.gibbonModuleID) 
									AND (gibbonAction.gibbonActionID=gibbonPermission.gibbonActionID) 
									AND (gibbonPermission.gibbonRoleID=:gibbonRoleID) 
									AND gibbonModule.name=:name 
								ORDER BY gibbonAction.name";
							$resultEntry = $mObj->executeQuery($dataEntry, $sqlEntry);
							if ($resultEntry->rowCount()>0) {
								$el->entryURL = $resultEntry->fetchColumn() ;
							}
						}
						$menu .= $this->view->renderReturn('menu.main.member', $el);
						$el->lastCategory = $el->currentCategory ;
						$el->count++ ;
					}
					$menu .= $this->view->renderReturn('menu.main.end', $el);
				}
			}
			$this->session->set('display.menu.main.refresh', $this->view->getConfig()->get('caching', 15));
			if (empty($el->count) || $el->count < 1)
				$this->session->set('display.menu.main.refresh', 0);
			$this->session->set('display.menu.main.content', $menu);
			$this->session->set('display.menu.main.theme', $this->session->get('theme.Name'));
			$this->menu = $menu ;
		}
		else
		{
			$this->session->plus('display.menu.main.refresh', -1);
			$this->menu = $this->session->get('display.menu.main.content');
		}
		return $this->menu ;
	}
}
