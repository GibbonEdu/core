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


if (isActionAccessible($guid, $connection2, "/modules/Rubrics/rubrics_edit_editRowsColumns.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print "You do not have access to this action." ;
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
				print "You do not have access to this action." ;
			print "</div>" ;
		}
		else {
			//Proceed!
			print "<div class='trail'>" ;
			print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/rubrics.php'>Manage Rubrics</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/rubrics_edit.php&gibbonRubricID=" . $_GET["gibbonRubricID"] ."'>Edit Rubric</a> > </div><div class='trailEnd'>Edit Rubric Rows & Columns</div>" ;
			print "</div>" ;
			
			if (isset($_GET["updateReturn"])) { $updateReturn=$_GET["updateReturn"] ; } else { $updateReturn="" ; }
			$updateReturnMessage ="" ;
			$class="error" ;
			if (!($updateReturn=="")) {
				if ($updateReturn=="fail0") {
					$updateReturnMessage =_("Your request failed because you do not have access to this action.") ;	
				}
				else if ($updateReturn=="fail1") {
					$updateReturnMessage =_("Your request failed because your inputs were invalid.") ;	
				}
				else if ($updateReturn=="fail2") {
					$updateReturnMessage =_("Your request failed due to a database error.") ;	
				}
				else if ($updateReturn=="fail3") {
					$updateReturnMessage =_("Your request failed because your inputs were invalid.") ;	
				}
				else if ($updateReturn=="fail4") {
					$updateReturnMessage ="Your request was successful, but some data was not properly saved." ;	
				}
				print "<div class='$class'>" ;
					print $updateReturnMessage;
				print "</div>" ;
			} 
			
			//Check if school year specified
			$gibbonRubricID=$_GET["gibbonRubricID"];
			if ($gibbonRubricID=="") {
				print "<div class='error'>" ;
					print "You have not specified one or more required parameters." ;
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
						print "The selected rubric does not exist." ;
					print "</div>" ;
				}
				else {
					//Let's go!
					$row=$result->fetch() ;
					?>
					<form method="post" action="<? print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/rubrics_edit_editRowsColumnsProcess.php?gibbonRubricID=$gibbonRubricID" ?>">
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
										<input readonly name="department" id="department" value="<? print $rowLearningAreas["name"] ?>" type="text" style="width: 300px" maxlength=20>
										<input name="gibbonDepartmentID" id="gibbonDepartmentID" value="<? print $row["gibbonDepartmentID"] ?>" type="hidden" style="width: 300px">
									</td>
								</tr>
								<?
							}
							?>
							<tr>
								<td> 
									<b>Name *</b><br/>
								</td>
								<td class="right">
									<input readonly name="name" id="name" maxlength=50 value="<? print $row["name"] ?>" type="text" style="width: 300px">
								</td>
							</tr>
							
							<?//ROWS!?>
							<tr class='break'>
								<td colspan=2>
									<h3>Rows</h3>
								</td>
							</tr>
							<?
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
									print "There are no records to display." ;
								print "</div>" ;
							}
							else {
								$count=0 ;
								while ($rowRows=$resultRows->fetch()) {
									?>
									<tr>
										<td> 
											<b>Row <?print $count+1 ?> Title</b><br/>
											<span style="font-size: 90%"><i></i></span>
										</td>
										<td class="right">
											<?
											$outcomeBased=FALSE ;
											if ($rowRows["gibbonOutcomeID"]!="") {
												$outcomeBased=TRUE ;
											}
											?>
											<script type="text/javascript">
												$(document).ready(function(){
													<?
													if ($outcomeBased==FALSE) {
														?>
														$("#gibbonOutcomeID-<? print $count ?>").css("display","none");
														<?
													}
													else {
														?>
														$("#rowTitle-<? print $count ?>").css("display","none");
														<?
													}
													?>
													
													$(".type-<? print $count ?>").click(function(){
														if ($('input[name=type-<? print $count ?>]:checked').val() == "Standalone" ) {
															$("#gibbonOutcomeID-<? print $count ?>").css("display","none");
															$("#rowTitle-<? print $count ?>").css("display","block"); 
														}
														else if ($('input[name=type-<? print $count ?>]:checked').val() == "Outcome Based" ) {
															$("#rowTitle-<? print $count ?>").css("display","none");
															$("#gibbonOutcomeID-<? print $count ?>").css("display","block"); 
														}
													});
													
												});
											</script>
											<input <? if ($outcomeBased==FALSE) {print "checked";} ?> type="radio" name="type-<? print $count ?>" value="Standalone" class="type-<? print $count ?>" /> Standalone 
											<input <? if ($outcomeBased==TRUE) {print "checked";} ?> type="radio" name="type-<? print $count ?>" value="Outcome Based" class="type-<? print $count ?>" /> Outcome Based<br/>
											<select name='gibbonOutcomeID[]' id='gibbonOutcomeID-<? print $count ?>' style='width: 304px'>
												<option><option>
												<optgroup label='--School Outcomes --'>
													<?
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
												<?
												if ($row["scope"]=="Learning Area") {
													?>
													<optgroup label='--Learning Area Outcomes--'>
														<?
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
													<?
												}
												?>
											</select>
											<input name="rowTitle[]" id="rowTitle-<? print $count ?>" value="<? print $rowRows["title"] ?>" type="text" style="width: 300px" maxlength=40>
											<input name="gibbonRubricRowID[]" id="gibbonRubricRowID[]" value="<? print $rowRows["gibbonRubricRowID"] ?>" type="hidden">
										</td>
									</tr>
									<?
									$count++ ;
								}
							}
							?>
							
							<?//COLUMNS!?>
							<tr class='break'>
								<td colspan=2>
									<h3>Columns</h3>
								</td>
							</tr>
							<?
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
									print "There are no records to display." ;
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
												<b>Column <?print $count+1 ?> Title</b><br/>
												<span style="font-size: 90%"><i></i></span>
											</td>
											<td class="right">
												<input name="columnTitle[]" id="columnTitle[]" value="<? print $rowColumns["title"] ?>" type="text" style="width: 300px" maxlength=20>
												<input name="gibbonRubricColumnID[]" id="gibbonRubricColumnID[]" value="<? print $rowColumns["gibbonRubricColumnID"] ?>" type="hidden">
											</td>
										</tr>
										<?
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
												<b>Column <?print $count+1 ?> Grade</b><br/>
												<span style="font-size: 90%"><i></i></span>
											</td>
											<td class="right">
												<?
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
												<input name="gibbonRubricColumnID[]" id="gibbonRubricColumnID[]" value="<? print $rowColumns["gibbonRubricColumnID"] ?>" type="hidden">
											</td>
										</tr>
										<?
										$count++ ;
									}
								}
							}
							?>
							
							
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