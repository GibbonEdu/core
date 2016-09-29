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
use Gibbon\Record\alertLevel ;

if (! $this instanceof view) die();

if ($this->getSecurity()->isActionAccessible()) {
	//Proceed!
	$trail = $this->initiateTrail();
	$trail->trailEnd = 'Manage Alert Levels';
	$trail->render($this);
	
	$this->render('default.flash');

	$obj = new alertLevel($this);
	$alerts = $obj->findAll('SELECT * 
		FROM `gibbonAlertLevel` 
		ORDER BY `sequenceNumber`', array(), '_');

	$form = $this->getForm(null, array('q'=>'modules/School Admin/alertLevelSettingsProcess.php'), true);

	$this->h2('Manage Alert Levels');
		
	foreach($alerts as $alert) {
		$form->addElement('h3', null, $alert->getField('name'));

		$el = $form->addElement('text', 'setting['.$alert->getField('name').'][name]', $alert->getField('name'));
		$el->nameDisplay = $this->__('Name');
		$el->setRequired();
		$el->setMaxLength(50);
	
		$el = $form->addElement('text', 'setting['.$alert->getField('name').'][nameShort]', $alert->getField('nameShort'));
		$el->nameDisplay = $this->__('Short Name');
		$el->setRequired();
		$el->setMaxLength(4);

		$el = $form->addElement('colour', 'setting['.$alert->getField('name').'][color]', $alert->getField('color'));
		$el->nameDisplay = 'Font/Border Colour';
	
		$el = $form->addElement('colour', 'setting['.$alert->getField('name').'][colorBG]', $alert->getField('colorBG'));
		$el->nameDisplay = 'Background Colour';
	
		$el = $form->addElement('number', 'setting['.$alert->getField('name').'][sequenceNumber]', $alert->getField('sequenceNumber'));
		$el->setNumericality(null, 1, 999, true);
		$el->nameDisplay = 'Sequence Number' ;
		$el->readOnly = true ;
		$el->description = 'This value cannot be changed.';
	
		$el = $form->addElement('textArea', 'setting['.$alert->getField('name').'][description]', $alert->getField('description'));
		$el->nameDisplay = $this->__('Description') ;
		$el->rows = 5;
		$el->description = 'This value cannot be changed.';

		$form->addElement('hidden', 'setting['.$alert->getField('name').'][gibbonAlertLevelID]', $alert->getField('gibbonAlertLevelID')); 
		
	}
	$form->addElement('submitBtn', null);
	$form->render();
}