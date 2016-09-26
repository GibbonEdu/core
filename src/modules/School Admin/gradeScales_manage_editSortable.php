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

namespace Module\School_Admin ;namespace Module\School_Admin ;

use Gibbon\core\post ;
use Gibbon\Record\scale ;
use Gibbon\Record\scaleGrade ;

if (! $this instanceof post) die();

$content = '';

if ($this->getSecurity()->isActionAccessible('/modules/School Admin/gradeScales_manage_edit.php')) {
	$postOrder = explode(',', $_POST['order']) ;
	if (is_array($postOrder))
	{
		$order = 0 ;
		$sgObj = new scaleGrade($this);
		foreach($postOrder as $fieldID)
		{
			$sgObj->find($fieldID);
			if ($sgObj->getSuccess())
			{
				$sgObj->setField('sequenceNumber', ++$order);
				$sgObj->writeRecord();
			}
		}
	}
	$sObj = new scale($this, $_POST['gibbonScaleID']);
	$sgList = $sObj->getGrades();
	$this->session->set('module', 'School Admin');
	foreach($sgList as $field)
		$content .= $this->renderReturn('scale.grade.listMember', $field);
}
die($content);
