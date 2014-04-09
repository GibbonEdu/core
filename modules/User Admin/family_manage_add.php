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

if (isActionAccessible($guid, $connection2, "/modules/User Admin/family_manage_add.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/User Admin/family_manage.php'>Manage Families</a> > </div><div class='trailEnd'>Add Family</div>" ;
	print "</div>" ;
	
	if (isset($_GET["addReturn"])) { $addReturn=$_GET["addReturn"] ; } else { $addReturn="" ; }
	$addReturnMessage="" ;
	$class="error" ;
	if (!($addReturn=="")) {
		if ($addReturn=="fail0") {
			$addReturnMessage=_("Your request failed because you do not have access to this action.") ;	
		}
		else if ($addReturn=="fail2") {
			$addReturnMessage=_("Your request failed due to a database error.") ;	
		}
		else if ($addReturn=="fail3") {
			$addReturnMessage=_("Your request failed because your inputs were invalid.") ;	
		}
		else if ($addReturn=="fail4") {
			$addReturnMessage=_("Your request failed because your inputs were invalid.") ;	
		}
		else if ($addReturn=="fail5") {
			$addReturnMessage="Your request failed because your passwords did not match." ;	
		}
		else if ($addReturn=="success0") {
			$addReturnMessage=_("Your request was completed successfully.You can now add another record if you wish.") ;	
			$class="success" ;
		}
		print "<div class='$class'>" ;
			print $addReturnMessage;
		print "</div>" ;
	} 
	
	$search=$_GET["search"] ;
	if ($search!="") {
		print "<div class='linkTop'>" ;
			print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/User Admin/family_manage.php&search=$search'>" . _('Back to Search Results') . "</a>" ;
		print "</div>" ;
	}
	?>
	<form method="post" action="<? print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/family_manage_addProcess.php?search=$search" ?>">
		<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
			<tr class='break'>
				<td colspan=2>
					<h3>
						General Information
					</h3>
				</td>
			</tr>
			<tr>
				<td> 
					<b>Family Name *</b><br/>
					<span style="font-size: 90%"><i></i></span>
				</td>
				<td class="right">
					<input name="name" id="name" maxlength=100 value="" type="text" style="width: 300px">
					<script type="text/javascript">
						var name=new LiveValidation('name');
						name.add(Validate.Presence);
					 </script>
				</td>
			</tr>
			<tr>
				<td> 
					<b>Status *</b><br/>
				</td>
				<td class="right">
					<select name="status" id="status" style="width: 302px">
						<option value="Married">Married</option>
						<option value="Separated">Separated</option>
						<option value="Divorced">Divorced</option>
						<option value="De Facto">De Facto</option>
						<option value="Other">Other</option>	
					</select>
				</td>
			</tr>
			<tr>
				<td> 
					<b>Home Language</b><br/>
					<span style="font-size: 90%"><i>Formal name to address parents with.</i></span>
				</td>
				<td class="right">
					<input name="languageHome" id="languageHome" maxlength=100 value="" type="text" style="width: 300px">
				</td>
			</tr>
			<tr>
				<td> 
					<b>Address Name *</b><br/>
					<span style="font-size: 90%"><i>Formal name to address parents with.</i></span>
				</td>
				<td class="right">
					<input name="nameAddress" id="nameAddress" maxlength=100 value="" type="text" style="width: 300px">
					<script type="text/javascript">
						var nameAddress=new LiveValidation('nameAddress');
						nameAddress.add(Validate.Presence);
					 </script>
				</td>
			</tr>
			<tr>
				<td> 
					<b>Home Address</b><br/>
					<span style="font-size: 90%"><i>Unit, Building, Street</i></span>
				</td>
				<td class="right">
					<input name="homeAddress" id="homeAddress" maxlength=255 value="" type="text" style="width: 300px">
				</td>
			</tr>
			<tr>
				<td> 
					<b>Home Address (District)</b><br/>
					<span style="font-size: 90%"><i>County, State, District</i></span>
				</td>
				<td class="right">
					<input name="homeAddressDistrict" id="homeAddressDistrict" maxlength=30 value="" type="text" style="width: 300px">
				</td>
				<script type="text/javascript">
					$(function() {
						var availableTags=[
							<?
							try {
								$dataAuto=array(); 
								$sqlAuto="SELECT DISTINCT name FROM gibbonDistrict ORDER BY name" ;
								$resultAuto=$connection2->prepare($sqlAuto);
								$resultAuto->execute($dataAuto);
							}
							catch(PDOException $e) { }
							while ($rowAuto=$resultAuto->fetch()) {
								print "\"" . $rowAuto["name"] . "\", " ;
							}
							?>
						];
						$( "#homeAddressDistrict" ).autocomplete({source: availableTags});
					});
				</script>
			</tr>
			<tr>
				<td> 
					<b>Home Address (Country)</b><br/>
				</td>
				<td class="right">
					<select name="homeAddressCountry" id="homeAddressCountry" style="width: 302px">
						<?
						print "<option value=''></option>" ;
						try {
							$dataSelect=array(); 
							$sqlSelect="SELECT printable_name FROM gibbonCountry ORDER BY printable_name" ;
							$resultSelect=$connection2->prepare($sqlSelect);
							$resultSelect->execute($dataSelect);
						}
						catch(PDOException $e) { }
						while ($rowSelect=$resultSelect->fetch()) {
							print "<option value='" . $rowSelect["printable_name"] . "'>" . htmlPrep($rowSelect["printable_name"]) . "</option>" ;
						}
						?>				
					</select>
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