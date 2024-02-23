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
use Gibbon\Domain\DataSet;
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Forms\CustomFieldHandler;
use Gibbon\Domain\System\HookGateway;
use Gibbon\Domain\User\FamilyGateway;
use Gibbon\Domain\Staff\StaffAbsenceGateway;
use Gibbon\Domain\Activities\ActivityGateway;
use Gibbon\Domain\School\HouseGateway;
use Gibbon\Domain\Staff\StaffFacilityGateway;
use Gibbon\Domain\User\PersonalDocumentGateway;
use Gibbon\Domain\Staff\StaffAbsenceDateGateway;

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
        $page->addError(__('The highest grouped action cannot be determined.'));
    } else {
        $gibbonPersonID = $_GET['gibbonPersonID'] ?? '';
        $gibbonPersonID = str_pad($gibbonPersonID, 10, 0, STR_PAD_LEFT);

        if ($gibbonPersonID == '' ) {
            $page->addError(__('You have not specified one or more required parameters.'));
        } else {
            $hookGateway = $container->get(HookGateway::class);
            $search = $_GET['search'] ?? '';
            $allStaff = $_GET['allStaff'] ?? '';
            $hook = $_GET['hook'] ?? '';

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
                        $page->navigator->addSearchResultsAction(Url::fromModuleRoute('Staff', 'staff_view.php')->withQueryParam('search', $search));
                    }

                    // Overview
                    $table = DataTable::createDetails('overview');

                    $col = $table->addColumn('Basic Information');

                    $col->addColumn('preferredName', __('Name'))
                        ->format(Format::using('name', ['title', 'preferredName', 'surname', 'Parent']));
                    $col->addColumn('type', __('Staff Type'));
                    $col->addColumn('jobTitle', __('Job Title'));
                    $col->addColumn('email', __('Email'))->format(Format::using('link', 'email'));
                    $col->addColumn('website', __('Website'))->format(Format::using('link', 'website'));

                    $col = $table->addColumn('Biography', __('Biography'));

                    $col->addColumn('countryOfOrigin', __('Country Of Origin'));
                    $col->addColumn('qualifications', __('Qualifications'))->addClass('col-span-2');
                    $col->addColumn('biography', __('Biography'))->addClass('col-span-3');

                    echo $table->render([$row]);

                    $page->addSidebarExtra(Format::userPhoto($row['image_240'], 240));
                }
            } else {
                try {
                    $data = array('gibbonPersonID' => $gibbonPersonID);
                    if ($allStaff != 'on') {
                        $sql = "SELECT gibbonPerson.*, gibbonStaff.initials, gibbonStaff.type, gibbonStaff.jobTitle, countryOfOrigin, qualifications, biography, gibbonStaff.gibbonStaffID, firstAidQualified, firstAidQualification, firstAidExpiry, gibbonStaff.fields as fieldsStaff FROM gibbonPerson JOIN gibbonStaff ON (gibbonStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonPerson.gibbonPersonID=:gibbonPersonID";
                    } else {
                        $sql = 'SELECT gibbonPerson.*, gibbonStaff.initials, gibbonStaff.type, gibbonStaff.jobTitle, countryOfOrigin, qualifications, biography, gibbonStaff.gibbonStaffID, firstAidQualified, firstAidQualification, firstAidExpiry, gibbonStaff.fields as fieldsStaff FROM gibbonPerson JOIN gibbonStaff ON (gibbonStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPerson.gibbonPersonID=:gibbonPersonID';
                    }
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                }

                if ($result->rowCount() != 1) {
                    $page->addError(__('The selected record does not exist, or you do not have access to it.'));
                } else {
                    $row = $result->fetch();

                    $customFieldHandler = $container->get(CustomFieldHandler::class);
                    $hooks = $hookGateway->selectHooksByType('Staff Profile')->fetchGroupedUnique();
                    $hooks = array_map(function ($item) {
                        $item['options'] = unserialize($item['options']);
                        return $item;
                    }, $hooks);

                    $page->breadcrumbs
                        ->add(__('Staff Directory'), 'staff_view.php', ['search' => $search, 'allStaff' => $allStaff])
                        ->add(Format::name('', $row['preferredName'], $row['surname'], 'Student'));

                    $subpage = null;
                    if (isset($_GET['subpage'])) {
                        $subpage = $_GET['subpage'] ?? '';
                    }
                    if ($subpage == '' and $hook == '') {
                        $subpage = 'Overview';
                    }

                    if ($search != '') {
                        $page->navigator->addSearchResultsAction(Url::fromModuleRoute('Staff', 'staff_view.php')->withQueryParam('search', $search));
                    }

                    echo '<h2>';
                    if ($subpage != '') {
                        echo __($subpage);
                    } else {
                        echo $hook;
                    }
                    echo '</h2>';

                    if ($subpage == 'Overview') {
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

                        // Overview
                        $table = DataTable::createDetails('overview');

                        if (isActionAccessible($guid, $connection2, '/modules/User Admin/user_manage.php')) {
                            $table->addHeaderAction('edit', __('Edit User'))
                                ->setURL('/modules/User Admin/user_manage_edit.php')
                                ->addParam('gibbonPersonID', $gibbonPersonID)
                                ->displayLabel()
                                ->append(' | ');
                        }

                        if (isActionAccessible($guid, $connection2, '/modules/Staff/staff_manage.php')) {
                            $table->addHeaderAction('edit2', __('Edit Staff'))
                                ->setIcon('config')
                                ->setURL('/modules/Staff/staff_manage_edit.php')
                                ->addParam('gibbonStaffID', $row['gibbonStaffID'])
                                ->displayLabel();
                        }

                        $col = $table->addColumn('Basic Information');

                        $col->addColumn('preferredName', __('Name'))
                            ->format(Format::using('name', ['title', 'preferredName', 'surname', 'Parent']));
                        $col->addColumn('type', __('Staff Type'))->translatable();
                        $col->addColumn('jobTitle', __('Job Title'));
                        $col->addColumn('username', __('Username'));
                        $col->addColumn('email', __('Email'))->format(Format::using('link', 'email'));
                        if (!empty($row['website'])) {
                            $col->addColumn('website', __('Website'))->format(Format::using('link', 'website'));
                        }

                        if (!empty($row['gibbonHouseID'])) {
                            $house = $container->get(HouseGateway::class)->getByID($row['gibbonHouseID'], ['name']);
                            $row['houseName'] = $house['name'] ?? '';
                            $col->addColumn('houseName', __('House'));
                        }

                        

                        $col = $table->addColumn('Biography', __('Biography'));

                        $col->addColumn('countryOfOrigin', __('Country Of Origin'));
                        $col->addColumn('qualifications', __('Qualifications'))->addClass('col-span-2');
                        $col->addColumn('biography', __('Biography'))->addClass('col-span-3');

                        // Custom Fields
                        $customFieldHandler->addCustomFieldsToTable($table, 'Staff', ['heading' => 'Other Information', 'withHeading' => ['Basic Information', 'Biography']], $row['fieldsStaff']);

                        // Append the first aid details
                        $headingCol = $table->getColumn('Basic Information');
                        $headingCol->addColumn('firstAidQualified', __('First Aid Qualified'))
                            ->addClass('grid')
                            ->format(Format::using('yesNo', 'firstAidQualified'));

                        echo $table->render([$row]);

                        // Show timetable
                        echo "<a name='timetable'></a>";
                        echo '<h4>';
                        echo __('Timetable');
                        echo '</h4>';
                        if (isActionAccessible($guid, $connection2, '/modules/Timetable/tt_view.php') == true) {
                            $table = DataTable::createDetails('timetable');

                            if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/courseEnrolment_manage_byPerson_edit.php') == true) {
                                $table->addHeaderAction('edit', __('Edit'))
                                    ->setURL('/modules/Timetable Admin/courseEnrolment_manage_byPerson_edit.php')
                                    ->addParam('gibbonPersonID', $gibbonPersonID)
                                    ->addParam('gibbonSchoolYearID', $session->get('gibbonSchoolYearID'))
                                    ->addParam('type', 'Staff')
                                    ->addParam('allUsers', '')
                                    ->displayLabel();
                            }

                            if ($gibbonPersonID == $session->get('gibbonPersonID')) {
                                $table->addHeaderAction('export', __('Export'))
                                    ->modalWindow()
                                    ->setURL('/modules/Timetable/tt_manage_subscription.php')
                                    ->addParam('gibbonPersonID', $gibbonPersonID)
                                    ->setIcon('download')
                                    ->displayLabel()
                                    ->prepend(' | ');
                            }

                            echo $table->render(['' => '']);

                            include './modules/Timetable/moduleFunctions.php';
                            $ttDate = '';
                            if (isset($_POST['ttDate'])) {
                                $ttDate = Format::timestamp(Format::dateConvert($_POST['ttDate']));
                            }
                            $gibbonTTID = null;
                            if (isset($_GET['gibbonTTID'])) {
                                $gibbonTTID = $_GET['gibbonTTID'] ?? '';
                            }
                            $tt = renderTT($guid, $connection2, $gibbonPersonID, $gibbonTTID, false, $ttDate, '/modules/Staff/staff_view_details.php', "&gibbonPersonID=$gibbonPersonID&search=$search#timetable");
                            if ($tt != false) {
                                echo $tt;
                            } else {
                                $page->addError(__('The selected record does not exist, or you do not have access to it.'));
                            }
                        }
                    } elseif ($subpage == 'Personal') {
                        $table = DataTable::createDetails('personal');

                        if (isActionAccessible($guid, $connection2, '/modules/User Admin/user_manage.php')) {
                            $table->addHeaderAction('edit', __('Edit User'))
                                ->setURL('/modules/User Admin/user_manage_edit.php')
                                ->addParam('gibbonPersonID', $gibbonPersonID)
                                ->displayLabel()
                                ->append(' | ');
                        }

                        if (isActionAccessible($guid, $connection2, '/modules/Staff/staff_manage.php')) {
                            $table->addHeaderAction('edit2', __('Edit Staff'))
                                ->setIcon('config')
                                ->setURL('/modules/Staff/staff_manage_edit.php')
                                ->addParam('gibbonStaffID', $row['gibbonStaffID'])
                                ->displayLabel();
                        }

                        $col = $table->addColumn('Basic Information');

                        $col->addColumn('preferredName', __('Name'))
                            ->format(Format::using('name', ['title', 'preferredName', 'surname', 'Parent']));
                        $col->addColumn('type', __('Staff Type'))->translatable();
                        $col->addColumn('jobTitle', __('Job Title'));
                        $col->addColumn('initials', __('Initials'));
                        $col->addColumn('gender', __('Gender'))
                            ->format(Format::using('genderName', 'gender'));
                        $col->addColumn('initials', __('Initials'));

                        $col = $table->addColumn('Contacts', __('Contacts'));

                        for ($i = 1; $i < 5; ++$i) {
                            if (empty($row['phone' . $i])) continue;
                            if ($row['phone' . $i] != '') {
                                $col->addColumn('phone' . $i, __('Phone') . " $i")
                                    ->format(Format::using('phone', ['phone' . $i, 'phone'.$i.'CountryCode', 'phone'.$i.'Type']));
                            }
                        }

                        $col->addColumn('email', __('Email'))
                            ->format(Format::using('link', ['mailto:' . $row['email'], 'email']));

                        $col->addColumn('emailAlternate', __('Alternate Email'))
                            ->format(function($row) {
                                if ($row['emailAlternate'] != '') {
                                    return Format::link('mailto:' . $row['emailAlternate'], $row['emailAlternate']);
                                }
                                return '';
                            });

                        $col->addColumn('website', __('Website'))
                            ->format(Format::using('link', ['website', 'website']));


                        $col = $table->addColumn('First Aid', __('First Aid'));

                        $col->addColumn('firstAidQualified', __('First Aid Qualified'))
                            ->addClass('grid')
                            ->format(Format::using('yesNo', 'firstAidQualified'));
                        if ($row["firstAidQualified"] == "Y") {
                            $col->addColumn('firstAidQualification', __('First Aid Qualification'))
                                ->addClass('grid')
                                ->format(Format::using('truncate', 'firstAidQualification'));
                            $col->addColumn('firstAidExpiry', __('Expiry Date'))
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

                        $col = $table->addColumn('Miscellaneous', __('Miscellaneous'));

                        $col->addColumn('transport', __('Transport'));
                        $col->addColumn('vehicleRegistration', __('Vehicle Registration'));
                        $col->addColumn('lockerNumber', __('Locker Number'));

                        // CUSTOM FIELDS
                        $customFieldHandler->addCustomFieldsToTable($table, 'Staff', ['withoutHeading' => ['Biography']], $row['fieldsStaff']);
                        $customFieldHandler->addCustomFieldsToTable($table, 'Person', ['staff' => 1], $row['fields']);

                        echo $table->render([$row]);

                        // PERSONAL DOCUMENTS
                        if ($highestActionManage == 'Manage Staff_confidential') {
                            $params = ['staff' => true, 'notEmpty' => true];
                            $documents = $container->get(PersonalDocumentGateway::class)->selectPersonalDocuments('gibbonPerson', $gibbonPersonID, $params)->fetchAll();

                            echo $page->fetchFromTemplate('ui/personalDocuments.twig.html', ['documents' => $documents]);
                        }

                    } elseif ($subpage == 'Family') {
                        $familyGateway = $container->get(FamilyGateway::class);

                        // CRITERIA
                        $criteria = $familyGateway->newQueryCriteria()
                            ->sortBy(['gibbonFamily.name'])
                            ->fromPOST('family');

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
                        $facilities = $staffFacilityGateway->queryFacilitiesByPerson($criteria, $session->get('gibbonSchoolYearID'), $gibbonPersonID);

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
                            $page->addError(__('You do not have access to this action.'));
                        }
                        else {
                            if (isActionAccessible($guid, $connection2, '/modules/User Admin/user_manage.php') == true) {
                                echo "<div class='linkTop'>";
                                echo "<a href='".$session->get('absoluteURL')."/index.php?q=/modules/User Admin/user_manage_edit.php&gibbonPersonID=$gibbonPersonID'>".__('Edit')."<img style='margin: 0 0 -4px 5px' title='".__('Edit')."' src='./themes/".$session->get('gibbonThemeName')."/img/config.png'/></a> ";
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
                                                    echo __($rowMember['role']);
                                                }
                                            } else {
                                                echo __($rowMember['role']);
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
                                            return $row[$emergency . 'Name'] . ' (' . __($row[$emergency . 'Relationship']) . ')';
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

                        $activities = $activityGateway->queryActivitiesByParticipant($criteria, $session->get('gibbonSchoolYearID'), $gibbonPersonID);

                        // DATA TABLE
                        $table = DataTable::createPaginated('myActivities', $criteria);

                        $table->addColumn('name', __('Activity'))
                            ->format(function ($activity) {
                                return $activity['name'].'<br/><span class="small emphasis">'.$activity['type'].'</span>';
                            });
                        $table->addColumn('role', __('Role'))
                            ->format(function ($activity) {
                                return !empty($activity['role']) ? __($activity['role']) : __('Student');
                            });

                        $table->addColumn('status', __('Status'))
                            ->format(function ($activity) {
                                return !empty($activity['status']) ? __($activity['status']) : '<i>'.__('N/A').'</i>';
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
                            $page->addError(__('The selected record does not exist, or you do not have access to it.'));
                        } else {
                            if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/courseEnrolment_manage_byPerson_edit.php') == true) {
                                echo "<div class='linkTop'>";
                                echo "<a href='".$session->get('absoluteURL')."/index.php?q=/modules/Timetable Admin/courseEnrolment_manage_byPerson_edit.php&gibbonPersonID=$gibbonPersonID&gibbonSchoolYearID=".$session->get('gibbonSchoolYearID')."&type=Staff&allUsers='>".__('Edit')."<img style='margin: 0 0 -4px 5px' title='".__('Edit')."' src='./themes/".$session->get('gibbonThemeName')."/img/config.png'/></a> ";
                                echo '</div>';
                            }

                            include './modules/Timetable/moduleFunctions.php';
                            $ttDate = '';
                            if (isset($_POST['ttDate'])) {
                                $ttDate = Format::timestamp(Format::dateConvert($_POST['ttDate']));
                            }
                            $gibbonTTID = null;
                            if (isset($_GET['gibbonTTID'])) {
                                $gibbonTTID = $_GET['gibbonTTID'] ?? '';
                            }
                            $tt = renderTT($guid, $connection2, $gibbonPersonID, $gibbonTTID, false, $ttDate, '/modules/Staff/staff_view_details.php', "&gibbonPersonID=$gibbonPersonID&subpage=Timetable&search=$search");
                            if ($tt != false) {
                                echo $tt;
                            } else {
                                $page->addError(__('The selected record does not exist, or you do not have access to it.'));
                            }
                        }
                    }

                    // Handle Staff Profile Hooks
                    if (!empty($hook)) {
                        $rowHook = $hookGateway->getByID($_GET['gibbonHookID'] ?? '');
                        if (empty($rowHook)) {
                            echo $page->getBlankSlate();
                        } else {
                            $options = unserialize($rowHook['options']);

                            // Check for permission to hook
                            $hookPermission = $hookGateway->getHookPermission($rowHook['gibbonHookID'], $session->get('gibbonRoleIDCurrent'), $options['sourceModuleName'] ?? '', $options['sourceModuleAction'] ?? '');

                            if (empty($options) || empty($hookPermission)) {
                                echo Format::alert(__('Your request failed because you do not have access to this action.'), 'error');
                            } else {
                                $include = $session->get('absolutePath').'/modules/'.$options['sourceModuleName'].'/'.$options['sourceModuleInclude'];
                                if (!file_exists($include)) {
                                    echo Format::alert(__('The selected page cannot be displayed due to a hook error.'), 'error');
                                } else {
                                    include $include;
                                }
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
                        'hooks' => $hooks,
                        'currentHook' => $hook,
                        'q' => $_GET['q'] ?? '',
                    ]));
                }
            }
        }
    }
}
