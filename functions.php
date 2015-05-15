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

//Takes the provided string, and uses a tinymce style valid_elements string to strip out unwanted tags
//Not complete, as it does not strip out unwanted options, just whole tags.
function tinymceStyleStripTags($string, $connection2) {
	$return="" ;
	
	$comment=html_entity_decode($string) ;
	$allowableTags=getSettingByScope($connection2, "System", "allowableHTML") ;
	$allowableTags=preg_replace("/\[([^\[\]]|(?0))*]/" , "" , $allowableTags) ;
	$allowableTagTokens=explode(",", $allowableTags) ;
	$allowableTags="" ;
	foreach ($allowableTagTokens AS $allowableTagToken) {
		$allowableTags.="&lt;" . $allowableTagToken . "&gt;" ;
	}
	$allowableTags=html_entity_decode($allowableTags) ;
	$comment=strip_tags($comment, $allowableTags) ;
				
	return $comment ;
}

function getMinorLinks($connection2, $guid, $cacheLoad) {
	$return=FALSE ;
	
	if (isset($_SESSION[$guid]["username"])) {
		$return.=$_SESSION[$guid]["preferredName"] . " " . $_SESSION[$guid]["surname"] . " . " ;
		$return.="<a href='./logout.php'>" . _("Logout") . "</a> . <a href='./index.php?q=preferences.php'>" . _('Preferences') . "</a>" ;
		if ($_SESSION[$guid]["emailLink"]!="") {
			$return.=" . <a target='_blank' href='" . $_SESSION[$guid]["emailLink"] . "'>" . _('Email') . "</a>" ;
		}
		if ($_SESSION[$guid]["webLink"]!="") {
			$return.=" . <a target='_blank' href='" . $_SESSION[$guid]["webLink"] . "'>" . $_SESSION[$guid]["organisationNameShort"] . " " . _('Website') . "</a>" ;
		}
		if ($_SESSION[$guid]["website"]!="") {
			$return.=" . <a target='_blank' href='" . $_SESSION[$guid]["website"] . "'>" . _('My Website') . "</a>" ;
		}

		//STARS!
		if ($cacheLoad) {
			$_SESSION[$guid]["likeCount"]=0 ;
			$_SESSION[$guid]["likeCountTitle"]="" ;
			//Count crowd assessment likes
			try {
				$dataLike=array("gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"], "gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
				$sqlLike="SELECT * FROM gibbonCrowdAssessLike JOIN gibbonPlannerEntryHomework ON (gibbonCrowdAssessLike.gibbonPlannerEntryHomeworkID=gibbonPlannerEntryHomework.gibbonPlannerEntryHomeworkID) JOIN gibbonPlannerEntry ON (gibbonPlannerEntryHomework.gibbonPlannerEntryID=gibbonPlannerEntry.gibbonPlannerEntryID) JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonPlannerEntryHomework.gibbonPersonID=:gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID" ;
				$resultLike=$connection2->prepare($sqlLike);
				$resultLike->execute($dataLike); 
				if ($resultLike->rowCount()>0) {
					$_SESSION[$guid]["likeCount"]+=$resultLike->rowCount() ;
					$_SESSION[$guid]["likeCountTitle"].=_('Crowd Assessment') . ": " . $resultLike->rowCount() . ", " ;
				}
			}
			catch(PDOException $e) { $return.="<div class='error'>" . $e->getMessage() . "</div>" ; }

			//Count planner likes
			try {
				$dataLike=array("gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"], "gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
				$sqlLike="SELECT * FROM gibbonPlannerEntryLike JOIN gibbonPlannerEntry ON (gibbonPlannerEntryLike.gibbonPlannerEntryID=gibbonPlannerEntry.gibbonPlannerEntryID) JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND role='Teacher' AND gibbonSchoolYearID=:gibbonSchoolYearID" ;
				$resultLike=$connection2->prepare($sqlLike);
				$resultLike->execute($dataLike); 
				if ($resultLike->rowCount()>0) {
					$_SESSION[$guid]["likeCount"]+=$resultLike->rowCount() ;
					$_SESSION[$guid]["likeCountTitle"].=_('Planner') . ": " . $resultLike->rowCount() . ", " ;
				}
			}
			catch(PDOException $e) { $return.="<div class='error'>" . $e->getMessage() . "</div>" ; }

			//Count positive haviour
			try {
				$dataLike=array("gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"], "gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
				$sqlLike="SELECT * FROM gibbonBehaviour WHERE gibbonPersonID=:gibbonPersonID AND type='Positive' AND gibbonSchoolYearID=:gibbonSchoolYearID" ;
				$resultLike=$connection2->prepare($sqlLike);
				$resultLike->execute($dataLike); 
				if ($resultLike->rowCount()>0) {
					$_SESSION[$guid]["likeCount"]+=$resultLike->rowCount() ;
					$_SESSION[$guid]["likeCountTitle"].=_('Behaviour') . ": " . $resultLike->rowCount() . ", " ;
				}
			}
			catch(PDOException $e) { $return.="<div class='error'>" . $e->getMessage() . "</div>" ; }
		}

		//Spit out likes
		if (isset($_SESSION[$guid]["likeCount"])) {
			if ($_SESSION[$guid]["likeCount"]>0) {
				$return.=" . <a title='" . substr($_SESSION[$guid]["likeCountTitle"],0,-2) . "' href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=stars.php'>" . $_SESSION[$guid]["likeCount"] . " x <img style='margin-left: 2px; vertical-align: -60%' src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/like_on_flat.png'></a>" ;
			}
			else {
				$return.=" . " . $_SESSION[$guid]["likeCount"] . " x <img title='" . substr($_SESSION[$guid]["likeCountTitle"],0,-2) . "' style='margin-left: 2px; opacity: 0.8; vertical-align: -60%' src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/like_off.png'>" ;
			}
		}
		
		//GET & SHOW NOTIFICATIONS
		try {
			$dataNotifications=array("gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"], "gibbonPersonID2"=>$_SESSION[$guid]["gibbonPersonID"]); 
			$sqlNotifications="(SELECT gibbonNotification.*, gibbonModule.name AS source FROM gibbonNotification JOIN gibbonModule ON (gibbonNotification.gibbonModuleID=gibbonModule.gibbonModuleID) WHERE gibbonPersonID=:gibbonPersonID)
			UNION
			(SELECT gibbonNotification.*, 'System' AS source FROM gibbonNotification WHERE gibbonModuleID IS NULL AND gibbonPersonID=:gibbonPersonID2)
			ORDER BY timestamp DESC, source, text" ;
			$resultNotifications=$connection2->prepare($sqlNotifications);
			$resultNotifications->execute($dataNotifications); 
		}
		catch(PDOException $e) { $return.="<div class='error'>" . $e->getMessage() . "</div>" ; }

		//Refresh notifications every 15 seconds for staff, 120 seconds for everyone else
		$interval=120000 ;
		if ($_SESSION[$guid]["gibbonRoleIDCurrentCategory"]=="Staff") {
			$interval=15000 ;
		}
		$return.="<script type=\"text/javascript\">
			$(document).ready(function(){
				setInterval(function() {
					$(\"#notifications\").load(\"index_notification_ajax.php\");
				}, " . $interval . ");
			});
		</script>" ;

		$return.="<div id='notifications' style='display: inline'>" ;
			//CHECK FOR SYSTEM ALARM
			if (isset($_SESSION[$guid]["gibbonRoleIDCurrentCategory"])) {
				if ($_SESSION[$guid]["gibbonRoleIDCurrentCategory"]=="Staff") {
					$alarm=getSettingByScope($connection2, "System", "alarm") ;
					if ($alarm=="General" OR $alarm=="Lockdown") {
						if ($alarm=="General") {
							$return.="<audio loop autoplay>
								<source src=\"./audio/alarm_general.mp3\" type=\"audio/mpeg\">
							</audio>" ; 
							$return.="<script>alert('" . _('General Alarm!') . "') ;</script>" ;
						}
						else {
							$return.="<audio loop autoplay>
								<source src=\"./audio/alarm_lockdown.mp3\" type=\"audio/mpeg\">
							</audio>" ; 
							$return.="<script>alert('" . _('Lockdown Alarm!') . "') ;</script>" ;
						}
					}
				}
			}
			
			if ($resultNotifications->rowCount()>0) {
				$return.=" . <a title='" . _('Notifications') . "' href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=notifications.php'>" . $resultNotifications->rowCount() . " x " . "<img style='margin-left: 2px; vertical-align: -75%' src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/notifications_on.png'></a>" ;
			}
			else {
				$return.=" . 0 x " . "<img style='margin-left: 2px; opacity: 0.8; vertical-align: -75%' src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/notifications_off.png'>" ;
			}
		$return.="</div>" ;

		//MESSAGE WALL!
		if (isActionAccessible($guid, $connection2, "/modules/Messenger/messageWall_view.php")) {
			include "./modules/Messenger/moduleFunctions.php" ; 
	
			$addReturn=NULL ;
			if (isset($_GET["addReturn"])) {
				$addReturn=$_GET["addReturn"] ;
			}
			$updateReturn=NULL ;
			if (isset($_GET["updateReturn"])) {
				$updateReturn=$_GET["updateReturn"] ;
			}
			$deleteReturn=NULL ;
			if (isset($_GET["deleteReturn"])) {
				$deleteReturn=$_GET["deleteReturn"] ;
			}
			if ($cacheLoad OR (@$_GET["q"]=="/modules/Messenger/messenger_post.php" AND $addReturn=="success0") OR (@$_GET["q"]=="/modules/Messenger/messenger_postQuickWall.php" AND $addReturn=="success0") OR (@$_GET["q"]=="/modules/Messenger/messenger_manage_edit.php" AND $updateReturn=="success0") OR (@$_GET["q"]=="/modules/Messenger/messenger_manage.php" AND $deleteReturn=="success0")) {
				$messages=getMessages($guid, $connection2, "result") ;					
				$messages=unserialize($messages) ;
				try {
					$resultPosts=$connection2->prepare($messages[1]);
					$resultPosts->execute($messages[0]);  
				}
				catch(PDOException $e) { }	

				$_SESSION[$guid]["messageWallCount"]=0 ;
				if ($resultPosts->rowCount()>0) {
					$count=0 ;
					$output=array() ;
					$last="" ;
					while ($rowPosts=$resultPosts->fetch()) {
						if ($last==$rowPosts["gibbonMessengerID"]) {
							$output[($count-1)]["source"]=$output[($count-1)]["source"] . "<br/>" .$rowPosts["source"] ;
						}
						else {
							$output[$_SESSION[$guid]["messageWallCount"]]["photo"]=$rowPosts["image_75"] ;
							$output[$_SESSION[$guid]["messageWallCount"]]["subject"]=$rowPosts["subject"] ;
							$output[$_SESSION[$guid]["messageWallCount"]]["details"]=$rowPosts["body"] ;
							$output[$_SESSION[$guid]["messageWallCount"]]["author"]=formatName($rowPosts["title"], $rowPosts["preferredName"], $rowPosts["surname"], $rowPosts["category"]) ;
							$output[$_SESSION[$guid]["messageWallCount"]]["source"]=$rowPosts["source"] ;
							$output[$_SESSION[$guid]["messageWallCount"]]["gibbonMessengerID"]=$rowPosts["gibbonMessengerID"] ;

							$_SESSION[$guid]["messageWallCount"]++ ;
							$last=$rowPosts["gibbonMessengerID"] ;
							$count++ ;
						}	
					}
					$_SESSION[$guid]["messageWallOutput"]=$output ;
				}
			}
			
			$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Messenger/messageWall_view.php" ;
			if (isset($_SESSION[$guid]["messageWallCount"])==FALSE) {
				$return.=" . 0 x <img style='margin-left: 4px; opacity: 0.8; vertical-align: -75%' src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/messageWall_none.png'>" ;
			}
			else {
				if ($_SESSION[$guid]["messageWallCount"]<1) {
					$return.=" . 0 x <img style='margin-left: 4px; opacity: 0.8; vertical-align: -75%' src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/messageWall_none.png'>" ;
				}
				else {
					$return.=" . <a title='" . _('Message Wall') . "' href='$URL'>" . $_SESSION[$guid]["messageWallCount"] . " x <img style='margin-left: 4px; vertical-align: -75%' src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/messageWall.png'></a>" ;
					if ($_SESSION[$guid]["pageLoads"]==0 AND ($_SESSION[$guid]["messengerLastBubble"]==NULL OR $_SESSION[$guid]["messengerLastBubble"]<date("Y-m-d"))) {
						print $messageBubbleBGColor=getSettingByScope($connection2, "Messenger", "messageBubbleBGColor") ;
						$bubbleBG="" ;
						if ($messageBubbleBGColor!="") {
							$bubbleBG="; background-color: rgba(" . $messageBubbleBGColor . ")!important" ;
							$return.="<style>" ;
								$return.=".ui-tooltip, .arrow:after { $bubbleBG }" ;
							$return.="</style>" ;
							
						}
						$messageBubbleWidthType=getSettingByScope($connection2, "Messenger", "messageBubbleWidthType") ;
						$bubbleWidth=300 ;
						$bubbleLeft=770 ;
						if ($messageBubbleWidthType=="Wide") {
							$bubbleWidth=700 ;
							$bubbleLeft=370 ;
						}
						$return.="<div id='messageBubbleArrow' style=\"left: 1089px; top: 38px; z-index: 9999\" class='arrow top'></div>" ;
						$return.="<div id='messageBubble' style=\"left: " . $bubbleLeft . "px; top: 54px; width: " . $bubbleWidth . "px; min-width: " . $bubbleWidth . "px; max-width: " . $bubbleWidth . "px; min-height: 100px; text-align: center; padding-bottom: 10px\" class=\"ui-tooltip ui-widget ui-corner-all ui-widget-content\" role=\"tooltip\">" ;
							$return.="<div class=\"ui-tooltip-content\">" ;
								$return.="<div style='font-weight: bold; font-style: italic; font-size: 120%; margin-top: 10px; margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px dotted rgba(255,255,255,0.5); display: block'>" . _('New Messages') . "</div>" ;
								$test=count($output) ;
								if ($test>3) {
									$test=3 ;
								}
								for ($i=0; $i<$test; $i++) {
									$return.="<span style='font-size: 120%; font-weight: bold'>" ;
									if (strlen($output[$i]["subject"])<=30) {
										$return.=$output[$i]["subject"] ;
									}
									else {
										$return.=substr($output[$i]["subject"],0,30) . "..." ;
									}
					 
									 $return.="</span><br/>" ;
									$return.="<i>" . $output[$i]["author"] . "</i><br/><br/>" ;
								}
								if (count($output)>3) {
									$return.="<i>" . _('Plus more') . "...</i>" ;
								}
							$return.="</div>" ;
							$return.="<div style='text-align: right; margin-top: 20px; color: #666'>" ;
								$return.="<a onclick='$(\"#messageBubble\").hide(\"fade\", {}, 1); $(\"#messageBubbleArrow\").hide(\"fade\", {}, 1)' style='text-decoration: none; color: #666' class='thickbox' href='" . $URL . "'>" . _('Read All') . "</a> . " ;
								$return.="<a style='text-decoration: none; color: #666' onclick='$(\"#messageBubble\").hide(\"fade\", {}, 1000); $(\"#messageBubbleArrow\").hide(\"fade\", {}, 1000)' href='#'>" . _('Dismiss') . "</a>" ;
							$return.="</div>" ;
						$return.="</div>" ;
		
						$messageBubbleAutoHide=getSettingByScope($connection2, "Messenger", "messageBubbleAutoHide") ;
						if ($messageBubbleAutoHide!="N") {
							$return.="<script type=\"text/javascript\">" ;
								$return.="$(function() {" ;
									$return.="setTimeout(function() {" ;
										$return.="$(\"#messageBubble\").hide('fade', {}, 3000)" ;
									$return.="}, 10000);" ;
								$return.="});" ;
								$return.="$(function() {" ;
									$return.="setTimeout(function() {" ;
										$return.="$(\"#messageBubbleArrow\").hide('fade', {}, 3000)" ;
									$return.="}, 10000);" ;
								$return.="});" ;
							$return.="</script>" ;
						}
						
						try {
							$data=array("messengerLastBubble"=>date("Y-m-d"), "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"] ); 
							$sql="UPDATE gibbonPerson SET messengerLastBubble=:messengerLastBubble WHERE gibbonPersonID=:gibbonPersonID" ;
							$result=$connection2->prepare($sql);
							$result->execute($data); 
						}
						catch(PDOException $e) { }
					}
				}
			}
		}
	}
	
	return $return ;
}

