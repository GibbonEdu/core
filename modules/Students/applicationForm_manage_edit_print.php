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

if (isActionAccessible($guid, $connection2, '/modules/Students/applicationForm_manage_edit.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    echo '<h2>';
    echo __($guid, 'Student Application Form Printout');
    echo '</h2>';

    $gibbonApplicationFormID = $_GET['gibbonApplicationFormID'];
    $search = '';
    if (isset($_GET['search'])) {
        $search = $_GET['search'];
    }

    if ($gibbonApplicationFormID == '') {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        //Proceed!
        try {
            $data = array('gibbonApplicationFormID' => $gibbonApplicationFormID);
            $sql = "SELECT * FROM gibbonApplicationForm LEFT JOIN gibbonPayment ON (gibbonApplicationForm.gibbonPaymentID=gibbonPayment.gibbonPaymentID AND foreignTable='gibbonApplicationForm') WHERE gibbonApplicationFormID=:gibbonApplicationFormID";
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
            ?>
            <style>
            .page-break-avoid {
                page-break-inside: avoid;
            }

            .print-table {
                width: 100%;
            }

            .print-table tr {
                page-break-inside: avoid;
            }

            .print-table td {
                page-break-inside: avoid;
                vertical-align: top;
                padding-top: 15px;
            }

            .print-table .label {
                font-size: 115%;
                font-weight: bold;
            }

            .print-table hr {
               margin-top: 24px;
               border-top: 1px solid #666666;
               border-bottom:0px;
            }

            .print-table hr:first-of-type {
                margin-top: 36px;
            }

            .print-table .checkbox {
                display: inline-block;
                margin-right: 10px;
                width: 20px;
                height: 20px;
                vertical-align: middle;
                background: #ffffff;
                border: 2px solid #666666;
            }

            h4 {
                page-break-after: avoid;
            }

            </style>
            <?php
            $row = $result->fetch();
            echo '<h4>'.__($guid, 'For Office Use').'</h4>';
            echo "<table class='print-table' cellspacing='0'>";
            echo '<tr>';
            echo "<td style='width: 25%;'>";
            echo "<span class='label'>".__($guid, 'Application ID').'</span><br/>';
            echo '<i>'.htmlPrep($row['gibbonApplicationFormID']).'</i>';
            echo '</td>';
            echo "<td style='width: 25%;'>";
            echo "<span class='label'>".__($guid, 'Priority').'</span><br/>';
            echo '<i>'.htmlPrep($row['priority']).'</i>';
            echo '</td>';
            echo "<td style='width: 50%;'>";
            echo "<span class='label'>".__($guid, 'Status').'</span><br/>';
            echo '<i>'.htmlPrep($row['status']).'</i>';
            echo '</td>';
            echo '</tr>';
            echo '<tr>';
            echo "<td>";
            echo "<span class='label'>".__($guid, 'Start Date').'</span><br/>';
            echo '<i>'.dateConvertBack($guid, $row['dateStart']).'</i>';
            echo '</td>';
            echo "<td>";
            echo "<span class='label'>".__($guid, 'Year of Entry').'</span><br/>';
            try {
                $dataSelect = array('gibbonSchoolYearIDEntry' => $row['gibbonSchoolYearIDEntry']);
                $sqlSelect = 'SELECT name FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearIDEntry';
                $resultSelect = $connection2->prepare($sqlSelect);
                $resultSelect->execute($dataSelect);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }
            if ($resultSelect->rowCount() == 1) {
                $rowSelect = $resultSelect->fetch();
                echo '<i>'.$rowSelect['name'].'</i>';
            }
            echo '</td>';
            echo "<td>";
            echo "<span class='label'>".__($guid, 'Year Group at Entry').'</span><br/>';
            try {
                $dataSelect = array('gibbonYearGroupIDEntry' => $row['gibbonYearGroupIDEntry']);
                $sqlSelect = 'SELECT name FROM gibbonYearGroup WHERE gibbonYearGroupID=:gibbonYearGroupIDEntry';
                $resultSelect = $connection2->prepare($sqlSelect);
                $resultSelect->execute($dataSelect);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }
            if ($resultSelect->rowCount() == 1) {
                $rowSelect = $resultSelect->fetch();
                echo '<i>'.__($guid, $rowSelect['name']);
                $dayTypeOptions = getSettingByScope($connection2, 'User Admin', 'dayTypeOptions');
                if ($dayTypeOptions != '') {
                    echo ' ('.$row['dayType'].')';
                }
                echo '</i>';
            }
            echo '</td>';
            echo '</tr>';
            echo '<tr>';
            echo "<td>";
            echo "<span class='label'>".__($guid, 'Roll Group at Entry').'</span><br/>';
            try {
                $dataSelect = array('gibbonRollGroupID' => $row['gibbonRollGroupID']);
                $sqlSelect = 'SELECT name FROM gibbonRollGroup WHERE gibbonRollGroupID=:gibbonRollGroupID';
                $resultSelect = $connection2->prepare($sqlSelect);
                $resultSelect->execute($dataSelect);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }
            if ($resultSelect->rowCount() == 1) {
                $rowSelect = $resultSelect->fetch();
                echo '<i>'.$rowSelect['name'].'</i>';
            }
            echo '</td>';
            echo "<td>";
            echo "<span class='label'>".__($guid, 'Milestones').'</span><br/>';
            echo '<i>'.htmlPrep($row['milestones']).'</i>';
            echo '</td>';
            echo "<td>";
            $currency = getSettingByScope($connection2, 'System', 'currency');
            $applicationFee = getSettingByScope($connection2, 'Application Form', 'applicationFee');
            if ($applicationFee > 0 and is_numeric($applicationFee)) {
                echo "<span class='label'>Payment</span><br/>";
                echo '<i>'.htmlPrep($row['paymentMade']).'</i><br/>';
                if ($row['paymentToken'] != '' or $row['paymentPayerID'] != '' or $row['paymentTransactionID'] != '' or $row['paymentReceiptID'] != '') {
                    if ($row['paymentToken'] != '') {
                        echo __($guid, 'Payment Token:').' '.$row['paymentToken'].'<br/>';
                    }
                    if ($row['paymentPayerID'] != '') {
                        echo __($guid, 'Payment Payer ID:').' '.$row['paymentPayerID'].'<br/>';
                    }
                    if ($row['paymentTransactionID'] != '') {
                        echo __($guid, 'Payment Transaction ID:').' '.$row['paymentTransactionID'].'<br/>';
                    }
                    if ($row['paymentReceiptID'] != '') {
                        echo __($guid, 'Payment Receipt ID:').' '.$row['paymentReceiptID'].'<br/>';
                    }
                }
            }
            echo '</td>';
            echo '</tr>';
            if ($row['notes'] != '') {
                echo '<tr>';
                echo "<td colspan=3>";
                echo "<span class='label'>".__($guid, 'Notes').'</span><br/>';
                echo '<i>'.$row['notes'].'</i>';
                echo '</td>';
                echo '</tr>';
            }
            echo '</table>';

            echo '<h4>'.__($guid, 'Student Details').'</h4>';
            echo "<table class='print-table' cellspacing='0' style='width: 100%'>";
            echo '<tr>';
            echo "<td style='width: 33%;'>";
            echo "<span class='label'>".__($guid, 'Surname').'</span><br/>';
            echo '<i>'.htmlPrep($row['surname']).'</i>';
            echo '</td>';
            echo "<td style='width: 33%;'>";
            echo "<span class='label'>".__($guid, 'Preferred Name').'</span><br/>';
            echo '<i>'.htmlPrep($row['preferredName']).'</i>';
            echo '</td>';
            echo "<td style='width: 33%;'>";
            echo "<span class='label'>".__($guid, 'Official Name').'</span><br/>';
            echo '<i>'.htmlPrep($row['officialName']).'</i>';
            echo '</td>';
            echo '</tr>';
            echo '<tr>';
            echo "<td style='width: 33%;'>";
            echo "<span class='label'>".__($guid, 'Gender').'</span><br/>';
            echo '<i>'.htmlPrep($row['gender']).'</i>';
            echo '</td>';
            echo "<td style='width: 33%;'>";
            echo "<span class='label'>".__($guid, 'Date of Birth').'</span><br/>';
            echo '<i>'.dateConvertBack($guid, $row['dob']).'</i>';
            echo '</td>';
            echo "<td style='width: 33%;'>";
            echo "<span class='label'>".__($guid, 'Current/Last School').'</span><br/>';
            $school = '';
            if ($row['schoolDate1'] > $row['schoolDate2'] and $row['schoolName1'] != '') {
                $school = $row['schoolName1'];
            } elseif ($row['schoolDate2'] > $row['schoolDate1'] and $row['schoolName2'] != '') {
                $school = $row['schoolName2'];
            } elseif ($row['schoolName1'] != '') {
                $school = $row['schoolName1'];
            }
            if ($school != '') {
                echo '<i>'.htmlPrep($school).'</i>';
            } else {
                echo '<i>'.__($guid, 'Unspecified').'</i>';
            }
            echo '</td>';
            echo '</tr>';
            echo '<tr>';
            echo "<td style='width: 33%;'>";
            echo "<span class='label'>".__($guid, 'Home Languages').'</span><br/>';
            if ($row['languageHomePrimary'] != '') {
                echo '<i>'.htmlPrep($row['languageHomePrimary']).'</i><br/>';
            }
            if ($row['languageHomeSecondary'] != '') {
                echo '<i>'.htmlPrep($row['languageHomeSecondary']).'</i><br/>';
            }
            echo '</td>';
            echo "<td style='width: 33%;'>";
            echo "<span class='label'>".__($guid, 'First Language').'</span><br/>';
            echo '<i>'.htmlPrep($row['languageFirst']).'</i>';
            echo '</td>';
            echo "<td style='width: 33%;'>";
            echo "<span class='label'>".__($guid, 'Second Language').'</span><br/>';
            echo '<i>'.htmlPrep($row['languageSecond']).'</i>';
            echo '</td>';
            echo '</tr>';
            echo '<tr>';
            echo "<td style='width: 33%;'>";
            echo "<span class='label'>".__($guid, 'Country of Birth').'</span><br/>';
            echo '<i>'.htmlPrep($row['countryOfBirth']).'</i>';
            echo '</td>';
            echo "<td style='width: 33%;'>";
            echo "<span class='label'>".__($guid, 'Citizenship').'</span><br/>';
            echo '<i>'.htmlPrep($row['citizenship1']).'</i>';
            echo '</td>';
            echo "<td style='width: 33%;'>";
            echo "<span class='label'>".__($guid, 'Passport Number').'</span><br/>';
            echo '<i>'.htmlPrep($row['citizenship1Passport']).'</i>';
            echo '</td>';
            echo '</tr>';
            echo '<tr>';
            echo "<td style='width: 33%;'>";
            echo "<span class='label'>";
            if ($_SESSION[$guid]['country'] == '') {
                echo '<b>'.__($guid, 'National ID Card Number').'</b>';
            } else {
                echo '<b>'.$_SESSION[$guid]['country'].' '.__($guid, 'ID Card Number').'</b>';
            }
            echo '</span><br/>';
            echo '<i>'.htmlPrep($row['nationalIDCardNumber']).'</i>';
            echo '</td>';
            echo "<td style='width: 33%;'>";
            echo "<span class='label'>";
            if ($_SESSION[$guid]['country'] == '') {
                echo '<b>'.__($guid, 'Residency/Visa Type').'</b>';
            } else {
                echo '<b>'.$_SESSION[$guid]['country'].' '.__($guid, 'Residency/Visa Type').'</b>';
            }
            echo '</span><br/>';
            echo '<i>'.htmlPrep($row['residencyStatus']).'</i>';
            echo '</td>';
            echo "<td style='width: 33%;'>";
            echo "<span class='label'>";
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
            echo "<td style='width: 33%;'>";
            echo "<span class='label'>".__($guid, 'Email').'</span><br/>';
            echo '<i>'.htmlPrep($row['email']).'</i>';
            echo '</td>';
            echo "<td style='width: 33%;'>";
            echo "<span class='label'>".__($guid, 'Phone').'</span><br/>';
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
            echo "<td style='width: 33%;'>";

            echo '</td>';
            echo '</tr>';
            if ($row['sen'] == 'Y') {
                echo '<tr>';
                echo "<td style='width: 33%;' colspan=3>";
                echo "<span class='label'>".__($guid, 'Special Educational Needs').'</span><br/>';
                echo '<i>'.$row['senDetails'].'</i>';
                echo '</td>';
                echo '</tr>';
            }
            if ($row['medicalInformation'] != '') {
                echo '<tr>';
                echo "<td style='width: 33%;' colspan=3>";
                echo "<span class='label'>".__($guid, 'Medical Information').'</span><br/>';
                echo '<i>'.$row['medicalInformation'].'</i>';
                echo '</td>';
                echo '</tr>';
            }
            echo '</table>';


            if (!empty($row['schoolName1']) || !empty($row['schoolName2'])) {
                echo '<h4>'.__($guid, 'Previous Schools').'</h4>';
                echo "<table class='print-table' cellspacing='0' style='width: 100%'>";

                for ($i = 1; $i <= 2; $i++) {
                    if (empty($row['schoolName'.$i])) continue;

                    echo '<tr>';
                    echo "<td style='width: 25%;'>";
                    echo "<span class='label'>".__($guid, 'School Name').'</span><br/>';
                    if (!empty($row['schoolName'.$i])) echo '<i>'.htmlPrep($row['schoolName'.$i]).'</i>';
                    echo '</td>';

                    echo "<td style='width: 30%;'>";
                    echo "<span class='label'>".__($guid, 'Address').'</span><br/>';
                    if (!empty($row['schoolAddress'.$i])) echo '<i>'.htmlPrep($row['schoolAddress'.$i]).'</i>';
                    echo '</td>';

                    echo "<td style='width: 15%;'>";
                    echo "<span class='label'>".__($guid, 'Grades Attended').'</span><br/>';
                    if (!empty($row['schoolGrades'.$i])) echo '<i>'.htmlPrep($row['schoolGrades'.$i]).'</i>';
                    echo '</td>';

                    echo "<td style='width: 15%;'>";
                    echo "<span class='label'>".__($guid, 'Language of Instruction').'</span><br/>';
                    if (!empty($row['schoolLanguage'.$i])) echo '<i>'.htmlPrep($row['schoolLanguage'.$i]).'</i>';
                    echo '</td>';

                    echo "<td style='width: 15%;'>";
                    echo "<span class='label'>".__($guid, 'Joining Date').'</span><br/><br/>';
                    if (!empty($row['schoolDate'.$i])) echo '<i>'.htmlPrep($row['schoolDate'.$i]).'</i>';
                    echo '</td>';
                    echo '</tr>';
                }


                echo '</table>';
            }

            echo '<h4>'.__($guid, 'Parents/Guardians').'</h4>';
            //No family in Gibbon
            if ($row['gibbonFamilyID'] == '') {
                echo "<table class='print-table' cellspacing='0' style='width: 100%'>";
                echo '<tr>';
                echo "<td colspan=3>";
                echo "<span class='label'>".__($guid, 'Home Address').'</span><br/>';
                if ($row['homeAddress'] != '') {
                    echo $row['homeAddress'].'<br/>';
                }
                if ($row['homeAddressDistrict'] != '') {
                    echo $row['homeAddressDistrict'].'<br/>';
                }
                if ($row['homeAddressCountry'] != '') {
                    echo $row['homeAddressCountry'].'<br/>';
                }
                echo '</td>';
                echo '</tr>';
                echo '</table>';

                //Parent 1 in Gibbon
                if ($row['parent1gibbonPersonID'] != '') {
                    $start = 2;

                    //Spit out parent 1 data from Gibbon
                    try {
                        $dataMember = array('gibbonPersonID' => $row['parent1gibbonPersonID']);
                        $sqlMember = 'SELECT * FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonID';
                        $resultMember = $connection2->prepare($sqlMember);
                        $resultMember->execute($dataMember);
                    } catch (PDOException $e) {
                        echo "<div class='error'>".$e->getMessage().'</div>';
                    }

                    while ($rowMember = $resultMember->fetch()) {
                        echo "<table class='print-table' cellspacing='0' style='width: 100%'>";
                        echo '<tr>';
                        echo "<td style='width: 33%;'>";
                        echo "<span class='label'>".__($guid, 'Parent 1 Name').'</span><br/>';
                        echo formatName($rowMember['title'], $rowMember['preferredName'], $rowMember['surname'], 'Parent');
                        echo '</td>';
                        echo "<td style='width: 33%;'>";
                        echo "<span class='label'>".__($guid, 'Parent 1 Relationship').'</span><br/>';
                        echo $row['parent1relationship'];
                        echo '</td>';
                        echo "<td style='width: 34%;'>";
                        echo "<span class='label'>".__($guid, 'Parent 1 Contact Priority').'</span><br/>';
                        echo '1';
                        echo '</td>';
                        echo '</tr>';
                        echo '<tr>';
                        echo "<td style='width: 33%;'>";
                        echo "<span class='label'>".__($guid, 'Parent 1 First Language').'</span><br/>';
                        echo $rowMember['languageFirst'];
                        echo '</td>';
                        echo "<td style='width: 33%;'>";
                        echo "<span class='label'>".__($guid, 'Parent 1 Phone').'</span><br/>';
                        if ($rowMember['phone1'] != '' or $rowMember['phone2'] != '' or $rowMember['phone3'] != '' or $rowMember['phone4'] != '') {
                            for ($i = 1; $i < 5; ++$i) {
                                if ($rowMember['phone'.$i] != '') {
                                    if ($rowMember['phone'.$i.'Type'] != '') {
                                        echo '<i>'.$rowMember['phone'.$i.'Type'].':</i> ';
                                    }
                                    if ($rowMember['phone'.$i.'CountryCode'] != '') {
                                        echo '+'.$rowMember['phone'.$i.'CountryCode'].' ';
                                    }
                                    echo formatPhone($rowMember['phone'.$i]).'<br/>';
                                }
                            }
                        }
                        echo '</td>';
                        echo "<td style='width: 33%;'>";
                        echo "<span class='label'>".__($guid, 'Parent 1 Email').'</span><br/>';
                        if ($rowMember['email'] != '' or $rowMember['emailAlternate'] != '') {
                            if ($rowMember['email'] != '') {
                                echo "Email: <a href='mailto:".$rowMember['email']."'>".$rowMember['email'].'</a><br/>';
                            }
                            if ($rowMember['emailAlternate'] != '') {
                                echo "Email 2: <a href='mailto:".$rowMember['emailAlternate']."'>".$rowMember['emailAlternate'].'</a><br/>';
                            }
                            echo '<br/>';
                        }
                        echo '</td>';
                        echo '</tr>';
                        echo '<tr>';
                        echo "<td style='width: 33%;'>";
                        echo "<span class='label'>".__($guid, 'Parent 1 Second Langage').'</span><br/>';
                        echo $rowMember['languageSecond'];
                        echo '</td>';
                        echo "<td style='width: 33%;'>";
                        echo "<span class='label'>".__($guid, 'Parent 1 Profession').'</span><br/>';
                        echo $rowMember['profession'];
                        echo '</td>';
                        echo "<td style='width: 34%;'>";
                        echo "<span class='label'>".__($guid, 'Parent 1 Employer').'</span><br/>';
                        echo $rowMember['employer'];
                        echo '</td>';
                        echo '</tr>';
                        echo '</table>';
                    }
                }
                //Parent 1 not in Gibbon
                else {
                    $start = 1;
                }
                for ($i = $start;$i < 3;++$i) {
                    //Spit out parent1/parent2 data from application, depending on parent1 status above.
                    echo "<table class='print-table' cellspacing='0' style='width: 100%'>";
                    echo '<tr>';
                    echo "<td style='width: 33%;'>";
                    echo "<span class='label'>".sprintf(__($guid, 'Parent %1$s Name'), $i).'</span><br/>';
                    echo formatName($row['parent'.$i.'title'], $row['parent'.$i.'preferredName'], $row['parent'.$i.'surname'], 'Parent');
                    echo '</td>';
                    echo "<td style='width: 33%;'>";
                    echo "<span class='label'>".sprintf(__($guid, 'Parent %1$s Relationship'), $i).'</span><br/>';
                    echo $row['parent'.$i.'relationship'];
                    echo '</td>';
                    echo "<td style='width: 34%;'>";
                    echo "<span class='label'>".sprintf(__($guid, 'Parent %1$s Contact Priority'), $i).'</span><br/>';
                    echo $i;
                    echo '</td>';
                    echo '</tr>';
                    echo '<tr>';
                    echo "<td style='width: 33%;'>";
                    echo "<span class='label'>".sprintf(__($guid, 'Parent %1$s First Language'), $i).'</span><br/>';
                    echo $row['parent'.$i.'languageFirst'];
                    echo '</td>';
                    echo "<td style='width: 33%;'>";
                    echo "<span class='label'>".sprintf(__($guid, 'Parent %1$s Phone'), $i).'</span><br/>';
                    if ($row['parent'.$i.'phone1'] != '' or $row['parent'.$i.'phone2'] != '') {
                        for ($n = 1; $n < 3; ++$n) {
                            if ($row['parent'.$i.'phone'.$n] != '') {
                                if ($row['parent'.$i.'phone'.$n.'Type'] != '') {
                                    echo '<i>'.$row['parent'.$i.'phone'.$n.'Type'].':</i> ';
                                }
                                if ($row['parent'.$i.'phone'.$n.'CountryCode'] != '') {
                                    echo '+'.$row['parent'.$i.'phone'.$n.'CountryCode'].' ';
                                }
                                echo formatPhone($row['parent'.$i.'phone'.$n]).'<br/>';
                            }
                        }
                    }
                    echo '</td>';
                    echo "<td style='width: 33%;'>";
                    echo "<span class='label'>".sprintf(__($guid, 'Parent %1$s Email'), $i).'</span><br/>';
                    if ($row['parent'.$i.'email'] != '') {
                        if ($row['parent'.$i.'email'] != '') {
                            echo "Email: <a href='mailto:".$row['parent'.$i.'email']."'>".$row['parent'.$i.'email'].'</a><br/>';
                        }
                        echo '<br/>';
                    }
                    echo '</td>';
                    echo '</tr>';
                    echo '<tr>';
                    echo "<td style='width: 33%;'>";
                    echo "<span class='label'>".sprintf(__($guid, 'Parent %1$s Second Langage'), $i).'</span><br/>';
                    echo $row['parent'.$i.'languageSecond'];
                    echo '</td>';
                    echo "<td style='width: 33%;'>";
                    echo "<span class='label'>".sprintf(__($guid, 'Parent %1$s Profession'), $i).'</span><br/>';
                    echo $row['parent'.$i.'profession'];
                    echo '</td>';
                    echo "<td style='width: 34%;'>";
                    echo "<span class='label'>".sprintf(__($guid, 'Parent %1$s Employer'), $i).'</span><br/>';
                    echo $row['parent'.$i.'employer'];
                    echo '</td>';
                    echo '</tr>';
                    echo '</table>';
                }
            }
            //Yes family
            else {
                //Spit out parent1/parent2 data from Gibbon
                try {
                    $dataFamily = array('gibbonFamilyID' => $row['gibbonFamilyID']);
                    $sqlFamily = 'SELECT * FROM gibbonFamily WHERE gibbonFamilyID=:gibbonFamilyID';
                    $resultFamily = $connection2->prepare($sqlFamily);
                    $resultFamily->execute($dataFamily);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }

                if ($resultFamily->rowCount() < 1) {
                    echo "<div class='error'>";
                    echo __($guid, 'There is no family information available for the current student.');
                    echo '</div>';
                } else {
                    while ($rowFamily = $resultFamily->fetch()) {
                        $count = 1;
                        //Print family information
                        echo "<table class='print-table' cellspacing='0' style='width: 100%'>";
                        echo '<tr>';
                        echo "<td style='width: 33%;'>";
                        echo "<span class='label'>".__($guid, 'Family Name').'</span><br/>';
                        echo $rowFamily['name'];
                        echo '</td>';
                        echo "<td style='width: 33%;'>";
                        echo "<span class='label'>".__($guid, 'Family Status').'</span><br/>';
                        echo $rowFamily['status'];
                        echo '</td>';
                        echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Home Language').'</span><br/>';
                        echo $rowFamily['languageHomePrimary'].'<br/>';
                        echo $rowFamily['languageHomeSecondary'];
                        echo '</td>';
                        echo '</tr>';
                        echo '<tr>';
                        echo "<td colspan=3>";
                        echo "<span class='label'>".__($guid, 'Home Address').'</span><br/>';
                        if ($rowFamily['homeAddress'] != '') {
                            echo $rowFamily['homeAddress'].'<br/>';
                        }
                        if ($rowFamily['homeAddressDistrict'] != '') {
                            echo $rowFamily['homeAddressDistrict'].'<br/>';
                        }
                        if ($rowFamily['homeAddressCountry'] != '') {
                            echo $rowFamily['homeAddressCountry'].'<br/>';
                        }
                        echo '</td>';
                        echo '</tr>';
                        echo '</table>';

                        //Get adults
                        try {
                            $dataMember = array('gibbonFamilyID' => $rowFamily['gibbonFamilyID']);
                            $sqlMember = 'SELECT * FROM gibbonFamilyAdult JOIN gibbonPerson ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonFamilyID=:gibbonFamilyID ORDER BY contactPriority, surname, preferredName';
                            $resultMember = $connection2->prepare($sqlMember);
                            $resultMember->execute($dataMember);
                        } catch (PDOException $e) {
                            echo "<div class='error'>".$e->getMessage().'</div>';
                        }

                        while ($rowMember = $resultMember->fetch()) {
                            echo "<table class='print-table' cellspacing='0' style='width: 100%'>";
                            echo '<tr>';
                            echo "<td style='width: 33%;'>";
                            echo "<span class='label'>".sprintf(__($guid, 'Parent %1$s Name'), $count).'</span><br/>';
                            echo formatName($rowMember['title'], $rowMember['preferredName'], $rowMember['surname'], 'Parent');
                            echo '</td>';
                            echo "<td style='width: 33%;'>";
                            echo "<span class='label'>".sprintf(__($guid, 'Parent %1$s Relationship'), $count).'</span><br/>';
                                            //This will not work and needs to be fixed. The relationship shown on edit page is a guestimate...whole form needs improving to allow specification of relationships in existing family...
                                            echo $row['parent1relationship'];
                            echo '</td>';
                            echo "<td style='width: 34%;' colspan=2>";
                            echo "<span class='label'>".sprintf(__($guid, 'Parent %1$s Contact Priority'), $count).'</span><br/>';
                            echo $rowMember['contactPriority'];
                            echo '</td>';
                            echo '</tr>';
                            echo '<tr>';
                            echo "<td style='width: 33%;'>";
                            echo "<span class='label'>".sprintf(__($guid, 'Parent %1$s First Language'), $count).'</span><br/>';
                            echo $rowMember['languageFirst'];
                            echo '</td>';
                            echo "<td style='width: 33%;'>";
                            echo "<span class='label'>".sprintf(__($guid, 'Parent %1$s Phone'), $count).'</span><br/>';
                            if ($rowMember['contactCall'] == 'N') {
                                echo __($guid, 'Do not contact by phone.');
                            } elseif ($rowMember['contactCall'] == 'Y' and ($rowMember['phone1'] != '' or $rowMember['phone2'] != '' or $rowMember['phone3'] != '' or $rowMember['phone4'] != '')) {
                                for ($i = 1; $i < 5; ++$i) {
                                    if ($rowMember['phone'.$i] != '') {
                                        if ($rowMember['phone'.$i.'Type'] != '') {
                                            echo '<i>'.$rowMember['phone'.$i.'Type'].':</i> ';
                                        }
                                        if ($rowMember['phone'.$i.'CountryCode'] != '') {
                                            echo '+'.$rowMember['phone'.$i.'CountryCode'].' ';
                                        }
                                        echo formatPhone($rowMember['phone'.$i]).'<br/>';
                                    }
                                }
                            }
                            echo '</td>';
                            echo "<td style='width: 33%;'>";
                            echo "<span class='label'>".sprintf(__($guid, 'Parent %1$s By Email'), $count).'</span><br/>';
                            if ($rowMember['contactEmail'] == 'N') {
                                echo __($guid, 'Do not contact by email.');
                            } elseif ($rowMember['contactEmail'] == 'Y' and ($rowMember['email'] != '' or $rowMember['emailAlternate'] != '')) {
                                if ($rowMember['email'] != '') {
                                    echo "Email: <a href='mailto:".$rowMember['email']."'>".$rowMember['email'].'</a><br/>';
                                }
                                if ($rowMember['emailAlternate'] != '') {
                                    echo "Email 2: <a href='mailto:".$rowMember['emailAlternate']."'>".$rowMember['emailAlternate'].'</a><br/>';
                                }
                                echo '<br/>';
                            }
                            echo '</td>';
                            echo '</tr>';
                            echo '<tr>';
                            echo "<td style='width: 33%;'>";
                            echo "<span class='label'>".sprintf(__($guid, 'Parent %1$s Second Langage'), $count).'</span><br/>';
                            echo $rowMember['languageSecond'];
                            echo '</td>';
                            echo "<td style='width: 33%;'>";
                            echo "<span class='label'>".sprintf(__($guid, 'Parent %1$s Profession'), $count).'</span><br/>';
                            echo $rowMember['profession'];
                            echo '</td>';
                            echo "<td style='width: 34%;'>";
                            echo "<span class='label'>".sprintf(__($guid, 'Parent %1$s Employer'), $count).'</span><br/>';
                            echo $rowMember['employer'];
                            echo '</td>';
                            echo '</tr>';
                            echo '</table>';
                            ++$count;
                        }
                    }
                }
            }

            $siblingCount = 0;
            echo '<h4>Siblings</h4>';
            echo "<table class='print-table' cellspacing='0' style='width: 100%'>";
                //Get siblings from the application
                for ($i = 1; $i < 4; ++$i) {
                    if ($row["siblingName$i"] != '' or $row["siblingDOB$i"] != '' or $row["siblingSchool$i"] != '') {
                        ++$siblingCount;
                        echo '<tr>';
                        echo "<td style='width: 33%;'>";
                        echo "<span class='label'>".sprintf(__($guid, 'Sibling %1$s Name'), $siblingCount).'</span><br/>';
                        echo '<i>'.htmlPrep($row["siblingName$i"]).'</i>';
                        echo '</td>';
                        echo "<td style='width: 33%;'>";
                        echo "<span class='label'>".sprintf(__($guid, 'Sibling %1$s Date of Birth'), $siblingCount).'</span><br/>';
                        echo '<i>'.dateConvertBack($guid, $row["siblingDOB$i"]).'</i>';
                        echo '</td>';
                        echo "<td style='width: 33%;'>";
                        echo "<span class='label'>".sprintf(__($guid, 'Sibling %1$s School'), $siblingCount).'</span><br/>';
                        echo '<i>'.htmlPrep($row["siblingSchool$i"]).'</i>';
                        echo '</td>';
                        echo '</tr>';
                    }
                }
                //Get siblings from Gibbon family
                if ($row['gibbonFamilyID'] != '') {
                    try {
                        $dataMember = array('gibbonFamilyID' => $row['gibbonFamilyID']);
                        $sqlMember = 'SELECT * FROM gibbonFamilyChild JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) WHERE gibbonFamilyID=:gibbonFamilyID ORDER BY surname, preferredName';
                        $resultMember = $connection2->prepare($sqlMember);
                        $resultMember->execute($dataMember);
                    } catch (PDOException $e) {
                        echo "<div class='error'>".$e->getMessage().'</div>';
                    }

                    if ($resultMember->rowCount() > 0) {
                        while ($rowMember = $resultMember->fetch()) {
                            ++$siblingCount;
                            echo '<tr>';
                            echo "<td style='width: 33%;'>";
                            echo "<span class='label'>".sprintf(__($guid, 'Sibling %1$s Name'), $siblingCount).'</span><br/>';
                            echo formatName($rowMember['title'], $rowMember['preferredName'], $rowMember['surname'], $rowMember['category']);
                            echo '</td>';
                            echo "<td style='width: 33%;'>";
                            echo "<span class='label'>".sprintf(__($guid, 'Sibling %1$s Date of Birth'), $siblingCount).'</span><br/>';
                            echo '<i>'.dateConvertBack($guid, $rowMember['dob']).'</i>';
                            echo '</td>';
                            echo "<td style='width: 33%;'>";
                            echo "<span class='label'>".sprintf(__($guid, 'Sibling %1$s School'), $siblingCount).'</span><br/>';
                            echo '<i>'.$_SESSION[$guid]['organisationName'].'</i>';
                            echo '</td>';
                            echo '</tr>';
                        }
                    }
                }

            if ($siblingCount < 1) {
                echo '<tr>';
                echo "<td style='width: 33%;' colspan=3>";
                echo "<div class='warning' style='margin-top: 0px'>";
                echo __($guid, 'No known siblings');
                echo '</div>';
                echo '</td>';
                echo '</tr>';
            }
            echo '</table>';

            // TIS OFFICE USE
            echo '<div class="page-break-avoid">';
            echo '<h4>'.__($guid, 'Test Results').'</h4>';
            echo "<table class='print-table' cellspacing='0' style='width: 100%;'>";
            echo '<tr>';
            echo "<td style='width: 33%;'>";
            echo "<span class='label'>".__($guid, 'Vocabulary').'</span><br/>';
            echo '<hr/>';
            echo '</td>';
            echo "<td style='width: 33%;'>";
            echo "<span class='label'>".__($guid, 'Reading').'</span><br/>';
            echo '<hr/>';
            echo '</td>';
            echo "<td style='width: 33%;'>";
            echo "<span class='label'>".__($guid, 'Maths').'</span><br/>';
            echo '<hr/>';
            echo '</td>';
            echo '</tr>';
            echo '</table>';

            echo '<h4>'.__($guid, 'Decision').'</h4>';
            echo "<table class='print-table' cellspacing='0' style='width: 100%;'>";
            echo '<tr>';
            echo "<td style='width: 25%;'>";
            echo "<span class='label'><span class='checkbox'></span>".__($guid, 'Accept').'</span><br/>';
            echo '</td>';
            echo "<td style='width: 25%;'>";
            echo "<span class='label'><span class='checkbox'></span>".__($guid, 'Decline').'</span><br/>';
            echo '</td>';
            echo "<td style='width: 30%;'>";
            echo "<span class='label'><span class='checkbox'></span>".__($guid, 'Conditional Acceptance').'</span><br/>';
            echo '</td>';
            echo "<td style='width: 25%;'>";
            echo "<span class='label'><span class='checkbox'></span>".__($guid, 'Waitlist').'</span><br/>';
            echo '</td>';
            echo '</tr>';
            echo '</table>';
            echo "<table class='print-table' cellspacing='0' style='width: 100%;'>";
            echo '<tr>';
            echo "<td colspan=3>";
            echo "<span class='label'>".__($guid, 'Notes').'</span><br/>';
            echo '<hr/><hr/><hr/><hr/><hr/><hr/>';
            echo '</td>';
            echo '</tr>';
            echo '<tr>';
            echo "<td style='width: 33%;'>";
            echo "<span class='label'>".__($guid, 'Homeroom').'</span><br/>';
            echo '<hr/>';
            echo '</td>';
            echo "<td colspan='2'>";
            echo "<span class='label'>".__($guid, 'Teacher').'</span><br/>';
            echo '<hr/>';
            echo '</td>';
            echo '</tr>';
            echo '<tr>';
            echo "<td  colspan='2'>";
            echo "<span class='label'>".__($guid, 'Admin Signature').'</span><br/>';
            echo '<hr/>';
            echo '</td>';
            echo "<td >";
            echo "<span class='label'>".__($guid, 'Date').'</span><br/>';
            echo '<hr/>';
            echo '</td>';
            echo '</tr>';
            echo '</table>';
            echo '</div>';
            // END TIS OFFICE USE

        }
    }
}
