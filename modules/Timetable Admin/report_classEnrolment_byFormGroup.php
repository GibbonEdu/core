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
use Gibbon\Domain\Timetable\CourseEnrolmentGateway;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/report_classEnrolment_byFormGroup.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs->add(__('Class Enrolment by Form Group'));

    echo '<h2>';
    echo __('Choose Form Group');
    echo '</h2>';

    $gibbonFormGroupID = isset($_GET['gibbonFormGroupID'])? $_GET['gibbonFormGroupID'] : '';

    $form = Form::create('filter', $session->get('absoluteURL').'/index.php', 'get');
    $form->setFactory(DatabaseFormFactory::create($pdo));
    $form->setClass('noIntBorder fullWidth');

    $form->addHiddenValue('q', '/modules/'.$session->get('module').'/report_classEnrolment_byFormGroup.php');

    $row = $form->addRow();
        $row->addLabel('gibbonFormGroupID', __('Form Group'));
        $row->addSelectFormGroup('gibbonFormGroupID', $session->get('gibbonSchoolYearID'))->selected($gibbonFormGroupID)->required()->placeholder();

    $row = $form->addRow();
        $row->addSearchSubmit($session);

    echo $form->getOutput();

    if ($gibbonFormGroupID != '') {
        echo '<h2>';
        echo __('Report Data');
        echo '</h2>';

        $courseGateway = $container->get(CourseEnrolmentGateway::class);

        $enrolment = $courseGateway->selectCourseEnrolmentByFormGroup($gibbonFormGroupID);

        // DATA TABLE
        $table = DataTable::create('courseEnrolment');

        $table->addColumn('formGroup', __('Form Group'));
        $table->addColumn('student', __('Student'))
            ->sortable(['surname', 'preferredName'])
            ->format(function($person) use ($session) {
                return Format::link($session->get('absoluteURL').'/index.php?q=/modules/Timetable/tt_view.php&gibbonPersonID='.$person['gibbonPersonID'], Format::name('', $person['preferredName'], $person['surname'], 'Student', true) );
            });
        $table->addColumn('classCount', __('Class Count'));

        echo $table->render($enrolment->toDataSet());
    }
}
