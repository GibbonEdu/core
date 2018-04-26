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

if (isActionAccessible($guid, $connection2, '/modules/System Admin/module_manage_update.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/module_manage.php'>".__($guid, 'Manage Modules')."</a> > </div><div class='trailEnd'>".__($guid, 'Update Module').'</div>';
    echo '</div>';

    $return = null;
    if (isset($_GET['return'])) {
        $return = $_GET['return'];
    }
    $returns = array();
    $returns['warning1'] = __($guid, 'Some aspects of your request failed, but others were successful. The elements that failed are shown below:');
    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, $returns);
    }
    if (isset($_SESSION[$guid]['moduleUpdateError'])) {
        if ($_SESSION[$guid]['moduleUpdateError'] != '') {
            echo "<div class='error'>";
            echo __($guid, 'The following SQL statements caused errors:').' '.$_SESSION[$guid]['moduleUpdateError'];
            echo '</div>';
        }
        $_SESSION[$guid]['moduleUpdateError'] = null;
    }

    //Check if school year specified
    $gibbonModuleID = $_GET['gibbonModuleID'];
    if ($gibbonModuleID == '') {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        try {
            $data = array('gibbonModuleID' => $gibbonModuleID);
            $sql = 'SELECT * FROM gibbonModule WHERE gibbonModuleID=:gibbonModuleID';
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

            $versionDB = $values['version'];
            if (file_exists($_SESSION[$guid]['absolutePath'].'/modules/'.$values['name'].'/version.php')) {
                include $_SESSION[$guid]['absolutePath'].'/modules/'.$values['name'].'/version.php';
            }
            @$versionCode = $moduleVersion;

            echo '<p>';
            echo sprintf(__($guid, 'This page allows you to semi-automatically update the %1$s module to a new version. You need to take care of the file updates, and based on the new files, Gibbon will do the database upgrades.'), htmlPrep($values['name']));
            echo '</p>';

            if ($return == 'success0') {
                echo '<p>';
                echo '<b>'.__($guid, 'You seem to be all up to date, good work!').'</b>';
                echo '</p>';
            } elseif ($versionDB > $versionCode or $versionCode == '') {
                //Error
                echo "<div class='error'>";
                echo __($guid, 'An error has occurred determining the version of the system you are using.');
                echo '</div>';
            } elseif ($versionDB == $versionCode) {
                //Instructions on how to update
                echo '<h3>';
                echo __($guid, 'Update Instructions');
                echo '</h3>';
                echo '<ol>';
                echo '<li>'.sprintf(__($guid, 'You are currently using %1$s v%2$s.'),  htmlPrep($values['name']), $versionCode).'</i></li>';
                echo '<li>'.sprintf(__($guid, 'Check %1$s for a newer version of this module.'), "<a target='_blank' href='https://gibbonedu.org/extend'>gibbonedu.org</a>").'</li>';
                echo '<li>'.__($guid, 'Download the latest version, and unzip it on your computer.').'</li>';
                echo '<li>'.__($guid, 'Use an FTP client to upload the new files to your server\'s modules folder.').'</li>';
                echo '<li>'.__($guid, 'Reload this page and follow the instructions to update your database to the latest version.').'</li>';
                echo '</ol>';
            } elseif ($versionDB < $versionCode) {
                //Time to update
                echo '<h3>';
                echo __($guid, 'Database Update');
                echo '</h3>';
                echo '<p>';
                echo sprintf(__($guid, 'It seems that you have updated your %1$s module code to a new version, and are ready to update your database from v%2$s to v%3$s. <b>Click "Submit" below to continue. This operation cannot be undone: backup your entire database prior to running the update!'), htmlPrep($values['name']), $versionDB, $versionCode).'</b>';
                echo '</p>'; 
                
                $form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/module_manage_updateProcess.php?&gibbonModuleID='.$gibbonModuleID);
                
                $form->addHiddenValue('versionDB', $versionDB);
                $form->addHiddenValue('versionCode', $versionCode);
                $form->addHiddenValue('address', $_SESSION[$guid]['address']);

                $form->addRow()->addSubmit();
                echo $form->getOutput(); 
            }
        }
    }
}
