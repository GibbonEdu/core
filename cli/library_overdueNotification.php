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
$pdo = new Gibbon\sqlConnection();
$connection2 = $pdo->getConnection();

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
//if (php_sapi_name()!="cli") { 
//	print __($guid, "This script cannot be run from a browser, only via CLI.") . "\n\n" ;
//}
//else {
	//SCAN THROUGH ALL OVERDUE LOANS
	$today=date("Y-m-d") ;
	
	try {
		$data=array("today"=>$today); 
		$sql="SELECT gibbonLibraryItem.*, surname, preferredName, email FROM gibbonLibraryItem JOIN gibbonPerson ON (gibbonLibraryItem.gibbonPersonIDStatusResponsible=gibbonPerson.gibbonPersonID) WHERE gibbonLibraryItem.status='On Loan' AND borrowable='Y' AND returnExpected<:today AND gibbonPerson.status='Full' ORDER BY surname, preferredName" ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { }

	if ($result->rowCount()>0) {
		while ($row=$result->fetch()) { //For every student
			$notificationText=sprintf(__($guid, 'You have an overdue loan item that needs to be returned (%1$s).'), $row["name"]) ;
			setNotification($connection2, $guid, $row["gibbonPersonIDStatusResponsible"], $notificationText, "Library", "/index.php?q=/modules/Library/library_browse.php&gibbonLibraryItemID=" . $row["gibbonLibraryItemID"]) ;
		}
	}

//}

?>