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
use Gibbon\Record\externalAssessmentField ;
use Gibbon\Record\externalAssessment ;

if (! $this instanceof post) die();

$content = '';

if ($this->getSecurity()->isActionAccessible('/modules/School Admin/externalAssessments_manage_edit.php')) {
	$postOrder = explode(',', $_POST['order']) ;
	if (is_array($postOrder))
	{
		$order = array();
		$efObj = new externalAssessmentField($this);
		foreach($postOrder as $fieldID)
		{
			$efObj->find($fieldID);
			if ($efObj->getSuccess())
			{
				$category = $efObj->getField('category');
				if (empty($order[$category])) $order[$category] = 0;
				$efObj->setField('order', ++$order[$category]);
				$efObj->writeRecord();
			}
		}
	}
	$eaObj = new externalAssessment($this, $_POST['gibbonExternalAssessmentID']);
	$efList = $eaObj->getFields();
	$this->session->set('module', 'School Admin');
	foreach($efList as $field)
		$content .= $this->renderReturn('externalAssessment.field.listMember', $field);
}
die($content);
