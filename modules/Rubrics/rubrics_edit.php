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


if (isActionAccessible($guid, $connection2, "/modules/Rubrics/rubrics_edit.php")==FALSE) {
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
		if ($highestAction!="Manage Rubrics_viewEditAll" AND $highestAction!="Manage Rubrics_viewAllEditLearningArea") {
			print "<div class='error'>" ;
				print _("You do not have access to this action.") ;
			print "</div>" ;
		}
		else {
			//Proceed!
			print "<div class='trail'>" ;
			print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/rubrics.php'>Manage Rubrics</a> > </div><div class='trailEnd'>Edit Rubric</div>" ;
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
				else if ($updateReturn=="success0") {
					$updateReturnMessage=_("Your request was completed successfully.") ;	
					$class="success" ;
				}
				print "<div class='$class'>" ;
					print $updateReturnMessage;
				print "</div>" ;
			} 
			
			if (isset($_GET["addReturn"])) { $addReturn=$_GET["addReturn"] ; } else { $addReturn="" ; }
			$addReturnMessage="" ;
			$class="error" ;
			if (!($addReturn=="")) {
				if ($addReturn=="success0") {
					$addReturnMessage=_("Your request was completed successfully.") ;	
					$class="success" ;
				}
				print "<div class='$class'>" ;
					print $addReturnMessage;
				print "</div>" ;
			} 
			
			if (isset($_GET["columnDeleteReturn"])) { $columnDeleteReturn=$_GET["columnDeleteReturn"] ; } else { $columnDeleteReturn="" ; }
			$columnDeleteReturnMessage="" ;
			$class="error" ;
			if (!($columnDeleteReturn=="")) {
				if ($columnDeleteReturn=="fail0") {
					$columnDeleteReturnMessage=_("Your request failed because you do not have access to this action.") ;	
				}
				else if ($columnDeleteReturn=="fail1") {
					$columnDeleteReturnMessage=_("Your request failed because your inputs were invalid.") ;	
				}
				else if ($columnDeleteReturn=="fail2") {
					$columnDeleteReturnMessage=_("Your request failed due to a database error.") ;	
				}
				else if ($columnDeleteReturn=="fail3") {
					$columnDeleteReturnMessage=_("Your request failed because your inputs were invalid.") ;	
				}
				else if ($columnDeleteReturn=="success0") {
					$columnDeleteReturnMessage="Your request was successful." ;	
					$class="success" ;
				}
				print "<div class='$class'>" ;
					print $columnDeleteReturnMessage;
				print "</div>" ;
			} 
			
			if (isset($_GET["rowDeleteReturn"])) { $rowDeleteReturn=$_GET["rowDeleteReturn"] ; } else { $rowDeleteReturn="" ; }
			$rowDeleteReturnMessage="" ;
			$class="error" ;
			if (!($rowDeleteReturn=="")) {
				if ($rowDeleteReturn=="fail0") {
					$rowDeleteReturnMessage=_("Your request failed because you do not have access to this action.") ;	
				}
				else if ($rowDeleteReturn=="fail1") {
					$rowDeleteReturnMessage=_("Your request failed because your inputs were invalid.") ;	
				}
				else if ($rowDeleteReturn=="fail2") {
					$rowDeleteReturnMessage=_("Your request failed due to a database error.") ;	
				}
				else if ($rowDeleteReturn=="fail3") {
					$rowDeleteReturnMessage=_("Your request failed because your inputs were invalid.") ;	
				}
				else if ($rowDeleteReturn=="success0") {
					$rowDeleteReturnMessage="Your request was successful." ;	
					$class="success" ;
				}
				print "<div class='$class'>" ;
					print $rowDeleteReturnMessage;
				print "</div>" ;
			} 
			
			if (isset($_GET["cellEditReturn"])) { $cellEditReturn=$_GET["cellEditReturn"] ; } else { $cellEditReturn="" ; }
			$cellEditReturnMessage="" ;
			$class="error" ;
			if (!($cellEditReturn=="")) {
				if ($cellEditReturn=="fail0") {
					$cellEditReturnMessage="Cell edit failed because you do not have access to this action." ;	
				}
				else if ($cellEditReturn=="fail1") {
					$cellEditReturnMessage="Cell edit failed because a required parameter was not set." ;	
				}
				else if ($cellEditReturn=="fail2") {
					$cellEditReturnMessage="Cell edit failed due to a database error." ;	
				}
				else if ($cellEditReturn=="fail3") {
					$cellEditReturnMessage="Cell edit failed because your inputs were invalid." ;	
				}
				else if ($cellEditReturn=="fail5") {
					$cellEditReturnMessage="Cell edit experienced partial failure." ;	
				}
				else if ($cellEditReturn=="success0") {
					$cellEditReturnMessage="Cell edit was successful." ;	
					$class="success" ;
				}
				print "<div class='$class'>" ;
					print $cellEditReturnMessage;
				print "</div>" ;
			} 
			
			//Check if school year specified
			$gibbonRubricID=$_GET["gibbonRubricID"];
			if ($gibbonRubricID=="") {
				print "<div class='error'>" ;
					print _("You have not specified one or more required parameters.") ;
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
						print _("The specified record does not exist.") ;
					print "</div>" ;
				}
				else {
					//Let's go!
					$row=$result->fetch() ;
					?>
					<form method="post" action="<? print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/rubrics_editProcess.php?gibbonRubricID=$gibbonRubricID" ?>">
						<table class='smallIntBorder' cellspacing='0' style="width: 760px">	
							<tr class='break'>
								<td colspan=2>
									<h3>Rubric Basics</h3>
								</td>
							</tr>
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
										<input readonly name="department" id="department" value="<? print $rowLearningAreas["name"] ?>" type="text" style="width: 300px">
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
									<input name="name" id="name" maxlength=50 value="<? print $row["name"] ?>" type="text" style="width: 300px">
									<script type="text/javascript">
										var name=new LiveValidation('name');
										name.add(Validate.Presence);
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
													$sqlAuto="SELECT DISTINCT category FROM gibbonRubric ORDER BY category" ;
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
									<b>Description</b><br/>
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
									<b>Grading Scale</b><br/>
									<span style="font-size: 90%"><i><? print _('This value cannot be changed.') ?></i></span>
								</td>
								<td class="right">
									<?
									if ($row["gibbonScaleID"]!="") { 
										try {
											$dataSelect=array("gibbonScaleID"=>$row["gibbonScaleID"]); 
											$sqlSelect="SELECT * FROM gibbonScale WHERE gibbonScaleID=:gibbonScaleID" ;
											$resultSelect=$connection2->prepare($sqlSelect);
											$resultSelect->execute($dataSelect);
										}
										catch(PDOException $e) { 
											print "<div class='error'>" . $e->getMessage() . "</div>" ; 
										}
										if ($resultSelect->rowCount()==1) {
											$rowSelect=$resultSelect->fetch() ;
										}
									}
									if (isset($rowSelect["name"])==FALSE) {
										?>
										<input readonly name="scale" id="scale" value="None" type="text" style="width: 300px">
										<?
									}
									else {
										?>
										<input readonly name="scale" id="scale" value="<? print $rowSelect["name"] ?>" type="text" style="width: 300px">
										<input name="gibbonScaleID" id="gibbonScaleID" value="<? print $rowSelect["gibbonScaleID"] ?>" type="hidden" style="width: 300px">
										<?
									}
									?>
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
					<a name='rubricDesign'></a>
					<table class='smallIntBorder' cellspacing='0' style="width:100%">
						<tr class='break'>
							<td colspan=2>
								<h3>Rubric Design</h3>
							</td>
						</tr>
					</table>
					<?
					$scaleName="" ;
					if (isset($rowSelect["name"])) {
						$scaleName=$rowSelect["name"] ;
					}
					print rubricEdit($guid, $connection2, $gibbonRubricID, $scaleName) ;
				}
			}
		}
	}
}
?>