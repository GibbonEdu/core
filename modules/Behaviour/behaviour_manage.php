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
use Gibbon\Forms\DatabaseFormFactory;

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

$enableDescriptors = getSettingByScope($connection2, 'Behaviour', 'enableDescriptors');
$enableLevels = getSettingByScope($connection2, 'Behaviour', 'enableLevels');

if (isActionAccessible($guid, $connection2, '/modules/Behaviour/behaviour_manage.php') == false) {
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
        echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Manage Behaviour Records').'</div>';
        echo '</div>';

        if (isset($_GET['return'])) {
            returnProcess($guid, $_GET['return'], null, null);
        }

        $gibbonPersonID = null;
        if (isset($_GET['gibbonPersonID'])) {
            $gibbonPersonID = $_GET['gibbonPersonID'];
        }
        $gibbonRollGroupID = null;
        if (isset($_GET['gibbonRollGroupID'])) {
            $gibbonRollGroupID = $_GET['gibbonRollGroupID'];
        }
        $gibbonYearGroupID = null;
        if (isset($_GET['gibbonYearGroupID'])) {
            $gibbonYearGroupID = $_GET['gibbonYearGroupID'];
        }
        $type = null;
        if (isset($_GET['type'])) {
            $type = $_GET['type'];
        }

        echo '<h3>';
        echo __($guid, 'Filter');
        echo '</h3>';

        $form = Form::create('filter', $_SESSION[$guid]['absoluteURL'].'/index.php', 'get');
            $form->setClass('noIntBorder fullWidth');
            $form->setFactory(DatabaseFormFactory::create($pdo));

            $form->addHiddenValue('q', "/modules/Behaviour/behaviour_manage.php");

        //Students
        $students = array();

        $row = $form->addRow();
            $row->addLabel('gibbonPersonID',__('Student'));
            $row->addSelectStudent('gibbonPersonID', $_SESSION[$guid]['gibbonSchoolYearID'])->selected($gibbonPersonID)->placeholder();

        //Roll Group
        $row = $form->addRow();
            $row->addLabel('gibbonRollGroupID',__('Roll Group'));
            $row->addSelectRollGroup('gibbonRollGroupID', $_SESSION[$guid]['gibbonSchoolYearID'])->selected($gibbonRollGroupID)->placeholder();

        //Year Group
        $row = $form->addRow();
            $row->addLabel('gibbonYearGroupID',__('Year Group'));
            $row->addSelectYearGroup('gibbonYearGroupID')->placeholder()->selected($gibbonYearGroupID);

        //Type
        $row = $form->addRow();
            $row->addLabel('type',__('Type'));
            $row->addSelect('type')->fromArray(array('Positive', 'Negative'))->selected($type)->placeholder();


        $row = $form->addRow();
            $row->addSearchSubmit($gibbon->session, __('Clear Filters'));

        echo $form->getOutput();


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
            $sqlWhere = 'AND ';
            if ($gibbonPersonID != '') {
                $data['gibbonPersonID'] = $gibbonPersonID;
                $sqlWhere .= 'gibbonBehaviour.gibbonPersonID=:gibbonPersonID AND ';
            }
            if ($gibbonRollGroupID != '') {
                $data['gibbonRollGroupID'] = $gibbonRollGroupID;
                $sqlWhere .= 'gibbonRollGroupID=:gibbonRollGroupID AND ';
            }
            if ($gibbonYearGroupID != '') {
                $data['gibbonYearGroupID'] = $gibbonYearGroupID;
                $sqlWhere .= 'gibbonYearGroupID=:gibbonYearGroupID AND ';
            }
            if ($type != '') {
                $data['type'] = $type;
                $sqlWhere .= 'type=:type AND ';
            }
            if ($sqlWhere == 'AND ') {
                $sqlWhere = '';
            } else {
                $sqlWhere = substr($sqlWhere, 0, -5);
            }
            if ($highestAction == 'Manage Behaviour Records_all') {
                $data['gibbonSchoolYearID'] = $_SESSION[$guid]['gibbonSchoolYearID'];
                $data['gibbonSchoolYearID2'] = $_SESSION[$guid]['gibbonSchoolYearID'];
                $sql = "SELECT gibbonBehaviour.*, student.surname AS surnameStudent, student.preferredName AS preferredNameStudent, creator.surname AS surnameCreator, creator.preferredName AS preferredNameCreator, creator.title FROM gibbonBehaviour JOIN gibbonPerson AS student ON (gibbonBehaviour.gibbonPersonID=student.gibbonPersonID) JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=student.gibbonPersonID) JOIN gibbonPerson AS creator ON (gibbonBehaviour.gibbonPersonIDCreator=creator.gibbonPersonID) WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonBehaviour.gibbonSchoolYearID=:gibbonSchoolYearID2 $sqlWhere ORDER BY timestamp DESC";
            } elseif ($highestAction == 'Manage Behaviour Records_my') {
                $data['gibbonSchoolYearID'] = $_SESSION[$guid]['gibbonSchoolYearID'];
                $data['gibbonSchoolYearID2'] = $_SESSION[$guid]['gibbonSchoolYearID'];
                $data['gibbonPersonID2'] = $_SESSION[$guid]['gibbonPersonID'];
                $sql = "SELECT gibbonBehaviour.*, student.surname AS surnameStudent, student.preferredName AS preferredNameStudent, creator.surname AS surnameCreator, creator.preferredName AS preferredNameCreator, creator.title FROM gibbonBehaviour JOIN gibbonPerson AS student ON (gibbonBehaviour.gibbonPersonID=student.gibbonPersonID) JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=student.gibbonPersonID) JOIN gibbonPerson AS creator ON (gibbonBehaviour.gibbonPersonIDCreator=creator.gibbonPersonID) WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonBehaviour.gibbonSchoolYearID=:gibbonSchoolYearID2 AND gibbonPersonIDCreator=:gibbonPersonID2 $sqlWhere ORDER BY timestamp DESC";
            }
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }
        $sqlPage = $sql.' LIMIT '.$_SESSION[$guid]['pagination'].' OFFSET '.(($page - 1) * $_SESSION[$guid]['pagination']);

        echo "<div class='linkTop'>";
        echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/behaviour_manage_add.php&gibbonPersonID=$gibbonPersonID&gibbonRollGroupID=$gibbonRollGroupID&gibbonYearGroupID=$gibbonYearGroupID&type=$type'>".__($guid, 'Add')."<img style='margin: 0 0 -4px 5px' title='".__($guid, 'Add')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/page_new.png'/></a> | ";
        echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/behaviour_manage_addMulti.php&gibbonPersonID=$gibbonPersonID&gibbonRollGroupID=$gibbonRollGroupID&gibbonYearGroupID=$gibbonYearGroupID&type=$type'>".__($guid, 'Add Multiple')."<img style='margin: 0 0 -4px 5px' title='".__($guid, 'Add Multiple')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/page_new_multi.png'/></a>";
        $policyLink = getSettingByScope($connection2, 'Behaviour', 'policyLink');
        if ($policyLink != '') {
            echo " | <a target='_blank' href='$policyLink'>".__($guid, 'View Behaviour Policy').'</a>';
        }
        echo '</div>';

        if ($result->rowCount() < 1) {
            echo "<div class='error'>";
            echo __($guid, 'There are no records to display.');
            echo '</div>';
        } else {
            if ($result->rowCount() > $_SESSION[$guid]['pagination']) {
                printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]['pagination'], 'top', "gibbonPersonID=$gibbonPersonID&gibbonRollGroupID=$gibbonRollGroupID&gibbonYearGroupID=$gibbonYearGroupID&type=$type");
            }

            echo "<table cellspacing='0' style='width: 100%'>";
            echo "<tr class='head'>";
            echo '<th>';
            echo __($guid, 'Student & Date');
            echo '</th>';
            echo '<th>';
            echo __($guid, 'Type');
            echo '</th>';
            if ($enableDescriptors == 'Y') {
                echo '<th>';
                echo __($guid, 'Descriptor');
                echo '</th>';
            }
            if ($enableLevels == 'Y') {
                echo '<th>';
                echo __($guid, 'Level');
                echo '</th>';
            }
            echo '<th>';
            echo __($guid, 'Teacher');
            echo '</th>';
            echo "<th style='min-width: 110px'>";
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

                    //COLOR ROW BY STATUS!
                    echo "<tr class=$rowNum>";
                if ($row['comment'] != '') {
                    echo '<td>';
                } else {
                    echo '<td>';
                }
                echo "<div style='padding: 2px 0px'><b><a href='index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=".$row['gibbonPersonID']."&subpage=Behaviour&search=&allStudents=&sort=surname, preferredName'>".formatName('', $row['preferredNameStudent'], $row['surnameStudent'], 'Student', true).'</a><br/></div>';
                if (substr($row['timestamp'], 0, 10) > $row['date']) {
                    echo __($guid, 'Updated:').' '.dateConvertBack($guid, substr($row['timestamp'], 0, 10)).'<br/>';
                    echo __($guid, 'Incident:').' '.dateConvertBack($guid, $row['date']).'<br/>';
                } else {
                    echo dateConvertBack($guid, $row['date']).'<br/>';
                }
                echo '</td>';
                echo "<td style='text-align: center'>";
                if ($row['type'] == 'Negative') {
                    echo "<img src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/iconCross.png'/> ";
                } elseif ($row['type'] == 'Positive') {
                    echo "<img src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/iconTick.png'/> ";
                }
                echo '</td>';
                if ($enableDescriptors == 'Y') {
                    echo '<td>';
                    echo trim($row['descriptor']);
                    echo '</td>';
                }
                if ($enableLevels == 'Y') {
                    echo '<td>';
                    echo trim($row['level']);
                    echo '</td>';
                }
                echo '<td>';
                echo formatName($row['title'], $row['preferredNameCreator'], $row['surnameCreator'], 'Staff').'</b><br/>';
                echo '</td>';
                echo '<td>';
                echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/behaviour_manage_edit.php&gibbonBehaviourID='.$row['gibbonBehaviourID']."&gibbonPersonID=$gibbonPersonID&gibbonRollGroupID=$gibbonRollGroupID&gibbonYearGroupID=$gibbonYearGroupID&type=$type'><img title='".__($guid, 'Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
                echo "<a class='thickbox' href='".$_SESSION[$guid]['absoluteURL'].'/fullscreen.php?q=/modules/'.$_SESSION[$guid]['module'].'/behaviour_manage_delete.php&gibbonBehaviourID='.$row['gibbonBehaviourID']."&gibbonPersonID=$gibbonPersonID&gibbonRollGroupID=$gibbonRollGroupID&gibbonYearGroupID=$gibbonYearGroupID&type=$type&width=650&height=135'><img title='".__($guid, 'Delete')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/garbage.png'/></a> ";
                echo "<script type='text/javascript'>";
                echo '$(document).ready(function(){';
                echo "\$(\".comment-$count\").hide();";
                echo "\$(\".show_hide-$count\").fadeIn(1000);";
                echo "\$(\".show_hide-$count\").click(function(){";
                echo "\$(\".comment-$count\").fadeToggle(1000);";
                echo '});';
                echo '});';
                echo '</script>';
                if ($row['comment'] != '' or $row['followup'] != '') {
                    echo "<a title='".__($guid, 'View Description')."' class='show_hide-$count' onclick='false' href='#'><img style='padding-right: 5px' src='".$_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName']."/img/page_down.png' alt='".__($guid, 'Show Comment')."' onclick='return false;' /></a>";
                }
                echo '</td>';
                echo '</tr>';
                if ($row['comment'] != '' or $row['followup'] != '') {
                    if ($row['type'] == 'Positive') {
                        $bg = 'background-color: #D4F6DC;';
                    } else {
                        $bg = 'background-color: #F6CECB;';
                    }
                    echo "<tr class='comment-$count' id='comment-$count'>";
                    echo "<td style='$bg' colspan=6>";
                    if ($row['comment'] != '') {
                        echo '<b>'.__($guid, 'Incident').'</b><br/>';
                        echo nl2brr($row['comment']).'<br/><br/>';
                    }
                    if ($row['followup'] != '') {
                        echo '<b>'.__($guid, 'Follow Up').'</b><br/>';
                        echo nl2brr($row['followup']).'<br/><br/>';
                    }
                    echo '</td>';
                    echo '</tr>';
                }
            }
            echo '</table>';

            if ($result->rowCount() > $_SESSION[$guid]['pagination']) {
                printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]['pagination'], 'bottom', "gibbonPersonID=$gibbonPersonID&gibbonRollGroupID=$gibbonRollGroupID&gibbonYearGroupID=$gibbonYearGroupID&type=$type");
            }
        }
    }
}
?>
