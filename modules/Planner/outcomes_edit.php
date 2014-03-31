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


if (isActionAccessible($guid, $connection2, "/modules/Planner/outcomes_edit.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Get action with highest precendence
	$highestAction=getHighestGroupedAction($guid, $_GET["q"], $connection2) ;
	if ($highestAction==FALSE) {
		print "<div class='error'>" ;
		print "The highest grouped action cannot be determined." ;
		print "</div>" ;
	}
	else {
		if ($highestAction!="Manage Outcomes_viewEditAll" AND $highestAction!="Manage Outcomes_viewAllEditLearningArea") {
			print "<div class='error'>" ;
				print _("You do not have access to this action.") ;
			print "</div>" ;
		}
		else {
			//Proceed!
			print "<div class='trail'>" ;
			print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/outcomes.php'>Manage Outcomes</a> > </div><div class='trailEnd'>Edit Outcome</div>" ;
			print "</div>" ;
			
			if (isset($_GET["updateReturn"])) { $updateReturn=$_GET["updateReturn"] ; } else { $updateReturn="" ; }
			$updateReturnMessage="" ;
			$class="error" ;
			if (!($updateReturn=="")) {
				if ($updateReturn=="fail0") {
					$updateReturnMessage=_("Your request failed because you do not have access to this action.") ;	
				}
				else if ($updateReturn=="fail1") {
					$updateReturnMessage=_("Your request failed because your inputs were invalid.") ;	
				}
				else if ($updateReturn=="fail2") {
					$updateReturnMessage=_("Your request failed due to a database error.") ;	
				}
				else if ($updateReturn=="fail3") {
					$updateReturnMessage=_("Your request failed because your inputs were invalid.") ;	
				}
				else if ($updateReturn=="fail4") {
					$updateReturnMessage=_("Your request failed because your inputs were invalid.") ;	
				}
				else if ($updateReturn=="fail5") {
					$updateReturnMessage=_("Your request failed due to an attachment error.") ;	
				}
				else if ($updateReturn=="success0") {
					$updateReturnMessage=_("Your request was completed successfully.") ;	
					$class="success" ;
				}
				print "<div class='$class'>" ;
					print $updateReturnMessage;
				print "</div>" ;
			} 
			
			//Check if school year specified
			$gibbonOutcomeID=$_GET["gibbonOutcomeID"];
			if ($gibbonOutcomeID=="") {
				print "<div class='error'>" ;
					print _("You have not specified one or more required parameters.") ;
				print "</div>" ;
			}
			else {
				try {
					if ($highestAction=="Manage Outcomes_viewEditAll") {
						$data=array("gibbonOutcomeID"=>$gibbonOutcomeID); 
						$sql="SELECT * FROM gibbonOutcome WHERE gibbonOutcomeID=:gibbonOutcomeID" ;
					}
					else if ($highestAction=="Manage Outcomes_viewAllEditLearningArea") {
						$data=array("gibbonOutcomeID"=>$gibbonOutcomeID, "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
						$sql="SELECT * FROM gibbonOutcome JOIN gibbonDepartment ON (gibbonOutcome.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) JOIN gibbonDepartmentStaff ON (gibbonDepartmentStaff.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) AND NOT gibbonOutcome.gibbonDepartmentID IS NULL WHERE gibbonOutcomeID=:gibbonOutcomeID AND (role='Coordinator' OR role='Teacher (Curriculum)') AND gibbonPersonID=:gibbonPersonID AND scope='Learning Area'" ;
					}
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				
				if ($result->rowCount()!=1) {
					print "<div class='error'>" ;
						print _("The specified record does not exist.") ;
					print "</div>" ;
				}
				else {
					//Let's go!
					$row=$result->fetch() ;
					?>
					<form method="post" action="<? print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/outcomes_editProcess.php?gibbonOutcomeID=$gibbonOutcomeID" ?>">
						<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
							<tr>
								<td> 
									<b>Scope *</b><br/>
									<span style="font-size: 90%"><i></i></span>
								</td>
								<td class="right">
									<input readonly name="scope" id="scope" value="<? print $row["scope"] ?>" type="text" style="width: 300px">
								</td>
							</tr>
							
							<?
							if ($row["scope"]=="Learning Area") {
								try {
									$dataLearningArea=array("gibbonDepartmentID"=>$row["gibbonDepartmentID"]); 
									$sqlLearningArea="SELECT * FROM gibbonDepartment WHERE gibbonDepartmentID=:gibbonDepartmentID" ;
									$resultLearningArea=$connection2->prepare($sqlLearningArea);
									$resultLearningArea->execute($dataLearningArea);
								}
								catch(PDOException $e) { 
									print "<div class='error'>" . $e->getMessage() . "</div>" ; 
								}
								if ($resultLearningArea->rowCount()==1) {
									$rowLearningAreas=$resultLearningArea->fetch() ;
								}
								?>
								<tr>
									<td> 
										<b>Learning Area *</b><br/>
										<span style="font-size: 90%"><i></i></span>
									</td>
									<td class="right">
										<input readonly name="gibbonDepartment" id="gibbonDepartment" value="<? print $rowLearningAreas["name"] ?>" type="text" style="width: 300px">
										<input name="gibbonDepartmentID" id="gibbonDepartmentID" value="<? print $row["gibbonDepartmentID"] ?>" type="hidden" style="width: 300px">
									</td>
								</tr>
								<?
							}
							?>
							
							
							<tr>
								<td> 
									<? print "<b>" . _('Name') . " *</b><br/>" ; ?>
								</td>
								<td class="right">
									<input name="name" id="name" maxlength=100 value="<? print $row["name"] ?>" type="text" style="width: 300px">
									<script type="text/javascript">
										var name=new LiveValidation('name');
										name.add(Validate.Presence);
									</script>
								</td>
							</tr>
							<tr>
								<td> 
									<b>Name Short *</b><br/>
								</td>
								<td class="right">
									<input name="nameShort" id="nameShort" maxlength=14 value="<? print $row["nameShort"] ?>" type="text" style="width: 300px">
									<script type="text/javascript">
										var nameShort=new LiveValidation('nameShort');
										nameShort.add(Validate.Presence);
									</script>
								</td>
							</tr>
							<tr>
								<td> 
									<b><? print _('Active') ?> *</b><br/>
									<span style="font-size: 90%"><i></i></span>
								</td>
								<td class="right">
									<select name="active" id="active" style="width: 302px">
										<option <? if ($row["active"]=="Y") { print "selected" ; } ?> value="Y"><? print _('Y') ?></option>
										<option <? if ($row["active"]=="N") { print "selected" ; } ?> value="N"><? print _('N') ?></option>
									</select>
								</td>
							</tr>
							
							<tr>
								<td> 
									<b>Category</b><br/>
								</td>
								<td class="right">
									<input name="category" id="category" maxlength=100 value="<? print $row["category"] ?>" type="text" style="width: 300px">
									<script type="text/javascript">
										$(function() {
											var availableTags=[
												<?
												try {
													$dataAuto=array(); 
													$sqlAuto="SELECT DISTINCT category FROM gibbonOutcome ORDER BY category" ;
													$resultAuto=$connection2->prepare($sqlAuto);
													$resultAuto->execute($dataAuto);
												}
												catch(PDOException $e) { }
												while ($rowAuto=$resultAuto->fetch()) {
													print "\"" . $rowAuto["category"] . "\", " ;
												}
												?>
											];
											$( "#category" ).autocomplete({source: availableTags});
										});
									</script>
								</td>
							</tr>
							<tr>
								<td> 
									<b><? print _('Description') ?></b><br/>
								</td>
								<td class="right">
									<textarea name='description' id='description' rows=5 style='width: 300px'><? print $row["description"] ?></textarea>
								</td>
							</tr>
							<tr>
								<td> 
									<b>Year Groups</b><br/>
									<span style="font-size: 90%"><i>Relevant student year groups<br/></i></span>
								</td>
								<td class="right">
									<? 
									$yearGroups=getYearGroups($connection2) ;
									if ($yearGroups=="") {
										print "<i>" . _('No year groups available.') . "</i>" ;
									}
									else {
										for ($i=0; $i<count($yearGroups); $i=$i+2) {
											$checked="" ;
											if (is_numeric(strpos($row["gibbonYearGroupIDList"], $yearGroups[$i]))) {
												$checked="checked " ;
											}
											print $yearGroups[($i+1)] . " <input $checked type='checkbox' name='gibbonYearGroupIDCheck" . ($i)/2 . "'><br/>" ; 
											print "<input type='hidden' name='gibbonYearGroupID" . ($i)/2 . "' value='" . $yearGroups[$i] . "'>" ;
										}
									}
									?>
									<input type="hidden" name="count" value="<? print (count($yearGroups))/2 ?>">
								</td>
							</tr>
							<tr>
								<td>
									<span style="font-size: 90%"><i>* <? print _("denotes a required field") ; ?></i></span>
								</td>
								<td class="right">
									<input type="hidden" name="address" value="<? print $_SESSION[$guid]["address"] ?>">
									<input type="submit" value="<? print _("Submit") ; ?>">
								</td>
							</tr>
						</table>
					</form>
					<?
				}
			}
		}
	}
}
?>