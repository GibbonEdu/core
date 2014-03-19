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


if (isActionAccessible($guid, $connection2, "/modules/User Admin/data_medical_edit.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/User Admin/data_medical.php'>Medical Data Updates</a> > </div><div class='trailEnd'>Edit Request</div>" ;
	print "</div>" ;
	
	//Check if school year specified
	$gibbonPersonMedicalUpdateID=$_GET["gibbonPersonMedicalUpdateID"];
	if ($gibbonPersonMedicalUpdateID=="Y") {
		print "<div class='error'>" ;
			print _("You have not specified one or more required parameters.") ;
		print "</div>" ;
	}
	else {
		try {
			$data=array("gibbonPersonMedicalUpdateID"=>$gibbonPersonMedicalUpdateID); 
			$sql="SELECT * FROM gibbonPersonMedicalUpdate WHERE gibbonPersonMedicalUpdateID=:gibbonPersonMedicalUpdateID" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
		}
		
		if ($result->rowCount()!=1) {
			print "<div class='error'>" ;
				print _("The specified record does not exist.") ;
			print "</div>" ;
		}
		else {
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
				else if ($updateReturn=="fail5") {
					$updateReturnMessage="Update succeeded, although some fields were not recorded." ;	
				}
				else if ($updateReturn=="success1") {
					$updateReturnMessage="Your request was completed successfully., but status could not be updated." ;	
					$class="success" ;
				}
				else if ($updateReturn=="success0") {
					$updateReturnMessage=_("Your request was completed successfully.") ;	
					$class="success" ;
				}
				print "<div class='$class'>" ;
					print $updateReturnMessage;
				print "</div>" ;
			} 

			//Let's go!
			$row=$result->fetch() ;
			
			$formExists=FALSE;
			$gibbonPersonID=$row["gibbonPersonID"] ;
			$formOK=true ;
			try {
				$data2=array("gibbonPersonID"=>$gibbonPersonID); 
				$sql2="SELECT * FROM gibbonPersonMedical WHERE gibbonPersonID=:gibbonPersonID" ;
				$result2=$connection2->prepare($sql2);
				$result2->execute($data2);
			}
			catch(PDOException $e) { 
				$formOK=false ;
				print "<div class='error'>" . $e->getMessage() . "</div>" ; 
			}
			
			if ($formOK==true) {
				if ($result2->rowCount()==1) {
					$formExists=TRUE ;
					$row2=$result2->fetch() ;
				}
				
				?>
				<form method="post" action="<? print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/data_medical_editProcess.php?gibbonPersonMedicalUpdateID=$gibbonPersonMedicalUpdateID" ?>">
					<?
							
					print "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>" ;
						print "<tr class='head'>" ;
							print "<th>" ;
								print "Field" ;
							print "</th>" ;
							print "<th>" ;
								print "Current Value" ;
							print "</th>" ;
							print "<th>" ;
								print "New Value" ;
							print "</th>" ;
							print "<th>" ;
								print "Accept" ;
							print "</th>" ;
						print "</tr>" ;
						print "<tr class='break'>" ;
							print "<td colspan=4> " ;
								print "<h3>Basic Information</h3>" ;
							print "</td>" ;
						print "</tr>" ;
						
						$rowNum="odd" ;
						$rowNum="even" ;
							
							
						//COLOR ROW BY STATUS!
						print "<tr class='odd'>" ;
							print "<td>" ;
								print "Blood Type" ;
							print "</td>" ;
							print "<td>" ;
								if (isset($row2)) {
									print $row2["bloodType"] ;
								}
							print "</td>" ;
							print "<td>" ;
								$style="" ;
								if (isset($row2)) {
									if ($row2["bloodType"]!=$row["bloodType"]) {
										$style="style='color: #ff0000'" ;
									}
								}
								print "<span $style>" ;
								print $row["bloodType"] ;
							print "</td>" ;
							print "<td>" ;
								if (isset($row2)) {
									if ($row2["bloodType"]!=$row["bloodType"]) { print "<input checked type='checkbox' name='bloodTypeOn'><input name='bloodType' type='hidden' value='" . htmlprep($row["bloodType"]) . "'>" ; }
								}
								else if ($row["bloodType"]!="") {
									print "<input checked type='checkbox' name='bloodTypeOn'><input name='bloodType' type='hidden' value='" . htmlprep($row["bloodType"]) . "'>" ;
								}
							print "</td>" ;
						print "</tr>" ;
						print "<tr class='even'>" ;
							print "<td>" ;
								print "Long Term Medication" ;
							print "</td>" ;
							print "<td>" ;
								if (isset($row2)) {
									print $row2["longTermMedication"] ;
								}
							print "</td>" ;
							print "<td>" ;
								$style="" ;
								if (isset($row2)) {
									if ($row2["longTermMedication"]!=$row["longTermMedication"]) {
										$style="style='color: #ff0000'" ;
									}
								}
								print "<span $style>" ;
								print $row["longTermMedication"] ;
							print "</td>" ;
							print "<td>" ;
								if (isset($row2)) {
									if ($row2["longTermMedication"]!=$row["longTermMedication"]) { print "<input checked type='checkbox' name='longTermMedicationOn'><input name='longTermMedication' type='hidden' value='" . htmlprep($row["longTermMedication"]) . "'>" ; }
								}
								else if ($row["longTermMedication"]!="") {
									print "<input checked type='checkbox' name='longTermMedicationOn'><input name='longTermMedication' type='hidden' value='" . htmlprep($row["longTermMedication"]) . "'>" ;
								}
							print "</td>" ;
						print "</tr>" ;
						print "<tr class='odd'>" ;
							print "<td>" ;
								print "Long Term Medication Details" ;
							print "</td>" ;
							print "<td>" ;
								if (isset($row2)) {
									print $row2["longTermMedicationDetails"] ;
								}
							print "</td>" ;
							print "<td>" ;
								$style="" ;
								if (isset($row2)) {
									if ($row2["longTermMedicationDetails"]!=$row["longTermMedicationDetails"]) {
										$style="style='color: #ff0000'" ;
									}
								}
								print "<span $style>" ;
								print $row["longTermMedicationDetails"] ;
							print "</td>" ;
							print "<td>" ;
								if (isset($row2)) {
									if ($row2["longTermMedicationDetails"]!=$row["longTermMedicationDetails"]) { print "<input checked type='checkbox' name='longTermMedicationDetailsOn'><input name='longTermMedicationDetails' type='hidden' value='" . htmlprep($row["longTermMedicationDetails"]) . "'>" ; }
								}
								else if ($row["longTermMedicationDetails"]!="") {
									print "<input checked type='checkbox' name='longTermMedicationDetailsOn'><input name='longTermMedicationDetails' type='hidden' value='" . htmlprep($row["longTermMedicationDetails"]) . "'>" ;
								}
							print "</td>" ;
						print "</tr>" ;
						print "<tr class='even'>" ;
							print "<td>" ;
								print "Tetanus Within 10 Years" ;
							print "</td>" ;
							print "<td>" ;
								if (isset($row2)) {
									print $row2["tetanusWithin10Years"] ;
								}
							print "</td>" ;
							print "<td>" ;
								$style="" ;
								if (isset($row2)) {
									if ($row2["tetanusWithin10Years"]!=$row["tetanusWithin10Years"]) {
										$style="style='color: #ff0000'" ;
									}
								}
								print "<span $style>" ;
								print $row["tetanusWithin10Years"] ;
							print "</td>" ;
							print "<td>" ;
								if (isset($row2)) {
									if ($row2["tetanusWithin10Years"]!=$row["tetanusWithin10Years"]) { print "<input checked type='checkbox' name='tetanusWithin10YearsOn'><input name='tetanusWithin10Years' type='hidden' value='" . htmlprep($row["tetanusWithin10Years"]) . "'>" ; }
								}
								else if ($row["tetanusWithin10Years"]!="") {
									print "<input checked type='checkbox' name='tetanusWithin10YearsOn'><input name='tetanusWithin10Years' type='hidden' value='" . htmlprep($row["tetanusWithin10Years"]) . "'>" ;
								}
							print "</td>" ;
						print "</tr>" ;
					
					//Get existing conditions
					try {
						$dataCond=array("gibbonPersonMedicalUpdateID"=>$gibbonPersonMedicalUpdateID); 
						$sqlCond="SELECT * FROM gibbonPersonMedicalConditionUpdate WHERE gibbonPersonMedicalUpdateID=:gibbonPersonMedicalUpdateID AND NOT gibbonPersonMedicalConditionID IS NULL ORDER BY gibbonPersonMedicalConditionUpdateID" ; 
						$resultCond=$connection2->prepare($sqlCond);
						$resultCond->execute($dataCond);
					}
					catch(PDOException $e) { 
						print "<div class='error'>" . $e->getMessage() . "</div>" ; 
					}
					
					$count=0 ;
					if ($resultCond->rowCount()>0) {
						while ($rowCond=$resultCond->fetch()) {
							$resultCond2=NULL ;
							try {
								$dataCond2=array("gibbonPersonMedicalConditionID"=> $rowCond["gibbonPersonMedicalConditionID"]); 
								$sqlCond2="SELECT * FROM gibbonPersonMedicalCondition WHERE gibbonPersonMedicalConditionID=:gibbonPersonMedicalConditionID" ;
								$resultCond2=$connection2->prepare($sqlCond2);
								$resultCond2->execute($dataCond2);
							}
							catch(PDOException $e) { 
								print "<div class='error'>" . $e->getMessage() . "</div>" ; 
							}
							if ($resultCond2->rowCount()==1) {
								$rowCond2=$resultCond2->fetch() ;
							}
				
							print "<tr class='break'>" ;
								print "<td colspan=4> " ;
									print "<h3>Existing Condition " . ($count+1) . "</h3>" ;
								print "</td>" ;
							print "</tr>" ;
							print "<tr class='odd'>" ;
								print "<td>" ;
									print "Name" ;
								print "</td>" ;
								print "<td>" ;
									print $rowCond2["name"] ;
								print "</td>" ;
								print "<td>" ;
									$style="" ;
									if ($rowCond2["name"]!=$rowCond["name"]) {
										$style="style='color: #ff0000'" ;
									}
									print "<span $style>" ;
									print $rowCond["name"] ;
								print "</td>" ;
								print "<td>" ;
									if ($rowCond2["name"]!=$rowCond["name"]) { print "<input checked type='checkbox' name='nameOn$count'><input name='name$count' type='hidden' value='" . htmlprep($rowCond["name"]) . "'>" ; }
								print "</td>" ;
							print "</tr>" ;
							print "<tr class='even'>" ;
								print "<td>" ;
									print "Risk" ;
								print "</td>" ;
								print "<td>" ;
									$alert=getAlert($connection2, $rowCond2["gibbonAlertLevelID"]) ;
									if ($alert!=FALSE) {
										$style="" ;
										if ($rowCond2["gibbonAlertLevelID"]!=$rowCond["gibbonAlertLevelID"]) {
											$style="style='color: #ff0000'" ;
										}
										print "<span $style>" ;
										print $alert["name"] ;
									}
								print "</td>" ;
								print "<td>" ;
									$alert=getAlert($connection2, $rowCond["gibbonAlertLevelID"]) ;
									if ($alert!=FALSE) {
										$style="" ;
										if ($rowCond2["gibbonAlertLevelID"]!=$rowCond["gibbonAlertLevelID"]) {
											$style="style='color: #ff0000'" ;
										}
										print "<span $style>" ;
										print $alert["name"] ;
									}
								print "</td>" ;
								print "<td>" ;
									if ($rowCond2["gibbonAlertLevelID"]!=$rowCond["gibbonAlertLevelID"]) { print "<input checked type='checkbox' name='gibbonAlertLevelIDOn$count'><input name='gibbonAlertLevelID$count' type='hidden' value='" . htmlprep($rowCond["gibbonAlertLevelID"]) . "'>" ; }
								print "</td>" ;
							print "</tr>" ;
							print "<tr class='odd'>" ;
								print "<td>" ;
									print "Triggers" ;
								print "</td>" ;
								print "<td>" ;
									print $rowCond2["triggers"] ;
								print "</td>" ;
								print "<td>" ;
									$style="" ;
									if ($rowCond2["triggers"]!=$rowCond["triggers"]) {
										$style="style='color: #ff0000'" ;
									}
									print "<span $style>" ;
									print $rowCond["triggers"] ;
								print "</td>" ;
								print "<td>" ;
									if ($rowCond2["triggers"]!=$rowCond["triggers"]) { print "<input checked type='checkbox' name='triggersOn$count'><input name='triggers$count' type='hidden' value='" . htmlprep($rowCond["triggers"]) . "'>" ; }
								print "</td>" ;
							print "</tr>" ;
							print "<tr class='even'>" ;
								print "<td>" ;
									print "Reaction" ;
								print "</td>" ;
								print "<td>" ;
									print $rowCond2["reaction"] ;
								print "</td>" ;
								print "<td>" ;
									$style="" ;
									if ($rowCond2["reaction"]!=$rowCond["reaction"]) {
										$style="style='color: #ff0000'" ;
									}
									print "<span $style>" ;
									print $rowCond["reaction"] ;
								print "</td>" ;
								print "<td>" ;
									if ($rowCond2["reaction"]!=$rowCond["reaction"]) { print "<input checked type='checkbox' name='reactionOn$count'><input name='reaction$count' type='hidden' value='" . htmlprep($rowCond["reaction"]) . "'>" ; }
								print "</td>" ;
							print "</tr>" ;
							print "<tr class='odd'>" ;
								print "<td>" ;
									print "Response" ;
								print "</td>" ;
								print "<td>" ;
									print $rowCond2["response"] ;
								print "</td>" ;
								print "<td>" ;
									$style="" ;
									if ($rowCond2["response"]!=$rowCond["response"]) {
										$style="style='color: #ff0000'" ;
									}
									print "<span $style>" ;
									print $rowCond["response"] ;
								print "</td>" ;
								print "<td>" ;
									if ($rowCond2["response"]!=$rowCond["response"]) { print "<input checked type='checkbox' name='responseOn$count'><input name='response$count' type='hidden' value='" . htmlprep($rowCond["response"]) . "'>" ; }
								print "</td>" ;
							print "</tr>" ;
							
							print "<tr class='even'>" ;
								print "<td>" ;
									print "Medication" ;
								print "</td>" ;
								print "<td>" ;
									print $rowCond2["medication"] ;
								print "</td>" ;
								print "<td>" ;
									$style="" ;
									if ($rowCond2["medication"]!=$rowCond["medication"]) {
										$style="style='color: #ff0000'" ;
									}
									print "<span $style>" ;
									print $rowCond["medication"] ;
								print "</td>" ;
								print "<td>" ;
									if ($rowCond2["medication"]!=$rowCond["medication"]) { print "<input checked type='checkbox' name='medicationOn$count'><input name='medication$count' type='hidden' value='" . htmlprep($rowCond["medication"]) . "'>" ; }
								print "</td>" ;
							print "</tr>" ;
							print "<tr class='odd'>" ;
								print "<td>" ;
									print "Last Episode" ;
								print "</td>" ;
								print "<td>" ;
									print dateConvertBack($guid, $rowCond2["lastEpisode"]) ;
								print "</td>" ;
								print "<td>" ;
									$style="" ;
									if ($rowCond2["lastEpisode"]!=$rowCond["lastEpisode"]) {
										$style="style='color: #ff0000'" ;
									}
									print "<span $style>" ;
									print dateConvertBack($guid, $rowCond["lastEpisode"]) ;
								print "</td>" ;
								print "<td>" ;
									if ($rowCond2["lastEpisode"]!=$rowCond["lastEpisode"]) { print "<input checked type='checkbox' name='lastEpisodeOn$count'><input name='lastEpisode$count' type='hidden' value='" . htmlprep($rowCond["lastEpisode"]) . "'>" ; }
								print "</td>" ;
							print "</tr>" ;
							print "<tr class='even'>" ;
								print "<td>" ;
									print "Last Episode Treatment" ;
								print "</td>" ;
								print "<td>" ;
									print $rowCond2["lastEpisodeTreatment"] ;
								print "</td>" ;
								print "<td>" ;
									$style="" ;
									if ($rowCond2["lastEpisodeTreatment"]!=$rowCond["lastEpisodeTreatment"]) {
										$style="style='color: #ff0000'" ;
									}
									print "<span $style>" ;
									print $rowCond["lastEpisodeTreatment"] ;
								print "</td>" ;
								print "<td>" ;
									if ($rowCond2["lastEpisodeTreatment"]!=$rowCond["lastEpisodeTreatment"]) { print "<input checked type='checkbox' name='lastEpisodeTreatmentOn$count'><input name='lastEpisodeTreatment$count' type='hidden' value='" . htmlprep($rowCond["lastEpisodeTreatment"]) . "'>" ; }
								print "</td>" ;
							print "</tr>" ;
							print "<tr class='odd'>" ;
								print "<td>" ;
									print "Comment" ;
								print "</td>" ;
								print "<td>" ;
									print $rowCond2["comment"] ;
								print "</td>" ;
								print "<td>" ;
									$style="" ;
									if ($rowCond2["comment"]!=$rowCond["comment"]) {
										$style="style='color: #ff0000'" ;
									}
									print "<span $style>" ;
									print $rowCond["comment"] ;
								print "</td>" ;
								print "<td>" ;
									if ($rowCond2["comment"]!=$rowCond["comment"]) { print "<input checked type='checkbox' name='commentOn$count'><input name='comment$count' type='hidden' value='" . htmlprep($rowCond["comment"]) . "'>" ; }
								print "</td>" ;
							print "</tr>" ;
							
							print "<input name='gibbonPersonMedicalConditionID$count' id='gibbonPersonMedicalConditionID$count' type='hidden' value='" . htmlprep($rowCond["gibbonPersonMedicalConditionID"]) . "'>" ;
							
							$count++ ;
						}
					}
					
					print "<input name='count' id='count' value='$count' type='hidden'>" ;
					
					//Get new conditions
					$count2=0 ;
					try {
						$dataCond=array("gibbonPersonMedicalUpdateID"=>$gibbonPersonMedicalUpdateID); 
						$sqlCond="SELECT * FROM gibbonPersonMedicalConditionUpdate WHERE gibbonPersonMedicalUpdateID=:gibbonPersonMedicalUpdateID AND gibbonPersonMedicalConditionID IS NULL ORDER BY name" ; 
						$resultCond=$connection2->prepare($sqlCond);
						$resultCond->execute($dataCond);
					}
					catch(PDOException $e) { 
						print "<div class='error'>" . $e->getMessage() . "</div>" ; 
					}
					
					if ($resultCond->rowCount()>0) {
						while ($rowCond=$resultCond->fetch()) {
							$count2++ ;
							$resultCond2=NULL ;
							$rowCond2=NULL ;
							print "<tr class='break'>" ;
								print "<td colspan=4> " ;
									print "<h3>New Condition $count2</h3>" ;
								print "</td>" ;
							print "</tr>" ;
							print "<tr class='odd'>" ;
								print "<td>" ;
									print "Name" ;
								print "</td>" ;
								print "<td>" ;
									print $rowCond2["name"] ;
								print "</td>" ;
								print "<td>" ;
									$style="" ;
									if ($rowCond2["name"]!=$rowCond["name"]) {
										$style="style='color: #ff0000'" ;
									}
									print "<span $style>" ;
									print $rowCond["name"] ;
								print "</td>" ;
								print "<td>" ;
									if ($rowCond2["name"]!=$rowCond["name"]) { print "<input checked type='checkbox' name='nameOn" . ($count+$count2) . "'><input name='name" . ($count+$count2) . "' type='hidden' value='" . htmlprep($rowCond["name"]) . "'>" ; }
								print "</td>" ;
							print "</tr>" ;
							print "<tr class='even'>" ;
								print "<td>" ;
									print "Risk" ;
								print "</td>" ;
								print "<td>" ;
									print $rowCond2["gibbonAlertLevelID"] ;
								print "</td>" ;
								print "<td>" ;
									$alert=getAlert($connection2, $rowCond["gibbonAlertLevelID"]) ;
									if ($alert!=FALSE) {
										$style="" ;
										if ($rowCond2["gibbonAlertLevelID"]!=$rowCond["gibbonAlertLevelID"]) {
											$style="style='color: #ff0000'" ;
										}
										print "<span $style>" ;
										print $alert["name"] ;
									}
								print "</td>" ;
								print "<td>" ;
									if ($rowCond2["gibbonAlertLevelID"]!=$rowCond["gibbonAlertLevelID"]) { print "<input checked type='checkbox' name='gibbonAlertLevelIDOn" . ($count+$count2) . "'><input name='gibbonAlertLevelID" . ($count+$count2) . "' type='hidden' value='" . htmlprep($rowCond["gibbonAlertLevelID"]) . "'>" ; }
								print "</td>" ;
							print "</tr>" ;
							print "<tr class='odd'>" ;
								print "<td>" ;
									print "Triggers" ;
								print "</td>" ;
								print "<td>" ;
									print $rowCond2["triggers"] ;
								print "</td>" ;
								print "<td>" ;
									$style="" ;
									if ($rowCond2["triggers"]!=$rowCond["triggers"]) {
										$style="style='color: #ff0000'" ;
									}
									print "<span $style>" ;
									print $rowCond["triggers"] ;
								print "</td>" ;
								print "<td>" ;
									if ($rowCond2["triggers"]!=$rowCond["triggers"]) { print "<input checked type='checkbox' name='triggersOn" . ($count+$count2) . "'><input name='triggers" . ($count+$count2) . "' type='hidden' value='" . htmlprep($rowCond["triggers"]) . "'>" ; }
								print "</td>" ;
							print "</tr>" ;
							print "<tr class='even'>" ;
								print "<td>" ;
									print "Reaction" ;
								print "</td>" ;
								print "<td>" ;
									print $rowCond2["reaction"] ;
								print "</td>" ;
								print "<td>" ;
									$style="" ;
									if ($rowCond2["reaction"]!=$rowCond["reaction"]) {
										$style="style='color: #ff0000'" ;
									}
									print "<span $style>" ;
									print $rowCond["reaction"] ;
								print "</td>" ;
								print "<td>" ;
									if ($rowCond2["reaction"]!=$rowCond["reaction"]) { print "<input checked type='checkbox' name='reactionOn" . ($count+$count2) . "'><input name='reaction" . ($count+$count2) . "' type='hidden' value='" . htmlprep($rowCond["reaction"]) . "'>" ; }
								print "</td>" ;
							print "</tr>" ;
							print "<tr class='odd'>" ;
								print "<td>" ;
									print "Response" ;
								print "</td>" ;
								print "<td>" ;
									print $rowCond2["response"] ;
								print "</td>" ;
								print "<td>" ;
									$style="" ;
									if ($rowCond2["response"]!=$rowCond["response"]) {
										$style="style='color: #ff0000'" ;
									}
									print "<span $style>" ;
									print $rowCond["response"] ;
								print "</td>" ;
								print "<td>" ;
									if ($rowCond2["response"]!=$rowCond["response"]) { print "<input checked type='checkbox' name='responseOn" . ($count+$count2) . "'><input name='response" . ($count+$count2) . "' type='hidden' value='" . htmlprep($rowCond["response"]) . "'>" ; }
								print "</td>" ;
							print "</tr>" ;
							
							print "<tr class='even'>" ;
								print "<td>" ;
									print "Medication" ;
								print "</td>" ;
								print "<td>" ;
									print $rowCond2["medication"] ;
								print "</td>" ;
								print "<td>" ;
									$style="" ;
									if ($rowCond2["medication"]!=$rowCond["medication"]) {
										$style="style='color: #ff0000'" ;
									}
									print "<span $style>" ;
									print $rowCond["medication"] ;
								print "</td>" ;
								print "<td>" ;
									if ($rowCond2["medication"]!=$rowCond["medication"]) { print "<input checked type='checkbox' name='medicationOn" . ($count+$count2) . "'><input name='medication" . ($count+$count2) . "' type='hidden' value='" . htmlprep($rowCond["medication"]) . "'>" ; }
								print "</td>" ;
							print "</tr>" ;
							print "<tr class='odd'>" ;
								print "<td>" ;
									print "Last Episode" ;
								print "</td>" ;
								print "<td>" ;
									print dateConvertBack($guid, $rowCond2["lastEpisode"]) ;
								print "</td>" ;
								print "<td>" ;
									$style="" ;
									if ($rowCond2["lastEpisode"]!=$rowCond["lastEpisode"]) {
										$style="style='color: #ff0000'" ;
									}
									print "<span $style>" ;
									print dateConvertBack($guid, $rowCond["lastEpisode"]) ;
								print "</td>" ;
								print "<td>" ;
									if ($rowCond2["lastEpisode"]!=$rowCond["lastEpisode"]) { print "<input checked type='checkbox' name='lastEpisodeOn" . ($count+$count2) . "'><input name='lastEpisode" . ($count+$count2) . "' type='hidden' value='" . htmlprep($rowCond["lastEpisode"]) . "'>" ; }
								print "</td>" ;
							print "</tr>" ;
							print "<tr class='even'>" ;
								print "<td>" ;
									print "Last Episode Treatment" ;
								print "</td>" ;
								print "<td>" ;
									print $rowCond2["lastEpisodeTreatment"] ;
								print "</td>" ;
								print "<td>" ;
									$style="" ;
									if ($rowCond2["lastEpisodeTreatment"]!=$rowCond["lastEpisodeTreatment"]) {
										$style="style='color: #ff0000'" ;
									}
									print "<span $style>" ;
									print $rowCond["lastEpisodeTreatment"] ;
								print "</td>" ;
								print "<td>" ;
									if ($rowCond2["lastEpisodeTreatment"]!=$rowCond["lastEpisodeTreatment"]) { print "<input checked type='checkbox' name='lastEpisodeTreatmentOn" . ($count+$count2) . "'><input name='lastEpisodeTreatment" . ($count+$count2) . "' type='hidden' value='" . htmlprep($rowCond["lastEpisodeTreatment"]) . "'>" ; }
								print "</td>" ;
							print "</tr>" ;
							print "<tr class='odd'>" ;
								print "<td>" ;
									print "Comment" ;
								print "</td>" ;
								print "<td>" ;
									print $rowCond2["comment"] ;
								print "</td>" ;
								print "<td>" ;
									$style="" ;
									if ($rowCond2["comment"]!=$rowCond["comment"]) {
										$style="style='color: #ff0000'" ;
									}
									print "<span $style>" ;
									print $rowCond["comment"] ;
								print "</td>" ;
								print "<td>" ;
									if ($rowCond2["comment"]!=$rowCond["comment"]) { print "<input checked type='checkbox' name='commentOn" . ($count+$count2) . "'><input name='comment" . ($count+$count2) . "' type='hidden' value='" . htmlprep($rowCond["comment"]) . "'>" ; }
								print "</td>" ;
							print "</tr>" ;
							
							print "<input type='hidden' name='gibbonPersonMedicalConditionUpdateID" . ($count+$count2) . "' type='gibbonPersonMedicalConditionUpdateID" . ($count+$count2) . "' value='" . $rowCond["gibbonPersonMedicalConditionUpdateID"] . "'>" ;
						}
						print "<input name='count2' id='count2' value='$count2' type='hidden'>" ;
					}
					
					
					print "<tr>" ;
						print "<td class='right' colspan=4>" ;
							print "<input name='formExists' type='hidden' value='$formExists'>" ;
							print "<input name='gibbonPersonID' type='hidden' value='" . $row["gibbonPersonID"] . "'>" ;
							print "<input name='address' type='hidden' value='" . $_GET["q"] . "'>" ;
							print "<input type='submit' value='Submit'>" ;
						print "</td>" ;
					print "</tr>" ;
					print "</table>" ;
					?>
				</form>
			<?
			}
		}
	}
}
?>