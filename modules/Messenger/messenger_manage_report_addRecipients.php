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

use Gibbon\Forms\Form;
use Gibbon\Services\Format;
use Gibbon\Domain\User\RoleGateway;
use Gibbon\Domain\User\UserGateway;
use Gibbon\Domain\School\HouseGateway;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\Messenger\GroupGateway;
use Gibbon\Domain\School\YearGroupGateway;
use Gibbon\Domain\Timetable\CourseGateway;
use Gibbon\Domain\Activities\ActivityGateway;
use Gibbon\Domain\Messenger\MessengerGateway;
use Gibbon\Domain\FormGroups\FormGroupGateway;
use Gibbon\Domain\Messenger\MailingListGateway;
use Gibbon\Domain\Attendance\AttendanceCodeGateway;

require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_manage_report.php")==FALSE) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
}
else {
    $highestAction = getHighestGroupedAction($guid, '/modules/Messenger/messenger_manage_report.php', $connection2);
    if ($highestAction == false) {
        $page->addError(__('The highest grouped action cannot be determined.'));
        return;
    }

    if (!$session->has('email')) {
        $page->addError(__('You do not have a personal email address set in Gibbon, and so cannot send out emails.'));
        return;
    }

    $gibbonMessengerID = $_GET['gibbonMessengerID'] ?? null;
    
    $page->breadcrumbs
        ->add(__('Manage Messages'), 'messenger_manage.php')
        ->add(__('View Send Report'), 'messenger_manage_report.php&gibbonMessengerID='.$gibbonMessengerID.'&sidebar=true')
        ->add(__('Add Recipients'));

    $page->return->addReturns([
        'error5' => __('Your request failed due to an error.'),
    ]);

    // Proceed!
    $settingGateway = $container->get(SettingGateway::class);
    $messengerGateway = $container->get(MessengerGateway::class);
    
    $page->addWarning(sprintf(__('While adding new recipients, ensure each family in Gibbon must have one parent who is contact priority 1, and who must be enabled to receive email and SMS messages from %1$s.'), $session->get('organisationNameShort')));

    // Get the existing message data, if any
    $message = !empty($gibbonMessengerID) ? $messengerGateway->getByID($gibbonMessengerID) : [];
    $sent = !empty($message) && $message['status'] == 'Sent';

    // FORM
    $form = Form::create('addRecipients', $session->get('absoluteURL').'/modules/Messenger/messenger_manage_report_addRecipientsProcess.php');
    $form->addHiddenValue('address', $session->get('address'));
    $form->addHiddenValue('gibbonMessengerID', $gibbonMessengerID ?? '');

    $form->addRow()->addHeading('Add New Recipients', __('Add New Recipients'))
         ->append(__("Select new recipients to add to this message."));

    // Individuals
    $row = $form->addRow();
        $row->addLabel('individuals', __('Individuals'))->description(__('Select specific individuals from the whole school.'));
        $row->addYesNoRadio('individuals')->checked('N')->required();

    $form->toggleVisibilityByClass('individuals')->onRadio('individuals')->when('Y');

    $userGateway = $container->get(UserGateway::class);
    $individuals = $userGateway->getIndividualsBySchoolYearWithFullStatus($session->get('gibbonSchoolYearID'))->fetchAll();

    $individuals = array_reduce($individuals, function ($group, $item) {
        $name = Format::name("", $item['preferredName'], $item['surname'], 'Student', true).' (';
        if (!empty($item['formGroupName'])) $name .= $item['formGroupName'].', ';
        $group[$item['gibbonPersonID']] = $name.$item['username'].', '.__($item['category']).')';
        return $group;
    }, []);

    $selected = [];
    $selectedIndividuals = array_intersect_key($individuals, array_flip($selected));

    $row = $form->addRow()->addClass('individuals bg-blue-50');
        $col = $row->addColumn();
        $col->addLabel('individualList', __('Select Individuals'));
        $select = $col->addMultiSelect('individualList')->required();
        $select->source()->fromArray($individuals);
        $select->destination()->fromArray($selectedIndividuals);
    
    // Role
    $row = $form->addRow();
        $row->addLabel('role', __('Role'))->description(__('Users of a certain type.'));
        $row->addYesNoRadio('role')->checked('N')->required();

    $form->toggleVisibilityByClass('role')->onRadio('role')->when('Y');

    $criteria = $container->get(RoleGateway::class)->newQueryCriteria()->sortBy(['gibbonRole.name']);
    $roles = $container->get(RoleGateway::class)->queryRoles($criteria);
    $arrRoles = [];

    foreach ($roles as $role) {
        $arrRoles[$role['gibbonRoleID']] = __($role['name'])." (".__($role['category']).")";
    }
    $row = $form->addRow()->addClass('role bg-blue-50');
        $row->addLabel('roles[]', __('Select Roles'));
        $row->addSelect('roles[]')->fromArray($arrRoles)->selectMultiple()->setSize(6)->required();

    // Year Groups
    $row = $form->addRow();
        $row->addLabel('yearGroup', __('Year Group'))->description(__('Students in year; staff by tutors and courses taught.'));
        $row->addYesNoRadio('yearGroup')->checked('N')->required();

    $form->toggleVisibilityByClass('yearGroup')->onRadio('yearGroup')->when('Y');

    $yearGroupResults = $container->get(YearGroupGateway::class)->selectYearGroups();

    $row = $form->addRow()->addClass('yearGroup bg-blue-50');
        $row->addLabel('yearGroups[]', __('Select Year Groups'));
        $row->addSelect('yearGroups[]')->fromResults($yearGroupResults)->selectMultiple()->setSize(6)->required();

    // Include Staff, Students, and Parents for Year Groups
    $row = $form->addRow()->addClass('yearGroup bg-blue-50');
        $row->addLabel('yearGroupsStaff', __('Include Staff?'));
        $row->addYesNo('yearGroupsStaff')->selected('N');

    $row = $form->addRow()->addClass('yearGroup bg-blue-50');
        $row->addLabel('yearGroupsStudents', __('Include Students?'));
        $row->addYesNo('yearGroupsStudents')->selected('N');

    $row = $form->addRow()->addClass('yearGroup bg-blue-50');
        $row->addLabel('yearGroupsParents', __('Include Parents?'));
        $row->addYesNo('yearGroupsParents')->selected('N');

    // Form Groups
    $row = $form->addRow();
        $row->addLabel('formGroup', __('Form Group'))->description(__('Tutees and tutors.'));
        $row->addYesNoRadio('formGroup')->checked('N')->required();

    $form->toggleVisibilityByClass('formGroup')->onRadio('formGroup')->when('Y');

    $formGroupResults = $container->get(FormGroupGateway::class)->selectAllFormGroupsBySchoolYear($session->get('gibbonSchoolYearID'));

    $row = $form->addRow()->addClass('formGroup bg-blue-50');
        $row->addLabel('formGroups[]', __('Select Form Groups'));
        $row->addSelect('formGroups[]')->fromResults($formGroupResults)->selectMultiple()->setSize(6)->required();

    // Include Staff, Students, and Parents for Form Groups
    $row = $form->addRow()->addClass('formGroup bg-blue-50');
        $row->addLabel('formGroupsStaff', __('Include Staff?'));
        $row->addYesNo('formGroupsStaff')->selected('N');

    $row = $form->addRow()->addClass('formGroup bg-blue-50');
        $row->addLabel('formGroupsStudents', __('Include Students?'));
        $row->addYesNo('formGroupsStudents')->selected('N');

    $row = $form->addRow()->addClass('formGroup bg-blue-50');
        $row->addLabel('formGroupsParents', __('Include Parents?'));
        $row->addYesNo('formGroupsParents')->selected('N');

    // Course
    $selectedByRole = [];

    $row = $form->addRow();
        $row->addLabel('course', __('Course'))->description(__('Members of a course of study.'));
        $row->addYesNoRadio('course')->checked('N')->required();

    $form->toggleVisibilityByClass('course')->onRadio('course')->when('Y');

    if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_courses_any")) {
        $courseResults = $container->get(CourseGateway::class)->selectAllCoursesBySchoolYear($session->get('gibbonSchoolYearID'));
    } else {
        $courseResults = $container->get(CourseGateway::class)->selectAllCoursesBySchoolYearAndPersonID($session->get('gibbonSchoolYearID'), $session->get('gibbonPersonID'));
    }

    $row = $form->addRow()->addClass('course bg-blue-50');
        $row->addLabel('courses[]', __('Select Courses'));
        $row->addSelect('courses[]')->fromResults($courseResults)->selectMultiple()->setSize(6)->required();

    // Include Staff, Students, and Parents for Form Group
    $row = $form->addRow()->addClass('course bg-blue-50');
        $row->addLabel('coursesStaff', __('Include Staff?'));
        $row->addYesNo('coursesStaff')->selected('N');

    $row = $form->addRow()->addClass('course bg-blue-50');
        $row->addLabel('coursesStudents', __('Include Students?'));
        $row->addYesNo('coursesStudents')->selected('N');

         
    $row = $form->addRow()->addClass('course bg-blue-50');
        $row->addLabel('coursesParents', __('Include Parents?'));
        $row->addYesNo('coursesParents')->selected('N');

    // Class
    $row = $form->addRow();
        $row->addLabel('class', __('Class'))->description(__('Members of a class within a course.'));
        $row->addYesNoRadio('class')->checked('N')->required();

    $form->toggleVisibilityByClass('class')->onRadio('class')->when('Y');

    if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_classes_any")) {
        $classResults = $container->get(CourseGateway::class)->selectClassIDByCourseAndSchoolYear($session->get('gibbonSchoolYearID'));
    } else {
        $classResults = $container->get(CourseGateway::class)->selectClassIDByCourseAndSchoolYearAndPerson($session->get('gibbonSchoolYearID'), $session->get('gibbonPersonID'));
    }

    $row = $form->addRow()->addClass('class bg-blue-50');
        $row->addLabel('classes[]', __('Select Classes'));
        $row->addSelect('classes[]')->fromResults($classResults)->selectMultiple()->setSize(6)->required();
  
    // Include Staff, Students, and Parents for Class
    $row = $form->addRow()->addClass('class bg-blue-50');
        $row->addLabel('classesStaff', __('Include Staff?'));
        $row->addYesNo('classesStaff')->selected('N');

    $row = $form->addRow()->addClass('class bg-blue-50');
        $row->addLabel('classesStudents', __('Include Students?'));
        $row->addYesNo('classesStudents')->selected('N');
 
    $row = $form->addRow()->addClass('class bg-blue-50');
        $row->addLabel('classesParents', __('Include Parents?'));
        $row->addYesNo('classesParents')->selected('N');

    // Groups
    $row = $form->addRow();
        $row->addLabel('group', __('Group'))->description(__('Members of a Messenger module group.'));
        $row->addYesNoRadio('group')->checked('N')->required();

    $form->toggleVisibilityByClass('messageGroup')->onRadio('group')->when('Y');

    if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_groups_any")) {
        $groupResults = $container->get(GroupGateway::class)->selectAllGroupsBySchoolYear($session->get('gibbonSchoolYearID'));
    } else {
        $groupResults = $container->get(GroupGateway::class)->selectGroupsByPersonAndOwner($session->get('gibbonSchoolYearID'), $session->get('gibbonPersonID'));
    }

    $row = $form->addRow()->addClass('messageGroup bg-blue-50');
        $row->addLabel('groups[]', __('Select Groups'));
        $row->addSelect('groups[]')->fromResults($groupResults)->selectMultiple()->setSize(6)->required();
    
    // Include Staff, Students, and Parents for Groups
    $row = $form->addRow()->addClass('messageGroup bg-blue-50');
        $row->addLabel('groupsStaff', __('Include Staff?'));
        $row->addYesNo('groupsStaff')->selected('N');

    $row = $form->addRow()->addClass('messageGroup bg-blue-50');
        $row->addLabel('groupsStudents', __('Include Students?'));
        $row->addYesNo('groupsStudents')->selected('N');

    $row = $form->addRow()->addClass('messageGroup bg-blue-50');
        $row->addLabel('groupsParents', __('Include Parents?'))->description('Parents who are members, and parents of student members.');
        $row->addYesNo('groupsParents')->selected('N');

    // Activities
    $row = $form->addRow();
        $row->addLabel('activity', __('Activity'))->description(__('Members of an activity.'));
        $row->addYesNoRadio('activity')->checked('N')->required();

    $form->toggleVisibilityByClass('activity')->onRadio('activity')->when('Y');

    if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_activities_any")) {
        $activitiesResults = $container->get(ActivityGateway::class)->selectAllActivitiesBySchoolYear($session->get('gibbonSchoolYearID'));
    } else {
        $data = ['gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'gibbonPersonID' => $session->get('gibbonPersonID')];
        if ($session->get('gibbonRoleIDCurrentCategory') == "Staff") {
            $activitiesResults = $container->get(ActivityGateway::class)->selectAllStaffActivitiesBySchoolYearAndPerson($session->get('gibbonSchoolYearID'), $session->get('gibbonPersonID'));
        } else if ($session->get('gibbonRoleIDCurrentCategory') == "Student") {
            $activitiesResults = $container->get(ActivityGateway::class)->selectAllStudentActivitiesBySchoolYearAndPerson($session->get('gibbonSchoolYearID'), $session->get('gibbonPersonID'));
        }
    }

    $row = $form->addRow()->addClass('activity bg-blue-50');
        $row->addLabel('activities[]', __('Select Activities'));
        $row->addSelect('activities[]')->fromResults($activitiesResults)->selectMultiple()->setSize(6)->required();

    // Include Staff, Students, and Parents for Activities
    $row = $form->addRow()->addClass('activity bg-blue-50');
        $row->addLabel('activitiesStaff', __('Include Staff?'));
        $row->addYesNo('activitiesStaff')->selected('N');

    $row = $form->addRow()->addClass('activity bg-blue-50');
        $row->addLabel('activitiesStudents', __('Include Students?'));
        $row->addYesNo('activitiesStudents')->selected('N');
            
    $row = $form->addRow()->addClass('activity bg-blue-50');
        $row->addLabel('activitiesParents', __('Include Parents?'));
        $row->addYesNo('activitiesParents')->selected('N');
    
    // Applicants
    $row = $form->addRow();
        $row->addLabel('applicants', __('Applicants'))->description(__('Applicants from a given year.'))->description(__('Only for resending emails or messages.'));
        $row->addYesNoRadio('applicants')->checked('N')->required();

    $form->toggleVisibilityByClass('applicants')->onRadio('applicants')->when('Y');

    $applicantResults = $container->get(YearGroupGateway::class)->selectYearsGroupsInDesc();

    $row = $form->addRow()->addClass('applicants bg-blue-50');
        $row->addLabel('applicantList[]', __('Select Years'));
        $row->addSelect('applicantList[]')->fromResults($applicantResults)->selectMultiple()->setSize(6)->required();

    // Include Students and Parents for Applicants
    $row = $form->addRow()->addClass('applicants hiddenReveal');
        $row->addLabel('applicantsStudents', __('Include Students?'));
        $row->addYesNo('applicantsStudents')->selected('N');

    $row = $form->addRow()->addClass('applicants hiddenReveal');
        $row->addLabel('applicantsParents', __('Include Parents?'));
        $row->addYesNo('applicantsParents')->selected('N');
    
    // Houses
    $row = $form->addRow();
        $row->addLabel('houses', __('Houses'))->description(__('Houses for competitions, etc.'));
        $row->addYesNoRadio('houses')->checked('N')->required();

    $form->toggleVisibilityByClass('houses')->onRadio('houses')->when('Y');

    if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_houses_all")) {
        $houseResults = $container->get(HouseGateway::class)->selectAllHousesByName();
    } else if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_houses_my")) {
        $houseResults = $container->get(HouseGateway::class)->selectHousesByPersonID($session->get('gibbonPersonID'));
    }

    $row = $form->addRow()->addClass('houses bg-blue-50');
        $row->addLabel('houseList[]', __('Select Houses'));
        $row->addSelect('houseList[]')->fromResults($houseResults)->selectMultiple()->setSize(6)->required();

    // Transport
    $row = $form->addRow();
        $row->addLabel('transport', __('Transport'))->description(__('Applies to all staff and students who have transport set.'));
        $row->addYesNoRadio('transport')->checked('N')->required();

    $form->toggleVisibilityByClass('transport')->onRadio('transport')->when('Y');

    $transportList = $userGateway->getDistinctTransportOptions()->fetchAll();
    $transportList = array_unique(array_reduce($transportList, function ($group, $item) {
        $list = array_map('trim', explode(',', $item['transport'] ?? ''));
        $group = array_merge($group, $list);
        return $group;
    }, []));
    sort($transportList, SORT_NATURAL);

    // Include Staff, Students, and Parents for Transport
    $row = $form->addRow()->addClass('transport bg-blue-50');
        $row->addLabel('transports[]', __('Select Transport'));
        $row->addSelect('transports[]')->fromArray($transportList)->selectMultiple()->setSize(6)->required();

    $row = $form->addRow()->addClass('transport bg-blue-50');
        $row->addLabel('transportStaff', __('Include Staff?'));
        $row->addYesNo('transportStaff')->selected('N');

    $row = $form->addRow()->addClass('transport bg-blue-50');
        $row->addLabel('transportStudents', __('Include Students?'));
        $row->addYesNo('transportStudents')->selected('N');

    $row = $form->addRow()->addClass('transport bg-blue-50');
        $row->addLabel('transportParents', __('Include Parents?'));
        $row->addYesNo('transportParents')->selected('N');


    // Attendance Status / Absentees
    $row = $form->addRow();
        $row->addLabel('attendance', __('Attendance Status'))->description(__('Students matching the given attendance status.'));
        $row->addYesNoRadio('attendance')->checked('N')->required();

    $form->toggleVisibilityByClass('attendance')->onRadio('attendance')->when('Y');

    $attendanceCodes = $container->get(AttendanceCodeGateway::class)->getActiveAttendanceCodes()->fetchAll();

    // Filter the attendance codes by allowed roles (if any)
    $currentRole = $session->get('gibbonRoleIDCurrent');
    $attendanceCodes = array_filter($attendanceCodes, function($item) use ($currentRole) {
        if (!empty($item['gibbonRoleIDAll'])) {
            $rolesAllowed = array_map('trim', explode(',', $item['gibbonRoleIDAll']));
            return in_array($currentRole, $rolesAllowed);
        } else {
            return true;
        }
    });
    $attendanceCodes = array_column($attendanceCodes, 'name');

    $row = $form->addRow()->addClass('attendance bg-blue-50');
        $row->addLabel('attendanceStatus[]', __('Select Attendance Status'));
        $row->addSelect('attendanceStatus[]')->fromArray($attendanceCodes)->selectMultiple()->setSize(6)->required();

    // Include Students and Parents for Attendance
    $row = $form->addRow()->addClass('attendance bg-blue-50');
        $row->addLabel('attendanceStudents', __('Include Students?'));
        $row->addYesNo('attendanceStudents')->selected('N');

    $row = $form->addRow()->addClass('attendance bg-blue-50');
        $row->addLabel('attendanceParents', __('Include Parents?'));
        $row->addYesNo('attendanceParents')->selected('N');

    // Mailing Lists
    $row = $form->addRow();
        $row->addLabel('mailingList', __('Mailing List'))->description(__('Members of a Messenger module mailing list.'));
        $row->addYesNoRadio('mailingList')->checked('N')->required();

    $form->toggleVisibilityByClass('messageMailingList')->onRadio('mailingList')->when('Y');

    $mailingListResults = $container->get(MailingListGateway::class)->selectActiveMailingList();

    $row = $form->addRow()->addClass('messageMailingList bg-blue-100');
        $row->addLabel('mailingLists[]', __('Select Mailing Lists'));
        $row->addSelect('mailingLists[]')->fromResults($mailingListResults)->selectMultiple()->setSize(6)->required();

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();
    
    echo $form->getOutput();
}
?>