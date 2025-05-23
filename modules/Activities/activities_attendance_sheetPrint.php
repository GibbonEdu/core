<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright © 2010, Gibbon Foundation
Gibbon™, Gibbon Education Ltd. (Hong Kong)

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

use Gibbon\Domain\System\SettingGateway;
use Gibbon\Services\Format;
use Gibbon\Forms\Form;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Activities/activities_attendance_sheet.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    $gibbonActivityID = $_GET['gibbonActivityID'] ?? '';
    $numberOfColumns = (isset($_GET['columns']) && $_GET['columns'] <= 20 ) ? $_GET['columns'] : 20;

    $data = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'gibbonSchoolYearID2' => $session->get('gibbonSchoolYearID'), 'gibbonActivityID' => $gibbonActivityID);
    $sql = "SELECT name, programStart, programEnd, gibbonPerson.gibbonPersonID, surname, preferredName, gibbonFormGroupID, gibbonActivityStudent.status FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonActivityStudent ON (gibbonActivityStudent.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonActivity ON (gibbonActivityStudent.gibbonActivityID=gibbonActivity.gibbonActivityID) WHERE gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonActivity.gibbonSchoolYearID=:gibbonSchoolYearID2 AND gibbonActivityStudent.status='Accepted' AND gibbonActivity.gibbonActivityID=:gibbonActivityID ORDER BY gibbonActivityStudent.status, surname, preferredName";
    $result = $connection2->prepare($sql);
    $result->execute($data);

    if (empty($gibbonActivityID) || $result->rowCount() < 1) {
        echo $page->getBlankSlate();
    } else {
        $output = '';

        $results = $result->fetchAll();
        $row = current($results);

        $dateType = $container->get(SettingGateway::class)->getSettingByScope('Activities', 'dateType');
        $date = '';
        if ($dateType == 'Date') {
            if (substr($row['programStart'], 0, 4) == substr($row['programEnd'], 0, 4)) {
                if (substr($row['programStart'], 5, 2) == substr($row['programEnd'], 5, 2)) {
                    $date = ' ('.date('F', mktime(0, 0, 0, substr($row['programStart'], 5, 2))).' '.substr($row['programStart'], 0, 4).')';
                } else {
                    $date = ' ('.date('F', mktime(0, 0, 0, substr($row['programStart'], 5, 2))).' - '.date('F', mktime(0, 0, 0, substr($row['programEnd'], 5, 2))).' '.substr($row['programStart'], 0, 4).')';
                }
            } else {
                $date = ' ('.date('F', mktime(0, 0, 0, substr($row['programStart'], 5, 2))).' '.substr($row['programStart'], 0, 4).' - '.date('F', mktime(0, 0, 0, substr($row['programEnd'], 5, 2))).' '.substr($row['programEnd'], 0, 4).')';
            }
        }

        echo '<h2>';
        echo __('Participants for').' '.$row['name'].$date;
        echo '</h2>';

        $form = Form::createBlank('buttons');
        $form->addHeaderAction('print', __('Print'))
            ->setURL('#')
            ->onClick('javascript:window.print(); return false;');
        echo $form->getOutput();

        $lastPerson = '';
        $count = 0;

        $pages = array_chunk($results, 30);
        $pageCount = 1;
        foreach ($pages as $pagenum => $page) {

            echo "<table class='mini colorOddEven' cellspacing='0' style='width: 100%'>";
            echo "<tr class='head'>";
            echo '<th>';
            echo __('Student');
            echo '</th>';
            echo "<th colspan=$numberOfColumns>";
            echo __('Attendance');
            echo '</th>';
            echo '</tr>';
            echo "<tr style='height: 75px' class='odd'>";
            echo "<td style='vertical-align:top; width: 120px'>".__('Date')."</td>";
            for ($i = 1; $i <= $numberOfColumns; ++$i) {
                echo "<td style='color: #bbb; vertical-align:top; width: 15px'>$i</td>";
            }
            echo '</tr>';

            $rowNum = 'odd';

            $data = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'gibbonActivityID' => $gibbonActivityID);
            $sql = "SELECT gibbonPerson.gibbonPersonID, surname, preferredName, gibbonFormGroupID, gibbonActivityStudent.status FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonActivityStudent ON (gibbonActivityStudent.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonActivityStudent.status='Accepted' AND gibbonActivityID=:gibbonActivityID ORDER BY gibbonActivityStudent.status, surname, preferredName";
            $result = $connection2->prepare($sql);
            $result->execute($data);
            while ($row = $result->fetch()) {
                ++$count;

                //COLOR ROW BY STATUS!
                echo '<tr>';
                echo '<td>';
                echo $count.'. '.Format::name('', $row['preferredName'], $row['surname'], 'Student', true);
                echo '</td>';
                for ($i = 1; $i <= $numberOfColumns; ++$i) {
                    echo '<td></td>';
                }
                echo '</tr>';

                $lastPerson = $row['gibbonPersonID'];
            }

            echo '</table>';

            if ($pageCount < count($pages)) {
                echo "<div class='page-break'></div>";
            }
            ++$pageCount;
        }

    }
}
