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

function sidebarExtra($guid, $connection2, $gibbonCourseClassID) {
	$output="" ;
	
	//Show class list in sidebar
	try {
		$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
		$sql="SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourseClass.gibbonCourseClassID FROM gibbonCourse, gibbonCourseClass, gibbonCourseClassPerson WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID AND gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID ORDER BY course, class" ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { 
		print "<div class='error'>" . $e->getMessage() . "</div>" ; 
	}

	if ($result->rowCount()>0) {
		$output="<h2 class='sidebar'>" ;
		$output.=_("My Classes") ;
		$output.="</h2>" ;
		
		$output.="<table class='mini' cellspacing='0' style='width: 100%; table-layout: fixed'>" ;
			$output.="<tr class='head'>" ;
					$output.="<th style='width: 36%'>" ;
					$output.=_("Class") ;
				$output.="</th>" ;
				$output.="<th style='width: 16%; font-size: 60%; text-align: center; text-transform: uppercase'>" ;
					$output.=_("View") ;
				$output.="</th>" ;
				$output.="<th style='width: 16%; font-size: 60%; text-align: center; text-transform: uppercase'>" ;
					$output.=_("Edit") ;
				$output.="</th>" ;
				if (isActionAccessible($guid, $connection2, "/modules/Planner/planner.php")) {
					$output.="<th style='width: 16%; font-size: 60%; text-align: center; text-transform: uppercase'>" ;
						$output.=_("Plan") ;
					$output.="</th>" ;
				}
				if (isActionAccessible($guid, $connection2, "/modules/Planner/planner.php")) {
					$output.="<th style='width: 16%; font-size: 60%; text-align: center; text-transform: uppercase'>" ;
						$output.=_("Tasks") ;
					$output.="</th>" ;
				}
			$output.="</tr>" ;
			
			$count=0;
			$rowNum="odd" ;
			while ($row=$result->fetch()) {
				if ($count%2==0) {
					$rowNum="even" ;
				}
				else {
					$rowNum="odd" ;
				}
				$count++ ;
				
				//COLOR ROW BY STATUS!
				$output.="<tr class=$rowNum>" ;
					$output.="<td style='word-wrap: break-word'>" ;
						$output.=$row["course"] . "." . $row["class"] ;
					$output.="</td>" ;
					$output.="<td style='text-align: center'>" ;
						if ($_GET["q"]=="/modules/Markbook/markbook_view.php" AND $row["gibbonCourseClassID"]==$gibbonCourseClassID) {
							$output.="<a style='border-bottom: 2px solid #f00' href='index.php?q=/modules/Markbook/markbook_view.php&gibbonCourseClassID=" . $row["gibbonCourseClassID"] . "'><img title='" . _('View Markbook') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/plus.png'/></a>" ;
						}
						else {
							$output.="<a href='index.php?q=/modules/Markbook/markbook_view.php&gibbonCourseClassID=" . $row["gibbonCourseClassID"] . "'><img title='" . _('View Markbook') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/plus.png'/></a>" ;
						}
						
					$output.="</td>" ;
					$output.="<td style='text-align: center'>" ;
						if ($_GET["q"]=="/modules/Markbook/markbook_edit.php" AND $row["gibbonCourseClassID"]==$gibbonCourseClassID) {
							$output.="<a style='border-bottom: 2px solid #f00' href='index.php?q=/modules/Markbook/markbook_edit.php&gibbonCourseClassID=" . $row["gibbonCourseClassID"] . "'><img title='" . _('Edit Markbook') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/config.png'/></a>" ;
						}
						else {
							$output.="<a href='index.php?q=/modules/Markbook/markbook_edit.php&gibbonCourseClassID=" . $row["gibbonCourseClassID"] . "'><img title='" . _('Edit Markbook') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/config.png'/></a>" ;
						}
					$output.="</td>" ;
					if (isActionAccessible($guid, $connection2, "/modules/Planner/planner.php")) {
						$output.="<td style='text-align: center'>" ;
							$output.="<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Planner/planner.php&gibbonCourseClassID=" . $row["gibbonCourseClassID"] . "&viewBy=class'><img style='margin-top: 3px' title='" . _('View Planner') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/planner.png'/></a> " ;
						$output.="</td>" ;
					}
					if (isActionAccessible($guid, $connection2, "/modules/Planner/planner.php")) {
						$output.="<td style='text-align: center'>" ;
							$output.="<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Planner/planner_deadlines.php&gibbonCourseClassIDFilter=" . $row["gibbonCourseClassID"] . "'><img style='margin-top: 3px' title='" . _('View Homework') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/homework.png'/></a> " ;
						$output.="</td>" ;
					}
				$output.="</tr>" ;
			}
		$output.="</table>" ;
	}	
	
	if (isActionAccessible($guid, $connection2, "/modules/Markbook/markbook_view.php", "View Markbook_allClassesAllData")) {
		$output.="<h2>" ;
		$output.=_("View Any Class") ;
		$output.="</h2>" ;
		
		$output.="<form method='get' action='" . $_SESSION[$guid]["absoluteURL"] . "/index.php'>" ;
			$output.="<table class='smallIntBorder' cellspacing='0' style='width: 100%; margin: 0px 0px'>" ;	
				$output.="<tr>" ;
					$output.="<td style='width: 190px'>" ; 
						$output.="<select name='gibbonCourseClassID' id='gibbonCourseClassID' style='width:160px; float: none'>" ;
							$output.="<option value='Please select...'>" . _('Please select...') . "</option>" ;
							try {
								$dataSelect=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
								$sqlSelect="SELECT gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourseClass JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY course, class" ;
								$resultSelect=$connection2->prepare($sqlSelect);
								$resultSelect->execute($dataSelect);
							}
							catch(PDOException $e) { }
							while ($rowSelect=$resultSelect->fetch()) {
								$selected="" ;
								if ($rowSelect["gibbonCourseClassID"]==$gibbonCourseClassID) {
									$selected="selected" ;
								}
								$output.="<option $selected value='" . $rowSelect["gibbonCourseClassID"] . "'>" . htmlPrep($rowSelect["course"]) . "." . htmlPrep($rowSelect["class"]) . "</option>" ;
							}		
						$output.="</select>" ;
					$output.="</td>" ;
					$output.="<td class='right'>" ;
						$output.="<input type='hidden' name='q' id='q' value='/modules/Markbook/markbook_view.php'>" ;
						$output.="<input type='submit' value='" . _('Go') . "'>" ;
					$output.="</td>" ;
				$output.="</tr>" ;
			$output.="</table>" ;
		$output.="</form>" ;
	}
	
	return $output ;
}
?>
