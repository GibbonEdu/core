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

//Checks whether or not a space is free over a given period of time, returning true or false accordingly.
function isSpaceFree($guid, $connection2, $gibbonSpaceID, $date, $timeStart, $timeEnd) {
	$return=TRUE ;
	
	//Check if school is open
	if (isSchoolOpen($guid, $date, $connection2)==FALSE) {
		$return=FALSE ;
	}
	else {
		
		//Check timetable inc classes moved out
		$ttClear=FALSE ;
		try {
			$dataSpace=array("gibbonSpaceID"=>$gibbonSpaceID, "date"=>$date, "timeStart1"=>$timeStart, "timeStart2"=>$timeStart, "timeStart3"=>$timeStart, "timeEnd1"=>$timeEnd, "timeStart4"=>$timeStart, "timeEnd2"=>$timeEnd); 
			$sqlSpace="SELECT gibbonTTDayRowClass.gibbonSpaceID, gibbonTTDayDate.date, timeStart, timeEnd, gibbonTTSpaceChangeID FROM gibbonTTDayRowClass JOIN gibbonTTColumnRow ON (gibbonTTDayRowClass.gibbonTTColumnRowID=gibbonTTColumnRow.gibbonTTColumnRowID) JOIN gibbonTTDay ON (gibbonTTDayRowClass.gibbonTTDayID=gibbonTTDay.gibbonTTDayID) JOIN gibbonTTDayDate ON (gibbonTTDayDate.gibbonTTDayID=gibbonTTDay.gibbonTTDayID) LEFT JOIN gibbonTTSpaceChange ON (gibbonTTSpaceChange.gibbonTTDayRowClassID=gibbonTTDayRowClass.gibbonTTDayRowClassID AND gibbonTTSpaceChange.date=gibbonTTDayDate.date) WHERE gibbonTTDayRowClass.gibbonSpaceID=:gibbonSpaceID AND gibbonTTDayDate.date=:date AND ((timeStart<=:timeStart1 AND timeEnd>:timeStart2) OR (timeStart>=:timeStart3 AND timeEnd<:timeEnd1) OR (timeStart>=:timeStart4 AND timeStart<:timeEnd2))" ;
			$resultSpace=$connection2->prepare($sqlSpace);
			$resultSpace->execute($dataSpace);
		}
		catch(PDOException $e) { $return=FALSE ; }
		if ($resultSpace->rowCount()<1) {
			$ttClear=TRUE ;
		}
		else {
			$ttClashFixed=TRUE ;
			
			while ($rowSpace=$resultSpace->fetch()) {
				if ($rowSpace["gibbonTTSpaceChangeID"]=="") {
					$ttClashFixed=FALSE ;
				}
			}
			if ($ttClashFixed==TRUE) {
				$ttClear=TRUE ;
			}
		}
		
		if ($ttClear==FALSE) {
			$return=FALSE ;
		}
		else {
			//Check room changes moving in
			try {
				$dataSpace=array("gibbonSpaceID"=>$gibbonSpaceID, "date1"=>$date, "date2"=>$date, "timeStart1"=>$timeStart, "timeStart2"=>$timeStart, "timeStart3"=>$timeStart, "timeEnd1"=>$timeEnd, "timeStart4"=>$timeStart, "timeEnd2"=>$timeEnd); 
				$sqlSpace="SELECT * FROM gibbonTTSpaceChange JOIN gibbonTTDayRowClass ON (gibbonTTSpaceChange.gibbonTTDayRowClassID=gibbonTTDayRowClass.gibbonTTDayRowClassID) JOIN gibbonTTColumnRow ON (gibbonTTDayRowClass.gibbonTTColumnRowID=gibbonTTColumnRow.gibbonTTColumnRowID) JOIN gibbonTTDay ON (gibbonTTDayRowClass.gibbonTTDayID=gibbonTTDay.gibbonTTDayID) JOIN gibbonTTDayDate ON (gibbonTTDayDate.gibbonTTDayID=gibbonTTDay.gibbonTTDayID) WHERE gibbonTTSpaceChange.gibbonSpaceID=:gibbonSpaceID AND gibbonTTSpaceChange.date=:date1 AND gibbonTTDayDate.date=:date2 AND ((timeStart<=:timeStart1 AND timeEnd>:timeStart2) OR (timeStart>=:timeStart3 AND timeEnd<:timeEnd1) OR (timeStart>=:timeStart4 AND timeStart<:timeEnd2))" ;
				$resultSpace=$connection2->prepare($sqlSpace);
				$resultSpace->execute($dataSpace);
			}
			catch(PDOException $e) { $return=FALSE ; }
			
			if ($resultSpace->rowCount()>0) {
				$return=FALSE ;
			}
			else {
				//Check room bookings
				try {
					$dataSpace=array("gibbonSpaceID"=>$gibbonSpaceID, "date"=>$date, "timeStart1"=>$timeStart, "timeStart2"=>$timeStart, "timeStart3"=>$timeStart, "timeEnd1"=>$timeEnd, "timeStart4"=>$timeStart, "timeEnd2"=>$timeEnd); 
					$sqlSpace="SELECT * FROM gibbonTTSpaceBooking WHERE gibbonSpaceID=:gibbonSpaceID AND date=:date AND ((timeStart<=:timeStart1 AND timeEnd>:timeStart2) OR (timeStart>=:timeStart3 AND timeEnd<:timeEnd1) OR (timeStart>=:timeStart4 AND timeStart<:timeEnd2))" ;
					$resultSpace=$connection2->prepare($sqlSpace);
					$resultSpace->execute($dataSpace);
				}
				catch(PDOException $e) { $return=FALSE ; }
				if ($resultSpace->rowCount()>0) {
					$return=FALSE ;
				}
			}
		}
	}
	
	return $return ;
}

//Returns space bookings for the specified user for the 7 days on/after $startDayStamp, or for all users for the 7 days on/after $startDayStamp if no user specified
function getSpaceBookingEvents($guid, $connection2, $startDayStamp, $gibbonPersonID="") {
	$return=FALSE ;
	
	try {
		if ($gibbonPersonID!="") {
			$dataSpaceBooking=array("gibbonPersonID"=>$gibbonPersonID); 
			$sqlSpaceBooking="SELECT gibbonTTSpaceBooking.*, name, title, surname, preferredName FROM gibbonTTSpaceBooking JOIN gibbonSpace ON (gibbonTTSpaceBooking.gibbonSpaceID=gibbonSpace.gibbonSpaceID) JOIN gibbonPerson ON (gibbonTTSpaceBooking.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonTTSpaceBooking.gibbonPersonID=:gibbonPersonID AND date>='" . date("Y-m-d", $startDayStamp) . "' AND  date<='" . date("Y-m-d", ($startDayStamp+(7*24*60*60))) . "' ORDER BY date, timeStart, name" ;
		} 
		else {
			$dataSpaceBooking=array(); 
			$sqlSpaceBooking="SELECT gibbonTTSpaceBooking.*, name, title, surname, preferredName FROM gibbonTTSpaceBooking JOIN gibbonSpace ON (gibbonTTSpaceBooking.gibbonSpaceID=gibbonSpace.gibbonSpaceID) JOIN gibbonPerson ON (gibbonTTSpaceBooking.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE date>='" . date("Y-m-d", $startDayStamp) . "' AND  date<='" . date("Y-m-d", ($startDayStamp+(7*24*60*60))) . "' ORDER BY date, timeStart, name" ;
		}
		$resultSpaceBooking=$connection2->prepare($sqlSpaceBooking);
		$resultSpaceBooking->execute($dataSpaceBooking);
	}
	catch(PDOException $e) { }
	if ($resultSpaceBooking->rowCount()>0) {
		$return=array() ;
		$count=0 ;
		while ($rowSpaceBooking=$resultSpaceBooking->fetch()) {
			$return[$count][0]=$rowSpaceBooking["gibbonTTSpaceBookingID"] ;
			$return[$count][1]=$rowSpaceBooking["name"] ;
			$return[$count][2]=$rowSpaceBooking["gibbonPersonID"] ;
			$return[$count][3]=$rowSpaceBooking["date"] ;
			$return[$count][4]=$rowSpaceBooking["timeStart"] ;
			$return[$count][5]=$rowSpaceBooking["timeEnd"] ;
			$return[$count][6]=formatName($rowSpaceBooking["title"], $rowSpaceBooking["preferredName"], $rowSpaceBooking["surname"], "Staff") ;
			$count++ ;
		}
	}
	
	return $return ;
}

//Returns space bookings for the specified space for the 7 days on/after $startDayStamp
function getSpaceBookingEventsSpace($guid, $connection2, $startDayStamp, $gibbonSpaceID) {
	$return=FALSE ;
	
	try {
		$dataSpaceBooking=array("gibbonSpaceID"=>$gibbonSpaceID); 
		$sqlSpaceBooking="SELECT gibbonTTSpaceBooking.*, name, title, surname, preferredName FROM gibbonTTSpaceBooking JOIN gibbonSpace ON (gibbonTTSpaceBooking.gibbonSpaceID=gibbonSpace.gibbonSpaceID) JOIN gibbonPerson ON (gibbonTTSpaceBooking.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonTTSpaceBooking.gibbonSpaceID=:gibbonSpaceID AND date>='" . date("Y-m-d", $startDayStamp) . "' AND  date<='" . date("Y-m-d", ($startDayStamp+(7*24*60*60))) . "' ORDER BY date, timeStart, name" ;
		$resultSpaceBooking=$connection2->prepare($sqlSpaceBooking);
		$resultSpaceBooking->execute($dataSpaceBooking);
	}
	catch(PDOException $e) { }
	if ($resultSpaceBooking->rowCount()>0) {
		$return=array() ;
		$count=0 ;
		while ($rowSpaceBooking=$resultSpaceBooking->fetch()) {
			$return[$count][0]=$rowSpaceBooking["gibbonTTSpaceBookingID"] ;
			$return[$count][1]=$rowSpaceBooking["name"] ;
			$return[$count][2]=$rowSpaceBooking["gibbonPersonID"] ;
			$return[$count][3]=$rowSpaceBooking["date"] ;
			$return[$count][4]=$rowSpaceBooking["timeStart"] ;
			$return[$count][5]=$rowSpaceBooking["timeEnd"] ;
			$return[$count][6]=formatName($rowSpaceBooking["title"], $rowSpaceBooking["preferredName"], $rowSpaceBooking["surname"], "Staff") ;
			$count++ ;
		}
	}
	
	return $return ;
}

//Returns events from a Google Calendar XML field, between the time and date specified
function getCalendarEvents($connection2, $guid, $xml, $startDayStamp, $endDayStamp) {
	$googleOAuth=getSettingByScope($connection2, "System", "googleOAuth") ;
	if ($googleOAuth=="Y" AND isset($_SESSION[$guid]['googleAPIAccessToken'])) {
		$eventsSchool=array() ;
		$start=date("Y-m-d\TH:i:s", strtotime(date("Y-m-d", $startDayStamp))) ;
		$end=date("Y-m-d\TH:i:s", (strtotime(date("Y-m-d", $endDayStamp))+86399)) ;
	
		require_once $_SESSION[$guid]["absolutePath"] . '/lib/google/google-api-php-client/autoload.php';
		
		$client=new Google_Client();
		$expires=(json_decode($_SESSION[$guid]['googleAPIAccessToken'])->created) + 3600 ;
		if ($expires-time()>600) { //Not yet expired, and not expiring imminently, so no need to refresh the token, just use it
			$client->setAccessToken($_SESSION[$guid]['googleAPIAccessToken']);
		}
		else { //Need to refresh the token
			//Get API details
			$googleClientName=getSettingByScope($connection2, "System", "googleClientName" ) ; 
			$googleClientID=getSettingByScope($connection2, "System", "googleClientID" ) ; 
			$googleClientSecret=getSettingByScope($connection2, "System", "googleClientSecret" ) ; 
			$googleRedirectUri=getSettingByScope($connection2, "System", "googleRedirectUri" ) ; 
			$googleDeveloperKey=getSettingByScope($connection2, "System", "googleDeveloperKey" ) ;
			
			//Re-establish $client
			$client->setApplicationName($googleClientName); // Set your applicatio name
			$client->setScopes(array('https://www.googleapis.com/auth/userinfo.email', 'https://www.googleapis.com/auth/plus.me', 'https://www.googleapis.com/auth/calendar')); // set scope during user login
			$client->setClientId($googleClientID); // paste the client id which you get from google API Console
			$client->setClientSecret($googleClientSecret); // set the client secret
			$client->setRedirectUri($googleRedirectUri); // paste the redirect URI where you given in APi Console. You will get the Access Token here during login success
			$client->setDeveloperKey($googleDeveloperKey); // Developer key
			$client->setAccessType('offline');	
			$client->refreshToken($_SESSION[$guid]['googleAPIRefreshToken']);
			$_SESSION[$guid]['googleAPIAccessToken']=$client->getAccessToken();
		}
		
		$service=new Google_Service_Calendar($client);
		$optParams = array('timeMin'=>$start . "+00:00", 'timeMax'=>$end . "+00:00", "singleEvents"=>TRUE);
		$calendarListEntry=$service->events->listEvents($xml, $optParams);
		
		$count=0 ;
		foreach ($calendarListEntry as $entry) { 
			$multiDay=FALSE ;
			if (substr($entry["start"]["dateTime"], 0, 10)!=substr($entry["end"]["dateTime"], 0, 10)) {
				$multiDay=TRUE ;
			}
			if ($entry["start"]["dateTime"]=="") {
				if ((strtotime($entry["end"]["date"])-strtotime($entry["start"]["date"]))/(60*60*24)>1) {
					$multiDay=TRUE ;
				}
			}
			
			if ($multiDay) { //This event spans multiple days
				if ($entry["start"]["date"]!=$entry["start"]["end"]) {
					$days=(strtotime($entry["end"]["date"])-strtotime($entry["start"]["date"]))/(60*60*24) ;
				}
				else if (substr($entry["start"]["dateTime"], 0, 10)!=substr($entry["end"]["dateTime"], 0, 10)) {
					$days=(strtotime(substr($entry["end"]["dateTime"], 0, 10))-strtotime(substr($entry["start"]["dateTime"], 0, 10)))/(60*60*24) ;
					$days++ ; //A hack for events that span multiple days with times set
				}
				for ($i=0; $i<$days; $i++) {
					//WHAT
					$eventsSchool[$count][0]=$entry["summary"]; 
			
					//WHEN - treat events that span multiple days, but have times set, the same as those without time set
					$eventsSchool[$count][1]="All Day" ;
					$eventsSchool[$count][2]=strtotime($entry["start"]["date"])+($i*60*60*24) ;
					$eventsSchool[$count][3]=NULL ;
						
					//WHERE
					$eventsSchool[$count][4]=$entry["location"];
		
					//LINK
					$eventsSchool[$count][5]=$entry["htmlLink"];
					
					$count++ ;
				}
			}
			else {  //This event falls on a single day
				//WHAT
				$eventsSchool[$count][0]=$entry["summary"]; 
			
				//WHEN
				if ($entry["start"]["dateTime"]!="") { //Part of day
					$eventsSchool[$count][1]="Specified Time" ;
					$eventsSchool[$count][2]=strtotime(substr($entry["start"]["dateTime"], 0, 10) . " " . substr($entry["start"]["dateTime"], 11, 8)) ;
					$eventsSchool[$count][3]=strtotime(substr($entry["end"]["dateTime"], 0, 10) . " " . substr($entry["end"]["dateTime"], 11, 8)) ;
				}
				else { //All day
					$eventsSchool[$count][1]="All Day" ;
					$eventsSchool[$count][2]=strtotime($entry["start"]["date"]) ;
					$eventsSchool[$count][3]=NULL ;
				}
				//WHERE
				$eventsSchool[$count][4]=$entry["location"];
		
				//LINK
				$eventsSchool[$count][5]=$entry["htmlLink"];
				
				$count++ ;
			}
		}
	}
	else {
		$eventsSchool=FALSE ;
	}
	
	return $eventsSchool ;
}


