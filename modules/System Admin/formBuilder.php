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

use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Domain\Forms\FormGateway;

if (isActionAccessible($guid, $connection2, '/modules/System Admin/formBuilder.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs->add(__('Form Builder'));

    $formGateway = $container->get(FormGateway::class);

    // QUERY
    $criteria = $formGateway->newQueryCriteria(true)
        ->sortBy('name', 'ASC')
        ->filterBy('active', 'Y')
        ->fromPOST();

    $forms = $formGateway->queryForms($criteria);

    // GRID TABLE
    $table = $container->get(DataTable::class);
    $table->setTitle(__('Forms'));

    $table->addHeaderAction('add', __('Add'))
        ->setURL('/modules/System Admin/formBuilder_add.php')
        ->displayLabel();

    $table->addColumn('name', __('Name'));
    $table->addColumn('type', __('Type'));
    $table->addColumn('active', __('Active'))->format(Format::using('yesNo', 'active'));

    $table->addActionColumn()
        ->addParam('gibbonFormID')
        ->format(function ($form, $actions) {
            $actions->addAction('view', __('Preview'))
                ->setURL('/modules/System Admin/formBuilder_preview.php');

            $actions->addAction('edit', __('Edit'))
                ->setURL('/modules/System Admin/formBuilder_edit.php');

            $actions->addAction('copy', __('Duplicate'))
                ->setIcon('copy')
                ->setURL('/modules/System Admin/formBuilder_duplicate.php');

            $actions->addAction('delete', __('Delete'))
                ->setURL('/modules/System Admin/formBuilder_delete.php');
        });

    echo $table->render($forms);
}
