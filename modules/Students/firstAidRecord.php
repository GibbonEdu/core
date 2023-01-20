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
        echo "<div class='error'>";
        echo __('The highest grouped action cannot be determined.');
        echo '</div>';
    } else {
        $page->breadcrumbs->add(__('First Aid Records'));

        $gibbonPersonID = isset($_GET['gibbonPersonID'])? $_GET['gibbonPersonID'] : null;
        $gibbonFormGroupID = isset($_GET['gibbonFormGroupID'])? $_GET['gibbonFormGroupID'] : null;
        $gibbonYearGroupID = isset($_GET['gibbonYearGroupID'])? $_GET['gibbonYearGroupID'] : null;

        echo '<h3>';
        echo __('Filter');
        echo '</h3>';

        $form = Form::create('action', $session->get('absoluteURL').'/index.php', 'get');

        $form->setFactory(DatabaseFormFactory::create($pdo));
        $form->setClass('noIntBorder fullWidth');

        $form->addHiddenValue('q', "/modules/".$session->get('module')."/firstAidRecord.php");

        $row = $form->addRow();
            $row->addLabel('gibbonPersonID', __('Student'));
            $row->addSelectStudent('gibbonPersonID', $session->get('gibbonSchoolYearID'))->placeholder()->selected($gibbonPersonID);

        $row = $form->addRow();
            $row->addLabel('gibbonFormGroupID', __('Form Group'));
            $row->addSelectFormGroup('gibbonFormGroupID', $session->get('gibbonSchoolYearID'))->selected($gibbonFormGroupID);

        $row = $form->addRow();
            $row->addLabel('gibbonYearGroupID', __('Year Group'));
            $row->addSelectYearGroup('gibbonYearGroupID')->selected($gibbonYearGroupID);

        $row = $form->addRow();
            $row->addFooter();
            $row->addSearchSubmit($gibbon->session);

        echo $form->getOutput();

        echo '<h3>';
        echo __('First Aid Records');
        echo '</h3>';

        $firstAidGateway = $container->get(FirstAidGateway::class);

        $criteria = $firstAidGateway->newQueryCriteria(true)
            ->sortBy(['date', 'timeIn'], 'DESC')
            ->filterBy('student', $gibbonPersonID)
            ->filterBy('formGroup', $gibbonFormGroupID)
            ->filterBy('yearGroup', $gibbonYearGroupID)
            ->fromPOST();

        $firstAidRecords = $firstAidGateway->queryFirstAidBySchoolYear($criteria, $session->get('gibbonSchoolYearID'));

        // DATA TABLE
        $table = DataTable::createPaginated('firstAidRecords', $criteria);

        $table->addHeaderAction('add', __('Add'))
            ->setURL('/modules/Students/firstAidRecord_add.php')
            ->addParam('gibbonFormGroupID', $gibbonFormGroupID)
            ->addParam('gibbonYearGroupID', $gibbonYearGroupID)
            ->displayLabel();

        // COLUMNS
        $table->addExpandableColumn('details')->format(function($person) use ($firstAidGateway) {
            $output = '';
            if ($person['description'] != '') $output .= '<b>'.__('Description').'</b><br/>'.nl2br($person['description']).'<br/><br/>';
            if ($person['actionTaken'] != '') $output .= '<b>'.__('Action Taken').'</b><br/>'.nl2br($person['actionTaken']).'<br/><br/>';
            if ($person['followUp'] != '') $output .= '<b>'.__("Follow Up by {name} at {date}", ['name' => Format::name('', $person['preferredNameFirstAider'], $person['surnameFirstAider']), 'date' => Format::dateTimeReadable($person['timestamp'], '%H:%M, %b %d %Y')]).'</b><br/>'.nl2br($person['followUp']).'<br/><br/>';
            $resultLog = $firstAidGateway->queryFollowUpByFirstAidID($person['gibbonFirstAidID']);
            foreach ($resultLog AS $rowLog) {
                $output .= '<b>'.__("Follow Up by {name} at {date}", ['name' => Format::name('', $rowLog['preferredName'], $rowLog['surname']), 'date' => Format::dateTimeReadable($rowLog['timestamp'], '%H:%M, %b %d %Y')]).'</b><br/>'.nl2br($rowLog['followUp']).'<br/><br/>';
            }

            return $output;
        });

        $table->addColumn('patient', __('Student'))
            ->description(__('Form Group'))
            ->sortable(['surnamePatient', 'preferredNamePatient'])
            ->format(function($person) use ($session) {
                $url = $session->get('absoluteURL').'/index.php?q=/modules/Students/student_view_details.php&gibbonPersonID='.$person['gibbonPersonIDPatient'].'&subpage=Medical&search=&allStudents=&sort=surname,preferredName';
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
            ->format(function ($person, $actions) {
                $actions->addAction('edit', __('Edit'))
                    ->setURL('/modules/Students/firstAidRecord_edit.php');
            });

        echo $table->render($firstAidRecords);
    }
}
