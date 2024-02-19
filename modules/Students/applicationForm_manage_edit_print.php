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

use Gibbon\Domain\System\SettingGateway;
use Gibbon\Services\Format;
use Gibbon\Domain\User\PersonalDocumentGateway;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Students/applicationForm_manage_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    echo '<h2>';
    echo __('Student Application Form Printout');
    echo '</h2>';

    $gibbonApplicationFormID = $_GET['gibbonApplicationFormID'] ?? '';
    $search = '';
    if (isset($_GET['search'])) {
        $search = $_GET['search'] ?? '';
    }

    if ($gibbonApplicationFormID == '') {
        $page->addError(__('You have not specified one or more required parameters.'));
    } else {
        //Proceed!
        $settingGateway = $container->get(SettingGateway::class);
        
        $data = array('gibbonApplicationFormID' => $gibbonApplicationFormID);
        $sql = "SELECT * FROM gibbonApplicationForm LEFT JOIN gibbonPayment ON (gibbonApplicationForm.gibbonPaymentID=gibbonPayment.gibbonPaymentID AND foreignTable='gibbonApplicationForm') WHERE gibbonApplicationFormID=:gibbonApplicationFormID";
        $result = $connection2->prepare($sql);
        $result->execute($data);

        if ($result->rowCount() != 1) {
            echo "<div class='error'>";
            echo __('There is no data to display, or an error has occurred.');
            echo '</div>';
        } else {
            $row = $result->fetch();
            echo '<h4>'.__('For Office Use').'</h4>';
            echo "<table cellspacing='0' style='width: 100%'>";
            echo '<tr>';
            echo "<td style='width: 25%; padding-top: 15px; vertical-align: top'>";
            echo "<span style='font-size: 115%; font-weight: bold'>".__('Application ID').'</span><br/>';
            echo '<i>'.htmlPrep($row['gibbonApplicationFormID']).'</i>';
            echo '</td>';
            echo "<td style='width: 25%; padding-top: 15px; vertical-align: top'>";
            echo "<span style='font-size: 115%; font-weight: bold'>".__('Priority').'</span><br/>';
            echo '<i>'.htmlPrep($row['priority']).'</i>';
            echo '</td>';
            echo "<td style='width: 50%; padding-top: 15px; vertical-align: top'>";
            echo "<span style='font-size: 115%; font-weight: bold'>".__('Status').'</span><br/>';
            echo '<i>'.htmlPrep($row['status']).'</i>';
            echo '</td>';
            echo '</tr>';
            echo '<tr>';
            echo "<td style='padding-top: 15px; vertical-align: top'>";
            echo "<span style='font-size: 115%; font-weight: bold'>".__('Start Date').'</span><br/>';
            echo '<i>'.Format::date($row['dateStart']).'</i>';
            echo '</td>';
            echo "<td style='padding-top: 15px; vertical-align: top'>";
            echo "<span style='font-size: 115%; font-weight: bold'>".__('Year of Entry').'</span><br/>';
            
                $dataSelect = array('gibbonSchoolYearIDEntry' => $row['gibbonSchoolYearIDEntry']);
                $sqlSelect = 'SELECT name FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearIDEntry';
                $resultSelect = $connection2->prepare($sqlSelect);
                $resultSelect->execute($dataSelect);
            if ($resultSelect->rowCount() == 1) {
                $rowSelect = $resultSelect->fetch();
                echo '<i>'.$rowSelect['name'].'</i>';
            }
            echo '</td>';
            echo "<td style='padding-top: 15px; vertical-align: top'>";
            echo "<span style='font-size: 115%; font-weight: bold'>".__('Year Group at Entry').'</span><br/>';
            
                $dataSelect = array('gibbonYearGroupIDEntry' => $row['gibbonYearGroupIDEntry']);
                $sqlSelect = 'SELECT name FROM gibbonYearGroup WHERE gibbonYearGroupID=:gibbonYearGroupIDEntry';
                $resultSelect = $connection2->prepare($sqlSelect);
                $resultSelect->execute($dataSelect);
            if ($resultSelect->rowCount() == 1) {
                $rowSelect = $resultSelect->fetch();
                echo '<i>'.__($rowSelect['name']);
                $dayTypeOptions = $settingGateway->getSettingByScope('User Admin', 'dayTypeOptions');
                if ($dayTypeOptions != '') {
                    echo ' ('.$row['dayType'].')';
                }
                echo '</i>';
            }
            echo '</td>';
            echo '</tr>';
            echo '<tr>';
            echo "<td style='padding-top: 15px; vertical-align: top'>";
            echo "<span style='font-size: 115%; font-weight: bold'>".__('Form Group at Entry').'</span><br/>';
            
                $dataSelect = array('gibbonFormGroupID' => $row['gibbonFormGroupID']);
                $sqlSelect = 'SELECT name FROM gibbonFormGroup WHERE gibbonFormGroupID=:gibbonFormGroupID';
                $resultSelect = $connection2->prepare($sqlSelect);
                $resultSelect->execute($dataSelect);
            if ($resultSelect->rowCount() == 1) {
                $rowSelect = $resultSelect->fetch();
                echo '<i>'.$rowSelect['name'].'</i>';
            }
            echo '</td>';
            echo "<td style='padding-top: 15px; vertical-align: top'>";
            echo "<span style='font-size: 115%; font-weight: bold'>".__('Milestones').'</span><br/>';
            echo '<i>'.htmlPrep($row['milestones']).'</i>';
            echo '</td>';
            echo "<td style='padding-top: 15px; vertical-align: top'>";
            $currency = $settingGateway->getSettingByScope('System', 'currency');
            $applicationFee = $settingGateway->getSettingByScope('Application Form', 'applicationFee');
            if ($applicationFee > 0 and is_numeric($applicationFee)) {
                echo "<span style='font-size: 115%; font-weight: bold'>Payment</span><br/>";
                echo '<i>'.htmlPrep($row['paymentMade']).'</i><br/>';
                if ($row['paymentToken'] != '' or $row['paymentPayerID'] != '' or $row['paymentTransactionID'] != '' or $row['paymentReceiptID'] != '') {
                    if ($row['paymentToken'] != '') {
                        echo __('Payment Token:').' '.$row['paymentToken'].'<br/>';
                    }
                    if ($row['paymentPayerID'] != '') {
                        echo __('Payment Payer ID:').' '.$row['paymentPayerID'].'<br/>';
                    }
                    if ($row['paymentTransactionID'] != '') {
                        echo __('Payment Transaction ID:').' '.$row['paymentTransactionID'].'<br/>';
                    }
                    if ($row['paymentReceiptID'] != '') {
                        echo __('Payment Receipt ID:').' '.$row['paymentReceiptID'].'<br/>';
                    }
                }
            }
            echo '</td>';
            echo '</tr>';
            if ($row['notes'] != '') {
                echo '<tr>';
                echo "<td style='padding-top: 15px; vertical-align: top' colspan=3>";
                echo "<span style='font-size: 115%; font-weight: bold'>".__('Notes').'</span><br/>';
                echo '<i>'.$row['notes'].'</i>';
                echo '</td>';
                echo '</tr>';
            }
            echo '</table>';

            echo '<h4>'.__('Student Details').'</h4>';
            echo "<table cellspacing='0' style='width: 100%'>";
            echo '<tr>';
            echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
            echo "<span style='font-size: 115%; font-weight: bold'>".__('Surname').'</span><br/>';
            echo '<i>'.htmlPrep($row['surname']).'</i>';
            echo '</td>';
            echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
            echo "<span style='font-size: 115%; font-weight: bold'>".__('Preferred Name').'</span><br/>';
            echo '<i>'.htmlPrep($row['preferredName']).'</i>';
            echo '</td>';
            echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
            echo "<span style='font-size: 115%; font-weight: bold'>".__('Official Name').'</span><br/>';
            echo '<i>'.htmlPrep($row['officialName']).'</i>';
            echo '</td>';
            echo '</tr>';
            echo '<tr>';
            echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
            echo "<span style='font-size: 115%; font-weight: bold'>".__('Gender').'</span><br/>';
            echo '<i>'.htmlPrep($row['gender']).'</i>';
            echo '</td>';
            echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
            echo "<span style='font-size: 115%; font-weight: bold'>".__('Date of Birth').'</span><br/>';
            echo '<i>'.Format::date($row['dob']).'</i>';
            echo '</td>';
            echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
            echo "<span style='font-size: 115%; font-weight: bold'>".__('Current/Last School').'</span><br/>';
            $school = '';
            if ($row['schoolDate1'] > $row['schoolDate2'] and $row['schoolName1'] != '') {
                $school = $row['schoolName1'];
            } elseif ($row['schoolDate2'] > $row['schoolDate1'] and $row['schoolName2'] != '') {
                $school = $row['schoolName2'];
            } elseif ($row['schoolName1'] != '') {
                $school = $row['schoolName1'];
            }
            if ($school != '') {
                if (strlen($school) <= 15) {
                    echo '<i>'.htmlPrep($school).'</i>';
                } else {
                    echo "<i><span title='".$school."'>".substr($school, 0, 15).'...</span></i>';
                }
            } else {
                echo '<i>'.__('Unspecified').'</i>';
            }
            echo '</td>';
            echo '</tr>';
            echo '<tr>';
            echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
            echo "<span style='font-size: 115%; font-weight: bold'>".__('Home Languages').'</span><br/>';
            if ($row['languageHomePrimary'] != '') {
                echo '<i>'.htmlPrep($row['languageHomePrimary']).'</i><br/>';
            }
            if ($row['languageHomeSecondary'] != '') {
                echo '<i>'.htmlPrep($row['languageHomeSecondary']).'</i><br/>';
            }
            echo '</td>';
            echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
            echo "<span style='font-size: 115%; font-weight: bold'>".__('First Language').'</span><br/>';
            echo '<i>'.htmlPrep($row['languageFirst']).'</i>';
            echo '</td>';
            echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
            echo "<span style='font-size: 115%; font-weight: bold'>".__('Second Language').'</span><br/>';
            echo '<i>'.htmlPrep($row['languageSecond']).'</i>';
            echo '</td>';
            echo '</tr>';
            echo '<tr>';
            echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
            echo "<span style='font-size: 115%; font-weight: bold'>".__('Email').'</span><br/>';
            echo '<i>'.htmlPrep($row['email']).'</i>';
            echo '</td>';
            echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
            echo "<span style='font-size: 115%; font-weight: bold'>".__('Phone').'</span><br/>';
            echo '<i>';
            if ($row['phone1Type'] != '') {
                echo htmlPrep($row['phone1Type']).': ';
            }
            if ($row['phone1CountryCode'] != '') {
                echo htmlPrep($row['phone1CountryCode']).' ';
            }
            echo htmlPrep(Format::phone($row['phone1'])).' ';
            echo '</i>';
            echo '</td>';
            echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";

            echo '</td>';
            echo '</tr>';
            if ($row['sen'] == 'Y') {
                echo '<tr>';
                echo "<td style='width: 33%; padding-top: 15px; vertical-align: top' colspan=3>";
                echo "<span style='font-size: 115%; font-weight: bold'>".__('Special Educational Needs').'</span><br/>';
                echo '<i>'.$row['senDetails'].'</i>';
                echo '</td>';
                echo '</tr>';
            }
            if ($row['medicalInformation'] != '') {
                echo '<tr>';
                echo "<td style='width: 33%; padding-top: 15px; vertical-align: top' colspan=3>";
                echo "<span style='font-size: 115%; font-weight: bold'>".__('Medical Information').'</span><br/>';
                echo '<i>'.$row['medicalInformation'].'</i>';
                echo '</td>';
                echo '</tr>';
            }
            echo '</table>';

            $params = ['student' => true, 'notEmpty' => true];
            $documents = $container->get(PersonalDocumentGateway::class)->selectPersonalDocuments('gibbonApplicationForm', $gibbonApplicationFormID, $params)->fetchAll();

            echo $page->fetchFromTemplate('ui/personalDocuments.twig.html', ['documents' => $documents]);

            echo '<h4>'.__('Parents/Guardians').'</h4>';
            //No family in Gibbon
            if ($row['gibbonFamilyID'] == '') {
                echo "<table cellspacing='0' style='width: 100%'>";
                echo '<tr>';
                echo "<td style='padding-top: 15px; vertical-align: top' colspan=3>";
                echo "<span style='font-size: 115%; font-weight: bold'>".__('Home Address').'</span><br/>';
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
                    
                        $dataMember = array('gibbonPersonID' => $row['parent1gibbonPersonID']);
                        $sqlMember = 'SELECT * FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonID';
                        $resultMember = $connection2->prepare($sqlMember);
                        $resultMember->execute($dataMember);

                    while ($rowMember = $resultMember->fetch()) {
                        echo '<h5 class="mt-4">'.__('Parent 1').'</h5>';

                        echo "<table cellspacing='0' style='width: 100%'>";
                        echo '<tr>';
                        echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__('Parent 1 Name').'</span><br/>';
                        echo Format::name($rowMember['title'], $rowMember['preferredName'], $rowMember['surname'], 'Parent');
                        echo '</td>';
                        echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__('Parent 1 Relationship').'</span><br/>';
                        echo $row['parent1relationship'];
                        echo '</td>';
                        echo "<td style='padding-top: 15px; width: 34%; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__('Parent 1 Contact Priority').'</span><br/>';
                        echo '1';
                        echo '</td>';
                        echo '</tr>';
                        echo '<tr>';
                        echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__('Parent 1 First Language').'</span><br/>';
                        echo $rowMember['languageFirst'];
                        echo '</td>';
                        echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__('Parent 1 Phone').'</span><br/>';
                        if ($rowMember['phone1'] != '' or $rowMember['phone2'] != '' or $rowMember['phone3'] != '' or $rowMember['phone4'] != '') {
                            for ($i = 1; $i < 5; ++$i) {
                                if ($rowMember['phone'.$i] != '') {
                                    if ($rowMember['phone'.$i.'Type'] != '') {
                                        echo '<i>'.$rowMember['phone'.$i.'Type'].':</i> ';
                                    }
                                    if ($rowMember['phone'.$i.'CountryCode'] != '') {
                                        echo '+'.$rowMember['phone'.$i.'CountryCode'].' ';
                                    }
                                    echo Format::phone($rowMember['phone'.$i]).'<br/>';
                                }
                            }
                        }
                        echo '</td>';
                        echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__('Parent 1 Email').'</span><br/>';
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
                        echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__('Parent 1 Second Language').'</span><br/>';
                        echo $rowMember['languageSecond'];
                        echo '</td>';
                        echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__('Parent 1 Profession').'</span><br/>';
                        echo $rowMember['profession'];
                        echo '</td>';
                        echo "<td style='padding-top: 15px; width: 34%; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__('Parent 1 Employer').'</span><br/>';
                        echo $rowMember['employer'];
                        echo '</td>';
                        echo '</tr>';
                        echo '</table>';
                    }

                    $params = ['parent' => true, 'notEmpty' => true];
                    $documents = $container->get(PersonalDocumentGateway::class)->selectPersonalDocuments('gibbonPerson', $row['parent1gibbonPersonID'], $params)->fetchAll();

                    echo $page->fetchFromTemplate('ui/personalDocuments.twig.html', ['documents' => $documents, 'noTitle' => true]);
                }
                //Parent 1 not in Gibbon
                else {
                    $start = 1;
                }
                for ($i = $start;$i < 3;++$i) {
                    //Spit out parent1/parent2 data from application, depending on parent1 status above.
                    echo '<h5 class="mt-4">'.($i > 1 ? __('Parent 2') : __('Parent 1')).'</h5>';

                    echo "<table cellspacing='0' style='width: 100%'>";
                    echo '<tr>';
                    echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                    echo "<span style='font-size: 115%; font-weight: bold'>".sprintf(__('Parent %1$s Name'), $i).'</span><br/>';
                    echo Format::name($row['parent'.$i.'title'], $row['parent'.$i.'preferredName'], $row['parent'.$i.'surname'], 'Parent');
                    echo '</td>';
                    echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                    echo "<span style='font-size: 115%; font-weight: bold'>".sprintf(__('Parent %1$s Relationship'), $i).'</span><br/>';
                    echo $row['parent'.$i.'relationship'];
                    echo '</td>';
                    echo "<td style='padding-top: 15px; width: 34%; vertical-align: top'>";
                    echo "<span style='font-size: 115%; font-weight: bold'>".sprintf(__('Parent %1$s Contact Priority'), $i).'</span><br/>';
                    echo $i;
                    echo '</td>';
                    echo '</tr>';
                    echo '<tr>';
                    echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                    echo "<span style='font-size: 115%; font-weight: bold'>".sprintf(__('Parent %1$s First Language'), $i).'</span><br/>';
                    echo $row['parent'.$i.'languageFirst'];
                    echo '</td>';
                    echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                    echo "<span style='font-size: 115%; font-weight: bold'>".sprintf(__('Parent %1$s Phone'), $i).'</span><br/>';
                    if ($row['parent'.$i.'phone1'] != '' or $row['parent'.$i.'phone2'] != '') {
                        for ($n = 1; $n < 3; ++$n) {
                            if ($row['parent'.$i.'phone'.$n] != '') {
                                if ($row['parent'.$i.'phone'.$n.'Type'] != '') {
                                    echo '<i>'.$row['parent'.$i.'phone'.$n.'Type'].':</i> ';
                                }
                                if ($row['parent'.$i.'phone'.$n.'CountryCode'] != '') {
                                    echo '+'.$row['parent'.$i.'phone'.$n.'CountryCode'].' ';
                                }
                                echo Format::phone($row['parent'.$i.'phone'.$n]).'<br/>';
                            }
                        }
                    }
                    echo '</td>';
                    echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                    echo "<span style='font-size: 115%; font-weight: bold'>".sprintf(__('Parent %1$s Email'), $i).'</span><br/>';
                    if ($row['parent'.$i.'email'] != '') {
                        if ($row['parent'.$i.'email'] != '') {
                            echo "Email: <a href='mailto:".$row['parent'.$i.'email']."'>".$row['parent'.$i.'email'].'</a><br/>';
                        }
                        echo '<br/>';
                    }
                    echo '</td>';
                    echo '</tr>';
                    echo '<tr>';
                    echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                    echo "<span style='font-size: 115%; font-weight: bold'>".sprintf(__('Parent %1$s Second Language'), $i).'</span><br/>';
                    echo $row['parent'.$i.'languageSecond'];
                    echo '</td>';
                    echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                    echo "<span style='font-size: 115%; font-weight: bold'>".sprintf(__('Parent %1$s Profession'), $i).'</span><br/>';
                    echo $row['parent'.$i.'profession'];
                    echo '</td>';
                    echo "<td style='padding-top: 15px; width: 34%; vertical-align: top'>";
                    echo "<span style='font-size: 115%; font-weight: bold'>".sprintf(__('Parent %1$s Employer'), $i).'</span><br/>';
                    echo $row['parent'.$i.'employer'];
                    echo '</td>';
                    echo '</tr>';
                    echo '</table>';

                    $params = ['parent' => true, 'notEmpty' => true];
                    $documents = $container->get(PersonalDocumentGateway::class)->selectPersonalDocuments('gibbonApplicationFormParent'.$i, $gibbonApplicationFormID, $params)->fetchAll();

                    echo $page->fetchFromTemplate('ui/personalDocuments.twig.html', ['documents' => $documents, 'noTitle' => true]);
                }
            }
            //Yes family
            else {
                //Spit out parent1/parent2 data from Gibbon
                
                    $dataFamily = array('gibbonFamilyID' => $row['gibbonFamilyID']);
                    $sqlFamily = 'SELECT * FROM gibbonFamily WHERE gibbonFamilyID=:gibbonFamilyID';
                    $resultFamily = $connection2->prepare($sqlFamily);
                    $resultFamily->execute($dataFamily);

                if ($resultFamily->rowCount() < 1) {
                    echo "<div class='error'>";
                    echo __('There is no family information available for the current student.');
                    echo '</div>';
                } else {
                    while ($rowFamily = $resultFamily->fetch()) {
                        $count = 1;
                        //Print family information
                        echo "<table cellspacing='0' style='width: 100%'>";
                        echo '<tr>';
                        echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__('Family Name').'</span><br/>';
                        echo $rowFamily['name'];
                        echo '</td>';
                        echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__('Family Status').'</span><br/>';
                        echo $rowFamily['status'];
                        echo '</td>';
                        echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__('Home Language').'</span><br/>';
                        echo $rowFamily['languageHomePrimary'].'<br/>';
                        echo $rowFamily['languageHomeSecondary'];
                        echo '</td>';
                        echo '</tr>';
                        echo '<tr>';
                        echo "<td style='padding-top: 15px; vertical-align: top' colspan=3>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__('Home Address').'</span><br/>';
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
                        
                            $dataMember = array('gibbonFamilyID' => $rowFamily['gibbonFamilyID']);
                            $sqlMember = 'SELECT * FROM gibbonFamilyAdult JOIN gibbonPerson ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonFamilyID=:gibbonFamilyID ORDER BY contactPriority, surname, preferredName';
                            $resultMember = $connection2->prepare($sqlMember);
                            $resultMember->execute($dataMember);

                        while ($rowMember = $resultMember->fetch()) {
                            echo '<h5 class="mt-4">'.($count > 1 ? __('Parent 2') : __('Parent 1')).'</h5>';

                            echo "<table cellspacing='0' style='width: 100%'>";
                            echo '<tr>';
                            echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                            echo "<span style='font-size: 115%; font-weight: bold'>".sprintf(__('Parent %1$s Name'), $count).'</span><br/>';
                            echo Format::name($rowMember['title'], $rowMember['preferredName'], $rowMember['surname'], 'Parent');
                            echo '</td>';
                            echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                            echo "<span style='font-size: 115%; font-weight: bold'>".sprintf(__('Parent %1$s Relationship'), $count).'</span><br/>';
                                            //This will not work and needs to be fixed. The relationship shown on edit page is a guestimate...whole form needs improving to allow specification of relationships in existing family...
                                            echo $row['parent1relationship'];
                            echo '</td>';
                            echo "<td style='padding-top: 15px; width: 34%; vertical-align: top' colspan=2>";
                            echo "<span style='font-size: 115%; font-weight: bold'>".sprintf(__('Parent %1$s Contact Priority'), $count).'</span><br/>';
                            echo $rowMember['contactPriority'];
                            echo '</td>';
                            echo '</tr>';
                            echo '<tr>';
                            echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                            echo "<span style='font-size: 115%; font-weight: bold'>".sprintf(__('Parent %1$s First Language'), $count).'</span><br/>';
                            echo $rowMember['languageFirst'];
                            echo '</td>';
                            echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                            echo "<span style='font-size: 115%; font-weight: bold'>".sprintf(__('Parent %1$s Phone'), $count).'</span><br/>';
                            if ($rowMember['contactCall'] == 'N') {
                                echo __('Do not contact by phone.');
                            } elseif ($rowMember['contactCall'] == 'Y' and ($rowMember['phone1'] != '' or $rowMember['phone2'] != '' or $rowMember['phone3'] != '' or $rowMember['phone4'] != '')) {
                                for ($i = 1; $i < 5; ++$i) {
                                    if ($rowMember['phone'.$i] != '') {
                                        if ($rowMember['phone'.$i.'Type'] != '') {
                                            echo '<i>'.$rowMember['phone'.$i.'Type'].':</i> ';
                                        }
                                        if ($rowMember['phone'.$i.'CountryCode'] != '') {
                                            echo '+'.$rowMember['phone'.$i.'CountryCode'].' ';
                                        }
                                        echo Format::phone($rowMember['phone'.$i]).'<br/>';
                                    }
                                }
                            }
                            echo '</td>';
                            echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                            echo "<span style='font-size: 115%; font-weight: bold'>".sprintf(__('Parent %1$s By Email'), $count).'</span><br/>';
                            if ($rowMember['contactEmail'] == 'N') {
                                echo __('Do not contact by email.');
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
                            echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                            echo "<span style='font-size: 115%; font-weight: bold'>".sprintf(__('Parent %1$s Second Language'), $count).'</span><br/>';
                            echo $rowMember['languageSecond'];
                            echo '</td>';
                            echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                            echo "<span style='font-size: 115%; font-weight: bold'>".sprintf(__('Parent %1$s Profession'), $count).'</span><br/>';
                            echo $rowMember['profession'];
                            echo '</td>';
                            echo "<td style='padding-top: 15px; width: 34%; vertical-align: top'>";
                            echo "<span style='font-size: 115%; font-weight: bold'>".sprintf(__('Parent %1$s Employer'), $count).'</span><br/>';
                            echo $rowMember['employer'];
                            echo '</td>';
                            echo '</tr>';
                            echo '</table>';

                            $params = ['parent' => true, 'notEmpty' => true];
                            $documents = $container->get(PersonalDocumentGateway::class)->selectPersonalDocuments('gibbonPerson', $rowMember['gibbonPersonID'], $params)->fetchAll();

                            echo $page->fetchFromTemplate('ui/personalDocuments.twig.html', ['documents' => $documents, 'noTitle' => true]);

                            ++$count;
                        }
                    }
                }
            }

            $siblingCount = 0;
            echo '<h4>Siblings</h4>';
            echo "<table cellspacing='0' style='width: 100%'>";
                //Get siblings from the application
                for ($i = 1; $i < 4; ++$i) {
                    if ($row["siblingName$i"] != '' or $row["siblingDOB$i"] != '' or $row["siblingSchool$i"] != '') {
                        ++$siblingCount;
                        echo '<tr>';
                        echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".sprintf(__('Sibling %1$s Name'), $siblingCount).'</span><br/>';
                        echo '<i>'.htmlPrep($row["siblingName$i"]).'</i>';
                        echo '</td>';
                        echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".sprintf(__('Sibling %1$s Date of Birth'), $siblingCount).'</span><br/>';
                        echo '<i>'.Format::date($row["siblingDOB$i"]).'</i>';
                        echo '</td>';
                        echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".sprintf(__('Sibling %1$s School'), $siblingCount).'</span><br/>';
                        echo '<i>'.htmlPrep($row["siblingSchool$i"]).'</i>';
                        echo '</td>';
                        echo '</tr>';
                    }
                }
                //Get siblings from Gibbon family
                if ($row['gibbonFamilyID'] != '') {
                    
                        $dataMember = array('gibbonFamilyID' => $row['gibbonFamilyID']);
                        $sqlMember = 'SELECT * FROM gibbonFamilyChild JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) WHERE gibbonFamilyID=:gibbonFamilyID ORDER BY surname, preferredName';
                        $resultMember = $connection2->prepare($sqlMember);
                        $resultMember->execute($dataMember);

                    if ($resultMember->rowCount() > 0) {
                        while ($rowMember = $resultMember->fetch()) {
                            ++$siblingCount;
                            echo '<tr>';
                            echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                            echo "<span style='font-size: 115%; font-weight: bold'>".sprintf(__('Sibling %1$s Name'), $siblingCount).'</span><br/>';
                            echo Format::name($rowMember['title'], $rowMember['preferredName'], $rowMember['surname'], $rowMember['category']);
                            echo '</td>';
                            echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                            echo "<span style='font-size: 115%; font-weight: bold'>".sprintf(__('Sibling %1$s Date of Birth'), $siblingCount).'</span><br/>';
                            echo '<i>'.Format::date($rowMember['dob']).'</i>';
                            echo '</td>';
                            echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                            echo "<span style='font-size: 115%; font-weight: bold'>".sprintf(__('Sibling %1$s School'), $siblingCount).'</span><br/>';
                            echo '<i>'.$session->get('organisationName').'</i>';
                            echo '</td>';
                            echo '</tr>';
                        }
                    }
                }

            if ($siblingCount < 1) {
                echo '<tr>';
                echo "<td style='width: 33%; padding-top: 15px; vertical-align: top' colspan=3>";
                echo "<div class='warning' style='margin-top: 0px'>";
                echo __('No known siblings');
                echo '</div>';
                echo '</td>';
                echo '</tr>';
            }
            echo '</table>';
        }
    }
}
