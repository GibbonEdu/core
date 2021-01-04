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
use Gibbon\Services\Format;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Activities/activities_view.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        echo "<div class='error'>";
        echo __('The highest grouped action cannot be determined.');
        echo '</div>';
    } else {
        $page->breadcrumbs->add(__('View Activities'));

        if (isset($_GET['return'])) {
            returnProcess($guid, $_GET['return'], null, array('success0' => __('Registration was successful.'), 'success1' => __('Unregistration was successful.'), 'success2' => __('Registration was successful, but the activity is full, so you are on the waiting list.')));
        }

        //Get current role category
        $roleCategory = getRoleCategory($_SESSION[$guid]['gibbonRoleIDCurrent'], $connection2);

        //Check access controls
        $access = getSettingByScope($connection2, 'Activities', 'access');
        $hideExternalProviderCost = getSettingByScope($connection2, 'Activities', 'hideExternalProviderCost');

        if (!($access == 'View' or $access == 'Register')) {
            echo "<div class='error'>";
            echo __('Activity listing is currently closed.');
            echo '</div>';
        } else {
            if ($access == 'View') {
                echo "<div class='warning'>";
                echo __('Registration is currently closed, but you can still view activities.');
                echo '</div>';
            }

            $disableExternalProviderSignup = getSettingByScope($connection2, 'Activities', 'disableExternalProviderSignup');
            if ($disableExternalProviderSignup == 'Y') {
                echo "<div class='warning'>";
                echo __('Please check activity details for instructions on how to register for activities offered by outside providers.');
                echo '</div>';
            }

            //If student, set gibbonPersonID to self
            if ($roleCategory == 'Student' and $highestAction == 'View Activities_studentRegister') {
                $gibbonPersonID = $_SESSION[$guid]['gibbonPersonID'];
            }
            //IF PARENT, SET UP LIST OF CHILDREN
            $countChild = 0;
            if ($roleCategory == 'Parent' and $highestAction == 'View Activities_studentRegisterByParent') {
                $gibbonPersonID = $_GET['gibbonPersonID'] ?? '';
                
                    $data = array('gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                    $sql = "SELECT * FROM gibbonFamilyAdult WHERE gibbonPersonID=:gibbonPersonID AND childDataAccess='Y'";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);

                if ($result->rowCount() < 1) {
                    echo "<div class='error'>";
                    echo __('Access denied.');
                    echo '</div>';
                } else {
                    $options = array();
                    while ($row = $result->fetch()) {
                        
                            $dataChild = array('gibbonFamilyID' => $row['gibbonFamilyID'], 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                            $sqlChild = "SELECT * FROM gibbonFamilyChild JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE gibbonFamilyID=:gibbonFamilyID AND gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL OR dateEnd>='".date('Y-m-d')."') AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY surname, preferredName ";
                            $resultChild = $connection2->prepare($sqlChild);
                            $resultChild->execute($dataChild);
                        if ($resultChild->rowCount() > 0) {
                            if ($resultChild->rowCount() == 1) {
                                $rowChild = $resultChild->fetch();
                                $gibbonPersonID = $rowChild['gibbonPersonID'];
                                $options[$rowChild['gibbonPersonID']] = Format::name('', $rowChild['preferredName'], $rowChild['surname'], 'Student', true);
                                ++$countChild;
                            }
                            else {
                                while ($rowChild = $resultChild->fetch()) {
                                    $options[$rowChild['gibbonPersonID']] = Format::name('', $rowChild['preferredName'], $rowChild['surname'], 'Student', true);
                                    ++$countChild;
                                }
                            }
                        }
                    }

                    if ($countChild == 0) {
                        echo "<div class='error'>";
                        echo __('There are no records to display.');
                        echo '</div>';
                    }
                }
            }

            echo '<h2>';
            echo __('Filter & Search');
            echo '</h2>';

            $search = $_GET['search'] ?? '';

            $form = Form::create('searchForm', $_SESSION[$guid]['absoluteURL'].'/index.php','get');
            $form->setClass('noIntBorder fullWidth');

            $form->addHiddenValue('q', "/modules/".$_SESSION[$guid]['module']."/activities_view.php");

            if ($countChild > 0 and $roleCategory == 'Parent' and $highestAction == 'View Activities_studentRegisterByParent') {
                $row = $form->addRow();
                    $row->addLabel('gibbonPersonID', __('Child'))->description(__('Choose the child you are registering for.'));
                    $row->addSelect('gibbonPersonID')->fromArray($options)->selected($gibbonPersonID)->placeholder(($countChild > 1)? '' : null);
            }

            $row = $form->addRow();
                $row->addLabel('search', __('Search'))->description(__('Activity name.'));
                $row->addTextField('search')->setValue($search)->maxLength(20);

            $row = $form->addRow();
                $row->addSearchSubmit($gibbon->session, __('Clear Search'));

            echo $form->getOutput();

            echo '<h2>';
            echo __('Activities');
            echo '</h2>';

            //Set pagination variable
            $page = $_GET['page'] ?? 1;

            if ((!is_numeric($page)) or $page < 1) {
                $page = 1;
            }

            $today = date('Y-m-d');

            //Set special where params for different roles and permissions
            $continue = true;
            $and = '';
            if ($roleCategory == 'Student' and $highestAction == 'View Activities_studentRegister') {
                $continue = false;
                
                    $dataStudent = array('gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                    $sqlStudent = 'SELECT * FROM gibbonStudentEnrolment WHERE gibbonPersonID=:gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID';
                    $resultStudent = $connection2->prepare($sqlStudent);
                    $resultStudent->execute($dataStudent);

                if ($resultStudent->rowCount() == 1) {
                    $rowStudent = $resultStudent->fetch();
                    $gibbonYearGroupID = $rowStudent['gibbonYearGroupID'];
                    if ($gibbonYearGroupID != '') {
                        $continue = true;
                        $and = " AND gibbonYearGroupIDList LIKE '%$gibbonYearGroupID%'";
                    }
                }
            }
            if ($roleCategory == 'Parent' and $highestAction == 'View Activities_studentRegisterByParent' and $gibbonPersonID != '' and $countChild > 0) {
                $continue = false;

                //Confirm access to this student
                
                    $dataChild = array('gibbonPersonID' => $gibbonPersonID, 'gibbonPersonID2' => $_SESSION[$guid]['gibbonPersonID']);
                    $sqlChild = "SELECT * FROM gibbonFamilyChild JOIN gibbonFamily ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonFamilyChild.gibbonPersonID=:gibbonPersonID AND gibbonFamilyAdult.gibbonPersonID=:gibbonPersonID2 AND childDataAccess='Y'";
                    $resultChild = $connection2->prepare($sqlChild);
                    $resultChild->execute($dataChild);
                if ($resultChild->rowCount() == 1) {
                    
                        $dataStudent = array('gibbonPersonID' => $gibbonPersonID, 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                        $sqlStudent = 'SELECT * FROM gibbonStudentEnrolment WHERE gibbonPersonID=:gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID';
                        $resultStudent = $connection2->prepare($sqlStudent);
                        $resultStudent->execute($dataStudent);

                    if ($resultStudent->rowCount() == 1) {
                        $rowStudent = $resultStudent->fetch();
                        $gibbonYearGroupID = $rowStudent['gibbonYearGroupID'];
                        if ($gibbonYearGroupID != '') {
                            $continue = true;
                            $and = " AND gibbonYearGroupIDList LIKE '%$gibbonYearGroupID%'";
                        }
                    }
                }
            }

            if ($continue == false) {
                echo "<div class='error'>";
                echo __('There are no records to display.');
                echo '</div>';
            } else {
                //Should we show date as term or date?
                $dateType = getSettingByScope($connection2, 'Activities', 'dateType');
                if ($dateType == 'Term') {
                    $maxPerTerm = getSettingByScope($connection2, 'Activities', 'maxPerTerm');
                }

                try {
                    if ($dateType != 'Date') {
                        $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                        $sql = "SELECT * FROM gibbonActivity WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND active='Y' AND NOT gibbonSchoolYearTermIDList='' $and ORDER BY gibbonSchoolYearTermIDList, name";
                    } else {
                        $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'listingStart' => $today, 'listingEnd' => $today);
                        $sql = "SELECT * FROM gibbonActivity WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND active='Y' AND listingStart<=:listingStart AND listingEnd>=:listingEnd $and ORDER BY name";
                    }
                    if ($search != '') {
                        if ($dateType != 'Date') {
                            $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'search' => "%$search%");
                            $sql = "SELECT * FROM gibbonActivity WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND active='Y' AND NOT gibbonSchoolYearTermIDList='' AND name LIKE :search $and ORDER BY gibbonSchoolYearTermIDList, name";
                        } else {
                            $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'listingStart' => $today, 'listingEnd' => $today, 'search' => "%$search%");
                            $sql = "SELECT * FROM gibbonActivity WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND active='Y' AND listingStart<=:listingStart AND listingEnd>=:listingEnd AND name LIKE :search $and ORDER BY name";
                        }
                    }
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }

                $sqlPage = $sql.' LIMIT '.$_SESSION[$guid]['pagination'].' OFFSET '.(($page - 1) * $_SESSION[$guid]['pagination']);

                if ($result->rowCount() < 1) {
                    echo "<div class='error'>";
                    echo __('There are no records to display.');
                    echo '</div>';
                } else {
                    if ($result->rowCount() > $_SESSION[$guid]['pagination']) {
                        printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]['pagination'], 'top', "search=$search");
                    }

                    if ($dateType == 'Term' and $maxPerTerm > 0 and (($roleCategory == 'Student' and $highestAction == 'View Activities_studentRegister') or ($roleCategory == 'Parent' and $highestAction == 'View Activities_studentRegisterByParent' and $gibbonPersonID != '' and $countChild > 0))) {
                        echo "<div class='warning'>";
                        echo __("Remember, each student can register for no more than $maxPerTerm activities per term. Your current registration count by term is:");
                        $terms = getTerms($connection2, $_SESSION[$guid]['gibbonSchoolYearID']);
                        echo '<ul>';
                        for ($i = 0; $i < count($terms); $i = $i + 2) {
                            echo '<li>';
                            echo '<b>'.$terms[($i + 1)].':</b> ';

                            
                                $dataActivityCount = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $gibbonPersonID, 'gibbonSchoolYearTermIDList' => '%'.$terms[$i].'%');
                                $sqlActivityCount = "SELECT * FROM gibbonActivityStudent JOIN gibbonActivity ON (gibbonActivityStudent.gibbonActivityID=gibbonActivity.gibbonActivityID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonID=:gibbonPersonID AND gibbonSchoolYearTermIDList LIKE :gibbonSchoolYearTermIDList AND NOT status='Not Accepted'";
                                $resultActivityCount = $connection2->prepare($sqlActivityCount);
                                $resultActivityCount->execute($dataActivityCount);

                            if ($resultActivityCount->rowCount() >= 0) {
                                echo $resultActivityCount->rowCount().' activities';
                            }
                            echo '</li>';
                        }
                        echo '</ul>';
                        echo '</div>';
                    }

                    echo "<table cellspacing='0' style='width: 100%'>";
                    echo "<tr class='head'>";
                    echo '<th>';
                    echo __('Activity');
                    echo '</th>';
                    echo '<th>';
                    echo __('Provider');
                    echo '</th>';
                    echo '<th>';
                    if ($dateType != 'Date') {
                        echo __('Terms').'<br/>';
                    } else {
                        echo __('Dates').'<br/>';
                    }
                    echo "<span style='font-style: italic; font-size: 85%'>";
                    echo __('Days');
                    echo '</span>';
                    echo '</th>';
                    echo "<th style='width: 100px'>";
                    echo __('Years');
                    echo '</th>';
                    echo '<th>';
                    echo __('Cost').'<br/>';
                    echo "<span style='font-style: italic; font-size: 85%'>".$_SESSION[$guid]['currency'].'</span>';
                    echo '</th>';
                    if (($roleCategory == 'Student' and $highestAction == 'View Activities_studentRegister') or ($roleCategory == 'Parent' and $highestAction == 'View Activities_studentRegisterByParent' and $gibbonPersonID != '' and $countChild > 0)) {
                        echo '<th>';
                        echo __('Enrolment');
                        echo '</th>';
                    }
                    echo "<th style='width: 80px'>";
                    echo __('Actions');
                    echo '</th>';
                    echo '</tr>';

                    $count = 0;
                    $rowNum = 'odd';
                    
                        $resultPage = $connection2->prepare($sqlPage);
                        $resultPage->execute($data);
                    while ($row = $resultPage->fetch()) {
                        if ($count % 2 == 0) {
                            $rowNum = 'even';
                        } else {
                            $rowNum = 'odd';
                        }

                        $rowEnrol = null;
                        if (($roleCategory == 'Student' and $highestAction == 'View Activities_studentRegister') or ($roleCategory == 'Parent' and $highestAction == 'View Activities_studentRegisterByParent' and $gibbonPersonID != '' and $countChild > 0)) {
                            
                                $dataEnrol = array('gibbonActivityID' => $row['gibbonActivityID'], 'gibbonPersonID' => $gibbonPersonID);
                                $sqlEnrol = 'SELECT * FROM gibbonActivityStudent WHERE gibbonActivityID=:gibbonActivityID AND gibbonPersonID=:gibbonPersonID';
                                $resultEnrol = $connection2->prepare($sqlEnrol);
                                $resultEnrol->execute($dataEnrol);
                            if ($resultEnrol->rowCount() > 0) {
                                $rowEnrol = $resultEnrol->fetch();
                                $rowNum = 'current';
                            }
                        }

                        ++$count;

                        //COLOR ROW BY STATUS!
                        echo "<tr class=$rowNum>";
                        echo '<td>';
                        echo $row['name'].'<br/>';
                        echo '<i>'.trim($row['type']).'</i>';
                        echo '</td>';
                        echo '<td>';
                        if ($row['provider'] == 'School') {
                            echo $_SESSION[$guid]['organisationNameShort'];
                        } else {
                            echo __('External');
                        }
                        echo '</td>';
                        echo '<td>';
                        if ($dateType != 'Date') {
                            $terms = getTerms($connection2, $_SESSION[$guid]['gibbonSchoolYearID'], true);
                            $termList = '';
                            for ($i = 0; $i < count($terms); $i = $i + 2) {
                                if (is_numeric(strpos($row['gibbonSchoolYearTermIDList'], $terms[$i]))) {
                                    $termList .= $terms[($i + 1)].'<br/>';
                                }
                            }
                            echo $termList;
                        } else {
                            echo formatDateRange($row['programStart'], $row['programEnd']);
                        }

                        echo "<span style='font-style: italic; font-size: 85%'>";
                        
                            $dataSlots = array('gibbonActivityID' => $row['gibbonActivityID']);
                            $sqlSlots = 'SELECT DISTINCT nameShort, sequenceNumber FROM gibbonActivitySlot JOIN gibbonDaysOfWeek ON (gibbonActivitySlot.gibbonDaysOfWeekID=gibbonDaysOfWeek.gibbonDaysOfWeekID) WHERE gibbonActivityID=:gibbonActivityID ORDER BY sequenceNumber';
                            $resultSlots = $connection2->prepare($sqlSlots);
                            $resultSlots->execute($dataSlots);

                        $count2 = 0;
                        while ($rowSlots = $resultSlots->fetch()) {
                            if ($count2 > 0) {
                                echo ', ';
                            }
                            echo __($rowSlots['nameShort']);
                            ++$count2;
                        }
                        if ($count2 == 0) {
                            echo '<i>'.__('None').'</i>';
                        }
                        echo '</span>';
                        echo '</td>';
                        echo '<td>';
                        echo getYearGroupsFromIDList($guid, $connection2, $row['gibbonYearGroupIDList']);
                        echo '</td>';
                        echo '<td>';
                        if ($hideExternalProviderCost == 'Y' and $row['provider'] == 'External') {
                            echo '<i>'.__('See activity details').'</i>';
                        } else {
                            if ($row['payment'] == 0) {
                                echo '<i>'.__('None').'</i>';
                            } else {
                                if (substr($_SESSION[$guid]['currency'], 4) != '') {
                                    echo substr($_SESSION[$guid]['currency'], 4);
                                }
                                echo number_format($row['payment'], 2)."<br/>";
                                echo __($row['paymentType'])."<br/>";
                                if ($row['paymentFirmness'] != 'Finalised') {
                                    echo __($row['paymentFirmness'])."<br/>";
                                }
                            }
                        }
                        echo '</td>';
                        if (($roleCategory == 'Student' and $highestAction == 'View Activities_studentRegister') or ($roleCategory == 'Parent' and $highestAction == 'View Activities_studentRegisterByParent' and $gibbonPersonID != '' and $countChild > 0)) {
                            echo '<td>';
                            if ($row['provider'] == 'External' and $disableExternalProviderSignup == 'Y') {
                                echo '<i>'.__('See activity details').'</i>';
                            } elseif ($row['registration'] == 'N') {
                                echo __('Closed').'<br/>';
                            } else {
                                echo $rowEnrol['status'] ?? '';
                            }
                            echo '</td>';
                        }
                        echo '<td>';
                        echo "<a class='thickbox' href='".$_SESSION[$guid]['absoluteURL'].'/fullscreen.php?q=/modules/'.$_SESSION[$guid]['module'].'/activities_view_full.php&gibbonActivityID='.$row['gibbonActivityID']."&width=1000&height=550'><img title='".__('View Details')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/plus.png'/></a> ";
                        $signup = true;
                        if ($access == 'View') {
                            $signup = false;
                        }
                        if ($row['registration'] == 'N') {
                            $signup = false;
                        }
                        if ($row['provider'] == 'External' and $disableExternalProviderSignup == 'Y') {
                            $signup = false;
                        }
                        if ($signup) {
                            if (($roleCategory == 'Student' and $highestAction == 'View Activities_studentRegister') or ($roleCategory == 'Parent' and $highestAction == 'View Activities_studentRegisterByParent' and $gibbonPersonID != '' and $countChild > 0)) {
                                if ($resultEnrol->rowCount() < 1) {
                                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/activities_view_register.php&gibbonPersonID=$gibbonPersonID&search=".$search.'&mode=register&gibbonActivityID='.$row['gibbonActivityID']."'><img title='".__('Register')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/attendance.png'/></a> ";
                                } else {
                                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/activities_view_register.php&gibbonPersonID=$gibbonPersonID&search=".$search.'&mode=unregister&gibbonActivityID='.$row['gibbonActivityID']."'><img title='".__('Unregister')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/garbage.png'/></a> ";
                                }
                            }
                        }
                        echo '</td>';
                        echo '</tr>';
                    }
                    echo '</table>';

                    if ($result->rowCount() > $_SESSION[$guid]['pagination']) {
                        printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]['pagination'], 'bottom', "search=$search");
                    }
                }
            }
        }
    }
}
?>
