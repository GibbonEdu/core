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
use Gibbon\Tables\Prefab\ReportTable;
use Gibbon\Domain\Activities\ActivityCategoryGateway;
use Gibbon\Domain\Activities\ActivityGateway;
use Gibbon\Domain\Activities\ActivityChoiceGateway;

if (isActionAccessible($guid, $connection2, '/modules/Activities/report_notSignedUp.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs->add(__('Students Not Signed Up'));

    // Setup data
    $gibbonSchoolYearID = $_REQUEST['gibbonSchoolYearID'] ?? $session->get('gibbonSchoolYearID');
    $viewMode = $_REQUEST['format'] ?? '';

    // Setup gateways
    $categoryGateway = $container->get(ActivityCategoryGateway::class);
    $activityGateway = $container->get(ActivityGateway::class);
    $choiceGateway = $container->get(ActivityChoiceGateway::class);

    $categories = $categoryGateway->selectCategoriesBySchoolYear($session->get('gibbonSchoolYearID'))->fetchKeyPair();

    $params = [
        'gibbonActivityCategoryID' => $_REQUEST['gibbonActivityCategoryID'] ?? '',
        'search'             => $_REQUEST['search'] ?? ''
    ];


    if (empty($categories)) {
        $page->addMessage(__('There are no records to display.'));
        return;
    }
    
    // CRITERIA
    $criteria = $choiceGateway->newQueryCriteria(true)
        ->searchBy($choiceGateway->getSearchableColumns(), $params['search'])
        ->sortBy(['yearGroupSequence', 'formGroup', 'surname', 'preferredName'])
        ->filterBy('category', $params['gibbonActivityCategoryID'])
        ->pageSize(-1)
        ->fromPOST();

    if (empty($viewMode)) {
        // FILTER
        $form = Form::create('filter', $session->get('absoluteURL').'/index.php', 'get');

        $form->setTitle(__('Filter'));
        $form->setClass('noIntBorder fullWidth');

        $form->addHiddenValue('q', '/modules/'.$session->get('module').'/report_notSignedUp.php');
        $form->addHiddenValue('address', $session->get('address'));

        $row = $form->addRow();
            $row->addLabel('gibbonActivityCategoryID', __('Category'));
            $row->addSelect('gibbonActivityCategoryID')->fromArray($categories)->required()->placeholder()->selected($params['gibbonActivityCategoryID']);

        $row = $form->addRow();
            $row->addLabel('search', __('Search For'))->description(__('Preferred name, surname'));
            $row->addTextField('search')->setValue($criteria->getSearchText())->maxLength(20);

        $row = $form->addRow();
            $row->addFooter();
            $row->addSearchSubmit($session);

        echo $form->getOutput();
    }

    if (empty($params['gibbonActivityCategoryID'])) return;

    $unenrolled = $choiceGateway->queryNotSignedUpStudentsByCategory($criteria, $params['gibbonActivityCategoryID']);
    
    // DATA TABLE
    $table = ReportTable::createPaginated('report_notSignedUp', $criteria)->setViewMode($viewMode, $session);

    $table->setTitle(__('Students Not Signed Up'));

    $table->addColumn('image_240', __('Photo'))
        ->context('primary')
        ->width('8%')
        ->notSortable()
        ->format(Format::using('userPhoto', ['image_240', 'xs']));

    $table->addColumn('student', __('Person'))
        ->sortable(['gibbonPerson.surname', 'gibbonPerson.preferredName'])
        ->width('25%')
        ->format(function ($values) {
            return Format::nameLinked($values['gibbonPersonID'], '', $values['preferredName'], $values['surname'], 'Student', true, true);
        });

    $table->addColumn('formGroup', __('Form Group'))->context('secondary');

    $table->addColumn('email', __('Email'));

    $table->addColumn('categoryNameShort', __('Category'))
        ->width('8%');

    echo $table->render($unenrolled);
}
