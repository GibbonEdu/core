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

if (isActionAccessible($guid, $connection2, '/modules/Library/report_viewOverdueItems.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'View Overdue Items').'</div>';
    echo '</div>';

    echo '<h2>';
    echo __($guid, 'Filter');
    echo '</h2>';

    $ignoreStatus = '';
    if (isset($_GET['ignoreStatus'])) {
        $ignoreStatus = $_GET['ignoreStatus'];
    }

    ?>
	<form method="get" action="<?php echo $_SESSION[$guid]['absoluteURL']?>/index.php">
		<table class='noIntBorder' cellspacing='0' style="width: 100%">
			<tr><td style="width: 30%"></td><td></td></tr>
			<tr>
				<td>
					<b><?php echo __($guid, 'Ignore Status') ?></b><br/>
					<span class="emphasis small"><?php echo __($guid, 'Include all users, regardless of status and current enrolment.') ?></span>
				</td>
				<td class="right">
					<?php
                        $checked = '';
					if ($ignoreStatus == 'on') {
						$checked = 'checked';
					}
					echo "<input $checked name=\"ignoreStatus\" id=\"ignoreStatus\" type=\"checkbox\">"; ?>
				</td>
			</tr>

			<tr>
				<td colspan=2 class="right">
					<input type="hidden" name="q" value="/modules/<?php echo $_SESSION[$guid]['module'] ?>/report_viewOverdueItems.php">
					<input type="hidden" name="address" value="<?php echo $_SESSION[$guid]['address'] ?>">
					<?php
                        echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/report_viewOverdueItems.php'>".__($guid, 'Clear Search').'</a>';?>
					<input type="submit" value="<?php echo __($guid, 'Submit'); ?>">
				</td>
			</tr>
		</table>
	</form>

	<?php
    echo '<h2>';
    echo __($guid, 'Report Data');
    echo '</h2>';

    $today = date('Y-m-d');

    try {
        $data = array('today' => $today);
        if ($ignoreStatus == 'on') {
            $sql = "SELECT gibbonLibraryItem.*, surname, preferredName, email FROM gibbonLibraryItem JOIN gibbonPerson ON (gibbonLibraryItem.gibbonPersonIDStatusResponsible=gibbonPerson.gibbonPersonID) WHERE gibbonLibraryItem.status='On Loan' AND borrowable='Y' AND returnExpected<:today ORDER BY surname, preferredName";
        } else {
            $sql = "SELECT gibbonLibraryItem.*, surname, preferredName, email FROM gibbonLibraryItem JOIN gibbonPerson ON (gibbonLibraryItem.gibbonPersonIDStatusResponsible=gibbonPerson.gibbonPersonID) WHERE gibbonLibraryItem.status='On Loan' AND borrowable='Y' AND returnExpected<:today AND gibbonPerson.status='Full' ORDER BY surname, preferredName";
        }
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        echo "<div class='error'>".$e->getMessage().'</div>';
    }

    echo "<table cellspacing='0' style='width: 100%'>";
    echo "<tr class='head'>";
    echo '<th>';
    echo __($guid, 'Borrowing User');
    echo '</th>';
    echo '<th>';
    echo __($guid, 'Email');
    echo '</th>';
    echo '<th>';
    echo __($guid, 'Item').'<br/>';
    echo "<span style='font-size: 85%; font-style: italic'>".__($guid, 'Author/Producer').'</span>';
    echo '</th>';
    echo '<th>';
    echo __($guid, 'Due Date');
    echo '</th>';
    echo '<th>';
    echo __($guid, 'Days Overdue');
    echo '</th>';
    echo "<th style='width: 50px'>";
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
        echo formatName('', $row['preferredName'], $row['surname'], 'Student', true);
        echo '</td>';
        echo '<td>';
        echo $row['email'];
        echo '</td>';
        echo '<td>';
        echo '<b>'.$row['name'].'</b><br/>';
        echo "<span style='font-size: 85%; font-style: italic'>".$row['producer'].'</span>';
        echo '</td>';
        echo '<td>';
        echo dateConvertBack($guid, $row['returnExpected']);
        echo '</td>';
        echo '<td>';
        echo(strtotime($today) - strtotime($row['returnExpected'])) / (60 * 60 * 24);
        echo '</td>';
        echo '<td>';
        echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/library_lending_item.php&gibbonLibraryItemID='.$row['gibbonLibraryItemID']."&name=&gibbonLibraryTypeID=&gibbonSpaceID=&status='><img title='".__($guid, 'Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
        echo '</td>';
        echo '</tr>';
    }
    if ($count == 0) {
        echo "<tr class=$rowNum>";
        echo '<td colspan=6>';
        echo __($guid, 'There are no records to display.');
        echo '</td>';
        echo '</tr>';
    }
    echo '</table>';
}
?>
