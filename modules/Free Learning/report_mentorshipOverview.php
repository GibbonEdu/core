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
use Gibbon\UI\Chart\Chart;
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Forms\Prefab\BulkActionForm;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Module\FreeLearning\Domain\UnitGateway;
use Gibbon\Module\FreeLearning\Domain\UnitStudentGateway;

// Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, "/modules/Free Learning/report_mentorshipOverview.php")==FALSE) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs->add(__m('Mentorship Overview'));

    $settingGateway = $container->get(SettingGateway::class);
    $bigDataSchool = $settingGateway->getSettingByScope('Free Learning', 'bigDataSchool');

    $highestAction = getHighestGroupedAction($guid, '/modules/Free Learning/report_mentorshipOverview.php', $connection2);
    if (empty($highestAction)) {
        $page->addError(__('You do not have access to this action.'));
        return;
    }

    // Filter
    $allMentors = !empty($_GET['allMentors']) && $highestAction == 'Mentorship Overview_all'
        ? $_GET['allMentors']
        : '';

    $allStudents = $_GET['allStudents'] ?? null;

    if ($highestAction == 'Mentorship Overview_all' && $allMentors == "on") {
        $gibbonPersonID = null;
    }
    else if ($highestAction == 'Mentorship Overview_all' && !empty($_GET['gibbonPersonIDSchoolMentor'])) {
        $gibbonPersonID = $_GET['gibbonPersonIDSchoolMentor'];
    }
    else {
        $gibbonPersonID = $session->get('gibbonPersonID');
    }

    $gibbonSchoolYearID = $_GET['gibbonSchoolYearID'] ?? $session->get('gibbonSchoolYearID');
    $customField = $settingGateway->getSettingByScope('Free Learning', 'customField');
    $dateStart = $_GET['dateStart'] ?? (($bigDataSchool == "Y") ? Format::date(date('Y-m-d', strtotime(' - 1 months'))) : null);
    $dateEnd = $_GET['dateEnd'] ?? (($bigDataSchool == "Y") ? Format::date(date('Y-m-d')) : null);

    $status = $_GET['status'] ?? '';

    if ($highestAction == 'Mentorship Overview_all') {
        echo "<p>".__m('This report offers a summary of all mentor activity, including enrolments by class.')."</p>";
    } else {
        echo "<p>".__m('This report offers a summary of all of your mentor activity, including enrolments by class.')."</p>";
    }

    $form = Form::create('search', $session->get('absoluteURL').'/index.php', 'get');
    $form->setFactory(DatabaseFormFactory::create($pdo));

    $form->setTitle(__('Filter'));
    $form->setClass('noIntBorder w-full');

    $form->addHiddenValue('q', '/modules/'.$session->get('module').'/report_mentorshipOverview.php');
    $form->addHiddenValue('status', $status);

    if ($highestAction == 'Mentorship Overview_all') {
        $row = $form->addRow();
            $row->addLabel('allMentors', __('All Mentors'));
            $row->addCheckbox('allMentors')->setValue('on')->checked($allMentors);

        $row = $form->addRow();
            $row->addLabel('allStudents', __('All Students'))->description(__m('Includes all statuses when checked'));
            $row->addCheckbox('allStudents')->setValue('on')->checked($allStudents);

        $form->toggleVisibilityByClass('mentor')->onCheckbox('allMentors')->whenNot('on');

        $data = array();
        $sql = "SELECT DISTINCT gibbonPerson.gibbonPersonID AS value, CONCAT(surname, ', ', preferredName) AS name, 'School Mentor' AS groupBy FROM gibbonPerson JOIN freeLearningUnitStudent ON (freeLearningUnitStudent.gibbonPersonIDSchoolMentor=gibbonPerson.gibbonPersonID) WHERE gibbonPerson.status='Full' ORDER BY surname, preferredName";
        $data2 = array();
        $sql2 = "SELECT DISTINCT gibbonPerson.gibbonPersonID AS value, CONCAT(surname, ', ', preferredName) AS name, 'Class Teacher' AS groupBy FROM gibbonPerson JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonPerson.status='Full' AND role='Teacher' ORDER BY surname, preferredName";
        $row = $form->addRow()->addClass('mentor');
            $row->addLabel('gibbonPersonIDSchoolMentor', __m('School Mentor'))->description(!empty($mentorGroups) ? __m('Mentors based on your assigned mentor groups.') : '');
            $row->addSelectPerson('gibbonPersonIDSchoolMentor')
                ->fromQuery($pdo, $sql, $data, 'groupBy')
                ->fromQuery($pdo, $sql2, $data2, 'groupBy')
                ->placeholder()
                ->selected($gibbonPersonID);

        $row = $form->addRow();
            $row->addLabel('gibbonSchoolYearID', __('School Year'));
            $row->addSelectSchoolYear('gibbonSchoolYearID', 'Recent')->required()->selected($gibbonSchoolYearID);
    }

    if ($bigDataSchool == "Y") {
        $row = $form->addRow();
            $row->addLabel('dateStart', __('Start Date'));
            $row->addDate('dateStart')->setValue($dateStart);

        $row = $form->addRow();
            $row->addLabel('dateEnd', __('End Date'));
            $row->addDate('dateEnd')->setValue($dateEnd);
    }

    $row = $form->addRow();
        $row->addSearchSubmit($session, __('Clear Filters'));

    echo $form->getOutput();

    $form = BulkActionForm::create('bulkAction', $session->get('absoluteURL').'/modules/Free Learning/report_mentorshipOverviewProcessBulk.php');

    $bulkActions = ['Approve' => __('Approve')];
    $col = $form->createBulkActionColumn($bulkActions);
        $col->addSubmit(__('Go'));

    // DATA TABLE
    $unitGateway = $container->get(UnitGateway::class);
    $unitStudentGateway = $container->get(UnitStudentGateway::class);

    $criteria = $unitStudentGateway->newQueryCriteria(true)
        ->sortBy(['statusSort', 'timestamp'], 'DESC')
        ->filterBy('status', $status)
        ->fromPOST();
    if (!empty($gibbonPersonID)) {
        $criteria->pageSize(0);
    }
    $mentorship = $unitStudentGateway->queryMentorship($criteria, $gibbonSchoolYearID, !empty($allMentors) ? null : $gibbonPersonID, $allStudents, $dateStart, $dateEnd);

    // Render chart for individuals
    if (!empty($gibbonPersonID) && count($mentorship) > 0) {
        $page->scripts->add('chart');

        echo "<h3>".__('Overview')."</h3>";

        $unitStats = [
            "Current - Pending" => 0,
            "Current" => 0,
            "Complete - Pending" => 0,
            "Evidence Not Yet Approved" => 0,
            "Complete - Approved" => 0,
            "Exempt" => 0
        ];
        $unitsComplete = 0;
        $unitsCompleteWaitTime = 0;
        foreach ($mentorship as $unit) {
            $unitStats[$unit['status']] ++;
            if ($unit['status'] == "Complete - Approved") {
                $unitsComplete ++;
                $unitsCompleteWaitTime += $unit['waitInDays'];
            }
        }

        $chart = Chart::create('unitStats', 'doughnut')
            ->setOptions([
                'height' => 80,
                'legend' => [
                    'position' => 'right',
                ]
            ])
            ->setLabels([__m('Current - Pending'), __m('Current'), __m('Complete - Pending'), __m('Evidence Not Yet Approved'), __m('Complete - Approved'), __m('Exempt')])
            ->setColors(['#FAF089', '#BAE6FD', '#DCC5f4', '#FFD2A8', '#6EE7B7', '#f9dbf2']);

        $chart->addDataset('pie')
            ->setData([$unitStats['Current - Pending'], $unitStats['Current'], $unitStats['Complete - Pending'], $unitStats['Evidence Not Yet Approved'], $unitStats['Complete - Approved'], $unitStats['Exempt']]);

        echo $chart->render();

        if ($unitsComplete > 0 ){
            echo "<hr class='mt-4 mb-4'/><div class='w-auto'><p class='text-center'>".__("Mean Wait Time (In Days)").": <b>".number_format($unitsCompleteWaitTime/$unitsComplete, 1)."</b></p></div>";
        }
    }

    $collaborationKeys = [];

    $table = $form->addRow()->addDataTable('mentorship', $criteria)->withData($mentorship);
    $table->setTitle(__('Details'));

    $table->modifyRows(function ($student, $row) {
        if ($student['status'] == 'Current - Pending') $row->addClass('currentPending');
        if ($student['status'] == 'Current') $row->addClass('currentUnit');
        if ($student['status'] == 'Evidence Not Yet Approved') $row->addClass('warning');
        if ($student['status'] == 'Complete - Pending') $row->addClass('pending');
        if ($student['status'] == 'Complete - Approved') $row->addClass('success');
        if ($student['status'] == 'Exempt') $row->addClass('exempt');
        return $row;
    });

    $table->addMetaData('bulkActions', $col);

    $table->addMetaData('filterOptions', [
        'status:Current - Pending'         => __('Status').': '.__m('Current - Pending'),
        'status:Current'                   => __('Status').': '.__m('Current'),
        'status:Evidence Not Yet Approved' => __('Status').': '.__m('Evidence Not Yet Approved'),
        'status:Complete - Pending'        => __('Status').': '.__m('Complete - Pending'),
        'status:Complete - Approved'       => __('Status').': '.__m('Complete - Approved'),
        'status:Exempt'                    => __('Status').': '.__m('Exempt'),
    ]);

    if ($highestAction == 'Mentorship Overview_all') {
        $table->addColumn('grouping', __m('Mentor'))
            ->description(__m('Enrolment Method'))
            ->sortable(['mentorsurname', 'mentorpreferredName'])
            ->format(function($values) {
                if ($values['enrolmentMethod'] == 'schoolMentor') {
                    $name = Format::name('', $values['mentorpreferredName'], $values['mentorsurname'], 'Student', false);
                    $output = Format::link('./index.php?q=/modules/Free Learning/report_mentorshipOverview.php&gibbonPersonID='.$values['gibbonPersonIDSchoolMentor'], $name);
                } else if ($values['enrolmentMethod'] == 'class') {
                    $name = Format::name('', $values['teacherpreferredName'], $values['teachersurname'], 'Student', false);
                    $output = Format::link('./index.php?q=/modules/Free Learning/report_mentorshipOverview.php&gibbonPersonID='.$values['teachergibbonPersonID'], $name);
                } else if ($values['enrolmentMethod'] == 'externalMentor') {
                    $output = $values['nameExternalMentor'];
                }

                $output .= '<br/>'.Format::small(ucwords(preg_replace('/(?<=\\w)(?=[A-Z])/'," $1", $values['enrolmentMethod'])));

                return $output;
            });
    }

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
        ->format(function($values) use ($customField) {
            $output = "";
            if ($values['category'] == 'Student') {
                $output .= Format::nameLinked($values['gibbonPersonID'], '', $values['studentpreferredName'], $values['studentsurname'], 'Student', true, true);
            } else {
                $output .= Format::name('', $values['studentpreferredName'], $values['studentsurname'], 'Student', true);
            }

            $fields = json_decode($values['fields'], true);
            if (!empty($fields[$customField])) {
                $value = $fields[$customField];
                if ($value != '') {
                    $output .= '<br/>'.Format::small($value);
                }
            }

            return $output;
        });

    $table->addColumn('status', __m('Status'))
        ->description(__m('Grouping'))
        ->format(function ($values) use (&$collaborationKeys) {
            $output = __m($values['status']);
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

            $output .= '<br/>' . Format::small($grouping);

            return $output;
        });

    $table->addColumn('timestamp', __('When'))
        ->format(Format::using('relativeTime', 'timestamp'));

    $table->addColumn('waitInDays', __('Wait Time'))
        ->description(__m('In Days'))
        ->format(function($values) {
            if ($values['status'] == "Complete - Approved") {
                return $values['waitInDays'];
            }
        });

    $table->addColumn('submissions', __m('Submissions'))
        ->notSortable();


    // ACTIONS
    $table->addActionColumn()
        ->addParam('freeLearningUnitStudentID')
        ->addParam('freeLearningUnitID')
        ->addParam('confirmationKey')
        ->addParam('mode', 'internal')
        ->addParam('sidebar', true)
        ->format(function ($values, $actions) use ($allMentors, $gibbonPersonID) {

            if (empty($allMentors) && $values['gibbonPersonIDSchoolMentor'] != $gibbonPersonID && $values['teachergibbonPersonID'] != $gibbonPersonID) return;

            if ($values['status'] == 'Current - Pending') {
                $actions->addAction('view', __('View'))
                    ->setURL('/modules/Free Learning/units_mentor.php');
            } else {
                $actions->addAction('edit', __('Edit'))
                    ->setURL('/modules/Free Learning/units_browse_details_approval.php');
            }

        });

    $table->addCheckboxColumn('freeLearningUnitStudentID')
        ->format(function ($values) {
            if ($values['status'] != 'Current - Pending') return ' ';
        });

    echo $form->getOutput();
}
