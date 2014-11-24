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

if (isActionAccessible($guid, $connection2, "/modules/Data Updater/data_medical.php")==FALSE) {
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
		print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . _(getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . _('Update Medical Data') . "</div>" ;
		print "</div>" ;
		
		if ($highestAction=="Update Medical Data_any") {
			print "<p>" ;
			print _("This page allows a user to request selected medical data updates for any student.") ;
			print "</p>" ;
		}
		else {
			print "<p>" ;
			print _("This page allows any adult with data access permission to request medical data updates for any member of their family.") ;
			print "</p>" ;
		}
		
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
				$updateReturnMessage=_("Your request was successful, but some data was not properly saved.") ;	
			}
			else if ($updateReturn=="success0") {
				$updateReturnMessage=_("Your request was completed successfully. An administrator will process your request as soon as possible. You will not see the updated data in the system until it has been processed and approved.") ; 
				if ($_SESSION[$guid]["organisationDBAEmail"]!="" AND $_SESSION[$guid]["organisationDBAName"]!="") {
					$updateReturnMessage.=" " . sprintf(_('Please contact %1$s if you have any questions.'), "<a href='mailto:" . $_SESSION[$guid]["organisationDBAEmail"] . "'>" . $_SESSION[$guid]["organisationDBAName"] . "</a>") ;	
				}
				$class="success" ;
			}
			print "<div class='$class'>" ;
				print $updateReturnMessage;
			print "</div>" ;
		} 
		
		print "<h2>" ;
		print "Choose User" ;
		print "</h2>" ;
		
		$gibbonPersonID=NULL ;
		if (isset($_GET["gibbonPersonID"])) {
			$gibbonPersonID=$_GET["gibbonPersonID"] ;
		}
		?>
		
		<form method="get" action="<?php print $_SESSION[$guid]["absoluteURL"]?>/index.php">
			<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
				<tr>
					<td style='width: 275px'> 
						<b><?php print _('Person') ?> *</b><br/>
					</td>
					<td class="right">
						<select style="width: 302px" name="gibbonPersonID">
							<?php
							if ($highestAction=="Update Medical Data_any") {
								try {
									$dataSelect=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
									$sqlSelect="SELECT surname, preferredName, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND status='Full' ORDER BY surname, preferredName" ;
									$resultSelect=$connection2->prepare($sqlSelect);
									$resultSelect->execute($dataSelect);
								}
								catch(PDOException $e) { }
								print "<option value=''></option>" ;
								while ($rowSelect=$resultSelect->fetch()) {
									if ($gibbonPersonID==$rowSelect["gibbonPersonID"]) {
										print "<option selected value='" . $rowSelect["gibbonPersonID"] . "'>" . formatName("", htmlPrep($rowSelect["preferredName"]), htmlPrep($rowSelect["surname"]), "Student", true) . "</option>" ;
									}
									else {
										print "<option value='" . $rowSelect["gibbonPersonID"] . "'>" . formatName("", htmlPrep($rowSelect["preferredName"]), htmlPrep($rowSelect["surname"]), "Student", true) . "</option>" ;
									}
								}
							}
							else {
								try {
									$dataSelect=array("gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
									$sqlSelect="SELECT gibbonFamilyAdult.gibbonFamilyID, name FROM gibbonFamilyAdult JOIN gibbonFamily ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID) WHERE gibbonPersonID=:gibbonPersonID AND childDataAccess='Y' ORDER BY name" ;
									$resultSelect=$connection2->prepare($sqlSelect);
									$resultSelect->execute($dataSelect);
								}
								catch(PDOException $e) { }
								print "<option value=''></option>" ;
								while ($rowSelect=$resultSelect->fetch()) {
									try {
										$dataSelect2=array("gibbonFamilyID"=>$rowSelect["gibbonFamilyID"]); 
										$sqlSelect2="SELECT surname, preferredName, gibbonPerson.gibbonPersonID, gibbonFamilyID FROM gibbonFamilyChild JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPerson.status='Full' AND gibbonFamilyID=:gibbonFamilyID" ;
										$resultSelect2=$connection2->prepare($sqlSelect2);
										$resultSelect2->execute($dataSelect2);
									}
									catch(PDOException $e) { }
									while ($rowSelect2=$resultSelect2->fetch()) {
										if ($gibbonPersonID==$rowSelect2["gibbonPersonID"]) {
											print "<option selected value='" . $rowSelect2["gibbonPersonID"] . "'>" . formatName("", htmlPrep($rowSelect2["preferredName"]), htmlPrep($rowSelect2["surname"]), "Student", true) . "</option>" ;
										}
										else {
											print "<option value='" . $rowSelect2["gibbonPersonID"] . "'>" . formatName("", htmlPrep($rowSelect2["preferredName"]), htmlPrep($rowSelect2["surname"]), "Student", true) . "</option>" ;
										}
									}
								}
							}
							?>				
						</select>
					</td>
				</tr>
				<tr>
					<td colspan=2 class="right">
						<input type="hidden" name="q" value="/modules/<?php print $_SESSION[$guid]["module"] ?>/data_medical.php">
						<input type="submit" value="<?php print _("Submit") ; ?>">
					</td>
				</tr>
			</table>
		</form>
		<?php
		
		if ($gibbonPersonID!="") {
			print "<h2>" ;
			print _("Update Data") ;
			print "</h2>" ;
			
			//Check access to person
			$checkCount=0 ;
			if ($highestAction=="Update Medical Data_any") {
				try {
					$dataSelect=array(); 
					$sqlSelect="SELECT surname, preferredName, gibbonPerson.gibbonPersonID FROM gibbonPerson WHERE status='Full' ORDER BY surname, preferredName" ;
					$resultSelect=$connection2->prepare($sqlSelect);
					$resultSelect->execute($dataSelect);
				}
				catch(PDOException $e) { }
				$checkCount=$resultSelect->rowCount() ;
			}
			else {
				try {
					$dataCheck=array("gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
					$sqlCheck="SELECT gibbonFamilyAdult.gibbonFamilyID, name FROM gibbonFamilyAdult JOIN gibbonFamily ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID) WHERE gibbonPersonID=:gibbonPersonID AND childDataAccess='Y' ORDER BY name" ;
					$resultCheck=$connection2->prepare($sqlCheck);
					$resultCheck->execute($dataCheck);
				}
				catch(PDOException $e) { }
				while ($rowCheck=$resultCheck->fetch()) {
					try {
						$dataCheck2=array("gibbonFamilyID"=>$rowCheck["gibbonFamilyID"], "gibbonFamilyID2"=>$rowCheck["gibbonFamilyID"]); 
						$sqlCheck2="(SELECT surname, preferredName, gibbonPerson.gibbonPersonID, gibbonFamilyID FROM gibbonFamilyChild JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonFamilyID=:gibbonFamilyID) UNION (SELECT surname, preferredName, gibbonPerson.gibbonPersonID, gibbonFamilyID FROM gibbonFamilyAdult JOIN gibbonPerson ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonFamilyID=:gibbonFamilyID2)" ;
						$resultCheck2=$connection2->prepare($sqlCheck2);
						$resultCheck2->execute($dataCheck2);
					}
					catch(PDOException $e) { }
					while ($rowCheck2=$resultCheck2->fetch()) {
						if ($gibbonPersonID==$rowCheck2["gibbonPersonID"]) {
							$checkCount++ ;
						}
					}
				}
			}
			if ($checkCount<1) {
				print "<div class='error'>" ;
					print _("The selected record does not exist, or you do not have access to it.") ;
				print "</div>" ;
			}
			else {
				//Get user's data
				try {
					$data=array("gibbonPersonID"=>$gibbonPersonID); 
					$sql="SELECT * FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonID" ;
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
					//Check if there is already a pending form for this user
					$existing=FALSE ;
					$proceed=FALSE;
					try {
						$dataForm=array("gibbonPersonID"=>$gibbonPersonID, "gibbonPersonID2"=>$_SESSION[$guid]["gibbonPersonID"]); 
						$sqlForm="SELECT * FROM gibbonPersonMedicalUpdate WHERE gibbonPersonID=:gibbonPersonID AND gibbonPersonIDUpdater=:gibbonPersonID2 AND status='Pending'" ;
						$resultForm=$connection2->prepare($sqlForm);
						$resultForm->execute($dataForm);
					}
					catch(PDOException $e) { 
						print "<div class='error'>" . $e->getMessage() . "</div>" ; 
					}
					if ($resultForm->rowCount()>1) {
						print "<div class='error'>" ;
							print _("Your request failed due to a database error.") ;
						print "</div>" ;
					}
					else if ($resultForm->rowCount()==1) {
						$existing=TRUE ;
						$proceed=TRUE;
						if ($updateReturn=="") {
							print "<div class='warning'>" ;
								print _("You have already submitted a form, which is pending approval by an administrator. If you wish to make changes, please edited the data below, but remember your data will not appear in the system until it has been approved.") ;
							print "</div>" ;
						}
					}
					else {
						//Get user's data
						try {
							$dataForm=array("gibbonPersonID"=>$gibbonPersonID); 
							$sqlForm="SELECT * FROM gibbonPersonMedical WHERE gibbonPersonID=:gibbonPersonID" ;
							$resultForm=$connection2->prepare($sqlForm);
							$resultForm->execute($dataForm);
						}
						catch(PDOException $e) { 
							print "<div class='error'>" . $e->getMessage() . "</div>" ; 
						}

						if ($result->rowCount()==1) {
							$proceed=TRUE;
						}
					}
				
					if ($proceed==TRUE) {
						$rowForm=$resultForm->fetch() ;
						?>
						<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/data_medicalProcess.php?gibbonPersonID=" . $gibbonPersonID ?>">
							<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
								<tr>
									<td style='width: 275px'> 
										<b><?php print _('Blood Type') ?></b><br/>
										<span style="font-size: 90%"><i></i></span>
									</td>
									<td class="right">
										<select style="width: 302px" name="bloodType">
											<option <?php if ($rowForm["bloodType"]=="") {print "selected ";}?>value=""></option>
											<option <?php if ($rowForm["bloodType"]=="O+") {print "selected ";}?>value="O+">O+</option>
											<option <?php if ($rowForm["bloodType"]=="A+") {print "selected ";}?>value="A+">A+</option>
											<option <?php if ($rowForm["bloodType"]=="B+") {print "selected ";}?>value="B+">B+</option>
											<option <?php if ($rowForm["bloodType"]=="AB+") {print "selected ";}?>value="AB+">AB+</option>
											<option <?php if ($rowForm["bloodType"]=="O-") {print "selected ";}?>value="O-">O-</option>
											<option <?php if ($rowForm["bloodType"]=="A-") {print "selected ";}?>value="A-">A-</option>
											<option <?php if ($rowForm["bloodType"]=="B-") {print "selected ";}?>value="B-">B-</option>
											<option <?php if ($rowForm["bloodType"]=="AB-") {print "selected ";}?>value="AB-">AB-</option>
										</select>
									</td>
								</tr>
								<tr>
									<td> 
										<b><?php print _('Long-Term Medication?') ?></b><br/>
										<span style="font-size: 90%"><i></i></span>
									</td>
									<td class="right">
										<select style="width: 302px" name="longTermMedication">
											<option <?php if ($rowForm["longTermMedication"]=="") {print "selected ";}?>value=""></option>
											<option <?php if ($rowForm["longTermMedication"]=="Y") {print "selected ";}?>value="Y"><?php print _('Yes') ?></option>
											<option <?php if ($rowForm["longTermMedication"]=="N") {print "selected ";}?>value="N"><?php print _('No') ?></option>
										</select>
									</td>
								</tr>
								<tr>
									<td> 
										<b><?php print _('Medication Details') ?></b><br/>
									</td>
									<td class="right">
										<textarea name="longTermMedicationDetails" id="longTermMedicationDetails" rowForms=8 style="width: 300px"><?php print $rowForm["longTermMedicationDetails"] ?></textarea>
									</td>
								</tr>
								<tr>
									<td> 
										<b><?php print _('Tetanus Within Last 10 Years?') ?></b><br/>
										<span style="font-size: 90%"><i></i></span>
									</td>
									<td class="right">
										<select style="width: 302px" name="tetanusWithin10Years">
											<option <?php if ($rowForm["tetanusWithin10Years"]=="") {print "selected ";}?>value=""></option>
											<option <?php if ($rowForm["tetanusWithin10Years"]=="Y") {print "selected ";}?>value="Y"><?php print _('Yes') ?></option>
											<option <?php if ($rowForm["tetanusWithin10Years"]=="N") {print "selected ";}?>value="N"><?php print _('No') ?></option>
										</select>
									</td>
								</tr>
								
								<input name="gibbonPersonMedicalID" id="gibbonPersonMedicalID" value="<?php print htmlPrep($rowForm["gibbonPersonMedicalID"]) ?>" type="hidden">
								
								<?php
								$count=0 ;
								if ($rowForm["gibbonPersonMedicalID"]!="" OR $existing==true) {
									try {
										if ($existing==true) {
											$dataCond=array("gibbonPersonMedicalUpdateID"=>$rowForm["gibbonPersonMedicalUpdateID"]); 
											$sqlCond="SELECT * FROM gibbonPersonMedicalConditionUpdate WHERE gibbonPersonMedicalUpdateID=:gibbonPersonMedicalUpdateID ORDER BY name" ; 
										}
										else {
											$dataCond=array("gibbonPersonMedicalID"=>$rowForm["gibbonPersonMedicalID"]); 
											$sqlCond="SELECT * FROM gibbonPersonMedicalCondition WHERE gibbonPersonMedicalID=:gibbonPersonMedicalID ORDER BY name" ; 
										}
										$resultCond=$connection2->prepare($sqlCond);
										$resultCond->execute($dataCond);
									}
									catch(PDOException $e) { 
										print "<div class='error'>" . $e->getMessage() . "</div>" ; 
									}

									while ($rowCond=$resultCond->fetch()) {
										?>
										<tr class='break'>
											<td colspan=2> 
												<h3><?php print _('Medical Condition') ?> <?php print ($count+1) ?></h3>
											</td>
										</tr>
										<tr>
											<td> 
												<b><?php print _('Condition Name') ?> *</b><br/>
											</td>
											<td class="right">
												<select style="width: 302px" name="name<?php print $count ?>" id="name<?php print $count ?>">
													<?php
													try {
														$dataSelect=array(); 
														$sqlSelect="SELECT * FROM gibbonMedicalCondition ORDER BY name" ;
														$resultSelect=$connection2->prepare($sqlSelect);
														$resultSelect->execute($dataSelect);
													}
													catch(PDOException $e) { }
													print "<option value='Please select...'>" . _('Please select...') . "</option>" ;
													while ($rowSelect=$resultSelect->fetch()) {
														 if ($rowCond["name"]==$rowSelect["name"]) {
															print "<option selected value='" . htmlPrep($rowSelect["name"]) . "'>" . htmlPrep(_($rowSelect["name"])) . "</option>" ;
														}
														 else {
															print "<option value='" . htmlPrep($rowSelect["name"]) . "'>" . htmlPrep(_($rowSelect["name"])) . "</option>" ;
														}
													}
													?>				
												</select>
												<script type="text/javascript">
													var name<?php print $count ?>=new LiveValidation('name<?php print $count ?>');
													name<?php print $count ?>.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print _('Select something!') ?>"});
												 </script>	
											</td>
										</tr>
										<tr>
											<td> 
												<b><?php print _('Risk') ?> *</b><br/>
											</td>
											<td class="right">
												<select name="gibbonAlertLevelID<?php print $count ?>" id="gibbonAlertLevelID<?php print $count ?>" style="width: 302px">
													<option value='Please select...'><?php print _('Please select...') ?></option>
													<?php
													try {
														$dataSelect=array(); 
														$sqlSelect="SELECT * FROM gibbonAlertLevel ORDER BY sequenceNumber" ;
														$resultSelect=$connection2->prepare($sqlSelect);
														$resultSelect->execute($dataSelect);
													}
													catch(PDOException $e) { }
													
													while ($rowSelect=$resultSelect->fetch()) {
														$selected="" ;
														if ($rowCond["gibbonAlertLevelID"]==$rowSelect["gibbonAlertLevelID"]) {
															$selected="selected" ;
														}	
														print "<option $selected value='" . $rowSelect["gibbonAlertLevelID"] . "'>" . _($rowSelect["name"]) . "</option>" ; 
													}
													?>
												</select>
												<script type="text/javascript">
													var gibbonAlertLevelID<?php print $count ?>=new LiveValidation('gibbonAlertLevelID<?php print $count ?>');
													gibbonAlertLevelID<?php print $count ?>.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print _('Select something!') ?>"});
												 </script>	
											</td>
										</tr>
										<tr>
											<td> 
												<b><?php print _('Triggers') ?></b><br/>
											</td>
											<td class="right">
												<input name="triggers<?php print $count ?>" id="triggers<?php print $count ?>" maxlength=255 value="<?php print htmlPrep($rowCond["triggers"]) ?>" type="text" style="width: 300px">
											</td>
										</tr>
										<tr>
											<td> 
												<b><?php print _('Reaction') ?></b><br/>
											</td>
											<td class="right">
												<input name="reaction<?php print $count ?>" id="reaction<?php print $count ?>" maxlength=255 value="<?php print htmlPrep($rowCond["reaction"]) ?>" type="text" style="width: 300px">
											</td>
										</tr>
										<tr>
											<td> 
												<b><?php print _('Response') ?></b><br/>
											</td>
											<td class="right">
												<input name="response<?php print $count ?>" id="response<?php print $count ?>" maxlength=255 value="<?php print htmlPrep($rowCond["response"]) ?>" type="text" style="width: 300px">
											</td>
										</tr>
										<tr>
											<td> 
												<b><?php print _('Medication') ?></b><br/>
											</td>
											<td class="right">
												<input name="medication<?php print $count ?>" id="medication<?php print $count ?>" maxlength=255 value="<?php print htmlPrep($rowCond["medication"]) ?>" type="text" style="width: 300px">
											</td>
										</tr>
										<tr>
											<td> 
												<b><?php print _('Last Episode Date') ?></b><br/>
												<span style="font-size: 90%"><i><?php print _('Format:') . " " . $_SESSION[$guid]["i18n"]["dateFormat"]  ?></i></span>
											</td>
											<td class="right">
												<input name="lastEpisode<?php print $count ?>" id="lastEpisode<?php print $count ?>" maxlength=10 value="<?php print dateConvertBack($guid, $rowCond["lastEpisode"]) ?>" type="text" style="width: 300px">
												<script type="text/javascript">
													var lastEpisode<?php print $count ?>=new LiveValidation('lastEpisode<?php print $count ?>');
													lastEpisode<?php print $count ?>.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]["i18n"]["dateFormatRegEx"]=="") {  print "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i" ; } else { print $_SESSION[$guid]["i18n"]["dateFormatRegEx"] ; } ?>, failureMessage: "Use <?php if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?>." } ); 
												 </script>
												 <script type="text/javascript">
													$(function() {
														$( "#lastEpisode<?php print $count ?>" ).datepicker();
													});
												</script>
											</td>
										</tr>
										<tr>
											<td> 
												<b><?php print _('Last Episode Treatment') ?></b><br/>
											</td>
											<td class="right">
												<input name="lastEpisodeTreatment<?php print $count ?>" id="lastEpisodeTreatment<?php print $count ?>" maxlength=255 value="<?php print htmlPrep($rowCond["lastEpisodeTreatment"]) ?>" type="text" style="width: 300px">
											</td>
										</tr>
										<tr>
											<td> 
												<b><?php print _('Comment') ?></b><br/>
											</td>
											<td class="right">
												<textarea name="comment<?php print $count ?>" id="comment<?php print $count ?>" rows=8 style="width: 300px"><?php print $rowCond["comment"] ?></textarea>
											</td>
										</tr>
										<input name="gibbonPersonMedicalConditionID<?php print $count ?>" id="gibbonPersonMedicalConditionID<?php print $count ?>" value="<?php print htmlPrep($rowCond["gibbonPersonMedicalConditionID"]) ?>" type="hidden">
										<input name="gibbonPersonMedicalConditionUpdateID<?php print $count ?>" id="gibbonPersonMedicalConditionUpdateID<?php print $count ?>" value="<?php print htmlPrep($rowCond["gibbonPersonMedicalConditionUpdateID"]) ?>" type="hidden">
										<?php
										$count++ ;
									}
									
									print "<input name='count' id='count' value='$count' type='hidden'>" ;
								}
								?>
								<tr class='break'>
									<td colspan=2> 
										<h3><?php print _('Add Medical Condition') ?></h3>
									</td>
								</tr>
								<tr>
									<td> 
										<b><?php print _('Condition Name') ?> *</b><br/>
									</td>
									<td class="right">
										<select style="width: 302px" name="name" id="name">
											<?php
											try {
												$dataSelect=array(); 
												$sqlSelect="SELECT * FROM gibbonMedicalCondition ORDER BY name" ;
												$resultSelect=$connection2->prepare($sqlSelect);
												$resultSelect->execute($dataSelect);
											}
											catch(PDOException $e) { }
											print "<option value=''>Please select...</option>" ;
											while ($rowSelect=$resultSelect->fetch()) {
												 if ($rowCond["name"]==$rowSelect["name"]) {
													print "<option selected value='" . htmlPrep($rowSelect["name"]) . "'>" . htmlPrep(_($rowSelect["name"])) . "</option>" ;
												}
												 else {
													print "<option value='" . htmlPrep($rowSelect["name"]) . "'>" . htmlPrep(_($rowSelect["name"])) . "</option>" ;
												}
											}
											?>				
										</select>
									</td>
								</tr>
								<tr>
									<td> 
										<b><?php print _('Risk') ?> *</b><br/>
									</td>
									<td class="right">
										<select name="gibbonAlertLevelID" id="gibbonAlertLevelID" style="width: 302px">
										<option value='Please select...'><?php print _('Please select...') ?></option>
										<?php
										try {
											$dataSelect=array(); 
											$sqlSelect="SELECT * FROM gibbonAlertLevel ORDER BY sequenceNumber" ;
											$resultSelect=$connection2->prepare($sqlSelect);
											$resultSelect->execute($dataSelect);
										}
										catch(PDOException $e) { }
										
										while ($rowSelect=$resultSelect->fetch()) {
											print "<option value='" . $rowSelect["gibbonAlertLevelID"] . "'>" . _($rowSelect["name"]) . "</option>" ; 
										}
										?>
									</select>
									</td>
								</tr>
								<tr>
									<td> 
										<b><?php print _('Triggers') ?></b><br/>
									</td>
									<td class="right">
										<input name="triggers" id="triggers" maxlength=255 value="<?php print htmlPrep($rowCond["triggers"]) ?>" type="text" style="width: 300px">
									</td>
								</tr>
								<tr>
									<td> 
										<b><?php print _('Reaction') ?></b><br/>
									</td>
									<td class="right">
										<input name="reaction" id="reaction" maxlength=255 value="<?php print htmlPrep($rowCond["reaction"]) ?>" type="text" style="width: 300px">
									</td>
								</tr>
								<tr>
									<td> 
										<b><?php print _('Response') ?></b><br/>
									</td>
									<td class="right">
										<input name="response" id="response" maxlength=255 value="<?php print htmlPrep($rowCond["response"]) ?>" type="text" style="width: 300px">
									</td>
								</tr>
								<tr>
									<td> 
										<b><?php print _('Medication') ?></b><br/>
									</td>
									<td class="right">
										<input name="medication" id="medication" maxlength=255 value="<?php print htmlPrep($rowCond["medication"]) ?>" type="text" style="width: 300px">
									</td>
								</tr>
								<tr>
									<td> 
										<b><?php print _('Last Episode Date') ?></b><br/>
										<span style="font-size: 90%"><i><?php print _('Format:') . " " . $_SESSION[$guid]["i18n"]["dateFormat"]  ?></i></span>
									</td>
									<td class="right">
										<input name="lastEpisode" id="lastEpisode" maxlength=10 value="<?php print dateConvertBack($guid, $rowCond["lastEpisode"]) ?>" type="text" style="width: 300px">
										<script type="text/javascript">
											var lastEpisode=new LiveValidation('lastEpisode');
											lastEpisode.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]["i18n"]["dateFormatRegEx"]=="") {  print "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i" ; } else { print $_SESSION[$guid]["i18n"]["dateFormatRegEx"] ; } ?>, failureMessage: "Use <?php if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?>." } ); 
										 </script>
										 <script type="text/javascript">
											$(function() {
												$( "#lastEpisode" ).datepicker();
											});
										</script>
									</td>
								</tr>
								<tr>
									<td> 
										<b><?php print _('Last Episode Treatment') ?></b><br/>
									</td>
									<td class="right">
										<input name="lastEpisodeTreatment" id="lastEpisodeTreatment" maxlength=255 value="<?php print htmlPrep($rowCond["lastEpisodeTreatment"]) ?>" type="text" style="width: 300px">
									</td>
								</tr>
								<tr>
									<td> 
										<b><?php print _('Comment') ?></b><br/>
									</td>
									<td class="right">
										<textarea name="comment" id="comment" rows=8 style="width: 300px"><?php print $rowCond["comment"] ?></textarea>
									</td>
								</tr>
								<tr>
									<td>
										<span style="font-size: 90%"><i>* <?php print _("denotes a required field") ; ?></i></span>
									</td>
									<td class="right">
										<?php
										if ($existing) {
											print "<input type='hidden' name='existing' value='" . $rowForm["gibbonPersonMedicalUpdateID"] . "'>" ;
										}
										else {
											print "<input type='hidden' name='existing' value='N'>" ;
										}
										?>
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
}
?>