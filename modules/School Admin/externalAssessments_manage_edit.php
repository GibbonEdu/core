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
use Gibbon\Tables\DataTable;
use Gibbon\Services\Format;
use Gibbon\Domain\School\ExternalAssessmentGateway;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/School Admin/externalAssessments_manage_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs
        ->add(__('Manage External Assessments'), 'externalAssessments_manage.php')
        ->add(__('Edit External Assessment'));

    //Check if gibbonExternalAssessmentID specified
    $gibbonExternalAssessmentID = $_GET['gibbonExternalAssessmentID'] ?? '';
    if ($gibbonExternalAssessmentID == '') {
        $page->addError(__('You have not specified one or more required parameters.'));
    } else {
        
            $data = array('gibbonExternalAssessmentID' => $gibbonExternalAssessmentID);
            $sql = 'SELECT * FROM gibbonExternalAssessment WHERE gibbonExternalAssessmentID=:gibbonExternalAssessmentID';
            $result = $connection2->prepare($sql);
            $result->execute($data);

        if ($result->rowCount() != 1) {
            $page->addError(__('The specified record cannot be found.'));
        } else {
            //Let's go!
            $values = $result->fetch();

            $form = Form::create('externalAssessmentEdit', $session->get('absoluteURL').'/modules/'.$session->get('module').'/externalAssessments_manage_editProcess.php?gibbonExternalAssessmentID='.$gibbonExternalAssessmentID);

            $form->addHiddenValue('address', $session->get('address'));
            $form->addHiddenValue('gibbonExternalAssessmentID', $gibbonExternalAssessmentID);

            $row = $form->addRow();
                $row->addLabel('name', __('Name'))->description(__('Must be unique.'));
                $row->addTextField('name')->required()->maxLength(50);

            $row = $form->addRow();
                $row->addLabel('nameShort', __('Short Name'))->description(__('Must be unique.'));
                $row->addTextField('nameShort')->required()->maxLength(10);

            $row = $form->addRow();
                $row->addLabel('description', __('Description'))->description(__('Brief description of assessment and how it is used.'));
                $row->addTextField('description')->required()->maxLength(255);

            $row = $form->addRow();
                $row->addLabel('active', __('Active'));
                $row->addYesNo('active')->required();

            $row = $form->addRow();
                $row->addLabel('allowFileUpload', __('Allow File Upload'))->description(__('Should the student record include the option of a file upload?'));
                $row->addYesNo('allowFileUpload')->required()->selected('N');

            $row = $form->addRow();
                $row->addFooter();
                $row->addSubmit();

            $form->loadAllValuesFrom($values);

            echo $form->getOutput();

            echo '<h2>';
            echo __('Edit Fields');
            echo '</h2>';

            $externalAssessmentGateway = $container->get(ExternalAssessmentGateway::class);

            // QUERY
            $criteria = $externalAssessmentGateway->newQueryCriteria(true)
                ->sortBy(['category', 'order'])
                ->fromPOST();

            $externalAssessments = $externalAssessmentGateway->queryExternalAssessmentFields($criteria, $gibbonExternalAssessmentID);

            // DATA TABLE
            $table = DataTable::createPaginated('externalAssessmentManage', $criteria);

            $table->addHeaderAction('add', __('Add'))
                ->setURL('/modules/School Admin/externalAssessments_manage_edit_field_add.php')
                ->addParam('gibbonExternalAssessmentID', $gibbonExternalAssessmentID)
                ->displayLabel();

            $table->addColumn('name', __('Name'));
            $table->addColumn('category', __('Category'));
            $table->addColumn('order', __('Order'));
                
            // ACTIONS
            $table->addActionColumn()
                ->addParam('gibbonExternalAssessmentID', $gibbonExternalAssessmentID)
                ->addParam('gibbonExternalAssessmentFieldID')
                ->format(function ($externalAssessment, $actions) {
                    $actions->addAction('edit', __('Edit'))
                            ->setURL('/modules/School Admin/externalAssessments_manage_edit_field_edit.php');

                    $actions->addAction('delete', __('Delete'))
                            ->setURL('/modules/School Admin/externalAssessments_manage_edit_field_delete.php');
                });

            echo $table->render($externalAssessments);
        }
    }
}
