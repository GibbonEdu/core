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

use Gibbon\Forms\Form;

$page->breadcrumbs->add(__('Manage Outcomes'));

if (isActionAccessible($guid, $connection2, '/modules/Planner/outcomes.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        echo "<div class='error'>";
        echo __('The highest grouped action cannot be determined.');
        echo '</div>';
    } else {
        //Proceed!
        if (isset($_GET['return'])) {
            returnProcess($guid, $_GET['return'], null, null);
        }

        //Set pagination variable
        $page = isset($_GET['page'])? $_GET['page'] : 1;
        if ((!is_numeric($page)) or $page < 1) {
            $page = 1;
        }

        //Filter variables
        $where = '';
        $data = array();

        $filter2 = isset($_GET['filter2'])? $_GET['filter2'] : '';
        if ($filter2 != '') {
            $data['gibbonDepartmentID'] = $filter2;
            $where .= " WHERE gibbonDepartment.gibbonDepartmentID=:gibbonDepartmentID";
        }

        
            $sql = "SELECT gibbonOutcome.*, gibbonDepartment.name AS department FROM gibbonOutcome LEFT JOIN gibbonDepartment ON (gibbonOutcome.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) $where ORDER BY scope, gibbonDepartmentID, category, nameShort";
            $sqlPage = $sql.' LIMIT '.$gibbon->session->get('pagination').' OFFSET '.(($page - 1) * $gibbon->session->get('pagination'));
            $result = $connection2->prepare($sql);
            $result->execute($data);

        echo '<h3>';
        echo __('Filter');
        echo '</h3>';

        $form = Form::create('filter', $gibbon->session->get('absoluteURL').'/index.php', 'get');
        $form->setClass('noIntBorder fullWidth');

        $form->addHiddenValue('q', '/modules/'.$gibbon->session->get('module').'/outcomes.php');

        $sql = "SELECT gibbonDepartmentID as value, name FROM gibbonDepartment WHERE type='Learning Area' ORDER BY name";
        $row = $form->addRow();
            $row->addLabel('filter2', __('Learning Areas'));
            $row->addSelect('filter2')
                ->fromArray(array('' => __('All Learning Areas')))
                ->fromQuery($pdo, $sql)
                ->selected($filter2);

        $row = $form->addRow();
            $row->addSearchSubmit($gibbon->session, __('Clear Filters'));

        echo $form->getOutput();

        echo '<h3>';
        echo __('Outcomes');
        echo '</h3>';

        if ($highestAction == 'Manage Outcomes_viewEditAll' or $highestAction == 'Manage Outcomes_viewAllEditLearningArea') {
            echo "<div class='linkTop'>";
            echo "<a href='".$gibbon->session->get('absoluteURL').'/index.php?q=/modules/'.$gibbon->session->get('module')."/outcomes_add.php&filter2=$filter2'>".__('Add')."<img style='margin-left: 5px' title='".__('Add')."' src='./themes/".$gibbon->session->get('gibbonThemeName')."/img/page_new.png'/></a>";
            echo '</div>';
        }
        if ($result->rowCount() < 1) {
            echo "<div class='error'>";
            echo __('There are no records to display.');
            echo '</div>';
        } else {
            if ($result->rowCount() > $gibbon->session->get('pagination')) {
                printPagination($guid, $result->rowCount(), $page, $gibbon->session->get('pagination'), 'top', "filter2=$filter2");
            }

            echo "<table cellspacing='0' style='width: 100%'>";
            echo "<tr class='head'>";
            echo '<th>';
            echo __('Scope');
            echo '</th>';
            echo '<th>';
            echo __('Category');
            echo '</th>';
            echo '<th>';
            echo __('Name');
            echo '</th>';
            echo '<th>';
            echo __('Year Groups');
            echo '</th>';
            echo '<th>';
            echo __('Active');
            echo '</th>';
            echo "<th style='width: 100px'>";
            echo __('Actions');
            echo '</th>';
            echo '</tr>';

            $count = 0;
            $rowNum = 'odd';
            
                $resultPage = $connection2->prepare($sqlPage);
                $resultPage->execute($data);
            while ($row = $resultPage->fetch()) {
                if ($count % 2 == 0) {
                    $rowNum = 'even';
                } else {
                    $rowNum = 'odd';
                }

                if ($row['active'] != 'Y') {
                    $rowNum = 'error';
                }

                //COLOR ROW BY STATUS!
                echo "<tr class=$rowNum>";
                echo '<td>';
                echo '<b>'.__($row['scope']).'</b><br/>';
                if ($row['scope'] == 'Learning Area' and $row['department'] != '') {
                    echo "<span style='font-size: 75%; font-style: italic'>".$row['department'].'</span>';
                }
                echo '</td>';
                echo '<td>';
                echo '<b>'.$row['category'].'</b><br/>';
                echo '</td>';
                echo '<td>';
                echo '<b>'.$row['nameShort'].'</b><br/>';
                echo "<span style='font-size: 75%; font-style: italic'>".$row['name'].'</span>';
                echo '</td>';
                echo '<td>';
                echo getYearGroupsFromIDList($guid, $connection2, $row['gibbonYearGroupIDList']);
                echo '</td>';
                echo '<td>';
                echo ynExpander($guid, $row['active']);
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

                if ($highestAction == 'Manage Outcomes_viewEditAll') {
                    echo "<a href='".$gibbon->session->get('absoluteURL').'/index.php?q=/modules/'.$gibbon->session->get('module').'/outcomes_edit.php&gibbonOutcomeID='.$row['gibbonOutcomeID']."&filter2=$filter2'><img title='".__('Edit')."' src='./themes/".$gibbon->session->get('gibbonThemeName')."/img/config.png'/></a> ";
                    echo "<a class='thickbox' href='".$gibbon->session->get('absoluteURL').'/fullscreen.php?q=/modules/'.$gibbon->session->get('module').'/outcomes_delete.php&gibbonOutcomeID='.$row['gibbonOutcomeID']."&filter2=$filter2&width=650&height=135'><img title='".__('Delete')."' src='./themes/".$gibbon->session->get('gibbonThemeName')."/img/garbage.png'/></a> ";
                } elseif ($highestAction == 'Manage Outcomes_viewAllEditLearningArea') {
                    if ($row['scope'] == 'Learning Area' and $row['gibbonDepartmentID'] != '') {
                        
                            $dataLearningAreaStaff = array('gibbonDepartmentID' => $row['gibbonDepartmentID'], 'gibbonPersonID' => $gibbon->session->get('gibbonPersonID'));
                            $sqlLearningAreaStaff = "SELECT * FROM gibbonDepartment JOIN gibbonDepartmentStaff ON (gibbonDepartmentStaff.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) WHERE gibbonDepartment.gibbonDepartmentID=:gibbonDepartmentID AND gibbonPersonID=:gibbonPersonID AND (role='Coordinator' OR role='Teacher (Curriculum)')";
                            $resultLearningAreaStaff = $connection2->prepare($sqlLearningAreaStaff);
                            $resultLearningAreaStaff->execute($dataLearningAreaStaff);
                        if ($resultLearningAreaStaff->rowCount() > 0) {
                            echo "<a href='".$gibbon->session->get('absoluteURL').'/index.php?q=/modules/'.$gibbon->session->get('module').'/outcomes_edit.php&gibbonOutcomeID='.$row['gibbonOutcomeID']."&filter2=$filter2'><img title='".__('Edit')."' src='./themes/".$gibbon->session->get('gibbonThemeName')."/img/config.png'/></a> ";
                            echo "<a class='thickbox' href='".$gibbon->session->get('absoluteURL').'/fullscreen.php?q=/modules/'.$gibbon->session->get('module').'/outcomes_delete.php&gibbonOutcomeID='.$row['gibbonOutcomeID']."&filter2=$filter2&width=650&height=135'><img title='".__('Delete')."' src='./themes/".$gibbon->session->get('gibbonThemeName')."/img/garbage.png'/></a> ";
                        }
                    }
                }
                if ($row['description'] != '') {
                    echo "<a title='".__('View Description')."' class='show_hide-$count' onclick='false' href='#'><img style='padding-left: 0px' src='".$gibbon->session->get('absoluteURL').'/themes/'.$gibbon->session->get('gibbonThemeName')."/img/page_down.png' ' onclick='return false;' /></a>";
                }
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

            if ($result->rowCount() > $gibbon->session->get('pagination')) {
                printPagination($guid, $result->rowCount(), $page, $gibbon->session->get('pagination'), 'bottom', "filter2=$filter2");
            }
        }
    }
}
