<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright © 2010, Gibbon Foundation
Gibbon™, Gibbon Education Ltd. (Hong Kong)

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

namespace Gibbon\Module\Messenger\Forms;

use Gibbon\View\View;
use Gibbon\Forms\Form;
use Gibbon\Services\Format;
use Gibbon\Contracts\Comms\SMS;
use Gibbon\Domain\User\RoleGateway;
use Gibbon\Contracts\Services\Session;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Contracts\Database\Connection;
use Gibbon\Domain\Messenger\MessengerGateway;
use Gibbon\Domain\Messenger\CannedResponseGateway;

/**
 * MessageForm
 *
 * @version v25
 * @since   v25
 */
class MessageForm extends Form
{
    protected $session;
    protected $db;
    protected $messengerGateway;
    protected $smsGateway;
    protected $cannedResponseGateway;
    protected $settingGateway;
    protected $roleGateway;
    protected $roleCategory;
    protected $defaultSendStaff;
    protected $defaultSendStudents;
    protected $defaultSendParents;

    public function __construct(Session $session, Connection $db, MessengerGateway $messengerGateway, SMS $smsGateway, CannedResponseGateway $cannedResponseGateway, SettingGateway $settingGateway, RoleGateway $roleGateway)
    {
        $this->session = $session;
        $this->db = $db;
        $this->messengerGateway = $messengerGateway;
        $this->smsGateway = $smsGateway;
        $this->cannedResponseGateway = $cannedResponseGateway;
        $this->settingGateway = $settingGateway;
        $this->roleGateway = $roleGateway;

        $this->roleCategory = $this->session->get('gibbonRoleIDCurrentCategory');

        $this->defaultSendStaff = ($this->roleCategory == 'Staff' || $this->roleCategory == 'Student')? 'Y' : 'N';
        $this->defaultSendStudents = ($this->roleCategory == 'Staff' || $this->roleCategory == 'Student')? 'Y' : 'N';
        $this->defaultSendParents = ($this->roleCategory == 'Parent')? 'Y' : 'N';
    }

