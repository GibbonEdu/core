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
				$row->addYesNoRadio('email')->checked('Y')->isRequired();

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
				$row->addYesNoRadio('messageWall')->checked('N')->isRequired();

			$form->toggleVisibilityByClass('messageWall')->onRadio('messageWall')->when('Y');

			$row = $form->addRow()->addClass('messageWall');
		        $row->addLabel('date1', __('Publication Dates'))->description(__('Select up to three individual dates.'));
				$col = $row->addColumn('date1')->addClass('stacked');
				$col->addDate('date1')->setValue(dateConvertBack($guid, date('Y-m-d')))->isRequired();
				$col->addDate('date2');
				$col->addDate('date3');
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
					$row->addYesNoRadio('sms')->checked('N')->isRequired();

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
			$row->addTextField('subject')->maxLength(60)->isRequired();

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
				$row->addYesNoRadio('emailReceipt')->checked('N')->isRequired();

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
				$row->addYesNoRadio('role')->checked('N')->isRequired();

			$form->toggleVisibilityByClass('role')->onRadio('role')->when('Y');

			$data = array();
			$sql = 'SELECT gibbonRoleID AS value, CONCAT(name," (",category,")") AS name FROM gibbonRole ORDER BY name';
			$row = $form->addRow()->addClass('role hiddenReveal');
		        $row->addLabel('roles[]', __('Select Roles'));
		        $row->addSelect('roles[]')->fromQuery($pdo, $sql, $data)->selectMultiple()->setSize(6)->isRequired()->placeholder();

			//Role Category
			$row = $form->addRow();
				$row->addLabel('roleCategory', __('Role Category'))->description(__('Users of a certain type.'));
				$row->addYesNoRadio('roleCategory')->checked('N')->isRequired();

			$form->toggleVisibilityByClass('roleCategory')->onRadio('roleCategory')->when('Y');

			$data = array();
			$sql = 'SELECT DISTINCT category AS value, category AS name FROM gibbonRole ORDER BY category';
			$row = $form->addRow()->addClass('roleCategory hiddenReveal');
		        $row->addLabel('roleCategories[]', __('Select Role Categories'));
		        $row->addSelect('roleCategories[]')->fromQuery($pdo, $sql, $data)->selectMultiple()->setSize(4)->isRequired()->placeholder();
		}

		//Year group
		if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_yearGroups_any")) {
			$row = $form->addRow();
				$row->addLabel('yearGroup', __('Year Group'))->description(__('Students in year; all staff.'));
				$row->addYesNoRadio('yearGroup')->checked('N')->isRequired();

			$form->toggleVisibilityByClass('yearGroup')->onRadio('yearGroup')->when('Y');

			$data = array();
			$sql = 'SELECT gibbonYearGroupID AS value, name FROM gibbonYearGroup ORDER BY sequenceNumber';
			$row = $form->addRow()->addClass('yearGroup hiddenReveal');
				$row->addLabel('yearGroups[]', __('Select Year Groups'));
				$row->addSelect('yearGroups[]')->fromQuery($pdo, $sql, $data)->selectMultiple()->setSize(6)->isRequired()->placeholder();

			$row = $form->addRow()->addClass('yearGroup hiddenReveal');
		        $row->addLabel('yearGroupsStaff', __('Include Staff?'));
				$row->addYesNo('yearGroupsStaff')->selected('Y');

			$row = $form->addRow()->addClass('yearGroup hiddenReveal');
		        $row->addLabel('yearGroupsStudents', __('Include Students?'));
				$row->addYesNo('yearGroupsStudents')->selected('Y');

			if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_yearGroups_parents")) {
				$row = $form->addRow()->addClass('yearGroup hiddenReveal');
			        $row->addLabel('yearGroupsParents', __('Include Parents?'));
					$row->addYesNo('yearGroupsParents')->selected('N');
			}
		}

		//Roll group
		if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_rollGroups_my") OR isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_rollGroups_any")) {
			$row = $form->addRow();
				$row->addLabel('rollGroup', __('Roll Group'))->description(__('Tutees and tutors.'));
				$row->addYesNoRadio('rollGroup')->checked('N')->isRequired();

			$form->toggleVisibilityByClass('rollGroup')->onRadio('rollGroup')->when('Y');

			if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_rollGroups_any")) {
				$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]);
				$sql="SELECT gibbonRollGroup.gibbonRollGroupID AS value, gibbonRollGroup.name FROM gibbonRollGroup WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY name" ;
			}
			else {
				if ($roleCategory == "Staff") {
					$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonPersonID1"=>$_SESSION[$guid]["gibbonPersonID"], "gibbonPersonID2"=>$_SESSION[$guid]["gibbonPersonID"], "gibbonPersonID3"=>$_SESSION[$guid]["gibbonPersonID"], "gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]);
					$sql="SELECT gibbonRollGroup.gibbonRollGroupID AS value, gibbonRollGroup.name FROM gibbonRollGroup WHERE (gibbonPersonIDTutor=:gibbonPersonID1 OR gibbonPersonIDTutor2=:gibbonPersonID2 OR gibbonPersonIDTutor3=:gibbonPersonID3) AND gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY name" ;
				}
				else if ($roleCategory == "Student") {
					$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"], );
					$sql="SELECT gibbonRollGroup.gibbonRollGroupID AS value, gibbonRollGroup.name FROM gibbonRollGroup JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE gibbonPersonID=:gibbonPersonID AND gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY name" ;
				}
			}
			$row = $form->addRow()->addClass('rollGroup hiddenReveal');
				$row->addLabel('rollGroups[]', __('Select Roll Groups'));
				$row->addSelect('rollGroups[]')->fromQuery($pdo, $sql, $data)->selectMultiple()->setSize(6)->isRequired()->placeholder();

			$row = $form->addRow()->addClass('rollGroup hiddenReveal');
		        $row->addLabel('rollGroupsStaff', __('Include Staff?'));
				$row->addYesNo('rollGroupsStaff')->selected('Y');

			$row = $form->addRow()->addClass('rollGroup hiddenReveal');
		        $row->addLabel('rollGroupsStudents', __('Include Students?'));
				$row->addYesNo('rollGroupsStudents')->selected('Y');

			if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_rollGroups_parents")) {
				$row = $form->addRow()->addClass('rollGroup hiddenReveal');
			        $row->addLabel('rollGroupsParents', __('Include Parents?'));
					$row->addYesNo('rollGroupsParents')->selected('N');
			}
        }

        // Course
        if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_courses_my") OR isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_courses_any")) {
            $row = $form->addRow();
				$row->addLabel('course', __('Course'))->description(__('Members of a course of study.'));
				$row->addYesNoRadio('course')->checked('N')->isRequired();

			$form->toggleVisibilityByClass('course')->onRadio('course')->when('Y');

            if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_courses_any")) {
                $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                $sql = "SELECT gibbonCourse.gibbonCourseID as value, gibbonCourse.nameShort as name FROM gibbonCourse WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY name";
            } else {
                $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                $sql = "SELECT gibbonCourse.gibbonCourseID as value, gibbonCourse.nameShort as name FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) WHERE gibbonPersonID=:gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID AND NOT role LIKE '%- Left' GROUP BY gibbonCourse.gibbonCourseID ORDER BY name";
            }

			$row = $form->addRow()->addClass('course hiddenReveal');
				$row->addLabel('courses[]', __('Select Courses'));
				$row->addSelect('courses[]')->fromQuery($pdo, $sql, $data)->selectMultiple()->setSize(6)->isRequired();

			$row = $form->addRow()->addClass('course hiddenReveal');
		        $row->addLabel('coursesStaff', __('Include Staff?'));
				$row->addYesNo('coursesStaff')->selected('Y');

			$row = $form->addRow()->addClass('course hiddenReveal');
		        $row->addLabel('coursesStudents', __('Include Students?'));
				$row->addYesNo('coursesStudents')->selected('Y');

			if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_courses_parents")) {
				$row = $form->addRow()->addClass('course hiddenReveal');
			        $row->addLabel('coursesParents', __('Include Parents?'));
					$row->addYesNo('coursesParents')->selected('N');
			}
        }

        // Class
        if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_classes_my") OR isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_classes_any")) {
            $row = $form->addRow();
				$row->addLabel('class', __('Class'))->description(__('Members of a class within a course.'));
				$row->addYesNoRadio('class')->checked('N')->isRequired();

			$form->toggleVisibilityByClass('class')->onRadio('class')->when('Y');

            if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_classes_any")) {
                $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                $sql = "SELECT gibbonCourseClass.gibbonCourseClassID as value, CONCAT(gibbonCourse.nameShort, '.', gibbonCourseClass.nameShort) as name FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY name";
            } else {
                $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                $sql = "SELECT gibbonCourseClass.gibbonCourseClassID as value, CONCAT(gibbonCourse.nameShort, '.', gibbonCourseClass.nameShort) as name FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) WHERE gibbonPersonID=:gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID AND NOT role LIKE '%- Left' ORDER BY name";
            }

			$row = $form->addRow()->addClass('class hiddenReveal');
				$row->addLabel('classes[]', __('Select Classes'));
				$row->addSelect('classes[]')->fromQuery($pdo, $sql, $data)->selectMultiple()->setSize(6)->isRequired();

			$row = $form->addRow()->addClass('class hiddenReveal');
		        $row->addLabel('classesStaff', __('Include Staff?'));
				$row->addYesNo('classesStaff')->selected('Y');

			$row = $form->addRow()->addClass('class hiddenReveal');
		        $row->addLabel('classesStudents', __('Include Students?'));
				$row->addYesNo('classesStudents')->selected('Y');

			if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_classes_parents")) {
				$row = $form->addRow()->addClass('class hiddenReveal');
			        $row->addLabel('classesParents', __('Include Parents?'));
					$row->addYesNo('classesParents')->selected('N');
			}
        }


        // Activities
        if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_activities_my") OR isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_activities_any")) {
            $row = $form->addRow();
				$row->addLabel('activity', __('Activity'))->description(__('Members of an activity.'));
				$row->addYesNoRadio('activity')->checked('N')->isRequired();

			$form->toggleVisibilityByClass('activity')->onRadio('activity')->when('Y');

            if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_activities_any")) {
			    $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                $sql = "SELECT gibbonActivity.gibbonActivityID as value, name FROM gibbonActivity WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND active='Y' ORDER BY name";
            } else {
                $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                if ($roleCategory == "Staff") {
                    $sql = "SELECT gibbonActivity.gibbonActivityID as value, gibbonActivity.name FROM gibbonActivity JOIN gibbonActivityStaff ON (gibbonActivityStaff.gibbonActivityID=gibbonActivity.gibbonActivityID) WHERE gibbonPersonID=:gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID AND active='Y' ORDER BY name";
                } else if ($roleCategory == "Student") {
                    $sql = "SELECT gibbonActivity.gibbonActivityID as value, gibbonActivity.name FROM gibbonActivity JOIN gibbonActivityStudent ON (gibbonActivityStudent.gibbonActivityID=gibbonActivity.gibbonActivityID) WHERE gibbonPersonID=:gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID AND status='Accepted' AND active='Y' ORDER BY name";
                }
            }
			$row = $form->addRow()->addClass('activity hiddenReveal');
				$row->addLabel('activities[]', __('Select Activities'));
				$row->addSelect('activities[]')->fromQuery($pdo, $sql, $data)->selectMultiple()->setSize(6)->isRequired();

			$row = $form->addRow()->addClass('activity hiddenReveal');
		        $row->addLabel('activitiesStaff', __('Include Staff?'));
				$row->addYesNo('activitiesStaff')->selected('Y');

			$row = $form->addRow()->addClass('activity hiddenReveal');
		        $row->addLabel('activitiesStudents', __('Include Students?'));
				$row->addYesNo('activitiesStudents')->selected('Y');

			if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_activities_parents")) {
				$row = $form->addRow()->addClass('activity hiddenReveal');
			        $row->addLabel('activitiesParents', __('Include Parents?'));
					$row->addYesNo('activitiesParents')->selected('N');
			}
        }

        // Applicants
        if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_applicants")) {
            $row = $form->addRow();
				$row->addLabel('applicants', __('Applicants'))->description(__('Applicants from a given year.'))->description(__('Does not apply to the message wall.'));
				$row->addYesNoRadio('applicants')->checked('N')->isRequired();

			$form->toggleVisibilityByClass('applicants')->onRadio('applicants')->when('Y');

			$sql = "SELECT gibbonSchoolYearID as value, name FROM gibbonSchoolYear ORDER BY sequenceNumber DESC";
			$row = $form->addRow()->addClass('applicants hiddenReveal');
				$row->addLabel('applicantList[]', __('Select Years'));
				$row->addSelect('applicantList[]')->fromQuery($pdo, $sql)->selectMultiple()->setSize(6)->isRequired();
        }

        // Houses
        if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_houses_all") OR isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_houses_my")) {
            $row = $form->addRow();
				$row->addLabel('houses', __('Houses'))->description(__('Houses for competitions, etc.'));
				$row->addYesNoRadio('houses')->checked('N')->isRequired();

			$form->toggleVisibilityByClass('houses')->onRadio('houses')->when('Y');

            if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_houses_all")) {
                $data = array();
                $sql = "SELECT gibbonHouse.gibbonHouseID as value, gibbonHouse.name FROM gibbonHouse ORDER BY name";
            } else if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_houses_my")) {
                $dataSelect = array('gibbonPersonID'=>$_SESSION[$guid]['gibbonPersonID']);
                $sql = "SELECT gibbonHouse.gibbonHouseID as value, gibbonHouse.name FROM gibbonHouse JOIN gibbonPerson ON (gibbonHouse.gibbonHouseID=gibbonPerson.gibbonHouseID) WHERE gibbonPersonID=:gibbonPersonID ORDER BY name";
            }
			$row = $form->addRow()->addClass('houses hiddenReveal');
				$row->addLabel('houseList[]', __('Select Houses'));
				$row->addSelect('houseList[]')->fromQuery($pdo, $sql, $data)->selectMultiple()->setSize(6)->isRequired();
        }

        // Transport
        if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_transport_any")) {
            $row = $form->addRow();
				$row->addLabel('transport', __('Transport'))->description(__('Applies to all staff and students who have transport set.'));
				$row->addYesNoRadio('transport')->checked('N')->isRequired();

			$form->toggleVisibilityByClass('transport')->onRadio('transport')->when('Y');

			$sql = "SELECT DISTINCT transport as value, transport as name FROM gibbonPerson WHERE status='Full' AND NOT transport='' ORDER BY transport";
			$row = $form->addRow()->addClass('transport hiddenReveal');
				$row->addLabel('transports[]', __('Select Transport'));
				$row->addSelect('transports[]')->fromQuery($pdo, $sql)->selectMultiple()->setSize(6)->isRequired();

			$row = $form->addRow()->addClass('transport hiddenReveal');
		        $row->addLabel('transportStaff', __('Include Staff?'));
				$row->addYesNo('transportStaff')->selected('Y');

			$row = $form->addRow()->addClass('transport hiddenReveal');
		        $row->addLabel('transportStudents', __('Include Students?'));
				$row->addYesNo('transportStudents')->selected('Y');

			if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_transport_parents")) {
				$row = $form->addRow()->addClass('transport hiddenReveal');
			        $row->addLabel('transportParents', __('Include Parents?'));
					$row->addYesNo('transportParents')->selected('N');
			}
        }

        // Attendance Status / Absentees
        if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_attendance")) {
            $row = $form->addRow();
				$row->addLabel('attendance', __('Attendance Status'))->description(__('Students matching the given attendance status.'));
				$row->addYesNoRadio('attendance')->checked('N')->isRequired();

			$form->toggleVisibilityByClass('attendance')->onRadio('attendance')->when('Y');

            $sql = "SELECT name, gibbonRoleIDAll FROM gibbonAttendanceCode WHERE active = 'Y' ORDER BY direction DESC, sequenceNumber ASC, name";
            $result = $pdo->executeQuery(array(), $sql);

            // Filter the attendance codes by allowed roles (if any)
            $currentRole = $_SESSION[$guid]['gibbonRoleIDCurrent'];
            $attendanceCodes = ($result->rowCount() > 0)? $result->fetchAll() : array();
            $attendanceCodes = array_filter($attendanceCodes, function($item) use ($currentRole) {
                if (!empty($item['gibbonRoleIDAll'])) {
                    $rolesAllowed = array_map('trim', explode(',', $item['gibbonRoleIDAll']));
                    return in_array($currentRole, $rolesAllowed);
                } else {
                    return true;
                }
            });
            $attendanceCodes = array_column($attendanceCodes, 'name');

			$row = $form->addRow()->addClass('attendance hiddenReveal');
				$row->addLabel('attendanceStatus[]', __('Select Attendance Status'));
                $row->addSelect('attendanceStatus[]')->fromArray($attendanceCodes)->selectMultiple()->setSize(6)->isRequired()->selected('Absent');

            $row = $form->addRow()->addClass('attendance hiddenReveal');
                $row->addLabel('attendanceDate', __('Date'));
                $row->addDate('attendanceDate')->isRequired()->setValue(date($_SESSION[$guid]['i18n']['dateFormatPHP']));

			$row = $form->addRow()->addClass('attendance hiddenReveal');
		        $row->addLabel('attendanceStudents', __('Include Students?'));
				$row->addYesNo('attendanceStudents')->selected('N');

			$row = $form->addRow()->addClass('attendance hiddenReveal');
                $row->addLabel('attendanceParents', __('Include Parents?'));
                $row->addYesNo('attendanceParents')->selected('Y');
        }

        // Individuals
        if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_individuals")) {
            $row = $form->addRow();
				$row->addLabel('individuals', __('Individuals'))->description(__('Individuals from the whole school.'));
				$row->addYesNoRadio('individuals')->checked('N')->isRequired();

			$form->toggleVisibilityByClass('individuals')->onRadio('individuals')->when('Y');

            $sql = "SELECT gibbonRole.category, gibbonPersonID, preferredName, surname, username FROM gibbonPerson JOIN gibbonRole ON (gibbonRole.gibbonRoleID=gibbonPerson.gibbonRoleIDPrimary) WHERE status='Full' ORDER BY surname, preferredName";
            $result = $pdo->executeQuery(array(), $sql);

            // Build a set of individuals by ID => formatted name
            $individuals = ($result->rowCount() > 0)? $result->fetchAll() : array();
            $individuals = array_reduce($individuals, function($group, $item){
                $group[$item['gibbonPersonID']] = formatName("", $item['preferredName'], $item['surname'], 'Student', true) . ' ('.$item['username'].', '.__($item['category']).')';
                return $group;
            }, array());

			$row = $form->addRow()->addClass('individuals hiddenReveal');
				$row->addLabel('individualList[]', __('Select Individuals'));
				$row->addSelect('individualList[]')->fromArray($individuals)->selectMultiple()->setSize(6)->isRequired();
        }

		$row = $form->addRow();
			$row->addFooter();
			$row->addSubmit();

		echo $form->getOutput();
	}
}
