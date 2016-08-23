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
require_once dirname(__FILE__).'/gibbon.php';

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


//Get and store custom string replacements in session
function setStringReplacementList($connection2, $guid)
{
    //$caller = debug_backtrace();
    //error_log("DEPRECATED: ".$caller[0]['line'].":".$caller[0]['file']." called " . __METHOD__ . " in " . __FILE__ );
    $trans = new Gibbon\trans();
    $trans->setStringReplacementList();
}

//Custom translation function to allow custom string replacement
function __($guid, $text)
{

    //$caller = debug_backtrace();
    //error_log("DEPRECATED: ".$caller[0]['line'].":".$caller[0]['file']." called " . __METHOD__ . " in " . __FILE__ );
    $trans = new Gibbon\trans();
    $x = true;
    if (empty($guid)) {
        $x = false;
    }

    return $trans->__($text, $x);
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
            $return .= "<option $selected value='".htmlPrep($rowSelect['value'])."'>".htmlPrep(__($guid, $rowSelect['value'])).'</option>';
        } else {
            $return .= "<option $selected value='".htmlPrep($rowSelect['gibbonScaleGradeID'])."'>".htmlPrep(__($guid, $rowSelect['value'])).'</option>';
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

function getMinorLinks($connection2, $guid, $cacheLoad)
{
    $return = false;

    if (isset($_SESSION[$guid]['username']) == false) {
        if ($_SESSION[$guid]['webLink'] != '') {
            $return .= __($guid, 'Return to')." <a style='margin-right: 12px' target='_blank' href='".$_SESSION[$guid]['webLink']."'>".$_SESSION[$guid]['organisationNameShort'].' '.__($guid, 'Website').'</a>';
        }
    } else {
        $name = $_SESSION[$guid]['preferredName'].' '.$_SESSION[$guid]['surname'];
        if (isset($_SESSION[$guid]['gibbonRoleIDCurrentCategory'])) {
            if ($_SESSION[$guid]['gibbonRoleIDCurrentCategory'] == 'Student') {
                $highestAction = getHighestGroupedAction($guid, '/modules/Students/student_view_details.php', $connection2);
                if ($highestAction == 'View Student Profile_brief') {
                    $name = "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Students/student_view_details.php&gibbonPersonID='.$_SESSION[$guid]['gibbonPersonID']."'>".$name.'</a>';
                }
            }
        }
        $return .= $name.' . ';
        $return .= "<a href='./logout.php'>".__($guid, 'Logout')."</a> . <a href='./index.php?q=preferences.php'>".__($guid, 'Preferences').'</a>';
        if ($_SESSION[$guid]['emailLink'] != '') {
            $return .= " . <a target='_blank' href='".$_SESSION[$guid]['emailLink']."'>".__($guid, 'Email').'</a>';
        }
        if ($_SESSION[$guid]['webLink'] != '') {
            $return .= " . <a target='_blank' href='".$_SESSION[$guid]['webLink']."'>".$_SESSION[$guid]['organisationNameShort'].' '.__($guid, 'Website').'</a>';
        }
        if ($_SESSION[$guid]['website'] != '') {
            $return .= " . <a target='_blank' href='".$_SESSION[$guid]['website']."'>".__($guid, 'My Website').'</a>';
        }

        //GET AND SHOW LIKES
        //Get likes
        $getLikes = false;
        if ($cacheLoad) {
            $getLikes = true;
        } elseif (isset($_GET['q'])) {
            if ($_GET['q'] == 'likes.php') {
                $getLikes = true;
            }
        }
        if ($getLikes) {
            $_SESSION[$guid]['likesCount'] = countLikesByRecipient($connection2, $_SESSION[$guid]['gibbonPersonID'], 'count', $_SESSION[$guid]['gibbonSchoolYearID']);
        }
        //Show likes
        if (isset($_SESSION[$guid]['likesCount'])) {
            if ($_SESSION[$guid]['likesCount'] > 0) {
                $return .= " . <a title='".__($guid, 'Likes')."' href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=likes.php'>".$_SESSION[$guid]['likesCount']." x <img class='minorLinkIcon' style='margin-left: 2px; vertical-align: -75%' src='".$_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName']."/img/like_large_on.png'></a>";
            } else {
                $return .= ' . '.$_SESSION[$guid]['likesCount']." x <img class='minorLinkIcon' title='".__($guid, 'Likes')."' style='margin-left: 2px; opacity: 0.8; vertical-align: -75%' src='".$_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName']."/img/like_large_off.png'>";
            }
        }

        //GET & SHOW NOTIFICATIONS
        try {
            $dataNotifications = array('gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonPersonID2' => $_SESSION[$guid]['gibbonPersonID']);
            $sqlNotifications = "(SELECT gibbonNotification.*, gibbonModule.name AS source FROM gibbonNotification JOIN gibbonModule ON (gibbonNotification.gibbonModuleID=gibbonModule.gibbonModuleID) WHERE gibbonPersonID=:gibbonPersonID AND status='New')
			UNION
			(SELECT gibbonNotification.*, 'System' AS source FROM gibbonNotification WHERE gibbonModuleID IS NULL AND gibbonPersonID=:gibbonPersonID2 AND status='New')
			ORDER BY timestamp DESC, source, text";
            $resultNotifications = $connection2->prepare($sqlNotifications);
            $resultNotifications->execute($dataNotifications);
        } catch (PDOException $e) {
            $return .= "<div class='error'>".$e->getMessage().'</div>';
        }

        //Refresh notifications every 10 seconds for staff, 120 seconds for everyone else
        $interval = 120000;
        if ($_SESSION[$guid]['gibbonRoleIDCurrentCategory'] == 'Staff') {
            $interval = 10000;
        }
        $return .= '<script type="text/javascript">
			$(document).ready(function(){
				setInterval(function() {
					$("#notifications").load("index_notification_ajax.php");
				}, '.$interval.');
			});
		</script>';

        $return .= "<div id='notifications' style='display: inline'>";
            //CHECK FOR SYSTEM ALARM
            if (isset($_SESSION[$guid]['gibbonRoleIDCurrentCategory'])) {
                if ($_SESSION[$guid]['gibbonRoleIDCurrentCategory'] == 'Staff') {
                    $alarm = getSettingByScope($connection2, 'System', 'alarm');
                    if ($alarm == 'General' or $alarm == 'Lockdown' or $alarm == 'Custom') {
                        $type = 'general';
                        if ($alarm == 'Lockdown') {
                            $type = 'lockdown';
                        } elseif ($alarm == 'Custom') {
                            $type = 'custom';
                        }
                        $return .= "<script>
							if ($('div#TB_window').is(':visible')===false) {
								var url = '".$_SESSION[$guid]['absoluteURL'].'/index_notification_ajax_alarm.php?type='.$type."&KeepThis=true&TB_iframe=true&width=1000&height=500';
								$(document).ready(function() {
									tb_show('', url);
									$('div#TB_window').addClass('alarm') ;
								}) ;
							}
						</script>";
                    }
                }
            }

        if ($resultNotifications->rowCount() > 0) {
            $return .= " . <a title='".__($guid, 'Notifications')."' href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=notifications.php'>".$resultNotifications->rowCount().' x '."<img class='minorLinkIcon' style='margin-left: 2px; vertical-align: -75%' src='".$_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName']."/img/notifications_on.png'></a>";
        } else {
            $return .= ' . 0 x '."<img title='".__($guid, 'Notifications')."' class='minorLinkIcon' style='margin-left: 2px; opacity: 0.8; vertical-align: -75%' src='".$_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName']."/img/notifications_off.png'>";
        }
        $return .= '</div>';

        //MESSAGE WALL!
        if (isActionAccessible($guid, $connection2, '/modules/Messenger/messageWall_view.php')) {
            include './modules/Messenger/moduleFunctions.php';

            $addReturn = null;
            if (isset($_GET['addReturn'])) {
                $addReturn = $_GET['addReturn'];
            }
            $updateReturn = null;
            if (isset($_GET['updateReturn'])) {
                $updateReturn = $_GET['updateReturn'];
            }
            $deleteReturn = null;
            if (isset($_GET['deleteReturn'])) {
                $deleteReturn = $_GET['deleteReturn'];
            }
            if ($cacheLoad or (@$_GET['q'] == '/modules/Messenger/messenger_post.php' and $addReturn == 'success0') or (@$_GET['q'] == '/modules/Messenger/messenger_postQuickWall.php' and $addReturn == 'success0') or (@$_GET['q'] == '/modules/Messenger/messenger_manage_edit.php' and $updateReturn == 'success0') or (@$_GET['q'] == '/modules/Messenger/messenger_manage.php' and $deleteReturn == 'success0')) {
                $messages = getMessages($guid, $connection2, 'result');
                $messages = unserialize($messages);
                try {
                    $resultPosts = $connection2->prepare($messages[1]);
                    $resultPosts->execute($messages[0]);
                } catch (PDOException $e) {
                }

                $_SESSION[$guid]['messageWallCount'] = 0;
                if ($resultPosts->rowCount() > 0) {
                    $count = 0;
                    $output = array();
                    $last = '';
                    while ($rowPosts = $resultPosts->fetch()) {
                        if ($last == $rowPosts['gibbonMessengerID']) {
                            $output[($count - 1)]['source'] = $output[($count - 1)]['source'].'<br/>'.$rowPosts['source'];
                        } else {
                            $output[$_SESSION[$guid]['messageWallCount']]['photo'] = $rowPosts['image_240'];
                            $output[$_SESSION[$guid]['messageWallCount']]['subject'] = $rowPosts['subject'];
                            $output[$_SESSION[$guid]['messageWallCount']]['details'] = $rowPosts['body'];
                            $output[$_SESSION[$guid]['messageWallCount']]['author'] = formatName($rowPosts['title'], $rowPosts['preferredName'], $rowPosts['surname'], $rowPosts['category']);
                            $output[$_SESSION[$guid]['messageWallCount']]['source'] = $rowPosts['source'];
                            $output[$_SESSION[$guid]['messageWallCount']]['gibbonMessengerID'] = $rowPosts['gibbonMessengerID'];

                            ++$_SESSION[$guid]['messageWallCount'];
                            $last = $rowPosts['gibbonMessengerID'];
                            ++$count;
                        }
                    }
                    $_SESSION[$guid]['messageWallOutput'] = $output;
                }
            }

            //Check for house logo (needed to get bubble, below, in right spot)
            $isHouseLogo = false;
            if (isset($_SESSION[$guid]['gibbonHouseIDLogo']) and isset($_SESSION[$guid]['gibbonHouseIDName'])) {
                if ($_SESSION[$guid]['gibbonHouseIDLogo'] != '') {
                    $isHouseLogo = true;
                }
            }

            $URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Messenger/messageWall_view.php';
            if (isset($_SESSION[$guid]['messageWallCount']) == false) {
                $return .= " . 0 x <img title='".__($guid, 'Message Wall')."' class='minorLinkIcon' style='margin-left: 4px; opacity: 0.8; vertical-align: -75%' src='".$_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName']."/img/messageWall_none.png'>";
            } else {
                if ($_SESSION[$guid]['messageWallCount'] < 1) {
                    $return .= " . 0 x <img title='".__($guid, 'Message Wall')."' class='minorLinkIcon' style='margin-left: 4px; opacity: 0.8; vertical-align: -75%' src='".$_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName']."/img/messageWall_none.png'>";
                } else {
                    $return .= " . <a title='".__($guid, 'Message Wall')."' href='$URL'>".$_SESSION[$guid]['messageWallCount']." x <img class='minorLinkIcon' style='margin-left: 4px; vertical-align: -75%' src='".$_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName']."/img/messageWall.png'></a>";
                    if ($_SESSION[$guid]['pageLoads'] == 0 and ($_SESSION[$guid]['messengerLastBubble'] == null or $_SESSION[$guid]['messengerLastBubble'] < date('Y-m-d'))) {
                        echo $messageBubbleBGColor = getSettingByScope($connection2, 'Messenger', 'messageBubbleBGColor');
                        $bubbleBG = '';
                        if ($messageBubbleBGColor != '') {
                            $bubbleBG = '; background-color: rgba('.$messageBubbleBGColor.')!important';
                            $return .= '<style>';
                            $return .= ".ui-tooltip, .arrow:after { $bubbleBG }";
                            $return .= '</style>';
                        }
                        $messageBubbleWidthType = getSettingByScope($connection2, 'Messenger', 'messageBubbleWidthType');
                        $bubbleWidth = 300;
                        $bubbleLeft = 770;
                        if ($messageBubbleWidthType == 'Wide') {
                            $bubbleWidth = 700;
                            $bubbleLeft = 370;
                        }
                        if ($isHouseLogo) { //Spacing with house logo
                            $bubbleLeft = $bubbleLeft - 70;
                            $return .= "<div id='messageBubbleArrow' style=\"left: 1019px; top: 58px; z-index: 9999\" class='arrow top'></div>";
                            $return .= "<div id='messageBubble' style=\"left: ".$bubbleLeft.'px; top: 74px; width: '.$bubbleWidth.'px; min-width: '.$bubbleWidth.'px; max-width: '.$bubbleWidth.'px; min-height: 100px; text-align: center; padding-bottom: 10px" class="ui-tooltip ui-widget ui-corner-all ui-widget-content" role="tooltip">';
                        } else { //Spacing without house logo
                            $return .= "<div id='messageBubbleArrow' style=\"left: 1089px; top: 38px; z-index: 9999\" class='arrow top'></div>";
                            $return .= "<div id='messageBubble' style=\"left: ".$bubbleLeft.'px; top: 54px; width: '.$bubbleWidth.'px; min-width: '.$bubbleWidth.'px; max-width: '.$bubbleWidth.'px; min-height: 100px; text-align: center; padding-bottom: 10px" class="ui-tooltip ui-widget ui-corner-all ui-widget-content" role="tooltip">';
                        }
                        $return .= '<div class="ui-tooltip-content">';
                        $return .= "<div style='font-weight: bold; font-style: italic; font-size: 120%; margin-top: 10px; margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px dotted rgba(255,255,255,0.5); display: block'>".__($guid, 'New Messages').'</div>';
                        $test = count($output);
                        if ($test > 3) {
                            $test = 3;
                        }
                        for ($i = 0; $i < $test; ++$i) {
                            $return .= "<span style='font-size: 120%; font-weight: bold'>";
                            if (strlen($output[$i]['subject']) <= 30) {
                                $return .= $output[$i]['subject'];
                            } else {
                                $return .= substr($output[$i]['subject'], 0, 30).'...';
                            }

                            $return .= '</span><br/>';
                            $return .= '<i>'.$output[$i]['author'].'</i><br/><br/>';
                        }
                        if (count($output) > 3) {
                            $return .= '<i>'.__($guid, 'Plus more').'...</i>';
                        }
                        $return .= '</div>';
                        $return .= "<div style='text-align: right; margin-top: 20px; color: #666'>";
                        $return .= "<a onclick='$(\"#messageBubble\").hide(\"fade\", {}, 1); $(\"#messageBubbleArrow\").hide(\"fade\", {}, 1)' style='text-decoration: none; color: #666' href='".$URL."'>".__($guid, 'Read All').'</a> . ';
                        $return .= "<a style='text-decoration: none; color: #666' onclick='$(\"#messageBubble\").hide(\"fade\", {}, 1000); $(\"#messageBubbleArrow\").hide(\"fade\", {}, 1000)' href='#'>".__($guid, 'Dismiss').'</a>';
                        $return .= '</div>';
                        $return .= '</div>';

                        $messageBubbleAutoHide = getSettingByScope($connection2, 'Messenger', 'messageBubbleAutoHide');
                        if ($messageBubbleAutoHide != 'N') {
                            $return .= '<script type="text/javascript">';
                            $return .= '$(function() {';
                            $return .= 'setTimeout(function() {';
                            $return .= "$(\"#messageBubble\").hide('fade', {}, 3000)";
                            $return .= '}, 10000);';
                            $return .= '});';
                            $return .= '$(function() {';
                            $return .= 'setTimeout(function() {';
                            $return .= "$(\"#messageBubbleArrow\").hide('fade', {}, 3000)";
                            $return .= '}, 10000);';
                            $return .= '});';
                            $return .= '</script>';
                        }

                        try {
                            $data = array('messengerLastBubble' => date('Y-m-d'), 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                            $sql = 'UPDATE gibbonPerson SET messengerLastBubble=:messengerLastBubble WHERE gibbonPersonID=:gibbonPersonID';
                            $result = $connection2->prepare($sql);
                            $result->execute($data);
                        } catch (PDOException $e) {
                        }
                    }
                }
            }
        }

        //House logo
        if (@$isHouseLogo) {
            $return .= " . <img class='minorLinkIconLarge' title='".$_SESSION[$guid]['gibbonHouseIDName']."' style='vertical-align: -75%; margin-left: 4px' src='".$_SESSION[$guid]['absoluteURL'].'/'.$_SESSION[$guid]['gibbonHouseIDLogo']."'/>";
        }
    }

    return $return;
}

//Gets the contents of the staff dashboard for the member of staff specified
function getStaffDashboardContents($connection2, $guid, $gibbonPersonID)
{
    $return = false;

    //GET PLANNER
    $planner = false;
    $date = date('Y-m-d');
    if (isSchoolOpen($guid, $date, $connection2) == true and isActionAccessible($guid, $connection2, '/modules/Planner/planner.php') and $_SESSION[$guid]['username'] != '') {
        try {
            $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'date' => $date, 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonSchoolYearID2' => $_SESSION[$guid]['gibbonSchoolYearID'], 'date2' => $date, 'gibbonPersonID2' => $_SESSION[$guid]['gibbonPersonID']);
            $sql = "(SELECT gibbonCourseClass.gibbonCourseClassID, gibbonPlannerEntry.gibbonPlannerEntryID, gibbonUnitID, gibbonHookID, gibbonPlannerEntry.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, timeStart, timeEnd, viewableStudents, viewableParents, homework, homeworkSubmission, homeworkCrowdAssess, role, date, summary, gibbonPlannerEntryStudentHomework.homeworkDueDateTime AS myHomeworkDueDateTime FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) LEFT JOIN gibbonPlannerEntryStudentHomework ON (gibbonPlannerEntryStudentHomework.gibbonPlannerEntryID=gibbonPlannerEntry.gibbonPlannerEntryID AND gibbonPlannerEntryStudentHomework.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND date=:date AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND NOT role='Student - Left' AND NOT role='Teacher - Left') UNION (SELECT gibbonCourseClass.gibbonCourseClassID, gibbonPlannerEntry.gibbonPlannerEntryID, gibbonUnitID, gibbonHookID, gibbonPlannerEntry.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, timeStart, timeEnd, viewableStudents, viewableParents, homework, homeworkSubmission, homeworkCrowdAssess,  role, date, summary, NULL AS myHomeworkDueDateTime FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonPlannerEntryGuest ON (gibbonPlannerEntryGuest.gibbonPlannerEntryID=gibbonPlannerEntry.gibbonPlannerEntryID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID2 AND date=:date2 AND gibbonPlannerEntryGuest.gibbonPersonID=:gibbonPersonID2) ORDER BY date, timeStart, course, class";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $planner .= "<div class='error'>".$e->getMessage().'</div>';
        }
        $planner .= '<h2>';
        $planner .= __($guid, "Today's Lessons");
        $planner .= '</h2>';
        if ($result->rowCount() < 1) {
            $planner .= "<div class='warning'>";
            $planner .= __($guid, 'There are no records to display.');
            $planner .= '</div>';
        } else {
            $planner .= "<div class='linkTop'>";
            $planner .= "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Planner/planner.php'>".__($guid, 'View Planner').'</a>';
            $planner .= '</div>';

            $planner .= "<table cellspacing='0' style='width: 100%'>";
            $planner .= "<tr class='head'>";
            $planner .= '<th>';
            $planner .= __($guid, 'Class').'<br/>';
            $planner .= '</th>';
            $planner .= '<th>';
            $planner .= __($guid, 'Lesson').'</br>';
            $planner .= "<span style='font-size: 85%; font-style: italic'>".__($guid, 'Unit').'</span>';
            $planner .= '</th>';
            $planner .= '<th>';
            $planner .= __($guid, 'Homework');
            $planner .= '</th>';
            $planner .= '<th>';
            $planner .= __($guid, 'Summary');
            $planner .= '</th>';
            $planner .= '<th>';
            $planner .= __($guid, 'Like');
            $planner .= '</th>';
            $planner .= '<th>';
            $planner .= __($guid, 'Action');
            $planner .= '</th>';
            $planner .= '</tr>';

            $count = 0;
            $rowNum = 'odd';
            while ($row = $result->fetch()) {
                if (!($row['role'] == 'Student' and $row['viewableStudents'] == 'N')) {
                    if ($count % 2 == 0) {
                        $rowNum = 'even';
                    } else {
                        $rowNum = 'odd';
                    }
                    ++$count;

                        //Highlight class in progress
                        if ((date('H:i:s') > $row['timeStart']) and (date('H:i:s') < $row['timeEnd']) and ($date) == date('Y-m-d')) {
                            $rowNum = 'current';
                        }

                        //COLOR ROW BY STATUS!
                        $planner .= "<tr class=$rowNum>";
                    $planner .= '<td>';
                    $planner .= $row['course'].'.'.$row['class'].'<br/>';
                    $planner .= "<span style='font-style: italic; font-size: 75%'>".substr($row['timeStart'], 0, 5).'-'.substr($row['timeEnd'], 0, 5).'</span>';
                    $planner .= '</td>';
                    $planner .= '<td>';
                    $planner .= '<b>'.$row['name'].'</b><br/>';
                    $planner .= "<span style='font-size: 85%; font-style: italic'>";
                    $unit = getUnit($connection2, $row['gibbonUnitID'], $row['gibbonHookID'], $row['gibbonCourseClassID']);
                    if (isset($unit[0])) {
                        $planner .= $unit[0];
                        if ($unit[1] != '') {
                            $planner .= '<br/><i>'.$unit[1].' '.__($guid, 'Unit').'</i>';
                        }
                    }
                    $planner .= '</span>';
                    $planner .= '</td>';
                    $planner .= '<td>';
                    if ($row['homework'] == 'N' and $row['myHomeworkDueDateTime'] == '') {
                        $planner .= __($guid, 'No');
                    } else {
                        if ($row['homework'] == 'Y') {
                            $planner .= __($guid, 'Yes').': '.__($guid, 'Teacher Recorded').'<br/>';
                            if ($row['homeworkSubmission'] == 'Y') {
                                $planner .= "<span style='font-size: 85%; font-style: italic'>+".__($guid, 'Submission').'</span><br/>';
                                if ($row['homeworkCrowdAssess'] == 'Y') {
                                    $planner .= "<span style='font-size: 85%; font-style: italic'>+".__($guid, 'Crowd Assessment').'</span><br/>';
                                }
                            }
                        }
                        if ($row['myHomeworkDueDateTime'] != '') {
                            $planner .= __($guid, 'Yes').': '.__($guid, 'Student Recorded').'</br>';
                        }
                    }
                    $planner .= '</td>';
                    $planner .= '<td>';
                    $planner .= $row['summary'];
                    $planner .= '</td>';
                    $planner .= '<td>';
                    if ($row['role'] == 'Teacher') {
                        $planner .= countLikesByContext($connection2, 'Planner', 'gibbonPlannerEntryID', $row['gibbonPlannerEntryID']);
                    } else {
                        $likesGiven = countLikesByContextAndGiver($connection2, 'Planner', 'gibbonPlannerEntryID', $row['gibbonPlannerEntryID'], $_SESSION[$guid]['gibbonPersonID']);
                        if ($likesGiven != 1) {
                            $planner .= "<a href='".$_SESSION[$guid]['absoluteURL'].'/modules/Planner/plannerProcess.php?gibbonPlannerEntryID='.$row['gibbonPlannerEntryID'].'&address=/modules/Planner/planner.php&viewBy=Class&gibbonCourseClassID='.$row['gibbonCourseClassID']."&date=&returnToIndex=Y'><img src='".$_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName']."/img/like_off.png'></a>";
                        } else {
                            $planner .= "<a href='".$_SESSION[$guid]['absoluteURL'].'/modules/Planner/plannerProcess.php?gibbonPlannerEntryID='.$row['gibbonPlannerEntryID'].'&address=/modules/Planner/planner.php&viewBy=Class&gibbonCourseClassID='.$row['gibbonCourseClassID']."&date=&returnToIndex=Y'><img src='".$_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName']."/img/like_on.png'></a>";
                        }
                    }
                    $planner .= '</td>';
                    $planner .= '<td>';
                    $planner .= "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Planner/planner_view_full.php&viewBy=class&gibbonCourseClassID='.$row['gibbonCourseClassID'].'&gibbonPlannerEntryID='.$row['gibbonPlannerEntryID']."'><img title='".__($guid, 'View')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/plus.png'/></a>";
                    $planner .= '</td>';
                    $planner .= '</tr>';
                }
            }
            $planner .= '</table>';
        }
    }

    //GET TIMETABLE
    $timetable = false;
    if (isActionAccessible($guid, $connection2, '/modules/Timetable/tt.php') and $_SESSION[$guid]['username'] != '' and getRoleCategory($_SESSION[$guid]['gibbonRoleIDCurrent'], $connection2) == 'Staff') {
        ?>
		<script type="text/javascript">
			$(document).ready(function(){
				$("#tt").load("<?php echo $_SESSION[$guid]['absoluteURL'] ?>/index_tt_ajax.php",{"gibbonTTID": "<?php echo @$_GET['gibbonTTID'] ?>", "ttDate": "<?php echo @$_POST['ttDate'] ?>", "fromTT": "<?php echo @$_POST['fromTT'] ?>", "personalCalendar": "<?php echo @$_POST['personalCalendar'] ?>", "schoolCalendar": "<?php echo @$_POST['schoolCalendar'] ?>", "spaceBookingCalendar": "<?php echo @$_POST['spaceBookingCalendar'] ?>"});
			});
		</script>
		<?php
        $timetable .= '<h2>'.__($guid, 'My Timetable').'</h2>';
        $timetable .= "<div id='tt' name='tt' style='width: 100%; min-height: 40px; text-align: center'>";
        $timetable .= "<img style='margin: 10px 0 5px 0' src='".$_SESSION[$guid]['absoluteURL']."/themes/Default/img/loading.gif' alt='".__($guid, 'Loading')."' onclick='return false;' /><br/><p style='text-align: center'>".__($guid, 'Loading').'</p>';
        $timetable .= '</div>';
    }

    //GET ROLL GROUPS
    $rollGroups = array();
    $rollGroupCount = 0;
    $count = 0;
    try {
        $dataRollGroups = array('gibbonPersonIDTutor' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonPersonIDTutor2' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonPersonIDTutor3' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
        $sqlRollGroups = 'SELECT * FROM gibbonRollGroup WHERE (gibbonPersonIDTutor=:gibbonPersonIDTutor OR gibbonPersonIDTutor2=:gibbonPersonIDTutor2 OR gibbonPersonIDTutor3=:gibbonPersonIDTutor3) AND gibbonSchoolYearID=:gibbonSchoolYearID';
        $resultRollGroups = $connection2->prepare($sqlRollGroups);
        $resultRollGroups->execute($dataRollGroups);
    } catch (PDOException $e) {
        echo "<div class='error'>".$e->getMessage().'</div>';
    }

    while ($rowRollGroups = $resultRollGroups->fetch()) {
        $rollGroups[$count][0] = $rowRollGroups['gibbonRollGroupID'];
        $rollGroups[$count][1] = $rowRollGroups['nameShort'];

        //Roll group table
        $rollGroups[$count][2] = "<div class='linkTop' style='margin-top: 0px'>";
        $rollGroups[$count][2] .= "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Attendance/attendance_take_byRollGroup.php&gibbonRollGroupID='.$rowRollGroups['gibbonRollGroupID']."'>".__($guid, 'Take Attendance')."<img style='margin-left: 5px' title='".__($guid, 'Take Attendance')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/attendance.png'/></a> | ";
        $rollGroups[$count][2] .= "<a href='".$_SESSION[$guid]['absoluteURL'].'/indexExport.php?gibbonRollGroupID='.$rowRollGroups['gibbonRollGroupID']."'>".__($guid, 'Export to Excel')."<img style='margin-left: 5px' title='".__($guid, 'Export to Excel')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/download.png'/></a>";
        $rollGroups[$count][2] .= '</div>';
        $rollGroups[$count][2] .= getRollGroupTable($guid, $rowRollGroups['gibbonRollGroupID'], 5, $connection2);

        $behaviourView = isActionAccessible($guid, $connection2, '/modules/Behaviour/behaviour_view.php');
        if ($behaviourView) {
            //Behaviour
            $rollGroups[$count][3] = '';
            $plural = 's';
            if ($resultRollGroups->rowCount() == 1) {
                $plural = '';
            }
            try {
                $dataBehaviour = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonSchoolYearID2' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonRollGroupID' => $rollGroups[$count][0]);
                $sqlBehaviour = 'SELECT gibbonBehaviour.*, student.surname AS surnameStudent, student.preferredName AS preferredNameStudent, creator.surname AS surnameCreator, creator.preferredName AS preferredNameCreator, creator.title FROM gibbonBehaviour JOIN gibbonPerson AS student ON (gibbonBehaviour.gibbonPersonID=student.gibbonPersonID) JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=student.gibbonPersonID) JOIN gibbonPerson AS creator ON (gibbonBehaviour.gibbonPersonIDCreator=creator.gibbonPersonID) WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonBehaviour.gibbonSchoolYearID=:gibbonSchoolYearID2 AND gibbonRollGroupID=:gibbonRollGroupID ORDER BY timestamp DESC';
                $resultBehaviour = $connection2->prepare($sqlBehaviour);
                $resultBehaviour->execute($dataBehaviour);
            } catch (PDOException $e) {
                $rollGroups[$count][3] .= "<div class='error'>".$e->getMessage().'</div>';
            }

            if (isActionAccessible($guid, $connection2, '/modules/Behaviour/behaviour_manage_add.php')) {
                $rollGroups[$count][3] .= "<div class='linkTop'>";
                $rollGroups[$count][3] .= "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Behaviour/behaviour_manage_add.php&gibbonPersonID=&gibbonRollGroupID=&gibbonYearGroupID=&type='>".__($guid, 'Add')."<img style='margin: 0 0 -4px 5px' title='".__($guid, 'Add')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/page_new.png'/></a>";
                $policyLink = getSettingByScope($connection2, 'Behaviour', 'policyLink');
                if ($policyLink != '') {
                    $rollGroups[$count][3] .= " | <a target='_blank' href='$policyLink'>".__($guid, 'View Behaviour Policy').'</a>';
                }
                $rollGroups[$count][3] .= '</div>';
            }

            if ($resultBehaviour->rowCount() < 1) {
                $rollGroups[$count][3] .= "<div class='error'>";
                $rollGroups[$count][3] .= __($guid, 'There are no records to display.');
                $rollGroups[$count][3] .= '</div>';
            } else {
                $rollGroups[$count][3] .= "<table cellspacing='0' style='width: 100%'>";
                $rollGroups[$count][3] .= "<tr class='head'>";
                $rollGroups[$count][3] .= '<th>';
                $rollGroups[$count][3] .= __($guid, 'Student & Date');
                $rollGroups[$count][3] .= '</th>';
                $rollGroups[$count][3] .= '<th>';
                $rollGroups[$count][3] .= __($guid, 'Type');
                $rollGroups[$count][3] .= '</th>';
                $rollGroups[$count][3] .= '<th>';
                $rollGroups[$count][3] .= __($guid, 'Descriptor');
                $rollGroups[$count][3] .= '</th>';
                $rollGroups[$count][3] .= '<th>';
                $rollGroups[$count][3] .= __($guid, 'Level');
                $rollGroups[$count][3] .= '</th>';
                $rollGroups[$count][3] .= '<th>';
                $rollGroups[$count][3] .= __($guid, 'Teacher');
                $rollGroups[$count][3] .= '</th>';
                $rollGroups[$count][3] .= '<th>';
                $rollGroups[$count][3] .= __($guid, 'Action');
                $rollGroups[$count][3] .= '</th>';
                $rollGroups[$count][3] .= '</tr>';

                $count2 = 0;
                $rowNum = 'odd';
                while ($rowBehaviour = $resultBehaviour->fetch()) {
                    if ($count2 % 2 == 0) {
                        $rowNum = 'even';
                    } else {
                        $rowNum = 'odd';
                    }
                    ++$count2;

                    //COLOR ROW BY STATUS!
                    $rollGroups[$count][3] .= "<tr class=$rowNum>";
                    $rollGroups[$count][3] .= '<td>';
                    $rollGroups[$count][3] .= '<b>'.formatName('', $rowBehaviour['preferredNameStudent'], $rowBehaviour['surnameStudent'], 'Student', false).'</b><br/>';
                    if (substr($rowBehaviour['timestamp'], 0, 10) > $rowBehaviour['date']) {
                        $rollGroups[$count][3] .= __($guid, 'Date Updated').': '.dateConvertBack($guid, substr($rowBehaviour['timestamp'], 0, 10)).'<br/>';
                        $rollGroups[$count][3] .= __($guid, 'Incident Date').': '.dateConvertBack($guid, $rowBehaviour['date']).'<br/>';
                    } else {
                        $rollGroups[$count][3] .= dateConvertBack($guid, $rowBehaviour['date']).'<br/>';
                    }
                    $rollGroups[$count][3] .= '</td>';
                    $rollGroups[$count][3] .= "<td style='text-align: center'>";
                    if ($rowBehaviour['type'] == 'Negative') {
                        $rollGroups[$count][3] .= "<img title='".__($guid, 'Negative')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/iconCross.png'/> ";
                    } elseif ($rowBehaviour['type'] == 'Positive') {
                        $rollGroups[$count][3] .= "<img title='".__($guid, 'Position')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/iconTick.png'/> ";
                    }
                    $rollGroups[$count][3] .= '</td>';
                    $rollGroups[$count][3] .= '<td>';
                    $rollGroups[$count][3] .= trim($rowBehaviour['descriptor']);
                    $rollGroups[$count][3] .= '</td>';
                    $rollGroups[$count][3] .= '<td>';
                    $rollGroups[$count][3] .= trim($rowBehaviour['level']);
                    $rollGroups[$count][3] .= '</td>';
                    $rollGroups[$count][3] .= '<td>';
                    $rollGroups[$count][3] .= formatName($rowBehaviour['title'], $rowBehaviour['preferredNameCreator'], $rowBehaviour['surnameCreator'], 'Staff', false).'<br/>';
                    $rollGroups[$count][3] .= '</td>';
                    $rollGroups[$count][3] .= '<td>';
                    $rollGroups[$count][3] .= "<script type='text/javascript'>";
                    $rollGroups[$count][3] .= '$(document).ready(function(){';
                    $rollGroups[$count][3] .= "\$(\".comment-$count2\").hide();";
                    $rollGroups[$count][3] .= "\$(\".show_hide-$count2\").fadeIn(1000);";
                    $rollGroups[$count][3] .= "\$(\".show_hide-$count2\").click(function(){";
                    $rollGroups[$count][3] .= "\$(\".comment-$count2\").fadeToggle(1000);";
                    $rollGroups[$count][3] .= '});';
                    $rollGroups[$count][3] .= '});';
                    $rollGroups[$count][3] .= '</script>';
                    if ($rowBehaviour['comment'] != '') {
                        $rollGroups[$count][3] .= "<a title='".__($guid, 'View Description')."' class='show_hide-$count2' onclick='false' href='#'><img style='padding-right: 5px' src='".$_SESSION[$guid]['absoluteURL']."/themes/Default/img/page_down.png' alt='".__($guid, 'Show Comment')."' onclick='return false;' /></a>";
                    }
                    $rollGroups[$count][3] .= '</td>';
                    $rollGroups[$count][3] .= '</tr>';
                    if ($rowBehaviour['comment'] != '') {
                        if ($rowBehaviour['type'] == 'Positive') {
                            $bg = 'background-color: #D4F6DC;';
                        } else {
                            $bg = 'background-color: #F6CECB;';
                        }
                        $rollGroups[$count][3] .= "<tr class='comment-$count2' id='comment-$count2'>";
                        $rollGroups[$count][3] .= "<td style='$bg' colspan=6>";
                        $rollGroups[$count][3] .= $rowBehaviour['comment'];
                        $rollGroups[$count][3] .= '</td>';
                        $rollGroups[$count][3] .= '</tr>';
                    }
                    $rollGroups[$count][3] .= '</tr>';
                    $rollGroups[$count][3] .= '</tr>';
                }
                $rollGroups[$count][3] .= '</table>';
            }
        }

        ++$count;
        ++$rollGroupCount;
    }

    //GET HOOKS INTO DASHBOARD
    $hooks = array();
    try {
        $dataHooks = array();
        $sqlHooks = "SELECT * FROM gibbonHook WHERE type='Staff Dashboard'";
        $resultHooks = $connection2->prepare($sqlHooks);
        $resultHooks->execute($dataHooks);
    } catch (PDOException $e) {
        echo "<div class='error'>".$e->getMessage().'</div>';
    }
    if ($resultHooks->rowCount() > 0) {
        $count = 0;
        while ($rowHooks = $resultHooks->fetch()) {
            $options = unserialize($rowHooks['options']);
            //Check for permission to hook
            try {
                $dataHook = array('gibbonRoleIDCurrent' => $_SESSION[$guid]['gibbonRoleIDCurrent'], 'sourceModuleName' => $options['sourceModuleName']);
                $sqlHook = "SELECT gibbonHook.name, gibbonModule.name AS module, gibbonAction.name AS action FROM gibbonHook JOIN gibbonModule ON (gibbonHook.gibbonModuleID=gibbonModule.gibbonModuleID) JOIN gibbonAction ON (gibbonAction.gibbonModuleID=gibbonModule.gibbonModuleID) JOIN gibbonPermission ON (gibbonPermission.gibbonActionID=gibbonAction.gibbonActionID) WHERE gibbonAction.gibbonModuleID=(SELECT gibbonModuleID FROM gibbonModule WHERE gibbonPermission.gibbonRoleID=:gibbonRoleIDCurrent AND name=:sourceModuleName) AND gibbonHook.type='Staff Dashboard'  AND gibbonAction.name='".$options['sourceModuleAction']."' AND gibbonModule.name='".$options['sourceModuleName']."' ORDER BY name";
                $resultHook = $connection2->prepare($sqlHook);
                $resultHook->execute($dataHook);
            } catch (PDOException $e) {
            }
            if ($resultHook->rowCount() == 1) {
                $rowHook = $resultHook->fetch();
                $hooks[$count]['name'] = $rowHooks['name'];
                $hooks[$count]['sourceModuleName'] = $rowHook['module'];
                $hooks[$count]['sourceModuleInclude'] = $options['sourceModuleInclude'];
                ++$count;
            }
        }
    }

    if ($planner == false and $timetable == false and count($hooks) < 1) {
        $return .= "<div class='warning'>";
        $return .= __($guid, 'There are no records to display.');
        $return .= '</div>';
    } else {
        $staffDashboardDefaultTab = getSettingByScope($connection2, 'School Admin', 'staffDashboardDefaultTab');
        $staffDashboardDefaultTabCount = null;

        $return .= "<div id='".$gibbonPersonID."tabs' style='margin: 0 0'>";
        $return .= '<ul>';
        $tabCount = 1;
        if ($planner != false or $timetable != false) {
            $return .= "<li><a href='#tabs".$tabCount."'>".__($guid, 'Planner').'</a></li>';
            if ($staffDashboardDefaultTab == 'Planner')
                $staffDashboardDefaultTabCount = $tabCount;
            ++$tabCount;
        }
        if (count($rollGroups) > 0) {
            foreach ($rollGroups as $rollGroup) {
                $return .= "<li><a href='#tabs".$tabCount."'>".$rollGroup[1].'</a></li>';
                ++$tabCount;
                if ($behaviourView) {
                    $return .= "<li><a href='#tabs".$tabCount."'>".$rollGroup[1].' '.__($guid, 'Behaviour').'</a></li>';
                    ++$tabCount;
                }
            }
        }

        foreach ($hooks as $hook) {
            $return .= "<li><a href='#tabs".$tabCount."'>".__($guid, $hook['name']).'</a></li>';
            if ($staffDashboardDefaultTab == $hook['name'])
                $staffDashboardDefaultTabCount = $tabCount;
            ++$tabCount;
        }
        $return .= '</ul>';

        $tabCount = 1;
        if ($planner != false or $timetable != false) {
            $return .= "<div id='tabs".$tabCount."'>";
            $return .= $planner;
            $return .= $timetable;
            $return .= '</div>';
            ++$tabCount;
        }
        if (count($rollGroups) > 0) {
            foreach ($rollGroups as $rollGroup) {
                $return .= "<div id='tabs".$tabCount."'>";
                $return .= $rollGroup[2];
                $return .= '</div>';
                ++$tabCount;

                if ($behaviourView) {
                    $return .= "<div id='tabs".$tabCount."'>";
                    $return .= $rollGroup[3];
                    $return .= '</div>';
                    ++$tabCount;
                }
            }
        }
        foreach ($hooks as $hook) {
            $return .= "<div style='min-height: 100px' id='tabs".$tabCount."'>";
            $include = $_SESSION[$guid]['absolutePath'].'/modules/'.$hook['sourceModuleName'].'/'.$hook['sourceModuleInclude'];
            if (!file_exists($include)) {
                $return .= "<div class='error'>";
                $return .= __($guid, 'The selected page cannot be displayed due to a hook error.');
                $return .= '</div>';
            } else {
                $return .= include $include;
            }
            ++$tabCount;
            $return .= '</div>';
        }
        $return .= '</div>';
    }

    $defaultTab = 0;
    if (isset($_GET['tab'])) {
        $defaultTab = $_GET['tab'];
    }
    else if (!is_null($staffDashboardDefaultTabCount)) {
        $defaultTab = $staffDashboardDefaultTabCount-1;
    }

    $return .= "<script type='text/javascript'>";
    $return .= '$(function() {';
    $return .= '$( "#'.$gibbonPersonID.'tabs" ).tabs({';
    $return .= 'active: '.$defaultTab.',';
    $return .= 'ajaxOptions: {';
    $return .= 'error: function( xhr, status, index, anchor ) {';
    $return .= '$( anchor.hash ).html(';
    $return .= "\"Couldn't load this tab.\" );";
    $return .= '}';
    $return .= '}';
    $return .= '});';
    $return .= '});';
    $return .= '</script>';

    return $return;
}

//Gets the contents of the student dashboard for the student specified
function getStudentDashboardContents($connection2, $guid, $gibbonPersonID)
{
    $return = false;

    //GET PLANNER
    $planner = false;
    $date = date('Y-m-d');
    if (isSchoolOpen($guid, $date, $connection2) == true and isActionAccessible($guid, $connection2, '/modules/Planner/planner.php') and $_SESSION[$guid]['username'] != '') {
        try {
            $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'date' => $date, 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonSchoolYearID2' => $_SESSION[$guid]['gibbonSchoolYearID'], 'date2' => $date, 'gibbonPersonID2' => $_SESSION[$guid]['gibbonPersonID']);
            $sql = "(SELECT gibbonCourseClass.gibbonCourseClassID, gibbonPlannerEntry.gibbonPlannerEntryID, gibbonUnitID, gibbonHookID, gibbonPlannerEntry.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, timeStart, timeEnd, viewableStudents, viewableParents, homework, homeworkSubmission, homeworkCrowdAssess, role, date, summary, gibbonPlannerEntryStudentHomework.homeworkDueDateTime AS myHomeworkDueDateTime FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) LEFT JOIN gibbonPlannerEntryStudentHomework ON (gibbonPlannerEntryStudentHomework.gibbonPlannerEntryID=gibbonPlannerEntry.gibbonPlannerEntryID AND gibbonPlannerEntryStudentHomework.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND date=:date AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND NOT role='Student - Left' AND NOT role='Teacher - Left') UNION (SELECT gibbonCourseClass.gibbonCourseClassID, gibbonPlannerEntry.gibbonPlannerEntryID, gibbonUnitID, gibbonHookID, gibbonPlannerEntry.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, timeStart, timeEnd, viewableStudents, viewableParents, homework, homeworkSubmission, homeworkCrowdAssess,  role, date, summary, NULL AS myHomeworkDueDateTime FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonPlannerEntryGuest ON (gibbonPlannerEntryGuest.gibbonPlannerEntryID=gibbonPlannerEntry.gibbonPlannerEntryID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID2 AND date=:date2 AND gibbonPlannerEntryGuest.gibbonPersonID=:gibbonPersonID2) ORDER BY date, timeStart, course, class";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $planner .= "<div class='error'>".$e->getMessage().'</div>';
        }
        $planner .= '<h2>';
        $planner .= __($guid, "Today's Lessons");
        $planner .= '</h2>';
        if ($result->rowCount() < 1) {
            $planner .= "<div class='warning'>";
            $planner .= __($guid, 'There are no records to display.');
            $planner .= '</div>';
        } else {
            $planner .= "<div class='linkTop'>";
            $planner .= "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Planner/planner.php'>".__($guid, 'View Planner').'</a>';
            $planner .= '</div>';

            $planner .= "<table cellspacing='0' style='width: 100%'>";
            $planner .= "<tr class='head'>";
            $planner .= '<th>';
            $planner .= __($guid, 'Class').'<br/>';
            $planner .= '</th>';
            $planner .= '<th>';
            $planner .= __($guid, 'Lesson').'</br>';
            $planner .= "<span style='font-size: 85%; font-style: italic'>".__($guid, 'Unit').'</span>';
            $planner .= '</th>';
            $planner .= '<th>';
            $planner .= __($guid, 'Homework');
            $planner .= '</th>';
            $planner .= '<th>';
            $planner .= __($guid, 'Summary');
            $planner .= '</th>';
            $planner .= '<th>';
            $planner .= __($guid, 'Like');
            $planner .= '</th>';
            $planner .= '<th>';
            $planner .= __($guid, 'Action');
            $planner .= '</th>';
            $planner .= '</tr>';

            $count = 0;
            $rowNum = 'odd';
            while ($row = $result->fetch()) {
                if (!($row['role'] == 'Student' and $row['viewableStudents'] == 'N')) {
                    if ($count % 2 == 0) {
                        $rowNum = 'even';
                    } else {
                        $rowNum = 'odd';
                    }
                    ++$count;

                    //Highlight class in progress
                    if ((date('H:i:s') > $row['timeStart']) and (date('H:i:s') < $row['timeEnd']) and ($date) == date('Y-m-d')) {
                        $rowNum = 'current';
                    }

                    //COLOR ROW BY STATUS!
                    $planner .= "<tr class=$rowNum>";
                    $planner .= '<td>';
                    $planner .= $row['course'].'.'.$row['class'].'<br/>';
                    $planner .= "<span style='font-style: italic; font-size: 75%'>".substr($row['timeStart'], 0, 5).'-'.substr($row['timeEnd'], 0, 5).'</span>';
                    $planner .= '</td>';
                    $planner .= '<td>';
                    $planner .= '<b>'.$row['name'].'</b><br/>';
                    $planner .= "<span style='font-size: 85%; font-style: italic'>";
                    $unit = getUnit($connection2, $row['gibbonUnitID'], $row['gibbonHookID'], $row['gibbonCourseClassID']);
                    if (isset($unit[0])) {
                        $planner .= $unit[0];
                        if ($unit[1] != '') {
                            $planner .= '<br/><i>'.$unit[1].' '.__($guid, 'Unit').'</i>';
                        }
                    }
                    $planner .= '</span>';
                    $planner .= '</td>';
                    $planner .= '<td>';
                    if ($row['homework'] == 'N' and $row['myHomeworkDueDateTime'] == '') {
                        $planner .= __($guid, 'No');
                    } else {
                        if ($row['homework'] == 'Y') {
                            $planner .= __($guid, 'Yes').': '.__($guid, 'Teacher Recorded').'<br/>';
                            if ($row['homeworkSubmission'] == 'Y') {
                                $planner .= "<span style='font-size: 85%; font-style: italic'>+".__($guid, 'Submission').'</span><br/>';
                                if ($row['homeworkCrowdAssess'] == 'Y') {
                                    $planner .= "<span style='font-size: 85%; font-style: italic'>+".__($guid, 'Crowd Assessment').'</span><br/>';
                                }
                            }
                        }
                        if ($row['myHomeworkDueDateTime'] != '') {
                            $planner .= __($guid, 'Yes').': '.__($guid, 'Student Recorded').'</br>';
                        }
                    }
                    $planner .= '</td>';
                    $planner .= '<td>';
                    $planner .= $row['summary'];
                    $planner .= '</td>';
                    $planner .= '<td>';
                    if ($row['role'] == 'Teacher') {
                        $planner .= countLikesByContext($connection2, 'Planner', 'gibbonPlannerEntryID', $row['gibbonPlannerEntryID']);
                    } else {
                        $likesGiven = countLikesByContextAndGiver($connection2, 'Planner', 'gibbonPlannerEntryID', $row['gibbonPlannerEntryID'], $_SESSION[$guid]['gibbonPersonID']);
                        if ($likesGiven != 1) {
                            $planner .= "<a href='".$_SESSION[$guid]['absoluteURL'].'/modules/Planner/plannerProcess.php?gibbonPlannerEntryID='.$row['gibbonPlannerEntryID'].'&address=/modules/Planner/planner.php&viewBy=Class&gibbonCourseClassID='.$row['gibbonCourseClassID']."&date=&returnToIndex=Y'><img src='".$_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName']."/img/like_off.png'></a>";
                        } else {
                            $planner .= "<a href='".$_SESSION[$guid]['absoluteURL'].'/modules/Planner/plannerProcess.php?gibbonPlannerEntryID='.$row['gibbonPlannerEntryID'].'&address=/modules/Planner/planner.php&viewBy=Class&gibbonCourseClassID='.$row['gibbonCourseClassID']."&date=&returnToIndex=Y'><img src='".$_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName']."/img/like_on.png'></a>";
                        }
                    }
                    $planner .= '</td>';
                    $planner .= '<td>';
                    $planner .= "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Planner/planner_view_full.php&viewBy=class&gibbonCourseClassID='.$row['gibbonCourseClassID'].'&gibbonPlannerEntryID='.$row['gibbonPlannerEntryID']."'><img title='".__($guid, 'View')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/plus.png'/></a>";
                    $planner .= '</td>';
                    $planner .= '</tr>';
                }
            }
            $planner .= '</table>';
        }
    }

    //GET TIMETABLE
    $timetable = false;
    if (isActionAccessible($guid, $connection2, '/modules/Timetable/tt.php') and $_SESSION[$guid]['username'] != '' and getRoleCategory($_SESSION[$guid]['gibbonRoleIDCurrent'], $connection2) == 'Student') {
        ?>
		<script type="text/javascript">
			$(document).ready(function(){
				$("#tt").load("<?php echo $_SESSION[$guid]['absoluteURL'] ?>/index_tt_ajax.php",{"gibbonTTID": "<?php echo @$_GET['gibbonTTID'] ?>", "ttDate": "<?php echo @$_POST['ttDate'] ?>", "fromTT": "<?php echo @$_POST['fromTT'] ?>", "personalCalendar": "<?php echo @$_POST['personalCalendar'] ?>", "schoolCalendar": "<?php echo @$_POST['schoolCalendar'] ?>", "spaceBookingCalendar": "<?php echo @$_POST['spaceBookingCalendar'] ?>"});
			});
		</script>
		<?php
        $timetable .= '<h2>'.__($guid, 'My Timetable').'</h2>';
        $timetable .= "<div id='tt' name='tt' style='width: 100%; min-height: 40px; text-align: center'>";
        $timetable .= "<img style='margin: 10px 0 5px 0' src='".$_SESSION[$guid]['absoluteURL']."/themes/Default/img/loading.gif' alt='".__($guid, 'Loading')."' onclick='return false;' /><br/><p style='text-align: center'>".__($guid, 'Loading').'</p>';
        $timetable .= '</div>';
    }

    //GET HOOKS INTO DASHBOARD
    $hooks = array();
    try {
        $dataHooks = array();
        $sqlHooks = "SELECT * FROM gibbonHook WHERE type='Student Dashboard'";
        $resultHooks = $connection2->prepare($sqlHooks);
        $resultHooks->execute($dataHooks);
    } catch (PDOException $e) {
        echo "<div class='error'>".$e->getMessage().'</div>';
    }
    if ($resultHooks->rowCount() > 0) {
        $count = 0;
        while ($rowHooks = $resultHooks->fetch()) {
            $options = unserialize($rowHooks['options']);
            //Check for permission to hook
            try {
                $dataHook = array('gibbonRoleIDCurrent' => $_SESSION[$guid]['gibbonRoleIDCurrent'], 'sourceModuleName' => $options['sourceModuleName']);
                $sqlHook = "SELECT gibbonHook.name, gibbonModule.name AS module, gibbonAction.name AS action FROM gibbonHook JOIN gibbonModule ON (gibbonHook.gibbonModuleID=gibbonModule.gibbonModuleID) JOIN gibbonAction ON (gibbonAction.gibbonModuleID=gibbonModule.gibbonModuleID) JOIN gibbonPermission ON (gibbonPermission.gibbonActionID=gibbonAction.gibbonActionID) WHERE gibbonAction.gibbonModuleID=(SELECT gibbonModuleID FROM gibbonModule WHERE name=:sourceModuleName) AND gibbonPermission.gibbonRoleID=:gibbonRoleIDCurrent AND gibbonHook.type='Student Dashboard' AND gibbonAction.name='".$options['sourceModuleAction']."' AND gibbonModule.name='".$options['sourceModuleName']."' ORDER BY name";
                $resultHook = $connection2->prepare($sqlHook);
                $resultHook->execute($dataHook);
            } catch (PDOException $e) {
            }
            if ($resultHook->rowCount() == 1) {
                $rowHook = $resultHook->fetch();
                $hooks[$count]['name'] = $rowHooks['name'];
                $hooks[$count]['sourceModuleName'] = $rowHook['module'];
                $hooks[$count]['sourceModuleInclude'] = $options['sourceModuleInclude'];
                ++$count;
            }
        }
    }

    if ($planner == false and $timetable == false and count($hooks) < 1) {
        $return .= "<div class='warning'>";
        $return .= __($guid, 'There are no records to display.');
        $return .= '</div>';
    } else {
        $studentDashboardDefaultTab = getSettingByScope($connection2, 'School Admin', 'studentDashboardDefaultTab');
        $studentDashboardDefaultTabCount = null;

        $return .= "<div id='".$gibbonPersonID."tabs' style='margin: 0 0'>";
        $return .= '<ul>';
        $tabCount = 1;
        if ($planner != false or $timetable != false) {
            $return .= "<li><a href='#tabs".$tabCount."'>".__($guid, 'Planner').'</a></li>';
            if ($studentDashboardDefaultTab == 'Planner')
                $studentDashboardDefaultTabCount = $tabCount;
            ++$tabCount;
        }
        foreach ($hooks as $hook) {
            $return .= "<li><a href='#tabs".$tabCount."'>".__($guid, $hook['name']).'</a></li>';
            if ($studentDashboardDefaultTab == $hook['name'])
                $studentDashboardDefaultTabCount = $tabCount;
            ++$tabCount;
        }
        $return .= '</ul>';

        $tabCount = 1;
        if ($planner != false or $timetable != false) {
            $return .= "<div id='tabs".$tabCount."'>";
            $return .= $planner;
            $return .= $timetable;
            $return .= '</div>';
            ++$tabCount;
        }
        foreach ($hooks as $hook) {
            $return .= "<div style='min-height: 100px' id='tabs".$tabCount."'>";
            $include = $_SESSION[$guid]['absolutePath'].'/modules/'.$hook['sourceModuleName'].'/'.$hook['sourceModuleInclude'];
            if (!file_exists($include)) {
                $return .= "<div class='error'>";
                $return .= __($guid, 'The selected page cannot be displayed due to a hook error.');
                $return .= '</div>';
            } else {
                $return .= include $include;
            }
            ++$tabCount;
            $return .= '</div>';
        }
        $return .= '</div>';
    }

    $defaultTab = 0;
    if (isset($_GET['tab'])) {
        $defaultTab = $_GET['tab'];
    }
    else if (!is_null($studentDashboardDefaultTabCount)) {
        $defaultTab = $studentDashboardDefaultTabCount-1;
    }
    $return .= "<script type='text/javascript'>";
    $return .= '$(function() {';
    $return .= '$( "#'.$gibbonPersonID.'tabs" ).tabs({';
    $return .= 'active: '.$defaultTab.',';
    $return .= 'ajaxOptions: {';
    $return .= 'error: function( xhr, status, index, anchor ) {';
    $return .= '$( anchor.hash ).html(';
    $return .= "\"Couldn't load this tab.\" );";
    $return .= '}';
    $return .= '}';
    $return .= '});';
    $return .= '});';
    $return .= '</script>';

    return $return;
}

//Gets the contents of a single parent dashboard, for the student specified
function getParentDashboardContents($connection2, $guid, $gibbonPersonID)
{
    $return = false;
    $alert = getAlert($guid, $connection2, 002);
    $entryCount = 0;

    //PREPARE PLANNER SUMMARY
    $plannerOutput = "<span style='font-size: 85%; font-weight: bold'>".__($guid, 'Today\'s Classes')."</span> . <span style='font-size: 70%'><a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Planner/planner.php&search='.$gibbonPersonID."'>".__($guid, 'View Planner').'</a></span>';

    $classes = false;
    $date = date('Y-m-d');
    if (isSchoolOpen($guid, $date, $connection2) == true and isActionAccessible($guid, $connection2, '/modules/Planner/planner.php') and $_SESSION[$guid]['username'] != '') {
        try {
            $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'date' => $date, 'gibbonPersonID' => $gibbonPersonID, 'date2' => $date, 'gibbonPersonID2' => $gibbonPersonID);
            $sql = "(SELECT gibbonPlannerEntry.gibbonPlannerEntryID, gibbonUnitID, gibbonHookID, gibbonPlannerEntry.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, timeStart, timeEnd, viewableStudents, viewableParents, homework, homeworkSubmission, homeworkCrowdAssess, role, date, summary, gibbonPlannerEntryStudentHomework.homeworkDueDateTime AS myHomeworkDueDateTime FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) LEFT JOIN gibbonPlannerEntryStudentHomework ON (gibbonPlannerEntryStudentHomework.gibbonPlannerEntryID=gibbonPlannerEntry.gibbonPlannerEntryID AND gibbonPlannerEntryStudentHomework.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND date=:date AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND NOT role='Student - Left' AND NOT role='Teacher - Left') UNION (SELECT gibbonPlannerEntry.gibbonPlannerEntryID, gibbonUnitID, gibbonHookID, gibbonPlannerEntry.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, timeStart, timeEnd, viewableStudents, viewableParents, homework, homeworkSubmission, homeworkCrowdAssess, role, date, summary, NULL AS myHomeworkDueDateTime FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonPlannerEntryGuest ON (gibbonPlannerEntryGuest.gibbonPlannerEntryID=gibbonPlannerEntry.gibbonPlannerEntryID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE date=:date2 AND gibbonPlannerEntryGuest.gibbonPersonID=:gibbonPersonID2) ORDER BY date, timeStart";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $plannerOutput .= "<div class='error'>".$e->getMessage().'</div>';
        }
        if ($result->rowCount() > 0) {
            $classes = true;
            $plannerOutput .= "<table cellspacing='0' style='margin: 3px 0px; width: 100%'>";
            $plannerOutput .= "<tr class='head'>";
            $plannerOutput .= '<th>';
            $plannerOutput .= __($guid, 'Class').'<br/>';
            $plannerOutput .= '</th>';
            $plannerOutput .= '<th>';
            $plannerOutput .= __($guid, 'Lesson').'<br/>';
            $plannerOutput .= "<span style='font-size: 85%; font-weight: normal; font-style: italic'>".__($guid, 'Summary').'</span>';
            $plannerOutput .= '</th>';
            $plannerOutput .= '<th>';
            $plannerOutput .= __($guid, 'Homework');
            $plannerOutput .= '</th>';
            $plannerOutput .= '<th>';
            $plannerOutput .= __($guid, 'Like');
            $plannerOutput .= '</th>';
            $plannerOutput .= '<th>';
            $plannerOutput .= __($guid, 'Action');
            $plannerOutput .= '</th>';
            $plannerOutput .= '</tr>';

            $count2 = 0;
            $rowNum = 'odd';
            while ($row = $result->fetch()) {
                if ($count2 % 2 == 0) {
                    $rowNum = 'even';
                } else {
                    $rowNum = 'odd';
                }
                ++$count2;

                //Highlight class in progress
                if ((date('H:i:s') > $row['timeStart']) and (date('H:i:s') < $row['timeEnd']) and ($date) == date('Y-m-d')) {
                    $rowNum = 'current';
                }

                //COLOR ROW BY STATUS!
                $plannerOutput .= "<tr class=$rowNum>";
                $plannerOutput .= '<td>';
                $plannerOutput .= '<b>'.$row['course'].'.'.$row['class'].'</b><br/>';
                $plannerOutput .= '</td>';
                $plannerOutput .= '<td>';
                $plannerOutput .= $row['name'].'<br/>';
                $unit = getUnit($connection2, $row['gibbonUnitID'], $row['gibbonHookID'], $row['gibbonCourseClassID']);
                if (isset($unit[0])) {
                    $plannerOutput .= $unit[0];
                    if ($unit[1] != '') {
                        $plannerOutput .= '<br/><i>'.$unit[1].' '.__($guid, 'Unit').'</i><br/>';
                    }
                }
                $plannerOutput .= "<span style='font-size: 85%; font-weight: normal; font-style: italic'>";
                $plannerOutput .= $row['summary'];
                $plannerOutput .= '</span>';
                $plannerOutput .= '</td>';
                $plannerOutput .= '<td>';
                if ($row['homework'] == 'N' and $row['myHomeworkDueDateTime'] == '') {
                    $plannerOutput .= __($guid, 'No');
                } else {
                    if ($row['homework'] == 'Y') {
                        $plannerOutput .= __($guid, 'Yes').': '.__($guid, 'Teacher Recorded').'<br/>';
                        if ($row['homeworkSubmission'] == 'Y') {
                            $plannerOutput .= "<span style='font-size: 85%; font-style: italic'>+".__($guid, 'Submission').'</span><br/>';
                            if ($row['homeworkCrowdAssess'] == 'Y') {
                                $plannerOutput .= "<span style='font-size: 85%; font-style: italic'>+".__($guid, 'Crowd Assessment').'</span><br/>';
                            }
                        }
                    }
                    if ($row['myHomeworkDueDateTime'] != '') {
                        $plannerOutput .= __($guid, 'Yes').': '.__($guid, 'Student Recorded').'</br>';
                    }
                }
                $plannerOutput .= '</td>';
                $plannerOutput .= '<td>';
                $likesGiven = countLikesByContextAndGiver($connection2, 'Planner', 'gibbonPlannerEntryID', $row['gibbonPlannerEntryID'], $_SESSION[$guid]['gibbonPersonID']);
                if ($likesGiven != 1) {
                    $plannerOutput .= "<a href='".$_SESSION[$guid]['absoluteURL'].'/modules/Planner/plannerProcess.php?gibbonPlannerEntryID='.$row['gibbonPlannerEntryID']."&address=/modules/Planner/planner.php&viewBy=date&date=$date&gibbonPersonID=".$gibbonPersonID."&returnToIndex=Y'><img src='".$_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName']."/img/like_off.png'></a>";
                } else {
                    $plannerOutput .= "<a href='".$_SESSION[$guid]['absoluteURL'].'/modules/Planner/plannerProcess.php?gibbonPlannerEntryID='.$row['gibbonPlannerEntryID']."&address=/modules/Planner/planner.php&viewBy=date&date=$date&gibbonPersonID=".$gibbonPersonID."&returnToIndex=Y'><img src='".$_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName']."/img/like_on.png'></a>";
                }
                $plannerOutput .= '</td>';
                $plannerOutput .= '<td>';
                $plannerOutput .= "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Planner/planner_view_full.php&search='.$gibbonPersonID.'&viewBy=date&gibbonPlannerEntryID='.$row['gibbonPlannerEntryID']."&date=$date&width=1000&height=550'><img title='".__($guid, 'View')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/plus.png'/></a> ";
                $plannerOutput .= '</td>';
                $plannerOutput .= '</tr>';
            }
            $plannerOutput .= '</table>';
        }
    }
    if ($classes == false) {
        $plannerOutput .= "<div style='margin-top: 2px' class='warning'>";
        $plannerOutput .= __($guid, 'There are no records to display.');
        $plannerOutput .= '</div>';
    }

    //PREPARE RECENT GRADES
    $gradesOutput = "<div style='margin-top: 20px'><span style='font-size: 85%; font-weight: bold'>".__($guid, 'Recent Grades')."</span> . <span style='font-size: 70%'><a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Markbook/markbook_view.php&search='.$gibbonPersonID."'>".__($guid, 'View Markbook').'</a></span></div>';
    $grades = false;

    //Get settings
    $enableEffort = getSettingByScope($connection2, 'Markbook', 'enableEffort');
    $enableRubrics = getSettingByScope($connection2, 'Markbook', 'enableRubrics');
    $attainmentAlternativeName = getSettingByScope($connection2, 'Markbook', 'attainmentAlternativeName');
    $attainmentAlternativeNameAbrev = getSettingByScope($connection2, 'Markbook', 'attainmentAlternativeNameAbrev');
    $effortAlternativeName = getSettingByScope($connection2, 'Markbook', 'effortAlternativeName');
    $effortAlternativeNameAbrev = getSettingByScope($connection2, 'Markbook', 'effortAlternativeNameAbrev');

    try {
        $dataEntry = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $gibbonPersonID);
        $sqlEntry = "SELECT *, gibbonMarkbookColumn.comment AS commentOn, gibbonMarkbookColumn.uploadedResponse AS uploadedResponseOn, gibbonMarkbookEntry.comment AS comment FROM gibbonMarkbookEntry JOIN gibbonMarkbookColumn ON (gibbonMarkbookEntry.gibbonMarkbookColumnID=gibbonMarkbookColumn.gibbonMarkbookColumnID) JOIN gibbonCourseClass ON (gibbonMarkbookColumn.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonIDStudent=:gibbonPersonID AND complete='Y' AND completeDate<='".date('Y-m-d')."' AND viewableParents='Y' ORDER BY completeDate DESC LIMIT 0, 3";
        $resultEntry = $connection2->prepare($sqlEntry);
        $resultEntry->execute($dataEntry);
    } catch (PDOException $e) {
        $gradesOutput .= "<div class='error'>".$e->getMessage().'</div>';
    }
    if ($resultEntry->rowCount() > 0) {
        $showParentAttainmentWarning = getSettingByScope($connection2, 'Markbook', 'showParentAttainmentWarning');
        $showParentEffortWarning = getSettingByScope($connection2, 'Markbook', 'showParentEffortWarning');
        $grades = true;
        $gradesOutput .= "<table cellspacing='0' style='margin: 3px 0px; width: 100%'>";
        $gradesOutput .= "<tr class='head'>";
        $gradesOutput .= "<th style='width: 120px'>";
        $gradesOutput .= __($guid, 'Assessment');
        $gradesOutput .= '</th>';
        $gradesOutput .= "<th style='width: 75px'>";
        if ($attainmentAlternativeName != '') {
            $gradesOutput .= $attainmentAlternativeName;
        } else {
            $gradesOutput .= __($guid, 'Attainment');
        }
        $gradesOutput .= '</th>';
        if ($enableEffort == 'Y') {
            $gradesOutput .= "<th style='width: 75px'>";
            if ($effortAlternativeName != '') {
                $gradesOutput .= $effortAlternativeName;
            } else {
                $gradesOutput .= __($guid, 'Effort');
            }
        }
        $gradesOutput .= '</th>';
        $gradesOutput .= '<th>';
        $gradesOutput .= __($guid, 'Comment');
        $gradesOutput .= '</th>';
        $gradesOutput .= "<th style='width: 75px'>";
        $gradesOutput .= __($guid, 'Submission');
        $gradesOutput .= '</th>';
        $gradesOutput .= '</tr>';

        $count3 = 0;
        while ($rowEntry = $resultEntry->fetch()) {
            if ($count3 % 2 == 0) {
                $rowNum = 'even';
            } else {
                $rowNum = 'odd';
            }
            ++$count3;

            $gradesOutput .= "<a name='".$rowEntry['gibbonMarkbookEntryID']."'></a>";

            $gradesOutput .= "<tr class=$rowNum>";
            $gradesOutput .= '<td>';
            $gradesOutput .= "<span title='".htmlPrep($rowEntry['description'])."'>".$rowEntry['name'].'</span><br/>';
            $gradesOutput .= "<span style='font-size: 90%; font-style: italic; font-weight: normal'>";
            $gradesOutput .= __($guid, 'Marked on').' '.dateConvertBack($guid, $rowEntry['completeDate']).'<br/>';
            $gradesOutput .= '</span>';
            $gradesOutput .= '</td>';
            if ($rowEntry['attainment'] == 'N' or ($rowEntry['gibbonScaleIDAttainment'] == '' and $rowEntry['gibbonRubricIDAttainment'] == '')) {
                $gradesOutput .= "<td class='dull' style='color: #bbb; text-align: center'>";
                $gradesOutput .= __($guid, 'N/A');
                $gradesOutput .= '</td>';
            } else {
                $gradesOutput .= "<td style='text-align: center'>";
                $attainmentExtra = '';
                try {
                    $dataAttainment = array('gibbonScaleID' => $rowEntry['gibbonScaleIDAttainment']);
                    $sqlAttainment = 'SELECT * FROM gibbonScale WHERE gibbonScaleID=:gibbonScaleID';
                    $resultAttainment = $connection2->prepare($sqlAttainment);
                    $resultAttainment->execute($dataAttainment);
                } catch (PDOException $e) {
                }
                if ($resultAttainment->rowCount() == 1) {
                    $rowAttainment = $resultAttainment->fetch();
                    $attainmentExtra = '<br/>'.__($guid, $rowAttainment['usage']);
                }
                $styleAttainment = "style='font-weight: bold'";
                if ($rowEntry['attainmentConcern'] == 'Y' and $showParentAttainmentWarning == 'Y') {
                    $styleAttainment = "style='color: #".$alert['color'].'; font-weight: bold; border: 2px solid #'.$alert['color'].'; padding: 2px 4px; background-color: #'.$alert['colorBG']."'";
                } elseif ($rowEntry['attainmentConcern'] == 'P' and $showParentAttainmentWarning == 'Y') {
                    $styleAttainment = "style='color: #390; font-weight: bold; border: 2px solid #390; padding: 2px 4px; background-color: #D4F6DC'";
                }
                $gradesOutput .= "<div $styleAttainment>".$rowEntry['attainmentValue'];
                if ($rowEntry['gibbonRubricIDAttainment'] != '' AND $enableRubrics =='Y') {
                    $gradesOutput .= "<a class='thickbox' href='".$_SESSION[$guid]['absoluteURL'].'/fullscreen.php?q=/modules/Markbook/markbook_view_rubric.php&gibbonRubricID='.$rowEntry['gibbonRubricIDAttainment'].'&gibbonCourseClassID='.$rowEntry['gibbonCourseClassID'].'&gibbonMarkbookColumnID='.$rowEntry['gibbonMarkbookColumnID'].'&gibbonPersonID='.$gibbonPersonID."&mark=FALSE&type=attainment&width=1100&height=550'><img style='margin-bottom: -3px; margin-left: 3px' title='View Rubric' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/rubric.png'/></a>";
                }
                $gradesOutput .= '</div>';
                if ($rowEntry['attainmentValue'] != '') {
                    $gradesOutput .= "<div class='detailItem' style='font-size: 75%; font-style: italic; margin-top: 2px'><b>".htmlPrep(__($guid, $rowEntry['attainmentDescriptor'])).'</b>'.__($guid, $attainmentExtra).'</div>';
                }
                $gradesOutput .= '</td>';
            }
            if ($enableEffort == 'Y') {
                if ($rowEntry['effort'] == 'N' or ($rowEntry['gibbonScaleIDEffort'] == '' and $rowEntry['gibbonRubricIDEffort'] == '')) {
                    $gradesOutput .= "<td class='dull' style='color: #bbb; text-align: center'>";
                    $gradesOutput .= __($guid, 'N/A');
                    $gradesOutput .= '</td>';
                } else {
                    $gradesOutput .= "<td style='text-align: center'>";
                    $effortExtra = '';
                    try {
                        $dataEffort = array('gibbonScaleID' => $rowEntry['gibbonScaleIDEffort']);
                        $sqlEffort = 'SELECT * FROM gibbonScale WHERE gibbonScaleID=:gibbonScaleID';
                        $resultEffort = $connection2->prepare($sqlEffort);
                        $resultEffort->execute($dataEffort);
                    } catch (PDOException $e) {
                    }
                    if ($resultEffort->rowCount() == 1) {
                        $rowEffort = $resultEffort->fetch();
                        $effortExtra = '<br/>'.__($guid, $rowEffort['usage']);
                    }
                    $styleEffort = "style='font-weight: bold'";
                    if ($rowEntry['effortConcern'] == 'Y' and $showParentEffortWarning == 'Y') {
                        $styleEffort = "style='color: #".$alert['color'].'; font-weight: bold; border: 2px solid #'.$alert['color'].'; padding: 2px 4px; background-color: #'.$alert['colorBG']."'";
                    }
                    $gradesOutput .= "<div $styleEffort>".$rowEntry['effortValue'];
                    if ($rowEntry['gibbonRubricIDEffort'] != '' AND $enableRubrics =='Y') {
                        $gradesOutput .= "<a class='thickbox' href='".$_SESSION[$guid]['absoluteURL'].'/fullscreen.php?q=/modules/Markbook/markbook_view_rubric.php&gibbonRubricID='.$rowEntry['gibbonRubricIDEffort'].'&gibbonCourseClassID='.$rowEntry['gibbonCourseClassID'].'&gibbonMarkbookColumnID='.$rowEntry['gibbonMarkbookColumnID'].'&gibbonPersonID='.$gibbonPersonID."&mark=FALSE&type=effort&width=1100&height=550'><img style='margin-bottom: -3px; margin-left: 3px' title='View Rubric' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/rubric.png'/></a>";
                    }
                    $gradesOutput .= '</div>';
                    if ($rowEntry['effortValue'] != '') {
                        $gradesOutput .= "<div class='detailItem' style='font-size: 75%; font-style: italic; margin-top: 2px'><b>".htmlPrep(__($guid, $rowEntry['effortDescriptor'])).'</b>'.__($guid, $effortExtra).'</div>';
                    }
                    $gradesOutput .= '</td>';
                }
            }
            if ($rowEntry['commentOn'] == 'N' and $rowEntry['uploadedResponseOn'] == 'N') {
                $gradesOutput .= "<td class='dull' style='color: #bbb; text-align: left'>";
                $gradesOutput .= __($guid, 'N/A');
                $gradesOutput .= '</td>';
            } else {
                $gradesOutput .= '<td>';
                if ($rowEntry['comment'] != '') {
                    if (strlen($rowEntry['comment']) > 50) {
                        $gradesOutput .= "<script type='text/javascript'>";
                        $gradesOutput .= '$(document).ready(function(){';
                        $gradesOutput .= "\$(\".comment-$entryCount-$gibbonPersonID\").hide();";
                        $gradesOutput .= "\$(\".show_hide-$entryCount-$gibbonPersonID\").fadeIn(1000);";
                        $gradesOutput .= "\$(\".show_hide-$entryCount-$gibbonPersonID\").click(function(){";
                        $gradesOutput .= "\$(\".comment-$entryCount-$gibbonPersonID\").fadeToggle(1000);";
                        $gradesOutput .= '});';
                        $gradesOutput .= '});';
                        $gradesOutput .= '</script>';
                        $gradesOutput .= '<span>'.substr($rowEntry['comment'], 0, 50).'...<br/>';
                        $gradesOutput .= "<a title='".__($guid, 'View Description')."' class='show_hide-$entryCount-$gibbonPersonID' onclick='return false;' href='#'>".__($guid, 'Read more').'</a></span><br/>';
                    } else {
                        $gradesOutput .= nl2br($rowEntry['comment']);
                    }
                    $gradesOutput .= '<br/>';
                }
                if ($rowEntry['response'] != '') {
                    $gradesOutput .= "<a title='".__($guid, 'Uploaded Response')."' href='".$_SESSION[$guid]['absoluteURL'].'/'.$rowEntry['response']."'>".__($guid, 'Uploaded Response').'</a><br/>';
                }
                $gradesOutput .= '</td>';
            }
            if ($rowEntry['gibbonPlannerEntryID'] == 0) {
                $gradesOutput .= "<td class='dull' style='color: #bbb; text-align: left'>";
                $gradesOutput .= __($guid, 'N/A');
                $gradesOutput .= '</td>';
            } else {
                try {
                    $dataSub = array('gibbonPlannerEntryID' => $rowEntry['gibbonPlannerEntryID']);
                    $sqlSub = "SELECT * FROM gibbonPlannerEntry WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND homeworkSubmission='Y'";
                    $resultSub = $connection2->prepare($sqlSub);
                    $resultSub->execute($dataSub);
                } catch (PDOException $e) {
                    $gradesOutput .= "<div class='error'>".$e->getMessage().'</div>';
                }
                if ($resultSub->rowCount() != 1) {
                    $gradesOutput .= "<td class='dull' style='color: #bbb; text-align: left'>";
                    $gradesOutput .= __($guid, 'N/A');
                    $gradesOutput .= '</td>';
                } else {
                    $gradesOutput .= '<td>';
                    $rowSub = $resultSub->fetch();

                    try {
                        $dataWork = array('gibbonPlannerEntryID' => $rowEntry['gibbonPlannerEntryID'], 'gibbonPersonID' => $gibbonPersonID);
                        $sqlWork = 'SELECT * FROM gibbonPlannerEntryHomework WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND gibbonPersonID=:gibbonPersonID ORDER BY count DESC';
                        $resultWork = $connection2->prepare($sqlWork);
                        $resultWork->execute($dataWork);
                    } catch (PDOException $e) {
                        $gradesOutput .= "<div class='error'>".$e->getMessage().'</div>';
                    }
                    if ($resultWork->rowCount() > 0) {
                        $rowWork = $resultWork->fetch();

                        if ($rowWork['status'] == 'Exemption') {
                            $linkText = __($guid, 'Exemption');
                        } elseif ($rowWork['version'] == 'Final') {
                            $linkText = __($guid, 'Final');
                        } else {
                            $linkText = __($guid, 'Draft').' '.$rowWork['count'];
                        }

                        $style = '';
                        $status = 'On Time';
                        if ($rowWork['status'] == 'Exemption') {
                            $status = __($guid, 'Exemption');
                        } elseif ($rowWork['status'] == 'Late') {
                            $style = "style='color: #ff0000; font-weight: bold; border: 2px solid #ff0000; padding: 2px 4px'";
                            $status = __($guid, 'Late');
                        }

                        if ($rowWork['type'] == 'File') {
                            $gradesOutput .= "<span title='".$rowWork['version'].". $status. ".sprintf(__($guid, 'Submitted at %1$s on %2$s'), substr($rowWork['timestamp'], 11, 5), dateConvertBack($guid, substr($rowWork['timestamp'], 0, 10)))."' $style><a href='".$_SESSION[$guid]['absoluteURL'].'/'.$rowWork['location']."'>$linkText</a></span>";
                        } elseif ($rowWork['type'] == 'Link') {
                            $gradesOutput .= "<span title='".$rowWork['version'].". $status. ".sprintf(__($guid, 'Submitted at %1$s on %2$s'), substr($rowWork['timestamp'], 11, 5), dateConvertBack($guid, substr($rowWork['timestamp'], 0, 10)))."' $style><a target='_blank' href='".$rowWork['location']."'>$linkText</a></span>";
                        } else {
                            $gradesOutput .= "<span title='$status. ".sprintf(__($guid, 'Recorded at %1$s on %2$s'), substr($rowWork['timestamp'], 11, 5), dateConvertBack($guid, substr($rowWork['timestamp'], 0, 10)))."' $style>$linkText</span>";
                        }
                    } else {
                        if (date('Y-m-d H:i:s') < $rowSub['homeworkDueDateTime']) {
                            $gradesOutput .= "<span title='Pending'>".__($guid, 'Pending').'</span>';
                        } else {
                            if ($row['dateStart'] > $rowSub['date']) {
                                $gradesOutput .= "<span title='".__($guid, 'Student joined school after assessment was given.')."' style='color: #000; font-weight: normal; border: 2px none #ff0000; padding: 2px 4px'>".__($guid, 'NA').'</span>';
                            } else {
                                if ($rowSub['homeworkSubmissionRequired'] == 'Compulsory') {
                                    $gradesOutput .= "<div style='color: #ff0000; font-weight: bold; border: 2px solid #ff0000; padding: 2px 4px; margin: 2px 0px'>".__($guid, 'Incomplete').'</div>';
                                } else {
                                    $gradesOutput .= __($guid, 'Not submitted online');
                                }
                            }
                        }
                    }
                    $gradesOutput .= '</td>';
                }
            }
            $gradesOutput .= '</tr>';
            if (strlen($rowEntry['comment']) > 50) {
                $gradesOutput .= "<tr class='comment-$entryCount-$gibbonPersonID' id='comment-$entryCount-$gibbonPersonID'>";
                $gradesOutput .= '<td colspan=6>';
                $gradesOutput .= nl2br($rowEntry['comment']);
                $gradesOutput .= '</td>';
                $gradesOutput .= '</tr>';
            }
            ++$entryCount;
        }

        $gradesOutput .= '</table>';
    }
    if ($grades == false) {
        $gradesOutput .= "<div style='margin-top: 2px' class='warning'>";
        $gradesOutput .= __($guid, 'There are no records to display.');
        $gradesOutput .= '</div>';
    }

    //PREPARE UPCOMING DEADLINES
    $deadlinesOutput = "<div style='margin-top: 20px'><span style='font-size: 85%; font-weight: bold'>".__($guid, 'Upcoming Deadlines')."</span> . <span style='font-size: 70%'><a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Planner/planner_deadlines.php&search='.$gibbonPersonID."'>".__($guid, 'View All Deadlines').'</a></span></div>';
    $deadlines = false;

    try {
        $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $gibbonPersonID);
        $sql = "
		(SELECT 'teacherRecorded' AS type, gibbonPlannerEntryID, gibbonUnitID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, date, timeStart, timeEnd, viewableStudents, viewableParents, homework, homeworkDueDateTime, role FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND NOT role='Student - Left' AND NOT role='Teacher - Left' AND homework='Y' AND (role='Teacher' OR (role='Student' AND viewableStudents='Y')) AND homeworkDueDateTime>'".date('Y-m-d H:i:s')."' AND ((date<'".date('Y-m-d')."') OR (date='".date('Y-m-d')."' AND timeEnd<='".date('H:i:s')."')))
		UNION
		(SELECT 'studentRecorded' AS type, gibbonPlannerEntry.gibbonPlannerEntryID, gibbonUnitID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, date, timeStart, timeEnd, 'Y' AS viewableStudents, 'Y' AS viewableParents, 'Y' AS homework, gibbonPlannerEntryStudentHomework.homeworkDueDateTime, role FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) JOIN gibbonPlannerEntryStudentHomework ON (gibbonPlannerEntryStudentHomework.gibbonPlannerEntryID=gibbonPlannerEntry.gibbonPlannerEntryID AND gibbonPlannerEntryStudentHomework.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND NOT role='Student - Left' AND NOT role='Teacher - Left' AND (role='Teacher' OR (role='Student' AND viewableStudents='Y')) AND gibbonPlannerEntryStudentHomework.homeworkDueDateTime>'".date('Y-m-d H:i:s')."' AND ((date<'".date('Y-m-d')."') OR (date='".date('Y-m-d')."' AND timeEnd<='".date('H:i:s')."')))
		ORDER BY homeworkDueDateTime, type";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        $deadlinesOutput .= "<div class='error'>".$e->getMessage().'</div>';
    }

    if ($result->rowCount() > 0) {
        $deadlines = true;
        $deadlinesOutput .= "<ol style='margin-left: 15px'>";
        while ($row = $result->fetch()) {
            $diff = (strtotime(substr($row['homeworkDueDateTime'], 0, 10)) - strtotime(date('Y-m-d'))) / 86400;
            $style = "style='padding-right: 3px;'";
            if ($diff < 2) {
                $style = "style='padding-right: 3px; border-right: 10px solid #cc0000'";
            } elseif ($diff < 4) {
                $style = "style='padding-right: 3px; border-right: 10px solid #D87718'";
            }
            $deadlinesOutput .= "<li $style>";
            $deadlinesOutput .= "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Planner/planner_view_full.php&search='.$gibbonPersonID.'&gibbonPlannerEntryID='.$row['gibbonPlannerEntryID']."&viewBy=date&date=$date&width=1000&height=550'>".$row['course'].'.'.$row['class'].'</a> ';
            $deadlinesOutput .= "<span style='font-style: italic'>".sprintf(__($guid, 'Due at %1$s on %2$s'), substr($row['homeworkDueDateTime'], 11, 5), dateConvertBack($guid, substr($row['homeworkDueDateTime'], 0, 10)));
            $deadlinesOutput .= '</li>';
        }
        $deadlinesOutput .= '</ol>';
    }

    if ($deadlines == false) {
        $deadlinesOutput .= "<div style='margin-top: 2px' class='warning'>";
        $deadlinesOutput .= __($guid, 'There are no records to display.');
        $deadlinesOutput .= '</div>';
    }

    //PREPARE TIMETABLE
    $timetable = false;
    $timetableOutput = '';
    if (isActionAccessible($guid, $connection2, '/modules/Timetable/tt_view.php')) {
        $date = date('Y-m-d');
        if (isset($_POST['ttDate'])) {
            $date = dateConvert($guid, $_POST['ttDate']);
        }
        $params = '';
        if ($classes != false or $grades != false or $deadlines != false) {
            $params = '&tab=1';
        }
        $timetableOutputTemp = renderTT($guid, $connection2, $gibbonPersonID, null, null, dateConvertToTimestamp($date), '', $params, 'narrow');
        if ($timetableOutputTemp != false) {
            $timetable = true;
            $timetableOutput .= $timetableOutputTemp;
        }
    }

    //PREPARE ACTIVITIES
    $activities = false;
    $activitiesOutput = false;
    if (!(isActionAccessible($guid, $connection2, '/modules/Activities/activities_view.php'))) {
        $activitiesOutput .= "<div class='error'>";
        $activitiesOutput .= __($guid, 'Your request failed because you do not have access to this action.');
        $activitiesOutput .= '</div>';
    } else {
        $activities = true;

        $activitiesOutput .= "<div class='linkTop'>";
        $activitiesOutput .= "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Activities/activities_view.php'>".__($guid, 'View Available Activities').'</a>';
        $activitiesOutput .= '</div>';

        $dateType = getSettingByScope($connection2, 'Activities', 'dateType');
        if ($dateType == 'Term') {
            $maxPerTerm = getSettingByScope($connection2, 'Activities', 'maxPerTerm');
        }
        try {
            $dataYears = array('gibbonPersonID' => $gibbonPersonID);
            $sqlYears = "SELECT * FROM gibbonStudentEnrolment JOIN gibbonSchoolYear ON (gibbonStudentEnrolment.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) WHERE gibbonSchoolYear.status='Current' AND gibbonPersonID=:gibbonPersonID ORDER BY sequenceNumber DESC";
            $resultYears = $connection2->prepare($sqlYears);
            $resultYears->execute($dataYears);
        } catch (PDOException $e) {
            $activitiesOutput .= "<div class='error'>".$e->getMessage().'</div>';
        }

        if ($resultYears->rowCount() < 1) {
            $activitiesOutput .= "<div class='error'>";
            $activitiesOutput .= __($guid, 'There are no records to display.');
            $activitiesOutput .= '</div>';
        } else {
            $yearCount = 0;
            while ($rowYears = $resultYears->fetch()) {
                ++$yearCount;
                try {
                    $data = array('gibbonPersonID' => $gibbonPersonID, 'gibbonSchoolYearID' => $rowYears['gibbonSchoolYearID']);
                    $sql = "SELECT gibbonActivity.*, gibbonActivityStudent.status, NULL AS role FROM gibbonActivity JOIN gibbonActivityStudent ON (gibbonActivity.gibbonActivityID=gibbonActivityStudent.gibbonActivityID) WHERE gibbonActivityStudent.gibbonPersonID=:gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID AND active='Y' ORDER BY name";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    $activitiesOutput .= "<div class='error'>".$e->getMessage().'</div>';
                }

                if ($result->rowCount() < 1) {
                    $activitiesOutput .= "<div class='error'>";
                    $activitiesOutput .= __($guid, 'There are no records to display.');
                    $activitiesOutput .= '</div>';
                } else {
                    $activitiesOutput .= "<table cellspacing='0' style='width: 100%'>";
                    $activitiesOutput .= "<tr class='head'>";
                    $activitiesOutput .= '<th>';
                    $activitiesOutput .= __($guid, 'Activity');
                    $activitiesOutput .= '</th>';
                    $options = getSettingByScope($connection2, 'Activities', 'activityTypes');
                    if ($options != '') {
                        $activitiesOutput .= '<th>';
                        $activitiesOutput .= __($guid, 'Type');
                        $activitiesOutput .= '</th>';
                    }
                    $activitiesOutput .= '<th>';
                    if ($dateType != 'Date') {
                        $activitiesOutput .= __($guid, 'Term');
                    } else {
                        $activitiesOutput .= __($guid, 'Dates');
                    }
                    $activitiesOutput .= '</th>';
                    $activitiesOutput .= '<th>';
                    $activitiesOutput .= __($guid, 'Status');
                    $activitiesOutput .= '</th>';
                    $activitiesOutput .= '</tr>';

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
                            $activitiesOutput .= "<tr class=$rowNum>";
                        $activitiesOutput .= '<td>';
                        $activitiesOutput .= $row['name'];
                        $activitiesOutput .= '</td>';
                        if ($options != '') {
                            $activitiesOutput .= '<td>';
                            $activitiesOutput .= trim($row['type']);
                            $activitiesOutput .= '</td>';
                        }
                        $activitiesOutput .= '<td>';
                        if ($dateType != 'Date') {
                            $terms = getTerms($connection2, $_SESSION[$guid]['gibbonSchoolYearID'], true);
                            $termList = '';
                            for ($i = 0; $i < count($terms); $i = $i + 2) {
                                if (is_numeric(strpos($row['gibbonSchoolYearTermIDList'], $terms[$i]))) {
                                    $termList .= $terms[($i + 1)].'<br/>';
                                }
                            }
                            $activitiesOutput .= $termList;
                        } else {
                            if (substr($row['programStart'], 0, 4) == substr($row['programEnd'], 0, 4)) {
                                if (substr($row['programStart'], 5, 2) == substr($row['programEnd'], 5, 2)) {
                                    $activitiesOutput .= date('F', mktime(0, 0, 0, substr($row['programStart'], 5, 2))).' '.substr($row['programStart'], 0, 4);
                                } else {
                                    $activitiesOutput .= date('F', mktime(0, 0, 0, substr($row['programStart'], 5, 2))).' - '.date('F', mktime(0, 0, 0, substr($row['programEnd'], 5, 2))).'<br/>'.substr($row['programStart'], 0, 4);
                                }
                            } else {
                                $activitiesOutput .= date('F', mktime(0, 0, 0, substr($row['programStart'], 5, 2))).' '.substr($row['programStart'], 0, 4).' -<br/>'.date('F', mktime(0, 0, 0, substr($row['programEnd'], 5, 2))).' '.substr($row['programEnd'], 0, 4);
                            }
                        }
                        $activitiesOutput .= '</td>';
                        $activitiesOutput .= '<td>';
                        if ($row['status'] != '') {
                            $activitiesOutput .= $row['status'];
                        } else {
                            $activitiesOutput .= '<i>'.__($guid, 'NA').'</i>';
                        }
                        $activitiesOutput .= '</td>';
                        $activitiesOutput .= '</tr>';
                    }
                    $activitiesOutput .= '</table>';
                }
            }
        }
    }

    //GET HOOKS INTO DASHBOARD
    $hooks = array();
    try {
        $dataHooks = array();
        $sqlHooks = "SELECT * FROM gibbonHook WHERE type='Parental Dashboard'";
        $resultHooks = $connection2->prepare($sqlHooks);
        $resultHooks->execute($dataHooks);
    } catch (PDOException $e) {
        $return .= "<div class='error'>".$e->getMessage().'</div>';
    }
    if ($resultHooks->rowCount() > 0) {
        $count = 0;
        while ($rowHooks = $resultHooks->fetch()) {
            $options = unserialize($rowHooks['options']);
            //Check for permission to hook
            try {
                $dataHook = array('gibbonRoleIDCurrent' => $_SESSION[$guid]['gibbonRoleIDCurrent'], 'sourceModuleName' => $options['sourceModuleName']);
                $sqlHook = "SELECT gibbonHook.name, gibbonModule.name AS module, gibbonAction.name AS action FROM gibbonHook JOIN gibbonModule ON (gibbonHook.gibbonModuleID=gibbonModule.gibbonModuleID) JOIN gibbonAction ON (gibbonAction.gibbonModuleID=gibbonModule.gibbonModuleID) JOIN gibbonPermission ON (gibbonPermission.gibbonActionID=gibbonAction.gibbonActionID) WHERE gibbonAction.gibbonModuleID=(SELECT gibbonModuleID FROM gibbonModule WHERE gibbonPermission.gibbonRoleID=:gibbonRoleIDCurrent AND name=:sourceModuleName) AND gibbonHook.type='Parental Dashboard'  AND gibbonAction.name='".$options['sourceModuleAction']."' AND gibbonModule.name='".$options['sourceModuleName']."' ORDER BY name";
                $resultHook = $connection2->prepare($sqlHook);
                $resultHook->execute($dataHook);
            } catch (PDOException $e) {
            }
            if ($resultHook->rowCount() == 1) {
                $rowHook = $resultHook->fetch();
                $hooks[$count]['name'] = $rowHooks['name'];
                $hooks[$count]['sourceModuleName'] = $rowHook['module'];
                $hooks[$count]['sourceModuleInclude'] = $options['sourceModuleInclude'];
                ++$count;
            }
        }
    }

    if ($classes == false and $grades == false and $deadlines == false and $timetable == false and $activities == false and count($hooks) < 1) {
        $return .= "<div class='warning'>";
        $return .= __($guid, 'There are no records to display.');
        $return .= '</div>';
    } else {
        $parentDashboardDefaultTab = getSettingByScope($connection2, 'School Admin', 'parentDashboardDefaultTab');
        $parentDashboardDefaultTabCount = null;

        $return .= "<div id='".$gibbonPersonID."tabs' style='margin: 0 0'>";
        $return .= '<ul>';
        $tabCountExtraReset = 0;
        if ($classes != false or $grades != false or $deadlines != false) {
            $return .= "<li><a href='#tabs".$tabCountExtraReset."'>".__($guid, 'Learning Overview').'</a></li>';
            $tabCountExtraReset++;
            if ($parentDashboardDefaultTab == 'Planner')
                $parentDashboardDefaultTabCount = $tabCountExtraReset;
        }
        if ($timetable != false) {
            $return .= "<li><a href='#tabs".$tabCountExtraReset."'>".__($guid, 'Timetable').'</a></li>';
            $tabCountExtraReset++;
            if ($parentDashboardDefaultTab == 'Timetable')
                $parentDashboardDefaultTabCount = $tabCountExtraReset;
        }
        if ($activities != false) {
            $return .= "<li><a href='#tabs".$tabCountExtraReset."'>".__($guid, 'Activities').'</a></li>';
            $tabCountExtraReset++;
            if ($parentDashboardDefaultTab == 'Activities')
                $parentDashboardDefaultTabCount = $tabCountExtraReset;
        }
        $tabCountExtra = $tabCountExtraReset;
        foreach ($hooks as $hook) {
            ++$tabCountExtra;
            $return .= "<li><a href='#tabs".$tabCountExtra."'>".__($guid, $hook['name']).'</a></li>';
        }
        $return .= '</ul>';

        $tabCountExtraReset = 0;
        if ($classes != false or $grades != false or $deadlines != false) {
            $return .= "<div id='tabs".$tabCountExtraReset."'>";
            $return .= $plannerOutput;
            $return .= $gradesOutput;
            $return .= $deadlinesOutput;
            $return .= '</div>';
            $tabCountExtraReset++;
        }
        if ($timetable != false) {
            $return .= "<div id='tabs".$tabCountExtraReset."'>";
            $return .= $timetableOutput;
            $return .= '</div>';
            $tabCountExtraReset++;
        }
        if ($activities != false) {
            $return .= "<div id='tabs".$tabCountExtraReset."'>";
            $return .= $activitiesOutput;
            $return .= '</div>';
            $tabCountExtraReset++;
        }
        $tabCountExtra = $tabCountExtraReset;
        foreach ($hooks as $hook) {
            if ($parentDashboardDefaultTab == $hook['name'])
                $parentDashboardDefaultTabCount = $tabCountExtra+1;
            ++$tabCountExtra;
            $return .= "<div style='min-height: 100px' id='tabs".$tabCountExtra."'>";
            $include = $_SESSION[$guid]['absolutePath'].'/modules/'.$hook['sourceModuleName'].'/'.$hook['sourceModuleInclude'];
            if (!file_exists($include)) {
                $return .= "<div class='error'>";
                $return .= __($guid, 'The selected page cannot be displayed due to a hook error.');
                $return .= '</div>';
            } else {
                $return .= include $include;
            }
            $return .= '</div>';
        }
        $return .= '</div>';
    }


    $defaultTab = 0;
    if (isset($_GET['tab'])) {
        $defaultTab = $_GET['tab'];
    }
    else if (!is_null($parentDashboardDefaultTabCount)) {
        $defaultTab = $parentDashboardDefaultTabCount-1;
    }
    $return .= "<script type='text/javascript'>";
    $return .= '$(function() {';
    $return .= '$( "#'.$gibbonPersonID.'tabs" ).tabs({';
    $return .= 'active: '.$defaultTab.',';
    $return .= 'ajaxOptions: {';
    $return .= 'error: function( xhr, status, index, anchor ) {';
    $return .= '$( anchor.hash ).html(';
    $return .= "\"Couldn't load this tab.\" );";
    $return .= '}';
    $return .= '}';
    $return .= '});';
    $return .= '});';
    $return .= '</script>';

    return $return;
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

