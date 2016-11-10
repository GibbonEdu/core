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
	 * Gibbon\trans
	 */
	private $trans ;

	/**
	 * Construct
	 *
	 * @version 10th November 2016
	 * @since	22nd April 2016
	 */
	public function __construct( sqlConnection $pdo, session $session, trans $trans )
	{
		$this->pdo = $pdo;
		$this->session = $session;
		$this->trans = $trans;
	}

	/**
	 * Construct and store main menu in session
	 *
	 * @version 10th November 2016
	 * @since	Old
	 */
	public function setMenu()
	{
		$menu="" ;

		$address = $this->session->get("address");
		$absoluteURL = $this->session->get("absoluteURL");

		if ($this->session->get("gibbonRoleIDCurrent") == null) {
			$menu.="<ul id='nav'>" ;
			$menu.="<li class='active'><a href='" . $absoluteURL . "/index.php'>" . $this->trans->__('Home') . "</a></li>" ;
			$menu.="</ul>" ;
		}
		else {
			$mainMenuCategoryOrder = getSettingByScope($this->pdo->getConnection(), 'System', 'mainMenuCategoryOrder');
			$orders = explode(',', $mainMenuCategoryOrder);
			$orderBy = '';
			foreach ($orders AS $order) {
				$orderBy .= "'".$order."',";
			}
			if ($orderBy != '')
				$orderBy = substr($orderBy, 0, -1);
			$data=array("gibbonRoleID"=> $this->session->get("gibbonRoleIDCurrent") );
			$sql="SELECT DISTINCT gibbonModule.name, gibbonModule.category, gibbonModule.entryURL FROM gibbonModule JOIN gibbonAction ON (gibbonAction.gibbonModuleID=gibbonModule.gibbonModuleID) JOIN gibbonPermission ON (gibbonPermission.gibbonActionID=gibbonAction.gibbonActionID) WHERE active='Y' AND menuShow='Y' AND gibbonPermission.gibbonRoleID=:gibbonRoleID ORDER BY FIELD(gibbonModule.category, $orderBy), category, name";
			$result = $this->pdo->executeQuery($data, $sql);
			if (! $this->pdo->getQuerySuccess()) {
				$menu.="<div class='error'>" . $this->pdo->getError() . "</div>" ;
			}

			if ($result->rowCount()<1) {
				$menu.="<ul id='nav'>" ;
				$menu.="<li class='active'><a href='" . $absoluteURL . "/index.php'>" . $this->trans->__('Home') . "</a></li>" ;
				$menu.="</ul>" ;
			}
			else {
				$menu.="<ul id='nav'>" ;
				$menu.="<li><a href='" . $absoluteURL . "/index.php'>" . $this->trans->__('Home') . "</a></li>" ;

				$currentCategory="" ;
				$lastCategory="" ;
				$count=0;
				while ($row=$result->fetch()) {
					$currentCategory=$row["category"] ;

					$entryURL=$row["entryURL"] ;
					if (isActionAccessible($this->session->guid(), $this->pdo->getConnection(), "/modules/" . $row["name"] . "/" . $entryURL)==FALSE AND $entryURL!="index.php") {
						$dataEntry=array("gibbonRoleID" => $this->session->get("gibbonRoleIDCurrent") ,"name"=>$row["name"]);
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
						$menu.="<li><a href='#'>" . $this->trans->__($currentCategory) . "</a>" ;
						$menu.="<ul>" ;
						$menu.="<li><a href='" . $absoluteURL . "/index.php?q=/modules/" . $row["name"] . "/" . $entryURL . "'>" . $this->trans->__($row["name"]) . "</a></li>" ;
					}
					else {
						$menu.="<li><a href='" . $absoluteURL . "/index.php?q=/modules/" . $row["name"] . "/" . $entryURL . "'>" . $this->trans->__($row["name"]) . "</a></li>" ;
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
