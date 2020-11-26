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
use Gibbon\Domain\Staff\StaffAbsenceGateway;
use Gibbon\Domain\Staff\StaffAbsenceDateGateway;
use Gibbon\Domain\Staff\StaffFacilityGateway;
use Gibbon\Domain\Activities\ActivityGateway;
use Gibbon\Tables\DataTable;
use Gibbon\Domain\User\FamilyGateway;
use Gibbon\Domain\DataSet;

//Module includes for User Admin (for custom fields)
include './modules/User Admin/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Staff/staff_view_details.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    $highestActionManage = getHighestGroupedAction($guid, "/modules/Staff/staff_manage.php", $connection2);
    if ($highestAction == false) {
        echo "<div class='error'>";
        echo __('The highest grouped action cannot be determined.');
        echo '</div>';
    } else {
        $gibbonPersonID = $_GET['gibbonPersonID'] ?? '';
        if ($gibbonPersonID == '' ) {
            echo "<div class='error'>";
            echo __('You have not specified one or more required parameters.');
            echo '</div>';
        } else {
            $search = $_GET['search'] ?? '';
            $allStaff = $_GET['allStaff'] ?? '';

            if ($highestAction == 'Staff Directory_brief') {
                //Proceed!

                    $data = array('gibbonPersonID' => $gibbonPersonID);
                    $sql = "SELECT title, surname, preferredName, type, gibbonStaff.jobTitle, email, website, countryOfOrigin, qualifications, biography, image_240 FROM gibbonPerson JOIN gibbonStaff ON (gibbonStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonPerson.gibbonPersonID=:gibbonPersonID";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);

                if ($result->rowCount() != 1) {
                    $page->addError(__('The selected record does not exist, or you do not have access to it.'));
                } else {
                    $row = $result->fetch();

                    $page->breadcrumbs
                        ->add(__('Staff Directory'), 'staff_view.php')
                        ->add(Format::name('', $row['preferredName'], $row['surname'], 'Student'));

                    if ($search != '') {
                        echo "<div class='linkTop'>";
                        echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Staff/staff_view.php&search='.$search."'>".__('Back to Search Results').'</a>';
                        echo '</div>';
                    }

                    echo $page->fetchFromTemplate('profile/overview.twig.html', [
                        'type' => 'Brief',
                        'person' => $row,
                        'staff' => $row,
                    ]);

                    $page->addSidebarExtra(Format::userPhoto($row['image_240'], 240));
                }
            } else {
                try {
                    $data = array('gibbonPersonID' => $gibbonPersonID);
                    if ($allStaff != 'on') {
                        $sql = "SELECT gibbonPerson.*, gibbonStaff.initials, gibbonStaff.type, gibbonStaff.jobTitle, countryOfOrigin, qualifications, biography, gibbonStaff.gibbonStaffID, firstAidQualified, firstAidQualification, firstAidExpiry FROM gibbonPerson JOIN gibbonStaff ON (gibbonStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonPerson.gibbonPersonID=:gibbonPersonID";
                    } else {
                        $sql = 'SELECT gibbonPerson.*, gibbonStaff.initials, gibbonStaff.type, gibbonStaff.jobTitle, countryOfOrigin, qualifications, biography, gibbonStaff.gibbonStaffID, firstAidQualified, firstAidQualification, firstAidExpiry FROM gibbonPerson JOIN gibbonStaff ON (gibbonStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPerson.gibbonPersonID=:gibbonPersonID';
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
                        ->add(__('Staff Directory'), 'staff_view.php', ['search' => $search, 'allStaff' => $allStaff])
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
                        echo __($subpage);
                    }
                    echo '</h2>';

                    if ($subpage == 'Overview') {
                        echo "<div class='linkTop'>";
                        if (isActionAccessible($guid, $connection2, '/modules/User Admin/user_manage.php') == true) {
                            echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/User Admin/user_manage_edit.php&gibbonPersonID=$gibbonPersonID'>".__('Edit User')."<img style='margin: 0 0 -4px 5px' title='".__('Edit User')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
                        }
                        if (isActionAccessible($guid, $connection2, '/modules/Staff/staff_manage.php') == true) {
                            echo " | <a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Staff/staff_manage_edit.php&gibbonStaffID=".$row['gibbonStaffID']."'>".__('Edit Staff')."<img style='margin: 0 0 -4px 5px' title='".__('Edit Staff')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
                        }
                        echo '</div>';

                        // Display a message if the staff member is absent today.
                        $staffAbsenceGateway = $container->get(StaffAbsenceGateway::class);
                        $staffAbsenceDateGateway = $container->get(StaffAbsenceDateGateway::class);

                        $criteria = $staffAbsenceGateway->newQueryCriteria(true)->filterBy('date', 'Today')->filterBy('status', 'Approved');
                        $absences = $staffAbsenceGateway->queryAbsencesByPerson($criteria, $gibbonPersonID)->toArray();

                        if (count($absences) > 0) {
                            $absenceMessage = __('{name} is absent today.', [
                                'name' => Format::name($row['title'], $row['preferredName'], $row['surname'], 'Staff', false, true),
                            ]);
                            $absenceMessage .= '<br/><br/><ul>';
                            foreach ($absences as $absence) {
                                $details = $staffAbsenceDateGateway->getByAbsenceAndDate($absence['gibbonStaffAbsenceID'], date('Y-m-d'));
                                $time = $details['allDay'] == 'N' ? Format::timeRange($details['timeStart'], $details['timeEnd']) : __('All Day');

                                $absenceMessage .= '<li>'.Format::dateRangeReadable($absence['dateStart'], $absence['dateEnd']).'  '.$time.'</li>';
                                if ($details['coverage'] == 'Accepted') {
                                    $absenceMessage .= '<li>'.__('Coverage').': '.Format::name($details['titleCoverage'], $details['preferredNameCoverage'], $details['surnameCoverage'], 'Staff', false, true).'</li>';
                                }
                            }
                            $absenceMessage .= '</ul>';

                            echo Format::alert($absenceMessage, 'warning');
                        }

                        // General Information
                        echo $page->fetchFromTemplate('profile/overview.twig.html', [
                            'type' => 'Full',
                            'person' => $row,
                            'staff' => $row,
                        ]);

                        // Show timetable
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

                        $table = DataTable::createDetails('personal');

                        $table->addColumn('preferredName', __('Name'))
                            ->format(Format::using('name', ['title', 'preferredName', 'surname', 'Parent']));
                        $table->addColumn('type', __('Staff Type'));
                        $table->addColumn('jobTitle', __('Job Title'));
                        $table->addColumn('initials', __('Initials'));
                        $table->addColumn('gender', __('Gender'));
                        $table->addColumn('initials', __('Initials'));

                        echo $table->render([$row]);

                        $table = DataTable::createDetails('contacts');
                        $table->setTitle(__('Contacts'));

                        $numberCount = 0;
                        $phones = 0;
                        for ($i = 1; $i < 5; $i++) {
                            if ($row['phone' . $i] != '') {
                                $phones++;
                            }
                        }
                        if ($phones > 0) {
                            $width = (100 / $phones) . '%';
                            for ($i = 1; $i < 5; ++$i) {
                                if ($row['phone' . $i] != '') {
                                    ++$numberCount;
                                    $table->addColumn('phone' . $i, __('Phone') . " $numberCount")
                                        ->width($width)
                                        ->format(Format::using('phone', ['phone' . $i, 'phone'.$i.'CountryCode', 'phone'.$i.'Type']));
                                }
                            }
                        }

                        $table->addColumn('email', __('Email'))
                            ->format(Format::using('link', ['mailto:' . $row['email'], 'email']));

                        $table->addColumn('emailAlternate', __('Alternate Email'))
                            ->format(function($row) {
                                if ($row['emailAlternate'] != '') {
                                    return Format::link('mailto:' . $row['emailAlternate'], $row['emailAlternate']);
                                }
                                return '';
                            });

                        $table->addColumn('website', __('Website'))
                            ->format(Format::using('link', ['website', 'website']));

                        echo $table->render([$row]);


                        $table = DataTable::createDetails('firstAid');
                        $table->setTitle(__('First Aid'));

                        $table->addColumn('firstAidQualified', __('First Aid Qualified'))
                            ->addClass('grid')
                            ->format(Format::using('yesNo', 'firstAidQualified'));
                        if ($row["firstAidQualified"] == "Y") {
                            $table->addColumn('firstAidQualification', __('First Aid Qualification'))
                                ->addClass('grid')
                                ->format(Format::using('truncate', 'firstAidQualification'));
                            $table->addColumn('firstAidExpiry', __('Expiry Date'))
                                ->format(function($row) {
                                    $output = Format::date($row['firstAidExpiry']);
                                    if ($row['firstAidExpiry'] <= date('Y-m-d')) {
                                        $output .= Format::tag(__('Expired'), 'warning ml-2');
                                    }
                                    else if ($row['firstAidExpiry'] > date('Y-m-d')) {
                                        $output .= Format::tag(__('Current'), 'success ml-2');
                                    }
                                    return $output;
                                });
                        }

                        echo $table->render([$row]);


                        $table = DataTable::createDetails("misc");
                        $table->setTitle(__('Miscellaneous'));

                        $table->addColumn('transport', __('Transport'));
                        $table->addColumn('vehicleRegistration', __('Vehicle Registration'));
                        $table->addColumn('lockerNumber', __('Locker Number'));

                        echo $table->render([$row]);

                        //Custom Fields
                        $fields = json_decode($row['fields'], true);
                        $resultFields = getCustomFields($connection2, $guid, false, true);
                        if ($resultFields->rowCount() > 0) {
                            echo '<h4>';
                            echo __('Custom Fields');
                            echo '</h4>';

                            $table = DataTable::createDetails('custom');

                            while ($rowFields = $resultFields->fetch()) {
                                $table->addColumn($rowFields['name'], __($rowFields['name']))
                                    ->format(function($row) use ($fields, $rowFields) {
                                        if (isset($fields[$rowFields['gibbonPersonFieldID']])) {
                                            if ($rowFields['type'] == 'date') {
                                                return Format::date($fields[$rowFields['gibbonPersonFieldID']]);
                                            } elseif ($rowFields['type'] == 'url') {
                                                return "<a target='_blank' href='".$fields[$rowFields['gibbonPersonFieldID']]."'>".$fields[$rowFields['gibbonPersonFieldID']].'</a>';
                                            } else {
                                                return $fields[$rowFields['gibbonPersonFieldID']];
                                            }
                                        }
                                        return '';
                                    });
                            }

                            echo $table->render([$row]);
                        }
                    } elseif ($subpage == 'Family') {
                        $familyGateway = $container->get(FamilyGateway::class);

                        // CRITERIA
                        $criteria = $familyGateway->newQueryCriteria()
                            ->sortBy(['gibbonFamily.name'])
                            ->fromPOST();

                        $families = $familyGateway->queryFamiliesByAdult($criteria, $gibbonPersonID);
                        $familyIDs = $families->getColumn('gibbonFamilyID');

                        // Join a set of data per family
                        $childrenData = $familyGateway->selectChildrenByFamily($familyIDs, true)->fetchGrouped();
                        $families->joinColumn('gibbonFamilyID', 'children', $childrenData);
                        $adultData = $familyGateway->selectAdultsByFamily($familyIDs, true)->fetchGrouped();
                        $families->joinColumn('gibbonFamilyID', 'adults', $adultData);

                        echo $page->fetchFromTemplate('profile/family.twig.html', [
                            'families' => $families,
                        ]);
                    } elseif ($subpage == 'Facilities') {
                        $staffFacilityGateway = $container->get(StaffFacilityGateway::class);
                        $criteria = $staffFacilityGateway->newQueryCriteria();
                        $facilities = $staffFacilityGateway->queryFacilitiesByPerson($criteria, $gibbon->session->get('gibbonSchoolYearID'), $gibbonPersonID);

                        $table = DataTable::create('facilities');

                        $table->addColumn('name', __('Name'));
                        $table->addColumn('phoneInternal', __('Extension'));
                        $table->addColumn('usageType', __("Usage"))
                            ->format(function($row) {
                                return __($row['usageType']);
                            });

                        echo $table->render($facilities);
                    } elseif ($subpage == 'Emergency Contacts') {
                        if ($highestActionManage != 'Manage Staff_confidential') {
                            echo "<div class='error'>";
                            echo __('You do not have access to this action.');
                            echo '</div>';
                        }
                        else {
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


                                $dataFamily = array('gibbonPersonID' => $gibbonPersonID);
                                $sqlFamily = 'SELECT * FROM gibbonFamily JOIN gibbonFamilyChild ON (gibbonFamily.gibbonFamilyID=gibbonFamilyChild.gibbonFamilyID) WHERE gibbonPersonID=:gibbonPersonID';
                                $resultFamily = $connection2->prepare($sqlFamily);
                                $resultFamily->execute($dataFamily);

                            if ($resultFamily->rowCount() != 1) {
                                echo "<div class='error'>";
                                echo __('There is no family information available for the current staff member.');
                                echo '</div>';
                            } else {
                                $rowFamily = $resultFamily->fetch();
                                $count = 1;
                                //Get adults

                                    $dataMember = array('gibbonFamilyID' => $rowFamily['gibbonFamilyID']);
                                    $sqlMember = 'SELECT * FROM gibbonFamilyAdult JOIN gibbonPerson ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonFamilyID=:gibbonFamilyID ORDER BY contactPriority, surname, preferredName';
                                    $resultMember = $connection2->prepare($sqlMember);
                                    $resultMember->execute($dataMember);

                                while ($rowMember = $resultMember->fetch()) {
                                    $table = DataTable::createDetails('family' . $count);

                                    $table->addColumn('preferredName', __('Name'))
                                        ->format(Format::using('name', ['title', 'preferredName', 'surname', 'Parent']));

                                    $table->addColumn('relationship', __('Relationship'))
                                        ->format(function($rowMember) {
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
                                        });

                                    $table->addColumn('phone', __('Contact By Phone'))
                                        ->format(function($rowMember) {
                                            $phones = '';

                                            for ($i = 1; $i < 5; ++$i) {
                                                if ($rowMember['phone'.$i] != '') {
                                                    $phones = $phones . Format::using('phone', ['phone' . $i, 'phone'.$i.'CountryCode', 'phone'.$i.'Type']) . '<br/>';
                                                }
                                            }

                                            return $phones;
                                        });

                                    echo $table->render([$rowMember]);

                                    ++$count;
                                }
                            }

                            $table = DataTable::createDetails('emergency');
                            $table->setTitle(__('Emergency Contacts'));

                            for ($i = 1; $i <= 2; $i++) {
                                $emergency = 'emergency' . $i;
                                $table->addColumn($emergency . 'Name', __('Contact ' . $i))
                                    ->format(function($row) use ($emergency) {
                                        if ($row[$emergency . 'Relationship'] != '') {
                                            return $row[$emergency . 'Name'] . ' (' . $row[$emergency . 'Relationship'] . ')';
                                        }
                                        return $row[$emergency . 'Name'];
                                    });

                                $table->addColumn($emergency . 'Number1', __('Number 1'));
                                $table->addColumn($emergency . 'Number2', __('Number 2'));
                            }

                            echo $table->render([$row]);
                        }

                    } elseif ($subpage == 'Activities') {

                        $highestActionActivities = getHighestGroupedAction($guid, '/modules/Activities/activities_attendance.php', $connection2);
                        $canAccessEnrolment = isActionAccessible($guid, $connection2, '/modules/Activities/activities_manage_enrolment.php');

                        // CRITERIA
                        $activityGateway = $container->get(ActivityGateway::class);
                        $criteria = $activityGateway->newQueryCriteria()
                            ->sortBy('name')
                            ->fromArray($_POST);

                        $activities = $activityGateway->queryActivitiesByParticipant($criteria, $_SESSION[$guid]['gibbonSchoolYearID'], $gibbonPersonID);

                        // DATA TABLE
                        $table = DataTable::createPaginated('myActivities', $criteria);

                        $table->addColumn('name', __('Activity'))
                            ->format(function ($activity) {
                                return $activity['name'].'<br/><span class="small emphasis">'.$activity['type'].'</span>';
                            });
                        $table->addColumn('role', __('Role'))
                            ->format(function ($activity) {
                                return !empty($activity['role']) ? $activity['role'] : __('Student');
                            });

                        $table->addColumn('status', __('Status'))
                            ->format(function ($activity) {
                                return !empty($activity['status']) ? $activity['status'] : '<i>'.__('N/A').'</i>';
                            });

                        $table->addActionColumn()
                            ->addParam('gibbonActivityID')
                            ->format(function ($activity, $actions) use ($highestActionActivities, $canAccessEnrolment) {
                                if ($activity['role'] == 'Organiser' &&  $canAccessEnrolment) {
                                    $actions->addAction('enrolment', __('Enrolment'))
                                        ->addParam('gibbonSchoolYearTermID', '')
                                        ->addParam('search', '')
                                        ->setIcon('config')
                                        ->setURL('/modules/Activities/activities_manage_enrolment.php');
                                }

                                $actions->addAction('view', __('View Details'))
                                    ->isModal(1000, 550)
                                    ->setURL('/modules/Activities/activities_my_full.php');

                                if ($highestActionActivities == "Enter Activity Attendance" ||
                                ($highestActionActivities == "Enter Activity Attendance_leader" && ($activity['role'] == 'Organiser' || $activity['role'] == 'Assistant' || $activity['role'] == 'Coach'))) {
                                    $actions->addAction('attendance', __('Attendance'))
                                        ->setIcon('attendance')
                                        ->setURL('/modules/Activities/activities_attendance.php');
                                }
                            });

                        echo $table->render($activities);

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

                    $page->addSidebarExtra($page->fetchFromTemplate('profile/sidebar.twig.html', [
                        'canViewEmergency' => ($highestActionManage == 'Manage Staff_confidential') ? true : false,
                        'userPhoto' => Format::userPhoto($row['image_240'], 240),
                        'canViewTimetable' => isActionAccessible($guid, $connection2, '/modules/Timetable/tt_view.php'),
                        'gibbonPersonID' => $gibbonPersonID,
                        'subpage' => $subpage,
                        'search' => $search,
                        'allStaff' => $allStaff,
                        'q' => $_GET['q'] ?? '',
                    ]));
                }
            }
        }
    }
}
