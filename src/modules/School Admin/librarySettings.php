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

if (! $this instanceof view) die();

if ($this->getSecurity()->isActionAccessible()) {
	//Proceed!
	$trail = $this->initiateTrail();
	$trail->trailEnd = 'Manage Library Settings';
	$trail->render($this);
	
	$this->render('default.flash');

	$this->h2('Manage Library Settings');
	$form = $this->getForm(null, array('q'=>"/modules/School Admin/librarySettingsProcess.php"), true);
	
	$el = $form->addElement('select', null);
	$el->injectRecord($this->config->getSetting('defaultLoanLength', 'Library'));
	for ($i = 0; $i <= 31; ++$i)
		$el->addOption($i);

	$el = $form->addElement('colour', null);
	$el->injectRecord($this->config->getSetting('browseBGColour', 'Library'));
	$el->setMaxLength(6);


	$el = $form->addElement('url', null);
	$el->injectRecord($this->config->getSetting('browseBGImage', 'Library'));
	$el->setMaxLength(150);

	$form->addElement('submitBtn', null);
	$form->render();
}