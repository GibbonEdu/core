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

if (isActionAccessible($guid, $connection2, "/modules/Markbook/markbook_edit_addMulti.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {	
	//Get action with highest precendence
	$highestAction=getHighestGroupedAction($guid, $_GET["q"], $connection2) ;
	if ($highestAction==FALSE OR ($highestAction!="Edit Markbook_multipleClassesAcrossSchool" AND $highestAction!="Edit Markbook_multipleClassesInDepartment" AND $highestAction!="Edit Markbook_everything")) {
		print "<div class='error'>" ;
		print "The highest grouped action cannot be determined, or you do not have high enough permission to accces this page." ;
		print "</div>" ;
	}
	else {
		$gibbonCourseClassID=$_GET["gibbonCourseClassID"]; 
		if ($gibbonCourseClassID=="") {
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
				$row=$result->fetch() ;
		
				print "<div class='trail'>" ;
				print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/markbook_view.php&gibbonCourseClassID=" . $_GET["gibbonCourseClassID"] . "'>View " . $row["course"] . "." . $row["class"] . " Markbook</a> > </div><div class='trailEnd'>Add Multiple Columns</div>" ;
				print "</div>" ;
	
				if (isset($_GET["addReturn"])) { $addReturn=$_GET["addReturn"] ; } else { $addReturn="" ; }
				$addReturnMessage="" ;
				$class="error" ;
				if (!($addReturn=="")) {
					if ($addReturn=="fail0") {
						$addReturnMessage=_("Your request failed because you do not have access to this action.") ;	
					}
					else if ($addReturn=="fail2") {
						$addReturnMessage=_("Your request failed due to a database error.") ;	
					}
					else if ($addReturn=="fail3") {
						$addReturnMessage=_("Your request failed because your inputs were invalid.") ;	
					}
					else if ($addReturn=="fail4") {
						$addReturnMessage=_("Your request failed because your inputs were invalid.") ;	
					}
					else if ($addReturn=="fail5") {
						$addReturnMessage=_("Your request failed due to an attachment error.") ;	
					}
					else if ($addReturn=="fail6") {
						$updateReturnMessage=_("Your request was successful, but some data was not properly saved.") ;
					}
					else if ($addReturn=="success0") {
						$addReturnMessage=_("Your request was completed successfully.You can now add another record if you wish.") ;	
						$class="success" ;
					}
					print "<div class='$class'>" ;
						print $addReturnMessage;
					print "</div>" ;
				} 
				?>

				<form method="post" action="<? print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/markbook_edit_addMultiProcess.php?gibbonCourseClassID=$gibbonCourseClassID&address=" . $_SESSION[$guid]["address"] ?>" enctype="multipart/form-data">
					<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
						<tr>
							<td> 
								<b><? print _('Class') ?> *</b><br/>
								<span style="font-size: 90%"><i><? print _('Use Control, Command and/or Shift to select multiple.') ?> The current class (<? print $row["course"] . "." . $row["class"] ?>) has already been selected).</i></span>
							</td>
							<td class="right">
								<?
								print "<select multiple name='gibbonCourseClassIDMulti[]' id='gibbonCourseClassIDMulti[]' style='width:300px; height:150px'>" ;
									try {
										if ($highestAction=="Edit Markbook_multipleClassesAcrossSchool" OR $highestAction=="Edit Markbook_everything") {
											$dataSelect=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
											$sqlSelect="SELECT gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourseClass JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY course, class" ;
										}
										else if  ($highestAction=="Edit Markbook_multipleClassesInDepartment") {
											$dataSelect=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
											$sqlSelect="SELECT DISTINCT gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourseClass JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) JOIN gibbonDepartment ON (gibbonCourse.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) JOIN gibbonDepartmentStaff ON (gibbonDepartmentStaff.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) WHERE (role='Coordinator' OR role='Assistant Coordinator' OR role='Teacher (Curriculum)') AND gibbonPersonID=:gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY course, class" ;
										}
										$resultSelect=$connection2->prepare($sqlSelect);
										$resultSelect->execute($dataSelect);
									}
									catch(PDOException $e) { }
									while ($rowSelect=$resultSelect->fetch()) {
										$selected="" ;
										if ($rowSelect["gibbonCourseClassID"]==$gibbonCourseClassID) {
											$selected="selected" ;
										}
										print "<option $selected value='" . $rowSelect["gibbonCourseClassID"] . "'>" . htmlPrep($rowSelect["course"]) . "." . htmlPrep($rowSelect["class"]) . "</option>" ;
									}		
								print "</select>" ;
								?>
							</td>
						</tr>
						<tr>
							<td> 
								<? print "<b>" . _('Name') . " *</b><br/>" ; ?>
							</td>
							<td class="right">
								<input name="name" id="name" maxlength=20 value="" type="text" style="width: 300px">
								<script type="text/javascript">
									var name=new LiveValidation('name');
									name.add(Validate.Presence);
								 </script>
							</td>
						</tr>
						<tr>
							<td> 
								<? print "<b>" . _('Description') . " *</b><br/>" ; ?>
							</td>
							<td class="right">
								<input name="description" id="description" maxlength=255 value="" type="text" style="width: 300px">
								<script type="text/javascript">
									var description=new LiveValidation('description');
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
									<b><? print _('Type') ?> *</b><br/>
									<span style="font-size: 90%"><i></i></span>
								</td>
								<td class="right">
									<select name="type" id="type" style="width: 302px">
										<option value="Please select..."><? print _('Please select...') ?></option>
										<?
										for ($i=0; $i<count($types); $i++) {
											?>
											<option value="<? print trim($types[$i]) ?>"><? print trim($types[$i]) ?></option>
										<?
										}
										?>
									</select>
									<script type="text/javascript">
										var type=new LiveValidation('type');
										type.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<? print _('Select something!') ?>"});
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
									print "<option value='Please select...'>" . _('Please select...') . "</option>" ;
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
									var gibbonScaleIDAttainment=new LiveValidation('gibbonScaleIDAttainment');
									gibbonScaleIDAttainment.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<? print _('Select something!') ?>"});
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
									var gibbonScaleIDEffort=new LiveValidation('gibbonScaleIDEffort');
									gibbonScaleIDEffort.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<? print _('Select something!') ?>"});
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
									print "<option value='Please select...'>" . _('Please select...') . "</option>" ;
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
									var gibbonScaleIDEffort=new LiveValidation('gibbonScaleIDEffort');
									gibbonScaleIDEffort.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<? print _('Select something!') ?>"});
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
									var gibbonScaleIDEffort=new LiveValidation('gibbonScaleIDEffort');
									gibbonScaleIDEffort.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<? print _('Select something!') ?>"});
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
									<option value="Y"><? print _('Y') ?></option>
									<option value="N"><? print _('N') ?></option>
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
									<option value="Y"><? print _('Y') ?></option>
									<option value="N"><? print _('N') ?></option>
								</select>
							</td>
						</tr>
						<tr>
							<td> 
								<b>Grading Completion Date</b><br/>
								<span style="font-size: 90%"><i>1. Format <? if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?><br/>2. Enter date after grading<br/>3. Column is hidden without date</i></span>
							</td>
							<td class="right">
								<input name="completeDate" id="completeDate" maxlength=10 value="" type="text" style="width: 300px">
								<script type="text/javascript">
									var completeDate=new LiveValidation('completeDate');
									completeDate.add( Validate.Format, {pattern: <? if ($_SESSION[$guid]["i18n"]["dateFormatRegEx"]=="") {  print "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i" ; } else { print $_SESSION[$guid]["i18n"]["dateFormatRegEx"] ; } ?>, failureMessage: "Use <? if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?>." } ); 
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
									var file=new LiveValidation('file');
									file.add( Validate.Inclusion, { within: [<? print $ext ;?>], failureMessage: "Illegal file type!", partialMatch: true, caseSensitive: false } );
								</script>
							</td>
						</tr>
						<tr>
							<td>
								<span style="font-size: 90%"><i>* <? print _("denotes a required field") ; ?></i></span>
							</td>
							<td class="right">
								<input type="submit" value="<? print _("Submit") ; ?>">
							</td>
						</tr>
					</table>
				</form>
				<?
			}	
		}
	}
	//Print sidebar
	$_SESSION[$guid]["sidebarExtra"]=sidebarExtra($guid, $connection2, $gibbonCourseClassID) ;
}
?>