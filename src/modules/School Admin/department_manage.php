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
use Gibbon\core\pagination ;

if (! $this instanceof view) die();

if ($this->getSecurity()->isActionAccessible()) {
	$trail = $this->initiateTrail();
	$trail->trailEnd = 'Manage Departments';
	$trail->render($this);
	
	$this->render('default.flash');
	
	$this->h2('Manage Departments');
	
	$form = $this->getForm(GIBBON_ROOT . "modules/School Admin/department_manageProcess.php", array(), true);
	
	$form->addElement('h3', null, 'Department Access');
	
	$el = $form->addElement('yesno', null);
	$el->injectRecord($this->config->getSetting('makeDepartmentsPublic', 'Departments'));
	$el->validateOff();
	
	$form->addElement('submitBtn', null);
	$form->render();

	$this->linkTop(array('add' => array('q' => "/modules/School Admin/department_manage_edit.php", 'gibbonDepartmentID' => 'Add')));
	$this->h3("Departments");
	
    //Set pagination variable
	$data = array();
	$where = '';
	$order = array('name' => 'ASC');
	$join = '';
	$select = '';

	$pagin = new pagination($this, $where, $data, $order, new department($this), $join, $select);
	
	if ($pagin->get('total') < 1) {
		$this->displayMessage("There are no records to display.") ;
	}
	else {
		$pagin->printPagination('top');
		$this->render('department.listStart');
		foreach($pagin->get('results') as $row) 
			$this->render('department.listMember', $row);
		$this->render('department.listEnd');
		$pagin->printPagination('bottom');

	}
}	
?>