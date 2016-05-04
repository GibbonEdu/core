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

if (isActionAccessible($guid, $connection2, '/modules/Individual Needs/in_view.php') == false) {
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
        echo "<div class='trail'>";
        echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'View Student Records').'</div>';
        echo '</div>';

        echo '<h2>';
        echo __($guid, 'Search');
        echo '</h2>';

        $gibbonPersonID = null;
        if (isset($_GET['gibbonPersonID'])) {
            $gibbonPersonID = $_GET['gibbonPersonID'];
        }
        $search = null;
        if (isset($_GET['search'])) {
            $search = $_GET['search'];
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
					<td colspan=2 class="right">
						<input type="hidden" name="q" value="/modules/<?php echo $_SESSION[$guid]['module'] ?>/in_view.php">
						<input type="hidden" name="address" value="<?php echo $_SESSION[$guid]['address'] ?>">
						<?php
                        echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/in_view.php'>".__($guid, 'Clear Search').'</a>';
        ?>
						<input type="submit" value="<?php echo __($guid, 'Submit'); ?>">
					</td>
				</tr>
			</table>
		</form>
		<?php

        echo '<h2>';
        echo __($guid, 'Choose A Student');
        echo '</h2>';
        echo '<p>';
        echo __($guid, 'This page displays all students enroled in the school, including those who have not yet met their start date. With the right permissions, you can set Individual Needs status and Individual Education Plan details for any student.');
        echo '</p>';

        //Set pagination variable
        $page = 1;
        if (isset($_GET['page'])) {
            $page = $_GET['page'];
        }
        if ((!is_numeric($page)) or $page < 1) {
            $page = 1;
        }

        try {
            $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
            $sql = "SELECT gibbonPerson.gibbonPersonID, gibbonStudentEnrolmentID, surname, preferredName, gibbonYearGroup.nameShort AS yearGroup, gibbonRollGroup.nameShort AS rollGroup, dateStart, dateEnd FROM gibbonPerson, gibbonStudentEnrolment, gibbonYearGroup, gibbonRollGroup WHERE (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) AND (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID) AND (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) AND gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPerson.status='Full' ORDER BY surname, preferredName";
            if ($search != '') {
                $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'search1' => "%$search%", 'search2' => "%$search%", 'search3' => "%$search%");
                $sql = "SELECT gibbonPerson.gibbonPersonID, gibbonStudentEnrolmentID, surname, preferredName, gibbonYearGroup.nameShort AS yearGroup, gibbonRollGroup.nameShort AS rollGroup, dateStart, dateEnd FROM gibbonPerson, gibbonStudentEnrolment, gibbonYearGroup, gibbonRollGroup WHERE (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) AND (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID) AND (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) AND gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID AND (preferredName LIKE :search1 OR surname LIKE :search2 OR username LIKE :search3) AND gibbonPerson.status='Full' ORDER BY surname, preferredName";
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
                printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]['pagination'], 'top', "search=$search");
            }

            echo "<table cellspacing='0' style='width: 100%'>";
            echo "<tr class='head'>";
            echo '<th>';
            echo __($guid, 'Name');
            echo '</th>';
            echo '<th>';
            echo __($guid, 'Year Group');
            echo '</th>';
            echo '<th>';
            echo __($guid, 'Roll Group');
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
                ++$count;

                    //Color rows based on start and end date
                    if (!($row['dateStart'] == '' or $row['dateStart'] <= date('Y-m-d')) and ($row['dateEnd'] == '' or $row['dateEnd'] >= date('Y-m-d'))) {
                        $rowNum = 'error';
                    }

                //COLOR ROW BY STATUS!
                echo "<tr class=$rowNum>";
                echo '<td>';
                echo formatName('', $row['preferredName'], $row['surname'], 'Student', true);
                echo '</td>';
                echo '<td>';
                echo __($guid, $row['yearGroup']);
                echo '</td>';
                echo '<td>';
                echo $row['rollGroup'];
                echo '</td>';
                echo '<td>';
                if ($highestAction == 'Individual Needs Records_view') {
                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/in_edit.php&gibbonPersonID='.$row['gibbonPersonID']."&search=$search'><img title='View Individual Needs Details' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/plus.png'/></a> ";
                } elseif ($highestAction == 'Individual Needs Records_viewEdit' or $highestAction == 'Individual Needs Records_viewContribute') {
                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/in_edit.php&gibbonPersonID='.$row['gibbonPersonID']."&search=$search'><img title='Edit Individual Needs Details' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
                }
                echo '</td>';
                echo '</tr>';
            }
            echo '</table>';

            if ($result->rowCount() > $_SESSION[$guid]['pagination']) {
                printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]['pagination'], 'bottom', "&search=$search");
            }
        }
    }
}
?>