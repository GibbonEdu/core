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

if (isActionAccessible($guid, $connection2, "/modules/Timetable/spaceChange_manage_delete.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Get action with highest precendence
	$highestAction=getHighestGroupedAction($guid, $_GET["q"], $connection2) ;
	if ($highestAction==FALSE) {
		print "<div class='error'>" ;
		print __($guid, "The highest grouped action cannot be determined.") ;
		print "</div>" ;
	}
	else {
		//Proceed!
		print "<div class='trail'>" ;
		print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/spaceChange_manage.php'>" . __($guid, 'Manage Facility Changes') . "</a> > </div><div class='trailEnd'>" . __($guid, 'Delete Facility Change') . "</div>" ;
		print "</div>" ;
	
		if (isset($_GET["deleteReturn"])) { $deleteReturn=$_GET["deleteReturn"] ; } else { $deleteReturn="" ; }
		$deleteReturnMessage="" ;
		$class="error" ;
		if (!($deleteReturn=="")) {
			if ($deleteReturn=="fail0") {
				$deleteReturnMessage=__($guid, "Your request failed because you do not have access to this action.") ;	
			}
			else if ($deleteReturn=="fail1") {
				$deleteReturnMessage=__($guid, "Your request failed because your inputs were invalid.") ;	
			}
			else if ($deleteReturn=="fail2") {
				$deleteReturnMessage=__($guid, "Your request failed due to a database error.") ;	
			}
			else if ($deleteReturn=="fail3") {
				$deleteReturnMessage=__($guid, "Your request failed because your inputs were invalid.") ;	
			}
			print "<div class='$class'>" ;
				print $deleteReturnMessage;
			print "</div>" ;
		} 
	
		//Check if school year specified
		$gibbonTTSpaceChangeID=$_GET["gibbonTTSpaceChangeID"] ;
		if ($gibbonTTSpaceChangeID=="") {
			print "<div class='error'>" ;
				print __($guid, "You have not specified one or more required parameters.") ;
			print "</div>" ;
		}
		else {
			try {
				if ($highestAction=="Manage Space Changes_allClasses") {
					$data=array("gibbonTTSpaceChangeID"=>$gibbonTTSpaceChangeID); 
					$sql="SELECT gibbonTTSpaceChangeID, gibbonTTSpaceChange.date, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, spaceOld.name AS spaceOld, spaceNew.name AS spaceNew FROM gibbonTTSpaceChange JOIN gibbonTTDayRowClass ON (gibbonTTSpaceChange.gibbonTTDayRowClassID=gibbonTTDayRowClass.gibbonTTDayRowClassID) JOIN gibbonCourseClass ON (gibbonTTDayRowClass.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) LEFT JOIN gibbonSpace AS spaceOld ON (gibbonTTDayRowClass.gibbonSpaceID=spaceOld.gibbonSpaceID) LEFT JOIN gibbonSpace AS spaceNew ON (gibbonTTSpaceChange.gibbonSpaceID=spaceNew.gibbonSpaceID) WHERE gibbonTTSpaceChangeID=:gibbonTTSpaceChangeID ORDER BY date, course, class" ; 
				}
				else {
					$data=array("gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"], "gibbonTTSpaceChangeID"=>$gibbonTTSpaceChangeID); 
					$sql="SELECT gibbonTTSpaceChangeID, gibbonTTSpaceChange.date, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, spaceOld.name AS spaceOld, spaceNew.name AS spaceNew FROM gibbonTTSpaceChange JOIN gibbonTTDayRowClass ON (gibbonTTSpaceChange.gibbonTTDayRowClassID=gibbonTTDayRowClass.gibbonTTDayRowClassID)  JOIN gibbonCourseClass ON (gibbonTTDayRowClass.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) LEFT JOIN gibbonSpace AS spaceOld ON (gibbonTTDayRowClass.gibbonSpaceID=spaceOld.gibbonSpaceID) LEFT JOIN gibbonSpace AS spaceNew ON (gibbonTTSpaceChange.gibbonSpaceID=spaceNew.gibbonSpaceID) WHERE gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND gibbonTTSpaceChangeID=:gibbonTTSpaceChangeID ORDER BY date, course, class" ; 
				}
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
				//Let's go!
				$row=$result->fetch() ;
				?>
				<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/spaceChange_manage_deleteProcess.php?gibbonTTSpaceChangeID=$gibbonTTSpaceChangeID" ?>">
					<table class='smallIntBorder fullWidth' cellspacing='0'>	
						<tr>
							<td> 
								<b><?php print __($guid, 'Are you sure you want to delete this record?') ; ?></b><br/>
								<span style="font-size: 90%; color: #cc0000"><i><?php print __($guid, 'This operation cannot be undone, and may lead to loss of vital data in your system. PROCEED WITH CAUTION!') ; ?></span>
							</td>
							<td class="right">
							
							</td>
						</tr>
						<tr>
							<td> 
								<input type="hidden" name="address" value="<?php print $_SESSION[$guid]["address"] ?>">
								<input type="submit" value="<?php print __($guid, 'Yes') ; ?>">
							</td>
							<td class="right">
							
							</td>
						</tr>
					</table>
				</form>
				<?php
			}
		}
	}
}
?>