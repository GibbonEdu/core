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
use Gibbon\Record\fileExtension ;

if (! $this instanceof view) die();

if ($this->getSecurity()->isActionAccessible()) {
	//Proceed!
	$trail = $this->initiateTrail();
	$trail->addTrail('Manage File Extensions', array('q'=>'/modules/School Admin/fileExtensions_manage.php'));
	$header = $trail->trailEnd = 'Edit File Extensions';
	if (isset($_GET["gibbonFileExtensionID"]) && $_GET["gibbonFileExtensionID"] == 'Add') $header = $trail->trailEnd = 'Add File Extensions';
	$trail->render($this);
	
	$this->render('default.flash');

	$_GET['page'] = isset($_GET['page']) ? $_GET['page'] : 1 ;
	
	if ($header === 'Edit File Extensions') $this->linkTop(array('Add'=>array('q'=>'/modules/School Admin/fileExtensions_manage_edit.php', 'gibbonFileExtensionID'=>'Add')));
	$this->h2($header);
	
	//Check if school year specified
	if (empty($_GET["gibbonFileExtensionID"])) {
		$this->displayMessage("You have not specified one or more required parameters.") ;
	}
	else {
		$dbObj = new fileExtension($this, $_GET["gibbonFileExtensionID"]);
		if (! $dbObj->getSuccess()) {
			$this->displayMessage("The specified record cannot be found.") ;
		}
		else {
			$form = $this->getForm(null, array('q'=>'/modules/School Admin/fileExtensions_manage_editProcess.php', "gibbonFileExtensionID"=>$_GET["gibbonFileExtensionID"], 'page'=>$_GET['page']), true);
			
			$el = $form->addElement('text', 'extension', $dbObj->getField("extension"));
			$el->setRequired();
			$el->setMaxLength(7);
			$el->nameDisplay = 'Extension' ;
			$el->description = 'Must be unique.' ;

			$el = $form->addElement('text', 'name', $dbObj->getField("name"));
			$el->setRequired();
			$el->setMaxLength(50);
			$el->nameDisplay = 'Name' ;

			$el = $form->addElement('select', 'type', $dbObj->getField("type"));
			$el->setRequired();
			$el->setPleaseSelect('You need to select a type');
			$el->nameDisplay = 'Type' ;
			$el->addOption($this->__('Please select...'), "Please select...");
			foreach($this->pdo->getEnum('gibbonFileExtension', 'type') as $w)
				$el->addOption($this->__($w), $w);

			$el = $form->addElement('text', 'mimeType', $dbObj->getField("mimeType"));
			$el->setMaxLength(100);
			$el->nameDisplay = 'Mime Type' ;
			$el->description = array('A comma separated list of mime type for this file extension. Should you need to find a mime type, Gibbon suggests an internet search.  One useful resource is the %sSitepoint Mime Type Reference%s.', array('<a href="https://www.sitepoint.com/web-foundations/mime-types-complete-list/" target="_blank">', '</a>'));

			$form->addElement('submitBtn', null);
			
			$form->render();
		}
	}
}
