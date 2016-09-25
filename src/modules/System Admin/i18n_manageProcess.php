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

use Gibbon\core\post;

if (! $this instanceof post) die();

$code = $_POST['gibboni18nCode'];
$URL = array('q' => '/modules/System Admin/i18n_manage.php');

if ($this->getSecurity()->isActionAccessible('/modules/System Admin/i18n_manage.php')) {
    //Proceed!
	if (! empty($_POST['update']))
	{
		foreach($_POST['update'] as $uCode)
		{
			$source = GIBBON_ROOT . 'src/i18n/'.$uCode.'/gibbon.yml';
			$destination = GIBBON_ROOT . 'src/i18n/'.$uCode.'/gibbon.yml';
			file_put_contents($destination, file_get_contents($source));
		}
	}
    //Check if language specified
    if (empty($code)) {
        $this->insertMessage('return.error.1');
        $this->redirect($URL);
    } else {

		//Write to database
		if (! $this->config->setSettingByScope('defaultLanguage', $code, 'System')) {
			$this->insertMessage('return.error.2');
			$this->redirect($URL);
		} else {
			//Update session variables
			$this->session->setLanguageSession($code);
			$this->insertMessage('return.success.0', 'success');
			$this->redirect($URL);
		}
    }
}
$this->insertMessage('return.error.0');
$this->redirect($URL);
