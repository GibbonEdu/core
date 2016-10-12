<?php
use Gibbon\core\trans ;
use Gibbon\core\view ;

if ( $this instanceof view) {
	//Get user account details
	$preferredName=$_POST["firstName"] ;
	$password=$_POST["passwordNew"] ;
	if (isset($_POST["support"]) && $_POST["support"]=="true") 
		$_POST["support"] = true ;
	else
		$_POST["support"] = false ;

	if ($_POST['surname']=="" OR $_POST["firstName"]=="" OR $preferredName=="" OR $_POST["email"]=="" OR $_POST["username"]=="" OR $password=="" OR $_POST["passwordConfirm"]=="" OR $_POST["email"]=="" OR $_POST["absoluteURL"]=="" OR $_POST["absolutePath"]=="" OR $_POST["systemName"]=="" OR $_POST["organisationName"]=="" OR $_POST["organisationNameShort"]=="" OR $_POST["timezone"]=="" OR $_POST["country"]=="" OR $_POST["primaryAssessmentScale"]=="" OR $_POST["installType"]=="" OR $_POST["statsCollection"]=="" OR $_POST["cuttingEdgeCode"]=="") {
		$this->displayMessage("Some required fields have not been set, and so installation cannot proceed.");
	}
	else {
		//Check passwords for match
		if ($password!=$_POST["passwordConfirm"]) {
			$this->displayMessage("Your request failed because your passwords did not match.") ;
		}
		else {
			$security = $this->getSecurity();
			
			$userFail = false ;
			//Write to database
			$personObj = new \Gibbon\Record\person($this);
			$personObj->setField("title", filter_var($_POST['title']));
			$personObj->setField("surname", filter_var($_POST['surname']));
			$personObj->setField("firstName", filter_var($_POST["firstName"]));
			$personObj->setField("preferredName", filter_var($_POST["firstName"]));
			$personObj->setField("officialName", filter_var($_POST["firstName"] . " " . $_POST['surname']));
			$personObj->setField("username", filter_var($_POST["username"]));
			$personObj->setField("passwordStrong", $security->getPasswordHash($password, $security->getSalt()));
			$personObj->setField("passwordStrongSalt", $security->getSalt());
			$personObj->setField("status", 'Full');
			$personObj->setField("canLogin", 'Y');
			$personObj->setField("passwordForceReset", 'N');
			$personObj->setField("gibbonRoleIDPrimary", "001");
			$personObj->setField("gibbonRoleIDAll", "001");
			$personObj->setField("email", filter_var($_POST["email"])) ;
			if (! $personObj->writeRecord(true) ) { 
				$userFail = true ;
				$this->displayMessage(trans::__('Errors occurred in populating the database; empty your database, remove config.php and %1$stry again%2$s.', array("<a href='./index.php?q=/installer/install.php'>", "</a>")));
			}
			$id = $personObj->getField("gibbonPersonID");
			$personObj->setField("gibbonPersonID", 1) ;
			$query = "UPDATE `gibbonPerson` SET `gibbonPersonID` = 1 WHERE `gibbonPersonID` = " . intval($id);
			$this->pdo->executeQuery(array(), $query);
			if (! $personObj->getSuccess() ) { 
				$userFail = true ;
				$this->displayMessage(trans::__('Errors occurred in populating the database; empty your database, remove config.php and %1$stry again%2$s.', array("<a href='./index.php?q=/installer/install.php'>", "</a>")));
			}
			
			$staffObj = new \Gibbon\Record\staff($this);
			$staffObj->setField("gibbonPersonID", 1);
			$staffObj->setField("type", 'Teaching') ;
			$staffObj->writeRecord();
			
			$tobj = new \Gibbon\Record\theme($this);
			$tobj->executeQuery(array(), "UPDATE `gibbonTheme` SET `active` = 'N'");
			$tobj->executeQuery(array(), "INSERT INTO `gibbonTheme` (`gibbonThemeID`, `name`, `description`, `active`, `version`, `author`, `url`) VALUES
(0001, 'Bootstrap', 'Gibbon\'s 2016 look and feel.', 'Y', '1.0.00', 'Craig Rayner', 'http://www.craigrayner.com')");			
			$tobj->setDefaultTheme();
			
			$this->getConfig();
			
			if (! $userFail ) {
				$settingsFail = false ;
				if (! $this->config->setSettingByScope("absoluteURL", $_POST["absoluteURL"], 'System')) $settingsFail = true ;
				if (! $this->config->setSettingByScope("absolutePath", $_POST["absolutePath"], 'System')) $settingsFail = true ;
				if (! $this->config->setSettingByScope("systemName", $_POST["systemName"], 'System')) $settingsFail = true ;
				if (! $this->config->setSettingByScope("organisationName", $_POST["organisationName"], 'System')) $settingsFail = true ;
				if (! $this->config->setSettingByScope("organisationNameShort", $_POST["organisationNameShort"], 'System')) $settingsFail = true ;
				if (! $this->config->setSettingByScope("currency", $_POST["currency"], 'System')) $settingsFail = true ;
				if (! $this->config->setSettingByScope("organisationAdministrator", 1, 'System')) $settingsFail = true ;
				if (! $this->config->setSettingByScope("organisationDBA", 1, 'System')) $settingsFail = true ;
				if (! $this->config->setSettingByScope("organisationHR", 1, 'System')) $settingsFail = true ;
				if (! $this->config->setSettingByScope("organisationAdmissions", 1, 'System')) $settingsFail = true ;
				if (! $this->config->setSettingByScope("country", $_POST["country"], 'System')) $settingsFail = true ;
				if (! $this->config->setSettingByScope("gibboneduComOrganisationName", $_POST["gibboneduComOrganisationName"], 'System')) $settingsFail = true ;
				if (! $this->config->setSettingByScope("gibboneduComOrganisationKey", $_POST["gibboneduComOrganisationKey"], 'System')) $settingsFail = true ;
				if (! $this->config->setSettingByScope("timezone", $_POST["timezone"], 'System')) $settingsFail = true ;
				if (! $this->config->setSettingByScope("primaryAssessmentScale", $_POST["primaryAssessmentScale"], 'System')) $settingsFail = true ;
				if (! $this->config->setSettingByScope('installType', $_POST["installType"], 'System')) $settingsFail = true ;
				if (! $this->config->setSettingByScope('statsCollection', $_POST["statsCollection"], 'System')) $settingsFail = true ;

		
				if ($_POST["statsCollection"]=="Y") {
					$absolutePathProtocol="" ;
					$_POST["absolutePath"]="" ;
					if (substr($_POST["absoluteURL"],0,7)=="http://") {
						$absolutePathProtocol="http" ;
						$_POST["absolutePath"]=substr($_POST["absoluteURL"],7) ;
					}
					else if (substr($_POST["absoluteURL"],0,8)=="https://") {
						$absolutePathProtocol="https" ;
						$_POST["absolutePath"]=substr($_POST["absoluteURL"],8) ;
					}
					print "<iframe style='display: none; height: 10px; width: 10px' src='https://gibbonedu.org/services/tracker/tracker.php?absolutePathProtocol=" . urlencode($absolutePathProtocol) . "&absolutePath=" . urlencode($_POST["absolutePath"]) . "&organisationName=" . urlencode($_POST["organisationName"]) . "&type=" . urlencode($_POST["installType"]) . "&version=" . urlencode($this->config->get('version')) . "&country=" . $_POST["country"] . "&usersTotal=1&usersFull=1'></iframe>" ;
				}
				
				if (! $this->config->setSettingByScope('cuttingEdgeCode', $_POST["cuttingEdgeCode"], 'System')) $settingsFail = true ;

				if ($_POST["cuttingEdgeCode"]=="Y") {
					include GIBBON_ROOT . "installer/CHANGEDB.php" ;
					$sqlTokens=explode(";end", $sql[(count($sql))][1]) ;
					$versionMaxLinesMax=(count($sqlTokens)-1) ;
					$tokenCount=0 ;
					$this->config->setSettingByScope('cuttingEdgeCodeLine', $versionMaxLinesMax, 'System');
					foreach ($sqlTokens AS $sqlToken) {
						if ($tokenCount<=$versionMaxLinesMax) { //Decide whether this has been run or not
							if (trim($sqlToken)!="") {
								$result = $this->pdo->executeQuery(array(), $sqlToken);
								if (! $this->pdo->getQuerySuccess()) $partialFail = true;
							}
						}
						$tokenCount++ ;
					}
				}
					
				$result = $this->pdo->executeQuery(array(), 'SHOW TABLE STATUS WHERE `Engine` = "MyISAM"');
				while ($table = $result->fetchObject())
					$this->pdo->executeQuery(array(), 'ALTER TABLE `'.$table->Name.'` ENGINE=INNODB');
				$result = $this->pdo->executeQuery(array(), 'SHOW TABLE STATUS WHERE `Collation` != "utf8_unicode_ci"');
				while ($table = $result->fetchObject())
					$this->pdo->executeQuery(array(), 'ALTER TABLE `'.$table->Name.'` CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci');
	
				
				//Deal with request to receive welcome email by calling gibbonedu.org iframe
				if ($_POST["support"]) {
					$absolutePathProtocol="" ;
					$_POST["absolutePath"]="" ;
					if (substr($_POST["absoluteURL"],0,7)=="http://") {
						$absolutePathProtocol="http" ;
						$_POST["absolutePath"]=substr($_POST["absoluteURL"],7) ;
					}
					else if (substr($_POST["absoluteURL"],0,8)=="https://") {
						$absolutePathProtocol="https" ;
						$_POST["absolutePath"]=substr($_POST["absoluteURL"],8) ;
					}
					print "<iframe class='support' style='display: none; height: 10px; width: 10px' src='https://gibbonedu.org/services/support/supportRegistration.php?absolutePathProtocol=" . urlencode($absolutePathProtocol) . "&absolutePath=" . urlencode($_POST["absolutePath"]) . "&organisationName=" . urlencode($_POST["organisationName"]) . "&email=" . urlencode($_POST["email"]) . "&title=" . urlencode($_POST['title']) . "&surname=" . urlencode($_POST['surname']) . "&preferredName=" . urlencode($preferredName) . "'></iframe>" ;
				}
															
				if ($settingsFail) {
					$this->displayMessage(trans::__('Some settings did not save. The system may work, but you may need to remove everything and start again. Try and %1$sgo to your Gibbon homepage%2$s and login as user <u>admin</u> with password <u>gibbon</u>.', array("<a href='".$_POST["absoluteURL"]."'>", "</a>")) . "<br/><br/>"  . trans::__('It is also advisable to follow the %1$sPost-Install and Server Config instructions%2$s.', array("<a target='_blank' href='https://gibbonedu.org/support/administrators/installing-gibbon/'>", "</a>"))) ;
				}
				else {
					$this->displayMessage(trans::__('Congratulations, your installation is complete. Feel free to %1$sgo to your Gibbon homepage%2$s and login with the username and password you created.', array("<a href='".$_POST["absoluteURL"]."'>", "</a>")) . "<br/><br/>" . trans::__('It is also advisable to follow the %1$sPost-Install and Server Config instructions%2$s.', array("<a target='_blank' href='https://gibbonedu.org/support/administrators/installing-gibbon/'>", "</a>")), 'success') ;
				}
				$this->session->clear('install');
			}
		}
	}
}
?>