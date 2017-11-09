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

@session_start();

if (isActionAccessible($guid, $connection2, '/modules/User Admin/family_manage_edit.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/User Admin/family_manage.php'>".__($guid, 'Manage Families')."</a> > </div><div class='trailEnd'>".__($guid, 'Edit Family').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    //Check if school year specified
    $gibbonFamilyID = $_GET['gibbonFamilyID'];
    $search = null;
    if (isset($_GET['search'])) {
        $search = $_GET['search'];
    }
    if ($gibbonFamilyID == '') {
        echo '<h1>';
        echo __($guid, 'Edit Family');
        echo '</h1>';
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        try {
            $data = array('gibbonFamilyID' => $gibbonFamilyID);
            $sql = 'SELECT * FROM gibbonFamily WHERE gibbonFamilyID=:gibbonFamilyID';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        if ($result->rowCount() != 1) {
            echo '<h1>';
            echo 'Edit Family';
            echo '</h1>';
            echo "<div class='error'>";
            echo __($guid, 'The specified record cannot be found.');
            echo '</div>';
        } else {
            //Let's go!
            $values = $result->fetch();

            if ($search != '') {
                echo "<div class='linkTop'>";
                echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/User Admin/family_manage.php&search=$search'>".__($guid, 'Back to Search Results').'</a>';
                echo '</div>';
            }

            $form = Form::create('action1', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/family_manage_editProcess.php?gibbonFamilyID=$gibbonFamilyID&search=$search");

            $form->setFactory(DatabaseFormFactory::create($pdo));
            $form->setClass('smallIntBorder fullWidth');

            $form->addHiddenValue('address', $_SESSION[$guid]['address']);

            $form->addRow()->addHeading(__('General Information'));

            $row = $form->addRow();
                $row->addLabel('name', __('Family Name'));
                $row->addTextField('name')->maxLength(100)->isRequired();

            $row = $form->addRow();
        		$row->addLabel('status', __('Marital Status'));
        		$row->addSelectMaritalStatus('status')->isRequired();

            $row = $form->addRow();
                $row->addLabel('languageHomePrimary', __('Home Language - Primary'));
                $row->addSelectLanguage('languageHomePrimary');

            $row = $form->addRow();
                $row->addLabel('languageHomeSecondary', __('Home Language - Secondary'));
                $row->addSelectLanguage('languageHomeSecondary');

            $row = $form->addRow();
                $row->addLabel('nameAddress', __('Address Name'))->description(__('Formal name to address parents with.'));
                $row->addTextField('nameAddress')->maxLength(100)->isRequired();

            $row = $form->addRow();
                $row->addLabel('homeAddress', __('Home Address'))->description(__('Unit, Building, Street'));
                $row->addTextField('homeAddress')->maxLength(255);

            $row = $form->addRow();
                $row->addLabel('homeAddressDistrict', __('Home Address (District)'))->description(__('County, State, District'));
                $row->addTextFieldDistrict('homeAddressDistrict');

            $row = $form->addRow();
                $row->addLabel('homeAddressCountry', __('Home Address (Country)'));
                $row->addSelectCountry('homeAddressCountry');

            $row = $form->addRow();
                $row->addFooter();
                $row->addSubmit();

            $form->loadAllValuesFrom($values);

            echo $form->getOutput();


            //Get children and prep array
            try {
                $dataChildren = array('gibbonFamilyID' => $gibbonFamilyID);
                $sqlChildren = 'SELECT * FROM gibbonFamilyChild JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonFamilyID=:gibbonFamilyID ORDER BY surname, preferredName';
                $resultChildren = $connection2->prepare($sqlChildren);
                $resultChildren->execute($dataChildren);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }
            $children = array();
            $count = 0;
            while ($rowChildren = $resultChildren->fetch()) {
                $children[$count]['image_240'] = $rowChildren['image_240'];
                $children[$count]['gibbonPersonID'] = $rowChildren['gibbonPersonID'];
                $children[$count]['preferredName'] = $rowChildren['preferredName'];
                $children[$count]['surname'] = $rowChildren['surname'];
                $children[$count]['status'] = $rowChildren['status'];
                $children[$count]['comment'] = $rowChildren['comment'];
                ++$count;
            }
            //Get adults and prep array
            try {
                $dataAdults = array('gibbonFamilyID' => $gibbonFamilyID);
                $sqlAdults = 'SELECT * FROM gibbonFamilyAdult, gibbonPerson WHERE (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) AND gibbonFamilyID=:gibbonFamilyID ORDER BY contactPriority, surname, preferredName';
                $resultAdults = $connection2->prepare($sqlAdults);
                $resultAdults->execute($dataAdults);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }
            $adults = array();
            $count = 0;
            while ($rowAdults = $resultAdults->fetch()) {
                $adults[$count]['image_240'] = $rowAdults['image_240'];
                $adults[$count]['gibbonPersonID'] = $rowAdults['gibbonPersonID'];
                $adults[$count]['title'] = $rowAdults['title'];
                $adults[$count]['preferredName'] = $rowAdults['preferredName'];
                $adults[$count]['surname'] = $rowAdults['surname'];
                $adults[$count]['status'] = $rowAdults['status'];
                $adults[$count]['comment'] = $rowAdults['comment'];
                $adults[$count]['childDataAccess'] = $rowAdults['childDataAccess'];
                $adults[$count]['contactPriority'] = $rowAdults['contactPriority'];
                $adults[$count]['contactCall'] = $rowAdults['contactCall'];
                $adults[$count]['contactSMS'] = $rowAdults['contactSMS'];
                $adults[$count]['contactEmail'] = $rowAdults['contactEmail'];
                $adults[$count]['contactMail'] = $rowAdults['contactMail'];
                ++$count;
            }

            //Get relationships and prep array
            try {
                $dataRelationships = array('gibbonFamilyID' => $gibbonFamilyID);
                $sqlRelationships = 'SELECT * FROM gibbonFamilyRelationship WHERE gibbonFamilyID=:gibbonFamilyID';
                $resultRelationships = $connection2->prepare($sqlRelationships);
                $resultRelationships->execute($dataRelationships);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }
            $relationships = array();
            $count = 0;
            while ($rowRelationships = $resultRelationships->fetch()) {
                $relationships[$rowRelationships['gibbonPersonID1']][$rowRelationships['gibbonPersonID2']] = $rowRelationships['relationship'];
                ++$count;
            }

            echo '<h3>';
            echo __($guid, 'Relationships');
            echo '</h3>';
            echo '<p>';
            echo __($guid, 'Use the table below to show how each child is related to each adult in the family.');
            echo '</p>';
            if ($resultChildren->rowCount() < 1 or $resultAdults->rowCount() < 1) {
                echo "<div class='error'>".__($guid, 'There are not enough people in this family to form relationships.').'</div>';
            } else {

                $form = Form::create('action2', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/family_manage_edit_relationshipsProcess.php?gibbonFamilyID=$gibbonFamilyID&search=$search");

                $form->setFactory(DatabaseFormFactory::create($pdo));
                $form->setClass('colorOddEven fullWidth');

                $form->addHiddenValue('address', $_SESSION[$guid]['address']);

                $row = $form->addRow()->addClass('head break');
                    $row->addContent(__('Adults'));
                    foreach ($children as $child) {
                        $row->addContent(formatName('', $child['preferredName'], $child['surname'], 'Student'));
                    }

                $count = 0;
                foreach ($adults as $adult) {
                    ++$count;
                    $row = $form->addRow();
                        $row->addContent(formatName($adult['title'], $adult['preferredName'], $adult['surname'], 'Parent'));
                        foreach ($children as $child) {
                            $form->addHiddenValue('gibbonPersonID1[]', $adult['gibbonPersonID']);
                            $form->addHiddenValue('gibbonPersonID2[]', $child['gibbonPersonID']);
                            $relationshipSet = (isset($relationships[$adult['gibbonPersonID']][$child['gibbonPersonID']]) ? $relationships[$adult['gibbonPersonID']][$child['gibbonPersonID']] : null);
                            $row->addSelectRelationship('relationships['.$adult['gibbonPersonID'].']['.$child['gibbonPersonID'].']')->setClass('smallWidth floatNone')->selected($relationshipSet);
                        }
                }

                $row = $form->addRow();
                    $row->addSubmit();

                $form->loadAllValuesFrom($values);

                echo $form->getOutput();
            }

            echo '<h3>';
            echo __($guid, 'View Children');
            echo '</h3>';

            if ($resultChildren->rowCount() < 1) {
                echo "<div class='error'>";
                echo __($guid, 'There are no records to display.');
                echo '</div>';
            } else {
                echo "<table cellspacing='0' style='width: 100%'>";
                echo "<tr class='head'>";
                echo '<th>';
                echo __($guid, 'Photo');
                echo '</th>';
                echo '<th>';
                echo __($guid, 'Name');
                echo '</th>';
                echo '<th>';
                echo __($guid, 'Status');
                echo '</th>';
                echo '<th>';
                echo __($guid, 'Roll Group');
                echo '</th>';
                echo '<th>';
                echo __($guid, 'Comment');
                echo '</th>';
                echo '<th>';
                echo __($guid, 'Actions');
                echo '</th>';
                echo '</tr>';

                $count = 0;
                $rowNum = 'odd';
                foreach ($children as $child) {
                    if ($count % 2 == 0) {
                        $rowNum = 'even';
                    } else {
                        $rowNum = 'odd';
                    }
                    ++$count;

                    //COLOR ROW BY STATUS!
                    echo "<tr class=$rowNum>";
                    echo '<td>';
                    echo getUserPhoto($guid, $child['image_240'], 75);
                    echo '</td>';
                    echo '<td>';
                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/User Admin/user_manage_edit.php&gibbonPersonID='.$child['gibbonPersonID']."'>".formatName('', $child['preferredName'], $child['surname'], 'Student').'</a>';
                    echo '</td>';
                    echo '<td>';
                    echo $child['status'];
                    echo '</td>';
                    echo '<td>';
                    try {
                        $dataDetail = array('gibbonPersonID' => $child['gibbonPersonID'], 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                        $sqlDetail = 'SELECT * FROM gibbonRollGroup JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE gibbonPersonID=:gibbonPersonID AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID';
                        $resultDetail = $connection2->prepare($sqlDetail);
                        $resultDetail->execute($dataDetail);
                    } catch (PDOException $e) {
                        echo "<div class='error'>".$e->getMessage().'</div>';
                    }
                    if ($resultDetail->rowCount() == 1) {
                        $rowDetail = $resultDetail->fetch();
                        echo $rowDetail['name'];
                    }
                    echo '</td>';
                    echo '<td>';
                    echo nl2brr($child['comment']);
                    echo '</td>';
                    echo '<td>';
                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/family_manage_edit_editChild.php&gibbonFamilyID=$gibbonFamilyID&gibbonPersonID=".$child['gibbonPersonID']."&search=$search'><img title='".__($guid, 'Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/family_manage_edit_deleteChild.php&gibbonFamilyID=$gibbonFamilyID&gibbonPersonID=".$child['gibbonPersonID']."&search=$search'><img title='".__($guid, 'Delete')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/garbage.png'/></a>";
                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/user_manage_password.php&gibbonPersonID='.$child['gibbonPersonID']."&search=$search'><img title='".__($guid, 'Change Password')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/key.png'/></a>";
                    echo '</td>';
                    echo '</tr>';
                }
                echo '</table>';
            }

            $form = Form::create('action3', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/family_manage_edit_addChildProcess.php?gibbonFamilyID=$gibbonFamilyID&search=$search");

            $form->setFactory(DatabaseFormFactory::create($pdo));
            $form->setClass('smallIntBorder fullWidth');

            $form->addHiddenValue('address', $_SESSION[$guid]['address']);

            $form->addRow()->addHeading(__('Add Child'));

            $row = $form->addRow();
                $row->addLabel('gibbonPersonID', __('Child\'s Name'));
                $row->addSelectStudent('gibbonPersonID', $_SESSION[$guid]['gibbonSchoolYearID'], array('byName' => true, 'byRoll' => true, 'showRoll' => true))->placeholder()->isRequired();

            $row = $form->addRow();
                $row->addLabel('comment', __('Comment'));
                $row->addTextArea('comment')->setRows(8);

            $row = $form->addRow();
                $row->addFooter();
                $row->addSubmit();

            echo $form->getOutput();

            echo '<h3>';
            echo __($guid, 'View Adults');
            echo '</h3>';
            echo "<div class='warning'>";
            echo __($guid, 'Logic exists to try and ensure that there is always one and only one parent with Contact Priority set to 1. This may result in values being set which are not exactly what you chose.');
            echo '</div>';

            if ($resultAdults->rowCount() < 1) {
                echo "<div class='error'>";
                echo __($guid, 'There are no records to display.');
                echo '</div>';
            } else {
                echo "<table cellspacing='0' style='width: 100%'>";
                echo "<tr class='head'>";
                echo '<th>';
                echo __($guid, 'Name');
                echo '</th>';
                echo '<th>';
                echo __($guid, 'Status');
                echo '</th>';
                echo '<th>';
                echo __($guid, 'Comment');
                echo '</th>';
                echo "<th style='max-width: 50px; padding-left: 1px; padding-right: 1px; height: 100px'>";
                echo "<div style='-webkit-transform: rotate(-90deg); -moz-transform: rotate(-90deg); -ms-transform: rotate(-90deg); -o-transform: rotate(-90deg); transform: rotate(-90deg);'>".__($guid, 'Data Access').'</div>';
                echo '</th>';
                echo "<th style='max-width: 50px; padding-left: 1px; padding-right: 1px'>";
                echo "<div style='-webkit-transform: rotate(-90deg); -moz-transform: rotate(-90deg); -ms-transform: rotate(-90deg); -o-transform: rotate(-90deg); transform: rotate(-90deg);'>".__($guid, 'Contact Priority').'</div>';
                echo '</th>';
                echo "<th style='max-width: 50px; padding-left: 1px; padding-right: 1px'>";
                echo "<div style='-webkit-transform: rotate(-90deg); -moz-transform: rotate(-90deg); -ms-transform: rotate(-90deg); -o-transform: rotate(-90deg); transform: rotate(-90deg);'>".__($guid, 'Contact By Phone').'</div>';
                echo '</th>';
                echo "<th style='max-width: 50px; padding-left: 1px; padding-right: 1px'>";
                echo "<div style='-webkit-transform: rotate(-90deg); -moz-transform: rotate(-90deg); -ms-transform: rotate(-90deg); -o-transform: rotate(-90deg); transform: rotate(-90deg);'>".__($guid, 'Contact By SMS').'</div>';
                echo '</th>';
                echo "<th style='max-width: 50px; padding-left: 1px; padding-right: 1px'>";
                echo "<div style='-webkit-transform: rotate(-90deg); -moz-transform: rotate(-90deg); -ms-transform: rotate(-90deg); -o-transform: rotate(-90deg); transform: rotate(-90deg);'>".__($guid, 'Contact By Email').'</div>';
                echo '</th>';
                echo "<th style='max-width: 50px; padding-left: 1px; padding-right: 1px'>";
                echo "<div style='-webkit-transform: rotate(-90deg); -moz-transform: rotate(-90deg); -ms-transform: rotate(-90deg); -o-transform: rotate(-90deg); transform: rotate(-90deg);'>".__($guid, 'Contact By Mail').'</div>';
                echo '</th>';
                echo '<th>';
                echo __($guid, 'Actions');
                echo '</th>';
                echo '</tr>';

                $count = 0;
                $rowNum = 'odd';
                foreach ($adults as $adult) {
                    if ($count % 2 == 0) {
                        $rowNum = 'even';
                    } else {
                        $rowNum = 'odd';
                    }
                    ++$count;

                    //COLOR ROW BY STATUS!
                    echo "<tr class=$rowNum>";
                    echo '<td>';
                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/User Admin/user_manage_edit.php&gibbonPersonID='.$adult['gibbonPersonID']."'>".formatName($adult['title'], $adult['preferredName'], $adult['surname'], 'Parent').'</a>';
                    echo '</td>';
                    echo '<td>';
                    echo $adult['status'];
                    echo '</td>';
                    echo '<td>';
                    echo nl2brr($adult['comment']);
                    echo '</td>';
                    echo "<td style='padding-left: 1px; padding-right: 1px'>";
                    echo $adult['childDataAccess'];
                    echo '</td>';
                    echo "<td style='padding-left: 1px; padding-right: 1px'>";
                    echo $adult['contactPriority'];
                    echo '</td>';
                    echo "<td style='padding-left: 1px; padding-right: 1px'>";
                    echo $adult['contactCall'];
                    echo '</td>';
                    echo "<td style='padding-left: 1px; padding-right: 1px'>";
                    echo $adult['contactSMS'];
                    echo '</td>';
                    echo "<td style='padding-left: 1px; padding-right: 1px'>";
                    echo $adult['contactEmail'];
                    echo '</td>';
                    echo "<td style='padding-left: 1px; padding-right: 1px'>";
                    echo $adult['contactMail'];
                    echo '</td>';
                    echo '<td>';
                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/family_manage_edit_editAdult.php&gibbonFamilyID=$gibbonFamilyID&gibbonPersonID=".$adult['gibbonPersonID']."&search=$search'><img title='".__($guid, 'Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/family_manage_edit_deleteAdult.php&gibbonFamilyID=$gibbonFamilyID&gibbonPersonID=".$adult['gibbonPersonID']."&search=$search'><img title='".__($guid, 'Delete')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/garbage.png'/></a>";
                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/user_manage_password.php&gibbonPersonID='.$adult['gibbonPersonID']."&search=$search'><img title='".__($guid, 'Change Password')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/key.png'/></a>";
                    echo '</td>';
                    echo '</tr>';
                }
                echo '</table>';
            }

            $form = Form::create('action4', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/family_manage_edit_addAdultProcess.php?gibbonFamilyID=$gibbonFamilyID&search=$search");

            $form->setFactory(DatabaseFormFactory::create($pdo));
            $form->setClass('smallIntBorder fullWidth');

            $form->addHiddenValue('address', $_SESSION[$guid]['address']);

            $form->addRow()->addHeading(__('Add Adult'));

            $adults = array();
            try {
                $dataSelect = array();
                $sqlSelect = "SELECT status, gibbonPersonID, preferredName, surname, username FROM gibbonPerson WHERE status='Full' OR status='Expected' ORDER BY surname, preferredName";
                $resultSelect = $connection2->prepare($sqlSelect);
                $resultSelect->execute($dataSelect);
            } catch (PDOException $e) { }
            while ($rowSelect = $resultSelect->fetch()) {
                $expected = (($rowSelect['status'] == 'Expected') ? ' ('.__('Expected').')' : '');
                $adults[$rowSelect['gibbonPersonID']] = formatName('', htmlPrep($rowSelect['preferredName']), htmlPrep($rowSelect['surname']), 'Parent', true, true).' ('.$rowSelect['username'].')'.$expected;
            }
            $row = $form->addRow();
                $row->addLabel('gibbonPersonID2', __('Adult\'s Name'));
                $row->addSelect('gibbonPersonID2')->fromArray($adults)->placeHolder()->isRequired();

            $row = $form->addRow();
                $row->addLabel('comment2', __('Comment'))->description(__('Data displayed in full Student Profile'));
                $row->addTextArea('comment2')->setRows(8);

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
                });
            </script>";
        }
    }
}
?>
