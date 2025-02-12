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

use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Forms\Form;
use Gibbon\Domain\Activities\ActivityCategoryGateway;
use Gibbon\Domain\Activities\ActivityChoiceGateway;

if (isActionAccessible($guid, $connection2, '/modules/Activities/choices_manage.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs
        ->add(__('Manage Choices'));

    $choiceGateway = $container->get(ActivityChoiceGateway::class);
    $categoryGateway = $container->get(ActivityCategoryGateway::class);

    $categories = $categoryGateway->selectCategoriesBySchoolYear($session->get('gibbonSchoolYearID'))->fetchKeyPair();

    $params = [
        'search' => $_REQUEST['search'] ?? '',
        'gibbonActivityCategoryID' => $_REQUEST['gibbonActivityCategoryID'] ?? '',
    ];

    // CRITERIA
    $criteria = $choiceGateway->newQueryCriteria(true)
        ->searchBy($choiceGateway->getSearchableColumns(), $params['search'])
        ->filterBy('category', $params['gibbonActivityCategoryID'])
        ->sortBy(['surname', 'preferredName'])
        ->fromPOST();

    // SEARCH
    $form = Form::create('filters', $session->get('absoluteURL').'/index.php', 'get');
    $form->setClass('noIntBorder fullWidth');

    $form->addHiddenValue('q', '/modules/Activities/choices_manage.php');

    $row = $form->addRow();
        $row->addLabel('gibbonActivityCategoryID', __('Category'));
        $row->addSelect('gibbonActivityCategoryID')->fromArray($categories)->placeholder()->selected($params['gibbonActivityCategoryID']);

    $row = $form->addRow();
        $row->addLabel('search', __('Search For'))->description(__('Preferred name, surname, activity name'));
        $row->addTextField('search')->setValue($criteria->getSearchText())->maxLength(20);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSearchSubmit($session, 'Clear Filters', ['view', 'sidebar']);

    echo $form->getOutput();

    $choices = $choiceGateway->queryChoices($criteria, $session->get('gibbonSchoolYearID'));

    // TABLE
    $table = DataTable::createPaginated('choices', $criteria);

    $table->addHeaderAction('generate', __('Generate Enrolments'))
        ->setURL('/modules/Activities/choices_manage_generate.php')
        ->addParam('gibbonActivityCategoryID', $params['gibbonActivityCategoryID'])
        ->addParam('sidebar', 'false')
        ->setIcon('run')
        ->displayLabel()
        ->append('&nbsp;|&nbsp;');

    $table->addHeaderAction('add', __('Add'))
        ->setURL('/modules/Activities/choices_manage_addEdit.php')
        ->addParam('gibbonActivityCategoryID', $params['gibbonActivityCategoryID'])
        ->addParam('mode', 'add')
        ->displayLabel();

    $table->addColumn('image_240', __('Photo'))
        ->context('primary')
        ->width('8%')
        ->notSortable()
        ->format(Format::using('userPhoto', ['image_240', 'xs']));

    $table->addColumn('student', __('Person'))
        ->sortable(['gibbonPerson.surname', 'gibbonPerson.preferredName'])
        ->width('25%')
        ->format(function ($values) {
            return Format::nameLinked($values['gibbonPersonID'], '', $values['preferredName'], $values['surname'], 'Student', true, true);
        });

    $table->addColumn('formGroup', __('FormGroup'))->context('secondary');

    $table->addColumn('categoryName', __('Category'))
        ->width('8%');

    $table->addColumn('choiceList', __('Choices'))
        ->context('primary')
        ->width('30%')
        ->format(function ($values) {
            if (empty($values['choices'])) return '';
            $output = '';
            $choices = explode(',', $values['choiceList']);
            foreach ($choices as $choice) {
                list($choiceNumber,$choiceName) = array_pad(explode(':', $choice),2,'');
                $output .= $choiceNumber.'. '.$choiceName.'<br/>';
            }
            return $output;
        });

    $table->addColumn('timestampModified', __('When'))
        ->format(Format::using('dateReadable', 'timestampModified'))
        ->width('15%');

    // ACTIONS
    $table->addActionColumn()
        ->addParam('gibbonActivityCategoryID')
        ->addParam('gibbonPersonID')
        ->format(function ($values, $actions) {
            $actions->addAction('edit', __('Edit'))
                    ->setURL('/modules/Activities/choices_manage_addEdit.php')
                    ->addParam('mode', 'edit');

            $actions->addAction('delete', __('Delete'))
                    ->setURL('/modules/Activities/choices_manage_delete.php');
        });

    echo $table->render($choices);
}
