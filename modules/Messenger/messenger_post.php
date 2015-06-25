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

//Only include module include if it is not already included (which it may be been on the index page)
$included=FALSE ;
$includes=get_included_files() ;
foreach ($includes AS $include) {
	if (str_replace("\\","/",$include)==str_replace("\\","/",$_SESSION[$guid]["absolutePath"] . "/modules/" . $_SESSION[$guid]["module"] . "/moduleFunctions.php")) {
		$included=TRUE ;
	}
}
if ($included==FALSE) {
	include "./modules/" . $_SESSION[$guid]["module"] . "/moduleFunctions.php" ;
}

if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	if ($_SESSION[$guid]["email"]=="") {
		print "<div class='error'>" ;
			print _("You do not have a personal email address set in Gibbon, and so cannot send out emails.") ;
		print "</div>" ;
	}
	else {
		//Proceed!
		print "<div class='trail'>" ;
		print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . _(getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . _('New Message') . "</div>" ;
		print "</div>" ;
		
		if (isset($_GET["addReturn"])) { $addReturn=$_GET["addReturn"] ; } else { $addReturn="" ; }
		$addReturnMessage="" ;
		$class="error" ;
		if (!($addReturn=="")) {
			if ($addReturn=="fail0") {
				$addReturnMessage=_("Your request failed because you do not have access to this action.") ;	
			}
			else if ($addReturn=="fail2") {
				$addReturnMessage=_("Your request failed due to a database error.") ;	
			}
			else if ($addReturn=="fail3") {
				$addReturnMessage=_("Your request failed because your inputs were invalid.") ;	
			}
			else if ($addReturn=="fail4") {
				$addReturnMessage=_("Your request was completed successfully, but some or all messages could not be delivered.") ;	
			}
			else if ($addReturn=="fail5") {
				$addReturnMessage=_("Your request failed due to an attachment error.") ;	
			}
			else if ($addReturn=="success0") {
				$addReturnMessage=_("Your request was completed successfully: not all messages may arrive at their destination, but an attempt has been made to get them all out.") ;
				if (is_numeric($_GET["emailCount"])) {
					$addReturnMessage.=" " . sprintf(_('%1$s email(s) were dispatched.'), $_GET["emailCount"]) ;	
				}
				if (is_numeric($_GET["smsCount"]) AND is_numeric($_GET["smsBatchCount"])) {
					$addReturnMessage.=" " . sprintf(_('%1$s SMS(es) were dispatched in %2$s batch(es).'), $_GET["smsCount"], $_GET["smsBatchCount"]) ;	
				}
				
				$class="success" ;
			}
			print "<div class='$class'>" ;
				print $addReturnMessage;
			print "</div>" ;
		} 
		
		print "<div class='warning'>" ;
			print sprintf(_('Each family in Gibbon must have one parent who is contact priority 1, and who must be enabled to receive email and SMS messages from %1$s. As a result, when targetting parents, you can be fairly certain that messages should get through to each family.'), $_SESSION[$guid]["organisationNameShort"]) ;
		print "</div>" ;
				
		?>
		<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/messenger_postProcess.php?address=" . $_GET["q"] ?>" enctype="multipart/form-data">
			<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
				<tr class='break'>
					<td colspan=2> 
						<h3><?php print _('Delivery Mode') ?></h3>
					</td>
				</tr>
				<?php
				if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_byEmail")) {
					?>
					<script type="text/javascript">
						$(document).ready(function(){
							$(".email").click(function(){
								if ($('input[name=email]:checked').val()=="Y" ) {
									$("#emailRow").slideDown("fast", $("#emailRow").css("display","table-row")); 
									$("#emailReplyToRow").slideDown("fast", $("#emailRow").css("display","table-row")); 
								} else {
									$("#emailRow").css("display","none");
									$("#emailReplyToRow").css("display","none");
								}
							 });
						});
					</script>
					<tr>
						<td style='width: 275px'> 
							<b><?php print _('Email') ?> *</b><br/>
							<span style="font-size: 90%"><i><?php print _('Deliver this message to user\'s primary email account?') ?><br/></i></span>
						</td>
						<td class="right">
							<input checked type="radio" name="email" class="email" value="Y"/> <?php print _('Yes') ?>
							<input type="radio" name="email" class="email" value="N"/> <?php print _('No') ?>
						</td>
					</tr>
					<tr id="emailRow">
						<td> 
							<b><?php print _('Email From') ?> *</b><br/>
						</td>
						<td class="right">
							<?php
							print "<select style='float: none; width:302px' name='from' id='from'>" ;
								print "<option value='" . $_SESSION[$guid]["email"] . "'>" . $_SESSION[$guid]["email"] . "</option>" ;
								if ($_SESSION[$guid]["emailAlternate"]!="") {
									print "<option value='" . $_SESSION[$guid]["emailAlternate"] . "'>" . $_SESSION[$guid]["emailAlternate"] . "</option>" ;
								}
								if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_fromSchool") AND $_SESSION[$guid]["organisationEmail"]!="") {
									print "<option value='" . $_SESSION[$guid]["organisationEmail"] . "'>" . $_SESSION[$guid]["organisationEmail"] . "</option>" ;
								}
							print "</select>" ;
							?>
						</td>
					</tr>
					<?php
					if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_fromSchool")) { 
						?>
						<tr id="emailReplyToRow">
							<td> 
								<b><?php print _('Reply To') ?> </b><br/>
							</td>
							<td class="right">
								<input name="emailReplyTo" id="emailReplyTo" maxlength=255 value="" type="text" style="width: 300px">
								<script type="text/javascript">
									var emailReplyTo=new LiveValidation('emailReplyTo');
									emailReplyTo.add(Validate.Email);
								 </script>
							</td>
						</tr>
						<?php
					}
				}
				if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_byMessageWall")) {
					?>
					<script type="text/javascript">
						$(document).ready(function(){
							$("#messageWallRow").css("display","none");
							$(".messageWall").click(function(){
								if ($('input[name=messageWall]:checked').val()=="Y" ) {
									$("#messageWallRow").slideDown("fast", $("#messageWallRow").css("display","table-row")); 
								} else {
									$("#messageWallRow").css("display","none");
								}
							 });
						});
					</script>
					<tr>
						<td> 
							<b><?php print _('Message Wall') ?> *</b><br/>
							<span style="font-size: 90%"><i><?php print _('Place this message on user\'s message wall?') ?><br/></i></span>
						</td>
						<td class="right">
							<input type="radio" name="messageWall" class="messageWall" value="Y"/> <?php print _('Yes') ?>
							<input checked type="radio" name="messageWall" class="messageWall" value="N"/> <?php print _('No') ?>
						</td>
					</tr>
					<tr id="messageWallRow">
						<td> 
							<b><?php print _('Publication Dates') ?> *</b><br/>
							<span style="font-size: 90%"><i><?php print _('Select up to three individual dates.') ?></br>Format <?php if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?>.<br/></i></span>
						</td>
						<td class="right">
							<input name="date1" id="date1" maxlength=10 value="" type="text" style="width: 300px">
							<script type="text/javascript">
								var date1=new LiveValidation('date1');
								date1.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]["i18n"]["dateFormatRegEx"]=="") {  print "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i" ; } else { print $_SESSION[$guid]["i18n"]["dateFormatRegEx"] ; } ?>, failureMessage: "Use <?php if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?>." } ); 
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
								date2.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]["i18n"]["dateFormatRegEx"]=="") {  print "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i" ; } else { print $_SESSION[$guid]["i18n"]["dateFormatRegEx"] ; } ?>, failureMessage: "Use <?php if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?>." } ); 
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
								date3.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]["i18n"]["dateFormatRegEx"]=="") {  print "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i" ; } else { print $_SESSION[$guid]["i18n"]["dateFormatRegEx"] ; } ?>, failureMessage: "Use <?php if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?>." } ); 
							 </script>
							 <script type="text/javascript">
								$(function() {
									$( "#date3" ).datepicker();
								});
							</script>
						</td>
					</tr>
					<?php
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
									if ($('input[name=sms]:checked').val()=="Y" ) {
										$("#smsRow").slideDown("fast", $("#smsRow").css("display","table-row")); 
									} else {
										$("#smsRow").css("display","none");
									}
								 });
							});
						</script>
						<tr>
							<td> 
								<b><?php print _('SMS') ?> *</b><br/>
								<span style="font-size: 90%"><i><?php print _('Deliver this message to user\'s mobile phone?') ?><br/></i></span>
							</td>
							<td class="right">
								<input type="radio" id="sms" name="sms" class="sms" value="Y"/> <?php print _('Yes') ?>
								<input checked type="radio" id="sms" name="sms" class="sms" value="N"/> <?php print _('No') ?>
							</td>
						</tr>
						<tr>
						<tr id="smsRow">
							<td colspan=2> 
								<div class='error' style='margin-top: 3px'>
									<?php print _('SMS messages are sent to local and overseas numbers, but not all countries are supported. Please see the SMS Gateway provider\'s documentation or error log to see which countries are not supported. The subject does not get sent, and all HTML tags are removed. Each message, to each recipient, will incur a charge (dependent on your SMS gateway provider). Messages over 140 characters will get broken into smaller messages, and will cost more.') ?><br/>
									<br/>
									<?php
									if ($smsURLCredit!="") {
										$query="?apiusername=" . $smsUsername . "&apipassword=" . $smsPassword ;        
										$result=@implode('', file($smsURLCredit . $query)) ;   
										if (is_numeric($result)==FALSE) {
											$result=0 ;
										} 
										if ($result>=0) {
											print "<b>" . sprintf(_('Current balance: %1$s credit(s).'), $result) . "</u></b>" ;
										} 
									}
									?>
								</div>
							</td>
						</tr>
						<?php
					}
					else {
						?>
						<tr>
							<td colspan=2> 
								<div class='error' style='margin-top: 3px'><?php print sprintf(_('SMS NOT CONFIGURED. Please contact %1$s for help.'), "<a href='mailto:" . $_SESSION[$guid]["organisationAdministratorEmail"] . "'>" . $_SESSION[$guid]["organisationAdministratorName"] . "</a>") ?></div>
							</td>
						</tr>
						<?php
					}
				}
				?>
				
				<tr class='break'>
					<td colspan=2> 
						<h3><?php print _('Message Details') ?></h3>
					</td>
				</tr>
				<tr>
					<td> 
						<b><?php print _('Subject') ?> *</b><br/>
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
						<b><?php print _('Body') ?> *</b>
						<?php 
						//Attempt to build a signature for the user
						$signature=getSignature($guid, $connection2, $_SESSION[$guid]["gibbonPersonID"]) ;
						print getEditor($guid,  TRUE, "body", $signature, 20, true, true, false, true ) ;
						?>
					</td>
				</tr>
				
				<tr class='break'>
					<td colspan=2> 
						<h3><?php print _('Targets') ?></h3>
					</td>
				</tr>
				<?php
				if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_role")) {
					?>
					<script type="text/javascript">
						/* Role Control */
						$(document).ready(function(){
							$("#roleRow").css("display","none");
							$(".role").click(function(){
								if ($('input[name=role]:checked').val()=="Y" ) {
									$("#roleRow").slideDown("fast", $("#roleRow").css("display","table-row")); 
								} else {
									$("#roleRow").css("display","none");
								}
							 });
						});
					</script>
					<tr>
						<td> 
							<b><?php print _('Role') ?></b><br/>
							<span style="font-size: 90%"><i><?php print _('Users of a certain type.') ?><br/></i></span>
						</td>
						<td class="right">
							<input type="radio" name="role" class="role" value="Y"/> <?php print _('Yes') ?>
							<input checked type="radio" name="role" class="role" value="N"/> <?php print _('No') ?>
						</td>
					</tr>
					<tr id="roleRow">
						<td class='hiddenReveal'> 
							<b><?php print _('Select Roles') ?></b><br/>
							<span style="font-size: 90%"><i><?php print _('Use Control, Command and/or Shift to select multiple.') ?></i></span>
						</td>
						<td class="hiddenReveal right">
							<select name="roles[]" id="roles[]" multiple style="width: 302px; height: 100px">
								<?php
								try {
									$dataSelect=array(); 
									$sqlSelect="SELECT * FROM gibbonRole ORDER BY name" ;
									$resultSelect=$connection2->prepare($sqlSelect);
									$resultSelect->execute($dataSelect);
								}
								catch(PDOException $e) { }
								while ($rowSelect=$resultSelect->fetch()) {
									print "<option value='" . $rowSelect["gibbonRoleID"] . "'>" . htmlPrep(_($rowSelect["name"])) . " (" . htmlPrep(_($rowSelect["category"])) . ")</option>" ;
								}
								?>
							</select>
						</td>
					</tr>
					
					<script type="text/javascript">
						/* Role CategoryControl */
						$(document).ready(function(){
							$("#roleCategoryRow").css("display","none");
							$(".roleCategory").click(function(){
								if ($('input[name=roleCategory]:checked').val()=="Y" ) {
									$("#roleCategoryRow").slideDown("fast", $("#roleCategoryRow").css("display","table-row")); 
								} else {
									$("#roleCategoryRow").css("display","none");
								}
							 });
						});
					</script>
					<tr>
						<td> 
							<b><?php print _('Role Category') ?></b><br/>
							<span style="font-size: 90%"><i><?php print _('Users of a certain type.') ?><br/></i></span>
						</td>
						<td class="right">
							<input type="radio" name="roleCategory" class="roleCategory" value="Y"/> <?php print _('Yes') ?>
							<input checked type="radio" name="roleCategory" class="roleCategory" value="N"/> <?php print _('No') ?>
						</td>
					</tr>
					<tr id="roleCategoryRow">
						<td class='hiddenReveal'> 
							<b><?php print _('Select Role Categories') ?></b><br/>
							<span style="font-size: 90%"><i><?php print _('Use Control, Command and/or Shift to select multiple.') ?></i></span>
						</td>
						<td class="hiddenReveal right">
							<select name="roleCategories[]" id="roleCategories[]" multiple style="width: 302px; height: 100px">
								<?php
								try {
									$dataSelect=array(); 
									$sqlSelect="SELECT DISTINCT category FROM gibbonRole ORDER BY category" ;
									$resultSelect=$connection2->prepare($sqlSelect);
									$resultSelect->execute($dataSelect);
								}
								catch(PDOException $e) { }
								while ($rowSelect=$resultSelect->fetch()) {
									print "<option value='" . $rowSelect["category"] . "'>" . htmlPrep(_($rowSelect["category"])) . "</option>" ;
								}
								?>
							</select>
						</td>
					</tr>
					<?php
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
								if ($('input[name=yearGroup]:checked').val()=="Y" ) {
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
							<b><?php print _('Year Group') ?></b><br/>
							<span style="font-size: 90%"><i><?php print _('Students in year; all staff.') ?><br/></i></span>
						</td>
						<td class="right">
							<input type="radio" name="yearGroup" class="yearGroup" value="Y"/> <?php print _('Yes') ?>
							<input checked type="radio" name="yearGroup" class="yearGroup" value="N"/> <?php print _('No') ?>
						</td>
					</tr>
					<tr id="yearGroupRow">
						<td class='hiddenReveal'> 
							<b><?php print _('Select Year Groups') ?></b><br/>
							<span style="font-size: 90%"><i><?php print _('Use Control, Command and/or Shift to select multiple.') ?></i></span>
						</td>
						<td class="hiddenReveal right">
							<select name="yearGroups[]" id="yearGroups[]" multiple style="width: 302px; height: 100px">
								<?php
								try {
									$dataSelect=array(); 
									$sqlSelect="SELECT * FROM gibbonYearGroup ORDER BY sequenceNumber" ;
									$resultSelect=$connection2->prepare($sqlSelect);
									$resultSelect->execute($dataSelect);
								}
								catch(PDOException $e) { }
								while ($rowSelect=$resultSelect->fetch()) {
									print "<option value='" . $rowSelect["gibbonYearGroupID"] . "'>" . htmlPrep(_($rowSelect["name"])) . "</option>" ;
								}
								?>
							</select>
						</td>
					</tr>
					<tr id="yearGroupRow3">
						<td class='hiddenReveal'> 
							<b><?php print _('Include staff?') ?></b><br/>
						</td>
						<td class="hiddenReveal right">
							<select name="yearGroupsStaff" id="yearGroupsStaff" style="width: 302px">
								<?php
								print "<option value='Y'>" . _('Yes') . "</option>" ;
								print "<option value='N'>" . _('No') . "</option>" ;
								?>
							</select>
						</td>
					</tr>
					<tr id="yearGroupRow4">
						<td class='hiddenReveal'> 
							<b><?php print _('Include students?') ?></b><br/>
						</td>
						<td class="hiddenReveal right">
							<select name="yearGroupsStudents" id="yearGroupsStudents" style="width: 302px">
								<?php
								print "<option value='Y'>" . _('Yes') . "</option>" ;
								print "<option value='N'>" . _('No') . "</option>" ;
								?>
							</select>
						</td>
					</tr>
					<?php
					if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_yearGroups_parents")) {
						?>
						<tr id="yearGroupRow2">
							<td class='hiddenReveal'> 
								<b><?php print _('Include parents?') ?></b><br/>
							</td>
							<td class="hiddenReveal right">
								<select name="yearGroupsParents" id="yearGroupsParents" style="width: 302px">
									<?php
									print "<option value='Y'>" . _('Yes') . "</option>" ;
									print "<option selected value='N'>No</option>" ;
									?>
								</select>
							</td>
						</tr>
						<?php
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
								if ($('input[name=rollGroup]:checked').val()=="Y" ) {
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
							<b><?php print _('Roll Group') ?></b><br/>
							<span style="font-size: 90%"><i><?php print _('Tutees and tutors.') ?><br/></i></span>
						</td>
						<td class="right">
							<input type="radio" name="rollGroup" class="rollGroup" value="Y"/> <?php print _('Yes') ?>
							<input checked type="radio" name="rollGroup" class="rollGroup" value="N"/> <?php print _('No') ?>
						</td>
					</tr>
					<tr id="rollGroupRow">
						<td class='hiddenReveal'> 
							<b><?php print _('Select Roll Groups') ?></b><br/>
							<span style="font-size: 90%"><i><?php print _('Use Control, Command and/or Shift to select multiple.') ?></i></span>
						</td>
						<td class="hiddenReveal right">
							<select name="rollGroups[]" id="rollGroups[]" multiple style="width: 302px; height: 100px">
								<?php
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
						<td class='hiddenReveal'> 
							<b><?php print _('Include staff?') ?></b><br/>
						</td>
						<td class="hiddenReveal right">
							<select name="rollGroupsStaff" id="rollGroupsStaff" style="width: 302px">
								<?php
								print "<option value='Y'>" . _('Yes') . "</option>" ;
								print "<option value='N'>" . _('No') . "</option>" ;
								?>
							</select>
						</td>
					</tr>
					<tr id="rollGroupRow4">
						<td class='hiddenReveal'> 
							<b><?php print _('Include student?') ?></b><br/>
						</td>
						<td class="hiddenReveal right">
							<select name="rollGroupsStudents" id="rollGroupsStudents" style="width: 302px">
								<?php
								print "<option value='Y'>" . _('Yes') . "</option>" ;
								print "<option value='N'>" . _('No') . "</option>" ;
								?>
							</select>
						</td>
					</tr>
					<?php
					if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_rollGroups_parents")) {
						?>
						<tr id="rollGroupRow2">
							<td class='hiddenReveal'> 
								<b><?php print _('Include parents?') ?></b><br/>
							</td>
							<td class="hiddenReveal right">
								<select name="rollGroupsParents" id="rollGroupsParents" style="width: 302px">
									<?php
									print "<option value='Y'>" . _('Yes') . "</option>" ;
									print "<option selected value='N'>No</option>" ;
									?>
								</select>
							</td>
						</tr>
						<?php
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
								if ($('input[name=course]:checked').val()=="Y" ) {
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
							<b><?php print _('Course') ?></b><br/>
							<span style="font-size: 90%"><i><?php print _('Members of a course of study.') ?><br/></i></span>
						</td>
						<td class="right">
							<input type="radio" name="course" class="course" value="Y"/> <?php print _('Yes') ?>
							<input checked type="radio" name="course" class="course" value="N"/> <?php print _('No') ?>
						</td>
					</tr>
					<tr id="courseRow">
						<td class='hiddenReveal'> 
							<b><?php print _('Select Courses') ?></b><br/>
							<span style="font-size: 90%"><i><?php print _('Use Control, Command and/or Shift to select multiple.') ?></i></span>
						</td>
						<td class="hiddenReveal right">
							<select name="courses[]" id="courses[]" multiple style="width: 302px; height: 100px">
								<?php
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
						<td class='hiddenReveal'> 
							<b><?php print _('Include staff?') ?></b><br/>
						</td>
						<td class="hiddenReveal right">
							<select name="coursesStaff" id="coursesStaff" style="width: 302px">
								<?php
								print "<option value='Y'>" . _('Yes') . "</option>" ;
								print "<option value='N'>" . _('No') . "</option>" ;
								?>
							</select>
						</td>
					</tr>
					<tr id="courseRow4">
						<td class='hiddenReveal'> 
							<b><?php print _('Include students?') ?></b><br/>
						</td>
						<td class="hiddenReveal right">
							<select name="coursesStudents" id="coursesStudents" style="width: 302px">
								<?php
								print "<option value='Y'>" . _('Yes') . "</option>" ;
								print "<option value='N'>" . _('No') . "</option>" ;
								?>
							</select>
						</td>
					</tr>
					<?php
					if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_courses_parents")) {
						?>
						<tr id="courseRow2">
							<td class='hiddenReveal'> 
								<b><?php print _('Include parents?') ?></b><br/>
							</td>
							<td class="hiddenReveal right">
								<select name="coursesParents" id="coursesParents" style="width: 302px">
									<?php
									print "<option value='Y'>" . _('Yes') . "</option>" ;
									print "<option selected value='N'>No</option>" ;
									?>
								</select>
							</td>
						</tr>
						<?php
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
								if ($('input[name=class]:checked').val()=="Y" ) {
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
							<b><?php print _('Class') ?></b><br/>
							<span style="font-size: 90%"><i><?php print _('Members of a class within a course.') ?><br/></i></span>
						</td>
						<td class="right">
							<input type="radio" name="class" class="class" value="Y"/> <?php print _('Yes') ?>
							<input checked type="radio" name="class" class="class" value="N"/> <?php print _('No') ?>
						</td>
					</tr>
					<tr id="classRow">
						<td class='hiddenReveal'> 
							<b><?php print _('Select Classes') ?></b><br/>
							<span style="font-size: 90%"><i><?php print _('Use Control, Command and/or Shift to select multiple.') ?></i></span>
						</td>
						<td class="hiddenReveal right">
							<select name="classes[]" id="classes[]" multiple style="width: 302px; height: 100px">
								<?php
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
						<td class='hiddenReveal'> 
							<b><?php print _('Include staff?') ?></b><br/>
						</td>
						<td class="hiddenReveal right">
							<select name="classesStaff" id="classesStaff" style="width: 302px">
								<?php
								print "<option value='Y'>" . _('Yes') . "</option>" ;
								print "<option value='N'>" . _('No') . "</option>" ;
								?>
							</select>
						</td>
					</tr>
					<tr id="classRow4">
						<td class='hiddenReveal'> 
							<b><?php print _('Include students?') ?></b><br/>
						</td>
						<td class="hiddenReveal right">
							<select name="classesStudents" id="classesStudents" style="width: 302px">
								<?php
								print "<option value='Y'>" . _('Yes') . "</option>" ;
								print "<option value='N'>" . _('No') . "</option>" ;
								?>
							</select>
						</td>
					</tr>
					<?php
					if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_classes_parents")) {
						?>
						<tr id="classRow2">
							<td class='hiddenReveal'> 
								<b><?php print _('Include parents?') ?></b><br/>
							</td>
							<td class="hiddenReveal right">
								<select name="classesParents" id="classesParents" style="width: 302px">
									<?php
									print "<option value='Y'>" . _('Yes') . "</option>" ;
									print "<option selected value='N'>No</option>" ;
									?>
								</select>
							</td>
						</tr>
						<?php
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
								if ($('input[name=activity]:checked').val()=="Y" ) {
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
							<b><?php print _('Activity') ?></b><br/>
							<span style="font-size: 90%"><i><?php print _('Members of an activity.') ?><br/></i></span>
						</td>
						<td class="right">
							<input type="radio" name="activity" class="activity" value="Y"/> <?php print _('Yes') ?>
							<input checked type="radio" name="activity" class="activity" value="N"/> <?php print _('No') ?>
						</td>
					</tr>
					<tr id="activitiesRow">
						<td class='hiddenReveal'> 
							<b><?php print _('Select Activities') ?></b><br/>
							<span style="font-size: 90%"><i><?php print _('Use Control, Command and/or Shift to select multiple.') ?></i></span>
						</td>
						<td class="hiddenReveal right">
							<select name="activities[]" id="activities[]" multiple style="width: 302px; height: 100px">
								<?php
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
						<td class='hiddenReveal'> 
							<b><?php print _('Include staff?') ?></b><br/>
						</td>
						<td class="hiddenReveal right">
							<select name="activitiesStaff" id="activitiesStaff" style="width: 302px">
								<?php
								print "<option value='Y'>" . _('Yes') . "</option>" ;
								print "<option value='N'>" . _('No') . "</option>" ;
								?>
							</select>
						</td>
					</tr>
					<tr id="activitiesRow4">
						<td class='hiddenReveal'> 
							<b><?php print _('Include students?') ?></b><br/>
						</td>
						<td class="hiddenReveal right">
							<select name="activitiesStudents" id="activitiesStudents" style="width: 302px">
								<?php
								print "<option value='Y'>" . _('Yes') . "</option>" ;
								print "<option value='N'>" . _('No') . "</option>" ;
								?>
							</select>
						</td>
					</tr>
					<?php
					if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_activities_parents")) {
						?>
						<tr id="activitiesRow2">
							<td class='hiddenReveal'> 
								<b><?php print _('Include parents?') ?></b><br/>
							</td>
							<td class="hiddenReveal right">
								<select name="activitiesParents" id="activitiesParents" style="width: 302px">
									<?php
									print "<option value='Y'>" . _('Yes') . "</option>" ;
									print "<option selected value='N'>No</option>" ;
									?>
								</select>
							</td>
						</tr>
						<?php
					}
				}
				if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_applicants")) {
					?>
					<script type="text/javascript">
						/* Role Control */
						$(document).ready(function(){
							$("#applicantsRow").css("display","none");
							$(".applicants").click(function(){
								if ($('input[name=applicants]:checked').val()=="Y" ) {
									$("#applicantsRow").slideDown("fast", $("#applicantsRow").css("display","table-row")); 
								} else {
									$("#applicantsRow").css("display","none");
								}
							 });
						});
					</script>
					<tr>
						<td> 
							<b><?php print _('Applicants') ?></b><br/>
							<span style="font-size: 90%"><i><?php print _('Applicants from a given year.') . "<br/>" . _('Does not apply to the message wall.') ?></i></span>
						</td>
						<td class="right">
							<input type="radio" name="applicants" class="applicants" value="Y"/> <?php print _('Yes') ?>
							<input checked type="radio" name="applicants" class="applicants" value="N"/> <?php print _('No') ?>
						</td>
					</tr>
					<tr id="applicantsRow">
						<td class='hiddenReveal'> 
							<b><?php print _('Select Years') ?></b><br/>
							<span style="font-size: 90%"><i><?php print _('Use Control, Command and/or Shift to select multiple.') ?></i></span>
						</td>
						<td class="hiddenReveal right">
							<select name="applicantList[]" id="applicantList[]" multiple style="width: 302px; height: 100px">
								<?php
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
					<?php
				}
				if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_houses_all") OR isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_houses_my")) {
					?>
					<script type="text/javascript">
						/* Role Control */
						$(document).ready(function(){
							$("#housesRow").css("display","none");
							$(".houses").click(function(){
								if ($('input[name=houses]:checked').val()=="Y" ) {
									$("#housesRow").slideDown("fast", $("#housesRow").css("display","table-row")); 
								} else {
									$("#housesRow").css("display","none");
								}
							 });
						});
					</script>
					<tr>
						<td> 
							<b><?php print _('Houses') ?></b><br/>
							<span style="font-size: 90%"><i><?php print _('Houses for competitions, etc.') ?><br/></i></span>
						</td>
						<td class="right">
							<input type="radio" name="houses" class="houses" value="Y"/> <?php print _('Yes') ?>
							<input checked type="radio" name="houses" class="houses" value="N"/> <?php print _('No') ?>
						</td>
					</tr>
					<tr id="housesRow">
						<td class='hiddenReveal'> 
							<b><?php print _('Select Houses') ?></b><br/>
							<span style="font-size: 90%"><i><?php print _('Use Control, Command and/or Shift to select multiple.') ?></i></span>
						</td>
						<td class="hiddenReveal right">
							<select name="houseList[]" id="houseList[]" multiple style="width: 302px; height: 100px">
								<?php
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
					<?php
				}
				if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_transport_any")) {
					?>
					<script type="text/javascript">
						/* yearGroup Control */
						$(document).ready(function(){
							$("#transportRow").css("display","none");
							$("#transportRow2").css("display","none");
							$("#transportRow3").css("display","none");
							$("#transportRow4").css("display","none");
							$(".transport").click(function(){
								if ($('input[name=transport]:checked').val()=="Y" ) {
									$("#transportRow").slideDown("fast", $("#transportRow").css("display","table-row")); 
									$("#transportRow2").slideDown("fast", $("#transportRow2").css("display","table-row")); 
									$("#transportRow3").slideDown("fast", $("#transportRow3").css("display","table-row")); 
									$("#transportRow4").slideDown("fast", $("#transportRow4").css("display","table-row")); 
								} else {
									$("#transportRow").css("display","none");
									$("#transportRow2").css("display","none");
									$("#transportRow3").css("display","none");
									$("#transportRow4").css("display","none");
								}
							 });
						});
					</script>
					<tr>
						<td> 
							<b><?php print _('Transport') ?></b><br/>
							<span style="font-size: 90%"><i><?php print _('Applies to all staff and students who have transport set.') ?><br/></i></span>
						</td>
						<td class="right">
							<input type="radio" name="transport" class="transport" value="Y"/> <?php print _('Yes') ?>
							<input checked type="radio" name="transport" class="transport" value="N"/> <?php print _('No') ?>
						</td>
					</tr>
					<tr id="transportRow">
						<td class='hiddenReveal'> 
							<b><?php print _('Select Transport') ?></b><br/>
							<span style="font-size: 90%"><i><?php print _('Use Control, Command and/or Shift to select multiple.') ?></i></span>
						</td>
						<td class="hiddenReveal right">
							<select name="transports[]" id="transports[]" multiple style="width: 302px; height: 100px">
								<?php
								try {
									$dataSelect=array(); 
									$sqlSelect="SELECT DISTINCT transport FROM gibbonPerson WHERE status='Full' AND NOT transport='' ORDER BY transport" ;
									$resultSelect=$connection2->prepare($sqlSelect);
									$resultSelect->execute($dataSelect);
								}
								catch(PDOException $e) { }
								while ($rowSelect=$resultSelect->fetch()) {
									print "<option value='" . htmlPrep($rowSelect["transport"]) . "'>" . htmlPrep(_($rowSelect["transport"])) . "</option>" ;
								}
								?>
							</select>
						</td>
					</tr>
					<tr id="transportRow3">
						<td class='hiddenReveal'> 
							<b><?php print _('Include staff?') ?></b><br/>
						</td>
						<td class="hiddenReveal right">
							<select name="transportStaff" id="transportStaff" style="width: 302px">
								<?php
								print "<option value='Y'>" . _('Yes') . "</option>" ;
								print "<option value='N'>" . _('No') . "</option>" ;
								?>
							</select>
						</td>
					</tr>
					<tr id="transportRow4">
						<td class='hiddenReveal'> 
							<b><?php print _('Include students?') ?></b><br/>
						</td>
						<td class="hiddenReveal right">
							<select name="transportStudents" id="transportStudents" style="width: 302px">
								<?php
								print "<option value='Y'>" . _('Yes') . "</option>" ;
								print "<option value='N'>" . _('No') . "</option>" ;
								?>
							</select>
						</td>
					</tr>
					<?php
					if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_transport_parents")) {
						?>
						<tr id="transportRow2">
							<td class='hiddenReveal'> 
								<b><?php print _('Include parents?') ?></b><br/>
							</td>
							<td class="hiddenReveal right">
								<select name="transportParents" id="transportParents" style="width: 302px">
									<?php
									print "<option value='Y'>" . _('Yes') . "</option>" ;
									print "<option selected value='N'>No</option>" ;
									?>
								</select>
							</td>
						</tr>
						<?php
					}
				}
				if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_individuals")) {
					?>
					<script type="text/javascript">
						/* Role Control */
						$(document).ready(function(){
							$("#individualsRow").css("display","none");
							$(".individuals").click(function(){
								if ($('input[name=individuals]:checked').val()=="Y" ) {
									$("#individualsRow").slideDown("fast", $("#individualsRow").css("display","table-row")); 
								} else {
									$("#individualsRow").css("display","none");
								}
							 });
						});
					</script>
					<tr>
						<td> 
							<b><?php print _('Indviduals') ?></b><br/>
							<span style="font-size: 90%"><i><?php print _('Individuals from the whole school.') ?><br/></i></span>
						</td>
						<td class="right">
							<input type="radio" name="individuals" class="individuals" value="Y"/> <?php print _('Yes') ?>
							<input checked type="radio" name="individuals" class="individuals" value="N"/> <?php print _('No') ?>
						</td>
					</tr>
					<tr id="individualsRow">
						<td class='hiddenReveal'> 
							<b><?php print _('Select Individuals') ?></b><br/>
							<span style="font-size: 90%"><i><?php print _('Use Control, Command and/or Shift to select multiple.') ?></i></span>
						</td>
						<td class="hiddenReveal right">
							<select name="individualList[]" id="individualList[]" multiple style="width: 302px; height: 100px">
								<?php
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
					<?php
				}
				?>
				
				<tr>
					<td>
						<span style="font-size: 90%"><i>* <?php print _("denotes a required field") ; ?></i></span>
					</td>
					<td class="right">
						<input type="submit" value="<?php print _("Submit") ; ?>">
					</td>
				</tr>
			</table>
		</form>
		<?php
	}
}
?>