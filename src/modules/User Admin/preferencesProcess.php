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

namespace Module\User_Admin ;

use Gibbon\core\post ;
use Gibbon\Record\schoolYear ;
use Gibbon\Record\person ;
use Gibbon\Record\theme ;

if (! $this instanceof post) die();

$syObj = new schoolYear($this);
//Check to see if academic year id variables are set, if not set them 
if ($this->session->isEmpty('gibbonAcademicYearID') || $this->session->isEmpty('gibbonSchoolYearName') )
    $syObj->setCurrentSchoolYear();

$calendarFeedPersonal = $_POST['calendarFeedPersonal'];
$personalBackground = isset($_POST['personalBackground']) ? $_POST['personalBackground'] : '';

$ThemeIDPersonal = ! empty($_POST['gibbonThemeIDPersonal']) ?$_POST['gibbonThemeIDPersonal'] : null ;

$LanguageCode = empty($_POST['personalLanguageCode']) ? '' : $_POST['personalLanguageCode'];

$receiveNotificationEmails = empty($receiveNotificationEmails) ? 'N' : $_POST['receiveNotificationEmails'];

$personID = $_GET['gibbonPersonID'];

$URL = array('q'=>'/modules/User Admin/preferences.php');

$pObj = new person($this, $personID);

$pObj->setField('calendarFeedPersonal', $calendarFeedPersonal);
$pObj->setField('personalBackground', $personalBackground);
$pObj->setField('gibbonThemeIDPersonal', $ThemeIDPersonal);
$pObj->setField('personalLanguageCode', $LanguageCode);
$pObj->setField('receiveNotificationEmails', $receiveNotificationEmails);

if (! $pObj->writeRecord(array('calendarFeedPersonal', 'personalBackground', 'gibbonThemeIDPersonal', 'personalLanguageCode', 'receiveNotificationEmails'))) {
    $this->insertMessage('return.error.2');
    $this->redirect($URL);
}

//Update personal preferences in session
$this->session->set('calendarFeedPersonal', $pObj->getField('calendarFeedPersonal'));
$this->session->set('personalBackground', $pObj->getField('personalBackground'));
$this->session->set('gibbonThemeIDPersonal', $pObj->getField('gibbonThemeIDPersonal'));
$this->session->set('theme.IDPersonal', $pObj->getField('gibbonThemeIDPersonal'));
if ($this->session->notEmpty('theme.IDPersonal')) {
	$tObj = new theme($this, $this->session->get('theme.IDPersonal'));
	$tObj->setDefaultTheme();
}
$this->session->set('personalLanguageCode', $pObj->getField('personalLanguageCode'));
$this->session->set('receiveNotificationEmails', $pObj->getField('receiveNotificationEmails'));

//Update language settings in session (to personal preference if set, or system default if not)
if ($this->session->notEmpty('personalLanguageCode')) {
	$this->session->setLanguageSession($this->session->get('personalLanguageCode'));
} else {
   	$this->session->setLanguageSession($this->session->get('defaultLanguage'));
}

$this->session->set('pageLoads', -1);
$this->insertMessage('success0', 'success');
$this->redirect($URL);
