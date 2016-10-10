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
use Gibbon\Record\house ;

if (! $this instanceof view) die();

if ($this->getSecurity()->isActionAccessible()) {
	//Proceed!
	$trail = $this->initiateTrail();
	$header = $trail->trailEnd = $_GET["gibbonHouseID"] == 'Add' ? 'Add House' : 'Edit House' ;
	$trail->addTrail('Manage Houses', "/index.php?q=/modules/School Admin/house_manage.php");
	$trail->render($this);
	$this->render('default.flash');
	$this->h2($header);
	//Check if school year specified
	$houseID = $_GET["gibbonHouseID"] ;
	if ($houseID === 'Add' and isset($_POST["gibbonHouseID"]))
		$houseID = $_GET["gibbonHouseID"] = $_POST["gibbonHouseID"];
		
	$form = $this->getForm(GIBBON_ROOT."modules/School Admin/house_manage_editProcess.php", array("gibbonHouseID" => $_GET["gibbonHouseID"]), true, 'house', true);
	
	$hObj = new house($this, $houseID); 
	$el = $form->addElement('text', 'name', $this->htmlPrep($hObj->getField('name')));
	$el->setRequired();
	$el->nameDisplay = 'Name' ;
	$el->description = 'Must be unique.' ;
	$el->setMaxLength(10);

	$el = $form->addElement('text', 'nameShort', $this->htmlPrep($hObj->getField('nameShort')));
	$el->setRequired();
	$el->nameDisplay = 'Short Name' ;
	$el->description = 'Must be unique.' ;
	$el->setMaxLength(4);


	$el = $form->addElement('photo', 'file1');
	$el->nameDisplay = 'Logo';
	$el->displayPhoto($hObj->getField('logo'),
		'Displayed at 240px by 240px.%1$sAccepts images up to 480px by 480px.%2$sAccepts aspect ratio between 1:0.8 and 1:1.2.',
		array('<br />','<br />'),
		array('q' => '/modules/School Admin/house_manage_edit_photoDeleteProcess.php', 'gibbonHouseID' => $houseID, 'divert' => true )
	);

	$el = $form->addElement('hidden', 'logo', $hObj->getField('logo'));

	$form->addElement('submitBtn', null);
	$form->render();
}
