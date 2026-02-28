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
use Gibbon\Forms\Form;
use Gibbon\Domain\DataSet;
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\UI\Components\Alert;
use Gibbon\Tables\View\GridView;
use Gibbon\UI\Timetable\Timetable;
use Gibbon\Domain\User\RoleGateway;
use Gibbon\Domain\User\UserGateway;
use Gibbon\Forms\CustomFieldHandler;
use Gibbon\Domain\System\HookGateway;
use Gibbon\Domain\User\FamilyGateway;
use Gibbon\Domain\School\HouseGateway;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\UI\Timetable\TimetableContext;
use Gibbon\Domain\School\YearGroupGateway;
use Gibbon\Domain\Students\MedicalGateway;
use Gibbon\Domain\Students\StudentGateway;
use Gibbon\Domain\School\SchoolYearGateway;
use Gibbon\Domain\Students\FirstAidGateway;
use Gibbon\Domain\System\AlertLevelGateway;
use Gibbon\Domain\FormGroups\FormGroupGateway;
use Gibbon\Domain\Planner\PlannerEntryGateway;
use Gibbon\Domain\Students\StudentNoteGateway;;
use Gibbon\Domain\School\SchoolYearTermGateway;
use Gibbon\Domain\User\PersonalDocumentGateway;
use Gibbon\Module\Planner\Tables\HomeworkTable;
use Gibbon\Domain\Departments\DepartmentGateway;
use Gibbon\Module\Attendance\StudentHistoryData;
use Gibbon\Module\Attendance\StudentHistoryView;
use Gibbon\Domain\Planner\PlannerEntryHomeworkGateway;
use Gibbon\Module\Students\StudentAttendanceStatus;
use Gibbon\Module\Students\View\LibraryBorrowingView;
use Gibbon\Module\Reports\Domain\ReportArchiveEntryGateway;

