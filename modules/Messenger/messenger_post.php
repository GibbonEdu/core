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
use Gibbon\Contracts\Comms\SMS;
use Gibbon\Services\Format;
use Gibbon\Domain\User\RoleGateway;

require_once __DIR__ . '/moduleFunctions.php';

$page->breadcrumbs->add(__('New Message'));

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
                if (!empty($_GET['notification']) && $_GET['notification'] == 'Y') {
                    $addReturnMessage = __("Your message has been dispatched to a team of highly trained gibbons for delivery: not all messages may arrive at their destination, but an attempt has been made to get them all out. You'll receive a notification once all messages have been sent.");
                } else {
                    $addReturnMessage = __('Your message has been posted successfully.');
                }

				$class="success" ;
			}
			print "<div class='$class'>" ;
				print $addReturnMessage;
			print "</div>" ;
		}

		$form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/messenger_postPreProcess.php');
		$form->addHiddenValue('address', $_SESSION[$guid]['address']);

		//DELIVERY MODE
        $form->addRow()->addHeading(__('Delivery Mode'));

        $deliverByEmail = isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_byEmail");
        $deliverByWall = isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_byMessageWall");
        $deliverBySMS = isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_bySMS");

        if (!$deliverByEmail && !$deliverByWall && !$deliverBySMS) {
            $page->addError(__('You do not have access to this action.'));
            return;
        }

        $page->addWarning(sprintf(__('Each family in Gibbon must have one parent who is contact priority 1, and who must be enabled to receive email and SMS messages from %1$s. As a result, when targetting parents, you can be fairly certain that messages should get through to each family.'), $_SESSION[$guid]["organisationNameShort"]));

		//Delivery by email
		if ($deliverByEmail) {
			$row = $form->addRow();
				$row->addLabel('email', __('Email'))->description(__('Deliver this message to user\'s primary email account?'));
				$row->addYesNoRadio('email')->checked('Y')->required();

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
				$row->addSelect('from')->fromArray($from)->required();

			if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_fromSchool")) {
				$row = $form->addRow()->addClass('email');
					$row->addLabel('emailReplyTo', __('Reply To'));
					$row->addEmail('emailReplyTo');
			}
		}

		//Delivery by message wall
		if ($deliverByWall) {
			$row = $form->addRow();
				$row->addLabel('messageWall', __('Message Wall'))->description(__('Place this message on user\'s message wall?'));
				$row->addYesNoRadio('messageWall')->checked('N')->required();

			$form->toggleVisibilityByClass('messageWall')->onRadio('messageWall')->when('Y');

			if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_manage.php", "Manage Messages_all")) {
				$row = $form->addRow()->addClass('messageWall');
					$row->addLabel('messageWallPin', __('Pin To Top?'));
					$row->addYesNo('messageWallPin')->selected('N')->required();
			}

			$row = $form->addRow()->addClass('messageWall');
		        $row->addLabel('date1', __('Publication Dates'))->description(__('Select up to three individual dates.'));
				$col = $row->addColumn('date1')->addClass('stacked');
				$col->addDate('date1')->setValue(dateConvertBack($guid, date('Y-m-d')))->required();
				$col->addDate('date2');
				$col->addDate('date3');
		}

        //Delivery by SMS
		if ($deliverBySMS) {
            $smsGateway = getSettingByScope($connection2, 'Messenger', 'smsGateway');
			$smsUsername = getSettingByScope($connection2, 'Messenger', 'smsUsername');

			if (empty($smsGateway) || empty($smsUsername)) {
				$row = $form->addRow()->addClass('sms');
					$row->addLabel('sms', __('SMS'))->description(__('Deliver this message to user\'s mobile phone?'));
					$row->addAlert(sprintf(__('SMS NOT CONFIGURED. Please contact %1$s for help.'), "<a href='mailto:" . $_SESSION[$guid]["organisationAdministratorEmail"] . "'>" . $_SESSION[$guid]["organisationAdministratorName"] . "</a>"), 'message');
			}
			else {
				$row = $form->addRow();
					$row->addLabel('sms', __('SMS'))->description(__('Deliver this message to user\'s mobile phone?'));
					$row->addYesNoRadio('sms')->checked('N')->required();

				$form->toggleVisibilityByClass('sms')->onRadio('sms')->when('Y');

				$smsAlert = __('SMS messages are sent to local and overseas numbers, but not all countries are supported. Please see the SMS Gateway provider\'s documentation or error log to see which countries are not supported. The subject does not get sent, and all HTML tags are removed. Each message, to each recipient, will incur a charge (dependent on your SMS gateway provider). Messages over 140 characters will get broken into smaller messages, and will cost more.');

                $sms = $container->get(SMS::class);

                if ($smsCredits = $sms->getCreditBalance()) {
                    $smsAlert .= "<br/><br/><b>" . sprintf(__('Current balance: %1$s credit(s).'), $smsCredits) . "</u></b>" ;
					$form->addHiddenValue('smsCreditBalance', $smsCredits);
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
			$row->addTextField('subject')->maxLength(60)->required();

		$row = $form->addRow();
	        $col = $row->addColumn('body');
	        $col->addLabel('body', __('Body'));
	        $col->addEditor('body', $guid)->required()->setRows(20)->showMedia(true)->setValue($signature);


		//READ RECEIPTS
		if (!isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_readReceipts")) {
			$form->addHiddenValue('emailReceipt', 'N');
		} else {
            $form->addRow()->addHeading(__('Customisation'));

			$row = $form->addRow();
				$row->addLabel('emailReceipt', __('Enable Read Receipts'))->description(__('Each email recipient will receive a personalised confirmation link.'));
				$row->addYesNoRadio('emailReceipt')->checked('N')->required();

			$form->toggleVisibilityByClass('emailReceipt')->onRadio('emailReceipt')->when('Y');

			$form->addRow()->addClass('emailReceipt')
				->addContent(__('With read receipts enabled, the text [confirmLink] can be included in a message to add a unique, login-free read receipt link. If [confirmLink] is not included, the link will be appended to the end of the message.'));

			$row = $form->addRow()->addClass('emailReceipt');
				$row->addLabel('emailReceiptText', __('Link Text'))->description(__('Confirmation link text to display to recipient.'));
				$row->addTextArea('emailReceiptText')->setRows(4)->required()->setValue(__('By clicking on this link I confirm that I have read, and agree to, the text contained within this email, and give consent for my child to participate.'));
		}

        $roleCategory = getRoleCategory($_SESSION[$guid]["gibbonRoleIDCurrent"], $connection2);

        //Individual naming
        if ($roleCategory == 'Staff') {
            $row = $form->addRow();
                $row->addLabel('individualNaming', __('Individual Naming'))->description(__('The names of relevant students will be prepended to messages.'));
                $row->addYesNoRadio('individualNaming')->checked('Y')->required();
        } else {
            $form->addHiddenValue('individualNaming', 'N');
        }

		//TARGETS
		$form->addRow()->addHeading(__('Targets'));

        $defaultSendStaff = ($roleCategory == 'Staff' || $roleCategory == 'Student')? 'Y' : 'N';
        $defaultSendStudents = ($roleCategory == 'Staff' || $roleCategory == 'Student')? 'Y' : 'N';
        $defaultSendParents = ($roleCategory == 'Parent')? 'Y' : 'N';

		//Role
		if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_role")) {
			$row = $form->addRow();
				$row->addLabel('role', __('Role'))->description(__('Users of a certain type.'));
				$row->addYesNoRadio('role')->checked('N')->required();

			$form->toggleVisibilityByClass('role')->onRadio('role')->when('Y');


                        $arrRoles = array();
                        $roleGateway = $container->get(RoleGateway::class);
                        $criteria =  $roleGateway->newQueryCriteria(true)
                            ->sortBy(['name']);
                        $roles = $roleGateway->queryRoles($criteria);

                        foreach ($roles AS $role) {
                            $arrRoles[$role['gibbonRoleID']] = __($role['name'])." (".__($role['category']).")";
                        }

                        $row = $form->addRow()->addClass('role hiddenReveal');
		        $row->addLabel('roles[]', __('Select Roles'));
		        $row->addSelect('roles[]')->fromArray($arrRoles)->selectMultiple()->setSize(6)->required()->placeholder();

			//Role Category
			$row = $form->addRow();
				$row->addLabel('roleCategory', __('Role Category'))->description(__('Users of a certain type.'));
				$row->addYesNoRadio('roleCategory')->checked('N')->required();

			$form->toggleVisibilityByClass('roleCategory')->onRadio('roleCategory')->when('Y');

			$data = array();
			$sql = 'SELECT DISTINCT category AS value, category AS name FROM gibbonRole ORDER BY category';
			$row = $form->addRow()->addClass('roleCategory hiddenReveal');
		        $row->addLabel('roleCategories[]', __('Select Role Categories'));
		        $row->addSelect('roleCategories[]')->fromQuery($pdo, $sql, $data)->selectMultiple()->setSize(4)->required()->placeholder();
		}

		//Year group
		if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_yearGroups_any")) {
			$row = $form->addRow();
				$row->addLabel('yearGroup', __('Year Group'))->description(__('Students in year; staff by tutors and courses taught.'));
				$row->addYesNoRadio('yearGroup')->checked('N')->required();

			$form->toggleVisibilityByClass('yearGroup')->onRadio('yearGroup')->when('Y');

			$data = array();
			$sql = 'SELECT gibbonYearGroupID AS value, name FROM gibbonYearGroup ORDER BY sequenceNumber';
			$row = $form->addRow()->addClass('yearGroup hiddenReveal');
				$row->addLabel('yearGroups[]', __('Select Year Groups'));
				$row->addSelect('yearGroups[]')->fromQuery($pdo, $sql, $data)->selectMultiple()->setSize(6)->required()->placeholder();

			$row = $form->addRow()->addClass('yearGroup hiddenReveal');
		        $row->addLabel('yearGroupsStaff', __('Include Staff?'));
				$row->addYesNo('yearGroupsStaff')->selected($defaultSendStaff);

			$row = $form->addRow()->addClass('yearGroup hiddenReveal');
		        $row->addLabel('yearGroupsStudents', __('Include Students?'));
				$row->addYesNo('yearGroupsStudents')->selected($defaultSendStudents);

			if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_yearGroups_parents")) {
				$row = $form->addRow()->addClass('yearGroup hiddenReveal');
			        $row->addLabel('yearGroupsParents', __('Include Parents?'));
					$row->addYesNo('yearGroupsParents')->selected($defaultSendParents);
			}
		}

		//Roll group
		if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_rollGroups_my") OR isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_rollGroups_any")) {
			$row = $form->addRow();
				$row->addLabel('rollGroup', __('Roll Group'))->description(__('Tutees and tutors.'));
				$row->addYesNoRadio('rollGroup')->checked('N')->required();

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
				$row->addSelect('rollGroups[]')->fromQuery($pdo, $sql, $data)->selectMultiple()->setSize(6)->required()->placeholder();

			$row = $form->addRow()->addClass('rollGroup hiddenReveal');
		        $row->addLabel('rollGroupsStaff', __('Include Staff?'));
				$row->addYesNo('rollGroupsStaff')->selected($defaultSendStaff);

			$row = $form->addRow()->addClass('rollGroup hiddenReveal');
		        $row->addLabel('rollGroupsStudents', __('Include Students?'));
				$row->addYesNo('rollGroupsStudents')->selected($defaultSendStudents);

			if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_rollGroups_parents")) {
				$row = $form->addRow()->addClass('rollGroup hiddenReveal');
			        $row->addLabel('rollGroupsParents', __('Include Parents?'));
					$row->addYesNo('rollGroupsParents')->selected($defaultSendParents);
			}
        }

        // Course
        if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_courses_my") OR isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_courses_any")) {
            $row = $form->addRow();
				$row->addLabel('course', __('Course'))->description(__('Members of a course of study.'));
				$row->addYesNoRadio('course')->checked('N')->required();

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
				$row->addSelect('courses[]')->fromQuery($pdo, $sql, $data)->selectMultiple()->setSize(6)->required();

			$row = $form->addRow()->addClass('course hiddenReveal');
		        $row->addLabel('coursesStaff', __('Include Staff?'));
				$row->addYesNo('coursesStaff')->selected($defaultSendStaff);

			$row = $form->addRow()->addClass('course hiddenReveal');
		        $row->addLabel('coursesStudents', __('Include Students?'));
				$row->addYesNo('coursesStudents')->selected($defaultSendStudents);

			if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_courses_parents")) {
				$row = $form->addRow()->addClass('course hiddenReveal');
			        $row->addLabel('coursesParents', __('Include Parents?'));
					$row->addYesNo('coursesParents')->selected($defaultSendParents);
			}
        }

        // Class
        if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_classes_my") OR isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_classes_any")) {
            $row = $form->addRow();
				$row->addLabel('class', __('Class'))->description(__('Members of a class within a course.'));
				$row->addYesNoRadio('class')->checked('N')->required();

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
				$row->addSelect('classes[]')->fromQuery($pdo, $sql, $data)->selectMultiple()->setSize(6)->required();

			$row = $form->addRow()->addClass('class hiddenReveal');
		        $row->addLabel('classesStaff', __('Include Staff?'));
				$row->addYesNo('classesStaff')->selected($defaultSendStaff);

			$row = $form->addRow()->addClass('class hiddenReveal');
		        $row->addLabel('classesStudents', __('Include Students?'));
				$row->addYesNo('classesStudents')->selected($defaultSendStudents);

			if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_classes_parents")) {
				$row = $form->addRow()->addClass('class hiddenReveal');
			        $row->addLabel('classesParents', __('Include Parents?'));
					$row->addYesNo('classesParents')->selected($defaultSendParents);
			}
        }


        // Activities
        if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_activities_my") OR isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_activities_any")) {
            $row = $form->addRow();
				$row->addLabel('activity', __('Activity'))->description(__('Members of an activity.'));
				$row->addYesNoRadio('activity')->checked('N')->required();

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
				$row->addSelect('activities[]')->fromQuery($pdo, $sql, $data)->selectMultiple()->setSize(6)->required();

			$row = $form->addRow()->addClass('activity hiddenReveal');
		        $row->addLabel('activitiesStaff', __('Include Staff?'));
				$row->addYesNo('activitiesStaff')->selected($defaultSendStaff);

			$row = $form->addRow()->addClass('activity hiddenReveal');
		        $row->addLabel('activitiesStudents', __('Include Students?'));
				$row->addYesNo('activitiesStudents')->selected($defaultSendStudents);

			if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_activities_parents")) {
				$row = $form->addRow()->addClass('activity hiddenReveal');
			        $row->addLabel('activitiesParents', __('Include Parents?'));
					$row->addYesNo('activitiesParents')->selected($defaultSendParents);
			}
        }

        // Applicants
        if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_applicants")) {
            $row = $form->addRow();
				$row->addLabel('applicants', __('Applicants'))->description(__('Accepted applicants from a given year.'))->description(__('Does not apply to the message wall.'));
				$row->addYesNoRadio('applicants')->checked('N')->required();

			$form->toggleVisibilityByClass('applicants')->onRadio('applicants')->when('Y');

			$sql = "SELECT gibbonSchoolYearID as value, name FROM gibbonSchoolYear ORDER BY sequenceNumber DESC";
			$row = $form->addRow()->addClass('applicants hiddenReveal');
				$row->addLabel('applicantList[]', __('Select Years'));
				$row->addSelect('applicantList[]')->fromQuery($pdo, $sql)->selectMultiple()->setSize(6)->required();

			$row = $form->addRow()->addClass('applicants hiddenReveal');
		        $row->addLabel('applicantsStudents', __('Include Students?'));
				$row->addYesNo('applicantsStudents')->selected($defaultSendStudents);

			$row = $form->addRow()->addClass('applicants hiddenReveal');
		        $row->addLabel('applicantsParents', __('Include Parents?'));
				$row->addYesNo('applicantsParents')->selected($defaultSendParents);
        }

        // Houses
        if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_houses_all") OR isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_houses_my")) {
            $row = $form->addRow();
				$row->addLabel('houses', __('Houses'))->description(__('Houses for competitions, etc.'));
				$row->addYesNoRadio('houses')->checked('N')->required();

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
				$row->addSelect('houseList[]')->fromQuery($pdo, $sql, $data)->selectMultiple()->setSize(6)->required();
        }

        // Transport
        if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_transport_any")) {
            $row = $form->addRow();
				$row->addLabel('transport', __('Transport'))->description(__('Applies to all staff and students who have transport set.'));
				$row->addYesNoRadio('transport')->checked('N')->required();

			$form->toggleVisibilityByClass('transport')->onRadio('transport')->when('Y');

			$sql = "SELECT DISTINCT transport as value, transport as name FROM gibbonPerson WHERE status='Full' AND NOT transport='' ORDER BY transport";
			$row = $form->addRow()->addClass('transport hiddenReveal');
				$row->addLabel('transports[]', __('Select Transport'));
				$row->addSelect('transports[]')->fromQuery($pdo, $sql)->selectMultiple()->setSize(6)->required();

			$row = $form->addRow()->addClass('transport hiddenReveal');
		        $row->addLabel('transportStaff', __('Include Staff?'));
				$row->addYesNo('transportStaff')->selected($defaultSendStaff);

			$row = $form->addRow()->addClass('transport hiddenReveal');
		        $row->addLabel('transportStudents', __('Include Students?'));
				$row->addYesNo('transportStudents')->selected($defaultSendStudents);

			if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_transport_parents")) {
				$row = $form->addRow()->addClass('transport hiddenReveal');
			        $row->addLabel('transportParents', __('Include Parents?'));
					$row->addYesNo('transportParents')->selected($defaultSendParents);
			}
        }

        // Attendance Status / Absentees
        if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_attendance")) {
            $row = $form->addRow();
				$row->addLabel('attendance', __('Attendance Status'))->description(__('Students matching the given attendance status.'));
				$row->addYesNoRadio('attendance')->checked('N')->required();

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
                $row->addSelect('attendanceStatus[]')->fromArray($attendanceCodes)->selectMultiple()->setSize(6)->required()->selected('Absent');

            $row = $form->addRow()->addClass('attendance hiddenReveal');
                $row->addLabel('attendanceDate', __('Date'));
                $row->addDate('attendanceDate')->required()->setValue(date($_SESSION[$guid]['i18n']['dateFormatPHP']));

			$row = $form->addRow()->addClass('attendance hiddenReveal');
		        $row->addLabel('attendanceStudents', __('Include Students?'));
				$row->addYesNo('attendanceStudents')->selected('N');

			$row = $form->addRow()->addClass('attendance hiddenReveal');
                $row->addLabel('attendanceParents', __('Include Parents?'));
                $row->addYesNo('attendanceParents')->selected('Y');
		}

		 // Group
		 if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_groups_my") OR isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_groups_any")) {
            $row = $form->addRow();
				$row->addLabel('group', __('Group'))->description(__('Members of a Messenger module group.'));
				$row->addYesNoRadio('group')->checked('N')->required();

			$form->toggleVisibilityByClass('messageGroup')->onRadio('group')->when('Y');

            if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_groups_any")) {
				$data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                $sql = "SELECT gibbonGroup.gibbonGroupID as value, gibbonGroup.name FROM gibbonGroup WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY name";
            } else {
                $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonSchoolYearID2' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID2' => $_SESSION[$guid]['gibbonPersonID']);
				$sql = "(SELECT gibbonGroup.gibbonGroupID as value, gibbonGroup.name FROM gibbonGroup WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonIDOwner=:gibbonPersonID ORDER BY name)
					UNION
					(SELECT gibbonGroup.gibbonGroupID as value, gibbonGroup.name FROM gibbonGroup JOIN gibbonGroupPerson ON (gibbonGroupPerson.gibbonGroupID=gibbonGroup.gibbonGroupID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID2 AND gibbonPersonID=:gibbonPersonID2)
					ORDER BY name
					";
            }

			$row = $form->addRow()->addClass('messageGroup hiddenReveal');
				$row->addLabel('groups[]', __('Select Groups'));
				$row->addSelect('groups[]')->fromQuery($pdo, $sql, $data)->selectMultiple()->setSize(6)->required();

			$row = $form->addRow()->addClass('messageGroup hiddenReveal');
		        $row->addLabel('groupsStaff', __('Include Staff?'));
				$row->addYesNo('groupsStaff')->selected($defaultSendStaff);

			$row = $form->addRow()->addClass('messageGroup hiddenReveal');
		        $row->addLabel('groupsStudents', __('Include Students?'));
				$row->addYesNo('groupsStudents')->selected($defaultSendStudents);

			if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_groups_parents")) {
				$row = $form->addRow()->addClass('messageGroup hiddenReveal');
			        $row->addLabel('groupsParents', __('Include Parents?'))->description('Parents who are members, and parents of student members.');
					$row->addYesNo('groupsParents')->selected($defaultSendParents);
			}
        }

        // Individuals
        if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_individuals")) {
            $row = $form->addRow();
				$row->addLabel('individuals', __('Individuals'))->description(__('Individuals from the whole school.'));
				$row->addYesNoRadio('individuals')->checked('N')->required();

			$form->toggleVisibilityByClass('individuals')->onRadio('individuals')->when('Y');

            $sql = "SELECT gibbonRole.category, gibbonPersonID, preferredName, surname, username FROM gibbonPerson JOIN gibbonRole ON (gibbonRole.gibbonRoleID=gibbonPerson.gibbonRoleIDPrimary) WHERE status='Full' ORDER BY surname, preferredName";
            $result = $pdo->executeQuery(array(), $sql);

            // Build a set of individuals by ID => formatted name
            $individuals = ($result->rowCount() > 0)? $result->fetchAll() : array();
            $individuals = array_reduce($individuals, function($group, $item){
                $group[$item['gibbonPersonID']] = Format::name("", $item['preferredName'], $item['surname'], 'Student', true) . ' ('.$item['username'].', '.__($item['category']).')';
                return $group;
            }, array());

			$row = $form->addRow()->addClass('individuals hiddenReveal');
				$row->addLabel('individualList[]', __('Select Individuals'));
				$row->addSelect('individualList[]')->fromArray($individuals)->selectMultiple()->setSize(6)->required();
        }

		$row = $form->addRow();
			$row->addFooter();
			$row->addSubmit();

		echo $form->getOutput();
	}
}
