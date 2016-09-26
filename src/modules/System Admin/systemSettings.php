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
use Symfony\Component\Yaml\Yaml ;
use Module\System_Admin\Functions\functions ;
use Gibbon\Record\person ;
use Gibbon\Record\setting ;

if (! $this instanceof view) die();

//Module includes
$systemAdmin = new functions($this);

if ($this->getSecurity()->isActionAccessible()) {
	//Prepare and submit stats if that is what the system calls for
	if ($this->session->get("statsCollection")=="Y") {
		$absolutePathProtocol="" ;
		$absolutePath="" ;
		if (substr(GIBBON_URL,0,7)=="http://") {
			$absolutePathProtocol="http" ;
			$absolutePath = substr(GIBBON_URL,7) ;
		}
		else if (substr(GIBBON_URL,0,8)=="https://") {
			$absolutePathProtocol="https" ;
			$absolutePath=substr(GIBBON_URL,8) ;
		}
		$personObj = new person($this);
		$usersTotal = $personObj->getTotalPeople('%');
		$usersFull = $personObj->getTotalPeople();

		echo "<iframe style='display: none; height: 10px; width: 10px' src='https://gibbonedu.org/services/tracker/tracker.php?absolutePathProtocol=" . urlencode($absolutePathProtocol) . "&absolutePath=" . urlencode($absolutePath) . "&organisationName=" . urlencode($this->session->get('organisationName')) . "&type=" . urlencode($this->session->get('installType')) . "&version=" . urlencode($this->config->get('version')) . "&country=" . $this->session->get('country') . "&usersTotal=" . $usersTotal . "&usersFull=" . $usersFull  . "'></iframe>" ;
	}

	//Proceed!
	$trail = $this->initiateTrail();
	$trail->trailEnd = 'System Settings';
	$trail->render($this);

	//Check for new version of Gibbon
	echo $systemAdmin->getCurrentVersion() ;

	$this->render('default.flash');

	$settingObj = new setting($this);

	$sysSettings = $settingObj->findAll("SELECT * FROM `gibbonSetting` WHERE `scope` = :scope", array('scope' =>'System'), '', 'name');

	$form = $this->getForm(null, array('q'=> "/modules/System Admin/systemSettingsProcess.php"), true);

	$form->setName('systemSettingsForm');

	$form->addElement('h3', null, 'System Settings');

	$el = $form->addElement('url', null);
	$el->injectRecord($sysSettings['absoluteURL']->returnRecord());
	$el->setRequired();


	$el = $form->addElement('text', null);
	$el->injectRecord($sysSettings['absolutePath']->returnRecord());
	$el->setRequired();
	$el->setLength('Length <= 50', null, 50);

	$el = $form->addElement('text', null);
	$el->injectRecord($sysSettings['systemName']->returnRecord());
	$el->setRequired();
	$el->setLength('Length <= 50', null, 50);

	$el = $form->addElement('textArea', null);
	$el->injectRecord($sysSettings['indexText']->returnRecord());
	$el->setRequired();

	$el = $form->addElement('select', null);
	$el->injectRecord($sysSettings['installType']->returnRecord());
	$el->addOption($this->__('Production'), 'Production');
	$el->addOption($this->__('Testing'), 'Testing');
	$el->addOption($this->__('Development'), 'Development');

	$el = $form->addElement('yesno', null);
	$el->injectRecord($sysSettings['cuttingEdgeCode']->returnRecord());
	$el->setDisabled();

	$el = $form->addElement('yesno', null);
	$el->injectRecord($sysSettings['statsCollection']->returnRecord());

	$form->addElement('h3', null, 'Organisation Settings');

	$el = $form->addElement('text', null);
	$el->injectRecord($sysSettings['organisationName']->returnRecord());
	$el->setRequired() ;
	$el->setLength('Length <= 50', null, 50);


	$el = $form->addElement('text', null);
	$el->injectRecord($sysSettings['organisationNameShort']->returnRecord());
	$el->setRequired() ;
	$el->setLength('Length <= 50', null, 50);


	$el = $form->addElement('email', null);
	$el->injectRecord($sysSettings['organisationEmail']->returnRecord());


	$el = $form->addElement('text', null);
	$el->injectRecord($sysSettings['organisationLogo']->returnRecord());
	$el->setRequired() ;
	$el->setLength('Length <= 80', null, 80);


	$el = $form->addElement('select', null);
	$el->injectRecord($sysSettings['organisationAdministrator']->returnRecord());
	$el->setPleaseSelect();
	$sql = "SELECT `gibbonPerson`.*
		FROM `gibbonPerson`
		JOIN `gibbonStaff` ON `gibbonPerson`.`gibbonPersonID` = `gibbonStaff`.`gibbonPersonID`
		WHERE `status` = 'Full'
		ORDER BY `surname`,`preferredName`" ;
	$pObj = new person($this);
	$rows = $pObj->findAll($sql);
	foreach ($rows as $person)
		$el->addOption($person->formatName(true, true), $person->getID());


	$el = $form->addElement('select', null);
	$el->injectRecord($sysSettings['organisationDBA']->returnRecord());
	$el->setPleaseSelect();
	foreach ($rows as $person)
		$el->addOption($person->formatName(true, true), $person->getID());


	$el = $form->addElement('select', null);
	$el->injectRecord($sysSettings['organisationAdmissions']->returnRecord());
	$el->setPleaseSelect();
	foreach ($rows as $person)
		$el->addOption($person->formatName(true, true), $person->getID());


	if (isset($sysSettings['organisationHR'])) {
		$el = $form->addElement('select', null);
		$el->injectRecord($sysSettings['organisationHR']->returnRecord());
		$el->setPleaseSelect();
		foreach ($rows as $person)
			$el->addOption($person->formatName(true, true), $person->getID());
	}

	$form->addElement('h3', null, 'Security Settings');

	$el = $form->addElement('select', null);
	$el->injectRecord($sysSettings['passwordPolicyMinLength']->returnRecord());
	$el->setRequired();
	for ($i=4; $i<13; $i++)
		$el->addOption($i);

	$el = $form->addElement('yesno', null);
	$el->injectRecord($sysSettings['passwordPolicyAlpha']->returnRecord());

	$el = $form->addElement('yesno', null);
	$el->injectRecord($sysSettings['passwordPolicyNumeric']->returnRecord());

	$el = $form->addElement('yesno', null);
	$el->injectRecord($sysSettings['passwordPolicyNonAlphaNumeric']->returnRecord());

	$el = $form->addElement('text', null);
	$el->injectRecord($sysSettings['sessionDuration']->returnRecord());
	$el->setRequired();
	$el->setNumericality('Number > 1200 Seconds.', 1200, null, true);


	$el = $form->addElement('textArea', null);
	$el->injectRecord($sysSettings['allowableHTML']->returnRecord());

	$form->addElement('h3', null, 'gibbonedu.com Value Added Services');

	$el = $form->addElement('text', null);
	$el->injectRecord($sysSettings['gibboneduComOrganisationName']->returnRecord());
	$el->maxlength = 80;
	$el->validateOff();

	$el = $form->addElement('text', null);
	$el->injectRecord($sysSettings['gibboneduComOrganisationKey']->returnRecord());
	$el->maxlength = 36;
	$el->validateOff();

	$form->addElement('h3', null, 'Localisation');

	$el = $form->addElement('select', null);
	$el->injectRecord($sysSettings['country']->returnRecord());
	$el->addOption($this->__('Select a Country!'), '' );
	$countries = Yaml::parse(file_get_contents(GIBBON_CONFIG . 'country.yml'));
	foreach($countries['countries'] as $name=>$value)
		$el->addOption($this->__( $name ), $name );
	$el->setRequired();

	if (isset($sysSettings['firstDayOfTheWeek'])) {
		$el = $form->addElement('select', null);
		$el->injectRecord($sysSettings['firstDayOfTheWeek']->returnRecord());
		$el->addOption( $this->__('Monday'), 'Monday' );
		$el->addOption( $this->__('Sunday'), 'Sunday' );
	}

	$el = $form->addElement('select', null);
	$el->injectRecord($sysSettings['timezone']->returnRecord());
	$el->setRequired() ;
	if (empty($el->value)) $el->value = date_default_timezone_get();
	$el->addOption($this->__( 'Please select...' ), '');
	$area = '';
	foreach(timezone_identifiers_list() as $timezone)
	{
		if (strpos($timezone, '/') !== false)
		{
			$w = substr($timezone, 0, strpos($timezone, '/'));
			if ($w !== $area)
			{
				$el->addOption('optgroup', '--'. $w.'--');
				$area = $w ;
			}
		}
		$el->addOption(str_replace($area.'/', '', $timezone), $timezone);
	}

	$el = $form->addElement('select', null);
	$el->injectRecord($sysSettings['currency']->returnRecord());
	$el->setRequired() ;
	$currencies = $this->config->getCurrencyList();
	$el->addOption($this->__('Please select...' ), '');
	foreach($currencies as $value=>$display)
	{
		if (is_array($display))
		{
			$el->addOption('optgroup', '--'.$this->__($value).'--');
			foreach($display as $q=>$w)
				$el->addOption($w, $q);
		}
	}

	$form->addElement('h3', null, 'Miscellaneous');

	$el = $form->addElement('url', null);
	$el->injectRecord($sysSettings['emailLink']->returnRecord());
	$el->setURL("Must start with http:// or https://");


	$el = $form->addElement('url', null);
	$el->injectRecord($sysSettings['webLink']->returnRecord());
	$el->setURL("Must start with http:// or https://");


	$el = $form->addElement('text', null);
	$el->injectRecord($sysSettings['pagination']->returnRecord());
	$el->setRequired();
	$el->setNumericality(null, 10, 100, true);


	$el = $form->addElement('textArea', null);
	$el->injectRecord($sysSettings['analytics']->returnRecord());
	$el->validateOff() ;


	$el = $form->addElement('select', null);
	$el->injectRecord($sysSettings['defaultAssessmentScale']->returnRecord());
	$el->setPleaseSelect();
	$sql = "SELECT *
		FROM gibbonScale
		WHERE active='Y'
		ORDER BY name" ;
	$resultSelect = $this->pdo->executeQuery(array(), $sql, '_');
	while ($row = $resultSelect->fetchObject())
		$el->addOption($this->htmlPrep($this->__($row->name ) ), $row->gibbonScaleID );

	$form->addElement('submitBtn', null);

	$form->render();
}
