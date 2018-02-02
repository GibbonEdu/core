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

use Gibbon\Forms\Form;

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
	include_once "./modules/" . $_SESSION[$guid]["module"] . "/moduleFunctions.php" ;
}
if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	if ($_SESSION[$guid]["email"]=="") {
		print "<div class='error'>" ;
			print __("You do not have a personal email address set in Gibbon, and so cannot send out emails.") ;
		print "</div>" ;
	}
	else {
		//Proceed!
		print "<div class='trail'>" ;
		print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __(getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . __('New Message') . "</div>" ;
		print "</div>" ;

		if (isset($_GET["addReturn"])) { $addReturn=$_GET["addReturn"] ; } else { $addReturn="" ; }
		$addReturnMessage="" ;
		$class="error" ;
		if (!($addReturn=="")) {
			if ($addReturn=="fail0") {
				$addReturnMessage=__("Your request failed because you do not have access to this action.") ;
			}
			else if ($addReturn=="fail2") {
				$addReturnMessage=__("Your request failed due to a database error.") ;
			}
			else if ($addReturn=="fail3") {
				$addReturnMessage=__("Your request failed because your inputs were invalid.") ;
			}
			else if ($addReturn=="fail4") {
				$addReturnMessage=__("Your request was completed successfully, but some or all messages could not be delivered.") ;
			}
			else if ($addReturn=="fail5") {
				$addReturnMessage=__("Your request failed due to an attachment error.") ;
			}
			else if ($addReturn=="success0") {
				$addReturnMessage=__("Your request was completed successfully: not all messages may arrive at their destination, but an attempt has been made to get them all out.") ;
				if (is_numeric($_GET["emailCount"])) {
					$addReturnMessage.=" " . sprintf(__('%1$s email(s) were dispatched.'), $_GET["emailCount"]) ;
				}
				if (is_numeric($_GET["smsCount"]) AND is_numeric($_GET["smsBatchCount"])) {
					$addReturnMessage.=" " . sprintf(__('%1$s SMS(es) were dispatched in %2$s batch(es).'), $_GET["smsCount"], $_GET["smsBatchCount"]) ;
				}

				$class="success" ;
			}
			print "<div class='$class'>" ;
				print $addReturnMessage;
			print "</div>" ;
		}

		print "<div class='warning'>" ;
			print sprintf(__('Each family in Gibbon must have one parent who is contact priority 1, and who must be enabled to receive email and SMS messages from %1$s. As a result, when targetting parents, you can be fairly certain that messages should get through to each family.'), $_SESSION[$guid]["organisationNameShort"]) ;
		print "</div>" ;

		$form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/messenger_postProcess.php');

		$form->addHiddenValue('address', $_SESSION[$guid]['address']);

		//DELIVERY MODE
		$form->addRow()->addHeading(__('Delivery Mode'));
		//Delivery by email
		if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_byEmail")) {
			$row = $form->addRow();
				$row->addLabel('email', __('Email'))->description(__('Deliver this message to user\'s primary email account?'));
				$row->addRadio('email')
					->fromArray(array('Y' => __('Yes'), 'N' => __('No')))->checked('Y')->inline()->isRequired();

			$form->toggleVisibilityByClass('email')->onRadio('email')->when('Y');

			$from = array($_SESSION[$guid]["email"] => $_SESSION[$guid]["email"]);
			if ($_SESSION[$guid]["emailAlternate"] != "") {
				$from[$_SESSION[$guid]["emailAlternate"]] = $_SESSION[$guid]["emailAlternate"];
			}
			if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_fromSchool") AND $_SESSION[$guid]["organisationEmail"] != "") {
				$from[$_SESSION[$guid]["organisationEmail"]] = $_SESSION[$guid]["organisationEmail"];
			}
			$row = $form->addRow()->addClass('email');
				$row->addLabel('from', __('Email From'));
				$row->addSelect('from')->fromArray($from)->isRequired();

			if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_fromSchool")) {
				$row = $form->addRow()->addClass('email');
					$row->addLabel('emailReplyTo', __('Reply To'));
					$row->addEmail('emailReplyTo')->maxLength(50);
			}
		}

		//Delivery by message wall
		if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_byMessageWall")) {
			$row = $form->addRow();
				$row->addLabel('messageWall', __('Message Wall'))->description(__('Place this message on user\'s message wall?'));
				$row->addRadio('messageWall')
					->fromArray(array('Y' => __('Yes'), 'N' => __('No')))->checked('N')->inline()->isRequired();

			$form->toggleVisibilityByClass('messageWall')->onRadio('messageWall')->when('Y');

			$row = $form->addRow()->addClass('messageWall');
				$row->addLabel('date1', __('Publication Date 1'))->description($_SESSION[$guid]['i18n']['dateFormat'])->prepend(__('Format:'));
				$row->addDate('date1')->isRequired();

			$row = $form->addRow()->addClass('messageWall');
				$row->addLabel('date2', __('Publication Date 2'));
				$row->addDate('date2');

			$row = $form->addRow()->addClass('messageWall');
				$row->addLabel('date3', __('Publication Date 3'));
				$row->addDate('date3');
		}

		//Delivery by SMS
		if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_bySMS")) {
			$smsUsername=getSettingByScope( $connection2, "Messenger", "smsUsername" ) ;
			$smsPassword=getSettingByScope( $connection2, "Messenger", "smsPassword" ) ;
			$smsURL=getSettingByScope( $connection2, "Messenger", "smsURL" ) ;
			$smsURLCredit=getSettingByScope( $connection2, "Messenger", "smsURLCredit" ) ;
			if ($smsUsername == "" OR $smsPassword == "" OR $smsURL == "") {
				$form->addRow()->addAlert(sprintf(__('SMS NOT CONFIGURED. Please contact %1$s for help.'), "<a href='mailto:" . $_SESSION[$guid]["organisationAdministratorEmail"] . "'>" . $_SESSION[$guid]["organisationAdministratorName"] . "</a>"), 'error');
			}
			else {
				$row = $form->addRow();
					$row->addLabel('sms', __('SMS'))->description(__('Deliver this message to user\'s mobile phone?'));
					$row->addRadio('sms')
						->fromArray(array('Y' => __('Yes'), 'N' => __('No')))->checked('N')->inline()->isRequired();

				$form->toggleVisibilityByClass('sms')->onRadio('sms')->when('Y');

				$smsAlert = __('SMS messages are sent to local and overseas numbers, but not all countries are supported. Please see the SMS Gateway provider\'s documentation or error log to see which countries are not supported. The subject does not get sent, and all HTML tags are removed. Each message, to each recipient, will incur a charge (dependent on your SMS gateway provider). Messages over 140 characters will get broken into smaller messages, and will cost more.').'<br/><br/>';
				if ($smsURLCredit!="") {
					$query="?apiusername=" . $smsUsername . "&apipassword=" . $smsPassword ;
					$result=@implode('', file($smsURLCredit . $query)) ;
					if (is_numeric($result)==FALSE) {
						$result=0 ;
					}
					if ($result>=0) {
						$smsAlert .= "<b>" . sprintf(__('Current balance: %1$s credit(s).'), $result) . "</u></b>" ;
					}
				}
				$form->addRow()->addAlert($smsAlert, 'error')->addClass('sms');
			}
		}


		//MESSAGE DETAILS
		$form->addRow()->addHeading(__('Message Details'));

		$signature = getSignature($guid, $connection2, $_SESSION[$guid]["gibbonPersonID"]) ;

		$cannedResponse = isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", 'New Message_cannedResponse');
		if ($cannedResponse) {
			try {
				$dataSelect=array();
				$sqlSelect="SELECT * FROM gibbonMessengerCannedResponse ORDER BY subject" ;
				$resultSelect=$connection2->prepare($sqlSelect);
				$resultSelect->execute($dataSelect);
			}
			catch(PDOException $e) { }
			if ($resultSelect->rowCount()>0) {
				$cannedResponses=$resultSelect->fetchAll() ;

				//Set up JS to deal with canned response selection
				print "<script type=\"text/javascript\">" ;
					print "$(document).ready(function(){" ;
						print "$(\"#cannedResponse\").change(function(){" ;
							print "if (confirm(\"Are you sure you want to insert these records.\")==1) {" ;
								print "if ($('#cannedResponse').val()==\"\" ) {" ;
									print "$('#subject').val('');" ;
									print "tinyMCE.execCommand('mceRemoveEditor', false, 'body') ;" ;
									print "$('#body').val('" . addSlashes($signature) . "');" ;
									print "tinyMCE.execCommand('mceAddEditor', false, 'body') ;" ;
								print "}" ;
								foreach ($cannedResponses AS $rowSelect) {
									print "if ($('#cannedResponse').val()==\"" . $rowSelect["gibbonMessengerCannedResponseID"] . "\" ) {" ;
										print "$('#subject').val('" . htmlPrep($rowSelect["subject"]) . "');" ;
										print "tinyMCE.execCommand('mceRemoveEditor', false, 'body') ;" ;
										print "
											$.get('./modules/Messenger/messenger_post_ajax.php?gibbonMessengerCannedResponseID=" . $rowSelect["gibbonMessengerCannedResponseID"] . "', function(response) {
												 var result = response;
												$('#body').val(result + '" . addSlashes($signature) . "');
												tinyMCE.execCommand('mceAddEditor', false, 'body') ;
											});
										" ;
									print "}" ;
								}
							print "}" ;
							print "else {" ;
								print "$('#cannedResponse').val('')" ;
							print "}" ;
						print "});" ;
					print "});" ;
				print "</script>" ;

				$cans = array();
				foreach ($cannedResponses AS $rowSelect) {
					$cans[$rowSelect["gibbonMessengerCannedResponseID"]] = $rowSelect["subject"];
				}
				$row = $form->addRow();
					$row->addLabel('cannedResponse', __('Canned Response'));
					$row->addSelect('cannedResponse')->fromArray($cans)->placeholder();
			}
		}

		$row = $form->addRow();
			$row->addLabel('subject', __('Subject'));
			$row->addTextField('subject')->maxLength(30)->isRequired();

		$row = $form->addRow();
	        $col = $row->addColumn('body');
	        $col->addLabel('body', __('Body'));
	        $col->addEditor('body', $guid)->isRequired()->setRows(20)->showMedia(true)->setValue($signature);


		//READ RECEIPTS
		if (!isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_readReceipts")) {
			$form->addHiddenValue('emailReceipt', 'N');
		}
		else {
			$form->addRow()->addHeading(__('Email Read Receipts'));
			$form->addRow()->addContent(__('With read receipts enabled, the text [confirmLink] can be included in a message to add a unique, login-free read receipt link. If [confirmLink] is not included, the link will be appended to the end of the message.'));

			$row = $form->addRow();
				$row->addLabel('emailReceipt', __('Enable Read Receipts'))->description(__('Each email recipient will receive a personalised confirmation link.'));
				$row->addRadio('emailReceipt')
					->fromArray(array('Y' => __('Yes'), 'N' => __('No')))->checked('N')->inline()->isRequired();

			$form->toggleVisibilityByClass('emailReceipt')->onRadio('emailReceipt')->when('Y');

			$row = $form->addRow()->addClass('emailReceipt');
				$row->addLabel('emailReceiptText', __('Link Text'))->description(__('Confirmation link text to display to recipient.'));
				$row->addTextArea('emailReceiptText')->setRows(3)->isRequired()->setValue(__('By clicking on this link I agree that I have read, and agree to, the text contained within this email.'));

		}


		//TARGETS
		$form->addRow()->addHeading(__('Targets'));
		$roleCategory = getRoleCategory($_SESSION[$guid]["gibbonRoleIDCurrent"], $connection2);
		//Role
		if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_role")) {
			$row = $form->addRow();
				$row->addLabel('role', __('Role'))->description(__('Users of a certain type.'));
				$row->addRadio('role')
					->fromArray(array('Y' => __('Yes'), 'N' => __('No')))->checked('N')->inline()->isRequired();

			$form->toggleVisibilityByClass('role')->onRadio('role')->when('Y');

			$data = array();
			$sql = 'SELECT gibbonRoleID AS value, name FROM gibbonRole ORDER BY name';
			$row = $form->addRow()->addClass('role');
		        $row->addLabel('roles[]', __('Select Roles'));
		        $row->addSelect('roles[]')->fromQuery($pdo, $sql, $data)->selectMultiple()->setSize(6)->isRequired()->placeholder();

			//Role Category
			$row = $form->addRow();
				$row->addLabel('roleCategory', __('Role Category'))->description(__('Users of a certain type.'));
				$row->addRadio('roleCategory')
					->fromArray(array('Y' => __('Yes'), 'N' => __('No')))->checked('N')->inline()->isRequired();

			$form->toggleVisibilityByClass('roleCategory')->onRadio('roleCategory')->when('Y');

			$data = array();
			$sql = 'SELECT DISTINCT category AS value, category AS name FROM gibbonRole ORDER BY category';
			$row = $form->addRow()->addClass('roleCategory');
		        $row->addLabel('roles[]', __('Select Role Categories'));
		        $row->addSelect('roles[]')->fromQuery($pdo, $sql, $data)->selectMultiple()->setSize(4)->isRequired()->placeholder();
		}

		//Year group
		if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_yearGroups_any")) {
			$row = $form->addRow();
				$row->addLabel('yearGroup', __('Year Group'))->description(__('Students in year; all staff.'));
				$row->addRadio('yearGroup')
					->fromArray(array('Y' => __('Yes'), 'N' => __('No')))->checked('N')->inline()->isRequired();

			$form->toggleVisibilityByClass('yearGroup')->onRadio('yearGroup')->when('Y');

			$data = array();
			$sql = 'SELECT gibbonYearGroupID AS value, name FROM gibbonYearGroup ORDER BY sequenceNumber';
			$row = $form->addRow()->addClass('yearGroup');
				$row->addLabel('yearGroups[]', __('Select Year Groups'));
				$row->addSelect('yearGroups[]')->fromQuery($pdo, $sql, $data)->selectMultiple()->setSize(6)->isRequired()->placeholder();

			$row = $form->addRow()->addClass('yearGroup');
		        $row->addLabel('yearGroupsStaff', __('Include Staff?'));
				$row->addYesNo('yearGroupsStaff')->selected('Y');

			$row = $form->addRow()->addClass('yearGroup');
		        $row->addLabel('yearGroupsStudents', __('Include Students?'));
				$row->addYesNo('yearGroupsStudents')->selected('Y');

			if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_yearGroups_parents")) {
				$row = $form->addRow()->addClass('yearGroup');
			        $row->addLabel('yearGroupsParents', __('Include Parents?'));
					$row->addYesNo('yearGroupsParents')->selected('N');
			}
		}

		//Roll group
		if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_rollGroups_my") OR isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_rollGroups_any")) {
			$row = $form->addRow();
				$row->addLabel('rollGroup', __('Roll Group'))->description(__('Tutees and tutors.'));
				$row->addRadio('rollGroup')
					->fromArray(array('Y' => __('Yes'), 'N' => __('No')))->checked('N')->inline()->isRequired();

			$form->toggleVisibilityByClass('rollGroup')->onRadio('rollGroup')->when('Y');

			if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_rollGroups_any")) {
				$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]);
				$sql="SELECT gibbonRollGroupID AS value, name FROM gibbonRollGroup WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY name" ;
			}
			else {
				if ($roleCategory == "Staff") {
					$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonPersonID1"=>$_SESSION[$guid]["gibbonPersonID"], "gibbonPersonID2"=>$_SESSION[$guid]["gibbonPersonID"], "gibbonPersonID3"=>$_SESSION[$guid]["gibbonPersonID"], "gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]);
					$sql="SELECT gibbonRollGroupID AS value, name FROM gibbonRollGroup WHERE (gibbonPersonIDTutor=:gibbonPersonID1 OR gibbonPersonIDTutor2=:gibbonPersonID2 OR gibbonPersonIDTutor3=:gibbonPersonID3) AND gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY name" ;
				}
				else if ($roleCategory == "Student") {
					$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"], );
					$sql="SELECT gibbonRollGroupID AS value, name FROM gibbonRollGroup JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE gibbonPersonID=:gibbonPersonID AND gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY name" ;
				}
			}
			$row = $form->addRow()->addClass('rollGroup');
				$row->addLabel('rollGroups[]', __('Select Roll Groups'));
				$row->addSelect('rollGroups[]')->fromQuery($pdo, $sql, $data)->selectMultiple()->setSize(6)->isRequired()->placeholder();

			$row = $form->addRow()->addClass('rollGroup');
		        $row->addLabel('rollGroupsStaff', __('Include Staff?'));
				$row->addYesNo('rollGroupsStaff')->selected('Y');

			$row = $form->addRow()->addClass('rollGroup');
		        $row->addLabel('rollGroupsStudents', __('Include Students?'));
				$row->addYesNo('rollGroupsStudents')->selected('Y');

			if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_rollGroups_parents")) {
				$row = $form->addRow()->addClass('rollGroup');
			        $row->addLabel('rollGroupsParents', __('Include Parents?'));
					$row->addYesNo('rollGroupsParents')->selected('N');
			}
		}




		$row = $form->addRow();
			$row->addFooter();
			$row->addSubmit();

		$form->loadAllValuesFrom($values);

		echo $form->getOutput();


		?>
		<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/messenger_postProcess.php?address=" . $_GET["q"] ?>" enctype="multipart/form-data">
			<table class='smallIntBorder' cellspacing='0' style="width: 100%">
				<?php
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
							<b><?php print __('Course') ?></b><br/>
							<span style="font-size: 90%"><i><?php print __('Members of a course of study.') ?><br/></i></span>
						</td>
						<td class="right">
							<input type="radio" name="course" class="course" value="Y"/> <?php print __('Yes') ?>
							<input checked type="radio" name="course" class="course" value="N"/> <?php print __('No') ?>
						</td>
					</tr>
					<tr id="courseRow">
						<td class='hiddenReveal'>
							<b><?php print __('Select Courses') ?></b><br/>
							<span style="font-size: 90%"><i><?php print __('Use Control, Command and/or Shift to select multiple.') ?></i></span>
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
										$sqlSelect="SELECT gibbonCourse.* FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) WHERE gibbonPersonID=:gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID AND NOT role LIKE '%- Left' GROUP BY gibbonCourse.gibbonCourseID ORDER BY name" ;
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
							<b><?php print __('Include staff?') ?></b><br/>
						</td>
						<td class="hiddenReveal right">
							<select name="coursesStaff" id="coursesStaff" style="width: 302px">
								<?php
								print "<option value='Y'>" . __('Yes') . "</option>" ;
								print "<option value='N'>" . __('No') . "</option>" ;
								?>
							</select>
						</td>
					</tr>
					<tr id="courseRow4">
						<td class='hiddenReveal'>
							<b><?php print __('Include students?') ?></b><br/>
						</td>
						<td class="hiddenReveal right">
							<select name="coursesStudents" id="coursesStudents" style="width: 302px">
								<?php
								print "<option value='Y'>" . __('Yes') . "</option>" ;
								print "<option value='N'>" . __('No') . "</option>" ;
								?>
							</select>
						</td>
					</tr>
					<?php
					if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_courses_parents")) {
						?>
						<tr id="courseRow2">
							<td class='hiddenReveal'>
								<b><?php print __('Include parents?') ?></b><br/>
							</td>
							<td class="hiddenReveal right">
								<select name="coursesParents" id="coursesParents" style="width: 302px">
									<?php
									print "<option value='Y'>" . __('Yes') . "</option>" ;
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
							<b><?php print __('Class') ?></b><br/>
							<span style="font-size: 90%"><i><?php print __('Members of a class within a course.') ?><br/></i></span>
						</td>
						<td class="right">
							<input type="radio" name="class" class="class" value="Y"/> <?php print __('Yes') ?>
							<input checked type="radio" name="class" class="class" value="N"/> <?php print __('No') ?>
						</td>
					</tr>
					<tr id="classRow">
						<td class='hiddenReveal'>
							<b><?php print __('Select Classes') ?></b><br/>
							<span style="font-size: 90%"><i><?php print __('Use Control, Command and/or Shift to select multiple.') ?></i></span>
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
							<b><?php print __('Include staff?') ?></b><br/>
						</td>
						<td class="hiddenReveal right">
							<select name="classesStaff" id="classesStaff" style="width: 302px">
								<?php
								print "<option value='Y'>" . __('Yes') . "</option>" ;
								print "<option value='N'>" . __('No') . "</option>" ;
								?>
							</select>
						</td>
					</tr>
					<tr id="classRow4">
						<td class='hiddenReveal'>
							<b><?php print __('Include students?') ?></b><br/>
						</td>
						<td class="hiddenReveal right">
							<select name="classesStudents" id="classesStudents" style="width: 302px">
								<?php
								print "<option value='Y'>" . __('Yes') . "</option>" ;
								print "<option value='N'>" . __('No') . "</option>" ;
								?>
							</select>
						</td>
					</tr>
					<?php
					if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_classes_parents")) {
						?>
						<tr id="classRow2">
							<td class='hiddenReveal'>
								<b><?php print __('Include parents?') ?></b><br/>
							</td>
							<td class="hiddenReveal right">
								<select name="classesParents" id="classesParents" style="width: 302px">
									<?php
									print "<option value='Y'>" . __('Yes') . "</option>" ;
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
							<b><?php print __('Activity') ?></b><br/>
							<span style="font-size: 90%"><i><?php print __('Members of an activity.') ?><br/></i></span>
						</td>
						<td class="right">
							<input type="radio" name="activity" class="activity" value="Y"/> <?php print __('Yes') ?>
							<input checked type="radio" name="activity" class="activity" value="N"/> <?php print __('No') ?>
						</td>
					</tr>
					<tr id="activitiesRow">
						<td class='hiddenReveal'>
							<b><?php print __('Select Activities') ?></b><br/>
							<span style="font-size: 90%"><i><?php print __('Use Control, Command and/or Shift to select multiple.') ?></i></span>
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
										if ($roleCategory == "Staff") {
											$dataSelect=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]);
											$sqlSelect="SELECT * FROM gibbonActivity JOIN gibbonActivityStaff ON (gibbonActivityStaff.gibbonActivityID=gibbonActivity.gibbonActivityID) WHERE gibbonPersonID=:gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID AND active='Y' ORDER BY name" ;
										}
										if ($roleCategory == "Student") {
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
							<b><?php print __('Include staff?') ?></b><br/>
						</td>
						<td class="hiddenReveal right">
							<select name="activitiesStaff" id="activitiesStaff" style="width: 302px">
								<?php
								print "<option value='Y'>" . __('Yes') . "</option>" ;
								print "<option value='N'>" . __('No') . "</option>" ;
								?>
							</select>
						</td>
					</tr>
					<tr id="activitiesRow4">
						<td class='hiddenReveal'>
							<b><?php print __('Include students?') ?></b><br/>
						</td>
						<td class="hiddenReveal right">
							<select name="activitiesStudents" id="activitiesStudents" style="width: 302px">
								<?php
								print "<option value='Y'>" . __('Yes') . "</option>" ;
								print "<option value='N'>" . __('No') . "</option>" ;
								?>
							</select>
						</td>
					</tr>
					<?php
					if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_activities_parents")) {
						?>
						<tr id="activitiesRow2">
							<td class='hiddenReveal'>
								<b><?php print __('Include parents?') ?></b><br/>
							</td>
							<td class="hiddenReveal right">
								<select name="activitiesParents" id="activitiesParents" style="width: 302px">
									<?php
									print "<option value='Y'>" . __('Yes') . "</option>" ;
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
							<b><?php print __('Applicants') ?></b><br/>
							<span style="font-size: 90%"><i><?php print __('Applicants from a given year.') . "<br/>" . __('Does not apply to the message wall.') ?></i></span>
						</td>
						<td class="right">
							<input type="radio" name="applicants" class="applicants" value="Y"/> <?php print __('Yes') ?>
							<input checked type="radio" name="applicants" class="applicants" value="N"/> <?php print __('No') ?>
						</td>
					</tr>
					<tr id="applicantsRow">
						<td class='hiddenReveal'>
							<b><?php print __('Select Years') ?></b><br/>
							<span style="font-size: 90%"><i><?php print __('Use Control, Command and/or Shift to select multiple.') ?></i></span>
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
							<b><?php print __('Houses') ?></b><br/>
							<span style="font-size: 90%"><i><?php print __('Houses for competitions, etc.') ?><br/></i></span>
						</td>
						<td class="right">
							<input type="radio" name="houses" class="houses" value="Y"/> <?php print __('Yes') ?>
							<input checked type="radio" name="houses" class="houses" value="N"/> <?php print __('No') ?>
						</td>
					</tr>
					<tr id="housesRow">
						<td class='hiddenReveal'>
							<b><?php print __('Select Houses') ?></b><br/>
							<span style="font-size: 90%"><i><?php print __('Use Control, Command and/or Shift to select multiple.') ?></i></span>
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
							<b><?php print __('Transport') ?></b><br/>
							<span style="font-size: 90%"><i><?php print __('Applies to all staff and students who have transport set.') ?><br/></i></span>
						</td>
						<td class="right">
							<input type="radio" name="transport" class="transport" value="Y"/> <?php print __('Yes') ?>
							<input checked type="radio" name="transport" class="transport" value="N"/> <?php print __('No') ?>
						</td>
					</tr>
					<tr id="transportRow">
						<td class='hiddenReveal'>
							<b><?php print __('Select Transport') ?></b><br/>
							<span style="font-size: 90%"><i><?php print __('Use Control, Command and/or Shift to select multiple.') ?></i></span>
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
									print "<option value='" . htmlPrep($rowSelect["transport"]) . "'>" . htmlPrep(__($rowSelect["transport"])) . "</option>" ;
								}
								?>
							</select>
						</td>
					</tr>
					<tr id="transportRow3">
						<td class='hiddenReveal'>
							<b><?php print __('Include staff?') ?></b><br/>
						</td>
						<td class="hiddenReveal right">
							<select name="transportStaff" id="transportStaff" style="width: 302px">
								<?php
								print "<option value='Y'>" . __('Yes') . "</option>" ;
								print "<option value='N'>" . __('No') . "</option>" ;
								?>
							</select>
						</td>
					</tr>
					<tr id="transportRow4">
						<td class='hiddenReveal'>
							<b><?php print __('Include students?') ?></b><br/>
						</td>
						<td class="hiddenReveal right">
							<select name="transportStudents" id="transportStudents" style="width: 302px">
								<?php
								print "<option value='Y'>" . __('Yes') . "</option>" ;
								print "<option value='N'>" . __('No') . "</option>" ;
								?>
							</select>
						</td>
					</tr>
					<?php
					if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_transport_parents")) {
						?>
						<tr id="transportRow2">
							<td class='hiddenReveal'>
								<b><?php print __('Include parents?') ?></b><br/>
							</td>
							<td class="hiddenReveal right">
								<select name="transportParents" id="transportParents" style="width: 302px">
									<?php
									print "<option value='Y'>" . __('Yes') . "</option>" ;
									print "<option selected value='N'>No</option>" ;
									?>
								</select>
							</td>
						</tr>
						<?php
					}
				}

				// Absentees
				if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_attendance")) {
					?>
					<script type="text/javascript">
						/* Absent Control */
						$(document).ready(function(){
							$(".attendanceRow").css("display","none");
							$(".attendance").click(function(){
								if ($('input[name=attendance]:checked').val()=="Y" ) {
									$(".attendanceRow").slideDown("fast", $(".attendanceRow").css("display","table-row"));
								} else {
									$(".attendanceRow").css("display","none");
								}
							 });
						});
					</script>
					<tr>
						<td>
							<b><?php print __('Attendance Status') ?></b><br/>
							<span style="font-size: 90%"><i><?php print __('Students matching the given attendance status.') ?><br/></i></span>
						</td>
						<td class="right">
							<input type="radio" name="attendance" class="attendance" value="Y"/> <?php print __('Yes') ?>
							<input checked type="radio" name="attendance" class="attendance" value="N"/> <?php print __('No') ?>
						</td>
					</tr>
					<tr class="attendanceRow">
						<td class='hiddenReveal'>
							<b><?php print __('Select Attendance Status') ?></b><br/>
							<span style="font-size: 90%"><i><?php print __('Use Control, Command and/or Shift to select multiple.') ?></i></span>
						</td>
						<td class="hiddenReveal right">
							<select name="attendanceStatus[]" id="attendanceStatus[]" multiple style="width: 302px; height: 100px">
								<?php
								// Get all possible attendance statuses
								try {
									$dataSelect=array();
									$sqlSelect="SELECT name, gibbonRoleIDAll FROM gibbonAttendanceCode WHERE active = 'Y' ORDER BY direction DESC, sequenceNumber ASC, name" ;
									$resultSelect=$connection2->prepare($sqlSelect);
									$resultSelect->execute($dataSelect);
								}
								catch(PDOException $e) {}


								if ($resultSelect->rowCount()>0) {
										// Extract status strings
										while ($rowSelect = $resultSelect->fetch()) {

											// Check if a role is restricted - blank for unrestricted use
							                if ( !empty($rowSelect['gibbonRoleIDAll']) ) {
							                    $allowAttendanceType = false;
							                    $rolesAllowed = explode(',', $rowSelect['gibbonRoleIDAll']);

							                    foreach ($rolesAllowed as $role) {
							                        if ( $role == $_SESSION[$guid]['gibbonRoleIDCurrent'] ) {
							                            $allowAttendanceType = true;
							                        }
							                    }
							                    if ($allowAttendanceType == false) continue; // Skip this type, continue the loop
							                }

											print "<option value='" . $rowSelect['name'] . "'" . ($rowSelect['name'] === 'Absent' ? ' selected' : '') . ">" . htmlPrep(__($rowSelect['name'])) . "</option>";
										}
								}
								?>
							</select>
						</td>
					</tr>
					<tr class="attendanceRow">
						<td class='hiddenReveal'>
							<b><?php print __('Date') ?> *</b><br/>
						</td>
						<td class='hiddenReveal right'>
							<input name="attendanceDate" id="attendanceDate" maxlength=10 value="" type="text" class='standardWidth'/>
							<script type="text/javascript">
								var attendanceDate=new LiveValidation('date1');
								attendanceDate.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]["i18n"]["dateFormatRegEx"]=="") {  print "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i" ; } else { print $_SESSION[$guid]["i18n"]["dateFormatRegEx"] ; } ?>, failureMessage: "Use <?php if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?>." } );
							</script>
							<script type="text/javascript">
								$(function() {
									$("#attendanceDate").datepicker();
									$("#attendanceDate").datepicker('setDate', new Date());
								});
							</script>
						</td>
					</tr>
					<tr class="attendanceRow">
						<td class='hiddenReveal'>
							<b><?php print __('Include students?') ?></b><br/>
						</td>
						<td class="hiddenReveal right">
							<select name="attendanceStudents" id="attendanceStudents" style="width: 302px">
								<?php
								print "<option value='Y'>" . __('Yes') . "</option>" ;
								print "<option value='N' selected>" . __('No') . "</option>" ;
								?>
							</select>
						</td>
					</tr>
					<tr class="attendanceRow">
						<td class='hiddenReveal'>
							<b><?php print __('Include parents?') ?></b><br/>
						</td>
						<td class="hiddenReveal right">
							<select name="attendanceParents" id="attendanceParents" style="width: 302px">
								<?php
								print "<option value='Y' selected>" . __('Yes') . "</option>" ;
								print "<option value='N'>" . __('No') . "</option>" ;
								?>
							</select>
						</td>
					</tr>
				<?php
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
							<b><?php print __('Individuals') ?></b><br/>
							<span style="font-size: 90%"><i><?php print __('Individuals from the whole school.') ?><br/></i></span>
						</td>
						<td class="right">
							<input type="radio" name="individuals" class="individuals" value="Y"/> <?php print __('Yes') ?>
							<input checked type="radio" name="individuals" class="individuals" value="N"/> <?php print __('No') ?>
						</td>
					</tr>
					<tr id="individualsRow">
						<td class='hiddenReveal'>
							<b><?php print __('Select Individuals') ?></b><br/>
							<span style="font-size: 90%"><i><?php print __('Use Control, Command and/or Shift to select multiple.') ?></i></span>
						</td>
						<td class="hiddenReveal right">
							<select name="individualList[]" id="individualList[]" multiple style="width: 302px; height: 100px">
								<?php
								try {
									$dataSelect=array();
									$sqlSelect="SELECT gibbonPersonID, preferredName, surname, username FROM gibbonPerson WHERE status='Full' ORDER BY surname, preferredName" ;
									$resultSelect=$connection2->prepare($sqlSelect);
									$resultSelect->execute($dataSelect);
								}
								catch(PDOException $e) { }
								while ($rowSelect=$resultSelect->fetch()) {
									print "<option value='" . $rowSelect["gibbonPersonID"] . "'>" . formatName("", $rowSelect["preferredName"], $rowSelect["surname"], "Student", true) . " (".$rowSelect['username'].")</option>" ;
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
						<span style="font-size: 90%"><i>* <?php print __("denotes a required field") ; ?></i></span>
					</td>
					<td class="right">
						<input type="submit" value="<?php print __("Submit") ; ?>">
					</td>
				</tr>
			</table>
		</form>
		<?php
	}
}
?>
