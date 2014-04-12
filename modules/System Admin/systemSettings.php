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

if (isActionAccessible($guid, $connection2, "/modules/System Admin/systemSettings.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Submit stats if that is what the system calls for
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
		print "<iframe style='display: none; height: 10px; width: 10px' src='http://gibbonedu.org/tracker/tracker.php?absolutePathProtocol=" . urlencode($absolutePathProtocol) . "&absolutePath=" . urlencode($absolutePath) . "&organisationName=" . urlencode($_SESSION[$guid]['organisationName']) . "&type=" . urlencode($_SESSION[$guid]['installType']) . "&version=" . urlencode($version) . "'></iframe>" ;
	}

	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > </div><div class='trailEnd'>" . _('System Settings') . "</div>" ;
	print "</div>" ;
	
	if (isset($_GET["updateReturn"])) { $updateReturn=$_GET["updateReturn"] ; } else { $updateReturn="" ; }
	$updateReturnMessage="" ;
	$class="error" ;
	if (!($updateReturn=="")) {
		if ($updateReturn=="fail0") {
			$updateReturnMessage=_("Your request failed because you do not have access to this action.") ;	
		}
		else if ($updateReturn=="fail1") {
			$updateReturnMessage=_("Your request failed because your inputs were invalid.") ;	
		}
		else if ($updateReturn=="fail2") {
			$updateReturnMessage=_("One or more of the fields in your request failed due to a database error.") ;	
		}
		else if ($updateReturn=="fail3") {
			$updateReturnMessage=_("Your request failed because your inputs were invalid.") ;	
		}
		else if ($updateReturn=="success0") {
			$updateReturnMessage=_("Your request was completed successfully.") ;	
			$class="success" ;
		}
		print "<div class='$class'>" ;
			print $updateReturnMessage;
		print "</div>" ;
	} 
	?>
	
	<form method="post" action="<? print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/systemSettingsProcess.php" ?>">
		<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
			<tr class='break'>
				<td colspan=2> 
					<h3><? print _('System Settings') ?></h3>
				</td>
			</tr>
			<tr>
				<?
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
				<td> 
					<b><? print $row["nameDisplay"] ?> *</b><br/>
					<span style="font-size: 90%"><i><? print $row["description"] ?></i></span>
				</td>
				<td stclass="right">
					<input name="<? print $row["name"] ?>" id="<? print $row["name"] ?>" maxlength=50 value="<? print htmlPrep($row["value"]) ?>" type="text" style="width: 300px">
					<script type="text/javascript">
						var <? print $row["name"] ?>=new LiveValidation('<? print $row["name"] ?>');
						<? print $row["name"] ?>.add(Validate.Presence);
						<? print $row["name"] ?>.add( Validate.Format, { pattern: /(http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/, failureMessage: "Must start with http://" } );
					 </script> 
				</td>
			</tr>
			<tr>
				<?
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
					<b><? print $row["nameDisplay"] ?> *</b><br/>
					<span style="font-size: 90%"><i><? print $row["description"] ?></i></span>
				</td>
				<td stclass="right">
					<input name="<? print $row["name"] ?>" id="<? print $row["name"] ?>" maxlength=50 value="<? print htmlPrep($row["value"]) ?>" type="text" style="width: 300px">
					<script type="text/javascript">
						var <? print $row["name"] ?>=new LiveValidation('<? print $row["name"] ?>');
						<? print $row["name"] ?>.add(Validate.Presence);
					 </script> 
				</td>
			</tr>
			<tr>
				<?
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
					<b><? print $row["nameDisplay"] ?> *</b><br/>
					<span style="font-size: 90%"><i><? print $row["description"] ?></i></span>
				</td>
				<td class="right">
					<input name="<? print $row["name"] ?>" id="<? print $row["name"] ?>" maxlength=50 value="<? print htmlPrep($row["value"]) ?>" type="text" style="width: 300px">
					<script type="text/javascript">
						var <? print $row["name"] ?>=new LiveValidation('<? print $row["name"] ?>');
						<? print $row["name"] ?>.add(Validate.Presence);
					 </script> 
				</td>
			</tr>
			<tr>
				<?
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
					<b><? print $row["nameDisplay"] ?> *</b><br/>
					<span style="font-size: 90%"><i><? print $row["description"] ?></i></span>
				</td>
				<td class="right">
					<textarea name="<? print $row["name"] ?>" id="<? print $row["name"] ?>" rows=8 style="width: 300px"><? print htmlPrep($row["value"]) ?></textarea>
					<script type="text/javascript">
						var <? print $row["name"] ?>=new LiveValidation('<? print $row["name"] ?>');
						<? print $row["name"] ?>.add(Validate.Presence);
					 </script> 
				</td>
			</tr>
			<tr>
				<?
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
					<b><? print $row["nameDisplay"] ?> *</b><br/>
					<span style="font-size: 90%"><i><? print $row["description"] ?></i></span>
				</td>
				<td class="right">
					<select name="<? print $row["name"] ?>" id="<? print $row["name"] ?>" style="width: 302px">
						<?
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
				<?
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
					<b><? print $row["nameDisplay"] ?> *</b><br/>
					<span style="font-size: 90%"><i><? print $row["description"] ?></i></span>
				</td>
				<td class="right">
					<select name="<? print $row["name"] ?>" id="<? print $row["name"] ?>" style="width: 302px">
						<?
						$selected="" ;
						if ($row["value"]=="Y" ) { $selected="selected" ; }
						print "<option $selected value='Y'>Y</option>" ;
						$selected="" ;
						if ($row["value"]=="N" ) { $selected="selected" ; }
						print "<option $selected value='N'>N</option>" ;
						?>			
					</select>
				</td>
			</tr>
			<tr>
				<?
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
					<b><? print $row["nameDisplay"] ?> *</b><br/>
					<span style="font-size: 90%"><i><? print $row["description"] ?></i></span>
				</td>
				<td class="right">
					<select name="<? print $row["name"] ?>" id="<? print $row["name"] ?>" style="width: 302px">
						<?
						$selected="" ;
						if ($row["value"]=="Y" ) { $selected="selected" ; }
						print "<option $selected value='Y'>Y</option>" ;
						$selected="" ;
						if ($row["value"]=="N" ) { $selected="selected" ; }
						print "<option $selected value='N'>N</option>" ;
						?>			
					</select>
				</td>
			</tr>
		
			<tr class='break'>
				<td colspan=2> 
					<h3><? print _('Organisation Settings') ?></h3>
				</td>
			</tr>
			<tr>
				<?
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
					<b><? print $row["nameDisplay"] ?> *</b><br/>
					<span style="font-size: 90%"><i><? print $row["description"] ?></i></span>
				</td>
				<td class="right">
					<input name="<? print $row["name"] ?>" id="<? print $row["name"] ?>" maxlength=50 value="<? print htmlPrep($row["value"]) ?>" type="text" style="width: 300px">
					<script type="text/javascript">
						var <? print $row["name"] ?>=new LiveValidation('<? print $row["name"] ?>');
						<? print $row["name"] ?>.add(Validate.Presence);
					 </script> 
				</td>
			</tr>
			<tr>
				<?
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
					<b><? print $row["nameDisplay"] ?> *</b><br/>
					<span style="font-size: 90%"><i><? print $row["description"] ?></i></span>
				</td>
				<td class="right">
					<input name="<? print $row["name"] ?>" id="<? print $row["name"] ?>" maxlength=50 value="<? print htmlPrep($row["value"]) ?>" type="text" style="width: 300px">
					<script type="text/javascript">
						var <? print $row["name"] ?>=new LiveValidation('<? print $row["name"] ?>');
						<? print $row["name"] ?>.add(Validate.Presence);
					 </script> 
				</td>
			</tr>
			<tr>
				<?
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
					<b><? print $row["nameDisplay"] ?></b><br/>
					<span style="font-size: 90%"><i><? print $row["description"] ?></i></span>
				</td>
				<td class="right">
					<input name="<? print $row["name"] ?>" id="<? print $row["name"] ?>" maxlength=255 value="<? print htmlPrep($row["value"]) ?>" type="text" style="width: 300px">
					<script type="text/javascript">
						var <? print $row["name"] ?>=new LiveValidation('<? print $row["name"] ?>');
						<? print $row["name"] ?>.add(Validate.Email);
					 </script> 
				</td>
			</tr>
			<tr>
				<?
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
					<b><? print $row["nameDisplay"] ?> *</b><br/>
					<span style="font-size: 90%"><i><? print $row["description"] ?></i></span>
				</td>
				<td class="right">
					<input name="<? print $row["name"] ?>" id="<? print $row["name"] ?>" maxlength=255 value="<? print htmlPrep($row["value"]) ?>" type="text" style="width: 300px">
					<script type="text/javascript">
						var <? print $row["name"] ?>=new LiveValidation('<? print $row["name"] ?>');
						<? print $row["name"] ?>.add(Validate.Presence);
					 </script> 
				</td>
			</tr>
			<tr>
				<?
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='System' AND name='organisationAdministratorName'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				$row=$result->fetch() ;
				?>
				<td> 
					<b><? print $row["nameDisplay"] ?> *</b><br/>
					<span style="font-size: 90%"><i><? print $row["description"] ?></i></span>
				</td>
				<td class="right">
					<input name="<? print $row["name"] ?>" id="<? print $row["name"] ?>" maxlength=50 value="<? print htmlPrep($row["value"]) ?>" type="text" style="width: 300px">
					<script type="text/javascript">
						var <? print $row["name"] ?>=new LiveValidation('<? print $row["name"] ?>');
						<? print $row["name"] ?>.add(Validate.Presence);
					 </script> 
				</td>
			</tr>
			<tr>
				<?
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='System' AND name='organisationAdministratorEmail'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				$row=$result->fetch() ;
				?>
				<td> 
					<b><? print $row["nameDisplay"] ?> *</b><br/>
					<span style="font-size: 90%"><i><? print $row["description"] ?></i></span>
				</td>
				<td class="right">
					<input name="<? print $row["name"] ?>" id="<? print $row["name"] ?>" maxlength=255 value="<? print htmlPrep($row["value"]) ?>" type="text" style="width: 300px">
					<script type="text/javascript">
						var <? print $row["name"] ?>=new LiveValidation('<? print $row["name"] ?>');
						<? print $row["name"] ?>.add(Validate.Email);
						<? print $row["name"] ?>.add(Validate.Presence);
					 </script> 
				</td>
			</tr>
			<tr>
				<?
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='System' AND name='organisationDBAName'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				$row=$result->fetch() ;
				?>
				<td> 
					<b><? print $row["nameDisplay"] ?> *</b><br/>
					<span style="font-size: 90%"><i><? print $row["description"] ?></i></span>
				</td>
				<td class="right">
					<input name="<? print $row["name"] ?>" id="<? print $row["name"] ?>" maxlength=50 value="<? print htmlPrep($row["value"]) ?>" type="text" style="width: 300px">
					<script type="text/javascript">
						var <? print $row["name"] ?>=new LiveValidation('<? print $row["name"] ?>');
						<? print $row["name"] ?>.add(Validate.Presence);
					 </script> 
				</td>
			</tr>
			<tr>
				<?
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='System' AND name='organisationDBAEmail'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				$row=$result->fetch() ;
				?>
				<td> 
					<b><? print $row["nameDisplay"] ?> *</b><br/>
					<span style="font-size: 90%"><i><? print $row["description"] ?></i></span>
				</td>
				<td class="right">
					<input name="<? print $row["name"] ?>" id="<? print $row["name"] ?>" maxlength=255 value="<? print htmlPrep($row["value"]) ?>" type="text" style="width: 300px">
					<script type="text/javascript">
						var <? print $row["name"] ?>=new LiveValidation('<? print $row["name"] ?>');
						<? print $row["name"] ?>.add(Validate.Email);
						<? print $row["name"] ?>.add(Validate.Presence);
					 </script> 
				</td>
			</tr>
			<tr>
				<?
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='System' AND name='organisationAdmissionsName'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				$row=$result->fetch() ;
				?>
				<td> 
					<b><? print $row["nameDisplay"] ?> *</b><br/>
					<span style="font-size: 90%"><i><? print $row["description"] ?></i></span>
				</td>
				<td class="right">
					<input name="<? print $row["name"] ?>" id="<? print $row["name"] ?>" maxlength=50 value="<? print htmlPrep($row["value"]) ?>" type="text" style="width: 300px">
					<script type="text/javascript">
						var <? print $row["name"] ?>=new LiveValidation('<? print $row["name"] ?>');
						<? print $row["name"] ?>.add(Validate.Presence);
					 </script> 
				</td>
			</tr>
			<tr>
				<?
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='System' AND name='organisationAdmissionsEmail'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				$row=$result->fetch() ;
				?>
				<td> 
					<b><? print $row["nameDisplay"] ?> *</b><br/>
					<span style="font-size: 90%"><i><? print $row["description"] ?></i></span>
				</td>
				<td class="right">
					<input name="<? print $row["name"] ?>" id="<? print $row["name"] ?>" maxlength=255 value="<? print htmlPrep($row["value"]) ?>" type="text" style="width: 300px">
					<script type="text/javascript">
						var <? print $row["name"] ?>=new LiveValidation('<? print $row["name"] ?>');
						<? print $row["name"] ?>.add(Validate.Email);
						<? print $row["name"] ?>.add(Validate.Presence);
					 </script> 
				</td>
			</tr>
			
			<tr class='break'>
				<td colspan=2> 
					<h3><? print _('Security Settings') ?></h3>
				</td>
			</tr>
			<tr>
				<td colspan=2> 
					<h4><? print _('Password Policy') ?></h4>
				</td>
			</tr>
			<tr>
				<?
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
					<b><? print $row["nameDisplay"] ?> *</b><br/>
					<span style="font-size: 90%"><i><? print $row["description"] ?></i></span>
				</td>
				<td class="right">
					<select name="<? print $row["name"] ?>" id="<? print $row["name"] ?>" style="width: 302px">
						<?
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
				<?
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
					<b><? print $row["nameDisplay"] ?> *</b><br/>
					<span style="font-size: 90%"><i><? print $row["description"] ?></i></span>
				</td>
				<td class="right">
					<select name="<? print $row["name"] ?>" id="<? print $row["name"] ?>" style="width: 302px">
						<?
						$selected="" ;
						if ($row["value"]=="Y" ) { $selected="selected" ; }
						print "<option $selected value='Y'>Y</option>" ;
						$selected="" ;
						if ($row["value"]=="N" ) { $selected="selected" ; }
						print "<option $selected value='N'>N</option>" ;
						?>			
					</select>
				</td>
			</tr>
			<tr>
				<?
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
					<b><? print $row["nameDisplay"] ?> *</b><br/>
					<span style="font-size: 90%"><i><? print $row["description"] ?></i></span>
				</td>
				<td class="right">
					<select name="<? print $row["name"] ?>" id="<? print $row["name"] ?>" style="width: 302px">
						<?
						$selected="" ;
						if ($row["value"]=="Y" ) { $selected="selected" ; }
						print "<option $selected value='Y'>Y</option>" ;
						$selected="" ;
						if ($row["value"]=="N" ) { $selected="selected" ; }
						print "<option $selected value='N'>N</option>" ;
						?>			
					</select>
				</td>
			</tr>
			<tr>
				<?
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
					<b><? print $row["nameDisplay"] ?> *</b><br/>
					<span style="font-size: 90%"><i><? print $row["description"] ?></i></span>
				</td>
				<td class="right">
					<select name="<? print $row["name"] ?>" id="<? print $row["name"] ?>" style="width: 302px">
						<?
						$selected="" ;
						if ($row["value"]=="Y" ) { $selected="selected" ; }
						print "<option $selected value='Y'>Y</option>" ;
						$selected="" ;
						if ($row["value"]=="N" ) { $selected="selected" ; }
						print "<option $selected value='N'>N</option>" ;
						?>			
					</select>
				</td>
			</tr>
			<tr>
				<td colspan=2> 
					<h4><? print _('Miscellaneous') ?></h4>
				</td>
			</tr>
			<tr>
				<?
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
					<b><? print $row["nameDisplay"] ?></b><br/>
					<span style="font-size: 90%"><i><? print $row["description"] ?></i></span>
				</td>
				<td class="right">
					<textarea name="<? print $row["name"] ?>" id="<? print $row["name"] ?>" rows=8 style="width: 300px"><? print htmlPrep($row["value"]) ?></textarea>
				</td>
			</tr>
			
			<tr class='break'>
				<td colspan=2> 
					<h3><? print _('Calendar, Web & Email') ?></h3>
				</td>
			</tr>
			<tr>
				<?
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='System' AND name='calendarFeed'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				$row=$result->fetch() ;
				?>
				<td> 
					<b><? print $row["nameDisplay"] ?></b><br/>
					<span style="font-size: 90%"><i><? print $row["description"] ?></i></span>
				</td>
				<td class="right">
					<input name="<? print $row["name"] ?>" id="<? print $row["name"] ?>" maxlength=255 value="<? print $row["value"] ?>" type="text" style="width: 300px">
					<script type="text/javascript">
						var <? print $row["name"] ?>=new LiveValidation('<? print $row["name"] ?>');
						<? print $row["name"] ?>.add( Validate.Format, { pattern: /(http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/, failureMessage: "Must start with http://" } );
					</script>	
				</td>
			</tr>
			<tr>
				<?
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
					<b><? print $row["nameDisplay"] ?></b><br/>
					<span style="font-size: 90%"><i><? print $row["description"] ?></i></span>
				</td>
				<td class="right">
					<input name="<? print $row["name"] ?>" id="<? print $row["name"] ?>" maxlength=255 value="<? print $row["value"] ?>" type="text" style="width: 300px">
					<script type="text/javascript">
						var <? print $row["name"] ?>=new LiveValidation('<? print $row["name"] ?>');
						<? print $row["name"] ?>.add( Validate.Format, { pattern: /(http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/, failureMessage: "Must start with http://" } );
					</script>	
				</td>
			</tr>
			<tr>
				<?
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
					<b><? print $row["nameDisplay"] ?></b><br/>
					<span style="font-size: 90%"><i><? print $row["description"] ?></i></span>
				</td>
				<td class="right">
					<input name="<? print $row["name"] ?>" id="<? print $row["name"] ?>" maxlength=255 value="<? print $row["value"] ?>" type="text" style="width: 300px">
					<script type="text/javascript">
						var <? print $row["name"] ?>=new LiveValidation('<? print $row["name"] ?>');
						<? print $row["name"] ?>.add( Validate.Format, { pattern: /(http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/, failureMessage: "Must start with http://" } );
					</script>	
				</td>
			</tr>
			
			<tr class='break'>
				<td colspan=2> 
					<h3><? print _('gibbonedu.com Value-Added Services') ?></h3>
				</td>
			</tr>
			<tr>
				<?
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
					<b><? print $row["nameDisplay"] ?></b><br/>
					<span style="font-size: 90%"><i><? print $row["description"] ?></i></span>
				</td>
				<td class="right">
					<input name="<? print $row["name"] ?>" id="<? print $row["name"] ?>" maxlength=255 value="<? print $row["value"] ?>" type="text" style="width: 300px">
				</td>
			</tr>
			<tr>
				<?
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
					<b><? print $row["nameDisplay"] ?></b><br/>
					<span style="font-size: 90%"><i><? print $row["description"] ?></i></span>
				</td>
				<td class="right">
					<input name="<? print $row["name"] ?>" id="<? print $row["name"] ?>" maxlength=255 value="<? print $row["value"] ?>" type="text" style="width: 300px">
				</td>
			</tr>
			
			<tr class='break'>
				<td colspan=2> 
					<h3><? print _('PayPal Payment Gateway') ?></h3>
				</td>
			</tr>
			
			<tr>
				<?
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
					<b><? print $row["nameDisplay"] ?> *</b><br/>
					<span style="font-size: 90%"><i><? print $row["description"] ?></i></span>
				</td>
				<td class="right">
					<select name="<? print $row["name"] ?>" id="<? print $row["name"] ?>" style="width: 302px">
						<option <? if ($row["value"]=="AUD $") { print "selected" ; } ?> value='AUD $'>Australian Dollar (A$)</option>
						<option <? if ($row["value"]=="BRL R$") { print "selected" ; } ?> value='BRL R$'>Brazilian Real</option>
						<option <? if ($row["value"]=="GBP £") { print "selected" ; } ?> value='GBP £'>British Pound (£)</option>
						<option <? if ($row["value"]=="CAD $") { print "selected" ; } ?> value='CAD $'>Canadian Dollar (C$)</option>
						<option <? if ($row["value"]=="CZK Kč") { print "selected" ; } ?> value='CZK Kč'>Czech Koruna</option>
						<option <? if ($row["value"]=="DKK kr") { print "selected" ; } ?> value='DKK kr'>Danish Krone</option>
						<option <? if ($row["value"]=="EUR €") { print "selected" ; } ?> value='EUR €'>Euro (€)</option>
						<option <? if ($row["value"]=="HKD $") { print "selected" ; } ?> value='HKD $'>Hong Kong Dollar ($)</option>
						<option <? if ($row["value"]=="HUF Ft") { print "selected" ; } ?> value='HUF Ft'>Hungarian Forint</option>
						<option <? if ($row["value"]=="ILS ₪") { print "selected" ; } ?> value='ILS ₪'>Israeli New Shekel</option>
						<option <? if ($row["value"]=="JPY ¥") { print "selected" ; } ?> value='JPY ¥'>Japanese Yen (¥)</option>
						<option <? if ($row["value"]=="MYR RM") { print "selected" ; } ?> value='MYR RM'>Malaysian Ringgit</option>
						<option <? if ($row["value"]=="MXN $") { print "selected" ; } ?> value='MXN $'>Mexican Peso</option>
						<option <? if ($row["value"]=="TWD $") { print "selected" ; } ?> value='TWD $'>New Taiwan Dollar</option>
						<option <? if ($row["value"]=="NZD $") { print "selected" ; } ?> value='NZD $'>New Zealand Dollar ($)</option>
						<option <? if ($row["value"]=="NOK kr") { print "selected" ; } ?> value='NOK kr'>Norwegian Krone</option>
						<option <? if ($row["value"]=="PHP ₱") { print "selected" ; } ?> value='PHP ₱'>Philippine Peso</option>
						<option <? if ($row["value"]=="PLN zł") { print "selected" ; } ?> value='PLN zł'>Polish Zloty</option>
						<option <? if ($row["value"]=="SGD $") { print "selected" ; } ?> value='SGD $'>Singapore Dollar ($)</option>
						<option <? if ($row["value"]=="CHF") { print "selected" ; } ?> value='CHF'>Swiss Franc</option>
						<option <? if ($row["value"]=="THB ฿") { print "selected" ; } ?> value='THB ฿'>Thai Baht</option>
						<option <? if ($row["value"]=="TRY") { print "selected" ; } ?> value='TRY'>Turkish Lira</option>
						<option <? if ($row["value"]=="USD $") { print "selected" ; } ?> value='USD $'>U.S. Dollar ($)</option>
					</select>
				</td>
			</tr>
			<tr>
				<?
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='System' AND name='enablePayments'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				$row=$result->fetch() ;
				?>
				<td> 
					<b><? print $row["nameDisplay"] ?> *</b><br/>
					<span style="font-size: 90%"><i><? print $row["description"] ?></i></span>
				</td>
				<td class="right">
					<select name="<? print $row["name"] ?>" id="<? print $row["name"] ?>" style="width: 302px">
						<?
						$selected="" ;
						if ($row["value"]=="Y" ) { $selected="selected" ; }
						print "<option $selected value='Y'>Y</option>" ;
						$selected="" ;
						if ($row["value"]=="N" ) { $selected="selected" ; }
						print "<option $selected value='N'>N</option>" ;
						?>			
					</select>
				</td>
			</tr>
			<tr>
				<?
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='System' AND name='paypalAPIUsername'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				$row=$result->fetch() ;
				?>
				<td> 
					<b><? print $row["nameDisplay"] ?></b><br/>
					<span style="font-size: 90%"><i><? print $row["description"] ?></i></span>
				</td>
				<td class="right">
					<input name="<? print $row["name"] ?>" id="<? print $row["name"] ?>" maxlength=255 value="<? print $row["value"] ?>" type="text" style="width: 300px">
				</td>
			</tr>
			<tr>
				<?
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='System' AND name='paypalAPIPassword'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				$row=$result->fetch() ;
				?>
				<td> 
					<b><? print $row["nameDisplay"] ?></b><br/>
					<span style="font-size: 90%"><i><? print $row["description"] ?></i></span>
				</td>
				<td class="right">
					<input name="<? print $row["name"] ?>" id="<? print $row["name"] ?>" maxlength=255 value="<? print $row["value"] ?>" type="text" style="width: 300px">
				</td>
			</tr>
			<tr>
				<?
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='System' AND name='paypalAPISignature'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				$row=$result->fetch() ;
				?>
				<td> 
					<b><? print $row["nameDisplay"] ?></b><br/>
					<span style="font-size: 90%"><i><? print $row["description"] ?></i></span>
				</td>
				<td class="right">
					<input name="<? print $row["name"] ?>" id="<? print $row["name"] ?>" maxlength=255 value="<? print $row["value"] ?>" type="text" style="width: 300px">
				</td>
			</tr>
			
			<tr class='break'>
				<td colspan=2> 
					<h3><? print _('Miscellaneous') ?></h3>
				</td>
			</tr>
			<tr>
				<?
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
					<b><? print $row["nameDisplay"] ?> *</b><br/>
					<span style="font-size: 90%"><i><? print $row["description"] ?></i></span>
				</td>
				<td class="right">
					<input name="<? print $row["name"] ?>" id="<? print $row["name"] ?>" maxlength=50 value="<? print $row["value"] ?>" type="text" style="width: 300px">
					<script type="text/javascript">
						var <? print $row["name"] ?>=new LiveValidation('<? print $row["name"] ?>');
						<? print $row["name"] ?>.add(Validate.Numericality);
						<? print $row["name"] ?>.add(Validate.Presence);
					 </script> 
				</td>
			</tr>
			<tr>
				<?
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
					<b><? print $row["nameDisplay"] ?></b><br/>
					<span style="font-size: 90%"><i><? print $row["description"] ?></i></span>
				</td>
				<td class="right">
					<select name="<? print $row["name"] ?>" id="<? print $row["name"] ?>" style="width: 302px">
						<?
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
							print "<option $selected value='" . $rowSelect["printable_name"] . "'>" . htmlPrep($rowSelect["printable_name"]) . "</option>" ;
						}
						?>
					</select>
				</td>
			</tr>
			<tr>
				<?
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
					<b><? print $row["nameDisplay"] ?> *</b><br/>
					<span style="font-size: 90%"><i><? print $row["description"] ?></i></span>
				</td>
				<td class="right">
					<input name="<? print $row["name"] ?>" id="<? print $row["name"] ?>" maxlength=50 value="<? print htmlPrep($row["value"]) ?>" type="text" style="width: 300px">
					<script type="text/javascript">
						var <? print $row["name"] ?>=new LiveValidation('<? print $row["name"] ?>');
						<? print $row["name"] ?>.add(Validate.Presence);
					 </script> 
				</td>
			</tr>
			<tr>
				<?
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
					<b><? print $row["nameDisplay"] ?></b><br/>
					<span style="font-size: 90%"><i><? print $row["description"] ?></i></span>
				</td>
				<td class="right">
					<textarea name="<? print $row["name"] ?>" id="<? print $row["name"] ?>" rows=8 style="width: 300px"><? print htmlPrep($row["value"]) ?></textarea>
				</td>
			</tr>
			<tr>
				<?
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
					<b><? print $row["nameDisplay"] ?> *</b><br/>
					<span style="font-size: 90%"><i><? print $row["description"] ?></i></span>
				</td>
				<td class="right">
					<select name="<? print $row["name"] ?>" id="<? print $row["name"] ?>" style="width: 302px">
						<?
						print "<option value='Please select...'>" . _('Please select...') . "</option>" ;
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
							
							print "<option $selected value='" . $rowSelect["gibbonScaleID"] . "'>" . htmlPrep($rowSelect["name"]) . "</option>" ;
						}
						?>			
					</select>
					<script type="text/javascript">
						var <? print $row["name"] ?>=new LiveValidation('<? print $row["name"] ?>');
						<? print $row["name"] ?>.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<? print _('Select something!') ?>"});
					 </script>
				</td>
			</tr>
			
			<tr>
				<td>
					<span style="font-size: 90%"><i>* <? print _("denotes a required field") ; ?></i></span>
				</td>
				<td class="right">
					<input type="hidden" name="address" value="<? print $_SESSION[$guid]["address"] ?>">
					<input type="submit" value="<? print _("Submit") ; ?>">
				</td>
			</tr>
		</table>
	</form>
<?
}
?>