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
	 * Gibbon\config
	 */
	private $config ;
	
	/**
	 * Stores the type of module menu
	 */
	private $type ;
	
	/**
	 * Stores the menu whilst it is being constructed, before returning
	 */
	public $menu = "" ;
	
	/**
	 * Construct
	 *
	 * @version 22nd April 2016
	 * @since	22nd April 2016
	 * @return	void
	 * $type should be 'full' or 'mini'
	 */
	public function __construct()
	{
		$this->pdo = new sqlConnection();
		$this->session = new session();
		$this->config = new config();
	}

	/**
	 * Construct and store main menu in session
	 *
	 * (Moved from /functions.php)
	 * @version 19th April 2016
	 * @since	Old
	 * @return	void
	 */
	public function getMenu($type='full')
	{
		$this->type=$type ;
		//Check address to see if we are in the module area
		if (substr($_SESSION[$this->config->get('guid')]["address"],0,8)=="/modules") {
			//Get and check the module name
			$moduleID=checkModuleReady( $_SESSION[$this->config->get('guid')]["address"], $this->pdo->getConnection() );
			if ($moduleID!=FALSE) {
				$gibbonRoleIDCurrent=NULL ;
				if (isset($_SESSION[$this->config->get('guid')]["gibbonRoleIDCurrent"])) {
					$gibbonRoleIDCurrent=$_SESSION[$this->config->get('guid')]["gibbonRoleIDCurrent"] ;
				}
				$data=array("gibbonModuleID"=>$moduleID, "gibbonRoleID"=>$gibbonRoleIDCurrent);
				$sql="SELECT gibbonModule.entryURL AS moduleEntry, gibbonModule.name AS moduleName, gibbonAction.name, gibbonAction.precedence, gibbonAction.category, gibbonAction.entryURL, URLList FROM gibbonModule, gibbonAction, gibbonPermission WHERE (gibbonModule.gibbonModuleID=:gibbonModuleID) AND (gibbonModule.gibbonModuleID=gibbonAction.gibbonModuleID) AND (gibbonAction.gibbonActionID=gibbonPermission.gibbonActionID) AND (gibbonPermission.gibbonRoleID=:gibbonRoleID) AND NOT gibbonAction.entryURL='' ORDER BY gibbonModule.name, category, gibbonAction.name, precedence DESC";
				$result = $this->pdo->executeQuery($data, $sql);

				if ($result->rowCount()>0) {
					if ($this->type=="full") {
						$this->menu.="<ul class='moduleMenu'>" ;
							$currentCategory="" ;
							$lastCategory="" ;
							$currentName="" ;
							$lastName="" ;
							$count=0;
							$links=0 ;
							while ($row=$result->fetch()) {
								$moduleName=$row["moduleName"] ;
								$moduleEntry=$row["moduleEntry"] ;

								//Set active link class
								$style="" ;
								if (strpos($row["URLList"],getActionName($_SESSION[$this->config->get('guid')]["address"]))===0) {
									$style="class='active'" ;
								}

								$currentCategory=$row["category"] ;
								if (strpos($row["name"],"_")>0) {
									$currentName=__($this->config->get('guid'), substr($row["name"],0,strpos($row["name"],"_"))) ;
								}
								else {
									$currentName=__($this->config->get('guid'), $row["name"]) ;
								}

								if ($currentName!=$lastName) {
									if ($currentCategory!=$lastCategory) {
										if ($count>0) {
											$this->menu.="</ul></li>";
										}
										$this->menu.="<li><h4>" . __($this->config->get('guid'), $currentCategory) . "</h4>" ;
										$this->menu.="<ul>" ;
										$this->menu.="<li><a $style href='" . $_SESSION[$this->config->get('guid')]["absoluteURL"] . "/index.php?q=/modules/" . $row["moduleName"] . "/" . $row["entryURL"] . "'>" . __($this->config->get('guid'), $currentName) . "</a></li>" ;
									}
									else {
										$this->menu.="<li><a $style href='" . $_SESSION[$this->config->get('guid')]["absoluteURL"] . "/index.php?q=/modules/" . $row["moduleName"] . "/" . $row["entryURL"] . "'>" . __($this->config->get('guid'), $currentName) . "</a></li>" ;
									}
									$links++ ;
								}
								$lastCategory=$currentCategory ;
								$lastName=$currentName ;
								$count++ ;
							}
							if ($count>0) {
								$this->menu.="</ul></li>";
							}
						$this->menu.="</ul>" ;
					}
					else if ($this->type=="mini") {
						$this->menu.="<div class='linkTop'>" ;
							$this->menu.="<select id='floatingModuleMenu' style='width: 200px'>" ;						
								$currentCategory="" ;
								$lastCategory="" ;
								$currentName="" ;
								$lastName="" ;
								$count=0;
								$links=0 ;
								while ($row=$result->fetch()) {
									$moduleName=$row["moduleName"] ;
									$moduleEntry=$row["moduleEntry"] ;

									$currentCategory=$row["category"] ;
									if (strpos($row["name"],"_")>0) {
										$currentName=__($this->config->get('guid'), substr($row["name"],0,strpos($row["name"],"_"))) ;
									}
									else {
										$currentName=__($this->config->get('guid'), $row["name"]) ;
									}

									if ($currentName!=$lastName) {
										if ($currentCategory!=$lastCategory) {
											$this->menu.="<optgroup label='--" .  __($this->config->get('guid'), $currentCategory) . "--'/>" ;
										}
										$selected="" ;
										if ($_GET["q"]=="/modules/" . $row["moduleName"] . "/" . $row["entryURL"]) {
											$selected="selected" ;
										}
										$this->menu.="<option value='" . $_SESSION[$this->config->get('guid')]["absoluteURL"] . "/index.php?q=/modules/" . $row["moduleName"] . "/" . $row["entryURL"] . "' $selected>" . __($this->config->get('guid'), $currentName) . "</option>" ;
										$links++ ;
									}
									$lastCategory=$currentCategory ;
									$lastName=$currentName ;
									$count++ ;
								}
						
								$this->menu.="<script>
									$(\"#floatingModuleMenu\").change(function() {
										document.location.href = $(this).val();
									});
								</script>" ;
							$this->menu.="</select>" ;
							$this->menu.="<div style='float: right; padding-top: 10px'>" ;
								$this->menu.=__($this->config->get('guid'), "Module Menu") ;
							$this->menu.="</div>" ;
						$this->menu.="</div>" ;	
					}
				}
			}
		}
	
		return $this->menu ;
	}
}
?>