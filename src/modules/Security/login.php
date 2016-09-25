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

namespace Module\Security ;

use Gibbon\core\logger ;
use Gibbon\core\post ;
use Gibbon\Record\schoolYear ;
use Gibbon\core\trans ;

if (! $this instanceof post ) die();

$security = $this->getSecurity();

$syObj = new schoolYear($this);

$syObj->setCurrentSchoolYear() ;


//The current/actual school year info, just in case we are working in a different year
$this->session->set("gibbonSchoolYearIDCurrent", $this->session->get("gibbonSchoolYearID")) ;
$this->session->set("gibbonSchoolYearNameCurrent", $this->session->get("gibbonSchoolYearName") );
$this->session->set("gibbonSchoolYearSequenceNumberCurrent", $this->session->get("gibbonSchoolYearSequenceNumber")) ;

$this->session->set("pageLoads", -1) ;

$URL = $this->session->get('absoluteURL')."/index.php" ;

if (empty($_POST['username']) || empty($_POST['password'])) {
	$this->insertMessage("Username or password not set.", 'error', false, 'login.flash');
	$this->redirect($URL);
}
//VALIDATE LOGIN INFORMATION
else {	
	$pObj = new \Gibbon\Record\person($this);
	$person = $pObj->findOneBy(array('username' => $_POST['username'], 'status'=>'Full', 'canLogin'=>'Y'));		
	if (! $pObj->getSuccess() || $pObj->rowCount() !== 1) {
		logger::__("Incorrect username or password.", 'Warning', 'Security', array("username" => $_POST['username'])) ;
		$this->insertMessage("Incorrect username or password.", 'error', false, 'login.flash');
		$this->redirect($URL);
	}
	else {
		//Check fail count, reject & alert if 3rd time
		if ($person->failCount >= 3) {
			$pObj->setField("lastFailIPAddress", $_SERVER["REMOTE_ADDR"]);
			$pObj->setField("lastFailTimestamp", date("Y-m-d H:i:s"));
			$pObj->setField("failCount", $person->failCount + 1);
			$pObj->setField("username", $_POST['username']);
			$pObj->writeRecord();
		
			if ($person->failCount == 3) {
				$notificationText=sprintf($this->__('Someone failed to login to account "%1$s" 3 times in a row.'), $_POST['username']) ;
				Gibbon\helper::setNotification($this->session->get("organisationAdministrator"), $notificationText, "System", "/index.php?q=/modules/User Admin/user_manage.php&search=".$_POST['username']) ;
			}
		
			logger::__(array('Too many failed logins: please %1$sreset password%2$s.', array("<a href='" . $this->session->get("absoluteURL") . "/index.php?q=/passwordReset.php'>", "</a>")), 'Warning', 'Security') ;
			$this->insertMessage($this->__('Too many failed logins: please %1$sreset password%2$s.', array("<a href='" . $this->session->get("absoluteURL") . "/index.php?q=/passwordReset.php'>", "</a>")), 'error', false, 'login.flash');
			$this->redirect($URL);
		}
		else {
			//Test to see if password matches username
			if (! $security->verifyPassword($_POST['password'], $person->passwordStrong, $person->passwordStrongSalt, $person->password)) {
				//FAIL PASSWORD
				$pObj->setField("lastFailIPAddress", $_SERVER["REMOTE_ADDR"]);
				$pObj->setField("lastFailTimestamp", date("Y-m-d H:i:s"));
				$pObj->setField("failCount", $person->failCount + 1);
				$pObj->setField("username", $_POST['username']);
				$pObj->writeRecord() ;
			
				logger::__("Incorrect username and password.", 'Warning', 'Security') ;
				$this->insertMessage("Incorrect username and password.", 'error', false, 'login.flash');
				$this->redirect($URL);
			}
			else {			
				if (empty($person->gibbonRoleIDPrimary) || count($security->getRoleList($person->gibbonRoleIDAll)) == 0) {
					//FAILED TO SET ROLES
					logger::__("You do not have sufficient privileges to login.", 'Warning', 'Security', array("username"=>$_POST['username'])) ;
					$this->insertMessage("You do not have sufficient privileges to login.", 'error', false, 'login.flash');
					$this->redirect($URL);
				}
				else {
					//Allow for non-current school years to be specified
					if (isset($_POST["gibbonSchoolYearID"]) && $_POST["gibbonSchoolYearID"] != $this->session->get("gibbonSchoolYearID")) {
						if ($pObj->getRole()->getField('futureYearsLogin') != "Y" && $pObj->getRole()->getField('pastYearsLogin') != "Y") { //NOT ALLOWED DUE TO CONTROLS ON ROLE, KICK OUT!
							logger::__('Your primary role does not support the ability to log into the specified year.', 'Warning', 'Security') ;
							$this->insertMessage('Your primary role does not support the ability to log into the specified year.', 'error', false, 'login.flash');
							$this->redirect($URL);
						}
						else {
							$syObj = new schoolYear($this, $_POST["gibbonSchoolYearID"]);
			
							//Check number of rows returned.
							//If it is not 1, show error
							if (! $syObj->getSuccess()) {
								throw new Gibbon\Exception( $this->__("Configuration Error: there is a problem accessing the current Academic Year from the database.")) ;
							}
							//Else get year details
							else {
								$rowYear = $syObj->returnRecord();
								if ($pObj->getRole()->getField('futureYearsLogin') != "Y" && $this->session->get("gibbonSchoolYearSequenceNumber") < $rowYear->sequenceNumber ) { //POSSIBLY NOT ALLOWED DUE TO CONTROLS ON ROLE, CHECK YEAR
									log:__('Your primary role does not support the ability to log into the specified year.', 'Warning', array("username"=>$_POST['username'])) ;
									$this->insertMessage('Your primary role does not support the ability to log into the specified year.', 'error', false, 'login.flash');
									$this->redirect($URL);
								}
								else if ($pObj->getRole()->getField('pastYearsLogin') != "Y" && $this->session->get("gibbonSchoolYearSequenceNumber")>$rowYear->sequenceNumber) { //POSSIBLY NOT ALLOWED DUE TO CONTROLS ON ROLE, CHECK YEAR
									logger::__('Your primary role does not support the ability to log into the specified year.', 'Warning', 'Security', array("username"=>$_POST['username'])) ;
									$this->insertMessage('Your primary role does not support the ability to log into the specified year.', 'error', false, 'login.flash');
									$this->redirect($URL);
									exit() ;
								}
								else { //ALLOWED
									$this->session->set("gibbonSchoolYearID", $rowYear->gibbonSchoolYearID) ;
									$this->session->set("gibbonSchoolYearName", $rowYear->name );
									$this->session->set("gibbonSchoolYearSequenceNumber", $rowYear->sequenceNumber) ;
								}
							}
						}
					}
					//USER EXISTS, SET SESSION VARIABLES
					$this->session->set("username", $person->username) ;
					$this->session->set("passwordStrong", $person->passwordStrong) ;
					$this->session->set("passwordStrongSalt", $person->passwordStrongSalt) ;
					$this->session->set("passwordForceReset", $person->passwordForceReset) ;
					$this->session->set("gibbonPersonID", $person->gibbonPersonID) ;
					$this->session->set("surname", $person->surname) ;
					$this->session->set("firstName", $person->firstName) ;
					$this->session->set("preferredName", $person->preferredName );
					$this->session->set("officialName", $person->officialName) ;
					$this->session->set("email", $person->email) ;
					$this->session->set("emailAlternate", $person->emailAlternate) ;
					$this->session->set("website", $person->website);
					$this->session->set("gender", $person->gender);
					$this->session->set("status", $person->status);
					$this->session->set("gibbonRoleIDPrimary", $person->gibbonRoleIDPrimary) ;
					$this->session->set("gibbonRoleIDCurrent", $person->gibbonRoleIDPrimary );
					$this->session->set("gibbonRoleIDCurrentCategory", $security->getRoleCategory($person->gibbonRoleIDPrimary) ) ;
					$this->session->set("gibbonRoleIDAll", $security->getRoleList($person->gibbonRoleIDAll) );
					$this->session->set("image_240", $person->image_240) ;
					$this->session->set("lastTimestamp", $person->lastTimestamp) ;
					$this->session->set("calendarFeedPersonal", $person->calendarFeedPersonal) ;
					$this->session->set("viewCalendarSchool", $person->viewCalendarSchool) ;
					$this->session->set("viewCalendarPersonal", $person->viewCalendarPersonal) ;
					$this->session->set("viewCalendarSpaceBooking", $person->viewCalendarSpaceBooking) ;
					$this->session->set("dateStart", $person->dateStart) ;
					$this->session->set("personalBackground", $person->personalBackground );
					$this->session->set("messengerLastBubble", $person->messengerLastBubble) ;
					$this->session->set("gibbonThemeIDPersonal", $person->gibbonThemeIDPersonal) ;
					$this->session->set("personalLanguage", $pObj->getField('gibbonLanguageCode')) ;
					$this->session->set("googleAPIRefreshToken", $person->googleAPIRefreshToken) ;
					$this->session->clear('googleAPIAccessToken') ; //Set only when user logs in with Google
					$this->session->set('receiveNotificationEmails', $person->receiveNotificationEmails) ;
					$this->session->set('gibbonHouseID', $person->gibbonHouseID) ;
					$this->session->set('security.lastPageTime', strtotime('now'));					
					$this->session->set('security.sessionDuration', $this->config->getSettingByScope('System', 'sessionDuration'));					
					
					//Allow for non-system default language to be specified from login form
					if (isset($_POST["gibboni18nCode"]) && $_POST["gibboni18nCode"] != $this->config->getSettingByScope('System', 'defaultLangauge')) {
						$this->session->setLanguageSession($_POST["gibboni18nCode"]) ;
						$this->config->setSettingByScope('defaultLangauge', $_POST["gibboni18nCode"], 'System');
					}
					else {
						//If no language specified, get user preference if it exists
						if ($this->session->notEmpty("personalLanguage")) {
							$this->session->setLanguageSession($this->session->get("personalLanguage")) ;
						}
					}
					
					if ($this->session->notEmpty("gibbonThemeIDPersonal") && $this->session->get("gibbonThemeIDPersonal") != $this->session->get("gibbonThemeID"))
					{
						$tObj = new \Gibbon\Record\theme($this, $this->session->get("gibbonThemeIDPersonal"));
						$tObj->setSessionTheme();
					}
					
					//Make best effort to set IP address and other details, but no need to error check etc.
					$pObj->setField("lastFailIPAddress", $_SERVER["REMOTE_ADDR"]);
					$pObj->setField("lastFailTimestamp", date("Y-m-d H:i:s"));
					$pObj->setField("failCount", 0);
					$pObj->setField("username", $_POST['username']);
					$pObj->writeRecord() ;
					if (isset($_GET["q"])) {
						if ($_GET["q"]=="/publicRegistration.php") {
							$this->session->get('absoluteURL')."/index.php";
						}
						else {
							$this->session->get('absoluteURL')."/index.php?q=" . $_GET["q"] ;
						}
					}
					else {
						$this->session->get('absoluteURL')."/index.php";
					}	
					$security->clearTokens($pObj->getField('gibbonPersonID'));	
					logger::__("Login - Success", 'Info', 'Security', array("username"=>$_POST['username'])) ;
					$this->redirect($URL);		
				}
			}
		}
	}
}
