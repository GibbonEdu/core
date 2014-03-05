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

//Module includes (not needed because already called by index page)
//include "./modules/" . $_SESSION[$guid]["module"] . "/moduleFunctions.php" ;

if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print "You do not have access to this action." ;
	print "</div>" ;
}
else {
	if ($_SESSION[$guid]["email"]=="") {
		print "<div class='error'>" ;
			print "You do not have an email address set in Gibbon, and so cannot send out emails." ;
		print "</div>" ;
	}
	else {
		//Proceed!
		print "<div class='trail'>" ;
		print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > </div><div class='trailEnd'>New Message</div>" ;
		print "</div>" ;
		
		if (isset($_GET["addReturn"])) { $addReturn=$_GET["addReturn"] ; } else { $addReturn="" ; }
		$addReturnMessage ="" ;
		$class="error" ;
		if (!($addReturn=="")) {
			if ($addReturn=="fail0") {
				$addReturnMessage ="Your request failed because you do not have access to this action." ;	
			}
			else if ($addReturn=="fail2") {
				$addReturnMessage ="Your request failed due to a database error." ;	
			}
			else if ($addReturn=="fail3") {
				$addReturnMessage ="Your request failed because your inputs were invalid." ;	
			}
			else if ($addReturn=="fail4") {
				$addReturnMessage ="Add succeeded, but some or all messages could not be delivered." ;	
			}
			else if ($addReturn=="fail5") {
				$addReturnMessage ="Your request failed due to an attachment error." ;	
			}
			else if ($addReturn=="success0") {
				$addReturnMessage ="Add was successful: not all messages may arrive at their destination, but an attempt has been made to get them all out." ;
				if (is_numeric($_GET["emailCount"])) {
					if ($_GET["emailCount"]==1) {
						$addReturnMessage.=" " . $_GET["emailCount"] . " email was dispatched." ;	
					}
					else {
						$addReturnMessage.=" " . $_GET["emailCount"] . " emails were dispatched." ;	
					}
				}
				if (is_numeric($_GET["smsCount"]) AND is_numeric($_GET["smsBatchCount"])) {
					if ($_GET["smsCount"]==1) {
						$addReturnMessage.=" " . $_GET["smsCount"] . " SMS was dispatched " ;
					}
					else {
						$addReturnMessage.=" " . $_GET["smsCount"] . " SMSs were dispatched " ;	
					}
					if ($_GET["smsBatchCount"]==1) {
						$addReturnMessage.="in " . $_GET["smsBatchCount"] . " batch." ;
					}
					else {
						$addReturnMessage.="in " . $_GET["smsBatchCount"] . " batches." ;
					}	
				}
				
				$class="success" ;
			}
			print "<div class='$class'>" ;
				print $addReturnMessage;
			print "</div>" ;
		} 
		
		print "<div class='warning'>" ;
			print "Each family in Gibbon must have one parent who is contact priority 1, and who must be enabled to receive email and SMS messages from " . $_SESSION[$guid]["organisationNameShort"] . ". As a result, when targetting parents, you can be fairly certain that messages should get through to each family." ;
		print "</div>" ;
				
		?>
		<form method="post" action="<? print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/messenger_postProcess.php?address=" . $_GET["q"] ?>" enctype="multipart/form-data">
			<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
				<tr class='break'>
					<td colspan=2> 
						<h3>Delivery Mode</h3>
					</td>
				</tr>
				<?
				if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_byEmail")) {
					?>
					<script type="text/javascript">
						$(document).ready(function(){
							$("#emailRow").css("display","none");
							$(".email").click(function(){
								if ($('input[name=email]:checked').val() == "Y" ) {
									$("#emailRow").slideDown("fast", $("#emailRow").css("display","table-row")); 
								} else {
									$("#emailRow").css("display","none");
								}
							 });
						});
					</script>
					<tr>
						<td> 
							<b>Email *</b><br/>
							<span style="font-size: 90%"><i>Deliver this message to user's email account?<br/></i></span>
						</td>
						<td class="right">
							<input type="radio" name="email" class="email" value="Y"/> Yes
							<input checked type="radio" name="email" class="email" value="N"/> No
						</td>
					</tr>
					<tr id="emailRow">
						<td> 
							<b>Email From *</b><br/>
						</td>
						<td class="right">
							<?
							print "<select style='float: none; width:302px' name='from' id='from'>" ;
								print "<option value='" . $_SESSION[$guid]["email"] . "'>" . $_SESSION[$guid]["email"] . "</option>" ;
								if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_fromSchool") AND $_SESSION[$guid]["organisationEmail"]!="") {
									print "<option value='" . $_SESSION[$guid]["organisationEmail"] . "'>" . $_SESSION[$guid]["organisationEmail"] . "</option>" ;
								}
							print "</select>" ;
							?>
						</td>
					</tr>
					<?
				}
				if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_byMessageWall")) {
					?>
					<script type="text/javascript">
						$(document).ready(function(){
							$("#messageWallRow").css("display","none");
							$(".messageWall").click(function(){
								if ($('input[name=messageWall]:checked').val() == "Y" ) {
									$("#messageWallRow").slideDown("fast", $("#messageWallRow").css("display","table-row")); 
								} else {
									$("#messageWallRow").css("display","none");
								}
							 });
						});
					</script>
					<tr>
						<td> 
							<b>Message Wall *</b><br/>
							<span style="font-size: 90%"><i>Place this message on user's message wall?<br/></i></span>
						</td>
						<td class="right">
							<input type="radio" name="messageWall" class="messageWall" value="Y"/> Yes
							<input checked type="radio" name="messageWall" class="messageWall" value="N"/> No
						</td>
					</tr>
					<tr id="messageWallRow">
						<td> 
							<b>Publication Dates *</b><br/>
							<span style="font-size: 90%"><i>Select up to three individual dates.</br>Format <? if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?>.<br/></i></span>
						</td>
						<td class="right">
							<input name="date1" id="date1" maxlength=10 value="" type="text" style="width: 300px">
							<script type="text/javascript">
								var date1=new LiveValidation('date1');
								date1.add( Validate.Format, {pattern: <? if ($_SESSION[$guid]["i18n"]["dateFormatRegEx"]=="") {  print "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i" ; } else { print $_SESSION[$guid]["i18n"]["dateFormatRegEx"] ; } ?>, failureMessage: "Use <? if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?>." } ); 
							 </script>
							 <script type="text/javascript">
								$(function() {
									$( "#date1" ).datepicker();
								});
							</script>
							<br/>
							<input name="date2" id="date2" maxlength=10 value="" type="text" style="width: 300px; margin-top: 3px">
							<script type="text/javascript">
								var date2=new LiveValidation('date2');
								date2.add( Validate.Format, {pattern: <? if ($_SESSION[$guid]["i18n"]["dateFormatRegEx"]=="") {  print "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i" ; } else { print $_SESSION[$guid]["i18n"]["dateFormatRegEx"] ; } ?>, failureMessage: "Use <? if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?>." } ); 
							 </script>
							 <script type="text/javascript">
								$(function() {
									$( "#date2" ).datepicker();
								});
							</script>
							<br/>
							<input name="date3" id="date3" maxlength=10 value="" type="text" style="width: 300px; margin-top: 3px">
							<script type="text/javascript">
								var date3=new LiveValidation('date3');
								date3.add( Validate.Format, {pattern: <? if ($_SESSION[$guid]["i18n"]["dateFormatRegEx"]=="") {  print "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i" ; } else { print $_SESSION[$guid]["i18n"]["dateFormatRegEx"] ; } ?>, failureMessage: "Use <? if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?>." } ); 
							 </script>
							 <script type="text/javascript">
								$(function() {
									$( "#date3" ).datepicker();
								});
							</script>
						</td>
					</tr>
					<?
				}
				if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_bySMS")) {
					$smsUsername=getSettingByScope( $connection2, "Messenger", "smsUsername" ) ;
					$smsPassword=getSettingByScope( $connection2, "Messenger", "smsPassword" ) ;
					$smsURL=getSettingByScope( $connection2, "Messenger", "smsURL" ) ;
					$smsURLCredit=getSettingByScope( $connection2, "Messenger", "smsURLCredit" ) ;
					if ($smsUsername!="" AND $smsPassword!="" AND $smsURL!="") {
						?>
						<script type="text/javascript">
							$(document).ready(function(){
								$("#smsRow").css("display","none");
								$(".sms").click(function(){
									if ($('input[name=sms]:checked').val() == "Y" ) {
										$("#smsRow").slideDown("fast", $("#smsRow").css("display","table-row")); 
									} else {
										$("#smsRow").css("display","none");
									}
								 });
							});
						</script>
						<tr>
							<td> 
								<b>SMS *</b><br/>
								<span style="font-size: 90%"><i>Deliver this message to user's mobile phone?<br/></i></span>
							</td>
							<td class="right">
								<input type="radio" id="sms" name="sms" class="sms" value="Y"/> Yes
								<input checked type="radio" id="sms" name="sms" class="sms" value="N"/> No
							</td>
						</tr>
						<tr>
						<tr id="smsRow">
							<td colspan=2> 
								<div class='error' style='margin-top: 3px'>
									SMS messages are sent to local and overseas numbers, but not all countries are supported. Please see the SMS Gateway provider's documentation or error log to see which countries are not supported. The subject does not get sent, and all HTML tags are removed. Each message, to each recipient, will incur a charge (dependent on your SMS gateway provider). Messages over 140 characters will get broken into smaller messages, and will cost more.<br/>
									<br/>
									<?
									if ($smsURLCredit!="") {
										$query="?apiusername=" . $smsUsername . "&apipassword=" . $smsPassword ;        
										$result=@implode('', file($smsURLCredit . $query)) ;    
										if ($result>=0) {
											print "<b>Current balance: <u>$result credits</u></b>" ;
										} 
									}
									?>
								</div>
							</td>
						</tr>
						<?
					}
					else {
						?>
						<tr>
							<td colspan=2> 
								<div class='error' style='margin-top: 3px'>SMS NOT CONFIGURED. Please contact <a href='mailto:<? print $_SESSION[$guid]["organisationAdministratorEmail"] ?>'><? print $_SESSION[$guid]["organisationAdministratorName"] ?></a> for help.</div>
							</td>
						</tr>
						<?
					}
				}
				?>
				
				<tr class='break'>
					<td colspan=2> 
						<h3>Message Details</h3>
					</td>
				</tr>
				<tr>
					<td> 
						<b>Subject *</b><br/>
						<span style="font-size: 90%"><i></i></span>
					</td>
					<td class="right">
						<input name="subject" id="subject" maxlength=30 value="" type="text" style="width: 300px">
						<script type="text/javascript">
							var subject=new LiveValidation('subject');
							subject.add(Validate.Presence);
						 </script>
					</td>
				</tr>
				<tr>
					<td colspan=2> 
						<b>Body *</b>
						<? print getEditor($guid,  TRUE, "body", "", 20, true, true, false, true ) ?>
					</td>
				</tr>
				
				<tr class='break'>
					<td colspan=2> 
						<h3>Targets</h3>
					</td>
				</tr>
				<?
				if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_role")) {
					?>
					<script type="text/javascript">
						/* Role Control */
						$(document).ready(function(){
							$("#roleRow").css("display","none");
							$(".role").click(function(){
								if ($('input[name=role]:checked').val() == "Y" ) {
									$("#roleRow").slideDown("fast", $("#roleRow").css("display","table-row")); 
								} else {
									$("#roleRow").css("display","none");
								}
							 });
						});
					</script>
					<tr>
						<td> 
							<b>Role</b><br/>
							<span style="font-size: 90%"><i>Users of a certain type<br/></i></span>
						</td>
						<td class="right">
							<input type="radio" name="role" class="role" value="Y"/> Yes
							<input checked type="radio" name="role" class="role" value="N"/> No
						</td>
					</tr>
					<tr id="roleRow">
						<td style='background: none; background-color: #EDF7FF;'> 
							<b>Select Roles</b><br/>
							<span style="font-size: 90%"><i>Use Control and/or Shift to select multiple.</i></span>
						</td>
						<td style='background: none; background-color: #EDF7FF;' class="right">
							<select name="roles[]" id="roles[]" multiple style="width: 302px; height: 100px">
								<?
								try {
									$dataSelect=array(); 
									$sqlSelect="SELECT * FROM gibbonRole ORDER BY name" ;
									$resultSelect=$connection2->prepare($sqlSelect);
									$resultSelect->execute($dataSelect);
								}
								catch(PDOException $e) { }
								while ($rowSelect=$resultSelect->fetch()) {
									print "<option value='" . $rowSelect["gibbonRoleID"] . "'>" . htmlPrep($rowSelect["name"]) . " (" . htmlPrep($rowSelect["category"]) . ")</option>" ;
								}
								?>
							</select>
						</td>
					</tr>
					<?
				}
				if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_yearGroups_any")) {
					?>
					<script type="text/javascript">
						/* yearGroup Control */
						$(document).ready(function(){
							$("#yearGroupRow").css("display","none");
							$("#yearGroupRow2").css("display","none");
							$("#yearGroupRow3").css("display","none");
							$("#yearGroupRow4").css("display","none");
							$(".yearGroup").click(function(){
								if ($('input[name=yearGroup]:checked').val() == "Y" ) {
									$("#yearGroupRow").slideDown("fast", $("#yearGroupRow").css("display","table-row")); 
									$("#yearGroupRow2").slideDown("fast", $("#yearGroupRow2").css("display","table-row")); 
									$("#yearGroupRow3").slideDown("fast", $("#yearGroupRow3").css("display","table-row")); 
									$("#yearGroupRow4").slideDown("fast", $("#yearGroupRow4").css("display","table-row")); 
								} else {
									$("#yearGroupRow").css("display","none");
									$("#yearGroupRow2").css("display","none");
									$("#yearGroupRow3").css("display","none");
									$("#yearGroupRow4").css("display","none");
								}
							 });
						});
					</script>
					<tr>
						<td> 
							<b>Year Group</b><br/>
							<span style="font-size: 90%"><i>Students in year; all staff<br/></i></span>
						</td>
						<td class="right">
							<input type="radio" name="yearGroup" class="yearGroup" value="Y"/> Yes
							<input checked type="radio" name="yearGroup" class="yearGroup" value="N"/> No
						</td>
					</tr>
					<tr id="yearGroupRow">
						<td style='background: none; background-color: #EDF7FF;'> 
							<b>Select Year Groups</b><br/>
							<span style="font-size: 90%"><i>Use Control and/or Shift to select multiple.</i></span>
						</td>
						<td style='background: none; background-color: #EDF7FF;' class="right">
							<select name="yearGroups[]" id="yearGroups[]" multiple style="width: 302px; height: 100px">
								<?
								try {
									$dataSelect=array(); 
									$sqlSelect="SELECT * FROM gibbonYearGroup ORDER BY sequenceNumber" ;
									$resultSelect=$connection2->prepare($sqlSelect);
									$resultSelect->execute($dataSelect);
								}
								catch(PDOException $e) { }
								while ($rowSelect=$resultSelect->fetch()) {
									print "<option value='" . $rowSelect["gibbonYearGroupID"] . "'>" . htmlPrep($rowSelect["name"]) . "</option>" ;
								}
								?>
							</select>
						</td>
					</tr>
					<tr id="yearGroupRow3">
						<td style='background: none; background-color: #EDF7FF;'> 
							<b>Include staff?</b><br/>
						</td>
						<td style='background: none; background-color: #EDF7FF;' class="right">
							<select name="yearGroupsStaff" id="yearGroupsStaff" style="width: 302px">
								<?
								print "<option value='Y'>Yes</option>" ;
								print "<option value='N'>No</option>" ;
								?>
							</select>
						</td>
					</tr>
					<tr id="yearGroupRow4">
						<td style='background: none; background-color: #EDF7FF;'> 
							<b>Include students?</b><br/>
						</td>
						<td style='background: none; background-color: #EDF7FF;' class="right">
							<select name="yearGroupsStudents" id="yearGroupsStudents" style="width: 302px">
								<?
								print "<option value='Y'>Yes</option>" ;
								print "<option value='N'>No</option>" ;
								?>
							</select>
						</td>
					</tr>
					<?
					if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_yearGroups_parents")) {
						?>
						<tr id="yearGroupRow2">
							<td style='background: none; background-color: #EDF7FF;'> 
								<b>Include parents?</b><br/>
							</td>
							<td style='background: none; background-color: #EDF7FF;' class="right">
								<select name="yearGroupsParents" id="yearGroupsParents" style="width: 302px">
									<?
									print "<option value='Y'>Yes</option>" ;
									print "<option selected value='N'>No</option>" ;
									?>
								</select>
							</td>
						</tr>
						<?
					}
				}
				if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_rollGroups_my") OR isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_rollGroups_any")) {
					?>
					<script type="text/javascript">
						/* rollGroup Control */
						$(document).ready(function(){
							$("#rollGroupRow").css("display","none");
							$("#rollGroupRow2").css("display","none");
							$("#rollGroupRow3").css("display","none");
							$("#rollGroupRow4").css("display","none");
							$(".rollGroup").click(function(){
								if ($('input[name=rollGroup]:checked').val() == "Y" ) {
									$("#rollGroupRow").slideDown("fast", $("#rollGroupRow").css("display","table-row")); 
									$("#rollGroupRow2").slideDown("fast", $("#rollGroupRow2").css("display","table-row")); 
									$("#rollGroupRow3").slideDown("fast", $("#rollGroupRow3").css("display","table-row")); 
									$("#rollGroupRow4").slideDown("fast", $("#rollGroupRow4").css("display","table-row")); 
								} else {
									$("#rollGroupRow").css("display","none");
									$("#rollGroupRow2").css("display","none");
									$("#rollGroupRow3").css("display","none");
									$("#rollGroupRow4").css("display","none");
								}
							 });
						});
					</script>
					<tr>
						<td> 
							<b>Roll Group</b><br/>
							<span style="font-size: 90%"><i>Tutees and tutors<br/></i></span>
						</td>
						<td class="right">
							<input type="radio" name="rollGroup" class="rollGroup" value="Y"/> Yes
							<input checked type="radio" name="rollGroup" class="rollGroup" value="N"/> No
						</td>
					</tr>
					<tr id="rollGroupRow">
						<td style='background: none; background-color: #EDF7FF;'> 
							<b>Select Roll Groups</b><br/>
							<span style="font-size: 90%"><i>Use Control and/or Shift to select multiple.</i></span>
						</td>
						<td style='background: none; background-color: #EDF7FF;' class="right">
							<select name="rollGroups[]" id="rollGroups[]" multiple style="width: 302px; height: 100px">
								<?
								try {
									if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_rollGroups_any")) {
										$dataSelect=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
										$sqlSelect="SELECT * FROM gibbonRollGroup WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY name" ;
									}
									else {
										if (getRoleCategory($_SESSION[$guid]["gibbonRoleIDCurrent"], $connection2)=="Staff") {
											$dataSelect=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonPersonID1"=>$_SESSION[$guid]["gibbonPersonID"], "gibbonPersonID2"=>$_SESSION[$guid]["gibbonPersonID"], "gibbonPersonID3"=>$_SESSION[$guid]["gibbonPersonID"], "gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
											$sqlSelect="SELECT * FROM gibbonRollGroup WHERE (gibbonPersonIDTutor=:gibbonPersonID1 OR gibbonPersonIDTutor2=:gibbonPersonID2 OR gibbonPersonIDTutor3=:gibbonPersonID3) AND gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY name" ;
										}
										if (getRoleCategory($_SESSION[$guid]["gibbonRoleIDCurrent"], $connection2)=="Student") {
											$dataSelect=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"], ); 
											$sqlSelect="SELECT * FROM gibbonRollGroup JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE gibbonPersonID=:gibbonPersonID AND gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY name" ;
										}
									}
									$resultSelect=$connection2->prepare($sqlSelect);
									$resultSelect->execute($dataSelect);
								}
								catch(PDOException $e) { }
								while ($rowSelect=$resultSelect->fetch()) {
									print "<option value='" . $rowSelect["gibbonRollGroupID"] . "'>" . htmlPrep($rowSelect["name"]) . "</option>" ;
								}
								?>
							</select>
						</td>
					</tr>
					<tr id="rollGroupRow3">
						<td style='background: none; background-color: #EDF7FF;'> 
							<b>Include staff?</b><br/>
						</td>
						<td style='background: none; background-color: #EDF7FF;' class="right">
							<select name="rollGroupsStaff" id="rollGroupsStaff" style="width: 302px">
								<?
								print "<option value='Y'>Yes</option>" ;
								print "<option value='N'>No</option>" ;
								?>
							</select>
						</td>
					</tr>
					<tr id="rollGroupRow4">
						<td style='background: none; background-color: #EDF7FF;'> 
							<b>Include student?</b><br/>
						</td>
						<td style='background: none; background-color: #EDF7FF;' class="right">
							<select name="rollGroupsStudents" id="rollGroupsStudents" style="width: 302px">
								<?
								print "<option value='Y'>Yes</option>" ;
								print "<option value='N'>No</option>" ;
								?>
							</select>
						</td>
					</tr>
					<?
					if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_rollGroups_parents")) {
						?>
						<tr id="rollGroupRow2">
							<td style='background: none; background-color: #EDF7FF;'> 
								<b>Include parents?</b><br/>
							</td>
							<td style='background: none; background-color: #EDF7FF;' class="right">
								<select name="rollGroupsParents" id="rollGroupsParents" style="width: 302px">
									<?
									print "<option value='Y'>Yes</option>" ;
									print "<option selected value='N'>No</option>" ;
									?>
								</select>
							</td>
						</tr>
						<?
					}
				}
				if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_courses_my") OR isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_courses_any")) {
					?>
					<script type="text/javascript">
						/* course Control */
						$(document).ready(function(){
							$("#courseRow").css("display","none");
							$("#courseRow2").css("display","none");
							$("#courseRow3").css("display","none");
							$("#courseRow4").css("display","none");
							$(".course").click(function(){
								if ($('input[name=course]:checked').val() == "Y" ) {
									$("#courseRow").slideDown("fast", $("#courseRow").css("display","table-row")); 
									$("#courseRow2").slideDown("fast", $("#courseRow2").css("display","table-row")); 
									$("#courseRow3").slideDown("fast", $("#courseRow3").css("display","table-row")); 
									$("#courseRow4").slideDown("fast", $("#courseRow4").css("display","table-row")); 
								} else {
									$("#courseRow").css("display","none");
									$("#courseRow2").css("display","none");
									$("#courseRow3").css("display","none");
									$("#courseRow4").css("display","none");
								}
							 });
						});
					</script>
					<tr>
						<td> 
							<b>Course</b><br/>
							<span style="font-size: 90%"><i>Members of a course<br/></i></span>
						</td>
						<td class="right">
							<input type="radio" name="course" class="course" value="Y"/> Yes
							<input checked type="radio" name="course" class="course" value="N"/> No
						</td>
					</tr>
					<tr id="courseRow">
						<td style='background: none; background-color: #EDF7FF;'> 
							<b>Select Courses</b><br/>
							<span style="font-size: 90%"><i>Use Control and/or Shift to select multiple.</i></span>
						</td>
						<td style='background: none; background-color: #EDF7FF;' class="right">
							<select name="courses[]" id="courses[]" multiple style="width: 302px; height: 100px">
								<?
								try {
									if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_courses_any")) {
										$dataSelect=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
										$sqlSelect="SELECT * FROM gibbonCourse WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY nameShort" ;
									}
									else {
										$dataSelect=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"] ); 
										$sqlSelect="SELECT gibbonCourse.* FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) WHERE gibbonPersonID=:gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID AND NOT role LIKE '%- Left' ORDER BY name" ;
									}
									$resultSelect=$connection2->prepare($sqlSelect);
									$resultSelect->execute($dataSelect);
								}
								catch(PDOException $e) { }
								while ($rowSelect=$resultSelect->fetch()) {
									print "<option value='" . $rowSelect["gibbonCourseID"] . "'>" . htmlPrep($rowSelect["nameShort"]) . "</option>" ;
								}
								?>
							</select>
						</td>
					</tr>
					<tr id="courseRow3">
						<td style='background: none; background-color: #EDF7FF;'> 
							<b>Include staff?</b><br/>
						</td>
						<td style='background: none; background-color: #EDF7FF;' class="right">
							<select name="coursesStaff" id="coursesStaff" style="width: 302px">
								<?
								print "<option value='Y'>Yes</option>" ;
								print "<option value='N'>No</option>" ;
								?>
							</select>
						</td>
					</tr>
					<tr id="courseRow4">
						<td style='background: none; background-color: #EDF7FF;'> 
							<b>Include students?</b><br/>
						</td>
						<td style='background: none; background-color: #EDF7FF;' class="right">
							<select name="coursesStudents" id="coursesStudents" style="width: 302px">
								<?
								print "<option value='Y'>Yes</option>" ;
								print "<option value='N'>No</option>" ;
								?>
							</select>
						</td>
					</tr>
					<?
					if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_courses_parents")) {
						?>
						<tr id="courseRow2">
							<td style='background: none; background-color: #EDF7FF;'> 
								<b>Include parents?</b><br/>
							</td>
							<td style='background: none; background-color: #EDF7FF;' class="right">
								<select name="coursesParents" id="coursesParents" style="width: 302px">
									<?
									print "<option value='Y'>Yes</option>" ;
									print "<option selected value='N'>No</option>" ;
									?>
								</select>
							</td>
						</tr>
						<?
					}
				}
				if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_classes_my") OR isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_classes_any")) {
					?>
					<script type="text/javascript">
						/* class Control */
						$(document).ready(function(){
							$("#classRow").css("display","none");
							$("#classRow2").css("display","none");
							$("#classRow3").css("display","none");
							$("#classRow4").css("display","none");
							$(".class").click(function(){
								if ($('input[name=class]:checked').val() == "Y" ) {
									$("#classRow").slideDown("fast", $("#classRow").css("display","table-row")); 
									$("#classRow2").slideDown("fast", $("#classRow2").css("display","table-row")); 
									$("#classRow3").slideDown("fast", $("#classRow3").css("display","table-row")); 
									$("#classRow4").slideDown("fast", $("#classRow4").css("display","table-row")); 
								} else {
									$("#classRow").css("display","none");
									$("#classRow2").css("display","none");
									$("#classRow3").css("display","none");
									$("#classRow4").css("display","none");
								}
							 });
						});
					</script>
					<tr>
						<td> 
							<b>Class</b><br/>
							<span style="font-size: 90%"><i>Members of a class<br/></i></span>
						</td>
						<td class="right">
							<input type="radio" name="class" class="class" value="Y"/> Yes
							<input checked type="radio" name="class" class="class" value="N"/> No
						</td>
					</tr>
					<tr id="classRow">
						<td style='background: none; background-color: #EDF7FF;'> 
							<b>Select Classes</b><br/>
							<span style="font-size: 90%"><i>Use Control and/or Shift to select multiple.</i></span>
						</td>
						<td style='background: none; background-color: #EDF7FF;' class="right">
							<select name="classes[]" id="classes[]" multiple style="width: 302px; height: 100px">
								<?
								try {
									if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_classes_any")) {
										$dataSelect=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
										$sqlSelect="SELECT gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY course, class" ;
									}
									else {
										$dataSelect=array("gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"], "gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
										$sqlSelect="SELECT gibbonCourseClass.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) WHERE gibbonPersonID=:gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID AND NOT role LIKE '%- Left' ORDER BY course, class" ;
									}
									$resultSelect=$connection2->prepare($sqlSelect);
									$resultSelect->execute($dataSelect);
								}
								catch(PDOException $e) { }
								while ($rowSelect=$resultSelect->fetch()) {
									print "<option value='" . $rowSelect["gibbonCourseClassID"] . "'>" . htmlPrep($rowSelect["course"]) . "." . htmlPrep($rowSelect["class"]) . "</option>" ;
								}
								?>
							</select>
						</td>
					</tr>
					<tr id="classRow3">
						<td style='background: none; background-color: #EDF7FF;'> 
							<b>Include staff?</b><br/>
						</td>
						<td style='background: none; background-color: #EDF7FF;' class="right">
							<select name="classesStaff" id="classesStaff" style="width: 302px">
								<?
								print "<option value='Y'>Yes</option>" ;
								print "<option value='N'>No</option>" ;
								?>
							</select>
						</td>
					</tr>
					<tr id="classRow4">
						<td style='background: none; background-color: #EDF7FF;'> 
							<b>Include students?</b><br/>
						</td>
						<td style='background: none; background-color: #EDF7FF;' class="right">
							<select name="classesStudents" id="classesStudents" style="width: 302px">
								<?
								print "<option value='Y'>Yes</option>" ;
								print "<option value='N'>No</option>" ;
								?>
							</select>
						</td>
					</tr>
					<?
					if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_classes_parents")) {
						?>
						<tr id="classRow2">
							<td style='background: none; background-color: #EDF7FF;'> 
								<b>Include parents?</b><br/>
							</td>
							<td style='background: none; background-color: #EDF7FF;' class="right">
								<select name="classesParents" id="classesParents" style="width: 302px">
									<?
									print "<option value='Y'>Yes</option>" ;
									print "<option selected value='N'>No</option>" ;
									?>
								</select>
							</td>
						</tr>
						<?
					}
				}
				if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_activities_my") OR isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_activities_any")) {
					?>
					<script type="text/javascript">
						/* activity Control */
						$(document).ready(function(){
							$("#activitiesRow").css("display","none");
							$("#activitiesRow2").css("display","none");
							$("#activitiesRow3").css("display","none");
							$("#activitiesRow4").css("display","none");
							$(".activity").click(function(){
								if ($('input[name=activity]:checked').val() == "Y" ) {
									$("#activitiesRow").slideDown("fast", $("#activitiesRow").css("display","table-row")); 
									$("#activitiesRow2").slideDown("fast", $("#activitiesRow2").css("display","table-row")); 
									$("#activitiesRow3").slideDown("fast", $("#activitiesRow3").css("display","table-row")); 
									$("#activitiesRow4").slideDown("fast", $("#activitiesRow4").css("display","table-row")); 
								} else {
									$("#activitiesRow").css("display","none");
									$("#activitiesRow2").css("display","none");
									$("#activitiesRow3").css("display","none");
									$("#activitiesRow4").css("display","none");
								}
							 });
						});
					</script>
					<tr>
						<td> 
							<b>Activity</b><br/>
							<span style="font-size: 90%"><i>Members of an activity<br/></i></span>
						</td>
						<td class="right">
							<input type="radio" name="activity" class="activity" value="Y"/> Yes
							<input checked type="radio" name="activity" class="activity" value="N"/> No
						</td>
					</tr>
					<tr id="activitiesRow">
						<td style='background: none; background-color: #EDF7FF;'> 
							<b>Select Activities</b><br/>
							<span style="font-size: 90%"><i>Use Control and/or Shift to select multiple.</i></span>
						</td>
						<td style='background: none; background-color: #EDF7FF;' activities="right">
							<select name="activities[]" id="activities[]" multiple style="width: 302px; height: 100px">
								<?
								try {
									if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_activities_any")) {
										$dataSelect=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
										$sqlSelect="SELECT * FROM gibbonActivity WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND active='Y' ORDER BY name" ;
									}
									else {
										if (getRoleCategory($_SESSION[$guid]["gibbonRoleIDCurrent"], $connection2)=="Staff") {
											$dataSelect=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
											$sqlSelect="SELECT * FROM gibbonActivity JOIN gibbonActivityStaff ON (gibbonActivityStaff.gibbonActivityID=gibbonActivity.gibbonActivityID) WHERE gibbonPersonID=:gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID AND active='Y' ORDER BY name" ;
										}
										if (getRoleCategory($_SESSION[$guid]["gibbonRoleIDCurrent"], $connection2)=="Student") {
											$dataSelect=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
											$sqlSelect="SELECT * FROM gibbonActivity JOIN gibbonActivityStudent ON (gibbonActivityStudent.gibbonActivityID=gibbonActivity.gibbonActivityID) WHERE gibbonPersonID=:gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID AND status='Accepted' AND active='Y' ORDER BY name" ;
										}
									}
									$resultSelect=$connection2->prepare($sqlSelect);
									$resultSelect->execute($dataSelect);
								}
								catch(PDOException $e) { }
								while ($rowSelect=$resultSelect->fetch()) {
									print "<option value='" . $rowSelect["gibbonActivityID"] . "'>" . htmlPrep($rowSelect["name"]) . "</option>" ;
								}
								?>
							</select>
						</td>
					</tr>
					<tr id="activitiesRow3">
						<td style='background: none; background-color: #EDF7FF;'> 
							<b>Include staff?</b><br/>
						</td>
						<td style='background: none; background-color: #EDF7FF;' activities="right">
							<select name="activitiesStaff" id="activitiesStaff" style="width: 302px">
								<?
								print "<option value='Y'>Yes</option>" ;
								print "<option value='N'>No</option>" ;
								?>
							</select>
						</td>
					</tr>
					<tr id="activitiesRow4">
						<td style='background: none; background-color: #EDF7FF;'> 
							<b>Include students?</b><br/>
						</td>
						<td style='background: none; background-color: #EDF7FF;' activities="right">
							<select name="activitiesStudents" id="activitiesStudents" style="width: 302px">
								<?
								print "<option value='Y'>Yes</option>" ;
								print "<option value='N'>No</option>" ;
								?>
							</select>
						</td>
					</tr>
					<?
					if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_activities_parents")) {
						?>
						<tr id="activitiesRow2">
							<td style='background: none; background-color: #EDF7FF;'> 
								<b>Include parents?</b><br/>
							</td>
							<td style='background: none; background-color: #EDF7FF;' activities="right">
								<select name="activitiesParents" id="activitiesParents" style="width: 302px">
									<?
									print "<option value='Y'>Yes</option>" ;
									print "<option selected value='N'>No</option>" ;
									?>
								</select>
							</td>
						</tr>
						<?
					}
				}
				if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_applicants")) {
					?>
					<script type="text/javascript">
						/* Role Control */
						$(document).ready(function(){
							$("#applicantsRow").css("display","none");
							$(".applicants").click(function(){
								if ($('input[name=applicants]:checked').val() == "Y" ) {
									$("#applicantsRow").slideDown("fast", $("#applicantsRow").css("display","table-row")); 
								} else {
									$("#applicantsRow").css("display","none");
								}
							 });
						});
					</script>
					<tr>
						<td> 
							<b>Applicants</b><br/>
							<span style="font-size: 90%"><i>Applicants from a given year.<br/>Does not apply to the message wall.</i></span>
						</td>
						<td class="right">
							<input type="radio" name="applicants" class="applicants" value="Y"/> Yes
							<input checked type="radio" name="applicants" class="applicants" value="N"/> No
						</td>
					</tr>
					<tr id="applicantsRow">
						<td style='background: none; background-color: #EDF7FF;'> 
							<b>Select Years</b><br/>
							<span style="font-size: 90%"><i>Use Control and/or Shift to select multiple.</i></span>
						</td>
						<td style='background: none; background-color: #EDF7FF;' class="right">
							<select name="applicantList[]" id="applicantList[]" multiple style="width: 302px; height: 100px">
								<?
								try {
									$dataSelect=array(); 
									$sqlSelect="SELECT * FROM gibbonSchoolYear ORDER BY sequenceNumber DESC" ;
									$resultSelect=$connection2->prepare($sqlSelect);
									$resultSelect->execute($dataSelect);
								}
								catch(PDOException $e) { }
								while ($rowSelect=$resultSelect->fetch()) {
									print "<option value='" . $rowSelect["gibbonSchoolYearID"] . "'>" . htmlPrep($rowSelect["name"]) . "</option>" ;
								}
								?>
							</select>
						</td>
					</tr>
					<?
				}
				if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_houses_all") OR isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_houses_my")) {
					?>
					<script type="text/javascript">
						/* Role Control */
						$(document).ready(function(){
							$("#housesRow").css("display","none");
							$(".houses").click(function(){
								if ($('input[name=houses]:checked').val() == "Y" ) {
									$("#housesRow").slideDown("fast", $("#housesRow").css("display","table-row")); 
								} else {
									$("#housesRow").css("display","none");
								}
							 });
						});
					</script>
					<tr>
						<td> 
							<b>Houses</b><br/>
							<span style="font-size: 90%"><i>Houses for competitions, etc.<br/></i></span>
						</td>
						<td class="right">
							<input type="radio" name="houses" class="houses" value="Y"/> Yes
							<input checked type="radio" name="houses" class="houses" value="N"/> No
						</td>
					</tr>
					<tr id="housesRow">
						<td style='background: none; background-color: #EDF7FF;'> 
							<b>Select Houses</b><br/>
							<span style="font-size: 90%"><i>Use Control and/or Shift to select multiple.</i></span>
						</td>
						<td style='background: none; background-color: #EDF7FF;' class="right">
							<select name="houseList[]" id="houseList[]" multiple style="width: 302px; height: 100px">
								<?
								try {
									if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_houses_all")) {
										$dataSelect=array(); 
										$sqlSelect="SELECT * FROM gibbonHouse ORDER BY name" ;
									}
									else if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_houses_my")) {
										$dataSelect=array("gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
										$sqlSelect="SELECT gibbonHouse.gibbonHouseID, name FROM gibbonHouse JOIN gibbonPerson ON (gibbonHouse.gibbonHouseID=gibbonPerson.gibbonHouseID) WHERE gibbonPersonID=:gibbonPersonID ORDER BY name" ;
									}
										
									$resultSelect=$connection2->prepare($sqlSelect);
									$resultSelect->execute($dataSelect);
								}
								catch(PDOException $e) { }
								while ($rowSelect=$resultSelect->fetch()) {
									print "<option value='" . $rowSelect["gibbonHouseID"] . "'>" . htmlPrep($rowSelect["name"]) . "</option>" ;
								}
								?>
							</select>
						</td>
					</tr>
					<?
				}
				if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_individuals")) {
					?>
					<script type="text/javascript">
						/* Role Control */
						$(document).ready(function(){
							$("#individualsRow").css("display","none");
							$(".individuals").click(function(){
								if ($('input[name=individuals]:checked').val() == "Y" ) {
									$("#individualsRow").slideDown("fast", $("#individualsRow").css("display","table-row")); 
								} else {
									$("#individualsRow").css("display","none");
								}
							 });
						});
					</script>
					<tr>
						<td> 
							<b>Indviduals</b><br/>
							<span style="font-size: 90%"><i>Individuals from the whole school<br/></i></span>
						</td>
						<td class="right">
							<input type="radio" name="individuals" class="individuals" value="Y"/> Yes
							<input checked type="radio" name="individuals" class="individuals" value="N"/> No
						</td>
					</tr>
					<tr id="individualsRow">
						<td style='background: none; background-color: #EDF7FF;'> 
							<b>Select Individuals</b><br/>
							<span style="font-size: 90%"><i>Use Control and/or Shift to select multiple.</i></span>
						</td>
						<td style='background: none; background-color: #EDF7FF;' class="right">
							<select name="individualList[]" id="individualList[]" multiple style="width: 302px; height: 100px">
								<?
								try {
									$dataSelect=array(); 
									$sqlSelect="SELECT gibbonPersonID, preferredName, surname FROM gibbonPerson WHERE status='Full' ORDER BY surname, preferredName" ;
									$resultSelect=$connection2->prepare($sqlSelect);
									$resultSelect->execute($dataSelect);
								}
								catch(PDOException $e) { }
								while ($rowSelect=$resultSelect->fetch()) {
									print "<option value='" . $rowSelect["gibbonPersonID"] . "'>" . formatName("", $rowSelect["preferredName"], $rowSelect["surname"], "Student", true) . "</option>" ;
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
						<span style="font-size: 90%"><i>* <? print _("denotes a required field") ; ?></i></span>
					</td>
					<td class="right">
						<input type="submit" value="<? print _("Submit") ; ?>">
					</td>
				</tr>
			</table>
		</form>
		<?
	}
}
?>