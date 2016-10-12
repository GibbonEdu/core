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
use Gibbon\core\trans ;
use Gibbon\Record\stringReplacement ;

if (! $this instanceof view) die();

if ($this->getSecurity()->isActionAccessible()) {
	//Proceed!
	$trail = $this->initiateTrail();
	$trail->addTrail('Manage String Replacements', array('q' => '/modules/System Admin/stringReplacement_manage.php'));
	$header = $trail->trailEnd = $_GET['gibbonStringID'] === 'Add' ? 'Add String Replacement' : 'Edit String Replacement';
	$trail->render($this);
		
	$this->render('default.flash');

	$search = isset($_GET['search']) ? $_GET['search'] : '';
	
    $stringID = intval($_GET['gibbonStringID']) == 0 ? 'Add' : $_GET['gibbonStringID'];

	$links = array();
	if ($stringID !== 'Add') 
		$links['add'] = array('q' => '/modules/System Admin/stringReplacement_manage_edit.php', 'search' =>$search, 'gibbonStringID' => 'Add');
	if (! empty($search)) 
		$links['Back to Search Results'] = array('q' => '/modules/System Admin/stringReplacement_manage.php', 'search' => $search);
	$this->linkTop($links);
	$this->h2($header);
	
    if (empty($stringID)) {
        $this->displayMessage('You have not specified one or more required parameters.');
    } else {

        $sObj = new stringReplacement($this, $stringID);

        if ($sObj->rowCount() != 1 && $stringID !== 'Add') {
            $this->displayMessage('The specified record cannot be found.');
        } else {
            //Let's go!

			$form = $this->getForm(null, array('q'=>'/modules/System Admin/stringReplacement_manage_editProcess.php', 'gibbonStringID' => $stringID, 'search' => $search), true);
	
			$el = $form->addElement('text', 'original', $this->htmlPrep($sObj->getField('original')));
			$el->setMaxLength(100);
			$el->nameDisplay = 'Original String';
			$el->setRequired();
			
			$el = $form->addElement('text', 'replacement', $this->htmlPrep($sObj->getField('replacement')));
			$el->setMaxLength(100);
			$el->nameDisplay = 'Replacement String';
			$el->setRequired();
			
			$el = $form->addElement('select', 'mode', $sObj->getField('mode'));
			$el->nameDisplay = 'Mode';
			$el->addOption($this->__('Whole'), 'Whole');
			$el->addOption($this->__('Partial'), 'Partial');
			
			$el = $form->addElement('yesno', 'caseSensitive', $sObj->getField('caseSensitive'));
			$el->nameDisplay = 'Case Sensitive';
			
			$el = $form->addElement('number', 'priority', $this->htmlPrep($sObj->getField('priority')));
			$el->nameDisplay = 'Priority';
			$el->description = 'The higher the priority the earlier the replacement happens.';
			$el->setRequired();
			$el->setMax(99);
			$el->setMin(1);
			$el->setInteger();
			
			$el = $form->addElement('submitBtn', null);

			$form->render();			
        }
    }
}
