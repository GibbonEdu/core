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

if (isActionAccessible($guid, $connection2, "/modules/Timetable Admin/tt_edit_day_edit_class_exception.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Check if school year specified
	$gibbonTTDayID=$_GET["gibbonTTDayID"] ;
	$gibbonTTID=$_GET["gibbonTTID"] ;
	$gibbonSchoolYearID=$_GET["gibbonSchoolYearID"] ;
	$gibbonTTColumnRowID=$_GET["gibbonTTColumnRowID"] ;
	$gibbonCourseClassID=$_GET["gibbonCourseClassID"] ;
	
	if ($gibbonTTDayID=="" OR $gibbonTTID=="" OR $gibbonSchoolYearID=="" OR $gibbonTTColumnRowID=="" OR $gibbonCourseClassID=="") {
		print "<div class='error'>" ;
			print __($guid, "You have not specified one or more required parameters.") ;
		print "</div>" ;
	}
	else {
		try {
			$data=array("gibbonTTColumnRowID"=>$gibbonTTColumnRowID, "gibbonTTDayID"=>$gibbonTTDayID, "gibbonTTColumnRowID"=>$gibbonTTColumnRowID, "gibbonCourseClassID"=>$gibbonCourseClassID); 
			$sql="SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonTTDayRowClassID FROM gibbonTTDayRowClass JOIN gibbonCourseClass ON (gibbonTTDayRowClass.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonTTColumnRowID=:gibbonTTColumnRowID AND gibbonTTDayID=:gibbonTTDayID AND gibbonTTColumnRowID=:gibbonTTColumnRowID AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
		}
		
		if ($result->rowCount()<1) {
			print "<div class='error'>" ;
				print __($guid, "The specified record cannot be found.") ;
			print "</div>" ;
		}
		else {
			//Let's go!
			$row=$result->fetch() ;
			$course=$row["course"] ;
			$class=$row["class"] ;
			$gibbonTTDayRowClassID=$row["gibbonTTDayRowClassID"] ;
			try {
				$data=array("gibbonTTDayID"=>$gibbonTTDayID, "gibbonTTID"=>$gibbonTTID, "gibbonSchoolYearID"=>$gibbonSchoolYearID, "gibbonTTColumnRowID"=>$gibbonTTColumnRowID); 
				$sql="SELECT gibbonTT.name AS ttName, gibbonTTDay.name AS dayName, gibbonTTColumnRow.name AS rowName, gibbonYearGroupIDList FROM gibbonTT JOIN gibbonTTDay ON (gibbonTT.gibbonTTID=gibbonTTDay.gibbonTTID) JOIN gibbonTTColumn ON (gibbonTTDay.gibbonTTColumnID=gibbonTTColumn.gibbonTTColumnID) JOIN gibbonTTColumnRow ON (gibbonTTColumn.gibbonTTColumnID=gibbonTTColumnRow.gibbonTTColumnID) WHERE gibbonTTDay.gibbonTTDayID=:gibbonTTDayID AND gibbonTT.gibbonTTID=:gibbonTTID AND gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonTTColumnRowID=:gibbonTTColumnRowID" ;
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				print "<div class='error'>" . $e->getMessage() . "</div>" ; 
			}
			
			if ($result->rowCount()!=1) {
				print "<div class='error'>" ;
					print __($guid, "The specified record cannot be found.") ;
				print "</div>" ;
			}
			else {
				$row=$result->fetch() ;
				
				print "<div class='trail'>" ;
				print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > ... > ... > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/tt_edit.php&gibbonTTID=$gibbonTTID&gibbonSchoolYearID=" . $_GET["gibbonSchoolYearID"] . "'>" . __($guid, 'Edit Timetable') . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/tt_edit_day_edit.php&gibbonTTDayID=$gibbonTTDayID&gibbonTTID=$gibbonTTID&gibbonSchoolYearID=$gibbonSchoolYearID'>" . __($guid, 'Edit Timetable Day') . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/tt_edit_day_edit_class.php&gibbonTTDayID=$gibbonTTDayID&gibbonTTID=$gibbonTTID&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonTTColumnRowID=$gibbonTTColumnRowID&gibbonTTDayRowClass=$gibbonTTDayRowClassID'>" . __($guid, 'Classes in Period') . "</a> > </div><div class='trailEnd'>" . __($guid, 'Class List Exception') . "</div>" ; 
				print "</div>" ;
				
				if (isset($_GET["updateReturn"])) { $updateReturn=$_GET["updateReturn"] ; } else { $updateReturn="" ; }
				$updateReturnMessage="" ;
				$class="error" ;
				if (!($updateReturn=="")) {
					if ($updateReturn=="fail0") {
						$updateReturnMessage=__($guid, "Your request failed because you do not have access to this action.") ;	
					}
					else if ($updateReturn=="fail1") {
						$updateReturnMessage=__($guid, "Your request failed because your inputs were invalid.") ;	
					}
					else if ($updateReturn=="fail2") {
						$updateReturnMessage=__($guid, "Your request failed due to a database error.") ;	
					}
					else if ($updateReturn=="fail3") {
						$updateReturnMessage=__($guid, "Your request failed because your inputs were invalid.") ;	
					}
					else if ($updateReturn=="success0") {
						$updateReturnMessage=__($guid, "Your request was completed successfully.") ;	
						$class="success" ;
					}
					print "<div class='$class'>" ;
						print $updateReturnMessage;
					print "</div>" ;
				} 
				
				if (isset($_GET["deleteReturn"])) { $deleteReturn=$_GET["deleteReturn"] ; } else { $deleteReturn="" ; }
				$deleteReturnMessage="" ;
				$class="error" ;
				if (!($deleteReturn=="")) {
					if ($deleteReturn=="success0") {
						$deleteReturnMessage=__($guid, "Your request was completed successfully.") ;		
						$class="success" ;
					}
					print "<div class='$class'>" ;
						print $deleteReturnMessage;
					print "</div>" ;
				} 
		
				print "<div class='linkTop'>" ;
				print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/tt_edit_day_edit_class_exception_add.php&gibbonTTDayID=$gibbonTTDayID&gibbonTTID=$gibbonTTID&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonTTColumnRowID=$gibbonTTColumnRowID&gibbonTTDayRowClass=$gibbonTTDayRowClassID&gibbonCourseClassID=$gibbonCourseClassID'>" .  __($guid, 'Add') . "<img style='margin-left: 5px' title='" . __($guid, 'Add') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/page_new.png'/></a>" ;
				print "</div>" ;
		
				try {
					$data=array("gibbonTTDayRowClassID"=>$gibbonTTDayRowClassID); 
					$sql="SELECT gibbonTTDayRowClassExceptionID, gibbonPerson.gibbonPersonID, surname, preferredName FROM gibbonTTDayRowClassException JOIN gibbonPerson ON (gibbonTTDayRowClassException.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonTTDayRowClassID=:gibbonTTDayRowClassID ORDER BY surname, preferredName" ; 
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
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
								print __($guid, "Name") ;
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
							$count++ ;
							
							//COLOR ROW BY STATUS!
							print "<tr class=$rowNum>" ;
								print "<td>" ;
									print formatName("", $row["preferredName"], $row["surname"], "Student", true) ;
								print "</td>" ;
								print "<td>" ;
									print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/tt_edit_day_edit_class_exception_delete.php&gibbonTTDayID=$gibbonTTDayID&gibbonTTID=$gibbonTTID&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonTTColumnRowID=$gibbonTTColumnRowID&gibbonTTDayRowClassID=$gibbonTTDayRowClassID&gibbonCourseClassID=$gibbonCourseClassID&gibbonCourseClassID=$gibbonCourseClassID&gibbonTTDayRowClassExceptionID=" . $row["gibbonTTDayRowClassExceptionID"] . "'><img title='" . __($guid, 'Delete') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/garbage.png'/></a> " ;
								print "</td>" ;
							print "</tr>" ;
						}
					print "</table>" ;
				}
			}
		}
	}
}
?>