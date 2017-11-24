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

if (isActionAccessible($guid, $connection2, '/modules/Staff/applicationForm_manage.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Manage Applications').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    //Set pagination variable
    $page = 1;
    if (isset($_GET['page'])) {
        $page = $_GET['page'];
    }
    if ((!is_numeric($page)) or $page < 1) {
        $page = 1;
    }

    $search = '';
    if (isset($_GET['search'])) {
        $search = $_GET['search'];
    }

    echo '<h4>';
    echo __($guid, 'Search');
    echo '</h2>';?>
	<form method="get" action="<?php echo $_SESSION[$guid]['absoluteURL']?>/index.php">
		<table class='noIntBorder' cellspacing='0' style="width: 100%">
			<tr><td style="width: 40%"></td><td></td></tr>
			<tr>
				<td>
					<b><?php echo __($guid, 'Search For') ?></b><br/>
					<span class="emphasis small"><?php echo __($guid, 'Application ID, preferred, surname') ?></span>
				</td>
				<td class="right">
					<input name="search" id="search" maxlength=20 value="<?php echo $search ?>" type="text" class="standardWidth">
				</td>
			</tr>
			<tr>
				<td colspan=2 class="right">
					<input type="hidden" name="q" value="/modules/<?php echo $_SESSION[$guid]['module'] ?>/applicationForm_manage.php">
					<input type="hidden" name="gibbonSchoolYearID" value="<?php echo $gibbonSchoolYearID ?>">
					<input type="hidden" name="address" value="<?php echo $_SESSION[$guid]['address'] ?>">
					<?php
                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/applicationForm_manage.php'>".__($guid, 'Clear Search').'</a>';?>
					<input type="submit" value="<?php echo __($guid, 'Submit'); ?>">
				</td>
			</tr>
		</table>
	</form>
	<?php

    echo '<h4>';
    echo __($guid, 'View');
    echo '</h2>';

    try {
        $data = array();
        $sql = 'SELECT gibbonStaffApplicationForm.*, gibbonStaffJobOpening.jobTitle FROM gibbonStaffApplicationForm JOIN gibbonStaffJobOpening ON (gibbonStaffApplicationForm.gibbonStaffJobOpeningID=gibbonStaffJobOpening.gibbonStaffJobOpeningID) LEFT JOIN gibbonPerson ON (gibbonStaffApplicationForm.gibbonPersonID=gibbonPerson.gibbonPersonID) ORDER BY gibbonStaffApplicationForm.status, priority DESC, timestamp DESC';
        if ($search != '') {
            $data = array('search' => "%$search%", 'search1' => "%$search%", 'search2' => "%$search%", 'search3' => "%$search%", 'search4' => "%$search%");
            $sql = 'SELECT gibbonStaffApplicationForm.*, gibbonStaffJobOpening.jobTitle FROM gibbonStaffApplicationForm JOIN gibbonStaffJobOpening ON (gibbonStaffApplicationForm.gibbonStaffJobOpeningID=gibbonStaffJobOpening.gibbonStaffJobOpeningID) LEFT JOIN gibbonPerson ON (gibbonStaffApplicationForm.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE (gibbonStaffApplicationFormID LIKE :search OR gibbonStaffApplicationForm.preferredName LIKE :search1 OR gibbonStaffApplicationForm.surname LIKE :search2 OR gibbonPerson.preferredName LIKE :search3 OR gibbonPerson.surname LIKE :search4) ORDER BY gibbonStaffApplicationForm.status, priority DESC, timestamp DESC';
        }
        $sqlPage = $sql.' LIMIT '.$_SESSION[$guid]['pagination'].' OFFSET '.(($page - 1) * $_SESSION[$guid]['pagination']);
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        echo "<div class='error'>".$e->getMessage().'</div>';
    }

    if ($result->rowCount() < 1) {
        echo "<div class='error'>";
        echo 'There are no records display.';
        echo '</div>';
    } else {
        if ($result->rowCount() > $_SESSION[$guid]['pagination']) {
            printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]['pagination'], 'top', "search=$search");
        }

        echo "<table cellspacing='0' style='width: 100%'>";
        echo "<tr class='head'>";
        echo '<th>';
        echo __($guid, 'ID');
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Applicant')."<br/><span style='font-style: italic; font-size: 85%'>".__($guid, 'Application Date').'</span>';
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Position');
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Status')."<br/><span style='font-style: italic; font-size: 85%'>".__($guid, 'Milestones').'</span>';
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Priority');
        echo '</th>';
        echo "<th style='width: 80px'>";
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

            if ($row['status'] == 'Accepted') {
                $rowNum = 'current';
            } elseif ($row['status'] == 'Rejected' or $row['status'] == 'Withdrawn') {
                $rowNum = 'error';
            }

            ++$count;

            //COLOR ROW BY STATUS!
            echo "<tr class=$rowNum>";
            echo '<td>';
            echo ltrim($row['gibbonStaffApplicationFormID'], '0');
            echo '</td>';
            echo '<td>';
            if ($row['gibbonPersonID'] != null and isActionAccessible($guid, $connection2, '/modules/Staff/staff_view.php')) {
                echo "<b><a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Staff/staff_view_details.php&gibbonPersonID='.$row['gibbonPersonID']."'>".formatName('', $row['preferredName'], $row['surname'], 'Student', true).'</a></b><br/>';
            } else {
                echo '<b>'.formatName('', $row['preferredName'], $row['surname'], 'Student', true).'</b><br/>';
            }
            echo "<span style='font-style: italic; font-size: 85%'>".dateConvertBack($guid, substr($row['timestamp'], 0, 10)).'</span>';
            echo '</td>';
            echo '<td>';
            echo $row['jobTitle'];
            echo '</td>';
            echo '<td>';
            echo '<b>'.$row['status'].'</b>';
            if ($row['status'] == 'Pending') {
                $milestones = explode(',', $row['milestones']);
                foreach ($milestones as $milestone) {
                    echo "<br/><span style='font-style: italic; font-size: 85%'>".trim($milestone).'</span>';
                }
            }
            echo '</td>';
            echo '<td>';
            echo $row['priority'];
            echo '</td>';
            echo '<td>';
            if ($row['status'] == 'Pending' or $row['status'] == 'Waiting List') {
                echo "<a style='margin-left: 1px' href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/applicationForm_manage_accept.php&gibbonStaffApplicationFormID='.$row['gibbonStaffApplicationFormID']."&search=$search'><img title='".__($guid, 'Accept')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/iconTick.png'/></a>";
                echo "<a style='margin-left: 5px' href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/applicationForm_manage_reject.php&gibbonStaffApplicationFormID='.$row['gibbonStaffApplicationFormID']."&search=$search'><img title='".__($guid, 'Reject')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/iconCross.png'/></a>";
                echo '<br/>';
            }
            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/applicationForm_manage_edit.php&gibbonStaffApplicationFormID='.$row['gibbonStaffApplicationFormID']."&search=$search'><img title='".__($guid, 'Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
            echo " <a class='thickbox' href='".$_SESSION[$guid]['absoluteURL'].'/fullscreen.php?q=/modules/'.$_SESSION[$guid]['module'].'/applicationForm_manage_delete.php&gibbonStaffApplicationFormID='.$row['gibbonStaffApplicationFormID']."&search=$search&width=650&height=135'><img style='margin-left: 4px' title='".__($guid, 'Delete')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/garbage.png'/></a>";

            echo '</td>';
            echo '</tr>';
        }
        echo '</table>';

        if ($result->rowCount() > $_SESSION[$guid]['pagination']) {
            printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]['pagination'], 'bottom', "search=$search");
        }
    }
}
?>
