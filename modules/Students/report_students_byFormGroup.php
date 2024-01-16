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

use Gibbon\Forms\Form;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Tables\Prefab\ReportTable;
use Gibbon\Services\Format;
use Gibbon\Domain\Students\StudentGateway;
use Gibbon\Domain\Students\MedicalGateway;
use Gibbon\Domain\FormGroups\FormGroupGateway;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Students/report_students_byFormGroup.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!

    $gibbonFormGroupID = (isset($_GET['gibbonFormGroupID']) ? $_GET['gibbonFormGroupID'] : null);
    $view = isset($_GET['view']) ? $_GET['view'] : 'basic';
    $viewMode = isset($_REQUEST['format']) ? $_REQUEST['format'] : '';

    if (empty($viewMode)) {
        $page->breadcrumbs->add(__('Students by Form Group'));

        $form = Form::create('action', $session->get('absoluteURL').'/index.php', 'get');
        $form->setTitle(__('Choose Form Group'))
            ->setFactory(DatabaseFormFactory::create($pdo))
            ->setClass('noIntBorder fullWidth');

        $form->addHiddenValue('q', "/modules/".$session->get('module')."/report_students_byFormGroup.php");

        $row = $form->addRow();
            $row->addLabel('gibbonFormGroupID', __('Form Group'));
            $row->addSelectFormGroup('gibbonFormGroupID', $session->get('gibbonSchoolYearID'), true)->selected($gibbonFormGroupID)->placeholder()->required();

        $row = $form->addRow();
            $row->addLabel('view', __('View'));
            $row->addSelect('view')->fromArray(array('basic' => __('Basic'), 'extended' =>__('Extended')))->selected($view)->required();

        $row = $form->addRow();
            $row->addFooter();
            $row->addSearchSubmit($session);

        echo $form->getOutput();
    }
    
    // Cancel out early if there's no form group selected
    if (!isset($gibbonFormGroupID)) return;

    $formGroupGateway = $container->get(FormGroupGateway::class);
    $studentGateway = $container->get(StudentGateway::class);
    $medicalGateway = $container->get(MedicalGateway::class);

    // QUERY
    $criteria = $studentGateway->newQueryCriteria(true)
        ->sortBy(['formGroup', 'surname', 'preferredName'])
        ->pageSize(!empty($viewMode) ? 0 : 50)
        ->filterBy('view', $view)
        ->fromArray($_POST);
    
    $students = $studentGateway->queryStudentEnrolmentByFormGroup($criteria, $gibbonFormGroupID != '*' ? $gibbonFormGroupID : null);

    // DATA TABLE
    $table = ReportTable::createPaginated('studentsByFormGroup', $criteria)->setViewMode($viewMode, $session);
    $table->setTitle(__('Report Data'));
    $table->setDescription(function () use ($gibbonFormGroupID, $formGroupGateway) {
        $output = '';

        if ($gibbonFormGroupID == '*') return $output;
        
        if ($formGroup = $formGroupGateway->getFormGroupByID($gibbonFormGroupID)) {
            $output .= '<b>'.__('Form Group').'</b>: '.$formGroup['name'];
        }
        if ($tutors = $formGroupGateway->selectTutorsByFormGroup($gibbonFormGroupID)->fetchAll()) {
            $output .= '<br/><b>'.__('Tutors').'</b>: '.Format::nameList($tutors, 'Staff');
        }

        return $output;
    });

    $table->addMetaData('filterOptions', [
        'view:basic'    => __('View').': '.__('Basic'),
        'view:extended' => __('View').': '.__('Extended'),
    ]);

    $table->addColumn('formGroup', __('Form Group'))->width('5%');
    $table->addColumn('student', __('Student'))
        ->sortable(['surname', 'preferredName'])
        ->format(function ($person) {
            return Format::name('', $person['preferredName'], $person['surname'], 'Student', true, true) . '<br/><small><i>'.Format::userStatusInfo($person).'</i></small>';
        });

    if ($criteria->hasFilter('view', 'extended')) {
        $table->addColumn('gender', __('Gender'))
                ->format(Format::using('genderName', 'gender'));
        $table->addColumn('dob', __('Age'))
            ->description(__('DOB'))
            ->format(function ($values) {
                return !empty($values['dob'])
                    ? Format::age($values['dob'], true).'<br/>'.Format::small(Format::date($values['dob']))
                    : '';
            });
        $table->addColumn('citizenship', __('Nationality'));
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
