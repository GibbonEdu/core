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

namespace Gibbon\Tables\Prefab;

use Gibbon\View\View;
use Gibbon\UI\Chart\Chart;
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Forms\OutputableInterface;
use Gibbon\Contracts\Services\Session;
use Gibbon\Domain\Students\StudentGateway;
use Gibbon\Domain\Students\StudentReportGateway;

/**
 * EnrolmentTable
 *
 * @version v22
 * @since   v22
 */
class EnrolmentTable implements OutputableInterface
{
    /**
     * @var \Gibbon\Contract\Services\Session
     */
    protected $session;

    /**
     * @var \Gibbon\View\View
     */
    protected $view;

    /**
     * @var \Gibbon\Domain\Students\StudentGateway
     */
    protected $studentGateway;

    /**
     * @var \Gibbon\Domain\Students\StudentReportGateway
     */
    protected $studentReportGateway;

    public function __construct(Session $session, View $view, StudentGateway $studentGateway, StudentReportGateway $studentReportGateway)
    {
        $this->session = $session;
        $this->view = $view;
        $this->studentGateway = $studentGateway;
        $this->studentReportGateway = $studentReportGateway;
    }

    public function getOutput()
    {
        global $page;
        $page->scripts->add('chart');

        $gibbonSchoolYearID = $this->session->get('gibbonSchoolYearID');

        $output = '';

        // TEMPLATE
        $output .= $this->view->fetchFromTemplate('ui/enrolmentOverview.twig.html', [
            'currentEnrolment' => $this->studentGateway->getStudentEnrolmentCount($gibbonSchoolYearID),
            'lastEnrolment' => $this->studentGateway->getStudentEnrolmentCount($gibbonSchoolYearID, date('Y-m-d', strtotime('today - 60 days'))),
            'nextEnrolment' => $this->studentGateway->getStudentEnrolmentCount($gibbonSchoolYearID, date('Y-m-d', strtotime('today + 60 days'))),
        ]);


        // CHART
        $chartData = $this->studentReportGateway->selectStudentCountByYearGroup($gibbonSchoolYearID)->fetchAll();

        if (!empty($chartData)) {
            $chart = Chart::create('overview', 'bar');
            $chart->setTitle(__('Student Enrolment by Year Group'));
            $chart->setLabels(array_column($chartData, 'yearGroup'));
            $chart->setLegend(false);
            $chart->setColors(['rgba(54, 162, 235, 1.0)']);
            $chart->setOptions([
                'height' => '20vh',
                'tooltip' => [
                    'mode' => 'x-axis',
                ],
                'animation' => false,
                'scales' => [
                    'y' => [
                        'display' => false,
                        'beginAtZero' => true,
                    ],
                    'x' => [
                        'display'   => true,
                        'gridLines' => ['display' => false],
                    ],
                ],
            ]);

            $chart->addDataset('total', __('Total Students'))
                ->setData(array_column($chartData, 'studentCount'));

            // RENDER CHART
            $output .= '<div style="overflow: visible;">'.$chart->render().'</div>';
        }

        // CRITERIA
        $criteria = $this->studentReportGateway->newQueryCriteria()
            ->sortBy('dateStart', 'DESC')
            ->sortBy(['formGroup', 'gibbonPerson.surname', 'gibbonPerson.preferredName'])
            ->fromPOST();

        $students = $this->studentReportGateway->queryStudentStatusBySchoolYear($criteria, $gibbonSchoolYearID, 'Full', date('Y-m-d', strtotime('today - 60 days')), date('Y-m-d', strtotime('today + 60 days')), false);

        // NEW TABLE
        $table = DataTable::create('studentsNew');
        $table->setTitle(__('New Students'));
        $table->setDescription(__('In the past 60 days or upcoming 60 days'));

        $table->addHeaderAction('view', __('View All'))
            ->setURL('/modules/Admissions/report_students_new.php')
            ->addParam('type', 'Current School Year')
            ->displayLabel();

        $table->modifyRows($this->studentReportGateway->getSharedUserRowHighlighter());

        $table->addColumn('student', __('Student'))
            ->context('primary')
            ->sortable(['gibbonPerson.surname', 'gibbonPerson.preferredName'])
            ->format(function ($student) {
                return Format::nameLinked($student['gibbonPersonID'], '', $student['preferredName'], $student['surname'], 'Student', true, true, ['allStudents' => 'on'])
                    . '<br/><small><i>'.Format::userStatusInfo($student).'</i></small>';
            });
        $table->addColumn('formGroup', __('Form Group'))->context('primary');
        $table->addColumn('username', __('Username'));
        $table->addColumn('officialName', __('Official Name'));
        $table->addColumn('dateStart', __('Start Date'))->context('secondary')->format(Format::using('date', 'dateStart'));
        $table->addColumn('lastSchool', __('Last School'));

        $table->addActionColumn()
            ->addParam('gibbonPersonID')
            ->addParam('allStudents', 'on')
            ->format(function ($row, $actions) {
                $actions->addAction('view', __('View Details'))
                    ->setURL('/modules/Students/student_view_details.php');
            });

        $output .= $table->render($students);

        // CRITERIA
        $criteria = $this->studentReportGateway->newQueryCriteria()
            ->sortBy(['dateEnd', 'formGroup', 'gibbonPerson.surname', 'gibbonPerson.preferredName'])
            ->fromPOST();

        $students = $this->studentReportGateway->queryStudentStatusBySchoolYear($criteria, $gibbonSchoolYearID, 'Left', date('Y-m-d', strtotime('today - 60 days')), date('Y-m-d', strtotime('today + 60 days')), true);

        // LEFT TABLE
        $table = DataTable::create('studentsLeft');
        $table->setTitle(__('Left Students'));
        $table->setDescription(__('In the past 60 days or upcoming 60 days'));

        $table->addHeaderAction('view', __('View All'))
            ->setURL('/modules/Admissions/report_students_left.php')
            ->addParam('type', 'Current School Year')
            ->displayLabel();

        $table->modifyRows($this->studentReportGateway->getSharedUserRowHighlighter());
        $table->addMetaData('hidePagination', true);

        $table->addColumn('student', __('Student'))
            ->context('primary')
            ->sortable(['gibbonPerson.surname', 'gibbonPerson.preferredName'])
            ->format(function ($student) {
                return Format::nameLinked($student['gibbonPersonID'], '', $student['preferredName'], $student['surname'], 'Student', true, true, ['allStudents' => 'on'])
                    . '<br/><small><i>'.Format::userStatusInfo($student).'</i></small>';
            });
        $table->addColumn('formGroup', __('Form Group'))->context('primary');
        $table->addColumn('username', __('Username'));
        $table->addColumn('officialName', __('Official Name'));
        $table->addColumn('dateStart', __('End Date'))->context('secondary')->format(Format::using('date', 'dateEnd'));
        $table->addColumn('nextSchool', __('Next School'));

        $table->addActionColumn()
            ->addParam('gibbonPersonID')
            ->addParam('allStudents', 'on')
            ->format(function ($row, $actions) {
                $actions->addAction('view', __('View Details'))
                    ->setURL('/modules/Students/student_view_details.php');
            });

        $output .= $table->render($students);

        return $output;
    }
}
