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

$enableDescriptors=getSettingByScope($connection2, "Behaviour", "enableDescriptors") ;
$enableLevels=getSettingByScope($connection2, "Behaviour", "enableLevels") ;

if (isActionAccessible($guid, $connection2, "/modules/Behaviour/behaviour_manage.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . __($guid, 'Find Behaviour Patterns') . "</div>" ;
	print "</div>" ;
	
	if (isset($_GET["deleteReturn"])) { $deleteReturn=$_GET["deleteReturn"] ; } else { $deleteReturn="" ; }
	$deleteReturnMessage="" ;
	$class="error" ;
	if (!($deleteReturn=="")) {
		if ($deleteReturn=="success0") {
			$deleteReturnMessage=__($guid, "Your request was completed successfully.") ;		
			$class="success" ;
		}
		print "<div class='$class'>" ;
			print $deleteReturnMessage;
		print "</div>" ;
	} 
	
	$descriptor=NULL ;
	if (isset($_GET["descriptor"])) {
		$descriptor=$_GET["descriptor"] ;
	}
	
	$level=NULL ;
	if (isset($_GET["level"])) {
		$level=$_GET["level"] ;
	}
	
	$fromDate=NULL ;
	if (isset($_GET["fromDate"])) {
		$fromDate=$_GET["fromDate"] ;
	}
	
	$gibbonRollGroupID=NULL ;
	if (isset($_GET["gibbonRollGroupID"])) {
		$gibbonRollGroupID=$_GET["gibbonRollGroupID"] ;
	}
	
	$gibbonYearGroupID=NULL ;
	if (isset($_GET["gibbonYearGroupID"])) {
		$gibbonYearGroupID=$_GET["gibbonYearGroupID"] ;
	}
	
	$minimumCount=NULL ;
	if (isset($_GET["minimumCount"])) {
		$minimumCount=$_GET["minimumCount"] ;
	}
	
	print "<h3>" ;
		print __($guid, "Filter") ;
	print "</h3>" ;
	print "<form method='get' action='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Behaviour/2Fbehaviour_pattern.php'>" ;
		print "<table class='noIntBorder' cellspacing='0' style='width: 100%'>" ;
			if ($enableDescriptors=="Y") {
				?>
				<tr>
					<td> 
						<b><?php print __($guid, 'Descriptor') ?></b><br/>
						<span class="emphasis small"></span>
					</td>
					<td class="right">
						<?php
						try {
							$sqlNegative="SELECT * FROM gibbonSetting WHERE scope='Behaviour' AND name='negativeDescriptors'" ;
							$resultNegative=$connection2->query($sqlNegative);   
						}
						catch(PDOException $e) { }

						if ($resultNegative->rowCount()==1) {
							$rowNegative=$resultNegative->fetch() ;
							$optionsNegative=$rowNegative["value"] ;
							if ($optionsNegative!="") {
								$optionsNegative=explode(",", $optionsNegative) ;
							}
						}
					
						print "<select name='descriptor' id='descriptor' style='width:302px;'>" ;
							print "<option value=''></option>" ;
							for ($i=0; $i<count($optionsNegative); $i++) {
							?>
								<option <?php if ($descriptor==$optionsNegative[$i]) {print "selected ";}?>value="<?php print trim($optionsNegative[$i]) ?>"><?php print trim($optionsNegative[$i]) ?></option>
							<?php
							}
						print "</select>" ;
						?>
					</td>
				</tr>
				<?php
			}
			if ($enableLevels=="Y") {	
				?>
				<tr>
					<td> 
						<b><?php print __($guid, 'Level') ?></b><br/>
						<span class="emphasis small"></span>
					</td>
					<td class="right">
						<?php
						$optionsLevels=getSettingByScope($connection2, "Behaviour", "levels") ;
						if ($optionsLevels!="") {
							$optionsLevels=explode(",", $optionsLevels) ;
						}
					
						print "<select name='level' id='level' style='width:302px'>" ;
							print "<option value=''></option>" ;
							for ($i=0; $i<count($optionsLevels); $i++) {
							?>
								<option <?php if ($level==$optionsLevels[$i]) {print "selected ";}?>value="<?php print trim($optionsLevels[$i]) ?>"><?php print trim($optionsLevels[$i]) ?></option>
							<?php
							}
						print "</select>" ;
						?>
					</td>
				</tr>
				<?php
			}
			?>
			
			<tr>
				<td> 
					<b><?php print __($guid, 'From Date') ?></b><br/>
					<span class="emphasis small"><?php print __($guid, "Format:") . " " ; if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; } ?></span>
				</td>
				<td class="right">
					<input name="fromDate" id="fromDate" maxlength=10 value="<?php if ($fromDate!="") { print $fromDate ; } ?>" type="text" class="standardWidth">
					<script type="text/javascript">
						var fromDate=new LiveValidation('fromDate');
						fromDate.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]["i18n"]["dateFormatRegEx"]=="") {  print "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i" ; } else { print $_SESSION[$guid]["i18n"]["dateFormatRegEx"] ; } ?>, failureMessage: "Use <?php if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?>." } ); 
					</script>
					 <script type="text/javascript">
						$(function() {
							$( "#fromDate" ).datepicker();
						});
					</script>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print __($guid, 'Roll Group') ?></b><br/>
					<span class="emphasis small"></span>
				</td>
				<td class="right">
					<?php
					try {
						$dataPurpose=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
						$sqlPurpose="SELECT * FROM gibbonRollGroup WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY name" ;
						$resultPurpose=$connection2->prepare($sqlPurpose);
						$resultPurpose->execute($dataPurpose);
					}
					catch(PDOException $e) { }
					
					print "<select name='gibbonRollGroupID' id='gibbonRollGroupID' style='width: 302px'>" ;
						print "<option value=''></option>" ;
						while ($rowPurpose=$resultPurpose->fetch()) {
							$selected="" ;
							if ($rowPurpose["gibbonRollGroupID"]==$gibbonRollGroupID) {
								$selected="selected" ;
							}
							print "<option $selected value='" . $rowPurpose["gibbonRollGroupID"] . "'>" . $rowPurpose["name"] . "</option>" ;
						}
					print "</select>" ;
					?>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print __($guid, 'Year Group') ?></b><br/>
					<span class="emphasis small"></span>
				</td>
				<td class="right">
					<?php
					try {
						$dataPurpose=array(); 
						$sqlPurpose="SELECT * FROM gibbonYearGroup ORDER BY sequenceNumber" ;
						$resultPurpose=$connection2->prepare($sqlPurpose);
						$resultPurpose->execute($dataPurpose);
					}
					catch(PDOException $e) { }
					
					print "<select name='gibbonYearGroupID' id='gibbonYearGroupID' style='width: 302px'>" ;
						print "<option value=''></option>" ;
						while ($rowPurpose=$resultPurpose->fetch()) {
							$selected="" ;
							if ($rowPurpose["gibbonYearGroupID"]==$gibbonYearGroupID) {
								$selected="selected" ;
							}
							print "<option $selected value='" . $rowPurpose["gibbonYearGroupID"] . "'>" . __($guid, $rowPurpose["name"]) . "</option>" ;
						}
					print "</select>" ;
					?>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print __($guid, 'Minimum Count') ?></b><br/>
					<span class="emphasis small"></span>
				</td>
				<td class="right">
					<?php
					print "<select name='minimumCount' id='minimumCount' style='width:302px'>" ;
						for ($i=0; $i<51; $i++) {
							if ($i==0 OR $i==1 OR $i==2 OR $i==3 OR $i==4 OR $i==5 OR $i==10 OR $i==25 OR $i==50) {
								?>
								<option <?php if ($minimumCount==$i) {print "selected ";}?>value="<?php print $i ?>"><?php print $i ?></option>
								<?php
							}
						}
					print "</select>" ;
					?>
				</td>
			</tr>
			<?php
			print "<tr>" ;
				print "<td class='right' colspan=2>" ;
					print "<input type='hidden' name='q' value='" . $_GET["q"] . "'>" ;
					print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Behaviour/behaviour_pattern.php'>" . __($guid, 'Clear Filters') . "</a> " ;
					print "<input type='submit' value='" . __($guid, 'Go') . "'>" ;
				print "</td>" ;
			print "</tr>" ;
			?>
		</table>
		<?php
	print "</form>" ;
	
	print "<h3>" ;
		print __($guid, "Behaviour Records") ;
	print "</h3>" ;
	print "<p>" ;
	print __($guid, "The students listed below match the criteria above, for negative behaviour records in the current school year. The count is updated according to the criteria above.") ;
	print "</p>" ;
	
	//Set pagination variable
	$page=1 ; if (isset($_GET["page"])) { $page=$_GET["page"] ; }
	if ((!is_numeric($page)) OR $page<1) {
		$page=1 ;
	}
	
	try {
		$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonSchoolYearID2"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
		$sqlWhere1="AND " ;
		if ($gibbonRollGroupID!="") {
			$data["gibbonRollGroupID"]=$gibbonRollGroupID ;
			$sqlWhere1.="gibbonStudentEnrolment.gibbonRollGroupID=:gibbonRollGroupID AND " ; 
		}
		if ($gibbonYearGroupID!="") {
			$data["gibbonYearGroupID"]=$gibbonYearGroupID ;
			$sqlWhere1.="gibbonStudentEnrolment.gibbonYearGroupID=:gibbonYearGroupID AND " ; 
		}
		if ($sqlWhere1=="AND ") {
			$sqlWhere1="" ;
		}
		else {
			$sqlWhere1=substr($sqlWhere1,0,-5) ;
		}
		
		$sqlWhere2="AND " ;
		if ($descriptor!="") {
			$data["descriptor"]=$descriptor ;
			$sqlWhere2.="gibbonBehaviour.descriptor=:descriptor AND " ; 
		}
		if ($level!="") {
			$data["level"]=$level ;
			$sqlWhere2.="gibbonBehaviour.level=:level AND " ; 
		}
		if ($fromDate!="") {
			$data["fromDate"]=dateConvert($guid, $fromDate) ;
			$sqlWhere2.="gibbonBehaviour.date>=:fromDate AND " ; 
		}
		if ($sqlWhere2=="AND ") {
			$sqlWhere2="" ;
		}
		else {
			$sqlWhere2=substr($sqlWhere2,0,-5) ;
		}
		
		$sqlWhere3="HAVING " ;
		if ($minimumCount!="") {
			$data["minimumCount"]=$minimumCount ;
			$sqlWhere3.="count>=:minimumCount" ; 
		}
		if ($sqlWhere3=="HAVING ") {
			$sqlWhere3="" ;
		}
		
		$sql="SELECT DISTINCT gibbonPerson.gibbonPersonID, gibbonStudentEnrolmentID, surname, preferredName, gibbonYearGroup.nameShort AS yearGroup, gibbonRollGroup.nameShort AS rollGroup, dateStart, dateEnd, (SELECT COUNT(*) FROM gibbonBehaviour WHERE type='Negative' AND gibbonPersonID=gibbonPerson.gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID2 $sqlWhere2) AS count FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonYearGroup ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPerson.status='Full' $sqlWhere1 $sqlWhere3 ORDER BY rollGroup, surname, preferredName" ; 
		$sqlPage=$sql . " LIMIT " . $_SESSION[$guid]["pagination"] . " OFFSET " . (($page-1)*$_SESSION[$guid]["pagination"]) ; 
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { 
		print "<div class='error'>" . $e->getMessage() . "</div>" ; 
	}

	if ($result->rowCount()<1) {
		print "<div class='error'>" ;
		print __($guid, "There are no records to display.") ;
		print "</div>" ;
	}
	else {
		if ($result->rowCount()>$_SESSION[$guid]["pagination"]) {
			printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]["pagination"], "top", "descriptor=$descriptor&level=$level&fromDate=$fromDate&gibbonRollGroupID=$gibbonRollGroupID&gibbonYearGroupID=$gibbonYearGroupID&minimumCount=$minimumCount&source=pattern") ;
		}
	
		print "<table cellspacing='0' style='width: 100%'>" ;
			print "<tr class='head'>" ;
				print "<th>" ;
					print __($guid, "Name") ;
				print "</th>" ;
				print "<th>" ;
					print __($guid, "Negative Count") . "<br/>" ;
					print "<span style='font-size: 75%; font-style: italic'>" . __($guid, '(Current Year Only)') . "</span>" ;
				print "</th>" ;
				print "<th>" ;
					print __($guid, "Year Group") ;
				print "</th>" ;
				print "<th>" ;
					print __($guid, "Roll Group") ;
				print "</th>" ;
				print "<th>" ;
					print __($guid, "Actions") ;
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
				$count++ ;
				
				//Color rows based on start and end date
				if (!($row["dateStart"]=="" OR $row["dateStart"]<=date("Y-m-d")) AND ($row["dateEnd"]=="" OR $row["dateEnd"]>=date("Y-m-d"))) {
					$rowNum="error" ;
				}
				
				//COLOR ROW BY STATUS!
				print "<tr class=$rowNum>" ;
					print "<td>" ;
						print "<a href='index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=" . $row["gibbonPersonID"] . "&subpage=Behaviour&search=&allStudents=&sort=surname, preferredName'>" . formatName("", $row["preferredName"], $row["surname"], "Student", true) . "</a>" ;
					print "</td>" ;
					print "<td>" ;
						print $row["count"] ;
					print "</td>" ;
					print "<td>" ;
						print __($guid, $row["yearGroup"]) ;
					print "</td>" ;
					print "<td>" ;
						print $row["rollGroup"] ;
					print "</td>" ;
					print "<td>" ;
						print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/behaviour_view_details.php&gibbonPersonID=" . $row["gibbonPersonID"] . "&descriptor=$descriptor&level=$level&fromDate=$fromDate&gibbonRollGroupID=$gibbonRollGroupID&gibbonYearGroupID=$gibbonYearGroupID&minimumCount=$minimumCount&source=pattern&search='><img title='" . __($guid, 'View Details') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/plus.png'/></a> " ;
					print "</td>" ;
				print "</tr>" ;
			}
		print "</table>" ;
		
		if ($result->rowCount()>$_SESSION[$guid]["pagination"]) {
			printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]["pagination"], "bottom", "descriptor=$descriptor&level=$level&fromDate=$fromDate&gibbonRollGroupID=$gibbonRollGroupID&gibbonYearGroupID=$gibbonYearGroupID&minimumCount=$minimumCount&source=pattern") ;
		}
		
	}
}	
?>