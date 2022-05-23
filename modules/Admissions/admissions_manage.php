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
use Gibbon\Domain\Admissions\AdmissionsAccountGateway;
use Gibbon\Services\Format;
use Gibbon\Http\Url;

if (isActionAccessible($guid, $connection2, '/modules/Admissions/admissions_manage.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs->add(__('Admissions Accounts'));

    $page->addMessage('This <b>BETA</b> feature is part of the new flexible application form and admissions system. While we have worked to ensure that this functionality is ready to use, this is part of a very large set of changes that are likely to continue evolving over the next version, so we\'ve marked it as beta for v24. You are welcome to use these features and please do let us know in the support forums if you encounter any issues.');

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

    // QUERY
    $admissionsAccountGateway = $container->get(AdmissionsAccountGateway::class);
    $criteria = $admissionsAccountGateway->newQueryCriteria(true)
        ->searchBy($admissionsAccountGateway->getSearchableColumns(), $search)
        ->sortBy('timestampCreated', 'DESC')
        ->fromPOST();

    $accounts = $admissionsAccountGateway->queryAdmissionsAccounts($criteria);

    // DATA TABLE
    $table = DataTable::createPaginated('admissions', $criteria);
    $table->setTitle(__('Admissions Accounts'));

    $table->addColumn('email', __('Email'));
    $table->addColumn('timestampCreated', __('Created'))
        ->format(Format::using('relativeTime', 'timestampCreated'));
    $table->addColumn('timestampActive', __('Last Active'))
        ->format(Format::using('relativeTime', 'timestampActive'));
    $table->addColumn('applicationCount', __('Applications'))
        ->format(function($values) {
            if (empty($values['applicationCount'])) return;

            $url = Url::fromModuleRoute('Admissions', 'applications_manage')->withQueryParams(['gibbonAdmissionsAccountID' => $values['gibbonAdmissionsAccountID']])->withAbsoluteUrl();
            return Format::link($url, $values['applicationCount']);
        });
    $table->addColumn('formCount', __('Other Forms'))
        ->format(function($values) {
            if (empty($values['formCount'])) return;

            $url = Url::fromModuleRoute('Admissions', 'applications_manage')->withQueryParams(['gibbonAdmissionsAccountID' => $values['gibbonAdmissionsAccountID']])->withAbsoluteUrl();
            return Format::link($url, $values['formCount']);
        });

    $table->addActionColumn()
        ->format(function ($values, $actions) {
            $actions->addAction('edit', __('Edit'))
                ->setURL('/modules/Admissions/admissions_manage_edit.php');

            $actions->addAction('delete', __('Delete'))
                ->setURL('/modules/Admissions/admissions_manage_delete.php');
        });

    echo $table->render($accounts);
}
