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
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Domain\Students\MedicalGateway;
use Gibbon\Tables\Renderer\PrintableRenderer;

if (isActionAccessible($guid, $connection2, '/modules/Students/medicalForm_manage.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Manage Medical Forms').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $search = isset($_GET['search'])? $_GET['search'] : '';

    $medicalGateway = $container->get(MedicalGateway::class);

    // CRITERIA
    $criteria = $medicalGateway->newQueryCriteria()
        ->searchBy($medicalGateway->getSearchableColumns(), $search)
        ->sortBy(['surname', 'preferredName'])
        ->fromArray($_POST);

    echo '<h2>';
    echo __('Search');
    echo '</h2>';

    $form = Form::create('filter', $_SESSION[$guid]['absoluteURL'].'/index.php', 'get');
    $form->setClass('noIntBorder fullWidth');

    $form->addHiddenValue('q', '/modules/'.$_SESSION[$guid]['module'].'/medicalForm_manage.php');

    $row = $form->addRow();
        $row->addLabel('search', __('Search For'))->description(__('Preferred, surname, username.'));
        $row->addTextField('search')->setValue($criteria->getSearchText());

    $row = $form->addRow();
        $row->addSearchSubmit($gibbon->session, __('Clear Search'));

    echo $form->getOutput();

    echo '<h2>';
    echo __('View');
    echo '</h2>';

    
    // DATA TABLE
    
    $isReport = strtolower(basename($_SERVER['SCRIPT_NAME'], '.php')) == 'report';
    if ($isReport) {
        $table = DataTable::create('medicalForms', new PrintableRenderer());
        $criteria->pageSize(0);
    } else {
        $table = DataTable::createPaginated('medicalForms', $criteria);
    }

    $medicalForms = $medicalGateway->queryMedicalFormsBySchoolYear($criteria, $_SESSION[$guid]['gibbonSchoolYearID']);


    $table->addHeaderAction('print', __('Print'))
        ->setIcon('print')
        ->setURL('/report.php')
        ->addParam('q', '/modules/Students/medicalForm_manage.php')
        ->displayLabel()
        ->isDirect()
        ->append('&nbsp;|&nbsp;');

    $table->addHeaderAction('add', __('Add'))
        ->setURL('/modules/Students/medicalForm_manage_add.php')
        ->addParam('search', $criteria->getSearchText(true))
        ->displayLabel();

    // COLUMNS
    $table->addExpandableColumn('comment')->format(function($person) {
        return !empty($person['comment'])? '<b>'.__('Comment').'</b><br/>'.nl2brr($person['comment']) : '';
    });

    $table->addColumn('student', __('Student'))
        ->sortable(['surname', 'preferredName'])
        ->format(Format::using('name', ['', 'preferredName', 'surname', 'Student', true]));

    $table->addColumn('rollGroup', __('Roll Group'));

    $table->addColumn('bloodType', __('Blood Type'));

    $table->addColumn('longTermMedication', __('Medication'))
        ->format(function($person) {
            return !empty($person['longTermMedicationDetails'])? $person['longTermMedicationDetails'] : Format::yesNo($person['longTermMedication']);
        });

    $table->addColumn('tetanusWithin10Years', __('Tetanus'))
        ->format(Format::using('yesNo', 'tetanusWithin10Years'));

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
