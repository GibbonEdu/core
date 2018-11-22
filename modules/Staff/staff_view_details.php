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

use Gibbon\Services\Format;

//Module includes for User Admin (for custom fields)
include './modules/User Admin/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Staff/staff_view_details.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __('You do not have access to this action.');
    echo '</div>';
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        echo "<div class='error'>";
        echo __('The highest grouped action cannot be determined.');
        echo '</div>';
    } else {
        $gibbonPersonID = $_GET['gibbonPersonID'];
        if ($gibbonPersonID == false) {
            echo "<div class='error'>";
            echo __('You have not specified one or more required parameters.');
            echo '</div>';
        } else {
            $search = null;
            if (isset($_GET['search'])) {
                $search = $_GET['search'];
            }
            $allStaff = '';
            if (isset($_GET['allStaff'])) {
                $allStaff = $_GET['allStaff'];
            }

            if ($highestAction == 'View Staff Profile_brief') {
                //Proceed!
                try {
                    $data = array('gibbonPersonID' => $gibbonPersonID);
                    $sql = "SELECT title, surname, preferredName, type, gibbonStaff.jobTitle, email, website, countryOfOrigin, qualifications, biography, image_240 FROM gibbonPerson JOIN gibbonStaff ON (gibbonStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonPerson.gibbonPersonID=:gibbonPersonID";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }

                if ($result->rowCount() != 1) {
                    echo "<div class='error'>";
                    echo __('The selected record does not exist, or you do not have access to it.');
                    echo '</div>';
                    echo '</div>';
                } else {
                    $row = $result->fetch();

                    $page->breadcrumbs
                        ->add(__('View Staff Profiles'), 'staff_view.php')
                        ->add(Format::name('', $row['preferredName'], $row['surname'], 'Student'));

                    if ($search != '') {
                        echo "<div class='linkTop'>";
                        echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Staff/staff_view.php&search='.$search."'>".__('Back to Search Results').'</a>';
                        echo '</div>';
                    }

                    echo "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>";
                    echo '<tr>';
                    echo "<td style='width: 33%; vertical-align: top'>";
                    echo "<span style='font-size: 115%; font-weight: bold'>".__('Name').'</span><br/>';
                    echo '<i>'.Format::name($row['title'], $row['preferredName'], $row['surname'], 'Parent').'</i>';
                    echo '</td>';
                    echo "<td style='width: 33%; vertical-align: top'>";
                    echo "<span style='font-size: 115%; font-weight: bold'>".__('Staff Type').'</span><br/>';
                    echo '<i>'.$row['type'].'</i>';
                    echo '</td>';
                    echo "<td style='width: 33%; vertical-align: top'>";
                    echo "<span style='font-size: 115%; font-weight: bold'>".__('Job Title').'</span><br/>';
                    echo '<i>'.$row['jobTitle'].'</i>';
                    echo '</td>';
                    echo '</tr>';
                    echo '<tr>';
                    echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                    echo "<span style='font-size: 115%; font-weight: bold'>".__('Email').'</span><br/>';
                    if ($row['email'] != '') {
                        echo "<i><a href='mailto:".$row['email']."'>".$row['email'].'</a></i>';
                    }
                    echo '</td>';
                    echo "<td style='width: 67%; padding-top: 15px; vertical-align: top' colspan=2>";
                    echo "<span style='font-size: 115%; font-weight: bold'>".__('Website').'</span><br/>';
                    if ($row['website'] != '') {
                        echo "<i><a href='".$row['website']."'>".$row['website'].'</a></i>';
                    }
                    echo '</td>';
                    echo '</tr>';
                    echo '</table>';

                    echo '<h4>';
                    echo __('Biography');
                    echo '</h4>';
                    echo "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>";
                    echo '<tr>';
                    echo "<td style='width: 33%; vertical-align: top'>";
                    echo "<span style='font-size: 115%; font-weight: bold'>".__('Country Of Origin').'</span><br/>';
                    echo '<i>'.$row['countryOfOrigin'].'</i>';
                    echo '</td>';
                    echo "<td style='width: 67%; vertical-align: top' colspan=2>";
                    echo "<span style='font-size: 115%; font-weight: bold'>".__('Qualifications').'</span><br/>';
                    echo '<i>'.$row['qualifications'].'</i>';
                    echo '</td>';
                    echo '</tr>';
                    echo '<tr>';
                    echo "<td style='width: 100%; vertical-align: top' colspan=3>";
                    echo "<span style='font-size: 115%; font-weight: bold'>".__('Biography').'</span><br/>';
                    echo '<i>'.$row['biography'].'</i>';
                    echo '</td>';
                    echo '</tr>';
                    echo '</table>';

                    //Set sidebar
                    $_SESSION[$guid]['sidebarExtra'] = getUserPhoto($guid, $row['image_240'], 240);
                }
            } else {
                try {
                    $data = array('gibbonPersonID' => $gibbonPersonID);
                    if ($allStaff != 'on') {
                        $sql = "SELECT gibbonPerson.*, gibbonStaff.initials, gibbonStaff.type, gibbonStaff.jobTitle, countryOfOrigin, qualifications, biography FROM gibbonPerson JOIN gibbonStaff ON (gibbonStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonPerson.gibbonPersonID=:gibbonPersonID";
                    } else {
                        $sql = 'SELECT gibbonPerson.*, gibbonStaff.initials, gibbonStaff.type, gibbonStaff.jobTitle, countryOfOrigin, qualifications, biography FROM gibbonPerson JOIN gibbonStaff ON (gibbonStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPerson.gibbonPersonID=:gibbonPersonID';
                    }
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }

                if ($result->rowCount() != 1) {
                    echo "<div class='error'>";
                    echo __('The selected record does not exist, or you do not have access to it.');
                    echo '</div>';
                } else {
                    $row = $result->fetch();

                    $page->breadcrumbs
                        ->add(__('View Staff Profiles'), 'staff_view.php', ['search' => $search, 'allStaff' => $allStaff])
                        ->add(Format::name('', $row['preferredName'], $row['surname'], 'Student'));

                    $subpage = null;
                    if (isset($_GET['subpage'])) {
                        $subpage = $_GET['subpage'];
                    }
                    if ($subpage == '') {
                        $subpage = 'Overview';
                    }

                    if ($search != '') {
                        echo "<div class='linkTop'>";
                        echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Staff/staff_view.php&search='.$search."'>".__('Back to Search Results').'</a>';
                        echo '</div>';
                    }

                    echo '<h2>';
                    if ($subpage != '') {
                        echo $subpage;
                    }
                    echo '</h2>';

                    if ($subpage == 'Overview') {
                        if (isActionAccessible($guid, $connection2, '/modules/User Admin/user_manage.php') == true) {
                            echo "<div class='linkTop'>";
                            echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/User Admin/user_manage_edit.php&gibbonPersonID=$gibbonPersonID'>".__('Edit')."<img style='margin: 0 0 -4px 5px' title='".__('Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
                            echo '</div>';
                        }

                        //General Information
                        echo '<h4>';
                        echo __('General Information');
                        echo '</h4>';
                        echo "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>";
                        echo '<tr>';
                        echo "<td style='width: 33%; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__('Name').'</span><br/>';
                        echo '<i>'.Format::name($row['title'], $row['preferredName'], $row['surname'], 'Parent').'</i>';
                        echo '</td>';
                        echo "<td style='width: 33%; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__('Staff Type').'</span><br/>';
                        echo '<i>'.$row['type'].'</i>';
                        echo '</td>';
                        echo "<td style='width: 33%; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__('Job Title').'</span><br/>';
                        echo '<i>'.$row['jobTitle'].'</i>';
                        echo '</td>';
                        echo '</tr>';
                        echo '<tr>';
                        echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__('Username').'</span><br/>';
                        echo '<i>'.$row['username'].'</i>';
                        echo '</td>';
                        echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__('Website').'</span><br/>';
                        if ($row['website'] != '') {
                            echo "<i><a href='".$row['website']."'>".$row['website'].'</a></i>';
                        }
                        echo '</td>';
                        echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__('Email').'</span><br/>';
                        if ($row['email'] != '') {
                            echo "<i><a href='mailto:".$row['email']."'>".$row['email'].'</a></i>';
                        }
                        echo '</td>';
                        echo '</tr>';
                        echo '</table>';

                        echo '<h4>';
                        echo __('Biography');
                        echo '</h4>';
                        echo "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>";
                        echo '<tr>';
                        echo "<td style='width: 33%; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__('Country Of Origin').'</span><br/>';
                        echo '<i>'.$row['countryOfOrigin'].'</i>';
                        echo '</td>';
                        echo "<td style='width: 67%; vertical-align: top' colspan=2>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__('Qualifications').'</span><br/>';
                        echo '<i>'.$row['qualifications'].'</i>';
                        echo '</td>';
                        echo '</tr>';
                        echo '<tr>';
                        echo "<td style='width: 100%; vertical-align: top' colspan=3>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__('Biography').'</span><br/>';
                        echo '<i>'.$row['biography'].'</i>';
                        echo '</td>';
                        echo '</tr>';
                        echo '</table>';

                        //Show timetable
                        echo "<a name='timetable'></a>";
                        echo '<h4>';
                        echo __('Timetable');
                        echo '</h4>';
                        if (isActionAccessible($guid, $connection2, '/modules/Timetable/tt_view.php') == true) {
                            if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/courseEnrolment_manage_byPerson_edit.php') == true) {
                                echo "<div class='linkTop'>";
                                echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Timetable Admin/courseEnrolment_manage_byPerson_edit.php&gibbonPersonID=$gibbonPersonID&gibbonSchoolYearID=".$_SESSION[$guid]['gibbonSchoolYearID']."&type=Staff&allUsers='>".__('Edit')."<img style='margin: 0 0 -4px 5px' title='".__('Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
                                echo '</div>';
                            }

                            include './modules/Timetable/moduleFunctions.php';
                            $ttDate = '';
                            if (isset($_POST['ttDate'])) {
                                $ttDate = dateConvertToTimestamp(dateConvert($guid, $_POST['ttDate']));
                            }
                            $gibbonTTID = null;
                            if (isset($_GET['gibbonTTID'])) {
                                $gibbonTTID = $_GET['gibbonTTID'];
                            }
                            $tt = renderTT($guid, $connection2, $gibbonPersonID, $gibbonTTID, false, $ttDate, '/modules/Staff/staff_view_details.php', "&gibbonPersonID=$gibbonPersonID&search=$search#timetable");
                            if ($tt != false) {
                                echo $tt;
                            } else {
                                echo "<div class='error'>";
                                echo __('The selected record does not exist, or you do not have access to it.');
                                echo '</div>';
                            }
                        }
                    } elseif ($subpage == 'Personal') {
                        if (isActionAccessible($guid, $connection2, '/modules/User Admin/user_manage.php') == true) {
                            echo "<div class='linkTop'>";
                            echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/User Admin/user_manage_edit.php&gibbonPersonID=$gibbonPersonID'>".__('Edit')."<img style='margin: 0 0 -4px 5px' title='".__('Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
                            echo '</div>';
                        }

                        echo "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>";
                        echo '<tr>';
                        echo "<td style='width: 33%; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__('Name').'</span><br/>';
                        echo '<i>'.Format::name($row['title'], $row['preferredName'], $row['surname'], 'Parent').'</i>';
                        echo '</td>';
                        echo "<td style='width: 33%; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__('Staff Type').'</span><br/>';
                        echo '<i>'.$row['type'].'</i>';
                        echo '</td>';
                        echo "<td style='width: 33%; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__('Job Title').'</span><br/>';
                        echo '<i>'.$row['jobTitle'].'</i>';
                        echo '</td>';
                        echo '</tr>';
                        echo '<tr>';
                        echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__('Initials').'</span><br/>';
                        echo $row['initials'];
                        echo '</td>';
                        echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__('Gender').'</span><br/>';
                        echo $row['gender'];
                        echo '</td>';
                        echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";

                        echo '</td>';
                        echo '</tr>';
                        echo '</table>';

                        echo '<h4>';
                        echo 'Contacts';
                        echo '</h4>';

                        echo "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>";
                        $numberCount = 0;
                        if ($row['phone1'] != '' or $row['phone2'] != '' or $row['phone3'] != '' or $row['phone4'] != '') {
                            echo '<tr>';
                            for ($i = 1; $i < 5; ++$i) {
                                if ($row['phone'.$i] != '') {
                                    ++$numberCount;
                                    echo "<td width: 33%; style='vertical-align: top'>";
                                    echo "<span style='font-size: 115%; font-weight: bold'>".__('Phone')." $numberCount</span><br/>";
                                    if ($row['phone'.$i.'Type'] != '') {
                                        echo '<i>'.$row['phone'.$i.'Type'].':</i> ';
                                    }
                                    if ($row['phone'.$i.'CountryCode'] != '') {
                                        echo '+'.$row['phone'.$i.'CountryCode'].' ';
                                    }
                                    echo formatPhone($row['phone'.$i]).'<br/>';
                                    echo '</td>';
                                }
                            }
                            for ($i = ($numberCount + 1); $i < 5; ++$i) {
                                echo "<td width: 33%; style='vertical-align: top'></td>";
                            }
                            echo '</tr>';
                        }
                        echo '<tr>';
                        echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__('Email').'</span><br/>';
                        if ($row['email'] != '') {
                            echo "<i><a href='mailto:".$row['email']."'>".$row['email'].'</a></i>';
                        }
                        echo '</td>';
                        echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__('Alternate Email').'</span><br/>';
                        if ($row['emailAlternate'] != '') {
                            echo "<i><a href='mailto:".$row['emailAlternate']."'>".$row['emailAlternate'].'</a></i>';
                        }
                        echo '</td>';
                        echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__('Website').'</span><br/>';
                        if ($row['website'] != '') {
                            echo "<i><a href='".$row['website']."'>".$row['website'].'</a></i>';
                        }
                        echo '</td>';
                        echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";

                        echo '</td>';
                        echo '</tr>';
                        if ($row['address1'] != '') {
                            echo '<tr>';
                            echo "<td style='width: 33%; padding-top: 15px; vertical-align: top' colspan=3>";
                            echo "<span style='font-size: 115%; font-weight: bold'>".__('Address 1').'</span><br/>';
                            $address1 = addressFormat($row['address1'], $row['address1District'], $row['address1Country']);
                            if ($address1 != false) {
                                echo $address1;
                            }
                            echo '</td>';
                            echo '</tr>';
                        }
                        if ($row['address2'] != '') {
                            echo '<tr>';
                            echo "<td style='width: 33%; padding-top: 15px; vertical-align: top' colspan=3>";
                            echo "<span style='font-size: 115%; font-weight: bold'>".__('Address 2').'</span><br/>';
                            $address2 = addressFormat($row['address2'], $row['address2District'], $row['address2Country']);
                            if ($address2 != false) {
                                echo $address2;
                            }
                            echo '</td>';
                            echo '</tr>';
                        }
                        echo '</table>';

                        echo '<h4>';
                        echo __('Miscellaneous');
                        echo '</h4>';

                        echo "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>";
                        echo '<tr>';
                        echo "<td style='width: 33%; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__('Transport').'</span><br/>';
                        echo $row['transport'];
                        echo '</td>';
                        echo "<td style='width: 33%; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__('Vehicle Registration').'</span><br/>';
                        echo $row['vehicleRegistration'];
                        echo '</td>';
                        echo "<td style='width: 33%; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__('Locker Number').'</span><br/>';
                        echo $row['lockerNumber'];
                        echo '</td>';
                        echo '</tr>';
                        echo '</table>';

                        //Custom Fields
                        $fields = unserialize($row['fields']);
                        $resultFields = getCustomFields($connection2, $guid, false, true);
                        if ($resultFields->rowCount() > 0) {
                            echo '<h4>';
                            echo __('Custom Fields');
                            echo '</h4>';

                            echo "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>";
                            $count = 0;
                            $columns = 3;

                            while ($rowFields = $resultFields->fetch()) {
                                if ($count % $columns == 0) {
                                    echo '<tr>';
                                }
                                echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                                echo "<span style='font-size: 115%; font-weight: bold'>".__($rowFields['name']).'</span><br/>';
                                if (isset($fields[$rowFields['gibbonPersonFieldID']])) {
                                    if ($rowFields['type'] == 'date') {
                                        echo dateConvertBack($guid, $fields[$rowFields['gibbonPersonFieldID']]);
                                    } elseif ($rowFields['type'] == 'url') {
                                        echo "<a target='_blank' href='".$fields[$rowFields['gibbonPersonFieldID']]."'>".$fields[$rowFields['gibbonPersonFieldID']].'</a>';
                                    } else {
                                        echo $fields[$rowFields['gibbonPersonFieldID']];
                                    }
                                }
                                echo '</td>';

                                if ($count % $columns == ($columns - 1)) {
                                    echo '</tr>';
                                }
                                ++$count;
                            }

                            if ($count % $columns != 0) {
                                for ($i = 0;$i < $columns - ($count % $columns);++$i) {
                                    echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'></td>";
                                }
                                echo '</tr>';
                            }

                            echo '</table>';
                        }
                    } elseif ($subpage == 'Facilities') {
                        try {
                            $data = array('gibbonPersonID1' => $gibbonPersonID, 'gibbonPersonID2' => $gibbonPersonID, 'gibbonPersonID3' => $gibbonPersonID, 'gibbonPersonID4' => $gibbonPersonID, 'gibbonPersonID5' => $gibbonPersonID, 'gibbonPersonID6' => $gibbonPersonID, 'gibbonSchoolYearID1' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonSchoolYearID2' => $_SESSION[$guid]['gibbonSchoolYearID']);
                            $sql = '(SELECT gibbonSpace.*, gibbonSpacePersonID, usageType, NULL AS \'exception\' FROM gibbonSpacePerson JOIN gibbonSpace ON (gibbonSpacePerson.gibbonSpaceID=gibbonSpace.gibbonSpaceID) WHERE gibbonPersonID=:gibbonPersonID1)
                            UNION
                            (SELECT DISTINCT gibbonSpace.*, NULL AS gibbonSpacePersonID, \'Roll Group\' AS usageType, NULL AS \'exception\' FROM gibbonRollGroup JOIN gibbonSpace ON (gibbonRollGroup.gibbonSpaceID=gibbonSpace.gibbonSpaceID) WHERE (gibbonPersonIDTutor=:gibbonPersonID2 OR gibbonPersonIDTutor2=:gibbonPersonID3 OR gibbonPersonIDTutor3=:gibbonPersonID4) AND gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID1)
                            UNION
                            (SELECT DISTINCT gibbonSpace.*, NULL AS gibbonSpacePersonID, \'Timetable\' AS usageType, gibbonTTDayRowClassException.gibbonPersonID AS \'exception\' FROM gibbonSpace JOIN gibbonTTDayRowClass ON (gibbonTTDayRowClass.gibbonSpaceID=gibbonSpace.gibbonSpaceID) JOIN gibbonCourseClass ON (gibbonTTDayRowClass.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) LEFT JOIN gibbonTTDayRowClassException ON (gibbonTTDayRowClassException.gibbonTTDayRowClassID=gibbonTTDayRowClass.gibbonTTDayRowClassID AND (gibbonTTDayRowClassException.gibbonPersonID=:gibbonPersonID6 OR gibbonTTDayRowClassException.gibbonPersonID IS NULL)) WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID2 AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID5)
                            ORDER BY name';
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
                            echo "<table cellspacing='0' style='width: 100%'>";
                            echo "<tr class='head'>";
                            echo '<th>';
                            echo __('Name');
                            echo '</th>';
                            echo '<th>';
                            echo __('Usage').'<br/>';
                            echo '</th>';
                            echo '</tr>';

                            $count = 0;
                            $rowNum = 'odd';
                            while ($row = $result->fetch()) {
                                if ($row['exception'] == null) {
                                    if ($count % 2 == 0) {
                                        $rowNum = 'even';
                                    } else {
                                        $rowNum = 'odd';
                                    }
                                    ++$count;

                                    echo "<tr class=$rowNum>";
                                    echo '<td>';
                                    echo $row['name'];
                                    echo '</td>';
                                    echo '<td>';
                                    echo $row['usageType'];
                                    echo '</td>';
                                    echo '</tr>';
                                }
                            }
                            echo '</table>';
                        }
                    } elseif ($subpage == 'Emergency Contacts') {
                        if (isActionAccessible($guid, $connection2, '/modules/User Admin/user_manage.php') == true) {
                            echo "<div class='linkTop'>";
                            echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/User Admin/user_manage_edit.php&gibbonPersonID=$gibbonPersonID'>".__('Edit')."<img style='margin: 0 0 -4px 5px' title='".__('Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
                            echo '</div>';
                        }

                        echo '<p>';
                        echo __('In an emergency, please try and contact the adult family members listed below first. If these cannot be reached, then try the emergency contacts below.');
                        echo '</p>';

                        echo '<h4>';
                        echo __('Adult Family Members');
                        echo '</h4>';

                        try {
                            $dataFamily = array('gibbonPersonID' => $gibbonPersonID);
                            $sqlFamily = 'SELECT * FROM gibbonFamily JOIN gibbonFamilyChild ON (gibbonFamily.gibbonFamilyID=gibbonFamilyChild.gibbonFamilyID) WHERE gibbonPersonID=:gibbonPersonID';
                            $resultFamily = $connection2->prepare($sqlFamily);
                            $resultFamily->execute($dataFamily);
                        } catch (PDOException $e) {
                            echo "<div class='error'>".$e->getMessage().'</div>';
                        }

                        if ($resultFamily->rowCount() != 1) {
                            echo "<div class='error'>";
                            echo __('There is no family information available for the current staff member.');
                            echo '</div>';
                        } else {
                            $rowFamily = $resultFamily->fetch();
                            $count = 1;
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
                                echo "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>";
                                echo '<tr>';
                                echo "<td style='width: 33%; vertical-align: top'>";
                                echo "<span style='font-size: 115%; font-weight: bold'>".__('Name').'</span><br/>';
                                echo Format::name($rowMember['title'], $rowMember['preferredName'], $rowMember['surname'], 'Parent');
                                echo '</td>';
                                echo "<td style='width: 33%; vertical-align: top'>";
                                echo "<span style='font-size: 115%; font-weight: bold'>".__('Relationship').'</span><br/>';
                                if ($rowMember['role'] == 'Parent') {
                                    if ($rowMember['gender'] == 'M') {
                                        echo __('Father');
                                    } elseif ($rowMember['gender'] == 'F') {
                                        echo __('Mother');
                                    } else {
                                        echo $rowMember['role'];
                                    }
                                } else {
                                    echo $rowMember['role'];
                                }
                                echo '</td>';
                                echo "<td style='width: 34%; vertical-align: top'>";
                                echo "<span style='font-size: 115%; font-weight: bold'>".__('Contact By Phone').'</span><br/>';
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
                                echo '</td>';
                                echo '</tr>';
                                echo '</table>';
                                ++$count;
                            }
                        }

                        echo '<h4>';
                        echo __('Emergency Contacts');
                        echo '</h4>';
                        echo "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>";
                        echo '<tr>';
                        echo "<td style='width: 33%; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__('Contact 1').'</span><br/>';
                        echo '<i>'.$row['emergency1Name'].'</i>';
                        if ($row['emergency1Relationship'] != '') {
                            echo ' ('.$row['emergency1Relationship'].')';
                        }
                        echo '</td>';
                        echo "<td style='width: 33%; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__('Number 1').'</span><br/>';
                        echo $row['emergency1Number1'];
                        echo '</td>';
                        echo "<td style=width: 34%; 'vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__('Number 2').'</span><br/>';
                        if ($row['emergency1Number2'] != '') {
                            echo $row['emergency1Number2'];
                        }
                        echo '</td>';
                        echo '</tr>';
                        echo '<tr>';
                        echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__('Contact 2').'</span><br/>';
                        echo '<i>'.$row['emergency2Name'].'</i>';
                        if ($row['emergency2Relationship'] != '') {
                            echo ' ('.$row['emergency2Relationship'].')';
                        }
                        echo '</td>';
                        echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__('Number 1').'</span><br/>';
                        echo $row['emergency2Number1'];
                        echo '</td>';
                        echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__('Number 2').'</span><br/>';
                        if ($row['emergency2Number2'] != '') {
                            echo $row['emergency2Number2'];
                        }
                        echo '</td>';
                        echo '</tr>';
                        echo '</table>';
                    } elseif ($subpage == 'Timetable') {
                        if (isActionAccessible($guid, $connection2, '/modules/Timetable/tt_view.php') == false) {
                            echo "<div class='error'>";
                            echo __('The selected record does not exist, or you do not have access to it.');
                            echo '</div>';
                        } else {
                            if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/courseEnrolment_manage_byPerson_edit.php') == true) {
                                echo "<div class='linkTop'>";
                                echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Timetable Admin/courseEnrolment_manage_byPerson_edit.php&gibbonPersonID=$gibbonPersonID&gibbonSchoolYearID=".$_SESSION[$guid]['gibbonSchoolYearID']."&type=Staff&allUsers='>".__('Edit')."<img style='margin: 0 0 -4px 5px' title='".__('Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
                                echo '</div>';
                            }

                            include './modules/Timetable/moduleFunctions.php';
                            $ttDate = '';
                            if (isset($_POST['ttDate'])) {
                                $ttDate = dateConvertToTimestamp(dateConvert($guid, $_POST['ttDate']));
                            }
                            $gibbonTTID = null;
                            if (isset($_GET['gibbonTTID'])) {
                                $gibbonTTID = $_GET['gibbonTTID'];
                            }
                            $tt = renderTT($guid, $connection2, $gibbonPersonID, $gibbonTTID, false, $ttDate, '/modules/Staff/staff_view_details.php', "&gibbonPersonID=$gibbonPersonID&subpage=Timetable&search=$search");
                            if ($tt != false) {
                                echo $tt;
                            } else {
                                echo "<div class='error'>";
                                echo __('The selected record does not exist, or you do not have access to it.');
                                echo '</div>';
                            }
                        }
                    }

                    //Set sidebar
                    $_SESSION[$guid]['sidebarExtra'] = '';

                    //Show pic
                    $_SESSION[$guid]['sidebarExtra'] .= getUserPhoto($guid, $row['image_240'], 240);

                    //PERSONAL DATA MENU ITEMS
                    $_SESSION[$guid]['sidebarExtra'] .= '<h4>Personal</h4>';
                    $_SESSION[$guid]['sidebarExtra'] .= "<ul class='moduleMenu'>";
                    $style = '';
                    if ($subpage == 'Overview') {
                        $style = "style='font-weight: bold'";
                    }
                    $_SESSION[$guid]['sidebarExtra'] .= "<li><a $style href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q='.$_GET['q']."&gibbonPersonID=$gibbonPersonID&search=".$search."&allStaff=$allStaff&subpage=Overview'>".__('Overview').'</a></li>';
                    $style = '';
                    if ($subpage == 'Personal') {
                        $style = "style='font-weight: bold'";
                    }
                    $_SESSION[$guid]['sidebarExtra'] .= "<li><a $style href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q='.$_GET['q']."&gibbonPersonID=$gibbonPersonID&search=".$search."&allStaff=$allStaff&subpage=Personal'>".__('Personal').'</a></li>';
                    $style = '';
                    if ($subpage == 'Facilities') {
                        $style = "style='font-weight: bold'";
                    }
                    $_SESSION[$guid]['sidebarExtra'] .= "<li><a $style href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q='.$_GET['q']."&gibbonPersonID=$gibbonPersonID&search=".$search."&allStaff=$allStaff&subpage=Facilities'>".__('Facilities').'</a></li>';
                    $style = '';
                    if ($subpage == 'Emergency Contacts') {
                        $style = "style='font-weight: bold'";
                    }
                    $_SESSION[$guid]['sidebarExtra'] .= "<li><a $style href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q='.$_GET['q']."&gibbonPersonID=$gibbonPersonID&search=".$search."&allStaff=$allStaff&subpage=Emergency Contacts'>".__('Emergency Contacts').'</a></li>';
                    if (isActionAccessible($guid, $connection2, '/modules/Timetable/tt_view.php')) {
                        $style = '';
                        if ($subpage == 'Timetable') {
                            $style = "style='font-weight: bold'";
                        }
                        $_SESSION[$guid]['sidebarExtra'] .= "<li><a $style href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q='.$_GET['q']."&gibbonPersonID=$gibbonPersonID&search=".$search."&allStaff=$allStaff&subpage=Timetable'>".__('Timetable').'</a></li>';
                    }
                    $_SESSION[$guid]['sidebarExtra'] .= '</ul>';
                }
            }
        }
    }
}
