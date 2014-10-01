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

@session_start() ;

//Module includes
include "./modules/" . $_SESSION[$guid]["module"] . "/moduleFunctions.php" ;

if (isActionAccessible($guid, $connection2, "/modules/Planner/units.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print _("Your request failed because you do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Get action with highest precendence
	$highestAction=getHighestGroupedAction($guid, $_GET["q"], $connection2) ;
	if ($highestAction==FALSE) {
		print "<div class='error'>" ;
		print _("The highest grouped action cannot be determined.") ;
		print "</div>" ;
	}
	else {
		print "<div class='trail'>" ;
		print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . _(getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . _('Manage Units') . "</div>" ;
		print "</div>" ;
		
		//Get Smart Workflow help message
		$category=getRoleCategory($_SESSION[$guid]["gibbonRoleIDCurrent"], $connection2) ;
		if ($category=="Staff") {
			$smartWorkflowHelp=getSmartWorkflowHelp($connection2, $guid, 2) ;
			if ($smartWorkflowHelp!=false) {
				print $smartWorkflowHelp ;
			}
		}
		
		if (isset($_GET["deleteReturn"])) { $deleteReturn=$_GET["deleteReturn"] ; } else { $deleteReturn="" ; }
		$deleteReturnMessage="" ;
		$class="error" ;
		if (!($deleteReturn=="")) {
			if ($deleteReturn=="success0") {
				$deleteReturnMessage=_("Your request was completed successfully.") ;		
				$class="success" ;
			}
			print "<div class='$class'>" ;
				print $deleteReturnMessage;
			print "</div>" ;
		} 
		
		$gibbonSchoolYearID="" ;
		if (isset($_GET["gibbonSchoolYearID"])) {
			$gibbonSchoolYearID=$_GET["gibbonSchoolYearID"] ;
		}
		if ($gibbonSchoolYearID=="" OR $gibbonSchoolYearID==$_SESSION[$guid]["gibbonSchoolYearID"]) {
			$gibbonSchoolYearID=$_SESSION[$guid]["gibbonSchoolYearID"] ;
			$gibbonSchoolYearName=$_SESSION[$guid]["gibbonSchoolYearName"] ;
		}
	
		if ($gibbonSchoolYearID!=$_SESSION[$guid]["gibbonSchoolYearID"]) {
			try {
				$data=array("gibbonSchoolYearID"=>$_GET["gibbonSchoolYearID"]); 
				$sql="SELECT * FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearID" ;
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				print "<div class='error'>" . $e->getMessage() . "</div>" ; 
			}
			
			if ($result->rowCount()!=1) {
				print "<div class='error'>" ;
					print _("The specified record does not exist.") ;
				print "</div>" ;
			}
			else {
				$row=$result->fetch() ;
				$gibbonSchoolYearID=$row["gibbonSchoolYearID"] ;
				$gibbonSchoolYearName=$row["name"] ;
			}
		}
		
		if ($gibbonSchoolYearID!="") {
			$gibbonCourseID=NULL ;
			if (isset($_GET["gibbonCourseID"])) {
				$gibbonCourseID=$_GET["gibbonCourseID"] ;
			}
			if ($gibbonCourseID=="") {
				try {
					if ($highestAction=="Manage Units_all") {
						$data=array("gibbonSchoolYearID"=>$gibbonSchoolYearID); 
						$sql="SELECT * FROM gibbonCourse WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY nameShort" ;
					}
					else if ($highestAction=="Manage Units_learningAreas") {
						$data=array("gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"], "gibbonSchoolYearID"=>$gibbonSchoolYearID, "gibbonSchoolYearID"=>$gibbonSchoolYearID); 
						$sql="SELECT gibbonCourseID, gibbonCourse.name, gibbonCourse.nameShort FROM gibbonCourse JOIN gibbonDepartment ON (gibbonCourse.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) JOIN gibbonDepartmentStaff ON (gibbonDepartmentStaff.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) WHERE gibbonDepartmentStaff.gibbonPersonID=:gibbonPersonID AND (role='Coordinator' OR role='Assistant Coordinator' OR role='Teacher (Curriculum)') AND gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY gibbonCourse.nameShort" ;
					}
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				if ($result->rowCount()>0) {
					$row=$result->fetch() ;
					$gibbonCourseID=$row["gibbonCourseID"] ;
				}
			}
			if ($gibbonCourseID!="") {
				try {
					$data=array("gibbonCourseID"=>$gibbonCourseID); 
					$sql="SELECT * FROM gibbonCourse WHERE gibbonCourseID=:gibbonCourseID" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				if ($result->rowCount()==1) {
					$row=$result->fetch() ;
				}
			}
			
			
			//Work out previous and next course with same name
			$gibbonCourseIDPrevious="" ;
			$gibbonSchoolYearIDPrevious=getPreviousSchoolYearID($gibbonSchoolYearID, $connection2) ;
			if ($gibbonSchoolYearIDPrevious!=FALSE AND isset($row["nameShort"])) {
				try {
					$dataPrevious=array("gibbonSchoolYearID"=>$gibbonSchoolYearIDPrevious, "nameShort"=>$row["nameShort"]); 
					$sqlPrevious="SELECT * FROM gibbonCourse WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND nameShort=:nameShort" ;
					$resultPrevious=$connection2->prepare($sqlPrevious);
					$resultPrevious->execute($dataPrevious);
				}
				catch(PDOException $e) { 	}
				if ($resultPrevious->rowCount()==1) {
					$rowPrevious=$resultPrevious->fetch() ;
					$gibbonCourseIDPrevious=$rowPrevious["gibbonCourseID"] ;
				}
			}
			$gibbonCourseIDNext="" ;
			$gibbonSchoolYearIDNext=getNextSchoolYearID($gibbonSchoolYearID, $connection2) ;
			if ($gibbonSchoolYearIDNext!=FALSE) {
				try {
					$dataNext=array("gibbonSchoolYearID"=>$gibbonSchoolYearIDNext, "nameShort"=>$row["nameShort"]); 
					$sqlNext="SELECT * FROM gibbonCourse WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND nameShort=:nameShort" ;
					$resultNext=$connection2->prepare($sqlNext);
					$resultNext->execute($dataNext);
				}
				catch(PDOException $e) { 	}
				if ($resultNext->rowCount()==1) {
					$rowNext=$resultNext->fetch() ;
					$gibbonCourseIDNext=$rowNext["gibbonCourseID"] ;
				}
			}
			
			
			
			print "<h2>" ;
				print $gibbonSchoolYearName ;
			print "</h2>" ;
			
			print "<div class='linkTop'>" ;
				//Print year picker
				if (getPreviousSchoolYearID($gibbonSchoolYearID, $connection2)!=FALSE) {
					print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/units.php&gibbonSchoolYearID=" . getPreviousSchoolYearID($gibbonSchoolYearID, $connection2) . "&gibbonCourseID=$gibbonCourseIDPrevious'>" . _('Previous Year') . "</a> " ;
				}
				else {
					print _("Previous Year") . " " ;
				}
				print " | " ;
				if (getNextSchoolYearID($gibbonSchoolYearID, $connection2)!=FALSE) {
					print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/units.php&gibbonSchoolYearID=" . getNextSchoolYearID($gibbonSchoolYearID, $connection2) . "&gibbonCourseID=$gibbonCourseIDNext'>" . _('Next Year') . "</a> " ;
				}
				else {
					print _("Next Year") . " " ;
				}
			print "</div>" ;
			
			if ($gibbonCourseID!="") {
				try {
					if ($highestAction=="Manage Units_all") {
						$data=array("gibbonSchoolYearID"=>$gibbonSchoolYearID, "gibbonCourseID"=>$gibbonCourseID); 
						$sql="SELECT * FROM gibbonCourse WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseID=:gibbonCourseID" ;
					}
					else if ($highestAction=="Manage Units_learningAreas") {
						$data=array("gibbonSchoolYearID"=>$gibbonSchoolYearID, "gibbonCourseID"=>$gibbonCourseID, "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
						$sql="SELECT gibbonCourseID, gibbonCourse.name, gibbonCourse.nameShort FROM gibbonCourse JOIN gibbonDepartment ON (gibbonCourse.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) JOIN gibbonDepartmentStaff ON (gibbonDepartmentStaff.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) WHERE gibbonDepartmentStaff.gibbonPersonID=:gibbonPersonID AND (role='Coordinator' OR role='Assistant Coordinator' OR role='Teacher (Curriculum)') AND gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseID=:gibbonCourseID ORDER BY gibbonCourse.nameShort" ;
					}
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				
				if ($result->rowCount()<1) {
					print "<div class='error'>" ;
					print _("The selected record does not exist, or you do not have access to it.") ;
					print "</div>" ;
				}
				else {
					$row=$result->fetch() ;
				
					print "<h4>" ;
						print $row["name"] ;
					print "</h4>" ;
					
					//Fetch units
					try {
						$data=array("gibbonSchoolYearID"=>$gibbonSchoolYearID, "gibbonCourseID"=>$gibbonCourseID); 
						$sql="SELECT gibbonUnitID, gibbonUnit.gibbonCourseID, nameShort, gibbonUnit.name, gibbonUnit.description FROM gibbonUnit JOIN gibbonCourse ON gibbonUnit.gibbonCourseID=gibbonCourse.gibbonCourseID WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonUnit.gibbonCourseID=:gibbonCourseID ORDER BY nameShort, name" ; 
						$result=$connection2->prepare($sql);
						$result->execute($data);
					}
					catch(PDOException $e) { 
						print "<div class='error'>" . $e->getMessage() . "</div>" ; 
					}
					
					print "<div class='linkTop'>" ;
					print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/units_add.php&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonCourseID=$gibbonCourseID'><img title='" . _('Add New Record') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/page_new.png'/></a>" ;
					print "</div>" ;
					
					if ($result->rowCount()<1) {
						print "<div class='error'>" ;
						print _("There are no records to display.") ;
						print "</div>" ;
					}
					else {
						print "<table cellspacing='0' style='width: 100%'>" ;
							print "<tr class='head'>" ;
								print "<th style='width: 150px'>" ;
									print _("Name") ;
								print "</th>" ;
								print "<th style='width: 450px'>" ;
									print _("Description") ;
								print "</th>" ;
								print "<th style='width: 120px'>" ;
									print _("Actions") ;
								print "</th>" ;
							print "</tr>" ;
							
							$count=0;
							$rowNum="odd" ;
							while ($row=$result->fetch()) {
								if ($count%2==0) {
									$rowNum="even" ;
								}
								else {
									$rowNum="odd" ;
								}
								
								//COLOR ROW BY STATUS!
								print "<tr class=$rowNum>" ;
									print "<td>" ;
										print $row["name"] ;
									print "</td>" ;
									print "<td style='max-width: 270px'>" ;
										print $row["description"] ;
									print "</td>" ;
									print "<td>" ;
										print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/units_edit.php&gibbonUnitID=" . $row["gibbonUnitID"] . "&gibbonCourseID=$gibbonCourseID&gibbonSchoolYearID=$gibbonSchoolYearID'><img title='" . _('Edit') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/config.png'/></a> " ;
										print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/units_delete.php&gibbonUnitID=" . $row["gibbonUnitID"] . "&gibbonCourseID=$gibbonCourseID&gibbonSchoolYearID=$gibbonSchoolYearID'><img title='" . _('Delete') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/garbage.png'/></a> " ;
										print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/units_duplicate.php&gibbonCourseID=$gibbonCourseID&gibbonUnitID=" . $row["gibbonUnitID"] . "&gibbonSchoolYearID=$gibbonSchoolYearID'><img title='" . _('Duplicate') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/copy.png'/></a> " ;
										print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/units_dump.php&gibbonCourseID=$gibbonCourseID&gibbonUnitID=" . $row["gibbonUnitID"] . "&gibbonSchoolYearID=$gibbonSchoolYearID&sidebar=false'><img title='" . _('Export') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/download.png'/></a>" ;
									print "</td>" ;
								print "</tr>" ;
								
								$count++ ;
							}
						print "</table>" ;
					}
					
					//List any hooked units
					try {
						$dataHooks=array(); 
						$sqlHooks="SELECT * FROM gibbonHook WHERE type='Unit' ORDER BY name" ;
						$resultHooks=$connection2->prepare($sqlHooks);
						$resultHooks->execute($dataHooks);
					}
					catch(PDOException $e) { }
					while ($rowHooks=$resultHooks->fetch()) {
						$hookOptions=unserialize($rowHooks["options"]) ;
						if ($hookOptions["unitTable"]!="" AND $hookOptions["unitIDField"]!="" AND $hookOptions["unitCourseIDField"]!="" AND $hookOptions["unitNameField"]!="" AND $hookOptions["unitDescriptionField"]!="" AND $hookOptions["classLinkTable"]!="" AND $hookOptions["classLinkJoinFieldUnit"]!="" AND $hookOptions["classLinkJoinFieldClass"]!="" AND $hookOptions["classLinkIDField"]!="") {
							try {
								$dataHookUnits=array("unitCourseIDField"=>$gibbonCourseID); 
								$sqlHookUnits="SELECT * FROM " . $hookOptions["unitTable"] . " WHERE " . $hookOptions["unitCourseIDField"] . "=:unitCourseIDField ORDER BY " . $hookOptions["unitNameField"] ;
								$resultHookUnits=$connection2->prepare($sqlHookUnits);
								$resultHookUnits->execute($dataHookUnits);
							}
							catch(PDOException $e) { }
							if ($resultHookUnits->rowCount()>0) {
								print "<h4>" . $rowHooks["name"] . " Units</h4>" ;
									print "<table cellspacing='0' style='width: 100%'>" ;
									print "<tr class='head'>" ;
										print "<th style='width: 150px'>" ;
											print _("Name") ;
										print "</th>" ;
										print "<th style='width: 450px'>" ;
											print "Description" ;
										print "</th>" ;
										print "<th>" ;
											print _("Actions") ;
										print "</th>" ;
									print "</tr>" ;
							
									$count=0;
								
								
									while ($rowHookUnits=$resultHookUnits->fetch()) {
										if ($count%2==0) {
											$rowNum="even" ;
										}
										else {
											$rowNum="odd" ;
										}
								
										//COLOR ROW BY STATUS!
										print "<tr class=$rowNum>" ;
											print "<td>" ;
												print $rowHookUnits[$hookOptions["unitNameField"]] ;
											print "</td>" ;
											print "<td style='max-width: 270px'>" ;
												print strip_tags($rowHookUnits[$hookOptions["unitDescriptionField"]]) ;
											print "</td>" ;
											print "<td>" ;
												print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/units_edit.php&gibbonUnitID=" . $rowHookUnits[$hookOptions["unitIDField"]] . "-" . $rowHooks["gibbonHookID"] . "&gibbonCourseID=$gibbonCourseID&gibbonSchoolYearID=$gibbonSchoolYearID'><img title='" . _('Edit') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/config.png'/></a> " ;
											print "</td>" ;
										print "</tr>" ;
								
										$count++ ;
									}	
								print "</table>" ;
							}									
						}
					}
				}
			}
		}
	}
	//Print sidebar
	$_SESSION[$guid]["sidebarExtra"]=sidebarExtraUnits($guid, $connection2, $gibbonCourseID, $gibbonSchoolYearID) ;
}		
?>