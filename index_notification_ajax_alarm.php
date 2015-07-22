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

@session_start() ;

//Gibbon system-wide includes
include "./functions.php" ;
include "./config.php" ;

//New PDO DB connection
try {
  	$connection2=new PDO("mysql:host=$databaseServer;dbname=$databaseName;charset=utf8", $databaseUsername, $databasePassword);
	$connection2->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$connection2->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
}
catch(PDOException $e) {
  echo $e->getMessage();
}

$type="" ;
if (isset($_GET["type"])) {
	$type=$_GET["type"] ;
}
$output="" ;

if ($type=="general" OR $type=="lockdown") {
	$output.="<div style='width: 100%; min-height: 492px; background-color: #f00; color: #fff; margin: 0'>" ;
		$output.="<div style='padding-top: 150px; font-size: 120px; font-weight: bold; font-family: arial, sans; text-align: center'>" ;
			if ($type=="general") {
				$output.=_("General Alarm!") ;
				$output.="<audio loop autoplay volume=3>
					<source src=\"./audio/alarm_general.mp3\" type=\"audio/mpeg\">
				</audio>" ;	
			}
			else {
				$output.=_("Lockdown!") ;
				$output.="<audio loop autoplay volume=3>
					<source src=\"./audio/alarm_lockdown.mp3\" type=\"audio/mpeg\">
				</audio>" ;	
			}
		$output.="</div>" ;	
		
		//Check alarm details
		try {
			$data=array(); 
			$sql="SELECT * FROM gibbonAlarm WHERE status='Current'" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			$output.="<div class='error'>" ;
				$output.=$e->getMessage();
			$output.="</div>" ;
		}
		
		if ($result->rowCount()==1) { //Alarm details OK
			$row=$result->fetch() ;
			
			$output.="<div style='padding: 0 20px; font-family: arial, sans; text-align: center'>" ;
				//Allow everyone except alarm sounder to confirm receipt
				if ($row["gibbonPersonID"]!=$_SESSION[$guid]["gibbonPersonID"]) {
					$output.="<p>" ;
						//Check for confirmation
						try {
							$dataConfirm=array("gibbonAlarmID"=>$row["gibbonAlarmID"], "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
							$sqlConfirm="SELECT * FROM gibbonAlarmConfirm WHERE gibbonAlarmID=:gibbonAlarmID AND gibbonPersonID=:gibbonPersonID" ;
							$resultConfirm=$connection2->prepare($sqlConfirm);
							$resultConfirm->execute($dataConfirm);
						}
						catch(PDOException $e) { 
							$output.="<div class='error'>" ;
								$output.=$e->getMessage();
							$output.="</div>" ;
						}
						
						if ($resultConfirm->rowCount()==0) {
							$output.="<a target='_parent' style='font-weight: bold; color: #fff' href='" . $_SESSION[$guid]["absoluteURL"] . "/index_notification_ajax_alarmProcess.php?gibbonAlarmID=" . $row["gibbonAlarmID"] . "'>" . _('Click here to confirm that you have received this alarm.') . "</a><br/>" ;
							$output.="<i>" . _("After confirming receipt, the alarm will continue to be displayed until it has been turned off.") . "</i>" ;
						}
						else {
							$output.="<i>" . _("You have successfully confirmed receipt of this alarm, which will continue to be displayed until it has been turned off.") . "</i>" ;
						}
					$output.="</p>" ;
				}
			
				//Show report to those with permission to sound alarm
				if (isActionAccessible($guid, $connection2, "/modules/System Admin/alarm.php")) {
					$output.="<h3>" ;
					$output.=_("Receipt Confirmation Report") ;
					$output.="</h3>" ;
					
					try {
						$dataConfirm=array("gibbonAlarmID"=>$row["gibbonAlarmID"]); 
						$sqlConfirm="SELECT gibbonPerson.gibbonPersonID, status, surname, preferredName, gibbonAlarmConfirmID FROM gibbonPerson JOIN gibbonStaff ON (gibbonStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) LEFT JOIN gibbonAlarmConfirm ON (gibbonAlarmConfirm.gibbonPersonID=gibbonPerson.gibbonPersonID AND gibbonAlarmID=:gibbonAlarmID) WHERE gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') ORDER BY surname, preferredName" ; 
						$resultConfirm=$connection2->prepare($sqlConfirm);
						$resultConfirm->execute($dataConfirm);
					}
					catch(PDOException $e) { 
						$output.="<div class='error'>" . $e->getMessage() . "</div>" ; 
					}

					if ($resultConfirm->rowcount()<1) {
						$output.="<div class='error'>" ;
						$output.=_("There are no records to display.") ;
						$output.="</div>" ;
					}
					else {
						$output.="<table cellspacing='0' style='width: 400px; margin: 0 auto'>" ;
							$output.="<tr class='head'>" ;
								$output.="<th style='color: #fff; text-align: left'>" ;
									$output.=_("Name") . "<br/>" ;
								$output.="</th>" ;
								$output.="<th style='color: #fff; text-align: left'>" ;
									$output.=_("Confirmed") ;
								$output.="</th>" ;
								$output.="<th style='color: #fff; text-align: left'>" ;
									$output.=_("Actions") ;
								$output.="</th>" ;
							$output.="</tr>" ;
				
							while ($rowConfirm=$resultConfirm->fetch()) {
								//COLOR ROW BY STATUS!
								$output.="<tr>" ;
									$output.="<td style='color: #fff'>" ;
										$output.=formatName("", $rowConfirm["preferredName"],$rowConfirm["surname"], "Student", true) . "<br/>" ;
									$output.="</td>" ;
									$output.="<td style='color: #fff'>" ;
										if ($row["gibbonPersonID"]==$rowConfirm["gibbonPersonID"]) {
											$output.=_("NA") ;
										}
										else {
											if ($rowConfirm["gibbonAlarmConfirmID"]!="") {
												$output.="<img src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/iconTick.png'/> " ;
											}
										}
									$output.="</td>" ;
									$output.="<td style='color: #fff'>" ;
										if ($row["gibbonPersonID"]!=$rowConfirm["gibbonPersonID"]) {
											if ($rowConfirm["gibbonAlarmConfirmID"]=="") {
												$output.="<a target='_parent' href='" . $_SESSION[$guid]["absoluteURL"] . "/index_notification_ajax_alarmConfirmProcess.php?gibbonPersonID=" . $rowConfirm["gibbonPersonID"] . "&gibbonAlarmID=" . $row["gibbonAlarmID"] . "'><img title='" . _('Confirm') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/iconTick_light.png'/></a> " ;
											}
										}
									$output.="</td>" ;
								$output.="</tr>" ;
							}
						$output.="</table>" ;
					}
				}
			$output.="</div>" ;
		}
	$output.="</div>" ;
}

print $output ;
?>