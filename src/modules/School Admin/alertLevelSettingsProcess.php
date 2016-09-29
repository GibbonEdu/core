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

namespace Module\School_Admin ;

use Gibbon\core\post ;
use Gibbon\Record\alertLevel ;

if (! $this instanceof post) die();

$URL = array('q' => '/modules/School Admin/alertLevelSettings.php');

if (! $this->getSecurity()->isActionAccessible('/modules/School Admin/alertLevelSettings.php')) {
    $this->insertMessage('return.error.0');
    $this->redirect($URL);
} else {

    $partialFail = false;
    //Proceed!
    if (empty($_POST['setting']) || ! is_array($_POST['setting'])) {
        $this->insertMessage('return.error.2');
        $this->redirect($URL);
    } else {
		$post = $_POST;
		foreach($post['setting'] as $alert) {
			
			$obj = new alertLevel($this, $alert['gibbonAlertLevelID']);
			$obj->injectPost($alert);

            //Validate Inputs
			if (! $obj->uniqueTest()) {
                $partialFail = true;
            } else {
                if (! $obj->writeRecord()) {
                    $partialFail = true;
                }
            }
        }

        //Deal with failed update
        if ($partialFail) {
            $this->insertMessage('return.warning.1', 'warning');
            $this->redirect($URL);
        } else {
            $this->insertMessage('return.success.0', 'success');
            $this->redirect($URL);
        }
    }
}
