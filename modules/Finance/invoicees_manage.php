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
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Domain\Finance\InvoiceeGateway;

if (isActionAccessible($guid, $connection2, '/modules/Finance/invoicees_manage.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __('You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    $page->breadcrumbs->add(__('Manage Invoicees'));

    //Check for missing students from studentEnrolment and add a gibbonFinanceInvoicee record for them.
    $addFail = false;
    $addCount = 0;
    try {
        $dataCur = array();
        $sqlCur = 'SELECT DISTINCT gibbonPerson.gibbonPersonID, surname, preferredName, gibbonFinanceInvoiceeID FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) LEFT JOIN gibbonFinanceInvoicee ON (gibbonFinanceInvoicee.gibbonPersonID=gibbonPerson.gibbonPersonID)';
        $resultCur = $connection2->prepare($sqlCur);
        $resultCur->execute($dataCur);
    } catch (PDOException $e) {
        $addFail = true;
    }
    if ($resultCur->rowCount() > 0) {
        while ($rowCur = $resultCur->fetch()) {
            if (is_null($rowCur['gibbonFinanceInvoiceeID'])) {
                try {
                    $dataAdd = array('gibbonPersonID' => $rowCur['gibbonPersonID']);
                    $sqlAdd = "INSERT INTO gibbonFinanceInvoicee SET gibbonPersonID=:gibbonPersonID, invoiceTo='Family'";
                    $resultAdd = $connection2->prepare($sqlAdd);
                    $resultAdd->execute($dataAdd);
                } catch (PDOException $e) {
                    $addFail = true;
                }
                ++$addCount;
            }
        }

        if ($addCount > 0) {
            if ($addFail == true) {
                echo "<div class='error'>";
                echo __('It was detected that some students did not have invoicee records. The system tried to create these, but some of more creations failed.');
                echo '</div>';
            } else {
                echo "<div class='success'>";
                echo sprintf(__('It was detected that some students did not have invoicee records. The system has successfully created %1$s record(s) for you.'), $addCount);
                echo '</div>';
            }
        }
    }

    $search = null;
    if (isset($_GET['search'])) {
        $search = $_GET['search'];
    }
    $allUsers = null;
    if (isset($_GET['allUsers'])) {
        $allUsers = $_GET['allUsers'];
    }

    $form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/index.php', 'get');

    $form->setTitle(__('Filters'));
    $form->setClass('noIntBorder fullWidth');

    $form->addHiddenValue('address', $_SESSION[$guid]['address']);
    $form->addHiddenValue('q', "/modules/".$_SESSION[$guid]['module']."/invoicees_manage.php");

    $row = $form->addRow();
        $row->addLabel('search', __('Search For'))->description(__('Preferred, surname, username.'));
        $row->addTextField('search')->setValue($search);

    $row = $form->addRow();
        $row->addLabel('allUsers', __('All Students'))->description(__('Include students whose status is not "Full".'));
        $row->addCheckbox('allUsers')->setValue('on')->checked($allUsers);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSearchSubmit($gibbon->session);

    echo $form->getOutput();


        $gateway = $container->get(InvoiceeGateway::class);
        $criteria = $gateway->newQueryCriteria(true)
                            ->filterBy('search')
                            ->filterBy('allUsers')
                            ->fromPOST();
        $invoicees = $gateway->queryInvoicees($criteria);

        $table = DataTable::createPaginated('invoicees', $criteria);
        $table->setTitle('View');
        $table->setDescription(__("The table below shows all student invoicees within the school. A red row in the table below indicates that an invoicee's status is not \"Full\" or that their start or end dates are greater or less than than the current date."));
        $table->modifyRows(function ($invoicee, $row) {
          //Highlight if the person is not "Full" status or is no longer at the organisation
            if ($invoicee['started'] == 'N'||
            $invoicee['ended'] == 'Y' ||
            $invoicee['status'] != 'Full') {
                $row->addClass('error');
            }
            return $row;
        });
        $table->addColumn('name', __('Name'))
              ->format(function ($invoicee) {
                return Format::name(
                    '',
                    $invoicee['preferredName'],
                    $invoicee['surname'],
                    'Student',
                    true
                );
              });
        $table->addColumn('status', __('Status'));
        $table->addColumn('invoiceTo', __('Invoice To'))
              ->format(function ($invoicee) {
                switch ($invoicee['invoiceTo']) {
                    case "Family":
                        return "Family";
                    case "Company":
                        switch ($invoicee['companyAll']) {
                            case "Y":
                                return "Company";
                            case "N":
                                return "Family + Company";
                            default:
                                return "Unknown";
                        }
                        break;
                    default:
                        return "Unknown";
                }
              });
        $table->addActionColumn()
              ->addParam('gibbonFinanceInvoiceeID')
              ->addParam('search')
              ->addParam('allUsers')
              ->format(function ($item, $actions) {
                $actions->addAction('edit', __('Edit'))
                  ->setURL('/modules/Finance/invoicees_manage_edit.php');
              });
        echo $table->render($invoicees);
}