    public function createForm($action, $gibbonMessengerID = null)
    {
        $guid = $this->session->get('guid');
        $connection2 = $this->db->getConnection();
        $pdo = $this->db;

        // Get the existing message data, if any
        $values = !empty($gibbonMessengerID) ? $this->messengerGateway->getByID($gibbonMessengerID) : [];
        $sent = !empty($values) && $values['status'] == 'Sent';

        // FORM
        $form = Form::create('messengerMessage', $this->session->get('absoluteURL').'/modules/Messenger/' .$action);
        $form->addHiddenValue('address', $this->session->get('address'));
        $form->addHiddenValue('gibbonMessengerID', $values['gibbonMessengerID'] ?? '');
        $form->addHiddenValue('status', $values['status'] ?? 'Draft');
        $form->addHiddenValue('saveMode', empty($values['status']) || $values['status'] == 'Draft' ? 'Preview' : 'Submit');

        $form->addRow()->addHeading('Delivery Mode', __('Delivery Mode'));

        // Delivery by email
        if (isActionAccessible($guid, $connection2, '/modules/Messenger/messenger_post.php', 'New Message_byEmail')) {
            $row = $form->addRow();
            $row->addLabel('email', __('Email'))->description(__('Deliver this message to user\'s primary email account?'));

            if ($sent) {
                $row->addContent($values['email'] == 'Y' ? Format::icon('iconTick', __('Sent by email.')) : Format::icon('iconCross', __('Not sent by email.')))->addClass('right');
            } else {
                $row->addYesNoRadio('email')->checked('Y')->required();

                $form->toggleVisibilityByClass('email')->onRadio('email')->when('Y');

                $from = [$this->session->get('email') => $this->session->get('email')];
                if ($this->session->has('emailAlternate')) {
                    $from[$this->session->get('emailAlternate')] = $this->session->get('emailAlternate');
                }
                if (isActionAccessible($guid, $connection2, '/modules/Messenger/messenger_post.php', 'New Message_fromSchool') && $this->session->has('organisationEmail')) {
                    $from[$this->session->get('organisationEmail')] = $this->session->get('organisationEmail');
                }
                $row = $form->addRow()->addClass('email');
                    $row->addLabel('emailFrom', __('Email From'));
                    $row->addSelect('emailFrom')->fromArray($from)->required();

                if (isActionAccessible($guid, $connection2, '/modules/Messenger/messenger_post.php', 'New Message_fromSchool')) {
                    $row = $form->addRow()->addClass('email');
                        $row->addLabel('emailReplyTo', __('Reply To'));
                        $row->addEmail('emailReplyTo');
                }
            }

        }

        // Delivery by message wall
        if (isActionAccessible($guid, $connection2, '/modules/Messenger/messenger_post.php', 'New Message_byMessageWall')) {
            $row = $form->addRow();
                $row->addLabel('messageWall', __('Message Wall'))->description(__('Place this message on user\'s message wall?'));
                $row->addYesNoRadio('messageWall')->checked('N')->required();

            $form->toggleVisibilityByClass('messageWall')->onRadio('messageWall')->when('Y');

            if (isActionAccessible($guid, $connection2, '/modules/Messenger/messenger_manage.php', 'Manage Messages_all')) {
                $row = $form->addRow()->addClass('messageWall');
                    $row->addLabel('messageWallPin', __('Pin To Top?'));
                    $row->addYesNo('messageWallPin')->selected($values['messageWallPin'] ?? 'N')->required();
            }

            $row = $form->addRow()->addClass('messageWall');
                $row->addLabel('datePublished', __('Publication Dates'));
                $col = $row->addColumn('dateStart')->addClass('stacked');
                $col->addLabel('dateStart', __('Start Date'));
                $col->addDate('dateStart')
                    ->chainedTo('dateEnd')
                    ->setValue(Format::date($values['messageWall_dateStart'] ?? ''))
                    ->required();
                $col->addLabel('dateEnd', __('End Date'));
                $col->addDate('dateEnd')
                    ->chainedFrom('dateStart')
                    ->setValue(Format::date($values['messageWall_dateEnd'] ?? ''))
                    ->required();
        }

        // Delivery by SMS
        if (isActionAccessible($guid, $connection2, '/modules/Messenger/messenger_post.php', 'New Message_bySMS')) {
            $smsGateway = $this->settingGateway->getSettingByScope('Messenger', 'smsGateway');
            $smsUsername = $this->settingGateway->getSettingByScope('Messenger', 'smsUsername');

            if (empty($smsGateway) || empty($smsUsername)) {
                $row = $form->addRow();
                    $row->addLabel('sms', __('SMS'))->description(__('Deliver this message to user\'s mobile phone?'));
                    $row->addAlert(sprintf(__('SMS NOT CONFIGURED. Please contact %1$s for help.'), "<a href='mailto:" . $this->session->get('organisationAdministratorEmail') . "'>" . $this->session->get('organisationAdministratorName') . "</a>"), 'message');
            } else {
                $row = $form->addRow();
                $row->addLabel('sms', __('SMS'))->description(__('Deliver this message to user\'s mobile phone?'));

                if ($sent) {
                    $row->addContent($values['sms'] == 'Y' ? Format::icon('iconTick', __('Sent by SMS.')) : Format::icon('iconCross', __('Not sent by SMS.')))->addClass('right');
                } else {
                    $row->addYesNoRadio('sms')->checked('N')->required();

                    if ($smsCredits = $this->smsGateway->getCreditBalance()) {
                        $row = $form->addRow()->addClass('sms');
                            $row->addAlert("<b>" . sprintf(__('Current balance: %1$s credit(s).'), $smsCredits) . "</u></b>", 'message');
                    }
                }
            }
        }

        // Confidential Message Option
        if (isActionAccessible($guid, $connection2, '/modules/Messenger/messenger_post.php', 'New Message_confidential')) {
            $row = $form->addRow();
                $row->addLabel('confidential', __('Confidential'))->description(__('Other users will not be able to see this message in Manage Messages.'));
                $row->addYesNoRadio('confidential')->checked($values['confidential'] ?? 'N')->required();
        }

        // MESSAGE DETAILS
        $form->addRow()->addHeading('Message Details', __('Message Details'));

        // CANNED RESPONSES
        $cannedResponse = isActionAccessible($guid, $connection2, '/modules/Messenger/messenger_post.php', 'New Message_cannedResponse');
        if (!$sent && $cannedResponse) {
            $cannedResponses = $this->cannedResponseGateway->selectCannedResponses()->fetchAll();

            if (!empty($cannedResponses)) {
                $this->getCannedResponseJS($cannedResponses);
                $cans = array_combine(array_column($cannedResponses, 'gibbonMessengerCannedResponseID'), array_column($cannedResponses, 'subject'));

                $row = $form->addRow();
                    $row->addLabel('cannedResponse', __('Canned Response'));
                    $row->addSelect('cannedResponse')->fromArray($cans)->placeholder();
            }
        }

        $row = $form->addRow();
            $row->addLabel('subject', __('Subject'));
            $col = $row->addColumn()->addClass('flex-col');
            $col->addTextField('subject')->maxLength(60)->required()->addClass('w-full');

            $form->toggleVisibilityByClass('sms')->onRadio('sms')->when('Y');
            $col->addContent(Format::alert('<b><u>'.__('Note').'</u></b>: '.__('SMS messages will not include the subject line.'), 'warning'))->addClass('sms');


        $row = $form->addRow();
            $col = $row->addColumn('body');
            $col->addLabel('body', __('Body'));
            $col->addEditor('body', $guid)->required()->setRows(20)->showMedia(true)->setValue($values['body'] ?? '');
            $col->addCheckbox('includeSignature')->description(__('Include Signature? (email only)'))->setValue('Y')->checked($values['includeSignature'] ?? 'Y');

        // READ RECEIPTS
        if (!isActionAccessible($guid, $connection2, '/modules/Messenger/messenger_post.php', 'New Message_readReceipts')) {
            $form->addHiddenValue('emailReceipt', 'N');
        } else {
            $form->addRow()->addHeading('Customisation', __('Customisation'));

            $row = $form->addRow();
            $row->addLabel('emailReceipt', __('Enable Read Receipts'))->description(__('Each email recipient will receive a personalised confirmation link.'));

            if ($sent) {
                $row->addContent($values['emailReceipt'] == 'Y' ? Format::icon('iconTick', __('Yes')) : Format::icon('iconCross', __('No')))->addClass('right');
            } else {
                $row->addYesNoRadio('emailReceipt')->checked($values['emailReceipt'] ?? 'N')->required();

                $form->toggleVisibilityByClass('emailReceipt')->onRadio('emailReceipt')->when('Y');
                $form->addRow()->addClass('emailReceipt')
                    ->addContent(__('With read receipts enabled, the text [confirmLink] can be included in a message to add a unique, login-free read receipt link. If [confirmLink] is not included, the link will be appended to the top of the message.'));
            }

            if (empty($values['emailReceiptText'])) {
                $values['emailReceiptText'] = __('By clicking on this link I confirm that I have read, and agree to, the text contained within this email, and give consent for my child to participate.');
            }

            if (!$sent || $values['emailReceipt'] == 'Y') {
                $row = $form->addRow()->addClass('emailReceipt');
                    $row->addLabel('emailReceiptText', __('Link Text'))->description(__('Confirmation link text to display to recipient.'));
                    $row->addTextArea('emailReceiptText')->setRows(4)->required()->setValue($values['emailReceiptText'])->readonly($sent);

                $row = $form->addRow()->addClass('emailReceipt');
                    $row->addLabel('enableSharingLink', __('Shareable Send Report'))->description(__('When enabled, you can share the Send Report for this message with other users.'));
                    $row->addYesNoRadio('enableSharingLink')->required()->checked($values['enableSharingLink'] ?? 'N');
            }
        }

        // Individual naming
        if ($this->roleCategory == 'Staff') {
            $row = $form->addRow();
                $row->addLabel('individualNaming', __('Individual Naming'))->description(__('The names of relevant students will be prepended to messages.'));
                $row->addYesNoRadio('individualNaming')->checked($values['individualNaming'] ?? 'Y')->required();
        } else {
            $form->addHiddenValue('individualNaming', 'N');
        }

        // TARGETS
        $form->addRow()->addHeading('Targets', __('Targets'));

        // Get existing TARGETS
        $targets = $this->messengerGateway->selectMessageTargetsByID($gibbonMessengerID)->fetchAll();
        $selectedRoleCategory = $this->getSelectedTargets($targets, 'Role Category');

        //Role
        if (isActionAccessible($guid, $connection2, '/modules/Messenger/messenger_post.php', 'New Message_role')) {
            $selected = $this->getSelectedTargets($targets, 'Role');

            $row = $form->addRow();
                $row->addLabel('role', __('Role'))->description(__('Users of a certain type.'));
                $row->addYesNoRadio('role')->checked(!empty($selected)? 'Y' : 'N')->required();

            $form->toggleVisibilityByClass('role')->onRadio('role')->when('Y');

            // CRITERIA
            $criteria = $this->roleGateway->newQueryCriteria()
                ->sortBy(['gibbonRole.name']);

            $arrRoles = array();
            $roles = $this->roleGateway->queryRoles($criteria);

            foreach ($roles AS $role) {
                $arrRoles[$role['gibbonRoleID']] = __($role['name'])." (".__($role['category']).")";
            }
            $row = $form->addRow()->addClass('role bg-blue-100');
                $row->addLabel('roles[]', __('Select Roles'));
                $row->addSelect('roles[]')->fromArray($arrRoles)->selectMultiple()->setSize(6)->required()->placeholder()->selected($selected);

            // Role Category
            $selected = $this->getSelectedTargets($targets, 'Role Category');
            $row = $form->addRow();
                $row->addLabel('roleCategory', __('Role Category'))->description(__('Users of a certain type.'));
                $row->addYesNoRadio('roleCategory')->checked(!empty($selected)? 'Y' : 'N')->required();

            $form->toggleVisibilityByClass('roleCategory')->onRadio('roleCategory')->when('Y');

            $data = array();
            $sql = 'SELECT DISTINCT category AS value, category AS name FROM gibbonRole ORDER BY category';
            $row = $form->addRow()->addClass('roleCategory bg-blue-100');
                $row->addLabel('roleCategories[]', __('Select Role Categories'));
                $row->addSelect('roleCategories[]')->fromQuery($pdo, $sql, $data)->selectMultiple()->setSize(4)->required()->placeholder()->selected($selected);
        } else if ($sent && $values['messageWall'] == 'Y' && !empty($selectedRoleCategory) && isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_postQuickWall.php")) {
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
            $selectedByRole = [];
            $selected = $this->getSelectedTargets($targets, 'Year Group', $selectedByRole);

            $row = $form->addRow();
                $row->addLabel('yearGroup', __('Year Group'))->description(__('Students in year; staff by tutors and courses taught.'));
                $row->addYesNoRadio('yearGroup')->checked(!empty($selected)? 'Y' : 'N')->required();

            $form->toggleVisibilityByClass('yearGroup')->onRadio('yearGroup')->when('Y');

            $data = array();
            $sql = 'SELECT gibbonYearGroupID AS value, name FROM gibbonYearGroup ORDER BY sequenceNumber';
            $row = $form->addRow()->addClass('yearGroup bg-blue-100');
                $row->addLabel('yearGroups[]', __('Select Year Groups'));
                $row->addSelect('yearGroups[]')->fromQuery($pdo, $sql, $data)->selectMultiple()->setSize(6)->required()->placeholder()->selected($selected);

            $row = $form->addRow()->addClass('yearGroup bg-blue-100');
                $row->addLabel('yearGroupsStaff', __('Include Staff?'));
                $row->addYesNo('yearGroupsStaff')->selected($selectedByRole['staff']);

            $row = $form->addRow()->addClass('yearGroup bg-blue-100');
                $row->addLabel('yearGroupsStudents', __('Include Students?'));
                    $row->addYesNo('yearGroupsStudents')->selected($selectedByRole['students']);

            if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_yearGroups_parents")) {
                $row = $form->addRow()->addClass('yearGroup bg-blue-100');
                    $row->addLabel('yearGroupsParents', __('Include Parents?'));
                    $row->addYesNo('yearGroupsParents')->selected($selectedByRole['parents']);
            }
        }

