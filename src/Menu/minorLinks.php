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
use Gibbon\Record\notification ;
use Gibbon\Record\person ;
use Gibbon\Form\token ;
use Module\Messenger\Functions\functions as messengerFunctions;
use stdClass ;

/**
 * Main menu building Class
 *
 * @version	19th September 2016
 * @since	24th April 2016
 * @author	Craig Rayner
 * @package		Gibbon
 * @subpackage	Menu
 */
class minorLinks extends menu
{
	/**
	 * set Menu
	 * 
	 * was getMinorLinks
	 * @version	19th September 2016
	 * @since	moved from functions.php
	 * @return	HTML String
	 */
	public function setMenu() {
		
		$el = $this->session->get('display.menu.minorLinks', array());
		if (empty($el['refresh']) || $el['refresh'] < 1 || empty($el['content']))
		{
			$security = $this->view->getSecurity();
			$return  = '';
			if ($this->session->isEmpty("username")) {
				if ($this->session->get("webLink")!="") {
					$return.= trans::__("Return to") . " <a style='margin-right: 12px' target='_blank' href='" . $this->session->get("webLink") . "'>" . $this->session->get("organisationNameShort") . " " . trans::__( 'Website') . "</a>" ;
				}
			}
			else {
				$return .= '<div class="minorLinksContent">';
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
				$return.="<a href='./index.php?q=/modules/Security/logout.php&divert=true'>" . trans::__("Logout") . "</a> . <a href='./index.php?q=/preferences.php'>" . trans::__('Preferences') . "</a>" ;
				if ($this->session->get("emailLink")!="") {
					$return.=" . <a target='_blank' href='" . $this->session->get("emailLink") . "'>" . trans::__('Email') . "</a>" ;
				}
				if ($this->session->get("webLink")!="") {
					$return.=" . <a target='_blank' href='" . $this->session->get("webLink") . "'>" . $this->session->get("organisationNameShort") . " " . trans::__('Website') . "</a>" ;
				}
				if ($this->session->get("website")!="") {
					$return.=" . <a target='_blank' href='" . $this->session->get("website") . "'>" . trans::__('My Website') . "</a>" ;
				}
				
				$return .= $this->showLikes();
				
				$return .= $this->showNotifications();
				
				$return .= $this->messageWall();
				$return .= '</div>';
				
			}

			$this->session->set('display.menu.minorLinks.refresh', $this->view->getConfig()->get('cache', 15));
			if (empty($return)) 
				$this->session->set('display.menu.minorLinks.refresh', 0);
			$this->session->set('display.menu.minorLinks.content', $return);
			$this->session->set('menuMinorLinks', $this->menu);
		}
		else
		{
			$this->session->plus('display.menu.minorLinks.refresh', -1);
		}
		$this->menu = $this->session->get('display.menu.minorLinks', array());
		return $this->menu ;
	}

