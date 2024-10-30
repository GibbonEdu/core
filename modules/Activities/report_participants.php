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

use Gibbon\View\View;
use Gibbon\Forms\Form;
use Gibbon\Services\Format;
use Gibbon\Domain\User\FamilyGateway;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Tables\Prefab\ReportTable;
use Gibbon\Domain\Activities\ActivityReportGateway;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Activities/report_participants.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $gibbonActivityID = $_GET['gibbonActivityID'] ?? null;
    $viewMode = $_REQUEST['format'] ?? '';

    if (empty($viewMode)) {
        $page->breadcrumbs->add(__('Participants by Activity'));

        $form = Form::create('filter', $session->get('absoluteURL').'/index.php','get');

        $form->setTitle(__('Choose Activity'));
        $form->setFactory(DatabaseFormFactory::create($pdo));
        $form->setClass('noIntBorder fullWidth');

        $form->addHiddenValue('q', "/modules/".$session->get('module')."/report_participants.php");

        $data = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'));
        $sql = "SELECT gibbonActivityID AS value, name FROM gibbonActivity WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND active='Y' ORDER BY name, programStart";
        $row = $form->addRow();
            $row->addLabel('gibbonActivityID', __('Activity'));
            $row->addSelect('gibbonActivityID')->fromQuery($pdo, $sql, $data)->selected($gibbonActivityID)->required()->placeholder();

        $row = $form->addRow();
            $row->addFooter();
            $row->addSearchSubmit($session);

        echo $form->getOutput();
    }

    if (empty($gibbonActivityID)) return;

    $activityGateway = $container->get(ActivityReportGateway::class);
    $familyGateway = $container->get(FamilyGateway::class);

    // CRITERIA
    $criteria = $activityGateway->newQueryCriteria(true)
        ->searchBy($activityGateway->getSearchableColumns(), $_GET['search'] ?? '')
        ->sortBy(['surname', 'preferredName'])
        ->pageSize(!empty($viewMode) ? 0 : 50)
        ->fromPOST();

    $participants = $activityGateway->queryParticipantsByActivity($criteria, $gibbonActivityID);

    // Join a set of family adults per student
    $people = $participants->getColumn('gibbonPersonID');
    $familyAdults = $familyGateway->selectFamilyAdultsByStudent($people)->fetchGrouped();
    $participants->joinColumn('gibbonPersonID', 'familyAdults', $familyAdults);

    // DATA TABLE
    $table = ReportTable::createPaginated('participants', $criteria)->setViewMode($viewMode, $session);

    $table->setTitle(__('Participants by Activity'));

    $table->addColumn('formGroup', __('Form Group'))->width('10%');
    $table->addColumn('student', __('Student'))
        ->sortable(['surname', 'preferredName'])
        ->format(function ($student) use ($session) {
            $name = Format::name('', $student['preferredName'], $student['surname'], 'Student', true);
            return Format::link($session->get('absoluteURL').'/index.php?q=/modules/Students/student_view_details.php&gibbonPersonID='.$student['gibbonPersonID'].'&subpage=Activities', $name);
        });
    $table->addColumn('status', __('Status'))->translatable();

    $table->addColumn('dob', __('Date of Birth'))->format(Format::using('date', 'dob'));

    $view = new View($container->get('twig'));

    $table->addColumn('contacts', __('Parental Contacts'))
        ->width('30%')
        ->notSortable()
        ->format(function ($student) use ($view) {
            return $view->fetchFromTemplate(
                'formats/familyContacts.twig.html',
                ['familyAdults' => $student['familyAdults'], 'includePhoneNumbers' => true]
            );
        });

    echo $table->render($participants);
}
