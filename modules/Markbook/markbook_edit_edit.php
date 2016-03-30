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
$enableColumnWeighting=getSettingByScope($connection2, "Markbook", "enableColumnWeighting") ;
$attainmentAlternativeName=getSettingByScope($connection2, "Markbook", "attainmentAlternativeName") ;
$attainmentAlternativeNameAbrev=getSettingByScope($connection2, "Markbook", "attainmentAlternativeNameAbrev") ;
$effortAlternativeName=getSettingByScope($connection2, "Markbook", "effortAlternativeName") ;
$effortAlternativeNameAbrev=getSettingByScope($connection2, "Markbook", "effortAlternativeNameAbrev") ;

if (isActionAccessible($guid, $connection2, "/modules/Markbook/markbook_edit_edit.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	$highestAction=getHighestGroupedAction($guid, $_GET["q"], $connection2) ;
	if ($highestAction==FALSE) {
		print "<div class='error'>" ;
		print __($guid, "The highest grouped action cannot be determined.") ;
		print "</div>" ;
	}
	else {
		//Check if school year specified
		$gibbonCourseClassID=$_GET["gibbonCourseClassID"] ;
		$gibbonMarkbookColumnID=$_GET["gibbonMarkbookColumnID"] ;
		if ($gibbonCourseClassID=="" OR $gibbonMarkbookColumnID=="") {
			print "<div class='error'>" ;
				print __($guid, "You have not specified one or more required parameters.") ;
			print "</div>" ;
		}
		else {
			try {
				if ($highestAction=="Edit Markbook_everything") {
					$data=array("gibbonCourseClassID"=>$gibbonCourseClassID); 
					$sql="SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourseClass.gibbonCourseClassID, gibbonCourse.gibbonDepartmentID, gibbonYearGroupIDList FROM gibbonCourse, gibbonCourseClass WHERE gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID ORDER BY course, class" ;
				}
				else {
					$data=array("gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"], "gibbonCourseClassID"=>$gibbonCourseClassID, "gibbonPersonID2"=>$_SESSION[$guid]["gibbonPersonID"], "gibbonCourseClassID2"=>$gibbonCourseClassID, "gibbonMarkbookColumnID"=>$gibbonMarkbookColumnID); 
					$sql="(SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourseClass.gibbonCourseClassID, gibbonCourse.gibbonDepartmentID, gibbonYearGroupIDList FROM gibbonCourse, gibbonCourseClass, gibbonCourseClassPerson WHERE gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID AND gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND role='Teacher' AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID)
					UNION
					(SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourseClass.gibbonCourseClassID, gibbonCourse.gibbonDepartmentID, gibbonYearGroupIDList FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) JOIN gibbonMarkbookColumn ON (gibbonMarkbookColumn.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) WHERE gibbonMarkbookColumn.gibbonPersonIDCreator=:gibbonPersonID2 AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID2 AND gibbonMarkbookColumnID=:gibbonMarkbookColumnID)
					ORDER BY course, class" ;
				}	
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
					$data2=array("gibbonMarkbookColumnID"=>$gibbonMarkbookColumnID); 
					$sql2="SELECT * FROM gibbonMarkbookColumn WHERE gibbonMarkbookColumnID=:gibbonMarkbookColumnID" ;
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
					print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/markbook_view.php&gibbonCourseClassID=" . $_GET["gibbonCourseClassID"] . "'>" . __($guid, 'View') . " " . $row["course"] . "." . $row["class"] . " " . __($guid, 'Markbook') . "</a> > </div><div class='trailEnd'>" . __($guid, 'Edit Column') . "</div>" ;
					print "</div>" ;
				
					if ($row2["groupingID"]!="" AND $row2["gibbonPersonIDCreator"]!=$_SESSION[$guid]["gibbonPersonID"]) {
						print "<div class='error'>" ;
							print __($guid, "This column is part of a set of columns, which you did not create, and so cannot be individually edited.") ;
						print "</div>" ;
					}
					else {
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
							else if ($updateReturn=="fail4") {
								$updateReturnMessage=__($guid, "Your request failed because your inputs were invalid.") ;	
							}
							else if ($updateReturn=="fail5") {
								$updateReturnMessage=__($guid, "Your request failed due to an attachment error.") ;	
							}
							else if ($updateReturn=="fail6") {
								$updateReturnMessage=__($guid, "Your request failed because you already have one \"End of Year\" column for this class.") ;	
							}
							else if ($updateReturn=="success0") {
								$updateReturnMessage=__($guid, "Your request was completed successfully.") ;	
								$class="success" ;
							}
							print "<div class='$class'>" ;
								print $updateReturnMessage;
							print "</div>" ;
						} 
				
						print "<div class='linkTop'>" ;
						print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/markbook_edit_data.php&gibbonCourseClassID=$gibbonCourseClassID&gibbonMarkbookColumnID=$gibbonMarkbookColumnID'>" . __($guid, 'Enter Data') . "<img style='margin: 0 0 0px 5px' title='" . __($guid, 'Enter Data') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/markbook.png'/></a> " ;
						print "</div>" ;
						?>
						<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/markbook_edit_editProcess.php?gibbonMarkbookColumnID=$gibbonMarkbookColumnID&gibbonCourseClassID=$gibbonCourseClassID&address=" . $_SESSION[$guid]["address"] ?>" enctype="multipart/form-data">
							<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
								<tr class='break'>
									<td colspan=2> 
										<h3><?php print __($guid, 'Basic Information') ?></h3>
									</td>
								</tr>
								<tr>
									<td style='width: 275px'> 
										<b><?php print __($guid, 'Class') ?> *</b><br/>
										<span style="font-size: 90%"><i><?php print __($guid, 'This value cannot be changed.') ?></i></span>
									</td>
									<td class="right">
										<input readonly name="schoolYearName" id="schoolYearName" maxlength=20 value="<?php print htmlPrep($row["course"]) . "." . htmlPrep($row["class"]) ?>" type="text" style="width: 300px">
									</td>
								</tr>
								<tr>
									<td> 
										<b><?php print __($guid, 'Unit') ?></b><br/>
									</td>
									<td class="right">
										<select name="gibbonUnitID" id="gibbonUnitID" style="width: 302px">
											<?php
											try {
												$dataSelect=array("gibbonCourseClassID"=>$gibbonCourseClassID); 
												$sqlSelect="SELECT * FROM gibbonUnit JOIN gibbonUnitClass ON (gibbonUnit.gibbonUnitID=gibbonUnitClass.gibbonUnitID) WHERE running='Y' AND gibbonCourseClassID=:gibbonCourseClassID ORDER BY name" ;
												$resultSelect=$connection2->prepare($sqlSelect);
												$resultSelect->execute($dataSelect);
											}
											catch(PDOException $e) { }

											$lastType="" ;
											$currentType="" ;
											print "<option value=''></option>" ;
											while ($rowSelect=$resultSelect->fetch()) {
												$selected="" ;
												if ($rowSelect["gibbonUnitID"]==$row2["gibbonUnitID"] AND $rowSelect["gibbonCourseClassID"]==$row2["gibbonCourseClassID"]) {
													$selected="selected" ;
												}
												$currentType=$rowSelect["type"] ;
												if ($currentType!=$lastType) {
													print "<optgroup label='--" . $currentType . "--'>" ;
												}
												print "<option $selected class='" . $rowSelect["gibbonCourseClassID"] . "' value='" . $rowSelect["gibbonUnitID"] . "'>" . htmlPrep($rowSelect["name"]) . "</option>" ;
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
											catch(PDOException $e) { 
												print "<div class='error'>" . $e->getMessage() . "</div>" ; 
											}

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
														$selected="" ;
														if ($rowHookUnits[$hookOptions["unitIDField"]]==$row2["gibbonUnitID"] AND $rowHooks["gibbonHookID"]==$row2["gibbonHookID"] AND $rowHookUnits[$hookOptions["classLinkJoinFieldClass"]]==$row2["gibbonCourseClassID"]) {
															$selected="selected" ;
														}
														$currentType=$rowHooks["name"] ;
														if ($currentType!=$lastType) {
															print "<optgroup label='--" . $currentType . "--'>" ;
														}
														print "<option $selected class='" . $rowHookUnits[$hookOptions["classLinkIDField"]] . "' value='" . $rowHookUnits[$hookOptions["unitIDField"]] . "-" . $rowHooks["gibbonHookID"] . "'>" . htmlPrep($rowHookUnits[$hookOptions["unitNameField"]]) . "</option>" ;
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
										<b><?php print __($guid, 'Lesson') ?></b><br/>
									</td>
									<td class="right">
										<select name="gibbonPlannerEntryID" id="gibbonPlannerEntryID" style="width: 302px">
											<?php
											try {
												$dataSelect=array("gibbonCourseClassID"=>$row["gibbonCourseClassID"]); 
												$sqlSelect="SELECT * FROM gibbonPlannerEntry WHERE gibbonCourseClassID=:gibbonCourseClassID ORDER BY name" ;
												$resultSelect=$connection2->prepare($sqlSelect);
												$resultSelect->execute($dataSelect);
											}
											catch(PDOException $e) { }
											print "<option value=''></option>" ;
											while ($rowSelect=$resultSelect->fetch()) {
												$selected="" ;
												if ($rowSelect["gibbonPlannerEntryID"]==$row2["gibbonPlannerEntryID"]) {
													$selected="selected " ;
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
										<b><?php print __($guid, 'Name') ?> *</b><br/>
									</td>
									<td class="right">
										<input name="name" id="name" maxlength=20 value="<?php print htmlPrep($row2["name"]) ?>" type="text" style="width: 300px">
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
										<input name="description" id="description" maxlength=1000 value="<?php print htmlPrep($row2["description"]) ?>" type="text" style="width: 300px">
										<script type="text/javascript">
											var description=new LiveValidation('description');
											description.add(Validate.Presence);
										</script>
									</td>
								</tr>
								<?php
								$types=getSettingByScope($connection2, "Markbook", "markbookType") ;
								if ($types!=FALSE) {
									$types=explode(",", $types) ;
									?>
									<tr>
										<td> 
											<b><?php print __($guid, 'Type') ?> *</b><br/>
											<span style="font-size: 90%"><i></i></span>
										</td>
										<td class="right">
											<select name="type" id="type" style="width: 302px">
												<option value="Please select..."><?php print __($guid, 'Please select...') ?></option>
												<?php
												for ($i=0; $i<count($types); $i++) {
													$selected="" ;
													if ($row2["type"]==$types[$i]) {
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
										<span style="font-size: 90%"><i><?php print __($guid, 'Will overwrite existing attachment.') ?></i></span>
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
												$("#attainmentWeightingRow").slideDown("fast", $("#attainmentWeightingRow").css("display","table-row")); 
												$("#gibbonRubricIDAttainmentRow").slideDown("fast", $("#gibbonRubricIDAttainmentRow").css("display","table-row")); 
												
											} else {
												$("#gibbonScaleIDAttainmentRow").css("display","none");
												$("#attainmentWeightingRow").css("display","none");
												$("#gibbonRubricIDAttainmentRow").css("display","none");
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
										<b><?php if ($attainmentAlternativeName!="") { print $attainmentAlternativeName . " " . __($guid, 'Scale') ; } else { print __($guid, 'Attainment Scale') ; } ?></b><br/>
									</td>
									<td class="right">
										<select name="gibbonScaleIDAttainment" id="gibbonScaleIDAttainment" style="width: 302px">
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
								<?php
								if ($enableColumnWeighting=="Y") {
									?>
									<tr id="attainmentWeightingRow" <?php if ($row2["attainment"]=="N") { print "style='display: none'" ; } ?>>
										<td> 
											<b><?php if ($attainmentAlternativeName!="") { print $attainmentAlternativeName . " " . __($guid, 'Weighting') ; } else { print __($guid, 'Attainment Weighting') ; } ?></b><br/>
										</td>
										<td class="right">
											<input name="attainmentWeighting" id="attainmentWeighting" maxlength=3 value="<?php print $row2["attainmentWeighting"] ?>" type="text" style="width: 300px">
											<script type="text/javascript">
												var attainmentWeighting=new LiveValidation('attainmentWeighting');
												attainmentWeighting.add(Validate.Numericality);
											</script>
										</td>
									</tr>
									<?php
								}
								?>
								<tr id='gibbonRubricIDAttainmentRow' <?php if ($row2["attainment"]=="N") { print "style='display: none'" ; } ?>>
									<td> 
										<b><?php if ($attainmentAlternativeName!="") { print $attainmentAlternativeName . " " . __($guid, 'Rubric') ; } else { print __($guid, 'Attainment Rubric') ; } ?></b><br/>
										<span style="font-size: 90%"><i><?php print __($guid, 'Choose predefined rubric, if desired.') ?></i></span>
									</td>
									<td class="right">
										<select name="gibbonRubricIDAttainment" id="gibbonRubricIDAttainment" style="width: 302px">
											<option><option>
											<optgroup label='--<?php print __($guid, 'School Rubrics') ?>--'>
											<?php
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
												$selected="" ;
												if ($row2["gibbonRubricIDAttainment"]==$rowSelect["gibbonRubricID"]) {
													$selected="selected" ;
												}
												print "<option $selected value='" . $rowSelect["gibbonRubricID"] . "'>$label</option>" ;
											}
											if ($row["gibbonDepartmentID"]!="") {
												?>
												<optgroup label='--<?php print __($guid, 'Learning Area Rubrics') ?>--'>
												<?php
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
													$selected="" ;
													if ($row2["gibbonRubricIDAttainment"]==$rowSelect["gibbonRubricID"]) {
														$selected="selected" ;
													}
													print "<option $selected value='" . $rowSelect["gibbonRubricID"] . "'>$label</option>" ;
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
										<b><?php if ($effortAlternativeName!="") { print $effortAlternativeName . " " . __($guid, 'Scale') ; } else { print __($guid, 'Effort Scale') ; } ?></b><br/>
									</td>
									<td class="right">
										<select name="gibbonScaleIDEffort" id="gibbonScaleIDEffort" style="width: 302px">
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
								<tr id='gibbonRubricIDEffortRow' <?php if ($row2["effort"]=="N") { print "style='display: none'" ; } ?>>
									<td> 
										<b><?php if ($effortAlternativeName!="") { print $effortAlternativeName . " " . __($guid, 'Rubric') ; } else { print __($guid, 'Effort Rubric') ; } ?></b><br/>
										<span style="font-size: 90%"><i><?php print __($guid, 'Choose predefined rubric, if desired.') ?></i></span>
									</td>
									<td class="right">
										<select name="gibbonRubricIDEffort" id="gibbonRubricIDEffort" style="width: 302px">
											<option><option>
											<optgroup label='--<?php print __($guid, 'School Rubrics') ?>--'>
											<?php
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
												$selected="" ;
												if ($row2["gibbonRubricIDEffort"]==$rowSelect["gibbonRubricID"]) {
													$selected="selected" ;
												}
												print "<option $selected value='" . $rowSelect["gibbonRubricID"] . "'>$label</option>" ;
											}
											if ($row["gibbonDepartmentID"]!="") {
												?>
												<optgroup label='--<?php print __($guid, 'Learning Area Rubrics') ?>--'>
												<?php
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
													$selected="" ;
													if ($row2["gibbonRubricIDEffort"]==$rowSelect["gibbonRubricID"]) {
														$selected="selected" ;
													}
													print "<option $selected value='" . $rowSelect["gibbonRubricID"] . "'>$label</option>" ;
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
										<span style="font-size: 90%"><i></i></span>
									</td>
									<td class="right">
										<select name="viewableStudents" id="viewableStudents" style="width: 302px">
											<option <?php if ($row2["viewableStudents"]=="N") { print "selected ";} ?>value="N"><?php print __($guid, 'No') ?></option>
											<option <?php if ($row2["viewableStudents"]=="Y") { print "selected ";} ?>value="Y"><?php print __($guid, 'Yes') ?></option>
										</select>
									</td>
								</tr>
								<tr>
									<td> 
										<b><?php print __($guid, 'Viewable to Parents') ?> *</b><br/>
										<span style="font-size: 90%"><i></i></span>
									</td>
									<td class="right">
										<select name="viewableParents" id="viewableParents" style="width: 302px">
											<option <?php if ($row2["viewableParents"]=="N") { print "selected ";} ?>value="N"><?php print __($guid, 'No') ?></option>
											<option <?php if ($row2["viewableParents"]=="Y") { print "selected ";} ?>value="Y"><?php print __($guid, 'Yes') ?></option>
										</select>
									</td>
								</tr>
								<tr>
									<td> 
										<b><?php print __($guid, 'Go Live Date') ?></b><br/>
										<span style="font-size: 90%"><i><?php print __($guid, '1. Format') ?> <?php if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?><br/><?php print __($guid, '2. Column is hidden until date is reached.') ?></i></span>
									</td>
									<td class="right">
										<input name="completeDate" id="completeDate" maxlength=10 value="<?php print dateConvertBack($guid, $row2["completeDate"]) ?>" type="text" style="width: 300px">
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
										<span style="font-size: 90%"><i>* <?php print __($guid, "denotes a required field") ; ?><br/>
										<?php print getMaxUpload($guid) ; ?>
										</i></span>
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
		}
	}
}
?>