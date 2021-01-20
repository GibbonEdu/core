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
use Gibbon\Domain\Timetable\CourseEnrolmentGateway;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/report_classEnrolment_byRollGroup.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs->add(__('Class Enrolment by Roll Group'));

    echo '<h2>';
    echo __('Choose Roll Group');
    echo '</h2>';

    $gibbonRollGroupID = isset($_GET['gibbonRollGroupID'])? $_GET['gibbonRollGroupID'] : '';

    $form = Form::create('filter', $_SESSION[$guid]['absoluteURL'].'/index.php', 'get');
    $form->setFactory(DatabaseFormFactory::create($pdo));
    $form->setClass('noIntBorder fullWidth');

    $form->addHiddenValue('q', '/modules/'.$_SESSION[$guid]['module'].'/report_classEnrolment_byRollGroup.php');

    $row = $form->addRow();
        $row->addLabel('gibbonRollGroupID', __('Roll Group'));
        $row->addSelectRollGroup('gibbonRollGroupID', $_SESSION[$guid]['gibbonSchoolYearID'])->selected($gibbonRollGroupID)->required()->placeholder();

    $row = $form->addRow();
        $row->addSearchSubmit($gibbon->session);

    echo $form->getOutput();

    if ($gibbonRollGroupID != '') {
        echo '<h2>';
        echo __('Report Data');
        echo '</h2>';

        $courseGateway = $container->get(CourseEnrolmentGateway::class);

        $enrolment = $courseGateway->selectCourseEnrolmentByRollGroup($gibbonRollGroupID);

        // DATA TABLE
        $table = DataTable::create('courseEnrolment');

        $table->addColumn('rollGroup', __('Roll Group'));
        $table->addColumn('student', __('Student'))
            ->sortable(['surname', 'preferredName'])
            ->format(function($person) use ($guid) {
                return Format::link($_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Timetable/tt_view.php&gibbonPersonID='.$person['gibbonPersonID'], Format::name('', $person['preferredName'], $person['surname'], 'Student', true) );
            });
        $table->addColumn('classCount', __('Class Count'));

        echo $table->render($enrolment->toDataSet());
    }
}
