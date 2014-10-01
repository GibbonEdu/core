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

if (isActionAccessible($guid, $connection2, "/modules/Behaviour/behaviour_manage_edit.php")==FALSE) {
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
		//Proceed!
		print "<div class='trail'>" ;
		print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . _(getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Behaviour/behaviour_manage.php'>" . _('Manage Behaviour Records') . "</a> > </div><div class='trailEnd'>" . _('Edit') . "</div>" ;
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
		
		if (isset($_GET["deleteReturn"])) { $deleteReturn=$_GET["deleteReturn"] ; } else { $deleteReturn="" ; }
		$deleteReturnMessage="" ;
		$class="error" ;
		if (!($deleteReturn=="")) {
			if ($deleteReturn=="fail0") {
				$deleteReturnMessage=_("Your request failed because you do not have access to this action.") ;	
			}
			else if ($deleteReturn=="fail1") {
				$deleteReturnMessage=_("Your request failed because your inputs were invalid.") ;	
			}
			else if ($deleteReturn=="fail2") {
				$deleteReturnMessage=_("Your request failed due to a database error.") ;	
			}
			else if ($deleteReturn=="fail3") {
				$deleteReturnMessage=_("Your request failed because your inputs were invalid.") ;	
			}
			else if ($deleteReturn=="success0") {
				$deleteReturnMessage=_("Your request was completed successfully.") ;		
				$class="success" ;
			}
			print "<div class='$class'>" ;
				print $deleteReturnMessage;
			print "</div>" ;
		} 
		
		//Check if school year specified
		$gibbonBehaviourID=$_GET["gibbonBehaviourID"];
		if ($gibbonBehaviourID=="Y") {
			print "<div class='error'>" ;
				print _("You have not specified one or more required parameters.") ;
			print "</div>" ;
		}
		else {
			try {
				if ($highestAction=="Manage Behaviour Records_all") {
					$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonBehaviourID"=>$gibbonBehaviourID); 
					$sql="SELECT gibbonBehaviour.*, student.surname AS surnameStudent, student.preferredName AS preferredNameStudent, creator.surname AS surnameCreator, creator.preferredName AS preferredNameCreator, creator.title FROM gibbonBehaviour JOIN gibbonPerson AS student ON (gibbonBehaviour.gibbonPersonID=student.gibbonPersonID) JOIN gibbonPerson AS creator ON (gibbonBehaviour.gibbonPersonIDCreator=creator.gibbonPersonID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonBehaviourID=:gibbonBehaviourID ORDER BY date DESC" ; 
				}
				else if ($highestAction=="Manage Behaviour Records_my") {
					$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonBehaviourID"=>$gibbonBehaviourID, "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
					$sql="SELECT gibbonBehaviour.*, student.surname AS surnameStudent, student.preferredName AS preferredNameStudent, creator.surname AS surnameCreator, creator.preferredName AS preferredNameCreator, creator.title FROM gibbonBehaviour JOIN gibbonPerson AS student ON (gibbonBehaviour.gibbonPersonID=student.gibbonPersonID) JOIN gibbonPerson AS creator ON (gibbonBehaviour.gibbonPersonIDCreator=creator.gibbonPersonID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonBehaviourID=:gibbonBehaviourID AND gibbonPersonIDCreator=:gibbonPersonID ORDER BY date DESC" ; 
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
				print "<div class='linkTop'>" ;
					$policyLink=getSettingByScope($connection2, "Behaviour", "policyLink") ;
					if ($policyLink!="") {
						print "<a target='_blank' href='$policyLink'>" . _('View Behaviour Policy') . "</a>" ;
					}
					if ($_GET["gibbonPersonID"]!="" OR $_GET["gibbonRollGroupID"]!="" OR $_GET["gibbonYearGroupID"]!="" OR $_GET["type"]!="") {
						if ($policyLink!="") {
							print " | " ;
						}
						print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Behaviour/behaviour_manage.php&gibbonPersonID=" . $_GET["gibbonPersonID"] . "&gibbonRollGroupID=" . $_GET["gibbonRollGroupID"] . "&gibbonYearGroupID=" . $_GET["gibbonYearGroupID"] . "&type=" .$_GET["type"] . "'>" . _('Back to Search Results') . "</a>" ;
					}
				print "</div>" ;
		
				//Let's go!
				$row=$result->fetch() ;
				?>
				<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/behaviour_manage_editProcess.php?gibbonBehaviourID=$gibbonBehaviourID&gibbonPersonID=" . $_GET["gibbonPersonID"] . "&gibbonRollGroupID=" . $_GET["gibbonRollGroupID"] . "&gibbonYearGroupID=" . $_GET["gibbonYearGroupID"] . "&type=" .$_GET["type"] ?>">
					<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
						<tr>
							<td style='width: 275px'> 
								<b><?php print _('Student') ?> *</b><br/>
								<span style="font-size: 90%"><i><?php print _('This value cannot be changed.') ?></i></span>
							</td>
							<td class="right">
								<?php
								try {
									$dataSelect=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonPersonID"=>$row["gibbonPersonID"]); 
									$sqlSelect="SELECT * FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID AND status='Full' AND gibbonPerson.gibbonPersonID=:gibbonPersonID ORDER BY surname, preferredName" ;
									$resultSelect=$connection2->prepare($sqlSelect);
									$resultSelect->execute($dataSelect);
								}
								catch(PDOException $e) { }
								if ($resultSelect->rowCount()==1) {
									$rowSelect=$resultSelect->fetch() ;
								}
								
								?>
								<input type="hidden" name="gibbonPersonID" value="<?php print $row["gibbonPersonID"] ?>">
								<input readonly name="name" id="name" value="<?php print formatName("", $rowSelect["preferredName"], $rowSelect["surname"], "Student") ?>" type="text" style="width: 300px">
							</td>
						</tr>
						<tr>
							<td> 
								<b><?php print _('Date') ?> *</b><br/>
								<span style="font-size: 90%"><i><?php print _('Format:') ?> <?php if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?></i></span>
							</td>
							<td class="right">
								<input readonly name="date" id="date" maxlength=10 value="<?php print dateConvertBack($guid, $row["date"]) ?>" type="text" style="width: 300px">
							</td>
						</tr>
					
						<tr>
							<td> 
								<b><?php print _('Type') ?> *</b><br/>
								<span style="font-size: 90%"><i></i></span>
							</td>
							<td class="right">
								<select name="type" id="type" style="width: 302px">
									<option <?php if ($row["type"]=="Positive") { print "selected" ; } ?> value="Positive"><?php print _('Positive') ?></option>
									<option <?php if ($row["type"]=="Negative") { print "selected" ; } ?> value="Negative"><?php print _('Negative') ?></option>
								</select>
							</td>
						</tr>
						<?php
						try {
							$sqlPositive="SELECT * FROM gibbonSetting WHERE scope='Behaviour' AND name='positiveDescriptors'" ;
							$resultPositive=$connection2->query($sqlPositive);   
							$sqlNegative="SELECT * FROM gibbonSetting WHERE scope='Behaviour' AND name='negativeDescriptors'" ;
							$resultNegative=$connection2->query($sqlNegative);   
						}
						catch(PDOException $e) { }
						
						if ($resultPositive->rowCount()==1 AND $resultNegative->rowCount()==1) {
							$rowPositive=$resultPositive->fetch() ;
							$rowNegative=$resultNegative->fetch() ;
							
							$optionsPositive=$rowPositive["value"] ;
							$optionsNegative=$rowNegative["value"] ;
							
							if ($optionsPositive!="" AND $optionsNegative!="") {
								$optionsPositive=explode(",", $optionsPositive) ;
								$optionsNegative=explode(",", $optionsNegative) ;
								?>
								<tr>
									<td> 
										<b><?php print _('Descriptor') ?> *</b><br/>
										<span style="font-size: 90%"><i></i></span>
									</td>
									<td class="right">
										<select name="descriptor" id="descriptor" style="width: 302px">
											<option value="Please select..."><?php print _('Please select...') ?></option>
											<?php
											if ($row["descriptor"]=="Quick Star") {
												print "<option class='Positive' value='Quick Star'>" . _('Quick Star') . "</option>" ;
											}
											for ($i=0; $i<count($optionsPositive); $i++) {
												$selected="" ;
												if ($row["descriptor"]==$optionsPositive[$i]) {
													$selected="selected" ;
												}
												?>
												<option <?php print $selected ?> class='Positive' <?php if ($row["descriptor"]==$optionsPositive[$i]) {print "selected ";}?>value="<?php print trim($optionsPositive[$i]) ?>"><?php print trim($optionsPositive[$i]) ?></option>
											<?php
											}
											?>
											<?php
											for ($i=0; $i<count($optionsNegative); $i++) {
												$selected="" ;
												if ($row["descriptor"]==$optionsNegative[$i]) {
													$selected="selected" ;
												}
												?>
												<option <?php print $selected ?> class='Negative' <?php if ($row["descriptor"]==$optionsNegative[$i]) {print "selected ";}?>value="<?php print trim($optionsNegative[$i]) ?>"><?php print trim($optionsNegative[$i]) ?></option>
											<?php
											}
											?>
										</select>
										<script type="text/javascript">
											var descriptor=new LiveValidation('descriptor');
											descriptor.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print _('Select something!') ?>"});
										 </script>
										 <script type="text/javascript">
											$("#descriptor").chainedTo("#type");
										</script>
									</td>
								</tr>
								<?php
							}
						}
						?>
						
						<?php
						try {
							$dataLevels=array(); 
							$sqlLevels="SELECT * FROM gibbonSetting WHERE scope='Behaviour' AND name='Levels'" ;
							$resultLevels=$connection2->prepare($sqlLevels);
							$resultLevels->execute($dataLevels);
						}
						catch(PDOException $e) {}
						if ($resultLevels->rowCount()==1) {
							$rowLevels=$resultLevels->fetch() ;
							$optionsLevels=$rowLevels["value"] ;
							
							if ($optionsLevels!="") {
								$optionsLevels=explode(",", $optionsLevels) ;
								?>
								<tr>
									<td> 
										<b><?php print _('Level') ?> *</b><br/>
										<span style="font-size: 90%"><i></i></span>
									</td>
									<td class="right">
										<select name="level" id="level" style="width: 302px">
											<option value="Please select..."><?php print _('Please select...') ?></option>
											<?php
											for ($i=0; $i<count($optionsLevels); $i++) {
												$selected="" ;
												if ($row["level"]==$optionsLevels[$i]) {
													$selected="selected" ;
												}
												?>
												<option <?php print $selected ?> value="<?php print trim($optionsLevels[$i]) ?>"><?php print trim($optionsLevels[$i]) ?></option>
											<?php
											}
											?>
										</select>
										<script type="text/javascript">
											var level=new LiveValidation('level');
											level.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print _('Select something!') ?>"});
										 </script>
									</td>
								</tr>
								<?php
							}
						}
						?>
						<tr>
							<td> 
								<b><?php print _('Comment') ?></b><br/>
								<span style="font-size: 90%"><i></i></span>
							</td>
							<td class="right">
								<textarea name="comment" id="comment" rows=8 style="width: 300px"><?php print htmlPrep($row["comment"]) ?></textarea>
							</td>
						</tr>
						<tr>
							<td> 
								<b><?php print _('Link To Lesson?') ?></b><br/>
								<span style="font-size: 90%"><i><?php print _('From last 30 days') ?></i></span>
							</td>
							<td class="right">
								<select name="gibbonPlannerEntryID" id="gibbonPlannerEntryID" style="width: 302px">
									<option value=""></option>
									<?php
									$minDate=date("Y-m-d", (strtotime($row["date"])-(24*60*60*30))) ;
									
									try {
										$dataSelect=array("date"=>date("Y-m-d", strtotime($row["date"])), "minDate"=>$minDate, "gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonPersonID"=>$row["gibbonPersonID"]); 
										$sqlSelect="SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourseClass.gibbonCourseClassID, gibbonCourseClass.gibbonCourseClassID, gibbonPlannerEntry.name AS lesson, gibbonPlannerEntryID, date, homework, homeworkSubmission FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonPlannerEntry ON (gibbonCourseClass.gibbonCourseClassID=gibbonPlannerEntry.gibbonCourseClassID) WHERE (date<=:date AND date>=:minDate) AND gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND role='Student' ORDER BY course, class, date, timeStart" ;
										$resultSelect=$connection2->prepare($sqlSelect);
										$resultSelect->execute($dataSelect);
									}
									catch(PDOException $e) { }
									while ($rowSelect=$resultSelect->fetch()) {
										$show=TRUE ;
										if ($highestAction=="Manage Behaviour Records_my") {
											try {
												$dataShow=array("gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"], "gibbonCourseClassID"=>$rowSelect["gibbonCourseClassID"]); 
												$sqlShow="SELECT * FROM gibbonCourseClassPerson WHERE gibbonPersonID=:gibbonPersonID AND gibbonCourseClassID=:gibbonCourseClassID AND role='Teacher'" ;
												$resultShow=$connection2->prepare($sqlShow);
												$resultShow->execute($dataShow);
											}
											catch(PDOException $e) {}
											if ($resultShow->rowCount()!=1) {
												$show=FALSE ;
											}
										}
										if ($show==TRUE) {
											$submission="" ;
											if ($rowSelect["homework"]=="Y") {
												$submission="HW" ;
												if ($rowSelect["homeworkSubmission"]=="Y") {
													$submission.="+OS" ;
												}
											}
											if ($submission!="") {
												$submission=" - " . $submission ;
											}
											$selected="" ;
											if ($rowSelect["gibbonPlannerEntryID"]==$row["gibbonPlannerEntryID"]) {
												$selected="selected" ;
											}
											print "<option $selected value='" . $rowSelect["gibbonPlannerEntryID"] . "'>" . htmlPrep($rowSelect["course"]) . "." . htmlPrep($rowSelect["class"]) . " - " . htmlPrep($rowSelect["lesson"]) . "$submission</option>" ;
										}
									}
									?>			
								</select>
							</td>
						</tr>
						
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
?>