//Module includes for User Admin (for custom fields)
include './modules/User Admin/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Students/student_view_details.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    $page->scripts->add('chart');

    /** @var RoleGateway */
    $roleGateway = $container->get(RoleGateway::class);

    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
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
            if ($highestAction == 'View Student Profile_fullEditAllNotes' || $highestAction == 'View Student Profile_full' || $highestAction == 'View Student Profile_fullNoNotes') {
                $skipBrief = true;
            }

            //Test if View Student Profile_myChildren is available and parent has access to this student...if so, skip brief, and go to full.
            if (isActionAccessible($guid, $connection2, '/modules/Students/student_view_details.php', 'View Student Profile_myChildren')) {
                    $data = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'gibbonPersonID1' => $_GET['gibbonPersonID'], 'gibbonPersonID2' => $session->get('gibbonPersonID'));
                    $sql = "SELECT * FROM gibbonFamilyChild JOIN gibbonFamily ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonFamilyChild.gibbonPersonID=:gibbonPersonID1 AND gibbonFamilyAdult.gibbonPersonID=:gibbonPersonID2 AND childDataAccess='Y'";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                if ($result->rowCount() == 1) {
                    $skipBrief = true;
                }
            }

            if (isActionAccessible($guid, $connection2, '/modules/Students/student_view_details.php', 'View Student Profile_my')) {
                if ($gibbonPersonID == $session->get('gibbonPersonID')) {
                    $skipBrief = true;
                } elseif (isActionAccessible($guid, $connection2, '/modules/Students/student_view_details.php', 'View Student Profile_brief')) {
                    $highestAction = 'View Student Profile_brief';
                } else {
                    //Acess denied
                    $page->addError(__('You do not have access to this action.'));
                    return;
                }
            }

            if (isActionAccessible($guid, $connection2, '/modules/Students/student_view_details.php', 'View Student Profile_brief') and $skipBrief == false) {
                //Proceed!
                $data = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'gibbonPersonID' => $gibbonPersonID);
                $sql = "SELECT gibbonPerson.*, gibbonStudentEnrolment.gibbonSchoolYearID, gibbonStudentEnrolment.gibbonYearGroupID, gibbonStudentEnrolment.gibbonFormGroupID, gibbonStudentEnrolment.rollOrder FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonPerson.gibbonPersonID=:gibbonPersonID";
                $result = $connection2->prepare($sql);
                $result->execute($data);

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

                    $dataDetail = array('gibbonYearGroupID' => $row['gibbonYearGroupID']);
                    $sqlDetail = 'SELECT * FROM gibbonYearGroup WHERE gibbonYearGroupID=:gibbonYearGroupID';
                    $resultDetail = $connection2->prepare($sqlDetail);
                    $resultDetail->execute($dataDetail);
                    if ($resultDetail->rowCount() == 1) {
                        $rowDetail = $resultDetail->fetch();
                        echo __($rowDetail['name']);
                    }
                    echo '</td>';
                    echo "<td style='width: 34%; vertical-align: top'>";
                    echo "<span style='font-size: 115%; font-weight: bold'>".__('Form Group').'</span><br/>';

                    $dataDetail = array('gibbonFormGroupID' => $row['gibbonFormGroupID']);
                    $sqlDetail = 'SELECT * FROM gibbonFormGroup WHERE gibbonFormGroupID=:gibbonFormGroupID';
                    $resultDetail = $connection2->prepare($sqlDetail);
                    $resultDetail->execute($dataDetail);
                    if ($resultDetail->rowCount() == 1) {
                        $rowDetail = $resultDetail->fetch();
                        echo $rowDetail['name'];
                    }
                    echo '</td>';
                    echo "<td style='width: 34%; vertical-align: top'>";
                    echo "<span style='font-size: 115%; font-weight: bold'>".__('House').'</span><br/>';

                    $dataDetail = array('gibbonHouseID' => $row['gibbonHouseID']);
                    $sqlDetail = 'SELECT * FROM gibbonHouse WHERE gibbonHouseID=:gibbonHouseID';
                    $resultDetail = $connection2->prepare($sqlDetail);
                    $resultDetail->execute($dataDetail);
                    if ($resultDetail->rowCount() == 1) {
                        $rowDetail = $resultDetail->fetch();
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
                    if ($highestAction == 'View Student Profile_myChildren') {
                        $data = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'gibbonPersonID1' => $_GET['gibbonPersonID'], 'gibbonPersonID2' => $session->get('gibbonPersonID'), 'today' => date('Y-m-d'));
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
                    } elseif ($highestAction == 'View Student Profile_my') {
                        $gibbonPersonID = $session->get('gibbonPersonID');
                        $data = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'gibbonPersonID' => $gibbonPersonID, 'today' => date('Y-m-d'));
                        $sql = "SELECT gibbonPerson.*, gibbonStudentEnrolment.gibbonSchoolYearID, gibbonStudentEnrolment.gibbonYearGroupID, gibbonStudentEnrolment.gibbonFormGroupID, gibbonStudentEnrolment.rollOrder FROM gibbonPerson
                            LEFT JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID)
                            WHERE gibbonPerson.gibbonPersonID=:gibbonPersonID
                            AND gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPerson.status='Full'
                            AND (dateStart IS NULL OR dateStart<=:today) AND (dateEnd IS NULL OR dateEnd>=:today)";
                    } elseif ($highestAction == 'View Student Profile_fullEditAllNotes' || $highestAction == 'View Student Profile_full' || $highestAction == 'View Student Profile_fullNoNotes') {
                        if ($allStudents != 'on') {
                            $data = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'gibbonPersonID' => $gibbonPersonID, 'today' => date('Y-m-d'));
                            $sql = "SELECT gibbonPerson.*, gibbonStudentEnrolment.gibbonSchoolYearID, gibbonStudentEnrolment.gibbonYearGroupID, gibbonStudentEnrolment.gibbonFormGroupID, gibbonStudentEnrolment.rollOrder FROM gibbonPerson
                                JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID)
                                WHERE gibbonSchoolYearID=:gibbonSchoolYearID
                                AND gibbonPerson.gibbonPersonID=:gibbonPersonID AND status='Full'
                                AND (dateStart IS NULL OR dateStart<=:today) AND (dateEnd IS NULL  OR dateEnd>=:today) ";
                        } else {
                            $data = array('gibbonPersonID' => $gibbonPersonID, 'gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'));
                            $sql = "SELECT gibbonPerson.*, gibbonStudentEnrolment.gibbonSchoolYearID, gibbonStudentEnrolment.gibbonYearGroupID, gibbonStudentEnrolment.gibbonFormGroupID, gibbonStudentEnrolment.rollOrder FROM gibbonPerson
                                LEFT JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID)
                                WHERE gibbonPerson.gibbonPersonID=:gibbonPersonID";
                        }
                    } else {
                        //Acess denied
                        $page->addError(__('You do not have access to this action.'));
                        return;
                    }
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
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

                    $sidebarExtra = '';
                    $alert = '';
                    //Show alerts
                    if ($highestAction == 'View Student Profile_fullEditAllNotes' || $highestAction == 'View Student Profile_full' || $highestAction == 'View Student Profile_fullNoNotes') {
                        $alert = $container->get(Alert::class)->getAlertBar($gibbonPersonID, ['wrap' => false, 'large' => true]);
                        $sidebarExtra .= '<div class="w-48 sm:w-64 h-10 mb-2">'.$alert.'</div>';
                    }
                    
                    $sidebarExtra .= Format::userPhoto($studentImage, 240);

                    //PERSONAL DATA MENU ITEMS
                     $sidebarExtra .= '<div class="column-no-break">';
                     $sidebarExtra .= '<h4>'.__('Personal').'</h4>';
                     $sidebarExtra .= "<ul class='moduleMenu'>";
                    $style = '';
                    if ($subpage == 'Overview') {
                        $style = "style='font-weight: bold'";
                    }
                     $sidebarExtra .= "<li><a $style href='".$session->get('absoluteURL').'/index.php?q='.$_GET['q']."&gibbonPersonID=$gibbonPersonID&search=".$search."&search=$search&allStudents=$allStudents&subpage=Overview'>".__('Overview').'</a></li>';
                    $style = '';
                    if ($subpage == 'Personal') {
                        $style = "style='font-weight: bold'";
                    }
                     $sidebarExtra .= "<li><a $style href='".$session->get('absoluteURL').'/index.php?q='.$_GET['q']."&gibbonPersonID=$gibbonPersonID&search=".$search."&search=$search&allStudents=$allStudents&subpage=Personal'>".__('Personal').'</a></li>';
                    $style = '';
                    if ($subpage == 'Family') {
                        $style = "style='font-weight: bold'";
                    }
                     $sidebarExtra .= "<li><a $style href='".$session->get('absoluteURL').'/index.php?q='.$_GET['q']."&gibbonPersonID=$gibbonPersonID&search=".$search."&search=$search&allStudents=$allStudents&subpage=Family'>".__('Family').'</a></li>';
                    $style = '';
                    if ($subpage == 'Emergency Contacts') {
                        $style = "style='font-weight: bold'";
                    }
                     $sidebarExtra .= "<li><a $style href='".$session->get('absoluteURL').'/index.php?q='.$_GET['q']."&gibbonPersonID=$gibbonPersonID&search=".$search."&search=$search&allStudents=$allStudents&subpage=Emergency Contacts'>".__('Emergency Contacts').'</a></li>';
                    $style = '';
                    if ($subpage == 'Medical') {
                        $style = "style='font-weight: bold'";
                    }
                    if (!isActionAccessible($guid, $connection2, '/modules/Students/student_view_details.php', 'View Student Profile_my')) {
                     $sidebarExtra .= "<li><a $style href='".$session->get('absoluteURL').'/index.php?q='.$_GET['q']."&gibbonPersonID=$gibbonPersonID&search=".$search."&search=$search&allStudents=$allStudents&subpage=Medical'>".__('Medical').'</a></li>';
                    }

                    if (isActionAccessible($guid, $connection2, '/modules/Students/firstAidRecord.php')) {
                        $style = '';
                        if ($subpage == 'First Aid') {
                            $style = "style='font-weight: bold'";
                        }
                        $sidebarExtra .= "<li><a $style href='".$session->get('absoluteURL').'/index.php?q='.$_GET['q']."&gibbonPersonID=$gibbonPersonID&search=".$search."&search=$search&allStudents=$allStudents&subpage=First Aid'>".__('First Aid').'</a></li>';

                    }

                    if (isActionAccessible($guid, $connection2, '/modules/Students/student_view_details_notes_add.php')) {
                        if ($enableStudentNotes == 'Y') {
                            $style = '';
                            if ($subpage == 'Notes') {
                                $style = "style='font-weight: bold'";
                            }
                             $sidebarExtra .= "<li><a $style href='".$session->get('absoluteURL').'/index.php?q='.$_GET['q']."&gibbonPersonID=$gibbonPersonID&search=".$search."&search=$search&allStudents=$allStudents&subpage=Notes'>".__('Notes').'</a></li>';
                        }
                    }
                     $sidebarExtra .= '</ul>';

                    //OTHER MENU ITEMS, DYANMICALLY ARRANGED TO MATCH CUSTOM TOP MENU
                    //Get all modules, with the categories

                        $dataMenu = array();
                        $sqlMenu = "SELECT gibbonModuleID, category, name FROM gibbonModule WHERE active='Y' ORDER BY category, name";
                        $resultMenu = $connection2->prepare($sqlMenu);
                        $resultMenu->execute($dataMenu);
                    $mainMenu = array();
                    while ($rowMenu = $resultMenu->fetch()) {
                        $mainMenu[$rowMenu['name']] = $rowMenu['category'];
                    }
                    $studentMenuCategory = [];
                    $studentMenuName = [];
                    $studentMenuLink = [];
                    $studentMenuCount = 0;

                    //Store items in an array
                    if (isActionAccessible($guid, $connection2, '/modules/Markbook/markbook_view.php')) {
                        $style = '';
                        if ($subpage == 'Markbook') {
                            $style = "style='font-weight: bold'";
                        }
                        $studentMenuCategory[$studentMenuCount] = $mainMenu['Markbook'];
                        $studentMenuName[$studentMenuCount] = __('Markbook');
                        $studentMenuLink[$studentMenuCount] = "<li><a $style href='".$session->get('absoluteURL').'/index.php?q='.$_GET['q']."&gibbonPersonID=$gibbonPersonID&search=".$search."&search=$search&allStudents=$allStudents&subpage=Markbook'>".__('Markbook').'</a></li>';
                        ++$studentMenuCount;
                    }
                    if (isActionAccessible($guid, $connection2, '/modules/Formal Assessment/internalAssessment_view.php')) {
                        $style = '';
                        if ($subpage == 'Internal Assessment') {
                            $style = "style='font-weight: bold'";
                        }
                        $studentMenuCategory[$studentMenuCount] = $mainMenu['Formal Assessment'];
                        $studentMenuName[$studentMenuCount] = __('Formal Assessment');
                        $studentMenuLink[$studentMenuCount] = "<li><a $style href='".$session->get('absoluteURL').'/index.php?q='.$_GET['q']."&gibbonPersonID=$gibbonPersonID&search=".$search."&search=$search&allStudents=$allStudents&subpage=Internal%20Assessment'>".__('Internal Assessment').'</a></li>';
                        ++$studentMenuCount;
                    }
                    if (isActionAccessible($guid, $connection2, '/modules/Formal Assessment/externalAssessment_details.php') or isActionAccessible($guid, $connection2, '/modules/Formal Assessment/externalAssessment_view.php')) {
                        $style = '';
                        if ($subpage == 'External Assessment') {
                            $style = "style='font-weight: bold'";
                        }
                        $studentMenuCategory[$studentMenuCount] = $mainMenu['Formal Assessment'];
                        $studentMenuName[$studentMenuCount] = __('External Assessment');
                        $studentMenuLink[$studentMenuCount] = "<li><a $style href='".$session->get('absoluteURL').'/index.php?q='.$_GET['q']."&gibbonPersonID=$gibbonPersonID&search=".$search."&search=$search&allStudents=$allStudents&subpage=External Assessment'>".__('External Assessment').'</a></li>';
                        ++$studentMenuCount;
                    }
                    if (isActionAccessible($guid, $connection2, '/modules/Reports/archive_byStudent_view.php')) {
                        $style = '';
                        if ($subpage == 'Reports') {
                            $style = "style='font-weight: bold'";
                        }
                        $studentMenuCategory[$studentMenuCount] = $mainMenu['Reports'];
                        $studentMenuName[$studentMenuCount] = __('Reports');
                        $studentMenuLink[$studentMenuCount] = "<li><a $style href='".$session->get('absoluteURL').'/index.php?q='.$_GET['q']."&gibbonPersonID=$gibbonPersonID&search=".$search."&search=$search&allStudents=$allStudents&subpage=Reports'>".__('Reports').'</a></li>';
                        ++$studentMenuCount;
                    }
                    if (isActionAccessible($guid, $connection2, '/modules/Activities/report_activityChoices_byStudent.php') || isActionAccessible($guid, $connection2, '/modules/Activities/activities_view_myChildren.php') || isActionAccessible($guid, $connection2, '/modules/Activities/activities_my.php')) {
                        $style = '';
                        if ($subpage == 'Activities') {
                            $style = "style='font-weight: bold'";
                        }
                        $studentMenuCategory[$studentMenuCount] = $mainMenu['Activities'];
                        $studentMenuName[$studentMenuCount] = __('Activities');
                        $studentMenuLink[$studentMenuCount] = "<li><a $style href='".$session->get('absoluteURL').'/index.php?q='.$_GET['q']."&gibbonPersonID=$gibbonPersonID&search=".$search."&search=$search&allStudents=$allStudents&subpage=Activities'>".__('Activities').'</a></li>';
                        ++$studentMenuCount;
                    }
                    if (isActionAccessible($guid, $connection2, '/modules/Planner/planner_edit.php') or isActionAccessible($guid, $connection2, '/modules/Planner/planner_view_full.php')) {
                        $style = '';
                        if ($subpage == 'Homework') {
                            $style = "style='font-weight: bold'";
                        }
                        $homeworkNamePlural = $settingGateway->getSettingByScope('Planner', 'homeworkNamePlural');
                        $studentMenuCategory[$studentMenuCount] = $mainMenu['Planner'];
                        $studentMenuName[$studentMenuCount] = __($homeworkNamePlural);
                        $studentMenuLink[$studentMenuCount] = "<li><a $style href='".$session->get('absoluteURL').'/index.php?q='.$_GET['q']."&gibbonPersonID=$gibbonPersonID&search=".$search."&search=$search&allStudents=$allStudents&subpage=Homework'>".__($homeworkNamePlural).'</a></li>';
                        ++$studentMenuCount;
                    }
                    if (isActionAccessible($guid, $connection2, '/modules/Individual Needs/in_view.php')) {
                        $style = '';
                        if ($subpage == 'Individual Needs') {
                            $style = "style='font-weight: bold'";
                        }
                        $studentMenuCategory[$studentMenuCount] = $mainMenu['Individual Needs'];
                        $studentMenuName[$studentMenuCount] = __('Individual Needs');
                        $studentMenuLink[$studentMenuCount] = "<li><a $style href='".$session->get('absoluteURL').'/index.php?q='.$_GET['q']."&gibbonPersonID=$gibbonPersonID&search=".$search."&search=$search&allStudents=$allStudents&subpage=Individual Needs'>".__('Individual Needs').'</a></li>';
                        ++$studentMenuCount;
                    }
                    if (isActionAccessible($guid, $connection2, '/modules/Library/library_browse.php')) {
                        $style = '';
                        if ($subpage == 'Library Borrowing') {
                            $style = "style='font-weight: bold'";
                        }
                        $studentMenuCategory[$studentMenuCount] = $mainMenu['Library'];
                        $studentMenuName[$studentMenuCount] = __('Library Borrowing');
                        $studentMenuLink[$studentMenuCount] = "<li><a $style href='".$session->get('absoluteURL').'/index.php?q='.$_GET['q']."&gibbonPersonID=$gibbonPersonID&search=".$search."&search=$search&allStudents=$allStudents&subpage=Library Borrowing'>".__('Library Borrowing').'</a></li>';
                        ++$studentMenuCount;
                    }
                    if (isActionAccessible($guid, $connection2, '/modules/Timetable/tt_view.php')) {
                        $style = '';
                        if ($subpage == 'Timetable') {
                            $style = "style='font-weight: bold'";
                        }
                        $studentMenuCategory[$studentMenuCount] = $mainMenu['Timetable'];
                        $studentMenuName[$studentMenuCount] = __('Timetable');
                        $studentMenuLink[$studentMenuCount] = "<li><a $style href='".$session->get('absoluteURL').'/index.php?q='.$_GET['q']."&gibbonPersonID=$gibbonPersonID&search=".$search."&search=$search&allStudents=$allStudents&subpage=Timetable'>".__('Timetable').'</a></li>';
                        ++$studentMenuCount;
                    }if (isActionAccessible($guid, $connection2, '/modules/Attendance/report_studentHistory.php')) {
                        $style = '';
                        if ($subpage == 'Attendance') {
                            $style = "style='font-weight: bold'";
                        }
                        $studentMenuCategory[$studentMenuCount] = $mainMenu['Attendance'];
                        $studentMenuName[$studentMenuCount] = __('Attendance');
                        $studentMenuLink[$studentMenuCount] = "<li><a $style href='".$session->get('absoluteURL').'/index.php?q='.$_GET['q']."&gibbonPersonID=$gibbonPersonID&search=".$search."&search=$search&allStudents=$allStudents&subpage=Attendance'>".__('Attendance').'</a></li>';
                        ++$studentMenuCount;
                    }
                    if (isActionAccessible($guid, $connection2, '/modules/Behaviour/behaviour_view.php')) {
                        $style = '';
                        if ($subpage == 'Behaviour') {
                            $style = "style='font-weight: bold'";
                        }
                        $studentMenuCategory[$studentMenuCount] = $mainMenu['Behaviour'];
                        $studentMenuName[$studentMenuCount] = __('Behaviour');
                        $studentMenuLink[$studentMenuCount] = "<li><a $style href='".$session->get('absoluteURL').'/index.php?q='.$_GET['q']."&gibbonPersonID=$gibbonPersonID&search=".$search."&search=$search&allStudents=$allStudents&subpage=Behaviour'>".__('Behaviour').'</a></li>';
                        ++$studentMenuCount;
                    }


                    //Check for hooks, and slot them into array
                    $hooks = $hookGateway->selectHooksByType('Student Profile')->fetchGroupedUnique();

                    if (!empty($hooks)) {
                        $count = 0;
                        foreach ($hooks as $rowHook) {
                            if (empty($rowHook) || empty($rowHook['options'])) continue;

                            $options = unserialize($rowHook['options']);
                            $hookPermission = $hookGateway->getHookPermission($rowHook['gibbonHookID'], $session->get('gibbonRoleIDCurrent'), $options['sourceModuleName'] ?? '', $options['sourceModuleAction'] ?? '');

                            //Check for permission to hook

                            if (!empty($hookPermission)) {
                                $style = '';
                                if ($hook == $rowHook['name']) {
                                    $style = "style='font-weight: bold'";
                                }
                                $studentMenuCategory[$studentMenuCount] = $mainMenu[$options['sourceModuleName']];
                                $studentMenuName[$studentMenuCount] = __($rowHook['name']);
                                $studentMenuLink[$studentMenuCount] = "<li><a $style href='".$session->get('absoluteURL').'/index.php?q='.$_GET['q']."&gibbonPersonID=$gibbonPersonID&search=".$search.'&allStudents='.$allStudents.'&hook='.$rowHook['name'].'&module='.$options['sourceModuleName'].'&action='.$options['sourceModuleAction'].'&gibbonHookID='.$rowHook['gibbonHookID']."'>".__($rowHook['name']).'</a></li>';
                                ++$studentMenuCount;
                                ++$count;
                            }
                        }
                    }

                    //Menu ordering categories
                    $mainMenuCategoryOrder = $settingGateway->getSettingByScope('System', 'mainMenuCategoryOrder');
                    $orders = explode(',', $mainMenuCategoryOrder);

                    //Sort array
                    @array_multisort($studentMenuCategory, $studentMenuName, $studentMenuLink);

                    //Spit out array whilt sorting by $mainMenuCategoryOrder
                    if (count($studentMenuCategory) > 0) {
                        foreach ($orders as $order) {
                            //Check for entries
                            $countEntries = 0;
                            for ($i = 0; $i < count($studentMenuCategory); ++$i) {
                                if ($studentMenuCategory[$i] == $order) {
                                    $countEntries ++;
                                }
                            }

                            if ($countEntries > 0) {
                                 $sidebarExtra .= '<h4>'.__($order).'</h4>';
                                 $sidebarExtra .= "<ul class='moduleMenu'>";
                                for ($i = 0; $i < count($studentMenuCategory); ++$i) {
                                    if ($studentMenuCategory[$i] == $order) {
                                         $sidebarExtra .= $studentMenuLink[$i];
                                    }
                                }

                                 $sidebarExtra .= '</ul>';
                            }
                        }
                    }

                    $sidebarExtra .= '</div>';

                    $session->set('sidebarExtra', $sidebarExtra);
                }
            }
        }
    }
}
