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

if (isActionAccessible($guid, $connection2, "/modules/Formal Assessment/internalAssessment_manage_delete.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Check if school year specified
	$gibbonCourseClassID=$_GET["gibbonCourseClassID"];
	$gibbonInternalAssessmentColumnID=$_GET["gibbonInternalAssessmentColumnID"] ;
	if ($gibbonCourseClassID=="" OR $gibbonInternalAssessmentColumnID=="") {
		print "<div class='error'>" ;
			print _("You have not specified one or more required parameters.") ;
		print "</div>" ;
	}
	else {
		try {
			$data=array("gibbonCourseClassID"=>$gibbonCourseClassID); 
			$sql="SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourseClass.gibbonCourseClassID, gibbonCourse.gibbonDepartmentID, gibbonYearGroupIDList FROM gibbonCourse, gibbonCourseClass WHERE gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID ORDER BY course, class" ;
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
				$data2=array("gibbonInternalAssessmentColumnID"=>$gibbonInternalAssessmentColumnID); 
				$sql2="SELECT * FROM gibbonInternalAssessmentColumn WHERE gibbonInternalAssessmentColumnID=:gibbonInternalAssessmentColumnID" ;
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
				//Let's go!
				$row=$result->fetch() ;
				$row2=$result2->fetch() ;
			
				print "<div class='trail'>" ;
				print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . _(getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/internalAssessment_manage.php&gibbonCourseClassID=" . $_GET["gibbonCourseClassID"] . "'>" . _('Manage') . " " . $row["course"] . "." . $row["class"] . " " . _('Internal Assessments') . "</a> > </div><div class='trailEnd'>" . _('Delete Column') . "</div>" ;
				print "</div>" ;
			
				if (isset($_GET["deleteReturn"])) { $deleteReturn=$_GET["deleteReturn"] ; } else { $deleteReturn="" ; }
				$deleteReturnMessage="" ;
				$class="error" ;
				if (!($deleteReturn=="")) {
					if ($deleteReturn=="fail0") {
						$deleteReturnMessage=_("Your request failed because you do not have access to this action.") ;	
					}
					else if ($deleteReturn=="fail1") {
						$deleteReturnMessage=_("Your request failed because your inputs were invalid.") ;	
					}
					else if ($deleteReturn=="fail2") {
						$deleteReturnMessage=_("Your request failed due to a database error.") ;	
					}
					else if ($deleteReturn=="fail3") {
						$deleteReturnMessage=_("Your request failed because your inputs were invalid.") ;	
					}
					print "<div class='$class'>" ;
						print $deleteReturnMessage;
					print "</div>" ;
				}
				?>
				<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/internalAssessment_manage_deleteProcess.php?gibbonInternalAssessmentColumnID=$gibbonInternalAssessmentColumnID" ?>">
					<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
						<tr>
							<td> 
								<b><?php print _('Are you sure you want to delete this record?') ; ?></b><br/>
								<span style="font-size: 90%; color: #cc0000"><i><?php print _('This operation cannot be undone, and may lead to loss of vital data in your system. PROCEED WITH CAUTION!') ; ?></i></span>
							</td>
							<td class="right">
						
							</td>
						</tr>
						<tr>
							<td> 
								<input name="gibbonCourseClassID" id="gibbonCourseClassID" value="<?php print $gibbonCourseClassID ?>" type="hidden">
								<input type="hidden" name="address" value="<?php print $_SESSION[$guid]["address"] ?>">
								<input type="submit" value="<?php print _('Yes') ; ?>">
							</td>
							<td class="right">
						
							</td>
						</tr>
					</table>
				</form>
				<?php
			}
		}
	
		//Print sidebar
		$_SESSION[$guid]["sidebarExtra"]=sidebarExtra($guid, $connection2, $gibbonCourseClassID) ;
	}
}
?>