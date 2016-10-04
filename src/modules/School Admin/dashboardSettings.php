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
use Gibbon\Record\hook ;

if (! $this instanceof view) die();

if ($this->getSecurity()->isActionAccessible()) {
	//Proceed!
	$trail = $this->initiateTrail();
	$trail->trailEnd = 'Dashboard Settings';
	$trail->render($this);
	
	$this->render('default.flash');

	$this->h2('Dashboard Settings');
	
	$form = $this->getForm(null, array('q' => '/modules/School Admin/dashboardSettingsProcess.php'), true);
	
	$el = $form->addElement('select', null);
	$el->injectRecord($this->config->getSetting('staffDashboardDefaultTab', 'School Admin'));
	$el->value = $this->htmlPrep($el->value);
	$el->addOption('');
	$el->addOption($this->__('Planner'), 'Planner');
	
	$obj = new hook($this);
	$hooks = $obj->findAllByType('Staff Dashboard');
	foreach($hooks as $hook)
		$el->addOption($this->__($nook->name), $hook->name);
	
	$el = $form->addElement('select', null);
	$el->injectRecord($this->config->getSetting('studentDashboardDefaultTab', 'School Admin'));
	$el->value = $this->htmlPrep($el->value);
	$el->addOption('');
	$el->addOption($this->__('Planner'), 'Planner');
	
	$hooks = $obj->findAllByType('Student Dashboard');
	foreach($hooks as $hook)
		$el->addOption($this->__($nook->name), $hook->name);
	
	$el = $form->addElement('select', null);
	$el->injectRecord($this->config->getSetting('parentDashboardDefaultTab', 'School Admin'));
	$el->value = $this->htmlPrep($el->value);
	$el->addOption('');
	$el->addOption($this->__('Learning Overview'), 'Learning Overview');
	$el->addOption($this->__('Timetable'), 'Timetable');
	$el->addOption($this->__('Activities'), 'Activities');
	
	$hooks = $obj->findAllByType('Parental Dashboard');
	foreach($hooks as $hook)
		$el->addOption($this->__($nook->name), $hook->name);
	
	$form->addElement('submitBtn', null);
	
	$form->render();
}
