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
 * @version	22nd April 2016
 * @since	22nd April 2016
 * @author	Ross Parker
 */
class menuModule
{
	/**
	 * Gibbon\sqlConnection
	 */
	private $pdo ;
	
	/**
	 * Gibbon\session
	 */
	private $session ;
	
	/**
	 * Stores the type of module menu
	 */
	private $type ;
	
	/**
	 * Construct
	 *
	 * @version 10th November 2016
	 * @since	22nd April 2016
	 */
	public function __construct( core $gibbon, sqlConnection $pdo )
	{
		$this->pdo = $pdo;
		$this->session = $gibbon->session;
	}

	/**
	 * Construct and return the module menu
	 *
	 * @version 10th November 2016
	 * @since	Old
	 * @return	string
	 */
	public function getMenu($type='full')
	{
		$menu="" ;

		$address = $this->session->get("address");
		$absoluteURL = $this->session->get("absoluteURL");

		//Check address to see if we are in the module area
		if (substr($address,0,8)=="/modules") {
			
			//Get and check the module name
			$moduleID=checkModuleReady( $address, $this->pdo->getConnection() );
			if ($moduleID!=FALSE) {

				$gibbonRoleIDCurrent= $this->session->get("gibbonRoleIDCurrent");

				$data=array("gibbonModuleID"=>$moduleID, "gibbonRoleID"=>$gibbonRoleIDCurrent);
				$sql="SELECT gibbonModule.entryURL AS moduleEntry, gibbonModule.name AS moduleName, gibbonAction.name, gibbonModule.type, gibbonAction.precedence, gibbonAction.category, gibbonAction.entryURL, URLList FROM gibbonModule JOIN gibbonAction ON (gibbonModule.gibbonModuleID=gibbonAction.gibbonModuleID) JOIN gibbonPermission ON (gibbonAction.gibbonActionID=gibbonPermission.gibbonActionID) WHERE (gibbonModule.gibbonModuleID=:gibbonModuleID) AND (gibbonPermission.gibbonRoleID=:gibbonRoleID) AND NOT gibbonAction.entryURL='' AND menuShow='Y' ORDER BY gibbonModule.name, category, gibbonAction.name, precedence DESC";
				$result = $this->pdo->executeQuery($data, $sql);

				if ($result->rowCount()>0) {
					if ($type=="full") {
						$menu.="<ul class='moduleMenu'>" ;
							$currentCategory="" ;
							$lastCategory="" ;
							$currentName="" ;
							$lastName="" ;
							$count=0;
							$links=0 ;
							while ($row=$result->fetch()) {
								$moduleName=$row["moduleName"] ;
								$moduleEntry=$row["moduleEntry"] ;
								$moduleDomain = ($row['type'] == 'Core')? null : $row['moduleName'];

								//Set active link class
								$style="" ;
								if (strpos($row["URLList"],getActionName($address))===0) {
									$style="class='active'" ;
								}

								$currentCategory=$row["category"] ;
								if (strpos($row["name"],"_")>0) {
									$currentName=substr($row["name"],0,strpos($row["name"],"_"));
								}
								else {
									$currentName=$row["name"] ;
								}

								if ($currentName!=$lastName) {
									if ($currentCategory!=$lastCategory) {
										if ($count>0) {
											$menu.="</ul></li>";
										}
										$menu.="<li><h4>" . __($currentCategory, $moduleDomain) . "</h4>" ;
										$menu.="<ul>" ;
										$menu.="<li><a $style href='" . $absoluteURL . "/index.php?q=/modules/" . $row["moduleName"] . "/" . $row["entryURL"] . "'>" . __($currentName, $moduleDomain) . "</a></li>" ;
									}
									else {
										$menu.="<li><a $style href='" . $absoluteURL . "/index.php?q=/modules/" . $row["moduleName"] . "/" . $row["entryURL"] . "'>" . __($currentName, $moduleDomain) . "</a></li>" ;
									}
									$links++ ;
								}
								$lastCategory=$currentCategory ;
								$lastName=$currentName ;
								$count++ ;
							}
							if ($count>0) {
								$menu.="</ul></li>";
							}
						$menu.="</ul>" ;
					}
					else if ($type=="mini") {
						$menu.="<div class='linkTop'>" ;
							$menu.="<select id='floatingModuleMenu' style='width: 200px'>" ;						
								$currentCategory="" ;
								$lastCategory="" ;
								$currentName="" ;
								$lastName="" ;
								$count=0;
								$links=0 ;
								while ($row=$result->fetch()) {
									$moduleName=$row["moduleName"] ;
									$moduleEntry=$row["moduleEntry"] ;
									$moduleDomain = ($row['type'] == 'Core')? null : $row['moduleName'];

									$currentCategory=$row["category"] ;
									if (strpos($row["name"],"_")>0) {
										$currentName=substr($row["name"],0,strpos($row["name"],"_"));
									}
									else {
										$currentName=$row["name"];
									}

									if ($currentName!=$lastName) {
										if ($currentCategory!=$lastCategory) {
											$menu.="<optgroup label='--" .  __($currentCategory, $moduleDomain) . "--'/>" ;
										}
										$selected="" ;
										if ($_GET["q"]=="/modules/" . $row["moduleName"] . "/" . $row["entryURL"]) {
											$selected="selected" ;
										}
										$menu.="<option value='" . $absoluteURL . "/index.php?q=/modules/" . $row["moduleName"] . "/" . $row["entryURL"] . "' $selected>" . __($currentName, $moduleDomain) . "</option>" ;
										$links++ ;
									}
									$lastCategory=$currentCategory ;
									$lastName=$currentName ;
									$count++ ;
								}
						
								$menu.="<script>
									$(\"#floatingModuleMenu\").change(function() {
										document.location.href = $(this).val();
									});
								</script>" ;
							$menu.="</select>" ;
							$menu.="<div style='float: right; padding-top: 10px'>" ;
								$menu.=__("Module Menu") ;
							$menu.="</div>" ;
						$menu.="</div>" ;	
					}
				}
			}
		}
	
		return $menu ;
	}
}
?>