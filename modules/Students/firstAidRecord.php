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


if (isActionAccessible($guid, $connection2, '/modules/Students/firstAidRecord.php') == false) {
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
        echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'First Aid Record').'</div>';
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

        echo '<h3>';
        echo __($guid, 'Filter');
        echo '</h3>';

        $form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/index.php', 'get');

        $form->setFactory(DatabaseFormFactory::create($pdo));
        $form->setClass('noIntBorder fullWidth');

        $form->addHiddenValue('q', "/modules/".$_SESSION[$guid]['module']."/firstAidRecord.php");

        $row = $form->addRow();
            $row->addLabel('gibbonPersonID', __('Student'));
            $row->addSelectStudent('gibbonPersonID', $_SESSION[$guid]['gibbonSchoolYearID'])->placeholder()->selected($gibbonPersonID);

        $row = $form->addRow();
            $row->addLabel('gibbonRollGroupID', __('Roll Group'));
            $row->addSelectRollGroup('gibbonRollGroupID', $_SESSION[$guid]['gibbonSchoolYearID'])->selected($gibbonRollGroupID);

        $row = $form->addRow();
            $row->addLabel('gibbonYearGroupID', __('Year Group'));
            $row->addSelectYearGroup('gibbonYearGroupID')->selected($gibbonYearGroupID);

        $row = $form->addRow();
            $row->addFooter();
            $row->addSearchSubmit($gibbon->session);

        echo $form->getOutput();

        echo '<h3>';
        echo __($guid, 'First Aid Records');
        echo '</h3>';
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
                $sqlWhere .= 'gibbonFirstAid.gibbonPersonIDPatient=:gibbonPersonID AND ';
            }
            if ($gibbonRollGroupID != '') {
                $data['gibbonRollGroupID'] = $gibbonRollGroupID;
                $sqlWhere .= 'gibbonStudentEnrolment.gibbonRollGroupID=:gibbonRollGroupID AND ';
            }
            if ($gibbonYearGroupID != '') {
                $data['gibbonYearGroupID'] = $gibbonYearGroupID;
                $sqlWhere .= 'gibbonStudentEnrolment.gibbonYearGroupID=:gibbonYearGroupID AND ';
            }
            if ($sqlWhere == 'AND ') {
                $sqlWhere = '';
            } else {
                $sqlWhere = substr($sqlWhere, 0, -5);
            }
            $data['gibbonSchoolYearID'] = $_SESSION[$guid]['gibbonSchoolYearID'];
            $sql = "SELECT gibbonFirstAid.*, patient.surname AS surnamePatient, patient.preferredName AS preferredNamePatient, firstAider.title, firstAider.surname AS surnameFirstAider, firstAider.preferredName AS preferredNameFirstAider
                FROM gibbonFirstAid
                    JOIN gibbonPerson AS patient ON (gibbonFirstAid.gibbonPersonIDPatient=patient.gibbonPersonID)
                    JOIN gibbonPerson AS firstAider ON (gibbonFirstAid.gibbonPersonIDFirstAider=firstAider.gibbonPersonID)
                    JOIN gibbonStudentEnrolment ON (patient.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID)
                    JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID)
                    JOIN gibbonYearGroup ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID)
                WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID $sqlWhere
                ORDER BY date DESC, timeIn DESC";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }
        $sqlPage = $sql.' LIMIT '.$_SESSION[$guid]['pagination'].' OFFSET '.(($page - 1) * $_SESSION[$guid]['pagination']);

        echo "<div class='linkTop'>";
        echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/firstAidRecord_add.php&gibbonPersonID=$gibbonPersonID&gibbonRollGroupID=$gibbonRollGroupID&gibbonYearGroupID=$gibbonYearGroupID'>".__($guid, 'Add')."<img style='margin: 0 0 -4px 5px' title='".__($guid, 'Add')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/page_new.png'/></a>";
        echo '</div>';

        if ($result->rowCount() < 1) {
            echo "<div class='error'>";
            echo __($guid, 'There are no records to display.');
            echo '</div>';
        } else {
            if ($result->rowCount() > $_SESSION[$guid]['pagination']) {
                printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]['pagination'], 'top', "gibbonPersonID=$gibbonPersonID&gibbonRollGroupID=$gibbonRollGroupID&gibbonYearGroupID=$gibbonYearGroupID");
            }

            echo "<table cellspacing='0' style='width: 100%'>";
            echo "<tr class='head'>";
            echo '<th>';
            echo __($guid, 'Student & Date');
            echo '</th>';
            echo '<th>';
            echo __($guid, 'First Aider');
            echo '</th>';
            echo '<th>';
            echo __($guid, 'Time');
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
                echo '<td>';
                echo "<div style='padding: 2px 0px'><b><a href='index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=".$row['gibbonPersonIDPatient']."&subpage=Behaviour&search=&allStudents=&sort=surname, preferredName'>".formatName('', $row['preferredNamePatient'], $row['surnamePatient'], 'Student', true).'</a><br/></div>';
                echo dateConvertBack($guid, $row['date']).'<br/>';
                echo '</td>';
                echo '<td>';
                echo formatName($row['title'], $row['preferredNameFirstAider'], $row['surnameFirstAider'], 'Student').'</b><br/>';
                echo '</td>';
                echo '<td>';
                echo substr($row['timeIn'], 0, -3);
                if (!is_null($row['timeOut'])) {
                  echo ' - '.substr($row['timeOut'], 0, -3);
                }
                echo '</td>';
                echo '<td>';
                echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/firstAidRecord_edit.php&gibbonFirstAidID='.$row['gibbonFirstAidID']."&gibbonPersonID=$gibbonPersonID&gibbonRollGroupID=$gibbonRollGroupID&gibbonYearGroupID=$gibbonYearGroupID'><img title='".__($guid, 'Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
                echo "<script type='text/javascript'>";
                echo '$(document).ready(function(){';
                echo "\$(\".comment-$count\").hide();";
                echo "\$(\".show_hide-$count\").fadeIn(1000);";
                echo "\$(\".show_hide-$count\").click(function(){";
                echo "\$(\".comment-$count\").fadeToggle(1000);";
                echo '});';
                echo '});';
                echo '</script>';
                if ($row['actionTaken'] != '' or $row['followUp'] != '' or $row['description'] != '') {
                    echo "<a title='".__($guid, 'View Description')."' class='show_hide-$count' onclick='false' href='#'><img style='padding-right: 5px' src='".$_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName']."/img/page_down.png' alt='".__($guid, 'Show Comment')."' onclick='return false;' /></a>";
                }
                echo '</td>';
                echo '</tr>';
                if ($row['actionTaken'] != '' or $row['followUp'] != '' or $row['description'] != '') {
                    echo "<tr class='comment-$count' id='comment-$count'>";
                    echo "<td colspan=6>";
                    if ($row['description'] != '') {
                        echo '<b>'.__($guid, 'Description').'</b><br/>';
                        echo nl2brr($row['description']).'<br/><br/>';
                    }
                    if ($row['actionTaken'] != '') {
                        echo '<b>'.__($guid, 'Action Taken').'</b><br/>';
                        echo nl2brr($row['actionTaken']).'<br/><br/>';
                    }
                    if ($row['followUp'] != '') {
                        echo '<b>'.__($guid, 'Follow Up').'</b><br/>';
                        echo nl2brr($row['followUp']).'<br/><br/>';
                    }
                    echo '</td>';
                    echo '</tr>';
                }
            }
            echo '</table>';

            if ($result->rowCount() > $_SESSION[$guid]['pagination']) {
                printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]['pagination'], 'bottom', "gibbonPersonID=$gibbonPersonID&gibbonRollGroupID=$gibbonRollGroupID&gibbonYearGroupID=$gibbonYearGroupID");
            }
        }
    }
}
?>
