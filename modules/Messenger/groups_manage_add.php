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

if (isActionAccessible($guid, $connection2, '/modules/Messenger/groups_manage_add.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Messenger/groups_manage.php'>".__($guid, 'Manage Groups')."</a> > </div><div class='trailEnd'>".__($guid, 'Add Group').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }
    
    $form = Form::create('groups', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/groups_manage_addProcess.php");
    
    $form->addHiddenValue('address', $_SESSION[$guid]['address']);

    $row = $form->addRow();
        $row->addLabel('name', __('Name'));
        $row->addTextField('name')->isRequired()->setValue();

    $students = array();
    $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'date' => date('Y-m-d'));
    $sql = "SELECT gibbonPerson.gibbonPersonID, preferredName, surname, gibbonRollGroup.name AS rollGroupName 
            FROM gibbonPerson
            JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) 
            JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID)
            JOIN gibbonYearGroup ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID)
            WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID
            AND gibbonPerson.status='FULL' 
            AND (dateStart IS NULL OR dateStart<=:date) AND (dateEnd IS NULL  OR dateEnd>=:date) 
            ORDER BY rollGroupName, gibbonPerson.surname, gibbonPerson.preferredName";
    $result = $pdo->executeQuery($data, $sql);

    if ($result->rowCount() > 0) {
        $students[__('Enrolable Students')] = array_reduce($result->fetchAll(), function($group, $item) {
            $group[$item['gibbonPersonID']] = $item['rollGroupName'].' - '.formatName('', $item['preferredName'], $item['surname'], 'Student', true);
            return $group;
        }, array());
    }

    $sql = "SELECT gibbonPersonID, surname, preferredName, status, username FROM gibbonPerson WHERE status='Full' OR status='Expected' ORDER BY surname, preferredName";
    $result = $pdo->executeQuery(array(), $sql);

    if ($result->rowCount() > 0) {
        $students[__('All Users')] = array_reduce($result->fetchAll(), function ($group, $item) {
            $group[$item['gibbonPersonID']] = formatName('', $item['preferredName'], $item['surname'], 'Student', true).' ('.$item['username'].')';
            return $group;
        }, array());
    }

    $row = $form->addRow();
        $row->addLabel('Members[]', __('Members'));
        $row->addSelect('Members[]')->fromArray($students)->selectMultiple()->isRequired();

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();
        
    echo $form->getOutput();
}
