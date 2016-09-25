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
use Gibbon\Record\yearGroup ;
use Gibbon\Record\externalAssessment ;

if (! $this instanceof view) die();

if ($this->getSecurity()->isActionAccessible()) {
	//Proceed!
	$trail = $this->initiateTrail();
	$trail->trailEnd = 'Formal Assessment Settings';
	$trail->render($this);
	
	$this->render('default.flash');

	$this->h2('Formal Assessment Settings');
	
	$form = $this->getForm(null, array('q' => '/modules/School Admin/formalAssessmentSettingsProcess.php'), true);
	
	$form->addElement('h3', null, 'Internal Assessment Settings');
	
	$el = $form->addElement('textArea', null);
	$el->injectRecord($this->config->getSetting('internalAssessmentTypes', 'Formal Assessment'));
	$el->setRequired();
	$el->rows = 4;
	
	$form->addElement('h3', null, 'Primary External Assessment');
	$el = $form->addElement('notice', null, 'These settings allow a particular type of external assessment to be associated with each year group. The selected assessment will be used as the primary assessment to be used as a baseline for comparison (for example, within the Markbook). In addition, a particular field category can be chosen from which to draw data (if no category is chosen, the system will try to pick the best data automatically).');

	$yGObj = new yearGroup($this);
	$yearGroups = $yGObj->findAll('SELECT * FROM gibbonYearGroup ORDER BY sequenceNumber');

	$primaryExternalAssessmentByYearGroup = unserialize($this->config->getSettingByScope('School Admin', 'primaryExternalAssessmentByYearGroup'));
	
	$eaObj = new externalAssessment($this);
	$eaList = $eaObj->findAll("SELECT * FROM gibbonExternalAssessment WHERE active='Y' ORDER BY name");
	$catList = $eaObj->findAll("SELECT DISTINCT gibbonExternalAssessment.gibbonExternalAssessmentID, category 
		FROM gibbonExternalAssessment 
			JOIN gibbonExternalAssessmentField ON (gibbonExternalAssessmentField.gibbonExternalAssessmentID=gibbonExternalAssessment.gibbonExternalAssessmentID) 
		WHERE active='Y' 
		ORDER BY gibbonExternalAssessmentID, category");

	$w = '';
	$w .= $this->renderReturn('formalAssessment.yearStart');
	$count = 0 ;
	foreach($yearGroups as $yearGroup)
	{
		$yearGroup->eaList = $eaList ;
		$yearGroup->catList = $catList ;
		$yearGroup->count = $count++;
		$yearGroup->eaValue = substr($primaryExternalAssessmentByYearGroup[$yearGroup->getField('gibbonYearGroupID')], 0, strpos($primaryExternalAssessmentByYearGroup[$yearGroup->getField('gibbonYearGroupID')], '-'));
		$yearGroup->catValue = substr($primaryExternalAssessmentByYearGroup[$yearGroup->getField('gibbonYearGroupID')], (strpos($primaryExternalAssessmentByYearGroup[$yearGroup->getField('gibbonYearGroupID')], '-') + 1));
		$w .= $this->renderReturn('formalAssessment.yearMember', $yearGroup);
	}
	$w .= $this->renderReturn('formalAssessment.yearEnd');
	$form->addElement('raw', null, $w);
	
	$form->addElement('submitBtn', null);

	$form->render();
}
