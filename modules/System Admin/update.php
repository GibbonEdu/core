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

@session_start();

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/System Admin/update.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Update').'</div>';
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

    if (isset($_SESSION[$guid]['systemUpdateError'])) {
        if ($_SESSION[$guid]['systemUpdateError'] != '') {
            echo "<div class='error'>";
            echo __($guid, 'The following SQL statements caused errors:').' '.$_SESSION[$guid]['systemUpdateError'];
            echo '</div>';
        }
        $_SESSION[$guid]['systemUpdateError'] = null;
    }

    getSystemSettings($guid, $connection2);

    $versionDB = getSettingByScope($connection2, 'System', 'version');
    $versionCode = $version;

    echo '<p>';
    echo __($guid, 'This page allows you to semi-automatically update your Gibbon installation to a new version. You need to take care of the file updates, and based on the new files, Gibbon will do the database upgrades.');
    echo '</p>';

    $cuttingEdgeCode = getSettingByScope($connection2, 'System', 'cuttingEdgeCode');
    if ($cuttingEdgeCode != 'Y') {
        //Check for new version of Gibbon
        echo getCurrentVersion($guid, $connection2, $version);

        if ($return == 'success0') {
            echo '<p>';
            echo '<b>'.__($guid, 'You seem to be all up to date, good work buddy!').'</b>';
            echo '</p>';
        } elseif (version_compare($versionDB, $versionCode, '=')) {
            //Instructions on how to update
            echo '<h3>';
            echo __($guid, 'Update Instructions');
            echo '</h3>';
            echo '<ol>';
            echo '<li>'.sprintf(__($guid, 'You are currently using Gibbon v%1$s.'), $versionCode).'</i></li>';
            echo '<li>'.sprintf(__($guid, 'Check %1$s for a newer version of Gibbon.'), "<a target='_blank' href='https://gibbonedu.org/download'>the Gibbon download page</a>").'</li>';
            echo '<li>'.__($guid, 'Download the latest version, and unzip it on your computer.').'</li>';
            echo '<li>'.__($guid, 'Use an FTP client to upload the new files to your server, making sure not to overwrite any additional modules and themes previously added to the system.').'</li>';
            echo '<li>'.__($guid, 'Reload this page and follow the instructions to update your database to the latest version.').'</li>';
            echo '</ol>';
        } elseif (version_compare($versionDB, $versionCode, '>')) {
            //Error
            echo "<div class='error'>";
            echo __($guid, 'An error has occurred determining the version of the system you are using.');
            echo '</div>';
        } elseif (version_compare($versionDB, $versionCode, '<')) {
            //Time to update
            echo '<h3>';
            echo __($guid, 'Datebase Update');
            echo '</h3>';
            echo '<p>';
            echo sprintf(__($guid, 'It seems that you have updated your Gibbon code to a new version, and are ready to update your databse from v%1$s to v%2$s. <b>Click "Submit" below to continue. This operation cannot be undone: backup your entire database prior to running the update!'), $versionDB, $versionCode).'</b>';
            echo '</p>';
            ?>
			<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/updateProcess.php?type=regularRelease' ?>">
				<table cellspacing='0' style="width: 100%">	
					<tr>
						<td class="right"> 
							<input type="hidden" name="versionDB" value="<?php echo $versionDB ?>">
							<input type="hidden" name="versionCode" value="<?php echo $versionCode ?>">
							<input type="hidden" name="address" value="<?php echo $_SESSION[$guid]['address'] ?>">
							<input type="submit" value="<?php echo __($guid, 'Submit');
            ?>">
						</td>
					</tr>
				</table>
			</form>
			<?php

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
        echo __($guid, 'Your system is set up to run Cutting Edge code, which may or may not be as reliable as regular release code. Backup before installing, and avoid using cutting edge in production.');
        echo '</div>';

        if ($return == 'success0') {
            echo '<p>';
            echo '<b>'.__($guid, 'You seem to be all up to date, good work buddy!').'</b>';
            echo '</p>';
        } elseif ($update == false) {
            //Instructions on how to update
            echo '<h3>';
            echo __($guid, 'Update Instructions');
            echo '</h3>';
            echo '<ol>';
            echo '<li>'.sprintf(__($guid, 'You are currently using Cutting Edge Gibbon v%1$s'), $versionCode).'</i></li>';
            echo '<li>'.sprintf(__($guid, 'Check %1$s to get the latest commits.'), "<a target='_blank' href='https://github.com/GibbonEdu/core'>our GitHub repo</a>").'</li>';
            echo '<li>'.__($guid, 'Download the latest commits, and unzip it on your computer.').'</li>';
            echo '<li>'.__($guid, 'Use an FTP client to upload the new files to your server, making sure not to overwrite any additional modules and themes previously added to the system.').'</li>';
            echo '<li>'.__($guid, 'Reload this page and follow the instructions to update your database to the latest version.').'</li>';
            echo '</ol>';
        } elseif ($update == true) {
            //Time to update
            echo '<h3>';
            echo __($guid, 'Datebase Update');
            echo '</h3>';
            echo '<p>';
            echo sprintf(__($guid, 'It seems that you have updated your Gibbon code to a new version, and are ready to update your databse from v%1$s line %2$s to v%3$s line %4$s. <b>Click "Submit" below to continue. This operation cannot be undone: backup your entire database prior to running the update!'), $versionDB, $cuttingEdgeCodeLine, $versionCode, $versionMaxLinesMax).'</b>';
            echo '</p>';
            ?>
			<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/updateProcess.php?type=cuttingEdge' ?>">
				<table cellspacing='0' style="width: 100%">	
					<tr>
						<td class="right"> 
							<input type="hidden" name="versionDB" value="<?php echo $versionDB ?>">
							<input type="hidden" name="versionCode" value="<?php echo $versionCode ?>">
							<input type="hidden" name="address" value="<?php echo $_SESSION[$guid]['address'] ?>">
							<input type="submit" value="<?php echo __($guid, 'Submit');
            ?>">
						</td>
					</tr>
				</table>
			</form>
			<?php

        }
    }
}
?>