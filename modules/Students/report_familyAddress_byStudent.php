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

use Gibbon\View\View;
use Gibbon\Forms\Form;
use Gibbon\Services\Format;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Tables\Prefab\ReportTable;
use Gibbon\Domain\User\FamilyGateway;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Students/report_familyAddress_byStudent.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $viewMode = $_REQUEST['format'] ?? '';
    $choices = $_POST['gibbonPersonID'] ?? [];
    $gibbonSchoolYearID = $gibbon->session->get('gibbonSchoolYearID');

    if (isset($_GET['gibbonPersonIDList'])) {
        $choices = explode(',', $_GET['gibbonPersonIDList']);
    } else {
        $_GET['gibbonPersonIDList'] = implode(',', $choices);
    }

    if (empty($viewMode)) {
        $page->breadcrumbs->add(__('Family Address by Student'));

        $form = Form::create('action', $_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Students/report_familyAddress_byStudent.php");
        $form->setTitle(__('Choose Students'));
        $form->setFactory(DatabaseFormFactory::create($pdo));
        $form->setClass('noIntBorder fullWidth');

        $row = $form->addRow();
            $row->addLabel('gibbonPersonID', __('Students'));
            $row->addSelectStudent('gibbonPersonID', $gibbonSchoolYearID, array("allStudents" => false, "byName" => true, "byRoll" => true))
                ->isRequired()
                ->selectMultiple()
                ->selected($choices);

        $row = $form->addRow();
            $row->addFooter();
            $row->addSearchSubmit($gibbon->session);

        echo $form->getOutput();
    }

    if (empty($choices)) {
        return;
    }

    $familyGateway = $container->get(FamilyGateway::class);

    // CRITERIA
    $criteria = $familyGateway->newQueryCriteria(true)
        ->sortBy(['gibbonFamily.name'])
        ->pageSize(!empty($viewMode) ? 0 : 50)
        ->fromPOST();

    $families = $familyGateway->queryFamiliesByStudent($criteria, $choices);

    // Join a set of student data per family
    $familyIDs = $families->getColumn('gibbonFamilyID');
    $childrenData = $familyGateway->selectChildrenByFamily($familyIDs)->fetchGrouped();
    $families->joinColumn('gibbonFamilyID', 'children', $childrenData);

    // DATA TABLE
    $table = ReportTable::createPaginated('familyAddressByStudent', $criteria)->setViewMode($viewMode, $gibbon->session);
    $table->setTitle(__('Family Address by Student'));
    $table->setDescription(__('This report attempts to print the family address(es) based on parents who are labelled as Contact Priority 1.'));

    $table->addMetaData('post', ['gibbonPersonID' => $choices]);
    
    $table->addColumn('name', __('Family'));
    $table->addColumn('students', __('Selected Students'))
        ->notSortable()
        ->format(function ($family) {
            $students = array_filter($family['children'], function ($child) use ($family) {
                return stripos($family['gibbonPersonIDList'], $child['gibbonPersonID']) !== false;
            });
            return Format::nameList($students);
        });

    $view = new View($container->get('twig'));

    $table->addColumn('homeAddress', __('Home Address'))
        ->width('50%')
        ->sortable(['homeAddressCountry', 'homeAddressDistrict', 'homeAddress'])
        ->format(function ($family) use ($view) {
            return $view->fetchFromTemplate(
                'formats/familyAddresses.twig.html',
                ['families' => [$family], 'includeAddressName' => true]
            );
        });

    echo $table->render($families);
}
