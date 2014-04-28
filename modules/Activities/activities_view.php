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

//Module includes
include "./modules/" . $_SESSION[$guid]["module"] . "/moduleFunctions.php" ;

if (isActionAccessible($guid, $connection2, "/modules/Activities/activities_view.php")==FALSE) {
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
		print "<div class='trail'>" ;
		print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > </div><div class='trailEnd'>" . _('View Activities') . "</div>" ;
		print "</div>" ;
		
		if (isset($_GET["updateReturn"])) { $updateReturn=$_GET["updateReturn"] ; } else { $updateReturn="" ; }
		$updateReturnMessage="" ;
		$class="error" ;
		if (!($updateReturn=="")) {
			if ($updateReturn=="success0") {
				$updateReturnMessage=_("Registration was successful.") ;	
				$class="success" ;
			}
			if ($updateReturn=="success1") {
				$updateReturnMessage=_("Unregistration was successful.") ;	
				$class="success" ;
			}
			print "<div class='$class'>" ;
				print $updateReturnMessage;
			print "</div>" ;
		} 
		
		//Get current role category
		$roleCategory=getRoleCategory($_SESSION[$guid]["gibbonRoleIDCurrent"], $connection2) ;
			
		//Check access controls
		$access=getSettingByScope($connection2, "Activities", "access") ;
		$hideExternalProviderCost=getSettingByScope( $connection2, "Activities", "hideExternalProviderCost" ) ;
		
		if (!($access=="View" OR $access=="Register")) {
			print "<div class='error'>" ;
				print _("Activity listing is currently closed.") ;
			print "</div>" ;
		}
		else {
			if ($access=="View") {
				print "<div class='warning'>" ;
					print _("Registration is currently closed, but you can still view activities.") ;
				print "</div>" ;
			}
			
			$disableExternalProviderSignup=getSettingByScope( $connection2, "Activities", "disableExternalProviderSignup" ) ;
			if ($disableExternalProviderSignup=="Y") {
				print "<div class='warning'>" ;
					print _("Registration for activities offered by outside providers is disabled. Check activity details for instructions on how to register for such acitvities.") ;
				print "</div>" ;
			}
			
			//If student, set gibbonPersonID to self
			if ($roleCategory=="Student" AND $highestAction=="View Activities_studentRegister") {
				$gibbonPersonID=$_SESSION[$guid]["gibbonPersonID"] ;
			}
			//IF PARENT, SET UP LIST OF CHILDREN
			$countChild=0 ;
			if ($roleCategory=="Parent" AND $highestAction=="View Activities_studentRegisterByParent") {
				$gibbonPersonID=NULL ;
				if (isset($_GET["gibbonPersonID"])) {
					$gibbonPersonID=$_GET["gibbonPersonID"] ;
				}
				try {
					$data=array("gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
					$sql="SELECT * FROM gibbonFamilyAdult WHERE gibbonPersonID=:gibbonPersonID AND childDataAccess='Y'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
	
				if ($result->rowCount()<1) {
					print "<div class='error'>" ;
					print _("Access denied.") ;
					print "</div>" ;
				}
				else {
					$options="" ;
					while ($row=$result->fetch()) {
						try {
							$dataChild=array("gibbonFamilyID"=>$row["gibbonFamilyID"], "gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
							$sqlChild="SELECT * FROM gibbonFamilyChild JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE gibbonFamilyID=:gibbonFamilyID AND gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY surname, preferredName " ;
							$resultChild=$connection2->prepare($sqlChild);
							$resultChild->execute($dataChild);
						}
						catch(PDOException $e) { 
							print "<div class='error'>" . $e->getMessage() . "</div>" ; 
						}
						if ($resultChild->rowCount()==1) {
							$rowChild=$resultChild->fetch() ;
							$gibbonPersonID=$rowChild["gibbonPersonID"] ;
							$select="" ;
							if ($rowChild["gibbonPersonID"]==$gibbonPersonID) {
								$select="selected" ;
							}
							$options=$options . "<option $select value='" . $rowChild["gibbonPersonID"] . "'>" . formatName("", $rowChild["preferredName"], $rowChild["surname"], "Student", true). "</option>" ;
							$countChild++ ;
						}
						else {
							while ($rowChild=$resultChild->fetch()) {
								$select="" ;
								if ($rowChild["gibbonPersonID"]==$gibbonPersonID) {
									$select="selected" ;
								}
								$options=$options . "<option $select value='" . $rowChild["gibbonPersonID"] . "'>" . formatName("", $rowChild["preferredName"], $rowChild["surname"], "Student", true) . "</option>" ;
								$countChild++ ;
							}
						}
					}
					
					if ($countChild==0) {
						print "<div class='error'>" ;
						print _("There are no records to display.") ;
						print "</div>" ;
					}
				}
			}
			
			print "<h2>" ;
			print _("Filter & Search") ;
			print "</h2>" ;
			
			$search=NULL ;
			if (isset($_GET["search"])) {
				$search=$_GET["search"] ;
			}
			?>
			<form method="get" action="<?php print $_SESSION[$guid]["absoluteURL"]?>/index.php">
				<table class='noIntBorder' cellspacing='0' style="width: 100%">
					<tr><td style="width: 30%"></td><td></td></tr>
					<?php
					if ($countChild>0 AND $roleCategory=="Parent" AND $highestAction=="View Activities_studentRegisterByParent") {
						?>
						<tr>
							<td> 
								<b><?php print _('Child') ?></b><br/>
								<span style="font-size: 90%"><i><?php print _('Choose the child you are registering for.') ?></i></span>
							</td>
							<td class="right">
								<select name="gibbonPersonID" id="gibbonPersonID" style="width: 302px">
									<?php 
									if ($countChild>1) { 
										print "<option value=''></value>" ; 
									}
									print $options ; 
									?> 
								</select>
							</td>
						</tr>
						<?php
					}
					?>
					
					<tr>
						<td> 
							<b><?php print _('Search For Activity') ?></b><br/>
							<span style="font-size: 90%"><i><?php print _('Activity name.') ?></i></span>
						</td>
						<td class="right">
							<input name="search" id="search" maxlength=20 value="<?php print $search ?>" type="text" style="width: 300px">
						</td>
					</tr>
					<tr>
						<td colspan=2 class="right">
							<input type="hidden" name="q" value="/modules/<?php print $_SESSION[$guid]["module"] ?>/activities_view.php">
							<input type="hidden" name="address" value="<?php print $_SESSION[$guid]["address"] ?>">
							<?php
							print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/activities_view.php'>" . _('Clear Search') . "</a>" ;
							?>
							<input type="submit" value="<?php print _("Submit") ; ?>">
						</td>
					</tr>
				</table>
			</form>
			<?php
			
			print "<h2>" ;
			print _("Activities") ;
			print "</h2>" ;
			
			//Set pagination variable
			$page=1 ; if (isset($_GET["page"])) { $page=$_GET["page"] ; }
			if ((!is_numeric($page)) OR $page<1) {
				$page=1 ;
			}
			
			$today=date("Y-m-d") ;
			
			//Set special where params for different roles and permissions
			$continue=TRUE ;
			$and="" ;
			if ($roleCategory=="Student" AND $highestAction=="View Activities_studentRegister") {
				$continue=FALSE ;
				try {
					$dataStudent=array("gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"], "gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
					$sqlStudent="SELECT * FROM gibbonStudentEnrolment WHERE gibbonPersonID=:gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID" ;
					$resultStudent=$connection2->prepare($sqlStudent);
					$resultStudent->execute($dataStudent);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}

				if ($resultStudent->rowCount()==1) {
					$rowStudent=$resultStudent->fetch() ;
					$gibbonYearGroupID=$rowStudent["gibbonYearGroupID"] ;
					if ($gibbonYearGroupID!="") {
						$continue=TRUE ;
						$and=" AND gibbonYearGroupIDList LIKE '%$gibbonYearGroupID%'" ;
					}
				}
			}
			if ($roleCategory=="Parent" AND $highestAction=="View Activities_studentRegisterByParent" AND $gibbonPersonID!="" AND $countChild>0) {
				$continue=FALSE ;
				
				//Confirm access to this student
				try {
					$dataChild=array(); 
					$sqlChild="SELECT * FROM gibbonFamilyChild JOIN gibbonFamily ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonFamilyChild.gibbonPersonID=$gibbonPersonID AND gibbonFamilyAdult.gibbonPersonID=" . $_SESSION[$guid]["gibbonPersonID"] . " AND childDataAccess='Y'" ;
					$resultChild=$connection2->prepare($sqlChild);
					$resultChild->execute($dataChild);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				if ($resultChild->rowCount()==1) {
					try {
						$dataStudent=array("gibbonPersonID"=>$gibbonPersonID, "gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
						$sqlStudent="SELECT * FROM gibbonStudentEnrolment WHERE gibbonPersonID=:gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID" ;
						$resultStudent=$connection2->prepare($sqlStudent);
						$resultStudent->execute($dataStudent);
					}
					catch(PDOException $e) { 
						print "<div class='error'>" . $e->getMessage() . "</div>" ; 
					}
	
					if ($resultStudent->rowCount()==1) {
						$rowStudent=$resultStudent->fetch() ;
						$gibbonYearGroupID=$rowStudent["gibbonYearGroupID"] ;
						if ($gibbonYearGroupID!="") {
							$continue=TRUE ;
							$and=" AND gibbonYearGroupIDList LIKE '%$gibbonYearGroupID%'" ;
						}
					}
				}
			}
			
			if ($continue==FALSE) {
				print "<div class='error'>" ;
				print _("Your request failed due to a database error.") ;
				print "</div>" ;
			}
			else {
				//Should we show date as term or date?
				$dateType=getSettingByScope($connection2, 'Activities', 'dateType') ;
				if ($dateType=="Term" ) {
					$maxPerTerm=getSettingByScope($connection2, 'Activities', 'maxPerTerm') ;
				}
	
				try {
					if ($dateType!="Date") {
						$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
						$sql="SELECT * FROM gibbonActivity WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND active='Y' AND NOT gibbonSchoolYearTermIDList='' $and ORDER BY gibbonSchoolYearTermIDList, name" ; 
					}
					else {
						$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "listingStart"=>$today, "listingEnd"=>$today); 
						$sql="SELECT * FROM gibbonActivity WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND active='Y' AND listingStart<=:listingStart AND listingEnd>=:listingEnd $and ORDER BY name" ; 
					}
					if ($search!="") {
						if ($dateType!="Date") {
							$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "search"=>"%$search%"); 
							$sql="SELECT * FROM gibbonActivity WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND active='Y' AND NOT gibbonSchoolYearTermIDList='' AND name LIKE :search $and ORDER BY gibbonSchoolYearTermIDList, name" ; 
						}
						else {
							$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "listingStart"=>$today, "listingEnd"=>$today, "search"=>"%$search%"); 
							$sql="SELECT * FROM gibbonActivity WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND active='Y' AND listingStart<=:listingStart AND listingEnd>=:listingEnd AND name LIKE :search $and ORDER BY name" ; 
						}
					}
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				
				$sqlPage=$sql . " LIMIT " . $_SESSION[$guid]["pagination"] . " OFFSET " . (($page-1)*$_SESSION[$guid]["pagination"]) ; 
			
				if ($result->rowCount()<1) {
					print "<div class='error'>" ;
					print _("There are no records to display.") ;
					print "</div>" ;
				}
				else {
					if ($result->rowCount()>$_SESSION[$guid]["pagination"]) {
						printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]["pagination"], "top", "search=$search") ;
					}
					
					if ($dateType=="Term" AND $maxPerTerm>0 AND (($roleCategory=="Student" AND $highestAction=="View Activities_studentRegister") OR ($roleCategory=="Parent" AND $highestAction=="View Activities_studentRegisterByParent" AND $gibbonPersonID!="" AND $countChild>0))) {
						print "<div class='warning'>" ;
							print _("Remember, each student can register for no more than $maxPerTerm activities per term. Your current registration count by term is:") ;
							$terms=getTerms($connection2, $_SESSION[$guid]["gibbonSchoolYearID"]) ;
							print "<ul>" ;
								for ($i=0; $i<count($terms); $i=$i+2) {
									print "<li>" ;
									print "<b>" . $terms[($i+1)] . ":</b> " ;
									
									try {
										$dataActivityCount=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonPersonID"=>$gibbonPersonID, "gibbonSchoolYearTermIDList"=>"%" . $terms[$i] . "%"); 
										$sqlActivityCount="SELECT * FROM gibbonActivityStudent JOIN gibbonActivity ON (gibbonActivityStudent.gibbonActivityID=gibbonActivity.gibbonActivityID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonID=:gibbonPersonID AND gibbonSchoolYearTermIDList LIKE :gibbonSchoolYearTermIDList AND NOT status='Not Accepted'" ;
										$resultActivityCount=$connection2->prepare($sqlActivityCount);
										$resultActivityCount->execute($dataActivityCount);
									}
									catch(PDOException $e) { 
										print "<div class='error'>" . $e->getMessage() . "</div>" ; 
									}
									
									if ($resultActivityCount->rowCount()>=0) {
										print $resultActivityCount->rowCount() . " activities" ;
									}
									print "</li>" ;
								}
							print "</ul>" ;
						print "</div>" ;
					}
					
					print "<table cellspacing='0' style='width: 100%'>" ;
						print "<tr class='head'>" ;
							print "<th>" ;
								print _("Activity") ;
							print "</th>" ;
							print "<th>" ;
								print _("Provider") ;
							print "</th>" ;
							print "<th>" ;
								print _("Days") ;
							print "</th>" ;
							print "<th>" ;
								print _("Years") ;
							print "</th>" ;
							print "<th>" ;
								if ($dateType!="Date") {
									print _("Term") ;
								}
								else {
									print _("Dates") ;
								}
							print "</th>" ;
							print "<th>" ;
								print _("Cost") ;
							print "</th>" ;
							if (($roleCategory=="Student" AND $highestAction=="View Activities_studentRegister") OR ($roleCategory=="Parent" AND $highestAction=="View Activities_studentRegisterByParent" AND $gibbonPersonID!="" AND $countChild>0)) {
								print "<th>" ;
									print _("Enrolment") ;
								print "</th>" ;
							}
							print "<th>" ;
								print _("Actions") ;
							print "</th>" ;
						print "</tr>" ;
						
						$count=0;
						$rowNum="odd" ;
						try {
							$resultPage=$connection2->prepare($sqlPage);
							$resultPage->execute($data);
						}
						catch(PDOException $e) { 
							print "<div class='error'>" . $e->getMessage() . "</div>" ; 
						}
						while ($row=$resultPage->fetch()) {
							if ($count%2==0) {
								$rowNum="even" ;
							}
							else {
								$rowNum="odd" ;
							}
							
							$rowEnrol=NULL ;
							if (($roleCategory=="Student" AND $highestAction=="View Activities_studentRegister") OR ($roleCategory=="Parent" AND $highestAction=="View Activities_studentRegisterByParent" AND $gibbonPersonID!="" AND $countChild>0)) {
								try {
									$dataEnrol=array("gibbonActivityID"=>$row["gibbonActivityID"], "gibbonPersonID"=>$gibbonPersonID); 
									$sqlEnrol="SELECT * FROM gibbonActivityStudent WHERE gibbonActivityID=:gibbonActivityID AND gibbonPersonID=:gibbonPersonID" ;
									$resultEnrol=$connection2->prepare($sqlEnrol);
									$resultEnrol->execute($dataEnrol);
								}
								catch(PDOException $e) { }
								if ($resultEnrol->rowCount()>0) {
									$rowEnrol=$resultEnrol->fetch() ;
									$rowNum="current" ;
								}
							}
							
							$count++ ;
							
							//COLOR ROW BY STATUS!
							print "<tr class=$rowNum>" ;
								print "<td>" ;
									print $row["name"] . "<br/>" ;
									print "<i>" . trim($row["type"]) . "</i>" ;
								print "</td>" ;
								print "<td>" ;
									if ($row["provider"]=="School") { print $_SESSION[$guid]["organisationNameShort"] ; } else { print "External" ; }
								print "</td>" ;
								print "<td>" ;
									try {
										$dataSlots=array("gibbonActivityID"=>$row["gibbonActivityID"]); 
										$sqlSlots="SELECT DISTINCT nameShort, sequenceNumber FROM gibbonActivitySlot JOIN gibbonDaysOfWeek ON (gibbonActivitySlot.gibbonDaysOfWeekID=gibbonDaysOfWeek.gibbonDaysOfWeekID) WHERE gibbonActivityID=:gibbonActivityID ORDER BY sequenceNumber" ;
										$resultSlots=$connection2->prepare($sqlSlots);
										$resultSlots->execute($dataSlots);
									}
									catch(PDOException $e) { 
										print "<div class='error'>" . $e->getMessage() . "</div>" ; 
									}
								
									$count2=0 ;
									while ($rowSlots=$resultSlots->fetch()) {
										if ($count2>0) {
											print ", " ;
										}
										print $rowSlots["nameShort"] ;
										$count2++ ;
									}
									if ($count2==0) {
										print "<i>" . _('None') . "</i>" ;
									}
								print "</td>" ;
								print "<td>" ;
									print getYearGroupsFromIDList($connection2, $row["gibbonYearGroupIDList"]) ;
								print "</td>" ;
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
									if ($hideExternalProviderCost=="Y" AND $row["provider"]=="External") {
										print "<i>" . _('See activity details') . "</i>" ;
									}
									else {
										if ($row["payment"]==0) {
											print "<i>" . _('None') . "</i>" ;
										}
										else {
											print "$" . $row["payment"] ;
										}
									}
								print "</td>" ;
								if (($roleCategory=="Student" AND $highestAction=="View Activities_studentRegister") OR ($roleCategory=="Parent" AND $highestAction=="View Activities_studentRegisterByParent" AND $gibbonPersonID!="" AND $countChild>0)) {
									print "<td>" ;
										print $rowEnrol["status"] ;
									print "</td>" ;
								}
								print "<td>" ;
									print "<a class='thickbox' href='" . $_SESSION[$guid]["absoluteURL"] . "/fullscreen.php?q=/modules/" . $_SESSION[$guid]["module"] . "/activities_view_full.php&gibbonActivityID=" . $row["gibbonActivityID"] . "&width=1000&height=550'><img title='" . _('View Details') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/plus.png'/></a> " ;
									$signup=TRUE ;
									if ($row["provider"]=="External" AND $disableExternalProviderSignup=="Y") {
										$signup=FALSE ;
									}
									if ($signup) {
										if (($roleCategory=="Student" AND $highestAction=="View Activities_studentRegister") OR ($roleCategory=="Parent" AND $highestAction=="View Activities_studentRegisterByParent" AND $gibbonPersonID!="" AND $countChild>0)) {
											if ($resultEnrol->rowCount()<1) {
												print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/activities_view_register.php&gibbonPersonID=$gibbonPersonID&search=" . $search . "&mode=register&gibbonActivityID=" . $row["gibbonActivityID"] . "'><img title='" . _('Register') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/attendance.gif'/></a> " ;
											}
											else {
												print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/activities_view_register.php&gibbonPersonID=$gibbonPersonID&search=" . $search . "&mode=unregister&gibbonActivityID=" . $row["gibbonActivityID"] . "'><img title='" . _('Unregister') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/garbage.png'/></a> " ;
											}
										}
									}
								print "</td>" ;
							print "</tr>" ;
						}
					print "</table>" ;
					
					if ($result->rowCount()>$_SESSION[$guid]["pagination"]) {
						printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]["pagination"], "bottom", "search=$search") ;
					}
				}
			}
		}
	}
}
?>