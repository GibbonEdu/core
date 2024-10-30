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
use Gibbon\Domain\Students\FirstAidGateway;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Students/firstAidRecord.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        $page->addError(__('The highest grouped action cannot be determined.'));
        return;
    }

    $page->breadcrumbs->add(__('First Aid Records'));

    $gibbonPersonID = $_GET['gibbonPersonID'] ?? null;
    $gibbonFormGroupID = $_GET['gibbonFormGroupID'] ?? null;
    $gibbonYearGroupID = $_GET['gibbonYearGroupID'] ?? null;
    $gibbonSchoolYearID = $_REQUEST['gibbonSchoolYearID'] ?? $session->get('gibbonSchoolYearID');

    $page->navigator->addSchoolYearNavigation($gibbonSchoolYearID);

    $form = Form::create('action', $session->get('absoluteURL').'/index.php', 'get');
    $form->setTitle(__('Filter'));

    $form->setFactory(DatabaseFormFactory::create($pdo));
    $form->setClass('noIntBorder fullWidth');

    $form->addHiddenValue('q', "/modules/".$session->get('module')."/firstAidRecord.php");

    $row = $form->addRow();
        $row->addLabel('gibbonPersonID', __('Student'));
        $row->addSelectStudent('gibbonPersonID', $gibbonSchoolYearID)->placeholder()->selected($gibbonPersonID);

    $row = $form->addRow();
        $row->addLabel('gibbonFormGroupID', __('Form Group'));
        $row->addSelectFormGroup('gibbonFormGroupID', $gibbonSchoolYearID)->selected($gibbonFormGroupID);

    $row = $form->addRow();
        $row->addLabel('gibbonYearGroupID', __('Year Group'));
        $row->addSelectYearGroup('gibbonYearGroupID')->selected($gibbonYearGroupID);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSearchSubmit($session);

    echo $form->getOutput();

    $firstAidGateway = $container->get(FirstAidGateway::class);

    $criteria = $firstAidGateway->newQueryCriteria(true)
        ->sortBy(['date', 'timeIn'], 'DESC')
        ->filterBy('student', $gibbonPersonID)
        ->filterBy('formGroup', $gibbonFormGroupID)
        ->filterBy('yearGroup', $gibbonYearGroupID)
        ->fromPOST();

    $firstAidRecords = $firstAidGateway->queryFirstAidBySchoolYear($criteria, $gibbonSchoolYearID);

    // DATA TABLE
    $table = DataTable::createPaginated('firstAidRecords', $criteria);
    $table->setTitle(__('First Aid Records'));

    if ($highestAction == 'First Aid Record_editAll') {
        $table->addHeaderAction('add', __('Add'))
            ->setURL('/modules/Students/firstAidRecord_add.php')
            ->addParam('gibbonFormGroupID', $gibbonFormGroupID)
            ->addParam('gibbonYearGroupID', $gibbonYearGroupID)
            ->displayLabel();
    }

    // COLUMNS
    $table->addExpandableColumn('details')->format(function($person) use ($firstAidGateway) {
        $output = '';
        if ($person['description'] != '') $output .= '<b>'.__('Description').'</b><br/>'.nl2br($person['description']).'<br/><br/>';
        if ($person['actionTaken'] != '') $output .= '<b>'.__('Action Taken').'</b><br/>'.nl2br($person['actionTaken']).'<br/><br/>';
        if ($person['followUp'] != '') $output .= '<b>'.__("Follow Up by {name} at {date}", ['name' => Format::name('', $person['preferredNameFirstAider'], $person['surnameFirstAider']), 'date' => Format::dateTimeReadable($person['timestamp'])]).'</b><br/>'.nl2br($person['followUp']).'<br/><br/>';
        $resultLog = $firstAidGateway->queryFollowUpByFirstAidID($person['gibbonFirstAidID']);
        foreach ($resultLog AS $rowLog) {
            $output .= '<b>'.__("Follow Up by {name} at {date}", ['name' => Format::name('', $rowLog['preferredName'], $rowLog['surname']), 'date' => Format::dateTimeReadable($rowLog['timestamp'])]).'</b><br/>'.nl2br($rowLog['followUp']).'<br/><br/>';
        }

        return $output;
    });

    $table->addColumn('patient', __('Student'))
        ->description(__('Form Group'))
        ->sortable(['surnamePatient', 'preferredNamePatient'])
        ->format(function($person) use ($session) {
            $url = $session->get('absoluteURL').'/index.php?q=/modules/Students/student_view_details.php&gibbonPersonID='.$person['gibbonPersonIDPatient'].'&subpage=First Aid&search=&allStudents=&sort=surname,preferredName';
            return Format::link($url, Format::name('', $person['preferredNamePatient'], $person['surnamePatient'], 'Student', true))
                    .'<br/><small><i>'.$person['formGroup'].'</i></small>';
        });

    $table->addColumn('firstAider', __('First Aider'))
        ->sortable(['surnameFirstAider', 'preferredNameFirstAider'])
        ->format(Format::using('name', ['', 'preferredNameFirstAider', 'surnameFirstAider', 'Staff', false, true]));

    $table->addColumn('date', __('Date'))
        ->format(Format::using('date', ['date']));

    $table->addColumn('time', __('Time'))
        ->sortable(['timeIn', 'timeOut'])
        ->format(Format::using('timeRange', ['timeIn', 'timeOut']));

    $table->addActionColumn()
        ->addParam('gibbonPersonID', $gibbonPersonID)
        ->addParam('gibbonFormGroupID', $gibbonFormGroupID)
        ->addParam('gibbonYearGroupID', $gibbonYearGroupID)
        ->addParam('gibbonFirstAidID')
        ->format(function ($person, $actions) use ($highestAction) {
            if ($highestAction == 'First Aid Record_editAll') {
                $actions->addAction('edit', __('Edit'))
                    ->setURL('/modules/Students/firstAidRecord_edit.php');
            } elseif ($highestAction == 'First Aid Record_viewOnlyAddNotes') {
                $actions->addAction('view', __('View'))
                    ->setURL('/modules/Students/firstAidRecord_edit.php');
            }
        });

    echo $table->render($firstAidRecords);

}
