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

if (isActionAccessible($guid, $connection2, '/modules/Students/medicalForm_manage.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Manage Medical Forms').'</div>';
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

    echo '<h2>';
    echo __($guid, 'Search');
    echo '</h2>';

    $search = isset($_GET['search'])? $_GET['search'] : '';

    $form = Form::create('filter', $_SESSION[$guid]['absoluteURL'].'/index.php', 'get');
    $form->setClass('noIntBorder fullWidth');

    $form->addHiddenValue('q', '/modules/'.$_SESSION[$guid]['module'].'/medicalForm_manage.php');

    $row = $form->addRow();
        $row->addLabel('search', __('Search For'))->description(__('Preferred, surname, username.'));
        $row->addTextField('search')->setValue($search);

    $row = $form->addRow();
        $row->addSearchSubmit($gibbon->session, __('Clear Search'));

    echo $form->getOutput();

    echo '<h2>';
    echo __($guid, 'View');
    echo '</h2>';

    $search = null;
    if (isset($_GET['search'])) {
        $search = $_GET['search'];
    }
    try {
        $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
        $sql = "SELECT * FROM gibbonPerson JOIN gibbonPersonMedical ON (gibbonPerson.gibbonPersonID=gibbonPersonMedical.gibbonPersonID) JOIN gibbonStudentEnrolment ON (gibbonPersonMedical.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonRollGroup ON (gibbonRollGroup.gibbonRollGroupID=gibbonStudentEnrolment.gibbonRollGroupID) WHERE (gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID) AND (status='FULL') ORDER BY surname, preferredName";
        if ($search != '') {
            $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'search1' => "%$search%", 'search2' => "%$search%", 'search3' => "%$search%");
            $sql = "SELECT * FROM gibbonPerson JOIN gibbonPersonMedical ON (gibbonPerson.gibbonPersonID=gibbonPersonMedical.gibbonPersonID) JOIN gibbonStudentEnrolment ON (gibbonPersonMedical.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonRollGroup ON (gibbonRollGroup.gibbonRollGroupID=gibbonStudentEnrolment.gibbonRollGroupID) WHERE (gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID) AND (status='FULL') AND ((preferredName LIKE :search1) OR (surname LIKE :search2) OR (username LIKE :search3)) ORDER BY surname, preferredName";
        }
        $sqlPage = $sql.' LIMIT '.$_SESSION[$guid]['pagination'].' OFFSET '.(($page - 1) * $_SESSION[$guid]['pagination']);
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        echo "<div class='error'>".$e->getMessage().'</div>';
    }

    echo "<div class='linkTop'>";
    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/medicalForm_manage_add.php&search=$search'>".__($guid, 'Add')."<img style='margin-left: 5px' title='".__($guid, 'Add')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/page_new.png'/></a>";
    echo '</div>';

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
        echo "<th style='width: 150px'>";
        echo __($guid, 'Name');
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Roll Group');
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Blood Type');
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Medication').'<br/>';
        echo "<span style='font-size: 80%'><i>".__($guid, 'Long Term').'</span>';
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Tetanus').'<br/>';
        echo "<span style='font-size: 80%'><i>".__($guid, '10 Years').'</span>';
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Conditions');
        echo '</th>';
        echo "<th style='width: 110px'>";
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
            echo formatName('', $row['preferredName'], $row['surname'], 'Student', true);
            echo '</td>';
            echo '<td>';
            echo $row['name'];
            echo '</td>';
            echo '<td>';
            echo $row['bloodType'];
            echo '</td>';
            echo '<td>';
            if ($row['longTermMedicationDetails'] != '') {
                echo $row['longTermMedicationDetails'];
            } else {
                echo $row['longTermMedication'];
            }
            echo '</td>';
            echo '<td>';
            echo $row['tetanusWithin10Years'];
            echo '</td>';
            echo '<td>';
            try {
                $dataCondition = array('gibbonPersonMedicalID' => $row['gibbonPersonMedicalID']);
                $sqlCondition = 'SELECT * FROM gibbonPersonMedical JOIN gibbonPersonMedicalCondition ON (gibbonPersonMedical.gibbonPersonMedicalID=gibbonPersonMedicalCondition.gibbonPersonMedicalID) WHERE gibbonPersonMedical.gibbonPersonMedicalID=:gibbonPersonMedicalID';
                $resultCondition = $connection2->prepare($sqlCondition);
                $resultCondition->execute($dataCondition);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }
            echo $resultCondition->rowCount();
            echo '</td>';
            echo '<td>';
            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/medicalForm_manage_edit.php&gibbonPersonMedicalID='.$row['gibbonPersonMedicalID']."&search=$search'><img title='".__($guid, 'Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
            echo "<a class='thickbox' href='".$_SESSION[$guid]['absoluteURL'].'/fullscreen.php?q=/modules/'.$_SESSION[$guid]['module'].'/medicalForm_manage_delete.php&gibbonPersonMedicalID='.$row['gibbonPersonMedicalID']."&search=$search&width=650&height=135'><img title='".__($guid, 'Delete')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/garbage.png'/></a>";
            echo "<script type='text/javascript'>";
            echo '$(document).ready(function(){';
            echo "\$(\".comment-$count\").hide();";
            echo "\$(\".show_hide-$count\").fadeIn(1000);";
            echo "\$(\".show_hide-$count\").click(function(){";
            echo "\$(\".comment-$count\").fadeToggle(1000);";
            echo '});';
            echo '});';
            echo '</script>';
            if ($row['comment'] != '') {
                echo "<a title='".__($guid, 'View Description')."' class='show_hide-$count' onclick='false' href='#'><img style='padding-right: 5px' src='".$_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName']."/img/page_down.png' alt='".__($guid, 'Show Comment')."' onclick='return false;' /></a>";
            }
            echo '</td>';
            echo '</tr>';
            if ($row['comment'] != '') {
                echo "<tr class='comment-$count' id='comment-$count'>";
                echo "<td colspan=7>";
                    echo nl2brr($row['comment']).'<br/><br/>';
                echo '</td>';
                echo '</tr>';
            }
        }
        echo '</table>';

        if ($result->rowCount() > $_SESSION[$guid]['pagination']) {
            printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]['pagination'], 'bottom', "search=$search");
        }
    }
}
?>
