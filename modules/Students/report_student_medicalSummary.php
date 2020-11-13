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

use Gibbon\View\View;
use Gibbon\Services\Format;
use Gibbon\Forms\Form;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Tables\Prefab\ReportTable;
use Gibbon\Domain\Students\MedicalGateway;
use Gibbon\Domain\Students\StudentReportGateway;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Students/report_student_medicalSummary.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $viewMode = $_REQUEST['format'] ?? '';
    $choices = $_POST['gibbonPersonID'] ?? [];
    //If $choices is blank, check to see if session is being used to inject gibbonPersonID list
    if (count($choices) == 0 && !empty($_SESSION[$guid]['report_student_medicalSummary.php_choices'])) {
        $choices = $_SESSION[$guid]['report_student_medicalSummary.php_choices'];
    }
    $gibbonSchoolYearID = $gibbon->session->get('gibbonSchoolYearID');

    if (isset($_GET['gibbonPersonIDList'])) {
        $choices = explode(',', $_GET['gibbonPersonIDList']);
    } else {
        $_GET['gibbonPersonIDList'] = implode(',', $choices);
    }

    if (empty($viewMode)) {
        $page->breadcrumbs->add(__('Student Medical Data Summary'));

        echo '<p>';
        echo __('This report prints a summary of medical data for the selected students.');
        echo '</p>';

        $choices = isset($_POST['gibbonPersonID'])? $_POST['gibbonPersonID'] : array();

        $form = Form::create('action', $_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Students/report_student_medicalSummary.php");
        $form->setTitle(__('Choose Students'));
        $form->setFactory(DatabaseFormFactory::create($pdo));
        $form->setClass('noIntBorder fullWidth');

        $row = $form->addRow();
            $row->addLabel('gibbonPersonID', __('Students'));
            $row->addSelectStudent('gibbonPersonID', $_SESSION[$guid]['gibbonSchoolYearID'], array("allStudents" => false, "byName" => true, "byRoll" => true))
                ->isRequired()
                ->selectMultiple()
                ->selected($choices);

        $row = $form->addRow();
            $row->addFooter();
            $row->addSearchSubmit($gibbon->session);

        echo $form->getOutput();
    }


    if (empty($choices)) {
        return;
    }

    $cutoffDate = getSettingByScope($connection2, 'Data Updater', 'cutoffDate');
    if (empty($cutoffDate)) $cutoffDate = Format::dateFromTimestamp(time() - (604800 * 26));

    $reportGateway = $container->get(StudentReportGateway::class);
    $medicalGateway = $container->get(MedicalGateway::class);

    // CRITERIA
    $criteria = $reportGateway->newQueryCriteria(true)
        ->sortBy(['gibbonPerson.surname', 'gibbonPerson.preferredName'])
        ->pageSize(!empty($viewMode) ? 0 : 50)
        ->fromPOST();

    $students = $reportGateway->queryStudentDetails($criteria, $choices);

    // Join a set of medical conditions per student
    $medicalIDs = $students->getColumn('gibbonPersonMedicalID');
    $medicalConditions = $medicalGateway->selectMedicalConditionsByID($medicalIDs)->fetchGrouped();
    $students->joinColumn('gibbonPersonMedicalID', 'medicalConditions', $medicalConditions);

    // DATA TABLE
    $table = ReportTable::createPaginated('studentEmergencySummary', $criteria)->setViewMode($viewMode, $gibbon->session);
    $table->setTitle(__('Student Medical Data Summary'));

    $table->addMetaData('post', ['gibbonPersonID' => $choices]);

    $table->addColumn('student', __('Student'))
        ->description(__('Last Update'))
        ->sortable(['gibbonPerson.surname', 'gibbonPerson.preferredName'])
        ->format(function ($student) use ($cutoffDate) {
            $output = Format::name('', $student['preferredName'], $student['surname'], 'Student', true, true).'<br/><br/>';

            $output .= ($student['lastMedicalUpdate'] < $cutoffDate) ? '<span style="color: #ff0000; font-weight: bold"><i>' : '<span><i>';
            $output .= !empty($student['lastMedicalUpdate']) ? Format::date($student['lastMedicalUpdate']) : __('N/A');
            $output .= '</i></span>';

            return $output;
        });

    $view = new View($container->get('twig'));

    $table->addColumn('medicalForm', __('Medical Form?'))
        ->width('16%')
        ->sortable('gibbonPersonMedicalID')
        ->format(function ($student) use ($view) {
            return $view->fetchFromTemplate('formats/medicalForm.twig.html', $student);
        });

    $table->addColumn('conditions', __('Medical Conditions'))
        ->width('60%')
        ->notSortable()
        ->format(function ($student) use ($view) {
            return $view->fetchFromTemplate('formats/medicalConditions.twig.html', $student);
        });

    echo $table->render($students);
}
