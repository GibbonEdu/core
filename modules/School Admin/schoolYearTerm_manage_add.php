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

if (isActionAccessible($guid, $connection2, "/modules/School Admin/schoolYearTerm_manage_add.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print "You do not have access to this action." ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/schoolYearTerm_manage.php'>Manage Terms</a> > </div><div class='trailEnd'>Add Term</div>" ;
	print "</div>" ;
	
	if (isset($_GET["addReturn"])) { $addReturn=$_GET["addReturn"] ; } else { $addReturn="" ; }
	$addReturnMessage ="" ;
	$class="error" ;
	if (!($addReturn=="")) {
		if ($addReturn=="fail0") {
			$addReturnMessage =_("Your request failed because you do not have access to this action.") ;	
		}
		else if ($addReturn=="fail2") {
			$addReturnMessage =_("Your request failed due to a database error.") ;	
		}
		else if ($addReturn=="fail3") {
			$addReturnMessage =_("Your request failed because your inputs were invalid.") ;	
		}
		else if ($addReturn=="fail4") {
			$addReturnMessage =_("Your request failed because your inputs were invalid.") ;	
		}
		else if ($addReturn=="success0") {
			$addReturnMessage ="Your request was completed successfully.You can now add another record if you wish." ;	
			$class="success" ;
		}
		print "<div class='$class'>" ;
			print $addReturnMessage;
		print "</div>" ;
	} 
	
	?>
	<form method="post" action="<? print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/schoolYearTerm_manage_addProcess.php" ?>">
		<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
			<tr>
				<td> 
					<b>School Year *</b><br/>
					<span style="font-size: 90%"><i></i></span>
				</td>
				<td class="right">
					<select name="gibbonSchoolYearID" id="gibbonSchoolYearID" style="width: 302px">
						<?
						print "<option value='Please select...'>Please select...</option>" ;
						try {
							$data=array(); 
							$sql="SELECT * FROM gibbonSchoolYear ORDER BY sequenceNumber" ;
							$result=$connection2->prepare($sql);
							$result->execute($data);
						}
						catch(PDOException $e) { 
							print "<div class='error'>" . $e->getMessage() . "</div>" ; 
						}
						while ($row=$result->fetch()) {
							print "<option value='" . $row["gibbonSchoolYearID"] . "'>" . htmlPrep($row["name"]) . "</option>" ;
						}
						?>				
					</select>
					<script type="text/javascript">
						var gibbonSchoolYearID=new LiveValidation('gibbonSchoolYearID');
						gibbonSchoolYearID.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "Select something!"});
					 </script>
				</td>
			</tr>
			<tr>
				<td> 
					<b>Sequence Number *</b><br/>
					<span style="font-size: 90%"><i>Needs to be unique. Controls the chronological ordering of terms.</i></span>
				</td>
				<td class="right">
					<input name="sequenceNumber" id="sequenceNumber" maxlength=3 value="<? print $row["sequenceNumber"] ?>" type="text" style="width: 300px">
					<script type="text/javascript">
						var sequenceNumber=new LiveValidation('sequenceNumber');
						sequenceNumber.add(Validate.Numericality);
						sequenceNumber.add(Validate.Presence);
					 </script>
				</td>
			</tr>
			<tr>
				<td> 
					<b>Name *</b><br/>
					<span style="font-size: 90%"><i></i></span>
				</td>
				<td class="right">
					<input name="name" id="name" maxlength=20 value="<? print $row["name"] ?>" type="text" style="width: 300px">
					<script type="text/javascript">
						var name=new LiveValidation('name');
						name.add(Validate.Presence);
					 </script>
				</td>
			</tr>
			<tr>
				<td> 
					<b>Short Name *</b><br/>
					<span style="font-size: 90%"><i></i></span>
				</td>
				<td class="right">
					<input name="nameShort" id="nameShort" maxlength=4 value="" type="text" style="width: 300px">
					<script type="text/javascript">
						var nameShort=new LiveValidation('nameShort');
						nameShort.add(Validate.Presence);
					 </script>
				</td>
			</tr>
			<tr>
				<td> 
					<b>First Day *</b><br/>
					<span style="font-size: 90%"><i>dd/mm/yyyy</i></span>
				</td>
				<td class="right">
					<input name="firstDay" id="firstDay" maxlength=10 value="<? print $row["firstDay"] ?>" type="text" style="width: 300px">
					<script type="text/javascript">
						var firstDay=new LiveValidation('firstDay');
						firstDay.add(Validate.Presence);
						firstDay.add( Validate.Format, {pattern: <? if ($_SESSION[$guid]["i18n"]["dateFormatRegEx"]=="") {  print "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i" ; } else { print $_SESSION[$guid]["i18n"]["dateFormatRegEx"] ; } ?>, failureMessage: "Use <? if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?>." } ); 
					 </script>
					 <script type="text/javascript">
						$(function() {
							$( "#firstDay" ).datepicker();
						});
					</script>
				</td>
			</tr>
			<tr>
				<td> 
					<b>Last Day *</b><br/>
					<span style="font-size: 90%"><i>dd/mm/yyyy</i></span>
				</td>
				<td class="right">
					<input name="lastDay" id="lastDay" maxlength=10 value="<? print $row["lastDay"] ?>" type="text" style="width: 300px">
					<script type="text/javascript">
						var lastDay=new LiveValidation('lastDay');
						lastDay.add(Validate.Presence);
						lastDay.add( Validate.Format, {pattern: <? if ($_SESSION[$guid]["i18n"]["dateFormatRegEx"]=="") {  print "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i" ; } else { print $_SESSION[$guid]["i18n"]["dateFormatRegEx"] ; } ?>, failureMessage: "Use <? if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?>." } ); 
					 </script>
					 <script type="text/javascript">
						$(function() {
							$( "#lastDay" ).datepicker();
						});
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