//TIMETABLE FOR INDIVIUDAL
function renderTT($guid, $connection2, $gibbonPersonID, $gibbonTTID, $title="", $startDayStamp="", $q="", $params="", $narrow=FALSE) {
	$zCount=0 ;
	$output="" ;
	$proceed=FALSE ;
	
	if (isActionAccessible($guid, $connection2, "/modules/Timetable/tt.php", "View Timetable by Person_allYears") ) {
		$proceed=TRUE ;
	}
	else {
		if ($_SESSION[$guid]["gibbonSchoolYearIDCurrent"]==$_SESSION[$guid]["gibbonSchoolYearID"]) {
			$proceed=TRUE ;
		}
	}
	
	if ($proceed==FALSE) {
		$output.="<div class='error'>" . _("You do not have permission to access this timetable at this time.") . "</div>" ; 
	}
	else {
		$self=FALSE ;
		if ($gibbonPersonID==$_SESSION[$guid]["gibbonPersonID"]) {
			$self=TRUE ;
			//Update display choices
			if ($_SESSION[$guid]["viewCalendarSchool"]!=FALSE AND $_SESSION[$guid]["viewCalendarPersonal"]!=FALSE AND $_SESSION[$guid]["viewCalendarSpaceBooking"]!=FALSE) {
				try {
					$dataDisplay=array("viewCalendarSchool"=>$_SESSION[$guid]["viewCalendarSchool"], "viewCalendarPersonal"=>$_SESSION[$guid]["viewCalendarPersonal"], "viewCalendarSpaceBooking"=>$_SESSION[$guid]["viewCalendarSpaceBooking"], "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
					$sqlDisplay="UPDATE gibbonPerson SET viewCalendarSchool=:viewCalendarSchool, viewCalendarPersonal=:viewCalendarPersonal, viewCalendarSpaceBooking=:viewCalendarSpaceBooking WHERE gibbonPersonID=:gibbonPersonID" ;
					$resultDisplay=$connection2->prepare($sqlDisplay);
					$resultDisplay->execute($dataDisplay);
				}
				catch(PDOException $e) { 
					$output.="<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
			}
		}
	
		$blank=TRUE ;
		if ($startDayStamp=="") {
			$startDayStamp=time() ;
		}
		
		//Find out which timetables I am involved in this year
		try {
			$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonPersonID"=>$gibbonPersonID); 
			$sql="SELECT DISTINCT gibbonTT.gibbonTTID, gibbonTT.name FROM gibbonTT JOIN gibbonTTDay ON (gibbonTT.gibbonTTID=gibbonTTDay.gibbonTTID) JOIN gibbonTTDayRowClass ON (gibbonTTDayRowClass.gibbonTTDayID=gibbonTTDay.gibbonTTDayID) JOIN gibbonCourseClass ON (gibbonTTDayRowClass.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) WHERE gibbonPersonID=:gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID AND active='Y' " ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			$output.="<div class='error'>" . $e->getMessage() . "</div>" ; 
		}
	
		//If I am not involved in any timetables display all within the year 
		if ($result->rowCount()==0) {
			try {
				$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
				$sql="SELECT gibbonTT.gibbonTTID, gibbonTT.name FROM gibbonTT WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND active='Y' " ;
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				$output.="<div class='error'>" . $e->getMessage() . "</div>" ; 
			}
		}
	
		//link to other TTs
		if ($result->rowcount()>1) {
			$output.="<table class='noIntBorder' cellspacing='0' style='width: 100%'>" ;
				$output.="<tr>" ; 
					$output.="<td>" ; 
						$output.="<span style='font-size: 115%; font-weight: bold'>" . _('Timetable Chooser') . "</span>: " ;
						while ($row=$result->fetch()) {
							$output.="<form method='post' action='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=$q&gibbonTTID=" . $row["gibbonTTID"] . "$params'>" ;
								$output.="<input name='ttDate' value='" . date($_SESSION[$guid]["i18n"]["dateFormatPHP"], $startDayStamp) . "' type='hidden'>" ;
								$output.="<input name='schoolCalendar' value='" . $_SESSION[$guid]["viewCalendarSchool"] . "' type='hidden'>" ;
								$output.="<input name='personalCalendar' value='" . $_SESSION[$guid]["viewCalendarPersonal"] . "' type='hidden'>" ;
								$output.="<input name='spaceBookingCalendar' value='" . $_SESSION[$guid]["viewCalendarSpaceBooking"] . "' type='hidden'>" ;
								$output.="<input name='fromTT' value='Y' type='hidden'>" ;
								$output.="<input class='buttonLink' style='min-width: 30px; margin-top: 0px; float: left' type='submit' value='" . $row["name"] . "'>" ;
							$output.="</form>" ;
						}
						try {
							$result=$connection2->prepare($sql);
							$result->execute($data);
						}
						catch(PDOException $e) { 
							$output.="<div class='error'>" . $e->getMessage() . "</div>" ; 
						}
					$output.="</td>" ; 
				$output.="</tr>" ; 
			$output.="</table>" ;
		
			if ($gibbonTTID!="") {
				$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonTTID"=>$gibbonTTID); 
				$sql="SELECT DISTINCT gibbonTT.gibbonTTID, gibbonTT.name FROM gibbonTT JOIN gibbonTTDay ON (gibbonTT.gibbonTTID=gibbonTTDay.gibbonTTID) JOIN gibbonTTDayRowClass ON (gibbonTTDayRowClass.gibbonTTDayID=gibbonTTDay.gibbonTTDayID) JOIN gibbonCourseClass ON (gibbonTTDayRowClass.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) WHERE gibbonPersonID=$gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonTT.gibbonTTID=:gibbonTTID" ;
			}
			try {
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				$output.="<div class='error'>" . $e->getMessage() . "</div>" ; 
			}
		}

	
		//Display first TT
		if ($result->rowCount()>0) {
			$row=$result->fetch() ;
		
			if ($title!=FALSE) {
				$output.="<h2>" . $row["name"] . "</h2>" ;
			}
			$output.="<table cellspacing='0' class='noIntBorder' style='width: 100%; margin: 10px 0 10px 0'>" ;	
				$output.="<tr>" ;
					$output.="<td style='vertical-align: top'>" ; 
						$output.="<form method='post' action='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=$q&gibbonTTID=" . $row["gibbonTTID"] . "$params'>" ;
							$output.="<input name='ttDate' value='" . date($_SESSION[$guid]["i18n"]["dateFormatPHP"], ($startDayStamp-(7*24*60*60))) . "' type='hidden'>" ;
							$output.="<input name='schoolCalendar' value='" . $_SESSION[$guid]["viewCalendarSchool"] . "' type='hidden'>" ;
							$output.="<input name='personalCalendar' value='" . $_SESSION[$guid]["viewCalendarPersonal"] . "' type='hidden'>" ;
							$output.="<input name='spaceBookingCalendar' value='" . $_SESSION[$guid]["viewCalendarSpaceBooking"] . "' type='hidden'>" ;
							$output.="<input name='fromTT' value='Y' type='hidden'>" ;
							$output.="<input class='buttonLink' style='min-width: 30px; margin-top: 0px; float: left' type='submit' value='" . _('Last Week') . "'>" ;
						$output.="</form>" ;
						$output.="<form method='post' action='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=$q&gibbonTTID=" . $row["gibbonTTID"] . "$params'>" ;
							$output.="<input name='ttDate' value='" . date($_SESSION[$guid]["i18n"]["dateFormatPHP"], ($startDayStamp+(7*24*60*60))) . "' type='hidden'>" ;
							$output.="<input name='schoolCalendar' value='" . $_SESSION[$guid]["viewCalendarSchool"] . "' type='hidden'>" ;
							$output.="<input name='personalCalendar' value='" . $_SESSION[$guid]["viewCalendarPersonal"] . "' type='hidden'>" ;
							$output.="<input name='spaceBookingCalendar' value='" . $_SESSION[$guid]["viewCalendarSpaceBooking"] . "' type='hidden'>" ;
							$output.="<input name='fromTT' value='Y' type='hidden'>" ;
							$output.="<input class='buttonLink' style='min-width: 30px; margin-top: 0px; float: left' type='submit' value='" . _('Next Week') . "'>" ;
						$output.="</form>" ;
					$output.="</td>" ; 
					$output.="<td style='vertical-align: top; text-align: right'>" ;
						$output.="<form method='post' action='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=$q&gibbonTTID=" . $row["gibbonTTID"] . "$params'>" ; 
							$output.="<input name='ttDate' id='ttDate' maxlength=10 value='" . date($_SESSION[$guid]["i18n"]["dateFormatPHP"], $startDayStamp) . "' type='text' style='height: 22px; width:100px; margin-right: 0px; float: none'> " ;
							$output.="<script type=\"text/javascript\">" ;
								$output.="var ttDate=new LiveValidation('ttDate');" ;
								$output.="ttDate.add( Validate.Format, {pattern:" ; if ($_SESSION[$guid]["i18n"]["dateFormatRegEx"]=="") {  $output.="/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i" ; } else { $output.=$_SESSION[$guid]["i18n"]["dateFormatRegEx"] ; } $output.=", failureMessage: \"Use " ; if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { $output.="dd/mm/yyyy" ; } else { $output.=$_SESSION[$guid]["i18n"]["dateFormat"] ; } $output.=".\" } );" ;
								$output.="ttDate.add(Validate.Presence);" ;
							 $output.="</script>" ;
							 $output.="<script type=\"text/javascript\">" ;
								$output.="$(function() {" ;
									$output.="$(\"#ttDate\").datepicker();" ;
								$output.="});" ;
							$output.="</script>" ;
							$output.="<input style='margin-top: 0px; margin-right: -2px' type='submit' value='" . _('Go') . "'>" ;
							$output.="<input name='schoolCalendar' value='" . $_SESSION[$guid]["viewCalendarSchool"] . "' type='hidden'>" ;
							$output.="<input name='personalCalendar' value='" . $_SESSION[$guid]["viewCalendarPersonal"] . "' type='hidden'>" ;
							$output.="<input name='spaceBookingCalendar' value='" . $_SESSION[$guid]["viewCalendarSpaceBooking"] . "' type='hidden'>" ;
							$output.="<input name='fromTT' value='Y' type='hidden'>" ;	
						$output.="</form>" ;
					$output.="</td>" ;
				$output.="</tr>" ;
			$output.="</table>" ;

			//Count back to first Monday before first day
			while (date("D",$startDayStamp)!="Mon") {
				$startDayStamp=$startDayStamp-86400 ;
			}
					
			//Check which days are school days
			$daysInWeek=0;
			$days=array() ;
			$timeStart="" ;
			$timeEnd="" ;
			$days["Mon"]="N" ;
			$days["Tue"]="N" ;
			$days["Wed"]="N" ;
			$days["Thu"]="N" ;
			$days["Fri"]="N" ;
			$days["Sat"]="N" ;
			$days["Sun"]="N" ;
			try {
				$dataDays=array(); 
				$sqlDays="SELECT * FROM gibbonDaysOfWeek WHERE schoolDay='Y'" ;
				$resultDays=$connection2->prepare($sqlDays);
				$resultDays->execute($dataDays);
			}
			catch(PDOException $e) { 
				$output.="<div class='error'>" . $e->getMessage() . "</div>" ; 
			}
			while ($rowDays=$resultDays->fetch()) {
				//Max diff time for week based on days of week
				if ($timeStart=="") {
					$timeStart=$rowDays["schoolStart"] ;
				}
				if ($rowDays["schoolStart"]<$timeStart) {
					$timeStart=$rowDays["schoolStart"] ;
				}
				if ($timeEnd=="") {
					$timeEnd=$rowDays["schoolEnd"] ;
				}
				if ($rowDays["schoolEnd"]>$timeEnd) {
					$timeEnd=$rowDays["schoolEnd"] ;
				}
			
				//See which days are school days
				if ($rowDays["nameShort"]=="Mon") {
					$days["Mon"]="Y" ;
					$daysInWeek++ ;
				}
				else if ($rowDays["nameShort"]=="Tue") {
					$days["Tue"]="Y" ;
					$daysInWeek++ ;
				}
				else if ($rowDays["nameShort"]=="Wed") {
					$days["Wed"]="Y" ;
					$daysInWeek++ ;
				}
				else if ($rowDays["nameShort"]=="Thu") {
					$days["Thu"]="Y" ;
					$daysInWeek++ ;
				}
				else if ($rowDays["nameShort"]=="Fri") {
					$days["Fri"]="Y" ;
					$daysInWeek++ ;
				}
				else if ($rowDays["nameShort"]=="Sat") {
					$days["Sat"]="Y" ;
					$daysInWeek++ ;
				}
				else if ($rowDays["nameShort"]=="Sun") {
					$days["Sun"]="Y" ;
					$daysInWeek++ ;
				}
			}
		
			//Count forward to the end of the week
			$endDayStamp=$startDayStamp+(86400*$daysInWeek) ;
		
			$schoolCalendarAlpha=0.85 ;
			$ttAlpha=1.0 ;
		
			if ($_SESSION[$guid]["viewCalendarSchool"]!="N" OR $_SESSION[$guid]["viewCalendarPersonal"]!="N" OR $_SESSION[$guid]["viewCalendarSpaceBooking"]!="N") {
				$ttAlpha=0.75 ;
			}
	
			//Get school calendar array
			$allDay=FALSE ;
			$eventsSchool=FALSE ;
			if ($self==TRUE AND $_SESSION[$guid]["viewCalendarSchool"]=="Y") {
				if ($_SESSION[$guid]["calendarFeed"]!="") {
					$eventsSchool=getCalendarEvents($connection2, $guid,  $_SESSION[$guid]["calendarFeed"], $startDayStamp, $endDayStamp) ;
				}
				//Any all days?
				if ($eventsSchool!=FALSE) {
					foreach ($eventsSchool AS $event) {
						if ($event[1]=="All Day") {
							$allDay=TRUE ;
						}
					}
				}
			}
		
			//Get personal calendar array
			$eventsPersonal=FALSE ;
			if ($self==TRUE AND $_SESSION[$guid]["viewCalendarPersonal"]=="Y") {
				if ($_SESSION[$guid]["calendarFeedPersonal"]!="") {
					$eventsPersonal=getCalendarEvents($connection2, $guid,  $_SESSION[$guid]["calendarFeedPersonal"], $startDayStamp, $endDayStamp) ;
				}
				//Any all days?
				if ($eventsPersonal!=FALSE) {
					foreach ($eventsPersonal AS $event) {
						if ($event[1]=="All Day") {
							$allDay=TRUE ;
						}
					}
				}
			}
		
			$spaceBookingAvailable=isActionAccessible($guid, $connection2, "/modules/Timetable/spaceBooking_manage.php") ;
			$eventsSpaceBooking=FALSE ;
			if ($spaceBookingAvailable) {
				//Get space booking array
				if ($self==TRUE AND $_SESSION[$guid]["viewCalendarSpaceBooking"]=="Y") {
					$eventsSpaceBooking=getSpaceBookingEvents($guid, $connection2, $startDayStamp, $_SESSION[$guid]["gibbonPersonID"]) ;
				}	
			}
		
			//Count up max number of all day events in a day
			$eventsCombined=FALSE ;
			$maxAllDays=0 ;
			if ($allDay==TRUE) {
				if ($eventsPersonal!=FALSE AND $eventsSchool!=FALSE) {
					$eventsCombined=array_merge($eventsSchool, $eventsPersonal) ;
				}
				else if ($eventsSchool!=FALSE) {
					$eventsCombined=$eventsSchool ;
				}
				else if ($eventsPersonal!=FALSE) {
					$eventsCombined=$eventsPersonal ;
				}
			
				$eventsCombined=msort($eventsCombined, 2, true) ;
			
				$currentAllDays=0 ;
				$lastDate="" ;
				$currentDate="" ;
				foreach ($eventsCombined AS $event) {
					if ($event[1]=="All Day") {
						$currentDate=date("Y-m-d", $event[2]) ;
						if ($lastDate!=$currentDate) {
							$currentAllDays=0 ;
						}	
						$currentAllDays++ ;
					
						if ($currentAllDays>$maxAllDays) {
							$maxAllDays=$currentAllDays ;
						}
					
						$lastDate=$currentDate ;
					} 
				}
			}
		
			//Max diff time for week based on timetables
			try {
				$dataDiff=array("date1"=>date("Y-m-d", ($startDayStamp+(86400*0))), "date2"=>date("Y-m-d", ($endDayStamp+(86400*1))), "gibbonTTID"=>$row["gibbonTTID"]); 
				$sqlDiff="SELECT DISTINCT gibbonTTColumn.gibbonTTColumnID FROM gibbonTTDay JOIN gibbonTTDayDate ON (gibbonTTDay.gibbonTTDayID=gibbonTTDayDate.gibbonTTDayID) JOIN gibbonTTColumn ON (gibbonTTDay.gibbonTTColumnID=gibbonTTColumn.gibbonTTColumnID) WHERE (date>=:date1 AND date<=:date2) AND gibbonTTID=:gibbonTTID" ;
				$resultDiff=$connection2->prepare($sqlDiff);
				$resultDiff->execute($dataDiff);
			}
			catch(PDOException $e) { 
				$output.="<div class='error'>" . $e->getMessage() . "</div>" ; 
			}
			while ($rowDiff=$resultDiff->fetch()) {
				try {
					$dataDiffDay=array("gibbonTTColumnID"=>$rowDiff["gibbonTTColumnID"]); 
					$sqlDiffDay="SELECT * FROM gibbonTTColumnRow WHERE gibbonTTColumnID=:gibbonTTColumnID ORDER BY timeStart" ;
					$resultDiffDay=$connection2->prepare($sqlDiffDay);
					$resultDiffDay->execute($dataDiffDay);
				}
				catch(PDOException $e) { 
					$output.="<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				while ($rowDiffDay=$resultDiffDay->fetch()) {
					if ($rowDiffDay["timeStart"]<$timeStart) {
						$timeStart=$rowDiffDay["timeStart"] ;
					}
					if ($rowDiffDay["timeEnd"]>$timeEnd) {
						$timeEnd=$rowDiffDay["timeEnd"] ;
					}
				}
			}
		
			//Max diff time for week based on special days timing change
			try {
				$dataDiff=array("date1"=>date("Y-m-d", ($startDayStamp+(86400*0))), "date2"=>date("Y-m-d", ($startDayStamp+(86400*6)))); 
				$sqlDiff="SELECT * FROM gibbonSchoolYearSpecialDay WHERE date>=:date1 AND date<=:date2 AND type='Timing Change' AND NOT schoolStart IS NULL AND NOT schoolEnd IS NULL" ;
				$resultDiff=$connection2->prepare($sqlDiff);
				$resultDiff->execute($dataDiff);
			}
			catch(PDOException $e) { 
				$output.="<div class='error'>" . $e->getMessage() . "</div>" ; 
			}
			while ($rowDiff=$resultDiff->fetch()) {
				if ($rowDiff["schoolStart"]<$timeStart) {
					$timeStart=$rowDiff["schoolStart"] ;
				}
				if ($rowDiff["schoolEnd"]>$timeEnd) {
					$timeEnd=$rowDiff["schoolEnd"] ;
				}
			}
		
			//Max diff based on school calendar events
			if ($self==TRUE AND $eventsSchool!=FALSE) {
				foreach ($eventsSchool as $event) {
					if (date("Y-m-d", $event[2])<=date("Y-m-d", ($startDayStamp+(86400*6)))) {
						if ($event[1]!="All Day") {
							if (date("H:i:s", $event[2])<$timeStart) {
								$timeStart=date("H:i:s", $event[2]) ;
							}
							if (date("H:i:s", $event[3])>$timeEnd) {
								$timeEnd=date("H:i:s", $event[3]) ;				
							}
							if (date("Y-m-d", $event[2])!=date("Y-m-d", $event[3])) {
								$timeEnd="23:59:59" ;
							}
						}
					}
				}
			}
		
			//Max diff based on personal calendar events
			if ($self==TRUE AND $eventsPersonal!=FALSE) {
				foreach ($eventsPersonal as $event) {
					if (date("Y-m-d", $event[2])<=date("Y-m-d", ($startDayStamp+(86400*6)))) {
						if ($event[1]!="All Day") {
							if (date("H:i:s", $event[2])<$timeStart) {
								$timeStart=date("H:i:s", $event[2]) ;
							}
							if (date("H:i:s", $event[3])>$timeEnd) {
								$timeEnd=date("H:i:s", $event[3]) ;				
							}
							if (date("Y-m-d", $event[2])!=date("Y-m-d", $event[3])) {
								$timeEnd="23:59:59" ;
							}
						}
					}
				}
			}
		
			//Max diff based on space booking events
			if ($self==TRUE AND $eventsSpaceBooking!=FALSE) {
				foreach ($eventsSpaceBooking as $event) {
					if ($event[3]<=date("Y-m-d", ($startDayStamp+(86400*6)))) {
						if ($event[4]<$timeStart) {
							$timeStart=$event[4] ;
						}
						if ($event[5]>$timeEnd) {
							$timeEnd=$event[5] ;				
						}
					}
				}
			}
		
			//Final calc
			$diffTime=strtotime($timeEnd)-strtotime($timeStart) ;
		
			if ($narrow) {
				$width=(ceil(515/$daysInWeek)-20) . "px" ;
			}
			else {
				$width=(ceil(690/$daysInWeek)-20) . "px" ;
			}
		
			$count=0;
		
			$output.="<table cellspacing='0' class='mini' cellspacing='0' style='width: " ; if ($narrow) { $output.="575px" ; } else { $output.="750px" ; } $output.="; margin: 0px 0px 30px 0px;'>" ;
				//Spit out controls for displaying calendars
				if ($self==TRUE AND ($_SESSION[$guid]["calendarFeed"]!="" OR $_SESSION[$guid]["calendarFeedPersonal"]!="" OR $_SESSION[$guid]["viewCalendarSpaceBooking"]!="")) {
					$output.="<tr class='head' style='height: 37px;'>" ;
						$output.="<th class='ttCalendarBar' colspan=" . ($daysInWeek+1) . ">" ;
							$output.="<form method='post' action='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=$q" . $params . "' style='padding: 5px 5px 0 0'>" ;
								if ($_SESSION[$guid]["calendarFeed"]!="" AND $_SESSION[$guid]['googleAPIAccessToken']!=NULL) {
									$checked="" ;
									if ($_SESSION[$guid]["viewCalendarSchool"]=="Y") {
										$checked="checked" ;
									}
									$output.="<span class='ttSchoolCalendar' style='opacity: $schoolCalendarAlpha'>" . _('School Calendar') ;
									$output.="<input $checked style='margin-left: 3px' type='checkbox' name='schoolCalendar' onclick='submit();'/>" ;
									$output.="</span>" ;
								}
								if ($_SESSION[$guid]["calendarFeedPersonal"]!="" AND isset($_SESSION[$guid]['googleAPIAccessToken'])) {
									$checked="" ;
									if ($_SESSION[$guid]["viewCalendarPersonal"]=="Y") {
										$checked="checked" ;
									}
									$output.="<span class='ttPersonalCalendar' style='opacity: $schoolCalendarAlpha'>" . _('Personal Calendar') ;
									$output.="<input $checked style='margin-left: 3px' type='checkbox' name='personalCalendar' onclick='submit();'/>" ;
									$output.="</span>" ;
								}
								if ($spaceBookingAvailable) {
									if ($_SESSION[$guid]["viewCalendarSpaceBooking"]!="") {
										$checked="" ;
										if ($_SESSION[$guid]["viewCalendarSpaceBooking"]=="Y") {
											$checked="checked" ;
										}
										$output.="<span class='ttSpaceBookingCalendar' style='opacity: $schoolCalendarAlpha'>" . _('Space Booking') . " " ;
										$output.="<input $checked style='margin-left: 3px' type='checkbox' name='spaceBookingCalendar' onclick='submit();'/>" ;
										$output.="</span>" ;
									}
								}
							
								$output.="<input type='hidden' name='ttDate' value='" . date("d/m/Y", $startDayStamp) . "'>" ;
								$output.="<input name='fromTT' value='Y' type='hidden'>" ;
							$output.="</form>" ;
						$output.="</th>" ;
					$output.="</tr>" ;
				}
		
		
				$output.="<tr class='head'>" ;
					$output.="<th style='vertical-align: top; width: 70px; text-align: center'>" ;
						//Calculate week number
						$week=getWeekNumber ($startDayStamp, $connection2, $guid) ;
						if ($week!=false) {
							$output.=sprintf(_('Week %1$s'), $week) . "<br/>" ;
						}
						$output.="<span style='font-weight: normal; font-style: italic;'>" . _('Time') . "<span>" ;
					$output.="</th>" ;
					if ($days["Mon"]=="Y") {
						$output.="<th style='vertical-align: top; text-align: center; width: " ; if ($narrow) { $output.=(375/$daysInWeek) ; } else { $output.=(550/$daysInWeek) ; } $output.="px'>" ;
							$output.=_("Mon") . "<br/>" ;
							$output.="<span style='font-size: 80%; font-style: italic'>". date("d/m", ($startDayStamp+(86400*0))) . "</span><br/>" ;
							try {
								$dataSpecial=array("date"=>date("Y-m-d", ($startDayStamp+(86400*0)))); 
								$sqlSpecial="SELECT * FROM gibbonSchoolYearSpecialDay WHERE date=:date AND type='Timing Change'" ;
								$resultSpecial=$connection2->prepare($sqlSpecial);
								$resultSpecial->execute($dataSpecial);
							}
							catch(PDOException $e) { 
								$output.="<div class='error'>" . $e->getMessage() . "</div>" ; 
							}
							if ($resultSpecial->rowcount()==1) {
								$rowSpecial=$resultSpecial->fetch() ;
								$output.="<span style='font-size: 80%; font-weight: bold'><u>". $rowSpecial["name"] . "</u></span>" ;
							}
						$output.="</th>" ;
					}
					if ($days["Tue"]=="Y") {	
						$output.="<th style='vertical-align: top; text-align: center; width: " ; if ($narrow) { $output.=(375/$daysInWeek) ; } else { $output.=(550/$daysInWeek) ; } $output.= "px'>" ;
							$output.=_("Tue") . "<br/>" ;
							$output.="<span style='font-size: 80%; font-style: italic'>". date("d/m", ($startDayStamp+(86400*1))) . "</span><br/>" ;
							try {
								$dataSpecial=array("date"=>date("Y-m-d", ($startDayStamp+(86400*1)))); 
								$sqlSpecial="SELECT * FROM gibbonSchoolYearSpecialDay WHERE date=:date AND type='Timing Change'" ;
								$resultSpecial=$connection2->prepare($sqlSpecial);
								$resultSpecial->execute($dataSpecial);
							}
							catch(PDOException $e) { 
								$output.="<div class='error'>" . $e->getMessage() . "</div>" ; 
							}
							if ($resultSpecial->rowcount()==1) {
								$rowSpecial=$resultSpecial->fetch() ;
								$output.="<span style='font-size: 80%; font-weight: bold'><u>". $rowSpecial["name"] . "</u></span>" ;
							}
						$output.="</th>" ;
					}
					if ($days["Wed"]=="Y") {
						$output.="<th style='vertical-align: top; text-align: center; width: " ; if ($narrow) { $output.=(375/$daysInWeek) ; } else { $output.=(550/$daysInWeek) ; } $output.= "px'>" ;
							$output.=_("Wed") . "<br/>" ;
							$output.="<span style='font-size: 80%; font-style: italic'>". date("d/m", ($startDayStamp+(86400*2))) . "</span><br/>" ;
							try {
								$dataSpecial=array("date"=>date("Y-m-d", ($startDayStamp+(86400*2)))); 
								$sqlSpecial="SELECT * FROM gibbonSchoolYearSpecialDay WHERE date=:date AND type='Timing Change'" ;
								$resultSpecial=$connection2->prepare($sqlSpecial);
								$resultSpecial->execute($dataSpecial);
							}
							catch(PDOException $e) { 
								$output.="<div class='error'>" . $e->getMessage() . "</div>" ; 
							}
							if ($resultSpecial->rowcount()==1) {
								$rowSpecial=$resultSpecial->fetch() ;
								$output.="<span style='font-size: 80%; font-weight: bold'><u>". $rowSpecial["name"] . "</u></span>" ;
							}
						$output.="</th>" ;
					}
					if ($days["Thu"]=="Y") {
						$output.="<th style='vertical-align: top; text-align: center; width: " ; if ($narrow) { $output.=(375/$daysInWeek) ; } else { $output.=(550/$daysInWeek) ; } $output.= "px'>" ;
							$output.=_("Thu") . "<br/>" ;
							$output.="<span style='font-size: 80%; font-style: italic'>". date("d/m", ($startDayStamp+(86400*3))) . "</span><br/>" ;
							try {
								$dataSpecial=array("date"=>date("Y-m-d", ($startDayStamp+(86400*3)))); 
								$sqlSpecial="SELECT * FROM gibbonSchoolYearSpecialDay WHERE date=:date AND type='Timing Change'" ;
								$resultSpecial=$connection2->prepare($sqlSpecial);
								$resultSpecial->execute($dataSpecial);
							}
							catch(PDOException $e) { 
								$output.="<div class='error'>" . $e->getMessage() . "</div>" ; 
							}
							if ($resultSpecial->rowcount()==1) {
								$rowSpecial=$resultSpecial->fetch() ;
								$output.="<span style='font-size: 80%; font-weight: bold'><u>". $rowSpecial["name"] . "</u></span>" ;
							}
						$output.="</th>" ;
					}
					if ($days["Fri"]=="Y") {
						$output.="<th style='vertical-align: top; text-align: center; width: " ; if ($narrow) { $output.=(375/$daysInWeek) ; } else { $output.=(550/$daysInWeek) ; } $output.= "px'>" ;
							$output.=_("Fri") . "<br/>" ;
							$output.="<span style='font-size: 80%; font-style: italic'>". date("d/m", ($startDayStamp+(86400*4))) . "</span><br/>" ;
							try {
								$dataSpecial=array("date"=>date("Y-m-d", ($startDayStamp+(86400*4)))); 
								$sqlSpecial="SELECT * FROM gibbonSchoolYearSpecialDay WHERE date=:date AND type='Timing Change'" ;
								$resultSpecial=$connection2->prepare($sqlSpecial);
								$resultSpecial->execute($dataSpecial);
							}
							catch(PDOException $e) { 
								$output.="<div class='error'>" . $e->getMessage() . "</div>" ; 
							}
							if ($resultSpecial->rowcount()==1) {
								$rowSpecial=$resultSpecial->fetch() ;
								$output.="<span style='font-size: 80%; font-weight: bold'><u>". $rowSpecial["name"] . "</u></span>" ;
							}
						$output.="</th>" ;
					}
					if ($days["Sat"]=="Y") {
						$output.="<th style='vertical-align: top; text-align: center; width: " ; if ($narrow) { $output.=(375/$daysInWeek) ; } else { $output.=(550/$daysInWeek) ; } $output.= "px'>" ;
							$output.=_("Sat") . "<br/>" ;
							$output.="<span style='font-size: 80%; font-style: italic'>". date("d/m", ($startDayStamp+(86400*5))) . "</span><br/>" ;
							try {
								$dataSpecial=array("date"=>date("Y-m-d", ($startDayStamp+(86400*5)))); 
								$sqlSpecial="SELECT * FROM gibbonSchoolYearSpecialDay WHERE date=:date AND type='Timing Change'" ;
								$resultSpecial=$connection2->prepare($sqlSpecial);
								$resultSpecial->execute($dataSpecial);
							}
							catch(PDOException $e) { 
								$output.="<div class='error'>" . $e->getMessage() . "</div>" ; 
							}
							if ($resultSpecial->rowcount()==1) {
								$rowSpecial=$resultSpecial->fetch() ;
								$output.="<span style='font-size: 80%; font-weight: bold'><u>". $rowSpecial["name"] . "</u></span>" ;
							}
						$output.="</th>" ;
					}
					if ($days["Sun"]=="Y") {
						$output.="<th style='vertical-align: top; text-align: center; width: " ; if ($narrow) { $output.=(375/$daysInWeek) ; } else { $output.=(550/$daysInWeek) ; } $output.= "px'>" ;
							$output.=_("Sun") . "<br/>" ;
							$output.="<span style='font-size: 80%; font-style: italic'>". date("d/m", ($startDayStamp+(86400*6))) . "</span><br/>" ;
							try {
								$dataSpecial=array("date"=>date("Y-m-d", ($startDayStamp+(86400*6)))); 
								$sqlSpecial="SELECT * FROM gibbonSchoolYearSpecialDay WHERE date=:date AND type='Timing Change'" ;
								$resultSpecial=$connection2->prepare($sqlSpecial);
								$resultSpecial->execute($dataSpecial);
							}
							catch(PDOException $e) { 
								$output.="<div class='error'>" . $e->getMessage() . "</div>" ; 
							}
							if ($resultSpecial->rowcount()==1) {
								$rowSpecial=$resultSpecial->fetch() ;
								$output.="<span style='font-size: 80%; font-weight: bold'><u>". $rowSpecial["name"] . "</u></span>" ;
							}
						$output.="</th>" ;
					}
				$output.="</tr>" ;
			
				//Space for all day events
				if (($eventsSchool==TRUE OR $eventsPersonal==TRUE) AND $allDay==TRUE AND $eventsCombined!=NULL) {
					$output.="<tr style='height: " . ((31*$maxAllDays)+5) . "px'>" ;
						$output.="<td style='vertical-align: top; width: 70px; text-align: center; border-top: 1px solid #888; border-bottom: 1px solid #888'>" ;
							$output.="<span style='font-size: 80%'><b>" . sprintf(_('All Day%1$s Events'), "<br/>") . "</b></span>" ;
						$output.="</td>" ;
						$output.="<td colspan=$daysInWeek style='vertical-align: top; width: 70px; text-align: center; border-top: 1px solid #888; border-bottom: 1px solid #888'>" ;
						$output.="</td>" ;
					$output.="</tr>" ;
				}
			
				$output.="<tr style='height:" . (ceil($diffTime/60)+14) . "px'>" ;
					$output.="<td style='height: 300px; width: 75px; text-align: center; vertical-align: top'>" ;
						$output.="<div style='position: relative; width: 71px'>" ;
							$countTime=0 ;
							$time=$timeStart ;
							$output.="<div $title style='z-index: " . $zCount . "; position: absolute; top: -3px; width: 71px ; border: none; height: 60px; margin: 0px; padding: 0px; font-size: 92%'>" ;
								$output.=substr($time,0,5) . "<br/>" ;
							$output.="</div>" ;
							$time=date("H:i:s", strtotime($time)+3600) ;
							$spinControl=0 ;
							while ($time<=$timeEnd AND $spinControl<@(23-date("H",$timeStart))) {
								$countTime++ ;
								$output.="<div $title style='z-index: $zCount; position: absolute; top:" . (($countTime*60)-5) . "px ; width: 71px ; border: none; height: 60px; margin: 0px; padding: 0px; font-size: 92%'>" ;
									$output.=substr($time,0,5) . "<br/>" ;
								$output.="</div>" ;
								$time=date("H:i:s", strtotime($time)+3600) ;
								$spinControl++ ;
							}
						
						$output.="</div>" ;
					$output.="</td>" ;
				
					//Check to see if week is at all in term time...if it is, then display the grid
					$isWeekInTerm=FALSE ;
					try {
						$dataTerm=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
						$sqlTerm="SELECT gibbonSchoolYearTerm.firstDay, gibbonSchoolYearTerm.lastDay FROM gibbonSchoolYearTerm, gibbonSchoolYear WHERE gibbonSchoolYearTerm.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID AND gibbonSchoolYear.gibbonSchoolYearID=:gibbonSchoolYearID" ; 
						$resultTerm=$connection2->prepare($sqlTerm);
						$resultTerm->execute($dataTerm);
					}
					catch(PDOException $e) { 
						$output.="<div class='error'>" . $e->getMessage() . "</div>" ; 
					}
					$weekStart=date("Y-m-d", ($startDayStamp+(86400*0))) ;
					$weekEnd=date("Y-m-d", ($startDayStamp+(86400*6))) ;
					while ($rowTerm=$resultTerm->fetch()) {
						if ($weekStart<=$rowTerm["firstDay"] AND $weekEnd>=$rowTerm["firstDay"]) {
							$isWeekInTerm=TRUE ;
						}
						else if ($weekStart>=$rowTerm["firstDay"] AND $weekEnd<=$rowTerm["lastDay"]) {
							$isWeekInTerm=TRUE ;
						}
						else if ($weekStart<=$rowTerm["lastDay"] AND $weekEnd>=$rowTerm["lastDay"]) {
							$isWeekInTerm=TRUE ;
						}
					}
					if ($isWeekInTerm==TRUE) {
						$blank=FALSE ;
					}
				
					//Run through days of the week
					$dayOfWeek="" ;
					for ($d=0; $d<7; $d++) {
						$day="" ;
						if ($d==0) { $dayOfWeek="Mon" ; }
						else if ($d==1) { $dayOfWeek="Tue" ; }
						else if ($d==2) { $dayOfWeek="Wed" ; }
						else if ($d==3) { $dayOfWeek="Thu" ; }
						else if ($d==4) { $dayOfWeek="Fri" ; }
						else if ($d==5) { $dayOfWeek="Sat" ; }
						else if ($d==6) { $dayOfWeek="Sun" ; }
					
					
						if ($days[$dayOfWeek]=="Y") {
							//Check to see if day is term time
							$isDayInTerm=FALSE ;
							try {
								$dataTerm=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
								$sqlTerm="SELECT gibbonSchoolYearTerm.firstDay, gibbonSchoolYearTerm.lastDay FROM gibbonSchoolYearTerm, gibbonSchoolYear WHERE gibbonSchoolYearTerm.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID AND gibbonSchoolYear.gibbonSchoolYearID=:gibbonSchoolYearID" ; 
								$resultTerm=$connection2->prepare($sqlTerm);
								$resultTerm->execute($dataTerm);
							}
							catch(PDOException $e) { 
								$output.="<div class='error'>" . $e->getMessage() . "</div>" ; 
							}
							while ($rowTerm=$resultTerm->fetch()) {
								if (date("Y-m-d", ($startDayStamp+(86400*$count)))>=$rowTerm["firstDay"] AND date("Y-m-d", ($startDayStamp+(86400*$count)))<=$rowTerm["lastDay"]) {
									$isDayInTerm=TRUE ;
								}
							}
						
							if ($isDayInTerm==TRUE) {
								//Check for school closure day
								try {
									$dataClosure=array("date"=>date("Y-m-d", ($startDayStamp+(86400*$count)))); 
									$sqlClosure="SELECT * FROM gibbonSchoolYearSpecialDay WHERE date=:date" ;
									$resultClosure=$connection2->prepare($sqlClosure);
									$resultClosure->execute($dataClosure);
								}
								catch(PDOException $e) { 
									$output.="<div class='error'>" . $e->getMessage() . "</div>" ; 
								}
								if ($resultClosure->rowCount()==1) {
									$rowClosure=$resultClosure->fetch() ;
									if ($rowClosure["type"]=="School Closure") {
										$day=$day . "<td style='text-align: center; vertical-align: top; font-size: 11px'>" ;
											$day=$day . "<div style='position: relative'>" ;
												$day=$day . "<div class='ttClosure' style='z-index: $zCount; position: absolute; width: $width ; height: " . ceil($diffTime/60) . "px; margin: 0px; padding: 0px; opacity: $ttAlpha'>" ;
													$day=$day . "<div style='position: relative; top: 50%'>" ;
														$day=$day . "<span>" . $rowClosure["name"] . "</span>" ;
													$day=$day . "</div>" ;
												$day=$day . "</div>" ;
											$day=$day . "</div>" ;
										$day=$day . "</td>" ;
									}
									else if ($rowClosure["type"]=="Timing Change") {
										$day=renderTTDay($guid, $connection2, $row["gibbonTTID"], $startDayStamp, $count, $daysInWeek, $gibbonPersonID, $timeStart, $eventsSchool, $eventsPersonal, $eventsSpaceBooking, $diffTime, $maxAllDays, $narrow, $rowClosure["schoolStart"], $rowClosure["schoolEnd"]) ;
									}
								}
								else {
									$day=renderTTDay($guid, $connection2, $row["gibbonTTID"], $startDayStamp, $count, $daysInWeek, $gibbonPersonID, $timeStart, $eventsSchool, $eventsPersonal, $eventsSpaceBooking, $diffTime, $maxAllDays, $narrow) ;
								}
							}
							else {
								$day=$day . "<td style='text-align: center; vertical-align: top; font-size: 11px'>" ;
									$day=$day . "<div style='position: relative'>" ;
										$day=$day . "<div class='ttClosure' style='z-index: $zCount; position: absolute; width: $width ; height: " . ceil($diffTime/60) . "px; margin: 0px; padding: 0px; opacity: $ttAlpha'>" ;
											$day=$day . "<div style='position: relative; top: 50%'>" ;
												$day=$day . "<span style='color: rgba(255,0,0,$ttAlpha);'>" . _('School Closed') . "</span>" ;
											$day=$day . "</div>" ;
										$day=$day . "</div>" ;
									$day=$day . "</div>" ;
								$day=$day . "</td>" ;
							}
						
							if ($day=="") {
								$day=$day . "<td style='text-align: center; vertical-align: top; font-size: 11px'></td>" ;
							}
						
							$output.=$day ;
								
							$count++ ;
						}
					}
				
				$output.="</tr>" ;
			$output.="</table>" ;
		}
	}
	
	return $output ;
}

function renderTTDay($guid, $connection2, $gibbonTTID, $startDayStamp, $count, $daysInWeek, $gibbonPersonID, $gridTimeStart, $eventsSchool, $eventsPersonal, $eventsSpaceBooking, $diffTime, $maxAllDays, $narrow, $specialDayStart="", $specialDayEnd="") {
	$schoolCalendarAlpha=0.85 ;
	$ttAlpha=1.0 ;
	
	if ($_SESSION[$guid]["viewCalendarSchool"]!="N" OR $_SESSION[$guid]["viewCalendarPersonal"]!="N" OR $_SESSION[$guid]["viewCalendarSpaceBooking"]!="N") {
		$ttAlpha=0.75 ;
	}
	
	
	$date=date("Y/m/d", ($startDayStamp+(86400*$count))) ;
	
	$self=FALSE ;
	if ($gibbonPersonID==$_SESSION[$guid]["gibbonPersonID"]) {
		$self=TRUE ;
		$roleCategory=getRoleCategory($_SESSION[$guid]["gibbonRoleIDCurrent"], $connection2) ;
	}
	
	$output="" ;
	$blank=TRUE ;
	
	//Make array of space changes
	$spaceChanges=array() ;
	try {
		$dataSpaceChange=array("date"=>date("Y-m-d", ($startDayStamp+(86400*$count)))); 
		$sqlSpaceChange="SELECT gibbonTTSpaceChange.*, gibbonSpace.name AS space, phoneInternal FROM gibbonTTSpaceChange LEFT JOIN gibbonSpace ON (gibbonTTSpaceChange.gibbonSpaceID=gibbonSpace.gibbonSpaceID) WHERE date=:date" ;
		$resultSpaceChange=$connection2->prepare($sqlSpaceChange);
		$resultSpaceChange->execute($dataSpaceChange);
	}
	catch(PDOException $e) { }
	while ($rowSpaceChange=$resultSpaceChange->fetch()) {
		$spaceChanges[$rowSpaceChange["gibbonTTDayRowClassID"]][0]=$rowSpaceChange["space"] ;
		$spaceChanges[$rowSpaceChange["gibbonTTDayRowClassID"]][1]=$rowSpaceChange["phoneInternal"] ;
	}
	
	//Get day start and end!
	$dayTimeStart="" ;
	$dayTimeEnd="" ;
	try {
		$dataDiff=array("date"=>date("Y-m-d", ($startDayStamp+(86400*$count))), "gibbonTTID"=>$gibbonTTID); 
		$sqlDiff="SELECT timeStart, timeEnd FROM gibbonTTDay JOIN gibbonTTDayDate ON (gibbonTTDay.gibbonTTDayID=gibbonTTDayDate.gibbonTTDayID) JOIN gibbonTTColumn ON (gibbonTTDay.gibbonTTColumnID=gibbonTTColumn.gibbonTTColumnID) JOIN gibbonTTColumnRow ON (gibbonTTColumn.gibbonTTColumnID=gibbonTTColumnRow.gibbonTTColumnID) WHERE date=:date AND gibbonTTID=:gibbonTTID" ;
		$resultDiff=$connection2->prepare($sqlDiff);
		$resultDiff->execute($dataDiff);
	}
	catch(PDOException $e) { 
		$output.="<div class='error'>" . $e->getMessage() . "</div>" ; 
	}
	while ($rowDiff=$resultDiff->fetch()) {
		if ($dayTimeStart=="") {
			$dayTimeStart=$rowDiff["timeStart"] ;
		}
		if ($rowDiff["timeStart"]<$dayTimeStart) {
			$dayTimeStart=$rowDiff["timeStart"] ;
		}
		if ($dayTimeEnd=="") {
			$dayTimeEnd=$rowDiff["timeEnd"] ;
		}
		if ($rowDiff["timeEnd"]>$dayTimeEnd) {
			$dayTimeEnd=$rowDiff["timeEnd"] ;
		}
	}
	if ($specialDayStart!="") {
		$dayTimeStart=$specialDayStart ;
	}
	if ($specialDayEnd!="") {
		$dayTimeEnd=$specialDayEnd ;
	}
	
	$dayDiffTime=strtotime($dayTimeEnd)-strtotime($dayTimeStart) ;
	
	$startPad=strtotime($dayTimeStart)-strtotime($gridTimeStart);
	
	$output.="<td style='text-align: center; vertical-align: top; font-size: 11px'>" ;
		try {
			$dataDay=array("gibbonTTID"=>$gibbonTTID, "date"=>date("Y-m-d", ($startDayStamp+(86400*$count)))); 
			$sqlDay="SELECT gibbonTTDay.gibbonTTDayID FROM gibbonTTDayDate JOIN gibbonTTDay ON (gibbonTTDayDate.gibbonTTDayID=gibbonTTDay.gibbonTTDayID) WHERE gibbonTTID=:gibbonTTID AND date=:date" ;
			$resultDay=$connection2->prepare($sqlDay);
			$resultDay->execute($dataDay);
		}
		catch(PDOException $e) { 
			$output.="<div class='error'>" . $e->getMessage() . "</div>" ; 
		}
		
		if ($resultDay->rowCount()==1) {
			$rowDay=$resultDay->fetch() ;
			$zCount=0 ;
			$output.="<div style='position: relative'>" ;
			
			//Draw outline of the day
			try {
				$dataPeriods=array("gibbonTTDayID"=>$rowDay["gibbonTTDayID"], "date"=>date("Y-m-d", ($startDayStamp+(86400*$count)))); 
				$sqlPeriods="SELECT gibbonTTColumnRow.name, timeStart, timeEnd, type, date FROM gibbonTTDay JOIN gibbonTTDayDate ON (gibbonTTDay.gibbonTTDayID=gibbonTTDayDate.gibbonTTDayID) JOIN gibbonTTColumn ON (gibbonTTDay.gibbonTTColumnID=gibbonTTColumn.gibbonTTColumnID) JOIN gibbonTTColumnRow ON (gibbonTTColumnRow.gibbonTTColumnID=gibbonTTColumn.gibbonTTColumnID) WHERE gibbonTTDayDate.gibbonTTDayID=:gibbonTTDayID AND date=:date ORDER BY timeStart, timeEnd" ;
				$resultPeriods=$connection2->prepare($sqlPeriods);
				$resultPeriods->execute($dataPeriods);
			}
			catch(PDOException $e) { 
				$output.="<div class='error'>" . $e->getMessage() . "</div>" ; 
			}
			while ($rowPeriods=$resultPeriods->fetch()) {
				$isSlotInTime=FALSE ;
				if ($rowPeriods["timeStart"]<=$dayTimeStart AND $rowPeriods["timeEnd"]>$dayTimeStart) {
					$isSlotInTime=TRUE ;
				}
				else if ($rowPeriods["timeStart"]>=$dayTimeStart AND $rowPeriods["timeEnd"]<=$dayTimeEnd) {
					$isSlotInTime=TRUE ;
				}
				else if ($rowPeriods["timeStart"]<$dayTimeEnd AND $rowPeriods["timeEnd"]>=$dayTimeEnd) {
					$isSlotInTime=TRUE ;
				}
				
				if ($isSlotInTime==TRUE) {
					$effectiveStart=$rowPeriods["timeStart"] ;
					$effectiveEnd=$rowPeriods["timeEnd"] ;
					if ($dayTimeStart>$rowPeriods["timeStart"]) {
						$effectiveStart=$dayTimeStart ;
					}
					if ($dayTimeEnd<$rowPeriods["timeEnd"]) {
						$effectiveEnd=$dayTimeEnd ;
					}
					
					if ($narrow) {
						$width=(ceil(515/$daysInWeek)-20) . "px" ;
					}
					else {
						$width=(ceil(690/$daysInWeek)-20) . "px" ;
					}
					$height=ceil((strtotime($effectiveEnd)-strtotime($effectiveStart))/60) . "px" ;
					$top=ceil(((strtotime($effectiveStart)-strtotime($dayTimeStart))+$startPad)/60) . "px" ;
					$title="" ;
					if ($rowPeriods["type"]!="Lesson" AND $height>15 AND $height<30) {
						$title="title='" . substr($effectiveStart,0,5) . " - " . substr($effectiveEnd,0,5) . "'" ;
					}
					else if ($rowPeriods["type"]!="Lesson" AND $height<=15) {
						$title="title='" . $rowPeriods["name"] . " (" . substr($effectiveStart,0,5) . "-" . substr($effectiveEnd,0,5) . ")'" ;
					}
					$class="ttGeneric" ;
					if ((date("H:i:s")>$effectiveStart) AND (date("H:i:s")<$effectiveEnd) AND $rowPeriods["date"]==date("Y-m-d")) {
						$class="ttCurrent" ;
					}
					$style="" ;
					if ($rowPeriods["type"]=="Lesson") {
						$class='ttLesson' ;
					}
					$output.="<div class='$class' $title style='z-index: $zCount; position: absolute; top: $top; width: $width; height: $height; margin: 0px; padding: 0px; opacity: $ttAlpha'>" ;
					if ($height>15 AND $height<30) {
						$output.=$rowPeriods["name"] . "<br/>" ;
					}
					else if ($height>=30) {
						$output.=$rowPeriods["name"] . "<br/>" ;
						$output.="<i>" . substr($effectiveStart,0,5) . "-" . substr($effectiveEnd,0,5) . "</i><br/>" ;
					}
					$output.="</div>" ;
					$zCount++ ;
				}
			}
			
			//Draw periods from TT
			try {
				$dataPeriods=array("gibbonTTDayID"=>$rowDay["gibbonTTDayID"], "gibbonPersonID"=>$gibbonPersonID); 
				$sqlPeriods="SELECT gibbonTTDayRowClassID, gibbonCourseClass.gibbonCourseClassID, gibbonTTColumnRow.name, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, timeStart, timeEnd, phoneInternal, gibbonSpace.name AS roomName FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonTTDayRowClass ON (gibbonCourseClass.gibbonCourseClassID=gibbonTTDayRowClass.gibbonCourseClassID) JOIN gibbonTTColumnRow ON (gibbonTTDayRowClass.gibbonTTColumnRowID=gibbonTTColumnRow.gibbonTTColumnRowID) LEFT JOIN gibbonSpace ON (gibbonTTDayRowClass.gibbonSpaceID=gibbonSpace.gibbonSpaceID) WHERE gibbonTTDayID=:gibbonTTDayID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND NOT role LIKE '% - Left' ORDER BY timeStart, timeEnd" ;
				$resultPeriods=$connection2->prepare($sqlPeriods);
				$resultPeriods->execute($dataPeriods);
			}
			catch(PDOException $e) { 
				$output.="<div class='error'>" . $e->getMessage() . "</div>" ; 
			}
			while ($rowPeriods=$resultPeriods->fetch()) {
				$isSlotInTime=FALSE ;
				if ($rowPeriods["timeStart"]<=$dayTimeStart AND $rowPeriods["timeEnd"]>$dayTimeStart) {
					$isSlotInTime=TRUE ;
				}
				else if ($rowPeriods["timeStart"]>=$dayTimeStart AND $rowPeriods["timeEnd"]<=$dayTimeEnd) {
					$isSlotInTime=TRUE ;
				}
				else if ($rowPeriods["timeStart"]<$dayTimeEnd AND $rowPeriods["timeEnd"]>=$dayTimeEnd) {
					$isSlotInTime=TRUE ;
				}
				
				if ($isSlotInTime==TRUE) {
					//Check for an exception for the current user
					try {
						$dataException=array("gibbonPersonID"=>$gibbonPersonID); 
						$sqlException="SELECT * FROM gibbonTTDayRowClassException WHERE gibbonTTDayRowClassID=" . $rowPeriods["gibbonTTDayRowClassID"] . " AND gibbonPersonID=:gibbonPersonID" ;
						$resultException=$connection2->prepare($sqlException);
						$resultException->execute($dataException);
					}
					catch(PDOException $e) { 
						$output.="<div class='error'>" . $e->getMessage() . "</div>" ; 
					}
					if ($resultException->rowCount()<1) {
						$effectiveStart=$rowPeriods["timeStart"] ;
						$effectiveEnd=$rowPeriods["timeEnd"] ;
						if ($dayTimeStart>$rowPeriods["timeStart"]) {
							$effectiveStart=$dayTimeStart ;
						}
						if ($dayTimeEnd<$rowPeriods["timeEnd"]) {
							$effectiveEnd=$dayTimeEnd ;
						}
					
						$blank=FALSE ;
						if ($narrow) {
							$width=(ceil(515/$daysInWeek)-20) . "px" ;
						}
						else {
							$width=(ceil(690/$daysInWeek)-20) . "px" ;
						}
						$height=ceil((strtotime($effectiveEnd)-strtotime($effectiveStart))/60) . "px" ;
						$top=(ceil((strtotime($effectiveStart)-strtotime($dayTimeStart))/60+($startPad/60))) . "px" ;
						$title="title='" ;
						if ($height<45) {
							$title=$title . $rowPeriods["name"] . " | " ;
							$title=$title . _("Timeslot:") . " " . $rowPeriods["name"] . " " ;
						}
						if ($rowPeriods["roomName"]!="") {
							if ($height<60) {
								if (isset($spaceChanges[$rowPeriods["gibbonTTDayRowClassID"]][0])==FALSE) {
									$title=$title . _("Room:") . " " . $rowPeriods["roomName"] . " " ;
								}
								else {
									$title=$title . _("Room:") . " " . $spaceChanges[$rowPeriods["gibbonTTDayRowClassID"]][0] . " " ;
								}
							}
							if ($rowPeriods["phoneInternal"]!="") {
								if (isset($spaceChanges[$rowPeriods["gibbonTTDayRowClassID"]][0])==FALSE) {
									$title=$title . _("Phone:") ." " . $rowPeriods["phoneInternal"] . " " ;
								}
								else {
									$title=$title . _("Phone:") ." " . $spaceChanges[$rowPeriods["gibbonTTDayRowClassID"]][1] . " " ;
								}
							}
						}
						$title=$title . "'" ;
						$class2="ttPeriod" ;
						if ((date("H:i:s")>$effectiveStart) AND (date("H:i:s")<$effectiveEnd) AND date("Y-m-d", ($startDayStamp+(84000*$count)))==date("Y-m-d")) {
							$class2="ttPeriodCurrent" ;	
						}
						
						//Create div to represent period
						$output.="<div class='$class2' $title style='z-index: $zCount; position: absolute; top: $top; width: $width; height: $height; margin: 0px; padding: 0px; opacity: $ttAlpha'>" ;
						if ($height>=45) {
							$output.=$rowPeriods["name"] . "<br/>" ;
							$output.="<i>" . substr($effectiveStart,0,5) . " - " . substr($effectiveEnd,0,5) . "</i><br/>" ;
						}
						
						if (isActionAccessible($guid, $connection2, "/modules/Departments/department_course_class.php")) {
							$output.="<a style='text-decoration: none; font-weight: bold; font-size: 120%' href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Departments/department_course_class.php&gibbonCourseClassID=" . $rowPeriods["gibbonCourseClassID"] . "&subpage=Participants'>" . $rowPeriods["course"] . "." . $rowPeriods["class"] . "</a><br/>" ;
						}
						else {
							$output.="<span style='font-size: 120%'><b>" . $rowPeriods["course"] . "." . $rowPeriods["class"] . "</b></span><br/>" ;
						}
						if ($height>=60) {
							if (isset($spaceChanges[$rowPeriods["gibbonTTDayRowClassID"]])==FALSE) {
								$output.=$rowPeriods["roomName"] ;
							}
							else {
								if ($spaceChanges[$rowPeriods["gibbonTTDayRowClassID"]][0]!="") {
									$output.="<span style='border: 1px solid #c00; padding: 0 2px'>" . $spaceChanges[$rowPeriods["gibbonTTDayRowClassID"]][0] . "</span>" ;
								}
								else {
									$output.="<span style='border: 1px solid #c00; padding: 0 2px'><i>" . _("No Space Allocated") . "</i></span>" ;
								}
							}
						}
						$output.="</div>" ;
						$zCount++ ;
						
						if ($narrow==FALSE) {
							//Add planner link icons for staff looking at own TT.
							if ($self==TRUE AND $roleCategory=="Staff") { 
								if ($height>=30) {
									$output.="<div $title style='z-index: $zCount; position: absolute; top: $top; width: $width ; border: 1px solid rgba(136,136,136, $ttAlpha); height: $height; margin: 0px; padding: 0px; background-color: none; pointer-events: none'>" ;
										//Check for lesson plan
										$bgImg="none" ;
								
										try {
											$dataPlan=array("gibbonCourseClassID"=>$rowPeriods["gibbonCourseClassID"], "date"=>$date, "timeStart"=>$rowPeriods["timeStart"], "timeEnd"=>$rowPeriods["timeEnd"]); 
											$sqlPlan="SELECT * FROM gibbonPlannerEntry WHERE gibbonCourseClassID=:gibbonCourseClassID AND date=:date AND timeStart=:timeStart AND timeEnd=:timeEnd" ;
											$resultPlan=$connection2->prepare($sqlPlan);
											$resultPlan->execute($dataPlan);
										}
										catch(PDOException $e) { 
											$output.="<div class='error'>" . $e->getMessage() . "</div>" ; 
										}
								
										if ($resultPlan->rowCount()==1) {
											$rowPlan=$resultPlan->fetch() ;
											$output.="<a style='pointer-events: auto' href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Planner/planner_view_full.php&viewBy=class&gibbonCourseClassID=" . $rowPeriods["gibbonCourseClassID"] . "&gibbonPlannerEntryID=" . $rowPlan["gibbonPlannerEntryID"] . "'><img style='float: right; margin: " . (substr($height,0,-2)-27) . "px 2px 0 0' title='Lesson planned: " . htmlPrep($rowPlan["name"]) . "' src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/iconTick.png'/></a>" ;
										}
										else if ($resultPlan->rowCount()==0) {
											$output.="<a style='pointer-events: auto' href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Planner/planner_add.php&viewBy=class&gibbonCourseClassID=" . $rowPeriods["gibbonCourseClassID"] . "&date=" . $date . "&timeStart=" . $effectiveStart . "&timeEnd=" . $effectiveEnd . "'><img style='float: right; margin: " . (substr($height,0,-2)-27) . "px 2px 0 0' title='Add lesson plan' src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/page_new.png'/></a>" ;
										}
									$output.="</div>" ;
									$zCount++ ;
								}
							}
							//Add planner link icons for any one else's TT
							else {
								$output.="<div $title style='z-index: $zCount; position: absolute; top: $top; width: $width ; border: 1px solid rgba(136,136,136, $ttAlpha); height: $height; margin: 0px; padding: 0px; background-color: none; pointer-events: none'>" ;
									//Check for lesson plan
									$bgImg="none" ;
								
									try {
										$dataPlan=array("gibbonCourseClassID"=>$rowPeriods["gibbonCourseClassID"], "date"=>$date, "timeStart"=>$rowPeriods["timeStart"], "timeEnd"=>$rowPeriods["timeEnd"]); 
										$sqlPlan="SELECT * FROM gibbonPlannerEntry WHERE gibbonCourseClassID=:gibbonCourseClassID AND date=:date AND timeStart=:timeStart AND timeEnd=:timeEnd" ;
										$resultPlan=$connection2->prepare($sqlPlan);
										$resultPlan->execute($dataPlan);
									}
									catch(PDOException $e) { 
										$output.="<div class='error'>" . $e->getMessage() . "</div>" ; 
									}
								
									if ($resultPlan->rowCount()==1) {
										$rowPlan=$resultPlan->fetch() ;
										$output.="<a style='pointer-events: auto' href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Planner/planner_view_full.php&viewBy=class&gibbonCourseClassID=" . $rowPeriods["gibbonCourseClassID"] . "&gibbonPlannerEntryID=" . $rowPlan["gibbonPlannerEntryID"] . "&search=$gibbonPersonID'><img style='float: right; margin: " . (substr($height,0,-2)-27) . "px 2px 0 0' title='View lesson: " . htmlPrep($rowPlan["name"]) . "' src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/plus.png'/></a>" ;
									}
								$output.="</div>" ;
								$zCount++ ;
							}
						}
					}
				}
			}
			
			$allDay=0 ;
			
			//Draw periods from school calendar
			if ($eventsSchool!=FALSE) {
				$height=0 ;
				$top=0 ;
				foreach ($eventsSchool AS $event) {
					if (date("Y-m-d",$event[2])==date("Y-m-d", ($startDayStamp+(86400*$count)))) {
						if ($event[1]=="All Day") {
							$label=$event[0] ;
							$title="" ;
							if (strlen($label)>20) {
								$label=substr($label, 0, 20) . "..." ;
								$title="title='" . $event[0] . "'" ;
							}
							$height="30px" ;
							$top=(($maxAllDays*-31)-8+($allDay*30)) . "px" ;
							$output.="<div class='ttSchoolCalendar' $title style='z-index: $zCount; position: absolute; top: $top; width: $width ; border: 1px solid #555; height: $height; margin: 0px; padding: 0px; opacity: $schoolCalendarAlpha'>" ;
								$output.="<a target=_blank style='color: #fff' href='" . $event[5] . "'>" . $label . "</a>" ;
							$output.="</div>" ;
							$allDay++ ;
						}
						else {
							$label=$event[0] ;
							$title="" ;
							if (strlen($label)>20) {
								$label=substr($label, 0, 20) . "..." ;
								$title="title='" . $event[0] . " (" . date("H:i", $event[2]) . " to " . date("H:i", $event[3]) . ")'" ;
							}
							$height=ceil(($event[3]-$event[2])/60) . "px" ;
							$top=(ceil(($event[2]-strtotime(date("Y-m-d", $startDayStamp+(86400*$count)) . " " . $dayTimeStart))/60+($startPad/60))) . "px" ;
							$output.="<div class='ttSchoolCalendar' $title style='z-index: $zCount; position: absolute; top: $top; width: $width ; border: 1px solid #555; height: $height; margin: 0px; padding: 0px; opacity: $schoolCalendarAlpha'>" ;
								$output.="<a target=_blank style='color: #fff' href='" . $event[5] . "'>" . $label . "</a>" ;
							$output.="</div>" ;
						}
						$zCount++ ;
					}
				}
			}
			
			//Draw periods from personal calendar
			if ($eventsPersonal!=FALSE) {
				$height=0 ;
				$top=0 ;
				$bg="rgba(103,153,207,$schoolCalendarAlpha)" ;
				foreach ($eventsPersonal AS $event) {
					if (date("Y-m-d",$event[2])==date("Y-m-d", ($startDayStamp+(86400*$count)))) {
						if ($event[1]=="All Day") {
							$label=$event[0] ;
							$title="" ;
							if (strlen($label)>20) {
								$label=substr($label, 0, 20) . "..." ;
								$title="title='" . $event[0] . "'" ;
							}
							$height="30px" ;
							$top=(($maxAllDays*-31)-8+($allDay*30)) . "px" ;
							$output.="<div class='ttPersonalCalendar' $title style='z-index: $zCount; position: absolute; top: $top; width: $width ; border: 1px solid #555; height: $height; margin: 0px; padding: 0px; opacity: $schoolCalendarAlpha'>" ;
								$output.="<a target=_blank style='color: #fff' href='" . $event[5] . "'>" . $label . "</a>" ;
							$output.="</div>" ;
							$allDay++ ;
						}
						else {
							$label=$event[0] ;
							$title="" ;
							if (strlen($label)>20) {
								$label=substr($label, 0, 20) . "..." ;
								$title="title='" . $event[0] . " (" . date("H:i", $event[2]) . " to " . date("H:i", $event[3]) . ")'" ;
							}
							$height=ceil(($event[3]-$event[2])/60) . "px" ;
							$top=(ceil(($event[2]-strtotime(date("Y-m-d", $startDayStamp+(86400*$count)) . " " . $dayTimeStart))/60+($startPad/60))) . "px" ;
							$output.="<div class='ttPersonalCalendar' $title style='z-index: $zCount; position: absolute; top: $top; width: $width ; border: 1px solid #555; height: $height; margin: 0px; padding: 0px; opacity: $schoolCalendarAlpha'>" ;
								$output.="<a target=_blank style='color: #fff' href='" . $event[5] . "'>" . $label . "</a>" ;
							$output.="</div>" ;
						}
						$zCount++ ;
					}
				}
			}
			
			//Draw space bookings
			if ($eventsSpaceBooking!=FALSE) {
				$height=0 ;
				$top=0 ;
				foreach ($eventsSpaceBooking AS $event) {
					if ($event[3]==date("Y-m-d", ($startDayStamp+(86400*$count)))) {
						$height=ceil((strtotime(date("Y-m-d", ($startDayStamp+(86400*$count))) . " " . $event[5])-strtotime(date("Y-m-d", ($startDayStamp+(86400*$count))) . " " . $event[4]))/60) . "px" ;
						$top=(ceil((strtotime($event[3] . " " . $event[4])-strtotime(date("Y-m-d", $startDayStamp+(86400*$count)) . " " . $dayTimeStart))/60+($startPad/60))) . "px" ;
						if ($height<45) {
							$label=$event[1] ;
							$title="title='" . substr($event[4],0,5) . "-" . substr($event[5],0,5) . "'";
						}
						else {
							$label=$event[1] . "<br/><span style='font-weight: normal'>(" . substr($event[4],0,5) ."-" . substr($event[5],0,5) . ")<br/></span>" ;
							$title="" ;
						}
						$output.="<div class='ttSpaceBookingCalendar' $title style='z-index: $zCount; position: absolute; top: $top; width: $width ; border: 1px solid #555; height: $height; margin: 0px; padding: 0px; opacity: $schoolCalendarAlpha'>" ;
							$output.=$label ;
						$output.="</div>" ;
						$zCount++ ;
					}
				}
			}
			$output.="</div>" ;
		}
	$output.="</td>" ;
	
	return $output ;
}


//TIMETABLE FOR ROOM
function renderTTSpace($guid, $connection2, $gibbonSpaceID, $gibbonTTID, $title="", $startDayStamp="", $q="", $params="") {
			
	$output="" ;
	$blank=TRUE ;
	if ($startDayStamp=="") {
		$startDayStamp=time() ;
	}
	$zCount=0 ;
	$top=0 ;
	
	//Find out which timetables I am involved in this year
	try {
		$data=array("gibbonSpaceID"=>$gibbonSpaceID, "gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
		$sql="SELECT DISTINCT gibbonTT.gibbonTTID, gibbonTT.name FROM gibbonTT JOIN gibbonTTDay ON (gibbonTT.gibbonTTID=gibbonTTDay.gibbonTTID) JOIN gibbonTTDayRowClass ON (gibbonTTDayRowClass.gibbonTTDayID=gibbonTTDay.gibbonTTDayID) JOIN gibbonCourseClass ON (gibbonTTDayRowClass.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) WHERE gibbonSpaceID=:gibbonSpaceID AND gibbonSchoolYearID=:gibbonSchoolYearID AND active='Y' " ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { 
		$output.="<div class='error'>" . $e->getMessage() . "</div>" ; 
	}
	
	//If I am not involved in any timetables display all within the year 
	if ($result->rowCount()==0) {
		try {
			$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
			$sql="SELECT gibbonTT.gibbonTTID, gibbonTT.name FROM gibbonTT WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND active='Y' " ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			$output.="<div class='error'>" . $e->getMessage() . "</div>" ; 
		}
	}
	
	//link to other TTs
	if ($result->rowcount()>1) {
		$output.="<table class='noIntBorder' style='width: 100%'>" ;
			$output.="<tr>" ; 
				$output.="<td>" ; 
					$output.="<span style='font-size: 115%; font-weight: bold'>" . _('Timetable Chooser') ."</span>: " ;
					while ($row=$result->fetch()) {
						$output.="<form method='post' action='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=$q" . $params . "&gibbonTTID=" . $row["gibbonTTID"] . "'>" ;
							$output.="<input name='ttDate' value='" . date($_SESSION[$guid]["i18n"]["dateFormatPHP"], $startDayStamp) . "' type='hidden'>" ;
							$output.="<input name='schoolCalendar' value='" . $_SESSION[$guid]["viewCalendarSchool"] . "' type='hidden'>" ;
							$output.="<input name='personalCalendar' value='" . $_SESSION[$guid]["viewCalendarPersonal"] . "' type='hidden'>" ;
							$output.="<input name='spaceBookingCalendar' value='" . $_SESSION[$guid]["viewCalendarSpaceBooking"] . "' type='hidden'>" ;
							$output.="<input name='fromTT' value='Y' type='hidden'>" ;
							$output.="<input class='buttonLink' style='min-width: 30px; margin-top: 0px; float: left' type='submit' value='" . $row["name"] . "'>" ;
						$output.="</form>" ;
					}
					try {
						$result=$connection2->prepare($sql);
						$result->execute($data);
					}
					catch(PDOException $e) { 
						$output.="<div class='error'>" . $e->getMessage() . "</div>" ; 
					}
				$output.="</td>" ; 
			$output.="</tr>" ; 
		$output.="</table>" ;
		
		if ($gibbonTTID!="") {
			$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonTTID"=>$gibbonTTID); 
			$sql="SELECT DISTINCT gibbonTT.gibbonTTID, gibbonTT.name FROM gibbonTT JOIN gibbonTTDay ON (gibbonTT.gibbonTTID=gibbonTTDay.gibbonTTID) JOIN gibbonTTDayRowClass ON (gibbonTTDayRowClass.gibbonTTDayID=gibbonTTDay.gibbonTTDayID) JOIN gibbonCourseClass ON (gibbonTTDayRowClass.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) WHERE gibbonSpaceID=$gibbonSpaceID AND gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonTT.gibbonTTID=:gibbonTTID" ;
		}
		try {
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			$output.="<div class='error'>" . $e->getMessage() . "</div>" ; 
		}
	}
	
	//Get space booking array
	$eventsSpaceBooking=FALSE ;
	if ($_SESSION[$guid]["viewCalendarSpaceBooking"]=="Y") {
		$eventsSpaceBooking=getSpaceBookingEventsSpace($guid, $connection2, $startDayStamp, $gibbonSpaceID) ;
	}	

	
	//Display first TT
	if ($result->rowCount()>0) {
		$row=$result->fetch() ;
		
		if ($title!=FALSE) {
			$output.="<h2>" . $row["name"] . "</h2>" ;
		}
		
		$output.="<table cellspacing='0' class='noIntBorder' cellspacing='0' style='width: 100%; margin: 10px 0 10px 0'>" ;	
			$output.="<tr>" ;
				$output.="<td style='vertical-align: top'>" ; 
					$output.="<form method='post' action='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=$q" . $params . "&gibbonTTID=" . $row["gibbonTTID"] . "'>" ;
						$output.="<input name='ttDate' maxlength=10 value='" . date($_SESSION[$guid]["i18n"]["dateFormatPHP"], ($startDayStamp-(7*24*60*60))) . "' type='hidden'>" ;
						$output.="<input name='schoolCalendar' value='" . $_SESSION[$guid]["viewCalendarSchool"] . "' type='hidden'>" ;
						$output.="<input name='personalCalendar' value='" . $_SESSION[$guid]["viewCalendarPersonal"] . "' type='hidden'>" ;
						$output.="<input name='spaceBookingCalendar' value='" . $_SESSION[$guid]["viewCalendarSpaceBooking"] . "' type='hidden'>" ;
						$output.="<input name='fromTT' value='Y' type='hidden'>" ;
						$output.="<input class='buttonLink' style='min-width: 30px; margin-top: 0px; float: left' type='submit' value='" . _('Last Week') . "'>" ;
					$output.="</form>" ;
					$output.="<form method='post' action='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=$q" . $params . "&gibbonTTID=" . $row["gibbonTTID"] . "'>" ;
						$output.="<input name='ttDate' value='" . date($_SESSION[$guid]["i18n"]["dateFormatPHP"], ($startDayStamp+(7*24*60*60))) . "' type='hidden'>" ;
						$output.="<input name='schoolCalendar' value='" . $_SESSION[$guid]["viewCalendarSchool"] . "' type='hidden'>" ;
						$output.="<input name='personalCalendar' value='" . $_SESSION[$guid]["viewCalendarPersonal"] . "' type='hidden'>" ;
						$output.="<input name='spaceBookingCalendar' value='" . $_SESSION[$guid]["viewCalendarSpaceBooking"] . "' type='hidden'>" ;
						$output.="<input name='fromTT' value='Y' type='hidden'>" ;
						$output.="<input class='buttonLink' style='min-width: 30px; margin-top: 0px; float: left' type='submit' value='" . _('Next Week') . "'>" ;
					$output.="</form>" ;
				$output.="</td>" ; 
				$output.="<td style='vertical-align: top; text-align: right'>" ; 
					$output.="<form method='post' action='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=$q" . $params . "&gibbonTTID=" . $row["gibbonTTID"] . "'>" ;
						$output.="<input name='ttDate' id='ttDate' maxlength=10 value='" . date($_SESSION[$guid]["i18n"]["dateFormatPHP"], $startDayStamp) . "' type='text' style='height: 22px; width:100px; margin-right: 0px; float: none'>" ;
						$output.="<script type=\"text/javascript\">" ;
							$output.="var ttDate=new LiveValidation('ttDate');" ;
							$output.="ttDate.add( Validate.Format, {pattern: " ; if ($_SESSION[$guid]["i18n"]["dateFormatRegEx"]=="") {  $output.="/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i" ; } else { $output.=$_SESSION[$guid]["i18n"]["dateFormatRegEx"] ; } $output.=", failureMessage: \"Use " ; if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { $output.="dd/mm/yyyy" ; } else { $output.=$_SESSION[$guid]["i18n"]["dateFormat"] ; } $output.=".\" } );" ;
							$output.="ttDate.add(Validate.Presence);" ;
						 $output.="</script>" ;
						 $output.="<script type=\"text/javascript\">" ;
							$output.="$(function() {" ;
								$output.="$(\"#ttDate\").datepicker();" ;
							$output.="});" ;
						$output.="</script>" ;
						$output.="<input style='margin-top: 0px; margin-right: -2px' type='submit' value='" . _('Go') . "'>" ;
						$output.="<input name='schoolCalendar' value='" . $_SESSION[$guid]["viewCalendarSchool"] . "' type='hidden'>" ;
						$output.="<input name='personalCalendar' value='" . $_SESSION[$guid]["viewCalendarPersonal"] . "' type='hidden'>" ;
						$output.="<input name='spaceBookingCalendar' value='" . $_SESSION[$guid]["viewCalendarSpaceBooking"] . "' type='hidden'>" ;
						$output.="<input name='fromTT' value='Y' type='hidden'>" ;	
					$output.="</form>" ;
				$output.="</td>" ;
			$output.="</tr>" ;
		$output.="</table>" ;

		//Count back to first Monday before first day
		while (date("D",$startDayStamp)!="Mon") {
			$startDayStamp=$startDayStamp-86400 ;
		}
					
		//Check which days are school days
		$daysInWeek=0;
		$days=array() ;
		$timeStart="" ;
		$timeEnd="" ;
		$days["Mon"]="N" ;
		$days["Tue"]="N" ;
		$days["Wed"]="N" ;
		$days["Thu"]="N" ;
		$days["Fri"]="N" ;
		$days["Sat"]="N" ;
		$days["Sun"]="N" ;
		try {
			$dataDays=array(); 
			$sqlDays="SELECT * FROM gibbonDaysOfWeek WHERE schoolDay='Y'" ;
			$resultDays=$connection2->prepare($sqlDays);
			$resultDays->execute($dataDays);
		}
		catch(PDOException $e) { 
			$output.="<div class='error'>" . $e->getMessage() . "</div>" ; 
		}
		while ($rowDays=$resultDays->fetch()) {
			//Max diff time for week based on days of week
			if ($timeStart=="") {
				$timeStart=$rowDays["schoolStart"] ;
			}
			if ($rowDays["schoolStart"]<$timeStart) {
				$timeStart=$rowDays["schoolStart"] ;
			}
			if ($timeEnd=="") {
				$timeEnd=$rowDays["schoolEnd"] ;
			}
			if ($rowDays["schoolEnd"]>$timeEnd) {
				$timeEnd=$rowDays["schoolEnd"] ;
			}
			
			//See which days are school days
			if ($rowDays["nameShort"]=="Mon") {
				$days["Mon"]="Y" ;
				$daysInWeek++ ;
			}
			else if ($rowDays["nameShort"]=="Tue") {
				$days["Tue"]="Y" ;
				$daysInWeek++ ;
			}
			else if ($rowDays["nameShort"]=="Wed") {
				$days["Wed"]="Y" ;
				$daysInWeek++ ;
			}
			else if ($rowDays["nameShort"]=="Thu") {
				$days["Thu"]="Y" ;
				$daysInWeek++ ;
			}
			else if ($rowDays["nameShort"]=="Fri") {
				$days["Fri"]="Y" ;
				$daysInWeek++ ;
			}
			else if ($rowDays["nameShort"]=="Sat") {
				$days["Sat"]="Y" ;
				$daysInWeek++ ;
			}
			else if ($rowDays["nameShort"]=="Sun") {
				$days["Sun"]="Y" ;
				$daysInWeek++ ;
			}
		}
		
		//Count forward to the end of the week
		$endDayStamp=$startDayStamp+(86400*$daysInWeek) ;
		
		$schoolCalendarAlpha=0.85 ;
		$ttAlpha=1.0 ;
		
		if ($_SESSION[$guid]["viewCalendarSpaceBooking"]!="N") {
			$ttAlpha=0.75 ;
		}
		
		
		//Max diff time for week based on timetables
		try {
			$dataDiff=array("date1"=>date("Y-m-d", ($startDayStamp+(86400*0))),"date2"=>date("Y-m-d", ($endDayStamp+(86400*1))), "gibbonTTID"=>$row["gibbonTTID"]); 
			$sqlDiff="SELECT DISTINCT gibbonTTColumn.gibbonTTColumnID FROM gibbonTTDay JOIN gibbonTTDayDate ON (gibbonTTDay.gibbonTTDayID=gibbonTTDayDate.gibbonTTDayID) JOIN gibbonTTColumn ON (gibbonTTDay.gibbonTTColumnID=gibbonTTColumn.gibbonTTColumnID) WHERE (date>=:date1 AND date<=:date2) AND gibbonTTID=:gibbonTTID" ;
			$resultDiff=$connection2->prepare($sqlDiff);
			$resultDiff->execute($dataDiff);
		}
		catch(PDOException $e) { 
			$output.="<div class='error'>" . $e->getMessage() . "</div>" ; 
		}
		while ($rowDiff=$resultDiff->fetch()) {
			try {
				$dataDiffDay=array("gibbonTTColumnID"=>$rowDiff["gibbonTTColumnID"]); 
				$sqlDiffDay="SELECT * FROM gibbonTTColumnRow WHERE gibbonTTColumnID=:gibbonTTColumnID ORDER BY timeStart" ;
				$resultDiffDay=$connection2->prepare($sqlDiffDay);
				$resultDiffDay->execute($dataDiffDay);
			}
			catch(PDOException $e) { 
				$output.="<div class='error'>" . $e->getMessage() . "</div>" ; 
			}
			while ($rowDiffDay=$resultDiffDay->fetch()) {
				if ($rowDiffDay["timeStart"]<$timeStart) {
					$timeStart=$rowDiffDay["timeStart"] ;
				}
				if ($rowDiffDay["timeEnd"]>$timeEnd) {
					$timeEnd=$rowDiffDay["timeEnd"] ;
				}
			}
		}
		
		//Max diff time for week based on special days timing change
		try {
			$dataDiff=array("date1"=>date("Y-m-d", ($startDayStamp+(86400*0))), "date2"=>date("Y-m-d", ($startDayStamp+(86400*6)))); 
			$sqlDiff="SELECT * FROM gibbonSchoolYearSpecialDay WHERE date>=:date1 AND date<=:date2 AND type='Timing Change' AND NOT schoolStart IS NULL AND NOT schoolEnd IS NULL" ;
			$resultDiff=$connection2->prepare($sqlDiff);
			$resultDiff->execute($dataDiff);
		}
		catch(PDOException $e) { 
			$output.="<div class='error'>" . $e->getMessage() . "</div>" ; 
		}
		
		while ($rowDiff=$resultDiff->fetch()) {
			if ($rowDiff["schoolStart"]<$timeStart) {
				$timeStart=$rowDiff["schoolStart"] ;
			}
			if ($rowDiff["schoolEnd"]>$timeEnd) {
				$timeEnd=$rowDiff["schoolEnd"] ;
			}
		}
		
		//Max diff based on space booking events
		if ($eventsSpaceBooking!=FALSE) {
			foreach ($eventsSpaceBooking as $event) {
				if ($event[3]<=date("Y-m-d", ($startDayStamp+(86400*6)))) {
					if ($event[4]<$timeStart) {
						$timeStart=$event[4] ;
					}
					if ($event[5]>$timeEnd) {
						$timeEnd=$event[5] ;				
					}
				}
			}
		}
		
		//Final calc
		$diffTime=strtotime($timeEnd)-strtotime($timeStart) ;
		$width=(ceil(690/$daysInWeek)-20) . "px" ;
		
		$count=0;
		
		$output.="<table cellspacing='0' class='mini' cellspacing='0' style='width: 750px; margin: 0px 0px 30px 0px;'>" ;
			//Spit out controls for displaying calendars
			if ($_SESSION[$guid]["viewCalendarSpaceBooking"]!="") {
				$output.="<tr class='head' style='height: 37px;'>" ;
					$output.="<th class='ttCalendarBar' colspan=" . ($daysInWeek+1) . ">" ;
						$output.="<form method='post' action='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=$q" . $params . "' style='padding: 5px 5px 0 0'>" ;
							if ($_SESSION[$guid]["viewCalendarSpaceBooking"]!="") {
								$checked="" ;
								if ($_SESSION[$guid]["viewCalendarSpaceBooking"]=="Y") {
									$checked="checked" ;
								}
								$output.="<span class='ttSpaceBookingCalendar' style='opacity: $schoolCalendarAlpha'>" . _('Space Booking') . " " ;
								$output.="<input $checked style='margin-left: 3px' type='checkbox' name='spaceBookingCalendar' onclick='submit();'/>" ;
								$output.="</span>" ;
							}
							
							$output.="<input type='hidden' name='ttDate' value='" . date("d/m/Y", $startDayStamp) . "'>" ;
							$output.="<input name='fromTT' value='Y' type='hidden'>" ;
						$output.="</form>" ;
					$output.="</th>" ;
				$output.="</tr>" ;
			}
			
		
			$output.="<tr class='head'>" ;
				$output.="<th style='vertical-align: top; width: 70px; text-align: center'>" ;
					//Calculate week number
					$week=getWeekNumber ($startDayStamp, $connection2, $guid) ;
					if ($week!=false) {
						$output.=sprintf(_('Week %1$s'), $week) . "<br/>" ;
					}
					$output.="<span style='font-weight: normal; font-style: italic;'>" . _('Time') ."<span>" ;
				$output.="</th>" ;
				if ($days["Mon"]=="Y") {
					$output.="<th style='vertical-align: top; text-align: center; width: " . (550/$daysInWeek) . "px'>" ;
						$output.=_("Mon") . "<br/>" ;
						$output.="<span style='font-size: 80%; font-style: italic'>". date("d/m", ($startDayStamp+(86400*0))) . "</span><br/>" ;
						try {
							$dataSpecial=array("date"=>date("Y-m-d", ($startDayStamp+(86400*0)))); 
							$sqlSpecial="SELECT * FROM gibbonSchoolYearSpecialDay WHERE date=:date AND type='Timing Change'" ;
							$resultSpecial=$connection2->prepare($sqlSpecial);
							$resultSpecial->execute($dataSpecial);
						}
						catch(PDOException $e) { 
							$output.="<div class='error'>" . $e->getMessage() . "</div>" ; 
						}
						if ($resultSpecial->rowCount()==1) {
							$rowSpecial=$resultSpecial->fetch() ;
							$output.="<span style='font-size: 80%; font-weight: bold'><u>". $rowSpecial["name"] . "</u></span>" ;
						}
					$output.="</th>" ;
				}
				if ($days["Tue"]=="Y") {	
					$output.="<th style='vertical-align: top; text-align: center; width: " . (550/$daysInWeek) . "px'>" ;
						$output.=_("Tue") . "<br/>" ;
						$output.="<span style='font-size: 80%; font-style: italic'>". date("d/m", ($startDayStamp+(86400*1))) . "</span><br/>" ;
						try {
							$dataSpecial=array("date"=>date("Y-m-d", ($startDayStamp+(86400*1)))); 
							$sqlSpecial="SELECT * FROM gibbonSchoolYearSpecialDay WHERE date=:date AND type='Timing Change'" ;
							$resultSpecial=$connection2->prepare($sqlSpecial);
							$resultSpecial->execute($dataSpecial);
						}
						catch(PDOException $e) { 
							$output.="<div class='error'>" . $e->getMessage() . "</div>" ; 
						}
						if ($resultSpecial->rowCount()==1) {
							$rowSpecial=$resultSpecial->fetch() ;
							$output.="<span style='font-size: 80%; font-weight: bold'><u>". $rowSpecial["name"] . "</u></span>" ;
						}
					$output.="</th>" ;
				}
				if ($days["Wed"]=="Y") {
					$output.="<th style='vertical-align: top; text-align: center; width: " . (550/$daysInWeek) . "px'>" ;
						$output.=_("Wed") . "<br/>" ;
						$output.="<span style='font-size: 80%; font-style: italic'>". date("d/m", ($startDayStamp+(86400*2))) . "</span><br/>" ;
						try {
							$dataSpecial=array("date"=>date("Y-m-d", ($startDayStamp+(86400*2)))); 
							$sqlSpecial="SELECT * FROM gibbonSchoolYearSpecialDay WHERE date=:date AND type='Timing Change'" ;
							$resultSpecial=$connection2->prepare($sqlSpecial);
							$resultSpecial->execute($dataSpecial);
						}
						catch(PDOException $e) { 
							$output.="<div class='error'>" . $e->getMessage() . "</div>" ; 
						}
						if ($resultSpecial->rowCount()==1) {
							$rowSpecial=$resultSpecial->fetch() ;
							$output.="<span style='font-size: 80%; font-weight: bold'><u>". $rowSpecial["name"] . "</u></span>" ;
						}
					$output.="</th>" ;
				}
				if ($days["Thu"]=="Y") {
					$output.="<th style='vertical-align: top; text-align: center; width: " . (550/$daysInWeek) . "px'>" ;
						$output.=_("Thu") . "<br/>" ;
						$output.="<span style='font-size: 80%; font-style: italic'>". date("d/m", ($startDayStamp+(86400*3))) . "</span><br/>" ;
						try {
							$dataSpecial=array("date"=>date("Y-m-d", ($startDayStamp+(86400*3)))); 
							$sqlSpecial="SELECT * FROM gibbonSchoolYearSpecialDay WHERE date=:date AND type='Timing Change'" ;
							$resultSpecial=$connection2->prepare($sqlSpecial);
							$resultSpecial->execute($dataSpecial);
						}
						catch(PDOException $e) { 
							$output.="<div class='error'>" . $e->getMessage() . "</div>" ; 
						}
						if ($resultSpecial->rowCount()==1) {
							$rowSpecial=$resultSpecial->fetch() ;
							$output.="<span style='font-size: 80%; font-weight: bold'><u>". $rowSpecial["name"] . "</u></span>" ;
						}
					$output.="</th>" ;
				}
				if ($days["Fri"]=="Y") {
					$output.="<th style='vertical-align: top; text-align: center; width: " . (550/$daysInWeek) . "px'>" ;
						$output.=_("Fri") . "<br/>" ;
						$output.="<span style='font-size: 80%; font-style: italic'>". date("d/m", ($startDayStamp+(86400*4))) . "</span><br/>" ;
						try {
							$dataSpecial=array("date"=>date("Y-m-d", ($startDayStamp+(86400*4)))); 
							$sqlSpecial="SELECT * FROM gibbonSchoolYearSpecialDay WHERE date=:date AND type='Timing Change'" ;
							$resultSpecial=$connection2->prepare($sqlSpecial);
							$resultSpecial->execute($dataSpecial);
						}
						catch(PDOException $e) { 
							$output.="<div class='error'>" . $e->getMessage() . "</div>" ; 
						}
						if ($resultSpecial->rowCount()==1) {
							$rowSpecial=$resultSpecial->fetch() ;
							$output.="<span style='font-size: 80%; font-weight: bold'><u>". $rowSpecial["name"] . "</u></span>" ;
						}
					$output.="</th>" ;
				}
				if ($days["Sat"]=="Y") {
					$output.="<th style='vertical-align: top; text-align: center; width: " . (550/$daysInWeek) . "px'>" ;
						$output.=_("Sat") . "<br/>" ;
						$output.="<span style='font-size: 80%; font-style: italic'>". date("d/m", ($startDayStamp+(86400*5))) . "</span><br/>" ;
						try {
							$dataSpecial=array("date"=>date("Y-m-d", ($startDayStamp+(86400*5)))); 
							$sqlSpecial="SELECT * FROM gibbonSchoolYearSpecialDay WHERE date=:date AND type='Timing Change'" ;
							$resultSpecial=$connection2->prepare($sqlSpecial);
							$resultSpecial->execute($dataSpecial);
						}
						catch(PDOException $e) { 
							$output.="<div class='error'>" . $e->getMessage() . "</div>" ; 
						}
						if ($resultSpecial->rowCount()==1) {
							$rowSpecial=$resultSpecial->fetch() ;
							$output.="<span style='font-size: 80%; font-weight: bold'><u>". $rowSpecial["name"] . "</u></span>" ;
						}
					$output.="</th>" ;
				}
				if ($days["Sun"]=="Y") {
					$output.="<th style='vertical-align: top; text-align: center; width: " . (550/$daysInWeek) . "px'>" ;
						$output.=_("Sun") . "<br/>" ;
						$output.="<span style='font-size: 80%; font-style: italic'>". date("d/m", ($startDayStamp+(86400*6))) . "</span><br/>" ;
						try {
							$dataSpecial=array("date"=>date("Y-m-d", ($startDayStamp+(86400*6)))); 
							$sqlSpecial="SELECT * FROM gibbonSchoolYearSpecialDay WHERE date=:date AND type='Timing Change'" ;
							$resultSpecial=$connection2->prepare($sqlSpecial);
							$resultSpecial->execute($dataSpecial);
						}
						catch(PDOException $e) { 
							$output.="<div class='error'>" . $e->getMessage() . "</div>" ; 
						}
						if ($resultSpecial->rowCount()==1) {
							$rowSpecial=$resultSpecial->fetch() ;
							$output.="<span style='font-size: 80%; font-weight: bold'><u>". $rowSpecial["name"] . "</u></span>" ;
						}
					$output.="</th>" ;
				}
			$output.="</tr>" ;
			
			$output.="<tr style='height:" . (ceil($diffTime/60)+14) . "px'>" ;
				$output.="<td style='height: 300px; width: 75px; text-align: center; vertical-align: top'>" ;
					$output.="<div style='position: relative; width: 71px'>" ;
						$countTime=0 ;
						$time=$timeStart ;
						$output.="<div $title style='position: absolute; top: -3px; width: 71px ; border: none; height: 60px; margin: 0px; padding: 0px; font-size: 92%'>" ;
							$output.=substr($time,0,5) . "<br/>" ;
						$output.="</div>" ;
						$time=date("H:i:s", strtotime($time)+3600) ;
						$spinControl=0 ;
						while ($time<=$timeEnd AND $spinControl<@(23-date("H",$timeStart))) {
							$countTime++ ;
							$output.="<div $title style='position: absolute; top:" . (($countTime*60)-5) . "px ; width: 71px ; border: none; height: 60px; margin: 0px; padding: 0px; font-size: 92%'>" ;
								$output.=substr($time,0,5) . "<br/>" ;
							$output.="</div>" ;
							$time=date("H:i:s", strtotime($time)+3600) ;
							$spinControl++ ;
						}
						
					$output.="</div>" ;
				$output.="</td>" ;
				
				//Check to see if week is at all in term time...if it is, then display the grid
				$isWeekInTerm=FALSE ;
				try {
					$dataTerm=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
					$sqlTerm="SELECT gibbonSchoolYearTerm.firstDay, gibbonSchoolYearTerm.lastDay FROM gibbonSchoolYearTerm, gibbonSchoolYear WHERE gibbonSchoolYearTerm.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID AND gibbonSchoolYear.gibbonSchoolYearID=:gibbonSchoolYearID" ; 
					$resultTerm=$connection2->prepare($sqlTerm);
					$resultTerm->execute($dataTerm);
				}
				catch(PDOException $e) { 
					$output.="<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				$weekStart=date("Y-m-d", ($startDayStamp+(86400*0))) ;
				$weekEnd=date("Y-m-d", ($startDayStamp+(86400*6))) ;
				while ($rowTerm=$resultTerm->fetch()) {
					if ($weekStart<=$rowTerm["firstDay"] AND $weekEnd>=$rowTerm["firstDay"]) {
						$isWeekInTerm=TRUE ;
					}
					else if ($weekStart>=$rowTerm["firstDay"] AND $weekEnd<=$rowTerm["lastDay"]) {
						$isWeekInTerm=TRUE ;
					}
					else if ($weekStart<=$rowTerm["lastDay"] AND $weekEnd>=$rowTerm["lastDay"]) {
						$isWeekInTerm=TRUE ;
					}
				}
				if ($isWeekInTerm==TRUE) {
					$blank=FALSE ;
				}
				
				//Run through days of the week
				$dayOfWeek="" ;
				for ($d=0; $d<7; $d++) {
					$day="" ;
					if ($d==0) { $dayOfWeek="Mon" ; }
					else if ($d==1) { $dayOfWeek="Tue" ; }
					else if ($d==2) { $dayOfWeek="Wed" ; }
					else if ($d==3) { $dayOfWeek="Thu" ; }
					else if ($d==4) { $dayOfWeek="Fri" ; }
					else if ($d==5) { $dayOfWeek="Sat" ; }
					else if ($d==6) { $dayOfWeek="Sun" ; }
					
					if ($days[$dayOfWeek]=="Y") {
						//Check to see if day is term time
						$isDayInTerm=FALSE ;
						try {
							$dataTerm=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
							$sqlTerm="SELECT gibbonSchoolYearTerm.firstDay, gibbonSchoolYearTerm.lastDay FROM gibbonSchoolYearTerm, gibbonSchoolYear WHERE gibbonSchoolYearTerm.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID AND gibbonSchoolYear.gibbonSchoolYearID=:gibbonSchoolYearID" ; 
							$resultTerm=$connection2->prepare($sqlTerm);
							$resultTerm->execute($dataTerm);
						}
						catch(PDOException $e) { 
							$output.="<div class='error'>" . $e->getMessage() . "</div>" ; 
						}
						while ($rowTerm=$resultTerm->fetch()) {
							if (date("Y-m-d", ($startDayStamp+(86400*$count)))>=$rowTerm["firstDay"] AND date("Y-m-d", ($startDayStamp+(86400*$count)))<=$rowTerm["lastDay"]) {
								$isDayInTerm=TRUE ;
							}
						}
						if ($isDayInTerm==TRUE) {
							//Check for school closure day
							try {
								$dataClosure=array("date"=>date("Y-m-d", ($startDayStamp+(86400*$count)))); 
								$sqlClosure="SELECT * FROM gibbonSchoolYearSpecialDay WHERE date=:date" ;
								$resultClosure=$connection2->prepare($sqlClosure);
								$resultClosure->execute($dataClosure);
							}
							catch(PDOException $e) { 
								$output.="<div class='error'>" . $e->getMessage() . "</div>" ; 
							}

							if ($resultClosure->rowCount()==1) {
								$rowClosure=$resultClosure->fetch() ;
								if ($rowClosure["type"]=="School Closure") {
									$day=$day . "<td style='text-align: center; vertical-align: top; font-size: 11px'>" ;
										$day=$day . "<div style='position: relative'>" ;
											$day=$day . "<div class='ttClosure' style='z-index: $zCount; position: absolute; width: $width ; height: " . ceil($diffTime/60) . "px; margin: 0px; padding: 0px; opacity: $ttAlpha'>" ;
												$day=$day . "<div style='position: relative; top: 50%'>" ;
													$day=$day . "<span>" . $rowClosure["name"] . "</span>" ;
												$day=$day . "</div>" ;
											$day=$day . "</div>" ;
										$day=$day . "</div>" ;
									$day=$day . "</td>" ;
								}
								else if ($rowClosure["type"]=="Timing Change") {
									$day=renderTTSpaceDay($guid, $connection2, $row["gibbonTTID"], $startDayStamp, $count, $daysInWeek, $gibbonSpaceID, $timeStart, $diffTime, $eventsSpaceBooking, $rowClosure["schoolStart"], $rowClosure["schoolEnd"]) ;
								}
							}
							else {
								$day=renderTTSpaceDay($guid, $connection2, $row["gibbonTTID"], $startDayStamp, $count, $daysInWeek, $gibbonSpaceID, $timeStart, $diffTime, $eventsSpaceBooking) ;
							}
						}
						else {
							$day=$day . "<td style='text-align: center; vertical-align: top; font-size: 11px'>" ;
								$day=$day . "<div style='position: relative'>" ;
									$day=$day . "<div class='ttClosure' style='z-index: $zCount; position: absolute; top: $top; width: $width; height: " . ceil($diffTime/60) . "px; margin: 0px; padding: 0px; opacity: $ttAlpha'>" ;
										$day=$day . "<div style='position: relative; top: 50%'>" ;
											$day=$day . "<span style='color: rgba(255,0,0,$ttAlpha);'>" . _('School Closed') . "</span>" ;
										$day=$day . "</div>" ;
									$day=$day . "</div>" ;
								$day=$day . "</div>" ;
							$day=$day . "</td>" ;
						}
						
						if ($day=="") {
							$day=$day . "<td style='text-align: center; vertical-align: top; font-size: 11px'></td>" ;
						}
						
						$output.=$day ;
								
						$count++ ;
					}
				}
				
			$output.="</tr>" ;
		$output.="</table>" ;
	}
	
	return $output ;
}

function renderTTSpaceDay($guid, $connection2, $gibbonTTID, $startDayStamp, $count, $daysInWeek, $gibbonSpaceID, $gridTimeStart, $diffTime, $eventsSpaceBooking, $specialDayStart="", $specialDayEnd="") {
	$schoolCalendarAlpha=0.85 ;
	$ttAlpha=1.0 ;
	
	if ($_SESSION[$guid]["viewCalendarSpaceBooking"]!="N") {
		$ttAlpha=0.75 ;
	}
	
	$date=date("Y/m/d", ($startDayStamp+(86400*$count))) ;
	
	$output="" ;
	$blank=TRUE ;
	
	//Make array of space changes
	$spaceChanges=array() ;
	try {
		$dataSpaceChange=array("date"=>date("Y-m-d", ($startDayStamp+(86400*$count)))); 
		$sqlSpaceChange="SELECT gibbonTTSpaceChange.*, gibbonSpace.name AS space, phoneInternal FROM gibbonTTSpaceChange LEFT JOIN gibbonSpace ON (gibbonTTSpaceChange.gibbonSpaceID=gibbonSpace.gibbonSpaceID) WHERE date=:date" ;
		$resultSpaceChange=$connection2->prepare($sqlSpaceChange);
		$resultSpaceChange->execute($dataSpaceChange);
	}
	catch(PDOException $e) { }
	while ($rowSpaceChange=$resultSpaceChange->fetch()) {
		$spaceChanges[$rowSpaceChange["gibbonTTDayRowClassID"]][0]=$rowSpaceChange["space"] ;
		$spaceChanges[$rowSpaceChange["gibbonTTDayRowClassID"]][1]=$rowSpaceChange["phoneInternal"] ;
	}
	
	//Get day start and end!
	$dayTimeStart="" ;
	$dayTimeEnd="" ;
	try {
		$dataDiff=array("date"=>date("Y-m-d", ($startDayStamp+(86400*$count))), "gibbonTTID"=>$gibbonTTID); 
		$sqlDiff="SELECT timeStart, timeEnd FROM gibbonTTDay JOIN gibbonTTDayDate ON (gibbonTTDay.gibbonTTDayID=gibbonTTDayDate.gibbonTTDayID) JOIN gibbonTTColumn ON (gibbonTTDay.gibbonTTColumnID=gibbonTTColumn.gibbonTTColumnID) JOIN gibbonTTColumnRow ON (gibbonTTColumn.gibbonTTColumnID=gibbonTTColumnRow.gibbonTTColumnID) WHERE date=:date AND gibbonTTID=:gibbonTTID" ;
		$resultDiff=$connection2->prepare($sqlDiff);
		$resultDiff->execute($dataDiff);
	}
	catch(PDOException $e) { 
		$output.="<div class='error'>" . $e->getMessage() . "</div>" ; 
	}
	while ($rowDiff=$resultDiff->fetch()) {
		if ($dayTimeStart=="") {
			$dayTimeStart=$rowDiff["timeStart"] ;
		}
		if ($rowDiff["timeStart"]<$dayTimeStart) {
			$dayTimeStart=$rowDiff["timeStart"] ;
		}
		if ($dayTimeEnd=="") {
			$dayTimeEnd=$rowDiff["timeEnd"] ;
		}
		if ($rowDiff["timeEnd"]>$dayTimeEnd) {
			$dayTimeEnd=$rowDiff["timeEnd"] ;
		}
	}
	if ($specialDayStart!="") {
		$dayTimeStart=$specialDayStart ;
	}
	if ($specialDayEnd!="") {
		$dayTimeEnd=$specialDayEnd ;
	}
	
	$dayDiffTime=strtotime($dayTimeEnd)-strtotime($dayTimeStart) ;
	
	$startPad=strtotime($dayTimeStart)-strtotime($gridTimeStart);
	
	$output.="<td style='text-align: center; vertical-align: top; font-size: 11px'>" ;
		try {
			$dataDay=array("date"=>date("Y-m-d", ($startDayStamp+(86400*$count))), "gibbonTTID"=>$gibbonTTID); 
			$sqlDay="SELECT gibbonTTDay.gibbonTTDayID FROM gibbonTTDayDate JOIN gibbonTTDay ON (gibbonTTDayDate.gibbonTTDayID=gibbonTTDay.gibbonTTDayID) WHERE gibbonTTID=:gibbonTTID AND date=:date" ;
			$resultDay=$connection2->prepare($sqlDay);
			$resultDay->execute($dataDay);
		}
		catch(PDOException $e) { 
			$output.="<div class='error'>" . $e->getMessage() . "</div>" ; 
		}

		if ($resultDay->rowCount()==1) {
			$rowDay=$resultDay->fetch() ;
			$zCount=0 ;
			$output.="<div style='position: relative'>" ;
			
			//Draw outline of the day
			try {
				$dataPeriods=array("gibbonTTDayID"=>$rowDay["gibbonTTDayID"], "date"=>date("Y-m-d", ($startDayStamp+(86400*$count)))); 
				$sqlPeriods="SELECT gibbonTTColumnRow.name, timeStart, timeEnd, type, date FROM gibbonTTDay JOIN gibbonTTDayDate ON (gibbonTTDay.gibbonTTDayID=gibbonTTDayDate.gibbonTTDayID) JOIN gibbonTTColumn ON (gibbonTTDay.gibbonTTColumnID=gibbonTTColumn.gibbonTTColumnID) JOIN gibbonTTColumnRow ON (gibbonTTColumnRow.gibbonTTColumnID=gibbonTTColumn.gibbonTTColumnID) WHERE gibbonTTDayDate.gibbonTTDayID=:gibbonTTDayID AND date=:date ORDER BY timeStart, timeEnd" ;
				$resultPeriods=$connection2->prepare($sqlPeriods);
				$resultPeriods->execute($dataPeriods);
			}
			catch(PDOException $e) { 
				$output.="<div class='error'>" . $e->getMessage() . "</div>" ; 
			}
			while ($rowPeriods=$resultPeriods->fetch()) {
				$isSlotInTime=FALSE ;
				if ($rowPeriods["timeStart"]<=$dayTimeStart AND $rowPeriods["timeEnd"]>$dayTimeStart) {
					$isSlotInTime=TRUE ;
				}
				else if ($rowPeriods["timeStart"]>=$dayTimeStart AND $rowPeriods["timeEnd"]<=$dayTimeEnd) {
					$isSlotInTime=TRUE ;
				}
				else if ($rowPeriods["timeStart"]<$dayTimeEnd AND $rowPeriods["timeEnd"]>=$dayTimeEnd) {
					$isSlotInTime=TRUE ;
				}
				
				if ($isSlotInTime==TRUE) {
					$effectiveStart=$rowPeriods["timeStart"] ;
					$effectiveEnd=$rowPeriods["timeEnd"] ;
					if ($dayTimeStart>$rowPeriods["timeStart"]) {
						$effectiveStart=$dayTimeStart ;
					}
					if ($dayTimeEnd<$rowPeriods["timeEnd"]) {
						$effectiveEnd=$dayTimeEnd ;
					}
					
					$width=(ceil(690/$daysInWeek)-20) . "px" ;
					$height=ceil((strtotime($effectiveEnd)-strtotime($effectiveStart))/60) . "px" ;
					$top=ceil(((strtotime($effectiveStart)-strtotime($dayTimeStart))+$startPad)/60) . "px" ;
					$title="" ;
					if ($rowPeriods["type"]!="Lesson" AND $height>15 AND $height<30) {
						$title="title='" . substr($effectiveStart,0,5) . " - " . substr($effectiveEnd,0,5) . "'" ;
					}
					else if ($rowPeriods["type"]!="Lesson" AND $height<=15) {
						$title="title='" . $rowPeriods["name"] . " (" . substr($effectiveStart,0,5) . "-" . substr($effectiveEnd,0,5) . ")'" ;
					}
					$class="ttGeneric" ;
					if ((date("H:i:s")>$effectiveStart) AND (date("H:i:s")<$effectiveEnd) AND $rowPeriods["date"]==date("Y-m-d")) {
						$class="ttCurrent" ;
					}
					$style="" ;
					if ($rowPeriods["type"]=="Lesson") {
						$class='ttLesson' ;
					}
					$output.="<div class='$class' $title style='z-index: $zCount; position: absolute; top: $top; width: $width; height: $height; margin: 0px; padding: 0px; opacity: $ttAlpha'>" ;
					if ($height>15 AND $height<30) {
						$output.=$rowPeriods["name"] . "<br/>" ;
					}
					else if ($height>=30) {
						$output.=$rowPeriods["name"] . "<br/>" ;
						$output.="<i>" . substr($effectiveStart,0,5) . "-" . substr($effectiveEnd,0,5) . "</i><br/>" ;
					}
					$output.="</div>" ;
					$zCount++ ;
				}
			}
			
			//Draw periods from TT
			try {
				$dataPeriods=array("gibbonTTDayID"=>$rowDay["gibbonTTDayID"], "gibbonSpaceID"=>$gibbonSpaceID, "gibbonTTDayID1"=>$rowDay["gibbonTTDayID"], "gibbonSpaceID1"=>$gibbonSpaceID, "date"=>date("Y-m-d", ($startDayStamp+(86400*$count)))); 
				$sqlPeriods="(SELECT 'Normal' AS type, gibbonTTDayRowClassID, gibbonCourseClass.gibbonCourseClassID, gibbonTTColumnRow.name, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, timeStart, timeEnd, phoneInternal, gibbonSpace.name AS roomName FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) JOIN gibbonTTDayRowClass ON (gibbonCourseClass.gibbonCourseClassID=gibbonTTDayRowClass.gibbonCourseClassID) JOIN gibbonTTColumnRow ON (gibbonTTDayRowClass.gibbonTTColumnRowID=gibbonTTColumnRow.gibbonTTColumnRowID) LEFT JOIN gibbonSpace ON (gibbonTTDayRowClass.gibbonSpaceID=gibbonSpace.gibbonSpaceID) WHERE gibbonTTDayID=:gibbonTTDayID AND gibbonSpace.gibbonSpaceID=:gibbonSpaceID)
				UNION
				(SELECT 'Change' AS type, gibbonTTDayRowClass.gibbonTTDayRowClassID, gibbonCourseClass.gibbonCourseClassID, gibbonTTColumnRow.name, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, timeStart, timeEnd, phoneInternal, gibbonSpace.name AS roomName FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) JOIN gibbonTTDayRowClass ON (gibbonCourseClass.gibbonCourseClassID=gibbonTTDayRowClass.gibbonCourseClassID) JOIN gibbonTTColumnRow ON (gibbonTTDayRowClass.gibbonTTColumnRowID=gibbonTTColumnRow.gibbonTTColumnRowID) JOIN gibbonTTSpaceChange ON (gibbonTTSpaceChange.gibbonTTDayRowClassID=gibbonTTDayRowClass.gibbonTTDayRowClassID AND gibbonTTSpaceChange.date=:date) LEFT JOIN gibbonSpace ON (gibbonTTSpaceChange.gibbonSpaceID=gibbonSpace.gibbonSpaceID) WHERE gibbonTTDayID=:gibbonTTDayID1 AND gibbonTTSpaceChange.gibbonSpaceID=:gibbonSpaceID1)
				ORDER BY timeStart, timeEnd" ;
				$resultPeriods=$connection2->prepare($sqlPeriods);
				$resultPeriods->execute($dataPeriods);
			}
			catch(PDOException $e) { 
				$output.="<div class='error'>" . $e->getMessage() . "</div>" ; 
			}
			
			while ($rowPeriods=$resultPeriods->fetch()) {
				$isSlotInTime=FALSE ;
				if ($rowPeriods["timeStart"]<=$dayTimeStart AND $rowPeriods["timeEnd"]>$dayTimeStart) {
					$isSlotInTime=TRUE ;
				}
				else if ($rowPeriods["timeStart"]>=$dayTimeStart AND $rowPeriods["timeEnd"]<=$dayTimeEnd) {
					$isSlotInTime=TRUE ;
				}
				else if ($rowPeriods["timeStart"]<$dayTimeEnd AND $rowPeriods["timeEnd"]>=$dayTimeEnd) {
					$isSlotInTime=TRUE ;
				}
				
				if ($isSlotInTime==TRUE) {
					if ((isset($spaceChanges[str_pad($rowPeriods["gibbonTTDayRowClassID"], 12, "0", STR_PAD_LEFT)])==FALSE AND $rowPeriods["type"]=="Normal") OR $rowPeriods["type"]=="Change") {
						$effectiveStart=$rowPeriods["timeStart"] ;
						$effectiveEnd=$rowPeriods["timeEnd"] ;
						if ($dayTimeStart>$rowPeriods["timeStart"]) {
							$effectiveStart=$dayTimeStart ;
						}
						if ($dayTimeEnd<$rowPeriods["timeEnd"]) {
							$effectiveEnd=$dayTimeEnd ;
						}
				
						$blank=FALSE ;
						$width=(ceil(690/$daysInWeek)-20) . "px" ;
						$height=ceil((strtotime($effectiveEnd)-strtotime($effectiveStart))/60) . "px" ;
						$top=(ceil((strtotime($effectiveStart)-strtotime($dayTimeStart))/60+($startPad/60))) . "px" ;
						$title="title='" ;
						if ($height<45) {
							$title=$title . _("Timeslot:") . " " . $rowPeriods["name"] . " " ;
						}
						if ($rowPeriods["roomName"]!="") {
							if ($height<60) {
								$title=$title . _("Room:") . " " . $rowPeriods["roomName"] . " " ;
							}
							if ($rowPeriods["phoneInternal"]!="") {
								$title=$title . _("Phone:") ." " . $rowPeriods["phoneInternal"] . " " ;
							}
						}
						$title=$title . "'" ;
						$class2="ttPeriod" ;
						if ((date("H:i:s")>$effectiveStart) AND (date("H:i:s")<$effectiveEnd) AND date("Y-m-d", ($startDayStamp+(84000*$count)))==date("Y-m-d")) {
							$class2="ttPeriodCurrent" ;	
						}
					
						//Create div to represent period
						$output.="<div class='$class2' $title style='z-index: $zCount; position: absolute; top: $top; width: $width; height: $height; margin: 0px; padding: 0px; opacity: $ttAlpha'>" ;
							if ($height>=45) {
								$output.=$rowPeriods["name"] . "<br/>" ;
							}
							$output.="<i>" . substr($effectiveStart,0,5) . " - " . substr($effectiveEnd,0,5) . "</i><br/>" ;
							if (isActionAccessible($guid, $connection2, "/modules/Department/department_course_class.php")) {
								$output.="<a style='text-decoration: none; font-weight: bold; font-size: 120%' href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Department/department_course_class.php&gibbonCourseClassID=" . $rowPeriods["gibbonCourseClassID"] . "&subpage=Participants'>" . $rowPeriods["course"] . "." . $rowPeriods["class"] . "</a><br/>" ;
							}
							else {
								$output.="<span style='font-size: 120%'><b>" . $rowPeriods["course"] . "." . $rowPeriods["class"] . "</b></span><br/>" ;
							}
							if ($height>=60) {
								if ($rowPeriods["type"]=="Normal") {
									$output.=$rowPeriods["roomName"] ;
								}
								else {
									$output.="<span style='border: 1px solid #c00; padding: 0 2px'>" . $rowPeriods["roomName"] . "</span>" ;
								}
							}
						$output.="</div>" ;
						$zCount++ ;
					}
				}
			}
			
			//Draw space bookings
			if ($eventsSpaceBooking!=FALSE) {
				$height=0 ;
				$top=0 ;
				foreach ($eventsSpaceBooking AS $event) {
					if ($event[3]==date("Y-m-d", ($startDayStamp+(86400*$count)))) {
						$height=ceil((strtotime(date("Y-m-d", ($startDayStamp+(86400*$count))) . " " . $event[5])-strtotime(date("Y-m-d", ($startDayStamp+(86400*$count))) . " " . $event[4]))/60) . "px" ;
						$top=(ceil((strtotime($event[3] . " " . $event[4])-strtotime(date("Y-m-d", $startDayStamp+(86400*$count)) . " " . $dayTimeStart))/60+($startPad/60))) . "px" ;
						if ($height<45) {
							$label=$event[1] ;
							$title="title='" . substr($event[4],0,5) . "-" . substr($event[5],0,5) . " by " . $event[6] . "'";
						}
						else {
							$label=$event[1] . "<br/><span style='font-weight: normal'>(" . substr($event[4],0,5) ."-" . substr($event[5],0,5) . ")<br/>by " . $event[6] ."</span>" ;
							$title="" ;
						}
						$output.="<div class='ttSpaceBookingCalendar' $title style='z-index: $zCount; position: absolute; top: $top; width: $width ; border: 1px solid #555; height: $height; margin: 0px; padding: 0px; opacity: $schoolCalendarAlpha'>" ;
							$output.=$label ;
						$output.="</div>" ;
						$zCount++ ;
					}
				}
			}
			
			$output.="</div>" ;
		}
	$output.="</td>" ;
	
	return $output ;
}

?>
