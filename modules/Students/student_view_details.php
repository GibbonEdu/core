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

use Gibbon\Domain\System\HookGateway;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\User\RoleGateway;
use Gibbon\Http\Url;
use Gibbon\Services\Format;
use Gibbon\Services\ModuleLoader;
use Gibbon\Support\Facades\Access;
use Gibbon\UI\Components\Alert;

//Module includes for User Admin (for custom fields)
include './modules/User Admin/moduleFunctions.php';

if (!Access::allows('Students', 'student_view_details')) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    $page->scripts->add('chart');

    $roleGateway = $container->get(RoleGateway::class);

    //Get action with highest precendence
    $highestAction = Access::get('Students', 'student_view_details');
    if (empty($highestAction)) {
        $page->addError(__('The highest grouped action cannot be determined.'));
        return;
    } else {
        $gibbonPersonID = $_GET['gibbonPersonID'] ?? '';
        $search = $_GET['search'] ?? '';
        $allStudents = $_GET['allStudents'] ?? '';
        $sort = $_GET['sort'] ?? '';

        if ($gibbonPersonID == '') {
            $page->addError(__('You have not specified one or more required parameters.'));
            return;
        } else {
            $settingGateway = $container->get(SettingGateway::class);
            $hookGateway = $container->get(HookGateway::class);
            $enableStudentNotes = $settingGateway->getSettingByScope('Students', 'enableStudentNotes');
            $skipBrief = false;

            //Skip brief for those with _full or _fullNoNotes, and _brief
            if ($highestAction->allowsAny('View Student Profile_full', 'View Student Profile_fullEditAllNotes', 'View Student Profile_fullNoNotes')) {
                $skipBrief = true;
            }

            //Test if View Student Profile_myChildren is available and parent has access to this student...if so, skip brief, and go to full.
            if (Access::allows('Students', 'student_view_details', 'View Student Profile_myChildren')) {
                    $data = ['gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'gibbonPersonID1' => $_GET['gibbonPersonID'], 'gibbonPersonID2' => $session->get('gibbonPersonID')];
                    $sql = "SELECT * FROM gibbonFamilyChild JOIN gibbonFamily ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonFamilyChild.gibbonPersonID=:gibbonPersonID1 AND gibbonFamilyAdult.gibbonPersonID=:gibbonPersonID2 AND childDataAccess='Y'";
                    $result = $pdo->select($sql, $data);
                if ($result->rowCount() == 1) {
                    $skipBrief = true;
                }
            }

            if (Access::allows('Students', 'student_view_details', 'View Student Profile_my')) {
                if ($gibbonPersonID == $session->get('gibbonPersonID')) {
                    $skipBrief = true;
                } elseif (Access::allows('Students', 'student_view_details', 'View Student Profile_brief')) {
                   $skipBrief = false;
                } else {
                    //Acess denied
                    $page->addError(__('You do not have access to this action.'));
                    return;
                }
            }

            if (Access::allows('Students', 'student_view_details', 'View Student Profile_brief') && $skipBrief == false) {
                //Proceed!
                $data = ['gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'gibbonPersonID' => $gibbonPersonID];
                $sql = "SELECT gibbonPerson.*, gibbonStudentEnrolment.gibbonSchoolYearID, gibbonStudentEnrolment.gibbonYearGroupID, gibbonStudentEnrolment.gibbonFormGroupID, gibbonStudentEnrolment.rollOrder FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonPerson.gibbonPersonID=:gibbonPersonID";
                $result = $pdo->select($sql, $data);

                if ($result->rowCount() != 1) {
                    $page->addError(__('The selected record does not exist, or you do not have access to it.'));
                } else {
                    $row = $result->fetch();
                    $studentImage=$row['image_240'] ;

                    $page->breadcrumbs
                        ->add(__('View Student Profiles'), 'student_view.php')
                        ->add(Format::name('', $row['preferredName'], $row['surname'], 'Student'));

                    echo "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>";
                    echo '<tr>';
                    echo "<td style='width: 33%; vertical-align: top'>";
                    echo "<span style='font-size: 115%; font-weight: bold'>".__('Year Group').'</span><br/>';

                    $dataDetail = ['gibbonYearGroupID' => $row['gibbonYearGroupID']];
                    $sqlDetail = 'SELECT * FROM gibbonYearGroup WHERE gibbonYearGroupID=:gibbonYearGroupID';
                    $resultDetail = $pdo->select($sqlDetail, $dataDetail);
                    if ($rowDetail = $resultDetail->fetch()) {
                        echo __($rowDetail['name']);
                    }
                    echo '</td>';
                    echo "<td style='width: 34%; vertical-align: top'>";
                    echo "<span style='font-size: 115%; font-weight: bold'>".__('Form Group').'</span><br/>';

                    $dataDetail = ['gibbonFormGroupID' => $row['gibbonFormGroupID']];
                    $sqlDetail = 'SELECT * FROM gibbonFormGroup WHERE gibbonFormGroupID=:gibbonFormGroupID';
                    $resultDetail = $pdo->select($sqlDetail, $dataDetail);
                    if ($rowDetail = $resultDetail->fetch()) {
                        echo $rowDetail['name'];
                    }
                    echo '</td>';
                    echo "<td style='width: 34%; vertical-align: top'>";
                    echo "<span style='font-size: 115%; font-weight: bold'>".__('House').'</span><br/>';

                    $dataDetail = ['gibbonHouseID' => $row['gibbonHouseID']];
                    $sqlDetail = 'SELECT * FROM gibbonHouse WHERE gibbonHouseID=:gibbonHouseID';
                    $resultDetail = $pdo->select($sqlDetail, $dataDetail);
                    if ($rowDetail = $resultDetail->fetch()) {
                        echo $rowDetail['name'];
                    }
                    echo '</td>';
                    echo '</tr>';
                    echo '<tr>';
                    echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                    echo "<span style='font-size: 115%; font-weight: bold'>".__('Email').'</span><br/>';
                    if ($row['email'] != '') {
                        $row['email'] = filter_var(trim($row['email']), FILTER_SANITIZE_EMAIL);
                        echo "<i><a href='mailto:".$row['email']."'>".$row['email'].'</a></i>';
                    }
                    echo '</td>';
                    echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                    echo "<span style='font-size: 115%; font-weight: bold'>".__('Website').'</span><br/>';
                    if ($row['website'] != '') {
                        echo "<i><a href='".$row['website']."'>".$row['website'].'</a></i>';
                    }
                    echo '</td>';
                    echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'></td>";
                    echo '</tr>';
                    echo '</table>';

                    //Set sidebar
                    $session->set('sidebarExtra', Format::userPhoto($row['image_240'], 240));
                }
                return;
            } else {
                try {
                    if ($highestAction->allows('View Student Profile_myChildren') ) {
                        $data = ['gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'gibbonPersonID1' => $_GET['gibbonPersonID'], 'gibbonPersonID2' => $session->get('gibbonPersonID'), 'today' => date('Y-m-d')];
                        $sql = "SELECT gibbonPerson.*, gibbonStudentEnrolment.gibbonSchoolYearID, gibbonStudentEnrolment.gibbonYearGroupID, gibbonStudentEnrolment.gibbonFormGroupID, gibbonStudentEnrolment.rollOrder FROM gibbonFamilyChild
                            JOIN gibbonFamily ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID)
                            JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID)
                            JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID)
                            JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID)
                            WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPerson.status='Full'
                            AND (dateStart IS NULL OR dateStart<=:today) AND (dateEnd IS NULL  OR dateEnd>=:today)
                            AND gibbonFamilyChild.gibbonPersonID=:gibbonPersonID1
                            AND gibbonFamilyAdult.gibbonPersonID=:gibbonPersonID2
                            AND childDataAccess='Y'";
                    } elseif ($highestAction->allows('View Student Profile_my')) {
                        $gibbonPersonID = $session->get('gibbonPersonID');
                        $data = ['gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'gibbonPersonID' => $gibbonPersonID, 'today' => date('Y-m-d')];
                        $sql = "SELECT gibbonPerson.*, gibbonStudentEnrolment.gibbonSchoolYearID, gibbonStudentEnrolment.gibbonYearGroupID, gibbonStudentEnrolment.gibbonFormGroupID, gibbonStudentEnrolment.rollOrder FROM gibbonPerson
                            LEFT JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID)
                            WHERE gibbonPerson.gibbonPersonID=:gibbonPersonID
                            AND gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPerson.status='Full'
                            AND (dateStart IS NULL OR dateStart<=:today) AND (dateEnd IS NULL OR dateEnd>=:today)";
                    } elseif ($highestAction->allowsAny('View Student Profile_full', 'View Student Profile_fullEditAllNotes', 'View Student Profile_fullNoNotes')) {
                        if ($allStudents != 'on') {
                            $data = ['gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'gibbonPersonID' => $gibbonPersonID, 'today' => date('Y-m-d')];
                            $sql = "SELECT gibbonPerson.*, gibbonStudentEnrolment.gibbonSchoolYearID, gibbonStudentEnrolment.gibbonYearGroupID, gibbonStudentEnrolment.gibbonFormGroupID, gibbonStudentEnrolment.rollOrder FROM gibbonPerson
                                JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID)
                                WHERE gibbonSchoolYearID=:gibbonSchoolYearID
                                AND gibbonPerson.gibbonPersonID=:gibbonPersonID AND status='Full'
                                AND (dateStart IS NULL OR dateStart<=:today) AND (dateEnd IS NULL  OR dateEnd>=:today) ";
                        } else {
                            $data = ['gibbonPersonID' => $gibbonPersonID, 'gibbonSchoolYearID' => $session->get('gibbonSchoolYearID')];
                            $sql = "SELECT gibbonPerson.*, gibbonStudentEnrolment.gibbonSchoolYearID, gibbonStudentEnrolment.gibbonYearGroupID, gibbonStudentEnrolment.gibbonFormGroupID, gibbonStudentEnrolment.rollOrder FROM gibbonPerson
                                LEFT JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID)
                                WHERE gibbonPerson.gibbonPersonID=:gibbonPersonID";
                        }
                    } else {
                        //Acess denied
                        $page->addError(__('You do not have access to this action.'));
                        return;
                    }
                    $result = $pdo->select($sql, $data);
                } catch (PDOException $e) {
                    return;
                }

                if ($result->rowCount() != 1) {
                    $page->addError(__('The selected record does not exist, or you do not have access to it.'));
                    return;
                } else {
                    $row = $result->fetch();
                    $studentImage=$row['image_240'] ;

                    $page->breadcrumbs
                    ->add(__('View Student Profiles'), 'student_view.php')
                    ->add(Format::name('', $row['preferredName'], $row['surname'], 'Student'));

                    $subpage = $_GET['subpage'] ?? '';
                    $hook = $_GET['hook'] ?? '';

                    // When viewing left students, they won't have a year group ID
                    if (empty($row['gibbonYearGroupID'])) {
                        $row['gibbonYearGroupID'] = '';
                    }

                    if ($subpage == '' and $hook == '') {
                        $subpage = 'Overview';
                    }

                    if ($search != '' or $allStudents != '') {
                         $params = [
                            "search" => $search,
                            "allStudents" => $allStudents,
                        ];
                        $page->navigator->addSearchResultsAction(Url::fromModuleRoute('Students', 'student_view.php')->withQueryParams($params));
                    }

                    // Map subpage names to class names for class-based routing
                    $subpageClasses = [
                        'Overview' => \Gibbon\Module\Students\Profile\OverviewPage::class,
                        'Personal' => \Gibbon\Module\Students\Profile\PersonalPage::class,
                        'Family' => \Gibbon\Module\Students\Profile\FamilyPage::class,
                        'Emergency Contacts' => \Gibbon\Module\Students\Profile\EmergencyContactsPage::class,
                        'Medical' => \Gibbon\Module\Students\Profile\MedicalPage::class,
                        'First Aid' => \Gibbon\Module\Students\Profile\FirstAidPage::class,
                        'Notes' => \Gibbon\Module\Students\Profile\NotesPage::class,
                        'Attendance' => \Gibbon\Module\Students\Profile\AttendancePage::class,
                        'Markbook' => \Gibbon\Module\Students\Profile\MarkbookPage::class,
                        'Internal Assessment' => \Gibbon\Module\Students\Profile\InternalAssessmentPage::class,
                        'External Assessment' => \Gibbon\Module\Students\Profile\ExternalAssessmentPage::class,
                        'Reports' => \Gibbon\Module\Students\Profile\ReportsPage::class,
                        'Individual Needs' => \Gibbon\Module\Students\Profile\IndividualNeedsPage::class,
                        'Library Borrowing' => \Gibbon\Module\Students\Profile\LibraryBorrowingPage::class,
                        'Timetable' => \Gibbon\Module\Students\Profile\TimetablePage::class,
                        'Activities' => \Gibbon\Module\Students\Profile\ActivitiesPage::class,
                        'Homework' => \Gibbon\Module\Students\Profile\HomeworkPage::class,
                        'Behaviour' => \Gibbon\Module\Students\Profile\BehaviourPage::class,
                    ];

                    echo '<h2>';
                    if ($subpage == 'Homework') {
                        $homeworkNamePlural = $settingGateway->getSettingByScope('Planner', 'homeworkNamePlural');
                        echo __($homeworkNamePlural);
                    } elseif ($subpage != '') {
                        echo __($subpage);
                    } else {
                        echo $hook;
                    }
                    echo '</h2>';

                    // Handle hook-based subpages (third-party integrations)
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
                    } elseif (isset($subpageClasses[$subpage])) {

                        $container->get(ModuleLoader::class)->registerModuleNamespace('Attendance');
                        $container->get(ModuleLoader::class)->registerModuleNamespace('Reports');
                        $container->get(ModuleLoader::class)->registerModuleNamespace('Planner');

                        // Handle class-based subpages
                        $pageClass = $subpageClasses[$subpage];
                        $profilePage = $container->get($pageClass);

                        
                        
                        // Set student context
                        $profilePage->setStudent($session->get('gibbonSchoolYearID'), $gibbonPersonID);
                        
                        // Check access
                        if (!$profilePage->checkAccess()) {
                            echo Format::alert(__('Your request failed because you do not have access to this action.'), 'error');
                        } else {
                            // Render output
                            echo $profilePage->getOutput();
                        }
                    } elseif ($subpage != '') {
                        // Invalid subpage
                        echo Format::alert(__('Invalid subpage specified.'), 'error');
                    }

                    //Set sidebar
                    $session->set('sidebarExtra', '');

                    // Prepare alert bar
                    $alert = '';
                    if ($highestAction->allowsAny('View Student Profile_full', 'View Student Profile_fullEditAllNotes', 'View Student Profile_fullNoNotes')) {
                        $alert = $container->get(Alert::class)->getAlertBar($gibbonPersonID, ['wrap' => false, 'large' => true]);
                    }

                    // Build base URL for menu items
                    $baseURL = $session->get('absoluteURL').'/index.php?q='.$_GET['q']."&gibbonPersonID=$gibbonPersonID&search=$search&allStudents=$allStudents";

                    // Build personal menu items
                    $personalMenu = [];
                    
                    $personalMenu[] = [
                        'name' => 'Overview',
                        'url' => $baseURL.'&subpage=Overview',
                        'active' => $subpage == 'Overview'
                    ];
                    
                    $personalMenu[] = [
                        'name' => 'Personal',
                        'url' => $baseURL.'&subpage=Personal',
                        'active' => $subpage == 'Personal'
                    ];
                    
                    $personalMenu[] = [
                        'name' => 'Family',
                        'url' => $baseURL.'&subpage=Family',
                        'active' => $subpage == 'Family'
                    ];
                    
                    $personalMenu[] = [
                        'name' => 'Emergency Contacts',
                        'url' => $baseURL.'&subpage=Emergency Contacts',
                        'active' => $subpage == 'Emergency Contacts'
                    ];
                    
                    // Medical - only show if NOT "View Student Profile_my"
                    if (!Access::allows('Students', 'student_view_details', 'View Student Profile_my')) {
                        $personalMenu[] = [
                            'name' => 'Medical',
                            'url' => $baseURL.'&subpage=Medical',
                            'active' => $subpage == 'Medical'
                        ];
                    }
                    
                    // First Aid - check permission
                    if (Access::allows('Students', 'firstAidRecord')) {
                        $personalMenu[] = [
                            'name' => 'First Aid',
                            'url' => $baseURL.'&subpage=First Aid',
                            'active' => $subpage == 'First Aid'
                        ];
                    }
                    
                    // Notes - check permission and setting
                    if (Access::allows('Students', 'student_view_details_notes_add') && $enableStudentNotes == 'Y') {
                        $personalMenu[] = [
                            'name' => 'Notes',
                            'url' => $baseURL.'&subpage=Notes',
                            'active' => $subpage == 'Notes'
                        ];
                    }

                    // Get all modules with categories
                    $dataMenu = [];
                    $sqlMenu = "SELECT gibbonModuleID, category, name FROM gibbonModule WHERE active='Y' ORDER BY category, name";
                    $resultMenu = $pdo->select($sqlMenu, $dataMenu);
                    
                    $mainMenu = [];
                    while ($rowMenu = $resultMenu->fetch()) {
                        $mainMenu[$rowMenu['name']] = $rowMenu['category'];
                    }

                    // Build dynamic menu items array
                    $studentMenuItems = [];

                    // Markbook
                    if (Access::allows('Markbook', 'markbook_view')) {
                        $studentMenuItems[] = [
                            'category' => $mainMenu['Markbook'],
                            'name' => 'Markbook',
                            'url' => $baseURL.'&subpage=Markbook',
                            'active' => $subpage == 'Markbook'
                        ];
                    }

                    // Internal Assessment
                    if (Access::allows('Formal Assessment', 'internalAssessment_view')) {
                        $studentMenuItems[] = [
                            'category' => $mainMenu['Formal Assessment'],
                            'name' => 'Internal Assessment',
                            'url' => $baseURL.'&subpage=Internal%20Assessment',
                            'active' => $subpage == 'Internal Assessment'
                        ];
                    }

                    // External Assessment
                    if (Access::allows('Formal Assessment', 'externalAssessment_details') || Access::allows('Formal Assessment', 'externalAssessment_view')) {
                        $studentMenuItems[] = [
                            'category' => $mainMenu['Formal Assessment'],
                            'name' => 'External Assessment',
                            'url' => $baseURL.'&subpage=External Assessment',
                            'active' => $subpage == 'External Assessment'
                        ];
                    }

                    // Reports
                    if (Access::allows('Reports', 'archive_byStudent_view')) {
                        $studentMenuItems[] = [
                            'category' => $mainMenu['Reports'],
                            'name' => 'Reports',
                            'url' => $baseURL.'&subpage=Reports',
                            'active' => $subpage == 'Reports'
                        ];
                    }

                    // Activities
                    if (Access::allows('Activities', 'report_activityChoices_byStudent') 
                        || Access::allows('Activities', 'activities_view_myChildren') 
                        || Access::allows('Activities', 'activities_my')) {
                        $studentMenuItems[] = [
                            'category' => $mainMenu['Activities'],
                            'name' => 'Activities',
                            'url' => $baseURL.'&subpage=Activities',
                            'active' => $subpage == 'Activities'
                        ];
                    }

                    // Homework
                    if (Access::allows('Planner', 'planner_edit') || Access::allows('Planner', 'planner_view_full')) {
                        $homeworkNamePlural = $settingGateway->getSettingByScope('Planner', 'homeworkNamePlural');
                        $studentMenuItems[] = [
                            'category' => $mainMenu['Planner'],
                            'name' => $homeworkNamePlural,
                            'url' => $baseURL.'&subpage=Homework',
                            'active' => $subpage == 'Homework'
                        ];
                    }

                    // Individual Needs
                    if (Access::allows('Individual Needs', 'in_view')) {
                        $studentMenuItems[] = [
                            'category' => $mainMenu['Individual Needs'],
                            'name' => 'Individual Needs',
                            'url' => $baseURL.'&subpage=Individual Needs',
                            'active' => $subpage == 'Individual Needs'
                        ];
                    }

                    // Library Borrowing
                    if (Access::allows('Library', 'library_browse')) {
                        $studentMenuItems[] = [
                            'category' => $mainMenu['Library'],
                            'name' => 'Library Borrowing',
                            'url' => $baseURL.'&subpage=Library Borrowing',
                            'active' => $subpage == 'Library Borrowing'
                        ];
                    }

                    // Timetable
                    if (Access::allows('Timetable', 'tt_view')) {
                        $studentMenuItems[] = [
                            'category' => $mainMenu['Timetable'],
                            'name' => 'Timetable',
                            'url' => $baseURL.'&subpage=Timetable',
                            'active' => $subpage == 'Timetable'
                        ];
                    }

                    // Attendance
                    if (Access::allows('Attendance', 'report_studentHistory')) {
                        $studentMenuItems[] = [
                            'category' => $mainMenu['Attendance'],
                            'name' => 'Attendance',
                            'url' => $baseURL.'&subpage=Attendance',
                            'active' => $subpage == 'Attendance'
                        ];
                    }

                    // Behaviour
                    if (Access::allows('Behaviour', 'behaviour_view')) {
                        $studentMenuItems[] = [
                            'category' => $mainMenu['Behaviour'],
                            'name' => 'Behaviour',
                            'url' => $baseURL.'&subpage=Behaviour',
                            'active' => $subpage == 'Behaviour'
                        ];
                    }

                    // Check for hooks and add them to the menu
                    $hooks = $hookGateway->selectHooksByType('Student Profile')->fetchGroupedUnique();

                    if (!empty($hooks)) {
                        foreach ($hooks as $rowHook) {
                            if (empty($rowHook) || empty($rowHook['options'])) continue;

                            $options = unserialize($rowHook['options']);
                            $hookPermission = $hookGateway->getHookPermission($rowHook['gibbonHookID'], $session->get('gibbonRoleIDCurrent'), $options['sourceModuleName'] ?? '', $options['sourceModuleAction'] ?? '');

                            if (!empty($hookPermission)) {
                                $studentMenuItems[] = [
                                    'category' => $mainMenu[$options['sourceModuleName']],
                                    'name' => $rowHook['name'],
                                    'url' => $baseURL.'&hook='.$rowHook['name'].'&module='.$options['sourceModuleName'].'&action='.$options['sourceModuleAction'].'&gibbonHookID='.$rowHook['gibbonHookID'],
                                    'active' => $hook == $rowHook['name']
                                ];
                            }
                        }
                    }

                    // Sort menu items by category and name
                    usort($studentMenuItems, function($a, $b) {
                        if ($a['category'] == $b['category']) {
                            return strcmp($a['name'], $b['name']);
                        }
                        return strcmp($a['category'], $b['category']);
                    });

                    // Group menu items by category according to mainMenuCategoryOrder
                    $mainMenuCategoryOrder = $settingGateway->getSettingByScope('System', 'mainMenuCategoryOrder');
                    $orders = explode(',', $mainMenuCategoryOrder);

                    $dynamicMenuSections = [];
                    foreach ($orders as $order) {
                        $categoryItems = array_filter($studentMenuItems, function($item) use ($order) {
                            return $item['category'] == $order;
                        });

                        if (!empty($categoryItems)) {
                            $dynamicMenuSections[] = [
                                'category' => $order,
                                'items' => array_values($categoryItems)
                            ];
                        }
                    }

                    // Prepare data for Twig template
                    $sidebarData = [
                        'alert' => $alert,
                        'studentImage' => Format::userPhoto($studentImage, 240),
                        'personalMenu' => $personalMenu,
                        'dynamicMenuSections' => $dynamicMenuSections
                    ];

                    // Render sidebar using Twig template
                    $sidebarExtra = $page->fetchFromTemplate('studentProfileSidebar.twig.html', $sidebarData);
                    $session->set('sidebarExtra', $sidebarExtra);
                }
            }
        }
    }
}
