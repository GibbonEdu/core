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
use Gibbon\Services\Format;
use Gibbon\Data\PasswordPolicy;
use Gibbon\Domain\Students\MedicalGateway;
use Gibbon\Domain\System\AlertLevelGateway;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Forms\Input\Editor;
use Gibbon\Locale;

function getIPAddress()
{
    $return = false;

    if (getenv('HTTP_CLIENT_IP'))
        $return = getenv('HTTP_CLIENT_IP');
    else if (getenv('HTTP_X_FORWARDED_FOR'))
        $return = getenv('HTTP_X_FORWARDED_FOR');
    else if (getenv('HTTP_X_FORWARDED'))
        $return = getenv('HTTP_X_FORWARDED');
    else if (getenv('HTTP_FORWARDED_FOR'))
        $return = getenv('HTTP_FORWARDED_FOR');
    else if (getenv('HTTP_FORWARDED'))
        $return = getenv('HTTP_FORWARDED');
    else if (getenv('REMOTE_ADDR'))
        $return = getenv('REMOTE_ADDR');

    return $return;
}

/**
 * Convert an HTML email body into a plain text email body.
 *
 * Deprecated. Use \Gibbon\Comms\Mailer::renderBody() instead, which internally
 * handles the HTML and non-HTML rendered messages.
 *
 * @deprecated v25
 * @version v12
 * @since   v12
 *
 * @param string $body
 *
 * @return string
 */
function emailBodyConvert($body)
{
    $return = $body;

    $return = preg_replace('#<br\s*/?>#i', "\n", $return);
    $return = str_replace('</p>', "\n\n", $return);
    $return = str_replace('</div>', "\n\n", $return);
    $return = preg_replace("#\<a.+href\=[\"|\'](.+)[\"|\'].*\>.*\<\/a\>#U", '$1', $return);
    $return = strip_tags($return, '<a>');

    return $return;
}

/**
 * Custom translation function to allow custom string replacement
 *
 * @param string        $text    Text to Translate. See documentation for
 *                               Gibbon\Locale::translate for more info.
 * @param array         $params  Assoc array of key value pairs for named
 *                               string replacement. See documentation for
 *                               Gibbon\Locale::translate for more info.
 * @param array|string  $options Options for translations (e.g. domain).
 *                               Or string of domain (for backward
 *                               compatibility, deprecated).
 *
 * @return string The resulted translation string.
 */
function __($text, $params = [], $options = [])
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

    // Fallback to format string if global locale does not exists.
    return isset($gibbon->locale)
        ? $gibbon->locale->translate($text, $params, $options)
        : Locale::formatString($text, $params);
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
    global $gibbon, $session;

    if ($session->has('module')) {
        $options['domain'] = $session->get('module');
    }

    return $gibbon->locale->translate($text, $params, $options);
}

//$valueMode can be "value" or "id" according to what goes into option's value field
//$selectMode can be "value" or "id" according to what is used to preselect an option
//$honourDefault can TRUE or FALSE, and determines whether or not the default grade is selected

function renderGradeScaleSelect($connection2, $guid, $gibbonScaleID, $fieldName, $valueMode, $honourDefault = true, $width = 50, $selectedMode = 'value', $selectedValue = null)
{
    $return = false;

    $return .= "<select name='$fieldName' id='$fieldName' style='width: " . $width . "px'>";

    $dataSelect = array('gibbonScaleID' => $gibbonScaleID);
    $sqlSelect = 'SELECT * FROM gibbonScaleGrade WHERE gibbonScaleID=:gibbonScaleID ORDER BY sequenceNumber';
    $resultSelect = $connection2->prepare($sqlSelect);
    $resultSelect->execute($dataSelect);
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
            $return .= "<option $selected value='" . htmlPrep($rowSelect['value']) . "'>" . htmlPrep(__($rowSelect['value'])) . '</option>';
        } else {
            $return .= "<option $selected value='" . htmlPrep($rowSelect['gibbonScaleGradeID']) . "'>" . htmlPrep(__($rowSelect['value'])) . '</option>';
        }
    }
    $return .= '</select>';

    return $return;
}

/**
 * Archives one or more notifications, based on partial match of actionLink
 * and total match of gibbonPersonID.
 *
 * @deprecated v25
 *             Should use NotificationGateway::archiveNotificationForPersonAction()
 *
 * @param \PDO    $connection2     The PDO instance.
 * @param string  $guid            The guid of current installation.
 * @param int     $gibbonPersonID  The Gibbon person ID.
 * @param string  $actionLinkPart  The partial string in an action link.
 *
 * @return bool Whether the database update was successful.
 */
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
 * Calculate the number of days before next birthday.
 *
 * Deprecated because it was only used in \Gibbon\Services\Format.
 * Replaced by the private method \Gibbon\Services\Format::daysUntilNextBirthday().
 *
 * @deprecated v25
 * @version v12
 * @since   v12
 *
 * @param string $birthday  Accepts birthday in mysql date (YYYY-MM-DD).
 *
 * @return int  Number of days before the next birthday. If today is a birthday, returns 0.
 */
