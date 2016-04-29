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

if (isActionAccessible($guid, $connection2, "/modules/Formal Assessment/internalAssessment_view.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "Your request failed because you do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Get action with highest precendence
	$highestAction=getHighestGroupedAction($guid, $_GET["q"], $connection2) ;
	if ($highestAction==FALSE) {
		print "<div class='error'>" ;
		print __($guid, "The highest grouped action cannot be determined.") ;
		print "</div>" ;
	}
	else {
		if ($highestAction=="View Internal Assessments_all") { //ALL STUDENTS
			print "<div class='trail'>" ;
			print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" .__($guid, 'View All Internal Assessments') . "</div>" ;
			print "</div>" ;
			
			$gibbonPersonID=NULL ;
			if (isset($_GET["gibbonPersonID"])) {
				$gibbonPersonID=$_GET["gibbonPersonID"] ;
			}	
	
			print "<h3>" ;
				print __($guid, "Choose A Student") ;
			print "</h3>" ;
			print "<form method='get' action='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Behaviour/behaviour_manage.php'>" ;
				print "<table class='noIntBorder' cellspacing='0' style='width: 100%'>" ;
					?>
					<tr>
						<td> 
							<b><?php print __($guid, 'Student') ?></b><br/>
							<span class="emphasis small"></span>
						</td>
						<td class="right">
							<select name="gibbonPersonID" id="gibbonPersonID" class="standardWidth">
								<option value=""></option>
								<?php
								try {
									$dataSelect=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
									$sqlSelect="SELECT * FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID AND status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') ORDER BY surname, preferredName" ;
									$resultSelect=$connection2->prepare($sqlSelect);
									$resultSelect->execute($dataSelect);
								}
								catch(PDOException $e) { }
								while ($rowSelect=$resultSelect->fetch()) {
									if ($gibbonPersonID==$rowSelect["gibbonPersonID"]) {
										print "<option selected value='" . $rowSelect["gibbonPersonID"] . "'>" . formatName("", htmlPrep($rowSelect["preferredName"]), htmlPrep($rowSelect["surname"]), "Student", true) . " (" . htmlPrep($rowSelect["nameShort"]) . ")</option>" ;
									}
									else {
										print "<option value='" . $rowSelect["gibbonPersonID"] . "'>" . formatName("", htmlPrep($rowSelect["preferredName"]), htmlPrep($rowSelect["surname"]), "Student", true) . " (" . htmlPrep($rowSelect["nameShort"]) . ")</option>" ;
									}
								}
								?>			
							</select>
						</td>
					</tr>
					
					<?php
					print "<tr>" ;
						print "<td class='right' colspan=2>" ;
							print "<input type='hidden' name='q' value='" . $_GET["q"] . "'>" ;
							print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Formal Assessment/internalAssessment_view.php'>" . __($guid, 'Clear Filters') . "</a> " ;
							print "<input type='submit' value='" . __($guid, 'Go') . "'>" ;
						print "</td>" ;
					print "</tr>" ;
				print "</table>" ;
			print "</form>" ;
			
			if ($gibbonPersonID) {
				print "<h3>" ;
					print __($guid, "Internal Assessments") ;
				print "</h3>" ;
				
				//Check for access
				try {
					$dataCheck=array("gibbonPersonID"=>$gibbonPersonID); 
					$sqlCheck="SELECT DISTINCT gibbonPerson.* FROM gibbonPerson LEFT JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) WHERE gibbonPerson.gibbonPersonID=:gibbonPersonID AND status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "')" ;
					$resultCheck=$connection2->prepare($sqlCheck);
					$resultCheck->execute($dataCheck);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				
				if ($resultCheck->rowCount()!=1) {
					print "<div class='error'>" ;
						print __($guid, "The selected record does not exist, or you do not have access to it.") ;
					print "</div>" ;
				}
				else {
					print getInternalAssessmentRecord($guid, $connection2, $gibbonPersonID) ;
				}
			}			
		}
		else if ($highestAction=="View Internal Assessments_myChildrens") { //MY CHILDREN
			print "<div class='trail'>" ;
			print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" .__($guid, 'View My Childrens\'s Internal Assessments') . "</div>" ;
			print "</div>" ;
			
			//Test data access field for permission
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
				print __($guid, "Access denied.") ;
				print "</div>" ;
			}
			else {
				//Get child list
				$count=0 ;
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
					while ($rowChild=$resultChild->fetch()) {
						$select="" ;
						if (isset($_GET["search"])) {
							if ($rowChild["gibbonPersonID"]==$_GET["search"]) {
								$select="selected" ;
							}
						}
						
						$options=$options . "<option $select value='" . $rowChild["gibbonPersonID"] . "'>" . formatName("", $rowChild["preferredName"], $rowChild["surname"], "Student", true). "</option>" ;
						$gibbonPersonID[$count]=$rowChild["gibbonPersonID"] ;
						$count++ ;
					}
				}
				
				if ($count==0) {
					print "<div class='error'>" ;
					print __($guid, "Access denied.") ;
					print "</div>" ;
				}
				else if ($count==1) {
					$_GET["search"]=$gibbonPersonID[0] ;
				}
				else {
					print "<h2>" ;
					print "Choose Student" ;
					print "</h2>" ;
					
					print "<form method='get' action='" . $_SESSION[$guid]["absoluteURL"] . "/index.php'>" ;
						print "<table class='noIntBorder' cellspacing='0' style='width: 100%'>" ;
							?>
							<tr>
								<td> 
									<b><?php print __($guid, 'Student') ?></b><br/>
								</td>
								<td class="right">
									<select name="search" id="search" class="standardWidth">
										<option value=""></value>
										<?php print $options ; ?> 
									</select>
								</td>
							</tr>
							<tr>
								<td colspan=2 class="right">
									<input type="hidden" name="q" value="/modules/Formal Assessment/internalAssessment_view.php">
									<input type="hidden" name="address" value="<?php print $_SESSION[$guid]["address"] ?>">
									<?php
									print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Formal Assessment/internalAssessment_view.php'>" . __($guid, 'Clear Search') . "</a>" ;
									?>
									<input type="submit" value="<?php print __($guid, "Submit") ; ?>">
								</td>
							</tr>
						</table>
					</form>
					<?php
				}
				
				$gibbonPersonID=NULL ;
				if (isset($_GET["search"])) {
					$gibbonPersonID=$_GET["search"] ;
				}
				$showParentAttainmentWarning=getSettingByScope($connection2, "Markbook", "showParentAttainmentWarning" ) ; 
				$showParentEffortWarning=getSettingByScope($connection2, "Markbook", "showParentEffortWarning" ) ; 
														
				if ($gibbonPersonID!="" AND $count>0) {
					//Confirm access to this student
					try {
						$dataChild=array("gibbonPersonID"=>$gibbonPersonID, "gibbonPersonID2"=>$_SESSION[$guid]["gibbonPersonID"]); 
						$sqlChild="SELECT * FROM gibbonFamilyChild JOIN gibbonFamily ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonFamilyChild.gibbonPersonID=:gibbonPersonID AND gibbonFamilyAdult.gibbonPersonID=:gibbonPersonID2 AND childDataAccess='Y'" ;
						$resultChild=$connection2->prepare($sqlChild);
						$resultChild->execute($dataChild);
					}
					catch(PDOException $e) { 
						print "<div class='error'>" . $e->getMessage() . "</div>" ; 
					}
					if ($resultChild->rowCount()<1) {
						print "<div class='error'>" ;
						print __($guid, "The selected record does not exist, or you do not have access to it.") ;
						print "</div>" ;
					}
					else {
						$rowChild=$resultChild->fetch() ;
						print getInternalAssessmentRecord($guid, $connection2, $gibbonPersonID, "parent") ;
					}
				}
			}
			
		}
		else { //MY Internal Assessments
			print "<div class='trail'>" ;
			print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" .__($guid, 'View My Internal Assessments') . "</div>" ;
			print "</div>" ;
			
			print "<h3>" ;
				print __($guid, "Internal Assessments") ;
			print "</h3>" ;
			
			print getInternalAssessmentRecord($guid, $connection2, $_SESSION[$guid]["gibbonPersonID"], "student") ;
		}
	}
}		
?>