//Gets the contents of a single dashboard, for the person specified
function getParentalDashboardContents($connection2, $guid, $gibbonPersonID) {
	$return=FALSE ;
	$alert=getAlert($connection2, 002) ;
	$entryCount=0 ;
											
	//PREPARE PLANNER SUMMARY
	$plannerOutput="<span style='font-size: 85%; font-weight: bold'>" . _('Today\'s Classes') . "</span> . <span style='font-size: 70%'><a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Planner/planner.php&search=" . $gibbonPersonID . "'>" . _('View Planner') . "</a></span>" ;
	
	if (isset($_GET["updateReturn"])) { $updateReturn=$_GET["updateReturn"] ; } else { $updateReturn="" ; }
	$updateReturnMessage="" ;
	$class="error" ;
	if (!($updateReturn=="")) {
		if ($updateReturn=="fail0") {
			$updateReturnMessage=_("Your request failed because you do not have access to this action.") ;	
		}
		else if ($updateReturn=="fail1") {
			$updateReturnMessage=_("Your request failed because your inputs were invalid.") ;	
		}
		else if ($updateReturn=="fail2") {
			$updateReturnMessage=_("Your request failed due to a database error.") ;	
		}
		else if ($updateReturn=="success0") {
			$updateReturnMessage=_("Your request was completed successfully.") ;	
			$class="success" ;
		}
		$plannerOutput.="<div class='$class'>" ;
			$plannerOutput.=$updateReturnMessage;
		$plannerOutput.="</div>" ;
	} 
	
	$classes=FALSE ;
	$date=date("Y-m-d") ;
	if (isSchoolOpen($guid, $date, $connection2)==TRUE AND isActionAccessible($guid, $connection2, "/modules/Planner/planner.php") AND $_SESSION[$guid]["username"]!="") {			
		try {
			$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "date"=>$date, "gibbonPersonID"=>$gibbonPersonID, "date2"=>$date, "gibbonPersonID2"=>$gibbonPersonID); 
			$sql="(SELECT gibbonPlannerEntry.gibbonPlannerEntryID, gibbonUnitID, gibbonHookID, gibbonPlannerEntry.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, timeStart, timeEnd, viewableStudents, viewableParents, homework, homeworkSubmission, homeworkCrowdAssess, role, date, summary, gibbonPlannerEntryStudentHomework.homeworkDueDateTime AS myHomeworkDueDateTime FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) LEFT JOIN gibbonPlannerEntryStudentHomework ON (gibbonPlannerEntryStudentHomework.gibbonPlannerEntryID=gibbonPlannerEntry.gibbonPlannerEntryID AND gibbonPlannerEntryStudentHomework.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND date=:date AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND NOT role='Student - Left' AND NOT role='Teacher - Left') UNION (SELECT gibbonPlannerEntry.gibbonPlannerEntryID, gibbonUnitID, gibbonHookID, gibbonPlannerEntry.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, timeStart, timeEnd, viewableStudents, viewableParents, homework, homeworkSubmission, homeworkCrowdAssess, role, date, summary, NULL AS myHomeworkDueDateTime FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonPlannerEntryGuest ON (gibbonPlannerEntryGuest.gibbonPlannerEntryID=gibbonPlannerEntry.gibbonPlannerEntryID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE date=:date2 AND gibbonPlannerEntryGuest.gibbonPersonID=:gibbonPersonID2) ORDER BY date, timeStart" ; 
			$result=$connection2->prepare($sql);
			$result->execute($data); 
		}
		catch(PDOException $e) { 
			$plannerOutput.="<div class='error'>" . $e->getMessage() . "</div>" ; 
		}
		if ($result->rowCount()>0) {
			$classes=TRUE ;
			$plannerOutput.="<table cellspacing='0' style='margin: 3px 0px; width: 100%'>" ;
				$plannerOutput.="<tr class='head'>" ;
					$plannerOutput.="<th>" ;
						$plannerOutput.=_("Class") . "<br/>" ;
					$plannerOutput.="</th>" ;
					$plannerOutput.="<th>" ;
						$plannerOutput.=_("Lesson") . "<br/>" ;
						$plannerOutput.="<span style='font-size: 85%; font-weight: normal; font-style: italic'>" . _("Summary") . "</span>" ;
					$plannerOutput.="</th>" ;
					$plannerOutput.="<th>" ;
						$plannerOutput.=_("Homework") ;
					$plannerOutput.="</th>" ;
					$plannerOutput.="<th>" ;
						$plannerOutput.=_("Like") ;
					$plannerOutput.="</th>" ;
					$plannerOutput.="<th>" ;
						$plannerOutput.=_("Action") ;
					$plannerOutput.="</th>" ;
				$plannerOutput.="</tr>" ;
				
				$count2=0;
				$rowNum="odd" ;
				while ($row=$result->fetch()) {
					if ($count2%2==0) {
						$rowNum="even" ;
					}
					else {
						$rowNum="odd" ;
					}
					$count2++ ;
					
					//Highlight class in progress
					if ((date("H:i:s")>$row["timeStart"]) AND (date("H:i:s")<$row["timeEnd"]) AND ($date)==date("Y-m-d")) {
						$rowNum="current" ;
					}
					
					//COLOR ROW BY STATUS!
					$plannerOutput.="<tr class=$rowNum>" ;
						$plannerOutput.="<td>" ;
							$plannerOutput.="<b>" . $row["course"] . "." . $row["class"] . "</b><br/>" ;
						$plannerOutput.="</td>" ;
						$plannerOutput.="<td>" ;
							$plannerOutput.=$row["name"] . "<br/>" ;
							$unit=getUnit($connection2, $row["gibbonUnitID"], $row["gibbonHookID"], $row["gibbonCourseClassID"]) ;
							if (isset($unit[0])) {
								$plannerOutput.=$unit[0] ;
								if ($unit[1]!="") {
									$plannerOutput.="<br/><i>" . $unit[1] . " " . _('Unit') . "</i><br/>" ;
								}
							}
							$plannerOutput.="<span style='font-size: 85%; font-weight: normal; font-style: italic'>" ;
								$plannerOutput.=$row["summary"] ;
							$plannerOutput.="</span>" ;
						$plannerOutput.="</td>" ;
						$plannerOutput.="<td>" ;
							if ($row["homework"]=="N" AND $row["myHomeworkDueDateTime"]=="") {
								$plannerOutput.=_("No") ;
							}
							else {
								if ($row["homework"]=="Y") {
									$plannerOutput.=_("Yes") . ": " . _("Teacher Recorded") . "<br/>" ;
									if ($row["homeworkSubmission"]=="Y") {
										$plannerOutput.="<span style='font-size: 85%; font-style: italic'>+" . _("Submission") . "</span><br/>" ;
										if ($row["homeworkCrowdAssess"]=="Y") {
											$plannerOutput.="<span style='font-size: 85%; font-style: italic'>+" . _("Crowd Assessment") . "</span><br/>" ;
										}
									}
								}
								if ($row["myHomeworkDueDateTime"]!="") {
									$plannerOutput.=_("Yes") . ": " . _("Student Recorded") . "</br>" ;
								}
							}
						$plannerOutput.="</td>" ;
						$plannerOutput.="<td>" ;
							try {
								$dataLike=array("gibbonPlannerEntryID"=>$row["gibbonPlannerEntryID"],"gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
								$sqlLike="SELECT * FROM gibbonPlannerEntryLike WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND gibbonPersonID=:gibbonPersonID" ;
								$resultLike=$connection2->prepare($sqlLike);
								$resultLike->execute($dataLike); 
							}
							catch(PDOException $e) { 
								$plannerOutput.="<div class='error'>" . $e->getMessage() . "</div>" ; 
							}
							if ($resultLike->rowCount()!=1) {
								$plannerOutput.="<a href='" . $_SESSION[$guid]["absoluteURL"] . "/modules/Planner/plannerProcess.php?gibbonPlannerEntryID=" . $row["gibbonPlannerEntryID"] . "&address=/modules/Planner/planner.php&viewBy=date&date=$date&gibbonPersonID=" . $gibbonPersonID . "&returnToIndex=Y'><img src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/like_off.png'></a>" ;
							}
							else {
								$plannerOutput.="<a href='" . $_SESSION[$guid]["absoluteURL"] . "/modules/Planner/plannerProcess.php?gibbonPlannerEntryID=" . $row["gibbonPlannerEntryID"] . "&address=/modules/Planner/planner.php&viewBy=date&date=$date&gibbonPersonID=" . $gibbonPersonID . "&returnToIndex=Y'><img src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/like_on.png'></a>" ;
							}
						$plannerOutput.="</td>" ;
						$plannerOutput.="<td>" ;
							$plannerOutput.="<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Planner/planner_view_full.php&search=" . $gibbonPersonID . "&viewBy=date&gibbonPlannerEntryID=" . $row["gibbonPlannerEntryID"] . "&date=$date&width=1000&height=550'><img title='" . _('View') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/plus.png'/></a> " ;
						$plannerOutput.="</td>" ;
					$plannerOutput.="</tr>" ;
				}
			$plannerOutput.="</table>" ;
		}
	}
	if ($classes==FALSE) {
		$plannerOutput.="<div style='margin-top: 2px' class='warning'>" ;
		$plannerOutput.=_("There are no records to display.") ;
		$plannerOutput.="</div>" ;
	}
	
	//PREPARE RECENT GRADES
	$gradesOutput="<div style='margin-top: 20px'><span style='font-size: 85%; font-weight: bold'>" . _('Recent Grades') . "</span> . <span style='font-size: 70%'><a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Markbook/markbook_view.php&search=" . $gibbonPersonID . "'>" . _('View Markbook') . "</a></span></div>" ;
	$grades=FALSE ;
	
	//Get alternative header names
	$attainmentAlternativeName=getSettingByScope($connection2, "Markbook", "attainmentAlternativeName") ;
	$attainmentAlternativeNameAbrev=getSettingByScope($connection2, "Markbook", "attainmentAlternativeNameAbrev") ;
	$effortAlternativeName=getSettingByScope($connection2, "Markbook", "effortAlternativeName") ;
	$effortAlternativeNameAbrev=getSettingByScope($connection2, "Markbook", "effortAlternativeNameAbrev") ;
	
	try {
		$dataEntry=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"],"gibbonPersonID"=>$gibbonPersonID); 
		$sqlEntry="SELECT *, gibbonMarkbookColumn.comment AS commentOn, gibbonMarkbookColumn.uploadedResponse AS uploadedResponseOn, gibbonMarkbookEntry.comment AS comment FROM gibbonMarkbookEntry JOIN gibbonMarkbookColumn ON (gibbonMarkbookEntry.gibbonMarkbookColumnID=gibbonMarkbookColumn.gibbonMarkbookColumnID) JOIN gibbonCourseClass ON (gibbonMarkbookColumn.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonIDStudent=:gibbonPersonID AND complete='Y' AND completeDate<='" . date("Y-m-d") . "' AND viewableParents='Y' ORDER BY completeDate DESC LIMIT 0, 3" ;
		$resultEntry=$connection2->prepare($sqlEntry);
		$resultEntry->execute($dataEntry); 
	}
	catch(PDOException $e) { 
		$gradesOutput.="<div class='error'>" . $e->getMessage() . "</div>" ; 
	}
	if ($resultEntry->rowCount()>0) {
		$showParentAttainmentWarning=getSettingByScope($connection2, "Markbook", "showParentAttainmentWarning" ) ; 
		$showParentEffortWarning=getSettingByScope($connection2, "Markbook", "showParentEffortWarning" ) ; 
		$grades=TRUE ;
		$gradesOutput.="<table cellspacing='0' style='margin: 3px 0px; width: 100%'>" ;
			$gradesOutput.="<tr class='head'>" ;
			$gradesOutput.="<th style='width: 120px'>" ;
				$gradesOutput.=_("Assessment") ;
			$gradesOutput.="</th>" ;
			$gradesOutput.="<th style='width: 75px'>" ;
				if ($attainmentAlternativeName!="") { $gradesOutput.=$attainmentAlternativeName ; } else { $gradesOutput.=_('Attainment') ; }
			$gradesOutput.="</th>" ;
			$gradesOutput.="<th style='width: 75px'>" ;
				if ($effortAlternativeName!="") { $gradesOutput.=$effortAlternativeName ; } else { $gradesOutput.=_('Effort') ; }
			$gradesOutput.="</th>" ;
			$gradesOutput.="<th>" ;
				$gradesOutput.=_("Comment") ;
			$gradesOutput.="</th>" ;
			$gradesOutput.="<th style='width: 75px'>" ;
				$gradesOutput.=_("Submission") ;
			$gradesOutput.="</th>" ;
		$gradesOutput.="</tr>" ;
		
		$count3=0 ;
		while ($rowEntry=$resultEntry->fetch()) {
			if ($count3%2==0) {
				$rowNum="even" ;
			}
			else {
				$rowNum="odd" ;
			}
			$count3++ ;
			
			$gradesOutput.="<a name='" . $rowEntry["gibbonMarkbookEntryID"] . "'></a>" ;

			$gradesOutput.="<tr class=$rowNum>" ;
				$gradesOutput.="<td>" ;
					$gradesOutput.="<span title='" . htmlPrep($rowEntry["description"]) . "'>" . $rowEntry["name"] . "</span><br/>" ;
					$gradesOutput.="<span style='font-size: 90%; font-style: italic; font-weight: normal'>" ;
					$gradesOutput.=_("Marked on") . " " . dateConvertBack($guid, $rowEntry["completeDate"]) . "<br/>" ;
					$gradesOutput.="</span>" ;
				$gradesOutput.="</td>" ;
				if ($rowEntry["attainment"]=="N" OR ($rowEntry["gibbonScaleIDAttainment"]=="" AND $rowEntry["gibbonRubricIDAttainment"]=="")) {
					$gradesOutput.="<td class='dull' style='color: #bbb; text-align: center'>" ;
						$gradesOutput.=_('N/A') ;
					$gradesOutput.="</td>" ;
				}
				else {
					$gradesOutput.="<td style='text-align: center'>" ;
						$attainmentExtra="" ;
						try {
							$dataAttainment=array("gibbonScaleID"=>$rowEntry["gibbonScaleIDAttainment"]); 
							$sqlAttainment="SELECT * FROM gibbonScale WHERE gibbonScaleID=:gibbonScaleID" ;
							$resultAttainment=$connection2->prepare($sqlAttainment);
							$resultAttainment->execute($dataAttainment);
						}
						catch(PDOException $e) { }
						if ($resultAttainment->rowCount()==1) {
							$rowAttainment=$resultAttainment->fetch() ;
							$attainmentExtra="<br/>" . _($rowAttainment["usage"]) ;
						}
						$styleAttainment="style='font-weight: bold'" ;
						if ($rowEntry["attainmentConcern"]=="Y" AND $showParentAttainmentWarning=="Y") {
							$styleAttainment="style='color: #" . $alert["color"] . "; font-weight: bold; border: 2px solid #" . $alert["color"] . "; padding: 2px 4px; background-color: #" . $alert["colorBG"] . "'" ;
						}	
						else if ($rowEntry["attainmentConcern"]=="P" AND $showParentAttainmentWarning=="Y") {
							$styleAttainment="style='color: #390; font-weight: bold; border: 2px solid #390; padding: 2px 4px; background-color: #D4F6DC'" ;
						}
						$gradesOutput.="<div $styleAttainment>" . $rowEntry["attainmentValue"] ;
							if ($rowEntry["gibbonRubricIDAttainment"]!="") {
								$gradesOutput.="<a class='thickbox' href='" . $_SESSION[$guid]["absoluteURL"] . "/fullscreen.php?q=/modules/Markbook/markbook_view_rubric.php&gibbonRubricID=" . $rowEntry["gibbonRubricIDAttainment"] . "&gibbonCourseClassID=" . $rowEntry["gibbonCourseClassID"] . "&gibbonMarkbookColumnID=" . $rowEntry["gibbonMarkbookColumnID"] . "&gibbonPersonID=" . $gibbonPersonID . "&mark=FALSE&type=attainment&width=1100&height=550'><img style='margin-bottom: -3px; margin-left: 3px' title='View Rubric' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/rubric.png'/></a>" ;
							}
						$gradesOutput.="</div>" ;
						if ($rowEntry["attainmentValue"]!="") {
							$gradesOutput.="<div class='detailItem' style='font-size: 75%; font-style: italic; margin-top: 2px'><b>" . htmlPrep(_($rowEntry["attainmentDescriptor"])) . "</b>" . _($attainmentExtra) . "</div>" ;
						}
					$gradesOutput.="</td>" ;
				}
				if ($rowEntry["effort"]=="N" OR ($rowEntry["gibbonScaleIDEffort"]=="" AND $rowEntry["gibbonRubricIDEffort"]=="")) {
					print "<td class='dull' style='color: #bbb; text-align: center'>" ;
						print _('N/A') ;
					print "</td>" ;
				}
				else {
					$gradesOutput.="<td style='text-align: center'>" ;
						$effortExtra="" ;
						try {
							$dataEffort=array("gibbonScaleID"=>$rowEntry["gibbonScaleIDEffort"]); 
							$sqlEffort="SELECT * FROM gibbonScale WHERE gibbonScaleID=:gibbonScaleID" ;
							$resultEffort=$connection2->prepare($sqlEffort);
							$resultEffort->execute($dataEffort); 
						}
						catch(PDOException $e) { }
						if ($resultEffort->rowCount()==1) {
							$rowEffort=$resultEffort->fetch() ;
							$effortExtra="<br/>" . _($rowEffort["usage"]) ;
						}
						$styleEffort="style='font-weight: bold'" ;
						if ($rowEntry["effortConcern"]=="Y" AND $showParentEffortWarning=="Y") {
							$styleEffort="style='color: #" . $alert["color"] . "; font-weight: bold; border: 2px solid #" . $alert["color"] . "; padding: 2px 4px; background-color: #" . $alert["colorBG"] . "'" ;
						}
						$gradesOutput.="<div $styleEffort>" . $rowEntry["effortValue"] ;
							if ($rowEntry["gibbonRubricIDEffort"]!="") {
								$gradesOutput.="<a class='thickbox' href='" . $_SESSION[$guid]["absoluteURL"] . "/fullscreen.php?q=/modules/Markbook/markbook_view_rubric.php&gibbonRubricID=" . $rowEntry["gibbonRubricIDEffort"] . "&gibbonCourseClassID=" . $rowEntry["gibbonCourseClassID"] . "&gibbonMarkbookColumnID=" . $rowEntry["gibbonMarkbookColumnID"] . "&gibbonPersonID=" . $gibbonPersonID . "&mark=FALSE&type=effort&width=1100&height=550'><img style='margin-bottom: -3px; margin-left: 3px' title='View Rubric' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/rubric.png'/></a>" ;
							}
						$gradesOutput.="</div>" ;
						if ($rowEntry["effortValue"]!="") {
							$gradesOutput.="<div class='detailItem' style='font-size: 75%; font-style: italic; margin-top: 2px'><b>" . htmlPrep(_($rowEntry["effortDescriptor"])) . "</b>" . _($effortExtra) . "</div>" ;
						}
					$gradesOutput.="</td>" ;
				}
				if ($rowEntry["commentOn"]=="N" AND $rowEntry["uploadedResponseOn"]=="N") {
					print "<td class='dull' style='color: #bbb; text-align: left'>" ;
						print _('N/A') ;
					print "</td>" ;
				}
				else {
					$gradesOutput.="<td>" ;
						if ($rowEntry["comment"]!="") {
							if (strlen($rowEntry["comment"])>50) {
								$gradesOutput.="<script type='text/javascript'>" ;	
									$gradesOutput.="$(document).ready(function(){" ;
										$gradesOutput.="\$(\".comment-$entryCount-$gibbonPersonID\").hide();" ;
										$gradesOutput.="\$(\".show_hide-$entryCount-$gibbonPersonID\").fadeIn(1000);" ;
										$gradesOutput.="\$(\".show_hide-$entryCount-$gibbonPersonID\").click(function(){" ;
										$gradesOutput.="\$(\".comment-$entryCount-$gibbonPersonID\").fadeToggle(1000);" ;
										$gradesOutput.="});" ;
									$gradesOutput.="});" ;
								$gradesOutput.="</script>" ;
								$gradesOutput.="<span>" . substr($rowEntry["comment"], 0, 50) . "...<br/>" ;
								$gradesOutput.="<a title='" . _('View Description') . "' class='show_hide-$entryCount-$gibbonPersonID' onclick='return false;' href='#'>" . _('Read more') . "</a></span><br/>" ;
							}
							else {
								$gradesOutput.=$rowEntry["comment"] ;
							}
							if ($rowEntry["response"]!="") {
								$gradesOutput.="<a title='" . _('Uploaded Response') . "' href='" . $_SESSION[$guid]["absoluteURL"] . "/" . $rowEntry["response"] . "'>" . _('Uploaded Response') . "</a><br/>" ;
							}
						}
					$gradesOutput.="</td>" ;
				}
				if ($rowEntry["gibbonPlannerEntryID"]==0) {
					$gradesOutput.="<td class='dull' style='color: #bbb; text-align: left'>" ;
						$gradesOutput.=_('N/A') ;
					$gradesOutput.="</td>" ;
				}
				else {
					try {
						$dataSub=array("gibbonPlannerEntryID"=>$rowEntry["gibbonPlannerEntryID"]); 
						$sqlSub="SELECT * FROM gibbonPlannerEntry WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND homeworkSubmission='Y'" ;
						$resultSub=$connection2->prepare($sqlSub);
						$resultSub->execute($dataSub);
					}
					catch(PDOException $e) { 
						$gradesOutput.="<div class='error'>" . $e->getMessage() . "</div>" ; 
					}
					if ($resultSub->rowCount()!=1) {
						$gradesOutput.="<td class='dull' style='color: #bbb; text-align: left'>" ;
							$gradesOutput.=_('N/A') ;
						$gradesOutput.="</td>" ;
					}
					else {
						$gradesOutput.="<td>" ;
							$rowSub=$resultSub->fetch() ;
							
							try {
								$dataWork=array("gibbonPlannerEntryID"=>$rowEntry["gibbonPlannerEntryID"], "gibbonPersonID"=>$gibbonPersonID); 
								$sqlWork="SELECT * FROM gibbonPlannerEntryHomework WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND gibbonPersonID=:gibbonPersonID ORDER BY count DESC" ;
								$resultWork=$connection2->prepare($sqlWork);
								$resultWork->execute($dataWork);
							}
							catch(PDOException $e) { 
								$gradesOutput.="<div class='error'>" . $e->getMessage() . "</div>" ; 
							}
							if ($resultWork->rowCount()>0) {
								$rowWork=$resultWork->fetch() ;
								
								if ($rowWork["status"]=="Exemption") {
									$linkText=_("Exemption") ;
								}
								else if ($rowWork["version"]=="Final") {
									$linkText=_("Final") ;
								}
								else {
									$linkText=_("Draft") . " " . $rowWork["count"] ;
								}
								
								$style="" ;
								$status="On Time" ;
								if ($rowWork["status"]=="Exemption") {
									$status=_("Exemption") ;
								}
								else if ($rowWork["status"]=="Late") {
									$style="style='color: #ff0000; font-weight: bold; border: 2px solid #ff0000; padding: 2px 4px'" ;
									$status=_("Late") ;
								}
								
								if ($rowWork["type"]=="File") {
									$gradesOutput.="<span title='" . $rowWork["version"] . ". $status. " . sprintf(_('Submitted at %1$s on %2$s'), substr($rowWork["timestamp"],11,5), dateConvertBack($guid, substr($rowWork["timestamp"],0,10))) . "' $style><a href='" . $_SESSION[$guid]["absoluteURL"] . "/" . $rowWork["location"] ."'>$linkText</a></span>" ;
								}
								else if ($rowWork["type"]=="Link") {
									$gradesOutput.="<span title='" . $rowWork["version"] . ". $status. " . sprintf(_('Submitted at %1$s on %2$s'), substr($rowWork["timestamp"],11,5), dateConvertBack($guid, substr($rowWork["timestamp"],0,10))) . "' $style><a target='_blank' href='" . $rowWork["location"] ."'>$linkText</a></span>" ;
								}
								else {
									$gradesOutput.="<span title='$status. " . sprintf(_('Recorded at %1$s on %2$s'), substr($rowWork["timestamp"],11,5), dateConvertBack($guid, substr($rowWork["timestamp"],0,10))) . "' $style>$linkText</span>" ;
								}
							}
							else {
								if (date("Y-m-d H:i:s")<$rowSub["homeworkDueDateTime"]) {
									$gradesOutput.="<span title='Pending'>" . _('Pending') . "</span>" ;
								}
								else {
									if ($row["dateStart"]>$rowSub["date"]) {
										$gradesOutput.="<span title='" . _('Student joined school after assessment was given.') . "' style='color: #000; font-weight: normal; border: 2px none #ff0000; padding: 2px 4px'>" . _('NA') . "</span>" ;
									}
									else {
										if ($rowSub["homeworkSubmissionRequired"]=="Compulsory") {
											$gradesOutput.="<div style='color: #ff0000; font-weight: bold; border: 2px solid #ff0000; padding: 2px 4px; margin: 2px 0px'>" . _('Incomplete') . "</div>" ;
										}
										else {
											$gradesOutput.=_("Not submitted online") ;
										}
									}
								}
							}	
						$gradesOutput.="</td>" ;
					}
				}
			$gradesOutput.="</tr>" ;
			if (strlen($rowEntry["comment"])>50) {
				$gradesOutput.="<tr class='comment-$entryCount-$gibbonPersonID' id='comment-$entryCount-$gibbonPersonID'>" ;
					$gradesOutput.="<td colspan=6>" ;
						$gradesOutput.=$rowEntry["comment"] ;
					$gradesOutput.="</td>" ;
				$gradesOutput.="</tr>" ;
			}
			$entryCount++ ;
		}
		
		$gradesOutput.="</table>" ;
	}
	if ($grades==FALSE) {
		$gradesOutput.="<div style='margin-top: 2px' class='warning'>" ;
			$gradesOutput.=_("There are no records to display.") ;
		$gradesOutput.="</div>" ;
	}
	
	//PREPARE UPCOMING DEADLINES
	$deadlinesOutput="<div style='margin-top: 20px'><span style='font-size: 85%; font-weight: bold'>" . _('Upcoming Deadlines') . "</span> . <span style='font-size: 70%'><a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Planner/planner_deadlines.php&search=" . $gibbonPersonID . "'>". _('View All Deadlines') . "</a></span></div>" ;
	$deadlines=FALSE ;
	
	try {
		$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"],"gibbonPersonID"=>$gibbonPersonID); 
		$sql="
		(SELECT 'teacherRecorded' AS type, gibbonPlannerEntryID, gibbonUnitID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, date, timeStart, timeEnd, viewableStudents, viewableParents, homework, homeworkDueDateTime, role FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND NOT role='Student - Left' AND NOT role='Teacher - Left' AND homework='Y' AND (role='Teacher' OR (role='Student' AND viewableStudents='Y')) AND homeworkDueDateTime>'" . date("Y-m-d H:i:s") . "' AND ((date<'" . date("Y-m-d") . "') OR (date='" . date("Y-m-d") . "' AND timeEnd<='" . date("H:i:s") . "')))
		UNION
		(SELECT 'studentRecorded' AS type, gibbonPlannerEntry.gibbonPlannerEntryID, gibbonUnitID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, date, timeStart, timeEnd, 'Y' AS viewableStudents, 'Y' AS viewableParents, 'Y' AS homework, gibbonPlannerEntryStudentHomework.homeworkDueDateTime, role FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) JOIN gibbonPlannerEntryStudentHomework ON (gibbonPlannerEntryStudentHomework.gibbonPlannerEntryID=gibbonPlannerEntry.gibbonPlannerEntryID AND gibbonPlannerEntryStudentHomework.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND NOT role='Student - Left' AND NOT role='Teacher - Left' AND (role='Teacher' OR (role='Student' AND viewableStudents='Y')) AND gibbonPlannerEntryStudentHomework.homeworkDueDateTime>'" . date("Y-m-d H:i:s") . "' AND ((date<'" . date("Y-m-d") . "') OR (date='" . date("Y-m-d") . "' AND timeEnd<='" . date("H:i:s") . "')))
		ORDER BY homeworkDueDateTime, type" ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { 
		$deadlinesOutput.="<div class='error'>" . $e->getMessage() . "</div>" ; 
	}

	if ($result->rowCount()>0) {
		$deadlines=TRUE ;
		$deadlinesOutput.="<ol style='margin-left: 15px'>" ;
		while ($row=$result->fetch()) {
			$diff=(strtotime(substr($row["homeworkDueDateTime"],0,10)) - strtotime(date("Y-m-d")))/86400 ;
			$style="style='padding-right: 3px;'" ;
			if ($diff<2) {
				$style="style='padding-right: 3px; border-right: 10px solid #cc0000'" ;	
			}
			else if ($diff<4) {
				$style="style='padding-right: 3px; border-right: 10px solid #D87718'" ;	
			}
			$deadlinesOutput.="<li $style>" ;
			$deadlinesOutput.="<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Planner/planner_view_full.php&search=" . $gibbonPersonID . "&gibbonPlannerEntryID=" . $row["gibbonPlannerEntryID"] . "&viewBy=date&date=$date&width=1000&height=550'>" . $row["course"] . "." . $row["class"] . "</a> " ;
			$deadlinesOutput.="<span style='font-style: italic'>" . sprintf(_('Due at %1$s on %2$s'), substr($row["homeworkDueDateTime"],11,5), dateConvertBack($guid, substr($row["homeworkDueDateTime"],0,10))) ;
			$deadlinesOutput.="</li>" ;
		}
		$deadlinesOutput.="</ol>" ;
	}
	
	if ($deadlines==FALSE) {
		$deadlinesOutput.="<div style='margin-top: 2px' class='warning'>" ;
		$deadlinesOutput.=_("There are no records to display.") ;
		$deadlinesOutput.="</div>" ;
	}
	
	
	//PREPARE TIMETABLE
	$timetable=FALSE ;
	$timetableOutput="" ;
	if (isActionAccessible($guid, $connection2, "/modules/Timetable/tt_view.php")) {
		$timetableOutput.="<div class='linkTop'>" ;
			$timetableOutput.="<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Timetable/tt.php'>" . _('View All Timetables') . "</a>" ;
		$timetableOutput.="</div>" ;
		$timetableOutputTemp=renderTT($guid, $connection2, $gibbonPersonID, NULL, NULL, dateConvertToTimestamp(date("Y-m-d")), "/index.php", "", TRUE) ;
		if ($timetableOutputTemp!=FALSE) {
			$timetable=TRUE ;
			$timetableOutput.=$timetableOutputTemp ;
		}
	}
	
	//PREPARE ACTIVITIES
	$activities=FALSE ;
	$activitiesOutput=FALSE ;
	if (!(isActionAccessible($guid, $connection2, "/modules/Activities/activities_view.php"))) {
		$activitiesOutput.="<div class='error'>" ;
			$activitiesOutput.=_("Your request failed because you do not have access to this action.");
		$activitiesOutput.="</div>" ;
	}
	else {
		$activities=TRUE ;
					
		$activitiesOutput.="<div class='linkTop'>" ;
			$activitiesOutput.="<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Activities/activities_view.php'>" . _('View Available Activities') . "</a>" ;
		$activitiesOutput.="</div>" ;
		
		$dateType=getSettingByScope($connection2, 'Activities', 'dateType') ;
		if ($dateType=="Term" ) {
			$maxPerTerm=getSettingByScope($connection2, 'Activities', 'maxPerTerm') ;
		}
		try {
			$dataYears=array("gibbonPersonID"=>$gibbonPersonID); 
			$sqlYears="SELECT * FROM gibbonStudentEnrolment JOIN gibbonSchoolYear ON (gibbonStudentEnrolment.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) WHERE gibbonSchoolYear.status='Current' AND gibbonPersonID=:gibbonPersonID ORDER BY sequenceNumber DESC" ;
			$resultYears=$connection2->prepare($sqlYears);
			$resultYears->execute($dataYears);
		}
		catch(PDOException $e) { 
			$activitiesOutput.="<div class='error'>" . $e->getMessage() . "</div>" ; 
		}

		if ($resultYears->rowCount()<1) {
			$activitiesOutput.="<div class='error'>" ;
			$activitiesOutput.=_("There are no records to display.") ;
			$activitiesOutput.="</div>" ;
		}
		else {
			$yearCount=0 ;
			while ($rowYears=$resultYears->fetch()) {
				$yearCount++ ;
				try {
					$data=array("gibbonPersonID"=>$gibbonPersonID, "gibbonSchoolYearID"=>$rowYears["gibbonSchoolYearID"]); 
					$sql="SELECT gibbonActivity.*, gibbonActivityStudent.status, NULL AS role FROM gibbonActivity JOIN gibbonActivityStudent ON (gibbonActivity.gibbonActivityID=gibbonActivityStudent.gibbonActivityID) WHERE gibbonActivityStudent.gibbonPersonID=:gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID AND active='Y' ORDER BY name" ; 
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					$activitiesOutput.="<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
	
				if ($result->rowCount()<1) {
					$activitiesOutput.="<div class='error'>" ;
					$activitiesOutput.=_("There are no records to display.") ;
					$activitiesOutput.="</div>" ;
				}
				else {
					$activitiesOutput.="<table cellspacing='0' style='width: 100%'>" ;
						$activitiesOutput.="<tr class='head'>" ;
							$activitiesOutput.="<th>" ;
								$activitiesOutput.=_("Activity") ;
							$activitiesOutput.="</th>" ;
							$options=getSettingByScope($connection2, "Activities", "activityTypes") ;
							if ($options!="") {
								$activitiesOutput.="<th>" ;
									$activitiesOutput.=_("Type") ;
								$activitiesOutput.="</th>" ;
							}
							$activitiesOutput.="<th>" ;
								if ($dateType!="Date") {
									$activitiesOutput.=_("Term") ;
								}
								else {
									$activitiesOutput.=_("Dates") ;
								}
							$activitiesOutput.="</th>" ;
							$activitiesOutput.="<th>" ;
								$activitiesOutput.=_("Status") ;
							$activitiesOutput.="</th>" ;
						$activitiesOutput.="</tr>" ;
				
						$count=0;
						$rowNum="odd" ;
						while ($row=$result->fetch()) {
							if ($count%2==0) {
								$rowNum="even" ;
							}
							else {
								$rowNum="odd" ;
							}
							$count++ ;
					
							//COLOR ROW BY STATUS!
							$activitiesOutput.="<tr class=$rowNum>" ;
								$activitiesOutput.="<td>" ;
									$activitiesOutput.=$row["name"] ;
								$activitiesOutput.="</td>" ;
								if ($options!="") {
									$activitiesOutput.="<td>" ;
										$activitiesOutput.=trim($row["type"]) ;
									$activitiesOutput.="</td>" ;
								}
								$activitiesOutput.="<td>" ;
									if ($dateType!="Date") {
										$terms=getTerms($connection2, $_SESSION[$guid]["gibbonSchoolYearID"], true) ;
										$termList="" ;
										for ($i=0; $i<count($terms); $i=$i+2) {
											if (is_numeric(strpos($row["gibbonSchoolYearTermIDList"], $terms[$i]))) {
												$termList.=$terms[($i+1)] . "<br/>" ;
											}
										}
										$activitiesOutput.=$termList ;
									}
									else {
										if (substr($row["programStart"],0,4)==substr($row["programEnd"],0,4)) {
											if (substr($row["programStart"],5,2)==substr($row["programEnd"],5,2)) {
												$activitiesOutput.=date("F", mktime(0, 0, 0, substr($row["programStart"],5,2))) . " " . substr($row["programStart"],0,4) ;
											}
											else {
												$activitiesOutput.=date("F", mktime(0, 0, 0, substr($row["programStart"],5,2))) . " - " . date("F", mktime(0, 0, 0, substr($row["programEnd"],5,2))) . "<br/>" . substr($row["programStart"],0,4) ;
											}
										}
										else {
											$activitiesOutput.=date("F", mktime(0, 0, 0, substr($row["programStart"],5,2))) . " " . substr($row["programStart"],0,4) . " -<br/>" . date("F", mktime(0, 0, 0, substr($row["programEnd"],5,2))) . " " . substr($row["programEnd"],0,4) ;
										}
									}
								$activitiesOutput.="</td>" ;
								$activitiesOutput.="<td>" ;
									if ($row["status"]!="") {
										$activitiesOutput.=$row["status"] ;
									}
									else {
										$activitiesOutput.="<i>" . _('NA') . "</i>" ;
									}
								$activitiesOutput.="</td>" ;
							$activitiesOutput.="</tr>" ;
						}
					$activitiesOutput.="</table>" ;		
				}
			}
		}
	}
	
	//GET HOOKS INTO DASHBOARD
	$hooks=array() ;
	try {
		$dataHooks=array(); 
		$sqlHooks="SELECT * FROM gibbonHook WHERE type='Parental Dashboard'" ;
		$resultHooks=$connection2->prepare($sqlHooks);
		$resultHooks->execute($dataHooks);
	}
	catch(PDOException $e) { 
		print "<div class='error'>" . $e->getMessage() . "</div>" ; 
	}
	if ($resultHooks->rowCount()>0) {
		$count=0 ;
		while ($rowHooks=$resultHooks->fetch()) {
			$options=unserialize($rowHooks["options"]) ;
			//Check for permission to hook
			try {
				$dataHook=array("gibbonRoleIDCurrent"=>$_SESSION[$guid]["gibbonRoleIDCurrent"], "sourceModuleName"=>$options["sourceModuleName"]); 
				$sqlHook="SELECT gibbonHook.name, gibbonModule.name AS module, gibbonAction.name AS action FROM gibbonHook JOIN gibbonModule ON (gibbonModule.name='" . $options["sourceModuleName"] . "') JOIN gibbonAction ON (gibbonAction.name='" . $options["sourceModuleAction"] . "') JOIN gibbonPermission ON (gibbonPermission.gibbonActionID=gibbonAction.gibbonActionID) WHERE gibbonAction.gibbonModuleID=(SELECT gibbonModuleID FROM gibbonModule WHERE gibbonPermission.gibbonRoleID=:gibbonRoleIDCurrent AND name=:sourceModuleName) AND gibbonHook.type='Parental Dashboard' ORDER BY name" ;
				$resultHook=$connection2->prepare($sqlHook);
				$resultHook->execute($dataHook);
			}
			catch(PDOException $e) { }
			if ($resultHook->rowCount()==1) {
				$rowHook=$resultHook->fetch() ;
				$hooks[$count]["name"]=$rowHooks["name"] ;
				$hooks[$count]["sourceModuleName"]=$rowHook["module"] ;
				$hooks[$count]["sourceModuleInclude"]=$options["sourceModuleInclude"] ;
				$count++ ;
			}
		}
	}
	
	
	if ($classes==FALSE AND $grades==FALSE AND $deadlines==FALSE AND $timetable==FALSE AND $activities==FALSE AND count($hooks)<1) {
		$return.="<div class='warning'>" ;
			$return.=_("There are no records to display.") ;
		$return.="</div>" ;
	}
	else {
		$return.="<script type='text/javascript'>" ;
			$return.="$(function() {" ;
				$return.="$( \"#" . $gibbonPersonID . "tabs\" ).tabs({" ;
					$return.="ajaxOptions: {" ;
						$return.="error: function( xhr, status, index, anchor ) {" ;
							$return.="$( anchor.hash ).html(" ;
								$return.="\"Couldn't load this tab.\" );" ;
						$return.="}" ;
					$return.="}" ;
				$return.="});" ;
			$return.="});" ;
		$return.="</script>" ;
	
		$return.="<div id='" . $gibbonPersonID . "tabs' style='margin: 0 0'>" ;
			$return.="<ul>" ;
				if ($classes!=FALSE OR $grades!=FALSE OR $deadlines!=FALSE) {
					$return.="<li><a href='#tabs1'>" . _('Learning Overview') . "</a></li>" ;
				}
				if ($timetable!=FALSE) {
					$return.="<li><a href='#tabs2'>" . _('Timetable') . "</a></li>" ;
				}
				if ($activities!=FALSE) {
					$return.="<li><a href='#tabs3'>" . _('Activities') . "</a></li>" ;
				}
				$tabCountExtra=3 ;
				foreach ($hooks AS $hook) {
					$tabCountExtra++ ;
					$return.="<li><a href='#tabs" . $tabCountExtra . "'>" . _($hook["name"]) . "</a></li>" ;
				}
			$return.="</ul>" ;
		
			if ($classes!=FALSE OR $grades!=FALSE OR $deadlines!=FALSE) {
				$return.="<div id='tabs1'>" ;
					$return.=$plannerOutput ;	
					$return.=$gradesOutput ;	
					$return.=$deadlinesOutput ;	
				$return.="</div>" ;
			}
			if ($timetable!=FALSE) {
				$return.="<div id='tabs2'>" ;
						$return.=$timetableOutput ;				
				$return.="</div>" ;
			}
			if ($activities!=FALSE) {
				$return.="<div id='tabs3'>" ;
					$return.=$activitiesOutput ;				
				$return.="</div>" ;
			}
			$tabCountExtra=3 ;
			foreach ($hooks AS $hook) {
				$tabCountExtra++ ;
				$return.="<div style='min-height: 100px' id='tabs" . $tabCountExtra . "'>" ;
					$include=$_SESSION[$guid]["absolutePath"] . "/modules/" . $hook["sourceModuleName"] . "/" . $hook["sourceModuleInclude"] ;
					if (!file_exists($include)) {
						$return.="<div class='error'>" ;
							$return.=_("The selected page cannot be displayed due to a hook error.") ;
						$return.="</div>" ;
					}
					else {
						$return.=include $include ;
					}
				$return.="</div>" ;
			}
		$return.="</div>" ;
	}
	
	return $return ;
}

//Sets a system-wide notification 
function setNotification($connection2, $guid, $gibbonPersonID, $text, $moduleName, $actionLink) {
	if ($moduleName=="") {
		$moduleName=NULL ;
	}
	
	//Check for existence of notification
	$dataCheck=array("gibbonPersonID"=>$gibbonPersonID, "text"=>$text, "actionLink"=>$actionLink, "name"=>$moduleName); 
	$sqlCheck="SELECT * FROM gibbonNotification WHERE gibbonPersonID=:gibbonPersonID AND text=:text AND actionLink=:actionLink AND gibbonModuleID=(SELECT gibbonModuleID FROM gibbonModule WHERE name=:name)" ;
	$resultCheck=$connection2->prepare($sqlCheck);
	$resultCheck->execute($dataCheck);
	
	if ($resultCheck->rowCount()==1) { //If exists, increment count
		$rowCheck=$resultCheck->fetch() ;
		$dataInsert=array("count"=>($rowCheck["count"]+1), "gibbonPersonID"=>$gibbonPersonID, "text"=>$text, "name"=>$moduleName); 
		$sqlInsert="UPDATE gibbonNotification SET count=:count WHERE gibbonPersonID=:gibbonPersonID AND text=:text AND gibbonModuleID=(SELECT gibbonModuleID FROM gibbonModule WHERE name=:name)" ;
		$resultInsert=$connection2->prepare($sqlInsert);
		$resultInsert->execute($dataInsert);
	}
	else { //If not exists, create
		$dataInsert=array("gibbonPersonID"=>$gibbonPersonID, "name"=>$moduleName, "text"=>$text, "actionLink"=>$actionLink); 
		$sqlInsert="INSERT INTO gibbonNotification SET gibbonPersonID=:gibbonPersonID, gibbonModuleID=(SELECT gibbonModuleID FROM gibbonModule WHERE name=:name), text=:text, actionLink=:actionLink, timestamp=now()" ;
		$resultInsert=$connection2->prepare($sqlInsert);
		$resultInsert->execute($dataInsert);
	}
	
	//Check for email notification preference and email address, and send if required
	$dataSelect=array("gibbonPersonID"=>$gibbonPersonID); 
	$sqlSelect="SELECT email, receiveNoticiationEmails FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonID AND receiveNoticiationEmails='Y' AND NOT email=''" ;
	$resultSelect=$connection2->prepare($sqlSelect);
	$resultSelect->execute($dataSelect);
	if ($resultSelect->rowCount()==1) {
		$rowSelect=$resultSelect->fetch() ;
		
		//Attempt email send
		$to=$rowSelect["email"] ;
		$subject=sprintf(_('You have received a notification on %1$s at %2$s (%3$s %4$s)'), $_SESSION[$guid]["systemName"], $_SESSION[$guid]["organisationNameShort"], date("H:i"), dateConvertBack($guid, date("Y-m-d"))) ;
		$body=_('Notification') . ": " . $text . "\n\n" ;
		$body.=sprintf(_('Login to %1$s and use the noticiation icon to check your new notification, or use the link below:'), $_SESSION[$guid]["systemName"]) . "\n\n" ;
		$body.=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=notifications.php\n\n" ;
		$headers="From: " . $_SESSION[$guid]["organisationAdministratorEmail"] ;
		mail($to, $subject, $body, $headers) ;
	}
}

//Expands Y and N to Yes and No, with and without translation
function ynExpander($yn, $translation=true) {
	$output="" ;
	
	if ($yn=="Y" OR $yn=="y") {
		$output="Yes" ;
	}
	else if ($yn=="N" OR $yn=="n") {
		$output="No" ;
	}
	else {
		$output="NA" ;
	}
	
	if ($translation==true) {
		$output=_($output) ;
	}
	
	return $output ;
}

//Accepts birthday in mysql date (YYYY-MM-DD) ;
function daysUntilNextBirthday($birthday) {
	$today=date("Y-m-d") ;
	$btsString=substr($today,0,4) . "-" . substr($birthday, 5) ;
	$bts=strtotime($btsString);
	$ts=time();
	
	if ($bts < $ts) {
		$bts=strtotime(date("y",strtotime("+1 year")) . "-" . substr($birthday, 5));
	}

	$days=ceil(($bts - $ts) / 86400);
	if ($days==365) {
		$days=0 ;
	}
	return $days ;
}

function getSmartWorkflowHelp($connection2, $guid, $step="") {
	$output=false ;
	
	try {
		$data=array("gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
		$sql="SELECT * FROM gibbonStaff WHERE gibbonPersonID=:gibbonPersonID" ; 
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { }
	if ($result->rowCount()==1) {
		$row=$result->fetch() ;
		if ($row["smartWorkflowHelp"]=="Y") {
			$output="<div id='smartWorkflowHelp' class='message' style='padding-top: 14px'>" ;
				$output.="<div style='padding: 0 7px'>" ;
					$output.="<span style='font-size: 175%'><i><b>" . _('Smart Workflow') . "</b></i> " . _('Getting Started') . "</span><br/>" ;
					$output.=_("Designed and built by teachers, Gibbon's Smart Workflow takes care of the boring stuff, so you can get on with teaching.") . "<br/>" ;
				$output.="</div>" ;
				$output.="<table cellspacing='0' style='width: 100%; margin: 10px 0px; border-spacing: 4px;'>" ;
					$output.="<tr>" ;
						if ($step==1) {
							$output.="<td style='width: 20%; border-top: 3px solid #fff; border-bottom: 2px solid rgba(255,255,255,0.0); background-color: rgba(255,255,255,0.25); padding: 4px'>" ;
								$output.="<span style='color: #c00; font-size: 270%; font-weight: bold; letter-spacing: 70%'>" . _('One') . "</span><br/>" ;
								$output.="<span style='color: #c00; font-size: 140%; letter-spacing: 70%'>" . sprintf(_('Create %1$s Outcomes'), "<br/>") . "</span><br/></span>" ;
							$output.="</td>" ;
						}
						else {
							$output.="<td style='width: 20%; border-top: 3px solid #fff; border-bottom: 2px solid #fff; background-color: rgba(255,255,255,0.25); padding: 4px'>" ;
								$output.="<span style='font-size: 270%; font-weight: bold; letter-spacing: 70%'>" . _('One') . "</span><br/>" ;
								$output.="<span style='font-size: 140%; letter-spacing: 70%'><a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Planner/outcomes.php'>" . sprintf(_('Create %1$s Outcomes'), "<br/>") . "</span><br/></a>" ;
							$output.="</td>" ;
						}
						if ($step==2) {
							$output.="<td style='width: 20%; border-top: 3px solid #fff; border-bottom: 2px solid rgba(255,255,255,0.0); background-color: rgba(255,255,255,0.25); padding: 4px'>" ;
								$output.="<span style='color: #c00; font-size: 270%; font-weight: bold; letter-spacing: 70%'>" . _('Two') . "</span><br/>" ;
								$output.="<span style='color: #c00; font-size: 140%; letter-spacing: 70%'>" . sprintf(_('Plan & Deploy %1$s Smart Units'), "<br/>") . "</span><br/></span>" ;
							$output.="</td>" ;
						}
						else {
							$output.="<td style='width: 20%; border-top: 3px solid #fff; border-bottom: 2px solid #fff; background-color: rgba(255,255,255,0.25); padding: 4px'>" ;
								$output.="<span style='font-size: 270%; font-weight: bold; letter-spacing: 70%'>" . _('Two') . "</span><br/>" ;
								$output.="<span style='font-size: 140%; letter-spacing: 70%'><a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Planner/units.php'>" . sprintf(_('Plan & Deploy %1$s Smart Units'), "<br/>") . "</span><br/></a>" ;
							$output.="</td>" ;
						}
						if ($step==3) {
							$output.="<td style='width: 20%; border-top: 3px solid #fff; border-bottom: 2px solid rgba(255,255,255,0.0); background-color: rgba(255,255,255,0.25); padding: 4px'>" ;
								$output.="<span style='color: #c00; font-size: 270%; font-weight: bold; letter-spacing: 70%'>" . _('Three') . "</span><br/>" ;
								$output.="<span style='color: #c00; font-size: 140%; letter-spacing: 70%'>" . sprintf(_('Share, Teach %1$s & Interact'), "<br/>") . "</span><br/></span>" ;
							$output.="</td>" ;
						}
						else {
							$output.="<td style='width: 20%; border-top: 3px solid #fff; border-bottom: 2px solid #fff; background-color: rgba(255,255,255,0.25); padding: 4px'>" ;
								$output.="<span style='font-size: 270%; font-weight: bold; letter-spacing: 70%'>" . _('Three') . "</span><br/>" ;
								$output.="<span style='font-size: 140%; letter-spacing: 70%'><a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Planner/planner.php'>" . sprintf(_('Share, Teach %1$s & Interact'), "<br/>") . "</span><br/></a>" ;
							$output.="</td>" ;
						}
						if ($step==4) {
							$output.="<td style='width: 20%; border-top: 3px solid #fff; border-bottom: 2px solid rgba(255,255,255,0.0); background-color: rgba(255,255,255,0.25); padding: 4px'>" ;
								$output.="<span style='color: #c00; font-size: 270%; font-weight: bold; letter-spacing: 70%'>" . _('Four<') . "/span><br/>" ;
								$output.="<span style='color: #c00; font-size: 140%; letter-spacing: 70%'>" . sprintf(_('Assign & Collect %1$s Work'), "<br/>") . "</span><br/></span>" ;
							$output.="</td>" ;
						}
						else {
							$output.="<td style='width: 20%; border-top: 3px solid #fff; border-bottom: 2px solid #fff; background-color: rgba(255,255,255,0.25); padding: 4px'>" ;
								$output.="<span style='font-size: 270%; font-weight: bold; letter-spacing: 70%'>" . _('Four') . "</span><br/>" ;
							$output.="<span style='font-size: 140%; letter-spacing: 70%'><a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Planner/planner_deadlines.php'>" . sprintf(_('Assign & Collect %1$s Work'), "<br/>") . "</span><br/></a>" ;
							$output.="</td>" ;
						}
						if ($step==5) {
							$output.="<td style='width: 20%; border-top: 3px solid #fff; border-bottom: 2px solid rgba(255,255,255,0.0); background-color: rgba(255,255,255,0.25); padding: 4px'>" ;
								$output.="<span style='color: #c00; font-size: 270%; font-weight: bold; letter-spacing: 70%'>" . _('Five') . "</span><br/>" ;
								$output.="<span style='color: #c00; font-size: 140%; letter-spacing: 70%'>" . sprintf(_('Assess & Give %1$s Feedback'), "<br/>") . "</span><br/></span>" ;
							$output.="</td>" ;
						}
						else {
							$output.="<td style='width: 20%; border-top: 3px solid #fff; border-bottom: 2px solid #fff; background-color: rgba(255,255,255,0.25); padding: 4px'>" ;
								$output.="<span style='font-size: 270%; font-weight: bold; letter-spacing: 70%'>" . _('Five') . "</span><br/>" ;
							$output.="<span style='font-size: 140%; letter-spacing: 70%'><a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Markbook/markbook_view.php'>" . sprintf(_('Assess & Give %1$s Feedback'), "<br/>") . "</span><br/></a>" ;
							$output.="</td>" ;
						}
					$output.="</tr>" ;
					if ($step!="") {
						$output.="<tr>" ;
							$output.="<td style='text-align: justify; font-size: 125%; border-bottom: 2px solid #fff; background-color: rgba(255,255,255,0.25); padding: 15px 4px' colspan=5>" ;
								if ($step==1) {
									$output.=_('<b>Outcomes</b> provide a way to plan and track what is being taught in school, and so are a great place to get started.<br/><br/>Click on the "Add" button (below this message, on the right) to add a new outcome, which can either be school-wide, or attached to a particular department.') . "<br/>" ;
									$output.="<div style='font-size: 75%; font-style: italic; margin-top: 10px'>" . _('<b>Note</b>: You need to be in a department, with the correct permissions, in order to be able to do this.') . " " . sprintf(_('Please contact %1$s for help.'), "<a href='mailto:" . $_SESSION[$guid]["organisationAdministratorEmail"] . "'>" . $_SESSION[$guid]["organisationAdministratorName"] . "</a>") . "</div>" ;
								}
								else if ($step==2) {
									$output.=_('<b>Smart Units</b> support you in the design of course content, and can be quickly turned into individual lesson plans using intuitive drag and drop. Smart Units cut planning time dramatically, and support ongoing improvement and reuse of content.<br/><br/>Choose a course, using the dropdown menu on the right, and then click on the "Add" button (below this message, on the right) to add a new unit. Once your master unit is complete, deploy it to a class to create your lesson plans.') . "<br/>" ;
									$output.="<div style='font-size: 75%; font-style: italic; margin-top: 10px'>" . _('<b>Note</b>: You need to be in a department, with the correct permissions, in order to be able to do this.') . " " . sprintf(_('Please contact %1$s for help.'), "<a href='mailto:" . $_SESSION[$guid]["organisationAdministratorEmail"] . "'>" . $_SESSION[$guid]["organisationAdministratorName"] . "</a>") . "</div>" ;
								}
								else if ($step==3) {
									$output.=sprintf(_('<b>Planner</b> supports online lesson plans which can be shared with students, parents and other teachers. Create your lesson by hand, or automatically via %1$sSmart Units%2$s. Lesson plans facilitate sharing of course content, homework assignment and submission, text chat, and attendance taking.<br/><br/>Choose a date or class, using the menu on the right, and then click on the "Add" button (below this message, on the right) to add a new unit.'), "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Planner/units.php'>", "</a>") . "<br/>" ;
									$output.="<div style='font-size: 75%; font-style: italic; margin-top: 10px'>" . _('<b>Note</b>: You need to have classes assigned to you, with the correct permissions, in order to be able to do this.') . " " . sprintf(_('Please contact %1$s for help.'), "<a href='mailto:" . $_SESSION[$guid]["organisationAdministratorEmail"] . "'>" . $_SESSION[$guid]["organisationAdministratorName"] . "</a>") . "</div>" ;
								}
								else if ($step==4) {
									$output.=sprintf(_('<b>Homework + Deadlines</b> allows teachers and students to see upcoming deadlines, cleanly displayed in one place. Click on an entry to view the details for that piece of homework, and the lesson it is attached to.<br/><br/>Homework can be assigned using the %1$sPlanner%2$s, which also allows teachers to view all submitted work, and records late and incomplete work.'), "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Planner/planner.php'>", "</a>") . "<br/>" ;
									$output.="<div style='font-size: 75%; font-style: italic; margin-top: 10px'>" . _('<b>Note</b>: You need to have classes assigned to you, with the correct permissions, in order to be able to do this.') . " " . sprintf(_('Please contact %1$s for help.'), "<a href='mailto:" . $_SESSION[$guid]["organisationAdministratorEmail"] . "'>" . $_SESSION[$guid]["organisationAdministratorName"] . "</a>") . "</div>" ;
								}
								else if ($step==5) {
									$output.=sprintf(_('<b>Markbook</b> provides an organised way to assess, record and report on student progress. Use grade scales, rubrics, comments and file uploads to keep students and parents up to date. Link markbooks to the %1$sPlanner%2$s, and see student work as you are marking it.<br/><br/>Choose a class from the menu on the right, and then click on the "Add" button (below this message, on the right) to create a new markbook column.'), "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Planner/planner.php'>", "</a>") . "<br/>" ;
									$output.="<div style='font-size: 75%; font-style: italic; margin-top: 10px'>" . _('<b>Note</b>: You need to have classes assigned to you, with the correct permissions, in order to be able to do this.') . " " . sprintf(_('Please contact %1$s for help.'), "<a href='mailto:" . $_SESSION[$guid]["organisationAdministratorEmail"] . "'>" . $_SESSION[$guid]["organisationAdministratorName"] . "</a>") . "</div>" ;
								}
							$output.="</td>" ;
						$output.="</tr>" ;
					}
				$output.="</table>" ;
				$output.="<div style='text-align: right; font-size: 90%; padding: 0 7px'>" ;
					$output.="<a title='". _('Dismiss Smart Workflow Help') . "' onclick='$(\"#smartWorkflowHelp\").fadeOut(1000); $.ajax({ url: \"" . $_SESSION[$guid]["absoluteURL"] . "/index_SmartWorkflowHelpAjax.php\"})' href='#'>" . _('Dismiss Smart Workflow Help') . "</a>" ;
				$output.="</div>" ;
			$output.="</div>" ;
		}
	}
	
	return $output ;	
}

function doesPasswordMatchPolicy($connection2, $passwordNew) {
	$output=TRUE ;
	
	$alpha=getSettingByScope( $connection2, "System", "passwordPolicyAlpha" ) ;
	$numeric=getSettingByScope( $connection2, "System", "passwordPolicyNumeric" ) ;
	$punctuation=getSettingByScope( $connection2, "System", "passwordPolicyNonAlphaNumeric" ) ;
	$minLength=getSettingByScope( $connection2, "System", "passwordPolicyMinLength" ) ;
	
	if ($alpha==FALSE OR $numeric==FALSE OR $punctuation==FALSE OR $minLength==FALSE) {
		$output=FALSE ;
	}
	else {
		if ($alpha!="N" OR $numeric!="N" OR $punctuation!="N" OR $minLength>=0) {
			if ($alpha=="Y") {
				if (preg_match('`[A-Z]`',$passwordNew)==FALSE OR preg_match('`[a-z]`',$passwordNew)==FALSE) {
					$output=FALSE ;
				}
			}
			if ($numeric=="Y") {
				if (preg_match('`[0-9]`',$passwordNew)==FALSE) {
					$output=FALSE ;
				}
			}  
			if ($punctuation=="Y") {
				if (preg_match('/[^a-zA-Z0-9]/',$passwordNew)==FALSE AND strpos($passwordNew, " ")==FALSE) {
					$output=FALSE ;
				}
			}
			if ($minLength>0) {
				if (strLen($passwordNew)<$minLength) {
					$output=FALSE ;
				}
			}
		}
	}
	
	return $output ;
}

function getPasswordPolicy($connection2) {
	$output=FALSE ;
	
	$alpha=getSettingByScope( $connection2, "System", "passwordPolicyAlpha" ) ;
	$numeric=getSettingByScope( $connection2, "System", "passwordPolicyNumeric" ) ;
	$punctuation=getSettingByScope( $connection2, "System", "passwordPolicyNonAlphaNumeric" ) ;
	$minLength=getSettingByScope( $connection2, "System", "passwordPolicyMinLength" ) ;
	
	if ($alpha==FALSE OR $numeric==FALSE OR $punctuation==FALSE OR $minLength==FALSE) {
		$output.=_("An error occurred.") ;
	}
	else if ($alpha!="N" OR $numeric!="N" OR $punctuation!="N" OR $minLength>=0) {
		$output.=_("The password policy stipulates that passwords must:") . "<br/>" ;
		$output.="<ul>" ;
			if ($alpha=="Y") {
				$output.="<li>" . _('Contain at least one lowercase letter, and one uppercase letter.') . "</li>" ;
			}
			if ($numeric=="Y") {
				$output.="<li>" . _('Contain at least one number.') . "</li>" ;
			}
			if ($punctuation=="Y") {
				$output.="<li>" . _('Contain at least one non-alphanumeric character (e.g. a punctuation mark or space).') . "</li>" ;
			}
			if ($minLength>=0) {
				$output.="<li>" . sprintf(_('Must be at least %1$s characters in length.'), $minLength) . "</li>" ;
			}
		$output.="</ul>" ;
	}
	
	return $output ;
}

function getStudentFastFinder($connection2, $guid) {
	$output=FALSE ;
	
	$output.="<div id='findStaffStudents'>" ;
		$studentHighestAction=getHighestGroupedAction($guid, "/modules/Students/student_view_details.php", $connection2) ;
		$staffIsAccessible=isActionAccessible($guid, $connection2, "/modules/Staff/staff_view.php") ;
		if ($studentHighestAction=="View Student Profile_full" OR $staffIsAccessible==TRUE) {
			//Get student list
			try {
				if ($studentHighestAction=="View Student Profile_full" AND $staffIsAccessible==TRUE) {
					$dataList=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
					$sqlList="(SELECT gibbonPerson.gibbonPersonID, preferredName, surname, gibbonRollGroup.name AS name, 'Student' AS role FROM gibbonPerson, gibbonStudentEnrolment, gibbonRollGroup WHERE gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID AND gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID AND status='FULL' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID) UNION (SELECT gibbonPerson.gibbonPersonID, preferredName, surname, NULL AS name, 'Staff' AS role FROM gibbonPerson JOIN gibbonStaff ON (gibbonStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE status='Full') ORDER BY surname, preferredName" ;
				}
				else if ($studentHighestAction=="View Student Profile_full") {
					$dataList=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
					$sqlList="SELECT gibbonPerson.gibbonPersonID, preferredName, surname, gibbonRollGroup.name AS name, 'Student' AS role FROM gibbonPerson, gibbonStudentEnrolment, gibbonRollGroup WHERE gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID AND gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID AND status='FULL' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY surname, preferredName" ;
				}
				else {
					$dataList=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
					$sqlList="SELECT gibbonPerson.gibbonPersonID, preferredName, surname, NULL AS name, 'Staff' AS role FROM gibbonPerson JOIN gibbonStaff ON (gibbonStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE status='Full' ORDER BY surname, preferredName" ;
				}
				$resultList=$connection2->prepare($sqlList);
				$resultList->execute($dataList); 
			}
			catch(PDOException $e) { $output.=$e->getMessage() ; }

			$studentCount=0 ;
			$list="" ;
			while ($rowList=$resultList->fetch()) {
				$list.="{id: \"" . substr($rowList["role"],0,3) . "-" . $rowList["gibbonPersonID"] . "\", name: \"" . formatName("", htmlPrep($rowList["preferredName"]), htmlPrep($rowList["surname"]), "Student", true) ; if ($rowList["role"]=="Student") { $list.=" (" . htmlPrep($rowList["name"]) . ")\"}," ; } else { $list.=" (" . _("Staff") . ")\"}," ; }
				if ($rowList["role"]=="Student") {
					$studentCount++ ;
				}
			}
			$output.="<style>" ;
				$output.="ul.token-input-list-facebook { width: 275px; float: left; height: 25px!important; }" ;
				$output.="div.token-input-dropdown-facebook { width: 275px }" ;
			$output.="</style>" ;
			$output.="<div style='padding-bottom: 7px; height: 40px; margin-top: 0px'>" ;
				$output.="<form method='get' action='" . $_SESSION[$guid]["absoluteURL"] . "/indexFindRedirect.php'>" ;
					$output.="<table class='smallIntBorder' cellspacing='0' style='width: 100%; margin: 0px 0px; opacity: 0.8'>" ;	
						$output.="<tr>" ;
							$output.="<td style='vertical-align: top; padding: 0px' colspan=2>" ;
								$output.="<h2 style='padding-bottom: 0px'>" ;
								if ($studentHighestAction=="View Student Profile_full" AND $staffIsAccessible==TRUE) {
									$output.=_("Find Staff & Students") . "<br/>" ;
								}
								else if ($studentHighestAction=="View Student Profile_full") {
									$output.=_("Find Students") . "<br/>" ;
								}
								else {
									$output.=_("Find Staff") . "<br/>" ;
								}
								$output.="</h2>" ;
							$output.="</td>" ;
						$output.="</tr>" ;
						$output.="<tr>" ;
							$output.="<td style='vertical-align: top; border: none'>" ; 
								$output.="<input class='topFinder' style='width: 275px' type='text' id='gibbonPersonID' name='gibbonPersonID' />" ;
								$output.="<script type='text/javascript'>" ;
									$output.="$(document).ready(function() {" ;
										 $output.="$(\"#gibbonPersonID\").tokenInput([" ;
											$output.=substr($list,0,-1) ;
										$output.="]," ; 
											$output.="{theme: \"facebook\"," ;
											$output.="hintText: \"Start typing a name...\"," ;
											$output.="allowCreation: false," ;
											$output.="preventDuplicates: true," ;
											$output.="tokenLimit: 1});" ;
									$output.="});" ;
								$output.="</script>" ;
								$output.="<script type='text/javascript'>" ;
									$output.="var gibbonPersonID=new LiveValidation('gibbonPersonID');" ;
									$output.="gibbonPersonID.add(Validate.Presence);" ;
								 $output.="</script>" ;
							$output.="</td>" ;
							$output.="<td class='right' style='vertical-align: top; border: none'>" ;
								$output.="<input style='height: 27px; width: 60px!important; margin-top: 0px;' type='submit' value='" . _('Go') . "'>" ;
							$output.="</td>" ;
						$output.="</tr>" ;
						if (getRoleCategory($_SESSION[$guid]["gibbonRoleIDCurrent"], $connection2)=="Staff") {
							$output.="<tr>" ;
								$output.="<td style='vertical-align: top' colspan=2>" ;
									$output.="<div style='padding-bottom: 0px; font-size: 80%; font-weight: normal; font-style: italic; line-height: 80%; padding: 1em,1em,1em,1em; width: 99%; text-align: left; color: #888;' >" . _('Total Student Enrolment:') . " " . $studentCount . "</div>" ;
								$output.="</td>" ;
							$output.="</tr>" ;
						}
					$output.="</table>" ;
				$output.="</form>" ;
			$output.="</div>" ;
		}
	$output.="</div>" ;
	
	return $output ;
}

function getParentPhotoUploader($connection2, $guid) {
	$output=FALSE ;
	
	$category=getRoleCategory($_SESSION[$guid]["gibbonRoleIDCurrent"], $connection2) ;
	if ($category=="Parent") {
		$output.="<h2 style='margin-bottom: 10px'>" ;
			$output.="Profile Photo" ;
		$output.="</h2>" ;
		
		if (isset($_GET["deleteReturn"])) { $deleteReturn=$_GET["deleteReturn"] ; } else { $deleteReturn="" ; }
		$deleteReturnMessage="" ;
		$class="error" ;
		if (!($deleteReturn=="")) {
			if ($deleteReturn=="fail1") {
				$deleteReturnMessage=_("Your request failed because your inputs were invalid.") ;	
			}
			else if ($deleteReturn=="fail2") {
				$deleteReturnMessage=_("Your request failed due to a database error.") ;	
			}
			else if ($deleteReturn=="success0") {
				$deleteReturnMessage=_("Your request was completed successfully.") ;		
				$class="success" ;
			}
			$output.="<div class='$class'>" ;
				$output.=$deleteReturnMessage;
			$output.="</div>" ;
		} 
		
		if (isset($_GET["uploadReturn"])) { $uploadReturn=$_GET["uploadReturn"] ; } else { $uploadReturn="" ; }
		$uploadReturnMessage="" ;
		$class="error" ;
		if (!($uploadReturn=="")) {
			if ($uploadReturn=="fail1") {
				$uploadReturnMessage=_("Your request failed because your inputs were invalid.") ;	
			}
			else if ($uploadReturn=="fail2") {
				$uploadReturnMessage=_("Your request failed due to a database error.") ;	
			}
			else if ($uploadReturn=="success0") {
				$uploadReturnMessage=_("Your request was completed successfully.") ;		
				$class="success" ;
			}
			$output.="<div class='$class'>" ;
				$output.=$uploadReturnMessage;
			$output.="</div>" ;
		}
		
		if ($_SESSION[$guid]["image_240"]=="") { //No photo, so show uploader
			$output.="<p>" ;
				$output.=_("Please upload a passport photo to use as a profile picture.") . " " . _('240px by 320px') . "." ;
			$output.="</p>" ;
			$output.="<form method='post' action='" . $_SESSION[$guid]["absoluteURL"] . "/index_parentPhotoUploadProcess.php?gibbonPersonID=" . $_SESSION[$guid]["gibbonPersonID"] . "' enctype='multipart/form-data'>" ;
				$output.="<table class='smallIntBorder' cellspacing='0' style='width: 100%; margin: 0px 0px'>" ;	
					$output.="<tr>" ;
						$output.="<td style='vertical-align: top'>" ; 
							$output.="<input type=\"file\" name=\"file1\" id=\"file1\" style='width: 165px'><br/><br/>" ;
							$output.="<script type=\"text/javascript\">" ;
								$output.="var file1=new LiveValidation('file1');" ;
								$output.="file1.add( Validate.Inclusion, { within: ['gif','jpg','jpeg','png'], failureMessage: \"Illegal file type!\", partialMatch: true, caseSensitive: false } );" ;
							$output.="</script>" ;
						$output.="</td>" ;
						$output.="<td class='right' style='vertical-align: top'>" ;
							$output.="<input style='height: 27px; width: 20px!important; margin-top: 0px;' type='submit' value='" . _('Go') . "'>" ;
						$output.="</td>" ;
					$output.="</tr>" ;
				$output.="</table>" ;
			$output.="</form>" ;
		}
		else { //Photo, so show image and removal link
			$output.="<p>" ;
				$output.=getUserPhoto($guid, $_SESSION[$guid]["image_240"], 240) ;
				$output.="<div style='margin-left: 220px; margin-top: -50px'>" ;
					$output.="<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index_parentPhotoDeleteProcess.php?gibbonPersonID=" . $_SESSION[$guid]["gibbonPersonID"] . "' onclick='return confirm(\"Are you sure you want to delete this record? Unsaved changes will be lost.\")'><img style='margin-bottom: -8px' id='image_75_delete' title='" . _('Delete') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/garbage.png'/></a><br/><br/>" ;
				$output.="</div>" ;
			$output.="</p>" ;
		}
	}
	
	return $output ;
}

function getAlert($connection2, $gibbonAlertLevelID) {
	$output=FALSE; 
	
	try {
		$dataAlert=array("gibbonAlertLevelID"=>$gibbonAlertLevelID); 
		$sqlAlert="SELECT * FROM gibbonAlertLevel WHERE gibbonAlertLevelID=:gibbonAlertLevelID" ;
		$resultAlert=$connection2->prepare($sqlAlert);
		$resultAlert->execute($dataAlert);
	}
	catch(PDOException $e) { }
	if ($resultAlert->rowCount()==1) {
		$rowAlert=$resultAlert->fetch() ;
		$output=array() ;
		$output["name"]=_($rowAlert["name"]) ;
		$output["nameShort"]=$rowAlert["nameShort"] ;
		$output["color"]=$rowAlert["color"] ;
		$output["colorBG"]=$rowAlert["colorBG"] ;
		$output["description"]=_($rowAlert["description"]) ;
		$output["sequenceNumber"]=$rowAlert["sequenceNumber"] ;
	}
	
	return $output ;
}

function getSalt() {
  $c=explode(" ", ". / a A b B c C d D e E f F g G h H i I j J k K l L m M n N o O p P q Q r R s S t T u U v V w W x X y Y z Z 0 1 2 3 4 5 6 7 8 9");
  $ks=array_rand($c, 22);
  $s="";
  foreach($ks as $k) { $s .=$c[$k]; }
  return $s;
}

//Get information on a unit of work, inlcuding the possibility that it is a hooked unit
function getUnit($connection2, $gibbonUnitID, $gibbonHookID, $gibbonCourseClassID) {
	$output=array() ;
	$unitType=false ;
	if ($gibbonUnitID!="") {
		//Check for hooked unit (will have - in value)
		if ($gibbonHookID=="") {
			//No hook
			try {
				$dataUnit=array("gibbonUnitID"=>$gibbonUnitID); 
				$sqlUnit="SELECT * FROM gibbonUnit WHERE gibbonUnitID=:gibbonUnitID" ;
				$resultUnit=$connection2->prepare($sqlUnit);
				$resultUnit->execute($dataUnit); 
				if ($resultUnit->rowCount()==1) {
					$rowUnit=$resultUnit->fetch() ;
					if (isset($rowUnit["type"])) {
						$unitType=$rowUnit["type"] ;
					}
					$output[0]=$rowUnit["name"] ;
					$output[1]="" ;
				}
			}
			catch(PDOException $e) { }
		}
		else {
			//Hook!
			try {
				$dataHooks=array("gibbonHookID"=>$gibbonHookID); 
				$sqlHooks="SELECT * FROM gibbonHook WHERE gibbonHookID=:gibbonHookID" ;
				$resultHooks=$connection2->prepare($sqlHooks);
				$resultHooks->execute($dataHooks); 
				if ($resultHooks->rowCount()==1) {
					$rowHooks=$resultHooks->fetch() ;
					$hookOptions=unserialize($rowHooks["options"]) ;
					if ($hookOptions["unitTable"]!="" AND $hookOptions["unitIDField"]!="" AND $hookOptions["unitCourseIDField"]!="" AND $hookOptions["unitNameField"]!="" AND $hookOptions["unitDescriptionField"]!="" AND $hookOptions["classLinkTable"]!="" AND $hookOptions["classLinkJoinFieldUnit"]!="" AND $hookOptions["classLinkJoinFieldClass"]!="" AND $hookOptions["classLinkIDField"]!="") {
						try {
							$dataHookUnits=array(); 
							$sqlHookUnits="SELECT * FROM " . $hookOptions["unitTable"] . " JOIN " . $hookOptions["classLinkTable"] . " ON (" . $hookOptions["unitTable"] . "." . $hookOptions["unitIDField"] . "=" . $hookOptions["classLinkTable"] . "." . $hookOptions["classLinkJoinFieldUnit"] . ") WHERE " . $hookOptions["unitTable"] . "." . $hookOptions["unitIDField"] . "=" . $gibbonUnitID . " AND " . $hookOptions["classLinkJoinFieldClass"] . "=" . $gibbonCourseClassID . " ORDER BY " . $hookOptions["classLinkTable"] . "." . $hookOptions["classLinkIDField"] ;
							$resultHookUnits=$connection2->prepare($sqlHookUnits);
							$resultHookUnits->execute($dataHookUnits);
							if ($resultHookUnits->rowCount()==1) {
								$rowHookUnits=$resultHookUnits->fetch() ;
								$output[0]=$rowHookUnits[$hookOptions["unitNameField"]] ;
								$output[1]=$rowHooks["name"] ;
							}
						}
						catch(PDOException $e) { }
					}
				}
			}
			catch(PDOException $e) { }
		}
	}
	
	return $output ;
}

function getWeekNumber($date, $connection2, $guid) {
	$week=0 ;
	try {
		$dataWeek=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
		$sqlWeek="SELECT * FROM gibbonSchoolYearTerm WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY sequenceNumber" ;
		$resultWeek=$connection2->prepare($sqlWeek);
		$resultWeek->execute($dataWeek); 
		while ($rowWeek=$resultWeek->fetch()) {
			$firstDayStamp=strtotime($rowWeek["firstDay"]) ;
			$lastDayStamp=strtotime($rowWeek["lastDay"]) ;
			while (date("D",$firstDayStamp)!="Mon") {
				$firstDayStamp=$firstDayStamp-86400 ;
			}
			$head=$firstDayStamp ;
			while ($head<=($date) AND $head<($lastDayStamp+86399)) {
				$head=$head+(86400*7) ;
				$week++ ;
			}
			if ($head<($lastDayStamp+86399)) {
				break ;
			}
		}
	}
	catch(PDOException $e) { }
	
	if ($week<=0) {
		return false ;
	}
	else {
		return $week ;
	}
}

function getModuleEntry($address, $connection2, $guid) {
	$output=false ;
	
	try {
		$data=array("moduleName"=>getModuleName($address),"gibbonRoleID"=>$_SESSION[$guid]["gibbonRoleIDCurrent"]); 
		$sql="SELECT DISTINCT gibbonModule.name, gibbonModule.category, gibbonModule.entryURL FROM `gibbonModule`, gibbonAction, gibbonPermission WHERE gibbonModule.name=:moduleName AND (active='Y') AND (gibbonModule.gibbonModuleID=gibbonAction.gibbonModuleID) AND (gibbonAction.gibbonActionID=gibbonPermission.gibbonActionID) AND (gibbonPermission.gibbonRoleID=:gibbonRoleID) ORDER BY category, name";
		$result=$connection2->prepare($sql);
		$result->execute($data);
		if ($result->rowCount()==1) {
			$row=$result->fetch() ;
			$entryURL=$row["entryURL"] ;
			if (isActionAccessible($guid, $connection2, "/modules/" . $row["name"] . "/" . $entryURL)==FALSE AND $entryURL!="index.php") {
				try {
					$dataEntry=array("gibbonRoleID"=>$_SESSION[$guid]["gibbonRoleIDCurrent"], "moduleName"=>$row["name"]); 
					$sqlEntry="SELECT DISTINCT gibbonAction.entryURL FROM gibbonModule, gibbonAction, gibbonPermission WHERE (active='Y') AND (gibbonModule.gibbonModuleID=gibbonAction.gibbonModuleID) AND (gibbonAction.gibbonActionID=gibbonPermission.gibbonActionID) AND (gibbonPermission.gibbonRoleID=:gibbonRoleID) AND gibbonModule.name=:moduleName ORDER BY gibbonAction.name";
					$resultEntry=$connection2->prepare($sqlEntry);
					$resultEntry->execute($dataEntry);
					if ($resultEntry->rowCount()>0) {
						$rowEntry=$resultEntry->fetch() ;
						$entryURL=$rowEntry["entryURL"] ;
					}
				}
				catch(PDOException $e) {}
			}
		}
	}
	catch(PDOException $e) {}
	
	if ($entryURL!="") {
		$output=$entryURL ;
	}
	return $output ;
}

function formatName( $title, $preferredName, $surname, $roleCategory, $reverse=FALSE, $informal=FALSE ) {
	$output=false ;
	
	if ($roleCategory=="Staff" OR $roleCategory=="Other") {
		if ($informal==FALSE) {
			if ($reverse==TRUE) {
				$output=$title . " " . $surname . ", " . strtoupper(substr($preferredName,0,1)) ;
			}
			else {
				$output=$title . " " . strtoupper(substr($preferredName,0,1)) . ". " . $surname ;
			}
		}
		else {
			if ($reverse==TRUE) {
				$output=$surname . ", " . $preferredName ;
			}
			else {
				$output=$preferredName . " " . $surname ;
			}
		}
	}
	else if ($roleCategory=="Parent") {
		if ($informal==FALSE) {
			if ($reverse==TRUE) {
				$output=$title . " " . $surname . ", " . $preferredName ;
			}
			else {
				$output=$title . " " . $preferredName . " " . $surname ;
			}
		}
		else {
			if ($reverse==TRUE) {
				$output=$surname . ", " . $preferredName ;
			}
			else {
				$output=$preferredName . " " . $surname ;
			}
		}
	}
	else if ($roleCategory=="Student") {
		if ($reverse==TRUE) {
			$output=$surname. ", " . $preferredName ;
		}
		else {
			$output=$preferredName. " " . $surname ;
		}
		
	}

	return $output ;
}

//$tinymceInit indicates whether or not tinymce should be initialised, or whether this will be done else where later (this can be used to improve page load.
function getEditor($guid, $tinymceInit=TRUE, $id, $value="", $rows=10, $showMedia=false, $required=false, $initiallyHidden=false, $allowUpload=true, $initialFilter="", $resourceAlphaSort=false ) {
	$output=false ;
	if ($resourceAlphaSort==false) {
		$resourceAlphaSort="false" ;
	}
	else {
		$resourceAlphaSort="true" ;
	}
	
	$output.="<a name='" . $id . "editor'>" ;
	
	$output.="<div id='editor-toolbar'>" ;
		$output.="<a style='margin-top:-4px' id='" . $id . "edButtonHTML' class='hide-if-no-js edButtonHTML'>HTML</a>" ;
		$output.="<a style='margin-top:-4px' id='" . $id . "edButtonPreview' class='active hide-if-no-js edButtonPreview'>" . _('Visual') . "</a>" ;
		
		$output.="<div id='media-buttons'>" ;
			$output.="<div style='padding-top: 2px; height: 15px'>" ;
				if ($showMedia==TRUE) {
					$output.="<div id='" . $id . "mediaInner' style='text-align: left'>" ;
						$output.="<script type='text/javascript'>" ;	
							$output.="$(document).ready(function(){" ;
								$output.="\$(\"." .$id . "resourceSlider\").hide();" ;
								$output.="\$(\"." .$id . "resourceAddSlider\").hide();" ;
								$output.="\$(\"." .$id . "resourceQuickSlider\").hide();" ;
								$output.="\$(\"." . $id . "show_hide\").show();" ;
								$output.="\$(\"." . $id . "show_hide\").unbind('click').click(function(){" ;
									$output.="\$(\"." .$id . "resourceSlider\").slideToggle();" ;
									$output.="\$(\"." .$id . "resourceAddSlider\").hide();" ;
									$output.="\$(\"." .$id . "resourceQuickSlider\").hide();" ;
									$output.="if (tinyMCE.get('" . $id . "').selection.getRng().startOffset < 1) {" ;
										$output.="tinyMCE.get('" . $id . "').focus();" ;
									$output.="}" ;
								$output.="});" ;
								$output.="\$(\"." . $id . "show_hideAdd\").show();" ;
								$output.="\$(\"." . $id . "show_hideAdd\").unbind('click').click(function(){" ;
									$output.="\$(\"." .$id . "resourceAddSlider\").slideToggle();" ;
									$output.="\$(\"." .$id . "resourceSlider\").hide();" ;
									$output.="\$(\"." .$id . "resourceQuickSlider\").hide();" ;
									$output.="if (tinyMCE.get('" . $id . "').selection.getRng().startOffset < 1) {" ;
										$output.="tinyMCE.get('" . $id . "').focus();" ;
									$output.="}" ;
								$output.="});" ;
								$output.="\$(\"." . $id . "show_hideQuickAdd\").show();" ;
								$output.="\$(\"." . $id . "show_hideQuickAdd\").unbind('click').click(function(){" ;
									$output.="\$(\"." .$id . "resourceQuickSlider\").slideToggle();" ;
									$output.="\$(\"." .$id . "resourceSlider\").hide();" ;
									$output.="\$(\"." .$id . "resourceAddSlider\").hide();" ;
									$output.="if (tinyMCE.get('" . $id . "').selection.getRng().startOffset < 1) {" ;
										$output.="tinyMCE.get('" . $id . "').focus();" ;
									$output.="}" ;
								$output.="});" ;
							$output.="});" ;
						$output.="</script>" ;
					
						$output.="<div style='float: left; padding-top:1px; margin-right: 5px'><u>" . _('Shared Resources') . "</u>:</div> " ;
						$output.="<a title='" . _('Insert Existing Resource') . "' style='float: left' class='" . $id . "show_hide' onclick='\$(\"." .$id . "resourceSlider\").load(\"" . $_SESSION[$guid]["absoluteURL"] . "/modules/Resources/resources_insert_ajax.php?alpha=" . $resourceAlphaSort . "&" . $initialFilter . "\",\"id=" . $id . "&allowUpload=$allowUpload\");' href='#'><img style='padding-right: 5px' src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/Default/img/search_mini.png' alt='" . _('Insert Existing Resource') . "' onclick='return false;' /></a>" ;
						if ($allowUpload==true) {
							$output.="<a title='" . _('Create & Insert New Resource') . "' style='float: left' class='" . $id . "show_hideAdd' onclick='\$(\"." .$id . "resourceAddSlider\").load(\"" . $_SESSION[$guid]["absoluteURL"] . "/modules/Resources/resources_add_ajax.php?alpha=" . $resourceAlphaSort . "&" . $initialFilter . "\",\"id=" . $id . "&allowUpload=$allowUpload\");' href='#'><img style='padding-right: 5px' src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/Default/img/upload_mini.png' alt='" . _('Create & Insert New Resource') . "' onclick='return false;' /></a>" ;
						}
						$output.="<div style='float: left; padding-top:1px; margin-right: 5px'><u>" . _('Quick File Upload') . "</u>:</div> " ;
						$output.="<a title='" . _('Quick Add') . "' style='float: left' class='" . $id . "show_hideQuickAdd' onclick='\$(\"." .$id . "resourceQuickSlider\").load(\"" . $_SESSION[$guid]["absoluteURL"] . "/modules/Resources/resources_addQuick_ajax.php?alpha=" . $resourceAlphaSort . "&" . $initialFilter . "\",\"id=" . $id . "&allowUpload=$allowUpload\");' href='#'><img style='padding-right: 5px' src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/Default/img/page_new_mini.png' alt='" . _('Quick Add') . "' onclick='return false;' /></a>" ;
					$output.="</div>" ;
				}
			$output.="</div>" ;
		$output.="</div>" ;
		
		if ($showMedia==TRUE) {
			//DEFINE MEDIA INPUT DISPLAY
			$output.="<div class='" . $id . "resourceSlider' style='display: none; width: 100%; min-height: 60px;'>" ;
				$output.="<div style='text-align: center; width: 100%; margin-top: 5px'>" ;
					$output.="<img style='margin: 10px 0 5px 0' src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/Default/img/loading.gif' alt='" . _('Loading') . "' onclick='return false;' /><br/>" ;
					$output.=_('Loading') ;
				$output.="</div>" ;
			$output.="</div>" ;
			
			//DEFINE QUICK INSERT
			$output.="<div class='" . $id . "resourceQuickSlider' style='display: none; width: 100%; min-height: 60px;'>" ;
				$output.="<div style='text-align: center; width: 100%; margin-top: 5px'>" ;
					$output.="<img style='margin: 10px 0 5px 0' src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/Default/img/loading.gif' alt='" . _('Loading') . "' onclick='return false;' /><br/>" ;
					$output.=_("Loading") ;
				$output.="</div>" ;
			$output.="</div>" ;
		}
		
		if ($showMedia==TRUE AND $allowUpload==TRUE) {
			//DEFINE MEDIA ADD DISPLAY
			$output.="<div class='" . $id . "resourceAddSlider' style='display: none; width: 100%; min-height: 60px;'>" ;
				$output.="<div style='text-align: center; width: 100%; margin-top: 5px'>" ;
					$output.="<img style='margin: 10px 0 5px 0' src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/Default/img/loading.gif' alt='" . _('Loading') . "' onclick='return false;' /><br/>" ;
					$output.=_("Loading") ;
				$output.="</div>" ;
			$output.="</div>" ;
		}
		
		$output.="<div id='editorcontainer' style='margin-top: 4px'>" ;
			
			$output.="<textarea class='tinymce' name='" . $id . "' id='" . $id . "' style='height: " . ($rows*18) . "px; width: 100%; margin-left: 0px'>" . htmlPrep($value) . "</textarea>" ;
			if ($required) {
				$output.="<script type='text/javascript'>" ;
					$output.="var " . $id ."='';" ;
					$output.=$id . "=new LiveValidation('" . $id . "');" ;
					$output.=$id . ".add(Validate.Presence, { tinymce: true, tinymceField: '" . $id . "'});" ;
					if ($initiallyHidden==true) {
						$output.=$id . ".disable();" ;
					}
				$output.="</script>" ;
			}
		$output.="</div>" ;
		
		$output.="<script type='text/javascript'>" ;
			$output.="$(document).ready(function(){" ;
				if ($tinymceInit) {
					$output.="tinyMCE.execCommand('mceAddControl', false, '" . $id . "');" ;
				}
				$output.="$('#" . $id . "edButtonPreview').addClass('active') ;" ;
				 $output.="$('#" . $id . "edButtonHTML').click(function(){" ;
					$output.="tinyMCE.execCommand('mceRemoveEditor', false, '" . $id . "');" ; 
					$output.="$('#" . $id . "edButtonHTML').addClass('active') ;" ;
					$output.="$('#" . $id . "edButtonPreview').removeClass('active') ;" ;
					$output.="\$(\"." .$id . "resourceSlider\").hide();" ;
					$output.="\$(\"#" .$id . "mediaInner\").hide();" ;
					if ($required) {
						$output.=$id . ".destroy();" ;
						$output.="$('.LV_validation_message').css('display','none');" ;
						$output.=$id . "=new LiveValidation('" . $id . "');" ;
						$output.=$id . ".add(Validate.Presence);" ;
					}
				 $output.="}) ;" ;
				 $output.="$('#" . $id . "edButtonPreview').click(function(){" ;
					$output.="tinyMCE.execCommand('mceAddEditor', false, '" . $id . "');" ;
					$output.="$('#" . $id . "edButtonPreview').addClass('active') ;" ;
					$output.="$('#" . $id . "edButtonHTML').removeClass('active') ; " ;
					$output.="\$(\"#" .$id . "mediaInner\").show();" ;
					if ($required) {
						$output.=$id . ".destroy();" ;
						$output.="$('.LV_validation_message').css('display','none');" ;
						$output.=$id . "=new LiveValidation('" . $id . "');" ;
						$output.=$id . ".add(Validate.Presence, { tinymce: true, tinymceField: '" . $id . "'});" ;
					}
				 $output.="}) ;" ;
			$output.="});" ;
		$output.="</script>" ;	
	$output.="</div>" ;
	
	return $output ;
}

function getYearGroups( $connection2 ) {
	$output=FALSE ;
	//Scan through year groups
	//SELECT NORMAL
	try {
		$sql="SELECT * FROM gibbonYearGroup ORDER BY sequenceNumber" ;
		$result=$connection2->query($sql);  
		while ($row=$result->fetch()) {
			$output.=$row["gibbonYearGroupID"] . "," ;
			$output.=$row["name"] . "," ;
		}
	}
	catch(PDOException $e) { }		
	
	if ($output!=FALSE) {
		$output=substr($output,0,(strlen($output)-1)) ;
		$output=explode(",", $output) ;
	}
	return $output ;
}

function getYearGroupsFromIDList ( $connection2, $ids, $vertical=false, $translated=true ) {
	$output=FALSE ;
	
	try {
		$sqlYears="SELECT DISTINCT nameShort, sequenceNumber FROM gibbonYearGroup ORDER BY sequenceNumber" ;
		$resultYears=$connection2->query($sqlYears);  
		
		$years=explode(",", $ids) ;
		if (count($years)>0 AND $years[0]!="") {
			if (count($years)==$resultYears->rowCount()) {
				$output="<i>All</i>" ;
			}
			else {
				try {
					$dataYears=array() ;
					$sqlYearsOr="" ;
					for ($i=0; $i<count($years); $i++) {
						if ($i==0) {
							$dataYears[$years[$i]]=$years[$i] ;
							$sqlYearsOr=$sqlYearsOr . " WHERE gibbonYearGroupID=:" . $years[$i] ;
						}
						else {
							$dataYears[$years[$i]]=$years[$i] ;
							$sqlYearsOr=$sqlYearsOr . " OR gibbonYearGroupID=:" . $years[$i] ;
						}
					}
					
					$sqlYears="SELECT DISTINCT nameShort, sequenceNumber FROM gibbonYearGroup $sqlYearsOr ORDER BY sequenceNumber" ;
					$resultYears=$connection2->prepare($sqlYears); 
					$resultYears->execute($dataYears); 
				}
				catch(PDOException $e) { }		
						
				$count3=0 ;
				while ($rowYears=$resultYears->fetch()) {
					if ($count3>0) {
						if ($vertical==false) {
							$output.=", " ;
						}
						else {
							$output.="<br/>" ;
						}
					}
					if ($translated==TRUE) {
						$output.=_($rowYears["nameShort"]) ;
					}
					else {
						$output.=$rowYears["nameShort"] ;
					}
					$count3++ ;
				}
			}
		}
		else {
			$output="<i>None</i>" ;
		}
	}
	catch(PDOException $e) { }		
	
	return $output ;
}

//Gets terms in the specified school year
function getTerms( $connection2, $gibbonSchoolYearID, $short=FALSE ) {
	$output=FALSE ;
	//Scan through year groups
	try {
		$data=array("gibbonSchoolYearID"=>$gibbonSchoolYearID); 
		$sql="SELECT * FROM gibbonSchoolYearTerm WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY sequenceNumber" ;
		$result=$connection2->prepare($sql);
		$result->execute($data); 
	}
	catch(PDOException $e) { }
	
	while ($row=$result->fetch()) {
		$output.=$row["gibbonSchoolYearTermID"] . "," ;
		if ($short==TRUE) {
			$output.=$row["nameShort"] . "," ;
		}
		else {
			$output.=$row["name"] . "," ;
		}
	}
	if ($output!=FALSE) {
		$output=substr($output,0,(strlen($output)-1)) ;
		$output=explode(",", $output) ;
	}
	return $output ;
}

//Array sort for multidimensional arrays
function msort($array, $id="id", $sort_ascending=true) {
	$temp_array=array();
	while(count($array)>0) {
		$lowest_id=0;
		$index=0;
		foreach ($array as $item) {
			if (isset($item[$id])) {
				if ($array[$lowest_id][$id]) {
					if (strtolower($item[$id]) < strtolower($array[$lowest_id][$id])) {
						$lowest_id=$index;
					}
				}
			}
			$index++;
		}
		$temp_array[]=$array[$lowest_id];
		$array=array_merge(array_slice($array, 0,$lowest_id), array_slice($array, $lowest_id+1));
	}
	if ($sort_ascending) {
		return $temp_array;
	} else {
		return array_reverse($temp_array);
	}
}

//Create the sidebar
function sidebar($connection2, $guid) {
	$googleOAuth=getSettingByScope($connection2, "System", "googleOAuth") ;
	if (isset($_GET["loginReturn"])) {
		$loginReturn=$_GET["loginReturn"] ;
	}
	else {
		$loginReturn="" ; 
	}
	$loginReturnMessage="" ;
	if (!($loginReturn=="")) {
		if ($loginReturn=="fail0b") {
			$loginReturnMessage=_("Username or password not set.") ;	
		}
		else if ($loginReturn=="fail1") {
			$loginReturnMessage=_("Incorrect username and password.") ;	
		}
		else if ($loginReturn=="fail2") {
			$loginReturnMessage=_("You do not have sufficient privileges to login.") ;	
		}
		else if ($loginReturn=="fail5") {
			$loginReturnMessage=_("Your request failed due to a database error.") ;	
		}
		else if ($loginReturn=="fail6") {
			$loginReturnMessage=sprintf(_('Too many failed logins: please %1$sreset password%2$s.'), "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/passwordReset.php'>", "</a>") ;	
		}
		else if ($loginReturn=="fail7") {
			$loginReturnMessage=sprintf(_('Error with Google Authentication. Please contact %1$s if you have any questions.'), "<a href='mailto:" . $_SESSION[$guid]["organisationDBAEmail"] . "'>" . $_SESSION[$guid]["organisationDBAName"] . "</a>") ;	
		}
		else if ($loginReturn=="fail8") {
			$loginReturnMessage=sprintf(_('Gmail account does not match the email stored in %1$s. If you have logged in with your school Gmail account please contact %2$s if you have any questions.'), $_SESSION[$guid]["systemName"], "<a href='mailto:" . $_SESSION[$guid]["organisationDBAEmail"] . "'>" . $_SESSION[$guid]["organisationDBAName"] . "</a>") ;	
		}
		else if ($loginReturn=="fail9") {
			$loginReturnMessage=_('Your primary role does not support the ability to log in to non-current years.') ;	
		}
		
		print "<div class='error'>" ;
			print $loginReturnMessage;
		print "</div>" ;
	} 
	
	if ($_SESSION[$guid]["sidebarExtra"]!="" AND $_SESSION[$guid]["sidebarExtraPosition"]!="bottom") {
		print "<div class='sidebarExtra'>" ;
		print $_SESSION[$guid]["sidebarExtra"] ;
		print "</div>" ;
	}
	
	// Add Google Login Button
	if ((isset($_SESSION[$guid]["username"])==FALSE) && (isset($_SESSION[$guid]["email"])==FALSE)) {
		if($googleOAuth=="Y") {
			print "<h2>" ;
				print _("Login with Google") ;
			print "</h2>" ;
		
			?>
			<script>
				$(function(){
					$('#siteloader').load('lib/googleOAuth/index.php');
				});
			</script>
			<div id="siteloader"></div>
			<?php 
			} //End Check for Google Auth
			if ((isset($_SESSION[$guid]["username"])==FALSE)){ // If Google Auth set to No make sure login screen not visible when logged in
			?>
			<h2>
				<?php print _("Login") ; ?>
			</h2>
			<form name="loginForm" method="post" action="./login.php?<?php if (isset($_GET["q"])) { print "q=" . $_GET["q"] ; } ?>">
				<table class='noIntBorder' cellspacing='0' style="width: 100%; margin: 0px 0px">	
					<tr>
						<td> 
							<b><?php print _("Username") ; ?></b>
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
							<b><?php print _("Password") ; ?></b>
						</td>
						<td class="right">
							<input name="password" id="password" maxlength=20 type="password" style="width:120px">
							<script type="text/javascript">
								var password=new LiveValidation('password', {onlyOnSubmit: true });
								password.add(Validate.Presence);
							 </script> 
						</td>
					</tr>
					<tr class='schoolYear' id='schoolYear'>
						<td> 
							<b><?php print _("School Year") ; ?></b>
						</td>
						<td class="right">
							<select name="gibbonSchoolYearID" id="gibbonSchoolYearID" style="width: 120px">
								<?php
								try {
									$dataSelect=array(); 
									$sqlSelect="SELECT * FROM gibbonSchoolYear ORDER BY sequenceNumber" ;
									$resultSelect=$connection2->prepare($sqlSelect);
									$resultSelect->execute($dataSelect);
								}
								catch(PDOException $e) { 
									print "<div class='error'>" . $e->getMessage() . "</div>" ; 
								}
								while ($rowSelect=$resultSelect->fetch()) {
									$selected="" ;
									if ($rowSelect["status"]=="Current") {
										$selected="selected" ;
									}
									print "<option $selected value='" . $rowSelect["gibbonSchoolYearID"] . "'>" . htmlPrep($rowSelect["name"]) . "</option>" ;
								}
								?>				
							</select>
						</td>
					</tr>
					<tr class='language' id='language'>
						<td> 
							<b><?php print _("Language") ; ?></b>
						</td>
						<td class="right">
							<select name="gibboni18nID" id="gibboni18nID" style="width: 120px">
								<?php
								try {
									$dataSelect=array(); 
									$sqlSelect="SELECT * FROM gibboni18n WHERE active='Y' ORDER BY name" ;
									$resultSelect=$connection2->prepare($sqlSelect);
									$resultSelect->execute($dataSelect);
								}
								catch(PDOException $e) { 
									print "<div class='error'>" . $e->getMessage() . "</div>" ; 
								}
								while ($rowSelect=$resultSelect->fetch()) {
									$selected="" ;
									if ($rowSelect["systemDefault"]=="Y") {
										$selected="selected" ;
									}
									print "<option $selected value='" . $rowSelect["gibboni18nID"] . "'>" . htmlPrep($rowSelect["name"]) . "</option>" ;
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
							print "<script type='text/javascript'>" ;	
								print "$(document).ready(function(){" ;
									print "\$(\".schoolYear\").hide();" ;
									print "\$(\".language\").hide();" ;
									print "\$(\".show_hide\").fadeIn(1000);" ;
									print "\$(\".show_hide\").click(function(){" ;
									print "\$(\".schoolYear\").fadeToggle(1000);" ;
									print "\$(\".language\").fadeToggle(1000);" ;
									print "});" ;
								print "});" ;
							print "</script>" ;
							?>
							<span style='font-size: 10px'><a class='show_hide' onclick='false' href='#'><?php print _("Options") ; ?></a> . <a href="<?php print $_SESSION[$guid]["absoluteURL"]?>/index.php?q=passwordReset.php"><?php print _("Forgot Password?") ; ?></a></span>
						</td>
					</tr>
					<tr>
						<td class="right" colspan=2>
							<input type="hidden" name="address" value="<?php print $_SESSION[$guid]["address"] ?>">
							<input type="submit" value="Login">
						</td>
					</tr>
				</table>
			</form>
			<?php
		}
	}

	//Show Module Menu
	//Check address to see if we are in the module area
	if (substr($_SESSION[$guid]["address"],0,8)=="/modules") {
		//Get and check the module name
		$moduleID=checkModuleReady($_SESSION[$guid]["address"], $connection2 );
		if ($moduleID!=FALSE) {
			$gibbonRoleIDCurrent=NULL ;
			if (isset($_SESSION[$guid]["gibbonRoleIDCurrent"])) {
				$gibbonRoleIDCurrent=$_SESSION[$guid]["gibbonRoleIDCurrent"] ;
			}
			try {
				$data=array("gibbonModuleID"=>$moduleID, "gibbonRoleID"=>$gibbonRoleIDCurrent); 
				$sql="SELECT gibbonModule.entryURL AS moduleEntry, gibbonModule.name AS moduleName, gibbonAction.name, gibbonAction.precedence, gibbonAction.category, gibbonAction.entryURL, URLList FROM gibbonModule, gibbonAction, gibbonPermission WHERE (gibbonModule.gibbonModuleID=:gibbonModuleID) AND (gibbonModule.gibbonModuleID=gibbonAction.gibbonModuleID) AND (gibbonAction.gibbonActionID=gibbonPermission.gibbonActionID) AND (gibbonPermission.gibbonRoleID=:gibbonRoleID) AND NOT gibbonAction.entryURL='' ORDER BY gibbonModule.name, category, gibbonAction.name, precedence DESC";
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { }
			
			if ($result->rowCount()>0) {			
				$output="<ul class='moduleMenu'>" ;
				
				$currentCategory="" ;
				$lastCategory="" ;
				$currentName="" ;
				$lastName="" ;
				$count=0;
				$links=0 ;
				while ($row=$result->fetch()) {
					$moduleName=$row["moduleName"] ;
					$moduleEntry=$row["moduleEntry"] ;
					
					//Set active link class
					$style="" ;
					if (strpos($row["URLList"],getActionName($_SESSION[$guid]["address"]))===0) {
						$style="class='active'" ;
					}
					
					$currentCategory=$row["category"] ;
					if (strpos($row["name"],"_")>0) {
						$currentName=_(substr($row["name"],0,strpos($row["name"],"_"))) ;
					}
					else {
						$currentName=_($row["name"]) ;
					}
							
					if ($currentName!=$lastName) {
						if ($currentCategory!=$lastCategory) {
							if ($count>0) {
								$output.="</ul></li>";
							}
							$output.="<li><h4>" . _($currentCategory) . "</h4>" ;
							$output.="<ul>" ;
							$output.="<li><a $style href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $row["moduleName"] . "/" . $row["entryURL"] . "'>" . _($currentName) . "</a></li>" ;
						}
						else {
							$output.="<li><a $style href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $row["moduleName"] . "/" . $row["entryURL"] . "'>" . _($currentName) . "</a></li>" ;
						}
						$links++ ;
					}
					$lastCategory=$currentCategory ;
					$lastName=$currentName ;
					$count++ ;
				}
				if ($count>0) {
					$output.="</ul></li>";
				}
				$output.="</ul>" ;
				
				if ($links>1 OR (isActionAccessible($guid, $connection2, "/modules/$moduleName/$moduleEntry")==FALSE)) {
					print $output ;
				}
			}
		}
	}
	
	//Show parent photo uploader
	if ($_SESSION[$guid]["address"]=="" AND isset($_SESSION[$guid]["username"])) {
		$sidebar=getParentPhotoUploader($connection2, $guid) ;
		if ($sidebar!=FALSE) {
			print $sidebar ;
		}
	}
	
	//Show homescreen widget for message wall
	if ($_SESSION[$guid]["address"]=="") {
		if (isset($_SESSION[$guid]["messageWallOutput"])) {
			if (isActionAccessible($guid, $connection2, "/modules/Messenger/messageWall_view.php")) {
				$attainmentAlternativeName=getSettingByScope($connection2, "Messenger", "enableHomeScreenWidget") ;
				if ($attainmentAlternativeName=="Y") {
					print "<h2>" ;
					print _("Message Wall") ;
					print "</h2>" ;
				
					if (count($_SESSION[$guid]["messageWallOutput"])<1) {
						print "<div class='warning'>" ;
							print _("There are no records to display.") ;
						print "</div>" ;
					}
					else if (is_array($_SESSION[$guid]["messageWallOutput"])==FALSE) {
						print "<div class='error'>" ;
							print _("An error occurred.") ;
						print "</div>" ;
					}
					else {
						$height=283 ;
						if (count($_SESSION[$guid]["messageWallOutput"])==1) {
							$height=94 ;
						}
						else if (count($_SESSION[$guid]["messageWallOutput"])==2) {
							$height=197 ;
						}
						print "<table id='messageWallWidget' style='width: 100%; height: " . $height . "px; border: 1px solid grey; padding: 6px; background-color: #eeeeee'>" ;
							//Content added by JS	
						$rand=rand(0, count($_SESSION[$guid]["messageWallOutput"]));
						$total=count($_SESSION[$guid]["messageWallOutput"]) ;
						$order = "";
						for($i=0; $i < $total; $i++) {
							$pos=($rand+$i)%$total;
							$order.="$pos, ";
							$message=$_SESSION[$guid]["messageWallOutput"][$pos];

							//COLOR ROW BY STATUS!
							print "<tr id='messageWall" . $pos . "'>" ;
								print "<td style='font-size: 95%; letter-spacing: 85%'>" ;
									//Image
									$style="style='width: 45px; height: 60px; float: right; margin-left: 6px; border: 1px solid black'" ;
									if ($message["photo"]=="" OR file_exists($_SESSION[$guid]["absolutePath"] . "/" . $message["photo"])==FALSE) {    
										print "<img $style  src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/anonymous_75.jpg'/>" ;
									}
									else {
										print "<img $style src='" . $_SESSION[$guid]["absoluteURL"] . "/" . $message["photo"] . "'/>" ;
									}
			
									//Message number
									print "<div style='margin-bottom: 4px; text-transform: uppercase; font-size: 70%; color: #888'>Message " . ($pos+1) . "</div>" ;
			
									//Title
									$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Messenger/messageWall_view.php#" . $message["gibbonMessengerID"] ;
									if (strlen($message["subject"])<=16) {
										print "<a style='font-weight: bold; font-size: 105%; letter-spacing: 85%; text-transform: uppercase' href='$URL'>"  . $message["subject"] . "</a><br/>" ;
									}
									else {
										print "<a style='font-weight: bold; font-size: 105%; letter-spacing: 85%; text-transform: uppercase' href='$URL'>"  . substr($message["subject"], 0, 16) . "...</a><br/>" ;
									}
			
									//Text
									print "<div style='margin-top: 5px'>" ;
										if (strlen(strip_tags($message["details"]))<=40) {
											print strip_tags($message["details"]) . "<br/>" ;
										}
										else {
											print substr(strip_tags($message["details"]), 0, 40) . "...<br/>" ;
										}
									print "</div>" ;
								print "</td>" ;
							print "</tr>" ;
						}
						print "</table>" ;
						$order = substr($order, 0, strlen($order)-2);
						print "<script type=\"text/javascript\">
							$(document).ready(function(){
								var order=[". $order . "];
								if(order.length > 3) {
								
									var fRow = $(\"#messageWall\".concat(order[0].toString()));
									var lRow = $(\"#messageWall\".concat(order[order.length-1].toString()));
									fRow.insertAfter(lRow);
									order.push(order.shift());
									
									$(\"#messageWall\".concat(order[0].toString())).attr('class', 'even');
									$(\"#messageWall\".concat(order[1].toString())).attr('class', 'odd');
									$(\"#messageWall\".concat(order[2].toString())).attr('class', 'even');
									
									for(var i=3; i<order.length; i++) {
										$(\"#messageWall\".concat(order[i].toString())).hide();
									}		
									
								}
								setInterval(function() {
									if(order.length > 3) {
									
										for(var i=0; i<order.length; i++) {
											$(\"#messageWall\".concat(order[i].toString())).show();
										}
										
										var fRow = $(\"#messageWall\".concat(order[0].toString()));
										var lRow = $(\"#messageWall\".concat(order[order.length-1].toString()));
										fRow.insertAfter(lRow);
										order.push(order.shift());
										
										$(\"#messageWall\".concat(order[0].toString())).attr('class', 'even');
										$(\"#messageWall\".concat(order[1].toString())).attr('class', 'odd');
										$(\"#messageWall\".concat(order[2].toString())).attr('class', 'even');
										
										for(var i=3; i<order.length; i++) {
											$(\"#messageWall\".concat(order[i].toString())).hide();
										}	
									}
								}, 8000);
							});
						</script>" ;
						
					}
				}
			}
		}
	}
	
	//Show upcoming deadlines
	if ($_SESSION[$guid]["address"]=="" AND isActionAccessible($guid, $connection2, "/modules/Planner/planner.php")) {
		$highestAction=getHighestGroupedAction($guid, "/modules/Planner/planner.php", $connection2) ;
		if ($highestAction=="Lesson Planner_viewMyClasses" OR $highestAction=="Lesson Planner_viewAllEditMyClasses" OR $highestAction=="Lesson Planner_viewEditAllClasses") {
			print "<h2>" ;
			print _("Homework & Deadlines") ;
			print "</h2>" ;
			
			try {
				$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
				$sql="
				(SELECT 'teacherRecorded' AS type, gibbonPlannerEntryID, gibbonUnitID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, date, timeStart, timeEnd, viewableStudents, viewableParents, homework, homeworkDueDateTime, role FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND NOT role='Student - Left' AND NOT role='Teacher - Left' AND homework='Y' AND (role='Teacher' OR (role='Student' AND viewableStudents='Y')) AND homeworkDueDateTime>'" . date("Y-m-d H:i:s") . "' AND ((date<'" . date("Y-m-d") . "') OR (date='" . date("Y-m-d") . "' AND timeEnd<='" . date("H:i:s") . "')))
				UNION
				(SELECT 'studentRecorded' AS type, gibbonPlannerEntry.gibbonPlannerEntryID, gibbonUnitID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, date, timeStart, timeEnd, 'Y' AS viewableStudents, 'Y' AS viewableParents, 'Y' AS homework, gibbonPlannerEntryStudentHomework.homeworkDueDateTime, role FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) JOIN gibbonPlannerEntryStudentHomework ON (gibbonPlannerEntryStudentHomework.gibbonPlannerEntryID=gibbonPlannerEntry.gibbonPlannerEntryID AND gibbonPlannerEntryStudentHomework.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND NOT role='Student - Left' AND NOT role='Teacher - Left' AND (role='Teacher' OR (role='Student' AND viewableStudents='Y')) AND gibbonPlannerEntryStudentHomework.homeworkDueDateTime>'" . date("Y-m-d H:i:s") . "' AND ((date<'" . date("Y-m-d") . "') OR (date='" . date("Y-m-d") . "' AND timeEnd<='" . date("H:i:s") . "')))
				 ORDER BY homeworkDueDateTime, type" ;
				$result=$connection2->prepare($sql);
				$result->execute($data); 
			}
			catch(PDOException $e) { print $e->getMessage() ;}
			if ($result->rowCount()<1) {
				print "<div class='success'>" ;
					print _("No upcoming deadlines. Yay!") ;
				print "</div>" ;
			}
			else {
				print "<ol>" ;
				$count=0 ;
				while ($row=$result->fetch()) {
					if ($count<5) {
						$diff=(strtotime(substr($row["homeworkDueDateTime"],0,10)) - strtotime(date("Y-m-d")))/86400 ;
						$category=getRoleCategory($_SESSION[$guid]["gibbonRoleIDCurrent"], $connection2) ;
						$style="padding-right: 3px;" ;
						if ($category=="Student") {
							//Calculate style for student-specified completion of teacher-recorded homework
							try {
								$dataCompletion=array("gibbonPlannerEntryID"=>$row["gibbonPlannerEntryID"], "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
								$sqlCompletion="SELECT gibbonPlannerEntryID FROM gibbonPlannerEntryStudentTracker WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND gibbonPersonID=:gibbonPersonID AND homeworkComplete='Y'" ;
								$resultCompletion=$connection2->prepare($sqlCompletion);
								$resultCompletion->execute($dataCompletion);
							}
							catch(PDOException $e) { }
							if ($resultCompletion->rowCount()==1) {
								$style.="; background-color: #B3EFC2" ;
							}
							
							//Calculate style for student-specified completion of student-recorded homework
							try {
								$dataCompletion=array("gibbonPlannerEntryID"=>$row["gibbonPlannerEntryID"], "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
								$sqlCompletion="SELECT gibbonPlannerEntryID FROM gibbonPlannerEntryStudentHomework WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND gibbonPersonID=:gibbonPersonID AND homeworkComplete='Y'" ;
								$resultCompletion=$connection2->prepare($sqlCompletion);
								$resultCompletion->execute($dataCompletion);
							}
							catch(PDOException $e) { }
							if ($resultCompletion->rowCount()==1) {
								$style.="; background-color: #B3EFC2" ;
							}
							
							//Calculate style for online submission completion
							try {
								$dataCompletion=array("gibbonPlannerEntryID"=>$row["gibbonPlannerEntryID"], "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
								$sqlCompletion="SELECT gibbonPlannerEntryID FROM gibbonPlannerEntryHomework WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND gibbonPersonID=:gibbonPersonID AND version='Final'" ;
								$resultCompletion=$connection2->prepare($sqlCompletion);
								$resultCompletion->execute($dataCompletion); 
							}
							catch(PDOException $e) { }
							if ($resultCompletion->rowCount()==1) {
								$style.="; background-color: #B3EFC2" ;
							}
						}
						
						//Calculate style for deadline
						if ($diff<2) {
							$style.="; border-right: 10px solid #cc0000" ;	
						}
						else if ($diff<4) {
							$style.="; border-right: 10px solid #D87718" ;	
						}
						
						print "<li style='$style'>" ;
						print  "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Planner/planner_view_full.php&gibbonPlannerEntryID=" . $row["gibbonPlannerEntryID"] . "&date=" . $row["date"] . "'>" . $row["course"] . "." . $row["class"] . "</a><br/>" ;
						print "<span style='font-style: italic'>Due at " . substr($row["homeworkDueDateTime"],11,5) . " on " . dateConvertBack($guid, substr($row["homeworkDueDateTime"],0,10)) ;
						print "</li>" ;
					}
					$count++ ;
				}
				print "</ol>" ;
			}
						
			print "<p style='padding-top: 15px; text-align: right'>" ;
			print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Planner/planner_deadlines.php'>" . _('View Homework') . "</a>" ;
			print "</p>" ;
		}
	}
	
	//Show recent results
	if ($_SESSION[$guid]["address"]=="" AND isActionAccessible($guid, $connection2, "/modules/Markbook/markbook_view.php")) {
		$highestAction=getHighestGroupedAction($guid, "/modules/Markbook/markbook_view.php", $connection2) ;
		if ($highestAction=="View Markbook_myMarks") {
			try {
				$dataEntry=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
				$sqlEntry="SELECT gibbonMarkbookEntryID, gibbonMarkbookColumn.name, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonMarkbookEntry JOIN gibbonMarkbookColumn ON (gibbonMarkbookEntry.gibbonMarkbookColumnID=gibbonMarkbookColumn.gibbonMarkbookColumnID) JOIN gibbonCourseClass ON (gibbonMarkbookColumn.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonIDStudent=:gibbonPersonID AND complete='Y' AND completeDate<='" . date("Y-m-d") . "' AND viewableStudents='Y' ORDER BY completeDate DESC, name" ;
				$resultEntry=$connection2->prepare($sqlEntry);
				$resultEntry->execute($dataEntry);
			}
			catch(PDOException $e) { }
			
			if ($resultEntry->rowCount()>0) {
				print "<h2>" ;
				print _("Recent Marks") ;
				print "</h2>" ;
				
				print "<ol>" ;
				$count=0 ;
				
				while ($rowEntry=$resultEntry->fetch() AND $count<5) {
					print "<li><a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Markbook/markbook_view.php#" . $rowEntry["gibbonMarkbookEntryID"] . "'>" . $rowEntry["course"] . "." . $rowEntry["class"] . "<br/><span style='font-size: 85%; font-style: italic'>" . $rowEntry["name"] . "</span></a></li>" ;
					$count++ ;
				}
				
				print "</ol>" ;
			}
		}
	}
	
	
	//Show My Classes
	if ($_SESSION[$guid]["address"]=="" AND isset($_SESSION[$guid]["username"])) {
		try {
			$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonPersonID"=> $_SESSION[$guid]["gibbonPersonID"]); 
			$sql="SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourseClass.gibbonCourseClassID FROM gibbonCourse, gibbonCourseClass, gibbonCourseClassPerson WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID AND gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND NOT role LIKE '% - Left%' ORDER BY course, class" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { }
		
		if ($result->rowCount()>0) {
			print "<h2 style='margin-bottom: 10px'  class='sidebar'>" ;
			print _("My Classes") ;
			print "</h2>" ;
			
			print "<table class='mini' cellspacing='0' style='width: 100%; table-layout: fixed;'>" ;
				print "<tr class='head'>" ;
						print "<th style='width: 36%; font-size: 85%; text-transform: uppercase'>" ;
						print _("Class") ;
					print "</th>" ;
					if (isActionAccessible($guid, $connection2, "/modules/Planner/planner.php")) {
						print "<th style='width: 16%; font-size: 60%; text-align: center; text-transform: uppercase'>" ;
							print _("Plan") ;
						print "</th>" ;
					}
					if (getHighestGroupedAction($guid, "/modules/Markbook/markbook_view.php", $connection2)=="View Markbook_allClassesAllData") {
						print "<th style='width: 16%; font-size: 60%; text-align: center; text-transform: uppercase'>" ;
							print _("Mark") ;
						print "</th>" ;
					}
					print "<th style='width: 16%; font-size: 60%; text-align: center; text-transform: uppercase'>" ;
						print _("People") ;
					print "</th>" ;
					if (isActionAccessible($guid, $connection2, "/modules/Planner/planner.php")) {
						print "<th style='width: 16%; font-size: 60%; text-align: center; text-transform: uppercase'>" ;
							print _("Tasks") ;
						print "</th>" ;
					}
				print "</tr>" ;
				
				$count=0;
				$rowNum="odd" ;
				while ($row=$result->fetch()) {
					if ($count%2==0) {
						$rowNum="even" ;
					}
					else {
						$rowNum="odd" ;
					}
					$count++ ;
					
					//COLOR ROW BY STATUS!
					print "<tr class=$rowNum>" ;
						print "<td style='word-wrap: break-word'>" ;
							print $row["course"] . "." . $row["class"] ;
						print "</td>" ;
						if (isActionAccessible($guid, $connection2, "/modules/Planner/planner.php")) {
							print "<td style='text-align: center'>" ;
								print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Planner/planner.php&gibbonCourseClassID=" . $row["gibbonCourseClassID"] . "&viewBy=class'><img style='margin-top: 3px' title='" . _('View Planner') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/planner.png'/></a> " ;
							print "</td>" ;
						}
						if (getHighestGroupedAction($guid, "/modules/Markbook/markbook_view.php", $connection2)=="View Markbook_allClassesAllData") {
							print "<td style='text-align: center'>" ;
								print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Markbook/markbook_view.php&gibbonCourseClassID=" . $row["gibbonCourseClassID"] . "'><img style='margin-top: 3px' title='" . _('View Markbook') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/markbook.png'/></a> " ;
							print "</td>" ;
						}
						print "<td style='text-align: center'>" ;
							print "<a href='index.php?q=/modules/Departments/department_course_class.php&gibbonCourseClassID=" . $row["gibbonCourseClassID"] . "&subpage=Participants'><img title='" . _('Participants') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/attendance.png'/></a>" ;
						print "</td>" ;
						if (isActionAccessible($guid, $connection2, "/modules/Planner/planner.php")) {
							print "<td style='text-align: center'>" ;
								print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Planner/planner_deadlines.php&gibbonCourseClassIDFilter=" . $row["gibbonCourseClassID"] . "'><img style='margin-top: 3px' title='" . _('View Homework') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/homework.png'/></a> " ;
							print "</td>" ;
						}
					print "</tr>" ;
				}
			print "</table>" ;
		}
	}
	
	//Show tag cloud
	if ($_SESSION[$guid]["address"]=="" AND isActionAccessible($guid, $connection2, "/modules/Resources/resources_view.php")) {
		include "./modules/Resources/moduleFunctions.php" ;
		print "<h2 class='sidebar'>" ;
			print _("Resource Tags") ;
		print "</h2>" ;
		print getTagCloud($guid, $connection2, 20) ;
		print "<p style='margin-bototm: 20px; text-align: right'>" ;
		print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Resources/resources_view.php'>" . _('View Resources') . "</a>" ;
		print "</p>" ;
	}
	
	//Show role switcher if user has more than one role
	if (isset($_SESSION[$guid]["username"])) {
		if (count($_SESSION[$guid]["gibbonRoleIDAll"])>1 AND $_SESSION[$guid]["address"]=="") {
			print "<h2 style='margin-bottom: 10px' class='sidebar'>" ;
			print _("Role Switcher") ;
			print "</h2>" ;
		
			if (isset($_GET["switchReturn"])) {
				$switchReturn=$_GET["switchReturn"] ;
			}
			else {
				$switchReturn="" ;
			}
			$switchReturnMessage="" ;
			$class="error" ;
			if (!($switchReturn=="")) {
				if ($switchReturn=="fail0") {
					$switchReturnMessage=_("Role ID not specified.") ;	
				}
				else if ($switchReturn=="fail1") {
					$switchReturnMessage=_("You do not have access to the specified role.") ;	
				}
				else if ($switchReturn=="success0") {
					$switchReturnMessage=_("Role switched successfully.") ;	
					$class="success" ;
				}
				print "<div class='$class'>" ;
					print $switchReturnMessage;
				print "</div>" ;
			} 
		
			print "<p>" ;
				print _("You have multiple roles within the system. Use the list below to switch role:") ;
			print "</p>" ;
		
			print "<ul>" ;
			for ($i=0; $i<count($_SESSION[$guid]["gibbonRoleIDAll"]); $i++) {
				if ($_SESSION[$guid]["gibbonRoleIDAll"][$i][0]==$_SESSION[$guid]["gibbonRoleIDCurrent"]) {
					print "<li><a href='roleSwitcherProcess.php?gibbonRoleID=" . $_SESSION[$guid]["gibbonRoleIDAll"][$i][0] . "'>" . _($_SESSION[$guid]["gibbonRoleIDAll"][$i][1]) . "</a> <i>" . _('(Active)') . "</i></li>" ;
				}
				else {
					print "<li><a href='roleSwitcherProcess.php?gibbonRoleID=" . $_SESSION[$guid]["gibbonRoleIDAll"][$i][0] . "'>" . _($_SESSION[$guid]["gibbonRoleIDAll"][$i][1]) . "</a></li>" ;
				}
			}
			print "</ul>" ;
		}	
	}
	
	if ($_SESSION[$guid]["sidebarExtra"]!="" AND $_SESSION[$guid]["sidebarExtraPosition"]=="bottom") {
		print "<div class='sidebarExtra'>" ;
		print $_SESSION[$guid]["sidebarExtra"] ;
		print "</div>" ;
	}
}

//Create the main menu
function mainMenu($connection2, $guid) {
	$output="" ;
	
	if (isset($_SESSION[$guid]["gibbonRoleIDCurrent"])==FALSE) {
		$output.="<ul id='nav'>" ;
		$output.="<li class='active'><a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php'>" . _('Home') . "</a></li>" ;
		$output.="</ul>" ;
	}
	else {
		try {
			$data=array("gibbonRoleID"=>$_SESSION[$guid]["gibbonRoleIDCurrent"]); 
			$sql="SELECT DISTINCT gibbonModule.name, gibbonModule.category, gibbonModule.entryURL FROM `gibbonModule`, gibbonAction, gibbonPermission WHERE (active='Y') AND (gibbonModule.gibbonModuleID=gibbonAction.gibbonModuleID) AND (gibbonAction.gibbonActionID=gibbonPermission.gibbonActionID) AND (gibbonPermission.gibbonRoleID=:gibbonRoleID) ORDER BY (gibbonModule.category='Other') ASC, category, name";
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			$output.="<div class='error'>" ;
			$output.=$e->getMessage() ;
			$output.="</div>" ;	
		}
	
		if ($result->rowCount()<1) {
			$output.="<ul id='nav'>" ;
			$output.="<li class='active'><a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php'>" . _('Home') . "</a></li>" ;
			$output.="</ul>" ;
		}
		else {
			$output.="<ul id='nav'>" ;
			$output.="<li><a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php'>" . _('Home') . "</a></li>" ;
		
			$currentCategory="" ;
			$lastCategory="" ;
			$count=0;
			while ($row=$result->fetch()) {
				$currentCategory=$row["category"] ;
			
				$entryURL=$row["entryURL"] ;
				if (isActionAccessible($guid, $connection2, "/modules/" . $row["name"] . "/" . $entryURL)==FALSE AND $entryURL!="index.php") {
					try {
						$dataEntry=array("gibbonRoleID"=>$_SESSION[$guid]["gibbonRoleIDCurrent"],"name"=>$row["name"]); 
						$sqlEntry="SELECT DISTINCT gibbonAction.entryURL FROM gibbonModule, gibbonAction, gibbonPermission WHERE (active='Y') AND (gibbonModule.gibbonModuleID=gibbonAction.gibbonModuleID) AND (gibbonAction.gibbonActionID=gibbonPermission.gibbonActionID) AND (gibbonPermission.gibbonRoleID=:gibbonRoleID) AND gibbonModule.name=:name ORDER BY gibbonAction.name";
						$resultEntry=$connection2->prepare($sqlEntry);
						$resultEntry->execute($dataEntry);
					}
					catch(PDOException $e) { }
					if ($resultEntry->rowCount()>0) {
						$rowEntry=$resultEntry->fetch() ;
						$entryURL=$rowEntry["entryURL"] ;
					}
				}
					
				if ($currentCategory!=$lastCategory) {
					if ($count>0) {
						$output.="</ul></li>";
					}
					$output.="<li><a href='#'>" . _($currentCategory) . "</a>" ;
					$output.="<ul>" ;
					$output.="<li><a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $row["name"] . "/" . $entryURL . "'>" . _($row["name"]) . "</a></li>" ;
				}
				else {
					$output.="<li><a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $row["name"] . "/" . $entryURL . "'>" . _($row["name"]) . "</a></li>" ;
				}
				$lastCategory=$currentCategory ;
				$count++ ;
			}
			if ($count>0) {
				$output.="</ul></li>";
			}
			$output.="</ul>" ;
		}
	}
	return $output ;
}

//Format address according to supplied inputs
function addressFormat( $address, $addressDistrict, $addressCountry ) {
	$return=FALSE ;
	
	if ($address!="") {
		$return.= $address ;
		if ($addressDistrict!="") {
			$return.= ", " . $addressDistrict ;
		}
		if ($addressCountry!="") {
			$return.= ", " . $addressCountry ;
		}
	}
	
	return $return ;
}

//Print out, preformatted indicator of max file upload size
function getMaxUpload( $multiple="" ) {
	$output="" ;
	$post=substr(ini_get("post_max_size"),0,(strlen(ini_get("post_max_size"))-1)) ;
	$file=substr(ini_get("upload_max_filesize"),0,(strlen(ini_get("upload_max_filesize"))-1)) ;
	
	$output.="<div style='margin-top: 10px; font-style: italic; color: #c00'>" ;
	if ($multiple==TRUE) {
		if ($post<$file) {
			$output.=sprintf(_('Maximum size for all files: %1$sMB'), $post) . "<br/>" ;
		}
		else {
			$output.=sprintf(_('Maximum size for all files: %1$sMB'), $file) . "<br/>" ;
		}
	}
	else {
		if ($post<$file) {
			$output.=sprintf(_('Maximum file size: %1$sMB'), $post) . "<br/>" ;
		}
		else {
			$output.=sprintf(_('Maximum file size: %1$sMB'), $file) . "<br/>" ;
		}
	}
	$output.="</div>" ; 
	
	return $output ;
}


//Encode strring using htmlentities with the ENT_QUOTES option
function htmlPrep($str) {
	return htmlentities($str, ENT_QUOTES, "UTF-8") ;
}


//Returns the risk level of the highest-risk condition for an individual
function getHighestMedicalRisk( $gibbonPersonID, $connection2 ) {
	$output=FALSE ;
	
	try {
		$dataAlert=array("gibbonPersonID"=>$gibbonPersonID); 
		$sqlAlert="SELECT * FROM gibbonPersonMedical JOIN gibbonPersonMedicalCondition ON (gibbonPersonMedical.gibbonPersonMedicalID=gibbonPersonMedicalCondition.gibbonPersonMedicalID) JOIN gibbonAlertLevel ON (gibbonPersonMedicalCondition.gibbonAlertLevelID=gibbonAlertLevel.gibbonAlertLevelID) WHERE gibbonPersonID=:gibbonPersonID ORDER BY gibbonAlertLevel.sequenceNumber DESC" ;
		$resultAlert=$connection2->prepare($sqlAlert);
		$resultAlert->execute($dataAlert);
	}
	catch(PDOException $e) { }

	if ($resultAlert->rowCount()>0) {
		$rowAlert=$resultAlert->fetch() ;
		$output=array() ;
		$output[0]=$rowAlert["gibbonAlertLevelID"] ;
		$output[1]=_($rowAlert["name"]) ;
		$output[2]=$rowAlert["nameShort"] ;
		$output[3]=$rowAlert["color"] ;
		$output[4]=$rowAlert["colorBG"] ;
	}
	
	return $output ;
}

//Gets age from date of birth, in days and months, from Unix timestamp
function getAge($stamp, $short=FALSE, $yearsOnly=FALSE) {
	$output="" ;
	$diff=time()-$stamp ;
	$years=floor($diff/31556926); 
	$months=floor(($diff-($years*31556926))/2629743.83) ;
	if ($short==TRUE) {
		$output=$years . _("y") . ", " . $months . _("m") ;
	}
	else {
		$output=$years . " " . _("years") . ", " . $months . " " . _("months") ;
	}
	if ($yearsOnly==TRUE) {
		$output=$years ;
	}
	return $output ;
}

//Looks at the grouped actions accessible to the user in the current module and returns the highest
function getHighestGroupedAction($guid, $address, $connection2) {
	$output=FALSE ;
	$moduleID=checkModuleReady($address, $connection2 );
	
	try {
		$data=array("actionName"=>"%" . getActionName($address) . "%", "gibbonRoleID"=>$_SESSION[$guid]["gibbonRoleIDCurrent"], "moduleID"=>$moduleID); 
		$sql="SELECT gibbonAction.name FROM gibbonAction, gibbonPermission, gibbonRole WHERE (gibbonAction.URLList LIKE :actionName) AND (gibbonAction.gibbonActionID=gibbonPermission.gibbonActionID) AND (gibbonPermission.gibbonRoleID=gibbonRole.gibbonRoleID) AND (gibbonPermission.gibbonRoleID=:gibbonRoleID) AND (gibbonAction.gibbonModuleID=:moduleID) ORDER BY precedence DESC" ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
		if ($result->rowCount()>0) {
			$row=$result->fetch() ;
			$output=$row["name"] ;
		}
	}
	catch(PDOException $e) { }
	
	return $output ;
}

//Returns the category of the specified role
function getRoleCategory($gibbonRoleID, $connection2) {
	$output=FALSE ;
	
	try {
		$data=array("gibbonRoleID"=>$gibbonRoleID); 
		$sql="SELECT * FROM gibbonRole WHERE gibbonRoleID=:gibbonRoleID" ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { }
	
	if ($result->rowCount()==1) {
		$row=$result->fetch() ;
		$output=$row["category"] ;
	}
	return $output ;
}

//Converts a specified date (YYYY-MM-DD) into a UNIX timestamp
function dateConvertToTimestamp( $date ) {
	list($dateYear, $dateMonth, $dateDay)=explode('-', $date);
	$timestamp=mktime(0, 0, 0, $dateMonth, $dateDay, $dateYear);
	
	return $timestamp ;
}

//Checks to see if a specified date (YYYY-MM-DD) is a day where school is open in the current academic year. There is an option to search all years
function isSchoolOpen($guid, $date, $connection2, $allYears="" ) {
	//Set test variables
	$isInTerm=FALSE ;
	$isSchoolDay=FALSE ;
	$isSchoolOpen=FALSE ;
	
	//Turn $date into UNIX timestamp and extract day of week
	$timestamp=dateConvertToTimestamp($date) ;
	$dayOfWeek=date("D",$timestamp) ;
	
	//See if date falls into a school term
	try {
		$data=array(); 
		$sqlWhere="" ;
		if ($allYears!=TRUE) {
			$data[$_SESSION[$guid]["gibbonSchoolYearID"]]=$_SESSION[$guid]["gibbonSchoolYearID"] ;
			$sqlWhere=" AND gibbonSchoolYear.gibbonSchoolYearID=:" . $_SESSION[$guid]["gibbonSchoolYearID"] ;
		}
		
		$sql="SELECT gibbonSchoolYearTerm.firstDay, gibbonSchoolYearTerm.lastDay FROM gibbonSchoolYearTerm, gibbonSchoolYear WHERE gibbonSchoolYearTerm.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID $sqlWhere" ; 
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { }
	while ($row=$result->fetch()) {
		if ($date>=$row["firstDay"] AND $date<=$row["lastDay"]) {
			$isInTerm=TRUE ;
		}
	}
	
	//See if date's day of week is a school day
	if ($isInTerm==TRUE) {
		try {
			$data=array("nameShort"=>$dayOfWeek); 
			$sql="SELECT * FROM gibbonDaysOfWeek WHERE nameShort=:nameShort AND schoolDay='Y'" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { }
		if ($result->rowCount()>0) {
			$isSchoolDay=TRUE ;
		}
	}
	
	//See if there is a special day
	if ($isInTerm==TRUE AND $isSchoolDay==TRUE) {
		try {
			$data=array("date"=>$date); 
			$sql="SELECT * FROM gibbonSchoolYearSpecialDay WHERE type='School Closure' AND date=:date" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { }
		
		if ($result->rowCount()<1) {
			$isSchoolOpen=TRUE ;
		}
	}
	
	return $isSchoolOpen ;
}

//DEPRECATED IN VERSION 8 IN FAVOUR OF getUserPhoto
//Prints a given user photo, or a blank if not available
function printUserPhoto($guid, $path, $size) {
	$sizeStyle="style='width: 75px; height: 100px'" ;
	if ($size==240) {
		$sizeStyle="style='width: 240px; height: 320px'" ;
	}
	if ($path=="" OR file_exists($_SESSION[$guid]["absolutePath"] . "/" . $path)==FALSE) {    
		print "<img $sizeStyle class='user' src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/anonymous_" . $size . ".jpg'/><br/>" ;
	}
	else {
		print "<img $sizeStyle class='user' src='" . $_SESSION[$guid]["absoluteURL"] . "/$path'/><br/>" ;
	}
}

//Gets a given user photo, or a blank if not available
function getUserPhoto($guid, $path, $size) {
	$output="" ;
	$sizeStyle="style='width: 75px; height: 100px'" ;
	if ($size==240) {
		$sizeStyle="style='width: 240px; height: 320px'" ;
	}
	if ($path=="" OR file_exists($_SESSION[$guid]["absolutePath"] . "/" . $path)==FALSE) {    
		$output="<img $sizeStyle class='user' src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/anonymous_" . $size . ".jpg'/><br/>" ;
	}
	else {
		$output="<img $sizeStyle class='user' src='" . $_SESSION[$guid]["absoluteURL"] . "/$path'/><br/>" ;
	}
	return $output ;
}

//Gets Members of a roll group and prints them as a table.
//Three modes: normal (roll order, surnema, firstName), surname (surname, preferredName), preferredName (preferredNam, surname)
function printRollGroupTable($guid, $gibbonRollGroupID, $columns, $connection2, $confidential=TRUE, $orderBy="Normal") {
	try {
		$dataRollGroup=array("gibbonRollGroupID"=>$gibbonRollGroupID); 
		if ($orderBy=="surname") {
			$sqlRollGroup="SELECT * FROM gibbonStudentEnrolment INNER JOIN gibbonPerson ON gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID WHERE gibbonRollGroupID=:gibbonRollGroupID AND status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') ORDER BY surname, preferredName" ;
		}
		else if ($orderBy=="preferredName") {
			$sqlRollGroup="SELECT * FROM gibbonStudentEnrolment INNER JOIN gibbonPerson ON gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID WHERE gibbonRollGroupID=:gibbonRollGroupID AND status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') ORDER BY preferredName, surname" ;
		}
		else {
			$sqlRollGroup="SELECT * FROM gibbonStudentEnrolment INNER JOIN gibbonPerson ON gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID WHERE gibbonRollGroupID=:gibbonRollGroupID AND status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') ORDER BY rollOrder, surname, preferredName" ;
		}
		$resultRollGroup=$connection2->prepare($sqlRollGroup);
		$resultRollGroup->execute($dataRollGroup);
	}
	catch(PDOException $e) { }
	
	print "<table class='noIntBorder' cellspacing='0' style='width:100%'>" ;
	$count=0 ;
	
	if ($confidential) {
		print "<tr>" ;
			print "<td style='text-align: right' colspan='$columns'>" ;
				print "<input checked type='checkbox' name='confidential' class='confidential' value='Yes' />" ;
				print "<span style='font-size: 85%; font-weight: normal; font-style: italic'> " . _('Show Confidential Data') . "</span>" ;
			print "</td>" ;
		print "</tr>" ;
	}
	
	while ($rowRollGroup=$resultRollGroup->fetch()) {
		if ($count%$columns==0) {
			print "<tr>" ;
		}
		print "<td style='width:20%; text-align: center; vertical-align: top'>" ;
		
		//Alerts, if permission allows
		if ($confidential) {
			print getAlertBar($guid, $connection2, $rowRollGroup["gibbonPersonID"], $rowRollGroup["privacy"], "id='confidential$count'") ;
		}
		
		//User photo
		print getUserPhoto($guid, $rowRollGroup["image_75"], 75) ;
		
		//HEY SHORTY IT'S YOUR BIRTHDAY!
		$daysUntilNextBirthday=daysUntilNextBirthday($rowRollGroup["dob"]) ;
		if ($daysUntilNextBirthday==0) {
			print "<img title='" . sprintf(_('%1$s  birthday today!'), $rowRollGroup["preferredName"] . "&#39;s") . "' style='z-index: 99; margin: -20px 0 0 74px; width: 25px; height: 25px' src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/gift_pink.png'/>" ;
		}
		else if ($daysUntilNextBirthday>0 AND $daysUntilNextBirthday<8) {
			print "<img title='" ;
			if ($daysUntilNextBirthday!=1) {
				print sprintf(_('%1$s days until %2$s birthday!'), $daysUntilNextBirthday, $rowRollGroup["preferredName"] . "&#39;s") ;
			}
			else {
				print sprintf(_('%1$s day until %2$s birthday!'), $daysUntilNextBirthday, $rowRollGroup["preferredName"] . "&#39;s") ;
			}
			print "' style='z-index: 99; margin: -20px 0 0 74px; width: 25px; height: 25px' src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/gift.png'/>" ;
		}
		
		print "<div style='padding-top: 5px'><b><a href='index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=" . $rowRollGroup["gibbonPersonID"] . "'>" . formatName("", $rowRollGroup["preferredName"], $rowRollGroup["surname"], "Student") . "</a><br/><br/></div>" ;
		print "</td>" ;
		
		if ($count%$columns==($columns-1)) {
			print "</tr>" ;
		}
		$count++ ;
	}
	
	for ($i=0;$i<$columns-($count%$columns);$i++) {
		print "<td></td>" ;
	}
	
	if ($count%$columns!=0) {
		print "</tr>" ;
	}
	
	print "</table>" ;	
	
	?>
	<script type="text/javascript">
		/* Confidential Control */
		$(document).ready(function(){
			$(".confidential").click(function(){
				if ($('input[name=confidential]:checked').val()=="Yes" ) {
					<?php
					for ($i=0; $i<$count; $i++) {
						?>
						$("#confidential<?php print $i ?>").slideDown("fast", $("#confidential<?php print $i ?>").css("{'display' : 'table-row', 'border' : 'right'}")); 
						<?php
					}
					?>
				} 
				else {
					<?php
					for ($i=0; $i<$count; $i++) {
						?>
						$("#confidential<?php print $i ?>").slideUp("fast"); 
						<?php
					}
					?>
				}
			 });
		});
	</script>
	<?php
}

//Gets Members of a roll group and prints them as a table.
function printClassGroupTable($guid, $gibbonCourseClassID, $columns, $connection2) {
	try {
		$dataClassGroup=array("gibbonCourseClassID"=>$gibbonCourseClassID); 
		$sqlClassGroup="SELECT * FROM gibbonCourseClassPerson INNER JOIN gibbonPerson ON gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID WHERE gibbonCourseClassID=:gibbonCourseClassID AND status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND (NOT role='Student - Left') AND (NOT role='Teacher - Left') ORDER BY role DESC, surname, preferredName" ;
		$resultClassGroup=$connection2->prepare($sqlClassGroup);
		$resultClassGroup->execute($dataClassGroup);
	}
	catch(PDOException $e) { }
	
	print "<table class='noIntBorder' cellspacing='0' style='width:100%'>" ;
	$count=0 ;
	while ($rowClassGroup=$resultClassGroup->fetch()) {
		if ($count%$columns==0) {
			print "<tr>" ;
		}
		print "<td style='width:20%; text-align: center; vertical-align: top'>" ;
		
		//Alerts, if permission allows
		print getAlertBar($guid, $connection2, $rowClassGroup["gibbonPersonID"], $rowClassGroup["privacy"]) ;
		
		//User photo
		print getUserPhoto($guid, $rowClassGroup["image_75"], 75) ;
		
		//HEY SHORTY IT'S YOUR BIRTHDAY!
		$daysUntilNextBirthday=daysUntilNextBirthday($rowClassGroup["dob"]) ;
		if ($daysUntilNextBirthday==0) {
			print "<img title='" . sprintf(_('%1$s  birthday today!'), $rowClassGroup["preferredName"] . "&#39;s") . "' style='z-index: 99; margin: -20px 0 0 74px; width: 25px; height: 25px' src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/gift_pink.png'/>" ;
		}
		else if ($daysUntilNextBirthday>0 AND $daysUntilNextBirthday<8) {
			print "<img title='$daysUntilNextBirthday " ;
			if ($daysUntilNextBirthday!=1) {
				print sprintf(_('days until %1$s birthday!'), $rowClassGroup["preferredName"] . "&#39;s") ;
			}
			else {
				print sprintf(_('day until %1$s birthday!'), $rowClassGroup["preferredName"] . "&#39;s") ;
			}
			print "' style='z-index: 99; margin: -20px 0 0 74px; width: 25px; height: 25px' src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/gift.png'/>" ;
		}
		
		if ($rowClassGroup["role"]=="Student") {
			print "<div style='padding-top: 5px'><b><a href='index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=" . $rowClassGroup["gibbonPersonID"] . "'>" . formatName("", $rowClassGroup["preferredName"], $rowClassGroup["surname"], "Student") . "</a></b><br/>" ;
		}
		else {
			print "<div style='padding-top: 5px'><b>" . formatName( $rowClassGroup["title"], $rowClassGroup["preferredName"], $rowClassGroup["surname"], "Staff") . "</b><br/>" ;
		}
		
		print "<i>" . $rowClassGroup["role"] . "</i><br/><br/></div>" ;
		print "</td>" ;
		
		if ($count%$columns==($columns-1)) {
			print "</tr>" ;
		}
		$count++ ;
	}
	
	for ($i=0;$i<$columns-($count%$columns);$i++) {
		print "<td></td>" ;
	}
	
	if ($count%$columns!=0) {
		print "</tr>" ;
	}
	
	print "</table>" ;	
}

function getAlertBar($guid, $connection2, $gibbonPersonID, $privacy="", $divExtras="", $div=TRUE) {
	$output="" ;
	
	$highestAction=getHighestGroupedAction($guid, "/modules/Students/student_view_details.php", $connection2) ;
	if ($highestAction=="View Student Profile_full") {
		if ($div==TRUE) {
			$output.="<div $divExtras style='width: 83px; text-align: right; height: 16px; padding: 3px 0px; margin: auto'><b>" ;
		}
		
		//Individual Needs
		try {
			$dataAlert=array("gibbonPersonID"=>$gibbonPersonID); 
			$sqlAlert="SELECT * FROM gibbonINPersonDescriptor JOIN gibbonAlertLevel ON (gibbonINPersonDescriptor.gibbonAlertLevelID=gibbonAlertLevel.gibbonAlertLevelID) WHERE gibbonPersonID=:gibbonPersonID ORDER BY sequenceNumber DESC" ;
			$resultAlert=$connection2->prepare($sqlAlert);
			$resultAlert->execute($dataAlert);
		}
		catch(PDOException $e) { }
		if ($resultAlert->rowCount()>0) {
			$rowAlert=$resultAlert->fetch() ;
			$highestLevel=_($rowAlert["name"]) ;
			$highestColour=$rowAlert["color"] ;
			$highestColourBG=$rowAlert["colorBG"] ;
			if ($resultAlert->rowCount()==1) {
				$title=$resultAlert->rowCount() . " " . sprintf(_('Individual Needs alert is set, with an alert level of %1$s.'), $rowAlert["name"]) ;
			}
			else {
				$title=$resultAlert->rowCount() . " " . sprintf(_('Individual Needs alerts are set, up to a maximum alert level of %1$s.'), $rowAlert["name"]) ;
			}
			$output.="<a style='color: #" . $highestColour . "; text-decoration: none' href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=" . $gibbonPersonID . "&subpage=Individual Needs'><div title='$title' style='float: right; text-align: center; vertical-align: middle; max-height: 14px; height: 14px; width: 14px; border-top: 2px solid #" . $highestColour . "; margin-right: 2px; background-color: #" . $highestColourBG . "'>" . _('IN') . "</div></a>" ; 
		}
		
		//Academic
		$gibbonAlertLevelID="" ;
		try {
			$dataAlert=array("gibbonPersonIDStudent"=>$gibbonPersonID, "gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
			$sqlAlert="SELECT * FROM gibbonMarkbookEntry JOIN gibbonMarkbookColumn ON (gibbonMarkbookEntry.gibbonMarkbookColumnID=gibbonMarkbookColumn.gibbonMarkbookColumnID) JOIN gibbonCourseClass ON (gibbonMarkbookColumn.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonPersonIDStudent=:gibbonPersonIDStudent AND (attainmentConcern='Y' OR effortConcern='Y') AND complete='Y' AND gibbonSchoolYearID=:gibbonSchoolYearID" ;
			$resultAlert=$connection2->prepare($sqlAlert);
			$resultAlert->execute($dataAlert);
		}
		catch(PDOException $e) { 
			$_SESSION[$guid]["sidebarExtra"].="<div class='error'>" . $e->getMessage() . "</div>" ; 
		}
		if ($resultAlert->rowCount()>1 AND $resultAlert->rowCount()<=4) {
			$gibbonAlertLevelID=003 ;
		}
		else if ($resultAlert->rowCount()>4 AND $resultAlert->rowCount()<=8) {
			$gibbonAlertLevelID=002 ;
		}
		else if ($resultAlert->rowCount()>8) {
			$gibbonAlertLevelID=001 ;	
		}
		if ($gibbonAlertLevelID!="") {
			$alert=getAlert($connection2, $gibbonAlertLevelID) ;
			if ($alert!=FALSE) {
				$title=sprintf(_('Student has a %1$s alert for academic concern in the current academic year.'), _($alert["name"])) ;
				$output.="<a style='color: #" . $alert["color"] . "; text-decoration: none' href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=" . $gibbonPersonID . "&subpage=Markbook&filter=" . $_SESSION[$guid]["gibbonSchoolYearID"] . "'><div title='$title' style='float: right; text-align: center; vertical-align: middle; max-height: 14px; height: 14px; width: 14px; border-top: 2px solid #" . $alert["color"] . "; margin-right: 2px; background-color: #" . $alert["colorBG"] . "'>" . _('A') . "</div></a>" ; 
			}
		}
		
		//Behaviour
		$gibbonAlertLevelID="" ;
		try {
			$dataAlert=array("gibbonPersonID"=>$gibbonPersonID); 
			$sqlAlert="SELECT * FROM gibbonBehaviour WHERE gibbonPersonID=:gibbonPersonID AND type='Negative' AND date>'" . date("Y-m-d", (time()-(24*60*60*60))) . "'" ;
			$resultAlert=$connection2->prepare($sqlAlert);
			$resultAlert->execute($dataAlert);
		}
		catch(PDOException $e) { 
			$_SESSION[$guid]["sidebarExtra"].="<div class='error'>" . $e->getMessage() . "</div>" ; 
		}
		if ($resultAlert->rowCount()>1 AND $resultAlert->rowCount()<=4) {
			$gibbonAlertLevelID=003 ;
		}
		else if ($resultAlert->rowCount()>4 AND $resultAlert->rowCount()<=8) {
			$gibbonAlertLevelID=002 ;
		}
		else if ($resultAlert->rowCount()>8) {
			$gibbonAlertLevelID=001 ;	
		}
		if ($gibbonAlertLevelID!="") {
			$alert=getAlert($connection2, $gibbonAlertLevelID) ;
			if ($alert!=FALSE) {
				$title=sprintf(_('Student has a %1$s alert for behaviour over the past 60 days.'), _($alert["name"])) ;
				$output.="<a style='color: #" . $alert["color"] . "; text-decoration: none' href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=" . $gibbonPersonID . "&subpage=Behaviour Record'><div title='$title' style='float: right; text-align: center; vertical-align: middle; max-height: 14px; height: 14px; width: 14px; border-top: 2px solid #" . $alert["color"] . "; margin-right: 2px; background-color: #" . $alert["colorBG"] . "'>" . _('B') . "</div></a>" ; 
			}
		}
		
		//Medical
		$alert=getHighestMedicalRisk( $gibbonPersonID, $connection2 ) ;
		if ($alert!=FALSE) {
			$highestLevel=$alert[1] ;
			$highestColour=$alert[3] ;
			$highestColourBG=$alert[4] ;
			$title=sprintf(_('Medical alerts are set, up to a maximum of %1$s'), $highestLevel) ;
			$output.="<a style='color: #" . $highestColour . "; text-decoration: none' href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=" . $gibbonPersonID . "&subpage=Medical'><div title='$title' style='float: right; text-align: center; vertical-align: middle; max-height: 14px; height: 14px; width: 14px; border-top: 2px solid #" . $highestColour . "; margin-right: 2px; background-color: #" . $highestColourBG . "'><b>" . _('M') . "</b></div></a>" ; 
		}
		
		//Privacy
		$privacySetting=getSettingByScope( $connection2, "User Admin", "privacy" ) ;
		if ($privacySetting=="Y" AND $privacy!="") {
			$alert=getAlert($connection2, 001) ;
			$title=sprintf(_('Privacy is required: %1$s'), $privacy) ;
			$output.="<div title='$title' style='float: right; text-align: center; vertical-align: middle; max-height: 14px; height: 14px; width: 14px; border-top: 2px solid #" . $alert["color"] . "; margin-right: 2px; color: #" . $alert["color"] . "; background-color: #" . $alert["colorBG"] . "'>" . _('P') . "</div>" ; 
		}
		
		if ($div==TRUE) {
			$output.="</div>" ;
		}
	}
	
	return $output ;
}

//Gets system settings from database and writes them to individual session variables.
function getSystemSettings($guid, $connection2) {
	
	//System settings from gibbonSetting
	try {
		$data=array(); 
		$sql="SELECT * FROM gibbonSetting WHERE scope='System'" ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { 
		$_SESSION[$guid]["systemSettingsSet"]=FALSE ;
	}

	while ($row=$result->fetch()) {
		$name=$row["name"] ;
		$_SESSION[$guid][$name]=$row["value"] ;
	}
	
	//Language settings from gibboni18n
	try {
		$data=array(); 
		$sql="SELECT * FROM gibboni18n WHERE systemDefault='Y'" ; 
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { 
		$_SESSION[$guid]["systemSettingsSet"]=FALSE ;
	}
	if ($result->rowCount()==1) {
		$row=$result->fetch() ;
		setLanguageSession($guid, $row) ;
	}
		
	$_SESSION[$guid]["systemSettingsSet"]=TRUE ;
}

//Set language session variables
function setLanguageSession($guid, $row) {
	$_SESSION[$guid]["i18n"]["gibboni18nID"]=$row["gibboni18nID"] ;
	$_SESSION[$guid]["i18n"]["code"]=$row["code"] ;
	$_SESSION[$guid]["i18n"]["name"]=$row["name"] ;
	$_SESSION[$guid]["i18n"]["dateFormat"]=$row["dateFormat"] ;
	$_SESSION[$guid]["i18n"]["dateFormatRegEx"]=$row["dateFormatRegEx"] ;
	$_SESSION[$guid]["i18n"]["dateFormatPHP"]=$row["dateFormatPHP"] ;
	$_SESSION[$guid]["i18n"]["maintainerName"]=$row["maintainerName"] ;
	$_SESSION[$guid]["i18n"]["maintainerWebsite"]=$row["maintainerWebsite"] ;
	$_SESSION[$guid]["i18n"]["rtl"]=$row["rtl"] ;
}

//Gets the desired setting, specified by name and scope.
function getSettingByScope( $connection2, $scope, $name ) {
	$output=FALSE ;
	
	try {
		$data=array("scope"=>$scope, "name"=>$name); 
		$sql="SELECT * FROM gibbonSetting WHERE scope=:scope AND name=:name" ;
		$result=$connection2->prepare($sql);
		$result->execute($data); 
	}
	catch(PDOException $e) { }
	if ($result->rowCount()==1) {
		$row=$result->fetch() ;
		$output=$row["value"] ;
	}
	
	return $output ;
}

//Converts date from language-specific format to YYYY-MM-DD
function dateConvert($guid, $date) {
	$output=FALSE ;
	
	if ($date!="") {
		if ($_SESSION[$guid]["i18n"]["dateFormat"]=="mm/dd/yyyy") {
			$firstSlashPosition=2 ;
			$secondSlashPosition=5 ;
			$output=substr($date,($secondSlashPosition+1)) . "-" . substr($date,0,$firstSlashPosition) . "-" . substr($date,($firstSlashPosition+1),2) ; 
		}
		else {
			$output=date('Y-m-d', strtotime(str_replace('/', '-', $date)));
		}
	}
	return $output ;
}

//Converts date from YYYY-MM-DD to language-specific format.
function dateConvertBack($guid, $date) {
	$output=FALSE ;
	
	if ($date!="") {
		$timestamp=strtotime($date) ;
		if ($_SESSION[$guid]["i18n"]["dateFormatPHP"]!="") {
			$output=date($_SESSION[$guid]["i18n"]["dateFormatPHP"], $timestamp) ;
		}
		else {
			$output=date("d/m/Y", $timestamp) ;
		}
	}
	return $output ;
}

function isActionAccessible($guid, $connection2, $address, $sub="") {
	$output=FALSE ;
	//Check user is logged in
	if (isset($_SESSION[$guid]["username"])) {
		//Check user has a current role set
		if ($_SESSION[$guid]["gibbonRoleIDCurrent"]!="") {
			//Check module ready
			$moduleID=checkModuleReady($address, $connection2);
			if ($moduleID!=FALSE) {
				//Check current role has access rights to the current action.
				try {
					$data=array("actionName"=>"%" . getActionName($address) . "%", "gibbonRoleID"=>$_SESSION[$guid]["gibbonRoleIDCurrent"]); 
					$sqlWhere="" ;
					if ($sub!="") {
						$data["sub"]=$sub ;
						$sqlWhere="AND gibbonAction.name=:sub" ;
					}
					$sql="SELECT gibbonAction.name FROM gibbonAction, gibbonPermission, gibbonRole WHERE (gibbonAction.URLList LIKE :actionName) AND (gibbonAction.gibbonActionID=gibbonPermission.gibbonActionID) AND (gibbonPermission.gibbonRoleID=gibbonRole.gibbonRoleID) AND (gibbonPermission.gibbonRoleID=:gibbonRoleID) AND (gibbonAction.gibbonModuleID=$moduleID) $sqlWhere" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
					if ($result->rowCount()>0) {
						$output=TRUE ;
					}
				}
				catch(PDOException $e) {}
			}
		}
	}
	return $output ;
}

function isModuleAccessible($guid, $connection2, $address="") {
	if ($address=="") {
		$address=$_SESSION[$guid]["address"];
	}
	$output=FALSE ;
	//Check user is logged in
	if ($_SESSION[$guid]["username"]!="") {
		//Check user has a current role set
		if ($_SESSION[$guid]["gibbonRoleIDCurrent"]!="") {
			//Check module ready
			$moduleID=checkModuleReady($address, $connection2 );
			if ($moduleID!=FALSE) {
				//Check current role has access rights to an action in the current module.
				try {
					$data=array("gibbonRoleID"=>$_SESSION[$guid]["gibbonRoleIDCurrent"], "moduleID"=>$moduleID); 
					$sql="SELECT * FROM gibbonAction, gibbonPermission, gibbonRole WHERE (gibbonAction.gibbonActionID=gibbonPermission.gibbonActionID) AND (gibbonPermission.gibbonRoleID=gibbonRole.gibbonRoleID) AND (gibbonPermission.gibbonRoleID=:gibbonRoleID) AND (gibbonAction.gibbonModuleID=:moduleID)" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
					if ($result->rowCount()>0) {
						$output=TRUE ;
					}
				}
				catch(PDOException $e) {}
			}
		}
	}
	
	return $output ;
}

function printPagination($guid, $total, $page, $pagination, $position, $get="") {
	if ($position=="bottom") {
		$class="paginationBottom" ;
	}
	else {
		$class="paginationTop" ;
	}
	
	print "<div class='$class'>" ;
		$totalPages=ceil($total/$pagination) ;
		$i=0;
		print _("Records") . " " . (($page-1)*$_SESSION[$guid]["pagination"]+1) . "-" ;
		if (($page*$_SESSION[$guid]["pagination"])>$total) {
			print $total ;
		}
		else {
			print ($page*$_SESSION[$guid]["pagination"]) ;
		}
		print " " . _('of') . " " . $total . " : " ;
		
		if ($totalPages<=10) {
			for ($i=0;$i<=($total/$pagination);$i++) {
				if ($i==($page-1)) {
					print "$page " ;
				}
				else {
					print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=" . $_SESSION[$guid]["address"] . "&page=" . ($i+1) . "&$get'>" . ($i+1) . "</a> " ; 
				}
			}
		}
		else {
			if ($page>1) {
				print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=" . $_SESSION[$guid]["address"] . "&page=1&$get'>" . _('First') . "</a> " ; 
				print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=" . $_SESSION[$guid]["address"] . "&page=" . ($page-1) . "&$get'>" . _('Previous') . "</a> " ; 
			}
			else {
				print _("First") . " " . _("Previous") . " " ;
			}
			
			$spread=10 ;
			for ($i=0;$i<=($total/$pagination);$i++) {
				if ($i==($page-1)) {
					print "$page " ;
				}
				else if ($i>($page-(($spread/2)+2)) AND $i<($page+(($spread/2)))) {
					print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=" . $_SESSION[$guid]["address"] . "&page=" . ($i+1) . "&$get'>" . ($i+1) . "</a> " ; 
				}
			}
			
			if ($page!=$totalPages) {
				print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=" . $_SESSION[$guid]["address"] . "&page=" . ($page+1) . "&$get'>" . _('Next') . "</a> " ; 
				print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=" . $_SESSION[$guid]["address"] . "&page=" . $totalPages . "&$get'>" . _('Last') . "</a> " ; 
			}
			else {
				print _("Next") . " " . _("Last") ;
			}
			
			
		}
	print "</div>" ;
}

//Get list of user roles from database, and convert to array
function getRoleList($gibbonRoleIDAll, $connection2) {
	@session_start() ;
	
	$output=array() ;
	
	//Tokenise list of roles
	$roles=explode(',', $gibbonRoleIDAll) ;
	
	//Check that roles exist
	$count=0 ;
	for ($i=0; $i<count($roles); $i++) {
		try {
			$data=array("gibbonRoleID"=>$roles[$i]); 
			$sql="SELECT * FROM gibbonRole WHERE gibbonRoleID=:gibbonRoleID";
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { }
		if ($result->rowCount()==1) {
			$row=$result->fetch() ;
			$output[$count][0]=$row["gibbonRoleID"] ;
			$output[$count][1]=_($row["name"]) ;
			$count++ ;
		}
	}
	
	//Return list of roles
	return $output ;
}

//Get the module name from the address
function getModuleName($address) {
	return substr(substr($address,9),0,strpos(substr($address,9),"/")) ;
}

//Get the action name from the address
function getActionName($address) {
	return substr($address,(10+strlen(getModuleName($address)))) ;
}

//Using the current address, checks to see that a module exists and is ready to use, returning the ID if it is
function checkModuleReady($address, $connection2) {
	$output=FALSE;
	
	//Get module name from address
	$module=getModuleName($address) ;
	try {
		$data=array("name"=>$module); 
		$sql="SELECT * FROM gibbonModule WHERE name=:name AND active='Y'" ;
 		$result=$connection2->prepare($sql);
		$result->execute($data);
		if ($result->rowCount()==1) {
			$row=$result->fetch() ;
			$output=$row["gibbonModuleID"] ;
		}
	}
	catch(PDOException $e) { }
		
	return $output ;
}

//Using the current address, get's the module's category
function getModuleCategory($address, $connection2 ) {
	$output=FALSE;
	
	//Get module name from address
	$module=getModuleName($address) ;
	
	try {
		$data=array("name"=>$module); 
		$sql="SELECT * FROM gibbonModule WHERE name=:name AND active='Y'" ;
 		$result=$connection2->prepare($sql);
		$result->execute($data); 
	}
	catch(PDOException $e) { }
	if ($result->rowCount()==1) {
		$row=$result->fetch() ;
		$output=_($row["category"]) ;
	}
		
	return $output ;
}

//GET THE CURRENT YEAR AND SET IT AS A GLOBAL VARIABLE
function setCurrentSchoolYear($guid,  $connection2 ) {
	@session_start() ;

	//Run query
	try {
		$data=array(); 
		$sql="SELECT * FROM gibbonSchoolYear WHERE status='Current'";
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { }

	//Check number of rows returned.
	//If it is not 1, show error
	if (!($result->rowCount()==1)) {
		die(_("Your request failed due to a database error.")) ;
	}
	//Else get schoolYearID
	else {
		$row=$result->fetch() ;
		$_SESSION[$guid]["gibbonSchoolYearID"]=$row["gibbonSchoolYearID"] ;
		$_SESSION[$guid]["gibbonSchoolYearName"]=$row["name"] ;
		$_SESSION[$guid]["gibbonSchoolYearSequenceNumber"]=$row["sequenceNumber"] ;
		$_SESSION[$guid]["gibbonSchoolYearFirstDay"]=$row["firstDay"] ;
		$_SESSION[$guid]["gibbonSchoolYearLastDay"]=$row["lastDay"] ;
	}
}

function nl2brr($string) {
	return preg_replace("/\r\n|\n|\r/", "<br/>", $string);
}

//Take a school year, and return the previous one, or false if none
function getPreviousSchoolYearID($gibbonSchoolYearID, $connection2) {
	$output=FALSE ;
	
	try {
		$data=array("gibbonSchoolYearID"=>$gibbonSchoolYearID); 
		$sql="SELECT * FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearID" ;
		$result=$connection2->prepare($sql);
		$result->execute($data); 
	}
	catch(PDOException $e) { }
	if ($result->rowcount()==1) {
		$row=$result->fetch() ;
		try {
			$dataPrevious=array("sequenceNumber"=>$row["sequenceNumber"]); 
			$sqlPrevious="SELECT * FROM gibbonSchoolYear WHERE sequenceNumber<:sequenceNumber ORDER BY sequenceNumber DESC" ;
			$resultPrevious=$connection2->prepare($sqlPrevious);
			$resultPrevious->execute($dataPrevious); 
		}
		catch(PDOException $e) { }
		if ($resultPrevious->rowCount()>=1) {
			$rowPrevious=$resultPrevious->fetch() ;
			$output=$rowPrevious["gibbonSchoolYearID"] ;	
		}
	}
	
	return $output ;
}

//Take a school year, and return the previous one, or false if none
function getNextSchoolYearID($gibbonSchoolYearID, $connection2) {		
	$output=FALSE ;
	
	try {
		$data=array("gibbonSchoolYearID"=>$gibbonSchoolYearID); 
		$sql="SELECT * FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearID" ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { }
	if ($result->rowcount()==1) {
		$row=$result->fetch() ;
		try {
			$dataPrevious=array("sequenceNumber"=>$row["sequenceNumber"]); 
			$sqlPrevious="SELECT * FROM gibbonSchoolYear WHERE sequenceNumber>:sequenceNumber ORDER BY sequenceNumber ASC" ;
			$resultPrevious=$connection2->prepare($sqlPrevious);
			$resultPrevious->execute($dataPrevious); 
		}
		catch(PDOException $e) { }
		if ($resultPrevious->rowCount()>=1) {
			$rowPrevious=$resultPrevious->fetch() ;
			$output=$rowPrevious["gibbonSchoolYearID"] ;	
		}
	}
	
	return $output ;
}

//Take a year group, and return the next one, or false if none
function getNextYearGroupID($gibbonYearGroupID, $connection2) {
	$output=FALSE ;
	try {
		$data=array("gibbonYearGroupID"=>$gibbonYearGroupID); 
		$sql="SELECT * FROM gibbonYearGroup WHERE gibbonYearGroupID=:gibbonYearGroupID" ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { }
	if ($result->rowCount()==1) {
		$row=$result->fetch() ;
		try {
			$dataPrevious=array("sequenceNumber"=>$row["sequenceNumber"]); 
			$sqlPrevious="SELECT * FROM gibbonYearGroup WHERE sequenceNumber>:sequenceNumber ORDER BY sequenceNumber ASC" ;
			$resultPrevious=$connection2->prepare($sqlPrevious);
			$resultPrevious->execute($dataPrevious); 
		}
		catch(PDOException $e) { }
		if ($resultPrevious->rowCount()>=1) {
			$rowPrevious=$resultPrevious->fetch() ;
			$output=$rowPrevious["gibbonYearGroupID"] ;	
		}
	}
	
	return $output ;
}

//Return the last school year in the school, or false if none
function getLastYearGroupID($connection2) {
	$output=FALSE ;
	try {
		$data=array(); 
		$sql="SELECT * FROM gibbonYearGroup ORDER BY sequenceNumber DESC" ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { }
	if ($result->rowCount()>1) {
		$row=$result->fetch() ;
		$output=$row["gibbonYearGroupID"] ;	
	}
	
	return $output ;
}

function randomPassword($length) {
  if (!(is_int($length))) {
  	$length=8 ;
  }
  else if ($length>255) {
  	$length=255;
  }
  
  $charList="abcdefghijkmnopqrstuvwxyz023456789";
  $password='' ;
  
  	//Generate the password
  	for ($i=0;$i<$length;$i++) {
  		$password=$password . substr($charList, rand(1,strlen($charList)),1);
  	}
  
  	return $password;
}


/*	Author: Raju Mazumder
 	email:rajuniit@gmail.com
	Class:A simple class to export mysql query and whole html and php page to excel,doc etc
 	Downloaded from: http://webscripts.softpedia.com/script/PHP-Clases/Export-To-Excel-50394.html
	License: GNU GPL 
*/
class ExportToExcel
{
	function setHeader($excel_file_name)//this function used to set the header variable
	{	
		header("Content-type: application/octet-stream");//A MIME attachment with the content type "application/octet-stream" is a binary file.
		//Typically, it will be an application or a document that must be opened in an application, such as a spreadsheet or word processor. 
		header("Content-Disposition: attachment; filename=$excel_file_name");//with this extension of file name you tell what kind of file it is.
		header("Pragma: no-cache");//Prevent Caching
		header("Expires: 0");//Expires and 0 mean that the browser will not cache the page on your hard drive
	}
	function exportWithQuery($qry,$excel_file_name,$conn)//to export with query
	{
		$body=NULL ;
		
		try {
			$tmprst=$conn->query($qry);  
			$tmprst->setFetchMode(PDO::FETCH_NUM); 
		}
		catch(PDOException $e) { }

		$header="<center><table cellspacing='0' border=1px>";
		$num_field=$tmprst->columnCount();
		while($row=$tmprst->fetch())
		{
			$body.="<tr>";
			for($i=0;$i<$num_field;$i++)
			{
				$body.="<td>".$row[$i]."</td>";
			}
			$body.="</tr>";	
		}
		
		$this->setHeader($excel_file_name);
		echo $header.$body."</table>";
	}
	//Ross Parker added the ability to specify paramaters to pass into the file, via a session variable.
	function exportWithPage($guid, $php_page,$excel_file_name,$params="")
	{
		$this->setHeader($excel_file_name);
		$_SESSION[$guid]["exportToExcelParams"]=$params ;
		require_once "$php_page";
	}	
}

function formatPhone($num) { //Function by Zeromatik on StackOverflow
    $num = preg_replace('/[^0-9]/', '', $num);
    $len = strlen($num);

    if($len == 7) $num = preg_replace('/([0-9]{2})([0-9]{2})([0-9]{3})/', '$1 $2 $3', $num);
    elseif($len == 8) $num = preg_replace('/([0-9]{4})([0-9]{4})/', '$1 $2', $num);
    elseif($len == 9) $num = preg_replace('/([0-9]{3})([0-9]{2})([0-9]{2})([0-9]{2})/', '$1 - $2 $3 $4', $num);
    elseif($len == 10) $num = preg_replace('/([0-9]{3})([0-9]{2})([0-9]{2})([0-9]{3})/', '$1 - $2 $3 $4', $num);

    return $num;
}
?>
