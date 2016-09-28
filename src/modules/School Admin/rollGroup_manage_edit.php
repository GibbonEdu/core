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
use Gibbon\Record\schoolYear ;
use Gibbon\Record\space ;
use Gibbon\Record\rollGroup ;
use Gibbon\People\staff ;

if (! $this instanceof view) die();

if ($this->getSecurity()->isActionAccessible()) {
	//Proceed!
	$trail = $this->initiateTrail();
	$header = $trail->trailEnd = $_GET['gibbonRollGroupID'] == 'Add' ? 'Add Roll Group' : 'Edit Roll Group' ;
	$trail->addTrail('Manage Roll Groups', array('q'=>'/modules/School Admin/rollGroup_manage.php','gibbonSchoolYearID'=>$_GET['gibbonSchoolYearID']));
	$trail->render($this);
	
	$this->render('default.flash');
	$this->h3($header);

    if (empty($_GET['gibbonRollGroupID']) || empty($_GET['gibbonSchoolYearID'])) {
        $this->displayMessage('You have not specified one or more required parameters.');
    } else {
		$syObj = new schoolYear($this, $_GET['gibbonSchoolYearID']);
		$rGObj = $syObj->getRollGroup($_GET['gibbonRollGroupID']);

        if (! $rGObj->getSuccess()) {
            $this->displayMessage('The specified record cannot be found.');
        } else {
            //Let's go!
			$form = $this->getForm(null, array('q'=>"/modules/School Admin/rollGroup_manage_editProcess.php", 'gibbonRollGroupID' => $_GET['gibbonRollGroupID'],
					'gibbonSchoolYearID' => $_GET['gibbonSchoolYearID']
				), 
				true);

			$el = $form->addElement('text', 'schoolYearName', $this->htmlPrep($syObj->getField("name")));
			$el->nameDisplay = 'School Year' ;
			$el->description = 'This value cannot be changed.' ;
			$el->setReadOnly();
			
			$el = $form->addElement('text', 'name', $this->htmlPrep($rGObj->getField("name")));
			$el->setRequired();
			$el->setMaxLength(10);
			$el->nameDisplay = 'Name' ;
			$el->description = 'Must be unique.' ;
			
			$el = $form->addElement('text', 'nameShort', $this->htmlPrep($rGObj->getField("nameShort")));
			$el->setRequired();
			$el->setMaxLength(5);
			$el->nameDisplay = 'Short Name' ;
			$el->description = 'Must be unique.' ;
			
			$pObj = new staff($this);
			$people = $pObj->allStaff('Full');
			$rGObj->people = $people;
			$rGObj->form = $form ; 
			
			$this->render('rollGroups.tutors', $rGObj);

			$el = $form->addElement('select', 'gibbonSpaceID', $rGObj->getField("gibbonSpaceID"));
			$el->nameDisplay = 'Location';
			$el->addOption('');
			$sObj = new space($this);
			$spaces = $sObj->findAllBy(array(), array('name'=>'ASC'));
			foreach ($spaces as $space)
				$el->addOption($this->htmlPrep($space->name), $space->gibbonSpaceID);

			$rollGroups = array();
			$nextYear = $syObj->getNextSchoolYearID();
			if (! empty($nextYear)) {
				$dbObj = new rollGroup($this);
				$rollGroups = $dbObj->findAll('SELECT * 
						FROM `gibbonRollGroup` 
						WHERE `gibbonRollGroup`.`gibbonSchoolYearID` = :gibbonSchoolYearID 
						ORDER BY `name`', 
						array('gibbonSchoolYearID' => $nextYear)
					);
				unset($dbObj);
			}
			if (! empty($rollGroups))
			{
				$el = $form->addElement('select', 'gibbonRollGroupIDNext', $rGObj->getField("gibbonRollGroupIDNext"));
				$el->nameDisplay = 'Next Roll Group';
				$el->description = 'Sets student progression on rollover.';
				$el->addOption('');
				foreach($rollGroups as $rollGroup)
					$el->addOption($this->htmlPrep($rollGroup->getField('name')), $rollGroup->getField('gibbonRollGroupID'));
			} 
			else
			{
				if (! $nextYear)
					$message = 'The next school year cannot be determined!';
				else
					$message = 'The next school year has not had any roll groups created!';
				$form->addElement('warning', null, $message);
			}

			$el = $form->addElement('url', 'website', $this->htmlPrep($rGObj->getField("website")));
			$el->nameDisplay = 'Form Group Website' ;
			$el->description = 'Include http://' ;
			
			$form->addElement('hidden', "gibbonSchoolYearID", $_GET['gibbonSchoolYearID'], $this);
			$form->addElement('submitBtn', null);
			$form->render();   
        }
    }
}
