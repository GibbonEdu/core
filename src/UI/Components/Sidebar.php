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

namespace Gibbon\UI\Components;

use Gibbon\Contracts\Services\Session;
use Gibbon\Contracts\Database\Connection;
use Gibbon\Forms\Form;
use Gibbon\Forms\OutputableInterface;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Domain\Planner\PlannerEntryGateway;
use League\Container\ContainerAwareTrait;
use League\Container\ContainerAwareInterface;

/**
 * Sidebar View Composer
 *
 * @version  v18
 * @since    v18
 */
class Sidebar implements OutputableInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    protected $db;
    protected $session;
    protected $category;

    public function __construct(Connection $db, Session $session)
    {
        $this->db = $db;
        $this->session = $session;
        $this->category = getRoleCategory($this->session->get('gibbonRoleIDCurrent'), $this->db->getConnection());
    }

    public function getOutput()
    {
        $guid = $this->session->get('guid');
        $connection2 = $this->db->getConnection();
        $pdo = $this->db;

        ob_start();

        $googleOAuth = getSettingByScope($connection2, 'System', 'googleOAuth');
        if (isset($_GET['loginReturn'])) {
            $loginReturn = $_GET['loginReturn'];
        } else {
            $loginReturn = '';
        }
        $loginReturnMessage = '';
        if (!($loginReturn == '')) {
            if ($loginReturn == 'fail0b') {
                $loginReturnMessage = __('Username or password not set.');
            } elseif ($loginReturn == 'fail1') {
                $loginReturnMessage = __('Incorrect username and password.');
            } elseif ($loginReturn == 'fail2') {
                $loginReturnMessage = __('You do not have sufficient privileges to login.');
            } elseif ($loginReturn == 'fail5') {
                $loginReturnMessage = __('Your request failed due to a database error.');
            } elseif ($loginReturn == 'fail6') {
                $loginReturnMessage = sprintf(__('Too many failed logins: please %1$sreset password%2$s.'), "<a href='".$this->session->get('absoluteURL')."/index.php?q=/passwordReset.php'>", '</a>');
            } elseif ($loginReturn == 'fail7') {
                $loginReturnMessage = sprintf(__('Error with Google Authentication. Please contact %1$s if you have any questions.'), "<a href='mailto:".$this->session->get('organisationDBAEmail')."'>".$this->session->get('organisationDBAName').'</a>');
            } elseif ($loginReturn == 'fail8') {
                $loginReturnMessage = sprintf(__('Gmail account does not match the email stored in %1$s. If you have logged in with your school Gmail account please contact %2$s if you have any questions.'), $this->session->get('systemName'), "<a href='mailto:".$this->session->get('organisationDBAEmail')."'>".$this->session->get('organisationDBAName').'</a>');
            } elseif ($loginReturn == 'fail9') {
                $loginReturnMessage = __('Your primary role does not support the ability to log into the specified year.');
            }

            echo "<div class='error'>";
            echo $loginReturnMessage;
            echo '</div>';
        }

        if ($this->session->get('sidebarExtra') != '' and $this->session->get('sidebarExtraPosition') != 'bottom') {
            echo "<div class='sidebarExtra'>";
            echo $this->session->get('sidebarExtra');
            echo '</div>';
        }

        // Add Google Login Button
        if (!$this->session->exists('username') && !$this->session->exists('email')) {
            if ($googleOAuth == 'Y') {
                echo '<div class="column-no-break">';
                echo '<h2>';
                echo __('Login with Google');
                echo '</h2>';

                ?>
                <script>
                    $(function(){
                        $('#siteloader').load('lib/google/index.php');
                    });
                </script>
                <div id="siteloader" style="min-height:73px"></div>
                <?php
                echo '</div>';

            } //End Check for Google Auth
            if (!$this->session->exists('username')) { // If Google Auth set to No make sure login screen not visible when logged in
                echo '<div class="column-no-break">';
                echo '<h2>';
                    echo __('Login');
                echo '</h2>';

                if (!$this->session->has('gibbonSchoolYearID')) setCurrentSchoolYear($guid, $connection2);
                unset($_GET['return']);

                $enablePublicRegistration = getSettingByScope($connection2, 'User Admin', 'enablePublicRegistration');
                
                $form = Form::create('loginForm', $this->session->get('absoluteURL').'/login.php?'.http_build_query($_GET) );

                $form->setFactory(DatabaseFormFactory::create($pdo));
                $form->setAutocomplete(false);
                $form->setClass('noIntBorder fullWidth');
                $form->addHiddenValue('address', $this->session->get('address'));

                $loginIcon = '<img src="'.$this->session->get('absoluteURL').'/themes/'.$this->session->get('gibbonThemeName').'/img/%1$s.png" style="width:20px;height:20px;margin:-2px 0 0 2px;" title="%2$s">';

                $row = $form->addRow();
                    $row->addContent(sprintf($loginIcon, 'attendance', __('Username or email')));
                    $row->addTextField('username')
                        ->required()
                        ->maxLength(50)
                        ->setClass('fullWidth')
                        ->setAria('label', __('Username or email'))
                        ->placeholder(__('Username or email'))
                        ->addValidationOption('onlyOnSubmit: true');

                $row = $form->addRow();
                    $row->addContent(sprintf($loginIcon, 'key', __('Password')));
                    $row->addPassword('password')
                        ->required()
                        ->maxLength(30)
                        ->setClass('fullWidth')
                        ->setAria('label', __('Password'))
                        ->placeholder(__('Password'))
                        ->addValidationOption('onlyOnSubmit: true');

                $row = $form->addRow()->setClass('loginOptions');
                    $row->addContent(sprintf($loginIcon, 'planner', __('School Year')));
                    $row->addSelectSchoolYear('gibbonSchoolYearID')
                        ->setClass('fullWidth')
                        ->setAria('label', __('School Year'))
                        ->placeholder(null)
                        ->selected($this->session->get('gibbonSchoolYearID'));

                $row = $form->addRow()->setClass('loginOptions');
                    $row->addContent(sprintf($loginIcon, 'language', __('Language')));
                    $row->addSelectI18n('gibboni18nID')
                        ->setClass('fullWidth')
                        ->setAria('label', __('Language'))
                        ->placeholder(null)
                        ->selected($this->session->get('i18n')['gibboni18nID']);

                $row = $form->addRow();
                    $row->addContent('<a class="show_hide" onclick="false" href="#">'.__('Options').'</a>')
                        ->append(' . <a href="'.$this->session->get('absoluteURL').'/index.php?q=passwordReset.php">'.__('Forgot Password?').'</a>')
                        ->wrap('<span class="small">', '</span>')
                        ->setClass('right');

                $row = $form->addRow();
                    $row->onlyIf($enablePublicRegistration == 'Y')->addButton('Register')->addClass('rounded-sm w-24 bg-blue-100')->onClick('window.location="'.$this->session->get('absoluteURL').'/index.php?q=/publicRegistration.php"');
                    $row->addSubmit(__('Login'));

                echo $form->getOutput();
                echo '</div>';

                // Control the show/hide for login options
                echo "<script type='text/javascript'>";
                    echo '$(".loginOptions").hide();';
                    echo '$(".show_hide").click(function(){';
                    echo '$(".loginOptions").fadeToggle(1000);';
                    echo '});';
                echo '</script>';
            }
        }

        //Show custom sidebar content on homepage for logged in users
        if ($this->session->get('address') == '' and $this->session->exists('username')) {
            if (!$this->session->exists('index_customSidebar.php')) {
                if (is_file('./index_customSidebar.php')) {
                    $this->session->set('index_customSidebar.php', include './index_customSidebar.php');
                } else {
                    $this->session->set('index_customSidebar.php', null);
                }
            }
            if ($this->session->exists('index_customSidebar.php')) {
                echo $this->session->get('index_customSidebar.php');
            }
        }

        //Show parent photo uploader
        if ($this->session->get('address') == '' and $this->session->exists('username')) {
            $sidebar = $this->getParentPhotoUploader();
            if ($sidebar != false) {
                echo $sidebar;
            }
        }

        //Show homescreen widget for message wall
        if ($this->session->get('address') == '') {
            if ($this->session->exists('messageWallArray')) {
                if (isActionAccessible($guid, $connection2, '/modules/Messenger/messageWall_view.php')) {
                    $enableHomeScreenWidget = getSettingByScope($connection2, 'Messenger', 'enableHomeScreenWidget');
                    if ($enableHomeScreenWidget == 'Y') {
                        $unpinnedMessages = array_reduce($_SESSION[$guid]['messageWallArray'], function ($group, $item) {
                            if ($item['messageWallPin'] == 'N') {
                                $group[$item['gibbonMessengerID']] = $item;
                            }
                            return $group;
                        }, []);

                        echo '<div class="column-no-break">';
                        echo '<h2>';
                        echo __('Message Wall');
                        echo '</h2>';

                        if (count($unpinnedMessages) < 1) {
                            echo "<div class='warning'>";
                            echo __('There are no records to display.');
                            echo '</div>';
                        } elseif (is_array($unpinnedMessages) == false) {
                            echo "<div class='error'>";
                            echo __('An error occurred.');
                            echo '</div>';
                        } else {
                            $height = 283;
                            if (count($unpinnedMessages) == 1) {
                                $height = 94;
                            } elseif (count($unpinnedMessages) == 2) {
                                $height = 197;
                            }
                            echo "<table id='messageWallWidget' style='width: 100%; height: ".$height."px; border: 1px solid grey; padding: 6px; background-color: #eeeeee'>";
                            //Content added by JS
                            $rand = rand(0, count($unpinnedMessages));
                            $total = count($unpinnedMessages);
                            $order = '';
                            $i = 0;
                            foreach ($unpinnedMessages as $message) {
                                $pos = ($rand + $i) % $total;
                                $order .= "$pos, ";

                                //COLOR ROW BY STATUS!
                                echo "<tr id='messageWall".$pos."' style='z-index: 1;'>";
                                echo "<td style='font-size: 95%; letter-spacing: 85%;'>";
                                //Image
                                $style = "style='width: 45px; height: 60px; float: right; margin-left: 6px; border: 1px solid black'";
                                if (empty($message['image_240']) or (!empty($message['photo']) and !file_exists($this->session->get('absolutePath').'/'.$message['photo']))) {
                                    echo "<img $style  src='".$this->session->get('absoluteURL').'/themes/'.$this->session->get('gibbonThemeName')."/img/anonymous_75.jpg'/>";
                                } else {
                                    echo "<img $style src='".$this->session->get('absoluteURL').'/'.$message['image_240']."'/>";
                                }

                                //Message number
                                echo "<div style='margin-bottom: 4px; text-transform: uppercase; font-size: 70%; color: #888'>Message ".($pos + 1).'</div>';

                                //Title
                                $URL = $this->session->get('absoluteURL').'/index.php?q=/modules/Messenger/messageWall_view.php#'.$message['gibbonMessengerID'];
                                if (strlen($message['subject']) <= 16) {
                                    echo "<a style='font-weight: bold; font-size: 105%; letter-spacing: 85%; text-transform: uppercase' href='$URL'>".$message['subject'].'</a><br/>';
                                } else {
                                    echo "<a style='font-weight: bold; font-size: 105%; letter-spacing: 85%; text-transform: uppercase' href='$URL'>".mb_substr($message['subject'], 0, 16).'...</a><br/>';
                                }

                                //Text
                                echo "<div style='margin-top: 5px'>";
                                $message = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $message);
                                if (strlen(strip_tags($message['body'])) <= 40) {
                                    echo strip_tags($message['body']).'<br/>';
                                } else {
                                    echo mb_substr(strip_tags($message['body']), 0, 40).'...<br/>';
                                }
                                echo '</div>';
                                echo '</td>';
                                echo '</tr>';
                                echo "
                                <script type=\"text/javascript\">
                                    $(document).ready(function(){
                                        $(\"#messageWall$pos\").hide();
                                    });
                                </script>";

                                $i++;
                            }
                            echo '</table>';
                            $order = substr($order, 0, strlen($order) - 2);
                            if ($order == '0' || $order == '0, 1' || $order == '1, 0') {
                                $order = '0,1,2';
                            }
                            echo '
                                <script type="text/javascript">
                                    $(document).ready(function(){
                                        var order=['.$order."];
                                        var interval = 1;

                                            for(var i=0; i<order.length; i++) {
                                                var tRow = $(\"#messageWall\".concat(order[i].toString()));
                                                if(i<3) {
                                                    tRow.show();
                                                }
                                                else {
                                                    tRow.hide();
                                                }
                                            }
                                            $(\"#messageWall\".concat(order[0].toString())).attr('class', 'even');
                                            $(\"#messageWall\".concat(order[1].toString())).attr('class', 'odd');
                                            $(\"#messageWall\".concat(order[2].toString())).attr('class', 'even');

                                        setInterval(function() {
                                            if(order.length > 3) {
                                                $(\"#messageWall\".concat(order[0].toString())).hide();
                                                var fRow = $(\"#messageWall\".concat(order[0].toString()));
                                                var lRow = $(\"#messageWall\".concat(order[order.length-1].toString()));
                                                fRow.insertAfter(lRow);
                                                order.push(order.shift());
                                                $(\"#messageWall\".concat(order[2].toString())).show();

                                                if(interval%2===0) {
                                                    $(\"#messageWall\".concat(order[0].toString())).attr('class', 'even');
                                                    $(\"#messageWall\".concat(order[1].toString())).attr('class', 'odd');
                                                    $(\"#messageWall\".concat(order[2].toString())).attr('class', 'even');
                                                }
                                                else {
                                                    $(\"#messageWall\".concat(order[0].toString())).attr('class', 'odd');
                                                    $(\"#messageWall\".concat(order[1].toString())).attr('class', 'even');
                                                    $(\"#messageWall\".concat(order[2].toString())).attr('class', 'odd');
                                                }

                                                interval++;
                                            }
                                        }, 8000);
                                    });
                                </script>";
                        }
                        echo "<p style='padding-top: 5px; text-align: right'>";
                        echo "<a href='".$this->session->get('absoluteURL')."/index.php?q=/modules/Messenger/messageWall_view.php'>".__('View Message Wall').'</a>';
                        echo '</p>';
                        echo '</div>';
                    }
                }
            }
        }

        //Show upcoming deadlines
        if ($this->session->get('address') == '' and isActionAccessible($guid, $connection2, '/modules/Planner/planner.php')) {
            $highestAction = getHighestGroupedAction($guid, '/modules/Planner/planner.php', $connection2);
            if ($highestAction == 'Lesson Planner_viewMyClasses' or $highestAction == 'Lesson Planner_viewAllEditMyClasses' or $highestAction == 'Lesson Planner_viewEditAllClasses') {

                $homeworkNamePlural = getSettingByScope($connection2, 'Planner', 'homeworkNamePlural');

                echo '<div class="column-no-break">';
                echo '<h2>';
                echo __('{homeworkName} + Due Dates', ['homeworkName' => __($homeworkNamePlural)]);
                echo '</h2>';

                $plannerGateway = $this->getContainer()->get(PlannerEntryGateway::class);
                $deadlines = $plannerGateway->selectUpcomingHomeworkByStudent($_SESSION[$guid]['gibbonSchoolYearID'], $this->session->get('gibbonPersonID'))->fetchAll();

                echo $this->getContainer()->get('page')->fetchFromTemplate('ui/upcomingDeadlines.twig.html', [
                    'gibbonPersonID' => $this->session->get('gibbonPersonID'),
                    'deadlines' => $deadlines,
                    'hideLessonName' => true,
                ]);

                echo "<p style='padding-top: 0px; text-align: right'>";
                echo "<a href='".$this->session->get('absoluteURL')."/index.php?q=/modules/Planner/planner_deadlines.php'>";
                
                echo __('View {homeworkName}', ['homeworkName' => __($homeworkNamePlural)]);
                echo '</a>';
                echo '</p>';
                echo '</div>';
            }
        }

        //Show recent results
        if ($this->session->get('address') == '' and isActionAccessible($guid, $connection2, '/modules/Markbook/markbook_view.php')) {
            $highestAction = getHighestGroupedAction($guid, '/modules/Markbook/markbook_view.php', $connection2);
            if ($highestAction == 'View Markbook_myMarks') {
                try {
                    $dataEntry = array('gibbonSchoolYearID' => $this->session->get('gibbonSchoolYearID'), 'gibbonPersonID' => $this->session->get('gibbonPersonID'));
                    $sqlEntry = "SELECT gibbonMarkbookEntryID, gibbonMarkbookColumn.name, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonMarkbookEntry JOIN gibbonMarkbookColumn ON (gibbonMarkbookEntry.gibbonMarkbookColumnID=gibbonMarkbookColumn.gibbonMarkbookColumnID) JOIN gibbonCourseClass ON (gibbonMarkbookColumn.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonIDStudent=:gibbonPersonID AND complete='Y' AND completeDate<='".date('Y-m-d')."' AND viewableStudents='Y' ORDER BY completeDate DESC, name";
                    $resultEntry = $connection2->prepare($sqlEntry);
                    $resultEntry->execute($dataEntry);
                } catch (\PDOException $e) {
                }

                if ($resultEntry->rowCount() > 0) {
                    echo '<div class="column-no-break">';
                    echo '<h2>';
                    echo __('Recent Marks');
                    echo '</h2>';

                    echo '<ol>';
                    $count = 0;

                    while ($rowEntry = $resultEntry->fetch() and $count < 5) {
                        echo "<li><a href='".$this->session->get('absoluteURL').'/index.php?q=/modules/Markbook/markbook_view.php#'.$rowEntry['gibbonMarkbookEntryID']."'>".$rowEntry['course'].'.'.$rowEntry['class']."<br/><span style='font-size: 85%; font-style: italic'>".$rowEntry['name'].'</span></a></li>';
                        ++$count;
                    }

                    echo '</ol>';
                    echo '</div>';
                }
            }
        }

        //Show My Classes
        if ($this->session->get('address') == '' and $this->session->exists('username')) {
            try {
                $data = array('gibbonSchoolYearID' => $this->session->get('gibbonSchoolYearID'), 'gibbonPersonID' => $this->session->get('gibbonPersonID'));
                $sql = "SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourseClass.gibbonCourseClassID, gibbonCourseClass.attendance FROM gibbonCourse, gibbonCourseClass, gibbonCourseClassPerson WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID AND gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND NOT role LIKE '% - Left%' ORDER BY course, class";
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (\PDOException $e) {
            }

            if ($result->rowCount() > 0) {
                echo '<div class="column-no-break">';
                echo "<h2 style='margin-bottom: 10px'  class='sidebar'>";
                echo __('My Classes');
                echo '</h2>';

                echo "<table class='mini' cellspacing='0' style='width: 100%; table-layout: fixed;'>";
                echo "<tr class='head'>";
                echo "<th style='width: 36%; font-size: 85%; text-transform: uppercase'>";
                echo __('Class');
                echo '</th>';
                if (isActionAccessible($guid, $connection2, '/modules/Planner/planner.php')) {
                    echo "<th style='width: 16%; font-size: 60%; text-align: center; text-transform: uppercase'>";
                    echo __('Plan');
                    echo '</th>';
                }
                if (getHighestGroupedAction($guid, '/modules/Markbook/markbook_view.php', $connection2) == 'View Markbook_allClassesAllData') {
                    echo "<th style='width: 16%; font-size: 60%; text-align: center; text-transform: uppercase'>";
                    echo __('Mark');
                    echo '</th>';
                }
                echo "<th style='width: 16%; font-size: 60%; text-align: center; text-transform: uppercase'>";
                echo __('People');
                echo '</th>';
                if (isActionAccessible($guid, $connection2, '/modules/Planner/planner.php')) {
                    echo "<th style='width: 16%; font-size: 60%; text-align: center; text-transform: uppercase'>";
                    echo __('Tasks');
                    echo '</th>';
                }
                echo '</tr>';

                $count = 0;
                $rowNum = 'odd';
                while ($row = $result->fetch()) {
                    if ($count % 2 == 0) {
                        $rowNum = 'even';
                    } else {
                        $rowNum = 'odd';
                    }
                    ++$count;

                    //COLOR ROW BY STATUS!
                    echo "<tr class=$rowNum>";
                    echo "<td style='word-wrap: break-word'>";
                    echo "<a href='".$this->session->get('absoluteURL').'/index.php?q=/modules/Departments/department_course_class.php&gibbonCourseClassID='.$row['gibbonCourseClassID']."'>".$row['course'].'.'.$row['class'].'</a>';
                    echo '</td>';
                    if (isActionAccessible($guid, $connection2, '/modules/Planner/planner.php')) {
                        echo "<td style='text-align: center'>";
                        echo "<a href='".$this->session->get('absoluteURL').'/index.php?q=/modules/Planner/planner.php&gibbonCourseClassID='.$row['gibbonCourseClassID']."&viewBy=class'><img style='margin-top: 3px' title='".__('View Planner')."' src='./themes/".$this->session->get('gibbonThemeName')."/img/planner.png'/></a> ";
                        echo '</td>';
                    }
                    if (getHighestGroupedAction($guid, '/modules/Markbook/markbook_view.php', $connection2) == 'View Markbook_allClassesAllData') {
                        echo "<td style='text-align: center'>";
                        echo "<a href='".$this->session->get('absoluteURL').'/index.php?q=/modules/Markbook/markbook_view.php&gibbonCourseClassID='.$row['gibbonCourseClassID']."'><img style='margin-top: 3px' title='".__('View Markbook')."' src='./themes/".$this->session->get('gibbonThemeName')."/img/markbook.png'/></a> ";
                        echo '</td>';
                    }
                    echo "<td style='text-align: center'>";
                    if (isActionAccessible($guid, $connection2, '/modules/Attendance/attendance_take_byCourseClass.php') && $row['attendance'] == 'Y') {
                        echo "<a href='index.php?q=/modules/Attendance/attendance_take_byCourseClass.php&gibbonCourseClassID=".$row['gibbonCourseClassID']."'><img title='".__('Take Attendance')."' src='./themes/".$this->session->get('gibbonThemeName')."/img/attendance.png'/></a>";
                    } else {
                        echo "<a href='index.php?q=/modules/Departments/department_course_class.php&gibbonCourseClassID=".$row['gibbonCourseClassID']."#participants'><img title='".__('Participants')."' src='./themes/".$this->session->get('gibbonThemeName')."/img/attendance.png'/></a>";
                    }
                    echo '</td>';
                    if (isActionAccessible($guid, $connection2, '/modules/Planner/planner.php')) {
                        $homeworkNamePlural = getSettingByScope($connection2, 'Planner', 'homeworkNamePlural');

                        echo "<td style='text-align: center'>";
                        echo "<a href='".$this->session->get('absoluteURL').'/index.php?q=/modules/Planner/planner_deadlines.php&gibbonCourseClassIDFilter='.$row['gibbonCourseClassID']."'><img style='margin-top: 3px' title='".__('View {homeworkName}', ['homeworkName' => __($homeworkNamePlural)])."' src='./themes/".$this->session->get('gibbonThemeName')."/img/homework.png'/></a> ";
                        echo '</td>';
                    }
                    echo '</tr>';
                }
                echo '</table>';
                echo '</div>';
            }
        }

        //Show tag cloud
        if ($this->session->get('address') == '' and isActionAccessible($guid, $connection2, '/modules/Planner/resources_view.php') && !function_exists('makeBlock')) {
            include_once './modules/Planner/moduleFunctions.php';
            echo '<div class="column-no-break">';
            echo "<h2 class='sidebar'>";
            echo __('Resource Tags');
            echo '</h2>';
            echo getResourcesTagCloud($guid, $connection2, 20);
            echo "<p style='margin-bototm: 20px; text-align: right'>";
            echo "<a href='".$this->session->get('absoluteURL')."/index.php?q=/modules/Planner/resources_view.php'>".__('View Resources').'</a>';
            echo '</p>';
            echo '</div>';
        }

        //Show role switcher if user has more than one role
        if ($this->session->exists('username')) {
            if (count($this->session->get('gibbonRoleIDAll')) > 1 and $this->session->get('address') == '') {
                echo '<div class="column-no-break">';
                echo "<h2 style='margin-bottom: 10px' class='sidebar'>";
                echo __('Role Switcher');
                echo '</h2>';

                echo '<p>';
                echo __('You have multiple roles within the system. Use the list below to switch role:');
                echo '</p>';

                echo '<ul>';
                for ($i = 0; $i < count($this->session->get('gibbonRoleIDAll')); ++$i) {
                    if ($this->session->get('gibbonRoleIDAll')[$i][0] == $this->session->get('gibbonRoleIDCurrent')) {
                        echo "<li><a href='roleSwitcherProcess.php?gibbonRoleID=".$this->session->get('gibbonRoleIDAll')[$i][0]."'>".__($this->session->get('gibbonRoleIDAll')[$i][1]).'</a> <i>'.__('(Active)').'</i></li>';
                    } else {
                        echo "<li><a href='roleSwitcherProcess.php?gibbonRoleID=".$this->session->get('gibbonRoleIDAll')[$i][0]."'>".__($this->session->get('gibbonRoleIDAll')[$i][1]).'</a></li>';
                    }
                }
                echo '</ul>';
                echo '</div>';
            }
        }

        //Show year switcher if user is staff and has access to multiple years
        if ($this->session->exists('username') && $this->category == 'Staff' && $this->session->get('address') == '') {
            //Check for multiple-year login
            
                $data = array('gibbonRoleID' => $this->session->get('gibbonRoleIDCurrent'));
                $sql = "SELECT futureYearsLogin, pastYearsLogin FROM gibbonRole WHERE gibbonRoleID=:gibbonRoleID";
                $result = $connection2->prepare($sql);
                $result->execute($data);

            //Test to see if username exists and is unique
            if ($result->rowCount() == 1) {
                $row = $result->fetch();
                if ($row['futureYearsLogin'] == 'Y' || $row['pastYearsLogin'] == 'Y') {

                    echo '<div class="column-no-break">';
                    echo "<h2 style='margin-bottom: 10px' class='sidebar'>";
                    echo __('Year Switcher');
                    echo '</h2>';

                    //Add year Switcher
                    $form = Form::create('yearSwitcher', $this->session->get('absoluteURL').'/yearSwitcherProcess.php');

                    $form->setFactory(DatabaseFormFactory::create($pdo));
                    $form->setAutocomplete(false);
                    $form->setClass('noIntBorder fullWidth');
                    $form->addHiddenValue('address', $this->session->get('address'));

                    $status = 'All';
                    if ($row['futureYearsLogin'] == 'Y' && $row['pastYearsLogin'] == 'N') {
                        $status = 'Active';
                    }
                    else if ($row['futureYearsLogin'] == 'N' && $row['pastYearsLogin'] == 'Y') {
                        $status = 'Recent';
                    }
                    $row = $form->addRow();
                        $row->addLabel('gibbonSchoolYearID', __('Year'));
                        $row->addSelectSchoolYear('gibbonSchoolYearID', $status)
                            ->placeholder(null)
                            ->selected($this->session->get('gibbonSchoolYearID'));

                    $row = $form->addRow();
                        $row->addFooter(false);
                        $row->addSubmit(__('Switch'));

                    echo $form->getOutput();

                    echo '</div>';
                }
            }
        }

        if ($this->session->get('sidebarExtra') != '' and $this->session->get('sidebarExtraPosition') == 'bottom') {
            echo "<div class='sidebarExtra'>";
            echo $this->session->get('sidebarExtra');
            echo '</div>';
        }

        return ob_get_clean();
    }

    protected function getParentPhotoUploader()
    {
        $guid = $this->session->get('guid');
        $connection2 = $this->db->getConnection();

        $output = false;

        if ($this->category == 'Parent') {
            $output .= '<div class="column-no-break">';
            $output .= "<h2 style='margin-bottom: 10px'>";
            $output .= 'Profile Photo';
            $output .= '</h2>';

            if ($this->session->get('image_240') == '') { //No photo, so show uploader
                $output .= '<p>';
                $output .= __('Please upload a passport photo to use as a profile picture.').' '.__('240px by 320px').'.';
                $output .= '</p>';

                $form = Form::create('photoUpload', $this->session->get('absoluteURL').'/index_parentPhotoUploadProcess.php?gibbonPersonID='.$this->session->get('gibbonPersonID'));
                $form->addHiddenValue('address', $this->session->get('address'));
                $form->setClass('smallIntBorder w-full');

                $row = $form->addRow();
                    $row->addFileUpload('file1')->accepts('.jpg,.jpeg,.gif,.png')->setMaxUpload(false)->setClass('fullWidth');
                    $row->addSubmit(__('Go'));

                $output .= $form->getOutput();

            } else { //Photo, so show image and removal link
                $output .= '<p>';
                $output .= getUserPhoto($guid, $this->session->get('image_240'), 240);
                $output .= "<div style='margin-left: 220px; margin-top: -50px'>";
                $output .= "<a href='".$this->session->get('absoluteURL').'/index_parentPhotoDeleteProcess.php?gibbonPersonID='.$this->session->get('gibbonPersonID')."' onclick='return confirm(\"Are you sure you want to delete this record? Unsaved changes will be lost.\")'><img style='margin-bottom: -8px' id='image_240_delete' title='".__('Delete')."' src='./themes/".$this->session->get('gibbonThemeName')."/img/garbage.png'/></a><br/><br/>";
                $output .= '</div>';
                $output .= '</p>';
            }
            $output .= '</div>';
        }

        return $output;
    }
}
