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


//Returns space bookings for the specified user for the 7 days on/after $startDayStamp, or for all users for the 7 days on/after $startDayStamp if no user specified
function getSpaceBookingEvents($guid, $connection2, $startDayStamp, $gibbonPersonID="") {
	$return=FALSE ;
	
	try {
		if ($gibbonPersonID!="") {
			$dataSpaceBooking=array("gibbonPersonID"=>$gibbonPersonID); 
			$sqlSpaceBooking="SELECT * FROM gibbonTTSpaceBooking JOIN gibbonSpace ON (gibbonTTSpaceBooking.gibbonSpaceID=gibbonSpace.gibbonSpaceID) WHERE gibbonPersonID=:gibbonPersonID AND date>='" . date("Y-m-d", $startDayStamp) . "' AND  date<='" . date("Y-m-d", ($startDayStamp+(7*24*60*60))) . "' ORDER BY date, timeStart, name" ;
		} 
		else {
			$dataSpaceBooking=array(); 
			$sqlSpaceBooking="SELECT * FROM gibbonTTSpaceBooking JOIN gibbonSpace ON (gibbonTTSpaceBooking.gibbonSpaceID=gibbonSpace.gibbonSpaceID) WHERE date>='" . date("Y-m-d", $startDayStamp) . "' AND  date<='" . date("Y-m-d", ($startDayStamp+(7*24*60*60))) . "' ORDER BY date, timeStart, name" ;
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
			$count++ ;
		}
	}
	
	return $return ;
}

//Returns events from a Google Calendar XML field, between the time and date specified
function getCalendarEvents($guid, $xml, $startDayStamp, $endDayStamp) {
	$start=date("Y-m-d\TH:i:s", strtotime(date("Y-m-d", $startDayStamp))) ;
	$end=date("Y-m-d\TH:i:s", (strtotime(date("Y-m-d", $endDayStamp))+86399)) ;
	
	$feed=(substr($xml,0,-5)) . 
		"full?sortorder=a&orderby=starttime&singleevents=true" .
		"&start-min=" . $start .
		"&start-max=" . $end .
		"&recurrence-expansion-start=" . $start .
		"&recurrence-expansion-end=" . $end .
		"&max-results=100" .
		"&ctz=" . $_SESSION[$guid]["timezone"];
	
	$doc=new DOMDocument(); 
	if (@$doc->load( $feed )) {
		$entries=$doc->getElementsByTagName("entry"); 
		$eventsSchool=array() ;
		$count=0 ;
		foreach ($entries as $entry) { 
			
			//WHAT
			$titles=$entry->getElementsByTagName("title"); 
			$eventsSchool[$count][0]=$titles->item(0)->nodeValue;
	
			//WHEN
			$times=$entry->getElementsByTagName("when"); 
			//Single events
			if ($times->length==1) {
				$startTime=$times->item(0)->getAttributeNode("startTime")->value;
				$endTime=$times->item(0)->getAttributeNode("endTime")->value;
			}
			//Recurring events
			else {
				$startTime=$times->item(1)->getAttributeNode("startTime")->value;
				$endTime=$times->item(1)->getAttributeNode("endTime")->value;
			}
			//All day
			if (date("H:i", strtotime($startTime))=="00:00" AND ((strtotime($endTime)-strtotime($startTime))==(60*60*24))) {
				$eventsSchool[$count][1]="All Day";
				$eventsSchool[$count][2]=strtotime($startTime) ;
				$eventsSchool[$count][3]=NULL;
			}
			//Time specified
			else {
				$eventsSchool[$count][1]="Specified Time" ;
				$eventsSchool[$count][2]=strtotime($startTime) ;
				$eventsSchool[$count][3]=mktime (substr($endTime,11,2), substr($endTime,14,2), substr($endTime,17,2), substr($endTime,5,2), substr($endTime,8,2), substr($endTime,0,4) ) ;
			}
			
			//WHERE
			$places=$entry->getElementsByTagName("where"); 
			$eventsSchool[$count][4]=$places->item(0)->getAttributeNode("valueString")->value;
			
			//LINK
			$link=$entry->getElementsByTagName("link"); 
			$eventsSchool[$count][4]=$link->item(0)->getAttribute("href");
		
			$count++ ;

		}
	}
	else {
		$eventsSchool=FALSE ;
	}
	
	return $eventsSchool ;
}


