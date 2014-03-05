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

if (isActionAccessible($guid, $connection2, "/modules/Activities/activities_view_register.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print "You do not have access to this action." ;
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
		print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Activities/activities_view.php'>View Activities</a> > </div><div class='trailEnd'>Activity Registration</div>" ;
		print "</div>" ;
		
		if (isActionAccessible($guid, $connection2,"/modules/Activities/activities_view_register")==FALSE) {
			//Acess denied
			print "<div class='error'>" ;
				print "You do not have access to this action." ;
			print "</div>" ;
		}
		else {
			//Get current role category
			$roleCategory=getRoleCategory($_SESSION[$guid]["gibbonRoleIDCurrent"], $connection2) ;
		
			//Check access controls
			$access=getSettingByScope($connection2, "Activities", "access") ;
			
			$gibbonPersonID=$_GET["gibbonPersonID"] ;
			
			if ($access!="Register") {
				print "<div class='error'>" ;
				print "Registration is closed, or you do not have permission to register." ;
				print "</div>" ;
			}
			else {
				//Check if school year specified
				$gibbonActivityID=$_GET["gibbonActivityID"];
				if ($gibbonActivityID=="Y") {
					print "<div class='error'>" ;
						print "You have not specified one or more required parameters." ;
					print "</div>" ;
				}
				else {
					$mode=$_GET["mode"] ;
					
					if ($_GET["search"]!="" OR $gibbonPersonID!="") {
						print "<div class='linkTop'>" ;
							print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Activities/activities_view.php&gibbonPersonID=$gibbonPersonID&search=" . $_GET["search"] . "'>Back to Search Results</a>" ;
						print "</div>" ;
					}

					
					//Check Access
					$continue=FALSE ;
					//Student
					if ($roleCategory=="Student" AND $highestAction=="View Activities_studentRegister") {
						try {
							$dataStudent=array("gibbonPersonID"=>$gibbonPersonID, "gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
							$sqlStudent="SELECT * FROM gibbonStudentEnrolment WHERE gibbonPersonID=:gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID" ;
							$resultStudent=$connection2->prepare($sqlStudent);
							$resultStudent->execute($dataStudent);
						}
						catch(PDOException $e) { 
							print "<div class='error'>" . $e->getMessage() . "</div>" ; 
						}
						if ($resultStudent->rowCount()==1) {
							$rowStudent=$resultStudent->fetch() ;
							$gibbonYearGroupID=$rowStudent["gibbonYearGroupID"] ;
							if ($gibbonYearGroupID!="") {
								$continue=TRUE ;
								$and=" AND gibbonYearGroupIDList LIKE '%$gibbonYearGroupID%'" ;
							}
						}
					}
					//Parent
					else if ($roleCategory=="Parent" AND $highestAction=="View Activities_studentRegisterByParent" AND $gibbonPersonID!="") {
						try {
							$data=array("gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
							$sql="SELECT * FROM gibbonFamilyAdult WHERE gibbonPersonID=:gibbonPersonID AND childDataAccess='Y'" ;
							$result=$connection2->prepare($sql);
							$result->execute($data);
						}
						catch(PDOException $e) { 
							print "<div class='error'>" . $e->getMessage() . "</div>" ; 
						}
			
						if ($result->rowCount()<1) {
							print "<div class='error'>" ;
							print "Access denied." ;
							print "</div>" ;
						}
						else {
							$countChild=0 ;
							while ($row=$result->fetch()) {
								try {
									$dataChild=array("gibbonFamilyID"=>$row["gibbonFamilyID"], "gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonPersonID"=>$gibbonPersonID); 
									$sqlChild="SELECT * FROM gibbonFamilyChild JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE gibbonFamilyID=:gibbonFamilyID AND gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPerson.gibbonPersonID=:gibbonPersonID ORDER BY surname, preferredName " ;
									$resultChild=$connection2->prepare($sqlChild);
									$resultChild->execute($dataChild);
								}
								catch(PDOException $e) { 
									print "<div class='error'>" . $e->getMessage() . "</div>" ; 
								}
								while ($rowChild=$resultChild->fetch()) {
									$countChild++ ;
									$gibbonYearGroupID=$rowChild["gibbonYearGroupID"] ;
								}
							}
							
							if ($countChild>0) {
								if ($gibbonYearGroupID!="") {
									$continue=TRUE ;
									$and=" AND gibbonYearGroupIDList LIKE '%$gibbonYearGroupID%'" ;
								}
							}
						}
					}
					
					
					if ($mode=="register") {
						if ($continue==FALSE) {
							print "<div class='error'>" ;
							print "You cannot register for the specified activity." ;
							print "</div>" ;
						}
						else {
							$today=date("Y-m-d") ;
							
							//Should we show date as term or date?
							$dateType=getSettingByScope($connection2, 'Activities', 'dateType') ;
							if ($dateType=="Term" ) {
								$maxPerTerm=getSettingByScope($connection2, 'Activities', 'maxPerTerm') ;
							}
							
							try {
								if ($dateType!="Date") {
									$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonActivityID"=>$gibbonActivityID); 
									$sql="SELECT * FROM gibbonActivity WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND active='Y' AND NOT gibbonSchoolYearTermIDList='' AND gibbonActivityID=:gibbonActivityID $and" ; 
								}
								else {
									$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonActivityID"=>$gibbonActivityID, "listingStart"=>$today, "listingEnd"=>$today); 
									$sql="SELECT * FROM gibbonActivity WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND active='Y' AND listingStart<=:listingStart AND listingEnd>=:listingEnd AND gibbonActivityID=:gibbonActivityID $and" ; 
								}
								$result=$connection2->prepare($sql);
								$result->execute($data);
							}
							catch(PDOException $e) { 
								print "<div class='error'>" . $e->getMessage() . "</div>" ; 
							}
							
							if ($result->rowCount()!=1) {
								print "<div class='error'>" ;
									print "The selected activity does not exist, is in a previous school year, or you do not have access to it." ;
								print "</div>" ;
							}
							else {
								$row=$result->fetch() ;
								
								//Check for existing registration
								try {
									$dataReg=array("gibbonActivityID"=>$gibbonActivityID, "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
									$sqlReg="SELECT * FROM gibbonActivityStudent WHERE gibbonActivityID=:gibbonActivityID AND gibbonPersonID=:gibbonPersonID" ;
									$resultReg=$connection2->prepare($sqlReg);
									$resultReg->execute($dataReg);
								}
								catch(PDOException $e) { 
									print "<div class='error'>" . $e->getMessage() . "</div>" ; 
								}
								
								if ($resultReg->rowCount()>0) {
									print "<div class='error'>" ;
										print "You are already registered for this activity and so cannot register again." ;
									print "</div>" ;
								}
								else {
									if (isset($_GET["updateReturn"])) { $updateReturn=$_GET["updateReturn"] ; } else { $updateReturn="" ; }
									$updateReturnMessage ="" ;
									$class="error" ;
									if (!($updateReturn=="")) {
										if ($updateReturn=="fail0") {
											$updateReturnMessage ="Registration failed because you do not have access to this action." ;	
										}
										else if ($updateReturn=="fail2") {
											$updateReturnMessage ="Registration failed due to a database error." ;	
										}
										else if ($updateReturn=="fail3") {
											$updateReturnMessage ="Registration failed because your inputs were invalid." ;	
										}
										else if ($updateReturn=="fail4") {
											$updateReturnMessage ="Registration failed some values need to be unique but were not." ;	
										}
										else if ($updateReturn=="fail5") {
											$updateReturnMessage ="Registration failed because you are already registered in this activity." ;	
										}
										print "<div class='$class'>" ;
											print $updateReturnMessage;
										print "</div>" ;
									} 
									
									//Check registration limit...
									$proceed=true ;
									if ($dateType=="Term" AND $maxPerTerm>0) {
										$termsList=explode(",", $row["gibbonSchoolYearTermIDList"]) ;
										foreach ($termsList as $term) {
											
											try {
												$dataActivityCount=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonPersonID"=>$gibbonPersonID, "gibbonSchoolYearTermIDList"=>"%" . $term . "%"); 
												$sqlActivityCount="SELECT * FROM gibbonActivityStudent JOIN gibbonActivity ON (gibbonActivityStudent.gibbonActivityID=gibbonActivity.gibbonActivityID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonID=:gibbonPersonID AND gibbonSchoolYearTermIDList LIKE :gibbonSchoolYearTermIDList AND NOT status='Not Accepted'" ;
												$resultActivityCount=$connection2->prepare($sqlActivityCount);
												$resultActivityCount->execute($dataActivityCount);
											}
											catch(PDOException $e) { 
												print "<div class='error'>" . $e->getMessage() . "</div>" ; 
											}
											if ($resultActivityCount->rowCount()>=$maxPerTerm) {
												$proceed=false ;
											}
										}
									}
									
									if ($proceed==false) {
										print "<div class='error'>" ;
											print "You have subscribed for the maximum number of activities in a term, and so cannot register for this activity." ;
										print "</div>" ;
									}
									else {
									?>
										<p>
											<?
											if (getSettingByScope($connection2, "Activities", "enrolmentType")=="Selection") {
												print "After you press the Register button below, your application will be considered by a member of staff who will decide whether or not there is space for you in this program." ;
											}
											else {
												print "If there is space on this program you will be accepted immediately upon pressing the Register button below. If there is not, then you will be placed on a waiting list." ;
											}
											?>
										</p>
										<form method="post" action="<? print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/activities_view_registerProcess.php?search=" . $_GET["search"] ?>">
											<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
												<tr>
													<td> 
														<b>Activity</b><br/>
													</td>
													<td class="right">
														<input readonly name="name" id="name" maxlength=40 value="<? print $row["name"] ?>" type="text" style="width: 300px">
													</td>
												</tr>
												<?
												if ($dateType!="Date") {
													?>
													<tr>
														<td> 
															<b>Terms</b><br/>
														</td>
														<td class="right">
															<?
															$terms=getTerms($connection2, $_SESSION[$guid]["gibbonSchoolYearID"]) ;
															$termList="" ;
															for ($i=0; $i<count($terms); $i=$i+2) {
																if (is_numeric(strpos($row["gibbonSchoolYearTermIDList"], $terms[$i]))) {
																	$termList.=$terms[($i+1)] . ", " ;
																}
															}
															?>
															<input readonly name="terms" id="terms" maxlength=10 value="<? print substr($termList, 0, -2) ?>" type="text" style="width: 300px">
														</td>
													</tr>
													<?
												}
												else {
													?>
													<tr>
														<td> 
															<b>Program Start Date</b><br/>
														</td>
														<td class="right">
															<input readonly name="programStart" id="programStart" maxlength=10 value="<? print dateConvertBack($guid, $row["programStart"]) ?>" type="text" style="width: 300px">
														</td>
													</tr>
													<tr>
														<td> 
															<b>Program End Date</b><br/>
														</td>
														<td class="right">
															<input readonly name="programEnd" id="programEnd" maxlength=10 value="<? print dateConvertBack($guid, $row["programEnd"]) ?>" type="text" style="width: 300px">
														</td>
													</tr>
													<?
												}
												?>
												<tr>
													<td> 
														<b>Cost</b><br/>
														<span style="font-size: 90%"><i>For entire programme<br/></i></span>
													</td>
													<td class="right">
														<?
															if (getSettingByScope($connection2, "Activities", "payment")!="None" AND getSettingByScope($connection2, "Activities", "payment")!="Single") {
																?>
																<input readonly name="payment" id="payment" maxlength=7 value="$<? print $row["payment"] ?>" type="text" style="width: 300px">
																<?
															}
														?>
														
													</td>
												</tr>
												
												<?
												if (getSettingByScope($connection2, "Activities", "backupChoice")=="Y") {
													?>
													<tr>
														<td> 
															<b>Backup Choice * </b><br/>
															<span style="font-size: 90%"><i>Incase <? print $row["name"] ?> is full.<br/></i></span>
														</td>
														<td class="right">
															<select name="gibbonActivityIDBackup" id="gibbonActivityIDBackup" style="width: 302px">
																<?
																print "<option value='Please select...'>Please select...</option>" ;
																
																try {
																	if ($dateType!="Date") {
																		$dataSelect=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonPersonID"=>$gibbonPersonID, "gibbonActivityID"=>$gibbonActivityID); 
																		$sqlSelect="SELECT DISTINCT gibbonActivity.* FROM gibbonActivity JOIN gibbonStudentEnrolment ON (gibbonActivity.gibbonYearGroupIDList LIKE concat( '%', gibbonStudentEnrolment.gibbonYearGroupID, '%' )) WHERE gibbonActivity.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonID=:gibbonPersonID AND NOT gibbonActivityID=:gibbonActivityID AND NOT gibbonSchoolYearTermIDList='' AND active='Y' $and ORDER BY name" ; 
																	}
																	else {
																		$dataSelect=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonPersonID"=>$gibbonPersonID, "gibbonActivityID"=>$gibbonActivityID, "listingStart"=>$today, "listingEnd"=>$today); 
																		$sqlSelect="SELECT DISTINCT gibbonActivity.* FROM gibbonActivity JOIN gibbonStudentEnrolment ON (gibbonActivity.gibbonYearGroupIDList LIKE concat( '%', gibbonStudentEnrolment.gibbonYearGroupID, '%' )) WHERE gibbonActivity.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonID=:gibbonPersonID AND NOT gibbonActivityID=:gibbonActivityID AND listingStart<=:listingStart AND listingEnd>=:listingEnd AND active='Y' $and ORDER BY name" ;
																	}
																	$resultSelect=$connection2->prepare($sqlSelect);
																	$resultSelect->execute($dataSelect);
																}
																catch(PDOException $e) { }
																
																while ($rowSelect=$resultSelect->fetch()) {
																	print "<option value='" . $rowSelect["gibbonActivityID"] . "'>" . htmlPrep($rowSelect["name"]) . "</option>" ;
																}
																?>				
															</select>
															<script type="text/javascript">
																var gibbonActivityIDBackup=new LiveValidation('gibbonActivityIDBackup');
																gibbonActivityIDBackup.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "Select something!"});
															 </script>
														</td>
													</tr>
													<?
												}
												?>
												<tr>
													<td>
														<span style="font-size: 90%"><i>* <? print _("denotes a required field") ; ?></i></span>
													</td>
													<td class="right">
														<input type="hidden" name="mode" value="<? print $mode ?>">
														<input type="hidden" name="gibbonPersonID" value="<? print $gibbonPersonID ?>">
														<input type="hidden" name="gibbonActivityID" value="<? print $gibbonActivityID ?>">
														<input type="hidden" name="address" value="<? print $_SESSION[$guid]["address"] ?>">
														<input style='width: 75px' type="submit" value="Register">
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
					else if ($mode="unregister") {
						if ($continue==FALSE) {
							print "<div class='error'>" ;
							print "You cannot register for the specified activity." ;
							print "</div>" ;
						}
						else {
							$today=date("Y-m-d") ;
							
							//Should we show date as term or date?
							$dateType=getSettingByScope( $connection2, "Activities", "dateType" ) ; 
							
							try {
								if ($dateType!="Date") {
									$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonPersonID"=>$gibbonPersonID, "gibbonActivityID"=>$gibbonActivityID); 
									$sql="SELECT DISTINCT gibbonActivity.* FROM gibbonActivity JOIN gibbonStudentEnrolment ON (gibbonActivity.gibbonYearGroupIDList LIKE concat( '%', gibbonStudentEnrolment.gibbonYearGroupID, '%' )) WHERE gibbonActivity.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonID=:gibbonPersonID AND gibbonActivityID=:gibbonActivityID AND NOT gibbonSchoolYearTermIDList='' AND active='Y' $and" ;
								}
								else {
									$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonPersonID"=>$gibbonPersonID, "gibbonActivityID"=>$gibbonActivityID, "listingStart"=>$today, "listingEnd"=>$today); 
									$sql="SELECT DISTINCT gibbonActivity.* FROM gibbonActivity JOIN gibbonStudentEnrolment ON (gibbonActivity.gibbonYearGroupIDList LIKE concat( '%', gibbonStudentEnrolment.gibbonYearGroupID, '%' )) WHERE gibbonActivity.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonID=:gibbonPersonID AND gibbonActivityID=:gibbonActivityID AND listingStart<=:listingStart AND listingEnd>=:listingEnd AND active='Y' $and" ;
								}
								$result=$connection2->prepare($sql);
								$result->execute($data);
							}
							catch(PDOException $e) { 
								print "<div class='error'>" . $e->getMessage() . "</div>" ; 
							}
									
							if ($result->rowCount()!=1) {
								print "<div class='error'>" ;
									print "The selected activity does not exist, is in a previous school year, or you do not have access to it." ;
								print "</div>" ;
							}
							else {
								$row=$result->fetch() ;
								
								//Check for existing registration
								try {
									$dataReg=array("gibbonActivityID"=>$gibbonActivityID, "gibbonPersonID"=>$gibbonPersonID); 
									$sqlReg="SELECT * FROM gibbonActivityStudent WHERE gibbonActivityID=:gibbonActivityID AND gibbonPersonID=:gibbonPersonID" ;
									$resultReg=$connection2->prepare($sqlReg);
									$resultReg->execute($dataReg);
								}
								catch(PDOException $e) { 
									print "<div class='error'>" . $e->getMessage() . "</div>" ; 
								}
								
								if ($resultReg->rowCount()<1) {
									print "<div class='error'>" ;
										print "You are not currently registered for this activity and so cannot unregister." ;
									print "</div>" ;
								}
								else {
							
									if (isset($_GET["updateReturn"])) { $updateReturn=$_GET["updateReturn"] ; } else { $updateReturn="" ; }
									$updateReturnMessage ="" ;
									$class="error" ;
									if (!($updateReturn=="")) {
										if ($updateReturn=="fail0") {
											$updateReturnMessage ="Unregistration failed because you do not have access to this action." ;	
										}
										else if ($updateReturn=="fail2") {
											$updateReturnMessage ="Unregistration failed due to a database error." ;	
										}
										else if ($updateReturn=="fail5") {
											$updateReturnMessage ="Unregistration failed because you are not already registered in this activity." ;	
										}
										print "<div class='$class'>" ;
											print $updateReturnMessage;
										print "</div>" ;
									} 
									
									?>
									<form method="post" action="<? print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/activities_view_registerProcess.php?search=" . $_GET["search"] ?>">
										<table cellspacing='0' style="width: 100%">	
											<tr>
												<td> 
													<b>Are you sure you want to unregister from activity "<? print $row["name"] ?>"? If you try to reregister later you may lose a space already assigned to you.</b><br/>
												</td>
											</tr>
											<tr>
												<td class="right" colspan=2>
													<input type="hidden" name="mode" value="<? print $mode ?>">
													<input type="hidden" name="gibbonPersonID" value="<? print $gibbonPersonID ?>">
													<input type="hidden" name="gibbonActivityID" value="<? print $gibbonActivityID ?>">
													<input type="hidden" name="address" value="<? print $_SESSION[$guid]["address"] ?>">
													<input style='width: 75px' type="submit" value="Unregister">
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
		}
	}
}
?>