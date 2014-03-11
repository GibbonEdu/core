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

//Module includes
include "./modules/" . $_SESSION[$guid]["module"] . "/moduleFunctions.php" ;

if (isActionAccessible($guid, $connection2, "/modules/Planner/planner_bump.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print "You do not have access to this action." ;
	print "</div>" ;
}
else {
	$highestAction=getHighestGroupedAction($guid, $_GET["q"], $connection2) ;
	if ($highestAction==FALSE) {
		print "<div class='error'>" ;
		print "The highest grouped action cannot be determined." ;
		print "</div>" ;
	}
	else {
		//Set variables
		$today=date("Y-m-d");
			
		//Proceed!
		//Get viewBy, date and class variables
		$params="" ;
		$viewBy=NULL ;
		if (isset($_GET["viewBy"])) {
			$viewBy=$_GET["viewBy"] ;
		}
		$subView=NULL ;
		if (isset($_GET["subView"])) {
			$subView=$_GET["subView"] ;
		}
		if ($viewBy!="date" AND $viewBy!="class") {
			$viewBy="date" ;
		}
		$gibbonCourseClassID=NULL ;
		$date=NULL ;
		$dateStamp=NULL ;
		if ($viewBy=="class") {
			$class=NULL ;
			if (isset($_GET["class"])) {
				$class=$_GET["class"] ;
			}
			$gibbonCourseClassID=$_GET["gibbonCourseClassID"] ;
			$params="&viewBy=class&class=$class&gibbonCourseClassID=$gibbonCourseClassID&subView=$subView" ;
		}
		
		if ($viewBy=="date") {
			print "<div class='error'>" ;
				print "You do not have access to this action." ;
			print "</div>" ;
		}
		else {
			list($todayYear, $todayMonth, $todayDay)=explode('-', $today);
			$todayStamp=mktime(0, 0, 0, $todayMonth, $todayDay, $todayYear);
			
			//Check if school year specified
			$gibbonCourseClassID=$_GET["gibbonCourseClassID"];
			$gibbonPlannerEntryID=$_GET["gibbonPlannerEntryID"] ;
			if ($gibbonPlannerEntryID=="" OR ($viewBy=="class" AND $gibbonCourseClassID=="Y")) {
				print "<div class='error'>" ;
					print "You have not specified one or more required parameters." ;
				print "</div>" ;
			}
			else {
				$proceed=TRUE ;
				try {
					if ($highestAction=="Lesson Planner_viewEditAllClasses" ) {
						$data=array("gibbonCourseClassID"=>$gibbonCourseClassID, "gibbonPlannerEntryID"=>$gibbonPlannerEntryID); 
						$sql="SELECT gibbonPlannerEntryID, gibbonUnitID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonPlannerEntry.gibbonCourseClassID=:gibbonCourseClassID AND gibbonPlannerEntryID=:gibbonPlannerEntryID" ;
					}
					else {
						$data=array("gibbonCourseClassID"=>$gibbonCourseClassID, "gibbonPlannerEntryID"=>$gibbonPlannerEntryID, "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
						$sql="SELECT gibbonPlannerEntryID, gibbonUnitID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, role FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND role='Teacher' AND gibbonPlannerEntry.gibbonCourseClassID=:gibbonCourseClassID AND gibbonPlannerEntryID=:gibbonPlannerEntryID" ;
					}
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				
				if ($result->rowCount()!=1) {
					print "<div class='error'>" ;
						print "The selected planner entry does not exist, or you do not have access to it." ;
					print "</div>" ;
				}
				else {
					//Let's go!
					$row=$result->fetch() ;
					$extra=$row["course"] . "." . $row["class"] ;
					
					print "<div class='trail'>" ;
					print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/planner.php$params'>Planner $extra</a> > </div><div class='trailEnd'>Bump Forward Lesson Plan</div>" ;
					print "</div>" ;
					
					//Proceed!
					if (isset($_GET["bumpReturn"])) { $bumpReturn=$_GET["bumpReturn"] ; } else { $bumpReturn="" ; }
					$bumpReturnMessage ="" ;
					$class="error" ;
					if (!($bumpReturn=="")) {
						if ($bumpReturn=="fail0") {
							$bumpReturnMessage =_("Your request failed because you do not have access to this action.") ;	
						}
						else if ($bumpReturn=="fail1") {
							$bumpReturnMessage =_("Your request failed because your inputs were invalid.") ;	
						}
						else if ($bumpReturn=="fail2") {
							$bumpReturnMessage =_("Your request failed due to a database error.") ;	
						}
						else if ($bumpReturn=="fail3") {
							$bumpReturnMessage =_("Your request failed because your inputs were invalid.") ;	
						}
						print "<div class='$class'>" ;
							print $bumpReturnMessage;
						print "</div>" ;
					} 
					?>
					<form method="post" action="<? print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/planner_bumpProcess.php?gibbonPlannerEntryID=$gibbonPlannerEntryID" ?>">
						<table cellspacing='0' style="width: 100%">	
							<tr>
								<td> 
									Pressing "Yes" below will move this lesson, and all succeeding lessons in this class, forward to the next available time slot. <b>Are you sure you want to bump "<? print $row["name"] ?>" forward?</b><br/>
								</td>
								<td class="right">
									
								</td>
							</tr>
							<tr>
								<td> 
									<input name="viewBy" id="viewBy" value="<? print $viewBy ?>" type="hidden">
									<input name="subView" id="subView" value="<? print $subView ?>" type="hidden">
									<input name="date" id="date" value="<? print $date ?>" type="hidden">
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
			//Print sidebar
			$_SESSION[$guid]["sidebarExtra"]=sidebarExtra($guid, $connection2, $todayStamp, $_SESSION[$guid]["gibbonPersonID"], $dateStamp, $gibbonCourseClassID ) ;
		}
	}
}
?>