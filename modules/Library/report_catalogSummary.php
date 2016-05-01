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

$_SESSION[$guid]['report_student_emergencySummary.php_choices'] = '';

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Library/report_catalogSummary.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Catalog Summary').'</div>';
    echo '</div>';

    echo '<h3>';
    echo __($guid, 'Search & Filter');
    echo '</h3>';

    //Get current filter values
    $ownershipType = null;
    if (isset($_POST['ownershipType'])) {
        $ownershipType = trim($_POST['ownershipType']);
    }
    if ($ownershipType == '') {
        if (isset($_GET['ownershipType'])) {
            $ownershipType = trim($_GET['ownershipType']);
        }
    }
    $gibbonLibraryTypeID = null;
    if (isset($_POST['gibbonLibraryTypeID'])) {
        $gibbonLibraryTypeID = trim($_POST['gibbonLibraryTypeID']);
    }
    if ($gibbonLibraryTypeID == '') {
        if (isset($_GET['gibbonLibraryTypeID'])) {
            $gibbonLibraryTypeID = trim($_GET['gibbonLibraryTypeID']);
        }
    }
    $gibbonSpaceID = null;
    if (isset($_POST['gibbonSpaceID'])) {
        $gibbonSpaceID = trim($_POST['gibbonSpaceID']);
    }
    if ($gibbonSpaceID == '') {
        if (isset($_GET['gibbonSpaceID'])) {
            $gibbonSpaceID = trim($_GET['gibbonSpaceID']);
        }
    }
    $status = null;
    if (isset($_POST['status'])) {
        $status = trim($_POST['status']);
    }
    if ($status == '') {
        if (isset($_GET['status'])) {
            $status = trim($_GET['status']);
        }
    }

    //Display filters
    echo "<form method='post' action='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Library/report_catalogSummary.php'>";
    echo "<table class='noIntBorder' cellspacing='0' style='width: 100%'>";
    ?>
			<tr>
				<td> 
					<b><?php echo __($guid, 'Ownership Type') ?></b><br/>
					<span class="emphasis small"></span>
				</td>
				<td class="right">
					<?php
                    echo "<select name='ownershipType' id='ownershipType' style='width:302px'>";
    echo '<option ';
    if ($ownershipType == '') {
        echo 'selected ';
    }
    echo "value=''></option>";
    echo '<option ';
    if ($ownershipType == 'School') {
        echo 'selected ';
    }
    echo "value='School'>".__($guid, 'School').'</option>';
    echo '<option ';
    if ($ownershipType == 'Individual') {
        echo 'selected ';
    }
    echo "value='Individual'>".__($guid, 'Individual').'</option>';
    echo '</select>';
    ?>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php echo __($guid, 'Type') ?></b><br/>
					<span class="emphasis small"></span>
				</td>
				<td class="right">
					<?php
                    try {
                        $dataType = array();
                        $sqlType = "SELECT * FROM gibbonLibraryType WHERE active='Y' ORDER BY name";
                        $resultType = $connection2->prepare($sqlType);
                        $resultType->execute($dataType);
                    } catch (PDOException $e) {
                        echo "<div class='error'>".$e->getMessage().'</div>';
                    }
    echo "<select name='gibbonLibraryTypeID' id='gibbonLibraryTypeID' style='width:302px'>";
    echo "<option value=''></option>";
    while ($rowType = $resultType->fetch()) {
        $selected = '';
        if ($rowType['gibbonLibraryTypeID'] == $gibbonLibraryTypeID) {
            $selected = 'selected';
        }
        echo "<option $selected value='".$rowType['gibbonLibraryTypeID']."'>".__($guid, $rowType['name']).'</option>';
    }
    echo '</select>';
    ?>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php echo __($guid, 'Location') ?> *</b><br/>
					<span class="emphasis small"></span>
				</td>
				<td class="right">
					<?php
                    try {
                        $dataLocation = array();
                        $sqlLocation = 'SELECT * FROM gibbonSpace ORDER BY name';
                        $resultLocation = $connection2->prepare($sqlLocation);
                        $resultLocation->execute($dataLocation);
                    } catch (PDOException $e) {
                        echo "<div class='error'>".$e->getMessage().'</div>';
                    }
    echo "<select name='gibbonSpaceID' id='gibbonSpaceID' style='width:302px'>";
    echo "<option value=''></option>";
    while ($rowLocation = $resultLocation->fetch()) {
        $selected = '';
        if ($rowLocation['gibbonSpaceID'] == $gibbonSpaceID) {
            $selected = 'selected';
        }
        echo "<option $selected value='".$rowLocation['gibbonSpaceID']."'>".$rowLocation['name'].'</option>';
    }
    echo '</select>';
    ?>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php echo __($guid, 'Status') ?></b><br/>
					<span class="emphasis small"></span>
				</td>
				<td class="right">
					<?php
                    echo "<select name='status' id='status' style='width:302px'>";
    echo "<option value=''></option>";
    echo '<option ';
    if ($status == 'Available') {
        echo 'selected ';
    }
    echo "value='Available'>".__($guid, 'Available').'</option>';
    echo '<option ';
    if ($status == 'Decommissioned') {
        echo 'selected ';
    }
    echo "value='Decommissioned'>".__($guid, 'Decommissioned').'</option>';
    echo '<option ';
    if ($status == 'In Use') {
        echo 'selected ';
    }
    echo "value='In Use'>".__($guid, 'In Use').'</option>';
    echo '<option ';
    if ($status == 'Lost') {
        echo 'selected ';
    }
    echo "value='Lost'>".__($guid, 'Lost').'</option>';
    echo '<option ';
    if ($status == 'On Loan') {
        echo 'selected ';
    }
    echo "value='On Loan'>".__($guid, 'On Loan').'</option>';
    echo '<option ';
    if ($status == 'Repair') {
        echo 'selected ';
    }
    echo "value='Repair'>".__($guid, 'Repair').'</option>';
    echo '<option ';
    if ($status == 'Reserved') {
        echo 'selected ';
    }
    echo "value='Reserved'>".__($guid, 'Reserved').'</option>';
    echo '</select>';
    ?>
				</td>
			</tr>
			<?php
            echo '<tr>';
    echo "<td class='right' colspan=2>";
    echo "<input type='hidden' name='q' value='".$_GET['q']."'>";
    echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Library/report_catalogSummary.php'>".__($guid, 'Clear Filters').'</a> ';
    echo "<input type='submit' value='".__($guid, 'Go')."'>";
    echo '</td>';
    echo '</tr>';
    echo '</table>';
    echo '</form>';

    echo '<h3>';
    echo __($guid, 'Report Data');
    echo '</h3>';

    echo "<div class='linkTop'>";
    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/report_catalogSummaryExport.php?address='.$_GET['q']."&ownershipType=$ownershipType&gibbonLibraryTypeID=$gibbonLibraryTypeID&gibbonSpaceID=$gibbonSpaceID&status=$status'><img title='".__($guid, 'Export to Excel')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/download.png'/></a>";
    echo '</div>';

    //Search with filters applied
    try {
        $data = array();
        $sqlWhere = 'WHERE ';
        if ($ownershipType != '') {
            $data['ownershipType'] = $ownershipType;
            $sqlWhere .= 'ownershipType=:ownershipType AND ';
        }
        if ($gibbonLibraryTypeID != '') {
            $data['gibbonLibraryTypeID'] = $gibbonLibraryTypeID;
            $sqlWhere .= 'gibbonLibraryTypeID=:gibbonLibraryTypeID AND ';
        }
        if ($gibbonSpaceID != '') {
            $data['gibbonSpaceID'] = $gibbonSpaceID;
            $sqlWhere .= 'gibbonSpaceID=:gibbonSpaceID AND ';
        }
        if ($status != '') {
            $data['status'] = $status;
            $sqlWhere .= 'status=:status AND ';
        }
        if ($sqlWhere == 'WHERE ') {
            $sqlWhere = '';
        } else {
            $sqlWhere = substr($sqlWhere, 0, -5);
        }
        $sql = "SELECT * FROM gibbonLibraryItem $sqlWhere ORDER BY id";
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
        echo "<table cellspacing='0' style='width: 100%'>";
        echo "<tr class='head'>";
        echo '<th>';
        echo __($guid, 'School ID').'<br/>';
        echo "<span style='font-style: italic; font-size: 85%'>".__($guid, 'Type').'</span>';
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Name').'<br/>';
        echo "<span style='font-size: 85%; font-style: italic'>".__($guid, 'Producer').'</span>';
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Location');
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Ownership').'<br/>';
        echo "<span style='font-size: 85%; font-style: italic'>".__($guid, 'User/Owner').'</span>';
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Status').'<br/>';
        echo "<span style='font-size: 85%; font-style: italic'>".__($guid, 'Borrowable').'</span>';
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Purchase Date').'<br/>';
        echo "<span style='font-size: 85%; font-style: italic'>".__($guid, 'Vendor').'</span>';
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

                //COLOR ROW BY STATUS!
                echo "<tr class=$rowNum>";
            echo '<td>';
            echo '<b>'.$row['id'].'</b><br/>';
            echo "<span style='font-style: italic; font-size: 85%'>";
            try {
                $dataType = array('gibbonLibraryTypeID' => $row['gibbonLibraryTypeID']);
                $sqlType = 'SELECT name FROM gibbonLibraryType WHERE gibbonLibraryTypeID=:gibbonLibraryTypeID';
                $resultType = $connection2->prepare($sqlType);
                $resultType->execute($dataType);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }
            if ($resultType->rowCount() == 1) {
                $rowType = $resultType->fetch();
                echo __($guid, $rowType['name']).'<br/>';
            }
            echo '</span>';
            echo '</td>';
            echo '<td>';
            echo '<b>'.$row['name'].'</b><br/>';
            echo "<span style='font-size: 85%; font-style: italic'>".$row['producer'].'</span>';
            echo '</td>';
            echo '<td>';
            if ($row['gibbonSpaceID'] != '') {
                try {
                    $dataSpace = array('gibbonSpaceID' => $row['gibbonSpaceID']);
                    $sqlSpace = 'SELECT * FROM gibbonSpace WHERE gibbonSpaceID=:gibbonSpaceID';
                    $resultSpace = $connection2->prepare($sqlSpace);
                    $resultSpace->execute($dataSpace);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }
                if ($resultSpace->rowCount() == 1) {
                    $rowSpace = $resultSpace->fetch();
                    echo $rowSpace['name'].'<br/>';
                }
            }
            if ($row['locationDetail'] != '') {
                echo "<span style='font-size: 85%; font-style: italic'>".$row['locationDetail'].'</span>';
            }
            echo '</td>';
            echo '<td>';
            if ($row['ownershipType'] == 'School') {
                echo $_SESSION[$guid]['organisationNameShort'].'<br/>';
            } elseif ($row['ownershipType'] == 'Individual') {
                echo 'Individual<br/>';
            }
            if ($row['gibbonPersonIDOwnership'] != '') {
                try {
                    $dataPerson = array('gibbonPersonID' => $row['gibbonPersonIDOwnership']);
                    $sqlPerson = 'SELECT title, preferredName, surname FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonID';
                    $resultPerson = $connection2->prepare($sqlPerson);
                    $resultPerson->execute($dataPerson);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }
                if ($resultPerson->rowCount() == 1) {
                    $rowPerson = $resultPerson->fetch();
                    echo "<span style='font-size: 85%; font-style: italic'>".formatName($rowPerson['title'], $rowPerson['preferredName'], $rowPerson['surname'], 'Staff', false, true).'</span>';
                }
            }
            echo '</td>';
            echo '<td>';
            echo $row['status'].'<br/>';
            echo "<span style='font-size: 85%; font-style: italic'>".$row['borrowable'].'</span>';
            echo '</td>';
            echo '<td>';
            if ($row['purchaseDate'] == '') {
                echo '<i>'.__($guid, 'Unknown').'</i><br/>';
            } else {
                echo dateConvertBack($guid, $row['purchaseDate']).'<br/>';
            }
            echo "<span style='font-size: 85%; font-style: italic'>".$row['vendor'].'</span>';
            echo '</td>';
            echo '</tr>';

            ++$count;
        }
        echo '</table>';
    }
}
?>