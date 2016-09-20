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

namespace Module\System_Admin ;

use Gibbon\core\view ;
use Gibbon\core\trans ;
use Symfony\Component\Yaml\Yaml ;

if (! $this instanceof view) die();

if ($this->getSecurity()->isActionAccessible()) {
	//Proceed!
	$trail = $this->initiateTrail();
	$trail->trailEnd = 'Third Party Settings';
	$trail->render($this);

	$this->render('default.flash');
	
	$form = $this->getForm($this->session->get('absolutePath').'/modules/System Admin/thirdPartySettingsProcess.php', array(), true);

	$el = $form->addElement('h3', '', 'Google Integration');
	$el->note = "Google Integration Note";
	$el->noteDetails = array("<a href='https://gibbonedu.org/support/administrators/installing-gibbon/authenticating-with-google-oauth/' target='_blank'>", '</a>');

	$el = $form->addElement('yesno', '');;
	$el->injectRecord($this->config->getSetting('googleOAuth', 'System'));

	$el = $form->addElement('text', '');
	$el->injectRecord($this->config->getSetting('googleClientName', 'System'));
	$el->setMaxLength(200);

	$el = $form->addElement('textArea', '');
	$el->injectRecord($this->config->getSetting('googleClientID', 'System'));
	$el->rows = 3;

	$el = $form->addElement('textArea', '');
	$el->injectRecord($this->config->getSetting('googleClientSecret', 'System'));
	$el->rows = 3;

	$el = $form->addElement('textArea', '');
	$el->injectRecord($this->config->getSetting('googleRedirectUri', 'System'));
	$el->rows = 3;

	$el = $form->addElement('textArea', '');
	$el->injectRecord($this->config->getSetting('googleDeveloperKey', 'System'));
	$el->rows = 3;

	$el = $form->addElement('text', '');
	$el->injectRecord($this->config->getSetting('calendarFeed', 'System'));
	$el->setMaxLength(255);


	$el = $form->addElement('h3', '', 'PayPal Payment Gateway');

	$el = $form->addElement('yesno', '');
	$el->injectRecord($this->config->getSetting('enablePayments', 'System'));

	$el = $form->addElement('text', '');
	$el->injectRecord($this->config->getSetting('paypalAPIUsername', 'System'));
	$el->setMaxLength(255);

	$el = $form->addElement('text', '');
	$el->injectRecord($this->config->getSetting('paypalAPIPassword', 'System'));
	$el->setMaxLength(255);

	$el = $form->addElement('text', '');
	$el->injectRecord($this->config->getSetting('paypalAPISignature', 'System'));
	$el->setMaxLength(255);

	$el = $form->addElement('h3', '', 'SMS Settings');
	$el->note = "SMS Settings Note";
	$el->noteDetails = array("<a href='http://onewaysms.com' target='_blank'>", '</a>');

	$el = $form->addElement('text', '');
	$el->injectRecord($this->config->getSetting('smsUsername', 'Messenger'));
	$el->setMaxLength(50);

	$el = $form->addElement('text', '');
	$el->injectRecord($this->config->getSetting('smsPassword', 'Messenger'));
	$el->setMaxLength(50);

	$el = $form->addElement('text', '');
	$el->injectRecord($this->config->getSetting('smsURL', 'Messenger'));
	$el->setMaxLength(255);

	$el = $form->addElement('text', '');
	$el->injectRecord($this->config->getSetting('smsURLCredit', 'Messenger'));
	$el->setMaxLength(255);

	//		<!-- PHPMail SMTP -->
	$mailSetting = array();
	if (file_exists(GIBBON_ROOT . 'config/local/mailer.yml'))
		$mailSetting = Yaml::parse(file_get_contents( GIBBON_ROOT . 'config/local/mailer.yml'));

	$el = $form->addElement('h3', '', 'SMTP Mail');
	$el->note = "SMTP Mail Note";
	$el->noteDetails = array("<a href='https://gibbonedu.org/support/administrators/installing-gibbon/smtp-mail-settings/' target='_blank'>", '</a>');

	$el = $form->addElement('yesno', '');;
	$el->injectRecord($this->config->getSetting('enableMailerSMTP', 'System'));
	if (isset($mailSetting['IsSMTP']) && $mailSetting['IsSMTP']) $el->value = 'Y';

	$el = $form->addElement('text', '');
	$el->injectRecord($this->config->getSetting('mailerSMTPHost', 'System'));
	$el->setMaxLength(255);
	if (isset($mailSetting['Host']) && $mailSetting['Host']) $el->value = $mailSetting['Host'];

	$el = $form->addElement('number', '');
	$el->injectRecord($this->config->getSetting('mailerSMTPPort', 'System'));
	$el->setNumericality(null, 0, 65535, true);
	if (isset($mailSetting['Port']) && $mailSetting['Port']) $el->value = $mailSetting['Port'];

	$el = $form->addElement('select', '');
	$el->injectRecord($this->config->getSetting('mailerSMTPSecure', 'System'));
	$el->addOption(trans::__('None'), 'none');
	$el->addOption(trans::__('SSL'), 'ssl');
	$el->addOption(trans::__('TLS'), 'tls');
	if (isset($mailSetting['SMTPSecure']) && $mailSetting['SMTPSecure']) $el->value = $mailSetting['SMTPSecure'];

	$el = $form->addElement('text', '');
	$el->injectRecord($this->config->getSetting('mailerSMTPUsername', 'System'));
	$el->setMaxLength(50);
	if (isset($mailSetting['Username']) && $mailSetting['Username']) $el->value = $mailSetting['Username'];

	$el = $form->addElement('text', '');
	$el->injectRecord($this->config->getSetting('mailerSMTPPassword', 'System'));
	$el->setMaxLength(50);
	if (isset($mailSetting['Password']) && $mailSetting['Password']) $el->value = $mailSetting['Password'];

	$el = $form->addElement('email', '');
	$el->injectRecord($this->config->getSetting('mailerFrom', 'System'));
	if (isset($mailSetting['From']) && $mailSetting['From']) $el->value = $mailSetting['From'];

	$el = $form->addElement('text', '');
	$el->injectRecord($this->config->getSetting('mailerFromName', 'System'));
	$el->setMaxLength(75);
	if (isset($mailSetting['FromName']) && $mailSetting['FromName']) $el->value = $mailSetting['FromName'];

	$form->addElement('submitBtn', null);
	$form->render();
}
