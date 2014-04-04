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
		print "The highest grouped action cannot be determined." ;
		print "</div>" ;
	}
	else {
		//Proceed!
		print "<div class='trail'>" ;
		print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > </div><div class='trailEnd'>Update Medical Data</div>" ;
		print "</div>" ;
		
		if ($highestAction=="Update Medical Data_any") {
			print "<p>" ;
			print "This page allows a user to request selected medical data updates for any student." ;
			print "</p>" ;
		}
		else {
			print "<p>" ;
			print "This page allows any adult with data access permission to request medical data updates for any member of their family." ;
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
				$updateReturnMessage="Update succeeded, although some fields were not recorded." ;	
			}
			else if ($updateReturn=="success0") {
				$updateReturnMessage="Your request was completed successfully. An administrator will process your request as soon as possible. <u>You will not see the updated data in the system until it has been processed and approved.</u>" ; 
				if ($_SESSION[$guid]["organisationDBAEmail"]!="" AND $_SESSION[$guid]["organisationDBAName"]!="") {
					$updateReturnMessage=$updateReturnMessage . " Please contact <a href='mailto:" . $_SESSION[$guid]["organisationDBAEmail"] . "'>" . $_SESSION[$guid]["organisationDBAName"] . "</a> if you have any questions." ;	
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
		
		<form method="get" action="<? print $_SESSION[$guid]["absoluteURL"]?>/index.php">
			<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
				<tr>
					<td> 
						<b>Person *</b><br/>
					</td>
					<td class="right">
						<select style="width: 302px" name="gibbonPersonID">
							<?
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
						<input type="hidden" name="q" value="/modules/<? print $_SESSION[$guid]["module"] ?>/data_medical.php">
						<input type="submit" value="<? print _("Submit") ; ?>">
					</td>
				</tr>
			</table>
		</form>
		<?
		
		if ($gibbonPersonID!="") {
			print "<h2>" ;
			print "Update Data" ;
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
					print "You do not have access to the specified person." ;
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
							print "This report cannot be displayed due to a database error." ;
						print "</div>" ;
					}
					else if ($resultForm->rowCount()==1) {
						$existing=TRUE ;
						$proceed=TRUE;
						if ($addReturn=="") {
							print "<div class='warning'>" ;
								print "You have already submitted a form, which is pending approval by an administrator. If you wish to make changes, please edited the data below, but remember your data will not appear in the system until it has been approved." ;
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
						<form method="post" action="<? print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/data_medicalProcess.php?gibbonPersonID=" . $gibbonPersonID ?>">
							<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
								<tr>
									<td> 
										<b>Blood Type</b><br/>
										<span style="font-size: 90%"><i></i></span>
									</td>
									<td class="right">
										<select style="width: 302px" name="bloodType">
											<option <? if ($rowForm["bloodType"]=="") {print "selected ";}?>value=""></option>
											<option <? if ($rowForm["bloodType"]=="O+") {print "selected ";}?>value="O+">O+</option>
											<option <? if ($rowForm["bloodType"]=="A+") {print "selected ";}?>value="A+">A+</option>
											<option <? if ($rowForm["bloodType"]=="B+") {print "selected ";}?>value="B+">B+</option>
											<option <? if ($rowForm["bloodType"]=="AB+") {print "selected ";}?>value="AB+">AB+</option>
											<option <? if ($rowForm["bloodType"]=="O-") {print "selected ";}?>value="O-">O-</option>
											<option <? if ($rowForm["bloodType"]=="A-") {print "selected ";}?>value="A-">A-</option>
											<option <? if ($rowForm["bloodType"]=="B-") {print "selected ";}?>value="B-">B-</option>
											<option <? if ($rowForm["bloodType"]=="AB-") {print "selected ";}?>value="AB-">AB-</option>
										</select>
									</td>
								</tr>
								<tr>
									<td> 
										<b>Long-Term Medication?</b><br/>
										<span style="font-size: 90%"><i></i></span>
									</td>
									<td class="right">
										<select style="width: 302px" name="longTermMedication">
											<option <? if ($rowForm["longTermMedication"]=="") {print "selected ";}?>value=""></option>
											<option <? if ($rowForm["longTermMedication"]=="Y") {print "selected ";}?>value="Y">Y</option>
											<option <? if ($rowForm["longTermMedication"]=="N") {print "selected ";}?>value="N">N</option>
										</select>
									</td>
								</tr>
								<tr>
									<td> 
										<b>Medication Details</b><br/>
										<span style="font-size: 90%"><i>1000 character limit</i></span>
									</td>
									<td class="right">
										<textarea name="longTermMedicationDetails" id="longTermMedicationDetails" rowForms=8 style="width: 300px"><? print $rowForm["longTermMedicationDetails"] ?></textarea>
										<script type="text/javascript">
											var longTermMedicationDetails=new LiveValidation('longTermMedicationDetails');
											longTermMedicationDetails.add( Validate.Length, { maximum: 1000 } );
										 </script>
									</td>
								</tr>
								<tr>
									<td> 
										<b>Tetanus Within Last 10 Years?</b><br/>
										<span style="font-size: 90%"><i></i></span>
									</td>
									<td class="right">
										<select style="width: 302px" name="tetanusWithin10Years">
											<option <? if ($rowForm["tetanusWithin10Years"]=="") {print "selected ";}?>value=""></option>
											<option <? if ($rowForm["tetanusWithin10Years"]=="Y") {print "selected ";}?>value="Y">Y</option>
											<option <? if ($rowForm["tetanusWithin10Years"]=="N") {print "selected ";}?>value="N">N</option>
										</select>
									</td>
								</tr>
								
								<input name="gibbonPersonMedicalID" id="gibbonPersonMedicalID" value="<? print htmlPrep($rowForm["gibbonPersonMedicalID"]) ?>" type="hidden">
								
								<?
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
												<h3>Medical Condition <? print ($count+1) ?></h3>
											</td>
										</tr>
										<!--<tr>
											<td> 
												<b>Delete</b><br/>
												<span style="font-size: 90%"><i>Check box to delete</i></span>
											</td>
											<td class="right">
												<input type='checkbox' name='delete<? print $count ?>'>
											</td>
										</tr>-->		
										<tr>
											<td> 
												<b>Condition Name *</b><br/>
											</td>
											<td class="right">
												<select style="width: 302px" name="name<? print $count ?>" id="name<? print $count ?>">
													<?
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
															print "<option selected value='" . htmlPrep($rowSelect["name"]) . "'>" . htmlPrep($rowSelect["name"]) . "</option>" ;
														}
														 else {
															print "<option value='" . htmlPrep($rowSelect["name"]) . "'>" . htmlPrep($rowSelect["name"]) . "</option>" ;
														}
													}
													?>				
												</select>
												<script type="text/javascript">
													var name<? print $count ?>=new LiveValidation('name<? print $count ?>');
													name<? print $count ?>.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "Select something!"});
												 </script>	
											</td>
										</tr>
										<tr>
											<td> 
												<b>Risk *</b><br/>
											</td>
											<td class="right">
												<select name="gibbonAlertLevelID<? print $count ?>" id="gibbonAlertLevelID<? print $count ?>" style="width: 302px">
													<option value='Please select...'>Please select...</option>
													<?
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
														print "<option $selected value='" . $rowSelect["gibbonAlertLevelID"] . "'>" . $rowSelect["name"] . "</option>" ; 
													}
													?>
												</select>
												<script type="text/javascript">
													var gibbonAlertLevelID<? print $count ?>=new LiveValidation('gibbonAlertLevelID<? print $count ?>');
													gibbonAlertLevelID<? print $count ?>.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "Select something!"});
												 </script>	
											</td>
										</tr>
										<tr>
											<td> 
												<b>Triggers</b><br/>
											</td>
											<td class="right">
												<input name="triggers<? print $count ?>" id="triggers<? print $count ?>" maxlength=255 value="<? print htmlPrep($rowCond["triggers"]) ?>" type="text" style="width: 300px">
											</td>
										</tr>
										<tr>
											<td> 
												<b>Reaction</b><br/>
											</td>
											<td class="right">
												<input name="reaction<? print $count ?>" id="reaction<? print $count ?>" maxlength=255 value="<? print htmlPrep($rowCond["reaction"]) ?>" type="text" style="width: 300px">
											</td>
										</tr>
										<tr>
											<td> 
												<b>Response</b><br/>
											</td>
											<td class="right">
												<input name="response<? print $count ?>" id="response<? print $count ?>" maxlength=255 value="<? print htmlPrep($rowCond["response"]) ?>" type="text" style="width: 300px">
											</td>
										</tr>
										<tr>
											<td> 
												<b>Medication</b><br/>
											</td>
											<td class="right">
												<input name="medication<? print $count ?>" id="medication<? print $count ?>" maxlength=255 value="<? print htmlPrep($rowCond["medication"]) ?>" type="text" style="width: 300px">
											</td>
										</tr>
										<tr>
											<td> 
												<b>Last Episode Date</b><br/>
												<span style="font-size: 90%"><i><? print $_SESSION[$guid]["i18n"]["dateFormat"]  ?></i></span>
											</td>
											<td class="right">
												<input name="lastEpisode<? print $count ?>" id="lastEpisode<? print $count ?>" maxlength=10 value="<? print dateConvertBack($guid, $rowCond["lastEpisode"]) ?>" type="text" style="width: 300px">
												<script type="text/javascript">
													var lastEpisode<? print $count ?>=new LiveValidation('lastEpisode<? print $count ?>');
													lastEpisode<? print $count ?>.add( Validate.Format, {pattern: <? if ($_SESSION[$guid]["i18n"]["dateFormatRegEx"]=="") {  print "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i" ; } else { print $_SESSION[$guid]["i18n"]["dateFormatRegEx"] ; } ?>, failureMessage: "Use <? if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?>." } ); 
												 </script>
												 <script type="text/javascript">
													$(function() {
														$( "#lastEpisode<? print $count ?>" ).datepicker();
													});
												</script>
											</td>
										</tr>
										<tr>
											<td> 
												<b>Last Episode Treatment</b><br/>
											</td>
											<td class="right">
												<input name="lastEpisodeTreatment<? print $count ?>" id="lastEpisodeTreatment<? print $count ?>" maxlength=255 value="<? print htmlPrep($rowCond["lastEpisodeTreatment"]) ?>" type="text" style="width: 300px">
											</td>
										</tr>
										<tr>
											<td> 
												<b>Comment</b><br/>
												<span style="font-size: 90%"><i>1000 character limit</i></span>
											</td>
											<td class="right">
												<textarea name="comment<? print $count ?>" id="comment<? print $count ?>" rows=8 style="width: 300px"><? print $rowCond["comment"] ?></textarea>
												<script type="text/javascript">
													var comment<? print $count ?>=new LiveValidation('comment<? print $count ?>');
													comment<? print $count ?>.add( Validate.Length, { maximum: 1000 } );
												 </script>
											</td>
										</tr>
										<input name="gibbonPersonMedicalConditionID<? print $count ?>" id="gibbonPersonMedicalConditionID<? print $count ?>" value="<? print htmlPrep($rowCond["gibbonPersonMedicalConditionID"]) ?>" type="hidden">
										<input name="gibbonPersonMedicalConditionUpdateID<? print $count ?>" id="gibbonPersonMedicalConditionUpdateID<? print $count ?>" value="<? print htmlPrep($rowCond["gibbonPersonMedicalConditionUpdateID"]) ?>" type="hidden">
										<?
										$count++ ;
									}
									
									print "<input name='count' id='count' value='$count' type='hidden'>" ;
								}
								?>
								<tr class='break'>
									<td colspan=2> 
										<h3>Add Medical Condition</h3>
									</td>
								</tr>
								<tr>
									<td> 
										<b>Condition Name *</b><br/>
									</td>
									<td class="right">
										<select style="width: 302px" name="name" id="name">
											<?
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
													print "<option selected value='" . htmlPrep($rowSelect["name"]) . "'>" . htmlPrep($rowSelect["name"]) . "</option>" ;
												}
												 else {
													print "<option value='" . htmlPrep($rowSelect["name"]) . "'>" . htmlPrep($rowSelect["name"]) . "</option>" ;
												}
											}
											?>				
										</select>
									</td>
								</tr>
								<tr>
									<td> 
										<b>Risk *</b><br/>
									</td>
									<td class="right">
										<select name="gibbonAlertLevelID" id="gibbonAlertLevelID" style="width: 302px">
										<option value='Please select...'>Please select...</option>
										<?
										try {
											$dataSelect=array(); 
											$sqlSelect="SELECT * FROM gibbonAlertLevel ORDER BY sequenceNumber" ;
											$resultSelect=$connection2->prepare($sqlSelect);
											$resultSelect->execute($dataSelect);
										}
										catch(PDOException $e) { }
										
										while ($rowSelect=$resultSelect->fetch()) {
											print "<option value='" . $rowSelect["gibbonAlertLevelID"] . "'>" . $rowSelect["name"] . "</option>" ; 
										}
										?>
									</select>
									</td>
								</tr>
								<tr>
									<td> 
										<b>Triggers</b><br/>
									</td>
									<td class="right">
										<input name="triggers" id="triggers" maxlength=255 value="<? print htmlPrep($rowCond["triggers"]) ?>" type="text" style="width: 300px">
									</td>
								</tr>
								<tr>
									<td> 
										<b>Reaction</b><br/>
									</td>
									<td class="right">
										<input name="reaction" id="reaction" maxlength=255 value="<? print htmlPrep($rowCond["reaction"]) ?>" type="text" style="width: 300px">
									</td>
								</tr>
								<tr>
									<td> 
										<b>Response</b><br/>
									</td>
									<td class="right">
										<input name="response" id="response" maxlength=255 value="<? print htmlPrep($rowCond["response"]) ?>" type="text" style="width: 300px">
									</td>
								</tr>
								<tr>
									<td> 
										<b>Medication</b><br/>
									</td>
									<td class="right">
										<input name="medication" id="medication" maxlength=255 value="<? print htmlPrep($rowCond["medication"]) ?>" type="text" style="width: 300px">
									</td>
								</tr>
								<tr>
									<td> 
										<b>Last Episode Date</b><br/>
										<span style="font-size: 90%"><i><? print $_SESSION[$guid]["i18n"]["dateFormat"]  ?></i></span>
									</td>
									<td class="right">
										<input name="lastEpisode" id="lastEpisode" maxlength=10 value="<? print dateConvertBack($guid, $rowCond["lastEpisode"]) ?>" type="text" style="width: 300px">
										<script type="text/javascript">
											var lastEpisode=new LiveValidation('lastEpisode');
											lastEpisode.add( Validate.Format, {pattern: <? if ($_SESSION[$guid]["i18n"]["dateFormatRegEx"]=="") {  print "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i" ; } else { print $_SESSION[$guid]["i18n"]["dateFormatRegEx"] ; } ?>, failureMessage: "Use <? if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?>." } ); 
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
										<b>Last Episode Treatment</b><br/>
									</td>
									<td class="right">
										<input name="lastEpisodeTreatment" id="lastEpisodeTreatment" maxlength=255 value="<? print htmlPrep($rowCond["lastEpisodeTreatment"]) ?>" type="text" style="width: 300px">
									</td>
								</tr>
								<tr>
									<td> 
										<b>Comment</b><br/>
										<span style="font-size: 90%"><i>1000 character limit</i></span>
									</td>
									<td class="right">
										<textarea name="comment" id="comment" rows=8 style="width: 300px"><? print $rowCond["comment"] ?></textarea>
										<script type="text/javascript">
											var commentNew=new LiveValidation('commentNew');
											commentNew.add( Validate.Length, { maximum: 1000 } );
										 </script>
									</td>
								</tr>
								<tr>
									<td>
										<span style="font-size: 90%"><i>* <? print _("denotes a required field") ; ?></i></span>
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
}
?>