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
use Gibbon\Tables\Prefab\ReportTable;
use Gibbon\Services\Format;
use Gibbon\Domain\Students\StudentGateway;
use Gibbon\Domain\Students\MedicalGateway;
use Gibbon\Domain\RollGroups\RollGroupGateway;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Students/report_students_byRollGroup.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!

    $gibbonRollGroupID = (isset($_GET['gibbonRollGroupID']) ? $_GET['gibbonRollGroupID'] : null);
    $view = isset($_GET['view']) ? $_GET['view'] : 'basic';
    $viewMode = isset($_REQUEST['format']) ? $_REQUEST['format'] : '';

    if (empty($viewMode)) {
        $page->breadcrumbs->add(__('Students by Roll Group'));

        $form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/index.php', 'get');
        $form->setTitle(__('Choose Roll Group'))
            ->setFactory(DatabaseFormFactory::create($pdo))
            ->setClass('noIntBorder fullWidth');

        $form->addHiddenValue('q', "/modules/".$_SESSION[$guid]['module']."/report_students_byRollGroup.php");

        $row = $form->addRow();
            $row->addLabel('gibbonRollGroupID', __('Roll Group'));
            $row->addSelectRollGroup('gibbonRollGroupID', $_SESSION[$guid]['gibbonSchoolYearID'], true)->selected($gibbonRollGroupID)->placeholder()->required();

        $row = $form->addRow();
            $row->addLabel('view', __('View'));
            $row->addSelect('view')->fromArray(array('basic' => __('Basic'), 'extended' =>__('Extended')))->selected($view)->required();

        $row = $form->addRow();
            $row->addFooter();
            $row->addSearchSubmit($gibbon->session);

        echo $form->getOutput();
    }

    // Cancel out early if there's no roll group selected
    if (empty($gibbonRollGroupID)) return;

    $rollGroupGateway = $container->get(RollGroupGateway::class);
    $studentGateway = $container->get(StudentGateway::class);
    $medicalGateway = $container->get(MedicalGateway::class);

    // QUERY
    $criteria = $studentGateway->newQueryCriteria(true)
        ->sortBy(['rollGroup', 'surname', 'preferredName'])
        ->pageSize(!empty($viewMode) ? 0 : 50)
        ->filterBy('view', $view)
        ->fromArray($_POST);
    
    $students = $studentGateway->queryStudentEnrolmentByRollGroup($criteria, $gibbonRollGroupID != '*' ? $gibbonRollGroupID : null);

    // DATA TABLE
    $table = ReportTable::createPaginated('studentsByRollGroup', $criteria)->setViewMode($viewMode, $gibbon->session);
    $table->setTitle(__('Report Data'));
    $table->setDescription(function () use ($gibbonRollGroupID, $rollGroupGateway) {
        $output = '';

        if ($gibbonRollGroupID == '*') return $output;
        
        if ($rollGroup = $rollGroupGateway->getRollGroupByID($gibbonRollGroupID)) {
            $output .= '<b>'.__('Roll Group').'</b>: '.$rollGroup['name'];
        }
        if ($tutors = $rollGroupGateway->selectTutorsByRollGroup($gibbonRollGroupID)->fetchAll()) {
            $output .= '<br/><b>'.__('Tutors').'</b>: '.Format::nameList($tutors, 'Staff');
        }

        return $output;
    });

    $table->addMetaData('filterOptions', [
        'view:basic'    => __('View').': '.__('Basic'),
        'view:extended' => __('View').': '.__('Extended'),
    ]);

    $table->addColumn('rollGroup', __('Roll Group'))->width('5%');
    $table->addColumn('student', __('Student'))
        ->sortable(['surname', 'preferredName'])
        ->format(function ($person) {
            return Format::name('', $person['preferredName'], $person['surname'], 'Student', true, true) . '<br/><small><i>'.Format::userStatusInfo($person).'</i></small>';
        });

    if ($criteria->hasFilter('view', 'extended')) {
        $table->addColumn('gender', __('Gender'));
        $table->addColumn('dob', __('Age').'<br/>'.Format::small('DOB'))
            ->format(function ($values) {
                return !empty($values['dob'])
                    ? Format::age($values['dob'], true).'<br/>'.Format::small(Format::date($values['dob']))
                    : '';
            });
        $table->addColumn('citizenship1', __('Nationality'))
            ->format(function ($values) {
                $output = '';
                if (!empty($values['citizenship1'])) {
                    $output .= $values['citizenship1'].'<br/>';
                }
                if (!empty($values['citizenship2'])) {
                    $output .= $values['citizenship2'].'<br/>';
                }
                return $output;
            });
        $table->addColumn('transport', __('Transport'));
        $table->addColumn('house', __('House'));
        $table->addColumn('lockerNumber', __('Locker'));
        $table->addColumn('longTermMedication', __('Medical'))->format(function ($values) use ($medicalGateway) {
            $output = '';

            if (!empty($values['longTermMedication'])) {
                if ($values['longTermMedication'] == 'Y') {
                    $output .= '<b><i>'.__('Long Term Medication').'</i></b>: '.$values['longTermMedicationDetails'].'<br/>';
                }

                if ($values['conditionCount'] > 0) {
                    $conditions = $medicalGateway->selectMedicalConditionsByID($values['gibbonPersonMedicalID'])->fetchAll();

                    foreach ($conditions as $index => $condition) {
                        $output .= '<b><i>'.__('Condition').' '.($index+1).'</i></b>: '.$condition['name'];
                        $output .= ' <span style="color: '.$condition['alertColor'].'; font-weight: bold">('.__($condition['risk']).' '.__('Risk').')</span>';
                        $output .= '<br/>';
                    }
                }
            } else {
                $output = '<i>'.__('No medical data').'</i>';
            }

            return $output;
        });
    }
    
    echo $table->render($students);
}
