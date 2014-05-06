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

if (isActionAccessible($guid, $connection2, "/modules/User Admin/user_manage_edit.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/User Admin/user_manage.php'>" . _('Manage Users') . "</a> > </div><div class='trailEnd'>" . _('') . "Edit User</div>" ;
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
			$updateReturnMessage=_("Your request was successful, but some data was not properly saved.") ;
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
		if ($deleteReturn=="success0") {
			$deleteReturnMessage=_("Your request was completed successfully.") ;		
			$class="success" ;
		}
		print "<div class='$class'>" ;
			print $deleteReturnMessage;
		print "</div>" ;
	} 
	
	//Check if school year specified
	$gibbonPersonID=$_GET["gibbonPersonID"] ;
	if ($gibbonPersonID=="") {
		print "<div class='error'>" ;
			print _("You have not specified one or more required parameters.") ;
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
				print _("The specified record cannot be found.") ;
			print "</div>" ;
		}
		else {
			//Let's go!
			$row=$result->fetch() ;
			
			//Get categories
			$staff=FALSE ;
			$student=FALSE ;
			$parent=FALSE ;
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
			}
			
			$search="" ;
			if (isset($_GET["search"])) {
				$search=$_GET["search"] ;
			}
			
			if ($search!="") {
				print "<div class='linkTop'>" ;
					print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/User Admin/user_manage.php&search=" . $search . "'>" . _('Back to Search Results') . "</a>" ;
				print "</div>" ;
			}
			?>
			<div class='warning'>
				<?php print _('Note that certain fields are hidden or revealed depending on the role categories (Staff, Student, Parent) that a user is assigned to. For example, parents do not get Emergency Contact fields, and stunders/staff do not get Employment fields.') ?>
			</div>
			<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/user_manage_editProcess.php?gibbonPersonID=" . $gibbonPersonID . "&search=" . $search ?>" enctype="multipart/form-data">
				<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
					<tr class='break'>
						<td colspan=2> 
							<h3><?php print _('Basic Information') ?></h3>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print _('Title') ?></b><br/>
						</td>
						<td class="right">
							<select style="width: 302px" name="title">
								<option value=""></option>
								<option <?php if ($row["title"]=="Ms. ") {print "selected ";}?>value="Ms. "><?php print _('Ms.') ?></option>
								<option <?php if ($row["title"]=="Miss ") {print "selected ";}?>value="Miss "><?php print _('Miss.') ?></option>
								<option <?php if ($row["title"]=="Mr. ") {print "selected ";}?>value="Mr. "><?php print _('Mr.') ?></option>
								<option <?php if ($row["title"]=="Mrs. ") {print "selected ";}?>value="Mrs. "><?php print _('Mrs.') ?></option>
								<option <?php if ($row["title"]=="Dr. ") {print "selected ";}?>value="Dr. "><?php print _('Dr.') ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print _('Surname') ?> *</b><br/>
							<span style="font-size: 90%"><i><?php print _('Family name as shown in ID documents.') ?></i></span>
						</td>
						<td class="right">
							<input name="surname" id="surname" maxlength=30 value="<?php print htmlPrep($row["surname"]) ?>" type="text" style="width: 300px">
							<script type="text/javascript">
								var surname=new LiveValidation('surname');
								surname.add(Validate.Presence);
							 </script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print _('First Name') ?> *</b><br/>
							<span style="font-size: 90%"><i><?php print _('First name as shown in ID documents.') ?></i></span>
						</td>
						<td class="right">
							<input name="firstName" id="firstName" maxlength=30 value="<?php print htmlPrep($row["firstName"]) ?>" type="text" style="width: 300px">
							<script type="text/javascript">
								var firstName=new LiveValidation('firstName');
								firstName.add(Validate.Presence);
							 </script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print _('Preferred Name') ?> *</b><br/>
							<span style="font-size: 90%"><i><?php print _('Most common name, alias, nickname, etc.') ?></i></span>
						</td>
						<td class="right">
							<input name="preferredName" id="preferredName" maxlength=30 value="<?php print htmlPrep($row["preferredName"]) ?>" type="text" style="width: 300px">
							<script type="text/javascript">
								var preferredName=new LiveValidation('preferredName');
								preferredName.add(Validate.Presence);
							 </script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print _('Official Name') ?> *</b><br/>
							<span style="font-size: 90%"><i><?php print _('Full name as shown in ID documents.') ?></i></span>
						</td>
						<td class="right">
							<input name="officialName" id="officialName" maxlength=150 value="<?php print htmlPrep($row["officialName"]) ?>" type="text" style="width: 300px">
							<script type="text/javascript">
								var officialName=new LiveValidation('officialName');
								officialName.add(Validate.Presence);
							 </script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print _('Name In Characters') ?></b><br/>
							<span style="font-size: 90%"><i><?php print _('Chinese or other character-based name.') ?></i></span>
						</td>
						<td class="right">
							<input name="nameInCharacters" id="nameInCharacters" maxlength=20 value="<?php print htmlPrep($row["nameInCharacters"]) ?>" type="text" style="width: 300px">
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print _('Gender') ?> *</b><br/>
						</td>
						<td class="right">
							<select name="gender" id="gender" style="width: 302px">
								<option value="Please select..."><?php print _('Please select...') ?></option>
								<option <?php if ($row["gender"]=="F") {print "selected ";}?>value="F"><?php print _('Female') ?></option>
								<option <?php if ($row["gender"]=="M") {print "selected ";}?>value="M"><?php print _('Male') ?></option>
							</select>
							<script type="text/javascript">
								var gender=new LiveValidation('gender');
								gender.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print _('Select something!') ?>"});
							 </script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print _('Date of Birth') ?></b><br/>
							<span style="font-size: 90%"><i><?php print $_SESSION[$guid]["i18n"]["dateFormat"]  ?></i></span>
						</td>
						<td class="right">
							<input name="dob" id="dob" maxlength=10 value="<?php print dateConvertBack($guid, $row["dob"]) ?>" type="text" style="width: 300px">
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
					
					<tr class='break'>
						<td colspan=2> 
							<h3><?php print _('System Acces') ?>s</h3>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print _('Primary Role') ?> *</b><br/>
							<span style="font-size: 90%"><i><?php print _('Controls what a user can do and see.') ?></i></span>
						</td>
						<td class="right">
							<select name="gibbonRoleIDPrimary" id="gibbonRoleIDPrimary" style="width: 302px">
								<?php
								print "<option value='Please select...'>" . _('Please select...') . "</option>" ;
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
									
									print "<option $selected value='" . $rowSelect["gibbonRoleID"] . "'>" . htmlPrep($rowSelect["name"]) . "</option>" ;
								}
								?>			
							</select>
							<script type="text/javascript">
								var gibbonRoleIDPrimary=new LiveValidation('gibbonRoleIDPrimary');
								gibbonRoleIDPrimary.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print _('Select something!') ?>"});
							 </script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print _('All Roles') ?></b><br/>
							<span style="font-size: 90%"><i><?php print _('Controls what a user can do and see.') ?></i></span>
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
									
									print "<option $selected value='" . $rowSelect["gibbonRoleID"] . "'>" . htmlPrep($rowSelect["name"]) . "</option>" ;
								}
								?>			
							</select>
							<script type="text/javascript">
								var gibbonRoleIDPrimary=new LiveValidation('gibbonRoleIDPrimary');
								gibbonRoleIDPrimary.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print _('Select something!') ?>"});
							 </script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print _('Username') ?> *</b><br/>
							<span style="font-size: 90%"><i><?php print _('Must be unique. System login name.') ?></i></span>
						</td>
						<td class="right">
							<input readonly name="username" id="username" maxlength=20 value="<?php print htmlPrep($row["username"]) ?>" type="text" style="width: 300px">
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
									if ($('#dateEnd').val()=="") {
										alert("<?php print _('The End Date field has been automatically set to today\'s date. Please change it if necessary.') ?>") ;									
										$('#dateEnd').val('<?php print date("d/m/Y") ?>') ;
									}
								}
								else if ($('#status option:selected').val()=="Full" ) {
									alert("<?php print _('The Start and End Date fields have been automatically updated. Please change them if necessary.') ?>") ;
									if ($('#dateStart').val()=="") {
										$('#dateStart').val('<?php print date("d/m/Y") ?>') ;
									}
									$('#dateEnd').val('') ;
								}
								else if ($('#status option:selected').val()=="Expected" ) {
									alert("<?php print _('The Start and End Date fields have been automatically emptied. Please change them if necessary.') ?>") ;
									$('#dateStart').val('') ;
									$('#dateEnd').val('') ;
								}
							 });
						});
					</script>
					<tr>
						<td> 
							<b><?php print _('Status') ?> *</b><br/>
							<span style="font-size: 90%"><i><?php print _('This determines visibility within the system.') ?></i></span>
						</td>
						<td class="right">
							<select style="width: 302px" name="status" id='status'>
								<option <?php if ($row["status"]=="Full") {print "selected ";}?>value="Full"><?php print _('Full') ?></option>
								<option <?php if ($row["status"]=="Expected") {print "selected ";}?>value="Expected"><?php print _('Expected') ?></option>
								<option <?php if ($row["status"]=="Left") {print "selected ";}?>value="Left"><?php print _('Left') ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print _('Can Login?') ?> *</b><br/>
							<span style="font-size: 90%"><i></i></span>
						</td>
						<td class="right">
							<select style="width: 302px" name="canLogin">
								<option <?php if ($row["canLogin"]=="Y") {print "selected ";}?>value="Y"><?php print _('Yes') ?></option>
								<option <?php if ($row["canLogin"]=="N") {print "selected ";}?>value="N"><?php print _('No') ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print _('Force Reset Password?') ?> *</b><br/>
							<span style="font-size: 90%"><i>User will be prompted on next login.</i></span>
						</td>
						<td class="right">
							<select style="width: 302px" name="passwordForceReset">
								<option <?php if ($row["passwordForceReset"]=="Y") {print "selected ";}?>value="Y"><?php print _('Yes') ?></option>
								<option <?php if ($row["passwordForceReset"]=="N") {print "selected ";}?>value="N"><?php print _('No') ?></option>
							</select>
						</td>
					</tr>
					
					<tr class='break'>
						<td colspan=2> 
							<h3><?php print _('Contact Information') ?></h3>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print _('Email') ?></b><br/>
						</td>
						<td class="right">
							<input name="email" id="email" maxlength=50 value="<?php print htmlPrep($row["email"]) ?>" type="text" style="width: 300px">
							<script type="text/javascript">
								var email=new LiveValidation('email');
								email.add(Validate.Email);
							 </script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print _('Alternate Email') ?></b><br/>
							<span style="font-size: 90%"><i></i></span>
						</td>
						<td class="right">
							<input name="emailAlternate" id="emailAlternate" maxlength=50 value="<?php print htmlPrep($row["emailAlternate"]) ?>" type="text" style="width: 300px">
							<script type="text/javascript">
								var emailAlternate=new LiveValidation('emailAlternate');
								emailAlternate.add(Validate.Email);
							 </script>
						</td>
					</tr>
					<tr>
						<td colspan=2> 
							<div class='warning'>
								<?php print _('Address information for an individual only needs to be set under the following conditions:') ?>
								<ol>
									<li><?php print _('If the user is not in a family.') ?></li>
									<li><?php print _('If the user\'s family does not have a home address set.') ?></li>
									<li><?php print _('If the user needs an address in addition to their family\'s home address.') ?></li>
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
							<b><?php print _('Enter Personal Address?') ?></b><br/>
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
							<b><?php print _('Address 1') ?></b><br/>
							<span style="font-size: 90%"><i><span style="font-size: 90%"><i><?php print _('Unit, Building, Street') ?></i></span></i></span>
						</td>
						<td class="right">
							<input name="address1" id="address1" maxlength=255 value="<?php print htmlPrep($row["address1"]) ?>" type="text" style="width: 300px">
						</td>
					</tr>
					<tr class='address'>
						<td> 
							<b><?php print _('Address 1 District') ?></b><br/>
							<span style="font-size: 90%"><i><?php print _('County, State, District') ?></i></span>
						</td>
						<td class="right">
							<input name="address1District" id="address1District" maxlength=30 value="<?php print $row["address1District"] ?>" type="text" style="width: 300px">
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
							<b><?php print _('Address 1 Country') ?></b><br/>
						</td>
						<td class="right">
							<select name="address1Country" id="address1Country" style="width: 302px">
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
									print "<option $selected value='" . $rowSelect["printable_name"] . "'>" . htmlPrep($rowSelect["printable_name"]) . "</option>" ;
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
									print "<b>" . _('Matching Address 1') . "</b><br/>" ;
									print "<span style='font-size: 90%'><i>" . _('These users have similar Address 1. Do you want to change them too?') . "</i></span>" ;
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
							<b><?php print _('Address 2') ?></b><br/>
							<span style="font-size: 90%"><i><span style="font-size: 90%"><i><?php print _('Unit, Building, Street') ?></i></span></i></span>
						</td>
						<td class="right">
							<input name="address2" id="address2" maxlength=255 value="<?php print htmlPrep($row["address2"]) ?>" type="text" style="width: 300px">
						</td>
					</tr>
					<tr class='address'>
						<td> 
							<b><?php print _('Address 2 District') ?></b><br/>
							<span style="font-size: 90%"><i><?php print _('County, State, District') ?></i></span>
						</td>
						<td class="right">
							<input name="address2District" id="address2District" maxlength=30 value="<?php print $row["address2District"] ?>" type="text" style="width: 300px">
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
							<b><?php print _('Address 2 Country') ?></b><br/>
						</td>
						<td class="right">
							<select name="address2Country" id="address2Country" style="width: 302px">
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
									print "<option $selected value='" . $rowSelect["printable_name"] . "'>" . htmlPrep($rowSelect["printable_name"]) . "</option>" ;
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
								<b><?php print _('Phone') ?> <?php print $i ?></b><br/>
								<span style="font-size: 90%"><i><?php print _('Type, country code, number.') ?></i></span>
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
										print "<option $selected value='" . $rowSelect["iddCountryCode"] . "'>" . htmlPrep($rowSelect["iddCountryCode"]) . " - " .  htmlPrep($rowSelect["printable_name"]) . "</option>" ;
									}
									?>				
								</select>
								<select style="width: 70px" name="phone<?php print $i ?>Type">
									<option <?php if ($row["phone" . $i . "Type"]=="") { print "selected" ; }?> value=""></option>
									<option <?php if ($row["phone" . $i . "Type"]=="Mobile") { print "selected" ; }?> value="Mobile"><?php print _('Mobile') ?></option>
									<option <?php if ($row["phone" . $i . "Type"]=="Home") { print "selected" ; }?> value="Home"><?php print _('Home') ?></option>
									<option <?php if ($row["phone" . $i . "Type"]=="Work") { print "selected" ; }?> value="Work"><?php print _('Work') ?></option>
									<option <?php if ($row["phone" . $i . "Type"]=="Fax") { print "selected" ; }?> value="Fax"><?php print _('Fax') ?></option>
									<option <?php if ($row["phone" . $i . "Type"]=="Pager") { print "selected" ; }?> value="Pager"><?php print _('Pager') ?></option>
									<option <?php if ($row["phone" . $i . "Type"]=="Other") { print "selected" ; }?> value="Other"><?php print _('Other') ?></option>
								</select>
							</td>
						</tr>
						<?php
					}
					?>
					<tr>
						<td> 
							<b><?php print _('Website') ?></b><br/>
							<span style="font-size: 90%"><i><?php print _('Include http://') ?></i></span>
						</td>
						<td class="right">
							<input name="website" id="website" maxlength=255 value="<?php print htmlPrep($row["website"]) ?>" type="text" style="width: 300px">
							<script type="text/javascript">
								var website=new LiveValidation('website');
								website.add( Validate.Format, { pattern: /(http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/, failureMessage: "Must start with http://" } );
							</script>	
						</td>
					</tr>
					
					
					<tr class='break'>
						<td colspan=2> 
							<h3><?php print _('School Information') ?></h3>
						</td>
					</tr>
					<?php
					if ($student) {
						$dayTypeOptions=getSettingByScope($connection2, 'User Admin', 'dayTypeOptions') ;
						if ($dayTypeOptions!="") {
							?>
							<tr>
								<td> 
									<b><?php print _('Day Type') ?></b><br/>
									<span style="font-size: 90%"><i><?php print getSettingByScope($connection2, 'User Admin', 'dayTypeText') ; ?></i></span>
								</td>
								<td class="right">
									<select name="dayType" id="dayType" style="width: 302px">
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
								<b><?php print _('Last School') ?></b><br/>
							</td>
							<td class="right">
								<input name="lastSchool" id="lastSchool" maxlength=30 value="<?php print $row["lastSchool"] ?>" type="text" style="width: 300px">
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
							<b><?php print _('Start Date') ?></b><br/>
							<span style="font-size: 90%"><i><?php print _('Users\'s first day at school.') ?><br/> Format <?php if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?></i></span>
						</td>
						<td class="right">
							<input name="dateStart" id="dateStart" maxlength=10 value="<?php print dateConvertBack($guid, $row["dateStart"]) ?>" type="text" style="width: 300px">
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
							<b><?php print _('End Date') ?></b><br/>
							<span style="font-size: 90%"><i><?php print _('Users\'s last day at school.') ?><br/> Format <?php if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?></i></span>
						</td>
						<td class="right">
							<input name="dateEnd" id="dateEnd" maxlength=10 value="<?php print dateConvertBack($guid, $row["dateEnd"]) ?>" type="text" style="width: 300px">
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
								<b><?php print _('Class Of') ?></b><br/>
								<span style="font-size: 90%"><i><?php print _('When is the student expected to graduate?') ?></i></span>
							</td>
							<td class="right">
								<select name="gibbonSchoolYearIDClassOf" id="gibbonSchoolYearIDClassOf" style="width: 302px">
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
								<b><?php print _('Next School') ?></b><br/>
							</td>
							<td class="right">
								<input name="nextSchool" id="nextSchool" maxlength=30 value="<?php print $row["nextSchool"] ?>" type="text" style="width: 300px">
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
								<b><?php print _('Departure Reason') ?></b><br/>
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
									<input name="departureReason" id="departureReason" maxlength=30 value="<?php print $row["departureReason"] ?>" type="text" style="width: 300px">
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
							<h3><?php print _('Background Information') ?></h3>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print _('First Language') ?></b><br/>
						</td>
						<td class="right">
							<input name="languageFirst" id="languageFirst" maxlength=30 value="<?php print $row["languageFirst"] ?>" type="text" style="width: 300px">
						</td>
						<script type="text/javascript">
							$(function() {
								var availableTags=[
									<?php
									try {
										$dataAuto=array(); 
										$sqlAuto="SELECT DISTINCT languageFirst FROM gibbonPerson ORDER BY languageFirst" ;
										$resultAuto=$connection2->prepare($sqlAuto);
										$resultAuto->execute($dataAuto);
									}
									catch(PDOException $e) { }
									while ($rowAuto=$resultAuto->fetch()) {
										print "\"" . $rowAuto["languageFirst"] . "\", " ;
									}
									?>
								];
								$( "#languageFirst" ).autocomplete({source: availableTags});
							});
						</script>
					</tr>
					<tr>
						<td> 
							<b><?php print _('Second Language') ?></b><br/>
						</td>
						<td class="right">
							<input name="languageSecond" id="languageSecond" maxlength=30 value="<?php print $row["languageSecond"] ?>" type="text" style="width: 300px">
						</td>
						<script type="text/javascript">
							$(function() {
								var availableTags=[
									<?php
									try {
										$dataAuto=array(); 
										$sqlAuto="SELECT DISTINCT languageSecond FROM gibbonPerson ORDER BY languageSecond" ;
										$resultAuto=$connection2->prepare($sqlAuto);
										$resultAuto->execute($dataAuto);
									}
									catch(PDOException $e) { }
									while ($rowAuto=$resultAuto->fetch()) {
										print "\"" . $rowAuto["languageSecond"] . "\", " ;
									}
									?>
								];
								$( "#languageSecond" ).autocomplete({source: availableTags});
							});
						</script>
					</tr>
					<tr>
						<td> 
							<b><?php print _('Third Language') ?></b><br/>
						</td>
						<td class="right">
							<input name="languageThird" id="languageThird" maxlength=30 value="<?php print $row["languageThird"] ?>" type="text" style="width: 300px">
						</td>
						<script type="text/javascript">
							$(function() {
								var availableTags=[
									<?php
									try {
										$dataAuto=array(); 
										$sqlAuto="SELECT DISTINCT languageThird FROM gibbonPerson ORDER BY languageThird" ;
										$resultAuto=$connection2->prepare($sqlAuto);
										$resultAuto->execute($dataAuto);
									}
									catch(PDOException $e) { }
									while ($rowAuto=$resultAuto->fetch()) {
										print "\"" . $rowAuto["languageThird"] . "\", " ;
									}
									?>
								];
								$( "#languageThird" ).autocomplete({source: availableTags});
							});
						</script>
					</tr>
					<tr>
						<td> 
							<b><?php print _('Country of Birth') ?></b><br/>
						</td>
						<td class="right">
							<select name="countryOfBirth" id="countryOfBirth" style="width: 302px">
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
									print "<option $selected value='" . $rowSelect["printable_name"] . "'>" . htmlPrep($rowSelect["printable_name"]) . "</option>" ;
								}
								?>				
							</select>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print _('Ethnicity') ?></b><br/>
						</td>
						<td class="right">
							<select name="ethnicity" id="ethnicity" style="width: 302px">
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
							<b><?php print _('Religion') ?></b><br/>
						</td>
						<td class="right">
							<select name="religion" id="religion" style="width: 302px">
								<option <?php if ($row["religion"]=="") {print "selected ";}?>value=""></option>
								<option <?php if ($row["religion"]=="Nonreligious/Agnostic/Atheist") {print "selected ";}?>value="Nonreligious/Agnostic/Atheist"><?php print _('Nonreligious/Agnostic/Atheist') ?></option>
								<option <?php if ($row["religion"]=="Buddhism") {print "selected ";}?>value="Buddhism"><?php print _('Buddhism') ?></option>
								<option <?php if ($row["religion"]=="Christianity") {print "selected ";}?>value="Christianity"><?php print _('Christianity') ?></option>
								<option <?php if ($row["religion"]=="Hinduism") {print "selected ";}?>value="Hinduism"><?php print _('Hinduism') ?></option>
								<option <?php if ($row["religion"]=="Islam") {print "selected ";}?>value="Islam"><?php print _('Islam') ?></option>
								<option <?php if ($row["religion"]=="Judaism") {print "selected ";}?>value=""><?php print _('Judaism') ?></option>
								<option <?php if ($row["religion"]=="Other") {print "selected ";}?>value="Other"><?php print _('Other') ?></option>	
							</select>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print _('Citizenship 1') ?></b><br/>
						</td>
						<td class="right">
							<select name="citizenship1" id="citizenship1" style="width: 302px">
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
										print "<option value='" . $rowSelect["printable_name"] . "'>" . htmlPrep($rowSelect["printable_name"]) . "</option>" ;
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
							<b><?php print _('Citizenship 1 Passport Number') ?></b><br/>
						</td>
						<td class="right">
							<input name="citizenship1Passport" id="citizenship1Passport" maxlength=30 value="<?php print htmlPrep($row["citizenship1Passport"]) ?>" type="text" style="width: 300px">
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print _('Citizenship 2') ?></b><br/>
						</td>
						<td class="right">
							<select name="citizenship2" id="citizenship2" style="width: 302px">
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
										print "<option value='" . $rowSelect["printable_name"] . "'>" . htmlPrep($rowSelect["printable_name"]) . "</option>" ;
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
							<b><?php print _('Citizenship 2 Passport Number') ?></b><br/>
						</td>
						<td class="right">
							<input name="citizenship2Passport" id="citizenship2Passport" maxlength=30 value="<?php print htmlPrep($row["citizenship2Passport"]) ?>" type="text" style="width: 300px">
						</td>
					</tr>
					<tr>
						<td> 
							<?php
							if ($_SESSION[$guid]["country"]=="") {
								print "<b>" . _('National ID Card Number') . "</b><br/>" ;
							}
							else {
								print "<b>" . $_SESSION[$guid]["country"] . " ". _('ID Card Number') . "</b><br/>" ;
							}
							?>
						</td>
						<td class="right">
							<input name="nationalIDCardNumber" id="nationalIDCardNumber" maxlength=30 value="<?php print htmlPrep($row["nationalIDCardNumber"]) ?>" type="text" style="width: 300px">
						</td>
					</tr>
					<tr>
						<td> 
							<?php
							if ($_SESSION[$guid]["country"]=="") {
								print "<b>" . _('Residency/Visa Type') . "</b><br/>" ;
							}
							else {
								print "<b>" . $_SESSION[$guid]["country"] . " " . _('Residency/Visa Type') . "</b><br/>" ;
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
								print "<b>" . _('Visa Expiry Date') . "</b><br/>" ;
							}
							else {
								print "<b>" . $_SESSION[$guid]["country"] . " " . _('Visa Expiry Date') . "</b><br/>" ;
							}
							print "<span style='font-size: 90%'><i>Format " ; if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; } print ". " . _('If relevant.') . "</i></span>" ;
							?>
						</td>
						<td class="right">
							<input name="visaExpiryDate" id="visaExpiryDate" maxlength=10 value="<?php print dateConvertBack($guid, $row["visaExpiryDate"]) ?>" type="text" style="width: 300px">
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
								<h3><?php print _('Employment') ?></h3>
							</td>
						</tr>
						<tr>
							<td> 
								<b><?php print _('Profession') ?></b><br/>
							</td>
							<td class="right">
								<input name="profession" id="profession" maxlength=30 value="<?php print htmlPrep($row["profession"]) ?>" type="text" style="width: 300px">
							</td>
						</tr>
						<tr>
							<td> 
								<b><?php print _('Employer') ?></b><br/>
							</td>
							<td class="right">
								<input name="employer" id="employer" maxlength=30 value="<?php print htmlPrep($row["employer"]) ?>" type="text" style="width: 300px">
							</td>
						</tr>
						<tr>
							<td> 
								<b><?php print _('Job Title') ?></b><br/>
							</td>
							<td class="right">
								<input name="jobTitle" id="jobTitle" maxlength=30 value="<?php print htmlPrep($row["jobTitle"]) ?>" type="text" style="width: 300px">
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
								<h3><?php print _('Emergency Contacts') ?></h3>
							</td>
						</tr>
						<tr>
							<td colspan=2> 
								<?php print _('These details are used when immediate family members (e.g. parent, spouse) cannot be reached first. Please try to avoid listing immediate family members.') ?> 
							</td>
						</tr>
						<tr>
							<td> 
								<b><?php print _('Contact 1 Name') ?></b><br/>
							</td>
							<td class="right">
								<input name="emergency1Name" id="emergency1Name" maxlength=30 value="<?php print htmlPrep($row["emergency1Name"]) ?>" type="text" style="width: 300px">
							</td>
						</tr>
						<tr>
							<td> 
								<b><?php print _('Contact 1 Relationship') ?></b><br/>
							</td>
							<td class="right">
								<select name="emergency1Relationship" id="emergency1Relationship" style="width: 302px">
									<option <?php if ($row["emergency1Relationship"]=="") {print "selected ";}?>value=""></option>
									<option <?php if ($row["emergency1Relationship"]=="Parent") {print "selected ";}?>value="Parent"><?php print _('Parent') ?></option>
									<option <?php if ($row["emergency1Relationship"]=="Spouse") {print "selected ";}?>value="Spouse"><?php print _('Spouse') ?></option>
									<option <?php if ($row["emergency1Relationship"]=="Offspring") {print "selected ";}?>value="Offspring"><?php print _('Offspring') ?></option>
									<option <?php if ($row["emergency1Relationship"]=="Friend") {print "selected ";}?>value="Friend"><?php print _('Friend') ?></option>
									<option <?php if ($row["emergency1Relationship"]=="Other Relation") {print "selected ";}?>value="Other Relation"><?php print _('Other Relation') ?></option>
									<option <?php if ($row["emergency1Relationship"]=="Doctor") {print "selected ";}?>value="Doctor"><?php print _('Doctor') ?></option>
									<option <?php if ($row["emergency1Relationship"]=="Other") {print "selected ";}?>value="Other"><?php print _('Other') ?></option>
								</select>	
							</td>
						</tr>
						<tr>
							<td> 
								<b><?php print _('Contact 1 Number 1') ?></b><br/>
							</td>
							<td class="right">
								<input name="emergency1Number1" id="emergency1Number1" maxlength=30 value="<?php print htmlPrep($row["emergency1Number1"]) ?>" type="text" style="width: 300px">
							</td>
						</tr>
						<tr>
							<td> 
								<b><?php print _('Contact 1 Number 2') ?></b><br/>
							</td>
							<td class="right">
								<input name="emergency1Number2" id="emergency1Number2" maxlength=30 value="<?php print htmlPrep($row["emergency1Number2"]) ?>" type="text" style="width: 300px">
							</td>
						</tr>
						<tr>
							<td> 
								<b><?php print _('Contact 2 Name') ?></b><br/>
							</td>
							<td class="right">
								<input name="emergency2Name" id="emergency2Name" maxlength=30 value="<?php print htmlPrep($row["emergency2Name"]) ?>" type="text" style="width: 300px">
							</td>
						</tr>
						<tr>
							<td> 
								<b><?php print _('Contact 2 Relationship') ?></b><br/>
							</td>
							<td class="right">
								<select name="emergency2Relationship" id="emergency2Relationship" style="width: 302px">
									<option <?php if ($row["emergency2Relationship"]=="") {print "selected ";}?>value=""></option>
									<option <?php if ($row["emergency2Relationship"]=="Parent") {print "selected ";}?>value="Parent"><?php print _('Parent') ?></option>
									<option <?php if ($row["emergency2Relationship"]=="Spouse") {print "selected ";}?>value="Spouse"><?php print _('Spouse') ?></option>
									<option <?php if ($row["emergency2Relationship"]=="Offspring") {print "selected ";}?>value="Offspring"><?php print _('Offspring') ?></option>
									<option <?php if ($row["emergency2Relationship"]=="Friend") {print "selected ";}?>value="Friend"><?php print _('Friend') ?></option>
									<option <?php if ($row["emergency2Relationship"]=="Other Relation") {print "selected ";}?>value="Other Relation"><?php print _('Other Relation') ?></option>
									<option <?php if ($row["emergency2Relationship"]=="Doctor") {print "selected ";}?>value="Doctor"><?php print _('Doctor') ?></option>
									<option <?php if ($row["emergency2Relationship"]=="Other") {print "selected ";}?>value="Other"><?php print _('Other') ?></option>
								</select>	
							</td>
						</tr>
						<tr>
							<td> 
								<b><?php print _('Contact 2 Number 1') ?></b><br/>
							</td>
							<td class="right">
								<input name="emergency2Number1" id="emergency2Number1" maxlength=30 value="<?php print htmlPrep($row["emergency2Number1"]) ?>" type="text" style="width: 300px">
							</td>
						</tr>
						<tr>
							<td> 
								<b><?php print _('Contact 2 Number 2') ?></b><br/>
							</td>
							<td class="right">
								<input name="emergency2Number2" id="emergency2Number2" maxlength=30 value="<?php print htmlPrep($row["emergency2Number2"]) ?>" type="text" style="width: 300px">
							</td>
						</tr>
						<?php
					}
					?>
					
					<tr class='break'>
						<td colspan=2> 
							<h3><?php print _('Images') ?></h3>
						</td>
					</tr>
					<tr>
						<td> 
							<b>Medium Portrait</b><br/>
							<span style="font-size: 90%"><i><?php print _('240px by 320px') ?><br/>
							<?php if ($row["image_240"]!="") {
							print "<?php print _('Will overwrite existing attachment.') ?>" ;
							} ?>
							</i></span>
						</td>
						<td class="right">
							<?php
							if ($row["image_240"]!="") {
								print _("Current attachment:") . " <a href='" . $_SESSION[$guid]["absoluteURL"] . "/" . $row["image_240"] . "'>" . $row["image_240"] . "</a> <a href='" . $_SESSION[$guid]["absoluteURL"] . "/modules/User Admin/user_manage_edit_photoDeleteProcess.php?gibbonPersonID=$gibbonPersonID&search=$search&size=240' onclick='return confirm(\"Are you sure you want to delete this record? Unsaved changes will be lost.\")'><img style='margin-bottom: -8px' id='image_75_delete' title='" . _('Delete Record') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/garbage.png'/></a><br/><br/>" ;
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
					<tr>
						<td> 
							<b><?php print _('Small Portrait') ?></b><br/>
							<span style="font-size: 90%"><i><?php print _('75px by 100px') ?><br/>
							<?php if ($row["image_75"]!="") {
							print _("Will overwrite existing attachment.") ;
							} ?>
							</i></span>
						</td>
						<td class="right">
							<?php
							if ($row["image_75"]!="") {
								print _("Current attachment:") . " <a href='" . $_SESSION[$guid]["absoluteURL"] . "/" . $row["image_75"] . "'>" . $row["image_75"] . "</a> <a href='" . $_SESSION[$guid]["absoluteURL"] . "/modules/User Admin/user_manage_edit_photoDeleteProcess.php?gibbonPersonID=$gibbonPersonID&search=$search&size=75' onclick='return confirm(\"Are you sure you want to delete this record? Unsaved changes will be lost.\")'><img style='margin-bottom: -8px' id='image_75_delete' title='" . _('Delete Record') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/garbage.png'/></a><br/><br/>" ;
							}
							?>
							<input type="file" name="file2" id="file2"><br/><br/>
							<input type="hidden" name="attachment2" value='<?php print $row["image_75"] ?>'>
							<?php
							print getMaxUpload(TRUE) ;				
							?>
							<script type="text/javascript">
								var file2=new LiveValidation('file2');
								file2.add( Validate.Inclusion, { within: ['gif','jpg','jpeg','png'], failureMessage: "Illegal file type!", partialMatch: true, caseSensitive: false } );
							</script>
						</td>
					</tr>
					
					
					<tr class='break'>
						<td colspan=2> 
							<h3><?php print _('Miscellaneous') ?></h3>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print _('House') ?></b><br/>
						</td>
						<td class="right">
							<select name="gibbonHouseID" id="gibbonHouseID" style="width: 302px">
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
								<b><?php print _('Student ID') ?></b><br/>
								<span style="font-size: 90%"><i><?php print _('If set, must be unqiue.') ?></i></span>
							</td>
							<td class="right">
								<input name="studentID" id="studentID" maxlength=10 value="<?php print htmlPrep($row["studentID"]) ?>" type="text" style="width: 300px">
							</td>
						</tr>
						<?php
					}
					if ($student OR $staff) {
						?>
						<tr>
							<td> 
								<b><?php print _('Transport') ?></b><br/>
							</td>
							<td class="right">
								<input name="transport" id="transport" maxlength=255 value="<?php print htmlPrep($row["transport"]) ?>" type="text" style="width: 300px">
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
					<?php
					}
					if ($student OR $staff) {
						?> 
						<tr>
							<td> 
								<b><?php print _('Locker Number') ?></b><br/>
								<span style="font-size: 90%"></span>
							</td>
							<td class="right">
								<input name="lockerNumber" id="lockerNumber" maxlength=20 value="<?php print $row["lockerNumber"] ?>" type="text" style="width: 300px">
							</td>
						</tr>
						<?php
					}
					?>
					<tr>
						<td> 
							<b><?php print _('Vehicle Registration') ?></b><br/>
							<span style="font-size: 90%"></span>
						</td>
						<td class="right">
							<input name="vehicleRegistration" id="vehicleRegistration" maxlength=20 value="<?php print $row["vehicleRegistration"] ?>" type="text" style="width: 300px">
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
									<b><?php print _('Privacy') ?> *</b><br/>
									<span style="font-size: 90%"><i><?php print htmlPrep($privacyBlurb) ?><br/>
									</i></span>
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
					?>
						
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
?>