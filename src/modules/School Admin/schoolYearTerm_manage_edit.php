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
use Gibbon\Record\schoolYearTerm ;
use Gibbon\Record\schoolYear ;

if (! $this instanceof view) die();

if ($this->getSecurity()->isActionAccessible()) {
	//Proceed!
	$trail = $this->initiateTrail();
	$trail->addtrail('Manage Terms', array('q'=>'/modules/School Admin/schoolYearTerm_manage.php'));
	$header = $trail->trailEnd = $_GET["gibbonSchoolYearTermID"] == 'Add' ? 'Add Term' : 'Edit Term';
	$trail->render($this);
	
	$this->render('default.flash');
	
	//Check if school year specified
	$schoolYearTermID = $_GET["gibbonSchoolYearTermID"] ;
	if (empty($_GET["gibbonSchoolYearTermID"])) {
		$this->displayMessage("You have not specified one or more required parameters.") ;
	}
	else {
		$this->linkTop(array('Add' => array('q'=>'/modules/School Admin/schoolYearTerm_manage_edit.php', 'gibbonSchoolYearTermID'=>'Add')));
		$termObj = new schoolYearTerm($this, intval($schoolYearTermID));
		$this->h2($header);
		
		if (! $termObj->getSuccess()) {
			$this->displayMessage("The specified record cannot be found.");
		} else {
			$sqlSelect = "SELECT `gibbonSchoolYearID`, `name` FROM `gibbonSchoolYear` ORDER BY `sequenceNumber`" ;
			$yearObj = new schoolYear($this);
			$years = $yearObj->findAll($sqlSelect);
			
			$form = $this->getForm(null, array('q'=>"/modules/School Admin/schoolYearTerm_manage_editProcess.php", 'gibbonSchoolYearTermID'=>$schoolYearTermID), true);

			$el = $form->addElement('select', 'gibbonSchoolYearID', $termObj->getField('gibbonSchoolYearID'));
			$el->nameDisplay = 'School Year' ;
			$el->setPleaseSelect();
			foreach ($years as $year)
				$el->addOption($year->getField('name'), $year->getField('gibbonSchoolYearID'));

			$el = $form->addElement('text', 'sequenceNumber', $termObj->getField('sequenceNumber'));
			$el->nameDisplay = 'Sequence Number' ;
			$el->setNumericality(null, 1, 100, true);
			$el->description = 'Must be unique for the selected school year. Controls chronological ordering.';
			$el->setRequired() ;

			$el = $form->addElement('text', 'name', $termObj->getField('name'));
			$el->nameDisplay = 'Name' ;
			$el->setRequired() ;
			$el->setMaxLength(20);

			$el = $form->addElement('text', 'nameShort', $termObj->getField('nameShort'));
			$el->nameDisplay = 'Short Name' ;
			$el->setRequired() ;
			$el->setMaxLength(4);

			$el = $form->addElement('date', 'firstDay',  $termObj->getField('firstDay'));
			$el->nameDisplay = 'First Day' ;
			$el->description = $this->session->get("i18n.dateFormat");
			$el->setRequired();

			$el = $form->addElement('date', 'lastDay', $termObj->getField('lastDay'));
			$el->nameDisplay = 'Last Day' ;
			$el->description = $this->session->get("i18n.dateFormat");
			$el->setRequired();

			$form->addElement('submitBtn', null, null);
			
			$form->render();
		}
	}
}
