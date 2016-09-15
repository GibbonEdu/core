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
class menuMain
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
	 * Stores the menu whilst it is being constructed, before storage in session
	 */
	public $menu = "" ;
	
	/**
	 * Construct
	 *
	 * @version 22nd April 2016
	 * @since	22nd April 2016
	 * @return	void
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
	public function setMenu()
	{
		$menu="" ;

		if (isset($_SESSION[$this->config->get('guid')]["gibbonRoleIDCurrent"])==FALSE) {
			$menu.="<ul id='nav'>" ;
			$menu.="<li class='active'><a href='" . $_SESSION[$this->config->get('guid')]["absoluteURL"] . "/index.php'>" . __($this->config->get('guid'), 'Home') . "</a></li>" ;
			$menu.="</ul>" ;
		}
		else {
			$data=array("gibbonRoleID"=>$_SESSION[$this->config->get('guid')]["gibbonRoleIDCurrent"]);
			$sql="SELECT DISTINCT gibbonModule.name, gibbonModule.category, gibbonModule.entryURL FROM gibbonModule JOIN gibbonAction ON (gibbonAction.gibbonModuleID=gibbonModule.gibbonModuleID) JOIN gibbonPermission ON (gibbonPermission.gibbonActionID=gibbonAction.gibbonActionID) WHERE active='Y' AND menuShow='Y' AND gibbonPermission.gibbonRoleID=:gibbonRoleID ORDER BY (gibbonModule.category='Other') ASC, category, name";
			$result = $this->pdo->executeQuery($data, $sql);
			if (! $this->pdo->getQuerySuccess()) {
				$menu.="<div class='error'>" . $this->pdo->getError() . "</div>" ;
			}
			
			if ($result->rowCount()<1) {
				$menu.="<ul id='nav'>" ;
				$menu.="<li class='active'><a href='" . $_SESSION[$this->config->get('guid')]["absoluteURL"] . "/index.php'>" . __($this->config->get('guid'), 'Home') . "</a></li>" ;
				$menu.="</ul>" ;
			}
			else {
				$menu.="<ul id='nav'>" ;
				$menu.="<li><a href='" . $_SESSION[$this->config->get('guid')]["absoluteURL"] . "/index.php'>" . __($this->config->get('guid'), 'Home') . "</a></li>" ;

				$currentCategory="" ;
				$lastCategory="" ;
				$count=0;
				while ($row=$result->fetch()) {
					$currentCategory=$row["category"] ;

					$entryURL=$row["entryURL"] ;
					if (isActionAccessible($this->config->get('guid'), $this->pdo->getConnection(), "/modules/" . $row["name"] . "/" . $entryURL)==FALSE AND $entryURL!="index.php") {
						$dataEntry=array("gibbonRoleID"=>$_SESSION[$this->config->get('guid')]["gibbonRoleIDCurrent"],"name"=>$row["name"]);
						$sqlEntry="SELECT DISTINCT gibbonAction.entryURL FROM gibbonModule JOIN gibbonAction ON (gibbonAction.gibbonModuleID=gibbonModule.gibbonModuleID) JOIN gibbonPermission ON (gibbonPermission.gibbonActionID=gibbonAction.gibbonActionID) WHERE active='Y' AND menuShow='Y' AND gibbonPermission.gibbonRoleID=:gibbonRoleID AND gibbonModule.name=:name ORDER BY gibbonAction.name";
						$resultEntry = $this->pdo->executeQuery($dataEntry, $sqlEntry);
						if ($resultEntry->rowCount()>0) {
							$rowEntry=$resultEntry->fetch() ;
							$entryURL=$rowEntry["entryURL"] ;
						}
					}
					
					if ($currentCategory!=$lastCategory) {
						if ($count>0) {
							$menu.="</ul></li>";
						}
						$menu.="<li><a href='#'>" . __($this->config->get('guid'), $currentCategory) . "</a>" ;
						$menu.="<ul>" ;
						$menu.="<li><a href='" . $_SESSION[$this->config->get('guid')]["absoluteURL"] . "/index.php?q=/modules/" . $row["name"] . "/" . $entryURL . "'>" . __($this->config->get('guid'), $row["name"]) . "</a></li>" ;
					}
					else {
						$menu.="<li><a href='" . $_SESSION[$this->config->get('guid')]["absoluteURL"] . "/index.php?q=/modules/" . $row["name"] . "/" . $entryURL . "'>" . __($this->config->get('guid'), $row["name"]) . "</a></li>" ;
					}
					$lastCategory=$currentCategory ;
					$count++ ;
					
					
				}
				if ($count>0) {
					$menu.="</ul></li>";
				}
				$menu.="</ul>" ;
			}
		}
		
		//$this->session->set('mainMenu', $menu) ;
		$_SESSION[$this->config->get('guid')]["mainMenu"]=$menu ;
		if (isset($_SESSION[$this->config->get('guid')]["lastMainMenu"])) unset($_SESSION[$this->config->get('guid')]["lastMainMenu"]);
	}
}
?>