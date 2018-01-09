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

@session_start();

if (isActionAccessible($guid, $connection2, '/modules/User Admin/family_manage_edit_editAdult.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/User Admin/family_manage.php'>".__($guid, 'Manage Families')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/User Admin/family_manage_edit.php&gibbonFamilyID='.$_GET['gibbonFamilyID']."'>".__($guid, 'Edit Family')."</a> > </div><div class='trailEnd'>".__($guid, 'Edit Adult').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    //Check if school year specified
    $gibbonFamilyID = $_GET['gibbonFamilyID'];
    $gibbonPersonID = $_GET['gibbonPersonID'];
    $search = $_GET['search'];
    if ($gibbonPersonID == '' or $gibbonFamilyID == '') {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        try {
            $data = array('gibbonFamilyID' => $gibbonFamilyID, 'gibbonPersonID' => $gibbonPersonID);
            $sql = "SELECT * FROM gibbonPerson, gibbonFamily, gibbonFamilyAdult WHERE gibbonFamily.gibbonFamilyID=gibbonFamilyAdult.gibbonFamilyID AND gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID AND gibbonFamily.gibbonFamilyID=:gibbonFamilyID AND gibbonFamilyAdult.gibbonPersonID=:gibbonPersonID AND (gibbonPerson.status='Full' OR gibbonPerson.status='Expected')";
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

            if ($search != '') {
                echo "<div class='linkTop'>";
                echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/User Admin/family_manage_edit.php&gibbonFamilyID=$gibbonFamilyID&search=$search'>".__($guid, 'Back').'</a>';
                echo '</div>';
            }

            $form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/family_manage_edit_editAdultProcess.php?gibbonPersonID=$gibbonPersonID&gibbonFamilyID=$gibbonFamilyID&search=$search");

            $form->setClass('smallIntBorder fullWidth');

            $form->addHiddenValue('address', $_SESSION[$guid]['address']);

            $form->addRow()->addHeading(__('Add Adult'));

            $row = $form->addRow();
                $row->addLabel('adult', __('Adult\'s Name'));
                $row->addTextField('adult')->setValue(formatName(htmlPrep($values['title']), htmlPrep($values['preferredName']), htmlPrep($values['surname']), 'Parent'))->isRequired()->readonly();

            $row = $form->addRow();
                $row->addLabel('comment', __('Comment'))->description(__('Data displayed in full Student Profile'));
                $row->addTextArea('comment')->setRows(8);

            $row = $form->addRow();
                $row->addLabel('childDataAccess', __('Data Access?'))->description(__('Access data on family\'s children?'));
                $row->addYesNo('childDataAccess')->isRequired();

            $priorities = array(
                '1' => __('1'),
                '2' => __('2'),
                '3' => __('3')
            );
            $row = $form->addRow();
                $row->addLabel('contactPriority', __('Contact Priority'))->description(__('The order in which school should contact family members.'));
                $row->addSelect('contactPriority')->fromArray($priorities)->isRequired();

            $row = $form->addRow()->addClass('contact');
                $row->addLabel('contactCall', __('Call?'))->description(__('Receive non-emergency phone calls from school?'));
                $row->addYesNo('contactCall')->isRequired();

            $row = $form->addRow()->addClass('contact');
                $row->addLabel('contactSMS', __('SMS?'))->description(__('Receive non-emergency SMS messages from school?'));
                $row->addYesNo('contactSMS')->isRequired();

            $row = $form->addRow()->addClass('contact');
                $row->addLabel('contactEmail', __('Email?'))->description(__('Receive non-emergency emails from school?'));
                $row->addYesNo('contactEmail')->isRequired();

            $row = $form->addRow()->addClass('contact');
                $row->addLabel('contactMail', __('Mail?'))->description(__('Receive postage mail from school?'));
                $row->addYesNo('contactMail')->isRequired();

            $row = $form->addRow();
                $row->addFooter();
                $row->addSubmit();

            $form->loadAllValuesFrom($values);

            echo $form->getOutput();

            echo "<script type=\"text/javascript\">
                $(document).ready(function(){
                    $(\"#contactCall\").attr(\"disabled\", \"disabled\");
                    $(\"#contactSMS\").attr(\"disabled\", \"disabled\");
                    $(\"#contactEmail\").attr(\"disabled\", \"disabled\");
                    $(\"#contactMail\").attr(\"disabled\", \"disabled\");
                    $(\"#contactPriority\").change(function(){
                        if ($('#contactPriority').val()==\"1\" ) {
                            $(\"#contactCall\").attr(\"disabled\", \"disabled\");
                            $(\"#contactCall\").val(\"Y\");
                            $(\"#contactSMS\").attr(\"disabled\", \"disabled\");
                            $(\"#contactSMS\").val(\"Y\");
                            $(\"#contactEmail\").attr(\"disabled\", \"disabled\");
                            $(\"#contactEmail\").val(\"Y\");
                            $(\"#contactMail\").attr(\"disabled\", \"disabled\");
                            $(\"#contactMail\").val(\"Y\");
                        }
                        else {
                            $(\"#contactCall\").removeAttr(\"disabled\");
                            $(\"#contactSMS\").removeAttr(\"disabled\");
                            $(\"#contactEmail\").removeAttr(\"disabled\");
                            $(\"#contactMail\").removeAttr(\"disabled\");
                        }
                    });
                    $(\"#contactPriority\").change();
                });
            </script>";

        }
    }
}
?>
