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

use Gibbon\Services\Module\Action;
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Domain\Forms\FormGateway;

if (isActionAccessible($guid, $connection2, new Action('System Admin', 'formBuilder')) == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs->add(__('Form Builder'));

    $page->addMessage('This <b>BETA</b> feature is part of the new flexible application form and admissions system. While we have worked to ensure that this functionality is ready to use, this is part of a very large set of changes that are likely to continue evolving over the next version, so we\'ve marked it as beta for v24. You are welcome to use these features and please do let us know in the support forums if you encounter any issues.');
    $formGateway = $container->get(FormGateway::class);

    // QUERY
    $criteria = $formGateway->newQueryCriteria(true)
        ->sortBy('name', 'ASC')
        ->fromPOST();

    $forms = $formGateway->queryForms($criteria);

    // GRID TABLE
    $table = $container->get(DataTable::class);
    $table->setTitle(__('Form Builder'));
    $table->setDescription(__('The form builder enables you to design application forms as well as other custom forms for collecting user data. The features available in each form depend on the fields added to them.'));

    $table->modifyRows(function ($module, $row) {
        $row->addClass($module['active'] == 'N' ? 'error' : '');
        return $row;
    });

    $table->addHeaderAction('add', __('Add'))
        ->setURL('/modules/System Admin/formBuilder_add.php')
        ->displayLabel();

    $table->addColumn('name', __('Name'));
    $table->addColumn('type', __('Type'))->translatable();
    $table->addColumn('pages', __('Pages'));
    $table->addColumn('active', __('Active'))->format(Format::using('yesNo', 'active'));
    $table->addColumn('public', __('Public'))->format(Format::using('yesNo', 'public'));

    $table->addActionColumn()
        ->addParam('gibbonFormID')
        ->format(function ($form, $actions) {
            if ($form['pages'] > 0) {
                $actions->addAction('view', __('Preview'))
                    ->setURL('/modules/System Admin/formBuilder_preview.php');
            }

            $actions->addAction('edit', __('Edit'))
                ->setURL('/modules/System Admin/formBuilder_edit.php');

            if ($form['pages'] > 0) {
                $actions->addAction('design', __('Design'))
                    ->setIcon('markbook')
                    ->setClass('mx-1')
                    ->addParam('sidebar', 'false')
                    ->setURL('/modules/System Admin/formBuilder_page_design.php');
            
                $actions->addAction('copy', __('Duplicate'))
                    ->setIcon('copy')
                    ->setURL('/modules/System Admin/formBuilder_duplicate.php');
            }

            $actions->addAction('delete', __('Delete'))
                ->setURL('/modules/System Admin/formBuilder_delete.php')
                ->modalWindow(650, 350);
        });

    echo $table->render($forms);
}
