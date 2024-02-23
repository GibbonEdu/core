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
use Gibbon\Domain\Timetable\CourseGateway;

if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/courseEnrolment_manage.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs->add(__('Course Enrolment by Class'));

    $gibbonSchoolYearID = $_REQUEST['gibbonSchoolYearID'] ?? $session->get('gibbonSchoolYearID');

    if (empty($gibbonSchoolYearID)) {
        $page->addError(__('The specified record does not exist.'));
    } else {
        $page->navigator->addSchoolYearNavigation($gibbonSchoolYearID);

        $search = (isset($_GET['search']))? $_GET['search'] : '';
        $gibbonYearGroupID = (isset($_GET['gibbonYearGroupID']))? $_GET['gibbonYearGroupID'] : '';

        $courseGateway = $container->get(CourseGateway::class);

        // CRITERIA
        $criteria = $courseGateway->newQueryCriteria()
            ->searchBy($courseGateway->getSearchableColumns(), $search)
            ->sortBy(['gibbonCourse.nameShort', 'gibbonCourse.name'])
            ->filterBy('yearGroup', $gibbonYearGroupID)
            ->fromPOST();

        echo '<h3>';
        echo __('Filters');
        echo '</h3>';

        $form = Form::create('searchForm', $session->get('absoluteURL').'/index.php', 'get');
        $form->setFactory(DatabaseFormFactory::create($pdo));

        $form->setClass('noIntBorder fullWidth');

        $form->addHiddenValue('q', '/modules/'.$session->get('module').'/courseEnrolment_manage.php');
        $form->addHiddenValue('gibbonSchoolYearID', $gibbonSchoolYearID);

        $row = $form->addRow();
            $row->addLabel('search', __('Search For'));
            $row->addTextField('search')->setValue($criteria->getSearchText());

        $row = $form->addRow();
            $row->addLabel('gibbonYearGroupID', __('Year Group'));
            $row->addSelectYearGroup('gibbonYearGroupID')->selected($gibbonYearGroupID);

        $row = $form->addRow();
            $row->addSearchSubmit($session, __('Clear Search'), array('gibbonSchoolYearID'));

        echo $form->getOutput();

        // QUERY
        $courses = $courseGateway->queryCoursesBySchoolYear($criteria, $gibbonSchoolYearID);

        if (count($courses) == 0) {
            echo $page->getBlankSlate();
            return;
        }

        foreach ($courses as $course) {
            echo '<h3>';
            echo $course['nameShort'].' ('.$course['name'].')';
            echo '</h3>';

            $classes = $courseGateway->selectClassesByCourseID($course['gibbonCourseID']);

            // DATA TABLE
            $table = DataTable::create('courseClassEnrolment');

            $table->addColumn('name', __('Name'));
            $table->addColumn('nameShort', __('Short Name'));
            $table->addColumn('teachersTotal', __('Teachers'));
            $table->addColumn('studentsActive', __('Students'))->description(__('Active'));
            $table->addColumn('studentsExpected', __('Students'))->description(__('Expected'));

            $table->addColumn('studentsTotal', __('Students'))
                ->description(__('Total'))
                ->format(function ($values) {
                   $return = $values['studentsTotal'];
                   if (is_numeric($values['enrolmentMin']) && $values['studentsTotal'] < $values['enrolmentMin']) {
                       $return .= Format::tag(__('Under Enrolled'), 'warning ml-2');
                   }
                   if (is_numeric($values['enrolmentMax']) && $values['studentsTotal'] > $values['enrolmentMax']) {
                       $return .= Format::tag(__('Over Enrolled'), 'error ml-2');
                   }
                   return $return;
                });

            // ACTIONS
            $table->addActionColumn()
                ->addParam('search', $criteria->getSearchText(true))
                ->addParam('gibbonSchoolYearID', $gibbonSchoolYearID)
                ->addParam('gibbonCourseID')
                ->addParam('gibbonCourseClassID')
                ->format(function ($class, $actions) {
                    $actions->addAction('edit', __('Edit'))
                        ->setURL('/modules/Timetable Admin/courseEnrolment_manage_class_edit.php');
                });

            echo $table->render($classes->toDataSet());
        }
    }
}
