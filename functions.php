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
use Gibbon\Contracts\Comms\Mailer;

require_once dirname(__FILE__).'/gibbon.php';

function getIPAddress() {
    $return = false;

    if (getenv('HTTP_CLIENT_IP'))
       $return = getenv('HTTP_CLIENT_IP');
    else if(getenv('HTTP_X_FORWARDED_FOR'))
       $return = getenv('HTTP_X_FORWARDED_FOR');
    else if(getenv('HTTP_X_FORWARDED'))
       $return = getenv('HTTP_X_FORWARDED');
    else if(getenv('HTTP_FORWARDED_FOR'))
       $return = getenv('HTTP_FORWARDED_FOR');
    else if(getenv('HTTP_FORWARDED'))
      $return = getenv('HTTP_FORWARDED');
    else if(getenv('REMOTE_ADDR'))
       $return = getenv('REMOTE_ADDR');

    return $return;
}

//Convert an HTML email body into a plain text email body
function emailBodyConvert($body)
{
    $return = $body;

    $return = preg_replace('#<br\s*/?>#i', "\n", $return);
    $return = str_replace('</p>', "\n\n", $return);
    $return = str_replace('</div>', "\n\n", $return);
    $return = preg_replace("#\<a.+href\=[\"|\'](.+)[\"|\'].*\>.*\<\/a\>#U", '$1', $return);
    $return = strip_tags($return, '<a>');

    return $return ;
}

/**
 * Custom translation function to allow custom string replacement
 *
 * @param string        $text    Text to Translate.
 * @param array         $params  Assoc array of key value pairs for named
 *                               string replacement.
 * @param array|string  $options Options for translations (e.g. domain).
 *                               Or string of domain (for backward
 *                               compatibility, deprecated).
 *
 * @return string The resulted translation string.
 */
function __($text, $params=[], $options=[])
{
    global $gibbon, $guid; // For backwards compatibilty

    $args = func_get_args();

    // Note: should remove the compatibility code in next
    // version, then properly state function signature.

    // Compatibility with __($guid, $text) and __($guid, $text, $domain) calls.
    // Deprecated.
    if ($args[0] === $guid) {
        array_shift($args); // discard $guid
    }
    if (empty($args)) {
        return ''; // if there is nothing after $guid, return nothing
    }

    // Basic __($text) signature handle by default.
    $text = array_shift($args);
    $params = [];
    $options = [];

    // Handle replacement parameters, if exists.
    if (!empty($args) && is_array($args[0])) {
        $params = array_shift($args);
    }

    // Handle options, if exists.
    if (!empty($args)) {
        $options = array_shift($args);

        // Backward compatibility layer.
        // Treat non-array options as 'domain'.
        $options = is_array($options) ? $options : ['domain' => $options];
    }

    // Cancel out early for empty translations
    if (empty($text)) {
        return $text;
    }

    return $gibbon->locale->translate($text, $params, $options);
}

/**
 * Custom translation function to allow custom string replacement with
 * plural string.
 *
 * @param string $singular The singular message ID.
 * @param string $plural   The plural message ID.
 * @param int    $n        The number (e.g. item count) to determine
 *                         the translation for the respective grammatical
 *                         number.
 * @param array  $params   Assoc array of key value pairs for named
 *                         string replacement.
 * @param array  $options  Options for translations (e.g. domain).
 *
 * @return string Translated Text
 */
function __n(string $singular, string $plural, int $n, array $params = [], array $options = [])
{
    global $gibbon;
    return $gibbon->locale->translateN($singular, $plural, $n, $params, $options);
}

/**
 * Identical to __() but automatically includes the current module as the text domain.
 *
 * @see __()
 * @param string $text
 * @param array  $params
 * @param array  $options
 * @return string
 */
function __m(string $text, array $params = [], array $options = [])
{
    global $gibbon;

    if ($gibbon->session->has('module')) {
        $options['domain'] = $gibbon->session->get('module');
    }

    return $gibbon->locale->translate($text, $params, $options);
}

//$valueMode can be "value" or "id" according to what goes into option's value field
//$selectMode can be "value" or "id" according to what is used to preselect an option
//$honourDefault can TRUE or FALSE, and determines whether or not the default grade is selected

function renderGradeScaleSelect($connection2, $guid, $gibbonScaleID, $fieldName, $valueMode, $honourDefault = true, $width = 50, $selectedMode = 'value', $selectedValue = null)
{
    $return = false;

    $return .= "<select name='$fieldName' id='$fieldName' style='width: ".$width."px'>";
    try {
        $dataSelect = array('gibbonScaleID' => $gibbonScaleID);
        $sqlSelect = 'SELECT * FROM gibbonScaleGrade WHERE gibbonScaleID=:gibbonScaleID ORDER BY sequenceNumber';
        $resultSelect = $connection2->prepare($sqlSelect);
        $resultSelect->execute($dataSelect);
    } catch (PDOException $e) {
    }
    $return .= "<option value=''></option>";
    $sequence = '';
    $descriptor = '';
    while ($rowSelect = $resultSelect->fetch()) {
        $selected = '';
        if ($honourDefault and is_null($selectedValue)) { //Select entry based on scale default
            if ($rowSelect['isDefault'] == 'Y') {
                $selected = 'selected';
            }
        } elseif ($selectedMode == 'value') { //Select entry based on value passed
            if ($rowSelect['value'] == $selectedValue) {
                $selected = 'selected';
            }
        } elseif ($selectedMode == 'id') { //Select entry based on id passed
            if ($rowSelect['gibbonScaleGradeID'] == $selectedValue) {
                $selected = 'selected';
            }
        }
        if ($valueMode == 'value') {
            $return .= "<option $selected value='".htmlPrep($rowSelect['value'])."'>".htmlPrep(__($rowSelect['value'])).'</option>';
        } else {
            $return .= "<option $selected value='".htmlPrep($rowSelect['gibbonScaleGradeID'])."'>".htmlPrep(__($rowSelect['value'])).'</option>';
        }
    }
    $return .= '</select>';

    return $return;
}

//Takes the provided string, and uses a tinymce style valid_elements string to strip out unwanted tags
//Not complete, as it does not strip out unwanted options, just whole tags.
function tinymceStyleStripTags($string, $connection2)
{
    $return = '';

    $comment = html_entity_decode($string);
    $allowableTags = getSettingByScope($connection2, 'System', 'allowableHTML');
    $allowableTags = preg_replace("/\[([^\[\]]|(?0))*]/", '', $allowableTags);
    $allowableTagTokens = explode(',', $allowableTags);
    $allowableTags = '';
    foreach ($allowableTagTokens as $allowableTagToken) {
        $allowableTags .= '&lt;'.$allowableTagToken.'&gt;';
    }
    $allowableTags = html_entity_decode($allowableTags);
    $comment = strip_tags($comment, $allowableTags);

    return $comment;
}

//Archives one or more notifications, based on partial match of actionLink and total match of gibbonPersonID
function archiveNotification($connection2, $guid, $gibbonPersonID, $actionLink)
{
    $return = true;

    try {
        $data = array('gibbonPersonID' => $gibbonPersonID, 'actionLink' => "%$actionLink%");
        $sql = "UPDATE gibbonNotification SET status='Archived' WHERE gibbonPersonID=:gibbonPersonID AND actionLink LIKE :actionLink AND status='New'";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        $return = false;
    }

    return $return;
}

/**
 * @deprecated in v16. Use NotificationSender class.
 */
function setNotification($connection2, $guid, $gibbonPersonID, $text, $moduleName, $actionLink)
{
    global $pdo, $gibbon;

    $notificationGateway = new \Gibbon\Domain\System\NotificationGateway($pdo);
    $notificationSender = new \Gibbon\Comms\NotificationSender($notificationGateway, $gibbon->session);

    $notificationSender->addNotification($gibbonPersonID, $text, $moduleName, $actionLink);
    $success = $notificationSender->sendNotifications();
}

/**
 * @deprecated in v16. Use Format::yesNo
 */
function ynExpander($guid, $yn, $translation = true)
{
    return Format::yesNo($yn, $translation);
}

//Accepts birthday in mysql date (YYYY-MM-DD) ;
function daysUntilNextBirthday($birthday)
{
    $today = date('Y-m-d');
    $btsString = substr($today, 0, 4).'-'.substr($birthday, 5);
    $bts = strtotime($btsString);
    $ts = time();

    if ($bts < $ts) {
        $bts = strtotime(date('y', strtotime('+1 year')).'-'.substr($birthday, 5));
    }

    $days = ceil(($bts - $ts) / 86400);

    //Full year correction, and leap year correction
    $includesLeap = false;
    if (substr($birthday, 5, 2) < 3) { //Born in January or February, so check if this year is a leap year
        $includesLeap = is_leap_year(substr($today, 0, 4));
    } else { //Otherwise, check next year
        $includesLeap = is_leap_year(substr($today, 0, 4) + 1);
    }

    if ($includesLeap == true and $days == 366) {
        $days = 0;
    } elseif ($includesLeap == false and $days == 365) {
        $days = 0;
    }

    return $days;
}

//This function written by David Walsh, shared under MIT License (http://davidwalsh.name/checking-for-leap-year-using-php)
function is_leap_year($year)
{
    return (($year % 4) == 0) && ((($year % 100) != 0) || (($year % 400) == 0));
}

