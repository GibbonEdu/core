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
	$trail->trailEnd = 'Manage Behaviour Settings';
	$trail->render($this);
	
	$this->render('default.flash');

	$enableDescriptors = $this->config->getSettingByScope('Behaviour', 'enableDescriptors');
	$enableLevels = $this->config->getSettingByScope('Behaviour', 'enableLevels');
	$enableBehaviourLetters = $this->config->getSettingByScope('Behaviour', 'enableBehaviourLetters');

	$this->h2('Manage Behaviour Settings');
	
	$form = $this->getForm($this->session->get("absolutePath") . "/modules/School Admin/behaviourSettingsProcess.php", array(), true);
	$form->addElement('h3', null, 'Descriptors');

	$el = $form->addElement('yesno', null);
	$el->injectRecord($this->config->getSetting('enableDescriptors', 'Behaviour'));
	
	$this->render('behaviour.enableDescriptors');
	
	$el = $form->addElement('textArea', null);
	$el->injectRecord($this->config->getSetting('positiveDescriptors', 'Behaviour'));
	$el->setRequired();
	$el->rows = 4;
	$el->row->mergeClass = 'showRow';
	if ($enableDescriptors == 'N')  
		$el->row->style = !empty($el->row->style) ? $el->row->style.' display: none; ': 'display: none; ' ;

	$el = $form->addElement('textArea', null);
	$el->injectRecord($this->config->getSetting('negativeDescriptors', 'Behaviour'));
	$el->setRequired();
	$el->rows = 4;
	$el->row->mergeClass = 'showRow';
	if ($enableDescriptors == 'N')  
		$el->row->style = !empty($el->row->style) ? $el->row->style.' display: none; ': 'display: none; ' ;
	
	$form->addElement('h3', null, 'Levels');

	$el = $form->addElement('yesno', null);
	$el->injectRecord($this->config->getSetting('enableLevels', 'Behaviour'));
	
	$this->render('behaviour.enableLevels');
	
	$el = $form->addElement('textArea', null);
	$el->injectRecord($this->config->getSetting('levels', 'Behaviour'));
	$el->setRequired();
	$el->rows = 4;
	$el->row->class ='levelsRow';
	if ($enableLevels == 'N') 
		$el->row->style = !empty($el->row->style) ? $el->row->style.' display: none; ': 'display: none; ' ;
	
	
	$form->addElement('h3', null,'Behaviour Letters'); 
	
	$el = $form->addElement('info', null, 'By using an %1$sincluded CLI script%2$s, %3$s can be configured to automatically generate and email behaviour letters to parents and tutors, once certain negative behaviour threshold levels have been reached. In your letter text you may use the following fields: %4$s');
	$el->valueParameters = array("<a target='_blank' href='https://gibbonedu.org/support/administrators/command-line-tools/'>", '</a>', $this->session->get('systemName'), '[studentName], [rollGroup], [behaviourCount], [behaviourRecord]');

	$el = $form->addElement('yesno', null);
	$el->injectRecord($this->config->getSetting('enableBehaviourLetters', 'Behaviour'));
	$el->script = false ;

	$this->render('behaviour.enableBehaviourLetters');
	
	$el = $form->addElement('select', null);
	$el->injectRecord($this->config->getSetting('behaviourLettersLetter1Count', 'Behaviour'));
	$el->setPleaseSelect();
	$el->addOption($this->__('Please select...'), 'Please select...');
	for ($i = 1; $i <= 20; ++$i)
		$el->addOption($i);
	$el->row->class = 'behaviourLetters';
	if ($enableBehaviourLetters == 'N')
	{
		$el->row->style = !empty($el->row->style) ? $el->row->style.' display: none; ': 'display: none; ' ;
		$el->validate->disable = true ;
	}
	
	$el = $form->addElement('textArea', null);
	$el->injectRecord($this->config->getSetting('behaviourLettersLetter1Text', 'Behaviour'));
	$el->setRequired();
	$el->row->class = 'behaviourLetters';
	$el->rows = 4;
	if ($enableBehaviourLetters == 'N')
	{
		$el->row->style = !empty($el->row->style) ? $el->row->style.' display: none; ': 'display: none; ' ;
		$el->validate->disable = true ;
	}
	
	$el = $form->addElement('select', null);
	$el->injectRecord($this->config->getSetting('behaviourLettersLetter2Count', 'Behaviour'));
	$el->setPleaseSelect();
	$el->addOption($this->__('Please select...'), 'Please select...');
	for ($i = 1; $i <= 20; ++$i)
		$el->addOption($i);
	$el->row->class = 'behaviourLetters';
	if ($enableBehaviourLetters == 'N')
	{
		$el->row->style = !empty($el->row->style) ? $el->row->style.' display: none; ': 'display: none; ' ;
		$el->validate->disable = true ;
	}
	
	$el = $form->addElement('textArea', null);
	$el->injectRecord($this->config->getSetting('behaviourLettersLetter2Text', 'Behaviour'));
	$el->setRequired();
	$el->row->class = 'behaviourLetters';
	$el->rows = 4;
	if ($enableBehaviourLetters == 'N')
	{
		$el->row->style = !empty($el->row->style) ? $el->row->style.' display: none; ': 'display: none; ' ;
		$el->validate->disable = true ;
	}
	
	$el = $form->addElement('select', null);
	$el->injectRecord($this->config->getSetting('behaviourLettersLetter3Count', 'Behaviour'));
	$el->setPleaseSelect();
	$el->addOption($this->__('Please select...'), 'Please select...');
	for ($i = 1; $i <= 20; ++$i)
		$el->addOption($i);
	$el->row->class = 'behaviourLetters';
	if ($enableBehaviourLetters == 'N')
	{
		$el->row->style = !empty($el->row->style) ? $el->row->style.' display: none; ': 'display: none; ' ;
		$el->validate->disable = true ;
	}
	
	$el = $form->addElement('textArea', null);
	$el->injectRecord($this->config->getSetting('behaviourLettersLetter3Text', 'Behaviour'));
	$el->setRequired();
	$el->row->class = 'behaviourLetters';
	$el->rows = 4;
	if ($enableBehaviourLetters == 'N')
	{
		$el->row->style = !empty($el->row->style) ? $el->row->style.' display: none; ': 'display: none; ' ;
		$el->validate->disable = true ;
	}
	
	$form->addElement('h3', null, 'Miscellaneous');
	
	$el = $form->addElement('url', null);
	$el->injectRecord($this->config->getSetting('policyLink', 'Behaviour'));
	$el->setMaxLength (150) ;

	$form->addElement('submitBtn', null);
	$form->render();
}
