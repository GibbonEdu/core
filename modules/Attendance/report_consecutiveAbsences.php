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
use Gibbon\Module\Attendance\AttendanceView;
use Gibbon\Services\Format;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

// set page breadcrumb
$page->breadcrumbs->add(__('Consecutive Absences'));

if (isActionAccessible($guid, $connection2, '/modules/Attendance/consecutiveAbsences.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __('You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    $numberOfSchoolDays = (!empty($_GET['numberOfSchoolDays']) && is_numeric($_GET['numberOfSchoolDays'])) ? $_GET['numberOfSchoolDays'] : 7;

    require_once __DIR__ . '/src/AttendanceView.php';
    $attendance = new AttendanceView($gibbon, $pdo);

    $form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/index.php', 'get');

    $form->setClass('noIntBorder fullWidth');

    $form->setTitle('Filter');

    $form->addHiddenValue('q', "/modules/".$_SESSION[$guid]['module']."/report_consecutiveAbsences.php");

    $row = $form->addRow();
        $row->addLabel('numberOfSchoolDays', __('Number of School Day'));
        $row->addNumber('numberOfSchoolDays')->setValue($numberOfSchoolDays)->required()->minimum(1)->maximum(99);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSearchSubmit($gibbon->session);

    echo $form->getOutput();

    if (!empty($_GET['numberOfSchoolDays']) && is_numeric($_GET['numberOfSchoolDays'])) {
        echo '<h2>';
        echo __('Report Data');
        echo '</h2>';

        $today = date('Y-m-d');
        $dates = getLastNSchoolDays($guid, $connection2, $today, $numberOfSchoolDays, true);
        if (!is_array($dates) || count($dates) != $numberOfSchoolDays) {
            echo "<div class='error'>";
            echo __('There are no records to display.');
            echo '</div>';
        }
        else {
            try {
                $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                $sql = "SELECT gibbonPerson.gibbonPersonID, surname, preferredName, gibbonRollGroup.gibbonRollGroupID, gibbonRollGroup.name as rollGroupName, gibbonRollGroup.nameShort AS rollGroup
                    FROM gibbonPerson
                        JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID)
                        LEFT JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID)
                    WHERE status='Full'
                        AND (dateStart IS NULL OR dateStart<='".$today."')
                        AND (dateEnd IS NULL  OR dateEnd>='".$today."')
                        AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID
                    ORDER BY surname, preferredName, LENGTH(rollGroup), rollGroup";
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }

            if ($result->rowCount() < 1) {
                echo "<div class='error'>";
                echo __('There are no records to display.');
                echo '</div>';
            } else {
                echo '<table cellspacing="0" class="fullWidth colorOddEven" >';
                echo "<tr class='head'>";
                echo '<th>';
                echo __('Count');
                echo '</th>';
                echo '<th style="width:80px">';
                echo __('Roll Group');
                echo '</th>';
                echo '<th>';
                echo __('Name');
                echo '</th>';
                echo '</tr>';

                $count = 0;
                while ($row = $result->fetch()) {
                    $absenceCount = getAbsenceCount($guid, $row['gibbonPersonID'], $connection2, end($dates), $today);
                    if ($absenceCount >= $numberOfSchoolDays) {
                        $count ++;
                        echo "<tr>";
                            echo '<td>';
                                echo $count;
                            echo '</td>';
                            echo '<td>';
                                echo $row['rollGroupName'];
                            echo '</td>';
                            echo '<td>';
                                echo Format::name('', $row['preferredName'], $row['surname'], 'Student', true);
                            echo '</td>';
                        echo '</tr>';
                    }
                }
                if ($count == 0) {
                    echo "<tr>";
                    echo '<td colspan=5>';
                    echo __('All students are present.');
                    echo '</td>';
                    echo '</tr>';
                }
                echo '</table>';
            }
        }
    }
}
