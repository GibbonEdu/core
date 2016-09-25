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
	$trail->trailEnd = 'Main Menu Settings';
	$trail->render($this);
		
	$this->render('default.flash');
	
	$this->h2('Main Menu Settings');

	$form = $this->getForm(null, array('q'=> "/modules/System Admin/menu_manageProcess.php"), true);
	
	$el = $form->addElement('textArea', null);
	$el->injectRecord($this->config->getSetting('mainMenuCategories', 'System'));
	$el->setRequired();
	if (empty($el->value)) 
	{
		$mObj = new module($this);
		$cats = array();
		foreach($mObj->findAll('SELECT DISTINCT `category` 
			FROM `gibbonModule` 
			ORDER BY `category`') as $cat=>$w)
			if ($cat !== 'Other')
				$cats[] = $cat;
		$cats[] = 'Other';
		$el->value = trim(implode(',', $cats), ',');
		$this->config->setSettingByScope('mainMenuCategories', $el->value, 'System');
	} else
		$el->value = trim(implode(',', $el->value), ',');
	
	$form->addElement('submitBtn', null);
	
	$form->render();
}
