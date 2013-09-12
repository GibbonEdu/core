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

session_start() ;

//Module includes
include "./modules/" . $_SESSION[$guid]["module"] . "/moduleFunctions.php" ;

if (isActionAccessible($guid, $connection2, "/modules/Markbook/markbook_edit_delete.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print "You do not have access to this action." ;
	print "</div>" ;
}
else {
	//Check if school year specified
	$gibbonCourseClassID=$_GET["gibbonCourseClassID"];
	$gibbonMarkbookColumnID=$_GET["gibbonMarkbookColumnID"] ;
	if ($gibbonCourseClassID=="" OR $gibbonMarkbookColumnID=="") {
		print "<div class='error'>" ;
			print "You have not specified a class or a markbook column." ;
		print "</div>" ;
	}
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
			print "<div class='error'>" ;
				print "The selected class does not exist, or you do not have access to it." ;
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
					print "The selected column does not exist, or you do not have access to it." ;
				print "</div>" ;
			}
			else {
				//Let's go!
				$row=$result->fetch() ;
				$row2=$result2->fetch() ;
				
				print "<div class='trail'>" ;
				print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>Home</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/markbook_edit.php&gibbonCourseClassID=" . $_GET["gibbonCourseClassID"] . "'>Edit " . $row["course"] . "." . $row["class"] . " Markbook</a> > </div><div class='trailEnd'>Delete Column</div>" ;
				print "</div>" ;
				
				if ($row2["groupingID"]!="" AND $row2["gibbonPersonIDCreator"]!=$_SESSION[$guid]["gibbonPersonID"]) {
					print "<div class='error'>" ;
						print "This column is part of a set of columns, and so can not be individually deleted." ;
					print "</div>" ;
				}
				else {
					$deleteReturn = $_GET["deleteReturn"] ;
					$deleteReturnMessage ="" ;
					$class="error" ;
					if (!($deleteReturn=="")) {
						if ($deleteReturn=="fail0") {
							$deleteReturnMessage ="Update failed because you do not have access to this action." ;	
						}
						else if ($deleteReturn=="fail1") {
							$deleteReturnMessage ="Update failed because a required parameter was not set." ;	
						}
						else if ($deleteReturn=="fail2") {
							$deleteReturnMessage ="Update failed due to a database error." ;	
						}
						else if ($deleteReturn=="fail3") {
							$deleteReturnMessage ="Update failed because your inputs were invalid." ;	
						}
						print "<div class='$class'>" ;
							print $deleteReturnMessage;
						print "</div>" ;
					} 
					?>
					<form method="post" action="<? print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/markbook_edit_deleteProcess.php?gibbonMarkbookColumnID=$gibbonMarkbookColumnID" ?>">
						<table style="width: 100%">	
							<tr>
								<td> 
									<b>Are you sure you want to delete column "<? print $row2["name"] ?>"?</b><br/>
									<span style="font-size: 90%; color: #cc0000"><i>This operation cannot be undone, and may lead to loss of vital data in your system.<br/>PROCEED WITH CAUTION!</i></span>
								</td>
								<td class="right">
								
								</td>
							</tr>
							<tr>
								<td> 
									<input name="gibbonCourseClassID" id="gibbonCourseClassID" value="<? print $gibbonCourseClassID ?>" type="hidden">
									<input type="hidden" name="address" value="<? print $_SESSION[$guid]["address"] ?>">
									<input type="submit" value="Yes">
								</td>
								<td class="right">
								
								</td>
							</tr>
						</table>
					</form>
					<?
				}
			}
		}
	}
	
	//Print sidebar
	$_SESSION[$guid]["sidebarExtra"]=sidebarExtra($guid, $connection2, $gibbonCourseClassID) ;
}
?>