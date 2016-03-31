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


if (isActionAccessible($guid, $connection2, "/modules/Students/applicationForm_manage_reject.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Students/applicationForm_manage.php&gibbonSchoolYearID=" . $_GET["gibbonSchoolYearID"] . "'>" . __($guid, 'Manage Applications') . "</a> > </div><div class='trailEnd'>" . __($guid, 'Reject Application') . "</div>" ;
	print "</div>" ;
	
	//Check if school year specified
	$gibbonApplicationFormID=$_GET["gibbonApplicationFormID"];
	$gibbonSchoolYearID=$_GET["gibbonSchoolYearID"] ;
	$search=$_GET["search"] ;
	if ($gibbonApplicationFormID=="" OR $gibbonSchoolYearID=="") {
		print "<div class='error'>" ;
			print __($guid, "You have not specified one or more required parameters.") ;
		print "</div>" ;
	}
	else {
		try {
			$data=array("gibbonApplicationFormID"=>$gibbonApplicationFormID); 
			$sql="SELECT * FROM gibbonApplicationForm WHERE gibbonApplicationFormID=:gibbonApplicationFormID" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
		}

		if ($result->rowCount()!=1) {
			print "<div class='error'>" ;
				print __($guid, "The specified record does not exist.") ;
			print "</div>" ;
		}
		else {
			if (isset($_GET["rejectReturn"])) { $rejectReturn=$_GET["rejectReturn"] ; } else { $rejectReturn="" ; }
			$rejectReturnMessage="" ;
			$class="error" ;
			if (!($rejectReturn=="")) {
				if ($rejectReturn=="fail0") {
					$rejectReturnMessage=__($guid, "Your request failed because you do not have access to this action.") ;	
				}
				else if ($rejectReturn=="fail1") {
					$rejectReturnMessage=__($guid, "Your request failed because your inputs were invalid.") ;	
				}
				else if ($rejectReturn=="fail2") {
					$rejectReturnMessage=__($guid, "Your request failed due to a database error.") ;	
				}
				else if ($rejectReturn=="fail3") {
					$rejectReturnMessage=__($guid, "Your request failed because your inputs were invalid.") ;	
				}
				else if ($rejectReturn=="success1") {
					$rejectReturnMessage=__($guid, "Your request was completed successfully., but status could not be updated.") ;	
				}
				print "<div class='$class'>" ;
					print $rejectReturnMessage;
				print "</div>" ;
			} 

			//Let's go!
			$row=$result->fetch() ;
			$proceed=TRUE ;
			
			print "<div class='linkTop'>" ;
				if ($search!="") {
					print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Students/applicationForm_manage.php&gibbonSchoolYearID=$gibbonSchoolYearID&search=$search'>" . __($guid, 'Back to Search Results') . "</a>" ;
				}
			print "</div>" ;
			
			?>
			<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/applicationForm_manage_rejectProcess.php?gibbonApplicationFormID=$gibbonApplicationFormID&search=$search" ?>">
				<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
					<tr>
						<td> 
							<b><?php print sprintf(__($guid, 'Are you sure you want to reject the application for %1$s?'), formatName("", $row["preferredName"], $row["surname"], "Student")) ?></b><br/>
						</td>
					</tr>
					<tr>
						<td class="right"> 
							<input name="gibbonSchoolYearID" id="gibbonSchoolYearID" value="<?php print $gibbonSchoolYearID ?>" type="hidden">
							<input name="gibbonApplicationFormID" id="gibbonApplicationFormID" value="<?php print $gibbonApplicationFormID ?>" type="hidden">
							<input type="hidden" name="address" value="<?php print $_SESSION[$guid]["address"] ?>">
							<input type="submit" value="<?php print __($guid, 'Yes') ; ?>">
						</td>
					</tr>
				</table>
			</form>				
			<?php
		}
	}
}
?>