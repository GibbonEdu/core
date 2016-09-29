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
	$trail->trailEnd = 'Manage Activity Settings';
	$trail->render($this);
	
	$this->render('default.flash');

	$this->h2('Manage Activity Settings');
	$form = $this->getForm(GIBBON_ROOT . "modules/School Admin/activitySettingsProcess.php", array(), true);
	$form->addElement('h3', null, 'Activity Settings');

	$el = $form->addElement('select', null);
	$el->injectRecord($this->config->getSetting('dateType', 'Activities'));
	$el->addOption($this->__('Date'), 'Date');
	$el->addOption($this->__('Term'), 'Term');
	$x = '<script type="text/javascript">
        $(document).ready(function(){ 
			';
	if ($el->value == 'Date') 
    	$x .= '$("#maxPerTermRow").css("display","none");
		';
	$scriptDisplay = $this->session->get('theme.settings.script.display');
	$x .= '$("#_dateType").change(function(){
                if ($("#_dateType option:selected").val()=="Term" ) {
                    $("#maxPerTermRow").slideDown("fast", $("#maxPerTermRow").css("display","'.$scriptDisplay.'")); 
                }
                else {
                    $("#maxPerTermRow").css("display","none");
                }
             });
        });
    </script>';
	$this->addScript($x);

	$el = $form->addElement('select', null);
	$el->injectRecord($this->config->getSetting('maxPerTerm', 'Activities'));
	$el->row->id = 'maxPerTermRow';
	$el->addOption($this->__('Unlimited'), 0);
	for($i=1; $i<=5; $i++)
		$el->addOption($i);

	$el = $form->addElement('select', null);
	$el->injectRecord($this->config->getSetting('access', 'Activities'));
	$el->addOption($this->__('None'), 'None');
	$el->addOption($this->__('View'), 'View');
	$el->addOption($this->__('Register'), 'Register');

	$el = $form->addElement('select', null);
	$el->injectRecord($this->config->getSetting('payment', 'Activities'));
	$el->addOption($this->__('None'), 'None');
	$el->addOption($this->__('Single'), 'Single');
	$el->addOption($this->__('Per Activity'), 'Per Activity');
	$el->addOption($this->__('Single + Per Activity'), 'Single + Per Activity');

	$el = $form->addElement('select', null);
	$el->injectRecord($this->config->getSetting('enrolmentType', 'Activities'));
	$el->addOption($this->__('Competitive'), 'Competitive');
	$el->addOption($this->__('Selection'), 'Selection');

	$el = $form->addElement('yesno', null);
	$el->injectRecord($this->config->getSetting('backupChoice', 'Activities'));

	$el = $form->addElement('textArea', null);
	$el->injectRecord($this->config->getSetting('activityTypes', 'Activities'));
	$el->rows = 4;

	$el = $form->addElement('yesno', null);
	$el->injectRecord($this->config->getSetting('disableExternalProviderSignup', 'Activities'));

	$el = $form->addElement('yesno', null);
	$el->injectRecord($this->config->getSetting('hideExternalProviderCost', 'Activities'));

	$form->addElement('submitBtn', null);
	$form->render();
}