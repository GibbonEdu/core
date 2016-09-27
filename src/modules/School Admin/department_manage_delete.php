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

use Gibbon\core\view ;
use Gibbon\Record\department ;

if (! $this instanceof view) die();

if ($this->getSecurity()->isActionAccessible()) {
	//Proceed!
	$trail = $this->initiateTrail();
	$trail->trailEnd = 'Delete Department';
	$trail->addTrail('Manage Departments', "/index.php?q=/modules/School Admin/department_manage.php");
	$trail->render($this);
	
	$this->render('default.flash');
	
	//Check if school year specified
	$departmentID=$_GET["gibbonDepartmentID"];
	if ($departmentID=="") {
		echo $this->issueMessage("You have not specified one or more required parameters.") ;
	}
	else {
		$dObj = new department($this, $departmentID);
		$data=array("gibbonDepartmentID"=>$departmentID); 
		$sql="SELECT * 
			FROM gibbonDepartment 
			WHERE gibbonDepartmentID=:gibbonDepartmentID" ;
		$result=$this->pdo->executeQuery($data, $sql, '_');

		if (! $dObj->getSuccess()) {
			echo $this->issueMessage("The selected record does not exist, or you do not have access to it.");
		}
		else {
			//Let's go!
			$el = new \stdClass();
			$el->action = false;
			$this->render('department.listStart', $el);
			$el  = $dObj->returnRecord();
			$el->action = false;
			$this->render('department.listMember', $el);
			$this->render('department.listEnd');
			$this->getForm(GIBBON_ROOT . "modules/School Admin/department_manage_deleteProcess.php", array('gibbonDepartmentID' => $departmentID), true)
				->deleteForm();
		}
	}
}
