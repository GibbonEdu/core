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

if (isActionAccessible($guid, $connection2, "/modules/Planner/units_edit.php")==FALSE) {
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
		//IF UNIT DOES NOT CONTAIN HYPHEN, IT IS A GIBBON UNIT
		$gibbonUnitID=$_GET["gibbonUnitID"]; 
		if (strpos($gibbonUnitID,"-")==FALSE) {
			$hooked=FALSE ;
		}
		else {
			$hooked=TRUE ;
			$gibbonHookIDToken=substr($gibbonUnitID,11) ;
			$gibbonUnitIDToken=substr($gibbonUnitID,0,10) ;
		}
		
		//Proceed!
		print "<div class='trail'>" ;
		print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . _(getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/units.php&gibbonSchoolYearID=" . $_GET["gibbonSchoolYearID"] . "&gibbonCourseID=" . $_GET["gibbonCourseID"] . "'>" . _('Manage Units') . "</a> > </div><div class='trailEnd'>" . _('Edit Unit') . "</div>" ;
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
			else if ($updateReturn=="fail6") {
				$updateReturnMessage=_("Your request was successful, but some data was not properly saved.") ;
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
				$addReturnMessage=_("Your Smart Unit was successfully created: you can now edit and deploy it using the form below.") ;	
				$class="success" ;
			}
			print "<div class='$class'>" ;
				print $addReturnMessage;
			print "</div>" ;
		} 
		
		if (isset($_GET["deployReturn"])) { $deployReturn=$_GET["deployReturn"] ; } else { $deployReturn="" ; }
		$deployReturnMessage="" ;
		$class="error" ;
		if (!($deployReturn=="")) {
			if ($deployReturn=="fail0") {
				$deployReturnMessage=_("Your request failed because you do not have access to this action.") ;	
			}
			else if ($deployReturn=="fail2") {
				$deployReturnMessage=_("Your request failed due to a database error.") ;	
			}
			else if ($deployReturn=="fail3") {
				$deployReturnMessage=_("Your request failed because your inputs were invalid.") ;	
			}
			else if ($deployReturn=="fail4") {
				$deployReturnMessage=_("The selected record does not exist, or you do not have access to it.") ;	
			}
			else if ($deployReturn=="fail6") {
				$deployReturnMessage=_("Your request was successful, but some data was not properly saved.") ;
				$class="success" ;	
			}
			else if ($deployReturn=="success0") {
				$deployReturnMessage=_("Your request was completed successfully.") ;	
				$class="success" ;
			}
			print "<div class='$class'>" ;
				print $deployReturnMessage;
			print "</div>" ;
		} 
		
		if (isset($_GET["copyReturn"])) { $copyReturn=$_GET["copyReturn"] ; } else { $copyReturn="" ; }
		$copyReturnMessage="" ;
		$class="error" ;
		if (!($copyReturn=="")) {
			if ($copyReturn=="success0") {
				$copyReturnMessage=_("Copy was successful. The blocks from the selected working unit have replaced those in the master unit (see below for the new block listing).") ;	
				$class="success" ;
			}
			print "<div class='$class'>" ;
				print $copyReturnMessage;
			print "</div>" ;
		} 
		
		if (isset($_GET["copyForwardReturn"])) { $copyForwardReturn=$_GET["copyForwardReturn"] ; } else { $copyForwardReturn="" ; }
		$copyForwardReturnMessage="" ;
		$class="error" ;
		if (!($copyForwardReturn=="")) {
			if ($copyForwardReturn=="success0") {
				$copyForwardReturnMessage=_("Your request was completed successfully.") ;	
				$class="success" ;
			}
			print "<div class='$class'>" ;
				print $copyForwardReturnMessage;
			print "</div>" ;
		} 
									
		//Check if courseschool year specified
		$gibbonSchoolYearID=$_GET["gibbonSchoolYearID"];
		$gibbonCourseID=$_GET["gibbonCourseID"]; 
		if ($gibbonCourseID=="" OR $gibbonSchoolYearID=="") {
			print "<div class='error'>" ;
				print _("You have not specified one or more required parameters.") ;
			print "</div>" ;
		}
		else {
			//IF UNIT DOES NOT CONTAIN HYPHEN, IT IS A GIBBON UNIT
			if (strpos($gibbonUnitID,"-")==FALSE) {
				try {
					if ($highestAction=="Manage Units_all") {
						$data=array("gibbonSchoolYearID"=>$gibbonSchoolYearID, "gibbonCourseID"=>$gibbonCourseID); 
						$sql="SELECT * FROM gibbonCourse WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseID=:gibbonCourseID" ;
					}
					else if ($highestAction=="Manage Units_learningAreas") {
						$data=array("gibbonSchoolYearID"=>$gibbonSchoolYearID, "gibbonCourseID"=>$gibbonCourseID, "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
						$sql="SELECT gibbonCourseID, gibbonCourse.name, gibbonCourse.nameShort, gibbonYearGroupIDList FROM gibbonCourse JOIN gibbonDepartment ON (gibbonCourse.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) JOIN gibbonDepartmentStaff ON (gibbonDepartmentStaff.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) WHERE gibbonDepartmentStaff.gibbonPersonID=:gibbonPersonID AND (role='Coordinator' OR role='Assistant Coordinator' OR role='Teacher (Curriculum)') AND gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseID=:gibbonCourseID ORDER BY gibbonCourse.nameShort" ;
					}
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
					$yearName=$row["name"] ;
					$gibbonYearGroupIDList=$row["gibbonYearGroupIDList"] ;
				
					//Check if unit specified
					if ($gibbonUnitID=="") {
						print "<div class='error'>" ;
							print _("You have not specified one or more required parameters.") ;
						print "</div>" ;
					}
					else {
						try {
							$data=array("gibbonUnitID"=>$gibbonUnitID, "gibbonCourseID"=>$gibbonCourseID); 
							$sql="SELECT gibbonCourse.nameShort AS courseName, gibbonCourse.gibbonDepartmentID, gibbonUnit.* FROM gibbonUnit JOIN gibbonCourse ON (gibbonUnit.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonUnitID=:gibbonUnitID AND gibbonUnit.gibbonCourseID=:gibbonCourseID" ;
							$result=$connection2->prepare($sql);
							$result->execute($data);
						}
						catch(PDOException $e) { 
							print "<div class='error'>" . $e->getMessage() . "</div>" ; 
						}
						if ($result->rowCount()!=1) {
							print "<div class='error'>" ;
								print _("The specified record cannot be found.") ;
							print "</div>" ;
						}
						else {
							//Let's go!
							$row=$result->fetch() ;
							$gibbonDepartmentID=$row["gibbonDepartmentID"] ;
							?>
							<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/units_editProcess.php?gibbonUnitID=$gibbonUnitID&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonCourseID=$gibbonCourseID&address=" . $_GET["q"] ?>" enctype="multipart/form-data">
								<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
									<tr class='break'>
										<td colspan=2> 
											<h3><?php print _('Unit Basics') ?></h3>
										</td>
									</tr>
									<tr>
										<td style='width: 275px'> 
											<b><?php print _('School Year') ?> *</b><br/>
											<span style="font-size: 90%"><i><?php print _('This value cannot be changed.') ?></i></span>
										</td>
										<td class="right">
											<input readonly name="yearName" id="yearName" maxlength=20 value="<?php print $yearName ?>" type="text" style="width: 300px">
										</td>
									</tr>
									<tr>
										<td> 
											<b><?php print _('Course') ?> *</b><br/>
											<span style="font-size: 90%"><i><?php print _('This value cannot be changed.') ?></i></span>
										</td>
										<td class="right">
											<input readonly name="courseName" id="courseName" maxlength=20 value="<?php print $row["courseName"] ?>" type="text" style="width: 300px">
										</td>
									</tr>
									<tr>
										<td> 
											<b><?php print _('Name') ?> *</b><br/>
											<span style="font-size: 90%"><i></i></span>
										</td>
										<td class="right">
											<input name="name" id="name" maxlength=40 value="<?php print $row["name"] ?>" type="text" style="width: 300px">
											<script type="text/javascript">
												var name2=new LiveValidation('name');
												name2.add(Validate.Presence);
											 </script>
										</td>
									</tr>
									<tr>
										<td colspan=2> 
											<b><?php print _('Blurb') ?> *</b> 
											<textarea name='description' id='description' rows=5 style='width: 300px'><?php print $row["description"] ?></textarea>
											<script type="text/javascript">
												var description=new LiveValidation('description');
												description.add(Validate.Presence);
											</script>
										</td>
									</tr>
									
									<tr>
										<td> 
											<b><?php print _("License") ?></b><br/>
											<span style="font-size: 90%"><i><?php print _("Under what conditions can this work be reused?") ; ?></i></span>
										</td>
										<td class="right">
											<select name="license" id="license" style="width: 302px">
												<option <?php if ($row["license"]=="") {print "selected ";} ?>value=""></option>
												<option <?php if ($row["license"]=="Copyright") {print "selected ";} ?>value="Copyright"><?php print _('Copyright') ?></option>
												<option <?php if ($row["license"]=="Creative Commons BY") {print "selected ";} ?>value="Creative Commons BY"><?php print _('Creative Commons BY') ?></option>
												<option <?php if ($row["license"]=="Creative Commons BY-SA") {print "selected ";} ?>value="Creative Commons BY-SA"><?php print _('Creative Commons BY-SA') ?></option>
												<option <?php if ($row["license"]=="Creative Commons BY-SA-NC") {print "selected ";} ?>value="Creative Commons BY-SA-NC"><?php print _('Creative Commons BY-SA-NC') ?></option>
												<option <?php if ($row["license"]=="Public Domain") {print "selected ";} ?>value="Public Domain"><?php print _('Public Domain') ?></option>
											</select>
										</td>
									</tr>
									<?php
									$makeUnitsPublic=getSettingByScope($connection2, "Planner", "makeUnitsPublic" ) ; 
									if ($makeUnitsPublic=="Y") {
										?>
										<tr>
											<td> 
												<b><?php print _("Shared Publically") ?> * </b><br/>
												<span style="font-size: 90%"><i><?php print _("Share this unit via the public listing of units? Useful for building MOOCS.") ; ?></i></span>
											</td>
											<td class="right">
												<input <?php if ($row["sharedPublic"]=="Y") { print "checked" ; } ?> type="radio" name="sharedPublic" value="Y" /> <?php print _('Yes') ?>
												<input <?php if ($row["sharedPublic"]=="N" OR $row["sharedPublic"]=="") { print "checked" ; } ?> type="radio" name="sharedPublic" value="N" /> <?php print _('No') ?>
											</td>
										</tr>
										<?php
									}
									?>
									<tr>
										<td> 
											<b><?php print _('Embeddable') ?> *</b><br/>
											<span style="font-size: 90%"><i><?php print _('Can this unit be embedded and shared publicly in other websites?') ?></i></span>
										</td>
										<td class="right">
											<input <?php if ($row["embeddable"]=="Y") { print "checked" ; } ?> type="radio" id="embeddable" name="embeddable" class="embeddable" value="Y" /> <?php print _('Yes') ?>
											<input <?php if ($row["embeddable"]=="N") { print "checked" ; } ?> type="radio" id="embeddable" name="embeddable" class="embeddable" value="N" /> <?php print _('No') ?>
										</td>
									</tr>
									<script type="text/javascript">
										$(document).ready(function(){
											<?php
											if ($row["embeddable"]=="Y") {
												print "$(\"#embeddableRow\").slideDown(\"fast\", $(\"#embeddableRow\").css(\"display\",\"table-row\"));" ;
											}
											?>
											
											$(".embeddable").click(function(){
												if ($('input[name=embeddable]:checked').val()=="Y" ) {
													$("#embeddableRow").slideDown("fast", $("#embeddableRow").css("display","table-row"));
												} else {
													$("#embeddableRow").css("display","none");
													
												}
											 });
										});
									</script>
									
									<tr id="embeddableRow" <?php if ($row["embeddable"]=="N") { print "style='display: none'" ; } ?>>
										<td> 
											<b><?php print _('Embed Code') ?></b><br/>
											<span style="font-size: 90%"><i><?php print _('Copy and paste this HTML code into the target website.') ?></i></span>
										</td>
										<td class="right">
											<textarea readonly name='embedCode' id='embedCode' rows=5 style='width: 300px'><?php print "<iframe style='border: none; width: 620px; height: 800px; overflow-x: hidden; overflow-y: scroll' src=\"" . $_SESSION[$guid]["absoluteURL"] . "/modules/Planner/units_embed.php?gibbonUnitID=$gibbonUnitID&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonCourseID=$gibbonCourseID&themeName=" . $_SESSION[$guid]["gibbonThemeName"] . "&title=false\"></iframe>" ?></textarea>
										</td>
									</tr>
									
									<tr class='break'>
										<td colspan=2> 
											<h3><?php print _('Outcomes') ?></h3>
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
												placeholder: "<?php print $type ?>-ui-state-highlight",
												axis: 'y'
											});
										});
									</script>
									<tr>
										<td colspan=2> 
											<p><?php print _('Link this unit to outcomes (defined in the Manage Outcomes section of the Planner), and track which outcomes are being met in which units, classes and courses.') ?></p>
											<div class="outcome" id="outcome" style='width: 100%; padding: 5px 0px 0px 0px; min-height: 66px'>
												<?php
												$i=1 ;
												$usedArrayFill="" ;
												try {
													$dataBlocks=array("gibbonUnitID"=>$gibbonUnitID);  
													$sqlBlocks="SELECT gibbonUnitOutcome.*, scope, name, category FROM gibbonUnitOutcome JOIN gibbonOutcome ON (gibbonUnitOutcome.gibbonOutcomeID=gibbonOutcome.gibbonOutcomeID) WHERE gibbonUnitID=:gibbonUnitID AND active='Y' ORDER BY sequenceNumber" ;
													$resultBlocks=$connection2->prepare($sqlBlocks);
													$resultBlocks->execute($dataBlocks);
												}
												catch(PDOException $e) { 
													print "<div class='error'>" . $e->getMessage() . "</div>" ; 
												}
												if ($resultBlocks->rowCount()<1) {
													print "<div id='outcomeOuter0'>" ;
														print "<div style='color: #ddd; font-size: 230%; margin: 15px 0 0 6px'>" . _('Outcomes listed here...') . "</div>" ;
													print "</div>" ;
												}
												else {
													while ($rowBlocks=$resultBlocks->fetch()) {
														makeBlockOutcome($guid, $i, "outcome", $rowBlocks["gibbonOutcomeID"],  $rowBlocks["name"],  $rowBlocks["category"], $rowBlocks["content"],"",TRUE, $allowOutcomeEditing) ;
														$usedArrayFill.="\"" . $rowBlocks["gibbonOutcomeID"] . "\"," ;
														$i++ ;
													}
												}
												?>
											</div>
											<div style='width: 100%; padding: 0px 0px 0px 0px'>
												<div class="ui-state-default_dud" style='padding: 0px; min-height: 50px'>
													<table class='blank' cellspacing='0' style='width: 100%'>
														<tr>
															<td style='width: 50%'>
																<script type="text/javascript">
																	var outcomeCount=<?php print $i ?>;
																</script>
																<select class='all' id='newOutcome' onChange='outcomeDisplayElements(this.value);' style='float: none; margin-left: 3px; margin-top: 0px; margin-bottom: 3px; width: 350px'>
																	<option class='all' value='0'><?php print _('Choose an outcome to add it to this unit') ?></option>
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
																	print "<optgroup label='--" . _('SCHOOL OUTCOMES') . "--'>" ;
																	while ($rowSelect=$resultSelect->fetch()) {
																		$currentCategory=$rowSelect["category"] ;
																		if (($currentCategory!=$lastCategory) AND $currentCategory!="") {
																			print "<optgroup label='--" . $currentCategory . "--'>" ;
																			print "<option class='$currentCategory' value='0'>" . _('Choose an outcome to add it to this unit') . "</option>" ;
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
																	}
																
																	$currentCategory="" ;
																	$lastCategory="" ;
																	$currentLA="" ;
																	$lastLA="" ;
																	try {
																		$countClause=0 ;
																		$years=explode(",", $gibbonYearGroupIDList) ;
																		$dataSelect=array("gibbonDepartmentID"=>$gibbonDepartmentID); 
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
																			print "<optgroup label='--" . strToUpper($currentLA) . " " . _('OUTCOMES') . "--'>" ;
																		}
																		if (($currentCategory!=$lastCategory) AND $currentCategory!="") {
																			print "<optgroup label='--" . $currentCategory . "--'>" ;
																			print "<option class='$currentCategory' value='0'>" . _('Choose an outcome to add it to this unit') . "</option>" ;
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
																
																	?>
																</select><br/>
																<?php
																if (count($categories)>0) {
																	?>
																	<select id='outcomeFilter' style='float: none; margin-left: 3px; margin-top: 0px; width: 350px'>
																		<option value='all'><?php print _('View All') ?></option>
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
																	var <?php print $type ?>Used=new Array(<?php print substr($usedArrayFill,0,-1) ?>);
																	var <?php print $type ?>UsedCount=<?php print $type ?>Used.length ;
																	
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
																			alert("This element has already been selected!") ;
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
								
									<tr class='break'>
										<td colspan=2> 
											<h3><?php print _('Classes') ?></h3>
										</td>
									</tr>
									<?php
									if ($_SESSION[$guid]["gibbonSchoolYearIDCurrent"]==$gibbonSchoolYearID AND $_SESSION[$guid]["gibbonSchoolYearIDCurrent"]==$_SESSION[$guid]["gibbonSchoolYearID"]) {
										?>
										<tr>
											<td colspan=2> 
												<p><?php print _('Select classes which will study this unit.') ?></p>
												<?php
												$classCount=0 ;
												try {
													$dataClass=array("gibbonCourseID"=>$gibbonCourseID); 
													$sqlClass="SELECT * FROM gibbonCourseClass WHERE gibbonCourseID=:gibbonCourseID ORDER BY name" ;
													$resultClass=$connection2->prepare($sqlClass);
													$resultClass->execute($dataClass);
												}
												catch(PDOException $e) { 
													print "<div class='error'>" . $e->getMessage() . "</div>" ; 
												}
										
												if ($resultClass->rowCount()<1) {
													print "<div class='error'>" ;
													print _("There are no records to display.") ;
													print "</div>" ;
												}
												else {
													print "<table cellspacing='0' style='width: 100%'>" ;
														print "<tr class='head'>" ;
															print "<th>" ;
																print _("Class") ;
															print "</th>" ;
															print "<th>" ;
																print _("Running") . "<br/><span style='font-size: 80%'>" . _('Is class studying this unit?') . "</span>" ;
															print "</th>" ;
															print "<th>" ;
																print _("First Lesson") . "<br/><span style='font-size: 80%'>" ; if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; } print "</span>" ;
															print "</th>" ;
															print "<th>" ;
																print _("Actions") ;
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
													
															try {
																$dataClassData=array("gibbonUnitID"=>$gibbonUnitID, "gibbonCourseClassID"=>$rowClass["gibbonCourseClassID"]); 
																$sqlClassData="SELECT * FROM gibbonUnitClass WHERE gibbonUnitID=:gibbonUnitID AND gibbonCourseClassID=:gibbonCourseClassID" ;
																$resultClassData=$connection2->prepare($sqlClassData);
																$resultClassData->execute($dataClassData);
															}
															catch(PDOException $e) { 
																print "<div class='error'>" . $e->getMessage() . "</div>" ; 
															}
															$rowClassData=NULL ;
															if ($resultClassData->rowCount()==1) {
																$rowClassData=$resultClassData->fetch() ;
															}
													
															//COLOR ROW BY STATUS!
															print "<tr class=$rowNum>" ;
																print "<td>" ;
																	print $row["courseName"] . "." . $rowClass["name"] . "</a>" ;
																print "</td>" ;
																print "<td>" ;
																	?>
																	<input name="gibbonCourseClassID<?php print $classCount?>" id="gibbonCourseClassID<?php print $classCount?>" maxlength=10 value="<?php print $rowClass["gibbonCourseClassID"] ?>" type="hidden" style="width: 300px">
																	<select name="running<?php print $classCount?>" id="running<?php print $classCount?>" style="width:100%">
																		<option <?php if ($rowClassData["running"]=="N") { print "selected ";} ?>value="N"><?php print _('No') ?></option>
																		<option <?php if ($rowClassData["running"]=="Y") { print "selected ";} ?>value="Y"><?php print _('Yes') ?></option>
																	</select>
																	<?php
																print "</td>" ;
																print "<td>" ;
																	try {
																		$dataDate=array("gibbonCourseClassID"=>$rowClass["gibbonCourseClassID"], "gibbonUnitID"=>$gibbonUnitID); 
																		$sqlDate="SELECT date FROM gibbonPlannerEntry WHERE gibbonCourseClassID=:gibbonCourseClassID AND gibbonUnitID=:gibbonUnitID ORDER BY date, timeStart" ;
																		$resultDate=$connection2->prepare($sqlDate);
																		$resultDate->execute($dataDate);
																	}
																	catch(PDOException $e) { }
																	if ($resultDate->rowCount()<1) {
																		print "<i>" . _('There are no records to display.') . "</i>" ;
																	}
																	else {
																		$rowDate=$resultDate->fetch() ;
																		print dateConvertBack($guid, $rowDate["date"]) ;
																	}
																print "</td>" ;
																print "<td>" ;
																	if ($rowClassData["running"]=="Y") {
																		if ($resultDate->rowCount()<1) {
																			print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/units_edit_deploy.php&gibbonCourseClassID=" . $rowClass["gibbonCourseClassID"] . "&gibbonCourseID=$gibbonCourseID&gibbonUnitID=$gibbonUnitID&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonUnitClassID=" . $rowClassData["gibbonUnitClassID"] . "'><img title='Edit Unit' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/config.png'/></a> " ;
																		}
																		else {
																			print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/units_edit_working.php&gibbonCourseClassID=" . $rowClass["gibbonCourseClassID"] . "&gibbonCourseID=$gibbonCourseID&gibbonUnitID=$gibbonUnitID&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonUnitClassID=" . $rowClassData["gibbonUnitClassID"] . "'><img title='Edit Unit' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/config.png'/></a> " ;
																		}
																		print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/planner.php&gibbonCourseClassID=" . $rowClass["gibbonCourseClassID"] . "&viewBy=class'><img style='margin-top: 3px' title='" . _('View Planner') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/planner.png'/></a> " ;
																		print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/units_edit_copyBack.php&gibbonCourseClassID=" . $rowClass["gibbonCourseClassID"] . "&gibbonCourseID=$gibbonCourseID&gibbonUnitID=$gibbonUnitID&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonUnitClassID=" . $rowClassData["gibbonUnitClassID"] . "'><img title='Copy Back' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/copyback.png'/></a> " ;
																		print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/units_edit_copyForward.php&gibbonCourseClassID=" . $rowClass["gibbonCourseClassID"] . "&gibbonCourseID=$gibbonCourseID&gibbonUnitID=$gibbonUnitID&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonUnitClassID=" . $rowClassData["gibbonUnitClassID"] . "'><img title='Copy Forward' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/copyforward.png'/></a> " ;
																	}
																print "</td>" ;
															print "</tr>" ;
															$classCount++ ;
														}
													print "</table>" ;
												}
												?>
											</td>
										</tr>
										<?php
									}
									else {
										print "<tr>" ;
											print "<td colspan=2 style='margin-top: 0; padding-top: 0'>" ;
												print "<div class='warning'>" ;
													print _("You are currently not logged into the current year and/or are looking at units in another year, and so you cannot access your classes. Please log back into the current school year, and look at units in the current year.") ;
												print "</div>" ;
											print "</td>" ;
										print "</tr>" ;
									}
									?>
									
									
									<tr class='break'>
										<td colspan=2> 
											<h3><?php print _('Unit Outline') ?></h3>
										</td>
									</tr>
									<tr>
										<td colspan=2>
											<p><?php print _('The contents of this field are viewable only to those with full access to the Planner (usually teachers and administrators, but not students and parents), whereas the downloadable version (below) is available to more users (usually parents).') ?></p>
											<?php print getEditor($guid,  TRUE, "details", $row["details"], 40, true, false, false) ?>
										</td>
									</tr>
									<tr>
										<td> 
											<b><?php print _('Downloadable Unit Outline') ?></b><br/>
											<span style="font-size: 90%"><i><?php print _('Available to most users.') ?></i></span>
											<?php if ($row["attachment"]!="") { ?>
												<span style="font-size: 90%"><i><?php print _('Will overwrite existing attachment.') ?></i></span>
											<?php } ?>
										</td>
										<td class="right">
											<?php
											if ($row["attachment"]!="") {
												print _("Current attachment:") . " <a href='" . $_SESSION[$guid]["absoluteURL"] . "/" . $row["attachment"] . "'>" . $row["attachment"] . "</a><br/><br/>" ;
											}
											?>
											<input type="file" name="file" id="file"><br/><br/>
											<?php
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
												file.add( Validate.Inclusion, { within: [<?php print $ext ;?>], failureMessage: "Illegal file type!", partialMatch: true, caseSensitive: false } );
											</script>
										</td>
									</tr>
							
									<tr class='break'>
										<td colspan=2>
											<h3><?php print _('Smart Blocks') ?></h3>
										</td>
									</tr>
									<tr>
										<td colspan=2>
											<p>
												<?php print _('Smart Blocks aid unit planning by giving teachers help in creating and maintaining new units, splitting material into smaller units which can be deployed to lesson plans. As well as predefined fields to fill, Smart Units provide a visual view of the content blocks that make up a unit. Blocks may be any kind of content, such as discussion, assessments, group work, outcome etc.') ?>
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
												try {
													$dataBlocks=array("gibbonUnitID"=>$gibbonUnitID); 
													$sqlBlocks="SELECT * FROM gibbonUnitBlock WHERE gibbonUnitID=:gibbonUnitID ORDER BY sequenceNumber" ;
													$resultBlocks=$connection2->prepare($sqlBlocks);
													$resultBlocks->execute($dataBlocks);
												}
												catch(PDOException $e) { 
													print "<div class='error'>" . $e->getMessage() . "</div>" ; 
												}
												try {
													$dataOutcomes=array("gibbonUnitID"=>$gibbonUnitID); 
													$sqlOutcomes="SELECT gibbonOutcome.gibbonOutcomeID, gibbonOutcome.name, gibbonOutcome.category, scope, gibbonDepartment.name AS department FROM gibbonUnitOutcome JOIN gibbonOutcome ON (gibbonUnitOutcome.gibbonOutcomeID=gibbonOutcome.gibbonOutcomeID) LEFT JOIN gibbonDepartment ON (gibbonOutcome.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) WHERE gibbonUnitID=:gibbonUnitID AND active='Y' ORDER BY sequenceNumber" ;
													$resultOutcomes=$connection2->prepare($sqlOutcomes);
													$resultOutcomes->execute($dataOutcomes);
												}
												catch(PDOException $e) { 
													print "<div class='error'>" . $e->getMessage() . "</div>" ; 
												}
												$unitOutcomes=$resultOutcomes->fetchall() ;
												$i=1 ;
												while ($rowBlocks=$resultBlocks->fetch()) {
													makeBlock($guid, $connection2, $i, "masterEdit", $rowBlocks["title"], $rowBlocks["type"], $rowBlocks["length"], $rowBlocks["contents"], "N", $rowBlocks["gibbonUnitBlockID"], "", $rowBlocks["teachersNotes"], TRUE, $unitOutcomes, $rowBlocks["gibbonOutcomeIDList"]) ;
													$i++ ;
												}
												?>
											</div>
											<div style='width: 100%; padding: 0px 0px 0px 0px'>
												<div class="ui-state-default_dud" style='padding: 0px; height: 40px'>
													<table class='blank' cellspacing='0' style='width: 100%'>
														<tr>
															<td style='width: 50%'>
																<script type="text/javascript">
																	var count=<?php print ($resultBlocks->rowCount()+1) ?> ;
																	$(document).ready(function(){
																		$("#new").click(function(){
																			$("#sortable").append('<div id=\'blockOuter' + count + '\'><img style=\'margin: 10px 0 5px 0\' src=\'<?php print $_SESSION[$guid]["absoluteURL"] ?>/themes/Default/img/loading.gif\' alt=\'Loading\' onclick=\'return false;\' /><br/>Loading</div>');
																			$("#blockOuter" + count).load("<?php print $_SESSION[$guid]["absoluteURL"] ?>/modules/Planner/units_add_blockAjax.php","id=" + count + "&mode=masterEdit") ;
																			count++ ;
																		 });
																	});
																</script>
																<div id='new' style='cursor: default; float: none; border: 1px dotted #aaa; background: none; margin-left: 3px; color: #999; margin-top: 0px; font-size: 140%; font-weight: bold; width: 350px'><?php print _('Click to create a new block') ?></div><br/>
															</td>
														</tr>
													</table>
												</div>
											</div>
										</td>
									</tr>
								
									<tr>
										<td>
											<span style="font-size: 90%"><i>* <?php print _("denotes a required field") ; ?></i></span>
										</td>
										<td class="right">
											<input name="classCount" id="classCount" value="<?php print $classCount ?>" type="hidden">
											<input id="submit" type="submit" value="Submit">
										</td>
									</tr>
								</table>
							</form>
							<?php
						}
					}
				}
			}
			//IF UNIT DOES CONTAIN HYPHEN, IT IS A HOOKED UNIT
			else {
				try {
					if ($highestAction=="Manage Units_all") {
						$data=array("gibbonSchoolYearID"=>$gibbonSchoolYearID, "gibbonCourseID"=>$gibbonCourseID); 
						$sql="SELECT * FROM gibbonCourse WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseID=:gibbonCourseID" ;
					}
					else if ($highestAction=="Manage Units_learningAreas") {
						$data=array("gibbonSchoolYearID"=>$gibbonSchoolYearID, "gibbonCourseID"=>$gibbonCourseID, "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
						$sql="SELECT gibbonCourseID, gibbonCourse.name, gibbonCourse.nameShort FROM gibbonCourse JOIN gibbonDepartment ON (gibbonCourse.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) JOIN gibbonDepartmentStaff ON (gibbonDepartmentStaff.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) WHERE gibbonDepartmentStaff.gibbonPersonID=:gibbonPersonID AND (role='Coordinator' OR role='Assistant Coordinator' OR role='Teacher (Curriculum)') AND gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseID=:gibbonCourseID ORDER BY gibbonCourse.nameShort" ;
					}
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
					$yearName=$row["name"] ;
					$gibbonYearGroupIDList=$row["gibbonYearGroupIDList"] ;
				
					//Check if unit specified
					if ($gibbonHookIDToken=="") {
						print "<div class='error'>" ;
							print _("You have not specified one or more required parameters.") ;
						print "</div>" ;
					}
					else {
						try {
							$dataHooks=array("gibbonHookID"=>$gibbonHookIDToken); 
							$sqlHooks="SELECT * FROM gibbonHook WHERE type='Unit' AND gibbonHookID=:gibbonHookID ORDER BY name" ;
							$resultHooks=$connection2->prepare($sqlHooks);
							$resultHooks->execute($dataHooks);
						}
						catch(PDOException $e) { }
						if ($resultHooks->rowCount()==1) {
							$rowHooks=$resultHooks->fetch() ;
							$hookOptions=unserialize($rowHooks["options"]) ;
							if ($hookOptions["unitTable"]!="" AND $hookOptions["unitIDField"]!="" AND $hookOptions["unitCourseIDField"]!="" AND $hookOptions["unitNameField"]!="" AND $hookOptions["unitDescriptionField"]!="" AND $hookOptions["classLinkTable"]!="" AND $hookOptions["classLinkJoinFieldUnit"]!="" AND $hookOptions["classLinkJoinFieldClass"]!="" AND $hookOptions["classLinkIDField"]!="") {
								try {
									$data=array("unitIDField"=>$gibbonUnitIDToken); 
									$sql="SELECT " . $hookOptions["unitTable"] . ".*, gibbonCourse.nameShort FROM " . $hookOptions["unitTable"] . " JOIN gibbonCourse ON (" . $hookOptions["unitTable"] . "." . $hookOptions["unitCourseIDField"] . "=gibbonCourse.gibbonCourseID) WHERE " . $hookOptions["unitIDField"] . "=:unitIDField" ;
									$result=$connection2->prepare($sql);
									$result->execute($data);
								}
								catch(PDOException $e) { }									
							}
						}
						if ($result->rowCount()!=1) {
							print "<div class='error'>" ;
								print _("The specified record cannot be found.") ;
							print "</div>" ;
						}
						else {
							//Let's go!
							$row=$result->fetch() ;
							?>
							<table cellspacing='0' style="width: 100%">	
								<tr><td style="width: 30%"></td><td></td></tr>
								<tr>
									<td colspan=2> 
										<h3>Unit Basics</h3>
									</td>
								</tr>
								<tr>
									<td> 
										<b><?php print _('School Year') ?> *</b><br/>
										<span style="font-size: 90%"><i><?php print _('This value cannot be changed.') ?></i></span>
									</td>
									<td class="right">
										<input readonly name="yearName" id="yearName" maxlength=20 value="<?php print $yearName ?>" type="text" style="width: 300px">
									</td>
								</tr>
								<tr>
									<td> 
										<b><?php print _('Course') ?> *</b><br/>
										<span style="font-size: 90%"><i><?php print _('This value cannot be changed.') ?></i></span>
									</td>
									<td class="right">
										<input readonly name="courseName" id="courseName" maxlength=20 value="<?php print $row["nameShort"] ?>" type="text" style="width: 300px">
									</td>
								</tr>
								<tr>
									<td> 
										<b><?php print _('Name') ?> *</b><br/>
										<span style="font-size: 90%"><i><?php print _('This value cannot be changed.') ?></i></span>
									</td>
									<td class="right">
										<input readonly name="name" id="name" maxlength=40 value="<?php print $row["name"] ?>" type="text" style="width: 300px">
									</td>
								</tr>
							
								<tr>
									<td colspan=2> 
										<h3><?php print _('Classes') ?></h3>
										<p><?php print _('Select classes which will study this unit.') ?></p>
									</td>
								</tr>
								<tr>
									<td colspan=2> 
										<?php
										$classCount=0 ;
										try {
											$dataClass=array("unitIDField"=>$row[$hookOptions["unitIDField"]], "gibbonCourseID"=>$row[$hookOptions["unitCourseIDField"]]); 
											$sqlClass="SELECT gibbonCourseClass.nameShort AS className, gibbonCourse.nameShort AS courseName, " . $hookOptions["classLinkTable"] . ".* FROM " . $hookOptions["classLinkTable"] . " JOIN " . $hookOptions["unitTable"] . " ON (" . $hookOptions["classLinkTable"] . "." . $hookOptions["unitIDField"] . "=" . $hookOptions["unitTable"] . "." . $hookOptions["unitIDField"] . ") JOIN gibbonCourseClass ON (" . $hookOptions["classLinkTable"] . "." . $hookOptions["classLinkJoinFieldClass"] . "=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE " . $hookOptions["classLinkTable"] . "." . $hookOptions["unitIDField"] . "=:unitIDField AND " . $hookOptions["unitTable"] . "." . $hookOptions["unitCourseIDField"] . "=:gibbonCourseID ORDER BY courseName, className" ;
											$resultClass=$connection2->prepare($sqlClass);
											$resultClass->execute($dataClass);
										}
										catch(PDOException $e) { 
											print "<div class='error'>" . $e->getMessage() . "</div>" ; 
										}
									
										if ($resultClass->rowCount()<1) {
											print "<div class='error'>" ;
											print _("There are no records to display.") ;
											print "</div>" ;
										}
										else {
											print "<table cellspacing='0' style='width: 100%'>" ;
												print "<tr class='head'>" ;
													print "<th>" ;
														print _("Class") ;
													print "</th>" ;
													print "<th>" ;
														print _("Running") . "<br/><span style='font-size: 80%'>" . _('Is class studying this unit?') . "</span>" ;
													print "</th>" ;
													print "<th>" ;
														print _("First Lesson") . "<br/><span style='font-size: 80%'>" ; if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; } ; print "</span>" ;
													print "</th>" ;
													print "<th>" ;
														print _("Actions") ;
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
															print $rowClass["courseName"] . "." . $rowClass["className"] . "</a>" ;
														print "</td>" ;
														print "<td>" ;
															print "Y" ;
														print "</td>" ;
														print "<td>" ;
															try {
																$dataDate=array("gibbonCourseClassID"=>$rowClass["gibbonCourseClassID"], "gibbonHookID"=>$gibbonHookIDToken, "gibbonUnitID"=>$gibbonUnitIDToken); 
																$sqlDate="SELECT date FROM gibbonPlannerEntry WHERE gibbonCourseClassID=:gibbonCourseClassID AND gibbonHookID=:gibbonHookID AND gibbonUnitID=:gibbonUnitID ORDER BY date, timeStart" ;
																$resultDate=$connection2->prepare($sqlDate);
																$resultDate->execute($dataDate);
															}
															catch(PDOException $e) { }
															if ($resultDate->rowCount()<1) {
																print "<i>" . _('There are no records to display.') . "</i>" ;
															}
															else {
																$rowDate=$resultDate->fetch() ;
																print dateConvertBack($guid, $rowDate["date"]) ;
															}
														print "</td>" ;
														print "<td>" ;
															if ($resultDate->rowCount()<1) {
																print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/units_edit_deploy.php&gibbonCourseClassID=" . $rowClass["gibbonCourseClassID"] . "&gibbonCourseID=$gibbonCourseID&gibbonUnitID=$gibbonUnitID&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonUnitClassID=" . $rowClass[$hookOptions["classLinkIDField"]] . "'><img title='Edit Unit' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/config.png'/></a> " ;
															}
															else {
																print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/units_edit_working.php&gibbonCourseClassID=" . $rowClass["gibbonCourseClassID"] . "&gibbonCourseID=$gibbonCourseID&gibbonUnitID=$gibbonUnitID&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonUnitClassID=" . $rowClass[$hookOptions["classLinkIDField"]] . "'><img title='Edit Unit' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/config.png'/></a> " ;
															}
															print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/planner.php&gibbonCourseClassID=" . $rowClass["gibbonCourseClassID"] . "&viewBy=class'><img style='margin-top: 3px' title='" . _('View Planner') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/planner.png'/></a> " ;
														print "</td>" ;
													print "</tr>" ;
													$classCount++ ;
												}
											print "</table>" ;
										}
										?>
									</td>
								</tr>
							</table>
							<?php
						}
					}
				}
			}
		}
	}
	//Print sidebar
	$_SESSION[$guid]["sidebarExtra"]=sidebarExtraUnits($guid, $connection2, $gibbonCourseID, $gibbonSchoolYearID) ;
}
?>