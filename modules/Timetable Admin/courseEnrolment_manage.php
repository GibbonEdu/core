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
use Gibbon\Domain\Timetable\CourseGateway;

if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/courseEnrolment_manage.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs->add(__('Course Enrolment by Class'));

    $gibbonSchoolYearID = isset($_GET['gibbonSchoolYearID'])? $_GET['gibbonSchoolYearID'] : '';

    if (empty($gibbonSchoolYearID) || $gibbonSchoolYearID == $_SESSION[$guid]['gibbonSchoolYearID']) {
        $gibbonSchoolYearID = $_SESSION[$guid]['gibbonSchoolYearID'];
        $gibbonSchoolYearName = $_SESSION[$guid]['gibbonSchoolYearName'];
    } else {
        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
        $sql = "SELECT name FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearID";
        $result = $pdo->executeQuery($data, $sql);
        
        $gibbonSchoolYearName = ($result->rowCount() > 0)? $result->fetchColumn(0) : '';
    }

    if (empty($gibbonSchoolYearID) || empty($gibbonSchoolYearName)) {
        echo '<div class="error">';
        echo __('The specified record does not exist.');
        echo '</div>';
    } else {
        echo '<h2>';
        echo $gibbonSchoolYearName;
        echo '</h2>';

        echo "<div class='linkTop'>";
            //Print year picker
            if (getPreviousSchoolYearID($gibbonSchoolYearID, $connection2) != false) {
                echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/courseEnrolment_manage.php&gibbonSchoolYearID='.getPreviousSchoolYearID($gibbonSchoolYearID, $connection2)."'>".__('Previous Year').'</a> ';
            } else {
                echo __('Previous Year').' ';
            }
        echo ' | ';
        if (getNextSchoolYearID($gibbonSchoolYearID, $connection2) != false) {
            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/courseEnrolment_manage.php&gibbonSchoolYearID='.getNextSchoolYearID($gibbonSchoolYearID, $connection2)."'>".__('Next Year').'</a> ';
        } else {
            echo __('Next Year').' ';
        }
        echo '</div>';

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
        
        $form = Form::create('searchForm', $_SESSION[$guid]['absoluteURL'].'/index.php', 'get');
        $form->setFactory(DatabaseFormFactory::create($pdo));

        $form->setClass('noIntBorder fullWidth');

        $form->addHiddenValue('q', '/modules/'.$_SESSION[$guid]['module'].'/courseEnrolment_manage.php');
        $form->addHiddenValue('gibbonSchoolYearID', $gibbonSchoolYearID);

        $row = $form->addRow();
            $row->addLabel('search', __('Search For'));
            $row->addTextField('search')->setValue($criteria->getSearchText());

        $row = $form->addRow();
            $row->addLabel('gibbonYearGroupID', __('Year Group'));
            $row->addSelectYearGroup('gibbonYearGroupID')->selected($gibbonYearGroupID);


        $row = $form->addRow();
            $row->addSearchSubmit($gibbon->session, __('Clear Search'), array('gibbonSchoolYearID'));

        echo $form->getOutput();

        // QUERY
        $courses = $courseGateway->queryCoursesBySchoolYear($criteria, $gibbonSchoolYearID);

        if (count($courses) == 0) {
            echo '<div class="error">';
            echo __('There are no records to display.');
            echo '</div>';
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
            $table->addColumn('participantsActive', __('Participants'))->description(__('Active'));
            $table->addColumn('participantsExpected', __('Participants'))->description(__('Expected'));
            $table->addColumn('participantsTotal', __('Participants'))->description(__('Total'));

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