        //Form group
        if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_formGroups_my") OR isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_formGroups_any")) {
            $selectedByRole = [];
            $selected = $this->getSelectedTargets($targets, 'Form Group', $selectedByRole);

            $row = $form->addRow();
                $row->addLabel('formGroup', __('Form Group'))->description(__('Tutees and tutors.'));
                $row->addYesNoRadio('formGroup')->checked(!empty($selected)? 'Y' : 'N')->required();

            $form->toggleVisibilityByClass('formGroup')->onRadio('formGroup')->when('Y');

            if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_formGroups_any")) {
                $data=array("gibbonSchoolYearID"=>$this->session->get('gibbonSchoolYearID'));
                $sql="SELECT gibbonFormGroupID AS value, name FROM gibbonFormGroup WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY name" ;
            }
            else {
                if ($this->roleCategory == "Staff") {
                    $data=array("gibbonSchoolYearID"=>$this->session->get('gibbonSchoolYearID'), "gibbonPersonID1"=>$this->session->get('gibbonPersonID'), "gibbonPersonID2"=>$this->session->get('gibbonPersonID'), "gibbonPersonID3"=>$this->session->get('gibbonPersonID'), "gibbonSchoolYearID"=>$this->session->get('gibbonSchoolYearID'));
                    $sql="SELECT gibbonFormGroupID AS value, name FROM gibbonFormGroup WHERE (gibbonPersonIDTutor=:gibbonPersonID1 OR gibbonPersonIDTutor2=:gibbonPersonID2 OR gibbonPersonIDTutor3=:gibbonPersonID3) AND gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY name" ;
                }
                else if ($this->roleCategory == "Student") {
                    $data=array("gibbonSchoolYearID"=>$this->session->get('gibbonSchoolYearID'), "gibbonPersonID"=>$this->session->get('gibbonPersonID'), );
                    $sql="SELECT gibbonFormGroupID AS value, name FROM gibbonFormGroup JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID) WHERE gibbonPersonID=:gibbonPersonID AND gibbonFormGroup.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY name" ;
                }
            }
            $row = $form->addRow()->addClass('formGroup bg-blue-100');
                $row->addLabel('formGroups[]', __('Select Form Groups'));
                $row->addSelect('formGroups[]')->fromQuery($pdo, $sql, $data)->selectMultiple()->setSize(6)->required()->placeholder()->selected($selected);

            $row = $form->addRow()->addClass('formGroup bg-blue-100');
                $row->addLabel('formGroupsStaff', __('Include Staff?'));
                $row->addYesNo('formGroupsStaff')->selected($selectedByRole['staff']);

            $row = $form->addRow()->addClass('formGroup bg-blue-100');
                $row->addLabel('formGroupsStudents', __('Include Students?'));
                $row->addYesNo('formGroupsStudents')->selected($selectedByRole['students']);

            if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_formGroups_parents")) {
                $row = $form->addRow()->addClass('formGroup bg-blue-100');
                    $row->addLabel('formGroupsParents', __('Include Parents?'));
                    $row->addYesNo('formGroupsParents')->selected($selectedByRole['parents']);
            }
        }

        // Course
        if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_courses_my") OR isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_courses_any")) {
            $selectedByRole = [];
            $selected = $this->getSelectedTargets($targets, 'Course', $selectedByRole);

            $row = $form->addRow();
                $row->addLabel('course', __('Course'))->description(__('Members of a course of study.'));
                $row->addYesNoRadio('course')->checked(!empty($selected)? 'Y' : 'N')->required();

            $form->toggleVisibilityByClass('course')->onRadio('course')->when('Y');

            if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_courses_any")) {
                $data = array('gibbonSchoolYearID' => $this->session->get('gibbonSchoolYearID'));
                $sql = "SELECT gibbonCourseID as value, nameShort as name FROM gibbonCourse WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY name";
            } else {
                $data = array('gibbonSchoolYearID' => $this->session->get('gibbonSchoolYearID'), 'gibbonPersonID' => $this->session->get('gibbonPersonID'));
                $sql = "SELECT gibbonCourse.gibbonCourseID as value, gibbonCourse.nameShort as name
                        FROM gibbonCourse
                        JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID)
                        JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID)
                        WHERE gibbonPersonID=:gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID AND NOT role LIKE '%- Left' GROUP BY gibbonCourse.gibbonCourseID ORDER BY name";
            }

            $row = $form->addRow()->addClass('course bg-blue-100');
                $row->addLabel('courses[]', __('Select Courses'));
                $row->addSelect('courses[]')->fromQuery($pdo, $sql, $data)->selectMultiple()->setSize(6)->required()->selected($selected);

            $row = $form->addRow()->addClass('course bg-blue-100');
                $row->addLabel('coursesStaff', __('Include Staff?'));
                $row->addYesNo('coursesStaff')->selected($selectedByRole['staff']);

            $row = $form->addRow()->addClass('course bg-blue-100');
                $row->addLabel('coursesStudents', __('Include Students?'));
                $row->addYesNo('coursesStudents')->selected($selectedByRole['students']);

            if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_courses_parents")) {
                $row = $form->addRow()->addClass('course bg-blue-100');
                    $row->addLabel('coursesParents', __('Include Parents?'));
                    $row->addYesNo('coursesParents')->selected($selectedByRole['parents']);
            }
        }

        // Class
        if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_classes_my") OR isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_classes_any")) {
            $selectedByRole = [];
            $selected = $this->getSelectedTargets($targets, 'Class', $selectedByRole);

            $row = $form->addRow();
                $row->addLabel('class', __('Class'))->description(__('Members of a class within a course.'));
                $row->addYesNoRadio('class')->checked(!empty($selected)? 'Y' : 'N')->required();

            $form->toggleVisibilityByClass('class')->onRadio('class')->when('Y');

            if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_classes_any")) {
                $data = array('gibbonSchoolYearID' => $this->session->get('gibbonSchoolYearID'));
                $sql = "SELECT gibbonCourseClassID as value, CONCAT(gibbonCourse.nameShort, '.', gibbonCourseClass.nameShort) as name FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY name";
            } else {
                $data = array('gibbonSchoolYearID' => $this->session->get('gibbonSchoolYearID'), 'gibbonPersonID' => $this->session->get('gibbonPersonID'));
                $sql = "SELECT gibbonCourseClass.gibbonCourseClassID as value, CONCAT(gibbonCourse.nameShort, '.', gibbonCourseClass.nameShort) as name
                    FROM gibbonCourse
                    JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID)
                    JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID)
                    WHERE gibbonPersonID=:gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID AND NOT role LIKE '%- Left' ORDER BY gibbonCourseClass.name";
            }

            $row = $form->addRow()->addClass('class bg-blue-100');
                $row->addLabel('classes[]', __('Select Classes'));
                $row->addSelect('classes[]')->fromQuery($pdo, $sql, $data)->selectMultiple()->setSize(6)->required()->selected($selected);

            $row = $form->addRow()->addClass('class bg-blue-100');
                $row->addLabel('classesStaff', __('Include Staff?'));
                $row->addYesNo('classesStaff')->selected($selectedByRole['staff']);

            $row = $form->addRow()->addClass('class bg-blue-100');
                $row->addLabel('classesStudents', __('Include Students?'));
                $row->addYesNo('classesStudents')->selected($selectedByRole['students']);

            if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_classes_parents")) {
                $row = $form->addRow()->addClass('class bg-blue-100');
                    $row->addLabel('classesParents', __('Include Parents?'));
                    $row->addYesNo('classesParents')->selected($selectedByRole['parents']);
            }
        }

        //Activities
        if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_activities_my") OR isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_activities_any")) {
            $selectedByRole = [];
            $selected = $this->getSelectedTargets($targets, 'Activity', $selectedByRole);

            $row = $form->addRow();
                $row->addLabel('activity', __('Activity'))->description(__('Members of an activity.'));
                $row->addYesNoRadio('activity')->checked(!empty($selected)? 'Y' : 'N')->required();

            $form->toggleVisibilityByClass('activity')->onRadio('activity')->when('Y');

            if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_activities_any")) {
                $data = array('gibbonSchoolYearID' => $this->session->get('gibbonSchoolYearID'));
                $sql = "SELECT gibbonActivityID as value, name FROM gibbonActivity WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND active='Y' ORDER BY name";
            } else {
                $data = array('gibbonSchoolYearID' => $this->session->get('gibbonSchoolYearID'), 'gibbonPersonID' => $this->session->get('gibbonPersonID'));
                if ($this->roleCategory == "Staff") {
                    $sql = "SELECT gibbonActivity.gibbonActivityID as value, name FROM gibbonActivity JOIN gibbonActivityStaff ON (gibbonActivityStaff.gibbonActivityID=gibbonActivity.gibbonActivityID) WHERE gibbonPersonID=:gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID AND active='Y' ORDER BY name";
                } else if ($this->roleCategory == "Student") {
                    $sql = "SELECT gibbonActivity.gibbonActivityID as value, name FROM gibbonActivity JOIN gibbonActivityStudent ON (gibbonActivityStudent.gibbonActivityID=gibbonActivity.gibbonActivityID) WHERE gibbonPersonID=:gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID AND status='Accepted' AND active='Y' ORDER BY name";
                }
            }
            $row = $form->addRow()->addClass('activity bg-blue-100');
                $row->addLabel('activities[]', __('Select Activities'));
                $row->addSelect('activities[]')->fromQuery($pdo, $sql, $data)->selectMultiple()->setSize(6)->required()->selected($selected);

            $row = $form->addRow()->addClass('activity bg-blue-100');
                $row->addLabel('activitiesStaff', __('Include Staff?'));
                $row->addYesNo('activitiesStaff')->selected($selectedByRole['staff']);

            $row = $form->addRow()->addClass('activity bg-blue-100');
                $row->addLabel('activitiesStudents', __('Include Students?'));
                $row->addYesNo('activitiesStudents')->selected($selectedByRole['students']);

            if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_activities_parents")) {
                $row = $form->addRow()->addClass('activity bg-blue-100');
                    $row->addLabel('activitiesParents', __('Include Parents?'));
                    $row->addYesNo('activitiesParents')->selected($selectedByRole['parents']);
            }
        }

        // Applicants
        if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_applicants")) {
            $selectedByRole = [];
            $selected = $this->getSelectedTargets($targets, 'Applicants', $selectedByRole);

            $row = $form->addRow();
                $row->addLabel('applicants', __('Applicants'))->description(__('Applicants from a given year.'))->description(__('Does not apply to the message wall.'));
                $row->addYesNoRadio('applicants')->checked(!empty($selected)? 'Y' : 'N')->required();

            $form->toggleVisibilityByClass('applicants')->onRadio('applicants')->when('Y');

            $sql = "SELECT gibbonSchoolYearID as value, name FROM gibbonSchoolYear ORDER BY sequenceNumber DESC";
            $row = $form->addRow()->addClass('applicants bg-blue-100');
                $row->addLabel('applicantList[]', __('Select Years'));
                $row->addSelect('applicantList[]')->fromQuery($pdo, $sql)->selectMultiple()->setSize(6)->required()->selected($selected);

            $row = $form->addRow()->addClass('applicants hiddenReveal');
                $row->addLabel('applicantsStudents', __('Include Students?'));
                $row->addYesNo('applicantsStudents')->selected($selectedByRole['students']);

            $row = $form->addRow()->addClass('applicants hiddenReveal');
                $row->addLabel('applicantsParents', __('Include Parents?'));
                $row->addYesNo('applicantsParents')->selected($selectedByRole['parents']);
        }

        // Houses
        if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_houses_all") OR isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_houses_my")) {
            $selected = $this->getSelectedTargets($targets, 'Houses');

            $row = $form->addRow();
                $row->addLabel('houses', __('Houses'))->description(__('Houses for competitions, etc.'));
                $row->addYesNoRadio('houses')->checked(!empty($selected)? 'Y' : 'N')->required();

            $form->toggleVisibilityByClass('houses')->onRadio('houses')->when('Y');

            if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_houses_all")) {
                $data = array();
                $sql = "SELECT gibbonHouseID as value, name FROM gibbonHouse ORDER BY name";
            } else if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_houses_my")) {
                $data = array('gibbonPersonID' => $this->session->get('gibbonPersonID'));
                $sql = "SELECT gibbonHouse.gibbonHouseID as value, name FROM gibbonHouse JOIN gibbonPerson ON (gibbonHouse.gibbonHouseID=gibbonPerson.gibbonHouseID) WHERE gibbonPersonID=:gibbonPersonID ORDER BY name";
            }
            $row = $form->addRow()->addClass('houses bg-blue-100');
                $row->addLabel('houseList[]', __('Select Houses'));
                $row->addSelect('houseList[]')->fromQuery($pdo, $sql, $data)->selectMultiple()->setSize(6)->required()->selected($selected);
        }

        // Transport
        if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_transport_any")) {
            $selectedByRole = [];
            $selected = $this->getSelectedTargets($targets, 'Transport', $selectedByRole);

            $row = $form->addRow();
                $row->addLabel('transport', __('Transport'))->description(__('Applies to all staff and students who have transport set.'));
                $row->addYesNoRadio('transport')->checked(!empty($selected)? 'Y' : 'N')->required();

            $form->toggleVisibilityByClass('transport')->onRadio('transport')->when('Y');

            $sql = "SELECT DISTINCT transport FROM gibbonPerson WHERE status='Full' AND NOT transport='' ORDER BY transport";
            $transportList = $pdo->select($sql)->fetchAll();
            $transportList = array_unique(array_reduce($pdo->select($sql)->fetchAll(), function ($group, $item) {
                $list = array_map('trim', explode(',', $item['transport'] ?? ''));
                $group = array_merge($group, $list);
                return $group;
            }, []));
            sort($transportList, SORT_NATURAL);

            $row = $form->addRow()->addClass('transport bg-blue-100');
                $row->addLabel('transports[]', __('Select Transport'));
                $row->addSelect('transports[]')->fromArray($transportList)->selectMultiple()->setSize(6)->required()->selected($selected);

            $row = $form->addRow()->addClass('transport bg-blue-100');
                $row->addLabel('transportStaff', __('Include Staff?'));
                $row->addYesNo('transportStaff')->selected($selectedByRole['staff']);

            $row = $form->addRow()->addClass('transport bg-blue-100');
                $row->addLabel('transportStudents', __('Include Students?'));
                $row->addYesNo('transportStudents')->selected($selectedByRole['students']);

            if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_transport_parents")) {
                $row = $form->addRow()->addClass('transport bg-blue-100');
                    $row->addLabel('transportParents', __('Include Parents?'));
                    $row->addYesNo('transportParents')->selected($selectedByRole['parents']);
            }
        }

        // Attendance Status / Absentees
        if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_attendance")) {
            $selectedByRole = [];
            $selected = $this->getSelectedTargets($targets, 'Attendance', $selectedByRole);

            $row = $form->addRow();
                $row->addLabel('attendance', __('Attendance Status'))->description(__('Students matching the given attendance status.'));
                $row->addYesNoRadio('attendance')->checked(!empty($selected)? 'Y' : 'N')->required();

            $form->toggleVisibilityByClass('attendance')->onRadio('attendance')->when('Y');

            $sql = "SELECT name, gibbonRoleIDAll FROM gibbonAttendanceCode WHERE active = 'Y' ORDER BY direction DESC, sequenceNumber ASC, name";
            $result = $pdo->select($sql);

            // Filter the attendance codes by allowed roles (if any)
            $currentRole = $this->session->get('gibbonRoleIDCurrent');
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

            $row = $form->addRow()->addClass('attendance bg-blue-100');
                $row->addLabel('attendanceStatus[]', __('Select Attendance Status'));
                $row->addSelect('attendanceStatus[]')->fromArray($attendanceCodes)->selectMultiple()->setSize(6)->required()->selected($selected);

            $row = $form->addRow()->addClass('attendance bg-blue-100');
                $row->addLabel('attendanceStudents', __('Include Students?'));
                $row->addYesNo('attendanceStudents')->selected($selectedByRole['students']);

            $row = $form->addRow()->addClass('attendance bg-blue-100');
                $row->addLabel('attendanceParents', __('Include Parents?'));
                $row->addYesNo('attendanceParents')->selected($selectedByRole['parents']);
        }

        // Group
        if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_groups_my") || isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_groups_any")) {
            $selectedByRole = [];
            $selected = $this->getSelectedTargets($targets, 'Group', $selectedByRole);

            $row = $form->addRow();
                $row->addLabel('group', __('Group'))->description(__('Members of a Messenger module group.'));
                $row->addYesNoRadio('group')->checked(!empty($selected)? 'Y' : 'N')->required();

            $form->toggleVisibilityByClass('messageGroup')->onRadio('group')->when('Y');

            if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_groups_any")) {
                $data = array('gibbonSchoolYearID' => $this->session->get('gibbonSchoolYearID'));
                $sql = "SELECT gibbonGroup.gibbonGroupID as value, gibbonGroup.name FROM gibbonGroup WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY name";
            } else {
                $data = array('gibbonSchoolYearID' => $this->session->get('gibbonSchoolYearID'), 'gibbonPersonID' => $this->session->get('gibbonPersonID'), 'gibbonSchoolYearID2' => $this->session->get('gibbonSchoolYearID'), 'gibbonPersonID2' => $this->session->get('gibbonPersonID'));
                $sql = "(SELECT gibbonGroup.gibbonGroupID as value, gibbonGroup.name FROM gibbonGroup WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonIDOwner=:gibbonPersonID ORDER BY name)
                    UNION
                    (SELECT gibbonGroup.gibbonGroupID as value, gibbonGroup.name FROM gibbonGroup JOIN gibbonGroupPerson ON (gibbonGroupPerson.gibbonGroupID=gibbonGroup.gibbonGroupID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID2 AND gibbonPersonID=:gibbonPersonID2)
                    ORDER BY name
                    ";
            }

            $row = $form->addRow()->addClass('messageGroup bg-blue-100');
                $row->addLabel('groups[]', __('Select Groups'));
                $row->addSelect('groups[]')->fromQuery($pdo, $sql, $data)->selectMultiple()->setSize(6)->required()->selected($selected);

            $row = $form->addRow()->addClass('messageGroup bg-blue-100');
                $row->addLabel('groupsStaff', __('Include Staff?'));
                $row->addYesNo('groupsStaff')->selected($selectedByRole['staff']);

            $row = $form->addRow()->addClass('messageGroup bg-blue-100');
                $row->addLabel('groupsStudents', __('Include Students?'));
                $row->addYesNo('groupsStudents')->selected($selectedByRole['students']);

            if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_groups_parents")) {
                $row = $form->addRow()->addClass('messageGroup bg-blue-100');
                    $row->addLabel('groupsParents', __('Include Parents?'))->description('Parents who are members, and parents of student members.');
                    $row->addYesNo('groupsParents')->selected($selectedByRole['parents']);
            }
        }

        // Individuals
        if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_individuals")) {
            $selected = $this->getSelectedTargets($targets, 'Individuals');

            $row = $form->addRow();
                $row->addLabel('individuals', __('Individuals'))->description(__('Individuals from the whole school.'));
                $row->addYesNoRadio('individuals')->checked(!empty($selected)? 'Y' : 'N')->required();

            $form->toggleVisibilityByClass('individuals')->onRadio('individuals')->when('Y');

            // Build a set of individuals by ID => formatted name
            $data = ['gibbonSchoolYearID' => $this->session->get('gibbonSchoolYearID')];
            $sql = "SELECT gibbonPerson.gibbonPersonID, preferredName, surname, username, gibbonFormGroup.name AS formGroupName, gibbonRole.category
                    FROM gibbonPerson
                    JOIN gibbonRole ON (gibbonRole.gibbonRoleID=gibbonPerson.gibbonRoleIDPrimary)
                    LEFT JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID)
                    LEFT JOIN gibbonFormGroup ON (gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID)
                    WHERE gibbonPerson.status='Full'
                    ORDER BY surname, preferredName";

            $individuals = $pdo->select($sql, $data)->fetchAll();
            $individuals = array_reduce($individuals, function($group, $item){
                $name = Format::name("", $item['preferredName'], $item['surname'], 'Student', true).' (';
                if (!empty($item['formGroupName'])) $name .= $item['formGroupName'].', ';
                $group[$item['gibbonPersonID']] = $name.$item['username'].', '.__($item['category']).')';
                return $group;
            }, array());
            $selectedIndividuals = array_intersect_key($individuals, array_flip($selected));

            $row = $form->addRow()->addClass('individuals bg-blue-100');
                $col = $row->addColumn();
                $col->addLabel('individualList', __('Select Individuals'));
                $select = $col->addMultiSelect('individualList')->required();
                $select->source()->fromArray($individuals);
                $select->destination()->fromArray($selectedIndividuals);
        }

        if (!empty($values)) {
            $form->loadAllValuesFrom($values);
        }

        if ($sent) {
            $row = $form->addRow();
                $row->addFooter();
                $row->addSubmit();
        } else {
            // Preflight!
            $form->addRow()->addClass('email')->addHeading('Preflight', __('Preflight'))->append(__("Before sending your message you'll have the option to preview the message as well as view a list of the recipients, based on your targets selected above. You can also choose to save your message as a draft and return to it later."));

            $row = $form->addRow()->addClass('email');
                $row->addCheckbox('sendTestEmail')->description(__('Send a test copy to {email}', ['email' => '<u>'.$this->session->get('email').'</u>']))->setValue('Y');

            $form->toggleVisibilityByClass('noEmail')->onRadio('email')->when('N');

            $row = $form->addRow('stickySubmit');
                $col = $row->addColumn()->addClass('items-center');
                    $col->addButton(__('Save Draft'))->onClick('saveDraft()')->addClass('rounded-sm w-auto mr-2');
                $col = $row->addColumn()->addClass('items-center');
                    $col->addSubmit(__('Preview & Send'))->addClass('email');
                    $col->addSubmit()->addClass('noEmail');
        }

        return $form;
    }

    private function getSelectedTargets(array $targets, string $type, array &$selectedByRole = [])
    {
        $selectedByRole = ['staff' => $this->defaultSendStaff, 'students' => $this->defaultSendStudents, 'parents' => $this->defaultSendParents];
        if (empty($targets)) return [];

        return array_reduce($targets, function($group, $item) use (&$type, &$selectedByRole) {
            if ($item['type'] == $type) {
                $group[] = $item['id'];
                $selectedByRole['staff'] = $item['staff'] ?? $this->defaultSendStaff;
                $selectedByRole['students'] = $item['students'] ?? $this->defaultSendStudents;
                $selectedByRole['parents'] = $item['parents'] ?? $this->defaultSendParents;
            }
            return $group;
        }, []);
    }

    private function getCannedResponseJS(array $cannedResponses = [])
    {
        // Set up JS to deal with canned response selection
        echo "<script type=\"text/javascript\">" ;
        echo "$(document).ready(function(){" ;
            echo "$(\"#cannedResponse\").change(function(){" ;
                echo "if (confirm(\"Are you sure you want to insert these records.\")==1) {" ;
                    echo "if ($('#cannedResponse').val()==\"\" ) {" ;
                        echo "$('#subject').val('');" ;
                        echo "tinyMCE.execCommand('mceRemoveEditor', false, 'body') ;" ;
                        echo "$('#body').val('');" ;
                        echo "tinyMCE.execCommand('mceAddEditor', false, 'body') ;" ;
                    echo "}" ;
                    foreach ($cannedResponses AS $rowSelect) {
                        echo "if ($('#cannedResponse').val()==\"" . $rowSelect["gibbonMessengerCannedResponseID"] . "\" ) {" ;
                            echo "$('#subject').val('" . htmlPrep($rowSelect["subject"]) . "');" ;
                            echo "tinyMCE.execCommand('mceRemoveEditor', false, 'body') ;" ;
                            echo "
                                $.get('./modules/Messenger/messenger_post_ajax.php?gibbonMessengerCannedResponseID=" . $rowSelect["gibbonMessengerCannedResponseID"] . "', function(response) {
                                     var result = response;
                                    $('#body').val(result);
                                    tinyMCE.execCommand('mceAddEditor', false, 'body') ;
                                });
                            " ;
                        echo "}" ;
                    }
                    echo "}" ;
                    echo "else {" ;
                        echo "$('#cannedResponse').val('')" ;
                    echo "}" ;
                echo "});" ;
            echo "});" ;
        echo "</script>" ;
    }
}
