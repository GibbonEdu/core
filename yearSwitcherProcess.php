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

// Gibbon system-wide include
require_once './gibbon.php';

$URL = './index.php';
$gibbonSchoolYearID = $_POST['gibbonSchoolYearID'] ?? null;

$gibbon->session->set('pageLoads', null);

//Check for parameter
if (empty($gibbonSchoolYearID)) {
    $URL .= '?return=error0';
    header("Location: {$URL}");
    exit;
} else {
    
        $data = array('gibbonRoleID' => $gibbon->session->get('gibbonRoleIDCurrent'));
        $sql = "SELECT futureYearsLogin, pastYearsLogin FROM gibbonRole WHERE gibbonRoleID=:gibbonRoleID";
        $result = $connection2->prepare($sql);
        $result->execute($data);

    //Test to see if username exists and is unique
    if ($result->rowCount() == 1) {
        $row = $result->fetch();

        if ($row['futureYearsLogin'] != 'Y' and $row['pastYearsLogin'] != 'Y') { //NOT ALLOWED DUE TO CONTROLS ON ROLE, KICK OUT!
            $URL .= '?return=error0';
            header("Location: {$URL}");
            exit();
        } else {
            //Get details on requested school year
            
                $dataYear = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
                $sqlYear = 'SELECT * FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearID';
                $resultYear = $connection2->prepare($sqlYear);
                $resultYear->execute($dataYear);

            //Get current year sequenceNumber
            
                $dataYearCurrent = array();
                $sqlYearCurrent = "SELECT * FROM gibbonSchoolYear WHERE status='Current'";
                $resultYearCurrent = $connection2->prepare($sqlYearCurrent);
                $resultYearCurrent->execute($dataYearCurrent);

            //Check number of rows returned.
            //If it is not 1, show error
            if (!($resultYear->rowCount() == 1) && !($resultYearCurrent->rowCount() == 1)) {
                $URL .= '?return=error0';
                header("Location: {$URL}");
                exit;
            }
            //Else get year details
            else {
                $rowYear = $resultYear->fetch();
                $rowYearCurrent = $resultYearCurrent->fetch();
                if ($row['futureYearsLogin'] != 'Y' and $rowYearCurrent['sequenceNumber'] < $rowYear['sequenceNumber']) { //POSSIBLY NOT ALLOWED DUE TO CONTROLS ON ROLE, CHECK YEAR
                    $URL .= '?return=error0';
                    header("Location: {$URL}");
                    exit();
                } elseif ($row['pastYearsLogin'] != 'Y' and $rowYearCurrent['sequenceNumber'] > $rowYear['sequenceNumber']) { //POSSIBLY NOT ALLOWED DUE TO CONTROLS ON ROLE, CHECK YEAR
                    $URL .= '?return=error0';
                    header("Location: {$URL}");
                    exit();
                } else { //ALLOWED
                    $gibbon->session->set('gibbonSchoolYearID', $rowYear['gibbonSchoolYearID']);
                    $gibbon->session->set('gibbonSchoolYearName', $rowYear['name']);
                    $gibbon->session->set('gibbonSchoolYearSequenceNumber', $rowYear['sequenceNumber']);
                    $gibbon->session->set('gibbonSchoolYearFirstDay', $rowYear['firstDay']);
                    $gibbon->session->set('gibbonSchoolYearLastDay', $rowYear['lastDay']);

                    // Reload cached FF actions
                    $gibbon->session->cacheFastFinderActions($gibbon->session->get('gibbonRoleIDCurrent'));

                    // Clear the main menu from session cache
                    $gibbon->session->forget('menuMainItems');

                    $URL .= '?return=success0';
                    header("Location: {$URL}");
                }
            }
        }
    }
}
