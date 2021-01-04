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
use Gibbon\Domain\DataSet;
use Gibbon\Tables\DataTable;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

// set page breadcrumb
$page->breadcrumbs->add(__('Consecutive Absences'));

if (isActionAccessible($guid, $connection2, '/modules/Attendance/consecutiveAbsences.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
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
        $row->addLabel('numberOfSchoolDays', __('Number of School Days'));
        $row->addNumber('numberOfSchoolDays')->setValue($numberOfSchoolDays)->required()->minimum(1)->maximum(99);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSearchSubmit($gibbon->session);

    echo $form->getOutput();

    if (!empty($_GET['numberOfSchoolDays']) && is_numeric($_GET['numberOfSchoolDays'])) {
        //Get an array of days school is in session
        $dates = getLastNSchoolDays(
            $gibbon->session->get('guid'),
            $connection2,
            date("Y-m-d"),
            $numberOfSchoolDays,
            true
        );
        if (!is_array($dates) || count($dates) != $numberOfSchoolDays) {
            echo "<div class='error'>";
            echo __('There are no records to display.');
            echo '</div>';
        } else {

            $data = array('gibbonSchoolYearID' => $gibbon->session->get('gibbonSchoolYearID'));
            $sql = "
                SELECT 
                  gibbonPerson.gibbonPersonID, 
                  gibbonPerson.title, 
                  gibbonPerson.surname, 
                  gibbonPerson.preferredName, 
                  gibbonRollGroup.gibbonRollGroupID, 
                  gibbonRollGroup.name as rollGroupName, 
                  gibbonRollGroup.nameShort AS rollGroup
                FROM gibbonPerson
                JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID)
                LEFT JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID)
                WHERE status='Full'
                  AND (dateStart IS NULL OR dateStart <= CURRENT_TIMESTAMP)
                  AND (dateEnd IS NULL  OR dateEnd >= CURRENT_TIMESTAMP)
                  AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID
                ORDER BY surname, preferredName, LENGTH(rollGroup), rollGroup";

            $result = $connection2->prepare($sql);
            $result->execute($data);

            $absences = array_map(function ($row) use ($gibbon, $connection2, $dates) {
              // Get number of absences within date range
                $row['count'] = getAbsenceCount(
                    $gibbon->session->get('guid'),
                    $row['gibbonPersonID'],
                    $connection2,
                    end($dates),
                    date('Y-m-d')
                );
                return $row;
            }, $result->fetchAll());

            $absences = array_filter($absences, function ($row) use ($numberOfSchoolDays) {
                return ($row['count'] >= $numberOfSchoolDays);
            });

            $table = DataTable::create('report');
            $table->setTitle(__('Report Data'));
            $table->setDescription(__("A list of students who were absent during the provided period."));
            $table->addColumn('count', __('Number Of Absences'));
            $table->addColumn('rollGroupName', __('Roll Group'))
                  ->format(function ($absence) {
                    return Format::bold($absence['rollGroupName']);
                  });
            $table->addColumn('name', __('Name'))
                  ->format(function ($absence) {
                    return Format::name(
                        $absence['title'],
                        $absence['preferredName'],
                        $absence['surname'],
                        'Student',
                        true
                    );
                  });

            echo $table->render($absences);
        }
    }
}
