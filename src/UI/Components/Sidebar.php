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

namespace Gibbon\UI\Components;

use Gibbon\Http\Url;
use Gibbon\View\View;
use Gibbon\Forms\Form;
use Gibbon\Services\Format;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Forms\OutputableInterface;
use Gibbon\Contracts\Services\Session;
use Gibbon\Contracts\Database\Connection;
use Gibbon\Domain\System\SettingGateway;
use League\Container\ContainerAwareTrait;
use League\Container\ContainerAwareInterface;
use Gibbon\Domain\Planner\PlannerEntryGateway;
use Gibbon\Domain\School\SchoolYearGateway;

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

    /**
     * Setting gateway
     *
     * @var SettingGateway
     */
    protected $settingGateway;

    /**
     * School Year gateway.
     *
     * @var SchoolYearGateway
     */
    protected $schoolYearGateway;

    public function __construct(
        Connection $db,
        Session $session,
        SettingGateway $settingGateway,
        SchoolYearGateway $schoolYearGateway
    ) {
        $this->db = $db;
        $this->session = $session;
        $this->category = $this->session->get('gibbonRoleIDCurrentCategory');
        $this->settingGateway = $settingGateway;
        $this->schoolYearGateway = $schoolYearGateway;
    }

    public function getOutput()
    {
        $guid = $this->session->get('guid');
        $connection2 = $this->db->getConnection();
        $pdo = $this->db;

        ob_start();

        $loginReturn = $_GET['loginReturn'] ?? '';


        if (!empty($loginReturn)) {
            $loginReturnMessage = '';

            switch ($loginReturn) {
                case 'fail0': $loginReturnMessage = __('Username or password not set.');
                    break;
                case 'fail1': $loginReturnMessage = __('Incorrect username and password.');
                    break;
                case 'fail2': $loginReturnMessage = __('You do not have sufficient privileges to login.');
                    break;
                case 'fail3': $loginReturnMessage = __('Your primary role does not support the ability to log into the specified year.');
                    break;
                case 'fail4': $loginReturnMessage = __('Your primary role does not support the ability to login.');
                    break;
                case 'fail5': $loginReturnMessage = __('Your request failed due to a database error.');
                    break;
                case 'fail6': $loginReturnMessage = sprintf(__('Too many failed logins: please %1$sreset password%2$s.'), "<a href='".Url::fromRoute('passwordReset') . "'>", '</a>');
                    break;
                case 'fail7': $loginReturnMessage = sprintf(__('Error with SSO Authentication. Please contact %1$s if you have any questions.'), "<a href='mailto:".$this->session->get('organisationDBAEmail')."'>".$this->session->get('organisationDBAName').'</a>');
                    break;
                case 'fail8': $loginReturnMessage = sprintf(__('Email account does not match the email stored in %1$s. If you have logged in with your school email account please contact %2$s if you have any questions.'), $this->session->get('systemName'), "<a href='mailto:".$this->session->get('organisationDBAEmail')."'>".$this->session->get('organisationDBAName').'</a>');
                    break;
                case 'fail10': $loginReturnMessage = __('Cannot login during maintenance mode.');
                    break;
                case 'fail11': $loginReturnMessage = __('Your MFA code is invalid or missing.');
                    break;
            }
            if ( $loginReturnMessage != ''){
                echo Format::alert($loginReturnMessage, 'error');
            }
        }



        if ($this->session->get('sidebarExtra') != '' and $this->session->get('sidebarExtraPosition') != 'bottom') {
            echo "<div class='sidebarExtra'>";
            echo $this->session->get('sidebarExtra');
            echo '</div>';
        }

        // Add Google Login Button
        if (!$this->session->exists('username') && !$this->session->exists('email')) {
            $googleSettings = json_decode($this->settingGateway->getSettingByScope('System Admin', 'ssoGoogle'), true);
            $microsoftSettings = json_decode($this->settingGateway->getSettingByScope('System Admin', 'ssoMicrosoft'), true);
            $genericSSOSettings = json_decode($this->settingGateway->getSettingByScope('System Admin', 'ssoOther'), true);

            if ($googleSettings['enabled'] == 'Y' || $microsoftSettings['enabled'] == 'Y' || $genericSSOSettings['enabled'] == 'Y') {
                echo '<div class="column-no-break">';

                $form = Form::createBlank('loginFormOAuth2', '#');
                $form->setFactory(DatabaseFormFactory::create($pdo));
                $form->setTitle(__('Single Sign-on'));
                $form->setClass('loginTableOAuth2');
                $form->setAttribute('x-data', "{'submitting': false, 'invalid': false, 'options': false}");

                $view = $this->getContainer()->get(View::class);

                if ($googleSettings['enabled'] == 'Y') {
                    $form->addRow()->addContent($view->fetchFromTemplate('ui/ssoButton.twig.html', [
                        'authURL'    => Url::fromHandlerRoute('login.php')->withQueryParams(['method' => 'google', 'options' => '']),
                        'service'    => 'google',
                        'clientName' => __('Google'),
                    ]))->addClass('flex-1');
                }
                if ($microsoftSettings['enabled'] == 'Y') {
                    $form->addRow()->addContent($view->fetchFromTemplate('ui/ssoButton.twig.html', [
                        'authURL'    => Url::fromHandlerRoute('login.php')->withQueryParams(['method' => 'microsoft', 'options' => '']),
                        'service'    => 'microsoft',
                        'clientName' => __('Microsoft'),
                    ]))->addClass('flex-1');
                }
                if ($genericSSOSettings['enabled'] == 'Y') {
                    $form->addRow()->addContent($view->fetchFromTemplate('ui/ssoButton.twig.html', [
                        'authURL'    => Url::fromHandlerRoute('login.php')->withQueryParams(['method' => 'oauth', 'options' => '']),
                        'service'    => 'other',
                        'clientName' => $genericSSOSettings['clientName'],
                    ]))->addClass('flex-1');
                }

                $row = $form->addRow()->setClass('flex items-center justify-between')->setAttribute('x-show', 'options')->setAttribute('x-transition')->setAttribute('x-cloak', 'on');
                $row->addButton('')
                    ->setID('schoolYearLabel')
                    ->setIcon('calendar')
                    ->groupAlign('left')
                    ->setAria('label', __('School Year'))
                    ->setClass('text-sm py-2')
                    ->setAttribute('tabindex', -1);
                $row->addSelectSchoolYear('gibbonSchoolYearID')
                    ->groupAlign('right')
                    ->setClass('w-full flex-grow py-2')
                    ->setAria('label', __('School Year'))
                    ->placeholder(null)
                    ->selected($this->session->get('gibbonSchoolYearID'));

                $row = $form->addRow()->setClass('flex items-center justify-between')->setAttribute('x-show', 'options')->setAttribute('x-transition')->setAttribute('x-cloak', 'on');
                    $row->addButton('')
                        ->setID('languageLabel')
                        ->setIcon('language')
                        ->groupAlign('left')
                        ->setAria('label', __('Language'))
                        ->setClass('text-sm py-2')
                        ->setAttribute('tabindex', -1);
                    $row->addSelectI18n('gibboni18nID')
                        ->groupAlign('right')
                        ->setClass('w-full flex-grow py-2')
                        ->setAria('label', __('Language'))
                        ->placeholder(null)
                        ->selected($this->session->get('i18n')['gibboni18nID']);

                    $row = $form->addRow()->addClass('flex justify-end items-center');
                    $row->addToggle('optionsSSO')
                        ->setToggle('Y', __('Options'), 'N', __('Options'))
                        ->setSize('sm')
                        ->setAttribute('@click', 'options = !options');

                echo $form->getOutput();

                echo $view->fetchFromTemplate('ui/ssoButton.twig.html');

                echo '</div>';

            }

            if (!$this->session->exists('username')) { // If Google Auth set to No make sure login screen not visible when logged in
                echo '<div class="column-no-break">';
                echo '<h2>';
                    echo __('Login');
                echo '</h2>';

                unset($_GET['return']);

                $enablePublicRegistration = $this->settingGateway->getSettingByScope('User Admin', 'enablePublicRegistration');

                $form = Form::createBlank('loginForm', $this->session->get('absoluteURL').'/login.php?'.http_build_query($_GET) )
                    ->setAttribute('x-data', "{'submitting': false, 'invalid': false, 'options': false}");

                $form->setFactory(DatabaseFormFactory::create($pdo));
                $form->setAutocomplete(false);
                $form->addHiddenValue('address', $this->session->get('address'));
                $form->addHiddenValue('method', 'default');

                $loginMethod = $_GET['method'] ?? '';

                if ($loginMethod == 'mfa') {
                    $nonce = hash('sha256', $guid.time());
                    $this->session->set('mfaFormNonce', $nonce);

                    $form = Form::createBlank('mfa',  $this->session->get('absoluteURL').'/login.php?'.http_build_query($_GET));
                    $form->setAutocomplete(false);
                    $form->addHiddenValue('address', $this->session->get('address'));
                    $form->addHiddenValue('method', 'mfa');
                    $form->addHiddenValue('mfaFormNonce', $nonce);

                    $col = $form->addRow()->addColumn();
                        $col->addLabel('mfaCode', __('Multi Factor Authentication Code'));
                        $col->addNumber('mfaCode');
                } else {
                    $row = $form->addRow()->addClass('flex items-center justify-between');
                        $row->addButton('')
                            ->setID('usernameLabel')
                            ->setIcon('user')
                            ->groupAlign('left')
                            ->setAria('label', __('Username or email'))
                            ->setTitle(__('Username or email'))
                            ->setClass('text-sm py-2')
                            ->setAttribute('tabindex', -1);
                        $row->addTextField('username')
                            ->groupAlign('right')
                            ->required()
                            ->maxLength(50)
                            ->setClass('w-full flex-grow py-2')
                            ->setAria('label', __('Username or email'))
                            ->placeholder(__('Username or email'))
                            ->addValidationOption('onlyOnSubmit: true');

                    $row = $form->addRow()->addClass('flex items-center justify-between');
                        $row->addButton('')
                            ->setID('passwordLabel')
                            ->setIcon('password')
                            ->groupAlign('left')
                            ->setAria('label', __('Password'))
                            ->setTitle(__('Password'))
                            ->setClass('text-sm py-2')
                            ->setAttribute('tabindex', -1);
                        $row->addPassword('password')
                            ->groupAlign('right')
                            ->required()
                            ->maxLength(30)
                            ->setClass('w-full flex-grow py-2')
                            ->setAria('label', __('Password'))
                            ->placeholder(__('Password'))
                            ->addValidationOption('onlyOnSubmit: true');

                    $row = $form->addRow()->setClass('flex items-center justify-between')->setAttribute('x-show', 'options')->setAttribute('x-transition')->setAttribute('x-cloak', 'on');
                        $row->addButton('')
                            ->setID('schoolYearLabel')
                            ->setIcon('calendar')
                            ->groupAlign('left')
                            ->setAria('label', __('School Year'))
                            ->setTitle(__('School Year'))
                            ->setClass('text-sm py-2')
                            ->setAttribute('tabindex', -1);
                        $row->addSelectSchoolYear('gibbonSchoolYearID')
                            ->groupAlign('right')
                            ->setClass('w-full flex-grow py-2')
                            ->setAria('label', __('School Year'))
                            ->placeholder(null)
                            ->selected($this->session->get('gibbonSchoolYearID'));

                    $row = $form->addRow()->setClass('flex items-center justify-between')->setAttribute('x-show', 'options')->setAttribute('x-transition')->setAttribute('x-cloak', 'on');
                        $row->addButton('')
                            ->setID('languageLabel')
                            ->setIcon('language')
                            ->groupAlign('left')
                            ->setAria('label', __('Language'))
                            ->setTitle(__('Language'))
                            ->setClass('text-sm py-2')
                            ->setAttribute('tabindex', -1);
                        $row->addSelectI18n('gibboni18nID')
                            ->groupAlign('right')
                            ->setClass('w-full flex-grow py-2')
                            ->setAria('label', __('Language'))
                            ->placeholder(null)
                            ->selected($this->session->get('i18n')['gibboni18nID']);

                    $row = $form->addRow()->addClass('flex justify-between items-center py-2');
                        $row->addContent('<a class="text-xs font-semibold text-gray-700 hover:text-blue-600 hover:underline" href="'.Url::fromRoute('passwordReset').'">'.__('Forgot Password?').'</a>')
                            ->wrap('<span class="small">', '</span>')
                            ->setClass('flex-1');
                        $row->addToggle('options')
                            ->setToggle('Y', __('Options'), 'N', __('Options'))
                            ->setSize('sm')
                            ->setAttribute('@click', 'options = !options');
                }

                $row = $form->addRow()->setClass('flex justify-end items-center');
                    $row->onlyIf($enablePublicRegistration == 'Y')->addButton('Register')->addClass('flex-1 mt-1')->onClick('window.location="'.Url::fromRoute('publicRegistration').'"');
                    $row->addSubmit(__('Login'))->addClass('flex-1 mt-1 text-right');

                echo $form->getOutput();
                echo '</div>';
            }
        }

        $session = $this->session;
        //Show custom sidebar content on homepage for logged in users
        if ($session->get('address') == '' and  $session->exists('username')) {
            $cacheLoad = false;
            $caching = $this->getContainer()->get('config')->getConfig('caching');

            if (!empty($caching) && is_numeric($caching)) {
                $cacheLoad = $session->get('pageLoads') % intval($caching) == 0;
            }

            if ($cacheLoad || !$session->exists('index_customSidebar.php')) {
                if (is_file('./index_customSidebar.php')) {
                    $session->set('index_customSidebar.php', include './index_customSidebar.php');
                } else {
                    $session->set('index_customSidebar.php', null);
                }
            }

            if ($session->exists('index_customSidebar.php')) {
                echo  $session->get('index_customSidebar.php');              
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
                    $enableHomeScreenWidget = $this->settingGateway->getSettingByScope('Messenger', 'enableHomeScreenWidget');
                    if ($enableHomeScreenWidget == 'Y') {
                        $unpinnedMessages = array_reduce($this->session->get('messageWallArray'), function ($group, $item) {
                            if ($item['messageWallPin'] == 'N') {
                                $group[$item['gibbonMessengerID']] = $item;
                            }
                            return $group;
                        }, []);
                        shuffle($unpinnedMessages);

                        echo '<div class="column-no-break overflow-x-hidden md:max-w-xs" >';
                        echo '<h2>';
                        echo __('Message Wall');
                        echo '</h2>';

                        if (count($unpinnedMessages) < 1) {
                            echo Format::alert(__('There are no records to display.'), 'empty');
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
                            echo "<div class='rounded overflow-hidden border'>";
                            echo "<div id='messageWallWidget' style='height: ".$height."px;' class='w-full overflow-y-auto  bg-gray-50 rounded leading-relaxed'>";
                            //Content added by JS
                            $rand = rand(0, count($unpinnedMessages));
                            $total = count($unpinnedMessages);

                            $i = 0;
                            foreach ($unpinnedMessages as $message) {
                                $pos = ($rand + $i) % $total;

                                //COLOR ROW BY STATUS!
                                echo "<div id='messageWall".$pos."' class='flex justify-between items-start p-2 sm:p-3 ".($i+1 < $total ? 'border-b border-gray-300' : '')."'>";
                                echo "<div class=''>";
                                
                                //Message number
                                // echo "<div class='mb-0.5 uppercase text-gray-500' style='font-size: 8px'>".__('Message').' '.($pos + 1).'</div>';
                                
                                //Title
                                $URL = Url::fromModuleRoute('Messenger', 'messageWall_view')->withFragment(strval($message['gibbonMessengerID']));
                                echo "<a class='block text-xs font-bold uppercase mb-1' href='$URL'>".Format::truncate($message['subject'], 20).'</a>';

                                //Text
                                echo "<div class='text-xs text-gray-700'>";

                                $messageBody = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $message['body']);
                                $messageBody = strip_tags($messageBody);
                                echo strlen($messageBody) > 46
                                    ? mb_substr($messageBody, 0, 46).'...'
                                    : $messageBody;

                                echo '</div>';

                                echo '</div>';

                                echo "<div class=''>";

                                //Image
                                $style = "style='width: 45px; height: 60px; float: right; margin-left: 6px; border: 1px solid black'";
                                if (empty($message['image_240']) or (!empty($message['photo']) and !file_exists($this->session->get('absolutePath').'/'.$message['photo']))) {
                                    echo "<img $style  src='".$this->session->get('absoluteURL').'/themes/'.$this->session->get('gibbonThemeName')."/img/anonymous_75.jpg'/>";
                                } else {
                                    echo "<img $style src='".$this->session->get('absoluteURL').'/'.$message['image_240']."'/>";
                                }
                                echo '</div>';

                                echo '</div>';

                                $i++;
                            }
                            echo '</div>';
                            echo '</div>';
                        }

                        echo "<p style='padding-top: 5px; text-align: right'>";
                        echo "<a href='".Url::fromModuleRoute('Messenger', 'messageWall_view')."'>".__('View Message Wall').'</a>';
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

                $homeworkNamePlural = $this->settingGateway->getSettingByScope('Planner', 'homeworkNamePlural');

                echo '<div class="column-no-break">';
                echo '<h2>';
                echo __('{homeworkName} + Due Dates', ['homeworkName' => __($homeworkNamePlural)]);
                echo '</h2>';

                $plannerGateway = $this->getContainer()->get(PlannerEntryGateway::class);
                $deadlines = $plannerGateway->selectUpcomingHomeworkByStudent($this->session->get('gibbonSchoolYearID'), $this->session->get('gibbonPersonID'))->fetchAll();

                echo $this->getContainer()->get('page')->fetchFromTemplate('ui/upcomingDeadlines.twig.html', [
                    'gibbonPersonID' => $this->session->get('gibbonPersonID'),
                    'deadlines' => $deadlines,
                    'hideLessonName' => true,
                ]);

                echo "<p style='padding-top: 0px; text-align: right'>";
                echo "<a href='".Url::fromModuleRoute('Planner', 'planner_deadlines')."'>";

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
                        echo "<li><a href='".Url::fromModuleRoute('Markbook', 'markbook_view')->withFragment($rowEntry['gibbonMarkbookEntryID'])."'>".$rowEntry['course'].'.'.$rowEntry['class']."<br/><span style='font-size: 85%; font-style: italic'>".$rowEntry['name'].'</span></a></li>';
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
                echo '<div class="column-no-break" id="myClasses">';
                echo "<h2 style='margin-bottom: 10px'>";
                echo __('My Classes');
                echo '</h2>';

                echo "<table class='mini' cellspacing='0' style='width: 100%; table-layout: fixed;'>";
                echo "<tr class='head'>";
                echo "<th style='width: 36%; font-size: 85%; text-transform: uppercase'>";
                echo __('Class');
                echo '</th>';
                echo "<th style='width: 16%; font-size: 60%; text-align: center; text-transform: uppercase'>";
                echo __('People');
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
                    echo "<a href='".Url::fromModuleRoute('Departments', 'department_course_class')->withQueryParam('gibbonCourseClassID', $row['gibbonCourseClassID'])."'>".$row['course'].'.'.$row['class'].'</a>';
                    echo '</td>';
                    $iconClass = 'size-7 text-gray-500 hover:text-gray-700';
                    echo "<td style='text-align: center'>";
                    if (isActionAccessible($guid, $connection2, '/modules/Attendance/attendance_take_byCourseClass.php') && $row['attendance'] == 'Y') {
                        echo "<a class='block' href='".Url::fromModuleRoute('Attendance', 'attendance_take_byCourseClass')->withQueryParam('gibbonCourseClassID', $row['gibbonCourseClassID'])."'title='".__('Take Attendance')."' >";
                        echo icon('solid', 'users', $iconClass);
                        echo "</a>";
                    } else {
                        echo "<a class='block' href='".Url::fromModuleRoute('Departments', 'department_course_class')->withQueryParam('gibbonCourseClassID', $row['gibbonCourseClassID'])->withFragment('participants')."' title='".__('Participants')."' >";
                        echo icon('solid', 'users', $iconClass);
                        echo "</a>";
                    }
                    echo '</td>';
                    if (isActionAccessible($guid, $connection2, '/modules/Planner/planner.php')) {
                        echo "<td style='text-align: center'>";
                        echo "<a class='block' href='".Url::fromModuleRoute('Planner', 'planner')->withQueryParams(['gibbonCourseClassID' => $row['gibbonCourseClassID'], 'viewBy' => 'class'])."' title='".__('View Planner')."'>";
                        echo icon('solid', 'calendar', $iconClass);
                        echo "</a> ";
                        echo '</td>';
                    }
                    if (getHighestGroupedAction($guid, '/modules/Markbook/markbook_view.php', $connection2) == 'View Markbook_allClassesAllData') {
                        echo "<td style='text-align: center'>";
                        echo "<a class='block' href='".Url::fromModuleRoute('Markbook', 'markbook_view')->withQueryParam('gibbonCourseClassID', $row['gibbonCourseClassID'])."' title='".__('View Markbook')."'>";
                        echo icon('solid', 'markbook', $iconClass);
                        echo "</a> ";
                        echo '</td>';
                    }
                    
                    if (isActionAccessible($guid, $connection2, '/modules/Planner/planner.php')) {
                        $homeworkNamePlural = $this->settingGateway->getSettingByScope('Planner', 'homeworkNamePlural');

                        echo "<td style='text-align: center'>";
                        echo "<a class='block' href='".Url::fromModuleRoute('Planner', 'planner_deadlines')->withQueryParam('gibbonCourseClassIDFilter', $row['gibbonCourseClassID'])."'  title='".__('View {homeworkName}', ['homeworkName' => __($homeworkNamePlural)])."'>";
                        echo icon('solid', 'homework', $iconClass);
                        echo "</a> ";
                        echo '</td>';
                    }
                    echo '</tr>';
                }
                echo '</table>';
                echo '</div>';
            }
        }

        //Show role switcher if user has more than one role
        if ($this->session->exists('username')) {
            if (count($this->session->get('gibbonRoleIDAll', [])) > 1 and $this->session->get('address') == '') {
                echo '<div class="column-no-break">';
                echo "<h2 class='uppercase text-base mt-4 mb-2 text-gray-800'>";
                echo __('Role Switcher');
                echo '</h2>';

                echo '<p class="text-xs text-gray-700">';
                echo __('You have multiple roles within the system. Use the list below to switch role:');
                echo '</p>';

                $form = Form::createBlank('roleSwitcher', $this->session->get('absoluteURL').'/roleSwitcherProcess.php', 'get');
                $form->setAutocomplete(false);
                $form->setClass('max-w-full');
                $form->addHiddenValue('address', $this->session->get('address'));

                $roles = $this->session->get('gibbonRoleIDAll', []);
                $row = $form->addRow()->addClass('flex');
                    $row->addSelect('gibbonRoleID')
                        ->fromArray(array_combine(array_column($roles, 0), array_column($roles, 1)))
                        ->placeholder(null)
                        ->addClass('flex-grow')
                        ->groupAlign('left')
                        ->selected($this->session->get('gibbonRoleIDCurrent'));
                    $row->addSubmit(__('Switch'), 'roleSwitch')
                        ->setType('quickSubmit')
                        ->groupAlign('right')
                        ->setClass('flex');

                echo $form->getOutput();

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
                    echo "<h2 class='uppercase text-base mt-4 mb-2 text-gray-800'>";
                    echo __('Year Switcher');
                    echo '</h2>';

                    //Add year Switcher
                    $form = Form::createBlank('yearSwitcher', $this->session->get('absoluteURL').'/yearSwitcherProcess.php', 'post')->enableQuickSubmit();
                    $form->setFactory(DatabaseFormFactory::create($pdo));
                    $form->setAutocomplete(false);
                    $form->setClass('max-w-full');
                    $form->addHiddenValue('address', $this->session->get('address'));

                    $status = 'All';
                    if ($row['futureYearsLogin'] == 'Y' && $row['pastYearsLogin'] == 'N') {
                        $status = 'Active';
                    }
                    else if ($row['futureYearsLogin'] == 'N' && $row['pastYearsLogin'] == 'Y') {
                        $status = 'Recent';
                    }
                    $row = $form->addRow()->addClass('flex');
                        $row->addSelectSchoolYear('gibbonSchoolYearID', $status)
                            ->placeholder(null)
                            ->addClass('flex-grow')
                            ->groupAlign('left')
                            ->selected($this->session->get('gibbonSchoolYearID'));
                        $row->addSubmit(__('Switch'), 'yearSwitch')
                            ->setType('quickSubmit')
                            ->groupAlign('right')
                            ->setClass('flex');

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

                $form = Form::create('photoUpload', Url::fromHandlerRoute('index_parentPhotoUploadProcess.php')->withQueryParam('gibbonPersonID', $this->session->get('gibbonPersonID')));
                $form->addHiddenValue('address', $this->session->get('address'));
                $form->setClass('smallIntBorder w-full');

                $row = $form->addRow();
                    $row->addFileUpload('file1')->accepts('.jpg,.jpeg,.gif,.png')->setMaxUpload(false)->setClass('w-full');
                    $row->addSubmit(__('Go'));

                $output .= $form->getOutput();

            } else { //Photo, so show image and removal link
                $output .= '<p>';
                $output .= Format::userPhoto($this->session->get('image_240'), 240);
                $output .= "<div style='margin-left: 220px; margin-top: -50px'>";
                $output .= "<a href='".Url::fromHandlerRoute('index_parentPhotoDeleteProcess.php')->withQueryParam('gibbonPersonID', $this->session->get('gibbonPersonID'))."' onclick='return confirm(\"Are you sure you want to delete this record? Unsaved changes will be lost.\")'><img style='margin-bottom: -8px' id='image_240_delete' title='".__('Delete')."' src='./themes/".$this->session->get('gibbonThemeName')."/img/garbage.png'/></a><br/><br/>";
                $output .= '</div>';
                $output .= '</p>';
            }
            $output .= '</div>';
        }

        return $output;
    }
}
