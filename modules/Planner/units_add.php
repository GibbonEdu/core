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

if (isActionAccessible($guid, $connection2, "/modules/Planner/units_add.php")==FALSE) {
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
		//Proceed!
		print "<div class='trail'>" ;
		print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/units.php&gibbonSchoolYearID=" . $_GET["gibbonSchoolYearID"] . "&gibbonCourseID=" . $_GET["gibbonCourseID"] . "'>" . __($guid, 'Unit Planner') . "</a> > </div><div class='trailEnd'>" . __($guid, 'Add Unit') . "</div>" ;
		print "</div>" ;
		
		if (isset($_GET["return"])) { returnProcess($guid, $_GET["return"], null, null); }
			
		$gibbonSchoolYearID=$_GET["gibbonSchoolYearID"]; 
		$gibbonCourseID=$_GET["gibbonCourseID"]; 
		
		if ($gibbonSchoolYearID=="") {
			print "<div class='error'>" ;
				print __($guid, "You have not specified one or more required parameters.") ;
			print "</div>" ;
		}
		else {
			try {
				$data=array("gibbonSchoolYearID"=>$gibbonSchoolYearID); 
				$sql="SELECT * FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearID" ;
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
				$row=$result->fetch() ;
				
				if ($gibbonCourseID=="") {
					print "<div class='error'>" ;
						print __($guid, "You have not specified one or more required parameters.") ;
					print "</div>" ;
				}
				else {
					try {
						if ($highestAction=="Unit Planner_all") {
							$dataCourse=array("gibbonSchoolYearID"=>$gibbonSchoolYearID, "gibbonCourseID"=>$gibbonCourseID); 
							$sqlCourse="SELECT * FROM gibbonCourse WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseID=:gibbonCourseID" ;
						}
						else if ($highestAction=="Unit Planner_learningAreas") {
							$dataCourse=array("gibbonSchoolYearID"=>$gibbonSchoolYearID, "gibbonCourseID"=>$gibbonCourseID, "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
							$sqlCourse="SELECT gibbonCourseID, gibbonCourse.name, gibbonCourse.nameShort, gibbonCourse.gibbonYearGroupIDList, gibbonCourse.gibbonDepartmentID FROM gibbonCourse JOIN gibbonDepartment ON (gibbonCourse.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) JOIN gibbonDepartmentStaff ON (gibbonDepartmentStaff.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) WHERE gibbonDepartmentStaff.gibbonPersonID=:gibbonPersonID AND (role='Coordinator' OR role='Assistant Coordinator' OR role='Teacher (Curriculum)') AND gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseID=:gibbonCourseID ORDER BY gibbonCourse.nameShort" ;
						}
						$resultCourse=$connection2->prepare($sqlCourse);
						$resultCourse->execute($dataCourse);
					}
					catch(PDOException $e) { 
						print "<div class='error'>" . $e->getMessage() . "</div>" ; 
					}

					if ($resultCourse->rowCount()!=1) {
						print "<div class='error'>" ;
							print "The selected record does not exist, or you do not have access to it." ;
						print "</div>" ;
					}
					else{
						$rowCourse=$resultCourse->fetch() ;
						$gibbonYearGroupIDList=$rowCourse["gibbonYearGroupIDList"] ;
						?>
						<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/units_addProcess.php?gibbonSchoolYearID=$gibbonSchoolYearID&gibbonCourseID=$gibbonCourseID&address=" . $_GET["q"] ?>" enctype="multipart/form-data">
							<table class='smallIntBorder fullWidth' cellspacing='0'>	
								<tr class='break'>
									<td colspan=2> 
										<h3><?php print __($guid, 'Unit Basics') ?></h3>
									</td>
								</tr>
								<tr>
									<td style='width: 275px'> 
										<b><?php print __($guid, 'School Year') ?> *</b><br/>
										<span class="emphasis small"><?php print __($guid, 'This value cannot be changed.') ?></span>
									</td>
									<td class="right">
										<input readonly name="yearName" id="yearName" maxlength=20 value="<?php print $row["name"] ?>" type="text" class="standardWidth">
									</td>
								</tr>
								<tr>
									<td> 
										<b><?php print __($guid, 'Course') ?> *</b><br/>
										<span class="emphasis small"><?php print __($guid, 'This value cannot be changed.') ?></span>
									</td>
									<td class="right">
										<input readonly name="courseName" id="courseName" maxlength=20 value="<?php print $rowCourse["nameShort"] ?>" type="text" class="standardWidth">
									</td>
								</tr>
								<tr>
									<td> 
										<b><?php print __($guid, 'Name') ?> *</b><br/>
										<span class="emphasis small"></span>
									</td>
									<td class="right">
										<input name="name" id="name" maxlength=40 value="" type="text" class="standardWidth">
										<script type="text/javascript">
											var name2=new LiveValidation('name');
											name2.add(Validate.Presence);
										</script>
									</td>
								</tr>
								<tr>
									<td colspan=2> 
										<b><?php print __($guid, 'Blurb') ?> *</b> 
										<textarea name='description' id='description' rows=5 style='width: 300px'></textarea>
										<script type="text/javascript">
											var description=new LiveValidation('description');
											description.add(Validate.Presence);
										</script>
									</td>
								</tr>
								<tr>
									<td> 
										<b><?php print __($guid, 'Ordering') ?> *</b><br/>
										<span class="emphasis small"><?php print __($guid, "Units are arranged form lowest to highest ordering value, then alphabetically.") ; ?></span>
									</td>
									<td class="right">
										<input name="ordering" id="ordering" maxlength=4 value="0" type="text" class="standardWidth">
										<script type="text/javascript">
											var ordering=new LiveValidation('ordering');
											ordering.add(Validate.Presence);
											ordering.add(Validate.Numericality);
										</script>
									</td>
								</tr>
								<tr>
									<td> 
										<b><?php print __($guid, "License") ?></b><br/>
										<span class="emphasis small"><?php print __($guid, "Under what conditions can this work be reused?") ; ?></span>
									</td>
									<td class="right">
										<select name="license" id="license" class="standardWidth">
											<option value=""></option>
											<option value="Copyright"><?php print __($guid, 'Copyright') ?></option>
											<option value="Creative Commons BY"><?php print __($guid, 'Creative Commons BY') ?></option>
											<option value="Creative Commons BY-SA"><?php print __($guid, 'Creative Commons BY-SA') ?></option>
											<option value="Creative Commons BY-SA-NC"><?php print __($guid, 'Creative Commons BY-SA-NC') ?></option>
											<option value="Public Domain"><?php print __($guid, 'Public Domain') ?></option>
										</select>
									</td>
								</tr>
								<?php
								$makeUnitsPublic=getSettingByScope($connection2, "Planner", "makeUnitsPublic" ) ; 
								if ($makeUnitsPublic=="Y") {
									?>
									<tr>
										<td> 
											<b><?php print __($guid, "Shared Publically") ?> * </b><br/>
											<span class="emphasis small"><?php print __($guid, "Share this unit via the public listing of units? Useful for building MOOCS.") ; ?></span>
										</td>
										<td class="right">
											<input type="radio" name="sharedPublic" value="Y" /> <?php print __($guid, 'Yes') ?>
											<input checked type="radio" name="sharedPublic" value="N" /> <?php print __($guid, 'No') ?>
										</td>
									</tr>
									<?php
								}
								?>
								
								<tr class='break'>
									<td colspan=2> 
										<h3><?php print __($guid, 'Outcomes') ?></h3>	
									</td>
								</tr>
								<?php 
								$type="outcome" ; 
								$allowOutcomeEditing=getSettingByScope($connection2, "Planner", "allowOutcomeEditing") ;
								$categories=array() ;
								$categoryCount=0 ;
								?> 
								<style>
									#<?php print $type ?> { list-style-type: none; margin: 0; padding: 0; width: 100%; }
									#<?php print $type ?> div.ui-state-default { margin: 0 0px 5px 0px; padding: 5px; font-size: 100%; min-height: 58px; }
									div.ui-state-default_dud { margin: 5px 0px 5px 0px; padding: 5px; font-size: 100%; min-height: 58px; }
									html>body #<?php print $type ?> li { min-height: 58px; line-height: 1.2em; }
									.<?php print $type ?>-ui-state-highlight { margin-bottom: 5px; min-height: 58px; line-height: 1.2em; width: 100%; }
									.<?php print $type ?>-ui-state-highlight {border: 1px solid #fcd3a1; background: #fbf8ee url(images/ui-bg_glass_55_fbf8ee_1x400.png) 50% 50% repeat-x; color: #444444; }
								</style>
								<script>
									$(function() {
										$( "#<?php print $type ?>" ).sortable({
											placeholder: "<?php print $type ?>-ui-state-highlight";
											axis: 'y'
										});
									});
								</script>
								<tr>
									<td colspan=2> 
										<p><?php print __($guid, 'Link this unit to outcomes (defined in the Manage Outcomes section of the Planner), and track which outcomes are being met in which units, classes and courses.') ?></p>
										<div class="outcome" id="outcome" style='width: 100%; padding: 5px 0px 0px 0px; min-height: 66px'>
											<div id="outcomeOuter0">
												<div style='color: #ddd; font-size: 230%; margin: 15px 0 0 6px'><?php print __($guid, 'Key outcomes listed here...') ?></div>
											</div>
										</div>
										<div style='width: 100%; padding: 0px 0px 0px 0px;'>
											<div class="ui-state-default_dud" style='padding: 0px; min-height: 66px'>
												<table class='blank' cellspacing='0' style='width: 100%'>
													<tr>
														<td style='width: 50%'>
															<script type="text/javascript">
																var outcomeCount=1 ;
																/* Unit type control */
																$(document).ready(function(){
																	$("#new").click(function(){
																		
																	 });
																});
															</script>
															<select id='newOutcome' onChange='outcomeDisplayElements(this.value);' style='float: none; margin-left: 3px; margin-top: 0px; margin-bottom: 3px; width: 350px'>
																<option class='all' value='0'><?php print __($guid, 'Choose an outcome to add it to this unit') ?></option>
																<?php
																$currentCategory="" ;
																$lastCategory="" ;
																$switchContents="" ;
																try {
																	$countClause=0 ;
																	$years=explode(",", $gibbonYearGroupIDList) ;
																	$dataSelect=array();  
																	$sqlSelect="" ;
																	foreach ($years as $year) {
																		$dataSelect["clause" . $countClause]="%" . $year . "%" ;
																		$sqlSelect.="(SELECT * FROM gibbonOutcome WHERE active='Y' AND scope='School' AND gibbonYearGroupIDList LIKE :clause" . $countClause . ") UNION " ;
																		$countClause++ ;
																	}
																	$resultSelect=$connection2->prepare(substr($sqlSelect,0,-6) . "ORDER BY category, name");
																	$resultSelect->execute($dataSelect);
																}
																catch(PDOException $e) { 
																	print "<div class='error'>" . $e->getMessage() . "</div>" ; 
																}
																print "<optgroup label='--" . __($guid, 'SCHOOL OUTCOMES') . "--'>" ;
																while ($rowSelect=$resultSelect->fetch()) {
																	$currentCategory=$rowSelect["category"] ;
																	if (($currentCategory!=$lastCategory) AND $currentCategory!="") {
																		print "<optgroup label='--" . $currentCategory . "--'>" ;
																		print "<option class='$currentCategory' value='0'>" . __($guid, 'Choose an outcome to add it to this unit') . "</option>" ;
																		$categories[$categoryCount]=$currentCategory ;
																		$categoryCount++ ;
																	}
																	print "<option class='all " . $rowSelect["category"] . "'   value='" . $rowSelect["gibbonOutcomeID"] . "'>" . $rowSelect["name"] . "</option>" ;
																	$switchContents.="case \"" . $rowSelect["gibbonOutcomeID"] . "\": " ;
																	$switchContents.="$(\"#outcome\").append('<div id=\'outcomeOuter' + outcomeCount + '\'><img style=\'margin: 10px 0 5px 0\' src=\'" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/loading.gif\' alt=\'Loading\' onclick=\'return false;\' /><br/>Loading</div>');" ;
																	$switchContents.="$(\"#outcomeOuter\" + outcomeCount).load(\"" . $_SESSION[$guid]["absoluteURL"] . "/modules/Planner/units_add_blockOutcomeAjax.php\",\"type=outcome&id=\" + outcomeCount + \"&title=" . urlencode($rowSelect["name"]) . "\&category=" . urlencode($rowSelect["category"]) . "&gibbonOutcomeID=" . $rowSelect["gibbonOutcomeID"] . "&contents=" . urlencode($rowSelect["description"]) . "&allowOutcomeEditing=" . urlencode($allowOutcomeEditing) . "\") ;" ;
																	$switchContents.="outcomeCount++ ;" ;
																	$switchContents.="$('#newOutcome').val('0');" ;
																	$switchContents.="break;" ;
																	$lastCategory=$rowSelect["category"] ;
																}
																
																if ($rowCourse["gibbonDepartmentID"]!="") {
																	$currentCategory="" ;
																	$lastCategory="" ;
																	$currentLA="" ;
																	$lastLA="" ;
																	try {
																		$countClause=0 ;
																		$years=explode(",", $gibbonYearGroupIDList) ;
																		$dataSelect=array("gibbonDepartmentID"=>$rowCourse["gibbonDepartmentID"]); 
																		$sqlSelect="" ;
																		foreach ($years as $year) {
																			$dataSelect["clause" . $countClause]="%" . $year . "%" ;
																			$sqlSelect.="(SELECT gibbonOutcome.*, gibbonDepartment.name AS learningArea FROM gibbonOutcome JOIN gibbonDepartment ON (gibbonOutcome.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) WHERE active='Y' AND scope='Learning Area' AND gibbonDepartment.gibbonDepartmentID=:gibbonDepartmentID AND gibbonYearGroupIDList LIKE :clause" . $countClause . ") UNION " ;
																			$countClause++ ;
																		}
																		$resultSelect=$connection2->prepare(substr($sqlSelect,0,-6) . "ORDER BY learningArea, category, name");
																		$resultSelect->execute($dataSelect);
																	}
																	catch(PDOException $e) { 
																		print "<div class='error'>" . $e->getMessage() . "</div>" ; 
																	}
																	while ($rowSelect=$resultSelect->fetch()) {
																		$currentCategory=$rowSelect["category"] ;
																		$currentLA=$rowSelect["learningArea"] ;
																		if (($currentLA!=$lastLA) AND $currentLA!="") {
																			print "<optgroup label='--" . strToUpper($currentLA) . " " . __($guid, 'OUTCOMES') . "--'>" ;
																		}
																		if (($currentCategory!=$lastCategory) AND $currentCategory!="") {
																			print "<optgroup label='--" . $currentCategory . "--'>" ;
																			print "<option class='$currentCategory' value='0'>Choose an outcome to add it to this unit</option>" ;
																			$categories[$categoryCount]=$currentCategory ;
																			$categoryCount++ ;
																		}
																		print "<option class='all " . $rowSelect["category"] . "'   value='" . $rowSelect["gibbonOutcomeID"] . "'>" . $rowSelect["name"] . "</option>" ;
																		$switchContents.="case \"" . $rowSelect["gibbonOutcomeID"] . "\": " ;
																		$switchContents.="$(\"#outcome\").append('<div id=\'outcomeOuter' + outcomeCount + '\'><img style=\'margin: 10px 0 5px 0\' src=\'" . $_SESSION[$guid]["absoluteURL"] . "/themes/Default/img/loading.gif\' alt=\'Loading\' onclick=\'return false;\' /><br/>Loading</div>');" ;
																		$switchContents.="$(\"#outcomeOuter\" + outcomeCount).load(\"" . $_SESSION[$guid]["absoluteURL"] . "/modules/Planner/units_add_blockOutcomeAjax.php\",\"type=outcome&id=\" + outcomeCount + \"&title=" . urlencode($rowSelect["name"]) . "\&category=" . urlencode($rowSelect["category"]) . "&gibbonOutcomeID=" . $rowSelect["gibbonOutcomeID"] . "&contents=" . urlencode($rowSelect["description"]) . "&allowOutcomeEditing=" . urlencode($allowOutcomeEditing) . "\") ;" ;
																		$switchContents.="outcomeCount++ ;" ;
																		$switchContents.="$('#newOutcome').val('0');" ;
																		$switchContents.="break;" ;
																		$lastCategory=$rowSelect["category"] ;
																		$lastLA=$rowSelect["learningArea"] ;
																	}
																}
																?>
															</select><br/>
															<?php
															if (count($categories)>0) {
																?>
																<select id='outcomeFilter' style='float: none; margin-left: 3px; margin-top: 0px; width: 350px'>
																	<option value='all'><?php print __($guid, 'View All') ?></option>
																	<?php
																	$categories=array_unique($categories) ;
																	$categories=msort($categories) ;
																	foreach ($categories AS $category) {
																		print "<option value='$category'>$category</option>" ;
																	}
																	?>
																</select>
																<script type="text/javascript">
																	$("#newOutcome").chainedTo("#outcomeFilter");
																</script>
																<?php
															}
															?>
															<script type='text/javascript'>
																var <?php print $type ?>Used=new Array();
																var <?php print $type ?>UsedCount=0 ;
																	
																function outcomeDisplayElements(number) {
																	$("#<?php print $type ?>Outer0").css("display", "none") ;
																	if (<?php print $type ?>Used.indexOf(number)<0) {
																		<?php print $type ?>Used[<?php print $type ?>UsedCount]=number ;
																		<?php print $type ?>UsedCount++ ;
																		switch(number) {
																			<?php print $switchContents ?>
																		}
																	}
																	else {
																		alert("<?php print __($guid, 'This element has already been selected!') ?>") ;
																		$('#newOutcome').val('0');
																	}
																}
															</script>
														</td>
													</tr>
												</table>
											</div>
										</div>
									</td>
								</tr>
								
								<tr class='break' id="datesHeaderRow">
									<td colspan=2> 
										<h3><?php print __($guid, 'Classes') ?></h3>
									</td>
								</tr>
								<tr id="datesRow">
									<td colspan=2> 
										<p><?php print __($guid, 'Select classes which will have access to this unit.') ?></p>
										<?php
										$classCount=0 ;
										try {
											$dataClass=array(); 
											$sqlClass="SELECT * FROM gibbonCourseClass WHERE gibbonCourseID=$gibbonCourseID ORDER BY name" ;
											$resultClass=$connection2->prepare($sqlClass);
											$resultClass->execute($dataClass);
										}
										catch(PDOException $e) { 
											print "<div class='error'>" . $e->getMessage() . "</div>" ; 
										}
										
										if ($resultClass->rowCount()<1) {
											print "<div class='error'>" ;
											print __($guid, "There are no records to display.") ;
											print "</div>" ;
										}
										else {
											print "<table cellspacing='0' style='width: 100%'>" ;
												print "<tr class='head'>" ;
													print "<th>" ;
														print __($guid, "Class") ;
													print "</th>" ;
													print "<th>" ;
														print __($guid, "Running") . "<br/><span style='font-size: 80%'>" . __($guid, 'Is class studying this unit?') . "</span>" ;
													print "</th>" ;
												print "</tr>" ;
												
												$count=0;
												$rowNum="odd" ;
												while ($rowClass=$resultClass->fetch()) {
													if ($count%2==0) {
														$rowNum="even" ;
													}
													else {
														$rowNum="odd" ;
													}
													$count++ ;
													
													//COLOR ROW BY STATUS!
													print "<tr class=$rowNum>" ;
														print "<td>" ;
															print $rowCourse["nameShort"] . "." . $rowClass["name"] . "</a>" ;
														print "</td>" ;
														print "<td>" ;
															?>
															<input name="gibbonCourseClassID<?php print $classCount?>" id="gibbonCourseClassID<?php print $classCount?>" maxlength=10 value="<?php print $rowClass["gibbonCourseClassID"] ?>" type="hidden" class="standardWidth">
															<select name="running<?php print $classCount?>" id="running<?php print $classCount?>" style="width:100%">
																<option value="N"><?php print __($guid, 'No') ?></option>
																<option value="Y"><?php print __($guid, 'Yes') ?></option>
															</select>
															<?php
														print "</td>" ;
													print "</tr>" ;
													$classCount++ ;
												}
											print "</table>" ;
										}
										?>
									</td>
								</tr>
								
								<tr class='break'>
									<td colspan=2> 
										<h3><?php print __($guid, 'Unit Outline') ?></h3>
									</td>
								</tr>
								<tr>
									<td colspan=2> 
										<?php $unitOutline=getSettingByScope($connection2, "Planner", "unitOutlineTemplate" ) ?>
										<p><?php print __($guid, 'The contents of this field are viewable only to those with full access to the Planner (usually teachers and administrators, but not students and parents), whereas the downloadable version (below) is available to more users (usually parents).') ?></p>
										<?php print getEditor($guid,  TRUE, "details", $unitOutline, 40, true, false, false) ?>
									</td>
								</tr>
								<tr>
									<td> 
										<b><?php print __($guid, 'Downloadable Unit Outline') ?></b><br/>
										<span class="emphasis small"><?php print __($guid, 'Available to most users.') ?></span>
									</td>
									<td class="right">
										<input type="file" name="file" id="file"><br/><br/>
										<?php
										print getMaxUpload($guid) ;
										
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
										<h3><?php print __($guid, 'Smart Blocks') ?></h3>
									</td>
								</tr>
								<tr>
									<td colspan=2> 
										<p>
											<?php print __($guid, 'Smart Blocks aid unit planning by giving teachers help in creating and maintaining new units, splitting material into smaller units which can be deployed to lesson plans. As well as predefined fields to fill, Smart Units provide a visual view of the content blocks that make up a unit. Blocks may be any kind of content, such as discussion, assessments, group work, outcome etc.') ?>
										</p>
										<style>
											#sortable { list-style-type: none; margin: 0; padding: 0; width: 100%; }
											#sortable div.ui-state-default { margin: 0 0px 5px 0px; padding: 5px; font-size: 100%; min-height: 58px; }
											div.ui-state-default_dud { margin: 5px 0px 5px 0px; padding: 5px; font-size: 100%; min-height: 58px; }
											html>body #sortable li { min-height: 58px; line-height: 1.2em; }
											#sortable .ui-state-highlight { margin-bottom: 5px; min-height: 58px; line-height: 1.2em; width: 100%; }
										</style>
										<script>
											$(function() {
												$( "#sortable" ).sortable({
													placeholder: "ui-state-highlight",
													axis: 'y'
												});
											});
										</script>
										
										<div class="sortable" id="sortable" style='width: 100%; padding: 5px 0px 0px 0px'>
											<?php 
											for ($i=1; $i<=5; $i++) {
												makeBlock($guid, $connection2, $i) ;
											}
											?>
										</div>
										
										<div style='width: 100%; padding: 0px 0px 0px 0px'>
											<div class="ui-state-default_dud" style='padding: 0px; height: 40px'>
												<table class='blank' cellspacing='0' style='width: 100%'>
													<tr>
														<td style='width: 50%'>
															<script type="text/javascript">
																var count=6 ;
																/* Unit type control */
																$(document).ready(function(){
																	$("#new").click(function(){
																		$("#sortable").append('<div id=\'blockOuter' + count + '\'  class=\'blockOuter\'><img style=\'margin: 10px 0 5px 0\' src=\'<?php print $_SESSION[$guid]["absoluteURL"] ?>/themes/Default/img/loading.gif\' alt=\'Loading\' onclick=\'return false;\' /><br/>Loading</div>');
																		$("#blockOuter" + count).load("<?php print $_SESSION[$guid]["absoluteURL"] ?>/modules/Planner/units_add_blockAjax.php","id=" + count) ;
																		count++ ;
																	 });
																});
															</script>
															<div id='new' style='cursor: default; float: none; border: 1px dotted #aaa; background: none; margin-left: 3px; color: #999; margin-top: 0px; font-size: 140%; font-weight: bold; width: 350px'><?php print __($guid, 'Click to create a new block') ?></div><br/>
														</td>
													</tr>
												</table>
											</div>
										</div>
									</td>
								</tr>
								
								<tr>
									<td class="right" colspan=2>
										<script type="text/javascript">
											$(document).ready(function(){
												$("#submit").click(function(){
													$("#blockCount").val(count) ;
												 });
											});
										</script>
										<input name="blockCount" id=blockCount value="5" type="hidden">
										<input name="classCount" id="classCount" value="<?php print $classCount ?>" type="hidden">
										<input type="submit" id="submit" value="Submit">
									</td>
								</tr>
								<tr>
									<td class="right" colspan=2>
										<span class="emphasis small">* <?php print __($guid, "denotes a required field") ; ?></span>
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
	//Print sidebar
	$_SESSION[$guid]["sidebarExtra"]=sidebarExtraUnits($guid, $connection2, $gibbonCourseID, $gibbonSchoolYearID) ;
}
?>