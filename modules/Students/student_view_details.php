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

if (isActionAccessible($guid, $connection2, "/modules/Students/student_view_details.php")==FALSE) {
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
		$search=NULL ;
		if (isset($_GET["search"])) {
			$search=$_GET["search"] ;
		}
		$allStudents="" ;
		if (isset($_GET["allStudents"])) {
			$allStudents=$_GET["allStudents"] ;
		}
		$sort="" ;
		if (isset($_GET["sort"])) {
			$sort=$_GET["sort"] ;
		}
		
		if ($gibbonPersonID==FALSE) {
			print "<div class='error'>" ;
			print _("You have not specified one or more required parameters.") ;
			print "</div>" ;
		}
		else {
			$enableStudentNotes=getSettingByScope($connection2, "Students", "enableStudentNotes") ;
			$skipBrief=FALSE ;
			//Test if View Student Profile_brief and View Student Profile_myChildren are both available and parent has access to this student...if so, skip brief, and go to full. 
			if (isActionAccessible($guid, $connection2, "/modules/Students/student_view_details.php", "View Student Profile_brief") AND isActionAccessible($guid, $connection2, "/modules/Students/student_view_details.php", "View Student Profile_myChildren")) {
				try {
					$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonPersonID1"=>$_GET["gibbonPersonID"], "gibbonPersonID2"=>$_SESSION[$guid]["gibbonPersonID"]); 
					$sql="SELECT * FROM gibbonFamilyChild JOIN gibbonFamily ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonFamilyChild.gibbonPersonID=:gibbonPersonID1 AND gibbonFamilyAdult.gibbonPersonID=:gibbonPersonID2 AND childDataAccess='Y'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { }
				if ($result->rowCount()==1) {
					$skipBrief=TRUE ;
				}
			}
		
			if ($highestAction=="View Student Profile_brief" AND $skipBrief==FALSE) {
				//Proceed!
				try {
					$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonPersonID"=>$gibbonPersonID); 
					$sql="SELECT * FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonPerson.gibbonPersonID=:gibbonPersonID" ;
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
					print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . _(getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/student_view.php'>" . _('View Student Profiles') . "</a> > </div><div class='trailEnd'>" . formatName("", $row["preferredName"], $row["surname"], "Student") . "</div>" ;
					print "</div>" ;
					
					print "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>" ;
						print "<tr>" ;
							print "<td style='width: 33%; vertical-align: top'>" ;
								print "<span style='font-size: 115%; font-weight: bold'>" . _('Year Group') . "</span><br/>" ;
								try {
									$dataDetail=array("gibbonYearGroupID"=>$row["gibbonYearGroupID"]); 
									$sqlDetail="SELECT * FROM gibbonYearGroup WHERE gibbonYearGroupID=:gibbonYearGroupID" ;
									$resultDetail=$connection2->prepare($sqlDetail);
									$resultDetail->execute($dataDetail);
								}
								catch(PDOException $e) { 
									print "<div class='error'>" . $e->getMessage() . "</div>" ; 
								}
								if ($resultDetail->rowCount()==1) {
									$rowDetail=$resultDetail->fetch() ;
									print _($rowDetail["name"]) ;
								}
							print "</td>" ;
							print "<td style='width: 34%; vertical-align: top'>" ;
								print "<span style='font-size: 115%; font-weight: bold'>" . _('Roll Group') . "</span><br/>" ;
								try {
									$dataDetail=array("gibbonRollGroupID"=>$row["gibbonRollGroupID"]); 
									$sqlDetail="SELECT * FROM gibbonRollGroup WHERE gibbonRollGroupID=:gibbonRollGroupID" ;
									$resultDetail=$connection2->prepare($sqlDetail);
									$resultDetail->execute($dataDetail);
								}
								catch(PDOException $e) { 
									print "<div class='error'>" . $e->getMessage() . "</div>" ; 
								}
								if ($resultDetail->rowCount()==1) {
									$rowDetail=$resultDetail->fetch() ;
									print $rowDetail["name"] ;
								}
							print "</td>" ;
							print "<td style='width: 34%; vertical-align: top'>" ;
								print "<span style='font-size: 115%; font-weight: bold'>" . _('House') . "</span><br/>" ;
								try {
									$dataDetail=array("gibbonHouseID"=>$row["gibbonHouseID"]); 
									$sqlDetail="SELECT * FROM gibbonHouse WHERE gibbonHouseID=:gibbonHouseID" ;
									$resultDetail=$connection2->prepare($sqlDetail);
									$resultDetail->execute($dataDetail);
								}
								catch(PDOException $e) { 
									print "<div class='error'>" . $e->getMessage() . "</div>" ; 
								}
								if ($resultDetail->rowCount()==1) {
									$rowDetail=$resultDetail->fetch() ;
									print $rowDetail["name"] ;
								}
							print "</td>" ;
						print "</tr>" ;
						print "<tr>" ;
							print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
								print "<span style='font-size: 115%; font-weight: bold'>" . _('Email') . "</span><br/>" ;
								if ($row["email"]!="") {
									print "<i><a href='mailto:" . $row["email"] . "'>" . $row["email"] . "</a></i>" ;
								}
							print "</td>" ;
							print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
								print "<span style='font-size: 115%; font-weight: bold'>" . _('Website') . "</span><br/>" ;
								if ($row["website"]!="") {
									print "<i><a href='" . $row["website"] . "'>" . $row["website"] . "</a></i>" ;
								}
							print "</td>" ;
							print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
								print "<span style='font-size: 115%; font-weight: bold'>" . _('Student ID') . "</span><br/>" ;
								if ($row["studentID"]!="") {
									print "<i>" . $row["studentID"] . "</a></i>" ;
								}
							print "</td>" ;
						print "</tr>" ;
					print "</table>" ;
						
					$extendedBriefProfile=getSettingByScope( $connection2, "Students", "extendedBriefProfile" ) ;
					if ($extendedBriefProfile=="Y") {
						print "<h3>" ;
							print _("Family Details") ;
						print "</h3>" ;
						
						try {
							$dataFamily=array("gibbonPersonID"=>$gibbonPersonID); 
							$sqlFamily="SELECT * FROM gibbonFamily JOIN gibbonFamilyChild ON (gibbonFamily.gibbonFamilyID=gibbonFamilyChild.gibbonFamilyID) WHERE gibbonPersonID=:gibbonPersonID" ;
							$resultFamily=$connection2->prepare($sqlFamily);
							$resultFamily->execute($dataFamily);
						}
						catch(PDOException $e) { 
							print "<div class='error'>" . $e->getMessage() . "</div>" ; 
						}
						
						if ($resultFamily->rowCount()<1) {
							print "<div class='error'>" ;
								print _("There are no records to display.");
							print "</div>" ;
						}
						else {
							while ($rowFamily=$resultFamily->fetch()) {
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
									print "<h4>" ;
									print _("Adult") . $count ;
									print "</h4>" ;
									print "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>" ;
										print "<tr>" ;
											print "<td style='width: 33%; vertical-align: top'>" ;
												print "<span style='font-size: 115%; font-weight: bold'>" . _('Name') . "</span><br/>" ;
												print formatName($rowMember["title"], $rowMember["preferredName"], $rowMember["surname"], "Parent") ;
											print "</td>" ;
											print "<td style='width: 33%; vertical-align: top'>" ;
												print "<span style='font-size: 115%; font-weight: bold'>" . _('First Language') . "</span><br/>" ;
												print $rowMember["languageFirst"] ;
											print "</td>" ;
											print "<td style='width: 34%; vertical-align: top' colspan=2>" ;
												print "<span style='font-size: 115%; font-weight: bold'>" . _('Second Language') . "</span><br/>" ;
												print $rowMember["languageSecond"] ;
											print "</td>" ;
										print "</tr>" ;
										print "<tr>" ;
											print "<td style='width: 33%; padding-top: 15px; width: 33%; vertical-align: top'>" ;
												print "<span style='font-size: 115%; font-weight: bold'>" . _('Contact By Phone') . "</span><br/>" ;
												if ($rowMember["contactCall"]=="N") {
													print _("Do not contact by phone.") ;
												}
												else if ($rowMember["contactCall"]=="Y" AND ($rowMember["phone1"]!="" OR $rowMember["phone2"]!="" OR $rowMember["phone3"]!="" OR $rowMember["phone4"]!="")) {
													for ($i=1; $i<5; $i++) {
														if ($rowMember["phone" . $i]!="") {
															if ($rowMember["phone" . $i . "Type"]!="") {
																print $rowMember["phone" . $i . "Type"] . ":</i> " ;
															}
															if ($rowMember["phone" . $i . "CountryCode"]!="") {
																print "+" . $rowMember["phone" . $i . "CountryCode"] . " " ;
															}
															print formatPhone($rowMember["phone" . $i]) . "<br/>" ;
														}
													}
												}
											print "</td>" ;
											print "<td style='width: 33%; padding-top: 15px; width: 34%; vertical-align: top' colspan=2>" ;
												print "<span style='font-size: 115%; font-weight: bold'>" . _('Contact By Email') . "</span><br/>" ;
												if ($rowMember["contactEmail"]=="N") {
													print _("Do not contact by email.") ;
												}
												else if ($rowMember["contactEmail"]=="Y" AND ($rowMember["email"]!="" OR $rowMember["emailAlternate"]!="")) {
													if ($rowMember["email"]!="") {
														print "<a href='mailto:" . $rowMember["email"] . "'>" . $rowMember["email"] . "</a><br/>" ;
													}
													print "<br/>" ;
												}
											print "</td>" ;
										print "</tr>" ;
									print "</table>" ;
									$count++ ;
								}	
							}
						}
					}
					//Set sidebar
					$_SESSION[$guid]["sidebarExtra"]=getUserPhoto($guid, $row["image_240"], 240) ;
				}
			}
			else {
				try {
					if ($highestAction=="View Student Profile_myChildren") {
						$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonPersonID1"=>$_GET["gibbonPersonID"], "gibbonPersonID2"=>$_SESSION[$guid]["gibbonPersonID"]); 
						$sql="SELECT * FROM gibbonFamilyChild JOIN gibbonFamily ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonFamilyChild.gibbonPersonID=:gibbonPersonID1 AND gibbonFamilyAdult.gibbonPersonID=:gibbonPersonID2 AND childDataAccess='Y'" ;
					}
					else {
						if ($allStudents!="on") {
							$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonPersonID"=>$gibbonPersonID); 
							$sql="SELECT * FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonPerson.gibbonPersonID=:gibbonPersonID" ;
						}
						else {
							$data=array("gibbonPersonID"=>$gibbonPersonID); 
							$sql="SELECT DISTINCT gibbonPerson.* FROM gibbonPerson LEFT JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) WHERE gibbonPerson.gibbonPersonID=:gibbonPersonID" ;
						}
					}
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
					print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . _(getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/student_view.php&search=$search&allStudents=$allStudents&sort=$sort'>" . _('View Student Profiles') . "</a> > </div><div class='trailEnd'>" . formatName("", $row["preferredName"], $row["surname"], "Student") . "</div>" ;
					print "</div>" ;
					
					$subpage=NULL ;
					if (isset($_GET["subpage"])) {
						$subpage=$_GET["subpage"] ;
					}
					$hook=NULL ;
					if (isset($_GET["hook"])) {
						$hook=$_GET["hook"] ;
					}
					$module=NULL ;
					if (isset($_GET["module"])) {
						$module=$_GET["module"] ;
					}
					$action=NULL ;
					if (isset($_GET["action"])) {
						$action=$_GET["action"] ;
					}
					
					if ($subpage=="" AND ($hook=="" OR $module=="" OR $action=="")) {
						$subpage="Overview" ;
					}
					
					if ($search!="") {
						print "<div class='linkTop'>" ;
							print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Students/student_view.php&search=" . $search . "'>" . _('Back to Search Results') . "</a>" ;
						print "</div>" ;
					}
					
					print "<h2>" ;
						if ($subpage!="") {
							print $subpage ;
						}
						else {
							print $hook ;
						}
					print "</h2>" ;
					
					if ($subpage=="Overview") {
						if (isActionAccessible($guid, $connection2, "/modules/User Admin/user_manage.php")==TRUE) {
							print "<div class='linkTop'>" ;
							print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/User Admin/user_manage_edit.php&gibbonPersonID=$gibbonPersonID'>" . _('Edit') . "<img style='margin: 0 0 -4px 5px' title='" . _('Edit') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/config.png'/></a> " ;
							print "</div>" ;
						}
						
						print "<h4>" ;
							print _("General Information") ;
						print "</h4>" ;
					
						//Medical alert!
						$alert=getHighestMedicalRisk( $gibbonPersonID, $connection2 ) ;
						if ($alert!=FALSE) {
							$highestLevel=$alert[1] ;
							$highestColour=$alert[3] ;
							$highestColourBG=$alert[4] ;
							print "<div class='error' style='background-color: #" . $highestColourBG . "; border: 1px solid #" . $highestColour . "; color: #" . $highestColour . "'>" ;
							print "<b>" . sprintf(_('This student has one or more %1$s risk medical conditions.'), strToLower(_($highestLevel))) . "</b>." ;
							print "</div>" ;
						}
						
						print "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>" ;
							print "<tr>" ;
								print "<td style='width: 33%; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>" . _('Preferred Name') . "</span><br/>" ;
									print formatName("", $row["preferredName"], $row["surname"], "Student") ;
								print "</td>" ;
								print "<td style='width: 34%; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>" . _('Official Name') . "</span><br/>" ;
									print $row["officialName"] ;
								print "</td>" ;
								print "<td style='width: 34%; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>" . _('Name In Characters') . "</span><br/>" ;
									print $row["nameInCharacters"] ;
								print "</td>" ;
							print "</tr>" ;
							print "<tr>" ;
								print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>" . _('Year Group') . "</span><br/>" ;
									if (isset($row["gibbonYearGroupID"])) {
										try {
											$dataDetail=array("gibbonYearGroupID"=>$row["gibbonYearGroupID"]); 
											$sqlDetail="SELECT * FROM gibbonYearGroup WHERE gibbonYearGroupID=:gibbonYearGroupID" ;
											$resultDetail=$connection2->prepare($sqlDetail);
											$resultDetail->execute($dataDetail);
										}
										catch(PDOException $e) { 
											print "<div class='error'>" . $e->getMessage() . "</div>" ; 
										}
										if ($resultDetail->rowCount()==1) {
											$rowDetail=$resultDetail->fetch() ;
											print _($rowDetail["name"]) ;
											$dayTypeOptions=getSettingByScope($connection2, 'User Admin', 'dayTypeOptions') ;
											if ($dayTypeOptions!="") {
												print " (" . $row["dayType"] . ")" ;
											}
											print "</i>" ;
										}
									}
								print "</td>" ;
								print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>" . _('Roll Group') . "</span><br/>" ;
									if (isset($row["gibbonRollGroupID"])) {
										try {
											$dataDetail=array("gibbonRollGroupID"=>$row["gibbonRollGroupID"]); 
											$sqlDetail="SELECT * FROM gibbonRollGroup WHERE gibbonRollGroupID=:gibbonRollGroupID" ;
											$resultDetail=$connection2->prepare($sqlDetail);
											$resultDetail->execute($dataDetail);
										}
										catch(PDOException $e) { 
											print "<div class='error'>" . $e->getMessage() . "</div>" ; 
										}
										if ($resultDetail->rowCount()==1) {
											$rowDetail=$resultDetail->fetch() ;
											if (isActionAccessible($guid, $connection2, "/modules/Roll Groups/rollGroups_details.php")) {
												print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Roll Groups/rollGroups_details.php&gibbonRollGroupID=" . $rowDetail["gibbonRollGroupID"] . "'>" . $rowDetail["name"] . "</a>" ;
											}
											else {
												print $rowDetail["name"] ;
											}
											$primaryTutor=$rowDetail["gibbonPersonIDTutor"] ;
										}
									}
								print "</td>" ;
								print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>" . _('Tutors') . "</span><br/>" ;
									if (isset($rowDetail["gibbonPersonIDTutor"])) {
										try {
											$dataDetail=array("gibbonPersonIDTutor"=>$rowDetail["gibbonPersonIDTutor"], "gibbonPersonIDTutor2"=>$rowDetail["gibbonPersonIDTutor2"], "gibbonPersonIDTutor3"=>$rowDetail["gibbonPersonIDTutor3"]); 
											$sqlDetail="SELECT gibbonPersonID, title, surname, preferredName FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonIDTutor OR gibbonPersonID=:gibbonPersonIDTutor2 OR gibbonPersonID=:gibbonPersonIDTutor3" ;
											$resultDetail=$connection2->prepare($sqlDetail);
											$resultDetail->execute($dataDetail);
										}
										catch(PDOException $e) { 
											print "<div class='error'>" . $e->getMessage() . "</div>" ; 
										}
										while ($rowDetail=$resultDetail->fetch()) {
											if (isActionAccessible($guid, $connection2, "/modules/Staff/staff_view_details.php")) {
												print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Staff/staff_view_details.php&gibbonPersonID=" . $rowDetail["gibbonPersonID"] . "'>" . formatName("", $rowDetail["preferredName"], $rowDetail["surname"], "Staff", false, true) . "</a>" ;
											}
											else {
												print formatName($rowDetail["title"], $rowDetail["preferredName"], $rowDetail["surname"], "Staff") ;
											}
											if ($rowDetail["gibbonPersonID"]==$primaryTutor AND $resultDetail->rowCount()>1) {
												print " (" . _('Main Tutor') . ")" ;
											}
											print "<br/>" ;
										}
									}
								print "</td>" ;
							print "</tr>" ;
							print "<tr>" ;
								print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>" . _('Username') . "</span><br/>" ;
									print $row["username"] ;
								print "</td>" ;
								print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>" . _('Age') . "</span><br/>" ;
									if (is_null($row["dob"])==FALSE AND $row["dob"]!="0000-00-00") {
										print getAge(dateConvertToTimestamp($row["dob"])) ;
									}
								print "</td>" ;
								print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>" . _('House') . "</span><br/>" ;
									try {
										$dataDetail=array("gibbonHouseID"=>$row["gibbonHouseID"]); 
										$sqlDetail="SELECT * FROM gibbonHouse WHERE gibbonHouseID=:gibbonHouseID" ;
										$resultDetail=$connection2->prepare($sqlDetail);
										$resultDetail->execute($dataDetail);
									}
									catch(PDOException $e) { 
										print "<div class='error'>" . $e->getMessage() . "</div>" ; 
									}
									if ($resultDetail->rowCount()==1) {
										$rowDetail=$resultDetail->fetch() ;
										print $rowDetail["name"] ;
									}
								print "</td>" ;
							print "</tr>" ;
							print "<tr>" ;
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
								print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;

								print "</td>" ;
							print "</tr>" ;
							print "<tr>" ;
								print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>" . _('Locker Number') . "</span><br/>" ;
									if ($row["lockerNumber"]!="") {
										print $row["lockerNumber"] ;
									}
								print "</td>" ;
								print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>" . _('Student ID') . "</span><br/>" ;
									if ($row["studentID"]!="") {
										print $row["studentID"] ;
									}
								print "</td>" ;
								print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
									
								print "</td>" ;
							print "</tr>" ;
							$privacySetting=getSettingByScope( $connection2, "User Admin", "privacy" ) ;
							if ($privacySetting=="Y") {
								print "<tr>" ;
									print "<td style='width: 33%; padding-top: 15px; vertical-align: top' colspan=3>" ;
										print "<span style='font-size: 115%; font-weight: bold'>" . _('Image Privacy') . "</span><br/>" ;
										if ($row["privacy"]!="") {
											print "<span style='color: #cc0000; background-color: #F6CECB'>" ;
												print _("Privacy required:") . " " . $row["privacy"] ;
											print "</span>" ;
										}
										else {
											print "<span style='color: #390; background-color: #D4F6DC;'>" ;
												print _("Privacy not required or not set.") ;
											print "</span>" ;
										}
									
									print "</td>" ;
								print "</tr>" ;
							}
							$studentAgreementOptions=getSettingByScope( $connection2, "School Admin", "studentAgreementOptions" ) ;
							if ($studentAgreementOptions!="") {
								print "<tr>" ;
									print "<td style='width: 33%; padding-top: 15px; vertical-align: top' colspan=3>" ;
										print "<span style='font-size: 115%; font-weight: bold'>" . _('Student Agreements') . "</span><br/>" ;
										print _("Agreements Signed:") . " " . $row["studentAgreements"] ;
									print "</td>" ;
								print "</tr>" ;
							}
						print "</table>" ;
						
						//Get list of teachers
						print "<h4>" ;
							print _("Student's Teachers") ;
						print "</h4>" ;
						try {
							$dataDetail=array("gibbonPersonID"=>$gibbonPersonID); 
							$sqlDetail="SELECT DISTINCT teacher.surname, teacher.preferredName, teacher.email FROM gibbonPerson AS teacher JOIN gibbonCourseClassPerson AS teacherClass ON (teacherClass.gibbonPersonID=teacher.gibbonPersonID)  JOIN gibbonCourseClassPerson AS studentClass ON (studentClass.gibbonCourseClassID=teacherClass.gibbonCourseClassID) JOIN gibbonPerson AS student ON (studentClass.gibbonPersonID=student.gibbonPersonID) JOIN gibbonCourseClass ON (studentClass.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE teacher.status='Full' AND teacherClass.role='Teacher' AND studentClass.role='Student' AND student.gibbonPersonID=:gibbonPersonID AND gibbonCourse.gibbonSchoolYearID=(SELECT gibbonSchoolYearID FROM gibbonSchoolYear WHERE status='Current') ORDER BY teacher.preferredName, teacher.surname, teacher.email ;" ;
							$resultDetail=$connection2->prepare($sqlDetail);
							$resultDetail->execute($dataDetail);
						}
						catch(PDOException $e) { 
							print "<div class='error'>" . $e->getMessage() . "</div>" ; 
						}
						if ($resultDetail->rowCount()<1) {
							print "<div class='error'>" ;
								print _("There are no records to display.") ;
							print "</div>" ;
						}
						else {
							print "<ul>" ;
								while ($rowDetail=$resultDetail->fetch()) {
									print "<li>" . htmlPrep(formatName("", $rowDetail["preferredName"], $rowDetail["surname"], "Student", FALSE) . " <" . $rowDetail["email"] . ">") . "</li>" ;
								}
							print "</ul>" ;
						}
						
						//Show timetable 
						print "<a name='timetable'></a>" ;
						print "<h4>" ;
							print _("Timetable") ;
						print "</h4>" ;
						if (isActionAccessible($guid, $connection2, "/modules/Timetable/tt_view.php")==TRUE) {
							if (isActionAccessible($guid, $connection2, "/modules/Timetable Admin/courseEnrolment_manage_byPerson_edit.php")==TRUE) {
								$role=getRoleCategory($row["gibbonRoleIDPrimary"], $connection2) ;
								if ($role=="Student" OR $role=="Staff") {
									print "<div class='linkTop'>" ;
									print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Timetable Admin/courseEnrolment_manage_byPerson_edit.php&gibbonPersonID=$gibbonPersonID&gibbonSchoolYearID=" . $_SESSION[$guid]["gibbonSchoolYearID"] . "&type=$role'>" . _('Edit') . "<img style='margin: 0 0 -4px 5px' title='" . _('Edit') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/config.png'/></a> " ;
									print "</div>" ;
								}
							}
					
							include "./modules/Timetable/moduleFunctions.php" ;
							$ttDate=NULL ;
							if (isset($_POST["ttDate"])) {
								$ttDate=dateConvertToTimestamp(dateConvert($guid, $_POST["ttDate"]));
							}
							$tt=renderTT($guid, $connection2,$gibbonPersonID, "", FALSE, $ttDate, "/modules/Students/student_view_details.php", "&gibbonPersonID=$gibbonPersonID&search=$search&allStudents=$allStudents#timetable") ;
							if ($tt!=FALSE) {
								print $tt ;
							}
							else {
								print "<div class='error'>" ;
									print _("There are no records to display.") ;
								print "</div>" ;
							}
						}
					}
					else if ($subpage=="Personal") {
						if (isActionAccessible($guid, $connection2, "/modules/User Admin/user_manage.php")==TRUE) {
							print "<div class='linkTop'>" ;
							print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/User Admin/user_manage_edit.php&gibbonPersonID=$gibbonPersonID'>" . _('Edit') . "<img style='margin: 0 0 -4px 5px' title='" . _('Edit') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/config.png'/></a> " ;
							print "</div>" ;
						}
						
						print "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>" ;
							print "<tr>" ;
								print "<td style='width: 33%; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>" . _('Surname') . "</span><br/>" ;
									print $row["surname"] ;
								print "</td>" ;
								print "<td style='width: 33%; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>" . _('First Name') . "</span><br/>" ;
									print $row["firstName"] ;
								print "</td>" ;
								print "<td style='width: 34%; vertical-align: top'>" ;
									
								print "</td>" ;
							print "</tr>" ;	
							print "<tr>" ;
								print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>" . _('Preferred Name') . "</span><br/>" ;
									print formatName("", $row["preferredName"], $row["surname"], "Student") ;
								print "</td>" ;
								print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>" . _('Official Name') . "</span><br/>" ;
									print $row["officialName"] ;
								print "</td>" ;
								print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>" . _('Name In Characters') . "</span><br/>" ;
									print $row["nameInCharacters"] ;
								print "</td>" ;
							print "</tr>" ;
							print "<tr>" ;
								print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>" . _('Gender') . "</span><br/>" ;
									print $row["gender"] ;
								print "</td>" ;
								print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>" . _('Date of Birth') . "</span><br/>" ;
									if (is_null($row["dob"])==FALSE AND $row["dob"]!="0000-00-00") {
										print dateConvertBack($guid, $row["dob"]) ;
									}
								print "</td>" ;
								print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>" . _('Age') . "</span><br/>" ;
									if (is_null($row["dob"])==FALSE AND $row["dob"]!="0000-00-00") {
										print getAge(dateConvertToTimestamp($row["dob"])) ;
									}
								print "</td>" ;
							print "</tr>" ;
						print "</table>" ;
							
						print "<h4>" ;
						print _("Contacts") ;
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
													print $row["phone" . $i . "Type"] . ":</i> " ;
												}
												if ($row["phone" . $i . "CountryCode"]!="") {
													print "+" . $row["phone" . $i . "CountryCode"] . " " ;
												}
												print formatPhone($row["phone" . $i]) . "<br/>" ;
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
								print "<td style='width: 33%; padding-top: 15px; vertical-align: top' colspan=2>" ;
									print "<span style='font-size: 115%; font-weight: bold'>" . _('Website') . "</span><br/>" ;
									if ($row["website"]!="") {
										print "<i><a href='" . $row["website"] . "'>" . $row["website"] . "</a></i>" ;
									}
								print "</td>" ;
							print "</tr>" ;
							if ($row["address1"]!="") {
								print "<tr>" ;
									print "<td style='width: 33%; padding-top: 15px; vertical-align: top' colspan=4>" ;
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
						print _("School Information") ;
						print "</h4>" ;
						
						print "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>" ;
							print "<tr>" ;
								print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>" . _('Last School') . "</span><br/>" ;
									print $row["lastSchool"] ;
								print "</td>" ;
								print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>" . _('Start Date') . "</span><br/>" ;
									print dateConvertBack($guid, $row["dateStart"]) ;
								print "</td>" ;
								print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>" . _('Class Of') . "</span><br/>" ;
									if ($row["gibbonSchoolYearIDClassOf"]=="") {
										print "<i>" . _('NA') . "</i>" ;
									}
									else {
										try {
											$dataDetail=array("gibbonSchoolYearIDClassOf"=>$row["gibbonSchoolYearIDClassOf"]); 
											$sqlDetail="SELECT * FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearIDClassOf" ;
											$resultDetail=$connection2->prepare($sqlDetail);
											$resultDetail->execute($dataDetail);
										}
										catch(PDOException $e) { 
											print "<div class='error'>" . $e->getMessage() . "</div>" ; 
										}
										if ($resultDetail->rowCount()==1) {
											$rowDetail=$resultDetail->fetch() ;
											print $rowDetail["name"] ;
										}
									}
									
								print "</td>" ;
							print "</tr>" ;
							print "<tr>" ;
								print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>" . _('Next School') . "</span><br/>" ;
									print $row["nextSchool"] ;
								print "</td>" ;
								print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>" . _('End Date') . "</span><br/>" ;
									print dateConvertBack($guid, $row["dateEnd"]) ;
								print "</td>" ;
								print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>" . _('Departure Reason') . "</span><br/>" ;
									print $row["departureReason"] ;
								print "</td>" ;
							print "</tr>" ;
							$dayTypeOptions=getSettingByScope($connection2, 'User Admin', 'dayTypeOptions') ;
							if ($dayTypeOptions!="") {
								print "<tr>" ;
									print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
										print "<span style='font-size: 115%; font-weight: bold'>" . _('Day Type') . "</span><br/>" ;
										print $row["dayType"] ;
									print "</td>" ;
									print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
										
									print "</td>" ;
									print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
										
									print "</td>" ;
								print "</tr>" ;
							}
						print "</table>" ;
						
						print "<h4>" ;
						print _("Background") ;
						print "</h4>" ;
						
						print "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>" ;
							print "<tr>" ;
								print "<td width: 33%; style='vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>" . _('Country of Birth') . "</span><br/>" ;
									print $row["countryOfBirth"] ;
								print "</td>" ;
								print "<td style='width: 33%; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>" . _('Ethnicity') . "</span><br/>" ;
									print $row["ethnicity"] ;
								print "</td>" ;
								print "<td style='width: 34%; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>" . _('Religion') . "</span><br/>" ;
									print $row["religion"] ;
								print "</td>" ;
							print "</tr>" ;
							print "<tr>" ;
								print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>" . _('Citizenship 1') . "</span><br/>" ;
									print $row["citizenship1"] ;
									if ($row["citizenship1Passport"]!="") {
										print "<br/>" ;
										print $row["citizenship1Passport"] ;
									}
								print "</td>" ;
								print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>" . _('Citizenship 2') . "</span><br/>" ;
									print $row["citizenship2"] ;
									if ($row["citizenship2Passport"]!="") {
										print "<br/>" ;
										print $row["citizenship2Passport"] ;
									}
								print "</td>" ;
								print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
									if ($_SESSION[$guid]["country"]=="") {
										print "<span style='font-size: 115%; font-weight: bold'>" . _('National ID Card') . "</span><br/>" ;
									}
									else {
										print "<span style='font-size: 115%; font-weight: bold'>" . $_SESSION[$guid]["country"] . " " . _('ID Card') . "</span><br/>" ;
									}
									print $row["nationalIDCardNumber"] ;
								print "</td>" ;
							print "</tr>" ;
							print "<tr>" ;
								print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>" . _('First Language') . "</span><br/>" ;
									print $row["languageFirst"] ;
								print "</td>" ;
								print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>" . _('Second Language') . "</span><br/>" ;
									print $row["languageSecond"] ;
								print "</td>" ;
								print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>" . _('Third Language') . "</span><br/>" ;
									print $row["languageThird"] ;
								print "</td>" ;
							print "</tr>" ;
							print "<tr>" ;
								print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
									if ($_SESSION[$guid]["country"]=="") {
										print "<span style='font-size: 115%; font-weight: bold'>" . _('Residency/Visa Type') . "</span><br/>" ;
									}
									else {
										print "<span style='font-size: 115%; font-weight: bold'>" . $_SESSION[$guid]["country"] . " " . _('Residency/Visa Type') . "</span><br/>" ;
									}
									print $row["residencyStatus"] ;
								print "</td>" ;
								print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
									if ($_SESSION[$guid]["country"]=="") {
										print "<span style='font-size: 115%; font-weight: bold'>" . _('Visa Expiry Date') . "</span><br/>" ;
									}
									else {
										print "<span style='font-size: 115%; font-weight: bold'>" . $_SESSION[$guid]["country"] . " " . _('Visa Expiry Date') . "</span><br/>" ;
									}
									if ($row["visaExpiryDate"]!="") {
										print dateConvertBack($guid, $row["visaExpiryDate"]) ;
									}
								print "</td>" ;
								print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
									
								print "</td>" ;
							print "</tr>" ;
						print "</table>" ;			
						
						print "<h4>" ;
						print "School Data" ;
						print "</h4>" ;
						print "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>" ;
							print "<tr>" ;
								print "<td style='width: 33%; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>" . _('Year Group') . "</span><br/>" ;
									if (isset($row["gibbonYearGroupID"])) {
										try {
											$dataDetail=array("gibbonYearGroupID"=>$row["gibbonYearGroupID"]); 
											$sqlDetail="SELECT * FROM gibbonYearGroup WHERE gibbonYearGroupID=:gibbonYearGroupID" ;
											$resultDetail=$connection2->prepare($sqlDetail);
											$resultDetail->execute($dataDetail);
										}
										catch(PDOException $e) { 
											print "<div class='error'>" . $e->getMessage() . "</div>" ; 
										}
										if ($resultDetail->rowCount()==1) {
											$rowDetail=$resultDetail->fetch() ;
											print _($rowDetail["name"]) ;
										}
									}
								print "</td>" ;
								print "<td style='width: 33%; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>" . _('Roll Group') . "</span><br/>" ;
									if (isset($row["gibbonRollGroupID"])) {
										$sqlDetail="SELECT * FROM gibbonRollGroup WHERE gibbonRollGroupID='" . $row["gibbonRollGroupID"] . "'" ;
										try {
											$dataDetail=array("gibbonRollGroupID"=>$row["gibbonRollGroupID"]); 
											$sqlDetail="SELECT * FROM gibbonRollGroup WHERE gibbonRollGroupID=:gibbonRollGroupID" ;
											$resultDetail=$connection2->prepare($sqlDetail);
											$resultDetail->execute($dataDetail);
										}
										catch(PDOException $e) { 
											print "<div class='error'>" . $e->getMessage() . "</div>" ; 
										}
										if ($resultDetail->rowCount()==1) {
											$rowDetail=$resultDetail->fetch() ;
											if (isActionAccessible($guid, $connection2, "/modules/Roll Groups/rollGroups_details.php")) {
												print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Roll Groups/rollGroups_details.php&gibbonRollGroupID=" . $rowDetail["gibbonRollGroupID"] . "'>" . $rowDetail["name"] . "</a>" ;
											}
											else {
												print $rowDetail["name"] ;
											}
											$primaryTutor=$rowDetail["gibbonPersonIDTutor"] ;
										}
									}
								print "</td>" ;
								print "<td style='width: 34%; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>" . _('Tutors') . "</span><br/>" ;
									if (isset($rowDetail["gibbonPersonIDTutor"])) {
										try {
											$dataDetail=array("gibbonPersonIDTutor"=>$rowDetail["gibbonPersonIDTutor"], "gibbonPersonIDTutor2"=>$rowDetail["gibbonPersonIDTutor2"], "gibbonPersonIDTutor3"=>$rowDetail["gibbonPersonIDTutor3"]); 
											$sqlDetail="SELECT gibbonPersonID, title, surname, preferredName FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonIDTutor OR gibbonPersonID=:gibbonPersonIDTutor2 OR gibbonPersonID=:gibbonPersonIDTutor3" ;
											$resultDetail=$connection2->prepare($sqlDetail);
											$resultDetail->execute($dataDetail);
										}
										catch(PDOException $e) { 
											print "<div class='error'>" . $e->getMessage() . "</div>" ; 
										}
										while ($rowDetail=$resultDetail->fetch()) {
											if (isActionAccessible($guid, $connection2, "/modules/Staff/staff_view_details.php")) {
												print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Staff/staff_view_details.php&gibbonPersonID=" . $rowDetail["gibbonPersonID"] . "'>" . formatName("", $rowDetail["preferredName"], $rowDetail["surname"], "Staff", false, true) . "</a>" ;
											}
											else {
												print formatName($rowDetail["title"], $rowDetail["preferredName"], $rowDetail["surname"], "Staff") ;
											}
											if ($rowDetail["gibbonPersonID"]==$primaryTutor AND $resultDetail->rowCount()>1) {
												print " (" . _('Main Tutor') . ")" ;
											}
											print "<br/>" ;
										}
									}
								print "</td>" ;
							print "<tr>" ;
								print "<td style='padding-top: 15px ; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>" . _('House') . "</span><br/>" ;
									try {
										$dataDetail=array("gibbonHouseID"=>$row["gibbonHouseID"]); 
										$sqlDetail="SELECT * FROM gibbonHouse WHERE gibbonHouseID=:gibbonHouseID" ;
										$resultDetail=$connection2->prepare($sqlDetail);
										$resultDetail->execute($dataDetail);
									}
									catch(PDOException $e) { 
										print "<div class='error'>" . $e->getMessage() . "</div>" ; 
									}
									if ($resultDetail->rowCount()==1) {
										$rowDetail=$resultDetail->fetch() ;
										print $rowDetail["name"] ;
									}
								print "</td>" ;
								print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>" . _('Student ID') . "</span><br/>" ;
									print $row["studentID"] ;
								print "</td>" ;
								print "<td style='width: 34%; vertical-align: top'>" ;
									
								print "</td>" ;
							print "</tr>" ;
						print "</table>" ;
						
						print "<h4>" ;
						print _("System Data") ;
						print "</h4>" ;
						
						print "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>" ;
							print "<tr>" ;
								print "<td width: 33%; style='vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>" . _('Username') . "</span><br/>" ;
									print $row["username"] ;
								print "</td>" ;
								print "<td style='width: 33%; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>" . _('Can Login?') . "</span><br/>" ;
									print $row["canLogin"] ;
								print "</td>" ;
								print "<td style='width: 34%; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>" . _('Last IP Address') . "</span><br/>" ;
									print $row["lastIPAddress"] ;
								print "</td>" ;
							print "</tr>" ;
						print "</table>" ;
						
						print "<h4>" ;
						print _("Miscellaneous") ;
						print "</h4>" ;
						
						print "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>" ;
							print "<tr>" ;
								print "<td style='width: 33%; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>" . _('Transport') . "</span><br/>" ;
									print $row["transport"] ;
									if ($row["transportNotes"]!="") {
										print "<br/>" ;
										print $row["transportNotes"] ; 
									}
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
							
							$privacySetting=getSettingByScope( $connection2, "User Admin", "privacy" ) ;
							if ($privacySetting=="Y") {
								print "<tr>" ;
									print "<td style='width: 33%; padding-top: 15px; vertical-align: top' colspan=3>" ;
										print "<span style='font-size: 115%; font-weight: bold'>" . _('Image Privacy') . "</span><br/>" ;
										if ($row["privacy"]!="") {
											print "<span style='color: #cc0000; background-color: #F6CECB'>" ;
												print _("Privacy required:") . " " . $row["privacy"] ;
											print "</span>" ;
										}
										else {
											print "<span style='color: #390; background-color: #D4F6DC;'>" ;
												print _("Privacy not required or not set.") ;
											print "</span>" ;
										}
									
									print "</td>" ;
								print "</tr>" ;
							}
							$studentAgreementOptions=getSettingByScope( $connection2, "School Admin", "studentAgreementOptions" ) ;
							if ($studentAgreementOptions!="") {
							
								print "<tr>" ;
									print "<td style='width: 33%; padding-top: 15px; vertical-align: top' colspan=3>" ;
										print "<span style='font-size: 115%; font-weight: bold'>" . _('Student Agreements') . "</span><br/>" ;
										print _("Agreements Signed:") . " " . $row["studentAgreements"] ;
									print "</td>" ;
								print "</tr>" ;
							}
						print "</table>" ;
					}
					else if ($subpage=="Family") {
						try {
							$dataFamily=array("gibbonPersonID"=>$gibbonPersonID); 
							$sqlFamily="SELECT * FROM gibbonFamily JOIN gibbonFamilyChild ON (gibbonFamily.gibbonFamilyID=gibbonFamilyChild.gibbonFamilyID) WHERE gibbonPersonID=:gibbonPersonID" ;
							$resultFamily=$connection2->prepare($sqlFamily);
							$resultFamily->execute($dataFamily);
						}
						catch(PDOException $e) { 
							print "<div class='error'>" . $e->getMessage() . "</div>" ; 
						}
						
						if ($resultFamily->rowCount()<1) {
							print "<div class='error'>" ;
								print _("There are no records to display.");
							print "</div>" ;
						}
						else {
							while ($rowFamily=$resultFamily->fetch()) {
								$count=1 ;
								
								if (isActionAccessible($guid, $connection2, "/modules/User Admin/family_manage.php")==TRUE) {
									print "<div class='linkTop'>" ;
									print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/User Admin/family_manage_edit.php&gibbonFamilyID=" . $rowFamily["gibbonFamilyID"] . "'>" . _('Edit') . "<img style='margin: 0 0 -4px 5px' title='" . _('Edit') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/config.png'/></a> " ;
									print "</div>" ;
								}
								
								//Print family information
								print "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>" ;
									print "<tr>" ;
										print "<td style='width: 33%; vertical-align: top'>" ;
											print "<span style='font-size: 115%; font-weight: bold'>" . _('Family Name') . "</span><br/>" ;
											print $rowFamily["name"] ;
										print "</td>" ;
										print "<td style='width: 33%; vertical-align: top'>" ;
											print "<span style='font-size: 115%; font-weight: bold'>" . _('Family Status') . "</span><br/>" ;
											print $rowFamily["status"] ;
										print "</td>" ;
										print "<td style='width: 34%; vertical-align: top' colspan=2>" ;
											print "<span style='font-size: 115%; font-weight: bold'>" . _('Home Language') . "</span><br/>" ;
											print $rowFamily["languageHome"] ;
										print "</td>" ;
									print "</tr>" ;
									print "<tr>" ;
											print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
												print "<span style='font-size: 115%; font-weight: bold'>" . _('Address Name') . "</span><br/>" ;
												print $rowFamily["nameAddress"] ;
											print "</td>" ;
											print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
											print "</td>" ;
											print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
											print "</td>" ;
										print "</tr>" ;
										
									print "<tr>" ;
											print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
												print "<span style='font-size: 115%; font-weight: bold'>" . _('Home Address') . "</span><br/>" ;
												print $rowFamily["homeAddress"] ;
											print "</td>" ;
											print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
												print "<span style='font-size: 115%; font-weight: bold'>" . _('Home Address (District)') . "</span><br/>" ;
												print $rowFamily["homeAddressDistrict"] ;
											print "</td>" ;
											print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
												print "<span style='font-size: 115%; font-weight: bold'>" . _('Home Address (Country)') . "</span><br/>" ;
												print $rowFamily["homeAddressCountry"] ;
											print "</td>" ;
										print "</tr>" ;
								print "</table>" ;
								
								
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
									print "<h4>" ;
									print _("Adult") . " " . $count ;
									print "</h4>" ;
									print "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>" ;
										print "<tr>" ;
											print "<td style='width: 33%; vertical-align: top' rowspan=2>" ;
												print getUserPhoto($guid, $rowMember["image_75"], 75) ;
											print "</td>" ;
											print "<td style='width: 33%; vertical-align: top'>" ;
												print "<span style='font-size: 115%; font-weight: bold'>" . _('Name') . "</span><br/>" ;
												print formatName($rowMember["title"], $rowMember["preferredName"], $rowMember["surname"], "Parent") . "<br/>" ;
												print "<div style='font-size: 85%; font-style: italic'>" ;
													try {
														$dataRelationship=array("gibbonPersonID1"=>$rowMember["gibbonPersonID"], "gibbonPersonID2"=>$gibbonPersonID, "gibbonFamilyID"=>$rowFamily["gibbonFamilyID"]); 
														$sqlRelationship="SELECT * FROM gibbonFamilyRelationship WHERE gibbonPersonID1=:gibbonPersonID1 AND gibbonPersonID2=:gibbonPersonID2 AND gibbonFamilyID=:gibbonFamilyID" ;
														$resultRelationship=$connection2->prepare($sqlRelationship);
														$resultRelationship->execute($dataRelationship);
													}
													catch(PDOException $e) { 
														print "<div class='error'>" . $e->getMessage() . "</div>" ; 
													}
													if ($resultRelationship->rowCount()==1) {
														$rowRelationship=$resultRelationship->fetch() ;
														print $rowRelationship["relationship"] ;
													}
													else {
														print "<i>" . _('Relationship Unknown') . "</i>" ;
													}
												print "</div>" ;
											print "</td>" ;
											print "<td style='width: 34%; vertical-align: top' colspan=2>" ;
												print "<span style='font-size: 115%; font-weight: bold'>" . _('Contact Priority') . "</span><br/>" ;
												print $rowMember["contactPriority"] ;
											print "</td>" ;
										print "</tr>" ;
										print "<tr>" ;
											print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
												print "<span style='font-size: 115%; font-weight: bold'>" . _('First Language') . "</span><br/>" ;
												print $rowMember["languageFirst"] ;
											print "</td>" ;
											print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
												print "<span style='font-size: 115%; font-weight: bold'>" . _('Second Language') . "</span><br/>" ;
												print $rowMember["languageSecond"] ;
											print "</td>" ;
										print "</tr>" ;
										print "<tr>" ;
											print "<td style='width: 33%; padding-top: 15px; width: 33%; vertical-align: top'>" ;
												print "<span style='font-size: 115%; font-weight: bold'>" . _('Contact By Phone') . "</span><br/>" ;
												if ($rowMember["contactCall"]=="N") {
													print _("Do not contact by phone.") ;
												}
												else if ($rowMember["contactCall"]=="Y" AND ($rowMember["phone1"]!="" OR $rowMember["phone2"]!="" OR $rowMember["phone3"]!="" OR $rowMember["phone4"]!="")) {
													for ($i=1; $i<5; $i++) {
														if ($rowMember["phone" . $i]!="") {
															if ($rowMember["phone" . $i . "Type"]!="") {
																print $rowMember["phone" . $i . "Type"] . ":</i> " ;
															}
															if ($rowMember["phone" . $i . "CountryCode"]!="") {
																print "+" . $rowMember["phone" . $i . "CountryCode"] . " " ;
															}
															print formatPhone($rowMember["phone" . $i]) . "<br/>" ;
														}
													}
												}
											print "</td>" ;
											print "<td style='width: 33%; padding-top: 15px; width: 33%; vertical-align: top'>" ;
												print "<span style='font-size: 115%; font-weight: bold'>" . _('Contact By SMS') . "</span><br/>" ;
												if ($rowMember["contactSMS"]=="N") {
													print _("Do not contact by SMS.") ;
												}
												else if ($rowMember["contactSMS"]=="Y" AND ($rowMember["phone1"]!="" OR $rowMember["phone2"]!="" OR $rowMember["phone3"]!="" OR $rowMember["phone4"]!="")) {
													for ($i=1; $i<5; $i++) {
														if ($rowMember["phone" . $i]!="" AND $rowMember["phone" . $i . "Type"]=="Mobile") {
															if ($rowMember["phone" . $i . "Type"]!="") {
																print $rowMember["phone" . $i . "Type"] . ":</i> " ;
															}
															if ($rowMember["phone" . $i . "CountryCode"]!="") {
																print "+" . $rowMember["phone" . $i . "CountryCode"] . " " ;
															}
															print formatPhone($rowMember["phone" . $i]) . "<br/>" ;
														}
													}
												}
											print "</td>" ;
											print "<td style='width: 33%; padding-top: 15px; width: 34%; vertical-align: top' colspan=2>" ;
												print "<span style='font-size: 115%; font-weight: bold'>" . _('Contact By Email') . "</span><br/>" ;
												if ($rowMember["contactEmail"]=="N") {
													print _("Do not contact by email.") ;
												}
												else if ($rowMember["contactEmail"]=="Y" AND ($rowMember["email"]!="" OR $rowMember["emailAlternate"]!="")) {
													if ($rowMember["email"]!="") {
														print _("Email") . ": <a href='mailto:" . $rowMember["email"] . "'>" . $rowMember["email"] . "</a><br/>" ;
													}
													if ($rowMember["emailAlternate"]!="") {
														print _("Email") . " 2: <a href='mailto:" . $rowMember["emailAlternate"] . "'>" . $rowMember["emailAlternate"] . "</a><br/>" ;
													}
													print "<br/>" ;
												}
											print "</td>" ;
										print "</tr>" ;
										print "<tr>" ;
											print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
												print "<span style='font-size: 115%; font-weight: bold'>" . _('Profession') . "</span><br/>" ;
												print $rowMember["profession"] ;
											print "</td>" ;
											print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
												print "<span style='font-size: 115%; font-weight: bold'>" . _('Employer') . "</span><br/>" ;
												print $rowMember["employer"] ;
											print "</td>" ;
											print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
												print "<span style='font-size: 115%; font-weight: bold'>" . _('Job Title') . "</span><br/>" ;
												print $rowMember["jobTitle"] ;
											print "</td>" ;
										print "</tr>" ;
										
										print "<tr>" ;
											print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
												print "<span style='font-size: 115%; font-weight: bold'>" . _('Vehicle Registration') . "</span><br/>" ;
												print $rowMember["vehicleRegistration"] ;
											print "</td>" ;
											print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
												
											print "</td>" ;
											print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
												
											print "</td>" ;
										print "</tr>" ;
										
										if ($rowMember["comment"]!="") {
											print "<tr>" ;
												print "<td style='width: 33%; vertical-align: top' colspan=3>" ;
													print "<span style='font-size: 115%; font-weight: bold'>" . _('Comment') . "</span><br/>" ;
													print $rowMember["comment"] ;
												print "</td>" ;
											print "</tr>" ;
										}
									print "</table>" ;
									$count++ ;
								}	
									
								//Get siblings
								try {
									$dataMember=array("gibbonFamilyID"=>$rowFamily["gibbonFamilyID"], "gibbonPersonID"=>$gibbonPersonID); 
									$sqlMember="SELECT * FROM gibbonFamilyChild JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) WHERE gibbonFamilyID=:gibbonFamilyID AND NOT gibbonPerson.gibbonPersonID=:gibbonPersonID ORDER BY surname, preferredName" ;
									$resultMember=$connection2->prepare($sqlMember);
									$resultMember->execute($dataMember);
								}
								catch(PDOException $e) { 
									print "<div class='error'>" . $e->getMessage() . "</div>" ; 
								}
								
								if ($resultMember->rowCount()>0) {
									print "<h4>" ;
									print _("Siblings") ;
									print "</h4>" ;
								
									print "<table class='smallIntBorder' cellspacing='0' style='width:100%'>" ;
										$count=0 ;
										$columns=3 ;
	
										while ($rowMember=$resultMember->fetch()) {
											if ($count%$columns==0) {
												print "<tr>" ;
											}
											print "<td style='width:30%; text-align: left; vertical-align: top'>" ;
												//User photo
												print getUserPhoto($guid, $rowMember["image_75"], 75) ;	
												print "<div style='padding-top: 5px'><b>" ;
												if ($rowMember["status"]=="Full") {
													print "<a href='index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=" . $rowMember["gibbonPersonID"] . "'>" . formatName("", $rowMember["preferredName"], $rowMember["surname"], "Student") . "</a><br/>" ;
												}
												else {
													print formatName("", $rowMember["preferredName"], $rowMember["surname"], "Student") . "<br/>" ;
												}
												print "<span style='font-weight: normal; font-style: italic'>" . _('Status') . ": " . $rowMember["status"] . "</span>" ;
												print "</div>" ;
											print "</td>" ;
		
											if ($count%$columns==($columns-1)) {
												print "</tr>" ;
											}
											$count++ ;
										}
	
										for ($i=0;$i<$columns-($count%$columns);$i++) {
											print "<td></td>" ;
										}
	
										if ($count%$columns!=0) {
											print "</tr>" ;
										}
	
										print "</table>" ;	
								}
							}
						}
					}
					else if ($subpage=="Emergency Contacts") {
						if (isActionAccessible($guid, $connection2, "/modules/User Admin/user_manage.php")==TRUE) {
							print "<div class='linkTop'>" ;
							print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/User Admin/user_manage_edit.php&gibbonPersonID=$gibbonPersonID'>" . _('Edit') . "<img style='margin: 0 0 -4px 5px' title='" . _('Edit') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/config.png'/></a> " ;
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
								print _("There are no records to display.");
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
											try {
												$dataRelationship=array("gibbonPersonID1"=>$rowMember["gibbonPersonID"], "gibbonPersonID2"=>$gibbonPersonID, "gibbonFamilyID"=>$rowFamily["gibbonFamilyID"]); 
												$sqlRelationship="SELECT * FROM gibbonFamilyRelationship WHERE gibbonPersonID1=:gibbonPersonID1 AND gibbonPersonID2=:gibbonPersonID2 AND gibbonFamilyID=:gibbonFamilyID" ;
												$resultRelationship=$connection2->prepare($sqlRelationship);
												$resultRelationship->execute($dataRelationship);
											}
											catch(PDOException $e) { 
												print "<div class='error'>" . $e->getMessage() . "</div>" ; 
											}
											if ($resultRelationship->rowCount()==1) {
												$rowRelationship=$resultRelationship->fetch() ;
												print $rowRelationship["relationship"] ;
											}
											else {
												print "<i>" . _('Unknown') . "</i>" ;
											}
											
										print "</td>" ;
										print "<td style='width: 34%; vertical-align: top'>" ;
											print "<span style='font-size: 115%; font-weight: bold'>" . _('Contact By Phone') . "</span><br/>" ;
											for ($i=1; $i<5; $i++) {
												if ($rowMember["phone" . $i]!="") {
													if ($rowMember["phone" . $i . "Type"]!="") {
														print $rowMember["phone" . $i . "Type"] . ":</i> " ;
													}
													if ($rowMember["phone" . $i . "CountryCode"]!="") {
														print "+" . $rowMember["phone" . $i . "CountryCode"] . " " ;
													}
													print _($rowMember["phone" . $i]) . "<br/>" ;
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
									print $row["emergency1Name"] ;
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
									if ($row["website"]!="") {
										print $row["emergency1Number2"] ;
									}
								print "</td>" ;
							print "</tr>" ;
							print "<tr>" ;
								print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>" . _('Contact 2') . "</span><br/>" ;
									print $row["emergency2Name"] ;
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
									if ($row["website"]!="") {
										print $row["emergency2Number2"] ;
									}
								print "</td>" ;
							print "</tr>" ;
						print "</table>" ;
					}
					else if ($subpage=="Medical") {
						try {
							$dataMedical=array("gibbonPersonID"=>$gibbonPersonID); 
							$sqlMedical="SELECT * FROM gibbonPersonMedical JOIN gibbonPerson ON (gibbonPersonMedical.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPerson.gibbonPersonID=:gibbonPersonID" ;
							$resultMedical=$connection2->prepare($sqlMedical);
							$resultMedical->execute($dataMedical);
						}
						catch(PDOException $e) { 
							print "<div class='error'>" . $e->getMessage() . "</div>" ; 
						}

						if ($resultMedical->rowCount()!=1) {
							if (isActionAccessible($guid, $connection2, "/modules/User Admin/medicalForm_manage_add.php")==TRUE) {
								print "<div class='linkTop'>" ;
								print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/User Admin/medicalForm_manage_add.php'>Add Medical Form<img style='margin: 0 0 -4px 3px' title='Add Medical Form' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/page_new.png'/></a> " ;
								print "</div>" ;
							}
							
							print "<div class='error'>" ;
								print _("There are no records to display.");
							print "</div>" ;
						}
						else {
							$rowMedical=$resultMedical->fetch() ;
							
							if (isActionAccessible($guid, $connection2, "/modules/User Admin/medicalForm_manage.php")==TRUE) {
								print "<div class='linkTop'>" ;
								print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/User Admin/medicalForm_manage_edit.php&gibbonPersonMedicalID=" . $rowMedical["gibbonPersonMedicalID"] . "'>" . _('Edit') . "<img style='margin: 0 0 -4px 5px' title='" . _('Edit') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/config.png'/></a> " ;
								print "</div>" ;
							}
						
							//Medical alert!
							$alert=getHighestMedicalRisk( $gibbonPersonID, $connection2 ) ;
							if ($alert!=FALSE) {
								$highestLevel=$alert[1] ;
								$highestColour=$alert[3] ;
								$highestColourBG=$alert[4] ;
								print "<div class='error' style='background-color: #" . $highestColourBG . "; border: 1px solid #" . $highestColour . "; color: #" . $highestColour . "'>" ;
								print "<b>" . sprintf(_('This student has one or more %1$s risk medical conditions'), strToLower($highestLevel)) . "</b>." ;
								print "</div>" ;
							}
						
							//Get medical conditions
							try {
								$dataCondition=array("gibbonPersonMedicalID"=>$rowMedical["gibbonPersonMedicalID"]); 
								$sqlCondition="SELECT * FROM gibbonPersonMedicalCondition WHERE gibbonPersonMedicalID=:gibbonPersonMedicalID ORDER BY name" ;
								$resultCondition=$connection2->prepare($sqlCondition);
								$resultCondition->execute($dataCondition);
							}
							catch(PDOException $e) { 
								print "<div class='error'>" . $e->getMessage() . "</div>" ; 
							}

							print "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>" ;
								print "<tr>" ;
									print "<td style='width: 33%; vertical-align: top'>" ;
										print "<span style='font-size: 115%; font-weight: bold'>" . _('Long Term Medication') . "</span><br/>" ;
										if ($rowMedical["longTermMedication"]=="") {
											print "<i>" . _('Unknown') . "</i>" ;
										}
										else {
											print $rowMedical["longTermMedication"] ;
										}
									print "</td>" ;
									print "<td style='width: 67%; vertical-align: top' colspan=2>" ;
										print "<span style='font-size: 115%; font-weight: bold'>" . _('Details') . "</span><br/>" ;
											print $rowMedical["longTermMedicationDetails"] ;
									print "</td>" ;
								print "</tr>" ;
								print "<tr>" ;
									print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
										print "<span style='font-size: 115%; font-weight: bold'>" . _('Tetanus Last 10 Years?') . "</span><br/>" ;
										if ($rowMedical["tetanusWithin10Years"]=="") {
											print "<i>" . _('Unknown') . "</i>" ;
										}
										else {
											print $rowMedical["tetanusWithin10Years"] ;
										}
									print "</td>" ;
									print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
										print "<span style='font-size: 115%; font-weight: bold'>" . _('Blood Type') . "</span><br/>" ;
										print $rowMedical["bloodType"] ;
									print "</td>" ;
									print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
										print "<span style='font-size: 115%; font-weight: bold'>" . _('Medical Conditions?') . "</span><br/>" ;
										if ($resultCondition->rowCount()>0) {
											print _("Yes") . ". " . _("Details below.") ;
										}
										else {
											_("No") ;
										}
									print "</td>" ;
								print "</tr>" ;
							print "</table>" ;
							
							while ($rowCondition=$resultCondition->fetch()) {
								print "<h4>" ;
								$alert=getAlert($connection2, $rowCondition["gibbonAlertLevelID"]) ;
								if ($alert!=FALSE) {
									print _($rowCondition["name"]) . " <span style='color: #" . $alert["color"] . "'>(" . _($alert["name"]) . " " . _('Risk') . ")</span>" ;
								}
								print "</h4>" ;
								
								print "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>" ;
								print "<tr>" ;
									print "<td style='width: 50%; vertical-align: top'>" ;
										print "<span style='font-size: 115%; font-weight: bold'>" . _('Triggers') . "</span><br/>" ;
										print $rowCondition["triggers"] ;
									print "</td>" ;
									print "<td style='width: 50%; vertical-align: top' colspan=2>" ;
										print "<span style='font-size: 115%; font-weight: bold'>" . _('Reaction') . "</span><br/>" ;
										print $rowCondition["reaction"] ;
									print "</td>" ;
								print "</tr>" ;
								print "<tr>" ;
									print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
										print "<span style='font-size: 115%; font-weight: bold'>" . _('Response') . "</span><br/>" ;
										print $rowCondition["response"] ;
									print "</td>" ;
									print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
										print "<span style='font-size: 115%; font-weight: bold'>" . _('Medication') . "</span><br/>" ;
										print $rowCondition["medication"] ;
									print "</td>" ;
								print "</tr>" ;
								print "<tr>" ;
									print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
										print "<span style='font-size: 115%; font-weight: bold'>" . _('Last Episode Date') . "</span><br/>" ;
										if (is_null($row["dob"])==FALSE AND $row["dob"]!="0000-00-00") {
											print dateConvertBack($guid, $rowCondition["lastEpisode"]) ;
										}
									print "</td>" ;
									print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
										print "<span style='font-size: 115%; font-weight: bold'>" . _('Last Episode Treatment') . "</span><br/>" ;
										print $rowCondition["lastEpisodeTreatment"] ;
									print "</td>" ;
								print "</tr>" ;
								print "<tr>" ;
									print "<td style='width: 33%; padding-top: 15px; vertical-align: top' colspan=2>" ;
										print "<span style='font-size: 115%; font-weight: bold'>" . _('Comments') . "</span><br/>" ;
										print $rowCondition["comment"] ;
									print "</td>" ;
								print "</tr>" ;
							print "</table>" ;
							}
						}
					}
					else if ($subpage=="Notes") {
						if ($enableStudentNotes!="Y") {
							print "<div class='error'>" ;
								print _("You do not have access to this action.") ;
							print "</div>" ;
						}
						else {
							if (isActionAccessible($guid, $connection2, "/modules/Students/student_view_details_notes_add.php")==FALSE) {
								print "<div class='error'>" ;
									print _("Your request failed because you do not have access to this action.") ;
								print "</div>" ; 
							}
							else {
								if (isset($_GET["deleteReturn"])) { $deleteReturn=$_GET["deleteReturn"] ; } else { $deleteReturn="" ; }
								$deleteReturnMessage="" ;
								$class="error" ;
								if (!($deleteReturn=="")) {
									if ($deleteReturn=="success0") {
										$deleteReturnMessage=_("Your request was completed successfully.") ;		
										$class="success" ;
									}
									print "<div class='$class'>" ;
										print $deleteReturnMessage;
									print "</div>" ;
								} 
							
								print "<p>" ;
									print _("Student Notes provide a way to store information on students which does not fit elsewhere in the system, or which you want to be able to see quickly in one place.") . " <b>" . _('Please remember that notes are visible to other users who have access to full student profiles (this should not generally include parents).') . "</b>" ;
								print "</p>" ;
							
								$categories=FALSE ;
								$category=NULL ;
								if (isset($_GET["category"])) {
									$category=$_GET["category"] ;
								}
							
								try {
									$dataCategories=array(); 
									$sqlCategories="SELECT * FROM gibbonStudentNoteCategory WHERE active='Y' ORDER BY name" ;
									$resultCategories=$connection2->prepare($sqlCategories);
									$resultCategories->execute($dataCategories);
								}
								catch(PDOException $e) { }
								if ($resultCategories->rowCount()>0) {
									$categories=TRUE ;
								
									print "<h3>" ;
									print _("Filter") ;
									print "</h3>" ;
									?>
									<form method="get" action="<?php print $_SESSION[$guid]["absoluteURL"]?>/index.php">
										<table class='noIntBorder' cellspacing='0' style="width: 100%">	
											<tr><td style="width: 30%"></td><td></td></tr>
											<tr>
												<td> 
													<b><?php print _('Category') ?></b><br/>
												</td>
												<td class="right">
													<?php
													print "<select name='category' id='category' style='width:302px'>" ;
														print "<option $selected value=''></option>" ;
														while ($rowCategories=$resultCategories->fetch()) {
															$selected="" ;
															if ($category==$rowCategories["gibbonStudentNoteCategoryID"]) {
																$selected="selected" ;
															}
															print "<option $selected value='" . $rowCategories["gibbonStudentNoteCategoryID"] . "'>" . $rowCategories["name"] . "</option>" ;
														}
													print "</select>" ;
													?>
												</td>
											</tr>
											<tr>
												<td colspan=2 class="right">
													<input type="hidden" name="q" value="/modules/<?php print $_SESSION[$guid]["module"] ?>/student_view_details.php">
													<input type="hidden" name="address" value="<?php print $_SESSION[$guid]["address"] ?>">
													<?php
													print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/student_view_details.php&gibbonPersonID=$gibbonPersonID&search=$search&allStudents=$allStudents&subpage=Notes'>" . _('Clear Search') . "</a>" ;
													?>
													<input type="hidden" name="gibbonPersonID" value="<?php print $gibbonPersonID ?>">
													<input type="hidden" name="allStudents" value="<?php print $allStudents ?>">
													<input type="hidden" name="search" value="<?php print $search ?>">
													<input type="hidden" name="subpage" value="Notes">
													<input type="submit" value="<?php print _("Submit") ; ?>">
												</td>
											</tr>
										</table>
									</form>
								<?php
								}
						
								try {
									if ($category==NULL) {
										$data=array("gibbonPersonID"=>$gibbonPersonID); 
										$sql="SELECT gibbonStudentNote.*, gibbonStudentNoteCategory.name AS category, surname, preferredName FROM gibbonStudentNote LEFT JOIN gibbonStudentNoteCategory ON (gibbonStudentNote.gibbonStudentNoteCategoryID=gibbonStudentNoteCategory.gibbonStudentNoteCategoryID) JOIN gibbonPerson ON (gibbonStudentNote.gibbonPersonIDCreator=gibbonPerson.gibbonPersonID) WHERE gibbonStudentNote.gibbonPersonID=:gibbonPersonID ORDER BY timestamp DESC" ; 
									}
									else {
										$data=array("gibbonPersonID"=>$gibbonPersonID, "gibbonStudentNoteCategoryID"=>$category); 
										$sql="SELECT gibbonStudentNote.*, gibbonStudentNoteCategory.name AS category, surname, preferredName FROM gibbonStudentNote LEFT JOIN gibbonStudentNoteCategory ON (gibbonStudentNote.gibbonStudentNoteCategoryID=gibbonStudentNoteCategory.gibbonStudentNoteCategoryID) JOIN gibbonPerson ON (gibbonStudentNote.gibbonPersonIDCreator=gibbonPerson.gibbonPersonID) WHERE gibbonStudentNote.gibbonPersonID=:gibbonPersonID AND gibbonStudentNote.gibbonStudentNoteCategoryID=:gibbonStudentNoteCategoryID ORDER BY timestamp DESC" ; 
									}
									$result=$connection2->prepare($sql);
									$result->execute($data);
								}
								catch(PDOException $e) { 
									print "<div class='error'>" . $e->getMessage() . "</div>" ; 
								}
							
								print "<div class='linkTop'>" ;
								print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/student_view_details_notes_add.php&gibbonPersonID=$gibbonPersonID&search=$search&allStudents=$allStudents&search=$search&allStudents=$allStudents&subpage=Notes&category=$category'>" .  _('Add') . "<img style='margin-left: 5px' title='" . _('Add') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/page_new.png'/></a>" ;
								print "</div>" ;
							
								if ($result->rowCount()<1) {
									print "<div class='error'>" ;
									print _("There are no records to display.") ;
									print "</div>" ;
								}
								else {
									print "<table cellspacing='0' style='width: 100%'>" ;
										print "<tr class='head'>" ;
											print "<th>" ;
												print _("Date") . "<br/>" ;
												print "<span style='font-size: 75%; font-style: italic'>" . _('Time') . "</span>" ;
											print "</th>" ;
											print "<th>" ;
												print _("Category") ;
											print "</th>" ;
											print "<th>" ;
												print _("Title") . "<br/>" ;
												print "<span style='font-size: 75%; font-style: italic'>" . _('Overview') . "</span>" ;
											print "</th>" ;
											print "<th>" ;
												print _("Note Taker") ;
											print "</th>" ;
											print "<th>" ;
												print _("Actions") ;
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
											$count++ ;
										
											//COLOR ROW BY STATUS!
											print "<tr class=$rowNum>" ;
												print "<td>" ;
													print dateConvertBack($guid, substr($row["timestamp"],0,10)) . "<br/>" ;
													print "<span style='font-size: 75%; font-style: italic'>" . substr($row["timestamp"],11,5) . "</span>" ;
												print "</td>" ;
												print "<td>" ;
													print $row["category"] ;
												print "</td>" ;
												print "<td>" ;
													if ($row["title"]=="") {
														print "<i>" . _('NA') . "</i><br/>" ;
													}
													else {
														print $row["title"] . "<br/>" ;
													}
													print "<span style='font-size: 75%; font-style: italic'>" .  substr(strip_tags($row["note"]),0,60)  . "</span>" ;
												print "</td>" ;
												print "<td>" ;
													print formatName("", $row["preferredName"], $row["surname"], "Staff", false, true) ;
												print "</td>" ;
												print "<td>" ;
													if ($row["gibbonPersonIDCreator"]==$_SESSION[$guid]["gibbonPersonID"]) {
														print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/student_view_details_notes_edit.php&search=" . $search . "&gibbonStudentNoteID=" . $row["gibbonStudentNoteID"] . "&gibbonPersonID=$gibbonPersonID&search=$search&allStudents=$allStudents&subpage=Notes&category=$category'><img title='" . _('Edit') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/config.png'/></a> " ;
														print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/student_view_details_notes_delete.php&search=" . $search . "&gibbonStudentNoteID=" . $row["gibbonStudentNoteID"] . "&gibbonPersonID=$gibbonPersonID&search=$search&allStudents=$allStudents&subpage=Notes&category=$category'><img title='" . _('Delete') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/garbage.png'/></a>" ;
													}
													print "<script type='text/javascript'>" ;	
														print "$(document).ready(function(){" ;
															print "\$(\".note-$count\").hide();" ;
															print "\$(\".show_hide-$count\").fadeIn(1000);" ;
															print "\$(\".show_hide-$count\").click(function(){" ;
															print "\$(\".note-$count\").fadeToggle(1000);" ;
															print "});" ;
														print "});" ;
													print "</script>" ;
													print "<a title='" . _('View Description') . "' class='show_hide-$count' onclick='return false;' href='#'><img title='" . _('View Details') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/page_down.png'/></a></span><br/>" ;
												print "</td>" ;
											print "</tr>" ;
											print "<tr class='note-$count' id='note-$count'>" ;
												print "<td colspan=6>" ;
													print $row["note"] ;
												print "</td>" ;
											print "</tr>" ;
										}
									print "</table>" ;
								}
							}
						}
					}
					else if ($subpage=="School Attendance") {
						if (isActionAccessible($guid, $connection2, "/modules/Attendance/report_studentHistory.php")==FALSE) {
							print "<div class='error'>" ;
								print _("Your request failed because you do not have access to this action.") ;
							print "</div>" ;
						}
						else {
							include "./modules/Attendance/moduleFunctions.php" ;
							report_studentHistory($guid, $gibbonPersonID, TRUE, $_SESSION[$guid]["absoluteURL"] . "/report.php?q=/modules/Attendance/report_studentHistory_print.php&gibbonPersonID=$gibbonPersonID", $connection2, $row["dateStart"], $row["dateEnd"]) ;
						}
					}
					else if ($subpage=="Markbook") {
						if (isActionAccessible($guid, $connection2, "/modules/Markbook/markbook_view.php")==FALSE) {
							print "<div class='error'>" ;
								print _("Your request failed because you do not have access to this action.");
							print "</div>" ;
						}
						else {
							$highestAction=getHighestGroupedAction($guid, "/modules/Markbook/markbook_view.php", $connection2) ;
							if ($highestAction==FALSE) {
								print "<div class='error'>" ;
									print _("The highest grouped action cannot be determined.") ;
								print "</div>" ;
							}
							else {
								//Get alternative header names
								$attainmentAlternativeName=getSettingByScope($connection2, "Markbook", "attainmentAlternativeName") ;
								$attainmentAlternativeNameAbrev=getSettingByScope($connection2, "Markbook", "attainmentAlternativeNameAbrev") ;
								$effortAlternativeName=getSettingByScope($connection2, "Markbook", "effortAlternativeName") ;
								$effortAlternativeNameAbrev=getSettingByScope($connection2, "Markbook", "effortAlternativeNameAbrev") ;

								$alert=getAlert($connection2, 002) ;
								$role=getRoleCategory($_SESSION[$guid]["gibbonRoleIDCurrent"], $connection2) ;
								if ($role=="Parent") {
									$showParentAttainmentWarning=getSettingByScope($connection2, "Markbook", "showParentAttainmentWarning" ) ; 
									$showParentEffortWarning=getSettingByScope($connection2, "Markbook", "showParentEffortWarning" ) ; 														
								}
								else {
									$showParentAttainmentWarning="Y" ;
									$showParentEffortWarning="Y" ;
								}
								$entryCount=0 ;
								
								$and="" ;
								$and2="" ;
								$dataList=array() ;
								$dataEntry=array() ;
								$filter=NULL ;
								if (isset($_GET["filter"])) {
									$filter=$_GET["filter"] ;
								}
								else if (isset($_POST["filter"])) {
									$filter=$_POST["filter"] ;
								}
								if ($filter=="") {
									$filter=$_SESSION[$guid]["gibbonSchoolYearID"] ;
								}
								if ($filter!="*") {
									$dataList["filter"]=$filter ;
									$and.=" AND gibbonSchoolYearID=:filter" ;
								}
								
								$filter2=NULL ;
								if (isset($_GET["filter2"])) {
									$filter2=$_GET["filter2"] ;
								}
								else if (isset($_POST["filter2"])) {
									$filter2=$_POST["filter2"] ;
								}
								if ($filter2!="") {
									$dataList["filter2"]=$filter2 ;
									$and.=" AND gibbonDepartmentID=:filter2" ;
								}
								
								$filter3=NULL ;
								if (isset($_GET["filter3"])) {
									$filter3=$_GET["filter3"] ;
								}
								else if (isset($_POST["filter3"])) {
									$filter3=$_POST["filter3"] ;
								}
								if ($filter3!="") {
									$dataEntry["filter3"]=$filter3 ;
									$and2.=" AND type=:filter3" ;
								}
								
								print "<p>" ;
									print _("This page displays academic results for a student throughout their school career. Only subjects with published results are shown.") ;
								print "</p>" ;
								
								print "<form method='post' action='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=" . $_GET["q"] . "&gibbonPersonID=$gibbonPersonID&search=$search&allStudents=$allStudents&subpage=Markbook'>" ;
									print"<table class='noIntBorder' cellspacing='0' style='width: 100%'>" ;	
										?>
										<tr>
											<td> 
												<b><?php print _('Learning Areas') ?></b><br/>
												<span style="font-size: 90%"><i></i></span>
											</td>
											<td class="right">
												<?php
												print "<select name='filter2' id='filter2' style='width:302px'>" ;
													print "<option value=''>" . _('All Learning Areas') . "</option>" ;
													try {
														$dataSelect=array(); 
														$sqlSelect="SELECT * FROM gibbonDepartment WHERE type='Learning Area' ORDER BY name" ;
														$resultSelect=$connection2->prepare($sqlSelect);
														$resultSelect->execute($dataSelect);
													}
													catch(PDOException $e) { }
													while ($rowSelect=$resultSelect->fetch()) {
														$selected="" ;
														if ($rowSelect["gibbonDepartmentID"]==$filter2) {
															$selected="selected" ;
														}
														print "<option $selected value='" . $rowSelect["gibbonDepartmentID"] . "'>" . $rowSelect["name"] . "</option>" ;
													}
												print "</select>" ;
												?>
											</td>
										</tr>
										<tr>
											<td> 
												<b><?php print _('School Years') ?></b><br/>
												<span style="font-size: 90%"><i></i></span>
											</td>
											<td class="right">
												<?php
												print "<select name='filter' id='filter' style='width:302px'>" ;
													print "<option value='*'>" . _('All Years') . "</option>" ;
													try {
														$dataSelect=array("gibbonPersonID"=>$gibbonPersonID); 
														$sqlSelect="SELECT gibbonSchoolYear.gibbonSchoolYearID, gibbonSchoolYear.name AS year, gibbonYearGroup.name AS yearGroup FROM gibbonStudentEnrolment JOIN gibbonSchoolYear ON (gibbonStudentEnrolment.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) JOIN gibbonYearGroup ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID) WHERE gibbonPersonID=:gibbonPersonID ORDER BY gibbonSchoolYear.sequenceNumber" ;
														$resultSelect=$connection2->prepare($sqlSelect);
														$resultSelect->execute($dataSelect);
													}
													catch(PDOException $e) { }
													while ($rowSelect=$resultSelect->fetch()) {
														$selected="" ;
														if ($rowSelect["gibbonSchoolYearID"]==$filter) {
															$selected="selected" ;
														}
														print "<option $selected value='" . $rowSelect["gibbonSchoolYearID"] . "'>" . $rowSelect["year"] . " (" . $rowSelect["yearGroup"] . ")</option>" ;
													}
												print "</select>" ;
												?>
											</td>
										</tr>
										<?php
										$types=getSettingByScope($connection2, "Markbook", "markbookType") ;
										if ($types!=FALSE) {
											$types=explode(",", $types) ;
											?>
											<tr>
												<td> 
													<b><?php print _('Type') ?></b><br/>
													<span style="font-size: 90%"><i></i></span>
												</td>
												<td class="right">
													<select name="filter3" id="filter3" style="width: 302px">
														<option value=""></option>
														<?php
														for ($i=0; $i<count($types); $i++) {
															$selected="" ;
															if ($filter3==$types[$i]) {
																$selected="selected" ;
															}
															?>
															<option <?php print $selected ?> value="<?php print trim($types[$i]) ?>"><?php print trim($types[$i]) ?></option>
														<?php
														}
														?>
													</select>
												</td>
											</tr>
											<?php
										}
										print "<tr>" ;
											print "<td class='right' colspan=2>" ;
												print "<input type='hidden' name='q' value='" . $_GET["q"] . "'>" ;
												print "<input checked type='checkbox' name='details' class='details' value='Yes' />" ;
												print "<span style='font-size: 85%; font-weight: normal; font-style: italic'> " ._('Show/Hide Details') . "</span>" ;
												?>
												<script type="text/javascript">
													/* Show/Hide detail control */
													$(document).ready(function(){
														$(".details").click(function(){
															if ($('input[name=details]:checked').val()=="Yes" ) {
																$(".detailItem").slideDown("fast", $("#detailItem").css("{'display' : 'table-row'}")); 
															} 
															else {
																$(".detailItem").slideUp("fast"); 
															}
														 });
													});
												</script>
												<?php
												print "<input type='submit' value='" . _('Go') . "'>" ;
											print "</td>" ;
										print "</tr>" ;
									print"</table>" ;
								print "</form>" ;
								
								//Get class list
								try {
									$dataList["gibbonPersonID"]=$gibbonPersonID ; 
									$dataList["gibbonPersonID2"]=$gibbonPersonID; 
									$sqlList="SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourse.name, gibbonCourseClass.gibbonCourseClassID, gibbonScaleGrade.value AS target FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) LEFT JOIN gibbonMarkbookTarget ON (gibbonMarkbookTarget.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID AND gibbonMarkbookTarget.gibbonPersonIDStudent=:gibbonPersonID2) LEFT JOIN gibbonScaleGrade ON (gibbonMarkbookTarget.gibbonScaleGradeID=gibbonScaleGrade.gibbonScaleGradeID) WHERE gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID $and ORDER BY course, class" ;
									$resultList=$connection2->prepare($sqlList);
									$resultList->execute($dataList);
								}
								catch(PDOException $e) { 
									print "<div class='error'>" . $e->getMessage() . "</div>" ; 
								}
								
								if ($resultList->rowCount()>0) {
									while ($rowList=$resultList->fetch()) {
										try {
											$dataEntry["gibbonPersonID"]=$gibbonPersonID ;
											$dataEntry["gibbonCourseClassID"]=$rowList["gibbonCourseClassID"] ;
											if ($highestAction=="Markbook_viewMyChildrensClasses") {
												$sqlEntry="SELECT *, gibbonMarkbookColumn.comment AS commentOn, gibbonMarkbookColumn.uploadedResponse AS uploadedResponseOn, gibbonMarkbookEntry.comment AS comment FROM gibbonMarkbookEntry JOIN gibbonMarkbookColumn ON (gibbonMarkbookEntry.gibbonMarkbookColumnID=gibbonMarkbookColumn.gibbonMarkbookColumnID) WHERE gibbonPersonIDStudent=:gibbonPersonID AND gibbonCourseClassID=:gibbonCourseClassID AND complete='Y' AND completeDate<='" . date("Y-m-d") . "' AND viewableParents='Y' $and2 ORDER BY completeDate" ;
											}
											else {
												$sqlEntry="SELECT *, gibbonMarkbookColumn.comment AS commentOn, gibbonMarkbookColumn.uploadedResponse AS uploadedResponseOn, gibbonMarkbookEntry.comment AS comment FROM gibbonMarkbookEntry JOIN gibbonMarkbookColumn ON (gibbonMarkbookEntry.gibbonMarkbookColumnID=gibbonMarkbookColumn.gibbonMarkbookColumnID) WHERE gibbonPersonIDStudent=:gibbonPersonID AND gibbonCourseClassID=:gibbonCourseClassID AND complete='Y' AND completeDate<='" . date("Y-m-d") . "' $and2 ORDER BY completeDate" ;
											}
											$resultEntry=$connection2->prepare($sqlEntry);
											$resultEntry->execute($dataEntry);
										}
										catch(PDOException $e) { 
											print "<div class='error'>" . $e->getMessage() . "</div>" ; 
										}
										
										if ($resultEntry->rowCount()>0) {
											print "<a name='" . $rowList["gibbonCourseClassID"] . "'></a><h4>" . $rowList["course"] . "." . $rowList["class"] . " <span style='font-size:85%; font-style: italic'>(" . $rowList["name"] . ")</span></h4>" ;
											
											try {
												$dataTeachers=array("gibbonCourseClassID"=>$rowList["gibbonCourseClassID"]); 
												$sqlTeachers="SELECT title, surname, preferredName FROM gibbonPerson JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE role='Teacher' AND gibbonCourseClassID=:gibbonCourseClassID ORDER BY surname, preferredName" ;
												$resultTeachers=$connection2->prepare($sqlTeachers);
												$resultTeachers->execute($dataTeachers);
											}
											catch(PDOException $e) { 
												print "<div class='error'>" . $e->getMessage() . "</div>" ; 
											}
											
											$teachers="<p><b>" . _('Taught by:') . "</b> " ;
											while ($rowTeachers=$resultTeachers->fetch()) {
												$teachers=$teachers . $rowTeachers["title"] . " " . $rowTeachers["surname"] . ", " ;
											}
											$teachers=substr($teachers,0,-2) ;
											$teachers=$teachers . "</p>" ;
											print $teachers ;
											
											if ($rowList["target"]!="") {
												print "<div style='font-weight: bold' class='linkTop'>" ;
													print _("Target") . ": " . $rowList["target"] ;
												print "</div>" ; 
											}
						
											print "<table cellspacing='0' style='width: 100%'>" ;
											print "<tr class='head'>" ;
												print "<th style='width: 120px'>" ;
													print _("Assessment") ;
												print "</th>" ;
												print "<th style='width: 75px; text-align: center'>" ;
													if ($attainmentAlternativeName!="") { print $attainmentAlternativeName ; } else { print _('Attainment') ; }
												print "</th>" ;
												print "<th style='width: 75px; text-align: center'>" ;
													if ($effortAlternativeName!="") { print $effortAlternativeName ; } else { print _('Effort') ; }
												print "</th>" ;
												print "<th>" ;
													print _("Comment") ;
												print "</th>" ;
												print "<th style='width: 75px'>" ;
													print _("Submission") ;
												print "</th>" ;
											print "</tr>" ;
											
											$count=0 ;
											while ($rowEntry=$resultEntry->fetch()) {
												if ($count%2==0) {
													$rowNum="even" ;
												}
												else {
													$rowNum="odd" ;
												}
												$count++ ;
												$entryCount++ ;
												
												print "<tr class=$rowNum>" ;
													print "<td>" ;
														print "<span title='" . htmlPrep($rowEntry["description"]) . "'><b><u>" . $rowEntry["name"] . "</u></b></span><br/>" ;
														print "<span style='font-size: 90%; font-style: italic; font-weight: normal'>" ;
														$unit=getUnit($connection2, $rowEntry["gibbonUnitID"], $rowEntry["gibbonHookID"], $rowEntry["gibbonCourseClassID"]) ;
														if (isset($unit[0])) {
															print $unit[0] . "<br/>" ;
														}
														if (isset($unit[1])) {
															if ($unit[1]!="") {
																print $unit[1] . " " . _('Unit') . "</i><br/>" ;
															}
														}
														if ($rowEntry["completeDate"]!="") {
															print _("Marked on") . " " . dateConvertBack($guid, $rowEntry["completeDate"]) . "<br/>" ;
														}
														else {
															print _("Unmarked") . "<br/>" ;
														}
														print $rowEntry["type"] ;
														if ($rowEntry["attachment"]!="" AND file_exists($_SESSION[$guid]["absolutePath"] . "/" . $rowEntry["attachment"])) {
															print " | <a 'title='" . _('Download more information') . "' href='" . $_SESSION[$guid]["absoluteURL"] . "/" . $rowEntry["attachment"] . "'>" . _('More info') . "</a>"; 
														}
														print "</span><br/>" ;
													print "</td>" ;
													if ($rowEntry["attainment"]=="N" OR ($rowEntry["gibbonScaleIDAttainment"]=="" AND $rowEntry["gibbonRubricIDAttainment"]=="")) {
														print "<td class='dull' style='text-align: center'>" ;
														
														print "</td>" ;
													}
													else {
														print "<td style='text-align: center'>" ;
															$attainmentExtra="" ;
															try {
																$dataAttainment=array("gibbonScaleIDAttainment"=>$rowEntry["gibbonScaleIDAttainment"]); 
																$sqlAttainment="SELECT * FROM gibbonScale WHERE gibbonScaleID=:gibbonScaleIDAttainment" ;
																$resultAttainment=$connection2->prepare($sqlAttainment);
																$resultAttainment->execute($dataAttainment);
															}
															catch(PDOException $e) { 
																print "<div class='error'>" . $e->getMessage() . "</div>" ; 
															}
															if ($resultAttainment->rowCount()==1) {
																$rowAttainment=$resultAttainment->fetch() ;
																$attainmentExtra="<br/>" . _($rowAttainment["usage"]) ;
															}
															$styleAttainment="style='font-weight: bold'" ;
															if ($rowEntry["attainmentConcern"]=="Y" AND $showParentAttainmentWarning=="Y") {
																$styleAttainment="style='color: #" . $alert["color"] . "; font-weight: bold; border: 2px solid #" . $alert["color"] . "; padding: 2px 4px; background-color: #" . $alert["colorBG"] . "'" ;
															}
															else if ($rowEntry["attainmentConcern"]=="P" AND $showParentAttainmentWarning=="Y") {
																$styleAttainment="style='color: #390; font-weight: bold; border: 2px solid #390; padding: 2px 4px; background-color: #D4F6DC'" ;
															}
															print "<div $styleAttainment>" . $rowEntry["attainmentValue"] ;
																if ($rowEntry["gibbonRubricIDAttainment"]!="") {
																	print "<a class='thickbox' href='" . $_SESSION[$guid]["absoluteURL"] . "/fullscreen.php?q=/modules/Markbook/markbook_view_rubric.php&gibbonRubricID=" . $rowEntry["gibbonRubricIDAttainment"] . "&gibbonCourseClassID=" . $rowList["gibbonCourseClassID"] . "&gibbonMarkbookColumnID=" . $rowEntry["gibbonMarkbookColumnID"] . "&gibbonPersonID=$gibbonPersonID&mark=FALSE&type=attainment&width=1100&height=550'><img style='margin-bottom: -3px; margin-left: 3px' title='View Rubric' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/rubric.png'/></a>" ;
																}
															print "</div>" ;
															if ($rowEntry["attainmentValue"]!="") {
																print "<div class='detailItem' style='font-size: 75%; font-style: italic; margin-top: 2px'><b>" . htmlPrep(_($rowEntry["attainmentDescriptor"])) . "</b>" . _($attainmentExtra) . "</div>" ;
															}
														print "</td>" ;
													}
													if ($rowEntry["effort"]=="N" OR ($rowEntry["gibbonScaleIDEffort"]=="" AND $rowEntry["gibbonRubricIDEffort"]=="")) {
														print "<td class='dull' style='text-align: center'>" ;
														
														print "</td>" ;
													}
													else {
														print "<td style='text-align: center'>" ;
															$effortExtra="" ;
															try {
																$dataEffort=array("gibbonScaleIDEffort"=>$rowEntry["gibbonScaleIDEffort"]); 
																$sqlEffort="SELECT * FROM gibbonScale WHERE gibbonScaleID=:gibbonScaleIDEffort" ;
																$resultEffort=$connection2->prepare($sqlEffort);
																$resultEffort->execute($dataEffort);
															}
															catch(PDOException $e) { 
																print "<div class='error'>" . $e->getMessage() . "</div>" ; 
															}
	
															if ($resultEffort->rowCount()==1) {
																$rowEffort=$resultEffort->fetch() ;
																$effortExtra="<br/>" . _($rowEffort["usage"]) ;
															}
															$styleEffort="style='font-weight: bold'" ;
															if ($rowEntry["effortConcern"]=="Y" AND $showParentEffortWarning=="Y") {
																$styleEffort="style='color: #" . $alert["color"] . "; font-weight: bold; border: 2px solid #" . $alert["color"] . "; padding: 2px 4px; background-color: #" . $alert["colorBG"] . "'" ;
															}
															print "<div $styleEffort>" . $rowEntry["effortValue"] ;
																if ($rowEntry["gibbonRubricIDEffort"]!="") {
																	print "<a class='thickbox' href='" . $_SESSION[$guid]["absoluteURL"] . "/fullscreen.php?q=/modules/Markbook/markbook_view_rubric.php&gibbonRubricID=" . $rowEntry["gibbonRubricIDEffort"] . "&gibbonCourseClassID=" . $rowList["gibbonCourseClassID"] . "&gibbonMarkbookColumnID=" . $rowEntry["gibbonMarkbookColumnID"] . "&gibbonPersonID=$gibbonPersonID&mark=FALSE&type=effort&width=1100&height=550'><img style='margin-bottom: -3px; margin-left: 3px' title='View Rubric' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/rubric.png'/></a>" ;
																}
															print "</div>" ;
															if ($rowEntry["effortValue"]!="") {
																print "<div class='detailItem' style='font-size: 75%; font-style: italic; margin-top: 2px'><b>" . htmlPrep(_($rowEntry["effortDescriptor"])) . "</b>" . _($effortExtra) . "</div>" ;
															}
														print "</td>" ;
													}
													if ($rowEntry["commentOn"]=="N" AND $rowEntry["uploadedResponseOn"]=="N") {
														print "<td class='dull' style='text-align: center'>" ;
	
														print "</td>" ;
													}
													else {
														print "<td>" ;
															if ($rowEntry["comment"]!="") {
																if (strlen($rowEntry["comment"])>50) {
																	print "<script type='text/javascript'>" ;	
																		print "$(document).ready(function(){" ;
																			print "\$(\".comment-$entryCount\").hide();" ;
																			print "\$(\".show_hide-$entryCount\").fadeIn(1000);" ;
																			print "\$(\".show_hide-$entryCount\").click(function(){" ;
																			print "\$(\".comment-$entryCount\").fadeToggle(1000);" ;
																			print "});" ;
																		print "});" ;
																	print "</script>" ;
																	print "<span>" . substr($rowEntry["comment"], 0, 50) . "...<br/>" ;
																	print "<a title='" . _('View Description') . "' class='show_hide-$entryCount' onclick='return false;' href='#'>" . _('Read more') . "</a></span><br/>" ;
																}
																else {
																	print $rowEntry["comment"] ;
																}
																if ($rowEntry["response"]!="") {
																	print "<a title='Uploaded Response' href='" . $_SESSION[$guid]["absoluteURL"] . "/" . $rowEntry["response"] . "'>" . _('Uploaded Response') . "</a><br/>" ;
																}
															}
														print "</td>" ;
													}
													if ($rowEntry["gibbonPlannerEntryID"]==0) {
														print "<td class='dull' style='text-align: center'>" ;
	
														print "</td>" ;
													}
													else {
														try {
															$dataSub=array("gibbonPlannerEntryID"=>$rowEntry["gibbonPlannerEntryID"]); 
															$sqlSub="SELECT * FROM gibbonPlannerEntry WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND homeworkSubmission='Y'" ;
															$resultSub=$connection2->prepare($sqlSub);
															$resultSub->execute($dataSub);
														}
														catch(PDOException $e) { 
															print "<div class='error'>" . $e->getMessage() . "</div>" ; 
														}
														if ($resultSub->rowCount()!=1) {
															print "<td class='dull' style='text-align: center'>" ;
	
															print "</td>" ;
														}
														else {
															print "<td>" ;
																$rowSub=$resultSub->fetch() ;
																
																try {
																	$dataWork=array("gibbonPlannerEntryID"=>$rowEntry["gibbonPlannerEntryID"], "gibbonPersonID"=>$_GET["gibbonPersonID"]); 
																	$sqlWork="SELECT * FROM gibbonPlannerEntryHomework WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND gibbonPersonID=:gibbonPersonID ORDER BY count DESC" ;
																	$resultWork=$connection2->prepare($sqlWork);
																	$resultWork->execute($dataWork);
																}
																catch(PDOException $e) { 
																	print "<div class='error'>" . $e->getMessage() . "</div>" ; 
																}
																if ($resultWork->rowCount()>0) {
																	$rowWork=$resultWork->fetch() ;
																	
																	if ($rowWork["status"]=="Exemption") {
																		$linkText=_("Exemption") ;
																	}
																	else if ($rowWork["version"]=="Final") {
																		$linkText=_("Final") ;
																	}
																	else {
																		$linkText=_("Draft") . " " . $rowWork["count"] ;
																	}
																	
																	$style="" ;
																	$status="On Time" ;
																	if ($rowWork["status"]=="Exemption") {
																		$status=_("Exemption") ;
																	}
																	else if ($rowWork["status"]=="Late") {
																		$style="style='color: #ff0000; font-weight: bold; border: 2px solid #ff0000; padding: 2px 4px'" ;
																		$status=_("Late") ;
																	}
																	
																	if ($rowWork["type"]=="File") {
																		print "<span title='" . $rowWork["version"] . ". $status. " . sprintf(_('Submitted at %1$s on %2$s'), substr($rowWork["timestamp"],11,5), dateConvertBack($guid, substr($rowWork["timestamp"],0,10))) . "' $style><a href='" . $_SESSION[$guid]["absoluteURL"] . "/" . $rowWork["location"] ."'>$linkText</a></span>" ;
																	}
																	else if ($rowWork["type"]=="Link") {
																		print "<span title='" . $rowWork["version"] . ". $status. " . sprintf(_('Submitted at %1$s on %2$s'), substr($rowWork["timestamp"],11,5), dateConvertBack($guid, substr($rowWork["timestamp"],0,10))) . "' $style><a target='_blank' href='" . $rowWork["location"] ."'>$linkText</a></span>" ;
																	}
																	else {
																		print "<span title='$status. " . sprintf(_('Recorded at %1$s on %2$s'), substr($rowWork["timestamp"],11,5), dateConvertBack($guid, substr($rowWork["timestamp"],0,10))) . "' $style>$linkText</span>" ;
																	}
																}
																else {
																	if (date("Y-m-d H:i:s")<$rowSub["homeworkDueDateTime"]) {
																		print "<span title='Pending'>" . _('Pending') . "</span>" ;
																	}
																	else {
																		if ($row["dateStart"]>$rowSub["date"]) {
																			print "<span title='" . _('Student joined school after assessment was given.') . "' style='color: #000; font-weight: normal; border: 2px none #ff0000; padding: 2px 4px'>" . _('NA') . "</span>" ;
																		}
																		else {
																			if ($rowSub["homeworkSubmissionRequired"]=="Compulsory") {
																				print "<div style='color: #ff0000; font-weight: bold; border: 2px solid #ff0000; padding: 2px 4px; margin: 2px 0px'>" . _('Incomplete') . "</div>" ;
																			}
																			else {
																				print _("Not submitted online") ;
																			}
																		}
																	}
																}	
															print "</td>" ;
														}
													}
												print "</tr>" ;
												if (strlen($rowEntry["comment"])>50) {
													print "<tr class='comment-$entryCount' id='comment-$entryCount'>" ;
														print "<td colspan=6>" ;
															print $rowEntry["comment"] ;
														print "</td>" ;
													print "</tr>" ;
												}
											}
											print "</table>" ;
										}
									}
								}
								if ($entryCount<1) {
									print "<div class='error'>" ;
										print _("There are no records to display.") ;
									print "</div>" ;
								}
							}
						}
					}
					else if ($subpage=="Individual Needs") {
						if (isActionAccessible($guid, $connection2, "/modules/Attendance/report_studentHistory.php")==FALSE) {
							print "<div class='error'>" ;
								print _("Your request failed because you do not have access to this action.");
							print "</div>" ;
						}
						else {
							//Module includes
							include "./modules/Individual Needs/moduleFunctions.php" ;
								
							$statusTable=printINStatusTable($connection2, $gibbonPersonID, "disabled") ;
							if ($statusTable==FALSE) {
								print "<div class='error'>" ;
								print _("Your request failed due to a database error.") ;
								print "</div>" ;
							}
							else {
								print $statusTable ;
							}
							
							print "<h3>" ;
								print _("Individual Education Plan") ;
							print "</h3>" ;
							try {
								$dataIN=array("gibbonPersonID"=>$gibbonPersonID); 
								$sqlIN="SELECT * FROM gibbonIN WHERE gibbonPersonID=:gibbonPersonID" ;
								$resultIN=$connection2->prepare($sqlIN);
								$resultIN->execute($dataIN);
							}
							catch(PDOException $e) { 
								print "<div class='error'>" . $e->getMessage() . "</div>" ; 
							}
							
							if ($resultIN->rowCount()!=1) {
								print "<div class='error'>" ;
								print _("There are no records to display.") ;
								print "</div>" ;
							}
							else {
								$rowIN=$resultIN->fetch() ;
								
								print "<div style='font-weight: bold'>" . _('Targets') . "</div>" ;
								print "<p>" . $rowIN["targets"] . "</p>" ;
								
								print "<div style='font-weight: bold; margin-top: 30px'>" . _('Teaching Strategies') . "</div>" ;
								print "<p>" . $rowIN["strategies"] . "</p>" ;
								
								print "<div style='font-weight: bold; margin-top: 30px'>" . _('Notes & Review') . "s</div>" ;
								print "<p>" . $rowIN["notes"] . "</p>" ;
							}
						}
					}
					else if ($subpage=="Library Borrowing") {
						if (isActionAccessible($guid, $connection2, "/modules/Library/report_studentBorrowingRecord.php")==FALSE) {
							print "<div class='error'>" ;
								print _("Your request failed because you do not have access to this action.");
							print "</div>" ;
						}
						else {
							include "./modules/Library/moduleFunctions.php" ;
							
							//Print borrowing record
							$output=getBorrowingRecord($guid, $connection2, $gibbonPersonID) ;
							if ($output==FALSE) {
								print "<div class='error'>" ;
									print _("Your request failed due to a database error.") ;
								print "</div>" ;
							}
							else {
								print $output ;
							}
						}
					}
					else if ($subpage=="Timetable") {
						if (isActionAccessible($guid, $connection2, "/modules/Timetable/tt_view.php")==FALSE) {
							print "<div class='error'>" ;
								print _("Your request failed because you do not have access to this action.");
							print "</div>" ;
						}
						else {
							if (isActionAccessible($guid, $connection2, "/modules/Timetable Admin/courseEnrolment_manage_byPerson_edit.php")==TRUE) {
								$role=getRoleCategory($row["gibbonRoleIDPrimary"], $connection2) ;
								if ($role=="Student" OR $role=="Staff") {
									print "<div class='linkTop'>" ;
									print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Timetable Admin/courseEnrolment_manage_byPerson_edit.php&gibbonPersonID=$gibbonPersonID&gibbonSchoolYearID=" . $_SESSION[$guid]["gibbonSchoolYearID"] . "&type=$role'>" . _('Edit') . "<img style='margin: 0 0 -4px 5px' title='" . _('Edit') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/config.png'/></a> " ;
									print "</div>" ;
								}
							}
						
							include "./modules/Timetable/moduleFunctions.php" ;
							$ttDate=NULL ;
							if (isset($_POST["ttDate"])) {
								$ttDate=dateConvertToTimestamp(dateConvert($guid, $_POST["ttDate"]));
							}
							$tt=renderTT($guid, $connection2,$gibbonPersonID, "", FALSE, $ttDate, "/modules/Students/student_view_details.php", "&gibbonPersonID=$gibbonPersonID&search=$search&allStudents=$allStudents&subpage=Timetable") ;
							if ($tt!=FALSE) {
								print $tt ;
							}
							else {
								print "<div class='error'>" ;
									print _("There are no records to display.") ;
								print "</div>" ;
							}
						}
					}
					else if ($subpage=="External Assessment") {
						if (isActionAccessible($guid, $connection2, "/modules/External Assessment/externalAssessment_details.php")==FALSE) {
							print "<div class='error'>" ;
								print _("Your request failed because you do not have access to this action.");
							print "</div>" ;
						}
						else {
							include "./modules/External Assessment/moduleFunctions.php" ;
							
							//Print assessments
							$gibbonYearGroupID="" ;
							if (isset($row["gibbonYearGroupID"])) {
								$gibbonYearGroupID=$row["gibbonYearGroupID"] ;
							}
							externalAssessmentDetails($guid, $gibbonPersonID, $connection2, $gibbonYearGroupID) ;
						}
					}
					else if ($subpage=="Activities") {
						if (!(isActionAccessible($guid, $connection2, "/modules/Activities/report_activityChoices_byStudent"))) {
							print "<div class='error'>" ;
								print _("Your request failed because you do not have access to this action.");
							print "</div>" ;
						}
						else {
							print "<p>" ;
							print _("This report shows the current and historical activities that a student has enroled in.") ;
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
								print _("There are no records to display.") ;
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
													print _("Activity") ;
												print "</th>" ;
												$options=getSettingByScope($connection2, "Activities", "activityTypes") ;
												if ($options!="") {
													print "<th>" ;
														print _("Type") ;
													print "</th>" ;
												}
												print "<th>" ;
													if ($dateType!="Date") {
														print _("Term") ;
													}
													else {
														print _("Dates") ;
													}
												print "</th>" ;
												print "<th>" ;
													print _("Status") ;
												print "</th>" ;
												print "<th>" ;
													print _("Actions") ;
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
															print "<i>" . _('NA') . "</i>" ;
														}
													print "</td>" ;
													print "<td>" ;
														print "<a class='thickbox' href='" . $_SESSION[$guid]["absoluteURL"] . "/fullscreen.php?q=/modules/Activities/activities_my_full.php&gibbonActivityID=" . $row["gibbonActivityID"] . "&width=1000&height=550'><img title='" . _('View Details') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/plus.png'/></a> " ;
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
								print _("Your request failed because you do not have access to this action.");
							print "</div>" ;
						}
						else {
							print "<h4>" ;
							print _("Upcoming Deadlines") ;
							print "</h4>" ;
							
							try {
								$data=array("gibbonPersonID"=>$gibbonPersonID, "gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
								$sql="
								(SELECT 'teacherRecorded' AS type, gibbonPlannerEntryID, gibbonUnitID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, date, timeStart, timeEnd, viewableStudents, viewableParents, homework, homeworkDueDateTime, role FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND NOT role='Student - Left' AND NOT role='Teacher - Left' AND homework='Y' AND (role='Teacher' OR (role='Student' AND viewableStudents='Y')) AND homeworkDueDateTime>'" . date("Y-m-d H:i:s") . "' AND ((date<'" . date("Y-m-d") . "') OR (date='" . date("Y-m-d") . "' AND timeEnd<='" . date("H:i:s") . "')))
								UNION
								(SELECT 'studentRecorded' AS type, gibbonPlannerEntry.gibbonPlannerEntryID, gibbonUnitID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, date, timeStart, timeEnd, 'Y' AS viewableStudents, 'Y' AS viewableParents, 'Y' AS homework, gibbonPlannerEntryStudentHomework.homeworkDueDateTime, role FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) JOIN gibbonPlannerEntryStudentHomework ON (gibbonPlannerEntryStudentHomework.gibbonPlannerEntryID=gibbonPlannerEntry.gibbonPlannerEntryID AND gibbonPlannerEntryStudentHomework.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND NOT role='Student - Left' AND NOT role='Teacher - Left' AND (role='Teacher' OR (role='Student' AND viewableStudents='Y')) AND gibbonPlannerEntryStudentHomework.homeworkDueDateTime>'" . date("Y-m-d H:i:s") . "' AND ((date<'" . date("Y-m-d") . "') OR (date='" . date("Y-m-d") . "' AND timeEnd<='" . date("H:i:s") . "')))
								 ORDER BY homeworkDueDateTime, type" ;
								 $result=$connection2->prepare($sql);
								$result->execute($data);
							}
							catch(PDOException $e) { 
								print "<div class='error'>" . $e->getMessage() . "</div>" ; 
							}
							
							if ($result->rowCount()<1) {
								print "<div class='success'>" ;
									print _("No upcoming deadlines!") ;
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
									print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Planner/planner_view_full.php&search=$gibbonPersonID&gibbonPlannerEntryID=" . $row["gibbonPlannerEntryID"] . "&viewBy=date&date=" . $row["date"] . "&width=1000&height=550'>" . $row["course"] . "." . $row["class"] . "</a><br/>" ;
									print "<span style='font-style: italic'>" . sprintf(_('Due at %1$s on %2$s'), substr($row["homeworkDueDateTime"],11,5), dateConvertBack($guid, substr($row["homeworkDueDateTime"],0,10))) ;
									print "</li>" ;
								}
								print "</ol>" ;
							}
							
							$style="" ;
							
							print "<h4>" ;
							print _("Homework History") ;
							print "</h4>" ;
							
							$gibbonCourseClassIDFilter=NULL ;
							$filter=NULL ;
							$filter2=NULL ;
							if (isset($_GET["gibbonCourseClassIDFilter"])) {
								$gibbonCourseClassIDFilter=$_GET["gibbonCourseClassIDFilter"] ;
							}
							$data=array() ;
							if ($gibbonCourseClassIDFilter!="") {
								$data["gibbonCourseClassIDFilter"]=$gibbonCourseClassIDFilter ;
								$data["gibbonCourseClassIDFilter2"]=$gibbonCourseClassIDFilter ;
								$filter=" AND gibbonPlannerEntry.gibbonCourseClassID=:gibbonCourseClassIDFilter" ;
								$filte2=" AND gibbonPlannerEntry.gibbonCourseClassID=:gibbonCourseClassIDFilte2" ;
							}
							
							try {
								$data["gibbonPersonID"]=$gibbonPersonID;
								$data["gibbonSchoolYearID"]=$_SESSION[$guid]["gibbonSchoolYearID"] ;
								$sql="
								(SELECT 'teacherRecorded' AS type, gibbonPlannerEntryID, gibbonUnitID, gibbonHookID, gibbonPlannerEntry.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, date, timeStart, timeEnd, viewableStudents, viewableParents, homework, role, homeworkDueDateTime, homeworkDetails, homeworkSubmission, homeworkSubmissionRequired FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND NOT role='Student - Left' AND NOT role='Teacher - Left' AND homework='Y' AND gibbonSchoolYearID=:gibbonSchoolYearID AND (date<'" . date("Y-m-d") . "' OR (date='" . date("Y-m-d") . "' AND timeEnd<='" . date("H:i:s") . "')) $filter)
								UNION
								(SELECT 'studentRecorded' AS type, gibbonPlannerEntry.gibbonPlannerEntryID, gibbonUnitID, gibbonHookID, gibbonPlannerEntry.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, date, timeStart, timeEnd, 'Y' AS viewableStudents, 'Y' AS viewableParents, 'Y' AS homework, role, gibbonPlannerEntryStudentHomework.homeworkDueDateTime AS homeworkDueDateTime, gibbonPlannerEntryStudentHomework.homeworkDetails AS homeworkDetails, 'N' AS homeworkSubmission, '' AS homeworkSubmissionRequired FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) JOIN gibbonPlannerEntryStudentHomework ON (gibbonPlannerEntryStudentHomework.gibbonPlannerEntryID=gibbonPlannerEntry.gibbonPlannerEntryID AND gibbonPlannerEntryStudentHomework.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID) WHERE gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND NOT role='Student - Left' AND NOT role='Teacher - Left' AND gibbonSchoolYearID=:gibbonSchoolYearID AND (date<'" . date("Y-m-d") . "' OR (date='" . date("Y-m-d") . "' AND timeEnd<='" . date("H:i:s") . "')) $filter)
								ORDER BY date DESC, timeStart DESC" ; 
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
										print"<table class='blank' cellspacing='0' style='float: right; width: 250px; margin: 0px 0px'>" ;	
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
													print"<input type='submit' value='" . _('Go') . "' style='margin-right: 0px'>" ;
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
											print _("Class") . "</br>" ;
											print "<span style='font-size: 85%; font-style: italic'>" . _('Date') . "</span>" ;
										print "</th>" ;
										print "<th>" ;
											print _("Lesson") . "</br>" ;
											print "<span style='font-size: 85%; font-style: italic'>" . _('Unit') . "</span>" ;
										print "</th>" ;
										print "<th style='min-width: 25%'>" ;
											print _("Type") . "<br/>" ;
											print "<span style='font-size: 85%; font-style: italic'>" . _('Details') . "</span>" ;
										print "</th>" ;
										print "<th>" ;
											print _("Deadline") ;
										print "</th>" ;
										print "<th>" ;
											print _("Online Submission") ;
										print "</th>" ;
										print "<th>" ;
											print _("Actions") ;
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
													print "<span style='font-size: 85%; font-style: italic'>" . dateConvertBack($guid, $row["date"]) . "</span>" ;
												print "</td>" ;
												print "<td>" ;
													print "<b>" . $row["name"] . "</b><br/>" ;
													print "<span style='font-size: 85%; font-style: italic'>" ;
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
													print "</span>" ;
												print "</td>" ;
												print "<td>" ;
													if ($row["type"]=="teacherRecorded") {
														print "Teacher Recorded" ;
													}
													else {
														print "Student Recorded" ;
													}
													print  "<br/>" ;
													print "<span style='font-size: 85%; font-style: italic'>" ;
														if ($row["homeworkDetails"]!="") {
															if (strlen(strip_tags($row["homeworkDetails"]))<21) {
																print strip_tags($row["homeworkDetails"]) ;
															}
															else {
																print "<span $style title='" . htmlPrep(strip_tags($row["homeworkDetails"])) . "'>" . substr(strip_tags($row["homeworkDetails"]), 0, 20) . "...</span>" ;
															}
														}
													print "</span>" ;
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
																	print "<span title='" . _('Pending') . "'>" . _('Pending') . "</span>" ;
																}
																//After
																else {
																	if (@$row["dateStart"]>@$rowSub["date"]) {
																		print "<span title='" . _('Student joined school after assessment was given.') . "' style='color: #000; font-weight: normal; border: 2px none #ff0000; padding: 2px 4px'>" . _('NA') . "</span>" ;
																	}
																	else {
																		if ($row["homeworkSubmissionRequired"]=="Compulsory") {
																			print "<div style='color: #ff0000; font-weight: bold; border: 2px solid #ff0000; padding: 2px 4px; margin: 2px 0px'>" . _('Incomplete') . "</div>" ;
																		}
																		else {
																			print _("Not submitted online") ;
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
													print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Planner/planner_view_full.php&search=$gibbonPersonID&gibbonPlannerEntryID=" . $row["gibbonPlannerEntryID"] . "&viewBy=class&gibbonCourseClassID=" . $row["gibbonCourseClassID"] . "&width=1000&height=550'><img title='" . _('View Details') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/plus.png'/></a> " ;
												print "</td>" ;
											print "</tr>" ;
										}
								}
								print "</table>" ;
							}
						}								
					}
					else if ($subpage=="Behaviour") {
						if (isActionAccessible($guid, $connection2, "/modules/Behaviour/behaviour_view.php")==FALSE) {
							print "<div class='error'>" ;
								print _("Your request failed because you do not have access to this action.");
							print "</div>" ;
						}
						else {
							include "./modules/Behaviour/moduleFunctions.php" ;
							
							//Print assessments
							getBehaviourRecord($guid, $gibbonPersonID, $connection2) ;
						}
					}
					
					//GET HOOK IF SPECIFIED
					if ($hook!="" AND $module!="" AND $action!="") {
						//GET HOOKS AND DISPLAY LINKS
						//Check for hook
						try {
							$dataHook=array("gibbonHookID"=>$_GET["gibbonHookID"]); 
							$sqlHook="SELECT * FROM gibbonHook WHERE gibbonHookID=:gibbonHookID" ;
							$resultHook=$connection2->prepare($sqlHook);
							$resultHook->execute($dataHook);
						}
						catch(PDOException $e) { 
							print "<div class='error'>" . $e->getMessage() . "</div>" ; 
						}
						if ($resultHook->rowCount()!=1) {
							print "<div class='error'>" ;
								print _("There are no records to display.");
							print "</div>" ;
						}
						else {
							$rowHook=$resultHook->fetch() ;
							$options=unserialize($rowHook["options"]) ;
							
							//Check for permission to hook
							try {
								$dataHook=array("gibbonRoleIDCurrent"=>$_SESSION[$guid]["gibbonRoleIDCurrent"], "sourceModuleName"=>$options["sourceModuleName"]); 
								$sqlHook="SELECT gibbonHook.name, gibbonModule.name AS module, gibbonAction.name AS action FROM gibbonHook JOIN gibbonModule ON (gibbonModule.name='" . $options["sourceModuleName"] . "') JOIN gibbonAction ON (gibbonAction.name='" . $options["sourceModuleAction"] . "') JOIN gibbonPermission ON (gibbonPermission.gibbonActionID=gibbonAction.gibbonActionID) WHERE gibbonAction.gibbonModuleID=(SELECT gibbonModuleID FROM gibbonModule WHERE gibbonPermission.gibbonRoleID=:gibbonRoleIDCurrent AND name=:sourceModuleName) AND gibbonHook.type='Student Profile' ORDER BY name" ;
								$resultHook=$connection2->prepare($sqlHook);
								$resultHook->execute($dataHook);
							}
							catch(PDOException $e) { 
								print "<div class='error'>" . $e->getMessage() . "</div>" ; 
							}
							if ($resultHook->rowcount()!=1) {
								print "<div class='error'>" ;
									print _("Your request failed because you do not have access to this action.");
								print "</div>" ;
							}
							else {
								$include=$_SESSION[$guid]["absolutePath"] . "/modules/" . $options["sourceModuleName"] . "/" . $options["sourceModuleInclude"] ;
								if (!file_exists($include)) {
									print "<div class='error'>" ;
										print _("The selected page cannot be displayed due to a hook error.") ;
									print "</div>" ;
								}
								else {
									include $include ;
								}
							}
						}
					}
					
					//Set sidebar
					$_SESSION[$guid]["sidebarExtra"]="" ;
					
					//Show alerts
					$alert=getAlertBar($guid, $connection2, $gibbonPersonID, $row["privacy"], "", FALSE) ;
					$_SESSION[$guid]["sidebarExtra"].="<div style='border-top: 1px solid #c00; background-color: none; font-size: 12px; margin: 3px 0 0px 0; width: 240px; text-align: left; height: 16px; padding: 5px 5px;'>" ;
					if ($alert=="") {
						$_SESSION[$guid]["sidebarExtra"].="<b>" . _('No Current Alerts') . "</b>" ; 
					}
					else {
						$_SESSION[$guid]["sidebarExtra"].="<b>" . _('Current Alerts:') . "</b>$alert" ; 
					}
					$_SESSION[$guid]["sidebarExtra"].="</div>" ;

					$_SESSION[$guid]["sidebarExtra"].=getUserPhoto($guid, $row["image_240"], 240) ;
					
					
				
					//PERSONAL DATA MENU ITEMS
					$_SESSION[$guid]["sidebarExtra"].="<h4>" . _('Personal') . "</h4>" ;
					$_SESSION[$guid]["sidebarExtra"].="<ul class='moduleMenu'>" ;
					$style="" ;
					if ($subpage=="Overview") {
						$style="style='font-weight: bold'" ;
					}
					$_SESSION[$guid]["sidebarExtra"].="<li><a $style href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=" . $_GET["q"] . "&gibbonPersonID=$gibbonPersonID&search=" . $search . "&search=$search&allStudents=$allStudents&subpage=Overview'>" . _('Overview') . "</a></li>" ;
					$style="" ;
					if ($subpage=="Personal") {
						$style="style='font-weight: bold'" ;
					}
					$_SESSION[$guid]["sidebarExtra"].="<li><a $style href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=" . $_GET["q"] . "&gibbonPersonID=$gibbonPersonID&search=" . $search . "&search=$search&allStudents=$allStudents&subpage=Personal'>" . _('Personal') . "</a></li>" ;
					$style="" ;
					if ($subpage=="Family") {
						$style="style='font-weight: bold'" ;
					}
					$_SESSION[$guid]["sidebarExtra"].="<li><a $style href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=" . $_GET["q"] . "&gibbonPersonID=$gibbonPersonID&search=" . $search . "&search=$search&allStudents=$allStudents&subpage=Family'>" . _('Family') . "</a></li>" ;
					$style="" ;
					if ($subpage=="Emergency Contacts") {
						$style="style='font-weight: bold'" ;
					}
					$_SESSION[$guid]["sidebarExtra"].="<li><a $style href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=" . $_GET["q"] . "&gibbonPersonID=$gibbonPersonID&search=" . $search . "&search=$search&allStudents=$allStudents&subpage=Emergency Contacts'>" . _('Emergency Contacts') . "</a></li>" ;
					$style="" ;
					if ($subpage=="Medical") {
						$style="style='font-weight: bold'" ;
					}
					$_SESSION[$guid]["sidebarExtra"].="<li><a $style href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=" . $_GET["q"] . "&gibbonPersonID=$gibbonPersonID&search=" . $search . "&search=$search&allStudents=$allStudents&subpage=Medical'>" . _('Medical') . "</a></li>" ;
					if (isActionAccessible($guid, $connection2, "/modules/Students/student_view_details_notes_add.php")) {
						if ($enableStudentNotes=="Y") {
							$style="" ;
							if ($subpage=="Notes") {
								$style="style='font-weight: bold'" ;
							}
							$_SESSION[$guid]["sidebarExtra"].="<li><a $style href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=" . $_GET["q"] . "&gibbonPersonID=$gibbonPersonID&search=" . $search . "&search=$search&allStudents=$allStudents&subpage=Notes'>" . _('Notes') . "</a></li>" ;
						}
					}
					$_SESSION[$guid]["sidebarExtra"].="</ul>" ;
					
					//OTHER MENU ITEMS, DYANMICALLY ARRANGED TO MATCH CUSTOM TOP MENU
					//Get all modules, with the categories
					try {
						$dataMenu=array(); 
						$sqlMenu="SELECT gibbonModuleID, category, name FROM gibbonModule WHERE active='Y' ORDER BY category, name" ;
						$resultMenu=$connection2->prepare($sqlMenu);
						$resultMenu->execute($dataMenu);
					}
					catch(PDOException $e) { 
						print "<div class='error'>" . $e->getMessage() . "</div>" ; 
					}
					$mainMenu=array() ;
					while ($rowMenu=$resultMenu->fetch()) {
						$mainMenu[$rowMenu["name"]]=$rowMenu["category"] ;
					}
					$studentMenuCateogry=array() ;
					$studentMenuName=array() ;
					$studentMenuLink=array() ;
					$studentMenuCount=0 ;
					
					//Store items in an array
					if (isActionAccessible($guid, $connection2, "/modules/Markbook/markbook_view.php")) {
						$style="" ;
						if ($subpage=="Markbook") {
							$style="style='font-weight: bold'" ;
						}
						$studentMenuCategory[$studentMenuCount]=$mainMenu["Markbook"] ;
						$studentMenuName[$studentMenuCount]=_('Markbook') ;
						$studentMenuLink[$studentMenuCount]="<li><a $style href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=" . $_GET["q"] . "&gibbonPersonID=$gibbonPersonID&search=" . $search . "&search=$search&allStudents=$allStudents&subpage=Markbook'>" . _('Markbook') . "</a></li>" ;
						$studentMenuCount++ ;
					}
					if (isActionAccessible($guid, $connection2, "/modules/External Assessment/externalAssessment_details.php")) {
						$style="" ;
						if ($subpage=="External Assessment") {
							$style="style='font-weight: bold'" ;
						}
						$studentMenuCategory[$studentMenuCount]=$mainMenu["External Assessment"] ;
						$studentMenuName[$studentMenuCount]=_('External Assessment') ;
						$studentMenuLink[$studentMenuCount]="<li><a $style href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=" . $_GET["q"] . "&gibbonPersonID=$gibbonPersonID&search=" . $search . "&search=$search&allStudents=$allStudents&subpage=External Assessment'>" . _('External Assessment') . "</a></li>" ;
						$studentMenuCount++ ;
					}
				
					if (isActionAccessible($guid, $connection2, "/modules/Activities/report_activityChoices_byStudent.php")) {
						$style="" ;
						if ($subpage=="Activities") {
							$style="style='font-weight: bold'" ;
						}
						$studentMenuCategory[$studentMenuCount]=$mainMenu["Activities"] ;
						$studentMenuName[$studentMenuCount]=_('Activities') ;
						$studentMenuLink[$studentMenuCount]="<li><a $style href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=" . $_GET["q"] . "&gibbonPersonID=$gibbonPersonID&search=" . $search . "&search=$search&allStudents=$allStudents&subpage=Activities'>" . _('Activities') . "</a></li>" ;
						$studentMenuCount++ ;
					}
					if (isActionAccessible($guid, $connection2, "/modules/Planner/planner_edit.php") OR isActionAccessible($guid, $connection2, "/modules/Planner/planner_view_full.php")) {
						$style="" ;
						if ($subpage=="Homework") {
							$style="style='font-weight: bold'" ;
						}
						$studentMenuCategory[$studentMenuCount]=$mainMenu["Planner"] ;
						$studentMenuName[$studentMenuCount]=_('Homework') ;
						$studentMenuLink[$studentMenuCount]="<li><a $style href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=" . $_GET["q"] . "&gibbonPersonID=$gibbonPersonID&search=" . $search . "&search=$search&allStudents=$allStudents&subpage=Homework'>" . _('Homework') . "</a></li>" ;
						$studentMenuCount++ ;
					}
					if (isActionAccessible($guid, $connection2, "/modules/Individual Needs/in_view.php")) {
						$style="" ;
						if ($subpage=="Individual Needs") {
							$style="style='font-weight: bold'" ;
						}
						$studentMenuCategory[$studentMenuCount]=$mainMenu["Individual Needs"] ;
						$studentMenuName[$studentMenuCount]=_('Individual Needs') ;
						$studentMenuLink[$studentMenuCount]="<li><a $style href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=" . $_GET["q"] . "&gibbonPersonID=$gibbonPersonID&search=" . $search . "&search=$search&allStudents=$allStudents&subpage=Individual Needs'>" . _('Individual Needs') . "</a></li>" ;
						$studentMenuCount++ ;
					}
					if (isActionAccessible($guid, $connection2, "/modules/Library/report_studentBorrowingRecord.php")) {
						$style="" ;
						if ($subpage=="Library Borrowing") {
							$style="style='font-weight: bold'" ;
						}
						$studentMenuCategory[$studentMenuCount]=$mainMenu["Library"] ;
						$studentMenuName[$studentMenuCount]=_('Library Borrowing') ;
						$studentMenuLink[$studentMenuCount]="<li><a $style href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=" . $_GET["q"] . "&gibbonPersonID=$gibbonPersonID&search=" . $search . "&search=$search&allStudents=$allStudents&subpage=Library Borrowing'>" . _('Library Borrowing') . "</a></li>" ;
						$studentMenuCount++ ;
					}
					if (isActionAccessible($guid, $connection2, "/modules/Timetable/tt_view.php")) {
						$style="" ;
						if ($subpage=="Timetable") {
							$style="style='font-weight: bold'" ;
						}
						$studentMenuCategory[$studentMenuCount]=$mainMenu["Timetable"] ;
						$studentMenuName[$studentMenuCount]=_('Timetable') ;
						$studentMenuLink[$studentMenuCount]="<li><a $style href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=" . $_GET["q"] . "&gibbonPersonID=$gibbonPersonID&search=" . $search . "&search=$search&allStudents=$allStudents&subpage=Timetable'>" . _('Timetable') . "</a></li>" ;
						$studentMenuCount++ ;
					}
					if (isActionAccessible($guid, $connection2, "/modules/Behaviour/behaviour_view.php")) {
						$style="" ;
						if ($subpage=="Behaviour") {
							$style="style='font-weight: bold'" ;
						}
						$studentMenuCategory[$studentMenuCount]=$mainMenu["Behaviour"] ;
						$studentMenuName[$studentMenuCount]=_('Behaviour') ;
						$studentMenuLink[$studentMenuCount]="<li><a $style href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=" . $_GET["q"] . "&gibbonPersonID=$gibbonPersonID&search=" . $search . "&search=$search&allStudents=$allStudents&subpage=Behaviour'>" . _('Behaviour') . "</a></li>" ;
						$studentMenuCount++ ;
					}
					if (isActionAccessible($guid, $connection2, "/modules/Attendance/report_studentHistory.php")) {
						$style="" ;
						if ($subpage=="School Attendance") {
							$style="style='font-weight: bold'" ;
						}
						$studentMenuCategory[$studentMenuCount]=$mainMenu["Attendance"] ;
						$studentMenuName[$studentMenuCount]=_('School Attendance') ;
						$studentMenuLink[$studentMenuCount]="<li><a $style href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=" . $_GET["q"] . "&gibbonPersonID=$gibbonPersonID&search=" . $search . "&search=$search&allStudents=$allStudents&subpage=School Attendance'>" . _('School Attendance') . "</a></li>" ;
						$studentMenuCount++ ;
					}
					
					//Check for hooks, and slot them into array
					try {
						$dataHooks=array(); 
						$sqlHooks="SELECT *FROM gibbonHook WHERE type='Student Profile'" ;
						$resultHooks=$connection2->prepare($sqlHooks);
						$resultHooks->execute($dataHooks);
					}
					catch(PDOException $e) { 
						print "<div class='error'>" . $e->getMessage() . "</div>" ; 
					}
					if ($resultHooks->rowCount()>0) {
						$hooks=array() ;
						$count=0 ;
						while ($rowHooks=$resultHooks->fetch()) {
							$options=unserialize($rowHooks["options"]) ;
							//Check for permission to hook
							try {
								$dataHook=array("gibbonRoleIDCurrent"=>$_SESSION[$guid]["gibbonRoleIDCurrent"], "sourceModuleName"=>$options["sourceModuleName"]); 
								$sqlHook="SELECT gibbonHook.name, gibbonModule.name AS module, gibbonAction.name AS action FROM gibbonHook JOIN gibbonModule ON (gibbonModule.name='" . $options["sourceModuleName"] . "') JOIN gibbonAction ON (gibbonAction.name='" . $options["sourceModuleAction"] . "') JOIN gibbonPermission ON (gibbonPermission.gibbonActionID=gibbonAction.gibbonActionID) WHERE gibbonAction.gibbonModuleID=(SELECT gibbonModuleID FROM gibbonModule WHERE gibbonPermission.gibbonRoleID=:gibbonRoleIDCurrent AND name=:sourceModuleName) AND gibbonHook.type='Student Profile' ORDER BY name" ;
								$resultHook=$connection2->prepare($sqlHook);
								$resultHook->execute($dataHook);
							}
							catch(PDOException $e) { 
								print "<div class='error'>" . $e->getMessage() . "</div>" ; 
							}
							if ($resultHook->rowCount()==1) {
								$style="" ;
								if ($hook==$rowHooks["name"] AND $_GET["module"]==$options["sourceModuleName"]) {
									$style="style='font-weight: bold'" ;
								}
								$studentMenuCategory[$studentMenuCount]=$mainMenu[$options["sourceModuleName"]] ;
								$studentMenuName[$studentMenuCount]=_($rowHooks["name"]) ;
								$studentMenuLink[$studentMenuCount]="<li><a $style href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=" . $_GET["q"] . "&gibbonPersonID=$gibbonPersonID&search=" . $search . "&hook=" . $rowHooks["name"] . "&module=" . $options["sourceModuleName"] . "&action=" . $options["sourceModuleAction"] . "&gibbonHookID=" . $rowHooks["gibbonHookID"] . "'>" . _($rowHooks["name"]) . "</a></li>" ;
								$studentMenuCount++ ;
								$count++ ;
							}
						}
					}
					
					//Sort array
					array_multisort($studentMenuCategory, $studentMenuName, $studentMenuLink);
					
					//Spit out array
					if (count($studentMenuCategory)>0) {
						$categoryCurrent="" ;
						$categoryLast="" ;
						for ($i=0; $i<count($studentMenuCategory); $i++) {
							$categoryCurrent=_($studentMenuCategory[$i]) ;
						
							if ($categoryCurrent!=$categoryLast AND $categoryLast!="") {
								$_SESSION[$guid]["sidebarExtra"].="</ul>" ;
							}
							if ($categoryCurrent!=$categoryLast) {
								$_SESSION[$guid]["sidebarExtra"].="<h4>" . _($studentMenuCategory[$i]) . "</h4>" ;
								$_SESSION[$guid]["sidebarExtra"].="<ul class='moduleMenu'>" ;
							}
						
							$_SESSION[$guid]["sidebarExtra"].=$studentMenuLink[$i] ;
						
							$categoryLast=_($studentMenuCategory[$i]) ;
						}
						$_SESSION[$guid]["sidebarExtra"].="</ul>" ;
					}
				}
			}
		}
	}
}
?>
