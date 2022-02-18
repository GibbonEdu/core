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

if (isActionAccessible($guid, $connection2, '/modules/Admissions/admissions_manage.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs->add(__('Admissions Accounts'));

    $search = $_GET['search'] ?? '';

    // SEARCH
    $form = Form::create('searchForm', $session->get('absoluteURL').'/index.php','get');
    $form->setTitle(__('Search'));
    $form->setClass('noIntBorder fullWidth');

    $form->addHiddenValue('q', '/modules/'.$session->get('module').'/admissions_manage.php');

    $row = $form->addRow();
        $row->addLabel('search', __('Search For'))->description();
        $row->addTextField('search')->setValue($search);

    $row = $form->addRow();
        $row->addSearchSubmit($gibbon->session, __('Clear Search'));

    echo $form->getOutput();

    // DATA TABLE
    $table = DataTable::create('admissions');
    $table->setTitle(__('Admissions Accounts'));

    $table->addColumn('name', __('Name'));
    $table->addColumn('email', __('Email'));
    $table->addColumn('date', __('Created On'));
    $table->addColumn('applications', __('Applications'));
    $table->addColumn('otherForms', __('Other Forms'));

    $table->addActionColumn()
        ->format(function ($values, $actions) {
            $actions->addAction('edit', __('Edit'))
                ->setURL('/modules/Admissions/admissions_manage_edit.php');

            $actions->addAction('delete', __('Delete'))
                ->setURL('/modules/Admissions/admissions_manage_delete.php');
        });

    echo $table->render([]);
}
