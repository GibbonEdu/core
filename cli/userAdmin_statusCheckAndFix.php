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

require getcwd() . "/../config.php" ;
require getcwd() . "/../functions.php" ;
require getcwd() . "/../lib/PHPMailer/class.phpmailer.php";
						
//New PDO DB connection
if ($databaseServer=="localhost") {
	$databaseServer="127.0.0.1" ;
}
try {
  	$connection2=new PDO("mysql:host=$databaseServer;dbname=$databaseName;charset=utf8", $databaseUsername, $databasePassword);
	$connection2->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$connection2->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
}
catch(PDOException $e) { }

@session_start() ;

getSystemSettings($guid, $connection2) ;

setCurrentSchoolYear($guid, $connection2) ;

//Set up for i18n via gettext
if (isset($_SESSION[$guid]["i18n"]["code"])) {
	if ($_SESSION[$guid]["i18n"]["code"]!=NULL) {
		putenv("LC_ALL=" . $_SESSION[$guid]["i18n"]["code"]);
		setlocale(LC_ALL, $_SESSION[$guid]["i18n"]["code"]);
		bindtextdomain("gibbon", getcwd() . "/../i18n");
		textdomain("gibbon");
	}
}

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]["timezone"]);

//Check for CLI, so this cannot be run through browser
if (php_sapi_name()!="cli") { 
	print _("This script cannot be run from a browser, only via CLI.") . "\n\n" ;
}
else {
	$count=0 ;	
	
	//Scan through every user to correct own status
	try {
		$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
		$sql="SELECT gibbonPersonID, status, dateEnd, dateStart, gibbonRoleIDAll FROM gibbonPerson ORDER BY gibbonPersonID" ;
		$result=$connection2->prepare($sql);
		$result->execute($data); 
	}
	catch(PDOException $e) { }
	
	while ($row=$result->fetch()) {
		//Check for status=='Expected' when met or exceeded start date and set to 'Full'
		if ($row["dateStart"]!="" AND date("Y-m-d")>=$row["dateStart"] AND $row["status"]=="Expected") {
			try {
				$dataUpdate=array("gibbonPersonID"=>$row["gibbonPersonID"]); 
				$sqlUpdate="UPDATE gibbonPerson SET status='Full' WHERE gibbonPersonID=:gibbonPersonID" ;
				$resultUpdate=$connection2->prepare($sqlUpdate);
				$resultUpdate->execute($dataUpdate); 
			}
			catch(PDOException $e) { }
			$count++ ;
		}
		
		//Check for status=='Full' when end date exceeded, and set to 'Left'
		if ($row["dateEnd"]!="" AND date("Y-m-d")>$row["dateEnd"] AND $row["status"]=="Full") {
			try {
				$dataUpdate=array("gibbonPersonID"=>$row["gibbonPersonID"]); 
				$sqlUpdate="UPDATE gibbonPerson SET status='Left' WHERE gibbonPersonID=:gibbonPersonID" ;
				$resultUpdate=$connection2->prepare($sqlUpdate);
				$resultUpdate->execute($dataUpdate); 
			}
			catch(PDOException $e) { }
			$count++ ;
		}
	}
	//Scan through every user who is child in a family to correct parent status
	try {
		$data=array(); 
		$sql="SELECT gibbonFamilyID, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonFamilyChild ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE status='Left' ORDER BY gibbonPersonID" ;
		$result=$connection2->prepare($sql);
		$result->execute($data); 
	}
	catch(PDOException $e) { }
	
	while ($row=$result->fetch()) {
		//Check to see if all siblings are left
		try {
			$dataCheck1=array("gibbonFamilyID"=>$row["gibbonFamilyID"]); 
			$sqlCheck1="SELECT gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonFamilyChild ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonFamilyID=:gibbonFamilyID AND NOT status='Left' ORDER BY gibbonPersonID" ;
			$resultCheck1=$connection2->prepare($sqlCheck1);
			$resultCheck1->execute($dataCheck1); 
		}
		catch(PDOException $e) { }
		
		if ($resultCheck1->rowCount()==0) { //There are no active siblings, so let's check parents to see if we can set anyone to left
			try {
				$dataCheck2=array("gibbonFamilyID"=>$row["gibbonFamilyID"]); 
				$sqlCheck2="SELECT gibbonPerson.gibbonPersonID, status, gibbonRoleIDAll FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonFamilyID=:gibbonFamilyID AND NOT status='Left' ORDER BY gibbonPersonID" ;
				$resultCheck2=$connection2->prepare($sqlCheck2);
				$resultCheck2->execute($dataCheck2); 
			}
			catch(PDOException $e) { }
			
			while ($rowCheck2=$resultCheck2->fetch()) {
				//Check to see if parent has any non-staff roles. If not, mark as 'Left'
				$nonParentRole=FALSE ;
				$roles=explode(",", $rowCheck2["gibbonRoleIDAll"]) ;
				foreach ($roles AS $role) {
					if (getRoleCategory($role, $connection2)!="Parent") {
						$nonParentRole=TRUE ;
					}
				}
				
				if ($nonParentRole==FALSE) {
					//Update status to 'Left'
					try {
						$dataUpdate=array("gibbonPersonID"=>$rowCheck2["gibbonPersonID"]); 
						$sqlUpdate="UPDATE gibbonPerson SET status='Left' WHERE gibbonPersonID=:gibbonPersonID" ;
						$resultUpdate=$connection2->prepare($sqlUpdate);
						$resultUpdate->execute($dataUpdate); 
					}
					catch(PDOException $e) { }
					$count++ ;
				}
			}
		}
	}
	
	//Send confirmation email to admin
	$body=_("Users Updated") . ": " . $count . "<br/><br/>" ;	
	$body.="<p style='font-style: italic;'>" . sprintf(_('Email sent via %1$s at %2$s.'), $_SESSION[$guid]["systemName"], $_SESSION[$guid]["organisationName"]) ."</p>" ;
	$bodyPlain=preg_replace('#<br\s*/?>#i', "\n", $body) ;
	$bodyPlain=str_replace("</p>", "\n\n", $bodyPlain) ;
	$bodyPlain=str_replace("</div>", "\n\n", $bodyPlain) ;
	$bodyPlain=preg_replace("#\<a.+href\=[\"|\'](.+)[\"|\'].*\>.*\<\/a\>#U","$1",$bodyPlain); 
	$bodyPlain=strip_tags($bodyPlain, '<a>');

	$mail=new PHPMailer;
	$mail->AddAddress($_SESSION[$guid]["organisationAdministratorEmail"], $_SESSION[$guid]["organisationAdministratorName"]);
	$mail->SetFrom($_SESSION[$guid]["organisationEmail"], $_SESSION[$guid]["organisationName"]);
	$mail->CharSet="UTF-8"; 
	$mail->Encoding="base64" ;
	$mail->IsHTML(true);                            
	$mail->Subject=_('User Admin CLI Report') ;
	$mail->Body=$body ;
	$mail->AltBody=$bodyPlain ;
	$mail->Send() ;
}

?>