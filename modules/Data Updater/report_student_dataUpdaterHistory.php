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
use Gibbon\Domain\DataUpdater\PersonUpdateGateway;

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Data Updater/report_student_dataUpdaterHistory.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Student Data Updater History').'</div>';
    echo '</div>';
    echo '<p>';
    echo __($guid, 'This report allows a user to select a range of students and check whether or not they have had their personal and medical data updated after a specified date.');
    echo '</p>';

    echo '<h2>';
    echo __($guid, 'Choose Students');
    echo '</h2>';

    $cutoffDate = getSettingByScope($connection2, 'Data Updater', 'cutoffDate');
    $cutoffDate = !empty($cutoffDate)? Format::date($cutoffDate) : Format::dateFromTimestamp(time() - (604800 * 26)); 

    $choices = isset($_POST['members'])? $_POST['members'] : array();
    $nonCompliant = isset($_POST['nonCompliant'])? $_POST['nonCompliant'] : '';
    $date = isset($_POST['date'])? $_POST['date'] : $cutoffDate;

    $form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/report_student_dataUpdaterHistory.php');
    $form->setFactory(DatabaseFormFactory::create($pdo));

    $form->addHiddenValue('address', $_SESSION[$guid]['address']);
    
    $row = $form->addRow();
        $row->addLabel('members', __('Students'));
        $row->addSelectStudent('members', $_SESSION[$guid]['gibbonSchoolYearID'], array('byRoll' => true, 'byName' => true))
            ->selectMultiple()
            ->isRequired()
            ->selected($choices);

    $row = $form->addRow();
        $row->addLabel('date', __('Date'))->description(__('Earliest acceptable update'));
        $row->addDate('date')->setValue($date)->isRequired();

    $row = $form->addRow();
        $row->addLabel('nonCompliant', __('Show Only Non-Compliant?'))->description(__('If not checked, show all. If checked, show only non-compliant students.'));
        $row->addCheckbox('nonCompliant')->setValue('Y')->checked($nonCompliant);
    
    $row = $form->addRow();
        $row->addSubmit();
    
    echo $form->getOutput();

    if (count($choices) > 0) {
        echo '<h2>';
        echo __($guid, 'Report Data');
        echo '</h2>';

        $gateway = $container->get(PersonUpdateGateway::class);

        // QUERY
        $criteria = $gateway->newQueryCriteria()
            ->sortBy(['gibbonPerson.surname', 'gibbonPerson.preferredName'])
            ->filterBy('cutoff', $nonCompliant == 'Y'? Format::dateConvert($date) : '')
            ->fromArray($_POST);

        $dataUpdates = $gateway->queryStudentUpdaterHistory($criteria, $_SESSION[$guid]['gibbonSchoolYearID'], $choices);
        
        // Join a set of parent emails per student
        $people = $dataUpdates->getColumn('gibbonPersonID');
        $parentEmails = $gateway->selectParentEmailsByPersonID($people)->fetchGrouped();
        $dataUpdates->joinColumn('gibbonPersonID', 'parentEmails', $parentEmails);

        // Function to display the updated date based on the cutoff date
        $dateCutoff = DateTime::createFromFormat('Y-m-d H:i:s', Format::dateConvert($date).' 00:00:00');
        $dataChecker = function($dateUpdated) use ($dateCutoff) {
            $dateDisplay = !empty($dateUpdated)? Format::dateTime($dateUpdated) : __('No data');
            $date = DateTime::createFromFormat('Y-m-d H:i:s', $dateUpdated);

            return empty($dateUpdated) || $dateCutoff > $date
                ? '<span style="color: #ff0000; font-weight: bold">'.$dateDisplay.'</span>'
                : $dateDisplay;
        };

        // DATA TABLE
        $table = DataTable::createPaginated('studentUpdaterHistory', $criteria);
        $table->addMetaData('post', ['members' => $choices]);

        $count = $dataUpdates->getPageFrom();
        $table->addColumn('count', '')
            ->notSortable()
            ->format(function ($row) use (&$count) {
                return $count++;
            });

        $table->addColumn('student', __('Student'))
            ->sortable(['gibbonPerson.surname', 'gibbonPerson.preferredName'])
            ->format(function ($row) use ($guid) {
                $name = Format::name('', $row['preferredName'], $row['surname'], 'Student', true);
                return Format::link($_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Students/student_view_details.php&gibbonPersonID='.$row['gibbonPersonID'], $name);
            });

        $table->addColumn('rollGroupName', __('Roll Group'));

        $table->addColumn('personalUpdate', __('Personal Data'))
            ->format(function($row) use ($dataChecker) {
                return $dataChecker($row['personalUpdate']);
            });

        $table->addColumn('medicalUpdate', __('Medical Data'))
            ->format(function($row) use ($dataChecker) {
                return $dataChecker($row['medicalUpdate']);
            });

        $table->addColumn('parentEmails', __('Parent Emails'))
            ->notSortable()
            ->format(function ($row) {
                return is_array($row['parentEmails'])? implode('<br/>', array_column($row['parentEmails'], 'email')) : '';
            });

        echo $table->render($dataUpdates);
    }
}