//Sets a system-wide notification
function setNotification($connection2, $guid, $gibbonPersonID, $text, $moduleName, $actionLink)
{
    if ($moduleName == '') {
        $moduleName = null;
    }

    //Check for existence of notification in new status
    $dataCheck = array('gibbonPersonID' => $gibbonPersonID, 'text' => $text, 'actionLink' => $actionLink, 'name' => $moduleName);
    $sqlCheck = "SELECT * FROM gibbonNotification WHERE gibbonPersonID=:gibbonPersonID AND text=:text AND actionLink=:actionLink AND gibbonModuleID=(SELECT gibbonModuleID FROM gibbonModule WHERE name=:name) AND status='New'";
    $resultCheck = $connection2->prepare($sqlCheck);
    $resultCheck->execute($dataCheck);

    if ($resultCheck->rowCount() == 1) { //If exists, increment count
        $rowCheck = $resultCheck->fetch();
        $dataInsert = array('count' => ($rowCheck['count'] + 1), 'gibbonPersonID' => $gibbonPersonID, 'text' => $text, 'name' => $moduleName);
        $sqlInsert = "UPDATE gibbonNotification SET count=:count WHERE gibbonPersonID=:gibbonPersonID AND text=:text AND gibbonModuleID=(SELECT gibbonModuleID FROM gibbonModule WHERE name=:name) AND status='New'";
        $resultInsert = $connection2->prepare($sqlInsert);
        $resultInsert->execute($dataInsert);
    } else { //If not exists, create
        $dataInsert = array('gibbonPersonID' => $gibbonPersonID, 'name' => $moduleName, 'text' => $text, 'actionLink' => $actionLink);
        $sqlInsert = 'INSERT INTO gibbonNotification SET gibbonPersonID=:gibbonPersonID, gibbonModuleID=(SELECT gibbonModuleID FROM gibbonModule WHERE name=:name), text=:text, actionLink=:actionLink, timestamp=now()';
        $resultInsert = $connection2->prepare($sqlInsert);
        $resultInsert->execute($dataInsert);
    }

    //Check for email notification preference and email address, and send if required
    $dataSelect = array('gibbonPersonID' => $gibbonPersonID);
    $sqlSelect = "SELECT email, receiveNotificationEmails FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonID AND receiveNotificationEmails='Y' AND NOT email=''";
    $resultSelect = $connection2->prepare($sqlSelect);
    $resultSelect->execute($dataSelect);
    if ($resultSelect->rowCount() == 1) {
        $rowSelect = $resultSelect->fetch();

        //Include mailer
        $included = false;
        $includes = get_included_files();
        foreach ($includes as $include) {
            if (strpos(str_replace('\\', '/', $include), '/lib/PHPMailer/PHPMailerAutoload.php') !== false) {
                $included = true;
            }
        }
        if ($included == false) {
            require $_SESSION[$guid]['absolutePath'].'/lib/PHPMailer/PHPMailerAutoload.php';
        }

        //Attempt email send
        $subject = sprintf(__($guid, 'You have received a notification on %1$s at %2$s (%3$s %4$s)'), $_SESSION[$guid]['systemName'], $_SESSION[$guid]['organisationNameShort'], date('H:i'), dateConvertBack($guid, date('Y-m-d')));
        $body = __($guid, 'Notification').': '.$text.'<br/><br/>';
        $body .= sprintf(__($guid, 'Login to %1$s and use the notification icon to check your new notification, or %2$sclick here%3$s.'), $_SESSION[$guid]['systemName'], "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=notifications.php'>", '</a>');
        $body .= '<br/><br/>';
        $body .= '<hr/>';
        $body .= "<p style='font-style: italic; font-size: 85%'>";
        $body .= sprintf(__($guid, 'If you do not wish to receive email notifications from %1$s, please %2$sclick here%3$s to adjust your preferences:'), $_SESSION[$guid]['systemName'], "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=preferences.php'>", '</a>');
        $body .= '<br/><br/>';
        $body .= sprintf(__($guid, 'Email sent via %1$s at %2$s.'), $_SESSION[$guid]['systemName'], $_SESSION[$guid]['organisationName']);
        $body .= '</p>';
        $bodyPlain = emailBodyConvert($body);

        $mail = new PHPMailer();
        $mail->IsSMTP();
        $mail->SetFrom($_SESSION[$guid]['organisationAdministratorEmail'], $_SESSION[$guid]['organisationName']);
        $mail->AddAddress($rowSelect['email']);
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';
        $mail->IsHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->AltBody = $bodyPlain;
        $mail->Send();
    }
}

