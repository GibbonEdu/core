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

if (isActionAccessible($guid, $connection2, '/modules/Staff/staff_manage.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Manage Staff').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $search = '';
    if (isset($_GET['search'])) {
        $search = $_GET['search'];
    }
    $allStaff = '';
    if (isset($_GET['allStaff'])) {
        $allStaff = $_GET['allStaff'];
    }

    echo '<h2>';
    echo __($guid, 'Search & Filter');
    echo '</h2>';?>
	<form method="get" action="<?php echo $_SESSION[$guid]['absoluteURL']?>/index.php">
		<table class='noIntBorder' cellspacing='0' style="width: 100%">
			<tr><td style="width: 30%"></td><td></td></tr>
			<tr>
				<td>
					<b><?php echo __($guid, 'Search For') ?></b><br/>
					<span class="emphasis small"><?php echo __($guid, 'Preferred, surname, username.') ?></span>
				</td>
				<td class="right">
					<input name="search" id="search" maxlength=20 value="<?php if (isset($_GET['search'])) { echo $_GET['search']; } ?>" type="text" class="standardWidth">
				</td>
			</tr>
			<tr>
				<td>
					<b><?php echo __($guid, 'All Staff') ?></b><br/>
					<span class="emphasis small"><?php echo __($guid, 'Include Expected and Left.') ?></span>
				</td>
				<td class="right">
					<?php
                    $checked = '';
					if ($allStaff == 'on') {
						$checked = 'checked';
					}
					echo "<input $checked name=\"allStaff\" id=\"allStaff\" type=\"checkbox\">"; ?>
				</td>
			</tr>
			<tr>
				<td colspan=2 class="right">
					<input type="hidden" name="q" value="/modules/<?php echo $_SESSION[$guid]['module'] ?>/staff_manage.php">
					<input type="hidden" name="address" value="<?php echo $_SESSION[$guid]['address'] ?>">
					<?php
                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/staff_manage.php'>".__($guid, 'Clear Search').'</a>';?>
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
        $data = array();
        $where = '';
        if ($allStaff != 'on') {
            $where = "WHERE status='Full'";
        }
        $sql = "SELECT gibbonStaffID, surname, preferredName, initials, type, gibbonStaff.jobTitle, status FROM gibbonPerson JOIN gibbonStaff ON (gibbonPerson.gibbonPersonID=gibbonStaff.gibbonPersonID) $where ORDER BY surname, preferredName";
        if ($search != '') {
            if ($allStaff != 'on') {
                $where = "AND status='Full'";
            }
            $data = array('search1' => "%$search%", 'search2' => "%$search%", 'search3' => "%$search%");
            $sql = "SELECT gibbonStaffID, surname, preferredName, initials, type, gibbonStaff.jobTitle, status FROM gibbonPerson JOIN gibbonStaff ON (gibbonPerson.gibbonPersonID=gibbonStaff.gibbonPersonID) WHERE (preferredName LIKE :search1 OR surname LIKE :search2 OR (username LIKE :search3)) $where ORDER BY surname, preferredName";
        }
        $sqlPage = $sql.' LIMIT '.$_SESSION[$guid]['pagination'].' OFFSET '.(($page - 1) * $_SESSION[$guid]['pagination']);
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        echo "<div class='error'>".$e->getMessage().'</div>';
    }

    echo "<div class='linkTop'>";
    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/staff_manage_add.php&search=$search&allStaff=$allStaff'>".__($guid, 'Add')."<img style='margin-left: 5px' title='".__($guid, 'Add')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/page_new.png'/></a>";
    echo '</div>';

    if ($result->rowCount() < 1) {
        echo "<div class='error'>";
        echo __($guid, 'There are no records to display.');
        echo '</div>';
    } else {
        if ($result->rowCount() > $_SESSION[$guid]['pagination']) {
            printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]['pagination'], 'top', "allStaff=$allStaff&search=$search");
        }

        echo "<table cellspacing='0' style='width: 100%'>";
        echo "<tr class='head'>";
        echo '<th>';
        echo __($guid, 'Name').'<br/>';
        echo "<span style='font-size: 85%; font-style: italic'>".__($guid, 'Initials').'</span>';
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Type');
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Status');
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Job Title');
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
            if ($row['status'] != 'Full') {
                $rowNum = 'error';
            }
            ++$count;

            //COLOR ROW BY STATUS!
            echo "<tr class=$rowNum>";
            echo '<td>';
            echo formatName('', $row['preferredName'], $row['surname'], 'Staff', true, true).'<br/>';
            echo "<span style='font-size: 85%; font-style: italic'>".$row['initials'].'</span>';
            echo '</td>';
            echo '<td>';
            echo $row['type'];
            echo '</td>';
            echo '<td>';
            echo $row['status'];
            echo '</td>';
            echo '<td>';
            echo $row['jobTitle'];
            echo '</td>';
            echo '<td>';
            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/staff_manage_edit.php&gibbonStaffID='.$row['gibbonStaffID']."&search=$search&allStaff=$allStaff'><img title='".__($guid, 'Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
            echo "<a class='thickbox' href='".$_SESSION[$guid]['absoluteURL'].'/fullscreen.php?q=/modules/'.$_SESSION[$guid]['module'].'/staff_manage_delete.php&gibbonStaffID='.$row['gibbonStaffID']."&search=$search&allStaff=$allStaff&width=650&height=135'><img title='".__($guid, 'Delete')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/garbage.png'/></a>";
            echo '</td>';
            echo '</tr>';
        }
        echo '</table>';

        if ($result->rowCount() > $_SESSION[$guid]['pagination']) {
            printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]['pagination'], 'bottom', "allStaff=$allStaff&search=$search");
        }
    }
}
?>
