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

//Search & Filters
$search=NULL ;
if (isset($_GET["search"])) {
	$search=$_GET["search"] ;
}
$filter2=NULL ;
if (isset($_GET["filter2"])) {
	$filter2=$_GET["filter2"] ;
}

if (isActionAccessible($guid, $connection2, "/modules/Rubrics/rubrics_duplicate.php")==FALSE) {
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
		if ($highestAction!="Manage Rubrics_viewEditAll" AND $highestAction!="Manage Rubrics_viewAllEditLearningArea") {
			print "<div class='error'>" ;
				print __($guid, "You do not have access to this action.") ;
			print "</div>" ;
		}
		else {
			//Proceed!
			print "<div class='trail'>" ;
			print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/rubrics.php&search=$search&filter2=$filter2'>" . __($guid, 'Manage Rubrics') . "</a> > </div><div class='trailEnd'>" . __($guid, 'Duplicate Rubric') . "</div>" ;
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
				else if ($updateReturn=="fail5") {
					$updateReturnMessage=__($guid, "Your request was successful, but some data was not properly saved.") ;
				}
				else if ($updateReturn=="success0") {
					$updateReturnMessage=__($guid, "Your request was completed successfully.") ;	
					$class="success" ;
				}
				print "<div class='$class'>" ;
					print $updateReturnMessage;
				print "</div>" ;
			} 
			
			//Check if school year specified
			$gibbonRubricID=$_GET["gibbonRubricID"];
			if ($gibbonRubricID=="") {
				print "<div class='error'>" ;
					print __($guid, "You have not specified one or more required parameters.") ;
				print "</div>" ;
			}
			else {
				try {
					$data=array("gibbonRubricID"=>$gibbonRubricID); 
					$sql="SELECT * FROM gibbonRubric WHERE gibbonRubricID=:gibbonRubricID" ;
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
					//Let's go!
					$row=$result->fetch() ;
					
					if ($search!="" OR $filter2!="") {
						print "<div class='linkTop'>" ;
							print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Rubrics/rubrics.php&search=$search&filter2=$filter2'>" . __($guid, 'Back to Search Results') . "</a>" ;
						print "</div>" ;
					}
					?>
					<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/rubrics_duplicateProcess.php?gibbonRubricID=$gibbonRubricID&search=$search&filter2=$filter2" ?>">
						<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
							<tr class='break'>
								<td colspan=2>
									<h3><?php print __($guid, 'Rubric Basics') ?></h3>
								</td>
							</tr>
							<tr>
								<td style='width: 275px'> 
									<b><?php print __($guid, 'Scope') ?> *</b><br/>
									<span style="font-size: 90%"><i></i></span>
								</td>
								<td class="right">
									<?php
									if ($highestAction=="Manage Rubrics_viewEditAll") {
										?>
										<select name="scope" id="scope" style="width: 302px">
											<option value="Please select..."><?php print __($guid, 'Please select...') ?></option>
											<option value="School"><?php print __($guid, 'School') ?></option>
											<option value="Learning Area"><?php print __($guid, 'Learning Area') ?></option>
										</select>
										<script type="text/javascript">
											var scope=new LiveValidation('scope');
											scope.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print __($guid, 'Select something!') ?>"});
										</script>
										 <?php
									}
									else if ($highestAction=="Manage Rubrics_viewAllEditLearningArea") {
										?>
										<input readonly name="scope" id="scope" value="Learning Area" type="text" style="width: 300px">
										<?php
									}
									?>
								</td>
							</tr>
					
							<?php
							if ($highestAction=="Manage Rubrics_viewEditAll") {
								?>
								<script type="text/javascript">
									$(document).ready(function(){
										$("#learningAreaRow").css("display","none");
								
										$("#scope").change(function(){
											if ($('#scope option:selected').val()=="Learning Area" ) {
												$("#learningAreaRow").slideDown("fast", $("#learningAreaRow").css("display","table-row")); 
												gibbonDepartmentID.enable();
											}
											else {
												$("#learningAreaRow").css("display","none");
												gibbonDepartmentID.disable();
											}
										 });
									});
								</script>
								<?php
							}
							?>
							<tr id='learningAreaRow'>
								<td> 
									<b><?php print __($guid, 'Learning Area') ?> *</b><br/>
									<span style="font-size: 90%"><i></i></span>
								</td>
								<td class="right">
									<select name="gibbonDepartmentID" id="gibbonDepartmentID" style="width: 302px">
										<option value="Please select..."><?php print __($guid, 'Please select...') ?></option>
										<?php
										try {
											if ($highestAction=="Manage Rubrics_viewEditAll") {
												$dataSelect=array(); 
												$sqlSelect="SELECT * FROM gibbonDepartment WHERE type='Learning Area' ORDER BY name" ;
											}
											else if ($highestAction=="Manage Rubrics_viewAllEditLearningArea") {
												$dataSelect=array("gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
												$sqlSelect="SELECT * FROM gibbonDepartment JOIN gibbonDepartmentStaff ON (gibbonDepartmentStaff.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) WHERE gibbonPersonID=:gibbonPersonID AND (role='Coordinator' OR role='Teacher (Curriculum)') AND type='Learning Area' ORDER BY name" ;
											}
											$resultSelect=$connection2->prepare($sqlSelect);
											$resultSelect->execute($dataSelect);
										}
										catch(PDOException $e) { }
										while ($rowSelect=$resultSelect->fetch()) {
											print "<option value='" . $rowSelect["gibbonDepartmentID"] . "'>" . $rowSelect["name"] . "</option>" ;
										}
										?>
									</select>
									<script type="text/javascript">
										var gibbonDepartmentID=new LiveValidation('gibbonDepartmentID');
										gibbonDepartmentID.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print __($guid, 'Select something!') ?>"});
										<?php
										if ($highestAction=="Manage Rubrics_viewEditAll") {
											print "gibbonDepartmentID.disable();" ;
										}
										?>
									</script>
								</td>
							</tr>
							<tr>
								<td> 
									<b><?php print __($guid, 'Name') ?> *</b><br/>
								</td>
								<td class="right">
									<input name="name" id="name" maxlength=50 value="<?php print $row["name"] ?>" type="text" style="width: 300px">
									<script type="text/javascript">
										var name2=new LiveValidation('name');
										name2.add(Validate.Presence);
									</script>
								</td>
							</tr>
							<tr>
								<td>
									<span style="font-size: 90%"><i>* <?php print __($guid, "denotes a required field") ; ?></i></span>
								</td>
								<td class="right">
									<input type="hidden" name="address" value="<?php print $_SESSION[$guid]["address"] ?>">
									<input type="submit" value="<?php print __($guid, "Submit") ; ?>">
								</td>
							</tr>
						</table>
					</form>
					<?php
				}
			}
		}
	}
}
?>