function getSmartWorkflowHelp($connection2, $guid, $step = '')
{
    $output = false;

    try {
        $data = array('gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
        $sql = 'SELECT * FROM gibbonStaff WHERE gibbonPersonID=:gibbonPersonID';
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
    }
    if ($result->rowCount() == 1) {
        $row = $result->fetch();
        if ($row['smartWorkflowHelp'] == 'Y') {
            $output = "<div id='smartWorkflowHelp' class='message' style='padding-top: 14px'>";
            $output .= "<div style='padding: 0 7px'>";
            $output .= "<span style='font-size: 175%'><i><b>".__('Smart Workflow').'</b></i> '.__('Getting Started').'</span><br/>';
            $output .= __("Designed and built by teachers, Gibbon's Smart Workflow takes care of the boring stuff, so you can get on with teaching.").'<br/>';
            $output .= '</div>';
            $output .= "<table cellspacing='0' style='width: 100%; margin: 10px 0px; border-spacing: 4px;'>";
            $output .= '<tr>';
            if ($step == 1) {
                $output .= "<td style='width: 20%; border-top: 3px solid #fff; border-bottom: 2px solid rgba(255,255,255,0.0); background-color: rgba(255,255,255,0.25); padding: 4px'>";
                $output .= "<span style='color: #c00; font-size: 270%; font-weight: bold; letter-spacing: 70%'>".__('One').'</span><br/>';
                $output .= "<span style='color: #c00; font-size: 140%; letter-spacing: 70%'>".sprintf(__('Create %1$s Outcomes'), '<br/>').'</span><br/></span>';
                $output .= '</td>';
            } else {
                $output .= "<td style='width: 20%; border-top: 3px solid #fff; border-bottom: 2px solid #fff; background-color: rgba(255,255,255,0.25); padding: 4px'>";
                $output .= "<span style='font-size: 270%; font-weight: bold; letter-spacing: 70%'>".__('One').'</span><br/>';
                $output .= "<span style='font-size: 140%; letter-spacing: 70%'><a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Planner/outcomes.php'>".sprintf(__('Create %1$s Outcomes'), '<br/>').'</span><br/></a>';
                $output .= '</td>';
            }
            if ($step == 2) {
                $output .= "<td style='width: 20%; border-top: 3px solid #fff; border-bottom: 2px solid rgba(255,255,255,0.0); background-color: rgba(255,255,255,0.25); padding: 4px'>";
                $output .= "<span style='color: #c00; font-size: 270%; font-weight: bold; letter-spacing: 70%'>".__('Two').'</span><br/>';
                $output .= "<span style='color: #c00; font-size: 140%; letter-spacing: 70%'>".sprintf(__('Plan & Deploy %1$s Smart Units'), '<br/>').'</span><br/></span>';
                $output .= '</td>';
            } else {
                $output .= "<td style='width: 20%; border-top: 3px solid #fff; border-bottom: 2px solid #fff; background-color: rgba(255,255,255,0.25); padding: 4px'>";
                $output .= "<span style='font-size: 270%; font-weight: bold; letter-spacing: 70%'>".__('Two').'</span><br/>';
                $output .= "<span style='font-size: 140%; letter-spacing: 70%'><a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Planner/units.php'>".sprintf(__('Plan & Deploy %1$s Smart Units'), '<br/>').'</span><br/></a>';
                $output .= '</td>';
            }
            if ($step == 3) {
                $output .= "<td style='width: 20%; border-top: 3px solid #fff; border-bottom: 2px solid rgba(255,255,255,0.0); background-color: rgba(255,255,255,0.25); padding: 4px'>";
                $output .= "<span style='color: #c00; font-size: 270%; font-weight: bold; letter-spacing: 70%'>".__('Three').'</span><br/>';
                $output .= "<span style='color: #c00; font-size: 140%; letter-spacing: 70%'>".sprintf(__('Share, Teach %1$s & Interact'), '<br/>').'</span><br/></span>';
                $output .= '</td>';
            } else {
                $output .= "<td style='width: 20%; border-top: 3px solid #fff; border-bottom: 2px solid #fff; background-color: rgba(255,255,255,0.25); padding: 4px'>";
                $output .= "<span style='font-size: 270%; font-weight: bold; letter-spacing: 70%'>".__('Three').'</span><br/>';
                $output .= "<span style='font-size: 140%; letter-spacing: 70%'><a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Planner/planner.php'>".sprintf(__('Share, Teach %1$s & Interact'), '<br/>').'</span><br/></a>';
                $output .= '</td>';
            }
            if ($step == 4) {
                $output .= "<td style='width: 20%; border-top: 3px solid #fff; border-bottom: 2px solid rgba(255,255,255,0.0); background-color: rgba(255,255,255,0.25); padding: 4px'>";
                $output .= "<span style='color: #c00; font-size: 270%; font-weight: bold; letter-spacing: 70%'>".__('Four').'</span><br/>';
                $output .= "<span style='color: #c00; font-size: 140%; letter-spacing: 70%'>".sprintf(__('Assign & Collect %1$s Work'), '<br/>').'</span><br/></span>';
                $output .= '</td>';
            } else {
                $output .= "<td style='width: 20%; border-top: 3px solid #fff; border-bottom: 2px solid #fff; background-color: rgba(255,255,255,0.25); padding: 4px'>";
                $output .= "<span style='font-size: 270%; font-weight: bold; letter-spacing: 70%'>".__('Four').'</span><br/>';
                $output .= "<span style='font-size: 140%; letter-spacing: 70%'><a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Planner/planner_deadlines.php'>".sprintf(__('Assign & Collect %1$s Work'), '<br/>').'</span><br/></a>';
                $output .= '</td>';
            }
            if ($step == 5) {
                $output .= "<td style='width: 20%; border-top: 3px solid #fff; border-bottom: 2px solid rgba(255,255,255,0.0); background-color: rgba(255,255,255,0.25); padding: 4px'>";
                $output .= "<span style='color: #c00; font-size: 270%; font-weight: bold; letter-spacing: 70%'>".__('Five').'</span><br/>';
                $output .= "<span style='color: #c00; font-size: 140%; letter-spacing: 70%'>".sprintf(__('Assess & Give %1$s Feedback'), '<br/>').'</span><br/></span>';
                $output .= '</td>';
            } else {
                $output .= "<td style='width: 20%; border-top: 3px solid #fff; border-bottom: 2px solid #fff; background-color: rgba(255,255,255,0.25); padding: 4px'>";
                $output .= "<span style='font-size: 270%; font-weight: bold; letter-spacing: 70%'>".__('Five').'</span><br/>';
                $output .= "<span style='font-size: 140%; letter-spacing: 70%'><a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Markbook/markbook_view.php'>".sprintf(__('Assess & Give %1$s Feedback'), '<br/>').'</span><br/></a>';
                $output .= '</td>';
            }
            $output .= '</tr>';
            if ($step != '') {
                $output .= '<tr>';
                $output .= "<td style='text-align: justify; font-size: 125%; border-bottom: 2px solid #fff; background-color: rgba(255,255,255,0.25); padding: 15px 4px' colspan=5>";
                if ($step == 1) {
                    $output .= __('<b>Outcomes</b> provide a way to plan and track what is being taught in school, and so are a great place to get started.<br/><br/>Click on the "Add" button (below this message, on the right) to add a new outcome, which can either be school-wide, or attached to a particular department.').'<br/>';
                    $output .= "<div style='font-size: 75%; font-style: italic; margin-top: 10px'>".__('<b>Note</b>: You need to be in a department, with the correct permissions, in order to be able to do this.').' '.sprintf(__('Please contact %1$s for help.'), "<a href='mailto:".$_SESSION[$guid]['organisationAdministratorEmail']."'>".$_SESSION[$guid]['organisationAdministratorName'].'</a>').'</div>';
                } elseif ($step == 2) {
                    $output .= __('<b>Smart Units</b> support you in the design of course content, and can be quickly turned into individual lesson plans using intuitive drag and drop. Smart Units cut planning time dramatically, and support ongoing improvement and reuse of content.<br/><br/>Choose a course, using the dropdown menu on the right, and then click on the "Add" button (below this message, on the right) to add a new unit. Once your master unit is complete, deploy it to a class to create your lesson plans.').'<br/>';
                    $output .= "<div style='font-size: 75%; font-style: italic; margin-top: 10px'>".__('<b>Note</b>: You need to be in a department, with the correct permissions, in order to be able to do this.').' '.sprintf(__('Please contact %1$s for help.'), "<a href='mailto:".$_SESSION[$guid]['organisationAdministratorEmail']."'>".$_SESSION[$guid]['organisationAdministratorName'].'</a>').'</div>';
                } elseif ($step == 3) {
                    $output .= sprintf(__('<b>Planner</b> supports online lesson plans which can be shared with students, parents and other teachers. Create your lesson by hand, or automatically via %1$sSmart Units%2$s. Lesson plans facilitate sharing of course content, homework assignment and submission, text chat, and attendance taking.<br/><br/>Choose a date or class, using the menu on the right, and then click on the "Add" button (below this message, on the right) to add a new unit.'), "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Planner/units.php'>", '</a>').'<br/>';
                    $output .= "<div style='font-size: 75%; font-style: italic; margin-top: 10px'>".__('<b>Note</b>: You need to have classes assigned to you, with the correct permissions, in order to be able to do this.').' '.sprintf(__('Please contact %1$s for help.'), "<a href='mailto:".$_SESSION[$guid]['organisationAdministratorEmail']."'>".$_SESSION[$guid]['organisationAdministratorName'].'</a>').'</div>';
                } elseif ($step == 4) {
                    $output .= sprintf(__('<b>Homework + Deadlines</b> allows teachers and students to see upcoming deadlines, cleanly displayed in one place. Click on an entry to view the details for that piece of homework, and the lesson it is attached to.<br/><br/>Homework can be assigned using the %1$sPlanner%2$s, which also allows teachers to view all submitted work, and records late and incomplete work.'), "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Planner/planner.php'>", '</a>').'<br/>';
                    $output .= "<div style='font-size: 75%; font-style: italic; margin-top: 10px'>".__('<b>Note</b>: You need to have classes assigned to you, with the correct permissions, in order to be able to do this.').' '.sprintf(__('Please contact %1$s for help.'), "<a href='mailto:".$_SESSION[$guid]['organisationAdministratorEmail']."'>".$_SESSION[$guid]['organisationAdministratorName'].'</a>').'</div>';
                } elseif ($step == 5) {
                    $output .= sprintf(__('<b>Markbook</b> provides an organised way to assess, record and report on student progress. Use grade scales, rubrics, comments and file uploads to keep students and parents up to date. Link markbooks to the %1$sPlanner%2$s, and see student work as you are marking it.<br/><br/>Choose a class from the menu on the right, and then click on the "Add" button (below this message, on the right) to create a new markbook column.'), "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Planner/planner.php'>", '</a>').'<br/>';
                    $output .= "<div style='font-size: 75%; font-style: italic; margin-top: 10px'>".__('<b>Note</b>: You need to have classes assigned to you, with the correct permissions, in order to be able to do this.').' '.sprintf(__('Please contact %1$s for help.'), "<a href='mailto:".$_SESSION[$guid]['organisationAdministratorEmail']."'>".$_SESSION[$guid]['organisationAdministratorName'].'</a>').'</div>';
                }
                $output .= '</td>';
                $output .= '</tr>';
            }
            $output .= '</table>';
            $output .= "<div style='text-align: right; font-size: 90%; padding: 0 7px'>";
            $output .= "<a title='".__('Dismiss Smart Workflow Help')."' onclick='$(\"#smartWorkflowHelp\").fadeOut(1000); $.ajax({ url: \"".$_SESSION[$guid]['absoluteURL']."/index_SmartWorkflowHelpAjax.php\"})' href='#'>".__('Dismiss Smart Workflow Help').'</a>';
            $output .= '</div>';
            $output .= '</div>';
        }
    }

    return $output;
}

function doesPasswordMatchPolicy($connection2, $passwordNew)
{
    $output = true;

    $alpha = getSettingByScope($connection2, 'System', 'passwordPolicyAlpha');
    $numeric = getSettingByScope($connection2, 'System', 'passwordPolicyNumeric');
    $punctuation = getSettingByScope($connection2, 'System', 'passwordPolicyNonAlphaNumeric');
    $minLength = getSettingByScope($connection2, 'System', 'passwordPolicyMinLength');

    if ($alpha == false or $numeric == false or $punctuation == false or $minLength == false) {
        $output = false;
    } else {
        if ($alpha != 'N' or $numeric != 'N' or $punctuation != 'N' or $minLength >= 0) {
            if ($alpha == 'Y') {
                if (preg_match('`[A-Z]`', $passwordNew) == false or preg_match('`[a-z]`', $passwordNew) == false) {
                    $output = false;
                }
            }
            if ($numeric == 'Y') {
                if (preg_match('`[0-9]`', $passwordNew) == false) {
                    $output = false;
                }
            }
            if ($punctuation == 'Y') {
                if (preg_match('/[^a-zA-Z0-9]/', $passwordNew) == false and strpos($passwordNew, ' ') == false) {
                    $output = false;
                }
            }
            if ($minLength > 0) {
                if (strLen($passwordNew) < $minLength) {
                    $output = false;
                }
            }
        }
    }

    return $output;
}

function getPasswordPolicy($guid, $connection2)
{
    $output = false;

    $alpha = getSettingByScope($connection2, 'System', 'passwordPolicyAlpha');
    $numeric = getSettingByScope($connection2, 'System', 'passwordPolicyNumeric');
    $punctuation = getSettingByScope($connection2, 'System', 'passwordPolicyNonAlphaNumeric');
    $minLength = getSettingByScope($connection2, 'System', 'passwordPolicyMinLength');

    if ($alpha == false or $numeric == false or $punctuation == false or $minLength == false) {
        $output .= __('An error occurred.');
    } elseif ($alpha != 'N' or $numeric != 'N' or $punctuation != 'N' or $minLength >= 0) {
        $output .= __('The password policy stipulates that passwords must:').'<br/>';
        $output .= '<ul>';
        if ($alpha == 'Y') {
            $output .= '<li>'.__('Contain at least one lowercase letter, and one uppercase letter.').'</li>';
        }
        if ($numeric == 'Y') {
            $output .= '<li>'.__('Contain at least one number.').'</li>';
        }
        if ($punctuation == 'Y') {
            $output .= '<li>'.__('Contain at least one non-alphanumeric character (e.g. a punctuation mark or space).').'</li>';
        }
        if ($minLength >= 0) {
            $output .= '<li>'.sprintf(__('Must be at least %1$s characters in length.'), $minLength).'</li>';
        }
        $output .= '</ul>';
    }

    return $output;
}

function getFastFinder($connection2, $guid)
{
    $form = Form::create('fastFinder', $_SESSION[$guid]['absoluteURL'].'/indexFindRedirect.php', 'get');
    $form->setClass('blank fullWidth');

    $form->addHiddenValue('address', $_SESSION[$guid]['address']);

    $row = $form->addRow();
        $row->addFinder('fastFinderSearch')
            ->fromAjax($_SESSION[$guid]['absoluteURL'].'/index_fastFinder_ajax.php')
            ->setClass('w-full text-white')
            ->setParameter('hintText', __('Start typing a name...'))
            ->setParameter('noResultsText', __('No results'))
            ->setParameter('searchingText', __('Searching...'))
            ->setParameter('tokenLimit', 1)
            ->addValidation('Validate.Presence', 'failureMessage: " "');
        $row->addSubmit(__('Go'));

    $highestActionClass = getHighestGroupedAction($guid, '/modules/Planner/planner.php', $connection2);

    $templateData = [
        'roleCategory'        => getRoleCategory($_SESSION[$guid]['gibbonRoleIDCurrent'], $connection2),
        'studentIsAccessible' => isActionAccessible($guid, $connection2, '/modules/students/student_view.php'),
        'staffIsAccessible'   => isActionAccessible($guid, $connection2, '/modules/Staff/staff_view.php'),
        'classIsAccessible'   => isActionAccessible($guid, $connection2, '/modules/Planner/planner.php') && $highestActionClass != 'Lesson Planner_viewMyChildrensClasses',
        'form'                => $form->getOutput(),
    ];

    return $templateData;
}

function getAlert($guid, $connection2, $gibbonAlertLevelID)
{
    $output = false;

    try {
        $dataAlert = array('gibbonAlertLevelID' => $gibbonAlertLevelID);
        $sqlAlert = 'SELECT * FROM gibbonAlertLevel WHERE gibbonAlertLevelID=:gibbonAlertLevelID';
        $resultAlert = $connection2->prepare($sqlAlert);
        $resultAlert->execute($dataAlert);
    } catch (PDOException $e) {
    }
    if ($resultAlert->rowCount() == 1) {
        $rowAlert = $resultAlert->fetch();
        $output = array();
        $output['name'] = __($rowAlert['name']);
        $output['nameShort'] = $rowAlert['nameShort'];
        $output['color'] = $rowAlert['color'];
        $output['colorBG'] = $rowAlert['colorBG'];
        $output['description'] = __($rowAlert['description']);
        $output['sequenceNumber'] = $rowAlert['sequenceNumber'];
    }

    return $output;
}

function getSalt()
{
    $c = explode(' ', '. / a A b B c C d D e E f F g G h H i I j J k K l L m M n N o O p P q Q r R s S t T u U v V w W x X y Y z Z 0 1 2 3 4 5 6 7 8 9');
    $ks = array_rand($c, 22);
    $s = '';
    foreach ($ks as $k) {
        $s .= $c[$k];
    }

    return $s;
}

//Get information on a unit of work, inlcuding the possibility that it is a hooked unit
function getUnit($connection2, $gibbonUnitID, $gibbonCourseClassID)
{
    $output = array();
    $unitType = false;
    if ($gibbonUnitID != '') {
        try {
            $dataUnit = array('gibbonUnitID' => $gibbonUnitID);
            $sqlUnit = 'SELECT * FROM gibbonUnit WHERE gibbonUnitID=:gibbonUnitID';
            $resultUnit = $connection2->prepare($sqlUnit);
            $resultUnit->execute($dataUnit);
            if ($resultUnit->rowCount() == 1) {
                $rowUnit = $resultUnit->fetch();
                if (isset($rowUnit['type'])) {
                    $unitType = $rowUnit['type'];
                }
                $output[0] = $rowUnit['name'];
                $output[1] = '';
            }
        } catch (PDOException $e) {
        }
    }

    return $output;
}

function getWeekNumber($date, $connection2, $guid)
{
    $week = 0;
    try {
        $dataWeek = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
        $sqlWeek = 'SELECT * FROM gibbonSchoolYearTerm WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY sequenceNumber';
        $resultWeek = $connection2->prepare($sqlWeek);
        $resultWeek->execute($dataWeek);
        while ($rowWeek = $resultWeek->fetch()) {
            $firstDayStamp = strtotime($rowWeek['firstDay']);
            $lastDayStamp = strtotime($rowWeek['lastDay']);
            while (date('N', $firstDayStamp) !== '1') {
                $firstDayStamp = $firstDayStamp - 86400;
            }
            $head = $firstDayStamp;
            while ($head <= ($date) and $head < ($lastDayStamp + 86399)) {
                $head = $head + (86400 * 7);
                ++$week;
            }
            if ($head < ($lastDayStamp + 86399)) {
                break;
            }
        }
    } catch (PDOException $e) {
    }

    if ($week <= 0) {
        return false;
    } else {
        return $week;
    }
}

function getModuleEntry($address, $connection2, $guid)
{
    $output = false;

    try {
        $data = array('moduleName' => getModuleName($address), 'gibbonRoleID' => $_SESSION[$guid]['gibbonRoleIDCurrent']);
        $sql = "SELECT DISTINCT gibbonModule.name, gibbonModule.category, gibbonModule.entryURL FROM `gibbonModule`, gibbonAction, gibbonPermission WHERE gibbonModule.name=:moduleName AND (active='Y') AND (gibbonModule.gibbonModuleID=gibbonAction.gibbonModuleID) AND (gibbonAction.gibbonActionID=gibbonPermission.gibbonActionID) AND (gibbonPermission.gibbonRoleID=:gibbonRoleID) ORDER BY category, name";
        $result = $connection2->prepare($sql);
        $result->execute($data);
        if ($result->rowCount() == 1) {
            $row = $result->fetch();
            $entryURL = $row['entryURL'];
            if (isActionAccessible($guid, $connection2, '/modules/'.$row['name'].'/'.$entryURL) == false and $entryURL != 'index.php') {
                try {
                    $dataEntry = array('gibbonRoleID' => $_SESSION[$guid]['gibbonRoleIDCurrent'], 'moduleName' => $row['name']);
                    $sqlEntry = "SELECT DISTINCT gibbonAction.entryURL FROM gibbonModule, gibbonAction, gibbonPermission WHERE (active='Y') AND (gibbonModule.gibbonModuleID=gibbonAction.gibbonModuleID) AND (gibbonAction.gibbonActionID=gibbonPermission.gibbonActionID) AND (gibbonPermission.gibbonRoleID=:gibbonRoleID) AND gibbonModule.name=:moduleName ORDER BY gibbonAction.name";
                    $resultEntry = $connection2->prepare($sqlEntry);
                    $resultEntry->execute($dataEntry);
                    if ($resultEntry->rowCount() > 0) {
                        $rowEntry = $resultEntry->fetch();
                        $entryURL = $rowEntry['entryURL'];
                    }
                } catch (PDOException $e) {
                }
            }
        }
    } catch (PDOException $e) {
    }

    if ($entryURL != '') {
        $output = $entryURL;
    }

    return $output;
}

/**
 * @deprecated in v16. Use Format::name
 */
function formatName($title, $preferredName, $surname, $roleCategory, $reverse = false, $informal = false)
{
    return Format::name($title, $preferredName, $surname, $roleCategory, $reverse, $informal);
}

/**
 * Updated v18 to use a twig template.
 * 
 * $tinymceInit indicates whether or not tinymce should be initialised, or whether this will be done else where later (this can be used to improve page load.
 */
function getEditor($guid, $tinymceInit = true, $id = '', $value = '', $rows = 10, $showMedia = false, $required = false, $initiallyHidden = false, $allowUpload = true, $initialFilter = '', $resourceAlphaSort = false)
{
    global $page;

    $templateData = compact('tinymceInit', 'id', 'value', 'rows', 'showMedia', 'required', 'initiallyHidden', 'allowUpload', 'initialFilter', 'resourceAlphaSort');

    $templateData['absoluteURL'] = $_SESSION[$guid]['absoluteURL'];

    return $page->fetchFromTemplate('components/editor.twig.html', $templateData);
}

function getYearGroups($connection2)
{
    $output = false;
    //Scan through year groups
    //SELECT NORMAL
    try {
        $sql = 'SELECT * FROM gibbonYearGroup ORDER BY sequenceNumber';
        $result = $connection2->query($sql);
        while ($row = $result->fetch()) {
            $output .= $row['gibbonYearGroupID'].',';
            $output .= $row['name'].',';
        }
    } catch (PDOException $e) {
    }

    if ($output != false) {
        $output = substr($output, 0, (strlen($output) - 1));
        $output = explode(',', $output);
    }

    return $output;
}

function getYearGroupsFromIDList($guid, $connection2, $ids, $vertical = false, $translated = true)
{
    $output = false;

    try {
        $sqlYears = 'SELECT DISTINCT nameShort, sequenceNumber FROM gibbonYearGroup ORDER BY sequenceNumber';
        $resultYears = $connection2->query($sqlYears);

        $years = explode(',', $ids);
        if (count($years) > 0 and $years[0] != '') {
            if (count($years) == $resultYears->rowCount()) {
                $output = '<i>All</i>';
            } else {
                try {
                    $dataYears = array();
                    $sqlYearsOr = '';
                    for ($i = 0; $i < count($years); ++$i) {
                        if ($i == 0) {
                            $dataYears[$years[$i]] = $years[$i];
                            $sqlYearsOr = $sqlYearsOr.' WHERE gibbonYearGroupID=:'.$years[$i];
                        } else {
                            $dataYears[$years[$i]] = $years[$i];
                            $sqlYearsOr = $sqlYearsOr.' OR gibbonYearGroupID=:'.$years[$i];
                        }
                    }

                    $sqlYears = "SELECT DISTINCT nameShort, sequenceNumber FROM gibbonYearGroup $sqlYearsOr ORDER BY sequenceNumber";
                    $resultYears = $connection2->prepare($sqlYears);
                    $resultYears->execute($dataYears);
                } catch (PDOException $e) {
                }

                $count3 = 0;
                while ($rowYears = $resultYears->fetch()) {
                    if ($count3 > 0) {
                        if ($vertical == false) {
                            $output .= ', ';
                        } else {
                            $output .= '<br/>';
                        }
                    }
                    if ($translated == true) {
                        $output .= __($rowYears['nameShort']);
                    } else {
                        $output .= $rowYears['nameShort'];
                    }
                    ++$count3;
                }
            }
        } else {
            $output = '<i>None</i>';
        }
    } catch (PDOException $e) {
    }

    return $output;
}

//Gets terms in the specified school year
function getTerms($connection2, $gibbonSchoolYearID, $short = false)
{
    $output = false;
    //Scan through year groups
    try {
        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
        $sql = 'SELECT * FROM gibbonSchoolYearTerm WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY sequenceNumber';
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
    }

    while ($row = $result->fetch()) {
        $output .= $row['gibbonSchoolYearTermID'].',';
        if ($short == true) {
            $output .= $row['nameShort'].',';
        } else {
            $output .= $row['name'].',';
        }
    }
    if ($output != false) {
        $output = substr($output, 0, (strlen($output) - 1));
        $output = explode(',', $output);
    }

    return $output;
}

//Array sort for multidimensional arrays
function msort($array, $id = 'id', $sort_ascending = true)
{
    $temp_array = array();
    while (count($array) > 0) {
        $lowest_id = 0;
        $index = 0;
        foreach ($array as $item) {
            if (isset($item[$id])) {
                if ($array[$lowest_id][$id]) {
                    if (strtolower($item[$id]) < strtolower($array[$lowest_id][$id])) {
                        $lowest_id = $index;
                    }
                }
            }
            ++$index;
        }
        $temp_array[] = $array[$lowest_id];
        $array = array_merge(array_slice($array, 0, $lowest_id), array_slice($array, $lowest_id + 1));
    }
    if ($sort_ascending) {
        return $temp_array;
    } else {
        return array_reverse($temp_array);
    }
}

/**
 * @deprecated in v16. Use Format::address
 */
function addressFormat($address, $addressDistrict, $addressCountry)
{
    return Format::address($address, $addressDistrict, $addressCountry);
}

//Print out, preformatted indicator of max file upload size
function getMaxUpload($guid, $multiple = '')
{
    $output = '';
    $post = substr(ini_get('post_max_size'), 0, (strlen(ini_get('post_max_size')) - 1));
    $file = substr(ini_get('upload_max_filesize'), 0, (strlen(ini_get('upload_max_filesize')) - 1));

    $output .= "<div style='margin-top: 10px; font-style: italic; color: #c00'>";
    if ($multiple == true) {
        if ($post < $file) {
            $output .= sprintf(__('Maximum size for all files: %1$sMB'), $post).'<br/>';
        } else {
            $output .= sprintf(__('Maximum size for all files: %1$sMB'), $file).'<br/>';
        }
    } else {
        if ($post < $file) {
            $output .= sprintf(__('Maximum file size: %1$sMB'), $post).'<br/>';
        } else {
            $output .= sprintf(__('Maximum file size: %1$sMB'), $file).'<br/>';
        }
    }
    $output .= '</div>';

    return $output;
}

//Encode strring using htmlentities with the ENT_QUOTES option
function htmlPrep($str)
{
    return htmlentities($str, ENT_QUOTES, 'UTF-8');
}

//Returns the risk level of the highest-risk condition for an individual
function getHighestMedicalRisk($guid, $gibbonPersonID, $connection2)
{
    $output = false;

    try {
        $dataAlert = array('gibbonPersonID' => $gibbonPersonID);
        $sqlAlert = 'SELECT * FROM gibbonPersonMedical JOIN gibbonPersonMedicalCondition ON (gibbonPersonMedical.gibbonPersonMedicalID=gibbonPersonMedicalCondition.gibbonPersonMedicalID) JOIN gibbonAlertLevel ON (gibbonPersonMedicalCondition.gibbonAlertLevelID=gibbonAlertLevel.gibbonAlertLevelID) WHERE gibbonPersonID=:gibbonPersonID ORDER BY gibbonAlertLevel.sequenceNumber DESC';
        $resultAlert = $connection2->prepare($sqlAlert);
        $resultAlert->execute($dataAlert);
    } catch (PDOException $e) {
    }

    if ($resultAlert->rowCount() > 0) {
        $rowAlert = $resultAlert->fetch();
        $output = array();
        $output[0] = $rowAlert['gibbonAlertLevelID'];
        $output[1] = __($rowAlert['name']);
        $output[2] = $rowAlert['nameShort'];
        $output[3] = $rowAlert['color'];
        $output[4] = $rowAlert['colorBG'];
    }

    return $output;
}

/**
 * @deprecated in v16. Use Format::age
 */
function getAge($guid, $stamp, $short = false, $yearsOnly = false)
{
    return Format::age(date('Y-m-d', $stamp), $short);
}

//Looks at the grouped actions accessible to the user in the current module and returns the highest
function getHighestGroupedAction($guid, $address, $connection2)
{
    $output = false;
    $moduleID = checkModuleReady($address, $connection2);

    try {
        $data = array('actionName' => '%'.getActionName($address).'%', 'gibbonRoleID' => $_SESSION[$guid]['gibbonRoleIDCurrent'], 'moduleID' => $moduleID);
        $sql = 'SELECT gibbonAction.name FROM gibbonAction, gibbonPermission, gibbonRole WHERE (gibbonAction.URLList LIKE :actionName) AND (gibbonAction.gibbonActionID=gibbonPermission.gibbonActionID) AND (gibbonPermission.gibbonRoleID=gibbonRole.gibbonRoleID) AND (gibbonPermission.gibbonRoleID=:gibbonRoleID) AND (gibbonAction.gibbonModuleID=:moduleID) ORDER BY precedence DESC';
        $result = $connection2->prepare($sql);
        $result->execute($data);
        if ($result->rowCount() > 0) {
            $row = $result->fetch();
            $output = $row['name'];
        }
    } catch (PDOException $e) {
    }

    return $output;
}

//Returns the category of the specified role
function getRoleCategory($gibbonRoleID, $connection2)
{
    $output = false;

    try {
        $data = array('gibbonRoleID' => $gibbonRoleID);
        $sql = 'SELECT * FROM gibbonRole WHERE gibbonRoleID=:gibbonRoleID';
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
    }

    if ($result->rowCount() == 1) {
        $row = $result->fetch();
        $output = $row['category'];
    }

    return $output;
}

/**
 * @deprecated in v16. Use Format::timestamp
 */
function dateConvertToTimestamp($date)
{
    return Format::timestamp($date);
}

//Checks to see if a specified date (YYYY-MM-DD) is a day where school is open in the current academic year. There is an option to search all years
function isSchoolOpen($guid, $date, $connection2, $allYears = '')
{
    //Set test variables
    $isInTerm = false;
    $isSchoolDay = false;
    $isSchoolOpen = false;

    //Turn $date into UNIX timestamp and extract day of week
    $timestamp = dateConvertToTimestamp($date);
    $dayOfWeek = date('D', $timestamp);

    //See if date falls into a school term
    try {
        $data = array();
        $sqlWhere = '';
        if ($allYears != true) {
            $data[$_SESSION[$guid]['gibbonSchoolYearID']] = $_SESSION[$guid]['gibbonSchoolYearID'];
            $sqlWhere = ' AND gibbonSchoolYear.gibbonSchoolYearID=:'.$_SESSION[$guid]['gibbonSchoolYearID'];
        }

        $sql = "SELECT gibbonSchoolYearTerm.firstDay, gibbonSchoolYearTerm.lastDay FROM gibbonSchoolYearTerm, gibbonSchoolYear WHERE gibbonSchoolYearTerm.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID $sqlWhere";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
    }
    while ($row = $result->fetch()) {
        if ($date >= $row['firstDay'] and $date <= $row['lastDay']) {
            $isInTerm = true;
        }
    }

    //See if date's day of week is a school day
    if ($isInTerm == true) {
        try {
            $data = array('nameShort' => $dayOfWeek);
            $sql = "SELECT * FROM gibbonDaysOfWeek WHERE nameShort=:nameShort AND schoolDay='Y'";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
        }
        if ($result->rowCount() > 0) {
            $isSchoolDay = true;
        }
    }

    //See if there is a special day
    if ($isInTerm == true and $isSchoolDay == true) {
        try {
            $data = array('date' => $date);
            $sql = "SELECT * FROM gibbonSchoolYearSpecialDay WHERE type='School Closure' AND date=:date";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
        }

        if ($result->rowCount() < 1) {
            $isSchoolOpen = true;
        }
    }

    return $isSchoolOpen;
}

/**
 * @deprecated in v16. Use Format::userPhoto
 */
function printUserPhoto($guid, $path, $size)
{
    echo Format::userPhoto($path, $size);
}

/**
 * @deprecated in v16. Use Format::userPhoto
 */
function getUserPhoto($guid, $path, $size)
{
    return Format::userPhoto($path, $size);
}

//Gets Members of a roll group and prints them as a table.
//Three modes: normal (roll order, surname, firstName), surname (surname, preferredName), preferredName (preferredNam, surname)
function getRollGroupTable($guid, $gibbonRollGroupID, $columns, $connection2, $confidential = true, $orderBy = 'Normal', $print = false)
{
    $return = false;

    $canAccessProfile = (isActionAccessible($guid, $connection2, '/modules/Students/student_view.php', 'View Student Profile_brief') || isActionAccessible($guid, $connection2, '/modules/Students/student_view.php', 'View Student Profile_full') || isActionAccessible($guid, $connection2, '/modules/Students/student_view.php', 'View Student Profile_fullNoNotes') ) ;

    if ($confidential && (isActionAccessible($guid, $connection2, '/modules/Students/student_view.php','View Student Profile_full') == false && isActionAccessible($guid, $connection2, '/modules/Students/student_view.php','View Student Profile_fullNoNotes') == false)) {
        $confidential = false;
    }
    if ($print && (isActionAccessible($guid, $connection2, '/modules/Students/report_students_byRollGroup.php') == false && isActionAccessible($guid, $connection2, '/modules/Students/report_students_byRollGroup_print.php') == false)) {
        $print = false ;
    }

    try {
        $dataRollGroup = array('gibbonRollGroupID' => $gibbonRollGroupID);
        if ($orderBy == 'surname') {
            $sqlRollGroup = "SELECT * FROM gibbonStudentEnrolment INNER JOIN gibbonPerson ON gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID WHERE gibbonRollGroupID=:gibbonRollGroupID AND status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') ORDER BY surname, preferredName";
        } elseif ($orderBy == 'preferredName') {
            $sqlRollGroup = "SELECT * FROM gibbonStudentEnrolment INNER JOIN gibbonPerson ON gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID WHERE gibbonRollGroupID=:gibbonRollGroupID AND status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') ORDER BY preferredName, surname";
        } else {
            $sqlRollGroup = "SELECT * FROM gibbonStudentEnrolment INNER JOIN gibbonPerson ON gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID WHERE gibbonRollGroupID=:gibbonRollGroupID AND status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') ORDER BY rollOrder, surname, preferredName";
        }
        $resultRollGroup = $connection2->prepare($sqlRollGroup);
        $resultRollGroup->execute($dataRollGroup);
    } catch (PDOException $e) {
    }

    if ($print) {
        echo "<div class='linkTop'>";
        echo "<a target='_blank' href='".$_SESSION[$guid]['absoluteURL']."/report.php?q=/modules/Students/report_students_byRollGroup_print.php&gibbonRollGroupID=$gibbonRollGroupID&view=Basic'>".__('Print')."<img style='margin-left: 5px' title='".__('Print')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/print.png'/></a>";
        echo '</div>';
    }

    $return .= "<table class='noIntBorder' cellspacing='0' style='width:100%'>";
    $count = 0;

    if ($confidential) {
        $return .= '<tr>';
        $return .= "<td style='text-align: right' colspan='$columns'>";
        $return .= "<input checked type='checkbox' name='confidential' class='confidential' id='confidential".$gibbonRollGroupID."' value='Yes' />";
        $return .= "<span style='font-size: 85%; font-weight: normal; font-style: italic'> ".__('Show Confidential Data').'</span>';
        $return .= '</td>';
        $return .= '</tr>';
    }

    while ($rowRollGroup = $resultRollGroup->fetch()) {
        if ($count % $columns == 0) {
            $return .= '<tr>';
        }
        $return .= "<td style='width:20%; text-align: center; vertical-align: top'>";

        //Alerts, if permission allows
        if ($confidential) {
            $return .= getAlertBar($guid, $connection2, $rowRollGroup['gibbonPersonID'], $rowRollGroup['privacy'], "id='confidential".$gibbonRollGroupID.'-'.$count."'");
        }

        //User photo
        $return .= getUserPhoto($guid, $rowRollGroup['image_240'], 75);

        //HEY SHORTY IT'S YOUR BIRTHDAY!
        $daysUntilNextBirthday = daysUntilNextBirthday($rowRollGroup['dob']);
        if ($daysUntilNextBirthday == 0) {
            $return .= "<img title='".sprintf(__('%1$s  birthday today!'), $rowRollGroup['preferredName'].'&#39;s')."' style='z-index: 99; margin: -20px 0 0 74px; width: 25px; height: 25px' src='".$_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName']."/img/gift_pink.png'/>";
        } elseif ($daysUntilNextBirthday > 0 and $daysUntilNextBirthday < 8) {
            $return .= "<img title='";
            if ($daysUntilNextBirthday != 1) {
                $return .= sprintf(__('%1$s days until %2$s birthday!'), $daysUntilNextBirthday, $rowRollGroup['preferredName'].'&#39;s');
            } else {
                $return .= sprintf(__('%1$s day until %2$s birthday!'), $daysUntilNextBirthday, $rowRollGroup['preferredName'].'&#39;s');
            }
            $return .= "' style='z-index: 99; margin: -20px 0 0 74px; width: 25px; height: 25px' src='".$_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName']."/img/gift.png'/>";
        }

        $return .= "<div style='padding-top: 5px'><b>";
        if ($canAccessProfile) {
            $return .= "<a href='index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=".$rowRollGroup['gibbonPersonID']."'>".formatName('', $rowRollGroup['preferredName'], $rowRollGroup['surname'], 'Student').'</a><br/><br/>';
        }
        else {
            $return .= formatName('', $rowRollGroup['preferredName'], $rowRollGroup['surname'], 'Student').'<br/><br/>';
        }
        $return .= '</div>';
        $return .= '</td>';

        if ($count % $columns == ($columns - 1)) {
            $return .= '</tr>';
        }
        ++$count;
    }

    for ($i = 0;$i < $columns - ($count % $columns);++$i) {
        $return .= '<td></td>';
    }

    if ($count % $columns != 0) {
        $return .= '</tr>';
    }

    $return .= '</table>';

    $return .= '<script type="text/javascript">
		/* Confidential Control */
		$(document).ready(function(){
			$("#confidential'.$gibbonRollGroupID."\").click(function(){
				if ($('input[id=confidential".$gibbonRollGroupID."]:checked').val()==\"Yes\" ) {";
    for ($i = 0; $i < $count; ++$i) {
        $return .= '$("#confidential'.$gibbonRollGroupID.'-'.$i.'").slideDown("fast", $("#confidential'.$i."\").css(\"{'display' : 'table-row', 'border' : 'right'}\"));";
    }
    $return .= '}
				else {';
    for ($i = 0; $i < $count; ++$i) {
        $return .= '$("#confidential'.$gibbonRollGroupID.'-'.$i.'").slideUp("fast");';
    }
    $return .= '}
			 });
		});
	</script>';

    return $return;
}

//Gets Members of a roll group and prints them as a table.
function printClassGroupTable($guid, $gibbonCourseClassID, $columns, $connection2)
{
    $highestAction = getHighestGroupedAction($guid, '/modules/Students/student_view_details.php', $connection2);
    $canViewStududents = ($highestAction == 'View Student Profile_brief' || $highestAction == 'View Student Profile_full' || $highestAction == 'View Student Profile_fullNoNotes');

    try {
        $dataClassGroup = array('gibbonCourseClassID' => $gibbonCourseClassID);
        $sqlClassGroup = "SELECT * FROM gibbonCourseClassPerson INNER JOIN gibbonPerson ON gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID WHERE gibbonCourseClassID=:gibbonCourseClassID AND status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND (NOT role='Student - Left') AND (NOT role='Teacher - Left') ORDER BY role DESC, surname, preferredName";
        $resultClassGroup = $connection2->prepare($sqlClassGroup);
        $resultClassGroup->execute($dataClassGroup);
    } catch (PDOException $e) {
    }

    echo "<table class='noIntBorder' cellspacing='0' style='width:100%'>";
    $count = 0;
    while ($rowClassGroup = $resultClassGroup->fetch()) {
        if ($canViewStududents == false && $rowClassGroup['role'] == 'Student') continue;

        if ($count % $columns == 0) {
            echo '<tr>';
        }
        echo "<td style='width:20%; text-align: center; vertical-align: top'>";

        //Alerts, if permission allows
        echo getAlertBar($guid, $connection2, $rowClassGroup['gibbonPersonID'], $rowClassGroup['privacy']);

        //User photo
        echo getUserPhoto($guid, $rowClassGroup['image_240'], 75);

        //HEY SHORTY IT'S YOUR BIRTHDAY!
        $daysUntilNextBirthday = daysUntilNextBirthday($rowClassGroup['dob']);
        if ($daysUntilNextBirthday == 0) {
            echo "<img title='".sprintf(__('%1$s  birthday today!'), $rowClassGroup['preferredName'].'&#39;s')."' style='z-index: 99; margin: -20px 0 0 74px; width: 25px; height: 25px' src='".$_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName']."/img/gift_pink.png'/>";
        } elseif ($daysUntilNextBirthday > 0 and $daysUntilNextBirthday < 8) {
            echo "<img title='$daysUntilNextBirthday ";
            if ($daysUntilNextBirthday != 1) {
                echo sprintf(__('days until %1$s birthday!'), $rowClassGroup['preferredName'].'&#39;s');
            } else {
                echo sprintf(__('day until %1$s birthday!'), $rowClassGroup['preferredName'].'&#39;s');
            }
            echo "' style='z-index: 99; margin: -20px 0 0 74px; width: 25px; height: 25px' src='".$_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName']."/img/gift.png'/>";
        }

        if ($rowClassGroup['role'] == 'Student') {
            echo "<div style='padding-top: 5px'><b><a href='index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=".$rowClassGroup['gibbonPersonID']."'>".formatName('', $rowClassGroup['preferredName'], $rowClassGroup['surname'], 'Student').'</a></b><br/>';
        } else {
            echo "<div style='padding-top: 5px'><b>".formatName($rowClassGroup['title'], $rowClassGroup['preferredName'], $rowClassGroup['surname'], 'Staff').'</b><br/>';
        }

        echo '<i>'.__($rowClassGroup['role']).'</i><br/><br/></div>';
        echo '</td>';

        if ($count % $columns == ($columns - 1)) {
            echo '</tr>';
        }
        ++$count;
    }

    for ($i = 0;$i < $columns - ($count % $columns);++$i) {
        echo '<td></td>';
    }

    if ($count % $columns != 0) {
        echo '</tr>';
    }

    echo '</table>';
}

function getAlertBar($guid, $connection2, $gibbonPersonID, $privacy = '', $divExtras = '', $div = true, $large = false)
{
    $output = '';

    $width = '14';
    $height = '13';
    $fontSize = '12';
    $totalHeight = '16';
    if ($large) {
        $width = '42';
        $height = '35';
        $fontSize = '39';
        $totalHeight = '45';
    }

    $highestAction = getHighestGroupedAction($guid, '/modules/Students/student_view_details.php', $connection2);
    if ($highestAction == 'View Student Profile_full' or $highestAction == 'View Student Profile_fullNoNotes') {
        if ($div == true) {
            $output .= "<div $divExtras style='width: 83px; text-align: right; height: ".$totalHeight."px; padding: 3px 0px; margin: auto'><b>";
        }

        //Individual Needs
        try {
            $dataAlert = array('gibbonPersonID' => $gibbonPersonID);
            $sqlAlert = 'SELECT * FROM gibbonINPersonDescriptor JOIN gibbonAlertLevel ON (gibbonINPersonDescriptor.gibbonAlertLevelID=gibbonAlertLevel.gibbonAlertLevelID) WHERE gibbonPersonID=:gibbonPersonID ORDER BY sequenceNumber DESC';
            $resultAlert = $connection2->prepare($sqlAlert);
            $resultAlert->execute($dataAlert);
        } catch (PDOException $e) { }
        if ($resultAlert->rowCount() > 0) {
            $rowAlert = $resultAlert->fetch();
            $highestLevel = __($rowAlert['name']);
            $highestColour = $rowAlert['color'];
            $highestColourBG = $rowAlert['colorBG'];
            if ($resultAlert->rowCount() == 1) {
                $title = $resultAlert->rowCount().' '.sprintf(__('Individual Needs alert is set, with an alert level of %1$s.'), $rowAlert['name']);
            } else {
                $title = $resultAlert->rowCount().' '.sprintf(__('Individual Needs alerts are set, up to a maximum alert level of %1$s.'), $rowAlert['name']);
            }
            $output .= "<a style='font-size: ".$fontSize.'px; color: #'.$highestColour."; text-decoration: none' href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Students/student_view_details.php&gibbonPersonID='.$gibbonPersonID."&subpage=Individual Needs'><div title='$title' style='float: left; text-align: center; vertical-align: middle; max-height: ".$height.'px; height: '.$height.'px; width: '.$width.'px; border-top: 2px solid #'.$highestColour.'; margin-right: 2px; background-color: #'.$highestColourBG."'>".__('IN').'</div></a>';
        }

        //Academic
        $gibbonAlertLevelID = '';
        $alertThresholdText = '';
        try {
            $dataAlert = array('gibbonPersonIDStudent' => $gibbonPersonID, 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'today' => date('Y-m-d'), 'date' => date('Y-m-d', (time() - (24 * 60 * 60 * 60))));
            $sqlAlert = "SELECT *
                FROM gibbonMarkbookEntry
                    JOIN gibbonMarkbookColumn ON (gibbonMarkbookEntry.gibbonMarkbookColumnID=gibbonMarkbookColumn.gibbonMarkbookColumnID)
                    JOIN gibbonCourseClass ON (gibbonMarkbookColumn.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID)
                    JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID)
                WHERE gibbonPersonIDStudent=:gibbonPersonIDStudent
                    AND (attainmentConcern='Y' OR effortConcern='Y')
                    AND complete='Y'
                    AND gibbonSchoolYearID=:gibbonSchoolYearID
                    AND completeDate<=:today
                    AND completeDate>:date
                    ";
            $resultAlert = $connection2->prepare($sqlAlert);
            $resultAlert->execute($dataAlert);
        } catch (PDOException $e) {
            $_SESSION[$guid]['sidebarExtra'] .= "<div class='error'>".$e->getMessage().'</div>';
        }

        $academicAlertLowThreshold = getSettingByScope($connection2, 'Students', 'academicAlertLowThreshold');
        $academicAlertMediumThreshold = getSettingByScope($connection2, 'Students', 'academicAlertMediumThreshold');
        $academicAlertHighThreshold = getSettingByScope($connection2, 'Students', 'academicAlertHighThreshold');

        if ($resultAlert->rowCount() >= $academicAlertHighThreshold) {
            $gibbonAlertLevelID = 001;
            $alertThresholdText = sprintf(__('This alert level occurs when there are more than %1$s events recorded for a student.'), $academicAlertHighThreshold);
        } elseif ($resultAlert->rowCount() >= $academicAlertMediumThreshold) {
            $gibbonAlertLevelID = 002;
            $alertThresholdText = sprintf(__('This alert level occurs when there are between %1$s and %2$s events recorded for a student.'), $academicAlertMediumThreshold, ($academicAlertHighThreshold-1));
        } elseif ($resultAlert->rowCount() >= $academicAlertLowThreshold) {
            $gibbonAlertLevelID = 003;
            $alertThresholdText = sprintf(__('This alert level occurs when there are between %1$s and %2$s events recorded for a student.'), $academicAlertLowThreshold, ($academicAlertMediumThreshold-1));
        }
        if ($gibbonAlertLevelID != '') {
            $alert = getAlert($guid, $connection2, $gibbonAlertLevelID);
            if ($alert != false) {
                $title = sprintf(__('Student has a %1$s alert for academic concern over the past 60 days.'), __($alert['name'])).' '.$alertThresholdText;
                $output .= "<a style='font-size: ".$fontSize.'px; color: #'.$alert['color']."; text-decoration: none' href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Students/student_view_details.php&gibbonPersonID='.$gibbonPersonID.'&subpage=Markbook&filter='.$_SESSION[$guid]['gibbonSchoolYearID']."'><div title='$title' style='float: left; text-align: center; vertical-align: middle; max-height: ".$height.'px; height: '.$height.'px; width: '.$width.'px; border-top: 2px solid #'.$alert['color'].'; margin-right: 2px; background-color: #'.$alert['colorBG']."'>".__('A').'</div></a>';
            }
        }

        //Behaviour
        $gibbonAlertLevelID = '';
        $alertThresholdText = '';
        try {
            $dataAlert = array('gibbonPersonID' => $gibbonPersonID, 'date' => date('Y-m-d', (time() - (24 * 60 * 60 * 60))));
            $sqlAlert = "SELECT * FROM gibbonBehaviour WHERE gibbonPersonID=:gibbonPersonID AND type='Negative' AND date>:date";
            $resultAlert = $connection2->prepare($sqlAlert);
            $resultAlert->execute($dataAlert);
        } catch (PDOException $e) {
            $_SESSION[$guid]['sidebarExtra'] .= "<div class='error'>".$e->getMessage().'</div>';
        }

        $behaviourAlertLowThreshold = getSettingByScope($connection2, 'Students', 'behaviourAlertLowThreshold');
        $behaviourAlertMediumThreshold = getSettingByScope($connection2, 'Students', 'behaviourAlertMediumThreshold');
        $behaviourAlertHighThreshold = getSettingByScope($connection2, 'Students', 'behaviourAlertHighThreshold');

        if ($resultAlert->rowCount() >= $behaviourAlertHighThreshold) {
            $gibbonAlertLevelID = 001;
            $alertThresholdText = sprintf(__('This alert level occurs when there are more than %1$s events recorded for a student.'), $behaviourAlertHighThreshold);
        } elseif ($resultAlert->rowCount() >= $behaviourAlertMediumThreshold) {
            $gibbonAlertLevelID = 002;
            $alertThresholdText = sprintf(__('This alert level occurs when there are between %1$s and %2$s events recorded for a student.'), $behaviourAlertMediumThreshold, ($behaviourAlertHighThreshold-1));
        } elseif ($resultAlert->rowCount() >= $behaviourAlertLowThreshold) {
            $gibbonAlertLevelID = 003;
            $alertThresholdText = sprintf(__('This alert level occurs when there are between %1$s and %2$s events recorded for a student.'), $behaviourAlertLowThreshold, ($behaviourAlertMediumThreshold-1));
        }

        if ($gibbonAlertLevelID != '') {
            $alert = getAlert($guid, $connection2, $gibbonAlertLevelID);
            if ($alert != false) {
                $title = sprintf(__('Student has a %1$s alert for behaviour over the past 60 days.'), __($alert['name'])).' '.$alertThresholdText;
                $output .= "<a style='font-size: ".$fontSize.'px; color: #'.$alert['color']."; text-decoration: none' href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Students/student_view_details.php&gibbonPersonID='.$gibbonPersonID."&subpage=Behaviour'><div title='$title' style='float: left; text-align: center; vertical-align: middle; max-height: ".$height.'px; height: '.$height.'px; width: '.$width.'px; border-top: 2px solid #'.$alert['color'].'; margin-right: 2px; background-color: #'.$alert['colorBG']."'>".__('B').'</div></a>';
            }
        }

        //Medical
        $alert = getHighestMedicalRisk($guid,  $gibbonPersonID, $connection2);
        if ($alert != false) {
            $highestLevel = $alert[1];
            $highestColour = $alert[3];
            $highestColourBG = $alert[4];
            $title = sprintf(__('Medical alerts are set, up to a maximum of %1$s'), $highestLevel);
            $output .= "<a style='font-size: ".$fontSize.'px; color: #'.$highestColour."; text-decoration: none' href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Students/student_view_details.php&gibbonPersonID='.$gibbonPersonID."&subpage=Medical'><div title='$title' style='float: left; text-align: center; vertical-align: middle; max-height: ".$height.'px; height: '.$height.'px; width: '.$width.'px; border-top: 2px solid #'.$highestColour.'; margin-right: 2px; background-color: #'.$highestColourBG."'><b>".__('M').'</b></div></a>';
        }

        //Privacy
        $privacySetting = getSettingByScope($connection2, 'User Admin', 'privacy');
        if ($privacySetting == 'Y' and $privacy != '') {
            $alert = getAlert($guid, $connection2, 001);
            $title = sprintf(__('Privacy is required: %1$s'), $privacy);
            $output .= "<div title='$title' style='font-size: ".$fontSize.'px; float: left; text-align: center; vertical-align: middle; max-height: '.$height.'px; height: '.$height.'px; width: '.$width.'px; border-top: 2px solid #'.$alert['color'].'; margin-right: 2px; color: #'.$alert['color'].'; background-color: #'.$alert['colorBG']."'>".__('P').'</div>';
        }

        if ($div == true) {
            $output .= '</div>';
        }
    }

    return $output;
}

//Gets system settings from database and writes them to individual session variables.
function getSystemSettings($guid, $connection2)
{

    //System settings from gibbonSetting
    try {
        $data = array();
        $sql = "SELECT * FROM gibbonSetting WHERE scope='System'";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        $_SESSION[$guid]['systemSettingsSet'] = false;
    }

    while ($row = $result->fetch()) {
        $name = $row['name'];
        $_SESSION[$guid][$name] = $row['value'];
    }

    //Get names and emails for administrator, dba, admissions
    //System Administrator
    try {
        $data = array('gibbonPersonID' => $_SESSION[$guid]['organisationAdministrator']);
        $sql = 'SELECT surname, preferredName, email FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonID';
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
    }
    if ($result->rowCount() == 1) {
        $row = $result->fetch();
        $_SESSION[$guid]['organisationAdministratorName'] = formatName('', $row['preferredName'], $row['surname'], 'Staff', false, true);
        $_SESSION[$guid]['organisationAdministratorEmail'] = $row['email'];
    }
    //DBA
    try {
        $data = array('gibbonPersonID' => $_SESSION[$guid]['organisationDBA']);
        $sql = 'SELECT surname, preferredName, email FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonID';
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
    }
    if ($result->rowCount() == 1) {
        $row = $result->fetch();
        $_SESSION[$guid]['organisationDBAName'] = formatName('', $row['preferredName'], $row['surname'], 'Staff', false, true);
        $_SESSION[$guid]['organisationDBAEmail'] = $row['email'];
    }
    //Admissions
    try {
        $data = array('gibbonPersonID' => $_SESSION[$guid]['organisationAdmissions']);
        $sql = 'SELECT surname, preferredName, email FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonID';
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
    }
    if ($result->rowCount() == 1) {
        $row = $result->fetch();
        $_SESSION[$guid]['organisationAdmissionsName'] = formatName('', $row['preferredName'], $row['surname'], 'Staff', false, true);
        $_SESSION[$guid]['organisationAdmissionsEmail'] = $row['email'];
    }
    //HR Administraotr
    try {
        $data = array('gibbonPersonID' => $_SESSION[$guid]['organisationHR']);
        $sql = 'SELECT surname, preferredName, email FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonID';
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
    }
    if ($result->rowCount() == 1) {
        $row = $result->fetch();
        $_SESSION[$guid]['organisationHRName'] = formatName('', $row['preferredName'], $row['surname'], 'Staff', false, true);
        $_SESSION[$guid]['organisationHREmail'] = $row['email'];
    }

    //Language settings from gibboni18n
    try {
        $data = array();
        $sql = "SELECT * FROM gibboni18n WHERE systemDefault='Y'";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        $_SESSION[$guid]['systemSettingsSet'] = false;
    }
    if ($result->rowCount() == 1) {
        $row = $result->fetch();
        setLanguageSession($guid, $row);
    }

    $_SESSION[$guid]['systemSettingsSet'] = true;
}

//Set language session variables
function setLanguageSession($guid, $row, $defaultLanguage = true)
{
    $_SESSION[$guid]['i18n']['gibboni18nID'] = $row['gibboni18nID'];
    $_SESSION[$guid]['i18n']['code'] = $row['code'];
    $_SESSION[$guid]['i18n']['name'] = $row['name'];
    $_SESSION[$guid]['i18n']['dateFormat'] = $row['dateFormat'];
    $_SESSION[$guid]['i18n']['dateFormatRegEx'] = $row['dateFormatRegEx'];
    $_SESSION[$guid]['i18n']['dateFormatPHP'] = $row['dateFormatPHP'];
    $_SESSION[$guid]['i18n']['rtl'] = $row['rtl'];

    if ($defaultLanguage) {
        $_SESSION[$guid]['i18n']['default']['code'] = $row['code'];
        $_SESSION[$guid]['i18n']['default']['name'] = $row['name'];
    }
}

//Gets the desired setting, specified by name and scope.
function getSettingByScope($connection2, $scope, $name, $returnRow = false )
{
    try {
        $data = array('scope' => $scope, 'name' => $name);
        $sql = 'SELECT * FROM gibbonSetting WHERE scope=:scope AND name=:name';
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
    }

    if ($result && $result->rowCount() == 1) {

        if ($returnRow) {
            return $result->fetch();
        } else {
            $row = $result->fetch();
            return $row['value'];
        }
    }

    return false;
}

/**
 * Converts date from language-specific format to YYYY-MM-DD. DEPRECATED.
 *
 * @deprecated in v16. Use Format::dateConvert
 */
function dateConvert($guid, $date)
{
    return Format::dateConvert($date);
}

/**
 * Converts date from YYYY-MM-DD to language-specific format. DEPRECATED.
 *
 * @deprecated in v16. Use Format::date
 */
function dateConvertBack($guid, $date)
{
    return Format::date($date);
}

function isActionAccessible($guid, $connection2, $address, $sub = '')
{
    $output = false;
    //Check user is logged in
    if (isset($_SESSION[$guid]['username'])) {
        //Check user has a current role set
        if ($_SESSION[$guid]['gibbonRoleIDCurrent'] != '') {
            //Check module ready
            $moduleID = checkModuleReady($address, $connection2);
            if ($moduleID != false) {
                //Check current role has access rights to the current action.
                try {
                    $data = array('actionName' => '%'.getActionName($address).'%', 'gibbonRoleID' => $_SESSION[$guid]['gibbonRoleIDCurrent']);
                    $sqlWhere = '';
                    if ($sub != '') {
                        $data['sub'] = $sub;
                        $sqlWhere = 'AND gibbonAction.name=:sub';
                    }
                    $sql = "SELECT gibbonAction.name FROM gibbonAction, gibbonPermission, gibbonRole WHERE (gibbonAction.URLList LIKE :actionName) AND (gibbonAction.gibbonActionID=gibbonPermission.gibbonActionID) AND (gibbonPermission.gibbonRoleID=gibbonRole.gibbonRoleID) AND (gibbonPermission.gibbonRoleID=:gibbonRoleID) AND (gibbonAction.gibbonModuleID=$moduleID) $sqlWhere";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                    if ($result->rowCount() > 0) {
                        $output = true;
                    }
                } catch (PDOException $e) {
                }
            }
        }
    }

    return $output;
}

function isModuleAccessible($guid, $connection2, $address = '')
{
    if ($address == '') {
        $address = $_SESSION[$guid]['address'];
    }
    $output = false;
    //Check user is logged in
    if ($_SESSION[$guid]['username'] != '') {
        //Check user has a current role set
        if ($_SESSION[$guid]['gibbonRoleIDCurrent'] != '') {
            //Check module ready
            $moduleID = checkModuleReady($address, $connection2);
            if ($moduleID != false) {
                //Check current role has access rights to an action in the current module.
                try {
                    $data = array('gibbonRoleID' => $_SESSION[$guid]['gibbonRoleIDCurrent'], 'moduleID' => $moduleID);
                    $sql = 'SELECT * FROM gibbonAction, gibbonPermission, gibbonRole WHERE (gibbonAction.gibbonActionID=gibbonPermission.gibbonActionID) AND (gibbonPermission.gibbonRoleID=gibbonRole.gibbonRoleID) AND (gibbonPermission.gibbonRoleID=:gibbonRoleID) AND (gibbonAction.gibbonModuleID=:moduleID)';
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                    if ($result->rowCount() > 0) {
                        $output = true;
                    }
                } catch (PDOException $e) {
                }
            }
        }
    }

    return $output;
}

/**
 * @deprecated in v16. Use DataTables::createdPaginated()
 */
function printPagination($guid, $total, $page, $pagination, $position, $get = '')
{
    if ($position == 'bottom') {
        $class = 'paginationBottom';
    } else {
        $class = 'paginationTop';
    }

    echo "<div class='$class'>";
    $totalPages = ceil($total / $pagination);
    $i = 0;
    echo __('Records').' '.(($page - 1) * $_SESSION[$guid]['pagination'] + 1).'-';
    if (($page * $_SESSION[$guid]['pagination']) > $total) {
        echo $total;
    } else {
        echo $page * $_SESSION[$guid]['pagination'];
    }
    echo ' '.__('of').' '.$total.' : ';

    if ($totalPages <= 10) {
        for ($i = 0;$i <= ($total / $pagination);++$i) {
            if ($i == ($page - 1)) {
                echo "$page ";
            } else {
                echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q='.$_SESSION[$guid]['address'].'&page='.($i + 1)."&$get'>".($i + 1).'</a> ';
            }
        }
    } else {
        if ($page > 1) {
            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q='.$_SESSION[$guid]['address']."&page=1&$get'>".__('First').'</a> ';
            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q='.$_SESSION[$guid]['address'].'&page='.($page - 1)."&$get'>".__('Previous').'</a> ';
        } else {
            echo __('First').' '.__('Previous').' ';
        }

        $spread = 10;
        for ($i = 0;$i <= ($total / $pagination);++$i) {
            if ($i == ($page - 1)) {
                echo "$page ";
            } elseif ($i > ($page - (($spread / 2) + 2)) and $i < ($page + (($spread / 2)))) {
                echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q='.$_SESSION[$guid]['address'].'&page='.($i + 1)."&$get'>".($i + 1).'</a> ';
            }
        }

        if ($page != $totalPages) {
            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q='.$_SESSION[$guid]['address'].'&page='.($page + 1)."&$get'>".__('Next').'</a> ';
            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q='.$_SESSION[$guid]['address'].'&page='.$totalPages."&$get'>".__('Last').'</a> ';
        } else {
            echo __('Next').' '.__('Last');
        }
    }
    echo '</div>';
}

//Get list of user roles from database, and convert to array
function getRoleList($gibbonRoleIDAll, $connection2)
{
    $output = array();

    //Tokenise list of roles
    $roles = explode(',', $gibbonRoleIDAll);

    //Check that roles exist
    $count = 0;
    for ($i = 0; $i < count($roles); ++$i) {
        try {
            $data = array('gibbonRoleID' => $roles[$i]);
            $sql = 'SELECT * FROM gibbonRole WHERE gibbonRoleID=:gibbonRoleID';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
        }
        if ($result->rowCount() == 1) {
            $row = $result->fetch();
            $output[$count][0] = $row['gibbonRoleID'];
            $output[$count][1] = $row['name'];
            ++$count;
        }
    }

    //Return list of roles
    return $output;
}

//Get the module name from the address
function getModuleName($address)
{
    return substr(substr($address, 9), 0, strpos(substr($address, 9), '/'));
}

//Get the action name from the address
function getActionName($address)
{
    return substr($address, (10 + strlen(getModuleName($address))));
}

//Using the current address, checks to see that a module exists and is ready to use, returning the ID if it is
function checkModuleReady($address, $connection2)
{
    $output = false;

    //Get module name from address
    $module = getModuleName($address);
    try {
        $data = array('name' => $module);
        $sql = "SELECT * FROM gibbonModule WHERE name=:name AND active='Y'";
        $result = $connection2->prepare($sql);
        $result->execute($data);
        if ($result->rowCount() == 1) {
            $row = $result->fetch();
            $output = $row['gibbonModuleID'];
        }
    } catch (PDOException $e) {
    }

    return $output;
}

//Using the current address, get's the module's category
function getModuleCategory($address, $connection2)
{
    $output = false;

    //Get module name from address
    $module = getModuleName($address);

    try {
        $data = array('name' => $module);
        $sql = "SELECT * FROM gibbonModule WHERE name=:name AND active='Y'";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
    }
    if ($result->rowCount() == 1) {
        $row = $result->fetch();
        $output = __($row['category']);
    }

    return $output;
}

//GET THE CURRENT YEAR AND SET IT AS A GLOBAL VARIABLE
function setCurrentSchoolYear($guid,  $connection2)
{
    //Run query
    try {
        $data = array();
        $sql = "SELECT * FROM gibbonSchoolYear WHERE status='Current'";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
    }

    //Check number of rows returned.
    //If it is not 1, show error
    if (!($result->rowCount() == 1)) {
        die(__('Configuration Error: there is a problem accessing the current Academic Year from the database.'));
    }
    //Else get schoolYearID
    else {
        $row = $result->fetch();
        $_SESSION[$guid]['gibbonSchoolYearID'] = $row['gibbonSchoolYearID'];
        $_SESSION[$guid]['gibbonSchoolYearName'] = $row['name'];
        $_SESSION[$guid]['gibbonSchoolYearSequenceNumber'] = $row['sequenceNumber'];
        $_SESSION[$guid]['gibbonSchoolYearFirstDay'] = $row['firstDay'];
        $_SESSION[$guid]['gibbonSchoolYearLastDay'] = $row['lastDay'];
    }
}

function nl2brr($string)
{
    return preg_replace("/\r\n|\n|\r/", '<br/>', $string);
}

//Take a school year, and return the previous one, or false if none
function getPreviousSchoolYearID($gibbonSchoolYearID, $connection2)
{
    $output = false;

    try {
        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
        $sql = 'SELECT * FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearID';
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
    }
    if ($result->rowcount() == 1) {
        $row = $result->fetch();
        try {
            $dataPrevious = array('sequenceNumber' => $row['sequenceNumber']);
            $sqlPrevious = 'SELECT * FROM gibbonSchoolYear WHERE sequenceNumber<:sequenceNumber ORDER BY sequenceNumber DESC';
            $resultPrevious = $connection2->prepare($sqlPrevious);
            $resultPrevious->execute($dataPrevious);
        } catch (PDOException $e) {
        }
        if ($resultPrevious->rowCount() >= 1) {
            $rowPrevious = $resultPrevious->fetch();
            $output = $rowPrevious['gibbonSchoolYearID'];
        }
    }

    return $output;
}

//Take a school year, and return the previous one, or false if none
function getNextSchoolYearID($gibbonSchoolYearID, $connection2)
{
    $output = false;

    try {
        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
        $sql = 'SELECT * FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearID';
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
    }
    if ($result->rowcount() == 1) {
        $row = $result->fetch();
        try {
            $dataPrevious = array('sequenceNumber' => $row['sequenceNumber']);
            $sqlPrevious = 'SELECT * FROM gibbonSchoolYear WHERE sequenceNumber>:sequenceNumber ORDER BY sequenceNumber ASC';
            $resultPrevious = $connection2->prepare($sqlPrevious);
            $resultPrevious->execute($dataPrevious);
        } catch (PDOException $e) {
        }
        if ($resultPrevious->rowCount() >= 1) {
            $rowPrevious = $resultPrevious->fetch();
            $output = $rowPrevious['gibbonSchoolYearID'];
        }
    }

    return $output;
}

//Take a year group, and return the next one, or false if none
function getNextYearGroupID($gibbonYearGroupID, $connection2)
{
    $output = false;
    try {
        $data = array('gibbonYearGroupID' => $gibbonYearGroupID);
        $sql = 'SELECT * FROM gibbonYearGroup WHERE gibbonYearGroupID=:gibbonYearGroupID';
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
    }
    if ($result->rowCount() == 1) {
        $row = $result->fetch();
        try {
            $dataPrevious = array('sequenceNumber' => $row['sequenceNumber']);
            $sqlPrevious = 'SELECT * FROM gibbonYearGroup WHERE sequenceNumber>:sequenceNumber ORDER BY sequenceNumber ASC';
            $resultPrevious = $connection2->prepare($sqlPrevious);
            $resultPrevious->execute($dataPrevious);
        } catch (PDOException $e) {
        }
        if ($resultPrevious->rowCount() >= 1) {
            $rowPrevious = $resultPrevious->fetch();
            $output = $rowPrevious['gibbonYearGroupID'];
        }
    }

    return $output;
}

//Take a roll group, and return the next one, or false if none
function getNextRollGroupID($gibbonRollGroupID, $connection2)
{
    $output = false;
    try {
        $data = array('gibbonRollGroupID' => $gibbonRollGroupID);
        $sql = 'SELECT * FROM gibbonRollGroup WHERE gibbonRollGroupID=:gibbonRollGroupID';
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) { }
    if ($result->rowCount() == 1) {
        $row = $result->fetch();
        if (!is_null($row['gibbonRollGroupIDNext'])) {
            $output = $row['gibbonRollGroupIDNext'];
        }
    }

    return $output;
}

//Return the last school year in the school, or false if none
function getLastYearGroupID($connection2)
{
    $output = false;
    try {
        $data = array();
        $sql = 'SELECT * FROM gibbonYearGroup ORDER BY sequenceNumber DESC';
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
    }
    if ($result->rowCount() > 1) {
        $row = $result->fetch();
        $output = $row['gibbonYearGroupID'];
    }

    return $output;
}

function randomPassword($length)
{
    if (!(is_int($length))) {
        $length = 8;
    } elseif ($length > 255) {
        $length = 255;
    }

    $charList = 'abcdefghijkmnopqrstuvwxyz023456789';
    $password = '';

    //Generate the password
    for ($i = 0;$i < $length;++$i) {
        $password = $password.substr($charList, rand(1, strlen($charList)), 1);
    }

    return $password;
}

/**
 * @deprecated in v16. Use Format::phone()
 */
function formatPhone($num)
{
    return Format::phone($num);
}

function setLog($connection2, $gibbonSchoolYearID, $gibbonModuleID, $gibbonPersonID, $title, $array = null, $ip = null)
{
    if ((!is_array($array) && $array != null) || $title == null || $gibbonSchoolYearID == null) {
        return;
    }

    $ip = (empty($ip) ? getIPAddress() : $ip);

    if ($array != null) {
        $serialisedArray = serialize($array);
    } else {
        $serialisedArray = null;
    }
    try {
        $dataLog = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonModuleID' => $gibbonModuleID, 'gibbonPersonID' => $gibbonPersonID, 'title' => $title, 'serialisedArray' => $serialisedArray, 'ip' => $ip);
        $sqlLog = 'INSERT INTO gibbonLog SET gibbonSchoolYearID=:gibbonSchoolYearID, gibbonModuleID=:gibbonModuleID, gibbonPersonID=:gibbonPersonID, title=:title, serialisedArray=:serialisedArray, ip=:ip';
        $resultLog = $connection2->prepare($sqlLog);
        $resultLog->execute($dataLog);
    } catch (PDOException $e) {
        return;
    }
    $gibbonLogID = $connection2->lastInsertId();

    return $gibbonLogID;
}

function getLog($connection2, $gibbonSchoolYearID, $gibbonModuleID = null, $gibbonPersonID = null, $title = null, $startDate = null, $endDate = null, $ip = null, $array = null)
{
    if ($gibbonSchoolYearID == null || $gibbonSchoolYearID == '') {
        return;
    }
    $dataLog = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
    $where = '';

    if (is_array($array) && $array != null && $array != '' && !empty($array)) {
        $valNum = 0;
        foreach ($array as $key => $val) {
            $keyName = 'key'.$valNum;
            $dataLog[$keyName] = $key;
            $valName = 'val'.$valNum;
            $dataLog[$valName] = $val;
            $where .= " AND serialisedArray LIKE CONCAT('%', :".$keyName.", '%;%', :".$valName.", '%')";
            ++$valNum;
        }
    }

    if ($gibbonModuleID != null && $gibbonModuleID != '') {
        $dataLog['gibbonModuleID'] = $gibbonModuleID;
        $where .= ' AND gibbonModuleID=:gibbonModuleID';
    }

    if ($gibbonPersonID != null && $gibbonPersonID != '') {
        $dataLog['gibbonPersonID'] = $gibbonPersonID;
        $where .= ' AND gibbonPersonID=:gibbonPersonID';
    }

    if ($title != null) {
        $dataLog['title'] = $title;
        $where .= ' AND title=:title';
    }

    if ($startDate != null && $endDate == null) {
        $startDate = str_replace('/', '-', $startDate);
        $startDate = date('Y-m-d', strtotime($startDate));
        $dataLog['startDate'] = $startDate;
        $where .= ' AND timestamp>=:startDate';
    } elseif ($startDate == null && $endDate != null) {
        $endDate = str_replace('/', '-', $endDate);
        $endDate = date('Y-m-d 23:59:59', strtotime($endDate)) + date('H:i:s');
        $dataLog['endDate'] = $endDate;
        $where .= ' AND timestamp<=:endDate';
    } elseif ($startDate != null && $endDate != null) {
        $startDate = str_replace('/', '-', $startDate);
        $startDate = date('Y-m-d', strtotime($startDate));
        $dataLog['startDate'] = $startDate;
        $endDate = str_replace('/', '-', $endDate);
        $endDate = date('Y-m-d 23:59:59', strtotime($endDate));
        $dataLog['endDate'] = $endDate;
        $where .= ' AND timestamp>=:startDate AND timestamp<=:endDate';
    }

    if ($ip != null || $ip != '') {
        $dataLog['ip'] = $ip;
        $where .= ' AND ip=:ip';
    }

    try {
        $sqlLog = 'SELECT * FROM gibbonLog WHERE gibbonSchoolYearID=:gibbonSchoolYearID '.$where.' ORDER BY timestamp DESC';
        $resultLog = $connection2->prepare($sqlLog);
        $resultLog->execute($dataLog);
    } catch (PDOException $e) {
        return;
    }

    return $resultLog;
}

function getLogByID($connection2, $gibbonLogID)
{
    if ($gibbonLogID == null) {
        return;
    }
    try {
        $dataLog = array('gibbonLogID' => $gibbonLogID);
        $sqlLog = 'SELECT * FROM gibbonLog WHERE gibbonLogID=:gibbonLogID';
        $resultLog = $connection2->prepare($sqlLog);
        $resultLog->execute($dataLog);
        $row = $resultLog->fetch();
    } catch (PDOException $e) {
        return;
    }

    return $row;
}

function getModuleID($connection2, $address)
{
    $name = getModuleName($address);

    return getModuleIDFromName($connection2, $name);
}

function getModuleIDFromName($connection2, $name)
{
    try {
        $dataModuleID = array('name' => $name);
        $sqlModuleID = 'SELECT gibbonModuleID FROM gibbonModule WHERE name=:name';
        $resultModuleID = $connection2->prepare($sqlModuleID);
        $resultModuleID->execute($dataModuleID);
        $row = $resultModuleID->fetch();
    } catch (PDOException $e) {
    }

    return $row['gibbonModuleID'];
}

/**
 * This method has been replaced by the Mailer class, and remains here only to handle legacy calls.
 * The Deprecation error will be logged, and if asked for in php.ini stop execution.
 *
 * @deprecated 30th Nov 2018
 * @version 1st September 2016
 * @since   1st September 2016
 */
function getGibbonMailer($guid) {

    global $container;
    $displayErrors = ini_get('display_errors');

    ini_set('display_errors', 'Off');
    trigger_error('getGibbonMailer method is deprecated and replaced by Gibbon\Comms\Mailer class', E_USER_DEPRECATED);
    ini_set('display_errors', $displayErrors);

    $mail = $container->get(Mailer::class);

    return $mail;
}

/**
 * Checks if PHP is currently running from the command line. Additional checks added to help with cgi/fcgi systems, currently limited to that scope.
 *
 * @version  v14
 * @since    24th May 2017
 * @return   bool
 */
function isCommandLineInterface()
{
    if (php_sapi_name() === 'cli')
    {
        return true;
    }

    if (stripos(php_sapi_name(), 'cgi') !== false) {
        if (defined('STDIN'))
        {
            return true;
        }

        if (empty($_SERVER['REMOTE_ADDR']) and !isset($_SERVER['HTTP_USER_AGENT']) and count($_SERVER['argv']) > 0)
        {
            return true;
        }

        if (!array_key_exists('REQUEST_METHOD', $_SERVER))
        {
            return true;
        }
    }

    return false;
}

/**
 * Easy Return Display Processing. Print out message as appropriate.
 * See returnProcessMessage() for more details.
 *
 * @param string $guid
 *      The guid of your Gibbon Install.
 * @param string $return
 *      The return value of the process.
 * @param string $editLink
 *      (Optional) This should be a link. The link will appended to the end of a success0 return.
 * @param array $customReturns
 *      (Optional) This should be an array. The array allows you to set custom return checks and
 *      messages. Set the array key to the return name and the value to the return message.
 *
 * @return void
 */
function returnProcess($guid, $return, $editLink = null, $customReturns = null)
{
    $alert = returnProcessGetAlert($return, $editLink, $customReturns);

    echo !empty($alert)
        ? "<div class='{$alert['context']}'>{$alert['text']}</div>"
        : '';
}

/**
 * Render HTML for easy return display process.
 *
 * Default returns:
 *   success0: This is a default success message for adding a new record.
 *   error0:   This is a default error message for invalid permission for an action.
 *   error1:   This is a default error message for invalid inputs.
 *   error2:   This is a defualt error message for a database error.
 *   warning0: This is a default warning message for a extra data failing to save.
 *   warning1: This is a default warning message for a successful request, where certain data was not save properly.
 *
 * @param string $guid
 *      The guid of your Gibbon Install.
 * @param string $return
 *      The return value of the process.
 * @param string $editLink
 *      (Optional) This should be a link. The link will appended to the end of a success0 return.
 * @param array $customReturns
 *      (Optional) This should be an array. The array allows you to set custom return checks and
 *      messages. Set the array key to the return name and the value to the return message.
 * @return string
 *      The HTML ouput of the easy return display.
 */
function returnProcessGetAlert($return, $editLink = null, $customReturns = null) {
    if (isset($return)) {
        $class = 'error';
        $returnMessage = 'Unknown Return';
        $returns = array();
        $returns['success0'] = __('Your request was completed successfully.');
        $returns['error0'] = __('Your request failed because you do not have access to this action.');
        $returns['error1'] = __('Your request failed because your inputs were invalid.');
        $returns['error2'] = __('Your request failed due to a database error.');
        $returns['error3'] = __('Your request failed because your inputs were invalid.');
        $returns['error4'] = __('Your request failed because your passwords did not match.');
        $returns['error5'] = __('Your request failed because there are no records to show.');
        $returns['error6'] = __('Your request was completed successfully, but one or more images were the wrong size and so were not saved.');
        $returns['warning0'] = __('Your optional extra data failed to save.');
        $returns['warning1'] = __('Your request was successful, but some data was not properly saved.');
        $returns['warning2'] = __('Your request was successful, but some data was not properly deleted.');

        if (isset($customReturns)) {
            if (is_array($customReturns)) {
                $customReturnKeys = array_keys($customReturns);
                foreach ($customReturnKeys as $customReturnKey) {
                    $customReturn = __('Unknown Return');
                    if (isset($customReturns[$customReturnKey])) {
                        $customReturn = $customReturns[$customReturnKey];
                    }
                    $returns[$customReturnKey] = $customReturn;
                }
            }
        }
        $returnKeys = array_keys($returns);
        foreach ($returnKeys as $returnKey) {
            if ($return == $returnKey) {
                $returnMessage = $returns[$returnKey];
                if (stripos($return, 'error') !== false) {
                    $class = 'error';
                } elseif (stripos($return, 'warning') !== false) {
                    $class = 'warning';
                } elseif (stripos($return, 'success') !== false) {
                    $class = 'success';
                } elseif (stripos($return, 'message') !== false) {
                    $class = 'message';
                }
                break;
            }
        }
        if ($class == 'success' && $editLink != null) {
            $returnMessage .= ' '.sprintf(__('You can edit your newly created record %1$shere%2$s.'), "<a href='$editLink'>", '</a>');
        }

        return ['context' => $class, 'text' => $returnMessage];
    }
    return null;
}
