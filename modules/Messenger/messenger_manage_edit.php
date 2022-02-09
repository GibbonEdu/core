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

use Gibbon\Domain\System\SettingGateway;
use Gibbon\Forms\Form;
use Gibbon\Services\Format;
use Gibbon\Domain\User\RoleGateway;

if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_manage_edit.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Get action with highest precendence
	$highestAction=getHighestGroupedAction($guid, $_GET["q"], $connection2) ;
	if ($highestAction==FALSE) {
		print "<div class='error'>" ;
		print __("The highest grouped action cannot be determined.") ;
		print "</div>" ;
	}
	else {
        $search = $_GET['search'] ?? null;
        $updateReturn = $_GET["updateReturn"] ?? '';

        $page->breadcrumbs
            ->add(__('Manage Messages'), 'messenger_manage.php', ['search' => $search])
            ->add(__('Edit Message'));

		$updateReturnMessage="" ;
		$class="error" ;
		if (!($updateReturn=="")) {
			if ($updateReturn=="fail0") {
				$updateReturnMessage=__("Your request failed because you do not have access to this action.") ;
			}
			else if ($updateReturn=="fail1") {
				$updateReturnMessage=__("Your request failed because your inputs were invalid.") ;
			}
			else if ($updateReturn=="fail2") {
				$updateReturnMessage=__("Your request failed due to a database error.") ;
			}
			else if ($updateReturn=="fail3") {
				$updateReturnMessage=__("Your request failed because your inputs were invalid.") ;
			}
			else if ($updateReturn=="fail4") {
				$updateReturnMessage=__("Your request failed because your inputs were invalid.") ;
			}
			else if ($updateReturn=="success0") {
				$updateReturnMessage=__("Your request was completed successfully.") ;
				$class="success" ;
			}
			print "<div class='$class'>" ;
				print $updateReturnMessage;
			print "</div>" ;
		}

		//Check if gibbonMessengerID specified
		$gibbonMessengerID=$_GET["gibbonMessengerID"] ;
		if ($gibbonMessengerID=="") {
			print "<div class='error'>" ;
				print __("You have not specified one or more required parameters.") ;
			print "</div>" ;
		}
		else {
			try {
				if ($highestAction=="Manage Messages_all") {
					$data=array("gibbonMessengerID"=>$gibbonMessengerID);
					$sql="SELECT gibbonMessenger.*, title, surname, preferredName FROM gibbonMessenger JOIN gibbonPerson ON (gibbonMessenger.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonMessengerID=:gibbonMessengerID" ;
				}
				else {
					$data=array("gibbonMessengerID"=>$gibbonMessengerID, "gibbonPersonID"=>$session->get('gibbonPersonID'));
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
					print __("The specified record cannot be found.") ;
				print "</div>" ;
			}
			else {
				//Let's go!
				$values=$result->fetch() ;
				echo '<div class="warning">';
					echo '<b><u>'.__('Note').'</u></b>: '.__('Changes made here do not apply to emails and SMS messages (which have already been sent), but only to message wall messages.');
				echo '</div>';

				$form = Form::create('action', $session->get('absoluteURL').'/modules/'.$session->get('module').'/messenger_manage_editProcess.php');

				$form->addHiddenValue('address', $session->get('address'));
				$form->addHiddenValue('gibbonMessengerID', $values['gibbonMessengerID']);

				$form->addRow()->addHeading('Delivery Mode', __('Delivery Mode'));
				//Delivery by email
				if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_byEmail")) {
					$row = $form->addRow();
						$row->addLabel('email', __('Email'))->description(__('Deliver this message to user\'s primary email account?'));
						if ($values["email"]=="Y") {
							$row->addContent("<img title='" . __('Sent by email.') . "' src='./themes/" . $session->get('gibbonThemeName') . "/img/iconTick.png'/>")->addClass('right');
						}
						else {
							$row->addContent("<img title='" . __('Not sent by email.') . "' src='./themes/" . $session->get('gibbonThemeName') . "/img/iconCross.png'/>")->addClass('right') ;
						}
				}

				//Delivery by message wall
				if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_byMessageWall")) {
					$row = $form->addRow();
						$row->addLabel('messageWall', __('Message Wall'))->description(__('Place this message on user\'s message wall?'));
						$row->addYesNoRadio('messageWall')->checked('N')->required();

					$form->toggleVisibilityByClass('messageWall')->onRadio('messageWall')->when('Y');

					if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_manage.php", "Manage Messages_all")) {
						$row = $form->addRow()->addClass('messageWall');
							$row->addLabel('messageWallPin', __('Pin To Top?'));
							$row->addYesNo('messageWallPin')->selected($values['messageWallPin'])->required();
					}

					$row = $form->addRow()->addClass('messageWall');
				        $row->addLabel('date1', __('Publication Dates'))->description(__('Select up to three individual dates.'));
						$col = $row->addColumn('date1')->addClass('stacked');
						$col->addDate('date1')->setValue(Format::date($values['messageWall_date1']))->required();
						$col->addDate('date2')->setValue(Format::date($values['messageWall_date2']));
						$col->addDate('date3')->setValue(Format::date($values['messageWall_date3']));
				}

				//Delivery by SMS
				if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_bySMS")) {
                    $settingGateway = $container->get(SettingGateway::class);
					$smsUsername = $settingGateway->getSettingByScope("Messenger", "smsUsername" ) ;
					$smsPassword = $settingGateway->getSettingByScope("Messenger", "smsPassword" ) ;
					$smsURL = $settingGateway->getSettingByScope("Messenger", "smsURL" ) ;
					$smsURLCredit = $settingGateway->getSettingByScope("Messenger", "smsURLCredit" ) ;
					if ($smsUsername == "" OR $smsPassword == "" OR $smsURL == "") {
						$row = $form->addRow()->addClass('sms');
							$row->addLabel('sms', __('SMS'))->description(__('Deliver this message to user\'s mobile phone?'));
							$row->addAlert(sprintf(__('SMS NOT CONFIGURED. Please contact %1$s for help.'), "<a href='mailto:" . $session->get('organisationAdministratorEmail') . "'>" . $session->get('organisationAdministratorName') . "</a>"), 'message');
					}
					else {
						$row = $form->addRow();
							$row->addLabel('sms', __('SMS'))->description(__('Deliver this message to user\'s mobile phone?'));
							if ($values["sms"]=="Y") {
								$row->addContent("<img title='" . __('Sent by email.') . "' src='./themes/" . $session->get('gibbonThemeName') . "/img/iconTick.png'/>")->addClass('right');
							}
							else {
								$row->addContent("<img title='" . __('Not sent by email.') . "' src='./themes/" . $session->get('gibbonThemeName') . "/img/iconCross.png'/>")->addClass('right') ;
							}
					}
				}

				//MESSAGE DETAILS
				$form->addRow()->addHeading('Message Details', __('Message Details'));

				$row = $form->addRow();
					$row->addLabel('subject', __('Subject'));
					$row->addTextField('subject')->maxLength(60)->required();

				$row = $form->addRow();
			        $col = $row->addColumn('body');
			        $col->addLabel('body', __('Body'));
			        $col->addEditor('body', $guid)->required()->setRows(20)->showMedia(true);

				//READ RECEIPTS
				if (!isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_readReceipts")) {
					$form->addHiddenValue('emailReceipt', 'N');
				}
				else {
					$form->addRow()->addHeading('Email Read Receipts', __('Email Read Receipts'));

					$row = $form->addRow();
						$row->addLabel('emailReceipt', __('Enable Read Receipts'))->description(__('Each email recipient will receive a personalised confirmation link.'));
						if ($values["emailReceipt"]=="Y") {
							$row->addContent("<img title='" . __('Sent by email.') . "' src='./themes/" . $session->get('gibbonThemeName') . "/img/iconTick.png'/>")->addClass('right');
						}
						else {
							$row->addContent("<img title='" . __('Not sent by email.') . "' src='./themes/" . $session->get('gibbonThemeName') . "/img/iconCross.png'/>")->addClass('right') ;
						}

					$row = $form->addRow()->addClass('emailReceipt');
						$row->addLabel('emailReceiptText', __('Link Text'))->description(__('Confirmation link text to display to recipient.'));
						$row->addTextArea('emailReceiptText')->setRows(3)->required()->setValue(__('By clicking on this link I confirm that I have read, and agree to, the text contained within this email, and give consent for my child to participate.'))->readonly();
				}

				//TARGETS
				$form->addRow()->addHeading('Targets', __('Targets'));
				$roleCategory = getRoleCategory($session->get('gibbonRoleIDCurrent'), $connection2);

				//Get existing TARGETS
				try {
					$dataTarget=array("gibbonMessengerID"=>$gibbonMessengerID);
					$sqlTarget="SELECT * FROM gibbonMessengerTarget WHERE gibbonMessengerID=:gibbonMessengerID ORDER BY type" ;
					$resultTarget=$connection2->prepare($sqlTarget);
					$resultTarget->execute($dataTarget);
				}
				catch(PDOException $e) {
					echo "<div class='error'>" . $e->getMessage() . "</div>" ;
				}

				$targets = $resultTarget->fetchAll();

				//Role
				if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_role")) {
					$selected = array_reduce($targets, function($group, $item) {
						if ($item['type'] == 'Role') $group[] = $item['id'];
						return $group;
					}, array());
					$checked = !empty($selected)? 'Y' : 'N';
					$row = $form->addRow();
						$row->addLabel('role', __('Role'))->description(__('Users of a certain type.'));
						$row->addYesNoRadio('role')->checked($checked)->required();

					$form->toggleVisibilityByClass('role')->onRadio('role')->when('Y');

					$roleGateway = $container->get(RoleGateway::class);
					// CRITERIA
					$criteria = $roleGateway->newQueryCriteria()
						->sortBy(['gibbonRole.name']);

					$arrRoles = array();
					$roles = $roleGateway->queryRoles($criteria);

					foreach ($roles AS $role) {
						$arrRoles[$role['gibbonRoleID']] = __($role['name'])." (".__($role['category']).")";
					}
					$row = $form->addRow()->addClass('role hiddenReveal');
						$row->addLabel('roles[]', __('Select Roles'));
						$row->addSelect('roles[]')->fromArray($arrRoles)->selectMultiple()->setSize(6)->required()->placeholder()->selected($selected);

					//Role Category
					$selected = array_reduce($targets, function($group, $item) {
						if ($item['type'] == 'Role Category') $group[] = $item['id'];
						return $group;
					}, array());
					$checked = !empty($selected)? 'Y' : 'N';
					$row = $form->addRow();
						$row->addLabel('roleCategory', __('Role Category'))->description(__('Users of a certain type.'));
						$row->addYesNoRadio('roleCategory')->checked($checked)->required();

					$form->toggleVisibilityByClass('roleCategory')->onRadio('roleCategory')->when('Y');

					$data = array();
					$sql = 'SELECT DISTINCT category AS value, category AS name FROM gibbonRole ORDER BY category';
					$row = $form->addRow()->addClass('roleCategory hiddenReveal');
						$row->addLabel('roleCategories[]', __('Select Role Categories'));
						$row->addSelect('roleCategories[]')->fromQuery($pdo, $sql, $data)->selectMultiple()->setSize(4)->required()->placeholder()->selected($selected);
				} else if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_postQuickWall.php")) {
                    // Handle the edge case where a user can post a Quick Wall message but doesn't have access to the Role target
                    $row = $form->addRow();
						$row->addLabel('roleCategoryLabel', __('Role Category'))->description(__('Users of a certain type.'));
                        $row->addYesNoRadio('roleCategoryLabel')->checked('Y')->readonly()->disabled();

                    $form->addHiddenValue('role', 'N');
                    $form->addHiddenValue('roleCategory', 'Y');
                    foreach ($targets as $target) {
                        if ($target['type'] == 'Role Category') {
                            $form->addHiddenValue('roleCategories[]', $target['id']);
                        }
                    }
                }

				//Year group
				if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_yearGroups_any")) {
					$selectedByRole = array('staff' => 'N', 'students' => 'N', 'parents' => 'N',);
					$selected = array_reduce($targets, function($group, $item) use (&$selectedByRole) {
						if ($item['type'] == 'Year Group') {
							$group[] = $item['id'];
							$selectedByRole['staff'] = $item['staff'];
							$selectedByRole['students'] = $item['students'];
							$selectedByRole['parents'] = $item['parents'];
						}
						return $group;
					}, array());
					$checked = !empty($selected)? 'Y' : 'N';
					$row = $form->addRow();
						$row->addLabel('yearGroup', __('Year Group'))->description(__('Students in year; staff by tutors and courses taught.'));
						$row->addYesNoRadio('yearGroup')->checked($checked)->required();

					$form->toggleVisibilityByClass('yearGroup')->onRadio('yearGroup')->when('Y');

					$data = array();
					$sql = 'SELECT gibbonYearGroupID AS value, name FROM gibbonYearGroup ORDER BY sequenceNumber';
					$row = $form->addRow()->addClass('yearGroup hiddenReveal');
						$row->addLabel('yearGroups[]', __('Select Year Groups'));
						$row->addSelect('yearGroups[]')->fromQuery($pdo, $sql, $data)->selectMultiple()->setSize(6)->required()->placeholder()->selected($selected);

					$row = $form->addRow()->addClass('yearGroup hiddenReveal');
						$row->addLabel('yearGroupsStaff', __('Include Staff?'));
						$row->addYesNo('yearGroupsStaff')->selected($selectedByRole['staff']);

					$row = $form->addRow()->addClass('yearGroup hiddenReveal');
						$row->addLabel('yearGroupsStudents', __('Include Students?'));
							$row->addYesNo('yearGroupsStudents')->selected($selectedByRole['students']);

					if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_yearGroups_parents")) {
						$row = $form->addRow()->addClass('yearGroup hiddenReveal');
							$row->addLabel('yearGroupsParents', __('Include Parents?'));
							$row->addYesNo('yearGroupsParents')->selected($selectedByRole['parents']);
					}
				}

				//Form group
				if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_formGroups_my") OR isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_formGroups_any")) {
					$selectedByRole = array('staff' => 'N', 'students' => 'N', 'parents' => 'N',);
					$selected = array_reduce($targets, function($group, $item) use (&$selectedByRole) {
						if ($item['type'] == 'Form Group') {
							$group[] = $item['id'];
							$selectedByRole['staff'] = $item['staff'];
							$selectedByRole['students'] = $item['students'];
							$selectedByRole['parents'] = $item['parents'];
						}
						return $group;
					}, array());
					$checked = !empty($selected)? 'Y' : 'N';
					$row = $form->addRow();
						$row->addLabel('formGroup', __('Form Group'))->description(__('Tutees and tutors.'));
						$row->addYesNoRadio('formGroup')->checked($checked)->required();

					$form->toggleVisibilityByClass('formGroup')->onRadio('formGroup')->when('Y');

					if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_formGroups_any")) {
						$data=array("gibbonSchoolYearID"=>$session->get('gibbonSchoolYearID'));
						$sql="SELECT gibbonFormGroupID AS value, name FROM gibbonFormGroup WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY name" ;
					}
					else {
						if ($roleCategory == "Staff") {
							$data=array("gibbonSchoolYearID"=>$session->get('gibbonSchoolYearID'), "gibbonPersonID1"=>$session->get('gibbonPersonID'), "gibbonPersonID2"=>$session->get('gibbonPersonID'), "gibbonPersonID3"=>$session->get('gibbonPersonID'), "gibbonSchoolYearID"=>$session->get('gibbonSchoolYearID'));
							$sql="SELECT gibbonFormGroupID AS value, name FROM gibbonFormGroup WHERE (gibbonPersonIDTutor=:gibbonPersonID1 OR gibbonPersonIDTutor2=:gibbonPersonID2 OR gibbonPersonIDTutor3=:gibbonPersonID3) AND gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY name" ;
						}
						else if ($roleCategory == "Student") {
							$data=array("gibbonSchoolYearID"=>$session->get('gibbonSchoolYearID'), "gibbonPersonID"=>$session->get('gibbonPersonID'), );
							$sql="SELECT gibbonFormGroupID AS value, name FROM gibbonFormGroup JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID) WHERE gibbonPersonID=:gibbonPersonID AND gibbonFormGroup.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY name" ;
						}
					}
					$row = $form->addRow()->addClass('formGroup hiddenReveal');
						$row->addLabel('formGroups[]', __('Select Form Groups'));
						$row->addSelect('formGroups[]')->fromQuery($pdo, $sql, $data)->selectMultiple()->setSize(6)->required()->placeholder()->selected($selected);

					$row = $form->addRow()->addClass('formGroup hiddenReveal');
						$row->addLabel('formGroupsStaff', __('Include Staff?'));
						$row->addYesNo('formGroupsStaff')->selected($selectedByRole['staff']);

					$row = $form->addRow()->addClass('formGroup hiddenReveal');
						$row->addLabel('formGroupsStudents', __('Include Students?'));
						$row->addYesNo('formGroupsStudents')->selected($selectedByRole['students']);

					if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_formGroups_parents")) {
						$row = $form->addRow()->addClass('formGroup hiddenReveal');
							$row->addLabel('formGroupsParents', __('Include Parents?'));
							$row->addYesNo('formGroupsParents')->selected($selectedByRole['parents']);
					}
				}

				// Course
				if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_courses_my") OR isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_courses_any")) {
					$selectedByRole = array('staff' => 'N', 'students' => 'N', 'parents' => 'N',);
					$selected = array_reduce($targets, function($group, $item) use (&$selectedByRole) {
						if ($item['type'] == 'Course') {
							$group[] = $item['id'];
							$selectedByRole['staff'] = $item['staff'];
							$selectedByRole['students'] = $item['students'];
							$selectedByRole['parents'] = $item['parents'];
						}
						return $group;
					}, array());
					$checked = !empty($selected)? 'Y' : 'N';
					$row = $form->addRow();
						$row->addLabel('course', __('Course'))->description(__('Members of a course of study.'));
						$row->addYesNoRadio('course')->checked($checked)->required();

					$form->toggleVisibilityByClass('course')->onRadio('course')->when('Y');

					if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_courses_any")) {
						$data = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'));
						$sql = "SELECT gibbonCourseID as value, nameShort as name FROM gibbonCourse WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY name";
					} else {
						$data = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'gibbonPersonID' => $session->get('gibbonPersonID'));
						$sql = "SELECT gibbonCourse.gibbonCourseID as value, gibbonCourse.nameShort as name
                                FROM gibbonCourse
                                JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID)
                                JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID)
                                WHERE gibbonPersonID=:gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID AND NOT role LIKE '%- Left' GROUP BY gibbonCourse.gibbonCourseID ORDER BY name";
					}

					$row = $form->addRow()->addClass('course hiddenReveal');
						$row->addLabel('courses[]', __('Select Courses'));
						$row->addSelect('courses[]')->fromQuery($pdo, $sql, $data)->selectMultiple()->setSize(6)->required()->selected($selected);

					$row = $form->addRow()->addClass('course hiddenReveal');
						$row->addLabel('coursesStaff', __('Include Staff?'));
						$row->addYesNo('coursesStaff')->selected($selectedByRole['staff']);

					$row = $form->addRow()->addClass('course hiddenReveal');
						$row->addLabel('coursesStudents', __('Include Students?'));
						$row->addYesNo('coursesStudents')->selected($selectedByRole['students']);

					if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_courses_parents")) {
						$row = $form->addRow()->addClass('course hiddenReveal');
							$row->addLabel('coursesParents', __('Include Parents?'));
							$row->addYesNo('coursesParents')->selected($selectedByRole['parents']);
					}
				}

				// Class
				if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_classes_my") OR isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_classes_any")) {
					$selectedByRole = array('staff' => 'N', 'students' => 'N', 'parents' => 'N',);
					$selected = array_reduce($targets, function($group, $item) use (&$selectedByRole) {
						if ($item['type'] == 'Class') {
							$group[] = $item['id'];
							$selectedByRole['staff'] = $item['staff'];
							$selectedByRole['students'] = $item['students'];
							$selectedByRole['parents'] = $item['parents'];
						}
						return $group;
					}, array());
					$checked = !empty($selected)? 'Y' : 'N';
					$row = $form->addRow();
						$row->addLabel('class', __('Class'))->description(__('Members of a class within a course.'));
						$row->addYesNoRadio('class')->checked($checked)->required();

					$form->toggleVisibilityByClass('class')->onRadio('class')->when('Y');

					if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_classes_any")) {
						$data = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'));
						$sql = "SELECT gibbonCourseClassID as value, CONCAT(gibbonCourse.nameShort, '.', gibbonCourseClass.nameShort) as name FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY name";
					} else {
						$data = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'gibbonPersonID' => $session->get('gibbonPersonID'));
						$sql = "SELECT gibbonCourseClass.gibbonCourseClassID as value, CONCAT(gibbonCourse.nameShort, '.', gibbonCourseClass.nameShort) as name
                            FROM gibbonCourse
                            JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID)
                            JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID)
                            WHERE gibbonPersonID=:gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID AND NOT role LIKE '%- Left' ORDER BY gibbonCourseClass.name";
					}

					$row = $form->addRow()->addClass('class hiddenReveal');
						$row->addLabel('classes[]', __('Select Classes'));
						$row->addSelect('classes[]')->fromQuery($pdo, $sql, $data)->selectMultiple()->setSize(6)->required()->selected($selected);

					$row = $form->addRow()->addClass('class hiddenReveal');
						$row->addLabel('classesStaff', __('Include Staff?'));
						$row->addYesNo('classesStaff')->selected($selectedByRole['staff']);

					$row = $form->addRow()->addClass('class hiddenReveal');
						$row->addLabel('classesStudents', __('Include Students?'));
						$row->addYesNo('classesStudents')->selected($selectedByRole['students']);

					if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_classes_parents")) {
						$row = $form->addRow()->addClass('class hiddenReveal');
							$row->addLabel('classesParents', __('Include Parents?'));
							$row->addYesNo('classesParents')->selected($selectedByRole['parents']);
					}
				}

				//Activities
				if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_activities_my") OR isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_activities_any")) {
					$selectedByRole = array('staff' => 'N', 'students' => 'N', 'parents' => 'N',);
					$selected = array_reduce($targets, function($group, $item) use (&$selectedByRole) {
						if ($item['type'] == 'Activity') {
							$group[] = $item['id'];
							$selectedByRole['staff'] = $item['staff'];
							$selectedByRole['students'] = $item['students'];
							$selectedByRole['parents'] = $item['parents'];
						}
						return $group;
					}, array());
					$checked = !empty($selected)? 'Y' : 'N';
					$row = $form->addRow();
						$row->addLabel('activity', __('Activity'))->description(__('Members of an activity.'));
						$row->addYesNoRadio('activity')->checked($checked)->required();

					$form->toggleVisibilityByClass('activity')->onRadio('activity')->when('Y');

					if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_activities_any")) {
						$data = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'));
						$sql = "SELECT gibbonActivityID as value, name FROM gibbonActivity WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND active='Y' ORDER BY name";
					} else {
						$data = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'gibbonPersonID' => $session->get('gibbonPersonID'));
						if ($roleCategory == "Staff") {
							$sql = "SELECT gibbonActivity.gibbonActivityID as value, name FROM gibbonActivity JOIN gibbonActivityStaff ON (gibbonActivityStaff.gibbonActivityID=gibbonActivity.gibbonActivityID) WHERE gibbonPersonID=:gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID AND active='Y' ORDER BY name";
						} else if ($roleCategory == "Student") {
							$sql = "SELECT gibbonActivity.gibbonActivityID as value, name FROM gibbonActivity JOIN gibbonActivityStudent ON (gibbonActivityStudent.gibbonActivityID=gibbonActivity.gibbonActivityID) WHERE gibbonPersonID=:gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID AND status='Accepted' AND active='Y' ORDER BY name";
						}
					}
					$row = $form->addRow()->addClass('activity hiddenReveal');
						$row->addLabel('activities[]', __('Select Activities'));
						$row->addSelect('activities[]')->fromQuery($pdo, $sql, $data)->selectMultiple()->setSize(6)->required()->selected($selected);

					$row = $form->addRow()->addClass('activity hiddenReveal');
						$row->addLabel('activitiesStaff', __('Include Staff?'));
						$row->addYesNo('activitiesStaff')->selected($selectedByRole['staff']);

					$row = $form->addRow()->addClass('activity hiddenReveal');
						$row->addLabel('activitiesStudents', __('Include Students?'));
						$row->addYesNo('activitiesStudents')->selected($selectedByRole['students']);

					if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_activities_parents")) {
						$row = $form->addRow()->addClass('activity hiddenReveal');
							$row->addLabel('activitiesParents', __('Include Parents?'));
							$row->addYesNo('activitiesParents')->selected($selectedByRole['parents']);
					}
				}

				// Applicants
				if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_applicants")) {
					$selected = array_reduce($targets, function($group, $item) {
						if ($item['type'] == 'Applicants') $group[] = $item['id'];
						return $group;
					}, array());
					$checked = !empty($selected)? 'Y' : 'N';
					$row = $form->addRow();
						$row->addLabel('applicants', __('Applicants'))->description(__('Applicants from a given year.'))->description(__('Does not apply to the message wall.'));
						$row->addYesNoRadio('applicants')->checked($checked)->required();

					$form->toggleVisibilityByClass('applicants')->onRadio('applicants')->when('Y');

					$sql = "SELECT gibbonSchoolYearID as value, name FROM gibbonSchoolYear ORDER BY sequenceNumber DESC";
					$row = $form->addRow()->addClass('applicants hiddenReveal');
						$row->addLabel('applicantList[]', __('Select Years'));
						$row->addSelect('applicantList[]')->fromQuery($pdo, $sql)->selectMultiple()->setSize(6)->required()->selected($selected);
				}

				// Houses
				if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_houses_all") OR isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_houses_my")) {
					$selected = array_reduce($targets, function($group, $item) {
						if ($item['type'] == 'Houses') $group[] = $item['id'];
						return $group;
					}, array());
					$checked = !empty($selected)? 'Y' : 'N';
					$row = $form->addRow();
						$row->addLabel('houses', __('Houses'))->description(__('Houses for competitions, etc.'));
						$row->addYesNoRadio('houses')->checked($checked)->required();

					$form->toggleVisibilityByClass('houses')->onRadio('houses')->when('Y');

					if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_houses_all")) {
						$data = array();
						$sql = "SELECT gibbonHouseID as value, name FROM gibbonHouse ORDER BY name";
					} else if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_houses_my")) {
						$dataSelect = array('gibbonPersonID'=>$session->get('gibbonPersonID'));
						$sql = "SELECT gibbonHouse.gibbonHouseID as value, name FROM gibbonHouse JOIN gibbonPerson ON (gibbonHouse.gibbonHouseID=gibbonPerson.gibbonHouseID) WHERE gibbonPersonID=:gibbonPersonID ORDER BY name";
					}
					$row = $form->addRow()->addClass('houses hiddenReveal');
						$row->addLabel('houseList[]', __('Select Houses'));
						$row->addSelect('houseList[]')->fromQuery($pdo, $sql, $data)->selectMultiple()->setSize(6)->required()->selected($selected);
				}

				// Transport
				if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_transport_any")) {
					$selectedByRole = array('staff' => 'Y', 'students' => 'Y', 'parents' => 'N');
					$selected = array_reduce($targets, function($group, $item) use (&$selectedByRole) {
						if ($item['type'] == 'Transport') {
							$group[] = $item['id'];
							$selectedByRole['staff'] = $item['staff'];
							$selectedByRole['students'] = $item['students'];
							$selectedByRole['parents'] = $item['parents'];
						}
						return $group;
					}, array());
					$checked = !empty($selected)? 'Y' : 'N';
					$row = $form->addRow();
						$row->addLabel('transport', __('Transport'))->description(__('Applies to all staff and students who have transport set.'));
						$row->addYesNoRadio('transport')->checked($checked)->required();

					$form->toggleVisibilityByClass('transport')->onRadio('transport')->when('Y');

					$sql = "SELECT DISTINCT transport as value, transport as name FROM gibbonPerson WHERE status='Full' AND NOT transport='' ORDER BY transport";
					$row = $form->addRow()->addClass('transport hiddenReveal');
						$row->addLabel('transports[]', __('Select Transport'));
						$row->addSelect('transports[]')->fromQuery($pdo, $sql)->selectMultiple()->setSize(6)->required()->selected($selected);

					$row = $form->addRow()->addClass('transport hiddenReveal');
						$row->addLabel('transportStaff', __('Include Staff?'));
						$row->addYesNo('transportStaff')->selected($selectedByRole['staff']);

					$row = $form->addRow()->addClass('transport hiddenReveal');
						$row->addLabel('transportStudents', __('Include Students?'));
						$row->addYesNo('transportStudents')->selected($selectedByRole['students']);

					if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_transport_parents")) {
						$row = $form->addRow()->addClass('transport hiddenReveal');
							$row->addLabel('transportParents', __('Include Parents?'));
							$row->addYesNo('transportParents')->selected($selectedByRole['parents']);
					}
				}

				// Attendance Status / Absentees
				if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_attendance")) {
					$selectedByRole = array('students' => 'N', 'parents' => 'N',);
					$selected = array_reduce($targets, function($group, $item) use (&$selectedByRole) {
						if ($item['type'] == 'Attendance') {
							$group[] = $item['id'];
							$selectedByRole['students'] = $item['students'];
							$selectedByRole['parents'] = $item['parents'];
						}
						return $group;
					}, array());
					$checked = !empty($selected)? 'Y' : 'N';$row = $form->addRow();
						$row->addLabel('attendance', __('Attendance Status'))->description(__('Students matching the given attendance status.'));
						$row->addYesNoRadio('attendance')->checked($checked)->required();

					$form->toggleVisibilityByClass('attendance')->onRadio('attendance')->when('Y');

					$sql = "SELECT name, gibbonRoleIDAll FROM gibbonAttendanceCode WHERE active = 'Y' ORDER BY direction DESC, sequenceNumber ASC, name";
					$result = $pdo->executeQuery(array(), $sql);

					// Filter the attendance codes by allowed roles (if any)
					$currentRole = $session->get('gibbonRoleIDCurrent');
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
						$row->addSelect('attendanceStatus[]')->fromArray($attendanceCodes)->selectMultiple()->setSize(6)->required()->selected($selected);

					$row = $form->addRow()->addClass('attendance hiddenReveal');
						$row->addLabel('attendanceStudents', __('Include Students?'));
						$row->addYesNo('attendanceStudents')->selected($selectedByRole['students']);

					$row = $form->addRow()->addClass('attendance hiddenReveal');
						$row->addLabel('attendanceParents', __('Include Parents?'));
						$row->addYesNo('attendanceParents')->selected($selectedByRole['parents']);
				}

				// Group
				if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_groups_my") OR isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_groups_any")) {
					$selectedByRole = array('staff' => 'N', 'students' => 'N', 'parents' => 'N',);
					$selected = array_reduce($targets, function($group, $item) use (&$selectedByRole) {
						if ($item['type'] == 'Group') {
							$group[] = $item['id'];
							$selectedByRole['staff'] = $item['staff'];
							$selectedByRole['students'] = $item['students'];
							$selectedByRole['parents'] = $item['parents'];
						}
						return $group;
					}, array());
					$checked = !empty($selected)? 'Y' : 'N';
					$row = $form->addRow();
						$row->addLabel('group', __('Group'))->description(__('Members of a Messenger module group.'));
						$row->addYesNoRadio('group')->checked($checked)->required();

					$form->toggleVisibilityByClass('messageGroup')->onRadio('group')->when('Y');

					if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_groups_any")) {
						$data = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'));
						$sql = "SELECT gibbonGroup.gibbonGroupID as value, gibbonGroup.name FROM gibbonGroup WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY name";
					} else {
						$data = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'gibbonPersonID' => $session->get('gibbonPersonID'), 'gibbonSchoolYearID2' => $session->get('gibbonSchoolYearID'), 'gibbonPersonID2' => $session->get('gibbonPersonID'));
						$sql = "(SELECT gibbonGroup.gibbonGroupID as value, gibbonGroup.name FROM gibbonGroup WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonIDOwner=:gibbonPersonID ORDER BY name)
							UNION
							(SELECT gibbonGroup.gibbonGroupID as value, gibbonGroup.name FROM gibbonGroup JOIN gibbonGroupPerson ON (gibbonGroupPerson.gibbonGroupID=gibbonGroup.gibbonGroupID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID2 AND gibbonPersonID=:gibbonPersonID2)
							ORDER BY name
							";
					}

					$row = $form->addRow()->addClass('messageGroup hiddenReveal');
						$row->addLabel('groups[]', __('Select Groups'));
						$row->addSelect('groups[]')->fromQuery($pdo, $sql, $data)->selectMultiple()->setSize(6)->required()->selected($selected);;

					$row = $form->addRow()->addClass('messageGroup hiddenReveal');
						$row->addLabel('groupsStaff', __('Include Staff?'));
						$row->addYesNo('groupsStaff')->selected($selectedByRole['staff']);

					$row = $form->addRow()->addClass('messageGroup hiddenReveal');
						$row->addLabel('groupsStudents', __('Include Students?'));
						$row->addYesNo('groupsStudents')->selected($selectedByRole['students']);

					if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_groups_parents")) {
						$row = $form->addRow()->addClass('messageGroup hiddenReveal');
							$row->addLabel('groupsParents', __('Include Parents?'))->description('Parents who are members, and parents of student members.');
							$row->addYesNo('groupsParents')->selected($selectedByRole['parents']);
					}
				}

				// Individuals
				if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_individuals")) {
					$selected = array_reduce($targets, function($group, $item) {
						if ($item['type'] == 'Individuals') $group[] = $item['id'];
						return $group;
					}, array());
					$checked = !empty($selected)? 'Y' : 'N';$row = $form->addRow();
						$row->addLabel('individuals', __('Individuals'))->description(__('Individuals from the whole school.'));
						$row->addYesNoRadio('individuals')->checked($checked)->required();

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
						$row->addSelect('individualList[]')->fromArray($individuals)->selectMultiple()->setSize(6)->required()->selected($selected);
				}

				$form->loadAllValuesFrom($values);

				$row = $form->addRow();
					$row->addFooter();
					$row->addSubmit();

				echo $form->getOutput();
			}
		}
	}
}
