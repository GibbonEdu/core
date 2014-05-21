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

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]["timezone"]);

if (isActionAccessible($guid, $connection2, "/modules/Staff/staff_view_details.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Get action with highest precendence
	$highestAction=getHighestGroupedAction($guid, $_GET["q"], $connection2) ;
	if ($highestAction==FALSE) {
		print "<div class='error'>" ;
		print _("The highest grouped action cannot be determined.") ;
		print "</div>" ;
	}
	else {
		$gibbonPersonID=$_GET["gibbonPersonID"] ;
		if ($gibbonPersonID==FALSE) {
			print "<div class='error'>" ;
			print _("You have not specified one or more required parameters.") ;
			print "</div>" ;
		}
		else {
			$search=NULL ;
			if (isset($_GET["search"])) {
				$search=$_GET["search"] ;
			}
					
			if ($highestAction=="View Staff Profile_brief") {
				//Proceed!
				try {
					$data=array("gibbonPersonID"=>$gibbonPersonID); 
					$sql="SELECT title, surname, preferredName, type, gibbonStaff.jobTitle, email, website, countryOfOrigin, qualifications, biography FROM gibbonPerson JOIN gibbonStaff ON (gibbonStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonPerson.gibbonPersonID=:gibbonPersonID" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}

				if ($result->rowCount()!=1) {
					print "<div class='error'>" ;
						print _("The selected record does not exist, or you do not have access to it.") ;
					print "</div>" ;
					print "</div>" ;
				}
				else {
					$row=$result->fetch() ;
					
					print "<div class='trail'>" ;
					print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . _(getModuleName($_GET["q"])) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/staff_view.php'>" . _('View Staff Profiles') . "</a> > </div><div class='trailEnd'>" . formatName("", $row["preferredName"], $row["surname"], "Student") . "</div>" ;
					print "</div>" ;
					
					if ($search!="") {
						print "<div class='linkTop'>" ;
							print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Staff/staff_view.php&search=" . $search . "'>" . _('Back to Search Results') . "</a>" ;
						print "</div>" ;
					}
					
					print "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>" ;
						print "<tr>" ;
							print "<td style='width: 33%; vertical-align: top'>" ;
								print "<span style='font-size: 115%; font-weight: bold'>" . _('Name') . "</span><br/>" ;
								print "<i>" . formatName($row["title"], $row["preferredName"], $row["surname"], "Parent") . "</i>" ;
							print "</td>" ;
							print "<td style='width: 33%; vertical-align: top'>" ;
								print "<span style='font-size: 115%; font-weight: bold'>" . _('Staff Type') . "</span><br/>" ;
								print "<i>" . $row["type"] . "</i>" ;
							print "</td>" ;
							print "<td style='width: 33%; vertical-align: top'>" ;
								print "<span style='font-size: 115%; font-weight: bold'>" . _('Job Title') . "</span><br/>" ;
								print "<i>" . $row["jobTitle"] . "</i>" ;
							print "</td>" ;
						print "</tr>" ;
						print "<tr>" ;
							print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
								print "<span style='font-size: 115%; font-weight: bold'>" . _('Email') . "</span><br/>" ;
								if ($row["email"]!="") {
									print "<i><a href='mailto:" . $row["email"] . "'>" . $row["email"] . "</a></i>" ;
								}
							print "</td>" ;
							print "<td style='width: 67%; padding-top: 15px; vertical-align: top' colspan=2>" ;
								print "<span style='font-size: 115%; font-weight: bold'>" . _('Website') . "</span><br/>" ;
								if ($row["website"]!="") {
									print "<i><a href='" . $row["website"] . "'>" . $row["website"] . "</a></i>" ;
								}
							print "</td>" ;
						print "</tr>" ;
					print "</table>" ;
					
					print "<h4>" ;
						print _("Biography") ;
					print "</h4>" ;
					print "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>" ;
						print "<tr>" ;
							print "<td style='width: 33%; vertical-align: top'>" ;
								print "<span style='font-size: 115%; font-weight: bold'>" . _('Country Of Origin') . "</span><br/>" ;
								print "<i>" . $row["countryOfOrigin"] . "</i>" ;
							print "</td>" ;
							print "<td style='width: 67%; vertical-align: top' colspan=2>" ;
								print "<span style='font-size: 115%; font-weight: bold'>" . _('Qualifications') . "</span><br/>" ;
								print "<i>" . $row["qualifications"] . "</i>" ;
							print "</td>" ;
						print "</tr>" ;
						print "<tr>" ;
							print "<td style='width: 100%; vertical-align: top' colspan=3>" ;
								print "<span style='font-size: 115%; font-weight: bold'>" . _('Biography') . "</span><br/>" ;
								print "<i>" . $row["biography"] . "</i>" ;
							print "</td>" ;
						print "</tr>" ;
					print "</table>" ;
					
					//Set sidebar
					$_SESSION[$guid]["sidebarExtra"]=getUserPhoto($guid, $row["image_240"], 240) ;
				}
			}
			else {
				try {
					$data=array("gibbonPersonID"=>$gibbonPersonID); 
					$sql="SELECT gibbonPerson.*, gibbonStaff.initials, gibbonStaff.type, gibbonStaff.jobTitle, countryOfOrigin, qualifications, biography FROM gibbonPerson JOIN gibbonStaff ON (gibbonStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonPerson.gibbonPersonID=:gibbonPersonID" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				
				if ($result->rowCount()!=1) {
					print "<div class='error'>" ;
						print _("The selected record does not exist, or you do not have access to it.") ;
					print "</div>" ;
				}
				else {
					$row=$result->fetch() ;
					
					print "<div class='trail'>" ;
					print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . _(getModuleName($_GET["q"])) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/staff_view.php'>" . _('View Staff Profiles') . "</a> > </div><div class='trailEnd'>" . formatName("", $row["preferredName"], $row["surname"], "Student") . "</div>" ;
					print "</div>" ;
					
					$subpage=NULL ;
					if (isset($_GET["subpage"])) {
						$subpage=$_GET["subpage"] ;
					}
					if ($subpage=="") {
						$subpage="Summary" ;
					}
					
					if ($search!="") {
						print "<div class='linkTop'>" ;
							print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Staff/staff_view.php&search=" . $search . "'>" . _('Back to Search Results') . "</a>" ;
						print "</div>" ;
					}
					
					print "<h2>" ;
						if ($subpage!="") {
							print $subpage ;
						}
					print "</h2>" ;
					
					if ($subpage=="Summary") {
						if (isActionAccessible($guid, $connection2, "/modules/User Admin/user_manage.php")==TRUE) {
							print "<div class='linkTop'>" ;
							print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/User Admin/user_manage_edit.php&gibbonPersonID=$gibbonPersonID'><img style='margin: 0 0 -4px 3px' title='Edit Record' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/config.png'/></a> " ;
							print "</div>" ;
						}
					
						print "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>" ;
							print "<tr>" ;
								print "<td style='width: 33%; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>" . _('Name') . "</span><br/>" ;
									print "<i>" . formatName($row["title"], $row["preferredName"], $row["surname"], "Parent") . "</i>" ;
								print "</td>" ;
								print "<td style='width: 33%; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>" . _('Staff Type') . "</span><br/>" ;
									print "<i>" . $row["type"] . "</i>" ;
								print "</td>" ;
								print "<td style='width: 33%; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>" . _('Job Title') . "</span><br/>" ;
									print "<i>" . $row["jobTitle"] . "</i>" ;
								print "</td>" ;
							print "</tr>" ;
							print "<tr>" ;
								print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>" . _('Username') . "</span><br/>" ;
									print "<i>" . $row["username"] . "</i>" ;
								print "</td>" ;
								print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>" . _('Website') . "</span><br/>" ;
									if ($row["website"]!="") {
										print "<i><a href='" . $row["website"] . "'>" . $row["website"] . "</a></i>" ;
									}
								print "</td>" ;
								print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>" . _('Email') . "</span><br/>" ;
									if ($row["email"]!="") {
										print "<i><a href='mailto:" . $row["email"] . "'>" . $row["email"] . "</a></i>" ;
									}
								print "</td>" ;
							print "</tr>" ;
						print "</table>" ;
						
						print "<h4>" ;
							print _("Biography") ;
						print "</h4>" ;
						print "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>" ;
							print "<tr>" ;
								print "<td style='width: 33%; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>" . _('Country Of Origin') . "</span><br/>" ;
									print "<i>" . $row["countryOfOrigin"] . "</i>" ;
								print "</td>" ;
								print "<td style='width: 67%; vertical-align: top' colspan=2>" ;
									print "<span style='font-size: 115%; font-weight: bold'>" . _('Qualifications') . "</span><br/>" ;
									print "<i>" . $row["qualifications"] . "</i>" ;
								print "</td>" ;
							print "</tr>" ;
							print "<tr>" ;
								print "<td style='width: 100%; vertical-align: top' colspan=3>" ;
									print "<span style='font-size: 115%; font-weight: bold'>" . _('Biography') . "</span><br/>" ;
									print "<i>" . $row["biography"] . "</i>" ;
								print "</td>" ;
							print "</tr>" ;
						print "</table>" ;
					}
					else if ($subpage=="Personal") {
						if (isActionAccessible($guid, $connection2, "/modules/User Admin/user_manage.php")==TRUE) {
							print "<div class='linkTop'>" ;
							print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/User Admin/user_manage_edit.php&gibbonPersonID=$gibbonPersonID'><img style='margin: 0 0 -4px 3px' title='Edit Record' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/config.png'/></a> " ;
							print "</div>" ;
						}
						
						print "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>" ;
							print "<tr>" ;
								print "<td style='width: 33%; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>" . _('Name') . "</span><br/>" ;
									print "<i>" . formatName($row["title"], $row["preferredName"], $row["surname"], "Parent") . "</i>" ;
								print "</td>" ;
								print "<td style='width: 33%; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>" . _('Staff Type') . "</span><br/>" ;
									print "<i>" . $row["type"] . "</i>" ;
								print "</td>" ;
								print "<td style='width: 33%; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>" . _('Job Title') . "</span><br/>" ;
									print "<i>" . $row["jobTitle"] . "</i>" ;
								print "</td>" ;
							print "</tr>" ;	
							print "<tr>" ;
								print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>" . _('Initials') . "</span><br/>" ;
									print $row["initials"] ;
								print "</td>" ;
								print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>" . _('Gender') . "</span><br/>" ;
									print $row["gender"] ;
								print "</td>" ;
								print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
									
								print "</td>" ;
							print "</tr>" ;
						print "</table>" ;
							
						print "<h4>" ;
						print "Contacts" ;
						print "</h4>" ;
						
						print "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>" ;
							$numberCount=0 ;
							if ($row["phone1"]!="" OR $row["phone2"]!="" OR $row["phone3"]!="" OR $row["phone4"]!="") {
								print "<tr>" ;
									for ($i=1; $i<5; $i++) {
										if ($row["phone" . $i]!="") {
											$numberCount++ ;
											print "<td width: 33%; style='vertical-align: top'>" ;
												print "<span style='font-size: 115%; font-weight: bold'>" . _('Phone') . " $numberCount</span><br/>" ;
												if ($row["phone" . $i . "Type"]!="") {
													print "<i>" . $row["phone" . $i . "Type"] . ":</i> " ;
												}
												if ($row["phone" . $i . "CountryCode"]!="") {
													print "+" . $row["phone" . $i . "CountryCode"] . " " ;
												}
												print formatPhone($row["phone" . $i]) . "<br/>" ;
											print "</td>" ;
										}
									}
									for ($i=($numberCount+1); $i<5; $i++) {
											print "<td width: 33%; style='vertical-align: top'></td>" ;
									}
								print "</tr>" ;
							}
							print "<tr>" ;
								print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>" . _('Email') . "</span><br/>" ;
									if ($row["email"]!="") {
										print "<i><a href='mailto:" . $row["email"] . "'>" . $row["email"] . "</a></i>" ;
									}
								print "</td>" ;
								print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>" . _('Alternate Email') . "</span><br/>" ;
									if ($row["emailAlternate"]!="") {
										print "<i><a href='mailto:" . $row["emailAlternate"] . "'>" . $row["emailAlternate"] . "</a></i>" ;
									}
								print "</td>" ;
								print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>" . _('Website') . "</span><br/>" ;
									if ($row["website"]!="") {
										print "<i><a href='" . $row["website"] . "'>" . $row["website"] . "</a></i>" ;
									}
								print "</td>" ;
								print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
									
								print "</td>" ;
							print "</tr>" ;
							if ($row["address1"]!="") {
								print "<tr>" ;
									print "<td style='width: 33%; padding-top: 15px; vertical-align: top' colspan=3>" ;
										print "<span style='font-size: 115%; font-weight: bold'>" . _('Address 1') . "</span><br/>" ;
										$address1=addressFormat( $row["address1"], $row["address1District"], $row["address1Country"] ) ;
										if ($address1!=FALSE) {
											print $address1 ;
										}
									print "</td>" ;
								print "</tr>" ;
							}
							if ($row["address2"]!="") {
								print "<tr>" ;
									print "<td style='width: 33%; padding-top: 15px; vertical-align: top' colspan=3>" ;
										print "<span style='font-size: 115%; font-weight: bold'>" . _('Address 2') . "</span><br/>" ;
										$address2=addressFormat( $row["address2"], $row["address2District"], $row["address2Country"] ) ;
										if ($address2!=FALSE) {
											print $address2 ;
										}
									print "</td>" ;
								print "</tr>" ;
							}
						print "</table>" ;	
						
						print "<h4>" ;
						print _("Miscellaneous") ;
						print "</h4>" ;
						
						print "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>" ;
							print "<tr>" ;
								print "<td style='width: 33%; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>" . _('Transport') . "</span><br/>" ;
									print $row["transport"] ;
								print "</td>" ;
								print "<td style='width: 33%; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>" . _('Vehicle Registration') . "</span><br/>" ;
									print $row["vehicleRegistration"] ;
								print "</td>" ;
								print "<td style='width: 33%; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>" . _('Locker Number') . "</span><br/>" ;
									print $row["lockerNumber"] ;
								print "</td>" ;
							print "</tr>" ;
						print "</table>" ;
					}
					else if ($subpage=="Emergency Contacts") {
						if (isActionAccessible($guid, $connection2, "/modules/User Admin/user_manage.php")==TRUE) {
							print "<div class='linkTop'>" ;
							print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/User Admin/user_manage_edit.php&gibbonPersonID=$gibbonPersonID'><img style='margin: 0 0 -4px 3px' title='Edit User' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/config.png'/></a> " ;
							print "</div>" ;
						}
						
						print "<p>" ;
						print _("In an emergency, please try and contact the adult family members listed below first. If these cannot be reached, then try the emergency contacts below.") ;
						print "</p>" ;
						
						print "<h4>" ;
						print _("Adult Family Members") ;
						print "</h4>" ;
						
						try {
							$dataFamily=array("gibbonPersonID"=>$gibbonPersonID); 
							$sqlFamily="SELECT * FROM gibbonFamily JOIN gibbonFamilyChild ON (gibbonFamily.gibbonFamilyID=gibbonFamilyChild.gibbonFamilyID) WHERE gibbonPersonID=:gibbonPersonID" ;
							$resultFamily=$connection2->prepare($sqlFamily);
							$resultFamily->execute($dataFamily);
						}
						catch(PDOException $e) { 
							print "<div class='error'>" . $e->getMessage() . "</div>" ; 
						}
						
						if ($resultFamily->rowCount()!=1) {
							print "<div class='error'>" ;
								print _("There is no family information available for the current staff member.");
							print "</div>" ;
						}
						else {
							$rowFamily=$resultFamily->fetch() ;
							$count=1 ;
							//Get adults
							try {
								$dataMember=array("gibbonFamilyID"=>$rowFamily["gibbonFamilyID"]); 
								$sqlMember="SELECT * FROM gibbonFamilyAdult JOIN gibbonPerson ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonFamilyID=:gibbonFamilyID ORDER BY contactPriority, surname, preferredName" ;
								$resultMember=$connection2->prepare($sqlMember);
								$resultMember->execute($dataMember);
							}
							catch(PDOException $e) { 
								print "<div class='error'>" . $e->getMessage() . "</div>" ; 
							}
							
							while ($rowMember=$resultMember->fetch()) {
								print "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>" ;
									print "<tr>" ;
										print "<td style='width: 33%; vertical-align: top'>" ;
											print "<span style='font-size: 115%; font-weight: bold'>" . _('Name') . "</span><br/>" ;
												print formatName($rowMember["title"], $rowMember["preferredName"], $rowMember["surname"], "Parent") ;
										print "</td>" ;
										print "<td style='width: 33%; vertical-align: top'>" ;
											print "<span style='font-size: 115%; font-weight: bold'>" . _('Relationship') . "</span><br/>" ;
												if ($rowMember["role"]=="Parent") {
													if ($rowMember["gender"]=="M") {
														print _("Father") ;
													}
													else if ($rowMember["gender"]=="F") {
														print _("Mother") ;
													}
													else {
														print $rowMember["role"] ;
													}
												}
												else {
													print $rowMember["role"] ;
												}
										print "</td>" ;
										print "<td style='width: 34%; vertical-align: top'>" ;
											print "<span style='font-size: 115%; font-weight: bold'>" . _('Contact By Phone') . "</span><br/>" ;
											for ($i=1; $i<5; $i++) {
												if ($rowMember["phone" . $i]!="") {
													if ($rowMember["phone" . $i . "Type"]!="") {
														print "<i>" . $rowMember["phone" . $i . "Type"] . ":</i> " ;
													}
													if ($rowMember["phone" . $i . "CountryCode"]!="") {
														print "+" . $rowMember["phone" . $i . "CountryCode"] . " " ;
													}
													print formatPhone($rowMember["phone" . $i]) . "<br/>" ;
												}
											}
										print "</td>" ;
									print "</tr>" ;
								print "</table>" ;
								$count++ ;
							}	
						}
							
						print "<h4>" ;
						print _("Emergency Contacts") ;
						print "</h4>" ;
						print "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>" ;
							print "<tr>" ;
								print "<td style='width: 33%; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>" . _('Contact 1') . "</span><br/>" ;
									print "<i>" . $row["emergency1Name"] . "</i>" ;
									if ($row["emergency1Relationship"]!="") {
										print " (" . $row["emergency1Relationship"] . ")" ;
									}
								print "</td>" ;
								print "<td style='width: 33%; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>" . _('Number 1') . "</span><br/>" ;
									print $row["emergency1Number1"] ;
								print "</td>" ;
								print "<td style=width: 34%; 'vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>" . _('Number 2') . "</span><br/>" ;
									if ($row["emergency1Number2"]!="") {
										print $row["emergency1Number2"] ;
									}
								print "</td>" ;
							print "</tr>" ;
							print "<tr>" ;
								print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>" . _('Contact 2') . "</span><br/>" ;
									print "<i>" . $row["emergency2Name"] . "</i>" ;
									if ($row["emergency2Relationship"]!="") {
										print " (" . $row["emergency2Relationship"] . ")" ;
									}
								print "</td>" ;
								print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>" . _('Number 1') . "</span><br/>" ;
									print $row["emergency2Number1"] ;
								print "</td>" ;
								print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>" . _('Number 2') . "</span><br/>" ;
									if ($row["emergency2Number2"]!="") {
										print $row["emergency2Number2"] ;
									}
								print "</td>" ;
							print "</tr>" ;
						print "</table>" ;
					}
					else if ($subpage=="Timetable") {
						if (isActionAccessible($guid, $connection2, "/modules/Timetable/tt_view.php")==FALSE) {
							print "<div class='error'>" ;
								print _("The selected record does not exist, or you do not have access to it.");
							print "</div>" ;
						}
						else {
							if (isActionAccessible($guid, $connection2, "/modules/Timetable Admin/courseEnrolment_manage_byPerson_edit.php")==TRUE) {
								print "<div class='linkTop'>" ;
								print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Timetable Admin/courseEnrolment_manage_byPerson_edit.php&gibbonPersonID=$gibbonPersonID&gibbonSchoolYearID=" . $_SESSION[$guid]["gibbonSchoolYearID"] . "&type=Staff&allUsers='><img style='margin: 0 0 -4px 3px' title='Edit Timetable' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/planner.gif'/></a> " ;
								print "</div>" ;
							}
						
							include "./modules/Timetable/moduleFunctions.php" ;
							$ttDate="" ;
							if (isset($_POST["ttDate"])) {
								$ttDate=dateConvertToTimestamp(dateConvert($guid, $_POST["ttDate"]));
							}
							$tt=renderTT($guid, $connection2,$gibbonPersonID, "", FALSE, $ttDate, "/modules/Staff/staff_view_details.php", "&gibbonPersonID=$gibbonPersonID&subpage=Timetable&search=$search") ;
							if ($tt!=FALSE) {
								print $tt ;
							}
							else {
								print "<div class='error'>" ;
									print _("The selected record does not exist, or you do not have access to it.");
								print "</div>" ;
							}
						}
					}
					
					
					//Set sidebar
					$_SESSION[$guid]["sidebarExtra"]="" ;
					
					//Show pic
					$_SESSION[$guid]["sidebarExtra"].=getUserPhoto($guid, $row["image_240"], 240) ;
					
					//PERSONAL DATA MENU ITEMS
					$_SESSION[$guid]["sidebarExtra"].="<h4>Personal</h4>" ;
					$_SESSION[$guid]["sidebarExtra"].="<ul>" ;
					$style="" ;
					if ($subpage=="Summary") {
						$style="style='font-weight: bold'" ;
					}
					$_SESSION[$guid]["sidebarExtra"].="<li><a $style href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=" . $_GET["q"] . "&gibbonPersonID=$gibbonPersonID&search=" . $search . "&subpage=Summary'>" . _('Summary') . "</a></li>" ;
					$style="" ;
					if ($subpage=="Personal") {
						$style="style='font-weight: bold'" ;
					}
					$_SESSION[$guid]["sidebarExtra"].="<li><a $style href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=" . $_GET["q"] . "&gibbonPersonID=$gibbonPersonID&search=" . $search . "&subpage=Personal'>" . _('Personal') . "</a></li>" ;
					$style="" ;
					if ($subpage=="Emergency Contacts") {
						$style="style='font-weight: bold'" ;
					}
					$_SESSION[$guid]["sidebarExtra"].="<li><a $style href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=" . $_GET["q"] . "&gibbonPersonID=$gibbonPersonID&search=" . $search . "&subpage=Emergency Contacts'>" . _('Emergency Contacts') . "</a></li>" ;
					if (isActionAccessible($guid, $connection2, "/modules/Timetable/tt_view.php")) {
						$style="" ;
						if ($subpage=="Timetable") {
							$style="style='font-weight: bold'" ;
						}
						$_SESSION[$guid]["sidebarExtra"].="<li><a $style href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=" . $_GET["q"] . "&gibbonPersonID=$gibbonPersonID&search=" . $search . "&subpage=Timetable'>" . _('Timetable') . "</a></li>" ;
					}
					$_SESSION[$guid]["sidebarExtra"].="</ul>" ;
					
				}
			}
		}
	}
}
?>