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
        $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonSchoolYearID2' => $_SESSION[$guid]['gibbonSchoolYearID']);
        $sql = "SELECT gibbonActivityStudentID, gibbonPerson.gibbonPersonID, surname, preferredName, gibbonRollGroup.nameShort AS rollGroup, gibbonActivityStudent.status, payment, gibbonActivity.name, programStart, programEnd FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) JOIN gibbonActivityStudent ON (gibbonActivityStudent.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonActivity ON (gibbonActivityStudent.gibbonActivityID=gibbonActivity.gibbonActivityID) WHERE gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonActivity.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID2 AND gibbonActivityStudent.status='Accepted' AND payment>0 AND invoiceGenerated='N' ORDER BY surname, preferredName, name";
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

        echo "<form method='post' action='".$_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/activities_paymentProcessBulk.php'>";
        echo "<fieldset style='border: none'>";
        echo "<div class='linkTop' style='height: 27px'>";
        ?>
		<input style='margin-top: 0px; float: right' type='submit' value='<?php echo __($guid, 'Go') ?>'>
		<select name="action" id="action" style='width:120px; float: right; margin-right: 1px;'>
			<option value="Select action"><?php echo __($guid, 'Select action') ?></option>
				<?php
				try {
					$dataSchedule = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
					$sqlSchedule = 'SELECT * FROM gibbonFinanceBillingSchedule WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY name';
					$resultSchedule = $connection2->prepare($sqlSchedule);
					$resultSchedule->execute($dataSchedule);
				} catch (PDOException $e) {
				}
				while ($rowSchedule = $resultSchedule->fetch()) {
					echo "<option value='".$rowSchedule['gibbonFinanceBillingScheduleID']."'>".sprintf(__($guid, 'Generate Invoices To %1$s'), $rowSchedule['name']).'</option>';
				}
				?>
			<option value="Generate Invoice - Simulate"><?php echo __($guid, 'Generate Invoice - Simulate') ?></option>
		</select>
		<script type="text/javascript">
			var action=new LiveValidation('action');
			action.add(Validate.Exclusion, { within: ['Select action'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
		</script>
		<?php
		echo '</div>';

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
		echo __($guid, 'Cost').'<br/>';
		echo "<span style='font-style: italic; font-size: 85%'>".$_SESSION[$guid]['currency'].'</span>';
		echo '</th>';
		echo '<th>';
		?>
		<script type="text/javascript">
			$(function () {
				$('.checkall').click(function () {
					$(this).parents('fieldset:eq(0)').find(':checkbox').attr('checked', this.checked);
				});
			});
		</script>
		<?php
		echo "<input type='checkbox' class='checkall'>";
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
            echo "<td style='text-align: left'>";
            if (substr($_SESSION[$guid]['currency'], 4) != '') {
                echo substr($_SESSION[$guid]['currency'], 4);
            }
            echo number_format($row['payment']);
            echo '</td>';
            echo '<td>';
            echo "<input type='checkbox' name='gibbonActivityStudentID-$count' id='gibbonActivityStudentID-$count' value='".$row['gibbonActivityStudentID']."'>";
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

        echo "<input name='count' value='$count' type='hidden'>";
        echo "<input name='address' value='".$_GET['q']."' type='hidden'>";
        echo '</fieldset>';
        echo '</form>';
    }

    echo '<h2>';
    echo __($guid, 'Invoices Generated');
    echo '</h2>';

    try {
        $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonSchoolYearID2' => $_SESSION[$guid]['gibbonSchoolYearID']);
        $sql = "SELECT gibbonPerson.gibbonPersonID, studentID, surname, preferredName, gibbonRollGroup.nameShort AS rollGroup, gibbonActivityStudent.status, payment, gibbonActivity.name, programStart, programEnd, gibbonFinanceInvoiceID FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) JOIN gibbonActivityStudent ON (gibbonActivityStudent.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonActivity ON (gibbonActivityStudent.gibbonActivityID=gibbonActivity.gibbonActivityID) WHERE gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonActivity.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID2 AND gibbonActivityStudent.status='Accepted' AND payment>0 AND invoiceGenerated='Y' ORDER BY surname, preferredName, name";
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
        echo __($guid, 'Cost').'<br/>';
        echo "<span style='font-style: italic; font-size: 85%'>".$_SESSION[$guid]['currency'].'</span>';
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
            echo "<td style='text-align: left'>";
            if (substr($_SESSION[$guid]['currency'], 4) != '') {
                echo substr($_SESSION[$guid]['currency'], 4);
            }
            echo number_format($row['payment']);
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