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

use Gibbon\Contracts\Database\Connection;

/**
 * Main menu building Class
 *
 * @version	24rd November 2016
 * @since	22nd April 2016
 * @author	Ross Parker
 */
class MenuModule
{
	/**
	 * Gibbon\Contracts\Database\Connection
	 */
	private $pdo;

	/**
	 * Gibbon\session
	 */
	private $session;

	/**
	 * Stores the type of module menu
	 */
	private $type;

	/**
	 * Construct
	 *
	 * @version 23rd November 2016
	 * @since	22nd April 2016
	 */
	public function __construct( Core $gibbon, Connection $pdo )
	{
		$this->pdo = $pdo;
		$this->session = $gibbon->session;
	}

	/**
	 * Construct and return the module menu
	 *
	 * @version 24th November 2016
	 * @since	Old
	 * @return	string
	 */
	public function getMenu($type='full')
	{
		$menu="";

		$address = $this->session->get('address');
		$absoluteURL = $this->session->get('absoluteURL');

		//Check address to see if we are in the module area
		if (substr($address,0,8)=='/modules') {

			//Get and check the module name
			$moduleID=checkModuleReady( $address, $this->pdo->getConnection() );
			if ($moduleID!=FALSE) {

				$gibbonRoleIDCurrent= $this->session->get('gibbonRoleIDCurrent');

				$data = array('gibbonModuleID'=>$moduleID, 'gibbonRoleID'=>$gibbonRoleIDCurrent);
				$sql = "SELECT gibbonAction.category, gibbonModule.entryURL AS moduleEntry, gibbonModule.name AS moduleName, gibbonAction.name, gibbonModule.type, gibbonAction.precedence, gibbonAction.entryURL, URLList
						FROM gibbonModule
						JOIN gibbonAction ON (gibbonModule.gibbonModuleID=gibbonAction.gibbonModuleID)
						JOIN gibbonPermission ON (gibbonAction.gibbonActionID=gibbonPermission.gibbonActionID)
						WHERE (gibbonModule.gibbonModuleID=:gibbonModuleID)
						AND (gibbonPermission.gibbonRoleID=:gibbonRoleID)
						AND NOT gibbonAction.entryURL=''
						AND gibbonAction.menuShow='Y'
						ORDER BY gibbonModule.name, gibbonAction.category, gibbonAction.name, precedence DESC";

				$result = $this->pdo->executeQuery($data, $sql);

				if ($result->rowCount()>0) {

					// Grab the result set, grouped by action category
					$menuData = $result->fetchAll(\PDO::FETCH_GROUP);

					if ($type=='full') {

						$currentName="";
						$lastName="";

						$menu .= "<ul class='moduleMenu'>";

						foreach ($menuData as $currentCategory => $menuItems) {

							$moduleDomain = ($menuItems[0]['type'] == 'Core')? null : $menuItems[0]['moduleName'];

							$menu .= "<li>";
							$menu .= "<h4>" . __($currentCategory, $moduleDomain) . "</h4>";
							$menu .= "<ul>";

							foreach ($menuItems as $row) {

								$moduleDomain = ($row['type'] == 'Core')? null : $row['moduleName'];

								//Set active link class
								$style = "";
								$urls = explode(',', $row['URLList']);
								foreach ($urls AS $url) {
									if (trim(getActionName($address)) == trim($url)) {
										$style = "class='active'";
									}
								}

								// Grab the base action name if this is a grouped action
								if (strpos($row['name'],'_') !== false) {
									$currentName=strstr($row['name'], '_', true);
								} else {
									$currentName=$row['name'];
								}

								// Avoid duplicates (esp. from grouped actions)
								if ($currentName!=$lastName) {
									$menu .= "<li><a $style href='" . $absoluteURL . "/index.php?q=/modules/" . $row['moduleName'] . "/" . $row['entryURL'] . "'>" . __($currentName, $moduleDomain) . "</a></li>";
								}
								$lastName=$currentName;
							}

							$menu .= "</ul></li>";
						}
						$menu .= "</ul>";
					}
					else if ($type=='mini') {
						$menu .= "<div class='linkTop'>";

							$currentName="";
							$lastName="";

							$menu .= "<select id='floatingModuleMenu' style='width: 200px'>";

							foreach ($menuData as $currentCategory => $menuItems) {

								// Wrap categories in optgroup labels
								if (!empty($currentCategory)) {
									$moduleDomain = ($menuItems[0]['type'] == 'Core')? null : $menuItems[0]['moduleName'];
									$menu .= "<optgroup label='--" .  __($currentCategory, $moduleDomain) . "--'/>";
								}

								foreach ($menuItems as $row) {

									$moduleDomain = ($row['type'] == 'Core')? null : $row['moduleName'];

									// Grab the base action name if this is a grouped action
									if (strpos($row['name'],'_') !== false) {
										$currentName = strstr($row['name'], '_', true);
									} else {
										$currentName = $row['name'];
									}

									// Avoid duplicates (esp. from grouped actions)
									if ($currentName!=$lastName) {
										$selected="";
										if ($_GET['q']=="/modules/" . $row['moduleName'] . "/" . $row['entryURL']) {
											$selected=" selected";
										}

										$menu .= "<option value='" . $absoluteURL . "/index.php?q=/modules/" . $row['moduleName'] . "/" . $row['entryURL'] . "'$selected>" . __($currentName, $moduleDomain) . "</option>";
									}

									$lastName=$currentName;
								}
                            }

							// TODO: Move this to common.js?
							$menu .= "<script>
								$(\"#floatingModuleMenu\").change(function() {
									document.location.href = $(this).val();
								});
							</script>";

						$menu .= "</select>";
							$menu .= "<div style='float: right; padding-top: 10px; margin-left: 10px;'>";
								$menu.=__('Module Menu');
							$menu .= "</div>";
						$menu .= "</div>";
					}
				}
			}
		}

		return $menu;
	}
}
?>
