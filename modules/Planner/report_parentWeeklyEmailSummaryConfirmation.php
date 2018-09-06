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

if (isActionAccessible($guid, $connection2, '/modules/Planner/report_parentWeeklyEmailSummaryConfirmation.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Parent Weekly Email Summary').'</div>';
    echo '</div>';
    echo '<p>';
    echo __($guid, 'This report shows responses to the weekly summary email, organised by calendar week and role group.');
    echo '</p>';

    echo '<h2>';
    echo __($guid, 'Choose Roll Group & Week');
    echo '</h2>';

    $gibbonRollGroupID = isset($_GET['gibbonRollGroupID'])? $_GET['gibbonRollGroupID'] : null;
    $weekOfYear = isset($_GET['weekOfYear'])? $_GET['weekOfYear'] : null;

    $form = Form::create('searchForm', $_SESSION[$guid]['absoluteURL'].'/index.php', 'get');
    $form->setFactory(DatabaseFormFactory::create($pdo));

    $form->addHiddenValue('q', '/modules/'.$_SESSION[$guid]['module'].'/report_parentWeeklyEmailSummaryConfirmation.php');

    $row = $form->addRow();
        $row->addLabel('gibbonRollGroupID', __('Roll Group'));
        $row->addSelectRollGroup('gibbonRollGroupID', $_SESSION[$guid]['gibbonSchoolYearID'])->isRequired()->selected($gibbonRollGroupID);

    $begin = new DateTime($_SESSION[$guid]['gibbonSchoolYearFirstDay']);
    $end = new DateTime();
    $dateRange = new DatePeriod($begin, new DateInterval('P1W'), $end);

    $weeks = array();
    foreach ($dateRange as $date) {
        $weeks[$date->format('W')] = __('Week').' '.$date->format('W').': '.$date->format($_SESSION[$guid]['i18n']['dateFormatPHP']);
    }
    $weeks = array_reverse($weeks, true);

    $row = $form->addRow();
        $row->addLabel('weekOfYear', __('Calendar Week'));
        $row->addSelect('weekOfYear')->fromArray($weeks)->selected($weekOfYear);

    $row = $form->addRow();
        $row->addSearchSubmit($gibbon->session, __('Clear Filters'));

    echo $form->getOutput();

    if ($gibbonRollGroupID != '') {
        echo '<h2>';
        echo __($guid, 'Report Data');
        echo '</h2>';

        try {
            $data = array('gibbonRollGroupID' => $gibbonRollGroupID);
            $sql = "SELECT student.surname AS studentSurname, student.preferredName AS studentPreferredName, parent.surname AS parentSurname, parent.preferredName AS parentPreferredName, parent.title AS parentTitle, gibbonRollGroup.name, student.gibbonPersonID AS gibbonPersonIDStudent, parent.gibbonPersonID AS gibbonPersonIDParent FROM gibbonPerson AS student JOIN gibbonStudentEnrolment ON (student.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) LEFT JOIN gibbonFamilyChild ON (gibbonFamilyChild.gibbonPersonID=student.gibbonPersonID) LEFT JOIN gibbonFamily ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID) LEFT JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID) LEFT JOIN gibbonPerson AS parent ON (gibbonFamilyAdult.gibbonPersonID=parent.gibbonPersonID) WHERE (gibbonFamilyAdult.contactPriority=1 OR gibbonFamilyAdult.contactPriority IS NULL) AND student.status='Full' AND parent.status='Full' AND (student.dateStart IS NULL OR student.dateStart<='".date('Y-m-d')."') AND (student.dateEnd IS NULL OR student.dateEnd>='".date('Y-m-d')."') AND gibbonStudentEnrolment.gibbonRollGroupID=:gibbonRollGroupID ORDER BY student.surname, student.preferredName, parent.surname, parent.preferredName";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        echo "<table cellspacing='0' style='width: 100%'>";
        echo "<tr class='head'>";
        echo '<th>';
        echo __($guid, 'Student');
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Parent');
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Sent');
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Confirmed');
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
            echo "<a href='index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=".$row['gibbonPersonIDStudent']."&subpage=Homework'>".formatName('', $row['studentPreferredName'], $row['studentSurname'], 'Student', true).'</a>';
            echo '</td>';
            echo '<td>';
            echo formatName($row['parentTitle'], $row['parentPreferredName'], $row['parentSurname'], 'Parent', true);
            echo '</td>';
            echo "<td style='width:15%'>";
            try {
                $dataData = array('gibbonPersonIDStudent' => $row['gibbonPersonIDStudent'], 'gibbonPersonIDParent' => $row['gibbonPersonIDParent'], 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'weekOfYear' => $weekOfYear);
                $sqlData = 'SELECT * FROM gibbonPlannerParentWeeklyEmailSummary WHERE gibbonPersonIDStudent=:gibbonPersonIDStudent AND gibbonPersonIDParent=:gibbonPersonIDParent AND gibbonSchoolYearID=:gibbonSchoolYearID AND weekOfYear=:weekOfYear';
                $resultData = $connection2->prepare($sqlData);
                $resultData->execute($dataData);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }

            if ($resultData->rowCount() == 1) {
                $rowData = $resultData->fetch();
                echo "<img title='".__($guid, 'Sent')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/iconTick.png'/> ";
            } else {
                $rowData = null;
                echo "<img title='".__($guid, 'Not Sent')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/iconCross.png'/> ";
            }
            echo '</td>';
            echo "<td style='width:15%'>";
            if (is_null($rowData)) {
                echo __($guid, 'NA');
            } else {
                if ($rowData['confirmed'] == 'Y') {
                    echo "<img title='".__($guid, 'Confirmed')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/iconTick.png'/> ";
                } else {
                    echo "<img title='".__($guid, 'Not Confirmed')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/iconCross.png'/> ";
                }
            }
            echo '</td>';
            echo '</tr>';
        }
        if ($count == 0) {
            echo "<tr class=$rowNum>";
            echo '<td colspan=4>';
            echo __($guid, 'There are no records to display.');
            echo '</td>';
            echo '</tr>';
        }
        echo '</table>';
    }
}
?>
