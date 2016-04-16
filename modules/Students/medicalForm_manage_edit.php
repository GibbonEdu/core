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

if (isActionAccessible($guid, $connection2, "/modules/Students/medicalForm_manage_edit.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Students/medicalForm_manage.php'>" . __($guid, 'Manage Medical Forms') . "</a> > </div><div class='trailEnd'>" . __($guid, 'Edit Medical Form') . "</div>" ;
	print "</div>" ;
	
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
		else if ($updateReturn=="success0") {
			$updateReturnMessage=__($guid, "Your request was completed successfully.") ;	
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
		if ($deleteReturn=="success0") {
			$deleteReturnMessage=__($guid, "Your request was completed successfully.") ;		
			$class="success" ;
		}
		print "<div class='$class'>" ;
			print $deleteReturnMessage;
		print "</div>" ;
	} 
	
	//Check if person medical specified
	$gibbonPersonMedicalID=$_GET["gibbonPersonMedicalID"] ;
	$search=NULL ;
	if (isset($_GET["search"])) {
		$search=$_GET["search"] ;
	}
	if ($gibbonPersonMedicalID=="") {
		print "<div class='error'>" ;
			print __($guid, "You have not specified one or more required parameters.") ;
		print "</div>" ;
	}
	else {
		try {
			$data=array("gibbonPersonMedicalID"=>$gibbonPersonMedicalID); 
			$sql="SELECT * FROM gibbonPersonMedical WHERE gibbonPersonMedicalID=:gibbonPersonMedicalID" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
		}

		if ($result->rowCount()!=1) {
			print "<div class='error'>" ;
				print __($guid, "The specified record cannot be found.") ;
			print "</div>" ;
		}
		else {
			//Let's go!
			$row=$result->fetch() ;
			
			if ($search!="") {
				print "<div class='linkTop'>" ;
					print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Students/medicalForm_manage.php&search=$search'>" . __($guid, 'Back to Search Results') . "</a>" ;
				print "</div>" ;
			}
			?>
			<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/medicalForm_manage_editProcess.php?gibbonPersonMedicalID=" . $gibbonPersonMedicalID . "&search=$search" ?>">
				<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
					<tr>
						<td style='width: 275px'> 
							<b><?php print __($guid, 'Person') ?> *</b><br/>
							<span style="font-size: 90%"><i><?php print __($guid, 'This value cannot be changed.') ?></i></span>
						</td>
						<td class="right">
							<?php
							try {
								$dataSelect=array("gibbonPersonID"=>$row["gibbonPersonID"]); 
								$sqlSelect="SELECT surname, preferredName FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonID" ;
								$resultSelect=$connection2->prepare($sqlSelect);
								$resultSelect->execute($dataSelect);
							}
							catch(PDOException $e) { }
							$rowSelect=$resultSelect->fetch() ;
							?>	
							<input readonly name="name" id="name" maxlength=255 value="<?php print formatName("", htmlPrep($rowSelect["preferredName"]), htmlPrep($rowSelect["surname"]), "Student") ; ?>" type="text" style="width: 300px">
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print __($guid, 'Blood Type') ?></b><br/>
							<span style="font-size: 90%"><i></i></span>
						</td>
						<td class="right">
							<select style="width: 302px" name="bloodType">
								<option <?php if ($row["bloodType"]=="") {print "selected ";}?>value=""></option>
								<option <?php if ($row["bloodType"]=="O+") {print "selected ";}?>value="O+">O+</option>
								<option <?php if ($row["bloodType"]=="A+") {print "selected ";}?>value="A+">A+</option>
								<option <?php if ($row["bloodType"]=="B+") {print "selected ";}?>value="B+">B+</option>
								<option <?php if ($row["bloodType"]=="AB+") {print "selected ";}?>value="AB+">AB+</option>
								<option <?php if ($row["bloodType"]=="O-") {print "selected ";}?>value="O-">O-</option>
								<option <?php if ($row["bloodType"]=="A-") {print "selected ";}?>value="A-">A-</option>
								<option <?php if ($row["bloodType"]=="B-") {print "selected ";}?>value="B-">B-</option>
								<option <?php if ($row["bloodType"]=="AB-") {print "selected ";}?>value="AB-">AB-</option>
							</select>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print __($guid, 'Long-Term Medication?') ?></b><br/>
							<span style="font-size: 90%"><i></i></span>
						</td>
						<td class="right">
							<select style="width: 302px" name="longTermMedication">
								<option <?php if ($row["longTermMedication"]=="") {print "selected ";}?>value=""></option>
								<option <?php if ($row["longTermMedication"]=="Y") {print "selected ";}?>value="Y">Y</option>
								<option <?php if ($row["longTermMedication"]=="N") {print "selected ";}?>value="N">N</option>
							</select>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print __($guid, 'Medication Details') ?></b><br/>
						</td>
						<td class="right">
							<textarea name="longTermMedicationDetails" id="longTermMedicationDetails" rows=8 style="width: 300px"><?php print $row["longTermMedicationDetails"] ?></textarea>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print __($guid, 'Tetanus Within Last 10 Years?') ?></b><br/>
						</td>
						<td class="right">
							<select style="width: 302px" name="tetanusWithin10Years">
								<option <?php if ($row["tetanusWithin10Years"]=="") {print "selected ";}?>value=""></option>
								<option <?php if ($row["tetanusWithin10Years"]=="Y") {print "selected ";}?>value="Y"><?php print __($guid, 'Yes') ?></option>
								<option <?php if ($row["tetanusWithin10Years"]=="N") {print "selected ";}?>value="N"><?php print __($guid, 'No') ?></option>
							</select>
						</td>
					</tr>						
					<tr>
						<td>
							<span style="font-size: 90%"><i>* <?php print __($guid, "denotes a required field") ; ?></i></span>
						</td>
						<td class="right">
							<input type="hidden" name="gibbonPersonMedicalID" value="<?php print $row["gibbonPersonMedicalID"] ?>">
							<input type="hidden" name="address" value="<?php print $_SESSION[$guid]["address"] ?>">
							<input type="submit" value="<?php print __($guid, "Submit") ; ?>">
						</td>
					</tr>
				</table>
			</form>
			<?php
			
			print "<h2>" ;
			print __($guid, "Medical Conditions") ;
			print "</h2>" ;
			
			try {
				$data=array("gibbonPersonMedicalID"=>$gibbonPersonMedicalID); 
				$sql="SELECT gibbonPersonMedicalCondition.*, gibbonAlertLevel.name AS risk FROM gibbonPersonMedicalCondition JOIN gibbonAlertLevel ON (gibbonPersonMedicalCondition.gibbonAlertLevelID=gibbonAlertLevel.gibbonAlertLevelID) WHERE gibbonPersonMedicalID=:gibbonPersonMedicalID ORDER BY gibbonPersonMedicalCondition.name" ; 
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				print "<div class='error'>" . $e->getMessage() . "</div>" ; 
			}
			
			print "<div class='linkTop'>" ;
			print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/medicalForm_manage_condition_add.php&gibbonPersonMedicalID=" . $row["gibbonPersonMedicalID"] . "&search=$search'>" .  __($guid, 'Add') . "<img style='margin-left: 5px' title='" . __($guid, 'Add') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/page_new.png'/></a>" ;
			print "</div>" ;
			
			if ($result->rowCount()<1) {
				print "<div class='error'>" ;
				print __($guid, "There are no records to display.") ;
				print "</div>" ;
			}
			else {
				print "<table cellspacing='0' style='width: 100%'>" ;
					print "<tr class='head'>" ;
						print "<th>" ;
							print __($guid, "Name") ;
						print "</th>" ;
						print "<th>" ;
							print __($guid, "Risk") ;
						print "</th>" ;
						print "<th>" ;
							print __($guid, "Details") ;
						print "</th>" ;
						print "<th>" ;
							print __($guid, "Medication") ;
						print "</th>" ;
						print "<th>" ;
							print __($guid, "Comment") ;
						print "</th>" ;
						print "<th>" ;
							print __($guid, "Actions") ;
						print "</th>" ;
					print "</tr>" ;
					
					$count=0;
					$rowNum="odd" ;
					while ($row=$result->fetch()) {
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
								print __($guid, $row["name"]) ;
							print "</td>" ;
							print "<td>" ;
								print __($guid, $row["risk"]) ;
							print "</td>" ;
							print "<td>" ;
								if ($row["triggers"]!="") {
									print "<b>" . __($guid, 'Triggers') . ":</b> " . $row["triggers"] . "<br/>" ;
								}
								if ($row["reaction"]!="") {
									print "<b>" . __($guid, 'Reaction') . ":</b> " . $row["reaction"] . "<br/>" ;
								}
								if ($row["response"]!="") {
									print "<b>" . __($guid, 'Response') . ":</b> " . $row["response"] . "<br/>" ;
								}
								if ($row["lastEpisode"]!="") {
									print "<b>" . __($guid, 'Last Episode') . ":</b> " . dateConvertBack($guid, $row["lastEpisode"]) . "<br/>" ;
								}
								if ($row["lastEpisodeTreatment"]!="") {
									print "<b>" . __($guid, 'Last Episode Treatment') . ":</b> " . $row["lastEpisodeTreatment"] . "<br/>" ;
								}
							print "</td>" ;
							print "<td>" ;
								print $row["medication"] ;
							print "</td>" ;
							print "<td>" ;
								print $row["comment"] ;
							print "</td>" ;
							print "<td>" ;
								print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/medicalForm_manage_condition_edit.php&gibbonPersonMedicalID=" . $row["gibbonPersonMedicalID"] . "&gibbonPersonMedicalConditionID=" . $row["gibbonPersonMedicalConditionID"] . "&search=$search'><img title='" . __($guid, 'Edit') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/config.png'/></a> " ;
								print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/medicalForm_manage_condition_delete.php&gibbonPersonMedicalID=" . $row["gibbonPersonMedicalID"] . "&gibbonPersonMedicalConditionID=" . $row["gibbonPersonMedicalConditionID"] . "&search=$search'><img title='" . __($guid, 'Delete') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/garbage.png'/></a>" ;
							print "</td>" ;
						print "</tr>" ;
					}
				print "</table>" ;
			}
		}
	}
}
?>