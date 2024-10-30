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
use Gibbon\Services\Format;
use Gibbon\Domain\User\FamilyGateway;
use Gibbon\Tables\Prefab\ReportTable;
use Gibbon\Domain\Students\StudentReportGateway;


//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Students/report_transport_student.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $viewMode = $_REQUEST['format'] ?? '';
    $gibbonSchoolYearID = $session->get('gibbonSchoolYearID');

    if (empty($viewMode)) {
        $page->breadcrumbs->add(__('Student Transport'));
    }

    $reportGateway = $container->get(StudentReportGateway::class);
    $familyGateway = $container->get(FamilyGateway::class);

    // CRITERIA
    $criteria = $reportGateway->newQueryCriteria(true)
        ->sortBy(['gibbonPerson.transport', 'gibbonPerson.surname', 'gibbonPerson.preferredName'])
        ->pageSize(!empty($viewMode) ? 0 : 50)
        ->fromPOST();

    $transport = $reportGateway->queryStudentTransport($criteria, $gibbonSchoolYearID);

    // Join a set of family data per student
    $people = $transport->getColumn('gibbonPersonID');
    $familyData = $familyGateway->selectFamiliesByStudent($people)->fetchGrouped();
    $transport->joinColumn('gibbonPersonID', 'families', $familyData);

    // Join a set of family adults per student
    $familyAdults = $familyGateway->selectFamilyAdultsByStudent($people)->fetchGrouped();
    $transport->joinColumn('gibbonPersonID', 'familyAdults', $familyAdults);

    // DATA TABLE
    $table = ReportTable::createPaginated('studentTransport', $criteria)->setViewMode($viewMode, $session);
    $table->setTitle(__('Student Transport'));

    $table->addColumn('transport', __('Transport'))
        ->context('primary');
    $table->addColumn('formGroup', __('Form Group'))
        ->context('secondary')
        ->width('10%');
    $table->addColumn('student', __('Student'))
        ->context('primary')
        ->sortable(['gibbonPerson.surname', 'gibbonPerson.preferredName'])
        ->format(Format::using('name', ['', 'preferredName', 'surname', 'Student', true]));
    
    $view = new View($container->get('twig'));

    $table->addColumn('address1', __('Address'))
        ->width('30%')
        ->notSortable()
        ->format(function ($student) use ($view) {
            return $view->fetchFromTemplate(
                'formats/familyAddresses.twig.html',
                ['families' => $student['families'], 'person' => $student]
            );
        });

    $table->addColumn('contacts', __('Parental Contacts'))
        ->context('secondary')
        ->width('30%')
        ->notSortable()
        ->format(function ($student) use ($view) {
            return $view->fetchFromTemplate(
                'formats/familyContacts.twig.html',
                ['familyAdults' => $student['familyAdults'], 'includePhoneNumbers' => true]
            );
        });

    echo $table->render($transport);
}
