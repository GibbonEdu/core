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
	$trail->trailEnd = 'Tracking Settings' ;
	$trail->render($this);

	$this->render('default.flash');
	$this->h2('Tracking Settings');
	
	$yGObj = new yearGroup($this);
	$yearGroups = $yGObj->getYearGroups();
	if (empty($yearGroups))
		$this->displayMessage("There are no records to display.");
	else
	{
		$form = $this->getForm(null, array('q' => '/modules/School Admin/trackingSettingsProcess.php'), true);
	
		$el = $form->addElement('h3', null, 'Data Points - External Assessment');
		$el = $form->addElement('note', null, 'Use the options below to select the external assessments that you wish to include in your Data Points export. If duplicates of any assessment exist, only the most recent entry will be shown.');
	
		$eAObj = new externalAssessment($this);
		$ea = $eAObj->findAll("SELECT DISTINCT `gibbonExternalAssessment`.`gibbonExternalAssessmentID`, `gibbonExternalAssessment`.`nameShort`, `gibbonExternalAssessmentField`.`category` 
					FROM `gibbonExternalAssessment` 
						JOIN `gibbonExternalAssessmentField` ON (`gibbonExternalAssessmentField`.`gibbonExternalAssessmentID` = `gibbonExternalAssessment`.`gibbonExternalAssessmentID`) 
					WHERE `active`='Y' 
					ORDER BY `nameShort`, `category`");
		$count = 0;
		if (count($ea) < 1)
			$form->addElement('error', null, "There are no records to display.");
		else
		{
			$externalAssessmentDataPoints = unserialize($this->config->getSettingByScope("Tracking", "externalAssessmentDataPoints", "a:0:{}")) ;
			if (! is_array($externalAssessmentDataPoints)) {
				$externalAssessmentDataPoints =	array();
				$this->config->setSettingByScope("externalAssessmentDataPoints", serialize($externalAssessmentDataPoints), 'Tracking'); 
			}
			foreach ($ea as $row)
			{
				$el = $form->addElement('optGroup', 'gibbonExternalAssessmentID['.$count.']');
				$el->description = substr($row->getField('category'), (strpos($row->getField('category'), "_") + 1));
				$el->nameDisplay = $row->getField('nameShort');
				$el->optionType = 'checkbox';
				for ($i=0; $i < count($yearGroups); $i = $i + 2)
				{
					$checked = false;
					foreach ($externalAssessmentDataPoints AS $externalAssessmentDataPoint) 
						if (intval($externalAssessmentDataPoint["gibbonExternalAssessmentID"]) == intval($row->getField('gibbonExternalAssessmentID'))
							&& $externalAssessmentDataPoint["category"] == $row->getField('category')) 
							if (isset($externalAssessmentDataPoint["gibbonYearGroupIDList"]) && in_array($yearGroups[$i], explode(',', $externalAssessmentDataPoint["gibbonYearGroupIDList"]))) 
								$checked = true ;
					$el->addOption($el->name."[gibbonYearGroupID][" . ($i)/2 . ']', $yearGroups[$i], $yearGroups[($i + 1)], $checked);
				}
				$form->addElement('hidden', 'gibbonExternalAssessmentID[' . $count .'][ID]', $row->getField("gibbonExternalAssessmentID"));
				$form->addElement('hidden', 'external_category[' . $count .'][category]', $row->getField("category"));
				$count++;
			}
		}
		$form->addElement('hidden', 'gibbonExternalAssessmentID_count', $count);
		$form->addElement('hidden', 'external_year_count', count($yearGroups)/2);
		
		$form->addElement('h3', null, 'Data Points - Internal Assessment');
		$form->addElement('note', null, 'Use the options below to select the internal assessments that you wish to include in your Data Points export. If duplicates of any assessment exist, only the most recent entry will be shown. Year 13 settings will be applied to recent grauates, who will be shown in the Last Graduating Cohort tab in the export.');

		$count = 0 ;
		$internalAssessmentTypes = explode(",", $this->config->getSettingByScope("Formal Assessment", "internalAssessmentTypes")) ;
		$internalAssessmentDataPoints = unserialize($this->config->getSettingByScope( "Tracking", "internalAssessmentDataPoints", "a:0:{}")) ;
		if (empty($internalAssessmentDataPoints)) 
		{
			$internalAssessmentDataPoints = array();
			$this->config->setSettingByScope("internalAssessmentDataPoints", serialize($internalAssessmentDataPoints), 'Tracking');
		}
		foreach ($internalAssessmentTypes AS $internalAssessmentType) 
		{
			$el = $form->addElement('optGroup', null);
			$el->optionType = 'checkbox';
			$el->nameDisplay = $internalAssessmentType;
			for ($i=0; $i<count($yearGroups); $i=$i+2) {
				$checked = false ;
				foreach ($internalAssessmentDataPoints AS $internalAssessmentDataPoint) 
					if ($internalAssessmentDataPoint["type"] == $internalAssessmentType && in_array($yearGroups[$i], explode(',', $internalAssessmentDataPoint["gibbonYearGroupIDList"]))) 
						$checked = true ;
				$el->addOption('internal_type[' . $count . '][gibbonYearGroupID][' . ($i)/2 . ']', $yearGroups[$i], $yearGroups[($i + 1)], $checked);
			}
			$form->addElement('hidden', 'internal_type[' . $count . '][type]', $internalAssessmentType);
			$count++;
		}
		
		$form->addElement('hidden', 'internal_type_count', $count);
		$form->addElement('hidden', 'internal_year_count', count($yearGroups)/2);
		$form->addElement('submitBtn', null);
		
		$form->render();
	}
}
