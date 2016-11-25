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
 * Main menu building Class
 *
 * @version	24rd November 2016
 * @since	22nd April 2016
 * @author	Ross Parker
 */
class MenuMain
{
	/**
	 * Gibbon\sqlConnection
	 */
	private $pdo;

	/**
	 * Gibbon\session
	 */
	private $session;

	/**
	 * Construct
	 *
	 * @version 23rd November 2016
	 * @since	22nd April 2016
	 */
	public function __construct( Core $gibbon, sqlConnection $pdo )
	{
		$this->pdo = $pdo;
		$this->session = $gibbon->session;
	}

	/**
	 * Construct and store main menu in session
	 *
	 * @version 24th November 2016
	 * @since	Old
	 */
	public function setMenu()
	{
		$menu='';

		$address = $this->session->get('address');
		$absoluteURL = $this->session->get('absoluteURL');

		if ($this->session->get('gibbonRoleIDCurrent') == null) {
			$menu .= "<ul id='nav'>";
			$menu .= "<li class='active'><a href='" . $absoluteURL . "/index.php'>" . __('Home') . "</a></li>";
			$menu .= "</ul>";
		}
		else {
			$mainMenuCategoryOrder = getSettingByScope($this->pdo->getConnection(), 'System', 'mainMenuCategoryOrder');

			$data = array('gibbonRoleID' => $this->session->get('gibbonRoleIDCurrent'), 'menuOrder' => $mainMenuCategoryOrder );
			$sql = "SELECT gibbonModule.category, gibbonModule.name, gibbonModule.type, gibbonModule.entryURL, gibbonAction.entryURL as alternateEntryURL 
					FROM gibbonModule 
					JOIN gibbonAction ON (gibbonAction.gibbonModuleID=gibbonModule.gibbonModuleID) 
					JOIN gibbonPermission ON (gibbonPermission.gibbonActionID=gibbonAction.gibbonActionID) 
					WHERE gibbonModule.active='Y' 
					AND gibbonAction.menuShow='Y' 
					AND gibbonPermission.gibbonRoleID=:gibbonRoleID 
					GROUP BY gibbonModule.name 
					ORDER BY FIND_IN_SET(gibbonModule.category, :menuOrder), gibbonModule.category, gibbonModule.name, gibbonAction.name";

			$result = $this->pdo->executeQuery($data, $sql);
			if (! $this->pdo->getQuerySuccess()) {
				$menu .= "<div class='error'>" . $this->pdo->getError() . "</div>";
			}

			$menu .= "<ul id='nav'>";
			$menu .= "<li><a href='" . $absoluteURL . "/index.php'>" . __('Home') . "</a></li>";

			// Output menu items, if they exist
			if ($result->rowCount() > 0) {
				
				// Grab the result set, grouped by module category
				$menuData = $result->fetchAll(\PDO::FETCH_GROUP);

				foreach ($menuData as $currentCategory => $menuItems) {

					$moduleDomain = ($menuItems[0]['type'] == 'Core')? null : $menuItems[0]['name'];

					// Display the top level category name
					$menu .= "<li><a href='#'>" . __($currentCategory, $moduleDomain) . "</a>";
					$menu .= "<ul>";

					foreach ($menuItems as $row) {
						$moduleDomain = ($row['type'] == 'Core')? null : $row['name'];

						$entryURL=$row['entryURL'];

						// Use the alternate entryURL if the main one is inaccessable by this role
						if (isActionAccessible($this->session->guid(), $this->pdo->getConnection(), "/modules/" . $row['name'] . '/' . $entryURL)==FALSE AND $entryURL!='index.php') {
							$entryURL=$row['alternateEntryURL'];
						}

						$menu .= "<li><a href='" . $absoluteURL . "/index.php?q=/modules/" . $row['name'] . "/" . $entryURL . "'>" . __($row['name'], $moduleDomain) . "</a></li>";
					}

					$menu .= "</ul>";
				}
			}

			$menu .= "</ul>";
		}

		$this->session->set('mainMenu', $menu);
	}

	/**
	 * Return the module menu (stored in session)
	 *
	 * @version 10th November 2016
	 * @since	Old
	 * @return	string
	 */
	public function getMenu()
	{
		return $this->session->get('mainMenu');
	}
}
?>
