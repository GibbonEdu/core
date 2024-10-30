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
use Gibbon\Services\Format;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Tables\Prefab\ReportTable;
use Gibbon\Forms\PersonalDocumentHandler;
use Gibbon\Domain\User\PersonalDocumentGateway;
use Gibbon\Domain\User\PersonalDocumentTypeGateway;

if (isActionAccessible($guid, $connection2, '/modules/Students/report_student_personalDocumentSummary.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $viewMode = $_REQUEST['format'] ?? '';
    $choices = $_POST['gibbonPersonID'] ?? [];
    $documents = $_POST['documents'] ?? [];
    $gibbonSchoolYearID = $session->get('gibbonSchoolYearID');

    if (isset($_GET['gibbonPersonIDList'])) {
        $choices = explode(',', $_GET['gibbonPersonIDList']);
    } else {
        $_GET['gibbonPersonIDList'] = implode(',', $choices);
    }

    $personalDocumentGateway = $container->get(PersonalDocumentGateway::class);
    $personalDocumentTypeGateway = $container->get(PersonalDocumentTypeGateway::class);

    if (empty($viewMode)) {
        $page->breadcrumbs->add(__('Personal Document Summary'));

        $form = Form::create('filter', $session->get('absoluteURL').'/index.php?q=/modules/Students/report_student_personalDocumentSummary.php');

        $form->setTitle(__('Choose Students'));
        $form->setDescription(__('This report prints a summary of personal documents including passports and ID cards for the selected students.'));
        $form->setFactory(DatabaseFormFactory::create($pdo));
        $form->setClass('noIntBorder fullWidth');

        $row = $form->addRow();
            $row->addLabel('gibbonPersonID', __('Students'));
            $row->addSelectStudent('gibbonPersonID', $session->get('gibbonSchoolYearID'), ['allStudents' => false, 'byName' => true, 'byForm' => true])
                ->isRequired()
                ->selectMultiple()
                ->selected($choices);

        $row = $form->addRow();
            $row->addLabel('documents', __('Document Type'));
            $row->addSelect('documents')
                ->fromArray($personalDocumentTypeGateway->selectDocumentTypes()->fetchKeyPair())
                ->selectMultiple()
                ->setSize(5)
                ->selected($documents);

        $row = $form->addRow();
            $row->addFooter();
            $row->addSearchSubmit($session);

        echo $form->getOutput();
    }

    if (empty($choices)) {
        return;
    }

    // CRITERIA
    $criteria = $personalDocumentGateway->newQueryCriteria(true)
        ->sortBy(['gibbonPerson.surname', 'gibbonPerson.preferredName'])
        ->filterBy('documents', is_array($documents) ? implode(',', $documents) : $documents)
        ->pageSize(!empty($viewMode) ? 0 : 50)
        ->fromPOST();

    $students = $personalDocumentGateway->queryStudentDocuments($criteria, $gibbonSchoolYearID, $choices);

    // DATA TABLE
    $table = ReportTable::createPaginated('studentPersonalDocumentSummary', $criteria)->setViewMode($viewMode, $session);
    $table->setTitle(__('Personal Document Summary'));

    $table->addMetaData('post', ['gibbonPersonID' => $choices, 'documents' => $documents]);

    $table->addColumn('formGroup', __('Form Group'));
    
    $table->addColumn('student', __('Student'))
        ->sortable(['gibbonPerson.surname', 'gibbonPerson.preferredName'])
        ->format(function ($student) {
            $output = Format::nameLinked($student['gibbonPersonID'], '', $student['preferredName'], $student['surname'], 'Student', true, true, ['subpage' => 'Personal']);
            return $output;
        });
    
    $table->addColumn('documentTypeName', __('Document'))
        ->description(__('Type'))
        ->format(function ($values) {
            $output = $values['documentTypeName'].'<br/>'.Format::small($values['documentType']);
            return $output;
        });

    $table->addColumn('documentNumber', __('Document Number'))
        ->description(__('Name on Document'))
        ->format(function ($values) {
            $output = $values['documentNumber'].'<br/>'.Format::small($values['documentName']);
            return $output;
        });

    $table->addColumn('dateIssue', __('Issue Date'))->format(Format::using('date', 'dateIssue'));
    $table->addColumn('dateExpiry', __('Expiry Date'))->format(Format::using('date', 'dateExpiry'));
    $table->addColumn('country', __('Country'));

    $table->addActionColumn()
        ->format(function ($values, $actions) use ($session) {
            if ($values['filePath']) {
                $actions->addAction('view', __('View').' '.__($values['document']))
                    ->setExternalURL($session->get('absoluteURL').'/'.$values['filePath'])
                    ->directLink();

                $actions->addAction('export', __('Download').' '.__($values['document']))
                    ->setExternalURL($session->get('absoluteURL').'/'.$values['filePath'], null, true)
                    ->directLink();
            }
        });

    echo $table->render($students);
}
