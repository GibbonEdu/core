<?
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
		print "You do not have access to this action." ;
	print "</div>" ;
}
else {
	//Get action with highest precendence
	$highestAction=getHighestGroupedAction($guid, $_GET["q"], $connection2) ;
	if ($highestAction==FALSE) {
		print "<div class='error'>" ;
		print "The highest grouped action cannot be determined." ;
		print "</div>" ;
	}
	else {
		$gibbonPersonID=$_GET["gibbonPersonID"] ;
		if ($gibbonPersonID==FALSE) {
			print "<div class='error'>" ;
			print "You have not specified one or more required parameters." ;
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
					print "The specified staff member does not seem to exist." ;
					print "</div>" ;
					print "</div>" ;
				}
				else {
					$row=$result->fetch() ;
					
					print "<div class='trail'>" ;
					print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/staff_view.php'>View Staff Profiles</a> > </div><div class='trailEnd'>" . formatName("", $row["preferredName"], $row["surname"], "Student") . "</div>" ;
					print "</div>" ;
					
					if ($search!="") {
						print "<div class='linkTop'>" ;
							print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Staff/staff_view.php&search=" . $search . "'>Back to Search Results</a>" ;
						print "</div>" ;
					}
					
					print "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>" ;
						print "<tr>" ;
							print "<td style='width: 33%; vertical-align: top'>" ;
								print "<span style='font-size: 115%; font-weight: bold'>Name</span><br/>" ;
								print "<i>" . formatName($row["title"], $row["preferredName"], $row["surname"], "Parent") . "</i>" ;
							print "</td>" ;
							print "<td style='width: 33%; vertical-align: top'>" ;
								print "<span style='font-size: 115%; font-weight: bold'>Staff Type</span><br/>" ;
								print "<i>" . $row["type"] . "</i>" ;
							print "</td>" ;
							print "<td style='width: 33%; vertical-align: top'>" ;
								print "<span style='font-size: 115%; font-weight: bold'>Job Title</span><br/>" ;
								print "<i>" . $row["jobTitle"] . "</i>" ;
							print "</td>" ;
						print "</tr>" ;
						print "<tr>" ;
							print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
								print "<span style='font-size: 115%; font-weight: bold'>Email</span><br/>" ;
								if ($row["email"]!="") {
									print "<i><a href='mailto:" . $row["email"] . "'>" . $row["email"] . "</a></i>" ;
								}
							print "</td>" ;
							print "<td style='width: 67%; padding-top: 15px; vertical-align: top' colspan=2>" ;
								print "<span style='font-size: 115%; font-weight: bold'>Website</span><br/>" ;
								if ($row["website"]!="") {
									print "<i><a href='" . $row["website"] . "'>" . $row["website"] . "</a></i>" ;
								}
							print "</td>" ;
						print "</tr>" ;
					print "</table>" ;
					
					print "<h4>" ;
						print "Biography" ;
					print "</h4>" ;
					print "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>" ;
						print "<tr>" ;
							print "<td style='width: 33%; vertical-align: top'>" ;
								print "<span style='font-size: 115%; font-weight: bold'>Country Of Origin</span><br/>" ;
								print "<i>" . $row["countryOfOrigin"] . "</i>" ;
							print "</td>" ;
							print "<td style='width: 67%; vertical-align: top' colspan=2>" ;
								print "<span style='font-size: 115%; font-weight: bold'>Qualifications</span><br/>" ;
								print "<i>" . $row["qualifications"] . "</i>" ;
							print "</td>" ;
						print "</tr>" ;
						print "<tr>" ;
							print "<td style='width: 100%; vertical-align: top' colspan=3>" ;
								print "<span style='font-size: 115%; font-weight: bold'>Biography</span><br/>" ;
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
					print "The specified staff member does not seem to exist." ;
					print "</div>" ;
				}
				else {
					$row=$result->fetch() ;
					
					print "<div class='trail'>" ;
					print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/staff_view.php'>View Staff Profiles</a> > </div><div class='trailEnd'>" . formatName("", $row["preferredName"], $row["surname"], "Student") . "</div>" ;
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
							print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Staff/staff_view.php&search=" . $search . "'>Back to Search Results</a>" ;
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
							print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/User Admin/user_manage_edit.php&gibbonPersonID=$gibbonPersonID'>Edit User<img style='margin: 0 0 -4px 3px' title='Enter Data' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/config.png'/></a> " ;
							print "</div>" ;
						}
					
						print "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>" ;
							print "<tr>" ;
								print "<td style='width: 33%; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>Name</span><br/>" ;
									print "<i>" . formatName($row["title"], $row["preferredName"], $row["surname"], "Parent") . "</i>" ;
								print "</td>" ;
								print "<td style='width: 33%; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>Staff Type</span><br/>" ;
									print "<i>" . $row["type"] . "</i>" ;
								print "</td>" ;
								print "<td style='width: 33%; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>Job Title</span><br/>" ;
									print "<i>" . $row["jobTitle"] . "</i>" ;
								print "</td>" ;
							print "</tr>" ;
							print "<tr>" ;
								print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>Username</span><br/>" ;
									print "<i>" . $row["username"] . "</i>" ;
								print "</td>" ;
								print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>Website</span><br/>" ;
									if ($row["website"]!="") {
										print "<i><a href='" . $row["website"] . "'>" . $row["website"] . "</a></i>" ;
									}
								print "</td>" ;
								print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>Email</span><br/>" ;
									if ($row["email"]!="") {
										print "<i><a href='mailto:" . $row["email"] . "'>" . $row["email"] . "</a></i>" ;
									}
								print "</td>" ;
							print "</tr>" ;
						print "</table>" ;
						
						print "<h4>" ;
							print "Biography" ;
						print "</h4>" ;
						print "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>" ;
							print "<tr>" ;
								print "<td style='width: 33%; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>Country Of Origin</span><br/>" ;
									print "<i>" . $row["countryOfOrigin"] . "</i>" ;
								print "</td>" ;
								print "<td style='width: 67%; vertical-align: top' colspan=2>" ;
									print "<span style='font-size: 115%; font-weight: bold'>Qualifications</span><br/>" ;
									print "<i>" . $row["qualifications"] . "</i>" ;
								print "</td>" ;
							print "</tr>" ;
							print "<tr>" ;
								print "<td style='width: 100%; vertical-align: top' colspan=3>" ;
									print "<span style='font-size: 115%; font-weight: bold'>Biography</span><br/>" ;
									print "<i>" . $row["biography"] . "</i>" ;
								print "</td>" ;
							print "</tr>" ;
						print "</table>" ;
					}
					else if ($subpage=="Personal") {
						if (isActionAccessible($guid, $connection2, "/modules/User Admin/user_manage.php")==TRUE) {
							print "<div class='linkTop'>" ;
							print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/User Admin/user_manage_edit.php&gibbonPersonID=$gibbonPersonID'>Edit User<img style='margin: 0 0 -4px 3px' title='Enter Data' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/config.png'/></a> " ;
							print "</div>" ;
						}
						
						print "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>" ;
							print "<tr>" ;
								print "<td style='width: 33%; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>Name</span><br/>" ;
									print "<i>" . formatName($row["title"], $row["preferredName"], $row["surname"], "Parent") . "</i>" ;
								print "</td>" ;
								print "<td style='width: 33%; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>Staff Type</span><br/>" ;
									print "<i>" . $row["type"] . "</i>" ;
								print "</td>" ;
								print "<td style='width: 33%; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>Job Title</span><br/>" ;
									print "<i>" . $row["jobTitle"] . "</i>" ;
								print "</td>" ;
							print "</tr>" ;	
							print "<tr>" ;
								print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>Initials</span><br/>" ;
									print $row["initials"] ;
								print "</td>" ;
								print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>Gender</span><br/>" ;
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
												print "<span style='font-size: 115%; font-weight: bold'>Phone $numberCount</span><br/>" ;
												if ($row["phone" . $i . "Type"]!="") {
													print "<i>" . $row["phone" . $i . "Type"] . ":</i> " ;
												}
												if ($row["phone" . $i . "CountryCode"]!="") {
													print "+" . $row["phone" . $i . "CountryCode"] . " " ;
												}
												print $row["phone" . $i] . "<br/>" ;
											print "</td>" ;
										}
										else {
											print "<td width: 33%; style='vertical-align: top'>" ;
											
											print "</td>" ;
										}
									}
								print "</tr>" ;
							}
							print "<tr>" ;
								print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>Email</span><br/>" ;
									if ($row["email"]!="") {
										print "<i><a href='mailto:" . $row["email"] . "'>" . $row["email"] . "</a></i>" ;
									}
								print "</td>" ;
								print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>Alternate Email</span><br/>" ;
									if ($row["emailAlternate"]!="") {
										print "<i><a href='mailto:" . $row["emailAlternate"] . "'>" . $row["emailAlternate"] . "</a></i>" ;
									}
								print "</td>" ;
								print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>Website</span><br/>" ;
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
										print "<span style='font-size: 115%; font-weight: bold'>Address 1</span><br/>" ;
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
										print "<span style='font-size: 115%; font-weight: bold'>Address 2</span><br/>" ;
										$address2=addressFormat( $row["address2"], $row["address2District"], $row["address2Country"] ) ;
										if ($address2!=FALSE) {
											print $address2 ;
										}
									print "</td>" ;
								print "</tr>" ;
							}
						print "</table>" ;	
						
						print "<h4>" ;
						print "Miscellaneous" ;
						print "</h4>" ;
						
						print "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>" ;
							print "<tr>" ;
								print "<td style='width: 33%; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>Transport</span><br/>" ;
									print $row["transport"] ;
								print "</td>" ;
								print "<td style='width: 33%; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>Vehicle Registration</span><br/>" ;
									print $row["vehicleRegistration"] ;
								print "</td>" ;
								print "<td style='width: 33%; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>Locker Number</span><br/>" ;
									print $row["lockerNumber"] ;
								print "</td>" ;
							print "</tr>" ;
						print "</table>" ;
					}
					else if ($subpage=="Emergency Contacts") {
						if (isActionAccessible($guid, $connection2, "/modules/User Admin/user_manage.php")==TRUE) {
							print "<div class='linkTop'>" ;
							print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/User Admin/user_manage_edit.php&gibbonPersonID=$gibbonPersonID'>Edit User<img style='margin: 0 0 -4px 3px' title='Enter Data' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/config.png'/></a> " ;
							print "</div>" ;
						}
						
						print "<p>" ;
						print "In an emergency, please try and contact the adult family members listed below first. If these cannot be reached, then try the emergency contacts below." ;
						print "</p>" ;
						
						print "<h4>" ;
						print "1. Adult Family Members" ;
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
								print "There is no family information available for the current student.";
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
											print "<span style='font-size: 115%; font-weight: bold'>Name</span><br/>" ;
												print formatName($rowMember["title"], $rowMember["preferredName"], $rowMember["surname"], "Parent") ;
										print "</td>" ;
										print "<td style='width: 33%; vertical-align: top'>" ;
											print "<span style='font-size: 115%; font-weight: bold'>Relationship</span><br/>" ;
												if ($rowMember["role"]=="Parent") {
													if ($rowMember["gender"]=="M") {
														print "Father" ;
													}
													else if ($rowMember["gender"]=="F") {
														print "Mother" ;
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
											print "<span style='font-size: 115%; font-weight: bold'>Contact By Phone</span><br/>" ;
											for ($i=1; $i<5; $i++) {
												if ($rowMember["phone" . $i]!="") {
													if ($rowMember["phone" . $i . "Type"]!="") {
														print "<i>" . $rowMember["phone" . $i . "Type"] . ":</i> " ;
													}
													if ($rowMember["phone" . $i . "CountryCode"]!="") {
														print "+" . $rowMember["phone" . $i . "CountryCode"] . " " ;
													}
													print $rowMember["phone" . $i] . "<br/>" ;
												}
											}
										print "</td>" ;
									print "</tr>" ;
								print "</table>" ;
								$count++ ;
							}	
						}
							
						print "<h4>" ;
						print "2. Emergency Contacts" ;
						print "</h4>" ;
						print "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>" ;
							print "<tr>" ;
								print "<td style='width: 33%; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>Contact 1</span><br/>" ;
									print "<i>" . $row["emergency1Name"] . "</i>" ;
									if ($row["emergency1Relationship"]!="") {
										print " (" . $row["emergency1Relationship"] . ")" ;
									}
								print "</td>" ;
								print "<td style='width: 33%; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>Number 1</span><br/>" ;
									print $row["emergency1Number1"] ;
								print "</td>" ;
								print "<td style=width: 34%; 'vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>Number 2</span><br/>" ;
									if ($row["website"]!="") {
										print $row["emergency1Number2"] ;
									}
								print "</td>" ;
							print "</tr>" ;
							print "<tr>" ;
								print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>Contact 2</span><br/>" ;
									print "<i>" . $row["emergency2Name"] . "</i>" ;
									if ($row["emergency2Relationship"]!="") {
										print " (" . $row["emergency2Relationship"] . ")" ;
									}
								print "</td>" ;
								print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>Number 1</span><br/>" ;
									print $row["emergency2Number1"] ;
								print "</td>" ;
								print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>Number 2</span><br/>" ;
									if ($row["website"]!="") {
										print $row["emergency2Number2"] ;
									}
								print "</td>" ;
							print "</tr>" ;
						print "</table>" ;
					}
					else if ($subpage=="Timetable") {
						if (isActionAccessible($guid, $connection2, "/modules/Timetable/tt_view.php")==FALSE) {
							print "<div class='error'>" ;
								print "You do not have permission to access this data.";
							print "</div>" ;
						}
						else {
							if (isActionAccessible($guid, $connection2, "/modules/Timetable Admin/courseEnrolment_manage_byPerson_class_edit.php")==TRUE) {
								$role=getRoleCategory($row["gibbonRoleIDPrimary"], $connection2) ;
								if ($role=="Student" OR $role=="Staff") {
									print "<div class='linkTop'>" ;
									print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Timetable Admin/courseEnrolment_manage_byPerson_class_edit.php&gibbonPersonID=$gibbonPersonID&gibbonSchoolYearID=" . $_SESSION[$guid]["gibbonSchoolYearID"] . "&type=$role'>Edit Timetable<img style='margin: 0 0 -4px 3px' title='Enter Data' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/planner.gif'/></a> " ;
									print "</div>" ;
								}
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
									print "There is no timetable information in the current academic year for the date specified." ;
								print "</div>" ;
							}
						}
					}
					else if ($subpage=="External Assessment") {
						if (isActionAccessible($guid, $connection2, "/modules/External Assessment/externalAssessment_details.php")==FALSE) {
							print "<div class='error'>" ;
								print "You do not have permission to access this data.";
							print "</div>" ;
						}
						else {
							include "./modules/External Assessment/moduleFunctions.php" ;
							
							//Print assessments
							externalAssessmentDetails($guid, $gibbonPersonID, $connection2, $row["gibbonYearGroupID"]) ;
						}
					}
					else if ($subpage=="Activities") {
						if (!(isActionAccessible($guid, $connection2, "/modules/Activities/report_activityChoices_byStudent"))) {
							print "<div class='error'>" ;
								print "You do not have permission to access this data.";
							print "</div>" ;
						}
						else {
							print "<p>" ;
							print "This report shows the current and historical activities that a student has enrolled in." ;
							print "</p>" ;

							$dateType=getSettingByScope($connection2, 'Activities', 'dateType') ;
							if ($dateType=="Term" ) {
								$maxPerTerm=getSettingByScope($connection2, 'Activities', 'maxPerTerm') ;
							}
							
							try {
								$dataYears=array("gibbonPersonID"=>$gibbonPersonID); 
								$sqlYears="SELECT * FROM gibbonStudentEnrolment JOIN gibbonSchoolYear ON (gibbonStudentEnrolment.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) WHERE gibbonPersonID=:gibbonPersonID ORDER BY sequenceNumber DESC" ;
								$resultYears=$connection2->prepare($sqlYears);
								$resultYears->execute($dataYears);
							}
							catch(PDOException $e) { 
								print "<div class='error'>" . $e->getMessage() . "</div>" ; 
							}

							if ($resultYears->rowCount()<1) {
								print "<div class='error'>" ;
								print "The specified student has not been enrolled in any school years." ;
								print "</div>" ;
							}
							else {
								$yearCount=0 ;
								while ($rowYears=$resultYears->fetch()) {
			
									$class="" ;
									if ($yearCount==0) {
										$class="class='top'" ;
									}
									print "<h3 $class>" ;
									print $rowYears["name"] ;
									print "</h3>" ;
			
									$yearCount++ ;
									try {
										$data=array("gibbonPersonID"=>$gibbonPersonID, "gibbonSchoolYearID"=>$rowYears["gibbonSchoolYearID"]); 
										$sql="SELECT gibbonActivity.*, gibbonActivityStudent.status, NULL AS role FROM gibbonActivity JOIN gibbonActivityStudent ON (gibbonActivity.gibbonActivityID=gibbonActivityStudent.gibbonActivityID) WHERE gibbonActivityStudent.gibbonPersonID=:gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID AND active='Y' ORDER BY name" ; 
										$result=$connection2->prepare($sql);
										$result->execute($data);
									}
									catch(PDOException $e) { 
										print "<div class='error'>" . $e->getMessage() . "</div>" ; 
									}
						
									if ($result->rowCount()<1) {
										print "<div class='error'>" ;
										print _("There are no records to display.") ;
										print "</div>" ;
									}
									else {
										print "<table cellspacing='0' style='width: 100%'>" ;
											print "<tr class='head'>" ;
												print "<th>" ;
													print "Activity" ;
												print "</th>" ;
												$options=getSettingByScope($connection2, "Activities", "activityTypes") ;
												if ($options!="") {
													print "<th>" ;
														print "Type" ;
													print "</th>" ;
												}
												print "<th>" ;
													if ($dateType!="Date") {
														print "Term" ;
													}
													else {
														print "Dates" ;
													}
												print "</th>" ;
												print "<th>" ;
													print "Status" ;
												print "</th>" ;
												print "<th>" ;
													print "Actions" ;
												print "</th>" ;
											print "</tr>" ;
									
											$count=0;
											$rowNum="odd" ;
											while ($row=$result->fetch()) {
												if ($count%2==0) {
													$rowNum="even" ;
												}
												else {
													$rowNum="odd" ;
												}
										
												if ($row["gibbonActivityStudentID"]!="") {
													$rowNum="current" ;
												}
												$count++ ;
										
												//COLOR ROW BY STATUS!
												print "<tr class=$rowNum>" ;
													print "<td>" ;
														print $row["name"] ;
													print "</td>" ;
													if ($options!="") {
														print "<td>" ;
															print trim($row["type"]) ;
														print "</td>" ;
													}
													print "<td>" ;
														if ($dateType!="Date") {
															$terms=getTerms($connection2, $_SESSION[$guid]["gibbonSchoolYearID"], true) ;
															$termList="" ;
															for ($i=0; $i<count($terms); $i=$i+2) {
																if (is_numeric(strpos($row["gibbonSchoolYearTermIDList"], $terms[$i]))) {
																	$termList.=$terms[($i+1)] . "<br/>" ;
																}
															}
															print $termList ;
														}
														else {
															if (substr($row["programStart"],0,4)==substr($row["programEnd"],0,4)) {
																if (substr($row["programStart"],5,2)==substr($row["programEnd"],5,2)) {
																	print date("F", mktime(0, 0, 0, substr($row["programStart"],5,2))) . " " . substr($row["programStart"],0,4) ;
																}
																else {
																	print date("F", mktime(0, 0, 0, substr($row["programStart"],5,2))) . " - " . date("F", mktime(0, 0, 0, substr($row["programEnd"],5,2))) . "<br/>" . substr($row["programStart"],0,4) ;
																}
															}
															else {
																print date("F", mktime(0, 0, 0, substr($row["programStart"],5,2))) . " " . substr($row["programStart"],0,4) . " -<br/>" . date("F", mktime(0, 0, 0, substr($row["programEnd"],5,2))) . " " . substr($row["programEnd"],0,4) ;
															}
														}
													print "</td>" ;
													print "<td>" ;
														if ($row["status"]!="") {
															print $row["status"] ;
														}
														else {
															print "<i>NA</i>" ;
														}
													print "</td>" ;
													print "<td>" ;
														print "<a class='thickbox' href='" . $_SESSION[$guid]["absoluteURL"] . "/fullscreen.php?q=/modules/Activities/activities_my_full.php&gibbonActivityID=" . $row["gibbonActivityID"] . "&width=1000&height=550'><img title='View Details' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/plus.png'/></a> " ;
													print "</td>" ;
												print "</tr>" ;
											}
										print "</table>" ;		
									}
								}
							}
						}
					}
					else if ($subpage=="Homework") {
						if (!(isActionAccessible($guid, $connection2, "/modules/Planner/planner_edit.php") OR isActionAccessible($guid, $connection2, "/modules/Planner/planner_view_full.php"))) {
							print "<div class='error'>" ;
								print "You do not have permission to access this data.";
							print "</div>" ;
						}
						else {
							print "<h4>" ;
							print "Upcoming Deadlines" ;
							print "</h4>" ;
							
							try {
								$data=array("gibbonPersonID"=>$gibbonPersonID, "homeworkDueDateTime"=>date("Y-m-d H:i:s"), "date1"=>date("Y-m-d"), "date2"=>date("Y-m-d"), "timeEnd"=>date("H:i:s")); 
								$sql="SELECT gibbonPlannerEntryID, gibbonUnitID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, date, timeStart, timeEnd, viewableStudents, viewableParents, homework, homeworkDueDateTime, role FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND NOT role='Student - Left' AND NOT role='Teacher - Left' AND homework='Y' AND role='Student' AND viewableParents='Y' AND homeworkDueDateTime>:homeworkDueDateTime AND (date<:date1 OR (date=:date2 AND timeEnd<=:timeEnd)) ORDER BY homeworkDueDateTime" ;
								$result=$connection2->prepare($sql);
								$result->execute($data);
							}
							catch(PDOException $e) { 
								print "<div class='error'>" . $e->getMessage() . "</div>" ; 
							}
							
							if ($result->rowCount()<1) {
								print "<div class='success'>" ;
									print "No upcoming deadlines!" ;
								print "</div>" ;
							}
							else {
								print "<ol>" ;
								while ($row=$result->fetch()) {
									$diff=(strtotime(substr($row["homeworkDueDateTime"],0,10)) - strtotime(date("Y-m-d")))/86400 ;
									$style="style='padding-right: 3px;'" ;
									if ($diff<2) {
										$style="style='padding-right: 3px; border-right: 10px solid #cc0000'" ;	
									}
									else if ($diff<4) {
										$style="style='padding-right: 3px; border-right: 10px solid #D87718'" ;	
									}
									print "<li $style>" ;
									if ($viewBy=="class") {
										print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Planner/planner_view_full.php&search=$gibbonPersonID&gibbonPlannerEntryID=" . $row["gibbonPlannerEntryID"] . "&viewBy=class&gibbonCourseClassID=$gibbonCourseClassID&width=1000&height=550'>" . $row["course"] . "." . $row["class"] . "</a><br/>" ;
									}
									else {
										print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Planner/planner_view_full.php&search=$gibbonPersonID&gibbonPlannerEntryID=" . $row["gibbonPlannerEntryID"] . "&viewBy=date&date=$date&width=1000&height=550'>" . $row["course"] . "." . $row["class"] . "</a><br/>" ;
									}
									print "<span style='font-style: italic'>Due at " . substr($row["homeworkDueDateTime"],11,5) . " on " . dateConvertBack($guid, substr($row["homeworkDueDateTime"],0,10)) ;
									print "</li>" ;
								}
								print "</ol>" ;
							}
							
							$style="" ;
							
							print "<h4>" ;
							print "Homework History" ;
							print "</h4>" ;
							
							$gibbonCourseClassIDFilter=$_GET["gibbonCourseClassIDFilter"] ;
							if ($_GET["gibbonCourseClassIDFilter"]!="") {
								$filter=" AND gibbonPlannerEntry.gibbonCourseClassID=$gibbonCourseClassIDFilter" ;
							}
							
							try {
								$data=array("gibbonPersonID"=>$gibbonPersonID, "gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
								$sql="SELECT gibbonPlannerEntryID, gibbonUnitID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, date, timeStart, timeEnd, viewableStudents, viewableParents, homework, role, homeworkDueDateTime, homeworkDetails, homeworkSubmission, homeworkSubmissionRequired FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND NOT role='Student - Left' AND NOT role='Teacher - Left' AND homework='Y' AND gibbonSchoolYearID=:gibbonSchoolYearID $filter ORDER BY date DESC, timeStart DESC" ; 
								$result=$connection2->prepare($sql);
								$result->execute($data);
							}
							catch(PDOException $e) { 
								print "<div class='error'>" . $e->getMessage() . "</div>" ; 
							}
							
							if ($result->rowCount()<1) {
								print "<div class='error'>" ;
								print _("There are no records to display.") ;
								print "</div>" ;
							}
							else {
								print "<div class='linkTop'>" ;
									print "<form method='get' action='" . $_SESSION[$guid]["absoluteURL"] . "/index.php'>" ;
										print"<table cellspacing='0' style='float: right; width: 250px; margin: 0px 0px'>" ;	
											print"<tr>" ;
												print"<td style='width: 190px'>" ; 
													print"<select name='gibbonCourseClassIDFilter' id='gibbonCourseClassIDFilter' style='width:190px'>" ;
														print"<option value=''></option>" ;
														try {
															$dataSelect=array("gibbonPersonID"=>$gibbonPersonID, "gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "date"=>date("Y-m-d")); 
															$sqlSelect="SELECT DISTINCT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.gibbonCourseClassID FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND NOT role='Student - Left' AND NOT role='Teacher - Left' AND homework='Y' AND gibbonSchoolYearID=:gibbonSchoolYearID AND date<=:date ORDER BY course, class" ; 
															$resultSelect=$connection2->prepare($sqlSelect);
															$resultSelect->execute($dataSelect);
														}
														catch(PDOException $e) { }
														while ($rowSelect=$resultSelect->fetch()) {
															$selected="" ;
															if ($rowSelect["gibbonCourseClassID"]==$gibbonCourseClassIDFilter) {
																$selected="selected" ;
															}
															print"<option $selected value='" . $rowSelect["gibbonCourseClassID"] . "'>" . htmlPrep($rowSelect["course"]) . "." . htmlPrep($rowSelect["class"]) . "</option>" ;
														}
													 print"</select>" ;
												print"</td>" ;
												print"<td class='right'>" ;
													print"<input type='submit' value='Go' style='margin-right: 0px'>" ;
													print"<input type='hidden' name='q' value='/modules/Students/student_view_details.php'>" ;
													print"<input type='hidden' name='subpage' value='Homework'>" ;
													print"<input type='hidden' name='gibbonPersonID' value='$gibbonPersonID'>" ;
												print"</td>" ;
											print"</tr>" ;
										print"</table>" ;
									print"</form>" ;
								print "</div>" ; 
								print "<table cellspacing='0' style='width: 100%'>" ;
									print "<tr class='head'>" ;
										print "<th>" ;
											print "Class/Date" ;
										print "</th>" ;
										print "<th>" ;
											print "Lesson/Unit" ;
										print "</th>" ;
										print "<th style='min-width: 25%'>" ;
											print "Details" ;
										print "</th>" ;
										print "<th>" ;
											print "Deadline" ;
										print "</th>" ;
										print "<th>" ;
											print "Online</br>Submission" ;
										print "</th>" ;
										print "<th>" ;
											print "Action" ;
										print "</th>" ;
									print "</tr>" ;
									
									$count=0;
									$rowNum="odd" ;
									while ($row=$result->fetch()) {
										if (!($row["role"]=="Student" AND $row["viewableParents"]=="N")) {
											if ($count%2==0) {
												$rowNum="even" ;
											}
											else {
												$rowNum="odd" ;
											}
											$count++ ;
											
											//Highlight class in progress
											if ((date("Y-m-d")==$row["date"]) AND (date("H:i:s")>$row["timeStart"]) AND (date("H:i:s")<$row["timeEnd"])) {
												$rowNum="current" ;
											}
											
											//COLOR ROW BY STATUS!
											print "<tr class=$rowNum>" ;
												print "<td>" ;
													print "<b>" . $row["course"] . "." . $row["class"] . "</b></br>" ;
													print dateConvertBack($guid, $row["date"]) ;
												print "</td>" ;
												print "<td>" ;
													print "<b>" . $row["name"] . "</b><br/>" ;
													if ($row["gibbonUnitID"]!="") {
														try {
															$dataUnit=array("gibbonUnitID"=>$row["gibbonUnitID"]); 
															$sqlUnit="SELECT * FROM gibbonUnit WHERE gibbonUnitID=:gibbonUnitID" ;
															$resultUnit=$connection2->prepare($sqlUnit);
															$resultUnit->execute($dataUnit);
														}
														catch(PDOException $e) { 
															print "<div class='error'>" . $e->getMessage() . "</div>" ; 
														}
														if ($resultUnit->rowCount()==1) {
															$rowUnit=$resultUnit->fetch() ;
															print $rowUnit["name"] ;
														}
													}
												print "</td>" ;
												print "<td>" ;
													if ($row["homeworkDetails"]!="") {
														if (strlen(strip_tags($row["homeworkDetails"]))<21) {
															print strip_tags($row["homeworkDetails"]) ;
														}
														else {
															print "<span $style title='" . htmlPrep(strip_tags($row["homeworkDetails"])) . "'>" . substr(strip_tags($row["homeworkDetails"]), 0, 20) . "...</span>" ;
														}
													}
												print "</td>" ;
												print "<td>" ;
													print dateConvertBack($guid, substr($row["homeworkDueDateTime"],0,10)) ;
												print "</td>" ;
												print "<td>" ;
													if ($row["homeworkSubmission"]=="Y") {
														print "<b>" . $row["homeworkSubmissionRequired"] . "<br/></b>" ;
														if ($row["role"]=="Student") {
															try {
																$dataVersion=array("gibbonPlannerEntryID"=>$row["gibbonPlannerEntryID"], "gibbonPersonID"=>$gibbonPersonID); 
																$sqlVersion="SELECT * FROM gibbonPlannerEntryHomework WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND gibbonPersonID=:gibbonPersonID ORDER BY count DESC" ;
																$resultVersion=$connection2->prepare($sqlVersion);
																$resultVersion->execute($dataVersion);
															}
															catch(PDOException $e) { 
																print "<div class='error'>" . $e->getMessage() . "</div>" ; 
															}
															if ($resultVersion->rowCount()<1) {
																//Before deadline
																if (date("Y-m-d H:i:s")<$row["homeworkDueDateTime"]) {
																	print "<span title='Pending'>Pending</span>" ;
																}
																//After
																else {
																	if ($row["dateStart"]>$rowSub["date"]) {
																		print "<span title='Student joined school after lesson was taught.' style='color: #000; font-weight: normal; border: 2px none #ff0000; padding: 2px 4px'>NA</span>" ;
																	}
																	else {
																		if ($row["homeworkSubmissionRequired"]=="Compulsory") {
																			print "<div style='color: #ff0000; font-weight: bold; border: 2px solid #ff0000; padding: 2px 4px; margin: 2px 0px'>Incomplete</div>" ;
																		}
																		else {
																			print "Not submitted online" ;
																		}
																	}
																}
															}
															else {
																$rowVersion=$resultVersion->fetch() ;
																if ($rowVersion["status"]=="On Time" OR $rowVersion["status"]=="Exemption") {
																	print $rowVersion["status"] ;
																} 
																else {
																	if ($row["homeworkSubmissionRequired"]=="Compulsory") {
																		print "<div style='color: #ff0000; font-weight: bold; border: 2px solid #ff0000; padding: 2px 4px; margin: 2px 0px'>" . $rowVersion["status"] . "</div>" ;
																	}
																	else {
																		print $rowVersion["status"] ;
																	}
																}
															}
														}
													}
												print "</td>" ;
												print "<td>" ;
													print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Planner/planner_view_full.php&search=$gibbonPersonID&gibbonPlannerEntryID=" . $row["gibbonPlannerEntryID"] . "&viewBy=class&gibbonCourseClassID=$gibbonCourseClassID&width=1000&height=550'><img title='View Details' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/plus.png'/></a> " ;
												print "</td>" ;
											print "</tr>" ;
										}
								}
								print "</table>" ;
							}
						}								
					}
					
					
					//Set sidebar
					$_SESSION[$guid]["sidebarExtra"]="" ;
					
					//Show pic
					$_SESSION[$guid]["sidebarExtra"].=getUserPhoto($guid, $row["image_240"], 240) ;
					
					//PERSONAL DATA MENU ITEMS
					$_SESSION[$guid]["sidebarExtra"].= "<h4>Personal</h4>" ;
					$_SESSION[$guid]["sidebarExtra"].= "<ul>" ;
					$style="" ;
					if ($subpage=="Summary") {
						$style="style='font-weight: bold'" ;
					}
					$_SESSION[$guid]["sidebarExtra"].= "<li><a $style href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=" . $_GET["q"] . "&gibbonPersonID=$gibbonPersonID&search=" . $search . "&subpage=Summary'>Summary</a></li>" ;
					$style="" ;
					if ($subpage=="Personal") {
						$style="style='font-weight: bold'" ;
					}
					$_SESSION[$guid]["sidebarExtra"].= "<li><a $style href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=" . $_GET["q"] . "&gibbonPersonID=$gibbonPersonID&search=" . $search . "&subpage=Personal'>Personal</a></li>" ;
					$style="" ;
					if ($subpage=="Emergency Contacts") {
						$style="style='font-weight: bold'" ;
					}
					$_SESSION[$guid]["sidebarExtra"].= "<li><a $style href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=" . $_GET["q"] . "&gibbonPersonID=$gibbonPersonID&search=" . $search . "&subpage=Emergency Contacts'>Emergency Contacts</a></li>" ;
					if (isActionAccessible($guid, $connection2, "/modules/Timetable/tt_view.php")) {
						$style="" ;
						if ($subpage=="Timetable") {
							$style="style='font-weight: bold'" ;
						}
						$_SESSION[$guid]["sidebarExtra"].= "<li><a $style href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=" . $_GET["q"] . "&gibbonPersonID=$gibbonPersonID&search=" . $search . "&subpage=Timetable'>Timetable</a></li>" ;
					}
					$_SESSION[$guid]["sidebarExtra"].= "</ul>" ;
					
				}
			}
		}
	}
}
?>