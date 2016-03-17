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


if (isActionAccessible($guid, $connection2, "/modules/User Admin/applicationForm_manage_edit.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/User Admin/applicationForm_manage.php&gibbonSchoolYearID=" . $_GET["gibbonSchoolYearID"] . "'>" . __($guid, 'Manage Application Forms') . "</a> > </div><div class='trailEnd'>" . __($guid, 'Edit Form') . "</div>" ;
	print "</div>" ;

	//Check if school year specified
	$gibbonApplicationFormID=$_GET["gibbonApplicationFormID"];
	$gibbonSchoolYearID=$_GET["gibbonSchoolYearID"] ;
	$search=$_GET["search"] ;
	if ($gibbonApplicationFormID=="" OR $gibbonSchoolYearID=="") {
		print "<div class='error'>" ;
			print __($guid, "You have not specified one or more required parameters.") ;
		print "</div>" ;
	}
	else {
		try {
			$data=array("gibbonApplicationFormID"=>$gibbonApplicationFormID);
			$sql="SELECT *, gibbonApplicationForm.status AS 'applicationStatus', gibbonPayment.status AS 'paymentStatus' FROM gibbonApplicationForm LEFT JOIN gibbonPayment ON (gibbonApplicationForm.gibbonPaymentID=gibbonPayment.gibbonPaymentID AND foreignTable='gibbonApplicationForm') WHERE gibbonApplicationFormID=:gibbonApplicationFormID" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) {
			print "<div class='error'>" . $e->getMessage() . "</div>" ;
		}

		if ($result->rowCount()!=1) {
			print "<div class='error'>" ;
				print __($guid, "The specified record does not exist.") ;
			print "</div>" ;
		}
		else {
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
				else if ($updateReturn=="success1") {
					$updateReturnMessage=__($guid, "Your request was completed successfully, but status could not be updated.") ;
				}
				else if ($updateReturn=="success0") {
					$updateReturnMessage=__($guid, "Your request was completed successfully.") ;
					$class="success" ;
				}
				print "<div class='$class'>" ;
					print $updateReturnMessage;
				print "</div>" ;
			}

			//Let's go!
			$row=$result->fetch() ;
			$proceed=TRUE ;
			
			print "<div class='linkTop'>" ;
				if ($search!="") {
					print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/User Admin/applicationForm_manage.php&gibbonSchoolYearID=$gibbonSchoolYearID&search=$search'>" . __($guid, 'Back to Search Results') . "</a> | " ;
				}
				print "<a target='_blank' href='" . $_SESSION[$guid]["absoluteURL"] . "/report.php?q=/modules/" . $_SESSION[$guid]["module"] . "/applicationForm_manage_edit_print.php&gibbonApplicationFormID=$gibbonApplicationFormID'>" .  __($guid, 'Print') . "<img style='margin-left: 5px' title='" . __($guid, 'Print') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/print.png'/></a>" ;
			print "</div>" ;
			?>
			<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/applicationForm_manage_editProcess.php?search=$search" ?>" enctype="multipart/form-data">
				<table class='smallIntBorder' cellspacing='0' style="width: 100%">
					<tr class='break'>
						<td colspan=2>
							<h3><?php print __($guid, 'For Office Use') ?></h3>
						</td>
					</tr>
					<tr>
						<td style='width: 275px'>
							<b><?php print __($guid, 'Application ID') ?> *</b><br/>
							<span style="font-size: 90%"><i><?php print __($guid, 'This value cannot be changed.') ?></i></span>
						</td>
						<td class="right">
							<input readonly name="gibbonApplicationFormID" id="gibbonApplicationFormID" value="<?php print htmlPrep($row["gibbonApplicationFormID"]) ?>" type="text" style="width: 300px">
						</td>
					</tr>
					<tr>
						<td>
							<b><?php print __($guid, 'Priority') ?> *</b><br/>
							<span style="font-size: 90%"><i><?php print __($guid, 'Higher priority applicants appear first in list of applications.') ?></i></span>
						</td>
						<td class="right">
							<select name="priority" id="priority" style="width: 302px">
								<option <?php if ($row["priority"]=="9") { print "selected" ; } ?> value="9">9</option>
								<option <?php if ($row["priority"]=="8") { print "selected" ; } ?> value="8">8</option>
								<option <?php if ($row["priority"]=="7") { print "selected" ; } ?> value="7">7</option>
								<option <?php if ($row["priority"]=="6") { print "selected" ; } ?> value="6">6</option>
								<option <?php if ($row["priority"]=="5") { print "selected" ; } ?> value="5">5</option>
								<option <?php if ($row["priority"]=="4") { print "selected" ; } ?> value="4">4</option>
								<option <?php if ($row["priority"]=="3") { print "selected" ; } ?> value="3">3</option>
								<option <?php if ($row["priority"]=="2") { print "selected" ; } ?> value="2">2</option>
								<option <?php if ($row["priority"]=="1") { print "selected" ; } ?> value="1">1</option>
								<option <?php if ($row["priority"]=="0") { print "selected" ; } ?> value="0">0</option>
								<option <?php if ($row["priority"]=="-1") { print "selected" ; } ?> value="-1">-1</option>
								<option <?php if ($row["priority"]=="-2") { print "selected" ; } ?> value="-2">-2</option>
								<option <?php if ($row["priority"]=="-3") { print "selected" ; } ?> value="-3">-3</option>
								<option <?php if ($row["priority"]=="-4") { print "selected" ; } ?> value="-4">-4</option>
								<option <?php if ($row["priority"]=="-5") { print "selected" ; } ?> value="-5">-5</option>
								<option <?php if ($row["priority"]=="-6") { print "selected" ; } ?> value="-6">-6</option>
								<option <?php if ($row["priority"]=="-7") { print "selected" ; } ?> value="-7">-7</option>
								<option <?php if ($row["priority"]=="-8") { print "selected" ; } ?> value="-8">-8</option>
								<option <?php if ($row["priority"]=="-9") { print "selected" ; } ?> value="-9">-9</option>
							</select>
						</td>
					</tr>
					<?php
					if ($row["applicationStatus"]!="Accepted") {
						?>
						<tr>
							<td>
								<b><?php print __($guid, 'Status') ?> *</b><br/>
								<span style="font-size: 90%"><i><?php print __($guid, 'Manually set status. "Approved" not permitted.') ?></i></span>
							</td>
							<td class="right">
								<select name="status" id="status" style="width: 302px">
									<option <?php if ($row["applicationStatus"]=="Pending") { print "selected" ; } ?> value="Pending"><?php print __($guid, 'Pending') ?></option>
									<option <?php if ($row["applicationStatus"]=="Waiting List") { print "selected" ; } ?> value="Waiting List"><?php print __($guid, 'Waiting List') ?></option>
									<option <?php if ($row["applicationStatus"]=="Rejected") { print "selected" ; } ?> value="Rejected"><?php print __($guid, 'Rejected') ?></option>
									<option <?php if ($row["applicationStatus"]=="Withdrawn") { print "selected" ; } ?> value="Withdrawn"><?php print __($guid, 'Withdrawn') ?></option>
								</select>
							</td>
						</tr>
						<?php
					}
					else {
						?>
						<tr>
							<td>
								<b><?php print __($guid, 'Status') ?> *</b><br/>
								<span style="font-size: 90%"><i><?php print __($guid, 'This value cannot be changed.') ?></i></span>
							</td>
							<td class="right">
								<input readonly name="status" id="status" maxlength=20 value="<?php print htmlPrep($row["applicationStatus"]) ?>" type="text" style="width: 300px">
							</td>
						</tr>
						<?php
					}
					$milestonesMasterRaw=getSettingByScope($connection2, "Application Form", "milestones") ;
					if ($milestonesMasterRaw!="") {
						?>
						<tr>
							<td>
								<b><?php print __($guid, 'Milestones') ?></b><br/>
							</td>
							<td class="right">
								<?php
								$milestones=explode(",", $row["milestones"]) ;
								$milestonesMaster=explode(",", $milestonesMasterRaw) ;
								foreach ($milestonesMaster as $milestoneMaster) {
									$checked="" ;
									foreach ($milestones as $milestone) {
										if (trim($milestoneMaster)==trim($milestone)) {
											$checked="checked" ;
										}
									}
									print trim($milestoneMaster) . " <input $checked type='checkbox' name='milestone_" .  preg_replace('/\s+/', '', $milestoneMaster) . "'></br>" ;
								}

								?>
							</td>
						</tr>
						<?php
					}
					?>
					<tr>
						<td>
							<b><?php print __($guid, 'Start Date') ?></b><br/>
							<span style="font-size: 90%"><i><?php print __($guid, 'Student\'s intended first day at school.') ?><br/>Format <?php if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; } ?></i></span>
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
							<b><?php print __($guid, 'Year of Entry') ?> *</b><br/>
							<span style="font-size: 90%"><i><?php print __($guid, 'When will the student join?') ?></i></span>
						</td>
						<td class="right">
							<select name="gibbonSchoolYearIDEntry" id="gibbonSchoolYearIDEntry" style="width: 302px">
								<?php
								print "<option value='Please select...'>" . __($guid, 'Please select...') . "</option>" ;
								try {
									$dataSelect=array();
									$sqlSelect="SELECT * FROM gibbonSchoolYear WHERE (status='Current' OR status='Upcoming') ORDER BY sequenceNumber" ;
									$resultSelect=$connection2->prepare($sqlSelect);
									$resultSelect->execute($dataSelect);
								}
								catch(PDOException $e) {
									print "<div class='error'>" . $e->getMessage() . "</div>" ;
								}
								while ($rowSelect=$resultSelect->fetch()) {
									$selected="" ;
									if ($row["gibbonSchoolYearIDEntry"]==$rowSelect["gibbonSchoolYearID"]) {
										$selected="selected" ;
									}
									print "<option $selected value='" . $rowSelect["gibbonSchoolYearID"] . "'>" . htmlPrep($rowSelect["name"]) . "</option>" ;
								}
								?>
							</select>
							<script type="text/javascript">
								var gibbonSchoolYearIDEntry=new LiveValidation('gibbonSchoolYearIDEntry');
								gibbonSchoolYearIDEntry.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print __($guid, 'Select something!') ?>"});
							</script>
						</td>
					</tr>
					<tr>
						<td>
							<b><?php print __($guid, 'Year Group at Entry') ?> *</b><br/>
							<span style="font-size: 90%"><i><?php print __($guid, 'Which year level will student enter.') ?></i></span>
						</td>
						<td class="right">
							<select name="gibbonYearGroupIDEntry" id="gibbonYearGroupIDEntry" style="width: 302px">
								<?php
								print "<option value='Please select...'>" . __($guid, 'Please select...') . "</option>" ;
								try {
									$dataSelect=array();
									$sqlSelect="SELECT * FROM gibbonYearGroup ORDER BY sequenceNumber" ;
									$resultSelect=$connection2->prepare($sqlSelect);
									$resultSelect->execute($dataSelect);
								}
								catch(PDOException $e) {
									print "<div class='error'>" . $e->getMessage() . "</div>" ;
								}

								while ($rowSelect=$resultSelect->fetch()) {
									$selected="" ;
									if ($row["gibbonYearGroupIDEntry"]==$rowSelect["gibbonYearGroupID"]) {
										$selected="selected" ;
									}
									print "<option $selected value='" . $rowSelect["gibbonYearGroupID"] . "'>" . htmlPrep(__($guid, $rowSelect["name"])) . "</option>" ;
								}
								?>
							</select>
							<script type="text/javascript">
								var gibbonYearGroupIDEntry=new LiveValidation('gibbonYearGroupIDEntry');
								gibbonYearGroupIDEntry.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print __($guid, 'Select something!') ?>"});
							</script>
						</td>
					</tr>
					<?php
					$dayTypeOptions=getSettingByScope($connection2, 'User Admin', 'dayTypeOptions') ;
					if ($dayTypeOptions!="") {
						?>
						<tr>
							<td>
								<b><?php print __($guid, 'Day Type') ?></b><br/>
								<span style="font-size: 90%"><i><?php print getSettingByScope($connection2, 'User Admin', 'dayTypeText') ; ?></i></span>
							</td>
							<td class="right">
								<select name="dayType" id="dayType" style="width: 302px">
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
					?>
					<tr>
						<td>
							<b><?php print __($guid, 'Roll Group at Entry') ?></b><br/>
							<span style="font-size: 90%"><?php print __($guid, 'If set, the student will automatically be enroled on Accept.') ?></span>
						</td>
						<td class="right">
							<select name="gibbonRollGroupID" id="gibbonRollGroupID" style="width: 302px">
								<?php
								print "<option value=''></option>" ;
								try {
									$dataSelect=array();
									$sqlSelect="SELECT gibbonRollGroupID, name, gibbonSchoolYearID FROM gibbonRollGroup ORDER BY gibbonSchoolYearID, name" ;
									$resultSelect=$connection2->prepare($sqlSelect);
									$resultSelect->execute($dataSelect);
								}
								catch(PDOException $e) {
									print "<div class='error'>" . $e->getMessage() . "</div>" ;
								}
								while ($rowSelect=$resultSelect->fetch()) {
									$selected="" ;
									if ($row["gibbonRollGroupID"]==$rowSelect["gibbonRollGroupID"]) {
										$selected="selected" ;
									}
									print "<option $selected class='" . $rowSelect["gibbonSchoolYearID"] . "' value='" . $rowSelect["gibbonRollGroupID"] . "'>" . htmlPrep($rowSelect["name"]) . "</option>" ;
								}
								?>
							</select>
							<script type="text/javascript">
								$("#gibbonRollGroupID").chainedTo("#gibbonSchoolYearIDEntry");
							</script>
						</td>
					</tr>

					<?php
					$currency=getSettingByScope($connection2, "System", "currency") ;
					$applicationFee=getSettingByScope($connection2, "Application Form", "applicationFee") ;
					$enablePayments=getSettingByScope($connection2, "System", "enablePayments") ;
					$paypalAPIUsername=getSettingByScope($connection2, "System", "paypalAPIUsername") ;
					$paypalAPIPassword=getSettingByScope($connection2, "System", "paypalAPIPassword") ;
					$paypalAPISignature=getSettingByScope($connection2, "System", "paypalAPISignature") ;
					$ccPayment=false ;

					if ($applicationFee>0 AND is_numeric($applicationFee)) {
						?>
						<tr>
							<td>
								<b><?php print __($guid, 'Payment') ?> *</b><br/>
								<span style="font-size: 90%"><i><?php print sprintf(__($guid, 'Has payment (%1$s %2$s) been made for this application.'), $currency, $applicationFee) ?></i></span>
							</td>
							<td class="right">
								<select name="paymentMade" id="paymentMade" style="width: 302px">
									<option <?php if ($row["paymentMade"]=="N") { print "selected" ; } ?> value='N'>N</option>
									<option <?php if ($row["paymentMade"]=="Y") { print "selected" ; } ?> value='Y'>Y</option>
									<option <?php if ($row["paymentMade"]=="Exemption") { print "selected" ; } ?> value='Exemption'>Exemption</option>
								</select>
							</td>
						</tr>
						<?php
						if ($row["paymentToken"]!="" OR $row["paymentPayerID"]!="" OR $row["paymentTransactionID"]!="" OR $row["paymentReceiptID"]!="") {
							?>
							<tr>
								<td style='text-align: right' colspan=2>
									<span style="font-size: 90%"><i>
										<?php
											if ($row["paymentToken"]!="") {
												print __($guid, "Payment Token:") . " " . $row["paymentToken"] . "<br/>" ;
											}
											if ($row["paymentPayerID"]!="") {
												print __($guid, "Payment Payer ID:") . " " . $row["paymentPayerID"] . "<br/>" ;
											}
											if ($row["paymentTransactionID"]!="") {
												print __($guid, "Payment Transaction ID:") . " " . $row["paymentTransactionID"] . "<br/>" ;
											}
											if ($row["paymentReceiptID"]!="") {
												print __($guid, "Payment Receipt ID:") . " " . $row["paymentReceiptID"] . "<br/>" ;
											}
										?>
									</i></span>
								</td>
							</tr>
							<?php
						}
					}

					?>
					<tr>
						<td colspan=2 style='padding-top: 15px'>
							<b><?php print __($guid, 'Notes') ?></b><br/>
							<textarea name="notes" id="notes" rows=5 style="width:738px; margin: 5px 0px 0px 0px"><?php print htmlPrep($row["notes"]) ?></textarea>
						</td>
					</tr>

					<tr class='break'>
						<td colspan=2>
							<h3><?php print __($guid, 'Student') ?></h3>
						</td>
					</tr>

					<tr>
						<td colspan=2>
							<h4><?php print __($guid, 'Student Personal Data') ?></h4>
						</td>
					</tr>
					<tr>
						<td>
							<b><?php print __($guid, 'Surname') ?> *</b><br/>
							<span style="font-size: 90%"><i><?php print __($guid, 'Family name as shown in ID documents.') ?></i></span>
						</td>
						<td class="right">
							<input name="surname" id="surname" maxlength=30 value="<?php print $row["surname"] ?>" type="text" style="width: 300px">
							<script type="text/javascript">
								var surname=new LiveValidation('surname');
								surname.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<tr>
						<td>
							<b><?php print __($guid, 'First Name') ?> *</b><br/>
							<span style="font-size: 90%"><i><?php print __($guid, 'First name as shown in ID documents.') ?></i></span>
						</td>
						<td class="right">
							<input name="firstName" id="firstName" maxlength=30 value="<?php print $row["firstName"] ?>" type="text" style="width: 300px">
							<script type="text/javascript">
								var firstName=new LiveValidation('firstName');
								firstName.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<tr>
						<td>
							<b><?php print __($guid, 'Preferred Name') ?> *</b><br/>
							<span style="font-size: 90%"><i><?php print __($guid, 'Most common name, alias, nickname, etc.') ?></i></span>
						</td>
						<td class="right">
							<input name="preferredName" id="preferredName" maxlength=30 value="<?php print $row["preferredName"] ?>" type="text" style="width: 300px">
							<script type="text/javascript">
								var preferredName=new LiveValidation('preferredName');
								preferredName.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<tr>
						<td>
							<b><?php print __($guid, 'Official Name') ?> *</b><br/>
							<span style="font-size: 90%"><i><?php print __($guid, 'Full name as shown in ID documents.') ?></i></span>
						</td>
						<td class="right">
							<input name="officialName" id="officialName" maxlength=150 value="<?php print $row["officialName"] ?>" type="text" style="width: 300px">
							<script type="text/javascript">
								var officialName=new LiveValidation('officialName');
								officialName.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<tr>
						<td>
							<b><?php print __($guid, 'Name In Characters') ?></b><br/>
							<span style="font-size: 90%"><i><?php print __($guid, 'Chinese or other character-based name.') ?></i></span>
						</td>
						<td class="right">
							<input name="nameInCharacters" id="nameInCharacters" maxlength=20 value="<?php print $row["nameInCharacters"] ?>" type="text" style="width: 300px">
						</td>
					</tr>
					<tr>
						<td>
							<b><?php print __($guid, 'Gender') ?> *</b><br/>
						</td>
						<td class="right">
							<select name="gender" id="gender" style="width: 302px">
								<option value="Please select..."><?php print __($guid, 'Please select...') ?></option>
								<option <?php if ($row["gender"]=="F") { print "selected" ; } ?> value="F"><?php print __($guid, 'Female') ?></option>
								<option <?php if ($row["gender"]=="M") { print "selected" ; } ?> value="M"><?php print __($guid, 'Male') ?></option>
							</select>
							<script type="text/javascript">
								var gender=new LiveValidation('gender');
								gender.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print __($guid, 'Select something!') ?>"});
							</script>
						</td>
					</tr>
					<tr>
						<td>
							<b><?php print __($guid, 'Date of Birth') ?> *</b><br/>
							<span style="font-size: 90%"><i><?php print $_SESSION[$guid]["i18n"]["dateFormat"]  ?></i></span>
						</td>
						<td class="right">
							<input name="dob" id="dob" maxlength=10 value="<?php print dateConvertBack($guid, $row["dob"]) ?>" type="text" style="width: 300px">
							<script type="text/javascript">
								var dob=new LiveValidation('dob');
								dob.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]["i18n"]["dateFormatRegEx"]=="") {  print "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i" ; } else { print $_SESSION[$guid]["i18n"]["dateFormatRegEx"] ; } ?>, failureMessage: "Use <?php if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?>." } );
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
							<h4><?php print __($guid, 'Student Background') ?></h4>
						</td>
					</tr>
					<tr>
						<td>
							<b><?php print __($guid, 'Home Language - Primary') ?> *</b><br/>
							<span style="font-size: 90%"><i><?php print __($guid, 'The primary language used in the student\'s home.') ?></i></span>
						</td>
						<td class="right">
							<select name="languageHomePrimary" id="languageHomePrimary" style="width: 302px">
								<?php
								print "<option value='Please select...'>Please select...</option>" ;
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
							<script type="text/javascript">
								var languageHomePrimary=new LiveValidation('languageHomePrimary');
								languageHomePrimary.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print __($guid, 'Select something!') ?>"});
							</script>
						</td>
					</tr>
					<tr>
						<td>
							<b><?php print __($guid, 'Home Language - Secondary') ?></b><br/>
							<span style="font-size: 90%"><i><?php print __($guid, 'The primary language used in the student\'s home.') ?></i></span>
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
							<b><?php print __($guid, 'First Language') ?> *</b><br/>
							<span style="font-size: 90%"><i><?php print __($guid, 'Student\'s native/first/mother language.') ?></i></span>
						</td>
						<td class="right">
							<select name="languageFirst" id="languageFirst" style="width: 302px">
								<?php
								print "<option value='Please select...'>Please select...</option>" ;
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
							<script type="text/javascript">
								var languageFirst=new LiveValidation('languageFirst');
								languageFirst.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print __($guid, 'Select something!') ?>"});
							</script>
						</td>
					</tr>
					<tr>
						<td>
							<b><?php print __($guid, 'Second Language') ?></b><br/>
						</td>
						<td class="right">
							<select name="languageSecond" id="languageSecond" style="width: 302px">
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
							<select name="languageThird" id="languageThird" style="width: 302px">
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
							<select name="countryOfBirth" id="countryOfBirth" style="width: 302px">
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
									if ($row["countryOfBirth"]==$rowSelect["printable_name"]) {
										$selected="selected" ;
									}
									print "<option $selected value='" . $rowSelect["printable_name"] . "'>" . htmlPrep(__($guid, $rowSelect["printable_name"])) . "</option>" ;
								}
								?>
							</select>
						</td>
					</tr>
					<tr>
						<td>
							<b><?php print __($guid, 'Citizenship') ?></b><br/>
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
								?>
							</select>
						</td>
					</tr>
					<tr>
						<td>
							<b><?php print __($guid, 'Citizenship Passport Number') ?></b><br/>
						</td>
						<td class="right">
							<input name="citizenship1Passport" id="citizenship1Passport" maxlength=30 value="<?php print $row["citizenship1Passport"] ?>" type="text" style="width: 300px">
						</td>
					</tr>
					<tr>
						<td>
						<?php
						if ($_SESSION[$guid]["country"]=="") {
							print "<b>" . __($guid, 'National ID Card Number') . "</b><br/>" ;
						}
						else {
							print "<b>" . $_SESSION[$guid]["country"] . " " . __($guid, 'ID Card Number') . "</b><br/>" ;
						}
						?>
					</td>
						<td class="right">
							<input name="nationalIDCardNumber" id="nationalIDCardNumber" maxlength=30 value="<?php print $row["nationalIDCardNumber"] ?>" type="text" style="width: 300px">
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
							print "<span style='font-size: 90%'><i>Format " ; if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; } print ". " . __($guid, 'If relevant.') . "</i></span>" ;
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


					<tr>
						<td colspan=2>
							<h4><?php print __($guid, 'Student Contact') ?></h4>
						</td>
					</tr>
					<tr>
						<td>
							<b><?php print __($guid, 'Email') ?></b><br/>
						</td>
						<td class="right">
							<input name="email" id="email" maxlength=50 value="<?php print $row["email"] ?>" type="text" style="width: 300px">
							<script type="text/javascript">
								var email=new LiveValidation('email');
								email.add(Validate.Email);
							</script>
						</td>
					</tr>
					<?php
					for ($i=1; $i<3; $i++) {
						?>
						<tr>
							<td>
								<b><?php print __($guid, 'Phone') ?> <?php print $i ?></b><br/>
								<span style="font-size: 90%"><i><?php print __($guid, 'Type, country code, number.') ?></i></span>
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
						<td colspan=2>
							<h4><?php print __($guid, 'Student Medical & Development') ?></h4>
						</td>
					</tr>
					<tr>
						<td colspan=2 style='padding-top: 15px'>
							<b><?php print __($guid, 'Medical Information') ?></b><br/>
							<span style="font-size: 90%"><i><?php print __($guid, 'Please indicate any medical conditions.') ?></i></span><br/>
							<textarea name="medicalInformation" id="medicalInformation" rows=5 style="width:738px; margin: 5px 0px 0px 0px"><?php print htmlPrep($row["medicalInformation"]) ?></textarea>
						</td>
					</tr>
					<tr>
						<td colspan=2 style='padding-top: 15px'>
							<b><?php print __($guid, 'Development Information') ?></b><br/>
							<span style="font-size: 90%"><i><?php print __($guid, 'Provide any comments or information concerning your child\'s development that may be relevant to your child’s performance in the classroom or elsewhere? (Incorrect or withheld information may affect continued enrolment).') ?></i></span><br/>
							<textarea name="developmentInformation" id="developmentInformation" rows=5 style="width:738px; margin: 5px 0px 0px 0px"><?php print htmlPrep($row["developmentInformation"]) ?></textarea>
						</td>
					</tr>



					<tr>
						<td colspan=2>
							<h4><?php print __($guid, 'Previous Schools') ?></h4>
							<p><?php print __($guid, 'Please give information on the last two schools attended by the applicant.') ?></p>
						</td>
					</tr>
					<tr>
						<td colspan=2>
							<?php
							print "<table cellspacing='0' style='width: 100%'>" ;
								print "<tr class='head'>" ;
									print "<th>" ;
										print __($guid, "School Name") ;
									print "</th>" ;
									print "<th>" ;
										print __($guid, "Address") ;
									print "</th>" ;
									print "<th>" ;
										print __($guid, "Grades<br/>Attended") ;
									print "</th>" ;
									print "<th>" ;
										print __($guid, "Language of<br/>Instruction") ;
									print "</th>" ;
									print "<th>" ;
										print __($guid, "Joining Date") . "<br/><span style='font-size: 80%'>" ; if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; } print "</span>" ;
									print "</th>" ;
								print "</tr>" ;


								for ($i=1; $i<3; $i++) {
									if ((($i%2)-1)==0) {
										$rowNum="even" ;
									}
									else {
										$rowNum="odd" ;
									}

									print "<tr class=$rowNum>" ;
										print "<td>" ;
											print "<input name='schoolName$i' id='schoolName$i' maxlength=50 value='" . htmlPrep($row["schoolName$i"]) . "' type='text' style='width:120px; float: left'>" ;
										print "</td>" ;
										print "<td>" ;
											print "<input name='schoolAddress$i' id='schoolAddress$i' maxlength=255 value='" . htmlPrep($row["schoolAddress$i"]) . "' type='text' style='width:120px; float: left'>" ;
										print "</td>" ;
										print "<td>" ;
											print "<input name='schoolGrades$i' id='schoolGrades$i' maxlength=20 value='" . htmlPrep($row["schoolGrades$i"]) . "' type='text' style='width:70px; float: left'>" ;
										print "</td>" ;
										print "<td>" ;
											print "<input name='schoolLanguage$i' id='schoolLanguage$i' maxlength=50 value='" . htmlPrep($row["schoolLanguage$i"]) . "' type='text' style='width:100px; float: left'>" ;
											?>
											<script type="text/javascript">
												$(function() {
													var availableTags=[
														<?php
														try {
															$dataAuto=array();
															$sqlAuto="SELECT DISTINCT schoolLanguage" . $i . " FROM gibbonApplicationForm ORDER BY schoolLanguage" . $i ;
															$resultAuto=$connection2->prepare($sqlAuto);
															$resultAuto->execute($dataAuto);
														}
														catch(PDOException $e) {
															print "<div class='error'>" . $e->getMessage() . "</div>" ;
														}
														while ($rowAuto=$resultAuto->fetch()) {
															print "\"" . $rowAuto["schoolLanguage" . $i] . "\", " ;
														}
														?>
													];
													$( "#schoolLanguage<?php print $i ?>" ).autocomplete({source: availableTags});
												});
											</script>
											<?php
										print "</td>" ;
										print "<td>" ;
											?>
											<input name="<?php print "schoolDate$i" ?>" id="<?php print "schoolDate$i" ?>" maxlength=10 value="<?php print dateConvertBack($guid, $row["schoolDate$i"]) ?>" type="text" style="width:90px; float: left">
											<script type="text/javascript">
												$(function() {
													$( "#<?php print "schoolDate$i" ?>" ).datepicker();
												});
											</script>
											<?php
										print "</td>" ;
									print "</tr>" ;
								}
							print "</table>" ;
							?>
						</td>
					</tr>

					<?php
					//CUSTOM FIELDS FOR STUDENT
					$fields=unserialize($row["fields"]) ;
					$resultFields=getCustomFields($connection2, $guid, TRUE, FALSE, FALSE, FALSE, TRUE, NULL) ;
					if ($resultFields->rowCount()>0) {
						?>
						<tr>
							<td colspan=2>
								<h4><?php print __($guid, 'Other Information') ?></h4>
							</td>
						</tr>
						<?php
						while ($rowFields=$resultFields->fetch()) {
							print renderCustomFieldRow($connection2, $guid, $rowFields, $fields[$rowFields["gibbonPersonFieldID"]]) ;
						}
					}

					if ($row["gibbonFamilyID"]=="") {
						?>
						<input type="hidden" name="gibbonFamily" value="FALSE">

						<tr class='break'>
							<td colspan=2>
								<h3>
									<?php print __($guid, 'Home Address') ?>
								</h3>
							</td>
						</tr>
						<tr>
							<td colspan=2>
								<p>
									<?php print __($guid, 'This address will be used for all members of the family. If an individual within the family needs a different address, this can be set through Data Updater after admission.') ?>
								</p>
							</td>
						</tr>
						<tr>
							<td>
								<b><?php print __($guid, 'Home Address') ?> *</b><br/>
								<span style="font-size: 90%"><i><?php print __($guid, 'Unit, Building, Street') ?></i></span>
							</td>
							<td class="right">
								<input name="homeAddress" id="homeAddress" maxlength=255 value="<?php print $row["homeAddress"] ?>" type="text" style="width: 300px">
								<script type="text/javascript">
									var homeAddress=new LiveValidation('homeAddress');
									homeAddress.add(Validate.Presence);
								</script>
							</td>
						</tr>
						<tr>
							<td>
								<b><?php print __($guid, 'Home Address') ?> (District) *</b><br/>
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
							<script type="text/javascript">
								var homeAddressDistrict=new LiveValidation('homeAddressDistrict');
								homeAddressDistrict.add(Validate.Presence);
							</script>
						</tr>
						<tr>
							<td>
								<b><?php print __($guid, 'Home Address (Country)') ?> *</b><br/>
							</td>
							<td class="right">
								<select name="homeAddressCountry" id="homeAddressCountry" style="width: 302px">
									<?php
									try {
										$dataSelect=array();
										$sqlSelect="SELECT printable_name FROM gibbonCountry ORDER BY printable_name" ;
										$resultSelect=$connection2->prepare($sqlSelect);
										$resultSelect->execute($dataSelect);
									}
									catch(PDOException $e) { }
									print "<option value='Please select...'>" . __($guid, 'Please select...') . "</option>" ;
									while ($rowSelect=$resultSelect->fetch()) {
										$selected="" ;
										if ($rowSelect["printable_name"]==$row["homeAddressCountry"]) {
											$selected="selected" ;
										}
										print "<option $selected value='" . $rowSelect["printable_name"] . "'>" . htmlPrep(__($guid, $rowSelect["printable_name"])) . "</option>" ;
									}
									?>
								</select>
								<script type="text/javascript">
									var homeAddressCountry=new LiveValidation('homeAddressCountry');
									homeAddressCountry.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print __($guid, 'Select something!') ?>"});
								</script>
							</td>
						</tr>
						<?php

						if ($row["parent1gibbonPersonID"]!="") {
							$start=2 ;
							?>
							<tr class='break'>
								<td colspan=2>
									<h3>
										<?php print __($guid, 'Parent/Guardian 1') ?>
										<?php
										if ($i==1) {
											print "<span style='font-size: 75%'></span>" ;
										}
										?>
									</h3>
								</td>
							</tr>
							<tr>
								<td colspan=2>
									<p>
										<?php print __($guid, 'The parent is already a Gibbon user, and so their details cannot be edited in this view.') ?>
									</p>
								</td>
							</tr>
							<tr>
								<td>
									<b><?php print __($guid, 'Surname') ?></b><br/>
									<span style="font-size: 90%"><i><?php print __($guid, 'Family name as shown in ID documents.') ?></i></span>
								</td>
								<td class="right">
									<input readonly name='parent1surname' maxlength=30 value="<?php print $row["parent1surname"] ?>" type="text" style="width: 300px">
								</td>
							</tr>
							<tr>
								<td>
									<b><?php print __($guid, 'Preferred Name') ?></b><br/>
									<span style="font-size: 90%"><i><?php print __($guid, 'Most common name, alias, nickname, etc.') ?></i></span>
								</td>
								<td class="right">
									<input readonly name='parent1preferredName' maxlength=30 value="<?php print $row["parent1preferredName"] ?>" type="text" style="width: 300px">
								</td>
							</tr>

							<tr>
								<td>
									<b><?php print __($guid, 'Relationship') ?> *</b><br/>
								</td>
								<td class="right">
									<select name="parent1relationship" id="parent1relationship" style="width: 302px">
										<option value="Please select..."><?php print __($guid, 'Please select...') ?></option>
										<option <?php if ($row["parent1relationship"]=="Mother") { print "selected" ; } ?> value="Mother"><?php print __($guid, 'Mother') ?></option>
										<option <?php if ($row["parent1relationship"]=="Father") { print "selected" ; } ?> value="Father"><?php print __($guid, 'Father') ?></option>
										<option <?php if ($row["parent1relationship"]=="Step-Mother") { print "selected" ; } ?> value="Step-Mother"><?php print __($guid, 'Step-Mother') ?></option>
										<option <?php if ($row["parent1relationship"]=="Step-Father") { print "selected" ; } ?> value="Step-Father"><?php print __($guid, 'Step-Father') ?></option>
										<option <?php if ($row["parent1relationship"]=="Adoptive Parent") { print "selected" ; } ?> value="Adoptive Parent"><?php print __($guid, 'Adoptive Parent') ?></option>
										<option <?php if ($row["parent1relationship"]=="Guardian") { print "selected" ; } ?> value="Guardian"><?php print __($guid, 'Guardian') ?></option>
										<option <?php if ($row["parent1relationship"]=="Grandmother") { print "selected" ; } ?> value="Grandmother"><?php print __($guid, 'Grandmother') ?></option>
										<option <?php if ($row["parent1relationship"]=="Grandfather") { print "selected" ; } ?> value="Grandfather"><?php print __($guid, 'Grandfather') ?></option>
										<option <?php if ($row["parent1relationship"]=="Aunt") { print "selected" ; } ?> value="Aunt"><?php print __($guid, 'Aunt') ?></option>
										<option <?php if ($row["parent1relationship"]=="Uncle") { print "selected" ; } ?> value="Uncle"><?php print __($guid, 'Uncle') ?></option>
										<option <?php if ($row["parent1relationship"]=="Nanny/Helper") { print "selected" ; } ?> value="Nanny/Helper"><?php print __($guid, 'Nanny/Helper') ?></option>
										<option <?php if ($row["parent1relationship"]=="Other") { print "selected" ; } ?> value="Other"><?php print __($guid, 'Other') ?></option>
									</select>
									<script type="text/javascript">
										var parent1relationship=new LiveValidation('parent1relationship');
										parent1relationship.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print __($guid, 'Select something!') ?>"});
									</script>
								</td>
							</tr>
							<input name='parent1gibbonPersonID' value="<?php print $row["parent1gibbonPersonID"] ?>" type="hidden">
							<?php
								//CUSTOM FIELDS FOR PARENT 1 WITH FAMILY
								$parent1fields=unserialize($row["parent1fields"]) ;
								$resultFields=getCustomFields($connection2, $guid, FALSE, FALSE, TRUE, FALSE, TRUE, NULL) ;
								if ($resultFields->rowCount()>0) {
									while ($rowFields=$resultFields->fetch()) {
										print renderCustomFieldRow($connection2, $guid, $rowFields, $parent1fields[$rowFields["gibbonPersonFieldID"]], "parent1") ;
									}
								}
							?>
							<?php
						}
						else {
							$start=1 ;
						}
						for ($i=$start;$i<3;$i++) {
							?>
							<tr class='break'>
								<td colspan=2>
									<h3>
										<?php print __($guid, 'Parent/Guardian') ?> <?php print $i ?>
										<?php
										if ($i==1) {
											print "<span style='font-size: 75%'>" . __($guid, '(e.g. mother)') . "</span>" ;
										}
										else if ($i==2 AND $row["parent1gibbonPersonID"]=="") {
											print "<span style='font-size: 75%'>" .  __($guid, '(e.g. father)') . "</span>" ;
										}
										?>
									</h3>
								</td>
							</tr>
							<tr>
								<td colspan=2>
									<h4><?php print sprintf(__($guid, 'Parent/Guardian %1$s Personal Data'), $i) ?></h4>
								</td>
							</tr>
							<tr>
								<td>
									<b><?php print __($guid, 'Title') ?><?php if ($i==1) { print " *" ;}?></b><br/>
									<span style="font-size: 90%"><i></i></span>
								</td>
								<td class="right">
									<select style="width: 302px" id="<?php print "parent$i" ?>title" name="<?php print "parent$i" ?>title">
										<?php
										if ($i==1) {
											?>
											<option value="Please select..."><?php print __($guid, 'Please select...') ?></option>
										<?php
										}
										else {
											?>
											<option value=""></option>
											<?php
										}
										?>
										<option <?php if ($row["parent$i" . "title"]=="Ms.") { print "selected" ; } ?> value="Ms."><?php print __($guid, 'Ms.') ?></option>
										<option <?php if ($row["parent$i" . "title"]=="Miss") { print "selected" ; } ?> value="Miss"><?php print __($guid, 'Miss') ?></option>
										<option <?php if ($row["parent$i" . "title"]=="Mr.") { print "selected" ; } ?> value="Mr."><?php print __($guid, 'Mr.') ?></option>
										<option <?php if ($row["parent$i" . "title"]=="Mrs.") { print "selected" ; } ?> value="Mrs."><?php print __($guid, 'Mrs.') ?></option>
										<option <?php if ($row["parent$i" . "title"]=="Dr.") { print "selected" ; } ?> value="Dr."><?php print __($guid, 'Dr.') ?></option>
									</select>

									<?php
									if ($i==1) {
										?>
										<script type="text/javascript">
											var <?php print "parent$i" ?>title=new LiveValidation('<?php print "parent$i" ?>title');
											<?php print "parent$i" ?>title.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print __($guid, 'Select something!') ?>"});
										</script>
										 <?php
									}
									?>
								</td>
							</tr>
							<tr>
								<td>
									<b><?php print __($guid, 'Surname') ?><?php if ($i==1) { print " *" ;}?></b><br/>
									<span style="font-size: 90%"><i><?php print __($guid, 'Family name as shown in ID documents.') ?></i></span>
								</td>
								<td class="right">
									<input name="<?php print "parent$i" ?>surname" id="<?php print "parent$i" ?>surname" maxlength=30 value="<?php print $row["parent$i" . "surname"] ;?>" type="text" style="width: 300px">
									<?php
									if ($i==1) {
										?>
										<script type="text/javascript">
											var <?php print "parent$i" ?>surname=new LiveValidation('<?php print "parent$i" ?>surname');
											<?php print "parent$i" ?>surname.add(Validate.Presence);
										</script>
										 <?php
									}
									?>
								</td>
							</tr>
							<tr>
								<td>
									<b><?php print __($guid, 'First Name') ?><?php if ($i==1) { print " *" ;}?></b><br/>
									<span style="font-size: 90%"><i><?php print __($guid, 'First name as shown in ID documents.') ?></i></span>
								</td>
								<td class="right">
									<input name="<?php print "parent$i" ?>firstName" id="<?php print "parent$i" ?>firstName" maxlength=30 value="<?php print $row["parent$i" . "firstName"] ;?>" type="text" style="width: 300px">
									<?php
									if ($i==1) {
										?>
										<script type="text/javascript">
											var <?php print "parent$i" ?>firstName=new LiveValidation('<?php print "parent$i" ?>firstName');
											<?php print "parent$i" ?>firstName.add(Validate.Presence);
										</script>
										 <?php
									}
									?>
								</td>
							</tr>
							<tr>
								<td>
									<b><?php print __($guid, 'Preferred Name') ?><?php if ($i==1) { print " *" ;}?></b><br/>
									<span style="font-size: 90%"><i><?php print __($guid, 'Most common name, alias, nickname, etc.') ?></i></span>
								</td>
								<td class="right">
									<input name="<?php print "parent$i" ?>preferredName" id="<?php print "parent$i" ?>preferredName" maxlength=30 value="<?php print $row["parent$i" . "preferredName"] ;?>" type="text" style="width: 300px">
									<?php
									if ($i==1) {
										?>
										<script type="text/javascript">
											var <?php print "parent$i" ?>preferredName=new LiveValidation('<?php print "parent$i" ?>preferredName');
											<?php print "parent$i" ?>preferredName.add(Validate.Presence);
										</script>
										 <?php
									}
									?>
								</td>
							</tr>
							<tr>
								<td>
									<b><?php print __($guid, 'Official Name') ?><?php if ($i==1) { print " *" ;}?></b><br/>
									<span style="font-size: 90%"><i><?php print __($guid, 'Full name as shown in ID documents.') ?></i></span>
								</td>
								<td class="right">
									<input name="<?php print "parent$i" ?>officialName" id="<?php print "parent$i" ?>officialName" maxlength=30 value="<?php print $row["parent$i" . "officialName"] ;?>" type="text" style="width: 300px">
									<?php
									if ($i==1) {
										?>
										<script type="text/javascript">
											var <?php print "parent$i" ?>officialName=new LiveValidation('<?php print "parent$i" ?>officialName');
											<?php print "parent$i" ?>officialName.add(Validate.Presence);
										</script>
										 <?php
									}
									?>
								</td>
							</tr>
							<tr>
								<td>
									<b><?php print __($guid, 'Name In Characters') ?></b><br/>
									<span style="font-size: 90%"><i><?php print __($guid, 'Chinese or other character-based name.') ?></i></span>
								</td>
								<td class="right">
									<input name="<?php print "parent$i" ?>nameInCharacters" id="<?php print "parent$i" ?>nameInCharacters" maxlength=20 value="<?php print $row["parent$i" . "nameInCharacters"] ;?>" type="text" style="width: 300px">
								</td>
							</tr>
							<tr>
								<td>
									<b><?php print __($guid, 'Gender') ?><?php if ($i==1) { print " *" ;}?></b><br/>
								</td>
								<td class="right">
									<select name="<?php print "parent$i" ?>gender" id="<?php print "parent$i" ?>gender" style="width: 302px">
										<?php
										if ($i==1) {
											?>
											<option value="Please select..."><?php print __($guid, 'Please select...') ?></option>
											<?php
										}
										else {
											?>
											<option value=""></option>
											<?php
										}
										?>
										<option <?php if ($row["parent$i" . "gender"]=="F") { print "selected" ; } ?> value="F">F</option>
										<option <?php if ($row["parent$i" . "gender"]=="M") { print "selected" ; } ?> value="M">M</option>
									</select>
									<?php
									if ($i==1) {
										?>
										<script type="text/javascript">
											var <?php print "parent$i" ?>gender=new LiveValidation('<?php print "parent$i" ?>gender');
											<?php print "parent$i" ?>gender.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print __($guid, 'Select something!') ?>"});
										</script>
										 <?php
									}
									?>
								</td>
							</tr>
							<tr>
								<td>
									<b><?php print __($guid, 'Relationship') ?><?php if ($i==1) { print " *" ;}?></b><br/>
								</td>
								<td class="right">
									<select name="<?php print "parent$i" ?>relationship" id="<?php print "parent$i" ?>relationship" style="width: 302px">
										<?php
										if ($i==1) {
											print "<option value=\"Please select...\">Please select...</option>" ;
										}
										else {
											print "<option value=\"\"></option>" ;
										}?>
										<option <?php if ($row["parent" . $i . "relationship"]=="Mother") { print "selected" ; } ?> value="Mother"><?php print __($guid, 'Mother') ?></option>
										<option <?php if ($row["parent" . $i . "relationship"]=="Father") { print "selected" ; } ?> value="Father"><?php print __($guid, 'Father') ?></option>
										<option <?php if ($row["parent" . $i . "relationship"]=="Step-Mother") { print "selected" ; } ?> value="Step-Mother"><?php print __($guid, 'Step-Mother') ?></option>
										<option <?php if ($row["parent" . $i . "relationship"]=="Step-Father") { print "selected" ; } ?> value="Step-Father"><?php print __($guid, 'Step-Father') ?></option>
										<option <?php if ($row["parent" . $i . "relationship"]=="Adoptive Parent") { print "selected" ; } ?> value="Adoptive Parent"><?php print __($guid, 'Adoptive Parent') ?></option>
										<option <?php if ($row["parent" . $i . "relationship"]=="Guardian") { print "selected" ; } ?> value="Guardian"><?php print __($guid, 'Guardian') ?></option>
										<option <?php if ($row["parent" . $i . "relationship"]=="Grandmother") { print "selected" ; } ?> value="Grandmother"><?php print __($guid, 'Grandmother') ?></option>
										<option <?php if ($row["parent" . $i . "relationship"]=="Grandfather") { print "selected" ; } ?> value="Grandfather"><?php print __($guid, 'Grandfather') ?></option>
										<option <?php if ($row["parent" . $i . "relationship"]=="Aunt") { print "selected" ; } ?> value="Aunt"><?php print __($guid, 'Aunt') ?></option>
										<option <?php if ($row["parent" . $i . "relationship"]=="Uncle") { print "selected" ; } ?> value="Uncle"><?php print __($guid, 'Uncle') ?></option>
										<option <?php if ($row["parent" . $i . "relationship"]=="Nanny/Helper") { print "selected" ; } ?> value="Nanny/Helper"><?php print __($guid, 'Nanny/Helper') ?></option>
										<option <?php if ($row["parent" . $i . "relationship"]=="Other") { print "selected" ; } ?> value="Other"><?php print __($guid, 'Other') ?></option>
									</select>
									<?php
									if ($i==1) {
										?>
										<script type="text/javascript">
											var <?php print "parent$i" ?>relationship=new LiveValidation('<?php print "parent$i" ?>relationship');
											<?php print "parent$i" ?>relationship.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print __($guid, 'Select something!') ?>"});
										</script>
										 <?php
									}
									?>
								</td>
							</tr>

							<tr>
								<td colspan=2>
									<h4><?php print sprintf(__($guid, 'Parent/Guardian %1$s Background'), $i) ?></h4>
								</td>
							</tr>
							<tr>
								<td>
									<b><?php print __($guid, 'First Language') ?></b><br/>
								</td>
								<td class="right">
									<select name="<?php print "parent$i" ?>languageFirst" id="<?php print "parent$i" ?>languageFirst" style="width: 302px">
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
											if ($row["parent" . $i . "languageFirst"]==$rowSelect["name"]) {
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
									<select name="<?php print "parent$i" ?>languageSecond" id="<?php print "parent$i" ?>languageSecond" style="width: 302px">
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
											if ($row["parent" . $i . "languageSecond"]==$rowSelect["name"]) {
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
									<b><?php print __($guid, 'Citizenship') ?></b><br/>
								</td>
								<td class="right">
									<select name="<?php print "parent$i" ?>citizenship1" id="<?php print "parent$i" ?>citizenship1" style="width: 302px">
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
												print "<option value='" . $rowSelect["printable_name"] . "'>" . htmlPrep(__($guid, $rowSelect["printable_name"])) . "</option>" ;
											}
										}
										else {
											$nationalities=explode(",", $nationalityList) ;
											foreach ($nationalities as $nationality) {
												$selected="" ;
												if (trim($nationality)==$row["parent" . $i . "citizenship1"]) {
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
									<?php
									if ($_SESSION[$guid]["country"]=="") {
										print "<b>" . __($guid, 'National ID Card Number') . "</b><br/>" ;
									}
									else {
										print "<b>" . $_SESSION[$guid]["country"] . " " . __($guid, 'ID Card Number') . "</b><br/>" ;
									}
									?>
								</td>
								<td class="right">
									<input name="<?php print "parent$i" ?>nationalIDCardNumber" id="<?php print "parent$i" ?>nationalIDCardNumber" maxlength=30 value="<?php print $row["parent$i" . "nationalIDCardNumber"] ;?>" type="text" style="width: 300px">
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
										print "<input name='parent" . $i . "residencyStatus' id='parent" . $i . "residencyStatus' maxlength=30 value='" . $row["residencyStatus"] . "' type='text' style='width: 300px'>" ;
									}
									else {
										print "<select name='parent" . $i . "residencyStatus' id='parent" . $i . "residencyStatus' style='width: 302px'>" ;
											print "<option value=''></option>" ;
											$residencyStatuses=explode(",", $residencyStatusList) ;
											foreach ($residencyStatuses as $residencyStatus) {
												$selected="" ;
												if (trim($residencyStatus)==$row["parent" . $i . "residencyStatus"]) {
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
									print "<span style='font-size: 90%'><i>Format " ; if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; } print ". " . __($guid, 'If relevant.') . "</i></span>" ;
									?>
								</td>
								<td class="right">
									<input name="<?php print "parent$i" ?>visaExpiryDate" id="<?php print "parent$i" ?>visaExpiryDate" maxlength=10 value="<?php print dateConvertBack($guid, $row["parent" . $i . "visaExpiryDate"]) ?>" type="text" style="width: 300px">
									<script type="text/javascript">
										var <?php print "parent$i" ?>visaExpiryDate=new LiveValidation('<?php print "parent$i" ?>visaExpiryDate');
										<?php print "parent$i" ?>visaExpiryDate.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]["i18n"]["dateFormatRegEx"]=="") {  print "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i" ; } else { print $_SESSION[$guid]["i18n"]["dateFormatRegEx"] ; } ?>, failureMessage: "Use <?php if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?>." } );
									</script>
									 <script type="text/javascript">
										$(function() {
											$( "#<?php print "parent$i" ?>visaExpiryDate" ).datepicker();
										});
									</script>
								</td>
							</tr>


							<tr>
								<td colspan=2>
									<h4><?php print sprintf(__($guid, 'Parent/Guardian %1$s Contact'), $i) ?></h4>
								</td>
							</tr>
							<tr>
								<td>
									<b><?php print __($guid, 'Email') ?><?php if ($i==1) { print " *" ;}?></b><br/>
								</td>
								<td class="right">
									<input name="<?php print "parent$i" ?>email" id="<?php print "parent$i" ?>email" maxlength=50 value="<?php print $row["parent$i" . "email"] ;?>" type="text" style="width: 300px">
									<script type="text/javascript">
										var <?php print "parent$i" ?>email=new LiveValidation('<?php print "parent$i" ?>email');
										<?php print "parent$i" ?>email.add(Validate.Email);
										<?php
										if ($i==1) {
											print "parent$i" . "email.add(Validate.Presence);" ;
										}
										?>
									</script>
								</td>
							</tr>
							<?php
							for ($y=1; $y<3; $y++) {
								?>
								<tr>
									<td>
										<b><?php print __($guid, 'Phone') ?> <?php print $y ; if ($i==1 AND $y==1) { print " *" ;}?></b><br/>
										<span style="font-size: 90%"><i><?php print __($guid, 'Type, country code, number.') ?></i></span>
									</td>
									<td class="right">
										<input name="<?php print "parent$i" ?>phone<?php print $y ?>" id="<?php print "parent$i" ?>phone<?php print $y ?>" maxlength=20 value="<?php print $row["parent" . $i . "phone" . $y] ?>" type="text" style="width: 160px">
										<?php
										if ($i==1 AND $y==1) {
											?>
											<script type="text/javascript">
												var <?php print "parent$i" ?>phone<?php print $y ?>=new LiveValidation('<?php print "parent$i" ?>phone<?php print $y ?>');
												<?php print "parent$i" ?>phone<?php print $y ?>.add(Validate.Presence);
											</script>
											<?php
										}
										?>
										<select name="<?php print "parent$i" ?>phone<?php print $y ?>CountryCode" id="<?php print "parent$i" ?>phone<?php print $y ?>CountryCode" style="width: 60px">
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
												if ($row["parent" . $i . "phone" . $y . "CountryCode"]!="" AND $row["parent" . $i . "phone" . $y . "CountryCode"]==$rowSelect["iddCountryCode"]) {
													$selected="selected" ;
												}
												print "<option $selected value='" . $rowSelect["iddCountryCode"] . "'>" . htmlPrep($rowSelect["iddCountryCode"]) . " - " .  htmlPrep(__($guid, $rowSelect["printable_name"])) . "</option>" ;
											}
											?>
										</select>
										<select style="width: 70px" name="<?php print "parent$i" ?>phone<?php print $y ?>Type">
											<option <?php if ($row["parent" . $i . "phone" . $y . "Type"]=="") { print "selected" ; }?> value=""></option>
											<option <?php if ($row["parent" . $i . "phone" . $y . "Type"]=="Mobile") { print "selected" ; }?> value="Mobile"><?php print __($guid, 'Mobile') ?></option>
											<option <?php if ($row["parent" . $i . "phone" . $y . "Type"]=="Home") { print "selected" ; }?> value="Home"><?php print __($guid, 'Home') ?></option>
											<option <?php if ($row["parent" . $i . "phone" . $y . "Type"]=="Work") { print "selected" ; }?> value="Work"><?php print __($guid, 'Work') ?></option>
											<option <?php if ($row["parent" . $i . "phone" . $y . "Type"]=="Fax") { print "selected" ; }?> value="Fax"><?php print __($guid, 'Fax') ?></option>
											<option <?php if ($row["parent" . $i . "phone" . $y . "Type"]=="Pager") { print "selected" ; }?> value="Pager"><?php print __($guid, 'Pager') ?></option>
											<option <?php if ($row["parent" . $i . "phone" . $y . "Type"]=="Other") { print "selected" ; }?> value="Other"><?php print __($guid, 'Other') ?></option>
										</select>
									</td>
								</tr>
								<?php
							}
							?>

							<tr>
								<td colspan=2>
									<h4><?php print __($guid, 'Employment') ?></h4>
								</td>
							</tr>
							<tr>
								<td>
									<b><?php print __($guid, 'Profession') ?></b><br/>
								</td>
								<td class="right">
									<input name="<?php print "parent$i" ?>profession" id="<?php print "parent$i" ?>profession" maxlength=30 value="<?php print $row["parent$i" . "profession"] ;?>" type="text" style="width: 300px">
								</td>
							</tr>
							<tr>
								<td>
									<b><?php print __($guid, 'Employer') ?></b><br/>
								</td>
								<td class="right">
									<input name="<?php print "parent$i" ?>employer" id="<?php print "parent$i" ?>employer" maxlength=30 value="<?php print $row["parent$i" . "employer"] ;?>" type="text" style="width: 300px">
								</td>
							</tr>
							<?php

							//CUSTOM FIELDS FOR PARENTS, WITH FAMILY
							$parent1fields=unserialize($row["parent1fields"]) ;
							$parent2fields=unserialize($row["parent2fields"]) ;
							$resultFields=getCustomFields($connection2, $guid, FALSE, FALSE, TRUE, FALSE, TRUE, NULL) ;
							if ($resultFields->rowCount()>0) {
								?>
								<tr <?php if ($i==2) { print "class='secondParent'" ; }?>>
									<td colspan=2>
										<h4><?php print __($guid, 'Parent/Guardian') ?> <?php print $i ?> <?php print __($guid, 'Other Fields') ?></h4>
									</td>
								</tr>
								<?php
								while ($rowFields=$resultFields->fetch()) {
									if ($i==2) {
										print renderCustomFieldRow($connection2, $guid, $rowFields, $parent2fields[$rowFields["gibbonPersonFieldID"]], "parent2", "secondParent", TRUE) ;
										?>
										<script type="text/javascript">
											/* Advanced Options Control */
											$(document).ready(function(){
												$("#secondParent").click(function(){
													if ($('input[name=secondParent]:checked').val()=="No" ) {
														$("#parent<?php print $i ?>custom<?php print $rowFields["gibbonPersonFieldID"] ?>").attr("disabled", "disabled");
													}
													else {
														$("#parent<?php print $i ?>custom<?php print $rowFields["gibbonPersonFieldID"] ?>").removeAttr("disabled");
													}
												 });
											});
										</script>
										<?php
									}
									else {
										print renderCustomFieldRow($connection2, $guid, $rowFields, $parent1fields[$rowFields["gibbonPersonFieldID"]], "parent1") ;
									}
								}
							}
						}
					}
					else {
						?>
						<input type="hidden" name="gibbonFamily" value="TRUE">
						<tr class='break'>
							<td colspan=2>
								<h3><?php print __($guid, 'Family') ?></h3>
							</td>
						</tr>
						<tr>
							<td colspan=2>
								<p><?php print sprintf(__($guid, 'The applying family is already a member of %1$s.'), $_SESSION[$guid]["organisationName"]) ?></p>
							</td>
						</tr>
						<tr>
							<td colspan=2>
								<?php
								try {
									$dataFamily=array("gibbonFamilyID"=>$row["gibbonFamilyID"]);
									$sqlFamily="SELECT * FROM gibbonFamily WHERE gibbonFamilyID=:gibbonFamilyID" ;
									$resultFamily=$connection2->prepare($sqlFamily);
									$resultFamily->execute($dataFamily);
								}
								catch(PDOException $e) {
									print "<div class='error'>" . $e->getMessage() . "</div>" ;
								}

								if ($resultFamily->rowCount()!=1) {
									$proceed=FALSE ;
									print "<input readonly type='text' name='gibbonFamilyID' value='There is an error with this form!' style='width: 300px; color: #c00; text-align: right; font-weight: bold'/>" ;
								}
								else {
									$rowFamily=$resultFamily->fetch() ;
									print "<table cellspacing='0' style='width: 100%'>" ;
										print "<tr class='head'>" ;
											print "<th>" ;
												print __($guid, "Family Name") ;
											print "</th>" ;
											print "<th>" ;
												print __($guid, "Parents") ;
											print "</th>" ;
										print "</tr>" ;
										print "<tr class='even'>" ;
											print "<td>" ;
												print "<b>" . $rowFamily["name"] . "</b><br/>" ;
											print "</td>" ;
											print "<td>" ;
												try {
													$dataRelationships=array("gibbonApplicationFormID"=>$gibbonApplicationFormID);
													$sqlRelationships="SELECT surname, preferredName, title, gender, gibbonApplicationFormRelationship.gibbonPersonID, relationship FROM gibbonApplicationFormRelationship JOIN gibbonPerson ON (gibbonApplicationFormRelationship.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonApplicationFormRelationship.gibbonApplicationFormID=:gibbonApplicationFormID" ;
													$resultRelationships=$connection2->prepare($sqlRelationships);
													$resultRelationships->execute($dataRelationships);
												}
												catch(PDOException $e) {
													print "<div class='error'>" . $e->getMessage() . "</div>" ;
												}
												while ($rowRelationships=$resultRelationships->fetch()) {
													print formatName($rowRelationships["title"], $rowRelationships["preferredName"], $rowRelationships["surname"], "Parent") . " (" . $rowRelationships["relationship"] . ")" ;
													print "<br/>" ;
												}
											print "</td>" ;
										print "</tr>" ;
									print "</table>" ;
									print "<input type='hidden' name='gibbonFamilyID' value='" . $row["gibbonFamilyID"] . "'/>" ;
								}
								?>
							</td>
						</tr>
						<?php
					}
					?>
					<tr class='break'>
						<td colspan=2>
							<h3><?php print __($guid, 'Siblings') ?></h3>
						</td>
					</tr>
					<tr>
						<td colspan=2 style='padding-top: 0px'>
							<p>Please give information on any siblings not currently studying at <?php print $_SESSION[$guid]["organisationName"] ?>.</p>
						</td>
					</tr>
					<tr>
						<td colspan=2>
							<?php
							print "<table cellspacing='0' style='width: 100%'>" ;
								print "<tr class='head'>" ;
									print "<th>" ;
										print __($guid, "Sibling Name") ;
									print "</th>" ;
									print "<th>" ;
										print __($guid, "Date of Birth") . "<br/><span style='font-size: 80%'>" ; if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; } print "</span>" ;
									print "</th>" ;
									print "<th>" ;
										print __($guid, "School Attending") ;
									print "</th>" ;
									print "<th>" ;
										print __($guid, "Joining Date") . "<br/><span style='font-size: 80%'>" ;if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; } print "</span>" ;
									print "</th>" ;
								print "</tr>" ;


								for ($i=1; $i<4; $i++) {
									if ((($i%2)-1)==0) {
										$rowNum="even" ;
									}
									else {
										$rowNum="odd" ;
									}

									print "<tr class=$rowNum>" ;
										print "<td>" ;
											print "<input name='siblingName$i' id='siblingName$i' maxlength=50 value='" . $row["siblingName$i"] . "' type='text' style='width:120px; float: left'>" ;
										print "</td>" ;
										print "<td>" ;
											?>
											<input name="<?php print "siblingDOB$i" ?>" id="<?php print "siblingDOB$i" ?>" maxlength=10 value="<?php print dateConvertBack($guid, $row["siblingDOB$i"]) ?>" type="text" style="width:90px; float: left"><br/>
											<script type="text/javascript">
												$(function() {
													$( "#<?php print "siblingDOB$i" ?>" ).datepicker();
												});
											</script>
											<?php
										print "</td>" ;
										print "<td>" ;
											print "<input name='siblingSchool$i' id='siblingSchool$i' maxlength=50 value='" . $row["siblingSchool$i"] . "' type='text' style='width:200px; float: left'>" ;
										print "</td>" ;
										print "<td>" ;
											?>
											<input name="<?php print "siblingSchoolJoiningDate$i" ?>" id="<?php print "siblingSchoolJoiningDate$i" ?>" maxlength=10 value="<?php print dateConvertBack($guid, $row["siblingSchoolJoiningDate$i"]) ?>" type="text" style="width:90px; float: left">
											<script type="text/javascript">
												$(function() {
													$( "#<?php print "siblingSchoolJoiningDate$i" ?>" ).datepicker();
												});
											</script>
											<?php
										print "</td>" ;
									print "</tr>" ;
								}
							print "</table>" ;
							?>
						</td>
					</tr>

					<?php
					$languageOptionsActive=getSettingByScope($connection2, 'Application Form', 'languageOptionsActive') ;
					if ($languageOptionsActive=="Y") {
						?>
						<tr class='break'>
							<td colspan=2>
								<h3><?php print __($guid, 'Language Selection') ?></h3>
								<?php
								$languageOptionsBlurb=getSettingByScope($connection2, 'Application Form', 'languageOptionsBlurb') ;
								if ($languageOptionsBlurb!="") {
									print "<p>" ;
										print $languageOptionsBlurb ;
									print "</p>" ;
								}
								?>
							</td>
						</tr>
						<tr>
							<td>
								<b><?php print __($guid, 'Language Choice') ?></b><br/>
								<span style="font-size: 90%"><i><?php print __($guid, 'Please choose preferred additional language to study.') ?></i></span>
							</td>
							<td class="right">
								<select name="languageChoice" id="languageChoice" style="width: 302px">
									<?php
									print "<option value='Please select...'>" . __($guid, 'Please select...') . "</option>" ;
									$languageOptionsLanguageList=getSettingByScope($connection2, "Application Form", "languageOptionsLanguageList") ;
									$languages=explode(",", $languageOptionsLanguageList) ;
									foreach ($languages as $language) {
										$selected="" ;
										if ($row["languageChoice"]==trim($language)) {
											$selected="selected" ;
										}
										print "<option $selected value='" . trim($language) . "'>" . trim($language) . "</option>" ;
									}
									?>
								</select>
							</td>
						</tr>
						<tr>
							<td colspan=2 style='padding-top: 15px'>
								<b><?php print __($guid, 'Language Choice Experience') ?></b><br/>
								<span style="font-size: 90%"><i><?php print __($guid, 'Has the applicant studied the selected language before? If so, please describe the level and type of experience.') ?></i></span><br/>
								<textarea name="languageChoiceExperience" id="languageChoiceExperience" rows=5 style="width:738px; margin: 5px 0px 0px 0px"><?php print htmlPrep($row["languageChoiceExperience"]) ;?></textarea>
							</td>
						</tr>
						<?php
					}
					?>

					<tr class='break'>
						<td colspan=2>
							<h3><?php print __($guid, 'Scholarships') ?></h3>
							<?php
							//Get scholarships info
							try {
								$dataIntro=array();
								$sqlIntro="SELECT * FROM gibbonSetting WHERE scope='Application Form' AND name='scholarships'" ;
								$resultIntro=$connection2->prepare($sqlIntro);
								$resultIntro->execute($dataIntro);
							}
							catch(PDOException $e) {
								print "<div class='error'>" . $e->getMessage() . "</div>" ;
							}
							if ($resultIntro->rowCount()==1) {
								$rowIntro=$resultIntro->fetch() ;
								if ($rowIntro["value"]!="") {
									print "<p>" ;
										print $rowIntro["value"] ;
									print "</p>" ;
								}
							}
							?>
						</td>
					</tr>
					<tr>
						<td>
							<b><?php print __($guid, 'Interest') ?></b><br/>
							<span style="font-size: 90%"><i><?php print __($guid, 'Indicate if you are interested in a scholarship.') ?></i></span><br/>
						</td>
						<td class="right">
							<input <?php if ($row["scholarshipInterest"]=="Y") { print "checked" ; } ?> type="radio" id="scholarshipInterest" name="scholarshipInterest" class="type" value="Y" /> <?php print __($guid, 'Yes') ?>
							<input <?php if ($row["scholarshipInterest"]=="N") { print "checked" ; } ?> type="radio" id="scholarshipInterest" name="scholarshipInterest" class="type" value="N" /><?php print __($guid, 'No') ?>
						</td>
					</tr>
					<tr>
						<td>
							<b>Required?</b><br/>
							<span style="font-size: 90%"><i>Is a scholarship <b>required</b> for you to take up a place at <?php print $_SESSION[$guid]["organisationNameShort"] ?>?</i></span><br/>
						</td>
						<td class="right">
							<input <?php if ($row["scholarshipRequired"]=="Y") { print "checked" ; } ?> type="radio" id="scholarshipRequired" name="scholarshipRequired" class="type" value="Y" /> <?php print __($guid, 'Yes') ?>
							<input <?php if ($row["scholarshipRequired"]=="N") { print "checked" ; } ?> type="radio" id="scholarshipRequired" name="scholarshipRequired" class="type" value="N" /> <?php print __($guid, 'No') ?>
						</td>
					</tr>


					<tr class='break'>
						<td colspan=2>
							<h3><?php print __($guid, 'Payment') ?></h3>
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
							<p><?php print __($guid, 'If you choose family, future invoices will be sent according to family contact preferences, which can be changed at a later date by contacting the school. For example you may wish both parents to receive the invoice, or only one. Alternatively, if you choose Company, you can choose for all or only some fees to be covered by the specified company.') ?></p>
						</td>
					</tr>
					<tr>
						<td>
							<b><?php print __($guid, 'Send Invoices To') ?></b><br/>
						</td>
						<td class="right">
							<input <?php if ($row["payment"]=="Family") { print "checked" ; } ?> type="radio" name="payment" value="Family" class="payment" /> <?php print __($guid, 'Family') ?>
							<input <?php if ($row["payment"]=="Company") { print "checked" ; } ?> type="radio" name="payment" value="Company" class="payment" /> <?php print __($guid, 'Company') ?>
						</td>
					</tr>
					<tr id="companyNameRow">
						<td>
							<b><?php print __($guid, 'Company Name') ?></b><br/>
						</td>
						<td class="right">
							<input name="companyName" id="companyName" maxlength=100 value="<?php print $row["companyName"] ?>" type="text" style="width: 300px">
							<script type="text/javascript">
								var companyName=new LiveValidation('companyName');
								companyName.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<tr id="companyContactRow">
						<td>
							<b><?php print __($guid, 'Company Contact Person') ?></b><br/>
						</td>
						<td class="right">
							<input name="companyContact" id="companyContact" maxlength=100 value="<?php print $row["companyContact"] ?>" type="text" style="width: 300px">
							<script type="text/javascript">
								var companyContact=new LiveValidation('companyContact');
								companyContact.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<tr id="companyAddressRow">
						<td>
							<b><?php print __($guid, 'Company Address') ?></b><br/>
						</td>
						<td class="right">
							<input name="companyAddress" id="companyAddress" maxlength=255 value="<?php print $row["companyAddress"] ?>" type="text" style="width: 300px">
							<script type="text/javascript">
								var companyAddress=new LiveValidation('companyAddress');
								companyAddress.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<tr id="companyEmailRow">
						<td>
							<b><?php print __($guid, 'Company Email') ?></b><br/>
						</td>
						<td class="right">
							<input name="companyEmail" id="companyEmail" maxlength=255 value="<?php print $row["companyEmail"] ?>" type="text" style="width: 300px">
							<script type="text/javascript">
								var companyEmail=new LiveValidation('companyEmail');
								companyEmail.add(Validate.Presence);
								companyEmail.add(Validate.Email);
							</script>
						</td>
					</tr>
					<tr id="companyCCFamilyRow">
						<td>
							<b><?php print __($guid, 'CC Family?') ?></b><br/>
							<span style="font-size: 90%"><i><?php print __($guid, 'Should the family be sent a copy of billing emails?') ?></i></span>
						</td>
						<td class="right">
							<select name="companyCCFamily" id="companyCCFamily" style="width: 302px">
								<option <?php if ($row["companyCCFamily"]=="N") { print "selected" ; } ?> value="N" /> <?php print __($guid, 'No') ?>
								<option <?php if ($row["companyCCFamily"]=="Y") { print "selected" ; } ?> value="Y" /> <?php print __($guid, 'Yes') ?>
							</select>
						</td>
					</tr>
					<tr id="companyPhoneRow">
						<td>
							<b><?php print __($guid, 'Company Phone') ?></b><br/>
						</td>
						<td class="right">
							<input name="companyPhone" id="companyPhone" maxlength=20 value="<?php print $row["companyPhone"] ?>" type="text" style="width: 300px">
						</td>
					</tr>
					<?php
					try {
						$dataCat=array();
						$sqlCat="SELECT * FROM gibbonFinanceFeeCategory WHERE active='Y' AND NOT gibbonFinanceFeeCategoryID=1 ORDER BY name" ;
						$resultCat=$connection2->prepare($sqlCat);
						$resultCat->execute($dataCat);
					}
					catch(PDOException $e) { }
					if ($resultCat->rowCount()<1) {
						print "<input type=\"hidden\" name=\"companyAll\" value=\"Y\" class=\"companyAll\"/>" ;
					}
					else {
						?>
						<tr id="companyAllRow">
							<td>
								<b><?php print __($guid, 'Company All?') ?></b><br/>
								<span style="font-size: 90%"><i><?php print __($guid, 'Should all items be billed to the specified company, or just some?') ?></i></span>
							</td>
							<td class="right">
								<input type="radio" name="companyAll" value="Y" class="companyAll" <?php if ($row["companyAll"]=="Y" OR $row["companyAll"]=="") { print "checked" ; } ?> /> <?php print __($guid, 'All') ?>
								<input type="radio" name="companyAll" value="N" class="companyAll" <?php if ($row["companyAll"]=="N") { print "checked" ; } ?> /> <?php print __($guid, 'Selected') ?>
							</td>
						</tr>
						<tr id="companyCategoriesRow">
							<td>
								<b><?php print __($guid, 'Company Fee Categories') ?></b><br/>
								<span style="font-size: 90%"><i><?php print __($guid, 'If the specified company is not paying all fees, which categories are they paying?') ?></i></span>
							</td>
							<td class="right">
								<?php
								while ($rowCat=$resultCat->fetch()) {
									$checked="" ;
									if (strpos($row["gibbonFinanceFeeCategoryIDList"], $rowCat["gibbonFinanceFeeCategoryID"])!==FALSE) {
										$checked="checked" ;
									}
									print $rowCat["name"] . " <input $checked type='checkbox' name='gibbonFinanceFeeCategoryIDList[]' value='" . $rowCat["gibbonFinanceFeeCategoryID"] . "'/><br/>" ;
								}
								$checked="" ;
								if (strpos($row["gibbonFinanceFeeCategoryIDList"], "0001")!==FALSE) {
									$checked="checked" ;
								}
								print "Other <input $checked type='checkbox' name='gibbonFinanceFeeCategoryIDList[]' value='0001'/><br/>" ;
								?>
							</td>
						</tr>
						<?php
					}

					$requiredDocuments=getSettingByScope($connection2, "Application Form", "requiredDocuments") ;
					$requiredDocumentsCompulsory=getSettingByScope($connection2, "Application Form", "requiredDocumentsCompulsory") ;
					$count=0 ;
					if ($requiredDocuments!="" AND $requiredDocuments!=FALSE) {
						?>
						<tr class='break'>
							<td colspan=2>
								<h3><?php print __($guid, 'Supporting Documents') ?></h3>
							</td>
						</tr>
						<?php

						//Get list of acceptable file extensions
						try {
							$dataExt=array();
							$sqlExt="SELECT * FROM gibbonFileExtension" ;
							$resultExt=$connection2->prepare($sqlExt);
							$resultExt->execute($dataExt);
						}
						catch(PDOException $e) { }
						$ext="" ;
						while ($rowExt=$resultExt->fetch()) {
							$ext=$ext . "'." . $rowExt["extension"] . "'," ;
						}

						$requiredDocumentsList=explode(",", $requiredDocuments) ;
						foreach ($requiredDocumentsList AS $document) {
							try {
								$dataFile=array("gibbonApplicationFormID"=>$gibbonApplicationFormID, "name"=>$document);
								$sqlFile="SELECT * FROM gibbonApplicationFormFile WHERE gibbonApplicationFormID=:gibbonApplicationFormID AND name=:name ORDER BY name" ;
								$resultFile=$connection2->prepare($sqlFile);
								$resultFile->execute($dataFile);
							}
							catch(PDOException $e) { }
							if ($resultFile->rowCount()==0) {
								?>
								<tr>
									<td>
										<b><?php print $document ; if ($requiredDocumentsCompulsory=="Y") { print " *" ; } ?></b><br/>
									</td>
									<td class="right">
										<?php
										print "<input type='file' name='file$count' id='file$count'><br/>" ;
										print "<input type='hidden' name='fileName$count' id='filefileName$count' value='$document'>" ;
										if ($requiredDocumentsCompulsory=="Y") {
											print "<script type='text/javascript'>" ;
												print "var file$count=new LiveValidation('file$count');" ;
												print "file$count.add( Validate.Inclusion, { within: [" . $ext . "], failureMessage: 'Illegal file type!', partialMatch: true, caseSensitive: false } );" ;
												print "file$count.add(Validate.Presence);" ;
											print "</script>" ;
										}
										$count++ ;
										?>
									</td>
								</tr>
								<?php
							}
							else if ($resultFile->rowCount()==1) {
								$rowFile=$resultFile->fetch() ;
								?>
								<tr>
									<td>
										<?php print "<b>" . $rowFile["name"] . "</b><br/>" ?>
										<span style="font-size: 90%"><i><?php print __($guid, 'This value cannot be changed.') ?></i></span>
									</td>
									<td class="right">
										<?php
										print "<a target='_blank' href='" . $_SESSION[$guid]["absoluteURL"] . "/" . $rowFile["path"] . "'>Download</a>" ;
										?>
									</td>
								</tr>
								<?php
							}
							else {
								//Error
							}
						}
					}
					if ($count>0) {
						?>
						<tr>
							<td colspan=2>
								<?php print getMaxUpload() ; ?>
								<input type="hidden" name="fileCount" value="<?php print $count ?>">
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
							<b><?php print __($guid, 'How Did You Hear About Us?') ?></b><br/>
						</td>
						<td class="right">
							<?php
							$howDidYouHearList=getSettingByScope($connection2, "Application Form", "howDidYouHear") ;
							if ($howDidYouHearList=="") {
								print "<input name='howDidYouHear' id='howDidYouHear' maxlength=30 value='" . $row["howDidYouHear"] . "' type='text' style='width: 300px'>" ;
							}
							else {
								print "<select name='howDidYouHear' id='howDidYouHear' style='width: 302px'>" ;
									print "<option value=''></option>" ;
									$howDidYouHears=explode(",", $howDidYouHearList) ;
									foreach ($howDidYouHears as $howDidYouHear) {
										$selected="" ;
										if (trim($howDidYouHear)==$row["howDidYouHear"]) {
											$selected="selected" ;
										}
										print "<option $selected value='" . trim($howDidYouHear) . "'>" . trim($howDidYouHear) . "</option>" ;
									}
								print "</select>" ;
							}
							?>
						</td>
					</tr>
					<tr id="tellUsMoreRow">
						<td>
							<b><?php print __($guid, 'Tell Us More') ?></b><br/>
							<span style="font-size: 90%"><i><?php print __($guid, 'The name of a person or link to a website.') ?></i></span>
						</td>
						<td class="right">
							<input name="howDidYouHearMore" id="howDidYouHearMore" maxlength=255 value="<?php print $row["howDidYouHearMore"] ?>" type="text" style="width: 300px">
						</td>
					</tr>
					<?php
					$privacySetting=getSettingByScope( $connection2, "User Admin", "privacy" ) ;
					$privacyBlurb=getSettingByScope( $connection2, "User Admin", "privacyBlurb" ) ;
					$privacyOptions=getSettingByScope( $connection2, "User Admin", "privacyOptions" ) ;
					if ($privacySetting=="Y" AND $privacyBlurb!="" AND $privacyOptions!="") {
						?>
						<tr>
							<td>
								<b><?php print __($guid, 'Privacy') ?> *</b><br/>
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
					if ($proceed==TRUE) {
						?>
						<tr>
							<td>
								<span style="font-size: 90%"><i>* <?php print __($guid, "denotes a required field") ; ?></i></span>
							</td>
							<td class="right">
								<input type="hidden" name="address" value="<?php print $_SESSION[$guid]["address"] ?>">
								<input name="gibbonSchoolYearID" id="gibbonSchoolYearID" value="<?php print $gibbonSchoolYearID ?>" type="hidden">
								<input type="hidden" name="gibbonApplicationFormID" value="<?php print $row["gibbonApplicationFormID"] ?>">
								<input type="submit" value="<?php print __($guid, "Submit") ; ?>">
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
