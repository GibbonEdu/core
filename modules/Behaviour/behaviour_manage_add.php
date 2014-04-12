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

if (isActionAccessible($guid, $connection2, "/modules/Behaviour/behaviour_manage_add.php")==FALSE) {
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
		print "<div class='trail'>" ;
		print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Behaviour/behaviour_manage.php'>Manage Behaviour Records</a> > </div><div class='trailEnd'>Add Record</div>" ;
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
				$addReturnMessage="Add succeeded, but there were problems uploading one or more attachments." ;	
			}
			else if ($addReturn=="success0") {
				$addReturnMessage=_("Your request was completed successfully.You can now add another record if you wish.") ;	
				$class="success" ;
			}
			print "<div class='$class'>" ;
				print $addReturnMessage;
			print "</div>" ;
		} 
		
		$step=NULL ;
		if (isset($_GET["step"])) {
			$step=$_GET["step"] ;
		}
		if ($step!=1 AND $step!=2) {
			$step=1 ;
		}
		
		//Step 1
		if ($step==1) {
			print "<div class='linkTop'>" ;
				$policyLink=getSettingByScope($connection2, "Behaviour", "policyLink") ;
				if ($policyLink!="") {
					print "<a target='_blank' href='$policyLink'>View Behaviour Policy</a>" ;
				}
				if ($_GET["gibbonPersonID"]!="" OR $_GET["gibbonRollGroupID"]!="" OR $_GET["gibbonYearGroupID"]!="" OR $_GET["type"]!="") {
					if ($policyLink!="") {
						print " | " ;
					}
					print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Behaviour/behaviour_manage.php&gibbonPersonID=" . $_GET["gibbonPersonID"] . "&gibbonRollGroupID=" . $_GET["gibbonRollGroupID"] . "&gibbonYearGroupID=" . $_GET["gibbonYearGroupID"] . "&type=" .$_GET["type"] . "'>" . _('Back to Search Results') . "</a>" ;
				}
			print "</div>" ;
		
			
			?>
		
			<form method="post" action="<? print $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/behaviour_manage_add.php&step=2&gibbonPersonID=" . $_GET["gibbonPersonID"] . "&gibbonRollGroupID=" . $_GET["gibbonRollGroupID"] . "&gibbonYearGroupID=" . $_GET["gibbonYearGroupID"] . "&type=" .$_GET["type"] ?>">
				<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
					<tr class='break'>
						<td colspan=2> 
							<h3>Step 1</h3>
						</td>
					</tr>
					<tr>
						<td> 
							<b><? print _('Student') ?> *</b><br/>
							<span style="font-size: 90%"><i></i></span>
						</td>
						<td class="right">
							<select name="gibbonPersonID" id="gibbonPersonID" style="width: 302px">
								<option value="Please select..."><? print _('Please select...') ?></option>
								<?
								try {
									$dataSelect=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
									$sqlSelect="SELECT * FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID AND status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') ORDER BY surname, preferredName" ;
									$resultSelect=$connection2->prepare($sqlSelect);
									$resultSelect->execute($dataSelect);
								}
								catch(PDOException $e) { 
									print "<div class='error'>" . $e->getMessage() . "</div>" ; 
								}
								while ($rowSelect=$resultSelect->fetch()) {
									if ($gibbonPersonID==$rowSelect["gibbonPersonID"]) {
										print "<option selected value='" . $rowSelect["gibbonPersonID"] . "'>" . formatName("", htmlPrep($rowSelect["preferredName"]), htmlPrep($rowSelect["surname"]), "Student", true) . htmlPrep($rowSelect["nameShort"]) . ")</option>" ;
									}
									else {
										print "<option value='" . $rowSelect["gibbonPersonID"] . "'>" . formatName("", htmlPrep($rowSelect["preferredName"]), htmlPrep($rowSelect["surname"]), "Student", true) . " (" . htmlPrep($rowSelect["nameShort"]) . ")</option>" ;
									}
								}
								?>			
							</select>
							<script type="text/javascript">
								var gibbonPersonID=new LiveValidation('gibbonPersonID');
								gibbonPersonID.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<? print _('Select something!') ?>"});
							 </script>
									
						</td>
					</tr>
					<tr>
						<td> 
							<b>Date *</b><br/>
							<span style="font-size: 90%"><i>Format <? if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?></i></span>
						</td>
						<td class="right">
							<input name="date" id="date" maxlength=10 value="<? print date("d/m/Y") ?>" type="text" style="width: 300px">
							<script type="text/javascript">
								var date=new LiveValidation('date');
								date.add( Validate.Format, {pattern: <? if ($_SESSION[$guid]["i18n"]["dateFormatRegEx"]=="") {  print "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i" ; } else { print $_SESSION[$guid]["i18n"]["dateFormatRegEx"] ; } ?>, failureMessage: "Use <? if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?>." } ); 
							 </script>
							 <script type="text/javascript">
								$(function() {
									$( "#date" ).datepicker();
								});
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><? print _('Type') ?> *</b><br/>
							<span style="font-size: 90%"><i></i></span>
						</td>
						<td class="right">
							<select name="type" id="type" style="width: 302px">
								<option value="Positive">Positive</option>
								<option value="Negative">Negative</option>
							</select>
						</td>
					</tr>
					<?
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
									<b>Descriptor *</b><br/>
									<span style="font-size: 90%"><i></i></span>
								</td>
								<td class="right">
									<select name="descriptor" id="descriptor" style="width: 302px">
										<option value="Please select..."><? print _('Please select...') ?></option>
										<?
										for ($i=0; $i<count($optionsPositive); $i++) {
										?>
											<option class='Positive' value="<? print trim($optionsPositive[$i]) ?>"><? print trim($optionsPositive[$i]) ?></option>
										<?
										}
										?>
										<?
										for ($i=0; $i<count($optionsNegative); $i++) {
										?>
											<option class='Negative' value="<? print trim($optionsNegative[$i]) ?>"><? print trim($optionsNegative[$i]) ?></option>
										<?
										}
										?>
									</select>
									<script type="text/javascript">
										var descriptor=new LiveValidation('descriptor');
										descriptor.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<? print _('Select something!') ?>"});
									 </script>
									 <script type="text/javascript">
										$("#descriptor").chainedTo("#type");
									</script>
								</td>
							</tr>
							<?
						}
					}
					
					$optionsLevels=getSettingByScope($connection2, "Behaviour", "levels") ;
					if ($optionsLevels!="") {
						$optionsLevels=explode(",", $optionsLevels) ;
						?>
						<tr>
							<td> 
								<b>Level *</b><br/>
								<span style="font-size: 90%"><i></i></span>
							</td>
							<td class="right">
								<select name="level" id="level" style="width: 302px">
									<option value="Please select..."><? print _('Please select...') ?></option>
									<?
									for ($i=0; $i<count($optionsLevels); $i++) {
									?>
										<option value="<? print trim($optionsLevels[$i]) ?>"><? print trim($optionsLevels[$i]) ?></option>
									<?
									}
									?>
								</select>
								<script type="text/javascript">
									var level=new LiveValidation('level');
									level.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<? print _('Select something!') ?>"});
								 </script>
							</td>
						</tr>
						<?
					}
					?>
					<tr>
						<td> 
							<b>Comment</b><br/>
							<span style="font-size: 90%"><i></i></span>
						</td>
						<td class="right">
							<textarea name="comment" id="comment" rows=8 style="width: 300px"></textarea>
						</td>
					</tr>
					<tr>
						<td>
							<span style="font-size: 90%"><i>* <? print _("denotes a required field") ; ?></i></span>
						</td>
						<td class="right">
							<input type="hidden" name="address" value="<? print $_SESSION[$guid]["address"] ?>">
							<input type="submit" value="Next">
						</td>
					</tr>
				</table>
			</form>
			<?
		}
		else {
			print "<div class='linkTop'>" ;
				$policyLink=getSettingByScope($connection2, "Behaviour", "policyLink") ;
				if ($policyLink!="") {
					print "<a target='_blank' href='$policyLink'>View Behaviour Policy</a>" ;
				}
				if ($_GET["gibbonPersonID"]!="" OR $_GET["gibbonRollGroupID"]!="" OR $_GET["gibbonYearGroupID"]!="" OR $_GET["type"]!="") {
					if ($policyLink!="") {
						print " | " ;
					}
					print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Behaviour/behaviour_manage.php&gibbonPersonID=" . $_GET["gibbonPersonID"] . "&gibbonRollGroupID=" . $_GET["gibbonRollGroupID"] . "&gibbonYearGroupID=" . $_GET["gibbonYearGroupID"] . "&type=" .$_GET["type"] . "'>" . _('Back to Search Results') . "</a>" ;
				}
			print "</div>" ;
		
			
			$gibbonPersonID=$_POST["gibbonPersonID"] ; 
			$date=$_POST["date"] ; 
			$type=$_POST["type"] ; 
			$descriptor=$_POST["descriptor"] ; 
			$level=$_POST["level"] ; 
			$comment=$_POST["comment"] ; 
			
			if ($gibbonPersonID=="" OR $date=="" OR $type=="" OR $descriptor=="") {
				print "<div class='error'>" ;
					print _("You have not specified one or more required parameters.") ;
				print "</div>" ;
			}
			else {
				?>
				<form method="post" action="<? print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/behaviour_manage_addProcess.php?gibbonPersonID=" . $_GET["gibbonPersonID"] . "&gibbonRollGroupID=" . $_GET["gibbonRollGroupID"] . "&gibbonYearGroupID=" . $_GET["gibbonYearGroupID"] . "&type=" .$_GET["type"] ?>">
					<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
						<tr class='break'>
							<td colspan=2> 
								<h3>Step 2</h3>
							</td>
						</tr>
						<tr>
							<td> 
								<b><? print _('Student') ?> *</b><br/>
								<span style="font-size: 90%"><i>This value cannot be changed</i></span>
							</td>
							<td class="right">
								<?
								try {
									$dataSelect=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonPersonID"=>$_POST["gibbonPersonID"]); 
									$sqlSelect="SELECT * FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID AND status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonPerson.gibbonPersonID=:gibbonPersonID ORDER BY surname, preferredName" ;
									$resultSelect=$connection2->prepare($sqlSelect);
									$resultSelect->execute($dataSelect);
								}
								catch(PDOException $e) { 
									print "<div class='error'>" . $e->getMessage() . "</div>" ; 
								}
								if ($resultSelect->rowCount()==1) {
									$rowSelect=$resultSelect->fetch() ;
								}
								
								?>
								<input type="hidden" name="gibbonPersonID" value="<? print $gibbonPersonID ?>">
								<input readonly name="name" id="name" value="<? print formatName("", $rowSelect["preferredName"], $rowSelect["surname"], "Student") ?>" type="text" style="width: 300px">
							</td>
						</tr>
						<tr>
							<td> 
								<b>Date *</b><br/>
								<span style="font-size: 90%"><i>Format <? if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?></i></span>
							</td>
							<td class="right">
								<input readonly name="date" id="date" maxlength=10 value="<? print $date ?>" type="text" style="width: 300px">
							</td>
						</tr>
						<tr>
							<td> 
								<b>Link To Lesson?</b><br/>
								<span style="font-size: 90%"><i>From last 30 days</i></span>
							</td>
							<td class="right">
								<select name="gibbonPlannerEntryID" id="gibbonPlannerEntryID" style="width: 302px">
									<option value=""></option>
									<?
									$minDate=date("Y-m-d", (time()-(24*60*60*30))) ;
									try {
										$dataSelect=array("date1"=>date("Y-m-d", time()), "date2"=>$minDate, "gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonPersonID"=>$gibbonPersonID); 
										$sqlSelect="SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourseClass.gibbonCourseClassID, gibbonCourseClass.gibbonCourseClassID, gibbonPlannerEntry.name AS lesson, gibbonPlannerEntryID, date, homework, homeworkSubmission FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonPlannerEntry ON (gibbonCourseClass.gibbonCourseClassID=gibbonPlannerEntry.gibbonCourseClassID) WHERE (date<=:date1 AND date>=:date2) AND gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND role='Student' ORDER BY course, class, date, timeStart" ;
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
											catch(PDOException $e) { 
												print "<div class='error'>" . $e->getMessage() . "</div>" ; 
											}
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
											print "<option value='" . $rowSelect["gibbonPlannerEntryID"] . "'>" . htmlPrep($rowSelect["course"]) . "." . htmlPrep($rowSelect["class"]) . " " . htmlPrep($rowSelect["lesson"]) . " - " . substr(dateConvertBack($guid, $rowSelect["date"]),0,5) . "$submission</option>" ;
										}
									}
									?>			
								</select>
							</td>
						</tr>
						
						<tr>
							<td>
								<span style="font-size: 90%"><i>* <? print _("denotes a required field") ; ?></i></span>
							</td>
							<td class="right">
								<input type="hidden" name="type" value="<? print $type ?>">
								<input type="hidden" name="descriptor" value="<? print $descriptor ?>">
								<input type="hidden" name="level" value="<? print $level ?>">
								<input type="hidden" name="comment" value="<? print $comment ?>">
								
							
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
?>