function daysUntilNextBirthday($birthday)
{
    $today = date('Y-m-d');
    $btsString = substr($today, 0, 4) . '-' . substr($birthday, 5);
    $bts = strtotime($btsString);
    $ts = time();

    if ($bts < $ts) {
        $bts = strtotime(date('y', strtotime('+1 year')) . '-' . substr($birthday, 5));
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

/**
 * This function written by David Walsh, shared under MIT License
 * (http://davidwalsh.name/checking-for-leap-year-using-php)
 *
 * @deprecated v25
 *
 * @param int  $year  The year.
 *
 * @return bool
 */
function is_leap_year($year)
{
    return (($year % 4) == 0) && ((($year % 100) != 0) || (($year % 400) == 0));
}

/**
 * Check if a password matches the password policy in the
 * settings.
 *
 * Deprecated. Use \Gibbon\Data\PasswordPolicy::validate() instead.
 *
 * @deprecated v25
 * @version v25
 * @since   v12
 *
 * @param \PDO   $connection2
 * @param string $passwordNew
 *
 * @return bool
 */
function doesPasswordMatchPolicy($connection2, $passwordNew)
{
    global $container;
    /** @var PasswordPolicy */
    $passwordPolicies = $container->get(PasswordPolicy::class);
    try {
        return $passwordPolicies->validate($passwordNew);
    } catch (\Exception $e) {
        return false;
    }
}

/**
 * Get an HTML list of all password policies.
 *
 * @deprecated v25
 * @version v25
 * @since   v12
 *
 * @param string $guid
 * @param \PDO $connection2
 *
 * @return string  An unorder HTML list.
 */
function getPasswordPolicy($guid, $connection2)
{
    global $container;
    /** @var PasswordPolicy */
    $passwordPolicies = $container->get(PasswordPolicy::class);
    return $passwordPolicies->describeHTML();
}

function getFastFinder($connection2, $guid)
{
    global $session;

    $form = Form::create('fastFinder', Url::fromHandlerRoute('indexFindRedirect.php'), 'get');
    $form->setClass('blank fullWidth');

    $form->addHiddenValue('address', $session->get('address'));

    $row = $form->addRow();
    $row->addFinder('fastFinderSearch')
        ->fromAjax(Url::fromHandlerRoute('index_fastFinder_ajax.php'))
        ->setClass('w-full text-white flex items-center')
        ->setAria('label', __('Search'))
        ->setParameter('hintText', __('Start typing a name...'))
        ->setParameter('noResultsText', __('No results'))
        ->setParameter('searchingText', __('Searching...'))
        ->setParameter('tokenLimit', 1)
        ->setParameter('arialabel', __('Fast Finder'))
        ->addValidation('Validate.Presence', 'failureMessage: " "')
        ->append('<input type="submit" style="height:34px;padding:0 1rem;" value="' . __('Go') . '">');

    $highestActionClass = getHighestGroupedAction($guid, '/modules/Planner/planner.php', $connection2);

    $templateData = [
        'roleCategory'        => $session->get('gibbonRoleIDCurrentCategory'),
        'studentIsAccessible' => isActionAccessible($guid, $connection2, '/modules/students/student_view.php'),
        'staffIsAccessible'   => isActionAccessible($guid, $connection2, '/modules/Staff/staff_view.php'),
        'classIsAccessible'   => isActionAccessible($guid, $connection2, '/modules/Planner/planner.php') && $highestActionClass != 'Lesson Planner_viewMyChildrensClasses',
        'form'                => $form->getOutput(),
    ];

    return $templateData;
}

/**
 * Get alert of the especified alert level.
 *
 * @deprecated v25
 *             Use AlertLevelGateway::getByID instead.
 *
 * @since    v12
 * @version  v23
 *
 * @param string  $guid
 * @param \PDO    $connection2
 * @param int     $gibbonAlertLevelID
 *
 * @return array|false
 */
function getAlert($guid, $connection2, $gibbonAlertLevelID)
{
    $output = false;


    $dataAlert = array('gibbonAlertLevelID' => $gibbonAlertLevelID);
    $sqlAlert = 'SELECT * FROM gibbonAlertLevel WHERE gibbonAlertLevelID=:gibbonAlertLevelID';
    $resultAlert = $connection2->prepare($sqlAlert);
    $resultAlert->execute($dataAlert);
    if ($resultAlert->rowCount() == 1) {
        $rowAlert = $resultAlert->fetch();
        $output = array();
        $output['gibbonAlertLevelID'] = $rowAlert['gibbonAlertLevelID'];
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
    $c = './aAbBcCdDeEfFgGhHiIjJkKlLmMnNoOpPqQrRsStTuUvVwWxXyYzZ0123456789';
    $s = '';
    $l = strlen($c);
    for ($x = 0; $x < 22; $x++) {
        $ind =  mt_rand(0, $l - 1);
        $s .= $c[$ind];
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
    global $session;

    $week = 0;
    try {
        $dataWeek = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'));
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

/**
 * Render the editor. Updated v18 to use a twig template.
 *
 * @deprecated  Since v25. Will be removed in the future.
 *              Please use \Gibbon\Forms\Input\Editor directly.
 * @version v25
 * @since   v12
 *
 * @param string   $guid              Obsoleted parameter.
 * @param boolean  $tinymceInit
 * @param string   $id
 * @param string   $value
 * @param integer  $rows
 * @param boolean  $showMedia
 * @param boolean  $required
 * @param boolean  $initiallyHidden
 * @param boolean  $allowUpload
 * @param string   $initialFilter
 * @param boolean  $resourceAlphaSort
 *
 * @return string
 */
function getEditor($guid, $tinymceInit = true, $id = '', $value = '', $rows = 10, $showMedia = false, $required = false, $initiallyHidden = false, $allowUpload = true, $initialFilter = '', $resourceAlphaSort = false): string
{
    $editor = (new Editor($id))
        ->tinymceInit($tinymceInit)
        ->setValue($value)
        ->setRows($rows)
        ->showMedia($showMedia)
        ->setRequired($required)
        ->initiallyHidden($initiallyHidden)
        ->allowUpload($allowUpload)
        ->initialFilter($initialFilter)
        ->resourceAlphaSort($resourceAlphaSort);
    return $editor->getOutput();
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
            $output .= $row['gibbonYearGroupID'] . ',';
            $output .= $row['name'] . ',';
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
                $output = '<i>' . __('All') . '</i>';
            } else {
                try {
                    $dataYears = array();
                    $sqlYearsOr = '';
                    for ($i = 0; $i < count($years); ++$i) {
                        if ($i == 0) {
                            $dataYears["year$i"] = $years[$i];
                            $sqlYearsOr = $sqlYearsOr . ' WHERE gibbonYearGroupID=:year' . $i;
                        } else {
                            $dataYears["year$i"] = $years[$i];
                            $sqlYearsOr = $sqlYearsOr . ' OR gibbonYearGroupID=:year' . $i;
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
            $output = '<i>' . __('None') . '</i>';
        }
    } catch (PDOException $e) {
    }

    return $output;
}

/**
 * Gets terms in the specified school year
 *
 * @deprecated v25
 *             Use SchoolYearTermGateway::selectTermsBySchoolYear() instead.
 *
 * @since   v12
 * @version v12
 *
 * @param \PDO     $connection2
 * @param int      $gibbonSchoolYearID
 * @param boolean  $short
 *
 * @return string[]
 */
function getTerms($connection2, $gibbonSchoolYearID, $short = false)
{
    $output = false;
    //Scan through year groups

    $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
    $sql = 'SELECT * FROM gibbonSchoolYearTerm WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY sequenceNumber';
    $result = $connection2->prepare($sql);
    $result->execute($data);

    while ($row = $result->fetch()) {
        $output .= $row['gibbonSchoolYearTermID'] . ',';
        if ($short == true) {
            $output .= $row['nameShort'] . ',';
        } else {
            $output .= $row['name'] . ',';
        }
    }
    if ($output != false) {
        $output = substr($output, 0, (strlen($output) - 1));
        $output = explode(',', $output);
    }

    return $output;
}

/**
 * Array sort for multidimensional arrays.
 *
 * Deprecated in favor of native usort.
 *
 * @since 2013
 * @version v12.0.00
 * @deprecated v26.0.00
 */
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
 * Returns preformatted HTML indicator of max file upload size
 *
 * @since 2013
 * @version v26
 *
 * @param bool $multiple  Whether to show text about multiple files.
 */
function getMaxUpload($multiple = false)
{
    // For backwards compatibilty
    global $guid;
    if ($multiple === $guid) {
        $multiple = func_get_args()[1] ?? false;
    }

    $output = '';
    $post = substr(ini_get('post_max_size'), 0, (strlen(ini_get('post_max_size')) - 1));
    $file = substr(ini_get('upload_max_filesize'), 0, (strlen(ini_get('upload_max_filesize')) - 1));

    $output .= "<div style='margin-top: 10px; font-style: italic; color: #c00'>";
    if ($multiple == true) {
        if ($post < $file) {
            $output .= sprintf(__('Maximum size for all files: %1$sMB'), $post) . '<br/>';
        } else {
            $output .= sprintf(__('Maximum size for all files: %1$sMB'), $file) . '<br/>';
        }
    } else {
        if ($post < $file) {
            $output .= sprintf(__('Maximum file size: %1$sMB'), $post) . '<br/>';
        } else {
            $output .= sprintf(__('Maximum file size: %1$sMB'), $file) . '<br/>';
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

/**
 * Get the risk level of the highest-risk condition for an individual.
 *
 * Deprecated. Use \Gibbon\Domain\Student\MedicalGateway::getHighestMedicalRisk() instead.
 *
 * @deprecated v25
 * @version v12
 *
 * @param string  $guid            Obsoleted parameter.
 * @param int     $gibbonPersonID  The person ID.
 * @param \PDO    $connection2
 *
 * @return array  An array of fields in the medical alert information of the person,
 *                or an empty array if none found.
 */
function getHighestMedicalRisk($guid, $gibbonPersonID, $connection2)
{
    $output = false;


    $dataAlert = array('gibbonPersonID' => $gibbonPersonID);
    $sqlAlert = 'SELECT * FROM gibbonPersonMedical JOIN gibbonPersonMedicalCondition ON (gibbonPersonMedical.gibbonPersonMedicalID=gibbonPersonMedicalCondition.gibbonPersonMedicalID) JOIN gibbonAlertLevel ON (gibbonPersonMedicalCondition.gibbonAlertLevelID=gibbonAlertLevel.gibbonAlertLevelID) WHERE gibbonPersonID=:gibbonPersonID ORDER BY gibbonAlertLevel.sequenceNumber DESC';
    $resultAlert = $connection2->prepare($sqlAlert);
    $resultAlert->execute($dataAlert);

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

//Looks at the grouped actions accessible to the user in the current module and returns the highest
function getHighestGroupedAction($guid, $address, $connection2)
{
    global $session;

    if (empty($session->get('gibbonRoleIDCurrent'))) return false;

    $output = false;
    $module = getModuleName($address);

    try {
        $data = [
            'actionName' => '%' . getActionName($address) . '%',
            'gibbonRoleID' => $session->get('gibbonRoleIDCurrent'),
            'moduleName' => $module,
        ];
        $sql = 'SELECT
        gibbonAction.name
        FROM
        gibbonAction
        INNER JOIN gibbonModule ON (gibbonModule.gibbonModuleID=gibbonAction.gibbonModuleID)
        INNER JOIN gibbonPermission ON (gibbonAction.gibbonActionID=gibbonPermission.gibbonActionID)
        INNER JOIN gibbonRole ON (gibbonPermission.gibbonRoleID=gibbonRole.gibbonRoleID)
        WHERE
        gibbonAction.URLList LIKE :actionName AND
        gibbonPermission.gibbonRoleID=:gibbonRoleID AND
        gibbonModule.name=:moduleName
        ORDER BY gibbonAction.precedence DESC, gibbonAction.gibbonActionID';

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

/**
 * Returns the category of the specified role.
 *
 * Deprecated. Use RoleGateway::getRoleCategory() instead.
 *
 * @deprecated v25
 * @version v12
 * @since   v12
 *
 * @param int   $gibbonRoleID
 * @param \PDO  $connection2
 *
 * @return string|false
 */
function getRoleCategory($gibbonRoleID, $connection2)
{
    $output = false;


    $data = array('gibbonRoleID' => $gibbonRoleID);
    $sql = 'SELECT * FROM gibbonRole WHERE gibbonRoleID=:gibbonRoleID';
    $result = $connection2->prepare($sql);
    $result->execute($data);

    if ($result->rowCount() == 1) {
        $row = $result->fetch();
        $output = $row['category'];
    }

    return $output;
}

//Checks to see if a specified date (YYYY-MM-DD) is a day where school is open in the current academic year. There is an option to search all years
function isSchoolOpen($guid, $date, $connection2, $allYears = '')
{
    global $session;

    //Set test variables
    $isInTerm = false;
    $isSchoolDay = false;
    $isSchoolOpen = false;

    //Turn $date into UNIX timestamp and extract day of week
    $timestamp = Format::timestamp($date);

    $dayOfWeek = date('D', $timestamp);

    //See if date falls into a school term
    $data = [];
    $sql = "SELECT gibbonSchoolYearTerm.firstDay, gibbonSchoolYearTerm.lastDay FROM gibbonSchoolYearTerm, gibbonSchoolYear WHERE gibbonSchoolYearTerm.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID";

    if ($allYears != true) {
        $data['gibbonSchoolYearID'] = $session->get('gibbonSchoolYearID');
        $sql .= ' AND gibbonSchoolYear.gibbonSchoolYearID=:gibbonSchoolYearID';
    }

    $result = $connection2->prepare($sql);
    $result->execute($data);

    while ($row = $result->fetch()) {
        if ($date >= $row['firstDay'] and $date <= $row['lastDay']) {
            $isInTerm = true;
        }
    }

    //See if date's day of week is a school day
    if ($isInTerm == true) {
        $data = array('nameShort' => $dayOfWeek);
        $sql = "SELECT * FROM gibbonDaysOfWeek WHERE nameShort=:nameShort AND schoolDay='Y'";
        $result = $connection2->prepare($sql);
        $result->execute($data);
        if ($result->rowCount() > 0) {
            $isSchoolDay = true;
        }
    }

    //See if there is a special day
    if ($isInTerm == true and $isSchoolDay == true) {
        $data = array('date' => $date);
        $sql = "SELECT * FROM gibbonSchoolYearSpecialDay WHERE type='School Closure' AND date=:date";
        $result = $connection2->prepare($sql);
        $result->execute($data);

        if ($result->rowCount() < 1) {
            $isSchoolOpen = true;
        }
    }

    return $isSchoolOpen;
}

function getAlertBar($guid, $connection2, $gibbonPersonID, $privacy = '', $divExtras = '', $div = true, $large = false, $target = "_self")
{
    global $session, $container;

    $output = '';
    $alerts = [];

    $target = ($target == "_blank") ? "_blank" : "_self";

    $highestAction = getHighestGroupedAction($guid, '/modules/Students/student_view_details.php', $connection2);
    if ($highestAction == 'View Student Profile_full' or $highestAction == 'View Student Profile_fullNoNotes' or $highestAction == 'View Student Profile_fullEditAllNotes') {
        // Individual Needs
        $dataAlert = array('gibbonPersonID' => $gibbonPersonID);
        $sqlAlert = "SELECT * FROM gibbonINPersonDescriptor JOIN gibbonAlertLevel ON (gibbonINPersonDescriptor.gibbonAlertLevelID=gibbonAlertLevel.gibbonAlertLevelID) WHERE gibbonPersonID=:gibbonPersonID ORDER BY sequenceNumber DESC";
        $resultAlert = $connection2->prepare($sqlAlert);
        $resultAlert->execute($dataAlert);

        if ($alert = $resultAlert->fetch()) {
            $title = $resultAlert->rowCount() == 1
                ? $resultAlert->rowCount() . ' ' . sprintf(__('Individual Needs alert is set, with an alert level of %1$s.'), $alert['name'])
                : $resultAlert->rowCount() . ' ' . sprintf(__('Individual Needs alerts are set, up to a maximum alert level of %1$s.'), $alert['name']);

            $alerts[] = [
                'highestLevel'    => __($alert['name']),
                'highestColour'   => $alert['color'],
                'highestColourBG' => $alert['colorBG'],
                'tag'             => __('IN'),
                'title'           => $title,
                'link'            => Url::fromModuleRoute('Students', 'student_view_details')
                    ->withQueryParams(['gibbonPersonID' => $gibbonPersonID, 'subpage' => 'Individual Needs']),
            ];
        }

        // Academic
        $gibbonAlertLevelID = '';
        $alertThresholdText = '';

        $dataAlert = array('gibbonPersonIDStudent' => $gibbonPersonID, 'gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'today' => date('Y-m-d'), 'date' => date('Y-m-d', (time() - (24 * 60 * 60 * 60))));
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

        $settingGateway = $container->get(SettingGateway::class);
        $academicAlertLowThreshold = $settingGateway->getSettingByScope('Students', 'academicAlertLowThreshold');
        $academicAlertMediumThreshold = $settingGateway->getSettingByScope('Students', 'academicAlertMediumThreshold');
        $academicAlertHighThreshold = $settingGateway->getSettingByScope('Students', 'academicAlertHighThreshold');

        if ($resultAlert->rowCount() >= $academicAlertHighThreshold) {
            $gibbonAlertLevelID = 001;
            $alertThresholdText = sprintf(__('This alert level occurs when there are more than %1$s events recorded for a student.'), $academicAlertHighThreshold);
        } elseif ($resultAlert->rowCount() >= $academicAlertMediumThreshold) {
            $gibbonAlertLevelID = 002;
            $alertThresholdText = sprintf(__('This alert level occurs when there are between %1$s and %2$s events recorded for a student.'), $academicAlertMediumThreshold, ($academicAlertHighThreshold - 1));
        } elseif ($resultAlert->rowCount() >= $academicAlertLowThreshold) {
            $gibbonAlertLevelID = 003;
            $alertThresholdText = sprintf(__('This alert level occurs when there are between %1$s and %2$s events recorded for a student.'), $academicAlertLowThreshold, ($academicAlertMediumThreshold - 1));
        }
        if ($gibbonAlertLevelID != '') {
            /**
             * @var AlertLevelGateway
             */
            $alertLevelGateway = $container->get(AlertLevelGateway::class);
            if ($alert = $alertLevelGateway->getByID($gibbonAlertLevelID)) {
                $alerts[] = [
                    'highestLevel'    => __($alert['name']),
                    'highestColour'   => $alert['color'],
                    'highestColourBG' => $alert['colorBG'],
                    'tag'             => __('A'),
                    'title'           => sprintf(__('Student has a %1$s alert for academic concern over the past 60 days.'), __($alert['name'])) . ' ' . $alertThresholdText,
                    'link'            => Url::fromModuleRoute('Students', 'student_view_details')
                        ->withQueryParams([
                            'gibbonPersonID' => $gibbonPersonID,
                            'subpage' => 'Markbook',
                            'filter' => $session->get('gibbonSchoolYearID'),
                        ]),
                ];
            }
        }

        // Behaviour
        $gibbonAlertLevelID = '';
        $alertThresholdText = '';

        $dataAlert = array('gibbonPersonID' => $gibbonPersonID, 'date' => date('Y-m-d', (time() - (24 * 60 * 60 * 60))));
        $sqlAlert = "SELECT * FROM gibbonBehaviour WHERE gibbonPersonID=:gibbonPersonID AND type='Negative' AND date>:date";
        $resultAlert = $connection2->prepare($sqlAlert);
        $resultAlert->execute($dataAlert);

        $behaviourAlertLowThreshold = $settingGateway->getSettingByScope('Students', 'behaviourAlertLowThreshold');
        $behaviourAlertMediumThreshold = $settingGateway->getSettingByScope('Students', 'behaviourAlertMediumThreshold');
        $behaviourAlertHighThreshold = $settingGateway->getSettingByScope('Students', 'behaviourAlertHighThreshold');

        if ($resultAlert->rowCount() >= $behaviourAlertHighThreshold) {
            $gibbonAlertLevelID = 001;
            $alertThresholdText = sprintf(__('This alert level occurs when there are more than %1$s events recorded for a student.'), $behaviourAlertHighThreshold);
        } elseif ($resultAlert->rowCount() >= $behaviourAlertMediumThreshold) {
            $gibbonAlertLevelID = 002;
            $alertThresholdText = sprintf(__('This alert level occurs when there are between %1$s and %2$s events recorded for a student.'), $behaviourAlertMediumThreshold, ($behaviourAlertHighThreshold - 1));
        } elseif ($resultAlert->rowCount() >= $behaviourAlertLowThreshold) {
            $gibbonAlertLevelID = 003;
            $alertThresholdText = sprintf(__('This alert level occurs when there are between %1$s and %2$s events recorded for a student.'), $behaviourAlertLowThreshold, ($behaviourAlertMediumThreshold - 1));
        }

        if ($gibbonAlertLevelID != '') {
            /**
             * @var AlertLevelGateway
             */
            $alertLevelGateway = $container->get(AlertLevelGateway::class);
            if ($alert = $alertLevelGateway->getByID($gibbonAlertLevelID)) {
                $alerts[] = [
                    'highestLevel'    => __($alert['name']),
                    'highestColour'   => $alert['color'],
                    'highestColourBG' => $alert['colorBG'],
                    'tag'             => __('B'),
                    'title'           => sprintf(__('Student has a %1$s alert for behaviour over the past 60 days.'), __($alert['name'])) . ' ' . $alertThresholdText,
                    'link'            => Url::fromModuleRoute('Students', 'student_view_details')
                        ->withQueryParams(['gibbonPersonID' => $gibbonPersonID, 'subpage' => 'Behaviour']),
                ];
            }
        }

        // Medical
        /** @var MedicalGateway */
        $medicalGateway = $container->get(MedicalGateway::class);
        if ($alert = $medicalGateway->getHighestMedicalRisk($gibbonPersonID)) {
            $alerts[] = [
                'highestLevel'    => __($alert['name']),
                'highestColour'   => $alert['color'],
                'highestColourBG' => $alert['colorBG'],
                'tag'             => __('M'),
                'title'           => sprintf(__('Medical alerts are set, up to a maximum of %1$s'), $alert['name']),
                'link'            => Url::fromModuleRoute('Students', 'student_view_details')
                    ->withQueryParams(['gibbonPersonID' => $gibbonPersonID, 'subpage' => 'Medical']),
            ];
        }

        // Privacy
        $privacySetting = $settingGateway->getSettingByScope('User Admin', 'privacy');
        if ($privacySetting == 'Y' and $privacy != '') {
            /**
             * @var AlertLevelGateway
             */
            $alertLevelGateway = $container->get(AlertLevelGateway::class);
            if ($alert = $alertLevelGateway->getByID(AlertLevelGateway::LEVEL_HIGH)) {
                $alerts[] = [
                    'highestLevel'    => __($alert['name']),
                    'highestColour'   => $alert['color'],
                    'highestColourBG' => $alert['colorBG'],
                    'tag'             => __('P'),
                    'title'           => sprintf(__('Privacy is required: %1$s'), $privacy),
                    'link'            => Url::fromModuleRoute('Students', 'student_view_details')
                        ->withQueryParam('gibbonPersonID', $gibbonPersonID),
                ];
            }
        }

        // Output alerts
        $classDefault = 'block align-middle text-center font-bold border-0 border-t-2 ';
        $classDefault .= $large
            ? 'text-4xl w-10 pt-1 mr-2 leading-none'
            : 'text-xs w-4 pt-px mr-1 leading-none';

        foreach ($alerts as $alert) {
            $style = "color: {$alert['highestColour']}; border-color: {$alert['highestColour']}; background-color: {$alert['highestColourBG']};";
            $class = $classDefault . ' ' . ($alert['class'] ?? 'float-left');
            $output .= Format::link($alert['link'], $alert['tag'], [
                'title' => $alert['title'],
                'class' => $class,
                'style' => $style,
                'target' => $target,
            ]);
        }

        if ($div == true) {
            $output = "<div {$divExtras} class='w-20 lg:w-24 h-6 text-left py-1 px-0 mx-auto'>{$output}</div>";
        }
    }

    return $output;
}

//Gets system settings from database and writes them to individual session variables.
function getSystemSettings($guid, $connection2)
{
    global $session;

    //System settings from gibbonSetting
    try {
        $data = array();
        $sql = "SELECT * FROM gibbonSetting WHERE scope='System'";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        $session->set('systemSettingsSet', false);
    }

    while ($row = $result->fetch()) {
        $name = $row['name'];
        $session->set($name, $row['value']);
    }

    //Get names and emails for administrator, dba, admissions
    //System Administrator
    $data = array('gibbonPersonID' => $session->get('organisationAdministrator'));
    $sql = 'SELECT surname, preferredName, email FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonID';
    $result = $connection2->prepare($sql);
    $result->execute($data);
    if ($result->rowCount() == 1) {
        $row = $result->fetch();
        $session->set('organisationAdministratorName', Format::name('', $row['preferredName'], $row['surname'], 'Staff', false, true));
        $session->set('organisationAdministratorEmail', $row['email']);
    }
    //DBA
    $data = array('gibbonPersonID' => $session->get('organisationDBA'));
    $sql = 'SELECT surname, preferredName, email FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonID';
    $result = $connection2->prepare($sql);
    $result->execute($data);
    if ($result->rowCount() == 1) {
        $row = $result->fetch();
        $session->set('organisationDBAName', Format::name('', $row['preferredName'], $row['surname'], 'Staff', false, true));
        $session->set('organisationDBAEmail', $row['email']);
    }
    //Admissions
    $data = array('gibbonPersonID' => $session->get('organisationAdmissions'));
    $sql = 'SELECT surname, preferredName, email FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonID';
    $result = $connection2->prepare($sql);
    $result->execute($data);
    if ($result->rowCount() == 1) {
        $row = $result->fetch();
        $session->set('organisationAdmissionsName', Format::name('', $row['preferredName'], $row['surname'], 'Staff', false, true));
        $session->set('organisationAdmissionsEmail', $row['email']);
    }
    //HR Administrator
    $data = array('gibbonPersonID' => $session->get('organisationHR'));
    $sql = 'SELECT surname, preferredName, email FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonID';
    $result = $connection2->prepare($sql);
    $result->execute($data);
    if ($result->rowCount() == 1) {
        $row = $result->fetch();
        $session->set('organisationHRName', Format::name('', $row['preferredName'], $row['surname'], 'Staff', false, true));
        $session->set('organisationHREmail', $row['email']);
    }

    //Language settings from gibboni18n
    try {
        $data = array();
        $sql = "SELECT * FROM gibboni18n WHERE systemDefault='Y'";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        $session->set('systemSettingsSet', false);
    }
    if ($result->rowCount() == 1) {
        $row = $result->fetch();
        setLanguageSession($guid, $row);
    }

    $session->set('systemSettingsSet', true);
}

//Set language session variables
function setLanguageSession($guid, $row, $defaultLanguage = true)
{
    global $session;

    $i18n = [
        'gibboni18nID' => $row['gibboni18nID'],
        'code' => $row['code'],
        'name' => $row['name'],
        'dateFormat' => $row['dateFormat'],
        'dateFormatRegEx' => $row['dateFormatRegEx'],
        'dateFormatPHP' => $row['dateFormatPHP'],
        'rtl' => $row['rtl'],
    ];

    if ($defaultLanguage) {
        $i18n['default']['code'] = $row['code'];
        $i18n['default']['name'] = $row['name'];
    }

    $session->set('i18n', $i18n);
}

function isActionAccessible($guid, $connection2, $address, $sub = '')
{
    global $session;

    $output = false;
    //Check user is logged in
    if ($session->has('username')) {
        //Check user has a current role set
        if ($session->get('gibbonRoleIDCurrent') != '') {
            //Check module ready
            $module = getModuleName($address);
            if (!empty($module)) {
                //Check current role has access rights to the current action.
                try {
                    $data = array('actionName' => '%' . getActionName($address) . '%', 'gibbonRoleID' => $session->get('gibbonRoleIDCurrent'), 'moduleName' => $module);

                    $sql = "SELECT gibbonAction.name FROM gibbonAction
                    JOIN gibbonModule ON (gibbonModule.gibbonModuleID=gibbonAction.gibbonModuleID)
                    JOIN gibbonPermission ON (gibbonAction.gibbonActionID=gibbonPermission.gibbonActionID)
                    JOIN gibbonRole ON (gibbonPermission.gibbonRoleID=gibbonRole.gibbonRoleID)
                    WHERE gibbonAction.URLList LIKE :actionName
                        AND gibbonPermission.gibbonRoleID=:gibbonRoleID
                        AND gibbonModule.name=:moduleName ";

                    if ($sub != '') {
                        $data['sub'] = $sub;
                        $sql .= ' AND gibbonAction.name=:sub';
                    }

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
 * @deprecated in v23
 */
function isModuleAccessible($guid, $connection2, $address = '')
{
    global $session;

    if ($address == '') {
        $address = $session->get('address');
    }
    $output = false;
    //Check user is logged in && Check user has a current role set
    if ($session->has('username') && $session->has('gibbonRoleIDCurrent')) {

        //Check module ready
        $moduleID = checkModuleReady($address, $connection2);
        if ($moduleID != false) {
            //Check current role has access rights to an action in the current module.
            try {
                $data = array('gibbonRoleID' => $session->get('gibbonRoleIDCurrent'), 'moduleID' => $moduleID);
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


    $data = array('name' => $module);
    $sql = "SELECT * FROM gibbonModule WHERE name=:name AND active='Y'";
    $result = $connection2->prepare($sql);
    $result->execute($data);
    if ($result->rowCount() == 1) {
        $row = $result->fetch();
        $output = __($row['category']);
    }

    return $output;
}

/**
 * Get the current year and set it as a global variable.
 * (i.e. global $session instance)
 *
 * @deprecated v25
 *             This happens in SessionFactory::setCurrentSchoolYear instead,
 *             which is called by Core::initializeCore, so shouldn't need to
 *             be called manually.
 *
 * @version v23
 * @since   v12
 *
 * @param  string $guid
 * @param  \PDO $connection2
 *
 * @return void
 */
function setCurrentSchoolYear($guid,  $connection2)
{
    global $session;

    //Run query
    $data = array();
    $sql = "SELECT * FROM gibbonSchoolYear WHERE status='Current'";
    $result = $connection2->prepare($sql);
    $result->execute($data);

    //Check number of rows returned.
    //If it is not 1, show error
    if (!($result->rowCount() == 1)) {
        die(__('Configuration Error: there is a problem accessing the current Academic Year from the database.'));
    }
    //Else get schoolYearID
    else {
        $row = $result->fetch();
        $session->set('gibbonSchoolYearID', $row['gibbonSchoolYearID']);
        $session->set('gibbonSchoolYearName', $row['name']);
        $session->set('gibbonSchoolYearSequenceNumber', $row['sequenceNumber']);
        $session->set('gibbonSchoolYearFirstDay', $row['firstDay']);
        $session->set('gibbonSchoolYearLastDay', $row['lastDay']);
    }
}

/**
 * Convert linebreaks into <br/> tags.
 * Deprecated. Use nl2br() instead.
 *
 * @deprecated v25
 * @version v12
 *
 * @param string $string
 *
 * @return string
 */
function nl2brr($string)
{
    return preg_replace("/\r\n|\n|\r/", '<br/>', $string);
}

/**
 * Take a school year, and return the previous one, or false if none.
 *
 * Please use SchoolYearGateway::getPreviousSchoolYearByID instead.
 *
 * @deprecated v25
 * @version v12
 * @since   v12
 *
 * @param int  $gibbonSchoolYearID
 * @param \PDO $connection2
 *
 * @return int|false  The ID of the previous school year, or false if none.
 */
function getPreviousSchoolYearID($gibbonSchoolYearID, $connection2)
{
    $output = false;


    $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
    $sql = 'SELECT * FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearID';
    $result = $connection2->prepare($sql);
    $result->execute($data);
    if ($result->rowcount() == 1) {
        $row = $result->fetch();

        $dataPrevious = array('sequenceNumber' => $row['sequenceNumber']);
        $sqlPrevious = 'SELECT * FROM gibbonSchoolYear WHERE sequenceNumber<:sequenceNumber ORDER BY sequenceNumber DESC';
        $resultPrevious = $connection2->prepare($sqlPrevious);
        $resultPrevious->execute($dataPrevious);
        if ($resultPrevious->rowCount() >= 1) {
            $rowPrevious = $resultPrevious->fetch();
            $output = $rowPrevious['gibbonSchoolYearID'];
        }
    }

    return $output;
}

/**
 * Take a school year, and return the previous one, or false if none.
 *
 * Please use SchoolYearGateway::getNextSchoolYearByID instead.
 *
 * @deprecated v25
 * @version v12
 * @since   v12
 *
 * @param int  $gibbonSchoolYearID
 * @param \PDO $connection2
 *
 * @return int|false  The ID of the next school year, or false if none.
 */
function getNextSchoolYearID($gibbonSchoolYearID, $connection2)
{
    $output = false;


    $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
    $sql = 'SELECT * FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearID';
    $result = $connection2->prepare($sql);
    $result->execute($data);
    if ($result->rowcount() == 1) {
        $row = $result->fetch();

        $dataPrevious = array('sequenceNumber' => $row['sequenceNumber']);
        $sqlPrevious = 'SELECT * FROM gibbonSchoolYear WHERE sequenceNumber>:sequenceNumber ORDER BY sequenceNumber ASC';
        $resultPrevious = $connection2->prepare($sqlPrevious);
        $resultPrevious->execute($dataPrevious);
        if ($resultPrevious->rowCount() >= 1) {
            $rowPrevious = $resultPrevious->fetch();
            $output = $rowPrevious['gibbonSchoolYearID'];
        }
    }

    return $output;
}

/**
 * Take a year group, and return the next one, or false if none.
 * Use YearGroupGateway::getNextYearGroupID instead.
 *
 * @deprecated v25
 *
 * @version v12
 * @since   v12
 *
 * @param int  $gibbonYearGroupID
 * @param \PDO $connection2
 *
 * @return int|false
 */
function getNextYearGroupID($gibbonYearGroupID, $connection2)
{
    $output = false;

    $data = array('gibbonYearGroupID' => $gibbonYearGroupID);
    $sql = 'SELECT * FROM gibbonYearGroup WHERE gibbonYearGroupID=:gibbonYearGroupID';
    $result = $connection2->prepare($sql);
    $result->execute($data);
    if ($result->rowCount() == 1) {
        $row = $result->fetch();

        $dataPrevious = array('sequenceNumber' => $row['sequenceNumber']);
        $sqlPrevious = 'SELECT * FROM gibbonYearGroup WHERE sequenceNumber>:sequenceNumber ORDER BY sequenceNumber ASC';
        $resultPrevious = $connection2->prepare($sqlPrevious);
        $resultPrevious->execute($dataPrevious);
        if ($resultPrevious->rowCount() >= 1) {
            $rowPrevious = $resultPrevious->fetch();
            $output = $rowPrevious['gibbonYearGroupID'];
        }
    }

    return $output;
}

/**
 * Take a form group, and return the next one, or false if none.
 * Use FormGroupGateway::getNextFormGroupID instead.
 *
 * @deprecated v25
 * @version v17
 * @since   v17
 *
 * @param int $gibbonFormGroupID
 * @param \PDO $connection2
 *
 * @return int|false
 */
function getNextFormGroupID($gibbonFormGroupID, $connection2)
{
    $output = false;

    $data = array('gibbonFormGroupID' => $gibbonFormGroupID);
    $sql = 'SELECT * FROM gibbonFormGroup WHERE gibbonFormGroupID=:gibbonFormGroupID';
    $result = $connection2->prepare($sql);
    $result->execute($data);
    if ($result->rowCount() == 1) {
        $row = $result->fetch();
        if (!is_null($row['gibbonFormGroupIDNext'])) {
            $output = $row['gibbonFormGroupIDNext'];
        }
    }

    return $output;
}

/**
 * Return the last school year in the school, or false if none.
 * Use YearGroupGateway::getLastYearGroupID instead.
 *
 * @deprecated v25
 *
 * @version v12
 * @since   v12
 *
 * @param \PDO $connection2
 *
 * @return int|false
 */
function getLastYearGroupID($connection2)
{
    $output = false;

    $data = array();
    $sql = 'SELECT * FROM gibbonYearGroup ORDER BY sequenceNumber DESC';
    $result = $connection2->prepare($sql);
    $result->execute($data);
    if ($result->rowCount() > 1) {
        $row = $result->fetch();
        $output = $row['gibbonYearGroupID'];
    }

    return $output;
}

/**
 * Generate a random password of the specified length.
 * Deprecated. Use PasswordPolicy::generate() instead.
 *
 * @deprecated v25
 * @version v12
 *
 * @param int $length
 *
 * @return string
 */
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
    for ($i = 0; $i < $length; ++$i) {
        $password = $password . substr($charList, rand(1, strlen($charList)), 1);
    }

    return $password;
}

function getModuleID($connection2, $address)
{
    $name = getModuleName($address);

    return getModuleIDFromName($connection2, $name);
}

function getModuleIDFromName($connection2, $name)
{

    $dataModuleID = array('name' => $name);
    $sqlModuleID = 'SELECT gibbonModuleID FROM gibbonModule WHERE name=:name';
    $resultModuleID = $connection2->prepare($sqlModuleID);
    $resultModuleID->execute($dataModuleID);
    $row = $resultModuleID->fetch();

    return $row['gibbonModuleID'];
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
    if (php_sapi_name() === 'cli') {
        return true;
    }

    if (stripos(php_sapi_name(), 'cgi') !== false) {
        if (defined('STDIN')) {
            return true;
        }

        if (empty($_SERVER['REMOTE_ADDR']) and !isset($_SERVER['HTTP_USER_AGENT']) and count($_SERVER['argv'] ?? []) > 0) {
            return true;
        }

        if (!array_key_exists('REQUEST_METHOD', $_SERVER)) {
            return true;
        }
    }

    return false;
}

/**
 * @deprecated in v22. Use Page's ReturnMessage.
 */
function returnProcess($guid, $return, $editLink = null, $customReturns = null)
{
    global $page;
    $page->return->setEditLink($editLink ?? '');
    $page->return->addReturns($customReturns ?? []);
}
