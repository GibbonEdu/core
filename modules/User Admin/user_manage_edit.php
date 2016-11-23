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

@session_start();

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/User Admin/user_manage_edit.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/User Admin/user_manage.php'>".__($guid, 'Manage Users')."</a> > </div><div class='trailEnd'>".__($guid, 'Edit User').'</div>';
    echo '</div>';

    $returns = array();
    $returns['warning1'] = __($guid, 'Your request was completed successfully, but one or more images were the wrong size and so were not saved.');
    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, $returns);
    }

    //Check if school year specified
    $gibbonPersonID = $_GET['gibbonPersonID'];
    if ($gibbonPersonID == '') {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        try {
            $data = array('gibbonPersonID' => $gibbonPersonID);
            $sql = 'SELECT * FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonID';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        if ($result->rowCount() != 1) {
            echo "<div class='error'>";
            echo __($guid, 'The specified record cannot be found.');
            echo '</div>';
        } else {
            //Let's go!
            $row = $result->fetch();

            //Get categories
            $staff = false;
            $student = false;
            $parent = false;
            $other = false;
            $roles = explode(',', $row['gibbonRoleIDAll']);
            foreach ($roles as $role) {
                $roleCategory = getRoleCategory($role, $connection2);
                if ($roleCategory == 'Staff') {
                    $staff = true;
                }
                if ($roleCategory == 'Student') {
                    $student = true;
                }
                if ($roleCategory == 'Parent') {
                    $parent = true;
                }
                if ($roleCategory == 'Other') {
                    $other = true;
                }
            }

            $search = '';
            if (isset($_GET['search'])) {
                $search = $_GET['search'];
            }

            if ($search != '') {
                echo "<div class='linkTop'>";
                echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/User Admin/user_manage.php&search='.$search."'>".__($guid, 'Back to Search Results').'</a>';
                echo '</div>';
            }
            ?>
			<div class='warning'>
				<?php echo __($guid, 'Note that certain fields are hidden or revealed depending on the role categories (Staff, Student, Parent) that a user is assigned to. For example, parents do not get Emergency Contact fields, and stunders/staff do not get Employment fields.') ?>
			</div>
			<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/user_manage_editProcess.php?gibbonPersonID='.$gibbonPersonID.'&search='.$search ?>" enctype="multipart/form-data">
				<table class='smallIntBorder fullWidth' cellspacing='0'>
					<tr class='break'>
						<td colspan=2>
							<h3><?php echo __($guid, 'Basic Information') ?></h3>
						</td>
					</tr>
					<tr>
						<td style='width: 275px'>
							<b><?php echo __($guid, 'Title') ?></b><br/>
						</td>
						<td class="right">
							<select class="standardWidth" name="title">
								<option value=""></option>
								<option <?php if ($row['title'] == 'Ms.') { echo 'selected '; } ?>value="Ms."><?php echo __($guid, 'Ms.') ?></option>
								<option <?php if ($row['title'] == 'Miss') { echo 'selected '; } ?>value="Miss"><?php echo __($guid, 'Miss') ?></option>
								<option <?php if ($row['title'] == 'Mr.') { echo 'selected '; } ?>value="Mr."><?php echo __($guid, 'Mr.') ?></option>
								<option <?php if ($row['title'] == 'Mrs.') { echo 'selected '; } ?>value="Mrs."><?php echo __($guid, 'Mrs.') ?></option>
								<option <?php if ($row['title'] == 'Dr.') { echo 'selected '; } ?>value="Dr."><?php echo __($guid, 'Dr.') ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<td>
							<b><?php echo __($guid, 'Surname') ?> *</b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Family name as shown in ID documents.') ?></span>
						</td>
						<td class="right">
							<input name="surname" id="surname" maxlength=30 value="<?php echo htmlPrep($row['surname']) ?>" type="text" class="standardWidth">
							<script type="text/javascript">
								var surname=new LiveValidation('surname');
								surname.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<tr>
						<td>
							<b><?php echo __($guid, 'First Name') ?> *</b><br/>
							<span class="emphasis small"><?php echo __($guid, 'First name as shown in ID documents.') ?></span>
						</td>
						<td class="right">
							<input name="firstName" id="firstName" maxlength=30 value="<?php echo htmlPrep($row['firstName']) ?>" type="text" class="standardWidth">
							<script type="text/javascript">
								var firstName=new LiveValidation('firstName');
								firstName.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<tr>
						<td>
							<b><?php echo __($guid, 'Preferred Name') ?> *</b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Most common name, alias, nickname, etc.') ?></span>
						</td>
						<td class="right">
							<input name="preferredName" id="preferredName" maxlength=30 value="<?php echo htmlPrep($row['preferredName']) ?>" type="text" class="standardWidth">
							<script type="text/javascript">
								var preferredName=new LiveValidation('preferredName');
								preferredName.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<tr>
						<td>
							<b><?php echo __($guid, 'Official Name') ?> *</b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Full name as shown in ID documents.') ?></span>
						</td>
						<td class="right">
							<input name="officialName" id="officialName" maxlength=150 value="<?php echo htmlPrep($row['officialName']) ?>" type="text" class="standardWidth">
							<script type="text/javascript">
								var officialName=new LiveValidation('officialName');
								officialName.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<tr>
						<td>
							<b><?php echo __($guid, 'Name In Characters') ?></b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Chinese or other character-based name.') ?></span>
						</td>
						<td class="right">
							<input name="nameInCharacters" id="nameInCharacters" maxlength=20 value="<?php echo htmlPrep($row['nameInCharacters']) ?>" type="text" class="standardWidth">
						</td>
					</tr>
					<tr>
						<td>
							<b><?php echo __($guid, 'Gender') ?> *</b><br/>
						</td>
						<td class="right">
							<select name="gender" id="gender" class="standardWidth">
								<option value="Please select..."><?php echo __($guid, 'Please select...') ?></option>
								<option <?php if ($row['gender'] == 'F') { echo 'selected '; } ?>value="F"><?php echo __($guid, 'Female') ?></option>
								<option <?php if ($row['gender'] == 'M') { echo 'selected '; } ?>value="M"><?php echo __($guid, 'Male') ?></option>
								<option <?php if ($row['gender'] == 'Other') { echo 'selected '; } ?>value="Other"><?php echo __($guid, 'Other') ?></option>
								<option <?php if ($row['gender'] == 'Unspecified') { echo 'selected '; } ?>value="Unspecified"><?php echo __($guid, 'Unspecified') ?></option>
							</select>
							<script type="text/javascript">
								var gender=new LiveValidation('gender');
								gender.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
							</script>
						</td>
					</tr>
					<tr>
						<td>
							<b><?php echo __($guid, 'Date of Birth') ?></b><br/>
							<span class="emphasis small"><?php echo $_SESSION[$guid]['i18n']['dateFormat']  ?></span>
						</td>
						<td class="right">
							<?php
                            $value = '';
							if ($row['dob'] != null and $row['dob'] != '' and $row['dob'] != '0000-00-00') {
								$value = dateConvertBack($guid, $row['dob']);
							}
							?>
							<input name="dob" id="dob" maxlength=10 value="<?php echo $value ?>" type="text" class="standardWidth">
							<script type="text/javascript">
								var dob=new LiveValidation('dob');
								dob.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]['i18n']['dateFormatRegEx'] == '') {
								echo "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i";
								} else {
									echo $_SESSION[$guid]['i18n']['dateFormatRegEx'];
								}
											?>, failureMessage: "Use <?php if ($_SESSION[$guid]['i18n']['dateFormat'] == '') {
									echo 'dd/mm/yyyy';
								} else {
									echo $_SESSION[$guid]['i18n']['dateFormat'];
								}
								?>." } );
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
							<b><?php echo __($guid, 'User Photo') ?></b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Displayed at 240px by 320px.').'<br/>'.__($guid, 'Accepts images up to 360px by 480px.').'<br/>'.__($guid, 'Accepts aspect ratio between 1:1.2 and 1:1.4.') ?><br/>
							<?php if ($row['image_240'] != '') { echo __($guid, 'Will overwrite existing attachment.'); } ?>
							</span>
						</td>
						<td class="right">
							<?php
                            if ($row['image_240'] != '') {
                                echo __($guid, 'Current attachment:')." <a target='_blank' href='".$_SESSION[$guid]['absoluteURL'].'/'.$row['image_240']."'>".$row['image_240']."</a> <a href='".$_SESSION[$guid]['absoluteURL']."/modules/User Admin/user_manage_edit_photoDeleteProcess.php?gibbonPersonID=$gibbonPersonID&search=$search&size=240' onclick='return confirm(\"Are you sure you want to delete this record? Unsaved changes will be lost.\")'><img style='margin-bottom: -8px' id='image_240_delete' title='".__($guid, 'Delete')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/garbage.png'/></a><br/><br/>";
                            }
            				?>
							<input type="file" name="file1" id="file1"><br/><br/>
							<input type="hidden" name="attachment1" value='<?php echo $row['image_240'] ?>'>
							<script type="text/javascript">
								var file1=new LiveValidation('file1');
								file1.add( Validate.Inclusion, { within: ['gif','jpg','jpeg','png'], failureMessage: "Illegal file type!", partialMatch: true, caseSensitive: false } );
							</script>
						</td>
					</tr>


					<tr class='break'>
						<td colspan=2>
							<h3><?php echo __($guid, 'System Acces') ?>s</h3>
						</td>
					</tr>
					<tr>
						<td>
							<b><?php echo __($guid, 'Primary Role') ?> *</b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Controls what a user can do and see.') ?></span>
						</td>
						<td class="right">
							<select name="gibbonRoleIDPrimary" id="gibbonRoleIDPrimary" class="standardWidth">
								<?php
                                echo "<option value='Please select...'>".__($guid, 'Please select...').'</option>';
								try {
									$dataSelect = array();
									$sqlSelect = 'SELECT * FROM gibbonRole ORDER BY name';
									$resultSelect = $connection2->prepare($sqlSelect);
									$resultSelect->execute($dataSelect);
								} catch (PDOException $e) {
									echo "<div class='error'>".$e->getMessage().'</div>';
								}
								while ($rowSelect = $resultSelect->fetch()) {
									$selected = '';
									if ($row['gibbonRoleIDPrimary'] == $rowSelect['gibbonRoleID']) {
										$selected = 'selected';
									}

									echo "<option $selected value='".$rowSelect['gibbonRoleID']."'>".htmlPrep(__($guid, $rowSelect['name'])).'</option>';
								}
								?>
							</select>
							<script type="text/javascript">
								var gibbonRoleIDPrimary=new LiveValidation('gibbonRoleIDPrimary');
								gibbonRoleIDPrimary.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
							</script>
						</td>
					</tr>
					<tr>
						<td>
							<b><?php echo __($guid, 'All Roles') ?></b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Controls what a user can do and see.') ?></span>
						</td>
						<td class="right">
							<select multiple name="gibbonRoleIDAll[]" id="gibbonRoleIDAll[]" style="width: 302px; height: 130px">
								<?php
                                try {
                                    $dataSelect = array();
                                    $sqlSelect = 'SELECT * FROM gibbonRole ORDER BY name';
                                    $resultSelect = $connection2->prepare($sqlSelect);
                                    $resultSelect->execute($dataSelect);
                                } catch (PDOException $e) {
                                    echo "<div class='error'>".$e->getMessage().'</div>';
                                }
								while ($rowSelect = $resultSelect->fetch()) {
									$selected = '';
									$roles = explode(',', $row['gibbonRoleIDAll']);
									foreach ($roles as $role) {
										if ($role == $rowSelect['gibbonRoleID']) {
											$selected = 'selected';
										}
									}

									echo "<option $selected value='".$rowSelect['gibbonRoleID']."'>".htmlPrep(__($guid, $rowSelect['name'])).'</option>';
								}
								?>
							</select>
							<script type="text/javascript">
								var gibbonRoleIDPrimary=new LiveValidation('gibbonRoleIDPrimary');
								gibbonRoleIDPrimary.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
							</script>
						</td>
					</tr>
					<tr>
						<td>
							<b><?php echo __($guid, 'Username') ?> *</b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Must be unique. System login name.') ?></span>
						</td>
						<td class="right">
							<input readonly name="username" id="username" maxlength=20 value="<?php echo htmlPrep($row['username']) ?>" type="text" class="standardWidth">
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
							<b><?php echo __($guid, 'Status') ?> *</b><br/>
							<span class="emphasis small"><?php echo __($guid, 'This determines visibility within the system.') ?></span>
						</td>
						<td class="right">
							<select class="standardWidth" name="status" id='status'>
								<option <?php if ($row['status'] == 'Full') { echo 'selected '; } ?>value="Full"><?php echo __($guid, 'Full') ?></option>
								<option <?php if ($row['status'] == 'Expected') { echo 'selected '; } ?>value="Expected"><?php echo __($guid, 'Expected') ?></option>
								<option <?php if ($row['status'] == 'Left') { echo 'selected '; } ?>value="Left"><?php echo __($guid, 'Left') ?></option>
								<option <?php if ($row['status'] == 'Pending Approval') { echo 'selected '; } ?>value="Pending Approval"><?php echo __($guid, 'Pending Approval') ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<td>
							<b><?php echo __($guid, 'Can Login?') ?> *</b><br/>
							<span class="emphasis small"></span>
						</td>
						<td class="right">
							<select class="standardWidth" name="canLogin">
								<option <?php if ($row['canLogin'] == 'Y') { echo 'selected '; } ?>value="Y"><?php echo __($guid, 'Yes') ?></option>
								<option <?php if ($row['canLogin'] == 'N') { echo 'selected '; } ?>value="N"><?php echo __($guid, 'No') ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<td>
							<b><?php echo __($guid, 'Force Reset Password?') ?> *</b><br/>
							<span class="emphasis small">User will be prompted on next login.</span>
						</td>
						<td class="right">
							<select class="standardWidth" name="passwordForceReset">
								<option <?php if ($row['passwordForceReset'] == 'Y') { echo 'selected '; } ?>value="Y"><?php echo __($guid, 'Yes') ?></option>
								<option <?php if ($row['passwordForceReset'] == 'N') { echo 'selected '; } ?>value="N"><?php echo __($guid, 'No') ?></option>
							</select>
						</td>
					</tr>

					<tr class='break'>
						<td colspan=2>
							<h3><?php echo __($guid, 'Contact Information') ?></h3>
						</td>
					</tr>
					<tr>
						<td>
							<b><?php echo __($guid, 'Email') ?></b><br/>
						</td>
						<td class="right">
							<input name="email" id="email" maxlength=50 value="<?php echo htmlPrep($row['email']) ?>" type="text" class="standardWidth">
							<script type="text/javascript">
								var email=new LiveValidation('email');
								email.add(Validate.Email);
							</script>
						</td>
					</tr>
					<tr>
						<td>
							<b><?php echo __($guid, 'Alternate Email') ?></b><br/>
							<span class="emphasis small"></span>
						</td>
						<td class="right">
							<input name="emailAlternate" id="emailAlternate" maxlength=50 value="<?php echo htmlPrep($row['emailAlternate']) ?>" type="text" class="standardWidth">
							<script type="text/javascript">
								var emailAlternate=new LiveValidation('emailAlternate');
								emailAlternate.add(Validate.Email);
							</script>
						</td>
					</tr>
					<tr>
						<td colspan=2>
							<div class='warning'>
								<?php echo __($guid, 'Address information for an individual only needs to be set under the following conditions:') ?>
								<ol>
									<li><?php echo __($guid, 'If the user is not in a family.') ?></li>
									<li><?php echo __($guid, 'If the user\'s family does not have a home address set.') ?></li>
									<li><?php echo __($guid, 'If the user needs an address in addition to their family\'s home address.') ?></li>
								</ol>
							</div>
						</td>
					</tr>
					<?php
                    //Controls to hide address fields unless they are present, or box is checked
                    $addressSet = false;
					if ($row['address1'] != '' or $row['address1District'] != '' or $row['address1Country'] != '' or $row['address2'] != '' or $row['address2District'] != '' or $row['address2Country'] != '') {
						$addressSet = true;
					}
					?>
					<tr>
						<td>
							<b><?php echo __($guid, 'Enter Personal Address?') ?></b><br/>
						</td>
						<td class='right' colspan=2>
							<script type="text/javascript">
								/* Advanced Options Control */
								$(document).ready(function(){
									<?php
                                    if ($addressSet == false) {
                                        echo '$(".address").slideUp("fast"); ';
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
							<input <?php if ($addressSet) { echo 'checked'; } ?> id='showAddresses' name='showAddresses' type='checkbox' value='Yes'/>
						</td>
					</tr>
					<tr class='address'>
						<td>
							<b><?php echo __($guid, 'Address 1') ?></b><br/>
							<span class="emphasis small"><span class="emphasis small"><?php echo __($guid, 'Unit, Building, Street') ?></span></span>
						</td>
						<td class="right">
							<input name="address1" id="address1" maxlength=255 value="<?php echo htmlPrep($row['address1']) ?>" type="text" class="standardWidth">
						</td>
					</tr>
					<tr class='address'>
						<td>
							<b><?php echo __($guid, 'Address 1 District') ?></b><br/>
							<span class="emphasis small"><?php echo __($guid, 'County, State, District') ?></span>
						</td>
						<td class="right">
							<input name="address1District" id="address1District" maxlength=30 value="<?php echo $row['address1District'] ?>" type="text" class="standardWidth">
						</td>
						<script type="text/javascript">
							$(function() {
								var availableTags=[
									<?php
                                    try {
                                        $dataAuto = array();
                                        $sqlAuto = 'SELECT DISTINCT name FROM gibbonDistrict ORDER BY name';
                                        $resultAuto = $connection2->prepare($sqlAuto);
                                        $resultAuto->execute($dataAuto);
                                    } catch (PDOException $e) {
                                    }
									while ($rowAuto = $resultAuto->fetch()) {
										echo '"'.$rowAuto['name'].'", ';
									}
									?>
								];
								$( "#address1District" ).autocomplete({source: availableTags});
							});
						</script>
					</tr>
					<tr class='address'>
						<td>
							<b><?php echo __($guid, 'Address 1 Country') ?></b><br/>
						</td>
						<td class="right">
							<select name="address1Country" id="address1Country" class="standardWidth">
								<?php
                                echo "<option value=''></option>";
								try {
									$dataSelect = array();
									$sqlSelect = 'SELECT printable_name FROM gibbonCountry ORDER BY printable_name';
									$resultSelect = $connection2->prepare($sqlSelect);
									$resultSelect->execute($dataSelect);
								} catch (PDOException $e) {
									echo "<div class='error'>".$e->getMessage().'</div>';
								}
								while ($rowSelect = $resultSelect->fetch()) {
									$selected = '';
									if ($rowSelect['printable_name'] == $row['address1Country']) {
										$selected = ' selected';
									}
									echo "<option $selected value='".$rowSelect['printable_name']."'>".htmlPrep(__($guid, $rowSelect['printable_name'])).'</option>';
								}
								?>
							</select>
						</td>
					</tr>

					<?php
                    //Check for matching addresses
                    if ($row['address1'] != '') {
                        try {
                            $dataAddress = array('gibbonPersonID' => $row['gibbonPersonID'], 'addressMatch' => '%'.strtolower(preg_replace('/ /', '%', preg_replace('/,/', '%', $row['address1']))).'%');
                            $sqlAddress = "SELECT gibbonPersonID, title, preferredName, surname, category FROM gibbonPerson JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) WHERE status='Full' AND address1 LIKE :addressMatch AND NOT gibbonPersonID=:gibbonPersonID ORDER BY surname, preferredName";
                            $resultAddress = $connection2->prepare($sqlAddress);
                            $resultAddress->execute($dataAddress);
                        } catch (PDOException $e) {
                            echo "<div class='error'>".$e->getMessage().'</div>';
                        }
                        if ($resultAddress->rowCount() > 0) {
                            $addressCount = 0;
                            echo "<tr class='address'>";
                            echo "<td style='border-top: 1px dashed #c00; border-bottom: 1px dashed #c00; background-color: #F6CECB'> ";
                            echo '<b>'.__($guid, 'Matching Address 1').'</b><br/>';
                            echo "<span style='font-size: 90%'><i>".__($guid, 'These users have similar Address 1. Do you want to change them too?').'</span>';
                            echo '</td>';
                            echo "<td style='text-align: right; border-top: 1px dashed #c00; border-bottom: 1px dashed #c00; background-color: #F6CECB'> ";
                            echo "<table cellspacing='0' style='width:306px; float: right; padding: 0px; margin: 0px'>";
                            while ($rowAddress = $resultAddress->fetch()) {
                                echo '<tr>';
                                echo "<td style='padding-left: 0px; padding-right: 0px; width:200px'>";
                                echo "<input readonly style='float: left; margin-left: 0px; width: 200px' type='text' value='".formatName($rowAddress['title'], $rowAddress['preferredName'], $rowAddress['surname'], $rowAddress['category']).' ('.$rowAddress['category'].")'>".'<br/>';
                                echo '</td>';
                                echo "<td style='padding-left: 0px; padding-right: 0px; width:60px'>";
                                echo "<input type='checkbox' name='$addressCount-matchAddress' value='".$rowAddress['gibbonPersonID']."'>".'<br/>';
                                echo '</td>';
                                echo '</tr>';
                                ++$addressCount;
                            }
                            echo '</table>';
                            echo '</td>';
                            echo '</tr>';
                            echo "<input type='hidden' name='matchAddressCount' value='$addressCount'>".'<br/>';
                        }
                    }
            		?>

					<tr class='address'>
						<td>
							<b><?php echo __($guid, 'Address 2') ?></b><br/>
							<span class="emphasis small"><span class="emphasis small"><?php echo __($guid, 'Unit, Building, Street') ?></span></span>
						</td>
						<td class="right">
							<input name="address2" id="address2" maxlength=255 value="<?php echo htmlPrep($row['address2']) ?>" type="text" class="standardWidth">
						</td>
					</tr>
					<tr class='address'>
						<td>
							<b><?php echo __($guid, 'Address 2 District') ?></b><br/>
							<span class="emphasis small"><?php echo __($guid, 'County, State, District') ?></span>
						</td>
						<td class="right">
							<input name="address2District" id="address2District" maxlength=30 value="<?php echo $row['address2District'] ?>" type="text" class="standardWidth">
						</td>
						<script type="text/javascript">
							$(function() {
								var availableTags=[
									<?php
                                    try {
                                        $dataAuto = array();
                                        $sqlAuto = 'SELECT DISTINCT name FROM gibbonDistrict ORDER BY name';
                                        $resultAuto = $connection2->prepare($sqlAuto);
                                        $resultAuto->execute($dataAuto);
                                    } catch (PDOException $e) {
                                    }
									while ($rowAuto = $resultAuto->fetch()) {
										echo '"'.$rowAuto['name'].'", ';
									}
									?>
								];
								$( "#address2District" ).autocomplete({source: availableTags});
							});
						</script>
					</tr>
					<tr class='address'>
						<td>
							<b><?php echo __($guid, 'Address 2 Country') ?></b><br/>
						</td>
						<td class="right">
							<select name="address2Country" id="address2Country" class="standardWidth">
								<?php
                                echo "<option value=''></option>";
								try {
									$dataSelect = array();
									$sqlSelect = 'SELECT printable_name FROM gibbonCountry ORDER BY printable_name';
									$resultSelect = $connection2->prepare($sqlSelect);
									$resultSelect->execute($dataSelect);
								} catch (PDOException $e) {
									echo "<div class='error'>".$e->getMessage().'</div>';
								}
								while ($rowSelect = $resultSelect->fetch()) {
									$selected = '';
									if ($rowSelect['printable_name'] == $row['address2Country']) {
										$selected = ' selected';
									}
									echo "<option $selected value='".$rowSelect['printable_name']."'>".htmlPrep(__($guid, $rowSelect['printable_name'])).'</option>';
								}
								?>
							</select>
						</td>
					</tr>
					<?php
                    for ($i = 1; $i < 5; ++$i) {
                        ?>
						<tr>
							<td>
								<b><?php echo __($guid, 'Phone') ?> <?php echo $i ?></b><br/>
								<span class="emphasis small"><?php echo __($guid, 'Type, country code, number.') ?></span>
							</td>
							<td class="right">
								<input name="phone<?php echo $i ?>" id="phone<?php echo $i ?>" maxlength=20 value="<?php echo $row['phone'.$i] ?>" type="text" style="width: 160px">
								<select name="phone<?php echo $i ?>CountryCode" id="phone<?php echo $i ?>CountryCode" style="width: 60px">
									<?php
                                    echo "<option value=''></option>";
									try {
										$dataSelect = array();
										$sqlSelect = 'SELECT * FROM gibbonCountry ORDER BY printable_name';
										$resultSelect = $connection2->prepare($sqlSelect);
										$resultSelect->execute($dataSelect);
									} catch (PDOException $e) {
									}
									while ($rowSelect = $resultSelect->fetch()) {
										$selected = '';
										if ($row['phone'.$i.'CountryCode'] != '' and $row['phone'.$i.'CountryCode'] == $rowSelect['iddCountryCode']) {
											$selected = 'selected';
										}
										echo "<option $selected value='".$rowSelect['iddCountryCode']."'>".htmlPrep($rowSelect['iddCountryCode']).' - '.htmlPrep(__($guid, $rowSelect['printable_name'])).'</option>';
									}
									?>
								</select>
								<select style="width: 70px" name="phone<?php echo $i ?>Type">
									<option <?php if ($row['phone'.$i.'Type'] == '') { echo 'selected'; } ?> value=""></option>
									<option <?php if ($row['phone'.$i.'Type'] == 'Mobile') { echo 'selected'; } ?> value="Mobile"><?php echo __($guid, 'Mobile') ?></option>
									<option <?php if ($row['phone'.$i.'Type'] == 'Home') { echo 'selected'; } ?> value="Home"><?php echo __($guid, 'Home') ?></option>
									<option <?php if ($row['phone'.$i.'Type'] == 'Work') { echo 'selected'; } ?> value="Work"><?php echo __($guid, 'Work') ?></option>
									<option <?php if ($row['phone'.$i.'Type'] == 'Fax') { echo 'selected'; } ?> value="Fax"><?php echo __($guid, 'Fax') ?></option>
									<option <?php if ($row['phone'.$i.'Type'] == 'Pager') { echo 'selected'; } ?> value="Pager"><?php echo __($guid, 'Pager') ?></option>
									<option <?php if ($row['phone'.$i.'Type'] == 'Other') { echo 'selected'; } ?> value="Other"><?php echo __($guid, 'Other') ?></option>
								</select>
							</td>
						</tr>
						<?php
					}
                    ?>
					<tr>
						<td>
							<b><?php echo __($guid, 'Website') ?></b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Include http://') ?></span>
						</td>
						<td class="right">
							<input name="website" id="website" maxlength=255 value="<?php echo htmlPrep($row['website']) ?>" type="text" class="standardWidth">
							<script type="text/javascript">
								var website=new LiveValidation('website');
								website.add( Validate.Format, { pattern: /(http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/, failureMessage: "Must start with http:// or https://" } );
							</script>
						</td>
					</tr>


					<tr class='break'>
						<td colspan=2>
							<h3><?php echo __($guid, 'School Information') ?></h3>
						</td>
					</tr>
					<?php
                    if ($student) {
                        $dayTypeOptions = getSettingByScope($connection2, 'User Admin', 'dayTypeOptions');
                        if ($dayTypeOptions != '') {
                            ?>
							<tr>
								<td>
									<b><?php echo __($guid, 'Day Type') ?></b><br/>
									<span class="emphasis small"><?php echo getSettingByScope($connection2, 'User Admin', 'dayTypeText'); ?></span>
								</td>
								<td class="right">
									<select name="dayType" id="dayType" class="standardWidth">
										<option value=''></option>
										<?php
                                        $dayTypes = explode(',', $dayTypeOptions);
                            foreach ($dayTypes as $dayType) {
                                $selected = '';
                                if ($row['dayType'] == $dayType) {
                                    $selected = 'selected';
                                }
                                echo "<option $selected value='".trim($dayType)."'>".trim($dayType).'</option>';
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
								<b><?php echo __($guid, 'Last School') ?></b><br/>
							</td>
							<td class="right">
								<input name="lastSchool" id="lastSchool" maxlength=30 value="<?php echo $row['lastSchool'] ?>" type="text" class="standardWidth">
							</td>
							<script type="text/javascript">
								$(function() {
									var availableTags=[
										<?php
                                        try {
                                            $dataAuto = array();
                                            $sqlAuto = 'SELECT DISTINCT lastSchool FROM gibbonPerson ORDER BY lastSchool';
                                            $resultAuto = $connection2->prepare($sqlAuto);
                                            $resultAuto->execute($dataAuto);
                                        } catch (PDOException $e) {
                                        }
										while ($rowAuto = $resultAuto->fetch()) {
											echo '"'.$rowAuto['lastSchool'].'", ';
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
							<b><?php echo __($guid, 'Start Date') ?></b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Users\'s first day at school.') ?><br/> <?php echo __($guid, 'Format:').' ';
							if ($_SESSION[$guid]['i18n']['dateFormat'] == '') {
								echo 'dd/mm/yyyy';
							} else {
								echo $_SESSION[$guid]['i18n']['dateFormat'];
							}
							?></span>
						</td>
						<td class="right">
							<input name="dateStart" id="dateStart" maxlength=10 value="<?php echo dateConvertBack($guid, $row['dateStart']) ?>" type="text" class="standardWidth">
							<script type="text/javascript">
								var dateStart=new LiveValidation('dateStart');
								dateStart.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]['i18n']['dateFormatRegEx'] == '') {
								echo "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i";
								} else {
									echo $_SESSION[$guid]['i18n']['dateFormatRegEx'];
								}
											?>, failureMessage: "Use <?php if ($_SESSION[$guid]['i18n']['dateFormat'] == '') {
									echo 'dd/mm/yyyy';
								} else {
									echo $_SESSION[$guid]['i18n']['dateFormat'];
								}
								?>." } );
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
							<b><?php echo __($guid, 'End Date') ?></b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Users\'s last day at school.') ?><br/> <?php echo __($guid, 'Format:').' ';
							if ($_SESSION[$guid]['i18n']['dateFormat'] == '') {
								echo 'dd/mm/yyyy';
							} else {
								echo $_SESSION[$guid]['i18n']['dateFormat'];
							}
							?></span>
						</td>
						<td class="right">
							<input name="dateEnd" id="dateEnd" maxlength=10 value="<?php echo dateConvertBack($guid, $row['dateEnd']) ?>" type="text" class="standardWidth">
							<script type="text/javascript">
								var dateEnd=new LiveValidation('dateEnd');
								dateEnd.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]['i18n']['dateFormatRegEx'] == '') {
								echo "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i";
								} else {
									echo $_SESSION[$guid]['i18n']['dateFormatRegEx'];
								}
											?>, failureMessage: "Use <?php if ($_SESSION[$guid]['i18n']['dateFormat'] == '') {
									echo 'dd/mm/yyyy';
								} else {
									echo $_SESSION[$guid]['i18n']['dateFormat'];
								}
								?>." } );
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
								<b><?php echo __($guid, 'Class Of') ?></b><br/>
								<span class="emphasis small"><?php echo __($guid, 'When is the student expected to graduate?') ?></span>
							</td>
							<td class="right">
								<select name="gibbonSchoolYearIDClassOf" id="gibbonSchoolYearIDClassOf" class="standardWidth">
									<?php
                                    echo "<option value=''></option>";
									try {
										$dataSelect = array();
										$sqlSelect = 'SELECT * FROM gibbonSchoolYear ORDER BY sequenceNumber';
										$resultSelect = $connection2->prepare($sqlSelect);
										$resultSelect->execute($dataSelect);
									} catch (PDOException $e) {
										echo "<div class='error'>".$e->getMessage().'</div>';
									}
									while ($rowSelect = $resultSelect->fetch()) {
										$selected = '';
										if ($row['gibbonSchoolYearIDClassOf'] == $rowSelect['gibbonSchoolYearID']) {
											$selected = 'selected';
										}
										echo "<option $selected value='".$rowSelect['gibbonSchoolYearID']."'>".htmlPrep($rowSelect['name']).'</option>';
									}
									?>
								</select>
							</td>
						</tr>
						<?php

                    }
					if ($student or $staff) {
						?>
						<tr>
							<td>
								<b><?php echo __($guid, 'Next School') ?></b><br/>
							</td>
							<td class="right">
								<input name="nextSchool" id="nextSchool" maxlength=30 value="<?php echo $row['nextSchool'] ?>" type="text" class="standardWidth">
							</td>
							<script type="text/javascript">
								$(function() {
									var availableTags=[
										<?php
                                        try {
                                            $dataAuto = array();
                                            $sqlAuto = 'SELECT DISTINCT nextSchool FROM gibbonPerson ORDER BY nextSchool';
                                            $resultAuto = $connection2->prepare($sqlAuto);
                                            $resultAuto->execute($dataAuto);
                                        } catch (PDOException $e) {
                                        }
										while ($rowAuto = $resultAuto->fetch()) {
											echo '"'.$rowAuto['nextSchool'].'", ';
										}
										?>
									];
									$( "#nextSchool" ).autocomplete({source: availableTags});
								});
							</script>
						</tr>
						<?php

            }
            if ($student or $staff) {
                ?>
						<tr>
							<td>
								<b><?php echo __($guid, 'Departure Reason') ?></b><br/>
							</td>
							<td class="right">
								<?php
                                $departureReasonsList = getSettingByScope($connection2, 'User Admin', 'departureReasons');
								if ($departureReasonsList != '') {
									echo '<select name="departureReason" id="departureReason" style="width: 302px">';
									echo "<option value=''></option>";
									$departureReasons = explode(',', $departureReasonsList);
									foreach ($departureReasons as $departureReason) {
										$selected = '';
										if (trim($departureReason) == $row['departureReason']) {
											$selected = 'selected';
										}
										echo "<option $selected value='".trim($departureReason)."'>".trim($departureReason).'</option>';
									}
									echo '</select>';
								} else {
									?>
									<input name="departureReason" id="departureReason" maxlength=30 value="<?php echo $row['departureReason'] ?>" type="text" class="standardWidth">
									<script type="text/javascript">
										$(function() {
											var availableTags=[
												<?php
                                                try {
                                                    $dataAuto = array();
                                                    $sqlAuto = 'SELECT DISTINCT departureReason FROM gibbonPerson ORDER BY departureReason';
                                                    $resultAuto = $connection2->prepare($sqlAuto);
                                                    $resultAuto->execute($dataAuto);
                                                } catch (PDOException $e) {
                                                }
												while ($rowAuto = $resultAuto->fetch()) {
													echo '"'.$rowAuto['departureReason'].'", ';
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
							<h3><?php echo __($guid, 'Background Information') ?></h3>
						</td>
					</tr>
					<tr>
						<td>
							<b><?php echo __($guid, 'First Language') ?></b><br/>
						</td>
						<td class="right">
							<select name="languageFirst" id="languageFirst" class="standardWidth">
								<?php
                                echo "<option value=''></option>";
								try {
									$dataSelect = array();
									$sqlSelect = 'SELECT name FROM gibbonLanguage ORDER BY name';
									$resultSelect = $connection2->prepare($sqlSelect);
									$resultSelect->execute($dataSelect);
								} catch (PDOException $e) {
								}
								while ($rowSelect = $resultSelect->fetch()) {
									$selected = '';
									if ($row['languageFirst'] == $rowSelect['name']) {
										$selected = 'selected';
									}
									echo "<option $selected value='".$rowSelect['name']."'>".htmlPrep(__($guid, $rowSelect['name'])).'</option>';
								}
								?>
							</select>
						</td>
					</tr>
					<tr>
						<td>
							<b><?php echo __($guid, 'Second Language') ?></b><br/>
						</td>
						<td class="right">
							<select name="languageSecond" id="languageSecond" class="standardWidth">
								<?php
                                echo "<option value=''></option>";
								try {
									$dataSelect = array();
									$sqlSelect = 'SELECT name FROM gibbonLanguage ORDER BY name';
									$resultSelect = $connection2->prepare($sqlSelect);
									$resultSelect->execute($dataSelect);
								} catch (PDOException $e) {
								}
								while ($rowSelect = $resultSelect->fetch()) {
									$selected = '';
									if ($row['languageSecond'] == $rowSelect['name']) {
										$selected = 'selected';
									}
									echo "<option $selected value='".$rowSelect['name']."'>".htmlPrep(__($guid, $rowSelect['name'])).'</option>';
								}
								?>
							</select>
						</td>
					</tr>
					<tr>
						<td>
							<b><?php echo __($guid, 'Third Language') ?></b><br/>
						</td>
						<td class="right">
							<select name="languageThird" id="languageThird" class="standardWidth">
								<?php
                                echo "<option value=''></option>";
								try {
									$dataSelect = array();
									$sqlSelect = 'SELECT name FROM gibbonLanguage ORDER BY name';
									$resultSelect = $connection2->prepare($sqlSelect);
									$resultSelect->execute($dataSelect);
								} catch (PDOException $e) {
								}
								while ($rowSelect = $resultSelect->fetch()) {
									$selected = '';
									if ($row['languageThird'] == $rowSelect['name']) {
										$selected = 'selected';
									}
									echo "<option $selected value='".$rowSelect['name']."'>".htmlPrep(__($guid, $rowSelect['name'])).'</option>';
								}
								?>
							</select>
						</td>
					</tr>
					<tr>
						<td>
							<b><?php echo __($guid, 'Country of Birth') ?></b><br/>
						</td>
						<td class="right">
							<select name="countryOfBirth" id="countryOfBirth" class="standardWidth">
								<?php
                                echo "<option value=''></option>";
								try {
									$dataSelect = array();
									$sqlSelect = 'SELECT printable_name FROM gibbonCountry ORDER BY printable_name';
									$resultSelect = $connection2->prepare($sqlSelect);
									$resultSelect->execute($dataSelect);
								} catch (PDOException $e) {
									echo "<div class='error'>".$e->getMessage().'</div>';
								}
								while ($rowSelect = $resultSelect->fetch()) {
									$selected = '';
									if ($rowSelect['printable_name'] == $row['countryOfBirth']) {
										$selected = ' selected';
									}
									echo "<option $selected value='".$rowSelect['printable_name']."'>".htmlPrep(__($guid, $rowSelect['printable_name'])).'</option>';
								}
								?>
							</select>
						</td>
					</tr>
                    <tr>
						<td>
							<b><?php echo __($guid, 'Birth Certificate Scan') ?></b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Less than 1440px by 900px') ?><br/>
							<?php if ($row['birthCertificateScan'] != '') { echo __($guid, 'Will overwrite existing attachment.'); } ?>
							</span>
						</td>
						<td class="right">
							<?php
                            if ($row['birthCertificateScan'] != '') {
                                echo __($guid, 'Current attachment:')." <a target='_blank' href='".$_SESSION[$guid]['absoluteURL'].'/'.$row['birthCertificateScan']."'>".$row['birthCertificateScan']."</a> <a href='".$_SESSION[$guid]['absoluteURL']."/modules/User Admin/user_manage_edit_photoDeleteProcess.php?gibbonPersonID=$gibbonPersonID&search=$search&size=birthCertificate' onclick='return confirm(\"Are you sure you want to delete this record? Unsaved changes will be lost.\")'><img style='margin-bottom: -8px' title='".__($guid, 'Delete')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/garbage.png'/></a><br/><br/>";
                            }
            				?>
							<input type="file" name="birthCertificateScan" id="birthCertificateScan"><br/><br/>
							<input type="hidden" name="birthCertificateScanCurrent" value='<?php echo $row['birthCertificateScan'] ?>'>
							<script type="text/javascript">
								var birthCertificateScan=new LiveValidation('birthCertificateScan');
								birthCertificateScan.add( Validate.Inclusion, { within: ['gif','jpg','jpeg','png'], failureMessage: "Illegal file type!", partialMatch: true, caseSensitive: false } );
							</script>
						</td>
					</tr>
					<tr>
						<td>
							<b><?php echo __($guid, 'Ethnicity') ?></b><br/>
						</td>
						<td class="right">
							<select name="ethnicity" id="ethnicity" class="standardWidth">
								<option <?php if ($row['ethnicity'] == '') { echo 'selected '; } ?>value=""></option>
								<?php
                                $ethnicities = explode(',', getSettingByScope($connection2, 'User Admin', 'ethnicity'));
								foreach ($ethnicities as $ethnicity) {
									$selected = '';
									if (trim($ethnicity) == $row['ethnicity']) {
										$selected = 'selected';
									}
									echo "<option $selected value='".trim($ethnicity)."'>".trim($ethnicity).'</option>';
								}
								?>
							</select>
						</td>
					</tr>
					<tr>
						<td>
							<b><?php echo __($guid, 'Religion') ?></b><br/>
						</td>
						<td class="right">
							<select name="religion" id="religion" class="standardWidth">
								<option <?php if ($row['religion'] == '') { echo 'selected '; } ?>value=""></option>
								<?php
                                $religions = explode(',', getSettingByScope($connection2, 'User Admin', 'religions'));
								foreach ($religions as $religion) {
									$selected = '';
									if (trim($religion) == $row['religion']) {
										$selected = 'selected';
									}
									echo "<option $selected value='".trim($religion)."'>".trim($religion).'</option>';
								}
								?>
							</select>
						</td>
					</tr>
					<tr>
						<td>
							<b><?php echo __($guid, 'Citizenship 1') ?></b><br/>
						</td>
						<td class="right">
							<select name="citizenship1" id="citizenship1" class="standardWidth">
								<?php
                                echo "<option value=''></option>";
								$nationalityList = getSettingByScope($connection2, 'User Admin', 'nationality');
								if ($nationalityList == '') {
									try {
										$dataSelect = array();
										$sqlSelect = 'SELECT printable_name FROM gibbonCountry ORDER BY printable_name';
										$resultSelect = $connection2->prepare($sqlSelect);
										$resultSelect->execute($dataSelect);
									} catch (PDOException $e) {
									}
									while ($rowSelect = $resultSelect->fetch()) {
										$selected = '';
										if ($rowSelect['printable_name'] == $row['citizenship1']) {
											$selected = 'selected';
										}
										echo "<option $selected value='".$rowSelect['printable_name']."'>".htmlPrep(__($guid, $rowSelect['printable_name'])).'</option>';
									}
								} else {
									$nationalities = explode(',', $nationalityList);
									foreach ($nationalities as $nationality) {
										$selected = '';
										if (trim($nationality) == $row['citizenship1']) {
											$selected = 'selected';
										}
										echo "<option $selected value='".trim($nationality)."'>".trim($nationality).'</option>';
									}
								}
								?>
							</select>
						</td>
					</tr>
					<tr>
						<td>
							<b><?php echo __($guid, 'Citizenship 1 Passport Number') ?></b><br/>
						</td>
						<td class="right">
							<input name="citizenship1Passport" id="citizenship1Passport" maxlength=30 value="<?php echo htmlPrep($row['citizenship1Passport']) ?>" type="text" class="standardWidth">
						</td>
					</tr>
					<tr>
						<td>
							<b><?php echo __($guid, 'Citizenship 1 Passport Scan') ?></b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Less than 1440px by 900px') ?><br/>
							<?php if ($row['citizenship1PassportScan'] != '') { echo __($guid, 'Will overwrite existing attachment.');} ?>
							</span>
						</td>
						<td class="right">
							<?php
                            if ($row['citizenship1PassportScan'] != '') {
                                echo __($guid, 'Current attachment:')." <a target='_blank' href='".$_SESSION[$guid]['absoluteURL'].'/'.$row['citizenship1PassportScan']."'>".$row['citizenship1PassportScan']."</a> <a href='".$_SESSION[$guid]['absoluteURL']."/modules/User Admin/user_manage_edit_photoDeleteProcess.php?gibbonPersonID=$gibbonPersonID&search=$search&size=passport' onclick='return confirm(\"Are you sure you want to delete this record? Unsaved changes will be lost.\")'><img style='margin-bottom: -8px' title='".__($guid, 'Delete')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/garbage.png'/></a><br/><br/>";
                            }
            				?>
							<input type="file" name="citizenship1PassportScan" id="citizenship1PassportScan"><br/><br/>
							<input type="hidden" name="citizenship1PassportScanCurrent" value='<?php echo $row['citizenship1PassportScan'] ?>'>
							<script type="text/javascript">
								var citizenship1PassportScan=new LiveValidation('citizenship1PassportScan');
								citizenship1PassportScan.add( Validate.Inclusion, { within: ['gif','jpg','jpeg','png'], failureMessage: "Illegal file type!", partialMatch: true, caseSensitive: false } );
							</script>
						</td>
					</tr>
					<tr>
						<td>
							<b><?php echo __($guid, 'Citizenship 2') ?></b><br/>
						</td>
						<td class="right">
							<select name="citizenship2" id="citizenship2" class="standardWidth">
								<?php
                                echo "<option value=''></option>";
								$nationalityList = getSettingByScope($connection2, 'User Admin', 'nationality');
								if ($nationalityList == '') {
									try {
										$dataSelect = array();
										$sqlSelect = 'SELECT printable_name FROM gibbonCountry ORDER BY printable_name';
										$resultSelect = $connection2->prepare($sqlSelect);
										$resultSelect->execute($dataSelect);
									} catch (PDOException $e) {
									}
									while ($rowSelect = $resultSelect->fetch()) {
										$selected = '';
										if ($rowSelect['printable_name'] == $row['citizenship2']) {
											$selected = 'selected';
										}
										echo "<option $selected value='".$rowSelect['printable_name']."'>".htmlPrep(__($guid, $rowSelect['printable_name'])).'</option>';
									}
								} else {
									$nationalities = explode(',', $nationalityList);
									foreach ($nationalities as $nationality) {
										$selected = '';
										if (trim($nationality) == $row['citizenship2']) {
											$selected = 'selected';
										}
										echo "<option $selected value='".trim($nationality)."'>".trim($nationality).'</option>';
									}
								}
								?>
							</select>
						</td>
					</tr>
					<tr>
						<td>
							<b><?php echo __($guid, 'Citizenship 2 Passport Number') ?></b><br/>
						</td>
						<td class="right">
							<input name="citizenship2Passport" id="citizenship2Passport" maxlength=30 value="<?php echo htmlPrep($row['citizenship2Passport']) ?>" type="text" class="standardWidth">
						</td>
					</tr>
					<tr>
						<td>
							<?php
                            if ($_SESSION[$guid]['country'] == '') {
                                echo '<b>'.__($guid, 'National ID Card Number').'</b><br/>';
                            } else {
                                echo '<b>'.$_SESSION[$guid]['country'].' '.__($guid, 'ID Card Number').'</b><br/>';
                            }
            				?>
						</td>
						<td class="right">
							<input name="nationalIDCardNumber" id="nationalIDCardNumber" maxlength=30 value="<?php echo htmlPrep($row['nationalIDCardNumber']) ?>" type="text" class="standardWidth">
						</td>
					</tr>
					<tr>
						<td>
							<?php
                            if ($_SESSION[$guid]['country'] == '') {
                                echo '<b>'.__($guid, 'National ID Card Scan').'</b><br/>';
                            } else {
                                echo '<b>'.$_SESSION[$guid]['country'].' '.__($guid, 'ID Card Scan').'</b><br/>';
                            }
            				?>
							<span class="emphasis small"><?php echo __($guid, 'Less than 1440px by 900px') ?></span>
						</td>
						<td class="right">
							<?php
                            if ($row['nationalIDCardScan'] != '') {
                                echo __($guid, 'Current attachment:')." <a target='_blank' href='".$_SESSION[$guid]['absoluteURL'].'/'.$row['nationalIDCardScan']."'>".$row['nationalIDCardScan']."</a> <a href='".$_SESSION[$guid]['absoluteURL']."/modules/User Admin/user_manage_edit_photoDeleteProcess.php?gibbonPersonID=$gibbonPersonID&search=$search&size=id' onclick='return confirm(\"Are you sure you want to delete this record? Unsaved changes will be lost.\")'><img style='margin-bottom: -8px' title='".__($guid, 'Delete')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/garbage.png'/></a><br/><br/>";
                            }
            				?>
							<input type="file" name="nationalIDCardScan" id="nationalIDCardScan"><br/><br/>
							<input type="hidden" name="nationalIDCardScanCurrent" value='<?php echo $row['nationalIDCardScan'] ?>'>
							<script type="text/javascript">
								var nationalIDCardScan=new LiveValidation('nationalIDCardScan');
								nationalIDCardScan.add( Validate.Inclusion, { within: ['gif','jpg','jpeg','png'], failureMessage: "Illegal file type!", partialMatch: true, caseSensitive: false } );
							</script>
						</td>
					</tr>

					<tr>
						<td>
							<?php
                            if ($_SESSION[$guid]['country'] == '') {
                                echo '<b>'.__($guid, 'Residency/Visa Type').'</b><br/>';
                            } else {
                                echo '<b>'.$_SESSION[$guid]['country'].' '.__($guid, 'Residency/Visa Type').'</b><br/>';
                            }
            				?>
						</td>
						<td class="right">
							<?php
                            $residencyStatusList = getSettingByScope($connection2, 'User Admin', 'residencyStatus');
							if ($residencyStatusList == '') {
								echo "<input name='residencyStatus' id='residencyStatus' maxlength=30 value='".$row['residencyStatus']."' type='text' style='width: 300px'>";
							} else {
								echo "<select name='residencyStatus' id='residencyStatus' style='width: 302px'>";
								echo "<option value=''></option>";
								$residencyStatuses = explode(',', $residencyStatusList);
								foreach ($residencyStatuses as $residencyStatus) {
									$selected = '';
									if (trim($residencyStatus) == $row['residencyStatus']) {
										$selected = 'selected';
									}
									echo "<option $selected value='".trim($residencyStatus)."'>".trim($residencyStatus).'</option>';
								}
								echo '</select>';
							}
							?>
						</td>
					</tr>
					<tr>
						<td>
							<?php
                            if ($_SESSION[$guid]['country'] == '') {
                                echo '<b>'.__($guid, 'Visa Expiry Date').'</b><br/>';
                            } else {
                                echo '<b>'.$_SESSION[$guid]['country'].' '.__($guid, 'Visa Expiry Date').'</b><br/>';
                            }
							echo "<span style='font-size: 90%'><i>Format ";
							if ($_SESSION[$guid]['i18n']['dateFormat'] == '') {
								echo 'dd/mm/yyyy';
							} else {
								echo $_SESSION[$guid]['i18n']['dateFormat'];
							}
							echo '. '.__($guid, 'If relevant.').'</span>'; ?>
										</td>
										<td class="right">
											<?php
											$value = '';
							if ($row['visaExpiryDate'] != null and $row['visaExpiryDate'] != '' and $row['visaExpiryDate'] != '0000-00-00') {
								$value = dateConvertBack($guid, $row['visaExpiryDate']);
							}
							?>
							<input name="visaExpiryDate" id="visaExpiryDate" maxlength=10 value="<?php echo $value ?>" type="text" class="standardWidth">
							<script type="text/javascript">
								var visaExpiryDate=new LiveValidation('visaExpiryDate');
								visaExpiryDate.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]['i18n']['dateFormatRegEx'] == '') {
								echo "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i";
								} else {
									echo $_SESSION[$guid]['i18n']['dateFormatRegEx'];
								}
											?>, failureMessage: "Use <?php if ($_SESSION[$guid]['i18n']['dateFormat'] == '') {
									echo 'dd/mm/yyyy';
								} else {
									echo $_SESSION[$guid]['i18n']['dateFormat'];
								}
								?>." } );
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
								<h3><?php echo __($guid, 'Employment') ?></h3>
							</td>
						</tr>
						<tr>
							<td>
								<b><?php echo __($guid, 'Profession') ?></b><br/>
							</td>
							<td class="right">
								<input name="profession" id="profession" maxlength=30 value="<?php echo htmlPrep($row['profession']) ?>" type="text" class="standardWidth">
							</td>
						</tr>
						<tr>
							<td>
								<b><?php echo __($guid, 'Employer') ?></b><br/>
							</td>
							<td class="right">
								<input name="employer" id="employer" maxlength=30 value="<?php echo htmlPrep($row['employer']) ?>" type="text" class="standardWidth">
							</td>
						</tr>
						<tr>
							<td>
								<b><?php echo __($guid, 'Job Title') ?></b><br/>
							</td>
							<td class="right">
								<input name="jobTitle" id="jobTitle" maxlength=30 value="<?php echo htmlPrep($row['jobTitle']) ?>" type="text" class="standardWidth">
							</td>
						</tr>
						<?php

                    }
            		?>


					<?php
                    if ($student or $staff) {
                        ?>
						<tr class='break'>
							<td colspan=2>
								<h3><?php echo __($guid, 'Emergency Contacts') ?></h3>
							</td>
						</tr>
						<tr>
							<td colspan=2>
								<?php echo __($guid, 'These details are used when immediate family members (e.g. parent, spouse) cannot be reached first. Please try to avoid listing immediate family members.') ?>
							</td>
						</tr>
						<tr>
							<td>
								<b><?php echo __($guid, 'Contact 1 Name') ?></b><br/>
							</td>
							<td class="right">
								<input name="emergency1Name" id="emergency1Name" maxlength=30 value="<?php echo htmlPrep($row['emergency1Name']) ?>" type="text" class="standardWidth">
							</td>
						</tr>
						<tr>
							<td>
								<b><?php echo __($guid, 'Contact 1 Relationship') ?></b><br/>
							</td>
							<td class="right">
								<select name="emergency1Relationship" id="emergency1Relationship" class="standardWidth">
									<option <?php if ($row['emergency1Relationship'] == '') { echo 'selected '; } ?>value=""></option>
									<option <?php if ($row['emergency1Relationship'] == 'Parent') { echo 'selected '; } ?>value="Parent"><?php echo __($guid, 'Parent') ?></option>
									<option <?php if ($row['emergency1Relationship'] == 'Spouse') { echo 'selected '; } ?>value="Spouse"><?php echo __($guid, 'Spouse') ?></option>
									<option <?php if ($row['emergency1Relationship'] == 'Offspring') { echo 'selected '; } ?>value="Offspring"><?php echo __($guid, 'Offspring') ?></option>
									<option <?php if ($row['emergency1Relationship'] == 'Friend') { echo 'selected '; } ?>value="Friend"><?php echo __($guid, 'Friend') ?></option>
									<option <?php if ($row['emergency1Relationship'] == 'Other Relation') { echo 'selected '; } ?>value="Other Relation"><?php echo __($guid, 'Other Relation') ?></option>
									<option <?php if ($row['emergency1Relationship'] == 'Doctor') { echo 'selected '; } ?>value="Doctor"><?php echo __($guid, 'Doctor') ?></option>
									<option <?php if ($row['emergency1Relationship'] == 'Other') { echo 'selected '; } ?>value="Other"><?php echo __($guid, 'Other') ?></option>
								</select>
							</td>
						</tr>
						<tr>
							<td>
								<b><?php echo __($guid, 'Contact 1 Number 1') ?></b><br/>
							</td>
							<td class="right">
								<input name="emergency1Number1" id="emergency1Number1" maxlength=30 value="<?php echo htmlPrep($row['emergency1Number1']) ?>" type="text" class="standardWidth">
							</td>
						</tr>
						<tr>
							<td>
								<b><?php echo __($guid, 'Contact 1 Number 2') ?></b><br/>
							</td>
							<td class="right">
								<input name="emergency1Number2" id="emergency1Number2" maxlength=30 value="<?php echo htmlPrep($row['emergency1Number2']) ?>" type="text" class="standardWidth">
							</td>
						</tr>
						<tr>
							<td>
								<b><?php echo __($guid, 'Contact 2 Name') ?></b><br/>
							</td>
							<td class="right">
								<input name="emergency2Name" id="emergency2Name" maxlength=30 value="<?php echo htmlPrep($row['emergency2Name']) ?>" type="text" class="standardWidth">
							</td>
						</tr>
						<tr>
							<td>
								<b><?php echo __($guid, 'Contact 2 Relationship') ?></b><br/>
							</td>
							<td class="right">
								<select name="emergency2Relationship" id="emergency2Relationship" class="standardWidth">
									<option <?php if ($row['emergency2Relationship'] == '') { echo 'selected '; } ?>value=""></option>
									<option <?php if ($row['emergency2Relationship'] == 'Parent') { echo 'selected '; } ?>value="Parent"><?php echo __($guid, 'Parent') ?></option>
									<option <?php if ($row['emergency2Relationship'] == 'Spouse') { echo 'selected '; } ?>value="Spouse"><?php echo __($guid, 'Spouse') ?></option>
									<option <?php if ($row['emergency2Relationship'] == 'Offspring') { echo 'selected '; } ?>value="Offspring"><?php echo __($guid, 'Offspring') ?></option>
									<option <?php if ($row['emergency2Relationship'] == 'Friend') { echo 'selected '; } ?>value="Friend"><?php echo __($guid, 'Friend') ?></option>
									<option <?php if ($row['emergency2Relationship'] == 'Other Relation') { echo 'selected '; } ?>value="Other Relation"><?php echo __($guid, 'Other Relation') ?></option>
									<option <?php if ($row['emergency2Relationship'] == 'Doctor') { echo 'selected '; } ?>value="Doctor"><?php echo __($guid, 'Doctor') ?></option>
									<option <?php if ($row['emergency2Relationship'] == 'Other') { echo 'selected '; } ?>value="Other"><?php echo __($guid, 'Other') ?></option>
								</select>
							</td>
						</tr>
						<tr>
							<td>
								<b><?php echo __($guid, 'Contact 2 Number 1') ?></b><br/>
							</td>
							<td class="right">
								<input name="emergency2Number1" id="emergency2Number1" maxlength=30 value="<?php echo htmlPrep($row['emergency2Number1']) ?>" type="text" class="standardWidth">
							</td>
						</tr>
						<tr>
							<td>
								<b><?php echo __($guid, 'Contact 2 Number 2') ?></b><br/>
							</td>
							<td class="right">
								<input name="emergency2Number2" id="emergency2Number2" maxlength=30 value="<?php echo htmlPrep($row['emergency2Number2']) ?>" type="text" class="standardWidth">
							</td>
						</tr>
						<?php

                    }
            		?>

					<tr class='break'>
						<td colspan=2>
							<h3><?php echo __($guid, 'Miscellaneous') ?></h3>
						</td>
					</tr>
					<tr>
						<td>
							<b><?php echo __($guid, 'House') ?></b><br/>
						</td>
						<td class="right">
							<select name="gibbonHouseID" id="gibbonHouseID" class="standardWidth">
								<?php
                                echo "<option value=''></option>";
								try {
									$dataSelect = array();
									$sqlSelect = 'SELECT gibbonHouseID, name FROM gibbonHouse ORDER BY name';
									$resultSelect = $connection2->prepare($sqlSelect);
									$resultSelect->execute($dataSelect);
								} catch (PDOException $e) {
									echo "<div class='error'>".$e->getMessage().'</div>';
								}
								while ($rowSelect = $resultSelect->fetch()) {
									$selected = '';
									if ($row['gibbonHouseID'] == $rowSelect['gibbonHouseID']) {
										$selected = 'selected';
									}
									echo "<option $selected value='".$rowSelect['gibbonHouseID']."'>".htmlPrep($rowSelect['name']).'</option>';
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
								<b><?php echo __($guid, 'Student ID') ?></b><br/>
								<span class="emphasis small"><?php echo __($guid, 'Must be unique if set.') ?></span>
							</td>
							<td class="right">
								<input name="studentID" id="studentID" maxlength=10 value="<?php echo htmlPrep($row['studentID']) ?>" type="text" class="standardWidth">
							</td>
						</tr>
						<?php

                    }
					if ($student or $staff) {
						?>
						<tr>
							<td>
								<b><?php echo __($guid, 'Transport') ?></b><br/>
							</td>
							<td class="right">
								<input name="transport" id="transport" maxlength=255 value="<?php echo htmlPrep($row['transport']) ?>" type="text" class="standardWidth">
							</td>
						</tr>
						<script type="text/javascript">
							$(function() {
								var availableTags=[
									<?php
                                    try {
                                        $dataAuto = array();
                                        $sqlAuto = 'SELECT DISTINCT transport FROM gibbonPerson ORDER BY lastSchool';
                                        $resultAuto = $connection2->prepare($sqlAuto);
                                        $resultAuto->execute($dataAuto);
                                    } catch (PDOException $e) {
                                    }
									while ($rowAuto = $resultAuto->fetch()) {
										echo '"'.$rowAuto['transport'].'", ';
									}
									?>
								];
								$( "#transport" ).autocomplete({source: availableTags});
							});
						</script>
						<tr>
							<td>
								<b><?php echo __($guid, 'Transport Notes') ?></b><br/>
								<span class="emphasis small"></span>
							</td>
							<td class="right">
								<textarea name="transportNotes" id="transportNotes" rows=4 class="standardWidth"><?php echo htmlPrep($row['transportNotes']) ?></textarea>
							</td>
						</tr>
					<?php

            }
            if ($student or $staff) {
                ?>
				<tr>
					<td>
						<b><?php echo __($guid, 'Locker Number') ?></b><br/>
						<span style="font-size: 90%"></span>
					</td>
					<td class="right">
						<input name="lockerNumber" id="lockerNumber" maxlength=20 value="<?php echo $row['lockerNumber'] ?>" type="text" class="standardWidth">
					</td>
				</tr>
				<?php

            }
            ?>
				<tr>
					<td>
						<b><?php echo __($guid, 'Vehicle Registration') ?></b><br/>
						<span style="font-size: 90%"></span>
					</td>
					<td class="right">
						<input name="vehicleRegistration" id="vehicleRegistration" maxlength=20 value="<?php echo $row['vehicleRegistration'] ?>" type="text" class="standardWidth">
					</td>
				</tr>

				<?php
				//Check if any roles are "Student"
				$imagePrivacySet = false;
				if ($student) {
					$privacySetting = getSettingByScope($connection2, 'User Admin', 'privacy');
					$privacyBlurb = getSettingByScope($connection2, 'User Admin', 'privacyBlurb');
					$privacyOptions = getSettingByScope($connection2, 'User Admin', 'privacyOptions');
					if ($privacySetting == 'Y' and $privacyBlurb != '' and $privacyOptions != '') {
						?>
						<tr>
							<td>
								<b><?php echo __($guid, 'Privacy') ?> *</b><br/>
								<span class="emphasis small"><?php echo htmlPrep($privacyBlurb) ?><br/>
								</span>
							</td>
							<td class="right">
								<?php
								$options = explode(',', $privacyOptions);
								$privacyChecks = explode(',', $row['privacy']);
								foreach ($options as $option) {
									$checked = '';
									foreach ($privacyChecks as $privacyCheck) {
										if (trim($option) == trim($privacyCheck)) {
											$checked = 'checked';
										}
									}
									echo $option." <input $checked type='checkbox' name='privacyOptions[]' value='".htmlPrep(trim($option))."'/><br/>";
								}
								?>

							</td>
						</tr>
					<?php
                } else {
                    echo '<input type="hidden" name="privacy" value="">';
                }
            }
            if ($imagePrivacySet == false) {
                echo '<input type="hidden" name="imagePrivacy" value="">';
            }
                    //Student options for agreements
                    if ($student) {
                        $studentAgreementOptions = getSettingByScope($connection2, 'School Admin', 'studentAgreementOptions');
                        if ($studentAgreementOptions != '') {
                            ?>
							<tr>
								<td>
									<b><?php echo __($guid, 'Student Agreements') ?></b><br/>
									<span class="emphasis small"><?php echo __($guid, 'Check to indicate that student has signed the relevant agreement.') ?><br/>
									</span>
								</td>
								<td class="right">
									<?php
                                    $agreements = explode(',', $studentAgreementOptions);
                            $agreementChecks = explode(',', $row['studentAgreements']);
                            foreach ($agreements as $agreement) {
                                $checked = '';
                                foreach ($agreementChecks as $agreementCheck) {
                                    if (trim($agreement) == trim($agreementCheck)) {
                                        $checked = 'checked';
                                    }
                                }
                                echo $agreement." <input $checked type='checkbox' name='studentAgreements[]' value='".htmlPrep(trim($agreement))."'/><br/>";
                            }
                            ?>

								</td>
							</tr>
							<?php

                        }
                    }

                    //CUSTOM FIELDS
                    $fields = unserialize($row['fields']);
					$resultFields = getCustomFields($connection2, $guid, $student, $staff, $parent, $other);
					if ($resultFields->rowCount() > 0) {
						?>
						<tr class='break'>
							<td colspan=2>
								<h3><?php echo __($guid, 'Custom Fields') ?></h3>
							</td>
						</tr>
						<?php
                        while ($rowFields = $resultFields->fetch()) {
                            echo renderCustomFieldRow($connection2, $guid, $rowFields, @$fields[$rowFields['gibbonPersonFieldID']]);
                        }
					}
					?>

					<tr>
						<td>
							<span class="emphasis small">* <?php echo __($guid, 'denotes a required field'); ?></i><br/>
							<?php
                            echo getMaxUpload($guid, true); ?>
							</span>
						</td>
						<td class="right">
							<input type="hidden" name="address" value="<?php echo $_SESSION[$guid]['address'] ?>">
							<input type="submit" value="<?php echo __($guid, 'Submit'); ?>">
						</td>
					</tr>
				</table>
			</form>
			<?php

        }
    }
}
?>
