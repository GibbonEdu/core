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

if (isActionAccessible($guid, $connection2, "/modules/System Admin/systemSettings.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Prepare and submit stats if that is what the system calls for
	if ($_SESSION[$guid]["statsCollection"]=="Y") {
		$absolutePathProtocol="" ;
		$absolutePath="" ;
		if (substr($_SESSION[$guid]["absoluteURL"],0,7)=="http://") {
			$absolutePathProtocol="http" ;
			$absolutePath=substr($_SESSION[$guid]["absoluteURL"],7) ;
		}
		else if (substr($_SESSION[$guid]["absoluteURL"],0,8)=="https://") {
			$absolutePathProtocol="https" ;
			$absolutePath=substr($_SESSION[$guid]["absoluteURL"],8) ;
		}
		try {
			$data=array(); 
			$sql="SELECT * FROM gibbonPerson" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { }
		$usersTotal=$result->rowCount() ;
		try {
			$data=array(); 
			$sql="SELECT * FROM gibbonPerson WHERE status='Full'" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { }
		$usersFull=$result->rowCount() ;
		print "<iframe style='display: none; height: 10px; width: 10px' src='https://gibbonedu.org/services/tracker/tracker.php?absolutePathProtocol=" . urlencode($absolutePathProtocol) . "&absolutePath=" . urlencode($absolutePath) . "&organisationName=" . urlencode($_SESSION[$guid]['organisationName']) . "&type=" . urlencode($_SESSION[$guid]['installType']) . "&version=" . urlencode($version) . "&country=" . $_SESSION[$guid]['country'] . "&usersTotal=$usersTotal&usersFull=$usersFull'></iframe>" ;
	}

	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . __($guid, 'System Settings') . "</div>" ;
	print "</div>" ;
	
	//Check for new version of Gibbon
	print getCurrentVersion($guid, $connection2, $version) ;
	
	if (isset($_GET["updateReturn"])) { $updateReturn=$_GET["updateReturn"] ; } else { $updateReturn="" ; }
	$updateReturnMessage="" ;
	$class="error" ;
	if (!($updateReturn=="")) {
		if ($updateReturn=="fail0") {
			$updateReturnMessage=__($guid, "Your request failed because you do not have access to this action.") ;	
		}
		else if ($updateReturn=="fail1") {
			$updateReturnMessage=__($guid, "Your request failed because your inputs were invalid.") ;	
		}
		else if ($updateReturn=="fail2") {
			$updateReturnMessage=__($guid, "One or more of the fields in your request failed due to a database error.") ;	
		}
		else if ($updateReturn=="fail3") {
			$updateReturnMessage=__($guid, "Your request failed because your inputs were invalid.") ;	
		}
		else if ($updateReturn=="success0") {
			$updateReturnMessage=__($guid, "Your request was completed successfully.") ;	
			$class="success" ;
		}
		print "<div class='$class'>" ;
			print $updateReturnMessage;
		print "</div>" ;
	} 
	?>
	
	<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/systemSettingsProcess.php" ?>">
		<table class='smallIntBorder fullWidth' cellspacing='0'>	
			<tr class='break'>
				<td colspan=2> 
					<h3><?php print __($guid, 'System Settings') ?></h3>
				</td>
			</tr>
			<tr>
				<?php
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='System' AND name='absoluteURL'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				$row=$result->fetch() ;
				?>
				<td style='width: 275px'> 
					<b><?php print __($guid, $row["nameDisplay"]) ?> *</b><br/>
					<span class="emphasis small"><?php if ($row["description"]!="") { print __($guid, $row["description"]) ; } ?></span>
				</td>
				<td stclass="right">
					<input name="<?php print $row["name"] ?>" id="<?php print $row["name"] ?>" maxlength=50 value="<?php print htmlPrep($row["value"]) ?>" type="text" class="standardWidth">
					<script type="text/javascript">
						var <?php print $row["name"] ?>=new LiveValidation('<?php print $row["name"] ?>');
						<?php print $row["name"] ?>.add(Validate.Presence);
						<?php print $row["name"] ?>.add( Validate.Format, { pattern: /(http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/, failureMessage: "Must start with http:// or https://" } );
					</script> 
				</td>
			</tr>
			<tr>
				<?php
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='System' AND name='absolutePath'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				$row=$result->fetch() ;
				?>
				<td> 
					<b><?php print __($guid, $row["nameDisplay"]) ?> *</b><br/>
					<span class="emphasis small"><?php if ($row["description"]!="") { print __($guid, $row["description"]) ; } ?></span>
				</td>
				<td stclass="right">
					<input name="<?php print $row["name"] ?>" id="<?php print $row["name"] ?>" maxlength=50 value="<?php print htmlPrep($row["value"]) ?>" type="text" class="standardWidth">
					<script type="text/javascript">
						var <?php print $row["name"] ?>=new LiveValidation('<?php print $row["name"] ?>');
						<?php print $row["name"] ?>.add(Validate.Presence);
					</script> 
				</td>
			</tr>
			<tr>
				<?php
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='System' AND name='systemName'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				$row=$result->fetch() ;
				?>
				<td> 
					<b><?php print __($guid, $row["nameDisplay"]) ?> *</b><br/>
					<span class="emphasis small"><?php if ($row["description"]!="") { print __($guid, $row["description"]) ; } ?></span>
				</td>
				<td class="right">
					<input name="<?php print $row["name"] ?>" id="<?php print $row["name"] ?>" maxlength=50 value="<?php print htmlPrep($row["value"]) ?>" type="text" class="standardWidth">
					<script type="text/javascript">
						var <?php print $row["name"] ?>=new LiveValidation('<?php print $row["name"] ?>');
						<?php print $row["name"] ?>.add(Validate.Presence);
					</script> 
				</td>
			</tr>
			<tr>
				<?php
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='System' AND name='indexText'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				$row=$result->fetch() ;
				?>
				<td> 
					<b><?php print __($guid, $row["nameDisplay"]) ?> *</b><br/>
					<span class="emphasis small"><?php if ($row["description"]!="") { print __($guid, $row["description"]) ; } ?></span>
				</td>
				<td class="right">
					<textarea name="<?php print $row["name"] ?>" id="<?php print $row["name"] ?>" rows=8 class="standardWidth"><?php print htmlPrep($row["value"]) ?></textarea>
					<script type="text/javascript">
						var <?php print $row["name"] ?>=new LiveValidation('<?php print $row["name"] ?>');
						<?php print $row["name"] ?>.add(Validate.Presence);
					</script> 
				</td>
			</tr>
			<tr>
				<?php
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='System' AND name='installType'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				$row=$result->fetch() ;
				?>
				<td> 
					<b><?php print __($guid, $row["nameDisplay"]) ?> *</b><br/>
					<span class="emphasis small"><?php if ($row["description"]!="") { print __($guid, $row["description"]) ; } ?></span>
				</td>
				<td class="right">
					<select name="<?php print $row["name"] ?>" id="<?php print $row["name"] ?>" class="standardWidth">
						<?php
						$selected="" ;
						if ($row["value"]=="Production" ) { $selected="selected" ; }
						print "<option $selected value='Production'>Production</option>" ;
						$selected="" ;
						if ($row["value"]=="Testing" ) { $selected="selected" ; }
						print "<option $selected value='Testing'>Testing</option>" ;
						$selected="" ;
						if ($row["value"]=="Development" ) { $selected="selected" ; }
						print "<option $selected value='Development'>Development</option>" ;
						?>			
					</select>
				</td>
			</tr>
			<tr>
				<?php
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='System' AND name='cuttingEdgeCode'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				$row=$result->fetch() ;
				?>
				<td> 
					<b><?php print __($guid, $row["nameDisplay"]) ?> *</b><br/>
					<span class="emphasis small"><?php print __($guid, $row["description"]) ?>. <?php print __($guid, 'This value cannot be changed.') ?></span>
				</td>
				<td class="right">
					<input readonly value='<?php print ynExpander($guid, $row["value"]) ?>'  class="standardWidth"\>
				</td>
			</tr>
			<tr>
				<?php
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='System' AND name='statsCollection'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				$row=$result->fetch() ;
				?>
				<td> 
					<b><?php print __($guid, $row["nameDisplay"]) ?> *</b><br/>
					<span class="emphasis small"><?php if ($row["description"]!="") { print __($guid, $row["description"]) ; } ?></span>
				</td>
				<td class="right">
					<select name="<?php print $row["name"] ?>" id="<?php print $row["name"] ?>" class="standardWidth">
						<?php
						$selected="" ;
						if ($row["value"]=="Y" ) { $selected="selected" ; }
						print "<option $selected value='Y'>" . ynExpander($guid, 'Y') . "</option>" ;
						$selected="" ;
						if ($row["value"]=="N" ) { $selected="selected" ; }
						print "<option $selected value='N'>" . ynExpander($guid, 'N') . "</option>" ;
						?>			
					</select>
				</td>
			</tr>
		
			<tr class='break'>
				<td colspan=2> 
					<h3><?php print __($guid, 'Organisation Settings') ?></h3>
				</td>
			</tr>
			<tr>
				<?php
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='System' AND name='organisationName'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				$row=$result->fetch() ;
				?>
				<td> 
					<b><?php print __($guid, $row["nameDisplay"]) ?> *</b><br/>
					<span class="emphasis small"><?php if ($row["description"]!="") { print __($guid, $row["description"]) ; } ?></span>
				</td>
				<td class="right">
					<input name="<?php print $row["name"] ?>" id="<?php print $row["name"] ?>" maxlength=50 value="<?php print htmlPrep($row["value"]) ?>" type="text" class="standardWidth">
					<script type="text/javascript">
						var <?php print $row["name"] ?>=new LiveValidation('<?php print $row["name"] ?>');
						<?php print $row["name"] ?>.add(Validate.Presence);
					</script> 
				</td>
			</tr>
			<tr>
				<?php
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='System' AND name='organisationNameShort'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				$row=$result->fetch() ;
				?>
				<td> 
					<b><?php print __($guid, $row["nameDisplay"]) ?> *</b><br/>
					<span class="emphasis small"><?php if ($row["description"]!="") { print __($guid, $row["description"]) ; } ?></span>
				</td>
				<td class="right">
					<input name="<?php print $row["name"] ?>" id="<?php print $row["name"] ?>" maxlength=50 value="<?php print htmlPrep($row["value"]) ?>" type="text" class="standardWidth">
					<script type="text/javascript">
						var <?php print $row["name"] ?>=new LiveValidation('<?php print $row["name"] ?>');
						<?php print $row["name"] ?>.add(Validate.Presence);
					</script> 
				</td>
			</tr>
			<tr>
				<?php
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='System' AND name='organisationEmail'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				$row=$result->fetch() ;
				?>
				<td> 
					<b><?php print __($guid, $row["nameDisplay"]) ?></b><br/>
					<span class="emphasis small"><?php if ($row["description"]!="") { print __($guid, $row["description"]) ; } ?></span>
				</td>
				<td class="right">
					<input name="<?php print $row["name"] ?>" id="<?php print $row["name"] ?>" maxlength=255 value="<?php print htmlPrep($row["value"]) ?>" type="text" class="standardWidth">
					<script type="text/javascript">
						var <?php print $row["name"] ?>=new LiveValidation('<?php print $row["name"] ?>');
						<?php print $row["name"] ?>.add(Validate.Email);
					</script> 
				</td>
			</tr>
			<tr>
				<?php
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='System' AND name='organisationLogo'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				$row=$result->fetch() ;
				?>
				<td> 
					<b><?php print __($guid, $row["nameDisplay"]) ?> *</b><br/>
					<span class="emphasis small"><?php if ($row["description"]!="") { print __($guid, $row["description"]) ; } ?></span>
				</td>
				<td class="right">
					<input name="<?php print $row["name"] ?>" id="<?php print $row["name"] ?>" maxlength=255 value="<?php print htmlPrep($row["value"]) ?>" type="text" class="standardWidth">
					<script type="text/javascript">
						var <?php print $row["name"] ?>=new LiveValidation('<?php print $row["name"] ?>');
						<?php print $row["name"] ?>.add(Validate.Presence);
					</script> 
				</td>
			</tr>
			
			<tr>
				<?php
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='System' AND name='organisationAdministrator'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				$row=$result->fetch() ;
				?>
				<td> 
					<b><?php print __($guid, $row["nameDisplay"]) ?> *</b><br/>
					<span class="emphasis small"><?php if ($row["description"]!="") { print __($guid, $row["description"]) ; } ?></span>
				</td>
				<td class="right">
					<select name="<?php print $row["name"] ?>" id="<?php print $row["name"] ?>" class="standardWidth">
						<?php
						print "<option value='Please select...'>" . __($guid, 'Please select...') . "</option>" ;
						try {
							$dataSelect=array(); 
							$sqlSelect="SELECT gibbonPerson.gibbonPersonID, title, surname, preferredName FROM gibbonPerson JOIN gibbonStaff ON (gibbonPerson.gibbonPersonID=gibbonStaff.gibbonPersonID) WHERE status='Full' ORDER BY surname, preferredName" ;
							$resultSelect=$connection2->prepare($sqlSelect);
							$resultSelect->execute($dataSelect);
						}
						catch(PDOException $e) { }	
						while ($rowSelect=$resultSelect->fetch()) {
							$selected="" ;
							if ($row["value"]==$rowSelect["gibbonPersonID"]) {
								$selected="selected" ;
							}
							print "<option $selected value='" . $rowSelect["gibbonPersonID"] . "'>" . formatName(htmlPrep($rowSelect["title"]), ($rowSelect["preferredName"]), htmlPrep($rowSelect["surname"]),"Staff", true, true) . "</option>" ;
						}
						?>
					</select>
					<script type="text/javascript">
						var <?php print $row["name"] ?>=new LiveValidation('<?php print $row["name"] ?>');
						<?php print $row["name"] ?>.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print __($guid, 'Select something!') ?>"});
					</script>
				</td>
			</tr>
			<tr>
				<?php
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='System' AND name='organisationDBA'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				$row=$result->fetch() ;
				?>
				<td> 
					<b><?php print __($guid, $row["nameDisplay"]) ?> *</b><br/>
					<span class="emphasis small"><?php if ($row["description"]!="") { print __($guid, $row["description"]) ; } ?></span>
				</td>
				<td class="right">
					<select name="<?php print $row["name"] ?>" id="<?php print $row["name"] ?>" class="standardWidth">
						<?php
						print "<option value='Please select...'>" . __($guid, 'Please select...') . "</option>" ;
						try {
							$dataSelect=array(); 
							$sqlSelect="SELECT gibbonPerson.gibbonPersonID, title, surname, preferredName FROM gibbonPerson JOIN gibbonStaff ON (gibbonPerson.gibbonPersonID=gibbonStaff.gibbonPersonID) WHERE status='Full' ORDER BY surname, preferredName" ;
							$resultSelect=$connection2->prepare($sqlSelect);
							$resultSelect->execute($dataSelect);
						}
						catch(PDOException $e) { }	
						while ($rowSelect=$resultSelect->fetch()) {
							$selected="" ;
							if ($row["value"]==$rowSelect["gibbonPersonID"]) {
								$selected="selected" ;
							}
							print "<option $selected value='" . $rowSelect["gibbonPersonID"] . "'>" . formatName(htmlPrep($rowSelect["title"]), ($rowSelect["preferredName"]), htmlPrep($rowSelect["surname"]),"Staff", true, true) . "</option>" ;
						}
						?>
					</select>
					<script type="text/javascript">
						var <?php print $row["name"] ?>=new LiveValidation('<?php print $row["name"] ?>');
						<?php print $row["name"] ?>.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print __($guid, 'Select something!') ?>"});
					</script>
				</td>
			</tr>
			<tr>
				<?php
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='System' AND name='organisationAdmissions'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				$row=$result->fetch() ;
				?>
				<td> 
					<b><?php print __($guid, $row["nameDisplay"]) ?> *</b><br/>
					<span class="emphasis small"><?php if ($row["description"]!="") { print __($guid, $row["description"]) ; } ?></span>
				</td>
				<td class="right">
					<select name="<?php print $row["name"] ?>" id="<?php print $row["name"] ?>" class="standardWidth">
						<?php
						print "<option value='Please select...'>" . __($guid, 'Please select...') . "</option>" ;
						try {
							$dataSelect=array(); 
							$sqlSelect="SELECT gibbonPerson.gibbonPersonID, title, surname, preferredName FROM gibbonPerson JOIN gibbonStaff ON (gibbonPerson.gibbonPersonID=gibbonStaff.gibbonPersonID) WHERE status='Full' ORDER BY surname, preferredName" ;
							$resultSelect=$connection2->prepare($sqlSelect);
							$resultSelect->execute($dataSelect);
						}
						catch(PDOException $e) { }	
						while ($rowSelect=$resultSelect->fetch()) {
							$selected="" ;
							if ($row["value"]==$rowSelect["gibbonPersonID"]) {
								$selected="selected" ;
							}
							print "<option $selected value='" . $rowSelect["gibbonPersonID"] . "'>" . formatName(htmlPrep($rowSelect["title"]), ($rowSelect["preferredName"]), htmlPrep($rowSelect["surname"]),"Staff", true, true) . "</option>" ;
						}
						?>
					</select>
					<script type="text/javascript">
						var <?php print $row["name"] ?>=new LiveValidation('<?php print $row["name"] ?>');
						<?php print $row["name"] ?>.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print __($guid, 'Select something!') ?>"});
					</script>
				</td>
			</tr>
			
			<tr>
				<?php
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='System' AND name='organisationHR'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				$row=$result->fetch() ;
				?>
				<td> 
					<b><?php print __($guid, $row["nameDisplay"]) ?> *</b><br/>
					<span class="emphasis small"><?php if ($row["description"]!="") { print __($guid, $row["description"]) ; } ?></span>
				</td>
				<td class="right">
					<select name="<?php print $row["name"] ?>" id="<?php print $row["name"] ?>" class="standardWidth">
						<?php
						print "<option value='Please select...'>" . __($guid, 'Please select...') . "</option>" ;
						try {
							$dataSelect=array(); 
							$sqlSelect="SELECT gibbonPerson.gibbonPersonID, title, surname, preferredName FROM gibbonPerson JOIN gibbonStaff ON (gibbonPerson.gibbonPersonID=gibbonStaff.gibbonPersonID) WHERE status='Full' ORDER BY surname, preferredName" ;
							$resultSelect=$connection2->prepare($sqlSelect);
							$resultSelect->execute($dataSelect);
						}
						catch(PDOException $e) { }	
						while ($rowSelect=$resultSelect->fetch()) {
							$selected="" ;
							if ($row["value"]==$rowSelect["gibbonPersonID"]) {
								$selected="selected" ;
							}
							print "<option $selected value='" . $rowSelect["gibbonPersonID"] . "'>" . formatName(htmlPrep($rowSelect["title"]), ($rowSelect["preferredName"]), htmlPrep($rowSelect["surname"]),"Staff", true, true) . "</option>" ;
						}
						?>
					</select>
					<script type="text/javascript">
						var <?php print $row["name"] ?>=new LiveValidation('<?php print $row["name"] ?>');
						<?php print $row["name"] ?>.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print __($guid, 'Select something!') ?>"});
					</script>
				</td>
			</tr>
			
			<tr class='break'>
				<td colspan=2> 
					<h3><?php print __($guid, 'Security Settings') ?></h3>
				</td>
			</tr>
			<tr>
				<td colspan=2> 
					<h4><?php print __($guid, 'Password Policy') ?></h4>
				</td>
			</tr>
			<tr>
				<?php
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='System' AND name='passwordPolicyMinLength'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				$row=$result->fetch() ;
				?>
				<td> 
					<b><?php print __($guid, $row["nameDisplay"]) ?> *</b><br/>
					<span class="emphasis small"><?php if ($row["description"]!="") { print __($guid, $row["description"]) ; } ?></span>
				</td>
				<td class="right">
					<select name="<?php print $row["name"] ?>" id="<?php print $row["name"] ?>" class="standardWidth">
						<?php
						for ($i=4; $i<13; $i++ ) { 
							$selected="" ;
							if ($row["value"]==$i ) { $selected="selected" ; }
							print "<option $selected value='$i'>$i</option>" ;
						}
						?>			
					</select>
				</td>
			</tr>
			<tr>
				<?php
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='System' AND name='passwordPolicyAlpha'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				$row=$result->fetch() ;
				?>
				<td> 
					<b><?php print __($guid, $row["nameDisplay"]) ?> *</b><br/>
					<span class="emphasis small"><?php if ($row["description"]!="") { print __($guid, $row["description"]) ; } ?></span>
				</td>
				<td class="right">
					<select name="<?php print $row["name"] ?>" id="<?php print $row["name"] ?>" class="standardWidth">
						<?php
						$selected="" ;
						if ($row["value"]=="Y" ) { $selected="selected" ; }
						print "<option $selected value='Y'>" . ynExpander($guid, 'Y') . "</option>" ;
						$selected="" ;
						if ($row["value"]=="N" ) { $selected="selected" ; }
						print "<option $selected value='N'>" . ynExpander($guid, 'N') . "</option>" ;
						?>			
					</select>
				</td>
			</tr>
			<tr>
				<?php
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='System' AND name='passwordPolicyNumeric'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				$row=$result->fetch() ;
				?>
				<td> 
					<b><?php print __($guid, $row["nameDisplay"]) ?> *</b><br/>
					<span class="emphasis small"><?php if ($row["description"]!="") { print __($guid, $row["description"]) ; } ?></span>
				</td>
				<td class="right">
					<select name="<?php print $row["name"] ?>" id="<?php print $row["name"] ?>" class="standardWidth">
						<?php
						$selected="" ;
						if ($row["value"]=="Y" ) { $selected="selected" ; }
						print "<option $selected value='Y'>" . ynExpander($guid, 'Y') . "</option>" ;
						$selected="" ;
						if ($row["value"]=="N" ) { $selected="selected" ; }
						print "<option $selected value='N'>" . ynExpander($guid, 'N') . "</option>" ;
						?>			
					</select>
				</td>
			</tr>
			<tr>
				<?php
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='System' AND name='passwordPolicyNonAlphaNumeric'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				$row=$result->fetch() ;
				?>
				<td> 
					<b><?php print __($guid, $row["nameDisplay"]) ?> *</b><br/>
					<span class="emphasis small"><?php if ($row["description"]!="") { print __($guid, $row["description"]) ; } ?></span>
				</td>
				<td class="right">
					<select name="<?php print $row["name"] ?>" id="<?php print $row["name"] ?>" class="standardWidth">
						<?php
						$selected="" ;
						if ($row["value"]=="Y" ) { $selected="selected" ; }
						print "<option $selected value='Y'>" . ynExpander($guid, 'Y') . "</option>" ;
						$selected="" ;
						if ($row["value"]=="N" ) { $selected="selected" ; }
						print "<option $selected value='N'>" . ynExpander($guid, 'N') . "</option>" ;
						?>			
					</select>
				</td>
			</tr>
			<tr>
				<td colspan=2> 
					<h4><?php print __($guid, 'Miscellaneous') ?></h4>
				</td>
			</tr>
			<tr>
				<?php
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='System' AND name='sessionDuration'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				$row=$result->fetch() ;
				?>
				<td> 
					<b><?php print __($guid, $row["nameDisplay"]) ?> *</b><br/>
					<span class="emphasis small"><?php if ($row["description"]!="") { print __($guid, $row["description"]) ; } ?></span>
				</td>
				<td class="right">
					<input name="<?php print $row["name"] ?>" id="<?php print $row["name"] ?>" maxlength=50 value="<?php print $row["value"] ?>" type="text" class="standardWidth">
					<script type="text/javascript">
						var <?php print $row["name"] ?>=new LiveValidation('<?php print $row["name"] ?>');
						<?php print $row["name"] ?>.add(Validate.Numericality);
						<?php print $row["name"] ?>.add( Validate.Numericality, { minimum: 1200 } );
						<?php print $row["name"] ?>.add(Validate.Presence);
					</script> 
				</td>
			</tr>
			<tr>
				<?php
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='System' AND name='allowableHTML'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				$row=$result->fetch() ;
				?>
				<td> 
					<b><?php print __($guid, $row["nameDisplay"]) ?></b><br/>
					<span class="emphasis small"><?php if ($row["description"]!="") { print __($guid, $row["description"]) ; } ?></span>
				</td>
				<td class="right">
					<textarea name="<?php print $row["name"] ?>" id="<?php print $row["name"] ?>" rows=8 class="standardWidth"><?php print htmlPrep($row["value"]) ?></textarea>
				</td>
			</tr>
			
			<tr class='break'>
				<td colspan=2> 
					<h3><?php print __($guid, 'gibbonedu.com Value Added Services') ?></h3>
				</td>
			</tr>
			<tr>
				<?php
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='System' AND name='gibboneduComOrganisationName'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				$row=$result->fetch() ;
				?>
				<td> 
					<b><?php print __($guid, $row["nameDisplay"]) ?></b><br/>
					<span class="emphasis small"><?php if ($row["description"]!="") { print __($guid, $row["description"]) ; } ?></span>
				</td>
				<td class="right">
					<input name="<?php print $row["name"] ?>" id="<?php print $row["name"] ?>" maxlength=255 value="<?php print $row["value"] ?>" type="text" class="standardWidth">
				</td>
			</tr>
			<tr>
				<?php
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='System' AND name='gibboneduComOrganisationKey'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				$row=$result->fetch() ;
				?>
				<td> 
					<b><?php print __($guid, $row["nameDisplay"]) ?></b><br/>
					<span class="emphasis small"><?php if ($row["description"]!="") { print __($guid, $row["description"]) ; } ?></span>
				</td>
				<td class="right">
					<input name="<?php print $row["name"] ?>" id="<?php print $row["name"] ?>" maxlength=255 value="<?php print $row["value"] ?>" type="text" class="standardWidth">
				</td>
			</tr>
			
			
			<tr class='break'>
				<td colspan=2> 
					<h3><?php print __($guid, 'Localisation') ?></h3>
				</td>
			</tr>
			<tr>
				<?php
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='System' AND name='country'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				$row=$result->fetch() ;
				?>
				<td> 
					<b><?php print __($guid, $row["nameDisplay"]) ?></b><br/>
					<span class="emphasis small"><?php if ($row["description"]!="") { print __($guid, $row["description"]) ; } ?></span>
				</td>
				<td class="right">
					<select name="<?php print $row["name"] ?>" id="<?php print $row["name"] ?>" class="standardWidth">
						<?php
						print "<option value=''></option>" ;
						try {
							$dataSelect=array(); 
							$sqlSelect="SELECT printable_name FROM gibbonCountry ORDER BY printable_name" ;
							$resultSelect=$connection2->prepare($sqlSelect);
							$resultSelect->execute($dataSelect);
						}
						catch(PDOException $e) { 
							print "<div class='error'>" . $e->getMessage() . "</div>" ; 
						}
						while ($rowSelect=$resultSelect->fetch()) {
							$selected="" ;
							if ($row["value"]==$rowSelect["printable_name"]) {
								$selected="selected" ;
							}
							print "<option $selected value='" . $rowSelect["printable_name"] . "'>" . __($guid, $rowSelect["printable_name"]) . "</option>" ;
						}
						?>
					</select>
				</td>
			</tr>
			<tr>
				<?php
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='System' AND name='firstDayOfTheWeek'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				$row=$result->fetch() ;
				?>
				<td> 
					<b><?php print __($guid, $row["nameDisplay"]) ?> *</b><br/>
					<span class="emphasis small"><?php if ($row["description"]!="") { print __($guid, $row["description"]) ; } ?></span>
				</td>
				<td class="right">
					<select name="<?php print $row["name"] ?>" id="<?php print $row["name"] ?>" class="standardWidth">
						<option <?php if ($row["value"]=="Monday") { print "selected" ; } ?> value='Monday'>Monday</option>
						<option <?php if ($row["value"]=="Sunday") { print "selected" ; } ?> value='Sunday'>Sunday</option>
					</select>
				</td>
			</tr>
			<tr>
				<?php
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='System' AND name='timezone'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				$row=$result->fetch() ;
				?>
				<td> 
					<b><?php print __($guid, $row["nameDisplay"]) ?> *</b><br/>
					<span class="emphasis small"><?php if ($row["description"]!="") { print __($guid, $row["description"]) ; } ?></span>
				</td>
				<td class="right">
					<input name="<?php print $row["name"] ?>" id="<?php print $row["name"] ?>" maxlength=50 value="<?php print htmlPrep($row["value"]) ?>" type="text" class="standardWidth">
					<script type="text/javascript">
						var <?php print $row["name"] ?>=new LiveValidation('<?php print $row["name"] ?>');
						<?php print $row["name"] ?>.add(Validate.Presence);
					</script> 
				</td>
			</tr>
			<tr>
				<?php
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='System' AND name='currency'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				$row=$result->fetch() ;
				?>
				<td> 
					<b><?php print __($guid, $row["nameDisplay"]) ?> *</b><br/>
					<span class="emphasis small"><?php if ($row["description"]!="") { print __($guid, $row["description"]) ; } ?></span>
				</td>
				<td class="right">
					<select name="<?php print $row["name"] ?>" id="<?php print $row["name"] ?>" class="standardWidth">
						<optgroup label='--<?php print __($guid, 'PAYPAL SUPPORTED') ?>--'/>
							<option <?php if ($row["value"]=="AUD $") { print "selected" ; } ?> value='AUD $'>Australian Dollar (A$)</option>
							<option <?php if ($row["value"]=="BRL R$") { print "selected" ; } ?> value='BRL R$'>Brazilian Real</option>
							<option <?php if ($row["value"]=="GBP £") { print "selected" ; } ?> value='GBP £'>British Pound (£)</option>
							<option <?php if ($row["value"]=="CAD $") { print "selected" ; } ?> value='CAD $'>Canadian Dollar (C$)</option>
							<option <?php if ($row["value"]=="CZK Kč") { print "selected" ; } ?> value='CZK Kč'>Czech Koruna</option>
							<option <?php if ($row["value"]=="DKK kr") { print "selected" ; } ?> value='DKK kr'>Danish Krone</option>
							<option <?php if ($row["value"]=="EUR €") { print "selected" ; } ?> value='EUR €'>Euro (€)</option>
							<option <?php if ($row["value"]=="HKD $") { print "selected" ; } ?> value='HKD $'>Hong Kong Dollar ($)</option>
							<option <?php if ($row["value"]=="HUF Ft") { print "selected" ; } ?> value='HUF Ft'>Hungarian Forint</option>
							<option <?php if ($row["value"]=="ILS ₪") { print "selected" ; } ?> value='ILS ₪'>Israeli New Shekel</option>
							<option <?php if ($row["value"]=="JPY ¥") { print "selected" ; } ?> value='JPY ¥'>Japanese Yen (¥)</option>
							<option <?php if ($row["value"]=="MYR RM") { print "selected" ; } ?> value='MYR RM'>Malaysian Ringgit</option>
							<option <?php if ($row["value"]=="MXN $") { print "selected" ; } ?> value='MXN $'>Mexican Peso</option>
							<option <?php if ($row["value"]=="TWD $") { print "selected" ; } ?> value='TWD $'>New Taiwan Dollar</option>
							<option <?php if ($row["value"]=="NZD $") { print "selected" ; } ?> value='NZD $'>New Zealand Dollar ($)</option>
							<option <?php if ($row["value"]=="NOK kr") { print "selected" ; } ?> value='NOK kr'>Norwegian Krone</option>
							<option <?php if ($row["value"]=="PHP ₱") { print "selected" ; } ?> value='PHP ₱'>Philippine Peso</option>
							<option <?php if ($row["value"]=="PLN zł") { print "selected" ; } ?> value='PLN zł'>Polish Zloty</option>
							<option <?php if ($row["value"]=="SGD $") { print "selected" ; } ?> value='SGD $'>Singapore Dollar ($)</option>
							<option <?php if ($row["value"]=="CHF") { print "selected" ; } ?> value='CHF'>Swiss Franc</option>
							<option <?php if ($row["value"]=="THB ฿") { print "selected" ; } ?> value='THB ฿'>Thai Baht</option>
							<option <?php if ($row["value"]=="TRY") { print "selected" ; } ?> value='TRY'>Turkish Lira</option>
							<option <?php if ($row["value"]=="USD $") { print "selected" ; } ?> value='USD $'>U.S. Dollar ($)</option>
						</optgroup>
						<optgroup label='--<?php print __($guid, 'OTHERS') ?>--'/>
							<option <?php if ($row["value"]=="BDT ó") { print "selected" ; } ?> value='BDT ó'>Bangladeshi Taka (ó)</option>
							<option <?php if ($row["value"]=="BTC") { print "selected" ; } ?> value='BTC'>Bitcoin</option>
							<option <?php if ($row["value"]=="XAF FCFA") { print "selected" ; } ?> value='XAF FCFA'>Central African Francs (FCFA)</option>
							<option <?php if ($row["value"]=="EGP £") { print "selected" ; } ?> value='EGP £'>Egyptian Pound (£)</option>
							<option <?php if ($row["value"]=="INR ₹") { print "selected" ; } ?> value='INR ₹'>Indian Rupee (₹)</option>
							<option <?php if ($row["value"]=="IDR Rp") { print "selected" ; } ?> value='IDR Rp'>Indonesian Rupiah (Rp)</option>
							<option <?php if ($row["value"]=="JMD $") { print "selected" ; } ?> value='JMD $'>Jamaican Dollar ($)</option>
							<option <?php if ($row["value"]=="KES KSh") { print "selected" ; } ?> value='KES KSh'>Kenyan Shilling (KSh)</option>
							<option <?php if ($row["value"]=="MOP MOP$") { print "selected" ; } ?> value='MOP MOP$'>Macanese Pataca (MOP$)</option>
							<option <?php if ($row["value"]=="NPR ₨") { print "selected" ; } ?> value='NPR ₨'>Nepalese Rupee (₨)</option>
							<option <?php if ($row["value"]=="NGN ₦") { print "selected" ; } ?> value='NGN ₦'>Nigerian Naira (₦)</option>
							<option <?php if ($row["value"]=="PKR ₨") { print "selected" ; } ?> value='PKR ₨'>Pakistani Rupee (₨)</option>
							<option <?php if ($row["value"]=="SAR ﷼‎") { print "selected" ; } ?> value='SAR ﷼‎'>Saudi Riyal (﷼‎)</option>
							<option <?php if ($row["value"]=="TZS TSh") { print "selected" ; } ?> value='TZS TSh'>Tanzania Shilling (TSh)</option>
							<option <?php if ($row["value"]=="VND ₫‎") { print "selected" ; } ?> value='VND ₫‎'>Vietnamese Dong (₫‎)</option>
						</optgroup>
					</select>
				</td>
			</tr>
			
			<tr class='break'>
				<td colspan=2> 
					<h3><?php print __($guid, 'Miscellaneous') ?></h3>
				</td>
			</tr>
			<tr>
				<?php
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='System' AND name='emailLink'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				$row=$result->fetch() ;
				?>
				<td> 
					<b><?php print __($guid, $row["nameDisplay"]) ?></b><br/>
					<span class="emphasis small"><?php if ($row["description"]!="") { print __($guid, $row["description"]) ; } ?></span>
				</td>
				<td class="right">
					<input name="<?php print $row["name"] ?>" id="<?php print $row["name"] ?>" maxlength=255 value="<?php print $row["value"] ?>" type="text" class="standardWidth">
					<script type="text/javascript">
						var <?php print $row["name"] ?>=new LiveValidation('<?php print $row["name"] ?>');
						<?php print $row["name"] ?>.add( Validate.Format, { pattern: /(http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/, failureMessage: "Must start with http:// or https://" } );
					</script>	
				</td>
			</tr>
			<tr>
				<?php
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='System' AND name='webLink'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				$row=$result->fetch() ;
				?>
				<td> 
					<b><?php print __($guid, $row["nameDisplay"]) ?></b><br/>
					<span class="emphasis small"><?php if ($row["description"]!="") { print __($guid, $row["description"]) ; } ?></span>
				</td>
				<td class="right">
					<input name="<?php print $row["name"] ?>" id="<?php print $row["name"] ?>" maxlength=255 value="<?php print $row["value"] ?>" type="text" class="standardWidth">
					<script type="text/javascript">
						var <?php print $row["name"] ?>=new LiveValidation('<?php print $row["name"] ?>');
						<?php print $row["name"] ?>.add( Validate.Format, { pattern: /(http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/, failureMessage: "Must start with http:// or https://" } );
					</script>	
				</td>
			</tr>
			<tr>
				<?php
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='System' AND name='pagination'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				$row=$result->fetch() ;
				?>
				<td> 
					<b><?php print __($guid, $row["nameDisplay"]) ?> *</b><br/>
					<span class="emphasis small"><?php if ($row["description"]!="") { print __($guid, $row["description"]) ; } ?></span>
				</td>
				<td class="right">
					<input name="<?php print $row["name"] ?>" id="<?php print $row["name"] ?>" maxlength=50 value="<?php print $row["value"] ?>" type="text" class="standardWidth">
					<script type="text/javascript">
						var <?php print $row["name"] ?>=new LiveValidation('<?php print $row["name"] ?>');
						<?php print $row["name"] ?>.add(Validate.Numericality);
						<?php print $row["name"] ?>.add(Validate.Presence);
					</script> 
				</td>
			</tr>
			<tr>
				<?php
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='System' AND name='analytics'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				$row=$result->fetch() ;
				?>
				<td> 
					<b><?php print __($guid, $row["nameDisplay"]) ?></b><br/>
					<span class="emphasis small"><?php if ($row["description"]!="") { print __($guid, $row["description"]) ; } ?></span>
				</td>
				<td class="right">
					<textarea name="<?php print $row["name"] ?>" id="<?php print $row["name"] ?>" rows=8 class="standardWidth"><?php print htmlPrep($row["value"]) ?></textarea>
				</td>
			</tr>
			<tr>
				<?php
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='System' AND name='primaryAssessmentScale'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				$row=$result->fetch() ;
				?>
				<td> 
					<b><?php print __($guid, $row["nameDisplay"]) ?> *</b><br/>
					<span class="emphasis small"><?php if ($row["description"]!="") { print __($guid, $row["description"]) ; } ?></span>
				</td>
				<td class="right">
					<select name="<?php print $row["name"] ?>" id="<?php print $row["name"] ?>" class="standardWidth">
						<?php
						print "<option value='Please select...'>" . __($guid, 'Please select...') . "</option>" ;
						try {
							$dataSelect=array(); 
							$sqlSelect="SELECT * FROM gibbonScale WHERE active='Y' ORDER BY name" ;
							$resultSelect=$connection2->prepare($sqlSelect);
							$resultSelect->execute($dataSelect);
						}
						catch(PDOException $e) { 
							print "<div class='error'>" . $e->getMessage() . "</div>" ; 
						}
						while ($rowSelect=$resultSelect->fetch()) {
							$selected="" ;
							if ($row["value"]==$rowSelect["gibbonScaleID"]) {
								$selected="selected" ;
							}
							
							print "<option $selected value='" . $rowSelect["gibbonScaleID"] . "'>" . htmlPrep(__($guid, $rowSelect["name"])) . "</option>" ;
						}
						?>			
					</select>
					<script type="text/javascript">
						var <?php print $row["name"] ?>=new LiveValidation('<?php print $row["name"] ?>');
						<?php print $row["name"] ?>.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print __($guid, 'Select something!') ?>"});
					</script>
				</td>
			</tr>
			
			<tr>
				<td>
					<span class="emphasis small">* <?php print __($guid, "denotes a required field") ; ?></span>
				</td>
				<td class="right">
					<input type="hidden" name="address" value="<?php print $_SESSION[$guid]["address"] ?>">
					<input type="submit" value="<?php print __($guid, "Submit") ; ?>">
				</td>
			</tr>
		</table>
	</form>
<?php
}
?>