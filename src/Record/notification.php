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
/**
 */
namespace Gibbon\Record ;

use Gibbon\core\mailer ;

/**
 * Notification Record
 *
 * @version	4th August 2016
 * @since	5th May 2016
 * @author	Craig Rayner
 * @package		Gibbon
 * @subpackage	Record
 */
class notification extends record
{
	/** 
	 * @var	string	$table	Table Name
	 */
	protected $table = 'gibbonNotification';
	
	/**
	 * @var	string	$identifier	Table Identifier Name
	 */
	protected $identifier = 'gibbonNotificationID';
	
	/**
	 * Unique Test
	 *
	 * @version	5th May 2016
	 * @since	5th May 2016
	 * @return	boolean
	 */
	public function uniqueTest()
	{
		if (empty($this->record))
			return $this->uniqueFailed('The Record has not been set.', 'Debug', $this->table) ;
		$required = array('gibbonPersonID', 'text', 'actionLink', 'status', 'count');
		foreach ($required as $name) {
			if (empty($this->record->$name))
			{
				return $this->uniqueFailed('A necessary field was empty.', 'Debug', $this->table, array($name)) ;
			}
		}
		return true ;
	}

	/**
	 * can Delete
	 *
	 * @version	27th May 2016
	 * @since	27th May 2016
	 * @return	boolean		
	 */
	public function canDelete()
	{
		return true;
	}

	/**
	 * default Record
	 *
	 * @version	4th August 2016
	 * @since	4th August 2016
	 * @return	boolean		
	 */
	public function defaultRecord()
	{
		parent::defaultRecord();
		$this->setField('timestamp', date('Y-m-d H:i:s'));
	}

	/**
	 * set
	 *
	 * @version	4th August 2016
	 * @since	copied from functions.php
	 * @param	integer		$personID	Persion ID
	 * @param	string		$text		Notice
	 * @param	string		$moduleName	Module Name
	 * @param	string		$actionLink	Action Link
	 * @return	boolean		
	 */
	public function set($personID, $text, $moduleName = '', $actionLink)
	{
		$moduleName = empty($moduleName) ? null : $moduleName ;
		if (! is_null($moduleName))
			$moduleID = $this->view->getModuleIDFromName($moduleName, $this->view);	
		$moduleID = empty($moduleID) ? null : $moduleID ;
		
		//Check for existence of notification in new status
		$this->findOneBy(array("gibbonPersonID"=>$personID, "text"=>$text, "actionLink"=>$actionLink, "gibbonModuleID"=>$moduleID));
		
		if ($this->getSuccess() && $this->rowCount() == 0)
			$this->defaultRecord();
		elseif ($this->getSuccess() && $this->rowCount() == 1)
			$this->setField('count', $this->getField('count') + 1);
		$this->setField('gibbonPersonID', $personID);
		$this->setField('text', $text);
		$this->setField('gibbonModuleID', $moduleID);
		$this->setField('actionLink', $actionLink);
		$archived = $this->getField('status') != 'New' ? true : false ;
		$this->setField('status', 'New');
		$this->setField('timestamp', date('Y-m-d H:i:s'));
		
		if (! ($this->uniqueTest() && $this->writeRecord()))
			return ;
		if ($this->insert || $archived)
		{
			$pObj = new person($this->view, $personID);
			if ($pObj->rowCount() == 1 && ! $pObj->isEmpty('email') && $pObj->getField('receiveNotificationEmails') == 'Y')
			{
		
				//Attempt email send
				$subject = $this->view->__(array('You have received a notification on %1$s at %2$s (%3$s %4$s)', array($this->view->session->get("systemName"), $this->view->session->get("organisationNameShort"), date("H:i"), $this->view->dateConvertBack(date("Y-m-d"))))) ;
				$body = $this->view->__( 'Notification') . ": " . $this->view->__($text) . "<br/><br/>" ;
				$body .= $this->view->__(array('Login to %1$s and use the notification icon to check your new notification, or %2$sclick here%3$s.', array($this->view->session->get("systemName"), "<a href='" . GIBBON_URL . "index.php?q=/modules/Notifications/notifications.php'>", "</a>"))) ;
				$body .= "<br/><br/>" ;
				$body .= "<hr/>" ;
				$body .= "<p style='font-style: italic; font-size: 85%'>" ;
				$body .= $this->view->__(array('If you do not wish to receive email notifications from %1$s, please %2$sclick here%3$s to adjust your preferences:', array($this->view->session->get("systemName"), "<a href='" . GIBBON_URL . "index.php?q=/modules/User Admin/preferences.php'>", "</a>"))) ;
				$body .= "<br/><br/>" ;
				$body .= $this->view->__(array('Email sent via %1$s at %2$s.' , array($this->view->session->get("systemName"), $this->view->session->get("organisationName")))) ;
				$body .= "</p>" ;
				$bodyPlain = preg_replace('#<br\s*/?>#i', "\n", $body) ;
				$bodyPlain = str_replace("</p>", "\n\n", $bodyPlain) ;
				$bodyPlain = str_replace("</div>", "\n\n", $bodyPlain) ;
				$bodyPlain = preg_replace("#\<a.+href\=[\"|\'](.+)[\"|\'].*\>.*\<\/a\>#U","$1",$bodyPlain);
				$bodyPlain = strip_tags($bodyPlain, '<a>');
		
				$mail = new mailer($this->view);
				$mail->SetFrom($this->view->session->get("organisationAdministratorEmail"), $this->view->session->get("organisationName"));
				$mail->AddAddress($pObj->getField('email'), $pObj->formatName(false, true));
				$mail->IsHTML(true);
				$mail->Subject = $subject ;
				$mail->Body = $body ;
				$mail->AltBody = $bodyPlain ;
				$mail->Send() ;
			}
		}
	}
	
	/**
	 * get Notifications for Current User
	 *
	 * @version	20th September 2016
	 * @since	20th September 2016
	 * @params	string		$status
	 * @return	array		Gibbon\Record\notification
	 */
	public function getCurrentUserNotifications($status = 'New')
	{
		$data = array('personID' => $this->session->get('gibbonPersonID'), 'personID2' => $this->session->get('gibbonPersonID'), 'status' => $status, 'status1' => $status);
		$sql = "(SELECT `gibbonNotification`.*, `gibbonModule`.`name` AS `source` 
			FROM `gibbonNotification` 
				JOIN `gibbonModule` ON `gibbonNotification`.`gibbonModuleID` = `gibbonModule`.`gibbonModuleID` 
			WHERE `gibbonPersonID` = :personID 
				AND `status` = :status)
			UNION
				(SELECT `gibbonNotification`.*, 'System' AS `source` 
					FROM `gibbonNotification` 
					WHERE `gibbonModuleID` IS NULL 
						AND `gibbonPersonID` = :personID2 
						AND `status` = :status1)
			ORDER BY `timestamp` DESC, `source`, `text`";
		$notice = $this->findAll($sql, $data, '_');
		if ($this->getSuccess()) return $notice ;
		return array();
	}
}