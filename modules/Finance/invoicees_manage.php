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
use Gibbon\Tables\DataTable;
use Gibbon\Domain\Finance\InvoiceeGateway;

if (isActionAccessible($guid, $connection2, '/modules/Finance/invoicees_manage.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs->add(__('Manage Invoicees'));
    $invoiceeGateway = $container->get(InvoiceeGateway::class);

    // Check for missing students from studentEnrolment and add a gibbonFinanceInvoicee record for them.
    $missingInvoicees = $invoiceeGateway->selectStudentsWithNoInvoicee()->fetchAll();
    $addFail = false;
    $addCount = 0;

    if (!empty($missingInvoicees)) {
        foreach ($missingInvoicees as $values) {
            $inserted = $invoiceeGateway->insert([
                'gibbonPersonID' => $values['gibbonPersonID'],
                'invoiceTo' => 'Family'
            ]);

            if (!$inserted || !$pdo->getQuerySuccess()) {
                $addFail = true;
            }

            $addCount++;
        }

        if ($addCount > 0 && $addFail == true) {
            echo Format::alert(__('It was detected that some students did not have invoicee records. The system tried to create these, but some of more creations failed.'));
        } elseif ($addCount > 0) {
            echo Format::alert(sprintf(__('It was detected that some students did not have invoicee records. The system has successfully created %1$s record(s) for you.'), $addCount), 'success');
        }
    }

    $search = $_GET['search'] ?? '';
    $allUsers = $_GET['allUsers'] ?? '';

    $form = Form::create('action', $session->get('absoluteURL').'/index.php', 'get');

    $form->setTitle(__('Filters'));
    $form->setClass('noIntBorder fullWidth');

    $form->addHiddenValue('address', $session->get('address'));
    $form->addHiddenValue('q', "/modules/".$session->get('module')."/invoicees_manage.php");

    $row = $form->addRow();
        $row->addLabel('search', __('Search For'))->description(__('Preferred, surname, username.'));
        $row->addTextField('search')->setValue($search);

    $row = $form->addRow();
        $row->addLabel('allUsers', __('All Students'))->description(__('Include students whose status is not "Full".'));
        $row->addCheckbox('allUsers')->setValue('on')->checked($allUsers);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSearchSubmit($session);

    echo $form->getOutput();

    $criteria = $invoiceeGateway->newQueryCriteria(true)
        ->searchBy($invoiceeGateway->getSearchableColumns(), $search)
        ->filterBy('allUsers', $allUsers)
        ->sortBy(['surname', 'preferredName'])
        ->fromPOST();
    $invoicees = $invoiceeGateway->queryInvoicees($criteria);

    $table = DataTable::createPaginated('invoicees', $criteria);
    $table->setTitle(__('View'));
    $table->setDescription(__("The table below shows all student invoicees within the school. A red row in the table below indicates that an invoicee's status is not \"Full\" or that their start or end dates are greater or less than than the current date."));

    $table->modifyRows(function ($invoicee, $row) {
        // Highlight if the person is not "Full" status or is no longer at the organisation
        if ($invoicee['started'] == 'N'|| $invoicee['ended'] == 'Y' || $invoicee['status'] != 'Full') {
            $row->addClass('error');
        }
        return $row;
    });
    $table->addColumn('name', __('Name'))
        ->sortable(['surname', 'preferredName'])
        ->format(function ($invoicee) {
            return Format::name('', $invoicee['preferredName'], $invoicee['surname'], 'Student', true);
        });
    $table->addColumn('status', __('Status'))->translatable();
    $table->addColumn('invoiceTo', __('Invoice To'))
            ->format(function ($invoicee) {
                switch ($invoicee['invoiceTo']) {
                    case "Family":
                        return __("Family");
                    case "Company":
                        switch ($invoicee['companyAll']) {
                            case "Y":
                                return __("Company");
                            case "N":
                                return __("Family + Company");
                            default:
                                return __("Unknown");
                        }
                        break;
                    default:
                        return __("Unknown");
                }
            });
    $table->addActionColumn()
            ->addParam('gibbonFinanceInvoiceeID')
            ->addParam('search', $search)
            ->addParam('allUsers', $allUsers)
            ->format(function ($item, $actions) {
                $actions->addAction('edit', __('Edit'))
                    ->setURL('/modules/Finance/invoicees_manage_edit.php');
            });

    echo $table->render($invoicees);
}
