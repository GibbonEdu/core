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
        $row->addSearchSubmit($session, __('Clear Search'));

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

    $table->addColumn('person', __('Person'))
        ->description(__('Role'))
        ->sortable(['surname', 'preferredName'])
            ->width('20%')
        ->format(function($values) {
            return !empty($values['gibbonPersonID'])
                ? Format::nameLinked($values['gibbonPersonID'], $values['title'], $values['preferredName'], $values['surname'], 'Other', false, true)
                : Format::small(__('N/A'));
        })
        ->formatDetails(function($values) {
            return Format::small($values['roleName']);
        });

    $table->addColumn('familyName', __('Family'))
        ->width('20%')
        ->format(function($values) {
            $url = Url::fromModuleRoute('User Admin', 'family_manage_edit')
                ->withQueryParams(['gibbonFamilyID' => $values['gibbonFamilyID']])
                ->withAbsoluteUrl();
            return !empty($values['familyName'])
                ? Format::link($url, $values['familyName']) 
                : Format::small(__('N/A'));
        });

    $table->addColumn('email', __('Email'));

    $table->addColumn('timestampActive', __('Last Active'))
        ->format(Format::using('relativeTime', 'timestampActive'));

    $table->addColumn('applicationCount', __('Applications'))
        ->description(__('Other Forms'))
        ->width('12%')
        ->format(function($values) {
            if (empty($values['applicationCount'])) return;
            return $values['applicationCount'].'&nbsp;'.__('Applications');
        })
        ->formatDetails(function($values) {
            if (empty($values['formCount'])) return;
            return $values['formCount'].'&nbsp;'.__('Other Forms');
        });

    $table->addActionColumn()
        ->addParam('gibbonAdmissionsAccountID')
        ->format(function ($values, $actions) {
            $actions->addAction('edit', __('Edit'))
                ->setURL('/modules/Admissions/admissions_manage_edit.php');

            $actions->addAction('delete', __('Delete'))
                ->setURL('/modules/Admissions/admissions_manage_delete.php');
        });

    echo $table->render($accounts);
}
