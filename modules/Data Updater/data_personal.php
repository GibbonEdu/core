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
include "./modules/User Admin/moduleFunctions.php" ; //for User Admin (for custom fields)

if (isActionAccessible($guid, $connection2, "/modules/Data Updater/data_personal.php")==FALSE) {
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
		print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . __($guid, 'Update Personal Data') . "</div>" ;
		print "</div>" ;
	
		if ($highestAction=="Update Personal Data_any") {
			print "<p>" ;
			print __($guid, "This page allows a user to request selected personal data updates for any user.") ;
			print "</p>" ;
		}
		else {
			print "<p>" ;
			print __($guid, "This page allows any adult with data access permission to request selected personal data updates for any member of their family.") ;
			print "</p>" ;
		}
		
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
				$updateReturnMessage=__($guid, "Your request was successful, but some data was not properly saved. An administrator will process your request as soon as possible. <u>You will not see the updated data in the system until it has been processed and approved.</u>") ; 
				if ($_SESSION[$guid]["organisationDBAEmail"]!="" AND $_SESSION[$guid]["organisationDBAName"]!="") {
					$updateReturnMessage.=" " . sprintf(__($guid, 'Please contact %1$s if you have any questions.'), "<a href='mailto:" . $_SESSION[$guid]["organisationDBAEmail"] . "'>" . $_SESSION[$guid]["organisationDBAName"] . "</a>") ;	
				}
			}
			else if ($updateReturn=="success0") {
				$updateReturnMessage=__($guid, "Your request was completed successfully. An administrator will process your request as soon as possible. You will not see the updated data in the system until it has been processed and approved.") ; 
				if ($_SESSION[$guid]["organisationDBAEmail"]!="" AND $_SESSION[$guid]["organisationDBAName"]!="") {
					$updateReturnMessage.=" " . sprintf(__($guid, 'Please contact %1$s if you have any questions.'), "<a href='mailto:" . $_SESSION[$guid]["organisationDBAEmail"] . "'>" . $_SESSION[$guid]["organisationDBAName"] . "</a>") ;	
				}
				$class="success" ;
			}
			print "<div class='$class'>" ;
				print $updateReturnMessage;
			print "</div>" ;
		} 
		
		print "<h2>" ;
		print __($guid, "Choose User") ;
		print "</h2>" ;
		
		$gibbonPersonID=NULL ;
		if (isset($_GET["gibbonPersonID"])) {
			$gibbonPersonID=$_GET["gibbonPersonID"] ;
		}
		?>
		
		<form method="get" action="<?php print $_SESSION[$guid]["absoluteURL"]?>/index.php">
			<table class='smallIntBorder fullWidth' cellspacing='0'>	
				<tr>
					<td style='width: 275px'> 
						<b><?php print __($guid, 'Person') ?> *</b><br/>
					</td>
					<td class="right">
						<select class="standardWidth" name="gibbonPersonID">
							<?php
							$self=FALSE ;
							if ($highestAction=="Update Personal Data_any") {
								try {
									$dataSelect=array(); 
									$sqlSelect="SELECT username, surname, preferredName, gibbonPerson.gibbonPersonID FROM gibbonPerson WHERE status='Full' ORDER BY surname, preferredName" ;
									$resultSelect=$connection2->prepare($sqlSelect);
									$resultSelect->execute($dataSelect);
								}
								catch(PDOException $e) { }
								print "<option value=''></option>" ;
								while ($rowSelect=$resultSelect->fetch()) {
									if ($gibbonPersonID==$rowSelect["gibbonPersonID"]) {
										print "<option selected value='" . $rowSelect["gibbonPersonID"] . "'>" . formatName("", htmlPrep($rowSelect["preferredName"]), htmlPrep($rowSelect["surname"]), "Student", true) . " (" . $rowSelect["username"] . ")</option>" ;
									}
									else {
										print "<option value='" . $rowSelect["gibbonPersonID"] . "'>" . formatName("", htmlPrep($rowSelect["preferredName"]), htmlPrep($rowSelect["surname"]), "Student", true) . " (" . $rowSelect["username"] . ")</option>" ;
									}
									$self=TRUE ;
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
										$dataSelect2=array("gibbonFamilyID1"=>$rowSelect["gibbonFamilyID"], "gibbonFamilyID2"=>$rowSelect["gibbonFamilyID"]); 
										$sqlSelect2="(SELECT surname, preferredName, gibbonPerson.gibbonPersonID, gibbonFamilyID FROM gibbonFamilyChild JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPerson.status='Full' AND gibbonFamilyID=:gibbonFamilyID1) UNION (SELECT surname, preferredName, gibbonPerson.gibbonPersonID, gibbonFamilyID FROM gibbonFamilyAdult JOIN gibbonPerson ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonFamilyID=:gibbonFamilyID2)" ;
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
										//Check for self
										if ($rowSelect2["gibbonPersonID"]==$_SESSION[$guid]["gibbonPersonID"]) {
											$self=TRUE ;
										}
									}
								}
							}
							
							if ($self==FALSE) {
								if ($gibbonPersonID==$_SESSION[$guid]["gibbonPersonID"]) {
									print "<option selected value='" . $_SESSION[$guid]["gibbonPersonID"] . "'>" . formatName("", htmlPrep($_SESSION[$guid]["preferredName"]), htmlPrep($_SESSION[$guid]["surname"]), "Student", true) . "</option>" ;
								}
								else {
									print "<option value='" . $_SESSION[$guid]["gibbonPersonID"] . "'>" . formatName("", htmlPrep($_SESSION[$guid]["preferredName"]), htmlPrep($_SESSION[$guid]["surname"]), "Student", true) . "</option>" ;
								}
							}
							?>				
						</select>
					</td>
				</tr>
				<tr>
					<td colspan=2 class="right">
						<input type="hidden" name="q" value="/modules/<?php print $_SESSION[$guid]["module"] ?>/data_personal.php">
						<input type="submit" value="<?php print __($guid, "Submit") ; ?>">
					</td>
				</tr>
			</table>
		</form>
		<?php
		
		if ($gibbonPersonID!="") {
			print "<h2>" ;
			print __($guid, "Update Data") ;
			print "</h2>" ;
			
			//Check access to person
			$checkCount=0 ;
			$self=FALSE ;
			if ($highestAction=="Update Personal Data_any") {
				try {
					$dataSelect=array(); 
					$sqlSelect="SELECT surname, preferredName, gibbonPerson.gibbonPersonID FROM gibbonPerson WHERE status='Full' ORDER BY surname, preferredName" ;
					$resultSelect=$connection2->prepare($sqlSelect);
					$resultSelect->execute($dataSelect);
				}
				catch(PDOException $e) { }
				$checkCount=$resultSelect->rowCount() ;
				$self=TRUE ;
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
						$dataCheck2=array("gibbonFamilyID1"=>$rowCheck["gibbonFamilyID"], "gibbonFamilyID2"=>$rowCheck["gibbonFamilyID"]); 
						$sqlCheck2="(SELECT surname, preferredName, gibbonPerson.gibbonPersonID, gibbonFamilyID FROM gibbonFamilyChild JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPerson.status='Full' AND gibbonFamilyID=:gibbonFamilyID1) UNION (SELECT surname, preferredName, gibbonPerson.gibbonPersonID, gibbonFamilyID FROM gibbonFamilyAdult JOIN gibbonPerson ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPerson.status='Full' AND gibbonFamilyID=:gibbonFamilyID2)" ;
						$resultCheck2=$connection2->prepare($sqlCheck2);
						$resultCheck2->execute($dataCheck2);
					}
					catch(PDOException $e) { }
					while ($rowCheck2=$resultCheck2->fetch()) {
						if ($gibbonPersonID==$rowCheck2["gibbonPersonID"]) {
							$checkCount++ ;
						}
						//Check for self
						if ($rowSelect2["gibbonPersonID"]==$_SESSION[$guid]["gibbonPersonID"]) {
							$self=TRUE ;
						}
					}
				}
			}
			
			if ($self==FALSE AND $gibbonPersonID==$_SESSION[$guid]["gibbonPersonID"]) {
				$checkCount++ ;
			}
			
			if ($checkCount<1) {
				print "<div class='error'>" ;
					print __($guid, "The selected record does not exist, or you do not have access to it.") ;
				print "</div>" ;
			}
			else {
				//Get categories
				try {
					$dataSelect=array("gibbonPersonID"=>$gibbonPersonID); 
					$sqlSelect="SELECT gibbonRoleIDAll FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonID ORDER BY surname, preferredName" ;
					$resultSelect=$connection2->prepare($sqlSelect);
					$resultSelect->execute($dataSelect);
				}
				catch(PDOException $e) { }
				if ($resultSelect->rowCount()==1) {
					$rowSelect=$resultSelect->fetch() ;
					$staff=FALSE ;
					$student=FALSE ;
					$parent=FALSE ;
					$other=FALSE ;
					$roles=explode(",", $rowSelect["gibbonRoleIDAll"]) ;
					foreach ($roles AS $role) {
						$roleCategory=getRoleCategory($role, $connection2) ;
						if ($roleCategory=="Staff") {
							$staff=TRUE ;
						} 
						if ($roleCategory=="Student") {
							$student=TRUE ;
						} 
						if ($roleCategory=="Parent") {
							$parent=TRUE ;
						} 
						if ($roleCategory=="Other") {
							$other=TRUE ;
						} 
					}
				}
				
			
			
				//Check if there is already a pending form for this user
				$existing=FALSE ;
				$proceed=FALSE;
				try {
					$data=array("gibbonPersonID"=>$gibbonPersonID, "gibbonPersonIDUpdater"=>$_SESSION[$guid]["gibbonPersonID"]); 
					$sql="SELECT * FROM gibbonPersonUpdate WHERE gibbonPersonID=:gibbonPersonID AND gibbonPersonIDUpdater=:gibbonPersonIDUpdater AND status='Pending'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				
				if ($result->rowCount()>1) {
					print "<div class='error'>" ;
						print __($guid, "Your request failed due to a database error.") ;
					print "</div>" ;
				}
				else if ($result->rowCount()==1) {
					$existing=TRUE ;
					$proceed=FALSE;
					if ($updateReturn=="") {
						print "<div class='warning'>" ;
							print __($guid, "You have already submitted a form, which is pending approval by an administrator. If you wish to make changes, please edited the data below, but remember your data will not appear in the system until it has been approved.") ;
						print "</div>" ;
					}
					if ($highestAction!="Update Personal Data_any") {
						$required=unserialize(getSettingByScope( $connection2, "User Admin", "personalDataUpdaterRequiredFields")) ;
						if (is_array($required)) {
							$proceed=TRUE;
						}
					}
					else {
						$proceed=TRUE;
					}
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
							print __($guid, "The specified record cannot be found.") ;
						print "</div>" ;
					}
					else {
						if ($highestAction!="Update Personal Data_any") {
							$required=unserialize(getSettingByScope( $connection2, "User Admin", "personalDataUpdaterRequiredFields")) ;
							if (is_array($required)) {
								$proceed=TRUE;
							}
						}
						else {
							$proceed=TRUE;
						}
					}
				}
			
				if ($proceed==TRUE) {
					//Let's go!
					$row=$result->fetch() ;
					?>
					<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/data_personalProcess.php?gibbonPersonID=" . $gibbonPersonID ?>">
						<table class='smallIntBorder fullWidth' cellspacing='0'>	
							<tr class='break'>
								<td colspan=2> 
									<h3><?php print __($guid, 'Basic Information') ?></h3>
								</td>
							</tr>
							<tr>
								<td style='width: 275px'> 
									<b><?php print __($guid, 'Title') ?><?php if (isset($required["title"])) { if ($required["title"]=="Y") { print " *" ; } } ?></b><br/>
								</td>
								<td class="right">
									<select class="standardWidth" name="title" id="title">
										<?php if ($required["title"]=="Y") { print "<option value='Please select...'>" . __($guid, 'Please select...') . "</option>" ; } else { print "<option value=''></option>" ; } ?>
										<option <?php if ($row["title"]=="Ms.") {print "selected ";}?>value="Ms."><?php print __($guid, 'Ms.') ?></option>
										<option <?php if ($row["title"]=="Miss") {print "selected ";}?>value="Miss"><?php print __($guid, 'Miss') ?></option>
										<option <?php if ($row["title"]=="Mr.") {print "selected ";}?>value="Mr."><?php print __($guid, 'Mr.') ?></option>
										<option <?php if ($row["title"]=="Mrs.") {print "selected ";}?>value="Mrs."><?php print __($guid, 'Mrs.') ?></option>
										<option <?php if ($row["title"]=="Dr.") {print "selected ";}?>value="Dr."><?php print __($guid, 'Dr.') ?></option>
									</select>
									<?php
									$fieldName="title" ; 
									if (isset($required[$fieldName])) { if ($required[$fieldName]=="Y") {
										print "<script type=\"text/javascript\">" ;
											print "var " . $fieldName . "=new LiveValidation('" . $fieldName . "');" ;
											print $fieldName . ".add(Validate.Exclusion, { within: ['Please select...'], failureMessage: \"Select something!\"});" ;
										 print "</script>" ;
									} }
									?>
								</td>
							</tr>
							<tr>
								<td> 
									<b><?php print __($guid, 'Surname') ?><?php if (isset($required["surname"])) { if ($required["surname"]=="Y") { print " *" ; } } ?></b><br/>
									<span class="emphasis small"><?php print __($guid, 'Family name as shown in ID documents.') ?></span>
								</td>
								<td class="right">
									<input name="surname" id="surname" maxlength=30 value="<?php print htmlPrep($row["surname"]) ?>" type="text" class="standardWidth">
									<?php
									$fieldName="surname" ; 
									if (isset($required[$fieldName])) { if ($required[$fieldName]=="Y") {
										print "<script type=\"text/javascript\">" ;
											print "var " . $fieldName . "=new LiveValidation('" . $fieldName . "');" ;
											print $fieldName . ".add(Validate.Presence);" ;
										 print "</script>" ;
									} }
									?>									
								</td>
							</tr>
							<tr>
								<td> 
									<b><?php print __($guid, 'First Name') ?><?php if (isset($required["firstName"])) { if ($required["firstName"]=="Y") { print " *" ; } } ?></b><br/>
									<span class="emphasis small"><?php print __($guid, 'First name as shown in ID documents.') ?></span>
								</td>
								<td class="right">
									<input name="firstName" id="firstName" maxlength=30 value="<?php print htmlPrep($row["firstName"]) ?>" type="text" class="standardWidth">
									<?php
									$fieldName="firstName" ; 
									if (isset($required[$fieldName])) { if ($required[$fieldName]=="Y") {
										print "<script type=\"text/javascript\">" ;
											print "var " . $fieldName . "=new LiveValidation('" . $fieldName . "');" ;
											print $fieldName . ".add(Validate.Presence);" ;
										 print "</script>" ;
									} }
									?>									
								</td>
							</tr>
							<tr>
								<td> 
									<b><?php print __($guid, 'Preferred Name') ?><?php if (isset($required["preferredName"])) { if ($required["preferredName"]=="Y") { print " *" ; } } ?></b><br/>
									<span class="emphasis small"><?php print __($guid, 'Most common name, alias, nickname, etc.') ?></span>
								</td>
								<td class="right">
									<input name="preferredName" id="preferredName" maxlength=30 value="<?php print htmlPrep($row["preferredName"]) ?>" type="text" class="standardWidth">
									<?php
									$fieldName="preferredName" ; 
									if (isset($required[$fieldName])) { if ($required[$fieldName]=="Y") {
										print "<script type=\"text/javascript\">" ;
											print "var " . $fieldName . "=new LiveValidation('" . $fieldName . "');" ;
											print $fieldName . ".add(Validate.Presence);" ;
										 print "</script>" ;
									} }
									?>									
								</td>
							</tr>
							<tr>
								<td> 
									<b><?php print __($guid, 'Official Name') ?><?php if (isset($required["officialName"])) { if ($required["officialName"]=="Y") { print " *" ; } } ?></b><br/>
									<span class="emphasis small"><?php print __($guid, 'Full name as shown in ID documents.') ?></span>
								</td>
								<td class="right">
									<input name="officialName" id="officialName" maxlength=150 value="<?php print htmlPrep($row["officialName"]) ?>" type="text" class="standardWidth">
									<?php
									$fieldName="officialName" ; 
									if (isset($required[$fieldName])) { if ($required[$fieldName]=="Y") {
										print "<script type=\"text/javascript\">" ;
											print "var " . $fieldName . "=new LiveValidation('" . $fieldName . "');" ;
											print $fieldName . ".add(Validate.Presence);" ;
										 print "</script>" ;
									} }
									?>									
								</td>
							</tr>
							<tr>
								<td> 
									<b><?php print __($guid, 'Name In Characters') ?><?php if (isset($required["nameInCharacters"])) { if ($required["nameInCharacters"]=="Y") { print " *" ; } } ?></b><br/>
									<span class="emphasis small"><?php print __($guid, 'Chinese or other character-based name.') ?></span>
								</td>
								<td class="right">
									<input name="nameInCharacters" id="nameInCharacters" maxlength=20 value="<?php print htmlPrep($row["nameInCharacters"]) ?>" type="text" class="standardWidth">
									<?php
									$fieldName="nameInCharacters" ; 
									if (isset($required[$fieldName])) { if ($required[$fieldName]=="Y") {
										print "<script type=\"text/javascript\">" ;
											print "var " . $fieldName . "=new LiveValidation('" . $fieldName . "');" ;
											print $fieldName . ".add(Validate.Presence);" ;
										 print "</script>" ;
									} }
									?>									
								</td>
							</tr>
							<tr>
								<td> 
									<b><?php print __($guid, 'Date of Birth') ?><?php if (isset($required["dob"])) { if ($required["dob"]=="Y") { print " *" ; } } ?></b><br/>
									<span class="emphasis small"><?php print $_SESSION[$guid]["i18n"]["dateFormat"]  ?></span>
								</td>
								<td class="right">
									<input name="dob" id="dob" maxlength=10 value="<?php print dateConvertBack($guid, $row["dob"]) ?>" type="text" class="standardWidth">
									<?php
									$fieldName="dob" ; 
									if (isset($required[$fieldName])) { if ($required[$fieldName]=="Y") {
										print "<script type=\"text/javascript\">" ;
											print "var " . $fieldName . "=new LiveValidation('" . $fieldName . "');" ;
											print $fieldName . ".add(Validate.Presence);" ;
											print $fieldName . "add( Validate.Format, {pattern:" ;  if ($_SESSION[$guid]["i18n"]["dateFormatRegEx"]=="") {  print "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i" ; } else { print $_SESSION[$guid]["i18n"]["dateFormatRegEx"] ; } print ", failureMessage: \"Use dd/mm/yyyy.\" } );" ; 
										 print "</script>" ;
									} }
									else {
										print "<script type=\"text/javascript\">" ;
											print "var " . $fieldName . "=new LiveValidation('" . $fieldName . "');" ;
											print $fieldName . "add( Validate.Format, {pattern:" ; if ($_SESSION[$guid]["i18n"]["dateFormatRegEx"]=="") {  print "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i" ; } else { print $_SESSION[$guid]["i18n"]["dateFormatRegEx"] ; } print ", failureMessage: \"Use dd/mm/yyyy.\" } );" ; 
										 print "</script>" ;
									}
									?>
									<script type="text/javascript">
										$(function() {
											$( "#dob" ).datepicker();
										});
									</script>
								</td>
							</tr>
							
							<?php
							if ($student OR $staff) {
								?> 
								<tr class='break'>
									<td colspan=2> 
										<h3><?php print __($guid, 'Emergency Contacts') ?></h3>
									</td>
								</tr>
								<tr>
									<td colspan=2> 
										<?php print __($guid, 'These details are used when immediate family members (e.g. parent, spouse) cannot be reached first. Please try to avoid listing immediate family members.') ?> 
									</td>
								</tr>
								<tr>
									<td> 
										<b><?php print __($guid, 'Contact 1 Name') ?><?php if (isset($required["emergency1Name"])) { if ($required["emergency1Name"]=="Y") { print " *" ; } } ?></b><br/>
									</td>
									<td class="right">
										<input name="emergency1Name" id="emergency1Name" maxlength=30 value="<?php print htmlPrep($row["emergency1Name"]) ?>" type="text" class="standardWidth">
										<?php
										$fieldName="emergency1Name" ; 
										if (isset($required[$fieldName])) { if ($required[$fieldName]=="Y") {
											print "<script type=\"text/javascript\">" ;
												print "var " . $fieldName . "=new LiveValidation('" . $fieldName . "');" ;
												print $fieldName . ".add(Validate.Presence);" ;
											 print "</script>" ;
										} }
										?>									
									</td>
								</tr>
								<tr>
									<td> 
										<b><?php print __($guid, 'Contact 1 Relationship') ?><?php if (isset($required["emergency1Relationship"])) { if ($required["emergency1Relationship"]=="Y") { print " *" ; } } ?></b><br/>
									</td>
									<td class="right">
										<select name="emergency1Relationship" id="emergency1Relationship" class="standardWidth">
											<?php if ($required["emergency1Relationship"]=="Y") { print "<option value='Please select...'>" . __($guid, 'Please select...') . "</option>" ; } else { print "<option value=''></option>" ; } ?>
											<option <?php if ($row["emergency1Relationship"]=="Parent") {print "selected ";}?>value="Parent"><?php print __($guid, 'Parent') ?></option>
											<option <?php if ($row["emergency1Relationship"]=="Spouse") {print "selected ";}?>value="Spouse"><?php print __($guid, 'Spouse') ?></option>
											<option <?php if ($row["emergency1Relationship"]=="Offspring") {print "selected ";}?>value="Offspring"><?php print __($guid, 'Offspring') ?></option>
											<option <?php if ($row["emergency1Relationship"]=="Friend") {print "selected ";}?>value="Friend"><?php print __($guid, 'Friend') ?></option>
											<option <?php if ($row["emergency1Relationship"]=="Other Relation") {print "selected ";}?>value="Other Relation"><?php print __($guid, 'Other Relation') ?></option>
											<option <?php if ($row["emergency1Relationship"]=="Doctor") {print "selected ";}?>value="Doctor"><?php print __($guid, 'Doctor') ?></option>
											<option <?php if ($row["emergency1Relationship"]=="Other") {print "selected ";}?>value="Other"><?php print __($guid, 'Other') ?></option>
										</select>
										<?php
										$fieldName="emergency1Relationship" ; 
										if (isset($required[$fieldName])) { if ($required[$fieldName]=="Y") {
											print "<script type=\"text/javascript\">" ;
												print "var " . $fieldName . "=new LiveValidation('" . $fieldName . "');" ;
												print $fieldName . ".add(Validate.Exclusion, { within: ['Please select...'], failureMessage: \"Select something!\"});" ;
											 print "</script>" ;
										} }
										?>
									</td>
								</tr>
								<tr>
									<td> 
										<b><?php print __($guid, 'Contact 1 Number 1') ?><?php if (isset($required["emergency1Number1"])) { if ($required["emergency1Number1"]=="Y") { print " *" ; } } ?></b><br/>
									</td>
									<td class="right">
										<input name="emergency1Number1" id="emergency1Number1" maxlength=30 value="<?php print htmlPrep($row["emergency1Number1"]) ?>" type="text" class="standardWidth">
										<?php
										$fieldName="emergency1Number1" ; 
										if (isset($required[$fieldName])) { if ($required[$fieldName]=="Y") {
											print "<script type=\"text/javascript\">" ;
												print "var " . $fieldName . "=new LiveValidation('" . $fieldName . "');" ;
												print $fieldName . ".add(Validate.Presence);" ;
											 print "</script>" ;
										} }
										?>									
									</td>
								</tr>
								<tr>
									<td> 
										<b><?php print __($guid, 'Contact 1 Number 2') ?><?php if (isset($required["emergency1Number2"])) { if ($required["emergency1Number2"]=="Y") { print " *" ; } } ?></b><br/>
									</td>
									<td class="right">
										<input name="emergency1Number2" id="emergency1Number2" maxlength=30 value="<?php print htmlPrep($row["emergency1Number2"]) ?>" type="text" class="standardWidth">
										<?php
										$fieldName="emergency1Number2" ; 
										if (isset($required[$fieldName])) { if ($required[$fieldName]=="Y") {
											print "<script type=\"text/javascript\">" ;
												print "var " . $fieldName . "=new LiveValidation('" . $fieldName . "');" ;
												print $fieldName . ".add(Validate.Presence);" ;
											 print "</script>" ;
										} }
										?>									
									</td>
								</tr>
								<tr>
									<td> 
										<b><?php print __($guid, 'Contact 2 Name') ?><?php if (isset($required["emergency2Name"])) { if ($required["emergency2Name"]=="Y") { print " *" ; } } ?></b><br/>
									</td>
									<td class="right">
										<input name="emergency2Name" id="emergency2Name" maxlength=30 value="<?php print htmlPrep($row["emergency2Name"]) ?>" type="text" class="standardWidth">
										<?php
										$fieldName="emergency2Name" ; 
										if (isset($required[$fieldName])) { if ($required[$fieldName]=="Y") {
											print "<script type=\"text/javascript\">" ;
												print "var " . $fieldName . "=new LiveValidation('" . $fieldName . "');" ;
												print $fieldName . ".add(Validate.Presence);" ;
											 print "</script>" ;
										} }
										?>									
									</td>
								</tr>
								<tr>
									<td> 
										<b><?php print __($guid, 'Contact 2 Relationship') ?><?php if (isset($required["emergency2Relationship"])) { if ($required["emergency2Relationship"]=="Y") { print " *" ; } } ?></b><br/>
									</td>
									<td class="right">
										<select name="emergency2Relationship" id="emergency2Relationship" class="standardWidth">
											<?php if ($required["emergency2Relationship"]=="Y") { print "<option value='Please select...'>" . __($guid, 'Please select...') . "</option>" ; } else { print "<option value=''></option>" ; } ?>
											<option <?php if ($row["emergency2Relationship"]=="Parent") {print "selected ";}?>value="Parent"><?php print __($guid, 'Parent') ?></option>
											<option <?php if ($row["emergency2Relationship"]=="Spouse") {print "selected ";}?>value="Spouse"><?php print __($guid, 'Spouse') ?></option>
											<option <?php if ($row["emergency2Relationship"]=="Offspring") {print "selected ";}?>value="Offspring"><?php print __($guid, 'Offspring') ?></option>
											<option <?php if ($row["emergency2Relationship"]=="Friend") {print "selected ";}?>value="Friend"><?php print __($guid, 'Friend') ?></option>
											<option <?php if ($row["emergency2Relationship"]=="Other Relation") {print "selected ";}?>value="Other Relation"><?php print __($guid, 'Other Relation') ?></option>
											<option <?php if ($row["emergency2Relationship"]=="Doctor") {print "selected ";}?>value="Doctor"><?php print __($guid, 'Doctor') ?></option>
											<option <?php if ($row["emergency2Relationship"]=="Other") {print "selected ";}?>value="Other"><?php print __($guid, 'Other') ?></option>
										</select>
										<?php
										$fieldName="emergency2Relationship" ; 
										if (isset($required[$fieldName])) { if ($required[$fieldName]=="Y") {
											print "<script type=\"text/javascript\">" ;
												print "var " . $fieldName . "=new LiveValidation('" . $fieldName . "');" ;
												print $fieldName . ".add(Validate.Exclusion, { within: ['Please select...'], failureMessage: \"Select something!\"});" ;
											 print "</script>" ;
										} }
										?>	
									</td>
								</tr>
								<tr>
									<td> 
										<b><?php print __($guid, 'Contact 2 Number 1') ?><?php if (isset($required["emergency2Number1"])) { if ($required["emergency2Number1"]=="Y") { print " *" ; } } ?></b><br/>
									</td>
									<td class="right">
										<input name="emergency2Number1" id="emergency2Number1" maxlength=30 value="<?php print htmlPrep($row["emergency2Number1"]) ?>" type="text" class="standardWidth">
										<?php
										$fieldName="emergency2Number1" ; 
										if (isset($required[$fieldName])) { if ($required[$fieldName]=="Y") {
											print "<script type=\"text/javascript\">" ;
												print "var " . $fieldName . "=new LiveValidation('" . $fieldName . "');" ;
												print $fieldName . ".add(Validate.Presence);" ;
											 print "</script>" ;
										} }
										?>									
									</td>
								</tr>
								<tr>
									<td> 
										<b><?php print __($guid, 'Contact 2 Number 2') ?><?php if (isset($required["emergency2Number2"])) { if ($required["emergency2Number2"]=="Y") { print " *" ; } } ?></b><br/>
									</td>
									<td class="right">
										<input name="emergency2Number2" id="emergency2Number2" maxlength=30 value="<?php print htmlPrep($row["emergency2Number2"]) ?>" type="text" class="standardWidth">
										<?php
										$fieldName="emergency2Number2" ; 
										if (isset($required[$fieldName])) { if ($required[$fieldName]=="Y") {
											print "<script type=\"text/javascript\">" ;
												print "var " . $fieldName . "=new LiveValidation('" . $fieldName . "');" ;
												print $fieldName . ".add(Validate.Presence);" ;
											 print "</script>" ;
										} }
										?>									
									</td>
								</tr>
								<?php
							}
							?>
							
							<tr class='break'>
								<td colspan=2> 
									<h3><?php print __($guid, 'Contact Information') ?></h3>
								</td>
							</tr>
							<tr>
								<td> 
									<b><?php print __($guid, 'Email') ?><?php if (isset($required["email"])) { if ($required["email"]=="Y") { print " *" ; } } ?></b><br/>
								</td>
								<td class="right">
									<input name="email" id="email" maxlength=50 value="<?php print htmlPrep($row["email"]) ?>" type="text" class="standardWidth">
									<?php
									$fieldName="email" ; 
									if (isset($required[$fieldName])) { if ($required[$fieldName]=="Y") {
										print "<script type=\"text/javascript\">" ;
											print "var " . $fieldName . "=new LiveValidation('" . $fieldName . "');" ;
											print $fieldName . ".add(Validate.Presence);" ;
											print $fieldName . ".add(Validate.Email);" ;
										 print "</script>" ;
									} }
									else {
										print "<script type=\"text/javascript\">" ;
											print "var " . $fieldName . "=new LiveValidation('" . $fieldName . "');" ;
											print $fieldName . ".add(Validate.Email);" ;
										 print "</script>" ;
									}
									?>
								</td>
							</tr>
							<tr>
								<td> 
									<b><?php print __($guid, 'Alternate Email') ?><?php if (isset($required["emailAlternate"])) { if ($required["emailAlternate"]=="Y") { print " *" ; } } ?></b><br/>
									<span class="emphasis small"></span>
								</td>
								<td class="right">
									<input name="emailAlternate" id="emailAlternate" maxlength=50 value="<?php print htmlPrep($row["emailAlternate"]) ?>" type="text" class="standardWidth">
									<?php
									$fieldName="email" ; 
									if (isset($required[$fieldName])) { if ($required[$fieldName]=="Y") {
										print "<script type=\"text/javascript\">" ;
											print "var " . $fieldName . "=new LiveValidation('" . $fieldName . "');" ;
											print $fieldName . ".add(Validate.Presence);" ;
											print $fieldName . ".add(Validate.Email);" ;
										 print "</script>" ;
									} }
									else {
										print "<script type=\"text/javascript\">" ;
											print "var " . $fieldName . "=new LiveValidation('" . $fieldName . "');" ;
											print $fieldName . ".add(Validate.Email);" ;
										 print "</script>" ;
									}
									?>
								</td>
							</tr>
							
							<tr>
								<td colspan=2> 
									<div class='warning'>
										<?php print __($guid, 'Address information for an individual only needs to be set under the following conditions:') ?>
										<ol>
											<li><?php print __($guid, 'If the user is not in a family.') ?></li>
											<li><?php print __($guid, 'If the user\'s family does not have a home address set.') ?></li>
											<li><?php print __($guid, 'If the user needs an address in addition to their family\'s home address.') ?></li>
										</ol>
									</div>
								</td>
							</tr>
							<?php
							//Controls to hide address fields unless they are present, or box is checked
							$addressSet=FALSE ;
							if ($row["address1"]!="" OR $row["address1District"]!="" OR $row["address1Country"]!="" OR $row["address2"]!="" OR $row["address2District"]!="" OR $row["address2Country"]!="") {
								$addressSet=TRUE ;
							}
							?>
							<tr>
								<td> 
									<b><?php print __($guid, 'Enter Personal Address?') ?></b><br/>
								</td>
								<td class='right' colspan=2> 
									<script type="text/javascript">
										/* Advanced Options Control */
										$(document).ready(function(){
											<?php
											if ($addressSet==FALSE) {
												print "$(\".address\").slideUp(\"fast\"); " ;
											}
											?>
											$("#showAddresses").click(function(){
												if ($('input[name=showAddresses]:checked').val()=="Yes" ) {
													$(".address").slideDown("fast", $(".address").css("display","table-row")); 
												} 
												else {
													$(".address").slideUp("fast"); 
													$("#address1").val(""); 
													$("#address1District").val(""); 
													$("#address1Country").val(""); 
													$("#address2").val(""); 
													$("#address2District").val(""); 
													$("#address2Country").val(""); 
														
												}
											 });
										});
									</script>
									<input <?php if ($addressSet) { print "checked" ; } ?> id='showAddresses' name='showAddresses' type='checkbox' value='Yes'/>
								</td>
							</tr>
							
							<tr class='address'>
								<td> 
									<b><?php print __($guid, 'Address 1') ?></b><br/>
									<span class="emphasis small"><span class="emphasis small"><?php print __($guid, 'Unit, Building, Street') ?></span></span>
								</td>
								<td class="right">
									<input name="address1" id="address1" maxlength=255 value="<?php print htmlPrep($row["address1"]) ?>" type="text" class="standardWidth">							
								</td>
							</tr>
							<tr class='address'>
								<td> 
									<b><?php print __($guid, 'Address 1 District') ?></b><br/>
									<span class="emphasis small"><?php print __($guid, 'County, State, District') ?></span>
								</td>
								<td class="right">
									<input name="address1District" id="address1District" maxlength=30 value="<?php print $row["address1District"] ?>" type="text" class="standardWidth">								
								</td>
								<script type="text/javascript">
									$(function() {
										var availableTags=[
											<?php
											try {
												$dataAuto=array(); 
												$sqlAuto="SELECT DISTINCT name FROM gibbonDistrict ORDER BY name" ;
												$resultAuto=$connection2->prepare($sqlAuto);
												$resultAuto->execute($dataAuto);
											}
											catch(PDOException $e) { }
											while ($rowAuto=$resultAuto->fetch()) {
												print "\"" . $rowAuto["name"] . "\", " ;
											}
											?>
										];
										$( "#address1District" ).autocomplete({source: availableTags});
									});
								</script>
							</tr>
							<tr class='address'>
								<td> 
									<b><?php print __($guid, 'Address 1 Country') ?></b><br/>
								</td>
								<td class="right">
									<select name="address1Country" id="address1Country" class="standardWidth">
										<?php
										try {
											$dataSelect=array(); 
											$sqlSelect="SELECT printable_name FROM gibbonCountry ORDER BY printable_name" ;
											$resultSelect=$connection2->prepare($sqlSelect);
											$resultSelect->execute($dataSelect);
										}
										catch(PDOException $e) { }
										if ($required["address1Country"]=="Y") { print "<option value='Please select...'>" . __($guid, 'Please select...') . "</option>" ; } else { print "<option value=''></option>" ; }
										while ($rowSelect=$resultSelect->fetch()) {
											$selected="" ;
											if ($rowSelect["printable_name"]==$row["address1Country"]) {
												$selected=" selected" ;
											}
											print "<option $selected value='" . $rowSelect["printable_name"] . "'>" . htmlPrep(__($guid, $rowSelect["printable_name"])) . "</option>" ;
										}
										?>				
									</select>
								</td>
							</tr>
							
							<?php
							//Check for matching addresses
							if ($row["address1"]!="") {
								$addressMatch="%" . strtolower(preg_replace("/ /", "%", preg_replace("/,/", "%", $row["address1"]))) . "%" ;
								
								try {
									$dataAddress=array("addressMatch"=>$addressMatch, "gibbonPersonID"=>$row["gibbonPersonID"]); 
									$sqlAddress="SELECT gibbonPersonID, title, preferredName, surname, category FROM gibbonPerson JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) WHERE status='Full' AND address1 LIKE :addressMatch AND NOT gibbonPersonID=:gibbonPersonID ORDER BY surname, preferredName" ;
									$resultAddress=$connection2->prepare($sqlAddress);
									$resultAddress->execute($dataAddress);
								}
								catch(PDOException $e) { 
									print "<div class='error'>" . $e->getMessage() . "</div>" ; 
								}
								
								if ($resultAddress->fetch()>0) {
									$addressCount=0 ;
									print "<tr class='address'>" ;
										print "<td style='border-top: 1px dashed #c00; border-bottom: 1px dashed #c00; background-color: #F6CECB'> " ;
											print "<b>" . __($guid, 'Matching Address 1') . "</b><br/>" ;
											print "<span style='font-size: 90%'><i>" . __($guid, 'These users have similar Address 1. Do you want to change them too?') . "</span>" ;
										print "</td>" ;
										print "<td style='text-align: right; border-top: 1px dashed #c00; border-bottom: 1px dashed #c00; background-color: #F6CECB'> " ;
											print "<table cellspacing='0' style='width:306px; float: right; padding: 0px; margin: 0px'>" ;
											while ($rowAddress=$resultAddress->fetch()) {
												print "<tr>" ;
													print "<td style='padding-left: 0px; padding-right: 0px; width:200px'>" ;
														print "<input readonly style='float: left; margin-left: 0px; width: 200px' type='text' value='" . formatName($rowAddress["title"], $rowAddress["preferredName"], $rowAddress["surname"], $rowAddress["category"]) ." (" . $rowAddress["category"] . ")'>" . "<br/>" ;
													print "</td>" ;
													print "<td style='padding-left: 0px; padding-right: 0px; width:60px'>" ;
														print "<input type='checkbox' name='$addressCount-matchAddress' value='" . $rowAddress["gibbonPersonID"] . "'>" . "<br/>" ;
													print "</td>" ;
												print "</tr>" ;
												$addressCount++ ;
											}
											print "</table>" ;
										print "</td>" ;
									print "</tr>" ;
									print "<input type='hidden' name='matchAddressCount' value='$addressCount'>" . "<br/>" ;
								}
							}
							?>
					
							<tr class='address'>
								<td> 
									<b><?php print __($guid, 'Address 2') ?></b><br/>
									<span class="emphasis small"><span class="emphasis small"><?php print __($guid, 'Unit, Building, Street') ?></span></span>
								</td>
								<td class="right">
									<input name="address2" id="address2" maxlength=255 value="<?php print htmlPrep($row["address2"]) ?>" type="text" class="standardWidth">							
								</td>
							</tr>
							<tr class='address'>
								<td> 
									<b><?php print __($guid, 'Address 2 District') ?></b><br/>
									<span class="emphasis small"><?php print __($guid, 'County, State, District') ?></span>
								</td>
								<td class="right">
									<input name="address2District" id="address2District" maxlength=30 value="<?php print $row["address2District"] ?>" type="text" class="standardWidth">						
								</td>
								<script type="text/javascript">
									$(function() {
										var availableTags=[
											<?php
											try {
												$dataAuto=array(); 
												$sqlAuto="SELECT DISTINCT name FROM gibbonDistrict ORDER BY name" ;
												$resultAuto=$connection2->prepare($sqlAuto);
												$resultAuto->execute($dataAuto);
											}
											catch(PDOException $e) { }
											while ($rowAuto=$resultAuto->fetch()) {
												print "\"" . $rowAuto["name"] . "\", " ;
											}
											?>
										];
										$( "#address2District" ).autocomplete({source: availableTags});
									});
								</script>
							</tr>
							<tr class='address'>
								<td> 
									<b><?php print __($guid, 'Address 2 Country') ?></b><br/>
								</td>
								<td class="right">
									<select name="address2Country" id="address2Country" class="standardWidth">
										<?php
										try {
											$dataSelect=array(); 
											$sqlSelect="SELECT printable_name FROM gibbonCountry ORDER BY printable_name" ;
											$resultSelect=$connection2->prepare($sqlSelect);
											$resultSelect->execute($dataSelect);
										}
										catch(PDOException $e) { }
										if ($required["address2Country"]=="Y") { print "<option value='Please select...'>" . __($guid, 'Please select...') . "</option>" ; } else { print "<option value=''></option>" ; }
										while ($rowSelect=$resultSelect->fetch()) {
											$selected="" ;
											if ($rowSelect["printable_name"]==$row["address2Country"]) {
												$selected=" selected" ;
											}
											print "<option $selected value='" . $rowSelect["printable_name"] . "'>" . htmlPrep(__($guid, $rowSelect["printable_name"])) . "</option>" ;
										}
										?>				
									</select>
								</td>
							</tr>
							<?php
								for ($i=1; $i<5; $i++) {
									?>
									<tr>
										<td> 
											<b><?php print __($guid, 'Phone') ?> <?php print $i ?><?php if (isset($required["phone" . $i])) { if ($required["phone" . $i]=="Y") { print " *" ; } } ?></b><br/>
											<span class="emphasis small"><?php print __($guid, 'Type, country code, number.') ?></span>
										</td>
										<td class="right">
											<input name="phone<?php print $i ?>" id="phone<?php print $i ?>" maxlength=20 value="<?php print $row["phone" . $i] ?>" type="text" style="width: 160px">
											<?php
											$fieldName="phone" . $i ; 
											if (isset($required[$fieldName])) { if ($required[$fieldName]=="Y") {
												print "<script type=\"text/javascript\">" ;
													print "var " . $fieldName . "=new LiveValidation('" . $fieldName . "');" ;
													print $fieldName . ".add(Validate.Presence);" ;
												 print "</script>" ;
											} }
											?>									
											<select name="phone<?php print $i ?>CountryCode" id="phone<?php print $i ?>CountryCode" style="width: 60px">
												<?php
												if ($required["phone" . $i]=="Y") { print "<option value='Please select...'>" . __($guid, 'Please select...') . "</option>" ; } else { print "<option value=''></option>" ; }
												try {
													$dataSelect=array(); 
													$sqlSelect="SELECT * FROM gibbonCountry ORDER BY printable_name" ;
													$resultSelect=$connection2->prepare($sqlSelect);
													$resultSelect->execute($dataSelect);
												}
												catch(PDOException $e) { }
												while ($rowSelect=$resultSelect->fetch()) {
													$selected="" ;
													if ($row["phone" . $i . "CountryCode"]!="" AND $row["phone" . $i . "CountryCode"]==$rowSelect["iddCountryCode"]) {
														$selected="selected" ;
													}
													print "<option $selected value='" . $rowSelect["iddCountryCode"] . "'>" . htmlPrep($rowSelect["iddCountryCode"]) . " - " .  htmlPrep(__($guid, $rowSelect["printable_name"])) . "</option>" ;
												}
												?>				
											</select>
											<?php
											$fieldName="phone" . $i . "CountryCode" ; 
											
											if (isset($required["phone" . $i])) { if ($required["phone" . $i]=="Y") {
												print "<script type=\"text/javascript\">" ;
													print "var " . $fieldName . "=new LiveValidation('" . $fieldName . "');" ;
													print $fieldName . ".add(Validate.Exclusion, { within: ['Please select...'], failureMessage: \"Select something!\"});" ;
												 print "</script>" ;
											} }
											?>
											<select style="width: 70px" name="phone<?php print $i ?>Type" id="phone<?php print $i ?>Type">
												<?php if ($required["phone" . $i]=="Y") { print "<option value='Please select...'>" . __($guid, 'Please select...') . "</option>" ; } else { print "<option value=''></option>" ; } ?>
												<option <?php if ($row["phone" . $i . "Type"]=="Mobile") { print "selected" ; }?> value="Mobile"><?php print __($guid, 'Mobile') ?></option>
												<option <?php if ($row["phone" . $i . "Type"]=="Home") { print "selected" ; }?> value="Home"><?php print __($guid, 'Home') ?></option>
												<option <?php if ($row["phone" . $i . "Type"]=="Work") { print "selected" ; }?> value="Work"><?php print __($guid, 'Work') ?></option>
												<option <?php if ($row["phone" . $i . "Type"]=="Fax") { print "selected" ; }?> value="Fax"><?php print __($guid, 'Fax') ?></option>
												<option <?php if ($row["phone" . $i . "Type"]=="Pager") { print "selected" ; }?> value="Pager"><?php print __($guid, 'Pager') ?></option>
												<option <?php if ($row["phone" . $i . "Type"]=="Other") { print "selected" ; }?> value="Other"><?php print __($guid, 'Other') ?></option>
											</select>
											<?php
											$fieldName="phone" . $i . "Type" ; 
											if (isset($required["phone" . $i])) { if ($required["phone" . $i]=="Y") {
												print "<script type=\"text/javascript\">" ;
													print "var " . $fieldName . "=new LiveValidation('" . $fieldName . "');" ;
													print $fieldName . ".add(Validate.Exclusion, { within: ['Please select...'], failureMessage: \"Select something!\"});" ;
												 print "</script>" ;
											} }
											?>
										</td>
									</tr>
									<?php
								}
								?>
							<tr class='break'>
								<td colspan=2> 
									<h3><?php print __($guid, 'Background Information') ?></h3>
								</td>
							</tr>
							<tr>
								<td> 
									<b><?php print __($guid, 'First Language') ?><?php if (isset($required["languageFirst"])) { if ($required["languageFirst"]=="Y") { print " *" ; } } ?></b><br/>
									<span class="emphasis small"><?php print __($guid, 'Student\'s native/first/mother language.') ?></span>
								</td>
								<td class="right">
									<select name="languageFirst" id="languageFirst" class="standardWidth">
										<?php
										if ($required["languageFirst"]=="Y") { print "<option value='Please select...'>" . __($guid, 'Please select...') . "</option>" ; } else { print "<option value=''></option>" ; }
										try {
											$dataSelect=array(); 
											$sqlSelect="SELECT name FROM gibbonLanguage ORDER BY name" ;
											$resultSelect=$connection2->prepare($sqlSelect);
											$resultSelect->execute($dataSelect);
										}
										catch(PDOException $e) { }
										while ($rowSelect=$resultSelect->fetch()) {
											$selected="" ;
											if ($row["languageFirst"]==$rowSelect["name"]) {
												$selected="selected" ;
											}
											print "<option $selected value='" . $rowSelect["name"] . "'>" . htmlPrep(__($guid, $rowSelect["name"])) . "</option>" ;
										}
										?>				
									</select>
									<?php
									$fieldName="languageFirst" ; 
									if (isset($required[$fieldName])) { if ($required[$fieldName]=="Y") {
										print "<script type=\"text/javascript\">" ;
											print "var " . $fieldName . "=new LiveValidation('" . $fieldName . "');" ;
											print $fieldName . ".add(Validate.Exclusion, { within: ['Please select...'], failureMessage: \"Select something!\"});" ;
										 print "</script>" ;
									} }
									?>								
								</td>
							</tr>
							<tr>
								<td> 
									<b><?php print __($guid, 'Second Language') ?><?php if (isset($required["languageSecond"])) { if ($required["languageSecond"]=="Y") { print " *" ; } }?></b><br/>
								</td>
								<td class="right">
									<select name="languageSecond" id="languageSecond" class="standardWidth">
										<?php
										if ($required["languageSecond"]=="Y") { print "<option value='Please select...'>" . __($guid, 'Please select...') . "</option>" ; } else { print "<option value=''></option>" ; }
										try {
											$dataSelect=array(); 
											$sqlSelect="SELECT name FROM gibbonLanguage ORDER BY name" ;
											$resultSelect=$connection2->prepare($sqlSelect);
											$resultSelect->execute($dataSelect);
										}
										catch(PDOException $e) { }
										while ($rowSelect=$resultSelect->fetch()) {
											$selected="" ;
											if ($row["languageSecond"]==$rowSelect["name"]) {
												$selected="selected" ;
											}
											print "<option $selected value='" . $rowSelect["name"] . "'>" . htmlPrep(__($guid, $rowSelect["name"])) . "</option>" ;
										}
										?>				
									</select>
									<?php
									$fieldName="languageSecond" ; 
									if (isset($required[$fieldName])) { if ($required[$fieldName]=="Y") {
										print "<script type=\"text/javascript\">" ;
											print "var " . $fieldName . "=new LiveValidation('" . $fieldName . "');" ;
											print $fieldName . ".add(Validate.Exclusion, { within: ['Please select...'], failureMessage: \"Select something!\"});" ;
										 print "</script>" ;
									} }
									?>									
								</td>
							</tr>
							<tr>
								<td> 
									<b><?php print __($guid, 'Third Language') ?><?php if (isset($required["languageThird"])) { if ($required["languageThird"]=="Y") { print " *" ; } } ?></b><br/>
								</td>
								<td class="right">
									<select name="languageThird" id="languageThird" class="standardWidth">
										<?php
										if ($required["languageThird"]=="Y") { print "<option value='Please select...'>" . __($guid, 'Please select...') . "</option>" ; } else { print "<option value=''></option>" ; }
										try {
											$dataSelect=array(); 
											$sqlSelect="SELECT name FROM gibbonLanguage ORDER BY name" ;
											$resultSelect=$connection2->prepare($sqlSelect);
											$resultSelect->execute($dataSelect);
										}
										catch(PDOException $e) { }
										while ($rowSelect=$resultSelect->fetch()) {
											$selected="" ;
											if ($row["languageThird"]==$rowSelect["name"]) {
												$selected="selected" ;
											}
											print "<option $selected value='" . $rowSelect["name"] . "'>" . htmlPrep(__($guid, $rowSelect["name"])) . "</option>" ;
										}
										?>				
									</select>
									<?php
									$fieldName="languageThird" ; 
									if (isset($required[$fieldName])) { if ($required[$fieldName]=="Y") {
										print "<script type=\"text/javascript\">" ;
											print "var " . $fieldName . "=new LiveValidation('" . $fieldName . "');" ;
											print $fieldName . ".add(Validate.Exclusion, { within: ['Please select...'], failureMessage: \"Select something!\"});" ;
										 print "</script>" ;
									} }
									?>									
								</td>
							</tr>
							<tr>
								<td> 
									<b><?php print __($guid, 'Country of Birth') ?><?php if (isset($required["countryOfBirth"])) { if ($required["countryOfBirth"]=="Y") { print " *" ; } } ?></b><br/>
								</td>
								<td class="right">
									<select name="countryOfBirth" id="countryOfBirth" class="standardWidth">
										<?php
										try {
											$dataSelect=array(); 
											$sqlSelect="SELECT printable_name FROM gibbonCountry ORDER BY printable_name" ;
											$resultSelect=$connection2->prepare($sqlSelect);
											$resultSelect->execute($dataSelect);
										}
										catch(PDOException $e) { }
										if ($required["countryOfBirth"]=="Y") { print "<option value='Please select...'>" . __($guid, 'Please select...') . "</option>" ; } else { print "<option value=''></option>" ; }
										while ($rowSelect=$resultSelect->fetch()) {
											$selected="" ;
											if ($row["countryOfBirth"]==$rowSelect["printable_name"]) {
												$selected="selected" ;
											}
											print "<option $selected value='" . $rowSelect["printable_name"] . "'>" . htmlPrep(__($guid, $rowSelect["printable_name"])) . "</option>" ;
										}
										?>				
									</select>
									<?php
									$fieldName="countryOfBirth" ; 
									if (isset($required[$fieldName])) { if ($required[$fieldName]=="Y") {
										print "<script type=\"text/javascript\">" ;
											print "var " . $fieldName . "=new LiveValidation('" . $fieldName . "');" ;
											print $fieldName . ".add(Validate.Exclusion, { within: ['Please select...'], failureMessage: \"Select something!\"});" ;
										 print "</script>" ;
									} }
									?>
								</td>
							</tr>
							<tr>
								<td> 
									<b><?php print __($guid, 'Ethnicity') ?><?php if (isset($required["ethnicity"])) { if ($required["ethnicity"]=="Y") { print " *" ; } } ?></b><br/>
								</td>
								<td class="right">
									<select name="ethnicity" id="ethnicity" class="standardWidth">
										<?php if ($required["ethnicity"]=="Y") { print "<option value='Please select...'>" . __($guid, 'Please select...') . "</option>" ; } else { print "<option value=''></option>" ; } ?>
										<?php
										$ethnicities=explode(",", getSettingByScope($connection2, "User Admin", "ethnicity")) ;
										foreach ($ethnicities as $ethnicity) {
											$selected="" ;
											if (trim($ethnicity)==$row["ethnicity"]) {
												$selected="selected" ;
											}
											print "<option $selected value='" . trim($ethnicity) . "'>" . trim($ethnicity) . "</option>" ;
										}
										?>
									</select>
									<?php
									$fieldName="ethnicity" ; 
									if (isset($required[$fieldName])) { if ($required[$fieldName]=="Y") {
										print "<script type=\"text/javascript\">" ;
											print "var " . $fieldName . "=new LiveValidation('" . $fieldName . "');" ;
											print $fieldName . ".add(Validate.Exclusion, { within: ['Please select...'], failureMessage: \"Select something!\"});" ;
										 print "</script>" ;
									} }
									?>
								</td>
							</tr>
							<tr>
								<td> 
									<b><?php print __($guid, 'Religion') ?><?php if (isset($required["religion"])) { if ($required["religion"]=="Y") { print " *" ; } } ?></b><br/>
								</td>
								<td class="right">
									<select name="religion" id="religion" class="standardWidth">
										<option <?php if ($row["religion"]=="") {print "selected ";}?>value=""></option>
										<?php
										$religions=explode(",", getSettingByScope($connection2, "User Admin", "religions")) ;
										foreach ($religions as $religion) {
											$selected="" ;
											if (trim($religion)==$row["religion"]) {
												$selected="selected" ;
											}
											print "<option $selected value='" . trim($religion) . "'>" . trim($religion) . "</option>" ;
										}
										?>
									</select>
									<?php
									$fieldName="religion" ; 
									if (isset($required[$fieldName])) { if ($required[$fieldName]=="Y") {
										print "<script type=\"text/javascript\">" ;
											print "var " . $fieldName . "=new LiveValidation('" . $fieldName . "');" ;
											print $fieldName . ".add(Validate.Exclusion, { within: ['Please select...'], failureMessage: \"Select something!\"});" ;
										 print "</script>" ;
									} }
									?>
								</td>
							</tr>
							<tr>
								<td> 
									<b><?php print __($guid, 'Citizenship 1') ?><?php if (isset($required["citizenship1"])) { if ($required["citizenship1"]=="Y") { print " *" ; } } ?></b><br/>
								</td>
								<td class="right">
									<select name="citizenship1" id="citizenship1" class="standardWidth">
										<?php
										if ($required["citizenship1"]=="Y") { print "<option value='Please select...'>" . __($guid, 'Please select...') . "</option>" ; } else { print "<option value=''></option>" ; }
										$nationalityList=getSettingByScope($connection2, "User Admin", "nationality") ;
										if ($nationalityList=="") {
											try {
												$dataSelect=array(); 
												$sqlSelect="SELECT printable_name FROM gibbonCountry ORDER BY printable_name" ;
												$resultSelect=$connection2->prepare($sqlSelect);
												$resultSelect->execute($dataSelect);
											}
											catch(PDOException $e) { }
											while ($rowSelect=$resultSelect->fetch()) {
												print "<option value='" . $rowSelect["printable_name"] . "'>" . htmlPrep(__($guid, $rowSelect["printable_name"])) . "</option>" ;
											}
										}
										else {
											$nationalities=explode(",", $nationalityList) ;
											foreach ($nationalities as $nationality) {
												$selected="" ;
												if (trim($nationality)==$row["citizenship1"]) {
													$selected="selected" ;
												}
												print "<option $selected value='" . trim($nationality) . "'>" . trim($nationality) . "</option>" ;
											}
										}
										?>					
									</select>
									<?php
									$fieldName="citizenship1" ; 
									if (isset($required[$fieldName])) { if ($required[$fieldName]=="Y") {
										print "<script type=\"text/javascript\">" ;
											print "var " . $fieldName . "=new LiveValidation('" . $fieldName . "');" ;
											print $fieldName . ".add(Validate.Exclusion, { within: ['Please select...'], failureMessage: \"Select something!\"});" ;
										 print "</script>" ;
									} }
									?>
								</td>
							</tr>
							<tr>
								<td> 
									<b><?php print __($guid, 'Citizenship 1 Passport Number') ?><?php if (isset($required["citizenship1Passport"])) { if ($required["citizenship1Passport"]=="Y") { print " *" ; } } ?></b><br/>
								</td>
								<td class="right">
									<input name="citizenship1Passport" id="citizenship1Passport" maxlength=30 value="<?php print htmlPrep($row["citizenship1Passport"]) ?>" type="text" class="standardWidth">
									<?php
									$fieldName="citizenship1Passport" ; 
									if (isset($required[$fieldName])) { if ($required[$fieldName]=="Y") {
										print "<script type=\"text/javascript\">" ;
											print "var " . $fieldName . "=new LiveValidation('" . $fieldName . "');" ;
											print $fieldName . ".add(Validate.Presence);" ;
										 print "</script>" ;
									} }
									?>									
								</td>
							</tr>
							<tr>
								<td> 
									<b><?php print __($guid, 'Citizenship 2') ?><?php if (isset($required["citizenshipr"])) { if ($required["citizenship2"]=="Y") { print " *" ; } } ?></b><br/>
								</td>
								<td class="right">
									<select name="citizenship2" id="citizenship2" class="standardWidth">
										<?php
										if ($required["citizenship2"]=="Y") { print "<option value='Please select...'>" . __($guid, 'Please select...') . "</option>" ; } else { print "<option value=''></option>" ; }
										$nationalityList=getSettingByScope($connection2, "User Admin", "nationality") ;
										if ($nationalityList=="") {
											try {
												$dataSelect=array(); 
												$sqlSelect="SELECT printable_name FROM gibbonCountry ORDER BY printable_name" ;
												$resultSelect=$connection2->prepare($sqlSelect);
												$resultSelect->execute($dataSelect);
											}
											catch(PDOException $e) { }
											while ($rowSelect=$resultSelect->fetch()) {
												print "<option value='" . $rowSelect["printable_name"] . "'>" . htmlPrep(__($guid, $rowSelect["printable_name"])) . "</option>" ;
											}
										}
										else {
											$nationalities=explode(",", $nationalityList) ;
											foreach ($nationalities as $nationality) {
												$selected="" ;
												if (trim($nationality)==$row["citizenship2"]) {
													$selected="selected" ;
												}
												print "<option $selected value='" . trim($nationality) . "'>" . trim($nationality) . "</option>" ;
											}
										}
										?>					
									</select>
									<?php
									$fieldName="citizenship2" ; 
									if (isset($required[$fieldName])) { if ($required[$fieldName]=="Y") {
										print "<script type=\"text/javascript\">" ;
											print "var " . $fieldName . "=new LiveValidation('" . $fieldName . "');" ;
											print $fieldName . ".add(Validate.Exclusion, { within: ['Please select...'], failureMessage: \"Select something!\"});" ;
										 print "</script>" ;
									} }
									?>
								</td>
							</tr>
							<tr>
								<td> 
									<b><?php print __($guid, 'Citizenship 2 Passport Number') ?><?php if (isset($required["citizenship2Passport"])) { if ($required["citizenship2Passport"]=="Y") { print " *" ; } } ?></b><br/>
								</td>
								<td class="right">
									<input name="citizenship2Passport" id="citizenship2Passport" maxlength=30 value="<?php print htmlPrep($row["citizenship2Passport"]) ?>" type="text" class="standardWidth">
									<?php
									$fieldName="citizenship2Passport" ; 
									if (isset($required[$fieldName])) { if ($required[$fieldName]=="Y") {
										print "<script type=\"text/javascript\">" ;
											print "var " . $fieldName . "=new LiveValidation('" . $fieldName . "');" ;
											print $fieldName . ".add(Validate.Presence);" ;
										 print "</script>" ;
									} }
									?>									
								</td>
							</tr>
							<tr>
								<td> 
									<?php
									$star="" ;
									if (isset($required["nationalIDCardNumber"])) { if ($required["nationalIDCardNumber"]=="Y") { 
										$star=" *" ; 
									} }
									if ($_SESSION[$guid]["country"]=="") {
										print "<b>" . __($guid, 'National ID Card Number') . $star . "</b><br/>" ;
									}
									else {
										print "<b>" . $_SESSION[$guid]["country"] . " " . __($guid, 'ID Card Number') . $star . "</b><br/>" ;
									}
									?>
								</td>
								<td class="right">
									<input name="nationalIDCardNumber" id="nationalIDCardNumber" maxlength=30 value="<?php print htmlPrep($row["nationalIDCardNumber"]) ?>" type="text" class="standardWidth">
									<?php
									$fieldName="nationalIDCardNumber" ; 
									if (isset($required[$fieldName])) { if ($required[$fieldName]=="Y") {
										print "<script type=\"text/javascript\">" ;
											print "var " . $fieldName . "=new LiveValidation('" . $fieldName . "');" ;
											print $fieldName . ".add(Validate.Presence);" ;
										 print "</script>" ;
									} }
									?>									
								</td>
							</tr>
							<tr>
								<td> 
									<?php
									$star="" ;
									if (isset($required["residencyStatus"])) { if ($required["residencyStatus"]=="Y") { 
										$star=" *" ; 
									} }
									if ($_SESSION[$guid]["country"]=="") {
										print "<b>" . __($guid, 'Residency/Visa Type') . $star . "</b><br/>" ;
									}
									else {
										print "<b>" . $_SESSION[$guid]["country"] . " " . __($guid, 'Residency/Visa Type') . $star . "</b><br/>" ;
									}
									?>
								</td>
								<td class="right">
									<?php
									$residencyStatusList=getSettingByScope($connection2, "User Admin", "residencyStatus") ;
									if ($residencyStatusList=="") {
										print "<input name='residencyStatus' id='residencyStatus' maxlength=30 value='" . $row["residencyStatus"] . "' type='text' style='width: 300px'>" ;
										$fieldName="residencyStatus" ; 
										if (isset($required[$fieldName])) { if ($required[$fieldName]=="Y") {
											print "<script type=\"text/javascript\">" ;
												print "var " . $fieldName . "=new LiveValidation('" . $fieldName . "');" ;
												print $fieldName . ".add(Validate.Presence);" ;
											 print "</script>" ;
										} }
									}
									else {
										print "<select name='residencyStatus' id='residencyStatus' style='width: 302px'>" ;
											if ($required["residencyStatus"]=="Y") { print "<option value='Please select...'>" . __($guid, 'Please select...') . "</option>" ; } else { print "<option value=''></option>" ; }
											$residencyStatuses=explode(",", $residencyStatusList) ;
											foreach ($residencyStatuses as $residencyStatus) {
												$selected="" ;
												if (trim($residencyStatus)==$row["residencyStatus"]) {
													$selected="selected" ;
												}
												print "<option $selected value='" . trim($residencyStatus) . "'>" . trim($residencyStatus) . "</option>" ;
											}
										print "</select>" ;
										$fieldName="residencyStatus" ; 
										if (isset($required[$fieldName])) { if ($required[$fieldName]=="Y") {
											print "<script type=\"text/javascript\">" ;
												print "var " . $fieldName . "=new LiveValidation('" . $fieldName . "');" ;
												print $fieldName . ".add(Validate.Exclusion, { within: ['Please select...'], failureMessage: \"Select something!\"});" ;
											 print "</script>" ;
										} }
									}
									?>
								</td>
							</tr>
							<tr>
								<td> 
									<?php
									$star="" ;
									if (isset($required["visaExpiryDate"])) { if ($required["visaExpiryDate"]=="Y") { 
										$star=" *" ; 
									} }
									if ($_SESSION[$guid]["country"]=="") {
										print "<b>" . __($guid, 'Visa Expiry Date') . $star . "</b><br/>" ;
									}
									else {
										print "<b>" . $_SESSION[$guid]["country"] . " " . __($guid, 'Visa Expiry Date') . $star . "</b><br/>" ;
									}
									print "<span style='font-size: 90%'><i>Format: " ; if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; } print ". " . __($guid, 'If relevant.') . "</span>" ;
									?>
								</td>
								<td class="right">
									<input name="visaExpiryDate" id="visaExpiryDate" maxlength=10 value="<?php print dateConvertBack($guid, $row["visaExpiryDate"]) ?>" type="text" class="standardWidth">
									<script type="text/javascript">
										var visaExpiryDate=new LiveValidation('visaExpiryDate');
										visaExpiryDate.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]["i18n"]["dateFormatRegEx"]=="") {  print "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i" ; } else { print $_SESSION[$guid]["i18n"]["dateFormatRegEx"] ; } ?>, failureMessage: "Use <?php if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?>." } ); 
									 	<?php
									 	if ($required["visaExpiryDate"]=="Y") {
											print "visaExpiryDate.add(Validate.Presence);" ;
										}
										?>
									</script>
									 <script type="text/javascript">
										$(function() {
											$( "#visaExpiryDate" ).datepicker();
										});
									</script>
								</td>
							</tr>
					
							<?php
							if ($parent) {
								?> 
								<tr class='break'>
									<td colspan=2> 
										<h3><?php print __($guid, 'Employment') ?></h3>
									</td>
								</tr>
								<tr>
									<td> 
										<b><?php print __($guid, 'Profession') ?><?php if (isset($required["profession"])) { if ($required["profession"]=="Y") { print " *" ; } } ?></b><br/>
									</td>
									<td class="right">
										<input name="profession" id="profession" maxlength=30 value="<?php print htmlPrep($row["profession"]) ?>" type="text" class="standardWidth">
										<?php
										$fieldName="profession" ; 
										if (isset($required[$fieldName])) { if ($required[$fieldName]=="Y") {
											print "<script type=\"text/javascript\">" ;
												print "var " . $fieldName . "=new LiveValidation('" . $fieldName . "');" ;
												print $fieldName . ".add(Validate.Presence);" ;
											 print "</script>" ;
										} }
										?>									
									</td>
								</tr>
								<tr>
									<td> 
										<b><?php print __($guid, 'Employer') ?><?php if (isset($required["employer"])) { if ($required["employer"]=="Y") { print " *" ; } } ?></b><br/>
									</td>
									<td class="right">
										<input name="employer" id="employer" maxlength=30 value="<?php print htmlPrep($row["employer"]) ?>" type="text" class="standardWidth">
										<?php
										$fieldName="employer" ; 
										if (isset($required[$fieldName])) { if ($required[$fieldName]=="Y") {
											print "<script type=\"text/javascript\">" ;
												print "var " . $fieldName . "=new LiveValidation('" . $fieldName . "');" ;
												print $fieldName . ".add(Validate.Presence);" ;
											 print "</script>" ;
										} }
										?>									
									</td>
								</tr>
								<tr>
									<td> 
										<b><?php print __($guid, 'Job Title') ?><?php if (isset($required["jobTitle"])) { if ($required["jobTitle"]=="Y") { print " *" ; } } ?></b><br/>
									</td>
									<td class="right">
										<input name="jobTitle" id="jobTitle" maxlength=30 value="<?php print htmlPrep($row["jobTitle"]) ?>" type="text" class="standardWidth">
										<?php
										$fieldName="jobTitle" ; 
										if (isset($required[$fieldName])) { if ($required[$fieldName]=="Y") {
											print "<script type=\"text/javascript\">" ;
												print "var " . $fieldName . "=new LiveValidation('" . $fieldName . "');" ;
												print $fieldName . ".add(Validate.Presence);" ;
											 print "</script>" ;
										} }
										?>									
									</td>
								</tr>
								<?php
							}
							?>
							
							<tr class='break'>
								<td colspan=2> 
									<h3><?php print __($guid, 'Miscellaneous') ?></h3>
								</td>
							</tr>
							<tr>
								<td> 
									<b><?php print __($guid, 'Vehicle Registration') ?><?php if (isset($required["vehicleRegistration"])) { if ($required["vehicleRegistration"]=="Y") { print " *" ; } } ?></b><br/>
								</td>
								<td class="right">
									<input name="vehicleRegistration" id="vehicleRegistration" maxlength=30 value="<?php print htmlPrep($row["vehicleRegistration"]) ?>" type="text" class="standardWidth">
									<?php
									$fieldName="vehicleRegistration" ; 
									if (isset($required[$fieldName])) { if ($required[$fieldName]=="Y") {
										print "<script type=\"text/javascript\">" ;
											print "var " . $fieldName . "=new LiveValidation('" . $fieldName . "');" ;
											print $fieldName . ".add(Validate.Presence);" ;
										 print "</script>" ;
									} }
									?>									
								</td>
							</tr>
							<?php
							//Check if any roles are "Student"
							$privacySet=false ;
							if ($student) {
								$privacySetting=getSettingByScope( $connection2, "User Admin", "privacy" ) ;
								$privacyBlurb=getSettingByScope( $connection2, "User Admin", "privacyBlurb" ) ;
								$privacyOptions=getSettingByScope( $connection2, "User Admin", "privacyOptions" ) ;
								if ($privacySetting=="Y" AND $privacyBlurb!="" AND $privacyOptions!="") {
									?>
									<tr>
										<td> 
											<b><?php print __($guid, 'Privacy') ?></b><br/>
											<span class="emphasis small"><?php print htmlPrep($privacyBlurb) ?><br/>
											</span>
										</td>
										<td class="right">
											<?php
											$options=explode(",",$privacyOptions) ;
											$privacyChecks=explode(",",$row["privacy"]) ;
											foreach ($options AS $option) {
												$checked="" ;
												foreach ($privacyChecks AS $privacyCheck) {
													if ($option==$privacyCheck) {
														$checked="checked" ;
													}
												}
												print $option . " <input $checked type='checkbox' name='privacyOptions[]' value='" . htmlPrep($option) . "'/><br/>" ;
											}
											?>
					
										</td>
									</tr>
									<?php
								}
							}
							
							//CUSTOM FIELDS
							$fields=unserialize($row["fields"]) ;
							$resultFields=getCustomFields($connection2, $guid, $student, $staff, $parent, $other, NULL, TRUE) ;
							if ($resultFields->rowCount()>0) {
								?>
								<tr class='break'>
									<td colspan=2> 
										<h3><?php print __($guid, 'Custom Fields') ?></h3>
									</td>
								</tr>
								<?php
								while ($rowFields=$resultFields->fetch()) {
									$value="" ;
									if (isset($fields[$rowFields["gibbonPersonFieldID"]])) {
										$value=$fields[$rowFields["gibbonPersonFieldID"]] ;
									} 
									print renderCustomFieldRow($connection2, $guid, $rowFields, $value) ;	
								}
							}
							?>
							
							<tr>
								<td>
									<span class="emphasis small">* <?php print __($guid, "denotes a required field") ; ?></span>
								</td>
								<td class="right">
									<?php
									if ($existing) {
										print "<input type='hidden' name='existing' value='" . $row["gibbonPersonUpdateID"] . "'>" ;
									}
									else {
										print "<input type='hidden' name='existing' value='N'>" ;
									}
									?>
									<input type="hidden" name="address" value="<?php print $_SESSION[$guid]["address"] ?>">
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
?>