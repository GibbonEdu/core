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

//Get alternative header names
$attainmentAlternativeName=getSettingByScope($connection2, "Markbook", "attainmentAlternativeName") ;
$attainmentAlternativeNameAbrev=getSettingByScope($connection2, "Markbook", "attainmentAlternativeNameAbrev") ;
$effortAlternativeName=getSettingByScope($connection2, "Markbook", "effortAlternativeName") ;
$effortAlternativeNameAbrev=getSettingByScope($connection2, "Markbook", "effortAlternativeNameAbrev") ;

if (isActionAccessible($guid, $connection2, "/modules/Formal Assessment/internalAssessment_manage_edit.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Check if school year specified
	$gibbonCourseClassID=$_GET["gibbonCourseClassID"] ;
	$gibbonInternalAssessmentColumnID=$_GET["gibbonInternalAssessmentColumnID"] ;
	if ($gibbonCourseClassID=="" OR $gibbonInternalAssessmentColumnID=="") {
		print "<div class='error'>" ;
			print __($guid, "You have not specified one or more required parameters.") ;
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
				print __($guid, "The selected record does not exist, or you do not have access to it.") ;
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
					print __($guid, "The selected record does not exist, or you do not have access to it.") ;
				print "</div>" ;
			}
			else {
				//Let's go!
				$row=$result->fetch() ;
				$row2=$result2->fetch() ;
			
				print "<div class='trail'>" ;
				print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/internalAssessment_manage.php&gibbonCourseClassID=" . $_GET["gibbonCourseClassID"] . "'>" . __($guid, 'Manage') . " " . $row["course"] . "." . $row["class"] . " " . __($guid, 'Internal Assessments') . "</a> > </div><div class='trailEnd'>" . __($guid, 'Edit Column') . "</div>" ;
				print "</div>" ;
			
				if ($row2["groupingID"]!="" AND $row2["gibbonPersonIDCreator"]!=$_SESSION[$guid]["gibbonPersonID"]) {
					print "<div class='error'>" ;
						print __($guid, "This column is part of a set of columns, which you did not create, and so cannot be individually edited.") ;
					print "</div>" ;
				}
				else {

					if (isset($_GET["return"])) { returnProcess($guid, $_GET["return"], null, array("error3" => "Your request failed due to an attachment error.", "success0" => "Your request was completed successfully.")); }
			
					?>
					<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/internalAssessment_manage_editProcess.php?gibbonInternalAssessmentColumnID=$gibbonInternalAssessmentColumnID&gibbonCourseClassID=$gibbonCourseClassID&address=" . $_SESSION[$guid]["address"] ?>" enctype="multipart/form-data">
						<table class='smallIntBorder fullWidth' cellspacing='0'>	
							<tr class='break'>
								<td colspan=2> 
									<h3><?php print __($guid, 'Basic Information') ?></h3>
								</td>
							</tr>
							<tr>
								<td style='width: 275px'> 
									<b><?php print __($guid, 'Class') ?> *</b><br/>
									<span class="emphasis small"><?php print __($guid, 'This value cannot be changed.') ?></span>
								</td>
								<td class="right">
									<input readonly name="schoolYearName" id="schoolYearName" maxlength=20 value="<?php print htmlPrep($row["course"]) . "." . htmlPrep($row["class"]) ?>" type="text" class="standardWidth">
								</td>
							</tr>
							<tr>
								<td> 
									<b><?php print __($guid, 'Name') ?> *</b><br/>
								</td>
								<td class="right">
									<input name="name" id="name" maxlength=20 value="<?php print htmlPrep($row2["name"]) ?>" type="text" class="standardWidth">
									<script type="text/javascript">
										var name2=new LiveValidation('name');
										name2.add(Validate.Presence);
									</script>
								</td>
							</tr>
							<tr>
								<td> 
									<b><?php print __($guid, 'Description') ?> *</b><br/>
								</td>
								<td class="right">
									<input name="description" id="description" maxlength=1000 value="<?php print htmlPrep($row2["description"]) ?>" type="text" class="standardWidth">
									<script type="text/javascript">
										var description=new LiveValidation('description');
										description.add(Validate.Presence);
									</script>
								</td>
							</tr>
							<?php
							$types=getSettingByScope($connection2, "Formal Assessment", "internalAssessmentTypes") ;
							if ($types!=FALSE) {
								$types=explode(",", $types) ;
								?>
								<tr>
									<td> 
										<b><?php print __($guid, 'Type') ?> *</b><br/>
										<span class="emphasis small"></span>
									</td>
									<td class="right">
										<select name="type" id="type" class="standardWidth">
											<option value="Please select..."><?php print __($guid, 'Please select...') ?></option>
											<?php
											for ($i=0; $i<count($types); $i++) {
												$selected="" ;
												if ($row2["type"]==trim($types[$i])) {
													$selected="selected" ;
												}
												?>
												<option <?php print $selected ?> value="<?php print trim($types[$i]) ?>"><?php print trim($types[$i]) ?></option>
											<?php
											}
											?>
										</select>
										<script type="text/javascript">
											var type=new LiveValidation('type');
											type.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print __($guid, 'Select something!') ?>"});
										</script>
									</td>
								</tr>
								<?php
							}
							?>
							<tr>
								<td> 
									<b><?php print __($guid, 'Attachment') ?></b><br/>
									<?php if ($row2["attachment"]!="") { ?>
									<span class="emphasis small"><?php print __($guid, 'Will overwrite existing attachment.') ?></span>
									<?php } ?>
								</td>
								<td class="right">
									<?php
									if ($row2["attachment"]!="") {
										print __($guid, "Current attachment:") . " <a href='" . $_SESSION[$guid]["absoluteURL"] . "/" . $row2["attachment"] . "'>" . $row2["attachment"] . "</a><br/><br/>" ;
									}
									?>
									<input type="file" name="file" id="file"><br/><br/>
									<?php
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
										file.add( Validate.Inclusion, { within: [<?php print $ext ;?>], failureMessage: "Illegal file type!", partialMatch: true, caseSensitive: false } );
									</script>
								</td>
							</tr>
							
							
							<tr class='break'>
								<td colspan=2> 
									<h3>
										<?php print __($guid, "Assessment")  ?>
									</h3>
								</td>
							</tr>
							<script type="text/javascript">
								/* Homework Control */
								$(document).ready(function(){
									 $(".attainment").click(function(){
										if ($('input[name=attainment]:checked').val()=="Y" ) {
											$("#gibbonScaleIDAttainmentRow").slideDown("fast", $("#gibbonScaleIDAttainmentRow").css("display","table-row")); 
										} else {
											$("#gibbonScaleIDAttainmentRow").css("display","none");
										}
									 });
								});
							</script>
							<tr>
								<td> 
									<b><?php if ($attainmentAlternativeName!="") { print sprintf(__($guid, 'Assess %1$s?'), $attainmentAlternativeName) ; } else { print __($guid, 'Assess Attainment?') ; } ?> *</b><br/>
								</td>
								<td class="right">
									<input <?php if ($row2["attainment"]=="Y") { print "checked" ; } ?> type="radio" name="attainment" value="Y" class="attainment" /> <?php print __($guid, 'Yes') ?>
									<input <?php if ($row2["attainment"]=="N") { print "checked" ; } ?> type="radio" name="attainment" value="N" class="attainment" /> <?php print __($guid, 'No') ?>
								</td>
							</tr>
							<tr id='gibbonScaleIDAttainmentRow' <?php if ($row2["attainment"]=="N") { print "style='display: none'" ; } ?>>
								<td> 
									<b><?php if ($attainmentAlternativeName!="") { print $attainmentAlternativeName . " " . __($guid, 'Scale') ; } else { print __($guid, 'Attainment Scale') ; } ?> *</b><br/>
								</td>
								<td class="right">
									<select name="gibbonScaleIDAttainment" id="gibbonScaleIDAttainment" class="standardWidth">
										<?php
										try {
											$dataSelect=array(); 
											$sqlSelect="SELECT * FROM gibbonScale WHERE (active='Y') ORDER BY name" ;
											$resultSelect=$connection2->prepare($sqlSelect);
											$resultSelect->execute($dataSelect);
										}
										catch(PDOException $e) { }
										print "<option value=''></option>" ;
										while ($rowSelect=$resultSelect->fetch()) {
											if ($row2["gibbonScaleIDAttainment"]==$rowSelect["gibbonScaleID"]) {
												print "<option selected value='" . $rowSelect["gibbonScaleID"] . "'>" . htmlPrep(__($guid, $rowSelect["name"])) . "</option>" ;
											}
											else {
												print "<option value='" . $rowSelect["gibbonScaleID"] . "'>" . htmlPrep(__($guid, $rowSelect["name"])) . "</option>" ;
											}
										}
										?>				
									</select>
								</td>
							</tr>
							<script type="text/javascript">
								/* Homework Control */
								$(document).ready(function(){
									 $(".effort").click(function(){
										if ($('input[name=effort]:checked').val()=="Y" ) {
											$("#gibbonScaleIDEffortRow").slideDown("fast", $("#gibbonScaleIDEffortRow").css("display","table-row")); 
											$("#gibbonRubricIDEffortRow").slideDown("fast", $("#gibbonRubricIDEffortRow").css("display","table-row")); 

										} else {
											$("#gibbonScaleIDEffortRow").css("display","none");
											$("#gibbonRubricIDEffortRow").css("display","none");
										}
									 });
								});
							</script>
							<tr>
								<td> 
									<b><?php if ($effortAlternativeName!="") { print sprintf(__($guid, 'Assess %1$s?'), $effortAlternativeName) ; } else { print __($guid, 'Assess Effort?') ; } ?> *</b><br/>
								</td>
								<td class="right">
									<input <?php if ($row2["effort"]=="Y") { print "checked" ; } ?> type="radio" name="effort" value="Y" class="effort" /> <?php print __($guid, 'Yes') ?>
									<input <?php if ($row2["effort"]=="N") { print "checked" ; } ?> type="radio" name="effort" value="N" class="effort" /> <?php print __($guid, 'No') ?>
								</td>
							</tr>
							<tr id='gibbonScaleIDEffortRow' <?php if ($row2["effort"]=="N") { print "style='display: none'" ; } ?>>
								<td> 
									<b><?php if ($effortAlternativeName!="") { print $effortAlternativeName . " " . __($guid, 'Scale') ; } else { print __($guid, 'Effort Scale') ; } ?> *</b><br/>
								</td>
								<td class="right">
									<select name="gibbonScaleIDEffort" id="gibbonScaleIDEffort" class="standardWidth">
										<?php
										try {
											$dataSelect=array(); 
											$sqlSelect="SELECT * FROM gibbonScale WHERE (active='Y') ORDER BY name" ;
											$resultSelect=$connection2->prepare($sqlSelect);
											$resultSelect->execute($dataSelect);
										}
										catch(PDOException $e) { }
										print "<option value=''></option>" ;
										while ($rowSelect=$resultSelect->fetch()) {
											if ($row2["gibbonScaleIDEffort"]==$rowSelect["gibbonScaleID"]) {
												print "<option selected value='" . $rowSelect["gibbonScaleID"] . "'>" . htmlPrep(__($guid, $rowSelect["name"])) . "</option>" ;
											}
											else {
												print "<option value='" . $rowSelect["gibbonScaleID"] . "'>" . htmlPrep(__($guid, $rowSelect["name"])) . "</option>" ;
											}
										}
										?>				
									</select>
								</td>
							</tr>
							<tr>
								<td> 
									<b><?php print __($guid, 'Include Comment?') ?> *</b><br/>
								</td>
								<td class="right">
									<input <?php if ($row2["comment"]=="Y") { print "checked" ; } ?> type="radio" name="comment" value="Y" class="comment" /> <?php print __($guid, 'Yes') ?>
									<input <?php if ($row2["comment"]=="N") { print "checked" ; } ?> type="radio" name="comment" value="N" class="comment" /> <?php print __($guid, 'No') ?>
								</td>
							</tr>
							<tr>
								<td> 
									<b><?php print __($guid, 'Include Uploaded Response?') ?> *</b><br/>
								</td>
								<td class="right">
									<input <?php if ($row2["uploadedResponse"]=="Y") { print "checked" ; } ?> type="radio" name="uploadedResponse" value="Y" class="uploadedResponse" /> <?php print __($guid, 'Yes') ?>
									<input <?php if ($row2["uploadedResponse"]=="N") { print "checked" ; } ?> type="radio" name="uploadedResponse" value="N" class="uploadedResponse" /> <?php print __($guid, 'No') ?>
								</td>
							</tr>
							
							<tr class='break'>
								<td colspan=2> 
									<h3><?php print __($guid, 'Access') ?></h3>
								</td>
							</tr>
							<tr>
								<td> 
									<b><?php print __($guid, 'Viewable to Students') ?> *</b><br/>
									<span class="emphasis small"></span>
								</td>
								<td class="right">
									<select name="viewableStudents" id="viewableStudents" class="standardWidth">
										<option <?php if ($row2["viewableStudents"]=="N") { print "selected ";} ?>value="N"><?php print __($guid, 'No') ?></option>
										<option <?php if ($row2["viewableStudents"]=="Y") { print "selected ";} ?>value="Y"><?php print __($guid, 'Yes') ?></option>
									</select>
								</td>
							</tr>
							<tr>
								<td> 
									<b><?php print __($guid, 'Viewable to Parents') ?> *</b><br/>
									<span class="emphasis small"></span>
								</td>
								<td class="right">
									<select name="viewableParents" id="viewableParents" class="standardWidth">
										<option <?php if ($row2["viewableParents"]=="N") { print "selected ";} ?>value="N"><?php print __($guid, 'No') ?></option>
										<option <?php if ($row2["viewableParents"]=="Y") { print "selected ";} ?>value="Y"><?php print __($guid, 'Yes') ?></option>
									</select>
								</td>
							</tr>
							<tr>
								<td> 
									<b><?php print __($guid, 'Go Live Date') ?></b><br/>
									<span class="emphasis small"><?php print __($guid, '1. Format') ?> <?php if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?><br/><?php print __($guid, '2. Column is hidden until date is reached.') ?></span>
								</td>
								<td class="right">
									<input name="completeDate" id="completeDate" maxlength=10 value="<?php print dateConvertBack($guid, $row2["completeDate"]) ?>" type="text" class="standardWidth">
									<script type="text/javascript">
										var completeDate=new LiveValidation('completeDate');
										completeDate.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]["i18n"]["dateFormatRegEx"]=="") {  print "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i" ; } else { print $_SESSION[$guid]["i18n"]["dateFormatRegEx"] ; } ?>, failureMessage: "Use <?php if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?>." } ); 
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
									<span class="emphasis small">* <?php print __($guid, "denotes a required field") ; ?><br/>
									<?php print getMaxUpload($guid) ; ?>
									</span>
								</td>
								<td class="right">
									<input type="submit" value="<?php print __($guid, "Submit") ; ?>">
								</td>
							</tr>
						</table>
					</form>
					<?php
				}
			}
		}
	
		//Print sidebar
		$_SESSION[$guid]["sidebarExtra"]=sidebarExtra($guid, $connection2, $gibbonCourseClassID) ;
	}
}
?>