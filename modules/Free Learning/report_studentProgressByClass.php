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
use Gibbon\Module\FreeLearning\Domain\UnitStudentGateway;

// Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Free Learning/report_studentProgressByClass.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs
         ->add(__m('Student Progress By Class'));

    $gibbonCourseClassID = $_GET['gibbonCourseClassID'] ?? null;
    $gibbonDepartmentID = $_REQUEST['gibbonDepartmentID'] ?? null;

    echo "<p>".__m("This report returns all enrolments from the specified class, as well as all school and external mentorship enrolments from the current school year.")."</p>";

    $form = Form::create('filter', $session->get('absoluteURL') . '/index.php', 'get');
    $form->setFactory(DatabaseFormFactory::create($pdo));
    $form->setClass('noIntBorder w-full');
    $form->setTitle(__m('Choose Class'));

    $form->addHiddenValue('q', '/modules/' . $session->get('module') . '/report_studentProgressByClass.php');

    $row = $form->addRow();
        $row->addLabel('gibbonCourseClassID', __('Class'));
        $row->addSelectClass('gibbonCourseClassID', $session->get('gibbonSchoolYearID'), $session->get('gibbonPersonID'))
            ->required()
            ->selected($gibbonCourseClassID)
            ->placeholder();

    $sql = "SELECT gibbonDepartmentID as value, name FROM gibbonDepartment WHERE type='Learning Area' ORDER BY name";
    $row = $form->addRow();
        $row->addLabel('gibbonDepartmentID', __('Learning Area'));
        $row->addSelect('gibbonDepartmentID')->fromQuery($pdo, $sql)->placeholder()->selected($gibbonDepartmentID);

    $row = $form->addRow();
    $row->addSearchSubmit($session);

    echo $form->getOutput();

    if ($gibbonCourseClassID != '') {
        echo '<h2>';
        echo __('Report Data');
        echo '</h2>';

        $courseGateway = $container->get(CourseGateway::class);
        $values = $courseGateway->getCourseClassByID($gibbonCourseClassID);

        if (!is_array($values)) {
            echo "<div class='error'>";
            echo __('There are no records to display.');
            echo '</div>';
        } else {
            echo "<p style='margin-bottom: 0px'><b>".__('Class').'</b>: '.$values['courseNameShort'].'.'.$values['nameShort'].'</p>';

            $unitStudentGateway = $container->get(UnitStudentGateway::class);

            $criteria = $unitStudentGateway->newQueryCriteria()
                ->sortBy('surname', 'preferredName', 'gibbonPersonID')
                ->fromPOST();

            $values = $unitStudentGateway->queryStudentProgressByStudent($criteria, $gibbonCourseClassID, $session->get('gibbonSchoolYearID'), $gibbonDepartmentID);

            $table = DataTable::createPaginated('progress', $criteria);

            $table->addColumn('student', __('Student'))
                ->sortable('surname', 'prefferedName', 'gibbonPersonID')
                ->format(function($values) {
                    return Format::nameLinked($values['gibbonPersonID'], '', $values['preferredName'], $values['surname'], 'Student', true, true);
                });

            $table->addColumn('completeApprovedCount', __('Complete - Approved'))
                ->width('11%')
                ->addClass('success');

            $table->addColumn('completePendingCount', __('Complete - Pending'))
                ->width('11%')
                ->addClass('pending');

            $table->addColumn('currentCount', __('Current'))
                ->width('11%')
                ->addClass('currentUnit');

            $table->addColumn('currentPendingCount', __('Current - Pending'))
                ->width('11%')
                ->addClass('currentPending');

            $table->addColumn('evidenceNotYetApprovedCount', __('Evidence Not Yet Approved'))
                ->width('11%')
                ->addClass('warning');

            $table->addColumn('exemptCount', __('Exempt'))
                ->width('11%')
                ->addClass('exempt');

            $table->addColumn('totalCount', __('Total'))
                ->width('11%');

            echo $table->render($values);
        }
    }
}
?>
