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

use Gibbon\core\view ;
use Gibbon\core\helper ;
use Gibbon\core\trans ;
use Gibbon\Record\module ;

if (! $this instanceof view) die();

if ($this->getSecurity()->isActionAccessible()) {
	//Proceed!
	$trail = $this->initiateTrail();
	$trail->addTrail('Manage Modules', "/index.php?q=/modules/System Admin/module_manage.php");
	$trail->trailEnd = 'Edit Module';
	$trail->render($this);
	
	$this->render('default.flash');
	
	//Check if school year specified
	$moduleID = $_GET["gibbonModuleID"] ;
	if (empty($moduleID)) {
		$this->displayMessage("You have not specified one or more required parameters.");
	}
	else {
		$moduleObj = new \Gibbon\Record\module($this, $moduleID);
		if ($moduleObj) {
			//Let's go!
			$row = (array)$moduleObj->returnRecord() ;
			
			$form = $this->getForm(null, array('q' => "/modules/System Admin/module_manage_editProcess.php", "gibbonModuleID" => $moduleID), true);
			
			$form->addElement('h3', null, 'Edit Module');
			
			$el = $form->addElement('text', 'name', helper::htmlPrep(trans::__($row["name"])));
			$el->validateOff();
			$el->nameDisplay = 'Name';
			$el->description = 'This value cannot be changed.' ;
			$el->setReadOnly();


			$el = $form->addElement('text', 'description', helper::htmlPrep(trans::__( $row["description"])));
			$el->nameDisplay = 'Description';
			$el->description = 'This value cannot be changed.' ;
			$el->setReadOnly();


			$el = $form->addElement('select', 'category', $row['category']);
			$el->setRequired();
			$menu = $this->pdo->getEnum('gibbonModule', 'category');
			foreach($menu as $category)
				$el->addOption(trans::__($category), $category);
			$el->nameDisplay = 'Category';
			$el->description = 'Determines menu structure' ;


			$el = $form->addElement('yesno', 'active', $row['active']);
			$el->nameDisplay = 'Active';
			
			$form->addElement('submitBtn', null);
			
			$form->renderForm();
		}
	}
}