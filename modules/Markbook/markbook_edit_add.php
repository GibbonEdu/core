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

if (isActionAccessible($guid, $connection2, "/modules/Markbook/markbook_edit_add.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print "You do not have access to this action." ;
	print "</div>" ;
}
else {
	$gibbonCourseClassID=$_GET["gibbonCourseClassID"]; 
	if ($gibbonCourseClassID=="") {
		print "<div class='error'>" ;
			print "You have not specified a class." ;
		print "</div>" ;
	}
	else {
		try {
			$data=array("gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"], "gibbonCourseClassID"=>$gibbonCourseClassID); 
			$sql="SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourseClass.gibbonCourseClassID, gibbonCourse.gibbonDepartmentID, gibbonYearGroupIDList FROM gibbonCourse, gibbonCourseClass, gibbonCourseClassPerson WHERE gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID AND gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND role='Teacher' AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID ORDER BY course, class" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
		}
		if ($result->rowCount()!=1) {
			print "<div class='error'>" ;
				print "The specified class does not exist, or you do not have access to it." ;
			print "</div>" ;
		}
		else {
			$row=$result->fetch() ;
			print "<div class='trail'>" ;
			print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>Home</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/markbook_view.php&gibbonCourseClassID=" . $_GET["gibbonCourseClassID"] . "'>View " . $row["course"] . "." . $row["class"] . " Markbook</a> > </div><div class='trailEnd'>Add Column</div>" ;
			print "</div>" ;
			
			$addReturn = $_GET["addReturn"] ;
			$addReturnMessage ="" ;
			$class="error" ;
			if (!($addReturn=="")) {
				if ($addReturn=="fail0") {
					$addReturnMessage ="Add failed because you do not have access to this action." ;	
				}
				else if ($addReturn=="fail2") {
					$addReturnMessage ="Add failed due to a database error." ;	
				}
				else if ($addReturn=="fail3") {
					$addReturnMessage ="Add failed because your inputs were invalid." ;	
				}
				else if ($addReturn=="fail4") {
					$addReturnMessage ="Add failed some values need to be unique but were not." ;	
				}
				else if ($addReturn=="fail5") {
					$addReturnMessage ="Add failed because your attachment could not be uploaded." ;	
				}
				else if ($addReturn=="fail6") {
					$addReturnMessage ="Add failed because you already have one \"End of Year\" column for this class." ;	
				}
				else if ($addReturn=="success0") {
					$addReturnMessage ="Add was successful. You can add another record if you wish." ;	
					$class="success" ;
				}
				print "<div class='$class'>" ;
					print $addReturnMessage;
				print "</div>" ;
			} 
			
			$addReturnPlanner = $_GET["addReturnPlanner"] ;
			$addReturnPlannerMessage ="" ;
			$class="error" ;
			if (!($addReturnPlanner=="")) {
				if ($addReturnPlanner=="success0") {
					$addReturnPlannerMessage ="Planner was successfully added: you opted to add a linked Markbook column, and you can now do so below." ;	
					$class="success" ;
				}
				print "<div class='$class'>" ;
					print $addReturnPlannerMessage;
				print "</div>" ;
			} 
			?>
	
			<form method="post" action="<? print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/markbook_edit_addProcess.php?gibbonCourseClassID=$gibbonCourseClassID&address=" . $_SESSION[$guid]["address"] ?>" enctype="multipart/form-data">
				<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
					<tr>
						<td> 
							<b>Class *</b><br/>
							<span style="font-size: 90%"><i>This value cannot be changed.</i></span>
						</td>
						<td class="right">
							<input readonly name="schoolYearName" id="schoolYearName" maxlength=20 value="<? print $row["course"] . "." . $row["class"] ?>" type="text" style="width: 300px">
						</td>
					</tr>
					<tr>
						<td> 
							<b>Unit</b><br/>
						</td>
						<td class="right">
							<select name="gibbonUnitID" id="gibbonUnitID" style="width: 302px">
									<?
									//List basic and smart units
									try {
										$dataSelect=array("gibbonCourseClassID"=>$gibbonCourseClassID); 
										$sqlSelect="SELECT * FROM gibbonUnit JOIN gibbonUnitClass ON (gibbonUnit.gibbonUnitID=gibbonUnitClass.gibbonUnitID) WHERE running='Y' AND gibbonCourseClassID=:gibbonCourseClassID ORDER BY name" ;
										$resultSelect=$connection2->prepare($sqlSelect);
										$resultSelect->execute($dataSelect);
									}
									catch(PDOException $e) { 
										print "<div class='error'>" . $e->getMessage() . "</div>" ; 
									}
									
									$lastType="" ;
									$currentType="" ;
									print "<option value=''></option>" ;
									while ($rowSelect=$resultSelect->fetch()) {
										$currentType=$rowSelect["type"] ;
										if ($currentType!=$lastType) {
											print "<optgroup label='--" . $currentType . "--'>" ;
										}
										print "<option class='" . $rowSelect["gibbonCourseClassID"] . "' value='" . $rowSelect["gibbonUnitID"] . "'>" . htmlPrep($rowSelect["name"]) . "</option>" ;
										$lastType=$currentType ;
									}		
									
									//List any hooked units
									$lastType="" ;
									$currentType="" ;
									try {
										$dataHooks=array(); 
										$sqlHooks="SELECT * FROM gibbonHook WHERE type='Unit' ORDER BY name" ;
										$resultHooks=$connection2->prepare($sqlHooks);
										$resultHooks->execute($dataHooks);
									}
									catch(PDOException $e) { }
									while ($rowHooks=$resultHooks->fetch()) {
										$hookOptions=unserialize($rowHooks["options"]) ;
										if ($hookOptions["unitTable"]!="" AND $hookOptions["unitIDField"]!="" AND $hookOptions["unitCourseIDField"]!="" AND $hookOptions["unitNameField"]!="" AND $hookOptions["unitDescriptionField"]!="" AND $hookOptions["classLinkTable"]!="" AND $hookOptions["classLinkJoinFieldUnit"]!="" AND $hookOptions["classLinkJoinFieldClass"]!="" AND $hookOptions["classLinkIDField"]!="") {
											try {
												$dataHookUnits=array("gibbonCourseClassID"=>$gibbonCourseClassID); 
												$sqlHookUnits="SELECT * FROM " . $hookOptions["unitTable"] . " JOIN " . $hookOptions["classLinkTable"] . " ON (" . $hookOptions["unitTable"] . "." . $hookOptions["unitIDField"] . "=" . $hookOptions["classLinkTable"] . "." . $hookOptions["classLinkJoinFieldUnit"] . ") WHERE " . $hookOptions["classLinkJoinFieldClass"] . "=:gibbonCourseClassID ORDER BY " . $hookOptions["classLinkTable"] . "." . $hookOptions["classLinkIDField"] ;
												$resultHookUnits=$connection2->prepare($sqlHookUnits);
												$resultHookUnits->execute($dataHookUnits);
											}
											catch(PDOException $e) { }
											while ($rowHookUnits=$resultHookUnits->fetch()) {
												$currentType=$rowHooks["name"] ;
												if ($currentType!=$lastType) {
													print "<optgroup label='--" . $currentType . "--'>" ;
												}
												print "<option class='" . $rowHookUnits[$hookOptions["classLinkIDField"]] . "' value='" . $rowHookUnits[$hookOptions["unitIDField"]] . "-" . $rowHooks["gibbonHookID"] . "'>" . htmlPrep($rowHookUnits[$hookOptions["unitNameField"]]) . "</option>" ;
												$lastType=$currentType ;
											}										
										}
									}
									?>				
								</select>
						</td>
					</tr>
					<tr>
						<td> 
							<b>Lesson</b><br/>
						</td>
						<td class="right">
							<select name="gibbonPlannerEntryID" id="gibbonPlannerEntryID" style="width: 302px">
								<?
								try {
									$dataSelect=array("username"=>$username); 
									$sqlSelect="SELECT * FROM gibbonPlannerEntry WHERE gibbonCourseClassID=" . $row["gibbonCourseClassID"] . " ORDER BY name" ;
									$resultSelect=$connection2->prepare($sqlSelect);
									$resultSelect->execute($dataSelect);
								}
								catch(PDOException $e) { }
								print "<option value=''></option>" ;
								while ($rowSelect=$resultSelect->fetch()) {
									$selected="" ;
									if ($rowSelect["gibbonPlannerEntryID"]==$_GET["gibbonPlannerEntryID"]) {
										$selected="selected" ;
									}
									if ($rowSelect["gibbonHookID"]=="") {
										print "<option $selected class='" . $rowSelect["gibbonUnitID"] . "' value='" . $rowSelect["gibbonPlannerEntryID"] . "'>" . htmlPrep($rowSelect["name"]) . "</option>" ;
									}
									else {
										print "<option $selected class='" . $rowSelect["gibbonUnitID"] . "-" . $rowSelect["gibbonHookID"] . "' value='" . $rowSelect["gibbonPlannerEntryID"] . "'>" . htmlPrep($rowSelect["name"]) . "</option>" ;
									}
								}		
								?>				
							</select>
							<script type="text/javascript">
								$("#gibbonPlannerEntryID").chainedTo("#gibbonUnitID");
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b>Name *</b><br/>
						</td>
						<td class="right">
							<input name="name" id="name" maxlength=20 value="<? print $_GET["name"] ?>" type="text" style="width: 300px">
							<script type="text/javascript">
								var name = new LiveValidation('name');
								name.add(Validate.Presence);
							 </script>
						</td>
					</tr>
					<tr>
						<td> 
							<b>Description *</b><br/>
						</td>
						<td class="right">
							<input name="description" id="description" maxlength=255 value="<? print $_GET["summary"] ?>" type="text" style="width: 300px">
							<script type="text/javascript">
								var description = new LiveValidation('description');
								description.add(Validate.Presence);
							 </script>
						</td>
					</tr>
					<?
					$types=getSettingByScope($connection2, "Markbook", "markbookType") ;
					if ($types!=FALSE) {
						$types=explode(",", $types) ;
						?>
						<tr>
							<td> 
								<b>Type *</b><br/>
								<span style="font-size: 90%"><i></i></span>
							</td>
							<td class="right">
								<select name="type" id="type" style="width: 302px">
									<option value="Please select...">Please select...</option>
									<?
									for ($i=0; $i<count($types); $i++) {
										?>
										<option value="<? print trim($types[$i]) ?>"><? print trim($types[$i]) ?></option>
									<?
									}
									?>
								</select>
								<script type="text/javascript">
									var type = new LiveValidation('type');
									type.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "Select something!"});
								 </script>
							</td>
						</tr>
						<?
					}
					?>
					<tr>
						<td> 
							<b>Attainment Scale *</b><br/>
							<span style="font-size: 90%"><i>How will attainment be graded?.</i></span>
						</td>
						<td class="right">
							<select name="gibbonScaleIDAttainment" id="gibbonScaleIDAttainment" style="width: 302px">
								<?
								try {
									$dataSelect=array(); 
									$sqlSelect="SELECT * FROM gibbonScale WHERE (active='Y') ORDER BY name" ;
									$resultSelect=$connection2->prepare($sqlSelect);
									$resultSelect->execute($dataSelect);
								}
								catch(PDOException $e) { }
								print "<option value='Please select...'>Please select...</option>" ;
								while ($rowSelect=$resultSelect->fetch()) {
									$selected="" ;
									if ($rowSelect["gibbonScaleID"]==$_SESSION[$guid]["primaryAssessmentScale"]) {
										$selected="selected" ;
									}
									print "<option $selected value='" . $rowSelect["gibbonScaleID"] . "'>" . htmlPrep($rowSelect["name"]) . "</option>" ;
								}
								?>				
							</select>
							<script type="text/javascript">
								var gibbonScaleIDAttainment = new LiveValidation('gibbonScaleIDAttainment');
								gibbonScaleIDAttainment.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "Select something!"});
							 </script>
						</td>
					</tr>
					<tr>
						<td> 
							<b>Attainment Rubric</b><br/>
							<span style="font-size: 90%"><i>Choose predefined rubric, if desired.</i></span>
						</td>
						<td class="right">
							<select name="gibbonRubricIDAttainment" id="gibbonRubricIDAttainment" style="width: 302px">
								<option><option>
								<optgroup label='--School Rubrics --'>
								<?
								try {
									$dataSelect=array(); 
									$sqlSelectWhere="" ;
									$years=explode(",",$row["gibbonYearGroupIDList"]) ;
									foreach ($years as $year) {
										$dataSelect[$year]="%$year%" ;
										$sqlSelectWhere.=" AND gibbonYearGroupIDList LIKE :$year" ;
									}
									$sqlSelect="SELECT * FROM gibbonRubric WHERE active='Y' AND scope='School' $sqlSelectWhere ORDER BY category, name" ;
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
									print "<option value='" . $rowSelect["gibbonRubricID"] . "'>$label</option>" ;
								}
								if ($row["gibbonDepartmentID"]!="") {
									?>
									<optgroup label='--Learning Area Rubrics --'>
									<?
									try {
										$dataSelect=array("gibbonDepartmentID"=>$row["gibbonDepartmentID"]); 
										$sqlSelectWhere="" ;
										$years=explode(",",$row["gibbonYearGroupIDList"]) ;
										foreach ($years as $year) {
											$dataSelect[$year]="%$year%" ;
											$sqlSelectWhere.=" AND gibbonYearGroupIDList LIKE :$year" ;
										}
										$sqlSelect="SELECT * FROM gibbonRubric WHERE active='Y' AND scope='Learning Area' AND gibbonDepartmentID=:gibbonDepartmentID $sqlSelectWhere ORDER BY category, name" ;
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
										print "<option value='" . $rowSelect["gibbonRubricID"] . "'>$label</option>" ;
									}
								}
								?>				
							</select>
							<script type="text/javascript">
								var gibbonScaleIDEffort = new LiveValidation('gibbonScaleIDEffort');
								gibbonScaleIDEffort.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "Select something!"});
							 </script>
						</td>
					</tr>
					<tr>
						<td> 
							<b>Effort Scale *</b><br/>
							<span style="font-size: 90%"><i>How will effort be graded?.</i></span>
						</td>
						<td class="right">
							<select name="gibbonScaleIDEffort" id="gibbonScaleIDEffort" style="width: 302px">
								<?
								try {
									$dataSelect=array(); 
									$sqlSelect="SELECT * FROM gibbonScale WHERE (active='Y') ORDER BY name" ;
									$resultSelect=$connection2->prepare($sqlSelect);
									$resultSelect->execute($dataSelect);
								}
								catch(PDOException $e) { }
								print "<option value='Please select...'>Please select...</option>" ;
								while ($rowSelect=$resultSelect->fetch()) {
									$selected="" ;
									if ($rowSelect["gibbonScaleID"]==$_SESSION[$guid]["primaryAssessmentScale"]) {
										$selected="selected" ;
									}
									print "<option $selected value='" . $rowSelect["gibbonScaleID"] . "'>" . htmlPrep($rowSelect["name"]) . "</option>" ;
								}
								?>				
							</select>
							<script type="text/javascript">
								var gibbonScaleIDEffort = new LiveValidation('gibbonScaleIDEffort');
								gibbonScaleIDEffort.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "Select something!"});
							 </script>
						</td>
					</tr>
					<tr>
						<td> 
							<b>Effort Rubric</b><br/>
							<span style="font-size: 90%"><i>Choose predefined rubric, if desired.</i></span>
						</td>
						<td class="right">
							<select name="gibbonRubricIDEffort" id="gibbonRubricIDEffort" style="width: 302px">
								<option><option>
								<optgroup label='--School Rubrics --'>
								<?
								try {
									$dataSelect=array(); 
									$sqlSelectWhere="" ;
									$years=explode(",",$row["gibbonYearGroupIDList"]) ;
									foreach ($years as $year) {
										$dataSelect[$year]="%$year%" ;
										$sqlSelectWhere.=" AND gibbonYearGroupIDList LIKE :$year" ;
									}
									$sqlSelect="SELECT * FROM gibbonRubric WHERE active='Y' AND scope='School' $sqlSelectWhere ORDER BY category, name" ;
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
									print "<option value='" . $rowSelect["gibbonRubricID"] . "'>$label</option>" ;
								}
								if ($row["gibbonDepartmentID"]!="") {
									?>
									<optgroup label='--Learning Area Rubrics --'>
									<?
									try {
										$dataSelect=array("gibbonDepartmentID"=>$row["gibbonDepartmentID"]); 
										$sqlSelectWhere="" ;
										$years=explode(",",$row["gibbonYearGroupIDList"]) ;
										foreach ($years as $year) {
											$dataSelect[$year]="%$year%" ;
											$sqlSelectWhere.=" AND gibbonYearGroupIDList LIKE :$year" ;
										}
										$sqlSelect="SELECT * FROM gibbonRubric WHERE active='Y' AND scope='Learning Area' AND gibbonDepartmentID=:gibbonDepartmentID $sqlSelectWhere ORDER BY category, name" ;
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
										print "<option value='" . $rowSelect["gibbonRubricID"] . "'>$label</option>" ;
									}
								}
								?>				
							</select>
							<script type="text/javascript">
								var gibbonScaleIDEffort = new LiveValidation('gibbonScaleIDEffort');
								gibbonScaleIDEffort.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "Select something!"});
							 </script>
						</td>
					</tr>
					
					<tr>
						<td> 
							<b>Viewable to Students *</b><br/>
							<span style="font-size: 90%"><i></i></span>
						</td>
						<td class="right">
							<select name="viewableStudents" id="viewableStudents" style="width: 302px">
								<option <? if ($_GET["viewableStudents"]=="Y") { print "selected " ; }?>value="Y">Y</option>
								<option <? if ($_GET["viewableStudents"]=="N") { print "selected " ; }?>value="N">N</option>
							</select>
						</td>
					</tr>
					<tr>
						<td> 
							<b>Viewable to Parents *</b><br/>
							<span style="font-size: 90%"><i></i></span>
						</td>
						<td class="right">
							<select name="viewableParents" id="viewableParents" style="width: 302px">
								<option <? if ($_GET["viewableParents"]=="Y") { print "selected " ; }?>value="Y">Y</option>
								<option <? if ($_GET["viewableParents"]=="N") { print "selected " ; }?>value="N">N</option>
							</select>
						</td>
					</tr>
					<tr>
						<td> 
							<b>Grading Completion Date</b><br/>
							<span style="font-size: 90%"><i>1. Format: dd/mm/yyyy<br/>2. Enter date after grading<br>3. Column is hidden without date</i></span>
						</td>
						<td class="right">
							<input name="completeDate" id="completeDate" maxlength=10 value="<? print $row["completeDate"] ?>" type="text" style="width: 300px">
							<script type="text/javascript">
								var completeDate = new LiveValidation('completeDate');
								completeDate.add( Validate.Format, {pattern: /^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i, failureMessage: "Use dd/mm/yyyy." } ); 
							 </script>
							 <script type="text/javascript">
								$(function() {
									$( "#completeDate" ).datepicker();
								});
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b>Attachment </b><br/>
						</td>
						<td class="right">
							<input type="file" name="file" id="file"><br/><br/>
							<?
							print getMaxUpload() ;
							
							//Get list of acceptable file extensions
							try {
								$dataExt=array(); 
								$sqlExt="SELECT * FROM gibbonFileExtension" ;
								$resultExt=$connection2->prepare($sqlExt);
								$resultExt->execute($dataExt);
							}
							catch(PDOException $e) { }
							$ext="" ;
							while ($rowExt=$resultExt->fetch()) {
								$ext=$ext . "'." . $rowExt["extension"] . "'," ;
							}
							?>
							
							<script type="text/javascript">
								var file = new LiveValidation('file');
								file.add( Validate.Inclusion, { within: [<? print $ext ;?>], failureMessage: "Illegal file type!", partialMatch: true, caseSensitive: false } );
							</script>
						</td>
					</tr>
					<tr>
						<td>
							<span style="font-size: 90%"><i>* denotes a required field</i></span>
						</td>
						<td class="right">
							<input type="reset" value="Reset"> <input type="submit" value="Submit">
						</td>
					</tr>
				</table>
			</form>
			<?
		}
	}
	
	//Print sidebar
	$_SESSION[$guid]["sidebarExtra"]=sidebarExtra($guid, $connection2, $gibbonCourseClassID) ;
}
?>