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
use Gibbon\Forms\Form;
use Gibbon\Domain\Finance\PettyCashGateway;

if (isActionAccessible($guid, $connection2, '/modules/Finance/pettyCash.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs
        ->add(__('Petty Cash'));

    $gibbonSchoolYearID = $_REQUEST['gibbonSchoolYearID'] ?? $session->get('gibbonSchoolYearID');
    $page->navigator->addSchoolYearNavigation($gibbonSchoolYearID);

    $params = [
        'search' => $_REQUEST['search'] ?? '',
    ];

    // CRITERIA
    $pettyCashGateway = $container->get(PettyCashGateway::class);
    $criteria = $pettyCashGateway->newQueryCriteria(true)
        ->searchBy($pettyCashGateway->getSearchableColumns(), $params['search'])
        ->sortBy(['statusSort'])
        ->sortBy('timestampCreated', 'DESC')
        ->sortBy(['surname', 'preferredName'])
        ->fromPOST();

    // SEARCH
    $form = Form::create('filters', $session->get('absoluteURL').'/index.php', 'get');
    $form->setClass('noIntBorder fullWidth');

    $form->addHiddenValue('q', '/modules/Finance/pettyCash.php');

    $row = $form->addRow();
        $row->addLabel('search', __('Search For'))->description(__('Preferred name, surname, username'));
        $row->addTextField('search')->setValue($criteria->getSearchText())->maxLength(20);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSearchSubmit($session, 'Clear Filters', ['view', 'sidebar']);

    echo $form->getOutput();

    $criteriaBalance = $pettyCashGateway->newQueryCriteria()
        ->searchBy($pettyCashGateway->getSearchableColumns(), $params['search'])
        ->pageSize(0);
        
    $balance = $pettyCashGateway->queryPettyCashBalance($criteriaBalance, $gibbonSchoolYearID)->getRow(0);
    if (!empty($balance['total'])) {
        echo Format::alert(__('Total petty cash needing repaid (in this list): {total}', ['total' => Format::currency($balance['total'])]), 'message');
    }

    $pettyCash = $pettyCashGateway->queryPettyCashBySchoolYear($criteria, $gibbonSchoolYearID);

    // TABLE
    $table = DataTable::createPaginated('pettyCash', $criteria);
    $table->setTitle(__('View'));

    $table->modifyRows(function($values, $row) {
        if ($values['status'] == 'Complete') $row->addClass('success');
        if ($values['status'] == 'Repaid') $row->addClass('success');
        if ($values['status'] == 'Refunded') $row->addClass('success');
        return $row;
    });

    $table->addHeaderAction('add', __('Add'))
        ->setURL('/modules/Finance/pettyCash_addEdit.php')
        ->addParam('gibbonSchoolYearID', $gibbonSchoolYearID)
        ->addParam('mode', 'add')
        ->displayLabel();

    $table->addColumn('student', __('Person'))
        ->sortable(['gibbonPerson.surname', 'gibbonPerson.preferredName'])
        ->width('20%')
        ->context('primary')
        ->format(function ($values) {
            return Format::nameLinked($values['gibbonPersonID'], '', $values['preferredName'], $values['surname'], $values['roleCategory'], true, true);
        })
        ->formatDetails(function ($values) {
            return Format::small($values['formGroup']);
        });

    $table->addColumn('roleCategory', __('Role'))->context('secondary');

    $table->addColumn('amount', __('Amount'))
        ->description(__('Reason'))
        ->context('primary')
        ->format(Format::using('currency', 'amount'))
        ->formatDetails(function ($values) {
            return Format::small($values['reason']);
        });

    $table->addColumn('timestampCreated', __('When'))
        ->format(Format::using('dateTimeReadable', 'timestampCreated'));

    $table->addColumn('status', __('Status'))
        ->context('secondary')
        ->format(function ($values) {
            $tag = 'success';
            if ($values['status'] == 'Pending' && $values['actionRequired'] == 'Repay') $tag = 'warning';
            if ($values['status'] == 'Pending' && $values['actionRequired'] == 'Refund') $tag = 'message';

            return Format::tag($values['status'], $tag);
        })
        ->formatDetails(function ($values) {
            if ($values['status'] == 'Complete' || $values['actionRequired'] == 'None') return;

            if ($values['status'] != 'Pending' && !empty($values['timestampStatus'])) {
                return Format::small(Format::dateReadable($values['timestampStatus']));
            }

            switch ($values['actionRequired']) {
                case 'Repay': 
                    return Format::small(__('Needs Repaid'));
                case 'Refund':
                    return Format::small(__('Needs Refunded'));
                default: 
                return '';
            }
        });

    // ACTIONS
    $table->addActionColumn()
        ->addParam('gibbonFinancePettyCashID')
        ->addParam('gibbonSchoolYearID', $gibbonSchoolYearID)
        ->addParam('search', $criteria->getSearchText())
        ->format(function ($values, $actions) {
            if ($values['status'] == 'Pending' && $values['actionRequired'] != 'None') {
                $action = $values['actionRequired'] == 'Repay' ? __('Repaid') : __('Refunded');
                $actions->addAction('accept', __($action))
                    ->setURL('/modules/Finance/pettyCash_action.php')
                    ->addParam('action', $values['actionRequired'])
                    ->modalWindow(650, 200)
                    ->setIcon('check');
            }

            $actions->addAction('edit', __('Edit'))
                    ->setURL('/modules/Finance/pettyCash_addEdit.php')
                    ->addParam('mode', 'edit');

            $actions->addAction('delete', __('Delete'))
                    ->setURL('/modules/Finance/pettyCash_delete.php');
        });

    echo $table->render($pettyCash);
}
