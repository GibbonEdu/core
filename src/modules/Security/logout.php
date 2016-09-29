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
// @version 20th April 2016

namespace Module\Security ;

use Gibbon\core\view ;
use Gibbon\Record\theme ;

if (! $this instanceof view) die();

$URL = GIBBON_URL . 'index.php' ;
if ($_GET['q'] === '/modules/Security/logout.php') $this->fileAnObject(array(__FILE__,__LINE__,$_GET), 'logout'.basename(__FILE__).__LINE__);

$this->session->clear('googleAPIAccessToken');
$this->session->clear('gplusuer');

$this->session->destroy();

$this->session->start();

if (isset($_GET["timeout"]) && $_GET["timeout"]=="true")
	$this->insertMessage('Your session expired, so you were automatically logged out of the system.', 'warning');

$tObj = new theme($this);
$tObj->setDefaultTheme();

$this->redirect($URL);
