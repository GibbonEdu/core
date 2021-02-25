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
use Gibbon\Domain\Finance\FinanceGateway;
use Gibbon\Tables\DataTable;
use Gibbon\Services\Format;

if (isActionAccessible($guid, $connection2, '/modules/Finance/fees_manage.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs->add(__('Manage Fees'));

    echo '<p>';
    echo __('In this area you can create the various fee options which apply to students. Fees are specific to a school year, cannot be deleted and must be linked to a category. When you come to create invoices later on, you will be able to use these fees, as well as ad hoc charges.');
    echo '</p>';

    $gibbonSchoolYearID = '';
    if (isset($_GET['gibbonSchoolYearID'])) {
        $gibbonSchoolYearID = $_GET['gibbonSchoolYearID'];
    }
    if ($gibbonSchoolYearID == '' or $gibbonSchoolYearID == $_SESSION[$guid]['gibbonSchoolYearID']) {
        $gibbonSchoolYearID = $_SESSION[$guid]['gibbonSchoolYearID'];
        $gibbonSchoolYearName = $_SESSION[$guid]['gibbonSchoolYearName'];
    }

    $search = null;
    if (isset($_GET['search'])) {
        $search = $_GET['search'];
    }


    if ($gibbonSchoolYearID != $_SESSION[$guid]['gibbonSchoolYearID']) {
        
            $data = array('gibbonSchoolYearID' => $_GET['gibbonSchoolYearID']);
            $sql = 'SELECT * FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearID';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        if ($result->rowcount() != 1) {
            echo "<div class='error'>";
            echo __('The specified record does not exist.');
            echo '</div>';
        } else {
            $row = $result->fetch();
            $gibbonSchoolYearID = $row['gibbonSchoolYearID'];
            $gibbonSchoolYearName = $row['name'];
        }
    }

    if ($gibbonSchoolYearID != '') {
        echo '<h2>';
        echo $gibbonSchoolYearName;
        echo '</h2>';

        echo "<div class='linkTop'>";
            //Print year picker
        if (getPreviousSchoolYearID($gibbonSchoolYearID, $connection2) != false) {
            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/fees_manage.php&gibbonSchoolYearID='.getPreviousSchoolYearID($gibbonSchoolYearID, $connection2)."'>".__('Previous Year').'</a> ';
        } else {
            echo __('Previous Year').' ';
        }
        echo ' | ';
        if (getNextSchoolYearID($gibbonSchoolYearID, $connection2) != false) {
            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/fees_manage.php&gibbonSchoolYearID='.getNextSchoolYearID($gibbonSchoolYearID, $connection2)."'>".__('Next Year').'</a> ';
        } else {
            echo __('Next Year').' ';
        }
        echo '</div>';

        $search = isset($_GET['search'])? $_GET['search'] : '';

        $form = Form::create('filter', $_SESSION[$guid]['absoluteURL'].'/index.php', 'get');
        $form->setTitle(__('Search'));
        $form->setClass('noIntBorder fullWidth');

        $form->addHiddenValue('q', '/modules/'.$_SESSION[$guid]['module'].'/fees_manage.php');
        $form->addHiddenValue('gibbonSchoolYearID', $gibbonSchoolYearID);

        $row = $form->addRow();
            $row->addLabel('search', __('Search For'))->description(__('Fee name, category name.'));
            $row->addTextField('search')->setValue($search);

        $row = $form->addRow();
            $row->addSearchSubmit($gibbon->session, __('Clear Search'));

            echo $form->getOutput();

            $gateway = $container->get(FinanceGateway::class);
            $criteria = $gateway->newQueryCriteria(true)
                ->filterBy('gibbonSchoolYearID', $gibbonSchoolYearID)
                ->filterBy('search', $search)
                ->sortBy('gibbonFinanceFee.active')
                ->sortBy('gibbonFinanceFee.name')
                ->fromPOST();

            $fees = $gateway->queryFees($criteria);
            $table = DataTable::createPaginated('fees', $criteria);
            $table->setTitle(__('View'));
            $table->addHeaderAction('add', __('Add'))
                ->addParam('gibbonSchoolYearID', $gibbonSchoolYearID)
                ->addParam('search', $search)
                ->setURL('/modules/Finance/fees_manage_add.php')
                ->displayLabel();

            $table->modifyRows(function ($fee, $row) {
                return $fee['active'] == 'N' ? $row->addClass('error') : $row;
            });

            $table->addExpandableColumn('description', __('Description'));
            $table->addColumn('name', __('Name'))
              ->description(__('Short Name'))
              ->format(function ($fee) {
                return sprintf('<b>%1$s</b><br/>%2$s', $fee['name'], Format::small($fee['nameShort']));
              });

            $table->addColumn('category', __('Category'));

            $table->addColumn('fee', __('Fee'))
              ->format(function ($fee) {
                return Format::currency($fee['fee']);
              });

            $table->addColumn('active', __('Active'))
              ->format(Format::using('yesNo', 'active'));

            $table->addActionColumn()
                  ->addParam('gibbonSchoolYearID')
                  ->addParam('gibbonFinanceFeeID')
                  ->addParam('search', $search)
                  ->format(function ($fee, $actions) {
                    $actions->addAction('edit', __('Edit'))
                      ->setURL('/modules/Finance/fees_manage_edit.php');
                  });
            echo $table->render($fees);
    }
}
