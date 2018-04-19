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
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Staff/applicationForm_manage_edit.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    echo '<h2>';
    echo __($guid, 'Staff Application Form Printout');
    echo '</h2>';

    $gibbonStaffApplicationFormID = $_GET['gibbonStaffApplicationFormID'];
    $search = '';
    if (isset($_GET['search'])) {
        $search = $_GET['search'];
    }

    if ($gibbonStaffApplicationFormID == '') {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        //Proceed!
        try {
            $data = array('gibbonStaffApplicationFormID' => $gibbonStaffApplicationFormID);
            $sql = 'SELECT gibbonStaffApplicationForm.*, gibbonStaffJobOpening.jobTitle, gibbonStaffJobOpening.type FROM gibbonStaffApplicationForm JOIN gibbonStaffJobOpening ON (gibbonStaffApplicationForm.gibbonStaffJobOpeningID=gibbonStaffJobOpening.gibbonStaffJobOpeningID) LEFT JOIN gibbonPerson ON (gibbonStaffApplicationForm.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonStaffApplicationFormID=:gibbonStaffApplicationFormID';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        if ($result->rowCount() != 1) {
            echo "<div class='error'>";
            echo __($guid, 'There is no data to display, or an error has occurred.');
            echo '</div>';
        } else {
            $row = $result->fetch();
            echo '<h4>'.__($guid, 'For Office Use').'</h4>';
            echo "<table cellspacing='0' style='width: 100%'>";
            echo '<tr>';
            echo "<td style='width: 25%; padding-top: 15px; vertical-align: top'>";
            echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Application ID').'</span><br/>';
            echo '<i>'.htmlPrep($row['gibbonStaffApplicationFormID']).'</i>';
            echo '</td>';
            echo "<td style='width: 25%; padding-top: 15px; vertical-align: top'>";
            echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Priority').'</span><br/>';
            echo '<i>'.htmlPrep($row['priority']).'</i>';
            echo '</td>';
            echo "<td style='width: 50%; padding-top: 15px; vertical-align: top'>";
            echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Status').'</span><br/>';
            echo '<i>'.htmlPrep($row['status']).'</i>';
            echo '</td>';
            echo '</tr>';
            echo '<tr>';
            echo "<td style='padding-top: 15px; vertical-align: top'>";
            echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Start Date').'</span><br/>';
            echo '<i>'.dateConvertBack($guid, $row['dateStart']).'</i>';
            echo '</td>';
            echo "<td style='padding-top: 15px; vertical-align: top'>";
            echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Milestones').'</span><br/>';
            echo '<i>'.htmlPrep($row['milestones']).'</i>';
            echo '</td>';
            echo "<td style='padding-top: 15px; vertical-align: top'>";

            echo '</td>';
            echo '</tr>';
            if ($row['notes'] != '') {
                echo '<tr>';
                echo "<td style='padding-top: 15px; vertical-align: top' colspan=3>";
                echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Notes').'</span><br/>';
                echo '<i>'.$row['notes'].'</i>';
                echo '</td>';
                echo '</tr>';
            }
            echo '</table>';

            echo '<h4>'.__($guid, 'Job Related Information').'</h4>';
            echo "<table cellspacing='0' style='width: 100%'>";
            echo '<tr>';
            echo "<td style='width: 33%; padding-top: 15px; vertical-align: top' colspan=2>";
            echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Job Opening').'</span><br/>';
            echo '<i>'.htmlPrep($row['jobTitle']).'</i>';
            echo '</td>';
            echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
            echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Job Type').'</span><br/>';
            echo '<i>'.htmlPrep($row['type']).'</i>';
            echo '</td>';
            echo '</tr>';
            echo '<tr>';
            echo "<td style='width: 33%; padding-top: 15px; vertical-align: top' colspan=3>";
            echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Application Questions').'</span><br/>';
            echo '<i>'.addSlashes($row['questions']).'</i>';
            echo '</td>';
            echo '</tr>';
            echo '</table>';

            echo '<h4>'.__($guid, 'Applicant Details').'</h4>';
            echo "<table cellspacing='0' style='width: 100%'>";
            if ($row['gibbonPersonID'] != '') {
                echo '<tr>';
                echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Internal Candidate').'</span><br/>';
                echo '</td>';
                echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Surname').'</span><br/>';
                echo '<i>'.htmlPrep($row['surname']).'</i>';
                echo '</td>';
                echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Preferred Name').'</span><br/>';
                echo '<i>'.htmlPrep($row['preferredName']).'</i>';
                echo '</td>';
                echo '</tr>';
            } else {
                echo '<tr>';
                echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Surname').'</span><br/>';
                echo '<i>'.htmlPrep($row['surname']).'</i>';
                echo '</td>';
                echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Preferred Name').'</span><br/>';
                echo '<i>'.htmlPrep($row['preferredName']).'</i>';
                echo '</td>';
                echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Official Name').'</span><br/>';
                echo '<i>'.htmlPrep($row['officialName']).'</i>';
                echo '</td>';
                echo '</tr>';
                echo '<tr>';
                echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Gender').'</span><br/>';
                echo '<i>'.htmlPrep($row['gender']).'</i>';
                echo '</td>';
                echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Date of Birth').'</span><br/>';
                echo '<i>'.dateConvertBack($guid, $row['dob']).'</i>';
                echo '</td>';
                echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";

                echo '</td>';
                echo '</tr>';
                echo '<tr>';
                echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'First Language').'</span><br/>';
                echo '<i>'.htmlPrep($row['languageFirst']).'</i>';
                echo '</td>';
                echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Second Language').'</span><br/>';
                echo '<i>'.htmlPrep($row['languageSecond']).'</i>';
                echo '</td>';
                echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Third Language').'</span><br/>';
                echo '<i>'.htmlPrep($row['languageThird']).'</i>';
                echo '</td>';
                echo '</tr>';
                echo '<tr>';
                echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Country of Birth').'</span><br/>';
                echo '<i>'.htmlPrep($row['countryOfBirth']).'</i>';
                echo '</td>';
                echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Citizenship').'</span><br/>';
                echo '<i>'.htmlPrep($row['citizenship1']).'</i>';
                echo '</td>';
                echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Passport Number').'</span><br/>';
                echo '<i>'.htmlPrep($row['citizenship1Passport']).'</i>';
                echo '</td>';
                echo '</tr>';
                echo '<tr>';
                echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                echo "<span style='font-size: 115%; font-weight: bold'>";
                if ($_SESSION[$guid]['country'] == '') {
                    echo '<b>'.__($guid, 'National ID Card Number').'</b>';
                } else {
                    echo '<b>'.$_SESSION[$guid]['country'].' '.__($guid, 'ID Card Number').'</b>';
                }
                echo '</span><br/>';
                echo '<i>'.htmlPrep($row['nationalIDCardNumber']).'</i>';
                echo '</td>';
                echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                echo "<span style='font-size: 115%; font-weight: bold'>";
                if ($_SESSION[$guid]['country'] == '') {
                    echo '<b>'.__($guid, 'Residency/Visa Type').'</b>';
                } else {
                    echo '<b>'.$_SESSION[$guid]['country'].' '.__($guid, 'Residency/Visa Type').'</b>';
                }
                echo '</span><br/>';
                echo '<i>'.htmlPrep($row['residencyStatus']).'</i>';
                echo '</td>';
                echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                echo "<span style='font-size: 115%; font-weight: bold'>";
                if ($_SESSION[$guid]['country'] == '') {
                    echo '<b>'.__($guid, 'Visa Expiry Date').'</b>';
                } else {
                    echo '<b>'.$_SESSION[$guid]['country'].' '.__($guid, 'Visa Expiry Date').'</b>';
                }
                echo '</span><br/>';
                echo '<i>'.dateConvertBack($guid, $row['visaExpiryDate']).'</i>';
                echo '</td>';
                echo '</tr>';
                echo '<tr>';
                echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Email').'</span><br/>';
                echo '<i>'.htmlPrep($row['email']).'</i>';
                echo '</td>';
                echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Phone').'</span><br/>';
                echo '<i>';
                if ($row['phone1Type'] != '') {
                    echo htmlPrep($row['phone1Type']).': ';
                }
                if ($row['phone1CountryCode'] != '') {
                    echo htmlPrep($row['phone1CountryCode']).' ';
                }
                echo htmlPrep(formatPhone($row['phone1'])).' ';
                echo '</i>';
                echo '</td>';
                echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";

                echo '</td>';
                echo '</tr>';
            }
            echo '</table>';
        }
    }
}
