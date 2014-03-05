<?
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
		print "You do not have access to this action." ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/User Admin/applicationForm_manage.php&gibbonSchoolYearID=" . $_GET["gibbonSchoolYearID"] . "'>Manage Application Forms</a> > </div><div class='trailEnd'>Edit Form</div>" ;
	print "</div>" ;
	
	//Check if school year specified
	$gibbonApplicationFormID=$_GET["gibbonApplicationFormID"];
	$gibbonSchoolYearID=$_GET["gibbonSchoolYearID"] ;
	$search=$_GET["search"] ;
	if ($gibbonApplicationFormID=="" OR $gibbonSchoolYearID=="") {
		print "<div class='error'>" ;
			print "You have not specified one or more required parameters." ;
		print "</div>" ;
	}
	else {
		try {
			$data=array("gibbonApplicationFormID"=>$gibbonApplicationFormID); 
			$sql="SELECT * FROM gibbonApplicationForm WHERE gibbonApplicationFormID=:gibbonApplicationFormID" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
		}

		if ($result->rowCount()!=1) {
			print "<div class='error'>" ;
				print "The selected application does not exist." ;
			print "</div>" ;
		}
		else {
			if (isset($_GET["updateReturn"])) { $updateReturn=$_GET["updateReturn"] ; } else { $updateReturn="" ; }
			$updateReturnMessage ="" ;
			$class="error" ;
			if (!($updateReturn=="")) {
				if ($updateReturn=="fail0") {
					$updateReturnMessage ="Your request failed because you do not have access to this action." ;	
				}
				else if ($updateReturn=="fail1") {
					$updateReturnMessage ="Your request failed because your inputs were invalid." ;	
				}
				else if ($updateReturn=="fail2") {
					$updateReturnMessage ="Your request failed due to a database error." ;	
				}
				else if ($updateReturn=="fail3") {
					$updateReturnMessage ="Your request failed because your inputs were invalid." ;	
				}
				else if ($updateReturn=="success1") {
					$updateReturnMessage ="Your request was completed successfully., but status could not be updated." ;	
				}
				else if ($updateReturn=="success0") {
					$updateReturnMessage ="Your request was completed successfully." ;	
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
					print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/User Admin/applicationForm_manage.php&gibbonSchoolYearID=$gibbonSchoolYearID&search=$search'>Back to Search Results</a> | " ;
				}
				print "<a target='_blank' href='" . $_SESSION[$guid]["absoluteURL"] . "/report.php?q=/modules/" . $_SESSION[$guid]["module"] . "/applicationForm_manage_edit_print.php&gibbonApplicationFormID=$gibbonApplicationFormID'><img title='Print' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/print.png'/></a>" ;
			print "</div>" ;
			?>
			<form method="post" action="<? print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/applicationForm_manage_editProcess.php?search=$search" ?>" enctype="multipart/form-data">
				<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
					<tr class='break'>
						<td colspan=2> 
							<h3>For Office Use</h3>
						</td>
					</tr>
					<tr>
						<td> 
							<b>Application ID *</b><br/>
							<span style="font-size: 90%"><i>This value cannot be changed.</i></span>
						</td>
						<td class="right">
							<input readonly name="gibbonApplicationFormID" id="gibbonApplicationFormID" value="<? print htmlPrep($row["gibbonApplicationFormID"]) ?>" type="text" style="width: 300px">
						</td>
					</tr>
					<tr>
						<td> 
							<b>Priority *</b><br/>
							<span style="font-size: 90%"><i>Higher priority applicants appear first in list of applications.</i></span>
						</td>
						<td class="right">
							<select name="priority" id="priority" style="width: 302px">
								<option <? if ($row["priority"]=="9") { print "selected" ; } ?> value="9">9</option>
								<option <? if ($row["priority"]=="8") { print "selected" ; } ?> value="8">8</option>
								<option <? if ($row["priority"]=="7") { print "selected" ; } ?> value="7">7</option>
								<option <? if ($row["priority"]=="6") { print "selected" ; } ?> value="6">6</option>
								<option <? if ($row["priority"]=="5") { print "selected" ; } ?> value="5">5</option>
								<option <? if ($row["priority"]=="4") { print "selected" ; } ?> value="4">4</option>
								<option <? if ($row["priority"]=="3") { print "selected" ; } ?> value="3">3</option>
								<option <? if ($row["priority"]=="2") { print "selected" ; } ?> value="2">2</option>
								<option <? if ($row["priority"]=="1") { print "selected" ; } ?> value="1">1</option>
								<option <? if ($row["priority"]=="0") { print "selected" ; } ?> value="0">0</option>
								<option <? if ($row["priority"]=="-1") { print "selected" ; } ?> value="-1">-1</option>
								<option <? if ($row["priority"]=="-2") { print "selected" ; } ?> value="-2">-2</option>
								<option <? if ($row["priority"]=="-3") { print "selected" ; } ?> value="-3">-3</option>
								<option <? if ($row["priority"]=="-4") { print "selected" ; } ?> value="-4">-4</option>
								<option <? if ($row["priority"]=="-5") { print "selected" ; } ?> value="-5">-5</option>
								<option <? if ($row["priority"]=="-6") { print "selected" ; } ?> value="-6">-6</option>
								<option <? if ($row["priority"]=="-7") { print "selected" ; } ?> value="-7">-7</option>
								<option <? if ($row["priority"]=="-8") { print "selected" ; } ?> value="-8">-8</option>
								<option <? if ($row["priority"]=="-9") { print "selected" ; } ?> value="-9">-9</option>
							</select>
						</td>
					</tr>
					<?
					if ($row["status"]!="Accepted") {
						?>
						<tr>
							<td> 
								<b>Status *</b><br/>
								<span style="font-size: 90%"><i>Manually set status. "Approved" not permitted.</i></span>
							</td>
							<td class="right">
								<select name="status" id="status" style="width: 302px">
									<option <? if ($row["status"]=="Pending") { print "selected" ; } ?> value="Pending">Pending</option>
									<option <? if ($row["status"]=="Rejected") { print "selected" ; } ?> value="Rejected">Rejected</option>
									<option <? if ($row["status"]=="Withdrawn") { print "selected" ; } ?> value="Withdrawn">Withdrawn</option>
								</select>
							</td>
						</tr>
						<?
					}
					else {
						?>
						<tr>
							<td> 
								<b>Status *</b><br/>
								<span style="font-size: 90%"><i>This value cannot be changed.</i></span>
							</td>
							<td class="right">
								<input readonly name="status" id="status" maxlength=20 value="<? print htmlPrep($row["status"]) ?>" type="text" style="width: 300px">
							</td>
						</tr>
						<?
					}
					$milestonesMasterRaw=getSettingByScope($connection2, "Application Form", "milestones") ;
					if ($milestonesMasterRaw!="") {
						?>
						<tr>
							<td> 
								<b>Milestones</b><br/>
								<span style="font-size: 90%"><i></i></span>
							</td>
							<td class="right">
								<?
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
						<?
					}
					?>
					<tr>
						<td> 
							<b>Start Date</b><br/>
							<span style="font-size: 90%"><i>Student's intended first day at school.<br/>dd/mm/yyyy</i></span>
						</td>
						<td class="right">
							<input name="dateStart" id="dateStart" maxlength=10 value="<? print dateConvertBack($guid, $row["dateStart"]) ?>" type="text" style="width: 300px">
							<script type="text/javascript">
								var dateStart=new LiveValidation('dateStart');
								dateStart.add( Validate.Format, {pattern: <? if ($_SESSION[$guid]["i18n"]["dateFormatRegEx"]=="") {  print "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i" ; } else { print $_SESSION[$guid]["i18n"]["dateFormatRegEx"] ; } ?>, failureMessage: "Use <? if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?>." } ); 
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
							<b>Year of Entry *</b><br/>
							<span style="font-size: 90%"><i>When will the student join?</i></span>
						</td>
						<td class="right">
							<select name="gibbonSchoolYearIDEntry" id="gibbonSchoolYearIDEntry" style="width: 302px">
								<?
								print "<option value='Please select...'>Please select...</option>" ;
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
								gibbonSchoolYearIDEntry.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "Select something!"});
							 </script>
						</td>
					</tr>
					<tr>
						<td> 
							<b>Year Group at Entry *</b><br/>
							<span style="font-size: 90%"><i>Which year level will student enter.</i></span>
						</td>
						<td class="right">
							<select name="gibbonYearGroupIDEntry" id="gibbonYearGroupIDEntry" style="width: 302px">
								<?
								print "<option value='Please select...'>Please select...</option>" ;
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
									print "<option $selected value='" . $rowSelect["gibbonYearGroupID"] . "'>" . htmlPrep($rowSelect["name"]) . "</option>" ;
								}
								?>				
							</select>
							<script type="text/javascript">
								var gibbonYearGroupIDEntry=new LiveValidation('gibbonYearGroupIDEntry');
								gibbonYearGroupIDEntry.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "Select something!"});
							 </script>
						</td>
					</tr>
					<?
					$dayTypeOptions=getSettingByScope($connection2, 'User Admin', 'dayTypeOptions') ;
					if ($dayTypeOptions!="") {
						?>
						<tr>
							<td> 
								<b>Day Type</b><br/>
								<span style="font-size: 90%"><i><? print getSettingByScope($connection2, 'User Admin', 'dayTypeText') ; ?></i></span>
							</td>
							<td class="right">
								<select name="dayType" id="dayType" style="width: 302px">
									<?
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
						<?
					}	
					?>
					<tr>
						<td> 
							<b>Roll Group at Entry</b><br/>
							<span style="font-size: 90%">If set, the student will automatically be enrolled on Accept.</span>
						</td>
						<td class="right">
							<select name="gibbonRollGroupID" id="gibbonRollGroupID" style="width: 302px">
								<?
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
					
					<?
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
								<b>Payment *</b><br/>
								<span style="font-size: 90%"><i>Has payment (<? print $currency . $applicationFee ?>) been made for this application.</i></span>
							</td>
							<td class="right">
								<select name="paymentMade" id="paymentMade" style="width: 302px">
									<option <? if ($row["paymentMade"]=="N") { print "selected" ; } ?> value='N'>N</option>	
									<option <? if ($row["paymentMade"]=="Y") { print "selected" ; } ?> value='Y'>Y</option>	
									<option <? if ($row["paymentMade"]=="Exemption") { print "selected" ; } ?> value='Exemption'>Exemption</option>				
								</select>
							</td>
						</tr>
						<?
						if ($row["paypalPaymentToken"]!="" OR $row["paypalPaymentPayerID"]!="" OR $row["paypalPaymentTransactionID"]!="" OR $row["paypalPaymentReceiptID"]!="") {
							?>
							<tr>
								<td style='text-align: right' colspan=2> 
									<span style="font-size: 90%"><i>
										<?
											if ($row["paypalPaymentToken"]!="") {
												print "PayPal Payment Token: " . $row["paypalPaymentToken"] . "<br/>" ;
											}
											if ($row["paypalPaymentPayerID"]!="") {
												print "PayPal Payment Payer ID: " . $row["paypalPaymentPayerID"] . "<br/>" ;
											}
											if ($row["paypalPaymentTransactionID"]!="") {
												print "PayPal Payment Transaction ID: " . $row["paypalPaymentTransactionID"] . "<br/>" ;
											}
											if ($row["paypalPaymentReceiptID"]!="") {
												print "PayPal Payment Receipt ID: " . $row["paypalPaymentReceiptID"] . "<br/>" ;
											}
										?>
									</i></span>
								</td>
							</tr>
							<?
						}
					}
					
					?>
					<tr>
						<td colspan=2 style='padding-top: 15px'> 
							<b>Notes</b><br/>
							<textarea name="notes" id="notes" rows=5 style="width:738px; margin: 5px 0px 0px 0px"><? print htmlPrep($row["notes"]) ?></textarea>
						</td>
					</tr>
					
					<tr class='break'>
						<td colspan=2> 
							<h3>Student</h3>
						</td>
					</tr>
					
					<tr>
						<td colspan=2> 
							<h4>Student Personal Data</h4>
						</td>
					</tr>
					<tr>
						<td> 
							<b>Surname *</b><br/>
							<span style="font-size: 90%"><i>Family name as shown in ID documents.</i></span>
						</td>
						<td class="right">
							<input name="surname" id="surname" maxlength=30 value="<? print $row["surname"] ?>" type="text" style="width: 300px">
							<script type="text/javascript">
								var surname=new LiveValidation('surname');
								surname.add(Validate.Presence);
							 </script>
						</td>
					</tr>
					<tr>
						<td> 
							<b>First Name *</b><br/>
							<span style="font-size: 90%"><i>First name as shown in ID documents.</i></span>
						</td>
						<td class="right">
							<input name="firstName" id="firstName" maxlength=30 value="<? print $row["firstName"] ?>" type="text" style="width: 300px">
							<script type="text/javascript">
								var firstName=new LiveValidation('firstName');
								firstName.add(Validate.Presence);
							 </script>
						</td>
					</tr>
					<tr>
						<td> 
							<b>Preferred Name *</b><br/>
							<span style="font-size: 90%"><i>Most common name, alias, nickname, etc.</i></span>
						</td>
						<td class="right">
							<input name="preferredName" id="preferredName" maxlength=30 value="<? print $row["preferredName"] ?>" type="text" style="width: 300px">
							<script type="text/javascript">
								var preferredName=new LiveValidation('preferredName');
								preferredName.add(Validate.Presence);
							 </script>
						</td>
					</tr>
					<tr>
						<td> 
							<b>Official Name *</b><br/>
							<span style="font-size: 90%"><i>Full name as shown in ID documents.</i></span>
						</td>
						<td class="right">
							<input name="officialName" id="officialName" maxlength=150 value="<? print $row["officialName"] ?>" type="text" style="width: 300px">
							<script type="text/javascript">
								var officialName=new LiveValidation('officialName');
								officialName.add(Validate.Presence);
							 </script>
						</td>
					</tr>
					<tr>
						<td> 
							<b>Name In Characters</b><br/>
							<span style="font-size: 90%"><i>Chinese or other character-based name.</i></span>
						</td>
						<td class="right">
							<input name="nameInCharacters" id="nameInCharacters" maxlength=20 value="<? print $row["nameInCharacters"] ?>" type="text" style="width: 300px">
						</td>
					</tr>
					<tr>
						<td> 
							<b>Gender *</b><br/>
						</td>
						<td class="right">
							<select name="gender" id="gender" style="width: 302px">
								<option value="Please select...">Please select...</option>
								<option <? if ($row["gender"]=="F") { print "selected" ; } ?> value="F">F</option>
								<option <? if ($row["gender"]=="M") { print "selected" ; } ?> value="M">M</option>
							</select>
							<script type="text/javascript">
								var gender=new LiveValidation('gender');
								gender.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "Select something!"});
							 </script>
						</td>
					</tr>
					<tr>
						<td> 
							<b>Date of Birth *</b><br/>
							<span style="font-size: 90%"><i>dd/mm/yyyy</i></span>
						</td>
						<td class="right">
							<input name="dob" id="dob" maxlength=10 value="<? print dateConvertBack($guid, $row["dob"]) ?>" type="text" style="width: 300px">
							<script type="text/javascript">
								var dob=new LiveValidation('dob');
								dob.add( Validate.Format, {pattern: <? if ($_SESSION[$guid]["i18n"]["dateFormatRegEx"]=="") {  print "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i" ; } else { print $_SESSION[$guid]["i18n"]["dateFormatRegEx"] ; } ?>, failureMessage: "Use <? if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?>." } ); 
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
							<h4>Student Background</h4>
						</td>
					</tr>
					<tr>
						<td> 
							<b>Home Language</b><br/>
							<span style="font-size: 90%"><i>The primary language used in the student's home.</i></span>
						</td>
						<td class="right">
							<input name="languageHome" id="languageHome" maxlength=30 value="<? print $row["languageHome"] ?>" type="text" style="width: 300px">
						</td>
						<script type="text/javascript">
							$(function() {
								var availableTags=[
									<?
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
							<b>First Language</b><br/>
							<span style="font-size: 90%"><i>Student's native/first/mother language. </i></span>
						</td>
						<td class="right">
							<input name="languageFirst" id="languageFirst" maxlength=30 value="<? print $row["languageFirst"] ?>" type="text" style="width: 300px">
						</td>
						<script type="text/javascript">
							$(function() {
								var availableTags=[
									<?
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
							<b>Second Language</b><br/>
						</td>
						<td class="right">
							<input name="languageSecond" id="languageSecond" maxlength=30 value="<? print $row["languageSecond"] ?>" type="text" style="width: 300px">
						</td>
						<script type="text/javascript">
							$(function() {
								var availableTags=[
									<?
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
							<b>Third Language</b><br/>
						</td>
						<td class="right">
							<input name="languageThird" id="languageThird" maxlength=30 value="<? print $row["languageThird"] ?>" type="text" style="width: 300px">
						</td>
						<script type="text/javascript">
							$(function() {
								var availableTags=[
									<?
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
							<b>Country of Birth</b><br/>
						</td>
						<td class="right">
							<select name="countryOfBirth" id="countryOfBirth" style="width: 302px">
								<?
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
									print "<option $selected value='" . $rowSelect["printable_name"] . "'>" . htmlPrep($rowSelect["printable_name"]) . "</option>" ;
								}
								?>				
							</select>
						</td>
					</tr>
					<tr>
						<td> 
							<b>Citizenship</b><br/>
						</td>
						<td class="right">
							<select name="citizenship1" id="citizenship1" style="width: 302px">
								<?
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
							<b>Citizenship Passport Number</b><br/>
						</td>
						<td class="right">
							<input name="citizenship1Passport" id="citizenship1Passport" maxlength=30 value="<? print $row["citizenship1Passport"] ?>" type="text" style="width: 300px">
						</td>
					</tr>
					<tr>
						<td> 
						<?
						if ($_SESSION[$guid]["country"]=="") {
							print "<b>National ID Card Number</b><br/>" ;
						}
						else {
							print "<b>" . $_SESSION[$guid]["country"] . " ID Card Number</b><br/>" ;
						}
						?>
					</td>
						<td class="right">
							<input name="nationalIDCardNumber" id="nationalIDCardNumber" maxlength=30 value="<? print $row["nationalIDCardNumber"] ?>" type="text" style="width: 300px">
						</td>
					</tr>
					<tr>
						<td> 
							<?
							if ($_SESSION[$guid]["country"]=="") {
								print "<b>Residency/Visa Type</b><br/>" ;
							}
							else {
								print "<b>" . $_SESSION[$guid]["country"] . " Residency/Visa Type</b><br/>" ;
							}
							?>
						</td>
						<td class="right">
							<?
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
							<?
							if ($_SESSION[$guid]["country"]=="") {
								print "<b>Visa Expiry Date</b><br/>" ;
							}
							else {
								print "<b>" . $_SESSION[$guid]["country"] . " Visa Expiry Date</b><br/>" ;
							}
							print "<span style='font-size: 90%'><i>dd/mm/yyyy. If relevant.</i></span>" ;
							?>
						</td>
						<td class="right">
							<input name="visaExpiryDate" id="visaExpiryDate" maxlength=10 value="<? print dateConvertBack($guid, $row["visaExpiryDate"]) ?>" type="text" style="width: 300px">
							<script type="text/javascript">
								var visaExpiryDate=new LiveValidation('visaExpiryDate');
								visaExpiryDate.add( Validate.Format, {pattern: <? if ($_SESSION[$guid]["i18n"]["dateFormatRegEx"]=="") {  print "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i" ; } else { print $_SESSION[$guid]["i18n"]["dateFormatRegEx"] ; } ?>, failureMessage: "Use <? if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?>." } ); 
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
							<h4>Student Contact</h4>
						</td>
					</tr>
					<tr>
						<td> 
							<b>Email</b><br/>
						</td>
						<td class="right">
							<input name="email" id="email" maxlength=50 value="<? print $row["email"] ?>" type="text" style="width: 300px">
							<script type="text/javascript">
								var email=new LiveValidation('email');
								email.add(Validate.Email);
							 </script>
						</td>
					</tr>
					<?
					for ($i=1; $i<3; $i++) {
						?>
						<tr>
							<td> 
								<b>Phone <? print $i ?></b><br/>
								<span style="font-size: 90%"><i>Type, country code, number</i></span>
							</td>
							<td class="right">
								<input name="phone<? print $i ?>" id="phone<? print $i ?>" maxlength=20 value="<? print $row["phone" . $i] ?>" type="text" style="width: 160px">
								<select name="phone<? print $i ?>CountryCode" id="phone<? print $i ?>CountryCode" style="width: 60px">
									<?
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
								<select style="width: 70px" name="phone<? print $i ?>Type">
									<option <? if ($row["phone" . $i . "Type"]=="") { print "selected" ; }?> value=""></option>
									<option <? if ($row["phone" . $i . "Type"]=="Mobile") { print "selected" ; }?> value="Mobile">Mobile</option>
									<option <? if ($row["phone" . $i . "Type"]=="Home") { print "selected" ; }?> value="Home">Home</option>
									<option <? if ($row["phone" . $i . "Type"]=="Work") { print "selected" ; }?> value="Work">Work</option>
									<option <? if ($row["phone" . $i . "Type"]=="Fax") { print "selected" ; }?> value="Fax">Fax</option>
									<option <? if ($row["phone" . $i . "Type"]=="Pager") { print "selected" ; }?> value="Pager">Pager</option>
									<option <? if ($row["phone" . $i . "Type"]=="Other") { print "selected" ; }?> value="Other">Other</option>
								</select>
							</td>
						</tr>
						<?
					}
					?>					
					<tr>
						<td colspan=2> 
							<h4>Student Medical & Development</h4>
						</td>
					</tr>
					<tr>
						<td colspan=2 style='padding-top: 15px'> 
							<b>Medical Information</b><br/>
							<span style="font-size: 90%"><i>Please indicate any medical conditions.</i></span><br/>
							<textarea name="medicalInformation" id="medicalInformation" rows=5 style="width:738px; margin: 5px 0px 0px 0px"><? print htmlPrep($row["medicalInformation"]) ?></textarea>
						</td>
					</tr>
					<tr>
						<td colspan=2 style='padding-top: 15px'> 
							<b>Development Information</b><br/>
							<span style="font-size: 90%"><i>Provide any comments or information concerning your child’s development that may be relevant to your child’s performance in the classroom or elsewhere? (Incorrect or withheld information may affect continued enrolment).</i></span><br/> 					
							<textarea name="developmentInformation" id="developmentInformation" rows=5 style="width:738px; margin: 5px 0px 0px 0px"><? print htmlPrep($row["developmentInformation"]) ?></textarea>
						</td>
					</tr>
					
					
					
					<tr>
						<td colspan=2> 
							<h4>Previous Schools</h4>
							<p>Please give information on the last two schools attended by the applicant.</p>
						</td>
					</tr>
					<tr>
						<td colspan=2> 
							<?
							print "<table cellspacing='0' style='width: 100%'>" ;
								print "<tr class='head'>" ;
									print "<th>" ;
										print "School Name" ;
									print "</th>" ;
									print "<th>" ;
										print "Address" ;
									print "</th>" ;
									print "<th>" ;
										print "Grades<br/>Attended" ;
									print "</th>" ;
									print "<th>" ;
										print "Language of<br/>Instruction" ;
									print "</th>" ;
									print "<th>" ;
										print "Joining Date<br/><span style='font-size: 80%'>dd/mm/yyyy</span>" ;
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
														<?
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
													$( "#schoolLanguage<? print $i ?>" ).autocomplete({source: availableTags});
												});
											</script>
											<?
										print "</td>" ;
										print "<td>" ;
											?>
											<input name="<? print "schoolDate$i" ?>" id="<? print "schoolDate$i" ?>" maxlength=10 value="<? print dateConvertBack($guid, $row["schoolDate$i"]) ?>" type="text" style="width:90px; float: left">
											<script type="text/javascript">
												$(function() {
													$( "#<? print "schoolDate$i" ?>" ).datepicker();
												});
											</script>
											<?
										print "</td>" ;
									print "</tr>" ;
								}
							print "</table>" ;
							?>
						</td>
					</tr>
					
					
					
					<?
					if ($row["gibbonFamilyID"]=="") {
						?>
						<input type="hidden" name="gibbonFamily" value="FALSE">
						
						<tr class='break'>
							<td colspan=2> 
								<h3>
									Home Address
								</h3>
							</td>
						</tr>
						<tr>
							<td colspan=2> 
								<p>
									This address will be used for all members of the family. If an individual within the family needs a different address, this can be set through Data Updater after admission. 
								</p>
							</td>
						</tr>
						<tr>
							<td> 
								<b>Home Address *</b><br/>
								<span style="font-size: 90%"><i>Unit, Building, Street</i></span>
							</td>
							<td class="right">
								<input name="homeAddress" id="homeAddress" maxlength=255 value="<? print $row["homeAddress"] ?>" type="text" style="width: 300px">
								<script type="text/javascript">
									var homeAddress=new LiveValidation('homeAddress');
									homeAddress.add(Validate.Presence);
								 </script>
							</td>
						</tr>
						<tr>
							<td> 
								<b>Home Address (District) *</b><br/>
								<span style="font-size: 90%"><i>County, State, District</i></span>
							</td>
							<td class="right">
								<input name="homeAddressDistrict" id="homeAddressDistrict" maxlength=30 value="<? print $row["homeAddressDistrict"] ?>" type="text" style="width: 300px">
							</td>
							<script type="text/javascript">
								$(function() {
									var availableTags=[
										<?
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
								<b>Address (Country) *</b><br/>
							</td>
							<td class="right">
								<select name="homeAddressCountry" id="homeAddressCountry" style="width: 302px">
									<?
									try {
										$dataSelect=array(); 
										$sqlSelect="SELECT printable_name FROM gibbonCountry ORDER BY printable_name" ;
										$resultSelect=$connection2->prepare($sqlSelect);
										$resultSelect->execute($dataSelect);
									}
									catch(PDOException $e) { }
									print "<option value='Please select...'>Please select...</option>" ;
									while ($rowSelect=$resultSelect->fetch()) {
										$selected="" ;
										if ($rowSelect["printable_name"]==$row["homeAddressCountry"]) {
											$selected="selected" ;
										}
										print "<option $selected value='" . $rowSelect["printable_name"] . "'>" . htmlPrep($rowSelect["printable_name"]) . "</option>" ;
									}
									?>				
								</select>
								<script type="text/javascript">
									var homeAddressCountry=new LiveValidation('homeAddressCountry');
									homeAddressCountry.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "Select something!"});
								 </script>
							</td>
						</tr>
						<?
						
						if ($row["parent1gibbonPersonID"]!="") {
							$start=2 ;
							?>
							<tr class='break'>
								<td colspan=2> 
									<h3>
										Parent/Guardian 1
										<?
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
										The parent is already a Gibbon user, and so their details cannot be edited in this view.
									</p>
								</td>
							</tr>
							<tr>
								<td> 
									<b>Surname</b><br/>
									<span style="font-size: 90%"><i>Family name as shown in ID documents.</i></span>
								</td>
								<td class="right">
									<input readonly name='parent1surname' maxlength=30 value="<? print $row["parent1surname"] ?>" type="text" style="width: 300px">
								</td>
							</tr>
							<tr>
								<td> 
									<b>Preferred Name</b><br/>
									<span style="font-size: 90%"><i>Most common name, alias, nickname, etc.</i></span>
								</td>
								<td class="right">
									<input readonly name='parent1preferredName' maxlength=30 value="<? print $row["parent1preferredName"] ?>" type="text" style="width: 300px">
								</td>
							</tr>
							
							<tr>
								<td> 
									<b>Relationship *</b><br/>
								</td>
								<td class="right">
									<select name="parent1relationship" id="parent1relationship" style="width: 302px">
										<option value="Please select...">Please select...</option>
										<option <? if ($row["parent1relationship"]=="Mother") { print "selected" ; } ?> value="Mother">Mother</option>
										<option <? if ($row["parent1relationship"]=="Father") { print "selected" ; } ?> value="Father">Father</option>
										<option <? if ($row["parent1relationship"]=="Step-Mother") { print "selected" ; } ?> value="Step-Mother">Step-Mother</option>
										<option <? if ($row["parent1relationship"]=="Step-Father") { print "selected" ; } ?> value="Step-Father">Step-Father</option>
										<option <? if ($row["parent1relationship"]=="Adoptive Parent") { print "selected" ; } ?> value="Adoptive Parent">Adoptive Parent</option>
										<option <? if ($row["parent1relationship"]=="Guardian") { print "selected" ; } ?> value="Guardian">Guardian</option>
										<option <? if ($row["parent1relationship"]=="Grandmother") { print "selected" ; } ?> value="Grandmother">Grandmother</option>
										<option <? if ($row["parent1relationship"]=="Grandfather") { print "selected" ; } ?> value="Grandfather">Grandfather</option>
										<option <? if ($row["parent1relationship"]=="Aunt") { print "selected" ; } ?> value="Aunt">Aunt</option>
										<option <? if ($row["parent1relationship"]=="Uncle") { print "selected" ; } ?> value="Uncle">Uncle</option>
										<option <? if ($row["parent1relationship"]=="Nanny/Helper") { print "selected" ; } ?> value="Nanny/Helper">Nanny/Helper</option>
										<option <? if ($row["parent1relationship"]=="Other") { print "selected" ; } ?> value="Other">Other</option>
									</select>
									<script type="text/javascript">
										var parent1relationship=new LiveValidation('parent1relationship');
										parent1relationship.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "Select something!"});
									 </script>
								</td>
							</tr>
							<input name='parent1gibbonPersonID' value="<? print $row["parent1gibbonPersonID"] ?>" type="hidden">
							<?
						}
						else {
							$start=1 ;
						}
						for ($i=$start;$i<3;$i++) {
							?>
							<tr class='break'>
								<td colspan=2> 
									<h3>
										Parent/Guardian <? print $i ?>
										<?
										if ($i==1) {
											print "<span style='font-size: 75%'> (e.g. mother)</span>" ;
										}
										else if ($i==2 AND $row["parent1gibbonPersonID"]=="") {
											print "<span style='font-size: 75%'> (e.g. father)</span>" ;
										}
										?>
									</h3>
								</td>
							</tr>
							<tr>
								<td colspan=2> 
									<h4>Parent/Guardian <? print $i ?> Personal Data</h4>
								</td>
							</tr>
							<tr>
								<td> 
									<b>Title<? if ($i==1) { print " *" ;}?></b><br/>
									<span style="font-size: 90%"><i></i></span>
								</td>
								<td class="right">
									<select style="width: 302px" id="<? print "parent$i" ?>title" name="<? print "parent$i" ?>title">
										<?
										if ($i==1) {
											?>
											<option value="Please select...">Please select...</option>
										<?
										}
										else {
											?>
											<option value=""></option>
											<?
										}
										?>
										<option <? if ($row["parent$i" . "title"]=="Ms. ") { print "selected" ; } ?> value="Ms. ">Ms.</option>
										<option <? if ($row["parent$i" . "title"]=="Miss ") { print "selected" ; } ?> value="Miss ">Miss</option>
										<option <? if ($row["parent$i" . "title"]=="Mr. ") { print "selected" ; } ?> value="Mr. ">Mr.</option>
										<option <? if ($row["parent$i" . "title"]=="Mrs. ") { print "selected" ; } ?> value="Mrs. ">Mrs.</option>
										<option <? if ($row["parent$i" . "title"]=="Dr. ") { print "selected" ; } ?> value="Dr. ">Dr.</option>
									</select>
									
									<?
									if ($i==1) {
										?>
										<script type="text/javascript">
											var <? print "parent$i" ?>title=new LiveValidation('<? print "parent$i" ?>title');
											<? print "parent$i" ?>title.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "Select something!"});
										 </script>
										 <?
									}
									?>
								</td>
							</tr>
							<tr>
								<td> 
									<b>Surname<? if ($i==1) { print " *" ;}?></b><br/>
									<span style="font-size: 90%"><i>Family name as shown in ID documents.</i></span>
								</td>
								<td class="right">
									<input name="<? print "parent$i" ?>surname" id="<? print "parent$i" ?>surname" maxlength=30 value="<? print $row["parent$i" . "surname"] ;?>" type="text" style="width: 300px">
									<?
									if ($i==1) {
										?>
										<script type="text/javascript">
											var <? print "parent$i" ?>surname=new LiveValidation('<? print "parent$i" ?>surname');
											<? print "parent$i" ?>surname.add(Validate.Presence);
										 </script>
										 <?
									}
									?>
								</td>
							</tr>
							<tr>
								<td> 
									<b>First Name<? if ($i==1) { print " *" ;}?></b><br/>
									<span style="font-size: 90%"><i>First name as shown in ID documents.</i></span>
								</td>
								<td class="right">
									<input name="<? print "parent$i" ?>firstName" id="<? print "parent$i" ?>firstName" maxlength=30 value="<? print $row["parent$i" . "firstName"] ;?>" type="text" style="width: 300px">
									<?
									if ($i==1) {
										?>
										<script type="text/javascript">
											var <? print "parent$i" ?>firstName=new LiveValidation('<? print "parent$i" ?>firstName');
											<? print "parent$i" ?>firstName.add(Validate.Presence);
										 </script>
										 <?
									}
									?>
								</td>
							</tr>
							<tr>
								<td> 
									<b>Preferred Name<? if ($i==1) { print " *" ;}?></b><br/>
									<span style="font-size: 90%"><i>Most common name, alias, nickname, etc.</i></span>
								</td>
								<td class="right">
									<input name="<? print "parent$i" ?>preferredName" id="<? print "parent$i" ?>preferredName" maxlength=30 value="<? print $row["parent$i" . "preferredName"] ;?>" type="text" style="width: 300px">
									<?
									if ($i==1) {
										?>
										<script type="text/javascript">
											var <? print "parent$i" ?>preferredName=new LiveValidation('<? print "parent$i" ?>preferredName');
											<? print "parent$i" ?>preferredName.add(Validate.Presence);
										 </script>
										 <?
									}
									?>
								</td>
							</tr>
							<tr>
								<td> 
									<b>Official Name<? if ($i==1) { print " *" ;}?></b><br/>
									<span style="font-size: 90%"><i>Full name as shown in ID documents.</i></span>
								</td>
								<td class="right">
									<input name="<? print "parent$i" ?>officialName" id="<? print "parent$i" ?>officialName" maxlength=30 value="<? print $row["parent$i" . "officialName"] ;?>" type="text" style="width: 300px">
									<?
									if ($i==1) {
										?>
										<script type="text/javascript">
											var <? print "parent$i" ?>officialName=new LiveValidation('<? print "parent$i" ?>officialName');
											<? print "parent$i" ?>officialName.add(Validate.Presence);
										 </script>
										 <?
									}
									?>
								</td>
							</tr>
							<tr>
								<td> 
									<b>Name In Characters</b><br/>
									<span style="font-size: 90%"><i>Chinese or other character-based name.</i></span>
								</td>
								<td class="right">
									<input name="<? print "parent$i" ?>nameInCharacters" id="<? print "parent$i" ?>nameInCharacters" maxlength=20 value="<? print $row["parent$i" . "nameInCharacters"] ;?>" type="text" style="width: 300px">
								</td>
							</tr>
							<tr>
								<td> 
									<b>Gender<? if ($i==1) { print " *" ;}?></b><br/>
								</td>
								<td class="right">
									<select name="<? print "parent$i" ?>gender" id="<? print "parent$i" ?>gender" style="width: 302px">
										<option value="Please select...">Please select...</option>
										<option <? if ($row["parent$i" . "gender"]=="F") { print "selected" ; } ?> value="F">F</option>
										<option <? if ($row["parent$i" . "gender"]=="M") { print "selected" ; } ?> value="M">M</option>
									</select>
									<?
									if ($i==1) {
										?>
										<script type="text/javascript">
											var <? print "parent$i" ?>gender=new LiveValidation('<? print "parent$i" ?>gender');
											<? print "parent$i" ?>gender.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "Select something!"});
										 </script>
										 <?
									}
									?>
								</td>
							</tr>
							<tr>
								<td> 
									<b>Relationship<? if ($i==1) { print " *" ;}?></b><br/>
								</td>
								<td class="right">
									<select name="<? print "parent$i" ?>relationship" id="<? print "parent$i" ?>relationship" style="width: 302px">
										<? 
										if ($i==1) { 
											print "<option value=\"Please select...\">Please select...</option>" ;
										}
										else {
											print "<option value=\"\"></option>" ;
										}?>
										<option <? if ($row["parent" . $i . "relationship"]=="Mother") { print "selected" ; } ?> value="Mother">Mother</option>
										<option <? if ($row["parent" . $i . "relationship"]=="Father") { print "selected" ; } ?> value="Father">Father</option>
										<option <? if ($row["parent" . $i . "relationship"]=="Step-Mother") { print "selected" ; } ?> value="Step-Mother">Step-Mother</option>
										<option <? if ($row["parent" . $i . "relationship"]=="Step-Father") { print "selected" ; } ?> value="Step-Father">Step-Father</option>
										<option <? if ($row["parent" . $i . "relationship"]=="Adoptive Parent") { print "selected" ; } ?> value="Adoptive Parent">Adoptive Parent</option>
										<option <? if ($row["parent" . $i . "relationship"]=="Guardian") { print "selected" ; } ?> value="Guardian">Guardian</option>
										<option <? if ($row["parent" . $i . "relationship"]=="Grandmother") { print "selected" ; } ?> value="Grandmother">Grandmother</option>
										<option <? if ($row["parent" . $i . "relationship"]=="Grandfather") { print "selected" ; } ?> value="Grandfather">Grandfather</option>
										<option <? if ($row["parent" . $i . "relationship"]=="Aunt") { print "selected" ; } ?> value="Aunt">Aunt</option>
										<option <? if ($row["parent" . $i . "relationship"]=="Uncle") { print "selected" ; } ?> value="Uncle">Uncle</option>
										<option <? if ($row["parent" . $i . "relationship"]=="Nanny/Helper") { print "selected" ; } ?> value="Nanny/Helper">Nanny/Helper</option>
										<option <? if ($row["parent" . $i . "relationship"]=="Other") { print "selected" ; } ?> value="Other">Other</option>
									</select>
									<?
									if ($i==1) {
										?>
										<script type="text/javascript">
											var <? print "parent$i" ?>relationship=new LiveValidation('<? print "parent$i" ?>relationship');
											<? print "parent$i" ?>relationship.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "Select something!"});
										 </script>
										 <?
									}
									?>
								</td>
							</tr>
							
							<tr>
								<td colspan=2> 
									<h4>Parent/Guardian <? print $i ?> Background</h4>
								</td>
							</tr>
							<tr>
								<td> 
									<b>First Language </b><br/>
								</td>
								<td class="right">
									<input name="<? print "parent$i" ?>languageFirst" id="<? print "parent$i" ?>languageFirst" maxlength=30 value="<? print $row["parent" . $i ."languageFirst"] ?>" type="text" style="width: 300px">
								</td>
								<script type="text/javascript">
									$(function() {
										var availableTags=[
											<?
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
										$( "#<? print 'parent' . $i ?>languageFirst" ).autocomplete({source: availableTags});
									});
								</script>
							</tr>
							<tr>
								<td> 
									<b>Second Language</b><br/>
								</td>
								<td class="right">
									<input name="<? print "parent$i" ?>languageSecond" id="<? print "parent$i" ?>languageSecond" maxlength=30 value="<? print $row["parent" . $i ."languageSecond"] ?>" type="text" style="width: 300px">
								</td>
								<script type="text/javascript">
									$(function() {
										var availableTags=[
											<?
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
										$( "#<? print 'parent' . $i ?>languageSecond" ).autocomplete({source: availableTags});
									});
								</script>
							</tr>
							<tr>
								<td> 
									<b>Citizenship</b><br/>
								</td>
								<td class="right">
									<select name="<? print "parent$i" ?>citizenship1" id="<? print "parent$i" ?>citizenship1" style="width: 302px">
										<?
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
									<?
									if ($_SESSION[$guid]["country"]=="") {
										print "<b>National ID Card Number</b><br/>" ;
									}
									else {
										print "<b>" . $_SESSION[$guid]["country"] . " ID Card Number</b><br/>" ;
									}
									?>
								</td>
								<td class="right">
									<input name="<? print "parent$i" ?>nationalIDCardNumber" id="<? print "parent$i" ?>nationalIDCardNumber" maxlength=30 value="<? print $row["parent$i" . "nationalIDCardNumber"] ;?>" type="text" style="width: 300px">
								</td>
							</tr>
							<tr>
								<td> 
									<?
									if ($_SESSION[$guid]["country"]=="") {
										print "<b>Residency/Visa Type</b><br/>" ;
									}
									else {
										print "<b>" . $_SESSION[$guid]["country"] . " Residency/Visa Type</b><br/>" ;
									}
									?>
								</td>
								<td class="right">
									<?
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
									<?
									if ($_SESSION[$guid]["country"]=="") {
										print "<b>Visa Expiry Date</b><br/>" ;
									}
									else {
										print "<b>" . $_SESSION[$guid]["country"] . " Visa Expiry Date</b><br/>" ;
									}
									print "<span style='font-size: 90%'><i>dd/mm/yyyy. If relevant.</i></span>" ;
									?>
								</td>
								<td class="right">
									<input name="<? print "parent$i" ?>visaExpiryDate" id="<? print "parent$i" ?>visaExpiryDate" maxlength=10 value="<? print dateConvertBack($guid, $row["parent" . $i . "visaExpiryDate"]) ?>" type="text" style="width: 300px">
									<script type="text/javascript">
										var <? print "parent$i" ?>visaExpiryDate=new LiveValidation('<? print "parent$i" ?>visaExpiryDate');
										<? print "parent$i" ?>visaExpiryDate.add( Validate.Format, {pattern: <? if ($_SESSION[$guid]["i18n"]["dateFormatRegEx"]=="") {  print "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i" ; } else { print $_SESSION[$guid]["i18n"]["dateFormatRegEx"] ; } ?>, failureMessage: "Use <? if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?>." } ); 
									 </script>
									 <script type="text/javascript">
										$(function() {
											$( "#<? print "parent$i" ?>visaExpiryDate" ).datepicker();
										});
									</script>
								</td>
							</tr>
							
							
							<tr>
								<td colspan=2> 
									<h4>Parent/Guardian <? print $i ?> Contact</h4>
								</td>
							</tr>
							<tr>
								<td> 
									<b>Email<? if ($i==1) { print " *" ;}?></b><br/>
								</td>
								<td class="right">
									<input name="<? print "parent$i" ?>email" id="<? print "parent$i" ?>email" maxlength=50 value="<? print $row["parent$i" . "email"] ;?>" type="text" style="width: 300px">
									<script type="text/javascript">
										var <? print "parent$i" ?>email=new LiveValidation('<? print "parent$i" ?>email');
										<? print "parent$i" ?>email.add(Validate.Email);
										<?
										if ($i==1) {
											print "parent$i" . "email.add(Validate.Presence);" ;
										}
										?>
									 </script>
								</td>
							</tr>
							<?
							for ($y=1; $y<3; $y++) {
								?>
								<tr>
									<td> 
										<b>Phone <? print $y ; if ($i==1 AND $y==1) { print " *" ;}?></b><br/>
										<span style="font-size: 90%"><i>Type, country code, number</i></span>
									</td>
									<td class="right">
										<input name="<? print "parent$i" ?>phone<? print $y ?>" id="<? print "parent$i" ?>phone<? print $y ?>" maxlength=20 value="<? print $row["parent" . $i . "phone" . $y] ?>" type="text" style="width: 160px">
										<?
										if ($i==1 AND $y==1) {
											?>
											<script type="text/javascript">
												var <? print "parent$i" ?>phone<? print $y ?>=new LiveValidation('<? print "parent$i" ?>phone<? print $y ?>');
												<? print "parent$i" ?>phone<? print $y ?>.add(Validate.Presence);
											 </script>
											<?
										}
										?>
										<select name="<? print "parent$i" ?>phone<? print $y ?>CountryCode" id="<? print "parent$i" ?>phone<? print $y ?>CountryCode" style="width: 60px">
											<?
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
												print "<option $selected value='" . $rowSelect["iddCountryCode"] . "'>" . htmlPrep($rowSelect["iddCountryCode"]) . " - " .  htmlPrep($rowSelect["printable_name"]) . "</option>" ;
											}
											?>				
										</select>
										<select style="width: 70px" name="<? print "parent$i" ?>phone<? print $y ?>Type">
											<option <? if ($row["parent" . $i . "phone" . $y . "Type"]=="") { print "selected" ; }?> value=""></option>
											<option <? if ($row["parent" . $i . "phone" . $y . "Type"]=="Mobile") { print "selected" ; }?> value="Mobile">Mobile</option>
											<option <? if ($row["parent" . $i . "phone" . $y . "Type"]=="Home") { print "selected" ; }?> value="Home">Home</option>
											<option <? if ($row["parent" . $i . "phone" . $y . "Type"]=="Work") { print "selected" ; }?> value="Work">Work</option>
											<option <? if ($row["parent" . $i . "phone" . $y . "Type"]=="Fax") { print "selected" ; }?> value="Fax">Fax</option>
											<option <? if ($row["parent" . $i . "phone" . $y . "Type"]=="Pager") { print "selected" ; }?> value="Pager">Pager</option>
											<option <? if ($row["parent" . $i . "phone" . $y . "Type"]=="Other") { print "selected" ; }?> value="Other">Other</option>
										</select>
									</td>
								</tr>
								<?
							}
							?>							
							
							<tr>
								<td colspan=2> 
									<h4>Employment</h4>
								</td>
							</tr>
							<tr>
								<td> 
									<b>Profession</b><br/>
								</td>
								<td class="right">
									<input name="<? print "parent$i" ?>profession" id="<? print "parent$i" ?>profession" maxlength=30 value="<? print $row["parent$i" . "profession"] ;?>" type="text" style="width: 300px">
								</td>
							</tr>
							<tr>
								<td> 
									<b>Employer</b><br/>
								</td>
								<td class="right">
									<input name="<? print "parent$i" ?>employer" id="<? print "parent$i" ?>employer" maxlength=30 value="<? print $row["parent$i" . "employer"] ;?>" type="text" style="width: 300px">
								</td>
							</tr>
							<?
						}
					}
					else {
						?>
						<input type="hidden" name="gibbonFamily" value="TRUE">
						<tr class='break'>
							<td colspan=2> 
								<h3>Family</h3>
							</td>
						</tr>
						<tr>
							<td colspan=2> 
								<p>The applying family is already a member of <? print $_SESSION[$guid]["organisationName"] ?>.</p>
							</td>
						</tr>
						<tr>
							<td colspan=2>
								<?
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
												print "Family Name" ;
											print "</th>" ;
											print "<th>" ;
												print "Parents" ;
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
						<?
					}
					?>
					<tr class='break'>
						<td colspan=2> 
							<h3>Siblings</h3>
						</td>
					</tr>
					<tr>
						<td colspan=2 style='padding-top: 0px'> 
							<p>Please give information on any siblings not currently studying at <? print $_SESSION[$guid]["organisationName"] ?>.</p>
						</td>
					</tr>
					<tr>
						<td colspan=2> 
							<?
							print "<table cellspacing='0' style='width: 100%'>" ;
								print "<tr class='head'>" ;
									print "<th>" ;
										print "Sibling Name" ;
									print "</th>" ;
									print "<th>" ;
										print "Date of Birth<br/><span style='font-size: 80%'>dd/mm/yyyy</span>" ;
									print "</th>" ;
									print "<th>" ;
										print "School Attending" ;
									print "</th>" ;
									print "<th>" ;
										print "Joining Date<br/><span style='font-size: 80%'>dd/mm/yyyy</span>" ;
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
											<input name="<? print "siblingDOB$i" ?>" id="<? print "siblingDOB$i" ?>" maxlength=10 value="<? print dateConvertBack($guid, $row["siblingDOB$i"]) ?>" type="text" style="width:90px; float: left"><br/>
											<script type="text/javascript">
												$(function() {
													$( "#<? print "siblingDOB$i" ?>" ).datepicker();
												});
											</script>
											<?
										print "</td>" ;
										print "<td>" ;
											print "<input name='siblingSchool$i' id='siblingSchool$i' maxlength=50 value='" . $row["siblingSchool$i"] . "' type='text' style='width:200px; float: left'>" ;
										print "</td>" ;
										print "<td>" ;
											?>
											<input name="<? print "siblingSchoolJoiningDate$i" ?>" id="<? print "siblingSchoolJoiningDate$i" ?>" maxlength=10 value="<? print dateConvertBack($guid, $row["siblingSchoolJoiningDate$i"]) ?>" type="text" style="width:90px; float: left">
											<script type="text/javascript">
												$(function() {
													$( "#<? print "siblingSchoolJoiningDate$i" ?>" ).datepicker();
												});
											</script>
											<?
										print "</td>" ;
									print "</tr>" ;
								}
							print "</table>" ;
							?>
						</td>
					</tr>
					
					<?
					$languageOptionsActive=getSettingByScope($connection2, 'Application Form', 'languageOptionsActive') ;
					if ($languageOptionsActive=="On") {
						?>
						<tr class='break'>
							<td colspan=2> 
								<h3>Language Selection</h3>
								<?
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
								<b>Language Choice</b><br/>
								<span style="font-size: 90%"><i>Please choose preferred additional language to study.</i></span>
							</td>
							<td class="right">
								<select name="languageChoice" id="languageChoice" style="width: 302px">
									<?
									print "<option value='Please select...'>Please select...</option>" ;
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
								<b>Language Choice Experience</b><br/>
								<span style="font-size: 90%"><i>Has the applicant studied the selected language before? If so, please describe the level and type of experience.</i></span><br/> 					
								<textarea name="languageChoiceExperience" id="languageChoiceExperience" rows=5 style="width:738px; margin: 5px 0px 0px 0px"><? print htmlPrep($row["languageChoiceExperience"]) ;?></textarea>
							</td>
						</tr>
						<?
					}		
					?>
		
					<tr class='break'>
						<td colspan=2> 
							<h3>Scholarships</h3>
							<?
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
							<b>Interest</b><br/>
							<span style="font-size: 90%"><i>Indicate if you are interested in a scholarship.</i></span><br/>
						</td>
						<td class="right">
							<input <? if ($row["scholarshipInterest"]=="Y") { print "checked" ; } ?> type="radio" id="scholarshipInterest" name="scholarshipInterest" class="type" value="Y" /> Yes
							<input <? if ($row["scholarshipInterest"]=="N") { print "checked" ; } ?> type="radio" id="scholarshipInterest" name="scholarshipInterest" class="type" value="N" /> No
						</td>
					</tr>
					<tr>
						<td> 
							<b>Required?</b><br/>
							<span style="font-size: 90%"><i>Is a scholarship <b>required</b> for you to take up a place at <? print $_SESSION[$guid]["organisationNameShort"] ?>?</i></span><br/>
						</td>
						<td class="right">
							<input <? if ($row["scholarshipRequired"]=="Y") { print "checked" ; } ?> type="radio" id="scholarshipRequired" name="scholarshipRequired" class="type" value="Y" /> Yes
							<input <? if ($row["scholarshipRequired"]=="N") { print "checked" ; } ?> type="radio" id="scholarshipRequired" name="scholarshipRequired" class="type" value="N" /> No
						</td>
					</tr>
					
					
					<tr class='break'>
						<td colspan=2> 
							<h3>Payment</h3>
						</td>
					</tr>
					<script type="text/javascript">
						/* Resource 1 Option Control */
						$(document).ready(function(){
							if ($('input[name=payment]:checked').val() == "Family" ) {
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
								if ($('input[name=companyAll]:checked').val() == "Y" ) {
									$("#companyCategoriesRow").css("display","none");
								}
							}
							
							$(".payment").click(function(){
								if ($('input[name=payment]:checked').val() == "Family" ) {
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
									if ($('input[name=companyAll]:checked').val() == "Y" ) {
										$("#companyCategoriesRow").css("display","none");
									} else {
										$("#companyCategoriesRow").slideDown("fast", $("#companyCategoriesRow").css("display","table-row")); 
									}
								}
							 });
							 
							 $(".companyAll").click(function(){
								if ($('input[name=companyAll]:checked').val() == "Y" ) {
									$("#companyCategoriesRow").css("display","none");
								} else {
									$("#companyCategoriesRow").slideDown("fast", $("#companyCategoriesRow").css("display","table-row")); 
								}
							 });
						});
					</script>
					<tr id="familyRow">
						<td colspan=2'>
							<p>If you choose family, future invoices will be sent according to family contact preferences, which can be changed at a later date by contacting the school. For example you may wish both parents to receive the invoice, or only one. Alternatively, if you choose Company, you can choose for all or only some fees to be covered by the specified company.</p>
						</td>
					</tr>
					<tr>
						<td> 
							<b>Send Invoices To</b><br/>
						</td>
						<td class="right">
							<input <? if ($row["payment"]=="Family") { print "checked" ; } ?> type="radio" name="payment" value="Family" class="payment" /> Family
							<input <? if ($row["payment"]=="Company") { print "checked" ; } ?> type="radio" name="payment" value="Company" class="payment" /> Company
						</td>
					</tr>
					<tr id="companyNameRow">
						<td> 
							<b>Company Name</b><br/>
						</td>
						<td class="right">
							<input name="companyName" id="companyName" maxlength=100 value="<? print $row["companyName"] ?>" type="text" style="width: 300px">
						</td>
					</tr>
					<tr id="companyContactRow">
						<td> 
							<b>Company Contact Person</b><br/>
						</td>
						<td class="right">
							<input name="companyContact" id="companyContact" maxlength=100 value="<? print $row["companyContact"] ?>" type="text" style="width: 300px">
						</td>
					</tr>
					<tr id="companyAddressRow">
						<td> 
							<b>Company Address</b><br/>
						</td>
						<td class="right">
							<input name="companyAddress" id="companyAddress" maxlength=255 value="<? print $row["companyAddress"] ?>" type="text" style="width: 300px">
						</td>
					</tr>
					<tr id="companyEmailRow">
						<td> 
							<b>Company Email</b><br/>
						</td>
						<td class="right">
							<input name="companyEmail" id="companyEmail" maxlength=255 value="<? print $row["companyEmail"] ?>" type="text" style="width: 300px">
							<script type="text/javascript">
								var companyEmail=new LiveValidation('companyEmail');
								companyEmail.add(Validate.Email);
							</script>
						</td>
					</tr>
					<tr id="companyCCFamilyRow">
						<td> 
							<b>CC Family?</b><br/>
							<span style="font-size: 90%"><i>Should the family be sent a copy of billing emails?</i></span>
						</td>
						<td class="right">
							<select name="companyCCFamily" id="companyCCFamily" style="width: 302px">
								<option <? if ($row["companyCCFamily"]=="N") { print "selected" ; } ?> value="N" /> No
								<option <? if ($row["companyCCFamily"]=="Y") { print "selected" ; } ?> value="Y" /> Yes
							</select>
						</td>
					</tr>
					<tr id="companyPhoneRow">
						<td> 
							<b>Company Phone</b><br/>
						</td>
						<td class="right">
							<input name="companyPhone" id="companyPhone" maxlength=20 value="<? print $row["companyPhone"] ?>" type="text" style="width: 300px">
						</td>
					</tr>
					<?
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
								<b>Company All?</b><br/>
								<span style="font-size: 90%"><i>Should all items be billed to the specified company, or just some?</i></span>
							</td>
							<td class="right">
								<input type="radio" name="companyAll" value="Y" class="companyAll" <? if ($row["companyAll"]=="Y" OR $row["companyAll"]=="") { print "checked" ; } ?> /> All
								<input type="radio" name="companyAll" value="N" class="companyAll" <? if ($row["companyAll"]=="N") { print "checked" ; } ?> /> Selected
							</td>
						</tr>
						<tr id="companyCategoriesRow">
							<td> 
								<b>Company Fee Categories</b><br/>
								<span style="font-size: 90%"><i>If the specified company is not paying all fees, which categories are they paying?</i></span>
							</td>
							<td class="right">
								<?
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
						<?
					}
					
					$requiredDocuments=getSettingByScope($connection2, "Application Form", "requiredDocuments") ;
					$requiredDocumentsCompulsory=getSettingByScope($connection2, "Application Form", "requiredDocumentsCompulsory") ;
					$count=0 ;
					if ($requiredDocuments!="" AND $requiredDocuments!=FALSE) {
						?>
						<tr class='break'>
							<td colspan=2> 
								<h3>Supporting Documents</h3>
							</td>
						</tr>
						<?
			
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
										<b><? print $document ; if ($requiredDocumentsCompulsory=="Y") { print " *" ; } ?></b><br/>
									</td>
									<td class="right">
										<?
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
								<?
							}
							else if ($resultFile->rowCount()==1) {
								$rowFile=$resultFile->fetch() ;
								?>
								<tr>
									<td> 
										<? print "<b>" . $rowFile["name"] . "</b><br/>" ?>
										<span style="font-size: 90%"><i>This value cannot be changed.</i></span>
									</td>
									<td class="right">
										<?
										print "<a target='_blank' href='" . $_SESSION[$guid]["absoluteURL"] . "/" . $rowFile["path"] . "'>Download</a>" ;
										?>
									</td>
								</tr>
								<?
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
								<? print getMaxUpload() ; ?>
								<input type="hidden" name="fileCount" value="<? print $count ?>">
							</td>
						</tr>
						<?
					}
					?>
					
					<tr class='break'>
						<td colspan=2> 
							<h3>Miscellaneous</h3>
						</td>
					</tr>
					<tr>
						<td> 
							<b>How Did You Hear About Us?</b><br/>
						</td>
						<td class="right">
							<?
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
							<b>Tell Us More </b><br/>
							<span style="font-size: 90%"><i>The name of a person or link to a website.</i></span>
						</td>
						<td class="right">
							<input name="howDidYouHearMore" id="howDidYouHearMore" maxlength=255 value="<? print $row["howDidYouHearMore"] ?>" type="text" style="width: 300px">
						</td>
					</tr>
					<?
					$privacySetting=getSettingByScope( $connection2, "User Admin", "privacy" ) ;
					$privacyBlurb=getSettingByScope( $connection2, "User Admin", "privacyBlurb" ) ;
					$privacyOptions=getSettingByScope( $connection2, "User Admin", "privacyOptions" ) ;
					if ($privacySetting=="Y" AND $privacyBlurb!="" AND $privacyOptions!="") {
						?>
						<tr>
							<td> 
								<b>Privacy *</b><br/>
								<span style="font-size: 90%"><i><? print htmlPrep($privacyBlurb) ?><br/>
								</i></span>
							</td>
							<td class="right">
								<?
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
					<?	
					}
					if ($proceed==TRUE) {
						?>
						<tr>
							<td>
								<span style="font-size: 90%"><i>* <? print _("denotes a required field") ; ?></i></span>
							</td>
							<td class="right">
								<input type="hidden" name="address" value="<? print $_SESSION[$guid]["address"] ?>">
								<input name="gibbonSchoolYearID" id="gibbonSchoolYearID" value="<? print $gibbonSchoolYearID ?>" type="hidden">
								<input type="hidden" name="gibbonApplicationFormID" value="<? print $row["gibbonApplicationFormID"] ?>">
								<input type="submit" value="<? print _("Submit") ; ?>">
							</td>
						</tr>
						<?
					}
					?>
				</table>
			</form>
			<?
		}
	}
}
?>