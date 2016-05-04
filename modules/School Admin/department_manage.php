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

if (isActionAccessible($guid, $connection2, '/modules/School Admin/department_manage.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Manage Departments').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    echo '<h3>';
    echo __($guid, 'Department Access');
    echo '</h3>';

    ?>
	<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/department_manageProcess.php' ?>">
		<table class='smallIntBorder fullWidth' cellspacing='0'>	
			<tr>
				<?php
                try {
                    $data = array();
                    $sql = "SELECT * FROM gibbonSetting WHERE scope='Departments' AND name='makeDepartmentsPublic'";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {}
                $row = $result->fetch();
                ?>
				<td style='width: 275px'> 
					<b><?php echo __($guid, $row['nameDisplay']) ?> *</b><br/>
					<span class="emphasis small"><?php if ($row['description'] != '') {
    echo __($guid, $row['description']);}?></span>
				</td>
				<td class="right">
					<select name="<?php echo $row['name'] ?>" id="<?php echo $row['name'] ?>" class="standardWidth">
						<option <?php if ($row['value'] == 'N') { echo 'selected '; }
    ?>value="N"><?php echo __($guid, 'No') ?></option>
						<option <?php if ($row['value'] == 'Y') { echo 'selected '; }
    ?>value="Y"><?php echo __($guid, 'Yes') ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<td>
					<span class="emphasis small">* <?php echo __($guid, 'denotes a required field'); ?></span>
				</td>
				<td class="right">
					<input type="hidden" name="address" value="<?php echo $_SESSION[$guid]['address'] ?>">
					<input type="submit" value="<?php echo __($guid, 'Submit'); ?>">
				</td>
			</tr>
		</table>
	</form>
	<?php

    echo '<h3>';
    echo __($guid, 'Departments');
    echo '</h3>';

    try {
        $data = array();
        $sql = 'SELECT * FROM gibbonDepartment ORDER BY name';
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        echo "<div class='error'>".$e->getMessage().'</div>';
    }

    echo "<div class='linkTop'>";
    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/department_manage_add.php'>".__($guid, 'Add')."<img style='margin-left: 5px' title='".__($guid, 'Add')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/page_new.png'/></a>";
    echo '</div>';

    if ($result->rowCount() < 1) {
        echo "<div class='error'>";
        echo __($guid, 'There are no records to display.');
        echo '</div>';
    } else {
        echo "<table cellspacing='0' style='width: 100%'>";
        echo "<tr class='head'>";
        echo '<th>';
        echo __($guid, 'Name');
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Type');
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Short Name');
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Staff');
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Actions');
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
            echo $row['name'];
            echo '</td>';
            echo '<td>';
            echo $row['type'];
            echo '</td>';
            echo '<td>';
            echo $row['nameShort'];
            echo '</td>';
            echo '<td>';
            try {
                $dataCoord = array('gibbonDepartmentID' => $row['gibbonDepartmentID']);
                $sqlCoord = "SELECT preferredName, surname FROM gibbonDepartmentStaff JOIN gibbonPerson ON (gibbonDepartmentStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPerson.status='Full' AND gibbonDepartmentID=:gibbonDepartmentID ORDER BY surname, preferredName";
                $resultCoord = $connection2->prepare($sqlCoord);
                $resultCoord->execute($dataCoord);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }
            if ($resultCoord->rowCount() > 0) {
                while ($rowCoord = $resultCoord->fetch()) {
                    echo formatName('', $rowCoord['preferredName'], $rowCoord['surname'], 'Staff', true, true).'<br/>';
                }
            } else {
                echo '<i>'.__($guid, 'None').'</i>';
            }
            echo '</td>';
            echo '<td>';
            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/department_manage_edit.php&gibbonDepartmentID='.$row['gibbonDepartmentID']."'><img title='".__($guid, 'Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/department_manage_delete.php&gibbonDepartmentID='.$row['gibbonDepartmentID']."'><img title='".__($guid, 'Delete')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/garbage.png'/></a> ";
            echo '</td>';
            echo '</tr>';
        }
        echo '</table>';
    }
}
?>