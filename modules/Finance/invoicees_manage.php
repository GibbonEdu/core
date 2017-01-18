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

if (isActionAccessible($guid, $connection2, '/modules/Finance/invoicees_manage.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Manage Invoicees').'</div>';
    echo '</div>';

    echo '<p>';
    echo __($guid, 'The table below shows all student invoicees within the school. A red row in the table below indicates that an invoicee\'s status is not "Full" or that their start or end dates are greater or less than than the current date.');
    echo '</p>';

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
                echo __($guid, 'It was detected that some students did not have invoicee records. The system tried to create these, but some of more creations failed.');
                echo '</div>';
            } else {
                echo "<div class='success'>";
                echo sprintf(__($guid, 'It was detected that some students did not have invoicee records. The system has successfully created %1$s record(s) for you.'), $addCount);
                echo '</div>';
            }
        }
    }

    echo '<h2>';
    echo __($guid, 'Filters');
    echo '</h2>';

    $search = null;
    if (isset($_GET['search'])) {
        $search = $_GET['search'];
    }
    $allUsers = null;
    if (isset($_GET['allUsers'])) {
        $allUsers = $_GET['allUsers'];
    }
    ?>
	<form method="get" action="<?php echo $_SESSION[$guid]['absoluteURL']?>/index.php">
		<table class='noIntBorder' cellspacing='0' style="width: 100%">
			<tr><td style="width: 30%"></td><td></td></tr>
			<tr>
				<td>
					<b><?php echo __($guid, 'Search For') ?></b><br/>
					<span class="emphasis small"><?php echo __($guid, 'Preferred, surname, username.') ?></span>
				</td>
				<td class="right">
					<input name="search" id="search" maxlength=20 value="<?php echo $search ?>" type="text" class="standardWidth">
				</td>
			</tr>
			<tr>
				<td>
					<b><?php echo __($guid, 'All Students') ?></b><br/>
					<span class="emphasis small"><?php echo __($guid, 'Include students whose status is not "Full".') ?></span>
				</td>
				<td class="right">
					<?php
                    $checked = '';
					if ($allUsers == 'on') {
						$checked = 'checked';
					}
					echo "<input $checked name=\"allUsers\" id=\"allUsers\" type=\"checkbox\">";
					?>
				</td>
			</tr>
			<tr>
				<td colspan=2 class="right">
					<input type="hidden" name="q" value="/modules/<?php echo $_SESSION[$guid]['module'] ?>/invoicees_manage.php">
					<input type="hidden" name="address" value="<?php echo $_SESSION[$guid]['address'] ?>">
					<?php
                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/invoicees_manage.php'>".__($guid, 'Clear Filters').'</a>';?>
					<input type="submit" value="<?php echo __($guid, 'Submit'); ?>">
				</td>
			</tr>
		</table>
	</form>
	<?php

    echo '<h2>';
    echo __($guid, 'View');
    echo '</h2>';

    //Set pagination variable
    $page = 1;
    if (isset($_GET['page'])) {
        $page = $_GET['page'];
    }
    if ((!is_numeric($page)) or $page < 1) {
        $page = 1;
    }

    try {
        $where = '';
        if ($allUsers != 'on') {
            $where = " AND status='Full'";
        }
        $data = array();
        $sql = "SELECT surname, preferredName, dateStart, dateEnd, status, gibbonFinanceInvoicee.* FROM gibbonFinanceInvoicee JOIN gibbonPerson ON (gibbonFinanceInvoicee.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT surname='' $where ORDER BY surname, preferredName";
        if ($search != '') {
            $data = array('search1' => "%$search%", 'search2' => "%$search%", 'search3' => "%$search%");
            $sql = "SELECT surname, preferredName, dateStart, dateEnd, status, gibbonFinanceInvoicee.* FROM gibbonFinanceInvoicee JOIN gibbonPerson ON (gibbonFinanceInvoicee.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT surname='' AND ((preferredName LIKE :search1) OR (surname LIKE :search2) OR (username LIKE :search3)) $where ORDER BY surname, preferredName";
        }
        $sqlPage = $sql.' LIMIT '.$_SESSION[$guid]['pagination'].' OFFSET '.(($page - 1) * $_SESSION[$guid]['pagination']);
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
        if ($result->rowCount() > $_SESSION[$guid]['pagination']) {
            printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]['pagination'], 'top', "&search=$search&allUsers=$allUsers");
        }

        echo "<table cellspacing='0' style='width: 100%'>";
        echo "<tr class='head'>";
        echo '<th>';
        echo __($guid, 'Name');
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Status');
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Invoice To');
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Actions');
        echo '</th>';
        echo '</tr>';

        $count = 0;
        $rowNum = 'odd';
        try {
            $resultPage = $connection2->prepare($sqlPage);
            $resultPage->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }
        while ($row = $resultPage->fetch()) {
            if ($count % 2 == 0) {
                $rowNum = 'even';
            } else {
                $rowNum = 'odd';
            }

			//Color rows based on start and end date
			if ($row['status'] != 'Full' or (!($row['dateStart'] == '' or $row['dateStart'] <= date('Y-m-d')) and ($row['dateEnd'] == '' or $row['dateEnd'] >= date('Y-m-d')))) {
				$rowNum = 'error';
			}

            //COLOR ROW BY STATUS!
            echo "<tr class=$rowNum>";
            echo '<td>';
            echo '<b>'.formatName('', $row['preferredName'], $row['surname'], 'Student', true).'</b><br/>';
            echo '</td>';
            echo '<td>';
            echo $row['status'];
            echo '</td>';
            echo '<td>';
            if ($row['invoiceTo'] == 'Family') {
                echo __($guid, 'Family');
            } elseif ($row['invoiceTo'] == 'Company' and $row['companyAll'] == 'Y') {
                echo __($guid, 'Company');
            } elseif ($row['invoiceTo'] == 'Company' and $row['companyAll'] == 'N') {
                echo __($guid, 'Family + Company');
            } else {
                echo '<i>'.__($guid, 'Unknown').'</i>';
            }
            echo '</td>';
            echo '<td>';
            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/invoicees_manage_edit.php&gibbonFinanceInvoiceeID='.$row['gibbonFinanceInvoiceeID']."&search=$search&allUsers=$allUsers'><img title='".__($guid, 'Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
            echo '</td>';
            echo '</tr>';

            ++$count;
        }
        echo '</table>';

        if ($result->rowCount() > $_SESSION[$guid]['pagination']) {
            printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]['pagination'], 'bottom');
        }
    }
}
?>
