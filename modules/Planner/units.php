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

use Gibbon\Services\Format;
use Gibbon\Domain\Planner\UnitGateway;
use Gibbon\Forms\Prefab\BulkActionForm;
use Gibbon\Domain\Timetable\CourseGateway;
use Gibbon\Domain\School\SchoolYearGateway;

// Module includes
require_once __DIR__ . '/moduleFunctions.php';

$page->breadcrumbs->add(__('Unit Planner'));

if (isActionAccessible($guid, $connection2, '/modules/Planner/units.php') == false) {
    //Acess denied
    $page->addError(__('Your request failed because you do not have access to this action.'));
} else {
    // Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        $page->addError(__('The highest grouped action cannot be determined.'));
        return;
    }

    /** @var SchoolYearGateway */
    $schoolYearGateway = $container->get(SchoolYearGateway::class);
    $courseGateway = $container->get(CourseGateway::class);
    $unitGateway = $container->get(UnitGateway::class);

    // School Year Info
    $gibbonSchoolYearID = $_GET['gibbonSchoolYearID'] ?? $session->get('gibbonSchoolYearID');
    $gibbonCourseID = $_GET['gibbonCourseID'] ?? null;

    if (empty($gibbonSchoolYearID)) {
        $page->addError(__('Your request failed because your inputs were invalid.'));
        return;
    }

    $courseName = $_GET['courseName'] ?? '';

    if (empty($gibbonCourseID) && !empty($courseName)) {
        $row = $container->get(CourseGateway::class)->selectBy(['gibbonSchoolYearID' => $gibbonSchoolYearID, 'nameShort' => $courseName])->fetch();
        $gibbonCourseID = $row['gibbonCourseID'] ?? '';
    }

    if (empty($gibbonCourseID)) {
        try {
            if ($highestAction == 'Unit Planner_all') {
                $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
                $sql = 'SELECT * FROM gibbonCourse WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY nameShort';
            } elseif ($highestAction == 'Unit Planner_learningAreas') {
                        $data = array('gibbonPersonID' => $session->get('gibbonPersonID'), 'gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonSchoolYearID' => $gibbonSchoolYearID);
                $sql = "SELECT gibbonCourseID, gibbonCourse.name, gibbonCourse.nameShort FROM gibbonCourse JOIN gibbonDepartment ON (gibbonCourse.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) JOIN gibbonDepartmentStaff ON (gibbonDepartmentStaff.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) WHERE gibbonDepartmentStaff.gibbonPersonID=:gibbonPersonID AND (role='Coordinator' OR role='Assistant Coordinator' OR role='Teacher (Curriculum)') AND gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY gibbonCourse.nameShort";
            }
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
        }
        if ($result->rowCount() > 0) {
            $row = $result->fetch();
            $gibbonCourseID = $row['gibbonCourseID'];
        }
    }
    if ($gibbonCourseID != '') {

        $data = array('gibbonCourseID' => $gibbonCourseID);
        $sql = 'SELECT * FROM gibbonCourse WHERE gibbonCourseID=:gibbonCourseID';
        $result = $connection2->prepare($sql);
        $result->execute($data);
        if ($result->rowCount() == 1) {
            $row = $result->fetch();
        }
    }

    $page->navigator->addSchoolYearNavigation($gibbonSchoolYearID, ['courseName' => $row['nameShort'] ?? '']);

    //Work out previous and next course with same name
    $gibbonCourseIDPrevious = '';
    $gibbonSchoolYearPrevious = $schoolYearGateway->getPreviousSchoolYearByID($gibbonSchoolYearID);
    if ($gibbonSchoolYearPrevious != false and isset($row['nameShort'])) {
        $dataPrevious = array('gibbonSchoolYearID' => $gibbonSchoolYearPrevious['gibbonSchoolYearID'], 'nameShort' => $row['nameShort']);
        $sqlPrevious = 'SELECT * FROM gibbonCourse WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND nameShort=:nameShort';
        $resultPrevious = $connection2->prepare($sqlPrevious);
        $resultPrevious->execute($dataPrevious);
        if ($resultPrevious->rowCount() == 1) {
            $rowPrevious = $resultPrevious->fetch();
            $gibbonCourseIDPrevious = $rowPrevious['gibbonCourseID'];
        }
    }
    $gibbonCourseIDNext = '';
    $gibbonSchoolYearNext = $schoolYearGateway->getNextSchoolYearByID($gibbonSchoolYearID);
    if ($gibbonSchoolYearNext != false and isset($row['nameShort'])) {

        $dataNext = array('gibbonSchoolYearID' => $gibbonSchoolYearNext['gibbonSchoolYearID'], 'nameShort' => $row['nameShort']);
        $sqlNext = 'SELECT * FROM gibbonCourse WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND nameShort=:nameShort';
        $resultNext = $connection2->prepare($sqlNext);
        $resultNext->execute($dataNext);
        if ($resultNext->rowCount() == 1) {
            $rowNext = $resultNext->fetch();
            $gibbonCourseIDNext = $rowNext['gibbonCourseID'];
        }
    }

    if (empty($gibbonCourseID)) {
        $page->addError(__('You do not have access to edit the unit planner for any active courses in the current school year. You may need to be added to the relevant Department or Learning Area for the courses you are trying to access, or those courses may not have been added to the necessary Department or Learning Area.'));
        return;
    }

    try {
        if ($highestAction == 'Unit Planner_all') {
            $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonCourseID' => $gibbonCourseID);
            $sql = 'SELECT * FROM gibbonCourse WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseID=:gibbonCourseID';
        } elseif ($highestAction == 'Unit Planner_learningAreas') {
                        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonCourseID' => $gibbonCourseID, 'gibbonPersonID' => $session->get('gibbonPersonID'));
            $sql = "SELECT gibbonCourseID, gibbonCourse.name, gibbonCourse.nameShort FROM gibbonCourse JOIN gibbonDepartment ON (gibbonCourse.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) JOIN gibbonDepartmentStaff ON (gibbonDepartmentStaff.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) WHERE gibbonDepartmentStaff.gibbonPersonID=:gibbonPersonID AND (role='Coordinator' OR role='Assistant Coordinator' OR role='Teacher (Curriculum)') AND gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseID=:gibbonCourseID ORDER BY gibbonCourse.nameShort";
        }
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
    }

    if ($result->rowCount() < 1) {
        $page->addError(__('The selected record does not exist, or you do not have access to it.'));
        return;
    }

    // CRITERIA
    $criteria = $unitGateway->newQueryCriteria(true)
        ->sortBy(['ordering', 'name'])
        ->fromPOST();

    $course = $courseGateway->getByID($gibbonCourseID);
    $units = $unitGateway->queryUnitsByCourse($criteria, $gibbonCourseID);

    // FORM
    $form = BulkActionForm::create('bulkAction', $session->get('absoluteURL').'/modules/Planner/unitsProcessBulk.php');
    $form->setTitle($course['name']);
    $form->addHiddenValue('gibbonSchoolYearID', $gibbonSchoolYearID);
    $form->addHiddenValue('gibbonCourseID', $gibbonCourseID);

    $bulkActions = array(
        'Duplicate' => __('Duplicate'),
    );

    $courses = $courseGateway->selectActiveAndUpcomingCourses($gibbonSchoolYearID);
    $col = $form->createBulkActionColumn($bulkActions);
        $col->addSelect('gibbonCourseIDCopyTo')
            ->fromResults($courses, 'groupBy')
            ->required()
            ->placeholder()
            ->setClass('shortWidth copyTo');
        $col->addSubmit(__('Go'));

    $form->toggleVisibilityByClass('copyTo')->onSelect('action')->when('Duplicate');

    // DATA TABLE
    $table = $form->addRow()->addDataTable('units', $criteria)->withData($units);

    $table->addHeaderAction('add', __('Add'))
        ->setURL('/modules/Planner/units_add.php')
        ->addParam('gibbonSchoolYearID', $gibbonSchoolYearID)
        ->addParam('gibbonCourseID', $gibbonCourseID)
        ->displayLabel();

    $table->addMetaData('bulkActions', $col);
    $table->addMetaData('filterOptions', [
        'active:Y' => __('Active').': '.__('Yes'),
        'active:N' => __('Active').': '.__('No'),
    ]);

    $table->addColumn('name', __('Name'))->context('Primary');
    $table->addColumn('description', __('Description'))->context('Secondary');
    $table->addColumn('active', __('Active'))
        ->width('10%')
        ->format(Format::using('yesNo', 'active'));

    // ACTIONS
    $table->addActionColumn()
        ->addParam('gibbonSchoolYearID', $gibbonSchoolYearID)
        ->addParam('gibbonCourseID', $gibbonCourseID)
        ->addParam('gibbonUnitID')
        ->format(function ($unit, $actions) {
            $actions->addAction('edit', __('Edit'))
                    ->setURL('/modules/Planner/units_edit.php');

            $actions->addAction('delete', __('Delete'))
                    ->setURL('/modules/Planner/units_delete.php');

            $actions->addAction('duplicate', __('Duplicate'))
                    ->setIcon('copy')
                    ->setURL('/modules/Planner/units_duplicate.php');

            $actions->addAction('export', __('Export'))
                    ->setIcon('download')
                    ->addParam('sidebar', 'false')
                    ->setURL('/modules/Planner/units_dump.php');
        });

    $table->addCheckboxColumn('gibbonUnitID');

    echo $form->getOutput();

    // Print sidebar
    $session->set('sidebarExtra',sidebarExtraUnits($guid, $connection2, $gibbonCourseID, $gibbonSchoolYearID));
}
