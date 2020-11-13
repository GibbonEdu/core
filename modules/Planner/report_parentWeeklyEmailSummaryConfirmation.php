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
use Gibbon\Services\Format;
use Gibbon\Domain\User\FamilyGateway;
use Gibbon\Forms\DatabaseFormFactory;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

$page->breadcrumbs->add(__('Parent Weekly Email Summary'));

if (isActionAccessible($guid, $connection2, '/modules/Planner/report_parentWeeklyEmailSummaryConfirmation.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    echo '<p>';
    echo __('This report shows responses to the weekly summary email, organised by calendar week and role group.');
    echo '</p>';

    echo '<h2>';
    echo __('Choose Roll Group & Week');
    echo '</h2>';

    $familyGateway = $container->get(FamilyGateway::class);
    $gibbonRollGroupID = isset($_GET['gibbonRollGroupID'])? $_GET['gibbonRollGroupID'] : null;
    $weekOfYear = isset($_GET['weekOfYear'])? $_GET['weekOfYear'] : null;

    $form = Form::create('searchForm', $_SESSION[$guid]['absoluteURL'].'/index.php', 'get');
    $form->setFactory(DatabaseFormFactory::create($pdo));

    $form->addHiddenValue('q', '/modules/'.$_SESSION[$guid]['module'].'/report_parentWeeklyEmailSummaryConfirmation.php');

    $row = $form->addRow();
        $row->addLabel('gibbonRollGroupID', __('Roll Group'));
        $row->addSelectRollGroup('gibbonRollGroupID', $_SESSION[$guid]['gibbonSchoolYearID'])->required()->selected($gibbonRollGroupID);

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
        echo __('Report Data');
        echo '</h2>';

        
            $data = array('gibbonRollGroupID' => $gibbonRollGroupID);
            $sql = "SELECT student.surname AS studentSurname, student.preferredName AS studentPreferredName, parent.surname AS parentSurname, parent.preferredName AS parentPreferredName, parent.title AS parentTitle, gibbonRollGroup.name, student.gibbonPersonID AS gibbonPersonIDStudent, parent.gibbonPersonID AS gibbonPersonIDParent FROM gibbonPerson AS student JOIN gibbonStudentEnrolment ON (student.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) LEFT JOIN gibbonFamilyChild ON (gibbonFamilyChild.gibbonPersonID=student.gibbonPersonID) LEFT JOIN gibbonFamily ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID) LEFT JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID) LEFT JOIN gibbonPerson AS parent ON (gibbonFamilyAdult.gibbonPersonID=parent.gibbonPersonID) WHERE (gibbonFamilyAdult.contactPriority=1 OR gibbonFamilyAdult.contactPriority IS NULL) AND student.status='Full' AND parent.status='Full' AND (student.dateStart IS NULL OR student.dateStart<='".date('Y-m-d')."') AND (student.dateEnd IS NULL OR student.dateEnd>='".date('Y-m-d')."') AND gibbonStudentEnrolment.gibbonRollGroupID=:gibbonRollGroupID ORDER BY student.surname, student.preferredName, parent.surname, parent.preferredName";
            $result = $connection2->prepare($sql);
            $result->execute($data);

        echo "<table cellspacing='0' style='width: 100%'>";
        echo "<tr class='head'>";
        echo '<th>';
        echo __('Student');
        echo '</th>';
        echo '<th>';
        echo __('Parents');
        echo '</th>';
        echo '<th>';
        echo __('Sent');
        echo '</th>';
        echo '<th>';
        echo __('Confirmed');
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
            echo "<a href='index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=".$row['gibbonPersonIDStudent']."&subpage=Homework'>".Format::name('', $row['studentPreferredName'], $row['studentSurname'], 'Student', true).'</a>';
            echo '</td>';

            $dataData = array('gibbonPersonIDStudent' => $row['gibbonPersonIDStudent'],  'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'weekOfYear' => $weekOfYear);
            $sqlData = 'SELECT gibbonPlannerParentWeeklyEmailSummary.*, gibbonPerson.gibbonPersonID, gibbonPerson.title, gibbonPerson.preferredName, gibbonPerson.surname FROM gibbonPlannerParentWeeklyEmailSummary LEFT JOIN gibbonPerson ON (gibbonPerson.gibbonPersonID=gibbonPlannerParentWeeklyEmailSummary.gibbonPersonIDParent) WHERE gibbonPersonIDStudent=:gibbonPersonIDStudent AND gibbonSchoolYearID=:gibbonSchoolYearID AND weekOfYear=:weekOfYear';

            $rowData = $pdo->selectOne($sqlData, $dataData);

            $familyAdults = $familyGateway->selectFamilyAdultsByStudent($row['gibbonPersonIDStudent'])->fetchAll();
            $familyAdults = array_filter($familyAdults, function ($parent) {
                return $parent['contactEmail'] == 'Y';
            });

            echo '<td>';
            foreach ($familyAdults as $parent) {
                echo Format::name($parent['title'], $parent['preferredName'], $parent['surname'], 'Parent', true);

                echo !empty($rowData) && $parent['gibbonPersonID'] == $rowData['gibbonPersonID'] && $rowData['confirmed'] == 'Y'
                    ? ' ('.__('Confirmed') . ')<br/>'
                    : '<br/>';
            }
            echo '</td>';
            
            echo "<td style='width:15%'>";
            
            if (!empty($rowData)) {
                echo "<img title='".__('Sent')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/iconTick.png'/> ";
            } else {
                echo "<img title='".__('Not Sent')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/iconCross.png'/> ";
            }
            echo '</td>';
            echo "<td style='width:15%'>";
            if (empty($rowData)) {
                echo __('NA');
            } else {
                if ($rowData['confirmed'] == 'Y') {
                    echo "<img title='".__('Confirmed')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/iconTick.png'/> ";
                } else {
                    echo "<img title='".__('Not Confirmed')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/iconCross.png'/> ";
                }
            }
            echo '</td>';
            echo '</tr>';
        }
        if ($count == 0) {
            echo "<tr class=$rowNum>";
            echo '<td colspan=4>';
            echo __('There are no records to display.');
            echo '</td>';
            echo '</tr>';
        }
        echo '</table>';
    }
}
?>
