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


if (isActionAccessible($guid, $connection2, "/modules/Rubrics/rubrics_edit_editRowsColumns.php")==FALSE) {
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
		print _("The highest grouped action cannot be determined.") ;
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
			print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/rubrics.php'>" . _('Manage Rubrics') . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/rubrics_edit.php&gibbonRubricID=" . $_GET["gibbonRubricID"] ."'>" . _('Edit Rubric') . "</a> > </div><div class='trailEnd'>" . _('Edit Rubric Rows & Columns') . "</div>" ;
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
					$updateReturnMessage=_("Your request was successful, but some data was not properly saved.") ;
				}
				print "<div class='$class'>" ;
					print $updateReturnMessage;
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
					<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/rubrics_edit_editRowsColumnsProcess.php?gibbonRubricID=$gibbonRubricID" ?>">
						<table class='smallIntBorder' cellspacing='0' style="width: 760px">	
							<tr class='break'>
								<td colspan=2>
									<h3><?php print _('Rubric Basics') ?></h3>
								</td>
							</tr>
							<tr>
								<td style='width: 275px'> 
									<b><?php print _('Scope') ?> *</b><br/>
									<span style="font-size: 90%"><i></i></span>
								</td>
								<td class="right">
									<input readonly name="scope" id="scope" value="<?php print $row["scope"] ?>" type="text" style="width: 300px">
								</td>
							</tr>
							
							<?php
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
										<b><?php print _('Learning Area') ?> *</b><br/>
										<span style="font-size: 90%"><i></i></span>
									</td>
									<td class="right">
										<input readonly name="department" id="department" value="<?php print $rowLearningAreas["name"] ?>" type="text" style="width: 300px" maxlength=20>
										<input name="gibbonDepartmentID" id="gibbonDepartmentID" value="<?php print $row["gibbonDepartmentID"] ?>" type="hidden" style="width: 300px">
									</td>
								</tr>
								<?php
							}
							?>
							<tr>
								<td> 
									<b><?php print _('Name') ?> *</b><br/>
								</td>
								<td class="right">
									<input readonly name="name" id="name" maxlength=50 value="<?php print $row["name"] ?>" type="text" style="width: 300px">
								</td>
							</tr>
							
							<?php//ROWS!?>
							<tr class='break'>
								<td colspan=2>
									<h3><?php print _('Rows') ?></h3>
								</td>
							</tr>
							<?php
							try {
								$dataRows=array("gibbonRubricID"=>$gibbonRubricID); 
								$sqlRows="SELECT * FROM gibbonRubricRow WHERE gibbonRubricID=:gibbonRubricID ORDER BY sequenceNumber" ;
								$resultRows=$connection2->prepare($sqlRows);
								$resultRows->execute($dataRows); 
							}
							catch(PDOException $e) { 
								print "<div class='error'>" . $e->getMessage() . "</div>" ; 
							}
							
							if ($resultRows->rowCount()<1) {
								print "<div class='error'>" ;
									print _("There are no records to display.") ;
								print "</div>" ;
							}
							else {
								$count=0 ;
								while ($rowRows=$resultRows->fetch()) {
									?>
									<tr>
										<td> 
											<b><?php print sprintf(_('Row %1$s Title'), ($count+1)) ?></b><br/>
											<span style="font-size: 90%"><i></i></span>
										</td>
										<td class="right">
											<?php
											$outcomeBased=FALSE ;
											if ($rowRows["gibbonOutcomeID"]!="") {
												$outcomeBased=TRUE ;
											}
											?>
											<script type="text/javascript">
												$(document).ready(function(){
													<?php
													if ($outcomeBased==FALSE) {
														?>
														$("#gibbonOutcomeID-<?php print $count ?>").css("display","none");
														<?php
													}
													else {
														?>
														$("#rowTitle-<?php print $count ?>").css("display","none");
														<?php
													}
													?>
													
													$(".type-<?php print $count ?>").click(function(){
														if ($('input[name=type-<?php print $count ?>]:checked').val()=="Standalone" ) {
															$("#gibbonOutcomeID-<?php print $count ?>").css("display","none");
															$("#rowTitle-<?php print $count ?>").css("display","block"); 
														}
														else if ($('input[name=type-<?php print $count ?>]:checked').val()=="Outcome Based" ) {
															$("#rowTitle-<?php print $count ?>").css("display","none");
															$("#gibbonOutcomeID-<?php print $count ?>").css("display","block"); 
														}
													});
													
												});
											</script>
											<input <?php if ($outcomeBased==FALSE) {print "checked";} ?> type="radio" name="type-<?php print $count ?>" value="Standalone" class="type-<?php print $count ?>" /> <?php print _('Standalone') ?> 
											<input <?php if ($outcomeBased==TRUE) {print "checked";} ?> type="radio" name="type-<?php print $count ?>" value="Outcome Based" class="type-<?php print $count ?>" /> <?php print _('Outcome Based') ?><br/>
											<select name='gibbonOutcomeID[]' id='gibbonOutcomeID-<?php print $count ?>' style='width: 304px'>
												<option><option>
												<optgroup label='--<?php print _('School Outcomes') ?>--'>
													<?php
													try {
														$dataSelect=array(); 
														$sqlSelect="SELECT * FROM gibbonOutcome WHERE scope='School' AND active='Y' ORDER BY category, name" ;
														$resultSelect=$connection2->prepare($sqlSelect);
														$resultSelect->execute($dataSelect);
													}
													catch(PDOException $e) { }
													while ($rowSelect=$resultSelect->fetch()) {
														$label="" ;
														if ($rowSelect["category"]=="") {
															$label=$rowSelect["name"] ;
														}
														else {
															$label=$rowSelect["category"] . " - " . $rowSelect["name"] ;
														}
														$selected="" ;
														if ($rowSelect["gibbonOutcomeID"]==$rowRows["gibbonOutcomeID"]) {
															$selected="selected" ; 
														}
														print "<option $selected value='" . $rowSelect["gibbonOutcomeID"] . "'>$label</option>" ;
													}
													?>
												</optgroup>
												<?php
												if ($row["scope"]=="Learning Area") {
													?>
													<optgroup label='--<?php print _('Learning Area Outcomes') ?>--'>
														<?php
														try {
															$dataSelect=array("gibbonDepartmentID"=>$row["gibbonDepartmentID"]); 
															$sqlSelect="SELECT * FROM gibbonOutcome WHERE scope='Learning Area' AND gibbonDepartmentID=:gibbonDepartmentID AND active='Y' ORDER BY category, name" ;
															$resultSelect=$connection2->prepare($sqlSelect);
															$resultSelect->execute($dataSelect);
														}
														catch(PDOException $e) { }
														while ($rowSelect=$resultSelect->fetch()) {
															$label="" ;
															if ($rowSelect["category"]=="") {
																$label=$rowSelect["name"] ;
															}
															else {
																$label=$rowSelect["category"] . " - " . $rowSelect["name"] ;
															}
															$selected="" ;
															if ($rowSelect["gibbonOutcomeID"]==$rowRows["gibbonOutcomeID"]) {
																$selected="selected" ; 
															}
															print "<option $selected value='" . $rowSelect["gibbonOutcomeID"] . "'>$label</option>" ;
														}
														?>
													</optgroup>
													<?php
												}
												?>
											</select>
											<input name="rowTitle[]" id="rowTitle-<?php print $count ?>" value="<?php print $rowRows["title"] ?>" type="text" style="width: 300px" maxlength=40>
											<input name="gibbonRubricRowID[]" id="gibbonRubricRowID[]" value="<?php print $rowRows["gibbonRubricRowID"] ?>" type="hidden">
										</td>
									</tr>
									<?php
									$count++ ;
								}
							}
							?>
							
							<?php//COLUMNS!?>
							<tr class='break'>
								<td colspan=2>
									<h3><?php print _('Columns') ?></h3>
								</td>
							</tr>
							<?php
							try {
								$dataColumns=array("gibbonRubricID"=>$gibbonRubricID); 
								$sqlColumns="SELECT * FROM gibbonRubricColumn WHERE gibbonRubricID=:gibbonRubricID ORDER BY sequenceNumber" ;
								$resultColumns=$connection2->prepare($sqlColumns);
								$resultColumns->execute($dataColumns);
							}
							catch(PDOException $e) { 
								print "<div class='error'>" . $e->getMessage() . "</div>" ; 
							}
							
							if ($resultColumns->rowCount()<1) {
								print "<div class='error'>" ;
									print _("There are no records to display.") ;
								print "</div>" ;
							}
							else {
								//If no grade scale specified
								if ($row["gibbonScaleID"]=="") {
									$count=0 ;
									while ($rowColumns=$resultColumns->fetch()) {
										?>
										<tr>
											<td> 
												<b><?php print sprintf(_('Column %1$s Title'), ($count+1)) ?></b><br/>
												<span style="font-size: 90%"><i></i></span>
											</td>
											<td class="right">
												<input name="columnTitle[]" id="columnTitle[]" value="<?php print $rowColumns["title"] ?>" type="text" style="width: 300px" maxlength=20>
												<input name="gibbonRubricColumnID[]" id="gibbonRubricColumnID[]" value="<?php print $rowColumns["gibbonRubricColumnID"] ?>" type="hidden">
											</td>
										</tr>
										<?php
										$count++ ;
									}
								}
								//If scale specified	
								else {
									$count=0 ;
									while ($rowColumns=$resultColumns->fetch()) {
										?>
										<tr>
											<td> 
												<b><?php print sprintf(_('Column %1$s Grade'), ($count+1)) ?></b><br/>
												<span style="font-size: 90%"><i></i></span>
											</td>
											<td class="right">
												<?php
												print "<select name='gibbonScaleGradeID[]' id='gibbonScaleGradeID[]' style='width:304px'>" ;
													try {
														$dataSelect=array("gibbonScaleID"=>$row["gibbonScaleID"]); 
														$sqlSelect="SELECT * FROM gibbonScaleGrade WHERE gibbonScaleID=:gibbonScaleID AND NOT value='Incomplete' ORDER BY sequenceNumber" ;
														$resultSelect=$connection2->prepare($sqlSelect);
														$resultSelect->execute($dataSelect);
													}
													catch(PDOException $e) { }
													while ($rowSelect=$resultSelect->fetch()) {
														if ($rowColumns["gibbonScaleGradeID"]==$rowSelect["gibbonScaleGradeID"]) {
															print "<option selected value='" . $rowSelect["gibbonScaleGradeID"] . "'>" . htmlPrep($rowSelect["value"]) . " - " . htmlPrep($rowSelect["descriptor"]) . "</option>" ;
														}
														else {
															print "<option value='" . $rowSelect["gibbonScaleGradeID"] . "'>" . htmlPrep($rowSelect["value"]) . " - " . htmlPrep($rowSelect["descriptor"]) . "</option>" ;
														}
													}
												print "</select>" ;
												?>
												<input name="gibbonRubricColumnID[]" id="gibbonRubricColumnID[]" value="<?php print $rowColumns["gibbonRubricColumnID"] ?>" type="hidden">
											</td>
										</tr>
										<?php
										$count++ ;
									}
								}
							}
							?>
							
							
							<tr>
								<td>
									<span style="font-size: 90%"><i>* <?php print _("denotes a required field") ; ?></i></span>
								</td>
								<td class="right">
									<input type="hidden" name="address" value="<?php print $_SESSION[$guid]["address"] ?>">
									<input type="submit" value="<?php print _("Submit") ; ?>">
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