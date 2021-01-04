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
use Gibbon\Tables\DataTable;
use Gibbon\Services\Format;
use Gibbon\Domain\User\FamilyGateway;

if (isActionAccessible($guid, $connection2, '/modules/User Admin/family_manage.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Set returnTo point for upcoming pages
    //Proceed!
    $page->breadcrumbs->add(__('Manage Families'));

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $search = isset($_GET['search'])? $_GET['search'] : '';

    $familyGateway = $container->get(FamilyGateway::class);

    // QUERY
    $criteria = $familyGateway->newQueryCriteria(true)
        ->searchBy($familyGateway->getSearchableColumns(), $search)
        ->sortBy(['name'])
        ->fromPOST();

    echo '<h2>';
    echo __('Search');
    echo '</h2>';

    $form = Form::create('filter', $_SESSION[$guid]['absoluteURL'].'/index.php', 'get');
    $form->setClass('noIntBorder fullWidth');

    $form->addHiddenValue('q', '/modules/'.$_SESSION[$guid]['module'].'/family_manage.php');

    $row = $form->addRow();
        $row->addLabel('search', __('Search For'))->description(_('Family Name'));
        $row->addTextField('search')->setValue($criteria->getSearchText());

    $row = $form->addRow();
        $row->addSearchSubmit($gibbon->session, __('Clear Search'));

    echo $form->getOutput();

    echo '<h2>';
    echo __('View');
    echo '</h2>';

    // QUERY
    $families = $familyGateway->queryFamilies($criteria);

    $familyIDs = $families->getColumn('gibbonFamilyID');
    $adults = $familyGateway->selectAdultsByFamily($familyIDs)->fetchGrouped();
    $families->joinColumn('gibbonFamilyID', 'adults', $adults);

    $children = $familyGateway->selectChildrenByFamily($familyIDs)->fetchGrouped();
    $families->joinColumn('gibbonFamilyID', 'children', $children);

    // DATA TABLE
    $table = DataTable::createPaginated('familyManage', $criteria);

    $table->addHeaderAction('add', __('Add'))
        ->setURL('/modules/User Admin/family_manage_add.php')
        ->addParam('search', $search)
        ->displayLabel();

    $table->addColumn('name', __('Name'));
    $table->addColumn('status', __('Marital Status'))->translatable();
    $table->addColumn('adults', __('Adults'))
        ->notSortable()
        ->format(function($row) {
            array_walk($row['adults'], function(&$person) {
                if ($person['status'] == 'Left' || $person['status'] == 'Expected') {
                    $person['surname'] .= ' <i>('.__($person['status']).')</i>';
                }
            });
            return Format::nameList($row['adults'], 'Parent');
        });
    $table->addColumn('children', __('Children'))
        ->notSortable()
        ->format(function($row) {
            array_walk($row['children'], function(&$person) {
                if ($person['status'] == 'Left' || $person['status'] == 'Expected') {
                    $person['surname'] .= ' <i>('.__($person['status']).')</i>';
                }
            });
            return Format::nameList($row['children'], 'Student');
        });

    // ACTIONS
    $table->addActionColumn()
        ->addParam('gibbonFamilyID')
        ->addParam('search', $criteria->getSearchText(true))
        ->format(function ($family, $actions) use ($guid) {
            $actions->addAction('edit', __('Edit'))
                    ->setURL('/modules/User Admin/family_manage_edit.php');

            $actions->addAction('delete', __('Delete'))
                    ->setURL('/modules/User Admin/family_manage_delete.php');
        });

    echo $table->render($families);
}