//TIMETABLE FOR INDIVIUDAL
function renderTT($guid, $connection2, $gibbonPersonID, $gibbonTTID, $title="", $startDayStamp="", $q="", $params="") {
	$zCount=0 ;
		
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
				print "<div class='error'>" . $e->getMessage() . "</div>" ; 
			}
		}
	}
	
	$output="" ;
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
		print "<div class='error'>" . $e->getMessage() . "</div>" ; 
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
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
		}
	}
	
	//link to other TTs
	if ($result->rowcount()>1 AND $title!=FALSE) {
		$output.="<p>" ;
			$output.="<span style='font-size: 115%; font-weight: bold'>" . _('Timetable Chooser') . "</span><br/>" ;
			$count=1 ;
			while ($row=$result->fetch()) {
				$output.="<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Timetable/tt_view.php&gibbonPersonID=$gibbonPersonID&gibbonTTID=" . $row["gibbonTTID"] . "'>" . $row["name"] . "</a>" ;
				if ($count<$result->rowCount()) {
					$output.=" . " ;
				}
				$count++ ;
			}
			try {
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				print "<div class='error'>" . $e->getMessage() . "</div>" ; 
			}
		$output.="</p>" ;
		
		if ($gibbonTTID!="") {
			$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonTTID"=>$gibbonTTID); 
			$sql="SELECT DISTINCT gibbonTT.gibbonTTID, gibbonTT.name FROM gibbonTT JOIN gibbonTTDay ON (gibbonTT.gibbonTTID=gibbonTTDay.gibbonTTID) JOIN gibbonTTDayRowClass ON (gibbonTTDayRowClass.gibbonTTDayID=gibbonTTDay.gibbonTTDayID) JOIN gibbonCourseClass ON (gibbonTTDayRowClass.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) WHERE gibbonPersonID=$gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonTT.gibbonTTID=:gibbonTTID" ;
		}
		try {
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
		}
	}

	
	//Display first TT
	if ($result->rowCount()>0) {
		$row=$result->fetch() ;
		
		if ($title!=FALSE) {
			$output.="<h2>" . $row["name"] . "</h2>" ;
		}
		print"<table cellspacing='0' class='noIntBorder' cellspacing='0' style='width: 100%; margin: 10px 0 10px 0'>" ;	
			print"<tr>" ;
				print"<td style='vertical-align: top'>" ; 
					print "<form method='post' action='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=$q" . $params . "'>" ;
						print "<input name='ttDate' value='" . date($_SESSION[$guid]["i18n"]["dateFormatPHP"], ($startDayStamp-(7*24*60*60))) . "' type='hidden'>" ;
						print "<input name='schoolCalendar' value='" . $_SESSION[$guid]["viewCalendarSchool"] . "' type='hidden'>" ;
						print "<input name='personalCalendar' value='" . $_SESSION[$guid]["viewCalendarPersonal"] . "' type='hidden'>" ;
						print "<input name='fromTT' value='Y' type='hidden'>" ;
						print "<input class='buttonLink' style='min-width: 30px; margin-top: 0px; float: left' type='submit' value='" . _('Last Week') . "'>" ;
					print "</form>" ;
					print "<form method='post' action='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=$q" . $params . "'>" ;
						print "<input name='ttDate' value='" . date($_SESSION[$guid]["i18n"]["dateFormatPHP"], ($startDayStamp+(7*24*60*60))) . "' type='hidden'>" ;
						print "<input name='schoolCalendar' value='" . $_SESSION[$guid]["viewCalendarSchool"] . "' type='hidden'>" ;
						print "<input name='personalCalendar' value='" . $_SESSION[$guid]["viewCalendarPersonal"] . "' type='hidden'>" ;
						print "<input name='fromTT' value='Y' type='hidden'>" ;
						print "<input class='buttonLink' style='min-width: 30px; margin-top: 0px; float: left' type='submit' value='" . _('Next Week') . "'>" ;
					print "</form>" ;
				print"</td>" ; 
				print"<td style='vertical-align: top; text-align: right'>" ;
					print "<form method='post' action='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=$q" . $params . "'>" ; 
						print "<input name='ttDate' id='ttDate' maxlength=10 value='" . date($_SESSION[$guid]["i18n"]["dateFormatPHP"], $startDayStamp) . "' type='text' style='height: 22px; width:100px; margin-right: 0px; float: none'> " ;
						?>
						<script type="text/javascript">
							var ttDate=new LiveValidation('ttDate');
							ttDate.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]["i18n"]["dateFormatRegEx"]=="") {  print "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i" ; } else { print $_SESSION[$guid]["i18n"]["dateFormatRegEx"] ; } ?>, failureMessage: "Use <?php if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?>." } ); 
							ttDate.add(Validate.Presence);
						 </script>
						 <script type="text/javascript">
							$(function() {
								$("#ttDate").datepicker();
							});
						</script>
						<input style='margin-top: 0px; margin-right: -2px' type='submit' value='<?php print _('Go') ?>'>
						<?php
						print "<input name='schoolCalendar' value='" . $_SESSION[$guid]["viewCalendarSchool"] . "' type='hidden'>" ;
						print "<input name='personalCalendar' value='" . $_SESSION[$guid]["viewCalendarPersonal"] . "' type='hidden'>" ;
						print "<input name='fromTT' value='Y' type='hidden'>" ;	
					print "</form>" ;
				print"</td>" ;
			print"</tr>" ;
		print"</table>" ;

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
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
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
				$eventsSchool=getCalendarEvents($guid,  $_SESSION[$guid]["calendarFeed"], $startDayStamp, $endDayStamp) ;
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
				$eventsPersonal=getCalendarEvents($guid,  $_SESSION[$guid]["calendarFeedPersonal"], $startDayStamp, $endDayStamp) ;
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
		
		//Get space booking array
		$eventsSpaceBooking=FALSE ;
		if ($self==TRUE AND $_SESSION[$guid]["viewCalendarSpaceBooking"]=="Y") {
			$eventsSpaceBooking=getSpaceBookingEvents($guid, $connection2, $startDayStamp, $_SESSION[$guid]["gibbonPersonID"]) ;
		}	
		
		//Count up max number of all day events in a day
		$eventsCombined=FALSE ;
		$maxAllDays=0 ;
		if ($allDay==TRUE) {
			if ($eventsPersonal!=FALSE AND $eventsSchool!=FALSE) {
				$eventsCombined=array_merge ($eventsSchool, $eventsPersonal) ;
			}
			else if ( $eventsSchool!=FALSE) {
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
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
		}
		while ($rowDiff=$resultDiff->fetch()) {
			try {
				$dataDiffDay=array("gibbonTTColumnID"=>$rowDiff["gibbonTTColumnID"]); 
				$sqlDiffDay="SELECT * FROM gibbonTTColumnRow WHERE gibbonTTColumnID=:gibbonTTColumnID ORDER BY timeStart" ;
				$resultDiffDay=$connection2->prepare($sqlDiffDay);
				$resultDiffDay->execute($dataDiffDay);
			}
			catch(PDOException $e) { 
				print "<div class='error'>" . $e->getMessage() . "</div>" ; 
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
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
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
		$width=(ceil(690/$daysInWeek)-20) . "px" ;
		
		$count=0;
		
		$output.="<table cellspacing='0' class='mini' cellspacing='0' style='width: 750px; margin: 0px 0px 30px 0px;'>" ;
			//Spit out controls for displaying calendars
			if ($self==TRUE AND ($_SESSION[$guid]["calendarFeed"]!="" OR $_SESSION[$guid]["calendarFeedPersonal"]!="" OR $_SESSION[$guid]["viewCalendarSpaceBooking"]!="")) {
				$output.="<tr class='head' style='height: 37px;'>" ;
					$output.="<th class='ttCalendarBar' colspan=" . ($daysInWeek+1) . ">" ;
						$output.="<form method='post' action='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=$q" . $params . "' style='padding: 5px 5px 0 0'>" ;
							if ($_SESSION[$guid]["calendarFeed"]!="") {
								$checked="" ;
								if ($_SESSION[$guid]["viewCalendarSchool"]=="Y") {
									$checked="checked" ;
								}
								$output.="<span class='ttSchoolCalendar' style='opacity: $schoolCalendarAlpha'>School Calendar " ;
								$output.="<input $checked style='margin-left: 3px' type='checkbox' name='schoolCalendar' onclick='submit();'/>" ;
								$output.="</span>" ;
							}
							if ($_SESSION[$guid]["calendarFeedPersonal"]!="") {
								$checked="" ;
								if ($_SESSION[$guid]["viewCalendarPersonal"]=="Y") {
									$checked="checked" ;
								}
								$output.="<span class='ttPersonalCalendar' style='opacity: $schoolCalendarAlpha'>Personal Calendar " ;
								$output.="<input $checked style='margin-left: 3px' type='checkbox' name='personalCalendar' onclick='submit();'/>" ;
								$output.="</span>" ;
							}
							if ($_SESSION[$guid]["viewCalendarSpaceBooking"]!="") {
								$checked="" ;
								if ($_SESSION[$guid]["viewCalendarSpaceBooking"]=="Y") {
									$checked="checked" ;
								}
								$output.="<span class='ttSpaceBookingCalendar' style='opacity: $schoolCalendarAlpha'>Space Booking " ;
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
						$output.="Week " . $week ."<br/>" ;
					}
					$output.="<span style='font-weight: normal; font-style: italic;'>" . _('Time') . "<span>" ;
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
							print "<div class='error'>" . $e->getMessage() . "</div>" ; 
						}
						if ($resultSpecial->rowcount()==1) {
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
							print "<div class='error'>" . $e->getMessage() . "</div>" ; 
						}
						if ($resultSpecial->rowcount()==1) {
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
							print "<div class='error'>" . $e->getMessage() . "</div>" ; 
						}
						if ($resultSpecial->rowcount()==1) {
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
							print "<div class='error'>" . $e->getMessage() . "</div>" ; 
						}
						if ($resultSpecial->rowcount()==1) {
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
							print "<div class='error'>" . $e->getMessage() . "</div>" ; 
						}
						if ($resultSpecial->rowcount()==1) {
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
							print "<div class='error'>" . $e->getMessage() . "</div>" ; 
						}
						if ($resultSpecial->rowcount()==1) {
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
							print "<div class='error'>" . $e->getMessage() . "</div>" ; 
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
						$output.="<span style='font-size: 80%'><b>All Day<br/>Events</b></span>" ;
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
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
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
							print "<div class='error'>" . $e->getMessage() . "</div>" ; 
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
								print "<div class='error'>" . $e->getMessage() . "</div>" ; 
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
									$day=renderTTDay($guid, $connection2, $row["gibbonTTID"], $startDayStamp, $count, $daysInWeek, $gibbonPersonID, $timeStart, $eventsSchool, $eventsPersonal, $eventsSpaceBooking, $diffTime, $maxAllDays, $rowClosure["schoolStart"], $rowClosure["schoolEnd"]) ;
								}
							}
							else {
								$day=renderTTDay($guid, $connection2, $row["gibbonTTID"], $startDayStamp, $count, $daysInWeek, $gibbonPersonID, $timeStart, $eventsSchool, $eventsPersonal, $eventsSpaceBooking, $diffTime, $maxAllDays) ;
							}
						}
						else {
							$day=$day . "<td style='text-align: center; vertical-align: top; font-size: 11px'>" ;
								$day=$day . "<div style='position: relative'>" ;
									$day=$day . "<div class='ttClosure' style='z-index: $zCount; position: absolute; width: $width ; height: " . ceil($diffTime/60) . "px; margin: 0px; padding: 0px; opacity: $ttAlpha'>" ;
										$day=$day . "<div style='position: relative; top: 50%'>" ;
											$day=$day . "<span style='color: rgba(255,0,0,$ttAlpha);'>School Closed</span>" ;
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
	
	
	
	if ($blank==TRUE) {
		return FALSE ;
	}
	else {
		return $output ;
	}
}

function renderTTDay($guid, $connection2, $gibbonTTID, $startDayStamp, $count, $daysInWeek, $gibbonPersonID, $gridTimeStart, $eventsSchool, $eventsPersonal, $eventsSpaceBooking, $diffTime, $maxAllDays, $specialDayStart="", $specialDayEnd="") {
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
		print "<div class='error'>" . $e->getMessage() . "</div>" ; 
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
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
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
				print "<div class='error'>" . $e->getMessage() . "</div>" ; 
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
				$dataPeriods=array("gibbonTTDayID"=>$rowDay["gibbonTTDayID"], "gibbonPersonID"=>$gibbonPersonID); 
				$sqlPeriods="SELECT gibbonTTDayRowClassID, gibbonCourseClass.gibbonCourseClassID, gibbonTTColumnRow.name, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, timeStart, timeEnd, phoneInternal, gibbonSpace.name AS roomName FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonTTDayRowClass ON (gibbonCourseClass.gibbonCourseClassID=gibbonTTDayRowClass.gibbonCourseClassID) JOIN gibbonTTColumnRow ON (gibbonTTDayRowClass.gibbonTTColumnRowID=gibbonTTColumnRow.gibbonTTColumnRowID) LEFT JOIN gibbonSpace ON (gibbonTTDayRowClass.gibbonSpaceID=gibbonSpace.gibbonSpaceID) WHERE gibbonTTDayID=:gibbonTTDayID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND NOT role LIKE '% - Left' ORDER BY timeStart, timeEnd" ;
				$resultPeriods=$connection2->prepare($sqlPeriods);
				$resultPeriods->execute($dataPeriods);
			}
			catch(PDOException $e) { 
				print "<div class='error'>" . $e->getMessage() . "</div>" ; 
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
						print "<div class='error'>" . $e->getMessage() . "</div>" ; 
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
						$width=(ceil(690/$daysInWeek)-20) . "px" ;
						$height=ceil((strtotime($effectiveEnd)-strtotime($effectiveStart))/60) . "px" ;
						$top=(ceil((strtotime($effectiveStart)-strtotime($dayTimeStart))/60+($startPad/60))) . "px" ;
						$title="title='" ;
						if ($height<45) {
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
						}
						$output.="<i>" . substr($effectiveStart,0,5) . " - " . substr($effectiveEnd,0,5) . "</i><br/>" ;
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
						
						//Add planner link icons for staff looking at own TT.
						if ($self==TRUE AND $roleCategory=="Staff") { 
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
									print "<div class='error'>" . $e->getMessage() . "</div>" ; 
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
									print "<div class='error'>" . $e->getMessage() . "</div>" ; 
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
								$output.="<a target=_blank style='color: #fff' href='" . $event[4] . "'>" . $label . "</a>" ;
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
								$output.="<a target=_blank style='color: #fff' href='" . $event[4] . "'>" . $label . "</a>" ;
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
								$output.="<a target=_blank style='color: #fff' href='" . $event[4] . "'>" . $label . "</a>" ;
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
								$output.="<a target=_blank style='color: #fff' href='" . $event[4] . "'>" . $label . "</a>" ;
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
						$label=$event[1] . "<br/>(" . $event[4] . ")" ;
						$title="" ;
						if (strlen($label)>20) {
							$label=substr($label, 0, 20) . "..." ;
							$title="title='" . $event[1] . " (" . $event[4] . ")'" ;
						}
						$height=ceil((strtotime(date("Y-m-d", ($startDayStamp+(86400*$count))) . " " . $event[5])-strtotime(date("Y-m-d", ($startDayStamp+(86400*$count))) . " " . $event[4]))/60) . "px" ;
						$top=(ceil((strtotime($event[3] . " " . $event[4])-strtotime(date("Y-m-d", $startDayStamp+(86400*$count)) . " " . $dayTimeStart))/60+($startPad/60))) . "px" ;
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
		print "<div class='error'>" . $e->getMessage() . "</div>" ; 
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
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
		}
	}
	
	//link to other TTs
	if ($result->rowCount()>1 AND $title!=FALSE) {
		$output.="<p>" ;
			$output.="<span style='font-size: 115%; font-weight: bold'>" . _('Timetable Chooser') ."</span><br/>" ;
			$count=1 ;
			while ($row=$result->fetch()) {
				$output.="<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Timetable/tt_space_view.php&gibbonSpaceID=$gibbonSpaceID&gibbonTTID=" . $row["gibbonTTID"] . "'>" . $row["name"] . "</a>" ;
				if ($count<$result->rowCount()) {
					$output.=" . " ;
				}
				$count++ ;
			}
			try {
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				print "<div class='error'>" . $e->getMessage() . "</div>" ; 
			}
		$output.="</p>" ;
		
		if ($gibbonTTID!="") {
			$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonTTID"=>$gibbonTTID); 
			$sql="SELECT DISTINCT gibbonTT.gibbonTTID, gibbonTT.name FROM gibbonTT JOIN gibbonTTDay ON (gibbonTT.gibbonTTID=gibbonTTDay.gibbonTTID) JOIN gibbonTTDayRowClass ON (gibbonTTDayRowClass.gibbonTTDayID=gibbonTTDay.gibbonTTDayID) JOIN gibbonCourseClass ON (gibbonTTDayRowClass.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) WHERE gibbonSpaceID=$gibbonSpaceID AND gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonTT.gibbonTTID=:gibbonTTID" ;
		}
		try {
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
		}
	}
	
	//Get space booking array
	$eventsSpaceBooking=FALSE ;
	if ($_SESSION[$guid]["viewCalendarSpaceBooking"]=="Y") {
		$eventsSpaceBooking=getSpaceBookingEvents($guid, $connection2, $startDayStamp, $_SESSION[$guid]["gibbonPersonID"]) ;
	}	

	
	//Display first TT
	if ($result->rowCount()>0) {
		$row=$result->fetch() ;
		
		if ($title!=FALSE) {
			$output.="<h2>" . $row["name"] . "</h2>" ;
		}
		
		print"<table cellspacing='0' class='noIntBorder' cellspacing='0' style='width: 100%; margin: 10px 0 10px 0'>" ;	
			print"<tr>" ;
				print"<td style='vertical-align: top'>" ; 
					print "<form method='post' action='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=$q" . $params . "'>" ;
						print "<input name='ttDate' maxlength=10 value='" . date($_SESSION[$guid]["i18n"]["dateFormatPHP"], ($startDayStamp-(7*24*60*60))) . "' type='hidden'>" ;
						print "<input name='schoolCalendar' value='" . $_SESSION[$guid]["viewCalendarSchool"] . "' type='hidden'>" ;
						print "<input name='personalCalendar' value='" . $_SESSION[$guid]["viewCalendarPersonal"] . "' type='hidden'>" ;
						print "<input name='fromTT' value='Y' type='hidden'>" ;
						?>
						<input class='buttonLink' style='min-width: 30px; margin-top: 0px; float: left' type='submit' value='<?php print _('Last Week') ?>'>
						<?php	
					print "</form>" ;
					print "<form method='post' action='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=$q" . $params . "'>" ;
						print "<input name='ttDate' value='" . date($_SESSION[$guid]["i18n"]["dateFormatPHP"], ($startDayStamp+(7*24*60*60))) . "' type='hidden'>" ;
						print "<input name='schoolCalendar' value='" . $_SESSION[$guid]["viewCalendarSchool"] . "' type='hidden'>" ;
						print "<input name='personalCalendar' value='" . $_SESSION[$guid]["viewCalendarPersonal"] . "' type='hidden'>" ;
						print "<input name='fromTT' value='Y' type='hidden'>" ;
						?>
						<input class='buttonLink' style='min-width: 30px; margin-top: 0px; float: left' type='submit' value='<?php print _('Next Week') ?>'>
						<?php	
					print "</form>" ;
				print"</td>" ; 
				print"<td style='vertical-align: top; text-align: right'>" ; 
					print "<form method='post' action='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=$q" . $params . "'>" ;
						print "<input name='ttDate' id='ttDate' maxlength=10 value='" . date($_SESSION[$guid]["i18n"]["dateFormatPHP"], $startDayStamp) . "' type='text' style='height: 22px; width:100px; margin-right: 0px; float: none'>" ;
						?>
						<script type="text/javascript">
							var ttDate=new LiveValidation('ttDate');
							ttDate.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]["i18n"]["dateFormatRegEx"]=="") {  print "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i" ; } else { print $_SESSION[$guid]["i18n"]["dateFormatRegEx"] ; } ?>, failureMessage: "Use <?php if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?>." } ); 
							ttDate.add(Validate.Presence);
						 </script>
						 <script type="text/javascript">
							$(function() {
								$("#ttDate").datepicker();
							});
						</script>
						<input style='margin-top: 0px; margin-right: -2px' type='submit' value='<?php print _('Go') ?>'>
						<?php
						print "<input name='schoolCalendar' value='" . $_SESSION[$guid]["viewCalendarSchool"] . "' type='hidden'>" ;
						print "<input name='personalCalendar' value='" . $_SESSION[$guid]["viewCalendarPersonal"] . "' type='hidden'>" ;
						print "<input name='fromTT' value='Y' type='hidden'>" ;	
					print "</form>" ;
				print"</td>" ;
			print"</tr>" ;
		print"</table>" ;

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
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
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
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
		}
		while ($rowDiff=$resultDiff->fetch()) {
			try {
				$dataDiffDay=array("gibbonTTColumnID"=>$rowDiff["gibbonTTColumnID"]); 
				$sqlDiffDay="SELECT * FROM gibbonTTColumnRow WHERE gibbonTTColumnID=:gibbonTTColumnID ORDER BY timeStart" ;
				$resultDiffDay=$connection2->prepare($sqlDiffDay);
				$resultDiffDay->execute($dataDiffDay);
			}
			catch(PDOException $e) { 
				print "<div class='error'>" . $e->getMessage() . "</div>" ; 
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
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
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
								$output.="<span class='ttSpaceBookingCalendar' style='opacity: $schoolCalendarAlpha'>Space Booking " ;
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
						$output.="Week " . $week ."<br/>" ;
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
							print "<div class='error'>" . $e->getMessage() . "</div>" ; 
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
							print "<div class='error'>" . $e->getMessage() . "</div>" ; 
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
							print "<div class='error'>" . $e->getMessage() . "</div>" ; 
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
							print "<div class='error'>" . $e->getMessage() . "</div>" ; 
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
							print "<div class='error'>" . $e->getMessage() . "</div>" ; 
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
							print "<div class='error'>" . $e->getMessage() . "</div>" ; 
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
							print "<div class='error'>" . $e->getMessage() . "</div>" ; 
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
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
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
							print "<div class='error'>" . $e->getMessage() . "</div>" ; 
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
								print "<div class='error'>" . $e->getMessage() . "</div>" ; 
							}

							if ($resultClosure->rowCount()==1) {
								$rowClosure=$resultClosure->fetch() ;
								if ($rowClosure["type"]=="School Closure") {
									$day=$day . "<td style='text-align: center; vertical-align: top; font-size: 11px'>" ;
										$day=$day . "<div style='position: relative'>" ;
											$day=$day . "<div class='ttClosure' style='z-index: $zCount; position: absolute; width: $width ; height: " . ceil($diffTime/60) . "px; margin: 0px; padding: 0px; opacity: $ttAlpha'>" ;
												$day=$day . "<div style='position: relative; top: 50%'>" ;
													$day=$day . "<span'>" . $rowClosure["name"] . "</span>" ;
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
											$day=$day . "<span style='color: rgba(255,0,0,$ttAlpha);'>School Closed</span>" ;
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
	
	if ($blank==TRUE) {
		return FALSE ;
	}
	else {
		return $output ;
	}
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
		print "<div class='error'>" . $e->getMessage() . "</div>" ; 
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
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
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
				print "<div class='error'>" . $e->getMessage() . "</div>" ; 
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
				print "<div class='error'>" . $e->getMessage() . "</div>" ; 
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
						$label=$event[1] . "<br/>(" . $event[4] . ")" ;
						$title="" ;
						if (strlen($label)>20) {
							$label=substr($label, 0, 20) . "..." ;
							$title="title='" . $event[1] . " (" . $event[4] . ")'" ;
						}
						$height=ceil((strtotime(date("Y-m-d", ($startDayStamp+(86400*$count))) . " " . $event[5])-strtotime(date("Y-m-d", ($startDayStamp+(86400*$count))) . " " . $event[4]))/60) . "px" ;
						$top=(ceil((strtotime($event[3] . " " . $event[4])-strtotime(date("Y-m-d", $startDayStamp+(86400*$count)) . " " . $dayTimeStart))/60+($startPad/60))) . "px" ;
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
