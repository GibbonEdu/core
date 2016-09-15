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
use Gibbon\core\module ;
use Gibbon\core\post ;

if (! $this instanceof post) die();


$mf = new Functions\functions($this);

$URL = array('q'=>'/modules/System Admin/systemSettings.php');

if (! $this->getSecurity()->isActionAccessible("/modules/System Admin/systemSettings.php")) {
	$this->insertMessage('return.error.0');
	$this->redirect($URL);
}
else {
	//Proceed!
	$args = array("absoluteURL" => FILTER_SANITIZE_URL,
		"absolutePath" => FILTER_DEFAULT,
		"systemName" => FILTER_DEFAULT,
		"indexText" => FILTER_DEFAULT,
		"organisationName" => FILTER_DEFAULT,
		"organisationNameShort" => FILTER_DEFAULT,
		"organisationEmail" => FILTER_SANITIZE_EMAIL,
		"organisationLogo" => FILTER_DEFAULT,
		"organisationAdministrator" => FILTER_SANITIZE_NUMBER_INT,
		"organisationDBA" => FILTER_SANITIZE_NUMBER_INT,
		"organisationHR" => array('filter' => FILTER_SANITIZE_NUMBER_INT,
										'options'=>array('default'=>1)),
		"organisationAdmissions" => FILTER_SANITIZE_NUMBER_INT,
		"pagination" => FILTER_SANITIZE_NUMBER_INT,
		"timezone" => FILTER_DEFAULT,
		"country" => FILTER_DEFAULT,
		"firstDayOfTheWeek" => FILTER_DEFAULT,
		"analytics" => FILTER_DEFAULT,
		"emailLink" => FILTER_SANITIZE_URL,
		"webLink" => FILTER_SANITIZE_URL,
		"primaryAssessmentScale" => FILTER_SANITIZE_NUMBER_INT,
		"installType" => FILTER_DEFAULT,
		"statsCollection" => FILTER_DEFAULT,
		"passwordPolicyMinLength" => array('filter' => FILTER_SANITIZE_NUMBER_INT,
										'options'=>array('default'=>8, 'min_range'=>4, 'max_range'=>16)),
		"passwordPolicyAlpha" => FILTER_DEFAULT,
		"passwordPolicyNumeric" => FILTER_DEFAULT,
		"passwordPolicyNonAlphaNumeric" => FILTER_DEFAULT,
		"sessionDuration" => array('filter' => FILTER_SANITIZE_NUMBER_INT,
										'options'=>array('default'=>1200, 'min_range'=>1200)),
		"allowableHTML" => FILTER_DEFAULT,
		"currency" => FILTER_DEFAULT,
		"gibboneduComOrganisationName" => FILTER_DEFAULT,
		"gibboneduComOrganisationKey" => FILTER_DEFAULT
	);
	
	foreach($args as $name => $filter)
	{
		if (is_array($filter))
			$post[$name] = filter_var($_POST[$name], $filter['filter'], $filter['options']);
		else
			$post[$name] = filter_var($_POST[$name], $filter);
		if (false === $post[$name])
		{
			$this->insertMessage(array('Your request failed because %1$s was not a valid entry.', array($name))) ;
			$this->redirect($URL);
		}
	}

	$required = array( 'absoluteURL', 'systemName', 'organisationLogo', 'indexText', 'organisationName', 'organisationNameShort', 
		'organisationAdministrator', 'organisationDBA', 'organisationHR', 'organisationAdmissions', 'pagination', 'timezone', 
		'installType', 'statsCollection', 'passwordPolicyMinLength', 'passwordPolicyAlpha', 'passwordPolicyNumeric', 'passwordPolicyNonAlphaNumeric', 
		'currency' ) ;
	foreach($required as $name)
		if (empty($post[$name]))
		{
			$this->insertMessage(array('Your request failed because %1$s was not supplied.', array($name))) ;
			$this->redirect($URL);
		}
	
	
	//Validate Inputs
	if (! is_numeric($post['pagination']) || ! in_array($post['firstDayOfTheWeek'], array("Monday", "Sunday"))) {
		$this->insertMessage('Your request failed because some inputs did not meet a requirement for uniqueness.') ;
		$this->redirect($URL);
	}
	else {	
		//Write to database
		$fail = false ;
		
		$this->config->setScope('System');
		if (! $this->config->setSettingByScope('absoluteURL', $post['absoluteURL']) ) $fail = true;
		if (! $this->config->setSettingByScope("absolutePath", $post['absolutePath']) ) $fail = true;
		if (! $this->config->setSettingByScope("systemName", $post['systemName']) ) $fail = true;
		if (! $this->config->setSettingByScope("indexText", $post['indexText']) ) $fail = true;
		if (! $this->config->setSettingByScope("organisationName", $post['organisationName']) ) $fail = true;
		if (! $this->config->setSettingByScope("organisationNameShort", $post['organisationNameShort']) ) $fail = true;
		if (! $this->config->setSettingByScope("organisationLogo", $post['organisationLogo']) ) $fail = true;
		if (! $this->config->setSettingByScope("organisationEmail", $post['organisationEmail']) ) $fail = true;
		if (! $this->config->setSettingByScope("organisationAdministrator", $post['organisationAdministrator']) ) $fail = true;

		//Update session variables
		if ($personObj = new \Gibbon\Record\person($this, $post['organisationAdministrator'])) {
			$this->session->set("organisationAdministratorName", module::formatName("", $personObj->getField("preferredName"), $personObj->getField("surname"), "Staff", FALSE, TRUE)) ;
			$this->session->set("organisationAdministratorEmail", $personObj->getField("email") );
		} else $fail = true;
		
		
		if (! $this->config->setSettingByScope("organisationDBA", $post['organisationDBA']) ) $fail = true;
		//Update session variables
		if ($personObj = new \Gibbon\Record\person($this, $post['organisationDBA'])) {
			$this->session->set("organisationDBAName", module::formatName("", $personObj->getField("preferredName"), $personObj->getField("surname"), "Staff", FALSE, TRUE)) ;
			$this->session->set("organisationDBAEmail", $personObj->getField("email")) ;
		}
		
		
		if (! $this->config->setSettingByScope("organisationHR", $post['organisationHR']) ) $fail = true;
		//Update session variables
		if ($personObj = new \Gibbon\Record\person($this, $post['organisationHR'])) {
			$this->session->set("organisationHRName", module::formatName("", $personObj->getField("preferredName"), $personObj->getField("surname"), "Staff", FALSE, TRUE)) ;
			$this->session->set("organisationHREmail", $personObj->getField("email")) ;
		}
		
		
		
		if (! $this->config->setSettingByScope("organisationAdmissions", $post['organisationAdmissions']) ) $fail = true;
		//Update session variables
		if ($personObj = new \Gibbon\Record\person($this, $post['organisationAdmissions'])) {
			$this->session->set("organisationAdmissionsName", module::formatName("", $personObj->getField("preferredName"), $personObj->getField("surname"), "Staff", FALSE, TRUE)) ;
			$this->session->set("organisationAdmissionsEmail", $personObj->getField("email")) ;
		}
		
		
		if (! $this->config->setSettingByScope("pagination", $post['pagination']) ) $fail = true;
		if (! $this->config->setSettingByScope("country", $post['country']) ) $fail = true;
		if (! $this->config->setSettingByScope("firstDayOfTheWeek", $post['firstDayOfTheWeek']) ) $fail = true;
		
		if (! $mf->setFirstDayOfTheWeek($post['firstDayOfTheWeek'])) $fail = true ;
		
		if (! $this->config->setSettingByScope("currency", $post['currency']) ) $fail = true;
		if (! $this->config->setSettingByScope("gibboneduComOrganisationName", $post['gibboneduComOrganisationName']) ) $fail = true;
		if (! $this->config->setSettingByScope("gibboneduComOrganisationKey", $post['gibboneduComOrganisationKey']) ) $fail = true;
		if (! $this->config->setSettingByScope("timezone", $post['timezone']) ) $fail = true;
		if (! $this->config->setSettingByScope("analytics", $post['analytics']) ) $fail = true;
		if (! $this->config->setSettingByScope("emailLink", $post['emailLink']) ) $fail = true;
		if (! $this->config->setSettingByScope("webLink", $post['webLink']) ) $fail = true;
		if (! $this->config->setSettingByScope("primaryAssessmentScale", $post['primaryAssessmentScale']) ) $fail = true;
		if (! $this->config->setSettingByScope("installType", $post['installType']) ) $fail = true;
		if (! $this->config->setSettingByScope("statsCollection", $post['statsCollection']) ) $fail = true;
		if (! $this->config->setSettingByScope("passwordPolicyMinLength", $post['passwordPolicyMinLength']) ) $fail = true;
		if (! $this->config->setSettingByScope("passwordPolicyAlpha", $post['passwordPolicyAlpha']) ) $fail = true;
		if (! $this->config->setSettingByScope("passwordPolicyNumeric", $post['passwordPolicyNumeric']) ) $fail = true;
		if (! $this->config->setSettingByScope("passwordPolicyNonAlphaNumeric", $post['passwordPolicyNonAlphaNumeric']) ) $fail = true;
		if (! $this->config->setSettingByScope("allowableHTML", $post['allowableHTML']) ) $fail = true;
		if (! $this->config->setSettingByScope("sessionDuration", $post['sessionDuration']) ) $fail = true;
		
		
		
		if ( $fail ) {
			//Fail 2
			$this->insertMessage('return.error.2') ;
			$this->redirect($URL);
		}
		else {
			//Success 0
			$this->session->getSystemSettings($this->pdo) ;
			$this->insertMessage('return.success.0', 'success') ;
			$this->redirect($URL);
		}
	}
}
