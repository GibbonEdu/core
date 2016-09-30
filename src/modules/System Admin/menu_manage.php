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
use Gibbon\Record\module ;

if (! $this instanceof view) die();

if ($this->getSecurity()->isActionAccessible()) {
	//Proceed!
	$trail = $this->initiateTrail();
	$trail->trailEnd = 'Display Settings';
	$trail->render($this);
		
	$this->render('default.flash');
	
	$this->h2('Display Settings');

	$form = $this->getForm(null, array('q'=> "/modules/System Admin/menu_manageProcess.php"), true);
	
	$el = $form->addElement('textArea', null);
	$el->injectRecord($this->config->getSetting('mainMenuCategories', 'System'));
	$el->setRequired();
	if (empty($el->value)) 
	{
		$mObj = new module($this);
		$cats = $mObj->getCategories();
		$el->value = trim(implode(',', $cats), ',');
		$this->config->setSettingByScope('mainMenuCategories', $cats, 'System');
	} 
	
	$el = $form->addElement('yesno', null);
	$el->injectRecord($this->config->getSetting('pageAnchorDisplay', 'System'));
	
	$el = $form->addElement('number', null);
	$el->injectRecord($this->config->getSetting('pagination', 'System'));
	$el->setRequired();
	$el->setNumericality(null, 10, 100, true);

	$form->addElement('submitBtn', null);
	
	$form->render();
}
