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

if (isActionAccessible($guid, $connection2, "/modules/User Admin/user_manage_edit.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/User Admin/user_manage.php'>" . __($guid, 'Manage Users') . "</a> > </div><div class='trailEnd'>" . __($guid, 'Edit User') . "</div>" ;
	print "</div>" ;
	
	$returns=array() ;
	$returns["warning1"] = __($guid, "Your request was completed successfully, but one or more images were the wrong size and so were not saved.") ;	
	if (isset($_GET["return"])) { returnProcess($guid, $_GET["return"], null, $returns); }
	
	//Check if school year specified
	$gibbonPersonID=$_GET["gibbonPersonID"] ;
	if ($gibbonPersonID=="") {
		print "<div class='error'>" ;
			print __($guid, "You have not specified one or more required parameters.") ;
		print "</div>" ;
	}
	else {
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
			//Let's go!
			$row=$result->fetch() ;
			
			//Get categories
			$staff=FALSE ;
			$student=FALSE ;
			$parent=FALSE ;
			$other=FALSE ;
			$roles=explode(",", $row["gibbonRoleIDAll"]) ;
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
			
			$search="" ;
			if (isset($_GET["search"])) {
				$search=$_GET["search"] ;
			}
			
			if ($search!="") {
				print "<div class='linkTop'>" ;
					print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/User Admin/user_manage.php&search=" . $search . "'>" . __($guid, 'Back to Search Results') . "</a>" ;
				print "</div>" ;
			}
			?>
			<div class='warning'>
				<?php print __($guid, 'Note that certain fields are hidden or revealed depending on the role categories (Staff, Student, Parent) that a user is assigned to. For example, parents do not get Emergency Contact fields, and stunders/staff do not get Employment fields.') ?>
			</div>
			<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/user_manage_editProcess.php?gibbonPersonID=" . $gibbonPersonID . "&search=" . $search ?>" enctype="multipart/form-data">
				<table class='smallIntBorder fullWidth' cellspacing='0'>	
					<tr class='break'>
						<td colspan=2> 
							<h3><?php print __($guid, 'Basic Information') ?></h3>
						</td>
					</tr>
					<tr>
						<td style='width: 275px'> 
							<b><?php print __($guid, 'Title') ?></b><br/>
						</td>
						<td class="right">
							<select class="standardWidth" name="title">
								<option value=""></option>
								<option <?php if ($row["title"]=="Ms.") {print "selected ";}?>value="Ms."><?php print __($guid, 'Ms.') ?></option>
								<option <?php if ($row["title"]=="Miss") {print "selected ";}?>value="Miss"><?php print __($guid, 'Miss') ?></option>
								<option <?php if ($row["title"]=="Mr.") {print "selected ";}?>value="Mr."><?php print __($guid, 'Mr.') ?></option>
								<option <?php if ($row["title"]=="Mrs.") {print "selected ";}?>value="Mrs."><?php print __($guid, 'Mrs.') ?></option>
								<option <?php if ($row["title"]=="Dr.") {print "selected ";}?>value="Dr."><?php print __($guid, 'Dr.') ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print __($guid, 'Surname') ?> *</b><br/>
							<span class="emphasis small"><?php print __($guid, 'Family name as shown in ID documents.') ?></span>
						</td>
						<td class="right">
							<input name="surname" id="surname" maxlength=30 value="<?php print htmlPrep($row["surname"]) ?>" type="text" class="standardWidth">
							<script type="text/javascript">
								var surname=new LiveValidation('surname');
								surname.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print __($guid, 'First Name') ?> *</b><br/>
							<span class="emphasis small"><?php print __($guid, 'First name as shown in ID documents.') ?></span>
						</td>
						<td class="right">
							<input name="firstName" id="firstName" maxlength=30 value="<?php print htmlPrep($row["firstName"]) ?>" type="text" class="standardWidth">
							<script type="text/javascript">
								var firstName=new LiveValidation('firstName');
								firstName.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print __($guid, 'Preferred Name') ?> *</b><br/>
							<span class="emphasis small"><?php print __($guid, 'Most common name, alias, nickname, etc.') ?></span>
						</td>
						<td class="right">
							<input name="preferredName" id="preferredName" maxlength=30 value="<?php print htmlPrep($row["preferredName"]) ?>" type="text" class="standardWidth">
							<script type="text/javascript">
								var preferredName=new LiveValidation('preferredName');
								preferredName.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print __($guid, 'Official Name') ?> *</b><br/>
							<span class="emphasis small"><?php print __($guid, 'Full name as shown in ID documents.') ?></span>
						</td>
						<td class="right">
							<input name="officialName" id="officialName" maxlength=150 value="<?php print htmlPrep($row["officialName"]) ?>" type="text" class="standardWidth">
							<script type="text/javascript">
								var officialName=new LiveValidation('officialName');
								officialName.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print __($guid, 'Name In Characters') ?></b><br/>
							<span class="emphasis small"><?php print __($guid, 'Chinese or other character-based name.') ?></span>
						</td>
						<td class="right">
							<input name="nameInCharacters" id="nameInCharacters" maxlength=20 value="<?php print htmlPrep($row["nameInCharacters"]) ?>" type="text" class="standardWidth">
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print __($guid, 'Gender') ?> *</b><br/>
						</td>
						<td class="right">
							<select name="gender" id="gender" class="standardWidth">
								<option value="Please select..."><?php print __($guid, 'Please select...') ?></option>
								<option <?php if ($row["gender"]=="F") {print "selected ";}?>value="F"><?php print __($guid, 'Female') ?></option>
								<option <?php if ($row["gender"]=="M") {print "selected ";}?>value="M"><?php print __($guid, 'Male') ?></option>
								<option <?php if ($row["gender"]=="Other") {print "selected ";}?>value="Other"><?php print __($guid, 'Other') ?></option>
								<option <?php if ($row["gender"]=="Unspecified") {print "selected ";}?>value="Unspecified"><?php print __($guid, 'Unspecified') ?></option>
							</select>
							<script type="text/javascript">
								var gender=new LiveValidation('gender');
								gender.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print __($guid, 'Select something!') ?>"});
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print __($guid, 'Date of Birth') ?></b><br/>
							<span class="emphasis small"><?php print $_SESSION[$guid]["i18n"]["dateFormat"]  ?></span>
						</td>
						<td class="right">
							<?php 
							$value="" ;
							if ($row["dob"]!=NULL AND $row["dob"]!="" AND $row["dob"]!="0000-00-00") {
								$value=dateConvertBack($guid, $row["dob"]) ;
							}
							?>
							<input name="dob" id="dob" maxlength=10 value="<?php print $value ?>" type="text" class="standardWidth">
							<script type="text/javascript">
								var dob=new LiveValidation('dob');
								dob.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]["i18n"]["dateFormatRegEx"]=="") {  print "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i" ; } else { print $_SESSION[$guid]["i18n"]["dateFormatRegEx"] ; } ?>, failureMessage: "Use <?php if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?>." } ); 
							</script>
							 <script type="text/javascript">
								$(function() {
									$( "#dob" ).datepicker();
								});
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print __($guid, 'User Photo') ?></b><br/>
							<span class="emphasis small"><?php print __($guid, 'Displayed at 240px by 320px.') . "<br/>" . __($guid, 'Accepts images up to 360px by 480px.') . "<br/>" . __($guid, 'Accepts aspect ratio between 1:1.2 and 1:1.4.') ?><br/>
							<?php if ($row["image_240"]!="") {
							print __($guid, 'Will overwrite existing attachment.') ;
							} ?>
							</span>
						</td>
						<td class="right">
							<?php
							if ($row["image_240"]!="") {
								print __($guid, "Current attachment:") . " <a target='_blank' href='" . $_SESSION[$guid]["absoluteURL"] . "/" . $row["image_240"] . "'>" . $row["image_240"] . "</a> <a href='" . $_SESSION[$guid]["absoluteURL"] . "/modules/User Admin/user_manage_edit_photoDeleteProcess.php?gibbonPersonID=$gibbonPersonID&search=$search&size=240' onclick='return confirm(\"Are you sure you want to delete this record? Unsaved changes will be lost.\")'><img style='margin-bottom: -8px' id='image_240_delete' title='" . __($guid, 'Delete') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/garbage.png'/></a><br/><br/>" ;
							}
							?>
							<input type="file" name="file1" id="file1"><br/><br/>
							<input type="hidden" name="attachment1" value='<?php print $row["image_240"] ?>'>
							<script type="text/javascript">
								var file1=new LiveValidation('file1');
								file1.add( Validate.Inclusion, { within: ['gif','jpg','jpeg','png'], failureMessage: "Illegal file type!", partialMatch: true, caseSensitive: false } );
							</script>
						</td>
					</tr>
					
					
					<tr class='break'>
						<td colspan=2> 
							<h3><?php print __($guid, 'System Acces') ?>s</h3>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print __($guid, 'Primary Role') ?> *</b><br/>
							<span class="emphasis small"><?php print __($guid, 'Controls what a user can do and see.') ?></span>
						</td>
						<td class="right">
							<select name="gibbonRoleIDPrimary" id="gibbonRoleIDPrimary" class="standardWidth">
								<?php
								print "<option value='Please select...'>" . __($guid, 'Please select...') . "</option>" ;
								try {
									$dataSelect=array(); 
									$sqlSelect="SELECT * FROM gibbonRole ORDER BY name" ;
									$resultSelect=$connection2->prepare($sqlSelect);
									$resultSelect->execute($dataSelect);
								}
								catch(PDOException $e) { 
									print "<div class='error'>" . $e->getMessage() . "</div>" ; 
								}
								while ($rowSelect=$resultSelect->fetch()) {
									$selected="" ;
									if ($row["gibbonRoleIDPrimary"]==$rowSelect["gibbonRoleID"]) {
										$selected="selected" ;
									}
									
									print "<option $selected value='" . $rowSelect["gibbonRoleID"] . "'>" . htmlPrep(__($guid, $rowSelect["name"])) . "</option>" ;
								}
								?>			
							</select>
							<script type="text/javascript">
								var gibbonRoleIDPrimary=new LiveValidation('gibbonRoleIDPrimary');
								gibbonRoleIDPrimary.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print __($guid, 'Select something!') ?>"});
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print __($guid, 'All Roles') ?></b><br/>
							<span class="emphasis small"><?php print __($guid, 'Controls what a user can do and see.') ?></span>
						</td>
						<td class="right">
							<select multiple name="gibbonRoleIDAll[]" id="gibbonRoleIDAll[]" style="width: 302px; height: 130px">
								<?php
								try {
									$dataSelect=array(); 
									$sqlSelect="SELECT * FROM gibbonRole ORDER BY name" ;
									$resultSelect=$connection2->prepare($sqlSelect);
									$resultSelect->execute($dataSelect);
								}
								catch(PDOException $e) { 
									print "<div class='error'>" . $e->getMessage() . "</div>" ; 
								}
								while ($rowSelect=$resultSelect->fetch()) {
									$selected="" ;
									$roles=explode(",", $row["gibbonRoleIDAll"]) ; 
									foreach ($roles as $role) {
										if ($role==$rowSelect["gibbonRoleID"]) {
											$selected="selected" ;
										}
									}
									
									print "<option $selected value='" . $rowSelect["gibbonRoleID"] . "'>" . htmlPrep(__($guid, $rowSelect["name"])) . "</option>" ;
								}
								?>			
							</select>
							<script type="text/javascript">
								var gibbonRoleIDPrimary=new LiveValidation('gibbonRoleIDPrimary');
								gibbonRoleIDPrimary.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print __($guid, 'Select something!') ?>"});
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print __($guid, 'Username') ?> *</b><br/>
							<span class="emphasis small"><?php print __($guid, 'Must be unique. System login name.') ?></span>
						</td>
						<td class="right">
							<input readonly name="username" id="username" maxlength=20 value="<?php print htmlPrep($row["username"]) ?>" type="text" class="standardWidth">
							<script type="text/javascript">
								var username=new LiveValidation('username');
								username.add(Validate.Presence);
							</script>
						</td>
					</tr>
					
					<!-- CONTROLS FOR STATUS -->
					<script type="text/javascript">
						$(document).ready(function(){
							$("#status").change(function(){
								if ($('#status option:selected').val()=="Left" ) {
									alert("As you have marked this person as left, please consider setting the End Date field.") ;									
								}
								else if ($('#status option:selected').val()=="Full" ) {
									alert("As you have marked this person as full, please consider setting the Start Date field.") ;									
								}
								else if ($('#status option:selected').val()=="Expected" ) {
									alert("As you have marked this person as expected, please consider setting the Start Date field.") ;									
								}
							 });
						});
					</script>
					<tr>
						<td> 
							<b><?php print __($guid, 'Status') ?> *</b><br/>
							<span class="emphasis small"><?php print __($guid, 'This determines visibility within the system.') ?></span>
						</td>
						<td class="right">
							<select class="standardWidth" name="status" id='status'>
								<option <?php if ($row["status"]=="Full") {print "selected ";}?>value="Full"><?php print __($guid, 'Full') ?></option>
								<option <?php if ($row["status"]=="Expected") {print "selected ";}?>value="Expected"><?php print __($guid, 'Expected') ?></option>
								<option <?php if ($row["status"]=="Left") {print "selected ";}?>value="Left"><?php print __($guid, 'Left') ?></option>
								<option <?php if ($row["status"]=="Pending Approval") {print "selected ";}?>value="Pending Approval"><?php print __($guid, 'Pending Approval') ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print __($guid, 'Can Login?') ?> *</b><br/>
							<span class="emphasis small"></span>
						</td>
						<td class="right">
							<select class="standardWidth" name="canLogin">
								<option <?php if ($row["canLogin"]=="Y") {print "selected ";}?>value="Y"><?php print __($guid, 'Yes') ?></option>
								<option <?php if ($row["canLogin"]=="N") {print "selected ";}?>value="N"><?php print __($guid, 'No') ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print __($guid, 'Force Reset Password?') ?> *</b><br/>
							<span class="emphasis small">User will be prompted on next login.</span>
						</td>
						<td class="right">
							<select class="standardWidth" name="passwordForceReset">
								<option <?php if ($row["passwordForceReset"]=="Y") {print "selected ";}?>value="Y"><?php print __($guid, 'Yes') ?></option>
								<option <?php if ($row["passwordForceReset"]=="N") {print "selected ";}?>value="N"><?php print __($guid, 'No') ?></option>
							</select>
						</td>
					</tr>
					
					<tr class='break'>
						<td colspan=2> 
							<h3><?php print __($guid, 'Contact Information') ?></h3>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print __($guid, 'Email') ?></b><br/>
						</td>
						<td class="right">
							<input name="email" id="email" maxlength=50 value="<?php print htmlPrep($row["email"]) ?>" type="text" class="standardWidth">
							<script type="text/javascript">
								var email=new LiveValidation('email');
								email.add(Validate.Email);
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print __($guid, 'Alternate Email') ?></b><br/>
							<span class="emphasis small"></span>
						</td>
						<td class="right">
							<input name="emailAlternate" id="emailAlternate" maxlength=50 value="<?php print htmlPrep($row["emailAlternate"]) ?>" type="text" class="standardWidth">
							<script type="text/javascript">
								var emailAlternate=new LiveValidation('emailAlternate');
								emailAlternate.add(Validate.Email);
							</script>
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
								print "<option value=''></option>" ;
								try {
									$dataSelect=array(); 
									$sqlSelect="SELECT printable_name FROM gibbonCountry ORDER BY printable_name" ;
									$resultSelect=$connection2->prepare($sqlSelect);
									$resultSelect->execute($dataSelect);
								}
								catch(PDOException $e) { 
									print "<div class='error'>" . $e->getMessage() . "</div>" ; 
								}
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
						try {
							$dataAddress=array("gibbonPersonID"=>$row["gibbonPersonID"], "addressMatch"=>"%" . strtolower(preg_replace("/ /", "%", preg_replace("/,/", "%", $row["address1"]))) . "%"); 
							$sqlAddress="SELECT gibbonPersonID, title, preferredName, surname, category FROM gibbonPerson JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) WHERE status='Full' AND address1 LIKE :addressMatch AND NOT gibbonPersonID=:gibbonPersonID ORDER BY surname, preferredName" ;
							$resultAddress=$connection2->prepare($sqlAddress);
							$resultAddress->execute($dataAddress);
						}
						catch(PDOException $e) { 
							print "<div class='error'>" . $e->getMessage() . "</div>" ; 
						}
						if ($resultAddress->rowCount()>0) {
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
								print "<option value=''></option>" ;
								try {
									$dataSelect=array(); 
									$sqlSelect="SELECT printable_name FROM gibbonCountry ORDER BY printable_name" ;
									$resultSelect=$connection2->prepare($sqlSelect);
									$resultSelect->execute($dataSelect);
								}
								catch(PDOException $e) { 
									print "<div class='error'>" . $e->getMessage() . "</div>" ; 
								}
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
								<b><?php print __($guid, 'Phone') ?> <?php print $i ?></b><br/>
								<span class="emphasis small"><?php print __($guid, 'Type, country code, number.') ?></span>
							</td>
							<td class="right">
								<input name="phone<?php print $i ?>" id="phone<?php print $i ?>" maxlength=20 value="<?php print $row["phone" . $i] ?>" type="text" style="width: 160px">
								<select name="phone<?php print $i ?>CountryCode" id="phone<?php print $i ?>CountryCode" style="width: 60px">
									<?php
									print "<option value=''></option>" ;
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
								<select style="width: 70px" name="phone<?php print $i ?>Type">
									<option <?php if ($row["phone" . $i . "Type"]=="") { print "selected" ; }?> value=""></option>
									<option <?php if ($row["phone" . $i . "Type"]=="Mobile") { print "selected" ; }?> value="Mobile"><?php print __($guid, 'Mobile') ?></option>
									<option <?php if ($row["phone" . $i . "Type"]=="Home") { print "selected" ; }?> value="Home"><?php print __($guid, 'Home') ?></option>
									<option <?php if ($row["phone" . $i . "Type"]=="Work") { print "selected" ; }?> value="Work"><?php print __($guid, 'Work') ?></option>
									<option <?php if ($row["phone" . $i . "Type"]=="Fax") { print "selected" ; }?> value="Fax"><?php print __($guid, 'Fax') ?></option>
									<option <?php if ($row["phone" . $i . "Type"]=="Pager") { print "selected" ; }?> value="Pager"><?php print __($guid, 'Pager') ?></option>
									<option <?php if ($row["phone" . $i . "Type"]=="Other") { print "selected" ; }?> value="Other"><?php print __($guid, 'Other') ?></option>
								</select>
							</td>
						</tr>
						<?php
					}
					?>
					<tr>
						<td> 
							<b><?php print __($guid, 'Website') ?></b><br/>
							<span class="emphasis small"><?php print __($guid, 'Include http://') ?></span>
						</td>
						<td class="right">
							<input name="website" id="website" maxlength=255 value="<?php print htmlPrep($row["website"]) ?>" type="text" class="standardWidth">
							<script type="text/javascript">
								var website=new LiveValidation('website');
								website.add( Validate.Format, { pattern: /(http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/, failureMessage: "Must start with http:// or https://" } );
							</script>	
						</td>
					</tr>
					
					
					<tr class='break'>
						<td colspan=2> 
							<h3><?php print __($guid, 'School Information') ?></h3>
						</td>
					</tr>
					<?php
					if ($student) {
						$dayTypeOptions=getSettingByScope($connection2, 'User Admin', 'dayTypeOptions') ;
						if ($dayTypeOptions!="") {
							?>
							<tr>
								<td> 
									<b><?php print __($guid, 'Day Type') ?></b><br/>
									<span class="emphasis small"><?php print getSettingByScope($connection2, 'User Admin', 'dayTypeText') ; ?></span>
								</td>
								<td class="right">
									<select name="dayType" id="dayType" class="standardWidth">
										<option value=''></option>
										<?php
										$dayTypes=explode(",", $dayTypeOptions) ;
										foreach ($dayTypes as $dayType) {
											$selected="" ;
											if ($row["dayType"]==$dayType) {
												$selected="selected" ;
											}
											print "<option $selected value='" . trim($dayType) . "'>" . trim($dayType) . "</option>" ;
										}
										?>				
									</select>
								</td>
							</tr>
							<?php
						}	
					}
					if ($student or $staff) {
						?>
						<tr>
							<td> 
								<b><?php print __($guid, 'Last School') ?></b><br/>
							</td>
							<td class="right">
								<input name="lastSchool" id="lastSchool" maxlength=30 value="<?php print $row["lastSchool"] ?>" type="text" class="standardWidth">
							</td>
							<script type="text/javascript">
								$(function() {
									var availableTags=[
										<?php
										try {
											$dataAuto=array(); 
											$sqlAuto="SELECT DISTINCT lastSchool FROM gibbonPerson ORDER BY lastSchool" ;
											$resultAuto=$connection2->prepare($sqlAuto);
											$resultAuto->execute($dataAuto);
										}
										catch(PDOException $e) { }
										while ($rowAuto=$resultAuto->fetch()) {
											print "\"" . $rowAuto["lastSchool"] . "\", " ;
										}
										?>
									];
									$( "#lastSchool" ).autocomplete({source: availableTags});
								});
							</script>
						</tr>
						<?php
					}
					?>
					<tr>
						<td> 
							<b><?php print __($guid, 'Start Date') ?></b><br/>
							<span class="emphasis small"><?php print __($guid, 'Users\'s first day at school.') ?><br/> <?php print __($guid, "Format:") . " " ; if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; } ?></span>
						</td>
						<td class="right">
							<input name="dateStart" id="dateStart" maxlength=10 value="<?php print dateConvertBack($guid, $row["dateStart"]) ?>" type="text" class="standardWidth">
							<script type="text/javascript">
								var dateStart=new LiveValidation('dateStart');
								dateStart.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]["i18n"]["dateFormatRegEx"]=="") {  print "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i" ; } else { print $_SESSION[$guid]["i18n"]["dateFormatRegEx"] ; } ?>, failureMessage: "Use <?php if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?>." } ); 
							</script>
							 <script type="text/javascript">
								$(function() {
									$( "#dateStart" ).datepicker();
								});
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print __($guid, 'End Date') ?></b><br/>
							<span class="emphasis small"><?php print __($guid, 'Users\'s last day at school.') ?><br/> <?php print __($guid, "Format:") . " " ; if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; } ?></span>
						</td>
						<td class="right">
							<input name="dateEnd" id="dateEnd" maxlength=10 value="<?php print dateConvertBack($guid, $row["dateEnd"]) ?>" type="text" class="standardWidth">
							<script type="text/javascript">
								var dateEnd=new LiveValidation('dateEnd');
								dateEnd.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]["i18n"]["dateFormatRegEx"]=="") {  print "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i" ; } else { print $_SESSION[$guid]["i18n"]["dateFormatRegEx"] ; } ?>, failureMessage: "Use <?php if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?>." } ); 
							</script>
							 <script type="text/javascript">
								$(function() {
									$( "#dateEnd" ).datepicker();
								});
							</script>
						</td>
					</tr>
					<?php
					if ($student) {
						?>
						<tr>
							<td> 
								<b><?php print __($guid, 'Class Of') ?></b><br/>
								<span class="emphasis small"><?php print __($guid, 'When is the student expected to graduate?') ?></span>
							</td>
							<td class="right">
								<select name="gibbonSchoolYearIDClassOf" id="gibbonSchoolYearIDClassOf" class="standardWidth">
									<?php
									print "<option value=''></option>" ;
									try {
										$dataSelect=array(); 
										$sqlSelect="SELECT * FROM gibbonSchoolYear ORDER BY sequenceNumber" ;
										$resultSelect=$connection2->prepare($sqlSelect);
										$resultSelect->execute($dataSelect);
									}
									catch(PDOException $e) { 
										print "<div class='error'>" . $e->getMessage() . "</div>" ; 
									}
									while ($rowSelect=$resultSelect->fetch()) {
										$selected="" ;
										if ($row["gibbonSchoolYearIDClassOf"]==$rowSelect["gibbonSchoolYearID"]) {
											$selected="selected" ;
										}
										print "<option $selected value='" . $rowSelect["gibbonSchoolYearID"] . "'>" . htmlPrep($rowSelect["name"]) . "</option>" ;
									}
									?>				
								</select>
							</td>
						</tr>
						<?php
					}
					if ($student OR $staff) {
						?>
						<tr>
							<td> 
								<b><?php print __($guid, 'Next School') ?></b><br/>
							</td>
							<td class="right">
								<input name="nextSchool" id="nextSchool" maxlength=30 value="<?php print $row["nextSchool"] ?>" type="text" class="standardWidth">
							</td>
							<script type="text/javascript">
								$(function() {
									var availableTags=[
										<?php
										try {
											$dataAuto=array(); 
											$sqlAuto="SELECT DISTINCT nextSchool FROM gibbonPerson ORDER BY nextSchool" ;
											$resultAuto=$connection2->prepare($sqlAuto);
											$resultAuto->execute($dataAuto);
										}
										catch(PDOException $e) { }
										while ($rowAuto=$resultAuto->fetch()) {
											print "\"" . $rowAuto["nextSchool"] . "\", " ;
										}
										?>
									];
									$( "#nextSchool" ).autocomplete({source: availableTags});
								});
							</script>
						</tr>
						<?php
					}
					if ($student OR $staff) {
						?>
						<tr>
							<td> 
								<b><?php print __($guid, 'Departure Reason') ?></b><br/>
							</td>
							<td class="right">
								<?php
								$departureReasonsList=getSettingByScope($connection2, "User Admin", "departureReasons") ;
								if ($departureReasonsList!="") {
									print "<select name=\"departureReason\" id=\"departureReason\" style=\"width: 302px\">" ;
										print "<option value=''></option>" ;
										$departureReasons=explode(",", $departureReasonsList) ;
										foreach ($departureReasons as $departureReason) {
											$selected="" ;
											if (trim($departureReason)==$row["departureReason"]) {
												$selected="selected" ;
											}
											print "<option $selected value='" . trim($departureReason) . "'>" . trim($departureReason) . "</option>" ;
										}	
									print "</select>" ;
								}
								else {
									?>
									<input name="departureReason" id="departureReason" maxlength=30 value="<?php print $row["departureReason"] ?>" type="text" class="standardWidth">
									<script type="text/javascript">
										$(function() {
											var availableTags=[
												<?php
												try {
													$dataAuto=array(); 
													$sqlAuto="SELECT DISTINCT departureReason FROM gibbonPerson ORDER BY departureReason" ;
													$resultAuto=$connection2->prepare($sqlAuto);
													$resultAuto->execute($dataAuto);
												}
												catch(PDOException $e) { }
												while ($rowAuto=$resultAuto->fetch()) {
													print "\"" . $rowAuto["departureReason"] . "\", " ;
												}
												?>
											];
											$( "#departureReason" ).autocomplete({source: availableTags});
										});
									</script>
									<?php
								}		
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
							<b><?php print __($guid, 'First Language') ?></b><br/>
						</td>
						<td class="right">
							<select name="languageFirst" id="languageFirst" class="standardWidth">
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
									if ($row["languageFirst"]==$rowSelect["name"]) {
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
							<b><?php print __($guid, 'Second Language') ?></b><br/>
						</td>
						<td class="right">
							<select name="languageSecond" id="languageSecond" class="standardWidth">
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
									if ($row["languageSecond"]==$rowSelect["name"]) {
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
							<b><?php print __($guid, 'Third Language') ?></b><br/>
						</td>
						<td class="right">
							<select name="languageThird" id="languageThird" class="standardWidth">
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
									if ($row["languageThird"]==$rowSelect["name"]) {
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
							<b><?php print __($guid, 'Country of Birth') ?></b><br/>
						</td>
						<td class="right">
							<select name="countryOfBirth" id="countryOfBirth" class="standardWidth">
								<?php
								print "<option value=''></option>" ;
								try {
									$dataSelect=array(); 
									$sqlSelect="SELECT printable_name FROM gibbonCountry ORDER BY printable_name" ;
									$resultSelect=$connection2->prepare($sqlSelect);
									$resultSelect->execute($dataSelect);
								}
								catch(PDOException $e) { 
									print "<div class='error'>" . $e->getMessage() . "</div>" ; 
								}
								while ($rowSelect=$resultSelect->fetch()) {
									$selected="" ;
									if ($rowSelect["printable_name"]==$row["countryOfBirth"]) {
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
							<b><?php print __($guid, 'Ethnicity') ?></b><br/>
						</td>
						<td class="right">
							<select name="ethnicity" id="ethnicity" class="standardWidth">
								<option <?php if ($row["ethnicity"]=="") {print "selected ";}?>value=""></option>
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
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print __($guid, 'Religion') ?></b><br/>
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
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print __($guid, 'Citizenship 1') ?></b><br/>
						</td>
						<td class="right">
							<select name="citizenship1" id="citizenship1" class="standardWidth">
								<?php
								print "<option value=''></option>" ;
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
										$selected="" ;
										if ($rowSelect["printable_name"]==$row["citizenship1"]) {
											$selected="selected" ;
										}
										print "<option $selected value='" . $rowSelect["printable_name"] . "'>" . htmlPrep(__($guid, $rowSelect["printable_name"])) . "</option>" ;
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
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print __($guid, 'Citizenship 1 Passport Number') ?></b><br/>
						</td>
						<td class="right">
							<input name="citizenship1Passport" id="citizenship1Passport" maxlength=30 value="<?php print htmlPrep($row["citizenship1Passport"]) ?>" type="text" class="standardWidth">
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print __($guid, 'Citizenship 1 Passport Scan') ?></b><br/>
							<span class="emphasis small"><?php print __($guid, 'Less than 1440px by 900px') ?></span>
							<?php if ($row["citizenship1PassportScan"]!="") {
							print "<?php print __($guid, 'Will overwrite existing attachment.') ?>" ;
							} ?>
							</span>
						</td>
						<td class="right">
							<?php
							if ($row["citizenship1PassportScan"]!="") {
								print __($guid, "Current attachment:") . " <a target='_blank' href='" . $_SESSION[$guid]["absoluteURL"] . "/" . $row["citizenship1PassportScan"] . "'>" . $row["citizenship1PassportScan"] . "</a> <a href='" . $_SESSION[$guid]["absoluteURL"] . "/modules/User Admin/user_manage_edit_photoDeleteProcess.php?gibbonPersonID=$gibbonPersonID&search=$search&size=passport' onclick='return confirm(\"Are you sure you want to delete this record? Unsaved changes will be lost.\")'><img style='margin-bottom: -8px' title='" . __($guid, 'Delete') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/garbage.png'/></a><br/><br/>" ;
							}
							?>
							<input type="file" name="citizenship1PassportScan" id="citizenship1PassportScan"><br/><br/>
							<input type="hidden" name="citizenship1PassportScanCurrent" value='<?php print $row["citizenship1PassportScan"] ?>'>
							<script type="text/javascript">
								var citizenship1PassportScan=new LiveValidation('citizenship1PassportScan');
								citizenship1PassportScan.add( Validate.Inclusion, { within: ['gif','jpg','jpeg','png'], failureMessage: "Illegal file type!", partialMatch: true, caseSensitive: false } );
							</script>
						</td>
					</tr>
					
					<tr>
						<td> 
							<b><?php print __($guid, 'Citizenship 2') ?></b><br/>
						</td>
						<td class="right">
							<select name="citizenship2" id="citizenship2" class="standardWidth">
								<?php
								print "<option value=''></option>" ;
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
										$selected="" ;
										if ($rowSelect["printable_name"]==$row["citizenship2"]) {
											$selected="selected" ;
										}
										print "<option $selected value='" . $rowSelect["printable_name"] . "'>" . htmlPrep(__($guid, $rowSelect["printable_name"])) . "</option>" ;
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
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print __($guid, 'Citizenship 2 Passport Number') ?></b><br/>
						</td>
						<td class="right">
							<input name="citizenship2Passport" id="citizenship2Passport" maxlength=30 value="<?php print htmlPrep($row["citizenship2Passport"]) ?>" type="text" class="standardWidth">
						</td>
					</tr>
					<tr>
						<td> 
							<?php
							if ($_SESSION[$guid]["country"]=="") {
								print "<b>" . __($guid, 'National ID Card Number') . "</b><br/>" ;
							}
							else {
								print "<b>" . $_SESSION[$guid]["country"] . " ". __($guid, 'ID Card Number') . "</b><br/>" ;
							}
							?>
						</td>
						<td class="right">
							<input name="nationalIDCardNumber" id="nationalIDCardNumber" maxlength=30 value="<?php print htmlPrep($row["nationalIDCardNumber"]) ?>" type="text" class="standardWidth">
						</td>
					</tr>
					<tr>
						<td> 
							<?php
							if ($_SESSION[$guid]["country"]=="") {
								print "<b>" . __($guid, 'National ID Card Scan') . "</b><br/>" ;
							}
							else {
								print "<b>" . $_SESSION[$guid]["country"] . " " . __($guid, 'ID Card Scan') . "</b><br/>" ;
							}
							?>
							<span class="emphasis small"><?php print __($guid, 'Less than 1440px by 900px') ?></span>
						</td>
						<td class="right">
							<?php
							if ($row["nationalIDCardScan"]!="") {
								print __($guid, "Current attachment:") . " <a target='_blank' href='" . $_SESSION[$guid]["absoluteURL"] . "/" . $row["nationalIDCardScan"] . "'>" . $row["nationalIDCardScan"] . "</a> <a href='" . $_SESSION[$guid]["absoluteURL"] . "/modules/User Admin/user_manage_edit_photoDeleteProcess.php?gibbonPersonID=$gibbonPersonID&search=$search&size=id' onclick='return confirm(\"Are you sure you want to delete this record? Unsaved changes will be lost.\")'><img style='margin-bottom: -8px' title='" . __($guid, 'Delete') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/garbage.png'/></a><br/><br/>" ;
							}
							?>
							<input type="file" name="nationalIDCardScan" id="nationalIDCardScan"><br/><br/>
							<input type="hidden" name="nationalIDCardScanCurrent" value='<?php print $row["nationalIDCardScan"] ?>'>
							<script type="text/javascript">
								var nationalIDCardScan=new LiveValidation('nationalIDCardScan');
								nationalIDCardScan.add( Validate.Inclusion, { within: ['gif','jpg','jpeg','png'], failureMessage: "Illegal file type!", partialMatch: true, caseSensitive: false } );
							</script>
						</td>
					</tr>
					
					<tr>
						<td> 
							<?php
							if ($_SESSION[$guid]["country"]=="") {
								print "<b>" . __($guid, 'Residency/Visa Type') . "</b><br/>" ;
							}
							else {
								print "<b>" . $_SESSION[$guid]["country"] . " " . __($guid, 'Residency/Visa Type') . "</b><br/>" ;
							}
							?>
						</td>
						<td class="right">
							<?php
							$residencyStatusList=getSettingByScope($connection2, "User Admin", "residencyStatus") ;
							if ($residencyStatusList=="") {
								print "<input name='residencyStatus' id='residencyStatus' maxlength=30 value='" . $row["residencyStatus"] . "' type='text' style='width: 300px'>" ;
							}
							else {
								print "<select name='residencyStatus' id='residencyStatus' style='width: 302px'>" ;
									print "<option value=''></option>" ;
									$residencyStatuses=explode(",", $residencyStatusList) ;
									foreach ($residencyStatuses as $residencyStatus) {
										$selected="" ;
										if (trim($residencyStatus)==$row["residencyStatus"]) {
											$selected="selected" ;
										}
										print "<option $selected value='" . trim($residencyStatus) . "'>" . trim($residencyStatus) . "</option>" ;
									}
								print "</select>" ;
							}
							?>
						</td>
					</tr>
					<tr>
						<td> 
							<?php
							if ($_SESSION[$guid]["country"]=="") {
								print "<b>" . __($guid, 'Visa Expiry Date') . "</b><br/>" ;
							}
							else {
								print "<b>" . $_SESSION[$guid]["country"] . " " . __($guid, 'Visa Expiry Date') . "</b><br/>" ;
							}
							print "<span style='font-size: 90%'><i>Format " ; if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; } print ". " . __($guid, 'If relevant.') . "</span>" ;
							?>
						</td>
						<td class="right">
							<?php 
							$value="" ;
							if ($row["visaExpiryDate"]!=NULL AND $row["visaExpiryDate"]!="" AND $row["visaExpiryDate"]!="0000-00-00") {
								$value=dateConvertBack($guid, $row["visaExpiryDate"]) ;
							}
							?>
							<input name="visaExpiryDate" id="visaExpiryDate" maxlength=10 value="<?php print $value ?>" type="text" class="standardWidth">
							<script type="text/javascript">
								var visaExpiryDate=new LiveValidation('visaExpiryDate');
								visaExpiryDate.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]["i18n"]["dateFormatRegEx"]=="") {  print "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i" ; } else { print $_SESSION[$guid]["i18n"]["dateFormatRegEx"] ; } ?>, failureMessage: "Use <?php if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?>." } ); 
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
								<b><?php print __($guid, 'Profession') ?></b><br/>
							</td>
							<td class="right">
								<input name="profession" id="profession" maxlength=30 value="<?php print htmlPrep($row["profession"]) ?>" type="text" class="standardWidth">
							</td>
						</tr>
						<tr>
							<td> 
								<b><?php print __($guid, 'Employer') ?></b><br/>
							</td>
							<td class="right">
								<input name="employer" id="employer" maxlength=30 value="<?php print htmlPrep($row["employer"]) ?>" type="text" class="standardWidth">
							</td>
						</tr>
						<tr>
							<td> 
								<b><?php print __($guid, 'Job Title') ?></b><br/>
							</td>
							<td class="right">
								<input name="jobTitle" id="jobTitle" maxlength=30 value="<?php print htmlPrep($row["jobTitle"]) ?>" type="text" class="standardWidth">
							</td>
						</tr>
						<?php
					}
					?>
					
					
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
								<b><?php print __($guid, 'Contact 1 Name') ?></b><br/>
							</td>
							<td class="right">
								<input name="emergency1Name" id="emergency1Name" maxlength=30 value="<?php print htmlPrep($row["emergency1Name"]) ?>" type="text" class="standardWidth">
							</td>
						</tr>
						<tr>
							<td> 
								<b><?php print __($guid, 'Contact 1 Relationship') ?></b><br/>
							</td>
							<td class="right">
								<select name="emergency1Relationship" id="emergency1Relationship" class="standardWidth">
									<option <?php if ($row["emergency1Relationship"]=="") {print "selected ";}?>value=""></option>
									<option <?php if ($row["emergency1Relationship"]=="Parent") {print "selected ";}?>value="Parent"><?php print __($guid, 'Parent') ?></option>
									<option <?php if ($row["emergency1Relationship"]=="Spouse") {print "selected ";}?>value="Spouse"><?php print __($guid, 'Spouse') ?></option>
									<option <?php if ($row["emergency1Relationship"]=="Offspring") {print "selected ";}?>value="Offspring"><?php print __($guid, 'Offspring') ?></option>
									<option <?php if ($row["emergency1Relationship"]=="Friend") {print "selected ";}?>value="Friend"><?php print __($guid, 'Friend') ?></option>
									<option <?php if ($row["emergency1Relationship"]=="Other Relation") {print "selected ";}?>value="Other Relation"><?php print __($guid, 'Other Relation') ?></option>
									<option <?php if ($row["emergency1Relationship"]=="Doctor") {print "selected ";}?>value="Doctor"><?php print __($guid, 'Doctor') ?></option>
									<option <?php if ($row["emergency1Relationship"]=="Other") {print "selected ";}?>value="Other"><?php print __($guid, 'Other') ?></option>
								</select>	
							</td>
						</tr>
						<tr>
							<td> 
								<b><?php print __($guid, 'Contact 1 Number 1') ?></b><br/>
							</td>
							<td class="right">
								<input name="emergency1Number1" id="emergency1Number1" maxlength=30 value="<?php print htmlPrep($row["emergency1Number1"]) ?>" type="text" class="standardWidth">
							</td>
						</tr>
						<tr>
							<td> 
								<b><?php print __($guid, 'Contact 1 Number 2') ?></b><br/>
							</td>
							<td class="right">
								<input name="emergency1Number2" id="emergency1Number2" maxlength=30 value="<?php print htmlPrep($row["emergency1Number2"]) ?>" type="text" class="standardWidth">
							</td>
						</tr>
						<tr>
							<td> 
								<b><?php print __($guid, 'Contact 2 Name') ?></b><br/>
							</td>
							<td class="right">
								<input name="emergency2Name" id="emergency2Name" maxlength=30 value="<?php print htmlPrep($row["emergency2Name"]) ?>" type="text" class="standardWidth">
							</td>
						</tr>
						<tr>
							<td> 
								<b><?php print __($guid, 'Contact 2 Relationship') ?></b><br/>
							</td>
							<td class="right">
								<select name="emergency2Relationship" id="emergency2Relationship" class="standardWidth">
									<option <?php if ($row["emergency2Relationship"]=="") {print "selected ";}?>value=""></option>
									<option <?php if ($row["emergency2Relationship"]=="Parent") {print "selected ";}?>value="Parent"><?php print __($guid, 'Parent') ?></option>
									<option <?php if ($row["emergency2Relationship"]=="Spouse") {print "selected ";}?>value="Spouse"><?php print __($guid, 'Spouse') ?></option>
									<option <?php if ($row["emergency2Relationship"]=="Offspring") {print "selected ";}?>value="Offspring"><?php print __($guid, 'Offspring') ?></option>
									<option <?php if ($row["emergency2Relationship"]=="Friend") {print "selected ";}?>value="Friend"><?php print __($guid, 'Friend') ?></option>
									<option <?php if ($row["emergency2Relationship"]=="Other Relation") {print "selected ";}?>value="Other Relation"><?php print __($guid, 'Other Relation') ?></option>
									<option <?php if ($row["emergency2Relationship"]=="Doctor") {print "selected ";}?>value="Doctor"><?php print __($guid, 'Doctor') ?></option>
									<option <?php if ($row["emergency2Relationship"]=="Other") {print "selected ";}?>value="Other"><?php print __($guid, 'Other') ?></option>
								</select>	
							</td>
						</tr>
						<tr>
							<td> 
								<b><?php print __($guid, 'Contact 2 Number 1') ?></b><br/>
							</td>
							<td class="right">
								<input name="emergency2Number1" id="emergency2Number1" maxlength=30 value="<?php print htmlPrep($row["emergency2Number1"]) ?>" type="text" class="standardWidth">
							</td>
						</tr>
						<tr>
							<td> 
								<b><?php print __($guid, 'Contact 2 Number 2') ?></b><br/>
							</td>
							<td class="right">
								<input name="emergency2Number2" id="emergency2Number2" maxlength=30 value="<?php print htmlPrep($row["emergency2Number2"]) ?>" type="text" class="standardWidth">
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
							<b><?php print __($guid, 'House') ?></b><br/>
						</td>
						<td class="right">
							<select name="gibbonHouseID" id="gibbonHouseID" class="standardWidth">
								<?php
								print "<option value=''></option>" ;
								try {
									$dataSelect=array(); 
									$sqlSelect="SELECT gibbonHouseID, name FROM gibbonHouse ORDER BY name" ;
									$resultSelect=$connection2->prepare($sqlSelect);
									$resultSelect->execute($dataSelect);
								}
								catch(PDOException $e) { 
									print "<div class='error'>" . $e->getMessage() . "</div>" ; 
								}
								while ($rowSelect=$resultSelect->fetch()) {
									$selected="" ;
									if ($row["gibbonHouseID"]==$rowSelect["gibbonHouseID"]) {
										$selected="selected" ;
									}
									print "<option $selected value='" . $rowSelect["gibbonHouseID"] . "'>" . htmlPrep($rowSelect["name"]) . "</option>" ;
								}
								?>				
							</select>
						</td>
					</tr>
					<?php
					if ($student) {
						?> 
						<tr>
							<td> 
								<b><?php print __($guid, 'Student ID') ?></b><br/>
								<span class="emphasis small"><?php print __($guid, 'Must be unique if set.') ?></span>
							</td>
							<td class="right">
								<input name="studentID" id="studentID" maxlength=10 value="<?php print htmlPrep($row["studentID"]) ?>" type="text" class="standardWidth">
							</td>
						</tr>
						<?php
					}
					if ($student OR $staff) {
						?>
						<tr>
							<td> 
								<b><?php print __($guid, 'Transport') ?></b><br/>
							</td>
							<td class="right">
								<input name="transport" id="transport" maxlength=255 value="<?php print htmlPrep($row["transport"]) ?>" type="text" class="standardWidth">
							</td>
						</tr>
						<script type="text/javascript">
							$(function() {
								var availableTags=[
									<?php
									try {
										$dataAuto=array(); 
										$sqlAuto="SELECT DISTINCT transport FROM gibbonPerson ORDER BY lastSchool" ;
										$resultAuto=$connection2->prepare($sqlAuto);
										$resultAuto->execute($dataAuto);
									}
									catch(PDOException $e) { }
									while ($rowAuto=$resultAuto->fetch()) {
										print "\"" . $rowAuto["transport"] . "\", " ;
									}
									?>
								];
								$( "#transport" ).autocomplete({source: availableTags});
							});
						</script>
						<tr>
							<td> 
								<b><?php print __($guid, 'Transport Notes') ?></b><br/>
								<span class="emphasis small"></span>
							</td>
							<td class="right">
								<textarea name="transportNotes" id="transportNotes" rows=4 class="standardWidth"><?php print htmlPrep($row["transportNotes"]) ?></textarea>
							</td>
						</tr>
					<?php
					}
					if ($student OR $staff) {
						?> 
						<tr>
							<td> 
								<b><?php print __($guid, 'Locker Number') ?></b><br/>
								<span style="font-size: 90%"></span>
							</td>
							<td class="right">
								<input name="lockerNumber" id="lockerNumber" maxlength=20 value="<?php print $row["lockerNumber"] ?>" type="text" class="standardWidth">
							</td>
						</tr>
						<?php
					}
					?>
					<tr>
						<td> 
							<b><?php print __($guid, 'Vehicle Registration') ?></b><br/>
							<span style="font-size: 90%"></span>
						</td>
						<td class="right">
							<input name="vehicleRegistration" id="vehicleRegistration" maxlength=20 value="<?php print $row["vehicleRegistration"] ?>" type="text" class="standardWidth">
						</td>
					</tr>
					
					<?php
					//Check if any roles are "Student"
					$imagePrivacySet=false ;
					if ($student) {
						$privacySetting=getSettingByScope( $connection2, "User Admin", "privacy" ) ;
						$privacyBlurb=getSettingByScope( $connection2, "User Admin", "privacyBlurb" ) ;
						$privacyOptions=getSettingByScope( $connection2, "User Admin", "privacyOptions" ) ;
						if ($privacySetting=="Y" AND $privacyBlurb!="" AND $privacyOptions!="") {
							?>
							<tr>
								<td> 
									<b><?php print __($guid, 'Privacy') ?> *</b><br/>
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
											if (trim($option)==trim($privacyCheck)) {
												$checked="checked" ;
											}
										}
										print $option . " <input $checked type='checkbox' name='privacyOptions[]' value='" . htmlPrep(trim($option)) . "'/><br/>" ;
									}
									?>
					
								</td>
							</tr>
							<?php
						}
						else {
							print "<input type=\"hidden\" name=\"privacy\" value=\"\">" ;
						}
					}
					if ($imagePrivacySet==false) {
						print "<input type=\"hidden\" name=\"imagePrivacy\" value=\"\">" ;
					}
					
					//Student options for agreements
					if ($student) {
						$studentAgreementOptions=getSettingByScope($connection2, "School Admin", "studentAgreementOptions") ;
						if ($studentAgreementOptions!="") {
							?>
							<tr>
								<td> 
									<b><?php print __($guid, 'Student Agreements') ?></b><br/>
									<span class="emphasis small"><?php print __($guid, 'Check to indicate that student has signed the relevant agreement.') ?><br/>
									</span>
								</td>
								<td class="right">
									<?php
									$agreements=explode(",",$studentAgreementOptions) ;
									$agreementChecks=explode(",",$row["studentAgreements"]) ;
									foreach ($agreements AS $agreement) {
										$checked="" ;
										foreach ($agreementChecks AS $agreementCheck) {
											if (trim($agreement)==trim($agreementCheck)) {
												$checked="checked" ;
											}
										}
										print $agreement . " <input $checked type='checkbox' name='studentAgreements[]' value='" . htmlPrep(trim($agreement)) . "'/><br/>" ;
									}
									?>
					
								</td>
							</tr>
							<?php
						}
					}
					
					//CUSTOM FIELDS
					$fields=unserialize($row["fields"]) ;
					$resultFields=getCustomFields($connection2, $guid, $student, $staff, $parent, $other) ;
					if ($resultFields->rowCount()>0) {
						?>
						<tr class='break'>
							<td colspan=2> 
								<h3><?php print __($guid, 'Custom Fields') ?></h3>
							</td>
						</tr>
						<?php
						while ($rowFields=$resultFields->fetch()) {
							print renderCustomFieldRow($connection2, $guid, $rowFields, @$fields[$rowFields["gibbonPersonFieldID"]]) ;	
						}
					}
					?>
						
					<tr>
						<td>
							<span class="emphasis small">* <?php print __($guid, "denotes a required field") ; ?></i><br/>
							<?php
							print getMaxUpload($guid, TRUE) ;				
							?>
							</span>
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