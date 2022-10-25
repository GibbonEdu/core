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
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Tables\DataTable;
use Gibbon\Services\Format;
use Gibbon\Domain\Timetable\CourseGateway;
use Gibbon\Domain\School\SchoolYearGateway;
use Gibbon\Http\Url;

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

        $form = Form::create(
            'action',
            Url::fromModuleRoute('Timetable Admin', 'course_manage')
                ->withQueryParam('gibbonSchoolYearID', $gibbonSchoolYearID),
            'get'
        );

        $form->setFactory(DatabaseFormFactory::create($pdo));
        $form->setClass('noIntBorder fullWidth');

        $row = $form->addRow();
            $row->addLabel('search', __('Search For'));
            $row->addTextField('search')->setValue($criteria->getSearchText());

        $row = $form->addRow();
            $row->addLabel('gibbonYearGroupID', __('Year Group'));
            $row->addSelectYearGroup('gibbonYearGroupID')->selected($criteria->getFilterValue('yearGroup'));

        $row = $form->addRow();
            $row->addSearchSubmit($gibbon->session, __('Clear Filters'), array('gibbonSchoolYearID'));

        echo $form->getOutput();

        echo '<h3>';
        echo __('View');
        echo '</h3>';

        $courses = $courseGateway->queryCoursesBySchoolYear($criteria, $gibbonSchoolYearID);

        // DATA TABLE
        $table = DataTable::createPaginated('courseManage', $criteria);

        if (!empty($nextYear)) {
            $table->addHeaderAction('copy', __('Copy All To Next Year'))
                ->setURL(Url::fromModuleRoute('Timetable Admin', 'course_manage_copyProcess'))
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
            ->setURL(Url::fromModuleRoute('Timetable Admin', 'course_manage_add')
                ->withQueryParams([
                    'gibbonSchoolYearID' => $gibbonSchoolYearID,
                    'search' => $search,
                ]))
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
                        ->setURL(Url::fromModuleRoute('Timetable Admin', 'course_manage_edit'));

                $actions->addAction('delete', __('Delete'))
                        ->setURL(Url::fromModuleRoute('Timetable Admin', 'course_manage_delete'));
            });

        echo $table->render($courses);
    }
}
