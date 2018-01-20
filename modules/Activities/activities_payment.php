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

@session_start();

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Activities/activities_payment.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Create Invoices').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    echo '<h2>';
    echo __($guid, 'Invoices Not Yet Generated');
    echo '</h2>';
    echo '<p>';
    echo sprintf(__($guid, 'The list below shows students who have been accepted for an activity in the current year, who have yet to have invoices generated for them. You can generate invoices to a given %1$sBilling Schedule%2$s, or you can simulate generation (e.g. mark them as generated, but not actually produce an invoice).'), "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Finance/billingSchedule_manage.php'>", '</a>');
    echo '</p>';

    try {
        $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonSchoolYearID2' => $_SESSION[$guid]['gibbonSchoolYearID'], 'today' => date('Y-m-d'));
        $sql = "SELECT gibbonActivityStudentID, gibbonPerson.gibbonPersonID, surname, preferredName, gibbonRollGroup.nameShort AS rollGroup, gibbonActivityStudent.status, payment, paymentType, gibbonActivity.name, programStart, programEnd FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) JOIN gibbonActivityStudent ON (gibbonActivityStudent.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonActivity ON (gibbonActivityStudent.gibbonActivityID=gibbonActivity.gibbonActivityID) WHERE gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<=:today) AND (dateEnd IS NULL  OR dateEnd>=:today) AND gibbonActivity.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID2 AND gibbonActivityStudent.status='Accepted' AND payment>0 AND invoiceGenerated='N' ORDER BY surname, preferredName, name";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        echo "<div class='error'>".$e->getMessage().'</div>';
    }

    if ($result->rowCount() < 1) {
        echo "<div class='error'>";
        echo __($guid, 'There are no records to display.');
        echo '</div>';
    } else {
        $lastPerson = '';

        $form = Form::create('generateInvoices', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/activities_paymentProcessBulk.php');
        $form->addConfirmation(__('Are you sure you wish to process this action? It cannot be undone.'));

        $form->getRenderer()->setWrapper('form', 'div');
        $form->getRenderer()->setWrapper('row', 'div');
        $form->getRenderer()->setWrapper('cell', 'fieldset');
        $form->addHiddenValue('address', $_SESSION[$guid]['address']);
        
        $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
        $sql = "SELECT gibbonFinanceBillingScheduleID as value, name FROM gibbonFinanceBillingSchedule WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY name";
        $resultSchedule = $pdo->executeQuery($data, $sql);

        $billingSchedules = ($resultSchedule->rowCount() > 0)? $resultSchedule->fetchAll(\PDO::FETCH_KEY_PAIR) : array();
        $billingSchedules = array_map(function($item) {
            return sprintf(__('Generate Invoices To %1$s'), $item);
        }, $billingSchedules);
        $defaultActions = array('Generate Invoice - Simulate' => __('Generate Invoice - Simulate'));

        $row = $form->addRow()->setClass('right');
            $bulkAction = $row->addColumn()->addClass('inline right');
            $bulkAction->addSelect('action')
                ->fromArray($billingSchedules)
                ->fromArray($defaultActions)
                ->isRequired()
                ->setClass('mediumWidth floatNone')
                ->placeholder(__('Select action'));
            $bulkAction->addSubmit(__('Go'));
        
        $table = $form->addRow()->addTable()->addClass('colorOddEven');

        $header = $table->addHeaderRow();
        $header->addContent(__('Roll Group'));
        $header->addContent(__('Student'));
        $header->addContent(__('Activity'));
        $header->addContent(__('Cost'))->append('<br/><span class="small emphasis">'.$_SESSION[$guid]['currency'].'</span>');
        $header->addCheckbox('checkall')->setClass('floatNone textCenter checkall');

        while ($student = $result->fetch()) {
            $gibbonActivityStudentID = $student['gibbonActivityStudentID'];

            $row = $table->addRow();
            $row->addContent($student['rollGroup']);
            $row->addContent(formatName('', $student['preferredName'], $student['surname'], 'Student', true));
            $row->addContent($student['name']);
            $row->addCurrency("payment[$gibbonActivityStudentID]")->isRequired()->setValue($student['payment']);
            $row->addCheckbox("gibbonActivityStudentID[$gibbonActivityStudentID]")->setValue($student['gibbonActivityStudentID'])->setClass('');
        }
        
        echo $form->getOutput();
    }

    echo '<h2>';
    echo __($guid, 'Invoices Generated');
    echo '</h2>';

    try {
        $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonSchoolYearID2' => $_SESSION[$guid]['gibbonSchoolYearID'], 'today' => date('Y-m-d'));
        $sql = "SELECT gibbonPerson.gibbonPersonID, studentID, surname, preferredName, gibbonRollGroup.nameShort AS rollGroup, gibbonActivityStudent.status, payment, gibbonActivity.name, programStart, programEnd, gibbonFinanceInvoiceID FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) JOIN gibbonActivityStudent ON (gibbonActivityStudent.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonActivity ON (gibbonActivityStudent.gibbonActivityID=gibbonActivity.gibbonActivityID) WHERE gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<=:today) AND (dateEnd IS NULL  OR dateEnd>=:today) AND gibbonActivity.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID2 AND gibbonActivityStudent.status='Accepted' AND payment>0 AND invoiceGenerated='Y' ORDER BY surname, preferredName, name";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        echo "<div class='error'>".$e->getMessage().'</div>';
    }

    if ($result->rowCount() < 1) {
        echo "<div class='error'>";
        echo __($guid, 'There are no records to display.');
        echo '</div>';
    } else {
        $lastPerson = '';

        echo "<table cellspacing='0' style='width: 100%'>";
        echo "<tr class='head'>";
        echo '<th>';
        echo __($guid, 'Roll Group');
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Student');
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Activity');
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Invoice Number').'<br/>';
        echo '</th>';
        echo '</tr>';

        $count = 0;
        $rowNum = 'odd';
        while ($row = $result->fetch()) {
            if ($count % 2 == 0) {
                $rowNum = 'even';
            } else {
                $rowNum = 'odd';
            }
            ++$count;

            //COLOR ROW BY STATUS!
            echo "<tr class=$rowNum>";
            echo '<td>';
            echo $row['rollGroup'];
            echo '</td>';
            echo '<td>';
            echo formatName('', $row['preferredName'], $row['surname'], 'Student', true);
            echo '</td>';
            echo '<td>';
            echo $row['name'];
            echo '</td>';
            echo '<td>';
            $invoiceNumber = getSettingByScope($connection2, 'Finance', 'invoiceNumber');
            if ($invoiceNumber == 'Person ID + Invoice ID') {
                echo ltrim($row['gibbonPersonID'], '0').'-'.ltrim($row['gibbonFinanceInvoiceID'], '0');
            } elseif ($invoiceNumber == 'Student ID + Invoice ID') {
                echo ltrim($row['studentID'], '0').'-'.ltrim($row['gibbonFinanceInvoiceID'], '0');
            } else {
                echo ltrim($row['gibbonFinanceInvoiceID'], '0');
            }
            echo '</td>';
            echo '</tr>';

            $lastPerson = $row['gibbonPersonID'];
        }
        if ($count == 0) {
            echo "<tr class=$rowNum>";
            echo '<td colspan=4>';
            echo __($guid, 'There are no records to display.');
            echo '</td>';
            echo '</tr>';
        }
        echo '</table>';
    }
}
?>
