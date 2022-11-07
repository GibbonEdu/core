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

use Gibbon\Services\Module\Resource;
use Gibbon\Forms\Form;
use Gibbon\Forms\DatabaseFormFactory;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, Resource::fromRoute('Data Updater', 'data_family')) == false) {
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
        //Proceed!
        $page->breadcrumbs->add(__('Update Family Data'));

        if ($highestAction == 'Update Personal Data_any') {
            echo '<p>';
            echo __('This page allows a user to request selected family data updates for any family.');
            echo '</p>';
        } else {
            echo '<p>';
            echo __('This page allows any adult with data access permission to request selected family data updates for their family.');
            echo '</p>';
        }

        $customResponces = array();
        $error3 = __('Your request was successful, but some data was not properly saved. An administrator will process your request as soon as possible. You will not see the updated data in the system until it has been processed.');
        if ($gibbon->session->get('organisationDBAEmail') != '' and $gibbon->session->get('organisationDBAName') != '') {
            $error3 .= ' '.sprintf(__('Please contact %1$s if you have any questions.'), "<a href='mailto:".$gibbon->session->get('organisationDBAEmail')."'>".$gibbon->session->get('organisationDBAName').'</a>');
        }
        $customResponces['error3'] = $error3;

        $success0 = __('Your request was completed successfully. An administrator will process your request as soon as possible. You will not see the updated data in the system until it has been processed.');
        if ($gibbon->session->get('organisationDBAEmail') != '' and $gibbon->session->get('organisationDBAName') != '') {
            $success0 .= ' '.sprintf(__('Please contact %1$s if you have any questions.'), "<a href='mailto:".$gibbon->session->get('organisationDBAEmail')."'>".$gibbon->session->get('organisationDBAName').'</a>');
        }
        $customResponces['success0'] = $success0;

        $page->return->addReturns($customResponces);

        echo '<h2>';
        echo __('Choose Family');
        echo '</h2>';

        $gibbonFamilyID = $_GET['gibbonFamilyID'] ?? null;

        $form = Form::create('selectFamily', $gibbon->session->get('absoluteURL').'/index.php', 'get');
        $form->addHiddenValue('q', '/modules/'.$gibbon->session->get('module').'/data_family.php');

        if ($highestAction == 'Update Family Data_any') {
            $data = array();
            $sql = "SELECT gibbonFamily.gibbonFamilyID as value, name FROM gibbonFamily ORDER BY name";
        } else {
            $data = array('gibbonPersonID' => $gibbon->session->get('gibbonPersonID'));
            $sql = "SELECT gibbonFamily.gibbonFamilyID as value, name FROM gibbonFamily JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID) WHERE gibbonPersonID=:gibbonPersonID AND childDataAccess='Y' ORDER BY name";
        }
        $row = $form->addRow();
            $row->addLabel('gibbonFamilyID', __('Family'));
            $row->addSelect('gibbonFamilyID')
                ->fromQuery($pdo, $sql, $data)
                ->required()
                ->selected($gibbonFamilyID)
                ->placeholder();

        $row = $form->addRow();
            $row->addSubmit();

        echo $form->getOutput();

        if ($gibbonFamilyID != '') {
            echo '<h2>';
            echo __('Update Data');
            echo '</h2>';

            //Check access to person
            if ($highestAction == 'Update Family Data_any') {

                    $dataCheck = array('gibbonFamilyID' => $gibbonFamilyID);
                    $sqlCheck = 'SELECT name, gibbonFamily.gibbonFamilyID FROM gibbonFamily WHERE gibbonFamilyID=:gibbonFamilyID';
                    $resultCheck = $connection2->prepare($sqlCheck);
                    $resultCheck->execute($dataCheck);
            } else {
                try {
                    $dataCheck = array('gibbonFamilyID' => $gibbonFamilyID, 'gibbonPersonID' => $gibbon->session->get('gibbonPersonID'));
                    $sqlCheck = "SELECT name, gibbonFamily.gibbonFamilyID FROM gibbonFamily JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID) WHERE gibbonPersonID=:gibbonPersonID AND childDataAccess='Y' AND gibbonFamily.gibbonFamilyID=:gibbonFamilyID";
                    $resultCheck = $connection2->prepare($sqlCheck);
                    $resultCheck->execute($dataCheck);
                } catch (PDOException $e) {
                    echo $e->getMessage();
                }
            }

            if ($resultCheck->rowCount() != 1) {
                echo "<div class='error'>";
                echo __('The selected record does not exist, or you do not have access to it.');
                echo '</div>';
            } else {
                //Check if there is already a pending form for this user
                $existing = false;
                $proceed = false;

                    $data = array('gibbonFamilyID' => $gibbonFamilyID, 'gibbonPersonIDUpdater' => $gibbon->session->get('gibbonPersonID'));
                    $sql = "SELECT * FROM gibbonFamilyUpdate WHERE gibbonFamilyID=:gibbonFamilyID AND gibbonPersonIDUpdater=:gibbonPersonIDUpdater AND status='Pending'";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);

                if ($result->rowCount() > 1) {
                    echo "<div class='error'>";
                    echo __('Your request failed due to a database error.');
                    echo '</div>';
                } elseif ($result->rowCount() == 1) {
                    $existing = true;
                    echo "<div class='warning'>";
                    echo __('You have already submitted a form, which is awaiting processing by an administrator. If you wish to make changes, please edit the data below, but remember your data will not appear in the system until it has been processed.');
                    echo '</div>';
                    $proceed = true;
                } else {
                    //Get user's data

                        $data = array('gibbonFamilyID' => $gibbonFamilyID);
                        $sql = 'SELECT * FROM gibbonFamily WHERE gibbonFamilyID=:gibbonFamilyID';
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    if ($result->rowCount() != 1) {
                        echo "<div class='error'>";
                        echo __('The specified record cannot be found.');
                        echo '</div>';
                    } else {
                        $proceed = true;
                    }
                }

                if ($proceed == true) {
                    //Let's go!
                    $values = $result->fetch();

                    $required = ($highestAction != 'Update Family Data_any');

                    $form = Form::create('updateFamily', $gibbon->session->get('absoluteURL').'/modules/'.$gibbon->session->get('module').'/data_familyProcess.php?gibbonFamilyID='.$gibbonFamilyID);
                    $form->setFactory(DatabaseFormFactory::create($pdo));

                    $form->addHiddenValue('address', $gibbon->session->get('address'));
                    $form->addHiddenValue('existing', isset($values['gibbonFamilyUpdateID'])? $values['gibbonFamilyUpdateID'] : 'N');

                    $row = $form->addRow();
                        $row->addLabel('nameAddress', __('Address Name'))->description(__('Formal name to address parents with.'));
                        $row->addTextField('nameAddress')->maxLength(100)->setRequired($required);

                    $row = $form->addRow();
                        $row->addLabel('homeAddress', __('Home Address'))->description(__('Unit, Building, Street'));
                        $row->addTextArea('homeAddress')->maxLength(255)->setRequired($required)->setRows(2);

                    $row = $form->addRow();
                        $row->addLabel('homeAddressDistrict', __('Home Address (District)'))->description(__('County, State, District'));
                        $row->addTextFieldDistrict('homeAddressDistrict')->setRequired($required);

                    $row = $form->addRow();
                        $row->addLabel('homeAddressCountry', __('Home Address (Country)'));
                        $row->addSelectCountry('homeAddressCountry')->setRequired($required);

                    $row = $form->addRow();
                        $row->addLabel('languageHomePrimary', __('Home Language - Primary'));
                        $row->addSelectLanguage('languageHomePrimary')->setRequired($required);

                    $row = $form->addRow();
                        $row->addLabel('languageHomeSecondary', __('Home Language - Secondary'));
                        $row->addSelectLanguage('languageHomeSecondary');

                    $row = $form->addRow();
                        $row->addFooter();
                        $row->addSubmit();

                    $form->loadAllValuesFrom($values);

                    echo $form->getOutput();
                }
            }
        }
    }
}
