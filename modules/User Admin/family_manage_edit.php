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

if (isActionAccessible($guid, $connection2, "/modules/User Admin/family_manage_edit.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/User Admin/family_manage.php'>" . __($guid, 'Manage Families') . "</a> > </div><div class='trailEnd'>" . __($guid, 'Edit Family') . "</div>" ;
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
		else if ($updateReturn=="fail4") {
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
	
	if (isset($_GET["addReturn"])) { $addReturn=$_GET["addReturn"] ; } else { $addReturn="" ; }
	$addReturnMessage="" ;
	$class="error" ;
	if (!($addReturn=="")) {
		if ($addReturn=="fail0") {
			$addReturnMessage=__($guid, "Your request failed because you do not have access to this action.") ;	
		}
		else if ($addReturn=="fail1") {
			$addReturnMessage=__($guid, "Your request failed because your inputs were invalid.") ;	
		}
		else if ($addReturn=="fail2") {
			$addReturnMessage=__($guid, "Your request failed due to a database error.") ;	
		}
		else if ($addReturn=="fail3") {
			$addReturnMessage=__($guid, "Your request failed because your inputs were invalid.") ;	
		}
		else if ($addReturn=="fail4") {
			$addReturnMessage=__($guid, "Your request failed because the person already exists as a member of this family.") ;	
		}
		else if ($addReturn=="success0") {
			$addReturnMessage=__($guid, "Your request was completed successfully.") ;	
			$class="success" ;
		}
		print "<div class='$class'>" ;
			print $addReturnMessage;
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
	
	//Check if school year specified
	$gibbonFamilyID=$_GET["gibbonFamilyID"] ;
	$search=NULL ;
	if (isset($_GET["search"])) {
		$search=$_GET["search"] ;
	}
	if ($gibbonFamilyID=="") {
		print "<h1>" ;
		print __($guid, "Edit Family") ;
		print "</h1>" ;
		print "<div class='error'>" ;
			print __($guid, "You have not specified one or more required parameters.") ;
		print "</div>" ;
	}
	else {
		try {
			$data=array("gibbonFamilyID"=>$gibbonFamilyID); 
			$sql="SELECT * FROM gibbonFamily WHERE gibbonFamilyID=:gibbonFamilyID" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
		}
		
		if ($result->rowCount()!=1) {
			print "<h1>" ;
			print "Edit Family" ;
			print "</h1>" ;
			print "<div class='error'>" ;
				print __($guid, "The specified record cannot be found.") ;
			print "</div>" ;
		}
		else {
			//Let's go!
			$row=$result->fetch() ;
			
			if ($search!="") {
				print "<div class='linkTop'>" ;
					print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/User Admin/family_manage.php&search=$search'>" . __($guid, 'Back to Search Results') . "</a>" ;
				print "</div>" ;
			}
			?>
			
			<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/family_manage_editProcess.php?gibbonFamilyID=$gibbonFamilyID&search=$search" ?>">
				<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
					<tr class='break'>
						<td colspan=2> 
							<h3>
								<?php print __($guid, 'General Information') ?>
							</h3>
						</td>
					</tr>
					<tr>
						<td style='width: 275px'> 
							<b><?php print __($guid, 'Family Name') ?> *</b><br/>
							<span style="font-size: 90%"><i></i></span>
						</td>
						<td class="right">
							<input name="name" id="name" maxlength=100 value="<?php print $row["name"] ?>" type="text" style="width: 300px">
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print __($guid, 'Status') ?></b><br/>
						</td>
						<td class="right">
							<select name="status" id="status" style="width: 302px">
								<option <?php if ($row["status"]=="Married") { print "selected " ; } ?>value="Married"><?php print __($guid, 'Married') ?></option>
								<option <?php if ($row["status"]=="Separated") { print "selected " ; } ?>value="Separated"><?php print __($guid, 'Separated') ?></option>
								<option <?php if ($row["status"]=="Divorced") { print "selected " ; } ?>value="Divorced"><?php print __($guid, 'Divorced') ?></option>
								<option <?php if ($row["status"]=="De Facto") { print "selected " ; } ?>value="De Facto"><?php print __($guid, 'De Facto') ?></option>
								<option <?php if ($row["status"]=="Other") { print "selected " ; } ?>value="Other"><?php print __($guid, 'Other') ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print __($guid, 'Home Language - Primary') ?></b><br/>
						</td>
						<td class="right">
							<select name="languageHomePrimary" id="languageHomePrimary" style="width: 302px">
								<?php
								print "<option value=''></option>" ;
								try {
									$dataSelect=array(); 
									$sqlSelect="SELECT name FROM gibbonLanguage ORDER BY name" ;
									$resultSelect=$connection2->prepare($sqlSelect);
									$resultSelect->execute($dataSelect);
								}
								catch(PDOException $e) { }
								while ($rowSelect=$resultSelect->fetch()) {
									$selected="" ;
									if ($row["languageHomePrimary"]==$rowSelect["name"]) {
										$selected="selected" ;
									}
									print "<option $selected value='" . $rowSelect["name"] . "'>" . htmlPrep(__($guid, $rowSelect["name"])) . "</option>" ;
								}
								?>				
							</select>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print __($guid, 'Home Language - Secondary') ?></b><br/>
						</td>
						<td class="right">
							<select name="languageHomeSecondary" id="languageHomeSecondary" style="width: 302px">
								<?php
								print "<option value=''></option>" ;
								try {
									$dataSelect=array(); 
									$sqlSelect="SELECT name FROM gibbonLanguage ORDER BY name" ;
									$resultSelect=$connection2->prepare($sqlSelect);
									$resultSelect->execute($dataSelect);
								}
								catch(PDOException $e) { }
								while ($rowSelect=$resultSelect->fetch()) {
									$selected="" ;
									if ($row["languageHomeSecondary"]==$rowSelect["name"]) {
										$selected="selected" ;
									}
									print "<option $selected value='" . $rowSelect["name"] . "'>" . htmlPrep(__($guid, $rowSelect["name"])) . "</option>" ;
								}
								?>				
							</select>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print __($guid, 'Address Name') ?> *</b><br/>
							<span style="font-size: 90%"><i><?php print __($guid, 'Formal name to address parents with.') ?></i></span>
						</td>
						<td class="right">
							<input name="nameAddress" id="nameAddress" maxlength=100 value="<?php print $row["nameAddress"] ?>" type="text" style="width: 300px">
							<script type="text/javascript">
								var nameAddress=new LiveValidation('nameAddress');
								nameAddress.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print __($guid, 'Home Address') ?></b><br/>
							<span style="font-size: 90%"><i><?php print __($guid, 'Unit, Building, Street') ?></i></span>
						</td>
						<td class="right">
							<input name="homeAddress" id="homeAddress" maxlength=255 value="<?php print $row["homeAddress"] ?>" type="text" style="width: 300px">
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print __($guid, 'Home Address (District)') ?></b><br/>
							<span style="font-size: 90%"><i><?php print __($guid, 'County, State, District') ?></i></span>
						</td>
						<td class="right">
							<input name="homeAddressDistrict" id="homeAddressDistrict" maxlength=30 value="<?php print $row["homeAddressDistrict"] ?>" type="text" style="width: 300px">
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
								$( "#homeAddressDistrict" ).autocomplete({source: availableTags});
							});
						</script>
					</tr>
					<tr>
						<td> 
							<b><?php print __($guid, 'Home Address (Country)') ?></b><br/>
						</td>
						<td class="right">
							<select name="homeAddressCountry" id="homeAddressCountry" style="width: 302px">
								<?php
								print "<option value=''></option>" ;
								try {
									$dataSelect=array(); 
									$sqlSelect="SELECT printable_name FROM gibbonCountry ORDER BY printable_name" ;
									$resultSelect=$connection2->prepare($sqlSelect);
									$resultSelect->execute($dataSelect);
								}
								catch(PDOException $e) { }
								while ($rowSelect=$resultSelect->fetch()) {
									$selected="" ;
									if ($rowSelect["printable_name"]==$row["homeAddressCountry"]) {
										$selected=" selected" ;
									}
									print "<option $selected value='" . $rowSelect["printable_name"] . "'>" . htmlPrep(__($guid, $rowSelect["printable_name"])) . "</option>" ;
								}
								?>				
							</select>
						</td>
					</tr>
					<tr>
						<td>
							<span style="font-size: 90%"><i>* <?php print __($guid, "denotes a required field") ; ?></i></span>
						</td>
						<td class="right">
							<input type="hidden" name="address" value="<?php print $_SESSION[$guid]["address"] ?>">
							<input type="submit" value="<?php print __($guid, "Submit") ; ?>">
						</td>
					</tr>
				</table>
			</form>
			
			<?php
			//Get children and prep array
			try {
				$dataChildren=array("gibbonFamilyID"=>$gibbonFamilyID); 
				$sqlChildren="SELECT * FROM gibbonFamilyChild JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonFamilyID=:gibbonFamilyID ORDER BY surname, preferredName" ;
				$resultChildren=$connection2->prepare($sqlChildren);
				$resultChildren->execute($dataChildren);
			}
			catch(PDOException $e) { 
				print "<div class='error'>" . $e->getMessage() . "</div>" ; 
			}
			$children=array() ;
			$count=0 ;
			while ($rowChildren=$resultChildren->fetch()) {
				$children[$count]["image_240"]=$rowChildren["image_240"] ;
				$children[$count]["gibbonPersonID"]=$rowChildren["gibbonPersonID"] ;
				$children[$count]["preferredName"]=$rowChildren["preferredName"] ;
				$children[$count]["surname"]=$rowChildren["surname"] ;
				$children[$count]["status"]=$rowChildren["status"] ;
				$children[$count]["comment"]=$rowChildren["comment"] ;
				$count++ ;
			}
			//Get adults and prep array
			try {
				$dataAdults=array("gibbonFamilyID"=>$gibbonFamilyID); 
				$sqlAdults="SELECT * FROM gibbonFamilyAdult, gibbonPerson WHERE (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) AND gibbonFamilyID=:gibbonFamilyID ORDER BY contactPriority, surname, preferredName" ; 
				$resultAdults=$connection2->prepare($sqlAdults);
				$resultAdults->execute($dataAdults);
			}
			catch(PDOException $e) { 
				print "<div class='error'>" . $e->getMessage() . "</div>" ; 
			}
			$adults=array() ;
			$count=0 ;
			while ($rowAdults=$resultAdults->fetch()) {
				$adults[$count]["image_240"]=$rowAdults["image_240"] ;
				$adults[$count]["gibbonPersonID"]=$rowAdults["gibbonPersonID"] ;
				$adults[$count]["title"]=$rowAdults["title"] ;
				$adults[$count]["preferredName"]=$rowAdults["preferredName"] ;
				$adults[$count]["surname"]=$rowAdults["surname"] ;
				$adults[$count]["status"]=$rowAdults["status"] ;
				$adults[$count]["comment"]=$rowAdults["comment"] ;
				$adults[$count]["childDataAccess"]=$rowAdults["childDataAccess"] ;
				$adults[$count]["contactPriority"]=$rowAdults["contactPriority"] ;
				$adults[$count]["contactCall"]=$rowAdults["contactCall"] ;
				$adults[$count]["contactSMS"]=$rowAdults["contactSMS"] ;
				$adults[$count]["contactEmail"]=$rowAdults["contactEmail"] ;
				$adults[$count]["contactMail"]=$rowAdults["contactMail"] ;
				$count++ ;
			}
			
			//Get relationships and prep array
			try {
				$dataRelationships=array("gibbonFamilyID"=>$gibbonFamilyID); 
				$sqlRelationships="SELECT * FROM gibbonFamilyRelationship WHERE gibbonFamilyID=:gibbonFamilyID" ; 
				$resultRelationships=$connection2->prepare($sqlRelationships);
				$resultRelationships->execute($dataRelationships);
			}
			catch(PDOException $e) { 
				print "<div class='error'>" . $e->getMessage() . "</div>" ; 
			}
			$relationships=array() ;
			$count=0 ;
			while ($rowRelationships=$resultRelationships->fetch()) {
				$relationships[$rowRelationships["gibbonPersonID1"]][$rowRelationships["gibbonPersonID2"]]=$rowRelationships["relationship"] ;
				$count++ ;
			}

			
			print "<h3>" ;
			print __($guid, "Relationships") ;
			print "</h3>" ;
			print "<p>" ;
			print __($guid, "Use the table below to show how each child is related to each adult in the family.") ;
			print "</p>" ;
			if ($resultChildren->rowCount()<1 OR $resultAdults->rowCount()<1) {
				print "<div class='error'>" . __($guid, 'There are not enough people in this family to form relationships.') . "</div>" ; 
			}			
			else {
				print "<form method='post' action='" . $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/family_manage_edit_relationshipsProcess.php?gibbonFamilyID=$gibbonFamilyID&search=$search'>" ;
					print "<table cellspacing='0' style='width: 100%'>" ;
						print "<tr class='head'>" ;
							print "<th>" ;
								print __($guid, "Adults") ;
							print "</th>" ;
							foreach ($children AS $child) {
								print "<th>" ;
									print formatName("", $child["preferredName"], $child["surname"], "Student") ;
								print "</th>" ;
							}
						print "</tr>" ;
						$count=0 ;
						foreach ($adults AS $adult) {
							if ($count%2==0) {
								$rowNum="even" ;
							}
							else {
								$rowNum="odd" ;
							}
							$count++ ;
							print "<tr class='$rowNum'>" ;
								print "<td>" ;
									print "<b>" . formatName($adult["title"], $adult["preferredName"], $adult["surname"], "Parent") . "<b>" ;
								print "</td>" ;
								foreach ($children AS $child) {
									print "<td>" ;
										?>
										<select name="relationships[]" id="relationships[]" style="width: 100%">
											<option <?php if (@$relationships[$adult["gibbonPersonID"]][$child["gibbonPersonID"]]=="") { print "selected" ; } ?> value=""></option>
											<option <?php if (@$relationships[$adult["gibbonPersonID"]][$child["gibbonPersonID"]]=="Mother") { print "selected" ; } ?> value="Mother"><?php print __($guid, 'Mother') ?></option>
											<option <?php if (@$relationships[$adult["gibbonPersonID"]][$child["gibbonPersonID"]]=="Father") { print "selected" ; } ?> value="Father"><?php print __($guid, 'Father') ?></option>
											<option <?php if (@$relationships[$adult["gibbonPersonID"]][$child["gibbonPersonID"]]=="Step-Mother") { print "selected" ; } ?> value="Step-Mother"><?php print __($guid, 'Step-Mother') ?></option>
											<option <?php if (@$relationships[$adult["gibbonPersonID"]][$child["gibbonPersonID"]]=="Step-Father") { print "selected" ; } ?> value="Step-Father"><?php print __($guid, 'Step-Father') ?></option>
											<option <?php if (@$relationships[$adult["gibbonPersonID"]][$child["gibbonPersonID"]]=="Adoptive Parent") { print "selected" ; } ?> value="Adoptive Parent"><?php print __($guid, 'Adoptive Parent') ?></option>
											<option <?php if (@$relationships[$adult["gibbonPersonID"]][$child["gibbonPersonID"]]=="Guardian") { print "selected" ; } ?> value="Guardian"><?php print __($guid, 'Guardian') ?></option>
											<option <?php if (@$relationships[$adult["gibbonPersonID"]][$child["gibbonPersonID"]]=="Grandmother") { print "selected" ; } ?> value="Grandmother"><?php print __($guid, 'Grandmother') ?></option>
											<option <?php if (@$relationships[$adult["gibbonPersonID"]][$child["gibbonPersonID"]]=="Grandfather") { print "selected" ; } ?> value="Grandfather"><?php print __($guid, 'Grandfather') ?></option>
											<option <?php if (@$relationships[$adult["gibbonPersonID"]][$child["gibbonPersonID"]]=="Aunt") { print "selected" ; } ?> value="Aunt"><?php print __($guid, 'Aunt') ?></option>
											<option <?php if (@$relationships[$adult["gibbonPersonID"]][$child["gibbonPersonID"]]=="Uncle") { print "selected" ; } ?> value="Uncle"><?php print __($guid, 'Uncle') ?></option>
											<option <?php if (@$relationships[$adult["gibbonPersonID"]][$child["gibbonPersonID"]]=="Nanny/Helper") { print "selected" ; } ?> value="Nanny/Helper"><?php print __($guid, 'Nanny/Helper') ?></option>
											<option <?php if (@$relationships[$adult["gibbonPersonID"]][$child["gibbonPersonID"]]=="Other") { print "selected" ; } ?> value="Other"><?php print __($guid, 'Other') ?></option>
										</select>
										<input type="hidden" name="gibbonPersonID1[]" value="<?php print $adult["gibbonPersonID"] ?>">
										<input type="hidden" name="gibbonPersonID2[]" value="<?php print $child["gibbonPersonID"] ?>">
										<?php
									print "</td>" ;
								}
							print "</tr>" ;
						}
						?>
						<tr><td colspan="<?php print (count($children)+1) ?>" class="right">
							<input type="hidden" name="address" value="<?php print $_SESSION[$guid]["address"] ?>">
							<input type="submit" value="<?php print __($guid, "Submit") ; ?>">
						</td></tr>
						<?php
					print "</table>" ;
				print "</form>" ;
			}
			
			print "<h3>" ;
			print __($guid, "View Children") ;
			print "</h3>" ;
			
			
			if ($resultChildren->rowCount()<1) {
				print "<div class='error'>" ;
				print __($guid, "There are no records to display.") ;
				print "</div>" ;
			}
			else {
				print "<table cellspacing='0' style='width: 100%'>" ;
					print "<tr class='head'>" ;
						print "<th>" ;
							print __($guid, "Photo") ;
						print "</th>" ;
						print "<th>" ;
							print __($guid, "Name") ;
						print "</th>" ;
						print "<th>" ;
							print __($guid, "Status") ;
						print "</th>" ;
						print "<th>" ;
							print __($guid, "Roll Group") ;
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
					foreach ($children AS $child) {
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
								print getUserPhoto($guid, $child["image_240"], 75) ;
							print "</td>" ;
							print "<td>" ;
								print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/User Admin/user_manage_edit.php&gibbonPersonID=" . $child["gibbonPersonID"] . "'>" . formatName("", $child["preferredName"], $child["surname"], "Student") . "</a>" ;
							print "</td>" ;
							print "<td>" ;
								print $child["status"] ;
							print "</td>" ;
							print "<td>" ;
								try {
									$dataDetail=array("gibbonPersonID"=>$child["gibbonPersonID"], "gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
									$sqlDetail="SELECT * FROM gibbonRollGroup JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE gibbonPersonID=:gibbonPersonID AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID" ;
									$resultDetail=$connection2->prepare($sqlDetail);
									$resultDetail->execute($dataDetail);
								}
								catch(PDOException $e) { 
									print "<div class='error'>" . $e->getMessage() . "</div>" ; 
								}
								if ($resultDetail->rowCount()==1) {
									$rowDetail=$resultDetail->fetch() ;
									print $rowDetail["name"] ;
								}
							print "</td>" ;
							print "<td>" ;
								print nl2brr($child["comment"]) ;
							print "</td>" ;
							print "<td>" ;
								print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/family_manage_edit_editChild.php&gibbonFamilyID=$gibbonFamilyID&gibbonPersonID=" . $child["gibbonPersonID"] . "&search=$search'><img title='" . __($guid, 'Edit') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/config.png'/></a> " ;
								print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/family_manage_edit_deleteChild.php&gibbonFamilyID=$gibbonFamilyID&gibbonPersonID=" . $child["gibbonPersonID"] . "&search=$search'><img title='" . __($guid, 'Delete') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/garbage.png'/></a>" ;
								print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/user_manage_password.php&gibbonPersonID=" . $child["gibbonPersonID"] . "&search=$search'><img title='" .__($guid, 'Change Password') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/key.png'/></a>" ;
							print "</td>" ;
						print "</tr>" ;
					}
				print "</table>" ;
			}
			
			?>
			<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/family_manage_edit_addChildProcess.php?gibbonFamilyID=$gibbonFamilyID&search=$search" ?>">
				<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
					<tr class='break'>
						<td colspan=2>
							<h3>
							<?php print __($guid, 'Add Child') ?>
							</h3>
						</td>
					</tr>
					<tr>
						<td style='width: 275px'> 
							<b><?php print __($guid, 'Child\'s Name') ?> *</b><br/>
							<span style="font-size: 90%"><i></i></span>
						</td>
						<td class="right">
							<select name="gibbonPersonID" id="gibbonPersonID" style="width: 302px">
								<?php
								print "<option value='Please select...'>" . __($guid, 'Please select...') . "</option>" ;
								?>
								<optgroup label='--<?php print __($guid, 'Enroled Students') ?>--'>
								<?php
								try {
									$dataSelect=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
									$sqlSelect="SELECT gibbonPerson.gibbonPersonID, preferredName, surname, gibbonRollGroup.name AS name FROM gibbonPerson, gibbonStudentEnrolment, gibbonRollGroup WHERE gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID AND gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID AND status='FULL' AND gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY name, surname, preferredName" ;
									$resultSelect=$connection2->prepare($sqlSelect);
									$resultSelect->execute($dataSelect);
								}
								catch(PDOException $e) { }
								while ($rowSelect=$resultSelect->fetch()) {
									print "<option value='" . $rowSelect["gibbonPersonID"] . "'>" . htmlPrep($rowSelect["name"]) . " - " . formatName("", htmlPrep($rowSelect["preferredName"]), htmlPrep($rowSelect["surname"]), "Student") . "</option>" ;
								}
								?>
								</optgroup>
								<optgroup label='--<?php print __($guid, 'All Users') ?>--'>
								<?php
								try {
									$dataSelect=array(); 
									$sqlSelect="SELECT * FROM gibbonPerson WHERE status='Full' OR status='Expected' ORDER BY surname, preferredName" ;
									$resultSelect=$connection2->prepare($sqlSelect);
									$resultSelect->execute($dataSelect);
								}
								catch(PDOException $e) { }
								while ($rowSelect=$resultSelect->fetch()) {
									$expected="" ;
									if ($rowSelect["status"]=="Expected") {
										$expected=" (Expected)" ;
									}
									print "<option value='" . $rowSelect["gibbonPersonID"] . "'>" . formatName("", htmlPrep($rowSelect["preferredName"]), htmlPrep($rowSelect["surname"]), "Student", true) . " (" . $rowSelect["username"] . ")" . $expected . "</option>" ;
								}
								?>
							</select>
							<script type="text/javascript">
								var gibbonPersonID=new LiveValidation('gibbonPersonID');
								gibbonPersonID.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print __($guid, 'Select something!') ?>"});
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print __($guid, 'Comment') ?></b><br/>
						</td>
						<td class="right">
							<textarea name="comment" id="comment" rows=8 style="width: 300px"></textarea>
						</td>
					</tr>
					</tr>
					<tr>
						<td>
							<span style="font-size: 90%"><i>* <?php print __($guid, "denotes a required field") ; ?></i></span>
						</td>
						<td class="right">
							<input type="hidden" name="address" value="<?php print $_SESSION[$guid]["address"] ?>">
							<input type="submit" value="<?php print __($guid, "Submit") ; ?>">
						</td>
					</tr>
				</table>
			</form>

			<?php	
			print "<h3>" ;
			print __($guid, "View Adults") ;
			print "</h3>" ;
			print "<div class='warning'>" ;
				print __($guid, "Logic exists to try and ensure that there is always one and only one parent with Contact Priority set to 1. This may result in values being set which are not exactly what you chose.") ;
			print "</div>" ;
			
			if ($resultAdults->rowCount()<1) {
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
							print __($guid, "Status") ;
						print "</th>" ;
						print "<th>" ;
							print __($guid, "Comment") ;
						print "</th>" ;
						print "<th style='max-width: 50px; padding-left: 1px; padding-right: 1px; height: 100px'>" ;
							print "<div style='-webkit-transform: rotate(-90deg); -moz-transform: rotate(-90deg); -ms-transform: rotate(-90deg); -o-transform: rotate(-90deg); transform: rotate(-90deg);'>" .__($guid, 'Data Access') . "</div>" ;
						print "</th>" ;
						print "<th style='max-width: 50px; padding-left: 1px; padding-right: 1px'>" ;
							print "<div style='-webkit-transform: rotate(-90deg); -moz-transform: rotate(-90deg); -ms-transform: rotate(-90deg); -o-transform: rotate(-90deg); transform: rotate(-90deg);'>" .__($guid, 'Contact Priority') . "</div>" ;
						print "</th>" ;
						print "<th style='max-width: 50px; padding-left: 1px; padding-right: 1px'>" ;
							print "<div style='-webkit-transform: rotate(-90deg); -moz-transform: rotate(-90deg); -ms-transform: rotate(-90deg); -o-transform: rotate(-90deg); transform: rotate(-90deg);'>" .__($guid, 'Contact By Phone') . "</div>" ;
						print "</th>" ;
						print "<th style='max-width: 50px; padding-left: 1px; padding-right: 1px'>" ;
							print "<div style='-webkit-transform: rotate(-90deg); -moz-transform: rotate(-90deg); -ms-transform: rotate(-90deg); -o-transform: rotate(-90deg); transform: rotate(-90deg);'>" .__($guid, 'Contact By SMS') . "</div>" ;
						print "</th>" ;
						print "<th style='max-width: 50px; padding-left: 1px; padding-right: 1px'>" ;
							print "<div style='-webkit-transform: rotate(-90deg); -moz-transform: rotate(-90deg); -ms-transform: rotate(-90deg); -o-transform: rotate(-90deg); transform: rotate(-90deg);'>" .__($guid, 'Contact By Email') . "</div>" ;
						print "</th>" ;
						print "<th style='max-width: 50px; padding-left: 1px; padding-right: 1px'>" ;
							print "<div style='-webkit-transform: rotate(-90deg); -moz-transform: rotate(-90deg); -ms-transform: rotate(-90deg); -o-transform: rotate(-90deg); transform: rotate(-90deg);'>" .__($guid, 'Contact By Mail') . "</div>" ;
						print "</th>" ;
						print "<th>" ;
							print __($guid, "Actions") ;
						print "</th>" ;
					print "</tr>" ;
					
					$count=0;
					$rowNum="odd" ;
					foreach ($adults AS $adult) {
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
								print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/User Admin/user_manage_edit.php&gibbonPersonID=" . $adult["gibbonPersonID"] . "'>" . formatName($adult["title"], $adult["preferredName"], $adult["surname"], "Parent") . "</a>" ;
							print "</td>" ;
							print "<td>" ;
								print $adult["status"] ;
							print "</td>" ;
							print "<td>" ;
								print nl2brr($adult["comment"]) ;
							print "</td>" ;
							print "<td style='padding-left: 1px; padding-right: 1px'>" ;
								print $adult["childDataAccess"] ;
							print "</td>" ;
							print "<td style='padding-left: 1px; padding-right: 1px'>" ;
								print $adult["contactPriority"] ;
							print "</td>" ;
							print "<td style='padding-left: 1px; padding-right: 1px'>" ;
								print $adult["contactCall"] ;
							print "</td>" ;
							print "<td style='padding-left: 1px; padding-right: 1px'>" ;
								print $adult["contactSMS"] ;
							print "</td>" ;
							print "<td style='padding-left: 1px; padding-right: 1px'>" ;
								print $adult["contactEmail"] ;
							print "</td>" ;
							print "<td style='padding-left: 1px; padding-right: 1px'>" ;
								print $adult["contactMail"] ;
							print "</td>" ;
							print "<td>" ;
								print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/family_manage_edit_editAdult.php&gibbonFamilyID=$gibbonFamilyID&gibbonPersonID=" . $adult["gibbonPersonID"] . "&search=$search'><img title='" . __($guid, 'Edit') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/config.png'/></a> " ;
								print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/family_manage_edit_deleteAdult.php&gibbonFamilyID=$gibbonFamilyID&gibbonPersonID=" . $adult["gibbonPersonID"] . "&search=$search'><img title='" . __($guid, 'Delete') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/garbage.png'/></a>" ;
								print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/user_manage_password.php&gibbonPersonID=" . $adult["gibbonPersonID"] . "&search=$search'><img title='" . __($guid, 'Change Password') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/key.png'/></a>" ;
							print "</td>" ;
						print "</tr>" ;
					}
				print "</table>" ;
			}
			
			?>
			<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/family_manage_edit_addAdultProcess.php?gibbonFamilyID=$gibbonFamilyID&search=$search" ?>">
				<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
					<tr class='break'>
						<td colspan=2>
							<h3>
							<?php print __($guid, 'Add Adult') ?>
							</h3>
						</td>
					</tr>
					<tr>
						<td style='width: 275px'> 
							<b><?php print __($guid, 'Adult\'s Name') ?> *</b><br/>
							<span style="font-size: 90%"><i></i></span>
						</td>
						<td class="right">
							<select name="gibbonPersonID2" id="gibbonPersonID2" style="width: 302px">
								<?php
								print "<option value='Please select...'>" . __($guid, 'Please select...') . "</option>" ;
								try {
									$dataSelect=array(); 
									$sqlSelect="SELECT status, gibbonPersonID, preferredName, surname, username FROM gibbonPerson WHERE status='Full' OR status='Expected' ORDER BY surname, preferredName" ;
									$resultSelect=$connection2->prepare($sqlSelect);
									$resultSelect->execute($dataSelect);
								}
								catch(PDOException $e) { }
								while ($rowSelect=$resultSelect->fetch()) {
									$expected="" ;
									if ($rowSelect["status"]=="Expected") {
										$expected=" (Expected)" ;
									}
									print "<option value='" . $rowSelect["gibbonPersonID"] . "'>" . formatName("", htmlPrep($rowSelect["preferredName"]), htmlPrep($rowSelect["surname"]), "Parent", true, true) . " (" . $rowSelect["username"] . ")" . $expected . "</option>" ;
								}
								?>				
							</select>
							<script type="text/javascript">
								var gibbonPersonID2=new LiveValidation('gibbonPersonID2');
								gibbonPersonID2.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print __($guid, 'Select something!') ?>"});
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print __($guid, 'Comment') ?></b><br/>
							<span style="font-size: 90%"><i><?php print __($guid, 'Data displayed in full Student Profile') ?><br/></i></span>
						</td>
						<td class="right">
							<textarea name="comment2" id="comment2" rows=8 style="width: 300px"></textarea>
							<script type="text/javascript">
								var comment2=new LiveValidation('comment2');
								comment2.add( Validate.Length, { maximum: 1000 } );
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print __($guid, 'Data Access?') ?></b><br/>
							<span style="font-size: 90%"><i><?php print __($guid, 'Access data on family\'s children?') ?></i></span>
						</td>
						<td class="right">
							<select name="childDataAccess" id="childDataAccess" style="width: 302px">
								<option value="Y"><?php print __($guid, 'Yes') ?></option>
								<option value="N"><?php print __($guid, 'No') ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print __($guid, 'Contact Priority') ?></b><br/>
							<span style="font-size: 90%"><i><?php print __($guid, 'The order in which school should contact family members.') ?></i></span>
						</td>
						<td class="right">
							<select name="contactPriority" id="contactPriority" style="width: 302px">
								<option value="1"><?php print __($guid, '1') ?></option>
								<option value="2"><?php print __($guid, '2') ?></option>
								<option value="3"><?php print __($guid, '3') ?></option>
							</select>
							<script type="text/javascript">
								/* Advanced Options Control */
								$(document).ready(function(){
									<?php 
									print "$(\"#contactCall\").attr(\"disabled\", \"disabled\");" ;
									print "$(\"#contactSMS\").attr(\"disabled\", \"disabled\");" ;
									print "$(\"#contactEmail\").attr(\"disabled\", \"disabled\");" ;
									print "$(\"#contactMail\").attr(\"disabled\", \"disabled\");" ;
									?>	
									$("#contactPriority").change(function(){
										if ($('#contactPriority').val()=="1" ) {
											$("#contactCall").attr("disabled", "disabled");
											$("#contactCall").val("Y");
											$("#contactSMS").attr("disabled", "disabled");
											$("#contactSMS").val("Y");
											$("#contactEmail").attr("disabled", "disabled");
											$("#contactEmail").val("Y");
											$("#contactMail").attr("disabled", "disabled");
											$("#contactMail").val("Y");
										} 
										else {
											$("#contactCall").removeAttr("disabled");
											$("#contactSMS").removeAttr("disabled");
											$("#contactEmail").removeAttr("disabled");
											$("#contactMail").removeAttr("disabled");
										}
									 });
								});
							</script>
						</td>
					</tr>
					
					<tr>
						<td> 
							<b><?php print __($guid, 'Call?') ?></b><br/>
							<span style="font-size: 90%"><i><?php print __($guid, 'Receive non-emergency phone calls from school?') ?></i></span>
						</td>
						<td class="right">
							<select name="contactCall" id="contactCall" style="width: 302px">
								<option value="Y"><?php print __($guid, 'Yes') ?></option>
								<option value="N"><?php print __($guid, 'No') ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print __($guid, 'SMS?') ?></b><br/>
							<span style="font-size: 90%"><i><?php print __($guid, 'Receive non-emergency SMS messages from school?') ?></i></span>
						</td>
						<td class="right">
							<select name="contactSMS" id="contactSMS" style="width: 302px">
								<option value="Y"><?php print __($guid, 'Yes') ?></option>
								<option value="N"><?php print __($guid, 'No') ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print __($guid, 'Email?') ?></b><br/>
							<span style="font-size: 90%"><i><?php print __($guid, 'Receive non-emergency emails from school?') ?></i></span>
						</td>
						<td class="right">
							<select name="contactEmail" id="contactEmail" style="width: 302px">
								<option value="Y"><?php print __($guid, 'Yes') ?></option>
								<option value="N"><?php print __($guid, 'No') ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print __($guid, 'Mail?') ?></b><br/>
							<span style="font-size: 90%"><i><?php print __($guid, 'Receive postage mail from school?') ?></i></span>
						</td>
						<td class="right">
							<select name="contactMail" id="contactMail" style="width: 302px">
								<option value="Y"><?php print __($guid, 'Yes') ?></option>
								<option value="N"><?php print __($guid, 'No') ?></option>
							</select>
						</td>
					</tr>
					
					<tr>
						<td>
							<span style="font-size: 90%"><i>* <?php print __($guid, "denotes a required field") ; ?></i></span>
						</td>
						<td class="right">
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
?>