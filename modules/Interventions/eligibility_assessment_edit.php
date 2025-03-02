<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright 2010, Gibbon Foundation
Gibbon, Gibbon Education Ltd. (Hong Kong)

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
use Gibbon\Services\Format;
use Gibbon\Domain\Students\StudentGateway;
use Gibbon\Domain\Interventions\INReferralGateway;
use Gibbon\Domain\Interventions\INEligibilityAssessmentGateway;
use Gibbon\FileUploader;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Interventions/eligibility_assessment_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Get action with highest precedence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if (empty($highestAction)) {
        $page->addError(__('The highest grouped action cannot be determined.'));
    } else {
        // Proceed!
        $gibbonINEligibilityAssessmentID = $_GET['gibbonINEligibilityAssessmentID'] ?? '';

        if (empty($gibbonINEligibilityAssessmentID)) {
            $page->addError(__('You have not specified one or more required parameters.'));
            return;
        }

        // Get the eligibility assessment
        $eligibilityAssessmentGateway = $container->get(INEligibilityAssessmentGateway::class);
        $assessment = $eligibilityAssessmentGateway->getByID($gibbonINEligibilityAssessmentID);

        if (empty($assessment)) {
            $page->addError(__('The specified record cannot be found.'));
            return;
        }

        // Check access - must be the assigned assessor or have admin rights
        if ($highestAction == 'Manage Eligibility Assessments_my' && $assessment['gibbonPersonIDAssessor'] != $session->get('gibbonPersonID')) {
            $page->addError(__('You do not have access to this action.'));
            return;
        }

        // Get referral
        $referralGateway = $container->get(INReferralGateway::class);
        $referral = $referralGateway->getByID($assessment['gibbonINReferralID']);

        if (empty($referral)) {
            $page->addError(__('The specified record cannot be found.'));
            return;
        }

        // Get assessment type name
        $sql = "SELECT name, description FROM gibbonINEligibilityAssessmentType WHERE gibbonINEligibilityAssessmentTypeID=:gibbonINEligibilityAssessmentTypeID";
        $result = $pdo->select($sql, ['gibbonINEligibilityAssessmentTypeID' => $assessment['gibbonINEligibilityAssessmentTypeID']]);
        $assessmentType = ($result->rowCount() > 0) ? $result->fetch() : ['name' => __('Unknown'), 'description' => ''];

        // Get student details
        $studentGateway = $container->get(StudentGateway::class);
        $student = $studentGateway->selectActiveStudentByPerson($session->get('gibbonSchoolYearID'), $referral['gibbonPersonIDStudent'])->fetch();

        if (empty($student)) {
            $page->addError(__('The specified record cannot be found.'));
            return;
        }

        $studentName = Format::name('', $student['preferredName'], $student['surname'], 'Student', true);

        $page->breadcrumbs
            ->add(__('Manage Eligibility Assessments'), 'eligibility_manage.php')
            ->add(__('Edit Assessment'));

        $form = Form::create('assessmentEdit', $session->get('absoluteURL').'/modules/Intervention/eligibility_assessment_editProcess.php');
        $form->setFactory(DatabaseFormFactory::create($pdo));

        $form->addHiddenValue('address', $session->get('address'));
        $form->addHiddenValue('gibbonINEligibilityAssessmentID', $gibbonINEligibilityAssessmentID);

        $form->addRow()->addHeading(__('Student Details'));

        $row = $form->addRow();
            $row->addLabel('studentName', __('Student'));
            $row->addTextField('studentName')->setValue($studentName)->readonly();

        $row = $form->addRow();
            $row->addLabel('formGroup', __('Form Group'));
            $row->addTextField('formGroup')->setValue($student['formGroup'])->readonly();

        $row = $form->addRow();
            $row->addLabel('yearGroup', __('Year Group'));
            $row->addTextField('yearGroup')->setValue($student['yearGroup'])->readonly();

        $form->addRow()->addHeading(__('Assessment Details'));

        $row = $form->addRow();
            $row->addLabel('assessmentType', __('Assessment Type'));
            $row->addTextField('assessmentType')->setValue($assessmentType['name'])->readonly();

        if (!empty($assessmentType['description'])) {
            $row = $form->addRow();
                $row->addLabel('assessmentDescription', __('Description'));
                $row->addContent($assessmentType['description']);
        }

        $row = $form->addRow();
            $row->addLabel('result', __('Result'))->description(__('Pass indicates the student meets criteria for intervention'));
            $options = [
                'Pass' => __('Pass'),
                'Fail' => __('Fail'),
                'Inconclusive' => __('Inconclusive')
            ];
            $row->addSelect('result')->fromArray($options)->required()->selected($assessment['result'] ?? 'Inconclusive');

        $row = $form->addRow();
            $row->addLabel('date', __('Assessment Date'));
            $row->addDate('date')->setValue($assessment['date'] ?? date('Y-m-d'))->required();

        $row = $form->addRow();
            $row->addLabel('notes', __('Assessment Notes'))->description(__('Detailed findings from the assessment'));
            $row->addTextArea('notes')->setRows(10)->setValue($assessment['notes'] ?? '')->required();

        // File upload
        $fileUploader = new FileUploader($pdo, $session);
        $row = $form->addRow();
            $row->addLabel('documentFile', __('Upload Document'))->description(__('Upload any supporting documentation'));
            $row->addFileUpload('documentFile')
                ->accepts($fileUploader->getFileExtensions())
                ->setMaxUpload(false);

        $row = $form->addRow();
            $row->addSubmit();

        echo $form->getOutput();
    }
}
