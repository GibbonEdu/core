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
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . _(getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/User Admin/applicationForm_manage.php&gibbonSchoolYearID=" . $_GET["gibbonSchoolYearID"] . "'>" . _('Manage Application Forms') . "</a> > </div><div class='trailEnd'>" . _('Edit Form') . "</div>" ;
	print "</div>" ;
	
	//Check if school year specified
	$gibbonApplicationFormID=$_GET["gibbonApplicationFormID"];
	$gibbonSchoolYearID=$_GET["gibbonSchoolYearID"] ;
	$search=$_GET["search"] ;
	if ($gibbonApplicationFormID=="" OR $gibbonSchoolYearID=="") {
		print "<div class='error'>" ;
			print _("You have not specified one or more required parameters.") ;
		print "</div>" ;
	}
	else {
		try {
			$data=array("gibbonApplicationFormID"=>$gibbonApplicationFormID); 
			$sql="SELECT * FROM gibbonApplicationForm LEFT JOIN gibbonPayment ON (gibbonApplicationForm.gibbonPaymentID=gibbonPayment.gibbonPaymentID AND foreignTable='gibbonApplicationForm') WHERE gibbonApplicationFormID=:gibbonApplicationFormID" ;
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
				else if ($updateReturn=="success1") {
					$updateReturnMessage=_("Your request was completed successfully, but status could not be updated.") ;	
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
			$proceed=TRUE ;
			
			print "<div class='linkTop'>" ;
				if ($search!="") {
					print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/User Admin/applicationForm_manage.php&gibbonSchoolYearID=$gibbonSchoolYearID&search=$search'>" . _('Back to Search Results') . "</a> | " ;
				}
				print "<a target='_blank' href='" . $_SESSION[$guid]["absoluteURL"] . "/report.php?q=/modules/" . $_SESSION[$guid]["module"] . "/applicationForm_manage_edit_print.php&gibbonApplicationFormID=$gibbonApplicationFormID'><img title='" . _('Print') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/print.png'/></a>" ;
			print "</div>" ;
			?>
			<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/applicationForm_manage_editProcess.php?search=$search" ?>" enctype="multipart/form-data">
				<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
					<tr class='break'>
						<td colspan=2> 
							<h3><?php print _('For Office Use') ?></h3>
						</td>
					</tr>
					<tr>
						<td style='width: 275px'> 
							<b><?php print _('Application ID') ?> *</b><br/>
							<span style="font-size: 90%"><i><?php print _('This value cannot be changed.') ?></i></span>
						</td>
						<td class="right">
							<input readonly name="gibbonApplicationFormID" id="gibbonApplicationFormID" value="<?php print htmlPrep($row["gibbonApplicationFormID"]) ?>" type="text" style="width: 300px">
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print _('Priority') ?> *</b><br/>
							<span style="font-size: 90%"><i><?php print _('Higher priority applicants appear first in list of applications.') ?></i></span>
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
					if ($row["status"]!="Accepted") {
						?>
						<tr>
							<td> 
								<b><?php print _('Status') ?> *</b><br/>
								<span style="font-size: 90%"><i><?php print _('Manually set status. "Approved" not permitted.') ?></i></span>
							</td>
							<td class="right">
								<select name="status" id="status" style="width: 302px">
									<option <?php if ($row["status"]=="Pending") { print "selected" ; } ?> value="Pending"><?php print _('Pending') ?></option>
									<option <?php if ($row["status"]=="Waiting List") { print "selected" ; } ?> value="Waiting List"><?php print _('Waiting List') ?></option>
									<option <?php if ($row["status"]=="Rejected") { print "selected" ; } ?> value="Rejected"><?php print _('Rejected') ?></option>
									<option <?php if ($row["status"]=="Withdrawn") { print "selected" ; } ?> value="Withdrawn"><?php print _('Withdrawn') ?></option>
								</select>
							</td>
						</tr>
						<?php
					}
					else {
						?>
						<tr>
							<td> 
								<b><?php print _('Status') ?> *</b><br/>
								<span style="font-size: 90%"><i><?php print _('This value cannot be changed.') ?></i></span>
							</td>
							<td class="right">
								<input readonly name="status" id="status" maxlength=20 value="<?php print htmlPrep($row["status"]) ?>" type="text" style="width: 300px">
							</td>
						</tr>
						<?php
					}
					$milestonesMasterRaw=getSettingByScope($connection2, "Application Form", "milestones") ;
					if ($milestonesMasterRaw!="") {
						?>
						<tr>
							<td> 
								<b><?php print _('Milestones') ?></b><br/>
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
							<b><?php print _('Start Date') ?></b><br/>
							<span style="font-size: 90%"><i><?php print _('Student\'s intended first day at school.') ?><br/>Format <?php if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; } ?></i></span>
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
							<b><?php print _('Year of Entry') ?> *</b><br/>
							<span style="font-size: 90%"><i><?php print _('When will the student join?') ?></i></span>
						</td>
						<td class="right">
							<select name="gibbonSchoolYearIDEntry" id="gibbonSchoolYearIDEntry" style="width: 302px">
								<?php
								print "<option value='Please select...'>" . _('Please select...') . "</option>" ;
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
								gibbonSchoolYearIDEntry.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print _('Select something!') ?>"});
							 </script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print _('Year Group at Entry') ?> *</b><br/>
							<span style="font-size: 90%"><i><?php print _('Which year level will student enter.') ?></i></span>
						</td>
						<td class="right">
							<select name="gibbonYearGroupIDEntry" id="gibbonYearGroupIDEntry" style="width: 302px">
								<?php
								print "<option value='Please select...'>" . _('Please select...') . "</option>" ;
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
									print "<option $selected value='" . $rowSelect["gibbonYearGroupID"] . "'>" . htmlPrep(_($rowSelect["name"])) . "</option>" ;
								}
								?>				
							</select>
							<script type="text/javascript">
								var gibbonYearGroupIDEntry=new LiveValidation('gibbonYearGroupIDEntry');
								gibbonYearGroupIDEntry.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print _('Select something!') ?>"});
							 </script>
						</td>
					</tr>
					<?php
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
							<b><?php print _('Roll Group at Entry') ?></b><br/>
							<span style="font-size: 90%"><?php print _('If set, the student will automatically be enroled on Accept.') ?></span>
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
								<b><?php print _('Payment') ?> *</b><br/>
								<span style="font-size: 90%"><i><?php print sprintf(_('Has payment (%1$s %2$s) been made for this application.'), $currency, $applicationFee) ?></i></span>
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
												print _("Payment Token:") . " " . $row["paymentToken"] . "<br/>" ;
											}
											if ($row["paymentPayerID"]!="") {
												print _("Payment Payer ID:") . " " . $row["paymentPayerID"] . "<br/>" ;
											}
											if ($row["paymentTransactionID"]!="") {
												print _("Payment Transaction ID:") . " " . $row["paymentTransactionID"] . "<br/>" ;
											}
											if ($row["paymentReceiptID"]!="") {
												print _("Payment Receipt ID:") . " " . $row["paymentReceiptID"] . "<br/>" ;
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
							<b><?php print _('Notes') ?></b><br/>
							<textarea name="notes" id="notes" rows=5 style="width:738px; margin: 5px 0px 0px 0px"><?php print htmlPrep($row["notes"]) ?></textarea>
						</td>
					</tr>
					
					<tr class='break'>
						<td colspan=2> 
							<h3><?php print _('Student') ?></h3>
						</td>
					</tr>
					
					<tr>
						<td colspan=2> 
							<h4><?php print _('Student Personal Data') ?></h4>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print _('Surname') ?> *</b><br/>
							<span style="font-size: 90%"><i><?php print _('Family name as shown in ID documents.') ?></i></span>
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
							<b><?php print _('First Name') ?> *</b><br/>
							<span style="font-size: 90%"><i><?php print _('First name as shown in ID documents.') ?></i></span>
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
							<b><?php print _('Preferred Name') ?> *</b><br/>
							<span style="font-size: 90%"><i><?php print _('Most common name, alias, nickname, etc.') ?></i></span>
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
							<b><?php print _('Official Name') ?> *</b><br/>
							<span style="font-size: 90%"><i><?php print _('Full name as shown in ID documents.') ?></i></span>
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
							<b><?php print _('Name In Characters') ?></b><br/>
							<span style="font-size: 90%"><i><?php print _('Chinese or other character-based name.') ?></i></span>
						</td>
						<td class="right">
							<input name="nameInCharacters" id="nameInCharacters" maxlength=20 value="<?php print $row["nameInCharacters"] ?>" type="text" style="width: 300px">
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print _('Gender') ?> *</b><br/>
						</td>
						<td class="right">
							<select name="gender" id="gender" style="width: 302px">
								<option value="Please select..."><?php print _('Please select...') ?></option>
								<option <?php if ($row["gender"]=="F") { print "selected" ; } ?> value="F"><?php print _('Female') ?></option>
								<option <?php if ($row["gender"]=="M") { print "selected" ; } ?> value="M"><?php print _('Male') ?></option>
							</select>
							<script type="text/javascript">
								var gender=new LiveValidation('gender');
								gender.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print _('Select something!') ?>"});
							 </script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print _('Date of Birth') ?> *</b><br/>
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
							<h4><?php print _('Student Background') ?></h4>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print _('Home Language') ?></b><br/>
							<span style="font-size: 90%"><i><?php print _('The primary language used in the student\'s home.') ?></i></span>
						</td>
						<td class="right">
							<input name="languageHome" id="languageHome" maxlength=30 value="<?php print $row["languageHome"] ?>" type="text" style="width: 300px">
						</td>
						<script type="text/javascript">
							$(function() {
								var availableTags=[
									<?php
									try {
										$dataAuto=array(); 
										$sqlAuto="SELECT DISTINCT languageHome FROM gibbonApplicationForm ORDER BY languageHome" ;
										$resultAuto=$connection2->prepare($sqlAuto);
										$resultAuto->execute($dataAuto);
									}
									catch(PDOException $e) { }
									while ($rowAuto=$resultAuto->fetch()) {
										print "\"" . $rowAuto["languageHome"] . "\", " ;
									}
									?>
								];
								$( "#languageHome" ).autocomplete({source: availableTags});
							});
						</script>
					</tr>
					<tr>
						<td> 
							<b><?php print _('First Language<') ?>/b><br/>
							<span style="font-size: 90%"><i><?php print _('Student\'s native/first/mother language.') ?></i></span>
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
										$sqlAuto="SELECT DISTINCT languageFirst FROM gibbonApplicationForm ORDER BY languageFirst" ;
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
										$sqlAuto="SELECT DISTINCT languageSecond FROM gibbonApplicationForm ORDER BY languageSecond" ;
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
										$sqlAuto="SELECT DISTINCT languageThird FROM gibbonApplicationForm ORDER BY languageThird" ;
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
								catch(PDOException $e) { }
								while ($rowSelect=$resultSelect->fetch()) {
									$selected="" ;
									if ($row["countryOfBirth"]==$rowSelect["printable_name"]) {
										$selected="selected" ;
									}
									print "<option $selected value='" . $rowSelect["printable_name"] . "'>" . htmlPrep(_($rowSelect["printable_name"])) . "</option>" ;
								}
								?>				
							</select>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print _('Citizenship') ?></b><br/>
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
							<b><?php print _('Citizenship Passport Number') ?></b><br/>
						</td>
						<td class="right">
							<input name="citizenship1Passport" id="citizenship1Passport" maxlength=30 value="<?php print $row["citizenship1Passport"] ?>" type="text" style="width: 300px">
						</td>
					</tr>
					<tr>
						<td> 
						<?php
						if ($_SESSION[$guid]["country"]=="") {
							print "<b>" . _('National ID Card Number') . "</b><br/>" ;
						}
						else {
							print "<b>" . $_SESSION[$guid]["country"] . " " . _('ID Card Number') . "</b><br/>" ;
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
					
					
					<tr>
						<td colspan=2> 
							<h4><?php print _('Student Contact') ?></h4>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print _('Email') ?></b><br/>
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
										print "<option $selected value='" . $rowSelect["iddCountryCode"] . "'>" . htmlPrep($rowSelect["iddCountryCode"]) . " - " .  htmlPrep(_($rowSelect["printable_name"])) . "</option>" ;
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
						<td colspan=2> 
							<h4><?php print _('Student Medical & Development') ?></h4>
						</td>
					</tr>
					<tr>
						<td colspan=2 style='padding-top: 15px'> 
							<b><?php print _('Medical Information') ?></b><br/>
							<span style="font-size: 90%"><i><?php print _('Please indicate any medical conditions.') ?></i></span><br/>
							<textarea name="medicalInformation" id="medicalInformation" rows=5 style="width:738px; margin: 5px 0px 0px 0px"><?php print htmlPrep($row["medicalInformation"]) ?></textarea>
						</td>
					</tr>
					<tr>
						<td colspan=2 style='padding-top: 15px'> 
							<b><?php print _('Development Information') ?></b><br/>
							<span style="font-size: 90%"><i><?php print _('Provide any comments or information concerning your child\'s development that may be relevant to your child’s performance in the classroom or elsewhere? (Incorrect or withheld information may affect continued enrolment).') ?></i></span><br/> 					
							<textarea name="developmentInformation" id="developmentInformation" rows=5 style="width:738px; margin: 5px 0px 0px 0px"><?php print htmlPrep($row["developmentInformation"]) ?></textarea>
						</td>
					</tr>
					
					
					
					<tr>
						<td colspan=2> 
							<h4><?php print _('Previous Schools') ?></h4>
							<p><?php print _('Please give information on the last two schools attended by the applicant.') ?></p>
						</td>
					</tr>
					<tr>
						<td colspan=2> 
							<?php
							print "<table cellspacing='0' style='width: 100%'>" ;
								print "<tr class='head'>" ;
									print "<th>" ;
										print _("School Name") ;
									print "</th>" ;
									print "<th>" ;
										print _("Address") ;
									print "</th>" ;
									print "<th>" ;
										print _("Grades<br/>Attended") ;
									print "</th>" ;
									print "<th>" ;
										print _("Language of<br/>Instruction") ;
									print "</th>" ;
									print "<th>" ;
										print _("Joining Date") . "<br/><span style='font-size: 80%'>" ; if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; } print "</span>" ;
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
					if ($row["gibbonFamilyID"]=="") {
						?>
						<input type="hidden" name="gibbonFamily" value="FALSE">
						
						<tr class='break'>
							<td colspan=2> 
								<h3>
									<?php print _('Home Address') ?>
								</h3>
							</td>
						</tr>
						<tr>
							<td colspan=2> 
								<p>
									<?php print _('This address will be used for all members of the family. If an individual within the family needs a different address, this can be set through Data Updater after admission.') ?> 
								</p>
							</td>
						</tr>
						<tr>
							<td> 
								<b><?php print _('Home Address') ?> *</b><br/>
								<span style="font-size: 90%"><i><?php print _('Unit, Building, Street') ?></i></span>
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
								<b><?php print _('Home Address') ?> (District) *</b><br/>
								<span style="font-size: 90%"><i><?php print _('County, State, District') ?></i></span>
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
								<b><?php print _('Home Address (Country)') ?> *</b><br/>
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
									print "<option value='Please select...'>" . _('Please select...') . "</option>" ;
									while ($rowSelect=$resultSelect->fetch()) {
										$selected="" ;
										if ($rowSelect["printable_name"]==$row["homeAddressCountry"]) {
											$selected="selected" ;
										}
										print "<option $selected value='" . $rowSelect["printable_name"] . "'>" . htmlPrep(_($rowSelect["printable_name"])) . "</option>" ;
									}
									?>				
								</select>
								<script type="text/javascript">
									var homeAddressCountry=new LiveValidation('homeAddressCountry');
									homeAddressCountry.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print _('Select something!') ?>"});
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
										<?php print _('Parent/Guardian 1') ?>
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
										<?php print _('The parent is already a Gibbon user, and so their details cannot be edited in this view.') ?>
									</p>
								</td>
							</tr>
							<tr>
								<td> 
									<b><?php print _('Surname') ?></b><br/>
									<span style="font-size: 90%"><i><?php print _('Family name as shown in ID documents.') ?></i></span>
								</td>
								<td class="right">
									<input readonly name='parent1surname' maxlength=30 value="<?php print $row["parent1surname"] ?>" type="text" style="width: 300px">
								</td>
							</tr>
							<tr>
								<td> 
									<b><?php print _('Preferred Name') ?></b><br/>
									<span style="font-size: 90%"><i><?php print _('Most common name, alias, nickname, etc.') ?></i></span>
								</td>
								<td class="right">
									<input readonly name='parent1preferredName' maxlength=30 value="<?php print $row["parent1preferredName"] ?>" type="text" style="width: 300px">
								</td>
							</tr>
							
							<tr>
								<td> 
									<b><?php print _('Relationship') ?> *</b><br/>
								</td>
								<td class="right">
									<select name="parent1relationship" id="parent1relationship" style="width: 302px">
										<option value="Please select..."><?php print _('Please select...') ?></option>
										<option <?php if ($row["parent1relationship"]=="Mother") { print "selected" ; } ?> value="Mother"><?php print _('Mother') ?></option>
										<option <?php if ($row["parent1relationship"]=="Father") { print "selected" ; } ?> value="Father"><?php print _('Father') ?></option>
										<option <?php if ($row["parent1relationship"]=="Step-Mother") { print "selected" ; } ?> value="Step-Mother"><?php print _('Step-Mother') ?></option>
										<option <?php if ($row["parent1relationship"]=="Step-Father") { print "selected" ; } ?> value="Step-Father"><?php print _('Step-Father') ?></option>
										<option <?php if ($row["parent1relationship"]=="Adoptive Parent") { print "selected" ; } ?> value="Adoptive Parent"><?php print _('Adoptive Parent') ?></option>
										<option <?php if ($row["parent1relationship"]=="Guardian") { print "selected" ; } ?> value="Guardian"><?php print _('Guardian') ?></option>
										<option <?php if ($row["parent1relationship"]=="Grandmother") { print "selected" ; } ?> value="Grandmother"><?php print _('Grandmother') ?></option>
										<option <?php if ($row["parent1relationship"]=="Grandfather") { print "selected" ; } ?> value="Grandfather"><?php print _('Grandfather') ?></option>
										<option <?php if ($row["parent1relationship"]=="Aunt") { print "selected" ; } ?> value="Aunt"><?php print _('Aunt') ?></option>
										<option <?php if ($row["parent1relationship"]=="Uncle") { print "selected" ; } ?> value="Uncle"><?php print _('Uncle') ?></option>
										<option <?php if ($row["parent1relationship"]=="Nanny/Helper") { print "selected" ; } ?> value="Nanny/Helper"><?php print _('Nanny/Helper') ?></option>
										<option <?php if ($row["parent1relationship"]=="Other") { print "selected" ; } ?> value="Other"><?php print _('Other') ?></option>
									</select>
									<script type="text/javascript">
										var parent1relationship=new LiveValidation('parent1relationship');
										parent1relationship.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print _('Select something!') ?>"});
									 </script>
								</td>
							</tr>
							<input name='parent1gibbonPersonID' value="<?php print $row["parent1gibbonPersonID"] ?>" type="hidden">
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
										<?php print _('Parent/Guardian') ?> <?php print $i ?>
										<?php
										if ($i==1) {
											print "<span style='font-size: 75%'>" . _('(e.g. mother)') . "</span>" ;
										}
										else if ($i==2 AND $row["parent1gibbonPersonID"]=="") {
											print "<span style='font-size: 75%'>" .  _('(e.g. father)') . "</span>" ;
										}
										?>
									</h3>
								</td>
							</tr>
							<tr>
								<td colspan=2> 
									<h4><?php print sprintf(_('Parent/Guardian %1$s Personal Data'), $i) ?></h4>
								</td>
							</tr>
							<tr>
								<td> 
									<b><?php print _('Title') ?><?php if ($i==1) { print " *" ;}?></b><br/>
									<span style="font-size: 90%"><i></i></span>
								</td>
								<td class="right">
									<select style="width: 302px" id="<?php print "parent$i" ?>title" name="<?php print "parent$i" ?>title">
										<?php
										if ($i==1) {
											?>
											<option value="Please select..."><?php print _('Please select...') ?></option>
										<?php
										}
										else {
											?>
											<option value=""></option>
											<?php
										}
										?>
										<option <?php if ($row["parent$i" . "title"]=="Ms. ") { print "selected" ; } ?> value="Ms. "><?php print _('Ms') ?></option>
										<option <?php if ($row["parent$i" . "title"]=="Miss ") { print "selected" ; } ?> value="Miss "><?php print _('Miss') ?></option>
										<option <?php if ($row["parent$i" . "title"]=="Mr. ") { print "selected" ; } ?> value="Mr. "><?php print _('Mr.') ?></option>
										<option <?php if ($row["parent$i" . "title"]=="Mrs. ") { print "selected" ; } ?> value="Mrs. "><?php print _('Mrs.') ?></option>
										<option <?php if ($row["parent$i" . "title"]=="Dr. ") { print "selected" ; } ?> value="Dr. "><?php print _('Dr.') ?></option>
									</select>
									
									<?php
									if ($i==1) {
										?>
										<script type="text/javascript">
											var <?php print "parent$i" ?>title=new LiveValidation('<?php print "parent$i" ?>title');
											<?php print "parent$i" ?>title.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print _('Select something!') ?>"});
										 </script>
										 <?php
									}
									?>
								</td>
							</tr>
							<tr>
								<td> 
									<b><?php print _('Surname') ?><?php if ($i==1) { print " *" ;}?></b><br/>
									<span style="font-size: 90%"><i><?php print _('Family name as shown in ID documents.') ?></i></span>
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
									<b><?php print _('First Name') ?><?php if ($i==1) { print " *" ;}?></b><br/>
									<span style="font-size: 90%"><i><?php print _('First name as shown in ID documents.') ?></i></span>
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
									<b><?php print _('Preferred Name') ?><?php if ($i==1) { print " *" ;}?></b><br/>
									<span style="font-size: 90%"><i><?php print _('Most common name, alias, nickname, etc.') ?></i></span>
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
									<b><?php print _('Official Name') ?><?php if ($i==1) { print " *" ;}?></b><br/>
									<span style="font-size: 90%"><i><?php print _('Full name as shown in ID documents.') ?></i></span>
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
									<b><?php print _('Name In Characters') ?></b><br/>
									<span style="font-size: 90%"><i><?php print _('Chinese or other character-based name.') ?></i></span>
								</td>
								<td class="right">
									<input name="<?php print "parent$i" ?>nameInCharacters" id="<?php print "parent$i" ?>nameInCharacters" maxlength=20 value="<?php print $row["parent$i" . "nameInCharacters"] ;?>" type="text" style="width: 300px">
								</td>
							</tr>
							<tr>
								<td> 
									<b><?php print _('Gender') ?><?php if ($i==1) { print " *" ;}?></b><br/>
								</td>
								<td class="right">
									<select name="<?php print "parent$i" ?>gender" id="<?php print "parent$i" ?>gender" style="width: 302px">
										<option value="Please select..."><?php print _('Please select...') ?></option>
										<option <?php if ($row["parent$i" . "gender"]=="F") { print "selected" ; } ?> value="F">F</option>
										<option <?php if ($row["parent$i" . "gender"]=="M") { print "selected" ; } ?> value="M">M</option>
									</select>
									<?php
									if ($i==1) {
										?>
										<script type="text/javascript">
											var <?php print "parent$i" ?>gender=new LiveValidation('<?php print "parent$i" ?>gender');
											<?php print "parent$i" ?>gender.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print _('Select something!') ?>"});
										 </script>
										 <?php
									}
									?>
								</td>
							</tr>
							<tr>
								<td> 
									<b><?php print _('Relationship') ?><?php if ($i==1) { print " *" ;}?></b><br/>
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
										<option <?php if ($row["parent" . $i . "relationship"]=="Mother") { print "selected" ; } ?> value="Mother"><?php print _('Mother') ?></option>
										<option <?php if ($row["parent" . $i . "relationship"]=="Father") { print "selected" ; } ?> value="Father"><?php print _('Father') ?></option>
										<option <?php if ($row["parent" . $i . "relationship"]=="Step-Mother") { print "selected" ; } ?> value="Step-Mother"><?php print _('Step-Mother') ?></option>
										<option <?php if ($row["parent" . $i . "relationship"]=="Step-Father") { print "selected" ; } ?> value="Step-Father"><?php print _('Step-Father') ?></option>
										<option <?php if ($row["parent" . $i . "relationship"]=="Adoptive Parent") { print "selected" ; } ?> value="Adoptive Parent"><?php print _('Adoptive Parent') ?></option>
										<option <?php if ($row["parent" . $i . "relationship"]=="Guardian") { print "selected" ; } ?> value="Guardian"><?php print _('Guardian') ?></option>
										<option <?php if ($row["parent" . $i . "relationship"]=="Grandmother") { print "selected" ; } ?> value="Grandmother"><?php print _('Grandmother') ?></option>
										<option <?php if ($row["parent" . $i . "relationship"]=="Grandfather") { print "selected" ; } ?> value="Grandfather"><?php print _('Grandfather') ?></option>
										<option <?php if ($row["parent" . $i . "relationship"]=="Aunt") { print "selected" ; } ?> value="Aunt"><?php print _('Aunt') ?></option>
										<option <?php if ($row["parent" . $i . "relationship"]=="Uncle") { print "selected" ; } ?> value="Uncle"><?php print _('Uncle') ?></option>
										<option <?php if ($row["parent" . $i . "relationship"]=="Nanny/Helper") { print "selected" ; } ?> value="Nanny/Helper"><?php print _('Nanny/Helper') ?></option>
										<option <?php if ($row["parent" . $i . "relationship"]=="Other") { print "selected" ; } ?> value="Other"><?php print _('Other') ?></option>
									</select>
									<?php
									if ($i==1) {
										?>
										<script type="text/javascript">
											var <?php print "parent$i" ?>relationship=new LiveValidation('<?php print "parent$i" ?>relationship');
											<?php print "parent$i" ?>relationship.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print _('Select something!') ?>"});
										 </script>
										 <?php
									}
									?>
								</td>
							</tr>
							
							<tr>
								<td colspan=2> 
									<h4><?php print sprintf(_('Parent/Guardian %1$s Background'), $i) ?></h4>
								</td>
							</tr>
							<tr>
								<td> 
									<b><?php print _('First Language') ?></b><br/>
								</td>
								<td class="right">
									<input name="<?php print "parent$i" ?>languageFirst" id="<?php print "parent$i" ?>languageFirst" maxlength=30 value="<?php print $row["parent" . $i ."languageFirst"] ?>" type="text" style="width: 300px">
								</td>
								<script type="text/javascript">
									$(function() {
										var availableTags=[
											<?php
											try {
												$dataAuto=array(); 
												$sqlAuto="SELECT DISTINCT languageFirst FROM gibbonApplicationForm ORDER BY languageFirst" ;
												$resultAuto=$connection2->prepare($sqlAuto);
												$resultAuto->execute($dataAuto);
											}
											catch(PDOException $e) { }
											while ($rowAuto=$resultAuto->fetch()) {
												print "\"" . $rowAuto["languageFirst"] . "\", " ;
											}
											?>
										];
										$( "#<?php print 'parent' . $i ?>languageFirst" ).autocomplete({source: availableTags});
									});
								</script>
							</tr>
							<tr>
								<td> 
									<b><?php print _('Second Language') ?></b><br/>
								</td>
								<td class="right">
									<input name="<?php print "parent$i" ?>languageSecond" id="<?php print "parent$i" ?>languageSecond" maxlength=30 value="<?php print $row["parent" . $i ."languageSecond"] ?>" type="text" style="width: 300px">
								</td>
								<script type="text/javascript">
									$(function() {
										var availableTags=[
											<?php
											try {
												$dataAuto=array(); 
												$sqlAuto="SELECT DISTINCT languageSecond FROM gibbonApplicationForm ORDER BY languageSecond" ;
												$resultAuto=$connection2->prepare($sqlAuto);
												$resultAuto->execute($dataAuto);
											}
											catch(PDOException $e) { }
											while ($rowAuto=$resultAuto->fetch()) {
												print "\"" . $rowAuto["languageSecond"] . "\", " ;
											}
											?>
										];
										$( "#<?php print 'parent' . $i ?>languageSecond" ).autocomplete({source: availableTags});
									});
								</script>
							</tr>
							<tr>
								<td> 
									<b><?php print _('Citizenship') ?></b><br/>
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
												print "<option value='" . $rowSelect["printable_name"] . "'>" . htmlPrep(_($rowSelect["printable_name"])) . "</option>" ;
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
										print "<b>" . _('National ID Card Number') . "</b><br/>" ;
									}
									else {
										print "<b>" . $_SESSION[$guid]["country"] . " " . _('ID Card Number') . "</b><br/>" ;
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
										print "<b>" . _('Visa Expiry Date') . "</b><br/>" ;
									}
									else {
										print "<b>" . $_SESSION[$guid]["country"] . " " . _('Visa Expiry Date') . "</b><br/>" ;
									}
									print "<span style='font-size: 90%'><i>Format " ; if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; } print ". " . _('If relevant.') . "</i></span>" ;
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
									<h4><?php print sprintf(_('Parent/Guardian %1$s Contact'), $i) ?></h4>
								</td>
							</tr>
							<tr>
								<td> 
									<b><?php print _('Email') ?><?php if ($i==1) { print " *" ;}?></b><br/>
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
										<b><?php print _('Phone') ?> <?php print $y ; if ($i==1 AND $y==1) { print " *" ;}?></b><br/>
										<span style="font-size: 90%"><i><?php print _('Type, country code, number.') ?></i></span>
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
												print "<option $selected value='" . $rowSelect["iddCountryCode"] . "'>" . htmlPrep($rowSelect["iddCountryCode"]) . " - " .  htmlPrep(_($rowSelect["printable_name"])) . "</option>" ;
											}
											?>				
										</select>
										<select style="width: 70px" name="<?php print "parent$i" ?>phone<?php print $y ?>Type">
											<option <?php if ($row["parent" . $i . "phone" . $y . "Type"]=="") { print "selected" ; }?> value=""></option>
											<option <?php if ($row["parent" . $i . "phone" . $y . "Type"]=="Mobile") { print "selected" ; }?> value="Mobile"><?php print _('Mobile') ?></option>
											<option <?php if ($row["parent" . $i . "phone" . $y . "Type"]=="Home") { print "selected" ; }?> value="Home"><?php print _('Home') ?></option>
											<option <?php if ($row["parent" . $i . "phone" . $y . "Type"]=="Work") { print "selected" ; }?> value="Work"><?php print _('Work') ?></option>
											<option <?php if ($row["parent" . $i . "phone" . $y . "Type"]=="Fax") { print "selected" ; }?> value="Fax"><?php print _('Fax') ?></option>
											<option <?php if ($row["parent" . $i . "phone" . $y . "Type"]=="Pager") { print "selected" ; }?> value="Pager"><?php print _('Pager') ?></option>
											<option <?php if ($row["parent" . $i . "phone" . $y . "Type"]=="Other") { print "selected" ; }?> value="Other"><?php print _('Other') ?></option>
										</select>
									</td>
								</tr>
								<?php
							}
							?>							
							
							<tr>
								<td colspan=2> 
									<h4><?php print _('Employment') ?></h4>
								</td>
							</tr>
							<tr>
								<td> 
									<b><?php print _('Profession') ?></b><br/>
								</td>
								<td class="right">
									<input name="<?php print "parent$i" ?>profession" id="<?php print "parent$i" ?>profession" maxlength=30 value="<?php print $row["parent$i" . "profession"] ;?>" type="text" style="width: 300px">
								</td>
							</tr>
							<tr>
								<td> 
									<b><?php print _('Employer') ?></b><br/>
								</td>
								<td class="right">
									<input name="<?php print "parent$i" ?>employer" id="<?php print "parent$i" ?>employer" maxlength=30 value="<?php print $row["parent$i" . "employer"] ;?>" type="text" style="width: 300px">
								</td>
							</tr>
							<?php
						}
					}
					else {
						?>
						<input type="hidden" name="gibbonFamily" value="TRUE">
						<tr class='break'>
							<td colspan=2> 
								<h3><?php print _('Family') ?></h3>
							</td>
						</tr>
						<tr>
							<td colspan=2> 
								<p><?php print sprintf(_('The applying family is already a member of %1$s.'), $_SESSION[$guid]["organisationName"]) ?></p>
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
												print _("Family Name") ;
											print "</th>" ;
											print "<th>" ;
												print _("Parents") ;
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
							<h3><?php print _('Siblings') ?></h3>
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
										print _("Sibling Name") ;
									print "</th>" ;
									print "<th>" ;
										print _("Date of Birth") . "<br/><span style='font-size: 80%'>" ; if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; } print "</span>" ;
									print "</th>" ;
									print "<th>" ;
										print _("School Attending") ;
									print "</th>" ;
									print "<th>" ;
										print _("Joining Date") . "<br/><span style='font-size: 80%'>" ;if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; } print "</span>" ;
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
					if ($languageOptionsActive=="On") {
						?>
						<tr class='break'>
							<td colspan=2> 
								<h3><?php print _('Language Selection') ?></h3>
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
								<b><?php print _('Language Choice') ?></b><br/>
								<span style="font-size: 90%"><i><?php print _('Please choose preferred additional language to study.') ?></i></span>
							</td>
							<td class="right">
								<select name="languageChoice" id="languageChoice" style="width: 302px">
									<?php
									print "<option value='Please select...'>" . _('Please select...') . "</option>" ;
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
								<b><?php print _('Language Choice Experience') ?></b><br/>
								<span style="font-size: 90%"><i><?php print _('Has the applicant studied the selected language before? If so, please describe the level and type of experience.') ?></i></span><br/> 					
								<textarea name="languageChoiceExperience" id="languageChoiceExperience" rows=5 style="width:738px; margin: 5px 0px 0px 0px"><?php print htmlPrep($row["languageChoiceExperience"]) ;?></textarea>
							</td>
						</tr>
						<?php
					}		
					?>
		
					<tr class='break'>
						<td colspan=2> 
							<h3><?php print _('Scholarships') ?></h3>
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
							<b><?php print _('Interest') ?></b><br/>
							<span style="font-size: 90%"><i><?php print _('Indicate if you are interested in a scholarship.') ?></i></span><br/>
						</td>
						<td class="right">
							<input <?php if ($row["scholarshipInterest"]=="Y") { print "checked" ; } ?> type="radio" id="scholarshipInterest" name="scholarshipInterest" class="type" value="Y" /> <?php print _('Yes') ?>
							<input <?php if ($row["scholarshipInterest"]=="N") { print "checked" ; } ?> type="radio" id="scholarshipInterest" name="scholarshipInterest" class="type" value="N" /><?php print _('No') ?>
						</td>
					</tr>
					<tr>
						<td> 
							<b>Required?</b><br/>
							<span style="font-size: 90%"><i>Is a scholarship <b>required</b> for you to take up a place at <?php print $_SESSION[$guid]["organisationNameShort"] ?>?</i></span><br/>
						</td>
						<td class="right">
							<input <?php if ($row["scholarshipRequired"]=="Y") { print "checked" ; } ?> type="radio" id="scholarshipRequired" name="scholarshipRequired" class="type" value="Y" /> <?php print _('Yes') ?>
							<input <?php if ($row["scholarshipRequired"]=="N") { print "checked" ; } ?> type="radio" id="scholarshipRequired" name="scholarshipRequired" class="type" value="N" /> <?php print _('No') ?>
						</td>
					</tr>
					
					
					<tr class='break'>
						<td colspan=2> 
							<h3><?php print _('Payment') ?></h3>
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
							<p><?php print _('If you choose family, future invoices will be sent according to family contact preferences, which can be changed at a later date by contacting the school. For example you may wish both parents to receive the invoice, or only one. Alternatively, if you choose Company, you can choose for all or only some fees to be covered by the specified company.') ?></p>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print _('Send Invoices To') ?></b><br/>
						</td>
						<td class="right">
							<input <?php if ($row["payment"]=="Family") { print "checked" ; } ?> type="radio" name="payment" value="Family" class="payment" /> <?php print _('Family') ?>
							<input <?php if ($row["payment"]=="Company") { print "checked" ; } ?> type="radio" name="payment" value="Company" class="payment" /> <?php print _('Company') ?>
						</td>
					</tr>
					<tr id="companyNameRow">
						<td> 
							<b><?php print _('Company Name') ?></b><br/>
						</td>
						<td class="right">
							<input name="companyName" id="companyName" maxlength=100 value="<?php print $row["companyName"] ?>" type="text" style="width: 300px">
						</td>
					</tr>
					<tr id="companyContactRow">
						<td> 
							<b><?php print _('Company Contact Person') ?></b><br/>
						</td>
						<td class="right">
							<input name="companyContact" id="companyContact" maxlength=100 value="<?php print $row["companyContact"] ?>" type="text" style="width: 300px">
						</td>
					</tr>
					<tr id="companyAddressRow">
						<td> 
							<b><?php print _('Company Address') ?></b><br/>
						</td>
						<td class="right">
							<input name="companyAddress" id="companyAddress" maxlength=255 value="<?php print $row["companyAddress"] ?>" type="text" style="width: 300px">
						</td>
					</tr>
					<tr id="companyEmailRow">
						<td> 
							<b><?php print _('Company Email') ?></b><br/>
						</td>
						<td class="right">
							<input name="companyEmail" id="companyEmail" maxlength=255 value="<?php print $row["companyEmail"] ?>" type="text" style="width: 300px">
							<script type="text/javascript">
								var companyEmail=new LiveValidation('companyEmail');
								companyEmail.add(Validate.Email);
							</script>
						</td>
					</tr>
					<tr id="companyCCFamilyRow">
						<td> 
							<b><?php print _('CC Family?') ?></b><br/>
							<span style="font-size: 90%"><i><?php print _('Should the family be sent a copy of billing emails?') ?></i></span>
						</td>
						<td class="right">
							<select name="companyCCFamily" id="companyCCFamily" style="width: 302px">
								<option <?php if ($row["companyCCFamily"]=="N") { print "selected" ; } ?> value="N" /> <?php print _('No') ?>
								<option <?php if ($row["companyCCFamily"]=="Y") { print "selected" ; } ?> value="Y" /> <?php print _('Yes') ?>
							</select>
						</td>
					</tr>
					<tr id="companyPhoneRow">
						<td> 
							<b><?php print _('Company Phone') ?></b><br/>
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
								<b><?php print _('Company All?') ?></b><br/>
								<span style="font-size: 90%"><i><?php print _('Should all items be billed to the specified company, or just some?') ?></i></span>
							</td>
							<td class="right">
								<input type="radio" name="companyAll" value="Y" class="companyAll" <?php if ($row["companyAll"]=="Y" OR $row["companyAll"]=="") { print "checked" ; } ?> /> <?php print _('All') ?>
								<input type="radio" name="companyAll" value="N" class="companyAll" <?php if ($row["companyAll"]=="N") { print "checked" ; } ?> /> <?php print _('Selected') ?>
							</td>
						</tr>
						<tr id="companyCategoriesRow">
							<td> 
								<b><?php print _('Company Fee Categories') ?></b><br/>
								<span style="font-size: 90%"><i><?php print _('If the specified company is not paying all fees, which categories are they paying?') ?></i></span>
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
								<h3><?php print _('Supporting Documents') ?></h3>
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
										<span style="font-size: 90%"><i><?php print _('This value cannot be changed.') ?></i></span>
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
							<h3><?php print _('Miscellaneous') ?></h3>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print _('How Did You Hear About Us?') ?></b><br/>
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
							<b><?php print _('Tell Us More') ?></b><br/>
							<span style="font-size: 90%"><i><?php print _('The name of a person or link to a website.') ?></i></span>
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
								<span style="font-size: 90%"><i>* <?php print _("denotes a required field") ; ?></i></span>
							</td>
							<td class="right">
								<input type="hidden" name="address" value="<?php print $_SESSION[$guid]["address"] ?>">
								<input name="gibbonSchoolYearID" id="gibbonSchoolYearID" value="<?php print $gibbonSchoolYearID ?>" type="hidden">
								<input type="hidden" name="gibbonApplicationFormID" value="<?php print $row["gibbonApplicationFormID"] ?>">
								<input type="submit" value="<?php print _("Submit") ; ?>">
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