//Expands Y and N to Yes and No, with and without translation
function ynExpander($guid, $yn, $translation = true)
{
    $output = '';

    if ($yn == 'Y' or $yn == 'y') {
        $output = 'Yes';
    } elseif ($yn == 'N' or $yn == 'n') {
        $output = 'No';
    } else {
        $output = 'NA';
    }

    if ($translation == true) {
        $output = __($guid, $output);
    }

    return $output;
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
            $output .= "<span style='font-size: 175%'><i><b>".__($guid, 'Smart Workflow').'</b></i> '.__($guid, 'Getting Started').'</span><br/>';
            $output .= __($guid, "Designed and built by teachers, Gibbon's Smart Workflow takes care of the boring stuff, so you can get on with teaching.").'<br/>';
            $output .= '</div>';
            $output .= "<table cellspacing='0' style='width: 100%; margin: 10px 0px; border-spacing: 4px;'>";
            $output .= '<tr>';
            if ($step == 1) {
                $output .= "<td style='width: 20%; border-top: 3px solid #fff; border-bottom: 2px solid rgba(255,255,255,0.0); background-color: rgba(255,255,255,0.25); padding: 4px'>";
                $output .= "<span style='color: #c00; font-size: 270%; font-weight: bold; letter-spacing: 70%'>".__($guid, 'One').'</span><br/>';
                $output .= "<span style='color: #c00; font-size: 140%; letter-spacing: 70%'>".sprintf(__($guid, 'Create %1$s Outcomes'), '<br/>').'</span><br/></span>';
                $output .= '</td>';
            } else {
                $output .= "<td style='width: 20%; border-top: 3px solid #fff; border-bottom: 2px solid #fff; background-color: rgba(255,255,255,0.25); padding: 4px'>";
                $output .= "<span style='font-size: 270%; font-weight: bold; letter-spacing: 70%'>".__($guid, 'One').'</span><br/>';
                $output .= "<span style='font-size: 140%; letter-spacing: 70%'><a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Planner/outcomes.php'>".sprintf(__($guid, 'Create %1$s Outcomes'), '<br/>').'</span><br/></a>';
                $output .= '</td>';
            }
            if ($step == 2) {
                $output .= "<td style='width: 20%; border-top: 3px solid #fff; border-bottom: 2px solid rgba(255,255,255,0.0); background-color: rgba(255,255,255,0.25); padding: 4px'>";
                $output .= "<span style='color: #c00; font-size: 270%; font-weight: bold; letter-spacing: 70%'>".__($guid, 'Two').'</span><br/>';
                $output .= "<span style='color: #c00; font-size: 140%; letter-spacing: 70%'>".sprintf(__($guid, 'Plan & Deploy %1$s Smart Units'), '<br/>').'</span><br/></span>';
                $output .= '</td>';
            } else {
                $output .= "<td style='width: 20%; border-top: 3px solid #fff; border-bottom: 2px solid #fff; background-color: rgba(255,255,255,0.25); padding: 4px'>";
                $output .= "<span style='font-size: 270%; font-weight: bold; letter-spacing: 70%'>".__($guid, 'Two').'</span><br/>';
                $output .= "<span style='font-size: 140%; letter-spacing: 70%'><a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Planner/units.php'>".sprintf(__($guid, 'Plan & Deploy %1$s Smart Units'), '<br/>').'</span><br/></a>';
                $output .= '</td>';
            }
            if ($step == 3) {
                $output .= "<td style='width: 20%; border-top: 3px solid #fff; border-bottom: 2px solid rgba(255,255,255,0.0); background-color: rgba(255,255,255,0.25); padding: 4px'>";
                $output .= "<span style='color: #c00; font-size: 270%; font-weight: bold; letter-spacing: 70%'>".__($guid, 'Three').'</span><br/>';
                $output .= "<span style='color: #c00; font-size: 140%; letter-spacing: 70%'>".sprintf(__($guid, 'Share, Teach %1$s & Interact'), '<br/>').'</span><br/></span>';
                $output .= '</td>';
            } else {
                $output .= "<td style='width: 20%; border-top: 3px solid #fff; border-bottom: 2px solid #fff; background-color: rgba(255,255,255,0.25); padding: 4px'>";
                $output .= "<span style='font-size: 270%; font-weight: bold; letter-spacing: 70%'>".__($guid, 'Three').'</span><br/>';
                $output .= "<span style='font-size: 140%; letter-spacing: 70%'><a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Planner/planner.php'>".sprintf(__($guid, 'Share, Teach %1$s & Interact'), '<br/>').'</span><br/></a>';
                $output .= '</td>';
            }
            if ($step == 4) {
                $output .= "<td style='width: 20%; border-top: 3px solid #fff; border-bottom: 2px solid rgba(255,255,255,0.0); background-color: rgba(255,255,255,0.25); padding: 4px'>";
                $output .= "<span style='color: #c00; font-size: 270%; font-weight: bold; letter-spacing: 70%'>".__($guid, 'Four<').'/span><br/>';
                $output .= "<span style='color: #c00; font-size: 140%; letter-spacing: 70%'>".sprintf(__($guid, 'Assign & Collect %1$s Work'), '<br/>').'</span><br/></span>';
                $output .= '</td>';
            } else {
                $output .= "<td style='width: 20%; border-top: 3px solid #fff; border-bottom: 2px solid #fff; background-color: rgba(255,255,255,0.25); padding: 4px'>";
                $output .= "<span style='font-size: 270%; font-weight: bold; letter-spacing: 70%'>".__($guid, 'Four').'</span><br/>';
                $output .= "<span style='font-size: 140%; letter-spacing: 70%'><a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Planner/planner_deadlines.php'>".sprintf(__($guid, 'Assign & Collect %1$s Work'), '<br/>').'</span><br/></a>';
                $output .= '</td>';
            }
            if ($step == 5) {
                $output .= "<td style='width: 20%; border-top: 3px solid #fff; border-bottom: 2px solid rgba(255,255,255,0.0); background-color: rgba(255,255,255,0.25); padding: 4px'>";
                $output .= "<span style='color: #c00; font-size: 270%; font-weight: bold; letter-spacing: 70%'>".__($guid, 'Five').'</span><br/>';
                $output .= "<span style='color: #c00; font-size: 140%; letter-spacing: 70%'>".sprintf(__($guid, 'Assess & Give %1$s Feedback'), '<br/>').'</span><br/></span>';
                $output .= '</td>';
            } else {
                $output .= "<td style='width: 20%; border-top: 3px solid #fff; border-bottom: 2px solid #fff; background-color: rgba(255,255,255,0.25); padding: 4px'>";
                $output .= "<span style='font-size: 270%; font-weight: bold; letter-spacing: 70%'>".__($guid, 'Five').'</span><br/>';
                $output .= "<span style='font-size: 140%; letter-spacing: 70%'><a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Markbook/markbook_view.php'>".sprintf(__($guid, 'Assess & Give %1$s Feedback'), '<br/>').'</span><br/></a>';
                $output .= '</td>';
            }
            $output .= '</tr>';
            if ($step != '') {
                $output .= '<tr>';
                $output .= "<td style='text-align: justify; font-size: 125%; border-bottom: 2px solid #fff; background-color: rgba(255,255,255,0.25); padding: 15px 4px' colspan=5>";
                if ($step == 1) {
                    $output .= __($guid, '<b>Outcomes</b> provide a way to plan and track what is being taught in school, and so are a great place to get started.<br/><br/>Click on the "Add" button (below this message, on the right) to add a new outcome, which can either be school-wide, or attached to a particular department.').'<br/>';
                    $output .= "<div style='font-size: 75%; font-style: italic; margin-top: 10px'>".__($guid, '<b>Note</b>: You need to be in a department, with the correct permissions, in order to be able to do this.').' '.sprintf(__($guid, 'Please contact %1$s for help.'), "<a href='mailto:".$_SESSION[$guid]['organisationAdministratorEmail']."'>".$_SESSION[$guid]['organisationAdministratorName'].'</a>').'</div>';
                } elseif ($step == 2) {
                    $output .= __($guid, '<b>Smart Units</b> support you in the design of course content, and can be quickly turned into individual lesson plans using intuitive drag and drop. Smart Units cut planning time dramatically, and support ongoing improvement and reuse of content.<br/><br/>Choose a course, using the dropdown menu on the right, and then click on the "Add" button (below this message, on the right) to add a new unit. Once your master unit is complete, deploy it to a class to create your lesson plans.').'<br/>';
                    $output .= "<div style='font-size: 75%; font-style: italic; margin-top: 10px'>".__($guid, '<b>Note</b>: You need to be in a department, with the correct permissions, in order to be able to do this.').' '.sprintf(__($guid, 'Please contact %1$s for help.'), "<a href='mailto:".$_SESSION[$guid]['organisationAdministratorEmail']."'>".$_SESSION[$guid]['organisationAdministratorName'].'</a>').'</div>';
                } elseif ($step == 3) {
                    $output .= sprintf(__($guid, '<b>Planner</b> supports online lesson plans which can be shared with students, parents and other teachers. Create your lesson by hand, or automatically via %1$sSmart Units%2$s. Lesson plans facilitate sharing of course content, homework assignment and submission, text chat, and attendance taking.<br/><br/>Choose a date or class, using the menu on the right, and then click on the "Add" button (below this message, on the right) to add a new unit.'), "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Planner/units.php'>", '</a>').'<br/>';
                    $output .= "<div style='font-size: 75%; font-style: italic; margin-top: 10px'>".__($guid, '<b>Note</b>: You need to have classes assigned to you, with the correct permissions, in order to be able to do this.').' '.sprintf(__($guid, 'Please contact %1$s for help.'), "<a href='mailto:".$_SESSION[$guid]['organisationAdministratorEmail']."'>".$_SESSION[$guid]['organisationAdministratorName'].'</a>').'</div>';
                } elseif ($step == 4) {
                    $output .= sprintf(__($guid, '<b>Homework + Deadlines</b> allows teachers and students to see upcoming deadlines, cleanly displayed in one place. Click on an entry to view the details for that piece of homework, and the lesson it is attached to.<br/><br/>Homework can be assigned using the %1$sPlanner%2$s, which also allows teachers to view all submitted work, and records late and incomplete work.'), "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Planner/planner.php'>", '</a>').'<br/>';
                    $output .= "<div style='font-size: 75%; font-style: italic; margin-top: 10px'>".__($guid, '<b>Note</b>: You need to have classes assigned to you, with the correct permissions, in order to be able to do this.').' '.sprintf(__($guid, 'Please contact %1$s for help.'), "<a href='mailto:".$_SESSION[$guid]['organisationAdministratorEmail']."'>".$_SESSION[$guid]['organisationAdministratorName'].'</a>').'</div>';
                } elseif ($step == 5) {
                    $output .= sprintf(__($guid, '<b>Markbook</b> provides an organised way to assess, record and report on student progress. Use grade scales, rubrics, comments and file uploads to keep students and parents up to date. Link markbooks to the %1$sPlanner%2$s, and see student work as you are marking it.<br/><br/>Choose a class from the menu on the right, and then click on the "Add" button (below this message, on the right) to create a new markbook column.'), "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Planner/planner.php'>", '</a>').'<br/>';
                    $output .= "<div style='font-size: 75%; font-style: italic; margin-top: 10px'>".__($guid, '<b>Note</b>: You need to have classes assigned to you, with the correct permissions, in order to be able to do this.').' '.sprintf(__($guid, 'Please contact %1$s for help.'), "<a href='mailto:".$_SESSION[$guid]['organisationAdministratorEmail']."'>".$_SESSION[$guid]['organisationAdministratorName'].'</a>').'</div>';
                }
                $output .= '</td>';
                $output .= '</tr>';
            }
            $output .= '</table>';
            $output .= "<div style='text-align: right; font-size: 90%; padding: 0 7px'>";
            $output .= "<a title='".__($guid, 'Dismiss Smart Workflow Help')."' onclick='$(\"#smartWorkflowHelp\").fadeOut(1000); $.ajax({ url: \"".$_SESSION[$guid]['absoluteURL']."/index_SmartWorkflowHelpAjax.php\"})' href='#'>".__($guid, 'Dismiss Smart Workflow Help').'</a>';
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
        $output .= __($guid, 'An error occurred.');
    } elseif ($alpha != 'N' or $numeric != 'N' or $punctuation != 'N' or $minLength >= 0) {
        $output .= __($guid, 'The password policy stipulates that passwords must:').'<br/>';
        $output .= '<ul>';
        if ($alpha == 'Y') {
            $output .= '<li>'.__($guid, 'Contain at least one lowercase letter, and one uppercase letter.').'</li>';
        }
        if ($numeric == 'Y') {
            $output .= '<li>'.__($guid, 'Contain at least one number.').'</li>';
        }
        if ($punctuation == 'Y') {
            $output .= '<li>'.__($guid, 'Contain at least one non-alphanumeric character (e.g. a punctuation mark or space).').'</li>';
        }
        if ($minLength >= 0) {
            $output .= '<li>'.sprintf(__($guid, 'Must be at least %1$s characters in length.'), $minLength).'</li>';
        }
        $output .= '</ul>';
    }

    return $output;
}

