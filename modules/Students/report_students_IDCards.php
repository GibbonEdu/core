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

if (isActionAccessible($guid, $connection2, "/modules/Students/report_student_dataUpdaterHistory.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . _(getModuleName($_GET["q"])) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > </div><div class='trailEnd'>" . _('Student ID Cards') . "</div>" ;
	print "</div>" ;
	print "<p>" ;
	print _("This report allows a user to select a range of students and create ID cards for those students.") ;
	print "</p>" ;
	
	print "<h2>" ;
	print "Choose Students" ;
	print "</h2>" ;
	
	?>
	
	<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/report_students_IDCards.php"?>" enctype="multipart/form-data">
		<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
			<tr>
				<td style='width: 275px'> 
					<b><?php print _('Students') ?> *</b><br/>
				</td>
				<td class="right">
					<select name="Members[]" id="Members[]" multiple style="width: 302px; height: 150px">
						<optgroup label='--<?php print _('Students by Roll Group') ?>--'>
							<?php
							try {
								$dataSelect=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
								$sqlSelect="SELECT gibbonPerson.gibbonPersonID, preferredName, surname, gibbonRollGroup.name AS name FROM gibbonPerson, gibbonStudentEnrolment, gibbonRollGroup WHERE gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID AND gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID AND status='FULL' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY name, surname, preferredName" ;
								$resultSelect=$connection2->prepare($sqlSelect);
								$resultSelect->execute($dataSelect);
							}
							catch(PDOException $e) { }
							while ($rowSelect=$resultSelect->fetch()) {
								print "<option value='" . $rowSelect["gibbonPersonID"] . "'>" . htmlPrep($rowSelect["name"]) . " - " . formatName("", htmlPrep($rowSelect["preferredName"]), htmlPrep($rowSelect["surname"]), "Student", true) . "</option>" ;
							}
							?>
						</optgroup>
						<optgroup label='--<?php print _('Students by Name') ?>--'>
							<?php
							try {
								$dataSelect=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
								$sqlSelect="SELECT gibbonPerson.gibbonPersonID, preferredName, surname, gibbonRollGroup.name AS name FROM gibbonPerson, gibbonStudentEnrolment, gibbonRollGroup WHERE gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID AND gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID AND gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY surname, preferredName" ;
								$resultSelect=$connection2->prepare($sqlSelect);
								$resultSelect->execute($dataSelect);
							}
							catch(PDOException $e) { }
							while ($rowSelect=$resultSelect->fetch()) {
								print "<option value='" . $rowSelect["gibbonPersonID"] . "'>" . formatName("", htmlPrep($rowSelect["preferredName"]), htmlPrep($rowSelect["surname"]), "Student", true) . " (" . htmlPrep($rowSelect["name"]) . ")</option>" ;
							}
							?>
						</optgroup>
					</select>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print _('Card Background') ?></b><br/>
					<span style="font-size: 90%"><i><?php print _('.png or .jpg file, 448 x 268px.') ?></i></span>
				</td>
				<td class="right">
					<input type="file" name="file" id="file"><br/><br/>
					<?php
					print getMaxUpload() ;
			
					//Get list of acceptable file extensions
					try {
						$dataExt=array(); 
						$sqlExt="SELECT * FROM gibbonFileExtension" ;
						$resultExt=$connection2->prepare($sqlExt);
						$resultExt->execute($dataExt);
					}
					catch(PDOException $e) { }
					$ext=".png','.jpg','.jpeg" ;
					?>
					<script type="text/javascript">
						var file=new LiveValidation('file');
						file.add( Validate.Inclusion, { within: [<?php print $ext ;?>], failureMessage: "Illegal file type!", partialMatch: true, caseSensitive: false } );
					</script>
				</td>
			</tr>
			<tr>
				<td colspan=2 class="right">
					<input type="submit" value="<?php print _("Submit") ; ?>">
				</td>
			</tr>
		</table>
	</form>
	<?php
	
	$choices=NULL;
	if (isset($_POST["Members"])) {
		$choices=$_POST["Members"] ;
	}
	
	if (count($choices)>0) {
		
		print "<h2>" ;
		print _("Report Data") ;
		print "</h2>" ;
		
		try {
			$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
			$sqlWhere=" AND (" ;
			for ($i=0; $i<count($choices); $i++) {
				$data[$choices[$i]]=$choices[$i] ;
				$sqlWhere=$sqlWhere . "gibbonPerson.gibbonPersonID=:" . $choices[$i] . " OR " ;
			}
			$sqlWhere=substr($sqlWhere,0,-4) ;
			$sqlWhere=$sqlWhere . ")" ;
			$sql="SELECT officialName, image_240, dob, studentID, gibbonPerson.gibbonPersonID, gibbonYearGroup.name AS year, gibbonRollGroup.name AS roll FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonYearGroup ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE status='Full' AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID $sqlWhere ORDER BY surname, preferredName" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
		}
		
		if ($result->rowCount()<1) {
			print "<div class='error'>" ;
				print "There is not data to display in this report" ;
			print "</div>" ;
		}
		else {
			print "<p>" ;
			print _("These cards are designed to be printed to credit-card size, however, they will look bigger on screen. To print in high quality (144dpi) and at true size, save the cards as an image, and print to 50% scale.") ;
			print "</p>" ;
			
			//Get background image
			$bg="" ;
			if ($_FILES['file']["tmp_name"]!="") {
				$time=time() ;
				//Check for folder in uploads based on today's date
				$path=$_SESSION[$guid]["absolutePath"] ; ;
				if (is_dir($path ."/uploads/" . date("Y", $time) . "/" . date("m", $time))==FALSE) {
					mkdir($path ."/uploads/" . date("Y", $time) . "/" . date("m", $time), 0777, TRUE) ;
				}
				$unique=FALSE;
				while ($unique==FALSE) {
					$suffix=randomPassword(16) ;
					$attachment="uploads/" . date("Y", $time) . "/" . date("m", $time) . "/Card BG_$suffix" . strrchr($_FILES["file"]["name"], ".") ;
					if (!(file_exists($path . "/" . $attachment))) {
						$unique=TRUE ;
					}
				}
				if (move_uploaded_file($_FILES["file"]["tmp_name"],$path . "/" . $attachment)) {
					$bg="background: url(\"" . $_SESSION[$guid]["absoluteURL"] . "/$attachment\") repeat left top #fff;" ;
				}
			}

			print "<table class='blank' cellspacing='0' style='width: 100%'>" ;
			
			$count=0;
			$columns=1 ;
			$rowNum="odd" ;
			while ($row=$result->fetch()) {
				if ($count%$columns==0) {
					print "<tr>" ;
				}
				print "<td style='width:" . (100/$columns) . "%; text-align: center; vertical-align: top'>" ;
					print "<div style='width: 488px; height: 308px; border: 1px solid black; $bg'>" ;
						print "<table class='blank' cellspacing='0' style='width 448px; max-width 448px; height: 268px; max-height: 268px; margin: 45px 10px 10px 10px'>" ; 
							print "<tr>" ;
								print "<td style='padding: 0px ; width: 150px; height: 200px; vertical-align: top' rowspan=5>" ;
									if ($row["image_240"]=="" OR file_exists($_SESSION[$guid]["absolutePath"] . "/" . $row["image_240"])==FALSE) {  
										print "<img style='width: 150px; height: 200px' class='user' src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/anonymous_240.jpg'/><br/>" ;
									}
									else {
										print "<img style='width: 150px; height: 200px' class='user' src='" . $_SESSION[$guid]["absoluteURL"] . "/" . $row["image_240"] . "'/><br/>" ;
									}
								print "</td>" ;
								print "<td style='padding: 0px ; width: 18px'></td>" ;
								print "<td style='padding: 15px 0 0 0 ; text-align: left; width: 280px; vertical-align: top; font-size: 22px'>" ;
									print "<div style='padding: 5px; background-color: rgba(255,255,255,0.3); min-height: 200px'>" ;
										print "<div style='font-weight: bold; font-size: 30px'>" .$row["officialName"] . "</div><br/>" ;
										print "<b>" . _('DOB') . "</b>: <span style='float: right'><i>" . dateConvertBack($guid, $row["dob"]) . "</i></span><br/>" ;
										print "<b>" . $_SESSION[$guid]["organisationNameShort"] . " " . _('ID') . "</b>: <span style='float: right'><i>" . $row["studentID"] . "</i></span><br/>" ;
										print "<b>" . _('Year/Roll') . "</b>: <span style='float: right'><i>" . _($row["year"]) . " / " . $row["roll"] . "</i></span><br/>" ;
										print "<b>" . _('School Year') . "</b>: <span style='float: right'><i>" . $_SESSION[$guid]["gibbonSchoolYearName"] . "</i></span><br/>" ;
									print "</div>" ;
								print "</td>" ;
							print "</tr>" ;
						print "</table>" ;
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
?>