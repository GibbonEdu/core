<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright © 2010, Gibbon Foundation
Gibbon™, Gibbon Education Ltd. (Hong Kong)

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

use Gibbon\Http\Url;

// Gibbon system-wide include
require_once './gibbon.php';

$gibbonSchoolYearID = $_POST['gibbonSchoolYearID'] ?? null;

$session->set('pageLoads', null);

//Check for parameter
if (empty($gibbonSchoolYearID)) {
    $URL = Url::fromRoute()->withReturn('error0');
    header("Location: {$URL}");
    exit;
} else {

        $data = array('gibbonRoleID' => $session->get('gibbonRoleIDCurrent'));
        $sql = "SELECT futureYearsLogin, pastYearsLogin FROM gibbonRole WHERE gibbonRoleID=:gibbonRoleID";
        $result = $connection2->prepare($sql);
        $result->execute($data);

    //Test to see if username exists and is unique
    if ($result->rowCount() == 1) {
        $row = $result->fetch();

        if ($row['futureYearsLogin'] != 'Y' and $row['pastYearsLogin'] != 'Y') { //NOT ALLOWED DUE TO CONTROLS ON ROLE, KICK OUT!
            $URL = Url::fromRoute()->withReturn('error0');
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
                $URL = Url::fromRoute()->withReturn('error0');
                header("Location: {$URL}");
                exit;
            }
            //Else get year details
            else {
                $rowYear = $resultYear->fetch();
                $rowYearCurrent = $resultYearCurrent->fetch();
                if ($row['futureYearsLogin'] != 'Y' and $rowYearCurrent['sequenceNumber'] < $rowYear['sequenceNumber']) { //POSSIBLY NOT ALLOWED DUE TO CONTROLS ON ROLE, CHECK YEAR
                    $URL = Url::fromRoute()->withReturn('error0');
                    header("Location: {$URL}");
                    exit();
                } elseif ($row['pastYearsLogin'] != 'Y' and $rowYearCurrent['sequenceNumber'] > $rowYear['sequenceNumber']) { //POSSIBLY NOT ALLOWED DUE TO CONTROLS ON ROLE, CHECK YEAR
                    $URL = Url::fromRoute()->withReturn('error0');
                    header("Location: {$URL}");
                    exit();
                } else { //ALLOWED
                    $session->set('gibbonSchoolYearID', $rowYear['gibbonSchoolYearID']);
                    $session->set('gibbonSchoolYearName', $rowYear['name']);
                    $session->set('gibbonSchoolYearSequenceNumber', $rowYear['sequenceNumber']);
                    $session->set('gibbonSchoolYearFirstDay', $rowYear['firstDay']);
                    $session->set('gibbonSchoolYearLastDay', $rowYear['lastDay']);

                    // Clear cached FF actions
                    $session->forget('fastFinderActions');

                    // Clear the main menu from session cache
                    $session->forget('menuMainItems');

                    $URL = Url::fromRoute()->withReturn('success0');
                    header("Location: {$URL}");
                }
            }
        }
    }
}
