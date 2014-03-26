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

//Rubric includes
include "./modules/Rubrics/moduleFunctions.php" ;

@session_start() ;

if (isActionAccessible($guid, $connection2, "/modules/Markbook/markbook_view.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print "You do not have access to this page." ;
	print "</div>" ;
}
else {
	//Proceed!
	//Check if school year specified
	$gibbonCourseClassID=$_GET["gibbonCourseClassID"] ;
	$gibbonMarkbookColumnID=$_GET["gibbonMarkbookColumnID"] ;
	$gibbonPersonID=$_GET["gibbonPersonID"] ;
	$gibbonRubricID=$_GET["gibbonRubricID"] ;
	if ($gibbonCourseClassID=="" OR $gibbonMarkbookColumnID=="" OR $gibbonPersonID=="" OR $gibbonRubricID=="") {
		print "<div class='error'>" ;
			print _("You have not specified one or more required parameters.") ;
		print "</div>" ;
	}
	else {
		$roleCategory=getRoleCategory($_SESSION[$guid]["gibbonRoleIDPrimary"], $connection2) ;
		$contextDBTableGibbonRubricIDField="gibbonRubricID" ;
		if ($_GET["type"]=="attainment") {
			$contextDBTableGibbonRubricIDField="gibbonRubricIDAttainment" ;
		}
		else if ($_GET["type"]=="effort") {
			$contextDBTableGibbonRubricIDField="gibbonRubricIDEffort" ;
		}
		
		
		try {
			if ($roleCategory=="Staff") {
				$data=array("gibbonCourseClassID"=>$gibbonCourseClassID); 
				$sql="SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourseClass.gibbonCourseClassID FROM gibbonCourse, gibbonCourseClass WHERE gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID ORDER BY course, class" ;
			}
			else if ($roleCategory=="Student") {
				$data=array("gibbonPersonID"=>$gibbonPersonID, "gibbonCourseClassID"=>$gibbonCourseClassID); 
				$sql="SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourseClass.gibbonCourseClassID FROM gibbonCourse, gibbonCourseClass, gibbonCourseClassPerson WHERE gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID AND gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND role='Student' AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID ORDER BY course, class" ;
			}
			else if ($roleCategory=="Parent") {
				$data=array("gibbonPersonID"=>$gibbonPersonID, "gibbonCourseClassID"=>$gibbonCourseClassID); 
				$sql="SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourseClass.gibbonCourseClassID FROM gibbonCourse, gibbonCourseClass, gibbonCourseClassPerson WHERE gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID AND gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND role='Student' AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID ORDER BY course, class" ;
			}
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
		}
		
		if ($result->rowCount()!=1) {
			print "<div class='error'>" ;
				print _("The selected record does not exist, or you do not have access to it.") ;
			print "</div>" ;
		}
		else {
			try {
				$data2=array("gibbonMarkbookColumnID"=>$gibbonMarkbookColumnID); 
				$sql2="SELECT * FROM gibbonMarkbookColumn WHERE gibbonMarkbookColumnID=:gibbonMarkbookColumnID" ;
				$result2=$connection2->prepare($sql2);
				$result2->execute($data2);
			}
			catch(PDOException $e) { 
				print "<div class='error'>" . $e->getMessage() . "</div>" ; 
			}

			if ($result2->rowCount()!=1) {
				print "<div class='error'>" ;
					print _("The selected record does not exist, or you do not have access to it.") ;
				print "</div>" ;
			}
			else {
				try {
					$data3=array("gibbonRubricID"=>$gibbonRubricID); 
					$sql3="SELECT * FROM gibbonRubric WHERE gibbonRubricID=:gibbonRubricID" ;
					$result3=$connection2->prepare($sql3);
					$result3->execute($data3);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
			
				if ($result3->rowCount()!=1) {
					print "<div class='error'>" ;
						print _("The specified record does not exist.") ;
					print "</div>" ;
				}
				else {
					try {
						$data4=array("gibbonPersonID"=>$gibbonPersonID, "gibbonCourseClassID"=>$gibbonCourseClassID); 
						$sql4="SELECT surname, preferredName, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPerson.gibbonPersonID=:gibbonPersonID AND gibbonCourseClassID=:gibbonCourseClassID AND status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND role='Student'" ;
						$result4=$connection2->prepare($sql4);
						$result4->execute($data4);
					}
					catch(PDOException $e) { 
						print "<div class='error'>" . $e->getMessage() . "</div>" ; 
					}

					if ($result4->rowCount()!=1) {
						print "<div class='error'>" ;
							print "The selected student does not exist or is not in this class." ;
						print "</div>" ;
					}
					else {
						//Let's go!
						$row=$result->fetch() ;
						$row2=$result2->fetch() ;
						$row3=$result3->fetch() ;
						$row4=$result4->fetch() ;
						
						print "<h2 style='margin-bottom: 10px;'>" ;
							print $row3["name"] . "<br/>" ;
							print "<span style='font-size: 65%; font-style: italic'>" . formatName("", $row4["preferredName"], $row4["surname"], "Student", true) . "</span>" ;
						print "</h2>" ;
						
						$mark=TRUE ;
						if (isset($_GET["mark"])) {
							if ($_GET["mark"]=="FALSE") {
								$mark=FALSE ;
							}
						}
						print rubricView($guid, $connection2, $gibbonRubricID, $mark, $row4["gibbonPersonID"], "gibbonMarkbookColumn", "gibbonMarkbookColumnID", $gibbonMarkbookColumnID,  $contextDBTableGibbonRubricIDField, "name", "completeDate") ;
					}
				}
			}
		}
	}	
}	
?>