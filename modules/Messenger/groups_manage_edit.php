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

if (isActionAccessible($guid, $connection2, '/modules/Messenger/groups_manage_edit.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    $gibbonGroupID = (isset($_GET['gibbonGroupID']))? $_GET['gibbonGroupID'] : null;

    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Messenger/groups_manage.php'>Manage Groups</a> > </div><div class='trailEnd'>Edit Group</div>";
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    //Check if school year specified
    $gibbonGroupID = $_GET['gibbonGroupID'];
    if ($gibbonGroupID == '') {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        try {
            $highestAction = getHighestGroupedAction($guid, '/modules/Messenger/groups_manage.php', $connection2);
            if ($highestAction == 'Manage Groups_all') {
                $data = array('gibbonGroupID' => $gibbonGroupID, 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                $sql = 'SELECT gibbonGroupID, name FROM gibbonGroup WHERE gibbonGroupID=:gibbonGroupID AND gibbonSchoolYearID=:gibbonSchoolYearID';
            }
            else {
                $data = array('gibbonGroupID' => $gibbonGroupID, 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                $sql = 'SELECT gibbonGroupID, name FROM gibbonGroup WHERE gibbonGroupID=:gibbonGroupID AND gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonIDOwner=:gibbonPersonID';
            }
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        if ($result->rowCount() != 1) {
            echo "<div class='error'>";
            echo __($guid, 'The specified record cannot be found.');
            echo '</div>';
        } else {
            //Let's go!
            $values = $result->fetch();
           
            $form = Form::create('groups', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/groups_manage_editProcess.php?gibbonGroupID=$gibbonGroupID");
			
			$form->addHiddenValue('address', $_SESSION[$guid]['address']);
			
            $row = $form->addRow();
                $row->addLabel('name', __('Name'));
                $row->addTextField('name')->isRequired()->setValue($values['name']);

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
                $row->addLabel('Members[]', __('Add Members'));
                $row->addSelect('Members[]')->fromArray($students)->selectMultiple();
            	
			$row = $form->addRow();
                $row->addFooter();
                $row->addSubmit();
                
            $form->loadAllValuesFrom($values);
				
            echo $form->getOutput();

            echo '<h2>';
            echo __($guid, 'Current Members');
            echo '</h2>';

            try {
                $data = array('gibbonGroupID' => $gibbonGroupID);
                $sql = "SELECT * FROM gibbonPerson JOIN gibbonGroupPerson ON (gibbonGroupPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonGroupID=:gibbonGroupID AND (status='Full' OR status='Expected') ORDER BY surname, preferredName";
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }

            if ($result->rowCount() < 1) {
                echo "<div class='error'>";
                echo __($guid, 'There are no records to display.');
                echo '</div>';
            } else {
                $form = Form::create('table', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/groups_manage_editProcess.php?gibbonGroupID=$gibbonGroupID");
                $form->addHiddenValue('gibbonGroupID', $gibbonGroupID);
                
                $linkParams = array(
                    'gibbonGroupID' => $gibbonGroupID
                );

                $table = $form->addRow()->addTable()->setClass('colorOddEven fullWidth');

                $header = $table->addHeaderRow();
                $header->addContent(__('Name'));
                $header->addContent(__('Email'));
                $header->addContent(__('Actions'));
               
                while ($student = $result->fetch()) {
                    $row = $table->addRow();
                    $name = formatName('', htmlPrep($student['preferredName']), htmlPrep($student['surname']), 'Student', true);
                    $row->addContent($name);
                    $row->addContent($student['email']);
                    $row->addWebLink('<img title="'.__('Delete').'" src="./themes/'.$_SESSION[$guid]['gibbonThemeName'].'/img/garbage.png"/>')
                        ->setURL($_SESSION[$guid]['absoluteURL'].'/fullscreen.php?q=/modules/'.$_SESSION[$guid]['module'].'/groups_manage_edit_delete.php&width=650&height=135')
                        ->setClass('thickbox')
                        ->addParam('gibbonPersonID', $student['gibbonPersonID'])
                        ->addParams($linkParams);
                }

                echo $form->getOutput();
            }
        }
    }
}
