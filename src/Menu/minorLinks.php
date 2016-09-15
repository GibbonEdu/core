<?php
/**
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

namespace Gibbon\Menu;

use Gibbon\core\trans ;
use Gibbon\core\helper ;
use Gibbon\core\security ;
use Gibbon\Record\notification ;

/**
 * Main menu building Class
 *
 * @version	24th April 2016
 * @since	24th April 2016
 * @author	Craig Rayner
 * @package	Gibbon
 */
class minorLinks extends menu
{
	/**
	 * set Menu
	 * 
	 * was getMinorLinks
	 * @version	24th April 2016
	 * @since	moved from functions.php
	 * @return	HTML String
	 */
	public function setMenu() {
		
		if ($this->menu !== NULL)
			return $this->menu ;
		if ($this->session->get('refreshCache'))
		{
			helper::injectView($this->view);
			$security = $this->view->getSecurity();
			$return  = '';
			if ($this->session->get("username") === NULL) {
				if ($this->session->get("webLink")!="") {
					$return.= trans::__( "Return to") . " <a style='margin-right: 12px' target='_blank' href='" . $this->session->get("webLink") . "'>" . $this->session->get("organisationNameShort") . " " . trans::__( 'Website') . "</a>" ;
				}
			}
			else {
				$name = $this->session->get("preferredName") . " " . $this->session->get("surname");
				if (! $this->session->isEmpty("gibbonRoleIDCurrentCategory")) {
					if ($this->session->get("gibbonRoleIDCurrentCategory")=="Student") {
						$highestAction = $this->view->getSecurity()->getHighestGroupedAction("/modules/Students/student_view_details.php") ;
						if ($highestAction == "View Student Profile_brief") {
							$name = "<a href='" . $this->session->get("absoluteURL") . "/index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=" . $this->session->get("gibbonPersonID") . "'>" . $name . "</a>";
						}
					}
				}
				$return.= $name . " . ";
				$return.="<a href='./index.php?q=/modules/Security/logout.php&divert=true'>" . trans::__("Logout") . "</a> . <a href='./index.php?q=/preferences.php'>" . trans::__( 'Preferences') . "</a>" ;
				if ($this->session->get("emailLink")!="") {
					$return.=" . <a target='_blank' href='" . $this->session->get("emailLink") . "'>" . trans::__( 'Email') . "</a>" ;
				}
				if ($this->session->get("webLink")!="") {
					$return.=" . <a target='_blank' href='" . $this->session->get("webLink") . "'>" . $this->session->get("organisationNameShort") . " " . trans::__( 'Website') . "</a>" ;
				}
				if ($this->session->get("website")!="") {
					$return.=" . <a target='_blank' href='" . $this->session->get("website") . "'>" . trans::__( 'My Website') . "</a>" ;
				}
		
				$this->session->set("likesCount", helper::countLikesByRecipient($this->session->get("gibbonPersonID"), "count", $this->session->get("gibbonSchoolYearID"))) ;
				//Show likes
				if (! $this->session->isEmpty("likesCount")) {
					if ($this->session->get("likesCount")>0) {
						$return.=" . <a title='" . trans::__( 'Likes') . "' href='" . $this->session->get("absoluteURL") . "/index.php?q=likes.php'>" . $this->session->get("likesCount") . " x " . $this->view->renderReturn('default.minorLinks.like_on'). "</a>" ;
					}
					else {
						$return.=" . " . $this->session->get("likesCount") . " x " . $this->view->renderReturn('default.minorLinks.like_off'). "" ;
					}
				}
		
				//GET & SHOW NOTIFICATIONS
				$obj = new notification($this->view);
				$notifications = $obj->findAllBy(array('gibbonPersonID'=>$this->session->get("gibbonPersonID"), 'status'=>'New'));
				
/*				$dataNotifications=array("gibbonPersonID"=>$this->session->get("gibbonPersonID"), "gibbonPersonID2"=>$this->session->get("gibbonPersonID"));
				$sqlNotifications="(SELECT gibbonNotification.*, gibbonModule.name AS source 
				FROM gibbonNotification 
					JOIN gibbonModule ON (gibbonNotification.gibbonModuleID=gibbonModule.gibbonModuleID) 
				WHERE gibbonPersonID=:gibbonPersonID AND status='New')
					UNION (SELECT gibbonNotification.*, 'System' AS source 
						FROM gibbonNotification 
						WHERE gibbonModuleID IS NULL 
							AND gibbonPersonID=:gibbonPersonID2 
							AND status='New')
				ORDER BY timestamp DESC, source, text" ;
				$nObj = new \Gibbon\Record\notification($this->view);
				$notifications = $nObj->findAll($sqlNotifications, $dataNotifications, '_'); */
		
				//Refresh notifications every 10 seconds for staff, 120 seconds for everyone else
				$interval = 120000 ;
				if ($this->session->get("gibbonRoleIDCurrentCategory")=="Staff") $interval = 10000 ;
				
				$action = '/modules/Notifications/index_notification_ajax.php';
				$tObj = new \Gibbon\Form\token($action);

				$return .= '
				<script type="text/javascript">
					$(document).ready(function(){
						setInterval(function() {
							$("#notifications").load("index.php?q=/modules/Notifications/index_notifications_ajax.php", {
									"action": "'. $tObj->generateAction($action) . '", 
									"divert": "true", 
									"_token": "' . $tObj->generateToken($action) . '"
								});
						}, "' . $interval . '");
					});
				</script>' ;
		
				$return.="<div id='notifications' style='display: inline'>" ;
					//CHECK FOR SYSTEM ALARM
					if (! $this->session->isEmpty("gibbonRoleIDCurrentCategory")) {
						if ($this->session->get("gibbonRoleIDCurrentCategory")=="Staff") {
							$alarm=$this->config->getSettingByScope( "System", "alarm") ;
							if ($alarm=="General" OR $alarm=="Lockdown" OR $alarm=="Custom") {
								$type="general" ;
								if ($alarm=="Lockdown") {
									$type="lockdown" ;
								}
								else if ($alarm=="Custom") {
									$type="custom" ;
								}
								$return.="<script>
									if ($('div#TB_window').is(':visible')===false) {
										var url = '" . GIBBON_URL . "index.php?q=/modules/Notifications/index_notification_ajax_alarm.php&divert=true&type=" . $type . "&KeepThis=true&TB_iframe=true&width=1000&height=500';
										$(document).ready(function() {
											tb_show('', url);
											$('div#TB_window').addClass('alarm') ;
										}) ;
									}
								</script>" ;
							}
						}
					}
		
					if (count($notifications)>0) {
						$return.=" . <a title='" . trans::__( 'Notifications') . "' href='" . GIBBON_URL . "index.php?q=/modules/Notifications/notifications.php'>" . count($notifications) . " x " . $this->view->renderReturn('default.minorLinks.notification_on') . "</a>" ;
					}
					else {
						$return.=" . 0 x " . $this->view->renderReturn('default.minorLinks.notification_off') ;
					}
				$return.="</div>" ;
		
				//MESSAGE WALL!
				if ($this->view->getSecurity()->isActionAccessible("/modules/Messenger/messageWall_view.php", null, '')) {
					$messenger = new \Module\Messenger\Functions\functions($this->view);
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
					if ($this->session->get('refreshCache') OR (@$_GET["q"]=="/modules/Messenger/messenger_post.php" AND $addReturn=="success0") OR (@$_GET["q"]=="/modules/Messenger/messenger_postQuickWall.php" AND $addReturn=="success0") OR (@$_GET["q"]=="/modules/Messenger/messenger_manage_edit.php" AND $updateReturn=="success0") OR (@$_GET["q"]=="/modules/Messenger/messenger_manage.php" AND $deleteReturn=="success0")) {
						$messages = $messenger->getMessages("result") ;
						$messages=unserialize($messages) ;
						$resultPosts=$this->pdo->executeQuery($messages[0], $messages[1]);
		
						$this->session->set("messageWallCount", 0) ;
						if ($this->pdo->getQuerySuccess() && $resultPosts->rowCount()>0) {
							$count=0 ;
							$output=array() ;
							$last="" ;
							while ($rowPosts=$resultPosts->fetch()) {
								if ($last==$rowPosts["gibbonMessengerID"]) {
									$output[($count-1)]["source"]=$output[($count-1)]["source"] . "<br/>" .$rowPosts["source"] ;
								}
								else {
									$output[$this->session->get("messageWallCount")]["photo"]=$rowPosts["image_240"] ;
									$output[$this->session->get("messageWallCount")]["subject"]=$rowPosts["subject"] ;
									$output[$this->session->get("messageWallCount")]["details"]=$rowPosts["body"] ;
									$output[$this->session->get("messageWallCount")]["author"]=formatName($rowPosts["title"], $rowPosts["preferredName"], $rowPosts["surname"], $rowPosts["category"]) ;
									$output[$this->session->get("messageWallCount")]["source"]=$rowPosts["source"] ;
									$output[$this->session->get("messageWallCount")]["gibbonMessengerID"]=$rowPosts["gibbonMessengerID"] ;
		
									$this->session->plus1("messageWallCount") ;
									$last=$rowPosts["gibbonMessengerID"] ;
									$count++ ;
								}
							}
							$this->session->set("messageWallOutput", $output) ;
						}
					}
		
					//Check for house logo (needed to get bubble, below, in right spot)
					$isHouseLogo=FALSE ;
					if (! $this->session->isEmpty("gibbonHouseIDLogo") AND ! $this->session->isEmpty("gibbonHouseIDName")) {
						if  (! $this->session->isEmpty("gibbonHouseIDLogo")) {
							$isHouseLogo=TRUE ;
						}
					}
		
					$URL=$this->session->get("absoluteURL") . "/index.php?q=/modules/Messenger/messageWall_view.php" ;
					if ($this->session->isEmpty("messageWallCount")) {
						$return.=" . 0 x ".$this->view->renderReturn('default.minorLinks.messageWall_none')."" ;
					}
					else {
						if ($this->session->get("messageWallCount")<1) {
							$return.=" . 0 x ".$this->view->renderReturn('default.minorLinks.messageWall_none')."" ;
						}
						else {
							$return.=" . ".$this->view->renderReturn('default.minorLinks.messageWall')."</a>" ;
							if ($this->session->get("pageLoads")==0 AND ($this->session->get("messengerLastBubble")==NULL OR $this->session->get("messengerLastBubble")<date("Y-m-d"))) {
								print $messageBubbleBGColour=$this->config->getSettingByScope( "Messenger", "messageBubbleBGColour") ;
								$bubbleBG="" ;
								if ($messageBubbleBGColour!="") {
									$bubbleBG="; background-color: rgba(" . $messageBubbleBGColour . ")!important" ;
									$return.="<style>" ;
										$return.=".ui-tooltip, .arrow:after { $bubbleBG }" ;
									$return.="</style>" ;
		
								}
								$messageBubbleWidthType=$this->config->getSettingByScope( "Messenger", "messageBubbleWidthType") ;
								$bubbleWidth=300 ;
								$bubbleLeft=770 ;
								if ($messageBubbleWidthType=="Wide") {
									$bubbleWidth=700 ;
									$bubbleLeft=370 ;
								}
								if ($isHouseLogo) { //Spacing with house logo
									$bubbleLeft=$bubbleLeft-70 ;
									$return.="<div id='messageBubbleArrow' style=\"left: 1019px; top: 58px; z-index: 9999\" class='arrow top'></div>" ;
									$return.="<div id='messageBubble' style=\"left: " . $bubbleLeft . "px; top: 74px; width: " . $bubbleWidth . "px; min-width: " . $bubbleWidth . "px; max-width: " . $bubbleWidth . "px; min-height: 100px; text-align: center; padding-bottom: 10px\" class=\"ui-tooltip ui-widget ui-corner-all ui-widget-content\" role=\"tooltip\">" ;
								}
								else { //Spacing without house logo
									$return.="<div id='messageBubbleArrow' style=\"left: 1089px; top: 38px; z-index: 9999\" class='arrow top'></div>" ;
									$return.="<div id='messageBubble' style=\"left: " . $bubbleLeft . "px; top: 54px; width: " . $bubbleWidth . "px; min-width: " . $bubbleWidth . "px; max-width: " . $bubbleWidth . "px; min-height: 100px; text-align: center; padding-bottom: 10px\" class=\"ui-tooltip ui-widget ui-corner-all ui-widget-content\" role=\"tooltip\">" ;
								}
									$return.="<div class=\"ui-tooltip-content\">" ;
										$return.="<div style='font-weight: bold; font-style: italic; font-size: 120%; margin-top: 10px; margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px dotted rgba(255,255,255,0.5); display: block'>" . trans::__( 'New Messages') . "</div>" ;
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
											$return.="<i>" . trans::__( 'Plus more') . "...</i>" ;
										}
									$return.="</div>" ;
									$return.="<div style='text-align: right; margin-top: 20px; color: #666'>" ;
										$return.="<a onclick='$(\"#messageBubble\").hide(\"fade\", {}, 1); $(\"#messageBubbleArrow\").hide(\"fade\", {}, 1)' style='text-decoration: none; color: #666' href='" . $URL . "'>" . trans::__( 'Read All') . "</a> . " ;
										$return.="<a style='text-decoration: none; color: #666' onclick='$(\"#messageBubble\").hide(\"fade\", {}, 1000); $(\"#messageBubbleArrow\").hide(\"fade\", {}, 1000)' href='#'>" . trans::__( 'Dismiss') . "</a>" ;
									$return.="</div>" ;
								$return.="</div>" ;
		
								$messageBubbleAutoHide=$this->config->getSettingByScope( "Messenger", "messageBubbleAutoHide") ;
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
		
								$data=array("messengerLastBubble"=>date("Y-m-d"), "gibbonPersonID"=>$this->session->get("gibbonPersonID") );
								$sql="UPDATE gibbonPerson SET messengerLastBubble=:messengerLastBubble WHERE gibbonPersonID=:gibbonPersonID" ;
								$result=$this->pdo->executeQuery($data, $sql);
							}
						}
					}
				}
		
				//House logo
				if (@$isHouseLogo) {
					$return.=" . <img class='minorLinkIconLarge' title='" . $this->session->get("gibbonHouseIDName") . "' style='vertical-align: -75%; margin-left: 4px' src='" . $this->session->get("absoluteURL") . "/" . $this->session->get("gibbonHouseIDLogo") . "'/>" ;
				}
			}
			$this->menu = $return ;
			$this->session->set('menuMinorLinks', $this->menu);
		}
		else
			$this->menu = $this->session->get('menuMinorLinks');
		return $this->menu ;
	}

}
?>