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

if (isActionAccessible($guid, $connection2, "/modules/Markbook/markbook_edit.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "Your request failed because you do not have access to this action.") ;
	print "</div>" ;
}
else {
	$highestAction=getHighestGroupedAction($guid, $_GET["q"], $connection2) ;
	if ($highestAction==FALSE) {
		print "<div class='error'>" ;
		print __($guid, "The highest grouped action cannot be determined.") ;
		print "</div>" ;
	}
	else {
		//Get class variable
		$gibbonCourseClassID=NULL ;
		if (isset($_GET["gibbonCourseClassID"])) {
			$gibbonCourseClassID=$_GET["gibbonCourseClassID"] ;
		}
		else {
			try {
				$data=array("gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
				$sql="SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourseClass.gibbonCourseClassID FROM gibbonCourse, gibbonCourseClass, gibbonCourseClassPerson WHERE gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID AND gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND gibbonCourse.gibbonSchoolYearID=(SELECT gibbonSchoolYearID FROM gibbonSchoolYear WHERE status='Current') ORDER BY course, class" ;
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				print "<div class='error'>" . $e->getMessage() . "</div>" ; 
			}
			if ($result->rowCount()>0) {
				$row=$result->fetch() ;
				$gibbonCourseClassID=$row["gibbonCourseClassID"] ;
			}
		}
		if ($gibbonCourseClassID=="") {
			print "<h1>" ;
				print "Edit Markbook" ;
			print "</h1>" ;
			print "<div class='warning'>" ;
				print __($guid, "Use the class listing on the right to choose a Markbook to edit.") ;
			print "</div>" ;
		}
		//Check existence of and access to this class.
		else {
			try {
				$data=array("gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"], "gibbonCourseClassID"=>$gibbonCourseClassID); 
				$sql="SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourseClass.gibbonCourseClassID FROM gibbonCourse, gibbonCourseClass, gibbonCourseClassPerson WHERE gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID AND gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND role='Teacher' AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID ORDER BY course, class" ;
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				print "<div class='error'>" . $e->getMessage() . "</div>" ; 
			}

			if ($result->rowCount()!=1) {
				print "<h1>" ;
					print __($guid, "Edit Markbook") ;
				print "</h1>" ;
				print "<div class='error'>" ;
					print __($guid, "The selected record does not exist, or you do not have access to it.") ;
				print "</div>" ;	
			}
			else {
				$row=$result->fetch() ;
				print "<div class='trail'>" ;
				print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . __($guid, 'Edit') . " " . $row["course"] . "." . $row["class"] . " " . __($guid, 'Markbook') . "</div>" ;
				print "</div>" ;
			
				//Add multiple columns
				if (isActionAccessible($guid, $connection2, "/modules/Markbook/markbook_edit.php")) {
					$highestAction2=getHighestGroupedAction($guid, "/modules/Markbook/markbook_edit.php", $connection2) ;
					if ($highestAction2=="Edit Markbook_multipleClassesAcrossSchool" OR $highestAction2=="Edit Markbook_multipleClassesInDepartment" OR $highestAction2=="Edit Markbook_everything") {
						//Check highest role in any department
						try {
							$dataRole=array("gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
							$sqlRole="SELECT role FROM gibbonDepartmentStaff WHERE gibbonPersonID=:gibbonPersonID AND (role='Coordinator' OR role='Assistant Coordinator' OR role='Teacher (Curriculum)')" ;
							$resultRole=$connection2->prepare($sqlRole);
							$resultRole->execute($dataRole);
						}
						catch(PDOException $e) { }
						if ($resultRole->rowCount()>=1 OR $highestAction2=="Edit Markbook_multipleClassesAcrossSchool" OR $highestAction2=="Edit Markbook_everything") {
							print "<div class='linkTop'>" ;
								print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/markbook_edit_addMulti.php&gibbonCourseClassID=$gibbonCourseClassID'>" . __($guid, 'Add Multiple Columns') . "<img style='margin-left: 5px' title='" . __($guid, 'Add Multiple Columns') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/page_new_multi.png'/></a>" ;
							print "</div>" ;
						}
					}
				}
			
				//Get teacher list
				$teaching=FALSE ;
				try {
					$data=array("gibbonCourseClassID"=>$gibbonCourseClassID); 
					$sql="SELECT gibbonPerson.gibbonPersonID, title, surname, preferredName FROM gibbonCourseClassPerson JOIN gibbonPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE role='Teacher' AND gibbonCourseClassID=:gibbonCourseClassID ORDER BY surname, preferredName" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				if ($result->rowCount()>0) {
					print "<h3>" ;
						print __($guid, "Teachers") ;
					print "</h3>" ;	
					print "<ul>" ;
						while ($row=$result->fetch()) {
							print "<li>" . formatName($row["title"], $row["preferredName"], $row["surname"], "Staff") . "</li>" ;
							if ($row["gibbonPersonID"]==$_SESSION[$guid]["gibbonPersonID"]) {
								$teaching=TRUE ;
							}
						}
					print "</ul>" ;
				}
			
				//Print mark
				print "<h3>" ;
					print __($guid, "Markbook Columns") ;
				print "</h3>" ;	
			
				//Set pagination variable
				$page=1 ; if (isset($_GET["page"])) { $page=$_GET["page"] ; }
				if ((!is_numeric($page)) OR $page<1) {
					$page=1 ;
				}
			
				try {
					$data=array("gibbonCourseClassID"=>$gibbonCourseClassID); 
					$sql="SELECT * FROM gibbonMarkbookColumn WHERE gibbonCourseClassID=:gibbonCourseClassID ORDER BY completeDate DESC, name" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}

				if ($teaching) {
					print "<div class='linkTop'>" ;
						print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/markbook_edit_add.php&gibbonCourseClassID=$gibbonCourseClassID'>" . __($guid, 'Add') . "<img style='margin-left: 5px' title='" . __($guid, 'Add') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/page_new.png'/></a>" ;
					print "</div>" ;
				}
			
				if ($result->rowCount()<1) {
					print "<div class='error'>" ;
					print __($guid, "There are no records to display.") ;
					print "</div>" ;
				}
				else {
					print "<table cellspacing='0' style='width: 100%'>" ;
						print "<tr class='head'>" ;
							print "<th>" ;
								print __($guid, "Name/Unit") ;
							print "</th>" ;
							print "<th>" ;
								print __($guid, "Type") ;
							print "</th>" ;
							print "<th>" ;
								print __($guid, "Date<br/>Complete") ;
							print "</th>" ;
							print "<th>" ;
								print __($guid, "Viewable <br/>to Students") ;
							print "</th>" ;
							print "<th>" ;
								print __($guid, "Viewable <br/>to Parents") ;
							print "</th>" ;
							print "<th>" ;
								print __($guid, "Actions") ;
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
									print "<b>" . $row["name"] . "</b><br/>" ;
									$unit=getUnit($connection2, $row["gibbonUnitID"], $row["gibbonHookID"], $row["gibbonCourseClassID"]) ;
									if (isset($unit[0])) {
										print $unit[0] ;
									}
									if (isset($unit[1])) {
										print "<br/><i>" . $unit[1] . " " . __($guid, 'Unit') . "</i>" ;
									}
								print "</td>" ;
								print "<td>" ;
									print $row["type"] ;
								print "</td>" ;
								print "<td>" ;
									if ($row["complete"]=="Y") {
										print dateConvertBack($guid, $row["completeDate"]) ;
									}
								print "</td>" ;
								print "<td>" ;
									print $row["viewableStudents"] ;
								print "</td>" ;
								print "<td>" ;
									print $row["viewableParents"] ;
								print "</td>" ;
								print "<td>" ;
									print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/markbook_edit_edit.php&gibbonCourseClassID=$gibbonCourseClassID&gibbonMarkbookColumnID=" . $row["gibbonMarkbookColumnID"] . "'><img title='" . __($guid, 'Edit') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/config.png'/></a> " ;
									print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/markbook_edit_delete.php&gibbonCourseClassID=$gibbonCourseClassID&gibbonMarkbookColumnID=" . $row["gibbonMarkbookColumnID"] . "'><img title='" . __($guid, 'Delete') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/garbage.png'/></a> " ;
									print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/markbook_edit_data.php&gibbonCourseClassID=$gibbonCourseClassID&gibbonMarkbookColumnID=" . $row["gibbonMarkbookColumnID"] . "'><img title='" . __($guid, 'Enter Data') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/markbook.png'/></a> " ;
									print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/modules/Markbook/markbook_viewExport.php?gibbonMarkbookColumnID=" . $row["gibbonMarkbookColumnID"] . "&gibbonCourseClassID=$gibbonCourseClassID&return=markbook_edit.php'><img title='" . __($guid, 'Export to Excel') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/download.png'/></a>" ;
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
?>