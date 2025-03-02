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
use Gibbon\Tables\DataTable;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\IndividualNeeds\INGateway;
use Gibbon\Module\FreeLearning\Domain\UnitGateway;
use Gibbon\Module\FreeLearning\Domain\UnitStudentGateway;

//Module includes
include "./modules/" . $session->get('module') . "/moduleFunctions.php" ;

if (isActionAccessible($guid, $connection2, "/modules/Free Learning/report_workPendingApproval.php")==FALSE) {
    //Acess denied
    $page->addError(__('You do not have access to this action.'));
}
else {
    //Proceed!
    $page->breadcrumbs->add(__m('Work Pending Approval'));

    $highestAction = getHighestGroupedAction($guid, '/modules/Free Learning/report_workPendingApproval.php', $connection2);
    if (empty($highestAction)) {
        $page->addError(__('You do not have access to this action.'));
        return;
    }

    //Check for custom field
    $customField = $container->get(SettingGateway::class)->getSettingByScope('Free Learning', 'customField');

    print "<p>" ;
        print __m('This report shows all work that is complete, but pending approval, in all of your classes.') ;
    print "<p>" ;

    //Filter
    $allMentors = (isset($_GET['allMentors']) && $highestAction == 'Work Pending Approval_all') ? $_GET['allMentors'] : '';
    $search = $_GET['search'] ?? '';
    $gibbonCourseClassID = $_GET['gibbonCourseClassID'] ?? null;

    $form = Form::create('search', $session->get('absoluteURL').'/index.php', 'get');
    $form->setFactory(DatabaseFormFactory::create($pdo));
    $form->setTitle(__('Filter'));
    $form->setClass('noIntBorder w-full');

    $form->addHiddenValue('q', '/modules/'.$session->get('module').'/report_workPendingApproval.php');

    $row = $form->addRow();
    $row->addLabel('gibbonCourseClassID', __('Class'));
    $row->addSelectClass('gibbonCourseClassID', $session->get('gibbonSchoolYearID'), $session->get('gibbonPersonID'), ["allClasses" => ($highestAction == 'Work Pending Approval_all')])
        ->selected($gibbonCourseClassID)
        ->placeholder();

    if ($highestAction == 'Work Pending Approval_all') {
        $row = $form->addRow();
            $row->addLabel('allMentors', __('All Mentors'))->description(__('Include evidence pending for all mentors.'));
            $row->addCheckbox('allMentors')->setValue('on')->checked($allMentors);
    }

    $row = $form->addRow();
        $row->addSearchSubmit($session, __('Clear Search'));

    echo $form->getOutput();

    //Table
    $unitGateway = $container->get(UnitGateway::class);
    $unitStudentGateway = $container->get(UnitStudentGateway::class);

    $criteria = $unitStudentGateway->newQueryCriteria()
        ->sortBy(['timestampCompletePending'])
        ->pageSize(50)
        ->fromPOST();

    if (!empty($allMentors)) {
        $journey = $unitStudentGateway->queryEvidencePending($criteria, $session->get('gibbonSchoolYearID'), null, $gibbonCourseClassID);
    }
    else {
        $journey = $unitStudentGateway->queryEvidencePending($criteria, $session->get('gibbonSchoolYearID'), $session->get('gibbonPersonID'), $gibbonCourseClassID);
    }

    $manageAll = isActionAccessible($guid, $connection2, '/modules/Free Learning/units_manage.php', 'Manage Units_all');
    $collaborationKeys = [];

    // Get list of my classes before we start looping, for efficiency's sake
    $myClasses = $unitGateway->selectRelevantClassesByTeacher($session->get('gibbonSchoolYearID'), $session->get('gibbonPersonID'))->fetchAll(PDO::FETCH_COLUMN, 0);
    // Render table
    $table = DataTable::createPaginated('pending', $criteria);

    $table->setTitle(__('Data'));

    $table->modifyRows(function ($journey, $row) {
        $row->addClass('pending');
        return $row;
    });

    $table->addColumn('enrolmentMethod', __m('Enrolment Method'))
        ->notSortable()
        ->format(function($values) {
            return ucwords(preg_replace('/(?<=\\w)(?=[A-Z])/'," $1", $values["enrolmentMethod"])).'<br/>';
        });

    $table->addColumn('class', __m('Class/Mentor'))
        ->sortable(['course', 'class'])
        ->format(function($values) use (&$collaborationKeys, $highestAction) {
            $output = '';
            if ($values['enrolmentMethod'] == 'class') {
                if ($values['course'] != '' and $values['class'] != '') {
                    $output .= $values['course'].'.'.$values['class'];
                } else {
                    $output .= '<i>'.__('N/A').'</i>';
                }

                if (!empty($values['teacherNames']) && $highestAction == 'Work Pending Approval_all') {
                    $output .= "<br/>".Format::small($values['teacherNames']);
                }
            }
            else if ($values['enrolmentMethod'] == 'schoolMentor') {
                $output .= Format::name('', $values['mentorpreferredName'], $values['mentorsurname'], 'Student', false);
            }
            else if ($values['enrolmentMethod'] == 'externalMentor') {
                $output .= $values['nameExternalMentor'];
            }

            return $output;
        });

    $table->addColumn('grouping', __m('Grouping'))
        ->format(function($values) use (&$collaborationKeys) {
            $output = '';
            $grouping = $values['grouping'];
            if ($values['collaborationKey'] != '') {
                // Get the index for the group, otherwise add it to the array
                $group = array_search($values['collaborationKey'], $collaborationKeys);
                if ($group === false) {
                    $collaborationKeys[] = $values['collaborationKey'];
                    $group = count($collaborationKeys);
                } else {
                    $group++;
                }
                $grouping .= " (".__m("Group")." ".$group.")";
            }
            $output .= $grouping;

            return $output;
        });

    $table->addColumn('unit', __m('Unit'))
        ->description(__m('Learning Area')."/".__m('Course'))
        ->format(function($values) use ($session) {
             $output = "<a href='" . $session->get("absoluteURL") . "/index.php?q=/modules/Free Learning/units_browse_details.php&freeLearningUnitID=" . $values["freeLearningUnitID"] . "&tab=2&sidebar=true'>" . $values["unit"] . "</a><br/>" ;
             $output .= !empty($values['learningArea']) ? '<div class="text-xxs">'.$values['learningArea'].'</div>' : '';
             $output .= !empty($values['flCourse']) && ($values['learningArea'] != $values['flCourse']) ? '<div class="text-xxs">'.$values['flCourse'].'</div>' : '';
             return $output;
        });

    $table->addColumn('student', __('Student'))
        ->sortable('gibbonPersonID')
        ->format(function($values) use ($container, $connection2, $guid, $customField) {
            // Name
            $output = "";
            if ($values['category'] == 'Student') {
                $output .= "<a href='index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=" . $values["gibbonPersonID"] . "'>" . Format::name("", $values["studentpreferredName"], $values["studentsurname"], "Student", true) . "</a>";
            }
            else {
                $output .= Format::name("", $values["studentpreferredName"], $values["studentsurname"], "Student", true);
            }
            $output .= "<br/>";
            // Custom fields
            $fields = json_decode($values['fields'], true);
            if (!empty($fields[$customField])) {
                $value = $fields[$customField];
                if ($value != '') {
                    $output .= Format::small($value);
                }
            }

            //Individual Needs
            if (isActionAccessible($guid, $connection2, "/modules/Individual Needs/in_view.php")) {
                $gateway = $container->get(INGateway::class);
                $criteria = $gateway
                  ->newQueryCriteria()
                  ->filterBy('gibbonPersonID', $values['gibbonPersonID']);
                $personalDescriptors = $gateway->queryIndividualNeedsPersonDescriptors($criteria)->toArray();

                if (count($personalDescriptors) > 0) {
                    $output .= "<br/>".Format::tag(__('Individual Needs'), 'message mt-1');
                }

            }

            return $output;
        });

    $table->addColumn('status', __m('Status'));

    $table->addColumn('submissions', __m('Submissions'))
        ->notSortable();

    $table->addColumn('timestampCompletePending', __('When'))->format(Format::using('relativeTime', 'timestampCompletePending'));

    // ACTIONS
    $table->addActionColumn()
        ->addParam('freeLearningUnitStudentID')
        ->addParam('freeLearningUnitID')
        ->addParam('sidebar', true)
        ->format(function ($student, $actions) use ($manageAll, $myClasses, $session) {
            // Check to see if we can edit this class's enrolment (e.g. we have $manageAll or this is one of our classes or we are the mentor)
            $editEnrolment = $manageAll ? true : false;
            if ($student['enrolmentMethod'] == 'class') {
                // Is teacher of this class?
                if (in_array($student['gibbonCourseClassID'], $myClasses)) {
                    $editEnrolment = true;
                }
            } elseif ($student['enrolmentMethod'] == 'schoolMentor' && $student['gibbonPersonIDSchoolMentor'] == $session->get('gibbonPersonID')) {
                // Is mentor of this student?
                $editEnrolment = true;
            }

            if (!$editEnrolment) return;

            if ($editEnrolment && ($student['status'] == 'Complete - Pending' or $student['status'] == 'Complete - Approved' or $student['status'] == 'Evidence Not Yet Approved')) {
                $actions->addAction('edit', __('Edit'))
                    ->setURL('/modules/Free Learning/units_browse_details_approval.php');
            }
        });

    echo $table->render($journey);
}
?>
