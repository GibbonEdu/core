<?
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

if (isActionAccessible($guid, $connection2, "/modules/Timetable Admin/courseEnrolment_manage.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print "You do not have access to this action." ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > </div><div class='trailEnd'>Course Enrolment by Class</div>" ;
	print "</div>" ;
	
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
				print "The specified year does not exist." ;
			print "</div>" ;
		}
		else {
			$row=$result->fetch() ;
			$gibbonSchoolYearID=$row["gibbonSchoolYearID"] ;
			$gibbonSchoolYearName=$row["name"] ;
		}
	}
	
	if ($gibbonSchoolYearID!="") {
		print "<h2>" ;
			print $gibbonSchoolYearName ;
		print "</h2>" ;
		
		print "<div class='linkTop'>" ;
			//Print year picker
			if (getPreviousSchoolYearID($gibbonSchoolYearID, $connection2)!=FALSE) {
				print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/courseEnrolment_manage.php&gibbonSchoolYearID=" . getPreviousSchoolYearID($gibbonSchoolYearID, $connection2) . "'>Previous Year</a> " ;
			}
			else {
				print "Previous Year " ;
			}
			print " | " ;
			if (getNextSchoolYearID($gibbonSchoolYearID, $connection2)!=FALSE) {
				print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/courseEnrolment_manage.php&gibbonSchoolYearID=" . getNextSchoolYearID($gibbonSchoolYearID, $connection2) . "'>Next Year</a> " ;
			}
			else {
				print "Next Year " ;
			}
		print "</div>" ;
		
		
		try {
			$data=array("gibbonSchoolYearID"=>$gibbonSchoolYearID); 
			$sql="SELECT * FROM gibbonCourse WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY nameShort" ; 
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
		}
		
		if ($result->rowCount()<1) {
			print "<div class='error'>" ;
			print _("There are no records to display.") ;
			print "</div>" ;
		}
		else {
			while ($row=$result->fetch()) {
				print "<h3>" ;
					print $row["name"] ;
				print "</h3>" ;
				
				try {
					$dataClass=array("gibbonCourseID"=>$row["gibbonCourseID"]); 
					$sqlClass="SELECT * FROM gibbonCourseClass WHERE gibbonCourseID=:gibbonCourseID ORDER BY name" ; 
					$resultClass=$connection2->prepare($sqlClass);
					$resultClass->execute($dataClass);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				
				if ($resultClass->rowCount()<1) {
					print "<div class='error'>" ;
					print "There are no classes to display." ;
					print "</div>" ;
				}
				else {
					print "<table cellspacing='0' style='width: 100%'>" ;
						print "<tr class='head'>" ;
							print "<th>" ;
								print "Name" ;
							print "</th>" ;
							print "<th>" ;
								print "Short Name" ;
							print "</th>" ;
							print "<th>" ;
								print "Participants<br/>" ;
								print "<span style='font-size: 85%; font-style: italic'>Active</span>" ;
							print "</th>" ;
							print "<th>" ;
								print "Participants<br/>" ;
								print "<span style='font-size: 85%; font-style: italic'>Expected</span>" ;
							print "</th>" ;
							print "<th>" ;
								print "Participants<br/>" ;
								print "<span style='font-size: 85%; font-style: italic'>Total</span>" ;
							print "</th>" ;
							print "<th style='width: 55px'>" ;
								print "Actions" ;
							print "</th>" ;
						print "</tr>" ;
						
						$count=0;
						$rowNum="odd" ;
						while ($rowClass=$resultClass->fetch()) {
							if ($count%2==0) {
								$rowNum="even" ;
							}
							else {
								$rowNum="odd" ;
							}
							
							//COLOR ROW BY STATUS!
							print "<tr class=$rowNum>" ;
								print "<td>" ;
									print $rowClass["name"] ;
								print "</td>" ;
								print "<td>" ;
									print $rowClass["nameShort"] ;
								print "</td>" ;
								print "<td>" ;
									$total=0 ;
									$active=0 ;
									$expected=0 ;
									try {
										$dataClasses=array("gibbonCourseClassID"=>$rowClass["gibbonCourseClassID"]); 
										$sqlClasses="SELECT gibbonCourseClassPerson.* FROM gibbonCourseClassPerson JOIN gibbonPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPerson.status='Full' AND gibbonCourseClassID=:gibbonCourseClassID AND (NOT role='Student - Left') AND (NOT role='Teacher - Left')" ;
										$resultClasses=$connection2->prepare($sqlClasses);
										$resultClasses->execute($dataClasses);
									}
									catch(PDOException $e) { 
										print "<div class='error'>" . $e->getMessage() . "</div>" ; 
									}
									if ($resultClasses->rowCount()>=0) {
										$active=$resultClasses->rowCount() ;
									}
									
									try {
										$dataClasses=array("gibbonCourseClassID"=>$rowClass["gibbonCourseClassID"]); 
										$sqlClasses="SELECT gibbonCourseClassPerson.* FROM gibbonCourseClassPerson JOIN gibbonPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPerson.status='Expected' AND gibbonCourseClassID=:gibbonCourseClassID AND (NOT role='Student - Left') AND (NOT role='Teacher - Left')" ;
										$resultClasses=$connection2->prepare($sqlClasses);
										$resultClasses->execute($dataClasses);
									}
									catch(PDOException $e) { 
										print "<div class='error'>" . $e->getMessage() . "</div>" ; 
									}
									if ($resultClasses->rowCount()>=0) {
										$expected=$resultClasses->rowCount() ;
									}
									print $active ;
								print "</td>" ;
								print "<td>" ;
									print $expected ;
								print "</td>" ;
								print "<td>" ;
									print "<b>" . ($active+$expected) . "<b/> " ;
								print "</td>" ;
								print "<td>" ;
									print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/courseEnrolment_manage_class_edit.php&gibbonCourseClassID=" . $rowClass["gibbonCourseClassID"] . "&gibbonCourseID=" . $row["gibbonCourseID"] . "&gibbonSchoolYearID=$gibbonSchoolYearID'><img title='" . _('Edit Record') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/config.png'/></a> " ;
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