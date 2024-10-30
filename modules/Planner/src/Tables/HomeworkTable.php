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

namespace Gibbon\Module\Planner\Tables;

use Gibbon\Domain\DataSet;
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Contracts\Services\Session;
use Gibbon\Contracts\Database\Connection;
use Gibbon\Domain\Planner\PlannerEntryGateway;

/**
 * HomeworkTable
 * 
 * Reusable DataTable class for displaying student homework.
 *
 * @version v21
 * @since   v21
 */
class HomeworkTable
{
    protected $session;
    protected $db;
    protected $plannerEntryGateway;

    public function __construct(Session $session, Connection $db,  PlannerEntryGateway $plannerEntryGateway)
    {
        $this->session = $session;
        $this->db = $db;
        $this->plannerEntryGateway = $plannerEntryGateway;
    }

    public function create($gibbonSchoolYearID, $gibbonPersonID, $roleCategory, $gibbonCourseClassID = null)
    {
        $guid = $this->session->get('guid');
        $connection2 = $this->db->getConnection();

        $criteria = $this->plannerEntryGateway->newQueryCriteria(true)
            ->sortBy(['date', 'timeStart'], 'DESC')
            ->filterBy('class', $gibbonCourseClassID)
            ->fromPOST();

        $allHomework = $this->plannerEntryGateway->queryHomeworkByPerson($criteria, $gibbonSchoolYearID, $gibbonPersonID);
        $plannerClasses = $this->plannerEntryGateway->selectPlannerClassesByPerson($gibbonSchoolYearID, $gibbonPersonID)->fetchKeyPair();

        // Here be Dragons! Be warned that there are three tables that contain student homework
        // Teacher Recorded - Online Submission = gibbonPlannerEntryHomework
        // Teacher Recorded - Manual Checkbox = gibbonPlannerEntryStudentTracker
        // Student Recorded - Manual Checkbox = gibbonPlannerEntryStudentHomework

        // Join homework submissions and tracker data
        if ($roleCategory == 'Student' || $roleCategory == 'Parent') {
            $trackerTeacherRecorded = $this->plannerEntryGateway->selectTeacherRecordedHomeworkTrackerByStudent($gibbonSchoolYearID, $gibbonPersonID)->fetchGroupedUnique();
            $allHomework->joinColumn('gibbonPlannerEntryID', 'trackerTeacher', $trackerTeacherRecorded);

            $trackerStudentRecorded = $this->plannerEntryGateway->selectStudentRecordedHomeworkTrackerByStudent($gibbonSchoolYearID, $gibbonPersonID)->fetchGroupedUnique();
            $allHomework->joinColumn('gibbonPlannerEntryID', 'trackerStudent', $trackerStudentRecorded);

            $submissions = $this->plannerEntryGateway->selectHomeworkSubmissionsByStudent($gibbonSchoolYearID, $gibbonPersonID)->fetchGrouped();
            $allHomework->joinColumn('gibbonPlannerEntryID', 'submissions', $submissions);
        }

        // Join homework submission counts
        if ($roleCategory == 'Staff') {
            $gibbonPlannerEntryIDList = $allHomework->getColumn('gibbonPlannerEntryID');
            $submissionCounts = $this->plannerEntryGateway->selectHomeworkSubmissionCounts($gibbonPlannerEntryIDList)->fetchGroupedUnique();
            $allHomework->joinColumn('gibbonPlannerEntryID', 'submissionCounts', $submissionCounts);
        }

        $table = DataTable::createPaginated('allHomework', $criteria)->withData($allHomework);
        
        $filterOptions = [
            'submission:Y' => __('Online Submission').': '.__('Yes'),
            'submission:N' => __('Online Submission').': '.__('No'),
        ];

        foreach ($plannerClasses as $value => $name) {
            $filterOptions['class:'.$value] = __('Class').': '.$name;
        }
        $table->addMetaData('filterOptions', $filterOptions);

        $table->modifyRows(function ($homework, $row) {
            // Teacher Recorded: Manual
            if (!empty($homework['trackerTeacher']['homeworkComplete']) && $homework['trackerTeacher']['homeworkComplete'] == 'Y' && $homework['type'] == 'teacherRecorded') {
                $row->addClass('success');
            }

            // Student Recorded: Manual
            if (!empty($homework['trackerStudent']['homeworkComplete']) && $homework['trackerStudent']['homeworkComplete'] == 'Y' && $homework['type'] == 'studentRecorded') {
                $row->addClass('success');
            }

            // Teacher Recorded: Online Submission
            if ($homework['type'] == 'teacherRecorded' && !empty($homework['submissions'])) {
                $latestSubmission = current($homework['submissions']);
                if ($latestSubmission['version'] == 'Final') $row->addClass('success');
            }

            return $row;
        });

        $table->addColumn('class', __('Class'))
            ->sortable(['course', 'class'])
            ->context('primary')
            ->description(__('Date'))
            ->format(function ($homework) {
                $output = Format::bold(Format::courseClassName($homework['course'], $homework['class'])).'<br/>'
                         .Format::small(Format::date($homework['date']));
                if (stripos($homework['role'], 'Left') !== false) {
                    $output .= Format::tag(__('Left'), 'dull ml-2');
                }
                
                return $output;
            });

        $table->addColumn('name', __('Lesson'))
            ->description(__('Unit'))
            ->context('primary')
            ->format(function ($homework) {
                return !empty($homework['unit'])
                    ? Format::bold($homework['name']).'<br/>'.Format::small($homework['unit'])
                    : Format::bold($homework['name']);
            });

        $table->addColumn('type', __('Type'))
            ->description(__('Details'))
            ->format(function ($homework) {
                return ($homework['type'] == 'teacherRecorded' ? __('Teacher Recorded') : __('Student Recorded') )
                    .'<br/>'.Format::small(Format::truncate(strip_tags($homework['homeworkDetails'])));
            });

        $table->addColumn('homeworkDueDateTime', __('Due Date'))
            ->format(function ($homework) {
                return Format::date($homework['homeworkDueDateTime']) . '<br/>' .
                    (!empty($homework['homeworkLocation']) && $homework['homeworkLocation'] == 'In Class' ? Format::small(__('In Class')) : '');
            });

        $table->addColumn('onlineSubmission', __('Online Submission'))
            ->sortable(['homeworkSubmission', 'homeworkSubmissionRequired'])
            ->format(function ($homework) use ($roleCategory) {
                if ($homework['homeworkSubmission'] != 'Y') return __('No');
                $output = Format::bold($homework['homeworkSubmissionRequired']).'</br>';

                $statusLabel = $this->getStatusLabel($homework);

                if ($roleCategory == 'Staff') {
                    $onTime = $homework['submissionCounts']['onTime'] ?? 0;
                    $late = $homework['submissionCounts']['late'] ?? 0;
                    $total = $homework['submissionCounts']['total'] ?? 0;

                    $output .= Format::small(__('On Time').': '.$onTime).'<br/>';
                    $output .= Format::small(__('Late').': '.$late).'<br/>';
                    $output .= Format::small($statusLabel.': '.($total - $late - $onTime)).'<br/>';
                } else {
                    if ($homework['homeworkSubmissionRequired'] == 'Required' 
                        && ($statusLabel == __('Late') || $statusLabel == __('Incomplete'))) {
                        $output .= Format::tag($statusLabel, 'error mt-1');
                    } else {
                        $output .= $statusLabel;
                    }
                }

                return $output;
            });

        if ($roleCategory == 'Student' || $roleCategory == 'Parent') {
            $table->addColumn('complete', __('Complete?'))
                ->context('primary')
                ->notSortable()
                ->width('10%')
                ->format(function ($homework) use ($roleCategory) {
                    if ($homework['type'] == 'teacherRecorded' && !empty($homework['submissions'])) {
                        $latestSubmission = end($homework['submissions']);
                        if ($latestSubmission['version'] == 'Final') return __('Yes');
                    }

                    $isTeacherHomeworkComplete = !empty($homework['trackerTeacher']['homeworkComplete']) && $homework['trackerTeacher']['homeworkComplete'] == 'Y' && $homework['type'] == 'teacherRecorded';

                    $isStudentHomeworkComplete = !empty($homework['trackerStudent']['homeworkComplete']) && $homework['trackerStudent']['homeworkComplete'] == 'Y' && $homework['type'] == 'studentRecorded';

                    if ($roleCategory == 'Student' && $homework['homeworkSubmissionRequired'] != 'Required') {
                        return '<input id="complete'.$homework['gibbonPlannerEntryID'].'" type="checkbox" class="mark-complete" data-id="'.$homework['gibbonPlannerEntryID'].'" data-type="'.$homework['type'].'" '.($isTeacherHomeworkComplete || $isStudentHomeworkComplete ? 'checked' : '').'>';
                    } else if (!empty($homework['trackerTeacher']['homeworkComplete']) && ($homework['trackerTeacher']['type'] == $homework['type'] || $homework['homeworkSubmissionRequired'] != 'Required')) {
                        return __('Yes');
                    }

                    return '';
                });
        }
        

        $table->addActionColumn()
            ->addParam('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->addParam('gibbonPersonID', $gibbonPersonID)
            ->addParam('gibbonPlannerEntryID')
            ->addParam('gibbonCourseClassID')
            ->addParam('search', $gibbonPersonID)
            ->addParam('viewBy', 'class')
            ->format(function ($homework, $actions) use ($roleCategory) {
                if (stripos($homework['role'], 'Left') === false) {
                    $actions->addAction('view', __('View'))
                        ->setURL('/modules/Planner/planner_view_full.php');
                }
            });

        return $table;
    }

    protected function getStatusLabel($homework)
    {
        if (!empty($homework['submissions'])) {
            $latestSubmission = end($homework['submissions']);
            return $latestSubmission['status'];
        }

        if ($homework['dateStart'] > $homework['date']) {
            return Format::tooltip(__('N/A'), __('Student joined school after assessment was given.'));
        }

        if (!empty($homework['trackerTeacher']['homeworkComplete']) && $homework['trackerTeacher']['homeworkComplete'] == 'Y' && $homework['type'] == 'teacherRecorded') {
            return __('On Time');
        }

        if (!empty($homework['trackerStudent']['homeworkComplete']) && $homework['trackerStudent']['homeworkComplete'] == 'Y' && $homework['type'] == 'studentRecorded') {
            return __('On Time');
        }

        if (date('Y-m-d H:i:s') < $homework['homeworkDueDateTime']) {
            return __('Pending');
        }

        if ($homework['homeworkSubmissionRequired'] == 'Required') {
            return __('Incomplete');
        }
        
        return __('Not Submitted Online');
    }
}