	/**
	 * message Wall
	 * 
	 * @version	18th September 2016
	 * @since	moved from functions.php
	 * @return	HTML String
	 */
	public function messageWall()	
	{
		//MESSAGE WALL!

		if ($this->view->getSecurity()->isActionAccessible("/modules/Messenger/messageWall_view.php", null, '')) {
			$messenger = new messengerFunctions($this->view);

			$addReturn = isset($_GET["addReturn"]) ? $_GET["addReturn"] : null;

			$updateReturn = isset($_GET["updateReturn"]) ? $_GET["updateReturn"] : null ;

			$deleteReturn = isset($_GET["deleteReturn"]) ? $_GET["deleteReturn"] : null;
				
			$return = '<div id="messageLink">';
			
			$el = new stdClass;
			
			$q = isset($_GET["q"]) ? $_GET["q"] : null ;

			if ($this->session->get('refreshCache') 
				|| ($q == "/modules/Messenger/messenger_post.php" && $addReturn=="success0") 
				|| ($q == "/modules/Messenger/messenger_postQuickWall.php" && $addReturn=="success0") 
				|| ($q == "/modules/Messenger/messenger_manage_edit.php" && $updateReturn=="success0") 
				|| ($q == "/modules/Messenger/messenger_manage.php" && $deleteReturn=="success0")) 
			{
				$messages = $messenger->getMessages("result") ;
				$messages = unserialize($messages) ;
				$resultPosts = $this->pdo->executeQuery($messages[0], $messages[1]);

				$this->session->set("messageWallCount", 0) ;
				if ($this->pdo->getQuerySuccess() && $resultPosts->rowCount()>0) {
					$count = 0 ;
					$el->output = array() ;
					$last = "" ;
					while ($rowPosts = $resultPosts->fetch()) {
						if ($last == $rowPosts["gibbonMessengerID"]) {
							$el->output[($count-1)]["source"] = $el->output[($count-1)]["source"] . "<br/>" .$rowPosts["source"] ;
						}
						else {
							$el->output[$this->session->get("messageWallCount")]["photo"] = $rowPosts["image_240"] ;
							$el->output[$this->session->get("messageWallCount")]["subject"] = $rowPosts["subject"] ;
							$el->output[$this->session->get("messageWallCount")]["details"] = $rowPosts["body"] ;
							$pObj = new person($this->view, $rowPosts['gibbonPersonID']);
							$el->output[$this->session->get("messageWallCount")]["author"] = $pObj->formatName() ;
							$el->output[$this->session->get("messageWallCount")]["source"] = $rowPosts["source"] ;
							$el->output[$this->session->get("messageWallCount")]["gibbonMessengerID"] = $rowPosts["gibbonMessengerID"] ;

							$this->session->plus("messageWallCount") ;
							$last = $rowPosts["gibbonMessengerID"] ;
							$count++ ;
						}
					}
					$this->session->set("messageWallOutput", $el->output) ;
				}
			}

			//Check for house logo (needed to get bubble, below, in right spot)
			$el->isHouseLogo = false ;
			if ($this->session->notEmpty("gibbonHouseIDLogo") && $this->session->notEmpty("gibbonHouseIDName")) {
				if  ( $this->session->notEmpty("gibbonHouseIDLogo")) {
					$el->isHouseLogo = true ;
				}
			}

			$el->URL = $this->session->get("absoluteURL") . "/index.php?q=/modules/Messenger/messageWall_view.php" ;
			if ($this->session->isEmpty("messageWallCount")) {
				$return .= " . 0 x ".$this->view->renderReturn('default.minorLinks.messageWall_none')."" ;
			}
			else {
				if ($this->session->get("messageWallCount") < 1) {
					$return .= " . 0 x ".$this->view->renderReturn('default.minorLinks.messageWall_none')."" ;
				}
				else {
					$return .= " . <a href='".$el->URL."'>".$this->session->get("messageWallCount")." x ".$this->view->renderReturn('default.minorLinks.messageWall')."</a>" ;
					if ($this->session->isEmpty('messenger.lastShowBubble')) 
						$this->session->set('messenger.lastShowBubble', 0);
					if ($this->session->get('messenger.lastShowBubble') <= (time() - $this->config->getSettingByScope("Messenger", "messageRepeatTime")) && ($this->session->isEmpty("messengerLastBubble") || $this->session->get("messengerLastBubble") < date("Y-m-d")))
					{
						$messageBubbleBGColour = $this->config->getSettingByScope("Messenger", "messageBubbleBGColour") ;
						$el->bubbleBG = "" ;
						if (! empty($messageBubbleBGColour)) {
							$el->bubbleBG = "; background-color: rgba(" . $messageBubbleBGColour . ")!important" ;
							$return .= "<style>" ;
								$return .= ".ui-tooltip, .arrow:after { $el->bubbleBG }" ;
							$return .= "</style>" ;

						}
						$messageBubbleWidthType = $this->config->getSettingByScope("Messenger", "messageBubbleWidthType") ;
						$el->bubbleWidth = 300 ;
						$el->bubbleLeft = 770 ;
						if ($messageBubbleWidthType == "Wide") {
							$el->bubbleWidth = 700 ;
							$el->bubbleLeft = 370 ;
						}

						$return .= $this->view->renderReturn('default.minorLinks.messageBubble', $el);
						$this->session->set('messenger.lastShowBubble', time());

						$pObj = new person($this->view, $this->session->get("gibbonPersonID"));
						$pObj->setField("messengerLastBubble", date("Y-m-d"));
						$pObj->writeRecord(array("messengerLastBubble"));
					}
				}
			}
		}

		//House logo
		if (@$isHouseLogo) {
			$return.=" . <img class='minorLinkIconLarge' title='" . $this->session->get("gibbonHouseIDName") . "' style='vertical-align: -75%; margin-left: 4px' src='" . $this->session->get("absoluteURL") . "/" . $this->session->get("gibbonHouseIDLogo") . "'/>" ;
		}
		$return .= '</div>';
		return $return ;
	}

	/**
	 * show Likes
	 * 
	 * @version	18th September 2016
	 * @since	moved from functions.php
	 * @return	HTML String
	 */
	public function showLikes()	
	{
		$pObj = new person($this->view);
		$return = '<div id="likeLink">';
		$this->session->set("likesCount", $pObj->countLikesByRecipient($this->session->get("gibbonPersonID"), "count", $this->session->get("gibbonSchoolYearID"))) ;
		//Show likes
		if (! $this->session->isEmpty("likesCount") && $this->session->get("likesCount") > 0) {
			$return .= " . <a title='" . trans::__('Likes') . "' href='" . $this->session->get("absoluteURL") . "/index.php?q=likes.php'>" . $this->session->get("likesCount") . " x " . $this->view->renderReturn('default.minorLinks.like_on'). "</a>" ;
		} else {
			$return .= " . " . $this->session->get("likesCount") . " x " . $this->view->renderReturn('default.minorLinks.like_off'). "" ;
		}
		$return .= '</div>';
		return $return ;
	}

	/**
	 * show Notifications
	 * 
	 * @version	20th September 2016
	 * @since	moved from functions.php
	 * @return	HTML String
	 */
	public function showNotifications()	
	{
		return $this->view->renderReturn('default.minorLinks.notification');
	}
}