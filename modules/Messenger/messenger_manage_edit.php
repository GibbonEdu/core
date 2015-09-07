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

if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_manage_edit.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Get action with highest precendence
	$highestAction=getHighestGroupedAction($guid, $_GET["q"], $connection2) ;
	if ($highestAction==FALSE) {
		print "<div class='error'>" ;
		print _("The highest grouped action cannot be determined.") ;
		print "</div>" ;
	}
	else {
		$search=NULL ;
		if (isset($_GET["search"])) {
			$search=$_GET["search"] ;
		}
		
		print "<div class='trail'>" ;
		print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . _(getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/messenger_manage.php&search=$search'>" . _('Manage Messages') . "</a> > </div><div class='trailEnd'>" . _('Edit Message') . "</div>" ;
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
			else if ($updateReturn=="success0") {
				$updateReturnMessage=_("Your request was completed successfully.") ;	
				$class="success" ;
			}
			print "<div class='$class'>" ;
				print $updateReturnMessage;
			print "</div>" ;
		} 
		
		//Check if school year specified
		$gibbonMessengerID=$_GET["gibbonMessengerID"] ;
		if ($gibbonMessengerID=="") {
			print "<div class='error'>" ;
				print _("You have not specified one or more required parameters.") ;
			print "</div>" ;
		}
		else {
			try {
				if ($highestAction=="Manage Messages_all") {
					$data=array("gibbonMessengerID"=>$gibbonMessengerID); 
					$sql="SELECT gibbonMessenger.*, title, surname, preferredName FROM gibbonMessenger JOIN gibbonPerson ON (gibbonMessenger.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonMessengerID=:gibbonMessengerID" ; 
				}
				else {
					$data=array("gibbonMessengerID"=>$gibbonMessengerID, "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
					$sql="SELECT gibbonMessenger.*, title, surname, preferredName FROM gibbonMessenger JOIN gibbonPerson ON (gibbonMessenger.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonMessengerID=:gibbonMessengerID AND gibbonMessenger.gibbonPersonID=:gibbonPersonID" ; 
				}
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
				?>
				<div class='warning'>
					<b><u><?php print _('Note') ?></u></b>: <?php print _('Changes made here do not apply to emails and SMS messages (which have already been sent), but only to message wall messages.') ?>
				</div>
				
				<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/messenger_manage_editProcess.php?gibbonMessengerID=$gibbonMessengerID&search=$search&address=" . $_GET["q"] ?>" enctype="multipart/form-data">
					<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
						<tr class='break'>
							<td colspan=2> 
								<h3><?php print _('Delivery Mode') ?></h3>
							</td>
						</tr>
						<?php
						if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_byEmail")) {
							?>
							<tr>
								<td style='width: 275px'> 
									<b><?php print _('Email') ?> *</b><br/>
									<span style="font-size: 90%"><i><?php print _('Deliver this message to user\'s primary email account?') ?><br/></i></span>
								</td>
								<td class="right">
									<?php 
									if ($row["email"]=="Y") {
										print "<img title='" . _('Sent by email.') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/iconTick.png'/> " ;
									}
									else {
										print "<img title='" . _('Not sent by email.') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/iconCross.png'/> " ;
									}
									?>
								</td>
							</tr>
							<?php
						}
						if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_byMessageWall")) {
							?>
							<script type="text/javascript">
								$(document).ready(function(){
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
									<input <?php if ($row["messageWall"]=="Y") { print "checked" ; } ?> type="radio" name="messageWall" class="messageWall" value="Y"/> <?php print _('Yes') ?>
									<input <?php if ($row["messageWall"]=="N") { print "checked" ; } ?> type="radio" name="messageWall" class="messageWall" value="N"/> <?php print _('No') ?>
								</td>
							</tr>
							<tr id="messageWallRow" <?php if ($row["messageWall"]=="N") { print "style='display: none'" ; } ?>>
								<td> 
									<b><?php print _('Publication Dates') ?> *</b><br/>
									<span style="font-size: 90%"><i><?php print _('Select up to three individual dates.') ?></br>Format <?php if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?>.<br/></i></span>
								</td>
								<td class="right">
									<input name="date1" id="date1" maxlength=10 value="<?php print dateConvertBack($guid, $row["messageWall_date1"]) ?>" type="text" style="width: 300px">
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
									<input name="date2" id="date2" maxlength=10 value="<?php print dateConvertBack($guid, $row["messageWall_date2"]) ?>" type="text" style="width: 300px; margin-top: 3px">
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
									<input name="date3" id="date3" maxlength=10 value="<?php print dateConvertBack($guid, $row["messageWall_date3"]) ?>" type="text" style="width: 300px; margin-top: 3px">
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
							?>
							<tr>
								<td> 
									<b><?php print _('SMS') ?> *</b><br/>
									<span style="font-size: 90%"><i><?php print _('Deliver this message to user\'s mobile phone?') ?><br/></i></span>
								</td>
								<td class="right">
									<?php
									if ($row["sms"]=="Y") {
										print "<img title='" . _('Sent by sms.') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/iconTick.png'/> " ;
									}
									else {
										print "<img title='" . _('Not sent by sms.') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/iconCross.png'/> " ;
									}
									?>
								</td>
							</tr>
							<?php
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
								<input name="subject" id="subject" maxlength=30 value="<?php print htmlPrep($row["subject"]) ?>" type="text" style="width: 300px">
								<script type="text/javascript">
									var subject=new LiveValidation('subject');
									subject.add(Validate.Presence);
								</script>
							</td>
						</tr>
						<tr>
							<td colspan=2> 
								<b><?php print _('Body') ?> *</b>
								<?php print getEditor($guid,  TRUE, "body", $row["body"], 20, true, true, false, true, "purpose=Mass%20Mailer%20Attachment" ) ?>
							</td>
						</tr>
						
						<tr class='break'>
							<td colspan=2> 
								<h3><?php print _('Targets') ?></h3>
							</td>
						</tr>
						<?php
						if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_role")) {
							//Role
							try {
								$dataTarget=array("gibbonMessengerID"=>$gibbonMessengerID); 
								$sqlTarget="SELECT * FROM gibbonMessengerTarget WHERE gibbonMessengerID=:gibbonMessengerID AND type='Role'" ;
								$resultTarget=$connection2->prepare($sqlTarget);
								$resultTarget->execute($dataTarget);
							}
							catch(PDOException $e) { 
								print "<div class='error'>" . $e->getMessage() . "</div>" ; 
							}
							?>
							<script type="text/javascript">
								/* Role Control */
								$(document).ready(function(){
									<?php if ($resultTarget->rowCount()<=0) { ?>
										$("#roleRow").css("display","none");
									<?php } ?>
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
									<input <?php if ($resultTarget->rowCount()>0) { print "checked" ; }?> type="radio" name="role" class="role" value="Y"/> <?php print _('Yes') ?>
									<input <?php if ($resultTarget->rowCount()<=0) { print "checked" ; }?> type="radio" name="role" class="role" value="N"/> <?php print _('No') ?>
								</td>
							</tr>
							<?php
							$selectedAll="" ;
							while ($rowTarget=$resultTarget->fetch()) {
								$selectedAll.=str_pad($rowTarget['id'], 3, "0", STR_PAD_LEFT) . "," ;
							}
							$selectedAll=substr($selectedAll,0,-1) ;
							?>
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
											$selected="" ;
											if (is_numeric(strpos($selectedAll,str_pad($rowSelect['gibbonRoleID'], 3, "0", STR_PAD_LEFT)))) {
												$selected="selected" ;
											}
											print "<option $selected value='" . $rowSelect["gibbonRoleID"] . "'>" . htmlPrep(_($rowSelect["name"])) . " (" . htmlPrep(_($rowSelect["category"])) . ")</option>" ;
										}
										?>
									</select>
								</td>
							</tr>
							
							<?php
							//Role Category
							try {
								$dataTarget=array("gibbonMessengerID"=>$gibbonMessengerID); 
								$sqlTarget="SELECT * FROM gibbonMessengerTarget WHERE gibbonMessengerID=:gibbonMessengerID AND type='Role Category'" ;
								$resultTarget=$connection2->prepare($sqlTarget);
								$resultTarget->execute($dataTarget);
							}
							catch(PDOException $e) { 
								print "<div class='error'>" . $e->getMessage() . "</div>" ; 
							}
							?>
							<script type="text/javascript">
								/* Role Category Control */
								$(document).ready(function(){
									<?php if ($resultTarget->rowCount()<=0) { ?>
										$("#roleCategoryRow").css("display","none");
									<?php } ?>
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
									<input <?php if ($resultTarget->rowCount()>0) { print "checked" ; }?> type="radio" name="roleCategory" class="roleCategory" value="Y"/> <?php print _('Yes') ?>
									<input <?php if ($resultTarget->rowCount()<=0) { print "checked" ; }?> type="radio" name="roleCategory" class="roleCategory" value="N"/> <?php print _('No') ?>
								</td>
							</tr>
							<?php
							$selectedAll="" ;
							while ($rowTarget=$resultTarget->fetch()) {
								$selectedAll.=$rowTarget['id'] . "," ;
							}
							$selectedAll=substr($selectedAll,0,-1) ;
							?>
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
											$selected="" ;
											if (is_numeric(strpos($selectedAll,$rowSelect['category']))) {
												$selected="selected" ;
											}
											print "<option $selected value='" . $rowSelect["category"] . "'>" . htmlPrep(_($rowSelect["category"])) . "</option>" ;
										}
										?>
									</select>
								</td>
							</tr>
							<?php
						}
						if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_yearGroups_any")) {
							try {
								$dataTarget=array("gibbonMessengerID"=>$gibbonMessengerID); 
								$sqlTarget="SELECT * FROM gibbonMessengerTarget WHERE gibbonMessengerID=:gibbonMessengerID AND type='Year Group'" ;
								$resultTarget=$connection2->prepare($sqlTarget);
								$resultTarget->execute($dataTarget);
							}
							catch(PDOException $e) { 
								print "<div class='error'>" . $e->getMessage() . "</div>" ; 
							}
							
							?>
							<script type="text/javascript">
								/* yearGroup Control */
								$(document).ready(function(){
									<?php if ($resultTarget->rowCount()<=0) { ?>
										$("#yearGroupRow").css("display","none");
										$("#yearGroupRow2").css("display","none");
										$("#yearGroupRow3").css("display","none");
										$("#yearGroupRow4").css("display","none");
									<?php } ?>
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
									<input <?php if ($resultTarget->rowCount()>0) { print "checked" ; }?> type="radio" name="yearGroup" class="yearGroup" value="Y"/> <?php print _('Yes') ?>
									<input <?php if ($resultTarget->rowCount()<=0) { print "checked" ; }?> type="radio" name="yearGroup" class="yearGroup" value="N"/> <?php print _('No') ?>
								</td>
							</tr>
							<?php
							$selectedAll="" ;
							$staff=TRUE ;
							$students=TRUE ;
							$parents=TRUE ;
							while ($rowTarget=$resultTarget->fetch()) {
								$selectedAll.=str_pad($rowTarget['id'], 3, "0", STR_PAD_LEFT) . "," ;
								if ($rowTarget["staff"]=="N") {
									$staff=FALSE ;
								}
								if ($rowTarget["students"]=="N") {
									$students=FALSE ;
								}
								if ($rowTarget["parents"]=="N") {
									$parents=FALSE ;
								}
							}
							$selectedAll=substr($selectedAll,0,-1) ;
							?>
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
											$selected="" ;
											if (is_numeric(strpos($selectedAll,str_pad($rowSelect['gibbonYearGroupID'], 3, "0", STR_PAD_LEFT)))) {
												$selected="selected" ;
											}
											print "<option $selected value='" . $rowSelect["gibbonYearGroupID"] . "'>" . htmlPrep(_($rowSelect["name"])) . "</option>" ;
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
										$selected="" ;
										if ($staff==FALSE) {
											$selected="selected" ; ;
										}
										print "<option value='Y'>Yes</option>" ;
										print "<option $selected value='N'>No</option>" ;
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
										$selected="" ;
										if ($students==FALSE) {
											$selected="selected" ; ;
										}
										print "<option value='Y'>Yes</option>" ;
										print "<option $selected value='N'>No</option>" ;
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
											$selected="" ;
											if ($parents==FALSE) {
												$selected="selected" ; ;
											}
											print "<option value='Y'>Yes</option>" ;
											print "<option $selected value='N'>No</option>" ;
											?>
										</select>
									</td>
								</tr>
								<?php
							}
						}
						if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_rollGroups_my") OR isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_rollGroups_any")) {
							try {
								$dataTarget=array("gibbonMessengerID"=>$gibbonMessengerID); 
								$sqlTarget="SELECT * FROM gibbonMessengerTarget WHERE gibbonMessengerID=:gibbonMessengerID AND type='RolL Group'" ;
								$resultTarget=$connection2->prepare($sqlTarget);
								$resultTarget->execute($dataTarget);
							}
							catch(PDOException $e) { 
								print "<div class='error'>" . $e->getMessage() . "</div>" ; 
							}
							?>
							<script type="text/javascript">
								/* rollGroup Control */
								$(document).ready(function(){
									<?php if ($resultTarget->rowCount()<=0) { ?>
										$("#rollGroupRow").css("display","none");
										$("#rollGroupRow2").css("display","none");
										$("#rollGroupRow3").css("display","none");
										$("#rollGroupRow4").css("display","none");
									<?php } ?>
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
									<input <?php if ($resultTarget->rowCount()>0) { print "checked" ; }?> type="radio" name="rollGroup" class="rollGroup" value="Y"/> <?php print _('Yes') ?>
									<input <?php if ($resultTarget->rowCount()<=0) { print "checked" ; }?> type="radio" name="rollGroup" class="rollGroup" value="N"/> <?php print _('No') ?>
								</td>
							</tr>
							<?php
							$selectedAll="" ;
							$staff=TRUE ;
							$students=TRUE ;
							$parents=TRUE ;
							while ($rowTarget=$resultTarget->fetch()) {
								$selectedAll.=str_pad($rowTarget['id'], 5, "0", STR_PAD_LEFT) . "," ;
								if ($rowTarget["staff"]=="N") {
									$staff=FALSE ;
								}
								if ($rowTarget["students"]=="N") {
									$students=FALSE ;
								}
								if ($rowTarget["parents"]=="N") {
									$parents=FALSE ;
								}	
							}
							$selectedAll=substr($selectedAll,0,-1) ;
							?>
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
											$selected="" ;
											if (is_numeric(strpos($selectedAll,str_pad($rowSelect['gibbonRollGroupID'], 5, "0", STR_PAD_LEFT)))) {
												$selected="selected" ;
											}
											print "<option $selected value='" . $rowSelect["gibbonRollGroupID"] . "'>" . htmlPrep($rowSelect["name"]) . "</option>" ;
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
										$selected="" ;
										if ($staff==FALSE) {
											$selected="selected" ; ;
										}
										print "<option value='Y'>Yes</option>" ;
										print "<option $selected value='N'>No</option>" ;
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
										$selected="" ;
										if ($students==FALSE) {
											$selected="selected" ; ;
										}
										print "<option value='Y'>Yes</option>" ;
										print "<option $selected value='N'>No</option>" ;
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
											$selected="" ;
											if ($parents==FALSE) {
												$selected="selected" ; ;
											}
											print "<option value='Y'>Yes</option>" ;
											print "<option $selected value='N'>No</option>" ;
											?>
										</select>
									</td>
								</tr>
								<?php
							}
						}
						if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_courses_my") OR isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_courses_any")) {
							try {
								$dataTarget=array("gibbonMessengerID"=>$gibbonMessengerID); 
								$sqlTarget="SELECT * FROM gibbonMessengerTarget WHERE gibbonMessengerID=:gibbonMessengerID AND type='Course'" ;
								$resultTarget=$connection2->prepare($sqlTarget);
								$resultTarget->execute($dataTarget);
							}
							catch(PDOException $e) { 
								print "<div class='error'>" . $e->getMessage() . "</div>" ; 
							}
							?>
							<script type="text/javascript">
								/* course Control */
								$(document).ready(function(){
									<?php if ($resultTarget->rowCount()<=0) { ?>
										$("#courseRow").css("display","none");
										$("#courseRow2").css("display","none");
										$("#courseRow3").css("display","none");
										$("#courseRow4").css("display","none");
									<?php } ?>
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
									<input <?php if ($resultTarget->rowCount()>0) { print "checked" ; }?> type="radio" name="course" class="course" value="Y"/> <?php print _('Yes') ?>
									<input <?php if ($resultTarget->rowCount()<=0) { print "checked" ; }?> type="radio" name="course" class="course" value="N"/> <?php print _('No') ?>
								</td>
							</tr>
							<?php
							$selectedAll="" ;
							$staff=TRUE ;
							$students=TRUE ;
							$parents=TRUE ;
							while ($rowTarget=$resultTarget->fetch()) {
								$selectedAll.=str_pad($rowTarget['id'], 8, "0", STR_PAD_LEFT) . "," ;
								if ($rowTarget["staff"]=="N") {
									$staff=FALSE ;
								}
								if ($rowTarget["students"]=="N") {
									$students=FALSE ;
								}
								if ($rowTarget["parents"]=="N") {
									$parents=FALSE ;
								}		
							}
							$selectedAll=substr($selectedAll,0,-1) ;
							?>
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
											$selected="" ;
											if (is_numeric(strpos($selectedAll,str_pad($rowSelect['gibbonCourseID'], 8, "0", STR_PAD_LEFT)))) {
												$selected="selected" ;
											}
											print "<option $selected value='" . $rowSelect["gibbonCourseID"] . "'>" . htmlPrep($rowSelect["nameShort"]) . "</option>" ;
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
										$selected="" ;
										if ($staff==TRUE) {
											$selected="selected" ; ;
										}
										print "<option value='N'>" . _('No') . "</option>" ;
										print "<option $selected value='Y'>Yes</option>" ;
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
										$selected="" ;
										if ($students==TRUE) {
											$selected="selected" ; ;
										}
										print "<option value='N'>" . _('No') . "</option>" ;
										print "<option $selected value='Y'>Yes</option>" ;
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
											$selected="" ;
											if ($parents==TRUE) {
												$selected="selected" ; ;
											}
											print "<option value='N'>" . _('No') . "</option>" ;
											print "<option $selected value='Y'>Yes</option>" ;
											?>
										</select>
									</td>
								</tr>
								<?php
							}
						}
						if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_classes_my") OR isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_classes_any")) {
							try {
								$dataTarget=array("gibbonMessengerID"=>$gibbonMessengerID); 
								$sqlTarget="SELECT * FROM gibbonMessengerTarget WHERE gibbonMessengerID=:gibbonMessengerID AND type='Class'" ;
								$resultTarget=$connection2->prepare($sqlTarget);
								$resultTarget->execute($dataTarget);
							}
							catch(PDOException $e) { 
								print "<div class='error'>" . $e->getMessage() . "</div>" ; 
							}
							?>
							<script type="text/javascript">
								/* class Control */
								$(document).ready(function(){
									<?php if ($resultTarget->rowCount()<=0) { ?>
										$("#classRow").css("display","none");
										$("#classRow2").css("display","none");
										$("#classRow3").css("display","none");
										$("#classRow4").css("display","none");
									<?php } ?>
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
									<input <?php if ($resultTarget->rowCount()>0) { print "checked" ; }?> type="radio" name="class" class="class" value="Y"/> <?php print _('Yes') ?>
									<input <?php if ($resultTarget->rowCount()<=0) { print "checked" ; }?> type="radio" name="class" class="class" value="N"/> <?php print _('No') ?>
								</td>
							</tr>
							<?php
							$selectedAll="" ;
							$staff=TRUE ;
							$students=TRUE ;
							$parents=TRUE ;
							while ($rowTarget=$resultTarget->fetch()) {
								$selectedAll.=str_pad($rowTarget['id'], 8, "0", STR_PAD_LEFT) . "," ;
								if ($rowTarget["staff"]=="N") {
									$staff=FALSE ;
								}
								if ($rowTarget["students"]=="N") {
									$students=FALSE ;
								}
								if ($rowTarget["parents"]=="N") {
									$parents=FALSE ;
								}	
							}
							$selectedAll=substr($selectedAll,0,-1) ;
							?>
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
											$selected="" ;
											if (is_numeric(strpos($selectedAll,str_pad($rowSelect['gibbonCourseClassID'], 8, "0", STR_PAD_LEFT)))) {
												$selected="selected" ;
											}
											print "<option $selected value='" . $rowSelect["gibbonCourseClassID"] . "'>" . htmlPrep($rowSelect["course"]) . "." . htmlPrep($rowSelect["class"]) . "</option>" ;
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
										$selected="" ;
										if ($staff==FALSE) {
											$selected="selected" ; ;
										}
										print "<option value='Y'>Yes</option>" ;
										print "<option $selected value='N'>No</option>" ;
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
										$selected="" ;
										if ($students==FALSE) {
											$selected="selected" ; ;
										}
										print "<option value='Y'>Yes</option>" ;
										print "<option $selected value='N'>No</option>" ;
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
											$selected="" ;
											if ($parents==FALSE) {
												$selected="selected" ; ;
											}
											print "<option value='Y'>Yes</option>" ;
											print "<option $selected value='N'>No</option>" ;
											?>
										</select>
									</td>
								</tr>
								<?php
							}
						}
						if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_activities_my") OR isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_activities_any")) {
							try {
								$dataTarget=array("gibbonMessengerID"=>$gibbonMessengerID); 
								$sqlTarget="SELECT * FROM gibbonMessengerTarget WHERE gibbonMessengerID=:gibbonMessengerID AND type='Activity'" ;
								$resultTarget=$connection2->prepare($sqlTarget);
								$resultTarget->execute($dataTarget);
							}
							catch(PDOException $e) { 
								print "<div class='error'>" . $e->getMessage() . "</div>" ; 
							}
							?>
							<script type="text/javascript">
								/* activity Control */
								$(document).ready(function(){
									<?php if ($resultTarget->rowCount()<=0) { ?>
										$("#activitiesRow").css("display","none");
										$("#activitiesRow2").css("display","none");
										$("#activitiesRow3").css("display","none");
										$("#activitiesRow4").css("display","none");
									<?php } ?>
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
									<input <?php if ($resultTarget->rowCount()>0) { print "checked" ; }?> type="radio" name="activity" class="activity" value="Y"/> <?php print _('Yes') ?>
									<input <?php if ($resultTarget->rowCount()<=0) { print "checked" ; }?> type="radio" name="activity" class="activity" value="N"/> <?php print _('No') ?>
								</td>
							</tr>
							<?php
							$selectedAll="" ;
							$staff=TRUE ;
							$students=TRUE ;
							$parents=TRUE ;
							while ($rowTarget=$resultTarget->fetch()) {
								$selectedAll.=str_pad($rowTarget['id'], 8, "0", STR_PAD_LEFT) . "," ;
								if ($rowTarget["staff"]=="N") {
									$staff=FALSE ;
								}
								if ($rowTarget["students"]=="N") {
									$students=FALSE ;
								}
								if ($rowTarget["parents"]=="N") {
									$parents=FALSE ;
								}		
							}
							$selectedAll=substr($selectedAll,0,-1) ;
							?>
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
											$selected="" ;
											if (is_numeric(strpos($selectedAll,str_pad($rowSelect['gibbonActivityID'], 8, "0", STR_PAD_LEFT)))) {
												$selected="selected" ;
											}
											print "<option $selected value='" . $rowSelect["gibbonActivityID"] . "'>" . htmlPrep($rowSelect["name"]) . "</option>" ;
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
										$selected="" ;
										if ($staff==FALSE) {
											$selected="selected" ; ;
										}
										print "<option value='Y'>Yes</option>" ;
										print "<option $selected value='N'>No</option>" ;
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
										$selected="" ;
										if ($students==FALSE) {
											$selected="selected" ; ;
										}
										print "<option value='Y'>Yes</option>" ;
										print "<option $selected value='N'>No</option>" ;
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
											$selected="" ;
											if ($parents==FALSE) {
												$selected="selected" ; ;
											}
											print "<option value='Y'>Yes</option>" ;
											print "<option $selected value='N'>No</option>" ;
											?>
										</select>
									</td>
								</tr>
								<?php
							}
						}
						if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_applicants")) {
							try {
								$dataTarget=array("gibbonMessengerID"=>$gibbonMessengerID); 
								$sqlTarget="SELECT * FROM gibbonMessengerTarget WHERE gibbonMessengerID=:gibbonMessengerID AND type='Applicants'" ;
								$resultTarget=$connection2->prepare($sqlTarget);
								$resultTarget->execute($dataTarget);
							}
							catch(PDOException $e) { 
								print "<div class='error'>" . $e->getMessage() . "</div>" ; 
							}
							?>
							<script type="text/javascript">
								/* Role Control */
								$(document).ready(function(){
									<?php if ($resultTarget->rowCount()<=0) { ?>
										$("#applicantsRow").css("display","none");
									<?php } ?>
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
									<input <?php if ($resultTarget->rowCount()>0) { print "checked" ; }?> type="radio" name="applicants" class="applicants" value="Y"/> <?php print _('Yes') ?>
									<input <?php if ($resultTarget->rowCount()<=0) { print "checked" ; }?> type="radio" name="applicants" class="applicants" value="N"/> <?php print _('No') ?>
								</td>
							</tr>
							<?php
							$selectedAll="" ;
							while ($rowTarget=$resultTarget->fetch()) {
								$selectedAll.=str_pad($rowTarget['id'], 3, "0", STR_PAD_LEFT) . "," ;
							}
							$selectedAll=substr($selectedAll,0,-1) ;
							?>
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
											$selected="" ;
											if (is_numeric(strpos($selectedAll,str_pad($rowSelect['gibbonSchoolYearID'], 3, "0", STR_PAD_LEFT)))) {
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
						if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_houses_all") OR isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_houses_my")) {
							try {
								$dataTarget=array("gibbonMessengerID"=>$gibbonMessengerID); 
								$sqlTarget="SELECT * FROM gibbonMessengerTarget WHERE gibbonMessengerID=:gibbonMessengerID AND type='Houses'" ;
								$resultTarget=$connection2->prepare($sqlTarget);
								$resultTarget->execute($dataTarget);
							}
							catch(PDOException $e) { 
								print "<div class='error'>" . $e->getMessage() . "</div>" ; 
							}
							?>
							<script type="text/javascript">
								/* Role Control */
								$(document).ready(function(){
									<?php if ($resultTarget->rowCount()<=0) { ?>
										$("#housesRow").css("display","none");
									<?php } ?>
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
									<input <?php if ($resultTarget->rowCount()>0) { print "checked" ; }?> type="radio" name="houses" class="houses" value="Y"/> <?php print _('Yes') ?>
									<input <?php if ($resultTarget->rowCount()<=0) { print "checked" ; }?> type="radio" name="houses" class="houses" value="N"/> <?php print _('No') ?>
								</td>
							</tr>
							<?php
							$selectedAll="" ;
							while ($rowTarget=$resultTarget->fetch()) {
								$selectedAll.=str_pad($rowTarget['id'], 3, "0", STR_PAD_LEFT) . "," ;
							}
							$selectedAll=substr($selectedAll,0,-1) ;
							?>
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
											$selected="" ;
											if (is_numeric(strpos($selectedAll,str_pad($rowSelect['gibbonHouseID'], 3, "0", STR_PAD_LEFT)))) {
												$selected="selected" ;
											}
											print "<option $selected value='" . $rowSelect["gibbonHouseID"] . "'>" . htmlPrep($rowSelect["name"]) . "</option>" ;
										}
										?>
									</select>
								</td>
							</tr>
							<?php
						}
						if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_individuals")) {
							try {
								$dataTarget=array("gibbonMessengerID"=>$gibbonMessengerID); 
								$sqlTarget="SELECT * FROM gibbonMessengerTarget WHERE gibbonMessengerID=:gibbonMessengerID AND type='Individuals'" ;
								$resultTarget=$connection2->prepare($sqlTarget);
								$resultTarget->execute($dataTarget);
							}
							catch(PDOException $e) { 
								print "<div class='error'>" . $e->getMessage() . "</div>" ; 
							}
							?>
							<script type="text/javascript">
								/* Role Control */
								$(document).ready(function(){
									<?php if ($resultTarget->rowCount()<=0) { ?>
										$("#individualsRow").css("display","none");
									<?php } ?>
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
									<input <?php if ($resultTarget->rowCount()>0) { print "checked" ; }?> type="radio" name="individuals" class="individuals" value="Y"/> <?php print _('Yes') ?>
									<input <?php if ($resultTarget->rowCount()<=0) { print "checked" ; }?> type="radio" name="individuals" class="individuals" value="N"/> <?php print _('No') ?>
								</td>
							</tr>
							<?php
							$selectedAll="" ;
							while ($rowTarget=$resultTarget->fetch()) {
								$selectedAll.=str_pad($rowTarget['id'], 10, "0", STR_PAD_LEFT) . "," ;
							}
							$selectedAll=substr($selectedAll,0,-1) ;
							?>
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
											$selected="" ;
											if (is_numeric(strpos($selectedAll,str_pad($rowSelect['gibbonPersonID'], 10, "0", STR_PAD_LEFT)))) {
												$selected="selected" ;
											}
											print "<option $selected value='" . $rowSelect["gibbonPersonID"] . "'>" . formatName("", $rowSelect["preferredName"], $rowSelect["surname"], "Student", true) . "</option>" ;
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
	}
}
?>