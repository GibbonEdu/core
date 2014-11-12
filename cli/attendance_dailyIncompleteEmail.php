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
	$currentDate=date("Y-m-d") ;
	
	if (isSchoolOpen($guid, $currentDate, $connection2, TRUE)==FALSE) {
		print _('School is not open on the specified day.') ;
	}
	else {
		$emails="" ;
		$report="" ;
		$reportInner="" ;
	
		//Produce array of attendance data
		try {
			$data=array("date"=>$currentDate); 
			$sql="SELECT gibbonRollGroupID FROM gibbonAttendanceLogRollGroup WHERE date=:date" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			$report=_("Your request failed due to a database error.") ;
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
		}
	
		$log=array() ;
		while ($row=$result->fetch()) {
			$log[$row["gibbonRollGroupID"]]=TRUE ;
		}

		try {
			$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"] ); 
			$sql="SELECT gibbonRollGroupID, name, gibbonPersonIDTutor, gibbonPersonIDTutor2, gibbonPersonIDTutor3, tutor1.email AS tutor1Email, tutor2.email AS tutor2Email, tutor3.email AS tutor3Email FROM gibbonRollGroup LEFT JOIN gibbonPerson AS tutor1 ON (gibbonRollGroup.gibbonPersonIDTutor=tutor1.gibbonPersonID) LEFT JOIN gibbonPerson AS tutor2 ON (gibbonRollGroup.gibbonPersonIDTutor2=tutor2.gibbonPersonID) LEFT JOIN gibbonPerson AS tutor3 ON (gibbonRollGroup.gibbonPersonIDTutor3=tutor3.gibbonPersonID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY name" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			$report=_("Your request failed due to a database error.") ;
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
		}
	
		if ($result->rowCount()<1) {
			print "<div class='error'>" ;
				print _("There are no records to display.") ;
			print "</div>" ;
			$report=_("There are no records to display.") ;
		}
		else {
			$count=0 ;
			while ($row=$result->fetch()) {
				if (isset($log[$row["gibbonRollGroupID"]])==FALSE) {
					$count++ ;
					$reportInner.=$row["name"] ."<br/>" ;
					if ($row["tutor1Email"]!="") {
						$emails.=$row["tutor1Email"] ."," ;
					}
					if ($row["tutor2Email"]!="") {
						$emails.=$row["tutor2Email"] ."," ;
					}
					if ($row["tutor3Email"]!="") {
						$emails.=$row["tutor3Email"] ."," ;
					}
				}
			}
		}
		if (isset($count)) {
			if ($count==0) {
				$report=sorintf(_('All form groups have been registered today (%1$s).'), dateConvertBack($guid, $currentDate)) ;
			}
			else {
				$report=sprintf(_('%1$s form groups have not been registered today  (%2$s).'), $count, dateConvertBack($guid, $currentDate)) . "<br/><br/>" . $reportInner ;
			}
		}
	
		//Send confirmation email to admin and non-completing tutors
		$emails=explode(",",substr($emails,0,-1)) ;
		$emails=array_unique($emails) ;
		natcasesort($emails) ;
	
		$body=$report . "<br/><br/>" ;	
		$body.="<p style='font-style: italic;'>" . sprintf(_('Email sent via %1$s at %2$s.'), $_SESSION[$guid]["systemName"], $_SESSION[$guid]["organisationName"]) ."</p>" ;
		$bodyPlain=preg_replace('#<br\s*/?>#i', "\n", $body) ;
		$bodyPlain=str_replace("</p>", "\n\n", $bodyPlain) ;
		$bodyPlain=str_replace("</div>", "\n\n", $bodyPlain) ;
		$bodyPlain=preg_replace("#\<a.+href\=[\"|\'](.+)[\"|\'].*\>.*\<\/a\>#U","$1",$bodyPlain); 
		$bodyPlain=strip_tags($bodyPlain, '<a>');

		$mail=new PHPMailer;
		$mail->AddAddress($_SESSION[$guid]["organisationAdministratorEmail"], $_SESSION[$guid]["organisationAdministratorName"]);
		$mail->SetFrom($_SESSION[$guid]["organisationEmail"], $_SESSION[$guid]["organisationName"]);
		foreach ($emails AS $address) {
			$mail->AddBCC($address);
		}
		$mail->CharSet="UTF-8"; 
		$mail->Encoding="base64" ;
		$mail->IsHTML(true);                            
		$mail->Subject=_('Incomplete Attendance Report') ;
		$mail->Body=$body ;
		$mail->AltBody=$bodyPlain ;
		$mail->Send() ;
	}
}

?>