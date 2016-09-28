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
use Gibbon\core\deleteRecord ;

if (! $this instanceof post) die();

new deleteRecord
	(
		'rollGroup', 
		$_GET["gibbonRollGroupID"], 
		"/modules/School Admin/rollGroup_manage_delete.php", 
		array('q'=>'=/modules/School Admin/rollGroup_manage_delete.php', 'gibbonSchoolYearID'=>$_GET["gibbonSchoolYearID"], 'gibbonRollGroupID'=>$_GET["gibbonRollGroupID"]), 
		array('q'=>'/modules/School Admin/rollGroup_manage.php', 'gibbonSchoolYearID'=>$_GET["gibbonSchoolYearID"]), 
		$this
	);
