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

use Gibbon\Tables\DataTable;
use Gibbon\Services\Format;
use Gibbon\Domain\School\SchoolYearGateway;
use Gibbon\Domain\FormGroups\FormGroupGateway;

if (isActionAccessible($guid, $connection2, '/modules/School Admin/formGroup_manage.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $gibbonSchoolYearID = $_GET['gibbonSchoolYearID'] ?? '';
    $gibbonFormGroupID = $_GET['gibbonFormGroupID'] ?? '';

    $page->breadcrumbs->add(__('Manage Form Groups'));

    $gibbonSchoolYearID = $_REQUEST['gibbonSchoolYearID'] ?? $session->get('gibbonSchoolYearID');
    $nextYear = $container->get(SchoolYearGateway::class)->getNextSchoolYearByID($gibbonSchoolYearID);

    // School Year Picker
    if (!empty($gibbonSchoolYearID)) {
        $page->navigator->addSchoolYearNavigation($gibbonSchoolYearID);
    }
        
    $formGroupGateway = $container->get(FormGroupGateway::class);

    // QUERY
    $criteria = $formGroupGateway->newQueryCriteria(true)
        ->sortBy(['sortOrder', 'gibbonFormGroup.name'])
        ->fromPOST();

    $formGroups = $formGroupGateway->queryFormGroups($criteria, $gibbonSchoolYearID);

    $formatTutorsList = function($row) use ($formGroupGateway) {
        $tutors = $formGroupGateway->selectTutorsByFormGroup($row['gibbonFormGroupID'])->fetchAll();
        if (count($tutors) > 1) $tutors[0]['surname'] .= ' ('.__('Main Tutor').')';

        return Format::nameList($tutors, 'Staff', false, true);
    };

    // DATA TABLE
    $table = DataTable::createPaginated('formGroupManage', $criteria);

    if (!empty($nextYear)) {
        $table->addHeaderAction('copy', __('Copy All To Next Year'))
            ->setURL('/modules/School Admin/formGroup_manage_copyProcess.php')
            ->addParam('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->addParam('gibbonSchoolYearIDNext', $nextYear['gibbonSchoolYearID'])
            ->setIcon('copy')
            ->onClick('return confirm("'.__('Are you sure you want to continue?').' '.__('This operation cannot be undone.').'");')
            ->displayLabel()
            ->directLink()
            ->append('&nbsp;|&nbsp;');
    }

    $table->addHeaderAction('add', __('Add'))
        ->setURL('/modules/School Admin/formGroup_manage_add.php')
        ->addParam('gibbonSchoolYearID', $gibbonSchoolYearID)
        ->displayLabel();

    $table->addColumn('name', __('Name'))
          ->description(__('Short Name'))
          ->format(function ($formGroup) {
            return '<strong>' . $formGroup['name'] . '</strong><br/><small><i>' . $formGroup['nameShort'] . '</i></small>';
          });
    $table->addColumn('tutors', __('Form Tutors'))->sortable(false)->format($formatTutorsList);
    $table->addColumn('space', __('Location'));
    $table->addColumn('website', __('Website'))
            ->format(Format::using('link', ['website']));
        
    // ACTIONS
    $table->addActionColumn()
        ->addParam('gibbonFormGroupID')
        ->addParam('gibbonSchoolYearID', $gibbonSchoolYearID)
        ->format(function ($formGroup, $actions) {
            $actions->addAction('edit', __('Edit'))
                    ->setURL('/modules/School Admin/formGroup_manage_edit.php');

            $actions->addAction('delete', __('Delete'))
                    ->setURL('/modules/School Admin/formGroup_manage_delete.php');
        });

    echo $table->render($formGroups);
}
