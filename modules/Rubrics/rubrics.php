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

if (isActionAccessible($guid, $connection2, '/modules/Rubrics/rubrics.php') == false) {
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
        echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Manage Rubrics').'</div>';
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
        $where = 'WHERE ';
        $data = array();
        $search = null;
        if (isset($_POST['search'])) {
            $search = $_POST['search'];
        } elseif (isset($_GET['search'])) {
            $search = $_GET['search'];
        }

        if ($search != '') {
            $data['name'] = $search;
            $where .= " name LIKE CONCAT('%', :name, '%') AND ";
        }

        $filter2 = null;
        if (isset($_POST['filter2'])) {
            $filter2 = $_POST['filter2'];
        }
        if ($filter2 != '') {
            $data['gibbonDepartmentID'] = $filter2;
            $where .= ' gibbonDepartmentID=:gibbonDepartmentID';
        }

        if (substr($where, -5) == ' AND ') {
            $where = substr($where, 0, -5);
        }

        if ($where == 'WHERE ') {
            $where = '';
        }

        try {
            $sql = "SELECT * FROM gibbonRubric $where ORDER BY scope, category, name";
            $sqlPage = $sql.' LIMIT '.$_SESSION[$guid]['pagination'].' OFFSET '.(($page - 1) * $_SESSION[$guid]['pagination']);
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        echo '<h3>';
        echo __($guid, 'Filter');
        echo '</h3>';

        $form = Form::create('courseEdit', $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/rubrics.php');
        $form->setClass('noIntBorder fullWidth');

        $row = $form->addRow();
            $row->addLabel('search', __('Search For'))->description(__('Rubric name.'));
            $row->addTextField('search')->setValue($search);

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
        echo __($guid, 'Rubrics');
        echo '</h3>';
        if ($highestAction == 'Manage Rubrics_viewEditAll' or $highestAction == 'Manage Rubrics_viewAllEditLearningArea') {
            echo "<div class='linkTop'>";
            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/rubrics_add.php&search=$search&filter2=$filter2'>".__($guid, 'Add')."<img style='margin-left: 5px' title='".__($guid, 'Add')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/page_new.png'/></a>";
            echo '</div>';
        }
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
            echo __($guid, 'Active');
            echo '</th>';
            echo "<th style='width: 130px'>";
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

                if ($row['active'] != 'Y') {
                    $rowNum = 'error';
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

                if ($highestAction == 'Manage Rubrics_viewEditAll') {
                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/rubrics_edit.php&gibbonRubricID='.$row['gibbonRubricID']."&sidebar=false&search=$search&filter2=$filter2'><img title='".__($guid, 'Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
                    echo "<a class='thickbox' href='".$_SESSION[$guid]['absoluteURL'].'/fullscreen.php?q=/modules/'.$_SESSION[$guid]['module'].'/rubrics_delete.php&gibbonRubricID='.$row['gibbonRubricID']."&search=$search&filter2=$filter2&width=650&height=135'><img title='".__($guid, 'Delete')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/garbage.png'/></a> ";
                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/rubrics_duplicate.php&gibbonRubricID='.$row['gibbonRubricID']."&search=$search&filter2=$filter2'><img style='margin-left: 3px' title='".__($guid, 'Duplicate')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/copy.png'/></a>";
                } elseif ($highestAction == 'Manage Rubrics_viewAllEditLearningArea') {
                    if ($row['scope'] == 'Learning Area' and $row['gibbonDepartmentID'] != '') {
                        try {
                            $dataLearningAreaStaff = array('gibbonDepartmentID' => $row['gibbonDepartmentID'], 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                            $sqlLearningAreaStaff = "SELECT * FROM gibbonDepartment JOIN gibbonDepartmentStaff ON (gibbonDepartmentStaff.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) WHERE gibbonDepartment.gibbonDepartmentID=:gibbonDepartmentID AND gibbonPersonID=:gibbonPersonID AND (role='Coordinator' OR role='Teacher (Curriculum)')";
                            $resultLearningAreaStaff = $connection2->prepare($sqlLearningAreaStaff);
                            $resultLearningAreaStaff->execute($dataLearningAreaStaff);
                        } catch (PDOException $e) {
                            echo "<div class='error'>".$e->getMessage().'</div>';
                        }

                        if ($resultLearningAreaStaff->rowCount() > 0) {
                            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/rubrics_edit.php&gibbonRubricID='.$row['gibbonRubricID']."&sidebar=false&search=$search&filter2=$filter2'><img title='".__($guid, 'Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
                            echo "<a class='thickbox' href='".$_SESSION[$guid]['absoluteURL'].'/fullscreen.php?q=/modules/'.$_SESSION[$guid]['module'].'/rubrics_delete.php&gibbonRubricID='.$row['gibbonRubricID']."&search=$search&filter2=$filter2&width=650&height=135'><img title='".__($guid, 'Delete')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/garbage.png'/></a> ";
                            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/rubrics_duplicate.php&gibbonRubricID='.$row['gibbonRubricID']."&search=$search&filter2=$filter2'><img style='margin-left: 3px' title='".__($guid, 'Duplicate')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/copy.png'/></a>";
                        }
                    }
                }
                if ($row['description'] != '') {
                    echo "<a title='".__($guid, 'View Description')."' class='show_hide-$count' onclick='false' href='#'><img style='padding-left: 3px' src='".$_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName']."/img/page_down.png' alt='".__($guid, 'Show Comment')."' onclick='return false;' /></a>";
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

            if ($result->rowCount() > $_SESSION[$guid]['pagination']) {
                printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]['pagination'], 'bottom');
            }
        }
    }
}
?>
