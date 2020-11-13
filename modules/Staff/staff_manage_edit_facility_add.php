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
use Gibbon\Services\Format;

if (isActionAccessible($guid, $connection2, '/modules/Staff/staff_manage_edit_facility_add.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $gibbonPersonID = $_GET['gibbonPersonID'] ?? '';
    $gibbonStaffID = $_GET['gibbonStaffID'] ?? '';
    $gibbonSpacePersonID = $_GET['gibbonSpacePersonID'] ?? '';
    $search = $_GET['search'] ?? '';

    $page->breadcrumbs
        ->add(__('Manage Staff'), 'staff_manage.php')
        ->add(__('Edit Staff'), 'staff_manage_edit.php', ['gibbonStaffID' => $gibbonStaffID, 'gibbonSpacePersonID' => $gibbonSpacePersonID])
        ->add(__('Add Facility'));

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    //Check if school year specified
    if ($gibbonStaffID == '' or $gibbonPersonID == '') {
        $page->addError(__('You have not specified one or more required parameters.'));
    } else {
        
            $data = array('gibbonStaffID' => $gibbonStaffID, 'gibbonPersonID' => $gibbonPersonID);
            $sql = 'SELECT gibbonStaff.*, preferredName, surname FROM gibbonStaff JOIN gibbonPerson ON (gibbonStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonStaffID=:gibbonStaffID AND gibbonPerson.gibbonPersonID=:gibbonPersonID';
            $result = $connection2->prepare($sql);
            $result->execute($data);

        if ($result->rowCount() != 1) {
            $page->addError(__('The specified record cannot be found.'));
        } else {
            $values = $result->fetch();

            if ($search != '') {
                echo "<div class='linkTop'>";
                echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Staff/staff_manage_edit.php&gibbonStaffID=$gibbonStaffID&search=$search'>".__('Back to Search Results').'</a>';
                echo '</div>';
            }

            $form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/staff_manage_edit_facility_addProcess.php?gibbonPersonID=$gibbonPersonID&gibbonStaffID=$gibbonStaffID&search=$search");
            $form->setFactory(DatabaseFormFactory::create($pdo));
            
            $form->addHiddenValue('address', $_SESSION[$guid]['address']);

            $row = $form->addRow();
                $row->addLabel('person', __('Person'));
                $row->addTextField('person')->setValue(Format::name('', $values['preferredName'], $values['surname'], 'Student'))->readonly()->required();

            $data = array('gibbonPersonID' => $gibbonPersonID);
            $sql = "SELECT gibbonSpace.gibbonSpaceID AS value, name
                FROM gibbonSpace
                    LEFT JOIN gibbonSpacePerson ON (gibbonSpacePerson.gibbonSpaceID=gibbonSpace.gibbonSpaceID AND (gibbonSpacePersonID IS NULL OR gibbonSpacePerson.gibbonPersonID=:gibbonPersonID))
                    WHERE gibbonSpacePerson.gibbonPersonID IS NULL
                ORDER BY gibbonSpace.name";
            $row = $form->addRow();
                $row->addLabel('gibbonSpaceID', __('Facility'));
                $row->addSelect('gibbonSpaceID')->fromQuery($pdo, $sql, $data)->placeholder()->required();

            $row = $form->addRow();
                $row->addLabel('usageType', __('Usage Type'));
                $row->addSelect('usageType')->fromArray(array('Teaching' => __('Teaching'), 'Office' => __('Office'), 'Other' => __('Other')))->placeholder();

            $row = $form->addRow();
                $row->addFooter();
                $row->addSubmit();

            echo $form->getOutput();
        }
    }
}
