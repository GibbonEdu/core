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
include './modules/User Admin/moduleFunctions.php'; //for User Admin (for custom fields)

if (isActionAccessible($guid, $connection2, '/modules/Data Updater/data_personal.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        echo "<div class='error'>";
        echo __($guid, 'The highest grouped action cannot be determined.');
        echo '</div>';
    } else {
        //Proceed!
        echo "<div class='trail'>";
        echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Update Personal Data').'</div>';
        echo '</div>';

        if ($highestAction == 'Update Personal Data_any') {
            echo '<p>';
            echo __($guid, 'This page allows a user to request selected personal data updates for any user.');
            echo '</p>';
        } else {
            echo '<p>';
            echo __($guid, 'This page allows any adult with data access permission to request selected personal data updates for any member of their family.');
            echo '</p>';
        }

        $customResponces = array();
        $error3 = __($guid, 'Your request was successful, but some data was not properly saved. An administrator will process your request as soon as possible. <u>You will not see the updated data in the system until it has been processed and approved.</u>');
        if ($_SESSION[$guid]['organisationDBAEmail'] != '' and $_SESSION[$guid]['organisationDBAName'] != '') {
            $error3 .= ' '.sprintf(__($guid, 'Please contact %1$s if you have any questions.'), "<a href='mailto:".$_SESSION[$guid]['organisationDBAEmail']."'>".$_SESSION[$guid]['organisationDBAName'].'</a>');
        }
        $customResponces['error3'] = $error3;

        $success0 = __($guid, 'Your request was completed successfully. An administrator will process your request as soon as possible. You will not see the updated data in the system until it has been processed and approved.');
        if ($_SESSION[$guid]['organisationDBAEmail'] != '' and $_SESSION[$guid]['organisationDBAName'] != '') {
            $success0 .= ' '.sprintf(__($guid, 'Please contact %1$s if you have any questions.'), "<a href='mailto:".$_SESSION[$guid]['organisationDBAEmail']."'>".$_SESSION[$guid]['organisationDBAName'].'</a>');
        }
        $customResponces['success0'] = $success0;

        if (isset($_GET['return'])) {
            returnProcess($guid, $_GET['return'], null, $customResponces);
        }

        echo '<h2>';
        echo __($guid, 'Choose User');
        echo '</h2>';

        $gibbonPersonID = null;
        if (isset($_GET['gibbonPersonID'])) {
            $gibbonPersonID = $_GET['gibbonPersonID'];
        }
        ?>

		<form method="get" action="<?php echo $_SESSION[$guid]['absoluteURL']?>/index.php">
			<table class='smallIntBorder fullWidth' cellspacing='0'>
				<tr>
					<td style='width: 275px'>
						<b><?php echo __($guid, 'Person') ?> *</b><br/>
					</td>
					<td class="right">
						<select class="standardWidth" name="gibbonPersonID">
							<?php
                            $self = false;
							if ($highestAction == 'Update Personal Data_any') {
								try {
									$dataSelect = array();
									$sqlSelect = "SELECT username, surname, preferredName, gibbonPerson.gibbonPersonID FROM gibbonPerson WHERE status='Full' ORDER BY surname, preferredName";
									$resultSelect = $connection2->prepare($sqlSelect);
									$resultSelect->execute($dataSelect);
								} catch (PDOException $e) {
								}
								echo "<option value=''></option>";
								while ($rowSelect = $resultSelect->fetch()) {
									if ($gibbonPersonID == $rowSelect['gibbonPersonID']) {
										echo "<option selected value='".$rowSelect['gibbonPersonID']."'>".formatName('', htmlPrep($rowSelect['preferredName']), htmlPrep($rowSelect['surname']), 'Student', true).' ('.$rowSelect['username'].')</option>';
									} else {
										echo "<option value='".$rowSelect['gibbonPersonID']."'>".formatName('', htmlPrep($rowSelect['preferredName']), htmlPrep($rowSelect['surname']), 'Student', true).' ('.$rowSelect['username'].')</option>';
									}
									$self = true;
								}
							} else {
								try {
									$dataSelect = array('gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
									$sqlSelect = "SELECT gibbonFamilyAdult.gibbonFamilyID, name FROM gibbonFamilyAdult JOIN gibbonFamily ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID) WHERE gibbonPersonID=:gibbonPersonID AND childDataAccess='Y' ORDER BY name";
									$resultSelect = $connection2->prepare($sqlSelect);
									$resultSelect->execute($dataSelect);
								} catch (PDOException $e) {
								}
								echo "<option value=''></option>";
								while ($rowSelect = $resultSelect->fetch()) {
									try {
										$dataSelect2 = array('gibbonFamilyID1' => $rowSelect['gibbonFamilyID'], 'gibbonFamilyID2' => $rowSelect['gibbonFamilyID']);
										$sqlSelect2 = "(SELECT surname, preferredName, gibbonPerson.gibbonPersonID, gibbonFamilyID FROM gibbonFamilyChild JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPerson.status='Full' AND gibbonFamilyID=:gibbonFamilyID1) UNION (SELECT surname, preferredName, gibbonPerson.gibbonPersonID, gibbonFamilyID FROM gibbonFamilyAdult JOIN gibbonPerson ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonFamilyID=:gibbonFamilyID2)";
										$resultSelect2 = $connection2->prepare($sqlSelect2);
										$resultSelect2->execute($dataSelect2);
									} catch (PDOException $e) {
									}
									while ($rowSelect2 = $resultSelect2->fetch()) {
										if ($gibbonPersonID == $rowSelect2['gibbonPersonID']) {
											echo "<option selected value='".$rowSelect2['gibbonPersonID']."'>".formatName('', htmlPrep($rowSelect2['preferredName']), htmlPrep($rowSelect2['surname']), 'Student', true).'</option>';
										} else {
											echo "<option value='".$rowSelect2['gibbonPersonID']."'>".formatName('', htmlPrep($rowSelect2['preferredName']), htmlPrep($rowSelect2['surname']), 'Student', true).'</option>';
										}
                                        //Check for self
                                        if ($rowSelect2['gibbonPersonID'] == $_SESSION[$guid]['gibbonPersonID']) {
                                            $self = true;
                                        }
									}
								}
							}

							if ($self == false) {
								if ($gibbonPersonID == $_SESSION[$guid]['gibbonPersonID']) {
									echo "<option selected value='".$_SESSION[$guid]['gibbonPersonID']."'>".formatName('', htmlPrep($_SESSION[$guid]['preferredName']), htmlPrep($_SESSION[$guid]['surname']), 'Student', true).'</option>';
								} else {
									echo "<option value='".$_SESSION[$guid]['gibbonPersonID']."'>".formatName('', htmlPrep($_SESSION[$guid]['preferredName']), htmlPrep($_SESSION[$guid]['surname']), 'Student', true).'</option>';
								}
							}
							?>
						</select>
					</td>
				</tr>
				<tr>
					<td colspan=2 class="right">
						<input type="hidden" name="q" value="/modules/<?php echo $_SESSION[$guid]['module'] ?>/data_personal.php">
						<input type="submit" value="<?php echo __($guid, 'Submit'); ?>">
					</td>
				</tr>
			</table>
		</form>
		<?php

        if ($gibbonPersonID != '') {
            echo '<h2>';
            echo __($guid, 'Update Data');
            echo '</h2>';

            //Check access to person
            $checkCount = 0;
            $self = false;
            if ($highestAction == 'Update Personal Data_any') {
                try {
                    $dataSelect = array();
                    $sqlSelect = "SELECT surname, preferredName, gibbonPerson.gibbonPersonID FROM gibbonPerson WHERE status='Full' ORDER BY surname, preferredName";
                    $resultSelect = $connection2->prepare($sqlSelect);
                    $resultSelect->execute($dataSelect);
                } catch (PDOException $e) {
                }
                $checkCount = $resultSelect->rowCount();
                $self = true;
            } else {
                try {
                    $dataCheck = array('gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                    $sqlCheck = "SELECT gibbonFamilyAdult.gibbonFamilyID, name FROM gibbonFamilyAdult JOIN gibbonFamily ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID) WHERE gibbonPersonID=:gibbonPersonID AND childDataAccess='Y' ORDER BY name";
                    $resultCheck = $connection2->prepare($sqlCheck);
                    $resultCheck->execute($dataCheck);
                } catch (PDOException $e) {
                }
                while ($rowCheck = $resultCheck->fetch()) {
                    try {
                        $dataCheck2 = array('gibbonFamilyID1' => $rowCheck['gibbonFamilyID'], 'gibbonFamilyID2' => $rowCheck['gibbonFamilyID']);
                        $sqlCheck2 = "(SELECT surname, preferredName, gibbonPerson.gibbonPersonID, gibbonFamilyID FROM gibbonFamilyChild JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPerson.status='Full' AND gibbonFamilyID=:gibbonFamilyID1) UNION (SELECT surname, preferredName, gibbonPerson.gibbonPersonID, gibbonFamilyID FROM gibbonFamilyAdult JOIN gibbonPerson ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPerson.status='Full' AND gibbonFamilyID=:gibbonFamilyID2)";
                        $resultCheck2 = $connection2->prepare($sqlCheck2);
                        $resultCheck2->execute($dataCheck2);
                    } catch (PDOException $e) {
                    }
                    while ($rowCheck2 = $resultCheck2->fetch()) {
                        if ($gibbonPersonID == $rowCheck2['gibbonPersonID']) {
                            ++$checkCount;
                        }
                        //Check for self
                        if ($rowSelect2['gibbonPersonID'] == $_SESSION[$guid]['gibbonPersonID']) {
                            $self = true;
                        }
                    }
                }
            }

            if ($self == false and $gibbonPersonID == $_SESSION[$guid]['gibbonPersonID']) {
                ++$checkCount;
            }

            if ($checkCount < 1) {
                echo "<div class='error'>";
                echo __($guid, 'The selected record does not exist, or you do not have access to it.');
                echo '</div>';
            } else {
                //Get categories
                try {
                    $dataSelect = array('gibbonPersonID' => $gibbonPersonID);
                    $sqlSelect = 'SELECT gibbonRoleIDAll FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonID ORDER BY surname, preferredName';
                    $resultSelect = $connection2->prepare($sqlSelect);
                    $resultSelect->execute($dataSelect);
                } catch (PDOException $e) {
                }
                if ($resultSelect->rowCount() == 1) {
                    $rowSelect = $resultSelect->fetch();
                    $staff = false;
                    $student = false;
                    $parent = false;
                    $other = false;
                    $roles = explode(',', $rowSelect['gibbonRoleIDAll']);
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
                }

                //Check if there is already a pending form for this user
                $existing = false;
                $proceed = false;
                try {
                    $data = array('gibbonPersonID' => $gibbonPersonID, 'gibbonPersonIDUpdater' => $_SESSION[$guid]['gibbonPersonID']);
                    $sql = "SELECT * FROM gibbonPersonUpdate WHERE gibbonPersonID=:gibbonPersonID AND gibbonPersonIDUpdater=:gibbonPersonIDUpdater AND status='Pending'";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }

                if ($result->rowCount() > 1) {
                    echo "<div class='error'>";
                    echo __($guid, 'Your request failed due to a database error.');
                    echo '</div>';
                } elseif ($result->rowCount() == 1) {
                    $existing = true;
                    echo "<div class='warning'>";
                    echo __($guid, 'You have already submitted a form, which is pending approval by an administrator. If you wish to make changes, please edited the data below, but remember your data will not appear in the system until it has been approved.');
                    echo '</div>';
                    $proceed = true;
                    if ($highestAction != 'Update Personal Data_any') {
                        $required = unserialize(getSettingByScope($connection2, 'User Admin', 'personalDataUpdaterRequiredFields'));
                        if (is_array($required)) {
                            $proceed = true;
                        }
                    } else {
                        $proceed = true;
                    }
                } else {
                    //Get user's data
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
                        if ($highestAction != 'Update Personal Data_any') {
                            $required = unserialize(getSettingByScope($connection2, 'User Admin', 'personalDataUpdaterRequiredFields'));
                            if (is_array($required)) {
                                $proceed = true;
                            }
                        } else {
                            $proceed = true;
                        }
                    }
                }

                if ($proceed == true) {
                    //Let's go!
                    $row = $result->fetch(); ?>
					<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/data_personalProcess.php?gibbonPersonID='.$gibbonPersonID ?>">
						<table class='smallIntBorder fullWidth' cellspacing='0'>
							<tr class='break'>
								<td colspan=2>
									<h3><?php echo __($guid, 'Basic Information') ?></h3>
								</td>
							</tr>
							<tr>
								<td style='width: 275px'>
									<b><?php echo __($guid, 'Title') ?><?php if (isset($required['title'])) {
									if ($required['title'] == 'Y') {
										echo ' *';
									}
								}
                    			?></b><br/>
								</td>
								<td class="right">
									<select class="standardWidth" name="title" id="title">
										<?php if ($required['title'] == 'Y') {
											echo "<option value='Please select...'>".__($guid, 'Please select...').'</option>';
										} else {
											echo "<option value=''></option>";
										}
                    					?>
										<option <?php if ($row['title'] == 'Ms.') { echo 'selected '; }
										?>value="Ms."><?php echo __($guid, 'Ms.') ?></option>
															<option <?php if ($row['title'] == 'Miss') { echo 'selected '; }
										?>value="Miss"><?php echo __($guid, 'Miss') ?></option>
															<option <?php if ($row['title'] == 'Mr.') { echo 'selected '; }
										?>value="Mr."><?php echo __($guid, 'Mr.') ?></option>
															<option <?php if ($row['title'] == 'Mrs.') { echo 'selected '; }
										?>value="Mrs."><?php echo __($guid, 'Mrs.') ?></option>
															<option <?php if ($row['title'] == 'Dr.') { echo 'selected '; }
										?>value="Dr."><?php echo __($guid, 'Dr.') ?></option>
									</select>
									<?php
                                    $fieldName = 'title';
									if (isset($required[$fieldName])) {
										if ($required[$fieldName] == 'Y') {
											echo '<script type="text/javascript">';
											echo 'var '.$fieldName."=new LiveValidation('".$fieldName."');";
											echo $fieldName.".add(Validate.Exclusion, { within: ['Please select...'], failureMessage: \"Select something!\"});";
											echo '</script>';
										}
									}
									?>
								</td>
							</tr>
							<tr>
								<td>
									<b><?php echo __($guid, 'Surname') ?><?php if (isset($required['surname'])) {
										if ($required['surname'] == 'Y') {
											echo ' *';
										}
									}
                    				?></b><br/>
									<span class="emphasis small"><?php echo __($guid, 'Family name as shown in ID documents.') ?></span>
								</td>
								<td class="right">
									<input name="surname" id="surname" maxlength=30 value="<?php echo htmlPrep($row['surname']) ?>" type="text" class="standardWidth">
									<?php
                                    $fieldName = 'surname';
									if (isset($required[$fieldName])) {
										if ($required[$fieldName] == 'Y') {
											echo '<script type="text/javascript">';
											echo 'var '.$fieldName."=new LiveValidation('".$fieldName."');";
											echo $fieldName.'.add(Validate.Presence);';
											echo '</script>';
										}
									}
									?>
								</td>
							</tr>
							<tr>
								<td>
									<b><?php echo __($guid, 'First Name') ?><?php if (isset($required['firstName'])) {
									if ($required['firstName'] == 'Y') {
										echo ' *';
									}
								}
                    			?></b><br/>
									<span class="emphasis small"><?php echo __($guid, 'First name as shown in ID documents.') ?></span>
								</td>
								<td class="right">
									<input name="firstName" id="firstName" maxlength=30 value="<?php echo htmlPrep($row['firstName']) ?>" type="text" class="standardWidth">
									<?php
                                    $fieldName = 'firstName';
									if (isset($required[$fieldName])) {
										if ($required[$fieldName] == 'Y') {
											echo '<script type="text/javascript">';
											echo 'var '.$fieldName."=new LiveValidation('".$fieldName."');";
											echo $fieldName.'.add(Validate.Presence);';
											echo '</script>';
										}
									}
									?>
								</td>
							</tr>
							<tr>
								<td>
									<b><?php echo __($guid, 'Preferred Name') ?><?php if (isset($required['preferredName'])) {
									if ($required['preferredName'] == 'Y') {
										echo ' *';
									}
								}
                    			?></b><br/>
									<span class="emphasis small"><?php echo __($guid, 'Most common name, alias, nickname, etc.') ?></span>
								</td>
								<td class="right">
									<input name="preferredName" id="preferredName" maxlength=30 value="<?php echo htmlPrep($row['preferredName']) ?>" type="text" class="standardWidth">
									<?php
                                    $fieldName = 'preferredName';
									if (isset($required[$fieldName])) {
										if ($required[$fieldName] == 'Y') {
											echo '<script type="text/javascript">';
											echo 'var '.$fieldName."=new LiveValidation('".$fieldName."');";
											echo $fieldName.'.add(Validate.Presence);';
											echo '</script>';
										}
									}
									?>
								</td>
							</tr>
							<tr>
								<td>
									<b><?php echo __($guid, 'Official Name') ?><?php if (isset($required['officialName'])) {
										if ($required['officialName'] == 'Y') {
											echo ' *';
										}
									}
                    				?></b><br/>
									<span class="emphasis small"><?php echo __($guid, 'Full name as shown in ID documents.') ?></span>
								</td>
								<td class="right">
									<input name="officialName" id="officialName" maxlength=150 value="<?php echo htmlPrep($row['officialName']) ?>" type="text" class="standardWidth">
									<?php
                                    $fieldName = 'officialName';
									if (isset($required[$fieldName])) {
										if ($required[$fieldName] == 'Y') {
											echo '<script type="text/javascript">';
											echo 'var '.$fieldName."=new LiveValidation('".$fieldName."');";
											echo $fieldName.'.add(Validate.Presence);';
											echo '</script>';
										}
									}
									?>
								</td>
							</tr>
							<tr>
								<td>
									<b><?php echo __($guid, 'Name In Characters') ?><?php if (isset($required['nameInCharacters'])) {
									if ($required['nameInCharacters'] == 'Y') {
										echo ' *';
									}
								}
                    			?></b><br/>
									<span class="emphasis small"><?php echo __($guid, 'Chinese or other character-based name.') ?></span>
								</td>
								<td class="right">
									<input name="nameInCharacters" id="nameInCharacters" maxlength=20 value="<?php echo htmlPrep($row['nameInCharacters']) ?>" type="text" class="standardWidth">
									<?php
                                    $fieldName = 'nameInCharacters';
									if (isset($required[$fieldName])) {
										if ($required[$fieldName] == 'Y') {
											echo '<script type="text/javascript">';
											echo 'var '.$fieldName."=new LiveValidation('".$fieldName."');";
											echo $fieldName.'.add(Validate.Presence);';
											echo '</script>';
										}
									}
									?>
								</td>
							</tr>
							<tr>
								<td>
									<b><?php echo __($guid, 'Date of Birth') ?><?php if (isset($required['dob'])) {
    								if ($required['dob'] == 'Y') {
											echo ' *';
										}
									}
									?></b><br/>
									<span class="emphasis small"><?php echo $_SESSION[$guid]['i18n']['dateFormat']  ?></span>
								</td>
								<td class="right">
									<input name="dob" id="dob" maxlength=10 value="<?php echo dateConvertBack($guid, $row['dob']) ?>" type="text" class="standardWidth">
									<?php
                                    $fieldName = 'dob';
									if (isset($required[$fieldName])) {
										if ($required[$fieldName] == 'Y') {
											echo '<script type="text/javascript">';
											echo 'var '.$fieldName."=new LiveValidation('".$fieldName."');";
											echo $fieldName.'.add(Validate.Presence);';
											echo $fieldName.'add( Validate.Format, {pattern:';
											if ($_SESSION[$guid]['i18n']['dateFormatRegEx'] == '') {
												echo "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i";
											} else {
												echo $_SESSION[$guid]['i18n']['dateFormatRegEx'];
											}
											echo ', failureMessage: "Use dd/mm/yyyy." } );';
											echo '</script>';
										}
									} else {
										echo '<script type="text/javascript">';
										echo 'var '.$fieldName."=new LiveValidation('".$fieldName."');";
										echo $fieldName.'add( Validate.Format, {pattern:';
										if ($_SESSION[$guid]['i18n']['dateFormatRegEx'] == '') {
											echo "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i";
										} else {
											echo $_SESSION[$guid]['i18n']['dateFormatRegEx'];
										}
										echo ', failureMessage: "Use dd/mm/yyyy." } );';
										echo '</script>';
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
										<b><?php echo __($guid, 'Contact 1 Name') ?><?php if (isset($required['emergency1Name'])) {
											if ($required['emergency1Name'] == 'Y') {
												echo ' *';
											}
										}
                                		?></b><br/>
									</td>
									<td class="right">
										<input name="emergency1Name" id="emergency1Name" maxlength=30 value="<?php echo htmlPrep($row['emergency1Name']) ?>" type="text" class="standardWidth">
										<?php
                                        $fieldName = 'emergency1Name';
										if (isset($required[$fieldName])) {
											if ($required[$fieldName] == 'Y') {
												echo '<script type="text/javascript">';
												echo 'var '.$fieldName."=new LiveValidation('".$fieldName."');";
												echo $fieldName.'.add(Validate.Presence);';
												echo '</script>';
											}
										}
										?>
									</td>
								</tr>
								<tr>
									<td>
										<b><?php echo __($guid, 'Contact 1 Relationship') ?><?php if (isset($required['emergency1Relationship'])) {
											if ($required['emergency1Relationship'] == 'Y') {
												echo ' *';
											}
										}
                               			?></b><br/>
									</td>
									<td class="right">
										<select name="emergency1Relationship" id="emergency1Relationship" class="standardWidth">
											<?php if ($required['emergency1Relationship'] == 'Y') {
												echo "<option value='Please select...'>".__($guid, 'Please select...').'</option>';
											} else {
												echo "<option value=''></option>";
											}
                                			?>
											<option <?php if ($row['emergency1Relationship'] == 'Parent') { echo 'selected '; } ?>value="Parent"><?php echo __($guid, 'Parent') ?></option>
											<option <?php if ($row['emergency1Relationship'] == 'Spouse') { echo 'selected '; } ?>value="Spouse"><?php echo __($guid, 'Spouse') ?></option>
											<option <?php if ($row['emergency1Relationship'] == 'Offspring') { echo 'selected '; } ?>value="Offspring"><?php echo __($guid, 'Offspring') ?></option>
											<option <?php if ($row['emergency1Relationship'] == 'Friend') { echo 'selected '; } ?>value="Friend"><?php echo __($guid, 'Friend') ?></option>
											<option <?php if ($row['emergency1Relationship'] == 'Other Relation') { echo 'selected '; } ?>value="Other Relation"><?php echo __($guid, 'Other Relation') ?></option>
											<option <?php if ($row['emergency1Relationship'] == 'Doctor') { echo 'selected '; } ?>value="Doctor"><?php echo __($guid, 'Doctor') ?></option>
											<option <?php if ($row['emergency1Relationship'] == 'Other') { echo 'selected '; } ?>value="Other"><?php echo __($guid, 'Other') ?></option>
										</select>
										<?php
                                        $fieldName = 'emergency1Relationship';
										if (isset($required[$fieldName])) {
											if ($required[$fieldName] == 'Y') {
												echo '<script type="text/javascript">';
												echo 'var '.$fieldName."=new LiveValidation('".$fieldName."');";
												echo $fieldName.".add(Validate.Exclusion, { within: ['Please select...'], failureMessage: \"Select something!\"});";
												echo '</script>';
											}
										}
										?>
									</td>
								</tr>
								<tr>
									<td>
										<b><?php echo __($guid, 'Contact 1 Number 1') ?><?php if (isset($required['emergency1Number1'])) {
											if ($required['emergency1Number1'] == 'Y') {
												echo ' *';
											}
										}
                                		?></b><br/>
									</td>
									<td class="right">
										<input name="emergency1Number1" id="emergency1Number1" maxlength=30 value="<?php echo htmlPrep($row['emergency1Number1']) ?>" type="text" class="standardWidth">
										<?php
                                        $fieldName = 'emergency1Number1';
										if (isset($required[$fieldName])) {
											if ($required[$fieldName] == 'Y') {
												echo '<script type="text/javascript">';
												echo 'var '.$fieldName."=new LiveValidation('".$fieldName."');";
												echo $fieldName.'.add(Validate.Presence);';
												echo '</script>';
											}
										}
										?>
									</td>
								</tr>
								<tr>
									<td>
										<b><?php echo __($guid, 'Contact 1 Number 2') ?><?php if (isset($required['emergency1Number2'])) {
										if ($required['emergency1Number2'] == 'Y') {
											echo ' *';
										}
									}
                                	?></b><br/>
									</td>
									<td class="right">
										<input name="emergency1Number2" id="emergency1Number2" maxlength=30 value="<?php echo htmlPrep($row['emergency1Number2']) ?>" type="text" class="standardWidth">
										<?php
                                        $fieldName = 'emergency1Number2';
										if (isset($required[$fieldName])) {
											if ($required[$fieldName] == 'Y') {
												echo '<script type="text/javascript">';
												echo 'var '.$fieldName."=new LiveValidation('".$fieldName."');";
												echo $fieldName.'.add(Validate.Presence);';
												echo '</script>';
											}
										}
										?>
									</td>
								</tr>
								<tr>
									<td>
										<b><?php echo __($guid, 'Contact 2 Name') ?><?php if (isset($required['emergency2Name'])) {
											if ($required['emergency2Name'] == 'Y') {
												echo ' *';
											}
										}
                                		?></b><br/>
									</td>
									<td class="right">
										<input name="emergency2Name" id="emergency2Name" maxlength=30 value="<?php echo htmlPrep($row['emergency2Name']) ?>" type="text" class="standardWidth">
										<?php
                                        $fieldName = 'emergency2Name';
										if (isset($required[$fieldName])) {
											if ($required[$fieldName] == 'Y') {
												echo '<script type="text/javascript">';
												echo 'var '.$fieldName."=new LiveValidation('".$fieldName."');";
												echo $fieldName.'.add(Validate.Presence);';
												echo '</script>';
											}
										}
										?>
									</td>
								</tr>
								<tr>
									<td>
										<b><?php echo __($guid, 'Contact 2 Relationship') ?><?php if (isset($required['emergency2Relationship'])) {
											if ($required['emergency2Relationship'] == 'Y') {
												echo ' *';
											}
										}
                                		?></b><br/>
									</td>
									<td class="right">
										<select name="emergency2Relationship" id="emergency2Relationship" class="standardWidth">
											<?php if ($required['emergency2Relationship'] == 'Y') {
												echo "<option value='Please select...'>".__($guid, 'Please select...').'</option>';
											} else {
												echo "<option value=''></option>";
											}
                               				?>
											<option <?php if ($row['emergency2Relationship'] == 'Parent') { echo 'selected '; } ?>value="Parent"><?php echo __($guid, 'Parent') ?></option>
											<option <?php if ($row['emergency2Relationship'] == 'Spouse') { echo 'selected '; } ?>value="Spouse"><?php echo __($guid, 'Spouse') ?></option>
											<option <?php if ($row['emergency2Relationship'] == 'Offspring') { echo 'selected '; } ?>value="Offspring"><?php echo __($guid, 'Offspring') ?></option>
											<option <?php if ($row['emergency2Relationship'] == 'Friend') { echo 'selected '; } ?>value="Friend"><?php echo __($guid, 'Friend') ?></option>
											<option <?php if ($row['emergency2Relationship'] == 'Other Relation') { echo 'selected '; } ?>value="Other Relation"><?php echo __($guid, 'Other Relation') ?></option>
											<option <?php if ($row['emergency2Relationship'] == 'Doctor') { echo 'selected '; } ?>value="Doctor"><?php echo __($guid, 'Doctor') ?></option>
											<option <?php if ($row['emergency2Relationship'] == 'Other') { echo 'selected '; } ?>value="Other"><?php echo __($guid, 'Other') ?></option>
										</select>
										<?php
                                        $fieldName = 'emergency2Relationship';
                                if (isset($required[$fieldName])) {
                                    if ($required[$fieldName] == 'Y') {
                                        echo '<script type="text/javascript">';
                                        echo 'var '.$fieldName."=new LiveValidation('".$fieldName."');";
                                        echo $fieldName.".add(Validate.Exclusion, { within: ['Please select...'], failureMessage: \"Select something!\"});";
                                        echo '</script>';
                                    }
                                }
                                ?>
									</td>
								</tr>
								<tr>
									<td>
										<b><?php echo __($guid, 'Contact 2 Number 1') ?><?php if (isset($required['emergency2Number1'])) {
											if ($required['emergency2Number1'] == 'Y') {
												echo ' *';
											}
										}
                                		?></b><br/>
									</td>
									<td class="right">
										<input name="emergency2Number1" id="emergency2Number1" maxlength=30 value="<?php echo htmlPrep($row['emergency2Number1']) ?>" type="text" class="standardWidth">
										<?php
                                        $fieldName = 'emergency2Number1';
										if (isset($required[$fieldName])) {
											if ($required[$fieldName] == 'Y') {
												echo '<script type="text/javascript">';
												echo 'var '.$fieldName."=new LiveValidation('".$fieldName."');";
												echo $fieldName.'.add(Validate.Presence);';
												echo '</script>';
											}
										}
										?>
									</td>
								</tr>
								<tr>
									<td>
										<b><?php echo __($guid, 'Contact 2 Number 2') ?><?php if (isset($required['emergency2Number2'])) {
											if ($required['emergency2Number2'] == 'Y') {
												echo ' *';
											}
										}
                               	 ?></b><br/>
									</td>
									<td class="right">
										<input name="emergency2Number2" id="emergency2Number2" maxlength=30 value="<?php echo htmlPrep($row['emergency2Number2']) ?>" type="text" class="standardWidth">
										<?php
                                        $fieldName = 'emergency2Number2';
										if (isset($required[$fieldName])) {
											if ($required[$fieldName] == 'Y') {
												echo '<script type="text/javascript">';
												echo 'var '.$fieldName."=new LiveValidation('".$fieldName."');";
												echo $fieldName.'.add(Validate.Presence);';
												echo '</script>';
											}
										}
										?>
									</td>
								</tr>
								<?php
                            }
                    		?>

							<tr class='break'>
								<td colspan=2>
									<h3><?php echo __($guid, 'Contact Information') ?></h3>
								</td>
							</tr>
							<tr>
								<td>
									<b><?php echo __($guid, 'Email') ?><?php if (isset($required['email'])) {
    								if ($required['email'] == 'Y') {
											echo ' *';
										}
									}
									?></b><br/>
								</td>
								<td class="right">
									<input name="email" id="email" maxlength=50 value="<?php echo htmlPrep($row['email']) ?>" type="text" class="standardWidth">
									<?php
                                    $fieldName = 'email';
									if (isset($required[$fieldName])) {
										if ($required[$fieldName] == 'Y') {
											echo '<script type="text/javascript">';
											echo 'var '.$fieldName."=new LiveValidation('".$fieldName."');";
											echo $fieldName.'.add(Validate.Presence);';
											echo $fieldName.'.add(Validate.Email);';
											echo '</script>';
										}
									} else {
										echo '<script type="text/javascript">';
										echo 'var '.$fieldName."=new LiveValidation('".$fieldName."');";
										echo $fieldName.'.add(Validate.Email);';
										echo '</script>';
									}
									?>
								</td>
							</tr>
							<tr>
								<td>
									<b><?php echo __($guid, 'Alternate Email') ?><?php if (isset($required['emailAlternate'])) {
   	 								if ($required['emailAlternate'] == 'Y') {
											echo ' *';
										}
									}
									?></b><br/>
									<span class="emphasis small"></span>
								</td>
								<td class="right">
									<input name="emailAlternate" id="emailAlternate" maxlength=50 value="<?php echo htmlPrep($row['emailAlternate']) ?>" type="text" class="standardWidth">
									<?php
                                    $fieldName = 'email';
									if (isset($required[$fieldName])) {
										if ($required[$fieldName] == 'Y') {
											echo '<script type="text/javascript">';
											echo 'var '.$fieldName."=new LiveValidation('".$fieldName."');";
											echo $fieldName.'.add(Validate.Presence);';
											echo $fieldName.'.add(Validate.Email);';
											echo '</script>';
										}
									} else {
										echo '<script type="text/javascript">';
										echo 'var '.$fieldName."=new LiveValidation('".$fieldName."');";
										echo $fieldName.'.add(Validate.Email);';
										echo '</script>';
									}
									?>
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
									<input <?php if ($addressSet) { echo 'checked' ; } ?> id='showAddresses' name='showAddresses' type='checkbox' value='Yes'/>
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
                                        try {
                                            $dataSelect = array();
                                            $sqlSelect = 'SELECT printable_name FROM gibbonCountry ORDER BY printable_name';
                                            $resultSelect = $connection2->prepare($sqlSelect);
                                            $resultSelect->execute($dataSelect);
                                        } catch (PDOException $e) {
                                        }
										if ($required['address1Country'] == 'Y') {
											echo "<option value='Please select...'>".__($guid, 'Please select...').'</option>';
										} else {
											echo "<option value=''></option>";
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
                                $addressMatch = '%'.strtolower(preg_replace('/ /', '%', preg_replace('/,/', '%', $row['address1']))).'%';

                                try {
                                    $dataAddress = array('addressMatch' => $addressMatch, 'gibbonPersonID' => $row['gibbonPersonID']);
                                    $sqlAddress = "SELECT gibbonPersonID, title, preferredName, surname, category FROM gibbonPerson JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) WHERE status='Full' AND address1 LIKE :addressMatch AND NOT gibbonPersonID=:gibbonPersonID ORDER BY surname, preferredName";
                                    $resultAddress = $connection2->prepare($sqlAddress);
                                    $resultAddress->execute($dataAddress);
                                } catch (PDOException $e) {
                                    echo "<div class='error'>".$e->getMessage().'</div>';
                                }

                                if ($resultAddress->fetch() > 0) {
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
                                        try {
                                            $dataSelect = array();
                                            $sqlSelect = 'SELECT printable_name FROM gibbonCountry ORDER BY printable_name';
                                            $resultSelect = $connection2->prepare($sqlSelect);
                                            $resultSelect->execute($dataSelect);
                                        } catch (PDOException $e) {
                                        }
										if ($required['address2Country'] == 'Y') {
											echo "<option value='Please select...'>".__($guid, 'Please select...').'</option>';
										} else {
											echo "<option value=''></option>";
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
											<b><?php echo __($guid, 'Phone') ?> <?php echo $i ?><?php if (isset($required['phone'.$i])) {
												if ($required['phone'.$i] == 'Y') {
													echo ' *';
												}
											}
                                    		?></b><br/>
											<span class="emphasis small"><?php echo __($guid, 'Type, country code, number.') ?></span>
										</td>
										<td class="right">
											<input name="phone<?php echo $i ?>" id="phone<?php echo $i ?>" maxlength=20 value="<?php echo $row['phone'.$i] ?>" type="text" style="width: 160px">
											<?php
                                            $fieldName = 'phone'.$i;
											if (isset($required[$fieldName])) {
												if ($required[$fieldName] == 'Y') {
													echo '<script type="text/javascript">';
													echo 'var '.$fieldName."=new LiveValidation('".$fieldName."');";
													echo $fieldName.'.add(Validate.Presence);';
													echo '</script>';
												}
											}
											?>
											<select name="phone<?php echo $i ?>CountryCode" id="phone<?php echo $i ?>CountryCode" style="width: 60px">
												<?php
                                                if ($required['phone'.$i] == 'Y') {
                                                    echo "<option value='Please select...'>".__($guid, 'Please select...').'</option>';
                                                } else {
                                                    echo "<option value=''></option>";
                                                }
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
											<?php
                                            $fieldName = 'phone'.$i.'CountryCode';

											if (isset($required['phone'.$i])) {
												if ($required['phone'.$i] == 'Y') {
													echo '<script type="text/javascript">';
													echo 'var '.$fieldName."=new LiveValidation('".$fieldName."');";
													echo $fieldName.".add(Validate.Exclusion, { within: ['Please select...'], failureMessage: \"Select something!\"});";
													echo '</script>';
												}
											}
											?>
											<select style="width: 70px" name="phone<?php echo $i ?>Type" id="phone<?php echo $i ?>Type">
												<?php if ($required['phone'.$i] == 'Y') {
													echo "<option value='Please select...'>".__($guid, 'Please select...').'</option>';
												} else {
													echo "<option value=''></option>";
												}
                                    			?>
												<option <?php if ($row['phone'.$i.'Type'] == 'Mobile') { echo 'selected'; } ?> value="Mobile"><?php echo __($guid, 'Mobile') ?></option>
												<option <?php if ($row['phone'.$i.'Type'] == 'Home') { echo 'selected'; } ?> value="Home"><?php echo __($guid, 'Home') ?></option>
												<option <?php if ($row['phone'.$i.'Type'] == 'Work') { echo 'selected'; } ?> value="Work"><?php echo __($guid, 'Work') ?></option>
												<option <?php if ($row['phone'.$i.'Type'] == 'Fax') { echo 'selected'; } ?> value="Fax"><?php echo __($guid, 'Fax') ?></option>
												<option <?php if ($row['phone'.$i.'Type'] == 'Pager') { echo 'selected'; } ?> value="Pager"><?php echo __($guid, 'Pager') ?></option>
												<option <?php if ($row['phone'.$i.'Type'] == 'Other') { echo 'selected'; } ?> value="Other"><?php echo __($guid, 'Other') ?></option>
											</select>
											<?php
                                            $fieldName = 'phone'.$i.'Type';
											if (isset($required['phone'.$i])) {
												if ($required['phone'.$i] == 'Y') {
													echo '<script type="text/javascript">';
													echo 'var '.$fieldName."=new LiveValidation('".$fieldName."');";
													echo $fieldName.".add(Validate.Exclusion, { within: ['Please select...'], failureMessage: \"Select something!\"});";
													echo '</script>';
												}
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
									<b><?php echo __($guid, 'First Language') ?><?php if (isset($required['languageFirst'])) {
    								if ($required['languageFirst'] == 'Y') {
											echo ' *';
										}
									}
									?></b><br/>
									<span class="emphasis small"><?php echo __($guid, 'Student\'s native/first/mother language.') ?></span>
								</td>
								<td class="right">
									<select name="languageFirst" id="languageFirst" class="standardWidth">
										<?php
                                        if ($required['languageFirst'] == 'Y') {
                                            echo "<option value='Please select...'>".__($guid, 'Please select...').'</option>';
                                        } else {
                                            echo "<option value=''></option>";
                                        }
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
									<?php
                                    $fieldName = 'languageFirst';
									if (isset($required[$fieldName])) {
										if ($required[$fieldName] == 'Y') {
											echo '<script type="text/javascript">';
											echo 'var '.$fieldName."=new LiveValidation('".$fieldName."');";
											echo $fieldName.".add(Validate.Exclusion, { within: ['Please select...'], failureMessage: \"Select something!\"});";
											echo '</script>';
										}
									}
									?>
								</td>
							</tr>
							<tr>
								<td>
									<b><?php echo __($guid, 'Second Language') ?><?php if (isset($required['languageSecond'])) {
    								if ($required['languageSecond'] == 'Y') {
											echo ' *';
										}
									}
									?></b><br/>
								</td>
								<td class="right">
									<select name="languageSecond" id="languageSecond" class="standardWidth">
										<?php
                                        if ($required['languageSecond'] == 'Y') {
                                            echo "<option value='Please select...'>".__($guid, 'Please select...').'</option>';
                                        } else {
                                            echo "<option value=''></option>";
                                        }
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
									<?php
                                    $fieldName = 'languageSecond';
									if (isset($required[$fieldName])) {
										if ($required[$fieldName] == 'Y') {
											echo '<script type="text/javascript">';
											echo 'var '.$fieldName."=new LiveValidation('".$fieldName."');";
											echo $fieldName.".add(Validate.Exclusion, { within: ['Please select...'], failureMessage: \"Select something!\"});";
											echo '</script>';
										}
									}
									?>
								</td>
							</tr>
							<tr>
								<td>
									<b><?php echo __($guid, 'Third Language') ?><?php if (isset($required['languageThird'])) {
    								if ($required['languageThird'] == 'Y') {
											echo ' *';
										}
									}
									?></b><br/>
								</td>
								<td class="right">
									<select name="languageThird" id="languageThird" class="standardWidth">
										<?php
                                        if ($required['languageThird'] == 'Y') {
                                            echo "<option value='Please select...'>".__($guid, 'Please select...').'</option>';
                                        } else {
                                            echo "<option value=''></option>";
                                        }
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
									<?php
                                    $fieldName = 'languageThird';
									if (isset($required[$fieldName])) {
										if ($required[$fieldName] == 'Y') {
											echo '<script type="text/javascript">';
											echo 'var '.$fieldName."=new LiveValidation('".$fieldName."');";
											echo $fieldName.".add(Validate.Exclusion, { within: ['Please select...'], failureMessage: \"Select something!\"});";
											echo '</script>';
										}
									}
									?>
								</td>
							</tr>
							<tr>
								<td>
									<b><?php echo __($guid, 'Country of Birth') ?><?php if (isset($required['countryOfBirth'])) {
    								if ($required['countryOfBirth'] == 'Y') {
											echo ' *';
										}
									}
									?></b><br/>
								</td>
								<td class="right">
									<select name="countryOfBirth" id="countryOfBirth" class="standardWidth">
										<?php
                                        try {
                                            $dataSelect = array();
                                            $sqlSelect = 'SELECT printable_name FROM gibbonCountry ORDER BY printable_name';
                                            $resultSelect = $connection2->prepare($sqlSelect);
                                            $resultSelect->execute($dataSelect);
                                        } catch (PDOException $e) {
                                        }
										if ($required['countryOfBirth'] == 'Y') {
											echo "<option value='Please select...'>".__($guid, 'Please select...').'</option>';
										} else {
											echo "<option value=''></option>";
										}
										while ($rowSelect = $resultSelect->fetch()) {
											$selected = '';
											if ($row['countryOfBirth'] == $rowSelect['printable_name']) {
												$selected = 'selected';
											}
											echo "<option $selected value='".$rowSelect['printable_name']."'>".htmlPrep(__($guid, $rowSelect['printable_name'])).'</option>';
										}
										?>
									</select>
									<?php
                                    $fieldName = 'countryOfBirth';
									if (isset($required[$fieldName])) {
										if ($required[$fieldName] == 'Y') {
											echo '<script type="text/javascript">';
											echo 'var '.$fieldName."=new LiveValidation('".$fieldName."');";
											echo $fieldName.".add(Validate.Exclusion, { within: ['Please select...'], failureMessage: \"Select something!\"});";
											echo '</script>';
										}
									}
									?>
								</td>
							</tr>
							<tr>
								<td>
									<b><?php echo __($guid, 'Ethnicity') ?><?php if (isset($required['ethnicity'])) {
    								if ($required['ethnicity'] == 'Y') {
											echo ' *';
										}
									}
									?></b><br/>
								</td>
								<td class="right">
									<select name="ethnicity" id="ethnicity" class="standardWidth">
										<?php if ($required['ethnicity'] == 'Y') {
											echo "<option value='Please select...'>".__($guid, 'Please select...').'</option>';
										} else {
											echo "<option value=''></option>";
										}
                    					?>
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
									<?php
                                    $fieldName = 'ethnicity';
									if (isset($required[$fieldName])) {
										if ($required[$fieldName] == 'Y') {
											echo '<script type="text/javascript">';
											echo 'var '.$fieldName."=new LiveValidation('".$fieldName."');";
											echo $fieldName.".add(Validate.Exclusion, { within: ['Please select...'], failureMessage: \"Select something!\"});";
											echo '</script>';
										}
									}
									?>
								</td>
							</tr>
							<tr>
								<td>
									<b><?php echo __($guid, 'Religion') ?><?php if (isset($required['religion'])) {
    								if ($required['religion'] == 'Y') {
											echo ' *';
										}
									}
									?></b><br/>
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
									<?php
                                    $fieldName = 'religion';
									if (isset($required[$fieldName])) {
										if ($required[$fieldName] == 'Y') {
											echo '<script type="text/javascript">';
											echo 'var '.$fieldName."=new LiveValidation('".$fieldName."');";
											echo $fieldName.".add(Validate.Exclusion, { within: ['Please select...'], failureMessage: \"Select something!\"});";
											echo '</script>';
										}
									}
									?>
								</td>
							</tr>
							<tr>
								<td>
									<b><?php echo __($guid, 'Citizenship 1') ?><?php if (isset($required['citizenship1'])) {
    								if ($required['citizenship1'] == 'Y') {
											echo ' *';
										}
									}
									?></b><br/>
								</td>
								<td class="right">
									<select name="citizenship1" id="citizenship1" class="standardWidth">
										<?php
                                        if ($required['citizenship1'] == 'Y') {
                                            echo "<option value='Please select...'>".__($guid, 'Please select...').'</option>';
                                        } else {
                                            echo "<option value=''></option>";
                                        }
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
												echo "<option value='".$rowSelect['printable_name']."'>".htmlPrep(__($guid, $rowSelect['printable_name'])).'</option>';
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
									<?php
                                    $fieldName = 'citizenship1';
									if (isset($required[$fieldName])) {
										if ($required[$fieldName] == 'Y') {
											echo '<script type="text/javascript">';
											echo 'var '.$fieldName."=new LiveValidation('".$fieldName."');";
											echo $fieldName.".add(Validate.Exclusion, { within: ['Please select...'], failureMessage: \"Select something!\"});";
											echo '</script>';
										}
									}
									?>
								</td>
							</tr>
							<tr>
								<td>
									<b><?php echo __($guid, 'Citizenship 1 Passport Number') ?><?php if (isset($required['citizenship1Passport'])) {
    								if ($required['citizenship1Passport'] == 'Y') {
											echo ' *';
										}
									}
									?></b><br/>
								</td>
								<td class="right">
									<input name="citizenship1Passport" id="citizenship1Passport" maxlength=30 value="<?php echo htmlPrep($row['citizenship1Passport']) ?>" type="text" class="standardWidth">
									<?php
                                    $fieldName = 'citizenship1Passport';
									if (isset($required[$fieldName])) {
										if ($required[$fieldName] == 'Y') {
											echo '<script type="text/javascript">';
											echo 'var '.$fieldName."=new LiveValidation('".$fieldName."');";
											echo $fieldName.'.add(Validate.Presence);';
											echo '</script>';
										}
									}
									?>
								</td>
							</tr>
							<tr>
								<td>
									<b><?php echo __($guid, 'Citizenship 2') ?><?php if (isset($required['citizenshipr'])) {
    								if ($required['citizenship2'] == 'Y') {
											echo ' *';
										}
									}
									?></b><br/>
								</td>
								<td class="right">
									<select name="citizenship2" id="citizenship2" class="standardWidth">
										<?php
                                        if ($required['citizenship2'] == 'Y') {
                                            echo "<option value='Please select...'>".__($guid, 'Please select...').'</option>';
                                        } else {
                                            echo "<option value=''></option>";
                                        }
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
												echo "<option value='".$rowSelect['printable_name']."'>".htmlPrep(__($guid, $rowSelect['printable_name'])).'</option>';
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
									<?php
                                    $fieldName = 'citizenship2';
									if (isset($required[$fieldName])) {
										if ($required[$fieldName] == 'Y') {
											echo '<script type="text/javascript">';
											echo 'var '.$fieldName."=new LiveValidation('".$fieldName."');";
											echo $fieldName.".add(Validate.Exclusion, { within: ['Please select...'], failureMessage: \"Select something!\"});";
											echo '</script>';
										}
									}
									?>
								</td>
							</tr>
							<tr>
								<td>
									<b><?php echo __($guid, 'Citizenship 2 Passport Number') ?><?php if (isset($required['citizenship2Passport'])) {
    								if ($required['citizenship2Passport'] == 'Y') {
											echo ' *';
										}
									}
									?></b><br/>
								</td>
								<td class="right">
									<input name="citizenship2Passport" id="citizenship2Passport" maxlength=30 value="<?php echo htmlPrep($row['citizenship2Passport']) ?>" type="text" class="standardWidth">
									<?php
                                    $fieldName = 'citizenship2Passport';
									if (isset($required[$fieldName])) {
										if ($required[$fieldName] == 'Y') {
											echo '<script type="text/javascript">';
											echo 'var '.$fieldName."=new LiveValidation('".$fieldName."');";
											echo $fieldName.'.add(Validate.Presence);';
											echo '</script>';
										}
									}
									?>
								</td>
							</tr>
							<tr>
								<td>
									<?php
                                    $star = '';
									if (isset($required['nationalIDCardNumber'])) {
										if ($required['nationalIDCardNumber'] == 'Y') {
											$star = ' *';
										}
									}
									if ($_SESSION[$guid]['country'] == '') {
										echo '<b>'.__($guid, 'National ID Card Number').$star.'</b><br/>';
									} else {
										echo '<b>'.$_SESSION[$guid]['country'].' '.__($guid, 'ID Card Number').$star.'</b><br/>';
									}
									?>
								</td>
								<td class="right">
									<input name="nationalIDCardNumber" id="nationalIDCardNumber" maxlength=30 value="<?php echo htmlPrep($row['nationalIDCardNumber']) ?>" type="text" class="standardWidth">
									<?php
                                    $fieldName = 'nationalIDCardNumber';
									if (isset($required[$fieldName])) {
										if ($required[$fieldName] == 'Y') {
											echo '<script type="text/javascript">';
											echo 'var '.$fieldName."=new LiveValidation('".$fieldName."');";
											echo $fieldName.'.add(Validate.Presence);';
											echo '</script>';
										}
									}
									?>
								</td>
							</tr>
							<tr>
								<td>
									<?php
                                    $star = '';
									if (isset($required['residencyStatus'])) {
										if ($required['residencyStatus'] == 'Y') {
											$star = ' *';
										}
									}
									if ($_SESSION[$guid]['country'] == '') {
										echo '<b>'.__($guid, 'Residency/Visa Type').$star.'</b><br/>';
									} else {
										echo '<b>'.$_SESSION[$guid]['country'].' '.__($guid, 'Residency/Visa Type').$star.'</b><br/>';
									}
									?>
								</td>
								<td class="right">
									<?php
                                    $residencyStatusList = getSettingByScope($connection2, 'User Admin', 'residencyStatus');
									if ($residencyStatusList == '') {
										echo "<input name='residencyStatus' id='residencyStatus' maxlength=30 value='".$row['residencyStatus']."' type='text' style='width: 300px'>";
										$fieldName = 'residencyStatus';
										if (isset($required[$fieldName])) {
											if ($required[$fieldName] == 'Y') {
												echo '<script type="text/javascript">';
												echo 'var '.$fieldName."=new LiveValidation('".$fieldName."');";
												echo $fieldName.'.add(Validate.Presence);';
												echo '</script>';
											}
										}
									} else {
										echo "<select name='residencyStatus' id='residencyStatus' style='width: 302px'>";
										if ($required['residencyStatus'] == 'Y') {
											echo "<option value='Please select...'>".__($guid, 'Please select...').'</option>';
										} else {
											echo "<option value=''></option>";
										}
										$residencyStatuses = explode(',', $residencyStatusList);
										foreach ($residencyStatuses as $residencyStatus) {
											$selected = '';
											if (trim($residencyStatus) == $row['residencyStatus']) {
												$selected = 'selected';
											}
											echo "<option $selected value='".trim($residencyStatus)."'>".trim($residencyStatus).'</option>';
										}
										echo '</select>';
										$fieldName = 'residencyStatus';
										if (isset($required[$fieldName])) {
											if ($required[$fieldName] == 'Y') {
												echo '<script type="text/javascript">';
												echo 'var '.$fieldName."=new LiveValidation('".$fieldName."');";
												echo $fieldName.".add(Validate.Exclusion, { within: ['Please select...'], failureMessage: \"Select something!\"});";
												echo '</script>';
											}
										}
									}
									?>
								</td>
							</tr>
							<tr>
								<td>
									<?php
                                    $star = '';
									if (isset($required['visaExpiryDate'])) {
										if ($required['visaExpiryDate'] == 'Y') {
											$star = ' *';
										}
									}
									if ($_SESSION[$guid]['country'] == '') {
										echo '<b>'.__($guid, 'Visa Expiry Date').$star.'</b><br/>';
									} else {
										echo '<b>'.$_SESSION[$guid]['country'].' '.__($guid, 'Visa Expiry Date').$star.'</b><br/>';
									}
									echo "<span style='font-size: 90%'><i>Format: ";
									if ($_SESSION[$guid]['i18n']['dateFormat'] == '') {
										echo 'dd/mm/yyyy';
									} else {
										echo $_SESSION[$guid]['i18n']['dateFormat'];
									}
									echo '. '.__($guid, 'If relevant.').'</span>';
									?>
								</td>
								<td class="right">
									<input name="visaExpiryDate" id="visaExpiryDate" maxlength=10 value="<?php echo dateConvertBack($guid, $row['visaExpiryDate']) ?>" type="text" class="standardWidth">
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
									 	<?php
                                        if ($required['visaExpiryDate'] == 'Y') {
                                            echo 'visaExpiryDate.add(Validate.Presence);';
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
										<h3><?php echo __($guid, 'Employment') ?></h3>
									</td>
								</tr>
								<tr>
									<td>
										<b><?php echo __($guid, 'Profession') ?><?php if (isset($required['profession'])) {
										if ($required['profession'] == 'Y') {
											echo ' *';
										}
									}
                                ?></b><br/>
									</td>
									<td class="right">
										<input name="profession" id="profession" maxlength=30 value="<?php echo htmlPrep($row['profession']) ?>" type="text" class="standardWidth">
										<?php
                                        $fieldName = 'profession';
                                if (isset($required[$fieldName])) {
                                    if ($required[$fieldName] == 'Y') {
                                        echo '<script type="text/javascript">';
                                        echo 'var '.$fieldName."=new LiveValidation('".$fieldName."');";
                                        echo $fieldName.'.add(Validate.Presence);';
                                        echo '</script>';
                                    }
                                }
                                ?>
									</td>
								</tr>
								<tr>
									<td>
										<b><?php echo __($guid, 'Employer') ?><?php if (isset($required['employer'])) {
											if ($required['employer'] == 'Y') {
												echo ' *';
											}
										}
                                		?></b><br/>
									</td>
									<td class="right">
										<input name="employer" id="employer" maxlength=30 value="<?php echo htmlPrep($row['employer']) ?>" type="text" class="standardWidth">
										<?php
                                        $fieldName = 'employer';
                                if (isset($required[$fieldName])) {
                                    if ($required[$fieldName] == 'Y') {
                                        echo '<script type="text/javascript">';
                                        echo 'var '.$fieldName."=new LiveValidation('".$fieldName."');";
                                        echo $fieldName.'.add(Validate.Presence);';
                                        echo '</script>';
                                    }
                                }
                                ?>
									</td>
								</tr>
								<tr>
									<td>
										<b><?php echo __($guid, 'Job Title') ?><?php if (isset($required['jobTitle'])) {
											if ($required['jobTitle'] == 'Y') {
												echo ' *';
											}
										}
                                		?></b><br/>
									</td>
									<td class="right">
										<input name="jobTitle" id="jobTitle" maxlength=30 value="<?php echo htmlPrep($row['jobTitle']) ?>" type="text" class="standardWidth">
										<?php
                                        $fieldName = 'jobTitle';
                                if (isset($required[$fieldName])) {
                                    if ($required[$fieldName] == 'Y') {
                                        echo '<script type="text/javascript">';
                                        echo 'var '.$fieldName."=new LiveValidation('".$fieldName."');";
                                        echo $fieldName.'.add(Validate.Presence);';
                                        echo '</script>';
                                    }
                                }
                                ?>
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
									<b><?php echo __($guid, 'Vehicle Registration') ?><?php if (isset($required['vehicleRegistration'])) {
    								if ($required['vehicleRegistration'] == 'Y') {
											echo ' *';
										}
									}
									?></b><br/>
								</td>
								<td class="right">
									<input name="vehicleRegistration" id="vehicleRegistration" maxlength=30 value="<?php echo htmlPrep($row['vehicleRegistration']) ?>" type="text" class="standardWidth">
									<?php
                                    $fieldName = 'vehicleRegistration';
									if (isset($required[$fieldName])) {
										if ($required[$fieldName] == 'Y') {
											echo '<script type="text/javascript">';
											echo 'var '.$fieldName."=new LiveValidation('".$fieldName."');";
											echo $fieldName.'.add(Validate.Presence);';
											echo '</script>';
										}
									}
									?>
								</td>
							</tr>
							<?php
                            //Check if any roles are "Student"
                            $privacySet = false;
							if ($student) {
								$privacySetting = getSettingByScope($connection2, 'User Admin', 'privacy');
								$privacyBlurb = getSettingByScope($connection2, 'User Admin', 'privacyBlurb');
								$privacyOptions = getSettingByScope($connection2, 'User Admin', 'privacyOptions');
								if ($privacySetting == 'Y' and $privacyBlurb != '' and $privacyOptions != '') {
									?>
									<tr>
										<td>
											<b><?php echo __($guid, 'Privacy') ?></b><br/>
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
													if ($option == $privacyCheck) {
														$checked = 'checked';
													}
												}
												echo $option." <input $checked type='checkbox' name='privacyOptions[]' value='".htmlPrep($option)."'/><br/>";
											}
											?>

										</td>
									</tr>
									<?php

									}
								}

                            //CUSTOM FIELDS
                            $fields = unserialize($row['fields']);
							$resultFields = getCustomFields($connection2, $guid, $student, $staff, $parent, $other, null, true);
							if ($resultFields->rowCount() > 0) {
								?>
								<tr class='break'>
									<td colspan=2>
										<h3><?php echo __($guid, 'Custom Fields') ?></h3>
									</td>
								</tr>
								<?php
                                while ($rowFields = $resultFields->fetch()) {
                                    $value = '';
                                    if (isset($fields[$rowFields['gibbonPersonFieldID']])) {
                                        $value = $fields[$rowFields['gibbonPersonFieldID']];
                                    }
                                    if ($highestAction != 'Update Personal Data_any') {
                                        echo renderCustomFieldRow($connection2, $guid, $rowFields, $value);
                                    }
                                    else {
                                        echo renderCustomFieldRow($connection2, $guid, $rowFields, $value, '', '', true);
                                    }

                                }
							}
							?>

							<tr>
								<td>
									<span class="emphasis small">* <?php echo __($guid, 'denotes a required field'); ?></span>
								</td>
								<td class="right">
									<?php
                                    if ($existing) {
                                        echo "<input type='hidden' name='existing' value='".$row['gibbonPersonUpdateID']."'>";
                                    } else {
                                        echo "<input type='hidden' name='existing' value='N'>";
                                    }
                   		 			?>
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
    }
}
?>
