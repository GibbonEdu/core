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

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Tracking/dataPoints.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Get action with highest precendence
    $page->breadcrumbs->add(__('Data Points'));

    //Check Settings
    $externalAssessmentDataPoints = unserialize(getSettingByScope($connection2, 'Tracking', 'externalAssessmentDataPoints'));
    $internalAssessmentDataPoints = unserialize(getSettingByScope($connection2, 'Tracking', 'internalAssessmentDataPoints'));
    if (count($externalAssessmentDataPoints) == 0 and count($internalAssessmentDataPoints) == 0) { //Seems like things are not configured, so give appropriate information according to access
        echo "<div class='warning'>";
        if (isActionAccessible($guid, $connection2, '/modules/School Admin/trackingSettings.php') == false) { //No access, just give warning
                echo sprintf(__('Data Points needs to be configured before use, but you do not have permission to do this. Please contact %1$s for help with this issue.'), "<a href='mailto:".$_SESSION[$guid]['organisationAdministratorEmail']."'>".$_SESSION[$guid]['organisationAdministratorName'].'</a>');
        } else { //Yes access, give link to settings.
                echo sprintf(__('Data Points needs to be configured before use. Please take a look at %1$sTracking Settings%2$s to set up what data points to use.'), "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/School Admin/trackingSettings.php'>", '</a>');
        }
        echo '</div>';
    } else { //Seems like things are configured, so give welcome message.
        echo '<p>';
        if (isActionAccessible($guid, $connection2, '/modules/School Admin/trackingSettings.php') == false) { //No access, just give warning
                echo sprintf(__('Data Points has been configured to allow you to export certain key assessment data. Please contact %1$s if you are not seeing the data you need here.'), "<a href='mailto:".$_SESSION[$guid]['organisationAdministratorEmail']."'>".$_SESSION[$guid]['organisationAdministratorName'].'</a>').'<br/>';
        } else { //Yes access, give link to settings.
                echo sprintf(__('Data Points has been configured to export certain key assessment data. Please take a look at %1$sTracking Settings%2$s to change what data points are included.'), "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/School Admin/trackingSettings.php'>", '</a>').' '.__('Use the export button below to prepare your Data Points export for download.').'<br/>';
        }
        echo '<br/>';
        echo '</p>';
        echo "<div class='warning'>";
        echo __('Warning, please note that this process is resource intensive, and may slow down access to the system for other users. Please be patient as the download might take a few minutes to prepare.');
        echo "<div class='linkTop'>";
        echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/dataPoints_contents.php'>".__('Export to Excel')." <img title='".__('Export to Excel')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/download.png'/></a>";
        echo '</div>';
        echo '</div>';
    }
}
