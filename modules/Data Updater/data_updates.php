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

use Gibbon\DataUpdater\Domain\DataUpdaterGateway;

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Data Updater/data_updates.php') == false) {
    //Acess denied
    echo '<div class="error">';
    echo __('You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__('Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__('My Data Updates').'</div>';
    echo '</div>';

    $gibbonPersonID = $_SESSION[$guid]['gibbonPersonID'];

    $gateway = new DataUpdaterGateway($pdo);
    $updatablePeople = $gateway->selectUpdatableUsersByPerson($gibbonPersonID);

    // Get the data updater settings for required updates
    $requiredUpdates = getSettingByScope($connection2, 'Data Updater', 'requiredUpdates');
    if ($requiredUpdates == 'Y') {
        $requiredUpdatesByType = getSettingByScope($connection2, 'Data Updater', 'requiredUpdatesByType');
        $requiredUpdatesByType = explode(',', $requiredUpdatesByType);
        $cutoffDate = getSettingByScope($connection2, 'Data Updater', 'cutoffDate');
    } else {
        $requiredUpdatesByType = array();
        $cutoffDate = null;
    }
    
    // Get the active data types based on this user's permissions
    $updatableDataTypes = array();
    if (isActionAccessible($guid, $connection2, '/modules/Data Updater/data_family.php')) $updatableDataTypes[] = 'Family';
    if (isActionAccessible($guid, $connection2, '/modules/Data Updater/data_personal.php')) $updatableDataTypes[] = 'Personal';
    if (isActionAccessible($guid, $connection2, '/modules/Data Updater/data_medical.php')) $updatableDataTypes[] = 'Medical';
    if (isActionAccessible($guid, $connection2, '/modules/Data Updater/data_finance.php')) $updatableDataTypes[] = 'Finance';

    echo '<p>';
    echo __('This page shows all the data updates that are available to you. If an update is required it will be highlighted in red.');
    echo '</p>';

    if ($requiredUpdates == 'Y') {
        $updatesRequiredCount = $gateway->countAllRequiredUpdatesByPerson($_SESSION[$guid]['gibbonPersonID']);

        if ($updatesRequiredCount > 0) {
            echo '<div class="warning">';
            if (isset($_GET['redirect'])) {
                echo '<b>'.__("You have been redirected upon login because there are pending data updates.").'</b> ';
            }
            echo __("Please take a moment to view and submit the required updates. Even if you don't change your data, submitting the form will indicate you've reviewed the data and have confirmed it is correct.");
            echo '</div>';
        } else {
            echo '<div class="success">';
            echo __('Your data is up to date. Please note any recent changes will not appear in the system until they have been approved.');
            echo '</div>';
        }
    }

    echo '<h2>';
    echo __('Data Updates');
    echo '</h2>';

    if ($updatablePeople->rowCount() == 0 || empty($updatableDataTypes)) {
        echo "<div class='error'>";
        echo __('There are no records to display.');
        echo '</div>';
    } else {
        echo '<table cellspacing="0" class="fullWidth colorOddEven">';
        echo '<tr class="head">';
        echo '<th>';
        echo __('Photo');
        echo '</th>';
        echo '<th>';
        echo __('Name');
        echo '</th>';
        foreach ($updatableDataTypes as $type) {
            echo '<th>';
            echo __($type.' Data');
            echo '</th>';
        }
        echo '</tr>';

        while ($person = $updatablePeople->fetch()) {
            echo '<tr>';

            echo '<td>';
            echo getUserPhoto($guid, $person['image_240'], 75);
            echo '</td>';

            echo '<td>';
            echo formatName('', $person['preferredName'], $person['surname'], 'Student', true);
            echo '</td>';

            $dataUpdatesByType = $gateway->selectDataUpdatesByPerson($person['gibbonPersonID'])->fetchAll(\PDO::FETCH_GROUP);

            foreach ($updatableDataTypes as $type) {
                $updateRequired = false;
                $output = '';

                if (!empty($dataUpdatesByType[$type])) {
                    foreach ($dataUpdatesByType[$type] as $dataUpdate) {
                        $output .= '<a href="'.$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Data Updater/data_'.strtolower($type).'.php&'.$dataUpdate['idType'].'='.$dataUpdate['id'].'">';

                        $lastUpdate = !empty($dataUpdate['lastUpdated'])? __('Last Updated').': '.date('F j, Y', strtotime($dataUpdate['lastUpdated'])) : '';
                        
                        if (!in_array($type, $requiredUpdatesByType) || empty($cutoffDate)) {
                            // Display an edit link if updates aren't required or no cutoff date is set
                            $output .= "<img title='".__('Edit').'<br/>'.$lastUpdate."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/><br/>";
                            $output .= $dataUpdate['name'];
                        } else if (empty($dataUpdate['lastUpdated']) || $dataUpdate['lastUpdated'] < $cutoffDate ) {
                            // Display an arrow and highlight the cell if the most recent update is before the cutoff date
                            $output .= "<img title='".__('Update Required').'<br/>'.$lastUpdate."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/copyforward.png'/>";
                            if ($dataUpdate['name'] != '') {
                                $output .= "<br/>".$dataUpdate['name'];
                            }
                            $output .= "<br/>".__('Update Required');
                            $updateRequired = true;
                        } else {
                            // Display a checkmark if the most recent data is up-to-date
                            $output .= "<img title='".__('Up to date').'<br/>'.$lastUpdate."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/iconTick.png'/><br/>";
                            $output .= $dataUpdate['name'];
                        }
                        $output .= '</a><br/>';
                    }
                } else {
                    $output .= '<span class="small subdued emphasis">'.__('N/A').'</span>';
                }

                echo '<td class="'.($updateRequired? 'error' : '').'">';
                echo $output;
                echo '</td>';
            }

            echo '</tr>';
        }

        echo '</table>';
    }
}