function getFastFinder($connection2, $guid)
{
    $output = false;

    $output .= "<div id='fastFinder'>";
    $studentIsAccessible = isActionAccessible($guid, $connection2, '/modules/students/student_view.php');
    $staffIsAccessible = isActionAccessible($guid, $connection2, '/modules/Staff/staff_view.php');
    $classIsAccessible = false;
    $highestActionClass = getHighestGroupedAction($guid, '/modules/Planner/planner.php', $connection2);
    if (isActionAccessible($guid, $connection2, '/modules/Planner/planner.php') and $highestActionClass != 'Lesson Planner_viewMyChildrensClasses') {
        $classIsAccessible = true;
    }
        //Get list
        try {
            $dataList = array('gibbonRoleID' => $_SESSION[$guid]['gibbonRoleIDCurrent']);
            $sqlList = "(SELECT DISTINCT concat(gibbonModule.name, '/', gibbonAction.entryURL) AS id, SUBSTRING_INDEX(gibbonAction.name, '_', 1) AS name, 'Action' AS type FROM gibbonModule JOIN gibbonAction ON (gibbonAction.gibbonModuleID=gibbonModule.gibbonModuleID) JOIN gibbonPermission ON (gibbonPermission.gibbonActionID=gibbonAction.gibbonActionID) WHERE active='Y' AND menuShow='Y' AND gibbonPermission.gibbonRoleID=:gibbonRoleID)";
            if ($staffIsAccessible == true) {
                $sqlList .= " UNION (SELECT gibbonPerson.gibbonPersonID AS id, concat(surname, ', ', preferredName) AS name, 'Staff' AS type FROM gibbonPerson JOIN gibbonStaff ON (gibbonStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."'))";
            }
            if ($studentIsAccessible == true) {
                $dataList['gibbonSchoolYearID'] = $_SESSION[$guid]['gibbonSchoolYearID'];
                $sqlList .= " UNION (SELECT gibbonPerson.gibbonPersonID AS id, concat(surname, ', ', preferredName, ' (', gibbonRollGroup.name, ')') AS name, 'Student' AS type FROM gibbonPerson, gibbonStudentEnrolment, gibbonRollGroup WHERE gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID AND gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID AND status='FULL' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID)";
            }
            if ($classIsAccessible) {
                if ($highestActionClass == 'Lesson Planner_viewEditAllClasses' or $highestActionClass == 'Lesson Planner_viewAllEditMyClasses') {
                    $dataList['gibbonSchoolYearID2'] = $_SESSION[$guid]['gibbonSchoolYearID'];
                    $sqlList .= " UNION (SELECT gibbonCourseClass.gibbonCourseClassID AS id, concat(gibbonCourse.nameShort, '.', gibbonCourseClass.nameShort) AS name, 'Class' AS type FROM gibbonCourseClass JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID2)";
                } else {
                    $dataList['gibbonSchoolYearID3'] = $_SESSION[$guid]['gibbonSchoolYearID'];
                    $dataList['gibbonPersonID'] = $_SESSION[$guid]['gibbonPersonID'];
                    $sqlList .= " UNION (SELECT gibbonCourseClass.gibbonCourseClassID AS id, concat(gibbonCourse.nameShort, '.', gibbonCourseClass.nameShort) AS name, 'Class' AS type FROM gibbonCourseClassPerson JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID3 AND gibbonPersonID=:gibbonPersonID)";
                }
            }
            $sqlList .= ' ORDER BY type, name';

            $resultList = $connection2->prepare($sqlList);
            $resultList->execute($dataList);
        } catch (PDOException $e) {
            $output .= $e->getMessage();
        }

    $studentCount = 0;
    $list = '';
    while ($rowList = $resultList->fetch()) {
        $list .= '{id: "'.substr($rowList['type'], 0, 3).'-'.$rowList['id'].'", name: "'.htmlPrep($rowList['type']).' - '.htmlPrep($rowList['name']).'"},';
        if ($rowList['name'] == 'Sound Alarm') { //Special lockdown entry
                if (isActionAccessible($guid, $connection2, '/modules/System Admin/alarm.php')) {
                    $list .= '{id: "'.substr($rowList['type'], 0, 3).'-'.$rowList['id'].'", name: "'.htmlPrep($rowList['type']).' - Lockdown"},';
                }
        }
        if ($rowList['type'] == 'Student') {
            ++$studentCount;
        }
    }

    $output .= '<style>';
    $output .= 'ul.token-input-list-facebook { width: 275px; float: left; height: 25px!important; }';
    $output .= 'div.token-input-dropdown-facebook { width: 275px; z-index: 99999999 }';
    $output .= '</style>';
    $output .= "<div style='padding-bottom: 7px; height: 40px; margin-top: 0px'>";
    $output .= "<form method='get' action='".$_SESSION[$guid]['absoluteURL']."/indexFindRedirect.php'>";
    $output .= "<table class='smallIntBorder' cellspacing='0' style='width: 100%; margin: 0px 0px; opacity: 0.8'>";
    $output .= '<tr>';
    $output .= "<td style='vertical-align: top; padding: 0px' colspan=2>";
    $output .= "<h2 style='padding-bottom: 0px'>";
    $output .= __($guid, 'Fast Finder').': Actions';
    if ($classIsAccessible == true) {
        $output .= ', '.__($guid, 'Classes');
    }
    if ($studentIsAccessible == true) {
        $output .= ', '.__($guid, 'Students');
    }
    if ($staffIsAccessible == true) {
        $output .= ', '.__($guid, 'Staff');
    }
    $output .= '<br/>';
    $output .= '</h2>';
    $output .= '</td>';
    $output .= '</tr>';
    $output .= '<tr>';
    $output .= "<td style='vertical-align: top; border: none'>";
    $output .= "<input class='topFinder' style='width: 275px' type='text' id='id' name='id' />";
    $output .= "<script type='text/javascript'>";
    $output .= '$(document).ready(function() {';
    $output .= '$("#id").tokenInput([';
    $output .= substr($list, 0, -1);
    $output .= '],';
    $output .= '{theme: "facebook",';
    $output .= 'hintText: "Start typing a name...",';
    $output .= 'allowCreation: false,';
    $output .= 'preventDuplicates: true,';
    $output .= 'tokenLimit: 1});';
    $output .= '});';
    $output .= '</script>';
    $output .= "<script type='text/javascript'>";
    $output .= "var id=new LiveValidation('id');";
    $output .= 'id.add(Validate.Presence);';
    $output .= '</script>';
    $output .= '</td>';
    $output .= "<td class='right' style='vertical-align: top; border: none'>";
    $output .= "<input style='height: 27px; width: 60px!important; margin-top: 0px;' type='submit' value='".__($guid, 'Go')."'>";
    $output .= '</td>';
    $output .= '</tr>';
    if (getRoleCategory($_SESSION[$guid]['gibbonRoleIDCurrent'], $connection2) == 'Staff') {
        $output .= '<tr>';
        $output .= "<td style='vertical-align: top' colspan=2>";
        $output .= "<div style='padding-bottom: 0px; font-size: 80%; font-weight: normal; font-style: italic; line-height: 80%; padding: 1em,1em,1em,1em; width: 99%; text-align: left; color: #888;' >".__($guid, 'Total Student Enrolment:').' '.$studentCount.'</div>';
        $output .= '</td>';
        $output .= '</tr>';
    }
    $output .= '</table>';
    $output .= '</form>';
    $output .= '</div>';
    $output .= '</div>';

    return $output;
}

