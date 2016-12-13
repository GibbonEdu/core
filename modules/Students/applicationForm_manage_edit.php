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

//Module includes from User Admin (for custom fields)
include './modules/User Admin/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Students/applicationForm_manage_edit.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Students/applicationForm_manage.php&gibbonSchoolYearID='.$_GET['gibbonSchoolYearID']."'>".__($guid, 'Manage Applications')."</a> > </div><div class='trailEnd'>".__($guid, 'Edit Form').'</div>';
    echo '</div>';

    //Check if school year specified
    $gibbonApplicationFormID = $_GET['gibbonApplicationFormID'];
    $gibbonSchoolYearID = $_GET['gibbonSchoolYearID'];
    $search = $_GET['search'];
    if ($gibbonApplicationFormID == '' or $gibbonSchoolYearID == '') {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        try {
            $data = array('gibbonApplicationFormID' => $gibbonApplicationFormID);
            $sql = "SELECT *, gibbonApplicationForm.status AS 'applicationStatus', gibbonPayment.status AS 'paymentStatus' FROM gibbonApplicationForm LEFT JOIN gibbonPayment ON (gibbonApplicationForm.gibbonPaymentID=gibbonPayment.gibbonPaymentID AND foreignTable='gibbonApplicationForm') WHERE gibbonApplicationFormID=:gibbonApplicationFormID";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        if ($result->rowCount() != 1) {
            echo "<div class='error'>";
            echo __($guid, 'The specified record does not exist.');
            echo '</div>';
        } else {
            if (isset($_GET['return'])) {
                returnProcess($guid, $_GET['return'], null, null);
            }

            //Let's go!
            $row = $result->fetch();
            $proceed = true;

            echo "<div class='linkTop'>";
            if ($search != '') {
                echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Students/applicationForm_manage.php&gibbonSchoolYearID=$gibbonSchoolYearID&search=$search'>".__($guid, 'Back to Search Results').'</a> | ';
            }
            echo "<a target='_blank' href='".$_SESSION[$guid]['absoluteURL'].'/report.php?q=/modules/'.$_SESSION[$guid]['module']."/applicationForm_manage_edit_print.php&gibbonApplicationFormID=$gibbonApplicationFormID'>".__($guid, 'Print')."<img style='margin-left: 5px' title='".__($guid, 'Print')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/print.png'/></a>";
            echo '</div>'; ?>
			<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/applicationForm_manage_editProcess.php?search=$search" ?>" enctype="multipart/form-data">
				<table class='smallIntBorder fullWidth' cellspacing='0'>
					<tr class='break'>
						<td colspan=2>
							<small class="emphasis small" style="float:right;margin-top:16px;"><a id="fixCaps">
								<?php echo __($guid, 'Fix Block Caps'); ?>
							</a></small>

							<script type="text/javascript">
							$(document).ready(function(){

								/* Replaces fields in all caps with title case */
								$('a#fixCaps').click(function(){
									$('input[type=text]').val (function () {
										if (this.value.toUpperCase() == this.value) {
									    	return this.value.replace(/\b\w+/g, function(txt){return txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase();});
										} else {
											return this.value;
										}
									});
									alert('<?php echo __($guid, 'Fields with all caps have been fixed. Please check the updated values and save the form to keep changes.'); ?>');
								});
							});
							</script>
							<h3><?php echo __($guid, 'For Office Use') ?></h3>
						</td>
					</tr>
					<tr>
						<td style='width: 275px'>
							<b><?php echo __($guid, 'Application ID') ?> *</b><br/>
							<span class="emphasis small"><?php echo __($guid, 'This value cannot be changed.') ?></span>
						</td>
						<td class="right">
							<input readonly name="gibbonApplicationFormID" id="gibbonApplicationFormID" value="<?php echo htmlPrep($row['gibbonApplicationFormID']) ?>" type="text" class="standardWidth">
						</td>
					</tr>
					<tr>
						<td>
							<b><?php echo __($guid, 'Priority') ?> *</b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Higher priority applicants appear first in list of applications.') ?></span>
						</td>
						<td class="right">
							<select name="priority" id="priority" class="standardWidth">
								<option <?php if ($row['priority'] == '9') { echo 'selected'; } ?> value="9">9</option>
								<option <?php if ($row['priority'] == '8') { echo 'selected'; } ?> value="8">8</option>
								<option <?php if ($row['priority'] == '7') { echo 'selected'; } ?> value="7">7</option>
								<option <?php if ($row['priority'] == '6') { echo 'selected'; } ?> value="6">6</option>
								<option <?php if ($row['priority'] == '5') { echo 'selected'; } ?> value="5">5</option>
								<option <?php if ($row['priority'] == '4') { echo 'selected'; } ?> value="4">4</option>
								<option <?php if ($row['priority'] == '3') { echo 'selected'; } ?> value="3">3</option>
								<option <?php if ($row['priority'] == '2') { echo 'selected'; } ?> value="2">2</option>
								<option <?php if ($row['priority'] == '1') { echo 'selected'; } ?> value="1">1</option>
								<option <?php if ($row['priority'] == '0') { echo 'selected'; } ?> value="0">0</option>
								<option <?php if ($row['priority'] == '-1') { echo 'selected'; } ?> value="-1">-1</option>
								<option <?php if ($row['priority'] == '-2') { echo 'selected'; } ?> value="-2">-2</option>
								<option <?php if ($row['priority'] == '-3') { echo 'selected'; } ?> value="-3">-3</option>
								<option <?php if ($row['priority'] == '-4') { echo 'selected'; } ?> value="-4">-4</option>
								<option <?php if ($row['priority'] == '-5') { echo 'selected'; } ?> value="-5">-5</option>
								<option <?php if ($row['priority'] == '-6') { echo 'selected'; } ?> value="-6">-6</option>
								<option <?php if ($row['priority'] == '-7') { echo 'selected'; } ?> value="-7">-7</option>
								<option <?php if ($row['priority'] == '-8') { echo 'selected'; } ?> value="-8">-8</option>
								<option <?php if ($row['priority'] == '-9') { echo 'selected'; } ?> value="-9">-9</option>
							</select>
						</td>
					</tr>
					<?php
                    if ($row['applicationStatus'] != 'Accepted') {
                        ?>
						<tr>
							<td>
								<b><?php echo __($guid, 'Status') ?> *</b><br/>
								<span class="emphasis small"><?php echo __($guid, 'Manually set status. "Approved" not permitted.') ?></span>
							</td>
							<td class="right">
								<select name="status" id="status" class="standardWidth">
									<option <?php if ($row['applicationStatus'] == 'Pending') { echo 'selected'; } ?> value="Pending"><?php echo __($guid, 'Pending') ?></option>
									<option <?php if ($row['applicationStatus'] == 'Waiting List') { echo 'selected'; } ?> value="Waiting List"><?php echo __($guid, 'Waiting List') ?></option>
									<option <?php if ($row['applicationStatus'] == 'Rejected') { echo 'selected'; } ?> value="Rejected"><?php echo __($guid, 'Rejected') ?></option>
									<option <?php if ($row['applicationStatus'] == 'Withdrawn') { echo 'selected'; } ?> value="Withdrawn"><?php echo __($guid, 'Withdrawn') ?></option>
								</select>
							</td>
						</tr>
						<?php

                    } else {
                        ?>
						<tr>
							<td>
								<b><?php echo __($guid, 'Status') ?> *</b><br/>
								<span class="emphasis small"><?php echo __($guid, 'This value cannot be changed.') ?></span>
							</td>
							<td class="right">
								<input readonly name="status" id="status" maxlength=20 value="<?php echo htmlPrep($row['applicationStatus']) ?>" type="text" class="standardWidth">
							</td>
						</tr>
						<?php

                    }
					$milestonesMasterRaw = getSettingByScope($connection2, 'Application Form', 'milestones');
					if ($milestonesMasterRaw != '') {
						?>
						<tr>
							<td>
								<b><?php echo __($guid, 'Milestones') ?></b><br/>
							</td>
							<td class="right">
								<?php
                                $milestones = explode(',', $row['milestones']);
								$milestonesMaster = explode(',', $milestonesMasterRaw);
								foreach ($milestonesMaster as $milestoneMaster) {
									$checked = '';
									foreach ($milestones as $milestone) {
										if (trim($milestoneMaster) == trim($milestone)) {
											$checked = 'checked';
										}
									}
									echo trim($milestoneMaster)." <input $checked type='checkbox' name='milestone_".preg_replace('/\s+/', '', $milestoneMaster)."'></br>";
								}
                			?>
							</td>
						</tr>
						<?php
					}
					?>
					<tr>
						<td>
							<b><?php echo __($guid, 'Start Date') ?></b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Student\'s intended first day at school.') ?><br/>Format <?php if ($_SESSION[$guid]['i18n']['dateFormat'] == '') { echo 'dd/mm/yyyy';
							} else {
								echo $_SESSION[$guid]['i18n']['dateFormat'];
							}
            			?></span>
						</td>
						<td class="right">
							<input name="dateStart" id="dateStart" maxlength=10 value="<?php echo dateConvertBack($guid, $row['dateStart']) ?>" type="text" class="standardWidth">
							<script type="text/javascript">
								var dateStart=new LiveValidation('dateStart');
								dateStart.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]['i18n']['dateFormatRegEx'] == '') { echo "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i";
								} else {
									echo $_SESSION[$guid]['i18n']['dateFormatRegEx'];
								}
								?>, failureMessage: "Use <?php if ($_SESSION[$guid]['i18n']['dateFormat'] == '') { echo 'dd/mm/yyyy';
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
							<b><?php echo __($guid, 'Year of Entry') ?> *</b><br/>
							<span class="emphasis small"><?php echo __($guid, 'When will the student join?') ?></span>
						</td>
						<td class="right">
							<select name="gibbonSchoolYearIDEntry" id="gibbonSchoolYearIDEntry" class="standardWidth">
								<?php
                                echo "<option value='Please select...'>".__($guid, 'Please select...').'</option>';
								try {
									$dataSelect = array();
									$sqlSelect = "SELECT * FROM gibbonSchoolYear WHERE (status='Current' OR status='Upcoming') ORDER BY sequenceNumber";
									$resultSelect = $connection2->prepare($sqlSelect);
									$resultSelect->execute($dataSelect);
								} catch (PDOException $e) {
									echo "<div class='error'>".$e->getMessage().'</div>';
								}
								while ($rowSelect = $resultSelect->fetch()) {
									$selected = '';
									if ($row['gibbonSchoolYearIDEntry'] == $rowSelect['gibbonSchoolYearID']) {
										$selected = 'selected';
									}
									echo "<option $selected value='".$rowSelect['gibbonSchoolYearID']."'>".htmlPrep($rowSelect['name']).'</option>';
								}
								?>
							</select>
							<script type="text/javascript">
								var gibbonSchoolYearIDEntry=new LiveValidation('gibbonSchoolYearIDEntry');
								gibbonSchoolYearIDEntry.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
							</script>
						</td>
					</tr>
					<tr>
						<td>
							<b><?php echo __($guid, 'Year Group at Entry') ?> *</b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Which year level will student enter.') ?></span>
						</td>
						<td class="right">
							<select name="gibbonYearGroupIDEntry" id="gibbonYearGroupIDEntry" class="standardWidth">
								<?php
                                echo "<option value='Please select...'>".__($guid, 'Please select...').'</option>';
								try {
									$dataSelect = array();
									$sqlSelect = 'SELECT * FROM gibbonYearGroup ORDER BY sequenceNumber';
									$resultSelect = $connection2->prepare($sqlSelect);
									$resultSelect->execute($dataSelect);
								} catch (PDOException $e) {
									echo "<div class='error'>".$e->getMessage().'</div>';
								}

								while ($rowSelect = $resultSelect->fetch()) {
									$selected = '';
									if ($row['gibbonYearGroupIDEntry'] == $rowSelect['gibbonYearGroupID']) {
										$selected = 'selected';
									}
									echo "<option $selected value='".$rowSelect['gibbonYearGroupID']."'>".htmlPrep(__($guid, $rowSelect['name'])).'</option>';
								}
								?>
							</select>
							<script type="text/javascript">
								var gibbonYearGroupIDEntry=new LiveValidation('gibbonYearGroupIDEntry');
								gibbonYearGroupIDEntry.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
							</script>
						</td>
					</tr>
					<?php
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
					?>
					<tr>
						<td>
							<b><?php echo __($guid, 'Roll Group at Entry') ?></b><br/>
							<span style="font-size: 90%"><?php echo __($guid, 'If set, the student will automatically be enroled on Accept.') ?></span>
						</td>
						<td class="right">
							<select name="gibbonRollGroupID" id="gibbonRollGroupID" class="standardWidth">
								<?php
                                echo "<option value=''></option>";
								try {
									$dataSelect = array();
									$sqlSelect = 'SELECT gibbonRollGroupID, name, gibbonSchoolYearID FROM gibbonRollGroup ORDER BY gibbonSchoolYearID, name';
									$resultSelect = $connection2->prepare($sqlSelect);
									$resultSelect->execute($dataSelect);
								} catch (PDOException $e) {
									echo "<div class='error'>".$e->getMessage().'</div>';
								}
								while ($rowSelect = $resultSelect->fetch()) {
									$selected = '';
									if ($row['gibbonRollGroupID'] == $rowSelect['gibbonRollGroupID']) {
										$selected = 'selected';
									}
									echo "<option $selected class='".$rowSelect['gibbonSchoolYearID']."' value='".$rowSelect['gibbonRollGroupID']."'>".htmlPrep($rowSelect['name']).'</option>';
								}
								?>
							</select>
							<script type="text/javascript">
								$("#gibbonRollGroupID").chainedTo("#gibbonSchoolYearIDEntry");
							</script>
						</td>
					</tr>

					<?php
                    $currency = getSettingByScope($connection2, 'System', 'currency');
					$applicationFee = getSettingByScope($connection2, 'Application Form', 'applicationFee');
					$enablePayments = getSettingByScope($connection2, 'System', 'enablePayments');
					$paypalAPIUsername = getSettingByScope($connection2, 'System', 'paypalAPIUsername');
					$paypalAPIPassword = getSettingByScope($connection2, 'System', 'paypalAPIPassword');
					$paypalAPISignature = getSettingByScope($connection2, 'System', 'paypalAPISignature');
					$ccPayment = false;

					if ($applicationFee > 0 and is_numeric($applicationFee)) {
						?>
						<tr>
							<td>
								<b><?php echo __($guid, 'Payment') ?> *</b><br/>
								<span class="emphasis small"><?php echo sprintf(__($guid, 'Has payment (%1$s %2$s) been made for this application.'), $currency, $applicationFee) ?></span>
							</td>
							<td class="right">
								<select name="paymentMade" id="paymentMade" class="standardWidth">
									<option <?php if ($row['paymentMade'] == 'N') { echo 'selected'; } ?> value='N'>N</option>
									<option <?php if ($row['paymentMade'] == 'Y') { echo 'selected'; } ?> value='Y'>Y</option>
									<option <?php if ($row['paymentMade'] == 'Exemption') { echo 'selected'; } ?> value='Exemption'>Exemption</option>
								</select>
							</td>
						</tr>
						<?php
                        if ($row['paymentToken'] != '' or $row['paymentPayerID'] != '' or $row['paymentTransactionID'] != '' or $row['paymentReceiptID'] != '') {
                            ?>
							<tr>
								<td style='text-align: right' colspan=2>
									<span class="emphasis small">
										<?php
                                            if ($row['paymentToken'] != '') {
                                                echo __($guid, 'Payment Token:').' '.$row['paymentToken'].'<br/>';
                                            }
											if ($row['paymentPayerID'] != '') {
												echo __($guid, 'Payment Payer ID:').' '.$row['paymentPayerID'].'<br/>';
											}
											if ($row['paymentTransactionID'] != '') {
												echo __($guid, 'Payment Transaction ID:').' '.$row['paymentTransactionID'].'<br/>';
											}
											if ($row['paymentReceiptID'] != '') {
												echo __($guid, 'Payment Receipt ID:').' '.$row['paymentReceiptID'].'<br/>';
											}
										?>
									</span>
								</td>
							</tr>
							<?php

                        }
					}

					?>
					<tr>
						<td colspan=2 style='padding-top: 15px'>
							<b><?php echo __($guid, 'Notes') ?></b><br/>
							<textarea name="notes" id="notes" rows=5 style="width:738px; margin: 5px 0px 0px 0px"><?php echo htmlPrep($row['notes']) ?></textarea>
						</td>
					</tr>

					<?php
						$data = array( 'gibbonApplicationFormID' => $row['gibbonApplicationFormID'] );
		                $sql = "SELECT DISTINCT gibbonApplicationFormID, preferredName, surname, status FROM gibbonApplicationForm 
                                JOIN gibbonApplicationFormLink ON (gibbonApplicationForm.gibbonApplicationFormID=gibbonApplicationFormLink.gibbonApplicationFormID1 OR gibbonApplicationForm.gibbonApplicationFormID=gibbonApplicationFormLink.gibbonApplicationFormID2) 
                                WHERE gibbonApplicationFormID1=:gibbonApplicationFormID 
                                OR gibbonApplicationFormID2=:gibbonApplicationFormID ORDER BY gibbonApplicationFormID";

		                $resultLinked = $pdo->executeQuery($data, $sql);

						if ($resultLinked && $resultLinked->rowCount() > 0) :
					?>
					<tr>
						<td>
							<b><?php echo __($guid, 'Linked Applications') ?></b><br/>
							<span class="emphasis small"><?php echo __($guid, 'If accepted, these students will be part of the same family. Accepting this application does NOT automatically accept other linked applications.') ?></span>
						</td>
						<td class="right">
							<ul style="width:302px;display:inline-block">
							<?php
							while ($rowLinked = $resultLinked->fetch()) {
								echo '<li>'. formatName('', $rowLinked['preferredName'], $rowLinked['surname'], 'Student', true);
								echo ' ('.str_pad( intval($rowLinked['gibbonApplicationFormID']), 7, '0', STR_PAD_LEFT).') - '.$rowLinked['status'].'</li>';
							}
							?>
							</ul>
						</td>
					</tr>
					<?php endif; ?>

					<tr class='break'>
						<td colspan=2>
							<h3><?php echo __($guid, 'Student') ?></h3>
						</td>
					</tr>

					<tr>
						<td colspan=2>
							<h4><?php echo __($guid, 'Student Personal Data') ?></h4>
						</td>
					</tr>
					<tr>
						<td>
							<b><?php echo __($guid, 'Surname') ?> *</b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Family name as shown in ID documents.') ?></span>
						</td>
						<td class="right">
							<input name="surname" id="surname" maxlength=30 value="<?php echo $row['surname'] ?>" type="text" class="standardWidth">
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
							<input name="firstName" id="firstName" maxlength=30 value="<?php echo $row['firstName'] ?>" type="text" class="standardWidth">
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
							<input name="preferredName" id="preferredName" maxlength=30 value="<?php echo $row['preferredName'] ?>" type="text" class="standardWidth">
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
							<input name="officialName" id="officialName" maxlength=150 value="<?php echo $row['officialName'] ?>" type="text" class="standardWidth">
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
							<input name="nameInCharacters" id="nameInCharacters" maxlength=20 value="<?php echo $row['nameInCharacters'] ?>" type="text" class="standardWidth">
						</td>
					</tr>
					<tr>
						<td>
							<b><?php echo __($guid, 'Gender') ?> *</b><br/>
						</td>
						<td class="right">
							<select name="gender" id="gender" class="standardWidth">
								<option value="Please select..."><?php echo __($guid, 'Please select...') ?></option>
								<option <?php if ($row['gender'] == 'F') { echo 'selected'; } ?> value="F"><?php echo __($guid, 'Female') ?></option>
								<option <?php if ($row['gender'] == 'M') { echo 'selected'; } ?> value="M"><?php echo __($guid, 'Male') ?></option>
							</select>
							<script type="text/javascript">
								var gender=new LiveValidation('gender');
								gender.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
							</script>
						</td>
					</tr>
					<tr>
						<td>
							<b><?php echo __($guid, 'Date of Birth') ?> *</b><br/>
							<span class="emphasis small"><?php echo $_SESSION[$guid]['i18n']['dateFormat']  ?></span>
						</td>
						<td class="right">
							<input name="dob" id="dob" maxlength=10 value="<?php echo dateConvertBack($guid, $row['dob']) ?>" type="text" class="standardWidth">
							<script type="text/javascript">
								var dob=new LiveValidation('dob');
								dob.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]['i18n']['dateFormatRegEx'] == '') { echo "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i";
								} else {
									echo $_SESSION[$guid]['i18n']['dateFormatRegEx'];
								}
								?>, failureMessage: "Use <?php if ($_SESSION[$guid]['i18n']['dateFormat'] == '') { echo 'dd/mm/yyyy';
								} else {
									echo $_SESSION[$guid]['i18n']['dateFormat'];
								}
            					?>." } );
								dob.add(Validate.Presence);
							</script>
							 <script type="text/javascript">
								$(function() {
									$( "#dob" ).datepicker();
								});
							</script>
						</td>
					</tr>


					<tr>
						<td colspan=2>
							<h4><?php echo __($guid, 'Student Background') ?></h4>
						</td>
					</tr>
					<tr>
						<td>
							<b><?php echo __($guid, 'Home Language - Primary') ?> *</b><br/>
							<span class="emphasis small"><?php echo __($guid, 'The primary language used in the student\'s home.') ?></span>
						</td>
						<td class="right">
							<select name="languageHomePrimary" id="languageHomePrimary" class="standardWidth">
								<?php
                                echo "<option value='Please select...'>Please select...</option>";
								try {
									$dataSelect = array();
									$sqlSelect = 'SELECT name FROM gibbonLanguage ORDER BY name';
									$resultSelect = $connection2->prepare($sqlSelect);
									$resultSelect->execute($dataSelect);
								} catch (PDOException $e) {
								}
								while ($rowSelect = $resultSelect->fetch()) {
									$selected = '';
									if ($row['languageHomePrimary'] == $rowSelect['name']) {
										$selected = 'selected';
									}
									echo "<option $selected value='".$rowSelect['name']."'>".htmlPrep(__($guid, $rowSelect['name'])).'</option>';
								}
								?>
							</select>
							<script type="text/javascript">
								var languageHomePrimary=new LiveValidation('languageHomePrimary');
								languageHomePrimary.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
							</script>
						</td>
					</tr>
					<tr>
						<td>
							<b><?php echo __($guid, 'Home Language - Secondary') ?></b><br/>
							<span class="emphasis small"><?php echo __($guid, 'The primary language used in the student\'s home.') ?></span>
						</td>
						<td class="right">
							<select name="languageHomeSecondary" id="languageHomeSecondary" class="standardWidth">
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
									if ($row['languageHomeSecondary'] == $rowSelect['name']) {
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
							<b><?php echo __($guid, 'First Language') ?> *</b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Student\'s native/first/mother language.') ?></span>
						</td>
						<td class="right">
							<select name="languageFirst" id="languageFirst" class="standardWidth">
								<?php
                                echo "<option value='Please select...'>Please select...</option>";
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
							<script type="text/javascript">
								var languageFirst=new LiveValidation('languageFirst');
								languageFirst.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
							</script>
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
						</td>
					</tr>
					<tr>
						<td>
							<b><?php echo __($guid, 'Citizenship') ?></b><br/>
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
										echo "<option value='".$rowSelect['printable_name']."'>".htmlPrep($rowSelect['printable_name']).'</option>';
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
							<b><?php echo __($guid, 'Citizenship Passport Number') ?></b><br/>
						</td>
						<td class="right">
							<input name="citizenship1Passport" id="citizenship1Passport" maxlength=30 value="<?php echo $row['citizenship1Passport'] ?>" type="text" class="standardWidth">
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
							<input name="nationalIDCardNumber" id="nationalIDCardNumber" maxlength=30 value="<?php echo $row['nationalIDCardNumber'] ?>" type="text" class="standardWidth">
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
							<input name="visaExpiryDate" id="visaExpiryDate" maxlength=10 value="<?php echo dateConvertBack($guid, $row['visaExpiryDate']) ?>" type="text" class="standardWidth">
							<script type="text/javascript">
								var visaExpiryDate=new LiveValidation('visaExpiryDate');
								visaExpiryDate.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]['i18n']['dateFormatRegEx'] == '') { echo "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i";
								} else {
									echo $_SESSION[$guid]['i18n']['dateFormatRegEx'];
								}
								?>, failureMessage: "Use <?php if ($_SESSION[$guid]['i18n']['dateFormat'] == '') { echo 'dd/mm/yyyy';
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


					<tr>
						<td colspan=2>
							<h4><?php echo __($guid, 'Student Contact') ?></h4>
						</td>
					</tr>
					<tr>
						<td>
							<b><?php echo __($guid, 'Email') ?></b><br/>
						</td>
						<td class="right">
							<input name="email" id="email" maxlength=50 value="<?php echo $row['email'] ?>" type="text" class="standardWidth">
							<script type="text/javascript">
								var email=new LiveValidation('email');
								email.add(Validate.Email);
							</script>
						</td>
					</tr>
					<?php
                    for ($i = 1; $i < 3; ++$i) {
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
						<td colspan=2>
							<h4><?php echo __($guid, 'Special Educational Needs & Medical') ?></h4>
							<?php
                            $applicationFormSENText = getSettingByScope($connection2, 'Students', 'applicationFormSENText');
							if ($applicationFormSENText != '') {
								echo '<p>';
								echo $applicationFormSENText;
								echo '</p>';
							}
							?>
						</td>
					</tr>
					<script type="text/javascript">
						$(document).ready(function(){
							$(".sen").change(function(){
								if ($('select.sen option:selected').val()=="Y" ) {
									$("#senDetailsRow").slideDown("fast", $("#senDetailsRow").css("display","table-row"));
								} else {
									$("#senDetailsRow").css("display","none");
								}
							 });
						});
					</script>
					<tr>
						<td>
							<b><?php echo __($guid, 'Special Educational Needs (SEN)') ?></b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Are there any known or suspected SEN concerns, or previous SEN assessments?') ?></span><br/>
						</td>
						<td class="right">
							<select name="sen" id="sen" class='sen standardWidth'>
								<option value="Please select..."><?php echo __($guid, 'Please select...') ?></option>
								<option <?php if ($row['sen'] == 'Y') { echo 'selected'; } ?> value="Y" /> <?php echo ynExpander($guid, 'Y') ?>
								<option <?php if ($row['sen'] == 'N') { echo 'selected'; } ?> value="N" /> <?php echo ynExpander($guid, 'N') ?>
							</select>
							<script type="text/javascript">
								var sen=new LiveValidation('sen');
								sen.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
							</script>
						</td>
					</tr>
					<tr id='senDetailsRow' <?php if ($row['sen'] == 'N') { echo "style='display: none'"; } ?>>
						<td colspan=2 style='padding-top: 15px'>
							<b><?php echo __($guid, 'SEN Details') ?></b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Provide any comments or information concerning your child\'s development and SEN history.') ?></span><br/>
							<textarea name="senDetails" id="senDetails" rows=5 style="width:738px; margin: 5px 0px 0px 0px"><?php echo htmlPrep($row['senDetails']) ?></textarea>
						</td>
					</tr>
					<tr>
						<td colspan=2 style='padding-top: 15px'>
							<b><?php echo __($guid, 'Medical Information') ?></b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Please indicate any medical conditions.') ?></span><br/>
							<textarea name="medicalInformation" id="medicalInformation" rows=5 style="width:738px; margin: 5px 0px 0px 0px"><?php echo htmlPrep($row['medicalInformation']) ?></textarea>
						</td>
					</tr>


					<tr>
						<td colspan=2>
							<h4><?php echo __($guid, 'Previous Schools') ?></h4>
							<p><?php echo __($guid, 'Please give information on the last two schools attended by the applicant.') ?></p>
						</td>
					</tr>
					<?php
                    $applicationFormRefereeLink = getSettingByScope($connection2, 'Students', 'applicationFormRefereeLink');
					if ($applicationFormRefereeLink != '') {
						?>
						<tr>
							<td>
								<b><?php echo __($guid, 'Current School Reference Email') ?> *</b><br/>
							<span class="emphasis small"><?php echo __($guid, 'An email address for a referee at the applicant\'s current school.') ?></span>
							</td>
							<td class="right">
								<input name="referenceEmail" id="referenceEmail" maxlength=100 value="<?php echo htmlPrep($row['referenceEmail']) ?>" type="text" class="standardWidth">
								<script type="text/javascript">
									var referenceEmail=new LiveValidation('referenceEmail');
									referenceEmail.add(Validate.Presence);
									referenceEmail.add(Validate.Email);
								</script>
							</td>
						</tr>
						<?php

					}
					?>
					<tr>
						<td colspan=2>
							<?php
                            echo "<table cellspacing='0' style='width: 100%'>";
							echo "<tr class='head'>";
							echo '<th>';
							echo __($guid, 'School Name');
							echo '</th>';
							echo '<th>';
							echo __($guid, 'Address');
							echo '</th>';
							echo '<th>';
							echo __($guid, 'Grades<br/>Attended');
							echo '</th>';
							echo '<th>';
							echo __($guid, 'Language of<br/>Instruction');
							echo '</th>';
							echo '<th>';
							echo __($guid, 'Joining Date')."<br/><span style='font-size: 80%'>";
							if ($_SESSION[$guid]['i18n']['dateFormat'] == '') {
								echo 'dd/mm/yyyy';
							} else {
								echo $_SESSION[$guid]['i18n']['dateFormat'];
							}
							echo '</span>';
							echo '</th>';
							echo '</tr>';

							for ($i = 1; $i < 3; ++$i) {
								if ((($i % 2) - 1) == 0) {
									$rowNum = 'even';
								} else {
									$rowNum = 'odd';
								}

								echo "<tr class=$rowNum>";
								echo '<td>';
								echo "<input name='schoolName$i' id='schoolName$i' maxlength=50 value='".htmlPrep($row["schoolName$i"])."' type='text' style='width:120px; float: left'>";
								echo '</td>';
								echo '<td>';
								echo "<input name='schoolAddress$i' id='schoolAddress$i' maxlength=255 value='".htmlPrep($row["schoolAddress$i"])."' type='text' style='width:120px; float: left'>";
								echo '</td>';
								echo '<td>';
								echo "<input name='schoolGrades$i' id='schoolGrades$i' maxlength=20 value='".htmlPrep($row["schoolGrades$i"])."' type='text' style='width:70px; float: left'>";
								echo '</td>';
								echo '<td>';
								echo "<input name='schoolLanguage$i' id='schoolLanguage$i' maxlength=50 value='".htmlPrep($row["schoolLanguage$i"])."' type='text' style='width:100px; float: left'>"; ?>
								<script type="text/javascript">
									$(function() {
										var availableTags=[
											<?php
											try {
												$dataAuto = array();
												$sqlAuto = 'SELECT DISTINCT schoolLanguage'.$i.' FROM gibbonApplicationForm ORDER BY schoolLanguage'.$i;
												$resultAuto = $connection2->prepare($sqlAuto);
												$resultAuto->execute($dataAuto);
											} catch (PDOException $e) {
												echo "<div class='error'>".$e->getMessage().'</div>';
											}
											while ($rowAuto = $resultAuto->fetch()) {
												echo '"'.$rowAuto['schoolLanguage'.$i].'", ';
											}
											?>
										];
										$( "#schoolLanguage<?php echo $i ?>" ).autocomplete({source: availableTags});
									});
								</script>
								<?php
                                echo '</td>';
                				echo '<td>'; ?>
									<input name="<?php echo "schoolDate$i" ?>" id="<?php echo "schoolDate$i" ?>" maxlength=10 value="<?php echo dateConvertBack($guid, $row["schoolDate$i"]) ?>" type="text" style="width:90px; float: left">
									<script type="text/javascript">
										$(function() {
											$( "#<?php echo "schoolDate$i" ?>" ).datepicker();
										});
									</script>
									<?php
								echo '</td>';
								echo '</tr>';
							}
							echo '</table>'; ?>
						</td>
					</tr>

					<?php
                    //CUSTOM FIELDS FOR STUDENT
                    $fields = unserialize($row['fields']);
					$resultFields = getCustomFields($connection2, $guid, true, false, false, false, true, null);
					if ($resultFields->rowCount() > 0) {
						?>
						<tr>
							<td colspan=2>
								<h4><?php echo __($guid, 'Other Information') ?></h4>
							</td>
						</tr>
						<?php
                        while ($rowFields = $resultFields->fetch()) {
                            echo renderCustomFieldRow($connection2, $guid, $rowFields, @$fields[$rowFields['gibbonPersonFieldID']]);
                        }
           			 }

					if ($row['gibbonFamilyID'] == '') {
						?>
						<input type="hidden" name="gibbonFamily" value="FALSE">

						<tr class='break'>
							<td colspan=2>
								<h3>
									<?php echo __($guid, 'Home Address') ?>
								</h3>
							</td>
						</tr>
						<tr>
							<td colspan=2>
								<p>
									<?php echo __($guid, 'This address will be used for all members of the family. If an individual within the family needs a different address, this can be set through Data Updater after admission.') ?>
								</p>
							</td>
						</tr>
						<tr>
							<td>
								<b><?php echo __($guid, 'Home Address') ?> *</b><br/>
								<span class="emphasis small"><?php echo __($guid, 'Unit, Building, Street') ?></span>
							</td>
							<td class="right">
								<input name="homeAddress" id="homeAddress" maxlength=255 value="<?php echo $row['homeAddress'] ?>" type="text" class="standardWidth">
								<script type="text/javascript">
									var homeAddress=new LiveValidation('homeAddress');
									homeAddress.add(Validate.Presence);
								</script>
							</td>
						</tr>
						<tr>
							<td>
								<b><?php echo __($guid, 'Home Address') ?> (District) *</b><br/>
								<span class="emphasis small"><?php echo __($guid, 'County, State, District') ?></span>
							</td>
							<td class="right">
								<input name="homeAddressDistrict" id="homeAddressDistrict" maxlength=30 value="<?php echo $row['homeAddressDistrict'] ?>" type="text" class="standardWidth">
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
									$( "#homeAddressDistrict" ).autocomplete({source: availableTags});
								});
							</script>
							<script type="text/javascript">
								var homeAddressDistrict=new LiveValidation('homeAddressDistrict');
								homeAddressDistrict.add(Validate.Presence);
							</script>
						</tr>
						<tr>
							<td>
								<b><?php echo __($guid, 'Home Address (Country)') ?> *</b><br/>
							</td>
							<td class="right">
								<select name="homeAddressCountry" id="homeAddressCountry" class="standardWidth">
									<?php
                                    try {
                                        $dataSelect = array();
                                        $sqlSelect = 'SELECT printable_name FROM gibbonCountry ORDER BY printable_name';
                                        $resultSelect = $connection2->prepare($sqlSelect);
                                        $resultSelect->execute($dataSelect);
                                    } catch (PDOException $e) {
                                    }
									echo "<option value='Please select...'>".__($guid, 'Please select...').'</option>';
									while ($rowSelect = $resultSelect->fetch()) {
										$selected = '';
										if ($rowSelect['printable_name'] == $row['homeAddressCountry']) {
											$selected = 'selected';
										}
										echo "<option $selected value='".$rowSelect['printable_name']."'>".htmlPrep(__($guid, $rowSelect['printable_name'])).'</option>';
									}
									?>
								</select>
								<script type="text/javascript">
									var homeAddressCountry=new LiveValidation('homeAddressCountry');
									homeAddressCountry.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
								</script>
							</td>
						</tr>
						<?php

                        if ($row['parent1gibbonPersonID'] != '') {
                            $start = 2;
                            ?>
							<tr class='break'>
								<td colspan=2>
									<h3>
										<?php echo __($guid, 'Parent/Guardian 1') ?>
										<?php
                                        if ($i == 1) {
                                            echo "<span style='font-size: 75%'></span>";
                                        }
                            			?>
									</h3>
								</td>
							</tr>
							<tr>
								<td colspan=2>
									<p>
										<?php echo __($guid, 'The parent is already a Gibbon user, and so their details cannot be edited in this view.') ?>
									</p>
								</td>
							</tr>
							<tr>
								<td>
									<b><?php echo __($guid, 'Surname') ?></b><br/>
									<span class="emphasis small"><?php echo __($guid, 'Family name as shown in ID documents.') ?></span>
								</td>
								<td class="right">
									<input readonly name='parent1surname' maxlength=30 value="<?php echo $row['parent1surname'] ?>" type="text" class="standardWidth">
								</td>
							</tr>
							<tr>
								<td>
									<b><?php echo __($guid, 'Preferred Name') ?></b><br/>
									<span class="emphasis small"><?php echo __($guid, 'Most common name, alias, nickname, etc.') ?></span>
								</td>
								<td class="right">
									<input readonly name='parent1preferredName' maxlength=30 value="<?php echo $row['parent1preferredName'] ?>" type="text" class="standardWidth">
								</td>
							</tr>

							<tr>
								<td>
									<b><?php echo __($guid, 'Relationship') ?> *</b><br/>
								</td>
								<td class="right">
									<select name="parent1relationship" id="parent1relationship" class="standardWidth">
										<option value="Please select..."><?php echo __($guid, 'Please select...') ?></option>
										<option <?php if ($row['parent1relationship'] == 'Mother') { echo 'selected'; } ?> value="Mother"><?php echo __($guid, 'Mother') ?></option>
										<option <?php if ($row['parent1relationship'] == 'Father') { echo 'selected'; } ?> value="Father"><?php echo __($guid, 'Father') ?></option>
										<option <?php if ($row['parent1relationship'] == 'Step-Mother') { echo 'selected'; } ?> value="Step-Mother"><?php echo __($guid, 'Step-Mother') ?></option>
										<option <?php if ($row['parent1relationship'] == 'Step-Father') { echo 'selected'; } ?> value="Step-Father"><?php echo __($guid, 'Step-Father') ?></option>
										<option <?php if ($row['parent1relationship'] == 'Adoptive Parent') { echo 'selected'; } ?> value="Adoptive Parent"><?php echo __($guid, 'Adoptive Parent') ?></option>
										<option <?php if ($row['parent1relationship'] == 'Guardian') { echo 'selected'; } ?> value="Guardian"><?php echo __($guid, 'Guardian') ?></option>
										<option <?php if ($row['parent1relationship'] == 'Grandmother') { echo 'selected'; } ?> value="Grandmother"><?php echo __($guid, 'Grandmother') ?></option>
										<option <?php if ($row['parent1relationship'] == 'Grandfather') { echo 'selected'; } ?> value="Grandfather"><?php echo __($guid, 'Grandfather') ?></option>
										<option <?php if ($row['parent1relationship'] == 'Aunt') { echo 'selected'; } ?> value="Aunt"><?php echo __($guid, 'Aunt') ?></option>
										<option <?php if ($row['parent1relationship'] == 'Uncle') { echo 'selected'; } ?> value="Uncle"><?php echo __($guid, 'Uncle') ?></option>
										<option <?php if ($row['parent1relationship'] == 'Nanny/Helper') { echo 'selected'; } ?> value="Nanny/Helper"><?php echo __($guid, 'Nanny/Helper') ?></option>
										<option <?php if ($row['parent1relationship'] == 'Other') { echo 'selected'; } ?> value="Other"><?php echo __($guid, 'Other') ?></option>
									</select>
									<script type="text/javascript">
										var parent1relationship=new LiveValidation('parent1relationship');
										parent1relationship.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
									</script>
								</td>
							</tr>
							<input name='parent1gibbonPersonID' value="<?php echo $row['parent1gibbonPersonID'] ?>" type="hidden">
							<?php
                                //CUSTOM FIELDS FOR PARENT 1 WITH FAMILY
                                $parent1fields = unserialize($row['parent1fields']);
                            $resultFields = getCustomFields($connection2, $guid, false, false, true, false, true, null);
                            if ($resultFields->rowCount() > 0) {
                                while ($rowFields = $resultFields->fetch()) {
                                    echo renderCustomFieldRow($connection2, $guid, $rowFields, $parent1fields[$rowFields['gibbonPersonFieldID']], 'parent1');
                                }
                            }
                            ?>
							<?php

                        } else {
                            $start = 1;
                        }
						for ($i = $start;$i < 3;++$i) {
							?>
							<tr class='break'>
								<td colspan=2>
									<h3>
										<?php echo __($guid, 'Parent/Guardian') ?> <?php echo $i ?>
										<?php
                                        if ($i == 1) {
                                            echo "<span style='font-size: 75%'>".__($guid, '(e.g. mother)').'</span>';
                                        } elseif ($i == 2 and $row['parent1gibbonPersonID'] == '') {
                                            echo "<span style='font-size: 75%'>".__($guid, '(e.g. father)').'</span>';
                                        }
                    					?>
									</h3>
								</td>
							</tr>
							<tr>
								<td colspan=2>
									<h4><?php echo sprintf(__($guid, 'Parent/Guardian %1$s Personal Data'), $i) ?></h4>
								</td>
							</tr>
							<tr>
								<td>
									<b><?php echo __($guid, 'Title') ?><?php if ($i == 1) { echo ' *'; } ?></b><br/>
									<span class="emphasis small"></span>
								</td>
								<td class="right">
									<select class="standardWidth" id="<?php echo "parent$i" ?>title" name="<?php echo "parent$i" ?>title">
										<?php
                                        if ($i == 1) {
                                            ?>
											<option value="Please select..."><?php echo __($guid, 'Please select...') ?></option>
										<?php

                                        } else {
                                            ?>
											<option value=""></option>
											<?php

                                        }
                    					?>
										<option <?php if ($row["parent$i".'title'] == 'Ms.') { echo 'selected'; } ?> value="Ms."><?php echo __($guid, 'Ms.') ?></option>
										<option <?php if ($row["parent$i".'title'] == 'Miss') { echo 'selected'; } ?> value="Miss"><?php echo __($guid, 'Miss') ?></option>
										<option <?php if ($row["parent$i".'title'] == 'Mr.') { echo 'selected'; } ?> value="Mr."><?php echo __($guid, 'Mr.') ?></option>
										<option <?php if ($row["parent$i".'title'] == 'Mrs.') { echo 'selected'; } ?> value="Mrs."><?php echo __($guid, 'Mrs.') ?></option>
										<option <?php if ($row["parent$i".'title'] == 'Dr.') { echo 'selected'; } ?> value="Dr."><?php echo __($guid, 'Dr.') ?></option>
									</select>

									<?php
                                    if ($i == 1) {
                                        ?>
										<script type="text/javascript">
											var <?php echo "parent$i" ?>title=new LiveValidation('<?php echo "parent$i" ?>title');
											<?php echo "parent$i" ?>title.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
										</script>
										 <?php

                                    }
                   		 			?>
								</td>
							</tr>
							<tr>
								<td>
									<b><?php echo __($guid, 'Surname') ?><?php if ($i == 1) { echo ' *'; } ?></b><br/>
									<span class="emphasis small"><?php echo __($guid, 'Family name as shown in ID documents.') ?></span>
								</td>
								<td class="right">
									<input name="<?php echo "parent$i" ?>surname" id="<?php echo "parent$i" ?>surname" maxlength=30 value="<?php echo $row["parent$i".'surname']; ?>" type="text" class="standardWidth">
									<?php
                                    if ($i == 1) {
                                        ?>
										<script type="text/javascript">
											var <?php echo "parent$i" ?>surname=new LiveValidation('<?php echo "parent$i" ?>surname');
											<?php echo "parent$i" ?>surname.add(Validate.Presence);
										</script>
										 <?php

                                    }
                   		 			?>
								</td>
							</tr>
							<tr>
								<td>
									<b><?php echo __($guid, 'First Name') ?><?php if ($i == 1) { echo ' *'; } ?></b><br/>
									<span class="emphasis small"><?php echo __($guid, 'First name as shown in ID documents.') ?></span>
								</td>
								<td class="right">
									<input name="<?php echo "parent$i" ?>firstName" id="<?php echo "parent$i" ?>firstName" maxlength=30 value="<?php echo $row["parent$i".'firstName']; ?>" type="text" class="standardWidth">
									<?php
                                    if ($i == 1) {
                                        ?>
										<script type="text/javascript">
											var <?php echo "parent$i" ?>firstName=new LiveValidation('<?php echo "parent$i" ?>firstName');
											<?php echo "parent$i" ?>firstName.add(Validate.Presence);
										</script>
										 <?php

                                    }
                   		 			?>
								</td>
							</tr>
							<tr>
								<td>
									<b><?php echo __($guid, 'Preferred Name') ?><?php if ($i == 1) { echo ' *'; } ?></b><br/>
									<span class="emphasis small"><?php echo __($guid, 'Most common name, alias, nickname, etc.') ?></span>
								</td>
								<td class="right">
									<input name="<?php echo "parent$i" ?>preferredName" id="<?php echo "parent$i" ?>preferredName" maxlength=30 value="<?php echo $row["parent$i".'preferredName']; ?>" type="text" class="standardWidth">
									<?php
                                    if ($i == 1) {
                                        ?>
										<script type="text/javascript">
											var <?php echo "parent$i" ?>preferredName=new LiveValidation('<?php echo "parent$i" ?>preferredName');
											<?php echo "parent$i" ?>preferredName.add(Validate.Presence);
										</script>
										 <?php

                                    }
                   		 			?>
								</td>
							</tr>
							<tr>
								<td>
									<b><?php echo __($guid, 'Official Name') ?><?php if ($i == 1) { echo ' *'; } ?></b><br/>
									<span class="emphasis small"><?php echo __($guid, 'Full name as shown in ID documents.') ?></span>
								</td>
								<td class="right">
									<input name="<?php echo "parent$i" ?>officialName" id="<?php echo "parent$i" ?>officialName" maxlength=30 value="<?php echo $row["parent$i".'officialName']; ?>" type="text" class="standardWidth">
									<?php
                                    if ($i == 1) {
                                        ?>
										<script type="text/javascript">
											var <?php echo "parent$i" ?>officialName=new LiveValidation('<?php echo "parent$i" ?>officialName');
											<?php echo "parent$i" ?>officialName.add(Validate.Presence);
										</script>
										 <?php

                                    }
                   		 			?>
								</td>
							</tr>
							<tr>
								<td>
									<b><?php echo __($guid, 'Name In Characters') ?></b><br/>
									<span class="emphasis small"><?php echo __($guid, 'Chinese or other character-based name.') ?></span>
								</td>
								<td class="right">
									<input name="<?php echo "parent$i" ?>nameInCharacters" id="<?php echo "parent$i" ?>nameInCharacters" maxlength=20 value="<?php echo $row["parent$i".'nameInCharacters']; ?>" type="text" class="standardWidth">
								</td>
							</tr>
							<tr>
								<td>
									<b><?php echo __($guid, 'Gender') ?><?php if ($i == 1) { echo ' *'; } ?></b><br/>
								</td>
								<td class="right">
									<select name="<?php echo "parent$i" ?>gender" id="<?php echo "parent$i" ?>gender" class="standardWidth">
										<?php
                                        if ($i == 1) {
                                            ?>
											<option value="Please select..."><?php echo __($guid, 'Please select...') ?></option>
											<?php

                                        } else {
                                            ?>
											<option value=""></option>
											<?php

                                        }
                    					?>
										<option <?php if ($row["parent$i".'gender'] == 'F') { echo 'selected'; } ?> value="F">F</option>
										<option <?php if ($row["parent$i".'gender'] == 'M') { echo 'selected'; } ?> value="M">M</option>
									</select>
									<?php
                                    if ($i == 1) {
                                        ?>
										<script type="text/javascript">
											var <?php echo "parent$i" ?>gender=new LiveValidation('<?php echo "parent$i" ?>gender');
											<?php echo "parent$i" ?>gender.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
										</script>
										 <?php

                                    }
                   		 			?>
								</td>
							</tr>
							<tr>
								<td>
									<b><?php echo __($guid, 'Relationship') ?><?php if ($i == 1) { echo ' *'; } ?></b><br/>
								</td>
								<td class="right">
									<select name="<?php echo "parent$i" ?>relationship" id="<?php echo "parent$i" ?>relationship" class="standardWidth">
										<?php
                                        if ($i == 1) {
                                            echo '<option value="Please select...">Please select...</option>';
                                        } else {
                                            echo '<option value=""></option>';
                                        }
                    					?>
										<option <?php if ($row['parent'.$i.'relationship'] == 'Mother') { echo 'selected'; } ?> value="Mother"><?php echo __($guid, 'Mother') ?></option>
										<option <?php if ($row['parent'.$i.'relationship'] == 'Father') { echo 'selected'; } ?> value="Father"><?php echo __($guid, 'Father') ?></option>
										<option <?php if ($row['parent'.$i.'relationship'] == 'Step-Mother') { echo 'selected'; } ?> value="Step-Mother"><?php echo __($guid, 'Step-Mother') ?></option>
										<option <?php if ($row['parent'.$i.'relationship'] == 'Step-Father') { echo 'selected'; } ?> value="Step-Father"><?php echo __($guid, 'Step-Father') ?></option>
										<option <?php if ($row['parent'.$i.'relationship'] == 'Adoptive Parent') { echo 'selected'; } ?> value="Adoptive Parent"><?php echo __($guid, 'Adoptive Parent') ?></option>
										<option <?php if ($row['parent'.$i.'relationship'] == 'Guardian') { echo 'selected'; } ?> value="Guardian"><?php echo __($guid, 'Guardian') ?></option>
										<option <?php if ($row['parent'.$i.'relationship'] == 'Grandmother') { echo 'selected'; } ?> value="Grandmother"><?php echo __($guid, 'Grandmother') ?></option>
										<option <?php if ($row['parent'.$i.'relationship'] == 'Grandfather') { echo 'selected'; } ?> value="Grandfather"><?php echo __($guid, 'Grandfather') ?></option>
										<option <?php if ($row['parent'.$i.'relationship'] == 'Aunt') { echo 'selected'; } ?> value="Aunt"><?php echo __($guid, 'Aunt') ?></option>
										<option <?php if ($row['parent'.$i.'relationship'] == 'Uncle') { echo 'selected'; } ?> value="Uncle"><?php echo __($guid, 'Uncle') ?></option>
										<option <?php if ($row['parent'.$i.'relationship'] == 'Nanny/Helper') { echo 'selected'; } ?> value="Nanny/Helper"><?php echo __($guid, 'Nanny/Helper') ?></option>
										<option <?php if ($row['parent'.$i.'relationship'] == 'Other') { echo 'selected'; } ?> value="Other"><?php echo __($guid, 'Other') ?></option>
									</select>
									<?php
                                    if ($i == 1) {
                                        ?>
										<script type="text/javascript">
											var <?php echo "parent$i" ?>relationship=new LiveValidation('<?php echo "parent$i" ?>relationship');
											<?php echo "parent$i" ?>relationship.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
										</script>
										 <?php

                                    }
                   		 			?>
								</td>
							</tr>

							<tr>
								<td colspan=2>
									<h4><?php echo sprintf(__($guid, 'Parent/Guardian %1$s Background'), $i) ?></h4>
								</td>
							</tr>
							<tr>
								<td>
									<b><?php echo __($guid, 'First Language') ?></b><br/>
								</td>
								<td class="right">
									<select name="<?php echo "parent$i" ?>languageFirst" id="<?php echo "parent$i" ?>languageFirst" class="standardWidth">
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
											if ($row['parent'.$i.'languageFirst'] == $rowSelect['name']) {
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
									<select name="<?php echo "parent$i" ?>languageSecond" id="<?php echo "parent$i" ?>languageSecond" class="standardWidth">
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
											if ($row['parent'.$i.'languageSecond'] == $rowSelect['name']) {
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
									<b><?php echo __($guid, 'Citizenship') ?></b><br/>
								</td>
								<td class="right">
									<select name="<?php echo "parent$i" ?>citizenship1" id="<?php echo "parent$i" ?>citizenship1" class="standardWidth">
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
												echo "<option value='".$rowSelect['printable_name']."'>".htmlPrep(__($guid, $rowSelect['printable_name'])).'</option>';
											}
										} else {
											$nationalities = explode(',', $nationalityList);
											foreach ($nationalities as $nationality) {
												$selected = '';
												if (trim($nationality) == $row['parent'.$i.'citizenship1']) {
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
									<?php
                                    if ($_SESSION[$guid]['country'] == '') {
                                        echo '<b>'.__($guid, 'National ID Card Number').'</b><br/>';
                                    } else {
                                        echo '<b>'.$_SESSION[$guid]['country'].' '.__($guid, 'ID Card Number').'</b><br/>';
                                    }
                   		 			?>
								</td>
								<td class="right">
									<input name="<?php echo "parent$i" ?>nationalIDCardNumber" id="<?php echo "parent$i" ?>nationalIDCardNumber" maxlength=30 value="<?php echo $row["parent$i".'nationalIDCardNumber']; ?>" type="text" class="standardWidth">
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
										echo "<input name='parent".$i."residencyStatus' id='parent".$i."residencyStatus' maxlength=30 value='".$row['residencyStatus']."' type='text' style='width: 300px'>";
									} else {
										echo "<select name='parent".$i."residencyStatus' id='parent".$i."residencyStatus' style='width: 302px'>";
										echo "<option value=''></option>";
										$residencyStatuses = explode(',', $residencyStatusList);
										foreach ($residencyStatuses as $residencyStatus) {
											$selected = '';
											if (trim($residencyStatus) == $row['parent'.$i.'residencyStatus']) {
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
									<input name="<?php echo "parent$i" ?>visaExpiryDate" id="<?php echo "parent$i" ?>visaExpiryDate" maxlength=10 value="<?php echo dateConvertBack($guid, $row['parent'.$i.'visaExpiryDate']) ?>" type="text" class="standardWidth">
									<script type="text/javascript">
										var <?php echo "parent$i" ?>visaExpiryDate=new LiveValidation('<?php echo "parent$i" ?>visaExpiryDate');
										<?php echo "parent$i" ?>visaExpiryDate.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]['i18n']['dateFormatRegEx'] == '') { echo "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i";
										} else {
											echo $_SESSION[$guid]['i18n']['dateFormatRegEx'];
										}
										?>, failureMessage: "Use <?php if ($_SESSION[$guid]['i18n']['dateFormat'] == '') { echo 'dd/mm/yyyy';
										} else {
											echo $_SESSION[$guid]['i18n']['dateFormat'];
										}
                    					?>." } );
									</script>
									 <script type="text/javascript">
										$(function() {
											$( "#<?php echo "parent$i" ?>visaExpiryDate" ).datepicker();
										});
									</script>
								</td>
							</tr>


							<tr>
								<td colspan=2>
									<h4><?php echo sprintf(__($guid, 'Parent/Guardian %1$s Contact'), $i) ?></h4>
								</td>
							</tr>
							<tr>
								<td>
									<b><?php echo __($guid, 'Email') ?><?php if ($i == 1) { echo ' *'; } ?></b><br/>
								</td>
								<td class="right">
									<input name="<?php echo "parent$i" ?>email" id="<?php echo "parent$i" ?>email" maxlength=50 value="<?php echo $row["parent$i".'email']; ?>" type="text" class="standardWidth">
									<script type="text/javascript">
										var <?php echo "parent$i" ?>email=new LiveValidation('<?php echo "parent$i" ?>email');
										<?php echo "parent$i" ?>email.add(Validate.Email);
										<?php
                                        if ($i == 1) {
                                            echo "parent$i".'email.add(Validate.Presence);';
                                        }
                    					?>
									</script>
								</td>
							</tr>
							<?php
                            for ($y = 1; $y < 3; ++$y) {
                                ?>
								<tr>
									<td>
										<b><?php echo __($guid, 'Phone') ?> <?php echo $y;
                                if ($i == 1 and $y == 1) {
                                    echo ' *';
                                }
                                ?></b><br/>
										<span class="emphasis small"><?php echo __($guid, 'Type, country code, number.') ?></span>
									</td>
									<td class="right">
										<input name="<?php echo "parent$i" ?>phone<?php echo $y ?>" id="<?php echo "parent$i" ?>phone<?php echo $y ?>" maxlength=20 value="<?php echo $row['parent'.$i.'phone'.$y] ?>" type="text" style="width: 160px">
										<?php
                                        if ($i == 1 and $y == 1) {
                                            ?>
											<script type="text/javascript">
												var <?php echo "parent$i" ?>phone<?php echo $y ?>=new LiveValidation('<?php echo "parent$i" ?>phone<?php echo $y ?>');
												<?php echo "parent$i" ?>phone<?php echo $y ?>.add(Validate.Presence);
											</script>
											<?php

                                        }
                                		?>
										<select name="<?php echo "parent$i" ?>phone<?php echo $y ?>CountryCode" id="<?php echo "parent$i" ?>phone<?php echo $y ?>CountryCode" style="width: 60px">
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
												if ($row['parent'.$i.'phone'.$y.'CountryCode'] != '' and $row['parent'.$i.'phone'.$y.'CountryCode'] == $rowSelect['iddCountryCode']) {
													$selected = 'selected';
												}
												echo "<option $selected value='".$rowSelect['iddCountryCode']."'>".htmlPrep($rowSelect['iddCountryCode']).' - '.htmlPrep(__($guid, $rowSelect['printable_name'])).'</option>';
											}
											?>
										</select>
										<select style="width: 70px" name="<?php echo "parent$i" ?>phone<?php echo $y ?>Type">
											<option <?php if ($row['parent'.$i.'phone'.$y.'Type'] == '') { echo 'selected'; } ?> value=""></option>
											<option <?php if ($row['parent'.$i.'phone'.$y.'Type'] == 'Mobile') { echo 'selected'; } ?> value="Mobile"><?php echo __($guid, 'Mobile') ?></option>
											<option <?php if ($row['parent'.$i.'phone'.$y.'Type'] == 'Home') { echo 'selected'; } ?> value="Home"><?php echo __($guid, 'Home') ?></option>
											<option <?php if ($row['parent'.$i.'phone'.$y.'Type'] == 'Work') { echo 'selected'; } ?> value="Work"><?php echo __($guid, 'Work') ?></option>
											<option <?php if ($row['parent'.$i.'phone'.$y.'Type'] == 'Fax') { echo 'selected'; } ?> value="Fax"><?php echo __($guid, 'Fax') ?></option>
											<option <?php if ($row['parent'.$i.'phone'.$y.'Type'] == 'Pager') { echo 'selected'; } ?> value="Pager"><?php echo __($guid, 'Pager') ?></option>
											<option <?php if ($row['parent'.$i.'phone'.$y.'Type'] == 'Other') { echo 'selected'; } ?> value="Other"><?php echo __($guid, 'Other') ?></option>
										</select>
									</td>
								</tr>
								<?php

                            }
                    		?>

							<tr>
								<td colspan=2>
									<h4><?php echo __($guid, 'Employment') ?></h4>
								</td>
							</tr>
							<tr>
								<td>
									<b><?php echo __($guid, 'Profession') ?></b><br/>
								</td>
								<td class="right">
									<input name="<?php echo "parent$i" ?>profession" id="<?php echo "parent$i" ?>profession" maxlength=30 value="<?php echo $row["parent$i".'profession']; ?>" type="text" class="standardWidth">
								</td>
							</tr>
							<tr>
								<td>
									<b><?php echo __($guid, 'Employer') ?></b><br/>
								</td>
								<td class="right">
									<input name="<?php echo "parent$i" ?>employer" id="<?php echo "parent$i" ?>employer" maxlength=30 value="<?php echo $row["parent$i".'employer']; ?>" type="text" class="standardWidth">
								</td>
							</tr>
							<?php

                            //CUSTOM FIELDS FOR PARENTS, WITH FAMILY
                            $parent1fields = unserialize($row['parent1fields']);
							$parent2fields = unserialize($row['parent2fields']);
							$resultFields = getCustomFields($connection2, $guid, false, false, true, false, true, null);
							if ($resultFields->rowCount() > 0) {
								?>
								<tr <?php if ($i == 2) {
									echo "class='secondParent'"; } ?>>
									<td colspan=2>
										<h4><?php echo __($guid, 'Parent/Guardian') ?> <?php echo $i ?> <?php echo __($guid, 'Other Fields') ?></h4>
									</td>
								</tr>
								<?php
                                while ($rowFields = $resultFields->fetch()) {
                                    if ($i == 2) {
                                        echo renderCustomFieldRow($connection2, $guid, $rowFields, $parent2fields[$rowFields['gibbonPersonFieldID']], 'parent2', 'secondParent', true);
                                        ?>
										<script type="text/javascript">
											/* Advanced Options Control */
											$(document).ready(function(){
												$("#secondParent").click(function(){
													if ($('input[name=secondParent]:checked').val()=="No" ) {
														$("#parent<?php echo $i ?>custom<?php echo $rowFields['gibbonPersonFieldID'] ?>").attr("disabled", "disabled");
													}
													else {
														$("#parent<?php echo $i ?>custom<?php echo $rowFields['gibbonPersonFieldID'] ?>").removeAttr("disabled");
													}
												 });
											});
										</script>
										<?php

                                    } else {
                                        echo renderCustomFieldRow($connection2, $guid, $rowFields, $parent1fields[$rowFields['gibbonPersonFieldID']], 'parent1');
                                    }
                                }
							}
						}
					} else {
						?>
						<input type="hidden" name="gibbonFamily" value="TRUE">
						<tr class='break'>
							<td colspan=2>
								<h3><?php echo __($guid, 'Family') ?></h3>
							</td>
						</tr>
						<tr>
							<td colspan=2>
								<p><?php echo sprintf(__($guid, 'The applying family is already a member of %1$s.'), $_SESSION[$guid]['organisationName']) ?></p>
							</td>
						</tr>
						<tr>
							<td colspan=2>
								<?php
                                try {
                                    $dataFamily = array('gibbonFamilyID' => $row['gibbonFamilyID']);
                                    $sqlFamily = 'SELECT * FROM gibbonFamily WHERE gibbonFamilyID=:gibbonFamilyID';
                                    $resultFamily = $connection2->prepare($sqlFamily);
                                    $resultFamily->execute($dataFamily);
                                } catch (PDOException $e) {
                                    echo "<div class='error'>".$e->getMessage().'</div>';
                                }

								if ($resultFamily->rowCount() != 1) {
									$proceed = false;
									echo "<input readonly type='text' name='gibbonFamilyID' value='There is an error with this form!' style='width: 300px; color: #c00; text-align: right; font-weight: bold'/>";
								} else {
									$rowFamily = $resultFamily->fetch();
									echo "<table cellspacing='0' style='width: 100%'>";
									echo "<tr class='head'>";
									echo '<th>';
									echo __($guid, 'Family Name');
									echo '</th>';
									echo '<th>';
									echo __($guid, 'Parents');
									echo '</th>';
									echo '</tr>';
									echo "<tr class='even'>";
									echo '<td>';
									echo '<b>'.$rowFamily['name'].'</b><br/>';
									echo '</td>';
									echo '<td>';
									try {
										$dataRelationships = array('gibbonApplicationFormID' => $gibbonApplicationFormID);
										$sqlRelationships = 'SELECT surname, preferredName, title, gender, gibbonApplicationFormRelationship.gibbonPersonID, relationship FROM gibbonApplicationFormRelationship JOIN gibbonPerson ON (gibbonApplicationFormRelationship.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonApplicationFormRelationship.gibbonApplicationFormID=:gibbonApplicationFormID';
										$resultRelationships = $connection2->prepare($sqlRelationships);
										$resultRelationships->execute($dataRelationships);
									} catch (PDOException $e) {
										echo "<div class='error'>".$e->getMessage().'</div>';
									}
									while ($rowRelationships = $resultRelationships->fetch()) {
										echo formatName($rowRelationships['title'], $rowRelationships['preferredName'], $rowRelationships['surname'], 'Parent').' ('.$rowRelationships['relationship'].')';
										echo '<br/>';
									}
									echo '</td>';
									echo '</tr>';
									echo '</table>';
									echo "<input type='hidden' name='gibbonFamilyID' value='".$row['gibbonFamilyID']."'/>";
								}
								?>
							</td>
						</tr>
						<?php

					}
					?>
					<tr class='break'>
						<td colspan=2>
							<h3><?php echo __($guid, 'Siblings') ?></h3>
						</td>
					</tr>
					<tr>
						<td colspan=2 style='padding-top: 0px'>
							<p>Please give information on any siblings not currently studying at <?php echo $_SESSION[$guid]['organisationName'] ?>.</p>
						</td>
					</tr>
					<tr>
						<td colspan=2>
							<?php
                            echo "<table cellspacing='0' style='width: 100%'>";
							echo "<tr class='head'>";
							echo '<th>';
							echo __($guid, 'Sibling Name');
							echo '</th>';
							echo '<th>';
							echo __($guid, 'Date of Birth')."<br/><span style='font-size: 80%'>";
							if ($_SESSION[$guid]['i18n']['dateFormat'] == '') {
								echo 'dd/mm/yyyy';
							} else {
								echo $_SESSION[$guid]['i18n']['dateFormat'];
							}
							echo '</span>';
							echo '</th>';
							echo '<th>';
							echo __($guid, 'School Attending');
							echo '</th>';
							echo '<th>';
							echo __($guid, 'Joining Date')."<br/><span style='font-size: 80%'>";
							if ($_SESSION[$guid]['i18n']['dateFormat'] == '') {
								echo 'dd/mm/yyyy';
							} else {
								echo $_SESSION[$guid]['i18n']['dateFormat'];
							}
							echo '</span>';
							echo '</th>';
							echo '</tr>';

							for ($i = 1; $i < 4; ++$i) {
								if ((($i % 2) - 1) == 0) {
									$rowNum = 'even';
								} else {
									$rowNum = 'odd';
								}

								echo "<tr class=$rowNum>";
								echo '<td>';
								echo "<input name='siblingName$i' id='siblingName$i' maxlength=50 value='".$row["siblingName$i"]."' type='text' style='width:120px; float: left'>";
								echo '</td>';
								echo '<td>'; ?>
									<input name="<?php echo "siblingDOB$i" ?>" id="<?php echo "siblingDOB$i" ?>" maxlength=10 value="<?php echo dateConvertBack($guid, $row["siblingDOB$i"]) ?>" type="text" style="width:90px; float: left"><br/>
									<script type="text/javascript">
										$(function() {
											$( "#<?php echo "siblingDOB$i" ?>" ).datepicker();
										});
									</script>
									<?php
								echo '</td>';
								echo '<td>';
								echo "<input name='siblingSchool$i' id='siblingSchool$i' maxlength=50 value='".$row["siblingSchool$i"]."' type='text' style='width:200px; float: left'>";
								echo '</td>';
								echo '<td>'; ?>
									<input name="<?php echo "siblingSchoolJoiningDate$i" ?>" id="<?php echo "siblingSchoolJoiningDate$i" ?>" maxlength=10 value="<?php echo dateConvertBack($guid, $row["siblingSchoolJoiningDate$i"]) ?>" type="text" style="width:90px; float: left">
									<script type="text/javascript">
										$(function() {
											$( "#<?php echo "siblingSchoolJoiningDate$i" ?>" ).datepicker();
										});
									</script>
									<?php
								echo '</td>';
								echo '</tr>';
							}
							echo '</table>'; ?>
						</td>
					</tr>

					<?php
                    $languageOptionsActive = getSettingByScope($connection2, 'Application Form', 'languageOptionsActive');
					if ($languageOptionsActive == 'Y') {
						?>
						<tr class='break'>
							<td colspan=2>
								<h3><?php echo __($guid, 'Language Selection') ?></h3>
								<?php
                                $languageOptionsBlurb = getSettingByScope($connection2, 'Application Form', 'languageOptionsBlurb');
								if ($languageOptionsBlurb != '') {
									echo '<p>';
									echo $languageOptionsBlurb;
									echo '</p>';
								}
								?>
							</td>
						</tr>
						<tr>
							<td>
								<b><?php echo __($guid, 'Language Choice') ?></b><br/>
								<span class="emphasis small"><?php echo __($guid, 'Please choose preferred additional language to study.') ?></span>
							</td>
							<td class="right">
								<select name="languageChoice" id="languageChoice" class="standardWidth">
									<?php
                                    echo "<option value='Please select...'>".__($guid, 'Please select...').'</option>';
									$languageOptionsLanguageList = getSettingByScope($connection2, 'Application Form', 'languageOptionsLanguageList');
									$languages = explode(',', $languageOptionsLanguageList);
									foreach ($languages as $language) {
										$selected = '';
										if ($row['languageChoice'] == trim($language)) {
											$selected = 'selected';
										}
										echo "<option $selected value='".trim($language)."'>".trim($language).'</option>';
									}
									?>
								</select>
							</td>
						</tr>
						<tr>
							<td colspan=2 style='padding-top: 15px'>
								<b><?php echo __($guid, 'Language Choice Experience') ?></b><br/>
								<span class="emphasis small"><?php echo __($guid, 'Has the applicant studied the selected language before? If so, please describe the level and type of experience.') ?></span><br/>
								<textarea name="languageChoiceExperience" id="languageChoiceExperience" rows=5 style="width:738px; margin: 5px 0px 0px 0px"><?php echo htmlPrep($row['languageChoiceExperience']); ?></textarea>
							</td>
						</tr>
						<?php
					}
					?>
					<tr class='break'>
						<td colspan=2>
							<h3><?php echo __($guid, 'Scholarships') ?></h3>
							<?php
                            //Get scholarships info
                            try {
                                $dataIntro = array();
                                $sqlIntro = "SELECT * FROM gibbonSetting WHERE scope='Application Form' AND name='scholarships'";
                                $resultIntro = $connection2->prepare($sqlIntro);
                                $resultIntro->execute($dataIntro);
                            } catch (PDOException $e) {
                                echo "<div class='error'>".$e->getMessage().'</div>';
                            }
							if ($resultIntro->rowCount() == 1) {
								$rowIntro = $resultIntro->fetch();
								if ($rowIntro['value'] != '') {
									echo '<p>';
									echo $rowIntro['value'];
									echo '</p>';
								}
							}
							?>
						</td>
					</tr>
					<tr>
						<td>
							<b><?php echo __($guid, 'Interest') ?></b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Indicate if you are interested in a scholarship.') ?></span><br/>
						</td>
						<td class="right">
							<input <?php if ($row['scholarshipInterest'] == 'Y') { echo 'checked'; } ?> type="radio" id="scholarshipInterest" name="scholarshipInterest" class="type" value="Y" /> <?php echo __($guid, 'Yes') ?>
							<input <?php if ($row['scholarshipInterest'] == 'N') { echo 'checked'; } ?> type="radio" id="scholarshipInterest" name="scholarshipInterest" class="type" value="N" /><?php echo __($guid, 'No') ?>
						</td>
					</tr>
					<tr>
						<td>
							<b>Required?</b><br/>
							<span class="emphasis small">Is a scholarship <b>required</b> for you to take up a place at <?php echo $_SESSION[$guid]['organisationNameShort'] ?>?</span><br/>
						</td>
						<td class="right">
							<input <?php if ($row['scholarshipRequired'] == 'Y') { echo 'checked'; } ?> type="radio" id="scholarshipRequired" name="scholarshipRequired" class="type" value="Y" /> <?php echo __($guid, 'Yes') ?>
							<input <?php if ($row['scholarshipRequired'] == 'N') { echo 'checked'; } ?> type="radio" id="scholarshipRequired" name="scholarshipRequired" class="type" value="N" /> <?php echo __($guid, 'No') ?>
						</td>
					</tr>


					<tr class='break'>
						<td colspan=2>
							<h3><?php echo __($guid, 'Payment') ?></h3>
						</td>
					</tr>
					<script type="text/javascript">
						/* Resource 1 Option Control */
						$(document).ready(function(){
							if ($('input[name=payment]:checked').val()=="Family" ) {
								$("#companyNameRow").css("display","none");
								$("#companyContactRow").css("display","none");
								$("#companyAddressRow").css("display","none");
								$("#companyEmailRow").css("display","none");
								$("#companyCCFamilyRow").css("display","none");
								$("#companyPhoneRow").css("display","none");
								$("#companyAllRow").css("display","none");
								$("#companyCategoriesRow").css("display","none");
								companyEmail.disable() ;
								companyAddress.disable() ;
								companyContact.disable() ;
								companyName.disable() ;
							}
							else {
								if ($('input[name=companyAll]:checked').val()=="Y" ) {
									$("#companyCategoriesRow").css("display","none");
								}
							}

							$(".payment").click(function(){
								if ($('input[name=payment]:checked').val()=="Family" ) {
									$("#companyNameRow").css("display","none");
									$("#companyContactRow").css("display","none");
									$("#companyAddressRow").css("display","none");
									$("#companyEmailRow").css("display","none");
									$("#companyCCFamilyRow").css("display","none");
									$("#companyPhoneRow").css("display","none");
									$("#companyAllRow").css("display","none");
									$("#companyCategoriesRow").css("display","none");
									companyEmail.disable() ;
									companyAddress.disable() ;
									companyContact.disable() ;
									companyName.disable() ;
								} else {
									$("#companyNameRow").slideDown("fast", $("#companyNameRow").css("display","table-row"));
									$("#companyContactRow").slideDown("fast", $("#companyContactRow").css("display","table-row"));
									$("#companyAddressRow").slideDown("fast", $("#companyAddressRow").css("display","table-row"));
									$("#companyEmailRow").slideDown("fast", $("#companyEmailRow").css("display","table-row"));
									$("#companyCCFamilyRow").slideDown("fast", $("#companyCCFamilyRow").css("display","table-row"));
									$("#companyPhoneRow").slideDown("fast", $("#companyPhoneRow").css("display","table-row"));
									$("#companyAllRow").slideDown("fast", $("#companyAllRow").css("display","table-row"));
									if ($('input[name=companyAll]:checked').val()=="Y" ) {
										$("#companyCategoriesRow").css("display","none");
									} else {
										$("#companyCategoriesRow").slideDown("fast", $("#companyCategoriesRow").css("display","table-row"));
									}
									companyEmail.enable() ;
									companyAddress.enable() ;
									companyContact.enable() ;
									companyName.enable() ;
								}
							 });

							 $(".companyAll").click(function(){
								if ($('input[name=companyAll]:checked').val()=="Y" ) {
									$("#companyCategoriesRow").css("display","none");
								} else {
									$("#companyCategoriesRow").slideDown("fast", $("#companyCategoriesRow").css("display","table-row"));
								}
							 });
						});
					</script>
					<tr id="familyRow">
						<td colspan=2'>
							<p><?php echo __($guid, 'If you choose family, future invoices will be sent according to family contact preferences, which can be changed at a later date by contacting the school. For example you may wish both parents to receive the invoice, or only one. Alternatively, if you choose Company, you can choose for all or only some fees to be covered by the specified company.') ?></p>
						</td>
					</tr>
					<tr>
						<td>
							<b><?php echo __($guid, 'Send Invoices To') ?></b><br/>
						</td>
						<td class="right">
							<input <?php if ($row['payment'] == 'Family') { echo 'checked'; } ?> type="radio" name="payment" value="Family" class="payment" /> <?php echo __($guid, 'Family') ?>
							<input <?php if ($row['payment'] == 'Company') { echo 'checked'; } ?> type="radio" name="payment" value="Company" class="payment" /> <?php echo __($guid, 'Company') ?>
						</td>
					</tr>
					<tr id="companyNameRow">
						<td>
							<b><?php echo __($guid, 'Company Name') ?></b><br/>
						</td>
						<td class="right">
							<input name="companyName" id="companyName" maxlength=100 value="<?php echo $row['companyName'] ?>" type="text" class="standardWidth">
							<script type="text/javascript">
								var companyName=new LiveValidation('companyName');
								companyName.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<tr id="companyContactRow">
						<td>
							<b><?php echo __($guid, 'Company Contact Person') ?></b><br/>
						</td>
						<td class="right">
							<input name="companyContact" id="companyContact" maxlength=100 value="<?php echo $row['companyContact'] ?>" type="text" class="standardWidth">
							<script type="text/javascript">
								var companyContact=new LiveValidation('companyContact');
								companyContact.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<tr id="companyAddressRow">
						<td>
							<b><?php echo __($guid, 'Company Address') ?></b><br/>
						</td>
						<td class="right">
							<input name="companyAddress" id="companyAddress" maxlength=255 value="<?php echo $row['companyAddress'] ?>" type="text" class="standardWidth">
							<script type="text/javascript">
								var companyAddress=new LiveValidation('companyAddress');
								companyAddress.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<tr id="companyEmailRow">
						<td>
                            <b><?php echo __($guid, 'Company Emails') ?> *</b><br/>
        					<span class="emphasis small"><?php echo __($guid, 'Comma-separated list of email address.') ?></span>
						</td>
						<td class="right">
							<input name="companyEmail" id="companyEmail" maxlength=255 value="<?php echo $row['companyEmail'] ?>" type="text" class="standardWidth">
							<script type="text/javascript">
								var companyEmail=new LiveValidation('companyEmail');
								companyEmail.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<tr id="companyCCFamilyRow">
						<td>
							<b><?php echo __($guid, 'CC Family?') ?></b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Should the family be sent a copy of billing emails?') ?></span>
						</td>
						<td class="right">
							<select name="companyCCFamily" id="companyCCFamily" class="standardWidth">
								<option <?php if ($row['companyCCFamily'] == 'N') { echo 'selected'; } ?> value="N" /> <?php echo __($guid, 'No') ?>
								<option <?php if ($row['companyCCFamily'] == 'Y') { echo 'selected'; } ?> value="Y" /> <?php echo __($guid, 'Yes') ?>
							</select>
						</td>
					</tr>
					<tr id="companyPhoneRow">
						<td>
							<b><?php echo __($guid, 'Company Phone') ?></b><br/>
						</td>
						<td class="right">
							<input name="companyPhone" id="companyPhone" maxlength=20 value="<?php echo $row['companyPhone'] ?>" type="text" class="standardWidth">
						</td>
					</tr>
					<?php
                    try {
                        $dataCat = array();
                        $sqlCat = "SELECT * FROM gibbonFinanceFeeCategory WHERE active='Y' AND NOT gibbonFinanceFeeCategoryID=1 ORDER BY name";
                        $resultCat = $connection2->prepare($sqlCat);
                        $resultCat->execute($dataCat);
                    } catch (PDOException $e) {
                    }
					if ($resultCat->rowCount() < 1) {
						echo '<input type="hidden" name="companyAll" value="Y" class="companyAll"/>';
					} else {
						?>
						<tr id="companyAllRow">
							<td>
								<b><?php echo __($guid, 'Company All?') ?></b><br/>
								<span class="emphasis small"><?php echo __($guid, 'Should all items be billed to the specified company, or just some?') ?></span>
							</td>
							<td class="right">
								<input type="radio" name="companyAll" value="Y" class="companyAll" <?php if ($row['companyAll'] == 'Y' or $row['companyAll'] == '') { echo 'checked'; } ?> /> <?php echo __($guid, 'All') ?>
								<input type="radio" name="companyAll" value="N" class="companyAll" <?php if ($row['companyAll'] == 'N') { echo 'checked'; } ?> /> <?php echo __($guid, 'Selected') ?>
							</td>
						</tr>
						<tr id="companyCategoriesRow">
							<td>
								<b><?php echo __($guid, 'Company Fee Categories') ?></b><br/>
								<span class="emphasis small"><?php echo __($guid, 'If the specified company is not paying all fees, which categories are they paying?') ?></span>
							</td>
							<td class="right">
								<?php
                                while ($rowCat = $resultCat->fetch()) {
                                    $checked = '';
                                    if (strpos($row['gibbonFinanceFeeCategoryIDList'], $rowCat['gibbonFinanceFeeCategoryID']) !== false) {
                                        $checked = 'checked';
                                    }
                                    echo $rowCat['name']." <input $checked type='checkbox' name='gibbonFinanceFeeCategoryIDList[]' value='".$rowCat['gibbonFinanceFeeCategoryID']."'/><br/>";
                                }
								$checked = '';
								if (strpos($row['gibbonFinanceFeeCategoryIDList'], '0001') !== false) {
									$checked = 'checked';
								}
								echo "Other <input $checked type='checkbox' name='gibbonFinanceFeeCategoryIDList[]' value='0001'/><br/>"; ?>
							</td>
						</tr>
						<?php
            		}
					$requiredDocuments = getSettingByScope($connection2, 'Application Form', 'requiredDocuments');
					$requiredDocumentsCompulsory = getSettingByScope($connection2, 'Application Form', 'requiredDocumentsCompulsory');
					$count = 0;
					if ($requiredDocuments != '' and $requiredDocuments != false) {
						?>
						<tr class='break'>
							<td colspan=2>
								<h3><?php echo __($guid, 'Supporting Documents') ?></h3>
							</td>
						</tr>
						<?php

                        //Get list of acceptable file extensions
                        try {
                            $dataExt = array();
                            $sqlExt = 'SELECT * FROM gibbonFileExtension';
                            $resultExt = $connection2->prepare($sqlExt);
                            $resultExt->execute($dataExt);
                        } catch (PDOException $e) {
                        }
						$ext = '';
						while ($rowExt = $resultExt->fetch()) {
							$ext = $ext."'.".$rowExt['extension']."',";
						}

						$requiredDocumentsList = explode(',', $requiredDocuments);
						foreach ($requiredDocumentsList as $document) {
							try {
								$dataFile = array('gibbonApplicationFormID' => $gibbonApplicationFormID, 'name' => $document);
								$sqlFile = 'SELECT * FROM gibbonApplicationFormFile WHERE gibbonApplicationFormID=:gibbonApplicationFormID AND name=:name ORDER BY name';
								$resultFile = $connection2->prepare($sqlFile);
								$resultFile->execute($dataFile);
							} catch (PDOException $e) {
							}
							if ($resultFile->rowCount() == 0) {
								?>
								<tr>
									<td>
										<b><?php echo $document;
										if ($requiredDocumentsCompulsory == 'Y') {
											echo ' *';
										}
										?></b><br/>
									</td>
									<td class="right">
										<?php
                                        echo "<input type='file' name='file$count' id='file$count'><br/>";
										echo "<input type='hidden' name='fileName$count' id='filefileName$count' value='$document'>";
										if ($requiredDocumentsCompulsory == 'Y') {
											echo "<script type='text/javascript'>";
											echo "var file$count=new LiveValidation('file$count');";
											echo "file$count.add( Validate.Inclusion, { within: [".$ext."], failureMessage: 'Illegal file type!', partialMatch: true, caseSensitive: false } );";
											echo "file$count.add(Validate.Presence);";
											echo '</script>';
										}
										++$count;
										?>
									</td>
								</tr>
								<?php

							} elseif ($resultFile->rowCount() == 1) {
								$rowFile = $resultFile->fetch();
								?>
								<tr>
									<td>
										<?php echo '<b>'.$rowFile['name'].'</b><br/>' ?>
										<span class="emphasis small"><?php echo __($guid, 'This value cannot be changed.') ?></span>
									</td>
									<td class="right">
										<?php
                                        echo "<a target='_blank' href='".$_SESSION[$guid]['absoluteURL'].'/'.$rowFile['path']."'>Download</a>";
                       					 ?>
									</td>
								</tr>
								<?php

							} else {
								//Error
							}
						}
					}
					if ($count > 0) {
						?>
						<tr>
							<td colspan=2>
								<?php echo getMaxUpload($guid); ?>
								<input type="hidden" name="fileCount" value="<?php echo $count ?>">
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
							<b><?php echo __($guid, 'How Did You Hear About Us?') ?></b><br/>
						</td>
						<td class="right">
							<?php
                            $howDidYouHearList = getSettingByScope($connection2, 'Application Form', 'howDidYouHear');
							if ($howDidYouHearList == '') {
								echo "<input name='howDidYouHear' id='howDidYouHear' maxlength=30 value='".$row['howDidYouHear']."' type='text' style='width: 300px'>";
							} else {
								echo "<select name='howDidYouHear' id='howDidYouHear' style='width: 302px'>";
								echo "<option value=''></option>";
								$howDidYouHears = explode(',', $howDidYouHearList);
								foreach ($howDidYouHears as $howDidYouHear) {
									$selected = '';
									if (trim($howDidYouHear) == $row['howDidYouHear']) {
										$selected = 'selected';
									}
									echo "<option $selected value='".trim($howDidYouHear)."'>".trim($howDidYouHear).'</option>';
								}
								echo '</select>';
							}
							?>
						</td>
					</tr>
					<tr id="tellUsMoreRow">
						<td>
							<b><?php echo __($guid, 'Tell Us More') ?></b><br/>
							<span class="emphasis small"><?php echo __($guid, 'The name of a person or link to a website.') ?></span>
						</td>
						<td class="right">
							<input name="howDidYouHearMore" id="howDidYouHearMore" maxlength=255 value="<?php echo $row['howDidYouHearMore'] ?>" type="text" class="standardWidth">
						</td>
					</tr>
					<?php
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
					if ($proceed == true) {
						?>
						<tr>
							<td>
								<span class="emphasis small">* <?php echo __($guid, 'denotes a required field'); ?></span>
							</td>
							<td class="right">
								<input type="hidden" name="address" value="<?php echo $_SESSION[$guid]['address'] ?>">
								<input name="gibbonSchoolYearID" id="gibbonSchoolYearID" value="<?php echo $gibbonSchoolYearID ?>" type="hidden">
								<input type="hidden" name="gibbonApplicationFormID" value="<?php echo $row['gibbonApplicationFormID'] ?>">
								<input type="submit" value="<?php echo __($guid, 'Submit'); ?>">
							</td>
						</tr>
						<?php
					}
					?>
				</table>
			</form>
			<?php

        }
    }
}
?>
