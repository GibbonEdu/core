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

if (isActionAccessible($guid, $connection2, '/modules/User Admin/family_manage_edit_editChild.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $urlParams = ['gibbonFamilyID' => $_GET['gibbonFamilyID']];
    
    $page->breadcrumbs
        ->add(__('Manage Families'), 'family_manage.php')
        ->add(__('Edit Family'), 'family_manage_edit.php', $urlParams)
        ->add(__('Edit Child'));  

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    //Check if school year specified
    $gibbonFamilyID = $_GET['gibbonFamilyID'];
    $gibbonPersonID = $_GET['gibbonPersonID'];
    $search = $_GET['search'];
    if ($gibbonPersonID == '' or $gibbonFamilyID == '') {
        $page->addError(__('You have not specified one or more required parameters.'));
    } else {
        
            $data = array('gibbonFamilyID' => $gibbonFamilyID, 'gibbonPersonID' => $gibbonPersonID);
            $sql = "SELECT * FROM gibbonPerson, gibbonFamily, gibbonFamilyChild WHERE gibbonFamily.gibbonFamilyID=gibbonFamilyChild.gibbonFamilyID AND gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID AND gibbonFamily.gibbonFamilyID=:gibbonFamilyID AND gibbonFamilyChild.gibbonPersonID=:gibbonPersonID AND (gibbonPerson.status='Full' OR gibbonPerson.status='Expected')";
            $result = $connection2->prepare($sql);
            $result->execute($data);

        if ($result->rowCount() != 1) {
            $page->addError(__('The specified record cannot be found.'));
        } else {
            //Let's go!
            $values = $result->fetch();

            if ($search != '') {
                echo "<div class='linkTop'>";
                echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/User Admin/family_manage_edit.php&gibbonFamilyID=$gibbonFamilyID&search=$search'>".__('Back').'</a>';
                echo '</div>';
            }

            $form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/family_manage_edit_editChildProcess.php?gibbonPersonID=$gibbonPersonID&gibbonFamilyID=$gibbonFamilyID&search=$search");

            $form->addHiddenValue('address', $_SESSION[$guid]['address']);

            $form->addRow()->addHeading(__('Edit Child'));

            $row = $form->addRow();
                $row->addLabel('child', __('Childs\'s Name'));
                $row->addTextField('child')->setValue(Format::name(htmlPrep($values['title']), htmlPrep($values['preferredName']), htmlPrep($values['surname']), 'Parent'))->required()->readonly();

            $row = $form->addRow();
                $row->addLabel('comment', __('Comment'))->description(__('Data displayed in full Student Profile'));
                $row->addTextArea('comment')->setRows(8);

            $row = $form->addRow();
                $row->addFooter();
                $row->addSubmit();

            $form->loadAllValuesFrom($values);

            echo $form->getOutput();
        }
    }
}
?>
