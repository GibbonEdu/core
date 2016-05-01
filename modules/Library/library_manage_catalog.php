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

if (isActionAccessible($guid, $connection2, '/modules/Library/library_manage_catalog.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Manage Catalog').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, array('success0' => 'Your request was completed successfully.'));
    }

    echo '<h3>';
    echo __($guid, 'Search & Filter');
    echo '</h3>';

    //Get current filter values
    $name = null;
    if (isset($_POST['name'])) {
        $name = trim($_POST['name']);
    }
    if ($name == '') {
        if (isset($_GET['name'])) {
            $name = trim($_GET['name']);
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
    $gibbonPersonIDOwnership = null;
    if (isset($_POST['gibbonPersonIDOwnership'])) {
        $gibbonPersonIDOwnership = trim($_POST['gibbonPersonIDOwnership']);
    }
    if ($gibbonPersonIDOwnership == '') {
        if (isset($_GET['gibbonPersonIDOwnership'])) {
            $gibbonPersonIDOwnership = trim($_GET['gibbonPersonIDOwnership']);
        }
    }
    $typeSpecificFields = null;
    if (isset($_POST['typeSpecificFields'])) {
        $typeSpecificFields = trim($_POST['typeSpecificFields']);
    }
    if ($typeSpecificFields == '') {
        if (isset($_GET['typeSpecificFields'])) {
            $typeSpecificFields = trim($_GET['typeSpecificFields']);
        }
    }

    //Display filters
    echo "<form method='post' action='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Library/library_manage_catalog.php'>";
    echo "<table class='noIntBorder' cellspacing='0' style='width: 100%'>";
    ?>
			<tr>
				<td> 
					<b><?php echo __($guid, 'ID/Name/Producer') ?></b><br/>
					<span class="emphasis small"></span>
				</td>
				<td class="right">
					<?php
                    echo "<input type='text' name='name' id='name' value='".htmlPrep($name)."' style='width:302px'/>";
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
			<tr>
				<td> 
					<b><?php echo __($guid, 'Owner/User') ?></b><br/>
					<span class="emphasis small"></span>
				</td>
				<td class="right">
					<?php
                    echo '<select name="gibbonPersonIDOwnership" id="gibbonPersonIDOwnership" style="width: 302px">';
    echo "<option value=''></option>";
    try {
        $dataSelect = array();
        $sqlSelect = "SELECT gibbonPersonID, preferredName, surname FROM gibbonPerson WHERE gibbonPerson.status='Full' ORDER BY surname, preferredName";
        $resultSelect = $connection2->prepare($sqlSelect);
        $resultSelect->execute($dataSelect);
    } catch (PDOException $e) {
    }
    while ($rowSelect = $resultSelect->fetch()) {
        $selected = '';
        if ($rowSelect['gibbonPersonID'] == $gibbonPersonIDOwnership) {
            $selected = 'selected';
        }
        echo "<option $selected value='".$rowSelect['gibbonPersonID']."'>".formatName('', htmlPrep($rowSelect['preferredName']), htmlPrep($rowSelect['surname']), 'Student', true).'</option>';
    }
    echo '</select>';
    ?>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php echo __($guid, 'Type-Specific Fields') ?></b><br/>
					<span class="emphasis small"><?php echo __($guid, 'For example, a computer\'s MAC address or a book\'s ISBN.') ?></span>
				</td>
				<td class="right">
					<?php
                    echo "<input type='text' name='typeSpecificFields' id='typeSpecificFields' value='".htmlPrep($typeSpecificFields)."' style='width:302px'/>";
    ?>
				</td>
			</tr>
			<?php
            echo '<tr>';
    echo "<td class='right' colspan=2>";
    echo "<input type='hidden' name='q' value='".$_GET['q']."'>";
    echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Library/library_manage_catalog.php'>".__($guid, 'Clear Filters').'</a> ';
    echo "<input type='submit' value='".__($guid, 'Go')."'>";
    echo '</td>';
    echo '</tr>';
    echo '</table>';
    echo '</form>';

    //Set pagination variable
    $page = 1;
    if (isset($_GET['page'])) {
        $page = $_GET['page'];
    }
    if ((!is_numeric($page)) or $page < 1) {
        $page = 1;
    }

    //Search with filters applied
    try {
        $data = array();
        $sqlWhere = 'WHERE ';
        if ($name != '') {
            $data['name'] = '%'.$name.'%';
            $data['producer'] = '%'.$name.'%';
            $data['id'] = '%'.$name.'%';
            $sqlWhere .= '(name LIKE :name  OR producer LIKE :producer OR id LIKE :id) AND ';
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
        if ($gibbonPersonIDOwnership != '') {
            $data['gibbonPersonIDOwnership'] = $gibbonPersonIDOwnership;
            $sqlWhere .= 'gibbonPersonIDOwnership=:gibbonPersonIDOwnership AND ';
        }
        if ($typeSpecificFields != '') {
            $data['fields'] = '%'.$typeSpecificFields.'%';
            $sqlWhere .= 'fields LIKE :fields AND ';
        }
        if ($sqlWhere == 'WHERE ') {
            $sqlWhere = '';
        } else {
            $sqlWhere = substr($sqlWhere, 0, -5);
        }

        $sql = "SELECT * FROM gibbonLibraryItem $sqlWhere ORDER BY id";
        $sqlPage = $sql.' LIMIT '.$_SESSION[$guid]['pagination'].' OFFSET '.(($page - 1) * $_SESSION[$guid]['pagination']);
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        echo "<div class='error'>".$e->getMessage().'</div>';
    }

    echo "<div class='linkTop'>";
    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/library_manage_catalog_add.php&name=$name&gibbonLibraryTypeID=$gibbonLibraryTypeID&gibbonSpaceID=$gibbonSpaceID&status=$status&gibbonPersonIDOwnership=$gibbonPersonIDOwnership&typeSpecificFields=".urlencode($typeSpecificFields)."'>".__($guid, 'Add')."<img style='margin-left: 5px' title='".__($guid, 'Add')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/page_new.png'/></a>";
    echo '</div>';

    if ($result->rowCount() < 1) {
        echo '<h3>';
        echo __($guid, 'View');
        echo '</h3>';

        echo "<div class='error'>";
        echo __($guid, 'There are no records to display.');
        echo '</div>';
    } else {
        echo '<h3>';
        echo __($guid, 'View');
        echo "<span style='font-weight: normal; font-style: italic; font-size: 55%'> ".sprintf(__($guid, '%1$s record(s) in current view'), $result->rowCount()).'</span>';
        echo '</h3>';

        if ($result->rowCount() > $_SESSION[$guid]['pagination']) {
            printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]['pagination'], 'top', "name=$name&gibbonLibraryTypeID=$gibbonLibraryTypeID&gibbonSpaceID=$gibbonSpaceID&status=$status");
        }

        echo "<table cellspacing='0' style='width: 100%'>";
        echo "<tr class='head'>";
        echo "<th style='width: 80px'>";
        echo __($guid, 'School ID').'<br/>';
        echo "<span style='font-size: 85%; font-style: italic'>".__($guid, 'Type').'</span>';
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
        echo "<th style='width: 125px'>";
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

                //COLOR ROW BY STATUS!
                echo "<tr class=$rowNum>";
            echo '<td>';
            echo '<b>'.$row['id'].'</b><br/>';
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
                echo "<span style='font-size: 85%; font-style: italic'>".__($guid, $rowType['name']).'</span>';
            }
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
            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/library_manage_catalog_edit.php&gibbonLibraryItemID='.$row['gibbonLibraryItemID']."&name=$name&gibbonLibraryTypeID=$gibbonLibraryTypeID&gibbonSpaceID=$gibbonSpaceID&status=$status&gibbonPersonIDOwnership=$gibbonPersonIDOwnership&typeSpecificFields=".urlencode($typeSpecificFields)."'><img title='".__($guid, 'Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/library_lending_item.php&gibbonLibraryItemID='.$row['gibbonLibraryItemID']."&name=$name&gibbonLibraryTypeID=$gibbonLibraryTypeID&gibbonSpaceID=$gibbonSpaceID&status=$status&gibbonPersonIDOwnership=$gibbonPersonIDOwnership&typeSpecificFields=".urlencode($typeSpecificFields)."'><img title='".__($guid, 'Lending')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/attendance.png'/></a> ";
            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/library_manage_catalog_delete.php&gibbonLibraryItemID='.$row['gibbonLibraryItemID']."&name=$name&gibbonLibraryTypeID=$gibbonLibraryTypeID&gibbonSpaceID=$gibbonSpaceID&status=$status&gibbonPersonIDOwnership=$gibbonPersonIDOwnership&typeSpecificFields=".urlencode($typeSpecificFields)."'><img style='margin-right: 2px' title='".__($guid, 'Delete')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/garbage.png'/></a>";
            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/library_manage_catalog_duplicate.php&gibbonLibraryItemID='.$row['gibbonLibraryItemID']."&name=$name&gibbonLibraryTypeID=$gibbonLibraryTypeID&gibbonSpaceID=$gibbonSpaceID&status=$status&gibbonPersonIDOwnership=$gibbonPersonIDOwnership&typeSpecificFields=".urlencode($typeSpecificFields)."'><img title='".__($guid, 'Duplicate')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/copy.png'/></a>";
            echo '</td>';
            echo '</tr>';

            ++$count;
        }
        echo '</table>';

        if ($result->rowCount() > $_SESSION[$guid]['pagination']) {
            printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]['pagination'], 'bottom', "name=$name&gibbonLibraryTypeID=$gibbonLibraryTypeID&gibbonSpaceID=$gibbonSpaceID&status=$status");
        }
    }
}
?>