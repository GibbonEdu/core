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

use Gibbon\core\editRecord ;
use Gibbon\core\post ;

if (! $this instanceof post) die();

new editRecord
	(
		'stringReplacement', 
		$_GET["gibbonStringID"], 
		"/modules/System Admin/stringReplacement_manage_edit.php", 
		array('q' => '/modules/System Admin/stringReplacement_manage_edit.php', 'gibbonStringID' => $_GET["gibbonStringID"], 'search' => $_GET['search']),
		$this
	);
