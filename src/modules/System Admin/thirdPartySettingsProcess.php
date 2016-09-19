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

use Gibbon\core\post ;
use Gibbon\core\trans ;
use Symfony\Component\Yaml\Yaml ;

if (! $this instanceof post) die();

$URL = array('q'=>'/modules/System Admin/thirdPartySettings.php');

if (! $this->getSecurity()->isActionAccessible('/modules/System Admin/thirdPartySettings.php')) {
    $this->insertMessage('return.error.0');
    $this->redirect($URL);
} else {
    //Proceed!

    //Validate Inputs
    if (empty($_POST['enablePayments']) || empty($_POST['googleOAuth'])) {
        $this->insertMessage('return.error.3');
        $this->redirect($URL);
    } else {
        //Write to database
        $fail = false;
		$smtp = array();
		if (file_exists(GIBBON_ROOT . 'config/local/mailer.yml'))
			$smtp = Yaml::parse(file_get_contents(GIBBON_ROOT . 'config/local/mailer.yml'));
		$smtp['Encoding'] = empty($smtp['Encoding']) ? 'base64' : $smtp['Encoding'] ;
		$smtp['CharSet'] = empty($smtp['CharSet']) ? 'UTF-8' : $smtp['CharSet'] ;
		$smtp['WordWrap'] = empty($smtp['WordWrap']) ? 65 : $smtp['WordWrap'] ;


		foreach($_POST as $name=>$value) 
		{
			switch ($name) 
			{
				case 'smsUsername':
				case 'smsPassword':
				case 'smsURL':
				case 'smsURLCredit':
					if (! $this->config->setSettingByScope($name, $value, 'Messenger') ) $fail = true;
					break;
				case 'enableMailerSMTP':
					$smtp['SMTPAuth'] = $smtp['IsSMTP'] = $_POST[$name] == 'Y' ? 1 : 0 ;
					if (! $this->config->setSettingByScope($name, $value, 'System') ) $fail = true;
					break ;
				case 'mailerSMTPHost':
					$smtp['Host'] = $_POST[$name] ;
					if (! $this->config->setSettingByScope($name, $value, 'System') ) $fail = true;
					break ;
				case 'mailerSMTPPort': 
					$smtp['Port'] = intval($value);
					if (! $this->config->setSettingByScope($name, $value, 'System') ) $fail = true;
					break ;
				case 'mailerSMTPUsername':
					$smtp['Username'] = $_POST[$name] ;
					if (! $this->config->setSettingByScope($name, $value, 'System') ) $fail = true;
					break ;
				case 'mailerSMTPPassword':
					$smtp['Password'] = $_POST[$name] ;
					if (! $this->config->setSettingByScope($name, $value, 'System') ) $fail = true;
					break ;
				case 'mailerSMTPSecure':
					$smtp['SMTPSecure'] = $_POST[$name] ;
					if (! $this->config->setSettingByScope($name, $value, 'System') ) $fail = true;
					break ;
				case 'mailerFrom':
					$smtp['From'] = $_POST[$name] ;
					if (! $this->config->setSettingByScope($name, $value, 'System') ) $fail = true;
					break ;
				case 'mailerFromName':
					$smtp['FromName'] = $_POST[$name] ;
					if (! $this->config->setSettingByScope($name, $value, 'System') ) $fail = true;
					break ;
				default:
					if (! $this->config->setSettingByScope($name, $value, 'System') ) $fail = true;
			}
		}
		
        if ($fail) {
            $this->insertMessage('return.error.2');
            $this->redirect($URL);
        } else {
            $this->session->getSystemSettings($this->pdo);
			file_put_contents(GIBBON_ROOT . 'config/local/mailer.yml', Yaml::dump($smtp));
            $this->insertMessage('return.success.0', 'success');
            $this->redirect($URL);
        }
    }
}
