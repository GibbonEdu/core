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

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/System Admin/update.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __('You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    $page->breadcrumbs->add(__('Update'));

    $return = null;
    if (isset($_GET['return'])) {
        $return = $_GET['return'];
    }
    $returns = array();
    $returns['warning1'] = __('Some aspects of your request failed, but others were successful. The elements that failed are shown below:');
    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, $returns);
    }

    if (isset($_SESSION[$guid]['systemUpdateError'])) {
        if ($_SESSION[$guid]['systemUpdateError'] != '') {
            echo "<div class='error'>";
            echo __('The following SQL statements caused errors:').' '.$_SESSION[$guid]['systemUpdateError'];
            echo '</div>';
        }
        $_SESSION[$guid]['systemUpdateError'] = null;
    }

    getSystemSettings($guid, $connection2);

    $versionDB = getSettingByScope($connection2, 'System', 'version');
    $versionCode = $version;

    echo '<p>';
    echo __('This page allows you to semi-automatically update your Gibbon installation to a new version. You need to take care of the file updates, and based on the new files, Gibbon will do the database upgrades.');
    echo '</p>';

    $cuttingEdgeCode = getSettingByScope($connection2, 'System', 'cuttingEdgeCode');
    $databaseUpdated = false;
    if ($cuttingEdgeCode != 'Y') {
        //Check for new version of Gibbon
        echo getCurrentVersion($guid, $connection2, $version);

        if ($return == 'success0') {
            $databaseUpdated = true;
            echo '<p>';
            echo '<b>'.__('You seem to be all up to date, good work buddy!').'</b>';
            echo '</p>';
        } elseif (version_compare($versionDB, $versionCode, '=')) {
            $databaseUpdated = true;
            //Instructions on how to update
            echo '<h3>';
            echo __('Update Instructions');
            echo '</h3>';
            echo '<ol>';
            echo '<li>'.sprintf(__('You are currently using Gibbon v%1$s.'), $versionCode).'</i></li>';
            echo '<li>'.sprintf(__('Check %1$s for a newer version of Gibbon.'), "<a target='_blank' href='https://gibbonedu.org/download'>the Gibbon download page</a>").'</li>';
            echo '<li>'.__('Download the latest version, and unzip it on your computer.').'</li>';
            echo '<li>'.__('Use an FTP client to upload the new files to your server, making sure not to overwrite any additional modules and themes previously added to the system.').'</li>';
            echo '<li>'.__('Reload this page and follow the instructions to update your database to the latest version.').'</li>';
            echo '</ol>';
        } elseif (version_compare($versionDB, $versionCode, '>')) {
            //Error
            echo "<div class='error'>";
            echo __('An error has occurred determining the version of the system you are using.');
            echo '</div>';
        } elseif (version_compare($versionDB, $versionCode, '<')) {
            //Time to update
            echo '<h3>';
            echo __('Database Update');
            echo '</h3>';
            echo '<p>';
            echo sprintf(__('It seems that you have updated your Gibbon code to a new version, and are ready to update your database from v%1$s to v%2$s. <b>Click "Submit" below to continue. This operation cannot be undone: backup your entire database prior to running the update!'), $versionDB, $versionCode).'</b>';
            echo '</p>';

            $form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/updateProcess.php?type=regularRelease');

            $form->addHiddenValue('versionDB', $versionDB);
            $form->addHiddenValue('versionCode', $versionCode);
            $form->addHiddenValue('address', $_SESSION[$guid]['address']);

            $form->addRow()->addSubmit();
            echo $form->getOutput();
        }
    } else {
        $cuttingEdgeCodeLine = getSettingByScope($connection2, 'System', 'cuttingEdgeCodeLine');
        if ($cuttingEdgeCodeLine == '' or is_null($cuttingEdgeCodeLine)) {
            $cuttingEdgeCodeLine = 0;
        }

        //Check to see if there are any updates
        include './CHANGEDB.php';
        $versionMax = $sql[(count($sql))][0];
        $sqlTokens = explode(';end', $sql[(count($sql))][1]);
        $versionMaxLinesMax = (count($sqlTokens) - 1);
        $update = false;
        if (version_compare($versionMax, $versionDB, '>')) {
            $update = true;
        } else {
            if ($versionMaxLinesMax > $cuttingEdgeCodeLine) {
                $update = true;
            }
        }

        //Go! Start with warning about cutting edge code
        echo "<div class='warning'>";
        echo __('Your system is set up to run Cutting Edge code, which may or may not be as reliable as regular release code. Backup before installing, and avoid using cutting edge in production.');
        echo '</div>';

        if ($return == 'success0') {
            $databaseUpdated = true;
            echo '<p>';
            echo '<b>'.__('You seem to be all up to date, good work buddy!').'</b>';
            echo '</p>';
        } elseif ($update == false) {
            $databaseUpdated = true;
            //Instructions on how to update
            echo '<h3>';
            echo __('Update Instructions');
            echo '</h3>';
            echo '<ol>';
            echo '<li>'.sprintf(__('You are currently using Cutting Edge Gibbon v%1$s'), $versionCode).'</i></li>';
            echo '<li>'.sprintf(__('Check %1$s to get the latest commits.'), "<a target='_blank' href='https://github.com/GibbonEdu/core'>our GitHub repo</a>").'</li>';
            echo '<li>'.__('Download the latest commits, and unzip it on your computer.').'</li>';
            echo '<li>'.__('Use an FTP client to upload the new files to your server, making sure not to overwrite any additional modules and themes previously added to the system.').'</li>';
            echo '<li>'.__('Reload this page and follow the instructions to update your database to the latest version.').'</li>';
            echo '</ol>';
        } elseif ($update == true) {
            //Time to update
            echo '<h3>';
            echo __('Database Update');
            echo '</h3>';
            echo '<p>';
            echo sprintf(__('It seems that you have updated your Gibbon code to a new version, and are ready to update your database from v%1$s line %2$s to v%3$s line %4$s. <b>Click "Submit" below to continue. This operation cannot be undone: backup your entire database prior to running the update!'), $versionDB, $cuttingEdgeCodeLine, $versionCode, $versionMaxLinesMax).'</b>';
            echo '</p>';

            $form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/updateProcess.php?type=cuttingEdge');

            $form->addHiddenValue('versionDB', $versionDB);
            $form->addHiddenValue('versionCode', $versionCode);
            $form->addHiddenValue('address', $_SESSION[$guid]['address']);

            $form->addRow()->addSubmit();
            echo $form->getOutput();
        }
    }

    //INNODB UPGRADE - can be removed
    if (version_compare($version, '16.0.00', '>=')) {
        echo '<h3>';
        echo __('Database Engine Migration');
        echo '</h3>';
        echo '<p>';
        echo __('Starting from v16, Gibbon is offering installations the option to migrate from MySQL\'s MyISAM engine to InnoDB, as a way to achieve greater reliability and performance.');
        echo '</p>';

        if (!$databaseUpdated) { //Not eligible
            echo '<div class=\'warning\'>';
                echo __('Please run the database update, above, before proceeding with the Database Engine Migration.');
            echo '</div>';
        }
        else { //Eligible
            //CHECK DEFAULT ENGINE
            $currentEngine = 'Unknown';
            try {
                $data = array();
                $sql = 'SHOW ENGINES';
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }
            if ($result->rowCount() < 1) {
                echo "<div class='error'>";
                echo __('There are no records to display.');
                echo '</div>';
            } else {
                while ($row = $result->fetch()) {
                    if ($row['Support'] == 'DEFAULT') {
                        $currentEngine = $row['Engine'];
                    }
                }
            }

            if ($currentEngine == 'InnoDB') {
                echo "<div class='message'>";
                    echo sprintf(__('Your current default database engine is: %1$s'), $currentEngine);
                echo "</div>";
            }
            else {
                echo "<div class='warning'>";
                    echo sprintf(__('Your current default database engine is: %1$s.'), $currentEngine).' '.__('It is advised that you change your server config so that your default storage engine is set to InnoDB.');
                echo "</div>";
            }

            //CHECK TABLES
            $tableUpdate = false;
            $tablesTotal = 0;
            $tablesInnoDB = 0;
            try {
                $data = array();
                $sql = 'SHOW TABLE STATUS';
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }
            if ($result->rowCount() < 1) {
                echo "<div class='error'>";
                echo __('There are no records to display.');
                echo '</div>';
            } else {
                while ($row = $result->fetch()) {
                    if ($row['Engine'] == 'InnoDB') {
                        $tablesInnoDB++;
                    }
                    $tablesTotal++;
                }
                if ($tablesTotal-$tablesInnoDB > 0) {
                    $tableUpdate = true;
                }
            }

            if (!$tableUpdate) { //No tables to update
                echo "<div class='success'>";
                    echo __('All of your tables are set to InnoDB. Well done!');
                echo "</div>";
            }
            else {
                echo "<div class='warning'>";
                    echo sprintf(__('%1$s of your tables are not set to InnoDB.'), $tablesTotal-$tablesInnoDB).' <b>'.__('Click "Submit" below to continue. This operation cannot be undone: backup your entire database prior to running the update!').'</b>';
                echo "</div>";
                $form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/updateProcess.php?type=InnoDB');

                $form->addHiddenValue('address', $_SESSION[$guid]['address']);

                $form->addRow()->addSubmit();
                echo $form->getOutput();
            }
        }
    }

    //echo "ALTER TABLE ".$row['Tables_in_'.$databaseName]." ENGINE=InnoDB;<br/>";


}