function getParentPhotoUploader($connection2, $guid)
{
    $output = false;

    $category = getRoleCategory($_SESSION[$guid]['gibbonRoleIDCurrent'], $connection2);
    if ($category == 'Parent') {
        $output .= "<h2 style='margin-bottom: 10px'>";
        $output .= 'Profile Photo';
        $output .= '</h2>';

        if ($_SESSION[$guid]['image_240'] == '') { //No photo, so show uploader
            $output .= '<p>';
            $output .= __($guid, 'Please upload a passport photo to use as a profile picture.').' '.__($guid, '240px by 320px').'.';
            $output .= '</p>';
            $output .= "<form method='post' action='".$_SESSION[$guid]['absoluteURL'].'/index_parentPhotoUploadProcess.php?gibbonPersonID='.$_SESSION[$guid]['gibbonPersonID']."' enctype='multipart/form-data'>";
            $output .= "<table class='smallIntBorder' cellspacing='0' style='width: 100%; margin: 0px 0px'>";
            $output .= '<tr>';
            $output .= "<td style='vertical-align: top'>";
            $output .= "<input type=\"file\" name=\"file1\" id=\"file1\" style='width: 165px'><br/><br/>";
            $output .= '<script type="text/javascript">';
            $output .= "var file1=new LiveValidation('file1');";
            $output .= "file1.add( Validate.Inclusion, { within: ['gif','jpg','jpeg','png'], failureMessage: \"Illegal file type!\", partialMatch: true, caseSensitive: false } );";
            $output .= '</script>';
            $output .= '</td>';
            $output .= "<td class='right' style='vertical-align: top'>";
            $output .= "<input style='height: 27px; width: 20px!important; margin-top: 0px;' type='submit' value='".__($guid, 'Go')."'>";
            $output .= '</td>';
            $output .= '</tr>';
            $output .= '</table>';
            $output .= '</form>';
        } else { //Photo, so show image and removal link
            $output .= '<p>';
            $output .= getUserPhoto($guid, $_SESSION[$guid]['image_240'], 240);
            $output .= "<div style='margin-left: 220px; margin-top: -50px'>";
            $output .= "<a href='".$_SESSION[$guid]['absoluteURL'].'/index_parentPhotoDeleteProcess.php?gibbonPersonID='.$_SESSION[$guid]['gibbonPersonID']."' onclick='return confirm(\"Are you sure you want to delete this record? Unsaved changes will be lost.\")'><img style='margin-bottom: -8px' id='image_240_delete' title='".__($guid, 'Delete')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/garbage.png'/></a><br/><br/>";
            $output .= '</div>';
            $output .= '</p>';
        }
    }

    return $output;
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
        $output['name'] = __($guid, $rowAlert['name']);
        $output['nameShort'] = $rowAlert['nameShort'];
        $output['color'] = $rowAlert['color'];
        $output['colorBG'] = $rowAlert['colorBG'];
        $output['description'] = __($guid, $rowAlert['description']);
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
function getUnit($connection2, $gibbonUnitID, $gibbonHookID, $gibbonCourseClassID)
{
    $output = array();
    $unitType = false;
    if ($gibbonUnitID != '') {
        //Check for hooked unit (will have - in value)
        if ($gibbonHookID == '') {
            //No hook
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
        } else {
            //Hook!
            try {
                $dataHooks = array('gibbonHookID' => $gibbonHookID);
                $sqlHooks = 'SELECT * FROM gibbonHook WHERE gibbonHookID=:gibbonHookID';
                $resultHooks = $connection2->prepare($sqlHooks);
                $resultHooks->execute($dataHooks);
                if ($resultHooks->rowCount() == 1) {
                    $rowHooks = $resultHooks->fetch();
                    $hookOptions = unserialize($rowHooks['options']);
                    if ($hookOptions['unitTable'] != '' and $hookOptions['unitIDField'] != '' and $hookOptions['unitCourseIDField'] != '' and $hookOptions['unitNameField'] != '' and $hookOptions['unitDescriptionField'] != '' and $hookOptions['classLinkTable'] != '' and $hookOptions['classLinkJoinFieldUnit'] != '' and $hookOptions['classLinkJoinFieldClass'] != '' and $hookOptions['classLinkIDField'] != '') {
                        try {
                            $dataHookUnits = array();
                            $sqlHookUnits = 'SELECT * FROM '.$hookOptions['unitTable'].' JOIN '.$hookOptions['classLinkTable'].' ON ('.$hookOptions['unitTable'].'.'.$hookOptions['unitIDField'].'='.$hookOptions['classLinkTable'].'.'.$hookOptions['classLinkJoinFieldUnit'].') WHERE '.$hookOptions['unitTable'].'.'.$hookOptions['unitIDField'].'='.$gibbonUnitID.' AND '.$hookOptions['classLinkJoinFieldClass'].'='.$gibbonCourseClassID.' ORDER BY '.$hookOptions['classLinkTable'].'.'.$hookOptions['classLinkIDField'];
                            $resultHookUnits = $connection2->prepare($sqlHookUnits);
                            $resultHookUnits->execute($dataHookUnits);
                            if ($resultHookUnits->rowCount() == 1) {
                                $rowHookUnits = $resultHookUnits->fetch();
                                $output[0] = $rowHookUnits[$hookOptions['unitNameField']];
                                $output[1] = $rowHooks['name'];
                            }
                        } catch (PDOException $e) {
                        }
                    }
                }
            } catch (PDOException $e) {
            }
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
            while (date('D', $firstDayStamp) != 'Mon') {
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

function formatName($title, $preferredName, $surname, $roleCategory, $reverse = false, $informal = false)
{
    $output = false;

    if ($roleCategory == 'Staff' or $roleCategory == 'Other') {
        if ($informal == false) {
            if ($reverse == true) {
                $output = $title.' '.$surname.', '.strtoupper(substr($preferredName, 0, 1)).'.';
            } else {
                $output = $title.' '.strtoupper(substr($preferredName, 0, 1)).'. '.$surname;
            }
        } else {
            if ($reverse == true) {
                $output = $surname.', '.$preferredName;
            } else {
                $output = $preferredName.' '.$surname;
            }
        }
    } elseif ($roleCategory == 'Parent') {
        if ($informal == false) {
            if ($reverse == true) {
                $output = $title.' '.$surname.', '.$preferredName;
            } else {
                $output = $title.' '.$preferredName.' '.$surname;
            }
        } else {
            if ($reverse == true) {
                $output = $surname.', '.$preferredName;
            } else {
                $output = $preferredName.' '.$surname;
            }
        }
    } elseif ($roleCategory == 'Student') {
        if ($reverse == true) {
            $output = $surname.', '.$preferredName;
        } else {
            $output = $preferredName.' '.$surname;
        }
    }

    return $output;
}

//$tinymceInit indicates whether or not tinymce should be initialised, or whether this will be done else where later (this can be used to improve page load.
function getEditor($guid, $tinymceInit = true, $id, $value = '', $rows = 10, $showMedia = false, $required = false, $initiallyHidden = false, $allowUpload = true, $initialFilter = '', $resourceAlphaSort = false)
{
    $output = false;
    if ($resourceAlphaSort == false) {
        $resourceAlphaSort = 'false';
    } else {
        $resourceAlphaSort = 'true';
    }

    $output .= "<a name='".$id."editor'></a>";

    $output .= "<div id='editor-toolbar'>";
    $output .= "<a style='margin-top:-4px' id='".$id."edButtonHTML' class='hide-if-no-js edButtonHTML'>HTML</a>";
    $output .= "<a style='margin-top:-4px' id='".$id."edButtonPreview' class='active hide-if-no-js edButtonPreview'>".__($guid, 'Visual').'</a>';

    $output .= "<div id='media-buttons'>";
    $output .= "<div style='padding-top: 2px; height: 15px'>";
    if ($showMedia == true) {
        $output .= "<div id='".$id."mediaInner' style='text-align: left'>";
        $output .= "<script type='text/javascript'>";
        $output .= '$(document).ready(function(){';
        $output .= '$(".'.$id.'resourceSlider").hide();';
        $output .= '$(".'.$id.'resourceAddSlider").hide();';
        $output .= '$(".'.$id.'resourceQuickSlider").hide();';
        $output .= '$(".'.$id.'show_hide").show();';
        $output .= '$(".'.$id."show_hide\").unbind('click').click(function(){";
        $output .= '$(".'.$id.'resourceSlider").slideToggle();';
        $output .= '$(".'.$id.'resourceAddSlider").hide();';
        $output .= '$(".'.$id.'resourceQuickSlider").hide();';
        $output .= "if (tinyMCE.get('".$id."').selection.getRng().startOffset < 1) {";
        $output .= "tinyMCE.get('".$id."').focus();";
        $output .= '}';
        $output .= '});';
        $output .= '$(".'.$id.'show_hideAdd").show();';
        $output .= '$(".'.$id."show_hideAdd\").unbind('click').click(function(){";
        $output .= '$(".'.$id.'resourceAddSlider").slideToggle();';
        $output .= '$(".'.$id.'resourceSlider").hide();';
        $output .= '$(".'.$id.'resourceQuickSlider").hide();';
        $output .= "if (tinyMCE.get('".$id."').selection.getRng().startOffset < 1) {";
        $output .= "tinyMCE.get('".$id."').focus();";
        $output .= '}';
        $output .= '});';
        $output .= '$(".'.$id.'show_hideQuickAdd").show();';
        $output .= '$(".'.$id."show_hideQuickAdd\").unbind('click').click(function(){";
        $output .= '$(".'.$id.'resourceQuickSlider").slideToggle();';
        $output .= '$(".'.$id.'resourceSlider").hide();';
        $output .= '$(".'.$id.'resourceAddSlider").hide();';
        $output .= "if (tinyMCE.get('".$id."').selection.getRng().startOffset < 1) {";
        $output .= "tinyMCE.get('".$id."').focus();";
        $output .= '}';
        $output .= '});';
        $output .= '});';
        $output .= '</script>';

        $output .= "<div style='float: left; padding-top:1px; margin-right: 5px'><u>".__($guid, 'Shared Resources').'</u>:</div> ';
        $output .= "<a title='".__($guid, 'Insert Existing Resource')."' style='float: left' class='".$id."show_hide' onclick='\$(\".".$id.'resourceSlider").load("'.$_SESSION[$guid]['absoluteURL'].'/modules/Resources/resources_insert_ajax.php?alpha='.$resourceAlphaSort.'&'.$initialFilter.'","id='.$id."&allowUpload=$allowUpload\");' href='#'><img style='padding-right: 5px' src='".$_SESSION[$guid]['absoluteURL']."/themes/Default/img/search_mini.png' alt='".__($guid, 'Insert Existing Resource')."' onclick='return false;' /></a>";
        if ($allowUpload == true) {
            $output .= "<a title='".__($guid, 'Create & Insert New Resource')."' style='float: left' class='".$id."show_hideAdd' onclick='\$(\".".$id.'resourceAddSlider").load("'.$_SESSION[$guid]['absoluteURL'].'/modules/Resources/resources_add_ajax.php?alpha='.$resourceAlphaSort.'&'.$initialFilter.'","id='.$id."&allowUpload=$allowUpload\");' href='#'><img style='padding-right: 5px' src='".$_SESSION[$guid]['absoluteURL']."/themes/Default/img/upload_mini.png' alt='".__($guid, 'Create & Insert New Resource')."' onclick='return false;' /></a>";
        }
        $output .= "<div style='float: left; padding-top:1px; margin-right: 5px'><u>".__($guid, 'Quick File Upload').'</u>:</div> ';
        $output .= "<a title='".__($guid, 'Quick Add')."' style='float: left' class='".$id."show_hideQuickAdd' onclick='\$(\".".$id.'resourceQuickSlider").load("'.$_SESSION[$guid]['absoluteURL'].'/modules/Resources/resources_addQuick_ajax.php?alpha='.$resourceAlphaSort.'&'.$initialFilter.'","id='.$id."&allowUpload=$allowUpload\");' href='#'><img style='padding-right: 5px' src='".$_SESSION[$guid]['absoluteURL']."/themes/Default/img/page_new_mini.png' alt='".__($guid, 'Quick Add')."' onclick='return false;' /></a>";
        $output .= '</div>';
    }
    $output .= '</div>';
    $output .= '</div>';

    if ($showMedia == true) {
        //DEFINE MEDIA INPUT DISPLAY
        $output .= "<div class='".$id."resourceSlider' style='display: none; width: 100%; min-height: 60px;'>";
        $output .= "<div style='text-align: center; width: 100%; margin-top: 5px'>";
        $output .= "<img style='margin: 10px 0 5px 0' src='".$_SESSION[$guid]['absoluteURL']."/themes/Default/img/loading.gif' alt='".__($guid, 'Loading')."' onclick='return false;' /><br/>";
        $output .= __($guid, 'Loading');
        $output .= '</div>';
        $output .= '</div>';

        //DEFINE QUICK INSERT
        $output .= "<div class='".$id."resourceQuickSlider' style='display: none; width: 100%; min-height: 60px;'>";
        $output .= "<div style='text-align: center; width: 100%; margin-top: 5px'>";
        $output .= "<img style='margin: 10px 0 5px 0' src='".$_SESSION[$guid]['absoluteURL']."/themes/Default/img/loading.gif' alt='".__($guid, 'Loading')."' onclick='return false;' /><br/>";
        $output .= __($guid, 'Loading');
        $output .= '</div>';
        $output .= '</div>';
    }

    if ($showMedia == true and $allowUpload == true) {
        //DEFINE MEDIA ADD DISPLAY
        $output .= "<div class='".$id."resourceAddSlider' style='display: none; width: 100%; min-height: 60px;'>";
        $output .= "<div style='text-align: center; width: 100%; margin-top: 5px'>";
        $output .= "<img style='margin: 10px 0 5px 0' src='".$_SESSION[$guid]['absoluteURL']."/themes/Default/img/loading.gif' alt='".__($guid, 'Loading')."' onclick='return false;' /><br/>";
        $output .= __($guid, 'Loading');
        $output .= '</div>';
        $output .= '</div>';
    }

    $output .= "<div id='editorcontainer' style='margin-top: 4px'>";

    $output .= "<textarea class='tinymce' name='".$id."' id='".$id."' style='height: ".($rows * 18)."px; width: 100%; margin-left: 0px'>".htmlPrep($value).'</textarea>';
    if ($required) {
        $output .= "<script type='text/javascript'>";
        $output .= 'var '.$id."='';";
        $output .= $id."=new LiveValidation('".$id."');";
        $output .= $id.".add(Validate.Presence, { tinymce: true, tinymceField: '".$id."'});";
        if ($initiallyHidden == true) {
            $output .= $id.'.disable();';
        }
        $output .= '</script>';
    }
    $output .= '</div>';

    $output .= "<script type='text/javascript'>";
    $output .= '$(document).ready(function(){';
    if ($tinymceInit) {
        $output .= "tinyMCE.execCommand('mceAddControl', false, '".$id."');";
    }
    $output .= "$('#".$id."edButtonPreview').addClass('active') ;";
    $output .= "$('#".$id."edButtonHTML').click(function(){";
    $output .= "tinyMCE.execCommand('mceRemoveEditor', false, '".$id."');";
    $output .= "$('#".$id."edButtonHTML').addClass('active') ;";
    $output .= "$('#".$id."edButtonPreview').removeClass('active') ;";
    $output .= '$(".'.$id.'resourceSlider").hide();';
    $output .= '$("#'.$id.'mediaInner").hide();';
    if ($required) {
        $output .= $id.'.destroy();';
        $output .= "$('.LV_validation_message').css('display','none');";
        $output .= $id."=new LiveValidation('".$id."');";
        $output .= $id.'.add(Validate.Presence);';
    }
    $output .= '}) ;';
    $output .= "$('#".$id."edButtonPreview').click(function(){";
    $output .= "tinyMCE.execCommand('mceAddEditor', false, '".$id."');";
    $output .= "$('#".$id."edButtonPreview').addClass('active') ;";
    $output .= "$('#".$id."edButtonHTML').removeClass('active') ; ";
    $output .= '$("#'.$id.'mediaInner").show();';
    if ($required) {
        $output .= $id.'.destroy();';
        $output .= "$('.LV_validation_message').css('display','none');";
        $output .= $id."=new LiveValidation('".$id."');";
        $output .= $id.".add(Validate.Presence, { tinymce: true, tinymceField: '".$id."'});";
    }
    $output .= '}) ;';
    $output .= '});';
    $output .= '</script>';
    $output .= '</div>';

    return $output;
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
                        $output .= __($guid, $rowYears['nameShort']);
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

//Create the sidebar
function sidebar($connection2, $guid)
{
    $googleOAuth = getSettingByScope($connection2, 'System', 'googleOAuth');
    if (isset($_GET['loginReturn'])) {
        $loginReturn = $_GET['loginReturn'];
    } else {
        $loginReturn = '';
    }
    $loginReturnMessage = '';
    if (!($loginReturn == '')) {
        if ($loginReturn == 'fail0b') {
            $loginReturnMessage = __($guid, 'Username or password not set.');
        } elseif ($loginReturn == 'fail1') {
            $loginReturnMessage = __($guid, 'Incorrect username and password.');
        } elseif ($loginReturn == 'fail2') {
            $loginReturnMessage = __($guid, 'You do not have sufficient privileges to login.');
        } elseif ($loginReturn == 'fail5') {
            $loginReturnMessage = __($guid, 'Your request failed due to a database error.');
        } elseif ($loginReturn == 'fail6') {
            $loginReturnMessage = sprintf(__($guid, 'Too many failed logins: please %1$sreset password%2$s.'), "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/passwordReset.php'>", '</a>');
        } elseif ($loginReturn == 'fail7') {
            $loginReturnMessage = sprintf(__($guid, 'Error with Google Authentication. Please contact %1$s if you have any questions.'), "<a href='mailto:".$_SESSION[$guid]['organisationDBAEmail']."'>".$_SESSION[$guid]['organisationDBAName'].'</a>');
        } elseif ($loginReturn == 'fail8') {
            $loginReturnMessage = sprintf(__($guid, 'Gmail account does not match the email stored in %1$s. If you have logged in with your school Gmail account please contact %2$s if you have any questions.'), $_SESSION[$guid]['systemName'], "<a href='mailto:".$_SESSION[$guid]['organisationDBAEmail']."'>".$_SESSION[$guid]['organisationDBAName'].'</a>');
        } elseif ($loginReturn == 'fail9') {
            $loginReturnMessage = __($guid, 'Your primary role does not support the ability to log into the specified year.');
        }

        echo "<div class='error'>";
        echo $loginReturnMessage;
        echo '</div>';
    }

    if ($_SESSION[$guid]['sidebarExtra'] != '' and $_SESSION[$guid]['sidebarExtraPosition'] != 'bottom') {
        echo "<div class='sidebarExtra'>";
        echo $_SESSION[$guid]['sidebarExtra'];
        echo '</div>';
    }

    // Add Google Login Button
    if ((isset($_SESSION[$guid]['username']) == false) && (isset($_SESSION[$guid]['email']) == false)) {
        if ($googleOAuth == 'Y') {
            echo '<h2>';
            echo __($guid, 'Login with Google');
            echo '</h2>';

            ?>
			<script>
				$(function(){
					$('#siteloader').load('lib/google/index.php');
				});
			</script>
			<div id="siteloader"></div>
			<?php

        } //End Check for Google Auth
            if ((isset($_SESSION[$guid]['username']) == false)) { // If Google Auth set to No make sure login screen not visible when logged in
            ?>
			<h2>
				<?php echo __($guid, 'Login'); ?>
			</h2>
			<form name="loginForm" method="post" action="./login.php?<?php if (isset($_GET['q'])) { echo 'q='.$_GET['q'];
}
                ?>">
				<table class='noIntBorder' cellspacing='0' style="width: 100%; margin: 0px 0px">
					<tr>
						<td>
							<b><?php echo __($guid, 'Username'); ?></b>
						</td>
						<td class="right">
							<input name="username" id="username" maxlength=20 type="text" style="width:120px">
							<script type="text/javascript">
								var username=new LiveValidation('username', {onlyOnSubmit: true });
								username.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<tr>
						<td>
							<b><?php echo __($guid, 'Password'); ?></b>
						</td>
						<td class="right">
							<input name="password" id="password" maxlength=30 type="password" style="width:120px">
							<script type="text/javascript">
								var password=new LiveValidation('password', {onlyOnSubmit: true });
								password.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<tr class='schoolYear' id='schoolYear'>
						<td>
							<b><?php echo __($guid, 'School Year'); ?></b>
						</td>
						<td class="right">
							<select name="gibbonSchoolYearID" id="gibbonSchoolYearID" style="width: 120px">
								<?php
                                try {
                                    $dataSelect = array();
                                    $sqlSelect = 'SELECT * FROM gibbonSchoolYear ORDER BY sequenceNumber';
                                    $resultSelect = $connection2->prepare($sqlSelect);
                                    $resultSelect->execute($dataSelect);
                                } catch (PDOException $e) {
                                    echo "<div class='error'>".$e->getMessage().'</div>';
                                }
                while ($rowSelect = $resultSelect->fetch()) {
                    $selected = '';
                    if ($rowSelect['status'] == 'Current') {
                        $selected = 'selected';
                    }
                    echo "<option $selected value='".$rowSelect['gibbonSchoolYearID']."'>".htmlPrep($rowSelect['name']).'</option>';
                }
                ?>
							</select>
						</td>
					</tr>
					<tr class='language' id='language'>
						<td>
							<b><?php echo __($guid, 'Language'); ?></b>
						</td>
						<td class="right">
							<select name="gibboni18nID" id="gibboni18nID" style="width: 120px">
								<?php
                                try {
                                    $dataSelect = array();
                                    $sqlSelect = "SELECT * FROM gibboni18n WHERE active='Y' ORDER BY name";
                                    $resultSelect = $connection2->prepare($sqlSelect);
                                    $resultSelect->execute($dataSelect);
                                } catch (PDOException $e) {
                                    echo "<div class='error'>".$e->getMessage().'</div>';
                                }
                while ($rowSelect = $resultSelect->fetch()) {
                    $selected = '';
                    if ($rowSelect['systemDefault'] == 'Y') {
                        $selected = 'selected';
                    }
                    echo "<option $selected value='".$rowSelect['gibboni18nID']."'>".htmlPrep($rowSelect['name']).'</option>';
                }
                ?>
							</select>
						</td>
					</tr>
					<tr>
						<td>
						</td>
						<td class="right">
							<?php
                            echo "<script type='text/javascript'>";
                echo '$(document).ready(function(){';
                echo '$(".schoolYear").hide();';
                echo '$(".language").hide();';
                echo '$(".show_hide").fadeIn(1000);';
                echo '$(".show_hide").click(function(){';
                echo '$(".schoolYear").fadeToggle(1000);';
                echo '$(".language").fadeToggle(1000);';
                echo '});';
                echo '});';
                echo '</script>'; ?>
							<span style='font-size: 10px'><a class='show_hide' onclick='false' href='#'><?php echo __($guid, 'Options'); ?></a> . <a href="<?php echo $_SESSION[$guid]['absoluteURL']?>/index.php?q=passwordReset.php"><?php echo __($guid, 'Forgot Password?'); ?></a></span>
						</td>
					</tr>
					<tr>
						<td class="right" colspan=2>
							<input type="hidden" name="address" value="<?php echo $_SESSION[$guid]['address'] ?>">
							<input type="submit" value="Login">
						</td>
					</tr>
				</table>
			</form>
			<?php

            }
    }

    //Invoke and show Module Menu
    $menuModule = new Gibbon\menuModule();
    echo $menuModule->getMenu('full');

    //Show custom sidebar content on homepage for logged in users
    if ($_SESSION[$guid]['address'] == '' and isset($_SESSION[$guid]['username'])) {
        if (isset($_SESSION[$guid]['index_customSidebar.php']) == false) {
            if (is_file('./index_customSidebar.php')) {
                $_SESSION[$guid]['index_customSidebar.php'] = include './index_customSidebar.php';
            } else {
                $_SESSION[$guid]['index_customSidebar.php'] = null;
            }
        }
        if (isset($_SESSION[$guid]['index_customSidebar.php'])) {
            echo $_SESSION[$guid]['index_customSidebar.php'];
        }
    }

    //Show parent photo uploader
    if ($_SESSION[$guid]['address'] == '' and isset($_SESSION[$guid]['username'])) {
        $sidebar = getParentPhotoUploader($connection2, $guid);
        if ($sidebar != false) {
            echo $sidebar;
        }
    }

    //Show homescreen widget for message wall
    if ($_SESSION[$guid]['address'] == '') {
        if (isset($_SESSION[$guid]['messageWallOutput'])) {
            if (isActionAccessible($guid, $connection2, '/modules/Messenger/messageWall_view.php')) {
                $attainmentAlternativeName = getSettingByScope($connection2, 'Messenger', 'enableHomeScreenWidget');
                if ($attainmentAlternativeName == 'Y') {
                    echo '<h2>';
                    echo __($guid, 'Message Wall');
                    echo '</h2>';

                    if (count($_SESSION[$guid]['messageWallOutput']) < 1) {
                        echo "<div class='warning'>";
                        echo __($guid, 'There are no records to display.');
                        echo '</div>';
                    } elseif (is_array($_SESSION[$guid]['messageWallOutput']) == false) {
                        echo "<div class='error'>";
                        echo __($guid, 'An error occurred.');
                        echo '</div>';
                    } else {
                        $height = 283;
                        if (count($_SESSION[$guid]['messageWallOutput']) == 1) {
                            $height = 94;
                        } elseif (count($_SESSION[$guid]['messageWallOutput']) == 2) {
                            $height = 197;
                        }
                        echo "<table id='messageWallWidget' style='width: 100%; height: ".$height."px; border: 1px solid grey; padding: 6px; background-color: #eeeeee'>";
                            //Content added by JS
                            $rand = rand(0, count($_SESSION[$guid]['messageWallOutput']));
                        $total = count($_SESSION[$guid]['messageWallOutput']);
                        $order = '';
                        for ($i = 0; $i < $total; ++$i) {
                            $pos = ($rand + $i) % $total;
                            $order .= "$pos, ";
                            $message = $_SESSION[$guid]['messageWallOutput'][$pos];

                                //COLOR ROW BY STATUS!
                                echo "<tr id='messageWall".$pos."' style='z-index: 1;'>";
                            echo "<td style='font-size: 95%; letter-spacing: 85%;'>";
                                        //Image
                                        $style = "style='width: 45px; height: 60px; float: right; margin-left: 6px; border: 1px solid black'";
                            if ($message['photo'] == '' or file_exists($_SESSION[$guid]['absolutePath'].'/'.$message['photo']) == false) {
                                echo "<img $style  src='".$_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName']."/img/anonymous_75.jpg'/>";
                            } else {
                                echo "<img $style src='".$_SESSION[$guid]['absoluteURL'].'/'.$message['photo']."'/>";
                            }

                                        //Message number
                                        echo "<div style='margin-bottom: 4px; text-transform: uppercase; font-size: 70%; color: #888'>Message ".($pos + 1).'</div>';

                                        //Title
                                        $URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Messenger/messageWall_view.php#'.$message['gibbonMessengerID'];
                            if (strlen($message['subject']) <= 16) {
                                echo "<a style='font-weight: bold; font-size: 105%; letter-spacing: 85%; text-transform: uppercase' href='$URL'>".$message['subject'].'</a><br/>';
                            } else {
                                echo "<a style='font-weight: bold; font-size: 105%; letter-spacing: 85%; text-transform: uppercase' href='$URL'>".substr($message['subject'], 0, 16).'...</a><br/>';
                            }

                                        //Text
                                        echo "<div style='margin-top: 5px'>";
                            $message = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $message);
                            if (strlen(strip_tags($message['details'])) <= 40) {
                                echo strip_tags($message['details']).'<br/>';
                            } else {
                                echo substr(strip_tags($message['details']), 0, 40).'...<br/>';
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
                        }
                        echo '</table>';
                        $order = substr($order, 0, strlen($order) - 2);
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
                    echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Messenger/messageWall_view.php'>".__($guid, 'View Message Wall').'</a>';
                    echo '</p>';
                }
            }
        }
    }

    //Show upcoming deadlines
    if ($_SESSION[$guid]['address'] == '' and isActionAccessible($guid, $connection2, '/modules/Planner/planner.php')) {
        $highestAction = getHighestGroupedAction($guid, '/modules/Planner/planner.php', $connection2);
        if ($highestAction == 'Lesson Planner_viewMyClasses' or $highestAction == 'Lesson Planner_viewAllEditMyClasses' or $highestAction == 'Lesson Planner_viewEditAllClasses') {
            echo '<h2>';
            echo __($guid, 'Homework & Deadlines');
            echo '</h2>';

            try {
                $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                $sql = "
				(SELECT 'teacherRecorded' AS type, gibbonPlannerEntryID, gibbonUnitID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, date, timeStart, timeEnd, viewableStudents, viewableParents, homework, homeworkDueDateTime, role FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND NOT role='Student - Left' AND NOT role='Teacher - Left' AND homework='Y' AND (role='Teacher' OR (role='Student' AND viewableStudents='Y')) AND homeworkDueDateTime>'".date('Y-m-d H:i:s')."' AND ((date<'".date('Y-m-d')."') OR (date='".date('Y-m-d')."' AND timeEnd<='".date('H:i:s')."')))
				UNION
				(SELECT 'studentRecorded' AS type, gibbonPlannerEntry.gibbonPlannerEntryID, gibbonUnitID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, date, timeStart, timeEnd, 'Y' AS viewableStudents, 'Y' AS viewableParents, 'Y' AS homework, gibbonPlannerEntryStudentHomework.homeworkDueDateTime, role FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) JOIN gibbonPlannerEntryStudentHomework ON (gibbonPlannerEntryStudentHomework.gibbonPlannerEntryID=gibbonPlannerEntry.gibbonPlannerEntryID AND gibbonPlannerEntryStudentHomework.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND NOT role='Student - Left' AND NOT role='Teacher - Left' AND (role='Teacher' OR (role='Student' AND viewableStudents='Y')) AND gibbonPlannerEntryStudentHomework.homeworkDueDateTime>'".date('Y-m-d H:i:s')."' AND ((date<'".date('Y-m-d')."') OR (date='".date('Y-m-d')."' AND timeEnd<='".date('H:i:s')."')))
				 ORDER BY homeworkDueDateTime, type";
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                echo $e->getMessage();
            }
            if ($result->rowCount() < 1) {
                echo "<div class='success'>";
                echo __($guid, 'No upcoming deadlines. Yay!');
                echo '</div>';
            } else {
                echo '<ol>';
                $count = 0;
                while ($row = $result->fetch()) {
                    if ($count < 5) {
                        $diff = (strtotime(substr($row['homeworkDueDateTime'], 0, 10)) - strtotime(date('Y-m-d'))) / 86400;
                        $category = getRoleCategory($_SESSION[$guid]['gibbonRoleIDCurrent'], $connection2);
                        $style = 'padding-right: 3px;';
                        if ($category == 'Student') {
                            //Calculate style for student-specified completion of teacher-recorded homework
                            try {
                                $dataCompletion = array('gibbonPlannerEntryID' => $row['gibbonPlannerEntryID'], 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                                $sqlCompletion = "SELECT gibbonPlannerEntryID FROM gibbonPlannerEntryStudentTracker WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND gibbonPersonID=:gibbonPersonID AND homeworkComplete='Y'";
                                $resultCompletion = $connection2->prepare($sqlCompletion);
                                $resultCompletion->execute($dataCompletion);
                            } catch (PDOException $e) {
                            }
                            if ($resultCompletion->rowCount() == 1) {
                                $style .= '; background-color: #B3EFC2';
                            }

                            //Calculate style for student-specified completion of student-recorded homework
                            try {
                                $dataCompletion = array('gibbonPlannerEntryID' => $row['gibbonPlannerEntryID'], 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                                $sqlCompletion = "SELECT gibbonPlannerEntryID FROM gibbonPlannerEntryStudentHomework WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND gibbonPersonID=:gibbonPersonID AND homeworkComplete='Y'";
                                $resultCompletion = $connection2->prepare($sqlCompletion);
                                $resultCompletion->execute($dataCompletion);
                            } catch (PDOException $e) {
                            }
                            if ($resultCompletion->rowCount() == 1) {
                                $style .= '; background-color: #B3EFC2';
                            }

                            //Calculate style for online submission completion
                            try {
                                $dataCompletion = array('gibbonPlannerEntryID' => $row['gibbonPlannerEntryID'], 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                                $sqlCompletion = "SELECT gibbonPlannerEntryID FROM gibbonPlannerEntryHomework WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND gibbonPersonID=:gibbonPersonID AND version='Final'";
                                $resultCompletion = $connection2->prepare($sqlCompletion);
                                $resultCompletion->execute($dataCompletion);
                            } catch (PDOException $e) {
                            }
                            if ($resultCompletion->rowCount() == 1) {
                                $style .= '; background-color: #B3EFC2';
                            }
                        }

                        //Calculate style for deadline
                        if ($diff < 2) {
                            $style .= '; border-right: 10px solid #cc0000';
                        } elseif ($diff < 4) {
                            $style .= '; border-right: 10px solid #D87718';
                        }

                        echo "<li style='$style'>";
                        echo  "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Planner/planner_view_full.php&gibbonPlannerEntryID='.$row['gibbonPlannerEntryID'].'&date='.$row['date']."'>".$row['course'].'.'.$row['class'].'</a><br/>';
                        echo "<span style='font-style: italic'>Due at ".substr($row['homeworkDueDateTime'], 11, 5).' on '.dateConvertBack($guid, substr($row['homeworkDueDateTime'], 0, 10));
                        echo '</li>';
                    }
                    ++$count;
                }
                echo '</ol>';
            }

            echo "<p style='padding-top: 0px; text-align: right'>";
            echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Planner/planner_deadlines.php'>".__($guid, 'View Homework').'</a>';
            echo '</p>';
        }
    }

    //Show recent results
    if ($_SESSION[$guid]['address'] == '' and isActionAccessible($guid, $connection2, '/modules/Markbook/markbook_view.php')) {
        $highestAction = getHighestGroupedAction($guid, '/modules/Markbook/markbook_view.php', $connection2);
        if ($highestAction == 'View Markbook_myMarks') {
            try {
                $dataEntry = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                $sqlEntry = "SELECT gibbonMarkbookEntryID, gibbonMarkbookColumn.name, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonMarkbookEntry JOIN gibbonMarkbookColumn ON (gibbonMarkbookEntry.gibbonMarkbookColumnID=gibbonMarkbookColumn.gibbonMarkbookColumnID) JOIN gibbonCourseClass ON (gibbonMarkbookColumn.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonIDStudent=:gibbonPersonID AND complete='Y' AND completeDate<='".date('Y-m-d')."' AND viewableStudents='Y' ORDER BY completeDate DESC, name";
                $resultEntry = $connection2->prepare($sqlEntry);
                $resultEntry->execute($dataEntry);
            } catch (PDOException $e) {
            }

            if ($resultEntry->rowCount() > 0) {
                echo '<h2>';
                echo __($guid, 'Recent Marks');
                echo '</h2>';

                echo '<ol>';
                $count = 0;

                while ($rowEntry = $resultEntry->fetch() and $count < 5) {
                    echo "<li><a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Markbook/markbook_view.php#'.$rowEntry['gibbonMarkbookEntryID']."'>".$rowEntry['course'].'.'.$rowEntry['class']."<br/><span style='font-size: 85%; font-style: italic'>".$rowEntry['name'].'</span></a></li>';
                    ++$count;
                }

                echo '</ol>';
            }
        }
    }

    //Show My Classes
    if ($_SESSION[$guid]['address'] == '' and isset($_SESSION[$guid]['username'])) {
        try {
            $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
            $sql = "SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourseClass.gibbonCourseClassID FROM gibbonCourse, gibbonCourseClass, gibbonCourseClassPerson WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID AND gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND NOT role LIKE '% - Left%' ORDER BY course, class";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
        }

        if ($result->rowCount() > 0) {
            echo "<h2 style='margin-bottom: 10px'  class='sidebar'>";
            echo __($guid, 'My Classes');
            echo '</h2>';

            echo "<table class='mini' cellspacing='0' style='width: 100%; table-layout: fixed;'>";
            echo "<tr class='head'>";
            echo "<th style='width: 36%; font-size: 85%; text-transform: uppercase'>";
            echo __($guid, 'Class');
            echo '</th>';
            if (isActionAccessible($guid, $connection2, '/modules/Planner/planner.php')) {
                echo "<th style='width: 16%; font-size: 60%; text-align: center; text-transform: uppercase'>";
                echo __($guid, 'Plan');
                echo '</th>';
            }
            if (getHighestGroupedAction($guid, '/modules/Markbook/markbook_view.php', $connection2) == 'View Markbook_allClassesAllData') {
                echo "<th style='width: 16%; font-size: 60%; text-align: center; text-transform: uppercase'>";
                echo __($guid, 'Mark');
                echo '</th>';
            }
            echo "<th style='width: 16%; font-size: 60%; text-align: center; text-transform: uppercase'>";
            echo __($guid, 'People');
            echo '</th>';
            if (isActionAccessible($guid, $connection2, '/modules/Planner/planner.php')) {
                echo "<th style='width: 16%; font-size: 60%; text-align: center; text-transform: uppercase'>";
                echo __($guid, 'Tasks');
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
                echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Departments/department_course_class.php&gibbonCourseClassID='.$row['gibbonCourseClassID']."'>".$row['course'].'.'.$row['class'].'</a>';
                echo '</td>';
                if (isActionAccessible($guid, $connection2, '/modules/Planner/planner.php')) {
                    echo "<td style='text-align: center'>";
                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Planner/planner.php&gibbonCourseClassID='.$row['gibbonCourseClassID']."&viewBy=class'><img style='margin-top: 3px' title='".__($guid, 'View Planner')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/planner.png'/></a> ";
                    echo '</td>';
                }
                if (getHighestGroupedAction($guid, '/modules/Markbook/markbook_view.php', $connection2) == 'View Markbook_allClassesAllData') {
                    echo "<td style='text-align: center'>";
                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Markbook/markbook_view.php&gibbonCourseClassID='.$row['gibbonCourseClassID']."'><img style='margin-top: 3px' title='".__($guid, 'View Markbook')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/markbook.png'/></a> ";
                    echo '</td>';
                }
                echo "<td style='text-align: center'>";
                echo "<a href='index.php?q=/modules/Departments/department_course_class.php&gibbonCourseClassID=".$row['gibbonCourseClassID']."&subpage=Participants'><img title='".__($guid, 'Participants')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/attendance.png'/></a>";
                echo '</td>';
                if (isActionAccessible($guid, $connection2, '/modules/Planner/planner.php')) {
                    echo "<td style='text-align: center'>";
                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Planner/planner_deadlines.php&gibbonCourseClassIDFilter='.$row['gibbonCourseClassID']."'><img style='margin-top: 3px' title='".__($guid, 'View Homework')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/homework.png'/></a> ";
                    echo '</td>';
                }
                echo '</tr>';
            }
            echo '</table>';
        }
    }

    //Show tag cloud
    if ($_SESSION[$guid]['address'] == '' and isActionAccessible($guid, $connection2, '/modules/Resources/resources_view.php')) {
        include './modules/Resources/moduleFunctions.php';
        echo "<h2 class='sidebar'>";
        echo __($guid, 'Resource Tags');
        echo '</h2>';
        echo getTagCloud($guid, $connection2, 20);
        echo "<p style='margin-bototm: 20px; text-align: right'>";
        echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Resources/resources_view.php'>".__($guid, 'View Resources').'</a>';
        echo '</p>';
    }

    //Show role switcher if user has more than one role
    if (isset($_SESSION[$guid]['username'])) {
        if (count($_SESSION[$guid]['gibbonRoleIDAll']) > 1 and $_SESSION[$guid]['address'] == '') {
            echo "<h2 style='margin-bottom: 10px' class='sidebar'>";
            echo __($guid, 'Role Switcher');
            echo '</h2>';

            echo '<p>';
            echo __($guid, 'You have multiple roles within the system. Use the list below to switch role:');
            echo '</p>';

            echo '<ul>';
            for ($i = 0; $i < count($_SESSION[$guid]['gibbonRoleIDAll']); ++$i) {
                if ($_SESSION[$guid]['gibbonRoleIDAll'][$i][0] == $_SESSION[$guid]['gibbonRoleIDCurrent']) {
                    echo "<li><a href='roleSwitcherProcess.php?gibbonRoleID=".$_SESSION[$guid]['gibbonRoleIDAll'][$i][0]."'>".__($guid, $_SESSION[$guid]['gibbonRoleIDAll'][$i][1]).'</a> <i>'.__($guid, '(Active)').'</i></li>';
                } else {
                    echo "<li><a href='roleSwitcherProcess.php?gibbonRoleID=".$_SESSION[$guid]['gibbonRoleIDAll'][$i][0]."'>".__($guid, $_SESSION[$guid]['gibbonRoleIDAll'][$i][1]).'</a></li>';
                }
            }
            echo '</ul>';
        }
    }

    if ($_SESSION[$guid]['sidebarExtra'] != '' and $_SESSION[$guid]['sidebarExtraPosition'] == 'bottom') {
        echo "<div class='sidebarExtra'>";
        echo $_SESSION[$guid]['sidebarExtra'];
        echo '</div>';
    }
}

//Format address according to supplied inputs
function addressFormat($address, $addressDistrict, $addressCountry)
{
    $return = false;

    if ($address != '') {
        $return .= $address;
        if ($addressDistrict != '') {
            $return .= ', '.$addressDistrict;
        }
        if ($addressCountry != '') {
            $return .= ', '.$addressCountry;
        }
    }

    return $return;
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
            $output .= sprintf(__($guid, 'Maximum size for all files: %1$sMB'), $post).'<br/>';
        } else {
            $output .= sprintf(__($guid, 'Maximum size for all files: %1$sMB'), $file).'<br/>';
        }
    } else {
        if ($post < $file) {
            $output .= sprintf(__($guid, 'Maximum file size: %1$sMB'), $post).'<br/>';
        } else {
            $output .= sprintf(__($guid, 'Maximum file size: %1$sMB'), $file).'<br/>';
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
        $output[1] = __($guid, $rowAlert['name']);
        $output[2] = $rowAlert['nameShort'];
        $output[3] = $rowAlert['color'];
        $output[4] = $rowAlert['colorBG'];
    }

    return $output;
}

//Gets age from date of birth, in days and months, from Unix timestamp
function getAge($guid, $stamp, $short = false, $yearsOnly = false)
{
    $output = '';
    $diff = time() - $stamp;
    $years = floor($diff / 31556926);
    $months = floor(($diff - ($years * 31556926)) / 2629743.83);
    if ($short == true) {
        $output = $years.__($guid, 'y').', '.$months.__($guid, 'm');
    } else {
        $output = $years.' '.__($guid, 'years').', '.$months.' '.__($guid, 'months');
    }
    if ($yearsOnly == true) {
        $output = $years;
    }

    return $output;
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

//Converts a specified date (YYYY-MM-DD) into a UNIX timestamp, factoring in timezones
function dateConvertToTimestamp($date)
{
    list($dateYear, $dateMonth, $dateDay) = explode('-', $date);
    $timestamp = mktime(0, 0, 0, $dateMonth, $dateDay, $dateYear);

    return $timestamp;
}

//Converts a specified date (YYYY-MM-DD) into a UNIX timestamp, at GMT
function dateConvertToTimestampGM($date)
{
    list($dateYear, $dateMonth, $dateDay) = explode('-', $date);
    $timestamp = gmmktime(0, 0, 0, $dateMonth, $dateDay, $dateYear);

    return $timestamp;
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

//DEPRECATED IN VERSION 8 IN FAVOUR OF getUserPhoto
//Prints a given user photo, or a blank if not available
function printUserPhoto($guid, $path, $size)
{
    $sizeStyle = "style='width: 75px; height: 100px'";
    if ($size == 240) {
        $sizeStyle = "style='width: 240px; height: 320px'";
    }
    if ($path == '' or file_exists($_SESSION[$guid]['absolutePath'].'/'.$path) == false) {
        echo "<img $sizeStyle class='user' src='".$_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName'].'/img/anonymous_'.$size.".jpg'/><br/>";
    } else {
        echo "<img $sizeStyle class='user' src='".$_SESSION[$guid]['absoluteURL']."/$path'/><br/>";
    }
}

//Gets a given user photo, or a blank if not available
function getUserPhoto($guid, $path, $size)
{
    $output = '';
    $sizeStyle = "style='width: 75px; height: 100px'";
    if ($size == 240) {
        $sizeStyle = "style='width: 240px; height: 320px'";
    }
    if ($path == '' or file_exists($_SESSION[$guid]['absolutePath'].'/'.$path) == false) {
        $output = "<img $sizeStyle class='user' src='".$_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName'].'/img/anonymous_'.$size.".jpg'/><br/>";
    } else {
        $output = "<img $sizeStyle class='user' src='".$_SESSION[$guid]['absoluteURL']."/$path'/><br/>";
    }

    return $output;
}

//Gets Members of a roll group and prints them as a table.
//Three modes: normal (roll order, surname, firstName), surname (surname, preferredName), preferredName (preferredNam, surname)
function getRollGroupTable($guid, $gibbonRollGroupID, $columns, $connection2, $confidential = true, $orderBy = 'Normal')
{
    $return = false;

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

    $return .= "<table class='noIntBorder' cellspacing='0' style='width:100%'>";
    $count = 0;

    if ($confidential) {
        $return .= '<tr>';
        $return .= "<td style='text-align: right' colspan='$columns'>";
        $return .= "<input checked type='checkbox' name='confidential' class='confidential' id='confidential".$gibbonRollGroupID."' value='Yes' />";
        $return .= "<span style='font-size: 85%; font-weight: normal; font-style: italic'> ".__($guid, 'Show Confidential Data').'</span>';
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
            $return .= "<img title='".sprintf(__($guid, '%1$s  birthday today!'), $rowRollGroup['preferredName'].'&#39;s')."' style='z-index: 99; margin: -20px 0 0 74px; width: 25px; height: 25px' src='".$_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName']."/img/gift_pink.png'/>";
        } elseif ($daysUntilNextBirthday > 0 and $daysUntilNextBirthday < 8) {
            $return .= "<img title='";
            if ($daysUntilNextBirthday != 1) {
                $return .= sprintf(__($guid, '%1$s days until %2$s birthday!'), $daysUntilNextBirthday, $rowRollGroup['preferredName'].'&#39;s');
            } else {
                $return .= sprintf(__($guid, '%1$s day until %2$s birthday!'), $daysUntilNextBirthday, $rowRollGroup['preferredName'].'&#39;s');
            }
            $return .= "' style='z-index: 99; margin: -20px 0 0 74px; width: 25px; height: 25px' src='".$_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName']."/img/gift.png'/>";
        }

        $return .= "<div style='padding-top: 5px'><b><a href='index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=".$rowRollGroup['gibbonPersonID']."'>".formatName('', $rowRollGroup['preferredName'], $rowRollGroup['surname'], 'Student').'</a><br/><br/></div>';
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
            echo "<img title='".sprintf(__($guid, '%1$s  birthday today!'), $rowClassGroup['preferredName'].'&#39;s')."' style='z-index: 99; margin: -20px 0 0 74px; width: 25px; height: 25px' src='".$_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName']."/img/gift_pink.png'/>";
        } elseif ($daysUntilNextBirthday > 0 and $daysUntilNextBirthday < 8) {
            echo "<img title='$daysUntilNextBirthday ";
            if ($daysUntilNextBirthday != 1) {
                echo sprintf(__($guid, 'days until %1$s birthday!'), $rowClassGroup['preferredName'].'&#39;s');
            } else {
                echo sprintf(__($guid, 'day until %1$s birthday!'), $rowClassGroup['preferredName'].'&#39;s');
            }
            echo "' style='z-index: 99; margin: -20px 0 0 74px; width: 25px; height: 25px' src='".$_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName']."/img/gift.png'/>";
        }

        if ($rowClassGroup['role'] == 'Student') {
            echo "<div style='padding-top: 5px'><b><a href='index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=".$rowClassGroup['gibbonPersonID']."'>".formatName('', $rowClassGroup['preferredName'], $rowClassGroup['surname'], 'Student').'</a></b><br/>';
        } else {
            echo "<div style='padding-top: 5px'><b>".formatName($rowClassGroup['title'], $rowClassGroup['preferredName'], $rowClassGroup['surname'], 'Staff').'</b><br/>';
        }

        echo '<i>'.$rowClassGroup['role'].'</i><br/><br/></div>';
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
    if ($highestAction == 'View Student Profile_full') {
        if ($div == true) {
            $output .= "<div $divExtras style='width: 83px; text-align: right; height: ".$totalHeight."px; padding: 3px 0px; margin: auto'><b>";
        }

        //Individual Needs
        try {
            $dataAlert = array('gibbonPersonID' => $gibbonPersonID);
            $sqlAlert = 'SELECT * FROM gibbonINPersonDescriptor JOIN gibbonAlertLevel ON (gibbonINPersonDescriptor.gibbonAlertLevelID=gibbonAlertLevel.gibbonAlertLevelID) WHERE gibbonPersonID=:gibbonPersonID ORDER BY sequenceNumber DESC';
            $resultAlert = $connection2->prepare($sqlAlert);
            $resultAlert->execute($dataAlert);
        } catch (PDOException $e) {
        }
        if ($resultAlert->rowCount() > 0) {
            $rowAlert = $resultAlert->fetch();
            $highestLevel = __($guid, $rowAlert['name']);
            $highestColour = $rowAlert['color'];
            $highestColourBG = $rowAlert['colorBG'];
            if ($resultAlert->rowCount() == 1) {
                $title = $resultAlert->rowCount().' '.sprintf(__($guid, 'Individual Needs alert is set, with an alert level of %1$s.'), $rowAlert['name']);
            } else {
                $title = $resultAlert->rowCount().' '.sprintf(__($guid, 'Individual Needs alerts are set, up to a maximum alert level of %1$s.'), $rowAlert['name']);
            }
            $output .= "<a style='font-size: ".$fontSize.'px; color: #'.$highestColour."; text-decoration: none' href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Students/student_view_details.php&gibbonPersonID='.$gibbonPersonID."&subpage=Individual Needs'><div title='$title' style='float: right; text-align: center; vertical-align: middle; max-height: ".$height.'px; height: '.$height.'px; width: '.$width.'px; border-top: 2px solid #'.$highestColour.'; margin-right: 2px; background-color: #'.$highestColourBG."'>".__($guid, 'IN').'</div></a>';
        }

        //Academic
        $gibbonAlertLevelID = '';
        try {
            $dataAlert = array('gibbonPersonIDStudent' => $gibbonPersonID, 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
            $sqlAlert = "SELECT * FROM gibbonMarkbookEntry JOIN gibbonMarkbookColumn ON (gibbonMarkbookEntry.gibbonMarkbookColumnID=gibbonMarkbookColumn.gibbonMarkbookColumnID) JOIN gibbonCourseClass ON (gibbonMarkbookColumn.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonPersonIDStudent=:gibbonPersonIDStudent AND (attainmentConcern='Y' OR effortConcern='Y') AND complete='Y' AND gibbonSchoolYearID=:gibbonSchoolYearID";
            $resultAlert = $connection2->prepare($sqlAlert);
            $resultAlert->execute($dataAlert);
        } catch (PDOException $e) {
            $_SESSION[$guid]['sidebarExtra'] .= "<div class='error'>".$e->getMessage().'</div>';
        }
        if ($resultAlert->rowCount() > 1 and $resultAlert->rowCount() <= 4) {
            $gibbonAlertLevelID = 003;
        } elseif ($resultAlert->rowCount() > 4 and $resultAlert->rowCount() <= 8) {
            $gibbonAlertLevelID = 002;
        } elseif ($resultAlert->rowCount() > 8) {
            $gibbonAlertLevelID = 001;
        }
        if ($gibbonAlertLevelID != '') {
            $alert = getAlert($guid, $connection2, $gibbonAlertLevelID);
            if ($alert != false) {
                $title = sprintf(__($guid, 'Student has a %1$s alert for academic concern in the current academic year.'), __($guid, $alert['name']));
                $output .= "<a style='font-size: ".$fontSize.'px; color: #'.$alert['color']."; text-decoration: none' href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Students/student_view_details.php&gibbonPersonID='.$gibbonPersonID.'&subpage=Markbook&filter='.$_SESSION[$guid]['gibbonSchoolYearID']."'><div title='$title' style='float: right; text-align: center; vertical-align: middle; max-height: ".$height.'px; height: '.$height.'px; width: '.$width.'px; border-top: 2px solid #'.$alert['color'].'; margin-right: 2px; background-color: #'.$alert['colorBG']."'>".__($guid, 'A').'</div></a>';
            }
        }

        //Behaviour
        $gibbonAlertLevelID = '';
        try {
            $dataAlert = array('gibbonPersonID' => $gibbonPersonID);
            $sqlAlert = "SELECT * FROM gibbonBehaviour WHERE gibbonPersonID=:gibbonPersonID AND type='Negative' AND date>'".date('Y-m-d', (time() - (24 * 60 * 60 * 60)))."'";
            $resultAlert = $connection2->prepare($sqlAlert);
            $resultAlert->execute($dataAlert);
        } catch (PDOException $e) {
            $_SESSION[$guid]['sidebarExtra'] .= "<div class='error'>".$e->getMessage().'</div>';
        }
        if ($resultAlert->rowCount() > 1 and $resultAlert->rowCount() <= 4) {
            $gibbonAlertLevelID = 003;
        } elseif ($resultAlert->rowCount() > 4 and $resultAlert->rowCount() <= 8) {
            $gibbonAlertLevelID = 002;
        } elseif ($resultAlert->rowCount() > 8) {
            $gibbonAlertLevelID = 001;
        }
        if ($gibbonAlertLevelID != '') {
            $alert = getAlert($guid, $connection2, $gibbonAlertLevelID);
            if ($alert != false) {
                $title = sprintf(__($guid, 'Student has a %1$s alert for behaviour over the past 60 days.'), __($guid, $alert['name']));
                $output .= "<a style='font-size: ".$fontSize.'px; color: #'.$alert['color']."; text-decoration: none' href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Students/student_view_details.php&gibbonPersonID='.$gibbonPersonID."&subpage=Behaviour'><div title='$title' style='float: right; text-align: center; vertical-align: middle; max-height: ".$height.'px; height: '.$height.'px; width: '.$width.'px; border-top: 2px solid #'.$alert['color'].'; margin-right: 2px; background-color: #'.$alert['colorBG']."'>".__($guid, 'B').'</div></a>';
            }
        }

        //Medical
        $alert = getHighestMedicalRisk($guid,  $gibbonPersonID, $connection2);
        if ($alert != false) {
            $highestLevel = $alert[1];
            $highestColour = $alert[3];
            $highestColourBG = $alert[4];
            $title = sprintf(__($guid, 'Medical alerts are set, up to a maximum of %1$s'), $highestLevel);
            $output .= "<a style='font-size: ".$fontSize.'px; color: #'.$highestColour."; text-decoration: none' href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Students/student_view_details.php&gibbonPersonID='.$gibbonPersonID."&subpage=Medical'><div title='$title' style='float: right; text-align: center; vertical-align: middle; max-height: ".$height.'px; height: '.$height.'px; width: '.$width.'px; border-top: 2px solid #'.$highestColour.'; margin-right: 2px; background-color: #'.$highestColourBG."'><b>".__($guid, 'M').'</b></div></a>';
        }

        //Privacy
        $privacySetting = getSettingByScope($connection2, 'User Admin', 'privacy');
        if ($privacySetting == 'Y' and $privacy != '') {
            $alert = getAlert($guid, $connection2, 001);
            $title = sprintf(__($guid, 'Privacy is required: %1$s'), $privacy);
            $output .= "<div title='$title' style='font-size: ".$fontSize.'px; float: right; text-align: center; vertical-align: middle; max-height: '.$height.'px; height: '.$height.'px; width: '.$width.'px; border-top: 2px solid #'.$alert['color'].'; margin-right: 2px; color: #'.$alert['color'].'; background-color: #'.$alert['colorBG']."'>".__($guid, 'P').'</div>';
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
function setLanguageSession($guid, $row)
{
    $_SESSION[$guid]['i18n']['gibboni18nID'] = $row['gibboni18nID'];
    $_SESSION[$guid]['i18n']['code'] = $row['code'];
    $_SESSION[$guid]['i18n']['name'] = $row['name'];
    $_SESSION[$guid]['i18n']['dateFormat'] = $row['dateFormat'];
    $_SESSION[$guid]['i18n']['dateFormatRegEx'] = $row['dateFormatRegEx'];
    $_SESSION[$guid]['i18n']['dateFormatPHP'] = $row['dateFormatPHP'];
    $_SESSION[$guid]['i18n']['maintainerName'] = $row['maintainerName'];
    $_SESSION[$guid]['i18n']['maintainerWebsite'] = $row['maintainerWebsite'];
    $_SESSION[$guid]['i18n']['rtl'] = $row['rtl'];
}

//Gets the desired setting, specified by name and scope.
function getSettingByScope($connection2, $scope, $name)
{
    $output = false;

    try {
        $data = array('scope' => $scope, 'name' => $name);
        $sql = 'SELECT * FROM gibbonSetting WHERE scope=:scope AND name=:name';
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
    }
    if ($result->rowCount() == 1) {
        $row = $result->fetch();
        $output = $row['value'];
    }

    return $output;
}

//Converts date from language-specific format to YYYY-MM-DD
function dateConvert($guid, $date)
{
    $output = false;

    if ($date != '') {
        if ($_SESSION[$guid]['i18n']['dateFormat'] == 'mm/dd/yyyy') {
            $firstSlashPosition = 2;
            $secondSlashPosition = 5;
            $output = substr($date, ($secondSlashPosition + 1)).'-'.substr($date, 0, $firstSlashPosition).'-'.substr($date, ($firstSlashPosition + 1), 2);
        } else {
            $output = date('Y-m-d', strtotime(str_replace('/', '-', $date)));
        }
    }

    return $output;
}

//Converts date from YYYY-MM-DD to language-specific format.
function dateConvertBack($guid, $date)
{
    $output = false;

    if ($date != '') {
        $timestamp = strtotime($date);
        if ($_SESSION[$guid]['i18n']['dateFormatPHP'] != '') {
            $output = date($_SESSION[$guid]['i18n']['dateFormatPHP'], $timestamp);
        } else {
            $output = date('d/m/Y', $timestamp);
        }
    }

    return $output;
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
    echo __($guid, 'Records').' '.(($page - 1) * $_SESSION[$guid]['pagination'] + 1).'-';
    if (($page * $_SESSION[$guid]['pagination']) > $total) {
        echo $total;
    } else {
        echo $page * $_SESSION[$guid]['pagination'];
    }
    echo ' '.__($guid, 'of').' '.$total.' : ';

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
            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q='.$_SESSION[$guid]['address']."&page=1&$get'>".__($guid, 'First').'</a> ';
            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q='.$_SESSION[$guid]['address'].'&page='.($page - 1)."&$get'>".__($guid, 'Previous').'</a> ';
        } else {
            echo __($guid, 'First').' '.__($guid, 'Previous').' ';
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
            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q='.$_SESSION[$guid]['address'].'&page='.($page + 1)."&$get'>".__($guid, 'Next').'</a> ';
            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q='.$_SESSION[$guid]['address'].'&page='.$totalPages."&$get'>".__($guid, 'Last').'</a> ';
        } else {
            echo __($guid, 'Next').' '.__($guid, 'Last');
        }
    }
    echo '</div>';
}

//Get list of user roles from database, and convert to array
function getRoleList($gibbonRoleIDAll, $connection2)
{
    @session_start();

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
        $output = __($guid, $row['category']);
    }

    return $output;
}

//GET THE CURRENT YEAR AND SET IT AS A GLOBAL VARIABLE
function setCurrentSchoolYear($guid,  $connection2)
{
    @session_start();

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
        die(__($guid, 'Your request failed due to a database error.'));
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

//THESE EXCEL FUNCTIONS ARE DEPCREATED IN V11 FOR REMOVAL BY V13. They have been replaced by the PHPExcel library in /lib
/*	Author: Raju Mazumder
    email:rajuniit@gmail.com
    Class:A simple class to export mysql query and whole html and php page to excel,doc etc
    Downloaded from: http://webscripts.softpedia.com/script/PHP-Clases/Export-To-Excel-50394.html
    License: GNU GPL
*/
class ExportToExcel
{
    public function setHeader($excel_file_name)//this function used to set the header variable
    {
        header('Content-type: application/octet-stream');//A MIME attachment with the content type "application/octet-stream" is a binary file.
        //Typically, it will be an application or a document that must be opened in an application, such as a spreadsheet or word processor.
        header("Content-Disposition: attachment; filename=$excel_file_name");//with this extension of file name you tell what kind of file it is.
        header('Pragma: no-cache');//Prevent Caching
        header('Expires: 0');//Expires and 0 mean that the browser will not cache the page on your hard drive
    }
    public function exportWithQuery($qry, $excel_file_name, $conn)//to export with query
    {
        $body = null;

        try {
            $tmprst = $conn->query($qry);
            $tmprst->setFetchMode(PDO::FETCH_NUM);
        } catch (PDOException $e) {
        }

        $header = "<center><table cellspacing='0' border=1px>";
        $num_field = $tmprst->columnCount();
        while ($row = $tmprst->fetch()) {
            $body .= '<tr>';
            for ($i = 0;$i < $num_field;++$i) {
                $body .= '<td>'.$row[$i].'</td>';
            }
            $body .= '</tr>';
        }

        $this->setHeader($excel_file_name);
        echo $header.$body.'</table>';
    }
    //Ross Parker added the ability to specify paramaters to pass into the file, via a session variable.
    public function exportWithPage($guid, $php_page, $excel_file_name, $params = '')
    {
        $this->setHeader($excel_file_name);
        $_SESSION[$guid]['exportToExcelParams'] = $params;
        require_once "$php_page";
    }
}

function formatPhone($num)
{ //Function by Zeromatik on StackOverflow
    $num = preg_replace('/[^0-9]/', '', $num);
    $len = strlen($num);

    if ($len == 7) {
        $num = preg_replace('/([0-9]{2})([0-9]{2})([0-9]{3})/', '$1 $2 $3', $num);
    } elseif ($len == 8) {
        $num = preg_replace('/([0-9]{4})([0-9]{4})/', '$1 $2', $num);
    } elseif ($len == 9) {
        $num = preg_replace('/([0-9]{3})([0-9]{2})([0-9]{2})([0-9]{2})/', '$1 - $2 $3 $4', $num);
    } elseif ($len == 10) {
        $num = preg_replace('/([0-9]{3})([0-9]{2})([0-9]{2})([0-9]{3})/', '$1 - $2 $3 $4', $num);
    }

    return $num;
}

function setLog($connection2, $gibbonSchoolYearID, $gibbonModuleID, $gibbonPersonID, $title, $array = null, $ip = null)
{
    if ((!is_array($array) && $array != null) || $title == null || $gibbonSchoolYearID == null) {
        return;
    }

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

function setLike($connection2, $moduleName, $gibbonSchoolYearID, $contextKeyName, $contextKeyValue, $gibbonPersonIDGiver, $gibbonPersonIDRecipient, $title, $comment = null)
{
    $return = true;

    try {
        $data = array('moduleName' => $moduleName, 'gibbonSchoolYearID' => $gibbonSchoolYearID, 'contextKeyName' => $contextKeyName, 'contextKeyValue' => $contextKeyValue, 'gibbonPersonIDGiver' => $gibbonPersonIDGiver, 'gibbonPersonIDRecipient' => $gibbonPersonIDRecipient, 'title' => $title, 'comment' => $comment);
        $sql = 'INSERT INTO gibbonLike SET gibbonModuleID=(SELECT gibbonModuleID FROM gibbonModule WHERE name=:moduleName), gibbonSchoolYearID=:gibbonSchoolYearID, contextKeyName=:contextKeyName, contextKeyValue=:contextKeyValue, gibbonPersonIDGiver=:gibbonPersonIDGiver, gibbonPersonIDRecipient=:gibbonPersonIDRecipient, title=:title, comment=:comment';
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        $return = false;
    }

    return $return;
}

function deleteLike($connection2, $moduleName, $contextKeyName, $contextKeyValue, $gibbonPersonIDGiver, $gibbonPersonIDRecipient, $title)
{
    $return = true;

    try {
        $data = array('moduleName' => $moduleName, 'contextKeyName' => $contextKeyName, 'contextKeyValue' => $contextKeyValue, 'gibbonPersonIDGiver' => $gibbonPersonIDGiver, 'gibbonPersonIDRecipient' => $gibbonPersonIDRecipient, 'title' => $title);
        $sql = 'DELETE FROM gibbonLike WHERE gibbonModuleID=(SELECT gibbonModuleID FROM gibbonModule WHERE name=:moduleName) AND contextKeyName=:contextKeyName AND contextKeyValue=:contextKeyValue AND gibbonPersonIDGiver=:gibbonPersonIDGiver AND gibbonPersonIDRecipient=:gibbonPersonIDRecipient AND title=:title';
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        $return = false;
    }

    return $return;
}

function countLikesByContext($connection2, $moduleName, $contextKeyName, $contextKeyValue)
{
    $return = null;

    try {
        $data = array('moduleName' => $moduleName, 'contextKeyName' => $contextKeyName, 'contextKeyValue' => $contextKeyValue);
        $sql = 'SELECT DISTINCT gibbonSchoolYearID, gibbonModuleID, contextKeyName, contextKeyValue, gibbonPersonIDGiver FROM gibbonLike WHERE gibbonModuleID=(SELECT gibbonModuleID FROM gibbonModule WHERE name=:moduleName) AND contextKeyName=:contextKeyName AND contextKeyValue=:contextKeyValue';
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        $return = false;
    }

    if ($return !== false) {
        $return = $result->rowCount();
    }

    return $return;
}

function countLikesByContextAndGiver($connection2, $moduleName, $contextKeyName, $contextKeyValue, $gibbonPersonIDGiver, $gibbonPersonIDRecipient = null)
{
    $return = null;

    try {
        if ($gibbonPersonIDRecipient == null) {
            $data = array('moduleName' => $moduleName, 'contextKeyName' => $contextKeyName, 'contextKeyValue' => $contextKeyValue, 'gibbonPersonIDGiver' => $gibbonPersonIDGiver);
            $sql = 'SELECT DISTINCT gibbonSchoolYearID, gibbonModuleID, contextKeyName, contextKeyValue FROM gibbonLike WHERE gibbonModuleID=(SELECT gibbonModuleID FROM gibbonModule WHERE name=:moduleName) AND contextKeyName=:contextKeyName AND contextKeyValue=:contextKeyValue AND gibbonPersonIDGiver=:gibbonPersonIDGiver';
        } else {
            $data = array('moduleName' => $moduleName, 'contextKeyName' => $contextKeyName, 'contextKeyValue' => $contextKeyValue, 'gibbonPersonIDGiver' => $gibbonPersonIDGiver, 'gibbonPersonIDRecipient' => $gibbonPersonIDRecipient);
            $sql = 'SELECT DISTINCT gibbonSchoolYearID, gibbonModuleID, contextKeyName, contextKeyValue FROM gibbonLike WHERE gibbonModuleID=(SELECT gibbonModuleID FROM gibbonModule WHERE name=:moduleName) AND contextKeyName=:contextKeyName AND contextKeyValue=:contextKeyValue AND gibbonPersonIDGiver=:gibbonPersonIDGiver AND gibbonPersonIDRecipient=:gibbonPersonIDRecipient';
        }
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        $return = false;
    }

    if ($return !== false) {
        $return = $result->rowCount();
    }

    return $return;
}

function countLikesByContextAndRecipient($connection2, $moduleName, $contextKeyName, $contextKeyValue, $gibbonPersonIDRecipient)
{
    $return = null;

    try {
        $data = array('moduleName' => $moduleName, 'contextKeyName' => $contextKeyName, 'contextKeyValue' => $contextKeyValue, 'gibbonPersonIDRecipient' => $gibbonPersonIDRecipient);
        $sql = 'SELECT DISTINCT gibbonSchoolYearID, gibbonModuleID, contextKeyName, contextKeyValue, gibbonPersonIDGiver FROM gibbonLike WHERE gibbonModuleID=(SELECT gibbonModuleID FROM gibbonModule WHERE name=:moduleName) AND contextKeyName=:contextKeyName AND contextKeyValue=:contextKeyValue  AND gibbonPersonIDRecipient=:gibbonPersonIDRecipient';
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        $return = false;
    }

    if ($return !== false) {
        $return = $result->rowCount();
    }

    return $return;
}

//$mode can be either "count" to get a numeric count, or "result" to get a result set
function countLikesByRecipient($connection2, $gibbonPersonIDRecipient, $mode = 'count', $gibbonSchoolYearID)
{
    $return = null;

    try {
        $data = array('gibbonPersonIDRecipient' => $gibbonPersonIDRecipient, 'gibbonSchoolYearID' => $gibbonSchoolYearID);
        if ($mode == 'count') {
            $sql = 'SELECT * FROM gibbonLike WHERE gibbonPersonIDRecipient=:gibbonPersonIDRecipient AND gibbonSchoolYearID=:gibbonSchoolYearID';
        } else {
            $sql = 'SELECT gibbonLike.*, gibbonPersonID, image_240, gibbonRoleIDPrimary, preferredName, surname FROM gibbonLike JOIN gibbonPerson ON (gibbonLike.gibbonPersonIDGiver=gibbonPerson.gibbonPersonID) WHERE gibbonPersonIDRecipient=:gibbonPersonIDRecipient AND gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY timestamp DESC';
        }
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        $return = false;
    }

    if ($mode == 'count') {
        $return = $result->rowCount();
    } else {
        $return = $result;
    }

    return $return;
}

/*
Easy Return Display Processing.
Arguments:
    $guid: The guid of your Gibbon Install.
    $return: This should be the return value of the process.
    $editLink: (Optional) This should be a link. The link will appended to the end of a success0 return.
    $customReturns: (Optional) This should be an array. The array allows you to set custom return checks and messages. Set the array key to the return name and the value to the return message.
Default returns:
    success0: This is a default success message for adding a new record.
    error0: This is a default error message for invalid permission for an action.
    error1: This is a default error message for invalid inputs.
    error2: This is a defualt error message for a database error.
    warning0: This is a default warning message for a extra data failing to save.
    warning1: This is a default warning message for a successful request, where certain data was not save properly.
*/
function returnProcess($guid, $return, $editLink = null, $customReturns = null)
{
    if (isset($return)) {
        $class = 'error';
        $returnMessage = 'Unknown Return';
        $returns = array();
        $returns['success0'] = __($guid, 'Your request was completed successfully.');
        $returns['error0'] = __($guid, 'Your request failed because you do not have access to this action.');
        $returns['error1'] = __($guid, 'Your request failed because your inputs were invalid.');
        $returns['error2'] = __($guid, 'Your request failed due to a database error.');
        $returns['error3'] = __($guid, 'Your request failed because your inputs were invalid.');
        $returns['error4'] = __($guid, 'Your request failed because your passwords did not match.');
        $returns['error5'] = __($guid, 'Your request failed because there are no records to show.');
        $returns['error6'] = __($guid, 'Your request was completed successfully, but one or more images were the wrong size and so were not saved.');
        $returns['warning0'] = __($guid, 'Your optional extra data failed to save.');
        $returns['warning1'] = __($guid, 'Your request was successful, but some data was not properly saved.');
        $returns['warning2'] = __($guid, 'Your request was successful, but some data was not properly deleted.');

        if (isset($customReturns)) {
            if (is_array($customReturns)) {
                $customReturnKeys = array_keys($customReturns);
                foreach ($customReturnKeys as $customReturnKey) {
                    $customReturn = __($guid, 'Unknown Return');
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
                }
                break;
            }
        }
        if ($class == 'success' && $editLink != null) {
            $returnMessage .= ' '.sprintf(__($guid, 'You can edit your newly created record %1$shere%2$s.'), "<a href='$editLink'>", '</a>');
        }

        echo "<div class='$class'>";
        echo $returnMessage;
        echo '</div>';
    }
}
?>
