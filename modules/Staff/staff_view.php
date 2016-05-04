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

if (isActionAccessible($guid, $connection2, '/modules/Staff/staff_view.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        echo "<div class='error'>";
        echo __($guid, 'The highest grouped action cannot be determined.');
        echo '</div>';
    } else {
        //Proceed!
        echo "<div class='trail'>";
        echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'View Staff Profiles').'</div>';
        echo '</div>';

        $search = null;
        if (isset($_GET['search'])) {
            $search = $_GET['search'];
        }
        $allStaff = '';
        if (isset($_GET['allStaff'])) {
            $allStaff = $_GET['allStaff'];
        }

        echo '<h2>';
        echo __($guid, 'Search');
        echo '</h2>';
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
				<?php if ($highestAction == 'View Staff Profile_full') {
    ?>
					<tr>
						<td> 
							<b><?php echo __($guid, 'All Staff') ?></b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Include all staff, regardless of status, start date, end date, etc.') ?></span>
						</td>
						<td class="right">
							<?php
                            $checked = '';
    if ($allStaff == 'on') {
        $checked = 'checked';
    }
    echo "<input $checked name=\"allStaff\" id=\"allStaff\" type=\"checkbox\">";
    ?>
						</td>
					</tr>
				<?php 
}
        ?>
				<tr>
					<td colspan=2 class="right">
						<input type="hidden" name="q" value="/modules/<?php echo $_SESSION[$guid]['module'] ?>/staff_view.php">
						<input type="hidden" name="address" value="<?php echo $_SESSION[$guid]['address'] ?>">
						<?php
                        echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/staff_view.php'>".__($guid, 'Clear Search').'</a>';
        ?>
						<input type="submit" value="<?php echo __($guid, 'Submit'); ?>">
					</td>
				</tr>
			</table>
		</form>
		<?php

        echo '<h2>';
        echo __($guid, 'Choose A Staff Member');
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
            if ($allStaff != 'on') {
                $data = array();
                $sql = "SELECT gibbonPerson.gibbonPersonID, status, surname, preferredName, initials, type, gibbonStaff.jobTitle FROM gibbonPerson JOIN gibbonStaff ON (gibbonStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') ORDER BY surname, preferredName";
                if ($search != '') {
                    $data = array('search1' => "%$search%", 'search2' => "%$search%", 'search3' => "%$search%");
                    $sql = "SELECT gibbonPerson.gibbonPersonID, status, surname, preferredName, initials, type, gibbonStaff.jobTitle FROM gibbonPerson JOIN gibbonStaff ON (gibbonStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND (preferredName LIKE :search1 OR surname LIKE :search2 OR username LIKE :search3) ORDER BY surname, preferredName";
                }
            } else {
                $data = array();
                $sql = 'SELECT gibbonPerson.gibbonPersonID, status, surname, preferredName, initials, type, gibbonStaff.jobTitle FROM gibbonPerson JOIN gibbonStaff ON (gibbonStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) ORDER BY surname, preferredName';
                if ($search != '') {
                    $data = array('search1' => "%$search%", 'search2' => "%$search%", 'search3' => "%$search%");
                    $sql = 'SELECT gibbonPerson.gibbonPersonID, status, surname, preferredName, initials, type, gibbonStaff.jobTitle FROM gibbonPerson JOIN gibbonStaff ON (gibbonStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE (preferredName LIKE :search1 OR surname LIKE :search2 OR username LIKE :search3) ORDER BY surname, preferredName';
                }
            }
            $sqlPage = $sql.' LIMIT '.$_SESSION[$guid]['pagination'].' OFFSET '.(($page - 1) * $_SESSION[$guid]['pagination']);
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        if ($result->rowcount() < 1) {
            echo "<div class='error'>";
            echo __($guid, 'There are no records to display.');
            echo '</div>';
        } else {
            if ($result->rowcount() > $_SESSION[$guid]['pagination']) {
                printPagination($guid, $result->rowcount(), $page, $_SESSION[$guid]['pagination'], 'top', "search=$search");
            }

            echo "<table cellspacing='0' style='width: 100%'>";
            echo "<tr class='head'>";
            echo '<th>';
            echo __($guid, 'Name').'<br/>';
            echo "<span style='font-size: 85%; font-style: italic'>".__($guid, 'Initials').'</span>';
            echo '</th>';
            echo '<th>';
            echo __($guid, 'Staff Type');
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
                echo formatName('', $row['preferredName'], $row['surname'], 'Student', true).'<br/>';
                echo "<span style='font-size: 85%; font-style: italic'>".$row['initials'].'</span>';
                echo '</td>';
                echo '<td>';
                echo $row['type'];
                echo '</td>';
                echo '<td>';
                echo $row['jobTitle'];
                echo '</td>';
                echo '<td>';
                echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/staff_view_details.php&gibbonPersonID='.$row['gibbonPersonID']."&search=$search&allStaff=$allStaff'><img title='".__($guid, 'View Details')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/plus.png'/></a> ";
                echo '</td>';
                echo '</tr>';
            }
            echo '</table>';

            if ($result->rowcount() > $_SESSION[$guid]['pagination']) {
                printPagination($guid, $result->rowcount(), $page, $_SESSION[$guid]['pagination'], 'bottom', "search=$search");
            }
        }
    }
}
?>