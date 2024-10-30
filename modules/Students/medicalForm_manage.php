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
use Gibbon\Tables\DataTable;
use Gibbon\Services\Format;
use Gibbon\Domain\Students\MedicalGateway;

if (isActionAccessible($guid, $connection2, '/modules/Students/medicalForm_manage.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs->add(__('Manage Medical Forms'));

    $search = isset($_GET['search'])? $_GET['search'] : '';

    $medicalGateway = $container->get(MedicalGateway::class);

    // CRITERIA
    $criteria = $medicalGateway->newQueryCriteria(true)
        ->searchBy($medicalGateway->getSearchableColumns(), $search)
        ->sortBy(['surname', 'preferredName'])
        ->fromPOST();

    echo '<h2>';
    echo __('Search');
    echo '</h2>';

    $form = Form::create('filter', $session->get('absoluteURL').'/index.php', 'get');
    $form->setClass('noIntBorder fullWidth');

    $form->addHiddenValue('q', '/modules/'.$session->get('module').'/medicalForm_manage.php');

    $row = $form->addRow();
        $row->addLabel('search', __('Search For'))->description(__('Preferred, surname, username.'));
        $row->addTextField('search')->setValue($criteria->getSearchText());

    $row = $form->addRow();
        $row->addSearchSubmit($session, __('Clear Search'));

    echo $form->getOutput();

    echo '<h2>';
    echo __('View');
    echo '</h2>';

    $medicalForms = $medicalGateway->queryMedicalFormsBySchoolYear($criteria, $session->get('gibbonSchoolYearID'));

    // DATA TABLE
    $table = DataTable::createPaginated('medicalForms', $criteria);

    $table->addHeaderAction('add', __('Add'))
        ->setURL('/modules/Students/medicalForm_manage_add.php')
        ->addParam('search', $criteria->getSearchText(true))
        ->displayLabel();

    // COLUMNS
    $table->addExpandableColumn('comment')->format(function($person) {
        return !empty($person['comment'])? '<b>'.__('Comment').'</b><br/>'.nl2br($person['comment']) : '';
    });

    $table->addColumn('student', __('Student'))
        ->sortable(['surname', 'preferredName'])
        ->format(Format::using('name', ['', 'preferredName', 'surname', 'Student', true]));

    $table->addColumn('formGroup', __('Form Group'));

    $table->addColumn('longTermMedication', __('Medication'))
        ->format(function($person) {
            return !empty($person['longTermMedicationDetails'])? $person['longTermMedicationDetails'] : Format::yesNo($person['longTermMedication']);
        });

    $table->addColumn('conditionCount', __('Conditions'));

    // ACTIONS
    $table->addActionColumn()
        ->addParam('gibbonPersonMedicalID')
        ->addParam('search', $criteria->getSearchText(true))
        ->format(function ($person, $actions) {
            $actions->addAction('edit', __('Edit'))
                ->setURL('/modules/Students/medicalForm_manage_edit.php');

            $actions->addAction('delete', __('Delete'))
                ->setURL('/modules/Students/medicalForm_manage_delete.php');
        });

    echo $table->render($medicalForms);
}
