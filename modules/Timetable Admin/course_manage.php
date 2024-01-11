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
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Tables\DataTable;
use Gibbon\Services\Format;
use Gibbon\Domain\Timetable\CourseGateway;
use Gibbon\Domain\School\SchoolYearGateway;

if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/course_manage.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs->add(__('Manage Courses & Classes'));

    $gibbonSchoolYearID = $_REQUEST['gibbonSchoolYearID'] ?? $session->get('gibbonSchoolYearID');
    $nextYear = $container->get(SchoolYearGateway::class)->getNextSchoolYearByID($gibbonSchoolYearID);

    if ($gibbonSchoolYearID != '') {
        $page->navigator->addSchoolYearNavigation($gibbonSchoolYearID);

        $search = (isset($_GET['search']))? $_GET['search'] : '';
        $gibbonYearGroupID = (isset($_GET['gibbonYearGroupID']))? $_GET['gibbonYearGroupID'] : '';

        $courseGateway = $container->get(CourseGateway::class);

        // CRITERIA
        $criteria = $courseGateway->newQueryCriteria(true)
            ->searchBy($courseGateway->getSearchableColumns(), $search)
            ->sortBy(['gibbonCourse.nameShort', 'gibbonCourse.name'])
            ->filterBy('yearGroup', $gibbonYearGroupID)
            ->fromPOST();

        echo '<h3>';
        echo __('Filters');
        echo '</h3>';

        $form = Form::create('action', $session->get('absoluteURL').'/index.php','get');

        $form->setFactory(DatabaseFormFactory::create($pdo));
        $form->setClass('noIntBorder fullWidth');

        $form->addHiddenValue('q', "/modules/".$session->get('module')."/course_manage.php");
        $form->addHiddenValue('gibbonSchoolYearID', $gibbonSchoolYearID);

        $row = $form->addRow();
            $row->addLabel('search', __('Search For'));
            $row->addTextField('search')->setValue($criteria->getSearchText());

        $row = $form->addRow();
            $row->addLabel('gibbonYearGroupID', __('Year Group'));
            $row->addSelectYearGroup('gibbonYearGroupID')->selected($criteria->getFilterValue('yearGroup'));

        $row = $form->addRow();
            $row->addSearchSubmit($session, __('Clear Filters'), array('gibbonSchoolYearID'));

        echo $form->getOutput();

        echo '<h3>';
        echo __('View');
        echo '</h3>';

        $courses = $courseGateway->queryCoursesBySchoolYear($criteria, $gibbonSchoolYearID);

        // DATA TABLE
        $table = DataTable::createPaginated('courseManage', $criteria);

        if (!empty($nextYear)) {
            $table->addHeaderAction('copy', __('Copy All To Next Year'))
                ->setURL('/modules/Timetable Admin/course_manage_copyProcess.php')
                ->addParam('gibbonSchoolYearID', $gibbonSchoolYearID)
                ->addParam('gibbonSchoolYearIDNext', $nextYear['gibbonSchoolYearID'])
                ->addParam('search', $search)
                ->setIcon('copy')
                ->onCLick('return confirm("'.__('Are you sure you want to do this? All courses and classes, but not their participants, will be copied.').'");')
                ->displayLabel()
                ->directLink()
                ->append(' | ');
        }

        $table->addHeaderAction('add', __('Add'))
            ->setURL('/modules/Timetable Admin/course_manage_add.php')
            ->addParam('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->addParam('search', $search)
            ->displayLabel();

        // COLUMNS
        $table->addColumn('nameShort', __('Short Name'));
        $table->addColumn('name', __('Name'));
        $table->addColumn('department', __('Learning Area'));
        $table->addColumn('classCount', __('Classes'));

        // ACTIONS
        $table->addActionColumn()
            ->addParam('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->addParam('gibbonCourseID')
            ->addParam('search', $criteria->getSearchText(true))
            ->format(function ($course, $actions) {
                $actions->addAction('edit', __('Edit'))
                        ->setURL('/modules/Timetable Admin/course_manage_edit.php');

                $actions->addAction('delete', __('Delete'))
                        ->setURL('/modules/Timetable Admin/course_manage_delete.php');
            });

        echo $table->render($courses);
    }
}
