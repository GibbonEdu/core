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

if (isActionAccessible($guid, $connection2, '/modules/Rubrics/rubrics_view.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'View Rubrics').'</div>';
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

    //Filter variables
    $and = '';
    $data = array();
    $filter2 = null;
    if (isset($_POST['filter2'])) {
        $filter2 = $_POST['filter2'];
    }
    if ($filter2 != '') {
        $data['gibbonDepartmentID'] = $filter2;
        $and .= ' AND gibbonDepartmentID=:gibbonDepartmentID';
    }

    try {
        $role = getRoleCategory($_SESSION[$guid]['gibbonRoleIDCurrent'], $connection2);
        if ($role == 'Student') {
            //Get enrolment
            try {
                $dataEnrolment = array('gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                $sqlEnrolment = 'SELECT * FROM gibbonStudentEnrolment WHERE gibbonPersonID=:gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID';
                $resultEnrolment = $connection2->prepare($sqlEnrolment);
                $resultEnrolment->execute($dataEnrolment);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }
            if ($resultEnrolment->rowCount() == 1) {
                $rowEnrolment = $resultEnrolment->fetch();
                $data = array('gibbonSchoolYearID' => '%'.$rowEnrolment['gibbonYearGroupID'].'%');
                $sql = "SELECT * FROM gibbonRubric WHERE active='Y' AND gibbonYearGroupIDList LIKE :gibbonSchoolYearID  $and ORDER BY scope, category, name";
            } else {
                $sql = "SELECT * FROM gibbonRubric WHERE active='Y' $and ORDER BY scope, category, name";
            }
        } else {
            $sql = "SELECT * FROM gibbonRubric WHERE active='Y' $and ORDER BY scope, category, name";
        }
        $sqlPage = $sql.' LIMIT '.$_SESSION[$guid]['pagination'].' OFFSET '.(($page - 1) * $_SESSION[$guid]['pagination']);
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        echo "<div class='error'>".$e->getMessage().'</div>';
    }

    echo '<h3>';
    echo __($guid, 'Filter');
    echo '</h3>';
    echo "<form method='post' action='".$_SESSION[$guid]['absoluteURL'].'/index.php?q='.$_GET['q']."'>";
    echo"<table class='noIntBorder' cellspacing='0' style='width: 100%'>";
    ?>
			<tr>
				<td> 
					<b><?php echo __($guid, 'Learning Areas') ?></b><br/>
					<span class="emphasis small"></span>
				</td>
				<td class="right">
					<?php
                    echo "<select name='filter2' id='filter2' style='width:302px'>";
    echo "<option value=''>".__($guid, 'All Learning Areas').'</option>';
    try {
        $dataSelect = array();
        $sqlSelect = "SELECT * FROM gibbonDepartment WHERE type='Learning Area' ORDER BY name";
        $resultSelect = $connection2->prepare($sqlSelect);
        $resultSelect->execute($dataSelect);
    } catch (PDOException $e) {
    }
    while ($rowSelect = $resultSelect->fetch()) {
        $selected = '';
        if ($rowSelect['gibbonDepartmentID'] == $filter2) {
            $selected = 'selected';
        }
        echo "<option $selected value='".$rowSelect['gibbonDepartmentID']."'>".$rowSelect['name'].'</option>';
    }
    echo '</select>';?>
				</td>
			</tr>
			<?php
            echo '<tr>';
    echo "<td class='right' colspan=2>";
    echo "<input type='hidden' name='q' value='".$_GET['q']."'>";
    echo "<input type='submit' value='".__($guid, 'Go')."'>";
    echo '</td>';
    echo '</tr>';
    echo'</table>';
    echo '</form>';

    echo '<h3>';
    echo __($guid, 'Rubrics');
    echo '</h3>';
    if ($result->rowCount() < 1) {
        echo "<div class='error'>";
        echo __($guid, 'There are no records to display.');
        echo '</div>';
    } else {
        if ($result->rowCount() > $_SESSION[$guid]['pagination']) {
            printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]['pagination'], 'top');
        }

        echo "<table cellspacing='0' style='width: 100%'>";
        echo "<tr class='head'>";
        echo '<th>';
        echo __($guid, 'Scope');
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Category');
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Name');
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Year Groups');
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

            //COLOR ROW BY STATUS!
            echo "<tr class=$rowNum>";
            echo '<td>';
            echo '<b>'.$row['scope'].'</b><br/>';
            if ($row['scope'] == 'Learning Area' and $row['gibbonDepartmentID'] != '') {
                try {
                    $dataLearningArea = array('gibbonDepartmentID' => $row['gibbonDepartmentID']);
                    $sqlLearningArea = 'SELECT * FROM gibbonDepartment WHERE gibbonDepartmentID=:gibbonDepartmentID';
                    $resultLearningArea = $connection2->prepare($sqlLearningArea);
                    $resultLearningArea->execute($dataLearningArea);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }
                if ($resultLearningArea->rowCount() == 1) {
                    $rowLearningAreas = $resultLearningArea->fetch();
                    echo "<span style='font-size: 75%; font-style: italic'>".$rowLearningAreas['name'].'</span>';
                }
            }
            echo '</td>';
            echo '<td>';
            echo '<b>'.$row['category'].'</b><br/>';
            echo '</td>';
            echo '<td>';
            echo '<b>'.$row['name'].'</b><br/>';
            echo '</td>';
            echo '<td>';
            echo getYearGroupsFromIDList($guid, $connection2, $row['gibbonYearGroupIDList']);
            echo '</td>';
            echo '<td>';
            echo "<script type='text/javascript'>";
            echo '$(document).ready(function(){';
            echo "\$(\".description-$count\").hide();";
            echo "\$(\".show_hide-$count\").fadeIn(1000);";
            echo "\$(\".show_hide-$count\").click(function(){";
            echo "\$(\".description-$count\").fadeToggle(1000);";
            echo '});';
            echo '});';
            echo '</script>';

            echo "<a class='thickbox' href='".$_SESSION[$guid]['absoluteURL'].'/fullscreen.php?q=/modules/'.$_SESSION[$guid]['module'].'/rubrics_view_full.php&gibbonRubricID='.$row['gibbonRubricID']."&width=1100&height=550'><img title='".__($guid, 'View')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/plus.png'/></a> ";
            echo '</td>';
            echo '</tr>';
            if ($row['description'] != '') {
                echo "<tr class='description-$count' id='description-$count'>";
                echo '<td colspan=6>';
                echo $row['description'];
                echo '</td>';
                echo '</tr>';
            }
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