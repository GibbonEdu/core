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

use Gibbon\core\view ;
use Gibbon\Record\person ;
use Gibbon\Record\theme ;

if (! $this instanceof view) die();

define('NO_SIDEBAR_MENU', true);

$trail = $this->initiateTrail();
$trail->trailEnd = 'Preferences';
$trail->render($this);
	
//Deal with force reset notification
if (isset($_GET["forceReset"])) {
	$forceReset=$_GET["forceReset"] ;
}
else {
	$forceReset=NULL ;
}
if ($forceReset=="Y") {
	$this->displayMessage("Your account has been flagged for a password reset. You cannot continue into the system until you change your password.");
}

$this->render('default.flash');

$pObj = new person($this, $this->session->get("gibbonPersonID"));

$form = $this->getForm(null, array('q' => '/modules/User Admin/preferencesPasswordProcess.php', 'gibbonPersonID' => $pObj->getField('gibbonPersonID')), true);

$el = $form->addElement('h3', '', "Reset Password");

if ($w = $this->getSecurity()->getPasswordPolicy())
{
	$form->addElement('info', '', $w);
}

$el = $form->addElement('password', "password", '');
$el->placeholder = 'Current Password';
$el->setRequired();
$el->setLength(null, intval($this->config->getSettingByScope('System', 'passwordPolicyMinLength')), 20);
$el->nameDisplay = "Current Password";

$el = $form->addElement('password', "passwordNew", '');
$el->setLength(null, null, 20);
$el->placeholder = 'New Password';
$el->setRequired();
if (intval($this->config->getSettingByScope( "System", "passwordPolicyMinLength" )) > 0 )
	$el->setLength(null, intval($this->config->getSettingByScope('System', 'passwordPolicyMinLength')));
$format = "^(.*";
if ( $this->config->getSettingByScope( "System", "passwordPolicyAlpha" ) == 'Y')
	$format .= "(?=.*[a-z])(?=.*[A-Z])";
if ($this->config->getSettingByScope( "System", "passwordPolicyNumeric" ) == 'Y')
	$format .= "(?=.*[0-9])";
if ($this->config->getSettingByScope( "System", "passwordPolicyNonAlphaNumeric" ) == 'Y')
	$format .= "(?=.*?[#?!@$%^&*-])";
$format .= ".*)$";
$el->setFormat($format, 'Does not meet password policy.');
$el->nameDisplay = "New Password";

$el2 = new \Gibbon\Form\button('generate', $this->__("Generate Password"), $this);
$el2->element->class = "generatePassword small";
$el->description = $this->renderReturn('form.button', $el2);

$this->addScript('
<script type="text/javascript">
	$(".generatePassword").click(function(){
		var chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789![]{}()%&*$#^<>~@|";
		var text = "";
		for(var i = 0; i < ' . intval($this->config->getSettingByScope('System', 'passwordPolicyMinLength') + 4) . '; i++) {
			if (i==0) { text += chars.charAt(Math.floor(Math.random() * 26)); }
			else if (i==1) { text += chars.charAt(Math.floor(Math.random() * 26)+26); }
			else if (i==2) { text += chars.charAt(Math.floor(Math.random() * 10)+52); }
			else if (i==3) { text += chars.charAt(Math.floor(Math.random() * 19)+62); }
			else { text += chars.charAt(Math.floor(Math.random() * chars.length)); }
		}
		$(\'input[name="passwordNew"]\').val(text);
		$(\'input[name="passwordNew"]\').get(0).type = "text";
		$(\'input[name="passwordNew"]\').blur(function() {
			$(\'input[name="passwordNew"]\').get(0).type = "password";
		});
		$(\'input[name="passwordConfirm"]\').val(text);
		alert("'.$this->__("Copy this password if required from the field below:").'" + "\n\n" + text) ;
	});
</script>
');

$el = $form->addElement('password', "passwordConfirm", '');
$el->setLength(null, null, 20);
$el->placeholder = 'Confirm New Password';
$el->setRequired();
$el->setConfirmation('Match the New Password', 'passwordNew');
$el->nameDisplay = "Confirm New Password";

$el = $form->addElement('submitBtn', 'submitBtn', 'Submit');

$form->render();

$form = $this->getForm(null, array('q' => '/modules/User Admin/preferencesProcess.php', 'gibbonPersonID' => $pObj->getField('gibbonPersonID')), true);

$el = $form->addElement('h3', '', "Settings");

$el = $form->addElement('text', "calendarFeedPersonal", $pObj->getField("calendarFeedPersonal"));
$el->setLength(null, null, 100);
$el->placeholder = "Personal Google Calendar ID";
$el->nameDisplay = "Personal Google Calendar ID";
$el->description = "Google Calendar ID for your personal calendar. <br/>Only enables timetable integration when logging in via Google.";

if ($this->config->getSettingByScope("User Admin", "personalBackground") == "Y")
{
	$el = $form->addElement('text', "personalBackground", $pObj->getField("personalBackground"));
	$el->setLength(null, null, 255);
	$el->placeholder = "Personal Background";
	$el->nameDisplay = "Personal Background";
	$el->description = "Set your own custom background image.<br/>Please provide URL to image.";
}

$el = $form->addElement('select', "gibbonThemeIDPersonal", $this->session->get("gibbonThemeIDPersonal"));
$el->nameDisplay = "Personal Theme";
$el->description = "Override the system theme.";
$tObj = new theme($this);
$el->addOption($this->__('Select your theme!'), '');
foreach($tObj->findAll("SELECT * FROM `gibbonTheme` ORDER BY `name`") as $theme)
	$el->addOption($theme->getField("active") == 'Y' ? $theme->getField("name")." (".$this->__("System Default").")" : $theme->getField("name"), $theme->getField("gibbonThemeID"));

$el = $form->addElement('select', "personalLanguageCode", $pObj->getField('personalLanguageCode'));
$el->nameDisplay = "Personal Language" ;
$el->description = "Override the system default language.";
$el->validateOff();
$el->addOption('');
if ($pObj->getField('personalLanguageCode') != '')
	$el->value = $pObj->getField('personalLanguageCode');
foreach ($this->config->getLanguages() as $code=>$name )
{
	$el->addOption($this->__($name), $code);
}

$el = $form->addElement('yesno', "receiveNotificationEmails", $this->session->get("receiveNotificationEmails"));
$el->nameDisplay = "Receive Email Notifications?";
$el->description = "Notifications can always be viewed on screen.";

$el = $form->addElement('submitBtn', 'submitBtn', 'Submit');

$form->render();
