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

//Module includes from User Admin (for custom fields)
include './modules/User Admin/moduleFunctions.php';

$proceed = false;
$public = false;
if (isset($_SESSION[$guid]['username']) == false) {
    $public = true;
    //Get public access
    $access = getSettingByScope($connection2, 'Staff Application Form', 'staffApplicationFormPublicApplications');
    if ($access == 'Y') {
        $proceed = true;
    }
} else {
    if (isActionAccessible($guid, $connection2, '/modules/Staff/applicationForm.php') != false) {
        $proceed = true;
    }
}

if ($proceed == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    if (isset($_SESSION[$guid]['username'])) {
        echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".$_SESSION[$guid]['organisationNameShort'].' '.__($guid, 'Application Form').'</div>';
    } else {
        echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > </div><div class='trailEnd'>".$_SESSION[$guid]['organisationNameShort'].' '.__($guid, 'Application Form').'</div>';
    }
    echo '</div>';

    //Check for job openings
    try {
        $data = array('dateOpen' => date('Y-m-d'));
        $sql = "SELECT * FROM gibbonStaffJobOpening WHERE active='Y' AND dateOpen<=:dateOpen ORDER BY jobTitle";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        echo "<div class='error'>";
        echo __($guid, 'Your request failed due to a database error.');
        echo '</div>';
    }

    if ($result->rowCount() < 1) {
        echo "<div class='error'>";
        echo __($guid, 'There are no job openings at this time: please try again later.');
        echo '</div>';
    } else {
        $jobOpenings = $result->fetchAll();

        echo "<div class='linkTop'>";
        echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/applicationForm.php'>".__($guid, 'Submit Application Form')."<img style='margin-left: 5px' title='".__($guid, 'Submit Application Form')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/plus.png'/></a>";
        echo '</div>';

        foreach ($jobOpenings as $jobOpening) {
            echo '<h3>'.$jobOpening['jobTitle'].'</h3>';
            echo '<p><b>'.sprintf(__($guid, 'Job Type: %1$s'), $jobOpening['type']).'</b></p>';
            echo $jobOpening['description'].'<br/>';
        }
    }
}
